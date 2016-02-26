<?php
$ulos = "";
$jatko = 1; // oletetaan normaali käsittely
$tyyppi = 1; // oletetaan rivin näkyvyys

if ($i == 0) { //yhtiötä ei näytetä
  $tyyppi = 0;
}

if (mysql_field_name($result, $i) == "liitostunnus") {
  if ($lukitse_avaimeen != "") {
    $tyyppi = 4;
    $ulos = "<input type='hidden' name='$nimi' value='$lukitse_avaimeen'>";
    $jatko = 0;
  }
  else {
    $tyyppi = 2;
  }
}

if (mysql_field_name($result, $i) == "tyyppi") {
  if ($laji == "A") {
    $trow[$i] = "A";
  }
  elseif ($laji == "T") {
    $trow[$i] = "T";
  }

  $tyyppi = 4;
}

if (mysql_field_name($result, $i) == "rooli") {
  $vresult = t_avainsana("CRM_ROOLI");

  $ulos = "<td><select name='$nimi'><option value=''>".t("Ei roolia")."</option>";

  while ($row = mysql_fetch_array($vresult)) {
    $sel = "";
    if ($row["selite"] == $trow[$i]) $sel = 'selected';
    $ulos .= "<option value='$row[selite]' $sel> $row[selite] - $row[selitetark]</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "suoramarkkinointi") {

  $vresult = t_avainsana("CRM_SUORAMARKKI");

  $ulos = "<td><select name='suoramarkkinointi'><option value=''>".t("Ei suoramarkkinointitietoja")."</option>";

  while ($row = mysql_fetch_array($vresult)) {
    $sel = "";
    if ($row["selite"] == $trow[$i]) $sel = 'selected';
    $ulos .= "<option value='$row[selite]' $sel> $row[selite] - $row[selitetark]</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "tilausyhteyshenkilo") {

  $sel = "";
  if ($trow[$i] != "" or (isset($tyhthenkilo) and $tyhthenkilo != "")) $sel = "SELECTED";

  $ulos = "<td><select name='$nimi'>";
  $ulos .= "<option value='' >".t("Ei")."</option>";
  $ulos .= "<option value='K' $sel>".t("Kyllä")."</option>";
  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "oletusyhteyshenkilo") {

  $sel = "";
  if ($trow[$i] != "") $sel = "SELECTED";

  $ulos = "<td><select name='$nimi'>";
  $ulos .= "<option value='' >".t("Ei")."</option>";
  $ulos .= "<option value='K' $sel>".t("Kyllä")."</option>";
  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "verkkokauppa_salasana") {
  if (!isset($presta_api_key)) {
    $tyyppi = 0;
    $jatko = 0;
  }
}

if (mysql_field_name($result, $i) == "aktivointikuittaus") {

  $sel = "";
  if ($trow[$i] != "") $sel = "SELECTED";

  if (isset($magento_asiakas_aktivointi)) {
    $ulos = "<td><select name='$nimi'>";
    $ulos .= "<option value='' >".t("Ei")."</option>";
    $ulos .= "<option value='K' $sel>".t("Kyllä")."</option>";
    $ulos .= "</select></td>";
    $jatko = 0;
  }
  else {
    $tyyppi = 0;
    $jatko = 0;
  }
}
