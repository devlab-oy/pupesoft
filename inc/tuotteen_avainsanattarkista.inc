<?php

if (!function_exists("tuotteen_avainsanattarkista")) {
  function tuotteen_avainsanattarkista(&$t, $i, $result, $tunnus, &$virhe, $trow) {
    global $kukarow, $yhtiorow, $alias_set, $alasveto;

    static $tem_tuoteno, $tem_tuoteno_i, $tem_kieli, $tem_laji, $tem_selite, $tem_selitetark, $tem_status, $tem_nakyvyys;

    if (mysql_field_name($result, $i) == "tuoteno") {
      $tem_tuoteno = trim($t[$i]);
      $tem_tuoteno_i = $i;

      if ($tem_tuoteno != '') {
        $query = "SELECT tuoteno
                  FROM tuote
                  WHERE yhtio = '$kukarow[yhtio]'
                  AND tuoteno = '$t[$i]'";
        $sresult = pupe_query($query);

        if (mysql_num_rows($sresult) != 1 and $trow["luedata_toiminto"] != "POISTA") {

          if (substr($t[$i], -1) != '*') $tuoteno = $t[$i]."*";
          else $tuoteno = $t[$i];

          $kutsuja     = "yllapito.php";
          $ulos      = "";

          require "inc/tuotehaku.inc";

          if ($ulos != "") {
            $alasveto[$i] = "<select name='t[$i]'>".$ulos."</select>";
          }

          $virhe[$i] = t("Tuotenumeroa ei löydy!");
        }
      }
      else {
        $virhe[$i] = t("Tuotenumero puuttuu!");
      }
    }

    if (mysql_field_name($result, $i) == "kieli") {

      $tem_kieli = trim($t[$i]);

      if ($tem_kieli == '') {
        $virhe[$i] = t("Kieltä ei voi jättää tyhjäksi")."!";
      }
      else {
        $loytyyko = FALSE;

        foreach ($GLOBALS["sanakirja_kielet"] as $sanakirja_kieli => $sanakirja_kieli_nimi) {
          if ($sanakirja_kieli == $t[$i]) {
            $loytyyko = TRUE;
            break;
          }
        }

        if (!$loytyyko) {
          $virhe[$i] = t("Virheellinen kieli")."!";
        }
      }
    }

    if (mysql_field_name($result, $i) == "laji") {

      $tem_laji = trim($t[$i]);

      if ($t[$i] == '') {
        $virhe[$i] = t("Tieto puuttuu")."!";
      }
    }

    if (mysql_field_name($result, $i) == "selite") {

      $tem_selite = trim($t[$i]);

      if ($t[$i] == '') {
        $virhe[$i] = t("Tieto puuttuu")."!";
      }

      if ($tem_laji == "SISTOIMITTAJA") {
        // salitaan vaan konserniyhtiö joka ei ole me itse
        $query = "SELECT *
                  FROM yhtio
                  WHERE yhtio   = '$t[$i]'
                  and konserni  = '$yhtiorow[konserni]'
                  and yhtio    != '$kukarow[yhtio]'";
        $sresult = pupe_query($query);

        if (mysql_num_rows($sresult) == 0) {
          $virhe[$i] = t("VIRHE: Yhtiötä ei löydy")."!";
        }
      }
      elseif (strtoupper($tem_laji) == "OLETUSVALINTA") {
        if (!in_array(substr($t[$i], 0, 3), array("", "!!!", "¡¡¡", "¡¡V"))) {
          $virhe[$i] = t("Tuotteen %s oletusvalinta %s ei kelpaa!", "", $tem_tuoteno, $t[$i]);
        }
      }

      // jos kyseessä on tuotteen parametri
      if (substr(strtoupper($tem_laji), 0, 10) == "PARAMETRI_") {
        $query = "SELECT tunnus
                  from tuotteen_avainsanat
                  where yhtio  = '$kukarow[yhtio]'
                  and laji     = '$tem_laji'
                  and kieli    = '$tem_kieli'
                  and tuoteno  = '$tem_tuoteno'
                  and tunnus  != '$tunnus'";
        $sresult = pupe_query($query);

        if (mysql_num_rows($sresult) > 0) {
          $virhe[$i] = t("Tuotteella on jo parametri"). " ". substr($tem_laji, 10);
        }
      }

      if (strtoupper($tem_laji) == "PARAMETRI_VARIAATIO_JAKO") {
        // Onko meillä moniulotteinen variaatio esim. väri ja koko...
        $variaatiot = explode(",", $t[$i]);

        foreach ($variaatiot as $variaatio) {
          $query = "SELECT tunnus
                    from tuotteen_avainsanat
                    where yhtio = '$kukarow[yhtio]'
                    and kieli   = '$yhtiorow[kieli]'
                    and laji    = 'parametri_$variaatio'
                    and tuoteno = '$tem_tuoteno'";
          $sresult = pupe_query($query);

          if (mysql_num_rows($sresult) != 1) {
            $virhe[$i] .= t("Tuotteella ei ole parametriä"). " '". $variaatio."'";
          }
          if ($variaatio == "variaatio" or $variaatio == "variaatio_jako") {
            $virhe[$i] .= t("Variaatio jako ei voi olla variaatio eikä variaatio_jako"). "!";
          }
        }
      }
    }

    if (mysql_field_name($result, $i) == "selitetark") {

      $tem_selitetark = trim($t[$i]);
    }


    if (mysql_field_name($result, $i) == 'tunnus') {

      $query = "SELECT tunnus
                FROM tuotteen_avainsanat
                WHERE yhtio     = '$kukarow[yhtio]'
                AND tuoteno     = '$tem_tuoteno'
                AND kieli       = '$tem_kieli'
                AND laji        = '$tem_laji'
                AND selite      = '$tem_selite'
                AND selitetark  = '$tem_selitetark'
                AND status      = '$tem_status'
                AND nakyvyys    = '$tem_nakyvyys'
                and tunnus     != '$tunnus'";
      $results = pupe_query($query);

      if (mysql_num_rows($results) > 0) {
        $virhe[$tem_tuoteno_i] = $virhe[$i] = t("Avainsana annetuilla tiedoilla löytyy kannasta. Duplikaatit ovat kiellettyjä!");
      }
      else {

        $query = "SELECT tunnus
                  FROM tuotteen_avainsanat
                  WHERE yhtio     = '$kukarow[yhtio]'
                  AND tuoteno     = '$tem_tuoteno'
                  AND kieli       = '$tem_kieli'
                  AND laji        = '$tem_laji'
                  AND selite      = '$tem_selite'
                  AND selitetark  = '$tem_selitetark'";
        $results = pupe_query($query);

        if (mysql_num_rows($results) > 0) {
          $virhe[$tem_tuoteno_i] = $virhe[$i] = t("Avainsana annetuilla tiedoilla löytyy kannasta. Duplikaatit ovat kiellettyjä!");
        }
      }
    }
  }
}
