<?php

if(!function_exists("yhtion_toimipaikat_parametrittarkista")) {
  function yhtion_toimipaikat_parametrittarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {
    global $kukarow, $yhtiorow, $alias_set, $ulosarray, $yllapitonappi;
    static $chtoimipaikka, $chparametri, $chparametri_ind;

    if (mysql_field_name($result, $i) == "toimipaikka") {
      $chtoimipaikka = (int) $t[$i];

      $query = "SELECT *
                FROM yhtion_toimipaikat
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = $chtoimipaikka";
      $vares = pupe_query($query);

      if (mysql_num_rows($vares) != 1) {
        $virhe[$i] = t("Toimipaikka puuttuu tai sitä ei löydy! ");
      }
    }

    if (mysql_field_name($result, $i) == "parametri") {
      $chparametri = $t[$i];
      $chparametri_ind = $i;
    }

    if (mysql_field_name($result, $i) == "tunnus") {
      if (!isset($yllapitonappi)) {
        $virhe[$i] = "XXX";
      }


      $query = "SELECT *
                FROM yhtion_toimipaikat_parametrit
                WHERE yhtio      = '{$kukarow['yhtio']}'
                AND toimipaikka  = '$chtoimipaikka'
                AND parametri    = '$chparametri'
                AND tunnus      != '$trow[$i]'";
      $vares = pupe_query($query);

      if (mysql_num_rows($vares) > 0) {
        $virhe[$chparametri_ind] = $virhe[$i] = t("VIRHE: Näillä tiedoilla on jo parametri järjestelmässä!");
      }
    }
  }
}
