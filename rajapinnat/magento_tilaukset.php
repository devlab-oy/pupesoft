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

$pupe_root_polku = dirname(dirname(__FILE__));

require "{$pupe_root_polku}/inc/connect.inc";
require "{$pupe_root_polku}/inc/functions.inc";

// Logitetaan ajo
cron_log();

// Sallitaan vain yksi instanssi t‰st‰ skriptist‰ kerrallaan
pupesoft_flock();

require "{$pupe_root_polku}/rajapinnat/magento_client.php";
require "{$pupe_root_polku}/rajapinnat/edi.php";

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
