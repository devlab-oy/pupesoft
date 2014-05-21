<?php

$ulos = "";
$jatko = 1; // oletetaan normaali käsittely
$tyyppi = 1; // oletetaan rivin näkyvyys

if ($i == 0) { //yhtiötä ei näytetä
  $tyyppi = 0;
}

if (mysql_field_name($result, $i) == "automaattipaivitys") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "selected";

  $ulos .= "<option value = ''>".t("Valuutta pävitetään automaattisesti")."</option>";
  $ulos .= "<option value = 'E' $sel[E]>".t("Valuuttaa ei pävitetä automaattisesti")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}
