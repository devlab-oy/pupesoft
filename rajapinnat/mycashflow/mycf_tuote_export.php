<?php

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
  die("Tätä scriptiä voi ajaa vain komentoriviltä!");
}

$pupe_root_polku = dirname(dirname(dirname(__FILE__)));

ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.$pupe_root_polku);
ini_set("display_errors", 1);
ini_set("max_execution_time", 0); // unlimited execution time
ini_set("memory_limit", "2G");
error_reporting(E_ALL);
date_default_timezone_set('Europe/Helsinki');

require "inc/connect.inc";
require "inc/functions.inc";
require_once 'rajapinnat/logger.php';

require "rajapinnat/mycashflow/mycf_functions.php";
require "rajapinnat/mycashflow/mycf_saldot.php";
require "rajapinnat/mycashflow/mycf_tilaukset.php";

if (empty($argv[1])) {
  die("ERROR! Aja näin:\nmycf_tuote_export.php yhtiö [laji,laji,...] [ajentaanko_kaikki]\n");
}

// ensimmäinen parametri yhtiö
$yhtio = mysql_real_escape_string($argv[1]);
$yhtiorow = hae_yhtion_parametrit($yhtio);

if (empty($yhtiorow)) {
  die("Yhtiö ei löydy.");
}

$kukarow = hae_kukarow('admin', $yhtio);

if (empty($kukarow)) {
  die("Admin -käyttäjä ei löydy.");
}

// toinen parametri ajettavat siirrot
if (!empty($argv[2])) {
  $synkronoi = explode(',', $argv[2]);
}
else {
  // ajetaan kaikki
  $synkronoi = array(
    'saldot',
  );
}

// kolmas parametri ajetaanko kaikki
$ajetaanko_kaikki = (!empty($argv[3])) ? "YES" : "NO";

// Tässä kaikki parametrit, jota voi säätää salasanat.php:ssä
if (!isset($mycf_url)) {
  // mycfkaupan url
  die('MyCashflow url puuttuu');
}
if (!isset($mycf_api_key)) {
  // mycfn API salasana
  die('MyCashflow api key puuttuu');
}
if (!isset($mycf_username)) {
  // mycfn API salasana
  die('MyCashflow käyttäjätunnus puuttuu');
}

if (!isset($mycf_edi_folderpath)) {
  die('MyCashflow editilausten kansio puuttuu');
}

if (!isset($mycf_ovttunnus)) {
  die('MyCashflow ovttunnus puuttuu');
}

if (!isset($mycf_rahtinimitys)) {
  die('MyCashflow rahdin nimitys puuttuu');
}

if (!isset($mycf_rahtituoteno)) {
  die('MyCashflow rahtituotenumero puuttuu');
}

if (!isset($mycf_tilaustyyppi)) {
  die('MyCashflow tilaustyyppi puuttuu');
}

if (!isset($mycf_asiakasnro)) {
  die('MyCashflow asiakasnumero puuttuu');
}

if (!isset($mycf_debug)) {
  // debug mode echottaa ruudulle ajon statusta
  $mycf_debug = false;
}

mycf_echo("Aloitetaan MyCashflow-päivitys.");

if (mycf_ajetaanko_sykronointi('saldot', $synkronoi)) {
  $kaikki_tuotteet = mycf_hae_paivitettavat_saldot();

  mycf_echo("Siirretään saldot.");
  $mycf_stocks = new MyCashflowSaldot($mycf_url, $mycf_username, $mycf_api_key, 'mycf_saldot');

  $mycf_stocks->set_all_products($kaikki_tuotteet);
  $mycf_stocks->update_stock();
}

if (mycf_ajetaanko_sykronointi('tilaukset', $synkronoi)) {
  mycf_echo("Haetaan tilaukset.");
  $mycf_orders = new MyCashflowTilaukset($mycf_url, $mycf_webhooks_key, 'mycf_tilaukset');

  $mycf_orders->set_edi_polku($mycf_edi_folderpath);
  $mycf_orders->set_ovt_tunnus($mycf_ovttunnus);
  $mycf_orders->set_rahtikulu_nimitys($mycf_rahtinimitys);
  $mycf_orders->set_rahtikulu_tuoteno($mycf_rahtituoteno);
  $mycf_orders->set_pupesoft_tilaustyyppi($mycf_tilaustyyppi);
  $mycf_orders->set_verkkokauppa_asiakasnro($mycf_asiakasnro);
  $mycf_orders->set_mycf_maksuehto_ohjaus(array());
  $mycf_orders->set_mycf_erikoiskasittely(array());


  $mycf_orders->fetch_orders();
}


mycf_echo("MyCashflow-päivitys valmis.");
