<?php

if (!function_exists("yhteensopivuus_tuote_lisatiedot")) {
  function yhteensopivuus_tuote_lisatiedot(&$t, $i, $result, $tunnus, &$virhe, $trow) {
    global $kukarow, $yhtiorow, $poistolukko, $alias_set;

    if (mysql_field_name($result, $i) == "otsikko") {
      if (trim($t[$i]) == '') {
        $virhe[$i] = t("Otsikko tieto puuttuu");
      }
      else {
        $chotsikko = $t[$i];
      }
    }
    if (mysql_field_name($result, $i) == "arvo") {
      if (trim($t[$i]) == '') {
        $virhe[$i] = t("Arvo tieto puuttuu");
      }
      else {
        $charvo = $t[$i];
      }

    }
  }


}
