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
			error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
			ini_set("display_errors", 0);

			// otetaan tietokanta connect
			require("inc/connect.inc");
			require("inc/functions.inc");
		}

		// jos verkkolaskun lähetys on feilannut niin koitetaan lähettää verkkolasku-tiedosto uudelleen
		if ($handle = opendir("dataout/pupevoice_error")) {

			while (($lasku = readdir($handle)) !== FALSE) {
				if (is_file($lasku)) {
					$kukarow['yhtio'] = $yhtio_dir;
					$kukarow['kuka']  = 'cron';
					$kukarow['kieli'] = 'fi';

					$yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);

					$ftphost = (isset($verkkohost_lah) and trim($verkkohost_lah) != '') ? $verkkohost_lah : "ftp.verkkolasku.net";
					$ftpuser = $yhtiorow['verkkotunnus_lah'];
					$ftppass = $yhtiorow['verkkosala_lah'];
					$ftppath = (isset($verkkopath_lah) and trim($verkkopath_lah) != '') ? $verkkopath_lah : "out/einvoice/data/";
					$ftpfile = $pupe_root_polku."/dataout/".basename($filenimi);

					$tulos_ulos = "";

					require("inc/ftp-send.inc");
				}
			}

			closedir($handle);
		}



		ipost_error
		elmaedi_error
		sisainenfinvoice_error












		if (is_file($verkkolasku_sscc."/".$file)) {
			$kerayseran_numero = preg_replace("/[^0-9]/", "", $file);

			require("inc/verkkolasku_send.inc");
		}



		// koitetaan uudelleen lähettää verkkolasku-tiedosto, jos FTP-siirto on feilannut aikaisemmin
		if ($verkkolasku_host != "" and $verkkolasku_user != "" and $verkkolasku_pass != "" and $verkkolasku_path != "" and $verkkolasku_fail != "") {
			$ftphost = $verkkolasku_host;
			$ftpuser = $verkkolasku_user;
			$ftppass = $verkkolasku_pass;
			$ftppath = $verkkolasku_path;
			$ftpport = $verkkolasku_port;
		    $ftpfail = $verkkolasku_fail;
			$ftpsucc = $verkkolasku_succ;
			$ftpfile = $verkkolaskunimi;

			if ($handle = opendir($ftpfail)) {

				while (($file = readdir($handle)) !== FALSE) {

					if (is_file($ftpfail."/".$file)) {
						$ftpfile = realpath($ftpfail."/".$file);

						require ("inc/ftp-send.inc");

						// Jos siirto meni ok, niin remmataan faili
						if ($palautus == 0) {
							@unlink($ftpfail."/".$file);
						}
					}
				}
			}

			closedir($handle);
		}

		unlink("/tmp/##verkkolasku-resend.lock");
	}


?>