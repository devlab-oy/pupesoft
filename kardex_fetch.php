<?php

	// Kutsutaanko CLI:st‰
	$php_cli = FALSE;

	if (php_sapi_name() == 'cli') {
		$php_cli = TRUE;
	}

	// jos meill‰ on lock-file ja se on alle 15 minuuttia vanha
	if (file_exists("/tmp/##kardex-fetch.lock") and mktime()-filemtime("/tmp/##kardex-fetch.lock") < 300) {
		echo "Kardex-fetch sis‰‰nluku k‰ynniss‰, odota hetki!";
	}
	elseif (file_exists("/tmp/##kardex-fetch.lock") and mktime()-filemtime("/tmp/##kardex-fetch.lock") >= 300) {
		echo "VIRHE: Kardex-fetch sis‰‰nluku jumissa! Ota yhteys tekniseen tukeen!!!";

		// Onko nagios monitor asennettu?
		if (file_exists("/home/nagios/nagios-pupesoft.sh")) {
			file_put_contents("/home/nagios/nagios-pupesoft.log", "VIRHE: Kardex-fetch sis‰‰nluku jumissa!", FILE_APPEND);
		}
	}
	else {

		touch("/tmp/##kardex-fetch.lock");

		if ($php_cli) {

			if (trim($argv[1]) == '') {
				echo "Et antanut yhtiˆt‰!\n";
				unlink("/tmp/##kardex-fetch.lock");
				exit;
			}

			// otetaan includepath aina rootista
			ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__).PATH_SEPARATOR."/usr/share/pear");
			error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
			ini_set("display_errors", 0);

			// otetaan tietokanta connect
			require("inc/connect.inc");
			require("inc/functions.inc");

			$kukarow['yhtio'] = (string) $argv[1];
			$kukarow['kuka']  = 'cron';
			$kukarow['kieli'] = 'fi';
			$operaattori 	  = (string) $argv[2];

			$yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);
		}

		if (trim($operaattori) == '') {
			echo "Operaattori puuttuu: kardex!\n";
			unlink("/tmp/##kardex-fetch.lock");
			exit;
		}

		if (trim($ftpget_dest[$operaattori]) == '') {
			echo "Kardex return-kansio puuttuu!\n";
			unlink("/tmp/##kardex-fetch.lock");
			exit;
		}

		if (!is_dir($ftpget_dest[$operaattori])) {
			echo "Kardex return-kansio virheellinen!\n";
			unlink("/tmp/##kardex-fetch.lock");
			exit;
		}

		// Setataan t‰m‰, niin ftp-get.php toimii niin kuin pit‰isikin
		$argv[1] = $operaattori;

		require('ftp-get.php');

		if ($handle = opendir($ftpget_dest[$operaattori])) {

			while (($file = readdir($handle)) !== FALSE) {

				if (is_file($ftpget_dest[$operaattori]."/".$file)) {

					$keraysera_nro = "";
					$maara = $kerivi = $rivin_varattu = $rivin_puhdas_tuoteno = $rivin_tuoteno = $vertaus_hylly = array();

					$_fh = fopen($ftpget_dest[$operaattori]."/".$file, "r+");

					while ($_content = fgets($_fh)) {
						$_content = explode(';', $_content);

						// index 0, tilaustyyppi = aina 4
						// index 1, sscc ulkoinen
						// index 2, tuoteno
						// index 3, kpl
						// index 4, ker‰yser‰n rivin tunnus
						// index 5, hyllypaikka
						// index 6, ker‰‰j‰

						$_content[4] = (int) $_content[4];

						if ($keraysera_nro == "") {
							$query = "	SELECT nro
										FROM kerayserat
										WHERE yhtio = '{$kukarow['yhtio']}'
										AND tunnus = '{$_content[4]}'
										AND tila = 'X'";
							$nro_chk_res = pupe_query($query);
							$nro_chk_row = mysql_fetch_assoc($nro_chk_res);

							$keraysera_nro = $nro_chk_row['nro'];
						}

						$query = "	UPDATE kerayserat SET
									tila = 'K',
									kpl_keratty = '{$_content[3]}'
									WHERE yhtio = '{$kukarow['yhtio']}'
									AND tunnus = '{$_content[4]}'
									AND tila = 'X'";
						$upd_res = pupe_query($query);

						$keraajalist = $_content[6];
					}

					if ($keraysera_nro != "") {
						$query = "	SELECT *
									FROM kerayserat
									WHERE yhtio = '{$kukarow['yhtio']}'
									AND tila = 'X'
									AND nro = '{$keraysera_nro}'";
						$onko_valmis_chk = pupe_query($query);

						if (mysql_num_rows($onko_valmis_chk) == 0) {

							$query = "	SELECT tilausrivi, SUM(kpl_keratty) AS kpl_keratty
										FROM kerayserat
										WHERE yhtio = '{$kukarow['yhtio']}'
										AND tila = 'K'
										AND nro = '{$keraysera_nro}'
										GROUP BY tilausrivi";
							$valmis_era_chk_res = pupe_query($query);

							while ($valmis_era_chk_row = mysql_fetch_assoc($valmis_era_chk_res)) {
								$kerivi[] = $valmis_era_chk_row['tilausrivi'];
								$maara[$valmis_era_chk_row['tilausrivi']] = $valmis_era_chk_row['kpl_keratty'];
							}

							$query = "	SELECT *
										FROM kerayserat
										WHERE yhtio = '{$kukarow['yhtio']}'
										AND tila = 'K'
										AND nro = '{$keraysera_nro}'";
							$valmis_era_chk_res = pupe_query($query);

							while ($valmis_era_chk_row = mysql_fetch_assoc($valmis_era_chk_res)) {
								$keraysera_maara[$valmis_era_chk_row['tunnus']] = $valmis_era_chk_row['kpl_keratty'];

								$query = "	SELECT tilausrivi.varattu, 
											tilausrivi.tuoteno AS puhdas_tuoteno,
											concat_ws(' ',tilausrivi.tuoteno, tilausrivi.nimitys) tuoteno,
											concat_ws('###',tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso) varastopaikka_rekla
											FROM tilausrivi
											WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
											AND tilausrivi.tunnus = '{$valmis_era_chk_row['tilausrivi']}'";
								$varattu_res = pupe_query($query);
								$varattu_row = mysql_fetch_assoc($varattu_res);

								$rivin_varattu[$valmis_era_chk_row['tilausrivi']] = $varattu_row['varattu'];
								$rivin_puhdas_tuoteno[$valmis_era_chk_row['tilausrivi']] = $varattu_row['puhdas_tuoteno'];
								$rivin_tuoteno[$valmis_era_chk_row['tilausrivi']] = $varattu_row['tuoteno'];
								$vertaus_hylly[$valmis_era_chk_row['tilausrivi']] = $varattu_row['varastopaikka_rekla'];
							}

							// setataan muuttujat keraa.php:ta varten
							$tee = "P";
							$toim = "";
							$id = $keraysera_nro_row['nro'];
							$keraajanro = "";
							$valittu_tulostin = "";
							$lahetekpl = 1;
							$valittu_oslapp_tulostin = "";
							$oslappkpl = 1;
							$lasku_yhtio = "";
							$real_submit = "Merkkaa ker‰tyksi";

							require('tilauskasittely/keraa.php');
						}
					}

					fclose($_fh);
					$keraysera_nro = "";
				}

				rename($ftpget_dest[$operaattori]."/".$file, $ftpget_dest[$operaattori]."/ok/".$file);
			}
		}

		closedir($dh);

		unlink("/tmp/##kardex-fetch.lock");
	}
