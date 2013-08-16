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

$locktime = 5400;
$lockfile = "/tmp/tuote_export_cron.lock";

// jos meillä on lock-file ja se on alle 90 minuuttia vanha
if (file_exists($lockfile)) {
	$locktime_calc = mktime() - filemtime($lockfile);

	if ($locktime_calc < $locktime) {
		exit("Magento-tilausten haku käynnissä, odota hetki!")."\n";
	}
	else {
		exit("VIRHE: Magento-tilausten haku jumissa! Ota yhteys tekniseen tukeen!!!")."\n";
	}
}

touch($lockfile);

// Magenton soap client
$magento = new MagentoClient($magento_api_ana_url, $magento_api_ana_usr, $magento_api_ana_pas);

// Haetaan maksetut tilaukset magentosta
$tilaukset = $magento->hae_tilaukset('Processing');

// Tehdään EDI-tilaukset
foreach($tilaukset as $tilaus) {
	$filename = Edi::create($tilaus);
}

unlink($lockfile);
