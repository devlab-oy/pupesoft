<?php

if(!function_exists("dynaaminen_puu_avainsanattarkista")) {
  function dynaaminen_puu_avainsanattarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {
    global $kukarow, $yhtiorow;
    static $tem_liitostunnus, $tem_kieli, $tem_laji, $tem_avainsana, $tem_avainsana_i, $tem_tarkenne;

    if (mysql_field_name($result, $i) == "liitostunnus") {
      $tem_liitostunnus = $t[$i];
    }

    if (mysql_field_name($result, $i) == "kieli") {
      $tem_kieli = $t[$i];
    }

    if (mysql_field_name($result, $i) == "laji") {
      $tem_laji = $t[$i];
    }

    if (mysql_field_name($result, $i) == "avainsana") {
      if (trim($t[$i]) == '') {
        $virhe[$i] = t("Selite ei voi olla tyhjä")."!";
      }
      $tem_avainsana = $t[$i];
      $tem_avainsana_i = $i;
    }

    if (mysql_field_name($result, $i) == "tarkenne") {
      $tem_tarkenne = $t[$i];
    }

    if (mysql_field_name($result, $i) == 'tunnus') {

      $query = "  SELECT tunnus
            FROM dynaaminen_puu_avainsanat
            WHERE yhtio     = '{$kukarow['yhtio']}'
            AND liitostunnus     = '{$tem_liitostunnus}'
            AND kieli       = '{$tem_kieli}'
            AND laji        = '{$tem_laji}'
            AND avainsana      = '{$tem_avainsana}'
            AND tarkenne      = '{$tem_tarkenne}'
            and tunnus        != '{$tunnus}'";
      $results = pupe_query($query);

      if (mysql_num_rows($results) > 0) {
        $virhe[$tem_avainsana_i] = $virhe[$i] = t("Avainsana annetuilla tiedoilla löytyy kannasta. Duplikaatit ovat kiellettyjä!");
      }
    }
  }
}
