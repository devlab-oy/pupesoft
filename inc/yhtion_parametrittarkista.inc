<?php

if (!function_exists("yhtion_parametrittarkista")) {
  function yhtion_parametrittarkista(&$t, $i, $result, $tunnus, &$virhe, $trow) {
    global $kukarow, $yhtiorow, $alias_set, $tiedostopaate;

    static  $verkkolasku_lah_talteen,
    $verkkolasku_lah_talteen_i,
    $maksuehto_tuotenumero,
    $rahti_tuotenumero,
    $jalkivaatimus_tuotenumero,
    $laskutuslisa_tuotenumero,
    $ennakkomaksu_tuotenumero,
    $kasittelykulu_tuotenumero,
    $alennus_tuotenumero,
    $kuljetusvakuutus_tuotenumero,
    $erilliskasiteltava_tuotenumero,
    $ennakkomaksu_tuotenumero,
    $lisakulu_tuotenumero,
    $ind_maksuehto_tuotenumero,
    $ind_rahti_tuotenumero,
    $ind_jalkivaatimus_tuotenumero,
    $ind_laskutuslisa_tuotenumero,
    $ind_ennakkomaksu_tuotenumero,
    $ind_kasittelykulu_tuotenumero,
    $ind_alennus_tuotenumero,
    $ind_kuljetusvakuutus_tuotenumero,
    $ind_erilliskasiteltava_tuotenumero,
    $ind_ennakkomaksu_tuotenumero,
    $ind_lisakulu_tuotenumero;

    $fieldname = mysql_field_name($result, $i);

    if ($fieldname == "admin_email" or
      $fieldname == "alert_email" or
      $fieldname == "talhal_email" or
      $fieldname == "postittaja_email") {

      if (trim($t[$i]) == '') {
        $virhe[$i] = t("Tieto puuttuu!");
      }
    }

    // Poistetaan spacet
    if ($fieldname == "finvoice_senderpartyid" or $fieldname == "finvoice_senderintermediator") {
      $t[$i] = str_replace(" ", "", $t[$i]);
    }

    if ($fieldname == "kuvapankki_polku") {
      if ($t[$i] != "" and !is_writable($t[$i]."/".$kukarow['yhtio'])) {
        $virhe[$i] = "<font class='error'>Kansiossa ei ole kirjoitusoikeutta!<br></font>";
      }
    }

    if ($fieldname == "pankkitiedostot") {
      $query = "SELECT count(*) AS kpl
                FROM lasku
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tila    = 'P'";
      $result = pupe_query($query);
      $row = mysql_fetch_assoc($result);

      if ($row['kpl'] > 0 and $t[$i] != $trow["pankkitiedostot"]) {
        $virhe[$i]  = "<font class='error'>";
        $virhe[$i] .= t("Et voi vahtaa luontitapaa, koska sinulla on poimittuja laskuja!");
        $virhe[$i] .= "<br></font>";
      }
    }

    if ($fieldname == "maksuehto_tuotenumero" or
      $fieldname == "rahti_tuotenumero" or
      $fieldname == "jalkivaatimus_tuotenumero" or
      $fieldname == "laskutuslisa_tuotenumero" or
      $fieldname == "ennakkomaksu_tuotenumero" or
      $fieldname == "kasittelykulu_tuotenumero" or
      $fieldname == "alennus_tuotenumero" or
      $fieldname == "kuljetusvakuutus_tuotenumero" or
      $fieldname == "erilliskasiteltava_tuotenumero" or
      $fieldname == "ennakkomaksu_tuotenumero" or
      $fieldname == "lisakulu_tuotenumero") {

      $tuote = mysql_real_escape_string(trim($t[$i]));

      ${$fieldname} = $tuote;
      ${"ind_".$fieldname} = $i;

      if ($tuote != "") {
        // katotaan lˆytyykˆ t‰ll‰nen saldoton tuote
        $query = "SELECT tunnus
                  FROM tuote
                  WHERE yhtio    = '$kukarow[yhtio]'
                  AND tuoteno    = '$tuote'
                  AND tuoteno   != ''
                  AND ei_saldoa != ''";
        $xyresult = pupe_query($query);

        if (mysql_num_rows($xyresult) == 0) {
          $virhe[$i] = t("Tuotetta ei lˆydy!")." / ".t("Tuote pit‰‰ olla saldoton!");
        }
      }
    }

    if ($fieldname == "myynnin_alekentat_muokkaus") {
      if (count($t[$i]) > 0) {
        if (count($t[$i]) == 1 and $t[$i][0] == 'default') {
          unset($t[$i]);
          $t[$i] = '';
        }
        else {
          unset($t[$i][0]);
          $t[$i] = implode(",", $t[$i]);
        }
      }
    }

    if ($fieldname == "synkronoi") {

      if ($t[$i]!="") {
        $t[$i]=str_replace("'", "", $t[$i]);
        if (strpos($t[$i], ",")) {
          $taulut=explode(",", $t[$i]);
        }
        else {
          $taulut=array($t[$i]);
        }

        $sallitut = array(
          'asiakas',
          'asiakasalennus',
          'asiakashinta',
          'avainsana',
          'dynaaminen_puu',
          'perusalennus',
          'puun_alkio',
          'rahtisopimukset',
          'toimi',
          'tuote',
          'tuoteperhe',
          'tuotteen_toimittajat',
          'yhteyshenkilo',
        );

        $sallitut_avainsana = array(
          'ASAVAINSANA',
          'OSASTO',
          'TRY',
          'Y',
        );

        if (count($taulut)>0) {
          foreach ($taulut as $taulu) {

            if (strpos($taulu, "avainsana")!==false) {
              $taululisat = explode("|", substr($taulu, 10));
              $taulu = substr($taulu, 0, 9);
              if (count($taululisat)>0) {
                foreach ($taululisat as $taululisa) {
                  if (!in_array($taululisa, $sallitut_avainsana)) {
                    if ($virhe[$i]!="") {
                      $virhe[$i] .= "<br>";
                    }
                    $virhe[$i] .= t("Lis‰tieto ei kelpaa")." '$taululisa'. ".t("Sallitut lisat ovat")." ".implode(", ", $sallitut_avainsana);
                  }
                }
              }
              else {
                if ($virhe[$i]!="") {
                  $virhe[$i] .= "<br>";
                }
                $virhe[$i] .= t("Lis‰tiedot vaaditaan").". ".t("Sallitut lisat ovat")." ".implode(", ", $sallitut_avainsana);
              }
            }

            $query="show tables like '$taulu'";
            $taulures=pupe_query($query);
            if (mysql_num_rows($taulures)==0 or !in_array($taulu, $sallitut)) {
              if ($virhe[$i]!="") {
                $virhe[$i] .= "<br>";
              }
              $virhe[$i] .= t("Taulu ei kelpaa")." '$taulu' ".t("sallitut taulut ovat")." ".implode(", ", $sallitut);
            }

            if (synkronoi_tarkista_pakolliset($taulu) === false) {
              $virhe[$i] .= "VIRHE: '$taulu' -taulun synkronoi_ainoastaan virheellinen. Pakolliset kent‰t on pakko synkronoida!";
            }
          }
        }
      }
    }

    if ($fieldname == "lasku_logo") {
      $tiedostopaate = array("JPG");
    }

    if ($fieldname == "logo") {
      $tiedostopaate = array("JPG", "PNG", "GIF");
    }

    // Talenom, SEPA ja PPG aina v‰hint‰‰n Finvoice 2.01-veriota
    if ($fieldname == "finvoice_versio" and empty($t[$i]) and in_array($verkkolasku_lah_talteen, array("ppg","sepa","talenom"))) {
      $t[$i] = "2";
    }

    if ($fieldname == "maventa_api_avain") {
      if ($verkkolasku_lah_talteen == "maventa" and trim($t[$i]) == "") {
        $virhe[$i] = t("Pakollinen: maventa_api_avain ja maventa_ohjelmisto_api_avain!!");
        $virhe[$verkkolasku_lah_talteen_i] = t("Pakollisia: maventa_api_avain ja maventa_ohjelmisto_api_avain!!");
      }
    }

    if ($fieldname == "maventa_ohjelmisto_api_avain") {

      if ($verkkolasku_lah_talteen == "maventa" and trim($t[$i]) == "") {
        $virhe[$i] = t("Pakollinen:  maventa_ohjelmisto_api_avain!!");
        $virhe[$verkkolasku_lah_talteen_i] = t("Pakollisia: maventa_api_avain ja maventa_ohjelmisto_api_avain!!");
      }
    }

    if ($fieldname == "apix_tunnus") {
      if ($verkkolasku_lah_talteen == "apix" and trim($t[$i]) == "") {
        $virhe[$i] = t("Pakollinen: apix_tunnus!");
        $virhe[$verkkolasku_lah_talteen_i] = t("Pakollisia: apix_tunnus ja apix_avain!!");
      }
    }

    if ($fieldname == "apix_avain") {
      if ($verkkolasku_lah_talteen == "apix" and trim($t[$i]) == "") {
        $virhe[$i] = t("Pakollinen: apix_avain!");
        $virhe[$verkkolasku_lah_talteen_i] = t("Pakollisia: apix_tunnus ja apix_avain!");
      }
    }

    if ($fieldname == "verkkolasku_lah") {
      $verkkolasku_lah_talteen = trim($t[$i]);
      $verkkolasku_lah_talteen_i = $i;
    }

    if ($fieldname == "erapaivan_ylityksen_raja") {
      $t[$i] = (int) trim($t[$i]);
      if ($t[$i] < 1) {
        $virhe[$i] = t("Arvon oltava kokonaisluku ja v‰hint‰‰n 1") . "!";
      }
    }

    if ($fieldname == "lahetteen_tulostustapa") {
      if (in_array($t[$i], array('B', 'I')) and !empty($t['kerayserat'])) {
        //jos lahetteen_tulostustapa on asennossa:
        //"Myyntitilausten ker‰yslistat ja l‰hetteet tulostetaan automaattisesti"
        //tai "Myyntitilausten ker‰yslistat ja l‰hetteet tulostetaan automaattisesti ja tilaus laitetaan toimitetuksi"
        //ja kerayser‰t on p‰‰ll‰
        $virhe[$i] = t("Ker‰yser‰t ei voi olla p‰‰ll‰ jos l‰hete halutaan tulostaa automaattisesti") . "!";
      }
    }

    if ($fieldname == "raaka_aine_tiliointi") {
      // Tarkistetaan, ett‰ sek‰, raaka-ainevarasto ja raaka-ainevarastonmuutos tilit ovat olemassa
      $query = "SELECT COUNT(*) AS tilien_maara
                FROM tili
                WHERE tili.yhtio = '{$kukarow['yhtio']}'
                AND tili.tilino  IN ('{$yhtiorow['raaka_ainevarasto']}',
                                    '{$yhtiorow['raaka_ainevarastonmuutos']}')";

      $result = pupe_query($query);
      $tilien_maara = mysql_fetch_assoc($result);
      $tilien_maara = $tilien_maara['tilien_maara'];

      if ($tilien_maara != 2) {
        $virhe[$i] = t("Raaka-ainetilej‰ ei ole m‰‰ritelty oikein");
      }
    }

    if ($fieldname == "tunnus") {
      // Tsekataan, ett‰ tietyt tuotteen ei oo samoja kesken‰‰n
      if ($rahti_tuotenumero != "" and $rahti_tuotenumero == $erilliskasiteltava_tuotenumero) {
        $virhe[$i] = $virhe[$ind_erilliskasiteltava_tuotenumero] = t("Erillisk‰sitelt‰v‰kulun tuotenumero ei voi olla sama kuin Rahtikulun tuotenumero")."!";
      }

      if ($rahti_tuotenumero != "" and $rahti_tuotenumero == $lisakulu_tuotenumero) {
        $virhe[$i] = $virhe[$ind_lisakulu_tuotenumero] = t("Lis‰rahtikulun tuotenumero ei voi olla sama kuin Rahtikulun tuotenumero")."!";
      }

      if ($rahti_tuotenumero != "" and $rahti_tuotenumero == $kuljetusvakuutus_tuotenumero) {
        $virhe[$i] = $virhe[$ind_kuljetusvakuutus_tuotenumero] = t("Kuljetusvakuutuksen tuotenumero ei voi olla sama kuin Rahtikulun tuotenumero")."!";
      }
    }
  }
}
