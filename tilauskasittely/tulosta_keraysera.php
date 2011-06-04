<?php

	require ("../inc/parametrit.inc");

	js_popup();

	echo "<font class='head'>",t("Tulosta keräyserä"),"</font><hr>";

	echo "	<script type='text/javascript' language='JavaScript'>
				$(document).ready(function() {

					$(':checkbox').click(function(event){
						event.stopPropagation();
					});

					$('.toggleable').click(function(event){

						if ($('#toggleable_'+this.id).is(':visible')) {
							$('#toggleable_'+this.id).fadeOut('fast');
						}
						else {
							$('#toggleable_'+this.id).fadeIn('fast');
						}
					});
				});
			</script>";

	if (!isset($tee)) $tee = '';

	if ($tee == '') {
		echo "<form method='post'>";
		echo "<input type='hidden' name='tee' value='selaa' />";

		echo "<table>";
		echo "<tr><th>",t("Kerääjä"),"</th><td><input type='text' size='5' name='keraajanro'> ",t("tai")," ";
		echo "<select name='keraajalist'>";

		$query = "	SELECT *
					from kuka
					where yhtio = '{$kukarow['yhtio']}'
					and extranet = ''
					and (keraajanro > 0 or kuka = '{$kukarow['kuka']}')
					and keraysvyohyke != ''";
		$kuresult = pupe_query($query);

		while ($kurow = mysql_fetch_assoc($kuresult)) {

			$selker = "";

			if ($keraajalist == "" and $kurow["kuka"] == $kukarow["kuka"]) {
				$selker = "SELECTED";
			}
			elseif ($keraajalist == $kurow["kuka"]) {
				$selker = "SELECTED";
			}

			echo "<option value='{$kurow['kuka']}' {$selker}>{$kurow['nimi']}</option>";
		}
		echo "</select>&nbsp;<input type='submit' value='",t("Valitse"),"' /></td>";
		echo "</tr>";
		echo "</table>";
		echo "</form>";
	}

	if ($tee == 'uusi_pakkaus') {

		if (isset($kerayseranro) and trim($kerayseranro) > 0) {

			// emuloidaan transactioita mysql LOCK komennolla
			$query = "LOCK TABLES avainsana WRITE";
			$res   = pupe_query($query);

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
							yhtio = '{$kukarow['yhtio']}',
							perhe = '{$max_perhe_row['perhe']}',
							kieli = '{$kukarow['kieli']}',
							laji = 'SSCC',
							nakyvyys = '',
							selite = '{$sscc}',
							selitetark = '',
							selitetark_2 = '',
							selitetark_3 = '',
							jarjestys = 0,
							laatija = '{$kukarow['kuka']}',
							luontiaika = now(),
							muutospvm = now(),
							muuttaja = '{$kukarow['kuka']}'";
				$insert_res = pupe_query($query);
			}
			else {
				$query = "UPDATE avainsana SET selite = '{$sscc}' WHERE yhtio = '{$kukarow['yhtio']}' AND laji='SSCC'";
				$update_res = pupe_query($query);
			}

			// poistetaan lukko
			$query = "UNLOCK TABLES";
			$res   = pupe_query($query);

			$query = "SELECT tila, (MAX(pakkausnro) + 1) uusi_pakkauskirjain FROM kerayserat WHERE yhtio = '{$kukarow['yhtio']}' AND nro = '{$kerayseranro}' GROUP BY 1";
			$uusi_paknro_res = pupe_query($query);
			$uusi_paknro_row = mysql_fetch_assoc($uusi_paknro_res);

			$query = "	INSERT INTO kerayserat SET
						yhtio = '{$kukarow['yhtio']}',
						nro = '{$kerayseranro}',
						tila = '{$uusi_paknro_row['tila']}',
						sscc = '{$sscc}',
						otunnus = 0,
						tilausrivi = 0,
						pakkaus = 0,
						pakkausnro = '{$uusi_paknro_row['uusi_pakkauskirjain']}',
						kpl = 0,
						laatija = '{$kukarow['kuka']}',
						luontiaika = now(),
						muutospvm = now(),
						muuttaja = '{$kukarow['kuka']}'";
			$ins_uusi_pak_res = pupe_query($query);

			echo "<br /><font class='message'>",t("Uusi pakkaus lisätty"),"!</font><br />";
		}

		$tee = 'muokkaa';
		$view = 'yes';
	}

	if ($tee == 'muuta') {
		if (trim($kayttaja) != '') {
			list($nro, $kayttaja) = explode('##', $kayttaja);
	
			$query = "UPDATE kerayserat SET laatija = '{$kayttaja}' WHERE yhtio = '{$kukarow['yhtio']}' AND nro = '{$nro}'";
			$update_res = pupe_query($query);
		}
	
		if (count($pakkaukset) > 0) {

			foreach ($pakkaukset as $pak_nro => $pak) {
				list($rivitunnus, $pak_nro) = explode('##', $pak_nro);
				list($rivitunnus, $pakkaus) = explode('##', $pak);

				$query = "	UPDATE kerayserat SET 
							pakkaus = '{$pakkaus}' 
							WHERE yhtio = '{$kukarow['yhtio']}' 
							AND pakkausnro = '{$pak_nro}'";
				$update_res = pupe_query($query);

				$query = "SELECT tunnus FROM kerayserat WHERE yhtio = '{$kukarow['yhtio']}' AND pakkausnro = '{$pak_nro}'";
				$rivitunnukset_res = pupe_query($query);

				while ($rivitunnukset_row = mysql_fetch_assoc($rivitunnukset_res)) {

					if (isset($poista_pakkaus[$pak_nro]) and trim($poista_pakkaus[$pak_nro]) != '') {
						$query = "DELETE FROM kerayserat WHERE yhtio = '{$kukarow['yhtio']}' AND nro = '{$poista_pakkaus[$pak_nro]}' AND pakkausnro = '{$pak_nro}'";
						$del_res = pupe_query($query);

						continue;
					}

					if (isset($siirra_tuote[$rivitunnukset_row['tunnus']]) and trim($siirra_tuote[$rivitunnukset_row['tunnus']]) != '' and isset($siirra_kpl[$rivitunnukset_row['tunnus']]) and trim($siirra_kpl[$rivitunnukset_row['tunnus']]) != '') {

						$mihin_siirretaan = $siirra_tuote[$rivitunnukset_row['tunnus']];
						$paljon_siirretaan = str_replace(",", ".", $siirra_kpl[$rivitunnukset_row['tunnus']]);

						$query = "SELECT kpl, otunnus, tilausrivi, nro, tila, sscc, pakkaus FROM kerayserat WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$rivitunnukset_row['tunnus']}'";
						$kpl_chk_res = pupe_query($query);
						$kpl_chk_row = mysql_fetch_assoc($kpl_chk_res);

						if ($paljon_siirretaan > $kpl_chk_row['kpl']) {
							echo "<br /><font class='error'>",t("Siirrettävä kappalemäärä oli liian suuri"),"! ({$paljon_siirretaan} > {$kpl_chk_row['kpl']})</font><br />";
						}
						else {
							// katsotaan onko nollarivejä (eli tyhjä pakkaus)
							$query = "	SELECT * 
										FROM kerayserat 
										WHERE yhtio = '{$kukarow['yhtio']}' 
										AND nro = '{$kpl_chk_row['nro']}' 
										AND pakkausnro = '{$mihin_siirretaan}'";
							$chk_res = pupe_query($query);
							$chk_row = mysql_fetch_assoc($chk_res);

							if ($chk_row['otunnus'] == 0 or $chk_row['tilausrivi'] == 0 or $chk_row['kpl'] == 0) {
								$query = "SELECT * FROM kerayserat WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$rivitunnukset_row['tunnus']}'";
								$fill_res = pupe_query($query);
								$fill_row = mysql_fetch_assoc($fill_res);

								$query = "	UPDATE kerayserat SET
											otunnus = '{$fill_row['otunnus']}',
											tilausrivi = '{$fill_row['tilausrivi']}',
											kpl = '{$paljon_siirretaan}'
											WHERE yhtio = '{$kukarow['yhtio']}'
											AND tunnus = '{$chk_row['tunnus']}'";
								$upd_res = pupe_query($query);
							}
							else {

								$query = "	SELECT tuoteno
											FROM tilausrivi
											WHERE yhtio = '{$kukarow['yhtio']}'
											AND tunnus = '{$kpl_chk_row['tilausrivi']}'";
								$kassellaan_res = pupe_query($query);
								$kassellaan_row = mysql_fetch_assoc($kassellaan_res);

								mysql_data_seek($chk_res, 0);
								$loytyko = '';

								while ($chk_row = mysql_fetch_assoc($chk_res)) {
									$query = "	SELECT tuoteno
												FROM tilausrivi
												WHERE yhtio = '{$kukarow['yhtio']}'
												AND tunnus = '{$chk_row['tilausrivi']}'";
									$check_it_res = pupe_query($query);
									$check_it_row = mysql_fetch_assoc($check_it_res);

									if ($kassellaan_row['tuoteno'] == $check_it_row['tuoteno']) {
										$query = "	UPDATE kerayserat SET
													kpl = kpl + {$paljon_siirretaan}
													WHERE yhtio = '{$kukarow['yhtio']}'
													AND tunnus = '{$chk_row['tunnus']}'";
										$upd_res = pupe_query($query);
										$loytyko = 'joo';
										break;
									}
								}

								if (!$loytyko) {
									mysql_data_seek($chk_res, 0);

									$query = "	INSERT INTO kerayserat SET
												yhtio = '{$kukarow['yhtio']}',
												nro = '{$kpl_chk_row['nro']}',
												tila = '{$kpl_chk_row['tila']}',
												sscc = '{$kpl_chk_row['sscc']}',
												otunnus = '{$kpl_chk_row['otunnus']}',
												tilausrivi = '{$kpl_chk_row['tilausrivi']}',
												pakkaus = '{$kpl_chk_row['pakkaus']}',
												pakkausnro = '{$mihin_siirretaan}',
												kpl = '{$paljon_siirretaan}',
												laatija = '{$who}',
												luontiaika = now(),
												muutospvm = now(),
												muuttaja = '{$who}'";
									$ins_res = pupe_query($query);
								}
							}

							if (($kpl_chk_row['kpl'] - $paljon_siirretaan) == 0) {
								$query = "DELETE FROM kerayserat WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$rivitunnukset_row['tunnus']}'";
								$del_res = pupe_query($query);
							}
							else {
								$query = "UPDATE kerayserat SET kpl = kpl - {$paljon_siirretaan} WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$rivitunnukset_row['tunnus']}'";
								$upd_res = pupe_query($query);
							}
						}
					}
				}

				if (isset($tulostettavat_reittietiketit[$pak_nro]) and count($tulostettavat_reittietiketit) > 0 and trim($tulostettavat_reittietiketit[$pak_nro]) != '') {

					$query = "	SELECT kerayserat.tilausrivi,
								lasku.toimitustapa, lasku.nimi, lasku.nimitark, lasku.osoite, lasku.postino, lasku.postitp, lasku.viesti, lasku.sisviesti2,
								kerayserat.sscc,
								COUNT(kerayserat.tunnus) AS rivit,
								ROUND(SUM(tuote.tuotemassa), 2) AS paino,
								ROUND(SUM(tuote.tuoteleveys * tuote.tuotekorkeus * tuote.tuotesyvyys), 4) AS tilavuus
								FROM kerayserat
								JOIN lasku ON (lasku.yhtio = kerayserat.yhtio AND lasku.tunnus = kerayserat.otunnus)
								JOIN tilausrivi ON (tilausrivi.yhtio = kerayserat.yhtio AND tilausrivi.tunnus = kerayserat.tilausrivi)
								JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
								WHERE kerayserat.yhtio = '{$kukarow['yhtio']}'
								AND kerayserat.pakkausnro = '{$pak_nro}'
								AND kerayserat.nro = '{$tulostettavat_reittietiketit[$pak_nro]}'
								GROUP by 1,2,3,4,5,6,7,8,9";
					$reittietiketti_res = pupe_query($query);
					$reittietiketti_row = mysql_fetch_assoc($reittietiketti_res);

					$pakkaus_kirjain = chr((64+$pak_nro));

					$params = array(
						'tilriv' => $reittietiketti_row['tilausrivi'],
						'pakkaus_kirjain' => $pakkaus_kirjain,
						'sscc' => $reittietiketti_row['sscc'],
						'toimitustapa' => $reittietiketti_row['toimitustapa'],
						'rivit' => $reittietiketti_row['rivit'],
						'paino' => $reittietiketti_row['paino'],
						'tilavuus' => $reittietiketti_row['tilavuus'],
						'lask_nimi' => $reittietiketti_row['nimi'],
						'lask_nimitark' => $reittietiketti_row['nimitark'],
						'lask_osoite' => $reittietiketti_row['osoite'],
						'lask_postino' => $reittietiketti_row['postino'],
						'lask_postitp' => $reittietiketti_row['postitp'],
						'lask_viite' => $reittietiketti_row['viesti'],
						'lask_merkki' => $reittietiketti_row['sisviesti2'],
						'komento_reittietiketti' => $komento['reittietiketti'],
					);

					tulosta_reittietiketti($params);
					
					if (trim($komento['reittietiketti']) != '') {
						echo t("Reittietiketti tulostuu"),"...<br />";
					}
					else {
						echo t("Reittietiketin tulostinta ei ole valittu. Reittietiketti ei tulostu"),"...<br />";
					}
				}
			}
		}

		$tee = 'muokkaa';
		$view = 'yes';
	}

	if ($tee != '') {

		if ((int) $keraajanro > 0) {
			$query = "	SELECT *
						from kuka
						where yhtio = '{$kukarow['yhtio']}'
						and keraajanro = '{$keraajanro}'";

		}
		else {
			$query = "	SELECT *
						from kuka
						where yhtio = '{$kukarow['yhtio']}'
						and kuka = '{$keraajalist}'";
		}
		$result = pupe_query($query);

		if (mysql_num_rows($result) == 0) {
			echo "<font class='error'>",t("Kerääjää")," {$keraajanro} ",t("ei löydy"),"!</font><br>";
		}
		else {

			$keraaja = mysql_fetch_assoc($result);
			$who = $keraaja['kuka'];

			$query = "	SELECT keraysvyohyke
						FROM kuka
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND kuka = '{$kukarow['kuka']}'";
			$keraysvyohyke_res = pupe_query($query);
			$keraysvyohyke_row = mysql_fetch_assoc($keraysvyohyke_res);

			$keraysvyohyke = $keraysvyohyke_row['keraysvyohyke'];

			echo "<form method='post'>";

			if ($tee != 'muuta' and $tee != 'muokkaa') {
				echo "<input type='hidden' name='tee' value='keraysera' />";
			}

			echo "<input type='hidden' name='keraysvyohyke' value='{$keraysvyohyke}' />";
			echo "<input type='hidden' name='keraajanro' value='{$keraajanro}' />";
			echo "<input type='hidden' name='keraajalist' value='{$keraajalist}' />";
			echo "<input type='hidden' name='who' value='{$who}' />";

			echo "<table><tr><th>",t("Valitse reittietiketin tulostin"),"</th><td>";
			echo "<select name='komento[reittietiketti]'>";
			echo "<option value=''>",t("Ei kirjoitinta"),"</option>";

			$querykieli = "	SELECT DISTINCT kirjoittimet.kirjoitin, kirjoittimet.komento, keraysvyohyke.tunnus, keraysvyohyke.printteri8, kirjoittimet.tunnus as kir_tunnus
							FROM kirjoittimet
							JOIN keraysvyohyke ON (keraysvyohyke.yhtio = kirjoittimet.yhtio AND keraysvyohyke.tunnus IN ({$kukarow['keraysvyohyke']}))
							JOIN kuka ON (kuka.yhtio = keraysvyohyke.yhtio AND kuka.keraysvyohyke = keraysvyohyke.tunnus AND kuka.kuka = '{$who}')
							WHERE kirjoittimet.yhtio = '{$kukarow['yhtio']}'
							GROUP BY 1,2,3,4,5
							ORDER BY kirjoittimet.kirjoitin";
			$kires = pupe_query($querykieli);

			while ($kirow = mysql_fetch_assoc($kires)) {

				$sel = "";
				if ($kirow["tunnus"] == $kukarow["keraysvyohyke"] and $kirow['kir_tunnus'] == $kirow['printteri8']) {
					$sel = " selected";
				}

				echo "<option value='{$kirow['komento']}' {$sel}>{$kirow['kirjoitin']}</option>";
			}

			echo "</select></td>";

			if ($tee != 'muuta' and $tee != 'muokkaa') {
				echo "<td class='back'>&nbsp;</td>";
			}

			echo "</tr>";

			if ($tee != 'muuta' and $tee != 'muokkaa') {
				echo "<tr><th>",t("Valitse keräyslistan tulostin"),"</th><td>";
				echo "<select name='komento[kerayslista]'>";
				echo "<option value=''>",t("Ei kirjoitinta"),"</option>";

				$querykieli = "	SELECT kirjoittimet.kirjoitin, kirjoittimet.komento, keraysvyohyke.tunnus, keraysvyohyke.printteri0, kirjoittimet.tunnus as kir_tunnus
								FROM kirjoittimet
								JOIN keraysvyohyke ON (keraysvyohyke.yhtio = kirjoittimet.yhtio AND keraysvyohyke.tunnus IN ({$kukarow['keraysvyohyke']}))
								JOIN kuka ON (kuka.yhtio = keraysvyohyke.yhtio AND kuka.keraysvyohyke = keraysvyohyke.tunnus)
								WHERE kirjoittimet.yhtio = '{$kukarow['yhtio']}'
								GROUP BY 1,2,3,4,5
								ORDER BY kirjoittimet.kirjoitin";
				$kires = pupe_query($querykieli);

				while ($kirow = mysql_fetch_assoc($kires)) {

					$sel = "";
					if ($kirow["tunnus"] == $kukarow["keraysvyohyke"] and $kirow['kir_tunnus'] == $kirow['printteri0']) {
						$sel = " selected";
					}

					echo "<option value='{$kirow['kir_tunnus']}'{$sel}>{$kirow['kirjoitin']}</option>";
				}

				echo "</select></td>";

				echo "<td class='back'><input type='submit' value='",t("Hae keräyserä"),"' /></td>";
			}

			echo "</tr></table>";

			if ($tee != 'muuta' and $tee != 'muokkaa') {
				echo "</form>";
			}

			if ($tee == 'keraysera' and trim($keraysvyohyke) != '') {

				// toimitustavan lähtöjen viikonpäivä
				$viikonpaiva = date('w');

				$pakkaukset = array();
				$max_keraysera_pintaala = 0;
				$max_keraysera_rivit = 0;
				$lahtojen_valinen_enimmaisaika = 0;
				$yhdistelysaanto = '';
				$ulkoinen_jarjestelma = '';

				// esim. muutetaan metrit senteiksi
				$mittakerroin = 100;
				// millä tarkkuudella tuotteita pakataan (esim. 5 cm)
				$resoluutio = 5;

				$query = "	SELECT sallitut_alustat, 
							if(max_keraysera_pintaala * pow({$mittakerroin}, 2) < 1, 1, max_keraysera_pintaala * pow({$mittakerroin}, 2)) max_keraysera_pintaala, 
							max_keraysera_rivit, 
							yhdistelysaanto, 
							lahtojen_valinen_enimmaisaika,
							ulkoinen_jarjestelma
							FROM keraysvyohyke
							JOIN pakkaus ON (pakkaus.yhtio = keraysvyohyke.yhtio AND pakkaus.tunnus IN (keraysvyohyke.sallitut_alustat))
							WHERE keraysvyohyke.yhtio = '{$kukarow['yhtio']}'
							AND keraysvyohyke.tunnus IN ({$keraysvyohyke})";
				$ker_result = pupe_query($query);

				while ($ker_row = mysql_fetch_assoc($ker_result)) {
					$max_keraysera_pintaala = $ker_row['max_keraysera_pintaala'];
					$max_keraysera_rivit = $ker_row['max_keraysera_rivit'];
					$yhdistelysaanto = $ker_row['yhdistelysaanto'];
					$lahtojen_valinen_enimmaisaika = $ker_row['lahtojen_valinen_enimmaisaika'];
					$ulkoinen_jarjestelma = $ker_row['ulkoinen_jarjestelma'];

					$query = "	SELECT pakkaus.tunnus,
								pakkaus.pakkaus,
								pakkaus.paino,
								if(pakkaus.leveys * {$mittakerroin} < 1, 1, pakkaus.leveys * {$mittakerroin}) leveys,
								if(pakkaus.korkeus * {$mittakerroin} < 1, 1, pakkaus.korkeus * {$mittakerroin}) korkeus,
								if(pakkaus.syvyys * {$mittakerroin} < 1, 1, pakkaus.syvyys * {$mittakerroin}) syvyys
								FROM pakkaus
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tunnus IN ({$ker_row['sallitut_alustat']})
								ORDER BY leveys ASC, korkeus ASC, syvyys ASC";
					$pakkaus_result = pupe_query($query);

					while ($pakkaus_row = mysql_fetch_assoc($pakkaus_result)) {
						$pakkaukset[$pakkaus_row['tunnus']]['leveys'] = $pakkaus_row['leveys'];
						$pakkaukset[$pakkaus_row['tunnus']]['korkeus'] = $pakkaus_row['korkeus'];
						$pakkaukset[$pakkaus_row['tunnus']]['syvyys'] = $pakkaus_row['syvyys'];
						$pakkaukset[$pakkaus_row['tunnus']]['paino'] = $pakkaus_row['paino'];
					}
				}

				$query = "	SELECT lasku.tunnus,
							keraysvyohyke.nimitys as keraysvyohyke_nimitys,
							lasku.liitostunnus,
							lasku.nimi as asiakas_nimi,
							SUBSTRING(lasku.h1time, 12, 8) aika,
							asiakas.tunnus as asiakas_tunnus,
							toimitustavan_lahdot.tunnus as lahto,
							(TIME_TO_SEC(TIMEDIFF(lahdon_kellonaika, CURTIME())) / 60) as erotus,
							lasku.hyvaksynnanmuutos as prioriteetti,
							lasku.h1time,
							lasku.sisviesti2,
							keraysvyohyke.tunnus as keraysvyohyke_tunnus,
							MIN(vh.indeksi) as minimi_indeksi,
							SUM(ROUND((if(tuote.tuoteleveys * {$mittakerroin} < 1, 1, tuote.tuoteleveys * {$mittakerroin}) * if(tuote.tuotekorkeus * {$mittakerroin} < 1, 1, tuote.tuotekorkeus * {$mittakerroin}) * if(tuote.tuotesyvyys * {$mittakerroin} < 1, 1, tuote.tuotesyvyys * {$mittakerroin})) * (tilausrivi.varattu+tilausrivi.kpl), 4)) tilauksen_koko
							FROM lasku
							JOIN asiakas ON (asiakas.yhtio = lasku.yhtio AND asiakas.tunnus = lasku.liitostunnus)
							JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus)
							JOIN varaston_hyllypaikat vh ON (vh.yhtio = tilausrivi.yhtio AND vh.hyllyalue = tilausrivi.hyllyalue AND vh.hyllynro = tilausrivi.hyllynro AND vh.hyllyvali = tilausrivi.hyllyvali AND vh.hyllytaso = tilausrivi.hyllytaso)
							JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno AND tuote.keraysvyohyke IN ({$keraysvyohyke}))
							JOIN keraysvyohyke ON (keraysvyohyke.yhtio = lasku.yhtio AND keraysvyohyke.tunnus IN ({$keraysvyohyke}))
							JOIN toimitustapa ON (toimitustapa.yhtio = lasku.yhtio AND toimitustapa.selite = lasku.toimitustapa)
						 	JOIN toimitustavan_lahdot ON (toimitustavan_lahdot.yhtio = toimitustapa.yhtio
															AND toimitustavan_lahdot.liitostunnus = toimitustapa.tunnus
															AND toimitustavan_lahdot.lahdon_kellonaika >= CURTIME()
															AND toimitustavan_lahdot.lahdon_viikonpvm = {$viikonpaiva}
															AND toimitustavan_lahdot.aktiivi = ''
															AND toimitustavan_lahdot.kerailyn_aloitusaika <= CURTIME()
		 												)
							WHERE lasku.yhtio = '{$kukarow['yhtio']}'
							AND lasku.tila = 'N'
							AND lasku.alatila = 'A'
							GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12
							ORDER BY lahto, erotus, lasku.hyvaksynnanmuutos, tilauksen_koko DESC, minimi_indeksi, lasku.h1time ASC, lasku.sisviesti2, keraysvyohyke.nimitys";
				$res = pupe_query($query);

				$debug = false;

				if ($debug) {
					echo "<table>";
					echo "<tr>";
					echo "<th>Tilaus</th>";
					echo "<th>Vyöhyke</th>";
					echo "<th>Max<br>m2</th>";
					echo "<th>Max<br>rivit</th>";
					echo "<th>Aika</th>";
					echo "<th>Tilauksen<br>Koko</th>";
					echo "<th>Asiakas</th>";
					echo "<th>Tilausrivi</th>";
					echo "<th>Tuoteno</th>";
					echo "<th>Tilavuus</th>";
					echo "<th>Yks.<br>koko</th>";
					echo "<th>Yks.<br>maara</th>";
					echo "<th>Yks.<br>paino</th>";
					echo "<th>Yksin</th>";
					echo "</tr>";
				}

				$ulkoisen_jarjestelman_tiedosto = '';

				$kaytettavat_pakkaukset = array();
				$asiakkaan_pakkaukset = array();
				$juokseva_pakkausnro = 1;
				$ed_asiakas = '';

				$ed_juokseva = 0;
				$juokseva_pakkausnro = 0;

				$pakkauksen_paino = 0;

				$rivit = 0;

				$ed_lahto = '';
				$ed_prioriteetti = '';
				$ed_sisviesti2 = 'ÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖ';

				$eran_koko = 0;
				$era_valmis = false;

				$erat = array('tilaukset' => array(), 'pakkaukset' => array());

				while ($row = mysql_fetch_assoc($res)) {

					if ($row['erotus'] > $lahtojen_valinen_enimmaisaika) {
						if ($debug) {
							echo "Lähdön enimmäisaika ylittyi! Let's skip it. ($row[erotus] > $lahtojen_valinen_enimmaisaika)<br>";
							echo "Laskutunnus: $row[tunnus]<br>Asiaskas: $row[asiakas_nimi]<br>Erotus: $row[erotus]<br>Lähtö: $row[lahto]<br>Minimi_indeksi: $row[minimi_indeksi]<br>Tilauksen koko: $row[tilauksen_koko]<br>H1time: $row[h1time]<br>Prioriteetti: $row[prioriteetti]<br><br>";
						}
						continue;
					}

					if ($eran_koko > $max_keraysera_pintaala) {
						if ($debug) {
							echo "Keräyserän maksimiraja ylitetty! $eran_koko / $max_keraysera_pintaala<br>";
						}

						$keraysvyohyke = $row['keraysvyohyke_tunnus'];
						$era_valmis = true;
						break;
					}

					if (trim($ed_prioriteetti) != '' and $ed_prioriteetti != $row['prioriteetti'] and strpos($yhdistelysaanto, 'P') === false and count($kaytettavat_pakkaukset) > 0) {
						if ($debug) {
							echo "EI SAA OLLA USEITA PRIORITEETTEJÄ ALUSTOISSA! KERÄYSERÄ VALMIS<br>";
						}

						$era_valmis = true;
						break;
					}

					$ed_prioriteetti = $row['prioriteetti'];

					if (trim($ed_lahto) != '' and $ed_lahto != $row['lahto'] and strpos($yhdistelysaanto, 'K') === false and count($kaytettavat_pakkaukset) > 0) {
						if ($debug) {
							echo "EI SAA OLLA ALUSTOJA USEISTA LÄHDÖISTÄ! KERÄYSERÄ VALMIS<br>";
						}

						$era_valmis = true;
						break;
					}

					$ed_lahto = $row['lahto'];

					if (trim($ed_asiakas) != '' and $ed_asiakas != $row['liitostunnus']) {
						$asiakkaan_pakkaukset = array();

						if (strpos($yhdistelysaanto, 'S') === false and count($kaytettavat_pakkaukset) > 0) {
							if ($debug) {
								echo "EI SAA OLLA USEITA ASIAKKAITA! KERÄYSERÄ VALMIS<br>";
							}

							$era_valmis = true;
							break;
						}
					}

					$query = "	SELECT tilausrivi.tunnus, tilausrivi.otunnus,
								round(if(tuote.tuotekorkeus * {$mittakerroin} < 1, 1, tuote.tuotekorkeus * {$mittakerroin}) * if(tuote.tuoteleveys * {$mittakerroin} < 1, 1, tuote.tuoteleveys * {$mittakerroin}) * if(tuote.tuotesyvyys * {$mittakerroin} < 1, 1, tuote.tuotesyvyys * {$mittakerroin}) * (tilausrivi.varattu+tilausrivi.kpl), 4) as tuotteen_koko,
								tilausrivi.tuoteno,
								round(if(tuote.tuotekorkeus * {$mittakerroin} < 1, 1, tuote.tuotekorkeus * {$mittakerroin}) * if(tuote.tuoteleveys * {$mittakerroin} < 1, 1, tuote.tuoteleveys * {$mittakerroin}) * if(tuote.tuotesyvyys * {$mittakerroin} < 1, 1, tuote.tuotesyvyys * {$mittakerroin}), 4) y_koko,
								round(if(tuote.tuotekorkeus * {$mittakerroin} < 1, 1, tuote.tuotekorkeus * {$mittakerroin}), 2) tuotekorkeus,
								round(if(tuote.tuoteleveys * {$mittakerroin} < 1, 1, tuote.tuoteleveys * {$mittakerroin}), 2) tuoteleveys,
								round(if(tuote.tuotesyvyys * {$mittakerroin} < 1, 1, tuote.tuotesyvyys * {$mittakerroin}), 2) tuotesyvyys,
								(tilausrivi.varattu+tilausrivi.kpl) y_maara,
								tuote.yksin_kerailyalustalle,
								tuote.tuotemassa y_paino
								FROM tilausrivi
								JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno AND tuote.keraysvyohyke IN ({$keraysvyohyke}))
								JOIN varaston_hyllypaikat vh ON (vh.yhtio = tilausrivi.yhtio AND vh.hyllyalue = tilausrivi.hyllyalue AND vh.hyllynro = tilausrivi.hyllynro AND vh.hyllyvali = tilausrivi.hyllyvali AND vh.hyllytaso = tilausrivi.hyllytaso)
								WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
								AND tilausrivi.otunnus = '{$row['tunnus']}'
								ORDER BY tuote.yksin_kerailyalustalle ASC, y_koko DESC";
					$tuoteres = pupe_query($query);

					while ($tuoterow = mysql_fetch_assoc($tuoteres)) {

						if ($rivit >= $max_keraysera_rivit) {
							if ($debug) {
								echo "RIVEJÄ ON TARPEEKSI KERÄYSERÄÄ VARTEN (rivit: $rivit max_rivit: $max_keraysera_rivit)<br>";
							}

							$era_valmis = true;
							break 2;
						}

						// katsotaan onko tilaus kokonaan jo keräyserässä
						$query = "	SELECT tunnus
									FROM kerayserat
									WHERE yhtio = '{$kukarow['yhtio']}'
									AND otunnus = '{$row['tunnus']}'
									AND tilausrivi = '{$tuoterow['tunnus']}'
									AND kpl = '{$tuoterow['y_maara']}'";
						$kerayserat_chk_res = pupe_query($query);

						if (mysql_num_rows($kerayserat_chk_res) != 0) {

							if ($debug) {
								echo "Tilausrivi on jo keräyserässä!!!! Let's skip it.<br />";
							}

							continue;
						}

						// katsotaan onko tilaus osittain jo keräyserässä
						$query = "	SELECT sum(kpl) as montako
									FROM kerayserat
									WHERE yhtio = '{$kukarow['yhtio']}'
									AND otunnus = '{$row['tunnus']}'
									AND tilausrivi = '{$tuoterow['tunnus']}'
									AND kpl < '{$tuoterow['y_maara']}'";
						$kerayserat_chk_res = pupe_query($query);
						$kerayserat_chk_row = mysql_fetch_assoc($kerayserat_chk_res);

						if ($kerayserat_chk_row['montako'] > 0) {

							if ($tuoterow['y_maara'] == $kerayserat_chk_row['montako']) {

								if ($debug) {
									echo "Kaikki tilausrivin kappaleet ovat jo keräyserissä. Let's skipataan.<br>";
								}

								continue;
							}

							$tuoterow['y_maara'] -= $kerayserat_chk_row['montako'];
						}

						if ($debug) {
							echo "<tr>";
							echo "<td>$row[tunnus]</td>";
							echo "<td>$row[keraysvyohyke_nimitys]</td>";
							echo "<td>$max_keraysera_pintaala</td>";
							echo "<td>$max_keraysera_rivit</td>";
							echo "<td>$row[aika]</td>";
							echo "<td>$row[tilauksen_koko]</td>";
							echo "<td>$row[asiakas_nimi]</td>";

							echo "<td>$tuoterow[tunnus]</td>";
							echo "<td>$tuoterow[tuoteno]</td>";
							echo "<td>$tuoterow[tuotteen_koko]</td>";
							echo "<td>$tuoterow[y_koko] ($tuoterow[tuoteleveys] x $tuoterow[tuotekorkeus] x $tuoterow[tuotesyvyys])</td>";
							echo "<td>$tuoterow[y_maara]</td>";
							echo "<td>$tuoterow[y_paino]</td>";
							echo "<td>$tuoterow[yksin_kerailyalustalle]</td>";
							echo "</tr>";
						}

						if (trim($ed_asiakas) == '' or $ed_asiakas != $row['liitostunnus'] or ($ed_asiakas == $row['liitostunnus'] and trim($ed_sisviesti2) != 'ÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖ' and $ed_sisviesti2 != $row['sisviesti2'])) {
							$taulukot = array();

							foreach ($pakkaukset as $pakkauksen_tunnus => $xarr) {

								// skipataan pakkaus, jos yksittäinen tuote ei mahdu kyseiseen laatikkoon
								if ($tuoterow['tuoteleveys'] > $xarr['leveys'] or $tuoterow['tuotekorkeus'] > $xarr['korkeus'] or $tuoterow['tuotesyvyys'] > $xarr['syvyys']) {

									if ($debug) {
										echo "POISTETAAN PAKETTI [$pakkauksen_tunnus]<br>";
										echo "($tuoterow[tuoteleveys] > $xarr[leveys] or $tuoterow[tuotekorkeus] > $xarr[korkeus] or $tuoterow[tuotesyvyys] > $xarr[syvyys])<br><br>";
									}

									continue;
								}

								$taulukko = array();

								// [x][y][z]
								for ($x = 0; $x <= $xarr['leveys']; $x += $resoluutio) {
									for ($y = 0; $y <= $xarr['korkeus']; $y += $resoluutio) {
										for ($z = 0; $z <= $xarr['syvyys']; $z += $resoluutio) {
											$taulukko["$x"]["$y"]["$z"] = true;
										}
									}
								}

								$taulukot[$pakkauksen_tunnus] = $taulukko;
							}

							if (isset($taulukko) and count($taulukko) > 0) {
								$taulukot = array_reverse($taulukot, true);
							}
						}

						// ei ole yhtään pakkausta käytettävissä
						if (count($taulukot) == 0) {
							continue;
						}

						if ($debug) {
							echo "ASIAKAS: $row[asiakas_nimi] ($row[asiakas_tunnus])<br>";
							echo "TUOTE: $tuoterow[tuoteno] ($tuoterow[y_maara] kpl)<br>";
							echo "=================================<br>";

							if (trim($tuoterow['yksin_kerailyalustalle']) != '') {
								echo "TUOTE YKSIN KERÄYSALUSTALLE!!!<br>";
							}
						}

						$all_in_one = false;

						if ($debug) {
							echo "Juokseva pakkausnro: $juokseva_pakkausnro<br>";
							echo "<br>";
						}

						// jos kaikki kappaleet ei mahdu yhteen pakettiin, katsotaan mihin paketteihin ne mahtuu
						if (!$all_in_one) {

							if (trim($ed_asiakas) == '' or $ed_asiakas != $row['liitostunnus'] or ($ed_asiakas == $row['liitostunnus'] and trim($ed_sisviesti2) != 'ÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖ' and $ed_sisviesti2 != $row['sisviesti2'])) {

								$sopiva_pakkaus = array();

								foreach ($taulukot as $key => $taulukko) {
									$sopiva_pakkaus[$key] = $taulukko;
									break;
								}

								if ($ed_asiakas != $row['liitostunnus'] and trim($tuoterow['yksin_kerailyalustalle']) == '') {
									$ed_juokseva = $juokseva_pakkausnro;
									$juokseva_pakkausnro++;
								}
							}

							$kaytetty_kpl = 0;

							$tuotteenleveys 	= (float) ceiling($tuoterow['tuoteleveys'], $resoluutio);
							$tuotteenkorkeus 	= (float) ceiling($tuoterow['tuotekorkeus'], $resoluutio);
							$tuotteensyvyys 	= (float) ceiling($tuoterow['tuotesyvyys'], $resoluutio);
							$tuotteenpaino		= $tuoterow['y_paino'];

							for ($i = 1; $i <= $tuoterow['y_maara']; $i++) {

								$breikkasi = '';

								if (count($asiakkaan_pakkaukset) > 0) {

									foreach ($asiakkaan_pakkaukset as $key => &$arr) {

										foreach ($arr as $juokseva_pakkausnumero => &$taulukko_chk) {

											if ($debug) {
												echo "Katsotaan mahtuuko aiempaan pakkaukseen [$key]<br>";
											}

											$params_fetch = array(
												'haystack' 		=> &$taulukko_chk,
												'needle'		=> true,
												'index'			=> array($tuotteenleveys, $tuotteenkorkeus, $tuotteensyvyys),
												'resoluutio'	=> $resoluutio,
												'i'				=> $i,
												'row'			=> $row,
												'tuoterow'		=> $tuoterow
											);

											$return_params = fetchFreeSlot($params_fetch);

											extract($return_params);

											if ($vapaa_x !== false and $vapaa_y !== false and $vapaa_z !== false and $vapaa_chk_x !== false and $vapaa_chk_y !== false and $vapaa_chk_z !== false) {

												if ($debug) {
													echo "MAHTUU AIEMPAAN PAKKAUKSEEN!!!! [$key] juokseva: $juokseva_pakkausnumero<br>";
													echo "vapaa_chk_x: $vapaa_chk_x ($vapaa_x) vapaa_chk_y: $vapaa_chk_y ($vapaa_y) vapaa_chk_z: $vapaa_chk_z ($vapaa_z)<br><br>";
												}

												if ($pakkaukset[$key]['paino'] > ($pakkauksen_paino + $tuotteenpaino)) {
													$pakkauksen_paino += $tuotteenpaino;

													if ($debug) {
														echo "Pakkauksen paino nyt: $pakkauksen_paino<br>";
													}
												}
												else {
													if ($debug) {
														echo "PAINO YLITTÄÄ PAKKAUKSEN MAKSIMIPAINON! ($pakkauksen_paino + $tuotteenpaino > {$pakkaukset[$key]['paino']})<br>";
													}

													continue;
												}

												for ($x = $vapaa_x; $x <= $vapaa_chk_x; $x += $resoluutio) {
													for ($y = $vapaa_y; $y <= $vapaa_chk_y; $y += $resoluutio) {
														for ($z = $vapaa_z; $z <= $vapaa_chk_z; $z += $resoluutio) {
															$taulukko_chk["$x"]["$y"]["$z"] = false;
														}
													}
												}

												if (!isset($kaytettavat_pakkaukset[$key][$row['liitostunnus']][$juokseva_pakkausnumero][$tuoterow['tunnus']])) $kaytettavat_pakkaukset[$key][$row['liitostunnus']][$juokseva_pakkausnumero][$tuoterow['tunnus']] = 0;
												$kaytettavat_pakkaukset[$key][$row['liitostunnus']][$juokseva_pakkausnumero][$tuoterow['tunnus']] += $i - $kaytetty_kpl;
												$kaytetty_kpl = $i;
												$erat['tilaukset'][$tuoterow['otunnus']] = $tuoterow['otunnus'];
												$rivit++;

												if ($i == $tuoterow['y_maara']) {
													if ($debug) {
														echo "Erän koko nyt: $eran_koko<br><br>";
													}
												}

												$breikkasi = 'joo';
												break 2;
											}
										}
									}

									if ($breikkasi == 'joo') {
										$breikkasi = '';
										continue;
									}
									else if ($ed_asiakas == $row['liitostunnus'] and trim($tuoterow['yksin_kerailyalustalle']) == '') {
										$ed_juokseva = $juokseva_pakkausnro;
										$juokseva_pakkausnro++;
									}
								}

								foreach ($sopiva_pakkaus as $key => &$taulukko_chk) {

									if (trim($tuoterow['yksin_kerailyalustalle']) != '') {

										if ($debug) {
											echo "TUOTE LISÄTÄÄN NYT OMALLE KERÄYSALUSTALLE!!!!<br>";
										}

										// katsotaan aluksi mahtuuko kaikki tuotteet yhteen pakettiin
										// pienemmät ensiksi
										$taulukot = array_reverse($taulukot, true);

										$params_fitting = array(
											'taulukot' 					=> $taulukot,
											'asiakkaan_pakkaukset' 		=> $asiakkaan_pakkaukset,
											'kaytettavat_pakkaukset' 	=> $kaytettavat_pakkaukset,
											'pakkaukset' 				=> $pakkaukset,
											'tuoterow'					=> $tuoterow,
											'resoluutio'				=> $resoluutio,
											'eran_koko' 				=> $eran_koko, 
											'max_keraysera_pintaala' 	=> $max_keraysera_pintaala, 
											'row'						=> $row, 
											'juokseva_pakkausnro'		=> $juokseva_pakkausnro, 
											'ed_juokseva'				=> $ed_juokseva, 
											'erat' 						=> $erat,
											'debug'						=> $debug
										);

										$return_params = fitting($params_fitting);

										extract($return_params);

										if ($breikki == 3) {
											break 4;
										}

										// reverse takaisin. isot ensimmäiseksi.
										$taulukot = array_reverse($taulukot, true);

										$pakkauksen_paino = 0;
										$rivit++;

										if ($debug) {
											echo "Lisätään uusi laatikko! juokseva: $juokseva_pakkausnro<br>";
											echo "=================================<br><br>";
										}

										foreach ($taulukot as $key_xx => $taulukko) {
											$sopiva_pakkaus[$key_xx] = $taulukko;
											break;
										}

										continue;
									}

									$params_fetch = array(
										'haystack' 		=> &$taulukko_chk,
										'needle'		=> true,
										'index'			=> array($tuotteenleveys, $tuotteenkorkeus, $tuotteensyvyys),
										'resoluutio'	=> $resoluutio,
										'i'				=> $i,
										'row'			=> $row,
										'tuoterow'		=> $tuoterow
									);

									$return_params = fetchFreeSlot($params_fetch);

									extract($return_params);

									if ($debug) {
										echo "vapaa_x: $vapaa_x, vapaa_y: $vapaa_y, vapaa_z: $vapaa_z<br>";
										echo "vapaa_chk_x: $vapaa_chk_x, vapaa_chk_y: $vapaa_chk_y, vapaa_chk_z: $vapaa_chk_z<br>";
										echo "<br>KPL: $i<br>";
									}

									if ($vapaa_x === false or $vapaa_y === false or $vapaa_z === false) {

										foreach ($pakkaukset as $pakkauksen_tunnus => $qarr) {

											// skipataan pakkaus, jos yksittäinen tuote ei mahdu kyseiseen laatikkoon
											if ($tuoterow['tuoteleveys'] > $qarr['leveys'] or $tuoterow['tuotekorkeus'] > $qarr['korkeus'] or $tuoterow['tuotesyvyys'] > $qarr['syvyys']) {
												if ($debug) {
													echo "TUOTE EI VOI MAHTUA PAKETTIIN [$pakkauksen_tunnus]<br>";
													echo "$tuoterow[tuoteleveys] > $qarr[leveys] or $tuoterow[tuotekorkeus] > $qarr[korkeus] or $tuoterow[tuotesyvyys] > $qarr[syvyys]<br><br>";
												}

												break 2;
											}
										}

										$asiakkaan_pakkaukset[$key][$juokseva_pakkausnro] = $taulukko_chk;

										if ($debug) {
											if ($ed_asiakas == $row['liitostunnus']) {
												echo "SAMA ASIAKAS $row[asiakas_nimi]!!!<br>";
											}

											echo "Joku ei riittänyt! [$key]<br>";
										}

										$i--;

										$eran_koko += ($pakkaukset[$key]['leveys'] * $pakkaukset[$key]['syvyys']);

										if ($debug) {
											echo "Pakkaus käytetty ({$pakkaukset[$key]['leveys']} x {$pakkaukset[$key]['syvyys']}): ".($pakkaukset[$key]['leveys'] * $pakkaukset[$key]['syvyys'])." (resoluutio: $resoluutio) / $max_keraysera_pintaala<br>";
											echo "Erän koko nyt: $eran_koko<br>";
										}

										if (count($pakkaukset) == 0) {
											if ($debug) {
												echo "Käytettäviä pakkauksia ei ole enää jäljellä! Keräyserä valmis.<br><br>";
											}

											$era_valmis = true;
											break 4;
										}

										$erat['tilaukset'][$tuoterow['otunnus']] = $tuoterow['otunnus'];
										$kaytettavat_pakkaukset[$key][$row['liitostunnus']][$juokseva_pakkausnro][$tuoterow['tunnus']] += $i - $kaytetty_kpl;
										$pakkauksen_paino = 0;

										$ed_juokseva = $juokseva_pakkausnro;
										$juokseva_pakkausnro++;

										if ($debug) {
											echo "Lisätään uusi laatikko!<br>";
											echo "=================================<br><br>";
										}

										unset($sopiva_pakkaus[$key]);

										foreach ($taulukot as $key_xx => $taulukko) {
											$sopiva_pakkaus[$key_xx] = $taulukko;
											break;
										}

										continue;
									}
									else {

										if ($debug) {
											echo "vapaa_chk_x: $vapaa_chk_x ($vapaa_x) vapaa_chk_y: $vapaa_chk_y ($vapaa_y) vapaa_chk_z: $vapaa_chk_z ($vapaa_z)<br><br>";
										}

										if ($pakkaukset[$key]['paino'] > ($pakkauksen_paino + $tuotteenpaino)) {
											$pakkauksen_paino += $tuotteenpaino;

											if ($debug) {
												echo "Pakkauksen [$key] paino nyt: $pakkauksen_paino<br>";
											}
										}
										else {
											if ($debug) {
												echo "PAINO YLITTÄÄ PAKKAUKSEN MAKSIMIPAINON! ($pakkauksen_paino + $tuotteenpaino > {$pakkaukset[$key]['paino']})<br>";
											}

											continue;
										}

										for ($x = $vapaa_x; $x <= $vapaa_chk_x; $x += $resoluutio) {
											for ($y = $vapaa_y; $y <= $vapaa_chk_y; $y += $resoluutio) {
												for ($z = $vapaa_z; $z <= $vapaa_chk_z; $z += $resoluutio) {
													$taulukko_chk["$x"]["$y"]["$z"] = false;
												}
											}
										}
									}

									if ($juokseva_pakkausnro == 0) $juokseva_pakkausnro = 1;

									if (!isset($kaytettavat_pakkaukset[$key][$row['liitostunnus']][$juokseva_pakkausnro][$tuoterow['tunnus']])) $kaytettavat_pakkaukset[$key][$row['liitostunnus']][$juokseva_pakkausnro][$tuoterow['tunnus']] = 0;
									$kaytettavat_pakkaukset[$key][$row['liitostunnus']][$juokseva_pakkausnro][$tuoterow['tunnus']] += $i - $kaytetty_kpl;
									$kaytetty_kpl = $i;
									$erat['tilaukset'][$tuoterow['otunnus']] = $tuoterow['otunnus'];
									$rivit++;

									if ($i == $tuoterow['y_maara']) {
										if ($ed_asiakas != '' and $ed_asiakas != $row['liitostunnus']) {
											$eran_koko += $pakkaukset[$key]['leveys'] * $pakkaukset[$key]['syvyys'];
										}
									}

									break;
								} #end foreach
							} #end for

							$ed_asiakas = $row['liitostunnus'];
						} #end if
					}

					if (mysql_num_rows($tuoteres) > 0) {
						$ed_asiakas = $row['liitostunnus'];
						$ed_sisviesti2 = $row['sisviesti2'];
					}

					if ($debug) {
						echo "==========================================================<br>";
						echo "==========================================================<br><br><br><br>";
					}
				}

				echo "</table>";

				if ($debug) {
					if ($era_valmis) echo "ERÄ VALMIS!!!<br>";
					elseif (count($erat['tilaukset']) == 0) echo "KERÄYSERÄ ON TYHJÄ!!!<br>";
				}

				if ($ulkoinen_jarjestelma == 'K') {
					//keksitään uudelle failille joku varmasti uniikki nimi:
					list($usec, $sec) = explode(' ', microtime());
					mt_srand((float) $sec + ((float) $usec * 100000));
					$filenimi = "/Users/sami/temp/kardex/orders/ulkoisen_jarjestelman_tiedosto-".md5(uniqid(mt_rand(), true)).".txt";
					$fh = fopen($filenimi, "w+");
				}

				foreach ($kaytettavat_pakkaukset as $pakkauksen_nro => $larr) {
					foreach ($larr as $as_tun => $jarr) {
						foreach ($jarr as $juokseva_nro => $marr) {
							foreach ($marr as $tilriv => $kpl) {

								$erat['pakkaukset'][$pakkauksen_nro][$juokseva_nro][$tilriv] = $kpl;

								if ($ulkoinen_jarjestelma == 'K') {
									$query = "SELECT otunnus, tuoteno, nimitys, CONCAT(hyllyalue, '-', hyllynro, '-', hyllyvali, '-', hyllytaso) AS hyllypaikka FROM tilausrivi WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$tilriv}'";
									$tilriv_tiedot_res = pupe_query($query);
									$tilriv_tiedot_row = mysql_fetch_assoc($tilriv_tiedot_res);

									$sisalto = "4;{$juokseva_nro};{$tilriv_tiedot_row['tuoteno']};{$tilriv_tiedot_row['nimitys']};-{$kpl};{$tilriv_tiedot_row['hyllypaikka']};{$tilriv};";

									fputs($fh, $sisalto."\r\n");
								}
							}
						}
					}
				}

				if ($ulkoinen_jarjestelma == 'K') {

					fclose($fh);

					$ftphost = $ftphost_kardex;
					$ftpuser = $ftpuser_kardex;
					$ftppass = $ftppass_kardex;
					$ftppath = $ftppath_kardex;

					$ftpfile = $unlink_filenimi = $filenimi;

					require('inc/ftp-send.inc');

					unlink($unlink_filenimi);
				}


				if ($debug) {
					echo "<pre>",var_dump($kaytettavat_pakkaukset, $erat),"</pre>";
				}

				if (count($erat['tilaukset']) != 0) {
					$otunnukset = implode(",", $erat['tilaukset']);

					require('inc/tulosta_reittietiketti.inc');

					$query = "SELECT * FROM lasku WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus IN ($otunnukset)";
					$res = pupe_query($query);
					$laskurow = mysql_fetch_assoc($res);

					$tilausnumeroita = $otunnukset;
					$valittu_tulostin = $komento['kerayslista'];
					$tullaan_kerayserasta = 'joo';

					$laskuja = count($erat['tilaukset']);

					require("tilauskasittely/tilaus-valmis-tulostus.inc");
				}
				else {
					echo "<font class='message'>",t("Ei ole yhtään kerättävää keräyserää"),".</font><br />";
				}
			}

			echo "<br /><br />";

			if ($tee == 'selaa') {
				$etsi_kerayseraa = !isset($etsi_kerayseraa) ? '' : $etsi_kerayseraa;

				$kerayseranro = (isset($etsi_kerayseraa) and trim($etsi_kerayseraa) != '') ? (int) $etsi_kerayseraa : (isset($kerayseranro) ? $kerayseranro : '');

				echo "<form method='post'>";
				echo "<input type='hidden' name='keraajanro' value='{$keraajanro}' />";
				echo "<input type='hidden' name='keraajalist' value='{$keraajalist}' />";
				echo "<input type='hidden' name='who' value='{$who}' />";
				echo "<input type='hidden' name='tee' value='selaa' />";
				echo t('Etsi'),":&nbsp;<input type='text' name='etsi_kerayseraa' value='{$etsi_kerayseraa}' />&nbsp;";
				echo "<input type='submit' value='",t("Hae"),"' />";
				echo "</form>";
				echo "<br /><br />";
			}

			$ker_lisa = (isset($kerayseranro) and $kerayseranro > 0) ? " AND kerayserat.nro = '{$kerayseranro}' " : '';

			$kuka_lisa = (isset($who) and trim($who) != '') ? " AND kerayserat.laatija = '{$who}' " : '';

			$query = "	SELECT kerayserat.nro,
						keraysvyohyke.nimitys AS keraysvyohyke,
						kerayserat.laatija AS kayttaja,
						COUNT(DISTINCT lasku.liitostunnus) AS asiakasmaara,
						COUNT(*) AS rivimaara
						FROM kerayserat
						JOIN lasku ON (lasku.yhtio = kerayserat.yhtio AND lasku.tunnus = kerayserat.otunnus)
						JOIN tilausrivi ON (tilausrivi.yhtio = kerayserat.yhtio AND tilausrivi.tunnus = kerayserat.tilausrivi)
						JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
						JOIN keraysvyohyke ON (keraysvyohyke.yhtio = tuote.yhtio AND keraysvyohyke.tunnus = tuote.keraysvyohyke)
						WHERE kerayserat.yhtio = '{$kukarow['yhtio']}'
						AND kerayserat.tila = 'K'
						{$ker_lisa}
						{$kuka_lisa}
						GROUP BY 1,2,3";
			$kerayserat_res = pupe_query($query);

			if (mysql_num_rows($kerayserat_res) > 0) {
				echo "<img title='",t("Näytä rivi esimerkkikuva"),"' alt='",t("Näytä rivi esimerkkikuva"),"' src='{$palvelin2}pics/lullacons/go-down.png' /> = <font class='message'>",t("Saat lisää infoa keräyserästä klikkaamalla riviä"),"</font><br /><br />";

				echo "<table>";
				echo "<tr>";
				echo "<th>",t("Tunnus"),"</th>";
				echo "<th>",t("Keräysvyöhyke"),"</th>";
				echo "<th>",t("Rivimäärä"),"</th>";
				echo "<th>",t("Asiakasmäärä"),"</th>";
				echo "<th>",t("Käyttäjä"),"</th>";
				echo "<th>",t("Toiminto"),"</th>";
				echo "</tr>";

				while ($kerayserat_row = mysql_fetch_assoc($kerayserat_res)) {
					echo "<tr class='toggleable' id='{$kerayserat_row['nro']}'>";
					echo "<td>{$kerayserat_row['nro']}&nbsp;<img title='",t("Näytä rivi"),"' alt='",t("Näytä rivi"),"' src='{$palvelin2}pics/lullacons/go-down.png' /></td>";
					echo "<td>{$kerayserat_row['keraysvyohyke']}</td>";
					echo "<td>{$kerayserat_row['rivimaara']}</td>";
					echo "<td>{$kerayserat_row['asiakasmaara']}</td>";

					echo "<td>";

					$kuka_lisa = $tee != 'muokkaa' ? " AND kuka = '{$kerayserat_row['kayttaja']}' " : '';

					$query = "	SELECT kuka, nimi
								FROM kuka
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND extranet = ''
								AND nimi != ''
								AND keraysvyohyke != ''
								{$kuka_lisa}
								ORDER BY nimi";
					$kuka_res = pupe_query($query);

					if ($tee == 'muokkaa') {

						echo "<select name='kayttaja'>";

						while ($kuka_row = mysql_fetch_assoc($kuka_res)) {

							$sel = $kuka_row['kuka'] == $kerayserat_row['kayttaja'] ? ' selected' : '';

							echo "<option value='{$kerayserat_row['nro']}##{$kuka_row['kuka']}'{$sel}>{$kuka_row['nimi']}</option>";
						}

						echo "</select>";
					}
					else {
						$kuka_row = mysql_fetch_assoc($kuka_res);
						echo $kuka_row['nimi'];
					}

					echo "</td>";

					echo "<td>";

					if ($tee == 'selaa' or $tee == 'keraysera') {
						echo "<form method='post'>";
						echo "<input type='hidden' name='kerayseranro' value='{$kerayserat_row['nro']}' />";
						echo "<input type='hidden' name='keraajanro' value='{$keraajanro}' />";
						echo "<input type='hidden' name='keraajalist' value='{$keraajalist}' />";
						echo "<input type='hidden' name='who' value='{$who}' />";
						echo "<input type='hidden' name='tee' value='muokkaa' />";
						echo "<input type='submit' value='",t("Muokkaa"),"' />";
						echo "</form>";
					}
					else if ($tee == 'muokkaa') {
						echo "<input type='hidden' name='kerayseranro' value='{$kerayserat_row['nro']}' />";
						echo "<input type='hidden' name='keraysvyohyke' value='{$keraysvyohyke}' />";
						echo "<input type='hidden' name='keraajanro' value='{$keraajanro}' />";
						echo "<input type='hidden' name='keraajalist' value='{$keraajalist}' />";
						echo "<input type='hidden' name='who' value='{$who}' />";
						echo "<select name='tee' id='tee'>";
						echo "<option value='muuta'>",t("Tallenna"),"</option>";
						echo "<option value='uusi_pakkaus'>",t("Uusi pakkaus"),"</option>";
						echo "<option value='selaa'>",t("Palaa"),"</option>";
						echo "</select>";
						echo "&nbsp;<input type='submit' value='",t("OK"),"' />";
					}

					echo "</td>";

					echo "</tr>";

					if ($tee == 'muokkaa') {
						$lisa = ' GROUP BY pakkausnro ';
						$selectlisa = ' SUM(kerayserat.kpl) AS kpl ';
					}
					else {
						$lisa = '';
						$selectlisa = " tilausrivi.tuoteno, TRIM(CONCAT(asiakas.nimi, ' ', asiakas.nimitark)) asiakasnimi, kerayserat.kpl ";
					}

					$query = "	SELECT kerayserat.pakkaus, kerayserat.tunnus AS rivitunnus, kerayserat.pakkausnro, kerayserat.sscc, kerayserat.otunnus, {$selectlisa}
					 			FROM kerayserat
								LEFT JOIN tilausrivi ON (tilausrivi.yhtio = kerayserat.yhtio AND tilausrivi.tunnus = kerayserat.tilausrivi)
								LEFT JOIN lasku ON (lasku.yhtio = kerayserat.yhtio AND lasku.tunnus = kerayserat.otunnus)
								LEFT JOIN asiakas ON (asiakas.yhtio = lasku.yhtio AND asiakas.tunnus = lasku.liitostunnus)
								WHERE kerayserat.yhtio = '{$kukarow['yhtio']}'
								AND kerayserat.nro = '{$kerayserat_row['nro']}'
								{$lisa}
								ORDER BY pakkausnro";
					$rivit_res = pupe_query($query);

					$display = ($tee == 'selaa' or $tee == 'keraysera') ? " display:none; " : '';

					echo "<tr>";
					echo "<td colspan='6' class='back'>";
					echo "<div id='toggleable_{$kerayserat_row['nro']}' style='{$display} width:100%;'>";
					echo "<table style='width:100%;'>";
					echo "<tr>";
					echo "<th>",t("Pakkauskirjain"),"</th>";
					echo "<th>",t("SSCC"),"</th>";
					echo "<th>",t("Määrä"),"</th>";

					if ($tee != 'muokkaa') {
						echo "<th>",t("Tuoteno"),"</th>";
						echo "<th>",t("Asiakas"),"</th>";
					}

					echo "<th>",t("Pakkaus"),"</th>";

					if ($tee == 'muokkaa') {
						echo "<th>",t("Reittietiketti"),"</th>";
					}

					echo "</tr>";

					while ($rivit_row = mysql_fetch_assoc($rivit_res)) {
						echo "<tr class='tumma toggleable' id='",chr((64+$rivit_row['pakkausnro'])),"'>";
						echo "<td nowrap>",chr((64+$rivit_row['pakkausnro']));

						if ($tee == 'muokkaa') {
							echo "&nbsp;<img title='",t("Näytä rivi"),"' alt='",t("Näytä rivi"),"' src='{$palvelin2}pics/lullacons/go-down.png' />";
						}

						echo "</td>";
						echo "<td nowrap>{$rivit_row['sscc']}</td>";
						echo "<td nowrap>{$rivit_row['kpl']}</td>";

						if ($tee != 'muokkaa') {
							echo "<td nowrap>{$rivit_row['tuoteno']}</td>";
							echo "<td nowrap>{$rivit_row['asiakasnimi']}</td>";
						}

						echo "<td nowrap>";

						if ($tee == 'muokkaa') {
							echo "<select name='pakkaukset[{$rivit_row['rivitunnus']}##{$rivit_row['pakkausnro']}]'>";

							$query = "SELECT tunnus, TRIM(CONCAT(pakkaus, ' ', pakkauskuvaus)) pakkaus FROM pakkaus WHERE yhtio = '{$kukarow['yhtio']}'";
							$pakkaus_vaihto_res = pupe_query($query);

							while ($pakkaus_vaihto_row = mysql_fetch_assoc($pakkaus_vaihto_res)) {

								$sel = $rivit_row['pakkaus'] == $pakkaus_vaihto_row['tunnus'] ? ' selected' : '';

								echo "<option value='{$rivit_row['rivitunnus']}##{$pakkaus_vaihto_row['tunnus']}'{$sel}>{$pakkaus_vaihto_row['pakkaus']}</option>";
							}

							echo "</select>";
						}
						else {
							$query = "SELECT tunnus, TRIM(CONCAT(pakkaus, ' ', pakkauskuvaus)) pakkaus FROM pakkaus WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = {$rivit_row['pakkaus']}";
							$pakkaus_vaihto_res = pupe_query($query);
							$pakkaus_vaihto_row = mysql_fetch_assoc($pakkaus_vaihto_res);

							echo $pakkaus_vaihto_row['pakkaus'];
						}

						echo "</td>";

						if ($tee == 'muokkaa') {
							echo "<td>";
							echo "<input class='checkbox' type='checkbox' name='tulostettavat_reittietiketit[{$rivit_row['pakkausnro']}]' value='{$kerayserat_row['nro']}' />";
							echo "</td>";
						}

						echo "</tr>";

						if ($tee == 'muokkaa') {
							$query = "	SELECT kerayserat.pakkaus, 
										kerayserat.tunnus AS rivitunnus, 
										kerayserat.pakkausnro, 
										tilausrivi.tuoteno, 
										TRIM(CONCAT(asiakas.nimi, ' ', asiakas.nimitark)) asiakasnimi, 
										kerayserat.kpl, 
										lasku.liitostunnus, 
										CONCAT(hyllyalue, ' ', hyllynro, ' ', hyllyvali, ' ', hyllytaso) hyllypaikka,
										tilausrivi.tunnus AS tilausrivin_tunnus
							 			FROM kerayserat
										JOIN tilausrivi ON (tilausrivi.yhtio = kerayserat.yhtio AND tilausrivi.tunnus = kerayserat.tilausrivi)
										JOIN lasku ON (lasku.yhtio = kerayserat.yhtio AND lasku.tunnus = kerayserat.otunnus)
										JOIN asiakas ON (asiakas.yhtio = lasku.yhtio AND asiakas.tunnus = lasku.liitostunnus)
										WHERE kerayserat.yhtio = '{$kukarow['yhtio']}'
										AND kerayserat.nro = '{$kerayserat_row['nro']}'
										AND kerayserat.pakkausnro = '{$rivit_row['pakkausnro']}'
										ORDER BY pakkausnro";
							$content_res = pupe_query($query);

							echo "<tr>";
							echo "<td colspan='6' class='back'>";

							$viewable = (isset($view) and $view == 'yes') ? '' : 'display:none;';

							echo "<div id='toggleable_",chr((64+$rivit_row['pakkausnro'])),"' style='{$viewable} width:100%'>";

							echo "<table style='width:100%'>";

							if (mysql_num_rows($content_res) == 0) {
								echo "<tr><td>",t("Poista pakkaus")," <input type='checkbox' name='poista_pakkaus[{$rivit_row['pakkausnro']}]' value='{$kerayserat_row['nro']}' /></td></tr>";
							}
							else {

								echo "<tr>";
								echo "<th>",t("Tuoteno"),"</th>";
								echo "<th>",t("Hyllypaikka"),"</th>";
								echo "<th>",t("Määrä"),"</th>";
								echo "<th>",t("Asiakas"),"</th>";
								echo "<th>",t("Siirrä")," (",t("Minne ja kuinka paljon"),")</th>";
								echo "</tr>";

								while ($content_row = mysql_fetch_assoc($content_res)) {
									echo "<tr>";
									echo "<td>{$content_row['tuoteno']}</td>";
									echo "<td>{$content_row['hyllypaikka']}</td>";
									echo "<td>{$content_row['kpl']}</td>";
									echo "<td>{$content_row['asiakasnimi']}</td>";
									echo "<td>";

									$query = "	SELECT DISTINCT kerayserat.pakkausnro 
												FROM kerayserat
												LEFT JOIN lasku ON (lasku.yhtio = kerayserat.yhtio AND lasku.tunnus = kerayserat.otunnus)
												WHERE kerayserat.yhtio = '{$kukarow['yhtio']}'
												AND kerayserat.pakkausnro != '{$content_row['pakkausnro']}'
												AND kerayserat.nro = '{$kerayserat_row['nro']}'
												AND ((lasku.liitostunnus = '{$content_row['liitostunnus']}') or (kerayserat.otunnus = 0 and kerayserat.tilausrivi = 0))
												ORDER BY 1";
									$siirra_res = pupe_query($query);

									echo "<select name='siirra_tuote[{$content_row['rivitunnus']}]' class='siirra_tuote'>";
									echo "<option value=''>",t("Ei siirretä"),"</option>";

									while ($siirra_row = mysql_fetch_assoc($siirra_res)) {
										echo "<option value='{$siirra_row['pakkausnro']}'>",chr((64+$siirra_row['pakkausnro'])),"</option>";
									}

									echo "</select>";

									echo "&nbsp;<input class='siirra_kpl' type='text' size='5' name='siirra_kpl[{$content_row['rivitunnus']}]' value='' />";

									echo "</td>";
									echo "</tr>";
								}

							}

							echo "</table>";
							echo "</div></td></tr>";
						}
					}

					echo "</table></div></td></tr>";
				}

				echo "</table>";

				if ($tee == 'muokkaa') {
					echo "</form>";
				}
			}
		}
	}

	require ("inc/footer.inc");