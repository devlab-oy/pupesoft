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

  $pupe_root_polku = dirname(dirname(__FILE__));

  require ("{$pupe_root_polku}/inc/connect.inc");
  require ("{$pupe_root_polku}/inc/functions.inc");

  $lock_params = array(
    "locktime" => 5400,
    "lockfile" => '##tuote-export-flock.lock',
  );

  // Sallitaan vain yksi instanssi tästä skriptistä kerrallaan
  pupesoft_flock($lock_params);

  require ("{$pupe_root_polku}/rajapinnat/magento_client.php");
  require ("{$pupe_root_polku}/rajapinnat/edi.php");

  if (empty($magento_api_ana_edi)
    or empty($magento_api_ana_url)
    or empty($magento_api_ana_usr)
    or empty($magento_api_ana_pas)
    or empty($ovt_tunnus)
    or empty($pupesoft_tilaustyyppi)
    or empty($verkkokauppa_asiakasnro)
    or empty($rahtikulu_tuoteno)
    or empty($rahtikulu_nimitys)) {
    exit("Parametrejä puuttuu\n");
  }

  // Magenton soap client
  $magento = new MagentoClient($magento_api_ana_url, $magento_api_ana_usr, $magento_api_ana_pas);

  if ($magento->getErrorCount() > 0) {
    exit;
  }

  // Haetaan maksetut tilaukset magentosta
  $tilaukset = $magento->hae_tilaukset('Processing');

  // Tehdään EDI-tilaukset
  foreach ($tilaukset as $tilaus) {
    $filename = Edi::create($tilaus);
  }
