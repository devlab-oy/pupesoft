<?php

if (!function_exists("pakkaamotarkista")) {
  function pakkaamotarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {
    global $kukarow, $yhtiorow, $alias_set, $upd, $uusi;

    static $pakkaamonimi, $pakkaamolokero, $pakkaamovarasto;

    if (mysql_field_name($result, $i) == "nimi") {
      if (trim($t[$i]) == '') {
        $virhe[$i] .= t("Nimi ei saa olla tyhjä")."!";
      }
      else {
        $t[$i] = trim(mysql_real_escape_string($t[$i]));
        $pakkaamonimi = $t[$i];
      }
    }

    if (mysql_field_name($result, $i) == "lokero") {

      if (trim($t[$i]) == '') {
        $virhe[$i] .= t("Lokero ei saa olla tyhjä")."!";
      }
      else {
        $t[$i] = trim(mysql_real_escape_string($t[$i]));
        $pakkaamolokero = $t[$i];
      }

    }

    if (mysql_field_name($result, $i) == "varasto") {
      if (trim($t[$i]) == '') {
        $virhe[$i] .= t("Valitse jokin varasto")."!";
      }
      else {
        $t[$i] = trim(mysql_real_escape_string($t[$i]));

        if (isset($upd) and $upd == 1) {
          $pakkaamovarasto = $t[$i];
          
          if ($t["varasto"] == $trow["varasto"] and $t["lokero"] != $trow["lokero"]) {

            $query = "  SELECT *
                  FROM pakkaamo
                  WHERE yhtio = '$kukarow[yhtio]'
                  AND lokero = '$pakkaamolokero'
                  AND varasto = '$pakkaamovarasto'";
            $pakkaamo_chk_res = mysql_query($query) or pupe_error($query);

            if (mysql_num_rows($pakkaamo_chk_res) != 0) {
              $virhe[$i] .= t("Päällekkäisiä lokeroita samassa varastossa")."!";
            }
          }
        }
      }
    }
    
    if (mysql_field_name($result, $i) == "printteri0") {
      if (trim($t[$i]) == '') {
        $virhe[$i] .= t("Pakkaamon tulostin ei saa olla tyhjä")."!";
      }
      else {
        $t[$i] = trim(mysql_real_escape_string($t[$i]));
      }
    }
    if (mysql_field_name($result, $i) == "printteri1") {
      if (trim($t[$i]) == '') {
        $virhe[$i] .= t("Pakkaamon tulostin ei saa olla tyhjä")."!";
      }
      else {
        $t[$i] = trim(mysql_real_escape_string($t[$i]));
      }
    }
    if (mysql_field_name($result, $i) == "printteri2") {
      if (trim($t[$i]) == '') {
        $virhe[$i] .= t("Pakkaamon tulostin ei saa olla tyhjä")."!";
      }
      else {
        $t[$i] = trim(mysql_real_escape_string($t[$i]));
      }
    }
    if (mysql_field_name($result, $i) == "printteri3") {
      if (trim($t[$i]) == '') {
        $virhe[$i] .= t("Pakkaamon tulostin ei saa olla tyhjä")."!";
      }
      else {
        $t[$i] = trim(mysql_real_escape_string($t[$i]));
      }
    }
    if (mysql_field_name($result, $i) == "printteri4") {
      if (trim($t[$i]) == '') {
        $virhe[$i] .= t("Pakkaamon tulostin ei saa olla tyhjä")."!";
      }
      else {
        $t[$i] = trim(mysql_real_escape_string($t[$i]));
      }
    }
    if (mysql_field_name($result, $i) == "printteri6") {
      if (trim($t[$i]) == '') {
        $virhe[$i] .= t("Pakkaamon tulostin ei saa olla tyhjä")."!";
      }
      else {
        $t[$i] = trim(mysql_real_escape_string($t[$i]));
      }
    }
    if (mysql_field_name($result, $i) == "printteri7") {
      if (trim($t[$i]) == '') {
        $virhe[$i] .= t("Pakkaamon tulostin ei saa olla tyhjä")."!";
      }
      else {
        $t[$i] = trim(mysql_real_escape_string($t[$i]));
      }
    }
  }
}
