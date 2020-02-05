<?php

$ulos = "";
$jatko = 1; // oletetaan normaali käsittely
$tyyppi = 1; // oletetaan rivin näkyvyys


// yhtiötä ei näytetä
if (mysql_field_name($result, $i) == "yhtio") {
  $tyyppi = 0;
}


if (mysql_field_name($result, $i) == "yhteensopivuus_tuote_tunnus") {
  $apu['yhteensopivuus_tuote_tunnus'] = $lukitse_avaimeen;
  $ulos = "<td><input type='hidden' name='{$nimi}' value='{$lukitse_avaimeen}'>{$lukitse_avaimeen}</td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "kieli") {

  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 300).">";
  $sel  = "";

  $query  = "show columns from sanakirja";
  $fields =  mysql_query($query);

  while ($apurow = mysql_fetch_array($fields)) {
    if (strlen($apurow[0]) == 2) {
      $sel = "";
      if ($trow[$i] == $apurow[0]) {
        $sel = "selected";
      }
      elseif ($trow[$i] == "" and $apurow[0] == $yhtiorow["kieli"]) {
        $sel = "selected";
      }

      if ($apurow[0] != "tunnus") {
        $query = "SELECT distinct nimi FROM maat WHERE koodi='{$apurow[0]}'";
        $maare = mysql_query($query);
        $maaro = mysql_fetch_array($maare);
        $maa   = strtolower($maaro["nimi"]);
        if ($maa=="") $maa = $apurow[0];
        $ulos .= "<option value='{$apurow[0]}' {$sel}>".t("{$maa}")."</option>";
      }
    }
  }

  $ulos .= "</select></td>";

  $jatko = 0;
}

if (mysql_field_name($result, $i) == 'nakyvyys') {
  $sel = array_fill_keys(array($trow[$i]), " selected") + array('o' => '');

  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 300).">";
  $ulos .= "<option value=''>".t("Kyllä")."</option>";
  $ulos .= "<option value='K'>".t("Kyllä")." (".t("ei otsikkoa").")</option>";
  $ulos .= "<option value='o'{$sel['o']}>".t("Ei")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if (mysql_field_name($result, $i) == 'status') {
  $sel = array_fill_keys(array($trow[$i]), " selected") + array('P' => '', 'L' => '');

  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 300).">";
  $ulos .= "<option value=''>".t("Aktiivinen")."</option>";
  $ulos .= "<option value='L'{$sel['L']}>".t("Aktiivinen")." (".t("lukittu").")</option>";
  $ulos .= "<option value='P'{$sel['P']}>".t("Poistettu")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}
