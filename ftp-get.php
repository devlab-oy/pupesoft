<?php

	if ($argc == 0) die ("Tätä scriptiä voi ajaa vain komentoriviltä!\n");

	require ("inc/salasanat.php");
	require ("inc/functions.inc");

	$syy = "";
	$debug = '';

	// tarvitaan $orders_host $orders_user $orders_pass $orders_path
	// palautetaan $syy
	if ($orders_host != '' and $orders_user != '' and $orders_pass != '' and $orders_path != '' and $orders_dest != '') {

		if ($debug != '') {
			echo "All variables are set!\n";
		}

		//lähetetään tiedosto
		$conn_id = ftp_connect($orders_host);

		// jos connectio ok, kokeillaan loginata
		if ($conn_id) {
			if ($debug != '') {
				echo "FTP-connect was successful, trying to login with $orders_user...\n";
			}
			$login_result = ftp_login($conn_id, $orders_user, $orders_pass);
		}

		// jos viimeinen merkki pathissä ei ole kauttaviiva lisätään kauttaviiva...
		if (substr($orders_path, -1) != "/") {
			$orders_path .= "/";
		}

		if ($login_result) {
			if ($debug != '') {
				echo "Login was successful! Trying to change directory to $orders_path...\n";
			}
			$changedir = ftp_chdir($conn_id, $orders_path);
		}

		// haetaan filet active modella
		if ($changedir) {

			if ($debug != '') {
				echo "Successfully changed working directory to $orders_path!\n";
				echo "Changing to passive mode.\n";
			}

			ftp_pasv($conn_id, true);

			if ($debug != '') {
				echo "Trying to get the file listing...\n";
			}

			$files = ftp_nlist($conn_id, "*.txt");

			if ($files) {

				if ($debug != '') {
					echo "We got some files! Lets loop 'em...\n";
				}

				foreach ($files as $file) {

					if ($debug != '') {
						echo "File $file\n";
					}

					if (substr($orders_dest, -1) != "/") {
						$orders_dest .= "/";
					}

					$fileget = ftp_get($conn_id, $orders_dest.$file, $file, FTP_ASCII);

					if ($fileget) {
						echo "File $file was successfully downloaded!\n";
						if (ftp_delete($conn_id, $file)) {
							echo "File $file was deleted succesfully.\n";
						}
						else {
							echo "Failed to delete file $file.\n";
						}
					}
					else {
						echo "Failed to download file $file!\n";
					}
				}
			}

		}

		if ($conn_id) {
			if ($debug != '') {
				echo "Closing ftp-connection...\n";
			}

			ftp_close($conn_id);
		}

		// mikä feilas?
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

		// jos siirto epäonnistuu
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
		
			echo "Error message: $syy\n";
		}
	}

?>
