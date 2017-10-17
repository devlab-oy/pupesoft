<?php

$ulos = "";
$jatko = 1; // oletetaan normaali käsittely
$tyyppi = 1; // oletetaan rivin näkyvyys

if (mysql_field_name($result, $i) == "tunnus" or
  mysql_field_name($result, $i) == "yhtio") {
  $tyyppi = 0;
}

if (mysql_field_name($result, $i) == "pakkaus") {
  $ulos = "<td><select name='$nimi'>";

  $query = "SELECT *
            FROM pakkaus
            WHERE yhtio = '{$kukarow['yhtio']}'
            ORDER BY jarjestys, pakkaus";
  $pakkaus_res = pupe_query($query);

  while ($pakkaus_row = mysql_fetch_assoc($pakkaus_res)) {

    $sel = '';
    if ($trow[$i] == $pakkaus_row['tunnus']) $sel = 'SELECTED';

    $ulos .= "<option value='{$pakkaus_row['tunnus']}' $sel>{$pakkaus_row['pakkaus']} - {$pakkaus_row['pakkauskuvaus']}</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;

}

if (mysql_field_name($result, $i) == "rahdinkuljettaja") {
  $ulos = "<td><select name='$nimi'>";

  $query = "SELECT *
            FROM rahdinkuljettajat
            WHERE yhtio = '{$kukarow['yhtio']}'
            ORDER BY nimi";
  $rahdinkuljettajat_res = pupe_query($query);

  while ($rahdinkuljettajat_row = mysql_fetch_assoc($rahdinkuljettajat_res)) {

    $sel = '';
    if ($trow[$i] == $rahdinkuljettajat_row['koodi']) $sel = 'SELECTED';

    $ulos .= "<option value='{$rahdinkuljettajat_row['koodi']}' $sel>{$rahdinkuljettajat_row['nimi']}</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}
