<?php

/**
 * Despatch Advice Messages
 * DESADV D.00A
 * Version 0.2
 * EDIT: FI0089
 *
 */

// Tarvitaan $otunnukset jossa on tilauksien tunnukset
// Tarvitaan $tunnukset jossa on rahtikirjojen tunnukset
// Tarvitaan $rahtikirjanro jossa on rahtikirjan numero
// Tarvitaan $toitarow jossa on laskun toimitustavan tiedot
// Passataan $desadv_version, mik‰li se on "una", niin tulostetaan uudet headerit

if (!function_exists('desadv_header')) {
  function desadv_header($params) {
    global $kukarow, $yhtiorow;

    // Luodaan muuttujat
    extract($params);

    if ($desadv_version == "una" or $desadv_version == 'fi0089') {

      if ($desadv_version == "una") {
        $kuljetustunnus = "PS".sprintf("%012.12s", $desadv_laskurow["laskutunnus"]); // t‰m‰ voi olla yhteens‰ max 14 merkki‰pitk‰ stringjono

        // ED2 ja UNA kehykset.
        // NRQ:0 = Ei CON kuittauspyyntˆ‰, NRQ:1 = CON kuittauspyyntˆ
        $desadv_message  = "'''ED2''':{$desadv_laskurow["asiakasovttunnus"]}:OVT':{$desadv_laskurow["yhtio_ovttunnus"]}:OVT'{$kuljetustunnus}'1'STX:EDIFACT'TYP:EDI'NRQ:0'''";
        $desadv_message .= "\nUNA:+.? '";
      }

      // Valmistelup‰iv‰ys ja valmisteluaika on now(), jollei parempaa tietoa tule.
      $valpvm = date("ymd");
      $valaika = date("Hi");
      $lahtunniste = $valpvm.$valaika.$rajaus;

      // UNB ja UNOC kehys
      // Ton DESADVD00A per‰ss‰ on 4 plusmerkki‰, mik‰li sinne viimeiseksi merkiksi laitetaan ennen hipsua 1 niin se on testisanoma
      $desadv_message .= "UNB+UNOC:1+{$desadv_laskurow["yhtio_ovttunnus"]}:30+{$desadv_laskurow["asiakasovttunnus"]}:30+{$valpvm}:{$valaika}+{$lahtunniste}++DESADVD00A++++'";
    }

    // UNH - message header
    $desadv_message .= "UNH+{$desadv_laskurow['laskutunnus']}+DESADV:D:00A:UN";

    if ($desadv_version == 'fi0089') $desadv_message .= ":FI0089";

    $desadv_message .= "'";
    $x++;

    // BGM (beginning of message) - identification data of despatch advice
    // 351 = despatch advice
    // document identifier? onko t‰m‰ laskun tunnus myˆs?
    $desadv_message .= "BGM+351+{$desadv_laskurow['laskutunnus']}+9'";
    $x++;

    if ($desadv_version == 'fi0089') {
      // DTM - date of despatch advice
      // 0000-00-00 00:00:00
      // 102   YYYYMMDD
      list($date, $time) = explode(" ", $desadv_rakir_row['tulostettu']);
      list($year, $month, $day) = explode("-", $date);
      $desadv_message .= "DTM+137:{$year}{$month}{$day}:102'";
      $x++;

      // DTM - despatch and/or delivery date/time
      // 11 = despatch date/time
      $desadv_message .= "DTM+11:{$year}{$month}{$day}:102'";
      $x++;


      $desadv_message .= "DTM+35:{$year}{$month}{$day}:102'";
      $x++;
    }

    if ($desadv_version == "una" or $desadv_version == "") {
      // DTM - date of despatch advice
      // 0000-00-00 00:00:00
      // 202   YYMMDDHHMMSS
      list($date, $time) = explode(" ", $desadv_rakir_row['tulostettu']);
      list($year, $month, $day) = explode("-", $date);
      list($hour, $minute, $second) = explode(":", $time);
      $desadv_message .= "DTM+137:".substr($year, 2, 2).$month.$day.$hour.$minute.$second.":202'";
      $x++;

      // DTM - despatch and/or delivery date/time
      // 11 = despatch date/time
      $desadv_message .= "DTM+11:".substr($year, 2, 2).$month.$day.$hour.$minute.$second.":202'";
      $x++;
      // DTM - despatch and/or delivery date/time
      // 171 = tilausp‰iv‰
      list($date, $time) = explode(" ", $desadv_laskurow['luontiaika']);
      list($year, $month, $day) = explode("-", $date);
      list($hour, $minute, $second) = explode(":", $time);
      $desadv_message .= "DTM+171:".substr($year, 2, 2).$month.$day.$hour.$minute.$second.":202'";
      $x++;

      // DTM - despatch and/or delivery date/time
      // 171 = tilausp‰iv‰
      list($year, $month, $day) = explode("-", $desadv_laskurow['toimaika']);
      $desadv_message .= "DTM+69:{$year}{$month}{$day}:102'";
      $x++;

      // RFF - customer reference number
      // CR = customer reference number
      // The format of this reference number is nnn-llllll, where nnn is Stockmann's merchandise group and llllll is the actual Stockmann's order number
      if ($desadv_laskurow['asiakkaan_tilausnumero'] != '') {
        $desadv_message .= "RFF+CR:".substr($desadv_laskurow["asiakkaan_tilausnumero"], 0, 35)."'";
        $x++;
      }
      else {
        $error .= t("Asiakkaan tilausnumero puuttui")."!<br>";
      }

      // RFF - waybill number
      // AAM = waybill number
      $desadv_message .= "RFF+AAM:".substr($desadv_rakir_row["rahtikirjanro"], 0, 35)."'";
      $x++;

      // RFF - buyer's order number
      // CO = buyer's order number
      if ($desadv_laskurow['asiakkaan_tilausnumero'] != '') {
        if (strpos($desadv_laskurow['asiakkaan_tilausnumero'], "-") !== FALSE) {
          // Stockmannille explodetaan
          list($group, $order) = explode("-", $desadv_laskurow['asiakkaan_tilausnumero']);
          $desadv_message .= "RFF+CO:".substr($order, 0, 35)."'";
        }
        else {
          $desadv_message .= "RFF+CO:".substr($desadv_laskurow["asiakkaan_tilausnumero"], 0, 35)."'";
        }
        $x++;
      }

      // NAD - identification of the seller
      // SE = seller
      // 100 = Finnish EDI identifier
      $desadv_message .= "NAD+SE+{$yhtiorow['ovttunnus']}:100++".substr($yhtiorow["nimi"], 0, 35)."+".substr($yhtiorow["osoite"], 0, 35)."+".substr($yhtiorow["postitp"], 0, 35)."++".substr($yhtiorow["postino"], 0, 17)."+".substr($yhtiorow["maa"], 0, 3)."'";
      $x++;
    }

    if ($desadv_version == 'fi0089') {
      // CZ - consignee
      $desadv_message .= "NAD+CZ+{$yhtiorow['ovttunnus']}::100++".substr($yhtiorow["nimi"], 0, 35)."+".substr($yhtiorow["osoite"], 0, 35)."+".substr($yhtiorow["postitp"], 0, 35)."++".substr($yhtiorow["postino"], 0, 17)."+".substr($yhtiorow["maa"], 0, 3)."'";
      $x++;
      // SE - seller
      $desadv_message .= "NAD+SE+{$yhtiorow['ovttunnus']}::100++".substr($yhtiorow["nimi"], 0, 35)."+".substr($yhtiorow["osoite"], 0, 35)."+".substr($yhtiorow["postitp"], 0, 35)."++".substr($yhtiorow["postino"], 0, 17)."+".substr($yhtiorow["maa"], 0, 3)."'";
      $x++;
    }

    // NAD - identification of buyer
    if ($desadv_laskurow['asiakasovttunnus'] != '' and $desadv_laskurow['asiakasnimi'] != '') {
      $desadv_message .= "NAD+BY+$desadv_laskurow[asiakasovttunnus]:100++".substr($desadv_laskurow["asiakasnimi"], 0, 35).":".substr($desadv_laskurow["asiakasnimitark"], 0, 35)."+".substr($desadv_laskurow["asiakasosoite"], 0, 35)."+".substr($desadv_laskurow["asiakaspostitp"], 0, 35)."++".substr($desadv_laskurow["asiakaspostino"], 0, 17)."+".substr($desadv_laskurow["asiakasmaa"], 0, 3)."'";
      $x++;
    }
    else {
      $error .= t("Asiakkaan ovt-tunnus / nimi puuttui")."!<br>";
    }

    // NAD - identification of consignee
    if ($toitarow['toim_nimi'] != '' and $toitarow['toim_ovttunnus'] != '') {
      $desadv_message .= "NAD+CN+{$toitarow['toim_ovttunnus']}::92++"
        .substr($toitarow["toim_nimi"], 0, 35).":"
        .substr($toitarow["toim_nimitark"], 0, 35)."+"
        .substr($toitarow["toim_osoite"], 0, 35)."+"
        .substr($toitarow["toim_postitp"], 0, 35)."++"
        .substr($toitarow["toim_postino"], 0, 17)."+"
        .substr($toitarow["toim_maa"], 0, 3)."'";
      $x++;
    }
    elseif ($desadv_laskurow["toim_nimi"] != '' and $desadv_laskurow['asiakasovttunnus'] != '') {
      //asiakkaan toimitusosoite
      $desadv_message .= "NAD+CN+{$desadv_laskurow['asiakasovttunnus']}:100++"
        .substr($desadv_laskurow["toim_nimi"], 0, 35).":"
        .substr($desadv_laskurow["toim_nimitark"], 0, 35)."+"
        .substr($desadv_laskurow["toim_osoite"], 0, 35)."+"
        .substr($desadv_laskurow["toim_postitp"], 0, 35)."++"
        .substr($desadv_laskurow["toim_postino"], 0, 17)."+"
        .substr($desadv_laskurow["toim_maa"], 0, 3)."'";
      $x++;
    }
    else {
      $error .= t("Toimitustavan toim_ovt-tunnus / toim_nimi puuttui")."!<br>";
    }

    // NAD - identification of the delivery party
    if ($desadv_laskurow['toim_nimi'] != '' and $desadv_laskurow['asiakastoim_ovttunnus'] != '') {
      $desadv_message .= "NAD+DP+{$desadv_laskurow['asiakastoim_ovttunnus']}::92++"
        .substr($desadv_laskurow["toim_nimi"], 0, 35).":"
        .substr($desadv_laskurow["toim_nimitark"], 0, 35)."+"
        .substr($desadv_laskurow["toim_osoite"], 0, 35)."+"
        .substr($desadv_laskurow["toim_postitp"], 0, 35)."++"
        .substr($desadv_laskurow["toim_postino"], 0, 17)."+"
        .substr($desadv_laskurow["toim_maa"], 0, 3)."'";
      $x++;

      if ($desadv_version != "fi0089") {
        $desadv_message .= "LOC+11+:::".substr($desadv_laskurow["toim_postitp"], 0, 70)."'";
        $x++;
      }
    }
    else {
      $error .= t("Asiakkaan toim_ovt-tunnus / toim_nimi puuttui")."!<br>";
    }

    // IC = information contact
    if ($yhteyshenkilo != '') {
      $desadv_message .= "CTA+IC+:".substr($yhteyshenkilo, 0, 35)."'";
      $x++;

      // COM - communication contact
      if ($yhteyshenkilo_puh != '') {
        $desadv_message .= "COM+".substr($yhteyshenkilo_puh, 0, 512).":TE'";
        $x++;
      }
      else {
        $yhteyshenkilo_puh = $desadv_laskurow['puhelin'] != '' ? $desadv_laskurow['puhelin'] : ($desadv_laskurow['gsm'] != '' ? $desadv_laskurow['gsm'] : ($desadv_laskurow['tyopuhelin'] != '' ? $desadv_laskurow['tyopuhelin'] : '')); // max 15 merkki‰, C

        if ($yhteyshenkilo_puh != '') {
          $desadv_message .= "COM+".substr($yhteyshenkilo_puh, 0, 512).":TE'";
          $x++;
        }
      }
    }

    if (trim($desadv_laskurow['toimitusehto']) != '') {
      // TOD - terms of delivery
      $desadv_message .= "TOD+6+Z2K+".substr($desadv_laskurow['toimitusehto'], 0, 3)."'";
      $x++;
    }

    if ($desadv_version != "fi0089") {
      // LOC - place/location identification
      // ei ole pupessa listausta UN/LOCODE
      $desadv_message .= "LOC+1'";
      $x++;
    }

    return array($desadv_message, $error, $x, $lahtunniste);
  }
}

if (!function_exists('desadv_row')) {
  function desadv_row($params) {
    global $kukarow, $yhtiorow;

    // Luodaan muuttujat
    extract($params);

    // Katsotaan lˆytyykˆ tilaukselle ker‰yser‰
    $query = "SELECT sscc, sscc_ulkoinen, pakkaus, GROUP_CONCAT(tilausrivi) AS tilausrivit
              FROM kerayserat
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND otunnus = '{$desadv_laskurow['laskutunnus']}'
              GROUP BY 1,2,3
              ORDER BY 1,2,3";
    $keraysera_res = pupe_query($query);

    $kokonais_pakettimaara = 0;

    if (mysql_num_rows($keraysera_res) > 0) {

      while ($keraysera_row = mysql_fetch_assoc($keraysera_res)) {

        if ($keraysera_row['sscc_ulkoinen'] != "") {
          $sscc = $keraysera_row['sscc_ulkoinen'];
        }
        else {
          $sscc = $keraysera_row['sscc'];
        }

        // CPS - consignment packing sequence
        $desadv_message .= "CPS+{$sscc}++1'";
        $x++;

        // PAC - number and type of packages
        $desadv_message .= "PAC+1++PK'";
        $x++;

        $query = "SELECT *
                  FROM pakkaus
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tunnus  = '{$keraysera_row['pakkaus']}'";
        $pakkaus_res = pupe_query($query);
        $pakkaus_row = mysql_fetch_assoc($pakkaus_res);

        if ($desadv_version != "fi0089" and ($pakkaus_row['korkeus'] == 0 or $pakkaus_row['leveys'] == 0 or $pakkaus_row['syvyys'] == 0)) {
          $error .= t("Pakkauksella %s %s on puutteelliset mittatiedot", '', $pakkaus_row['pakkaus'], $pakkaus_row['pakkauskuvaus'])."<br>";
        }

        if ($desadv_version == "fi0089") {
          // Haetaan pakkauksen tiedot
          $query = "SELECT SUM(tuote.tuotemassa * kerayserat.kpl_keratty) kilot, SUM(tuote.tuotekorkeus * tuote.tuoteleveys * tuote.tuotesyvyys * kerayserat.kpl_keratty) tilavuus
                    FROM tilausrivi
                    JOIN kerayserat ON (kerayserat.yhtio = tilausrivi.yhtio AND kerayserat.tilausrivi = tilausrivi.tunnus)
                    JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
                    WHERE tilausrivi.yhtio  = '{$kukarow['yhtio']}'
                    AND tilausrivi.tunnus   IN ({$keraysera_row['tilausrivit']})
                    AND tilausrivi.tyyppi  != 'D'";
          $paino_res = pupe_query($query);
          $paino_row = mysql_fetch_assoc($paino_res);

          $paino_row['kilot'] += $pakkaus_row['oma_paino'];
          $paino_row['kilot'] = round($paino_row['kilot']);

          // Pakkauksen bruttopaino
          $desadv_message .= "MEA+AAE+AAA+KGM:{$paino_row['kilot']}'";
          $x++;

          // Pakkauksen tilavuus
          // m3 => litrat
          $pakkauksen_tilavuus_litroina = round($paino_row['tilavuus'] * 1000);
          $desadv_message .= "MEA+AAE+ABJ+LTR:{$pakkauksen_tilavuus_litroina}'";
          $x++;
        }

        $query = "SELECT tuote.eankoodi,
                  tilausrivi.tuoteno,
                  tilausrivi.nimitys,
                  tilausrivi.tilaajanrivinro,
                  SUM(tilausrivi.varattu+tilausrivi.kpl) varattu
                  FROM tilausrivi
                  JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
                  WHERE tilausrivi.yhtio  = '{$kukarow['yhtio']}'
                  AND tilausrivi.tunnus   IN ({$keraysera_row['tilausrivit']})
                  AND tilausrivi.tyyppi  != 'D'
                  GROUP BY tuote.eankoodi, tilausrivi.tuoteno, tilausrivi.nimitys, tilausrivi.tilaajanrivinro";
        $tuotteet_res = pupe_query($query);

        $i = 0;

        while ($tuotteet_row = mysql_fetch_assoc($tuotteet_res)) {

          $i++;

          if ($tuotteet_row["eankoodi"] == "") {
            $error .= t("Tuotteelta %s puuttuu EAN-koodi", '', $tuotteet_row['tuoteno'])."<br>";
          }

          // LIN - line item
          $desadv_message .= "LIN+{$i}++".substr($tuotteet_row["eankoodi"], 0, 35).":EN'";
          $x++;

          // PIA - additional product identification
          $tuotenumero = preg_replace("/[^a-zA-ZÂ‰ˆ≈ƒ÷0-9]/", " ", $tuotteet_row['tuoteno']);
          $desadv_message .= "PIA+5+".substr($tuotenumero, 0, 35).":SA'";
          $x++;

          // IMD - additional information of the article
          $tuotteen_nimitys = preg_replace("/[^a-zA-ZÂ‰ˆ≈ƒ÷0-9]/", " ", $tuotteet_row['nimitys']);
          $desadv_message .= "IMD+F+8+:::".substr($tuotteen_nimitys, 0, 256)."'";
          $x++;

          // QTY - quantity
          // PCE = piece
          // tarvitaan varmaan laittaa eri uniitteja???? UN/ECE recommendation number 20
          $desadv_message .= "QTY+12:".round($tuotteet_row['varattu'], 0).":PCE'";
          $x++;

          // RFF - buyer's order number
          // CO = buyer's order number
          if ($desadv_version == "fi0089") {
            // In line level RFF+CO the order line number has to be like we send in our orders 10, 20, 30 etc. instead of 1, 2, 3
            // RFF+CO: 116100531:10'
            if ($desadv_laskurow['asiakkaan_tilausnumero'] != '') {
              $desadv_message .= "RFF+CO:".substr($desadv_laskurow["asiakkaan_tilausnumero"], 0, 35).":{$tuotteet_row['tilaajanrivinro']}'";
              $x++;
            }

            // DTM - despatch and/or delivery date/time
            // 171 = tilausp‰iv‰
            list($date, $time) = explode(" ", $desadv_laskurow['luontiaika']);
            list($year, $month, $day) = explode("-", $date);
            $desadv_message .= "DTM+171:{$year}{$month}{$day}:102'";
            $x++;
          }
        }

        $kokonais_pakettimaara++;
      }
    }
    else {

      // CPS - consignment packing sequence
      if ($desadv_version == "fi0089") {
        $sscc          = uusi_sscc_nro();

        if (!empty($yhtiorow['ean'])) {
          $_selitetark = t_avainsana("GS1_SSCC", "", "and avainsana.selite = '{$desadv_laskurow['toimitustapa']}'", "", "", "selitetark");

          if ($_selitetark == '') {
            $_selitetark = t_avainsana("GS1_SSCC", "", "and avainsana.selite = 'kaikki'", "", "", "selitetark");
          }

          if ($_selitetark != '') {
            $expansioncode = $_selitetark;

            $sscc_ulkoinen = gs1_sscc($expansioncode, $sscc, 1);
          }
          else {
            $sscc_ulkoinen = $pakkaukset[$monesko]['sscc'];
          }
        }
        else {
          $sscc_ulkoinen = $sscc;
        }

        $desadv_message .= "CPS+{$sscc_ulkoinen}++1'";
        $x++;
      }
      else {
        $desadv_message .= "CPS+1'";
        $x++;
      }

      if (trim($desadv_rakir_row['viesti']) != '') {
        $desadv_message .= "FTX+GEN+++{$desadv_rakir_row['viesti']}'";
        $x++;
      }

      unset($desadv_kollitres);

      if ((int) $rahtikirjanro != 0) {
        $query = "SELECT ROUND(SUM(kollit), 0) kollit, SUM(kuutiot) kuutiot, SUM(kilot) kilot
                  FROM rahtikirjat USE INDEX (otsikko_index)
                  WHERE yhtio       = '{$kukarow['yhtio']}'
                  AND otsikkonro    = '{$desadv_laskurow['laskutunnus']}'
                  AND rahtikirjanro = '{$rahtikirjanro}'";
        $desadv_kollitres = pupe_query($query);
      }

      if (isset($desadv_kollitres) and mysql_num_rows($desadv_kollitres) > 0) {
        $desadv_kollitrow = mysql_fetch_assoc($desadv_kollitres);
      }
      else {
        $desadv_kollitrow["kollit"] = $desadv_kollitrow["kuutiot"] = $desadv_kollitrow["kilot"] = 0;
      }

      // PAC - number and type of packages
      $desadv_message .= "PAC+{$desadv_kollitrow['kollit']}++PK:6'";
      $x++;

      if ($desadv_version == "fi0089") {

        // Pakkauksen bruttopaino
        $desadv_message .= "MEA+AAE+AAA+KGM:{$desadv_kollitrow["kilot"]}'";
        $x++;

        // Pakkauksen tilavuus
        // m3 => litrat
        $pakkauksen_tilavuus_litroina = round($desadv_kollitrow["kuutiot"] * 1000);
        $desadv_message .= "MEA+AAE+ABJ+LTR:{$pakkauksen_tilavuus_litroina}'";
        $x++;
      }

      // LIN - line item
      $query = "SELECT tuote.eankoodi,
                tilausrivi.tuoteno,
                tilausrivi.nimitys,
                tilausrivi.tilaajanrivinro,
                SUM(tilausrivi.varattu+tilausrivi.kpl) varattu
                FROM tilausrivi
                JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
                WHERE tilausrivi.yhtio  = '{$kukarow['yhtio']}'
                AND tilausrivi.otunnus  = '{$desadv_laskurow['laskutunnus']}'
                AND tilausrivi.tyyppi  != 'D'
                GROUP BY tuote.eankoodi, tilausrivi.tuoteno, tilausrivi.nimitys, tilausrivi.tilaajanrivinro";
      $tuotteet_res = pupe_query($query);

      $i = 0;

      while ($tuotteet_row = mysql_fetch_assoc($tuotteet_res)) {

        $i++;

        if ($tuotteet_row["eankoodi"] == "") {
          $error .= t("Tuotteelta %s puuttuu EAN-koodi", '', $tuotteet_row['tuoteno'])."<br>";
        }

        $desadv_message .= "LIN+{$i}++".substr($tuotteet_row["eankoodi"], 0, 35).":EN'";
        $x++;

        // PIA - additional product identification
        $tuotenumero = preg_replace("/[^a-zA-ZÂ‰ˆ≈ƒ÷0-9]/", " ", $tuotteet_row['tuoteno']);
        $desadv_message .= "PIA+5+".substr($tuotenumero, 0, 35).":SA'";
        $x++;

        // IMD - additional information of the article
        $tuotteen_nimitys = preg_replace("/[^a-zA-ZÂ‰ˆ≈ƒ÷0-9]/", " ", $tuotteet_row['nimitys']);
        $desadv_message .= "IMD+F+8+:::".substr($tuotteen_nimitys, 0, 256)."'";
        $x++;

        // QTY - quantity
        // PCE = piece
        // tarvitaan varmaan laittaa eri uniitteja???? UN/ECE recommendation number 20
        $desadv_message .= "QTY+12:".round($tuotteet_row['varattu'], 0).":PCE'";
        $x++;

        // RFF - buyer's order number
        // CO = buyer's order number
        if ($desadv_version == "fi0089") {
          // In line level RFF+CO the order line number has to be like we send in our orders 10, 20, 30 etc. instead of 1, 2, 3
          // RFF+CO: 116100531:10'
          if ($desadv_laskurow['asiakkaan_tilausnumero'] != '') {
            $desadv_message .= "RFF+CO:".substr($desadv_laskurow["asiakkaan_tilausnumero"], 0, 35).":{$tuotteet_row['tilaajanrivinro']}'";
            $x++;
          }

          // DTM - despatch and/or delivery date/time
          // 171 = tilausp‰iv‰
          list($date, $time) = explode(" ", $desadv_laskurow['luontiaika']);
          list($year, $month, $day) = explode("-", $date);
          $desadv_message .= "DTM+171:{$year}{$month}{$day}:102'";
          $x++;
        }
      }

      $kokonais_pakettimaara++;
    }

    // CNT - control total
    $desadv_message .= "CNT+2:{$kokonais_pakettimaara}'";
    $x++;

    return array($desadv_message, $error, $x);
  }
}

//jos on laitettu kollikentt‰‰n 0 niin ei yritet‰ luoda siirtotiedostoa
if ($desadv_host != '' and $desadv_user != '' and $desadv_pass != '' and $desadv_path != '') {

  mysql_data_seek($toimv_laskures, 0);

  $rajaus = 1;

  while ($desadv_laskurow = mysql_fetch_assoc($toimv_laskures)) {

    $lahtunniste = "";

    $query = "SELECT *
              FROM rahtikirjat
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  in ({$tunnukset})";
    $desadv_rakir_res = pupe_query($query);

    if (mysql_num_rows($desadv_rakir_res) > 0) {
      $desadv_rakir_row = mysql_fetch_assoc($desadv_rakir_res);
    }
    else {
      $desadv_rakir_row["tulostettu"] = date("Y-m-d H:i:s");
      $desadv_rakir_row["rahtikirjanro"] = 0;
      $desadv_rakir_row["viesti"] = "";
    }

    // CTA - contact information
    $query = "SELECT *
              FROM yhteyshenkilo
              WHERE yhtio      = '$kukarow[yhtio]'
              AND liitostunnus = '$desadv_laskurow[liitostunnus]'
              AND nimi         = '$desadv_laskurow[tilausyhteyshenkilo]'
              and tyyppi       = 'A'";
    $yhteyshenkiloresult = pupe_query($query);
    $yhteyshenkiloasiakas = mysql_fetch_assoc($yhteyshenkiloresult);

    $yhteyshenkilo = '';
    $yhteyshenkilo_puh = '';

    if ($yhteyshenkiloasiakas['nimi'] != '') {
      $yhteyshenkilo = $yhteyshenkiloasiakas['nimi'];
      $yhteyshenkilo_puh = $yhteyshenkiloasiakas['puh'] != '' ? $yhteyshenkiloasiakas['puh'] : ($yhteyshenkiloasiakas['gsm'] != '' ? $yhteyshenkiloasiakas['gsm'] : '');
    }
    elseif ($desadv_laskurow['yhteyshenkilo_kaupallinen'] != '') {
      $query = "SELECT *
                FROM yhteyshenkilo
                WHERE yhtio = '$kukarow[yhtio]'
                AND tunnus  = '$desadv_laskurow[yhteyshenkilo_kaupallinen]'
                and tyyppi  = 'A'";
      $yhteyshenkilo_kaupallinen_res = pupe_query($query);
      $yhteyshenkilo_kaupallinen_row = mysql_fetch_assoc($yhteyshenkilo_kaupallinen_res);

      $yhteyshenkilo = $yhteyshenkilo_kaupallinen_row['nimi'];
      $yhteyshenkilo_puh = $yhteyshenkilo_kaupallinen_row['puh'] != '' ? $yhteyshenkilo_kaupallinen_row['puh'] : ($yhteyshenkilo_kaupallinen_row['gsm'] != '' ? $yhteyshenkilo_kaupallinen_row['gsm'] : '');
    }
    elseif ($desadv_laskurow['yhteyshenkilo_tekninen'] != '') {
      $query = "SELECT *
                FROM yhteyshenkilo
                WHERE yhtio = '$kukarow[yhtio]'
                AND tunnus  = '$desadv_laskurow[yhteyshenkilo_tekninen]'
                and tyyppi  = 'A'";
      $yhteyshenkilo_tekninen_res = pupe_query($query);
      $yhteyshenkilo_tekninen_row = mysql_fetch_assoc($yhteyshenkilo_tekninen_res);

      $yhteyshenkilo = $yhteyshenkilo_tekninen_row['nimi']; // max 35 merkki‰, R (riippuu maasta)
      $yhteyshenkilo_puh = $yhteyshenkilo_tekninen_row['puh'] != '' ? $yhteyshenkilo_tekninen_row['puh'] : ($yhteyshenkilo_tekninen_row['gsm'] != '' ? $yhteyshenkilo_tekninen_row['gsm'] : '');
    }

    $desadv_message = '';
    $error = '';
    $x = 0; // yhteens‰ rivej‰ koko filess‰

    $params = array(
      'desadv_laskurow'   => $desadv_laskurow,
      'desadv_rakir_row'  => $desadv_rakir_row,
      'desadv_message'    => $desadv_message,
      'error'             => $error,
      'x'                 => $x,
      'desadv_version'    => $desadv_version,
      'yhteyshenkilo'     => $yhteyshenkilo,
      'yhteyshenkilo_puh' => $yhteyshenkilo_puh,
      'toitarow'          => $toitarow,
      'lahtunniste'       => $lahtunniste,
    );

    list($desadv_message, $error, $x, $lahtunniste) = desadv_header($params);

    $params = array(
      'desadv_laskurow'  => $desadv_laskurow,
      'desadv_rakir_row' => $desadv_rakir_row,
      'desadv_message'   => $desadv_message,
      'error'            => $error,
      'x'                => $x,
      'desadv_version'   => $desadv_version,
      'rahtikirjanro'    => $rahtikirjanro,
    );

    list($desadv_message, $error, $x) = desadv_row($params);

    // UNT - message trailer
    $x++;
    $desadv_message .= "UNT+{$x}+{$desadv_laskurow['laskutunnus']}'";

    if ($desadv_version == "una" or $desadv_version == 'fi0089') {
      // loppuun pakollinen UNZ ja EOF. HUOM: UNZ ja EOF pit‰‰ olla erillisill‰ riveill‰ !!!
      $desadv_message .= "\nUNZ+1+{$lahtunniste}'";

      if ($desadv_version == "una") {
        $desadv_message .= "\n'''EOF'''0'";
      }
    }

    $filenimi = $pupe_root_polku."/dataout/desadv_import-{$kukarow['yhtio']}-".date("Ymd")."-".md5(uniqid(rand(), true)).".txt";

    //kirjoitetaan faili levylle..
    if (file_put_contents($filenimi, $desadv_message) === FALSE) {
      echo "<br/><font class='error'>", t("tiedoston kirjoitus EPƒONNISTUI"), " ({$filenimi})</font><br/>";
    }

    if ($error == '') {
      // tarvitaan  $ftphost $ftpuser $ftppass $ftppath $ftpfile
      // palautetaan $palautus ja $syy
      $ftphost = $desadv_host;
      $ftpuser = $desadv_user;
      $ftppass = $desadv_pass;
      $ftppath = $desadv_path;
      $ftpfile = realpath($filenimi);

      require "inc/ftp-send.inc";

      if ($palautus == 0) {
        echo "<font class='message'>", t("Tilauksesta %s l‰hetettiin toimitusvahvistus.", "", $desadv_laskurow["tunnus"])."</font><br><br>\n";
      }
    }
    else {
      echo "<br/><font class='error'><br><hr>", t("Aineistoa ei voida l‰hett‰‰"), "!<br>", t("Aineistossa virheit‰"), ":<br>{$error}</font><hr><br>";
    }

    $rajaus++;
  }
}
else {
  echo "<br/><font class='error'><br><hr>", t("Toimitusvahvistusta ei voida l‰hett‰‰"), "!<br>", t("L‰hetystiedot puuttuu"), ".<br></font><hr><br>";
}
