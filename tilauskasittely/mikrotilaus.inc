<?php

/* FILEFORMAATTI: (ilman sarakeotsikoita)
Tuoteno (pakollinen)
Määrä (pakollinen)
Arvioitu toimituspäivä vvvv-kk-pp
Hinta
Ale1 - Ale3 (lasketaan yhtiön parametreistä monta myynnin alekenttää on käytössä)
Netto
Var
Kommentti
*/

/* VAIHTOEHTOINEN FILEFORMAATTI: (sarakeotsikoilla)
Tuoteno (pakollinen)
Määrä (pakollinen)
Arvioitu toimituspäivä vvvv-kk-pp
Nimitys
Hinta
Ale1 - Ale3 (lasketaan yhtiön parametreistä monta myynnin alekenttää on käytössä)
Netto
Var
Kommentti
*/

/* VAIHTOEHTOINEN FILEFORMAATTI (VALMISTUS) $yhtiorow['Valmistuksien_kasittely'] == Y: (sarakeotsikoilla)
Tuoteno (pakollinen)
Määrä (pakollinen)
Arvioitu toimituspäivä vvvv-kk-pp
Nimitys
Hinta
Ale1 - Ale3 (lasketaan yhtiön parametreistä monta myynnin alekenttää on käytössä)
Netto
Var
Kommentti
Valmistuslinja
Toivottu keräysajankohta
Toivottu valmistusajankohta
*/

if ($tee == "file") {
  if (is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE) {
    $timeparts = explode(" ", microtime());
    $starttime = $timeparts[1].substr($timeparts[0], 1);

    $path_parts = pathinfo($_FILES['userfile']['name']);
    $name  = strtoupper($path_parts['filename']);
    $ext  = strtoupper($path_parts['extension']);

    if ($ext != "TXT" and $ext != "CSV" and $ext != "XLS" and $ext != "XLSX") {
      die ("<font class='error'><br>".t("Ainoastaan .txt, .csv, .xlsx tai .xls tiedostot sallittuja")."!</font>");
    }

    if ($_FILES['userfile']['size']==0) {
      die ("<font class='error'><br>".t("Tiedosto on tyhjä")."!</font>");
    }

    if (!empty($lue_useammalta_valilehdelta)) {
      $lue_useammalta_valilehdelta = true;
    }
    else {
      $lue_useammalta_valilehdelta = false;
    }

    $excelrivi = pupeFileReader($_FILES['userfile']['tmp_name'], $ext, '', $lue_useammalta_valilehdelta);

    $filetyyppi = "";
    $rivilask1 = 0;

    if (!empty($lue_useammalta_valilehdelta)) {
      foreach ($excelrivi as $sheet) {

        // Tiedostosta päivitys
        foreach ($sheet as $rivi) {
          $rivilask1++;

          // tila==G, alatila==V
          if ($toim == "MYYNTITILI" and $laskurow['tila'] == "G" and $laskurow['alatila'] == "V" and $laskurow['tilaustyyppi'] == 'M') {
            $var = $rivi[7];
            $tuoteno = $rivi[0];
            $kpl = $rivi[1];
            $rivitunnus = 0;

            // Tarkistukset, var ja kpl
            if ($var != 'A' and $var != 'B'  ) {
              echo "<font class='error'>".t("Väärä arvo kentässä var! Sallitut arvot ovat: A tai B").". ".t("Rivillä").": $rivilask1. '$var'"."</font><br><br>";
              continue;
            }

            $query = "SELECT tunnus
                      FROM tilausrivi
                      WHERE yhtio='$kukarow[yhtio]'
                      AND tyyppi = 'G'
                      AND var    = ''
                      AND otunnus='$kukarow[kesken]'
                      AND tuoteno='$tuoteno'";

            $result = pupe_query($query);

            if (mysql_num_rows($result) == 1) {
              $row = mysql_fetch_assoc($result);
              $rivitunnus = $row['tunnus'];
            }
            else {
              $query = "SELECT tunnus
                        FROM tilausrivi
                        WHERE yhtio='$kukarow[yhtio]'
                        AND tyyppi = 'G'
                        AND var    = ''
                        AND otunnus='$kukarow[kesken]'
                        AND tuoteno='$tuoteno'
                        AND kpl    = '$kpl'";

              $result = pupe_query($query);

              if (mysql_num_rows($result) == 1) {
                $row = mysql_fetch_assoc($result);
                $rivitunnus = $row['tunnus'];
              }
            }

            if ($rivitunnus != 0) {
              $tilatapa = "PAIVITA";
              $myyntitilirivit = $var;
              require "laskuta_myyntitilirivi.inc";
            }
            else {
              echo "<font class='error'>".t("Tuotetta ei osattu valita")."</font><br>";
            }
          }
          else {

            $tila    = '';
            $valinta = '';

            // Detektoidaan tiedostomuoto
            if ($filetyyppi == "" and $laskurow["tila"] == "V") {
              // Valmistukset aina tyyppiä kolme
              $filetyyppi = "KOLMONE";

              $ind_Tuoteno                 = 0;
              $ind_Maara                   = 1;

              if ($yhtiorow["splittauskielto"] == "K" or $toim == "YLLAPITO" or (($toim == 'VALMISTAVARASTOON' or $toim == 'VALMISTAASIAKKAALLE') and $yhtiorow['valmistuksien_kasittely'] == 'Y')) {
                $ind_Arvioitu = 2;
                $ind_Hinta = 3;

                $_indx = 4;
              }
              else {
                $ind_Hinta = 2;

                $_indx = 3;

              }

              for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
                ${'ind_Ale'.$alepostfix} = $_indx;
                $_indx++;
              }

              $ind_Netto                   = $_indx;
              $_indx++;
              $ind_Var                     = $_indx;
              $_indx++;
              $ind_Kommentti               = $_indx;
              $_indx++;
              $ind_Valmistuslinja          = $_indx;
              $_indx++;
              $ind_ToivottuKeraysajankohta = $_indx;

              // Jos ensimmäisestä solusta löytyy sana "tuote", niin käyttäjä on antanu sarakeotsikot
              if (stripos($rivi[0], "tuote") !== FALSE) {
                // Skipataan otsikot
                continue;
              }
            }
            elseif ($filetyyppi == ""
              and stripos($rivi[0], "tuote") !== FALSE
              and (stripos($rivi[1], "kpl") !== FALSE or stripos($rivi[1], "määrä") !== FALSE)
              and stripos($rivi[2], "päivä") !== FALSE
              and stripos($rivi[3], "nimi") !== FALSE) {

              $filetyyppi = "KAKKONEN";

              $ind_Tuoteno   = 0;
              $ind_Maara     = 1;

              if ($yhtiorow["splittauskielto"] == "K" or $toim == "YLLAPITO" or (($toim == 'VALMISTAVARASTOON' or $toim == 'VALMISTAASIAKKAALLE') and $yhtiorow['valmistuksien_kasittely'] == 'Y')) {
                $ind_Arvioitu = 2;
                $ind_Hinta = 4;

                $_indx = 5;
              }
              else {
                $ind_Hinta = 3;

                $_indx = 4;

              }

              for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
                ${'ind_Ale'.$alepostfix} = $_indx;
                $_indx++;
              }

              $ind_Netto     = $_indx;
              $_indx++;
              $ind_Var     = $_indx;
              $_indx++;
              $ind_Kommentti  = $_indx;

              // Skipataan otsikot
              continue;
            }
            elseif ($filetyyppi == ""
              and stripos($rivi[0], "tuote") !== FALSE
              and (stripos($rivi[1], "kpl") !== FALSE or stripos($rivi[1], "määrä") !== FALSE)
              and stripos($rivi[2], "päivä") !== FALSE
              and stripos($rivi[3], "hinta") !== FALSE) {

              $filetyyppi = "YKSPISTEVITONEN";

              $ind_Tuoteno   = 0;
              $ind_Maara     = 1;

              if ($yhtiorow["splittauskielto"] == "K" or $toim == "YLLAPITO" or (($toim == 'VALMISTAVARASTOON' or $toim == 'VALMISTAASIAKKAALLE') and $yhtiorow['valmistuksien_kasittely'] == 'Y')) {
                $ind_Arvioitu = 2;
                $ind_Hinta = 3;

                $_indx = 4;
              }
              else {
                $ind_Hinta = 2;

                $_indx = 3;

              }

              for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
                ${'ind_Ale'.$alepostfix} = $_indx;
                $_indx++;
              }

              $ind_Netto     = $_indx;
              $_indx++;
              $ind_Var     = $_indx;
              $_indx++;
              $ind_Kommentti  = $_indx;

              // Skipataan otsikot
              continue;
            }
            elseif ($filetyyppi == "") {

              $filetyyppi = "YKKÖNEN";

              $ind_Tuoteno   = 0;
              $ind_Maara     = 1;

              if ($yhtiorow["splittauskielto"] == "K" or $toim == "YLLAPITO" or (($toim == 'VALMISTAVARASTOON' or $toim == 'VALMISTAASIAKKAALLE') and $yhtiorow['valmistuksien_kasittely'] == 'Y')) {
                $ind_Arvioitu = 2;
                $ind_Hinta = 3;

                $_indx = 4;
              }
              else {
                $ind_Hinta = 2;

                $_indx = 3;

              }

              for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
                ${'ind_Ale'.$alepostfix} = $_indx;
                $_indx++;
              }

              $ind_Netto     = $_indx;
              $_indx++;
              $ind_Var     = $_indx;
              $_indx++;
              $ind_Kommentti  = $_indx;
            }

            $tuoteno = trim($rivi[$ind_Tuoteno]);
            $kpl   = str_replace(",", ".", $rivi[$ind_Maara]);

            $toimaika      = "";
            $kerayspvm      = "";
            $hinta        = "";
            $netto        = "";
            $var        = "";
            $alv        = "";
            $paikka        = "";
            $varasto       = $laskurow["varasto"];
            $rivitunnus      = "";
            $korvaavakielto    = "";
            $varataan_saldoa  = "";
            $perhekielto    = "";
            $jtkielto        = $laskurow['jtkielto'];

            for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
              ${'ale'.$alepostfix} = "";
            }

            if ($kukarow["extranet"] == "") {

              //Toimitusaika
              if ($yhtiorow["splittauskielto"] == "K" or $toim == "YLLAPITO" or (($toim == 'VALMISTAVARASTOON' or $toim == 'VALMISTAASIAKKAALLE') and $yhtiorow['valmistuksien_kasittely'] == 'Y')) {
                $toimaika = choose_correct_date($rivi[$ind_Arvioitu]);
              }
              else {
                $toimaika = "";
              }

              //Keräyspäivämäärä
              if (($toim == 'VALMISTAVARASTOON' or $toim == 'VALMISTAASIAKKAALLE') and $yhtiorow['valmistuksien_kasittely'] == 'Y') {
                $kerayspvm  = choose_correct_date($rivi[$ind_ToivottuKeraysajankohta]);
              }
              else {
                $kerayspvm = "";
              }

              $hinta = str_replace(",", ".", $rivi[$ind_Hinta]);

              for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
                ${'ale'.$alepostfix} = str_replace(",", ".", $rivi[${'ind_Ale'.$alepostfix}]);
              }

              $netto         = strtoupper(trim($rivi[$ind_Netto]));
              $var        = strtoupper(trim($rivi[$ind_Var]));
              $kommentti    = trim($rivi[$ind_Kommentti]);
              $valmistuslinja  = trim($rivi[$ind_Valmistuslinja]);

              if ($toim == "EXTENNAKKO" and $var != '' and $var != 'O') {
                echo t("Väärä arvo kentässä var! Sallitut arvot ovat").": 'O'. ".t("Rivillä").": $rivilask1. $var<br>";
                $var = '';
              }
              elseif ($toim != "EXTENNAKKO" and $var != '' and $var != 'P' and $var != 'H' and $var != 'J') {
                echo t("Väärä arvo kentässä var! Sallitut arvot ovat").": 'P','H','J'. ".t("Rivillä").": $rivilask1. $var<br>";
                $var = '';
              }

              if ($netto != '' and $netto != 'N') {
                echo t("Väärä arvo kentässä netto! Sallitut arvot ovat: tyhjä tai N").". ".t("Rivillä").": $rivilask1. $netto<br>";
                $netto = '';
              }
            }

            if ($tuoteno != '' and $kpl != 0) {

              // Tuotehaku ei toimi extranetin puolella
              if (file_exists("../inc/tuotehaku.inc")) {
                require "../inc/tuotehaku.inc";
              }

              if ($mikrotila_tuotenumero == "koodilla") {
                $query = "SELECT *
                          FROM tuote
                          WHERE eankoodi='$tuoteno' and yhtio='$kukarow[yhtio]'";
              }
              else {
                $query = "SELECT *
                          FROM tuote
                          WHERE tuoteno='$tuoteno' and yhtio='$kukarow[yhtio]'";
              }

              $rarresult = pupe_query($query);

              if (mysql_num_rows($rarresult) == 1) {

                $trow = mysql_fetch_array($rarresult);

                $ohitus = 0;

                if ($kukarow["extranet"] != "") {
                  if (!saako_myyda_private_label($kukarow["oletus_asiakas"], $trow["tuoteno"], $kpl)) {
                    $ohitus = 1;
                  }
                }

                if ($ohitus == 0) {
                  $tuoteno = $trow["tuoteno"];

                  if ($toimaika == '') {
                    $toimaika = $laskurow["toimaika"];
                  }

                  if ($kerayspvm == "") {
                    $kerayspvm = $laskurow["kerayspvm"];
                  }

                  if ($hinta == 0) {
                    $hinta = "";
                  }

                  // Ennakkotilaukset, Tarjoukset, Ylläpitosopimukset ja Valmistukset eivät tee saldotsekkiä
                  if ($laskurow["tilaustyyppi"] == "E" or $laskurow["tila"] == "T" or $laskurow["tilaustyyppi"] == "0" or $laskurow["tila"] == "V") {
                    $varataan_saldoa = "EI";
                  }
                  else {
                    $varataan_saldoa = "";
                  }

                  //lisätään rivi
                  if ($kukarow["extranet"] == "" and $tila == "MYYNTITILIRIVI") {
                    $tilatapa = "PAIVITA";
                    require "laskuta_myyntitilirivi.inc";
                  }
                  elseif ($laskurow["tila"] == "V") {
                    // Valmistuksilla pitää huomioida rekursiiviset reseptit
                    // funktio populoi globaalit muuttujat $tuoteno_array $kpl_array $kommentti_array $lapsenlap_array
                    $tuoteno_array   = array($tuoteno);
                    $kpl_array       = array($tuoteno => $kpl);

                    pupesoft_lisaa_valmisteen_rekursiiviset_reseptit();

                    foreach ($tuoteno_array as $tuoteno_temp) {

                      $query = "SELECT *
                                FROM tuote
                                WHERE yhtio = '{$kukarow["yhtio"]}'
                                AND tuoteno = '{$tuoteno_temp}'";
                      $tuotearray_result = pupe_query($query);
                      $tuotearray_row = mysql_fetch_array($tuotearray_result);

                      if (isset($kommentti_array[$tuoteno_temp])) {
                        $kommentti = $kommentti_array[$tuoteno_temp];
                      }

                      $params = array(
                        "trow"              => $tuotearray_row,
                        "laskurow"          => $laskurow,
                        "kpl"               => $kpl_array[$tuoteno_temp],
                        "tuoteno"           => $tuoteno_temp,
                        "hinta"             => 0,
                        "varasto"           => $varasto,
                        "varataan_saldoa"   => "EI",
                        "kommentti"         => $kommentti,
                        'valmistuslinja'  => $valmistuslinja,
                        'toimaika'      => $toimaika,
                        'kerayspvm'      => $kerayspvm,
                        'toim'        => "{$toim}",
                      );

                      lisaa_rivi($params);
                    }

                    // Nämä pitää unsetata, muuten tilaus_myynti lisää rivit uudestaan
                    unset($tuoteno_array, $kpl_array, $kommentti_array, $lapsenlap_array);
                  }
                  else {
                    $from_mikrotilaus = TRUE;
                    if ($yhtiorow["reklamaation_hinnoittelu"] == "K" and ($toim == "REKLAMAATIO" or $toim == "EXTRANET_REKLAMAATIO") and $var != "H") {

                      $palautus = hae_hyvityshinta($laskurow["liitostunnus"], $tuoteno, $kpl);

                      foreach ($palautus as $inx => $arvot) {

                        $tuoteno = $palautus[$inx]["tuoteno"];
                        $hinta = $palautus[$inx]["hinta"];
                        $ale1 = $palautus[$inx]["ale"];

                        for ($alepostfix = 2; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
                          ${'ale'.$alepostfix} = 0;
                        }

                        $kpl = $palautus[$inx]["kpl"] * -1;
                        if (stripos($kommentti, $palautus[$inx]["kommentti"]) === FALSE) $kommentti = $palautus[$inx]["kommentti"];
                        $hyvityssaannon_palautuskielto = $palautus[$inx]["palautuskielto"];

                        require "lisaarivi.inc";
                      }

                      unset($hyvityssaannon_palautuskielto);
                    }
                    else {
                      require "lisaarivi.inc";
                    }
                  }
                }
                else {
                  echo "<font class='message'>".t("Tuotenumeroa")." $tuoteno ".t("ei löydy")."!</font><br>";
                }

              }
              else {
                echo "<font class='message'>".t("Tuotenumeroa")." $tuoteno ".t("ei löydy")."!</font><br>";
              }
            }
          }
        }
      }
    }
    else {
      // Tiedostosta päivitys
      foreach ($excelrivi as $rivi) {
        $rivilask1++;

        // tila==G, alatila==V
        if ($toim == "MYYNTITILI" and $laskurow['tila'] == "G" and $laskurow['alatila'] == "V" and $laskurow['tilaustyyppi'] == 'M') {
          $var = $rivi[7];
          $tuoteno = $rivi[0];
          $kpl = $rivi[1];
          $rivitunnus = 0;

          // Tarkistukset, var ja kpl
          if ($var != 'A' and $var != 'B'  ) {
            echo "<font class='error'>".t("Väärä arvo kentässä var! Sallitut arvot ovat: A tai B").". ".t("Rivillä").": $rivilask1. '$var'"."</font><br><br>";
            continue;
          }

          $query = "SELECT tunnus
                    FROM tilausrivi
                    WHERE yhtio='$kukarow[yhtio]'
                    AND tyyppi = 'G'
                    AND var    = ''
                    AND otunnus='$kukarow[kesken]'
                    AND tuoteno='$tuoteno'";

          $result = pupe_query($query);

          if (mysql_num_rows($result) == 1) {
            $row = mysql_fetch_assoc($result);
            $rivitunnus = $row['tunnus'];
          }
          else {
            $query = "SELECT tunnus
                      FROM tilausrivi
                      WHERE yhtio='$kukarow[yhtio]'
                      AND tyyppi = 'G'
                      AND var    = ''
                      AND otunnus='$kukarow[kesken]'
                      AND tuoteno='$tuoteno'
                      AND kpl    = '$kpl'";

            $result = pupe_query($query);

            if (mysql_num_rows($result) == 1) {
              $row = mysql_fetch_assoc($result);
              $rivitunnus = $row['tunnus'];
            }
          }

          if ($rivitunnus != 0) {
            $tilatapa = "PAIVITA";
            $myyntitilirivit = $var;
            require "laskuta_myyntitilirivi.inc";
          }
          else {
            echo "<font class='error'>".t("Tuotetta ei osattu valita")."</font><br>";
          }
        }
        else {

          $tila    = '';
          $valinta = '';

          // Detektoidaan tiedostomuoto
          if ($filetyyppi == "" and $laskurow["tila"] == "V") {
            // Valmistukset aina tyyppiä kolme
            $filetyyppi = "KOLMONE";

            $ind_Tuoteno = 0;
            $ind_Maara = 1;

            if ($yhtiorow["splittauskielto"] == "K" or $toim == "YLLAPITO" or (($toim == 'VALMISTAVARASTOON' or $toim == 'VALMISTAASIAKKAALLE') and $yhtiorow['valmistuksien_kasittely'] == 'Y')) {
              $ind_Arvioitu = 2;
              $ind_Hinta = 3;

              $_indx = 4;
            }
            else {
              $ind_Hinta = 2;

              $_indx = 3;

            }

            for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
              ${'ind_Ale'.$alepostfix} = $_indx;
              $_indx++;
            }

            $ind_Netto                   = $_indx;
            $_indx++;
            $ind_Var                     = $_indx;
            $_indx++;
            $ind_Kommentti               = $_indx;
            $_indx++;
            $ind_Valmistuslinja          = $_indx;
            $_indx++;
            $ind_ToivottuKeraysajankohta = 9;

            // Jos ensimmäisestä solusta löytyy sana "tuote", niin käyttäjä on antanu sarakeotsikot
            if (stripos($rivi[0], "tuote") !== FALSE) {
              // Skipataan otsikot
              continue;
            }
          }
          elseif ($filetyyppi == ""
            and stripos($rivi[0], "tuote") !== FALSE
            and (stripos($rivi[1], "kpl") !== FALSE or stripos($rivi[1], "määrä") !== FALSE)
            and stripos($rivi[2], "päivä") !== FALSE
            and stripos($rivi[3], "nimi") !== FALSE) {

            $filetyyppi = "KAKKONEN";

            $ind_Tuoteno   = 0;
            $ind_Maara     = 1;

            if ($yhtiorow["splittauskielto"] == "K" or $toim == "YLLAPITO" or (($toim == 'VALMISTAVARASTOON' or $toim == 'VALMISTAASIAKKAALLE') and $yhtiorow['valmistuksien_kasittely'] == 'Y')) {
              $ind_Arvioitu = 2;
              $ind_Hinta = 4;

              $_indx = 5;
            }
            else {
              $ind_Hinta = 3;

              $_indx = 4;

            }

            for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
              ${'ind_Ale'.$alepostfix} = $_indx;
              $_indx++;
            }

            $ind_Netto     = $_indx;
            $_indx++;
            $ind_Var     = $_indx;
            $_indx++;
            $ind_Kommentti  = $_indx;

            // Skipataan otsikot
            continue;
          }
          elseif ($filetyyppi == ""
            and stripos($rivi[0], "tuote") !== FALSE
            and (stripos($rivi[1], "kpl") !== FALSE or stripos($rivi[1], "määrä") !== FALSE)
            and stripos($rivi[2], "päivä") !== FALSE
            and stripos($rivi[3], "hinta") !== FALSE) {

            $filetyyppi = "YKSPISTEVITONEN";

            $ind_Tuoteno   = 0;
            $ind_Maara     = 1;

            if ($yhtiorow["splittauskielto"] == "K" or $toim == "YLLAPITO" or (($toim == 'VALMISTAVARASTOON' or $toim == 'VALMISTAASIAKKAALLE') and $yhtiorow['valmistuksien_kasittely'] == 'Y')) {
              $ind_Arvioitu = 2;
              $ind_Hinta    = 3;

              $_indx = 4;
            }
            else {
              $ind_Hinta = 2;

              $_indx = 3;

            }

            for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
              ${'ind_Ale'.$alepostfix} = $_indx;
              $_indx++;
            }

            $ind_Netto     = $_indx;
            $_indx++;
            $ind_Var     = $_indx;
            $_indx++;
            $ind_Kommentti  = $_indx;

            // Skipataan otsikot
            continue;
          }
          elseif ($filetyyppi == "") {

            $filetyyppi = "YKKÖNEN";

            $ind_Tuoteno   = 0;
            $ind_Maara     = 1;

            if ($yhtiorow["splittauskielto"] == "K" or $toim == "YLLAPITO" or (($toim == 'VALMISTAVARASTOON' or $toim == 'VALMISTAASIAKKAALLE') and $yhtiorow['valmistuksien_kasittely'] == 'Y')) {
              $ind_Arvioitu = 2;
              $ind_Hinta = 3;

              $_indx = 4;
            }
            else {
              $ind_Hinta = 2;

              $_indx = 3;

            }

            for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
              ${'ind_Ale'.$alepostfix} = $_indx;
              $_indx++;
            }

            $ind_Netto     = $_indx;
            $_indx++;
            $ind_Var     = $_indx;
            $_indx++;
            $ind_Kommentti  = $_indx;
          }

          $tuoteno = trim($rivi[$ind_Tuoteno]);
          $kpl   = str_replace(",", ".", $rivi[$ind_Maara]);

          $toimaika      = "";
          $kerayspvm      = "";
          $hinta        = "";
          $netto        = "";
          $var        = "";
          $alv        = "";
          $paikka        = "";
          $varasto       = $laskurow["varasto"];
          $rivitunnus      = "";
          $korvaavakielto    = "";
          $varataan_saldoa  = "";
          $perhekielto    = "";
          $jtkielto        = $laskurow['jtkielto'];

          for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
            ${'ale'.$alepostfix} = "";
          }

          if ($kukarow["extranet"] == "") {

            //Toimitusaika
            if ($yhtiorow["splittauskielto"] == "K" or $toim == "YLLAPITO" or (($toim == 'VALMISTAVARASTOON' or $toim == 'VALMISTAASIAKKAALLE') and $yhtiorow['valmistuksien_kasittely'] == 'Y')) {
              $toimaika = choose_correct_date($rivi[$ind_Arvioitu]);
            }
            else {
              $toimaika = "";
            }

            //Keräyspäivämäärä
            if (($toim == 'VALMISTAVARASTOON' or $toim == 'VALMISTAASIAKKAALLE') and $yhtiorow['valmistuksien_kasittely'] == 'Y') {
              $kerayspvm = choose_correct_date($rivi[$ind_ToivottuKeraysajankohta]);
            }
            else {
              $kerayspvm = "";
            }

            $hinta = str_replace(",", ".", $rivi[$ind_Hinta]);

            for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
              ${'ale'.$alepostfix} = str_replace(",", ".", $rivi[${'ind_Ale'.$alepostfix}]);
            }

            $netto         = strtoupper(trim($rivi[$ind_Netto]));
            $var        = strtoupper(trim($rivi[$ind_Var]));
            $kommentti    = trim($rivi[$ind_Kommentti]);
            $valmistuslinja  = trim($rivi[$ind_Valmistuslinja]);

            if ($toim == "EXTENNAKKO" and $var != '' and $var != 'O') {
              echo t("Väärä arvo kentässä var! Sallitut arvot ovat").": 'O'. ".t("Rivillä").": $rivilask1. $var<br>";
              $var = '';
            }
            elseif ($toim != "EXTENNAKKO" and $var != '' and $var != 'P' and $var != 'H' and $var != 'J') {
              echo t("Väärä arvo kentässä var! Sallitut arvot ovat").": 'P','H','J'. ".t("Rivillä").": $rivilask1. $var<br>";
              $var = '';
            }

            if (!in_array($netto, array('','E','N'))) {
              echo t("Väärä arvo kentässä netto! Sallitut arvot ovat: tyhjä, E tai N").". ".t("Rivillä").": $rivilask1. $netto<br>";
              $netto = '';
            }
          }

          if ($tuoteno != '' and $kpl != 0) {

            // Tuotehaku ei toimi extranetin puolella
            if (file_exists("../inc/tuotehaku.inc")) {
              require "../inc/tuotehaku.inc";
            }

            if ($mikrotila_tuotenumero == "koodilla") {
              $query = "SELECT *
                        FROM tuote
                        WHERE eankoodi='$tuoteno' and yhtio='$kukarow[yhtio]'";
            }
            else {
              $query = "SELECT *
                        FROM tuote
                        WHERE tuoteno='$tuoteno' and yhtio='$kukarow[yhtio]'";
            }

            $rarresult = pupe_query($query);

            if (mysql_num_rows($rarresult) == 1) {

              $trow = mysql_fetch_array($rarresult);

              $ohitus = 0;

              if ($kukarow["extranet"] != "") {
                if (!saako_myyda_private_label($kukarow["oletus_asiakas"], $trow["tuoteno"], $kpl)) {
                  $ohitus = 1;
                }
              }

              if ($ohitus == 0) {
                $tuoteno = $trow["tuoteno"];

                if ($toimaika == '') {
                  $toimaika = $laskurow["toimaika"];
                }

                if ($kerayspvm == "") {
                  $kerayspvm = $laskurow["kerayspvm"];
                }

                if ($hinta == 0) {
                  $hinta = "";
                }

                // Ennakkotilaukset, Tarjoukset, Ylläpitosopimukset ja Valmistukset eivät tee saldotsekkiä
                if ($laskurow["tilaustyyppi"] == "E" or $laskurow["tila"] == "T" or $laskurow["tilaustyyppi"] == "0" or $laskurow["tila"] == "V") {
                  $varataan_saldoa = "EI";
                }
                else {
                  $varataan_saldoa = "";
                }

                //lisätään rivi
                if ($kukarow["extranet"] == "" and $tila == "MYYNTITILIRIVI") {
                  $tilatapa = "PAIVITA";
                  require "laskuta_myyntitilirivi.inc";
                }
                elseif ($laskurow["tila"] == "V") {
                  // Valmistuksilla pitää huomioida rekursiiviset reseptit
                  // funktio populoi globaalit muuttujat $tuoteno_array $kpl_array $kommentti_array $lapsenlap_array
                  $tuoteno_array = array($tuoteno);
                  $kpl_array     = array($tuoteno => $kpl);

                  pupesoft_lisaa_valmisteen_rekursiiviset_reseptit();

                  foreach ($tuoteno_array as $tuoteno_temp) {

                    $query = "SELECT *
                              FROM tuote
                              WHERE yhtio = '{$kukarow["yhtio"]}'
                              AND tuoteno = '{$tuoteno_temp}'";
                    $tuotearray_result = pupe_query($query);
                    $tuotearray_row = mysql_fetch_array($tuotearray_result);

                    if (isset($kommentti_array[$tuoteno_temp])) {
                      $kommentti = $kommentti_array[$tuoteno_temp];
                    }

                    $params = array(
                      "trow"            => $tuotearray_row,
                      "laskurow"        => $laskurow,
                      "kpl"             => $kpl_array[$tuoteno_temp],
                      "tuoteno"         => $tuoteno_temp,
                      "hinta"           => 0,
                      "varasto"         => $varasto,
                      "varataan_saldoa" => "EI",
                      "kommentti"       => $kommentti,
                      'valmistuslinja'  => $valmistuslinja,
                      'toimaika'        => $toimaika,
                      'kerayspvm'       => $kerayspvm,
                      'toim'            => "{$toim}",
                    );

                    lisaa_rivi($params);
                  }

                  // Nämä pitää unsetata, muuten tilaus_myynti lisää rivit uudestaan
                  unset($tuoteno_array, $kpl_array, $kommentti_array, $lapsenlap_array);
                }
                else {
                  $from_mikrotilaus = TRUE;
                  if ($yhtiorow["reklamaation_hinnoittelu"] == "K" and ($toim == "REKLAMAATIO" or $toim == "EXTRANET_REKLAMAATIO") and $var != "H") {

                    $palautus = hae_hyvityshinta($laskurow["liitostunnus"], $tuoteno, $kpl);

                    foreach ($palautus as $inx => $arvot) {

                      $tuoteno = $palautus[$inx]["tuoteno"];
                      $hinta = $palautus[$inx]["hinta"];
                      $ale1 = $palautus[$inx]["ale"];

                      for ($alepostfix = 2; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
                        ${'ale'.$alepostfix} = 0;
                      }

                      $kpl = $palautus[$inx]["kpl"] * -1;
                      if (stripos($kommentti, $palautus[$inx]["kommentti"]) === FALSE) $kommentti = $palautus[$inx]["kommentti"];
                      $hyvityssaannon_palautuskielto = $palautus[$inx]["palautuskielto"];

                      require "lisaarivi.inc";
                    }

                    unset($hyvityssaannon_palautuskielto);
                  }
                  else {
                    require "lisaarivi.inc";
                  }
                }
              }
              else {
                echo "<font class='message'>".t("Tuotenumeroa")." $tuoteno ".t("ei löydy")."!</font><br>";
              }

            }
            else {
              echo "<font class='message'>".t("Tuotenumeroa")." $tuoteno ".t("ei löydy")."!</font><br>";
            }
          }
        }
      }
    }


    $tuoteno  = '';
    $kpl    = '';
    $var    = '';
    $hinta    = '';
    $netto    = '';
    $rivitunnus  = '';
    $kommentti  = '';
    $kerayspvm  = '';
    $toimaika  = '';
    $paikka    = '';
    $alv    = '';
    $varasto   = '';
    $perheid   = '';
    $perheid2    = '';
    $tee     = 'Y';
    $lopetus = '';

    for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
      ${'ale'.$alepostfix} = '';
    }
  }
  else {
    $tuoteno  = '';
    $kpl    = '';
    $var    = '';
    $hinta    = '';
    $netto    = '';
    $rivitunnus  = '';
    $kommentti  = '';
    $kerayspvm  = '';
    $toimaika  = '';
    $paikka    = '';
    $alv    = '';
    $varasto   = '';
    $perheid   = '';
    $perheid2    = '';
    $tee     = 'Y';
    $lopetus = '';

    for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
      ${'ale'.$alepostfix} = '';
    }
  }
}

if ($tee == 'mikrotila') {
  if ( ($toim == 'VALMISTAVARASTOON' or $toim == 'VALMISTAASIAKKAALLE') and $yhtiorow['valmistuksien_kasittely'] == 'Y') {
    $colspan = 11;
  }
  else {
    $colspan = 8;
  }

  $colspan += $yhtiorow['myynnin_alekentat'];

  echo "<font class='head'>$otsikko</font><hr>";

  echo "<form method='post' name='sendfile' enctype='multipart/form-data'>
      <input type='hidden' name='toim' value='$toim'>
      <input type='hidden' name='lopetus' value='$lopetus'>
      <input type='hidden' name='tilausnumero' value='$tilausnumero'>
      <input type='hidden' name='tee' value='file'>

      <font class='message'>".t("Tiedostomuoto").":</font><br><br>

      <table>
      <tr><th colspan='{$colspan}'>".t("Sarkaineroteltu tekstitiedosto tai Excel-tiedosto").".</th></tr>
      <tr>";

  echo "  <td>".t("Tuoteno")."</td><td>".t("Määrä")."</td>";

  if ($kukarow["extranet"] == "") {

    if (($toim == 'VALMISTAVARASTOON' or $toim == 'VALMISTAASIAKKAALLE') and $yhtiorow['valmistuksien_kasittely'] == 'Y') {
      echo "<td>".t("Valmistusajankohta")." ".t("(vvvv-kk-pp)")."</td>";
      echo "<td>".t("Hinta")."</td>";

      for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
        echo "<td>".t("Ale{$alepostfix}")."</td>";
      }

      echo "<td>".t("Netto")."</td>";
      echo "<td>".t("Tila")."</td>";
      echo "<td>".t("Kommentti")."</td>";
      echo "<td>".t('Valmistuslinja')."</td>";
      echo "<td>".t('Keräysajankohta')." ".t("(vvvv-kk-pp)")."</td>";
    }
    else {
      if ($yhtiorow["splittauskielto"] == "K" or $toim == "YLLAPITO") {
        echo "<td>".t("Arvioitu toimituspäivä")." ".t("(vvvv-kk-pp)")."</td>";
      }

      echo "<td>".t("Hinta")."</td>";

      for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
        echo "<td>".t("Ale{$alepostfix}")."</td>";
      }

      echo "<td>".t("Netto")."</td>";
      echo "<td>".t("Tila")."</td>";
      echo "<td>".t("Kommentti")."</td>";
    }
  }

  echo "</tr>";
  echo "</table>";

  echo "<br/>";

  echo "<table>";

  echo "<tr>";
  echo "<th>".t("Valitse tiedosto").":</th>";
  echo "<td>";
  echo "<input name='userfile' type='file' />";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>".t("Tuotteet").":</th>";
  echo "<td>";
  echo "<select name='mikrotila_tuotenumero'>";
  echo "<option value='tuote'>".t("Tuotenumerolla")."</option>";
  echo "<option value='koodilla'>".t("EAN-Koodilla")."</option>";
  echo "</select>";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>".t('Lue excel useammalta välilehdeltä')."</th>";
  echo "<td>";
  echo "<input type='checkbox' name='lue_useammalta_valilehdelta' />";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td colspan='2' class='back'>";
  echo "<input type='submit' value='".t("Läheta")."' />";
  echo "</td>";
  echo "</tr>";

  echo "</table>";

  echo "</form>";
}
$from_mikrotilaus = FALSE;
