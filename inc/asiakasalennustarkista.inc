<?php

if (!function_exists("asiakasalennustarkista")) {
  function asiakasalennustarkista(&$t, $i, $result, $tunnus, &$virhe, $trow) {
    global $kukarow, $yhtiorow, $alias_set, $alasveto;

    static $chytunnus, $chasiakas_ryhma, $chtuoteno, $chryhma, $chminkpl, $chmonikerta, $chalkupvm, $chloppupvm, $chastunn, $chtuoteno_ind, $chsegmentti, $chpiiri, $chalennuslaji;

    if (mysql_field_name($result, $i) == "alennus") {
      if (trim($t[$i]) == '') {
        $virhe[$i] = t("Tieto puuttuu!");
      }

      if (abs(trim($t[$i])) > 100 and (float) trim($t[$i]) != 999.99 and (float) trim($t[$i]) != -999.99) {
        $virhe[$i] = t("Alennus ei voi olla yli 100%");
      }

      if ($chryhma == "**" and (float) trim($t[$i]) < 0) {
        $virhe[$i] = t("Katemyyntihintaa ei voi vielä sitoa kaikkiin tuotteisiin");
      }
    }

    if (mysql_field_name($result, $i) == "ytunnus") {
      $chytunnus = trim($t[$i]);

      // Ei tsekata turhaan kun rivi dellataan luedatasta
      if ($chytunnus != '' and (!isset($trow["luedata_toiminto"]) or $trow["luedata_toiminto"] != "POISTA")) {
        $query = "SELECT ytunnus
                  FROM asiakas
                  WHERE yhtio  = '$kukarow[yhtio]'
                  and laji    != 'P'
                  and ytunnus  = '$t[$i]'";
        $sresult = pupe_query($query);

        if (mysql_num_rows($sresult) == 0) {
          $virhe[$i] = t("Ytunnuksella ei löydy asiakasta!");
        }
      }
    }

    if (mysql_field_name($result, $i) == "asiakas") {
      $chastunn = trim($t[$i]);

      // Ei tsekata turhaan kun rivi dellataan luedatasta
      if ($chastunn != '' and (!isset($trow["luedata_toiminto"]) or $trow["luedata_toiminto"] != "POISTA")) {

        unset($chasresult);

        if (is_numeric($chastunn) and $chastunn > 0) {
          $query  = "SELECT *
                     FROM asiakas
                     WHERE yhtio  = '$kukarow[yhtio]'
                     and laji    != 'P'
                     and tunnus   = '$chastunn'";
          $chasresult = pupe_query($query);
        }

        if (!isset($chasresult) or (isset($chasresult) and mysql_num_rows($chasresult) != 1)) {

          $ytunnus     = $chastunn;
          $kutsuja     = "yllapito.php";
          $ulos2       = "";

          if ($ytunnus != "" and $ytunnus != "0") {
            require "asiakashaku.inc";
          }

          if ($ulos2 != "" and $ytunnus == "") {
            $alasveto[$i] = "<select name='t[$i]'><option value=''>".t("Valitse asiakas")."</option>".$ulos2."</select>";
            $virhe[$i]    = t("Valitse asiakas!");
          }
          elseif ($ytunnus != "" and $asiakasid > 0) {
            $t[$i]  = $asiakasid;
            if (strpos($_SERVER['SCRIPT_NAME'], "lue_data.php") === FALSE) $virhe[$i] = t("Asiakas löytyi!");
          }
          else {
            $virhe[$i] = t("Asiakas puuttuu tai sitä ei löydy!");
          }
        }
      }
    }

    if (mysql_field_name($result, $i) == "asiakas_ryhma") {
      $chasiakas_ryhma = trim($t[$i]);

      // Ei tsekata turhaan kun rivi dellataan luedatasta
      if ($chasiakas_ryhma != '' and (!isset($trow["luedata_toiminto"]) or $trow["luedata_toiminto"] != "POISTA")) {
        $sresult = t_avainsana("ASIAKASRYHMA", "", "and avainsana.selite = '$chasiakas_ryhma'");

        if (mysql_num_rows($sresult) == 0) {
          $virhe[$i] = t("Asiakasryhmä puuttuu tai sitä ei löydy!");
        }
      }
    }

    if (mysql_field_name($result, $i) == "tuoteno") {
      $chtuoteno = $t[$i];
      $chtuoteno_ind = $i;

      // Ei tsekata turhaan kun rivi dellataan luedatasta
      if ($chtuoteno != '' and (!isset($trow["luedata_toiminto"]) or $trow["luedata_toiminto"] != "POISTA")) {
        $query = "SELECT tuoteno
                  FROM tuote
                  WHERE yhtio='$kukarow[yhtio]' and tuoteno = '$t[$i]'";
        $sresult = pupe_query($query);

        if (mysql_num_rows($sresult) != 1) {

          if (substr($t[$i], -1) != '*') $tuoteno = $t[$i]."*";
          else $tuoteno = $t[$i];

          $kutsuja     = "yllapito.php";
          $ulos      = "";

          require "inc/tuotehaku.inc";

          if ($ulos != "") {
            $alasveto[$i] = "<select name='t[$i]'>".$ulos."</select>";
          }

          $virhe[$i] = t("Tuotenumero puuttuu tai sitä ei löydy!");
        }
      }
    }

    if (mysql_field_name($result, $i) == "ryhma") {
      $chryhma = $t[$i];

      // Ei tsekata turhaan kun rivi dellataan luedatasta
      if ($chryhma != '' and (!isset($trow["luedata_toiminto"]) or $trow["luedata_toiminto"] != "POISTA") and $chryhma != '**') {
        $query = "SELECT tunnus
                  FROM perusalennus
                  WHERE yhtio='$kukarow[yhtio]' and ryhma = '$t[$i]'";
        $sresult = pupe_query($query);

        if (mysql_num_rows($sresult) != 1) {
          $virhe[$i] = t("Aleryhmä puuttuu tai sitä ei löydy!");
        }
      }
    }

    if (mysql_field_name($result, $i) == "asiakas_segmentti") {
      $chsegmentti = (int) $t[$i];

      // Ei tsekata turhaan kun rivi dellataan luedatasta
      if ($chsegmentti > 0 and (!isset($trow["luedata_toiminto"]) or $trow["luedata_toiminto"] != "POISTA")) {
        $preq = "SELECT * FROM dynaaminen_puu where yhtio='$kukarow[yhtio]' and laji='asiakas' and tunnus='$chsegmentti'";
        $preres = pupe_query($preq);

        if (mysql_num_rows($preres) != 1) {
          $virhe[$i] = t("Asiakassegmentti puuttuu tai sitä ei löydy!");
        }
      }
    }

    if (mysql_field_name($result, $i) == "piiri") {
      $chpiiri = $t[$i];

      // Ei tsekata turhaan kun rivi dellataan luedatasta
      if ($chpiiri != '' and (!isset($trow["luedata_toiminto"]) or $trow["luedata_toiminto"] != "POISTA")) {
        $preq = "SELECT avainsana.selite, avainsana.selitetark
                 FROM avainsana
                 WHERE avainsana.selite='{$chpiiri}' AND avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='piiri'";
        $preres = pupe_query($preq);

        if (mysql_num_rows($preres) != 1) {
          $virhe[$i] = t("Asiakaspiiri puuttuu tai sitä ei löydy! ");
        }
      }
    }

    if ((($chryhma == '' and $chtuoteno == '') or ($chryhma != '' and $chtuoteno != '')) and mysql_field_name($result, $i) == 'ryhma') {
      $virhe[$i] = t("Sinun on annettava joko tuote tai ryhmä!");
    }

    if (mysql_field_name($result, $i) == "minkpl") {
      $t[$i] = str_replace(",", ".", $t[$i]);
      $chminkpl = (int) $t[$i];
    }

    if (mysql_field_name($result, $i) == "monikerta") {
      $t[$i] = trim($t[$i]);
      $chmonikerta = $t[$i];
    }

    if (mysql_field_name($result, $i) == "alkupvm") {
      $chalkupvm = $t[$i];

      if ($chalkupvm == '') {
        $chalkupvm = '0000-00-00';
      }

      // Ei tsekata turhaan kun rivi dellataan luedatasta
      if ($chalkupvm != '0000-00-00' and (!isset($trow["luedata_toiminto"]) or $trow["luedata_toiminto"] != "POISTA")) {
        $pp = substr($chalkupvm, 8, 2);
        $kk = substr($chalkupvm, 5, 2);
        $vv = substr($chalkupvm, 0, 4);

        if (!checkdate($kk, $pp, $vv)) {
          $virhe[$i] = t("Alkupäivämäärä virheellinen!");
        }
      }
    }

    if (mysql_field_name($result, $i) == "loppupvm") {
      $chloppupvm = $t[$i];

      if ($chloppupvm == '') {
        $chloppupvm = '0000-00-00';
      }

      // Ei tsekata turhaan kun rivi dellataan luedatasta
      if ($chloppupvm != '0000-00-00' and (!isset($trow["luedata_toiminto"]) or $trow["luedata_toiminto"] != "POISTA")) {
        $pp = substr($chloppupvm, 8, 2);
        $kk = substr($chloppupvm, 5, 2);
        $vv = substr($chloppupvm, 0, 4);

        if (!checkdate($kk, $pp, $vv)) {
          $virhe[$i] = t("Loppupäivämäärä virheellinen!");
        }
      }
    }

    if (mysql_field_name($result, $i) == "alennuslaji") {
      $chalennuslaji = (int) $t[$i];

      // Ei tsekata turhaan kun rivi dellataan luedatasta
      if (!isset($trow["luedata_toiminto"]) or $trow["luedata_toiminto"] != "POISTA") {
        if ($chalennuslaji < 1 or $chalennuslaji > $yhtiorow['myynnin_alekentat']) {
          $virhe[$i] = t("Virheellinen alennuslaji!");
        }
      }
    }

    // Ei tsekata turhaan kun rivi dellataan luedatasta
    if (($chasiakas_ryhma != '' or $chytunnus != '' or $chpiiri != '' or $chsegmentti != '' or $chastunn != '') and ($chryhma != '' or $chtuoteno != '') and mysql_field_name($result, $i) == 'tunnus' and (!isset($trow["luedata_toiminto"]) or $trow["luedata_toiminto"] != "POISTA")) {

      $rajlask = 0;

      if ($chasiakas_ryhma != '') $rajlask++;
      if ($chytunnus != '') $rajlask++;
      if ($chpiiri != '') $rajlask++;
      if ($chsegmentti > 0) $rajlask++;
      if ($chastunn > 0) $rajlask++;

      if ($rajlask > 1 or $rajlask == 0) {
        $virhe[$i] = t("Valitse vain asiakas, ytunnus, asiakasryhmä, asiakassegmentti tai piiri!");
      }

      $and = '';

      if ($chasiakas_ryhma != '') $and .= " and asiakas_ryhma = '$chasiakas_ryhma'";
      if ($chytunnus != '')       $and .= " and ytunnus = '$chytunnus'";
      if ($chpiiri != '')         $and .= " and piiri = '$chpiiri'";
      if ($chsegmentti > 0)       $and .= " and asiakas_segmentti = '$chsegmentti'";
      if ($chastunn > 0)          $and .= " and asiakas = '$chastunn'";

      if ($chryhma != '')         $and .= " and ryhma = '$chryhma'";
      if ($chtuoteno != '')       $and .= " and tuoteno = '$chtuoteno'";
      if ($chalennuslaji == "" or $chalennuslaji == 0) $chalennuslaji = 1;

      $chminkpl = (int) $chminkpl;

      $aquery = "SELECT ytunnus
                 FROM asiakasalennus
                 WHERE yhtio      = '$kukarow[yhtio]'
                 $and
                 and alkupvm      = '$chalkupvm'
                 and loppupvm     = '$chloppupvm'
                 and minkpl       = $chminkpl
                 and monikerta    = '$chmonikerta'
                 and alennuslaji  = $chalennuslaji
                 and tunnus      != '$trow[$i]'";
      $dsresult = pupe_query($aquery);

      if (mysql_num_rows($dsresult) > 0) {
        // Pitää aina setata myös "tämän" kentän virhe, muuten luedata ei toimi
        $virhe[$chtuoteno_ind] = $virhe[$i] = t("VIRHE: Näillä tiedoilla on jo asiakasalennus järjestelmässä!");
      }
    }
  }
}
