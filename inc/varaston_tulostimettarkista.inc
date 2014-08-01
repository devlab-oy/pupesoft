<?php

if (!function_exists("varaston_tulostimettarkista")) {
  function varaston_tulostimettarkista(&$t, $i, $result, $tunnus, &$virhe, $trow) {
    global $kukarow, $yhtiorow, $alias_set;

    static $varasto, $alkuhyllyalue, $alkuhyllynro, $loppuhyllyalue, $loppuhyllynro;

    if (mysql_field_name($result, $i) == "varasto") {
      $varasto = $t[$i];
    }

    if (mysql_field_name($result, $i) == "alkuhyllyalue") {
      $t[$i] = trim(strtoupper($t[$i]));
      $alkuhyllyalue = $t[$i];

      if (trim($t[$i]) == '') {
        $virhe[$i] .= t("Alkuhyllyalue ei saa olla tyhji‰")."!";
      }
    }

    if (mysql_field_name($result, $i) == "alkuhyllynro") {
      $t[$i] = trim(strtoupper($t[$i]));
      $alkuhyllynro = $t[$i];

      if (trim($t[$i]) == '') {
        $virhe[$i] .= t("Alkuhyllynro ei saa olla tyhji‰")."!";
      }
    }

    if (mysql_field_name($result, $i) == "loppuhyllyalue") {
      $t[$i] = trim(strtoupper($t[$i]));
      $loppuhyllyalue = $t[$i];

      if (trim($t[$i]) == '') {
        $virhe[$i] .= t("Loppuhyllyalue ei saa olla tyhji‰")."!";
      }
    }

    if (mysql_field_name($result, $i) == "loppuhyllynro") {
      $t[$i] = trim(strtoupper($t[$i]));
      $loppuhyllynro = $t[$i];

      if (trim($t[$i]) == '') {
        $virhe[$i] .= t("Loppuhyllynro ei saa olla tyhji‰")."!";
      }

      //Katotaan osuuko alkuhyllyalue johonkin tulostusalueeseen
      $query = "SELECT tunnus
                FROM varaston_tulostimet
                WHERE
                tunnus    != '$tunnus'
                and concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper('$alkuhyllyalue') ,5,'0'),lpad(upper('$alkuhyllynro') ,5,'0'))
                and concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper('$alkuhyllyalue') ,5,'0'),lpad(upper('$alkuhyllynro') ,5,'0'))
                and yhtio  = '$kukarow[yhtio]'";
      $vares = mysql_query($query) or pupe_error($query);

      if (mysql_num_rows($vares) == 0) {
        //Katotaan osuuko loppuhyllyalue johonkin tulostusalueeseen
        $query = "SELECT tunnus
                  FROM varaston_tulostimet
                  WHERE
                  tunnus    != '$tunnus'
                  and concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper('$loppuhyllyalue') ,5,'0'),lpad(upper('$loppuhyllynro') ,5,'0'))
                  and concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper('$loppuhyllyalue') ,5,'0'),lpad(upper('$loppuhyllynro') ,5,'0'))
                  and yhtio  = '$kukarow[yhtio]'";
        $vares = mysql_query($query) or pupe_error($query);

        if (mysql_num_rows($vares) == 0) {
          //Katotaan onko jo jonkun tulostusalueen sis‰ll‰
          $query = "SELECT tunnus
                    FROM varaston_tulostimet
                    WHERE
                    tunnus    != '$tunnus'
                    and concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper('$loppuhyllyalue') ,5,'0'),lpad(upper('$loppuhyllynro') ,5,'0'))
                    and concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper('$alkuhyllyalue') ,5,'0'),lpad(upper('$alkuhyllynro') ,5,'0'))
                    and yhtio  = '$kukarow[yhtio]'";
          $vares = mysql_query($query) or pupe_error($query);
        }
      }

      if (mysql_num_rows($vares) != 0) {
        $virhe[$i] .= t("P‰‰llekk‰isi‰ varastoalueita")."!";
      }

      $alku = kuuluukovarastoon($alkuhyllyalue, $alkuhyllynro, $varasto);
      $loppu = kuuluukovarastoon($loppuhyllyalue, $loppuhyllynro, $varasto);

      if ($alku == 0 or $loppu == 0) {
        $virhe[$i] .= t("Tulostusalue ei ole varastonalueen sis‰ll‰")."!";
      }
    }


    if (mysql_field_name($result, $i) == "nimi") {
      if (trim($t[$i]) == '') {
        $virhe[$i] .= t("Nimi puuttuu")."!";
      }
    }

    if (substr(mysql_field_name($result, $i), 0, 9) == 'printteri') {
      if (trim($t[$i]) == '') {
        $virhe[$i] .= t("Tulostin puuttuu")."!";
      }
    }

  }
}
