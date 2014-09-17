<?php

ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__).PATH_SEPARATOR."/usr/share/pear".PATH_SEPARATOR."/usr/share/php/");

require "inc/connect.inc";
require "inc/functions.inc";

$laskuri = 0;

$query = "SELECT ultilno, swift, tilinumero, tunnus
          FROM toimi
          WHERE ultilno  = ''
          AND tilinumero not in ('', 0)";
$result = pupe_query($query);

while ($toimirow = mysql_fetch_array($result)) {

  $vastaus = luoiban(preg_replace("/[^0-9]/", "", $toimirow["tilinumero"]));
  $iban = trim($vastaus["iban"]);
  $bic = trim($vastaus["swift"]);

  if (tarkista_iban($iban) != "" and $bic != '') {
    $query = "UPDATE toimi SET
              ultilno      = '$iban',
              swift        = '$bic'
              WHERE tunnus = '$toimirow[tunnus]'";
    $update = pupe_query($query);
    $laskuri++;
  }
}

$query = "SELECT tilino, iban, bic, tunnus
          FROM yriti
          WHERE iban = ''";
$result = pupe_query($query);

while ($toimirow = mysql_fetch_array($result)) {

  $vastaus = luoiban(preg_replace("/[^0-9]/", "", $toimirow["tilino"]));
  $iban = trim($vastaus["iban"]);
  $bic = trim($vastaus["swift"]);

  if (tarkista_iban($iban) != "" and $bic != '') {
    $query = "UPDATE yriti SET
              iban         = '$iban',
              bic          = '$bic'
              WHERE tunnus = '$toimirow[tunnus]'";
    $update = pupe_query($query);
    $laskuri++;
  }
}

$query = "SELECT ultilno, swift, tilinumero, tunnus
          FROM lasku
          WHERE ultilno  = ''
          AND tilinumero not in ('', 0)
          AND tila       in ('H','M','P')";
$result = pupe_query($query);

while ($toimirow = mysql_fetch_array($result)) {

  $vastaus = luoiban(preg_replace("/[^0-9]/", "", $toimirow["tilinumero"]));
  $iban = trim($vastaus["iban"]);
  $bic = trim($vastaus["swift"]);

  if (tarkista_iban($iban) != "" and $bic != '') {
    $query = "UPDATE lasku SET
              ultilno      = '$iban',
              swift        = '$bic'
              WHERE tunnus = '$toimirow[tunnus]'";
    $update = pupe_query($query);
    $laskuri++;
  }
}

echo "\nPaivitettiin $laskuri rivia\n\n";
