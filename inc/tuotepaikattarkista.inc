<?php

if (!function_exists("tuotepaikattarkista")) {
  function tuotepaikattarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {
    global $kukarow, $yhtiorow, $alias_set;
    static $tmp_hyllyalue, $tmp_hyllynro, $tmp_tuotenoind;

    if (mysql_field_name($result, $i) == "tuoteno") {
      $query = "  SELECT *
            FROM tuote
            WHERE yhtio = '$kukarow[yhtio]'
            and tuoteno = '$t[$i]'";
      $chktuote = mysql_query($query) or pupe_error($query);
      
      $tmp_tuotenoind = $i;
            
      if (mysql_num_rows($chktuote) != 1) {
        $virhe[$i] = t("Tuotenumero ei löydy!");
      }
      else {
        $chktuoterow = mysql_fetch_assoc($chktuote);

        if ($chktuoterow['ei_saldoa'] != "") {
          $virhe[$i] = t("Tuote on saldoton! Tuotepaikkaa ei voida lisätä.");
        }
      }
    }

    if (mysql_field_name($result, $i) == "hyllyalue") {
      $tmp_hyllyalue = $t[$i];
    }

    if (mysql_field_name($result, $i) == "hyllynro") {
      $tmp_hyllynro = $t[$i];
    }

    if (mysql_field_name($result, $i) == "tunnus") {
      if (kuuluukovarastoon($tmp_hyllyalue, $tmp_hyllynro) == 0) {
        $virhe[$tmp_tuotenoind] = $virhe[$i] = t("VIRHE: Syötetty varastopaikka ei kuulu mihinkään varastoon");
      }
    }
  }
}
