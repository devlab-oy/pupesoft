<?php

$ulos = "";
$jatko = 1; // oletetaan normaali käsittely
$tyyppi = 1; // oletetaan rivin näkyvyys

if (mysql_field_name($result, $i) == "yhtio") {
  $tyyppi = 0;
}

if (mysql_field_name($result, $i) == "try") {
  // tehdään avainsana query
  $sresult = t_avainsana("TRY");

  $ulos = "<td><select name='$nimi'>";

  $ulos .= "<option value='*' $sel>".t("Kaikki tuoteryhmät")."</option>";

  while ($srow = mysql_fetch_array($sresult)) {
    $sel = '';
    if ($trow[$i] == $srow["selite"]) {
      $sel = "selected";
    }
    $ulos .= "<option value='$srow[selite]' $sel>$srow[selite] $srow[selitetark]</option>";
  }

  $sel = '';
  if ($trow[$i] == "*") {
    $sel = "selected";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "osasto") {
  // tehdään avainsana query
  $sresult = t_avainsana("OSASTO");

  $ulos = "<td><select name='$nimi'>";

  $ulos .= "<option value='*' $sel>".t("Kaikki osastot")."</option>";

  while ($srow = mysql_fetch_array($sresult)) {
    $sel = '';
    if ($trow[$i] == $srow["selite"]) {
      $sel = "selected";
    }
    $ulos .= "<option value='$srow[selite]' $sel>$srow[selite] $srow[selitetark]</option>";
  }

  $sel = '';
  if ($trow[$i] == "*") {
    $sel = "selected";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "laji") {

  // Vaihtoehdot
  $selected = array_fill_keys(array('K','O','X','Y'), '');
  $selected[$trow[$i]] = "SELECTED";

  $ulos = "<td><select name='$nimi'>";
  $ulos .= "<option value='K' $selected[K]>".t("Korvaavia ja vastaavia tuotteita ei myydä")."</option>";
  $ulos .= "<option value='O' $selected[O]>".t("Korvaavia tuotteita myydään, mikäli korvaavuusketjusta löytyy tilattu määrä")."</option>";
  $ulos .= "<option value='X' $selected[X]>".t("Korvaavia ja Vastaavia tuotteita myydään")."</option>";
  $ulos .= "<option value='Y' $selected[Y]>".t("Korvaavia ja Vastaavuusketjun päätuotetta myydään")."</option>";

  $ulos .= "</select></td>";
  $jatko = 0;
}
