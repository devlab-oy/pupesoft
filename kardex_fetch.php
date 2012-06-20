<?php

	// Kutsutaanko CLI:stä
	$php_cli = FALSE;

	if (php_sapi_name() == 'cli') {
		$php_cli = TRUE;
	}

	date_default_timezone_set('Europe/Helsinki');

	// jos meillä on lock-file ja se on alle 5 minuuttia vanha
	if (file_exists("/tmp/##kardex-fetch.lock") and mktime()-filemtime("/tmp/##kardex-fetch.lock") < 300) {
		#echo "Kardex-fetch sisäänluku käynnissä, odota hetki!";
	}
	elseif (file_exists("/tmp/##kardex-fetch.lock") and mktime()-filemtime("/tmp/##kardex-fetch.lock") >= 300) {
		echo "VIRHE: Kardex-fetch sisäänluku jumissa! Ota yhteys tekniseen tukeen!!!";

		// Onko nagios monitor asennettu?
		if (file_exists("/home/nagios/nagios-pupesoft.sh")) {
			file_put_contents("/home/nagios/nagios-pupesoft.log", "VIRHE: Kardex-fetch sisäänluku jumissa!", FILE_APPEND);
		}
	}
	else {

		touch("/tmp/##kardex-fetch.lock");

		if ($php_cli) {

			if (trim($argv[1]) == '') {
				echo "Et antanut yhtiötä!\n";
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

		// Setataan tämä, niin ftp-get.php toimii niin kuin pitäisikin
		$argv[1] = $operaattori;

		require('ftp-get.php');

		if ($kardex_handle = opendir($ftpget_dest[$operaattori])) {

			while (($file = readdir($kardex_handle)) !== FALSE) {

				if (is_file($ftpget_dest[$operaattori]."/".$file)) {

					$kerayserat_array = array();

					$_fh = fopen($ftpget_dest[$operaattori]."/".$file, "r+");

					while ($_content = fgets($_fh)) {
						$_content = explode(';', $_content);

						// index 0, tilaustyyppi = aina 4
						// index 1, sscc ulkoinen
						// index 2, tuoteno
						// index 3, kpl
						// index 4, keräyserän rivin tunnus
						// index 5, hyllypaikka
						// index 6, kerääjä

						$_content[4] = (int) $_content[4];

						$query = "	SELECT nro
									FROM kerayserat
									WHERE yhtio = '{$kukarow['yhtio']}'
									AND tunnus  = '{$_content[4]}'
									AND tila in ('X','K')";
						$nro_chk_res = pupe_query($query);

						if (mysql_num_rows($nro_chk_res) == 1) {
							$nro_chk_row = mysql_fetch_assoc($nro_chk_res);

							$kerayserat_array[$nro_chk_row['nro']] = trim($_content[6]);

							$query = "	UPDATE kerayserat SET
										tila 		= 'K',
										kpl_keratty = '{$_content[3]}',
										keratty 	= '{$kukarow['kuka']}',
										kerattyaika = now()
										WHERE yhtio = '{$kukarow['yhtio']}'
										AND tunnus = '{$_content[4]}'
										AND tila in ('X','K')";
							$upd_res = pupe_query($query);
						}
					}

					if (count($kerayserat_array) > 0) {
						foreach ($kerayserat_array as $keraysera_nro => $keraajalist) {
							// Nollataan muuttujat
							$maara = $kerivi = $rivin_varattu = $rivin_puhdas_tuoteno = $rivin_tuoteno = $vertaus_hylly = $keraysera_maara = array();

							$query = "	SELECT *
										FROM kerayserat
										WHERE yhtio = '{$kukarow['yhtio']}'
										AND tila 	= 'X'
										AND nro 	= '{$keraysera_nro}'";
							$onko_valmis_chk = pupe_query($query);

							if (mysql_num_rows($onko_valmis_chk) == 0) {

								$query = "	SELECT tilausrivi, SUM(kpl) AS kpl, SUM(kpl_keratty) AS kpl_keratty
											FROM kerayserat
											WHERE yhtio = '{$kukarow['yhtio']}'
											AND tila 	= 'K'
											AND nro 	= '{$keraysera_nro}'
											GROUP BY tilausrivi";
								$valmis_era_chk_res = pupe_query($query);

								while ($valmis_era_chk_row = mysql_fetch_assoc($valmis_era_chk_res)) {
									$kerivi[] = $valmis_era_chk_row['tilausrivi'];

									if ($valmis_era_chk_row['kpl'] != $valmis_era_chk_row['kpl_keratty']) {
										$maara[$valmis_era_chk_row['tilausrivi']] = $valmis_era_chk_row['kpl_keratty'];
									}
									else {
										$maara[$valmis_era_chk_row['tilausrivi']] = "";
									}
								}

								$query = "	SELECT *
											FROM kerayserat
											WHERE yhtio = '{$kukarow['yhtio']}'
											AND tila 	= 'K'
											AND nro 	= '{$keraysera_nro}'";
								$valmis_era_chk_res = pupe_query($query);

								$keraysera_vyohyke = 0;

								while ($valmis_era_chk_row = mysql_fetch_assoc($valmis_era_chk_res)) {
									$keraysera_maara[$valmis_era_chk_row['tunnus']] = $valmis_era_chk_row['kpl_keratty'];

									$query = "	SELECT tilausrivi.otunnus, tilausrivi.varattu,
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

									$keraysera_vyohyke = $valmis_era_chk_row["keraysvyohyke"];
								}

								$query = "	SELECT printteri1, printteri3
											FROM keraysvyohyke
											WHERE yhtio = '{$kukarow['yhtio']}'
											AND tunnus = '{$keraysera_vyohyke}'";
								$printteri_res = pupe_query($query);
								$printteri_row = mysql_fetch_assoc($printteri_res);

								// setataan muuttujat keraa.php:ta varten
								$tee 		= "P";
								$toim 		= "";
								$id 		= $keraysera_nro;
								$keraajanro = "";

								// vakadr-tulostin on aina sama kuin lähete-tulostin
								$valittu_tulostin = $vakadr_tulostin = $printteri_row['printteri1'];
								$valittu_oslapp_tulostin = $printteri_row['printteri3'];

								$lahetekpl = $vakadrkpl = $yhtiorow["oletus_lahetekpl"];
								$oslappkpl = $yhtiorow["oletus_oslappkpl"];

								$lasku_yhtio = "";
								$real_submit = "Merkkaa kerätyksi";

								require('tilauskasittely/keraa.php');
							}
						}
					}

					fclose($_fh);
					rename($ftpget_dest[$operaattori]."/".$file, $ftpget_dest[$operaattori]."/ok/".$file);
				}
			}
		}

		closedir($kardex_handle);

		unlink("/tmp/##kardex-fetch.lock");
	}
