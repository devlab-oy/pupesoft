<?php

	// Kutsutaanko CLI:stä
	$php_cli = FALSE;

	if (php_sapi_name() == 'cli') {
		$php_cli = TRUE;
	}

	// jos meillä on lock-file ja se on alle 15 minuuttia vanha
	if (file_exists("/tmp/##kardex-fetch.lock") and mktime()-filemtime("/tmp/##kardex-fetch.lock") < 300) {
		echo "Kardex-fetch sisäänluku käynnissä, odota hetki!";
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

		require('ftp-get.php');

		if ($handle = opendir($ftpget_dest[$operaattori])) {

			while (($file = readdir($handle)) !== FALSE) {

				if (is_file($ftpget_dest[$operaattori]."/".$file)) {

					$_fh = fopen($ftpget_dest[$operaattori]."/".$file, "r+");

					while ($_content = fgets($_fh)) {
						$_content = explode(';', $_content);

						// index 0, tilaustyyppi = aina 4
						// index 1, pakkaus
						// index 2, tuoteno
						// index 3, kpl
						// index 4, tilausrivin tunnus
						// index 5, hyllypaikka
						// index 6, kerääjä

						$_content[4] = (int) $_content[4];

						// haetaan keräyserän numero
						$query = "	SELECT nro
									FROM kerayserat
									WHERE yhtio = '{$kukarow['yhtio']}'
									AND tilausrivi = '{$_content[4]}'";
						$keraysera_nro_res = pupe_query($query);
						$keraysera_nro_row = mysql_fetch_assoc($keraysera_nro_res);

						// haetaan kaikki keräyserän otunnukset
						$query = "	SELECT GROUP_CONCAT(DISTINCT otunnus) AS otunnukset
									FROM kerayserat
									WHERE yhtio = '{$kukarow['yhtio']}'
									AND nro = '{$keraysera_nro_row['nro']}'";
						$otunnukset_res = pupe_query($query);
						$otunnukset_row = mysql_fetch_assoc($otunnukset_res);

						if (trim($otunnukset_row['otunnukset']) != '' and is_numeric($_content[3]) and is_numeric($_content[4]) and $_content[4] > 0) {

							// pitää saada kerääjä selville
							$query = "	SELECT laatija
										FROM kerayserat
										WHERE yhtio = '{$kukarow['yhtio']}'
										AND otunnus IN ({$otunnukset_row['otunnukset']})";
							$laatija_chk_res = pupe_query($query);
							$laatija_chk_row = mysql_fetch_assoc($laatija_chk_res);

							// päivitetään rivi kerätyksi
							$query = "	UPDATE tilausrivi SET
										keratty = '{$laatija_chk_row['laatija']}',
										kerattyaika = now() ,
										varattu = '{$_content[3]}'
										WHERE yhtio = '{$kukarow['yhtio']}'
										AND tunnus = '{$_content[4]}'";
							$upd_res = pupe_query($query);

							$query = "	UPDATE kerayserat SET
										kpl = '{$_content[3]}',
										kpl_keratty = '{$_content[3]}'
										WHERE yhtio = '{$kukarow['yhtio']}'
										AND tilausrivi = '{$_content[4]}'";
							$upd_res = pupe_query($query);

							// onko kaikki keräyserän rivit kerätty
							$query = "	SELECT SUM(IF((tilausrivi.keratty = '' and tilausrivi.kerattyaika = '0000-00-00 00:00:00'), 1, 0)) AS chk
										FROM tilausrivi
										JOIN kerayserat ON (kerayserat.yhtio = tilausrivi.yhtio AND kerayserat.tilausrivi = tilausrivi.tunnus)
										WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
										AND tilausrivi.otunnus IN ({$otunnukset_row['otunnukset']})";
							$chk_res = pupe_query($query);
							$chk_row = mysql_fetch_assoc($chk_res);

							if ($chk_row['chk'] == 0) {

								// päivitetään tilaus kerätyksi
								$query = "UPDATE lasku SET alatila = 'C' WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus IN ({$otunnukset_row['otunnukset']})";
								$upd_res = pupe_query($query);

								// päivitetään keräyserä kerätyksi
								$query = "UPDATE kerayserat SET tila = 'T' WHERE yhtio = '{$kukarow['yhtio']}' AND otunnus IN ({$otunnukset_row['otunnukset']})";
								$upd_res = pupe_query($query);

								if ($yhtiorow['oletus_lahetekpl'] != 0 or $yhtiorow['oletus_oslappkpl'] != 0) {

									// pitää saada keräysvyöhyke selville
									$query = "SELECT keraysvyohyke FROM kuka WHERE yhtio = '{$kukarow['yhtio']}' AND kuka = '{$laatija_chk_row['laatija']}'";
									$keraysvyohyke_chk_res = pupe_query($query);
									$keraysvyohyke_chk_row = mysql_fetch_assoc($keraysvyohyke_chk_res);

									// pitää saada lähete- ja osoitelappu-printterit selville
									$query = "SELECT printteri1, printteri3 FROM keraysvyohyke WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$keraysvyohyke_chk_row['keraysvyohyke']}'";
									$printteri_res = pupe_query($query);
									$printteri_row = mysql_fetch_assoc($printteri_res);

									// loopataan tilaukset
									$query = "SELECT * FROM lasku WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus IN ({$otunnukset_row['otunnukset']})";
									$laskures = pupe_query($query);

									while ($laskurow = mysql_fetch_assoc($laskures)) {

										if ($yhtiorow['oletus_lahetekpl'] > 0 AND $printteri_row['printteri1'] != '') {

											//haetaan lähetteen tulostuskomento
											$query   = "SELECT * from kirjoittimet where yhtio='{$kukarow['yhtio']}' and tunnus='{$printteri_row['printteri1']}'";
											$kirres  = pupe_query($query);
											$kirrow  = mysql_fetch_assoc($kirres);
											$komento = $kirrow['komento'];

											if (($komento != "" and $yhtiorow['oletus_lahetekpl'] > 0) or ($yhtiorow["keraysvahvistus_lahetys"] == "o" and $laskurow['email'] != "")) {

												if ($yhtiorow['oletus_lahetekpl'] > 0 and $komento != 'email') {
													$komento .= " -# $yhtiorow[oletus_lahetekpl] ";
												}

												if ($yhtiorow["keraysvahvistus_lahetys"] == "o" and $laskurow['email'] != "") {
													$komento = array($komento);
													$komento[] = "asiakasemail".$laskurow['email'];
												}

												$params = array(
													'laskurow'					=> $laskurow,
													'sellahetetyyppi' 			=> '',
													'extranet_tilausvahvistus' 	=> '',
													'naytetaanko_rivihinta'		=> '',
													'tee'						=> $tee,
													'toim'						=> $toim,
													'komento' 					=> $komento,
													'kieli' 					=> $kieli
													);

												pupesoft_tulosta_lahete($params);
											}
										}

										if ($yhtiorow['oletus_oslappkpl'] != 0 AND $printteri_row['printteri3'] != '') {
											$valittu_oslapp_tulostin = $printteri_row['printteri3'];
											$oslappkpl = $yhtiorow['oletus_oslappkpl'];

											$tunnus = $laskurow['tunnus'];

											//haetaan osoitelapun tulostuskomento
											$query  = "SELECT * from kirjoittimet where yhtio='{$kukarow['yhtio']}' and tunnus='{$valittu_oslapp_tulostin}'";
											$kirres = pupe_query($query);
											$kirrow = mysql_fetch_assoc($kirres);
											$oslapp = $kirrow['komento'];

											if ($oslappkpl > 1) {
												$oslapp .= " -#$oslappkpl ";
											}

											$query = "SELECT osoitelappu FROM toimitustapa WHERE yhtio = '{$kukarow['yhtio']}' and selite = '{$laskurow['toimitustapa']}'";
											$oslares = pupe_query($query);
											$oslarow = mysql_fetch_assoc($oslares);

											if ($oslarow['osoitelappu'] == 'intrade') {
												require('tilauskasittely/osoitelappu_intrade_pdf.inc');
											}
											else {
												require ("tilauskasittely/osoitelappu_pdf.inc");
											}
										}
									}
								}
							}
						}
					}
				}

				fclose($_fh);

				rename($ftpget_dest[$operaattori]."/".$file, $ftpget_dest[$operaattori]."/ok/".$file);
			}
		}

		closedir($dh);

		unlink("/tmp/##kardex-fetch.lock");
	}
