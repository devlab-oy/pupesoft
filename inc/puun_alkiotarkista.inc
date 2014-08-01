<?php

if (!function_exists("puun_alkiotarkista")) {
  function puun_alkiotarkista(&$t, $i, $result, $tunnus, &$virhe, $trow) {
    global $kukarow, $yhtiorow, $alias_set, $alasveto, $table_tarkenne;
    static $tem_laji, $liitos_ind, $tem_liitos, $tem_kutsuja;

    if (mysql_field_name($result, $i) == "liitos") {
      $liitos_ind = $i;
      $tem_liitos = isset($trow['liitos']) ? $trow['liitos']: $t[$i];
    }

    if (mysql_field_name($result, $i) == "laji") {
      $tem_laji = !empty($trow['laji']) ? $trow['laji'] : $t[$i];

      if ($t[$i] == '') {
        $virhe[$i] = t("Lajia ei voi jättää tyhjäksi")."!";
      }
    }

    if (mysql_field_name($result, $i) == "kutsuja") {
      $tem_kutsuja = !empty($trow['kutsuja']) ? $trow['kutsuja'] : $t[$i];
    }

    if (mysql_field_name($result, $i) == "puun_tunnus") {
      if ($t[$i] == '') {
        $virhe[$i] = t("Puun tunnusta ei voi jättää tyhjäksi")."!";
      }
    }

    if (mysql_field_name($result, $i) == "tunnus") {

      if ((!isset($trow["luedata_toiminto"]) or $trow["luedata_toiminto"] != "POISTA") and ((strtoupper($tem_laji) == "TUOTE" and strtoupper($tem_kutsuja) == "ASIAKAS") or strtoupper($tem_laji) == 'ASIAKAS' or strtoupper($table_tarkenne) == 'ASIAKAS')) {
        $query = "SELECT tunnus
                  FROM asiakas
                  WHERE yhtio  = '{$kukarow['yhtio']}'
                  AND laji    != 'P'
                  AND tunnus   = '$tem_liitos'";
        $asiakkaan_haku_res = pupe_query($query);

        if (mysql_num_rows($asiakkaan_haku_res) == 0) {
          $virhe[$i] = $virhe[$liitos_ind] = t("Asiakasta ei löydy")."!";
        }
      }
      elseif ((!isset($trow["luedata_toiminto"]) or $trow["luedata_toiminto"] != "POISTA") and ((strtoupper($tem_laji) == "ASIAKAS" and strtoupper($tem_kutsuja) == "TUOTE") or strtoupper($tem_laji) == 'TUOTE' or strtoupper($table_tarkenne) == 'TUOTE')) {
        $query = "SELECT tunnus
                  FROM tuote
                  WHERE yhtio  = '{$kukarow['yhtio']}'
                  AND status  != 'P'
                  AND tuoteno  = '$tem_liitos'";
        $asiakkaan_haku_res = pupe_query($query);

        if (mysql_num_rows($asiakkaan_haku_res) == 0) {
          $virhe[$i] = $virhe[$liitos_ind] = t("Tuotetta ei löydy $tem_laji $tem_kutsuja")."!";
        }
      }
    }
  }
}
