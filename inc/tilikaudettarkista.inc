<?php

if(!function_exists("tilikaudettarkista")) {
  function tilikaudettarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {
    global $kukarow, $yhtiorow, $alias_set;

    if (mysql_field_name($result, $i) == "avaava_tase") {
      if ($t[$i] > 0) {
        $query = "SELECT tunnus
                  FROM lasku
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tila    = 'X'
                  AND alatila = 'A'
                  AND tunnus  = '{$t[$i]}'";
        $res = pupe_query($query);

        // jos tämä on vääränlainen tosite, niin nollataan tieto
        if (mysql_num_rows($res) != 1) {
          $t[$i] = 0;
        }
      }
    }
  }
}
