<?php

if (!function_exists("kirjoittimettarkista")) {
  function kirjoittimettarkista(&$t, $i, $result, $tunnus, &$virhe, $trow) {
    global $kukarow, $yhtiorow, $alias_set;

    if (mysql_field_name($result, $i) == "komento") {

      $komento = pupesoft_cleanstring($t[$i]);

      if ($komento == '') {
        $virhe[$i] = t("Tieto puuttuu");
      }
      else {
        $query  = "SELECT tunnus
                   FROM kirjoittimet
                   WHERE yhtio  = '{$kukarow["yhtio"]}'
                   AND komento  = '{$komento}'
                   AND tunnus  != '{$tunnus}'";
        $chkressiresult = pupe_query($query);

        if (mysql_num_rows($chkressiresult) > 0) {
          $virhe[$i] = t("Kirjoitin on jo perustettu j‰rjestelm‰‰n");
        }
      }
    }

    if (mysql_field_name($result, $i) == "kirjoitin") {
      if (trim($t[$i]) == '') {
        $virhe[$i] = t("Tieto puuttuu");
      }
    }

  }
}
