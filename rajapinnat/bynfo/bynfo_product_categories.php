<?php

/*
 * Siirretään tuoteryhmätiedot Bynfoon
*/

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
  die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
}

if (!isset($argv[1]) or $argv[1] == '') {
  die("Yhtiö on annettava!!");
}

ini_set("memory_limit", "5G");

// Otetaan includepath aina rootista
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(dirname(__FILE__))));

require 'inc/connect.inc';
require 'inc/functions.inc';

// Logitetaan ajo
cron_log();

// Yhtiö
$yhtio = mysql_real_escape_string($argv[1]);

$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow  = hae_kukarow('admin', $yhtiorow['yhtio']);

// Tallennetaan rivit tiedostoon
$filepath = "/tmp/product_categories_{$yhtio}_".date("Y-m-d").".csv";

if (!$fp = fopen($filepath, 'w+')) {
  die("Tiedoston avaus epäonnistui: $filepath\n");
}

// Otsikkotieto
$header  = "ryhma;";
$header .= "kuvaus;";
$header .= "laji;";
$header .= "\n";

fwrite($fp, $header);

// Haetaan tuoteryhmät
$query = "SELECT avainsana.selite,
          avainsana.selitetark,
          if(avainsana.laji = 'try', 'tuoteryhma', 'tuoteosasto') AS laji
          FROM avainsana
          WHERE avainsana.yhtio = '{$yhtio}'
          AND avainsana.laji    IN ('try', 'osasto')";
$res = pupe_query($query);

// Kerrotaan montako riviä käsitellään
$rows = mysql_num_rows($res);

echo "Tuoteryhmärivejä {$rows} kappaletta.\n";

while ($row = mysql_fetch_assoc($res)) {
  $rivi  = "{$row['selite']};";
  $rivi .= pupesoft_csvstring($row['selitetark']).";";
  $rivi .= "{$row['laji']};";
  $rivi .= "\n";

  fwrite($fp, $rivi);
}

fclose($fp);

echo "Valmis.\n";
