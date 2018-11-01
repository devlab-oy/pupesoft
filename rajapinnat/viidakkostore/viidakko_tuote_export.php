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

require "rajapinnat/viidakkostore/viidakko_functions.php";
require "rajapinnat/viidakkostore/viidakko_saldot.php";
require "rajapinnat/viidakkostore/viidakko_tuotteet.php";
require "rajapinnat/viidakkostore/viidakko_kuvat.php";
require "rajapinnat/viidakkostore/viidakko_tilaukset.php";

if (empty($argv[1])) {
  die("ERROR! Aja näin:\nviidakko_tuote_export.php yhtiö [laji,laji,...] [ajentaanko_kaikki]\n");
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
    'tuotteet',
    #'saldot',
    'kuvat',
    #'tilaukset',
  );
}

// kolmas parametri ajetaanko kaikki
$ajetaanko_kaikki = (!empty($argv[3])) ? "YES" : "NO";

// Tässä kaikki parametrit, jota voi säätää salasanat.php:ssä
if (!isset($viidakko_url)) {
  // viidakkokaupan url
  die('ViidakkoStore url puuttuu');
}
if (!isset($viidakko_token)) {
  // viidakkon API salasana
  die('ViidakkoStore token puuttuu');
}

if (!isset($viidakko_edi_folderpath)) {
  die('ViidakkoStore editilausten kansio puuttuu');
}

if (!isset($viidakko_ovttunnus)) {
  die('ViidakkoStore ovttunnus puuttuu');
}

if (!isset($viidakko_rahtinimitys)) {
  die('ViidakkoStore rahdin nimitys puuttuu');
}

if (!isset($viidakko_rahtituoteno)) {
  die('ViidakkoStore rahtituotenumero puuttuu');
}

if (!isset($viidakko_tilaustyyppi)) {
  die('ViidakkoStore tilaustyyppi puuttuu');
}

if (!isset($viidakko_asiakasnro)) {
  die('ViidakkoStore asiakasnumero puuttuu');
}

if (!isset($viidakko_tilausstatus)) {
  die('ViidakkoStore tilaus status puuttuu');
}

if (!isset($viidakko_kuvaurl)) {
  $viidakko_kuvaurl = "";
}

if (!isset($viidakko_debug)) {
  // debug mode echottaa ruudulle ajon statusta
  $viidakko_debug = false;
}

viidakko_echo("Aloitetaan ViidakkoStore-päivitys.");

if (viidakko_ajetaanko_sykronointi('tuotteet', $synkronoi)) {
  $tyyppi = "viidakko_tuotteet";
  $kaikki_tuotteet = viidakko_hae_tuotteet();

  #echo "<pre>",var_dump($kaikki_tuotteet);

  viidakko_echo("Siirretään tuotteet.");
  $viidakko_products = new ViidakkoStoreTuotteet($viidakko_url, $viidakko_token, $tyyppi);

  $viidakko_products->set_all_products($kaikki_tuotteet);
  $viidakko_products->check_products();
}

if (viidakko_ajetaanko_sykronointi('saldot', $synkronoi)) {
  $tyyppi = "viidakko_saldot";
  $kaikki_tuotteet = viidakko_hae_tuotteet($tyyppi);

  viidakko_echo("Siirretään saldot.");
  $viidakko_stocks = new ViidakkoStoreSaldot($viidakko_url, $viidakko_token, $tyyppi);

  $viidakko_stocks->set_all_products($kaikki_tuotteet);
  $viidakko_stocks->update_stock();
}

if (viidakko_ajetaanko_sykronointi('kuvat', $synkronoi)) {
  $tyyppi = "viidakko_kuvat";
  $kaikki_tuotteet = viidakko_hae_tuotteet($tyyppi);

  #echo "<pre>",var_dump($kaikki_tuotteet);

  viidakko_echo("Siirretään kuvat.");
  $viidakko_pics = new ViidakkoStoreKuvat($viidakko_url, $viidakko_token, $tyyppi);

  $viidakko_pics->set_all_products($kaikki_tuotteet);
  $viidakko_pics->check_pics();
}

if (viidakko_ajetaanko_sykronointi('tilaukset', $synkronoi)) {
  viidakko_echo("Haetaan tilaukset.");
  $viidakko_orders = new ViidakkoStoreTilaukset($viidakko_url, $viidakko_username, $viidakko_api_key, 'viidakko_tilaukset');

  $viidakko_orders->set_edi_polku($viidakko_edi_folderpath);
  $viidakko_orders->set_ovt_tunnus($viidakko_ovttunnus);
  $viidakko_orders->set_rahtikulu_nimitys($viidakko_rahtinimitys);
  $viidakko_orders->set_rahtikulu_tuoteno($viidakko_rahtituoteno);
  $viidakko_orders->set_pupesoft_tilaustyyppi($viidakko_tilaustyyppi);
  $viidakko_orders->set_verkkokauppa_asiakasnro($viidakko_asiakasnro);
  $viidakko_orders->set_viidakko_maksuehto_ohjaus(array());
  $viidakko_orders->set_viidakko_erikoiskasittely(array());


  $viidakko_orders->fetch_orders();
}


viidakko_echo("ViidakkoStore-päivitys valmis.");
