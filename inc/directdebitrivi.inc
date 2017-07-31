<?php

$ulos = "";
$jatko = 1; // oletetaan normaali käsittely
$tyyppi = 1; // oletetaan rivin näkyvyys

if ($i == 0) { //yhtiötä ei näytetä
  $tyyppi = 0;
}

if (mysql_field_name($result, $i) == "rahalaitos") {

  $sel = array_fill_keys(array($trow[$i]), " selected") +
    array_fill_keys(array('NORDEA'), '');

  $ulos  = "<td><select name='$nimi'>";
  $ulos .= "<option value='NORDEA' {$sel['NORDEA']}>".t("Nordea")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ((mysql_field_name($result, $i) == "teksti_fi") or
  (mysql_field_name($result, $i) == "teksti_en") or
  (mysql_field_name($result, $i) == "teksti_se")) {
  $ulos = "<td><textarea cols='80' rows='8' name='$nimi'>$trow[$i]</textarea></td>";
  $jatko = 0;
}

