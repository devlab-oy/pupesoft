<?php

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
  die("Tätä scriptiä voi ajaa vain komentoriviltä!");
}

$pupe_root_polku = dirname(dirname(__FILE__));

ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.$pupe_root_polku);
ini_set("display_errors", 1);
ini_set("max_execution_time", 0); // unlimited execution time
ini_set("memory_limit", "2G");
error_reporting(E_ALL);
date_default_timezone_set('Europe/Helsinki');

require "inc/connect.inc";
require "inc/functions.inc";
require "rajapinnat/magento_client.php";

// Logitetaan ajo
cron_log();

// Sallitaan vain yksi instanssi tästä skriptistä kerrallaan
pupesoft_flock();

if (empty($magento_api_ht_edi)
  or empty($magento_api_ht_url)
  or empty($magento_api_ht_usr)
  or empty($magento_api_ht_pas)
  or empty($ovt_tunnus)
  or empty($verkkokauppa_asiakasnro)
  or empty($rahtikulu_tuoteno)
  or empty($rahtikulu_nimitys)) {
  exit("Parametrejä puuttuu\n");
}

// Missä tilassa olevat tilaukset haetaan, default 'Processing'
if (empty($magento_tilaushaku)) {
  $magento_tilaushaku = 'Processing';
}

// Pupesoftin tilaustyyppi.
if (empty($pupesoft_tilaustyyppi)) {
  $pupesoft_tilaustyyppi = '';
}

// Vaihoehtoisia OVT-tunnuksia EDI-tilaukselle
if (empty($verkkokauppa_erikoiskasittely)) {
  $verkkokauppa_erikoiskasittely = array();
}

// Korvaavia Maksuehtoja Magenton maksuehdoille
if (empty($magento_maksuehto_ohjaus)) {
  $magento_maksuehto_ohjaus = array();
}

// Soap Clientin extra optiot
if (empty($magento_client_options)) {
  $magento_client_options = array(
    // 'login'    => 'http_basic_user',
    // 'password' => 'http_basic_pass',
  );
}

// Lokitetaanko debug -tietoa lokitiedostoon
if (empty($magento_debug)) {
  $magento_debug = false;
}

// Magenton soap client
$magento = new MagentoClient(
  $magento_api_ht_url,
  $magento_api_ht_usr,
  $magento_api_ht_pas,
  $magento_client_options,
  $magento_debug
);

$magento->set_edi_polku($magento_api_ht_edi);
$magento->set_magento_erikoiskasittely($verkkokauppa_erikoiskasittely);
$magento->set_magento_fetch_order_status($magento_tilaushaku);
$magento->set_magento_maksuehto_ohjaus($magento_maksuehto_ohjaus);
$magento->set_ovt_tunnus($ovt_tunnus);
$magento->set_pupesoft_tilaustyyppi($pupesoft_tilaustyyppi);
$magento->set_rahtikulu_nimitys($rahtikulu_nimitys);
$magento->set_rahtikulu_tuoteno($rahtikulu_tuoteno);
$magento->set_verkkokauppa_asiakasnro($verkkokauppa_asiakasnro);
$magento->setSisaanluvunEsto($magento_sisaanluvun_esto);

if ($magento->getErrorCount() > 0) {
  exit;
}

try {
  // Haetaan tilaukset magentosta ja tallennetaan EDI-tilauksiksi
  $tilaukset = $magento->tallenna_tilaukset();
}
catch (Exception $e) {
  $message = "Tilausten haku epäonnistui";
  $magento->log($message, $e, "order");
}
