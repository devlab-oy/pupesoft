<?php

$ulos = "";
$jatko = 1; // oletetaan normaali käsittely

if (mysql_field_name($result, $i) == "laskutus_nimi" or
  mysql_field_name($result, $i) == "laskutus_nimitark" or
  mysql_field_name($result, $i) == "laskutus_osoite" or
  mysql_field_name($result, $i) == "laskutus_osoitetark" or
  mysql_field_name($result, $i) == "laskutus_postino" or
  mysql_field_name($result, $i) == "laskutus_postitp") {

  $tyyppi = 1;
}
elseif (mysql_field_name($result, $i) == "laskutus_maa") {

  $query = "SELECT distinct koodi, nimi
            FROM maat
            WHERE nimi != ''
            ORDER BY koodi";
  $vresult = mysql_query($query) or pupe_error($query);
  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";

  $ulos .= "<option value = '' ></option>";

  while ($vrow = mysql_fetch_array($vresult)) {
    $sel="";
    if (strtoupper($trow[$i]) == strtoupper($vrow[0])) {
      $sel = "selected";
    }
    elseif($trow[$i] == "" and strtoupper($vrow[0]) == strtoupper($yhtiorow["maa"])) {
      $sel = "selected";
    }
    $ulos .= "<option value = '".strtoupper($vrow[0])."' $sel>".t($vrow[1])."</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}
else {
  $tyyppi = 0;
}
