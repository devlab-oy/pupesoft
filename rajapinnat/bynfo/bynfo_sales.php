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
// Otsikkotieto
$header  = "tuoteno;";
$header .= "nimitys;";
$header .= "tuoteosasto;";
$header .= "tuoteryhm‰;";
$header .= "tuotemerkki;";
$header .= "malli;";
$header .= "mallitarkenne;";
$header .= "kuvaus;";
$header .= "lyhytkuvaus;";
$header .= "mainosteksti;";
$header .= "aleryhm‰;";
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

// Haetaan tapahtumat
$query = "SELECT
          tilausrivi.laskutettuaika,
          tilausrivi.toimitettuaika,
          tilausrivi.tuoteno,
          tilausrivi.kpl,
          tilausrivi.rivihinta,
          tilausrivi.kate,

          lasku.valkoodi,
          lasku.maa,
          lasku.yhtio_toimipaikka,

          FROM tilausrivi
          JOIN lasku USE INDEX (PRIMARY) ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
          WHERE tilausrivi.yhtio = '$yhtio'
          AND tilausrivi.tyyppi  = 'L'
          AND tilausrivi.laskutettuaika >= ''
          ORDER BY tapahtuma.laadittu, tapahtuma.tuoteno";
$res = pupe_query($query);

// Kerrotaan montako rivi‰ k‰sitell‰‰n
$rows = mysql_num_rows($res);

echo "Tapahtumarivej‰ {$rows} kappaletta.\n";

$k_rivi = 0;

while ($row = mysql_fetch_assoc($res)) {

  // Rivin arvo
  $arvo = round($row['kplhinta']*$row['kpl'], 2);

  $rivi  = "{$row['pvm']};";
  $rivi .= "{$row['varasto']};";
  $rivi .= pupesoft_csvstring($row['tuoteno']).";";
  $rivi .= "{$type};";
  $rivi .= "{$row['kpl']};";
  $rivi .= "{$arvo};";
  $rivi .= ";";
  $rivi .= ";";
  $rivi .= ";";
  $rivi .= ";";
  $rivi .= "{$row['liitostunnus']}";
  $rivi .= "\n";

  fwrite($fp, $rivi);

  $k_rivi++;

  if ($k_rivi % 1000 == 0) {
    echo "K‰sitell‰‰n rivi‰ {$k_rivi}\n";
  }
}

fclose($fp);

echo "Valmis.\n";
