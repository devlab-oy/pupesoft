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

function is_log($str) {
  echo date("d.m.Y @ G:i:s") . ": $str\n";
}


echo "\n";
is_log("Tuotepaikkasiivous");

// Haetaan yhtiörow ja kukarow
$yhtiorow = hae_yhtion_parametrit('atarv');
$kukarow = hae_kukarow('admin', $yhtiorow['yhtio']);

is_log("Haetaan avoimet tilausrivit");

// Haetaan avoimet tilausrivit
$query = "SELECT DISTINCT
          tuoteno,
          hyllyalue,
          hyllynro,
          hyllytaso,
          hyllyvali
          FROM tilausrivi
          WHERE yhtio        = '{$kukarow['yhtio']}'
          AND laskutettuaika = '0000-00-00'
          AND tyyppi         IN ('L','O','G')";
$avoinrivi_result = pupe_query($query);

$total_items = mysql_num_rows($avoinrivi_result);
$current_item = 0;

// Päivitetään niille poistettava = A
while ($avoinrivi = mysql_fetch_assoc($avoinrivi_result)) {

  $current_item++;
  progress_bar($current_item, $total_items);

  $query = "UPDATE tuotepaikat
            SET poistettava = 'A'
            WHERE yhtio      = '{$kukarow['yhtio']}'
            AND tuoteno      = '{$avoinrivi["tuoteno"]}'
            AND hyllyalue    = '{$avoinrivi["hyllyalue"]}'
            AND hyllynro     = '{$avoinrivi["hyllynro"]}'
            AND hyllyvali    = '{$avoinrivi["hyllyvali"]}'
            AND hyllytaso    = '{$avoinrivi["hyllytaso"]}'
            AND poistettava != 'A'";
  pupe_query($query);
}

echo "\n\n\n\n\n\n";

// Poistetaan varastopaikan ekat paikat, jos niiläl ei ole saldoa eikä avoimia rivejä
$poistettu = 0;
$skipattu = 0;

is_log("Haetaan poistettavat tuotepaikat");

$query = "SELECT tuotepaikat.tunnus,
          tuotepaikat.tuoteno,
          tuotepaikat.hyllyalue,
          tuotepaikat.hyllynro,
          tuotepaikat.hyllytaso,
          tuotepaikat.hyllyvali
          FROM tuotepaikat
          JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio
          AND varastopaikat.alkuhyllyalue  = tuotepaikat.hyllyalue
          AND varastopaikat.alkuhyllynro   = tuotepaikat.hyllynro)
          WHERE tuotepaikat.yhtio          = '{$kukarow['yhtio']}'
          AND tuotepaikat.saldo            = 0
          AND tuotepaikat.hyllytaso        = 0
          AND tuotepaikat.hyllyvali        = 0
          AND tuotepaikat.oletus           = ''
          AND tuotepaikat.poistettava     != 'A'";
$result = pupe_query($query);

$total_items = mysql_num_rows($result);
$current_item = 0;

is_log("Disabloidaan tuotepaikat keys");
pupe_query("alter table tuotepaikat disable keys");

is_log("Disabloidaan tapahtuma keys");
pupe_query("alter table tapahtuma disable keys");

// Loopataan poistettavat tuotepaikat läpi
while ($tuotepaikka = mysql_fetch_assoc($result)) {

  $current_item++;
  progress_bar($current_item, $total_items);

  // Poistetaan tuotepaikka
  $query = "DELETE FROM tuotepaikat
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus  = {$tuotepaikka['tunnus']}";
  pupe_query($query);

  $selite = "Poistettiin tuotepaikka";
  $selite .= " {$tuotepaikka["hyllyalue"]}";
  $selite .= " {$tuotepaikka["hyllynro"]}";
  $selite .= " {$tuotepaikka["hyllyvali"]}";
  $selite .= " {$tuotepaikka["hyllytaso"]}";

  // Luodaan tapahtuma
  $tapahtuma_query = "INSERT INTO tapahtuma SET
                      yhtio     = '$kukarow[yhtio]',
                      tuoteno   = '$tuotepaikka[tuoteno]',
                      kpl       = '0',
                      kplhinta  = '0',
                      hinta     = '0',
                      hyllyalue = '$tuotepaikka[hyllyalue]',
                      hyllynro  = '$tuotepaikka[hyllynro]',
                      hyllyvali = '$tuotepaikka[hyllyvali]',
                      hyllytaso = '$tuotepaikka[hyllytaso]',
                      laji      = 'poistettupaikka',
                      selite    = '$selite',
                      laatija   = '$kukarow[kuka]',
                      laadittu  = now()";
  pupe_query($tapahtuma_query);

  $poistettu++;
}

echo "\n\n\n\n\n\n";

is_log("Poistettiin $poistettu tuotepaikkaa, skipattiin $skipattu avointa");

is_log("Päivitetään tuotepaikoilta poistettava A pois");

// Loopataan avoimet uudestaan ja päivitetään paikoilta A pois
mysql_data_seek($avoinrivi_result, 0);
$total_items = mysql_num_rows($avoinrivi_result);
$current_item = 0;

// Päivitetään niille poistettava = A
while ($avoinrivi = mysql_fetch_assoc($avoinrivi_result)) {

  $current_item++;
  progress_bar($current_item, $total_items);

  $query = "UPDATE tuotepaikat
            SET poistettava = ''
            WHERE yhtio      = '{$kukarow['yhtio']}'
            AND tuoteno      = '{$avoinrivi["tuoteno"]}'
            AND hyllyalue    = '{$avoinrivi["hyllyalue"]}'
            AND hyllynro     = '{$avoinrivi["hyllynro"]}'
            AND hyllyvali    = '{$avoinrivi["hyllyvali"]}'
            AND hyllytaso    = '{$avoinrivi["hyllytaso"]}'
            AND poistettava != ''";
  pupe_query($query);
}

echo "\n\n\n\n\n\n";

is_log("Enabloidaan tuotepaikat keys");
pupe_query("alter table tuotepaikat enable keys");

is_log("Enabloidaan tapahtuma keys");
pupe_query("alter table tapahtuma enable keys");

is_log("Valmis");
