<?php

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
	die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
}

$pupe_root_polku = dirname(dirname(__FILE__));
require ("{$pupe_root_polku}/inc/connect.inc");
require ("{$pupe_root_polku}/inc/functions.inc");

require 'magento_client.php';
require 'edi.php';

$url = 'http://127.0.0.1/~antti/magento/index.php/api/soap/?wsdl';
$user = 'anti';
$pass = '123456';

// Magenton soap client
$magento= new MagentoClient($url, $user, $pass);

// Haetaan maksetut tilaukset magentosta
$tilaukset = $magento->hae_tilaukset('canceled');

// Tehdään EDI-tilaukset
foreach($tilaukset as $tilaus) {
	$filename = Edi::create($tilaus);
}
