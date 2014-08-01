<?php

if (!function_exists("perusalennustarkista")) {
  function perusalennustarkista(&$t, $i, $result, $tunnus, &$virhe, $trow) {
    global $kukarow, $yhtiorow, $alias_set;

  }

  if (mysql_field_name($result, $i) == "ryhma") {
    if (trim($t[$i]) == '') {
      $virhe[$i] = t("Tieto puuttuu");
    }
    else {
      if ($tunnus == "") {
        $query  = "SELECT yhtio
                   FROM perusalennus
                   WHERE yhtio = '$kukarow[yhtio]'
                   and ryhma   = '".trim($t[$i])."'";
        $chkressiresult = mysql_query($query) or pupe_error($query);

        if (mysql_num_rows($chkressiresult) > 0) {
          $virhe[$i] = t("Alennusryhmä on jo perustettu järjestelmään");
        }
      }
    }
  }
}
