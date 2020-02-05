<?php
$ulos = "";
$jatko = 1; // oletetaan normaali käsittely
$tyyppi = 1; // oletetaan rivin näkyvyys

//yhtiötä ei näytetä
if (mysql_field_name($result, $i) == "yhtio") {
  $tyyppi = 0;
}

//yhtiötä ei näytetä
if (mysql_field_name($result, $i) == "atunnus") {

  if ($trow[$i] != '') {

    if ($tem_tyyppi == 'RA') {
      $type = 'cv';
    }
    else {
      $type = 'pc';
    }

    $params = array(
      'tyyppi' => $type,
      'autoid' => $trow[$i]
    );

    $sresult = td_getversion($params);
    $srow = mysql_fetch_assoc($sresult);

    $ulos  = "<td><input type='text' name='{$nimi}' value='{$trow[$i]}'> {$srow['manu']} {$srow['model']} {$srow['versio']}</td>";
  }
  else {
    $ulos  = "<td><input type='text' name='{$nimi}' value='{$trow[$i]}'></td>";
  }

  $jatko = 0;
}

if (mysql_field_name($result, $i) == "tyyppi") {

  $sel = array_fill_keys(array($trow[$i]), " selected") + array('HA' => '', 'RA' => '');

  $tem_tyyppi = $trow[$i];

  $ulos  = "<td><select name='{$nimi}'>";
  $ulos .= "<option value='HA' {$sel['HA']}>".t("Henkilöauto")."</option>";
  $ulos .= "<option value='RA' {$sel['RA']}>".t("Raskasajoneuvo")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if (mysql_field_name($result, $i) == 'status') {
  $sel = array_fill_keys(array($trow[$i]), " selected") + array('o' => '', 'L' => '');

  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 300).">";
  $ulos .= "<option value=''>".t("Aktiivinen")."</option>";
  $ulos .= "<option value='L'{$sel['L']}>".t("Aktiivinen")." (".t("lukittu").")</option>";
  $ulos .= "<option value='o'{$sel['o']}>".t("Poistettu")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}
