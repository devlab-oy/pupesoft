<?php

//Sisään haluamme
// $otunnus jossa on kopioitavan tilauksen tunnus
// $tilrivilisa muuttujalla voidaan rajailla mitä tilausrivejä kopsataan
// $syot_varasto jos halutaan myydä tietystä varastosta
// $syot_var voidaan ohjata var-kenttää

if (!function_exists("tilauksesta_myyntitilaus")) {
  function tilauksesta_myyntitilaus($otunnus, $tilrivilisa, $syot_varasto, $syot_var, $varataan_saldoa = "", $kopioi_paikat = "", $perusta="") {
    global $yhtiorow, $kukarow;

    $tilauksesta_myyntitilaus = "";

    $query = "SELECT *
              FROM tilausrivi
              WHERE yhtio  = '$kukarow[yhtio]'
              and otunnus  = '$otunnus'
              and tyyppi  != 'D'
              $tilrivilisa";
    $copresult = pupe_query($query);

    if (mysql_num_rows($copresult) == 0) {
      $tilauksesta_myyntitilaus = t("Tarjouksella ei ollut yhtään laskutettavaa riviä");
      return;
    }

    // Napataan toimittajan/asiakkaan tunnus
    if (substr($perusta, 0, 11) == "REKLAMAATIO") {
      list($perusta, $asiakasjollepalautetaan) = explode("#", $perusta);
    }

    //Kopioidaan otsikko ja siirretään rivit uudelle otsikolle
    $query = "SELECT * FROM lasku WHERE tunnus='$otunnus' and yhtio ='$kukarow[yhtio]'";
    $monistares = pupe_query($query);
    $monistarow = mysql_fetch_array($monistares);

    $tehdaan_uusi_otsikko = TRUE;

    // Katsotaan löytyykö jo sopiva otsikko, jos ei niin tehdään uusi, eli tässä tapauksessa ei kopsata vanhaa
    if ($perusta == "REKLAMAATIO") {
      $query = "SELECT lasku.*
                FROM lasku
                WHERE yhtio           = '$kukarow[yhtio]'
                and tila              = 'N'
                and alatila           in ('','A')
                and yhtio_toimipaikka = '{$kukarow['toimipaikka']}'
                and liitostunnus      = '{$asiakasjollepalautetaan}'
                and tilaustyyppi      = '9'
                ORDER BY luontiaika DESC
                LIMIT 1";
      $stresult = pupe_query($query);

      if (mysql_num_rows($stresult) == 1) {
        $strow = mysql_fetch_assoc($stresult);
        $utunnus = $strow['tunnus'];
        $tehdaan_uusi_otsikko = FALSE;
      }
      else {
        require "tilauskasittely/luo_myyntitilausotsikko.inc";

        $kukarowkesken      = $kukarow["kesken"];
        $kukarow['kesken'] = 0;

        $utunnus = luo_myyntitilausotsikko("RIVISYOTTO", $asiakasjollepalautetaan, '', '', '', '', '', 9);

        $kukarow['kesken']    = $kukarowkesken;
        $tehdaan_uusi_otsikko = FALSE;
      }
    }

    if ($tehdaan_uusi_otsikko) {

      $fields = mysql_field_name($monistares, 0);
      $values = "'".$monistarow[0]."'";

      for ($i=1; $i < mysql_num_fields($monistares)-1; $i++) { // Ei monisteta tunnusta

        $fields .= ", ".mysql_field_name($monistares, $i);

        switch (mysql_field_name($monistares, $i)) {
        case 'luontiaika':
          $values .= ", now()";
          break;
        case 'alatila':
          $values .= ", ''";
          break;
        case 'tila':
          if ($perusta == "TYOMAARAYS") {
            $values .= ", 'A'";
          }
          else {
            $values .= ", 'N'";
          }
          break;
        case 'tilaustyyppi':
          if ($perusta == "TYOMAARAYS") {
            $values .= ", 'A'";
          }
          elseif ($monistarow["tila"] == "T") {
            $values .= ", 'T'";
          }
          else {
            $values .= ", 'N'";
          }
          break;
        case 'clearing':
          if ($monistarow["tila"] == "T") {
            $values .= ", 'TARJOUSTILAUS'";
          }
          else {
            $values .= ", ''";
          }
          break;
        case 'tunnus':
        case 'kapvm':
        case 'tapvm':
        case 'olmapvm':
        case 'summa':
        case 'kasumma':
        case 'kate':
        case 'arvo':
        case 'maksuaika':
        case 'lahetepvm':
        case 'viite':
        case 'laskunro':
        case 'mapvm':
        case 'viikorkoeur':
        case 'tullausnumero':
        case 'laskutuspvm':
        case 'erpcm':
        case 'laskuttaja':
        case 'laskutettu':
        case 'maksaja':
        case 'maksettu':
        case 'maa_maara':
        case 'kuljetusmuoto':
        case 'kauppatapahtuman_luonne':
        case 'sisamaan_kuljetus':
        case 'sisamaan_kuljetusmuoto':
        case 'poistumistoimipaikka':
        case 'poistumistoimipaikka_koodi':
          $values .= ", ''";
          break;
        case 'laatija':
          $values .= ", '$kukarow[kuka]'";
          break;
        case 'kate_korjattu':
        case 'lahetetty_ulkoiseen_varastoon':
          $values .= ", NULL";
          break;
        case 'kerayspvm':
          if ($monistarow[$i] > date("Y-m-d G:i:s")) {
            $values .= ", '".$monistarow[$i]."'";
          }
          else {
            $values .= ", now()";
          }
          break;
        case 'toimaika':
          if ($monistarow[$i] > date("Y-m-d")) {
            $values .= ", '".$monistarow[$i]."'";
          }
          else {
            $values .= ", now()";
          }
          break;
        default:
          $values .= ", '".$monistarow[$i]."'";
        }
      }

      /*
        Vähän glungea taas.. jos halutaan perustaa tarjous insertoidaan ensin pelkkä perusotsikko ja sitten tämä toimitusotsikko
        Korjataan projektiotsikon tiedot insertin jälkeen..
      */

      $tunnusnippu=$ptunnus=0;

      if ($perusta == "PROJEKTI" and $monistarow["tunnusnippu"] > 0 and $monistarow["tila"] == "T") {
        $kysely  = "INSERT into lasku ($fields) VALUES ($values)";
        $insres  = pupe_query($kysely);
        $ptunnus = mysql_insert_id($GLOBALS["masterlink"]);

        $kysely = "UPDATE lasku SET tunnusnippu='$ptunnus', tila='R', alatila='' WHERE yhtio='$kukarow[yhtio]' and tunnus='$ptunnus'";
        $updres=pupe_query($kysely);

        // avataan projekti kirjanpitoon
        $query = " INSERT into kustannuspaikka set yhtio  = '$kukarow[yhtio]', nimi = '$ptunnus - $monistarow[nimi]', tyyppi = 'P', kaytossa = 'o', luontiaika = now(), laatija = '$kukarow[kuka]'";
        $result = pupe_query($query);
        $projekti = mysql_insert_id($GLOBALS["masterlink"]);

        $tilauksesta_myyntitilaus = "<font class='message'>".t("PERUSTETTIIN PROJEKTI").": $ptunnus</font><br><br>";
      }

      $kysely  = "INSERT into lasku ($fields) VALUES ($values)";
      $insres  = pupe_query($kysely);
      $utunnus = mysql_insert_id($GLOBALS["masterlink"]);

      if ($perusta == "TYOMAARAYS") {
        // Kopioidaan työmääräys
        $query = "SELECT * FROM tyomaarays WHERE otunnus='$otunnus' and yhtio ='$kukarow[yhtio]'";
        $monistatyomres = pupe_query($query);
        $monistatyomrow = mysql_fetch_array($monistatyomres);

        $fields = mysql_field_name($monistatyomres, 0);
        $values = "'".$monistatyomrow[0]."'";

        for ($i=1; $i < mysql_num_fields($monistatyomres); $i++) { // Ei monisteta tunnusta

          $fields .= ", ".mysql_field_name($monistatyomres, $i);

          switch (mysql_field_name($monistatyomres, $i)) {
          case 'luontiaika':
            $values .= ", now()";
            break;
          case 'otunnus':
            $values .= ", $utunnus";
            break;
          case 'laatija':
            $values .= ", '$kukarow[kuka]'";
            break;
          default:
            $values .= ", '".$monistatyomrow[$i]."'";
          }
        }

        $kysely  = "INSERT into tyomaarays ($fields) VALUES ($values)";
        $insres  = pupe_query($kysely);
      }

      /*
        Tää voi olla hassua, mutta näin tämä haluttiin!
        Eli liitetään hyväksytty tarjous uuteen tunnusnippuun eikä jatketa vanhaa.
        Tämä siksi, että tarjous on laitettu vetämään ehkä puoli vuotta aiemmin ja silloin tämä uusi tilaus ei mene raporteissa "loogisessa" järjestyksessä..
      */

      if ($monistarow["tunnusnippu"] > 0 and $monistarow["tila"] == "T") {
        if ($ptunnus > 0) {
          $tunnusnippu=$ptunnus;
        }
        else {
          $tunnusnippu=$utunnus;
        }

        $query = "UPDATE lasku set tunnusnippu = '$tunnusnippu' WHERE yhtio = '$kukarow[yhtio]' and tunnus='$utunnus'";
        $updres = pupe_query($query);
      }

      //Kopioidaan otsikon lisatiedot
      $query = "SELECT * FROM laskun_lisatiedot WHERE otunnus='$otunnus' and yhtio ='$kukarow[yhtio]'";
      $monistares2 = pupe_query($query);
      $monistarow2 = mysql_fetch_array($monistares2);

      $fields = mysql_field_name($monistares2, 0);
      $values = "'".$monistarow2[0]."'";

      //  Samat tiedot tarvitaan myös projektill
      $pvalues = "'".$monistarow2[0]."'";

      for ($i=1; $i < mysql_num_fields($monistares2)-1; $i++) { // Ei monisteta tunnusta

        $fields .= ", ".mysql_field_name($monistares2, $i);

        switch (mysql_field_name($monistares2, $i)) {
        case 'otunnus':
          $values .= ", '$utunnus'";
          $pvalues .= ", '$ptunnus'";
          break;
        case 'tunnusnippu_tarjous':
          $values .= ", '$monistarow[tunnusnippu]'";
          $pvalues .= ", '$monistarow[tunnusnippu]'";
          break;
        case 'projekti':
          $values .= ", '$projekti'";
          $pvalues .= ", '$projekti'";
          break;
        default:
          $values .= ", '".$monistarow2[$i]."'";
          $pvalues .= ", '".$monistarow2[$i]."'";
        }
      }

      $kysely  = "INSERT into laskun_lisatiedot ($fields) VALUES ($values)";
      $insres2 = pupe_query($kysely);

      if ($ptunnus > 0) {
        $kysely  = "INSERT into laskun_lisatiedot ($fields) VALUES ($pvalues)";
        $insres2 = pupe_query($kysely);
      }

      //  Projektin maksusuunnitelma kuuluu projektiotsikolle, ei tilaukselle
      if ($ptunnus > 0) {
        $mtunnus = $ptunnus;
      }
      else {
        $mtunnus = $utunnus;
      }

      //Siirretään maksusuunnitelma
      $query = "SELECT *
                FROM maksupositio
                WHERE yhtio = '$kukarow[yhtio]'
                and otunnus = '$otunnus'";
      $copresult = pupe_query($query);

      if (mysql_num_rows($copresult) > 0) {
        $query = "UPDATE maksupositio
                  SET otunnus = '$mtunnus'
                  WHERE yhtio = '$kukarow[yhtio]'
                  and otunnus = '$otunnus'";
        $result = pupe_query($query);

        //Päivitetään lasku
        $query = "UPDATE lasku
                  SET jaksotettu   = '$mtunnus'
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tunnus  = '$utunnus'";
        $result = pupe_query($query);

        //  Päivitetään projekti
        if ($ptunnus > 0) {
          $query = "UPDATE lasku
                    SET jaksotettu   = '$mtunnus'
                    WHERE yhtio = '$kukarow[yhtio]'
                    and tunnus  = '$ptunnus'";
          $result = pupe_query($query);
        }
      }

      //Kopioidaan liitetiedostot
      $query = "SELECT *
                from liitetiedostot
                where yhtio      = '$kukarow[yhtio]'
                and liitostunnus = '$otunnus'
                and liitos       = 'lasku'";
      $liiteres = pupe_query($query);

      if (mysql_num_rows($liiteres) > 0) {

        while ($liiterow = mysql_fetch_assoc($liiteres)) {

          $liiterow["data"] = mysql_real_escape_string($liiterow["data"]);

          $query = "INSERT INTO liitetiedostot SET
                    yhtio           = '$kukarow[yhtio]',
                    liitos          = '$liiterow[liitos]',
                    liitostunnus    = '$utunnus',
                    data            = '$liiterow[data]',
                    selite          = '$liiterow[selite]',
                    kieli           = '$liiterow[kieli]',
                    filename        = '$liiterow[filename]',
                    filesize        = '$liiterow[filesize]',
                    filetype        = '$liiterow[filetype]',
                    image_width     = '$liiterow[image_width]',
                    image_height    = '$liiterow[image_height]',
                    image_bits      = '$liiterow[image_bits]',
                    image_channels  = '$liiterow[image_channels]',
                    kayttotarkoitus = '$liiterow[kayttotarkoitus]',
                    jarjestys       = '$liiterow[jarjestys]',
                    laatija         = '$kukarow[kuka]',
                    luontiaika      = now()";
          $liiteinsres = pupe_query($query);
        }
      }
    }

    //Siirretään tilausrivit
    $kukarowkesken     = $kukarow["kesken"];
    $kukarow["kesken"]  = $utunnus;
    $varataan_sa_revert = FALSE;
    $isaok        = array();

    if ($perusta == "TYOMAARAYS") {
      $tymlisa    = t("työmääräykselle");
      $linkkilisa = "<a href='tilaus_myynti.php?toim=TYOMAARAYS&tilausnumero=$utunnus'>".t("Siirry myyntitilaukselle")."</a>";
    }
    else {
      $tymlisa    = t("myyntitilaukselle");
      $linkkilisa = "<a href='tilaus_myynti.php?toim=PIKATILAUS&tilausnumero=$utunnus'>".t("Siirry myyntitilaukselle")."</a>";
    }

    $tilauksesta_myyntitilaus .= "<font class='message'>".t("Lisätään valitut rivit")." $tymlisa: $utunnus</font><br>";

    $query    = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$utunnus'";
    $laskures = pupe_query($query);
    $laskurow = mysql_fetch_array($laskures);

    $_ALVREKULK = FALSE;

    // Jos meillä on lasku menossa ulkomaille
    // Tietyissä tapauksissa yhtiorow maa voi olla sama kuin laskun maa, mutta vat_numerosta tiedetään,
    // että ollaan verovelsollisia tähä maahan
    if ((isset($yhtiorow["vat_numero"]) and $yhtiorow["vat_numero"] != "") or ($laskurow["maa"] != "" and $laskurow["maa"] != $yhtiorow["maa"])) {

      // tutkitaan ollaanko siellä alv-rekisteröity
      $query = "SELECT * from yhtion_toimipaikat where yhtio='$kukarow[yhtio]' and maa='$laskurow[maa]' and vat_numero != ''";
      $alhire = pupe_query($query);

      // ollaan alv-rekisteröity, haetaan tuotteelle oikea ALV
      if (mysql_num_rows($alhire) == 1) {
        $_ALVREKULK = TRUE;
      }
    }

    $query = "SELECT tilausrivi.*, tilausrivin_lisatiedot.positio, tilausrivin_lisatiedot.toimittajan_tunnus
              FROM tilausrivi
              LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
              WHERE tilausrivi.yhtio  = '$kukarow[yhtio]'
              and tilausrivi.otunnus  = '$otunnus'
              and tilausrivi.tyyppi  != 'D'
              $tilrivilisa
              ORDER by tilausrivi.perheid2, tilausrivi.perheid, tilausrivi.tunnus";
    $copresult = pupe_query($query);

    while ($coprivirow = mysql_fetch_array($copresult)) {

      $query = "SELECT *
                FROM tuote
                WHERE yhtio  = '$kukarow[yhtio]'
                and  tuoteno = '$coprivirow[tuoteno]'";
      $aresult = pupe_query($query);
      $tuoterow = mysql_fetch_assoc($aresult);

      $tuoteno = $coprivirow['tuoteno'];

      if ($coprivirow["var"] == "J" or $coprivirow["var"] == "S" or $coprivirow["var"] == "T" or $coprivirow["var"] == "U") {
        if ($yhtiorow["varaako_jt_saldoa"] == "") {
          $kpl = $coprivirow['jt'];
        }
        else {
          $kpl = $coprivirow['jt']+$coprivirow['varattu'];
        }

        $var  = "J";
      }
      elseif ($coprivirow["var"] == "P") {
        $kpl  = $coprivirow['tilkpl'];
        $var  = "";
      }
      else {
        $kpl  = $coprivirow['varattu'];
        $var  = "";
      }

      $tuotealv = $tuoterow["alv"];

      // ollaan alv-rekisteröity, haetaan tuotteelle oikea ALV
      if ($_ALVREKULK) {
        $query = "SELECT * from tuotteen_alv where yhtio='$kukarow[yhtio]' and maa='$laskurow[maa]' and tuoteno='$coprivirow[tuoteno]' limit 1";
        $alhire = pupe_query($query);
        $alehi_alrow = mysql_fetch_assoc($alhire);
        $tuotealv = $alehi_alrow["alv"];
      }

      if ($tuotealv != $coprivirow["alv"] and $yhtiorow["alv_kasittely"] == "" and $coprivirow["alv"] < 500) {
        $hinta = hintapyoristys($coprivirow["hinta"] / (1+$coprivirow['alv']/100) * (1+$tuotealv/100));
      }
      else {
        $hinta = $coprivirow['hinta'];
      }

      // jos käytössä on myyntihinnan poikkeava määrä, kerrotaan hinta takaisin kuntoon.
      if ($tuoterow["myyntihinta_maara"] != 0) {
        $hinta = $hinta * $tuoterow["myyntihinta_maara"];
      }

      $netto    = $coprivirow['netto'];

      for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
        ${'ale'.$alepostfix} = $coprivirow["ale{$alepostfix}"];
      }

      $kommentti  = $coprivirow['kommentti'];

      if ($coprivirow['kerayspvm'] >= date("Y-m-d")) {
        $kerayspvm = $coprivirow['kerayspvm'];
      }
      else {
        $kerayspvm = date("Y-m-d");
      }

      if ($coprivirow['toimaika'] >= date("Y-m-d")) {
        $toimaika = $coprivirow['toimaika'];
      }
      else {
        $toimaika = date("Y-m-d");
      }

      $alv    = $coprivirow['alv'];
      $ytunnus  = $laskurow["ytunnus"];
      $rivinumero = $coprivirow['tilaajanrivinro'];

      $perheid  = $coprivirow['perheid'];
      $perheid2  = $coprivirow['perheid2'];

      if ($perusta == "REKLAMAATIO") {
        $kpl = $kpl * -1;

        $query  = "SELECT *
                   FROM asiakas
                   WHERE yhtio = '$kukarow[yhtio]'
                   and tunnus  = '$asiakasjollepalautetaan'";
        $result = pupe_query($query);
        $asiakasrow = mysql_fetch_assoc($result);

        $query = "SELECT toimi.tunnus
                  FROM toimi
                  JOIN tuotteen_toimittajat ON (toimi.yhtio = tuotteen_toimittajat.yhtio AND toimi.tunnus = tuotteen_toimittajat.liitostunnus AND tuotteen_toimittajat.tuoteno = '{$tuoteno}')
                  WHERE toimi.yhtio  = '$kukarow[yhtio]'
                  AND toimi.tyyppi  != 'P'
                  AND toimi.ytunnus  = '{$asiakasrow['ytunnus']}'
                  LIMIT 1";
        $result = pupe_query($query);
        $toimirow = mysql_fetch_assoc($result);

        $ostorow = array();
        $ostorow["liitostunnus"] = $toimirow['tunnus'];
        $ostorow["valkoodi"]    = $toimirow['valkoodi'];

        $vquery = "SELECT nimi, kurssi, tunnus
                   FROM valuu
                   WHERE yhtio = '$kukarow[yhtio]'
                   and nimi    = '{$toimirow['valkoodi']}'";
        $vresult = pupe_query($vquery);

        if (mysql_num_rows($vresult) == 1) {
          $vrow = mysql_fetch_assoc($vresult);
          $ostorow["vienti_kurssi"] = $vrow['kurssi'];
        }
        else {
          $ostorow["vienti_kurssi"] = 1;
        }

        $ostorow["ytunnus"] = $toimirow['ytunnus'];

        list($lis_hinta, $lis_netto, $lis_ale_kaikki, $alehinta_val) = alehinta_osto($ostorow, $tuoterow, $kpl, "", "", "");

        $hinta = $lis_hinta;
        $netto = $lis_netto;

        for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
          ${'ale'.$alepostfix} = $lis_ale_kaikki['ale'.$alepostfix];
        }

        $varataan_saldoa = "EI";
      }

      if ($coprivirow['jaksotettu'] != 0) {
        $jaksotettu = $mtunnus;
      }
      else {
        $jaksotettu  = 0;
      }

      $tuotenimitys = $coprivirow['nimitys'];

      $paikat    = "";
      $rivitunnus = "";

      if ($syot_var != "") {
        $var  = $syot_var;
      }

      if ((int) $syot_varasto > 0) {
        $varasto = $syot_varasto;
      }
      elseif ($laskurow["varasto"] > 0) {
        $varasto = $laskurow["varasto"];
      }
      else {
        $varasto = "";
      }

      if (($coprivirow["var"] == "T" or $coprivirow["var"] == "U") and $coprivirow['toimittajan_tunnus'] != 0) {
        if ($coprivirow['var'] == 'T') {
          $paikka = "¡¡¡{$coprivirow['toimittajan_tunnus']}";
        }
        else {
          $paikka = "!!!{$coprivirow['toimittajan_tunnus']}";
        }
      }
      elseif ($kopioi_paikat != "") {
        $paikka  = $coprivirow['hyllyalue']."#!¡!#".$coprivirow['hyllynro']."#!¡!#".$coprivirow['hyllyvali']."#!¡!#".$coprivirow['hyllytaso'];
      }
      else {
        $paikka  = "";
      }

      if (isset($isaok[$coprivirow['perheid2']]) and $isaok[$coprivirow['perheid2']] > 0 and $varataan_saldoa == "" and $coprivirow['perheid2'] != $coprivirow['tunnus'] and $coprivirow['perheid2'] > 0 and stripos($coprivirow["positio"], "TEHDAS") !== FALSE) {
        // Jos kyseessä on tehdaslisävaruste niin myydään saldoa varaamatta
        $varataan_saldoa = "EI";
        $varataan_sa_revert = TRUE;
      }

      if (isset($isaok[$coprivirow['perheid2']]) and $isaok[$coprivirow['perheid2']] > 0 and $coprivirow['perheid2'] != $coprivirow['tunnus'] and $coprivirow['perheid2'] > 0 and stripos($coprivirow["positio"], "TEHDAS") !== FALSE) {
        // Otetaan paikka isältä aina kun on tehdaslisävaruste!
        $query = "SELECT concat_ws('#!¡!#', hyllyalue,hyllynro,hyllyvali,hyllytaso) paikka
                  FROM tilausrivi
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tunnus  = '".$isaok[$coprivirow['perheid2']]."'";
        $aresult = pupe_query($query);
        $paikkarow = mysql_fetch_assoc($aresult);

        $paikka = $paikkarow["paikka"];
      }

      $korvaavakielto    = "";
      $jtkielto        = $laskurow['jtkielto'];

      $query = "SELECT * FROM tuote WHERE yhtio='$kukarow[yhtio]' and tuoteno='$coprivirow[tuoteno]'";
      $tuote_result = pupe_query($query);
      $trow = mysql_fetch_array($tuote_result);

      $tilauksesta_myyntitilaus .= "<font class='message'>Lisätään rivi $tuoteno $kpl kpl tilaukselle $utunnus</font><br>";

      // Lisaarivi incissä oletetaan, että hinnat on laskun valuutassa,
      // mutta tässä kohti otetaan hinta tilausriviltä, jossa on hinnat aina yhtiön valuutassa
      // -> ilman tätä muutosta hinnat muuntuisi väärin.
      // Otetaan laskun alkuperäinen valuutta kuitenkin talteen ja plautetaan se lisaarivi.inc jälkeen.
      $valkoodi_lasku = $laskurow["valkoodi"];
      if (trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
        $laskurow["valkoodi"] = $yhtiorow["valkoodi"];
      }

      require 'lisaarivi.inc';

      $laskurow["valkoodi"] = $valkoodi_lasku;

      // Jos isätuote meni ok ja sillä on tehdaslisävarusteita niin lisätään ne saldosta piittaamatta
      if (count($lisatyt_rivit1) == 1 and $coprivirow['perheid2'] == $coprivirow['tunnus']) {
        $isaok[$coprivirow['tunnus']] = $lisatyt_rivit1[0];
      }

      $lisatyt_rivit = array_merge($lisatyt_rivit1, $lisatyt_rivit2);

      if ($varataan_sa_revert) {
        $varataan_saldoa = "";
      }

      // Korjataan sarjanumero-oliot, eli siirretään ne tarjousriviltä tilausriville, jos kopioitava tilaus on tarjous
      if ($monistarow["tila"] == "T" and count($lisatyt_rivit) > 0) {
        if ($coprivirow["varattu"] > 0) {

          $lisax = "myyntirivitunnus";
        }
        else {
          $lisax = "ostorivitunnus";
        }

        $query = "SELECT $lisax, count(*) kpl
                  FROM sarjanumeroseuranta
                  WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
                  and sarjanumeroseuranta.tuoteno = '$coprivirow[tuoteno]'
                  and sarjanumeroseuranta.$lisax  = '$coprivirow[tunnus]'
                  group by $lisax";
        $sarjares = pupe_query($query);
        $sarjarow = mysql_fetch_array($sarjares);

        foreach ($lisatyt_rivit as $lisatty_tun) {
          $query = "SELECT *
                    FROM tilausrivi
                    WHERE yhtio = '$kukarow[yhtio]'
                    and otunnus = '$utunnus'
                    and tunnus  = '$lisatty_tun'";
          $lisres = pupe_query($query);
          $lisrow = mysql_fetch_array($lisres);

          if ($lisrow["varattu"] != 0) {
            $lisrow["varattu"] = (int) abs(round($lisrow["varattu"], 0));

            $query = "UPDATE sarjanumeroseuranta
                      SET $lisax = '$lisrow[tunnus]'
                      WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
                      and sarjanumeroseuranta.tuoteno = '$coprivirow[tuoteno]'
                      and sarjanumeroseuranta.$lisax  = '$coprivirow[tunnus]'
                      LIMIT $lisrow[varattu]";
            $cores = pupe_query($query);
          }

          //Kopioidaan tilausrivin lisatiedot
          $query = "SELECT *
                    FROM tilausrivin_lisatiedot
                    WHERE tilausrivitunnus = '$coprivirow[tunnus]' and yhtio = '$kukarow[yhtio]'";
          $monistares2 = pupe_query($query);

          if (mysql_num_rows($monistares2) > 0) {
            $monistarow2 = mysql_fetch_array($monistares2);

            $kysely = "  UPDATE tilausrivin_lisatiedot SET ";

            for ($i=0; $i < mysql_num_fields($monistares2)-1; $i++) { // Ei monisteta tunnusta
              switch (mysql_field_name($monistares2, $i)) {
              case 'yhtio':
              case 'tilausrivitunnus':
              case 'tiliointirivitunnus':
              case 'tilausrivilinkki':
              case 'luontiaika':
              case 'laatija':
                $values .= "";
                break;
              default:
                $kysely .= mysql_field_name($monistares2, $i)."='".$monistarow2[$i]."',";
              }
            }

            $kysely  = substr($kysely, 0, -1);
            $kysely .= " WHERE yhtio='$kukarow[yhtio]' and tilausrivitunnus='$lisrow[tunnus]'";
            $insres2 = pupe_query($kysely);
          }
        }

        //Jos jotakin kopioitavan rivin sarjanumeroista ei saatu kopioitua niin irrotetaan se siltä riviltä
        $query = "UPDATE sarjanumeroseuranta
                  SET $lisax = 0
                  WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
                  and sarjanumeroseuranta.tuoteno = '$coprivirow[tuoteno]'
                  and sarjanumeroseuranta.$lisax  = '$coprivirow[tunnus]'";
        $cores = pupe_query($query);
      }
    }

    //Korjataan perheid:t uusilla riveillä
    $query = "SELECT perheid, min(tunnus) uusiperheid
              FROM tilausrivi
              WHERE yhtio  = '$kukarow[yhtio]'
              and otunnus  = '$utunnus'
              and perheid != 0
              GROUP by perheid";
    $copresult = pupe_query($query);

    while ($coprivirow = mysql_fetch_array($copresult)) {
      $query = "UPDATE tilausrivi
                SET perheid = '$coprivirow[uusiperheid]'
                WHERE yhtio = '$kukarow[yhtio]'
                and otunnus = '$utunnus'
                and perheid = '$coprivirow[perheid]'";
      $cores = pupe_query($query);
    }

    //Korjataan perheid2:t uusilla riveillä
    $query = "SELECT perheid2, min(tunnus) uusiperheid2
              FROM tilausrivi
              WHERE yhtio   = '$kukarow[yhtio]'
              and otunnus   = '$utunnus'
              and perheid2 != 0
              GROUP by perheid2";
    $copresult = pupe_query($query);

    while ($coprivirow = mysql_fetch_array($copresult)) {
      $query = "UPDATE tilausrivi
                SET perheid2 = '$coprivirow[uusiperheid2]'
                WHERE yhtio  = '$kukarow[yhtio]'
                and otunnus  = '$utunnus'
                and perheid2 = '$coprivirow[perheid2]'";
      $cores = pupe_query($query);
    }

    //Jos kyseessä on laskutettava myyntitili
    if (($monistarow["tila"] == "G" and $monistarow["alatila"] == "V" and $monistarow["tilaustyyppi"] == "M") or ($monistarow['clearing'] == 'EXTTARJOUS')) {
      if ($monistarow['clearing'] != 'EXTTARJOUS') {
        // Päivitetään kopsatun laskun eilahetettä kenttää
        $query = "UPDATE lasku
                  SET eilahetetta = 'o'
                  where yhtio = '$kukarow[yhtio]'
                  and tunnus  = '$kukarow[kesken]'";
        $result = pupe_query($query);

        $tilausvalmiskutsuja = "MYYNTITILI";
      }

      $query   = "SELECT *
                  from lasku
                  where tunnus = '$kukarow[kesken]'
                  and yhtio    = '$kukarow[yhtio]'";
      $result    = pupe_query($query);
      $laskurow   = mysql_fetch_array($result);

      require "tilaus-valmis.inc";
    }

    //Korjataan globaali muuttuja
    $kukarow["kesken"] = $kukarowkesken;

    if ($tilauksesta_myyntitilaus != "" and $linkkilisa != "") {
      $tilauksesta_myyntitilaus .= "<br><br>$linkkilisa<br>";
    }

    return $tilauksesta_myyntitilaus;
  }
}
