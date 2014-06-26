<?php

/*
 * Siirretään korvaavusketjut Relexiin
 * 4.4 PRODUCT REPLACEMENTS
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

$tecd = FALSE;

if (@include("inc/tecdoc.inc")) {
  $tecd = TRUE;
}


// Tallennetaan rivit tiedostoon
$filepath = "/tmp/product_replacement_update_{$yhtio}_".date("Y-m-d").".csv";

if (!$fp = fopen($filepath, 'w+')) {
  die("Tiedoston avaus epäonnistui: $filepath\n");
}

// Otsikkotieto
$header  = "new_product_code;";
$header .= "old_product_code;";
$header .= "new_clean_product_code;";
$header .= "old_clean_product_code;";
$header .= "state\n";
fwrite($fp, $header);

// Haetaan ketjut
$query = "SELECT DISTINCT yhtio.maa, korvaavat.id
          FROM tuote
          JOIN korvaavat ON (tuote.yhtio = korvaavat.yhtio AND tuote.tuoteno = korvaavat.tuoteno)
          JOIN yhtio ON (tuote.yhtio = yhtio.yhtio)
          WHERE tuote.yhtio     = '$yhtio'
          AND tuote.status     != 'P'
          AND tuote.ei_saldoa   = ''
          AND tuote.tuotetyyppi = ''
          AND tuote.ostoehdotus = ''";
$res = pupe_query($query);

// Kerrotaan montako riviä käsitellään
$rows = mysql_num_rows($res);

echo "Korvaavusketjuja {$rows} kappaletta.\n";

$k_rivi = 0;

while ($row = mysql_fetch_assoc($res)) {

  // Heataan ketjun tuotteet
  $kquery = "SELECT korvaavat.tuoteno
             FROM korvaavat
             JOIN tuote on (tuote.yhtio = korvaavat.yhtio and tuote.tuoteno = korvaavat.tuoteno)
             WHERE korvaavat.yhtio = '{$yhtio}'
             AND korvaavat.id      = '{$row['id']}'
             ORDER BY if(korvaavat.jarjestys=0, 9999, korvaavat.jarjestys), korvaavat.tuoteno";
  $kresult = pupe_query($kquery);

  $korvaavat = array();

  while ($krow = mysql_fetch_assoc($kresult)) {
    $korvaavat[] = $krow["tuoteno"];
  }

  // Käydään array läpi ja listataan tuotteet niin, että rivillä on aina uusi_tuote ja vanha_tuote
  for ($k = 0; $k < count($korvaavat)-1; $k++) {
    // Korvaavuudet
    $rivi  = "{$row['maa']}-".pupesoft_csvstring($korvaavat[$k]).";";
    $rivi .= "{$row['maa']}-".pupesoft_csvstring($korvaavat[$k+1]).";";
    $rivi .= pupesoft_csvstring($korvaavat[$k]).";";
    $rivi .= pupesoft_csvstring($korvaavat[$k+1]).";";
    $rivi .= "REPLACEMENT";
    $rivi .= "\n";
    fwrite($fp, $rivi);
  }

  $k_rivi++;

  if ($k_rivi % 1000 == 0) {
    echo "Käsitellään riviä {$k_rivi}\n";
  }
}

fclose($fp);

echo "Valmis.\n";
