<?php

$ulos = "";
$jatko = 1; // oletetaan normaali käsittely
$tyyppi = 1; // oletetaan rivin näkyvyys

//yhtiötä ei näytetä
if (mysql_field_name($result, $i) == "yhtio") {
  $tyyppi = 0;
}

if (mysql_field_name($result, $i) == "liitostunnus") {
  if ($lukitse_avaimeen != "") {
    $tyyppi = 4;
    if ($lukitse_avaimeen != "") {
      $ulos = "<input type='hidden' name='$nimi' value='$lukitse_avaimeen'>";
      $jatko = 0;
    }
  }
}

if (mysql_field_name($result, $i) == "laji") {

  $sresult = t_avainsana("EXTASAVAINSANA");
  $ulos .= "<td><select name='$nimi'>";

  while ($srow = mysql_fetch_array($sresult)) {
    $sel = '';
    if ($srow['selite'] == $trow['laji']) {
      $sel = 'selected';
    }
    $ulos .= "<option value='{$srow['selite']}' $sel>{$srow['selite']}</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}
