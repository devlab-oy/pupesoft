<?php

if (!function_exists("toimitustavan_avainsanattarkista")) {
  function toimitustavan_avainsanattarkista(&$t, $i, $result, $tunnus, &$virhe, $trow) {
    global $kukarow, $yhtiorow, $alias_set, $alasveto;

    static $tem_liitostunnus, $tem_liitostunnus_i, $tem_kieli, $tem_laji, $tem_selite, $tem_selitetark;

    if (mysql_field_name($result, $i) == "liitostunnus") {
      $tem_liitostunnus = trim($t[$i]);
      $tem_liitostunnus_i = $i;

      if ($tem_liitostunnus != '') {
        $query = "SELECT tunnus, selite
                  FROM toimitustapa
                  WHERE yhtio = '$kukarow[yhtio]'
                  AND (tunnus = '$t[$i]' or selite = '$t[$i]')";
        $sresult = pupe_query($query);

        if (mysql_num_rows($sresult) != 1 and $trow["luedata_toiminto"] != "POISTA") {

          $query = "SELECT tunnus, selite
                    FROM toimitustapa
                    WHERE yhtio = '$kukarow[yhtio]'";
          $sresult = pupe_query($query);

          if (mysql_num_rows($sresult) > 0) {

            $alasveto[$i] = "<select name='t[$i]'>";
            $alasveto[$i] .= "<option value='0'>".t("Valitse toimitustapa")."</option>";
            while ($toimtaparow = mysql_fetch_assoc($sresult)) {
              $alasveto[$i] .= "<option value='{$toimtaparow['tunnus']}'>".$toimtaparow['selite']."</option>";
            }
            $alasveto[$i] .= "</select>";
          }

          $virhe[$i] = t("Toimitustapaa ei löydy!");
        }
        elseif (mysql_num_rows($sresult) == 1) {

          $_toimitustavan_tunnus = mysql_fetch_assoc($sresult);
          $tem_liitostunnus = $_toimitustavan_tunnus['tunnus'];

          $alasveto[$i] = "<select name='t[$i]'>";
          $alasveto[$i] .= "<option value='{$tem_liitostunnus}'>".$_toimitustavan_tunnus['selite']."</option>";
          $alasveto[$i] .= "</select>";
        }
      }
      else {
        $virhe[$i] = t("Toimitustapa puuttuu!");
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
    }

    if (mysql_field_name($result, $i) == "selitetark") {

      $tem_selitetark = trim($t[$i]);

      if ($t[$i] == '') {
        $virhe[$i] = t("Tieto puuttuu")."!";
      }
    }

    if (mysql_field_name($result, $i) == 'tunnus') {

      $query = "SELECT tunnus
                FROM toimitustavan_avainsanat
                WHERE yhtio     = '$kukarow[yhtio]'
                AND liitostunnus = '$tem_liitostunnus'
                AND laji        = '$tem_laji'
                AND selite      = '$tem_selite'
                AND tunnus     != '$tunnus'";
      $results = pupe_query($query);

      if (mysql_num_rows($results) > 0) {
        $virhe[$tem_liitostunnus_i] = $virhe[$i] = t("Toimittajan avainsana annetuilla tiedoilla löytyy kannasta. Duplikaatit ovat kiellettyjä!");
      }
    }
  }
}
