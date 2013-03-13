<?php

//* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *//
$useslave = 1;

// Kutsutaanko CLI:st‰
$php_cli = FALSE;

if (php_sapi_name() == 'cli') {
	$php_cli = TRUE;
}

if (!$php_cli) {
	require ("inc/parametrit.inc");
	$kyhtio = $kukarow['yhtio'];
}
else {
	//tarvitaan yhtiˆ
	if ($argv[1] != "") $kyhtio = trim($argv[1]);
	else die ("Yhtiˆ on annettava!");

	require ("/var/www/html/pupesoft/inc/connect.inc");
	require ("/var/www/html/pupesoft/inc/functions.inc");
	$tee = "aja";
}

function ftp_rmfiles($ftp_stream, $directory, $nodel = "", $nodelpict = "") {

    if (!is_resource($ftp_stream) ||
        get_resource_type($ftp_stream) !== 'FTP Buffer') {
        return false;
    }

	ftp_pasv($ftp_stream, true);

    $i             = 0;
    $files         = array();
    $statusnext    = false;
    $statusprev    = false;
    $currentfolder = $directory;

    $list = ftp_rawlist($ftp_stream, $directory, true);

    foreach ($list as $current) {

        if (empty($current)) {
			if ($statusprev == true) {
				$statusprev = false;
				continue;
			}
            $statusnext = true;
            continue;
        }

        if ($statusnext === true) {
			$currentfolder = substr($current, 0, -1);
            $statusnext = false;
			$statusprev = true;
            continue;
        }

        $split = preg_split('[ ]', $current, 9, PREG_SPLIT_NO_EMPTY);
        $entry = $split[8];
        $isdir = ($split[0]{0} === 'd') ? true : false;

        if ($entry === '.' || $entry === '..') {
            continue;
        }

        if ($isdir !== true) {
            $files[] = $currentfolder . '/' . $entry;
        }

    }

    foreach ($files as $file) {
		#HUOM: Kuvia joiden nimess‰ on stringi "eipoisteta" ei poisteta
		if (stripos($file, "eipoisteta") === FALSE) {
			if ($nodelpict != '') {
				if (strpos($file, $nodelpict) === FALSE) {
		        	ftp_delete($ftp_stream, $file);
				}
			}
			else {
	        	ftp_delete($ftp_stream, $file);
			}
		}
    }
}

if ($tee == "aja") {

	echo date("H:i:s").": Aloitetaan tuotekuvien siirto.\n";

	// tarvitaan $ftpkuvahost $ftpkuvauser $ftpkuvapass $ftpkuvapath $ftpmuupath
	// palautetaan $palautus ja $syy

	$dummy			= array();
	$syy			= "";
	$poistosyy		= "";
	$palautus		= "";
	$tulos_ulos_ftp	= "";

	if ($ftpkuvahost=='' or $ftpkuvauser=='' or $ftpkuvapass=='' or $ftpkuvapath=='' or $ftpmuupath == '') {
		$tulos_ulos_ftp .= "<font class='error'>".t("L‰hetykseen tarvittavia tietoja puuttuu")."! (host, user, pass, path)</font><br>";
	}
	else {

		//tehd‰‰n kysely t‰ss‰, ettei tule timeouttia
		$query = "	SELECT liitetiedostot.*
					FROM liitetiedostot
					JOIN tuote ON tuote.yhtio = liitetiedostot.yhtio and tuote.hinnastoon = 'W' and tuote.tunnus = liitetiedostot.liitostunnus
					WHERE liitetiedostot.yhtio = '$kyhtio'
					and liitetiedostot.liitos = 'tuote'
					and liitetiedostot.kayttotarkoitus in ('TK','MU')
					ORDER BY liitetiedostot.kayttotarkoitus ASC";
		$result = pupe_query($query);

		//l‰hetet‰‰n tiedosto
		$conn_id = ftp_connect($ftpkuvahost);

		// jos connectio ok, kokeillaan loginata
		if ($conn_id) {
			$login_result = ftp_login($conn_id, $ftpkuvauser, $ftpkuvapass);

			if ($login_result === FALSE) {
				$syy .= "Could not login to remote host ($conn_id, $ftpkuvauser, $ftpkuvapass)\n";
			}
		}
		else {
			$syy .= "Could not connect to remote host. ($ftpkuvahost)\n";
		}

		// jos viimeinen merkki pathiss‰ ei ole kauttaviiva lis‰t‰‰n kauttaviiva...
		if (substr($ftpkuvapath, -1) != "/") {
			 $ftpkuvapath .= "/";
		}

		if (substr($ftpmuupath, -1) != "/") {
			 $ftpmuupath .= "/";
		}

		// jos login ok kokeillaan uploadata
		if ($login_result) {

			$dirri = "/tmp";

			if (!is_writable($dirri)) {
				die("$kokonimi ei ole m‰‰ritelty kirjoitusoikeutta. Ei voida jatkaa!<br>");
			}

			ftp_rmfiles($conn_id, $ftpmuupath);
			ftp_rmfiles($conn_id, $ftpkuvapath, "672x", "kategoria");

			$counter = 0;

			while ($row = mysql_fetch_array($result) and $counter < 10) {

				if ($row["liitos"] == '' or $row["kayttotarkoitus"] == '' or $row["filename"] == '') {
					continue;
				}

				//Laitetaan oikean maan kansioon
				if ($row["kieli"] == "" or $row["kieli"] == "fi") {
					$ftpmuupathlisa = "fi/";
				}
				else {
					$ftpmuupathlisa = $row["kieli"]."/";
				}

				$kokonimi = $dirri;

				$kokonimi .= "/".$row["filename"];

				if (!file_exists($kokonimi)) {
					$handle = fopen("$kokonimi", "x");

					if ($handle === FALSE) {
						$syy .= "Tiedoston $row[filename] luonti ep‰onnistui!\n";
						$counter++;
					}
					else {
						file_put_contents($kokonimi,$row["data"]);
						fclose($handle);

						list($mtype, $crap) = explode("/", $row["filetype"]);

						if ($mtype == "image") {
							$upload = ftp_put($conn_id, $ftpkuvapath.$row["filename"], realpath($kokonimi), FTP_BINARY);
						}
						else {
							$upload = ftp_put($conn_id, $ftpmuupath.$ftpmuupathlisa.$row["filename"], realpath($kokonimi), FTP_BINARY);
						}

						//check upload
						if ($upload === FALSE) {
							if ($mtype == "image") {
								$syy .= "Transfer failed ($conn_id, $ftpkuvapath, ".realpath($kokonimi).")\n";
							}
							else {
								$syy .= "Transfer failed ($conn_id, $ftpmuupath, ".realpath($kokonimi).")\n";
							}

							$counter++;
						}
					}

					//poistetaan filu
					system("rm -f '$kokonimi'");
				}

			}

		}

		if ($conn_id) {
			ftp_close($conn_id);
		}

		if ($poistosyy != "") {
			echo $poistosyy;
		}

		if ($syy != "") {
			echo $syy;
		}

	}

	echo date("H:i:s").": Tuotekuvien siirto valmis.\n\n";

}

if ($tee == "") {
	echo "<font class='head'>".t("Tuotekuvien siirto verkkokauppaan")."</font><hr>";

	echo "<table><form name='uliuli' method='post'>";
	echo "<input type='hidden' name='tee' value='aja'>";
	echo "<tr><td class='back' colspan='2'><br><input type='submit' value='".t("Siirr‰ tuotekuvat")."'></td></tr>";
	echo "</table>";
	echo "</form>";

}

?>