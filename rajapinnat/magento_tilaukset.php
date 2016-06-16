<?php

// Kutsutaanko CLI:st‰
if (php_sapi_name() != 'cli') {
  die("T‰t‰ scripti‰ voi ajaa vain komentorivilt‰!");
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
require "rajapinnat/edi.php";

// Logitetaan ajo
cron_log();

// Sallitaan vain yksi instanssi t‰st‰ skriptist‰ kerrallaan
pupesoft_flock();

if (empty($magento_api_ht_edi)
  or empty($magento_api_ht_url)
  or empty($magento_api_ht_usr)
  or empty($magento_api_ht_pas)
  or empty($ovt_tunnus)
  or empty($verkkokauppa_asiakasnro)
  or empty($rahtikulu_tuoteno)
  or empty($rahtikulu_nimitys)) {
  exit("Parametrej‰ puuttuu\n");
}

// Miss‰ tilassa olevat tilaukset haetaan, default 'Processing'
if (empty($magento_tilaushaku)) {
  $magento_tilaushaku = 'Processing';
}

// Pupesoftin tilaustyyppi.
if (empty($pupesoft_tilaustyyppi)) {
  $pupesoft_tilaustyyppi = '';
}

// Magenton soap client
$magento = new MagentoClient($magento_api_ht_url, $magento_api_ht_usr, $magento_api_ht_pas);

// Halutaanko est‰‰ tilausten tuplasis‰‰nluku, eli jos tilaushistoriasta lˆytyy k‰sittely
// 'processing_pupesoft'-tilassa niin tilausta ei lueta sis‰‰n jos sis‰‰nluvun esto on p‰‰ll‰
// Default on: YES
if (isset($magento_sisaanluvun_esto) and !empty($magento_sisaanluvun_esto)) {
  $magento->setSisaanluvunEsto($magento_sisaanluvun_esto);
}

if ($magento->getErrorCount() > 0) {
  exit;
}

try {
  // Haetaan tilaukset magentosta
  $tilaukset = $magento->hae_tilaukset($magento_tilaushaku);
}
catch (Exception $e) {
  $message = "Tilausten haku ep‰onnistui";
  $magento->log($message, $e, "order");
  exit;
}

// Tehd‰‰n EDI-tilaukset
foreach ($tilaukset as $tilaus) {
  $filename = Edi::create($tilaus);
}
