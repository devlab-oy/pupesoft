<?php

if (php_sapi_name() != 'cli') {
  die('clionly!');
}

if (!isset($argv[1])) {
  echo "Anna Puperoot\n";
  exit(1);
}

$pupesoft_root = $argv[1];

if (!is_dir($pupesoft_root) or !is_file("{$pupesoft_root}/inc/salasanat.php")) {
  echo "Pupesoft root missing!";
  exit(1);
}

if (!isset($argv[2])) {
  echo "Poistettaavat yritykset puuttuu!";
  exit(1);
}

// Pupesoft root include_pathiin
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.$pupesoft_root);

// Otetaan tietokanta connect
require "inc/connect.inc";
require "inc/functions.inc";

// Logitetaan ajo
cron_log();

// Tämä vaatii paljon muistia
error_reporting(E_ALL);
ini_set("display_errors", 1);
unset($pupe_query_debug);

function is_log($str) {
  echo date("d.m.Y @ G:i:s") . ": $str\n";
}


// Shiftataan skriptin nimi ja Puperoot pois argv -arraystä
$yhtiot_argv = $argv;
array_shift($yhtiot_argv);
array_shift($yhtiot_argv);
$yhtiot = "";

// Testataan, että kaikki löytyy
foreach ($yhtiot_argv as $yhtio) {
  hae_yhtion_parametrit($yhtio);
  $yhtiot .= "'{$yhtio}', ";
}

$yhtiot = substr($yhtiot, 0, -2);

is_log("Poistetaan kannasta $yhtiot");

// Haetaan kaikki tietokantataulut
$query = "show tables";
$tables_result = pupe_query($query);

while ($row = mysql_fetch_row($tables_result)) {
  // Tietokantataulun nimi
  $table = $row[0];

  // Katsotaan onko taulussa yhtio -sarake
  $query = "SHOW COLUMNS FROM {$table} like 'yhtio'";
  $result = pupe_query($query);

  // Jos meillä on yhtio -sarake, poistetaan sieltä kaikki yhtion tietueet
  if (mysql_num_rows($result) == 1) {
    is_log("Tyhjennetään {$table}");
    $query = "DELETE QUICK FROM {$table} WHERE yhtio IN ($yhtiot)";
    pupe_query($query);
  }
}

is_log("Valmis");
