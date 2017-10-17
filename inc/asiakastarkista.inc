<?php

if (!function_exists("asiakastarkista")) {
  function asiakastarkista(&$t, $i, $result, $tunnus, &$virhe, $trow) {
    global $kukarow, $yhtiorow, $alias_set, $ulosarray, $ytunnus_select, $asiakasnro_select, $toim, $asiak_laji;

    static $ytunnus_tunnus, $ytunnus, $ovttunnus, $apuverkkotunnus, $apuverkkotunnusi, $apulasku_email, $apulasku_emaili, $apu_maa, $apu_toim_maa, $laskutus_talteen_i, $laskutus_talteen;

    if (mysql_field_name($result, $i) == "valkoodi") {
      if (trim($t[$i]) == '') {
        $virhe[$i] = t("Tieto puuttuu");
      }
    }

    if (mysql_field_name($result, $i) == "laji") {
      $asiak_laji = $t[$i];
    }

    if (mysql_field_name($result, $i) == "ytunnus") {
      if ($ytunnus_select != '') {
        // Jos selectoitu drop downista niin katotaan, että tämä on vielä vapaa
        //jos konsernin asiakkaat synkronoidaan niin asiakkaiden yksilöivät tiedot on oltava konsernitasolla-yksilölliset
        $query = "SELECT group_concat(concat('\'',yhtio.yhtio,'\'')) yhtiot
                  FROM yhtio
                  JOIN yhtion_parametrit ON yhtion_parametrit.yhtio=yhtio.yhtio
                  where konserni = '$yhtiorow[konserni]'
                  and (synkronoi = '$toim' or synkronoi like '$toim,%' or synkronoi like '%,$toim,%' or synkronoi like '%,$toim')";
        $sresult = pupe_query($query);
        $srowapu = mysql_fetch_array($sresult);

        if ($srowapu["yhtiot"] != "") {
          $tarkyhtio = $srowapu["yhtiot"];
        }
        else {
          $tarkyhtio = "'$kukarow[yhtio]'";
        }

        $query   = "select tunnus from asiakas where yhtio in ($tarkyhtio) and ytunnus='$ytunnus_select' and tunnus != '$tunnus'";
        $sresult = pupe_query($query);

        if (mysql_num_rows($sresult) > 0) {
          if ($alias_set == "VAHITTAISMYYNTI") {
            $virhe[$i] = t("Valitsemasi asiakasnumero ei ollutkaan vapaa.");

          }
          else {
            $virhe[$i] = t("Valitsemasi ytunnus ei ollutkaan vapaa.");
          }
        }
        else {
          $t[$i] = $ytunnus_select;
        }
      }
    }

    if (mysql_field_name($result, $i) == "ytunnus" and $virhe[$i] == "") {

      $ytunnus = $t[$i];
      $ytunnus_tunnus = $i;

      if ($ytunnus == "") {
        $virhe[$i] = t("Y-tunnus puuttuu")."!";
      }
      elseif ($yhtiorow["myyntitilaus_saatavat"] == "Y") {

        $qry = "SELECT luottoraja
                FROM asiakas
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND ytunnus = '{$ytunnus}'";
        $res = pupe_query($qry);

        if (mysql_num_rows($res) > 0) {
          if ($alias_set != "PROSPEKTI") {
            $t['luottoraja'] = mysql_result($res, 0);
          }
          elseif ($asiak_laji == "" and $alias_set == "PROSPEKTI") {
            // Päivitetään prospektien luottoraja,
            // mutta vasta kun tehdään prospektista normiasiakas
            $_luottoraja = mysql_result($res, 0);
            $qry = "UPDATE asiakas
                    SET luottoraja = '{$_luottoraja}'
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND tunnus  = '$tunnus'";
            pupe_query($qry);
          }
        }
      }
    }

    if (mysql_field_name($result, $i) == "luottoraja" and $yhtiorow["myyntitilaus_saatavat"] == "Y" and isset($t['luottoraja']) and $alias_set != "PROSPEKTI") {
      $t[$i] = $t['luottoraja'];
    }

    if (mysql_field_name($result, $i) == "ytunnus" and $yhtiorow['asiakkaan_tarkenne'] == 'B') {

      $query   = "SELECT tunnus from asiakas where yhtio = '$kukarow[yhtio]' and ytunnus='$ytunnus' and tunnus != '$tunnus'";
      $sresult = pupe_query($query);

      if (mysql_num_rows($sresult) > 0) {
        $virhe[$i] = t("Samalla tiedoilla on useita asiakkaita!");
      }
    }

    if ((mysql_field_name($result, $i) == "osasto" or mysql_field_name($result, $i) == "ryhma" or mysql_field_name($result, $i) == "piiri") and trim($t[$i]) != '') {

      if (mysql_field_name($result, $i) == 'osasto')  $kptyyppi = "ASIAKASOSASTO";
      if (mysql_field_name($result, $i) == 'ryhma')   $kptyyppi = "ASIAKASRYHMA";
      if (mysql_field_name($result, $i) == 'piiri')   $kptyyppi = "PIIRI";

      $tpque = "SELECT tunnus FROM avainsana WHERE yhtio = '$kukarow[yhtio]' and LAJI = '$kptyyppi' and SELITE = '$t[$i]'";
      $tpres = pupe_query($tpque);

      if (mysql_num_rows($tpres) == 0) {
        $virhe[$i] .= t("Avainsanaa ei löydy")."!";
      }
    }

    if (mysql_field_name($result, $i) == "erikoisale") {
      if (trim($t[$i]) > 100 or trim($t[$i]) < 0) {
        $virhe[$i] = t("Erikoisale pitää olla alle 100% ja yli 0%");
      }
    }

    if (mysql_field_name($result, $i) == "asiakasnro") {
      if ($asiakasnro_select != '') {
        // Jos selectoitu drop downista niin katotaan, että tämä on vielä vapaa
        //jos konsernin asiakkaat synkronoidaan niin asiakkaiden yksilöivät tiedot on oltava konsernitasolla-yksilölliset
        $query = "SELECT ifnull(group_concat(concat('\'',yhtio.yhtio,'\'')), '') yhtiot
                  FROM yhtio
                  JOIN yhtion_parametrit ON yhtion_parametrit.yhtio=yhtio.yhtio
                  where konserni = '$yhtiorow[konserni]'
                  and (synkronoi = '$toim' or synkronoi like '$toim,%' or synkronoi like '%,$toim,%' or synkronoi like '%,$toim')";
        $sresult = pupe_query($query);
        $srowapu = mysql_fetch_array($sresult);

        if ($srowapu["yhtiot"] != "") {
          $srowapu = mysql_fetch_array($sresult);

          if ($srowapu["yhtiot"] != '') {
            $tarkyhtio = $srowapu["yhtiot"];
          }
          else {
            $tarkyhtio = "'$kukarow[yhtio]'";
          }
        }
        else {
          $tarkyhtio = "'$kukarow[yhtio]'";
        }

        $query   = "SELECT tunnus from asiakas where yhtio in ($tarkyhtio) and asiakasnro='$asiakasnro_select' and tunnus != '$tunnus'";
        $sresult = pupe_query($query);

        if (mysql_num_rows($sresult) > 0) {
          $virhe[$i] = t("Valitsemasi asiakasnumero ei ollutkaan vapaa.");

        }
        else {
          $t[$i] = $asiakasnro_select;
        }
      }

      if ($yhtiorow['asiakkaan_tarkenne'] == 'A') {

        if ($asiakasnro_select != '') {
          $asiakas_numero = $asiakasnro_select;
        }
        else {
          $asiakas_numero = $t[$i];
        }

        $query   = "SELECT tunnus from asiakas where yhtio = '$kukarow[yhtio]' and ytunnus='$ytunnus' and asiakasnro='$asiakas_numero' and tunnus != '$tunnus'";
        $sresult = pupe_query($query);

        if (mysql_num_rows($sresult) > 0) {
          if ($alias_set == "VAHITTAISMYYNTI") {
            $virhe[$i] .= t("Samalla tiedoilla on useita asiakkaita! Lisää tarkenne Asiakasnumeroon/Henkilötunnukseen.");
          }
          else {
            $virhe[$i] .= t("Samalla tiedoilla on useita asiakkaita! Lisää tarkenne Ytunnukseen/Asiakasnumeroon.");
          }
        }
      }
    }

    if (mysql_field_name($result, $i) == "ovttunnus") {
      $ovttunnus = $t[$i];
    }

    if (mysql_field_name($result, $i) == "toim_ovttunnus" and $yhtiorow['asiakkaan_tarkenne'] == '') {

      $query   = "SELECT tunnus from asiakas where yhtio = '$kukarow[yhtio]' and ytunnus='$ytunnus' and ovttunnus='$ovttunnus' and toim_ovttunnus='$t[$i]' and tunnus != '$tunnus'";
      $sresult = pupe_query($query);

      if (mysql_num_rows($sresult) > 0) {
        if ($alias_set == "VAHITTAISMYYNTI") {
          $virhe[$i] = t("Samalla tiedoilla on useita asiakkaita! Lisää tarkenne Asiakasnumeroon/Henkilötunnukseen.");

        }
        else {
          $virhe[$i] = t("Samalla tiedoilla on useita asiakkaita! Lisää tarkenne Ytunnukseen/Ovttunnukseen/Toim_ovttunnukseen.");
        }
      }
    }

    if (mysql_field_name($result, $i) == "verkkotunnus") {
      // verkkotunnus tulee datassa ennen CHN kenttää, joten otetaan tämä talteen
      $apuverkkotunnus  = trim($t[$i]);
      $apuverkkotunnusi = $i;
    }

    if (mysql_field_name($result, $i) == "lasku_email") {
      // lasku_email tulee datassa ennen CHN kenttää, joten otetaan tämä talteen
      $apulasku_email  = trim($t[$i]);
      $apulasku_emaili = $i;
    }

    if ((mysql_field_name($result, $i) == "tilino" or
        mysql_field_name($result, $i) == "tilino_eu" or
        mysql_field_name($result, $i) == "tilino_ei_eu" or
        mysql_field_name($result, $i) == "tilino_marginaali" or
        mysql_field_name($result, $i) == "tilino_osto_marginaali") and $t[$i] != '') {

      $query = "SELECT yhtio
                FROM tili
                WHERE yhtio = '$kukarow[yhtio]'
                and tilino  = '$t[$i]'";
      $sresult = pupe_query($query);

      if (mysql_num_rows($sresult) != 1) {
        $virhe[$i] = t("Tiliä ei löydy");
      }
    }

    if (mysql_field_name($result, $i) == "chn") {
      if (isset($t[$i]) and trim($t[$i]) != '') {
        // jos ollaan valittu sähköposti niin lasku_email pitää syöttää!
        if (trim($t[$i]) == '666' and $apulasku_email == "") {
          // Pitää aina setata myös "tämän" kentän virhe, muuten luedata ei toimi
          $virhe[$i] = $virhe[$apulasku_emaili] = t("Olet valinnut laskutustavaksi sähköpostin ja lasku_email on tyhjä! Laskutus ei onnistu")."!";
        }

        // jos ollaan valittu jotain muuta kuin PAPERI tai EMAIL niin verkkotunnus pitää syöttää!
        if (trim($t[$i]) != '999'
          and trim($t[$i]) != '888'
          and trim($t[$i]) != '112'
          and trim($t[$i]) != '666'
          and trim($t[$i]) != '667'
          and trim($t[$i]) != '100'
          and trim($t[$i]) != '030'
          and $apuverkkotunnus == ""
          and $yhtiorow['verkkolasku_lah'] != 'apix') {
          // Pitää aina setata myös "tämän" kentän virhe, muuten luedata ei toimi
          $virhe[$i] = $virhe[$apuverkkotunnusi] = t("Olet valinnut laskutustavaksi muun kuin Paperi ja verkkotunnus on tyhjä! Laskutus ei onnistu")."!";
        }
      }
    }

    if ((mysql_field_name($result, $i) == "email" or mysql_field_name($result, $i) == "lasku_email") and trim($t[$i]) != '') {

      $email_virhe = "";
      // Tarkistetaan sähköpostiosoite (jos ei olla Virossa)
      if ($yhtiorow["maa"] != "EE") {
        $email_virhe = tarkista_email($t[$i]);
      }

      if ($email_virhe != "") {
        $virhe[$i] = t("Sähköpostin domain ei löydy")."! ($email_virhe)";
      }
    }

    if (mysql_field_name($result, $i) == "maksuehto" and $t[$i] > 0) {

      $query = "SELECT tunnus
                from maksuehto
                where yhtio='$kukarow[yhtio]'
                and tunnus='$t[$i]'
                and (sallitut_maat = '' or sallitut_maat like '%$apu_maa%')";
      $sresult = pupe_query($query);

      if (mysql_num_rows($sresult) == 0) {
        $virhe[$i] = t("Tätä maksuehtoa ei saa käyttää asiakkaalla tässä maassa.");
      }
    }

    if (mysql_field_name($result, $i) == "toimitustapa" and trim($t[$i]) != '') {

      $query   = "SELECT tunnus
                  from toimitustapa
                  where yhtio='$kukarow[yhtio]' and selite='$t[$i]'
                  and (sallitut_maat = '' or sallitut_maat like '%$apu_toim_maa%')";
      $sresult = pupe_query($query);

      if (mysql_num_rows($sresult) == 0) {
        $virhe[$i] = t("Tätä toimitustapaa ei saa käyttää asiakkaalla tässä maassa.");
      }
    }

    if (mysql_field_name($result, $i) == "toimitusvahvistus" and trim($t[$i]) == 'editilaus_out_futur.inc') {

      $query = "SELECT *
                FROM asiakkaan_avainsanat
                WHERE yhtio       = '{$kukarow['yhtio']}'
                AND liitostunnus  = '{$tunnus}'
                AND laji          IN ('futur_host','futur_path')
                AND avainsana    != ''";
      $chk_res = pupe_query($query);

      if (mysql_num_rows($chk_res) != 2) {
        $virhe[$i] = t("Tarvittavia tietoja puuttuu").". ".t("Lisää asiakkaan avainsanoihin Futur FTP -tiedot").".";
      }
    }

    if (mysql_field_name($result, $i) == "toim_maa") {
      $apu_toim_maa = $t[$i];
    }

    if (mysql_field_name($result, $i) == "maa") {

      $apu_maa = $t[$i];

      if (strtoupper($t[$i]) == 'FI' and $asiak_laji != "R" and $asiak_laji != "H" and ($yhtiorow["ytunnus_tarkistukset"] == "" or $yhtiorow["ytunnus_tarkistukset"] == "A")) {

        require "tarkistaytunnus.inc";

        if ($ok == 0) {
          // Pitää aina setata myös "tämän" kentän virhe, muuten luedata ei toimi
          $virhe[$i] = $virhe[$ytunnus_tunnus] = t("Virheellinen y-tunnus")."! $t[$i] -> $ytunnus";

        }
        else {
          $t[$ytunnus_tunnus] = $ytunnus;
        }
      }
    }

    if (mysql_field_name($result, $i) == "laskutus_nimi" and trim($t[$i]) != '') {
      $laskutus_talteen  = trim($t[$i]);
      $laskutus_talteen_i = $i;
    }

    if (mysql_field_name($result, $i) == "laskutus_osoite" or mysql_field_name($result, $i) == "laskutus_postino" or mysql_field_name($result, $i) == "laskutus_postitp") {

      if ($laskutus_talteen != "" and trim($t[$i]) == "") {
        $virhe[$i] = t("Pakollinen kenttä kun laskutus nimi on annettu")."!";
        $virhe[$laskutus_talteen_i] = t("Pakollisia: laskutusosoite, laskutus postinumero, laskutus postitoimipaikka")."!";
      }

      $laskutus_talteen = "";
      $laskutus_talteen_i = "";
    }
  }
}
