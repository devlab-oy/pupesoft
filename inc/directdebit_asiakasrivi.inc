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

if (mysql_field_name($result, $i) == "directdebit_id") {
  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 300).">";

  $query = "SELECT *
            FROM directdebit
            WHERE yhtio = '{$kukarow['yhtio']}'
            ORDER BY 1";
  $ddres = pupe_query($query);

  while ($ddrow = mysql_fetch_assoc($ddres)) {
    $sel = $trow[$i] == $ddrow['tunnus'] ? " selected" : "";
    $ulos .= "<option value='{$ddrow['tunnus']}'{$sel}>{$ddrow['nimitys']}</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "valtuutus_pvm") {
  $tyyppi = 1.5;
}
