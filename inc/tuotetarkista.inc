<?php

if (!function_exists("tuotetarkista")) {
  function tuotetarkista(&$t, $i, $result, $tunnus, &$virhe, $trow) {
    global $kukarow, $yhtiorow, $alias_set, $laji, $poistolukko, $luedata_toiminto;

    static $tem_tuoteno, $tem_tullinimike1, $tem_eisaldoa, $tem_kehahin, $tem_kehahin_ind;
    static $tem_tuotetyyppi, $tem_status, $tem_status_vanha, $tem_try, $tem_try_vanha;
    static $tem_eankoodi, $tem_yksikko, $tem_nimitys, $tem_tuotemassa;

    $fieldname = mysql_field_name($result, $i);

    if ($fieldname == "tuoteno") {
      if (trim($t[$i]) == '') {
        $virhe[$i] = t("Tieto puuttuu");
      }
      else {
        $temp_ekakirjain = (substr(trim($t[$i]), 0, 1));

        if (($temp_ekakirjain == '*') or ($temp_ekakirjain == '@') or ($temp_ekakirjain == '?')) {
          $virhe[$i] = t("Tuotenumero ei voi alkaa merkeillä *, @ tai ?");
        }
        else {
          if (substr(trim($t[$i]), -1) == '*') {
            $virhe[$i] = t("Tuotenumero ei voi loppua merkkiin *");
          }
          else {
            if ($tunnus == "") {
              $query  = "SELECT yhtio
                         FROM tuote
                         WHERE yhtio = '$kukarow[yhtio]'
                         and tuoteno = '".trim($t[$i])."'";
              $chkressiresult = pupe_query($query);

              if (mysql_num_rows($chkressiresult) > 0) {
                $virhe[$i] = t("Tuotenumero on jo perustettu järjestelmään");
              }
            }
          }
        }
      }

      $tem_tuoteno = $t[$i];

      // Onko tuotteella tapahtumia
      if ($poistolukko != "" and ($poistolukko != "LUEDATA" or ($poistolukko == "LUEDATA" and strtoupper($luedata_toiminto) == "POISTA"))) {
        onkotapahtumajariveja($tem_tuoteno);

        if ($poistolukko != "" and strtoupper($luedata_toiminto) == "POISTA") {
          $virhe[$i] = t("Tuotetta ei saa poistaa, koska tuotteella on tilausrivejä/tapahtumia")."!";
        }
      }
    }

    if (($fieldname == "projekti" or $fieldname == "kohde" or $fieldname == "kustp") and (int) trim($t[$i]) != 0) {

      if ($fieldname == "projekti") $kptyyppi = "P";
      if ($fieldname == "kohde")    $kptyyppi = "O";
      if ($fieldname == "kustp")    $kptyyppi = "K";

      $tpque = "SELECT tunnus
                from kustannuspaikka
                where yhtio   = '$kukarow[yhtio]'
                and tyyppi    = '$kptyyppi'
                and kaytossa != 'E'
                and tunnus    = '$t[$i]'";
      $tpres = pupe_query($tpque);

      if (mysql_num_rows($tpres) == 0) {
        $virhe[$i] .= t("Kustannuspaikkaa ei löydy")."!";
      }
    }

    if ($laji != "V" and
      ($fieldname == "osasto"
        or $fieldname == "try"
        or $fieldname == "tuotemerkki"
        or $fieldname == "malli"
        or $fieldname == "mallitarkenne")) {

      // Ei tehdä tarkistuksia jos tietoa ei oo syötetty
      if (empty($t[$i])) {
        return;
      }

      $t[$i] = trim($t[$i]);

      $tpque = "SELECT tunnus
                FROM avainsana
                WHERE yhtio = '$kukarow[yhtio]'
                and LAJI    = '$fieldname'";
      $tpres = pupe_query($tpque);

      if (mysql_num_rows($tpres) > 0) {

        $tpque = "SELECT tunnus
                  FROM avainsana
                  WHERE yhtio = '$kukarow[yhtio]'
                  AND LAJI    = '$fieldname'
                  AND SELITE  = '$t[$i]'";
        $tpres = pupe_query($tpque);

        if (mysql_num_rows($tpres) == 0) {
          $virhe[$i] .= t("Avainsanaa ei löydy")."!";
          return;
        }

        if ($fieldname == "try") {
          $tem_try = $t[$i];
          if (!empty($trow)) {
            $tem_try_vanha = $trow[$i];
            $tem_status_vanha = $trow['status'];
          }
          else {
            $tem_try_vanha = '';
            $tem_status_vanha = '';
          }
        }

        if ($fieldname == "try" and !empty($trow) and $tem_try_vanha != $tem_try) {
          list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($tem_tuoteno);

          if ($saldo == 0) {

            $query = "SELECT tuotepaikat.*,
                      concat_ws('-', hyllyalue, hyllynro, hyllyvali, hyllytaso) as hylly
                      FROM tuotepaikat
                      WHERE yhtio = '{$kukarow['yhtio']}'
                      AND tuoteno = '{$tem_tuoteno}'
                      ORDER BY oletus ASC";
            $tuotepaikat_result = pupe_query($query);
            $poista = array();

            while ($tuotepaikka = mysql_fetch_assoc($tuotepaikat_result)) {
              $poista[] = $tuotepaikka;
            }

            $tee = "MUUTA";
            $tuoteno = $tem_tuoteno;
            $halyraja2 = array();
            $tilausmaara2 = array();
            $kutsuja = "tuotetarkista.inc";
            require "muuvarastopaikka.php";
          }
        }
      }
    }

    if ($fieldname == "kehahin" and $yhtiorow["palvelutuotteiden_kehahinnat"] == "K") {
      $tem_kehahin = $t[$i];
      $tem_kehahin_ind = $i;
    }

    if ($fieldname == "nimitys") {
      $tem_nimitys = $t[$i];

      if (trim($t[$i]) == '') {
        $virhe[$i] .= t("Nimitys puuttuu")."!";
      }
    }

    if (($fieldname == "tilino" or
        $fieldname == "tilino_eu" or
        $fieldname == "tilino_ei_eu" or
        $fieldname == "tilino_marginaali" or
        $fieldname == "tilino_osto_marginaali") and $t[$i] != "") {

      $query = "SELECT yhtio
                FROM tili
                WHERE yhtio = '$kukarow[yhtio]'
                and tilino  = '$t[$i]'";
      $sresult = pupe_query($query);

      if (mysql_num_rows($sresult) != 1) {
        $virhe[$i] = t("Tiliä ei löydy");
      }
    }

    if ($fieldname == "tilino" and trim($t[$i]) == "" and $laji == "V") {
      $virhe[$i] = t("Viranomaistuotteella on oltava tili")."!";
    }

    if (($fieldname == "tullinimike1") and ($t[$i] != 0)) {
      $query = "SELECT cn
                FROM tullinimike
                WHERE cn = '$t[$i]' and kieli = '$yhtiorow[kieli]'";
      $sresult = pupe_query($query);

      if (mysql_num_rows($sresult) != 1) {
        $virhe[$i] = t("Virheellinen tullinimike");
      }

      $tem_tullinimike1 = $t[$i];
    }

    if ($fieldname == "tullinimike2" and $tem_tullinimike1 != "") {
      // jos tyhjää laitetaan nollaksi
      if ($t[$i] == "") $t[$i] = "00";
    }

    if ($fieldname == "yksikko") {
      $tem_yksikko = $t[$i];
    }

    if ($fieldname == "tuotemassa") {
      $tem_tuotemassa = $t[$i];
    }

    if ($fieldname == "tuotetyyppi") {
      if (isset($t[$i])) {
        // Tyyppiä ei saa vaihtaa, jos tuotteella on yksikin tapahtuma
        if (trim(strtoupper($trow["tuotetyyppi"])) != trim(strtoupper($t[$i]))) {

          // Onko tuotteella tapahtumia
          if ($poistolukko != "" and $poistolukko != "TSEKATTU") {
            onkotapahtumajariveja($tem_tuoteno);
          }

          if (trim(strtoupper($trow["tuotetyyppi"])) == "") {
            if ($poistolukko != "" and ($t[$i] == 'R' or $t[$i] == 'K' or $t[$i] == 'M')) {
              $virhe[$i] = t("Tuotetyyppiä ei voida muuttaa, koska tuotteella on tapahtumia")."!";
            }
          }
          elseif ($poistolukko != "") {
            $virhe[$i] = t("Tuotetyyppiä ei voida muuttaa, koska tuotteella on tapahtumia")."!";
          }
        }

        $tem_tuotetyyppi = $t[$i];
      }
    }

    if ($fieldname == "sarjanumeroseuranta") {

      // Onko tuotteella tapahtumia
      if ($poistolukko != "" and $poistolukko != "TSEKATTU") {
        onkotapahtumajariveja($tem_tuoteno);
      }

      if ($poistolukko != "" and $t[$i] != $trow["sarjanumeroseuranta"]) {
        $query = "SELECT count(*) saldo
                  FROM tuotepaikat
                  WHERE yhtio  = '$kukarow[yhtio]'
                  and tuoteno  = '$tem_tuoteno'
                  and saldo   != 0";
        $vresult = pupe_query($query);
        $vrow = mysql_fetch_array($vresult);

        // Tietyt vaihdot sallitaan!!!, huomaa NOT iffissä, helpompi kirjoittaa näin päin
        if (!(($t[$i] == ""  and $vrow["saldo"] == 0 and in_array($trow["sarjanumeroseuranta"], array("", "T", "V", "E", "F")))
            or ($t[$i] == "T" and $vrow["saldo"] == 0 and in_array($trow["sarjanumeroseuranta"], array("", "T", "V", "E", "F")))
            or ($t[$i] == "V" and $vrow["saldo"] == 0 and in_array($trow["sarjanumeroseuranta"], array("", "T", "V", "E", "F")))
            or ($t[$i] == "E" and (in_array($trow["sarjanumeroseuranta"], array("E", "F")) or ($vrow["saldo"] == 0 and in_array($trow["sarjanumeroseuranta"], array("", "T", "V", "E", "F")))))
            or ($t[$i] == "F" and (in_array($trow["sarjanumeroseuranta"], array("E", "F")) or ($vrow["saldo"] == 0 and in_array($trow["sarjanumeroseuranta"], array("", "T", "V", "E", "F"))))))) {
          $virhe[$i] = t("Seurantatyyppiä ei voida muuttaa, koska tuotteella on tapahtumia")."!";
        }
      }
    }

    if ($fieldname == "ei_saldoa") {
      if (isset($t[$i])) {
        $query = "SELECT *
                  FROM tuotepaikat
                  WHERE tuoteno = '$tem_tuoteno'
                  AND yhtio     = '$kukarow[yhtio]'";
        $sresult = pupe_query($query);

        if ($t[$i] != '' and mysql_num_rows($sresult) > 0) {
          $virhe[$i] = t("Tuotteella on varastopaikkoja. Poista ne ensin!");
        }

        // Onko tuotteella tapahtumia
        if ($poistolukko != "" and $poistolukko != "TSEKATTU") {
          onkotapahtumajariveja($tem_tuoteno);
        }

        if ($poistolukko != "" and isset($trow['ei_saldoa']) and $t[$i] != $trow["ei_saldoa"]) {
          $virhe[$i] = t("Tuotteella on tapahtumia. Saldottomuutta ei voi vaihtaa")."!";
        }

        $tem_eisaldoa = $t[$i];
      }
    }

    // Viranomaistuotteita ei tsekata...
    if ($fieldname == "vienti" and $laji != "V" and $tem_tuotetyyppi != "A" and $tem_tuotetyyppi != "B") {

      // Katsotaan, ettei ole sekaisin plussia ja miinuksia
      if ($t[$i] != '' and strpos($t[$i], "+") !== FALSE and strpos($t[$i], "-") !== FALSE) {
        $virhe[$i] = t("Laita samanaikaisesti vain + tai - maakoodeja!")."<br>";
      }
      elseif ($t[$i] != '') {

        // Käyttäjät voi erotella myös maat pilkuilla, joten vaihdetaan ne tässä spaceiksi, trimmataan ja uppercasetaan
        $t[$i] = str_replace(",", " ", strtoupper(trim($t[$i])));

        // Poistetaan ylimääräiset spacet (kaksi tai enemmän spaceja yhdeksi spaceksi)
        $t[$i] = preg_replace("/ {2,}/", " ", $t[$i]);

        // Käydään läpi syötetyt arvot, splitataan välilyönnistä
        $apu_vientikiellot = explode(" ", $t[$i]);

        foreach ($apu_vientikiellot as $apu_vientikielto) {

          // varmistetaan, että löytyy plus tai miinusmerkki
          if (strpos($apu_vientikielto, "+") === FALSE and strpos($apu_vientikielto, "-") === FALSE) {
            $virhe[$i] .= t("Maakoodi virheellisessä muodossa").": '$apu_vientikielto'<br>";
          }
          // varmistetaan, että plus tai miinus on eka merkki
          elseif (strpos($apu_vientikielto, "+") != 0 or strpos($apu_vientikielto, "-") != 0) {
            $virhe[$i] .= t("Maakoodi virheellisessä muodossa").": '$apu_vientikielto'<br>";
          }
          else {
            // Otetaan eka +/- pois
            $apu_vientikielto = substr($apu_vientikielto, 1);

            // Katsotaan löytyykö annettu maakoodi
            $query = "SELECT distinct koodi
                      FROM maat
                      WHERE koodi = '$apu_vientikielto'";
            $vientikielto_result = pupe_query($query);

            if (mysql_num_rows($vientikielto_result) == 0) {
              $virhe[$i] .= "Virheellinen maakoodi $apu_vientikielto!<br>";
            }
          }
        }
      }

      if ($virhe[$i] != "") {
        $virhe[$i] .= t("Syötä tieto muodossa +MAAKOODI tai -MAAKOODI ja erottele maat välilyönnillä.");
      }
    }

    if ($fieldname == "eankoodi") {
      $tem_eankoodi = $t[$i];

      if (trim($t[$i]) != 0 and trim($t[$i] != '')) {
        $query  = "SELECT eankoodi
                   FROM tuote
                   WHERE yhtio   = '$kukarow[yhtio]'
                   and eankoodi  = '".trim($t[$i])."'
                   and tuoteno  != '$tem_tuoteno'";
        $chkressiresult = pupe_query($query);

        if (mysql_num_rows($chkressiresult) > 0) {
          $virhe[$i] = t("EAN-koodi on jo perustettu järjestelmään");
        }

        if ($yhtiorow["tarkista_eankoodi"] == "K" and tarkista_ean13(trim($t[$i])) === FALSE) {
          $virhe[$i] = t("EAN-koodi on virheellinen");
        }
      }
    }

    if (mysql_field_name($result, $i) == "valmistusaika_sekunneissa") {
      $valmistusaika_sekunneissa = trim($t[$i]);

      if ($valmistusaika_sekunneissa != "" and (!is_numeric($valmistusaika_sekunneissa) or $valmistusaika_sekunneissa < 0)) {
        $virhe[$i] = t("Valmistusaika pitää olla suurempi tai yhtäsuuri kuin 0");
      }
    }

    if ($fieldname == 'status') {
      // trim ja upcase statukselle
      $t[$i] = strtoupper(trim($t[$i]));

      $tem_status = $t[$i];
      $tem_status_vanha = $trow[$i];

      if (!in_array($tem_status, array('A', 'P', 'E', 'T', 'X'))) {
        $virhe[$i] = t("Status tulee olla A, P, E, T tai X");
      }

      if (empty($virhe) and !empty($trow) and $tem_status_vanha != $tem_status) {
        //jos tuotteella status muutetaan ja tuotteella on vain oletuspaikka ja saldo on nolla,
        //niin tuotteen muokkauksen yhteydessä mitätöidään nykyinen tuotepaikka + tapahtumiin jälki ja perustetaan määritellyin ehdoin uudet tuotepaikat.
        list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($tem_tuoteno);

        if ($saldo == 0) {

          $query = "SELECT tuotepaikat.*,
                    concat_ws('-', hyllyalue, hyllynro, hyllyvali, hyllytaso) as hylly
                    FROM tuotepaikat
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND tuoteno = '{$tem_tuoteno}'
                    ORDER BY oletus ASC";
          $tuotepaikat_result = pupe_query($query);
          $poista = array();
          while ($tuotepaikka = mysql_fetch_assoc($tuotepaikat_result)) {
            $poista[] = $tuotepaikka;
          }

          $tee = "MUUTA";
          $tuoteno = $tem_tuoteno;
          $halyraja2 = array();
          $tilausmaara2 = array();
          $kutsuja = "tuotetarkista.inc";
          require "muuvarastopaikka.php";
        }
      }
      // Jos kyseessä tuotteen poisto, tarkistetaan löytyykö tuotetta avoimilta ostotilauksilta, jos löytyy niin poistoa ei sallita.
      if (empty($virhe) and $tem_status == 'P') {
        $query = "SELECT tuoteno
                  FROM tilausrivi
                  WHERE yhtio  = '$kukarow[yhtio]'
                  AND tyyppi   = 'O'
                  AND tuoteno  = '$tem_tuoteno'
                  AND kpl      = 0
                  AND varattu != 0";
        $result = pupe_query($query);

        if (mysql_num_rows($result) > 0) {
          $virhe[$i] = t("Tuotetta löytyy avoimelta ostotilaukselta, ei voida merkata poistetuksi");
        }
      }
    }

    // ei piirretä ellei yhtiöparametri ohjeista katsomaan täältä
    if ($fieldname == "suoratoimitus") {
      if (in_array($yhtiorow["tee_osto_myyntitilaukselta"], array('V', 'W', 'I', 'J'))) {
        if (!in_array($t[$i], array('S', 'U', 'X', ''))) {
          $virhe[$i] = t("Suoratoimitustieto virheellinen");
        }
      }
    }

    //tehdään nää jutut tässä koska tämä on aina viimeinen sarake
    if ($fieldname == "tunnus") {

      if ($tem_kehahin != ""
        and $tem_kehahin != $trow["kehahin"]
        and ((isset($tem_eisaldoa)
            and $tem_eisaldoa == "")
          or (!isset($tem_eisaldoa)
            and $trow["ei_saldoa"] == ""))) {
        // Pitää aina setata myös "tämän" kentän virhe, muuten luedata ei toimi
        $virhe[$i] = $virhe[$tem_kehahin_ind] = t("Saldollisen tuotteen hankintahintaa ei voi muuttaa")."!";
      }

      if ($tem_kehahin != ""
        and $tem_kehahin != $trow["kehahin"]
        and count($virhe) == 0
        and $yhtiorow["palvelutuotteiden_kehahinnat"] == "K"
        and ((isset($tem_eisaldoa)
            and $tem_eisaldoa == "")
          or (!isset($tem_eisaldoa)
            and $trow["ei_saldoa"] == ""))) {

        $tem_kehahin = sprintf("%.6f", $tem_kehahin);

        $query = "INSERT into tapahtuma set
                  yhtio     = '$kukarow[yhtio]',
                  tuoteno   = '$tem_tuoteno',
                  kpl       = '0',
                  kplhinta  = '$tem_kehahin',
                  hinta     = '$tem_kehahin',
                  laji      = 'tulo',
                  hyllyalue = '',
                  hyllynro  = '',
                  hyllyvali = '',
                  hyllytaso = '',
                  selite    = '".t("Tuotteen kehahinta vaihdettiin")." $trow[kehahin] --> $tem_kehahin',
                  laatija   = '$kukarow[kuka]',
                  laadittu  = now()";
        $chkressires = pupe_query($query);
      }

      # Kun nämä tiedot muuttuvat, tuote täytyy siirtää uudestaan ulkoiseen järjestelmään
      # Tuotteen avainsana "synkronointi" kertoo milloin tuote täytyy siirtää
      # Jos avainsanaa ei ole olemassa tuotteella tai selite on tyhjää, siirretään tuote seuraavalla
      # synkronointiajolla ulkoiseen järjestelmään
      if (count($virhe) == 0 and
        ($tem_status     != $trow['status']     or
          $tem_eankoodi   != $trow['eankoodi']   or
          $tem_yksikko    != $trow['yksikko']    or
          $tem_nimitys    != $trow['nimitys']    or
          $tem_tuotemassa != $trow['tuotemassa'] or
          $tem_try        != $tem_try_vanha)) {

        $query = "UPDATE tuotteen_avainsanat SET
                  selite      = ''
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND laji    = 'synkronointi'
                  AND tuoteno = '{$tem_tuoteno}'";
        $updres = pupe_query($query);
      }

      if (count($virhe) == 0
        and $tem_tuotetyyppi != "A"
        and $tem_tuotetyyppi != "B"
        and $yhtiorow["tuotteen_oletuspaikka"] != ""
        and ((isset($tem_eisaldoa)
            and $tem_eisaldoa == "")
          or (!isset($tem_eisaldoa)
            and $trow["ei_saldoa"] == ""))) {

        $query = "SELECT *
                  FROM tuotepaikat
                  WHERE tuoteno = '$tem_tuoteno'
                  and yhtio     = '$kukarow[yhtio]'";
        $sresult = pupe_query($query);

        if (mysql_num_rows($sresult) == 0) {
          $kaikki_oletuspaikat = hae_kaikki_oletuspaikat_try_tai_status($tem_try, $tem_status);
          $_counter = 0;

          foreach ($kaikki_oletuspaikat as $_tuotepaikka) {
            list($hyllyalue, $hyllynro, $hyllyvali, $hyllytaso) = explode("-", trim($_tuotepaikka));

            if ($hyllyalue == "" or !isset($hyllyalue)) {
              $hyllyalue = 0;
            }
            if ($hyllynro == "" or !isset($hyllynro)) {
              $hyllynro = 0;
            }
            if ($hyllyvali == "" or !isset($hyllyvali)) {
              $hyllyvali = 0;
            }
            if ($hyllytaso == "" or !isset($hyllytaso)) {
              $hyllytaso = 0;
            }

            $oletus_x = $_counter == 0 ? 'X' : '';

            $query = "SELECT *
                      FROM tuotepaikat
                      WHERE yhtio   = '{$kukarow['yhtio']}'
                      AND tuoteno   = '{$tem_tuoteno}'
                      AND hyllyalue = '{$hyllyalue}'
                      AND hyllynro  = '{$hyllynro}'
                      AND hyllytaso = '{$hyllytaso}'
                      AND hyllyvali = '{$hyllyvali}'";
            $paikka_chk_res = pupe_query($query);

            if (mysql_num_rows($paikka_chk_res) == 0) {
              lisaa_tuotepaikka($tem_tuoteno, $hyllyalue, $hyllynro, $hyllyvali, $hyllytaso, "Tuoteylläpidossa", $oletus_x, 0, 0, 0);

              if (empty($trow["luedata_from"])) {
                echo "<font class='message'>".t("Lisättiin varastopaikka")." {$_tuotepaikka}</font></br>";
              }

              $_counter++;
            }
          }
        }
      }
    }
    //tarkistetaan ettei yritetä laittaa ostoehdotus sarakkeeseen vääriä kirjaimia! sallitut "E" = ei ja "" = kyllä
    if ($fieldname == "ostoehdotus") {
      if ($t[$i] != "E" and $t[$i] != "") {
        $virhe[$i] = t("Ostoehdotus arvo tulee olla joko E tai tyhjä! (E = ei näytetä ja tyhjä = näytetään)");
      }
    }
  }
}
