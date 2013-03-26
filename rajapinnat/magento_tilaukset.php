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

if (empty($magentoEdiPolku) or empty($magentoSoapUrl) or empty($magentoSoapUser) or empty($magentoSoapPass)) {
	exit;
}

// Magenton soap client
$magento = new MagentoClient($magentoSoapUrl, $magentoSoapUser, $magentoSoapPass);

// Haetaan maksetut tilaukset magentosta
$tilaukset = $magento->hae_tilaukset('paid');

// Tehdään EDI-tilaukset
foreach($tilaukset as $tilaus) {
	$filename = Edi::create($tilaus);
}
