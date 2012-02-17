<?php

	require ("../inc/parametrit.inc");

	js_popup();

	echo "<font class='head'>",t("Tulosta ker‰yser‰"),"</font><hr>";

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
		echo "<tr><th>",t("Ker‰‰j‰"),"</th><td><input type='text' size='5' name='keraajanro'> ",t("tai")," ";
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

		echo "<tr><th>",t("Valitse varasto"),"</th><td>&nbsp;";
		echo "<select name='select_varasto'>";

		$query = "	SELECT tunnus, nimitys
					FROM varastopaikat
					WHERE yhtio = '{$kukarow['yhtio']}'
					ORDER BY nimitys";
		$varastores = pupe_query($query);

		while ($varastorow = mysql_fetch_assoc($varastores)) {

			$sel = $select_varasto == $varastorow['tunnus'] ? " selected" : "";

			echo "<option value='{$varastorow['tunnus']}'{$sel}>{$varastorow['nimitys']}</option>";
		}

		echo "</select>";
		echo "</td></tr>";
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

			echo "<br /><font class='message'>",t("Uusi pakkaus lis‰tty"),"!</font><br />";
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
							echo "<br /><font class='error'>",t("Siirrett‰v‰ kappalem‰‰r‰ oli liian suuri"),"! ({$paljon_siirretaan} > {$kpl_chk_row['kpl']})</font><br />";
						}
						else {
							// katsotaan onko nollarivej‰ (eli tyhj‰ pakkaus)
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

	if ($tee != '' and trim($kukarow['keraysvyohyke']) != '') {

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
			echo "<font class='error'>",t("Ker‰‰j‰‰")," {$keraajanro} ",t("ei lˆydy"),"!</font><br>";
		}
		elseif ($select_varasto == '') {
			echo "<font class='error'>",t("Et valinnut varastoa"),"!</font><br>";
		}
		else {

			$keraaja = mysql_fetch_assoc($result);
			$who = $keraaja['kuka'];

			$query = "	SELECT keraysvyohyke
						FROM kuka
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND kuka = '{$keraaja['kuka']}'";
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
			echo "<input type='hidden' name='select_varasto' value='{$select_varasto}' />";

			echo "<table><tr><th>",t("Valitse reittietiketin tulostin"),"</th><td>";
			echo "<select name='komento[reittietiketti]'>";
			echo "<option value=''>",t("Ei kirjoitinta"),"</option>";

			$select_varasto = (int) $select_varasto;

			$querykieli = "	SELECT DISTINCT kirjoittimet.kirjoitin, kirjoittimet.komento, keraysvyohyke.tunnus, keraysvyohyke.printteri8, kirjoittimet.tunnus as kir_tunnus
							FROM kirjoittimet
							JOIN keraysvyohyke ON (keraysvyohyke.yhtio = kirjoittimet.yhtio AND keraysvyohyke.tunnus IN ({$kukarow['keraysvyohyke']}) AND keraysvyohyke.varasto = '{$select_varasto}')
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
				echo "<tr><th>",t("Valitse ker‰yslistan tulostin"),"</th><td>";
				echo "<select name='komento[kerayslista]'>";
				echo "<option value=''>",t("Ei kirjoitinta"),"</option>";

				$querykieli = "	SELECT kirjoittimet.kirjoitin, kirjoittimet.komento, keraysvyohyke.tunnus, keraysvyohyke.printteri0, kirjoittimet.tunnus as kir_tunnus
								FROM kirjoittimet
								JOIN keraysvyohyke ON (keraysvyohyke.yhtio = kirjoittimet.yhtio AND keraysvyohyke.tunnus IN ({$kukarow['keraysvyohyke']}) AND keraysvyohyke.varasto = '{$select_varasto}')
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

				echo "<td class='back'><input type='submit' value='",t("Hae ker‰yser‰"),"' /></td>";
			}

			echo "</tr></table>";

			if ($tee != 'muuta' and $tee != 'muokkaa') {
				echo "</form>";
			}

			if ($tee == 'keraysera' and trim($keraysvyohyke) != '' and $select_varasto > 0) {

				echo "<font class='head'>Ker‰‰j‰: $who</font><br>";
				echo "<div id='content'></div>";

				$locktables = array(
						'lasku', 
						'lasku1', 
						'lasku2', 
						'laskun_lisatiedot', 
						'asiakas1', 
						'asiakas2', 
						'tilausrivi', 
						'tilausrivi1', 
						'tilausrivi2', 
						'tilausrivin_lisatiedot', 
						'vh',
						'vh1', 
						'vh2', 
						'tuote',
						'tuote1', 
						'tuote2',
						'keraysvyohyke1', 
						'keraysvyohyke2',
						'toimitustapa1', 
						'toimitustapa2', 
						'lahdot1',
						'lahdot2',
						'tuoteperhe',
						'kerayserat',
						'tuotteen_toimittajat',
						'pakkaus',
						'avainsana',
						'keraysvyohyke',
						'asiakas',
						'liitetiedostot', 
						't2', 
						'tlt2', 
						'tilausrivi_osto', 
						'tilausrivi_myynti', 
						'sarjanumeroseuranta', 
						't3', 
						'sanakirja', 
						'a', 
						'b', 
						'varaston_tulostimet', 
						'tuotepaikat', 
						'maksuehto', 
						'varastopaikat', 
						'kirjoittimet', 
						'kuka', 
						'asiakaskommentti', 
						'tuotteen_toimittajat', 
						'tuotteen_avainsanat', 
						'pankkiyhteystiedot', 
						'toimitustapa', 
						'yhtion_toimipaikat', 
						'yhtion_parametrit', 
						'tuotteen_alv', 
						'maat', 
						'rahtisopimukset', 
						'rahtisopimukset2', 
						'pakkaamo', 
						'avainsana_kieli', 
						'vanha_lasku', 
						'vanha_varaston_tulostimet', 
						'yhtio'
					);

				$lukotetaan = check_lock_tables($locktables);

				if ($lukotetaan) {
					// lukitaan tableja
					$query = "	LOCK TABLES lasku WRITE, 
								lasku AS lasku1 WRITE, 
								lasku AS lasku2 WRITE, 
								laskun_lisatiedot WRITE, 
								asiakas AS asiakas1 READ, 
								asiakas AS asiakas2 READ, 
								tilausrivi WRITE, 
								tilausrivi AS tilausrivi1 READ, 
								tilausrivi AS tilausrivi2 READ, 
								tilausrivin_lisatiedot WRITE, 
								varaston_hyllypaikat AS vh READ,
								varaston_hyllypaikat AS vh1 READ, 
								varaston_hyllypaikat AS vh2 READ, 
								tuote READ,
								tuote AS tuote1 READ, 
								tuote AS tuote2 READ,
								keraysvyohyke AS keraysvyohyke1 READ, 
								keraysvyohyke AS keraysvyohyke2 READ,
								toimitustapa AS toimitustapa1 READ, 
								toimitustapa AS toimitustapa2 READ, 
								lahdot AS lahdot1 WRITE,
								lahdot AS lahdot2 WRITE,
								tuoteperhe READ,
								kerayserat READ,
								tuotteen_toimittajat READ,
								pakkaus READ,
								avainsana WRITE,
								keraysvyohyke READ,
								asiakas READ,
								liitetiedostot READ, 
								tilausrivi AS t2 READ, 
								tilausrivin_lisatiedot AS tlt2 READ, 
								tilausrivi AS tilausrivi_osto READ, 
								tilausrivi AS tilausrivi_myynti READ, 
								sarjanumeroseuranta READ, 
								tilausrivi AS t3 READ, 
								sanakirja WRITE, 
								avainsana as a READ, 
								avainsana as b READ, 
								varaston_tulostimet READ, 
								tuotepaikat READ, 
								maksuehto READ, 
								varastopaikat READ, 
								kirjoittimet READ, 
								kuka WRITE, 
								asiakaskommentti READ, 
								tuotteen_toimittajat READ, 
								tuotteen_avainsanat READ, 
								pankkiyhteystiedot READ, 
								toimitustapa READ, 
								yhtion_toimipaikat READ, 
								yhtion_parametrit READ, 
								tuotteen_alv READ, 
								maat READ, 
								rahtisopimukset READ, 
								rahtisopimukset AS rahtisopimukset2 READ, 
								pakkaamo WRITE, 
								avainsana as avainsana_kieli READ, 
								lasku as vanha_lasku READ, 
								varaston_tulostimet as vanha_varaston_tulostimet READ, 
								yhtio READ";
					$result = pupe_query($query);
				}

				$debug = true;

				$erat = tee_keraysera2($keraysvyohyke, $select_varasto);

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
					echo "<font class='message'>",t("Ei ole yht‰‰n ker‰tt‰v‰‰ ker‰yser‰‰"),".</font><br />";

					if ($lukotetaan) {
						// lukitaan tableja
				 		$query = "UNLOCK TABLES";
	 					$result = pupe_query($query);
					}
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
				echo "<input type='hidden' name='keraysvyohyke' value='{$keraysvyohyke}' />";
				echo "<input type='hidden' name='select_varasto' value='{$select_varasto}' />";
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
						JOIN varaston_hyllypaikat vh ON (vh.yhtio = tilausrivi.yhtio AND vh.hyllyalue = tilausrivi.hyllyalue AND vh.hyllynro = tilausrivi.hyllynro AND vh.hyllyvali = tilausrivi.hyllyvali AND vh.hyllytaso = tilausrivi.hyllytaso AND vh.keraysvyohyke = '{$kukarow['keraysvyohyke']}')
						JOIN keraysvyohyke ON (keraysvyohyke.yhtio = tuote.yhtio AND keraysvyohyke.tunnus = vh.keraysvyohyke)
						WHERE kerayserat.yhtio = '{$kukarow['yhtio']}'
						AND kerayserat.tila = 'K'
						{$ker_lisa}
						{$kuka_lisa}
						GROUP BY 1,2,3";
			$kerayserat_res = pupe_query($query);

			if (mysql_num_rows($kerayserat_res) > 0) {
				echo "<img title='",t("N‰yt‰ rivi esimerkkikuva"),"' alt='",t("N‰yt‰ rivi esimerkkikuva"),"' src='{$palvelin2}pics/lullacons/go-down.png' /> = <font class='message'>",t("Saat lis‰‰ infoa ker‰yser‰st‰ klikkaamalla rivi‰"),"</font><br /><br />";

				echo "<table>";
				echo "<tr>";
				echo "<th>",t("Tunnus"),"</th>";
				echo "<th>",t("Ker‰ysvyˆhyke"),"</th>";
				echo "<th>",t("Rivim‰‰r‰"),"</th>";
				echo "<th>",t("Asiakasm‰‰r‰"),"</th>";
				echo "<th>",t("K‰ytt‰j‰"),"</th>";
				echo "<th>",t("Toiminto"),"</th>";
				echo "</tr>";

				while ($kerayserat_row = mysql_fetch_assoc($kerayserat_res)) {
					echo "<tr class='toggleable' id='{$kerayserat_row['nro']}'>";
					echo "<td>{$kerayserat_row['nro']}&nbsp;<img title='",t("N‰yt‰ rivi"),"' alt='",t("N‰yt‰ rivi"),"' src='{$palvelin2}pics/lullacons/go-down.png' /></td>";
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
						echo "<input type='hidden' name='keraysvyohyke' value='{$keraysvyohyke}' />";
						echo "<input type='hidden' name='select_varasto' value='{$select_varasto}' />";
						echo "<input type='submit' value='",t("Muokkaa"),"' />";
						echo "</form>";
					}
					else if ($tee == 'muokkaa') {
						echo "<input type='hidden' name='kerayseranro' value='{$kerayserat_row['nro']}' />";
						echo "<input type='hidden' name='keraysvyohyke' value='{$keraysvyohyke}' />";
						echo "<input type='hidden' name='keraajanro' value='{$keraajanro}' />";
						echo "<input type='hidden' name='keraajalist' value='{$keraajalist}' />";
						echo "<input type='hidden' name='who' value='{$who}' />";
						echo "<input type='hidden' name='select_varasto' value='{$select_varasto}' />";
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
					echo "<th>",t("M‰‰r‰"),"</th>";

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
							echo "&nbsp;<img title='",t("N‰yt‰ rivi"),"' alt='",t("N‰yt‰ rivi"),"' src='{$palvelin2}pics/lullacons/go-down.png' />";
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
								echo "<th>",t("M‰‰r‰"),"</th>";
								echo "<th>",t("Asiakas"),"</th>";
								echo "<th>",t("Siirr‰")," (",t("Minne ja kuinka paljon"),")</th>";
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
									echo "<option value=''>",t("Ei siirret‰"),"</option>";

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
	else {
		if (trim($kukarow['keraysvyohyke']) == '') {
			echo "<font class='error'>",t("Ker‰ysvyˆhyke t‰ytyy valita k‰ytt‰j‰n takanta"),"</font><br />";
		}
	}

	require ("inc/footer.inc");