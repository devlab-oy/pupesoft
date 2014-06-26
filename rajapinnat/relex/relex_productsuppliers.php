<?php

/*
 * Siirretään tuotteiden toimittajat Relexiin
 * X.X PRODUCT SUPPLIER DATA
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
$filepath = "/tmp/product_suppliers_update_{$yhtio}_".date("Y-m-d").".csv";

if (!$fp = fopen($filepath, 'w+')) {
  die("Tiedoston avaus epäonnistui: $filepath\n");
}

// Otsikkotieto
$header  = "code;";
$header .= "clean_code;";
$header .= "supplier;";
$header .= "suppliers_code;";
$header .= "suppliers_name;";
$header .= "ostoera;";
$header .= "pakkauskoko;";
$header .= "purchase_price;";
$header .= "alennus;";
$header .= "valuutta;";
$header .= "suppliers_unit;";
$header .= "tuotekerroin;";
$header .= "jarjestys\n";
fwrite($fp, $header);

// Haetaan tuotteet
$query = "SELECT
          yhtio.maa,
          tuote.tuoteno,
          tuote.nimitys,
          toimi.tunnus toimittaja,
          tuotteen_toimittajat.toim_tuoteno,
          tuotteen_toimittajat.toim_nimitys,
          if(tuotteen_toimittajat.osto_era = 0, 1, tuotteen_toimittajat.osto_era) osto_era,
          if(tuotteen_toimittajat.pakkauskoko = 0, '', tuotteen_toimittajat.pakkauskoko) pakkauskoko,
          tuotteen_toimittajat.ostohinta,
          tuotteen_toimittajat.alennus,
          toimi.oletus_valkoodi valuutta,
          tuotteen_toimittajat.toim_yksikko,
          if(tuotteen_toimittajat.tuotekerroin = 0, 1, tuotteen_toimittajat.tuotekerroin) tuotekerroin,
          if(tuotteen_toimittajat.jarjestys = 0, 9999, tuotteen_toimittajat.jarjestys) jarjestys
          FROM tuote
          JOIN tuotteen_toimittajat ON (tuote.yhtio = tuotteen_toimittajat.yhtio
            AND tuote.tuoteno = tuotteen_toimittajat.tuoteno)
          JOIN toimi ON (tuotteen_toimittajat.yhtio = toimi.yhtio
            AND tuotteen_toimittajat.liitostunnus = toimi.tunnus
            AND toimi.oletus_vienti in ('C','F','I')
            AND toimi.toimittajanro not in ('0','')
            AND toimi.tyyppi = '')
          JOIN yhtio ON (tuote.yhtio = yhtio.yhtio)
          WHERE tuote.yhtio     = '$yhtio'
          AND tuote.status     != 'P'
          AND tuote.ei_saldoa   = ''
          AND tuote.tuotetyyppi = ''
          AND tuote.ostoehdotus = ''
          ORDER BY tuote.tuoteno, jarjestys";
$res = pupe_query($query);

// Kerrotaan montako riviä käsitellään
$rows = mysql_num_rows($res);

echo "Tuotteen toimittajarivejä {$rows} kappaletta.\n";

$k_rivi = 0;

while ($row = mysql_fetch_assoc($res)) {
  // Tuotetiedot
  $rivi  = $row['maa']."-".pupesoft_csvstring($row['tuoteno']).";";
  $rivi .= pupesoft_csvstring($row['tuoteno']).";";
  $rivi .= "{$row['maa']}-{$row['toimittaja']};";
  $rivi .= pupesoft_csvstring($row['toim_tuoteno']).";";
  $rivi .= pupesoft_csvstring($row['toim_nimitys']).";";
  $rivi .= "{$row['osto_era']};";
  $rivi .= "{$row['pakkauskoko']};";
  $rivi .= "{$row['ostohinta']};";
  $rivi .= "{$row['alennus']};";
  $rivi .= "{$row['valuutta']};";
  $rivi .= "{$row['toim_yksikko']};";
  $rivi .= "{$row['tuotekerroin']};";
  $rivi .= "{$row['jarjestys']};";
  $rivi .= "\n";

  fwrite($fp, $rivi);

  $k_rivi++;

  if ($k_rivi % 1000 == 0) {
    echo "Käsitellään riviä {$k_rivi}\n";
  }
}

fclose($fp);

echo "Valmis.\n";
