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

$query = "SELECT a.ytunnus, min(a.luottoraja) luottoraja, group_concat(a.tunnus) tunnukset
          FROM asiakas a
          WHERE a.yhtio  = 'atarv'
          AND a.laji    != 'P'
          #AND a.ytunnus = 'FI14630134'
          GROUP BY 1";
$query_result = mysql_query($query) or pupe_error($query);
/*
echo $query;
echo "\n";
*/
while ($query_row = mysql_fetch_assoc($query_result)) {

  // koitetaan kääntää atarv kustannuspaikka artr kustannuspaikaksi
  $query = "SELECT a.ytunnus, min(a.luottoraja) luottoraja, group_concat(a.tunnus) tunnukset
            FROM asiakas a
            WHERE a.yhtio = 'artr'
            AND a.ytunnus = '{$query_row['ytunnus']}'
            GROUP BY 1";
  $query_result2 = mysql_query($query) or pupe_error($query);
  if (mysql_num_rows($query_result2) == 1) {
    $query_row2 = mysql_fetch_assoc($query_result2);

    // samalla ytunnuksella on asiakkaita jo artr:ssä, päivitetään yhteenlaskettu luottoraja molempiin yhtiöihin
    $luottoraja = $query_row['luottoraja'] + $query_row2['luottoraja'];
    $asiakastunnukset = $query_row['tunnukset'] . "," . $query_row2['tunnukset'];
    /*
    echo $query;
    echo "\n";
    echo "uusi luottoraja: $luottoraja ({$query_row['luottoraja']} + {$query_row2['luottoraja']})";
    echo "\n";
    die;
    break;
    */
    $query = "UPDATE asiakas SET
              luottoraja   = '$luottoraja'
              WHERE tunnus in ($asiakastunnukset)";
    $asiakasres = mysql_query($query) or pupe_error($query);
  }
}
