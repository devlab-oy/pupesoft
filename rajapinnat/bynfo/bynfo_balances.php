<?php

/*
 * Siirretään saldot Bynfoon
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

$scp_siirto = "";

if (!empty($argv[2])) {
  $scp_siirto = $argv[2];
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
$filepath = "/tmp/balances_{$yhtio}_".date("Y-m-d").".csv";

if (!$fp = fopen($filepath, 'w+')) {
  die("Tiedoston avaus epäonnistui: $filepath\n");
}

// Otsikkotieto
$header  = "tuoteno;";
$header .= "eankoodi;";
$header .= "varasto;";
$header .= "varastopaikka;";
$header .= "saldo;";
$header .= "kehahin";
$header .= "\n";

fwrite($fp, $header);

// Haetaan tuotteiden saldot per varasto
$query = "SELECT
          tuote.tuoteno,
          tuote.eankoodi,
          round(if (tuote.epakurantti100pvm = '0000-00-00',
                  if (tuote.epakurantti75pvm = '0000-00-00',
                    if (tuote.epakurantti50pvm = '0000-00-00',
                      if (tuote.epakurantti25pvm = '0000-00-00',
                        tuote.kehahin,
                      tuote.kehahin * 0.75),
                    tuote.kehahin * 0.5),
                  tuote.kehahin * 0.25),
                0),
              6) kehahin,
          varastopaikat.nimitys,
          concat_ws('-', tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso) hyllypaikka,
          sum(tuotepaikat.saldo) saldo
          FROM tuote
          JOIN tuotepaikat ON (tuote.tuoteno = tuotepaikat.tuoteno and tuote.yhtio = tuotepaikat.yhtio)
          JOIN varastopaikat ON (varastopaikat.tunnus = tuotepaikat.varasto and varastopaikat.yhtio = tuotepaikat.yhtio)
          JOIN yhtio ON (tuote.yhtio = yhtio.yhtio)
          WHERE tuote.yhtio = '$yhtio'
          GROUP BY 1,2,3,4,5
          ORDER BY tuotepaikat.varasto, tuotepaikat.tuoteno";
$res = pupe_query($query);

// Kerrotaan montako riviä käsitellään
$rows = mysql_num_rows($res);

echo date("d.m.Y @ G:i:s") . ": Bynfo saldorivejä {$rows} kappaletta.\n";

$k_rivi = 0;

while ($row = mysql_fetch_assoc($res)) {
  $rivi  = "{$row['tuoteno']};";
  $rivi .= "{$row['eankoodi']};";
  $rivi .= pupesoft_csvstring($row['nimitys']).";";
  $rivi .= pupesoft_csvstring($row['hyllypaikka']).";";
  $rivi .= "{$row['saldo']};";
  $rivi .= "{$row['kehahin']}";
  $rivi .= "\n";

  fwrite($fp, $rivi);

  $k_rivi++;

  if ($k_rivi % 1000 == 0) {
    echo "Käsitellään riviä {$k_rivi}\n";
  }
}

fclose($fp);

if (!empty($scp_siirto)) {
  // Pakataan tiedosto
  system("zip {$filepath}.zip $filepath");

  // Siirretään toiselle palvelimelle
  system("scp {$filepath}.zip $scp_siirto");
}

echo "Valmis.\n";
