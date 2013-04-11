#!/usr/bin/php
<?php

	# Kutsutaanko CLI:stä
	if (php_sapi_name() != 'cli') {
		die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
	}

	$pupesoft_polku = dirname(__FILE__);

	# otetaan tietokanta connect
	require ($pupesoft_polku."/inc/connect.inc");

	// Otetaan defaultit, jos ei olla yliajettu salasanat.php:ssä
	$verkkolaskut_in     = empty($verkkolaskut_in)     ? "/home/verkkolaskut"        : rtrim($verkkolaskut_in, "/");
	$verkkolaskut_ok     = empty($verkkolaskut_ok)     ? "/home/verkkolaskut/ok"     : rtrim($verkkolaskut_ok, "/");
	$verkkolaskut_orig   = empty($verkkolaskut_orig)   ? "/home/verkkolaskut/orig"   : rtrim($verkkolaskut_orig, "/");
	$verkkolaskut_error  = empty($verkkolaskut_error)  ? "/home/verkkolaskut/error"  : rtrim($verkkolaskut_error, "/");
	$verkkolaskut_reject = empty($verkkolaskut_reject) ? "/home/verkkolaskut/reject" : rtrim($verkkolaskut_reject, "/");

	// VIRHE: verkkolasku-kansiot on väärin määritelty!
	if (!is_dir($verkkolaskut_in) or !is_writable($verkkolaskut_in)) exit;
	if (!is_dir($verkkolaskut_ok) or !is_writable($verkkolaskut_ok)) exit;
	if (!is_dir($verkkolaskut_orig) or !is_writable($verkkolaskut_orig)) exit;
	if (!is_dir($verkkolaskut_error) or !is_writable($verkkolaskut_error)) exit;
	if (!is_dir($verkkolaskut_reject) or !is_writable($verkkolaskut_reject)) exit;

	$aikaikkuna = empty($argv[1]) ? 5 : (int) $argv[1];

	# Haetaan api_keyt yhtion_parametrit taulusta
	# Kaikki yritykset joilla on api_avain ja ohjelmisto_api_avain kenttää täytettynä. Yrityksen_uuid on vaihtoehtoinen kenttä.
	$sql_query = "	SELECT yhtion_parametrit.maventa_api_avain, yhtion_parametrit.maventa_ohjelmisto_api_avain, yhtion_parametrit.maventa_yrityksen_uuid, yhtio.nimi, yhtio.yhtio,
					ifnull(date_sub(yhtion_parametrit.maventa_aikaleima, INTERVAL {$aikaikkuna} MINUTE), '0000-00-00 00:00:00') maventa_aikaleima
					FROM yhtio
					JOIN yhtion_parametrit USING (yhtio)
					WHERE yhtion_parametrit.maventa_api_avain != ''
					AND yhtion_parametrit.maventa_ohjelmisto_api_avain != ''";
	$maventa_result = mysql_query($sql_query) or die("Error in query: ".$sql_query);

	while ($maventa_keys = mysql_fetch_assoc($maventa_result)) {

		# Testaus
		#$client = new SoapClient('https://testing.maventa.com/apis/bravo/wsdl');

		# Tuotanto
		$client = new SoapClient('https://secure.maventa.com/apis/bravo/wsdl/');

		# Kun ajetaan ekan kerran niin ajetaan ilman akaleimaa, niin saadaan kaikki "unseen"-laskut.
		if ($maventa_keys["maventa_aikaleima"] == "0000-00-00 00:00:00") {
			$maventa_keys["maventa_aikaleima"] = "";
		}

		#echo $maventa_keys['nimi']."\n";
		#echo "api_avain: ".$maventa_keys['maventa_api_avain']."\n";
		#echo "UUID: ".$maventa_keys['maventa_yrityksen_uuid'].", API_KEY: ".$maventa_keys['maventa_ohjelmisto_api_avain']."\n";
		#echo "Haetaan laskuja...\n";

		# Täytetään api_keys, näillä kirjaudutaan Maventaan
		$api_keys = array();
		$api_keys["user_api_key"]   = $maventa_keys['maventa_api_avain'];
		$api_keys["vendor_api_key"] = $maventa_keys['maventa_ohjelmisto_api_avain'];

		# Vaihtoehtoinen company_uuid
		if ($maventa_keys['maventa_yrityksen_uuid'] != "") {
			$api_keys["company_uuid"] = $maventa_keys['maventa_yrityksen_uuid'];
		}

		# Kellonaika Maventan serverillä "YYYYMMDDHHMMSS"
		$maventan_kellonaika = $client->server_time();
		$maventan_kellonaika = date("Y-m-d H:i:s", strtotime(substr($maventan_kellonaika, 0, 8)."T".substr($maventan_kellonaika, 8)));

		# Haetaan uudet laskut
		# $maventa_keys["maventa_aikaleima"] --> viimeisin laskuhaku kannassa, tästä otettu 5 minsaa pois niin pelataan aikaikkunoiden suhteen varman päälle.
		# Duplikaattitsekki kuitenkin laskuloopissa.
		$uudet_laskut = $client->invoice_list_inbound($api_keys, preg_replace("/[^0-9]/", "", $maventa_keys["maventa_aikaleima"]));

		# Päivitetään aikaleima kantaan
		$aika_query = "	UPDATE yhtion_parametrit
						SET maventa_aikaleima = '$maventan_kellonaika'
						WHERE yhtio = '$maventa_keys[yhtio]'";
		$aika_res = mysql_query($aika_query) or die("Error in query: ".$aika_query);

		# Jos uusia laskuja ei löydy
		if (!$uudet_laskut) {
			continue;
		}

		# Haetaan uudet laskut ja niiden liitteet
		foreach ($uudet_laskut as $lasku) {

			# Jos id on tyhjää niin ohitetaan
			if ($lasku->id == "") {
				continue;
			}

			$find = exec("find $verkkolaskut_in -name \"*maventa_{$lasku->id}_maventa*\"");

			# Tsekataan ettei tää lasku oo jo noudettu
			if ($find != "") {
				continue;
			}

			# Haetaan tiedostot ID:n mukaan
			$invoice = $client->inbound_invoice_show($api_keys, $lasku->id, true, "finvoice");

			# Loopataan kaikki liitteet läpi
			foreach ($invoice->attachments as $liite) {

				# Finvoice - XML
				if ($liite->attachment_type == "FINVOICE") {
					# Tiedoston nimeen id mukaan, ettei tule samoja nimiä. Älä muuta nimeä, koska siitä etsitään ID myöhemmässä vaiheessa (verkkolasku-in.inc)
					file_put_contents($verkkolaskut_in."/maventa_".$lasku->id."_maventa-".$liite->filename, base64_decode($liite->file));

					echo "Haettiin yritykselle: $maventa_keys[nimi] lasku toimittajalta: {$lasku->company_name}, {$lasku->invoice_nr}\n";
				}

				# Laskun kuva ja liitteet tallennetaan $verkkolaskut_orig-kansioon
				if ($liite->attachment_type == "INVOICE_IMAGE" or $liite->attachment_type == "ATTACHMENT") {
					file_put_contents($verkkolaskut_orig."/maventa_".$lasku->id."_maventa-".$liite->filename, base64_decode($liite->file));
				}
			}
		}
	}
