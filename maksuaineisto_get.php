<?php

	// Kutsutaanko CLI:stä
	$php_cli = FALSE;

	if (php_sapi_name() == 'cli') {
		$php_cli = TRUE;
	}

	date_default_timezone_set('Europe/Helsinki');

	$flock = fopen("/tmp/##pankkiaineisto-haku.lock", "w+");

	if (! @flock($flock, LOCK_EX | LOCK_NB)) {
		if (file_exists("/tmp/##pankkiaineisto-haku.lock") and mktime()-filemtime("/tmp/##pankkiaineisto-haku.lock") >= 300) {
			echo "VIRHE: pankkiaineisto-haku lähetys jumissa! Ota yhteys tekniseen tukeen!!!\n";
			
			// Onko nagios monitor asennettu?
			if (file_exists("/home/nagios/nagios-pupesoft.sh")) {
				file_put_contents("/home/nagios/nagios-pupesoft.log", "VIRHE: pankkiaineisto-haku lähetys jumissa!", FILE_APPEND);
			}
		}
		exit;
	}
	else {
		
		if (!$php_cli) {
			echo "Vain komentoriviltä!";
			exit;
		}
		
		ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__));
		require("inc/salasanat.php");

		if (!isset(	$pankkiaineiston_haku["host"],
					$pankkiaineiston_haku["user"],
					$pankkiaineiston_haku["pass"],
					$pankkiaineiston_haku["tiliote_file"],
					$pankkiaineiston_haku["viite_file"],
					$pankkiaineiston_haku["local_dir"])) {
			echo "pankkiaineiston-haku parametrit puuttuu!\n";
			exit;
		}

		if (!is_writable($pankkiaineiston_haku["local_dir"])) {
			echo "Hakemistossa ongelma!\n";
			exit;
		}

		// Avataan yhteys
		$conn_id = @ftp_connect($pankkiaineiston_haku["host"]);

		if ($conn_id === FALSE) {
			echo "Yhteys epaonnistui {$pankkiaineiston_haku["host"]}!";
			exit;
		}

		$login_result = @ftp_login($conn_id, $pankkiaineiston_haku["user"], $pankkiaineiston_haku["pass"]);

		if ($login_result === FALSE) {
			echo "Login epaonnistui {$pankkiaineiston_haku["host"]}!";
			exit;
		}

		$quote = ftp_site($conn_id, "NAMEFMT 0");

		ftp_pasv($conn_id, true);

		$local_file = $pankkiaineiston_haku["local_dir"]."tiliote-".date("YmdHis").".dat";
		$fileget = @ftp_get($conn_id, $local_file, $pankkiaineiston_haku["tiliote_file"], FTP_ASCII);

		if ($fileget !== FALSE) {
			$quote = ftp_raw($conn_id, "rcmd clrpfm file({$pankkiaineiston_haku["tiliote_file"]})");
		}

		$local_file = $pankkiaineiston_haku["local_dir"]."viite-".date("YmdHis").".dat";
		$fileget = @ftp_get($conn_id, $local_file, $pankkiaineiston_haku["viite_file"], FTP_ASCII);

		if ($fileget !== FALSE) {
			$quote = ftp_raw($conn_id, "rcmd clrpfm file({$pankkiaineiston_haku["viite_file"]})");
		}

		ftp_close($conn_id);
	}
