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
