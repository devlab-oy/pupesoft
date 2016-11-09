<?php

if (!function_exists("tuotteen_toimittajattarkista")) {
  function tuotteen_toimittajattarkista(&$t, $i, $result, $tunnus, &$virhe, $trow) {
    global $kukarow, $yhtiorow, $alias_set, $alasveto, $liitostunnus_static, $chtuoteno;
    global $lue_datasta, $toimi_liitostunnus, $maa_static, $valuutta_static;

    if (mysql_field_name($result, $i) == "tuoteno") {
      $chtuoteno = $t[$i];

      if ($chtuoteno != '') {
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

          $virhe[$i] = t("Tuotenumeroa ei löydy!");
        }
      }
      else {
        $virhe[$i] = t("Tuotenumero puuttuu!");
      }
    }

    if (mysql_field_name($result, $i) == "liitostunnus") {

      $liitostunnus_static = 0;

      if (isset($toimi_liitostunnus) and $toimi_liitostunnus > 0) {
        $t[$i] = $toimi_liitostunnus;
      }

      if ($t[$i] != '') {

        $t[$i] = trim($t[$i]);

        if (is_numeric($t[$i]) and $t[$i] > 0) {
          $query = "SELECT *
                    FROM toimi
                    WHERE yhtio  = '$kukarow[yhtio]'
                    AND tyyppi  != 'P'
                    AND tunnus   = {$t[$i]}
                    ORDER by nimi";
          $sresult = pupe_query($query);

          if (mysql_num_rows($sresult) == 1) {
            $srow = mysql_fetch_assoc($sresult);
            $liitostunnus_static = $srow["tunnus"];
            $maa_static = $srow['maa'];
            $valuutta_static = $srow['oletus_valkoodi'];
          }
        }

        if ($liitostunnus_static == 0) {

          $toimittaja_haku = pupesoft_cleanstring($t[$i]);

          // Kokeillaan ovttunnuksella
          $query = "SELECT toimi.*, IF(maa_lahetys != '', maa_lahetys, maa) AS maa
                    FROM toimi
                    WHERE yhtio    = '$kukarow[yhtio]'
                    AND tyyppi    != 'P'
                    AND ovttunnus  = '$toimittaja_haku'
                    AND ovttunnus != ''";
          $sresult = pupe_query($query);

          // Kokeillaan ytunnuksella
          if (mysql_num_rows($sresult) != 1) {
            $query = "SELECT toimi.*, IF(maa_lahetys != '', maa_lahetys, maa) AS maa
                      FROM toimi
                      WHERE yhtio  = '$kukarow[yhtio]'
                      AND tyyppi  != 'P'
                      AND ytunnus  = '$toimittaja_haku'
                      AND ytunnus != ''";
            $sresult = pupe_query($query);
          }

          // Kokeillaan toimittajanumerolla
          if (mysql_num_rows($sresult) != 1) {
            $query = "SELECT toimi.*, IF(maa_lahetys != '', maa_lahetys, maa) AS maa
                      FROM toimi
                      WHERE yhtio        = '$kukarow[yhtio]'
                      AND tyyppi        != 'P'
                      AND toimittajanro  = '$toimittaja_haku'
                      AND toimittajanro != ''";
            $sresult = pupe_query($query);
          }

          // Kokeillaan vanhalla mallilla
          if (mysql_num_rows($sresult) != 1) {
            $query = "SELECT toimi.*, IF(maa_lahetys != '', maa_lahetys, maa) AS maa
                      from toimi
                      where yhtio  = '$kukarow[yhtio]'
                      and tyyppi  != 'P'
                      and (ytunnus like '%$toimittaja_haku%' or nimi like '%$toimittaja_haku%' or toimittajanro like '%$toimittaja_haku%')
                      order by nimi";
            $sresult = pupe_query($query);
          }

          if (mysql_num_rows($sresult) == 1) {
            $srow = mysql_fetch_assoc($sresult);
            $liitostunnus_static = $srow["tunnus"];
            $t[$i] = $liitostunnus_static;
            $maa_static = $srow['maa'];
            $valuutta_static = $srow['oletus_valkoodi'];
          }
          elseif (mysql_num_rows($sresult) > 1) {

            $alasveto[$i] = "<input type='hidden' name='t[$i]' value='XXX'>
                    <select name='toimi_liitostunnus'>";

            while ($srow = mysql_fetch_assoc($sresult)) {
              if ($srow['toimittajanro'] != '') $naytetaantoimittajanro = "(".$srow['toimittajanro'].")";
              else $naytetaantoimittajanro = "";
              $alasveto[$i] .= "<option value='$srow[tunnus]'>$srow[ytunnus] $srow[nimi] $srow[nimitark] $naytetaantoimittajanro </option>";
            }

            $alasveto[$i] .= "</select>";

            $virhe[$i] = t("Valitse toimittaja")."!";
          }
        }
      }

      if ($liitostunnus_static > 0) {
        $query = "SELECT *
                  FROM tuotteen_toimittajat
                  WHERE yhtio       = '$kukarow[yhtio]'
                  AND liitostunnus  = '$liitostunnus_static'
                  AND tunnus       != '$tunnus'
                  AND tuoteno       = '$chtuoteno'";
        $sresult = pupe_query($query);

        if (mysql_num_rows($sresult) > 0) {
          $virhe[$i] = t("Tämä toimittaja on jo tällä tuotteella")."!";
        }
      }

      if ($liitostunnus_static == 0) {
        $virhe[$i] = t("Toimittaja puuttuu!");
      }
    }

    if (mysql_field_name($result, $i) == "alkuperamaa" and $t[$i] == "" and !empty($liitostunnus_static)) {
      if ($lue_datasta) {
        $virhe[$i] =  t("Tieto puuttuu")."!";
      }
      else {
        if (!empty($maa_static)) {
          $t[$i] = $maa_static;
        }
        else {
          $virhe[$i] =  t("Valitse maa")."!";
        }
      }
    }

    if (mysql_field_name($result, $i) == "valuutta" and $t[$i] == "" and !empty($liitostunnus_static)) {
      if ($lue_datasta) {
        $virhe[$i] =  t("Tieto puuttuu")."!";
      }
      else {
        if (!empty($valuutta_static)) {
          $t[$i] = $valuutta_static;
        }
        else {
          $virhe[$i] =  t("Valitse valuutta")."!";
        }
      }
    }

    if (mysql_field_name($result, $i) == "toim_tuoteno") {

      $query = "SELECT tyyppi from toimi where yhtio='$kukarow[yhtio]' and tunnus='$liitostunnus_static'";
      $sresult = pupe_query($query);
      $tuotoimirow = mysql_fetch_assoc($sresult);

      if ($t[$i] == "" and $tuotoimirow["tyyppi"] != "") {
        $virhe[$i] = t("Online/sisäinen toimittaja vaatii aina toimittajan tuotenumeron")."!";
      }
    }
  }
}
