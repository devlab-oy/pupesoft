<?php

	// Kutsutaanko CLI:st‰
	$php_cli = FALSE;

	if (php_sapi_name() == 'cli') {
		$php_cli = TRUE;
	}

	date_default_timezone_set('Europe/Helsinki');

	// jos meill‰ on lock-file ja se on alle 5 minuuttia vanha
	if (file_exists("/tmp/##ftp-get-in.lock") and mktime()-filemtime("/tmp/##ftp-get-in.lock") < 300) {
		#echo "FTP-get sis‰‰nluku ($argv[1]) k‰ynniss‰, odota hetki!";
	}
	elseif (file_exists("/tmp/##ftp-get-in.lock") and mktime()-filemtime("/tmp/##ftp-get-in.lock") >= 300) {
		echo "VIRHE: FTP-get sis‰‰nluku ($argv[1]) jumissa! Ota yhteys tekniseen tukeen!!!";

		// Onko nagios monitor asennettu?
		if (file_exists("/home/nagios/nagios-pupesoft.sh")) {
			file_put_contents("/home/nagios/nagios-pupesoft.log", "VIRHE: FTP-get sis‰‰nluku ($argv[1]) jumissa!", FILE_APPEND);
		}
	}
	else {

		touch("/tmp/##ftp-get-in.lock");

		if ($php_cli) {

			ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__).PATH_SEPARATOR."/usr/share/pear");
			error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
			ini_set("display_errors", 0);

			require ("inc/salasanat.php");
			require ("inc/functions.inc");

			if (!isset($ftpget_email)) $ftpget_email = "development@devlab.fi"; 			# kenelle meilataan jos on ongelma
			if (!isset($ftpget_emailfrom)) $ftpget_emailfrom = "development@devlab.fi"; 	# mill‰ osoitteella meili l‰hetet‰‰n

			// ja operaattori komentorivilt‰
			// itella, servinet, yms
			// pit‰‰ olla m‰‰ritettyn‰ salasanat.inc:iss‰ tai sitten t‰m‰ menee puihin.
			$operaattori = $argv[1];
		}

		if ($operaattori == "") {
			mail($ftpget_email,  mb_encode_mimeheader("VIRHE: FTP-get!", "ISO-8859-1", "Q"), "FTP-get sis‰‰nluvussa ongelma, ei operaattoria valittuna. Tutki asia!", "From: ".mb_encode_mimeheader("Pupesoft", "ISO-8859-1", "Q")." <$ftpget_emailfrom>\n", "-f $ftpget_emailfrom");
			unlink("/tmp/##ftp-get-in.lock");
			exit;
		}

		if ($ftpget_host[$operaattori] != '' and $ftpget_user[$operaattori] != '' and $ftpget_pass[$operaattori] != '' and $ftpget_path[$operaattori] != '' and $ftpget_dest[$operaattori] != '') {

			if (isset($ftpget_port[$operaattori]) and (int) $ftpget_port[$operaattori] > 0) {
				$ftpget_port[$operaattori] = (int) $ftpget_port[$operaattori];

				$conn_id = ftp_connect($ftpget_host[$operaattori], $ftpget_port[$operaattori]);
			}
			else {
				$conn_id = ftp_connect($ftpget_host[$operaattori]);
			}

			// jos connectio ok, kokeillaan loginata
			if ($conn_id) {
				$login_result = ftp_login($conn_id, $ftpget_user[$operaattori], $ftpget_pass[$operaattori]);
			}

			if ($login_result) {
				$changedir = ftp_chdir($conn_id, $ftpget_path[$operaattori]);
			}

			// haetaan filet active modella
			if ($changedir) {

				if (!isset($ftpget_ei_passive) or trim($ftpget_ei_passive) == '') {
					ftp_pasv($conn_id, true);
				}

				$files = ftp_nlist($conn_id, ".");

				if ($files) {
					foreach ($files as $file) {
						$temp_filename = tempnam("/tmp", "ftp");

						$fileget = ftp_get($conn_id, $temp_filename, $file, FTP_ASCII);

						if (filesize($temp_filename) == 0) {
							#echo "VIRHE: Ladattava tiedotsto on tyhj‰!\n";
							unlink($temp_filename);
						}
						elseif ($fileget) {
							rename($temp_filename, $ftpget_dest[$operaattori]."/".$file);
							ftp_delete($conn_id, $file);
						}
						else {
							echo "VIRHE: Tiedoston $file lataus epaonnistui!\n";
							unlink($temp_filename);
						}
					}
				}
			}

			if ($conn_id) {
				ftp_close($conn_id);
			}

			$palautus = 0;

			// mik‰ feilas?
			if ($conn_id === FALSE) {
				$palautus = 1;
			}
			if ($login_result === FALSE) {
				$palautus = 2;
			}
			if ($changedir === FALSE) {
				$palautus = 3;
			}
			if ($files === FALSE) {
				$palautus = 4;
			}

			// jos siirto ep‰onnistuu
			if ($palautus != 0) {
				switch ($palautus) {
					case  1:
						$syy = "Could not connect to remote host. ($ftpget_host[$operaattori])";
						break;
					case  2:
						$syy = "Could not login to remote host ($conn_id, $ftpget_user[$operaattori], $ftpget_pass[$operaattori])";
						break;
					case  3:
						$syy = "Changedir failed ($conn_id, $ftpget_path[$operaattori], ".realpath($ftpget_path[$operaattori]).")";
						break;
					case  4:
						$syy = "Getting files failed ($conn_id, $ftpget_path[$operaattori])";
						break;
					default:
						$syy = t("Tuntematon errorkoodi")." ($palautus)!!";
				}
			}
		}
		else {
			mail($ftpget_email,  mb_encode_mimeheader("VIRHE: FTP-get!", "ISO-8859-1", "Q"), "FTP-get sis‰‰nluvussa saattaa olla ongelma. Jokin tarvittavista tiedoista on v‰‰rin (operaattori: $operaattori)", "From: ".mb_encode_mimeheader("Pupesoft", "ISO-8859-1", "Q")." <$ftpget_emailfrom>\n", "-f $ftpget_emailfrom");
		}

		unlink("/tmp/##ftp-get-in.lock");
	}
?>