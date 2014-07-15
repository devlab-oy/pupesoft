<?php

require_once('inc/laite_huolto_functions.inc');

$ulos = "";
$jatko = 1; // oletetaan normaali käsittely
$tyyppi = 1; // oletetaan rivin näkyvyys

if (mysql_field_name($result, $i) == "tunnus" or mysql_field_name($result, $i) == "yhtio") {
  $tyyppi = 0;
}

if (mysql_field_type($result, $i) == "blob") {
  $ulos = "<td><textarea cols='50' rows='5' name='$nimi'>$trow[$i]</textarea></td>";
  $jatko = 0;
}

//tyyppi pitää löytyä tuotteen_avainsanat taulusta
if (mysql_field_name($result, $i) == "tyyppi") {
  $sammutin_tyypit = hae_mahdolliset_sammutin_tyypit();
  $sel = "";
  $ulos = "<td>";
  $ulos .= "<select name='{$nimi}'>";
  foreach ($sammutin_tyypit as $key => $sammutin_tyyppi) {
    if ($trow[$i] == $key) {
      $sel = "SELECTED";
    }
    $ulos .= "<option value='{$key}' {$sel}>".ucfirst($sammutin_tyyppi)."</option>";
    $sel = "";
  }
  $ulos .= "</select>";
  $ulos .= "</td>";

  $jatko = 0;
}

//koko on numero??
if (mysql_field_name($result, $i) == "koko") {

}

//olosuhde pitää löytyä avainsanat taulusta OLOSUHDE
if (mysql_field_name($result, $i) == "olosuhde") {
  $olosuhde_result = t_avainsana('OLOSUHDE');
  $olosuhteet = array();
  while ($olosuhde = mysql_fetch_assoc($olosuhde_result)) {
    if ($trow[$i] == $olosuhde['selite']) {
      $olosuhde['selected'] = "SELECTED";
    }
    else {
      $olosuhde['selected'] = "";
    }
    $olosuhteet[] = $olosuhde;
  }

  $ulos = "<td>";
  $ulos .= "<select name='{$nimi}'>";
  foreach ($olosuhteet as $olosuhde) {
    $ulos .= "<option value='{$olosuhde['selite']}' {$olosuhde['selected']}>".t($olosuhde['selitetark'])."</option>";
  }
  $ulos .= "</select>";
  $ulos .= "</td>";

  $jatko = 0;
}

//toimenpide on palvelu tuote joka pitää löytyä tuote taulusta
if (mysql_field_name($result, $i) == "toimenpide") {
  $query = "  SELECT *
        FROM tuote
        WHERE yhtio = '{$kukarow['yhtio']}'
        AND tuotetyyppi = 'K'";
  $toimenpide_result = pupe_query($query);
  $toimenpiteet = array();
  while($toimenpide = mysql_fetch_assoc($toimenpide_result)) {
    if ($trow[$i] == $toimenpide['tuoteno']) {
      $toimenpide['selected'] = "SELECTED";
    }
    else {
      $toimenpide['selected'] = "";
    }

    $toimenpiteet[] = $toimenpide;
  }

  $ulos = "<td>";
  $ulos .= "<select name='{$nimi}'>";
  foreach($toimenpiteet as $toimenpide) {
    $ulos .= "<option value='{$toimenpide['tuoteno']}' {$toimenpide['selected']}>{$toimenpide['nimitys']}</option>";
  }
  $ulos .= "</select>";
  $ulos .= "</td>";

  $jatko = 0;
}

if (mysql_field_name($result, $i) == "huoltovali") {
  $huoltovalit = huoltovali_options();
  $sel = "";
  $ulos = "<td>";
  $ulos .= "<select name='{$nimi}'>";
  foreach ($huoltovalit as $key => $value) {
    if ($trow[$i] == $key) {
      $sel = "SELECTED";
    }
    $ulos .= "<option value='{$key}' {$sel}>{$value['dropdown_text']}</option>";
    $sel = "";
  }
  $ulos .= "</select>";
  $ulos .= "</td>";

  $jatko = 0;
}

if (mysql_field_name($result, $i) == "pakollisuus") {
  $tyyppi = 0;
}
