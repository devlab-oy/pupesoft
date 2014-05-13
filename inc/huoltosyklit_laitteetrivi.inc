<?php

require_once('inc/laite_huolto_functions.inc');

$ulos = "";
$jatko = 1; // oletetaan normaali käsittely
$tyyppi = 1; // oletetaan rivin näkyvyys

if (mysql_field_name($result, $i) == "yhtio") {
  $tyyppi = 0;
}

if (mysql_field_type($result, $i) == "blob") {
  $ulos = "<td><textarea cols='50' rows='5' name='$nimi'>$trow[$i]</textarea></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "huoltosykli_tunnus") {

  //jos lukitse avaimeen, niin ollaan näyttämässä huoltosyklit_laitteita iframessa laite näkymässä
  //tällöin haetaan laitteen tiedot sekä paikka ja dropdownissa näytetään vain kyseisen laitteen huoltosyklit
  if (!empty($lukitse_avaimeen)) {
    $laite = hae_laite($lukitse_avaimeen);
    $laite['paikka'] = hae_paikka($laite['paikka']);
  }

  $huoltosyklit = hae_laitteelle_mahdolliset_huoltosyklit($laite['laite_tyyppi'], $laite['laite_koko'], $laite['paikka']['olosuhde']);

  $ulos = "<td>";
  $ulos .= "<select class='{$otsikko}' name='{$nimi}'>";
  $sel = "";
  foreach ($huoltosyklit as $huoltosykli) {
    if ($trow[$i] == $huoltosykli['tunnus']) {
      $sel = "SELECTED";
    }
    $ulos .= "<option value='{$huoltosykli['tunnus']}' {$sel}>".t($huoltosykli['dropdown_text'])."</option>";
    $sel = "";
  }
  $ulos .= "</select>";
  $ulos .= "</td>";

  $jatko = 0;
}

if (mysql_field_name($result, $i) == "laite_tunnus") {
  // Jos tämä on liitetty tunnuksella:
  if ($trow[$i] > 0) {
    $ulos = "<input type='hidden' name='$nimi' value='$trow[$i]'>";
  }
  // Jos tää on uusi itemi
  elseif ((int)$tunnus == 0) {
    $ulos = "<input type='hidden' name='$nimi' value='$lukitse_avaimeen'>";
  }

  $tyyppi = 4;
  $jatko = 0;
}

//huoltoväli float aluksi annetaan päivissä, myöhemmin voi olla mittarilukema, käyttökerta
if (mysql_field_name($result, $i) == "huoltovali") {
  
}

//pakollisuus kyllä / ei
if (mysql_field_name($result, $i) == "pakollisuus") {
  $ulos = "<td>";
  $ulos .= "<select class='{$otsikko}' name='{$nimi}'>";
  $ulos .= "<option value=1>".t("Kyllä")."</option>";
  $ulos .= "<option value=0>".t("Ei")."</option>";
  $ulos .= "</select>";
  $ulos .= "</td>";

  $jatko = 0;
}

if (mysql_field_name($result, $i) == "viimeinen_tapahtuma") {
  $tyyppi = 0;
}
