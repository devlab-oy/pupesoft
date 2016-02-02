<?php

// Kutsutaanko CLI:stä
$php_cli = FALSE;

if (php_sapi_name() == 'cli') {
  $php_cli = TRUE;
}

date_default_timezone_set('Europe/Helsinki');

// Kutsutaanko CLI:stä
if (!$php_cli) {
  die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
}

$pupe_root_polku=dirname(dirname(dirname(__FILE__)));
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.$pupe_root_polku.PATH_SEPARATOR."/usr/share/pear");
error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
ini_set("display_errors", 0);

require "inc/connect.inc";
require "inc/functions.inc";

$lock_params = array(
  "locktime" => 5400
);

// Logitetaan ajo
cron_log();

// Sallitaan vain yksi instanssi tästä skriptistä kerrallaan
pupesoft_flock($lock_params);

require_once "{$pupe_root_polku}/rajapinnat/presta/presta_products.php";
require_once "{$pupe_root_polku}/rajapinnat/presta/presta_categories.php";
require_once "{$pupe_root_polku}/rajapinnat/presta/presta_customers.php";
require_once "{$pupe_root_polku}/rajapinnat/presta/presta_customer_groups.php";
require_once "{$pupe_root_polku}/rajapinnat/presta/presta_sales_orders.php";
require_once "{$pupe_root_polku}/rajapinnat/presta/presta_product_stocks.php";
require_once "{$pupe_root_polku}/rajapinnat/presta/presta_shops.php";
require_once "{$pupe_root_polku}/rajapinnat/presta/presta_specific_prices.php";
require_once "{$pupe_root_polku}/rajapinnat/presta/presta_addresses.php";
require_once "{$pupe_root_polku}/rajapinnat/presta/presta_functions.php";

// Laitetaan unlimited execution time
ini_set("max_execution_time", 0);

if (trim($argv[1]) != '') {
  $yhtio = mysql_real_escape_string($argv[1]);
  $yhtiorow = hae_yhtion_parametrit($yhtio);
  $kukarow = hae_kukarow('admin', $yhtio);

  if ($kukarow === null) {
    die ("\n");
  }
}
else {
  die ("ERROR! Aja näin:\npresta_tuote_export.php yhtiö [ajentaanko_kaikki] [laji,laji,...]\n");
}

$ajetaanko_kaikki = (isset($argv[2]) and trim($argv[2]) != '') ? "YES" : "NO";

if (isset($argv[3])) {
  $synkronoi = explode(',', $argv[3]);
  $synkronoi = array_flip($synkronoi);
}
elseif (isset($synkronoi_prestaan) and count($synkronoi_prestaan) > 0) {
  $synkronoi = $synkronoi_prestaan;
}
else {
  $synkronoi = array(
    'kategoriat'    => t('Kategoriat'),
    'tuotteet'      => t('Tuotteet ja tuotekuvat'),
    'asiakasryhmat' => t('Asiakasryhmät'),
    'asiakkaat'     => t('Asiakkaat'),
    'asiakashinnat' => t('Asiakashinnat'),
    'tilaukset'     => t('Tilauksien haku'),
  );
}

if (!isset($verkkokauppa_saldo_varasto)) $verkkokauppa_saldo_varasto = array();

if (!is_array($verkkokauppa_saldo_varasto)) {
  echo "verkkokauppa_saldo_varasto pitää olla array!";
  exit;
}

if (!isset($presta_url)) {
  die('Presta url puuttuu');
}
if (!isset($presta_api_key)) {
  die('Presta api key puuttuu');
}
if (!isset($presta_edi_folderpath)) {
  die('Presta edi folder path puuttuu');
}
if (!isset($yhtiorow)) {
  die('Yhtiorow puuttuu');
}
if (!isset($presta_home_category_id)) {
  $presta_home_category_id = 2;
}
if (!isset($presta_verkkokauppa_asiakas)) {
  // verkkokauppa_asiakas on fallback, mikäli oikeaa asiakasta ei löydetä pupesoftista
  $presta_verkkokauppa_asiakas = null;
}
if (!isset($presta_haettavat_tilaus_statukset)) {
  /* Tämä on Prestan default status list;
   * 1  Awaiting cheque payment
   * 2  Payment accepted
   * 3  Preparation in progress
   * 4  Shipped
   * 5  Delivered
   * 6  Canceled
   * 7  Refund
   * 8  Payment error
   * 9  On backorder
   * 10 Awaiting bank wire payment
   * 11 Awaiting PayPal payment
   * 12 Remote payment accepted
  */
  $presta_haettavat_tilaus_statukset = array(2);
}
if (!isset($presta_haettu_tilaus_status)) {
  $presta_haettu_tilaus_status = 3;
}

// Haetaan timestamp
$datetime_checkpoint_res = t_avainsana("TUOTE_EXP_CRON");

if (mysql_num_rows($datetime_checkpoint_res) != 1) {
  exit("VIRHE: Timestamp ei löydy avainsanoista!\n");
}

$datetime_checkpoint_row = mysql_fetch_assoc($datetime_checkpoint_res);
$datetime_checkpoint = $datetime_checkpoint_row['selite']; // Mikä tilanne on jo käsitelty
$datetime_checkpoint_uusi = date('Y-m-d H:i:s'); // Timestamp nyt

echo date("d.m.Y @ G:i:s")." - Aloitetaan tuote-export.\n";

if (array_key_exists('kategoriat', $synkronoi)) {
  echo date("d.m.Y @ G:i:s")." - Haetaan tuotekategoriat.\n";
  $kategoriat = hae_kategoriat();

  echo date("d.m.Y @ G:i:s")." - Siirretään tuotekategoriat.\n";
  $presta_categories = new PrestaCategories($presta_url, $presta_api_key, $presta_home_category_id);
  $ok = $presta_categories->sync_categories($kategoriat);
}

if (array_key_exists('tuotteet', $synkronoi)) {
  echo date("d.m.Y @ G:i:s")." - Haetaan tuotetiedot.\n";
  $tuotteet = hae_tuotteet();

  echo date("d.m.Y @ G:i:s")." - Siirretään tuotetiedot.\n";
  $presta_products = new PrestaProducts($presta_url, $presta_api_key, $presta_home_category_id);

  if (isset($presta_dynaamiset_tuoteparametrit) and count($presta_dynaamiset_tuoteparametrit) > 0) {
    $presta_products->set_dynamic_fields($presta_dynaamiset_tuoteparametrit);
  }

  if (isset($presta_ohita_tuoteparametrit) and count($presta_ohita_tuoteparametrit) > 0) {
    $presta_products->set_removable_fields($presta_ohita_tuoteparametrit);
  }

  if (isset($presta_ohita_kategoriat) and !empty($presta_ohita_kategoriat)) {
    $presta_products->set_category_sync($presta_ohita_kategoriat);
  }

  $ok = $presta_products->sync_products($tuotteet);
}

if (array_key_exists('asiakasryhmat', $synkronoi)) {
  echo date("d.m.Y @ G:i:s")." - Haetaan asiakasryhmät.\n";
  $groups = hae_asiakasryhmat();

  echo date("d.m.Y @ G:i:s")." - Siirretään asiakasryhmät.\n";
  $presta_customer_groups = new PrestaCustomerGroups($presta_url, $presta_api_key);
  $ok = $presta_customer_groups->sync_groups($groups);
}

if (array_key_exists('asiakkaat', $synkronoi)) {
  echo date("d.m.Y @ G:i:s")." - Haetaan asiakkaat.\n";
  $asiakkaat = presta_hae_asiakkaat();

  echo date("d.m.Y @ G:i:s")." - Siirretään asiakkaat.\n";
  $presta_customer = new PrestaCustomers($presta_url, $presta_api_key);
  $ok = $presta_customer->sync_customers($asiakkaat);
}

if (array_key_exists('asiakashinnat', $synkronoi)) {
  echo date("d.m.Y @ G:i:s")." - Haetaan asiakashinnat ja alennukset.\n";
  $hinnat = presta_specific_prices();

  echo date("d.m.Y @ G:i:s")." - Siirretään asiakashinnat ja alennukset.\n";
  $presta_prices = new PrestaSpecificPrices($presta_url, $presta_api_key);
  $presta_prices->sync_prices($hinnat);
}

if (array_key_exists('tilaukset', $synkronoi)) {
  echo date("d.m.Y @ G:i:s")." - Siirretään tilaukset.\n";

  $presta_orders = new PrestaSalesOrders($presta_url, $presta_api_key);
  $presta_orders->set_edi_filepath($presta_edi_folderpath);
  $presta_orders->set_yhtiorow($yhtiorow);
  $presta_orders->set_verkkokauppa_customer($presta_verkkokauppa_asiakas);
  $presta_orders->set_fetch_statuses($presta_haettavat_tilaus_statukset);
  $presta_orders->set_fetched_status($presta_haettu_tilaus_status);
  $presta_orders->transfer_orders_to_pupesoft();
}

// Otetaan tietokantayhteys uudestaan (voi olla timeoutannu)
unset($link);
$link = mysql_connect($dbhost, $dbuser, $dbpass, true) or die ("Ongelma tietokantapalvelimessa $dbhost (tuote_export)");

mysql_select_db($dbkanta, $link) or die ("Tietokantaa $dbkanta ei löydy palvelimelta $dbhost! (tuote_export)");
mysql_set_charset("latin1", $link);
mysql_query("set group_concat_max_len=1000000", $link);

// Kun kaikki onnistui, päivitetään lopuksi timestamppi talteen
$query = "UPDATE avainsana SET
          selite      = '{$datetime_checkpoint_uusi}'
          WHERE yhtio = '{$kukarow['yhtio']}'
          AND laji    = 'TUOTE_EXP_CRON'";
pupe_query($query);

if (mysql_affected_rows() != 1) {
  echo date("d.m.Y @ G:i:s")." - Timestamp päivitys epäonnistui!\n";
}

echo date("d.m.Y @ G:i:s")." - Tuote-export valmis.\n";
