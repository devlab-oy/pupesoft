<?php

$ulos = "";
$jatko = 1; // oletetaan normaali käsittely
$tyyppi = 1; // oletetaan rivin näkyvyys

if ($i == 0) { //yhtiötä ei näytetä
  $tyyppi = 0;
}

if (mysql_field_name($result, $i) == "ytunnus" and $lukitse_avaimeen != "") {
  $ulos = "<input type='hidden' name='$nimi' value='$lukitse_avaimeen'>";
  $tyyppi = 4;
  $jatko = 0;
}
if (mysql_field_name($result, $i) == "tyyppi") {

  $sela = $selb = '';
  if ($trow[$i]=='S') $selb='SELECTED';
  else $sela = "SELECTED";
  $ulos = "<td>
      <select name='$komtyyppi' ".js_alasvetoMaxWidth($nimi, 300).">
      <option value='' $sela>".t("Asiakas näkee kommentin")."</option>
      <option value='S' $selb>".t("Sisäinen kommentti")."</option>
      </select></td>";
  $jatko = 0;
}
