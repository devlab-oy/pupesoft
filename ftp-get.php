<?php

	// Kutsutaanko CLI:st‰
	if (php_sapi_name() != 'cli') {
		die ("T‰t‰ scripti‰ voi ajaa vain komentorivilt‰!");
	}

	require ("inc/salasanat.php");
	require ("inc/functions.inc");

	//keksit‰‰n uudelle failille joku varmasti uniikki nimi:
	list($usec, $sec) = explode(' ', microtime());
	mt_srand((float) $sec + ((float) $usec * 100000));
	$tmpfile = "/tmp/ftpget-tmp-".md5(uniqid(mt_rand(), true)).".txt";

	if (!isset($ftpget_email)) $ftpget_email = "development@devlab.fi"; 			# kenelle meilataan jos on ongelma
	if (!isset($ftpget_emailfrom)) $ftpget_emailfrom = "development@devlab.fi"; 	# mill‰ osoitteella meili l‰hetet‰‰n

	// ja operaattori komentorivilt‰
	$operaattori = $argv[1];
	// itella, servinet, yms
	// pit‰‰ olla m‰‰ritettyn‰ salasanat.inc:iss‰ tai sitten t‰m‰ menee puihin.

	if ($operaattori == "") {
		mail($ftpget_email,  mb_encode_mimeheader("VIRHE: FTP-get!", "ISO-8859-1", "Q"), "Tilausten sis‰‰nluvussa ongelma, ei operaattoria valittuna. Tutki asia!", "From: ".mb_encode_mimeheader("Pupesoft", "ISO-8859-1", "Q")." <$ftpget_emailfrom>\n", "-f $ftpget_emailfrom");
		die;
	}

	# jos lukkofaili lˆytyy, mutta se on yli 15 minsaa vanha niin dellatan se
	if (@fopen($tmpfile, "r") !== FALSE) {
		$mode = stat($tmpfile);
		$now = mktime(date("H"), date("i"), date("s"), date("m"), date("d"), date("Y"));

		if ($now - $mode[9] > 900) {
			mail($ftpget_email,  mb_encode_mimeheader("VIRHE: FTP-get!", "ISO-8859-1", "Q"), "Tilausten sis‰‰nluvussa saattaa olla ongelma. Lukkotiedosto oli yli 15 minuuttia vanha ja se poistettiin. Tutki asia!", "From: ".mb_encode_mimeheader("Pupesoft", "ISO-8859-1", "Q")." <$ftpget_emailfrom>\n", "-f $ftpget_emailfrom");

			system("rm -f $tmpfile");
		}
	}

	// tarvitaan $ftpget_host $ftpget_user $ftpget_pass $ftpget_path
	// palautetaan $syy
	if (@fopen($tmpfile, "r") === FALSE and $ftpget_host[$operaattori] != '' and $ftpget_user[$operaattori] != '' and $ftpget_pass[$operaattori] != '' and $ftpget_path[$operaattori] != '' and $ftpget_dest[$operaattori] != '') {

		if ($filehandle = fopen($tmpfile, "w")) {
			fwrite($filehandle, "All variables are set!\n");
		}

		//l‰hetet‰‰n tiedosto
		$conn_id = ftp_connect($ftpget_host[$operaattori]);

		// jos connectio ok, kokeillaan loginata
		if ($conn_id) {
			fwrite($filehandle, "FTP-connect was successful, trying to login with $ftpget_user[$operaattori]...\n");
			$login_result = ftp_login($conn_id, $ftpget_user[$operaattori], $ftpget_pass[$operaattori]);
		}

		// jos viimeinen merkki pathiss‰ ei ole kauttaviiva lis‰t‰‰n kauttaviiva...
		if (substr($ftpget_path[$operaattori], -1) != "/") {
			$ftpget_path[$operaattori] .= "/";
		}

		if ($login_result) {
			fwrite($filehandle, "Login was successful! Trying to change directory to $ftpget_path[$operaattori]...\n");
			$changedir = ftp_chdir($conn_id, $ftpget_path[$operaattori]);
		}

		// haetaan filet active modella
		if ($changedir) {

			if (isset($debug) and $debug != '') {
				fwrite($filehandle, "Successfully changed working directory to $ftpget_path[$operaattori]!\n");
				fwrite($filehandle, "Changing to passive mode.\n");
			}

			if (!isset($ftpget_ei_passive) or trim($ftpget_ei_passive) == '') {
				ftp_pasv($conn_id, true);
			}

			fwrite($filehandle, "Trying to get the file listing...\n");

			$files = ftp_nlist($conn_id, ".");

			if ($files) {

				fwrite($filehandle, "We got some files! Lets loop em...\n");

				foreach ($files as $file) {

					fwrite($filehandle, "File $file\n");

					if (substr($ftpget_dest[$operaattori], -1) != "/") {
						$ftpget_dest[$operaattori] .= "/";
					}

					$fileget = ftp_get($conn_id, $ftpget_dest[$operaattori].$file, $file, FTP_ASCII);

					if ($fileget) {
						fwrite($filehandle, "File $file was successfully downloaded!\n");

						if (ftp_delete($conn_id, $file)) {
							fwrite($filehandle, "File $file was deleted succesfully.\n");
						}
						else {
							fwrite($filehandle, "Failed to delete file $file.\n");
						}
					}
					else {
						fwrite($filehandle, "Failed to download file $file!\n");
					}
				}
			}
			else {
				fwrite($filehandle, "We didnt have anyfiles to handle...\n");
			}

		}

		if ($conn_id) {
			fwrite($filehandle, "Closing ftp-connection...\n");

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
		if ($palautus<>0) {

			// ncftpput:in exit valuet
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

			fwrite($filehandle, "Error message: $syy\n");
		}

		unlink($tmpfile);
	}
	else {
		mail($ftpget_email,  mb_encode_mimeheader("VIRHE: FTP-get!", "ISO-8859-1", "Q"), "Tilausten sis‰‰nluvussa saattaa olla ongelma. Jokin tarvittavista tiedoista on v‰‰rin (operaattori: $operaattori)", "From: ".mb_encode_mimeheader("Pupesoft", "ISO-8859-1", "Q")." <$ftpget_emailfrom>\n", "-f $ftpget_emailfrom");

	}
?>