<?php

if(!function_exists("extranet_kayttajan_lisatiedottarkista")) {
  function extranet_kayttajan_lisatiedottarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {
    global $kukarow, $yhtiorow, $alias_set, $laji, $poistolukko;

    if (mysql_field_name($result, $i) == "liitostunnus") {

      $t[$i] = (int) trim($t[$i]);

      if ($t[$i] == 0) {
        $virhe[$i] = t("Tieto puuttuu");
      }
      else {
        $query = "SELECT tunnus
                  FROM kuka
                  WHERE yhtio   = '$kukarow[yhtio]'
                  and extranet != ''
                  and tunnus    = $t[$i]";
        $sresult = pupe_query($query);

        if (mysql_num_rows($sresult) == 0) {
          $virhe[$i] = t("Extranet-käyttäjää ei löydy");
        }
      }
    }
  }
}
