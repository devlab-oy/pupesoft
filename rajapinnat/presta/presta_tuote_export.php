<?php

// Kutsutaanko CLI:st�
if (php_sapi_name() != 'cli') {
  die("T�t� scripti� voi ajaa vain komentorivilt�!");
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
require "rajapinnat/presta/presta_functions.php";

require_once "rajapinnat/presta/presta_categories.php";
require_once "rajapinnat/presta/presta_customer_groups.php";
require_once "rajapinnat/presta/presta_customers.php";
require_once "rajapinnat/presta/presta_product_stocks.php";
require_once "rajapinnat/presta/presta_products.php";
require_once "rajapinnat/presta/presta_sales_orders.php";
require_once "rajapinnat/presta/presta_specific_prices.php";

if (empty($argv[1])) {
  die("ERROR! Aja n�in:\npresta_tuote_export.php yhti� [laji,laji,...] [ajentaanko_kaikki]\n");
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
    'asiakashinnat',
    'asiakasryhmat',
    'asiakkaat',
    'kategoriat',
    'saldot',
    'tilaukset',
    'tuotekuvat',
    'tuotteet',
  );
}

// kolmas parametri ajetaanko kaikki
$ajetaanko_kaikki = (!empty($argv[3])) ? "YES" : "NO";

// T�ss� kaikki parametrit, jota voi s��t�� salasanat.php:ss�
if (!isset($presta_url)) {
  // Prestakaupan url
  die('Presta url puuttuu');
}
if (!isset($presta_api_key)) {
  // Prestan API salasana
  die('Presta api key puuttuu');
}
if (!isset($presta_edi_folderpath)) {
  // Mihin hakemistoon tehd��n Prestan tilauksista EDI tiedosto
  die('Presta edi folder path puuttuu');
}
if (!isset($presta_debug)) {
  // debug mode echottaa ruudulle ajon statusta
  $presta_debug = false;
}
if (!isset($presta_home_category_id)) {
  // Prestan "home" kategorian tunnus, jonka alle kaikki Pupesoftin kategoriat siirret��n
  $presta_home_category_id = 2;
}
if (!isset($presta_verkkokauppa_asiakas)) {
  // verkkokauppa_asiakas on fallback, mik�li oikeaa asiakasta ei l�ydet� pupesoftista
  $presta_verkkokauppa_asiakas = null;
}
if (!isset($presta_laskutusosoitteen_muutos)) {
  // voidaanko verkkokaupassa vaihtaa laskutusosoitetta
  // jos false, otetaan aina pupesoftin asiakkaan laskutusosoite
  $presta_laskutusosoitteen_muutos = true;
}
if (!isset($presta_asiakaskasittely)) {
  // PrestaShopin asiakkaan m��rittely joko "asiakkaittain" tai "yhteyshenkiloittain".
  // "asiakkaittain" = Pupesoftin yhteyshenkil�st� tehd��n PrestaShopin asiakas. PrestaShopin
  // asiakkaalla on yksi osoite, joka tulee Pupesoftin yhteyshenkilolt�.
  // "yhteyshenkiloittain" = Pupesoftin asiakkaat yhdistet��n yhteyshenkil�n mukaan. PrestaShopin
  // asiakkaalle tulee useampi osoitetieto, jotka tulevat Pupesoftin eri asiakkailta.
  $presta_asiakaskasittely = 'asiakkaittain';
}
if (!isset($presta_haettavat_tilaus_statukset)) {
  // Miss� tilassa olevia tilauksia haetaan Prestasta
  /* T�m� on Prestan default status list;
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
  // Jos halutaan poikkeavan kent�n arvo prestan tuotteelle
  // nimi = prestan tuotteen kent�n nimi, arvo = tuotesiirto arrayn kent�n nimi
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
if (!isset($presta_dynaamiset_asiakasparametrit)) {
  // Jos halutaan poikkeavan kent�n arvo prestan asiakkaalle
  // nimi = prestan asiakkaan kent�n nimi, arvo = yhteyshenkil� taulun kent�n nimi
  $presta_dynaamiset_asiakasparametrit = array(
    // array(
    //   'nimi' => 'website',
    //   'arvo' => 'www'
    // ),
    // array(
    //   'nimi' => 'firstname',
    //   'arvo' => 'titteli'
    // ),
  );
}
if (!isset($presta_ohita_tuoteparametrit)) {
  // Lista Prestan tuotteen kentist�, joita ei tule p�ivitt�� rajapinnassa
  $presta_ohita_tuoteparametrit = array(
    // "price",
    // "description",
  );
}
if (!isset($presta_synkronoi_tuotepuu)) {
  // Siirret��nko Pupesoftin kategoriat. Aseta false, niin ei siirret�.
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
  // Miten valitaan tuotteet, jotka siirret��n Prestaan
  // 1 = siirret��n tuotteet joiden status != 'P' ja nakyvyys != ''
  // 2 = siirret��n kaikki pupen tuotteet prestaan (poistetutkin), mutta merkataan ne hiddeniksi
  $presta_tuotekasittely = 1;
}
if (!isset($presta_tuoteominaisuudet)) {
  // Mik� tuotearrayn kentt� laitetaan mihinkin Prestan "Product Feature":een.
  $presta_tuoteominaisuudet = array(
    // Tuote-arrayn kentt� => Prestan product_feature_id
    // "tuotemerkki" => 6,
    // "t�htituote"  => 10,
  );
}
if (!isset($presta_vakioasiakasryhmat)) {
  $presta_vakioasiakasryhmat = array(
    // Lis�t��n asiakas Prestassa aina t�h�n ryhm��n
    // Prestan customer_group_id
    // 3,
    // 6,
  );
}
if (!isset($presta_asiakasryhmien_hinta)) {
  // 1 = N�ytet��n hinta, 0 = Ei n�ytet� hintoja
  $presta_asiakasryhmien_hinta = 1;
}
if (!isset($presta_asiakasryhmien_hinnat)) {
  // 0 = Verolliset hinnat, 1 = Verottomat hinnat
  $presta_asiakasryhmien_hinnat = 0;
}
if (!isset($presta_hinnaston_asiakasryhma)) {
  // Prestashop customer group id, joka lis�t��n kaikki Pupesoftin hinnastohintoihin
  $presta_hinnaston_asiakasryhma = null;
}
if (!isset($presta_varastot)) {
  // Pupesoftin varastojen tunnukset, joista lasketaan Prestaan saldot. Nolla on kaikki varastot.
  $presta_varastot = array(0);
}
if (!isset($presta_tuotekuvien_nouto)) {
  // Siirret��nk� Prestashopin tuotekuvat Pupesoftiin
  $presta_tuotekuvien_nouto = false;
}
if (!isset($presta_tilauksen_liitetiedostojen_nouto)) {
  // Haetaan tilauksen liitetiedostot. HUOM! Vaatii custom muutoksia Prestaan.
  $presta_tilauksen_liitetiedostojen_nouto = false;
}
if (!isset($presta_siirrettavat_hinnat)) {
  // Mit� hintoja siirret��n Prestan Specific Prices hinnoiksi
  $presta_siirrettavat_hinnat = array(
    'asiakasalennukset',
    'asiakashinnat',
    'hinnastohinnat',
  );
}

presta_echo("Aloitetaan Prestashop p�ivitys.");

if (presta_ajetaanko_sykronointi('kategoriat', $synkronoi)) {
  $kategoriat = presta_hae_kategoriat();

  presta_echo("Siirret��n tuotekategoriat.");
  $presta_categories = new PrestaCategories($presta_url, $presta_api_key, 'presta_kategoriat');

  $presta_categories->set_category_sync($presta_synkronoi_tuotepuu);
  $presta_categories->set_home_category_id($presta_home_category_id);
  $presta_categories->set_languages_table($presta_kieliversiot);
  $presta_categories->sync_categories($kategoriat);
}

if (presta_ajetaanko_sykronointi('tuotteet', $synkronoi)) {
  $tuotteet = presta_hae_tuotteet();
  $kaikki_tuotteet = presta_hae_kaikki_tuotteet();

  presta_echo("Siirret��n tuotetiedot.");
  $presta_products = new PrestaProducts($presta_url, $presta_api_key, 'presta_tuotteet');

  $presta_products->set_all_products($kaikki_tuotteet);
  $presta_products->set_category_sync($presta_synkronoi_tuotepuu);
  $presta_products->set_dynamic_fields($presta_dynaamiset_tuoteparametrit);
  $presta_products->set_home_category_id($presta_home_category_id);
  $presta_products->set_languages_table($presta_kieliversiot);
  $presta_products->set_product_features($presta_tuoteominaisuudet);
  $presta_products->set_removable_fields($presta_ohita_tuoteparametrit);
  $presta_products->set_tax_rates_table($presta_verokannat);
  $presta_products->set_visibility_type($presta_tuotekasittely);
  $presta_products->sync_products($tuotteet);
}

if (presta_ajetaanko_sykronointi('saldot', $synkronoi)) {
  // t�m� on voitu jo hakea tuotetietojen yhteydess�, ei tartte uutta query�
  if (empty($kaikki_tuotteet)) {
    $kaikki_tuotteet = presta_hae_kaikki_tuotteet();
  }

  presta_echo("Siirret��n saldot.");
  $presta_stocks = new PrestaProductStocks($presta_url, $presta_api_key, 'presta_saldot');

  $presta_stocks->set_all_products($kaikki_tuotteet);
  $presta_stocks->update_stock();
}

if (presta_ajetaanko_sykronointi('tuotekuvat', $synkronoi)) {
  presta_echo("Haetaan tuotekuvat.");
  $presta_products = new PrestaProducts($presta_url, $presta_api_key, 'presta_tuotekuvat');

  $presta_products->set_image_fetch($presta_tuotekuvien_nouto);
  $presta_products->fetch_and_save_images();
}

if (presta_ajetaanko_sykronointi('asiakasryhmat', $synkronoi)) {
  $groups = presta_hae_asiakasryhmat();

  presta_echo("Siirret��n asiakasryhm�t.");
  $presta_customer_groups = new PrestaCustomerGroups($presta_url, $presta_api_key, 'presta_asiakasryhmat');

  $presta_customer_groups->set_show_prices($presta_asiakasryhmien_hinta);
  $presta_customer_groups->set_price_display_method($presta_asiakasryhmien_hinnat);
  $presta_customer_groups->sync_groups($groups);
}

if (presta_ajetaanko_sykronointi('asiakkaat', $synkronoi)) {
  $asiakkaat = presta_hae_asiakkaat($presta_asiakaskasittely);

  presta_echo("Siirret��n asiakkaat.");
  $presta_customer = new PrestaCustomers($presta_url, $presta_api_key, 'presta_asiakkaat');

  $presta_customer->set_customer_handling($presta_asiakaskasittely);
  $presta_customer->set_default_groups($presta_vakioasiakasryhmat);
  $presta_customer->set_dynamic_fields($presta_dynaamiset_asiakasparametrit);
  $presta_customer->sync_customers($asiakkaat);
}

if (presta_ajetaanko_sykronointi('asiakashinnat', $synkronoi)) {
  $hinnat = presta_specific_prices($presta_siirrettavat_hinnat);

  presta_echo("Siirret��n specific prices.");
  $presta_prices = new PrestaSpecificPrices($presta_url, $presta_api_key, 'presta_hinnoittelu');

  $presta_prices->set_currency_codes($presta_valuuttakoodit);
  $presta_prices->set_presta_static_customer_group($presta_hinnaston_asiakasryhma);
  $presta_prices->sync_prices($hinnat);
}

if (presta_ajetaanko_sykronointi('tilaukset', $synkronoi)) {
  presta_echo("Haetaan tilaukset.");
  $presta_orders = new PrestaSalesOrders($presta_url, $presta_api_key, 'presta_tilaukset');

  $presta_orders->set_changeable_invoice_address($presta_laskutusosoitteen_muutos);
  $presta_orders->set_edi_filepath($presta_edi_folderpath);
  $presta_orders->set_fetch_carrier_files($presta_tilauksen_liitetiedostojen_nouto);
  $presta_orders->set_fetch_statuses($presta_haettavat_tilaus_statukset);
  $presta_orders->set_fetched_status($presta_haettu_tilaus_status);
  $presta_orders->set_verkkokauppa_customer($presta_verkkokauppa_asiakas);
  $presta_orders->set_yhtiorow($yhtiorow);
  $presta_orders->transfer_orders_to_pupesoft();
}

presta_echo("Prestashop p�ivitys valmis.");
