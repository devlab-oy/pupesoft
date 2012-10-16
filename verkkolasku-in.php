<?php

	date_default_timezone_set("Europe/Helsinki");

	// jos meill‰ on lock-file ja se on alle 90 minuuttia vanha (90 minsaa ku backuppia odotellessa saattaa tunti vier‰ht‰‰ aika nopeasti)
	if (file_exists("/tmp/##verkkolasku-in.lock") and mktime()-filemtime("/tmp/##verkkolasku-in.lock") < 5400) {
		echo "Verkkolaskujen sis‰‰nluku k‰ynniss‰, odota hetki!";
	}
	elseif (file_exists("/tmp/##verkkolasku-in.lock") and mktime()-filemtime("/tmp/##verkkolasku-in.lock") >= 5400) {
		echo "VIRHE: Verkkolaskujen sis‰‰nluku jumissa! Ota yhteys tekniseen tukeen!!!";

		// Onko nagios monitor asennettu?
		if (file_exists("/home/nagios/nagios-pupesoft.sh")) {
			file_put_contents("/home/nagios/nagios-pupesoft.log", "VIRHE: Verkkolaskujen sis‰‰nluku jumissa!", FILE_APPEND);
		}
	}
	else {

		touch("/tmp/##verkkolasku-in.lock");

		// Kutsutaanko CLI:st‰
		$php_cli = FALSE;

		if (php_sapi_name() == 'cli') {
			$php_cli = TRUE;
		}

		if ($php_cli) {
			//Komentorivilt‰
			// otetaan includepath aina rootista
			ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__).PATH_SEPARATOR."/usr/share/pear");
			error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
			ini_set("display_errors", 0);

			require ("inc/connect.inc"); // otetaan tietokantayhteys

			// m‰‰ritell‰‰n polut
			if (!isset($verkkolaskut_in)) {
				$verkkolaskut_in = "/home/verkkolaskut";
			}
			if (!isset($verkkolaskut_ok)){
				$verkkolaskut_ok = "/home/verkkolaskut/ok";
			}
			if (!isset($verkkolaskut_orig)) {
				$verkkolaskut_orig = "/home/verkkolaskut/orig";
			}
			if (!isset($verkkolaskut_error)) {
				$verkkolaskut_error = "/home/verkkolaskut/error";
			}

			$laskut     = $verkkolaskut_in;
			$oklaskut   = $verkkolaskut_ok;
			$origlaskut = $verkkolaskut_orig;
			$errlaskut  = $verkkolaskut_error;
		}
		elseif (strpos($_SERVER['SCRIPT_NAME'], "tiliote.php") !== FALSE and $verkkolaskut_in != "" and $verkkolaskut_ok != "" and $verkkolaskut_orig != "" and $verkkolaskut_error != "") {
			//Pupesoftista
			echo "Aloitetaan verkkolaskun sis‰‰nluku...<br>\n<br>\n";

			$laskut     = $verkkolaskut_in;
			$oklaskut   = $verkkolaskut_ok;
			$origlaskut = $verkkolaskut_orig;
			$errlaskut  = $verkkolaskut_error;

			// Kopsataan uploadatta faili verkkoalskudirikkaan
			$copy_boob = copy($filenimi, $laskut."/".$userfile);

			if ($copy_boob === FALSE) {
			    echo "Kopiointi ep‰onnistui $filenimi $laskut/$userfile<br>\n";
				unlink("/tmp/##verkkolasku-in.lock");
				exit;
			}
		}
		else {
			echo "N‰ill‰ ehdoilla emme voi ajaa verkkolaskujen sis‰‰nlukua!";
			unlink("/tmp/##verkkolasku-in.lock");
			exit;
		}

	    require ("inc/verkkolasku-in.inc"); // t‰‰ll‰ on itse koodi
	    require ("inc/verkkolasku-in-erittele-laskut.inc"); // t‰‰ll‰ pilkotaan Finvoiceaineiston laskut omiksi tiedostoikseen

		// K‰sitell‰‰n ensin kaikki Finvoicet
		if ($handle = opendir($laskut)) {
			while (($file = readdir($handle)) !== FALSE) {
				if (is_file($laskut."/".$file)) {

					$nimi = $laskut."/".$file;
					$luotiinlaskuja = erittele_laskut($nimi);

					// Jos tiedostosta luotiin laskuja siirret‰‰n se tielt‰ pois
					if ($luotiinlaskuja > 0) {
						rename($laskut."/".$file, $origlaskut."/".$file);
					}
				}
			}
		}

		if ($handle = opendir($laskut)) {

			while (($file = readdir($handle)) !== FALSE) {

				if (is_file($laskut."/".$file)) {

					// $yhtiorow ja $xmlstr
					unset($yhtiorow);
					unset($xmlstr);

					$nimi = $laskut."/".$file;
					$laskuvirhe = verkkolasku_in($nimi, TRUE);

				    if ($laskuvirhe == "") {
						if (!$php_cli)  {
							echo "Verkkolasku vastaanotettu onnistuneesti!<br>\n<br>\n";
						}

						rename($laskut."/".$file, $oklaskut."/".$file);
				    }
				    else {
						if (!$php_cli)  {
							echo "<font class='error'>Verkkolaskun vastaanotossa virhe:</font><br>\n<pre>$laskuvirhe</pre><br>\n";
						}

						rename($laskut."/".$file, $errlaskut."/".$file);
					}
				}
			}
		}

		unlink("/tmp/##verkkolasku-in.lock");

		if ($php_cli) {
			# laitetaan k‰yttˆoikeudet kuntoon
			system("chown -R root:apache $verkkolaskut_in; chmod -R 770 $verkkolaskut_in;");
		}

		# siivotaan yli 90 p‰iv‰‰ vanhat aineistot
		system("find $verkkolaskut_in -type f -mtime +90 -delete");
	}

?>