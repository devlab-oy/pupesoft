<?php

	if (php_sapi_name() != 'cli' and strpos($_SERVER['SCRIPT_NAME'], "keraa.php") !== FALSE) {
		require ("../inc/parametrit.inc");

		js_popup();
	}

	if ($toim == "VASTAANOTA_REKLAMAATIO" and $yhtiorow['reklamaation_kasittely'] != 'U') {
		echo "<font class='error'>",t("HUOM: Ohjelma on k‰ytˆss‰ vain kun k‰ytet‰‰n laajaa reklamaatioprosessia"),"!</font>";
		exit;
	}

	$logistiikka_yhtio = '';
	$logistiikka_yhtiolisa = '';

	if (!isset($toim)) 			$toim 			= '';
	if (!isset($id)) 			$id 			= 0;
	if (!isset($tee)) 			$tee 			= '';
	if (!isset($jarj)) 			$jarj 			= '';
	if (!isset($etsi)) 			$etsi 			= '';
	if (!isset($tuvarasto)) 	$tuvarasto 		= '';
	if (!isset($tumaa)) 		$tumaa 			= '';
	if (!isset($tutoimtapa)) 	$tutoimtapa 	= '';
	if (!isset($tutyyppi))	 	$tutyyppi 		= '';
	if (!isset($rahtikirjaan)) 	$rahtikirjaan 	= '';
	if (!isset($sorttaus)) 		$sorttaus 		= '';
	if (!isset($keraajalist)) 	$keraajalist 	= '';
	if (!isset($sel_lahete)) 	$sel_lahete 	= array();
	if (!isset($sel_oslapp)) 	$sel_oslapp 	= array();

	$keraysvirhe = 0;
	$virherivi   = 0;
	$muuttuiko	 = '';

	if ($yhtiorow['konsernivarasto'] != '' and $konsernivarasto_yhtiot != '') {
		$logistiikka_yhtio = $konsernivarasto_yhtiot;
		$logistiikka_yhtiolisa = "yhtio IN ({$logistiikka_yhtio})";

		if (isset($lasku_yhtio) and $lasku_yhtio != '') {
			$kukarow['yhtio'] = mysql_real_escape_string($lasku_yhtio);

			$yhtiorow = hae_yhtion_parametrit($lasku_yhtio);
		}
	}
	else {
		$logistiikka_yhtiolisa = "yhtio = '{$kukarow['yhtio']}'";
	}

	if ($yhtiorow['kerayserat'] == 'K' and $toim == "") {
		require_once("inc/unifaun_send.inc");

		if (php_sapi_name() != 'cli' and strpos($_SERVER['SCRIPT_NAME'], "keraa.php") !== FALSE) {
			echo "	<script type='text/javascript' language='JavaScript'>
						$(document).ready(function() {
							$('input[name^=\"keraysera_maara\"]').keyup(function(){
								var rivitunnukset = $(this).attr('id').split(\"_\", 2);
								var yhteensa = 0;

								$('input[id^=\"'+rivitunnukset[0]+'\"]').each(function(){
									yhteensa += Number($(this).val().replace(',', '.'));
								});

								if (parseFloat(yhteensa) == parseFloat($('#'+rivitunnukset[0]+'_varattu').html().replace(',', '.'))) {
									yhteensa = '';
								}

								$('#maara_'+rivitunnukset[0]).val(yhteensa);
								$('#maaran_paivitys_'+rivitunnukset[0]).html(yhteensa);
							});
						});
					</script>";
		}
	}

	if ($toim == 'SIIRTOLISTA') {
		echo "<font class='head'>",t("Ker‰‰ siirtolista"),":</font><hr>";
		$tila = "'G'";
		$tyyppi = "'G'";
		$tilaustyyppi = " and tilaustyyppi != 'M' ";
	}
	elseif ($toim == 'SIIRTOTYOMAARAYS') {
		echo "<font class='head'>",t("Ker‰‰ sis‰inen tyˆm‰‰r‰ys"),":</font><hr>";
		$tila = "'S'";
		$tyyppi = "'G'";
		$tilaustyyppi = " and tilaustyyppi = 'S' ";
	}
	elseif ($toim == 'MYYNTITILI') {
		echo "<font class='head'>",t("Ker‰‰ myyntitili"),":</font><hr>";
		$tila = "'G'";
		$tyyppi = "'G'";
		$tilaustyyppi = " and tilaustyyppi = 'M' ";
	}
	elseif ($toim == 'VALMISTUS') {
		echo "<font class='head'>",t("Ker‰‰ valmistus"),":</font><hr>";
		$tila = "'V'";
		$tyyppi = "'V','L'";
		$tilaustyyppi = "";
	}
	elseif ($toim == 'VALMISTUSMYYNTI') {
		echo "<font class='head'>",t("Ker‰‰ tilaus tai valmistus"),":</font><hr>";
		$tila = "'V','L'";
		$tyyppi = "'V','L'";
		$tilaustyyppi = "";
	}
	elseif ($toim == 'VASTAANOTA_REKLAMAATIO') {
		echo "<font class='head'>",t("Hyllyt‰ reklamaatio tai palautus"),":</font><hr>";
		$tila 			= "'C'";
		$alatilarekla 	= "'C'";
		$tyyppi 		= "'L'";
		$tilaustyyppi 	= " and tilaustyyppi = 'R'";
	}
	else {
		echo "<font class='head'>",t("Ker‰‰ tilaus"),":</font><hr>";
		$tila = "'L'";
		$tyyppi = "'L'";
		$tilaustyyppi = "";
	}

	if ($toim != '') {
		$yhtiorow['karayksesta_rahtikirjasyottoon'] = '';
	}
	else {

		if ($yhtiorow['kerayserat'] == 'K' and $toim == "") {
			$yhtiorow['karayksesta_rahtikirjasyottoon'] = '';
		}
		else {
			$query = "	SELECT toimitustapa.tunnus
						FROM toimitustapa, lasku, maksuehto
						WHERE toimitustapa.yhtio = lasku.yhtio and toimitustapa.selite = lasku.toimitustapa
						and lasku.yhtio = maksuehto.yhtio and lasku.maksuehto = maksuehto.tunnus
						and toimitustapa.yhtio = '$kukarow[yhtio]'
						and lasku.tunnus = '$id'
						and ((toimitustapa.nouto is null or toimitustapa.nouto='') or lasku.vienti!='')
						and toimitustapa.tulostustapa != 'H'
						and maksuehto.jv = ''";
			$result = pupe_query($query);

			if (mysql_num_rows($result) == 0) {
				$yhtiorow['karayksesta_rahtikirjasyottoon'] = '';
			}
		}
	}

	if (isset($real_submit)) {
		$real_submit = 'yes';
	}
	else {
		$real_submit = 'no';
	}

	$var_lisa = "";

	if ($yhtiorow["puute_jt_kerataanko"] == "J" or $yhtiorow["puute_jt_kerataanko"] == "Q") {
		$var_lisa .= ",'J'";
	}

	if ($yhtiorow["puute_jt_kerataanko"] == "P" or $yhtiorow["puute_jt_kerataanko"] == "Q") {
		$var_lisa .= ",'P'";
	}

	if ($tee == 'PAKKAUKSET' and ($yhtiorow['kerayserat'] == 'P' or ($yhtiorow['kerayserat'] == 'A' and isset($kerayserat_asiakas_chk) and $kerayserat_asiakas_chk == 'A'))) {

		if (trim($pakkaukset_kaikille) == "") {
			echo "<br /><font class='error'>",t("Pakkausvalinta ei saa olla tyhj‰‰"),"!</font><br />";
		}
		else {
			$query = "	SELECT *
						from lasku
						where yhtio = '{$kukarow['yhtio']}'
						and tunnus  = '{$id}'";
			$testresult = pupe_query($query);
			$laskurow = mysql_fetch_assoc($testresult);

			if ($laskurow['kerayslista'] > 0) {
				//haetaan kaikki t‰lle klˆntille kuuluvat otsikot
				$query = "	SELECT GROUP_CONCAT(DISTINCT tunnus ORDER BY tunnus SEPARATOR ',') tunnukset
							FROM lasku
							WHERE yhtio		= '{$kukarow['yhtio']}'
							AND kerayslista	= '{$id}'
							AND kerayslista != 0
							AND tila		IN ({$tila})
							{$tilaustyyppi}
							HAVING tunnukset IS NOT NULL";
				$toimresult = pupe_query($query);

				//jos rivej‰ lˆytyy niin tiedet‰‰n, ett‰ t‰m‰ on ker‰ysklˆntti
				if (mysql_num_rows($toimresult) > 0) {
					$toimrow = mysql_fetch_assoc($toimresult);
					$tilausnumeroita = $toimrow["tunnukset"];
				}
				else {
					$tilausnumeroita = $id;
				}
			}
			else {
				$tilausnumeroita = $id;
			}

			tee_keraysera_painon_perusteella($laskurow, $tilausnumeroita, $pakkaukset_kaikille);
		}
	}

	if ($tee == 'P') {

		if ($yhtiorow['kerayserat'] == 'K' and $toim == "") {
			$query = "	SELECT GROUP_CONCAT(DISTINCT otunnus SEPARATOR ', ') AS 'tilaukset'
						FROM kerayserat
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND nro 	= '{$id}'";
			$testresult = pupe_query($query);
			$testrow = mysql_fetch_assoc($testresult);

			$tilausnumeroita = $testrow['tilaukset'];
		}
		else {
			$query = "	SELECT kerayslista
						from lasku
						where yhtio = '$kukarow[yhtio]'
						and tunnus  = '$id'";
			$testresult = pupe_query($query);
			$testrow = mysql_fetch_assoc($testresult);

			if ($testrow['kerayslista'] > 0) {
				//haetaan kaikki t‰lle klˆntille kuuluvat otsikot
				$query = "	SELECT GROUP_CONCAT(DISTINCT tunnus ORDER BY tunnus SEPARATOR ',') tunnukset
							FROM lasku
							WHERE yhtio		= '$kukarow[yhtio]'
							and kerayslista	= '$id'
							and kerayslista != 0
							and tila		in ($tila)
							$tilaustyyppi
							HAVING tunnukset is not null";
				$toimresult = pupe_query($query);

				//jos rivej‰ lˆytyy niin tiedet‰‰n, ett‰ t‰m‰ on ker‰ysklˆntti
				if (mysql_num_rows($toimresult) > 0) {
					$toimrow = mysql_fetch_assoc($toimresult);
					$tilausnumeroita = $toimrow["tunnukset"];
				}
				else {
					$tilausnumeroita = $id;
				}
			}
			else {
				$tilausnumeroita = $id;
			}
		}

		// katotaan aluks onko yht‰‰n tuotetta sarjanumeroseurannassa t‰ll‰ ker‰yslistalla
		$query = "	SELECT tilausrivi.tunnus, tilausrivi.tuoteno, tilausrivi.varattu, tuote.sarjanumeroseuranta
					FROM tilausrivi use index (yhtio_otunnus)
					JOIN tuote on tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.sarjanumeroseuranta!=''
					WHERE tilausrivi.yhtio='$kukarow[yhtio]' and
					tilausrivi.otunnus in ($tilausnumeroita) and
					tilausrivi.tyyppi in ('L','G')
					and tilausrivi.var not in ('P','T','U','J')";
		$toimresult = pupe_query($query);

		if (mysql_num_rows($toimresult) > 0) {

			while ($toimrow = mysql_fetch_assoc($toimresult)) {

				if ($toim == 'SIIRTOTYOMAARAYS' or $toim == 'SIIRTOLISTA') {
					$tunken = "siirtorivitunnus";
				}
				elseif ($toimrow["varattu"] < 0) {
					$tunken = "ostorivitunnus";
				}
				else {
					$tunken = "myyntirivitunnus";
				}

				if ($toimrow["sarjanumeroseuranta"] == "S" or $toimrow["sarjanumeroseuranta"] == "T" or $toimrow["sarjanumeroseuranta"] == "U" or $toimrow["sarjanumeroseuranta"] == "V") {
					$query = "	SELECT count(distinct sarjanumero) kpl, min(sarjanumero) sarjanumero
								FROM sarjanumeroseuranta
								WHERE yhtio = '$kukarow[yhtio]'
								and tuoteno = '$toimrow[tuoteno]'
								and $tunken = '$toimrow[tunnus]'";
					$sarjares2 = pupe_query($query);
					$sarjarow = mysql_fetch_assoc($sarjares2);

					if ($sarjarow["kpl"] != abs($toimrow["varattu"])) {
						echo "<font class='error'>".t("Sarjanumeroseurannassa oleville tuotteille on liitett‰v‰ sarjanumero ennen ker‰yst‰")."! ".t("Tuote").": $toimrow[tuoteno].</font><br>";
						$keraysvirhe++;
					}
				}
				else {

					//Siivotaan hieman k‰ytt‰j‰n syˆtt‰m‰‰ kappalem‰‰r‰‰
					$eratsekkpl = (float) str_replace( ",", ".", $maara[$toimrow["tunnus"]]);

					// Muutetaanko er‰seurattavan tuotteen kappalem‰‰r‰‰
					if (trim($maara[$toimrow["tunnus"]]) != '' and $eratsekkpl >= 0) {
						if ($eratsekkpl < $toimrow["varattu"]) {
							//Jos er‰ on keksitty k‰sin t‰‰lt‰ ker‰yksest‰
							$query = "	SELECT *
										FROM sarjanumeroseuranta
										WHERE yhtio = '$kukarow[yhtio]'
										and tuoteno = '$toimrow[tuoteno]'
										and myyntirivitunnus = '$toimrow[tunnus]'
										and ostorivitunnus 	 = '$toimrow[tunnus]'";
							$lisa_res = pupe_query($query);

							if (mysql_num_rows($lisa_res) == 1) {
								$lisa_row = mysql_fetch_assoc($lisa_res);

								$query = "	UPDATE sarjanumeroseuranta
											SET era_kpl	= '$eratsekkpl'
											WHERE yhtio 		 = '$kukarow[yhtio]'
											and tuoteno 		 = '$toimrow[tuoteno]'
											and ostorivitunnus 	 = '$lisa_row[myyntirivitunnus]'
											and myyntirivitunnus = 0";
								$lisa_res = pupe_query($query);
							}

							$keraysvirhe++;
						}
						elseif ($eratsekkpl < $toimrow["varattu"]) {
							$query = "	DELETE FROM sarjanumeroseuranta
										WHERE yhtio = '$kukarow[yhtio]'
										and tuoteno = '$toimrow[tuoteno]'
										and myyntirivitunnus = '$toimrow[tunnus]'";
							$sarjares2 = pupe_query($query);
							$keraysvirhe++;
						}
					}
					else {
						$eratsekkpl = $toimrow["varattu"];
					}

					// P‰ivitet‰‰n er‰
					if ($era_new_paikka[$toimrow["tunnus"]] != $era_old_paikka[$toimrow["tunnus"]]) {

						list($myy_hyllyalue, $myy_hyllynro, $myy_hyllyvali, $myy_hyllytaso, $myy_era) = explode("#", $era_new_paikka[$toimrow["tunnus"]]);

						$query = "	DELETE FROM sarjanumeroseuranta
									WHERE yhtio = '$kukarow[yhtio]'
									and tuoteno = '$toimrow[tuoteno]'
									and myyntirivitunnus = '$toimrow[tunnus]'";
						$sarjares2 = pupe_query($query);

						if ($era_new_paikka[$toimrow["tunnus"]] != "") {

							$oslisa = "";

							if ($toimrow["varattu"] > 0) {
								$query = "	SELECT *
											FROM sarjanumeroseuranta
											WHERE yhtio		= '$kukarow[yhtio]'
											and tuoteno		= '$toimrow[tuoteno]'
											and hyllyalue   = '$myy_hyllyalue'
											and hyllynro    = '$myy_hyllynro'
											and hyllytaso   = '$myy_hyllytaso'
											and hyllyvali   = '$myy_hyllyvali'
											and sarjanumero = '$myy_era'
											and myyntirivitunnus = 0
											and ostorivitunnus > 0
											LIMIT 1";
								$lisa_res = pupe_query($query);

								if (mysql_num_rows($lisa_res) > 0) {
									$lisa_row = mysql_fetch_assoc($lisa_res);
									$oslisa = " ostorivitunnus ='$lisa_row[ostorivitunnus]', ";
								}
								else {
									$oslisa = " ostorivitunnus ='', ";
								}
							}

							$query = "	INSERT into sarjanumeroseuranta
										SET yhtio 		= '$kukarow[yhtio]',
										tuoteno			= '$toimrow[tuoteno]',
										lisatieto 		= '$lisa_row[lisatieto]',
										$tunken 		= '$toimrow[tunnus]',
										$oslisa
										kaytetty		= '$lisa_row[kaytetty]',
										era_kpl			= '',
										laatija			= '$kukarow[kuka]',
										luontiaika		= now(),
										takuu_alku 		= '$lisa_row[takuu_alku]',
										takuu_loppu		= '$lisa_row[takuu_loppu]',
										parasta_ennen	= '$lisa_row[parasta_ennen]',
										hyllyalue   	= '$myy_hyllyalue',
										hyllynro    	= '$myy_hyllynro',
										hyllytaso   	= '$myy_hyllytaso',
										hyllyvali   	= '$myy_hyllyvali',
										sarjanumero 	= '$myy_era'";
							$lisa_res = pupe_query($query);

							$query = "	UPDATE tilausrivi
										SET hyllyalue   = '$myy_hyllyalue',
										hyllynro    	= '$myy_hyllynro',
										hyllytaso   	= '$myy_hyllytaso',
										hyllyvali   	= '$myy_hyllyvali'
										WHERE yhtio 	= '$kukarow[yhtio]'
										and tunnus		= '$toimrow[tunnus]'";
							$lisa_res = pupe_query($query);
						}
					}

					if ($eratsekkpl != 0) {
						$query = "	SELECT count(*) kpl
									FROM sarjanumeroseuranta
									WHERE yhtio = '$kukarow[yhtio]'
									and tuoteno = '$toimrow[tuoteno]'
									and $tunken = '$toimrow[tunnus]'";
						$sarjares2 = pupe_query($query);
						$sarjarow = mysql_fetch_assoc($sarjares2);

						if ($sarjarow["kpl"] != 1) {
							echo "<font class='error'>".t("Er‰numeroseurannassa oleville tuotteille on liitett‰v‰ er‰numero ennen ker‰yst‰")."! ".t("Tuote").": $toimrow[tuoteno].</font><br>";
							$keraysvirhe++;
						}
					}
				}
			}
		}

		// Tarkistetaan onko syˆtetty pakkauskirjaimet
		if ($yhtiorow['kerayserat'] == 'P' or $yhtiorow['kerayserat'] == 'A') {

			$ok_chk = true;

			// jos ker‰yser‰t on A, eli asiakkaan takan pit‰‰ olla ker‰yser‰t p‰‰ll‰, tarkistetaan se ensiksi
			if ($yhtiorow['kerayserat'] == 'A') {

				$query = "	SELECT asiakas.kerayserat
							FROM lasku
							JOIN asiakas ON (asiakas.yhtio = lasku.yhtio AND asiakas.tunnus = lasku.liitostunnus AND asiakas.kerayserat = 'A')
							WHERE lasku.yhtio = '{$kukarow['yhtio']}'
							AND lasku.tunnus IN ({$tilausnumeroita})";
				$chk_res = pupe_query($query);

				if (mysql_num_rows($chk_res) == 0) $ok_chk = false;
			}

			if ($ok_chk) {
				for ($y=0; $y < count($kerivi); $y++) {
					if (trim($keraysera_pakkaus[$kerivi[$y]]) == '') $virherivi++;
				}

				if ($virherivi != 0) {

					echo "<font class='error'>",t("HUOM: Tuotteita ei viety hyllyyn. Syˆt‰ pakkauskirjain"),"!</font><br /><br />";
					$keraysvirhe++;

					$virherivi = 0;
				}
			}
		}

		// Tarkistetaan syˆtetyt varastopaikat
		if ($toim == 'VASTAANOTA_REKLAMAATIO') {
			for ($a=0; $a < count($kerivi); $a++) {
				// varastorekla on dropdown ja vertaushylly on kannasta
				if ((trim($varastorekla[$kerivi[$a]]) == trim($vertaus_hylly[$kerivi[$a]])) and $rekla_hyllyalue[$kerivi[$a]] != '' and $rekla_hyllynro[$kerivi[$a]] != '') {
					if (kuuluukovarastoon($rekla_hyllyalue[$kerivi[$a]], $rekla_hyllynro[$kerivi[$a]], '') == 0) {
						echo "<font class='error'>".t("VIRHE: Tuotenumerolle")." ".$rivin_tuoteno[$kerivi[$a]]." ".t("annettu paikka")." ".$rekla_hyllyalue[$kerivi[$a]]."-".$rekla_hyllynro[$kerivi[$a]]."-".$rekla_hyllyvali[$kerivi[$a]]."-".$rekla_hyllytaso[$kerivi[$a]]." ".t("ei kuulu mihink‰‰n varastoon")."!</font><br>";
						$virherivi++;
					}
				}

				if ((trim($varastorekla[$kerivi[$a]]) != trim($vertaus_hylly[$kerivi[$a]])) and $rekla_hyllyalue[$kerivi[$a]] != '') {
					echo "<font class='error'>".t("VIRHE: Tuotenumerolle")." ".$rivin_tuoteno[$kerivi[$a]]." ".t("voi antaa vain yhden paikan per rivi")."</font><br>";
					$virherivi++;
				}
			}
		}

		if ($virherivi != 0 and $toim == 'VASTAANOTA_REKLAMAATIO') {
			echo "<font class='error'>". t("HUOM: Tuotteita ei viety hyllyyn. Korjaa virheet")."!</font><br><br>";
			$keraysvirhe++;
		}
	}

	if ($tee == 'P') {

		$tilausnumerot = array();
		$poikkeamat = array();

		if ((int) $keraajanro > 0) {
			$query = "	SELECT *
						from kuka
						where yhtio = '$kukarow[yhtio]'
						and keraajanro = '$keraajanro'";

		}
		else {
			$query = "	SELECT *
						from kuka
						where yhtio = '$kukarow[yhtio]'
						and kuka = '$keraajalist'";
		}

		$result = pupe_query($query);

		if (mysql_num_rows($result) == 0) {
			echo "<font class='error'>".t("VIRHE: Ker‰‰j‰‰ %s ei lˆydy", "", $keraajanro)."!</font><br><br>";
			$keraysvirhe++;
		}
		else {
			$keraaja 	= mysql_fetch_assoc($result);
			$who 		= $keraaja['kuka'];
			$keraamaton = 0;

			for ($i=0; $i < count($kerivi); $i++) {

				$query1 = "	SELECT if (kerattyaika='0000-00-00 00:00:00', 'keraamaton', 'keratty') status
							FROM tilausrivi
							WHERE tunnus = '$kerivi[$i]'
							and yhtio	 = '$kukarow[yhtio]'";
				$ktresult = pupe_query($query1);
				$statusrow = mysql_fetch_assoc($ktresult);

				if ($statusrow["status"] == "keraamaton") {
					if ($kerivi[$i] > 0) {

						$apui = $kerivi[$i];

						//Kysess‰ voi olla ker‰ysklˆntti, haetaan muuttuneen rivin otunnus
						$query1 = "	SELECT otunnus
									FROM tilausrivi
									WHERE tunnus = '$apui'
									and yhtio	 = '$kukarow[yhtio]'";
						$result  = pupe_query($query1);
						$otsikko = mysql_fetch_assoc($result);

						//Haetaan otsikon kaikki tiedot
						$query1 = "	SELECT lasku.*,
									laskun_lisatiedot.laskutus_nimi,
									laskun_lisatiedot.laskutus_nimitark,
									laskun_lisatiedot.laskutus_osoite,
									laskun_lisatiedot.laskutus_postino,
									laskun_lisatiedot.laskutus_postitp,
									laskun_lisatiedot.laskutus_maa,
									asiakas.kerayserat
									FROM lasku
									JOIN asiakas ON (asiakas.yhtio = lasku.yhtio AND asiakas.tunnus = lasku.liitostunnus)
									LEFT JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio = lasku.yhtio and laskun_lisatiedot.otunnus = lasku.tunnus)
									WHERE lasku.tunnus = '$otsikko[otunnus]'
									and lasku.yhtio = '$kukarow[yhtio]'";
						$result = pupe_query($query1);
						$otsikkorivi = mysql_fetch_assoc($result);

						//Haetaan tilausrivin kaikki tiedot
						$query1 = "	SELECT *
									FROM tilausrivi
									WHERE tunnus = '$apui'
									and yhtio	 = '$kukarow[yhtio]'";
						$result = pupe_query($query1);
						$tilrivirow = mysql_fetch_assoc($result);

						//Aloitellaan tilausrivi p‰ivitysquery‰
						$query = "	UPDATE tilausrivi
									SET yhtio = yhtio ";

						if ($tilrivirow["var"] != "J" and $keraysvirhe == 0 and $real_submit == 'yes') {
							//Muut kuin JT-rivit p‰ivitet‰‰n aina ker‰tyiksi jos virhetsekit meniv‰t ok
							$query .= ", keratty = '$who',
										 kerattyaika = now()";
						}

						// K‰ytt‰j‰ on syˆtt‰nyt jonkun luvun, p‰ivitet‰‰n vaikka virhetsekit menisiv‰t pepulleen
						if (trim($maara[$apui]) != '') {

							// Siivotaan hieman k‰ytt‰j‰n syˆtt‰m‰‰ kappalem‰‰r‰‰
							$maara[$apui] = str_replace (",", ".", $maara[$apui]);
							$maara[$apui] = (float) $maara[$apui];

							// Ker‰t‰‰n tietoa poikkeama-maileja varten
							$poikkeamat[$tilrivirow["otunnus"]][$i]["tuoteno"] 	= $tilrivirow["tuoteno"];
							$poikkeamat[$tilrivirow["otunnus"]][$i]["nimitys"] 	= $tilrivirow["nimitys"];
							$poikkeamat[$tilrivirow["otunnus"]][$i]["tilkpl"] 	= $tilrivirow["tilkpl"];
							$poikkeamat[$tilrivirow["otunnus"]][$i]["var"] 		= $tilrivirow["var"];
							$poikkeamat[$tilrivirow["otunnus"]][$i]["maara"] 	= $maara[$apui];

							$rotunnus = 0;

							if ($tilrivirow["var"] == 'P' and $maara[$apui] > 0) {

								// Puuterivi lˆytyi poistetaan VAR
								$query .= "	, var		= ''
											, varattu	= '".$maara[$apui]."'";

								//Poistetaan 'tuote loppu'-kommentti jos tuotetta sittenkin lˆytyi
								$puurivires = t_avainsana("PUUTEKOMM");

								if (mysql_num_rows($puurivires) > 0) {
									$puurivirow = mysql_fetch_assoc($puurivires);

									$korvataan_pois = $puurivirow["selite"];
								}
								else {
									$korvataan_pois = t("Tuote Loppu.");
								}

								$query .= "	, kommentti	= replace(kommentti, '$korvataan_pois', '') ";

								// PUUTE-riville tehd‰‰n osatoimitus ja loput j‰tet‰‰n puuteriviksi
								if ($maara[$apui] < $tilrivirow['tilkpl']) {

									$poikkeamat[$tilrivirow["otunnus"]][$i]["loput"] = "J‰tettiin puuteriviksi.";

									$rotunnus	= $tilrivirow['otunnus'];
									$rtyyppi	= $tilrivirow['tyyppi'];
									$rtilkpl 	= round($tilrivirow['tilkpl']-$maara[$apui],2);
									$rvarattu	= 0;
									$rjt  		= 0;
									$rvar		= $tilrivirow['var'];
									$keratty	= "'$who'";
									$kerattyaik	= "now()";
									$rkomm 		= $tilrivirow["kommentti"];
								}
							}
							elseif ($tilrivirow["var"] == 'J' and $maara[$apui] > 0) {
								// JT-rivi lˆytyi, poistetaan VAR ja merkataan rivi ker‰tyksi, jos virhetsekit ok
								if ($keraysvirhe == 0) {
									$query .= ", keratty = '$who',
												 kerattyaika = now()";
								}

								$query .= "	, var			= ''
											, jt			= 0
											, varattu		= '".$maara[$apui]."'";

								if ($yhtiorow["varaako_jt_saldoa"] == "") {
									$jtsek = $tilrivirow['jt'];
								}
								else {
									$jtsek = $tilrivirow['jt']+$tilrivirow['varattu'];
								}

								// JT-riville tehd‰‰n osatoimitus ja loput j‰tet‰‰n j‰lkitoimitukseen
								if ($maara[$apui] < $jtsek) {

									$poikkeamat[$tilrivirow["otunnus"]][$i]["loput"] = "J‰tettiin JT-riviksi.";

									$rotunnus		= $tilrivirow['otunnus'];
									$rtyyppi		= $tilrivirow['tyyppi'];
									$rtilkpl 		= round($jtsek-$maara[$apui],2);

									if ($yhtiorow["varaako_jt_saldoa"] == "") {
										$rvarattu	= 0;
										$rjt  		= round($jtsek-$maara[$apui],2);
									}
									else {
										$rvarattu	= round($jtsek-$maara[$apui],2);
										$rjt  		= 0;
									}

									$rvar			= $tilrivirow['var'];
									$keratty		= "''";
									$kerattyaik		= "''";
									$rkomm 			= $tilrivirow["kommentti"];
								}
							}
							elseif (($tilrivirow["var"] == 'J' or $tilrivirow["var"] == 'P') and $maara[$apui] == 0 and $poikkeama_kasittely[$apui] == "MI") {

								$poikkeamat[$tilrivirow["otunnus"]][$i]["loput"] = "JT/Puuterivi nollattiin.";

								// Varastomiehell‰ on nyt oikeus nollata myˆs JT-rivi jos h‰n saa l‰hetetty‰ $poikkeama_kasittely[$apui] == "MI"
								if ($keraysvirhe == 0) {
									$query .= ", keratty = '$who',
												 kerattyaika = now()";
								}

								$query .= "	, var			= ''
											, jt			= 0
											, varattu		= 0";
							}
							elseif ((!isset($poikkeama_kasittely[$apui]) or $poikkeama_kasittely[$apui] == "") and $maara[$apui] >= 0 and $maara[$apui] < $tilrivirow['varattu'] and ($otsikkorivi['clearing'] == 'ENNAKKOTILAUS' or $otsikkorivi['clearing'] == 'JT-TILAUS')) {
								// Jos t‰m‰ on toimitettava ennakkotilaus tai jt-tilaus niin yritet‰‰n laittaa poikkeama jollekin sopivalle otsikolle
								// T‰h‰n haaraan ei menn‰ jos poikkeamat ohjataan manuaalisesti

								$query .= ", varattu='".$maara[$apui]."'";

								if ($otsikkorivi['clearing'] == 'ENNAKKOTILAUS') {

									$poikkeamat[$tilrivirow["otunnus"]][$i]["loput"] = "Siirrettiin takaisin ennakkotilaukselle.";

									$ejttila    = "((tila='E' and alatila='A') or (tila='D' and alatila='E'))";

									$rotunnus	= 0;
									$rtyyppi	= "E";
									$rtilkpl 	= round($tilrivirow['varattu']-$maara[$apui],2);
									$rvarattu	= round($tilrivirow['varattu']-$maara[$apui],2);
									$rjt  		= 0;
									$rvar		= "";
									$keratty	= "''";
									$kerattyaik	= "''";
									$rkomm 		= $tilrivirow["kommentti"];
								}

								if ($otsikkorivi['clearing'] == 'JT-TILAUS') {

									$poikkeamat[$tilrivirow["otunnus"]][$i]["loput"] = "Siirrettiin takaisin JT-tilaukselle.";

									$ejttila    = "(lasku.tila != 'N' or lasku.alatila != '')";

									$rotunnus	= 0;
									$rtyyppi	= "L";
									$rtilkpl 	= round($tilrivirow['varattu']-$maara[$apui],2);

									if ($yhtiorow["varaako_jt_saldoa"] == "") {
										$rvarattu	= 0;
										$rjt  		= round($tilrivirow['varattu']-$maara[$apui],2);
									}
									else {
										$rvarattu	= round($tilrivirow['varattu']-$maara[$apui],2);
										$rjt  		= 0;
									}

									$rvar		= "J";
									$keratty	= "''";
									$kerattyaik	= "''";
									$rkomm 		= $tilrivirow["kommentti"];
								}

								// Etsit‰‰n sopiva otsikko jolle rivi laitetaan
								// Samat ehdot kuin tee_jt_tilaus.inc:ss‰ rivill‰ ~180
								$query1 = "	SELECT lasku.yhtio, lasku.tunnus, lasku.tila, lasku.alatila
											FROM lasku
											LEFT JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio = lasku.yhtio and laskun_lisatiedot.otunnus = lasku.tunnus)
											WHERE $ejttila
											and lasku.yhtio			= '$otsikkorivi[yhtio]'
											and lasku.ytunnus		= '$otsikkorivi[ytunnus]'
											and lasku.nimi 			= '$otsikkorivi[nimi]'
											and lasku.nimitark 		= '$otsikkorivi[nimitark]'
											and lasku.osoite 		= '$otsikkorivi[osoite]'
											and lasku.postino		= '$otsikkorivi[postino]'
											and lasku.postitp 		= '$otsikkorivi[postitp]'
											and lasku.toim_nimi		= '$otsikkorivi[toim_nimi]'
											and lasku.toim_nimitark	= '$otsikkorivi[toim_nimitark]'
											and lasku.toim_osoite 	= '$otsikkorivi[toim_osoite]'
											and lasku.toim_postino 	= '$otsikkorivi[toim_postino]'
											and lasku.toim_postitp 	= '$otsikkorivi[toim_postitp]'
											and lasku.toimitustapa 	= '$otsikkorivi[toimitustapa]'
											and lasku.maksuehto 	= '$otsikkorivi[maksuehto]'
											and lasku.vienti	 	= '$otsikkorivi[vienti]'
											and lasku.alv		 	= '$otsikkorivi[alv]'
											and lasku.ketjutus 		= '$otsikkorivi[ketjutus]'
											and lasku.kohdistettu	= '$otsikkorivi[kohdistettu]'
											and lasku.toimitusehto	= '$otsikkorivi[toimitusehto]'
											and lasku.valkoodi 		= '$otsikkorivi[valkoodi]'
											and lasku.vienti_kurssi	= '$otsikkorivi[vienti_kurssi]'
											and lasku.erikoisale	= '$otsikkorivi[erikoisale]'
											and lasku.eilahetetta	= '$otsikkorivi[suoraan_laskutukseen]'
											and lasku.piiri			= '$otsikkorivi[piiri]'
											and laskun_lisatiedot.laskutus_nimi 	= '$otsikkorivi[laskutus_nimi]'
											and laskun_lisatiedot.laskutus_nimitark = '$otsikkorivi[laskutus_nimitark]'
											and laskun_lisatiedot.laskutus_osoite 	= '$otsikkorivi[laskutus_osoite]'
											and laskun_lisatiedot.laskutus_postino 	= '$otsikkorivi[laskutus_postino]'
											and laskun_lisatiedot.laskutus_postitp 	= '$otsikkorivi[laskutus_postitp]'
											and laskun_lisatiedot.laskutus_maa 		= '$otsikkorivi[laskutus_maa]'
											ORDER BY tunnus desc
											LIMIT 1";
								$stresult = pupe_query($query1);

								// Sopiva otsikko lˆytyi
								if (mysql_num_rows($stresult) > 0) {
									$strow = mysql_fetch_assoc($stresult);

									// Sopivin otsikko oli dellattu, elvytet‰‰n se!
									if ($otsikkorivi['clearing'] == 'ENNAKKOTILAUS' and $strow["tila"] == "D") {

										// E, A - Ennakkotilaus lep‰‰m‰ss‰
										$ukysx  = "UPDATE lasku SET tila = 'E', alatila = 'A', comments = '' WHERE yhtio = '$strow[yhtio]' and tunnus = '$strow[tunnus]'";
										$ukysxres  = pupe_query($ukysx);
									}
									elseif ($otsikkorivi['clearing'] == 'JT-TILAUS' and $strow["tila"] == "D") {

										// N, T - Myyntitilaus odottaa JT-tuotteita
										$ukysx  = "UPDATE lasku SET tila = 'N', alatila = 'T', comments = '' WHERE yhtio = '$strow[yhtio]' and tunnus = '$strow[tunnus]'";
										$ukysxres  = pupe_query($ukysx);
									}

									$rotunnus = $strow["tunnus"];
								}
								else {
									// Laitetaan t‰lle otsikolle, voi menn‰ solmuun, mutta ei katoa ainakaan kokonaan
									$rotunnus = $tilrivirow['otunnus'];
								}
							}
							elseif ($tilrivirow["var"] != 'J' and $tilrivirow["var"] != 'P') {
								// Jos t‰m‰ on normaali rivi
								if ($maara[$apui] < 0) {
									// Jos ker‰‰j‰ kuittaa alle nollan niin ei tehd‰ mit‰‰n
									$query .= ", varattu = varattu";

									$poikkeamat[$tilrivirow["otunnus"]][$i]["loput"] = "M‰‰r‰ nollaa pienempi. Poikkeamaa ei hyv‰ksytty.";
								}
								elseif ($maara[$apui] >= 0 and $maara[$apui] < $tilrivirow['varattu']) {

									$query .= ", varattu = '".$maara[$apui]."'";

									if (isset($poikkeama_kasittely[$apui]) and $poikkeama_kasittely[$apui] != "") {

										if ($maara[$apui] == 0) {
											// Mit‰tˆid‰‰n nollarivi koska poikkeamalle kuitenkin tehd‰‰n jotain fiksua
											$query .= ", tyyppi = 'D', kommentti=trim(concat(kommentti, ' Mit‰tˆitiin koska ker‰yspoikkeamasta tehtiin: ".$poikkeama_kasittely[$apui]."'))";
										}

										$rotunnus	= $tilrivirow['otunnus'];
										$rtyyppi	= $tilrivirow['tyyppi'];
										$rtilkpl 	= round($tilrivirow['varattu']-$maara[$apui],2);
										$rvarattu	= round($tilrivirow['varattu']-$maara[$apui],2);
										$rjt  		= 0;
										$rvar		= $tilrivirow['var'];
										$keratty	= "''";
										$kerattyaik	= "''";
										$rkomm 		= $tilrivirow['kommentti'];
									}
								}
								else {
									//P‰ivitet‰‰n vain m‰‰r‰ jos se on isompi kuin alkuper‰inen varattum‰‰r‰
									$query .= ", varattu = '".$maara[$apui]."'";
								}
							}

							if (isset($poikkeama_kasittely[$apui]) and $poikkeama_kasittely[$apui] != "" and $rotunnus != 0) {
								// K‰ytt‰j‰n valitsemia poikkeamak‰sittelys‰‰ntˆj‰
								if ($poikkeama_kasittely[$apui] == "PU") {

									$poikkeamat[$tilrivirow["otunnus"]][$i]["loput"] = "J‰tettiin puuteriviksi.";

									// Riville tehd‰‰n osatoimitus ja loput j‰tet‰‰n puuteriviksi
									$rvarattu	= 0;
									$rjt  		= 0;
									$rvar		= "P";
									$keratty	= "'$who'";
									$kerattyaik	= "now()";

									$puurivires = t_avainsana("PUUTEKOMM");

									if (mysql_num_rows($puurivires) > 0) {
										$puurivirow = mysql_fetch_assoc($puurivires);

										// Tilausrivin systeemikommentti
										$rkomm = $puurivirow["selite"];
									}
									else {
										// Tilausrivin systeemikommentti
										$rkomm = t("Tuote Loppu.");
									}
								}
								elseif ($poikkeama_kasittely[$apui] == "JT") {

									$poikkeamat[$tilrivirow["otunnus"]][$i]["loput"] = "J‰tettiin JT-riviksi.";

									// Riville tehd‰‰n osatoimitus ja loput j‰tet‰‰n j‰lkk‰riin
									if ($yhtiorow["varaako_jt_saldoa"] == "") {
										$rvarattu	= 0;
										$rjt  		= $rtilkpl;
									}
									else {
										$rvarattu	= $rtilkpl;
										$rjt  		= 0;
									}

									$rvar		= "J";
									$keratty	= "''";
									$kerattyaik	= "''";
									$rkomm 		= $tilrivirow["kommentti"];
								}
								elseif ($poikkeama_kasittely[$apui] == "MI") {

									$poikkeamat[$tilrivirow["otunnus"]][$i]["loput"] = "Mit‰tˆitiin.";

									// Riville tehd‰‰n osatoimitus ja loput mit‰tˆid‰‰n
									$rotunnus	= 0;
								}
								elseif ($poikkeama_kasittely[$apui] == "UR") {

									$poikkeamat[$tilrivirow["otunnus"]][$i]["loput"] = "Siirrettiin uudelle riville.";

									// Riville tehd‰‰n osatoimitus ja loput kopsataan uudelle riville
									$rvarattu	= $rtilkpl;
									$rjt  		= 0;
									$rvar		= "";
									$keratty	= "''";
									$kerattyaik	= "''";
									$rkomm 		= $tilrivirow["kommentti"];
								}
								elseif ($poikkeama_kasittely[$apui] == "UT") {
									// Riville tehd‰‰n osatoimitus ja loput siirret‰‰n ihan uudelle tilaukselle
									if (!isset($tilausnumerot[$tilrivirow["otunnus"]])) {

										// Jotta saadaa lasku kopsattua kivasti jos se splittaantuu
										$laspliq = "SELECT *
													FROM lasku
													WHERE yhtio = '$kukarow[yhtio]'
													and tunnus = '$tilrivirow[otunnus]'";
										$laskusplitres = pupe_query($laspliq);
										$laskusplitrow = mysql_fetch_assoc($laskusplitres);

										if ($laskusplitrow["tunnusnippu"] == 0) {
											// Laitetaan uusi tilaus osatoimitukseksi alkuper‰iselle tilaukselle
											$kysely  = "UPDATE lasku SET tunnusnippu=tunnus WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tilrivirow[otunnus]'";
											$insres  = pupe_query($kysely);

											$laskusplitrow["tunnusnippu"] = $tilrivirow["otunnus"];
										}

										$fields = "yhtio";
										$values = "'$kukarow[yhtio]'";

										// Ei monisteta tunnusta
										for ($islpit=1; $islpit < mysql_num_fields($laskusplitres)-1; $islpit++) {

											$fieldname = mysql_field_name($laskusplitres, $islpit);

											$fields .= ", ".$fieldname;

											switch ($fieldname) {
												case 'vanhatunnus':
													$values .= ", '$tilrivirow[otunnus]'";
													break;
												case 'laatija':
													$values .= ", '$kukarow[kuka]'";
													break;
												case 'luontiaika':
													$values .= ", now()";
													break;
												case 'alatila':
													$values .= ", ''";
													break;
												case 'tila':
													$values .= ", 'N'";
													break;
												default:
													$values .= ", '".$laskusplitrow[$fieldname]."'";
											}
										}

										$kysely  = "INSERT INTO lasku ($fields) VALUES ($values)";
										$insres  = pupe_query($kysely);
										$tilausnumerot[$tilrivirow["otunnus"]] = mysql_insert_id();

										$kysely2 = "	SELECT laskutus_nimi, laskutus_nimitark, laskutus_osoite, laskutus_postino, laskutus_postitp, laskutus_maa, laatija, luontiaika, otunnus
														FROM laskun_lisatiedot
														WHERE yhtio = '$kukarow[yhtio]'
														AND otunnus = '$tilrivirow[otunnus]'";
										$lisatiedot_result = pupe_query($kysely2);
										$lisatiedot_row = mysql_fetch_assoc($lisatiedot_result);

										$fields = "yhtio";
										$values = "'$kukarow[yhtio]'";

										// Ei monisteta tunnusta
										for ($ijk = 0; $ijk < mysql_num_fields($lisatiedot_result); $ijk++) {

											$fieldname = mysql_field_name($lisatiedot_result, $ijk);

											$fields .= ", ".$fieldname;

											switch ($fieldname) {
												case 'otunnus':
													$values .= ", '".$tilausnumerot[$tilrivirow["otunnus"]]."'";
													break;
												case 'laatija':
													$values .= ", '$kukarow[kuka]'";
													break;
												case 'luontiaika':
													$values .= ", now()";
													break;
												default:
													$values .= ", '".$lisatiedot_row[$fieldname]."'";
											}
										}

										$kysely2  = "INSERT INTO laskun_lisatiedot ($fields) VALUES ($values)";
										$insres  = pupe_query($kysely2);
									}

									$poikkeamat[$tilrivirow["otunnus"]][$i]["loput"] = "Siirrettiin tilaukselle ".$tilausnumerot[$tilrivirow["otunnus"]].".";

									$rotunnus	= $tilausnumerot[$tilrivirow["otunnus"]];
									$rvarattu	= $rtilkpl;
									$rjt  		= 0;
									$rvar		= "";
									$keratty	= "''";
									$kerattyaik	= "''";
									$rkomm 		= $tilrivirow["kommentti"];
								}
							}

							// T‰ss‰ tehd‰‰n uusi rivi
							if ($rotunnus != 0) {

								// Aina jos rivi splitataan niin p‰ivitet‰‰n alkuper‰isen rivin tilkpl
								$query .= ", tilkpl = '".$maara[$apui]."'";

								$ale_query_insert_lisa = '';

								for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
									$ale_query_insert_lisa .= " ale{$alepostfix} = '".$tilrivirow["ale{$alepostfix}"]."', ";
								}

								$querys = "	INSERT into tilausrivi set
											hyllyalue   = '$tilrivirow[hyllyalue]',
											hyllynro    = '$tilrivirow[hyllynro]',
											hyllytaso   = '$tilrivirow[hyllyvali]',
											hyllyvali   = '$tilrivirow[hyllytaso]',
											tilaajanrivinro = '$tilrivirow[tilaajanrivinro]',
											laatija 	= '$kukarow[kuka]',
											laadittu 	= now(),
											yhtio 		= '$kukarow[yhtio]',
											tuoteno 	= '$tilrivirow[tuoteno]',
											varattu 	= '$rvarattu',
											yksikko 	= '$tilrivirow[yksikko]',
											kpl 		= '0',
											tilkpl 		= '$rtilkpl',
											jt	 		= '$rjt',
											{$ale_query_insert_lisa}
											erikoisale	= '{$tilrivirow['erikoisale']}',
											alv 		= '$tilrivirow[alv]',
											netto		= '$tilrivirow[netto]',
											hinta 		= '$tilrivirow[hinta]',
											kerayspvm 	= '$tilrivirow[kerayspvm]',
											otunnus 	= '$rotunnus',
											tyyppi 		= '$rtyyppi',
											toimaika 	= '$tilrivirow[toimaika]',
											kommentti 	= '$rkomm',
											var 		= '$rvar',
											try			= '$tilrivirow[try]',
											osasto		= '$tilrivirow[osasto]',
											perheid		= '$tilrivirow[perheid]',
											perheid2	= '$tilrivirow[perheid2]',
											nimitys 	= '$tilrivirow[nimitys]',
											jaksotettu	= '$tilrivirow[jaksotettu]'";
								$riviresult = pupe_query($querys);
								$lisatty_tun = mysql_insert_id();

								//Kopioidaan tilausrivin lisatiedot
								$querys = "	SELECT *
											FROM tilausrivin_lisatiedot
											WHERE tilausrivitunnus='$tilrivirow[tunnus]'
											and yhtio ='$kukarow[yhtio]'";
								$monistares2 = pupe_query($querys);

								if (mysql_num_rows($monistares2) > 0) {
									$monistarow2 = mysql_fetch_array($monistares2);

									$querys = "	INSERT INTO tilausrivin_lisatiedot
												SET yhtio				= '$kukarow[yhtio]',
												positio 				= '$monistarow2[positio]',
												tilausrivilinkki		= '$monistarow2[tilausrivilinkki]',
												toimittajan_tunnus		= '$monistarow2[toimittajan_tunnus]',
												ei_nayteta 				= '$monistarow2[ei_nayteta]',
												tilausrivitunnus		= '$lisatty_tun',
												erikoistoimitus_myynti 	= '$monistarow2[erikoistoimitus_myynti]',
												vanha_otunnus			= '$monistarow2[vanha_otunnus]',
												jarjestys				= '$monistarow2[jarjestys]',
												luontiaika				= now(),
												laatija 				= '$kukarow[kuka]'";
									$riviresult = pupe_query($querys);
								}

								$queryera = "SELECT sarjanumeroseuranta FROM tuote WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$tilrivirow[tuoteno]'";
								$sarjares2 = pupe_query($queryera);
								$erarow = mysql_fetch_assoc($sarjares2);

								if ($erarow['sarjanumeroseuranta'] == 'E' or $erarow['sarjanumeroseuranta'] == 'F') {
									echo "<font class='error'>".t("Er‰numeroseurannassa oleville tuotteille on liitett‰v‰ er‰numero ennen ker‰yst‰")."! ".t("Tuote").": $tilrivirow[tuoteno].</font><br>";
								}
							}

							//p‰ivitet‰‰n tuoteperheiden saldottomat j‰senet oikeisiin m‰‰riin (ne voi olla alkuper‰isell‰kin l‰hetteell‰ == vanhatunnus)
							if ($tilrivirow["perheid"] != 0) {
								$query1 = "	SELECT tilausrivi.tunnus, tilausrivi.tuoteno
											FROM tilausrivi, tuote
											WHERE tilausrivi.otunnus in ('$tilrivirow[otunnus]', '$otsikkorivi[vanhatunnus]')
											and tilausrivi.tunnus	!= '$apui'
											and tilausrivi.perheid	 = '$tilrivirow[perheid]'
											and tilausrivi.yhtio	 = '$kukarow[yhtio]'
											and tuote.yhtio			 = tilausrivi.yhtio
											and tuote.tuoteno	 	 = tilausrivi.tuoteno
											and tuote.ei_saldoa		!= ''";
								$result = pupe_query($query1);

								while ($tilrivirow2 = mysql_fetch_assoc($result)) {
									$query2 = "	SELECT kerroin
												FROM tuoteperhe
												WHERE yhtio = '$kukarow[yhtio]' AND
												isatuoteno = '$tilrivirow[tuoteno]' AND
												tuoteno = '$tilrivirow2[tuoteno]'";
									$result2 = pupe_query($query2);

									// oltiin muokkaamassa is‰tuotteen kappalem‰‰r‰‰, p‰ivitet‰‰n saldottomien lasten m‰‰r‰t kertoimella
									if (mysql_num_rows($result2) == 1) {
										$kerroinrow = mysql_fetch_assoc($result2);
										if ($kerroinrow["kerroin"] == 0) $kerroinrow["kerroin"] = 1;
										$tilrivimaara = round($maara[$apui] * $kerroinrow["kerroin"], 2);

										$query1 = "	UPDATE tilausrivi
													SET varattu = '$tilrivimaara'
													WHERE tunnus = '$tilrivirow2[tunnus]' AND
													yhtio = '$kukarow[yhtio]'";
										$result1 = pupe_query($query1);
									}
								}
							}

							$muuttuiko = 'kylsemuuttu';
						}

						if ($keraysvirhe == 0 and ($yhtiorow['kerayserat'] == 'P' or ($yhtiorow['kerayserat'] == 'A' and $otsikkorivi['kerayserat'] == 'A'))) {

							$kerattylisa = (trim($maara[$apui]) == '' or $maara[$apui] < 0) ? ", kpl_keratty = kpl" : ", kpl_keratty = '{$maara[$apui]}'";

							$pakkauskirjain = (int) abs(ord($keraysera_pakkaus[$kerivi[$i]]) - 64);

							$query_ins = "	UPDATE kerayserat SET
											pakkausnro = '{$pakkauskirjain}'
											{$kerattylisa}
											WHERE yhtio = '{$kukarow['yhtio']}'
											AND tilausrivi = '{$kerivi[$i]}'";
							$keraysera_ins_res = pupe_query($query_ins);
						}

						if ($toim == 'VASTAANOTA_REKLAMAATIO' and $keraysvirhe == 0) {

							if (trim($varastorekla[$apui]) != '' and trim($vertaus_hylly[$apui]) != trim($varastorekla[$apui])) {
								// Ollaan valittu varastopaikka dropdownista
								list($rekla_hyllyalue, $rekla_hyllynro, $rekla_hyllyvali, $rekla_hyllytaso) = explode("###", $varastorekla[$apui]);
							}
							elseif (trim($vertaus_hylly[$apui]) == trim($varastorekla[$apui]) and $rekla_hyllyalue[$apui] != '') {
								// Ollaan syˆtetty varastopaikka k‰sin
								$rekla_hyllyalue = $rekla_hyllyalue[$apui];
								$rekla_hyllynro  = $rekla_hyllynro[$apui];
								$rekla_hyllyvali = $rekla_hyllyvali[$apui];
								$rekla_hyllytaso = $rekla_hyllytaso[$apui];
							}
							else {
								// Otetaan tuotteen oletuspaikka
								list($rekla_hyllyalue, $rekla_hyllynro, $rekla_hyllyvali, $rekla_hyllytaso) = explode("###", $vertaus_hylly[$apui]);
							}

							// Lis‰t‰‰n paikat tilausriville
							$query .= ", hyllyalue = '$rekla_hyllyalue', hyllynro = '$rekla_hyllynro', hyllyvali = '$rekla_hyllyvali', hyllytaso = '$rekla_hyllytaso'";
						}

						//p‰ivitet‰‰n alkuper‰inen rivi
						$query .= " WHERE tunnus='$apui' and yhtio='$kukarow[yhtio]'";
						$result = pupe_query($query);

						// jos ker‰yser‰t on k‰ytˆss‰, p‰ivitet‰‰n ker‰tyt kappalem‰‰r‰t ker‰yser‰‰n
						if ($yhtiorow['kerayserat'] == 'K' and $toim == "") {
							$query_ker = "	SELECT tunnus
											FROM kerayserat
											WHERE yhtio 	= '{$kukarow['yhtio']}'
											AND nro 		= '$id'
											AND tilausrivi 	= '{$apui}'";
							$keraysera_chk_res = pupe_query($query_ker);

							while ($keraysera_chk_row = mysql_fetch_assoc($keraysera_chk_res)) {
								if (!is_numeric(trim($keraysera_maara[$keraysera_chk_row['tunnus']]))) {
									$keraysera_maara[$keraysera_chk_row['tunnus']] = 0;
								}

								// P‰ivitet‰‰n ker‰‰j‰ ja aika vain jos niit‰ ei olla jo aikaisemmin laitettu (optiscan tai kardex...)
								$query_upd = "	UPDATE kerayserat
												SET kpl_keratty = '{$keraysera_maara[$keraysera_chk_row['tunnus']]}',
												keratty 		= if(keratty = '', '{$kukarow['kuka']}', keratty),
												kerattyaika 	= if(kerattyaika = '0000-00-00 00:00:00', now(), kerattyaika)
												WHERE yhtio = '{$kukarow['yhtio']}'
												AND tunnus 	= '{$keraysera_chk_row['tunnus']}'";
								$keraysera_update_res = pupe_query($query_upd);
							}

							// p‰ivitet‰‰n tuoteperhen lapset ker‰tyiksi jos niill‰ on ohita_ker‰ys t‰pp‰ p‰‰ll‰
							$query_chk = "	SELECT perheid, tuoteno, varattu, otunnus
											FROM tilausrivi
											WHERE yhtio = '{$kukarow['yhtio']}'
											AND tunnus  = '{$apui}'
											AND perheid = '{$apui}'";
							$perheid_chk_res = pupe_query($query_chk);

							if (mysql_num_rows($perheid_chk_res) > 0) {

								$perheid_chk_row = mysql_fetch_assoc($perheid_chk_res);

								// haetaan lapset, ei oteta is‰‰ huomioon
								$query_lapset = "	SELECT tunnus, tuoteno, varattu
													FROM tilausrivi
													WHERE yhtio = '{$kukarow['yhtio']}'
													AND otunnus = '{$perheid_chk_row['otunnus']}'
													AND perheid = '{$perheid_chk_row['perheid']}'
													AND tunnus != '{$apui}'";
								$lapset_chk_res = pupe_query($query_lapset);

								while ($lapset_chk_row = mysql_fetch_assoc($lapset_chk_res)) {

									$query_ohita = "	SELECT ohita_kerays, kerroin
														FROM tuoteperhe
														WHERE yhtio 	= '{$kukarow['yhtio']}'
														AND tyyppi 		= 'P'
														AND isatuoteno 	= '{$perheid_chk_row['tuoteno']}'
														AND tuoteno 	= '{$lapset_chk_row['tuoteno']}'";
									$ohita_chk_res = pupe_query($query_ohita);
									$ohita_chk_row = mysql_fetch_assoc($ohita_chk_res);

									// Pit‰‰kˆ lapsen ker‰ttym‰‰r‰‰ muuttaa? (lapsi tulee myˆs $kerivi[] arrayssa, joten se merkataan joka tapauksessa ker‰tyksi, mutta muutetaan t‰ss‰ m‰‰r‰ jos on tarvis)
									if ($ohita_chk_row['ohita_kerays'] != '' and round($lapset_chk_row["varattu"], 2) != round($perheid_chk_row["varattu"] * $ohita_chk_row["kerroin"], 2)) {
										$maara[$lapset_chk_row['tunnus']] = round($perheid_chk_row["varattu"] * $ohita_chk_row["kerroin"], 2);
									}
								}
							}
						}

						// Pit‰‰ lis‰t‰ p‰ivityksen yhteydess‰ myˆs tuotepaikka...
						if ($toim == 'VASTAANOTA_REKLAMAATIO' and $keraysvirhe == 0) {

							$select = "	SELECT *
										FROM tuotepaikat
										WHERE yhtio 	= '$kukarow[yhtio]'
										AND hyllyalue 	= '$rekla_hyllyalue'
										AND hyllynro 	= '$rekla_hyllynro'
										AND hyllyvali 	= '$rekla_hyllyvali'
										AND hyllytaso 	= '$rekla_hyllytaso'
										AND tuoteno 	= '{$rivin_puhdas_tuoteno[$apui]}'";
							$hakures = pupe_query($select);
							$sresults = mysql_fetch_assoc($hakures);

							if (mysql_num_rows($hakures) == 0) {
								// lis‰t‰‰n tuotteelle tapahtuma
								$select = "	INSERT into tuotepaikat set
											yhtio 		= '$yhtiorow[yhtio]',
											tuoteno 	= '{$rivin_puhdas_tuoteno[$apui]}',
											hyllyalue	= '$rekla_hyllyalue',
											hyllynro	= '$rekla_hyllynro',
											hyllyvali	= '$rekla_hyllyvali',
											hyllytaso	= '$rekla_hyllytaso',
											laatija 	= '$kukarow[kuka]',
											luontiaika 	= now(),
											muutospvm 	= now(),
											muuttaja	= '$kukarow[kuka]' ";
								$result = pupe_query($select);

								// tehd‰‰n tapahtuma
								$select = "	INSERT into tapahtuma set
											yhtio 		= '$kukarow[yhtio]',
											tuoteno 	= '{$rivin_puhdas_tuoteno[$apui]}',
											kpl 		= '0',
											kplhinta	= '0',
											hinta 		= '0',
											laji 		= 'uusipaikka',
											hyllyalue	= '$rekla_hyllyalue',
											hyllynro	= '$rekla_hyllynro',
											hyllyvali	= '$rekla_hyllyvali',
											hyllytaso	= '$rekla_hyllytaso',
											selite 		= '".t("Lis‰ttiin tuotepaikka")." $rekla_hyllyalue $rekla_hyllynro $rekla_hyllyvali $rekla_hyllytaso',
											laatija 	= '$kukarow[kuka]',
											laadittu 	= now()";
								$result = pupe_query($select);
							}
						}
					}

					//Ker‰‰m‰tˆn rivi
					$keraamaton++;
				}
				else {
					echo t("HUOM: T‰m‰ rivi oli jo ker‰tty! Ei voida ker‰t‰ uudestaan.")."<br>";
				}
			}
		}

		if ($keraysvirhe > 0) {
			$tee = '';
		}

		// Jos ker‰yspoikkeamia syntyi, niin l‰hetet‰‰n mailit myyj‰lle ja asiakkaalle
		if ($muuttuiko == 'kylsemuuttu') {
			foreach ($poikkeamat as $poikkeamatilaus => $poikkeamatilausrivit) {

				$query = "	SELECT lasku.*, asiakas.email, asiakas.kerayspoikkeama, asiakas.keraysvahvistus_lahetys, kuka.nimi kukanimi, kuka.eposti as kukamail, asiakas.kieli
							FROM lasku
							JOIN asiakas on asiakas.yhtio=lasku.yhtio and asiakas.tunnus=lasku.liitostunnus
							LEFT JOIN kuka on kuka.yhtio=lasku.yhtio and kuka.tunnus=lasku.myyja
							WHERE lasku.tunnus	= '$poikkeamatilaus'
							and lasku.yhtio		= '$kukarow[yhtio]'";
				$result = pupe_query($query);
				$laskurow = mysql_fetch_assoc($result);

				$kieli = $laskurow["kieli"];

				$rivit = '';

				foreach ($poikkeamatilausrivit as $poikkeama) {

					$poikkeama['nimitys'] = t_tuotteen_avainsanat($poikkeama, 'nimitys', $kieli);

					$rivit .= "<tr>";
					$rivit .= "<td>$poikkeama[nimitys]</td>";
					$rivit .= "<td>$poikkeama[tuoteno]</td>";
					$rivit .= "<td>". (float) $poikkeama["tilkpl"]."</td>";
					$rivit .= "<td>". (float) $poikkeama["maara"]."</td>";
					if ($yhtiorow["kerayspoikkeama_kasittely"] != '') $rivit .= "<td>$poikkeama[loput]</td>";
					$rivit .= "</tr>";
				}

				$header  = "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n";
				$header .= "Content-type: text/html; charset=\"iso-8859-1\"\n";

				$ulos  = "<html>\n<head>\n";
				$ulos .= "<style type='text/css'>$css</style>\n";
				$ulos .= "<title>$yhtiorow[nimi]</title>\n";
				$ulos .= "</head>\n";

				$ulos .= "<body>\n";

				$ulos .= "<font class='head'>".t("Ker‰yspoikkeamat", $kieli)."</font><hr><br><br><table>";

				$ulos .= "<tr><th>".t("Yhtiˆ", $kieli)."</th></tr>";
				$ulos .= "<tr><td>$yhtiorow[nimi]</td></tr>";
				$ulos .= "<tr><td>$yhtiorow[osoite]</td></tr>";
				$ulos .= "<tr><td>$yhtiorow[postino] $yhtiorow[postitp]</td></tr>";
				$ulos .= "</table><br><br>";

				$ulos .= "<table>";
				$ulos .= "<tr><th>".t("Ostaja", $kieli).":</th><th>".t("Toimitusosoite", $kieli).":</th></tr>";

				$ulos .= "<tr><td>$laskurow[nimi]</td><td>$laskurow[toim_nimi]</td></tr>";
				$ulos .= "<tr><td>$laskurow[nimitark]</td><td>$laskurow[toim_nimitark]</td></tr>";
				$ulos .= "<tr><td>$laskurow[osoite]</td><td>$laskurow[toim_osoite]</td></tr>";
				$ulos .= "<tr><td>$laskurow[postino] $laskurow[postitp]</td><td>$laskurow[toim_postino] $laskurow[toim_postitp]</td></tr>";
				$ulos .= "</table><br><br>";

				$ulos .= "<table>";
				$ulos .= "<tr><th>".t("Laadittu", $kieli).":</th><td>".tv1dateconv($laskurow['luontiaika'])."</td></tr>";
				$ulos .= "<tr><th>".t("Tilausnumero", $kieli).":</th><td>$laskurow[tunnus]</td></tr>";
				$ulos .= "<tr><th>".t("Tilausviite", $kieli).":</th><td>$laskurow[viesti]</td></tr>";
				$ulos .= "<tr><th>".t("Toimitustapa", $kieli).":</th><td>".t_tunnus_avainsanat($laskurow['toimitustapa'], "selite", "TOIMTAPAKV", $kieli)."</td></tr>";
				$ulos .= "<tr><th>".t("Myyj‰", $kieli).":</th><td>$laskurow[kukanimi]</td></tr>";

				if ($laskurow['comments'] != '') {
					$ulos .= "<tr><th>".t("Kommentti", $kieli).":</th><td>".$laskurow['comments']."</td></tr>";
				}

				$ulos .= "</table><br><br>";

				$ulos .= "<table>";
				$ulos .= "<tr><th>".t("Nimitys", $kieli)."</th><th>".t("Tuotenumero", $kieli)."</th><th>".t("Tilattu", $kieli)."</th><th>".t("Toimitetaan", $kieli)."</th>";
				if ($yhtiorow["kerayspoikkeama_kasittely"] != '') $ulos .= "<th>".t("Poikkeaman k‰sittely", $kieli)."</th>";
				$ulos .= "</tr>";
				$ulos .= $rivit;
				$ulos .= "</table><br><br>";

				$ulos .= t("T‰m‰ on automaattinen viesti. T‰h‰n s‰hkˆpostiin ei tarvitse vastata.", $kieli)."<br><br>";
				$ulos .= "</body></html>";

				// korvataan poikkeama-meili ker‰ysvahvistuksella
				if (($laskurow["keraysvahvistus_lahetys"] == 'o' or ($yhtiorow["keraysvahvistus_lahetys"] == 'o' and $laskurow["keraysvahvistus_lahetys"] == '')) and $laskurow["kerayspoikkeama"] == 0) {
					$laskurow["kerayspoikkeama"] = 2;
				}

				$boob = "";

				// L‰hetet‰‰n ker‰yspoikkeama asiakkaalle
				if ($laskurow["email"] != '' and $laskurow["kerayspoikkeama"] == 0) {
					$boob = mail($laskurow["email"], mb_encode_mimeheader("$yhtiorow[nimi] - ".t("Ker‰yspoikkeamat", $kieli), "ISO-8859-1", "Q"), $ulos, $header, "-f $yhtiorow[postittaja_email]");
					if ($boob === FALSE) echo " - ".t("Email l‰hetys ep‰onnistui")."!<br>";
				}

				// L‰hetet‰‰n ker‰yspoikkeama myyj‰lle
				if ($laskurow["kukamail"] != '' and ($laskurow["kerayspoikkeama"] == 0 or $laskurow["kerayspoikkeama"] == 2)) {

					$uloslisa = "";

					if (($laskurow["email"] == '' or $boob === FALSE) and $laskurow["kerayspoikkeama"] == 0) {
						$uloslisa .= t("Asiakkaalta puuttuu s‰hkˆpostiosoite! Ker‰yspoikkeamia ei voitu l‰hett‰‰!")."<br><br>";
					}
					elseif ($laskurow["kerayspoikkeama"] == 2) {
						$uloslisa .= t("Asiakkaalle on merkitty ett‰ h‰n ei halua ker‰yspoikkeama ilmoituksia!")."<br><br>";
					}
					else {
						$uloslisa .= t("T‰m‰ viesti on l‰hetetty myˆs asiakkaalle")."!<br><br>";
					}

					$uloslisa .= t("Tilauksen ker‰si").": $keraaja[nimi]<br><br>";

					$ulos = str_replace("</font><hr><br><br><table>", "</font><hr><br><br>$uloslisa<table>", $ulos);

					$boob = mail($laskurow["kukamail"], mb_encode_mimeheader("$yhtiorow[nimi] - ".t("Ker‰yspoikkeamat", $kieli), "ISO-8859-1", "Q"), $ulos, $header, "-f $yhtiorow[postittaja_email]");
					if ($boob === FALSE) echo " - ".t("Email l‰hetys ep‰onnistui")."!<br>";
				}

				unset($ulos);
				unset($header);
			}
		}

		if ($tee == 'P' and $real_submit == 'yes') {
			if ($keraamaton > 0) {

				if ($toim == "VASTAANOTA_REKLAMAATIO") {
					$hakualatila = 'C';
				}
				else {
					$hakualatila = 'A';
				}

				// Jos tilauksella oli yht‰‰n ker‰‰m‰tˆnt‰ rivi‰
				$query = "	SELECT lasku.tunnus, lasku.vienti, lasku.tila, lasku.alatila,
							lasku.toimitustavan_lahto,
							lasku.ytunnus,
							lasku.toim_osoite,
							lasku.toim_postino,
							lasku.toim_postitp,
							toimitustapa.rahtikirja,
							toimitustapa.tulostustapa,
							toimitustapa.nouto,
							lasku.varasto,
							lasku.toimitustapa,
							lasku.jaksotettu
							FROM lasku
							LEFT JOIN toimitustapa ON (lasku.yhtio = toimitustapa.yhtio and lasku.toimitustapa = toimitustapa.selite)
							where lasku.yhtio = '$kukarow[yhtio]'
							and lasku.tunnus in ($tilausnumeroita)
							and lasku.tila in ($tila)
							and lasku.alatila = '$hakualatila'";
				$lasresult = pupe_query($query);

				$lask_nro = "";
				$extra    = "";

				while ($laskurow = mysql_fetch_assoc($lasresult)) {

					if ($laskurow["tila"] == 'L' and $laskurow["vienti"] == '' and $laskurow["tulostustapa"] == "X" and $laskurow["nouto"] == "") {

						// Jos meill‰ on maksupositioita laskulla, tulee se siirt‰‰ alatilaan J
						if ($laskurow['jaksotettu'] != 0) {
							$alatilak = "J";
						}
						else {
							$alatilak = "D";
						}

						// P‰ivitet‰‰n myˆs rivit toimitetuiksi
						$query = "	UPDATE tilausrivi
									SET toimitettu = '$kukarow[kuka]', toimitettuaika = now()
									WHERE otunnus 	= '$laskurow[tunnus]'
									and var not in ('P','J')
									and yhtio 		= '$kukarow[yhtio]'
									and keratty    != ''
									and toimitettu  = ''
									and tyyppi 		= 'L'";
						$yoimresult = pupe_query($query);

						// Etuk‰teen maksetut tilaukset pit‰‰ muuttaa takaisin "maksettu"-tilaan
						$query = "	UPDATE lasku SET
									alatila = 'X'
									WHERE yhtio = '$kukarow[yhtio]'
									AND tunnus = '$laskurow[tunnus]'
									AND mapvm != '0000-00-00'
									AND chn = '999'";
						$yoimresult  = pupe_query($query);
					}
					elseif ($laskurow["tila"] == 'G' and $laskurow["vienti"] == '' and $laskurow["tulostustapa"] == "X" and $laskurow["nouto"] == "") {
						// Jos meill‰ on maksupositioita laskulla, tulee se siirt‰‰ alatilaan J
						if ($laskurow['jaksotettu'] != 0) {
							$alatilak = "J";
						}
						else {
							$alatilak = "D";
						}
					}
					elseif ($toim == "VASTAANOTA_REKLAMAATIO" and $laskurow["tila"] == 'C' and $laskurow["alatila"] == "C") {
						$alatilak = "D";
						$extra = ", tila = 'L' ";
					}
					else {
						$alatilak = "C";
					}

					// Lasku p‰ivitet‰‰n vasta kuin tilausrivit on p‰ivitetty...
					$query  = "	UPDATE lasku SET
								alatila = '$alatilak'
								$extra
								WHERE yhtio = '$kukarow[yhtio]'
								AND tunnus  = '$laskurow[tunnus]'";
					$result = pupe_query($query);

					if ($lask_nro == '') {
						$lask_nro = $laskurow['tunnus'];
					}

					if ($yhtiorow['kerayserat'] != 'K' and $yhtiorow['pakkaamolokerot'] != '') {
						// jos meill‰ on pakkaamolokerot k‰ytˆss‰ (eli pakkaamotsydeema), niin esisyˆtet‰‰n rahtikirjat jos k‰ytt‰j‰ on antanut kollien/rullakkojen m‰‰r‰t
						// ei kuiteskaan tehd‰ t‰st‰ virallisesti esisyˆtetty‰ rahtikirjaa!
						if (isset($pakkaamo_kolli) and $pakkaamo_kolli != '') {
							$pakkaamo_kolli = (int) $pakkaamo_kolli;

							$query  = "	INSERT INTO rahtikirjat SET
										kollit 			= '$pakkaamo_kolli',
										pakkaus 		= 'KOLLI',
										pakkauskuvaus 	= 'KOLLI',
										rahtikirjanro 	= '$lask_nro',
										otsikkonro 		= '$lask_nro',
										tulostuspaikka 	= '$laskurow[varasto]',
										yhtio 			= '$kukarow[yhtio]'";
							$result_rk = pupe_query($query);
						}

						if (isset($pakkaamo_rullakko) and $pakkaamo_rullakko != '') {
							$pakkaamo_rullakko = (int) $pakkaamo_rullakko;

							$query  = "	INSERT INTO rahtikirjat SET
										kollit 			= '$pakkaamo_rullakko',
										pakkaus 		= 'Rullakko',
										pakkauskuvaus 	= 'Rullakko',
										rahtikirjanro 	= '$lask_nro',
										otsikkonro 		= '$lask_nro',
										tulostuspaikka 	= '$laskurow[varasto]',
										yhtio 			= '$kukarow[yhtio]'";
							$result_rk = pupe_query($query);
						}
					}

					if ($yhtiorow['kerayserat'] == 'K' and $toim == "") {
						$query = "	SELECT
									IFNULL(pakkaus.pakkaus, 'MUU KOLLI') pakkaus,
									IFNULL(pakkaus.pakkauskuvaus, 'MUU KOLLI') pakkauskuvaus,
									IFNULL(pakkaus.oma_paino, 0) oma_paino,
									IF(pakkaus.puukotuskerroin is not null and pakkaus.puukotuskerroin > 0, pakkaus.puukotuskerroin, 1) puukotuskerroin,
									SUM(tuote.tuotemassa * kerayserat.kpl_keratty) tuotemassa,
									SUM(tuote.tuoteleveys * tuote.tuotekorkeus * tuote.tuotesyvyys * kerayserat.kpl_keratty) as kuutiot,
									COUNT(distinct kerayserat.pakkausnro) AS kollit
									FROM kerayserat
									LEFT JOIN pakkaus ON (pakkaus.yhtio = kerayserat.yhtio AND pakkaus.tunnus = kerayserat.pakkaus)
									JOIN tilausrivi ON (tilausrivi.yhtio = kerayserat.yhtio AND tilausrivi.tunnus = kerayserat.tilausrivi)
									JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
									WHERE kerayserat.yhtio 	= '{$kukarow['yhtio']}'
									AND kerayserat.nro 		= '$id'
									AND kerayserat.otunnus 	= '{$laskurow['tunnus']}'
									AND kerayserat.tila 	= 'K'
									GROUP BY 1,2,3
									ORDER BY kerayserat.pakkausnro";
						$keraysera_res = pupe_query($query);

						while ($keraysera_row = mysql_fetch_assoc($keraysera_res)) {

							$kilot = round($keraysera_row["tuotemassa"] + $keraysera_row["oma_paino"], 2);
							$kuutiot = round($keraysera_row["kuutiot"] * $keraysera_row["puukotuskerroin"], 4);

							$tulostettulisa = "";

							// Merataan tieto tulostetuksi jos tulostustapa on hetitulostus ja lappu on jo tullut Unifaunista
							if ($laskurow['tulostustapa'] == 'H' and ($laskurow["rahtikirja"] == 'rahtikirja_unifaun_ps_siirto.inc' or $laskurow["rahtikirja"] == 'rahtikirja_unifaun_uo_siirto.inc')) {
								$tulostettulisa = " , tulostettu = now() ";
							}

							// Insertˆid‰‰n aina rahtikirjan tiedot per tilaus
							$query_ker  = "	INSERT INTO rahtikirjat SET
											kollit 			= '{$keraysera_row['kollit']}',
											kilot 			= '{$kilot}',
											kuutiot 		= '{$kuutiot}',
											pakkauskuvaus 	= '{$keraysera_row['pakkauskuvaus']}',
											pakkaus 		= '{$keraysera_row['pakkaus']}',
											rahtikirjanro 	= '{$laskurow['tunnus']}',
											otsikkonro 		= '{$laskurow['tunnus']}',
											tulostuspaikka 	= '{$laskurow['varasto']}',
											toimitustapa 	= '{$laskurow['toimitustapa']}',
											yhtio 			= '{$kukarow['yhtio']}'
											{$tulostettulisa}";
							$ker_res = pupe_query($query_ker);
						}

						if ($laskurow['tulostustapa'] == 'E' and
							(($laskurow["rahtikirja"] == 'rahtikirja_unifaun_ps_siirto.inc' and $unifaun_ps_host != "" and $unifaun_ps_user != "" and $unifaun_ps_pass != "" and $unifaun_ps_path != "") or
							($laskurow["rahtikirja"] == 'rahtikirja_unifaun_uo_siirto.inc' and $unifaun_uo_host != "" and $unifaun_uo_user != "" and $unifaun_uo_pass != "" and $unifaun_uo_path != ""))) {

							// Katotaan j‰‰kˆ meille t‰ss‰ vaiheessa tyhji‰ kolleja?
							$query = "	SELECT pakkausnro, sscc_ulkoinen, sum(kpl_keratty) kplkeratty
										FROM kerayserat
										WHERE yhtio 		= '{$kukarow['yhtio']}'
										AND nro 			= '$id'
										AND otunnus 		= '{$laskurow['tunnus']}'
										AND tila 			= 'K'
										AND sscc_ulkoinen  != '0'
										GROUP BY 1,2
										HAVING kplkeratty = 0";
							$keraysera_res = pupe_query($query);

							while ($keraysera_row = mysql_fetch_assoc($keraysera_res)) {
								if ($laskurow["rahtikirja"] == 'rahtikirja_unifaun_ps_siirto.inc' and $unifaun_ps_host != "" and $unifaun_ps_user != "" and $unifaun_ps_pass != "" and $unifaun_ps_path != "") {
									$unifaun = new Unifaun($unifaun_ps_host, $unifaun_ps_user, $unifaun_ps_pass, $unifaun_ps_path, $unifaun_ps_port, $unifaun_ps_fail, $unifaun_ps_succ);
								}
								elseif ($laskurow["rahtikirja"] == 'rahtikirja_unifaun_uo_siirto.inc' and $unifaun_uo_host != "" and $unifaun_uo_user != "" and $unifaun_uo_pass != "" and $unifaun_uo_path != "") {
									$unifaun = new Unifaun($unifaun_uo_host, $unifaun_uo_user, $unifaun_uo_pass, $unifaun_uo_path, $unifaun_uo_port, $unifaun_uo_fail, $unifaun_uo_succ);
								}

								$mergeid = md5($laskurow["toimitustavan_lahto"].$laskurow["ytunnus"].$laskurow["toim_osoite"].$laskurow["toim_postino"].$laskurow["toim_postitp"]);

								$unifaun->_discardParcel($mergeid, $keraysera_row['sscc_ulkoinen']);
								$unifaun->ftpSend();
							}
						}

						// jos kyseess‰ on toimitustapa jonka rahtikirja on hetitulostus
						if ($laskurow['tulostustapa'] == 'H' and $laskurow["nouto"] == "") {
							// p‰ivitet‰‰n ker‰yser‰n tila "Rahtikirja tulostettu"-tilaan
							$query = "	UPDATE kerayserat
										SET tila = 'R'
										WHERE yhtio = '{$kukarow['yhtio']}'
										AND nro 	= '$id'
										AND otunnus = '{$laskurow['tunnus']}'";
							$tila_upd_res = pupe_query($query);
						}
						else {
							// p‰ivitet‰‰n ker‰yser‰n tila "Ker‰tty"-tilaan
							$query = "	UPDATE kerayserat
										SET tila = 'T'
										WHERE yhtio = '{$kukarow['yhtio']}'
										AND nro 	= '$id'
										AND otunnus = '{$laskurow['tunnus']}'";
							$tila_upd_res = pupe_query($query);
						}
					}
				}

				// Tutkitaan viel‰ aivan lopuksi mihin tilaan me laitetaan t‰m‰ otsikko
				// Ker‰ysvaiheessahan tilausrivit muuttuvat ja tarkastamme nyt tilanteen uudestaan
				// T‰m‰ tehd‰‰n vain myyntitilauksille
				if ($tila == "'L'") {
					$kutsuja = "keraa.php";

					$query = "	SELECT *
								FROM lasku
								WHERE tunnus in ($tilausnumeroita)
								and yhtio = '$kukarow[yhtio]'
								and tila  = 'L'";
					$lasresult = pupe_query($query);

					while ($laskurow = mysql_fetch_assoc($lasresult)) {
						require("tilaus-valmis-valitsetila.inc");
					}
				}

				if ($toim != 'VASTAANOTA_REKLAMAATIO') {
					// Tulostetaan uusi l‰hete jos k‰ytt‰j‰ valitsi drop-downista printterin
					// Paitsi jos tilauksen tila p‰ivitettiin sellaiseksi, ett‰ l‰hetett‰ ei kuulu tulostaa
					$query = "	SELECT lasku.*, if(asiakas.keraysvahvistus_email != '', asiakas.keraysvahvistus_email, asiakas.email) email, asiakas.keraysvahvistus_lahetys
								FROM lasku
								LEFT JOIN asiakas on lasku.yhtio = asiakas.yhtio and lasku.liitostunnus = asiakas.tunnus
								WHERE lasku.tunnus in ($tilausnumeroita)
								and lasku.yhtio = '$kukarow[yhtio]'
								and lasku.alatila in ('C','D')";
					$lasresult = pupe_query($query);

					$tilausnumeroita_backup 	= $tilausnumeroita;
					$lahete_tulostus_paperille 	= 0;
					$laheteprintterinimi 		= "";

					while ($laskurow = mysql_fetch_assoc($lasresult)) {

						// Nollataan t‰m‰:
						$komento		= "";
						$oslapp			= "";
						$vakadr_komento = "";

						if ($yhtiorow["vak_erittely"] == "K" and $yhtiorow["kerayserat"] == "K" and $vakadrkpl > 0 and $vakadr_tulostin !='' and $toim == "") {
							//haetaan l‰hetteen tulostuskomento
							$query   = "SELECT * from kirjoittimet where yhtio='$kukarow[yhtio]' and tunnus='$vakadr_tulostin'";
							$kirres  = pupe_query($query);
							$kirrow  = mysql_fetch_assoc($kirres);
							$vakadr_komento = $kirrow['komento'];

							tulosta_vakadr_erittely($laskurow["tunnus"], $vakadr_komento, $tee);
						}

						if ($valittu_tulostin != "") {
							//haetaan l‰hetteen tulostuskomento
							$query   = "SELECT * from kirjoittimet where yhtio='$kukarow[yhtio]' and tunnus='$valittu_tulostin'";
							$kirres  = pupe_query($query);
							$kirrow  = mysql_fetch_assoc($kirres);
							$komento = $kirrow['komento'];

							$laheteprintterinimi = $kirrow["kirjoitin"];
						}

						if ($valittu_oslapp_tulostin != "") {
							//haetaan osoitelapun tulostuskomento
							$query  = "SELECT * from kirjoittimet where yhtio='$kukarow[yhtio]' and tunnus='$valittu_oslapp_tulostin'";
							$kirres = pupe_query($query);
							$kirrow = mysql_fetch_assoc($kirres);
							$oslapp = $kirrow['komento'];
						}

						if (($valittu_tulostin != '' and $komento != "" and $lahetekpl > 0) or (($laskurow["keraysvahvistus_lahetys"] == 'k' or ($yhtiorow["keraysvahvistus_lahetys"] == 'k' and $laskurow["keraysvahvistus_lahetys"] == '')) or (($laskurow["keraysvahvistus_lahetys"] == 'o' or ($yhtiorow["keraysvahvistus_lahetys"] == 'o' and $laskurow["keraysvahvistus_lahetys"] == '')) and $laskurow['email'] != ""))) {

							$koontilahete = FALSE;

							// onko koontivahvistus k‰ytˆss‰?
							if ($laskurow["keraysvahvistus_lahetys"] == 'k' or ($yhtiorow["keraysvahvistus_lahetys"] == 'k' and $laskurow["keraysvahvistus_lahetys"] == '')) {

								$hakutunnus = ($laskurow["vanhatunnus"] > 0) ? $laskurow["vanhatunnus"] : $laskurow["tunnus"];

								// Onko kaikki rivit ker‰tty?
								$query = "	SELECT lasku.tunnus
											FROM lasku
											JOIN tilausrivi use index (yhtio_otunnus) ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.keratty = '' AND tilausrivi.tyyppi != 'D' AND tilausrivi.var not in ('P','J'))
											JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus AND tilausrivin_lisatiedot.ohita_kerays = '')
											JOIN tuote ON (tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno and tuote.ei_saldoa = '')
											WHERE lasku.yhtio	  = '$kukarow[yhtio]'
											AND lasku.vanhatunnus = '$hakutunnus'";
								$vanhat_res = pupe_query($query);

								if (mysql_num_rows($vanhat_res) == 0) {
									// Kaikki rivit ker‰tty! Tulostetaan koontilahete
									$koontilahete = TRUE;
								}
								else {
									// T‰ll‰ asiakkaalla on koontil‰heteprosessi p‰‰ll‰, mutta kaikki rivit ei oo viel‰ ker‰tty, joten ei tulosteta mitt‰‰n
									$komento = "";
								}
							}

							if ($koontilahete and $laskurow['email'] != "") {
								// Jos l‰hetet‰‰n s‰hkˆinen koontil‰hete, niin ei tulosteta paperille mith‰‰n
								$komento = "asiakasemail".$laskurow['email'];
							}
							elseif (($laskurow["keraysvahvistus_lahetys"] == 'o' or ($yhtiorow["keraysvahvistus_lahetys"] == 'o' and $laskurow["keraysvahvistus_lahetys"] == '')) and $laskurow['email'] != "") {
								// Jos l‰hetet‰‰n s‰hkˆinen ker‰ysvahvistus, niin ei tulostetaan myˆs paperille, eli pushataan arrayseen
								if ($komento != "") $komento = array($komento);
								else $komento = array();

								$komento[] = "asiakasemail".$laskurow['email'];
							}

							if ((is_array($komento) and count($komento) > 0) or (!is_array($komento) and $komento != "")) {

								// Lasketaan kuinka monta l‰hetett‰ tulostuu paperille (muuttujat valuu optiscan.php:seen)
								if (is_array($komento)) {
									foreach ($komento as $paprulleko) {
										if ($paprulleko != 'email' and substr($paprulleko,0,12) != 'asiakasemail') {
											$lahete_tulostus_paperille++;
										}
									}
								}
								elseif ($komento != 'email' and substr($komento,0,12) != 'asiakasemail') {
									$lahete_tulostus_paperille++;
								}

								$sellahetetyyppi = (!isset($sellahetetyyppi)) ? "" : $sellahetetyyppi;
								$kieli = (!isset($kieli)) ? "" : $kieli;

								$params = array(
									'laskurow'					=> $laskurow,
									'sellahetetyyppi' 			=> $sellahetetyyppi,
									'extranet_tilausvahvistus' 	=> "",
									'naytetaanko_rivihinta'		=> "",
									'tee'						=> $tee,
									'toim'						=> $toim,
									'komento' 					=> $komento,
									'lahetekpl'					=> $lahetekpl,
									'kieli' 					=> $kieli,
									'koontilahete'				=> $koontilahete,
									);

								pupesoft_tulosta_lahete($params);
							}
						}

						if ($yhtiorow['karayksesta_rahtikirjasyottoon'] == 'Y' or ($yhtiorow['karayksesta_rahtikirjasyottoon'] == 'H' and $rahtikirjalle != "")) {
							$valittu_oslapp_tulostin 	= "";
							$oslapp 					= '';
							$oslappkpl 					= 0;
						}

						// Tulostetaan osoitelappu
						if ($valittu_oslapp_tulostin != "" and $oslapp != '' and $oslappkpl > 0) {
							$tunnus = $laskurow["tunnus"];

							$query = "SELECT osoitelappu FROM toimitustapa WHERE yhtio = '$kukarow[yhtio]' and selite = '$laskurow[toimitustapa]'";
							$oslares = pupe_query($query);
							$oslarow = mysql_fetch_assoc($oslares);

							if ($oslarow['osoitelappu'] == 'intrade') {
								require('osoitelappu_intrade_pdf.inc');
							}
							else {
								require ("osoitelappu_pdf.inc");
							}
						}
					}

					if ($yhtiorow['kerayserat'] == 'K' and $toim == "") {
						$query = "	UPDATE lasku
									SET alatila = 'B'
									WHERE yhtio = '{$kukarow['yhtio']}'
									AND tunnus IN ({$tilausnumeroita_backup})";
						$alatila_upd_res = pupe_query($query);
					}

					echo "<br><br>";
				}
			}

			$boob    = '';
			$header  = '';
			$content = '';
			$rivit   = '';

			if ($yhtiorow['karayksesta_rahtikirjasyottoon'] == 'Y' or ($yhtiorow['karayksesta_rahtikirjasyottoon'] == 'H' and $rahtikirjalle != "")) {
				$query = "	SELECT tunnus
							FROM lasku
							WHERE lasku.yhtio 	= '$kukarow[yhtio]'
							and lasku.tila 		= 'L'
							and lasku.alatila 	= 'C'
							and tunnus = '$id'";
				$result = pupe_query($query);

				if (mysql_num_rows($result) > 0) {
					$rahtikirjaan = 'mennaan';
				}
				else {
					$tilausnumeroita = '';
					$rahtikirjaan = '';
					$id = 0;
				}
			}
			else {
				$tilausnumeroita	= '';
				$rahtikirjaan		= '';
				$id 				= 0;
			}
		}
	}

	if (php_sapi_name() != 'cli' and strpos($_SERVER['SCRIPT_NAME'], "keraa.php") !== FALSE) {
		if ($id == '') {
			$id = 0;
			if ($logistiikka_yhtio != '' and $konsernivarasto_yhtiot != '') {
				$logistiikka_yhtio = $konsernivarasto_yhtiot;
			}
		}

		if ($id == 0) {

			$formi	= "find";
			$kentta	= "etsi";

			echo "<table>";
			echo "<form name='find' method='post'>";
			echo "<input type='hidden' name='toim' value='{$toim}'>";
			echo "<input type='hidden' id='jarj' name='jarj' value='{$jarj}'>";
			echo "<tr><th>",t("Valitse varasto"),":</th><td><select name='tuvarasto' onchange='submit()'>";

			$query = "	SELECT yhtio, tunnus, nimitys
						FROM varastopaikat
						WHERE {$logistiikka_yhtiolisa}
						ORDER BY yhtio, tyyppi, nimitys";
			$result = pupe_query($query);

			echo "<option value='KAIKKI'>",t("N‰yt‰ kaikki"),"</option>";

			while ($row = mysql_fetch_assoc($result)) {
				$sel = '';

				if (($row['tunnus'] == $tuvarasto) or ((isset($kukarow["varasto"]) and (int) $kukarow["varasto"] > 0 and in_array($row['tunnus'], explode(",", $kukarow['varasto']))) and $tuvarasto=='')) {
					$sel = 'selected';
					$tuvarasto = $row['tunnus'];
				}

				echo "<option value='{$row['tunnus']}' {$sel}>{$row['nimitys']}";

				if ($logistiikka_yhtio != '') {
					echo " ({$row['yhtio']})";
				}

				echo "</option>";
			}
			echo "</select>";

			$query = "	SELECT DISTINCT maa
						FROM varastopaikat
						WHERE maa != ''
						AND {$logistiikka_yhtiolisa}
						ORDER BY maa";
			$result = pupe_query($query);

			if (mysql_num_rows($result) > 1) {
				echo "<select name='tumaa' onchange='submit()'>";
				echo "<option value=''>",t("Kaikki"),"</option>";

				while ($row = mysql_fetch_assoc($result)){
					$sel = '';

					if ($row['maa'] == $tumaa) {
						$sel = 'selected';
						$tumaa = $row['maa'];
					}

					echo "<option value='{$row['maa']}' {$sel}>{$row['maa']}</option>";
				}
				echo "</select>";
			}

			echo "</td>";
			echo "<th>",t("Valitse tilaustyyppi"),":</th><td><select name='tutyyppi' onchange='submit()'>";

			$sela = $selb = $selc = "";

			if ($tutyyppi == "NORMAA") {
				$sela = "SELECTED";
			}
			if ($tutyyppi == "ENNAKK") {
				$selb = "SELECTED";
			}
			if ($tutyyppi == "JTTILA") {
				$selc = "SELECTED";
			}

			echo "<option value='KAIKKI'>",t("N‰yt‰ kaikki"),"</option>";
			echo "<option value='NORMAA' {$sela}>",t("N‰yt‰ normaalitilaukset"),"</option>";
			echo "<option value='ENNAKK' {$selb}>",t("N‰yt‰ ennakkotilaukset"),"</option>";
			echo "<option value='JTTILA' {$selc}>",t("N‰yt‰ jt-tilaukset"),"</option>";

			echo "</select></td></tr>";

			echo "<tr><th>",t("Valitse toimitustapa"),":</th><td><select name='tutoimtapa' onchange='submit()'>";

			$query = "	SELECT selite, MIN(tunnus) tunnus
						FROM toimitustapa
						WHERE {$logistiikka_yhtiolisa}
						GROUP BY selite
						ORDER BY selite";
			$result = pupe_query($query);

			echo "<option value='KAIKKI'>",t("N‰yt‰ kaikki"),"</option>";

			while ($row = mysql_fetch_assoc($result)){
				$sel = '';

				if ($row['selite'] == $tutoimtapa) {
					$sel = 'selected';
					$tutoimtapa = $row['selite'];
				}

				echo "<option value='{$row['selite']}' {$sel}>",t_tunnus_avainsanat($row, "selite", "TOIMTAPAKV"),"</option>";
			}

			echo "</select></td>";

			echo "<th>",t("Etsi tilausta"),":</th><td><input type='text' name='etsi'>";
			echo "<input type='submit' value='",t("Etsi"),"'></form></td></tr>";
			echo "</table>";

			$haku = '';
			$kerayserahaku = '';

			if (!is_numeric($etsi) and $etsi != '') {
				$haku .= "AND lasku.nimi LIKE '%{$etsi}%'";
			}

			if (is_numeric($etsi) and $etsi != '') {
				if ($yhtiorow['kerayserat'] == 'K' and $toim == "") {
					$query = "	SELECT nro
								FROM kerayserat
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND otunnus = '{$etsi}'";
					$nro_chk_res = pupe_query($query);
					$nro_chk_row = mysql_fetch_assoc($nro_chk_res);

					$kerayserahaku = "AND kerayserat.nro = '{$nro_chk_row['nro']}'";
				}
				else {
					$haku .= "AND lasku.tunnus = '{$etsi}'";
				}
			}

			if ($tuvarasto != '' and $tuvarasto != 'KAIKKI') {
				$haku .= " AND lasku.varasto = '{$tuvarasto}' ";
			}

			if ($tumaa != '') {
				$query = "	SELECT GROUP_CONCAT(tunnus) tunnukset
							FROM varastopaikat
							WHERE maa != ''
							AND {$logistiikka_yhtiolisa}
							AND maa = '{$tumaa}'";
				$maare = pupe_query($query);
				$maarow = mysql_fetch_assoc($maare);
				$haku .= " AND lasku.varasto IN ({$maarow['tunnukset']}) ";
			}

			if ($tutoimtapa != '' and $tutoimtapa != 'KAIKKI') {
				$haku .= " AND lasku.toimitustapa = '{$tutoimtapa}' ";
			}

			if ($tutyyppi != '' and $tutyyppi != 'KAIKKI') {
				if ($tutyyppi == "NORMAA") {
					$haku .= " AND lasku.clearing = '' ";
				}
				elseif ($tutyyppi == "ENNAKK") {
					$haku .= " AND lasku.clearing = 'ENNAKKOTILAUS' ";
				}
				elseif ($tutyyppi == "JTTILA") {
					$haku .= " AND lasku.clearing = 'JT-TILAUS' ";
				}
			}

			if ($jarj != "") {
				$jarjx = " ORDER BY {$jarj}";
			}
			else {
				$jarjx = ($yhtiorow['kerayserat'] == 'K' and $toim == "") ? " ORDER BY kerayserat.nro" : " ORDER BY laadittu";
			}

			if ($toim == "VASTAANOTA_REKLAMAATIO") {
				$alatilareklamaatio = 'C';
			}
			else {
				$alatilareklamaatio = 'A';
			}

			if ($yhtiorow['kerayserat'] == 'K' and $toim == "") {
				$query = "	SELECT lasku.yhtio AS 'yhtio',
							lasku.yhtio_nimi AS 'yhtio_nimi',
							kerayserat.nro AS 'keraysera',
							GROUP_CONCAT(DISTINCT lasku.toimitustapa ORDER BY lasku.toimitustapa SEPARATOR '<br />') AS 'toimitustapa',
							GROUP_CONCAT(DISTINCT lasku.prioriteettinro ORDER BY lasku.prioriteettinro SEPARATOR ', ') AS prioriteetti,
							GROUP_CONCAT(DISTINCT concat_ws(' ', lasku.toim_nimi, lasku.toim_nimitark, CONCAT(\"(\", lasku.ytunnus, \")\")) SEPARATOR '<br />') AS 'asiakas',
							GROUP_CONCAT(DISTINCT lasku.tunnus ORDER BY lasku.tunnus SEPARATOR ', ') AS 'tunnus',
							COUNT(DISTINCT tilausrivi.tunnus) AS 'riveja'
							FROM lasku USE INDEX (tila_index)
							JOIN tilausrivi USE INDEX (yhtio_otunnus) ON (
								tilausrivi.yhtio = lasku.yhtio AND
								tilausrivi.otunnus = lasku.tunnus AND
								tilausrivi.tyyppi = 'L' AND
								tilausrivi.var IN ('', 'H') AND
								tilausrivi.keratty = '' AND
								tilausrivi.kerattyaika = '0000-00-00 00:00:00' AND
								((tilausrivi.laskutettu = '' AND tilausrivi.laskutettuaika 	= '0000-00-00') OR lasku.mapvm != '0000-00-00'))
							JOIN kerayserat ON (kerayserat.yhtio = lasku.yhtio AND kerayserat.otunnus = lasku.tunnus AND kerayserat.tila = 'K' {$kerayserahaku})
							JOIN asiakas ON (asiakas.yhtio = lasku.yhtio AND asiakas.tunnus = lasku.liitostunnus)
							WHERE lasku.{$logistiikka_yhtiolisa}
							AND lasku.tila = 'L'
							AND lasku.alatila = 'A'
							{$haku}
							GROUP BY 1,2,3
							{$jarjx}";
			}
			else {
				$query = "	SELECT distinct
							if (lasku.kerayslista!=0, lasku.kerayslista, lasku.tunnus) tunnus,
							group_concat(DISTINCT lasku.tunnus SEPARATOR '<br>') tunnukset,
							min(lasku.ytunnus) ytunnus,
							min(concat_ws(' ', lasku.toim_nimi, lasku.toim_nimitark)) asiakas,
							min(lasku.luontiaika) laadittu,
							min(lasku.h1time) h1time,
							min(lasku.lahetepvm) lahetepvm,
							min(lasku.kerayspvm) kerayspvm,
							min(lasku.toimaika) toimaika,
							group_concat(DISTINCT lasku.laatija) laatija,
							group_concat(DISTINCT lasku.toimitustapa SEPARATOR '<br>') toimitustapa,
							group_concat(DISTINCT concat_ws('\n\n', if (comments!='',concat('".t("L‰hetteen lis‰tiedot").":\n',comments),NULL), if (sisviesti2!='',concat('".t("Ker‰yslistan lis‰tiedot").":\n',sisviesti2),NULL)) SEPARATOR '\n') ohjeet,
							min(if (lasku.hyvaksynnanmuutos = '', 'X', lasku.hyvaksynnanmuutos)) prioriteetti,
							min(if (lasku.clearing = '', 'N', if (lasku.clearing = 'JT-TILAUS', 'J', if (lasku.clearing = 'ENNAKKOTILAUS', 'E', '')))) t_tyyppi,
							#(select nimitys from varastopaikat where varastopaikat.tunnus=min(lasku.varasto)) varastonimi,
							count(*) riveja,
							lasku.yhtio yhtio,
							lasku.yhtio_nimi yhtio_nimi
							from lasku use index (tila_index)
							JOIN tilausrivi use index (yhtio_otunnus) ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi != 'D')
							WHERE lasku.{$logistiikka_yhtiolisa}
							and lasku.tila					in ({$tila})
							and lasku.alatila				= '{$alatilareklamaatio}'
							and tilausrivi.tyyppi			in ({$tyyppi})
							and tilausrivi.var				in ('', 'H' {$var_lisa})
							and tilausrivi.keratty	 		= ''
							and tilausrivi.kerattyaika		= '0000-00-00 00:00:00'
							and ((tilausrivi.laskutettu		= ''
							and tilausrivi.laskutettuaika 	= '0000-00-00') or lasku.mapvm != '0000-00-00')
							{$haku}
							{$tilaustyyppi}
							GROUP BY tunnus
							{$jarjx}";
			}

			$result = pupe_query($query);

			//jos haetaan numerolla ja lˆydet‰‰n yksi osuma, siirryt‰‰n suoraan ker‰‰m‰‰n
			if (mysql_num_rows($result) == 1 AND is_numeric($etsi) and $etsi != '') {
				$row = mysql_fetch_assoc($result);
				if ($yhtiorow['kerayserat'] == 'K' and $toim == "") {
					$id = $row[keraysera];
				}
				else {
					$id = $row[tunnus];
				}
			}
			else if (mysql_num_rows($result) > 0) {
				//piirret‰‰n taulukko...
				echo "<br><table>";
				echo "<tr>";
				if ($logistiikka_yhtio != '') {
					echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='yhtio'; document.forms['find'].submit();\">",t("Yhtiˆ"),"</a></th>";
				}
				echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='prioriteetti'; document.forms['find'].submit();\">",t("Pri"),"</a><br>";
				//echo "<a href='#' onclick=\"getElementById('jarj').value='varastonimi'; document.forms['find'].submit();\">".t("Varastoon")."</a></th>";

				if ($yhtiorow['kerayserat'] == '' or $toim != "") {
					echo "<a href='#'>",t("Varastoon"),"</a>";
				}

				echo "</th>";

				if ($yhtiorow['kerayserat'] == 'K' and $toim == "") {
					echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='keraysera'; document.forms['find'].submit();\">",t("Er‰"),"</a></th>";
				}

				echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='tunnus'; document.forms['find'].submit();\">",t("Tilaus"),"</a></th>";

				echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='ytunnus'; document.forms['find'].submit();\">",t("Asiakas"),"</a><br>
						<a href='#' onclick=\"getElementById('jarj').value='asiakas'; document.forms['find'].submit();\">",t("Nimi"),"</a></th>";


				echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='laadittu'; document.forms['find'].submit();\">",t("Laadittu"),"</a><br>
					  	<a href='#' onclick=\"getElementById('jarj').value='lasku.h1time'; document.forms['find'].submit();\">",t("Valmis"),"</a><br>
						<a href='#' onclick=\"getElementById('jarj').value='lasku.lahetepvm'; document.forms['find'].submit();\">",t("Tulostettu"),"</a></th>";

				echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='kerayspvm'; document.forms['find'].submit();\">",t("Ker‰ysaika"),"</a><br>
						<a href='#' onclick=\"getElementById('jarj').value='toimaika'; document.forms['find'].submit();\">",t("Toimitusaika"),"</a></th>";

				echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='toimitustapa'; document.forms['find'].submit();\">",t("Toimitustapa"),"</a></th>";
				echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='riveja'; document.forms['find'].submit();\">",t("Riv"),"</a></th>";
				echo "<th valign='top'>",t("Ker‰‰"),"</th>";

				echo "</tr></form>";

				$riveja_yht = 0;

				while ($row = mysql_fetch_assoc($result)) {
					echo "<tr class='aktiivi'>";

					if ($logistiikka_yhtio != '') {
						echo "<td valign='top'>{$row['yhtio_nimi']}</td>";
					}

					if (isset($row['ohjeet']) and trim($row["ohjeet"]) != "") {
						echo "<div id='div_{$row['tunnus']}' class='popup' style='width: 500px;'>";
						echo t("Tilaukset"),": {$row['tunnukset']}<br />";
						echo t("Laatija"),": {$row['laatija']}<br /><br />";
						echo str_replace("\n", "<br />", $row["ohjeet"]),"<br />";
						echo "</div>";

						echo "<td valign='top' class='tooltip' id='{$row['tunnus']}'>{$row['t_tyyppi']} {$row['prioriteetti']} <img src='{$palvelin2}pics/lullacons/info.png' />";
					}
					else {
						if ($yhtiorow['kerayserat'] == 'K' and $toim == "") {
							echo "<td valign='top'>{$row['prioriteetti']}";
						}
						else {
							echo "<td valign='top'>{$row['t_tyyppi']} {$row['prioriteetti']}";
						}
					}

					if (isset($row['varastonimi'])) echo "<br>{$row['varastonimi']}";

					echo "</td>";

					if ($yhtiorow['kerayserat'] == 'K' and $toim == "") {
						echo "<td valign='top'>{$row['keraysera']}</td>";
					}

					echo "<td valign='top'>{$row['tunnus']}</td>";

					if ($yhtiorow['kerayserat'] == 'K' and $toim == "") {
						echo "<td valign='top'>{$row['asiakas']}</td>";
					}
					else {
						echo "<td valign='top'>{$row['ytunnus']}<br />{$row['asiakas']}</td>";
					}

					if ($yhtiorow['kerayserat'] == 'K' and $toim == "") {
						echo "<td valign='top' nowrap align='right'></td>";
						echo "<td valign='top' nowrap align='right'></td>";
					}
					else {
						$laadittu_e  = tv1dateconv($row["laadittu"], "P", "LYHYT");
						$h1time_e	 = tv1dateconv($row["h1time"], "P", "LYHYT");
						$lahetepvm_e = tv1dateconv($row["lahetepvm"], "P", "LYHYT");
						$lahetepvm_e = str_replace(substr($h1time_e, 0, strpos($h1time_e, " ")), "", $lahetepvm_e);
						$h1time_e	 = str_replace(substr($laadittu_e, 0, strpos($laadittu_e, " ")), "", $h1time_e);

						echo "<td valign='top' nowrap align='right'>{$laadittu_e}<br />{$h1time_e}<br />{$lahetepvm_e}</td>";
						echo "<td valign='top' nowrap align='right'>",tv1dateconv($row["kerayspvm"], "", "LYHYT"),"<br />",tv1dateconv($row["toimaika"], "", "LYHYT"),"</td>";
					}

					echo "<td valign='top'>{$row['toimitustapa']}</td>";
					echo "<td valign='top'>{$row['riveja']}</td>";

					$riveja_yht += $row['riveja'];

					echo "<td valign='top'><form method='post'>";

					if ($yhtiorow['kerayserat'] == 'K' and $toim == "") {
						echo "<input type='hidden' name='id' value='{$row['keraysera']}' />";
					}
					else {
						echo "<input type='hidden' name='id' value='{$row['tunnus']}' />";
					}

					echo "	<input type='hidden' name='toim' value='{$toim}' />
							<input type='hidden' name='lasku_yhtio' value='{$row['yhtio']}' />
							<input type='submit' name='tila' value='",t("Ker‰‰"),"' /></form></td></tr>";
				}

				$spanni = $logistiikka_yhtio != '' ? 7 : 6;

				$spanni = ($yhtiorow['kerayserat'] == 'K' and $toim == "") ? $spanni + 1 : $spanni;

				echo "<tr>";
				echo "<td colspan='{$spanni}' style='text-align:right;' class='back'>",t("Rivej‰ yhteens‰"),":</td>";
				echo "<td valign='top' class='back'>{$riveja_yht}</td>";
				echo "</tr>";
				echo "</table>";
			}
			else {
				echo "<font class='message'>",t("Yht‰‰n ker‰‰m‰tˆnt‰ tilausta ei lˆytynyt"),"...</font>";
			}
		}

		if ($id != 0 and (!isset($rahtikirjaan) or $rahtikirjaan == '')) {
			// p‰ivit‰ ker‰tyt formi
			$formi	= "rivit";
			$kentta	= "keraajanro";

			$otsik_row = array();
			$keraysklontti = FALSE;

			if ($yhtiorow['kerayserat'] == 'K' and $toim == "") {
				$query = "	SELECT lasku.varasto, GROUP_CONCAT(DISTINCT lasku.tunnus SEPARATOR ', ') AS 'tilaukset'
							FROM kerayserat
							JOIN lasku ON (lasku.yhtio = kerayserat.yhtio AND lasku.tunnus = kerayserat.otunnus)
							WHERE kerayserat.yhtio = '{$kukarow['yhtio']}'
							AND kerayserat.nro = '{$id}'
							GROUP BY 1";
				$testresult = pupe_query($query);
				$testrow = mysql_fetch_assoc($testresult);

				$lp_varasto = $testrow["varasto"];
				$tilausnumeroita = $testrow['tilaukset'];
			}
			else {
				$query = "	SELECT kerayslista, varasto
							FROM lasku
							WHERE yhtio	= '{$kukarow['yhtio']}'
							AND tunnus	= '{$id}'";
				$testresult = pupe_query($query);
				$testrow = mysql_fetch_assoc($testresult);

				// Koko klˆntti kuuluu aina samaan varastoon joten otetaan t‰m‰ t‰ss‰ talteen
				$lp_varasto = $testrow["varasto"];

				if ($testrow['kerayslista'] > 0) {
					//haetaan kaikki t‰lle klˆntille kuuluvat otsikot
					$query = "	SELECT GROUP_CONCAT(DISTINCT tunnus ORDER BY tunnus SEPARATOR ',') tunnukset
								FROM lasku
								WHERE yhtio		= '$kukarow[yhtio]'
								and kerayslista	= '$id'
								and kerayslista != 0
								and tila		in ($tila)
								$tilaustyyppi
								HAVING tunnukset is not null";
					$toimresult = pupe_query($query);

					//jos rivej‰ lˆytyy niin tiedet‰‰n, ett‰ t‰m‰ on ker‰ysklˆntti
					if (mysql_num_rows($toimresult) > 0) {
						$toimrow = mysql_fetch_assoc($toimresult);
						$tilausnumeroita = $toimrow["tunnukset"];
						$keraysklontti = true;
					}
					else {
						$tilausnumeroita = $id;
					}
				}
				else {
					$tilausnumeroita = $id;
				}
			}

 			echo "<table>";

			if ($toim == 'SIIRTOLISTA')	 {
				echo "<tr><th align='left'>",t("Siirtolista"),"</th><td>{$id}</td></tr>";
			}
			if ($toim == 'MYYNTITILI')	 {
				echo "<tr><th align='left'>",t("Myyntitili"),"</th><td>{$id}</td></tr>";
			}
			else {
				if ($toim == "VASTAANOTA_REKLAMAATIO") {
					$alatilareklamaatio = 'C';
				}
				else {
					$alatilareklamaatio = 'A';
				}

 				$query = "	SELECT
 							lasku.*,
 							toimitustapa.tulostustapa,
 							toimitustapa.nouto
 							FROM lasku
							LEFT JOIN toimitustapa ON (lasku.yhtio = toimitustapa.yhtio and lasku.toimitustapa = toimitustapa.selite)
							WHERE lasku.tunnus in ({$tilausnumeroita})
							and lasku.yhtio = '{$kukarow['yhtio']}'
							and lasku.tila in ({$tila})
							and lasku.alatila = '{$alatilareklamaatio}'";
				$result = pupe_query($query);
				$otsik_row = mysql_fetch_assoc($result);

				echo "<tr><th>",t("Tilaus"),"</th><th>",t("Ostaja"),"</th><th>",t("Toimitusosoite"),"</th></tr>";

				if ($yhtiorow['kerayserat'] == 'K' and $toim == "") {

					mysql_data_seek($result, 0);

					while ($otsik_row = mysql_fetch_assoc($result)) {
						echo "<tr>";
						echo "<td>{$otsik_row['tunnus']}<br />{$otsik_row['clearing']}</td>";
						echo "<td>{$otsik_row['nimi']} {$otsik_row['nimitark']}<br />{$otsik_row['osoite']}<br />{$otsik_row['postino']} {$otsik_row['postitp']}<br />",maa($otsik_row["maa"]),"</td>";
						echo "<td>{$otsik_row['toim_nimi']} {$otsik_row['toim_nimitark']}<br />{$otsik_row['toim_osoite']}<br />{$otsik_row['toim_postino']} {$otsik_row['toim_postitp']}<br />",maa($otsik_row["toim_maa"]),"</td>";
						echo "</tr>";
					}

				}
				else {

					echo "<tr><td>{$tilausnumeroita}<br>{$otsik_row['clearing']}";

					if ($toim == 'VASTAANOTA_REKLAMAATIO') {
						echo "<br><form action='tilaus_myynti.php' method='POST'>";
						echo "<input type='hidden' name='tee' value = 'AKTIVOI'>";
						echo "<input type='hidden' name='toim' value = 'REKLAMAATIO'>";
						echo "<input type='hidden' name='tilausnumero' value = '{$tilausnumeroita}'>";
						echo "<input type='hidden' name='mista' value = 'keraa'>";
						echo "<input type='submit' value='",t("Muokkaa"),"'/> ";
						echo "</form>";
					}
					echo "</td>";
					echo "<td>{$otsik_row['nimi']} {$otsik_row['nimitark']}<br />{$otsik_row['osoite']}<br />{$otsik_row['postino']} {$otsik_row['postitp']}<br />",maa($otsik_row["maa"]),"</td>";
					echo "<td>{$otsik_row['toim_nimi']} {$otsik_row['toim_nimitark']}<br />{$otsik_row['toim_osoite']}<br />{$otsik_row['toim_postino']} {$otsik_row['toim_postitp']}<br />",maa($otsik_row["toim_maa"]),"</td></tr>";
				}
			}

			echo "</table>";

			$select_lisa 	= "tilausrivi.tilkpl, tilausrivi.varattu, tilausrivi.jt,";
			$where_lisa 	= "";
			$pjat_sortlisa 	= "";

			if ($toim == "VALMISTUS") {
				$sorttauskentta = generoi_sorttauskentta($yhtiorow["valmistus_kerayslistan_jarjestys"]);
				$order_sorttaus = $yhtiorow["valmistus_kerayslistan_jarjestys_suunta"];

				if ($yhtiorow["valmistus_kerayslistan_palvelutjatuottet"] == "E") $pjat_sortlisa = "tuotetyyppi,";

				// Summataan rivit yhteen (HUOM: unohdetaan kaikki perheet!)
				if ($yhtiorow["valmistus_kerayslistan_jarjestys"] == "S") {
					$select_lisa = "sum(tilausrivi.tilkpl) tilkpl, sum(tilausrivi.varattu) varattu, sum(tilausrivi.jt) jt, group_concat(tilausrivi.tunnus) rivitunnukset,";
					$where_lisa = "GROUP BY tilausrivi.tuoteno, tilausrivi.hyllyalue, tilausrivi.hyllyvali, tilausrivi.hyllyalue, tilausrivi.hyllynro";
				}
			}
			else {
				$sorttauskentta = generoi_sorttauskentta($yhtiorow["kerayslistan_jarjestys"]);
				$order_sorttaus = $yhtiorow["kerayslistan_jarjestys_suunta"];

				if ($yhtiorow["kerayslistan_palvelutjatuottet"] == "E") $pjat_sortlisa = "tuotetyyppi,";

				// Summataan rivit yhteen (HUOM: unohdetaan kaikki perheet!)
				if ($yhtiorow["kerayslistan_jarjestys"] == "S") {
					$select_lisa = "sum(tilausrivi.tilkpl) tilkpl, sum(tilausrivi.varattu) varattu, sum(tilausrivi.jt) jt, group_concat(tilausrivi.tunnus) rivitunnukset,";
					$where_lisa = "GROUP BY tilausrivi.tuoteno, tilausrivi.hyllyalue, tilausrivi.hyllyvali, tilausrivi.hyllyalue, tilausrivi.hyllynro";
				}
			}

			$asiakas_join_lisa = "";

			// Jos ker‰yser‰t k‰ytˆss‰, pit‰‰ hakea asiakkaankin tiedot (myyntipuolella toim="")
			if ($yhtiorow['kerayserat'] != '' and $toim == "") {
				$select_lisa .= "asiakas.kerayserat,";
				$asiakas_join_lisa = "JOIN asiakas ON (asiakas.yhtio = lasku.yhtio AND asiakas.tunnus = lasku.liitostunnus)";
			}

			$query = "	SELECT
						concat_ws(' ',tilausrivi.tuoteno, tilausrivi.nimitys) tuoteno,
						tilausrivi.tuoteno puhdas_tuoteno,
						tilausrivi.hyllyalue hyllyalue,
						tilausrivi.hyllynro hyllynro,
						tilausrivi.hyllyvali hyllyvali,
						tilausrivi.hyllytaso hyllytaso,
						concat_ws(' ',tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso) varastopaikka,
						concat_ws('###',tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso) varastopaikka_rekla,
						tuote.ei_saldoa,
						tuote.sarjanumeroseuranta,
						tilausrivi.keratty,
						tilausrivi.tunnus,
						tilausrivi.var,
						lasku.jtkielto,
						$select_lisa
						$sorttauskentta,
						if (tuote.tuotetyyppi='K','2 Tyˆt','1 Muut') tuotetyyppi
						FROM tilausrivi
						JOIN tuote ON tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno
						JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
						$asiakas_join_lisa
						WHERE tilausrivi.yhtio	= '$kukarow[yhtio]'
						and tilausrivi.otunnus in ($tilausnumeroita)
						and tilausrivi.var in ('', 'H' $var_lisa)
						and tilausrivi.tyyppi in ($tyyppi)
						and tilausrivi.kerattyaika = '0000-00-00 00:00:00'
						$where_lisa
						ORDER BY $pjat_sortlisa sorttauskentta $order_sorttaus, tilausrivi.tunnus";
			$result = pupe_query($query);
			$riveja = mysql_num_rows($result);

			if ($riveja > 0) {

				$row_chk = mysql_fetch_assoc($result);
				mysql_data_seek($result, 0);

				if ($yhtiorow['kerayserat'] == 'P' or ($yhtiorow['kerayserat'] == 'A' and $row_chk['kerayserat'] == 'A')) {
					echo "<form name = 'pakkaukset' method='post' autocomplete='off'>";
					echo "	<input type='hidden' name='tee' value='PAKKAUKSET'>
							<input type='hidden' name='toim' value='{$toim}'>
							<input type='hidden' name='id'  value='{$id}'>
							<input type='hidden' name='kerayserat_asiakas_chk' value='{$row_chk['kerayserat']}' />";

					$query = "	SELECT *
								FROM pakkaus
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND paino != 0
								ORDER BY paino ASC";
					$pakkausres = pupe_query($query);

					if (mysql_num_rows($pakkausres) > 0) {
						echo "<br />";
						echo "<table><tr>";
						echo "<th>",t("Pakkaus"),"</th>";
						echo "<td><select name='pakkaukset_kaikille' onchange='submit();'>";

						echo "<option value=''>",t("Valitse pakkaus kaikille riveille"),"</option>";

						// kaikilla pit‰isi olla sama pakkaus, joten pre-selectoidaan se
						$query = "	SELECT pakkaus
									FROM kerayserat
									WHERE yhtio = '{$kukarow['yhtio']}'
									AND otunnus IN ($tilausnumeroita)";
						$ker_pak_chk_res = pupe_query($query);
						$ker_pak_chk_row = mysql_fetch_assoc($ker_pak_chk_res);

						if (!isset($pakkaukset_kaikille) and $ker_pak_chk_row['pakkaus'] != 0) $pakkaukset_kaikille = $ker_pak_chk_row['pakkaus'];

						while ($pakkausrow = mysql_fetch_assoc($pakkausres)) {

							$sel = (isset($pakkaukset_kaikille) and $pakkaukset_kaikille == $pakkausrow['tunnus']) ? " selected" : "";

							echo "<option value='{$pakkausrow['tunnus']}'{$sel}>{$pakkausrow['pakkaus']} {$pakkausrow['pakkauskuvaus']}</option>";
						}

						echo "</select></td>";
						echo "</tr></table>";
					}

					echo "</form>";
				}

				echo "<form name = 'rivit' method='post' autocomplete='off'>";
				echo "	<input type='hidden' name='tee' value='P'>
						<input type='hidden' name='toim' value='$toim'>
						<input type='hidden' name='id'  value='$id'>";

				echo "<br>";
				echo "<table>";
				echo "<tr><th>".t("Ker‰‰j‰")."</th><td><input type='text' size='5' name='keraajanro'> ".t("tai")." ";
				echo "<select name='keraajalist'>";

				$query = "	SELECT *
							from kuka
							where yhtio = '$kukarow[yhtio]'
							and extranet = ''
							and (keraajanro > 0 or kuka = '$kukarow[kuka]')";
				$kuresult = pupe_query($query);

				while ($kurow = mysql_fetch_assoc($kuresult)) {

					$selker = "";

					if ($keraajalist == "" and $kurow["kuka"] == $kukarow["kuka"]) {
						$selker = "SELECTED";
					}
					elseif ($keraajalist == $kurow["kuka"]) {
						$selker = "SELECTED";
					}

					echo "<option value='$kurow[kuka]' $selker>$kurow[nimi]</option>";
				}

				echo "</select></td></tr>";

				if ($otsik_row['pakkaamo'] > 0 and $yhtiorow['pakkaamolokerot'] != '') {
					$query = "	SELECT nimi, lokero
								FROM pakkaamo
								WHERE yhtio = '$kukarow[yhtio]'
								AND tunnus = '$otsik_row[pakkaamo]'";
					$lokero_chk_res = pupe_query($query);

					if (mysql_num_rows($lokero_chk_res) > 0) {
						$lokero_chk_row = mysql_fetch_assoc($lokero_chk_res);
						echo "<tr><th>".t("Pakkaamo")."</th><td>$lokero_chk_row[nimi]</td></tr><tr><th>".t("Lokero")."</th><td>$lokero_chk_row[lokero]</td></tr>";
					}
				}

				if ($otsik_row["tulostustapa"] != "X" and $yhtiorow['karayksesta_rahtikirjasyottoon'] == 'H' and $keraysklontti === FALSE) {
					echo "<tr><th>".t("Siirry rahtikirjan syˆttˆˆn")."</th><td><input type='checkbox' name='rahtikirjalle'>".t("Kyll‰")."</td></tr>";
				}

				echo "</table><br>";

				echo "<table>
						<tr>
						<th>".t("Varastopaikka")."</th>
						<th>".t("Tuoteno")."</th>
						<th>".t("M‰‰r‰")."</th>
						<th>".t("Poikkeava m‰‰r‰")."</th>";

				$colspanni = 4;

				if ($yhtiorow['kerayserat'] == 'P' or ($yhtiorow['kerayserat'] == 'A' and $row_chk['kerayserat'] == 'A')) {
					echo "<th>",t("Pakkaus"),"</th>";
					$colspanni++;
				}

				if ($yhtiorow["kerayspoikkeama_kasittely"] != '') {
					echo "<th>".t("Poikkeaman k‰sittely")."</th>";
					$colspanni++;
				}

				echo "</tr>";

				$i = 0;
				$oslappkpl 	= 0;

				while ($row = mysql_fetch_assoc($result)) {

					if ($row['var'] == 'P') {
						// jos kyseess‰ on puuterivi
						$puute 			= t("PUUTE");
						$row['varattu']	= $row['tilkpl'];
					}
					elseif ($row['var'] == 'J') {
						// jos kyseess‰ on JT-rivi
						$puute 			= t("**JT**");

						if ($yhtiorow["varaako_jt_saldoa"] == "") {
							$row['varattu']	= $row['jt'];
						}
						else {
							$row['varattu']	= $row['jt']+$row['varattu'];
						}
					}
					elseif ($row['var']=='H') {
						// jos kyseess‰ on v‰kisinhyv‰ksytty-rivi
						$puute 			= "...........";
					}
					else {
						$puute			= '';
						$ker			= '';
					}

					$poikkeava_maara_disabled = "";

					if ($otsik_row["mapvm"] != '' and $otsik_row["mapvm"] != '0000-00-00') {
						$row["varattu"] = $row["tilkpl"];
						$poikkeava_maara_disabled = "disabled";
						$puute .= " ".t("Verkkokaupassa etuk‰teen maksettu tuote!");
					}

					// Reklamaation m‰‰r‰t lyˆd‰‰n lukkoon "vastaanota reklamaatio" vaiheessa
					if ($toim == 'VASTAANOTA_REKLAMAATIO') {
						$poikkeava_maara_disabled = "disabled";
					}

					if ($row['ei_saldoa'] != '') {
						echo "	<tr class='aktiivi'>
								<td>*</td>
								<td>$row[tuoteno]</td>
								<td>$row[varattu]</td>
								<td>".t("Saldoton tuote")."</td>";

						if ($yhtiorow['kerayserat'] == 'P' or ($yhtiorow['kerayserat'] == 'A' and $row['kerayserat'] == 'A')) {
							echo "<td></td>";
						}

						if ($yhtiorow["kerayspoikkeama_kasittely"] != '') {
							echo "<td></td>";
						}

						echo "</tr>";

						if ((($toim == "VALMISTUS" and $yhtiorow["valmistus_kerayslistan_jarjestys"] == "S") or $yhtiorow["kerayslistan_jarjestys"] == "S") and strpos($row["rivitunnukset"], ",") !== FALSE) {
							foreach (explode(",", $row["rivitunnukset"]) as $tunn) {
								$tunn = trim($tunn);
								echo "<input type='hidden' name='kerivi[]' value='$tunn'>";
							}
						}
						else {
							echo "<input type='hidden' name='kerivi[]' value='$row[tunnus]'><input type='hidden' name='maara[$row[tunnus]]' value=''>";
						}
					}
					else {
						echo "<tr class='aktiivi'>";
						echo "<td>";

						if ($toim == 'VASTAANOTA_REKLAMAATIO') {

							if (!isset($rekla_hyllyalue[$row["tunnus"]])) $rekla_hyllyalue[$row["tunnus"]] = "";
							if (!isset($rekla_hyllynro[$row["tunnus"]]))  $rekla_hyllynro[$row["tunnus"]]  = "";
							if (!isset($rekla_hyllyvali[$row["tunnus"]])) $rekla_hyllyvali[$row["tunnus"]] = "";
							if (!isset($rekla_hyllytaso[$row["tunnus"]])) $rekla_hyllytaso[$row["tunnus"]] = "";

							$query = "	SELECT hyllyalue, hyllynro, hyllyvali, hyllytaso,
										concat_ws(' ',hyllyalue, hyllynro, hyllyvali, hyllytaso) varastopaikka,
										concat_ws('###',hyllyalue, hyllynro, hyllyvali, hyllytaso) varastopaikka_rekla,
										concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0')) sorttauskentta
										FROM tuotepaikat
										WHERE yhtio	= '$kukarow[yhtio]'
										and tuoteno = '$row[puhdas_tuoteno]'
										order by oletus desc, sorttauskentta";
							$results2 = pupe_query($query);

							echo "<select name='varastorekla[$row[tunnus]]'>";

							while ($rivi = mysql_fetch_assoc($results2)) {
								$sel = '';
								if (trim($row['varastopaikka_rekla']) == trim($rivi['varastopaikka_rekla'])) {
									$sel = "SELECTED";
								}
								echo "<option value='$rivi[varastopaikka_rekla]' $sel>$rivi[varastopaikka]</option>";
							}

							echo "</select><br />";
							echo "<input type='text' size='5' name='rekla_hyllyalue[$row[tunnus]]' value = '{$rekla_hyllyalue[$row["tunnus"]]}'>
	                              <input type='text' size='5' name='rekla_hyllynro[$row[tunnus]]'  value = '{$rekla_hyllynro[$row["tunnus"]]}'>
	                              <input type='text' size='5' name='rekla_hyllyvali[$row[tunnus]]' value = '{$rekla_hyllyvali[$row["tunnus"]]}'>
	                              <input type='text' size='5' name='rekla_hyllytaso[$row[tunnus]]' value = '{$rekla_hyllytaso[$row["tunnus"]]}'>";
						}
						else {
							echo "$row[varastopaikka]";
						}

						echo "<input type='hidden' name='vertaus_hylly[$row[tunnus]]' value='$row[varastopaikka_rekla]'>";
						echo "</td>";
						echo "<td>$row[tuoteno] <input type='hidden' name='rivin_tuoteno[$row[tunnus]]' value='$row[tuoteno]'> <input type='hidden' name='rivin_puhdas_tuoteno[$row[tunnus]]' value='$row[puhdas_tuoteno]'></td>";
						echo "<td id='{$row['tunnus']}_varattu'>$row[varattu] <input type='hidden' name='rivin_varattu[$row[tunnus]]' value='$row[varattu]'> </td>";
						echo "<td>";

						//	kaikki gruupatut tunnukset mukaan!
						if ((($toim == "VALMISTUS" and $yhtiorow["valmistus_kerayslistan_jarjestys"] == "S") or $yhtiorow["kerayslistan_jarjestys"] == "S") and strpos($row["rivitunnukset"], ",") !== FALSE) {
							foreach (explode(",", $row["rivitunnukset"]) as $tunn) {
								$tunn = trim($tunn);
								echo "<input type='hidden' name='kerivi[]' value='$tunn'>";
							}
						}
						else {
							if ($yhtiorow['kerayserat'] == 'K' and $toim == "") {
								echo "<span id='maaran_paivitys_{$row['tunnus']}'></span>";

								$query = "	SELECT sum(kpl) kpl
											FROM kerayserat
											WHERE yhtio 	= '{$kukarow['yhtio']}'
											AND nro 		= '$id'
											AND tilausrivi 	= '{$row['tunnus']}'
											ORDER BY pakkausnro ASC";
								$keraysera_res = pupe_query($query);
								$keraysera_row = mysql_fetch_assoc($keraysera_res);

								// Katotaan jo t‰ss‰ vaiheessa onko er‰ss‰ eri m‰‰r‰ kuin tilausrivill‰.
								// M‰‰r‰ voi olla eri, koska ker‰yseriin menee vain kokonaislukuja ja tilausrivill‰ voi olla desimaalilukuja
								$erapoikkeamamaara = "";

								if ($row["varattu"] != (float) $keraysera_row['kpl']) {
									$erapoikkeamamaara = (float) $keraysera_row['kpl'];
								}

								echo "<input type='hidden' name='maara[$row[tunnus]]' id='maara_{$row['tunnus']}' value='$erapoikkeamamaara' />";
							}
							else {
								if (!isset($maara[$i])) $maara[$i] = "";

								if ($poikkeava_maara_disabled != "") {
									echo "<input type='hidden' name='maara[$row[tunnus]]' value=''>";
								}
								else {
									echo "<input type='text' size='4' name='maara[$row[tunnus]]' value='$maara[$i]'>";
								}
								echo $puute;
							}
							echo "<input type='hidden' name='kerivi[]' value='$row[tunnus]'>";
						}

						if ($toim == 'SIIRTOTYOMAARAYS' or $toim == 'SIIRTOLISTA') {
							$tunken1 = "siirtorivitunnus";
							$tunken2 = "siirtorivitunnus";
						}
						elseif ($row["varattu"] < 0) {
							$tunken1 = "ostorivitunnus";
							$tunken2 = "myyntirivitunnus";
						}
						else {
							$tunken1 = "myyntirivitunnus";
							$tunken2 = "myyntirivitunnus";
						}

						if ($row["sarjanumeroseuranta"] == "S" or $row["sarjanumeroseuranta"] == "T" or $row["sarjanumeroseuranta"] == "U" or $row["sarjanumeroseuranta"] == "V") {

							$query = "	SELECT count(*) kpl, min(sarjanumero) sarjanumero
										from sarjanumeroseuranta
										where yhtio  = '$kukarow[yhtio]'
										and tuoteno  = '$row[puhdas_tuoteno]'
										and $tunken1 = '$row[tunnus]'";
							$sarjares = pupe_query($query);
							$sarjarow = mysql_fetch_assoc($sarjares);

							if ($sarjarow["kpl"] == abs($row["varattu"])) {
								echo " (<a href='sarjanumeroseuranta.php?tuoteno=".urlencode($row["puhdas_tuoteno"])."&$tunken2=$row[tunnus]&from=KERAA&aputoim=$toim&otunnus=$id#".urlencode($sarjarow["sarjanumero"])."' style='color:#00FF00;'>".t("S:nro OK")."</font></a>)";
							}
							else {
								echo " (<a href='sarjanumeroseuranta.php?tuoteno=".urlencode($row["puhdas_tuoteno"])."&$tunken2=$row[tunnus]&from=KERAA&aputoim=$toim&otunnus=$id#".urlencode($sarjarow["sarjanumero"])."'>".t("S:nro")."</a>)";
							}
						}
						elseif ($row["sarjanumeroseuranta"] == "E" or $row["sarjanumeroseuranta"] == "F" or $row["sarjanumeroseuranta"] == "G") {

							if ($row["sarjanumeroseuranta"] == "F") {
								$pepvmlisa1 = " sarjanumeroseuranta.parasta_ennen, ";
								$pepvmlisa2 = ", 17";
							}
							else {
								$pepvmlisa1 = "";
								$pepvmlisa2 = "";
							}

							$query = "	SELECT tuote.yhtio, tuote.tuoteno, tuote.ei_saldoa, varastopaikat.tunnus varasto, varastopaikat.tyyppi varastotyyppi, varastopaikat.maa varastomaa,
										tuotepaikat.oletus, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso,
										sarjanumeroseuranta.sarjanumero era,
										sarjanumeroseuranta.ostorivitunnus,
										$pepvmlisa1
										concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'),lpad(upper(tuotepaikat.hyllyvali), 5, '0'),lpad(upper(tuotepaikat.hyllytaso), 5, '0')) sorttauskentta,
										varastopaikat.nimitys, if (varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi
							 			FROM tuote
										JOIN tuotepaikat ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno
										JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
										and concat(rpad(upper(varastopaikat.alkuhyllyalue),  5, '0'),lpad(upper(varastopaikat.alkuhyllynro),  5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
										and concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'),lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
										JOIN sarjanumeroseuranta ON sarjanumeroseuranta.yhtio = tuote.yhtio
										and sarjanumeroseuranta.tuoteno = tuote.tuoteno
										and sarjanumeroseuranta.hyllyalue = tuotepaikat.hyllyalue
										and sarjanumeroseuranta.hyllynro  = tuotepaikat.hyllynro
										and sarjanumeroseuranta.hyllyvali = tuotepaikat.hyllyvali
										and sarjanumeroseuranta.hyllytaso = tuotepaikat.hyllytaso
										and sarjanumeroseuranta.myyntirivitunnus = 0
										and sarjanumeroseuranta.era_kpl != 0
										WHERE tuote.yhtio = '$kukarow[yhtio]'
										and tuote.tuoteno = '$row[puhdas_tuoteno]'
										GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16 $pepvmlisa2
										ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";
							$omavarastores = pupe_query($query);

							$paikat 	= "<option value=''>".t("Valitse er‰")."</option>";
							$selpaikka 	= "";

							$query	= "	SELECT sarjanumeroseuranta.sarjanumero era, sarjanumeroseuranta.parasta_ennen
					   					FROM sarjanumeroseuranta
					   					WHERE yhtio = '$kukarow[yhtio]'
										and tuoteno = '$row[puhdas_tuoteno]'
					   					and $tunken1 = '$row[tunnus]'
					   					LIMIT 1";
					   		$sarjares = pupe_query($query);
					   		$sarjarow = mysql_fetch_assoc($sarjares);

							echo t("Er‰").": ";

							while ($alkurow = mysql_fetch_assoc($omavarastores)) {
								if ($alkurow["hyllyalue"] != "!!M" and
									($alkurow["varastotyyppi"] != "E" or
									$laskurow["varasto"] == $alkurow["varasto"] or
									($alkurow["hyllyalue"] == $row["hyllyalue"] and $alkurow["hyllynro"] == $row["hyllynro"] and $alkurow["hyllyvali"] == $row["hyllyvali"] and $alkurow["hyllytaso"] == $row["hyllytaso"]))) {

									if ($yhtiorow["saldo_kasittely"] == "T") {
										$saldoaikalisa = date("Y-m-d");
									}
									else {
										$saldoaikalisa = "";
									}

									list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($row["puhdas_tuoteno"], '', '', '', $alkurow["hyllyalue"], $alkurow["hyllynro"], $alkurow["hyllyvali"], $alkurow["hyllytaso"], $laskurow["toim_maa"], $saldoaikalisa, $alkurow["era"]);

									$myytavissa = (float) $myytavissa;

									//Jos er‰ on keksitty k‰sin t‰‰lt‰ ker‰yksest‰
									$query = "	SELECT tyyppi, (varattu+kpl+jt) kpl, tunnus, laskutettu
												FROM tilausrivi
												WHERE yhtio = '$kukarow[yhtio]'
												and tuoteno = '$row[puhdas_tuoteno]'
												and tunnus	= '$alkurow[ostorivitunnus]'";
									$lisa_res = pupe_query($query);
									$lisa_row = mysql_fetch_assoc($lisa_res);

									// varmistetaan, ett‰ t‰m‰ er‰ on k‰ytett‰viss‰, eli ostorivitunnus pointtaa ostoriviin, hyvitysriviin tai laskutettuun myyntiriviin tai t‰h‰n riviin itsess‰‰n
									if (($lisa_row["tyyppi"] == "O" or $lisa_row["kpl"] < 0 or $lisa_row["laskutettu"] != "" or $lisa_row["tunnus"] == $row["tunnus"]) and
										(in_array($yhtiorow["puute_jt_oletus"], array('H','O')) or
										$myytavissa >= $row["varattu"] or
										($row["var"] != "P"
										and $alkurow["hyllyalue"] == $row["hyllyalue"]
										and $alkurow["hyllynro"] == $row["hyllynro"]
										and $alkurow["hyllyvali"] == $row["hyllyvali"]
										and $alkurow["hyllytaso"] == $row["hyllytaso"]
										and $sarjarow["era"] == $alkurow["era"]))) {

										$sel = "";

										if ($sarjarow["era"] == $alkurow["era"] and !in_array($row["var"], array("P","S")) and $alkurow["hyllyalue"] == $row["hyllyalue"] and $alkurow["hyllynro"] == $row["hyllynro"] and $alkurow["hyllyvali"] == $row["hyllyvali"] and $alkurow["hyllytaso"] == $row["hyllytaso"]) {
											$sel = "SELECTED";

											$selpaikka = "$alkurow[hyllyalue]#$alkurow[hyllynro]#$alkurow[hyllyvali]#$alkurow[hyllytaso]#$alkurow[era]";
										}
										elseif (isset($_POST) and $_POST["era_new_paikka"][$row["tunnus"]] == "$alkurow[hyllyalue]#$alkurow[hyllynro]#$alkurow[hyllyvali]#$alkurow[hyllytaso]#$alkurow[era]") {
											$sel = "SELECTED";

											$selpaikka = "$alkurow[hyllyalue]#$alkurow[hyllynro]#$alkurow[hyllyvali]#$alkurow[hyllytaso]#$alkurow[era]";
										}

										$paikat .= "<option value='$alkurow[hyllyalue]#$alkurow[hyllynro]#$alkurow[hyllyvali]#$alkurow[hyllytaso]#$alkurow[era]' $sel>";

										if (strtoupper($alkurow['varastomaa']) != strtoupper($yhtiorow['maa'])) {
											$paikat .= strtoupper($alkurow['varastomaa'])." ";
										}

										$paikat .= "$alkurow[hyllyalue] $alkurow[hyllynro] $alkurow[hyllyvali] $alkurow[hyllytaso], $alkurow[era]";
										$paikat .= " ($myytavissa)";

										if ($row["sarjanumeroseuranta"] == "F") {
											$paikat .= " ".tv1dateconv($alkurow["parasta_ennen"]);
										}

										$paikat .= "</option>";
									}
								}
							}

							$subbari = " onchange='submit();'";

							if (($row["sarjanumeroseuranta"] == "E" or $row["sarjanumeroseuranta"] == "F" or $row["sarjanumeroseuranta"] == "G") and $yhtiorow["kerayspoikkeama_kasittely"] != '') {
								$subbari = "";
							}

							echo "<select name='era_new_paikka[$row[tunnus]]' $subbari>".$paikat."</select>";
							echo "<input type='hidden' name='era_old_paikka[$row[tunnus]]' value='$selpaikka'>";
							echo " (<a href='sarjanumeroseuranta.php?tuoteno=".urlencode($row["puhdas_tuoteno"])."&$tunken2=$row[tunnus]&from=KERAA&aputoim=$toim&otunnus=$id#".urlencode($sarjarow["sarjanumero"])."'>".t("E:nro")."</a>)";
						}

						echo "</td>";

						if ($yhtiorow['kerayserat'] == 'P' or ($yhtiorow['kerayserat'] == 'A' and $row['kerayserat'] == 'A')) {

							$query = "	SELECT *
										FROM kerayserat
										WHERE yhtio 	= '{$kukarow['yhtio']}'
										AND tilausrivi 	= '{$row['tunnus']}'";
							$keraysera_res = pupe_query($query);
							$keraysera_row = mysql_fetch_assoc($keraysera_res);

							$pakkauskirjain = chr(64+$keraysera_row['pakkausnro']);

							$oslappkpl = $yhtiorow["oletus_oslappkpl"] != 0 ? ($oslappkpl + 1) : 0;

							echo "<td><input type='text' size='4' name='keraysera_pakkaus[{$row['tunnus']}]' value='{$pakkauskirjain}' /></td>";
						}

						if ($yhtiorow["kerayspoikkeama_kasittely"] != '') {

							echo "<td><select name='poikkeama_kasittely[$row[tunnus]]'>";

							if ($row["sarjanumeroseuranta"] == "E" or $row["sarjanumeroseuranta"] == "F" or $row["sarjanumeroseuranta"] == "G") {
								$selpk_UR = "SELECTED";
							}
							elseif ($yhtiorow["kerayspoikkeama_kasittely"] == 'J') {

								if ($row["jtkielto"] == "o") {
									$selpk_PU = "SELECTED";
								}
								else {
									$selpk_JT = "SELECTED";
								}
							}
							else {
								echo "<option value='' SELECTED>".t("Ei k‰sitell‰")."</option>";
							}

							echo "<option value='JT' $selpk_JT>".t("JT")."</option>";
							echo "<option value='PU' $selpk_PU>".t("Puute")."</option>";

							if ($row["sarjanumeroseuranta"] == "E" or $row["sarjanumeroseuranta"] == "F" or $row["sarjanumeroseuranta"] == "G") {
								echo "<option value='UR' $selpk_UR>".t("Uusi rivi")."</option>";
							}

							echo "<option value='UT'>".t("Uusi tilaus")."</option>";
							echo "<option value='MI'>".t("Mit‰tˆi")."</option>";
							echo "</select></td>";
						}

						echo "</tr>";

						if ($yhtiorow['kerayserat'] == 'K' and $toim == "") {
							$query = "	SELECT *
										FROM kerayserat
										WHERE yhtio 	= '{$kukarow['yhtio']}'
										AND nro 		= '$id'
										AND tilausrivi 	= '{$row['tunnus']}'
										ORDER BY pakkausnro ASC";
							$keraysera_res = pupe_query($query);

							echo "<tr><td colspan='{$colspanni}'>";

							while ($keraysera_row = mysql_fetch_assoc($keraysera_res)) {
								echo chr((64+$keraysera_row['pakkausnro']))," <input type='text' name='keraysera_maara[{$keraysera_row['tunnus']}]' id='{$row['tunnus']}_{$keraysera_row['tunnus']}' value='".(float) $keraysera_row['kpl']."' size='4' />&nbsp;";
							}

							echo "</td></tr>";
						}
					}

					$i++;
				}

				// Jos kyseess‰ ei ole valmistus tulostetaan virallinen l‰hete
				$sel 		= "SELECTED";
				$lahetekpl  = 0;

				if ($toim != 'VALMISTUS' and $otsik_row["tila"] != 'V') {
					$oslappkpl 	= $oslappkpl != 0 ? $oslappkpl : $yhtiorow["oletus_oslappkpl"];
					$lahetekpl 	= $yhtiorow["oletus_lahetekpl"];
					$vakadrkpl	= $yhtiorow["oletus_lahetekpl"];
				}

				$spanni = 3;

				if ($yhtiorow['karayksesta_rahtikirjasyottoon'] != '') {
					$spanni = 4;
				}

				if ($yhtiorow["lahete_tyyppi_tulostus"] != '') {
					$spanni += 1;
				}

				if ($toim != 'VASTAANOTA_REKLAMAATIO' and ($otsik_row['pakkaamo'] == 0 or $yhtiorow['pakkaamolokerot'] == '')) {

					//tulostetaan faili ja valitaan sopivat printterit
					if ($lp_varasto == 0) {
						$query = "	SELECT *
									from varastopaikat
									where yhtio = '$kukarow[yhtio]'
									order by alkuhyllyalue,alkuhyllynro
									limit 1";
					}
					else {
						$query = "	SELECT *
									from varastopaikat
									where yhtio	= '$kukarow[yhtio]'
									and tunnus	= '$lp_varasto'
									order by alkuhyllyalue,alkuhyllynro";
					}
					$kirre = pupe_query($query);

					if (mysql_num_rows($kirre) > 0 and $yhtiorow['pakkaamolokerot'] == '') {

						$prirow = mysql_fetch_assoc($kirre);

						// k‰teinen muuttuja viritet‰‰n tilaus-valmis.inc:iss‰ jos maksuehto on k‰teinen
						// ja silloin pit‰‰ kaikki l‰hetteet tulostaa aina printteri5:lle (lasku printteri)
						if ($kateinen == 'X') {
							$sel_lahete[$prirow['printteri5']] = "SELECTED";	// laskuprintteri
							$sel_oslapp[$prirow['printteri5']] = "SELECTED";	// osoitelappuprintteri
						}
						else {
							$sel_lahete[$prirow['printteri1']] = "SELECTED";	// laskuprintteri
							$sel_oslapp[$prirow['printteri3']] = "SELECTED";	// osoitelappuprintteri
						}
					}

					echo "<tr><th>".t("L‰hete").":</th><th colspan='$spanni'>";

					$query = "	SELECT *
								FROM kirjoittimet
								WHERE yhtio = '$kukarow[yhtio]'
								ORDER by kirjoitin";
					$kirre = pupe_query($query);

					echo "<select name='valittu_tulostin'>";
					echo "<option value=''>".t("Ei tulosteta")."</option>";

					while ($kirrow = mysql_fetch_assoc($kirre)) {
						$sel = (isset($sel_lahete[$kirrow["tunnus"]])) ? " selected" : "";

						echo "<option value='{$kirrow['tunnus']}'{$sel}>{$kirrow['kirjoitin']}</option>";
					}

					echo "</select> ".t("Kpl").": <input type='text' size='4' name='lahetekpl' value='$lahetekpl'>";

					if ($yhtiorow["lahete_tyyppi_tulostus"] != '') {
						echo " ".t("L‰hetetyyppi").": <select name='sellahetetyyppi'>";

						$lahetetyyppi = pupesoft_lahetetyyppi($id);

						$vresult = t_avainsana("LAHETETYYPPI");

						while($row = mysql_fetch_array($vresult)) {
							$sel = "";
							if ($row["selite"] == $lahetetyyppi) $sel = 'selected';

							echo "<option value='$row[selite]' $sel>$row[selitetark]</option>";
						}

						echo "</select>";
						echo "</th>";
					}

					if ($yhtiorow["kerayspoikkeama_kasittely"] != '') {
						echo "<th>&nbsp;</th>";
					}
					echo "</tr>";
				}

				if ($yhtiorow["vak_erittely"] == "K" and $yhtiorow["kerayserat"] == "K" and $toim == "") {
					echo "<tr>";
					echo "<th>".t("VAK/ADR-erittely").":</th>";
					echo "<th colspan='$spanni'>";

					$query = "	SELECT *
								FROM kirjoittimet
								WHERE yhtio = '{$kukarow["yhtio"]}'
								ORDER by kirjoitin";
					$kirre = pupe_query($query);

					echo "<select name='vakadr_tulostin'>";
					echo "<option value=''>".t("Ei tulosteta")."</option>";

					while ($kirrow = mysql_fetch_assoc($kirre)) {
						$sel = (isset($sel_lahete[$kirrow["tunnus"]])) ? " selected" : "";

						echo "<option value='{$kirrow['tunnus']}'{$sel}>{$kirrow['kirjoitin']}</option>";
					}

					echo "</select> ".t("Kpl").": <input type='text' size='4' name='vakadrkpl' value='$vakadrkpl'>";
					echo "</tr>";
				}

				if ($toim != 'VASTAANOTA_REKLAMAATIO' and $otsik_row['pakkaamo'] > 0 and $yhtiorow['pakkaamolokerot'] != '') {
					echo "<tr><th>".t("Kolli")."</th><th colspan='$spanni'><input type='text' name='pakkaamo_kolli' size='5'/></th>";

					if ($yhtiorow["kerayspoikkeama_kasittely"] != '') {
						echo "<th>&nbsp;</th>";
					}
					echo "</tr>";
				}

				echo "<tr>";

				if ($toim != 'VASTAANOTA_REKLAMAATIO' and ($yhtiorow['karayksesta_rahtikirjasyottoon'] == '' or $otsik_row["tulostustapa"] == "X") and ($otsik_row['pakkaamo'] == 0 or $yhtiorow['pakkaamolokerot'] == '')) {
					echo "<th>".t("Osoitelappu").":</th>";

					echo "<th colspan='$spanni'>";

					mysql_data_seek($kirre, 0);

					echo "<select name='valittu_oslapp_tulostin'>";
					echo "<option value=''>".t("Ei tulosteta")."</option>";

					while ($kirrow = mysql_fetch_assoc($kirre)) {
						$sel = (isset($sel_oslapp[$kirrow["tunnus"]])) ? " selected" : "";

						echo "<option value='$kirrow[tunnus]'{$sel}>$kirrow[kirjoitin]</option>";
					}

					echo "</select> ".t("Kpl").": ";

					$oslappkpl_hidden = 0;
					$disabled = '';

					if ($yhtiorow["oletus_oslappkpl"] != 0 and ($yhtiorow['kerayserat'] == 'P' or $yhtiorow['kerayserat'] == 'A')) {

						$kaikki_ok = true;

						if ($yhtiorow['kerayserat'] == 'A') {

							$query = "	SELECT kerayserat
										FROM asiakas
										WHERE yhtio = '{$kukarow['yhtio']}'
										AND tunnus = '{$otsik_row['liitostunnus']}'
										AND kerayserat = 'A'";
							$asiakas_chk_res = pupe_query($query);

							if (mysql_num_rows($asiakas_chk_res) == 0) $kaikki_ok = false;
						}

						if ($kaikki_ok) {
							$oslappkpl_hidden = 1;
							$oslappkpl = '';
							$disabled = 'disabled';
						}
					}

					echo "<input type='text' size='4' name='oslappkpl' value='$oslappkpl' {$disabled}>";

					if ($oslappkpl_hidden != 0) {
						echo "<input type='hidden' name='oslappkpl' value='{$oslappkpl_hidden}' />";
					}

					echo "</th>";

					if ($yhtiorow["kerayspoikkeama_kasittely"] != '') {
						echo "<th></th>";
					}
					echo "</tr>";
				}

				if ($otsik_row['pakkaamo'] > 0 and $yhtiorow['pakkaamolokerot'] != '') {
					echo "<th>".t("Rullakko")."</th><th colspan='$spanni'><input type='text' name='pakkaamo_rullakko' size='5'/></th>";

					if ($yhtiorow["kerayspoikkeama_kasittely"] != '') {
						echo "<th></th>";
					}
					echo "</tr>";
				}

				echo "</table><br>";

				echo "<input type='hidden' name='tilausnumeroita' id='tilausnumeroita' value='$tilausnumeroita'>";
				echo "<input type='hidden' name='lasku_yhtio' value='$otsik_row[yhtio]'>";

				if ($toim == 'VASTAANOTA_REKLAMAATIO') {
					echo "<input type='submit' name='real_submit' id='real_submit' value='".t("Tuotteet hyllytetty ja reklamaatio valmis laskutukseen")."'></form>";
				}
				elseif ($otsik_row["tulostustapa"] != "X" or $otsik_row["nouto"] != "") {
					echo "<input type='submit' name='real_submit' id='real_submit' value='".t("Merkkaa ker‰tyksi")."'></form>";
				}
				else {
					echo "<input type='submit' name='real_submit' id='real_submit' value='".t("Merkkaa toimitetuksi")."'></form>";
				}

				if ($otsik_row["tulostustapa"] != "X" and $yhtiorow['karayksesta_rahtikirjasyottoon'] == 'Y') {
					echo "<br><br><font class='message'>".t("Siirryt automaattisesti rahtikirjan syˆttˆˆn")."!</font>";
				}
				elseif ($otsik_row["tulostustapa"] != "X" and $yhtiorow['karayksesta_rahtikirjasyottoon'] == 'H' and $keraysklontti === FALSE) {
					echo "<br><br><font class='message'>".t("Voit halutessasi siirty‰ rahtikirjan syˆttˆˆn")."!</font>";
				}
			}
			else {
				echo t("T‰ll‰ tilauksella ei ole yht‰‰n ker‰tt‰v‰‰ rivi‰!");
			}
		}

		if (isset($rahtikirjaan) and $rahtikirjaan == 'mennaan') {
			echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL={$palvelin2}rahtikirja.php?toim=lisaa&id=$id&rakirno=$id&tunnukset=$tilausnumeroita&mista=keraa.php'>";
		}

		require ("inc/footer.inc");
	}
