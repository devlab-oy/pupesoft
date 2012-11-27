<?php

	require ("../inc/parametrit.inc");

	js_popup();

	echo "<font class='head'>",t("Tulosta ker‰yser‰"),"</font><hr>";

	echo "	<script type='text/javascript' language='JavaScript'>
				$(document).ready(function() {

					$('.notoggle').click(function(event){
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
	if (!isset($keraajalist)) $keraajalist = '';
	if (!isset($select_varasto)) $select_varasto = '';
	if (!isset($keraajanro)) $keraajanro = '';

	if ($tee == 'tee_arpajaiset') {
		if (isset($palaa)) {
			$kerayseranro = "";
			$tee = "selaa";
		}
		if (isset($muuta)) {
			$tee = "muuta";
		}
		if (isset($uusi_pakkaus)) {
			$tee = "uusi_pakkaus";
		}
	}

	if ($tee == '') {
		echo "<form method='post'>";
		echo "<input type='hidden' name='tee' value='selaa' />";

		echo "<table>";

		$query = "	SELECT *
					FROM kuka
					WHERE yhtio 	= '{$kukarow['yhtio']}'
					AND extranet 	= ''
					AND (keraajanro > 0 OR kuka = '{$kukarow['kuka']}')
					AND keraysvyohyke != ''";
		$kuresult = pupe_query($query);

		echo "<tr><th>",t("Ker‰‰j‰"),"</th><td><input type='text' size='5' name='keraajanro'> ",t("tai")," ";
		echo "<select name='keraajalist'>";

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

		$query = "	SELECT tunnus, nimitys
					FROM varastopaikat
					WHERE yhtio = '{$kukarow['yhtio']}' AND tyyppi != 'P'
					ORDER BY tyyppi, nimitys";
		$varastores = pupe_query($query);

		echo "<tr><th>",t("Valitse varasto"),"</th><td>&nbsp;";
		echo "<select name='select_varasto'>";

		while ($varastorow = mysql_fetch_assoc($varastores)) {

			$sel = ($kukarow['oletus_varasto'] == $varastorow['tunnus']) ? " selected" : "";

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

			$query = "	SELECT selite
						FROM avainsana
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND laji 	= 'SSCC'";
			$result = pupe_query($query);
			$row = mysql_fetch_assoc($result);

			$sscc = is_numeric($row['selite']) ? (int) $row['selite'] + 1 : 1;

			if (trim($row['selite']) == '') {

				$query = "	INSERT INTO avainsana SET
							yhtio 			= '{$kukarow['yhtio']}',
							perhe 			= '666',
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
				$query = "	UPDATE avainsana
							SET selite  = '{$sscc}'
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND laji	= 'SSCC'";
				$update_res = pupe_query($query);
			}

			// poistetaan lukko
			$query = "UNLOCK TABLES";
			$res   = pupe_query($query);

			$query = "	SELECT tila, keraysvyohyke, (MAX(pakkausnro) + 1) uusi_pakkauskirjain
						FROM kerayserat
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND nro = '{$kerayseranro}'
						GROUP BY 1,2";
			$uusi_paknro_res = pupe_query($query);
			$uusi_paknro_row = mysql_fetch_assoc($uusi_paknro_res);

			$query = "	INSERT INTO kerayserat SET
						yhtio 			= '{$kukarow['yhtio']}',
						nro 			= '{$kerayseranro}',
						keraysvyohyke 	= '{$uusi_paknro_row['keraysvyohyke']}',
						tila			= '{$uusi_paknro_row['tila']}',
						sscc 			= '{$sscc}',
						otunnus 		= 0,
						tilausrivi 		= 0,
						pakkaus 		= 0,
						pakkausnro 		= '{$uusi_paknro_row['uusi_pakkauskirjain']}',
						kpl 			= 0,
						laatija 		= '{$kukarow['kuka']}',
						luontiaika 		= now()";
			$ins_uusi_pak_res = pupe_query($query);

			echo "<br /><font class='message'>",t("Uusi pakkaus lis‰tty"),"!</font><br />";
		}

		$tee = 'muokkaa';
		$view = 'yes';
	}

	if ($tee == 'muuta') {
		$keraajasiirto = FALSE;

		if (trim($kayttaja) != '') {

			$query = "LOCK TABLES kerayserat WRITE";
			$lock = mysql_query($query) or pupe_error($query);

			// Tarkistetaan, ett‰ nykyisell‰ ker‰‰j‰ll‰ ei ole t‰t‰ keikkaa optiscanissa kesken
			$query = "	SELECT tunnus
						FROM kerayserat
						WHERE yhtio 		= '{$kukarow['yhtio']}'
						AND nro 			= '{$kerayseranro}'
						AND tila    		= 'K'
						AND ohjelma_moduli	= 'OPTISCAN'";
			$kerkeskres = pupe_query($query);
			$keredkesk = (mysql_num_rows($kerkeskres) > 0) ? TRUE : FALSE;

			// Tarkistetaan, ett‰ uudella ker‰‰j‰ll‰ ei ole toista keikkaa optiscanissa kesken
			$query = "	SELECT tunnus
						FROM kerayserat
						WHERE yhtio 		= '{$kukarow['yhtio']}'
						AND laatija 		= '{$kayttaja}'
						AND tila    		= 'K'
						AND ohjelma_moduli	= 'OPTISCAN'";
			$kerkeskres = pupe_query($query);
			$kertukesk = (mysql_num_rows($kerkeskres) > 0) ? TRUE : FALSE;

			if ($keredkesk) {
				echo "<font class='error'><br>".t("VIRHE: Ker‰yser‰ on kesken ker‰‰j‰ll‰ puheker‰yksess‰")."!</font><br><br>";
			}
			elseif ($kertukesk) {
				echo "<font class='error'><br>".t("VIRHE: Ker‰‰j‰ll‰ on jo toinen er‰ puheker‰yksess‰")."!</font><br><br>";
			}
			else {
				$query = "	UPDATE kerayserat
							SET laatija = '{$kayttaja}'
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND nro 	= '{$kerayseranro}'";
				$update_res = pupe_query($query);

				$keraajasiirto = TRUE;
			}

			$query = "UNLOCK TABLES";
			$lock = mysql_query($query) or pupe_error($query);
		}

		if (count($pakkaukset) > 0) {

			foreach ($pakkaukset as $pak_nro => $pak) {
				list($rivitunnus, $pak_nro) = explode('##', $pak_nro);
				list($rivitunnus, $pakkaus) = explode('##', $pak);

				$query = "	UPDATE kerayserat SET
							pakkaus = '{$pakkaus}'
							WHERE yhtio 	= '{$kukarow['yhtio']}'
							AND nro 		= '{$kerayseranro}'
							AND pakkausnro 	= '{$pak_nro}'";
				$update_res = pupe_query($query);

				$query = "	SELECT tunnus
							FROM kerayserat
							WHERE yhtio 	= '{$kukarow['yhtio']}'
							AND nro 		= '{$kerayseranro}'
							AND pakkausnro 	= '{$pak_nro}'";
				$rivitunnukset_res = pupe_query($query);

				while ($rivitunnukset_row = mysql_fetch_assoc($rivitunnukset_res)) {

					if (isset($poista_pakkaus[$pak_nro]) and trim($poista_pakkaus[$pak_nro]) != '') {
						$query = "	DELETE FROM kerayserat
									WHERE yhtio 	= '{$kukarow['yhtio']}'
									AND nro 		= '{$kerayseranro}'
									AND pakkausnro 	= '{$pak_nro}'";
						$del_res = pupe_query($query);
						continue;
					}

					if (isset($siirra_tuote[$rivitunnukset_row['tunnus']]) and trim($siirra_tuote[$rivitunnukset_row['tunnus']]) != '' and isset($siirra_kpl[$rivitunnukset_row['tunnus']]) and trim($siirra_kpl[$rivitunnukset_row['tunnus']]) != '') {

						$mihin_siirretaan  = $siirra_tuote[$rivitunnukset_row['tunnus']];
						$paljon_siirretaan = str_replace(",", ".", $siirra_kpl[$rivitunnukset_row['tunnus']]);

						$query = "	SELECT *,
									if (kerattyaika = '0000-00-00 00:00:00', kpl, kpl_keratty) kpl
									FROM kerayserat
									WHERE yhtio = '{$kukarow['yhtio']}'
									AND nro 	= '{$kerayseranro}'
									AND tunnus 	= '{$rivitunnukset_row['tunnus']}'";
						$kpl_chk_res = pupe_query($query);
						$kpl_chk_row = mysql_fetch_assoc($kpl_chk_res);

						if ($paljon_siirretaan > $kpl_chk_row['kpl']) {
							echo "<br /><font class='error'>",t("Siirrett‰v‰ kappalem‰‰r‰ oli liian suuri"),"! ({$paljon_siirretaan} > {$kpl_chk_row['kpl']})</font><br />";
						}
						else {
							$kerattylisa1 = $kerattylisa2 = $kerattylisa3 = "";

							// Oliko t‰m‰ rivi ker‰tty?
							if ($kpl_chk_row["kerattyaika"] != "0000-00-00 00:00:00") {
								$kerattylisa1 = " , kpl_keratty = '{$paljon_siirretaan}', keratty = '{$kpl_chk_row['keratty']}', kerattyaika = '{$kpl_chk_row['kerattyaika']}' ";
								$kerattylisa2 = " , kpl_keratty = kpl_keratty + {$paljon_siirretaan} ";
								$kerattylisa3 = " , kpl_keratty = kpl_keratty - {$paljon_siirretaan} ";
							}

							// katsotaan onko nollarivej‰ (eli tyhj‰ pakkaus)
							$query = "	SELECT *
										FROM kerayserat
										WHERE yhtio 	= '{$kukarow['yhtio']}'
										AND nro 		= '{$kerayseranro}'
										AND pakkausnro 	= '{$mihin_siirretaan}'";
							$chk_res = pupe_query($query);
							$chk_row = mysql_fetch_assoc($chk_res);

							if ($chk_row['otunnus'] == 0 or $chk_row['tilausrivi'] == 0 or $chk_row['kpl'] == 0) {
								$query = "	UPDATE kerayserat SET
											otunnus 	= '{$kpl_chk_row['otunnus']}',
											tilausrivi 	= '{$kpl_chk_row['tilausrivi']}',
											kpl 		= '{$paljon_siirretaan}'
											{$kerattylisa1}
											WHERE yhtio = '{$kukarow['yhtio']}'
											AND nro 	= '{$kerayseranro}'
											AND tunnus 	= '{$chk_row['tunnus']}'";
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
												AND tunnus 	= '{$chk_row['tilausrivi']}'";
									$check_it_res = pupe_query($query);
									$check_it_row = mysql_fetch_assoc($check_it_res);

									if ($kassellaan_row['tuoteno'] == $check_it_row['tuoteno']) {
										$query = "	UPDATE kerayserat SET
													kpl = kpl + {$paljon_siirretaan}
													{$kerattylisa2}
													WHERE yhtio = '{$kukarow['yhtio']}'
													AND nro 	= '{$kerayseranro}'
													AND tunnus 	= '{$chk_row['tunnus']}'";
										$upd_res = pupe_query($query);

										$loytyko = 'joo';
										break;
									}
								}

								if (!$loytyko) {
									mysql_data_seek($chk_res, 0);

									$query = "	INSERT INTO kerayserat SET
												yhtio 			= '{$kukarow['yhtio']}',
												nro		 		= '{$kerayseranro}',
												keraysvyohyke 	= '{$kpl_chk_row['keraysvyohyke']}',
												tila 			= '{$kpl_chk_row['tila']}',
												sscc 			= '{$kpl_chk_row['sscc']}',
												otunnus 		= '{$kpl_chk_row['otunnus']}',
												tilausrivi 		= '{$kpl_chk_row['tilausrivi']}',
												pakkaus 		= '{$kpl_chk_row['pakkaus']}',
												pakkausnro 		= '{$mihin_siirretaan}',
												kpl 			= '{$paljon_siirretaan}',
												laatija 		= '{$keraajarow['kuka']}',
												luontiaika 		= now()
												{$kerattylisa1}";
									$ins_res = pupe_query($query);
								}
							}

							if (($kpl_chk_row['kpl'] - $paljon_siirretaan) == 0) {
								$query = "	DELETE FROM kerayserat
											WHERE yhtio = '{$kukarow['yhtio']}'
											AND nro 	= '{$kerayseranro}'
											AND tunnus 	= '{$rivitunnukset_row['tunnus']}'";
								$del_res = pupe_query($query);
							}
							else {
								$query = "	UPDATE kerayserat
											SET kpl = kpl - {$paljon_siirretaan}
											{$kerattylisa3}
											WHERE yhtio = '{$kukarow['yhtio']}'
											AND nro 	= '{$kerayseranro}'
											AND tunnus 	= '{$rivitunnukset_row['tunnus']}'";
								$upd_res = pupe_query($query);
							}
						}
					}
				}

				if (isset($tulostettavat_reittietiketit[$pak_nro]) and count($tulostettavat_reittietiketit) > 0 and trim($tulostettavat_reittietiketit[$pak_nro]) != '') {

					$query = "	SELECT nro, sscc, laatija
								FROM kerayserat
								WHERE yhtio 	= '{$kukarow['yhtio']}'
								AND pakkausnro 	= '{$pak_nro}'
								AND nro 		= '{$kerayseranro}'";
					$reittietiketti_res = pupe_query($query);
					$reittietiketti_row = mysql_fetch_assoc($reittietiketti_res);

					$query = "	SELECT *
								FROM kuka
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND kuka = '{$reittietiketti_row['laatija']}'";
					$result = pupe_query($query);

					if (mysql_num_rows($result) == 0) {
						echo "<font class='error'>",t("Ker‰‰j‰‰")," {$reittietiketti_row['laatija']} ",t("ei lˆydy"),"!</font><br>";
					}
					else {

						// Valitun ker‰‰j‰n tiedot
						$keraajarow = mysql_fetch_assoc($result);

						// Otetaan alkup kukarow talteen
						$kukarow_orig = $kukarow;

						// HUOM: Generoidaan ker‰yser‰ valitulle k‰ytt‰j‰lle
						$kukarow = $keraajarow;

						$kerayseran_numero = $reittietiketti_row["nro"];
						$uusi_sscc = $reittietiketti_row["sscc"];

						// Tulostetaan kollilappu
						require('inc/tulosta_reittietiketti.inc');

						// Palautetaan alkup kukarow
						$kukarow = $kukarow_orig;
					}
				}
			}
		}

		$tee = 'muokkaa';
		$view = 'yes';

		if ($keraajasiirto) {
			$kerayseranro = "";
			$tee = 'selaa';
		}
	}

	if ($tee != '') {

		if ((int) $keraajanro > 0) {
			$query = "	SELECT *
						FROM kuka
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND keraajanro = '{$keraajanro}'
						AND keraysvyohyke != ''";
		}
		else {
			$query = "	SELECT *
						FROM kuka
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND kuka = '{$keraajalist}'
						AND keraysvyohyke != ''";
		}
		$result = pupe_query($query);

		if (mysql_num_rows($result) == 0) {
			echo "<font class='error'>",t("Ker‰‰j‰‰")," {$keraajanro} ",t("ei lˆydy"),"!</font><br>";
		}
		elseif ($select_varasto == '') {
			echo "<font class='error'>",t("Et valinnut varastoa"),"!</font><br>";
		}
		else {

			// Valitun ker‰‰j‰n tiedot
			$keraajarow = mysql_fetch_assoc($result);

			echo "<form method='post'>";

			if ($tee != 'muuta' and $tee != 'muokkaa') {
				echo "<input type='hidden' name='tee' value='keraysera' />";
			}

			echo "<input type='hidden' name='keraajanro' value='{$keraajanro}' />";
			echo "<input type='hidden' name='keraajalist' value='{$keraajalist}' />";
			echo "<input type='hidden' name='select_varasto' value='{$select_varasto}' />";

			echo "<table>";
			echo "<tr><th>",t("Ker‰‰j‰"),"</th><td>{$keraajarow['nimi']}</td></tr>";
			echo "<tr><th>",t("Valitse reittietiketin tulostin"),"</th><td>";
			echo "<select name='reittietikettitulostin'>";
			echo "<option value=''>",t("Ei kirjoitinta"),"</option>";

			$select_varasto = (int) $select_varasto;

			$querykieli = "	SELECT kirjoittimet.kirjoitin, keraysvyohyke.tunnus, keraysvyohyke.printteri8, keraysvyohyke.printteri0, kirjoittimet.tunnus as kir_tunnus
							FROM kirjoittimet
							JOIN keraysvyohyke ON (keraysvyohyke.yhtio = kirjoittimet.yhtio AND keraysvyohyke.tunnus IN ({$keraajarow['keraysvyohyke']}) AND keraysvyohyke.varasto = '{$select_varasto}')
							JOIN kuka ON (kuka.yhtio = keraysvyohyke.yhtio AND kuka.keraysvyohyke = keraysvyohyke.tunnus AND kuka.kuka = '{$keraajarow['kuka']}')
							WHERE kirjoittimet.yhtio = '{$kukarow['yhtio']}'
							AND kirjoittimet.komento != 'EDI'
							GROUP BY 1,2,3,4,5
							ORDER BY kirjoittimet.kirjoitin";
			$kires = pupe_query($querykieli);

			while ($kirow = mysql_fetch_assoc($kires)) {

				$sel = "";
				if (strpos($keraajarow["keraysvyohyke"], $kirow["tunnus"]) !== FALSE and $kirow['kir_tunnus'] == $kirow['printteri8']) {
					$sel = " selected";
				}

				echo "<option value='{$kirow['kir_tunnus']}' {$sel}>{$kirow['kirjoitin']}</option>";
			}

			echo "</select></td>";

			if ($tee != 'muuta' and $tee != 'muokkaa') {
				echo "<td class='back'>&nbsp;</td>";
			}

			echo "</tr>";

			if ($tee != 'muuta' and $tee != 'muokkaa') {
				echo "<tr><th>",t("Valitse ker‰yslistan tulostin"),"</th><td>";
				echo "<select name='kerayslistatulostin'>";
				echo "<option value=''>",t("Ei kirjoitinta"),"</option>";

				mysql_data_seek($kires, 0);

				while ($kirow = mysql_fetch_assoc($kires)) {

					$sel = "";
					if (strpos($keraajarow["keraysvyohyke"], $kirow["tunnus"]) !== FALSE and $kirow['kir_tunnus'] == $kirow['printteri0']) {
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

			if ($tee == 'keraysera' and trim($keraajarow['keraysvyohyke']) != '' and $select_varasto > 0) {

				echo "<br><br>";
				echo "<div id='content'></div>";

				// Otetaan alkup kukarow talteen
				$kukarow_orig = $kukarow;

				// HUOM: Generoidaan ker‰yser‰ valitulle k‰ytt‰j‰lle
				$kukarow = $keraajarow;

				// HUOM!!! FUNKTIOSSA TEHDƒƒN LOCK TABLESIT, LUKKOJA EI AVATA TƒSSƒ FUNKTIOSSA! MUISTA AVATA LUKOT FUNKTION KƒYT÷N JƒLKEEN!!!!!!!!!!
				$erat = tee_keraysera($keraajarow['keraysvyohyke'], $select_varasto);

				if (isset($erat['tilaukset']) and count($erat['tilaukset']) > 0) {

					// Tallennetaan ker‰yser‰
					require('inc/tallenna_keraysera.inc');

					// N‰m‰ tilaukset tallennettin ker‰yser‰‰n
					if (isset($lisatyt_tilaukset) and count($lisatyt_tilaukset) > 0) {

						$otunnukset = implode(",", $lisatyt_tilaukset);
						$kerayslistatunnus = array_shift(array_keys($lisatyt_tilaukset));

						$query = "	SELECT *
									FROM lasku
									WHERE yhtio = '{$kukarow['yhtio']}'
									AND tunnus IN ($otunnukset)";
						$res = pupe_query($query);
						$laskurow = mysql_fetch_assoc($res);

						$tilausnumeroita  	  = $otunnukset;
						$valittu_tulostin 	  = $kerayslistatulostin;
						$keraysvyohyke		  = $erat['keraysvyohyketiedot']['keraysvyohyke'];
						$laskuja 			  = count($erat['tilaukset']);
						$lukotetaan 		  = FALSE;

						require("tilauskasittely/tilaus-valmis-tulostus.inc");
					}
				}
				else {
					echo "<font class='message'>",t("Ei ole yht‰‰n ker‰tt‰v‰‰ ker‰yser‰‰"),".</font><br />";
				}

				// lukitaan tableja
		 		$query = "UNLOCK TABLES";
				$result = pupe_query($query);

				if (isset($lisatyt_tilaukset) and count($lisatyt_tilaukset) > 0) {

					// Tulostetaan kollilappu
					require('inc/tulosta_reittietiketti.inc');

					if ($erat['keraysvyohyketiedot']['ulkoinen_jarjestelma'] == "K") {
						require("inc/kardex_send.inc");
					}
				}

				// Palautetaan alkup kukarow
				$kukarow = $kukarow_orig;
			}

			echo "<br /><br />";

			if ($tee == 'selaa') {
				$etsi_kerayseraa = !isset($etsi_kerayseraa) ? '' : $etsi_kerayseraa;

				$kerayseranro = (isset($etsi_kerayseraa) and trim($etsi_kerayseraa) != '') ? (int) $etsi_kerayseraa : (isset($kerayseranro) ? $kerayseranro : '');

				echo "<form method='post'>";
				echo "<input type='hidden' name='keraajanro' value='{$keraajanro}' />";
				echo "<input type='hidden' name='keraajalist' value='{$keraajalist}' />";
				echo "<input type='hidden' name='tee' value='selaa' />";
				echo "<input type='hidden' name='select_varasto' value='{$select_varasto}' />";
				echo t('Etsi'),":&nbsp;<input type='text' name='etsi_kerayseraa' value='{$etsi_kerayseraa}' />&nbsp;";
				echo "<input type='submit' value='",t("Hae"),"' />";
				echo "</form>";
				echo "<br /><br />";
			}

			$ker_lisa = (isset($kerayseranro) and $kerayseranro > 0) ? " AND kerayserat.nro = '{$kerayseranro}' " : '';

			$query = "	SELECT kerayserat.nro,
						keraysvyohyke.nimitys AS keraysvyohyke,
						kerayserat.laatija AS kayttaja,
						max(kerayserat.luontiaika) luontiaika,
						max(kerayserat.kerattyaika) kerattyaika,
						COUNT(DISTINCT lasku.liitostunnus) AS asiakasmaara,
						COUNT(*) AS rivimaara
						FROM kerayserat
						JOIN lasku ON (lasku.yhtio = kerayserat.yhtio AND lasku.tunnus = kerayserat.otunnus)
						JOIN tilausrivi ON (tilausrivi.yhtio = kerayserat.yhtio AND tilausrivi.tunnus = kerayserat.tilausrivi)
						JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
						JOIN varaston_hyllypaikat vh ON (vh.yhtio = tilausrivi.yhtio AND vh.hyllyalue = tilausrivi.hyllyalue AND vh.hyllynro = tilausrivi.hyllynro AND vh.hyllyvali = tilausrivi.hyllyvali AND vh.hyllytaso = tilausrivi.hyllytaso AND vh.keraysvyohyke IN ({$keraajarow['keraysvyohyke']}))
						JOIN keraysvyohyke ON (keraysvyohyke.yhtio = tuote.yhtio AND keraysvyohyke.tunnus = vh.keraysvyohyke)
						WHERE kerayserat.yhtio = '{$kukarow['yhtio']}'
						AND kerayserat.laatija = '{$keraajarow['kuka']}'
						AND (kerayserat.luontiaika >= DATE_SUB(NOW(), INTERVAL 2 DAY) OR kerayserat.tila in ('K','X'))
						{$ker_lisa}
						GROUP BY 1,2,3
						ORDER BY 1 desc";
			$kerayserat_res = pupe_query($query);

			if (mysql_num_rows($kerayserat_res) > 0) {
				echo "<img title='",t("N‰yt‰ rivi esimerkkikuva"),"' alt='",t("N‰yt‰ rivi esimerkkikuva"),"' src='{$palvelin2}pics/lullacons/go-down.png' /> = <font class='message'>",t("Saat lis‰‰ infoa ker‰yser‰st‰ klikkaamalla rivi‰"),"</font><br /><br />";

				echo "<table>";
				echo "<tr>";
				echo "<th>",t("Nro"),"</th>";
				echo "<th>",t("Ker‰ysvyˆhyke"),"</th>";
				echo "<th>",t("Rivej‰"),"</th>";
				echo "<th>",t("Asiakkaita"),"</th>";
				echo "<th>",t("Laadittu"),"<br>",t("Ker‰tty"),"</th>";
				echo "<th>",t("Ker‰‰j‰"),"</th>";
				echo "</tr>";

				while ($kerayserat_row = mysql_fetch_assoc($kerayserat_res)) {
					echo "<tr class='toggleable' id='{$kerayserat_row['nro']}'>";
					echo "<td>{$kerayserat_row['nro']}&nbsp;<img title='",t("N‰yt‰ rivi"),"' alt='",t("N‰yt‰ rivi"),"' src='{$palvelin2}pics/lullacons/go-down.png' /></td>";
					echo "<td>{$kerayserat_row['keraysvyohyke']}</td>";
					echo "<td>{$kerayserat_row['rivimaara']}</td>";
					echo "<td>{$kerayserat_row['asiakasmaara']}</td>";
					echo "<td>".tv1dateconv($kerayserat_row["luontiaika"], "PITKA", "LYHYT")."<br>".tv1dateconv($kerayserat_row["kerattyaika"], "PITKA", "LYHYT")."</td>";

					echo "<td>";

					$kuka_lisa = ($tee != 'muokkaa' or $kerayserat_row["kerattyaika"] != "0000-00-00 00:00:00") ? " AND kuka = '{$kerayserat_row['kayttaja']}' " : '';

					$query = "	SELECT kuka, nimi
								FROM kuka
								WHERE yhtio 	   = '{$kukarow['yhtio']}'
								AND extranet 	   = ''
								AND nimi 		  != ''
								AND keraysvyohyke != ''
								{$kuka_lisa}
								ORDER BY nimi";
					$kuka_res = pupe_query($query);

					if ($tee == 'muokkaa' and $kerayserat_row["kerattyaika"] == "0000-00-00 00:00:00") {

						echo "<select class='notoggle' name='kayttaja'>";

						while ($kuka_row = mysql_fetch_assoc($kuka_res)) {

							$sel = $kuka_row['kuka'] == $kerayserat_row['kayttaja'] ? ' selected' : '';

							echo "<option value='{$kuka_row['kuka']}'{$sel}>{$kuka_row['nimi']}</option>";
						}

						echo "</select>";
					}
					else {
						$kuka_row = mysql_fetch_assoc($kuka_res);
						echo $kuka_row['nimi'];
					}

					echo "</td>";

					echo "<td class='back'>";

					if ($tee == 'selaa' or $tee == 'keraysera') {
						echo "<form method='post'>";
						echo "<input type='hidden' name='kerayseranro' value='{$kerayserat_row['nro']}' />";
						echo "<input type='hidden' name='keraajanro' value='{$keraajanro}' />";
						echo "<input type='hidden' name='keraajalist' value='{$keraajalist}' />";
						echo "<input type='hidden' name='tee' value='muokkaa' />";
						echo "<input type='hidden' name='select_varasto' value='{$select_varasto}' />";
						echo "<input type='submit' value='",t("Muokkaa"),"' />";
						echo "</form>";
					}
					elseif ($tee == 'muokkaa') {
						echo "<input type='hidden' name='kerayseranro' value='{$kerayserat_row['nro']}' />";
						echo "<input type='hidden' name='keraajanro' value='{$keraajanro}' />";
						echo "<input type='hidden' name='keraajalist' value='{$keraajalist}' />";
						echo "<input type='hidden' name='select_varasto' value='{$select_varasto}' />";
						echo "<input type='hidden' name='tee' value='tee_arpajaiset'>";
						echo "<input class='notoggle' type='submit' name='muuta' value='",t("Tallenna"),"'>";
						echo "<input class='notoggle' type='submit' name='uusi_pakkaus' value='",t("Uusi pakkaus"),"'>";
						echo "<input class='notoggle' type='submit' name='palaa' value='",t("Palaa"),"'>";
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

					$query = "	SELECT kerayserat.pakkaus, kerayserat.tunnus AS rivitunnus,
								kerayserat.pakkausnro, kerayserat.sscc,
								kerayserat.otunnus, {$selectlisa}
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
							echo "<select class='notoggle' name='pakkaukset[{$rivit_row['rivitunnus']}##{$rivit_row['pakkausnro']}]'>";
							echo "<option value='{$rivit_row['rivitunnus']}##999'>".t("Muu")."</option>";

							$query = "	SELECT tunnus, TRIM(CONCAT(pakkaus, ' ', pakkauskuvaus)) pakkaus
										FROM pakkaus
										WHERE yhtio = '{$kukarow['yhtio']}'
										ORDER BY pakkaus, pakkauskuvaus";
							$pakkaus_vaihto_res = pupe_query($query);

							while ($pakkaus_vaihto_row = mysql_fetch_assoc($pakkaus_vaihto_res)) {

								$sel = $rivit_row['pakkaus'] == $pakkaus_vaihto_row['tunnus'] ? ' selected' : '';

								echo "<option value='{$rivit_row['rivitunnus']}##{$pakkaus_vaihto_row['tunnus']}'{$sel}>{$pakkaus_vaihto_row['pakkaus']}</option>";
							}

							echo "</select>";
						}
						else {
							$query = "	SELECT tunnus, TRIM(CONCAT(pakkaus, ' ', pakkauskuvaus)) pakkaus
										FROM pakkaus
										WHERE yhtio = '{$kukarow['yhtio']}'
										AND tunnus  = {$rivit_row['pakkaus']}";
							$pakkaus_vaihto_res = pupe_query($query);
							$pakkaus_vaihto_row = mysql_fetch_assoc($pakkaus_vaihto_res);

							echo $pakkaus_vaihto_row['pakkaus'];
						}

						echo "</td>";

						if ($tee == 'muokkaa') {
							echo "<td>";
							echo "<input class='notoggle' type='checkbox' name='tulostettavat_reittietiketit[{$rivit_row['pakkausnro']}]' value='{$rivit_row['pakkausnro']}' />";
							echo "</td>";
						}

						echo "</tr>";

						if ($tee == 'muokkaa') {
							$query = "	SELECT kerayserat.pakkaus,
										kerayserat.tunnus AS rivitunnus,
										kerayserat.pakkausnro,
										tilausrivi.tuoteno,
										TRIM(CONCAT(asiakas.nimi, ' ', asiakas.nimitark)) asiakasnimi,
										if (kerayserat.kerattyaika = '0000-00-00 00:00:00', kerayserat.kpl, kerayserat.kpl_keratty) kpl,
										lasku.liitostunnus,
										CONCAT(tilausrivi.hyllyalue, ' ', tilausrivi.hyllynro, ' ', tilausrivi.hyllyvali, ' ', tilausrivi.hyllytaso) hyllypaikka,
										tilausrivi.tunnus AS tilausrivin_tunnus
							 			FROM kerayserat
										JOIN tilausrivi ON (tilausrivi.yhtio = kerayserat.yhtio AND tilausrivi.tunnus = kerayserat.tilausrivi)
										JOIN lasku ON (lasku.yhtio = kerayserat.yhtio AND lasku.tunnus = kerayserat.otunnus)
										JOIN asiakas ON (asiakas.yhtio = lasku.yhtio AND asiakas.tunnus = lasku.liitostunnus)
										WHERE kerayserat.yhtio 		= '{$kukarow['yhtio']}'
										AND kerayserat.nro 			= '{$kerayserat_row['nro']}'
										AND kerayserat.pakkausnro 	= '{$rivit_row['pakkausnro']}'
										ORDER BY pakkausnro";
							$content_res = pupe_query($query);

							echo "<tr>";
							echo "<td colspan='6' class='back'>";

							$viewable = (isset($view) and $view == 'yes') ? '' : 'display:none;';

							echo "<div id='toggleable_",chr((64+$rivit_row['pakkausnro'])),"' style='{$viewable} width:100%'>";

							echo "<table style='width:100%'>";

							if (mysql_num_rows($content_res) == 0) {
								echo "<tr><td>",t("Poista pakkaus")," <input class='notoggle' type='checkbox' name='poista_pakkaus[{$rivit_row['pakkausnro']}]' value='{$rivit_row['pakkausnro']}' /></td></tr>";
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

	require ("inc/footer.inc");
