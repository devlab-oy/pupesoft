<?php

	if (!is_array($erat) and (!isset($ei_tulosteta) or !$ei_tulosteta)) {
		echo "<br /><font class='error'>",t("VIRHE: Keräyserää ei enää löydy"),"!</font><br />";

		require('inc/footer.inc');
		exit;
	}

	$otunnukset = implode(",", $erat['tilaukset']);

	$tilausrivit = '';
	$pakkaus_kirjaimet = array();

	$kerayseran_numero = 0;

	foreach ($erat as $era_key => $era_arr) {

		if ($era_key == 'pakkaukset') {

			if (!isset($lukotetaan) or $lukotetaan) {
				$res = pupe_query("LOCK TABLES kerayserat WRITE, avainsana WRITE");
			}

			$query = "	SELECT ifnull(max(nro), 0) AS nro
						FROM kerayserat
						WHERE yhtio = '{$kukarow['yhtio']}'";
			$nro_fetch_res = pupe_query($query);
			$nro_fetch_row = mysql_fetch_assoc($nro_fetch_res);

			$kerayseran_numero = $nro_fetch_row['nro'] + 1;

			foreach ($era_arr as $pakkaus_nro => $rarr) {
				foreach ($rarr as $juokseva_nro => $pakkaus) {

					$pakkaus_kirjain = chr(64+$juokseva_nro);

					$rivit 		= 0;
					$paino 		= 0;
					$tilavuus 	= 0;
					$kappaleet 	= 0;

					$query = "SELECT selite FROM avainsana WHERE yhtio = '{$kukarow['yhtio']}' AND laji='SSCC'";
					$result = pupe_query($query);
					$row = mysql_fetch_assoc($result);

					$sscc = is_numeric($row['selite']) ? (int) $row['selite'] + 1 : 1;

					if (trim($row['selite']) == '') {

						// haetaan aluksi max perhe
						$query = "	SELECT max(perhe)+1 perhe
									FROM avainsana
									WHERE yhtio = '{$kukarow['yhtio']}'";
						$max_perhe_res = pupe_query($query);
						$max_perhe_row = mysql_fetch_assoc($max_perhe_res);

						$query = "	INSERT INTO avainsana SET
									yhtio 			= '{$kukarow['yhtio']}',
									perhe 			= '{$max_perhe_row['perhe']}',
									kieli 			= '{$kukarow['kieli']}',
									laji 			= 'SSCC',
									nakyvyys 		= '',
									selite 			= '{$sscc}',
									selitetark	 	= '',
									selitetark_2 	= '',
									selitetark_3 	= '',
									jarjestys 		= 0,
									laatija 		= '{$kukarow['kuka']}',
									luontiaika 		= now(),
									muutospvm 		= now(),
									muuttaja 		= '{$kukarow['kuka']}'";
						$insert_res = pupe_query($query);
					}
					else {
						$query = "UPDATE avainsana SET selite = '{$sscc}' WHERE yhtio = '{$kukarow['yhtio']}' AND laji='SSCC'";
						$update_res = pupe_query($query);
					}

					foreach ($pakkaus as $tilriv => $kpl) {
						$pakkaus_kirjaimet[$tilriv][$pakkaus_kirjain] = $kpl;
						$rivit += 1;
						$tilausrivit .= "$tilriv,";
						$kappaleet += $kpl;

						$query = "	SELECT round((tuote.tuotemassa * (tilausrivi.kpl+tilausrivi.varattu)), 2) as paino, round(((tuote.tuoteleveys * tuote.tuotekorkeus * tuote.tuotesyvyys) * (tilausrivi.kpl+tilausrivi.varattu)), 4) as tilavuus, tilausrivi.otunnus
									FROM tilausrivi
									JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
									WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
									AND tilausrivi.tunnus = '{$tilriv}'";
						$paino_res = pupe_query($query);
						$paino_row = mysql_fetch_assoc($paino_res);

						$paino += $paino_row['paino'];
						$tilavuus += $paino_row['tilavuus'];

						$query = "SELECT * FROM lasku WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$paino_row['otunnus']}'";
						$res = pupe_query($query);
						$laskurow = mysql_fetch_assoc($res);

						// tila = 'K' tarkoittaa "Keräyksessä"
						if (!isset($ei_tallenneta) or !$ei_tallenneta) {
							$query = "	INSERT INTO kerayserat SET
										yhtio 		= '{$kukarow['yhtio']}',
										nro 		= {$kerayseran_numero},
										tila 		= 'K',
										sscc 		= {$sscc},
										otunnus 	= '{$laskurow['tunnus']}',
										tilausrivi 	= '{$tilriv}',
										pakkaus 	= '{$pakkaus_nro}',
										pakkausnro 	= '{$juokseva_nro}',
										kpl 		= '{$kpl}',
										laatija 	= '{$kukarow['kuka']}',
										luontiaika 	= now(),
										muuttaja 	= '{$kukarow['kuka']}',
										muutospvm 	= now()";
							$insert_res = pupe_query($query);
						}
					}
				}
			}

			if (!isset($lukotetaan) or $lukotetaan) {
				$res = pupe_query("UNLOCK TABLES");
			}
		}
	}

	if ($kerayseran_numero > 0 and (!isset($ei_tulosteta) or !$ei_tulosteta)) {

		$query = "	SELECT DISTINCT otunnus
					FROM kerayserat
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND nro = '{$kerayseran_numero}'";
		$ker_res = pupe_query($query);

		while ($ker_row = mysql_fetch_assoc($ker_res)) {

			$query = "	SELECT *
						FROM lasku
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tunnus = '{$ker_row['otunnus']}'";
			$res = pupe_query($query);
			$row = mysql_fetch_assoc($res);

			// haetaan toimitustavan tiedot
			$query = "	SELECT *
						FROM toimitustapa
						WHERE yhtio = '$kukarow[yhtio]'
						AND selite = '{$row['toimitustapa']}'";
			$toitares = pupe_query($query);
			$toitarow = mysql_fetch_assoc($toitares);

			$query = "SELECT * FROM maksuehto WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$row['maksuehto']}'";
			$mehto_res = pupe_query($query);
			$mehto = mysql_fetch_assoc($mehto_res);

			$unifaun_kaytossa = FALSE;

			if (($toitarow["rahtikirja"] == 'rahtikirja_unifaun_ps_siirto.inc' and $unifaun_ps_host != "" and $unifaun_ps_user != "" and $unifaun_ps_pass != "" and $unifaun_ps_path != "") OR
				($toitarow["rahtikirja"] == 'rahtikirja_unifaun_uo_siirto.inc' and $unifaun_uo_host != "" and $unifaun_uo_user != "" and $unifaun_uo_pass != "" and $unifaun_uo_path != "")) {

				require("tilauskasittely/unifaun.php");

				$query = "SELECT unifaun_nimi FROM kirjoittimet WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$komento['reittietiketti']}'";
				$kirjoitin_res = pupe_query($query);
				$kirjoitin_row = mysql_fetch_assoc($kirjoitin_res);

				$unifaun_kaytossa = TRUE;
			}

			$query = "	SELECT distinct lasku.ytunnus, lasku.toim_maa, lasku.toim_nimi, lasku.toim_nimitark, lasku.toim_osoite, lasku.toim_ovttunnus, lasku.toim_postino, lasku.toim_postitp,
						lasku.maa, lasku.nimi, lasku.nimitark, lasku.osoite, lasku.ovttunnus, lasku.postino, lasku.postitp,
						if(maksuehto.jv is null,'',maksuehto.jv) jv, lasku.alv, lasku.vienti,
						asiakas.toimitusvahvistus, if(asiakas.gsm != '', asiakas.gsm, if(asiakas.tyopuhelin != '', asiakas.tyopuhelin, if(asiakas.puhelin != '', asiakas.puhelin, ''))) puhelin
						FROM lasku
						JOIN asiakas ON (asiakas.yhtio = lasku.yhtio AND asiakas.tunnus = lasku.liitostunnus)
						JOIN maksuehto on (lasku.yhtio = maksuehto.yhtio and lasku.maksuehto = maksuehto.tunnus)
						WHERE lasku.yhtio = '{$kukarow['yhtio']}'
						AND lasku.tunnus = '{$row['tunnus']}'";
			$rakir_res = pupe_query($query);
			$rakir_row = mysql_fetch_assoc($rakir_res);

			$query = "	SELECT IF(kerayserat.pakkaus = '999', 'KLI', pakkaus.pakkaus) AS pakkauskuvaus,
						IF(kerayserat.pakkaus = '999', 'KLI', pakkaus.pakkauskuvaus) AS kollilaji,
						kerayserat.pakkausnro,
						kerayserat.sscc,
						COUNT(DISTINCT CONCAT(kerayserat.nro,kerayserat.pakkaus,kerayserat.pakkausnro)) AS maara,
						ROUND(SUM(tuote.tuotemassa * tilausrivi.varattu) + IFNULL(pakkaus.oma_paino, 0), 1) tuotemassa
						FROM kerayserat
						JOIN tilausrivi ON (tilausrivi.yhtio = kerayserat.yhtio AND tilausrivi.tunnus = kerayserat.tilausrivi)
						JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
						LEFT JOIN pakkaus ON (pakkaus.yhtio = kerayserat.yhtio AND pakkaus.tunnus = kerayserat.pakkaus)
						WHERE kerayserat.yhtio = '{$kukarow['yhtio']}'
						AND kerayserat.nro = '{$kerayseran_numero}'
						AND kerayserat.otunnus = '{$row['tunnus']}'
						GROUP BY 1,2,3,4";
			$keraysera_res = pupe_query($query);

			while ($keraysera_row = mysql_fetch_assoc($keraysera_res)) {

				if ($unifaun_kaytossa) {
					$row['pakkausid'] = $keraysera_row['pakkausnro'];
					$row['kollilaji'] = $keraysera_row['kollilaji'];
					$row['sscc'] 	  = $keraysera_row['sscc'];

					$row['shipment_unique_id'] = "{$row['tunnus']}_{$row['sscc']}";

					if ($toitarow["rahtikirja"] == 'rahtikirja_unifaun_ps_siirto.inc' and $unifaun_ps_host != "" and $unifaun_ps_user != "" and $unifaun_ps_pass != "" and $unifaun_ps_path != "") {
						$unifaun = new Unifaun($unifaun_ps_host, $unifaun_ps_user, $unifaun_ps_pass, $unifaun_ps_path, $unifaun_ps_port, $unifaun_ps_fail);
					}
					elseif ($toitarow["rahtikirja"] == 'rahtikirja_unifaun_uo_siirto.inc' and $unifaun_uo_host != "" and $unifaun_uo_user != "" and $unifaun_uo_pass != "" and $unifaun_uo_path != "") {
						$unifaun = new Unifaun($unifaun_uo_host, $unifaun_uo_user, $unifaun_uo_pass, $unifaun_uo_path, $unifaun_uo_port, $unifaun_uo_fail);
					}
					else {
						echo t("VIRHE: Unifaun parametrit virheelliset")."!<br>";
						continue;
					}

					$unifaun->setYhtioRow($yhtiorow);
					$unifaun->setKukaRow($kukarow);
					$unifaun->setPostiRow($row);
					$unifaun->setToimitustapaRow($toitarow);
					$unifaun->setMehto($mehto);

					$unifaun->setRahtikirjaRow($rakir_row);

					$unifaun->setYhteensa($row['summa']);
					$unifaun->setViite($row['viesti']);

					$unifaun->setKirjoitin($kirjoitin_row['unifaun_nimi']);

					$unifaun->_getXML();

					$selectlisa   = $keraysera_row['kollilaji'] == 'KLI' ? "tuote.tuoteleveys AS leveys, tuote.tuotekorkeus AS korkeus, tuote.tuotesyvyys AS syvyys" : "pakkaus.leveys, pakkaus.korkeus, pakkaus.syvyys";
					$joinlisa 	  = $keraysera_row['kollilaji'] == 'KLI' ? "" : "JOIN pakkaus ON (pakkaus.yhtio = kerayserat.yhtio AND pakkaus.tunnus = kerayserat.pakkaus)";
					$puukotuslisa = $keraysera_row['kollilaji'] != 'KLI' ? "* IF(pakkaus.puukotuskerroin > 0, pakkaus.puukotuskerroin, 1)" : "";

					$query = "	SELECT tuote.vakkoodi,
								{$selectlisa},
								ROUND(SUM((tuote.tuoteleveys * tuote.tuotekorkeus * tuote.tuotesyvyys * kerayserat.kpl) {$puukotuslisa}), 2) as kuutiot
								FROM kerayserat
								{$joinlisa}
								JOIN tilausrivi ON (tilausrivi.yhtio = kerayserat.yhtio AND tilausrivi.tunnus = kerayserat.tilausrivi)
								JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
								WHERE kerayserat.yhtio = '{$kukarow['yhtio']}'
								AND kerayserat.sscc = '{$keraysera_row['sscc']}'
								GROUP BY 1,2,3,4";
					$pakkaus_info_res = pupe_query($query);
					$pakkaus_info_row = mysql_fetch_assoc($pakkaus_info_res);

					if ($pakkaus_info_row['vakkoodi'] != '') {

						$query = "	SELECT SUM(tuote.tuotemassa) AS paino
									FROM kerayserat
									JOIN tilausrivi ON (tilausrivi.yhtio = kerayserat.yhtio AND tilausrivi.tunnus = kerayserat.tilausrivi)
									JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno AND tuote.vakkoodi != '')
									WHERE kerayserat.yhtio = '{$kukarow['yhtio']}'
									AND kerayserat.sscc = '{$keraysera_row['sscc']}'";
						$paino_chk_res = pupe_query($query);
						$paino_chk_row = mysql_fetch_assoc($paino_chk_res);

						$query = "	SELECT *
									FROM vak
									WHERE yhtio = '{$kukarow['yhtio']}'
									AND tunnus = '{$pakkaus_info_row['vakkoodi']}'";
						$vakkoodi_res = pupe_query($query);
						$vakkoodi_row = mysql_fetch_assoc($vakkoodi_res);

						$vakkoodi = array(
							'yk_nro' => $vakkoodi_row['yk_nro'],
							'nimi_ja_kuvaus' => $vakkoodi_row['nimi_ja_kuvaus'],
							'luokka' => $vakkoodi_row['luokka'],
							'luokituskoodi' => $vakkoodi_row['luokituskoodi'],
							'paino' => $paino_chk_row['paino']
						);
					}
					else {
						$vakkoodi = array();
					}

					$kollitiedot = array(
						'maara' => $keraysera_row['maara'],
						'paino' => $keraysera_row['tuotemassa'],
						'pakkauskuvaus' => $keraysera_row['pakkauskuvaus'],
						'leveys' => $pakkaus_info_row['leveys'],
						'korkeus' => $pakkaus_info_row['korkeus'],
						'syvyys' => $pakkaus_info_row['syvyys'],
						'vakkoodi' => $vakkoodi,
						'kuutiot' => $pakkaus_info_row['kuutiot']
					);

					/* VOIDAAN LÄHETTÄÄ MYÖS MONTA CONTAINER-ROWTA PER SANOMA! YKSI HEADER */
					$unifaun->setContainerRow($kollitiedot);

					// $unifaun->_saveForDebug("shipment");

					$unifaun->ftpSend();
				}
				else {
					// HUOM: Tämä vielää off line, väärät muuttujat $params-arrayssa!!!!
					continue;

					// Tulostetaan Pupen oma kollitarra
					$params = array(
			 			'tilriv' => $tilriv,
			 			'pakkaus_kirjain' => $pakkaus_kirjain,
			 			'sscc' => $sscc,
			 			'toimitustapa' => $laskurow['toimitustapa'],
			 			'rivit' => $rivit,
			 			'paino' => $paino,
			 			'tilavuus' => $tilavuus,
			 			'lask_nimi' => $laskurow['nimi'],
			 			'lask_nimitark' => $laskurow['nimitark'],
			 			'lask_osoite' => $laskurow['osoite'],
			 			'lask_postino' => $laskurow['postino'],
			 			'lask_postitp' => $laskurow['postitp'],
			 			'lask_viite' => $laskurow['viesti'],
			 			'lask_merkki' => $laskurow['sisviesti2'],
			 			'komento_reittietiketti' => $komento['reittietiketti'],
			 		);

				 	tulosta_reittietiketti($params);
				}
			}
		}
	}

	if (!isset($ei_tulosteta) or !$ei_tulosteta) {
		if (trim($komento['reittietiketti']) != '') {
			echo t("Reittietiketti tulostuu"),"...<br />";
		}
		else {
			echo t("Reittietiketin tulostinta ei ole valittu. Reittietiketti ei tulostu"),"...<br />";
		}
	}
