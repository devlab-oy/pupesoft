<?php

/*
 * Siirretään varastosaldot Relexiin
 * 2.3 BALANCES
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
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(dirname(__FILE__))).PATH_SEPARATOR."/usr/share/pear");

require 'inc/connect.inc';
require 'inc/functions.inc';

$ajopaiva  = date("Y-m-d");
$paiva_ajo = FALSE;

if (isset($argv[2]) and $argv[2] != '') {
  $paiva_ajo = TRUE;

  if ($argv[2] == "edpaiva") {
      $ajopaiva = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
  }
}

// Logitetaan ajo
cron_log();

// Yhtiö
$yhtio = mysql_real_escape_string($argv[1]);

$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow  = hae_kukarow('admin', $yhtiorow['yhtio']);

// Tallennetaan rivit tiedostoon
$filepath = "/tmp/input_balances_{$yhtio}_$ajopaiva.csv";

if (!$fp = fopen($filepath, 'w+')) {
  die("Tiedoston avaus epäonnistui: $filepath\n");
}

// Otsikkotieto
$header = "location;product;clean_product;quantity;type\n";
fwrite($fp, $header);

// Haetaan tuotteiden saldot per varasto
$query = "SELECT
          yhtio.maa,
          tuotepaikat.tuoteno,
          tuotepaikat.varasto,
          sum(tuotepaikat.saldo) saldo
          FROM tuote
          JOIN tuotepaikat ON (tuote.tuoteno = tuotepaikat.tuoteno and tuote.yhtio = tuotepaikat.yhtio)
          JOIN varastopaikat ON (varastopaikat.tunnus = tuotepaikat.varasto and varastopaikat.yhtio = tuotepaikat.yhtio)
          JOIN yhtio ON (tuote.yhtio = yhtio.yhtio)
          WHERE tuote.yhtio      = '$yhtio'
          AND tuote.status      != 'P'
          AND tuote.ei_saldoa    = ''
          AND tuote.tuotetyyppi  = ''
          AND tuote.ostoehdotus  = ''
          GROUP BY 1,2,3
          ORDER BY tuotepaikat.varasto, tuotepaikat.tuoteno";
$res = pupe_query($query);

// Kerrotaan montako riviä käsitellään
$rows = mysql_num_rows($res);

echo "Saldorivejä {$rows} kappaletta.\n";

$k_rivi = 0;

while ($row = mysql_fetch_assoc($res)) {
  $rivi  = "{$row['maa']}-{$row['varasto']};";
  $rivi .= "{$row['maa']}-".pupesoft_csvstring($row['tuoteno']).";";
  $rivi .= pupesoft_csvstring($row['tuoteno']).";";
  $rivi .= "{$row['saldo']};";
  $rivi .= "BALANCE";
  $rivi .= "\n";

  fwrite($fp, $rivi);

  $k_rivi++;

  if ($k_rivi % 1000 == 0) {
    echo "Käsitellään riviä {$k_rivi}\n";
  }
}

fclose($fp);

// Tehdään FTP-siirto
if ($paiva_ajo and !empty($relex_ftphost)) {
  $ftphost = $relex_ftphost;
  $ftpuser = $relex_ftpuser;
  $ftppass = $relex_ftppass;
  $ftppath = "/data/input";
  $ftpfile = $filepath;
  require "inc/ftp-send.inc";
}

echo "Valmis.\n";
