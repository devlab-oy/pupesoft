<?php

	if ($argc == 0) die ("T‰t‰ scripti‰ voi ajaa vain komentorivilt‰!\n");

	require ("inc/salasanat.php");
	require ("inc/functions.inc");

	$tmpfile = "/tmp/orders-tmp.txt";   # minne tehd‰‰n lock file
	$email     = ""; # kenelle meilataan jos on ongelma
	$emailfrom = ""; # mill‰ osoitteella meili l‰hetet‰‰n

	# jos lukkofaili lˆytyy, mutta se on yli 15 minsaa vanha niin dellatan se
	if (@fopen($tmpfile, "r") !== FALSE) {
		$mode = stat($tmpfile);
		$now = mktime(date("H"), date("i"), date("s"), date("m"), date("d"), date("Y"));

		if ($now - $mode[9] > 900) {
			mail($email,  "FTP-get ORDERS HUOM!", "Tilausten sis‰‰nluvussa saattaa olla ongelma. Lukkotiedosto oli yli 15 minuuttia vanha ja se poistettiin. Tutki asia!", "From: <$emailfrom>\n", "-f $emailfrom");

			system("rm -f $tmpfile");
		}
	}

	// tarvitaan $orders_host $orders_user $orders_pass $orders_path
	// palautetaan $syy
	if (@fopen($tmpfile, "r") === FALSE and $orders_host != '' and $orders_user != '' and $orders_pass != '' and $orders_path != '' and $orders_dest != '') {

		if ($filehandle = fopen($tmpfile, "w")) {
			fwrite($filehandle, "All variables are set!\n");
		}

		//l‰hetet‰‰n tiedosto
		$conn_id = ftp_connect($orders_host);

		// jos connectio ok, kokeillaan loginata
		if ($conn_id) {
			fwrite($filehandle, "FTP-connect was successful, trying to login with $orders_user...\n");
			$login_result = ftp_login($conn_id, $orders_user, $orders_pass);
		}

		// jos viimeinen merkki pathiss‰ ei ole kauttaviiva lis‰t‰‰n kauttaviiva...
		if (substr($orders_path, -1) != "/") {
			$orders_path .= "/";
		}

		if ($login_result) {
			fwrite($filehandle, "Login was successful! Trying to change directory to $orders_path...\n");
			$changedir = ftp_chdir($conn_id, $orders_path);
		}

		// haetaan filet active modella
		if ($changedir) {

			if ($debug != '') {
				fwrite($filehandle, "Successfully changed working directory to $orders_path!\n");
				fwrite($filehandle, "Changing to passive mode.\n");
			}

			ftp_pasv($conn_id, true);

			fwrite($filehandle, "Trying to get the file listing...\n");

			$files = ftp_nlist($conn_id, "*.txt");

			if ($files) {

				fwrite($filehandle, "We got some files! Lets loop 'em...\n");

				foreach ($files as $file) {

					fwrite($filehandle, "File $file\n");

					if (substr($orders_dest, -1) != "/") {
						$orders_dest .= "/";
					}

					$fileget = ftp_get($conn_id, $orders_dest.$file, $file, FTP_ASCII);

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

		}

		if ($conn_id) {
			fwrite($filehandle, "Closing ftp-connection...\n");

			ftp_close($conn_id);
		}

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
					$syy = "Could not connect to remote host. ($orders_host)";
					break;
				case  2:
					$syy = "Could not login to remote host ($conn_id, $orders_user, $orders_pass)";
					break;
				case  3:
					$syy = "Changedir failed ($conn_id, $orders_path, ".realpath($orders_path).")";
					break;
				case  4:
					$syy = "Getting files failed ($conn_id, $orders_path)";
					break;
				default:
					$syy = t("Tuntematon errorkoodi")." ($palautus)!!";
			}
		
			fwrite($filehandle, "Error message: $syy\n");
		}

		system("rm -f $tmpfile");
	}

?>
