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

ini_set("memory_limit", "1G");

// Otetaan includepath aina rootista
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(dirname(__FILE__))).PATH_SEPARATOR."/usr/share/pear");

require 'inc/connect.inc';
require 'inc/functions.inc';

// Yhtiö
$yhtio = mysql_real_escape_string($argv[1]);

// Tallannetan rivit tiedostoon
$filepath = "/tmp/input_balances_{$yhtio}_".date("Y-m-d").".csv";

if (!$fp = fopen($filepath, 'w+')) {
  die("Tiedoston avaus epäonnistui: $filepath\n");
}

// Otsikkotieto
$header = "location;product;quantity;type\n";
fwrite($fp, $header);

// Haetaan tuotteiden saldot per varasto
$query = "SELECT
          tuotepaikat.tuoteno tuote,
          varastopaikat.nimitys varasto,
          sum(tuotepaikat.saldo) saldo
          FROM tuote
          JOIN tuotepaikat ON (tuote.tuoteno = tuotepaikat.tuoteno and tuote.yhtio = tuotepaikat.yhtio)
          JOIN varastopaikat ON (varastopaikat.tunnus = tuotepaikat.varasto)
          WHERE tuote.yhtio     = '$yhtio'
          AND tuote.status     != 'P'
          AND tuote.ei_saldoa   = ''
          AND tuote.tuotetyyppi = ''
          AND tuote.ostoehdotus = ''
          GROUP BY 1,2
          ORDER BY 1,2
          LIMIT 1000";
$res = pupe_query($query);

// Kerrotaan montako riviä käsitellään
$rows = mysql_num_rows($res);

echo "Saldorivejä {$rows} kappaletta.\n";

$k_rivi = 0;

while ($row = mysql_fetch_assoc($res)) {
  $rivi = "{$row['varasto']};{$row['tuote']};{$row['saldo']};BALANCE\n";
  fwrite($fp, $rivi);

  $k_rivi++;

  if ($k_rivi % 1000 == 0) {
    echo "Käsitellään riviä {$k_rivi}\n";
  }
}

fclose($fp);

echo "Valmis.\n";
