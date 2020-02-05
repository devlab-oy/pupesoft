<?php
$ulos = "";
$jatko = 1; // oletetaan normaali käsittely
$tyyppi = 1; // oletetaan rivin näkyvyys

//yhtiötä ei näytetä
if (mysql_field_name($result, $i) == "yhtio") {
  $tyyppi = 0;
}
