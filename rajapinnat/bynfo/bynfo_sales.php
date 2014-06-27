<?php

/*
 * Siirret‰‰n myynnit Relexiin
*/

//* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *//
$useslave = 1;

// Kutsutaanko CLI:st‰
if (php_sapi_name() != 'cli') {
  die ("T‰t‰ scripti‰ voi ajaa vain komentorivilt‰!");
}

if (!isset($argv[1]) or $argv[1] == '') {
  die("Yhtiˆ on annettava!!");
}

ini_set("memory_limit", "5G");

// Otetaan includepath aina rootista
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(dirname(__FILE__))).PATH_SEPARATOR."/usr/share/pear");

require 'inc/connect.inc';
require 'inc/functions.inc';

// Yhtiˆ
$yhtio = mysql_real_escape_string($argv[1]);

$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow  = hae_kukarow('admin', $yhtiorow['yhtio']);

// Tallennetaan rivit tiedostoon
$filepath = "/tmp/sales_{$yhtio}_".date("Y-m-d").".csv";

if (!$fp = fopen($filepath, 'w+')) {
  die("Tiedoston avaus ep‰onnistui: $filepath\n");
}

// Otsikkotieto

$header  = "laskunro;";
$header .= "asiakasid;";
$header .= "laskutuspvm;";
$header .= "toimituspvm;";
$header .= "tuoteno;";
$header .= "m‰‰r‰;";
$header .= "rivihinta;";
$header .= "rivikate";
$header .= "\n";
fwrite($fp, $header);

// Haetaan tapahtumat
$query = "SELECT
          lasku.laskunro,
          lasku.liitostunnus,
          tilausrivi.laskutettuaika,
          left(tilausrivi.toimitettuaika,10) toimitettuaika,
          tilausrivi.tuoteno,
          tilausrivi.kpl,
          tilausrivi.rivihinta,
          tilausrivi.kate
          FROM tilausrivi
          JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno AND tuote.myynninseuranta = '')
          JOIN lasku USE INDEX (PRIMARY) ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
          JOIN asiakas use index (PRIMARY) ON (asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus and asiakas.myynninseuranta = '')
          WHERE tilausrivi.yhtio = '$yhtio'
          AND tilausrivi.tyyppi  = 'L'
          AND tilausrivi.laskutettuaika >= '2012-01-01'
          ORDER BY lasku.laskunro, tilausrivi.tuoteno";
$res = pupe_query($query);

// Kerrotaan montako rivi‰ k‰sitell‰‰n
$rows = mysql_num_rows($res);

echo "Tapahtumarivej‰ {$rows} kappaletta.\n";

$k_rivi = 0;

while ($row = mysql_fetch_assoc($res)) {

  $rivi  = "{$row['laskunro']};";
  $rivi .= "{$row['liitostunnus']};";
  $rivi .= "{$row['laskutettuaika']};";
  $rivi .= "{$row['toimitettuaika']};";
  $rivi .= pupesoft_csvstring($row['tuoteno']).";";
  $rivi .= "{$row['kpl']};";
  $rivi .= "{$row['rivihinta']};";
  $rivi .= "{$row['kate']}";
  $rivi .= "\n";

  fwrite($fp, $rivi);

  $k_rivi++;

  if ($k_rivi % 1000 == 0) {
    echo "K‰sitell‰‰n rivi‰ {$k_rivi}\n";
  }
}

fclose($fp);

echo "Valmis.\n";
