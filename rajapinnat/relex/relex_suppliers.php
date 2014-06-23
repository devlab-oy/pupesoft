<?php

/*
 * Siirretään toimittajat Relexiin
 * 5.2 SUPPLIER DATA
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

// Yhtiö
$yhtio = mysql_real_escape_string($argv[1]);

$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow  = hae_kukarow('admin', $yhtiorow['yhtio']);

// Tallannetan rivit tiedostoon
$filepath = "/tmp/supplier_update_{$yhtio}_".date("Y-m-d").".csv";

if (!$fp = fopen($filepath, 'w+')) {
  die("Tiedoston avaus epäonnistui: $filepath\n");
}

// Otsikkotieto
$header = "code;name;country\n";
fwrite($fp, $header);

// Haetaan toimittajat
$query = "SELECT
          yhtio.maa,
          toimi.tunnus,
          concat_ws(' ', toimi.nimi, toimi.nimitark) nimi,
          toimi.maa toimittajan_maa
          FROM toimi
          JOIN yhtio ON (toimi.yhtio = yhtio.yhtio)
          WHERE toimi.yhtio = '$yhtio'
          AND toimi.oletus_vienti in ('C','F','I')
          AND toimi.toimittajanro not in ('0','')
          AND toimi.tyyppi = ''
          ORDER BY toimi.tunnus";
$res = pupe_query($query);

// Kerrotaan montako riviä käsitellään
$rows = mysql_num_rows($res);

echo "Toimittajarivejä {$rows} kappaletta.\n";

$k_rivi = 0;

while ($row = mysql_fetch_assoc($res)) {

  $rivi  = "{$row['maa']}-{$row['tunnus']};";
  $rivi .= pupesoft_csvstring($row['nimi']).";";
  $rivi .= pupesoft_csvstring($row['toimittajan_maa']);
  $rivi .= "\n";

  fwrite($fp, $rivi);

  $k_rivi++;

  if ($k_rivi % 1000 == 0) {
    echo "Käsitellään riviä {$k_rivi}\n";
  }
}

fclose($fp);

echo "Valmis.\n";
