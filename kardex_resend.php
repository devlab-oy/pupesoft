<?php

	// Kutsutaanko CLI:stä
	$php_cli = FALSE;

	if (php_sapi_name() == 'cli') {
		$php_cli = TRUE;
	}

	// jos meillä on lock-file ja se on alle 15 minuuttia vanha
	if (file_exists("/tmp/##kardex-resend.lock") and mktime()-filemtime("/tmp/##kardex-resend.lock") < 300) {
		echo "Kardex-resend lähetys käynnissä, odota hetki!";
	}
	elseif (file_exists("/tmp/##kardex-resend.lock") and mktime()-filemtime("/tmp/##kardex-resend.lock") >= 300) {
		echo "VIRHE: Kardex-resend lähetys jumissa! Ota yhteys tekniseen tukeen!!!";

		// Onko nagios monitor asennettu?
		if (file_exists("/home/nagios/nagios-pupesoft.sh")) {
			file_put_contents("/home/nagios/nagios-pupesoft.log", "VIRHE: Kardex-resend lähetys jumissa!", FILE_APPEND);
		}
	}
	else {

		touch("/tmp/##kardex-resend.lock");

		if ($php_cli) {

			if (trim($argv[1]) == '') {
				echo "Et antanut yhtiötä!\n";
				unlink("/tmp/##kardex-resend.lock");
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

			$yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);
		}

		if (!is_dir($kardex_sscc)) {
			echo "Kardex_sscc-kansio virheellinen!\n";
			unlink("/tmp/##kardex-resend.lock");
			exit;
		}

		if ($handle = opendir($kardex_sscc)) {

			while (($file = readdir($handle)) !== FALSE) {

				if (is_file($kardex_sscc."/".$file)) {
					$kerayseran_numero = preg_replace("/[^0-9]/", "", $file);

					require("inc/kardex_send.inc");
				}
			}

			closedir($handle);
		}

		unlink("/tmp/##kardex-resend.lock");
	}
