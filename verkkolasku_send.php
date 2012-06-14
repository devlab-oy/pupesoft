<?php

	// Kutsutaanko CLI:st�
	$php_cli = FALSE;

	if (php_sapi_name() == 'cli') {
		$php_cli = TRUE;
	}

	date_default_timezone_set('Europe/Helsinki');

	if ($php_cli) {

		if (trim($argv[1]) == '') {
			echo "Et antanut yhti�t�!\n";
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

		// Haetaan k�ytt�j�n tiedot
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

	// Katsotaan, ett� tarvittavat muuttujat on setattu
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

	// Jos meill� on lock-file ja se on alle 15 minuuttia vanha
	if (file_exists("/tmp/##verkkolasku-send.lock") and mktime()-filemtime("/tmp/##verkkolasku-send.lock") < 300) {
		echo "verkkolasku-send l�hetys k�ynniss�, odota hetki!\n";
	}
	// Jos meill� on lock-file ja se on yli 15 minuuttia vanha
	elseif (file_exists("/tmp/##verkkolasku-send.lock") and mktime()-filemtime("/tmp/##verkkolasku-send.lock") >= 300) {
		echo "VIRHE: verkkolasku-send l�hetys jumissa! Ota yhteys tekniseen tukeen!!!\n";
		if (file_exists("/home/nagios/nagios-pupesoft.sh")) {
			file_put_contents("/home/nagios/nagios-pupesoft.log", "VIRHE: verkkolasku-send l�hetys jumissa!", FILE_APPEND);
		}
	}
	else {

		touch("/tmp/##verkkolasku-send.lock");

		// Loopataan l�pi pankkipolku
		if ($handle = opendir($localdir)) {
			while (($file = readdir($handle)) !== FALSE) {
				$ftpfile = realpath($localdir."/".$file);
				if (is_file($ftpfile)) {
					require ("inc/ftp-send.inc");
				}
			}
			closedir($handle);
		}

		// Loopataan l�pi ep�onnistuneet dirikka
		if ($handle = opendir($localdir_error)) {
			// Ei siirret� feilattuja en�� uudestaan jos feilaa taas
			unset($ftpfail);
			while (($file = readdir($handle)) !== FALSE) {
				$ftpfile = realpath($localdir_error."/".$file);
				if (is_file($ftpfile)) {
					require ("inc/ftp-send.inc");
				}
			}
			closedir($handle);
		}

		unlink("/tmp/##verkkolasku-send.lock");
	}
