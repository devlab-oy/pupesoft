<?php

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
	die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
}

$pupe_root_polku = dirname(dirname(__FILE__));

require ("{$pupe_root_polku}/inc/connect.inc");
require ("{$pupe_root_polku}/inc/functions.inc");
require ("{$pupe_root_polku}/rajapinnat/magento_client.php");
require ("{$pupe_root_polku}/rajapinnat/edi.php");

if (empty($magento_api_edi) or empty($magento_api_url) or empty($magento_api_usr) or empty($magento_api_pas)) {
	exit("Parametrejä puuttuu\n");
}

// Magenton soap client
$magento = new MagentoClient($magento_api_url, $magento_api_usr, $magento_api_pas);

// Haetaan maksetut tilaukset magentosta
$tilaukset = $magento->hae_tilaukset('pending');

// Tehdään EDI-tilaukset
foreach($tilaukset as $tilaus) {
	$filename = Edi::create($tilaus);
}
