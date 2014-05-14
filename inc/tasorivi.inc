<?php

$ulos   = "";
$jatko  = 1; // oletetaan normaali käsittely
$tyyppi = 1; // oletetaan rivin näkyvyys

// yhtiötä ei näytetä
if ($i == 0) {
  $tyyppi = 0;
}

if (mysql_field_name($result, $i) == "tyyppi") {

  $seltyyppi = array();
  $seltyyppi[$trow[$i]] = "SELECTED";

  $ulos  = "<td><select name='$nimi'>";
  $ulos .= "<option value = 'S' $seltyyppi[S]>".t("Sisäinen")."</option>";
  $ulos .= "<option value = 'U' $seltyyppi[U]>".t("Ulkoinen")."</option>";
  $ulos .= "<option value = 'A' $seltyyppi[A]>".t("ALV")."</option>";
  $ulos .= "<option value = 'B' $seltyyppi[B]>".t("Tulosseuranta")."</option>";
  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "kumulatiivinen") {

  $sel = array();
  $sel[$trow[$i]] = "SELECTED";

  $ulos  = "<td><select name='$nimi'>";
  $ulos .= "<option value = ''>".t("Ei")."</option>";
  $ulos .= "<option value = 'X' $sel[X]>".t("Kyllä")." ".t("Tulosseurannassa tämä taso lasketaan tilikauden alusta")."</option>";
  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "kayttotarkoitus") {

  if ($seltyyppi["U"] == "SELECTED") {
    $sel = array_fill_keys(array($trow[$i]), " selected") + array('M' => '', 'O' => '');

    $ulos  = "<td><select name='$nimi'>";
    $ulos .= "<option value = ''>".t("Ei valintaa")."</option>";
    $ulos .= "<option value = 'M' $sel[M]>".t("Liikevaihto")."</option>";
    $ulos .= "<option value = 'O' $sel[O]>".t("Ostot, Aineet, tarvikkeet ja tavarat")."</option>";
    $ulos .= "</select></td>";
    $jatko = 0;
  }
  else {
    $tyyppi = 0;
  }
}
