<?php

	// Kutsutaanko CLI:stä
	$php_cli = FALSE;

	if (php_sapi_name() == 'cli') {
		$php_cli = TRUE;
	}

	date_default_timezone_set('Europe/Helsinki');

	// jos meillä on lock-file ja se on alle 15 minuuttia vanha
	if (file_exists("/tmp/##unifaun-resend.lock") and mktime()-filemtime("/tmp/##unifaun-resend.lock") < 300) {
		echo "Unifaun-resend lähetys käynnissä, odota hetki!";
	}
	elseif (file_exists("/tmp/##unifaun-resend.lock") and mktime()-filemtime("/tmp/##unifaun-resend.lock") >= 300) {
		echo "VIRHE: Unifaun-resend lähetys jumissa! Ota yhteys tekniseen tukeen!!!";

		// Onko nagios monitor asennettu?
		if (file_exists("/home/nagios/nagios-pupesoft.sh")) {
			file_put_contents("/home/nagios/nagios-pupesoft.log", "VIRHE: Unifaun-resend lähetys jumissa!", FILE_APPEND);
		}
	}
	else {

		touch("/tmp/##unifaun-resend.lock");

		if ($php_cli) {

			if (trim($argv[1]) == '') {
				echo "Et antanut yhtiötä!\n";
				unlink("/tmp/##unifaun-resend.lock");
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

		// PrintServer
		// koitetaan uudelleen lähettää unifaun-tiedosto, jos FTP-siirto on feilannut aikaisemmin
		if ($unifaun_ps_host != "" and $unifaun_ps_user != "" and $unifaun_ps_pass != "" and $unifaun_ps_path != "" and $unifaun_ps_fail != "") {
			$ftphost = $unifaun_ps_host;
			$ftpuser = $unifaun_ps_user;
			$ftppass = $unifaun_ps_pass;
			$ftppath = $unifaun_ps_path;
			$ftpport = $unifaun_ps_port;
			$ftpfail = $unifaun_ps_fail;
			$ftpsucc = $unifaun_ps_succ;

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

		// UnifaunOnline
		// koitetaan uudelleen lähettää unifaun-tiedosto, jos FTP-siirto on feilannut aikaisemmin
		if ($unifaun_uo_host != "" and $unifaun_uo_user != "" and $unifaun_uo_pass != "" and $unifaun_uo_path != "" and $unifaun_uo_fail != "") {
			$ftphost = $unifaun_uo_host;
			$ftpuser = $unifaun_uo_user;
			$ftppass = $unifaun_uo_pass;
			$ftppath = $unifaun_uo_path;
			$ftpport = $unifaun_uo_port;
			$ftpfail = $unifaun_uo_fail;
			$ftpsucc = $unifaun_uo_succ;

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

		unlink("/tmp/##unifaun-resend.lock");
	}
