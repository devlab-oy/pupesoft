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

// Logitetaan ajo
cron_log();

$ajopaiva  = date("Y-m-d");
$paiva_ajo = FALSE;
$ftppath = "/data/input";

if (isset($argv[2]) and $argv[2] != '') {

  if (strpos($argv[2], "-") !== FALSE) {
    list($y, $m, $d) = explode("-", $argv[2]);
    if (is_numeric($y) and is_numeric($m) and is_numeric($d) and checkdate($m, $d, $y)) {
      $ajopaiva = $argv[2];
    }
  }
  $paiva_ajo = TRUE;
}

if (isset($argv[3]) and trim($argv[3]) != '') {
  $ftppath = trim($argv[3]);
}

// Yhtiö
$yhtio = mysql_real_escape_string($argv[1]);

$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow  = hae_kukarow('admin', $yhtiorow['yhtio']);

$tuoterajaus = rakenna_relex_tuote_parametrit();

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
          WHERE tuote.yhtio = '$yhtio'
          {$tuoterajaus}
          GROUP BY 1,2,3
          ORDER BY tuotepaikat.varasto, tuotepaikat.tuoteno";
$res = pupe_query($query);

// Kerrotaan montako riviä käsitellään
$rows = mysql_num_rows($res);

echo date("d.m.Y @ G:i:s") . ": Relex saldorivejä {$rows} kappaletta.\n";

$k_rivi = 0;

while ($row = mysql_fetch_assoc($res)) {
  // Haetaan hyllyssämäärä
  $query = "SELECT
            ifnull(sum(if(tilausrivi.keratty!='', tilausrivi.varattu, 0)), 0) keratty
            FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
            JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio AND lasku.tunnus = tilausrivi.otunnus)
            WHERE tilausrivi.yhtio = '$yhtio'
            and tilausrivi.tyyppi  in ('L','G','V')
            and (tilausrivi.perheid2 = 0 or tilausrivi.perheid2=tilausrivi.tunnus)
            and tilausrivi.tuoteno = '$row[tuoteno]'
            and (tilausrivi.varattu > 0 or (tilausrivi.varattu < 0 and lasku.tilaustyyppi = 'R'))
            and tilausrivi.varasto = '$row[varasto]'";
  $kerres = pupe_query($query);
  $kerrow = mysql_fetch_assoc($kerres);

  if (!empty($kerrow["keratty"])) {
    $row['saldo'] -= $kerrow["keratty"];
  }

  $rivi  = "{$row['maa']}-{$row['varasto']};";
  $rivi .= "{$row['maa']}-".pupesoft_csvstring($row['tuoteno']).";";
  $rivi .= pupesoft_csvstring($row['tuoteno']).";";
  $rivi .= "{$row['saldo']};";
  $rivi .= "BALANCE";
  $rivi .= "\n";

  fwrite($fp, $rivi);

  $k_rivi++;
}

fclose($fp);

// Tehdään FTP-siirto
if ($paiva_ajo and !empty($relex_ftphost)) {
  $ftphost = $relex_ftphost;
  $ftpuser = $relex_ftpuser;
  $ftppass = $relex_ftppass;
  $ftpfile = $filepath;
  require "inc/ftp-send.inc";
}

echo date("d.m.Y @ G:i:s") . ": Relex saldorivit valmis.\n\n";
