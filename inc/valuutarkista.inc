<?php

if (!function_exists("valuutarkista")) {
  function valuutarkista(&$t, $i, $result, $tunnus, &$virhe, $trow) {
    global $kukarow, $yhtiorow, $alias_set;
    if (mysql_field_name($result, $i) == "nimi") {
      $t[$i] = strtoupper($t[$i]);
      if (trim($t[$i]) == '') {
        $virhe[$i] = t("Tieto puuttuu");
      }
    }
  }
}
