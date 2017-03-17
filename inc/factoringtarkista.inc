<?php

if (!function_exists("factoringtarkista")) {
  function factoringtarkista(&$t, $i, $result, $tunnus, &$virhe, $trow) {
    global $kukarow, $yhtiorow, $alias_set;

    static $directdebit;

    if (mysql_field_name($result, $i) == "factoringyhtio") {
      if (substr($t[$i], -3) == "_DD") {
        $directdebit = TRUE;
      }
    }

    if (mysql_field_name($result, $i) == "direct_debit") {
      if (!empty($directdebit)) {
        $t[$i] = "D";
      }
      else {
        $t[$i] = "";
      }
    }
  }
}
