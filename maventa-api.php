#!/usr/bin/php
<?php

	// Kutsutaanko CLI:stä
	if (php_sapi_name() != 'cli') {
		die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
	}

	$pupesoft_polku = "/var/www/html/pupesoft/";

	// otetaan tietokanta connect
	require ($pupesoft_polku."inc/connect.inc");

	if (!isset($verkkolaskut_in) or $verkkolaskut_in == "" or !is_dir($verkkolaskut_in)) {
		die("VIRHE: verkkolaskut_in-kansio ei ole määritelty!");
	}

	$client = new SoapClient('https://testing.maventa.com/apis/bravo/wsdl');

	// Haetaan api_keyt yhtion_parametrit taulusta
	// Kaikki yritykset joilla on api_avain ja ohjelmisto_api_avain kenttää täytettynä. Yrityksen_uuid on vaihtoehtoinen kenttä.
	$sql_query = "	SELECT yhtion_parametrit.maventa_api_avain, yhtion_parametrit.maventa_ohjelmisto_api_avain, yhtion_parametrit.maventa_yrityksen_uuid, yhtio.nimi
					FROM yhtio
					JOIN yhtion_parametrit USING (yhtio)
					WHERE yhtion_parametrit.maventa_api_avain != ''
					AND yhtion_parametrit.maventa_ohjelmisto_api_avain != ''";
	$maventa_result = mysql_query($sql_query) or die("Error in query");

	if (mysql_num_rows($maventa_result) == 0) {
		die("Maventaa käyttäviä yrityksiä ei löytynyt!");
	}

	// Mistä lähtien uudet laskut noudetaan, "YYYYMMDDHHMMSS"
	// Tyhjä hakee vain uudet
	define("TIMESTAMP", "");

	while ($maventa_keys = mysql_fetch_assoc($maventa_result)) {

		#echo $maventa_keys['nimi']."\n";
		#echo "api_avain: ".$maventa_keys['maventa_api_avain']."\n";
		#echo "UUID: ".$maventa_keys['maventa_yrityksen_uuid'];
		#echo ", API_KEY: ".$maventa_keys['maventa_ohjelmisto_api_avain']."\n";
		#echo "Haetaan laskuja...\n";

		// Täytetään api_keys, näillä kirjaudutaan Maventaan
		$api_keys = array();
		$api_keys["user_api_key"] 	= $maventa_keys['maventa_api_avain'];
		$api_keys["vendor_api_key"] = $maventa_keys['maventa_ohjelmisto_api_avain'];

		// Vaihtoehtoinen company_uuid
		if ($maventa_keys['maventa_yrityksen_uuid'] != "") {
			$api_keys["company_uuid"] = $maventa_keys['maventa_yrityksen_uuid'];
		}

		// Used for getting list of outbound invoices.
		// Haetaan lista kaikista inbound invoiceista, listaa vain _unseen_ laskut!
		$uudet_laskut = $client->invoice_list_inbound($api_keys, TIMESTAMP);

		// Jos uusia laskuja ei löydy
		if (!$uudet_laskut) {
			#echo "Ei uusia laskuja!\n";
			exit;
		}

		// Haetaan uudet laskut ja niiden liitteet
		foreach ($uudet_laskut as $lasku) {

			// Haetaan tiedostot ID:n mukaan
			$invoice = $client->inbound_invoice_show($api_keys, $lasku->id, true, "finvoice");

			// Loopataan kaikki liitteet läpi
			foreach ($invoice->attachments as $liite) {
				//Finvoice - XML
				if ($liite->attachment_type == "FINVOICE") {

					// Tiedoston nimeen joku hash ettei tule samoja nimiä.
					$tiedosto = $verkkolaskut_in."maventa_".md5(uniqid(mt_rand(), true))."_".$liite->filename;
					$fd = fopen($tiedosto, "w") or die("Tiedostoa ei voitu tallentaa\n");
					fwrite($fd, base64_decode($liite->file));
					fclose($fd);

					echo "Haettiin yritykselle: $maventa_keys[nimi] lasku toimittajalta: {$lasku->company_name}, {$lasku->invoice_nr}\n";
				}
			}
		}
	}

?>