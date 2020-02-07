<?php

$ulos = "";
$jatko = 1; // oletetaan normaali käsittely
$tyyppi = 1; // oletetaan rivin näkyvyys

//yhtiötä ei näytetä
if (mysql_field_name($result, $i) == "yhtio") {
  $tyyppi = 0;
}

if (mysql_field_name($result, $i) == "tuoteryhma") {

  if (isset($selitekenttaan) and trim($selitekenttaan) == '') {
    $trow[$i] = 0;
  }
  elseif (isset($selitekenttaan) and trim($selitekenttaan) != '') {
    $trow[$i] = $selitekenttaan;
  }


  $ulos = "<td><input name='$nimi' value='{$trow[$i]}' /></td>\n";

  $jatko = 0;
}
