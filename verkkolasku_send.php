<?php

	// Kutsutaanko CLI:stä
	$php_cli = FALSE;

	if (php_sapi_name() == 'cli') {
		$php_cli = TRUE;
	}

	date_default_timezone_set('Europe/Helsinki');

	$flock = fopen("/tmp/##verkkolasku-send.lock", "w+");

	if (! @flock($flock, LOCK_EX | LOCK_NB)) {
		if (file_exists("/tmp/##verkkolasku-send.lock") and mktime()-filemtime("/tmp/##verkkolasku-send.lock") >= 300) {
			echo "VIRHE: verkkolasku-send lähetys jumissa! Ota yhteys tekniseen tukeen!!!\n";
			
			// Onko nagios monitor asennettu?
			if (file_exists("/home/nagios/nagios-pupesoft.sh")) {
				file_put_contents("/home/nagios/nagios-pupesoft.log", "VIRHE: verkkolasku-send lähetys jumissa!", FILE_APPEND);
			}
		}
		exit;
	}
	else {

		if ($php_cli) {

			if (trim($argv[1]) == '') {
				echo "Et antanut yhtiötä!\n";
				exit;
			}

			// otetaan includepath aina rootista
			ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__).PATH_SEPARATOR."/usr/share/pear");
			error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
			ini_set("display_errors", 0);

			// otetaan tietokanta connect
			require("inc/connect.inc");
			require("inc/functions.inc");

			$yhtio = pupesoft_cleanstring($argv[1]);
			$yhtiorow = hae_yhtion_parametrit($yhtio);

			// Haetaan käyttäjän tiedot
			$query = "	SELECT *
						FROM kuka
						WHERE yhtio = '$yhtio'
						AND kuka = 'admin'";
			$result = pupe_query($query);

			if (mysql_num_rows($result) == 0) {
				echo "Admin kayttaja puuttuu!";
				exit;
			}

			// Adminin tiedot, mutta kuka on cron
			$kukarow = mysql_fetch_assoc($result);
			$kukarow['kuka'] = 'cron';
			$PHP_SELF = "verkkolasku_send.php";
		}

		// Katsotaan, että tarvittavat muuttujat on setattu
		if (!isset(	$verkkolaskut_siirto["host"],
					$verkkolaskut_siirto["user"],
					$verkkolaskut_siirto["pass"],
					$verkkolaskut_siirto["path"],
					$verkkolaskut_siirto["type"],
					$verkkolaskut_siirto["local_dir"],
					$verkkolaskut_siirto["local_dir_ok"],
					$verkkolaskut_siirto["local_dir_error"])) {
			echo "verkkolasku-send parametrit puuttuu!\n";
			exit;
		}

		// Setataan oikeat muuttujat
		$ftphost = $verkkolaskut_siirto["host"];
		$ftpuser = $verkkolaskut_siirto["user"];
		$ftppass = $verkkolaskut_siirto["pass"];
		$ftppath = $verkkolaskut_siirto["path"];
		$ftptype = $verkkolaskut_siirto["type"];
		$localdir = $verkkolaskut_siirto["local_dir"];
		$localdir_error = $verkkolaskut_siirto["local_dir_error"];
		$ftpsucc = $verkkolaskut_siirto["local_dir_ok"];
	    $ftpfail = $localdir_error;



		// Loopataan läpi pankkipolku
		if ($handle = opendir($localdir)) {
			while (($file = readdir($handle)) !== FALSE) {
				$ftpfile = realpath($localdir."/".$file);
				if (is_file($ftpfile)) {
					require ("inc/ftp-send.inc");
				}
			}
			closedir($handle);
		}

		// Loopataan läpi epäonnistuneet dirikka
		if ($handle = opendir($localdir_error)) {
			// Ei siirretä feilattuja enää uudestaan jos feilaa taas
			unset($ftpfail);
			while (($file = readdir($handle)) !== FALSE) {
				$ftpfile = realpath($localdir_error."/".$file);
				if (is_file($ftpfile)) {
					require ("inc/ftp-send.inc");
				}
			}
			closedir($handle);
		}
	}
