<?php

	/* DATA IMPORT CRON LOOP */
	/* Ajetaan cronista ja tämä sisäänlukee luedata-tiedostot datain hakemistosta */

	// Kutsutaanko CLI:stä
	if (php_sapi_name() != 'cli') {
		die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
	}

	// Laitetaan unlimited max time
	ini_set("max_execution_time", 0);

	$data_import_lock_file = "/tmp/data_import.lock";

	// Jos meillä ei ole lukkofileä, voidaan loopata
	if (!file_exists($data_import_lock_file)) {

		// Tehdään lukkofile
		touch($data_import_lock_file);

		$pupe_root_polku = dirname(__FILE__);
		require ("{$pupe_root_polku}/inc/connect.inc");
		require ("{$pupe_root_polku}/inc/functions.inc");

		// Loopataan DATAIN -hakemisto läpi
		if ($handle = opendir($pupe_root_polku."/datain")) {
		    while (false !== ($file = readdir($handle))) {

				// Etsitään "lue-data#" -alkuisia filejä, jotka loppuu ".CSV"
				if (substr($file, 0, 9) == "lue-data#" and substr($file, -4) == ".CSV") {

					// Filename on muotoa: lue-data#username#yhtio#taulu#randombit#jarjestys.CSV
					// Filename on muotoa: lue-data#username#yhtio#taulu#randombit#alkuperainen_filename#jarjestys.CSV
					$filen_tiedot = explode("#", $file);

					// Ei käsitellä jos filename ei ole oikeaa muotoa
					if (count($filen_tiedot) == 7) {

						$kuka = $filen_tiedot[1];
						$yhtio = $filen_tiedot[2];
						$taulu = $filen_tiedot[3];
						$random = $filen_tiedot[4];
						$orig_file = $filen_tiedot[5];
						$jarjestys = $filen_tiedot[6];

						// Logfile on muotoa: lue-data#username#yhtio#taulu#randombit#jarjestys.LOG
						$logfile = "lue-data#{$kuka}#{$yhtio}#{$taulu}#{$random}#{$orig_file}#{$jarjestys}.LOG";

						// Ajetaan lue_data tälle tiedostolle
						passthru("/usr/bin/php ".escapeshellarg($pupe_root_polku."/lue_data.php")." ".escapeshellarg($yhtio)." ".escapeshellarg($taulu)." ".escapeshellarg($pupe_root_polku."/datain/".$file)." ".escapeshellarg($pupe_root_polku."/datain/".$logfile));

						// Siirretään file käsitellyksi
						rename($pupe_root_polku."/datain/".$file, $pupe_root_polku."/datain/".$file.".DONE");
					}
				}
		    }
		    closedir($handle);
		}

		// Poistetaan lukkofile
		unlink($data_import_lock_file);

	}
