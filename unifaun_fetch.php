<?php

	// Kutsutaanko CLI:stä
	$php_cli = FALSE;

	if (php_sapi_name() == 'cli' or isset($editil_cli)) {
		$php_cli = TRUE;
	}

	// jos meillä on lock-file ja se on alle 15 minuuttia vanha
	if (file_exists("/tmp/##unifaun-fetch.lock") and mktime()-filemtime("/tmp/##unifaun-fetch.lock") < 300) {
		echo "FTP-get sisäänluku käynnissä, odota hetki!";
	}
	elseif (file_exists("/tmp/##unifaun-fetch.lock") and mktime()-filemtime("/tmp/##unifaun-fetch.lock") >= 300) {
		echo "VIRHE: FTP-get sisäänluku jumissa! Ota yhteys tekniseen tukeen!!!";

		// Onko nagios monitor asennettu?
		if (file_exists("/home/nagios/nagios-pupesoft.sh")) {
			file_put_contents("/home/nagios/nagios-pupesoft.log", "VIRHE: Unifaun-fetch sisäänluku jumissa!", FILE_APPEND);
		}
	}
	else {

		touch("/tmp/##unifaun-fetch.lock");

		if ($php_cli) {

			if (trim($argv[1]) == '') {
				echo "Et antanut yhtiötä!\n";
				unlink("/tmp/##unifaun-fetch.lock");
				exit;
			}

			// otetaan includepath aina rootista
			ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__).PATH_SEPARATOR."/usr/share/pear");
			error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
			ini_set("display_errors", 0);

			// otetaan tietokanta connect
			require("inc/connect.inc");
			require("inc/functions.inc");

			$kukarow['yhtio'] = (string) $argv[1];
			$kukarow['kuka']  = 'cron';
			$kukarow['kieli'] = 'fi';
			$operaattori 	  = (string) $argv[2];

			$yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);
		}

		if (trim($operaattori) == '') {
			echo "Operaattori puuttuu: unifaun_ps/unifaun_uo!\n";
			unlink("/tmp/##unifaun-fetch.lock");
			exit;
		}

		if (trim($ftpget_dest[$operaattori]) == '') {
			echo "Unifaun return-kansio puuttuu!\n";
			unlink("/tmp/##unifaun-fetch.lock");
			exit;
		}

		if (!is_dir($ftpget_dest[$operaattori])) {
			echo "Unifaun return-kansio virheellinen!\n";
			unlink("/tmp/##unifaun-fetch.lock");
			exit;
		}

		require('ftp-get.php');

		if ($handle = opendir($ftpget_dest[$operaattori])) {

			while (($file = readdir($handle)) !== FALSE) {

				if (is_file($ftpget_dest[$operaattori]."/".$file)) {

					/*
	 				 * pupessa tilausnumerona lähetettiin tilausnumero_ssccvanha esim.: 6215821_1025616
					 */

					/* Normaalisanoma ilman viitettä
					 * tilnro;sscc_ulkoinen;rahtikirjanro;datetime
					 * 12345;373325380188609457;1000017762;2012-01-20 13:51:50
					 */

					/* Normaalisanoma viitteen kanssa
					 * tilnro;sscc_ulkoinen;rahtikirjanro;datetime;reference
					 * 12345;373325380188609457;1000017762;2012-01-20 13:51:50;77777777
					 */

					/* Sanomien erikoiskeissit (Itella, TNT, DPD, Matkahuolto)
					 * tilnro;ensimmäinen kollitunniste on lähetysnumero;sama ensimmäinen kollitunniste on rahtikirjanumerona;timestamp
					 * 199188177;MA1234567810000009586;MA1234567810000009586;2012-01-23 10:58:57 (Kimi: MAtkahuolto)
					 *
					 * tilnro;sscc_ulkoinen;LOGY rahtikirjanro;timestamp
					 * 12345;373325380188816602;200049424052;2012-01-23 10:59:03 (Kimi: Kaukokiito, Kiitolinja ja Vr Transpoint; SSCC + LOGY-rahtikirjanumero)
					 *
					 *
					 * 555555;JJFI65432110000070773;;2012-01-24 11:12:56; (Kimi: Itella)
					 *
					 *
					 * 14656099734;1;GE249908410WW;2012-01-24 11:12:49;52146882 (Kimi: TNT)
					 */

					list($tilausnumero_sscc, $sscc_ulkoinen, $rahtikirjanro, $timestamp, $viite) = explode(";", file_get_contents($ftpget_dest[$operaattori]."/".$file));

					$sscc_ulkoinen = $sscc_ulkoinen == 1 ? 0 : $sscc_ulkoinen;

					list($tilausnumero, $sscc) = explode("_", $tilausnumero_sscc);

					$query = "	UPDATE kerayserat SET
								sscc_ulkoinen = '{$sscc_ulkoinen}',
								muutospvm 	  = now(),
								muuttaja 	  = 'cron'
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND sscc 	= '{$sscc}'
								AND otunnus = '{$tilausnumero}'";
					$upd_res = pupe_query($query);

					rename($ftpget_dest[$operaattori]."/".$file, $ftpget_dest[$operaattori]."/ok/".$file);
				}
			}
		}

		unlink("/tmp/##unifaun-fetch.lock");
	}
