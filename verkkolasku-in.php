<?php

	if (isset($argc) and $argc > 0) {
		//Komentorivilt‰
		// pit‰‰ siirty‰ www roottiin
	chdir("/var/www/html/pupesoft");

	// m‰‰ritell‰‰n polut
    $laskut     = "/home/verkkolaskut";
    $oklaskut   = "/home/verkkolaskut/ok";
    $origlaskut = "/home/verkkolaskut/orig";
    $errlaskut  = "/home/verkkolaskut/error";

		$komentorivilta = TRUE;

		// otetaan includepath aina rootista
		ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(__FILE__)).PATH_SEPARATOR."/usr/share/pear");
		error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
		ini_set("display_errors", 0);

		require ("inc/connect.inc"); // otetaan tietokantayhteys
	}
	elseif (strpos($_SERVER['SCRIPT_NAME'], "tiliote.php") !== FALSE and $verkkolaskut_in != "" and $verkkolaskut_ok != "" and $verkkolaskut_orig != "" and $verkkolaskut_error != "") {
		//Pupesoftista
		echo "Aloitetaan verkkolaskun sis‰‰nluku...<br><br>";

		$laskut     = $verkkolaskut_in;
		$oklaskut   = $verkkolaskut_ok;
		$origlaskut = $verkkolaskut_orig;
		$errlaskut  = $verkkolaskut_error;

		$komentorivilta = FALSE;

		// Kopsataan uploadatta faili verkkoalskudirikkaan
		$copy_boob = copy($filenimi, $laskut."/".$userfile);

		if ($copy_boob === FALSE) {
		    echo "Kopiointi ep‰onnistui $filenimi $laskut/$userfile<br>";
			exit;
		}
	}
	else {
		echo "N‰ill‰ ehdoilla emme voi ajaa verkkolaskujen sis‰‰nlukua!";
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

					$cleanfile = escapeshellarg($file);

					system("mv $laskut/$cleanfile $origlaskut/$cleanfile");
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
				$laskuvirhe = verkkolasku_in($nimi);

			    if ($laskuvirhe == "") {
					if (!$komentorivilta)  {
						echo "Verkkolasku vastaanotettu onnistuneesti!<br><br>";
					}

					$cleanfile = escapeshellarg($file);

			    	system("mv -f $nimi $oklaskut/$cleanfile");
			    }
			    else {
					if (!$komentorivilta)  {
						echo "<font class='error'>Verkkolaskun vastaanotossa virhe:</font><br><pre>$laskuvirhe</pre><br>";
					}

					$cleanfile = escapeshellarg($file);

			    	system("mv -f $nimi $errlaskut/$cleanfile");
				}
			}
		}
	}

?>
