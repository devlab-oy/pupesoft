<?php

$query  = "SELECT *
           FROM lasku
           WHERE tunnus  = '$otunnus'
           AND yhtio     = '$kukarow[yhtio]'
           AND tila      = 'K'
           AND alatila  != 'X'";
$result = pupe_query($query);

if (mysql_num_rows($result) == 1) {
  $laskurow = mysql_fetch_assoc($result);
}
else {
  echo "<font style='error'>".t("VIRHE: Saapumista ei enää löydy")."!</font>";
  exit;
}

if ($tee == 'excel') {
  $excel_array = array(
    'laskurow' => $laskurow,
    'liitetyt_laskut' => array(),
  );

  $excel_array_check = true;
}
else {
  $excel_array_check = false;
}

$query = "SELECT *
          FROM toimi
          WHERE yhtio = '{$kukarow['yhtio']}'
          AND tunnus  = '{$toimittajaid}'";
$toimires = pupe_query($query);
$toimirow = mysql_fetch_assoc($toimires);

if (!isset($tila))         $tila = "";
if (!isset($tee))         $tee = "";
if (!isset($nayta_kaikki))     $nayta_kaikki = "";

if (!isset($nayta_kohdistetut)) {
  if (isset($lisarajaus) and $lisarajaus != '' and ($lisarajaus == 'liitetty_lasku_rivitok_kohdistus_eiok' or $lisarajaus == 'liitetty_lasku_rivitok_kohdistus_ok')) {

    if ($toimirow['asn_sanomat'] == '') {
      $nayta_kohdistetut = "hinta";
    }
    else {
      $nayta_kohdistetut = "lasku";
    }
  }
}

//Kukarow kesken päivitetään kohdistuksessa jotta kukaan ei voi samalla viedä varaston ja tehdä kohdistuksia
if ($kukarow["kesken"] == 0 or $kukarow["kesken"] != $laskurow["tunnus"]) {
  $query = "UPDATE kuka
            SET kesken = '$laskurow[tunnus]'
            WHERE yhtio = '$kukarow[yhtio]' AND
            kuka        = '$kukarow[kuka]' AND
            session     = '$session'";
  $result = pupe_query($query);
}

// katsotaan onko tälle keikalle jo liitetty vaihto-omaisuuslaskuja (kotimaa, eu tai ei-eu)
$kulurow = saapumisen_laskut($laskurow);

if ($excel_array_check) {
  $excel_array['kulurow'] = $kulurow;
}

//nyt kohdistus on onnistunut sentilleen. Ollan valmiita ja tulostetaan purkulistat
if ($tila == 'valmis') {

  $meniko_pennilleen_oikein = false;

  // päivitetään kohdistettu=K kun kohdistus on pennilleen ok...
  if ($laskunsumma == 0 and ($laskurow["summa"] == 0 or $kulurow["abssumma"] != 0)) {
    $query = "UPDATE lasku
              SET kohdistettu='K'
              WHERE tunnus='$otunnus'
              and yhtio='$kukarow[yhtio]'";
    $result = pupe_query($query);

    $meniko_pennilleen_oikein = true;
  }

  $query = "  UPDATE tilausrivi set toimaika='$laskurow[toimaika]' WHERE otunnus='$otunnus' and tyyppi='O' and yhtio='$kukarow[yhtio]'";
  $result = pupe_query($query);

  //Vapautetaan 'tilaus'
  $query = "UPDATE kuka set kesken=0 where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
  $result = pupe_query($query);

  list(, , , $kohok, $kplvarasto, $kplyhteensa, , $lisok, $llrow, $sarjanrook, , , , , , $varok) = tsekit($laskurow, 0);

  // jos lisätiedot, kohdistus ja paikat on ok sekä kaikki rivit on viety varastoon, ja kaikki liitetyt laskut on hyväksytty (kukarow.taso 3 voi ohittaa tämän), niin saadaan laskea virallinen varastonarvo
  if ($lisok == 1 and $meniko_pennilleen_oikein and $varok == 1 and $kplyhteensa == $kplvarasto and $sarjanrook == 1 and (($llrow["volasku"] == $llrow["volasku_ok"] and $llrow["kulasku"] == $llrow["kulasku_ok"]) or $kukarow["taso"] == "3")) {
    echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL={$palvelin2}tilauskasittely/keikka.php?otunnus={$otunnus}&tee=&toiminto=kaikkiok&toimittajaid={$toimittajaid}'>";
    exit;
  }
  else {
    $otunnus  = "";
    $toiminto = "";
    $tee     = "";
    $ytunnus  = $laskurow["ytunnus"];
  }
}

// päivitetään rivin hinta alkuperäisellä hinnalla
if ($tila == 'paivita_ostohinta' and (int) $riviid > 0) {

  if ($tila_toiminto == 'yes') {

    $riviid = (int) $riviid;

    $query = "SELECT tuoteno, hinta
              FROM tilausrivi
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = '{$riviid}'";
    $tuoteno_fetch_res = pupe_query($query);
    $tuoteno_fetch_row = mysql_fetch_assoc($tuoteno_fetch_res);

    $oshinq = "UPDATE tuotteen_toimittajat
               SET ostohinta    = '{$tuoteno_fetch_row['hinta']}'
               WHERE yhtio      = '{$kukarow['yhtio']}'
               AND tuoteno      = '{$tuoteno_fetch_row['tuoteno']}'
               AND liitostunnus = '{$laskurow['liitostunnus']}'";
    $ishinr = pupe_query($oshinq);


  }

  $query = "UPDATE tilausrivi SET
            var2        = 'O'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus  = '{$riviid}'";
  $upd_res = pupe_query($query);

  $tila = '';
}

//muokataan tilausriviä
if ($tila == 'muokkaa_rivi' and $tee != 'TI') {

  $query = "SELECT tilausrivi.*, tuote.sarjanumeroseuranta, tuote.myyntihinta_maara
            FROM tilausrivi use index (PRIMARY)
            LEFT JOIN tuote use index (tuoteno_index) ON (tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.ei_saldoa = '')
            WHERE tilausrivi.tunnus = '$riviid'
            and tilausrivi.yhtio    = '$kukarow[yhtio]'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {
    echo t("Tilausriviä ei löydy")."! $query";
    exit;
  }
  $trow = mysql_fetch_assoc($result);

  $query = "DELETE from tilausrivi
            WHERE tunnus = '$riviid' and yhtio='$kukarow[yhtio]'";
  $result = pupe_query($query);

  // Tehdään pari juttua jos tuote on sarjanuerosaurannassa
  if ($trow["sarjanumeroseuranta"] != '') {

    //Nollataan sarjanumero
    if ($trow["varattu"] > 0) {
      $tunken = "ostorivitunnus";
    }
    else {
      $tunken = "myyntirivitunnus";
    }

    $query = "SELECT group_concat(tunnus) tunnukset
              FROM sarjanumeroseuranta
              WHERE yhtio = '$kukarow[yhtio]'
              and tuoteno = '$trow[tuoteno]'
              and $tunken = '$trow[tunnus]'";
    $sarjares = pupe_query($query);
    $sarjarow = mysql_fetch_assoc($sarjares);

    //Pidetään sarjatunnus muistissa
    $osto_sarjatunnus = $sarjarow["tunnukset"];

    $query = "UPDATE sarjanumeroseuranta
              SET $tunken = 0
              WHERE yhtio = '$kukarow[yhtio]'
              and tuoteno = '$trow[tuoteno]'
              and $tunken = '$trow[tunnus]'";
    $sarjares = pupe_query($query);
  }


  $tuoteno     = $trow["tuoteno"];
  $hinta       = $trow["hinta"];
  $kpl       = $trow["varattu"];
  $toimaika     = $trow["toimaika"];
  $kerayspvm    = $trow["kerayspvm"];
  $alv       = $trow["alv"];
  $var       = $trow["var"];
  $perheid2    = $trow["perheid2"];
  $kommentti     = $trow["kommentti"];
  $rivinotunnus  = $trow["otunnus"];
  $rivitunnus    = $riviid;
  $hinta_alkuperainen = $trow['hinta_alkuperainen'];

  if ($trow["myyntihinta_maara"] != 0 and $hinta != 0) {
    $hinta = hintapyoristys($hinta * $trow["myyntihinta_maara"]);
  }

  for ($alepostfix = 1; $alepostfix <= $yhtiorow["oston_alekentat"]; $alepostfix++) {
    ${'ale'.$alepostfix} = $trow["ale{$alepostfix}"];
  }

  echo t("Muuta riviä").":<br>";

  //pidetään kaikki muuttujat tallessa
  $muut_siirrettavat = $nayta_kohdistetut."!¡!".$nayta_kaikki."!¡!".$toimittajaid;

  //rivien splittausvaihtoehtot näkyviin
  $automatiikka = 'ON';

  $saapuminen_ostorivin_muokkaus = 1;

  require 'syotarivi_ostotilaus.inc';
  require '../inc/footer.inc';
  exit;
}

//Lisätään sarjanumeroita
if ($tila == "sarjanumerot") {
  //pidetään kaikki muuttujat tallessa
  $muut_siirrettavat = $nayta_kohdistetut."!¡!".$nayta_kaikki."!¡!".$toimittajaid;

  $PHP_SELF = "sarjanumeroseuranta.php";

  require "sarjanumeroseuranta.php";
  exit;
}

//kohdistetaan tiedostosta
if ($tila == 'FAILISTA') {
  require 'ostotilausten_rivien_kohdistus_tiedostosta.inc';
}

//annetaan mahis tehdä kokonaan uusi rivi
if ($tila == 'uusirivi') {

  // Liitetään rivi tähän isäriviin
  if ($perheid2 > 0) {
    $query = "UPDATE tilausrivi SET perheid2='$perheid2' where yhtio='$kukarow[yhtio]' and tunnus='$perheid2' and tyyppi='O'";
    $result = pupe_query($query);
  }

  echo t("Tee uusi rivi").":<br>";

  //pidetään kaikki muuttujat tallessa
  $muut_siirrettavat = $nayta_kohdistetut."!¡!".$nayta_kaikki."!¡!".$toimittajaid;

  $rivinotunnus = $otunnus;
  $toimaika = date('Y')."-".date('m')."-".date('d');

  require 'syotarivi_ostotilaus.inc';
  require '../inc/footer.inc';
  exit;
}

//poistetaan rivi, tyhjennetään muuttujat, näytetään lista
if ($tee == 'TI' and isset($tyhjenna)) {

  $tila   = "";
  $tee   = "";

  for ($alepostfix = 1; $alepostfix <= 1; $alepostfix++) {
    unset(${'ale'.$alepostfix});
    unset(${'ale_array'.$alepostfix});
    unset(${'kayttajan_ale'.$alepostfix});
  }

  unset($alv);
  unset($alv_array);
  unset($hinta);
  unset($hinta_array);
  unset($kayttajan_alv);
  unset($kayttajan_hinta);
  unset($kayttajan_kpl);
  unset($kayttajan_netto);
  unset($kayttajan_var);
  unset($kerayspvm);
  unset($kommentti);
  unset($kpl);
  unset($kpl_array);
  unset($netto);
  unset($netto_array);
  unset($paikat);
  unset($paikka);
  unset($paikka_array);
  unset($perheid);
  unset($perheid2);
  unset($rivinumero);
  unset($rivitunnus);
  unset($toimaika);
  unset($tuotenimitys);
  unset($tuoteno);
  unset($var);
  unset($variaatio_tuoteno);
  unset($var_array);
}

//tarkastetaan tilausrivi
if ($tee == 'TI' or $tee == "Y") {
  // Parametreja joita tarkistarivi tarvitsee
  $laskurow["tila"]   = "O";
  $toim_tarkistus   = "EI";
  $prow["tuoteno"]   = $tuoteno;
  $prow["var"]    = $rivinvar;
  $kukarow["kesken"]  = $rivinotunnus;

  $kpl   = str_replace(',', '.', $kpl);
  $hinta   = str_replace(',', '.', $hinta);

  for ($alepostfix = 1; $alepostfix <= $yhtiorow["oston_alekentat"]; $alepostfix++) {
    ${'ale'.$alepostfix} = str_replace(',', '.', ${'ale'.$alepostfix});
  }

  if (checkdate($toimkka, $toimppa, $toimvva)) {
    $toimaika = $toimvva."-".$toimkka."-".$toimppa;
  }

  $query = "SELECT myyntihinta_maara
            FROM tuote
            WHERE yhtio  = '$kukarow[yhtio]'
            AND  tuoteno = '$prow[tuoteno]'";
  $result = pupe_query($query);
  $trow = mysql_fetch_assoc($result);

  if ($hinta == "") {
    $query = "SELECT *
              FROM tuotteen_toimittajat
              WHERE tuoteno    = '$prow[tuoteno]'
              and yhtio        = '$kukarow[yhtio]'
              and liitostunnus = '$laskurow[liitostunnus]'";
    $rarres1 = pupe_query($query);
    $hinrow1 = mysql_fetch_assoc($rarres1);

    $prow["hinta"] = $hinrow1["ostohinta"];
  }
  else {
    $prow["hinta"] = $hinta;
  }

  $prow["hinta"] = round($prow["hinta"] / $trow["myyntihinta_maara"], $yhtiorow["hintapyoristys"]);

  $multi = "";
  require "../inc/tuotehaku.inc";

  $prow["tuoteno"]      = $tuoteno;
  $oikeusostohintapaiv  = tarkista_oikeus("yllapito.php", "tuotteen_toimittajat", "check");

  require 'tarkistarivi_ostotilaus.inc';

  //näytetään virhe ja annetaan mahis korjata se
  if ((isset($varaosavirhe) and $varaosavirhe != '') or $tuoteno == "") {

    //rivien splittausvaihtoehtot näkyviin
    $automatiikka = 'ON';

    echo t("Muuta riviä").":<br>";
    require 'syotarivi_ostotilaus.inc';
    require '../inc/footer.inc';
    exit;
  }
}

//rivi on tarkistettu ja se lisataan tietokantaan
if ((!isset($varaosavirhe) or $varaosavirhe == '') and ($tee == "TI" or $tee == "Y") and $tuoteno != '') {

  $laskurow["tila"] = "O";
  $kukarow["kesken"] = $rivinotunnus;

  if (!is_array($tuoteno_array) and trim($tuoteno) != "") {
    $tuoteno_array[] = $tuoteno;
  }

  //Käyttäjän syöttämä hinta ja ale ja netto, pitää säilöä jotta tuotehaussakin voidaan syöttää nämä
  for ($alepostfix = 1; $alepostfix <= 1; $alepostfix++) {
    ${'kayttajan_ale'.$alepostfix} = ${'ale'.$alepostfix};
  }

  $kayttajan_hinta  = $hinta;
  $kayttajan_netto   = $netto;
  $kayttajan_var    = $var;
  $kayttajan_kpl    = $kpl;
  $kayttajan_alv    = $alv;

  foreach ($tuoteno_array as $tuoteno) {

    if ($kukarow['oletus_ostovarasto'] != 0) {
      $varasto = $kukarow["oletus_ostovarasto"];
    }
    elseif ($kukarow['oletus_varasto'] != 0) {
      $varasto = $kukarow["oletus_varasto"];
    }
    else {
      $varasto = $laskurow["varasto"];
    }

    if ($onkolaajattoimipaikat) {
      $_varastopaikat = hae_yhtion_toimipaikan_varastot($laskurow['yhtio_toimipaikka']);

      if (!empty($_varastopaikat)) {

        $_varastopaikka_loytyi = false;

        # Tarkistetaan onko jossakin toimipaikan varastossa tuotepaikkaa
        foreach ($_varastopaikat AS $_varastopaikka) {
          $query = "SELECT *
                    FROM tuotepaikat
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND tuoteno = '{$tuoteno}'
                    AND varasto = '{$_varastopaikka['tunnus']}'";
          $_chk_res = pupe_query($query);

          if (mysql_num_rows($_chk_res) != 0) {
            $_chk_row = mysql_fetch_assoc($_chk_res);
            $varasto = $_chk_row['varasto'];
            $_varastopaikka_loytyi = true;
            break;
          }
        }

        # Jos varastopaikkaa tuotteelle ei löytynyt mistään toimipaikan varastoista
        # Perustetaan tuotepaikka ensimmäiseen varastoon
        if (!$_varastopaikka_loytyi) {
          $_var_tunnus = $_varastopaikat[0]['tunnus'];
          $varasto = $_var_tunnus;
          lisaa_tuotepaikka($tuoteno, "", "", "", "", "Saapumisella", '', 0, 0, $_var_tunnus);
        }
      }
    }

    $query  = "SELECT *
               FROM tuote
               WHERE tuoteno = '$tuoteno'
               and yhtio     = '$kukarow[yhtio]'
               and ei_saldoa = ''";
    $result = pupe_query($query);

    if (mysql_num_rows($result) > 0) {
      //Tuote löytyi
      $trow = mysql_fetch_assoc($result);
    }
    else {
      //Tuotetta ei löydy, arvataan muutamia muuttujia
      $trow["alv"] = $laskurow["alv"];
    }

    if (checkdate($toimkka, $toimppa, $toimvva)) {
      $toimaika = $toimvva."-".$toimkka."-".$toimppa;
    }
    if (checkdate($kerayskka, $keraysppa, $keraysvva)) {
      $kerayspvm = $keraysvva."-".$kerayskka."-".$keraysppa;
    }
    if ($toimaika == "" or $toimaika == "0000-00-00") {
      $toimaika = $laskurow["toimaika"];
    }
    if ($kerayspvm == "" or $kerayspvm == "0000-00-00") {
      $kerayspvm = $laskurow["kerayspvm"];
    }

    //Tehdään muuttujaswitchit
    if (is_array($hinta_array)) {
      $hinta = $hinta_array[$tuoteno];
    }
    else {
      $hinta = $kayttajan_hinta;
    }

    for ($alepostfix = 1; $alepostfix <= $yhtiorow["oston_alekentat"]; $alepostfix++) {
      if (is_array(${'ale_array'.$alepostfix})) {
        ${'ale'.$alepostfix} = ${'ale_array'.$alepostfix}[$tuoteno];
      }
      else {
        ${'ale'.$alepostfix} = ${'kayttajan_ale'.$alepostfix};
      }
    }

    if (is_array($netto_array)) {
      $netto = $netto_array[$tuoteno];
    }
    else {
      $netto = $kayttajan_netto;
    }

    if (is_array($var_array)) {
      $var = $var_array[$tuoteno];
    }
    else {
      $var = $kayttajan_var;
    }

    if (is_array($kpl_array)) {
      $kpl = $kpl_array[$tuoteno];
    }
    else {
      $kpl = $kayttajan_kpl;
    }

    if (is_array($alv_array)) {
      $alv = $alv_array[$tuoteno];
    }
    else {
      $alv = $kayttajan_alv;
    }

    if ($kpl != 0) {
      require 'lisaarivi.inc';
    }

    $hinta   = '';
    $netto   = '';
    $var   = '';
    $kpl   = '';
    $alv   = '';
    $paikka  = '';

    for ($alepostfix = 1; $alepostfix <= $yhtiorow["oston_alekentat"]; $alepostfix++) {
      ${'ale'.$alepostfix} = '';
    }
  }

  if ($lisavarusteita == "ON" and $perheid2 > 0) {
    //Päivitetään isälle perheid jotta tiedetään, että lisävarusteet on nyt lisätty
    $query = "UPDATE tilausrivi set
              perheid2    = '$perheid2'
              where yhtio = '$kukarow[yhtio]'
              and tunnus  = '$perheid2'";
    $updres = pupe_query($query);
  }

  unset($tee);
  unset($tila);

  for ($alepostfix = 1; $alepostfix <= $yhtiorow["oston_alekentat"]; $alepostfix++) {
    unset(${'ale'.$alepostfix});
    unset(${'ale_array'.$alepostfix});
    unset(${'kayttajan_ale'.$alepostfix});
  }

  unset($alv);
  unset($alv_array);
  unset($hinta);
  unset($hinta_array);
  unset($kayttajan_alv);
  unset($kayttajan_hinta);
  unset($kayttajan_kpl);
  unset($kayttajan_netto);
  unset($kayttajan_var);
  unset($kerayspvm);
  unset($kommentti);
  unset($kpl);
  unset($kpl_array);
  unset($netto);
  unset($netto_array);
  unset($paikat);
  unset($paikka);
  unset($paikka_array);
  unset($perheid);
  unset($perheid2);
  unset($rivinumero);
  unset($rivitunnus);
  unset($toimaika);
  unset($tuotenimitys);
  unset($tuoteno);
  unset($var);
  unset($variaatio_tuoteno);
  unset($var_array);
}

//korjataan siirrettävät muuttujat taas talteen
if (isset($muut_siirrettavat)) {
  $muut = explode('!¡!', $muut_siirrettavat);

  $nayta_kohdistetut   = $muut[0];
  $nayta_kaikki     = $muut[1];
  $toimittajaid     = $muut[2];
}

//kohdistetaan rivejä laskulle
if ($tila == 'tee_siirto') {

  if ($ala_kohdista_mitaan != "") {
    unset($rivi_siirrettavat_tunnukset);
  }

  if ($kohdista_koko_sivu != "") {
    $rivi_siirrettavat_tunnukset = $rivi_kaikki_tunnukset;
  }

  //Tarksitetaan vielä ennen päivitystä, ettei saapumsita ole poistettu ja infotaan käyttäjää, jos näin on käynyt
  $query = "SELECT tila
            FROM lasku
            WHERE yhtio     = '{$kukarow[yhtio]}'
            AND tila        = 'K'
            AND alatila     = ''
            AND vanhatunnus = 0
            AND tunnus      = '$otunnus'";
  $res = pupe_query($query);

  if (mysql_num_rows($res) == 1) {
    //Siirretään rivit
    if (is_array($rivi_siirrettavat_tunnukset)) {

      $q1 = "LOCK TABLE lasku WRITE, tilausrivi WRITE, tilausrivin_lisatiedot WRITE, suuntalavat_saapuminen WRITE";
      $r = pupe_query($q1);

      $keikkakesken = 0;

      $lock_params = array(
        "locktime" => 0,
        "lockfile" => "$kukarow[yhtio]-keikka.lock",
        "return"   => TRUE
      );

      if (!pupesoft_flock($lock_params)) {
        list($keikkakesken, $_kuka, $_timestamp) = explode(";", file_get_contents("/tmp/$kukarow[yhtio]-keikka.lock"));
      }

      foreach ($rivi_siirrettavat_tunnukset as $valittu) {

        list($valittutunnus, $valittuuusiotunnus) = explode("###", $valittu);

        if ($valittuuusiotunnus != $keikkakesken) {
          // Katostaan, että keikka jolta rivi siirretään ei ole loppuunlaskettu
          $query  = "SELECT tunnus
                     FROM lasku USE INDEX (PRIMARY)
                     WHERE tunnus    = '$valittuuusiotunnus'
                     and yhtio       = '$kukarow[yhtio]'
                     and tila        = 'K'
                     and vanhatunnus = 0
                     and alatila     = ''";
          $result = pupe_query($query);

          if (mysql_num_rows($result) == 1) {

            $kpl_lisa = "";

            // Ollaanko syötetty siirrettävä määrä?
            if (isset($siirrettavat_kpl[$valittutunnus])) {
              $query = "SELECT *
                        FROM tilausrivi
                        WHERE tunnus = '$valittutunnus'
                        and yhtio    = '$kukarow[yhtio]'";
              $rivires = pupe_query($query);
              $rivitiedot = mysql_fetch_assoc($rivires);

              $siirrettavat_kpl[$valittutunnus] = (float) str_replace(',', '.', $siirrettavat_kpl[$valittutunnus]);

              // Jos luku ei ole ok niin unsetataan se ja siirretään koko rivi
              if ($siirrettavat_kpl[$valittutunnus] > 0 and $siirrettavat_kpl[$valittutunnus] < $rivitiedot["kpl"]) {

                $jaljelle_jaa = $rivitiedot["kpl"] - $siirrettavat_kpl[$valittutunnus];
                $kpl_lisa = ", tilkpl='".$siirrettavat_kpl[$valittutunnus]."', kpl='".$siirrettavat_kpl[$valittutunnus]."' ";

                // Splitataan rivi, uusi rivi jää vanhalle keikalle
                $rfields = "yhtio";
                $rvalues = "'$kukarow[yhtio]'";

                for ($i=1; $i < mysql_num_fields($rivires)-1; $i++) {

                  $fieldname = mysql_field_name($rivires, $i);

                  $rfields .= ", $fieldname";

                  switch ($fieldname) {
                  case 'laatija':
                    $rvalues .= ", '$kukarow[kuka]'";
                    break;
                  case 'laadittu':
                    $rvalues .= ", now()";
                    break;
                  case 'tilkpl':
                  case 'kpl':
                    $rvalues .= ", '$jaljelle_jaa'";
                    break;
                  case 'kate_korjattu':
                    $rvalues .= ", NULL";
                    break;
                  default:
                    $rvalues .= ", '".$rivitiedot[$fieldname]."'";
                  }
                }

                $kysely = "INSERT into tilausrivi ($rfields) VALUES ($rvalues)";
                $insres = pupe_query($kysely);
                $insid  = mysql_insert_id();

                $query = "SELECT *
                          FROM tilausrivin_lisatiedot
                          WHERE tilausrivitunnus = '$valittutunnus'
                          and yhtio              = '$kukarow[yhtio]'";
                $rivires = pupe_query($query);

                if (mysql_num_rows($rivires) > 0) {
                  $rivirow = mysql_fetch_assoc($rivires);

                  // Splitataan rivin lisätiedot, uusi rivi jää vanhalle keikalle
                  $rfields = "yhtio";
                  $rvalues = "'$kukarow[yhtio]'";

                  for ($i=1; $i < mysql_num_fields($rivires)-1; $i++) {

                    $fieldname = mysql_field_name($rivires, $i);

                    $rfields .= ", $fieldname";

                    switch ($fieldname) {
                    case 'laatija':
                      $rvalues .= ", '$kukarow[kuka]'";
                      break;
                    case 'luontiaika':
                      $rvalues .= ", now()";
                      break;
                    case 'tilausrivitunnus':
                      $rvalues .= ", '$insid'";
                      break;
                    default:
                      $rvalues .= ", '".$rivirow[$fieldname]."'";
                    }
                  }

                  $kysely = "INSERT into tilausrivin_lisatiedot ($rfields) VALUES ($rvalues)";
                  $insres = pupe_query($kysely);
                }
              }
            }

            // lisätään linkki suuntalavasta saapumiselle, johon rivi(t) siirretään
            //tarkistetaan kuitenkin eka, että suuntalava on olemassa (!=0)
            if ($rivitiedot["suuntalava"] != 0) {
              //tarkistetaan eka onko linkki jo olemassa, jos on niin sitä ei tarvi luoda uudestaan
              $query2 = "SELECT tunnus
                         FROM suuntalavat_saapuminen
                         WHERE yhtio    = '$kukarow[yhtio]'
                         AND saapuminen = '$otunnus'
                         AND suuntalava = '$rivitiedot[suuntalava]'";
              $olemassa = pupe_query($query2);
              if (mysql_fetch_assoc($olemassa) == 0) {
                $query = "INSERT into suuntalavat_saapuminen
                          (yhtio, suuntalava, saapuminen, laatija, luontiaika, muutospvm, muuttaja)
                          VALUES   ('$kukarow[yhtio]', '$rivitiedot[suuntalava]', '$otunnus', '$kukarow[kuka]', now(), now(), '$kukarow[kuka]')";
                $lisays = pupe_query($query);
              }

            }

            // Siiretään rivi tälle keikalle
            $query = "UPDATE tilausrivi USE INDEX (uusiotunnus_index)
                      SET uusiotunnus     = '$otunnus',
                      erikoisale_saapuminen = '$laskurow[erikoisale_saapuminen]',
                      kerattyaika           = now(),
                      keratty               = '$kukarow[kuka]'
                      $kpl_lisa
                      WHERE yhtio           = '$kukarow[yhtio]'
                      and uusiotunnus       = '$valittuuusiotunnus'
                      and (tunnus = '$valittutunnus' or perheid2 = '$valittutunnus')";
            $result = pupe_query($query);

            // Nollataan kohdistus keikalta jolta rivi siirretään
            $query = "UPDATE lasku USE INDEX (PRIMARY)
                      SET kohdistettu = ''
                      WHERE yhtio = '$kukarow[yhtio]'
                      and tunnus  = '$valittuuusiotunnus'
                      and tila    = 'K'";
            $result = pupe_query($query);
          }
          else {
            echo "<font class='error'>".t("VIRHE: Saapuminen jolta rivi siirretään ei löydy")."</font><br>!";
          }
        }
        else {
          echo "<font class='error'>".t("VIRHE: Saapuminen jolta rivi siirretään on lukittu")."</font><br>!";
        }
      }

      $q6 = "UNLOCK TABLES";
      $r = pupe_query($q6);
    }
  } else {
    echo "<font class='error'>".t("VIRHE: Saapumiselle ei voi liittää rivejä, koska se on poistettu")."!</font><br>";
  }
  $tila = '';
}

// jos loppusumma on isompi kuin tietokannassa oleva tietuen koko (10 numeroa + 2 desimaalia), niin herjataan
if ($tila == 'tee_kohdistus' and is_array($rivi_hinta) !== FALSE and is_array($rivi_kpl) !== FALSE) {

  foreach ($rivi_hinta as $tun => $hinta_chk) {
    $loppusumma_check = $rivi_kpl[$tun] * $hinta_chk;

    if (count($rivi_valitut_tunnukset) > 0 or count($rivi_kaikki_tunnukset) > 0) {

      if ($kohdista_koko_sivu != "") {
        $rivi_valitut_tunnukset = $rivi_kaikki_tunnukset;
      }
      elseif ($ala_kohdista_mitaan != "") {
        unset($rivi_valitut_tunnukset);
      }

      if (isset($rivi_valitut_tunnukset) and count($rivi_valitut_tunnukset) > 0) {

        foreach ($rivi_valitut_tunnukset as $valittu) {

          list($valittutunnus, $valittuotunnus) = explode("###", $valittu);

          if ($valittutunnus == $tun) {
            if (abs($loppusumma_check) > 9999999999.99) {
              echo "<font class='error'>", t("VIRHE: liian iso loppusumma"), "!</font><br/>";
              $tila = '';
              break;
            }
          }
        }
      }
    }
  }
}

//kohdistetaan rivejä laskulle
if ($tila == 'tee_kohdistus') {

  if ($ala_kohdista_mitaan != "") {
    unset($rivi_valitut_tunnukset);
  }

  if ($kohdista_koko_sivu != "") {
    $rivi_valitut_tunnukset = $rivi_kaikki_tunnukset;
  }

  //Tehdään uusia lisävarusterivejä
  if (is_array($lisavarusterivi_kpl)) {

    foreach ($lisavarusterivi_kpl as $tunnus => $kpl) {
      $kpl    = str_replace(",", ".", $kpl);
      $perheid2 = substr($tunnus, 0, strpos($tunnus, '##'));
      $tuoteno  = substr($tunnus,  strpos($tunnus, '##')+2, strpos($tunnus, '#*#')-strpos($tunnus, '##')-2);
      $hinta     = str_replace(",", ".", $lisavarusterivi_hinta[$tunnus]);
      $potunnus = substr($tunnus, strpos($tunnus, '#*#')+3);

      if (abs($kpl) > 0 or strtoupper($kpl) == "DEL") {
        if (abs($kpl) > 0) {
          $query  = "SELECT *
                     FROM tuote
                     LEFT JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio=tuote.yhtio and tuotteen_toimittajat.tuoteno=tuote.tuoteno and tuotteen_toimittajat.liitostunnus='$toimittajaid'
                     where tuote.yhtio    = '$kukarow[yhtio]'
                     and tuote.tuoteno    = '$tuoteno'
                      and tuote.ei_saldoa = ''";
          $lisaresult = pupe_query($query);
          $lisarow = mysql_fetch_assoc($lisaresult);

          if ($hinta == "") {
            $hinta = $lisarow["ostohinta"];
          }

          $query = "INSERT into tilausrivi
                    (perheid2, hinta, nimitys, tuoteno, tilkpl, varattu, otunnus, yhtio, tyyppi, laatija, laadittu) values
                    ('$perheid2','$hinta','$lisarow[nimitys]','$tuoteno', '$kpl', '$kpl', '$potunnus', '$kukarow[yhtio]', 'O', '$kukarow[kuka]', now())";
          $updre = pupe_query($query);
        }
      }

      unset($lisaa_lisavarusteita);
    }

    $query = "UPDATE tilausrivi SET perheid2='$perheid2' where yhtio='$kukarow[yhtio]' and tunnus='$perheid2' and tyyppi='O'";
    $result = pupe_query($query);
  }

  $tila = '';

  // katotaan ollaanko muutettunimityksiä ja päivitetään ne
  if (is_array($rivi_nimitys)) {
    foreach ($rivi_nimitys as $tunnus => $nimitys) {
      $query  = "UPDATE tilausrivi set nimitys='$nimitys' where yhtio='$kukarow[yhtio]' and tunnus='$tunnus'";
      $result = pupe_query($query);
    }
  }

  // katotaan ollaanko muutettu hintaa tai kappaleita ja päivitetään ne
  if (is_array($rivi_hinta)) {
    foreach ($rivi_hinta as $tunnus => $hinta) {
      $hinta  = str_replace(",", ".", $hinta);
      $query  = "UPDATE tilausrivi set hinta='$hinta' where yhtio='$kukarow[yhtio]' and tunnus='$tunnus' and hinta<>'$hinta'";
      $result = pupe_query($query);
    }
  }

  if (is_array($rivi_kuluhinta)) {
    // katotaan ollaanko muutettu kuluhintaa tai kappaleita ja päivitetään ne
    foreach ($rivi_kuluhinta as $tunnus => $kulu) {
      $kulu   = str_replace(",", ".", $kulu);
      $query  = "UPDATE tilausrivi set kate='$kulu' where yhtio='$kukarow[yhtio]' and tunnus='$tunnus' and kate<>'$kulu'";
      $result = pupe_query($query);
    }
  }
}

//näytetään rivilista ja ruksataan kohdistettavia rivejä
if ($tila == '') {

  list(
    $hintarow,
    $valitutrivit,
    $alvit,
    $alvti
  ) = paivita_saapumisen_summa($laskurow, $kulurow, $toimittajaid, $otunnus);

  if ($excel_array_check) {
    $excel_array['hintarow'] = $hintarow;
  }

  //Jos käyttäjä on syöttänyt alvillisen hinnan niin lisätään alvi
  if ((int) $laskurow["rahti_etu_alv"] != 0) {
    $laskurow["rahti_etu"] = round($laskurow["rahti_etu"]*(1+($laskurow["rahti_etu_alv"]/100)), 6);
  }

  // Jos käyttäjä on syöttänyt alvillisen pyöristyksen niin lisätään alvi
  if ((int) $laskurow["pyoristys_erot_alv"] != 0) {
    $laskurow["pyoristys_erot"] = round($laskurow["pyoristys_erot"] * (1 + ($laskurow["pyoristys_erot_alv"] / 100)), 6);
  }

  $valittusumma       = sprintf("%.2f", round($hintarow["hinta"], 2));
  $laskunsumma        = sprintf("%.2f", round($kulurow["summa"] - $laskurow["rahti_etu"] - $laskurow['pyoristys_erot'] - $valittusumma - $kulurow['osto_kulut'], 2));
  $checksumma         = $kulurow["summa"] - $laskurow["rahti_etu"] - $laskurow['pyoristys_erot'] - $hintarow["hinta"] - $kulurow['osto_kulut'];
  $kohdistettava_summa   = sprintf("%.2f", round($kulurow["summa"] - $laskurow["rahti_etu"] - $laskurow['pyoristys_erot'] - $kulurow['osto_kulut'], 2));

  if ($laskunsumma != 0.00 and $checksumma < 0.01 and $checksumma > -0.01 and $kohdistettava_summa == $valittusumma) {
    $laskunsumma = 0.00;
  }

  if ($excel_array_check) {
    $excel_array['valittusumma'] = $valittusumma;
    $excel_array['laskunsumma'] = $laskunsumma;
    $excel_array['kohdistettava_summa'] = $kohdistettava_summa;
  }

  $jarjestys = " perheid2, rivitunnus ";

  $limit           = "";
  $lisa2           = "";
  $uusilisa        = "";
  $tilaajanrivinro = "";

  $unionwhere1 = " and lasku.tila = 'O' and alatila in ('A','X') ";
  $unionwhere2 = " and lasku.tila = 'K' ";
  $tilriindex  = "yhtio_otunnus";

  if (isset($nayta_siirrettavat) and $nayta_siirrettavat == "YES") {
    $uusilisa   = "uusi";
    $tilriindex = "uusiotunnus_index";

    $unionwhere1 = " and lasku.tila = 'K' and lasku.alatila = '' ";
    $unionwhere2 = "";

    $lisa2 = " and tilausrivi.uusiotunnus not in ({$otunnus},0) ";

    $h_haut = FALSE;

    if (!empty($h_siirra_saapuminen)) {
      $unionwhere1 .= " and lasku.laskunro = '{$h_siirra_saapuminen}' ";
      $h_haut = TRUE;
    }
    if (!empty($h_siirra_tilaus)) {
      $lisa2 .= " and tilausrivi.otunnus = '{$h_siirra_tilaus}' ";
      $h_haut = TRUE;
    }
    if (!empty($h_siirra_tuoteno)) {
      $lisa2 .= " and tilausrivi.tuoteno like '{$h_siirra_tuoteno}%' ";
      $h_haut = TRUE;
    }
    if (!empty($h_siirra_toimtuoteno)) {
      $unionwhere1 .= " and tuotteen_toimittajat.toim_tuoteno like '{$h_siirra_toimtuoteno}%' ";
      $h_haut = TRUE;
    }

    if (!$h_haut) {
      $unionwhere1 = " and lasku.tunnus -1 ";
      $lisa2 = " and tilausrivi.otunnus = -1 ";
    }
  }
  elseif ($nayta_kohdistetut == "jees" or $nayta_kohdistetut == "lasku" or $nayta_kohdistetut == 'hinta') {
    $lisa2 = " and tilausrivi.uusiotunnus in ($otunnus) ";
  }
  elseif ($nayta_kohdistetut == "eitod") {
    $lisa2 = " and tilausrivi.uusiotunnus = 0 ";
  }
  else {
    $lisa2 = " and tilausrivi.uusiotunnus in ($otunnus,0) ";
  }

  $suuntalavat_selectlisa = $yhtiorow['suuntalavat'] == 'S' ? ", tilausrivi.suuntalava " : '';

  $onkolaajattoimipaikat = ($yhtiorow['toimipaikkakasittely'] == "L" and $toimipaikat_res = hae_yhtion_toimipaikat($kukarow['yhtio']) and mysql_num_rows($toimipaikat_res) > 0) ? TRUE : FALSE;

  if ($onkolaajattoimipaikat and isset($toimipaikka)) {
    if ($toimipaikka == 'kaikki') {
      $toimipaikkalisa = $toimipaikkalisa2 = "";
    }
    else {
      $toimipaikka = (int) $toimipaikka;

      if (isset($nayta_siirrettavat) and $nayta_siirrettavat == "YES") {
        $toimipaikkalisa = "AND lasku.yhtio_toimipaikka IN ({$toimipaikka})";
        $toimipaikkalisa2 = "";

      }
      else {
        $toimipaikkalisa = "AND lasku.vanhatunnus IN ({$toimipaikka})";
        $toimipaikkalisa2 = "AND lasku.yhtio_toimipaikka IN ({$toimipaikka})";
      }
    }
  }
  elseif ($onkolaajattoimipaikat) {
    if (isset($nayta_siirrettavat) and $nayta_siirrettavat == "YES") {
      $toimipaikkalisa = "AND lasku.yhtio_toimipaikka IN ({$laskurow['yhtio_toimipaikka']})";
      $toimipaikkalisa2 = "";

    }
    else {
      $toimipaikkalisa = "AND lasku.vanhatunnus IN ({$laskurow['yhtio_toimipaikka']})";
      $toimipaikkalisa2 = "AND lasku.yhtio_toimipaikka IN ({$laskurow['yhtio_toimipaikka']})";
    }
  }
  else {
    $toimipaikkalisa = $toimipaikkalisa2 = "";
  }

  $query_ale_lisa = generoi_alekentta('O');

  $query = "SELECT
            $alvti alv,
            concat_ws('/', tilkpl, round(tilkpl * if (tuotteen_toimittajat.tuotekerroin<=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin),4)) 'tilattu',
            GROUP_CONCAT(DISTINCT tuotteen_toimittajat.toim_tuoteno ORDER BY toim_tuoteno SEPARATOR ',') toim_tuoteno,
            GROUP_CONCAT(DISTINCT tuotteen_toimittajat.viivakoodi ORDER BY toim_tuoteno SEPARATOR ',') toim_viivakoodi,
            if(tuotteen_toimittajat.toim_yksikko!='', tuotteen_toimittajat.toim_yksikko, tilausrivi.yksikko) toimyksikko,
            lasku.laskunro keikka,
            round((tilausrivi.varattu+tilausrivi.kpl) * (tilausrivi.hinta * {$query_ale_lisa}) * if (tuotteen_toimittajat.tuotekerroin<=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin),'$yhtiorow[hintapyoristys]') rivihinta,
            round((tilausrivi.varattu+tilausrivi.kpl)*if (tuotteen_toimittajat.tuotekerroin<=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin),4) ulkkpl,
            round((tilausrivi.varattu+tilausrivi.kpl) * if (tuotteen_toimittajat.tuotekerroin<=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin) * tilausrivi.hinta * {$query_ale_lisa} *
              (1+(if ((SELECT max(kaytetty) kaytetty
                  FROM sarjanumeroseuranta
                  WHERE sarjanumeroseuranta.yhtio=tilausrivi.yhtio
                  and sarjanumeroseuranta.tuoteno=tilausrivi.tuoteno
                  and ((tilausrivi.varattu+tilausrivi.kpl < 0 and sarjanumeroseuranta.myyntirivitunnus=tilausrivi.tunnus) or (tilausrivi.varattu+tilausrivi.kpl > 0 and sarjanumeroseuranta.ostorivitunnus=tilausrivi.tunnus))) = 'K', 0, $alvit)/100)) ,'$yhtiorow[hintapyoristys]') alerivihinta,
            tilausrivi.ale1,
            tilausrivi.ale2,
            tilausrivi.ale3,
            tilausrivi.erikoisale,
            tilausrivi.hinta,
            tilausrivi.hinta_alkuperainen,
            tilausrivi.var2,
            tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllytaso, tilausrivi.hyllyvali,
            tilausrivi.kate kuluhinta,
            tilausrivi.kommentti kommentti,
            tilausrivi.kpl varastossa_kpl,
            tilausrivi.varastoon,
            tilausrivi.nimitys,
            tilausrivi.otunnus,
            tilausrivi.perheid,
            tilausrivi.perheid2,
            tilausrivi.toimaika toimaika,
            tilausrivi.tunnus rivitunnus,
            tilausrivi.tuoteno,
            tilausrivi.uusiotunnus,
            tilausrivi.varasto,
            tilausrivi.varattu+tilausrivi.kpl siskpl,
            tilausrivi.varattu,
            tilausrivi.yksikko tilyksikko,
            tilausrivin_lisatiedot.suoratoimitettuaika,
            tuote.eankoodi,
            tuote.kehahin keskihinta,
            tuote.keraysvyohyke,
            tuote.myyntihinta,
            tuotteen_toimittajat.ostohinta,
            tuotteen_toimittajat.valuutta,
            tuotteen_toimittajat.tunnus tuotteen_toimittajat_tunnus,
            lasku.comments,
            lasku.vanhatunnus as toimipaikka
            $suuntalavat_selectlisa
            FROM lasku
            JOIN tilausrivi use index ($tilriindex) ON (tilausrivi.yhtio=lasku.yhtio and tilausrivi.{$uusilisa}otunnus=lasku.tunnus and tilausrivi.tyyppi='O' AND tilausrivi.kpl+tilausrivi.varattu != 0 $tilaajanrivinro $lisa2)
            LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus)
            JOIN tuote use index (tuoteno_index) ON (tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno  and tuote.ei_saldoa = '')
            LEFT JOIN tuotteen_toimittajat use index (yhtio_tuoteno) ON (tuotteen_toimittajat.yhtio=tuote.yhtio and tuotteen_toimittajat.tuoteno=tuote.tuoteno and tuotteen_toimittajat.liitostunnus=lasku.liitostunnus)
            LEFT JOIN tuotteen_alv ON (tuotteen_alv.yhtio = tilausrivi.yhtio AND tuotteen_alv.tuoteno = tilausrivi.tuoteno AND tuotteen_alv.maa = '$laskurow[maa]')
            WHERE lasku.yhtio      = '$kukarow[yhtio]'
            and lasku.liitostunnus = '$laskurow[liitostunnus]'";

  // Tehdään unionilla niin saadaan sekä keikat että osotilaukset nopsasti haettua suoraan indeksistä
  if ($unionwhere2 != "") {
    $query = "($query {$toimipaikkalisa} $unionwhere1 GROUP BY tilausrivi.tunnus) UNION ($query {$toimipaikkalisa2} $unionwhere2 GROUP BY tilausrivi.tunnus) ORDER BY $jarjestys";
  }
  else {
    $query = "$query {$toimipaikkalisa} $unionwhere1 GROUP BY tilausrivi.tunnus ORDER BY $jarjestys";
  }

  $result = pupe_query($query);

  echo "<table><tr><td class='back'>";

  // aloitellaan laskun otsikko taulu
  $chk1 = $chk2 = $chk3 = $chk4 = "";

  if ($nayta_kohdistetut == "jees") {
    $chk1 = "checked";
  }

  if ($nayta_kohdistetut == "eitod") {
    $chk2 = "checked";
  }

  if ($nayta_kohdistetut == "lasku" or $nayta_kohdistetut == 'hinta') {
    $chk3 = "checked";
  }

  if ($nayta_kohdistetut == "") {
    $chk4 = "checked";
  }

  if ($laskunsumma == 0 and $valitutrivit > 0) { // jos ollaan saatu kohdistus nollaan ja ollaan valittu jotain
    $ok = "<font class='ok'>";
    $okend = "</font>";
  }
  else {
    $ok = "";
    $okend = "";
  }

  // jos kululaskua ei ole vielä liitetty, oletetaan että käytetään toimittajan oletus valuuttaa
  if ($kulurow["valkoodi"] == "") $kulurow["valkoodi"] = $laskurow["valkoodi"];

  $query  = "SELECT sum(kpl+varattu) kappaleet, count(*) rivit from tilausrivi where yhtio='$kukarow[yhtio]' and uusiotunnus='$laskurow[tunnus]' and tyyppi='O'";
  $kappaleetres = pupe_query($query);
  $kappaleetrow = mysql_fetch_assoc($kappaleetres);

  if ($excel_array_check) {
    $excel_array['rivit'] = $kappaleetrow['rivit'];
    $excel_array['kappaleet'] = $kappaleetrow['kappaleet'];
  }

  //näytetään mitkä näytetään
  echo "<form name='formi1' id='formi1' method='post'>";
  echo "<input type='hidden' name='toiminto' value='kohdista'>";
  echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
  echo "<input type='hidden' name='otunnus' value='$otunnus'>";
  echo "<input type='hidden' name='tee' value='K'>";

  echo "<table>
  <tr>
    <th>".t("Saapuminen").":</th><td>$laskurow[laskunro]</td>
    <th>".t("Laskun summa").":</th><td style='text-align:right'>$kulurow[summa] $kulurow[valkoodi]</td>
    <th>".t("Perusnäkymä").":</th><td><INPUT type='radio' $chk4 name='nayta_kohdistetut' value='' onclick='submit()'></td>
  </tr>
  <tr>
    <th>".t("Toimittaja").":</th><td>$laskurow[nimi]</td>
    <th>".t("Laskun kulut").":</th><td style='text-align:right'><span id='laskun_kulut'>".sprintf("%.2f", $kulurow['osto_kulut'])."</span> $kulurow[valkoodi]</td>
    <th>".t("Näytä kaikki kohdistetut").":</th><td><INPUT type='radio' $chk1 name='nayta_kohdistetut' value='jees' onclick='submit()'></td>
  </tr>
  <tr>
    <th>".t("Näytä lasku").":</th><td>";


  js_popup();

  $query = "SELECT l2.tunnus
            FROM lasku l1
            JOIN lasku l2 ON l1.yhtio=l2.yhtio and l1.vanhatunnus=l2.tunnus
            WHERE l1.yhtio     = '$kukarow[yhtio]'
            and l1.tila        = 'K'
            and l1.laskunro    = '$laskurow[laskunro]'
            and l1.vanhatunnus > 0";
  $muutkeikres = pupe_query($query);

  while ($muutkeikrow = mysql_fetch_assoc($muutkeikres)) {
    echo ebid($muutkeikrow['tunnus']);

    if ($excel_array_check) {
      array_push($excel_array['liitetyt_laskut'], $muutkeikrow['tunnus']);
    }
  }

  if ($toimirow['asn_sanomat'] == '') {
    $nayta_kohdistetut_otsikko = t("Näytä Vain Laskun hintaerot");
    $nayta_kohdistetut_value = 'hinta';
  }
  else {
    $nayta_kohdistetut_otsikko = t("Näytä vain sähköisen laskun erot");
    $nayta_kohdistetut_value = 'lasku';
  }

  echo "</td>
      <th>".t("Eturahti").":</th><td style='text-align:right'>".sprintf("%.2f", $laskurow["rahti_etu"])." $kulurow[valkoodi]</td>
      <th>".t("Näytä vain kohdistamattomat").":</th><td><INPUT type='radio' $chk2 name='nayta_kohdistetut' value='eitod' onclick='submit()'></td>
      </tr>
      <tr>
      <th>".t("Saapumisen erikoisale").":</th><td><span id='keikan_erikoisale'>".($laskurow["erikoisale_saapuminen"]*1)."</span>%</td>
      <th>".t("Pyöristyserot").":</th><td style='text-align:right'>".sprintf("%.2f", $laskurow["pyoristys_erot"])." $kulurow[valkoodi]</td>
      <th>{$nayta_kohdistetut_otsikko}</th><td><input type='radio' {$chk3} name='nayta_kohdistetut' value='{$nayta_kohdistetut_value}' onclick='submit()' /></td>
      </tr>";

  echo "<tr>
      <th></th><td></td>
      <th>".t("Kohdistettava summa")."</th><td style='text-align:right'><span id='kohdistettava_summa'>$kohdistettava_summa</span> $kulurow[valkoodi]</td>";

  echo "<th>", t("Näytä toimipaikan rivejä"), "</th>";
  echo "<td>";

  if ($onkolaajattoimipaikat) {
    $sel = (isset($toimipaikka) and is_numeric($toimipaikka) and $toimipaikka == 0) ? "selected" : "";

    echo "<select name='toimipaikka' onchange='submit();'>";
    echo "<option value='kaikki'>", t("Kaikki toimipaikat"), "</option>";
    echo "<option value='0' {$sel}>", t("Ei toimipaikkaa"), "</option>";

    while ($toimipaikat_row = mysql_fetch_assoc($toimipaikat_res)) {

      $sel = '';

      if (isset($toimipaikka)) {
        if ($toimipaikka == $toimipaikat_row['tunnus']) {
          $sel = ' selected';
          $toimipaikka = $toimipaikat_row['tunnus'];
        }
      }
      else {
        if ($laskurow['yhtio_toimipaikka'] == $toimipaikat_row['tunnus']) {
          $sel = ' selected';
          $toimipaikka = $toimipaikat_row['tunnus'];
        }
      }

      echo "<option value='{$toimipaikat_row['tunnus']}'{$sel}>{$toimipaikat_row['nimi']}</option>";
    }

    echo "</select>";
  }

  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th></th><td></td>";
  echo "<th>".t("Kohdistettu summa").":</th><td id='kohdistettu_summa_td' style='text-align:right'>$ok <span id='kohdistettu_summa'>$valittusumma</span> $kulurow[valkoodi]$okend</td>";
  echo "<th>".t("Rivejä").":</th><td style='text-align:right'><span id='kohdistettu_rivit'>$kappaleetrow[rivit]</span></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th></th><td></td>";
  echo "<th>".t("Jäljellä").":</th><td style='text-align:right'>$ok $laskunsumma $kulurow[valkoodi]$okend</td>";
  echo "<th>".t("Kappaleita").":</th><td style='text-align:right'><span id='kohdistettu_kpl'>".(float) $kappaleetrow['kappaleet']."</span></td>";
  echo "</tr>";

  echo "</table>";
  echo "</form>";

  $toimipaikka = isset($toimipaikka) ? $toimipaikka : 0;

  if (tarkista_oikeus('messenger.php')) {
    echo "<br />";
    echo "<form method='post' name='messenger_form'>";
    echo "<table>";
    echo "<input type='hidden' name='toimipaikka' value='{$toimipaikka}' />";
    echo "<input type='hidden' name='messenger' value='X' />";
    echo "<input type='hidden' name='status' value='X' />";
    echo "<input type='hidden' name='toiminto' value='kohdista' />";
    echo "<input type='hidden' name='toimittajaid' value='{$toimittajaid}' />";
    echo "<input type='hidden' name='otunnus' value='{$otunnus}' />";
    echo "<input type='hidden' name='tee' value='K' />";
    echo "<input type='hidden' name='nayta_kohdistetut' value='{$nayta_kohdistetut}' />";
    echo "<input type='hidden' name='nayta_kaikki' value='{$nayta_kaikki}' />";

    if ($kulurow['laskut'] != "") {
      $query = "SELECT group_concat(distinct laskunro) laskunro
                FROM lasku
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  IN ({$kulurow['laskut']})";
      $chk_lasku_res = pupe_query($query);
      $chk_lasku_row = mysql_fetch_assoc($chk_lasku_res);

      $message_postfix =  "{$laskurow['nimi']}, ".t("lasku")." {$chk_lasku_row['laskunro']}, ".t("saapuminen")." {$laskurow['laskunro']}";
    }
    else {
      $message_postfix =  "{$laskurow['nimi']}, ".t("saapuminen")." {$laskurow['laskunro']}";
    }

    echo "<input type='hidden' name='message_postfix' value='{$message_postfix}' />";

    $query = "SELECT kuka.nimi, kuka.kuka
              FROM kuka
              WHERE kuka.yhtio     = '{$kukarow['yhtio']}'
              AND kuka.aktiivinen  = 1
              AND kuka.extranet    = ''
              AND kuka.nimi       != ''
              ORDER BY kuka.nimi, kuka.kuka";
    $kuka_result = pupe_query($query);

    echo "<tr><th>", t("Lähetä viesti käyttäjälle"), ": <select name='vastaanottaja'>";

    while ($userrow = mysql_fetch_assoc($kuka_result)) {
      echo "<option value='{$userrow['kuka']}'>{$userrow['nimi']}</option>";
    }

    echo "</select></th>";
    echo "<td><textarea rows='1' cols='50' name='message'>";
    echo "</textarea>";
    echo "</td>";
    echo "<td class='back'><input type='submit' name='submit' value='", t("Lähetä"), "' /></td>";
    echo "</tr>";
    echo "</table>";
    echo "</form><br />";
  }

  // otsikko valmis
  if (!isset($nayta_siirrettavat)) {
    echo "<table>";
    echo "<tr>";
    echo "<td class='back'>";
    echo "<input type='button' name='submit_tallenna' id='submit_tallenna' value='".t("Tallenna sivun muutokset")."'>";
    echo "<input type='button' name='submit_kohd_kaikki' id='submit_kohd_kaikki' value='".t("Kohdista kaikki sivun rivit")."'>";
    echo "<input type='button' name='submit_kohd_eimit' id='submit_kohd_eimit' value='".t("Poista kaikki sivun kohdistukset")."'>";

    echo "<form method='post'>";
    echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
    echo "<input type='hidden' name='toiminto' value='kohdista'>";
    echo "<input type='hidden' name='tila' value='uusirivi'>";
    echo "<input type='hidden' name='tee' value='K'>";
    echo "<input type='hidden' name='nayta_kohdistetut' value='$nayta_kohdistetut'>";
    echo "<input type='hidden' name='nayta_kaikki' value='$nayta_kaikki'>";
    echo "<input type='hidden' name='otunnus' value='$otunnus'>";
    echo "<input type='hidden' name='toimipaikka' value='{$toimipaikka}'>";
    echo "<input type='submit' value='".t("Tee uusi rivi")."'></form>";

    echo "<form name='formi' method='post'>";
    echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
    echo "<input type='hidden' name='toiminto' value='kohdista'>";
    echo "<input type='hidden' name='otunnus' value='$otunnus'>";
    echo "<input type='hidden' name='tee' value='$tee'>";
    echo "<input type='hidden' name='tila' value='FAILISTA'>";
    echo "<input type='submit' value='".t("Kohdista tiedostosta")."'>";
    echo "</form>";

    echo "<form method='post'>";
    echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
    echo "<input type='hidden' name='toiminto' value='kohdista'>";
    echo "<input type='hidden' name='tila' value=''>";
    echo "<input type='hidden' name='nayta_siirrettavat' value='YES'>";
    echo "<input type='hidden' name='tee' value='$tee'>";
    echo "<input type='hidden' name='nayta_kohdistetut' value='$nayta_kohdistetut'>";
    echo "<input type='hidden' name='nayta_kaikki' value='$nayta_kaikki'>";
    echo "<input type='hidden' name='otunnus' value='$otunnus'>";
    echo "<input type='hidden' name='toimipaikka' value='{$toimipaikka}'>";
    echo "<input type='submit' value='".t("Siirrä rivejä toiselta saapumiselta")."'></form>";

    echo "<form method='post'>";
    echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
    echo "<input type='hidden' name='toiminto' value='kohdista'>";
    echo "<input type='hidden' name='laskunsumma' value='$laskunsumma'>";
    echo "<input type='hidden' name='tee' value='K'>";
    echo "<input type='hidden' name='tila' value='valmis'>";
    echo "<input type='hidden' name='otunnus' value='$otunnus'>";
    echo "<input type='hidden' name='toimipaikka' value='{$toimipaikka}'>";
    echo "<input type='submit' value='".t("Kohdistus valmis")."'></form>";
    echo "</td></tr></table>";
  }

  if (isset($nayta_siirrettavat) and $nayta_siirrettavat == "YES") {
    echo "<form method='post'>";
    echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
    echo "<input type='hidden' name='toiminto' value='kohdista'>";
    echo "<input type='hidden' name='nayta_siirrettavat' value='YES'>";
    echo "<input type='hidden' name='tee' value='K'>";
    echo "<input type='hidden' name='nayta_kohdistetut' value='$nayta_kohdistetut'>";
    echo "<input type='hidden' name='nayta_kaikki' value='$nayta_kaikki'>";
    echo "<input type='hidden' name='otunnus' value='$otunnus'>";
    echo "<input type='hidden' name='toimipaikka' value='{$toimipaikka}'>";
    echo "Anna hakuehto:<br>";
    echo "<table>";
    echo "<tr><th>".t("Saapuminen")."</th><td><input type='text' size='10' name='h_siirra_saapuminen' value='$h_siirra_saapuminen'></td>";

    if (!$h_haut) {
      echo "<td class='back'><font class='error'>".t("HUOM: Syötä vähintään yksi seuraavista hakuehdoista")."!</font></td>";
    }

    echo "</tr>";
    echo "<tr><th>".t("Tilausnumero")."</th><td><input type='text' size='10' name='h_siirra_tilaus' value='$h_siirra_tilaus'></td></tr>";
    echo "<tr><th>".t("Tuoteno")."</th><td><input type='text' size='10' name='h_siirra_tuoteno' value='$h_siirra_tuoteno'></td></tr>";
    echo "<tr><th>".t("Toim_tuoteno")."</th><td><input type='text' size='10' name='h_siirra_toimtuoteno' value='$h_siirra_toimtuoteno'></td></tr>";
    echo "</table>";
    echo "<input type='submit' value='".t("Etsi")."'><br><br>";
    echo "</form>";

  }

  if (mysql_num_rows($result) > 0) {

    $default_nakyvissa = (int) $yhtiorow["oston_alekentat"];
    $default_totaali = (int) $yhtiorow["oston_alekentat"];

    $default_nakyvissa+=10;
    $default_totaali+=10;

    if ($yhtiorow['suuntalavat'] == 'S') {
      $default_nakyvissa++;
      $default_totaali+=2;
    }

    if ($yhtiorow['myyntihinta_paivitys_saapuminen'] == 'K') {
      $default_nakyvissa++;
      $default_totaali++;
    }

    pupe_DataTables(array(array($pupe_DataTables, $default_nakyvissa, $default_totaali, true, true, true)));
  }

  echo "<form id='paaformi' method='post' autocomplete='off'>";
  echo "<input type='hidden' name='toimipaikka' value='{$toimipaikka}' />";
  echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
  echo "<input type='hidden' name='toiminto' value='kohdista'>";

  if (isset($nayta_siirrettavat) and $nayta_siirrettavat == "YES") {
    echo "<input type='hidden' name='nayta_siirrettavat' value='$nayta_siirrettavat'>";
    echo "<input type='hidden' name='tila' value='tee_siirto'>";
  }
  else {
    echo "<input type='hidden' name='tila' value='tee_kohdistus'>";
  }

  echo "<input type='hidden' name='otunnus' value='$otunnus'>";
  echo "<input type='hidden' name='tee' value='K'>";
  echo "<input type='hidden' name='nayta_kohdistetut' value='$nayta_kohdistetut'>";
  echo "<input type='hidden' name='nayta_kaikki' value='$nayta_kaikki'>";
  echo "<input type='hidden' name='refresh_screen_ajaxille' id='refresh_screen_ajaxille' value='false' />";

  echo "  <style type='text/css'>
        div.vihrea, div.punainen {
          width: 15px;
          height: 15px;
          margin-left: auto;
          margin-right: auto;
          margin-top: 2px;

          border-radius: 50%;
          -webkit-border-radius: 50%;
          -moz-border-radius: 50%;

          -webkit-box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.4), 0 1px rgba(200, 200, 200, 0.8);
          box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.4), 0 1px rgba(200, 200, 200, 0.8);
          -moz-box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.4), 0 1px rgba(200, 200, 200, 0.8);
        }

        div.vihrea {
          background-color: #5D2;
        }

        div.punainen {
          background-color: #E66;
        }
      </style>";

  echo "<table class='display dataTable ostorivien_kohdistus' id='{$pupe_DataTables}'>";

  echo "<thead>";
  echo "<tr>";
  echo "<th></th>";

  echo "<th nowrap class='ptop'>";
  if (isset($nayta_siirrettavat) and $nayta_siirrettavat == "YES") echo t("Saapuminen")."<br />";
  echo t('Tilaus')."</th>";
  echo "<th nowrap class='ptop'>".t('Tuoteno')."<br />".t('Toim_tuoteno')."</th>";
  echo "<th nowrap class='ptop'>".t('Nimitys')."</th>";
  echo "<th nowrap class='ptop'>".t('Määrä')."<br />".t('Määrä/Ulk')."</th>";
  echo "<th nowrap class='ptop'>".t('Hinta')."<br />", t("Valuutta"), "</th>";

  for ($alepostfix=1; $alepostfix <= $yhtiorow["oston_alekentat"]; $alepostfix++) {
    echo "<th nowrap class='ptop'>".t("Ale").$alepostfix;

    if ($alepostfix == $yhtiorow["oston_alekentat"]) {
      echo "<br>".t('Alv');
    }

    echo "</th>";
  }

  echo "<th nowrap class='ptop'>".t('Tilauksen')."<br />".t('erikoisale')."</th>";
  echo "<th nowrap class='ptop'>".t('Rivihinta')."<br />".t('+Alv -Ale')."</th>";

  if ($yhtiorow['myyntihinta_paivitys_saapuminen'] == 'K') {
    echo "<th nowrap class='ptop'>", t('Myyntihinta'), "</th>";
  }

  echo "<th nowrap class='ptop'>".t('Kommentti')."<br />".t('Toimaika')."</th>";

  if ($yhtiorow['suuntalavat'] == 'S') {
    echo "<th>".t("Varastopaikka");
    echo "<br>", t("Suuntalava"), "</th>";
    echo "</th>";
  }

  echo "<th></th>";

  if ($yhtiorow['suuntalavat'] == 'S') {
    echo "<th style='visibility:hidden; display:none;'></th>";
  }

  echo "</tr>";

  echo "<tr>";
  echo "<td><input type='hidden' class='search_field' name='search_kohdistus'></td>";
  echo "  <td><input type='text' class='search_field' name='search_tilaus'></td>
      <td><input type='text' class='search_field' name='search_tuoteno'></td>";

  echo "<td>";
  echo "<input type='text' class='search_field' name='search_nimitys'>";
  echo "</td>";

  echo "  <td><input type='text' class='search_field' name='search_maara'></td>
      <td><input type='text' class='search_field' name='search_hinta'></td>";

  for ($alepostfix=1; $alepostfix <= $yhtiorow["oston_alekentat"] ; $alepostfix++) {
    echo "<td><input type='text' class='search_field' name='search_ale$alepostfix'></td>";
  }

  echo "<td><input type='text' class='search_field' name='search_erikoisale'></td>";
  echo "<td><input type='text' class='search_field' name='search_rivihinta'></td>";
  echo "<td><input type='text' class='search_field' name='search_kommentti'></td>";

  if ($yhtiorow['suuntalavat'] == 'S') {
    echo "<td>";
    echo "<input type='text' class='search_field' name='search_varastopaikka'>";

    echo "<br /><select class='suuntalava_sort'><option value=''></option>";

    $query = "SELECT DISTINCT suuntalavat.sscc
              FROM suuntalavat
              JOIN suuntalavat_saapuminen ON (suuntalavat_saapuminen.yhtio = suuntalavat.yhtio AND suuntalavat_saapuminen.suuntalava = suuntalavat.tunnus AND suuntalavat_saapuminen.saapuminen = '{$otunnus}')
              WHERE suuntalavat.yhtio = '{$kukarow['yhtio']}'";
    $suuntalava_res = pupe_query($query);

    while ($suuntalava_row = mysql_fetch_assoc($suuntalava_res)) {
      echo "<option value='{$suuntalava_row['sscc']}'>{$suuntalava_row['sscc']}</option>";
    }

    echo "</select>";
    echo "</td>";
  }

  echo "<td><input type='hidden' class='search_field' name='search_lisavar'></td>";

  if ($yhtiorow['suuntalavat'] == 'S') {
    echo "<td style='visibility:hidden; display:none;'><input type='hidden' class='search_field' name='search_suuntalava_haku'></td>";
  }
  else {
    echo "<td><input type='hidden' class='search_field' name='search_edit'></td>";
  }

  echo "</tr>";
  echo "</thead>";

  //itse rivit
  $rivilask = 0;

  echo "<tbody>";

  if (mysql_num_rows($result) == 0) {
    echo "<tr><td colspan='5' class='back'>".t("Yhtään kohdistettavaa riviä ei löydy")."!</td></tr>";
  }

  while ($rivirow = mysql_fetch_assoc($result)) {

    $red = $green = false;

    if ($nayta_kohdistetut == 'hinta') {
      $hintapyoristys_hinta = hintapyoristys($rivirow['hinta'], $yhtiorow['hintapyoristys']);
      $hintapyoristys_ostohinta = hintapyoristys($rivirow['ostohinta'], $yhtiorow['hintapyoristys']);

      if ($rivirow['hinta_alkuperainen'] == 0 or (abs(hintapyoristys($rivirow['rivihinta'] - $rivirow['hinta_alkuperainen'], 2)) <= 0.02)) continue;
    }
    elseif ($nayta_kohdistetut == "lasku" or $nayta_kohdistetut == "jees") {

      $query = "SELECT tilausrivi, IFNULL(SUM(kappalemaara), 'X') kpl
                FROM asn_sanomat USE INDEX (fulltext_tilausrivi)
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND laji IN ('mav', 'tec')
                AND MATCH (tilausrivi) AGAINST ('{$rivirow['rivitunnus']}' IN BOOLEAN MODE)
                GROUP BY 1";
      $asn_chk_res = pupe_query($query);
      $asn_chk_row = mysql_fetch_assoc($asn_chk_res);

      if (mysql_num_rows($asn_chk_res) > 0 and $asn_chk_row['kpl'] != 'X' and $asn_chk_row['tilausrivi'] != '' and $rivirow['uusiotunnus'] != 0) {

        $query = "SELECT SUM(tilausrivi.varattu+tilausrivi.kpl) kpl
                  FROM tilausrivi
                  WHERE yhtio     = '{$kukarow['yhtio']}'
                  AND tunnus      IN ({$asn_chk_row['tilausrivi']})
                  AND uusiotunnus = '{$rivirow['uusiotunnus']}'";
        $tilriv_kpl_chk_res = pupe_query($query);
        $tilriv_kpl_chk_row = mysql_fetch_assoc($tilriv_kpl_chk_res);

        if ($asn_chk_row['kpl'] != $tilriv_kpl_chk_row['kpl']) $red = true;
        else $green = true;

        if ($nayta_kohdistetut == "lasku" and $green) {

          $hintapyoristys_hinta = hintapyoristys($rivirow['hinta'], $yhtiorow['hintapyoristys']);
          $hintapyoristys_ostohinta = hintapyoristys($rivirow['ostohinta'], $yhtiorow['hintapyoristys']);

          if ($rivirow['hinta_alkuperainen'] == 0 or (abs(hintapyoristys($rivirow['rivihinta'] - $rivirow['hinta_alkuperainen'], 2)) <= 0.02 and round(abs($hintapyoristys_hinta - $hintapyoristys_ostohinta), 2) <= 0.02)) continue;
        }
      }
      else {

        if ($nayta_kohdistetut == "lasku" and mysql_num_rows($asn_chk_res) == 0) continue;

        $tilriv_kpl_chk_row['kpl'] = FALSE;
      }
    }

    if ($excel_array_check) {
      $excel_array['rows'][$rivirow['rivitunnus']] = array();
    }

    echo "<tr class='aktiivi $rivirow[rivitunnus]' id='tr_{$rivirow['rivitunnus']}'>";

    if ($rivirow["uusiotunnus"] > 0) {
      $chk = "checked";
      $td  = "td class='tumma ptop'";
    }
    else {
      $chk = '';

      if ($rivirow["perheid2"] > 0 and $rivirow["perheid2"] != $rivirow["rivitunnus"]) {
        $td = "td class='spec ptop'";
      }
      else {
        $td = "td class='ptop'";
      }
    }

    if ($rivirow["perheid"] > 0 or $rivirow["perheid2"] > 0) {
      if ($rivirow["perheid"] == 0) {
        $pklisa = " and perheid2 = '$rivirow[perheid2]'";
      }
      else {
        $pklisa = " and (perheid = '$rivirow[perheid]' or perheid2 = '$rivirow[perheid]')";
      }
    }

    if ($rivirow["rivitunnus"] == $rivirow["perheid2"] and $rivilask != 0) {
      echo "</tr><tr><td class='back'><br></td></tr>";
    }

    $onkosuoratoimitus = FALSE;
    $onkosuoratoimitus_suoraan_asiakkaalle = FALSE;

    $onko_suoratoimitus_res = onko_suoratoimitus($rivirow['rivitunnus']);

    //Tutkitaan löytyykö JT-rivi joka mäppäytyy tähän ostoriviin
    if ($varastoon_row = mysql_fetch_assoc($onko_suoratoimitus_res)) {
      $onkosuoratoimitus = TRUE;

      if ($varastoon_row["suoraan_laskutukseen"] != "") {
        $rivirow["kommentti"] .= "<br>".t("Suoratoimitus asiakkaalle").": ".$varastoon_row["nimi"];
        $onkosuoratoimitus_suoraan_asiakkaalle = TRUE;
      }
      else {
        $rivirow["kommentti"] .= "<br>".t("Tilattu asiakkaalle").": ".$varastoon_row["nimi"];
      }
    }
    else {
      // katsotaan onko tuotetta jälkitoimituksessa
      $query = "SELECT sum(varattu+jt) kpl
                FROM tilausrivi USE INDEX (yhtio_tyyppi_tuoteno_laskutettuaika)
                WHERE yhtio        = '{$kukarow['yhtio']}'
                AND tyyppi         = 'L'
                AND tuoteno        = '{$rivirow['tuoteno']}'
                AND laskutettuaika = '0000-00-00'
                AND var            = 'J'";
      $jt_check_res = pupe_query($query);
      $jt_check_row = mysql_fetch_assoc($jt_check_res);

      if ($jt_check_row['kpl'] > 0) {
        $rivirow['kommentti'] .= "<br/>".t("Jälkitoimituksessa")." {$jt_check_row['kpl']} ".t_avainsana("Y", "", "and avainsana.selite='$rivirow[tilyksikko]'", "", "", "selite");
      }
    }

    $rivilask++;

    // Tuoteperheiden lapsille ei näytetä rivinumeroa
    if ($rivirow["perheid"] == $rivirow["rivitunnus"] or ($rivirow["perheid2"] == $rivirow["rivitunnus"] and $rivirow["perheid"] == 0)) {
      $query = "SELECT count(*) kpl
                from tilausrivi
                where yhtio = '$kukarow[yhtio]'
                and otunnus in ($rivirow[otunnus],$otunnus)
                $pklisa";
      $pkres = pupe_query($query);
      $pkrow = mysql_fetch_assoc($pkres);

      $pknum     = $pkrow['kpl'];
      $borderlask = $pkrow['kpl'];

    }
    elseif ($rivirow["perheid"] == 0 and $rivirow["perheid2"] == 0) {
      $pknum     = 0;
      $borderlask = 0;
    }

    $classlisa   = "";
    $class     = "";
    $classeka  = "";

    if ($borderlask == 1 and $pknum == 1) {
      $classeka  = " style='border-top: 1px solid; border-left: 1px solid; border-bottom: 1px solid;' ";
      $classlisa = " style='border-top: 1px solid; border-bottom: 1px solid; border-right: 1px solid;' ";
      $class    .= " style=' border-top: 1px solid; border-bottom: 1px solid;' ";

      $borderlask--;
    }
    elseif ($borderlask == $pknum and $pknum > 0) {
      $classeka  = " style='border-top: 1px solid; border-left: 1px solid;' ";
      $classlisa = " style='border-top: 1px solid; border-right: 1px solid;' ";
      $class    .= " style='border-top: 1px solid;' ";
      $borderlask--;
    }
    elseif ($borderlask == 1) {
      if ($pknum > 1) {
        $classeka  = " style='border-bottom: 1px solid; border-left: 1px solid;' ";
        $classlisa =" style='font-style:italic; border-bottom: 1px solid; border-right: 1px solid;' ";
        $class    .= " style='font-style:italic; border-bottom: 1px solid;' ";
      }
      else {
        $classeka  = " style='border-bottom: 1px solid; border-left: 1px solid;' ";
        $classlisa = $class." style='border-bottom: 1px solid; border-right: 1px solid;' ";
        $class    .= " style='border-bottom: 1px solid;' ";
      }

      $borderlask--;
    }
    elseif ($borderlask > 0 and $borderlask < $pknum) {
      $classeka  = " style='border-left: 1px solid;' ";
      $classlisa = $class." style='font-style:italic; border-right: 1px solid;' ";
      $class    .= " style='font-style:italic;' ";
      $borderlask--;
    }

    // näytetään vaan tsekpoksi jos riviä saa vielä muuttaa
    if (isset($nayta_siirrettavat) and $nayta_siirrettavat == "YES") {
      echo "<$td $classeka>";
      echo "<input type='checkbox' name='rivi_siirrettavat_tunnukset[]' value='$rivirow[rivitunnus]###$rivirow[uusiotunnus]' >";
      echo "<input type='hidden'   name='rivi_kaikki_tunnukset[]'  value='$rivirow[rivitunnus]###$rivirow[uusiotunnus]'>";
      echo "</$td>";
    }
    elseif ($rivirow["varastossa_kpl"] == 0) {
      echo "<$td $classeka>";
      echo "<a name='$rivirow[rivitunnus]'></a>";

      //tsekataan isän status
      $query = "SELECT kpl
                from tilausrivi
                where yhtio = '$kukarow[yhtio]'
                and tunnus  = '$rivirow[perheid2]'";
      $isapkres = pupe_query($query);
      $isapkrow = mysql_fetch_assoc($isapkres);

      if ($rivirow["rivitunnus"] == $rivirow["perheid2"] or $rivirow["perheid2"] == 0 or $isapkrow["kpl"] != 0) {
        echo "<input disabled='true' type='checkbox' id='rivi_valitut_tunnukset_$rivirow[rivitunnus]' name='rivi_valitut_tunnukset[]' value='$rivirow[rivitunnus]###$rivirow[otunnus]' $chk>";

        if ($excel_array_check) {
          if (!empty($chk)) {
            $excel_array['rows'][$rivirow['rivitunnus']]['kohdistus'] = t("Kohdistettu");
          }
          else {
            $excel_array['rows'][$rivirow['rivitunnus']]['kohdistus'] = t("Avoin");
          }
        }
      }

      echo "<input type='hidden'   name='rivi_kaikki_tunnukset[]'  value='$rivirow[rivitunnus]###$rivirow[otunnus]'>";
      echo "<input type='hidden'   name='rivi_summa' value='$rivirow[alerivihinta]'>";
      echo "</$td>";
    }
    elseif ($rivirow["varastossa_kpl"] != 0) {
      echo "<$td $classeka id='varastossa_kpl_{$rivirow['rivitunnus']}'>x</$td>";

      if ($excel_array_check) {
        $excel_array['rows'][$rivirow['rivitunnus']]['kohdistus'] = t("Viety varastoon");
      }
    }
    else {
      echo "<$td $classeka></$td>";
    }

    echo "<$td $class nowrap>";
    if (isset($nayta_siirrettavat) and $nayta_siirrettavat == "YES") echo "$rivirow[keikka]<br>";

    // tooltip jos ostotilauksella on kommentti
    if ($rivirow["comments"] != '') {
      echo "
        <div id='div_comment_$rivirow[rivitunnus]' class='popup' style='width: 400px;'>
        {$rivirow["comments"]}
      </div>
      <a class='tooltip' id='comment_$rivirow[rivitunnus]'>$rivirow[otunnus]</a>";
    }
    else {
      echo "$rivirow[otunnus]";
    }

    if ($excel_array_check) {
      $excel_array['rows'][$rivirow['rivitunnus']]['tilaus'] = $rivirow['otunnus'];
      $excel_array['rows'][$rivirow['rivitunnus']]['tuoteno'] = $rivirow['tuoteno'];
      $excel_array['rows'][$rivirow['rivitunnus']]['toim_tuoteno'] = $rivirow['toim_tuoteno'];
    }

    echo " <img alt='", t("Etsi"), "' title='", t("Etsi"), "' class='use_as_search_tilaus' name='use_as_search_{$rivirow['otunnus']}' src='../pics/lullacons/system-search.png' />";

    if (!$rivirow['varastoon']) {
      echo "&nbsp;<img src='../pics/lullacons/alert.png' alt='", t("Huomio"), "' title='", t("HUOM: Riviä ei viedä automaattisesti varastoon."), "' />";
    }

    if ($red or $green) {
      if ($red) echo "&nbsp;<div class='punainen'>&nbsp;</div>";
      else echo "&nbsp;<div class='vihrea'>&nbsp;</div>";
    }
    if ($rivirow["uusiotunnus"] > 0 and $chk != '') {
      $state = "";
    }
    else {
      $state = "none";
    }
    echo "<br>";
    echo "<div id='massalisanappi_{$rivirow['rivitunnus']}' style='display:{$state};'/>";
    echo "<input type='button' id='massaesiin_{$rivirow['rivitunnus']}' value='".t('Lisävalinnat')."' />";
    echo "</div>";
    echo "<div id='massalisa_{$rivirow['rivitunnus']}' style='display:none;'>";
    echo "<br>";
    echo t("Suuntalavan asetukset");
    echo "<table>";
    $query = "SELECT *
              FROM pakkaus
              WHERE yhtio = '{$kukarow['yhtio']}'";
    $pakkaus_result = pupe_query($query);

    echo "<tr><th>", t("Tyyppi"), "</th>";
    echo "<td><select id='tyyppi_{$rivirow['rivitunnus']}' ><option value=''>", t("Valitse"), "</option>";

    while ($pakkaus_row = mysql_fetch_assoc($pakkaus_result)) {
      $sel = $tyyppi == $pakkaus_row['tunnus'] ? ' selected' : '';

      echo "<option value='{$pakkaus_row['tunnus']}'{$sel}>".t_tunnus_avainsanat($pakkaus_row, "pakkaus", "PAKKAUSKV")." ".t_tunnus_avainsanat($pakkaus_row, "pakkauskuvaus", "PAKKAUSKV")."</option>";
    }

    echo "</select>{$virheviesti['tyyppi']}</td></tr>";

    echo "<tr><th>", t("Keräysvyöhyke"), "</th>";
    echo "<td><select id='keraysvyohyke_{$rivirow['rivitunnus']}'><option value=''>", t("Valitse"), "</option>";

    $query = "SELECT tunnus, nimitys FROM keraysvyohyke WHERE yhtio = '{$kukarow['yhtio']}' AND nimitys != ''";
    $keraysvyohyke_result = pupe_query($query);

    while ($keraysvyohyke_row = mysql_fetch_assoc($keraysvyohyke_result)) {
      $sel = $keraysvyohyke == $keraysvyohyke_row['tunnus'] ? ' selected' : '';

      echo "<option value='{$keraysvyohyke_row['tunnus']}'{$sel}>{$keraysvyohyke_row['nimitys']}</option>";
    }

    echo "</select>{$virheviesti['keraysvyohyke']}</td></tr>";

    $sel_y = $kaytettavyys == 'Y' ? ' selected' : '';
    $sel_l = $kaytettavyys == 'L' ? ' selected' : '';

    echo "<tr><th>", t("Käytettävyys"), "</th>";
    echo "<td><select id='kaytettavyys_{$rivirow['rivitunnus']}'>";

    echo "<option value='Y'{$sel_y}>", t("Yksityinen"), "</option>";
    echo "<option value='L'{$sel_l}>", t("Yleinen"), "</option>";
    echo "</select>{$virheviesti['kaytettavyys']}</td></tr>";

    echo "<tr><th>", t("Terminaalialue"), "</th>";
    echo "<td><select id='terminaalialue_{$rivirow['rivitunnus']}'><option value=''>", t("Valitse"), "</option>";

    $terminaalialue_result = t_avainsana("TERMINAALIALUE");

    while ($terminaalialue_row = mysql_fetch_assoc($terminaalialue_result)) {
      $sel = $terminaalialue == $terminaalialue_row['selite'] ? ' selected' : '';

      echo "<option value='{$terminaalialue_row['selite']}'{$sel}>{$terminaalialue_row['selite']}</option>";
    }

    echo "</select>{$virheviesti['terminaalialue']}</td></tr>";
    echo "<tr><th>".t("Tuotemäärä")."</th>";
    echo "<td><input type='text' id='massajakokplmaara_{$rivirow['rivitunnus']}' /></td></tr>";

    echo "<tr><th>", t("Valitse tulostin"), "</th><td><select id='kirjoitin_{$rivirow['rivitunnus']}'>";
    echo "<option value=''>", t("Ei kirjoitinta"), "</option>";

    $query = "SELECT *
              FROM kirjoittimet
              WHERE yhtio  = '{$kukarow['yhtio']}'
              AND komento != 'email'
              AND komento != 'edi'
              ORDER BY kirjoitin";
    $kires = mysql_query($query) or pupe_error($query);

    while ($kirow = mysql_fetch_assoc($kires)) {
      if ($kirow['tunnus'] == $kirjoitin) $select = ' selected';
      else $select = '';
      echo "<option value='{$kirow['tunnus']}'{$select}>{$kirow['kirjoitin']}</option>";
    }

    echo "</select><select id='kappalemaara_{$rivirow['rivitunnus']}' />";
    for ($i = 1; $i < 6; $i++) echo "<option value='{$i}'>{$i}</option>";
    echo "</select> ".t("Kpl")."</td></tr>";

    echo "</table>";
    echo "<input type='button' id='massasubmitti_{$rivirow['rivitunnus']}' value='".t("Jaa rivi suuntalavoille")."'/>";
    echo "<input type='hidden' id='varastoon_{$rivirow['rivitunnus']}' value='{$rivirow['varastoon']}' />";
    echo "</div>";
    echo "</$td>";
    echo "<$td $class nowrap>";

    // tehdään pop-up divi
    $pop_yks = t_avainsana("Y", "", "and avainsana.selite='$rivirow[tilyksikko]'", "", "", "selite");

    echo "<div id='div_$rivirow[rivitunnus]' class='popup' style='width: 400px;'>";
    echo "<ul>";
    echo "<li>".t("Tilattu").": $rivirow[tilattu] $pop_yks</li><li>".t("Varattu").": $rivirow[varattu] $pop_yks</li>";
    echo "<li>".t("Keskihinta").": $rivirow[keskihinta] $rivirow[valuutta]</li><li>".t("Ostohinta").": $rivirow[ostohinta] $rivirow[valuutta]</li>";
    echo "</ul>";
    echo "</div>";
    echo "<a target='Tuotekysely' href='{$palvelin2}tuote.php?tee=Z&tuoteno=".urlencode($rivirow["tuoteno"])."&lopetus=$PHP_SELF////toiminto=kohdista//toimittajaid=$toimittajaid//tee=K//otunnus=$otunnus//nayta_kohdistetut=$nayta_kohdistetut//nayta_kaikki=$nayta_kaikki//tila=$tila//riviid=$rivirow[rivitunnus]///$rivirow[rivitunnus]' class='tooltip' id='$rivirow[rivitunnus]'>$rivirow[tuoteno]</a> <img alt='", t("Etsi"), "' title='", t("Etsi"), "' class='use_as_search_tuoteno' name='use_as_search_{$rivirow['tuoteno']}' src='../pics/lullacons/system-search.png' />";
    echo "<br>$rivirow[toim_tuoteno]";

    echo "&nbsp;";

    $_src = "{$palvelin2}pics/lullacons/info.png";
    $_title = t("Näytä saldo");

    $id = md5(uniqid());

    echo "<img title='{$_title}' src='{$_src}' class='hae_saldo' id='{$id}' />";
    echo "<input type='hidden' id='{$id}_tuoteno' value='{$rivirow['tuoteno']}' />";
    echo "<input type='hidden' id='{$id}_varasto' value='{$rivirow['varasto']}' />";
    echo "<span class='saldo_{$id}' style='display:none;'></span>";

    if ($rivirow['toim_viivakoodi'] != '') {
      echo "<div style='display:none; visibility:hidden;'>{$rivirow['toim_viivakoodi']}</div>";
    }

    if ($rivirow['tuotteen_toimittajat_tunnus'] != '') {
      $query = "SELECT tuoteno, viivakoodi
                FROM tuotteen_toimittajat_tuotenumerot
                WHERE yhtio             = '{$kukarow['yhtio']}'
                AND toim_tuoteno_tunnus = '{$rivirow['tuotteen_toimittajat_tunnus']}'";
      $vaihtoehtoiset_res = pupe_query($query);

      while ($vaihtoehtoiset_row = mysql_fetch_assoc($vaihtoehtoiset_res)) {
        echo "<br /><font class='info' title='", t("Tuotteen toimittajan vaihtoehtoinen tuotenumero"), "'>{$vaihtoehtoiset_row['tuoteno']}</font>";
        echo "<div style='display:none; visibility:hidden;'>{$vaihtoehtoiset_row['viivakoodi']}</div>";
      }
    }

    $query = "SELECT sarjanumeroseuranta
              FROM tuote
              WHERE yhtio   = '$kukarow[yhtio]'
              and tuoteno   = '$rivirow[tuoteno]'
              and ei_saldoa = ''";
    $sarjares = pupe_query($query);
    $sarjarow = mysql_fetch_assoc($sarjares);

    if ($sarjarow["sarjanumeroseuranta"] != "") {
      if ($rivirow["siskpl"] < 0) {
        $tunken = "sarjanumeroseuranta.myyntirivitunnus";
        $tunind = "yhtio_myyntirivi";
      }
      else {
        $tunken = "sarjanumeroseuranta.ostorivitunnus";
        $tunind = "yhtio_ostorivi";
      }

      if ($sarjarow["sarjanumeroseuranta"] == "S" or $sarjarow["sarjanumeroseuranta"] == "U" or $sarjarow["sarjanumeroseuranta"] == "V") {
        $query = "SELECT count(distinct sarjanumero) kpl, min(sarjanumero) sarjanumero
                  FROM sarjanumeroseuranta
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tuoteno = '$rivirow[tuoteno]'
                  and $tunken = '$rivirow[rivitunnus]'";

        $snro_ok = t("S:nro ok");
        $snro   = t("S:nro");
      }
      else {
        $query = "SELECT sum(sarjanumeroseuranta.era_kpl*if(tilausrivi.tunnus is not null, if(tilausrivi.kpl+tilausrivi.varattu+tilausrivi.jt > 0 or tilausrivi.tyyppi = 'O', 1, -1), 1)) kpl, min(sarjanumeroseuranta.sarjanumero) sarjanumero
                  FROM sarjanumeroseuranta
                  LEFT JOIN tilausrivi ON (tilausrivi.yhtio = sarjanumeroseuranta.yhtio and tilausrivi.tunnus = sarjanumeroseuranta.myyntirivitunnus)
                  WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
                  and sarjanumeroseuranta.tuoteno = '$rivirow[tuoteno]'
                  and $tunken = '$rivirow[rivitunnus]'";

        $snro_ok = t("E:nro ok");
        $snro   = t("E:nro");
      }

      $sarjares = pupe_query($query);
      $sarjarow2 = mysql_fetch_assoc($sarjares);

      if ($sarjarow2["kpl"] == abs($rivirow["siskpl"])) {
        echo "<br>(<a href='$PHP_SELF?tila=sarjanumerot&toiminto=kohdista&toimittajaid=$toimittajaid&tuoteno=".urlencode($rivirow["tuoteno"])."&ostorivitunnus=$rivirow[rivitunnus]&from=kohdista&tee=K&otunnus=$otunnus&nayta_kohdistetut=$nayta_kohdistetut&nayta_kaikki=$nayta_kaikki#$sarjarow2[sarjanumero]' style='color:#00FF00;'>$snro_ok</font></a>)";
      }
      else {
        echo "<br>(<a href='$PHP_SELF?tila=sarjanumerot&toiminto=kohdista&toimittajaid=$toimittajaid&tuoteno=".urlencode($rivirow["tuoteno"])."&ostorivitunnus=$rivirow[rivitunnus]&from=kohdista&tee=K&otunnus=$otunnus&nayta_kohdistetut=$nayta_kohdistetut&nayta_kaikki=$nayta_kaikki#$sarjarow2[sarjanumero]' style='color:#DD0000;'>$snro</a>)";
      }
    }

    echo "<br><div style='display:none; visibility:hidden;'>$rivirow[eankoodi]</div></$td>";
    echo "<{$td} {$class}>";

    if ($sarjarow["sarjanumeroseuranta"] == "U" and (!isset($nayta_siirrettavat) or $nayta_siirrettavat == "")) {
      echo "<input size='15' type='text' name='rivi_nimitys[$rivirow[rivitunnus]]' value='$rivirow[nimitys]'>";
    }
    else {
      $_nimitys = t_tuotteen_avainsanat($rivirow, 'nimitys');
      echo $_nimitys;

      if ($excel_array_check) {
        $excel_array['rows'][$rivirow['rivitunnus']]['nimitys'] = $_nimitys;
      }
    }

    echo "</{$td}>";
    echo "<$td $class align='right'>";

    // jos riviä ei ole vielä viety varastoon, voidaan muuttaa kappaleita
    if (isset($nayta_siirrettavat) and $nayta_siirrettavat == "YES" and $sarjarow["sarjanumeroseuranta"] == "" and !$onkosuoratoimitus) {
      echo "<input size='5' type='text' name='siirrettavat_kpl[$rivirow[rivitunnus]]' value='". (float) $rivirow["siskpl"]."' style='text-align:right'> ".t_avainsana("Y", "", "and avainsana.selite='$rivirow[tilyksikko]'", "", "", "selite")."<br><span id='ulkkpl_{$rivirow['rivitunnus']}'>". (float) $rivirow["ulkkpl"]."</span> ".t_avainsana("Y", "", "and avainsana.selite='$rivirow[toimyksikko]'", "", "", "selite");
    }
    elseif ($rivirow["varattu"] != 0 and !$onkosuoratoimitus) {
      echo "<input size='5' type='text' name='rivi_kpl[$rivirow[rivitunnus]]' id='rivi_kpl_{$rivirow['rivitunnus']}' value='". (float) $rivirow["siskpl"]."' style='text-align:right'> ".t_avainsana("Y", "", "and avainsana.selite='$rivirow[tilyksikko]'", "", "", "selite")."<br><span id='ulkkpl_{$rivirow['rivitunnus']}'>". (float) $rivirow["ulkkpl"]."</span> ".t_avainsana("Y", "", "and avainsana.selite='$rivirow[toimyksikko]'", "", "", "selite");
      echo "<input type='hidden' id='rivi_kpl_alkuperainen_{$rivirow['rivitunnus']}' value='", (float) $rivirow['siskpl'], "' />";
    }
    else {
      echo (float) $rivirow["siskpl"]."<br><span id='ulkkpl_{$rivirow['rivitunnus']}'>". (float) $rivirow["ulkkpl"]."</span>";
      echo "<input type='hidden' id='rivi_kpl_alkuperainen_{$rivirow['rivitunnus']}' value='", (float) $rivirow['siskpl'], "' />";
    }

    if ($excel_array_check) {
      $excel_array['rows'][$rivirow['rivitunnus']]['maara'] = (float) $rivirow["siskpl"];
      $excel_array['rows'][$rivirow['rivitunnus']]['maara_ulk'] = (float) $rivirow["ulkkpl"];
    }

    if (($red or $green) and $rivirow['uusiotunnus'] != 0 and $asn_chk_row['kpl'] != 'X' and $tilriv_kpl_chk_row['kpl'] !== FALSE and $tilriv_kpl_chk_row['kpl'] - $asn_chk_row['kpl'] != 0) echo "<br /><font class='error'>", ($tilriv_kpl_chk_row['kpl'] - $asn_chk_row['kpl']), "</font>";

    echo "</$td>";

    if (isset($nayta_siirrettavat) and $nayta_siirrettavat == "YES") {
      echo "<$td $class align='right'>".hintapyoristys($rivirow["hinta"], 6)."<br>$kulurow[valkoodi]</$td>";

      for ($alepostfix = 1; $alepostfix <= $yhtiorow["oston_alekentat"] ; $alepostfix ++) {
        echo "<$td $class align='right'>". (float) $rivirow["ale$alepostfix"];

        if ($alepostfix == $yhtiorow["oston_alekentat"]) {
          if ($laskurow['vienti']=='C' or $laskurow['vienti']=='J') {
            echo "<br>". (float) $rivirow["alv"]."&nbsp;";
          }
          else {
            echo "<br>0&nbsp;";
          }
        }

        echo "</$td>";
      }
    }
    else {
      echo "<$td $class align='right'><input size='10' type='text' name='rivi_hinta[$rivirow[rivitunnus]]' id='rivi_hinta_{$rivirow['rivitunnus']}' value='".hintapyoristys($rivirow["hinta"], 6)."' style='text-align:right'><br>";
      echo "<span id='rivin_hinta_{$rivirow['rivitunnus']}'>".hintapyoristys($rivirow["hinta"], 6)."</span> $kulurow[valkoodi]";
      echo "<input type='hidden' name='rivihintaalkuperainen_{$rivirow['rivitunnus']}' id='rivihintaalkuperainen_{$rivirow['rivitunnus']}' value='{$rivirow['hinta']}' />";

      if ($excel_array_check) {
        $excel_array['rows'][$rivirow['rivitunnus']]['hinta'] = hintapyoristys($rivirow["hinta"], 6);
        $excel_array['rows'][$rivirow['rivitunnus']]['valuutta'] = $kulurow['valkoodi'];
      }

      if ($rivirow['hinta_alkuperainen'] != 0 and hintapyoristys($rivirow['rivihinta'] - $rivirow['hinta_alkuperainen'], 2) != 0) {

        echo "<div id='div_{$rivirow['rivitunnus']}_hinta_alkuperainen' class='popup' style='width: 400px;'>";
        echo t("Erotus / Alkuperäinen hinta");
        echo "</div>";

        echo "<br /><span class='tooltip' id='{$rivirow['rivitunnus']}_hinta_alkuperainen'><font class='error'>", hintapyoristys($rivirow['rivihinta'] - $rivirow['hinta_alkuperainen']), "</font> / ", hintapyoristys($rivirow['hinta_alkuperainen'], $yhtiorow['hintapyoristys']), "</span>";
      }

      echo "</$td>";

      for ($alepostfix = 1; $alepostfix <= $yhtiorow["oston_alekentat"] ; $alepostfix ++) {
        echo "<$td $class align='right'>";
        echo "<input size='4' type='text' name='rivi_ale{$alepostfix}[$rivirow[rivitunnus]]' id='rivi_ale{$alepostfix}_{$rivirow['rivitunnus']}' value='". (float) $rivirow["ale$alepostfix"]."' style='text-align:right'>";
        echo "<input type='hidden' name='rivialealkuperainen{$alepostfix}_{$rivirow['rivitunnus']}' id='rivialealkuperainen{$alepostfix}_{$rivirow['rivitunnus']}' value='". (float) $rivirow["ale$alepostfix"]."' /> ";

        if ($excel_array_check) {
          $excel_array['rows'][$rivirow['rivitunnus']]["ale{$alepostfix}"] = (float) $rivirow["ale{$alepostfix}"];
        }

        if ($alepostfix == $yhtiorow["oston_alekentat"]) {
          if ($laskurow['vienti'] == 'C' or $laskurow['vienti'] == 'J') {
            echo "<br>". (float) $rivirow["alv"];

            if ($excel_array_check) {
              $excel_array['rows'][$rivirow['rivitunnus']]["alv"] = (float) $rivirow["alv"];
            }
          }
          else {
            echo "<br>0";

            if ($excel_array_check) {
              $excel_array['rows'][$rivirow['rivitunnus']]["alv"] = 0;
            }
          }
        }

        echo "</$td>";
      }
    }

    // rivi_aleE == erikoisalennus
    echo "<$td $class align='right'>";
    echo "<input size='4' type='text' name='rivi_aleE[$rivirow[rivitunnus]]' id='rivi_aleE_{$rivirow['rivitunnus']}' value='". (float) $rivirow["erikoisale"]."' style='text-align:right'>";
    echo "<input type='hidden' name='rivialealkuperainenE_{$rivirow['rivitunnus']}' id='rivialealkuperainenE_{$rivirow['rivitunnus']}' value='". (float) $rivirow["erikoisale"]."' /> ";
    echo "</$td>";

    if ($excel_array_check) {
      $excel_array['rows'][$rivirow['rivitunnus']]["erikoisale"] = (float) $rivirow["erikoisale"];
    }

    echo "<$td $class align='right' id='hinnat_{$rivirow['rivitunnus']}'>". (float) $rivirow["rivihinta"]."<br>". (float) $rivirow["alerivihinta"]."</$td>";

    if ($excel_array_check) {
      $excel_array['rows'][$rivirow['rivitunnus']]["rivihinta"] = (float) $rivirow["rivihinta"];
      $excel_array['rows'][$rivirow['rivitunnus']]["alerivihinta"] = (float) $rivirow["alerivihinta"];
    }

    if ($yhtiorow['myyntihinta_paivitys_saapuminen'] == 'K') {
      echo "<{$td} {$class} style='text-align:right'>";

      if (tarkista_oikeus('yllapito.php', 'tuote', 1)) {
        echo "<input type='text' name='rivi_myyntihinta[{$rivirow['rivitunnus']}]' id='rivi_myyntihinta_{$rivirow['rivitunnus']}' value='", hintapyoristys($rivirow["myyntihinta"], 6), "' size='10' style='text-align:right' />";
        echo "<input type='hidden' name='rivimyyntihintaalkuperainen_{$rivirow['rivitunnus']}' id='rivimyyntihintaalkuperainen_{$rivirow['rivitunnus']}' value='{$rivirow["myyntihinta"]}' /> ";
      }
      else {
        echo hintapyoristys($rivirow["myyntihinta"], 6);
      }

      echo "</{$td}>";
    }

    echo "<$td $class><input type='hidden' name='alerivihintaalkuperainen_{$rivirow['rivitunnus']}' id='alerivihintaalkuperainen_{$rivirow['rivitunnus']}' value='{$rivirow["alerivihinta"]}' />";

    if ($rivirow["toimaika"] != "0000-00-00") {
      echo tv1dateconv($rivirow["toimaika"]);
    }

    echo "<br>$rivirow[kommentti]";

    if ($excel_array_check) {
      $excel_array['rows'][$rivirow['rivitunnus']]["kommentti"] = $rivirow["kommentti"];

      if ($rivirow["toimaika"] != "0000-00-00") {
        $excel_array['rows'][$rivirow['rivitunnus']]["toimaika"] = tv1dateconv($rivirow["toimaika"]);
      }
      else {
        $excel_array['rows'][$rivirow['rivitunnus']]["toimaika"] = "";
      }
    }

    if ($onkosuoratoimitus_suoraan_asiakkaalle) {

      $toimitettuaikavv = substr($rivirow["suoratoimitettuaika"], 0, 4);
      $toimitettuaikakk = substr($rivirow["suoratoimitettuaika"], 5, 2);
      $toimitettuaikapp = substr($rivirow["suoratoimitettuaika"], 8, 2);

      if ($toimitettuaikavv == 0) $toimitettuaikavv = "";
      if ($toimitettuaikakk == 0) $toimitettuaikakk = "";
      if ($toimitettuaikapp == 0) $toimitettuaikapp = "";

      if ($rivirow["varastossa_kpl"] == 0) {
        echo "<br>".t("Toimitettu asiakkaalle").":<br>
            <input size='4' type='text' name='rivi_toimitettuaika_pp_{$rivirow["rivitunnus"]}' id='rivi_toimitettuaika_pp_{$rivirow["rivitunnus"]}' value='$toimitettuaikapp' style='text-align:right'>
            <input size='4' type='text' name='rivi_toimitettuaika_kk_{$rivirow["rivitunnus"]}' id='rivi_toimitettuaika_kk_{$rivirow["rivitunnus"]}' value='$toimitettuaikakk' style='text-align:right'>
            <input size='4' type='text' name='rivi_toimitettuaika_vv_{$rivirow["rivitunnus"]}' id='rivi_toimitettuaika_vv_{$rivirow["rivitunnus"]}' value='$toimitettuaikavv' style='text-align:right'>";
      }
      elseif ($toimitettuaikavv != "") {
        echo "<br>".t("Toimitettu asiakkaalle").": ".tv1dateconv("$toimitettuaikavv-$toimitettuaikakk-$toimitettuaikapp");
      }
    }

    if ($yhtiorow['suuntalavat'] == 'S' and $onkosuoratoimitus and $onkosuoratoimitus_suoraan_asiakkaalle) {
      echo "<input type='hidden' id='suoratoimitus_{$rivirow['rivitunnus']}' value='X' />";
    }

    echo "</$td>";

    if ($excel_array_check) {
      $excel_array['rows'][$rivirow['rivitunnus']]["varastopaikka"] = "";
      $excel_array['rows'][$rivirow['rivitunnus']]["suuntalava"] = "";
    }

    if ($yhtiorow['suuntalavat'] == 'S' and $rivirow["varastossa_kpl"] == 0 and (!isset($nayta_siirrettavat) or $nayta_siirrettavat != "YES")) {

      echo "<{$td} {$classlisa}>";

      if (trim($rivirow['hyllyalue']) != '') {

        if ($yhtiorow['varastontunniste'] != "") {

          $chk_var = kuuluukovarastoon($rivirow['hyllyalue'], $rivirow['hyllynro']);

          $query = "SELECT nimitys
                    FROM varastopaikat
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND tunnus  = '{$chk_var}'";
          $varastores = pupe_query($query);
          $varastorow = mysql_fetch_assoc($varastores);

          $hyllyalue_value = $varastorow['nimitys']." ".substr("{$rivirow['hyllyalue']}", $yhtiorow['varastontunniste']);
        }
        else {
          $hyllyalue_value = $rivirow['hyllyalue'];
        }

        echo "{$hyllyalue_value} {$rivirow['hyllynro']} {$rivirow['hyllyvali']} {$rivirow['hyllytaso']}";

        if ($excel_array_check) {
          $excel_array['rows'][$rivirow['rivitunnus']]["varastopaikka"] = "{$hyllyalue_value} {$rivirow['hyllynro']} {$rivirow['hyllyvali']} {$rivirow['hyllytaso']}";
        }
      }

      $selected_suuntalava = '';

      echo "<br>";

      $query = "(SELECT DISTINCT suuntalavat.tunnus, suuntalavat.sscc, suuntalavat.tila, suuntalavat.kaytettavyys
                 FROM suuntalavat
                 JOIN suuntalavat_saapuminen ON (suuntalavat_saapuminen.yhtio = suuntalavat.yhtio AND suuntalavat_saapuminen.suuntalava = suuntalavat.tunnus AND suuntalavat_saapuminen.saapuminen = '{$otunnus}')
                 WHERE suuntalavat.yhtio            = '{$kukarow['yhtio']}'
                 AND suuntalavat.keraysvyohyke = '{$rivirow['keraysvyohyke']}'
                 AND suuntalavat.tila               IN ('', 'S', 'P'))
                UNION
                (SELECT DISTINCT suuntalavat.tunnus, suuntalavat.sscc, suuntalavat.tila, suuntalavat.kaytettavyys
                 FROM suuntalavat
                 JOIN suuntalavat_saapuminen ON (suuntalavat_saapuminen.yhtio = suuntalavat.yhtio AND suuntalavat_saapuminen.suuntalava = suuntalavat.tunnus AND suuntalavat_saapuminen.saapuminen = '{$otunnus}')
                 WHERE suuntalavat.yhtio            = '{$kukarow['yhtio']}'
                 AND suuntalavat.usea_keraysvyohyke = 'K'
                 AND suuntalavat.tila               IN ('', 'S', 'P'))
                 UNION
                 (SELECT DISTINCT suuntalavat.tunnus, suuntalavat.sscc, suuntalavat.tila, suuntalavat.kaytettavyys
                 FROM suuntalavat
                 WHERE suuntalavat.yhtio            = '{$kukarow['yhtio']}'
                 AND suuntalavat.tila               IN ('', 'S', 'P')
                 AND suuntalavat.tunnus             = '{$rivirow['suuntalava']}')
                 UNION
                 (SELECT DISTINCT suuntalavat.tunnus, suuntalavat.sscc, suuntalavat.tila, suuntalavat.kaytettavyys
                 FROM suuntalavat
                 WHERE suuntalavat.yhtio            = '{$kukarow['yhtio']}'
                 AND suuntalavat.keraysvyohyke      = '{$rivirow['keraysvyohyke']}'
                 AND suuntalavat.tila               = ''
                 AND suuntalavat.kaytettavyys       = 'L')
                 UNION
                 (SELECT DISTINCT suuntalavat.tunnus, suuntalavat.sscc, suuntalavat.tila, suuntalavat.kaytettavyys
                 FROM suuntalavat
                 WHERE suuntalavat.yhtio            = '{$kukarow['yhtio']}'
                 AND suuntalavat.usea_keraysvyohyke = 'K'
                 AND suuntalavat.tila               IN ('', 'S', 'P')
                 AND suuntalavat.kaytettavyys       = 'L')
                 UNION
                 (SELECT DISTINCT suuntalavat.tunnus, suuntalavat.sscc, suuntalavat.tila, suuntalavat.kaytettavyys
                 FROM suuntalavat
                 JOIN suuntalavat_saapuminen ON (suuntalavat_saapuminen.yhtio = suuntalavat.yhtio AND suuntalavat_saapuminen.suuntalava = suuntalavat.tunnus AND suuntalavat_saapuminen.saapuminen = '{$otunnus}')
                 WHERE suuntalavat.yhtio            = '{$kukarow['yhtio']}'
                 AND suuntalavat.usea_keraysvyohyke = 'K'
                 AND suuntalavat.tila               IN ('', 'S', 'P')
                 AND suuntalavat.kaytettavyys       = 'Y'
                 AND suuntalavat.sscc               = 'Suoratoimitus')
                 ORDER BY sscc, tunnus";
      $suuntalavat_res = pupe_query($query);

      $disabled = $rivirow["varastossa_kpl"] != 0 ? " disabled='disabled' " : "";

      echo "<select name='suuntalava' id='suuntalava_{$rivirow['rivitunnus']}'{$disabled}><option value='0'>", t("Ei suuntalavaa"), "</option>";

      $suuntalavan_sscc = '';

      while ($suuntalavat_row = mysql_fetch_assoc($suuntalavat_res)) {

        if ($rivirow['suuntalava'] > 0 and $suuntalavat_row['tunnus'] != $rivirow['suuntalava'] and $suuntalavat_row['kaytettavyys'] != 'L' and $suuntalavat_row['tila'] != '') {
          continue;
        }

        $sel = '';

        if ($suuntalavat_row['tunnus'] == $rivirow['suuntalava']) {
          $sel = ' selected';
          $suuntalavan_sscc = $suuntalavat_row['sscc'];
          $selected_suuntalava = $suuntalavat_row['sscc'];

          if ($excel_array_check) {
            $excel_array['rows'][$rivirow['rivitunnus']]["suuntalava"] = $suuntalavat_row['sscc'];
          }
        }

        if ($suuntalavat_row['tila'] == 'P' and $rivirow["varastossa_kpl"] == 0) continue;

        $disabled = ($suuntalavat_row['tila'] == 'S') ? " disabled='disabled' " : '';

        echo "<option id='option_{$rivirow['rivitunnus']}_{$suuntalavat_row['tunnus']}' value='{$suuntalavat_row['tunnus']}'{$sel}{$disabled}>{$suuntalavat_row['sscc']}</option>";
      }

      echo "</select>";
      echo "</{$td}>";
    }
    elseif ($yhtiorow['suuntalavat'] == 'S') {
      echo "<{$td} {$classlisa}>";
      echo "</{$td}>";
    }
    else {
      if ($excel_array_check) {
        $_varastopaikka = "{$rivirow["hyllyalue"]} - ";
        $_varastopaikka .= "{$rivirow["hyllynro"]} - ";
        $_varastopaikka .= "{$rivirow["hyllyvali"]} - ";
        $_varastopaikka .= "{$rivirow["hyllytaso"]}";

        $excel_array['rows'][$rivirow['rivitunnus']]["varastopaikka"] = $_varastopaikka;
      }
    }

    //Tutkitaan tuotteiden lisävarusteita
    $query  = "SELECT tuote.tuoteno, tuote.nimitys, tuotteen_toimittajat.toim_tuoteno
               FROM tuoteperhe
               JOIN tuote ON (tuoteperhe.yhtio=tuote.yhtio and tuoteperhe.tuoteno=tuote.tuoteno and tuote.ei_saldoa = '')
                LEFT JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio=tuote.yhtio and tuotteen_toimittajat.tuoteno=tuote.tuoteno and tuotteen_toimittajat.liitostunnus='$toimittajaid')
               WHERE tuoteperhe.yhtio    = '$kukarow[yhtio]'
               AND tuoteperhe.isatuoteno = '$rivirow[tuoteno]'
               AND tuoteperhe.tyyppi     = 'L'
               ORDER BY tuoteperhe.tuoteno";
    $lisaresult = pupe_query($query);

    echo "<$td $classlisa>";

    if ($rivirow["varastossa_kpl"] == 0) {
      echo "<a href='$PHP_SELF?toiminto=kohdista&toimittajaid=$toimittajaid&tee=K&otunnus=$otunnus&nayta_kohdistetut=$nayta_kohdistetut&nayta_kaikki=$nayta_kaikki&riviid=$rivirow[rivitunnus]&tila=muokkaa_rivi'><img title='", t("Muokkaa"), "' alt='", t("Muokkaa"), "' src='{$palvelin2}pics/lullacons/document-properties.png'></a><br>";
    }

    if ((!isset($nayta_siirrettavat) or $nayta_siirrettavat == "") and $rivirow["rivitunnus"] == $rivirow["perheid2"] and mysql_num_rows($lisaresult) > 0) {
      echo "<a href='$PHP_SELF?toiminto=kohdista&toimittajaid=$toimittajaid&tee=K&otunnus=$otunnus&nayta_kohdistetut=$nayta_kohdistetut&nayta_kaikki=$nayta_kaikki&tila=$tila&lisaa_lisavarusteita=$rivirow[rivitunnus]'>".t("Lisävarusteet")."</a><br>";
    }

    if ((!isset($nayta_siirrettavat) or $nayta_siirrettavat == "") and ($sarjarow["sarjanumeroseuranta"] == "S" or $sarjarow["sarjanumeroseuranta"] == "U" or $sarjarow["sarjanumeroseuranta"] == "V") and ($rivirow["rivitunnus"] == $rivirow["perheid2"] or $rivirow["perheid2"] == 0)) {
      echo "<a href='$PHP_SELF?toiminto=kohdista&toimittajaid=$toimittajaid&tee=K&otunnus=$otunnus&nayta_kohdistetut=$nayta_kohdistetut&nayta_kaikki=$nayta_kaikki&tila=$tila&riviid=$rivirow[rivitunnus]&tila=uusirivi&perheid2=$rivirow[rivitunnus]'>".t("Liitä rivi")."</a>";
    }

    $hintapyoristys_hinta = hintapyoristys($rivirow['hinta'], $yhtiorow['hintapyoristys']);
    $hintapyoristys_ostohinta = hintapyoristys($rivirow['ostohinta'], $yhtiorow['hintapyoristys']);

    if ($rivirow['var2'] != 'O' and $rivirow['uusiotunnus'] != 0 and round(abs($hintapyoristys_hinta - $hintapyoristys_ostohinta), 2) > 0.02) {
      echo "<br />";
      echo t("Haluatko päivittää ostohinnan"), "? {$hintapyoristys_ostohinta} {$kulurow['valkoodi']} &rarr; {$hintapyoristys_hinta} {$kulurow['valkoodi']} ";

      for ($iix = 0; $iix < 2; $iix++) {

        if ($iix == 0) {
          $_title     = t("Päivitä");
          $_tila_toiminto = "yes";
          $_icon       = "alert.png";
          $_confirm_text  = t("Haluatko varmasti päivittää hinnaksi %.3f?", $kukarow['kieli'], $hintapyoristys_hinta);
        }
        else {
          $_title     = t("Älä päivitä");
          $_tila_toiminto = "no";
          $_icon       = "stop.png";
          $_confirm_text  = t("Hintaa ei päivitetä. Oletko varma?", $kukarow['kieli']);
        }

        echo "<a onclick=\"return confirm('{$_confirm_text}')\" href='?toiminto=kohdista&toimittajaid={$toimittajaid}&tee=K&otunnus={$otunnus}&nayta_kohdistetut={$nayta_kohdistetut}&nayta_kaikki={$nayta_kaikki}&riviid={$rivirow['rivitunnus']}&tila=paivita_ostohinta&tila_toiminto={$_tila_toiminto}'><img title='{$_title}' alt='{$_title}' src='{$palvelin2}pics/lullacons/{$_icon}'></a>&nbsp;&nbsp;";
      }
    }

    echo "</$td>";

    if ($yhtiorow['suuntalavat'] == 'S') {
      echo "<td style='visibility:hidden; display:none;'>{$selected_suuntalava}</td>";
    }

    if (mysql_num_rows($lisaresult) > 0 and ($rivirow["perheid2"] == 0 or (isset($lisaa_lisavarusteita) and $lisaa_lisavarusteita > 0))) {

      echo "</tr>\n";

      while ($lisavar_row = mysql_fetch_assoc($lisaresult)) {
        echo "<tr>
            <$td class='back'></$td>
            <$td class='back'></$td>
            <$td>$lisavar_row[tuoteno]<br>$lisavar_row[toim_tuoteno]</$td>";

        echo "<$td>".t_tuotteen_avainsanat($lisavar_row, 'nimitys')."</$td>";
        echo "<$td align='right'><input size='5' type='text' name='lisavarusterivi_kpl[$rivirow[rivitunnus]##$lisavar_row[tuoteno]#*#$rivirow[otunnus]]' style='text-align:right'></$td>";
        echo "<$td align='right'><input size='7' type='text' name='lisavarusterivi_hinta[$rivirow[rivitunnus]##$lisavar_row[tuoteno]#*#$rivirow[otunnus]]' style='text-align:right'><br>$kulurow[valkoodi]</$td>";
        echo "<$td class='back'></$td>
              <$td class='back'></$td>
              <$td class='back'></$td>
              <$td class='back'></$td>";
        echo "</tr>";
      }
    }
    else {
      echo "</tr>";
    }
  }

  echo "</tbody>";

  if (isset($nayta_siirrettavat) and $nayta_siirrettavat == "YES") {
    echo "</table>";
    echo "<input type='submit' value='".t("Tallenna muutokset")."'>";
    echo "</form>";
  }
  else {
    echo "</table>";
    echo "</form>";

    echo "<br /><br />";

    echo "<form method='post'>
        <input type='hidden' name='toimittajaid' value='$toimittajaid'>
        <input type='hidden' name='toiminto' value='kohdista'>
        <input type='hidden' name='tila' value='uusirivi'>
        <input type='hidden' name='tee' value='K'>
        <input type='hidden' name='nayta_kohdistetut' value='$nayta_kohdistetut'>
        <input type='hidden' name='nayta_kaikki' value='$nayta_kaikki'>
        <input type='hidden' name='otunnus' value='$otunnus'>
        <input type='hidden' name='toimipaikka' value='{$toimipaikka}'>
        <input type='submit' value='".t("Tee uusi rivi")."'>
        </form>";

    echo "<form name='formi' method='post'>
        <input type='hidden' name='toimittajaid' value='$toimittajaid'>
        <input type='hidden' name='toiminto' value='kohdista'>
        <input type='hidden' name='otunnus' value='$otunnus'>
        <input type='hidden' name='tee' value='$tee'>
        <input type='hidden' name='tila' value='FAILISTA'>
        <input type='submit' value='".t("Kohdista tiedostosta")."'>
        </form>";

    echo "<form method='post'>
        <input type='hidden' name='toimittajaid' value='$toimittajaid'>
        <input type='hidden' name='toiminto' value='kohdista'>
        <input type='hidden' name='tila' value=''>
        <input type='hidden' name='nayta_siirrettavat' value='YES'>
        <input type='hidden' name='tee' value='$tee'>
        <input type='hidden' name='nayta_kohdistetut' value='$nayta_kohdistetut'>
        <input type='hidden' name='nayta_kaikki' value='$nayta_kaikki'>
        <input type='hidden' name='otunnus' value='$otunnus'>
        <input type='hidden' name='toimipaikka' value='{$toimipaikka}'>
        <input type='submit' value='".t("Siirrä rivejä toiselta saapumiselta")."'>
        </form>";

    echo "<form method='post'>
        <input type='submit' value='".t("Kohdistus valmis")."'>
        <input type='hidden' name='toimittajaid' value='$toimittajaid'>
        <input type='hidden' name='toiminto' value='kohdista'>
        <input type='hidden' name='laskunsumma' value='$laskunsumma'>
        <input type='hidden' name='tee' value='K'>
        <input type='hidden' name='tila' value='valmis'>
        <input type='hidden' name='otunnus' value='$otunnus'>
        <input type='hidden' name='toimipaikka' value='{$toimipaikka}'>
        </form>";

    echo "<form method='post' action='keikka.php#ankkuri'>";
    echo "<input type='hidden' name='toimittajaid' value='{$toimittajaid}'>";
    echo "<input type='hidden' name='toiminto' value='kohdista'>";
    echo "<input type='hidden' name='tila' value=''>";
    echo "<input type='hidden' name='tee' value='excel'>";
    echo "<input type='hidden' name='nayta_kohdistetut' value='{$nayta_kohdistetut}'>";
    echo "<input type='hidden' name='nayta_kaikki' value='{$nayta_kaikki}'>";
    echo "<input type='hidden' name='otunnus' value='{$otunnus}'>";
    echo "<input type='hidden' name='toimipaikka' value='{$toimipaikka}'>";
    echo "<input type='submit' value='".t("Luo Excel")."'>";
    echo "</form>";
  }

  if ($tee == 'excel') {
    include 'inc/pupeExcel.inc';

    $worksheet   = new pupeExcel();
    $format_bold = array("bold" => TRUE);
    $excelrivi   = 0;
    $excelsarake = 0;

    // jos kululaskua ei ole vielä liitetty, oletetaan että käytetään toimittajan oletus valuuttaa
    if (empty($excel_array['kulurow']["valkoodi"])) {
      $_excl_valkoodi = $excel_array['laskurow']["valkoodi"];
    }
    else {
      $_excl_valkoodi = $excel_array['kulurow']['valkoodi'];
    }

    $_excl_laskunro = $excel_array['laskurow']['laskunro'];

    $worksheet->writeString(0, 0, t("Saapuminen"), $format_bold);
    $worksheet->writeString(0, 1, $_excl_laskunro);

    $worksheet->writeString(1, 0, t("Toimittaja"), $format_bold);
    $worksheet->writeString(1, 1, $excel_array['laskurow']['nimi']);

    $worksheet->writeString(2, 0, t("Liitetyt laskut"), $format_bold);
    $worksheet->writeString(2, 1, implode(", ", $excel_array['liitetyt_laskut']));

    $worksheet->writeString(3, 0, t("Erikoisale"), $format_bold);
    $worksheet->writeString(3, 1, $excel_array['laskurow']['erikoisale_saapuminen']);

    $worksheet->writeString(0, 3, t("Laskun summa"), $format_bold);
    $worksheet->writeString(0, 4, $excel_array['kulurow']['summa']." ".$_excl_valkoodi);

    $worksheet->writeString(1, 3, t("Laskun kulut"), $format_bold);
    $_excl_osto_kulut = $excel_array['kulurow']['osto_kulut'];
    $worksheet->writeString(1, 4, sprintf("%.2f", $_excl_osto_kulut)." ".$_excl_valkoodi);

    $worksheet->writeString(2, 3, t("Eturahti"), $format_bold);
    $_excl_rahti_etu = $excel_array['laskurow']["rahti_etu"];
    $worksheet->writeString(2, 4, sprintf("%.2f", $_excl_rahti_etu)." ".$_excl_valkoodi);

    $worksheet->writeString(3, 3, t("Pyöristyserot"), $format_bold);
    $_excl_pyoristys_erot = $excel_array['laskurow']["pyoristys_erot"];
    $worksheet->writeString(3, 4, sprintf("%.2f", $_excl_pyoristys_erot)." ".$_excl_valkoodi);

    $worksheet->writeString(4, 3, t("Kohdistettava summa"), $format_bold);
    $worksheet->writeString(4, 4, $excel_array['kohdistettava_summa']." ".$_excl_valkoodi);

    $worksheet->writeString(5, 3, t("Kohdistettu summa"), $format_bold);
    $worksheet->writeString(5, 4, $excel_array['valittusumma']." ".$kulurow['valkoodi']);

    $worksheet->writeString(6, 3, t("Jäljellä"), $format_bold);
    $worksheet->writeString(6, 4, $excel_array['laskunsumma']." ".$kulurow['valkoodi']);

    $suodatusvalinta = array(
      '' => t("Perusnäkymä"),
      'jees' => t("Näytä kaikki kohdistetut"),
      'eitod' => t("Näytä vain kohdistamattomat"),
    );

    $worksheet->writeString(0, 6, t("Suodatusvalinta"), $format_bold);
    $worksheet->writeString(0, 7, $suodatusvalinta[$nayta_kohdistetut]);

    if (!empty($toimipaikka)) {
      $_toimipaikka_res = hae_yhtion_toimipaikat($kukarow['yhtio'], $toimipaikka);
      $_toimipaikka = mysql_fetch_assoc($_toimipaikka_res);
    }
    else {
      $_toimipaikka['nimi'] = "";
    }

    $worksheet->writeString(1, 6, t("Toimipaikka"), $format_bold);
    $worksheet->writeString(1, 7, $_toimipaikka['nimi']);

    $worksheet->writeString(2, 6, t("Rivejä"), $format_bold);
    $worksheet->writeString(2, 7, $excel_array['rivit']);

    $worksheet->writeString(3, 6, t("Kappaleita"), $format_bold);
    $worksheet->writeString(3, 7, (float) $excel_array['kappaleet']);

    $worksheet->writeString(8, 0, t("Kohdistus-status"), $format_bold);
    $worksheet->writeString(8, 1, t("Tilaus"), $format_bold);
    $worksheet->writeString(8, 2, t("Tuoteno"), $format_bold);
    $worksheet->writeString(8, 3, t("Toimittajan tuoteno"), $format_bold);
    $worksheet->writeString(8, 4, t("Nimitys"), $format_bold);
    $worksheet->writeString(8, 5, t("Määrä"), $format_bold);
    $worksheet->writeString(8, 6, t("Määrä / Ulk"), $format_bold);
    $worksheet->writeString(8, 7, t("Hinta"), $format_bold);
    $worksheet->writeString(8, 8, t("Valuutta"), $format_bold);

    $excelsarake = 9;

    for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
      $worksheet->writeString(8, $excelsarake, t("Ale{$alepostfix}"), $format_bold);
      $excelsarake++;
    }

    $worksheet->writeString(8, $excelsarake, t("Alv"), $format_bold);
    $excelsarake++;
    $worksheet->writeString(8, $excelsarake, t("Erikoisale"), $format_bold);
    $excelsarake++;
    $worksheet->writeString(8, $excelsarake, t("Rivihinta"), $format_bold);
    $excelsarake++;
    $worksheet->writeString(8, $excelsarake, t("+Alv / -Ale"), $format_bold);
    $excelsarake++;
    $worksheet->writeString(8, $excelsarake, t("Kommentti"), $format_bold);
    $excelsarake++;
    $worksheet->writeString(8, $excelsarake, t("Toim.aika"), $format_bold);
    $excelsarake++;
    $worksheet->writeString(8, $excelsarake, t("Varastopaikka"), $format_bold);
    $excelsarake++;
    if ($yhtiorow['suuntalavat'] == 'S') {
      $worksheet->writeString(8, $excelsarake, t("Suuntalava"), $format_bold);
    }

    $excelrivi = 9;
    $excelsarake = 0;

    foreach ($excel_array['rows'] as $_row_tunnus => $_row) {
      $worksheet->writeString($excelrivi, $excelsarake, $_row['kohdistus']);
      $excelsarake++;

      $worksheet->writeString($excelrivi, $excelsarake, $_row['tilaus']);
      $excelsarake++;

      $worksheet->writeString($excelrivi, $excelsarake, $_row['tuoteno']);
      $excelsarake++;

      $worksheet->writeString($excelrivi, $excelsarake, $_row['toim_tuoteno']);
      $excelsarake++;

      $worksheet->writeString($excelrivi, $excelsarake, $_row['nimitys']);
      $excelsarake++;

      $worksheet->writeString($excelrivi, $excelsarake, str_replace(".", ",", $_row['maara']));
      $excelsarake++;

      $worksheet->writeString($excelrivi, $excelsarake, str_replace(".", ",", $_row['maara_ulk']));
      $excelsarake++;

      $worksheet->writeString($excelrivi, $excelsarake, str_replace(".", ",", $_row['hinta']));
      $excelsarake++;

      $worksheet->writeString($excelrivi, $excelsarake, $_row['valuutta']);
      $excelsarake++;

      for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
        $worksheet->writeString($excelrivi, $excelsarake, str_replace(".", ",", $_row["ale{$alepostfix}"]));
        $excelsarake++;
      }

      $worksheet->writeString($excelrivi, $excelsarake, str_replace(".", ",", $_row['alv']));
      $excelsarake++;

      $worksheet->writeString($excelrivi, $excelsarake, str_replace(".", ",", $_row['erikoisale']));
      $excelsarake++;

      $worksheet->writeString($excelrivi, $excelsarake, str_replace(".", ",", $_row['rivihinta']));
      $excelsarake++;

      $worksheet->writeString($excelrivi, $excelsarake, str_replace(".", ",", $_row['alerivihinta']));
      $excelsarake++;

      $worksheet->writeString($excelrivi, $excelsarake, trim(str_replace("<br>", " ", $_row['kommentti'])));
      $excelsarake++;

      $worksheet->writeString($excelrivi, $excelsarake, $_row['toimaika']);
      $excelsarake++;

      $worksheet->writeString($excelrivi, $excelsarake, $_row['varastopaikka']);
      $excelsarake++;

      if ($yhtiorow['suuntalavat'] == 'S') {
        $worksheet->writeString($excelrivi, $excelsarake, $_row['suuntalava']);
        $excelsarake++;
      }

      $excelsarake = 0;
      $excelrivi++;
    }

    $tmpfilenimi = $worksheet->close();

    echo "<form method='post'>";
    echo "<input type='hidden' name='toimittajaid' value='{$toimittajaid}'>";
    echo "<input type='hidden' name='toiminto' value='kohdista'>";
    echo "<input type='hidden' name='tila' value=''>";
    echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
    echo "<input type='hidden' name='nayta_kohdistetut' value='{$nayta_kohdistetut}'>";
    echo "<input type='hidden' name='nayta_kaikki' value='{$nayta_kaikki}'>";
    echo "<input type='hidden' name='otunnus' value='{$otunnus}'>";
    echo "<input type='hidden' name='toimipaikka' value='{$toimipaikka}'>";
    echo "<input type='hidden' name='kaunisnimi' value='saapuminen_{$_excl_laskunro}.xlsx'>";
    echo "<input type='hidden' name='tmpfilenimi' value='${tmpfilenimi}'>";
    echo "<a name='ankkuri'>";
    echo "<input type='submit' value='".t("Tallenna Excel")."'>";
    echo "</a>";
    echo "</form>";
  }

  $tila = "eityhja";
}
