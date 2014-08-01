<?php

require "salasanat.php";

// $masterlink on aina linkki master-kantaan.
// $link on linkki master- tai slave-kantaan. Jos slave on käytössä ja aktivoitu $useslave-muuttujalla niin silloin tuo pointaa siis slave-kantaan.
// $temporarylink on linkki omaan kantaan johon pupe tekee temporary tablet. Jos $tempdbhost:ia ei oo määritelty, niin silloin $temporarylink pointaa master-kantaan.

// Katsotaan halutaanko käyttää slavea
if (isset($useslave)) {
  $_useslave = (int) $useslave;
}
else {
  $_useslave = 0;
}

// Default, ei käytetä slavea
$useslave = 0;

// Otetaan isoin mahdollinen slave käyttöön
for ($_slave_i = $_useslave; $_slave_i > 0; $_slave_i--) {
  if (!empty($slavedb[$_slave_i])) {
    $useslave = $_slave_i;
    break;
  }
}

// Jos Pupeen on konffattu oma database temporary tableja varten
if (isset($tempdbhost) and $tempdbhost != "") {
  $temporarylink = mysql_connect($tempdbhost, $tempdbuser, $tempdbpass) or die ("Ongelma tietokantapalvelimessa $tempdbhost");
  mysql_select_db($tempdbkanta, $temporarylink) or die ("Tietokantaa $tempdbkanta ei löydy palvelimelta $tempdbhost! (connect.inc)");
  mysql_set_charset("latin1", $temporarylink);
  mysql_query("set group_concat_max_len=1000000", $temporarylink);
}

// Käytetään slave kantaa
if ($useslave > 0) {
  // Otetaan yhteys myös master-kantaan koska tietyissä paikoissa tätä tarvitaan
  $masterlink = mysql_connect($dbhost, $dbuser, $dbpass) or die ("Ongelma tietokantapalvelimessa $dbhost");
  mysql_select_db($dbkanta, $masterlink) or die ("Tietokantaa $dbkanta ei löydy palvelimelta $dbhost! (connect.inc)");
  mysql_set_charset("latin1", $masterlink);
  mysql_query("set group_concat_max_len=1000000", $masterlink);

  $link = mysql_connect($slavedb[$useslave], $slaveuser[$useslave], $slavepass[$useslave]) or die ("Ongelma tietokantapalvelimessa: '$slavedb[$useslave]'");
}
else {
  $link = mysql_connect($dbhost, $dbuser, $dbpass) or die ("Ongelma tietokantapalvelimessa $dbhost");
  $masterlink = $link;
}

mysql_select_db($dbkanta, $link) or die ("Tietokantaa $dbkanta ei löydy palvelimelta $dbhost! (connect.inc)");
mysql_set_charset("latin1", $link);
mysql_query("set group_concat_max_len=1000000", $link);

// jos debugataan pienillä arvoilla, ni pakotetaan myös queryt kuntoon!
if (isset($GLOBALS["pupe_query_debug"]) and $GLOBALS["pupe_query_debug"] > 0 and $GLOBALS["pupe_query_debug"] < 1) {
  mysql_query("set session sql_mode = 'STRICT_ALL_TABLES,ONLY_FULL_GROUP_BY,NO_ZERO_IN_DATE'", $link); // NO_ZERO_DATE ois myös kova!
}

if (!isset($temporarylink)) $temporarylink = $masterlink;
