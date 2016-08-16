<?php

if (!function_exists("varastopaikattarkista")) {
  function varastopaikattarkista(&$t, $i, $result, $tunnus, &$virhe, $trow) {
    global $kukarow, $yhtiorow, $alias_set;

    static $alkuhyllyalue, $alkuhyllynro, $loppuhyllyalue, $loppuhyllynro;

    if (mysql_field_name($result, $i) == "alkuhyllyalue") {
      $t[$i] = trim(strtoupper($t[$i]));
      $alkuhyllyalue = $t[$i];

      if (trim($t[$i]) == '') {
        $virhe[$i] .= t("Alkuhyllyalue ei saa olla tyhjiä")."!";
      }
    }

    if (mysql_field_name($result, $i) == "alkuhyllynro") {
      $t[$i] = trim(strtoupper($t[$i]));
      $alkuhyllynro = $t[$i];

      if (trim($t[$i]) == '') {
        $virhe[$i] .= t("Alkuhyllynro ei saa olla tyhjiä")."!";
      }
    }

    if (mysql_field_name($result, $i) == "loppuhyllyalue") {
      $t[$i] = trim(strtoupper($t[$i]));
      $loppuhyllyalue = $t[$i];

      if (trim($t[$i]) == '') {
        $virhe[$i] .= t("Loppuhyllyalue ei saa olla tyhjiä")."!";
      }
    }

    if (mysql_field_name($result, $i) == "loppuhyllynro") {
      $t[$i] = trim(strtoupper($t[$i]));
      $loppuhyllynro = $t[$i];

      if (trim($t[$i]) == '') {
        $virhe[$i] .= t("Loppuhyllynro ei saa olla tyhjiä")."!";
      }

      //Katotaan osuuko alkuhyllyalue johonkin varastoon
      $query = "SELECT tunnus
                FROM varastopaikat
                WHERE
                tunnus    != '$tunnus'
                and concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper('$alkuhyllyalue') ,5,'0'),lpad(upper('$alkuhyllynro') ,5,'0'))
                and concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper('$alkuhyllyalue') ,5,'0'),lpad(upper('$alkuhyllynro') ,5,'0'))
                and yhtio  = '$kukarow[yhtio]'";
      $vares = pupe_query($query);

      if (mysql_num_rows($vares) == 0) {
        //Katotaan osuuko loppuhyllyalue johonkin varastoon
        $query = "SELECT tunnus
                  FROM varastopaikat
                  WHERE
                  tunnus    != '$tunnus'
                  and concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper('$loppuhyllyalue') ,5,'0'),lpad(upper('$loppuhyllynro') ,5,'0'))
                  and concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper('$loppuhyllyalue') ,5,'0'),lpad(upper('$loppuhyllynro') ,5,'0'))
                  and yhtio  = '$kukarow[yhtio]'";
        $vares = pupe_query($query);

        if (mysql_num_rows($vares) == 0) {
          //Katotaan onko jo joku varasto syötetyn alueen sisällä
          $query = "SELECT tunnus
                    FROM varastopaikat
                    WHERE
                    tunnus    != '$tunnus'
                    and concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper('$loppuhyllyalue') ,5,'0'),lpad(upper('$loppuhyllynro') ,5,'0'))
                    and concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper('$alkuhyllyalue') ,5,'0'),lpad(upper('$alkuhyllynro') ,5,'0'))
                    and yhtio  = '$kukarow[yhtio]'";
          $vares = pupe_query($query);
        }
      }

      if (mysql_num_rows($vares) != 0) {
        $virhe[$i] .= t("Päällekkäisiä varastoalueita")."!";
      }
    }

    if (mysql_field_name($result, $i) == "printteri2") {
      if (trim($t[$i]) == '') {
        $virhe[$i] .= t("Rahtikirjatulostimet ei saa olla tyhjiä")."!";
      }
    }
    if (mysql_field_name($result, $i) == "printteri4") {
      if (trim($t[$i]) == '') {
        $virhe[$i] .= t("Rahtikirjatulostimet ei saa olla tyhjiä")."!";
      }
    }
    if (mysql_field_name($result, $i) == "printteri5") {
      if (trim($t[$i]) == '') {
        $virhe[$i] .= t("Rahtikirjatulostimet ei saa olla tyhjiä")."!";
      }
    }

    if (mysql_field_name($result, $i) == "ulkoinen_jarjestelma") {
      if (in_array($t[$i], array('L','P')) and !LOGMASTER_RAJAPINTA) {
        $virhe[$i] .= t("Valittuun ulkoiseen järjestelmään vaaditut FTP-tiedot puuttuvat")."!";
      }
    }
  }
}
