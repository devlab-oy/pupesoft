<?php

// Kutsutaanko CLI:st‰
$php_cli = FALSE;

if (php_sapi_name() == 'cli') {
  $php_cli = TRUE;
}

date_default_timezone_set('Europe/Helsinki');

// Kutsutaanko CLI:st‰
if (!$php_cli) {
  die ("T‰t‰ scripti‰ voi ajaa vain komentorivilt‰!");
}

$pupe_root_polku=dirname(dirname(dirname(__FILE__)));
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.$pupe_root_polku.PATH_SEPARATOR."/usr/share/pear");
error_reporting(E_ALL);
ini_set("display_errors", 1);

require "inc/connect.inc";
require "inc/functions.inc";

$lock_params = array(
  "locktime" => 5400
);

// Logitetaan ajo
cron_log();

// Sallitaan vain yksi instanssi t‰st‰ skriptist‰ kerrallaan
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

// Laitetaan isompi allowed memory size
ini_set("memory_limit", "2G");

if (trim($argv[1]) != '') {
  $yhtio = mysql_real_escape_string($argv[1]);
  $yhtiorow = hae_yhtion_parametrit($yhtio);
  $kukarow = hae_kukarow('admin', $yhtio);

  if ($kukarow === null) {
    die ("\n");
  }

  if (!isset($yhtiorow)) {
    die('Yhtiorow puuttuu');
  }
}
else {
  die ("ERROR! Aja n‰in:\npresta_tuote_export.php yhtiˆ [ajentaanko_kaikki] [laji,laji,...]\n");
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
    'asiakasryhmat' => t('Asiakasryhm‰t'),
    'asiakkaat'     => t('Asiakkaat'),
    'asiakashinnat' => t('Asiakashinnat'),
    'tilaukset'     => t('Tilauksien haku'),
  );
}

// T‰ss‰ kaikki parametrit, jota voi s‰‰t‰‰ salasanat.php:ss‰
if (!isset($presta_url)) {
  // Prestakaupan url
  die('Presta url puuttuu');
}
if (!isset($presta_api_key)) {
  // Prestan API salasana
  die('Presta api key puuttuu');
}
if (!isset($presta_edi_folderpath)) {
  // Mihin hakemistoon tehd‰‰n Prestan tilauksista EDI tiedosto
  die('Presta edi folder path puuttuu');
}
if (!isset($presta_home_category_id)) {
  // Prestan "home" kategorian tunnus, jonka alle kaikki Pupesoftin kategoriat siirret‰‰n
  $presta_home_category_id = 2;
}
if (!isset($presta_verkkokauppa_asiakas)) {
  // verkkokauppa_asiakas on fallback, mik‰li oikeaa asiakasta ei lˆydet‰ pupesoftista
  $presta_verkkokauppa_asiakas = null;
}
if (!isset($presta_haettavat_tilaus_statukset)) {
  // Miss‰ tilassa olevia tilauksia haetaan Prestasta
  /* T‰m‰ on Prestan default status list;
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
  // Mihin tilaan haettu tilaus asetataan Prestassa
  $presta_haettu_tilaus_status = 3;
}
if (!isset($presta_dynaamiset_tuoteparametrit)) {
  // Jos halutaan poikkeavan kent‰n arvo prestan tuotteelle
  // nimi = prestan tuotteen kent‰n nimi, arvo = tuotesiirto arrayn kent‰n nimi
  $presta_dynaamiset_tuoteparametrit = array(
    // array(
    //   'nimi' => 'price',
    //   'arvo' => 'myymalahinta_verot_pois'
    // ),
    // array(
    //   'nimi' => 'wholesale_price',
    //   'arvo' => 'myymalahinta_verot_pois'
    // ),
  );
}
if (!isset($presta_ohita_tuoteparametrit)) {
  // Lista Prestan tuotteen kentist‰, joita ei tule p‰ivitt‰‰ rajapinnassa
  $presta_ohita_tuoteparametrit = array();
}
if (!isset($presta_synkronoi_tuotepuu)) {
  // Siirret‰‰nko Pupesoftin kategoriat. Aseta false, niin ei siirret‰.
  $presta_synkronoi_tuotepuu = true;
}
if (!isset($presta_verokannat)) {
  $presta_verokannat = array(
    // Pupen verokanta decimal => Prestan tax_group_id integer
    // 24 => 1,
    // 14 => 2,
    // 10 => 3,
  );
}
if (!isset($presta_kieliversiot)) {
  $presta_kieliversiot = array(
    // Pupen kieli => Prestan language_id
    // "fi" => 2,
    // "se" => 3,
    // "en" => 1,
  );
}
if (!isset($presta_valuuttakoodit)) {
  $presta_valuuttakoodit = array(
    // Pupen valkoodi => Prestan currency_id
    // "USD" => 1,
    // "EUR" => 2,
  );
}
if (!isset($presta_tuotekasittely)) {
  // Miten valitaan tuotteet, jotka siirret‰‰n Prestaan
  // 1 = siirret‰‰n tuotteet joiden status != 'P' ja nakyvyys != ''
  // 2 = siirret‰‰n kaikki pupen tuotteet prestaan (poistetutkin), mutta merkataan ne hiddeniksi
  $presta_tuotekasittely = 1;
}
if (!isset($presta_tuoteominaisuudet)) {
  // Mik‰ tuotearrayn kentt‰ laitetaan mihinkin Prestan "Product Feature":een.
  $presta_tuoteominaisuudet = array(
    // Tuote-arrayn kentt‰ => Prestan product_feature_id
    // "tuotemerkki" => 6,
    // "t‰htituote"  => 10,
  );
}
if (!isset($presta_vakioasiakasryhmat)) {
  $presta_vakioasiakasryhmat = array(
    // Lis‰t‰‰n asiakas Prestassa aina t‰h‰n ryhm‰‰n
    // Prestan customer_group_id
    // 3,
    // 6,
  );
}
if (!isset($presta_asiakasryhmien_hinta)) {
  // 1 = N‰ytet‰‰n hinta, 0 = Ei n‰ytet‰ hintoja
  $presta_asiakasryhmien_hinta = 1;
}
if (!isset($presta_asiakasryhmien_hinnat)) {
  // 0 = Verolliset hinnat, 1 = Verottomat hinnat
  $presta_asiakasryhmien_hinnat = 0;
}
if (!isset($presta_varastot)) {
  // Pupesoftin varastojen tunnukset, joista lasketaan Prestaan saldot. Nolla on kaikki varastot.
  $presta_varastot = array(0);
}

// Haetaan timestamp
$datetime_checkpoint_res = t_avainsana("TUOTE_EXP_CRON");

if (mysql_num_rows($datetime_checkpoint_res) != 1) {
  exit("VIRHE: Timestamp ei lˆydy avainsanoista!\n");
}

$datetime_checkpoint_row = mysql_fetch_assoc($datetime_checkpoint_res);
$datetime_checkpoint = $datetime_checkpoint_row['selite']; // Mik‰ tilanne on jo k‰sitelty
$datetime_checkpoint_uusi = date('Y-m-d H:i:s'); // Timestamp nyt

echo date("d.m.Y @ G:i:s")." - Aloitetaan Prestashop p‰ivitys.\n";

if (array_key_exists('kategoriat', $synkronoi)) {
  echo date("d.m.Y @ G:i:s")." - Haetaan tuotekategoriat.\n";
  $kategoriat = presta_hae_kategoriat();

  echo date("d.m.Y @ G:i:s")." - Siirret‰‰n tuotekategoriat.\n";
  $presta_categories = new PrestaCategories($presta_url, $presta_api_key);
  $presta_categories->set_category_sync($presta_synkronoi_tuotepuu);
  $presta_categories->set_home_category_id($presta_home_category_id);
  $presta_categories->set_languages_table($presta_kieliversiot);
  $presta_categories->sync_categories($kategoriat);
}

if (array_key_exists('tuotteet', $synkronoi)) {
  echo date("d.m.Y @ G:i:s")." - Haetaan tuotetiedot.\n";
  $tuotteet = presta_hae_tuotteet();
  $kaikki_tuotteet = presta_hae_kaikki_tuotteet();

  echo date("d.m.Y @ G:i:s")." - Siirret‰‰n tuotetiedot.\n";
  $presta_products = new PrestaProducts($presta_url, $presta_api_key, $presta_home_category_id);

  $presta_products->set_all_products($kaikki_tuotteet);
  $presta_products->set_category_sync($presta_synkronoi_tuotepuu);
  $presta_products->set_dynamic_fields($presta_dynaamiset_tuoteparametrit);
  $presta_products->set_languages_table($presta_kieliversiot);
  $presta_products->set_product_features($presta_tuoteominaisuudet);
  $presta_products->set_removable_fields($presta_ohita_tuoteparametrit);
  $presta_products->set_tax_rates_table($presta_verokannat);
  $presta_products->set_visibility_type($presta_tuotekasittely);

  $presta_products->sync_products($tuotteet);
}

if (array_key_exists('asiakasryhmat', $synkronoi)) {
  echo date("d.m.Y @ G:i:s")." - Haetaan asiakasryhm‰t.\n";
  $groups = presta_hae_asiakasryhmat();

  echo date("d.m.Y @ G:i:s")." - Siirret‰‰n asiakasryhm‰t.\n";
  $presta_customer_groups = new PrestaCustomerGroups($presta_url, $presta_api_key);
  $presta_customer_groups->set_show_prices($presta_asiakasryhmien_hinta);
  $presta_customer_groups->set_price_display_method($presta_asiakasryhmien_hinnat);
  $presta_customer_groups->sync_groups($groups);
}

if (array_key_exists('asiakkaat', $synkronoi)) {
  echo date("d.m.Y @ G:i:s")." - Haetaan asiakkaat.\n";
  $asiakkaat = presta_hae_asiakkaat();

  echo date("d.m.Y @ G:i:s")." - Siirret‰‰n asiakkaat.\n";
  $presta_customer = new PrestaCustomers($presta_url, $presta_api_key);
  $presta_customer->set_default_groups($presta_vakioasiakasryhmat);
  $presta_customer->sync_customers($asiakkaat);
}

if (array_key_exists('asiakashinnat', $synkronoi)) {
  echo date("d.m.Y @ G:i:s")." - Haetaan asiakashinnat ja alennukset.\n";
  $hinnat = presta_specific_prices();

  echo date("d.m.Y @ G:i:s")." - Siirret‰‰n asiakashinnat ja alennukset.\n";
  $presta_prices = new PrestaSpecificPrices($presta_url, $presta_api_key);
  $presta_prices->set_currency_codes($presta_valuuttakoodit);
  $presta_prices->sync_prices($hinnat);
}

if (array_key_exists('tilaukset', $synkronoi)) {
  echo date("d.m.Y @ G:i:s")." - Siirret‰‰n tilaukset.\n";

  $presta_orders = new PrestaSalesOrders($presta_url, $presta_api_key);
  $presta_orders->set_edi_filepath($presta_edi_folderpath);
  $presta_orders->set_yhtiorow($yhtiorow);
  $presta_orders->set_verkkokauppa_customer($presta_verkkokauppa_asiakas);
  $presta_orders->set_fetch_statuses($presta_haettavat_tilaus_statukset);
  $presta_orders->set_fetched_status($presta_haettu_tilaus_status);
  $presta_orders->transfer_orders_to_pupesoft();
}

// Otetaan tietokantayhteys uudestaan (voi olla timeoutannu)
mysql_close($link);
unset($link);
require "inc/connect.inc";

// Kun kaikki onnistui, p‰ivitet‰‰n lopuksi timestamppi talteen
$query = "UPDATE avainsana SET
          selite      = '{$datetime_checkpoint_uusi}'
          WHERE yhtio = '{$kukarow['yhtio']}'
          AND laji    = 'TUOTE_EXP_CRON'";
pupe_query($query);

if (mysql_affected_rows() != 1) {
  echo date("d.m.Y @ G:i:s")." - Timestamp p‰ivitys ep‰onnistui!\n";
}

echo date("d.m.Y @ G:i:s")." - Prestashop p‰ivitys valmis.\n";
