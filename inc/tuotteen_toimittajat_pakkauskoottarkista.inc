<?php

if (!function_exists("tuotteen_toimittajat_pakkauskoottarkista")) {
  function tuotteen_toimittajat_pakkauskoottarkista(&$t, $i, $result, $tunnus, &$virhe, $trow) {
    global $kukarow, $yhtiorow, $alias_set, $upd, $uusi, $toim_tuoteno, $liitostunnus;

    static $chk_pakkauskoko, $chk_toim_tuoteno_tunnus;

    if (mysql_field_name($result, $i) == "toim_tuoteno_tunnus") {
      $chk_toim_tuoteno_tunnus = $t[$i];
    }

    if (mysql_field_name($result, $i) == "pakkauskoko") {

      $chk_pakkauskoko = trim($t[$i]);

      if (empty($chk_pakkauskoko)) {
        $virhe[$i] = t("Pakkauskoko ei saa olla tyhjä")."!";
      }

      if (!is_numeric($chk_pakkauskoko)) {
        $virhe[$i] = t("Pakkauskoko pitää olla numeerinen")."!";
      }
    }

    // Tsekataan ettei kannassa ole samanlaista settiä
    $query = "SELECT *
              FROM tuotteen_toimittajat_pakkauskoot
              WHERE yhtio              = '{$kukarow['yhtio']}'
              AND toim_tuoteno_tunnus  = '{$chk_toim_tuoteno_tunnus}'
              AND pakkauskoko          = '{$chk_pakkauskoko}'
              AND tunnus              != '{$tunnus}'";
    $chk_res = pupe_query($query);

    if (mysql_num_rows($chk_res) != 0) {
      $virhe[$i] = t("Samoilla tiedoilla oleva rivi on jo tietokannassa")."!";
    }
  }
}
