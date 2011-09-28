<?php

	date_default_timezone_set("Europe/Helsinki");

	// jos meillä on lock-file ja se on alle 15 minuuttia vanha
	if (file_exists("/tmp/##verkkolasku-in.lock") and mktime()-filemtime("/tmp/##verkkolasku-in.lock") < 900) {
		echo "Verkkolaskujen sisäänluku käynnissä, odota hetki!";
	}
	else {

		touch("/tmp/##verkkolasku-in.lock");

		// Kutsutaanko CLI:stä
		$php_cli = FALSE;

		if (php_sapi_name() == 'cli') {
			$php_cli = TRUE;
		}

		if ($php_cli) {
			//Komentoriviltä
			// otetaan includepath aina rootista
			ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__).PATH_SEPARATOR."/usr/share/pear");
			error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
			ini_set("display_errors", 0);

			require ("inc/connect.inc"); // otetaan tietokantayhteys

			// määritellään polut
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
			echo "Aloitetaan verkkolaskun sisäänluku...<br><br>";

			$laskut     = $verkkolaskut_in;
			$oklaskut   = $verkkolaskut_ok;
			$origlaskut = $verkkolaskut_orig;
			$errlaskut  = $verkkolaskut_error;

			// Kopsataan uploadatta faili verkkoalskudirikkaan
			$copy_boob = copy($filenimi, $laskut."/".$userfile);

			if ($copy_boob === FALSE) {
			    echo "Kopiointi epäonnistui $filenimi $laskut/$userfile<br>";
				unlink("/tmp/##verkkolasku-in.lock");
				exit;
			}
		}
		else {
			echo "Näillä ehdoilla emme voi ajaa verkkolaskujen sisäänlukua!";
			unlink("/tmp/##verkkolasku-in.lock");
			exit;
		}

	    require ("inc/verkkolasku-in.inc"); // täällä on itse koodi
	    require ("inc/verkkolasku-in-erittele-laskut.inc"); // täällä pilkotaan Finvoiceaineiston laskut omiksi tiedostoikseen

		// Käsitellään ensin kaikki Finvoicet
		if ($handle = opendir($laskut)) {
			while (($file = readdir($handle)) !== FALSE) {
				if (is_file($laskut."/".$file)) {

					$nimi = $laskut."/".$file;
					$luotiinlaskuja = erittele_laskut($nimi);

					// Jos tiedostosta luotiin laskuja siirretään se tieltä pois
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
							echo "Verkkolasku vastaanotettu onnistuneesti!<br><br>";
						}

						rename($laskut."/".$file, $oklaskut."/".$file);
				    }
				    else {
						if (!$php_cli)  {
							echo "<font class='error'>Verkkolaskun vastaanotossa virhe:</font><br><pre>$laskuvirhe</pre><br>";
						}

						rename($laskut."/".$file, $errlaskut."/".$file);
					}
				}
			}
		}

		unlink("/tmp/##verkkolasku-in.lock");
	}

?>