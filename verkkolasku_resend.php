<?php

	// Kutsutaanko CLI:st‰
	$php_cli = FALSE;

	if (php_sapi_name() == 'cli') {
		$php_cli = TRUE;
	}

	date_default_timezone_set('Europe/Helsinki');

	// jos meill‰ on lock-file ja se on alle 15 minuuttia vanha
	if (file_exists("/tmp/##verkkolasku-resend.lock") and mktime()-filemtime("/tmp/##verkkolasku-resend.lock") < 300) {
		echo "VIRHE: Verkkolaskujen uudelleenl‰hetys k‰ynniss‰, odota hetki!";
	}
	elseif (file_exists("/tmp/##verkkolasku-resend.lock") and mktime()-filemtime("/tmp/##verkkolasku-resend.lock") >= 300) {
		echo "VIRHE: Verkkolaskujen uudelleenl‰hetys jumissa! Ota yhteys tekniseen tukeen!!!";

		// Onko nagios monitor asennettu?
		if (file_exists("/home/nagios/nagios-pupesoft.sh")) {
			file_put_contents("/home/nagios/nagios-pupesoft.log", "VIRHE: Verkkolaskujen uudelleenl‰hetys jumissa!", FILE_APPEND);
		}
	}
	else {

		touch("/tmp/##verkkolasku-resend.lock");

		if ($php_cli) {
			// otetaan includepath aina rootista
			ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__).PATH_SEPARATOR."/usr/share/pear");
			error_reporting(E_ALL);
			ini_set("display_errors", 0);

			// otetaan tietokanta connect
			require("inc/connect.inc");
			require("inc/functions.inc");

			$pupe_root_polku = dirname(__FILE__);
		}

		// jos verkkolaskun l‰hetys on feilannut niin koitetaan l‰hett‰‰ verkkolasku-tiedosto uudelleen

		// PUPEVOICE
		$kansio = "{$pupe_root_polku}/dataout/pupevoice_error/";

		if ($handle = opendir($kansio)) {
			while (($lasku = readdir($handle)) !== FALSE) {
				if (preg_match("/laskutus\-(.*?)\-2/", $lasku, $yhtio)) {

					$kukarow['yhtio'] = $yhtio[1];
					$kukarow['kuka']  = 'cron';
					$kukarow['kieli'] = 'fi';

					$yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);

					$ftphost = (isset($verkkohost_lah) and trim($verkkohost_lah) != '') ? $verkkohost_lah : "ftp.verkkolasku.net";
					$ftpuser = $yhtiorow['verkkotunnus_lah'];
					$ftppass = $yhtiorow['verkkosala_lah'];
					$ftppath = (isset($verkkopath_lah) and trim($verkkopath_lah) != '') ? $verkkopath_lah : "out/einvoice/data/";
					$ftpfile = $kansio.$lasku;
					$ftpsucc = "{$pupe_root_polku}/dataout/";

					$tulos_ulos = "";

					require("inc/ftp-send.inc");
				}
			}

			closedir($handle);
		}

		// IPOST FINVOICE
		$kansio = "{$pupe_root_polku}/dataout/ipost_error/";

		if ($handle = opendir($kansio)) {
			while (($lasku = readdir($handle)) !== FALSE) {
				if (preg_match("/TRANSFER_IPOST\-(.*?)\-2/", $lasku, $yhtio)) {

					$kukarow['yhtio'] = $yhtio[1];
					$kukarow['kuka']  = 'cron';
					$kukarow['kieli'] = 'fi';

					$yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);

					$ftphost 		= "ftp.itella.net";
					$ftpuser 		= $yhtiorow['verkkotunnus_lah'];
					$ftppass 		= $yhtiorow['verkkosala_lah'];
					$ftppath 		= "out/finvoice/data/";
					$ftpfile 		= $kansio.$lasku;
					$renameftpfile 	= str_replace("TRANSFER_IPOST", "DELIVERED_IPOST", $lasku);
					$ftpsucc 		= "{$pupe_root_polku}/dataout/";

					$tulos_ulos = "";

					require("inc/ftp-send.inc");
				}
			}

			closedir($handle);
		}

		// ELMAEDI
		$kansio = "{$pupe_root_polku}/dataout/elmaedi_error/";

		if ($handle = opendir($kansio)) {
			while (($lasku = readdir($handle)) !== FALSE) {
				if (preg_match("/laskutus\-(.*?)\-2/", $lasku, $yhtio)) {

					$kukarow['yhtio'] = $yhtio[1];
					$kukarow['kuka']  = 'cron';
					$kukarow['kieli'] = 'fi';

					$yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);

					$ftphost = $edi_ftphost;
					$ftpuser = $edi_ftpuser;
					$ftppass = $edi_ftppass;
					$ftppath = $edi_ftppath;
					$ftpfile = $kansio.$lasku;
					$ftpsucc = "{$pupe_root_polku}/dataout/";

					$tulos_ulos = "";

					require("inc/ftp-send.inc");
				}
			}

			closedir($handle);
		}

		// PUPESOFT-FINVOICE
		$kansio = "{$pupe_root_polku}/dataout/sisainenfinvoice_error/";

		if ($handle = opendir($kansio)) {
			while (($lasku = readdir($handle)) !== FALSE) {
				if (preg_match("/laskutus\-(.*?)\-2/", $lasku, $yhtio)) {

					$kukarow['yhtio'] = $yhtio[1];
					$kukarow['kuka']  = 'cron';
					$kukarow['kieli'] = 'fi';

					$yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);

					$ftphost = $sisainenfoinvoice_ftphost;
					$ftpuser = $sisainenfoinvoice_ftpuser;
					$ftppass = $sisainenfoinvoice_ftppass;
					$ftppath = $sisainenfoinvoice_ftppath;
					$ftpfile = $kansio.$lasku;
					$ftpsucc = "{$pupe_root_polku}/dataout/";

					$tulos_ulos = "";

					require("inc/ftp-send.inc");
				}
			}

			closedir($handle);
		}

		// MAVENTA
		$kansio = "{$pupe_root_polku}/dataout/maventa_error/";

		if ($handle = opendir($kansio)) {
			while (($lasku = readdir($handle)) !== FALSE) {
				if (preg_match("/laskutus\-(.*?)\-2[0-9]{7,7}\-([0-9]*?)\-serialized.txt/", $lasku, $matsit)) {

					$kukarow['yhtio'] = $matsit[1];
					$kukarow['kuka']  = 'cron';
					$kukarow['kieli'] = 'fi';

					$yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);

					// T‰ytet‰‰n api_keys, n‰ill‰ kirjaudutaan Maventaan
					$api_keys = array();
					$api_keys["user_api_key"] 	= $yhtiorow['maventa_api_avain'];
					$api_keys["vendor_api_key"] = $yhtiorow['maventa_ohjelmisto_api_avain'];

					// Vaihtoehtoinen company_uuid
					if ($yhtiorow['maventa_yrityksen_uuid'] != "") {
						$api_keys["company_uuid"] = $yhtiorow['maventa_yrityksen_uuid'];
					}

					try {
						// Testaus
						#$client = new SoapClient('https://testing.maventa.com/apis/bravo/wsdl');

						// Tuotanto
						$client = new SoapClient('https://secure.maventa.com/apis/bravo/wsdl/');

						// Haetaan tarvittavat tiedot filest‰
						$files_out = unserialize(file_get_contents($kansio.$lasku));

						$status = maventa_invoice_put_file($client, $api_keys, $matsit[2], "", $kukarow['kieli'], $files_out);

						// Siirret‰‰n dataout kansioon jos kaikki meni ok
						rename($kansio.$lasku, "{$pupe_root_polku}/dataout/$lasku");

						echo  "Maventa-lasku $matsit[2]: $status<br>\n";
					}
					catch (Exception $exVirhe) {
						echo "VIRHE: Yhteys Maventaan ep‰onnistui: ".$exVirhe->getMessage()."\n";
					}
				}
			}

			closedir($handle);
		}

		// APIX
		$kansio = "{$pupe_root_polku}/dataout/apix_error/";

		if ($handle = opendir($kansio)) {
			while (($lasku = readdir($handle)) !== FALSE) {
				if (preg_match("/Apix_(.*?)_invoices_/", $lasku, $matsit)) {

					$kukarow['yhtio'] = $matsit[1];
					$kukarow['kuka']  = 'cron';
					$kukarow['kieli'] = 'fi';

					$yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);

					$status = apix_invoice_put_file("", $kukarow['kieli'], $lasku);

					echo "APIX-l‰hetys $status<br>\n";
				}
			}

			closedir($handle);
		}

		unlink("/tmp/##verkkolasku-resend.lock");
	}

?>