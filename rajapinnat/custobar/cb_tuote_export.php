<?php

// Kutsutaanko CLI:st�
if (php_sapi_name() != 'cli') {
  die("T�t� scripti� voi ajaa vain komentorivilt�!");
}

$pupe_root_polku = dirname(dirname(dirname(__FILE__)));

ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.$pupe_root_polku);
ini_set("display_errors", 1);
ini_set("max_execution_time", 0); // unlimited execution time
ini_set("memory_limit", "10G");
error_reporting(E_ALL);
date_default_timezone_set('Europe/Helsinki');

require "inc/connect.inc";
require "inc/functions.inc";
require_once 'rajapinnat/logger.php';

require "rajapinnat/custobar/cb_functions.php";
require "rajapinnat/custobar/cb_customers.php";
require "rajapinnat/custobar/cb_sales.php";
require "rajapinnat/custobar/cb_products.php";

if (empty($argv[1])) {
  die("ERROR! Aja n�in:\ncb_tuote_export.php yhti� [laji,laji,...] [ajentaanko_kaikki]\n");
}

// ensimm�inen parametri yhti�
$yhtio = mysql_real_escape_string($argv[1]);
$yhtiorow = hae_yhtion_parametrit($yhtio);

if (empty($yhtiorow)) {
  die("Yhti� ei l�ydy.");
}

$kukarow = hae_kukarow('admin', $yhtio);

if (empty($kukarow)) {
  die("Admin -k�ytt�j� ei l�ydy.");
}

// toinen parametri ajettavat siirrot
if (!empty($argv[2])) {
  $synkronoi = explode(',', $argv[2]);
}
else {
  // ajetaan kaikki
  $synkronoi = array(
    'customers',
    'sales',
    'products',
  );
}

// kolmas parametri ajetaanko kaikki
$ajetaanko_kaikki = (!empty($argv[3])) ? "YES" : "NO";

// T�ss� kaikki parametrit, jota voi s��t�� salasanat.php:ss�
if (!isset($cb_url)) {
  // cb-kaupan url
  die('Custobar url puuttuu');
}
if (!isset($cb_api_key)) {
  // cbn API salasana
  die('Custobar api key puuttuu');
}
if (!isset($cb_username)) {
  // cbn API salasana
  die('Custobar k�ytt�j�tunnus puuttuu');
}
if (!isset($cb_shortname)) {
  // cbn API salasana
  die('Custobar yrityksen lyhyt nimi puuttuu');
}
if (!isset($cb_picture_url)) {
  // cbn API salasana
  die('Custobar tuotteen kuvan url puuttuu');
}

if (!isset($cb_debug)) {
  // debug mode echottaa ruudulle ajon statusta
  $cb_debug = false;
}

cb_echo("Aloitetaan Custobar-p�ivitys.");

if (cb_ajetaanko_sykronointi('customers', $synkronoi)) {
  $asiakkaat = cb_hae_asiakkaat();

  cb_echo("Siirret��n asiakkaat.");
  $cb_customers = new CustobarCustomers($cb_url, $cb_username, $cb_api_key, 'cb_customers');

  $cb_customers->set_all_customers($asiakkaat);
  $cb_customers->update_customers();
}

if (cb_ajetaanko_sykronointi('sales', $synkronoi)) {
  $sales = cb_hae_myynnit();

  cb_echo("Siirret��n myynnit.");
  $cb_sales = new CustobarSales($cb_url, $cb_username, $cb_api_key, 'cb_sales');

  $cb_sales->set_all_sales($sales);
  $cb_sales->update_sales();
}

if (cb_ajetaanko_sykronointi('products', $synkronoi)) {
  $products = cb_hae_tuotteet();

  cb_echo("Siirret��n tuotteet.");
  $cb_products = new CustobarProducts($cb_url, $cb_username, $cb_api_key, 'cb_products');

  $cb_products->set_all_products($products);
  $cb_products->update_products();
}

cb_echo("Custobar-p�ivitys valmis.");
