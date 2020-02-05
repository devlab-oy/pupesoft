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

// Pupesoft root include_pathiin
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.$pupesoft_root);

// Otetaan tietokanta connect
require "inc/connect.inc";
require "inc/functions.inc";

// Logitetaan ajo
cron_log();

// Tämä vaatii paljon muistia
error_reporting(E_ALL);
ini_set("memory_limit", "5G");
ini_set("display_errors", 1);
unset($pupe_query_debug);

$query = "SELECT *
          FROM asiakasalennus
          WHERE yhtio  = 'atarv'
          AND ytunnus != ''
          AND asiakas  = 0";
$query_result = mysql_query($query) or pupe_error($query);

$total_items = mysql_num_rows($query_result);
$current_item = 0;

while ($query_row = mysql_fetch_assoc($query_result)) {

  $current_item++;
  progress_bar($current_item, $total_items);

  $query = "SELECT tunnus
            FROM asiakas
            WHERE yhtio = 'atarv'
            AND ytunnus = '{$query_row['ytunnus']}'";
  $query_result2 = mysql_query($query) or pupe_error($query);

  while ($query_row2 = mysql_fetch_assoc($query_result2)) {
    $query = "INSERT INTO asiakasalennus SET
              yhtio       = '{$query_row['yhtio']}',
              asiakas     = '{$query_row2['tunnus']}',
              tuoteno     = '{$query_row['tuoteno']}',
              ryhma       = '{$query_row['ryhma']}',
              alennus     = '{$query_row['alennus']}',
              alkupvm     = '{$query_row['alkupvm']}',
              loppupvm    = '{$query_row['loppupvm']}',
              alennuslaji = '{$query_row['alennuslaji']}',
              minkpl      = '{$query_row['minkpl']}',
              monikerta   = '{$query_row['monikerta']}',
              laatija     = 'konversio',
              luontiaika  = now()";
    mysql_query($query) or pupe_error($query);
  }

  // poistetaan ytunnus-tuoteno sidos tässä
  $query = "DELETE FROM asiakasalennus
            WHERE yhtio = '{$query_row['yhtio']}'
            AND tunnus  = '{$query_result['tunnus']}'";
  mysql_query($query) or pupe_error($query);
}

echo "\n\n\n\n\n\n";

// asiakashinnoille sama temppu
$query = "SELECT *
          FROM asiakashinta
          WHERE yhtio  = 'atarv'
          AND ytunnus != ''
          AND asiakas  = 0";
$query_result = mysql_query($query) or pupe_error($query);

$total_items = mysql_num_rows($query_result);
$current_item = 0;

while ($query_row = mysql_fetch_assoc($query_result)) {

  $current_item++;
  progress_bar($current_item, $total_items);

  $query = "SELECT tunnus
            FROM asiakas
            WHERE yhtio = 'atarv'
            AND ytunnus = '{$query_row['ytunnus']}'";
  $query_result2 = mysql_query($query) or pupe_error($query);

  while ($query_row2 = mysql_fetch_assoc($query_result2)) {
    $query = "INSERT INTO asiakashinta SET
              yhtio      = '{$query_row['yhtio']}',
              asiakas    = '{$query_row2['tunnus']}',
              tuoteno    = '{$query_row['tuoteno']}',
              ryhma      = '{$query_row['ryhma']}',
              hinta      = '{$query_row['hinta']}',
              alkupvm    = '{$query_row['alkupvm']}',
              loppupvm   = '{$query_row['loppupvm']}',
              laji       = '{$query_row['laji']}',
              minkpl     = '{$query_row['minkpl']}',
              maxkpl     = '{$query_row['maxkpl']}',
              valkoodi   = '{$query_row['valkoodi']}',
              laatija    = 'konversio',
              luontiaika = now()";
    mysql_query($query) or pupe_error($query);
  }

  // poistetaan ytunnus-tuoteno sidos tässä
  $query = "DELETE FROM asiakashinta
            WHERE yhtio = '{$query_row['yhtio']}'
            AND tunnus  = '{$query_result['tunnus']}'";
  mysql_query($query) or pupe_error($query);
}

echo "\n\n\n\n\n\n";
