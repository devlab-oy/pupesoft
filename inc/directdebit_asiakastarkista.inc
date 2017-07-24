<?php

if (!function_exists("directdebit_asiakastarkista")) {
  function directdebit_asiakastarkista(&$t, $i, $result, $tunnus, &$virhe, $trow) {
    global $kukarow, $yhtiorow, $alias_set;
    static $tem_liitostunnus, $tem_directdebit_id, $tem_directdebit_id_i;

    if (mysql_field_name($result, $i) == 'liitostunnus') {
      $tem_liitostunnus = $t[$i];
    }

    if (mysql_field_name($result, $i) == 'directdebit_id') {
      $tem_directdebit_id = $t[$i];
      $tem_directdebit_id_i = $i;


      if (empty($t[$i])) {
        $virhe[$i] = t("Directdebit-sopimus on pakollinen tieto");
      }
    }

    if (mysql_field_name($result, $i) == 'tunnus') {

      $query = "SELECT tunnus
                FROM directdebit_asiakas
                WHERE yhtio        = '$kukarow[yhtio]'
                AND liitostunnus   = '$tem_liitostunnus'
                AND directdebit_id = '$tem_directdebit_id'
                and tunnus        != '$tunnus'";
      $results = pupe_query($query);

      if (mysql_num_rows($results) > 0) {
        $virhe[$tem_directdebit_id_i] = $virhe[$i] = t("Direct Debit sopimus vastaavilla tiedoilla löytyy kannasta. Duplikaatit ovat kiellettyjä!");
      }
    }
  }
}
