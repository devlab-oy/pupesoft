<?php

/*
 * Siirret��n tuotemasterdata Bynfoon
*/

//* T�m� skripti k�ytt�� slave-tietokantapalvelinta *//
$useslave = 1;

// Kutsutaanko CLI:st�
if (php_sapi_name() != 'cli') {
  die ("T�t� scripti� voi ajaa vain komentorivilt�!");
}

if (!isset($argv[1]) or $argv[1] == '') {
  die("Yhti� on annettava!!");
}

ini_set("memory_limit", "5G");

// Otetaan includepath aina rootista
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(dirname(__FILE__))));

require 'inc/connect.inc';
require 'inc/functions.inc';

// Logitetaan ajo
cron_log();

// Yhti�
$yhtio = mysql_real_escape_string($argv[1]);

$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow  = hae_kukarow('admin', $yhtiorow['yhtio']);

// Tallennetaan tuoterivit tiedostoon
$filepath = "/tmp/products_{$yhtio}_".date("Y-m-d").".csv";

if (!$fp = fopen($filepath, 'w+')) {
  die("Tiedoston avaus ep�onnistui: $filepath\n");
}

// Otsikkotieto
$header  = "tuoteno;";
$header .= "nimitys;";
$header .= "tuoteosasto;";
$header .= "tuoteryhm�;";
$header .= "tuotemerkki;";
$header .= "malli;";
$header .= "mallitarkenne;";
$header .= "kuvaus;";
$header .= "lyhytkuvaus;";
$header .= "mainosteksti;";
$header .= "aleryhm�;";
$header .= "purkukommentti;";
$header .= "keskihankintahinta;";
$header .= "viimeisin_hankintahinta;";
$header .= "viimeisin_hankintapaiva;";
$header .= "yksikko;";
$header .= "tuotetyyppi;";
$header .= "hinnastoon;";
$header .= "sarjanumeroseuranta;";
$header .= "status;";
$header .= "luontiaika;";
$header .= "epakuranttipvm;";
$header .= "\n";

fwrite($fp, $header);

// Haetaan tuotteet
$query = "SELECT tuote.tuoteno,
          tuote.nimitys,
          tuote.osasto,
          tuote.try,
          tuote.tuotemerkki,
          tuote.malli,
          tuote.mallitarkenne,
          tuote.kuvaus,
          tuote.lyhytkuvaus,
          tuote.mainosteksti,
          tuote.aleryhma,
          tuote.purkukommentti,
          tuote.kehahin,
          tuote.vihahin,
          tuote.vihapvm,
          upper(tuote.yksikko) yksikko,
          tuote.tuotetyyppi,
          tuote.hinnastoon,
          tuote.sarjanumeroseuranta,
          tuote.status,
          left(tuote.luontiaika, 10) luontiaika,
          tuote.epakurantti25pvm,
          tuote.myyjanro,
          tuote.tuotepaallikko,
          tuote.tunnus
          FROM tuote
          WHERE tuote.yhtio          = '{$yhtio}'
          AND tuote.status          != 'P'
          AND tuote.ei_saldoa        = ''
          AND tuote.myynninseuranta  = ''
          ORDER BY tuote.tuoteno";
$res = pupe_query($query);

// Kerrotaan montako rivi� k�sitell��n
$rows = mysql_num_rows($res);

echo "Tuoterivej� {$rows} kappaletta.\n";

$k_rivi = 0;

while ($row = mysql_fetch_assoc($res)) {

  // Tuotetiedot
  $rivi  = pupesoft_csvstring($row['tuoteno']).";";
  $rivi .= pupesoft_csvstring($row['nimitys']).";";
  $rivi .= "{$row['osasto']};";
  $rivi .= "{$row['try']};";
  $rivi .= pupesoft_csvstring($row['tuotemerkki']).";";
  $rivi .= pupesoft_csvstring($row['malli']).";";
  $rivi .= pupesoft_csvstring($row['mallitarkenne']).";";
  $rivi .= pupesoft_csvstring($row['kuvaus']).";";
  $rivi .= pupesoft_csvstring($row['lyhytkuvaus']).";";
  $rivi .= pupesoft_csvstring($row['mainosteksti']).";";
  $rivi .= "{$row['aleryhma']};";
  $rivi .= pupesoft_csvstring($row['purkukommentti']).";";
  $rivi .= "{$row['kehahin']};";
  $rivi .= "{$row['vihahin']};";
  $rivi .= "{$row['vihapvm']};";
  $rivi .= "{$row['yksikko']};";
  $rivi .= "{$row['tuotetyyppi']};";
  $rivi .= "{$row['hinnastoon']};";
  $rivi .= "{$row['sarjanumeroseuranta']};";
  $rivi .= "{$row['status']};";
  $rivi .= "{$row['luontiaika']};";
  $rivi .= "{$row['epakurantti25pvm']}";
  $rivi .= "\n";

  fwrite($fp, $rivi);

  $k_rivi++;

  if ($k_rivi % 1000 == 0) {
    echo "K�sitell��n rivi� {$k_rivi}\n";
  }
}

fclose($fp);

echo "Valmis.\n";
