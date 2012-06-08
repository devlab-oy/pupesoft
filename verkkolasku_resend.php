<?php

	// Kutsutaanko CLI:stä
	$php_cli = FALSE;

	if (php_sapi_name() == 'cli') {
		$php_cli = TRUE;
	}

	date_default_timezone_set('Europe/Helsinki');

	// jos meillä on lock-file ja se on alle 15 minuuttia vanha
	if (file_exists("/tmp/##verkkolasku-resend.lock") and mktime()-filemtime("/tmp/##verkkolasku-resend.lock") < 300) {
		echo "VIRHE: Verkkolaskujen uudelleenlähetys käynnissä, odota hetki!";
	}
	elseif (file_exists("/tmp/##verkkolasku-resend.lock") and mktime()-filemtime("/tmp/##verkkolasku-resend.lock") >= 300) {
		echo "VIRHE: Verkkolaskujen uudelleenlähetys jumissa! Ota yhteys tekniseen tukeen!!!";

		// Onko nagios monitor asennettu?
		if (file_exists("/home/nagios/nagios-pupesoft.sh")) {
			file_put_contents("/home/nagios/nagios-pupesoft.log", "VIRHE: Verkkolaskujen uudelleenlähetys jumissa!", FILE_APPEND);
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

		// jos verkkolaskun lähetys on feilannut niin koitetaan lähettää verkkolasku-tiedosto uudelleen

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

		unlink("/tmp/##verkkolasku-resend.lock");
	}

?>