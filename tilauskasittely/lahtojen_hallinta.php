<?php

	ini_set('zlib.output_compression', 1);

	require ("../inc/parametrit.inc");

	$onko_paivitysoikeuksia_ohjelmaan = tarkista_oikeus('tilauskasittely/lahtojen_hallinta.php', '', 1);

	echo "<font class='head'>",t("Lähtöjen hallinta"),"</font><hr>";

	if (isset($man_aloitus) and $onko_paivitysoikeuksia_ohjelmaan) {

		if (isset($checkbox_child) and count($checkbox_child) > 0) {

			foreach ($checkbox_child as $tilausnumero) {

				$tilausnumero = (int) $tilausnumero;

				$query = "UPDATE lasku SET vakisin_kerays = 'X' WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$tilausnumero}'";
				$res = pupe_query($query);
			}
		}
		else {
			echo "<font class='error'>",t("Et valinnut yhtään tilausta"),"!</font><br /><br />";
		}

		// Unsetataan tässä KAIKKI postatut muuttujat paitsi valittu varasto
		if (isset($_REQUEST)) {
			foreach($_REQUEST as $a => $b) {
				if ($a != "select_varasto") unset(${$a});
			}
		}
	}

	if (isset($vaihda_prio) and $onko_paivitysoikeuksia_ohjelmaan) {

		if (isset($checkbox_child) and (is_array($checkbox_child) or is_string(trim($checkbox_child)))) {

			if (isset($uusi_prio) and is_numeric(trim($uusi_prio)) and trim($uusi_prio) > 0) {

				$checkbox_child = unserialize(urldecode($checkbox_child));

				$uusi_prio = (int) $uusi_prio;

				foreach ($checkbox_child as $tilausnumero) {

					$tilausnumero = (int) $tilausnumero;

					$query = "UPDATE lasku SET prioriteettinro = '{$uusi_prio}' WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$tilausnumero}'";
					$res = pupe_query($query);
				}
			}
			else {

				if (isset($uusi_prio) and !is_numeric(trim($uusi_prio))) {
					echo "<font class='error'>",t("Virheellinen prioriteetti"),"!</font><br />";

					$checkbox_child = unserialize(urldecode($checkbox_child));
				}

				if (isset($valittu_lahto)) {
					$valittu_lahto = trim($valittu_lahto);
				}

				echo "<form method='post'>";
				echo "<input type='hidden' name='vaihda_prio' value='X' />";
				echo "<input type='hidden' name='valittu_lahto' id='valittu_lahto' value='{$valittu_lahto}' />";
				echo "<input type='hidden' name='select_varasto' id='select_varasto' value='{$select_varasto}' />";
				echo "<input type='hidden' name='checkbox_child' value='",urlencode(serialize($checkbox_child)),"' />";
				echo "<table>";
				echo "<tr>";
				echo "<th>",t("Anna uusi prio"),"</th>";
				echo "<td><input type='text' name='uusi_prio' value='' size='3' />&nbsp;<input type='submit' value='",t("Vaihda"),"' /></td>";
				echo "</tr>";
				echo "</table>";
				echo "</form>";

				require ("inc/footer.inc");
				exit;
			}
		}
		else {
			echo "<font class='error'>",t("Et valinnut yhtään tilausta"),"!</font><br /><br />";
		}

		// Unsetataan tässä KAIKKI postatut muuttujat paitsi valittu varasto
		if (isset($_REQUEST)) {
			foreach($_REQUEST as $a => $b) {
				if ($a != "select_varasto") unset(${$a});
			}
		}
	}

	if (isset($siirra_lahtoon) and $onko_paivitysoikeuksia_ohjelmaan) {

		if (isset($checkbox_child) and (is_array($checkbox_child) or is_string(trim($checkbox_child)))) {

			if (isset($uusi_lahto) and is_numeric(trim($uusi_lahto)) and trim($uusi_lahto) > 0) {

				$checkbox_child = unserialize(urldecode($checkbox_child));

				$uusi_lahto = (int) $uusi_lahto;

				if (strpos($valittu_lahto, "__") !== false) {
					list($valittu_lahto, $buu) = explode("__", $valittu_lahto);
				}

				// haetaan vanhan lähdön toimitustapa
				$query = "	SELECT lahdot.liitostunnus, toimitustapa.selite, toimitustapa.tulostustapa
							FROM lahdot
							JOIN toimitustapa ON (toimitustapa.yhtio = lahdot.yhtio AND toimitustapa.tunnus = lahdot.liitostunnus)
							WHERE lahdot.yhtio = '{$kukarow['yhtio']}'
							AND lahdot.tunnus  = '{$valittu_lahto}'";
				$old_res = pupe_query($query);
				$old_row = mysql_fetch_assoc($old_res);

				// haetaan uuden lähdön toimitustapa
				$query = "	SELECT lahdot.liitostunnus, toimitustapa.selite, toimitustapa.tulostustapa
							FROM lahdot
							JOIN toimitustapa ON (toimitustapa.yhtio = lahdot.yhtio AND toimitustapa.tunnus = lahdot.liitostunnus)
							WHERE lahdot.yhtio = '{$kukarow['yhtio']}'
							AND lahdot.tunnus  = '{$uusi_lahto}'";
				$new_res = pupe_query($query);
				$new_row = mysql_fetch_assoc($new_res);

				//haetaan kirjoittimen tiedot
				$query = "	SELECT *
							FROM kirjoittimet
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND tunnus  = '{$reittietikettitulostin}'";
				$kirjoitin_res = pupe_query($query);
				$kirjoitin_row = mysql_fetch_assoc($kirjoitin_res);

				$erat = array('tilaukset' => array(), 'pakkaukset' => array());

				// otetaan duplikaatit pois.
				$checkbox_child = array_unique($checkbox_child);

				foreach ($checkbox_child as $tilausnumero) {

					$tilausnumero = (int) $tilausnumero;

					$query = "	SELECT *
								FROM kerayserat
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND otunnus = '{$tilausnumero}'";
					$tila_chk_res = pupe_query($query);
					$tila_chk_row = mysql_fetch_assoc($tila_chk_res);

					// jos keräys on aloitettu tai kerätty
					if ($tila_chk_row['tila'] == 'K' or $tila_chk_row['tila'] == 'T') {

						mysql_data_seek($tila_chk_res, 0);

						$erat['tilaukset'][$tilausnumero] = $tilausnumero;

						while ($tila_chk_row = mysql_fetch_assoc($tila_chk_res)) {
							$erat['pakkaukset'][$tila_chk_row['pakkaus']][$tila_chk_row['pakkausnro']][$tila_chk_row['tilausrivi']] = $tila_chk_row['kpl'];
						}
					}

					if ($old_row['liitostunnus'] != $new_row['liitostunnus']) {
						$query = "	UPDATE rahtikirjat
									SET toimitustapa = '{$new_row['selite']}'
									WHERE yhtio    = '{$kukarow['yhtio']}'
									AND otsikkonro = '{$tilausnumero}'";
						$upd_res = pupe_query($query);
					}

					$query = "	UPDATE lasku
								SET toimitustavan_lahto = '{$uusi_lahto}', toimitustapa = '{$new_row['selite']}'
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tunnus = '{$tilausnumero}'";
					$res = pupe_query($query);
				}

				// palautetaan pointeri alkuun
				reset($checkbox_child);

				$sscc_chk_arr = array();

				require("inc/unifaun_send.inc");

				foreach ($checkbox_child as $tilnro) {

					$query = "	SELECT DISTINCT nro, sscc, sscc_ulkoinen, tila
								FROM kerayserat
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND otunnus = '{$tilnro}'";
					$sscc_chk_res = pupe_query($query);

					while ($sscc_chk_row = mysql_fetch_assoc($sscc_chk_res)) {

						if ($old_row['liitostunnus'] != $new_row['liitostunnus'] and !in_array($sscc_chk_row['sscc_ulkoinen'], $sscc_chk_arr)) {

							// haetaan toimitustavan tiedot
							$query = "	SELECT *
										FROM toimitustapa
										WHERE yhtio = '{$kukarow['yhtio']}'
										AND selite = '{$old_row['selite']}'";
							$toitares = pupe_query($query);
							$toitarow = mysql_fetch_assoc($toitares);

							$query = "	SELECT *
										FROM lasku
										WHERE yhtio = '{$kukarow['yhtio']}'
										AND tunnus = '{$tilnro}'";
							$res = pupe_query($query);
							$row = mysql_fetch_assoc($res);

							$unifaun_kaytossa = FALSE;

							if (($toitarow["rahtikirja"] == 'rahtikirja_unifaun_ps_siirto.inc' and $unifaun_ps_host != "" and $unifaun_ps_user != "" and $unifaun_ps_pass != "" and $unifaun_ps_path != "") OR
									($toitarow["rahtikirja"] == 'rahtikirja_unifaun_uo_siirto.inc' and $unifaun_uo_host != "" and $unifaun_uo_user != "" and $unifaun_uo_pass != "" and $unifaun_uo_path != "")) {
								$unifaun_kaytossa = TRUE;
							}

							if ($toitarow['tulostustapa'] == 'E' and ((is_numeric($era_row['sscc_ulkoinen']) and (int) $era_row['sscc_ulkoinen'] > 0) or (!is_numeric($era_row['sscc_ulkoinen']) and (string) $era_row['sscc_ulkoinen'] != ""))) {
								if ($toitarow["rahtikirja"] == 'rahtikirja_unifaun_ps_siirto.inc' and $unifaun_ps_host != "" and $unifaun_ps_user != "" and $unifaun_ps_pass != "" and $unifaun_ps_path != "") {
									$unifaun = new Unifaun($unifaun_ps_host, $unifaun_ps_user, $unifaun_ps_pass, $unifaun_ps_path, $unifaun_ps_port, $unifaun_ps_fail, $unifaun_ps_succ);
								}
								elseif ($toitarow["rahtikirja"] == 'rahtikirja_unifaun_uo_siirto.inc' and $unifaun_uo_host != "" and $unifaun_uo_user != "" and $unifaun_uo_pass != "" and $unifaun_uo_path != "") {
									$unifaun = new Unifaun($unifaun_uo_host, $unifaun_uo_user, $unifaun_uo_pass, $unifaun_uo_path, $unifaun_uo_port, $unifaun_uo_fail, $unifaun_uo_succ);
								}

								$mergeid = md5($row["toimitustavan_lahto"].$row["ytunnus"].$row["toim_osoite"].$row["toim_postino"].$row["toim_postitp"]);

								$unifaun->setToimitustapaRow($toitarow);
								$unifaun->_discardParcel($mergeid, $sscc_chk_row['sscc_ulkoinen']);
								$unifaun->ftpSend();
							}

							// haetaan toimitustavan tiedot
							$query = "	SELECT *
										FROM toimitustapa
										WHERE yhtio = '$kukarow[yhtio]'
										AND selite = '{$new_row['selite']}'";
							$toitares = pupe_query($query);
							$toitarow = mysql_fetch_assoc($toitares);

							$query = "SELECT * FROM maksuehto WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$row['maksuehto']}'";
							$mehto_res = pupe_query($query);
							$mehto = mysql_fetch_assoc($mehto_res);

							$query = "	SELECT distinct lasku.ytunnus, lasku.toim_maa, lasku.toim_nimi, lasku.toim_nimitark, lasku.toim_osoite, lasku.toim_ovttunnus, lasku.toim_postino, lasku.toim_postitp,
										lasku.maa, lasku.nimi, lasku.nimitark, lasku.osoite, lasku.ovttunnus, lasku.postino, lasku.postitp,
										if(maksuehto.jv is null,'',maksuehto.jv) jv, lasku.alv, lasku.vienti,
										asiakas.toimitusvahvistus, if(asiakas.gsm != '', asiakas.gsm, if(asiakas.tyopuhelin != '', asiakas.tyopuhelin, if(asiakas.puhelin != '', asiakas.puhelin, ''))) puhelin
										FROM lasku
										JOIN asiakas ON (asiakas.yhtio = lasku.yhtio AND asiakas.tunnus = lasku.liitostunnus)
										JOIN maksuehto on (lasku.yhtio = maksuehto.yhtio and lasku.maksuehto = maksuehto.tunnus)
										WHERE lasku.yhtio = '{$kukarow['yhtio']}'
										AND lasku.tunnus  = '{$row['tunnus']}'";
							$rakir_res = pupe_query($query);
							$rakir_row = mysql_fetch_assoc($rakir_res);

							$query = "	SELECT DISTINCT viesti
										FROM rahtikirjat
										WHERE otsikkonro = {$row['tunnus']};";
							$viesti_res = pupe_query($query);

							$tmpviesti = "";
							while ($viesti_row = mysql_fetch_assoc($viesti_res)) {
								$tmpviesti = trim( "$tmpviesti {$viesti_row['viesti']}" );
							}
							$rakir_row['viesti'] = trim("{$rakir_row['viesti']} $tmpviesti");

							$query = "	SELECT IF(kerayserat.pakkaus = '999', 'MUU KOLLI', pakkaus.pakkaus) AS pakkauskuvaus,
										IF(kerayserat.pakkaus = '999', 'MUU KOLLI', pakkaus.pakkauskuvaus) AS kollilaji,
										kerayserat.pakkausnro,
										kerayserat.sscc,
										COUNT(DISTINCT CONCAT(kerayserat.nro,kerayserat.pakkaus,kerayserat.pakkausnro)) AS maara,
										ROUND(SUM(tuote.tuotemassa * tilausrivi.varattu) + IFNULL(pakkaus.oma_paino, 0), 1) tuotemassa
										FROM kerayserat
										JOIN tilausrivi ON (tilausrivi.yhtio = kerayserat.yhtio AND tilausrivi.tunnus = kerayserat.tilausrivi AND tilausrivi.tyyppi != 'D' AND tilausrivi.var not in ('P','J'))
										JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
										LEFT JOIN pakkaus ON (pakkaus.yhtio = kerayserat.yhtio AND pakkaus.tunnus = kerayserat.pakkaus)
										WHERE kerayserat.yhtio 		 = '{$kukarow['yhtio']}'
										AND kerayserat.nro 			 = '{$sscc_chk_row['nro']}'
										AND kerayserat.sscc 		 = '{$sscc_chk_row['sscc']}'
										AND kerayserat.sscc_ulkoinen = '{$sscc_chk_row['sscc_ulkoinen']}'
										GROUP BY 1,2,3,4";
							$keraysera_res = pupe_query($query);

							while ($keraysera_row = mysql_fetch_assoc($keraysera_res)) {

								if ($unifaun_kaytossa) {
									$row['pakkausid'] = $keraysera_row['pakkausnro'];
									$row['kollilaji'] = $keraysera_row['kollilaji'];
									$row['sscc'] = $keraysera_row['sscc'];

									$row['shipment_unique_id'] = "{$row['tunnus']}_{$row['sscc']}";

									if ($toitarow["rahtikirja"] == 'rahtikirja_unifaun_ps_siirto.inc' and $unifaun_ps_host != "" and $unifaun_ps_user != "" and $unifaun_ps_pass != "" and $unifaun_ps_path != "") {
										$unifaun = new Unifaun($unifaun_ps_host, $unifaun_ps_user, $unifaun_ps_pass, $unifaun_ps_path, $unifaun_ps_port, $unifaun_ps_fail, $unifaun_ps_succ);
									}
									elseif ($toitarow["rahtikirja"] == 'rahtikirja_unifaun_uo_siirto.inc' and $unifaun_uo_host != "" and $unifaun_uo_user != "" and $unifaun_uo_pass != "" and $unifaun_uo_path != "") {
										$unifaun = new Unifaun($unifaun_uo_host, $unifaun_uo_user, $unifaun_uo_pass, $unifaun_uo_path, $unifaun_uo_port, $unifaun_uo_fail, $unifaun_uo_succ);
									}

									$unifaun->setYhtioRow($yhtiorow);
									$unifaun->setKukaRow($kukarow);
									$unifaun->setPostiRow($row);
									$unifaun->setToimitustapaRow($toitarow);
									$unifaun->setMehto($mehto);

									$unifaun->setKirjoitin($kirjoitin_row['unifaun_nimi']);

									$unifaun->setRahtikirjaRow($rakir_row);

									$unifaun->setYhteensa($row['summa']);
									$unifaun->setViite($row['viesti']);

									$unifaun->_getXML();

									$selectlisa = $keraysera_row['kollilaji'] == 'MUU KOLLI' ? "tuote.tuoteleveys AS leveys, tuote.tuotekorkeus AS korkeus, tuote.tuotesyvyys AS syvyys" : "pakkaus.leveys, pakkaus.korkeus, pakkaus.syvyys";
									$joinlisa = $keraysera_row['kollilaji'] == 'MUU KOLLI' ? "" : "JOIN pakkaus ON (pakkaus.yhtio = kerayserat.yhtio AND pakkaus.tunnus = kerayserat.pakkaus)";
									$puukotuslisa = $keraysera_row['kollilaji'] != 'MUU KOLLI' ? "* IF(pakkaus.puukotuskerroin > 0, pakkaus.puukotuskerroin, 1)" : "";

									$query = "	SELECT tuote.vakkoodi,
												{$selectlisa},
												ROUND(SUM((tuote.tuoteleveys * tuote.tuotekorkeus * tuote.tuotesyvyys * kerayserat.kpl) {$puukotuslisa}), 2) as kuutiot
												FROM kerayserat
												{$joinlisa}
												JOIN tilausrivi ON (tilausrivi.yhtio = kerayserat.yhtio AND tilausrivi.tunnus = kerayserat.tilausrivi AND tilausrivi.tyyppi != 'D' AND tilausrivi.var not in ('P','J'))
												JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
												WHERE kerayserat.yhtio = '{$kukarow['yhtio']}'
												AND kerayserat.sscc = '{$row['sscc']}'
												GROUP BY 1,2,3,4";
									$pakkaus_info_res = pupe_query($query);
									$pakkaus_info_row = mysql_fetch_assoc($pakkaus_info_res);

									$kollitiedot = array(
										'maara' => $keraysera_row['maara'],
										'paino' => $keraysera_row['tuotemassa'],
										'pakkauskuvaus' => $keraysera_row['pakkauskuvaus'],
										'leveys' => $pakkaus_info_row['leveys'],
										'korkeus' => $pakkaus_info_row['korkeus'],
										'syvyys' => $pakkaus_info_row['syvyys'],
										'vakkoodi' => $pakkaus_info_row['vakkoodi'],
										'kuutiot' => $pakkaus_info_row['kuutiot']
									);

									$unifaun->setContainerRow($kollitiedot);
									$unifaun->ftpSend();
								}
								else {
									$selectlisa   = $keraysera_row['kollilaji'] == 'MUU KOLLI' ? "tuote.tuoteleveys AS leveys, tuote.tuotekorkeus AS korkeus, tuote.tuotesyvyys AS syvyys" : "pakkaus.leveys, pakkaus.korkeus, pakkaus.syvyys";
									$joinlisa 	  = $keraysera_row['kollilaji'] == 'MUU KOLLI' ? "" : "JOIN pakkaus ON (pakkaus.yhtio = kerayserat.yhtio AND pakkaus.tunnus = kerayserat.pakkaus)";
									$puukotuslisa = $keraysera_row['kollilaji'] != 'MUU KOLLI' ? "* IF(pakkaus.puukotuskerroin > 0, pakkaus.puukotuskerroin, 1)" : "";

									$query = "	SELECT {$selectlisa},
												ROUND(SUM(tuote.tuotemassa * kerayserat.kpl) + IFNULL(pakkaus2.oma_paino, 0), 1) tuotemassa,
												ROUND(SUM((tuote.tuoteleveys * tuote.tuotekorkeus * tuote.tuotesyvyys * kerayserat.kpl) {$puukotuslisa}), 2) as kuutiot
												FROM kerayserat
												{$joinlisa}
												JOIN tilausrivi ON (tilausrivi.yhtio = kerayserat.yhtio AND tilausrivi.tunnus = kerayserat.tilausrivi)
												JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
												LEFT JOIN pakkaus AS pakkaus2 ON (pakkaus2.yhtio = kerayserat.yhtio AND pakkaus2.tunnus = kerayserat.pakkaus)
												WHERE kerayserat.yhtio 	= '{$kukarow['yhtio']}'
												AND kerayserat.sscc 	= '{$keraysera_row['sscc']}'
												GROUP BY 1,2,3";
									$pakkaus_info_res = pupe_query($query);
									$pakkaus_info_row = mysql_fetch_assoc($pakkaus_info_res);

									$params = array(
										'tilriv' => $row['tunnus'],//tilausnumero
										'pakkaus_kirjain' => excel_column_name($keraysera_row['pakkausnro']),
										'sscc' => $keraysera_row['sscc'],
										'toimitustapa' => $row['toimitustapa'],
										'rivit' => $keraysera_row['maara'],//tilausrivien määrä
										'paino' => $pakkaus_info_row['tuotemassa'],
										'tilavuus' => $pakkaus_info_row['kuutiot'],
										'lask_nimi' => $rakir_row['toim_nimi'],
										'lask_nimitark' => $rakir_row['toim_nimitark'],
										'lask_osoite' => $rakir_row['toim_osoite'],
										'lask_postino' => $rakir_row['toim_postino'],
										'lask_postitp' => $rakir_row['toim_postitp'],
										'lask_viite' => $row['viesti'],
										'lask_merkki' => $row['ohjausmerkki'],
										'reittietikettitulostin' => $kirjoitin_row['komento'],
									);

									tulosta_reittietiketti($params);
								}
								$sscc_chk_arr[$sscc_chk_row['sscc_ulkoinen']] = $sscc_chk_row['sscc_ulkoinen'];
							}
						}
					}
				}
			}
			else {

				if (isset($uusi_lahto) and !is_numeric(trim($uusi_lahto))) {
					echo "<font class='error'>",t("Virheellinen lähtö"),"!</font><br />";

					$checkbox_child = unserialize(urldecode($checkbox_child));
				}

				$select_varasto = (int) $select_varasto;

				$query = "	SELECT lahdot.tunnus, toimitustapa.selite, lahdot.asiakasluokka, lahdot.pvm, lahdot.lahdon_kellonaika
							FROM lahdot
							JOIN toimitustapa ON (toimitustapa.yhtio = lahdot.yhtio AND toimitustapa.tunnus = lahdot.liitostunnus)
							WHERE lahdot.yhtio = '{$kukarow['yhtio']}'
							AND lahdot.tunnus = '{$valittu_lahto}'";
				$chk_res = pupe_query($query);
				$chk_row = mysql_fetch_assoc($chk_res);

				echo "<br><font class='head'>",t("Nykyinen lähtö"),": ",tv1dateconv($chk_row['pvm'])," {$chk_row['lahdon_kellonaika']} - {$chk_row['asiakasluokka']} - {$chk_row['selite']}</font><br /><br>";

				echo "<form method='post'>";
				echo "<input type='hidden' name='siirra_lahtoon' value='X' />";
				echo "<input type='hidden' name='valittu_lahto' value='{$valittu_lahto}' />";
				echo "<input type='hidden' name='select_varasto' value='{$select_varasto}' />";
				echo "<input type='hidden' name='checkbox_child' value='",urlencode(serialize($checkbox_child)),"' />";
				echo "<table><tr><th>",t("Siirrä lähtöön"),"</th><td>";

				$query = "SELECT asiakasluokka FROM lahdot WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$valittu_lahto}'";
				$asluok_res = pupe_query($query);
				$asluok_row = mysql_fetch_assoc($asluok_res);

				$query = "	SELECT lahdot.tunnus, toimitustapa.selite, lahdot.asiakasluokka, lahdot.pvm, lahdot.lahdon_kellonaika
							FROM lahdot
							JOIN toimitustapa ON (toimitustapa.yhtio = lahdot.yhtio AND toimitustapa.tunnus = lahdot.liitostunnus)
							WHERE lahdot.yhtio = '{$kukarow['yhtio']}'
							AND lahdot.aktiivi IN ('','P','T')
							AND lahdot.tunnus != '{$valittu_lahto}'
							AND lahdot.varasto = '{$select_varasto}'
							AND lahdot.asiakasluokka = '{$asluok_row['asiakasluokka']}'
							AND lahdot.pvm >= CURDATE()
							ORDER BY lahdot.pvm, lahdot.lahdon_kellonaika";
				$lahdot_res = pupe_query($query);

				echo "<select name='uusi_lahto'>";
				echo "<option value=''>",t("Valitse"),"</option>";

				while ($lahdot_row = mysql_fetch_assoc($lahdot_res)) {
					echo "<option value='{$lahdot_row['tunnus']}'>",tv1dateconv($lahdot_row['pvm'])," {$lahdot_row['lahdon_kellonaika']} - {$lahdot_row['asiakasluokka']} - {$lahdot_row['selite']}</option>";
				}

				echo "</select></td></tr>";

				$query = "	SELECT komento, min(kirjoitin) kirjoitin, min(tunnus) tunnus
							FROM kirjoittimet
							WHERE yhtio = '{$kukarow['yhtio']}'
							GROUP BY komento
							ORDER BY kirjoitin";
				$kires = pupe_query($query);

				echo "<tr><th>",t("Valitse tulostin"),":</th>";
				echo "<td><select name='reittietikettitulostin'>";

				while ($kirow = mysql_fetch_assoc($kires)) {

					$sel = (isset($reittietikettitulostin) and $reittietikettitulostin == $kirow['tunnus']) ? " selected" : "";

					echo "<option value='{$kirow['tunnus']}'{$sel}>{$kirow['kirjoitin']}</option>";
				}

				echo "</select>&nbsp;<input type='submit' value='",t("Siirrä"),"' />";
				echo "</td></tr></table></form>";

				require ("inc/footer.inc");
				exit;
			}
		}
		else {
			echo "<font class='error'>",t("Et valinnut yhtään tilausta"),"!</font><br /><br />";
		}

		// Unsetataan tässä KAIKKI postatut muuttujat paitsi valittu varasto
		if (isset($_REQUEST)) {
			foreach($_REQUEST as $a => $b) {
				if ($a != "select_varasto") unset(${$a});
			}
		}
	}

	if (isset($muokkaa_lahto) and $onko_paivitysoikeuksia_ohjelmaan) {

		if (isset($checkbox_parent) and ((is_array($checkbox_parent) and count($checkbox_parent) > 0) or is_string($checkbox_parent))) {

			$virhe = "";

			if (isset($lahto_muokkaus_kellonaika) and trim($lahto_muokkaus_kellonaika) != ''
			and isset($lahto_muokkaus_aloitusaika) and trim($lahto_muokkaus_aloitusaika) != ''
			and isset($lahto_muokkaus_tilausaika) and trim($lahto_muokkaus_tilausaika) != '') {

				$array_chk = array($lahto_muokkaus_kellonaika, $lahto_muokkaus_aloitusaika, $lahto_muokkaus_tilausaika);

				foreach ($array_chk as $klo) {
					$klo_arr = explode(":", $klo);

					if (strpos($klo, " ") !== FALSE or !is_numeric($klo_arr[0]) or !is_numeric($klo_arr[1]) or isset($klo_arr[2])) {
						$virhe = t("Anna kellonaika muodossa hh:mm")."!";
						break;
					}
					elseif ($klo_arr[0] > 23 or $klo_arr[1] > 59 or $klo_arr[0] < 0 or $klo_arr[1] < 0) {
						$virhe = t("Virheellinen kellonaina")."!";
						break;
					}
				}

			}

			if ($virhe == "" and isset($lahto_muokkaus_aktiivi)) {

				$checkbox_parent = unserialize(urldecode($checkbox_parent));

				if (is_array($checkbox_parent) and count($checkbox_parent) > 0) {
					if ($lahto_muokkaus_aktiivi != "P" and $lahto_muokkaus_aktiivi != "E" and $lahto_muokkaus_aktiivi != "T") {
						$lahto_muokkaus_aktiivi = "";
					}

					foreach ($checkbox_parent as $lahto) {

						$lahto = (int) $lahto;

						if ($lahto_muokkaus_aktiivi == "E") {

							$query = "	SELECT tunnus
										FROM lasku
										WHERE yhtio = '{$kukarow['yhtio']}'
										AND toimitustavan_lahto = '{$lahto}'
										AND ((lasku.tila = 'N' AND lasku.alatila = 'A') OR (lasku.tila = 'L' AND lasku.alatila IN ('A','B','C')))";
							$chk_res = pupe_query($query);

							if (mysql_num_rows($chk_res) > 0) {
								echo "<font class='error'>",t("Virhe lähdössä")," {$lahto}! ",t("Lähtö sisältää tilauksia"),". ",t("Sallitut tilat ovat aktiivi ja pysäytetty"),".</font><br />";

								continue;
							}
						}

						$query = "	UPDATE lahdot SET
									lahdon_kellonaika 		= '{$lahto_muokkaus_kellonaika}:00',
									viimeinen_tilausaika 	= '{$lahto_muokkaus_tilausaika}:00',
									kerailyn_aloitusaika 	= '{$lahto_muokkaus_aloitusaika}:00',
									aktiivi 				= '{$lahto_muokkaus_aktiivi}'
									WHERE yhtio = '{$kukarow['yhtio']}'
									AND tunnus = '{$lahto}'";
						$upd_res = pupe_query($query);
					}
				}
				else {
					echo "<font class='error'>",t("Virhe lähdön tallentamisessa"),"!</font><br /><br />";
				}
			}
			else {

				if ($virhe != "") {
					echo "<font class='error'>{$virhe}</font><br /><br />";

					$checkbox_parent = unserialize(urldecode($checkbox_parent));
				}

				$lahto = (int) $checkbox_parent[0];

				$query = "	SELECT SUBSTRING(lahdon_kellonaika, 1, 5) AS 'lahdon_kellonaika',
							SUBSTRING(viimeinen_tilausaika, 1, 5) AS 'viimeinen_tilausaika',
							SUBSTRING(kerailyn_aloitusaika, 1, 5) AS 'kerailyn_aloitusaika',
							aktiivi
							FROM lahdot
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND tunnus  = '{$lahto}'";
				$lahto_res = pupe_query($query);
				$lahto_row = mysql_fetch_assoc($lahto_res);

				echo "<form method='post'>";
				echo "<input type='hidden' name='muokkaa_lahto' value='X' />";
				echo "<input type='hidden' name='select_varasto' value='{$select_varasto}' />";
				echo "<input type='hidden' name='checkbox_parent' value='",urlencode(serialize($checkbox_parent)),"' />";
				echo "<table>";
				echo "<tr><th>",t("Lähtö"),"</th><td>",implode(", ", $checkbox_parent),"</td></tr>";
				echo "<tr><th>",t("Aktiivi"),"</th><td>";
				echo "<select name='lahto_muokkaus_aktiivi'>";

				$sel = array_fill_keys(array($lahto_row['aktiivi']), " selected") + array('' => '', 'P' => '', 'E' => '');

				echo "<option value=''>",t("Aktiivi"),"</option>";
				echo "<option value='P'{$sel['P']}>",t("Pysäytetty"),"</option>";
				echo "<option value='E'{$sel['E']}>",t("Ei aktiivi"),"</option>";
				echo "<option value='T'{$sel['T']}>",t("Aktiivi, ei oteta lisää tilauksia"),"</option>";
				echo "</select>";
				echo "</td></tr>";
				echo "<tr><th>",t("Lähdön kellonaika"),"</th><td><input type='text' name='lahto_muokkaus_kellonaika' value='{$lahto_row['lahdon_kellonaika']}' /></td></tr>";
				echo "<tr><th>",t("Viimeinen tilausaika"),"</th><td><input type='text' name='lahto_muokkaus_tilausaika' value='{$lahto_row['viimeinen_tilausaika']}' /></td></tr>";
				echo "<tr><th>",t("Keräilyn aloitusaika"),"</th><td><input type='text' name='lahto_muokkaus_aloitusaika' value='{$lahto_row['kerailyn_aloitusaika']}' /></td></tr>";
				echo "<tr><td colspan='2' class='back'><input type='submit' value='",t("Tallenna"),"' /></td></tr>";
				echo "</table>";
				echo "</form>";

				require ("inc/footer.inc");
				exit;
			}
		}
		else {
			echo "<font class='error'>",t("Et valinnut yhtään lähtöä"),"!</font><br /><br />";
		}

		// Unsetataan tässä KAIKKI postatut muuttujat paitsi valittu varasto
		if (isset($_REQUEST)) {
			foreach($_REQUEST as $a => $b) {
				if ($a != "select_varasto") unset(${$a});
			}
		}
	}

	if (isset($tulosta_rahtikirjat) and isset($select_varasto) and $select_varasto > 0 and $onko_paivitysoikeuksia_ohjelmaan) {

		if (isset($checkbox_parent) and ((is_array($checkbox_parent) and count($checkbox_parent) > 0) or is_string($checkbox_parent))) {

			if (isset($jv) and (isset($laskukomento) and isset($komento) and isset($valittu_rakiroslapp_tulostin))) {
				$checkbox_parent = unserialize(urldecode($checkbox_parent));

				$select_varasto = (int) $select_varasto;

				require("inc/unifaun_send.inc");

				foreach ($checkbox_parent as $lahto) {

					$sel_ltun = $mergeid_arr = array();

					$lahto = (int) $lahto;

					$query = "	SELECT tunnus, toimitustavan_lahto, toimitustapa, ytunnus, toim_osoite, toim_postino, toim_postitp
								FROM lasku
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tila 	= 'L'
								AND alatila = 'B'
								AND toimitustavan_lahto = '{$lahto}'";
					$result = pupe_query($query);

					$toimitustapa_varasto = "";
					$lahetetaanko_unifaun_era  = FALSE;
					$lahetetaanko_unifaun_heti = FALSE;

					while ($row = mysql_fetch_assoc($result)) {
						$sel_ltun[] = $row['tunnus'];

						$toimitustapa_varasto = $row['toimitustapa']."!!!!".$kukarow['yhtio']."!!!!".$select_varasto;

						$query = "	SELECT *
									FROM toimitustapa
									WHERE yhtio = '{$kukarow['yhtio']}'
									AND selite  = '{$row['toimitustapa']}'";
						$toimitustapa_res = pupe_query($query);
						$toimitustapa_row = mysql_fetch_assoc($toimitustapa_res);

						// Erätulostus
						if (($toimitustapa_row["rahtikirja"] == 'rahtikirja_unifaun_ps_siirto.inc' or $toimitustapa_row["rahtikirja"] == 'rahtikirja_unifaun_uo_siirto.inc') and $toimitustapa_row['tulostustapa'] == 'E') {
							$lahetetaanko_unifaun_era = $toimitustapa_row["rahtikirja"];

							$mergeid = md5($row["toimitustavan_lahto"].$row["ytunnus"].$row["toim_osoite"].$row["toim_postino"].$row["toim_postitp"]);
							$mergeid_arr[$mergeid] = $mergeid;
						}

						// Hetitulostus
						if (($toimitustapa_row["rahtikirja"] == 'rahtikirja_unifaun_ps_siirto.inc' or $toimitustapa_row["rahtikirja"] == 'rahtikirja_unifaun_uo_siirto.inc') and $toimitustapa_row['tulostustapa'] == 'H') {
							$lahetetaanko_unifaun_heti = $toimitustapa_row["rahtikirja"];
						}
					}

					if (count($sel_ltun) > 0) {
						if ($lahetetaanko_unifaun_era !== FALSE) {
							foreach ($mergeid_arr as $mergeid) {

								if ($lahetetaanko_unifaun_era == 'rahtikirja_unifaun_ps_siirto.inc' and $unifaun_ps_host != "" and $unifaun_ps_user != "" and $unifaun_ps_pass != "" and $unifaun_ps_path != "") {
									$unifaun = new Unifaun($unifaun_ps_host, $unifaun_ps_user, $unifaun_ps_pass, $unifaun_ps_path, $unifaun_ps_port, $unifaun_ps_fail, $unifaun_ps_succ);
								}
								elseif ($lahetetaanko_unifaun_era == 'rahtikirja_unifaun_uo_siirto.inc' and $unifaun_uo_host != "" and $unifaun_uo_user != "" and $unifaun_uo_pass != "" and $unifaun_uo_path != "") {
									$unifaun = new Unifaun($unifaun_uo_host, $unifaun_uo_user, $unifaun_uo_pass, $unifaun_uo_path, $unifaun_uo_port, $unifaun_uo_fail, $unifaun_uo_succ);
								}

								$query = "	SELECT unifaun_nimi
											FROM kirjoittimet
											WHERE yhtio = '{$kukarow['yhtio']}'
											AND tunnus  = '{$komento}'";
								$kires = pupe_query($query);
								$kirow = mysql_fetch_assoc($kires);

								$unifaun->_closeWithPrinter($mergeid, $kirow['unifaun_nimi']);
								$unifaun->ftpSend();
							}
						}

						$query = "	UPDATE kerayserat
									SET tila = 'R'
									WHERE yhtio = '{$kukarow['yhtio']}'
									AND otunnus IN (".implode(",", $sel_ltun).")";
						$ures  = pupe_query($query);

						$tee 		= "tulosta";
						$nayta_pdf  = 'foo';
						$tee_varsinainen_tulostus = ($lahetetaanko_unifaun_era !== FALSE or $lahetetaanko_unifaun_heti !== FALSE) ? FALSE : TRUE;

						require ("rahtikirja-tulostus.php");

						$tee = "";
					}
					else {
						echo "<font class='error'>",t("Lähdössä")," {$lahto} ",t("ei ole tulostettavia rahtikirjoja"),"!</font><br /><br />";
					}
				}

				foreach ($checkbox_parent as $lahto) {
					// Siirretään ne tilaukset toiseen lähtöön jotka oli tässä lähdössä, mutta joiden rahtikirjat ei vielä tulostunu.
					$lahto = (int) $lahto;

					$query = "	(SELECT lasku.tunnus, lasku.varasto, lasku.prioriteettinro, lasku.toimitustapa
								FROM lasku
								WHERE lasku.yhtio = '{$kukarow['yhtio']}'
								AND lasku.tila 	  = 'N'
								AND lasku.alatila = 'A'
								AND lasku.toimitustavan_lahto = '{$lahto}')
								UNION
								(SELECT lasku.tunnus, lasku.varasto, lasku.prioriteettinro, lasku.toimitustapa
								FROM lasku
								WHERE lasku.yhtio = '{$kukarow['yhtio']}'
								AND lasku.tila 	  = 'L'
								AND lasku.alatila IN ('A','C')
								AND lasku.toimitustavan_lahto = '{$lahto}')";
					$result = pupe_query($query);

					while ($row = mysql_fetch_assoc($result)) {
						$lahdot = seuraavat_lahtoajat($row['toimitustapa'], $row['prioriteettinro'], $row['varasto'], $lahto);

						if ($lahdot !== FALSE) {
							// Otetaan eka lähtö
							$valitu_lahto = array_shift($lahdot);

							$query = "	UPDATE rahtikirjat
										SET toimitustapa = '{$row['toimitustapa']}'
										WHERE yhtio    = '{$kukarow['yhtio']}'
										AND otsikkonro = '{$row['tunnus']}'";
							$upd_res = pupe_query($query);

							$query = "	UPDATE lasku
										SET toimitustavan_lahto    = '{$valitu_lahto["tunnus"]}',
										toimitustavan_lahto_siirto = '{$lahto}'
										WHERE yhtio = '{$kukarow['yhtio']}'
										AND tunnus  = '{$row['tunnus']}'";
							$upd_res = pupe_query($query);
						}
					}

					$query = "	UPDATE lahdot
								SET aktiivi = 'S'
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tunnus  = '{$lahto}'";
					$upd_res = pupe_query($query);
				}
			}
			else {
				echo "<form method='post'>";
				echo "<input type='hidden' name='tulosta_rahtikirjat' value='X' />";
				echo "<input type='hidden' name='select_varasto' value='{$select_varasto}' />";
				echo "<input type='hidden' name='checkbox_parent' value='",urlencode(serialize($checkbox_parent)),"' />";
				echo "<table>";
				echo "<tr><th>",t("Tulosta kaikki rahtikirjat"),":</th>";
				echo "<td><input type='radio' name='jv' value='' checked></td></tr>";

				echo "<tr><th>",t("Tulosta vain jälkivaatimukset"),":</th>";
				echo "<td><input type='radio' name='jv' value='vainjv'></td></tr>";

				echo "<tr><th>",t("Älä tulosta jälkivaatimuksia"),":</th>";
				echo "<td><input type='radio' name='jv' value='eijv'></td></tr>";

				echo "<tr><th>",t("Tulosta vain rahtikirjoja joilla on VAK-koodeja"),":</th>";
				echo "<td><input type='radio' name='jv' id='jv' value='vainvak'></td></tr>";

				echo "<tr><th>",t("Valitse jälkivaatimuslaskujen tulostuspaikka"),":</th>";
				echo "<td><select id='kirjoitin' name='laskukomento'>";
				echo "<option value=''>",t("Ei kirjoitinta"),"</option>";

				$query = "	SELECT komento, min(kirjoitin) kirjoitin, min(tunnus) tunnus
							FROM kirjoittimet
							WHERE yhtio = '{$kukarow['yhtio']}'
							GROUP BY komento
							ORDER BY kirjoitin";
				$kires = pupe_query($query);

				while ($kirow = mysql_fetch_assoc($kires)) {

					$sel = (isset($laskukomento) and $laskukomento == $kirow['komento']) ? " selected" : "";

					echo "<option value='{$kirow['komento']}'{$sel}>{$kirow['kirjoitin']}</option>";
				}

				echo "</select></td></tr>";

				echo "<tr><th>",t("Valitse tulostin"),":</th>";
				echo "<td><select name='komento'>";

				// Oletustulostin rahtikirjojen tulostukseen. Käytetään oletustulostimena varaston takana olevaa Rahtikirja A4 -tulostinta eli printteri6-kenttää.
				$query = "	SELECT printteri6
							FROM varastopaikat
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND tunnus = '{$select_varasto}'";
				$default_printer_res = pupe_query($query);
				$default_printer_row = mysql_fetch_assoc($default_printer_res);

				if ($default_printer_row['printteri6'] != '') {
					echo "<option value='{$default_printer_row['printteri6']}'>",t("Oletustulostin"),"</option>";
				}

				mysql_data_seek($kires, 0);

				while ($kirow = mysql_fetch_assoc($kires)) {

					$sel = (isset($komento) and $komento == $kirow['tunnus']) ? " selected" : "";

					echo "<option value='{$kirow['tunnus']}'{$sel}>{$kirow['kirjoitin']}</option>";
				}

				echo "</select></td></tr>";

				echo "<tr><th>",t("Tulosta osoitelaput"),"</th>";
				echo "<td><select name='valittu_rakiroslapp_tulostin'>";
				echo "<option value=''>",t("Ei tulosteta"),"</option>";

				mysql_data_seek($kires, 0);

				while ($kirrow = mysql_fetch_assoc($kires)) {

					$sel = (isset($valittu_rakiroslapp_tulostin) and $valittu_rakiroslapp_tulostin == $kirrow['tunnus']) ? " selected" : "";

					echo "<option value='{$kirrow['tunnus']}'{$sel}>{$kirrow['kirjoitin']}</option>";
				}

				echo "</select></td></tr>";

				//tarkistetaan jos lahdon toimitustavan rahtikirjaerittely != ''
				//tällöin näytetään liitettävien lähtöjen dropdownit
				$lahdot_temp = implode(',', $checkbox_parent);
				$query = "	SELECT
							toimitustapa.erittely,
							Group_concat(DISTINCT lasku.tunnus) AS 'tilaukset',
							concat(varastopaikat.nimitys, ' - ', toimitustapa.selite, ' - ', lahdot.pvm, ' - ', Substring(lahdot.lahdon_kellonaika, 1, 5)) AS dropdown_text
							FROM lahdot
							JOIN lasku
							ON ( lasku.yhtio = lahdot.yhtio AND lahdot.tunnus = lasku.toimitustavan_lahto )
							JOIN tilausrivi
							ON ( tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus AND tilausrivi.tyyppi != 'D' AND tilausrivi.var NOT IN ( 'P', 'J' ) )
							JOIN varastopaikat ON (varastopaikat.yhtio = tilausrivi.yhtio
							AND concat(rpad(upper(varastopaikat.alkuhyllyalue), 5, '0'),lpad(upper(varastopaikat.alkuhyllynro), 5, '0')) <= concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'))
							AND concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'),lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0')) )
							JOIN toimitustapa
							ON ( toimitustapa.yhtio = lasku.yhtio AND toimitustapa.selite = lasku.toimitustapa )
							WHERE lahdot.yhtio = '{$kukarow['yhtio']}'
							AND lahdot.tunnus IN ({$lahdot_temp})
							AND ( ( lasku.tila = 'N' AND lasku.alatila = 'A' )
							OR ( lasku.tila = 'L' AND lasku.alatila IN ( 'A', 'B', 'C' ) ) )
							GROUP by lahdot.tunnus";
				$lahdot_result = pupe_query($query);
				$lahdot_joissa_tilauksien_toimitustapa_rahtikirja_eritelty = array();
				while($lahto_row = mysql_fetch_assoc($lahdot_result)) {
					if($lahto_row['erittely'] != '') {
						$lahdot_joissa_tilauksien_toimitustapa_rahtikirja_eritelty[] = array(
							'lahdon_tilauksien_tunnukset' => $lahto_row['tilaukset'],
							'dropdown_text' => $lahto_row['dropdown_text'],
						);
					}
				}

				if(!empty($lahdot_joissa_tilauksien_toimitustapa_rahtikirja_eritelty)) {
					array_unshift($lahdot_joissa_tilauksien_toimitustapa_rahtikirja_eritelty, array('lahdon_tilauksien_tunnukset' => 0 ,'dropdown_text' => 'Valitse lähtö'));
					//tällöin voidaan näyttää mahdolliset liitettävät rahtikirjat
					//haetaanlistaus suljetuista lähdöistä
					$query = "	SELECT
								lahdot.aktiivi,
								Group_concat(DISTINCT lasku.tunnus) AS 'tilaukset',
								concat(varastopaikat.nimitys, ' - ', toimitustapa.selite, ' - ', lahdot.pvm, ' - ', Substring(lahdot.lahdon_kellonaika, 1, 5)) AS dropdown_text
								FROM lahdot
								JOIN lasku
								ON ( lasku.yhtio = lahdot.yhtio AND lahdot.tunnus = lasku.toimitustavan_lahto )
								JOIN tilausrivi
								ON ( tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus AND tilausrivi.tyyppi != 'D' AND tilausrivi.var NOT IN ( 'P', 'J' ) )
								JOIN varastopaikat ON (varastopaikat.yhtio = tilausrivi.yhtio
								AND concat(rpad(upper(varastopaikat.alkuhyllyalue), 5, '0'),lpad(upper(varastopaikat.alkuhyllynro), 5, '0')) <= concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'))
								AND concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'),lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0')) )
								JOIN toimitustapa
								ON ( toimitustapa.yhtio = lasku.yhtio AND toimitustapa.selite = lasku.toimitustapa )
								WHERE  lahdot.yhtio = '{$kukarow['yhtio']}'
								AND lahdot.aktiivi = 'S'
								AND ( ( lasku.tila = 'N' AND lasku.alatila = 'A' )
								OR ( lasku.tila = 'L' AND lasku.alatila IN ( 'A', 'B', 'C' ) ) )
								GROUP BY lahdot.tunnus
								ORDER  BY
								lahdot.pvm,
								lahdot.lahdon_kellonaika,
								lahdot.tunnus";
					$suljetut_lahdot_result = pupe_query($query);

					$suljetut_lahdot = array();
					$suljetut_lahdot[] = array(
						'dropdown_text' => t("Valitse liitettävä lähtö"),
						'tilaukset' => 0,
					);
					while ($suljetut_lahdot_row = mysql_fetch_assoc($suljetut_lahdot_result)) {
						$suljetut_lahdot[] = $suljetut_lahdot_row;
					}

					echo "<tr>";
					echo "<th>".t("Yhdistetään lähtöön")."</th>";
					echo "<td>";
					echo "<select name='yhdistetaan_lahtoon'>";
					foreach($lahdot_joissa_tilauksien_toimitustapa_rahtikirja_eritelty as $lahto) {
						echo "<option value='{$lahto['lahdon_tilauksien_tunnukset']}'>{$lahto['dropdown_text']}</option>";
					}
					echo "</select>";
					echo "</td>";
					echo "</tr>";

					echo "<tr>";
					echo "<th>".t("Yhdistettävä lähtö")."</th>";
					echo "<td>";
					echo "<select name='yhdistettavan_lahdon_tilaukset'>";
					foreach($suljetut_lahdot as $lahto) {
						echo "<option value='{$lahto['tilaukset']}'>{$lahto['dropdown_text']}</option>";
					}
					echo "</select>";
					echo "</td>";
					echo "</tr>";
				}

				echo "<tr><td class='back' colspan='2'>";
				echo "<input type='submit' value='",t("Tulosta rahtikirjat"),"'>";
				echo "</td></tr>";

				echo "</table>";
				echo "</form>";

				require ("inc/footer.inc");
				exit;
			}
		}
		else {
			echo "<font class='error'>",t("Et valinnut yhtään lähtöä"),"!</font><br /><br />";
		}

		// Unsetataan tässä KAIKKI postatut muuttujat paitsi valittu varasto
		if (isset($_REQUEST)) {
			foreach($_REQUEST as $a => $b) {
				if ($a != "select_varasto") unset(${$a});
			}
		}
	}

	echo "	<script type='text/javascript' language='JavaScript'>
				<!--

				$.expr[':'].containsi = function(a,i,m) {
				    return $(a).text().toUpperCase().indexOf(m[3].toUpperCase()) >= 0;
				};

				function profilerWrapper() {
					var prevTime = 0;
					var curTime = 0;
					var diffTime = 0;
					var date = new Date();

					return {
						init: function(txt) {
							console.log(txt);
							prevTime = date.getTime();
						},
						calc: function(txt) {
							curTime = date.getTime();
							diffTime = curTime - prevTime;
							prevTime = curTime;
							console.log(txt + ' (time elapsed: '+diffTime+')');
						}
					}
				}

				$(document).ready(function() {

					$('div.vihrea, div.keltainen, div.punainen, div.sininen').css({
						'width': '15px',
						'height': '15px',
						'margin-left': 'auto',
						'margin-right': 'auto',
						'margin-top': '2px',

						'border-radius': '50%',
						'-webkit-border-radius': '50%',
						'-moz-border-radius': '50%',

						'-webkit-box-shadow': 'inset 0 1px 1px rgba(0, 0, 0, 0.4), 0 1px rgba(200, 200, 200, 0.8)',
						'box-shadow': 'inset 0 1px 1px rgba(0, 0, 0, 0.4), 0 1px rgba(200, 200, 200, 0.8)',
						'-moz-box-shadow': 'inset 0 1px 1px rgba(0, 0, 0, 0.4), 0 1px rgba(200, 200, 200, 0.8)'
					});

					$('div.vihrea').css({
						'background-color': '#5D2'
					});

					$('div.keltainen').css({
						'background-color': '#FCF300'
					});

					$('div.punainen').css({
						'background-color': '#E66'
					});

					$('div.sininen').css({
						'background-color': '#66F'
					});

					// disabloidaan enterin painallus
					$(window).keydown(function(event) {
						if (event.keyCode == 13) {
							event.preventDefault();
							return false;
						}
					});

					// disabloidaan enterin painallus
					$(window).keyup(function(event) {
						if (event.keyCode == 13) {
							event.preventDefault();
							return false;
						}
					});

					var profiler = profilerWrapper();

					// laitetaan jokaiselle TD:lle padding 0, jotta saadaan mahdollisimman paljon tietoa näkyviin samaan aikaan, nowrap kuitenkin jotta tekstit olisivat luettavammassa muodossa
					$('td').css({'padding': '0px', 'white-space': 'nowrap'});

					// tilaustyyppi-kentässä halutaan wrapata teksti, koska ne voivat olla tosi pitkiä
					$('td.toggleable_row_type').css({'white-space': 'pre-wrap'});

					// jos asiakkaan nimi on yli 30 merkkiä pitkä, wrapataan TD
					$('td.toggleable_row_client').each(function() {
						if ($(this).html().length > 30) {
							$(this).css({'white-space': 'pre-wrap'});
						}
					});

					// laitetaan pikkasen paddingia vasemmalle ja oikealle puolelle data ja center sarakkeisiin.
					$('td.center').css({'text-align': 'center', 'padding-left': '7px', 'padding-right': '7px'});
					$('td.data').css({'padding-left': '7px', 'padding-right': '7px', 'padding-bottom': '0px', 'padding-top': '0px'});

					// oletuksena ollaan sortattu 2. tason rivit nousevaan järjestykseen tilausnumeron mukaan
					$('img.row_direction_order').attr('src', '{$palvelin2}pics/lullacons/arrow-double-up-green.png');

					// tehdään 2. tason sorttausnuolista globaalit muuttujat dynaamisesti, jotta muistetaan missä asennoissa ne oli
					$('th.sort_row_by').each(function() {
						var title_sort = this.id.substring(4);

						window['sort_row_direction_'+title_sort] = false;

					});

					// tehdään 1. tason sorttausnuolista globaalit muuttujat dynaamisesti, jotta muistetaan missä asennoissa ne oli
					$('th.sort_parent_row_by').each(function() {
						var title_sort = this.id.substring(11);
						window['sort_parent_row_direction_'+title_sort] = false;
					});

					// nappien click eventti
					$(':checkbox').live('click', function(event){
						event.stopPropagation();

						if (!$(this).hasClass('nayta_valinnat')) $(this).is(':checked') ? $(this).parent().parent().addClass('tumma') : $(this).parent().parent().removeClass('tumma');
					});

					// numeroiden vertailu
					function compareId(a, b) {
						return b.id - a.id;
					}

					// tekstin vertailu
					function compareName(a, b) {
						a = a.id.toString(), b = b.id.toString();
						for (var i = 0, n = Math.max(a.length, b.length); i < n && a.charAt(i) === b.charAt(i); ++i);
						if (i === n) return 0;
						return a.charAt(i) > b.charAt(i) ? -1 : 1;
					}

					function compareDate(a, b) {

						var a_date = a.id.substr(6) + a.id.substr(3, 2) + a.id.substr(0, 2);
						var b_date = b.id.substr(6) + b.id.substr(3, 2) + b.id.substr(0, 2);

						if (b_date > a_date) {
							return 1;
						}
						else if (b_date < a_date) {
							return -1;
						}
						else {
							return 0;
						}
					}

					// uniikit arvot arrayssa
					function sort_unique(arr) {
						arr = arr.sort(function (a, b) { return a*1 - b*1; });
						var ret = [arr[0]];
						for (var i = 1; i < arr.length; i++) { // start loop at 1 as element 0 can never be a duplicate
							if (arr[i-1] !== arr[i]) {
								ret.push(arr[i]);
							}
						}

						return ret;
					}

					// 2. tason checkboxin eventti
					$('input.checkall_child').live('click', function(){

						if ($(this).is(':checked')) {
							$('input:checkbox:visible').filter('input:checkbox:visible:not(input.checkall_parent, input.checkall_child)').attr('checked', true).parent().parent().addClass('tumma');
						}
						else {
							$('input:checkbox:visible').filter('input:checkbox:visible:not(input.checkall_parent, input.checkall_child)').attr('checked', false).parent().parent().removeClass('tumma');
						}
					});

					var column_sort = function(event) {

						if (event.target != this) {
							return true;
						}

						var parent_sort = $(this).hasClass('sort_parent_row_by') ? true : false;

						var title = parent_sort ? this.id.substring(11) : this.id.substring(4);

						if (parent_sort) {

							var arr = $('tr.toggleable_parent:visible');
							var _arr = new Array();

							var _arrChild = new Array();

							var _getId = new Array('departure', 'manual');
							var _sortByNameParent = new Array('delivery', 'time1', 'time2', 'time3', 'manual', 'transfer', 'carrier', 'stopped');
							var _sortByDate = new Array('date');

							for (i = 0; i < arr.length; i++) {
								var row = arr[i];

								var id = $(row).children('td.toggleable_parent_row_'+title).attr('id').replace(/(:|\.)/g,'\\$1');

								if (_getId.indexOf(title) >= 0) {
									var id_temp = id.split(\"__\", 2);
									id = id_temp[0];
									counter = id_temp[1];
								}
								else {
									var id_temp = id.split(\"__\", 3);
									id = id_temp[0];
									counter = id_temp[2];
								}

								_arr.push({
									'id': id,
									'row': row,
									'counter': counter,
									'link': id_temp[0]
								});

								_arrChild.push({
									'id': id,
									'row': $('#toggleable_tr_'+id_temp[0]+'__'+counter),
									'counter': counter
								});
							}

							$('tr.toggleable_parent:visible').remove();

							for (i = 0; i < _arr.length; i++) {
								$('#toggleable_tr_'+_arr[i].link+'__'+_arr[i].counter).remove();
							}

							if (window['sort_parent_row_direction_'+title]) {

								if (_sortByNameParent.indexOf(title) >= 0) {
									_arr.sort(compareName);
									_arrChild.sort(compareName);
								}
								else if (_sortByDate.indexOf(title) >= 0) {
									_arr.sort(compareDate);
									_arrChild.sort(compareDate);
								}
								else {
									_arr.sort(compareId);
									_arrChild.sort(compareId);
								}

								var length = _arr.length;
								var header_parent = $('#header_parent');

								for (i = 0; i < length; i++) {
									header_parent.after(_arr[i].row, _arrChild[i].row);
								}

								$('img.parent_row_direction_'+title).attr('src', '{$palvelin2}pics/lullacons/arrow-double-up-green.png').show();
								$('th.sort_parent_row_by').children('img[class!=\"parent_row_direction_'+title+'\"]').hide();

								window['sort_parent_row_direction_'+title] = false;
							}
							else {

								if (_sortByNameParent.indexOf(title) >= 0) {
									_arr.sort(compareName).reverse();
									_arrChild.sort(compareName).reverse();
								}
								else if (_sortByDate.indexOf(title) >= 0) {
									_arr.sort(compareDate).reverse();
									_arrChild.sort(compareDate).reverse();
								}
								else {
									_arr.sort(compareId).reverse();
									_arrChild.sort(compareId).reverse();
								}

								var length = _arr.length;
								var header_parent = $('#header_parent');

								for (i = 0; i < length; i++) {
									header_parent.after(_arr[i].row, _arrChild[i].row);
								}

								$('img.parent_row_direction_'+title).attr('src', '{$palvelin2}pics/lullacons/arrow-double-down-green.png').show();
								$('th.sort_parent_row_by').children('img[class!=\"parent_row_direction_'+title+'\"]').hide();

								window['sort_parent_row_direction_'+title] = true;
							}

						}
						else {

							var tmp = title.split(\"__\", 3);
							title = tmp[0];
							title_id = tmp[1];
							title_counter = tmp[2];

							var arr = $('tr.toggleable_row_tr:visible');
							var _arr = new Array();

							var _arrChildOrder = new Array();
							var _arrChildSscc = new Array();

							var _getId = new Array('status', 'prio', 'client', 'locality', 'picking_zone', 'batch', 'sscc', 'package', 'weight', 'type', 'clientprio', 'manual', 'control', 'transfer');
							var _sortByNameChild = new Array('client', 'locality', 'picking_zone', 'package', 'type', 'clientprio', 'manual', 'control', 'transfer');

							for (i = 0; i < arr.length; i++) {
								var row = arr[i];

								var id = $(row).children('td.toggleable_row_'+title).attr('id');
								var counter = 0;

								if (_getId.indexOf(title) >= 0) {
									var id_temp = id.split(\"__\", 3);
									id = id_temp[0];
									counter = id_temp[2];
								}
								else {
									var id_temp = id.split(\"__\", 2);
									id = id_temp[0];
									counter = id_temp[1];
								}

								_arr.push({
									'id': id,
									'row': row,
									'counter': counter
								});

								_arrChildOrder.push({
									'id': id,
									'row': $('#toggleable_row_child_order_'+id+'__'+counter),
									'counter': counter
								});

								_arrChildSscc.push({
									'id': id,
									'row': $('#toggleable_row_child_sscc_'+id+'__'+counter),
									'counter': counter
								});
							}

							$('tr.toggleable_row_tr:visible').remove();

							for (i = 0; i < _arr.length; i++) {
								$('#toggleable_row_child_order_'+_arr[i].id+'__'+_arr[i].counter).remove();
								$('#toggleable_row_child_sscc_'+_arr[i].id+'__'+_arr[i].counter).remove();
							}

							var header_id = $('tr.toggleable_tr:visible').attr('id').substring(14).split(\"__\", 2);

							if (window['sort_row_direction_'+title+'__'+title_id+'__'+title_counter]) {

								if (_sortByNameChild.indexOf(title) >= 0) {
									_arr.sort(compareName);
									_arrChildOrder.sort(compareName);
									_arrChildSscc.sort(compareName);
								}
								else {
									_arr.sort(compareId);
									_arrChildOrder.sort(compareId);
									_arrChildSscc.sort(compareId);
								}

								var length = _arr.length;
								var header_row = $('#header_row_'+header_id[0]+'__'+header_id[1]);

								for (i = 0; i < length; i++) {
									header_row.after(_arr[i].row, _arrChildOrder[i].row, _arrChildSscc[i].row);
								}

								$('img.row_direction_'+title).attr('src', '{$palvelin2}pics/lullacons/arrow-double-up-green.png').show();
								$('th.sort_row_by').children('img[class!=\"row_direction_'+title+'\"]').hide();

								window['sort_row_direction_'+title+'__'+title_id+'__'+title_counter] = false;
							}
							else {

								if (_sortByNameChild.indexOf(title) >= 0) {
									_arr.sort(compareName).reverse();
									_arrChildOrder.sort(compareName).reverse();
									_arrChildSscc.sort(compareName).reverse();
								}
								else {
									_arr.sort(compareId).reverse();
									_arrChildOrder.sort(compareId).reverse();
									_arrChildSscc.sort(compareId).reverse();
								}

								var length = _arr.length;
								var header_row = $('#header_row_'+header_id[0]+'__'+header_id[1]);

								for (i = 0; i < length; i++) {
									header_row.after(_arr[i].row, _arrChildOrder[i].row, _arrChildSscc[i].row);
								}

								$('img.row_direction_'+title).attr('src', '{$palvelin2}pics/lullacons/arrow-double-down-green.png').show();
								$('th.sort_row_by').children('img[class!=\"row_direction_'+title+'\"]').hide();

								window['sort_row_direction_'+title+'__'+title_id+'__'+title_counter] = true;
							}
						}
					};

					// 1. ja 2. tason sarakkeiden sorttaus
					$('th.sort_parent_row_by').live('click', column_sort);
					$('th.sort_row_by').on('click', column_sort);

					// 2. tason alasvetovalikolla filteröinti
					$('select.filter_row_by_select').live('change', function(event) {

						event.stopPropagation();

						$('tr.toggleable_row_tr').hide();

						var empty_all = true;

						var selected = '';

						$('select.filter_row_by_select option:selected').each(function() {

							if ($(this).val() != '') {
								empty_all = false;

								var title = $(this).parent().attr('id').substring(17);

								if (selected != '') {
									selected = $(selected).children().filter('td[id^=\"'+$(this).val().replace(/(:|\.)/g,'\\$1')+'\"][class~=\"toggleable_row_'+title+'\"]').parent();
								}
								else {
									selected = $('td[id^=\"'+$(this).val().replace(/(:|\.)/g,'\\$1')+'\"][class~=\"toggleable_row_'+title+'\"]').parent();
								}
							}
						});

						if (empty_all) {
							$('tr.toggleable_row_tr').show();
						}
						else {
							$(selected).show();
						}
					});

					// 2. tason tekstikentällä rajaaminen
					$('input.filter_row_by_text').live('keyup', function(event) {

						// ei ajeta listaa jos painetaan nuolinäppäimiä
						if (event.keyCode == 37 || event.keyCode == 38 || event.keyCode == 39 || event.keyCode == 40) {
							event.preventDefault();
							return false;
						}

						$('tr.toggleable_row_tr').hide();

						var empty_all = true;

						var selectedx = '';

						$('select.filter_row_by_select option:selected').each(function() {

							if ($(this).val() != '') {
								empty_all = false;

								var title = $(this).parent().attr('id').substring(17);

								if (selectedx != '') {
									selectedx = $(selectedx).children().filter('td[id^=\"'+$(this).val().replace(/(:|\.)/g,'\\$1')+'\"][class~=\"toggleable_row_'+title+'\"]').parent();
								}
								else {
									selectedx = $('td[id^=\"'+$(this).val().replace(/(:|\.)/g,'\\$1')+'\"][class~=\"toggleable_row_'+title+'\"]').parent();
								}
							}
						});

						$('input.filter_row_by_text').each(function() {

							if (selectedx != '' && $(this).val() != '') {
								var tmp = $(this).attr('id').substring(15).split(\"__\", 3);
								var title = tmp[0];
								selectedx = $(selectedx).children().filter('.toggleable_row_'+title+':containsi(\"'+$(this).val().replace(/(:|\.)/g,'\\$1')+'\")').parent();

								if (selectedx != '') {
									empty_all = false;
								}
							}
							else if (selectedx == '' && $(this).val() != '') {
								var tmp = $(this).attr('id').substring(15).split(\"__\", 3);
								var title = tmp[0];
								selectedx = $('.toggleable_row_'+title+':containsi(\"'+$(this).val().replace(/(:|\.)/g,'\\$1')+'\")').parent();

								if (selectedx != '') {
									empty_all = false;
								}
							}
							else if (selectedx != '' && $(this).val() == '') {
								empty_all = false;
							}
							else {
								//$('tr.toggleable_row_tr').show();
								//return true;
								// empty_all = true;
							}
						});

						if (empty_all) {
							$('tr.toggleable_row_tr').show();
						}
						else {
							$(selectedx).show();
						}
					});

					$('#uni_client_search, #uni_order_search, #uni_locality_search').keyup(function(event) {

						// ei ajeta listaa jos painetaan nuolinäppäimiä
						if (event.keyCode == 9 || event.keyCode == 37 || event.keyCode == 38 || event.keyCode == 39 || event.keyCode == 40 || event.keyCode == 16) {
							event.preventDefault();
							return false;
						}

						if ($(this).val() == undefined) {
							return false;
						}

						var title = $(this).attr('class');

						if (title == 'client' || title == 'locality') {
							$('#uni_order_search').val('');
						}
						else {
							$('#uni_client_search, #uni_locality_search').val('');
						}

						$('tr.toggleable_row_tr').hide();
						$('tr.toggleable_tr').hide();
						$('tr.toggleable_parent').hide();

						var empty_all = true;

						var selectedx = new Array();
						var selectedxChild = new Array();

						if (title == 'client' || title == 'locality') {

							var _tmp = new Array();
							var _tmpChild = new Array();

							$('#uni_client_search, #uni_locality_search').each(function(indx, dom) {

								if ($(dom).val() != '') {

									var title = $(dom).attr('class');

									if (selectedx.length > 0) {

										for (var i = 0; i < selectedx.length; i++) {

											if (selectedxChild[i].children('.toggleable_row_'+title+':containsi(\"'+$(dom).val().replace(/(:|\.)/g,'\\$1')+'\")').length > 0) {
												_tmp[_tmp.length] = selectedx[i];
												_tmpChild[_tmpChild.length] = selectedxChild[i];
											}

										}

										empty_all = false;

										selectedx = _tmp;
										selectedxChild = _tmpChild;
									}
									else {

										var i = 0;

										$('.toggleable_row_'+title+':containsi(\"'+$(dom).val().replace(/(:|\.)/g,'\\$1')+'\")').each(function(indexChild, domChild) {

											var id = $(domChild).parent().attr('id');

											if (id != undefined) {
												selectedxChild[i] = $('#'+id);
												selectedx[i] = $('#toggleable_parent_'+id.replace('child_', ''));
												i++;
											}
										});

										empty_all = false;
									}
								}

							});
						}
						else {

							if ($(this).val() != '') {
								var i = 0;

								$('.toggleable_row_'+title+':containsi(\"'+$(this).val().replace(/(:|\.)/g,'\\$1')+'\")').each(function(indexChild, domChild) {

									var id = $(domChild).parent().attr('id');

									if (id != undefined) {
										selectedxChild[i] = $('#'+id);
										selectedx[i] = $('#toggleable_parent_'+id.replace('child_', ''));
										i++;
									}
								});

								empty_all = false;
							}

						}

						if (empty_all) {
							$('tr.toggleable_tr').show();
							$('tr.toggleable_parent').show();
						}
						else {
							for (i = 0; i < selectedx.length; i++) {
								$(selectedx[i]).show();
							}
						}

					});

					$('tr[id^=\"toggleable_row_child_\"]:visible').hide();

					// 2. tason tilausnumeronapin eventti
					$('td.toggleable_row_order').live('click', function() {

						var parent = $(this).parent().parent().parent().parent();
						var parent_id = $(parent).attr('id');

						var id = this.id.split(\"__\", 2);

						var toggleable_row_order = $('#toggleable_row_order_'+id[0]+'__'+id[1]);

						if (toggleable_row_order.is(':visible')) {

							$(this).removeClass('tumma');

							toggleable_row_order.slideUp('fast');
							toggleable_row_order.parent().hide().parent().hide();

							if ($('div.toggleable_row_child_div_order:visible, div.toggleable_row_child_div_sscc:visible').length == 0) {

								$('#'+parent_id).children().children().children('tr[id!=\"toggleable_row_tr_'+id[0]+'__'+id[1]+'\"][class=\"toggleable_row_tr\"]').show();

								var text_search = false;

								$('input.filter_row_by_text:visible').attr('disabled', false).each(function() {
									if ($(this).val() != '') {
										$(this).trigger('keyup');
										text_search = true;
									}
								});

								$('select.filter_row_by_select:visible').attr('disabled', false);

								if (!text_search) {
									$('select.filter_row_by_select:visible').trigger('change');
								}

								$('th.sort_row_by:visible').on('click', column_sort);
							}
						}
						else {

							$('select.filter_row_by_select:visible, input.filter_row_by_text:visible').attr('disabled', true);

							$('th.sort_row_by:visible').off();

							$(this).addClass('tumma');

							var parent_element = toggleable_row_order.parent();

							var children = $('#'+parent_id).children().children();

							children.children('tr[id!=\"toggleable_row_tr_'+id[0]+'__'+id[1]+'\"][class=\"toggleable_row_tr\"]').hide();
							children.children('tr[id!=\"toggleable_row_child_order_'+id[0]+'__'+id[1]+'\"][class^=\"toggleable_row_child_order_\"]').hide();

							parent_element.parent().show();
							parent_element.show();
							toggleable_row_order.css({'width': parent_element.width()+'px', 'padding-bottom': '15px'}).delay(1).slideDown('fast');
						}
					});

					// 2. tason sscc-napin eventti
					$('td.toggleable_row_sscc').live('click', function() {

						if ($(this).html() != '') {

							var parent = $(this).parent().parent().parent().parent();
							var parent_id = $(parent).attr('id');
							var id = this.id.split(\"__\", 3);
							var toggleable_row_sscc = $('#toggleable_row_sscc_'+id[0]+'__'+id[2]);

							if (toggleable_row_sscc.is(':visible')) {
								$(this).removeClass('tumma');

								toggleable_row_sscc.slideUp('fast');
								toggleable_row_sscc.parent().hide().parent().hide();

								if ($('div.toggleable_row_child_div_order:visible, div.toggleable_row_child_div_sscc:visible').length == 0) {

									$('#'+parent_id).children().children().children('tr[id!=\"toggleable_row_tr_'+id[1]+'__'+id[2]+'\"][class=\"toggleable_row_tr\"]').show();

									var text_search = false;

									$('input.filter_row_by_text:visible').attr('disabled', false).each(function() {
										if ($(this).val() != '') {
											$(this).trigger('keyup');
											text_search = true;
										}
									});

									$('select.filter_row_by_select:visible').attr('disabled', false);

									if (!text_search) {
										$('select.filter_row_by_select:visible').trigger('change');
									}

									$('th.sort_row_by:visible').on('click', column_sort);
								}
							}
							else {

								$('select.filter_row_by_select:visible, input.filter_row_by_text:visible').attr('disabled', true);

								$('th.sort_row_by:visible').off();

								$(this).addClass('tumma');

								var parent_element = toggleable_row_sscc.parent();

								var children = $('#'+parent_id).children().children();

								children.children('tr[id!=\"toggleable_row_tr_'+id[1]+'__'+id[2]+'\"][class=\"toggleable_row_tr\"]').hide();
								children.children('tr[id!=\"toggleable_row_child_sscc_'+id[0]+'__'+id[2]+'\"][class^=\"toggleable_row_child_sscc_\"]').hide();

								parent_element.parent().show();
								parent_element.show();
								toggleable_row_sscc.css({'width': parent_element.width()+'px', 'padding-bottom': '15px'}).delay(1).slideDown('fast');
							}
						}

					});

					// 1. tason alasvetovalikolla filteröinti
					$('select.filter_parent_row_by').live('change', function(event) {

						event.stopPropagation();

						$('tr.toggleable_parent').hide();

						var empty_all = true, selected = '';

						$('select.filter_parent_row_by option:selected').each(function() {

							if ($(this).val() != '') {
								empty_all = false;

								var title = $(this).parent().attr('id').substring(18);

								if (selected != '') {
									selected = $(selected).children().filter('td[id^=\"'+$(this).val().replace(/(:|\.)/g,'\\$1')+'\"][class~=\"toggleable_parent_row_'+title+'\"]').parent();
								}
								else {
									selected = $('td[id^=\"'+$(this).val().replace(/(:|\.)/g,'\\$1')+'\"][class~=\"toggleable_parent_row_'+title+'\"]').parent();
								}
							}
						});

						if (empty_all) {
							$('tr.toggleable_parent').show();
						}
						else {
							$(selected).show();
							$(this).attr('selected', true);
						}
					});

					$('select.filter_parent_row_by option').each(function() {
						if ($(this).is(':selected') && $(this).val() != '') {
							$(this).trigger('change');
						}
					});

					$('#select_varasto').live('change', function() {
						$('#varastoformi').submit();
					});

					$('#muokkaa_lahto').on('click', function() {
						$('input[name^=\"checkbox_parent\"]').each(function() {
							if ($(this).is(':checked')) {
								$('#muokkaa_lahto').after('<input type=\"hidden\" name=\"checkbox_parent[]\" value=\"'+$(this).val()+'\">');
							}
						});

						$(this).after('<input type=\"hidden\" name=\"muokkaa_lahto\" value=\"X\">');

						$('#napitformi').submit();
					});

					$('#muokkaa_kolleja').on('click', function() {

						if ($('input[name^=\"checkbox_parent\"]:checked').length > 1) {
							alert('",t("Voit muokata vain yhden lähdön kolleja kerrallaan"),".');
						}
						else if ($('input[name^=\"checkbox_parent\"]:checked').length == 0) {
							alert('",t("Lähtö täytyy valita"),".');
						}
						else {
							$('input[name^=\"checkbox_parent\"]:checked').each(function() {
								$('#muokkaa_kolleja').after('<input type=\"hidden\" name=\"checkbox_parent[]\" value=\"'+$(this).val()+'\">');
							});

							$('#muokkaa_kolleja').after('<input type=\"hidden\" name=\"lopetus\" value=\"{$palvelin2}tilauskasittely/lahtojen_hallinta.php////select_varasto={$select_varasto}//tee=\">');
							$('#napitformi').attr('action', '{$palvelin2}tilauskasittely/muokkaa_kolleja.php');
							$('#napitformi').submit();
						}

					});

					$('#tulosta_rahtikirjat').on('click', function() {

						var lahdot = '';
						var lahdotcount = 0;

						$('input[name^=\"checkbox_parent\"]').each(function() {
							if ($(this).is(':checked')) {

								lahdot=lahdot+$(this).val()+', ';
								lahdotcount++;

								$('#tulosta_rahtikirjat').after('<input type=\"hidden\" name=\"checkbox_parent[]\" value=\"'+$(this).val()+'\">');
							}
						});

						$(this).after('<input type=\"hidden\" name=\"tulosta_rahtikirjat\" value=\"X\">');

						if (lahdotcount > 1) lahdot = '".t("Oletko varma, että haluat tulostaa rahtikirjat ja sulkea lähdöt")."'+': '+lahdot.substring(0, lahdot.length - 2);
						else lahdot = '".t("Oletko varma, että haluat tulostaa rahtikirjat ja sulkea lähdön")."'+': '+lahdot.substring(0, lahdot.length - 2);

						if (confirm(lahdot)) $('#napitformi').submit();
					});

					$('#vaihda_prio').on('click', function() {
						$('input[name^=\"checkbox_child\"]').each(function() {
							if ($(this).is(':checked')) {
								$('#vaihda_prio').after('<input type=\"hidden\" name=\"checkbox_child[]\" value=\"'+$(this).val()+'\">');
							}
						});

						$(this).after('<input type=\"hidden\" name=\"vaihda_prio\" value=\"X\">');

						$('#napitformi').submit();
					});

					$('#man_aloitus').on('click', function() {
						$('input[name^=\"checkbox_child\"]').each(function() {
							if ($(this).is(':checked')) {
								$('#man_aloitus').after('<input type=\"hidden\" name=\"checkbox_child[]\" value=\"'+$(this).val()+'\">');
							}
						});

						$(this).after('<input type=\"hidden\" name=\"man_aloitus\" value=\"X\">');

						$('#napitformi').submit();
					});

					$('#siirra_lahtoon').on('click', function() {
						$('input[name^=\"checkbox_child\"]').each(function() {
							if ($(this).is(':checked')) {
								$('#siirra_lahtoon').after('<input type=\"hidden\" name=\"checkbox_child[]\" value=\"'+$(this).val()+'\">');
							}
						});

						$(this).after('<input type=\"hidden\" name=\"siirra_lahtoon\" value=\"X\">');

						$('#napitformi').submit();
					});

				});

				//-->
			</script>";

	if (!isset($parent_row_select_status)) $parent_row_select_status = "";
	if (!isset($parent_row_select_prio)) $parent_row_select_prio = "";
	if (!isset($parent_row_select_carrier)) $parent_row_select_carrier = "";
	if (!isset($parent_row_select_delivery)) $parent_row_select_delivery = "";
	if (!isset($parent_row_select_date)) $parent_row_select_date = "";
	if (!isset($parent_row_select_manual)) $parent_row_select_manual = "";
	if (!isset($valittu_lahto)) $valittu_lahto = "";
	if (!isset($select_varasto)) $select_varasto = 0;
	if (!isset($tee)) $tee = "";

	if ($tee == "") {
		echo "<br><form method='post' id='varastoformi'>";
		echo "<table>";

		echo "<tr><th>",t("Valitse varasto"),"</th><td class='back' style='vertical-align:middle;'>&nbsp;";
		echo "<select name='select_varasto' id='select_varasto'>";
		echo "<option value=''>",t("Valitse"),"</option>";

		$query = "	SELECT tunnus, nimitys
					FROM varastopaikat
					WHERE yhtio = '{$kukarow['yhtio']}' AND tyyppi != 'P'
					ORDER BY tyyppi, nimitys";
		$varastores = pupe_query($query);

		while ($varastorow = mysql_fetch_assoc($varastores)) {

			$sel = $select_varasto == $varastorow['tunnus'] ? " selected" : ($kukarow['oletus_varasto'] == $varastorow['tunnus'] ? " selected" : "");

			if ($select_varasto == 0 and $sel != "" and $kukarow['oletus_varasto'] == $varastorow['tunnus']) {
				$select_varasto = $kukarow['oletus_varasto'];
			}

			echo "<option value='{$varastorow['tunnus']}'{$sel}>{$varastorow['nimitys']}</option>";
		}

		echo "</select>";
		echo "</td></tr>";
		echo "</table>";
		echo "</form><br>";
	}

	if ($select_varasto > 0) {

		$ohita_kerays_lapset = array();

		$query = "	SELECT tilausrivi.perheid, tilausrivi.tuoteno, tilausrivi.tunnus
					FROM lasku
					JOIN lahdot ON (lahdot.yhtio = lasku.yhtio AND lahdot.tunnus = lasku.toimitustavan_lahto AND lahdot.aktiivi IN ('','P','T'))
					JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus AND tilausrivi.perheid != 0 AND tilausrivi.perheid != tilausrivi.tunnus AND tilausrivi.tyyppi != 'D' AND tilausrivi.var not in ('P','J'))
					JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
					JOIN varastopaikat ON (varastopaikat.yhtio = tilausrivi.yhtio
						and concat(rpad(upper(varastopaikat.alkuhyllyalue), 5, '0'),lpad(upper(varastopaikat.alkuhyllynro), 5, '0')) <= concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'))
						and concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'),lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'))
					AND varastopaikat.tunnus = '{$select_varasto}')
					WHERE lasku.yhtio = '{$kukarow['yhtio']}'
					AND ((lasku.tila = 'N' AND lasku.alatila = 'A') OR (lasku.tila = 'L' AND lasku.alatila IN ('A','B','C')))";
		$result = pupe_query($query);

		while ($row = mysql_fetch_assoc($result)) {

			$query = "	SELECT tuoteno
						FROM tilausrivi
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tunnus = '{$row['perheid']}'
						AND tyyppi != 'D'
						AND var not in ('P','J')";
			$isatuote_chk_res = pupe_query($query);
			$isatuote_chk_row = mysql_fetch_assoc($isatuote_chk_res);

			$query = "	SELECT ohita_kerays
						FROM tuoteperhe
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND isatuoteno = '{$isatuote_chk_row['tuoteno']}'
						AND tuoteno = '{$row['tuoteno']}'
						AND tyyppi = 'P'";
			$ohita_kerays_chk_res = pupe_query($query);
			$ohita_kerays_chk_row = mysql_fetch_assoc($ohita_kerays_chk_res);

			if ($ohita_kerays_chk_row['ohita_kerays'] != '') {
				array_push($ohita_kerays_lapset, $row['tunnus']);
			}

		}

		$ei_lapsia_lisa = count($ohita_kerays_lapset) > 0 ? "AND tilausrivi.tunnus NOT IN (".implode(",", $ohita_kerays_lapset).")" : "";

		echo "<form method='post' id='napitformi'>";
		echo "<table>";
		echo "<tr>";
		echo "<td class='back'>";

		if ($onko_paivitysoikeuksia_ohjelmaan) {
			if ($tee == 'lahto' and trim($tilaukset) != '') {
				echo "<button type='button' id='man_aloitus'>",t("Man. aloitus"),"</button>&nbsp;";
				echo "<button type='button' id='vaihda_prio'>",t("Vaihda prio"),"</button>&nbsp;";
				echo "<button type='button' id='siirra_lahtoon'>",t("Siirrä lähtöön"),"</button>";
			}
			else {
				echo "<button type='button' id='muokkaa_lahto'>",t("Muokkaa lähtö"),"</button>&nbsp;";
				echo "<button type='button' id='tulosta_rahtikirjat'>",t("Tulosta rahtikirjat"),"</button>&nbsp;";

				if (tarkista_oikeus('tilauskasittely/muokkaa_kolleja.php')) {
					echo "<button type='button' id='muokkaa_kolleja'>",t("Muokkaa kolleja"),"</button>&nbsp;";
				}
			}
		}

		if ($valittu_lahto == "" and isset($tilaukset) and $tilaukset != "") {
			$query = "	SELECT toimitustavan_lahto
						FROM lasku
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tunnus IN ({$tilaukset})";
			$chk_res = pupe_query($query);
			$chk_row = mysql_fetch_assoc($chk_res);
			$valittu_lahto = $chk_row['toimitustavan_lahto'];
		}

		echo "<input type='hidden' name='valittu_lahto' id='valittu_lahto' value='{$valittu_lahto}' />";
		echo "<input type='hidden' name='select_varasto' id='select_varasto' value='{$select_varasto}' />";
		echo "</td>";
		echo "</tr>";
		echo "</table>";
		echo "</form>";

		if ($tee == '') {

			$query = "	SELECT lahdot.tunnus AS 'lahdon_tunnus',
						lahdot.pvm AS 'lahdon_pvm',
						SUBSTRING(lahdot.viimeinen_tilausaika, 1, 5) AS 'viimeinen_tilausaika',
						SUBSTRING(lahdot.lahdon_kellonaika, 1, 5) AS 'lahdon_kellonaika',
						SUBSTRING(lahdot.kerailyn_aloitusaika, 1, 5) AS 'kerailyn_aloitusaika',
						avainsana.selitetark_3 AS 'prioriteetti',
						toimitustapa.selite AS 'toimitustapa',
						toimitustapa.lahdon_selite,
						lahdot.aktiivi,
						GROUP_CONCAT(IF(lasku.toimitustavan_lahto_siirto = 0, '', lasku.toimitustavan_lahto_siirto) SEPARATOR '') AS 'toimitustavan_lahto_siirto',
						GROUP_CONCAT(lasku.vakisin_kerays) AS 'vakisin_kerays',
						COUNT(DISTINCT lasku.liitostunnus) AS 'asiakkaita',
						GROUP_CONCAT(DISTINCT CONCAT(lasku.nimi, ' ', IF(lasku.nimi != lasku.toim_nimi, lasku.toim_nimi, '')) SEPARATOR ' ') AS asiakkaiden_nimet,
						GROUP_CONCAT(DISTINCT CONCAT(lasku.toim_postitp) SEPARATOR ' ') AS asiakkaiden_postitp,
						GROUP_CONCAT(DISTINCT lasku.tunnus) AS 'tilaukset'
						FROM lasku
						JOIN lahdot ON (lahdot.yhtio = lasku.yhtio AND lahdot.tunnus = lasku.toimitustavan_lahto AND lahdot.aktiivi IN ('','P','T'))
						JOIN avainsana ON (avainsana.yhtio = lahdot.yhtio AND avainsana.laji = 'ASIAKASLUOKKA' AND avainsana.kieli = '{$yhtiorow['kieli']}' AND avainsana.selitetark_3 = lahdot.asiakasluokka)
						JOIN toimitustapa ON (toimitustapa.yhtio = lasku.yhtio AND toimitustapa.selite = lasku.toimitustapa)
						JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus AND tilausrivi.tyyppi != 'D' AND tilausrivi.var not in ('P','J') {$ei_lapsia_lisa})
						JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
						JOIN varastopaikat ON (varastopaikat.yhtio = tilausrivi.yhtio
							and concat(rpad(upper(varastopaikat.alkuhyllyalue), 5, '0'),lpad(upper(varastopaikat.alkuhyllynro), 5, '0')) <= concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'))
							and concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'),lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'))
						AND varastopaikat.tunnus = '{$select_varasto}')
						WHERE lasku.yhtio = '{$kukarow['yhtio']}'
						AND ((lasku.tila = 'N' AND lasku.alatila = 'A') OR (lasku.tila = 'L' AND lasku.alatila IN ('A','B','C')))
						GROUP BY 1,2,3,4,5,6,7,8,9
						ORDER BY lahdot.pvm, lahdot.lahdon_kellonaika, lahdot.tunnus";
			$result = pupe_query($query);

			$deliveries = $dates = $priorities = $carriers = array();

			while ($row = mysql_fetch_assoc($result)) {
				$deliveries[$row['toimitustapa']] = $row['toimitustapa'];
				$dates[$row['lahdon_pvm']] = $row['lahdon_pvm'];
				$priorities[$row['prioriteetti']] = $row['prioriteetti'];
				$carriers[$row['lahdon_selite']] = $row['lahdon_selite'];
			}

			echo "<br />";

			$colspan_parent = 17;

			echo "<table>";
			echo "<tr><th>",t("Etsi asiakas"),"</th><td><input type='text' class='client' id='uni_client_search' value='' />&nbsp;</td>";
			echo "<th>",t("Paikkakunta"),"</th><td><input type='text' class='locality' id='uni_locality_search' value='' /></td></tr>";
			echo "<tr><th>",t("Etsi tilausnumero"),"</th><td colspan='3'><input type='text' class='order' id='uni_order_search' value='' /></td></tr>";
			echo "</table>";

			echo "<br />";

			echo "<form method='post'>";
			echo "<table>";

			echo "<tr><td colspan='{$colspan_parent}' class='back'>&nbsp;</td></tr>";

			echo "<tr class='header_parent' id='header_parent'>";

			echo "<th class='sort_parent_row_by' id='parent_row_status'>",t("Status")," <img class='parent_row_direction_status' />";
			echo "<br />";

			$sel = array_fill_keys(array($parent_row_select_status), " selected") + array(1 => '', 2 => '', 3 => '');

			echo "<select class='filter_parent_row_by' name='parent_row_select_status' id='parent_row_select_status'>";
			echo "<option value=''>",t("Valitse"),"</option>";
			echo "<option value='3'{$sel[3]}>",t("Aloittamatta"),"</option>";
			echo "<option value='2'{$sel[2]}>",t("Aloitettu"),"</option>";
			echo "<option value='1'{$sel[1]}>",t("Aika ylitetty"),"</option>";
			echo "</select>";
			echo "</th>";

			echo "<th class='sort_parent_row_by' id='parent_row_manual'>",t("Man. aloitus")," <img class='parent_row_direction_manual' />";
			echo "<br />";

			$sel = $parent_row_select_manual != "" ? " selected" : "";

			echo "<select class='filter_parent_row_by' name='parent_row_select_manual' id='parent_row_select_manual'>";
			echo "<option value=''>",t("Valitse"),"</option>";
			echo "<option value='X'{$sel}>",t("Man. aloitus"),"</option>";
			echo "</select>";
			echo "</th>";

			echo "<th class='sort_parent_row_by' id='parent_row_transfer'>S <img class='parent_row_direction_transfer' /></th>";
			echo "<th class='sort_parent_row_by' id='parent_row_stopped'>P <img class='parent_row_direction_stopped' /><br>T</th>";
			echo "<th></th>";
			echo "<th class='sort_parent_row_by' id='parent_row_departure'>",t("Lähtö")," <img class='parent_row_direction_departure' /></th>";

			echo "<th class='sort_parent_row_by' id='parent_row_prio'>",t("Prio")," <img class='parent_row_direction_prio' />";
			echo "<br />";
			echo "<select class='filter_parent_row_by' name='parent_row_select_prio' id='parent_row_select_prio'>";
			echo "<option value=''>",t("Valitse"),"</option>";

			sort($priorities);

			foreach ($priorities AS $prio) {

				$sel = $parent_row_select_prio == $prio ? " selected" : "";

				echo "<option value='{$prio}'{$sel}>{$prio}</option>";
			}

			echo "</select>";
			echo "</th>";

			echo "<th class='sort_parent_row_by' id='parent_row_carrier'>",t("Rahdinkuljettaja")," <img class='parent_row_direction_carrier' />";
			echo "<br />";
			echo "<select class='filter_parent_row_by' name='parent_row_select_carrier' id='parent_row_select_carrier'>";
			echo "<option value=''>",t("Valitse"),"</option>";

			sort($carriers);

			foreach ($carriers AS $carr) {

				$sel = $parent_row_select_carrier == $carr ? " selected" : "";

				echo "<option value='{$carr}'{$sel}>{$carr}</option>";
			}

			echo "</select>";
			echo "</th>";

			echo "<th class='sort_parent_row_by' id='parent_row_delivery'>",t("Toimitustapa")," <img class='parent_row_direction_delivery' />";
			echo "<br />";
			echo "<select class='filter_parent_row_by' name='parent_row_select_delivery' id='parent_row_select_delivery'>";
			echo "<option value=''>",t("Valitse"),"</option>";

			sort($deliveries);

			foreach ($deliveries AS $deli) {

				$sel = $parent_row_select_delivery == $deli ? " selected" : "";

				echo "<option value='{$deli}'{$sel}>{$deli}</option>";
			}

			echo "</select>";
			echo "</th>";

			echo "<th class='sort_parent_row_by' id='parent_row_date'>",t("Pvm")," <img class='parent_row_direction_date' />";
			echo "<br />";
			echo "<select class='filter_parent_row_by' name='parent_row_select_date' id='parent_row_select_date'>";
			echo "<option value=''>",t("Valitse"),"</option>";

			sort($dates);

			foreach ($dates AS $pvm) {

				$pvm = tv1dateconv($pvm);

				$sel = $parent_row_select_date == $pvm ? " selected" : (date("d.m.Y") == $pvm ? " selected" : "");

				echo "<option value='{$pvm}'{$sel}>{$pvm}</option>";
			}

			echo "</select>";
			echo "</th>";

			echo "<th class='sort_parent_row_by' id='parent_row_time1'>",t("Viim til klo")," <img class='parent_row_direction_time1' /></th>";
			echo "<th class='sort_parent_row_by' id='parent_row_time2'>",t("Lähtöaika")," <img class='parent_row_direction_time2' /></th>";
			echo "<th class='sort_parent_row_by' id='parent_row_time3'>",t("Ker. alku klo")," <img class='parent_row_direction_time3' /></th>";
			echo "<th class='sort_parent_row_by' id='parent_row_orders'>",t("Til / valm")," <img class='parent_row_direction_orders' /></th>";
			echo "<th class='sort_parent_row_by' id='parent_row_rows'>",t("Rivit suun / ker")," <img class='parent_row_direction_rows' /></th>";
			echo "<th class='sort_parent_row_by' id='parent_row_weight'>",t("Kg suun / ker")," <img class='parent_row_direction_weight' /></th>";
			echo "<th class='sort_parent_row_by' id='parent_row_liters'>",t("Litrat suun / ker")," <img class='parent_row_direction_liters' /></th>";
			echo "</tr>";

			echo "</form>";

			if (mysql_num_rows($result)) {
				mysql_data_seek($result, 0);
			}

			$y = 0;

			while ($row = mysql_fetch_assoc($result)) {

				echo "<tr class='toggleable_parent' id='toggleable_parent_{$row['lahdon_tunnus']}__{$y}'>";

				$exp_date = strtotime($row['lahdon_pvm']);
				$exp_date_klo = strtotime($row['lahdon_kellonaika'].':00');
				$ker_date_klo = strtotime($row['kerailyn_aloitusaika'].':00');
				$todays_date = strtotime(date('Y-m-d'));
				$todays_date_klo = strtotime(date('H:i:s'));

				if ($todays_date == $exp_date and $todays_date_klo > $ker_date_klo) {
					echo "<td class='toggleable_parent_row_status' id='2__{$row['lahdon_tunnus']}__{$y}'><div class='vihrea'>&nbsp;</div></td>";
				}
				elseif (($todays_date > $exp_date) or ($todays_date == $exp_date and $todays_date_klo > $exp_date_klo)) {
					echo "<td class='toggleable_parent_row_status' id='1__{$row['lahdon_tunnus']}__{$y}'><div class='punainen'>&nbsp;</div></td>";
				}
				else {
					echo "<td class='toggleable_parent_row_status' id='3__{$row['lahdon_tunnus']}__{$y}'><div class='keltainen'>&nbsp;</div></td>";
				}

				if (strpos($row['vakisin_kerays'], "X") !== false) {
					echo "<td class='center toggleable_parent_row_manual' id='X__{$row['lahdon_tunnus']}__{$y}'><div class='sininen'>&nbsp;</div></td>";
				}
				else {
					echo "<td class='center toggleable_parent_row_manual' id='!__{$row['lahdon_tunnus']}__{$y}'>&nbsp;</td>";
				}

				if ($row['toimitustavan_lahto_siirto'] != '') {
					echo "<td class='center toggleable_parent_row_transfer' id='X__{$row['lahdon_tunnus']}__{$y}'>X</td>";
				}
				else {
					echo "<td class='center toggleable_parent_row_transfer' id='!__{$row['lahdon_tunnus']}__{$y}'>&nbsp;</td>";
				}

				if ($row['aktiivi'] == 'P') {
					echo "<td class='center toggleable_parent_row_stopped' id='X__{$row['lahdon_tunnus']}{$row['aktiivi']}__{$y}'>X</td>";
				}
				elseif ($row['aktiivi'] == 'T') {
					echo "<td class='center toggleable_parent_row_stopped' id='T__{$row['lahdon_tunnus']}{$row['aktiivi']}__{$y}'>T</td>";
				}
				else {
					echo "<td class='center toggleable_parent_row_stopped' id='!__{$row['lahdon_tunnus']}X__{$y}'>&nbsp;</td>";
				}

				echo "<td>";

				if ($onko_paivitysoikeuksia_ohjelmaan) {
					echo "<input type='checkbox' class='checkall_parent' name='checkbox_parent[]' id='{$row['lahdon_tunnus']}' value='{$row['lahdon_tunnus']}'>";
				}

				echo "</td>";

				echo "<td class='toggleable center toggleable_parent_row_departure' id='{$row['lahdon_tunnus']}__{$y}'>";
				echo "<form method='post'>";
				echo "<input type='hidden' name='tee' value='lahto' />";
				echo "<input type='hidden' name='ei_lapsia_lisa' value='{$ei_lapsia_lisa}' />";
				echo "<input type='hidden' name='select_varasto' value='{$select_varasto}' />";
				echo "<input type='hidden' name='lopetus' value='{$palvelin2}tilauskasittely/lahtojen_hallinta.php////tee=//select_varasto={$select_varasto}' />";
				echo "<input type='hidden' name='lahdon_tunnus' value='{$row['lahdon_tunnus']}' />";
				echo "<input type='hidden' name='tilaukset' value='{$row['tilaukset']}' />";
				echo "<button type='submit' id='{$row['lahdon_tunnus']}'>{$row['lahdon_tunnus']}</button>";
				echo "</form>";
				echo "</td>";
				echo "<td class='center toggleable_parent_row_prio' id='{$row['prioriteetti']}__{$row['lahdon_tunnus']}__{$y}'>{$row['prioriteetti']}</td>";
				echo "<td class='toggleable_parent_row_carrier' id='{$row['lahdon_selite']}__{$row['lahdon_tunnus']}__{$y}'>{$row['lahdon_selite']}</td>";
				echo "<td class='toggleable_parent_row_delivery' id='{$row['toimitustapa']}__{$row['lahdon_tunnus']}__{$y}'>{$row['toimitustapa']}</td>";
				echo "<td class='center toggleable_parent_row_date' id='",tv1dateconv($row['lahdon_pvm']),"__{$row['lahdon_tunnus']}__{$y}'>",tv1dateconv($row['lahdon_pvm']),"</td>";
				echo "<td class='center toggleable_parent_row_time1' id='{$row['viimeinen_tilausaika']}__{$row['lahdon_tunnus']}__{$y}'>{$row['viimeinen_tilausaika']}</td>";

				echo "<td class='center toggleable_parent_row_time2' id='{$row['lahdon_kellonaika']}__{$row['lahdon_tunnus']}__{$y}'>";

				$exp_date = strtotime($row['lahdon_pvm'].' '.$row['lahdon_kellonaika'].':00');
				$todays_date = strtotime(date('Y-m-d H:i:s'));

				if ($todays_date > $exp_date) {
					echo "<font class='error'>{$row['lahdon_kellonaika']}</font>";
				}
				else {
					echo $row['lahdon_kellonaika'];
				}

				echo "</td>";

				echo "<td class='center toggleable_parent_row_time3' id='{$row['kerailyn_aloitusaika']}__{$row['lahdon_tunnus']}__{$y}'>{$row['kerailyn_aloitusaika']}</td>";

				$query = "	SELECT COUNT(DISTINCT lasku.tunnus) AS 'tilatut',
							SUM(IF((lasku.tila = 'L' AND lasku.alatila IN ('B', 'C')), 1, 0)) AS 'valmiina'
							FROM lasku
							WHERE lasku.yhtio = '{$kukarow['yhtio']}'
							AND lasku.tunnus IN ({$row['tilaukset']})";
				$rivit_res = pupe_query($query);
				$rivit_row = mysql_fetch_assoc($rivit_res);

				echo "<td class='center toggleable_parent_row_orders' id='{$rivit_row['tilatut']}__{$row['lahdon_tunnus']}__{$y}'>{$rivit_row['tilatut']} / {$rivit_row['valmiina']}</td>";

				$query = "	SELECT COUNT(DISTINCT tilausrivi.tunnus) AS 'suunnittelussa',
							SUM(IF(tilausrivi.kerattyaika != '0000-00-00 00:00:00', 1, 0)) AS 'keratyt'
							FROM lasku
							JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus AND tilausrivi.varattu > 0 AND tilausrivi.tyyppi != 'D' AND tilausrivi.var not in ('P','J') {$ei_lapsia_lisa})
							WHERE lasku.yhtio = '{$kukarow['yhtio']}'
							AND lasku.tunnus IN ({$row['tilaukset']})";
				$rivit_res = pupe_query($query);
				$rivit_row = mysql_fetch_assoc($rivit_res);

				echo "<td class='center toggleable_parent_row_rows' id='{$rivit_row['suunnittelussa']}__{$row['lahdon_tunnus']}__{$y}'>{$rivit_row['suunnittelussa']} / {$rivit_row['keratyt']}</td>";

				$query = "	SELECT ROUND(SUM(tilausrivi.varattu * tuote.tuotemassa), 0) AS 'kg_suun',
							ROUND(SUM(IF(tilausrivi.kerattyaika != '0000-00-00 00:00:00', tilausrivi.varattu * tuote.tuotemassa, 0)), 0) AS 'kg_ker',
							ROUND(SUM(tilausrivi.varattu * (tuote.tuoteleveys * tuote.tuotekorkeus * tuote.tuotesyvyys * 1000)), 0) AS 'litrat_suun',
							ROUND(SUM(IF(tilausrivi.kerattyaika != '0000-00-00 00:00:00', (tilausrivi.varattu * (tuote.tuoteleveys * tuote.tuotekorkeus * tuote.tuotesyvyys * 1000)), 0)), 0) AS 'litrat_ker'
							FROM tilausrivi
							JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
							WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
							AND tilausrivi.otunnus IN ({$row['tilaukset']})
							AND tilausrivi.tyyppi != 'D'
							AND tilausrivi.var not in ('P','J')
							{$ei_lapsia_lisa}";
				$kg_res = pupe_query($query);
				$kg_row = mysql_fetch_assoc($kg_res);

				echo "<td class='center toggleable_parent_row_weight' id='{$kg_row['kg_suun']}__{$row['lahdon_tunnus']}__{$y}'>{$kg_row['kg_suun']} / {$kg_row['kg_ker']}</td>";
				echo "<td class='center toggleable_parent_row_liters' id='{$kg_row['litrat_suun']}__{$row['lahdon_tunnus']}__{$y}'>{$kg_row['litrat_suun']} / {$kg_row['litrat_ker']}</td>";

				echo "</tr>";

				echo "<tr class='toggleable_row_tr' id='child_{$row['lahdon_tunnus']}__{$y}' style='display:none;'>";
				echo "<td class='toggleable_row_client'>{$row['asiakkaiden_nimet']}</td>";
				echo "<td class='toggleable_row_locality'>{$row['asiakkaiden_postitp']}</td>";
				echo "<td class='toggleable_row_order'>",str_replace(",", " ", $row['tilaukset']),"</td>";
				echo "</tr>";

				$y++;
			}

			echo "</table>";
			echo "</form>";
		}

		if ($tee == 'lahto' and trim($tilaukset) != '') {

			if (!isset($nayta_valinnat) or count($nayta_valinnat) == 1) $nayta_valinnat = array('aloittamatta', 'aloitettu');
			$chk = array_fill_keys($nayta_valinnat, " checked") + array('aloittamatta' => '', 'aloitettu' => '', 'keratty' => '');

			$lopetus_url = "{$palvelin2}tilauskasittely/lahtojen_hallinta.php////select_varasto={$select_varasto}//tee=";

			echo "<br />";
			echo "<form method='post' action=''>";
			echo "<table>";
			echo "<tr class='tumma'>";
			echo "<th>",t("Valitse"),"</td>";
			echo "<td style='vertical-align:middle;'>";
			echo "<input type='hidden' name='tee' value='lahto' />";
			echo "<input type='hidden' name='tilaukset' value='{$tilaukset}' />";
			echo "<input type='hidden' name='select_varasto' value='{$select_varasto}' />";
			echo "<input type='hidden' name='lopetus' value='{$lopetus_url}' />";
			echo "<input type='hidden' name='nayta_valinnat[]' value='default' />";
			echo "<input type='checkbox' class='nayta_valinnat' name='nayta_valinnat[]' value='aloittamatta' {$chk['aloittamatta']} /> ",t("Aloittamatta"),"&nbsp;&nbsp;";
			echo "<input type='checkbox' class='nayta_valinnat' name='nayta_valinnat[]' value='aloitettu' {$chk['aloitettu']} /> ",t("Aloitettu"),"&nbsp;&nbsp;";
			echo "<input type='checkbox' class='nayta_valinnat' name='nayta_valinnat[]' value='keratty' {$chk['keratty']} /> ",t("Kerätty"),"&nbsp;&nbsp;";
			echo "<input type='submit' value='",t("Näytä"),"' />";
			echo "</td>";
			echo "</tr>";
			echo "</table>";
			echo "</form>";

			$y = 0;

			echo "<form>";
			echo "<table>";

			$wherelisa = "";
			$kerayserat_tilalisa = "";

			foreach ($nayta_valinnat as $mita_naytetaan) {

				switch ($mita_naytetaan) {
					case 'aloittamatta':
						$wherelisa = trim($wherelisa) != "" ? "{$wherelisa} OR (lasku.tila = 'N' AND lasku.alatila = 'A')" : "(lasku.tila = 'N' AND lasku.alatila = 'A')";
						break;
					case 'aloitettu':
						$wherelisa = trim($wherelisa) != "" ? "{$wherelisa} OR (lasku.tila = 'L' AND lasku.alatila = 'A')" : "(lasku.tila = 'L' AND lasku.alatila = 'A')";
						$kerayserat_tilalisa = trim($kerayserat_tilalisa) != "" ? "{$kerayserat_tilalisa} OR kerayserat.tila IN ('K','X')" : "kerayserat.tila IN ('K','X')";
						break;
					case 'keratty':
						$wherelisa = trim($wherelisa) != "" ? "{$wherelisa} OR (lasku.tila = 'L' AND lasku.alatila IN ('B', 'C'))" : "(lasku.tila = 'L' AND lasku.alatila IN ('B', 'C'))";
						$kerayserat_tilalisa = trim($kerayserat_tilalisa) != "" ? "{$kerayserat_tilalisa} OR (kerayserat.tila IN ('T','R'))" : "(kerayserat.tila IN ('T','R'))";
						break;
				}
			}

			$wherelisa = "AND ({$wherelisa})";

			$query = "	SELECT lasku.tunnus AS 'tilauksen_tunnus',
						lasku.vanhatunnus AS 'tilauksen_vanhatunnus',
						IF(lasku.tilaustyyppi = '', 'N', lasku.tilaustyyppi) AS 'tilauksen_tilaustyyppi',
						lasku.nimi AS 'asiakas_nimi',
						lasku.toim_nimi AS 'asiakas_toim_nimi',
						lasku.toim_postitp AS 'asiakas_toim_postitp',
						asiakas.luokka AS 'asiakas_luokka',
						lasku.prioriteettinro AS 'prioriteetti',
						lasku.ohjausmerkki,
						lasku.vakisin_kerays,
						kerayserat.nro AS 'erat',
						kerayserat.sscc,
						kerayserat.sscc_ulkoinen,
						kerayserat.pakkausnro,
						IF(lasku.toimitustavan_lahto_siirto = 0, '', lasku.toimitustavan_lahto_siirto) AS toimitustavan_lahto_siirto,
						lahdot.tunnus AS 'lahdon_tunnus',
						lahdot.pvm AS 'lahdon_pvm',
						SUBSTRING(lahdot.viimeinen_tilausaika, 1, 5) AS 'viimeinen_tilausaika',
						SUBSTRING(lahdot.lahdon_kellonaika, 1, 5) AS 'lahdon_kellonaika',
						SUBSTRING(lahdot.kerailyn_aloitusaika, 1, 5) AS 'kerailyn_aloitusaika',
						avainsana.selitetark_3 AS 'prioriteetti_info',
						toimitustapa.selite AS 'toimitustapa',
						toimitustapa.lahdon_selite,
						GROUP_CONCAT(DISTINCT kerayserat.tila) AS 'tilat',
						COUNT(kerayserat.tunnus) AS 'keraysera_rivi_count',
						SUM(IF((kerayserat.tila = 'T' OR kerayserat.tila = 'R'), 1, 0)) AS 'keraysera_rivi_valmis'
						FROM lasku
						JOIN asiakas ON (asiakas.yhtio = lasku.yhtio AND asiakas.tunnus = lasku.liitostunnus)
						JOIN lahdot ON (lahdot.yhtio = lasku.yhtio AND lahdot.tunnus = lasku.toimitustavan_lahto AND lahdot.aktiivi IN ('','P','T'))
						JOIN avainsana ON (avainsana.yhtio = lahdot.yhtio AND avainsana.laji = 'ASIAKASLUOKKA' AND avainsana.kieli = '{$yhtiorow['kieli']}' AND avainsana.selitetark_3 = lahdot.asiakasluokka)
						JOIN toimitustapa ON (toimitustapa.yhtio = lasku.yhtio AND toimitustapa.selite = lasku.toimitustapa)
						LEFT JOIN kerayserat ON (kerayserat.yhtio = lasku.yhtio AND kerayserat.otunnus = lasku.tunnus and kerayserat.tila != 'Ö')
						LEFT JOIN pakkaus ON (pakkaus.yhtio = kerayserat.yhtio AND pakkaus.tunnus = kerayserat.pakkaus)
						WHERE lasku.yhtio = '{$kukarow['yhtio']}'
						AND lasku.tunnus IN ({$tilaukset})
						{$wherelisa}
						GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23";
			$lahto_res = pupe_query($query);
			$lahto_row = mysql_fetch_assoc($lahto_res);

			if (!isset($child_row_select_status)) $child_row_select_status = "";
			if (!isset($child_row_select_prio)) $child_row_select_prio = "";

			echo "<tr class='toggleable_tr' id='toggleable_tr_{$row['lahdon_tunnus']}__{$y}'>";
			echo "<td colspan='{$colspan_parent}' class='back'>";
			echo "<div id='toggleable_{$row['lahdon_tunnus']}__{$y}'>";

			echo "<table style='width:100%; padding:0px; margin:0px; border:0px;'>";

			echo "<tr><td class='back'>&nbsp;</td></tr>";

			echo "<tr>";
			echo "<th>",t("Lähtö"),"</th>";
			echo "<th>",t("Prio"),"</th>";
			echo "<th>",t("Rahdinkuljettaja"),"</th>";
			echo "<th>",t("Toimitustapa"),"</th>";
			echo "<th>",t("Pvm"),"</th>";
			echo "<th>",t("Viim Til Klo"),"</th>";
			echo "<th>",t("Lähtöaika"),"</th>";
			echo "<th>",t("Ker. Alku Klo"),"</th>";
			echo "</tr>";

			echo "<tr>";
			echo "<td class='data'>{$lahto_row['lahdon_tunnus']}</td>";
			echo "<td class='data'>{$lahto_row['prioriteetti_info']}</td>";
			echo "<td class='data'>{$lahto_row['lahdon_selite']}</td>";
			echo "<td class='data'>{$lahto_row['toimitustapa']}</td>";
			echo "<td class='data'>",tv1dateconv($lahto_row['lahdon_pvm']),"</td>";
			echo "<td class='data'>{$lahto_row['viimeinen_tilausaika']}</td>";
			echo "<td class='data'>{$lahto_row['lahdon_kellonaika']}</td>";
			echo "<td class='data'>{$lahto_row['kerailyn_aloitusaika']}</td>";
			echo "</tr>";

			echo "<tr><td class='back'>&nbsp;</td></tr>";

			echo "</table>";

			echo "<table style='width:100%; padding:0px; margin:0px; border:0px;'>";

			$priorities = array();

			mysql_data_seek($lahto_res, 0);

			while ($lahto_row = mysql_fetch_assoc($lahto_res)) {
				$priorities[$lahto_row['prioriteetti']] = $lahto_row['prioriteetti'];
			}

			echo "<tr class='header_row_{$row['lahdon_tunnus']}__{$y}' id='header_row_{$row['lahdon_tunnus']}__{$y}'>";

			echo "<th>";

			if ($onko_paivitysoikeuksia_ohjelmaan) {
				echo "<input type='checkbox' class='checkall_child'>";
			}

			echo "</th>";

			echo "<th class='sort_row_by' id='row_status__{$row['lahdon_tunnus']}__{$y}'>",t("Status")," <img class='row_direction_status' />";
			echo "<br />";

			$sel = array_fill_keys(array($child_row_select_status), " selected") + array(1 => '', 2 => '', 3 => '', 4 => '');

	 		echo "<select class='filter_row_by_select' name='child_row_select_status' id='child_row_select_status'>";
			echo "<option value=''>",t("Valitse"),"</option>";
			if (in_array("aloittamatta", $nayta_valinnat)) echo "<option value='1'{$sel[1]}>",t("Aloittamatta"),"</option>";
			if (in_array("aloitettu", $nayta_valinnat)) echo "<option value='2'{$sel[2]}>",t("Aloitettu"),"</option>";
			if (in_array("keratty", $nayta_valinnat)) echo "<option value='3'{$sel[3]}>",t("Kerätty"),"</option>";
			echo "</select>";
			echo "</th>";

			echo "<th class='sort_row_by' id='row_manual__{$row['lahdon_tunnus']}__{$y}'>M <img class='row_direction_manual' /></th>";
			echo "<th class='sort_row_by' id='row_transfer__{$row['lahdon_tunnus']}__{$y}'>S <img class='row_direction_transfer' /></th>";

			sort($priorities);

			echo "<th class='sort_row_by' id='row_prio__{$row['lahdon_tunnus']}__{$y}'>",t("Prio")," <img class='row_direction_prio' />";
			echo "<br />";
			echo "<select class='filter_row_by_select' name='child_row_select_prio' id='child_row_select_prio'>";
			echo "<option value=''></option>";

			foreach ($priorities as $prio) {
				echo "<option value='{$prio}'>{$prio}</option>";
			}

			echo "</select>";
			echo "</th>";

			echo "<th class='sort_row_by' id='row_order__{$row['lahdon_tunnus']}__{$y}'>",t("Tilausnumero")," <img class='row_direction_order' />";
			echo "<br />";
			echo "<input type='text' class='filter_row_by_text' id='child_row_text_order__{$row['lahdon_tunnus']}__{$y}' value='' size='8' />";
			echo "</th>";

			echo "<th class='sort_row_by' id='row_orderold__{$row['lahdon_tunnus']}__{$y}'>",t("Vanhatunnus")," <img class='row_direction_orderold' />";
			echo "<br />";
			echo "<input type='text' class='filter_row_by_text' id='child_row_text_orderold__{$row['lahdon_tunnus']}__{$y}' value='' size='8' />";
			echo "</th>";

			echo "<th class='sort_row_by' id='row_type__{$row['lahdon_tunnus']}__{$y}'>",t("Tilaustyyppi")," <img class='row_direction_type' /></th>";
			echo "<th class='sort_row_by' id='row_control__{$row['lahdon_tunnus']}__{$y}'>",t("Ohjausmerkki")," <img class='row_direction_control' /></th>";

			echo "<th class='sort_row_by' id='row_clientprio__{$row['lahdon_tunnus']}__{$y}'>",t("PriLk")," <img class='row_direction_clientprio' /></th>";

			echo "<th class='sort_row_by' id='row_client__{$row['lahdon_tunnus']}__{$y}'>",t("Asiakas")," <img class='row_direction_client' />";
			echo "<br />";
			echo "<input type='text' class='filter_row_by_text' id='child_row_text_client__{$row['lahdon_tunnus']}__{$y}' value='' />";
			echo "</th>";

			echo "<th class='sort_row_by' id='row_locality__{$row['lahdon_tunnus']}__{$y}'>",t("Paikkakunta")," <img class='row_direction_locality' /></th>";

			$query = "	SELECT DISTINCT nimitys
						FROM keraysvyohyke
						WHERE yhtio = '{$kukarow['yhtio']}'";
			$keraysvyohyke_result = pupe_query($query);

			echo "<th class='sort_row_by' id='row_picking_zone__{$row['lahdon_tunnus']}__{$y}'>",t("Vyöhyke")," <img class='row_direction_picking_zone' />";
			echo "<br />";
			echo "<select class='filter_row_by_select' id='child_row_select_picking_zone'>";
			echo "<option value=''>",t("Valitse"),"</option>";

			while ($keraysvyohyke_row = mysql_fetch_assoc($keraysvyohyke_result)) {
				echo "<option value='{$keraysvyohyke_row['nimitys']}'>{$keraysvyohyke_row['nimitys']}</option>";
			}

			echo "</select>";
			echo "</th>";

			echo "<th class='sort_row_by' id='row_batch__{$row['lahdon_tunnus']}__{$y}'>",t("Erä")," <img class='row_direction_batch' />";
			echo "<br />";
			echo "<input type='text' class='filter_row_by_text' id='child_row_text_batch__{$row['lahdon_tunnus']}__{$y}' value='' size='3' />";
			echo "</th>";

			echo "<th class='sort_row_by' id='row_rows__{$row['lahdon_tunnus']}__{$y}'>R/K <img class='row_direction_rows' /></th>";
			echo "<th class='sort_row_by' id='row_sscc__{$row['lahdon_tunnus']}__{$y}'>",t("SSCC")," <img class='row_direction_sscc' /></th>";
			echo "<th class='sort_row_by' id='row_package__{$row['lahdon_tunnus']}__{$y}'>",t("Pakkaus")," <img class='row_direction_package' /></th>";
			echo "<th class='sort_row_by' id='row_weight__{$row['lahdon_tunnus']}__{$y}'>",t("Paino")," <img class='row_direction_weight' /></th>";
			echo "</tr>";

			mysql_data_seek($lahto_res, 0);

			$x = 0;

			$type_array = array(
				"N" => t("Normaalitilaus"),
				"E" => t("Ennakkotilaus"),
				"T" => t("Tarjoustilaus"),
				"2" => t("Varastotäydennys"),
				"7" => t("Tehdastilaus"),
				"8" => t("Muiden mukana"),
				"A" => t("Työmääräys"),
				"S" => t("Sarjatilaus")
			);

			while ($lahto_row = mysql_fetch_assoc($lahto_res)) {

				echo "<tr class='toggleable_row_tr' id='toggleable_row_tr_{$lahto_row['tilauksen_tunnus']}__{$x}'>";

				echo "<td>";

				if ($onko_paivitysoikeuksia_ohjelmaan) {
					echo "<input type='checkbox' class='checkbox_{$lahto_row['tilauksen_tunnus']}' name='checkbox_child[{$lahto_row['tilauksen_tunnus']}]' value='{$lahto_row['tilauksen_tunnus']}' id='checkbox_{$lahto_row['tilauksen_tunnus']}__{$x}'>";
				}

				echo "</td>";

				$status = $status_text = '';

				if (preg_match("/(K|X)/", $lahto_row['tilat'])) {
					$status_text = t("Aloitettu");
					$status = 2;
				}
				elseif ($lahto_row['keraysera_rivi_count'] > 0 and $lahto_row['keraysera_rivi_count'] == $lahto_row['keraysera_rivi_valmis']) {
					$status_text = t("Kerätty");
					$status = 3;
				}
				else {
					$status_text = t("Aloittamatta");
					$status = 1;
				}

				echo "<td class='data toggleable_row_status' id='{$status}__{$lahto_row['tilauksen_tunnus']}__{$x}'>{$status_text}</td>";

				if ($lahto_row['vakisin_kerays'] != '') {
					echo "<td class='data toggleable_row_manual' id='{$lahto_row['vakisin_kerays']}__{$lahto_row['tilauksen_tunnus']}__{$x}'>{$lahto_row['vakisin_kerays']}</td>";
				}
				else {
					echo "<td class='data toggleable_row_manual' id='!__{$lahto_row['tilauksen_tunnus']}__{$x}'>&nbsp;</td>";
				}

				if ($lahto_row['toimitustavan_lahto_siirto'] != '') {
					echo "<td class='data toggleable_row_transfer' id='{$lahto_row['toimitustavan_lahto_siirto']}__{$lahto_row['tilauksen_tunnus']}__{$x}'>{$lahto_row['toimitustavan_lahto_siirto']}</td>";
				}
				else {
					echo "<td class='data toggleable_row_transfer' id='!__{$lahto_row['tilauksen_tunnus']}__{$x}'>&nbsp;</td>";
				}

				echo "<td class='center toggleable_row_prio' id='{$lahto_row['prioriteetti']}__{$lahto_row['tilauksen_tunnus']}__{$x}'>{$lahto_row['prioriteetti']}</td>";
				echo "<td class='toggleable_row_order' id='{$lahto_row['tilauksen_tunnus']}__{$x}'><button type='button'>{$lahto_row['tilauksen_tunnus']}</button></td>";
				echo "<td class='toggleable_row_orderold' id='{$lahto_row['tilauksen_vanhatunnus']}__{$x}'>{$lahto_row['tilauksen_vanhatunnus']}</td>";

				echo "<td class='data toggleable_row_type' id='{$lahto_row['tilauksen_tilaustyyppi']}__{$lahto_row['tilauksen_tunnus']}__{$x}'>{$type_array[$lahto_row['tilauksen_tilaustyyppi']]}</td>";

				if (trim($lahto_row['ohjausmerkki']) == '') {
					echo "<td class='toggleable_row_control' id='!__{$lahto_row['tilauksen_tunnus']}__{$x}'>{$lahto_row['ohjausmerkki']}</td>";
				}
				else {
					echo "<td class='toggleable_row_control' id='{$lahto_row['ohjausmerkki']}__{$lahto_row['tilauksen_tunnus']}__{$x}'>{$lahto_row['ohjausmerkki']}</td>";
				}

				echo "<td class='data toggleable_row_clientprio' id='{$lahto_row['asiakas_luokka']}__{$lahto_row['tilauksen_tunnus']}__{$x}'>{$lahto_row['asiakas_luokka']}</td>";

				echo "<td class='data toggleable_row_client' id='{$lahto_row['asiakas_nimi']}__{$lahto_row['tilauksen_tunnus']}__{$x}'>{$lahto_row['asiakas_nimi']}";
				if ($lahto_row['asiakas_nimi'] != $lahto_row['asiakas_toim_nimi']) echo " {$lahto_row['asiakas_toim_nimi']}";
				echo "</td>";

				echo "<td class='toggleable_row_locality' id='{$lahto_row['asiakas_toim_postitp']}__{$lahto_row['tilauksen_tunnus']}__{$x}'>{$lahto_row['asiakas_toim_postitp']}</td>";

				if ($lahto_row['sscc'] != '') {
					$joinilisa = "	JOIN kerayserat ON (kerayserat.yhtio = tilausrivi.yhtio AND kerayserat.tilausrivi = tilausrivi.tunnus AND kerayserat.sscc = '{$lahto_row['sscc']}' AND kerayserat.nro = '{$lahto_row['erat']}')";
					$selectilisa = "COUNT(kerayserat.tunnus) AS 'rivit',";
				}
				else {
					$joinilisa = "";
					$selectilisa = "COUNT(tilausrivi.tunnus) AS 'rivit',";
				}

				$query = "	SELECT keraysvyohyke.nimitys AS 'keraysvyohyke',
							tuoteperhe.ohita_kerays AS 'ohitakerays',
							{$selectilisa}
							SUM(IF(tilausrivi.kerattyaika != '0000-00-00 00:00:00', 1, 0)) AS 'keratyt',
							ROUND(SUM(tilausrivi.varattu * tuote.tuotemassa), 0) AS 'kg'
							FROM tilausrivi
							JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
							JOIN varaston_hyllypaikat vh ON (vh.yhtio = tilausrivi.yhtio AND vh.hyllyalue = tilausrivi.hyllyalue AND vh.hyllynro = tilausrivi.hyllynro AND vh.hyllyvali = tilausrivi.hyllyvali AND vh.hyllytaso = tilausrivi.hyllytaso)
							JOIN keraysvyohyke ON (keraysvyohyke.yhtio = tuote.yhtio AND keraysvyohyke.tunnus = vh.keraysvyohyke)
							{$joinilisa}
							LEFT JOIN tuoteperhe ON (tuoteperhe.yhtio = tilausrivi.yhtio AND tuoteperhe.tuoteno = tilausrivi.tuoteno AND tuoteperhe.tyyppi = 'P' AND tuoteperhe.ohita_kerays != '')
							WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
							AND tilausrivi.otunnus = '{$lahto_row['tilauksen_tunnus']}'
							AND tilausrivi.tyyppi != 'D'
							AND tilausrivi.var not in ('P','J')
							{$ei_lapsia_lisa}
							GROUP BY 1,2
							HAVING ohitakerays IS NULL";
				$til_res = pupe_query($query);
				$til_row = mysql_fetch_assoc($til_res);

				echo "<td class='data toggleable_row_picking_zone' id='{$til_row['keraysvyohyke']}__{$lahto_row['tilauksen_tunnus']}__{$x}'>{$til_row['keraysvyohyke']}</td>";

				echo "<td class='data toggleable_row_batch' id='{$lahto_row['erat']}__{$lahto_row['tilauksen_tunnus']}__{$x}'>{$lahto_row['erat']}</td>";

				echo "<td class='toggleable_row_rows' id='{$til_row['rivit']}__{$lahto_row['tilauksen_tunnus']}__{$x}'>{$til_row['rivit']} / {$til_row['keratyt']}</td>";

				echo "<td class='data toggleable_row_sscc' id='{$lahto_row['sscc']}__{$lahto_row['tilauksen_tunnus']}__{$x}'>";

				if (trim($lahto_row['sscc_ulkoinen']) != '' and trim($lahto_row['sscc_ulkoinen']) != 0) {
					echo "<button type='button'>{$lahto_row['sscc_ulkoinen']}</button>";
				}
				elseif ($lahto_row['sscc'] != '') {
					echo "<button type='button'>{$lahto_row['sscc']}</button>";
				}

				echo "</td>";

				if ($lahto_row['pakkausnro'] != '') {

					$query = "	SELECT pakkaus.pakkauskuvaus,
								pakkaus.oma_paino,
								ROUND((kerayserat.kpl * tuote.tuotemassa), 0) AS 'kg'
								FROM kerayserat
								JOIN pakkaus ON (pakkaus.yhtio = kerayserat.yhtio AND pakkaus.tunnus = kerayserat.pakkaus)
								JOIN tilausrivi ON (tilausrivi.yhtio = kerayserat.yhtio AND tilausrivi.tunnus = kerayserat.tilausrivi AND tilausrivi.tyyppi != 'D' AND tilausrivi.var not in ('P','J') {$ei_lapsia_lisa})
								JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
								WHERE kerayserat.yhtio = '{$kukarow['yhtio']}'
								AND kerayserat.sscc = '{$lahto_row['sscc']}'
								AND kerayserat.pakkausnro = '{$lahto_row['pakkausnro']}'
								ORDER BY kerayserat.sscc";
					$pakkauskuvaus_res = pupe_query($query);
					$pakkauskuvaus_row = mysql_fetch_assoc($pakkauskuvaus_res);

					echo "<td class='data toggleable_row_package' id='{$pakkauskuvaus_row['pakkauskuvaus']}__{$lahto_row['tilauksen_tunnus']}__{$x}'>{$pakkauskuvaus_row['pakkauskuvaus']}</td>";

					$kg = $pakkauskuvaus_row['kg'] + $pakkauskuvaus_row['oma_paino'];

					while ($pakkauskuvaus_row = mysql_fetch_assoc($pakkauskuvaus_res)) {
						$kg += $pakkauskuvaus_row['kg'];
					}

					$kg = round($kg, 0);

					echo "<td class='toggleable_row_weight' id='{$kg}__{$lahto_row['tilauksen_tunnus']}__{$x}'>{$kg}</td>";
				}
				else {
					echo "<td class='data toggleable_row_package' id='!__{$lahto_row['tilauksen_tunnus']}__{$x}'></td>";
					echo "<td class='toggleable_row_weight' id='{$til_row['kg']}__{$lahto_row['tilauksen_tunnus']}__{$x}'>{$til_row['kg']}</td>";
				}

				echo "</tr>";

				$colspan_child = 18;

				$query = "	SELECT tilausrivi.tuoteno,
							IF(kerayserat.sscc_ulkoinen != '', kerayserat.sscc_ulkoinen, kerayserat.sscc) AS sscc,
							tuote.nimitys,
							ROUND(IFNULL(kerayserat.kpl, tilausrivi.varattu), 0) AS 'suunniteltu',
							tilausrivi.yksikko,
							CONCAT(tilausrivi.hyllyalue,'-',tilausrivi.hyllynro,'-',tilausrivi.hyllyvali,'-',tilausrivi.hyllytaso) AS 'hyllypaikka',
							kerayserat.laatija AS 'keraaja',
							tilausrivi.kerattyaika,
							#IF(tilausrivi.kerattyaika != '0000-00-00 00:00:00', kerayserat.kpl, 0) AS 'keratyt'
							ROUND(IF(tilausrivi.kerattyaika != '0000-00-00 00:00:00', IFNULL(kerayserat.kpl_keratty, tilausrivi.varattu), 0), 0) AS 'keratyt'
							FROM tilausrivi
							LEFT JOIN kerayserat ON (kerayserat.yhtio = tilausrivi.yhtio AND kerayserat.tilausrivi = tilausrivi.tunnus and kerayserat.tila != 'Ö')
							JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
							WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
							AND tilausrivi.otunnus = '{$lahto_row['tilauksen_tunnus']}'
							AND tilausrivi.tyyppi != 'D'
							AND tilausrivi.var not in ('P','J')
							{$ei_lapsia_lisa}
							ORDER BY kerayserat.sscc, tilausrivi.tuoteno";
				$rivi_res = pupe_query($query);

				echo "<tr class='toggleable_row_child_order_{$lahto_row['tilauksen_tunnus']}__{$x}' id='toggleable_row_child_order_{$lahto_row['tilauksen_tunnus']}__{$x}'>";
				echo "<td colspan='{$colspan_child}' class='back' style='display:none;'>";
				echo "<div class='toggleable_row_child_div_order' id='toggleable_row_order_{$lahto_row['tilauksen_tunnus']}__{$x}' style='display:none;'>";

				echo "<table style='width:100%;'>";

				echo "<tr>";
				echo "<th>",t("SSCC"),"</th>";
				echo "<th>",t("Tuotenumero"),"</th>";
				echo "<th>",t("Nimitys"),"</th>";
				echo "<th>",t("Suunniteltu määrä"),"</th>";
				echo "<th>",t("Kerätty määrä"),"</th>";
				echo "<th>",t("Poikkeava määrä"),"</th>";
				echo "<th>",t("Yksikkö"),"</th>";
				echo "<th>",t("Hyllypaikka"),"</th>";
				echo "<th>",t("Kerääjä"),"</th>";
				echo "</tr>";

				$lopetus_url = trim($lopetus) != '' ? $lopetus."/SPLIT/{$palvelin2}tilauskasittely/lahtojen_hallinta.php////select_varasto={$select_varasto}//tee=lahto//tilaukset={$tilaukset}" : "{$palvelin2}tilauskasittely/lahtojen_hallinta.php////select_varasto={$select_varasto}//tee=lahto//tilaukset={$tilaukset}";

				while ($rivi_row = mysql_fetch_assoc($rivi_res)) {

					echo "<tr>";
					echo "<td class='tumma'>{$rivi_row['sscc']}</td>";
					echo "<td class='tumma'><a href='{$palvelin2}tuvar.php?toim=&tee=Z&tuoteno=".urlencode($rivi_row["tuoteno"])."&lopetus={$lopetus_url}'>{$rivi_row['tuoteno']}</a></td>";
					echo "<td class='tumma'>{$rivi_row['nimitys']}</td>";
					echo "<td class='tumma'>{$rivi_row['suunniteltu']}</td>";
					echo "<td class='tumma'>{$rivi_row['keratyt']}</td>";
					echo "<td class='tumma'>";

					if ($rivi_row['kerattyaika'] != '0000-00-00 00:00:00' and $rivi_row['keratyt'] - $rivi_row['suunniteltu'] != 0) {
						echo ($rivi_row['keratyt'] - $rivi_row['suunniteltu']);
					}

					echo "</td>";
					echo "<td class='tumma'>",t_avainsana("Y", "", " and avainsana.selite='{$rivi_row['yksikko']}'", "", "", "selite"),"</td>";
					echo "<td class='tumma'>{$rivi_row['hyllypaikka']}</td>";
					echo "<td class='tumma'>{$rivi_row['keraaja']}</td>";
					echo "</tr>";
				}

				echo "</table>";

				echo "</div>";
				echo "</td>";
				echo "</tr>";

				if ($lahto_row['sscc'] != '') {

					$query = "	SELECT tilausrivi.tuoteno,
								kerayserat.otunnus,
								tuote.nimitys,
								ROUND(IFNULL(kerayserat.kpl, tilausrivi.varattu), 0) AS 'suunniteltu',
								tilausrivi.yksikko,
								CONCAT(tilausrivi.hyllyalue,'-',tilausrivi.hyllynro,'-',tilausrivi.hyllyvali,'-',tilausrivi.hyllytaso) AS hyllypaikka,
								kerayserat.laatija AS keraaja,
								tilausrivi.kerattyaika,
								ROUND(IF(tilausrivi.kerattyaika != '0000-00-00 00:00:00', IFNULL(kerayserat.kpl_keratty, tilausrivi.varattu), 0), 0) AS 'keratyt'
								FROM kerayserat
								JOIN tilausrivi ON (tilausrivi.yhtio = kerayserat.yhtio AND tilausrivi.tunnus = kerayserat.tilausrivi AND tilausrivi.tyyppi != 'D' AND tilausrivi.var not in ('P','J') {$ei_lapsia_lisa})
								JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
								WHERE kerayserat.yhtio = '{$kukarow['yhtio']}'
								AND kerayserat.sscc = '{$lahto_row['sscc']}'
								ORDER BY kerayserat.otunnus, tilausrivi.tuoteno";
					$rivi_res = pupe_query($query);

					echo "<tr class='toggleable_row_child_sscc_{$lahto_row['tilauksen_tunnus']}__{$x}' id='toggleable_row_child_sscc_{$lahto_row['tilauksen_tunnus']}__{$x}'>";
					echo "<td colspan='{$colspan_child}' class='back' style='display:none;'>";
					echo "<div class='toggleable_row_child_div_sscc' id='toggleable_row_sscc_{$lahto_row['sscc']}__{$x}' style='display:none;'>";

					echo "<table style='width:100%;'>";

					echo "<tr>";
					echo "<th>",t("Tilausnumero"),"</th>";
					echo "<th>",t("Tuotenumero"),"</th>";
					echo "<th>",t("Nimitys"),"</th>";
					echo "<th>",t("Suunniteltu määrä"),"</th>";
					echo "<th>",t("Kerätty määrä"),"</th>";
					echo "<th>",t("Poikkeava määrä"),"</th>";
					echo "<th>",t("Yksikkö"),"</th>";
					echo "<th>",t("Hyllypaikka"),"</th>";
					echo "<th>",t("Kerääjä"),"</th>";
					echo "</tr>";

					$lopetus_url = trim($lopetus) != '' ? $lopetus."/SPLIT/{$palvelin2}tilauskasittely/lahtojen_hallinta.php////select_varasto={$select_varasto}//tee=lahto//tilaukset={$tilaukset}" : "{$palvelin2}tilauskasittely/lahtojen_hallinta.php////select_varasto={$select_varasto}//tee=lahto//tilaukset={$tilaukset}";

					while ($rivi_row = mysql_fetch_assoc($rivi_res)) {

						echo "<tr>";
						echo "<td class='tumma'>{$rivi_row['otunnus']}</td>";
						echo "<td class='tumma'><a href='{$palvelin2}tuvar.php?toim=&tee=Z&tuoteno=".urlencode($rivi_row["tuoteno"])."&lopetus={$lopetus_url}'>{$rivi_row['tuoteno']}</a></td>";
						echo "<td class='tumma'>{$rivi_row['nimitys']}</td>";
						echo "<td class='tumma'>{$rivi_row['suunniteltu']}</td>";
						echo "<td class='tumma'>{$rivi_row['keratyt']}</td>";

						echo "<td class='tumma'>";

						if ($rivi_row['kerattyaika'] != '0000-00-00 00:00:00' and $rivi_row['keratyt'] - $rivi_row['suunniteltu'] != 0) {
							echo ($rivi_row['keratyt'] - $rivi_row['suunniteltu']);
						}

						echo "</td>";

						echo "<td class='tumma'>",t_avainsana("Y", "", " and avainsana.selite='{$rivi_row['yksikko']}'", "", "", "selite"),"</td>";
						echo "<td class='tumma'>{$rivi_row['hyllypaikka']}</td>";
						echo "<td class='tumma'>{$rivi_row['keraaja']}</td>";
						echo "</tr>";
					}

					echo "</table>";

					echo "</div>";
					echo "</td>";
					echo "</tr>";
				}

				$x++;
			}

			echo "</table>";

			echo "</div>";
			echo "</td>";
			echo "</tr>";

			$y++;
		}

		echo "</table>";
		echo "</form>";
	}

	require ("inc/footer.inc");
