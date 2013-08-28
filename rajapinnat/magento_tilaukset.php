<?php

	// Kutsutaanko CLI:stä
	$php_cli = FALSE;

	if (php_sapi_name() == 'cli') {
		$php_cli = TRUE;
	}

	date_default_timezone_set('Europe/Helsinki');

	$flock = fopen("/tmp/##magento_tilaukset.lock", "w+");

	if (! @flock($flock, LOCK_EX | LOCK_NB)) {
		if (file_exists("/tmp/##magento_tilaukset.lock") and mktime()-filemtime("/tmp/##magento_tilaukset.lock") >= 5400) {
			echo "VIRHE: Magento-tilausten sisäänluku jumissa! Ota yhteys tekniseen tukeen!!!";

			// Onko nagios monitor asennettu?
			if (file_exists("/home/nagios/nagios-pupesoft.sh")) {
				file_put_contents("/home/nagios/nagios-pupesoft.log", "VIRHE: Magento-tilausten sisäänluku jumissa!", FILE_APPEND);
			}
		}
		exit;
	}
	else {

		// Kutsutaanko CLI:stä
		if (!$php_cli) {
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

		// Magenton soap client
		$magento = new MagentoClient($magento_api_ana_url, $magento_api_ana_usr, $magento_api_ana_pas);

		if ($magento->getErrorCount() > 0) {
			exit;
		}

		// Haetaan maksetut tilaukset magentosta
		$tilaukset = $magento->hae_tilaukset('Processing');

		// Tehdään EDI-tilaukset
		foreach($tilaukset as $tilaus) {
			$filename = Edi::create($tilaus);
		}
	}
