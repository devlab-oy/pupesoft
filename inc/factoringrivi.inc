<?php

$ulos = "";
$jatko = 1; // oletetaan normaali käsittely
$tyyppi = 1; // oletetaan rivin näkyvyys

if ($i == 0) { //yhtiötä ei näytetä
  $tyyppi = 0;
}

if ((mysql_field_name($result, $i) == "teksti_fi") or
  (mysql_field_name($result, $i) == "teksti_en") or
  (mysql_field_name($result, $i) == "teksti_se") or
  (mysql_field_name($result, $i) == "teksti_ee")) {
  $ulos = "<td><textarea cols='100' rows='8' name='$nimi'>$trow[$i]</textarea></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "factoringyhtio") {

  $sel = array_fill_keys(array($trow[$i]), " selected") +
    array_fill_keys(array('NORDEA', 'SAMPO', 'SEB', 'OKO', 'SVEA', 'COLLECTOR', 'AKTIA'), '');

  $ulos  = "<td><select name='$nimi'>";
  $ulos .= "<option value='NORDEA' {$sel['NORDEA']}>".t("Nordea Factoring")."</option>";
  $ulos .= "<option value='SAMPO' {$sel['SAMPO']}>".t("Danske Factoring")."</option>";
  $ulos .= "<option value='SEB' {$sel['SEB']}>".t("SEB Factoring")."</option>";
  $ulos .= "<option value='OKO' {$sel['OKO']}>".t("OP Factoring")."</option>";
  $ulos .= "<option value='SVEA' {$sel['SVEA']}>".t("Svea Factoring")."</option>";
  $ulos .= "<option value='COLLECTOR' {$sel['COLLECTOR']}>".t("Collector Factoring")."</option>";
  $ulos .= "<option value='AKTIA' {$sel['AKTIA']}>".t("Aktia Factoring")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if (mysql_field_name($result, $i) == "valkoodi") { //valuutta
  $query = "SELECT nimi, tunnus
            FROM valuu
            WHERE yhtio = '$kukarow[yhtio]'
             ORDER BY jarjestys";
  $vresult = pupe_query($query);

  $ulos = "<td><select name='$nimi'>";

  while ($vrow=mysql_fetch_array($vresult)) {
    $sel="";
    if ($trow[$i] == $vrow['nimi']) {
      $sel = "selected";
    }
    $ulos .= "<option value = '$vrow[nimi]' $sel>$vrow[nimi]";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "pankki_tili") { //valuutta
  $query = "SELECT nimi, tilino
            FROM yriti
            WHERE yhtio  = '$kukarow[yhtio]'
            and kaytossa = ''
             ORDER BY nimi";
  $vresult = pupe_query($query);

  $ulos = "<td><select name='$nimi'>";

  while ($vrow=mysql_fetch_array($vresult)) {
    $sel="";
    if ($trow[$i] == $vrow['tilino']) {
      $sel = "selected";
    }
    $ulos .= "<option value = '$vrow[tilino]' $sel>$vrow[nimi] $vrow[tilino]";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}


if (mysql_field_name($result, $i) == "viitetyyppi") {

  $sel = "";

  if ($trow[$i] != '') {
    $sel = "SELECTED";
  }

  $ulos  = "<td><select name='$nimi'>";
  $ulos .= "<option value=''> ".t("Laskun viite muodostetaan sopimusnumerosta, välimerkistä ja laskunumerosta")."</option>";
  $ulos .= "<option value='X' $sel> ".t("Laskun viite muodostetaan laskunumerosta")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}
