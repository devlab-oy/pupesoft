<?php

/*
 * Siirretään myynnit Relexiin
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

// Tallennetaan rivit tiedostoon
$filepath = "/tmp/sales_{$yhtio}_".date("Y-m-d").".csv";

if (!$fp = fopen($filepath, 'w+')) {
  die("Tiedoston avaus epäonnistui: $filepath\n");
}

// Otsikkotieto
// Otsikkotieto
$header  = "tuoteno;";
$header .= "nimitys;";
$header .= "tuoteosasto;";
$header .= "tuoteryhmä;";
$header .= "tuotemerkki;";
$header .= "malli;";
$header .= "mallitarkenne;";
$header .= "kuvaus;";
$header .= "lyhytkuvaus;";
$header .= "mainosteksti;";
$header .= "aleryhmä;";
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
          date_format(tapahtuma.laadittu, '%Y-%m-%d') pvm,
          tapahtuma.varasto,
          tapahtuma.tuoteno,
          tapahtuma.laji,
          tapahtuma.kpl,
          tapahtuma.kplhinta,
          lasku.tilaustyyppi,
          lasku.liitostunnus,
          lasku.yhtio_toimipaikka,
          tilausrivi.toimitettuaika
          FROM tapahtuma
          JOIN tuote ON (tuote.yhtio = tapahtuma.yhtio
            AND tuote.tuoteno     = tapahtuma.tuoteno
            AND tuote.status     != 'P'
            AND tuote.ei_saldoa   = ''
            AND tuote.tuotetyyppi = ''
            AND tuote.ostoehdotus = '')
          JOIN yhtio ON (tapahtuma.yhtio = yhtio.yhtio)
          LEFT JOIN tilausrivi USE INDEX (PRIMARY) ON (tilausrivi.yhtio = tapahtuma.yhtio and tilausrivi.tunnus = tapahtuma.rivitunnus)
          LEFT JOIN lasku USE INDEX (PRIMARY) ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
          WHERE tapahtuma.yhtio = '$yhtio'
          AND tapahtuma.laji    = 'laskutus'
          ORDER BY tapahtuma.laadittu, tapahtuma.tuoteno";
$res = pupe_query($query);

// Kerrotaan montako riviä käsitellään
$rows = mysql_num_rows($res);

echo "Tapahtumarivejä {$rows} kappaletta.\n";

$k_rivi = 0;

while ($row = mysql_fetch_assoc($res)) {

  // Rivin arvo
  $arvo = round($row['kplhinta']*$row['kpl'], 2);
  
  $rivi  = "{$row['pvm']};";
  $rivi .= "{$row['varasto']};";
  $rivi .= "{$row['maa']}-".pupesoft_csvstring($row['tuoteno']).";";
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
    echo "Käsitellään riviä {$k_rivi}\n";
  }
}

fclose($fp);

echo "Valmis.\n";
