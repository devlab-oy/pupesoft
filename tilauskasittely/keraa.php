<?php

	require ("../inc/parametrit.inc");

	if ($toim == "VASTAANOTA_REKLAMAATIO" and $yhtiorow['reklamaation_kasittely'] != 'U') {
		echo "<font class='error'>".t("HUOM: Ohjelma on k�yt�ss� vain kun k�ytet��n laajaa reklamaatioprosessia")."!</font>";
		exit;
	}

	$logistiikka_yhtio = '';
	$logistiikka_yhtiolisa = '';

	if (!isset($toim)) $toim = '';
	if (!isset($id)) $id = 0;
	if (!isset($tee)) $tee = '';
	if (!isset($jarj)) $jarj = '';
	if (!isset($etsi)) $etsi = '';
	if (!isset($tuvarasto)) $tuvarasto = '';
	if (!isset($tumaa)) $tumaa = '';
	if (!isset($tutoimtapa)) $tutoimtapa = '';
	if (!isset($tutyyppi)) $tutyyppi = '';
	if (!isset($rahtikirjaan)) $rahtikirjaan = '';
	if (!isset($sorttaus)) $sorttaus = '';

	if ($yhtiorow['konsernivarasto'] != '' and $konsernivarasto_yhtiot != '') {
		$logistiikka_yhtio = $konsernivarasto_yhtiot;
		$logistiikka_yhtiolisa = "yhtio in ($logistiikka_yhtio)";

		if (isset($lasku_yhtio) and $lasku_yhtio != '') {
			$kukarow['yhtio'] = mysql_real_escape_string($lasku_yhtio);

			$yhtiorow = hae_yhtion_parametrit($lasku_yhtio);
		}
	}
	else {
		$logistiikka_yhtiolisa = "yhtio = '$kukarow[yhtio]'";
	}

	js_popup();

	if ($yhtiorow['kerayserat'] == 'K' and trim($kukarow['keraysvyohyke']) != '' and $toim == "") {
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

	if (!isset($toim)) $toim = '';
	if (!isset($tee)) $tee = '';
	$keraysvirhe = 0;
	$virherivi   = 0;
	$muuttuiko	 = '';

	if ($toim == 'SIIRTOLISTA') {
		echo "<font class='head'>".t("Ker�� siirtolista").":</font><hr>";
		$tila = "'G'";
		$tyyppi = "'G'";
		$tilaustyyppi = " and tilaustyyppi!='M' ";
	}
	elseif ($toim == 'SIIRTOTYOMAARAYS') {
		echo "<font class='head'>".t("Ker�� sis�inen ty�m��r�ys").":</font><hr>";
		$tila = "'S'";
		$tyyppi = "'G'";
		$tilaustyyppi = " and tilaustyyppi='S' ";
	}
	elseif ($toim == 'MYYNTITILI') {
		echo "<font class='head'>".t("Ker�� myyntitili").":</font><hr>";
		$tila = "'G'";
		$tyyppi = "'G'";
		$tilaustyyppi = " and tilaustyyppi='M' ";
	}
	elseif ($toim == 'VALMISTUS') {
		echo "<font class='head'>".t("Ker�� valmistus").":</font><hr>";
		$tila = "'V'";
		$tyyppi = "'V','L'";
		$tilaustyyppi = "";
	}
	elseif ($toim == 'VALMISTUSMYYNTI') {
		echo "<font class='head'>".t("Ker�� tilaus tai valmistus").":</font><hr>";
		$tila = "'V','L'";
		$tyyppi = "'V','L'";
		$tilaustyyppi = "";
	}
	elseif ($toim == 'VASTAANOTA_REKLAMAATIO') {
		echo "<font class='head'>".t("Hyllyt� reklamaatio tai palautus").":</font><hr>";
		$tila 			= "'C'";
		$alatilarekla 	= "'C'";
		$tyyppi 		= "'L'";
		$tilaustyyppi 	= " and tilaustyyppi ='R'";
	}
	else {
		echo "<font class='head'>".t("Ker�� tilaus").":</font><hr>";
		$tila = "'L'";
		$tyyppi = "'L'";
		$tilaustyyppi = "";
	}

	if ($toim != '') {
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
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			$yhtiorow['karayksesta_rahtikirjasyottoon'] = '';
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

	if ($tee == 'P') {
		$query = "SELECT kerayslista from lasku where yhtio = '$kukarow[yhtio]' and tunnus = '$id'";
		$testresult = mysql_query($query) or pupe_error($query);
		$testrow = mysql_fetch_assoc($testresult);

		if ($testrow['kerayslista'] > 0) {
			//haetaan kaikki t�lle kl�ntille kuuluvat otsikot
			$query = "	SELECT GROUP_CONCAT(DISTINCT tunnus ORDER BY tunnus SEPARATOR ',') tunnukset
						FROM lasku
						WHERE yhtio		= '$kukarow[yhtio]'
						and kerayslista	= '$id'
						and kerayslista != 0
						and tila		in ($tila)
						$tilaustyyppi
						HAVING tunnukset is not null";
			$toimresult = mysql_query($query) or pupe_error($query);

			//jos rivej� l�ytyy niin tiedet��n, ett� t�m� on ker�yskl�ntti
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

		// katotaan aluks onko yht��n tuotetta sarjanumeroseurannassa t�ll� ker�yslistalla
		$query = "	SELECT tilausrivi.tunnus, tilausrivi.tuoteno, tilausrivi.varattu, tuote.sarjanumeroseuranta
					FROM tilausrivi use index (yhtio_otunnus)
					JOIN tuote on tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.sarjanumeroseuranta!=''
					WHERE tilausrivi.yhtio='$kukarow[yhtio]' and
					tilausrivi.otunnus in ($tilausnumeroita) and
					tilausrivi.tyyppi in ('L','G')
					and tilausrivi.var not in ('P','T','U','J')";
		$toimresult = mysql_query($query) or pupe_error($query);

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
					$sarjares2 = mysql_query($query) or pupe_error($query);
					$sarjarow = mysql_fetch_assoc($sarjares2);

					if ($sarjarow["kpl"] != abs($toimrow["varattu"])) {
						echo "<font class='error'>".t("Sarjanumeroseurannassa oleville tuotteille on liitett�v� sarjanumero ennen ker�yst�")."! ".t("Tuote").": $toimrow[tuoteno].</font><br>";
						$keraysvirhe++;
					}
				}
				else {

					//Siivotaan hieman k�ytt�j�n sy�tt�m�� kappalem��r��
					$eratsekkpl = (float) str_replace( ",", ".", $maara[$toimrow["tunnus"]]);

					// Muutetaanko er�seurattavan tuotteen kappalem��r��
					if (trim($maara[$toimrow["tunnus"]]) != '' and $eratsekkpl >= 0) {
						if ($eratsekkpl < $toimrow["varattu"]) {
							//Jos er� on keksitty k�sin t��lt� ker�yksest�
							$query = "	SELECT *
										FROM sarjanumeroseuranta
										WHERE yhtio = '$kukarow[yhtio]'
										and tuoteno = '$toimrow[tuoteno]'
										and myyntirivitunnus = '$toimrow[tunnus]'
										and ostorivitunnus 	 = '$toimrow[tunnus]'";
							$lisa_res = mysql_query($query) or pupe_error($query);

							if (mysql_num_rows($lisa_res) == 1) {
								$lisa_row = mysql_fetch_assoc($lisa_res);

								$query = "	UPDATE sarjanumeroseuranta
											SET era_kpl	= '$eratsekkpl'
											WHERE yhtio 		 = '$kukarow[yhtio]'
											and tuoteno 		 = '$toimrow[tuoteno]'
											and ostorivitunnus 	 = '$lisa_row[myyntirivitunnus]'
											and myyntirivitunnus = 0";
								$lisa_res = mysql_query($query) or pupe_error($query);
							}

							$keraysvirhe++;
						}
						elseif ($eratsekkpl < $toimrow["varattu"]) {
							$query = "	DELETE FROM sarjanumeroseuranta
										WHERE yhtio = '$kukarow[yhtio]'
										and tuoteno = '$toimrow[tuoteno]'
										and myyntirivitunnus = '$toimrow[tunnus]'";
							$sarjares2 = mysql_query($query) or pupe_error($query);
							$keraysvirhe++;
						}
					}
					else {
						$eratsekkpl = $toimrow["varattu"];
					}

					// P�ivitet��n er�
					if ($era_new_paikka[$toimrow["tunnus"]] != $era_old_paikka[$toimrow["tunnus"]]) {

						list($myy_hyllyalue, $myy_hyllynro, $myy_hyllyvali, $myy_hyllytaso, $myy_era) = explode("#", $era_new_paikka[$toimrow["tunnus"]]);

						$query = "	DELETE FROM sarjanumeroseuranta
									WHERE yhtio = '$kukarow[yhtio]'
									and tuoteno = '$toimrow[tuoteno]'
									and myyntirivitunnus = '$toimrow[tunnus]'";
						$sarjares2 = mysql_query($query) or pupe_error($query);

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
								$lisa_res = mysql_query($query) or pupe_error($query);

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
							$lisa_res = mysql_query($query) or pupe_error($query);

							$query = "	UPDATE tilausrivi
										SET hyllyalue   = '$myy_hyllyalue',
										hyllynro    	= '$myy_hyllynro',
										hyllytaso   	= '$myy_hyllytaso',
										hyllyvali   	= '$myy_hyllyvali'
										WHERE yhtio 	= '$kukarow[yhtio]'
										and tunnus		= '$toimrow[tunnus]'";
							$lisa_res = mysql_query($query) or pupe_error($query);
						}
					}

					if ($eratsekkpl != 0) {
						$query = "	SELECT count(*) kpl
									FROM sarjanumeroseuranta
									WHERE yhtio = '$kukarow[yhtio]'
									and tuoteno = '$toimrow[tuoteno]'
									and $tunken = '$toimrow[tunnus]'";
						$sarjares2 = mysql_query($query) or pupe_error($query);
						$sarjarow = mysql_fetch_assoc($sarjares2);

						if ($sarjarow["kpl"] != 1) {
							echo "<font class='error'>".t("Er�numeroseurannassa oleville tuotteille on liitett�v� er�numero ennen ker�yst�")."! ".t("Tuote").": $toimrow[tuoteno].</font><br>";
							$keraysvirhe++;
						}
					}
				}
			}
		}

		// Tarkistetaan sy�tetyt varastopaikat
		if ($toim == 'VASTAANOTA_REKLAMAATIO') {
			for ($a=0; $a < count($kerivi); $a++) {
				// varastorekla on dropdown ja vertaushylly on kannasta
				if ((trim($varastorekla[$kerivi[$a]]) == trim($vertaus_hylly[$kerivi[$a]])) and $rekla_hyllyalue[$kerivi[$a]] != '' and $rekla_hyllynro[$kerivi[$a]] != '') {
					if (kuuluukovarastoon($rekla_hyllyalue[$kerivi[$a]], $rekla_hyllynro[$kerivi[$a]], '') == 0) {
						echo "<font class='error'>".t("VIRHE: Tuotenumerolle")." ".$rivin_tuoteno[$kerivi[$a]]." ".t("annettu paikka")." ".$rekla_hyllyalue[$kerivi[$a]]."-".$rekla_hyllynro[$kerivi[$a]]."-".$rekla_hyllyvali[$kerivi[$a]]."-".$rekla_hyllytaso[$kerivi[$a]]." ".t("ei kuulu mihink��n varastoon")."!</font><br>";
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

		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			echo "<font class='error'>".t("VIRHE: Ker��j�� %s ei l�ydy", "", $keraajanro)."!</font><br><br>";
			$keraysvirhe++;
		}
		else {
			$keraaja = mysql_fetch_assoc($result);
			$who = $keraaja['kuka'];

			for ($i=0; $i < count($kerivi); $i++) {

				$query1 = "	SELECT if (kerattyaika='0000-00-00 00:00:00', 'keraamaton', 'keratty') status
							FROM tilausrivi
							WHERE tunnus = '$kerivi[$i]' and yhtio='$kukarow[yhtio]'";
				$ktresult = mysql_query($query1) or pupe_error($query1);
				$statusrow = mysql_fetch_assoc($ktresult);
				$keraamaton = 0;

				if ($statusrow["status"] == "keraamaton") {
					if ($kerivi[$i] > 0) {

						$apui = $kerivi[$i];

						//Kysess� voi olla ker�yskl�ntti, haetaan muuttuneen rivin otunnus
						$query1 = "	SELECT otunnus
									FROM tilausrivi
									WHERE tunnus = '$kerivi[$i]' and yhtio='$kukarow[yhtio]'";
						$result  = mysql_query($query1) or pupe_error($query1);
						$otsikko = mysql_fetch_assoc($result);

						//Haetaan otsikon kaikki tiedot
						$query1 = "	SELECT lasku.*,
									laskun_lisatiedot.laskutus_nimi,
									laskun_lisatiedot.laskutus_nimitark,
									laskun_lisatiedot.laskutus_osoite,
									laskun_lisatiedot.laskutus_postino,
									laskun_lisatiedot.laskutus_postitp,
									laskun_lisatiedot.laskutus_maa
									FROM lasku
									LEFT JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio = lasku.yhtio and laskun_lisatiedot.otunnus = lasku.tunnus)
									WHERE lasku.tunnus = '$otsikko[otunnus]'
									and lasku.yhtio = '$kukarow[yhtio]'";
						$result = mysql_query($query1) or pupe_error($query1);
						$otsikkorivi = mysql_fetch_assoc($result);

						//Haetaan tilausrivin kaikki tiedot
						$query1 = "	SELECT *
									FROM tilausrivi
									WHERE tunnus = '$kerivi[$i]' and yhtio='$kukarow[yhtio]'";
						$result = mysql_query($query1) or pupe_error($query1);
						$tilrivirow = mysql_fetch_assoc($result);

						//Aloitellaan tilausrivi p�ivitysquery�
						$query = "	UPDATE tilausrivi
									SET yhtio = yhtio ";

						if ($tilrivirow["var"] != "J" and $keraysvirhe == 0 and $real_submit == 'yes') {
							//Muut kuin JT-rivit p�ivitet��n aina ker�tyiksi jos virhetsekit meniv�t ok
							$query .= ", keratty = '$who',
										 kerattyaika = now()";
						}

						// K�ytt�j� on sy�tt�nyt jonkun luvun, p�ivitet��n vaikka virhetsekit menisiv�t pepulleen
						if (trim($maara[$apui]) != '') {

							// jos ker�yser�t on k�yt�ss�, p�ivitet��n muuttuneet kappalem��r�t ker�yser��n
							if ($yhtiorow['kerayserat'] == 'K' and trim($kukarow['keraysvyohyke']) != '' and $toim == "") {
								$query_ker = "SELECT tunnus FROM kerayserat WHERE yhtio = '{$kukarow['yhtio']}' AND tilausrivi = '{$apui}'";
								$keraysera_chk_res = mysql_query($query_ker) or pupe_error($query_ker);

								while ($keraysera_chk_row = mysql_fetch_assoc($keraysera_chk_res)) {
									if (!is_numeric(trim($keraysera_maara[$keraysera_chk_row['tunnus']]))) {
										$keraysera_maara[$keraysera_chk_row['tunnus']] = 0;
									}

									$query_upd = "UPDATE kerayserat SET kpl = '{$keraysera_maara[$keraysera_chk_row['tunnus']]}' WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$keraysera_chk_row['tunnus']}'";
									$keraysera_update_res = mysql_query($query_upd) or pupe_error($query_upd);
								}
							}

							// Siivotaan hieman k�ytt�j�n sy�tt�m�� kappalem��r��
							$maara[$apui] = str_replace (",", ".", $maara[$apui]);
							$maara[$apui] = (float) $maara[$apui];

							// Ker�t��n tietoa poikkeama-maileja varten
							$poikkeamat[$tilrivirow["otunnus"]][$i]["tuoteno"] 	= $tilrivirow["tuoteno"];
							$poikkeamat[$tilrivirow["otunnus"]][$i]["nimitys"] 	= $tilrivirow["nimitys"];
							$poikkeamat[$tilrivirow["otunnus"]][$i]["tilkpl"] 	= $tilrivirow["tilkpl"];
							$poikkeamat[$tilrivirow["otunnus"]][$i]["var"] 		= $tilrivirow["var"];
							$poikkeamat[$tilrivirow["otunnus"]][$i]["maara"] 	= $maara[$apui];

							$rotunnus = 0;

							if ($tilrivirow["var"] == 'P' and $maara[$apui] > 0) {

								// Puuterivi l�ytyi poistetaan VAR
								$query .= "	, var		= ''
											, varattu	= '".$maara[$apui]."'";

								//Poistetaan 'tuote loppu'-kommentti jos tuotetta sittenkin l�ytyi
								$puurivires = t_avainsana("PUUTEKOMM");

								if (mysql_num_rows($puurivires) > 0) {
									$puurivirow = mysql_fetch_assoc($puurivires);

									$korvataan_pois = $puurivirow["selite"];
								}
								else {
									$korvataan_pois = t("Tuote Loppu.");
								}

								$query .= "	, kommentti	= replace(kommentti, '$korvataan_pois', '') ";

								// PUUTE-riville tehd��n osatoimitus ja loput j�tet��n puuteriviksi
								if ($maara[$apui] < $tilrivirow['tilkpl']) {

									$poikkeamat[$tilrivirow["otunnus"]][$i]["loput"] = "J�tettiin puuteriviksi.";

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
								// JT-rivi l�ytyi, poistetaan VAR ja merkataan rivi ker�tyksi, jos virhetsekit ok
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

								// JT-riville tehd��n osatoimitus ja loput j�tet��n j�lkitoimitukseen
								if ($maara[$apui] < $jtsek) {

									$poikkeamat[$tilrivirow["otunnus"]][$i]["loput"] = "J�tettiin JT-riviksi.";

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

								// Varastomiehell� on nyt oikeus nollata my�s JT-rivi jos h�n saa l�hetetty� $poikkeama_kasittely[$apui] == "MI"
								if ($keraysvirhe == 0) {
									$query .= ", keratty = '$who',
												 kerattyaika = now()";
								}

								$query .= "	, var			= ''
											, jt			= 0
											, varattu		= 0";
							}
							elseif ((!isset($poikkeama_kasittely[$apui]) or $poikkeama_kasittely[$apui] == "") and $maara[$apui] >= 0 and $maara[$apui] < $tilrivirow['varattu'] and ($otsikkorivi['clearing'] == 'ENNAKKOTILAUS' or $otsikkorivi['clearing'] == 'JT-TILAUS')) {
								// Jos t�m� on toimitettava ennakkotilaus tai jt-tilaus niin yritet��n laittaa poikkeama jollekin sopivalle otsikolle
								// T�h�n haaraan ei menn� jos poikkeamat ohjataan manuaalisesti

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

								// Etsit��n sopiva otsikko jolle rivi laitetaan
								// Samat ehdot kuin tee_jt_tilaus.inc:ss� rivill� ~180
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
								$stresult = mysql_query($query1) or pupe_error($query1);

								// Sopiva otsikko l�ytyi
								if (mysql_num_rows($stresult) > 0) {
									$strow = mysql_fetch_assoc($stresult);

									// Sopivin otsikko oli dellattu, elvytet��n se!
									if ($otsikkorivi['clearing'] == 'ENNAKKOTILAUS' and $strow["tila"] == "D") {

										// E, A - Ennakkotilaus lep��m�ss�
										$ukysx  = "UPDATE lasku SET tila = 'E', alatila = 'A', comments = '' WHERE yhtio = '$strow[yhtio]' and tunnus = '$strow[tunnus]'";
										$ukysxres  = mysql_query($ukysx) or pupe_error($ukysx);
									}
									elseif ($otsikkorivi['clearing'] == 'JT-TILAUS' and $strow["tila"] == "D") {

										// N, T - Myyntitilaus odottaa JT-tuotteita
										$ukysx  = "UPDATE lasku SET tila = 'N', alatila = 'T', comments = '' WHERE yhtio = '$strow[yhtio]' and tunnus = '$strow[tunnus]'";
										$ukysxres  = mysql_query($ukysx) or pupe_error($ukysx);
									}

									$rotunnus = $strow["tunnus"];
								}
								else {
									// Laitetaan t�lle otsikolle, voi menn� solmuun, mutta ei katoa ainakaan kokonaan
									$rotunnus = $tilrivirow['otunnus'];
								}
							}
							elseif ($tilrivirow["var"] != 'J' and $tilrivirow["var"] != 'P') {
								// Jos t�m� on normaali rivi
								if ($maara[$apui] < 0) {
									// Jos ker��j� kuittaa alle nollan niin ei tehd� mit��n
									$query .= ", varattu = varattu";

									$poikkeamat[$tilrivirow["otunnus"]][$i]["loput"] = "M��r� nollaa pienempi. Poikkeamaa ei hyv�ksytty.";
								}
								elseif ($maara[$apui] >= 0 and $maara[$apui] < $tilrivirow['varattu']) {

									$query .= ", varattu = '".$maara[$apui]."'";

									if ($poikkeama_kasittely[$apui] != "") {

										if ($maara[$apui] == 0) {
											// Mit�t�id��n nollarivi koska poikkeamalle kuitenkin tehd��n jotain fiksua
											$query .= ", tyyppi = 'D', kommentti=trim(concat(kommentti, ' Mit�t�itiin koska ker�yspoikkeamasta tehtiin: ".$poikkeama_kasittely[$apui]."'))";
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
									//P�ivitet��n vain m��r� jos se on isompi kuin alkuper�inen varattum��r�
									$query .= ", varattu = '".$maara[$apui]."'";
									}
								}

							if ($poikkeama_kasittely[$apui] != "" and $rotunnus != 0) {
								// K�ytt�j�n valitsemia poikkeamak�sittelys��nt�j�
								if ($poikkeama_kasittely[$apui] == "PU") {

									$poikkeamat[$tilrivirow["otunnus"]][$i]["loput"] = "J�tettiin puuteriviksi.";

									// Riville tehd��n osatoimitus ja loput j�tet��n puuteriviksi
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

									$poikkeamat[$tilrivirow["otunnus"]][$i]["loput"] = "J�tettiin JT-riviksi.";

									// Riville tehd��n osatoimitus ja loput j�tet��n j�lkk�riin
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

									$poikkeamat[$tilrivirow["otunnus"]][$i]["loput"] = "Mit�t�itiin.";

									// Riville tehd��n osatoimitus ja loput mit�t�id��n
									$rotunnus	= 0;
								}
								elseif ($poikkeama_kasittely[$apui] == "UR") {

									$poikkeamat[$tilrivirow["otunnus"]][$i]["loput"] = "Siirrettiin uudelle riville.";

									// Riville tehd��n osatoimitus ja loput kopsataan uudelle riville
									$rvarattu	= $rtilkpl;
									$rjt  		= 0;
									$rvar		= "";
									$keratty	= "''";
									$kerattyaik	= "''";
									$rkomm 		= $tilrivirow["kommentti"];
								}
								elseif ($poikkeama_kasittely[$apui] == "UT") {
									// Riville tehd��n osatoimitus ja loput siirret��n ihan uudelle tilaukselle
									if (!isset($tilausnumerot[$tilrivirow["otunnus"]])) {

										// Jotta saadaa lasku kopsattua kivasti jos se splittaantuu
										$laspliq = "SELECT *
													FROM lasku
													WHERE yhtio = '$kukarow[yhtio]'
													and tunnus = '$tilrivirow[otunnus]'";
										$laskusplitres = mysql_query($laspliq) or pupe_error($laspliq);
										$laskusplitrow = mysql_fetch_assoc($laskusplitres);

										if ($laskusplitrow["tunnusnippu"] == 0) {
											// Laitetaan uusi tilaus osatoimitukseksi alkuper�iselle tilaukselle
											$kysely  = "UPDATE lasku SET tunnusnippu=tunnus WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tilrivirow[otunnus]'";
											$insres  = mysql_query($kysely) or pupe_error($kysely);

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
										$insres  = mysql_query($kysely) or pupe_error($kysely);
										$tilausnumerot[$tilrivirow["otunnus"]] = mysql_insert_id();

										$kysely2 = "	SELECT laskutus_nimi, laskutus_nimitark, laskutus_osoite, laskutus_postino, laskutus_postitp, laskutus_maa, laatija, luontiaika, otunnus
														FROM laskun_lisatiedot
														WHERE yhtio = '$kukarow[yhtio]'
														AND otunnus = '$tilrivirow[otunnus]'";
										$lisatiedot_result = mysql_query($kysely2) or pupe_error($kysely2);
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
										$insres  = mysql_query($kysely2) or pupe_error($kysely2);
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

							// T�ss� tehd��n uusi rivi
							if ($rotunnus != 0) {

								// Aina jos rivi splitataan niin p�ivitet��n alkuper�isen rivin tilkpl
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
								$riviresult = mysql_query($querys) or pupe_error($querys);
								$lisatty_tun = mysql_insert_id();

								//Kopioidaan tilausrivin lisatiedot
								$querys = "	SELECT *
											FROM tilausrivin_lisatiedot
											WHERE tilausrivitunnus='$tilrivirow[tunnus]'
											and yhtio ='$kukarow[yhtio]'";
								$monistares2 = mysql_query($querys) or pupe_error($querys);

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
									$riviresult = mysql_query($querys) or pupe_error($querys);
								}

								$queryera = "SELECT sarjanumeroseuranta FROM tuote WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$tilrivirow[tuoteno]'";
								$sarjares2 = mysql_query($queryera) or pupe_error($queryera);
								$erarow = mysql_fetch_assoc($sarjares2);

								if ($erarow['sarjanumeroseuranta'] == 'E' or $erarow['sarjanumeroseuranta'] == 'F') {
									echo "<font class='error'>".t("Er�numeroseurannassa oleville tuotteille on liitett�v� er�numero ennen ker�yst�")."! ".t("Tuote").": $tilrivirow[tuoteno].</font><br>";
								}
							}

							//p�ivitet��n tuoteperheiden saldottomat j�senet oikeisiin m��riin (ne voi olla alkuper�isell�kin l�hetteell� == vanhatunnus)
							if ($tilrivirow["perheid"] != 0) {
								$query1 = "	SELECT tilausrivi.tunnus, tilausrivi.tuoteno
											FROM tilausrivi, tuote
											WHERE tilausrivi.otunnus in ('$tilrivirow[otunnus]', '$otsikkorivi[vanhatunnus]')
											and tilausrivi.tunnus	!= '$kerivi[$i]'
											and tilausrivi.perheid	 = '$tilrivirow[perheid]'
											and tilausrivi.yhtio	 = '$kukarow[yhtio]'
											and tuote.yhtio			 = tilausrivi.yhtio
											and tuote.tuoteno	 	 = tilausrivi.tuoteno
											and tuote.ei_saldoa		!= ''";
								$result = mysql_query($query1) or pupe_error($query1);

								while ($tilrivirow2 = mysql_fetch_assoc($result)) {
									$query2 = "	SELECT kerroin
												FROM tuoteperhe
												WHERE yhtio = '$kukarow[yhtio]' AND
												isatuoteno = '$tilrivirow[tuoteno]' AND
												tuoteno = '$tilrivirow2[tuoteno]'";
									$result2 = mysql_query($query2) or pupe_error($query2);

									// oltiin muokkaamassa is�tuotteen kappalem��r��, p�ivitet��n saldottomien lasten m��r�t kertoimella
									if (mysql_num_rows($result2) == 1) {
										$kerroinrow = mysql_fetch_assoc($result2);
										if ($kerroinrow["kerroin"] == 0) $kerroinrow["kerroin"] = 1;
										$tilrivimaara = round($maara[$apui] * $kerroinrow["kerroin"], 2);

										$query1 = "	UPDATE tilausrivi
													SET varattu = '$tilrivimaara'
													WHERE tunnus = '$tilrivirow2[tunnus]' AND
													yhtio = '$kukarow[yhtio]'";
										$result1 = mysql_query($query1) or pupe_error($query1);
									}
								}
							}

							$muuttuiko = 'kylsemuuttu';
						}

						if ($toim == 'VASTAANOTA_REKLAMAATIO' and $keraysvirhe == 0) {

							if (trim($varastorekla[$kerivi[$i]]) != '' and trim($vertaus_hylly[$kerivi[$i]]) != trim($varastorekla[$kerivi[$i]])) {
								// Ollaan valittu varastopaikka dropdownista
								list($rekla_hyllyalue, $rekla_hyllynro, $rekla_hyllyvali, $rekla_hyllytaso) = explode("###", $varastorekla[$kerivi[$i]]);
							}
							elseif (trim($vertaus_hylly[$kerivi[$i]]) == trim($varastorekla[$kerivi[$i]]) and $rekla_hyllyalue[$kerivi[$i]] != '') {
								// Ollaan sy�tetty varastopaikka k�sin
								$rekla_hyllyalue = $rekla_hyllyalue[$kerivi[$i]];
								$rekla_hyllynro  = $rekla_hyllynro[$kerivi[$i]];
								$rekla_hyllyvali = $rekla_hyllyvali[$kerivi[$i]];
								$rekla_hyllytaso = $rekla_hyllytaso[$kerivi[$i]];
							}
							else {
								// Otetaan tuotteen oletuspaikka
								list($rekla_hyllyalue, $rekla_hyllynro, $rekla_hyllyvali, $rekla_hyllytaso) = explode("###", $vertaus_hylly[$kerivi[$i]]);
							}

							// Lis�t��n paikat tilausriville
							$query .= ", hyllyalue = '$rekla_hyllyalue', hyllynro = '$rekla_hyllynro', hyllyvali = '$rekla_hyllyvali', hyllytaso = '$rekla_hyllytaso'";
						}

						//p�ivitet��n alkuper�inen rivi
						$query .= " WHERE tunnus='$kerivi[$i]' and yhtio='$kukarow[yhtio]'";
						$result = mysql_query($query) or pupe_error($query);

						// Pit�� lis�t� p�ivityksen yhteydess� my�s tuotepaikka...
						if ($toim == 'VASTAANOTA_REKLAMAATIO' and $keraysvirhe == 0) {

							$select = "	SELECT *
										FROM tuotepaikat
										WHERE yhtio 	= '$kukarow[yhtio]'
										AND hyllyalue 	= '$rekla_hyllyalue'
										AND hyllynro 	= '$rekla_hyllynro'
										AND hyllyvali 	= '$rekla_hyllyvali'
										AND hyllytaso 	= '$rekla_hyllytaso'
										AND tuoteno 	= '{$rivin_puhdas_tuoteno[$kerivi[$i]]}'";
							$hakures = mysql_query($select) or pupe_error($select);
							$sresults = mysql_fetch_assoc($hakures);

							if (mysql_num_rows($hakures) == 0) {
								// lis�t��n tuotteelle tapahtuma
								$select = "	INSERT into tuotepaikat set
											yhtio 		= '$yhtiorow[yhtio]',
											tuoteno 	= '{$rivin_puhdas_tuoteno[$kerivi[$i]]}',
											hyllyalue	= '$rekla_hyllyalue',
											hyllynro	= '$rekla_hyllynro',
											hyllyvali	= '$rekla_hyllyvali',
											hyllytaso	= '$rekla_hyllytaso',
											laatija 	= '$kukarow[kuka]',
											luontiaika 	= now(),
											muutospvm 	= now(),
											muuttaja	= '$kukarow[kuka]' ";
								$result = mysql_query($select) or pupe_error($select);

								// tehd��n tapahtuma
								$select = "	INSERT into tapahtuma set
											yhtio 		= '$kukarow[yhtio]',
											tuoteno 	= '{$rivin_puhdas_tuoteno[$kerivi[$i]]}',
											kpl 		= '0',
											kplhinta	= '0',
											hinta 		= '0',
											laji 		= 'uusipaikka',
											hyllyalue	= '$rekla_hyllyalue',
											hyllynro	= '$rekla_hyllynro',
											hyllyvali	= '$rekla_hyllyvali',
											hyllytaso	= '$rekla_hyllytaso',
											selite 		= '".t("Lis�ttiin tuotepaikka")." $rekla_hyllyalue $rekla_hyllynro $rekla_hyllyvali $rekla_hyllytaso',
											laatija 	= '$kukarow[kuka]',
											laadittu 	= now()";
								$result = mysql_query($select) or pupe_error($select);
							}
						}
					}

					//Ker��m�t�n rivi
					$keraamaton++;
				}
				else {
					echo t("HUOM: T�m� rivi oli jo ker�tty! Ei voida ker�t� uudestaan.")."<br>";
				}
			}
		}

		if ($keraysvirhe > 0) {
			$tee = '';
		}

		// Jos ker�yspoikkeamia syntyi, niin l�hetet��n mailit myyj�lle ja asiakkaalle
		if ($muuttuiko == 'kylsemuuttu') {
			foreach ($poikkeamat as $poikkeamatilaus => $poikkeamatilausrivit) {

				$query = "	SELECT lasku.*, asiakas.email, asiakas.kerayspoikkeama, kuka.nimi kukanimi, kuka.eposti as kukamail, asiakas.kieli
							FROM lasku
							JOIN asiakas on asiakas.yhtio=lasku.yhtio and asiakas.tunnus=lasku.liitostunnus
							LEFT JOIN kuka on kuka.yhtio=lasku.yhtio and kuka.tunnus=lasku.myyja
							WHERE lasku.tunnus	= '$poikkeamatilaus'
							and lasku.yhtio		= '$kukarow[yhtio]'";
				$result = mysql_query($query) or pupe_error($query);
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

				$ulos .= "<font class='head'>".t("Ker�yspoikkeamat", $kieli)."</font><hr><br><br><table>";

				$ulos .= "<tr><th>".t("Yhti�", $kieli)."</th></tr>";
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
				$ulos .= "<tr><th>".t("Myyj�", $kieli).":</th><td>$laskurow[kukanimi]</td></tr>";

				if ($laskurow['comments'] != '') {
					$ulos .= "<tr><th>".t("Kommentti", $kieli).":</th><td>".$laskurow['comments']."</td></tr>";
				}

				$ulos .= "</table><br><br>";

				$ulos .= "<table>";
				$ulos .= "<tr><th>".t("Nimitys", $kieli)."</th><th>".t("Tuotenumero", $kieli)."</th><th>".t("Tilattu", $kieli)."</th><th>".t("Toimitetaan", $kieli)."</th>";
				if ($yhtiorow["kerayspoikkeama_kasittely"] != '') $ulos .= "<th>".t("Poikkeaman k�sittely", $kieli)."</th>";
				$ulos .= "</tr>";
				$ulos .= $rivit;
				$ulos .= "</table><br><br>";

				$ulos .= t("T�m� on automaattinen viesti. T�h�n s�hk�postiin ei tarvitse vastata.", $kieli)."<br><br>";
				$ulos .= "</body></html>";

				// korvataan poikkeamameili ker�ysvahvistuksella
				if ($yhtiorow["keraysvahvistus_lahetys"] == 'o' and $laskurow["kerayspoikkeama"] == 0) {
					$laskurow["kerayspoikkeama"] = 2;
				}

				// L�hetet��n ker�yspoikkeama asiakkaalle
				if ($laskurow["email"] != '' and $laskurow["kerayspoikkeama"] == 0) {
					$boob = mail($laskurow["email"], mb_encode_mimeheader("$yhtiorow[nimi] - ".t("Ker�yspoikkeamat", $kieli), "ISO-8859-1", "Q"), $ulos, $header, "-f $yhtiorow[postittaja_email]");
					if ($boob === FALSE) echo " - ".t("Email l�hetys ep�onnistui")."!<br>";
				}

				// L�hetet��n ker�yspoikkeama myyj�lle
				if ($laskurow["kukamail"] != '' and ($laskurow["kerayspoikkeama"] == 0 or $laskurow["kerayspoikkeama"] == 2)) {

					$uloslisa = "";

					if (($laskurow["email"] == '' or $boob === FALSE) and $laskurow["kerayspoikkeama"] == 0) {
						$uloslisa .= t("Asiakkaalta puuttuu s�hk�postiosoite! Ker�yspoikkeamia ei voitu l�hett��!")."<br><br>";
					}
					elseif ($laskurow["kerayspoikkeama"] == 2) {
						$uloslisa .= t("Asiakkaalle on merkitty ett� h�n ei halua ker�yspoikkeama ilmoituksia!")."<br><br>";
					}
					else {
						$uloslisa .= t("T�m� viesti on l�hetetty my�s asiakkaalle")."!<br><br>";
					}

					$uloslisa .= t("Tilauksen ker�si").": $keraaja[nimi]<br><br>";

					$ulos = str_replace("</font><hr><br><br><table>", "</font><hr><br><br>$uloslisa<table>", $ulos);

					$boob = mail($laskurow["kukamail"], mb_encode_mimeheader("$yhtiorow[nimi] - ".t("Ker�yspoikkeamat", $kieli), "ISO-8859-1", "Q"), $ulos, $header, "-f $yhtiorow[postittaja_email]");
					if ($boob === FALSE) echo " - ".t("Email l�hetys ep�onnistui")."!<br>";
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

				// Jos tilauksella oli yht��n ker��m�t�nt� rivi�
				$query = "	SELECT lasku.tunnus, lasku.vienti, lasku.tila, lasku.alatila,
							toimitustapa.rahtikirja,
							toimitustapa.tulostustapa,
							toimitustapa.nouto,
							lasku.varasto,
							lasku.toimitustapa,
							lasku.jaksotettu
							FROM lasku
							LEFT JOIN toimitustapa ON lasku.yhtio = toimitustapa.yhtio and lasku.toimitustapa = toimitustapa.selite and toimitustapa.nouto=''
							where lasku.yhtio = '$kukarow[yhtio]'
							and lasku.tunnus in ($tilausnumeroita)
							and lasku.tila in ($tila)
							and lasku.alatila = '$hakualatila'";

				$lasresult = mysql_query($query) or pupe_error($query);

				$lask_nro = "";
				$extra    = "";

				while ($laskurow = mysql_fetch_assoc($lasresult)) {

					if ($laskurow["tila"] == 'L' and $laskurow["vienti"] == '' and $laskurow["tulostustapa"] == "X" and $laskurow["nouto"] == "") {

						// Jos meill� on maksupositioita laskulla, tulee se siirt�� alatilaan J
						if ($laskurow['jaksotettu'] != 0) {
							$alatilak = "J";
						}
						else {
							$alatilak = "D";
						}

						// P�ivitet��n my�s rivit toimitetuiksi
						$query = "	UPDATE tilausrivi
									SET toimitettu = '$kukarow[kuka]', toimitettuaika = now()
									WHERE otunnus 	= '$laskurow[tunnus]'
									and var not in ('P','J')
									and yhtio 		= '$kukarow[yhtio]'
									and keratty    != ''
									and toimitettu  = ''
									and tyyppi 		= 'L'";
						$yoimresult = mysql_query($query) or pupe_error($query);

						// Etuk�teen maksetut tilaukset pit�� muuttaa takaisin "maksettu"-tilaan
						$query = "	UPDATE lasku SET
									alatila = 'X'
									WHERE yhtio = '$kukarow[yhtio]'
									AND tunnus = '$laskurow[tunnus]'
									AND mapvm != '0000-00-00'
									AND chn = '999'";
						$yoimresult  = mysql_query($query) or pupe_error($query);
					}
					elseif ($laskurow["tila"] == 'G' and $laskurow["vienti"] == '' and $laskurow["tulostustapa"] == "X" and $laskurow["nouto"] == "") {
						// Jos meill� on maksupositioita laskulla, tulee se siirt�� alatilaan J
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
					// Lasku p�ivitet��n vasta kuin tilausrivit on p�ivitetty...

					$query  = "	UPDATE lasku SET
								alatila = '$alatilak'
								$extra
								WHERE yhtio = '$kukarow[yhtio]'
								AND tunnus  = '$laskurow[tunnus]'";
					$result = mysql_query($query) or pupe_error($query);

					if ($lask_nro == '') {
						$lask_nro = $laskurow['tunnus'];
					}

					if ($yhtiorow['kerayserat'] == 'K' and $toim == "") {
						$query = "	SELECT pakkaus.pakkaus, 
									pakkaus.pakkauskuvaus, 
									tuote.tuotemassa, 
									(pakkaus.leveys * pakkaus.korkeus * pakkaus.syvyys) as kuutiot,
									kerayserat.kpl,
									kerayserat.pakkausnro
									FROM kerayserat
									JOIN pakkaus ON (pakkaus.yhtio = kerayserat.yhtio AND pakkaus.tunnus = kerayserat.pakkaus)
									JOIN tilausrivi ON (tilausrivi.yhtio = kerayserat.yhtio AND tilausrivi.tunnus = kerayserat.tilausrivi)
									JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
									WHERE kerayserat.yhtio = '{$kukarow['yhtio']}'
									AND kerayserat.otunnus = '{$laskurow['tunnus']}'
									AND kerayserat.tila = 'K'
									ORDER BY pakkausnro ASC";
						$keraysera_res = mysql_query($query) or pupe_error($query);

						$rahtikirjan_pakkaukset = array();

						while ($keraysera_row = mysql_fetch_assoc($keraysera_res)) {
							if (!isset($rahtikirjan_pakkaukset[$keraysera_row['pakkaus']]['kpl'])) $rahtikirjan_pakkaukset[$keraysera_row['pakkaus']]['kpl'] = 0;
							if (!isset($rahtikirjan_pakkaukset[$keraysera_row['pakkaus']]['paino'])) $rahtikirjan_pakkaukset[$keraysera_row['pakkaus']]['paino'] = 0;
							if (!isset($rahtikirjan_pakkaukset[$keraysera_row['pakkaus']]['kuutiot'])) $rahtikirjan_pakkaukset[$keraysera_row['pakkaus']]['kuutiot'] = 0;
							if (!isset($rahtikirjan_pakkaukset[$keraysera_row['pakkaus']]['pakkausnro'])) $rahtikirjan_pakkaukset[$keraysera_row['pakkaus']]['pakkausnro'] = 0;

							$rahtikirjan_pakkaukset[$keraysera_row['pakkaus']]['kpl'] 			+= $keraysera_row['kpl'];
							$rahtikirjan_pakkaukset[$keraysera_row['pakkaus']]['pakkauskuvaus'] = $keraysera_row['pakkauskuvaus'];
							$rahtikirjan_pakkaukset[$keraysera_row['pakkaus']]['paino'] 		+= ($keraysera_row['tuotemassa'] * $keraysera_row['kpl']);
							$rahtikirjan_pakkaukset[$keraysera_row['pakkaus']]['kuutiot'] 		+= $keraysera_row['kuutiot'];
							$rahtikirjan_pakkaukset[$keraysera_row['pakkaus']]['pakkausnro']	= $keraysera_row['pakkausnro'];
						}

						// esisy�tet��n rahtikirjan tiedot
						if (count($rahtikirjan_pakkaukset) > 0) {

							foreach ($rahtikirjan_pakkaukset as $pak => $arr) {
								$query_ker  = "	INSERT INTO rahtikirjat SET
												kollit = '{$arr['pakkausnro']}',
												kilot = '{$arr['paino']}',
												kuutiot = '{$arr['kuutiot']}',
												pakkauskuvaus = '{$arr['pakkauskuvaus']}',
												pakkaus = '{$pak}',
												rahtikirjanro = '{$laskurow['tunnus']}',
												otsikkonro = '{$laskurow['tunnus']}',
												tulostuspaikka = '{$laskurow['varasto']}',
												toimitustapa = '{$laskurow['toimitustapa']}',
												yhtio = '{$kukarow['yhtio']}'";
								$ker_res = mysql_query($query_ker) or pupe_error($query_ker);

								$query = "UPDATE lasku SET alatila = 'B' WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$laskurow['tunnus']}'";
								$alatila_upd_res = mysql_query($query) or pupe_error($query);

								// jos kyseess� on toimitustapa jonka rahtikirja on hetitulostus, tulostetaan my�s rahtikirja t�ss� vaiheessa
								if ($laskurow['tulostustapa'] == 'H' and $laskurow["nouto"] == "") {

									// p�ivitet��n ker�yser�n tila "Rahtikirja tulostettu"-tilaan
									$query = "UPDATE kerayserat SET tila = 'R' WHERE yhtio = '{$kukarow['yhtio']}' AND otunnus = '{$laskurow['tunnus']}'";
									$tila_upd_res = mysql_query($query) or pupe_error($query);

									// rahtikirjojen tulostus vaatii seuraavat muuttujat:

									// $toimitustapa_varasto	toimitustavan selite!!!!varastopaikan tunnus
									// $tee						t�ss� pit�� olla teksti tulosta

									// otetaan talteen tee-muuttuja
									$tee_tmp = $tee;

									$toimitustapa_varasto = $laskurow['toimitustapa']."!!!!".$kukarow['yhtio']."!!!!".$laskurow['varasto'];
									$tee				  = "tulosta";

									require ("rahtikirja-tulostus.php");

									$tee = $tee_tmp;
								}
								else {
									// p�ivitet��n ker�yser�n tila "Ker�tty"-tilaan
									$query = "UPDATE kerayserat SET tila = 'T' WHERE yhtio = '{$kukarow['yhtio']}' AND otunnus = '{$laskurow['tunnus']}'";
									$tila_upd_res = mysql_query($query) or pupe_error($query);
								}
							}
						}
						else {
							// p�ivitet��n ker�yser�n tila "Ker�tty"-tilaan
							$query = "UPDATE kerayserat SET tila = 'T' WHERE yhtio = '{$kukarow['yhtio']}' AND otunnus = '{$laskurow['tunnus']}'";
							$tila_upd_res = mysql_query($query) or pupe_error($query);
						}
					}
					elseif ($yhtiorow['pakkaamolokerot'] != '') {
						// jos meill� on pakkaamolokerot k�yt�ss� (eli pakkaamotsydeema), niin esisy�tet��n rahtikirjat jos k�ytt�j� on antanut kollien/rullakkojen m��r�t
						// ei kuiteskaan tehd� t�st� virallisesti esisy�tetty� rahtikirjaa!
						if (isset($pakkaamo_kolli) and $pakkaamo_kolli != '') {
							$pakkaamo_kolli = (int) $pakkaamo_kolli;

							$query  = "	INSERT INTO rahtikirjat SET
										kollit = '$pakkaamo_kolli',
										pakkaus = 'KOLLI',
										pakkauskuvaus = 'KOLLI',
										rahtikirjanro = '$lask_nro',
										otsikkonro = '$lask_nro',
										tulostuspaikka = '$laskurow[varasto]',
										yhtio = '$kukarow[yhtio]'";
							$result_rk = mysql_query($query) or pupe_error($query);
						}

						if (isset($pakkaamo_rullakko) and $pakkaamo_rullakko != '') {
							$pakkaamo_rullakko = (int) $pakkaamo_rullakko;

							$query  = "	INSERT INTO rahtikirjat SET
										kollit = '$pakkaamo_rullakko',
										pakkaus = 'Rullakko',
										pakkauskuvaus = 'Rullakko',
										rahtikirjanro = '$lask_nro',
										otsikkonro = '$lask_nro',
										tulostuspaikka = '$laskurow[varasto]',
										yhtio = '$kukarow[yhtio]'";
							$result_rk = mysql_query($query) or pupe_error($query);
						}
					}
				}

				// Tutkitaan viel� aivan lopuksi mihin tilaan me laitetaan t�m� otsikko
				// Ker�ysvaiheessahan tilausrivit muuttuvat ja tarkastamme nyt tilanteen uudestaan
				// T�m� tehd��n vain myyntitilauksille
				if ($tila == "'L'") {
					$kutsuja = "keraa.php";

					$query = "	SELECT *
								FROM lasku
								WHERE tunnus in ($tilausnumeroita)
								and yhtio = '$kukarow[yhtio]'
								and tila  = 'L'";
					$lasresult = mysql_query($query) or pupe_error($query);

					while ($laskurow = mysql_fetch_assoc($lasresult)) {
						require("tilaus-valmis-valitsetila.inc");
					}
				}

				if ($toim != 'VASTAANOTA_REKLAMAATIO') {
					// Tulostetaan uusi l�hete jos k�ytt�j� valitsi drop-downista printterin
					// Paitsi jos tilauksen tila p�ivitettiin sellaiseksi, ett� l�hetett� ei kuulu tulostaa
					$query = "	SELECT lasku.*, asiakas.email
								FROM lasku
								LEFT JOIN asiakas on lasku.yhtio = asiakas.yhtio and lasku.liitostunnus = asiakas.tunnus
								WHERE lasku.tunnus in ($tilausnumeroita)
								and lasku.yhtio = '$kukarow[yhtio]'
								and lasku.alatila in ('C','D')";
					$lasresult = mysql_query($query) or pupe_error($query);

					while ($laskurow = mysql_fetch_assoc($lasresult)) {

						if ($valittu_tulostin != "") {
							//haetaan l�hetteen tulostuskomento
							$query   = "SELECT * from kirjoittimet where yhtio='$kukarow[yhtio]' and tunnus='$valittu_tulostin'";
							$kirres  = mysql_query($query) or pupe_error($query);
							$kirrow  = mysql_fetch_assoc($kirres);
							$komento = $kirrow['komento'];
						}

						if ($valittu_oslapp_tulostin != "") {
							//haetaan osoitelapun tulostuskomento
							$query  = "SELECT * from kirjoittimet where yhtio='$kukarow[yhtio]' and tunnus='$valittu_oslapp_tulostin'";
							$kirres = mysql_query($query) or pupe_error($query);
							$kirrow = mysql_fetch_assoc($kirres);
							$oslapp = $kirrow['komento'];
						}

						if (($valittu_tulostin != '' and $komento != "" and $lahetekpl > 0) or ($yhtiorow["keraysvahvistus_lahetys"] == "o" and $laskurow['email'] != "")) {

							$otunnus = $laskurow["tunnus"];

							//hatetaan asiakkaan l�hetetyyppi
							$query = "  SELECT lahetetyyppi, luokka, puhelin, if (asiakasnro!='', asiakasnro, ytunnus) asiakasnro
										FROM asiakas
										WHERE tunnus='$laskurow[liitostunnus]' and yhtio='$kukarow[yhtio]'";
							$result = mysql_query($query) or pupe_error($query);
							$asrow = mysql_fetch_assoc($result);

							$lahetetyyppi = "";

							if ($sellahetetyyppi != '') {
								$lahetetyyppi = $sellahetetyyppi;
							}
							elseif ($asrow["lahetetyyppi"] != '') {
								$lahetetyyppi = $asrow["lahetetyyppi"];
							}
							else {
								//Haetaan yhti�n oletusl�hetetyyppi
								$query = "  SELECT selite
											FROM avainsana
											WHERE yhtio = '$kukarow[yhtio]' and laji = 'LAHETETYYPPI'
											ORDER BY jarjestys, selite
											LIMIT 1";
								$vres = mysql_query($query) or pupe_error($query);
								$vrow = mysql_fetch_assoc($vres);

								if ($vrow["selite"] != '' and file_exists($vrow["selite"])) {
									$lahetetyyppi = $vrow["selite"];
								}
							}

							require("tulosta_lahete.inc");

							//	Jos meill� on funktio tulosta_lahete meill� on suora funktio joka hoitaa koko tulostuksen
							if (function_exists("tulosta_lahete")) {
								if ($vrow["selite"] != '') {
									$tulostusversio = $vrow["selite"];
								}
								else {
									$tulostusversio = $asrow["lahetetyyppi"];
								}

								tulosta_lahete($otunnus, $komento["L�hete"], $kieli = "", $toim, $tee, $tulostusversio);
							}
							else {
								// katotaan miten halutaan sortattavan
								// haetaan asiakkaan tietojen takaa sorttaustiedot
								$order_sorttaus = '';

								$asiakas_apu_query = "	SELECT lahetteen_jarjestys, lahetteen_jarjestys_suunta, email
														FROM asiakas
														WHERE yhtio = '$kukarow[yhtio]'
														and tunnus = '$laskurow[liitostunnus]'";
								$asiakas_apu_res = mysql_query($asiakas_apu_query) or pupe_error($asiakas_apu_query);

								if (mysql_num_rows($asiakas_apu_res) == 1) {
									$asiakas_apu_row = mysql_fetch_assoc($asiakas_apu_res);
									$sorttauskentta = generoi_sorttauskentta($asiakas_apu_row["lahetteen_jarjestys"] != "" ? $asiakas_apu_row["lahetteen_jarjestys"] : $yhtiorow["lahetteen_jarjestys"]);
									$order_sorttaus = $asiakas_apu_row["lahetteen_jarjestys_suunta"] != "" ? $asiakas_apu_row["lahetteen_jarjestys_suunta"] : $yhtiorow["lahetteen_jarjestys_suunta"];
								}
								else {
									$sorttauskentta = generoi_sorttauskentta($yhtiorow["lahetteen_jarjestys"]);
									$order_sorttaus = $yhtiorow["lahetteen_jarjestys_suunta"];
								}

								if ($yhtiorow["lahetteen_palvelutjatuottet"] == "E") $pjat_sortlisa = "tuotetyyppi,";
								else $pjat_sortlisa = "";

								if ($laskurow["tila"] == "L" or $laskurow["tila"] == "N") {
									$tyyppilisa = " and tilausrivi.tyyppi in ('L') ";
								}
								else {
									$tyyppilisa = " and tilausrivi.tyyppi in ('L','G','W') ";
								}

								$query_ale_lisa = generoi_alekentta('M');

								//generoidaan l�hetteelle ja ker�yslistalle rivinumerot
								$query = "  SELECT tilausrivi.*,
											round(if (tuote.myymalahinta != 0, tuote.myymalahinta/if(tuote.myyntihinta_maara>0, tuote.myyntihinta_maara, 1), tilausrivi.hinta * if ('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1)),'$yhtiorow[hintapyoristys]') ovhhinta,
											round(tilausrivi.hinta * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * {$query_ale_lisa},'$yhtiorow[hintapyoristys]') rivihinta,
											$sorttauskentta,
											if (tilausrivi.tuoteno='$yhtiorow[rahti_tuotenumero]', 2, if(tilausrivi.var='J', 1, 0)) jtsort,
											if (tuote.tuotetyyppi='K','2 Ty�t','1 Muut') tuotetyyppi,
											if (tuote.myyntihinta_maara=0, 1, tuote.myyntihinta_maara) myyntihinta_maara,
											tuote.sarjanumeroseuranta
											FROM tilausrivi
											JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
											JOIN lasku ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus
											LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
											WHERE tilausrivi.otunnus = '$otunnus'
											and tilausrivi.yhtio = '$kukarow[yhtio]'
											$tyyppilisa
											and (tilausrivi.perheid = 0 or tilausrivi.perheid=tilausrivi.tunnus or tilausrivin_lisatiedot.ei_nayteta !='E' or tilausrivin_lisatiedot.ei_nayteta is null)
											ORDER BY jtsort, $pjat_sortlisa sorttauskentta $order_sorttaus, tilausrivi.tunnus";
								$riresult = mysql_query($query) or pupe_error($query);

								$params_lahete = array(
								'arvo'						=> 0,
								'asrow'						=> $asrow,
								'boldi'						=> $boldi,
								'ei_otsikoita'				=> '',
								'extranet_tilausvahvistus'	=> $extranet_tilausvahvistus,
								'iso'						=> $iso,
								'jtid'						=> '',
								'kala'						=> 0,
								'kassa_ale'					=> '',
								'kieli'						=> $kieli,
								'lahetetyyppi'				=> $lahetetyyppi,
								'laskurow'					=> $laskurow,
								'naytetaanko_rivihinta'		=> $naytetaanko_rivihinta,
								'norm'						=> $norm,
								'page'						=> NULL,
								'pdf'						=> NULL,
								'perheid'					=> 0,
								'pieni'						=> $pieni,
								'pieni_boldi'				=> $pieni_boldi,
								'pitkattuotteet'			=> FALSE,
								'rectparam'					=> $rectparam,
								'riviresult'				=> $riresult,
								'rivinkorkeus'				=> $rivinkorkeus,
								'rivinumerot'				=> "",
								'row'						=> NULL,
								'sivu'						=> 1,
								'summa'						=> 0,
								'tee'						=> $tee,
								'thispage'					=> NULL,
								'toim'						=> $toim,
								'tots'						=> 0,
								'tuotenopituus'				=> '',
								'nimityskohta'   			=> '',
								'nimitysleveys'   		    => '',
								'tyyppi'					=> '',
								'useita'					=> '',
								);

								if ($laskurow["tila"] == "G") {
									$params_lahete["tyyppi"] = "SIIRTOLISTA";
								}

								// Aloitellaan l�hetteen teko
								$params_lahete = alku_lahete($params_lahete);

								// Piirret��n rivit
								mysql_data_seek($riresult,0);

								while ($row = mysql_fetch_assoc($riresult)) {
									$params_lahete["row"] = $row;
									$params_lahete = rivi_lahete($params_lahete);
								}

								//Haetaan erikseen toimitettavat tuotteet
								if ($laskurow["vanhatunnus"] != 0) {
									$query = " 	SELECT GROUP_CONCAT(distinct tunnus SEPARATOR ',') tunnukset
												FROM lasku use index (yhtio_vanhatunnus)
												WHERE yhtio		= '$kukarow[yhtio]'
												and vanhatunnus = '$laskurow[vanhatunnus]'
												and tunnus != '$laskurow[tunnus]'";
									$perheresult = mysql_query($query) or pupe_error($query);
									$tunrow = mysql_fetch_assoc($perheresult);

									//generoidaan l�hetteelle ja ker�yslistalle rivinumerot
									if ($tunrow["tunnukset"] != "") {

										$toimitettulisa = "";

										if ($laskurow["clearing"] == "ENNAKKOTILAUS" or $laskurow["clearing"] == "JT-TILAUS") {
											$toimitettulisa = " and tilausrivi.toimitettu = '' ";
										}

										$query_ale_lisa = generoi_alekentta('M');

										$query = "  SELECT tilausrivi.*,
													round(if (tuote.myymalahinta != 0, tuote.myymalahinta/if(tuote.myyntihinta_maara>0, tuote.myyntihinta_maara, 1), tilausrivi.hinta * if ('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1)),'$yhtiorow[hintapyoristys]') ovhhinta,
													round(tilausrivi.hinta * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * {$query_ale_lisa},'$yhtiorow[hintapyoristys]') rivihinta,
													$sorttauskentta,
													if (tilausrivi.tuoteno='$yhtiorow[rahti_tuotenumero]', 2, if(tilausrivi.var='J', 1, 0)) jtsort,
													if (tuote.tuotetyyppi='K','2 Ty�t','1 Muut') tuotetyyppi,
													if (tuote.myyntihinta_maara=0, 1, tuote.myyntihinta_maara) myyntihinta_maara,
													tuote.sarjanumeroseuranta
													FROM tilausrivi
													JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
													JOIN lasku ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus
													LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
													WHERE tilausrivi.otunnus in ('$tunrow[tunnukset]')
													and tilausrivi.yhtio = '$kukarow[yhtio]'
													$tyyppilisa
													$toimitettulisa
													and (tilausrivi.perheid = 0 or tilausrivi.perheid=tilausrivi.tunnus or tilausrivin_lisatiedot.ei_nayteta !='E' or tilausrivin_lisatiedot.ei_nayteta is null)
													ORDER BY jtsort, $pjat_sortlisa sorttauskentta $order_sorttaus, tilausrivi.tunnus";
										$riresult = mysql_query($query) or pupe_error($query);

										while ($row = mysql_fetch_assoc($riresult)) {

											if ($row['toimitettu'] == '') {
												$row['kommentti'] .= "\n******* ".t("Toimitetaan erikseen",$kieli).". *******";
											}
											else {
												$row['kommentti'] .= "\n******* ".t("Toimitettu erikseen tilauksella",$kieli)." ".$row['otunnus'].". *******";
											}

											$row['rivihinta'] 	= "";
											$row['varattu'] 	= "";
											$row['kpl']			= "";
											$row['jt'] 			= "";
											$row['d_erikseen'] 	= "JOO";

											$params_lahete["row"] = $row;
											$params_lahete = rivi_lahete($params_lahete);
										}
									}
								}

								// Loppulaatikot
								$params_lahete["tots"] = 1;
								$params_lahete = loppu_lahete($params_lahete);

								//katotaan onko laskutus nouto
								$query = "  SELECT toimitustapa.nouto, maksuehto.kateinen
											FROM lasku
											JOIN toimitustapa ON lasku.yhtio = toimitustapa.yhtio and lasku.toimitustapa = toimitustapa.selite
											JOIN maksuehto ON lasku.yhtio = maksuehto.yhtio and lasku.maksuehto = maksuehto.tunnus
											WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tunnus = '$laskurow[tunnus]'
											and toimitustapa.nouto != '' and maksuehto.kateinen = ''";
								$kures = mysql_query($query) or pupe_error($query);

								if (mysql_num_rows($kures) > 0 and $yhtiorow["lahete_nouto_allekirjoitus"] != "") {
									$params_lahete = kuittaus_lahete($params_lahete);
								}

								//tulostetaan sivu
								if ($lahetekpl > 1 and $komento != "email") {
									$komento .= " -#$lahetekpl ";
								}

								if ($yhtiorow["keraysvahvistus_lahetys"] == "o" and $laskurow['email'] != "") {
									$komento = array($komento);
									$komento[] = "asiakasemail".$laskurow['email'];
								}

								$params_lahete["komento"] = $komento;

								//tulostetaan sivu
								print_pdf_lahete($params_lahete);
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

							if ($oslappkpl > 1) {
								$oslapp .= " -#$oslappkpl ";
							}

							$query = "SELECT osoitelappu FROM toimitustapa WHERE yhtio = '$kukarow[yhtio]' and selite = '$laskurow[toimitustapa]'";
							$oslares = mysql_query($query) or pupe_error($query);
							$oslarow = mysql_fetch_assoc($oslares);

							if ($oslarow['osoitelappu'] == 'intrade') {
								require('osoitelappu_intrade_pdf.inc');
							}
							else {
								require ("osoitelappu_pdf.inc");
							}
						}
					}

					echo "<br><br>";
				}
			}

			$boob    			= '';
			$header  			= '';
			$content 			= '';
			$rivit   			= '';

			if ($yhtiorow['karayksesta_rahtikirjasyottoon'] == 'Y' or ($yhtiorow['karayksesta_rahtikirjasyottoon'] == 'H' and $rahtikirjalle != "")) {
				$query = "	SELECT tunnus
							FROM lasku
							WHERE lasku.yhtio 	= '$kukarow[yhtio]'
							and lasku.tila 		= 'L'
							and lasku.alatila 	= 'C'
							and tunnus = '$id'";
				$result = mysql_query($query) or pupe_error($query);

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
		echo "<form action='$PHP_SELF' name='find' method='post'>";
		echo "<input type='hidden' name='toim' value='$toim'>";
		echo "<input type='hidden' id='jarj' name='jarj' value='$jarj'>";
		echo "<tr><td>".t("Valitse varasto:")."</td><td><select name='tuvarasto' onchange='submit()'>";

		$query = "	SELECT yhtio, tunnus, nimitys
					FROM varastopaikat
					WHERE $logistiikka_yhtiolisa
					ORDER BY yhtio, tyyppi, nimitys";
		$result = mysql_query($query) or pupe_error($query);

		echo "<option value='KAIKKI'>".t("N�yt� kaikki")."</option>";

		while ($row = mysql_fetch_assoc($result)) {
			$sel = '';
			if (($row['tunnus'] == $tuvarasto) or ((isset($kukarow["varasto"]) and (int) $kukarow["varasto"] > 0 and in_array($row['tunnus'], explode(",", $kukarow['varasto']))) and $tuvarasto=='')) {
				$sel = 'selected';
				$tuvarasto = $row['tunnus'];
			}
			echo "<option value='$row[tunnus]' $sel>$row[nimitys]";
			if ($logistiikka_yhtio != '') {
				echo " ($row[yhtio])";
			}
			echo "</option>";
		}
		echo "</select>";

		$query = "	SELECT distinct maa
					FROM varastopaikat
					WHERE maa != ''
					and $logistiikka_yhtiolisa
					ORDER BY maa";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 1) {
			echo "<select name='tumaa' onchange='submit()'>";
			echo "<option value=''>".t("Kaikki")."</option>";

			while ($row = mysql_fetch_assoc($result)){
				$sel = '';
				if ($row['maa'] == $tumaa) {
					$sel = 'selected';
					$tumaa = $row['maa'];
				}
				echo "<option value='$row[maa]' $sel>$row[maa]</option>";
			}
			echo "</select>";
		}

		echo "</td>";

		echo "<td>".t("Valitse tilaustyyppi:")."</td><td><select name='tutyyppi' onchange='submit()'>";

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
		echo "<option value='KAIKKI'>".t("N�yt� kaikki")."</option>";
		echo "<option value='NORMAA' $sela>".t("N�yt� normaalitilaukset")."</option>";
		echo "<option value='ENNAKK' $selb>".t("N�yt� ennakkotilaukset")."</option>";
		echo "<option value='JTTILA' $selc>".t("N�yt� jt-tilaukset")."</option>";

		echo "</select></td></tr>";

		echo "<tr><td>".t("Valitse toimitustapa:")."</td><td><select name='tutoimtapa' onchange='submit()'>";

		$query = "	SELECT selite, min(tunnus) tunnus
					FROM toimitustapa
					WHERE $logistiikka_yhtiolisa
					GROUP BY selite
					ORDER BY selite";
		$result = mysql_query($query) or pupe_error($query);

		echo "<option value='KAIKKI'>".t("N�yt� kaikki")."</option>";

		while ($row = mysql_fetch_assoc($result)){
			$sel = '';
			if ($row['selite'] == $tutoimtapa) {
				$sel = 'selected';
				$tutoimtapa = $row['selite'];
			}
			echo "<option value='$row[selite]' $sel>".t_tunnus_avainsanat($row, "selite", "TOIMTAPAKV")."</option>";
		}

		echo "</select></td>";

		echo "<td>".t("Etsi tilausta").":</td><td><input type='text' name='etsi'>";
		echo "<input type='Submit' value='".t("Etsi")."'></form></td></tr>";

		echo "</table>";

		$haku = '';

		if (!is_numeric($etsi) and $etsi != '') {
			$haku .= "and lasku.nimi LIKE '%$etsi%'";
		}

		if (is_numeric($etsi) and $etsi != '') {
			$haku .= "and lasku.tunnus='$etsi'";
		}

		if ($tuvarasto != '' and $tuvarasto != 'KAIKKI') {
			$haku .= " and lasku.varasto='$tuvarasto' ";
		}

		if ($tumaa != '') {
			$query = "	SELECT group_concat(tunnus) tunnukset
						FROM varastopaikat
						WHERE maa != '' and $logistiikka_yhtiolisa and maa = '$tumaa'";
			$maare = mysql_query($query) or pupe_error($query);
			$maarow = mysql_fetch_assoc($maare);
			$haku .= " and lasku.varasto in ($maarow[tunnukset]) ";
		}

		if ($tutoimtapa != '' and $tutoimtapa != 'KAIKKI') {
			$haku .= " and lasku.toimitustapa='$tutoimtapa' ";
		}

		if ($tutyyppi != '' and $tutyyppi != 'KAIKKI') {
			if ($tutyyppi == "NORMAA") {
				$haku .= " and lasku.clearing='' ";
			}
			elseif ($tutyyppi == "ENNAKK") {
				$haku .= " and lasku.clearing='ENNAKKOTILAUS' ";
			}
			elseif ($tutyyppi == "JTTILA") {
				$haku .= " and lasku.clearing='JT-TILAUS' ";
			}
		}

		if ($jarj != "") {
			$jarjx = " ORDER BY $jarj";
		}
		else {
			$jarjx = " ORDER BY laadittu";
		}

		if ($toim == "VASTAANOTA_REKLAMAATIO") {
			$alatilareklamaatio = 'C';
		}
		else {
			$alatilareklamaatio = 'A';
		}

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
					group_concat(DISTINCT concat_ws('\n\n', if (comments!='',concat('".t("L�hetteen lis�tiedot").":\n',comments),NULL), if (sisviesti2!='',concat('".t("Ker�yslistan lis�tiedot").":\n',sisviesti2),NULL)) SEPARATOR '\n') ohjeet,
					min(if (lasku.hyvaksynnanmuutos = '', 'X', lasku.hyvaksynnanmuutos)) prioriteetti,
					min(if (lasku.clearing = '', 'N', if (lasku.clearing = 'JT-TILAUS', 'J', if (lasku.clearing = 'ENNAKKOTILAUS', 'E', '')))) t_tyyppi,
					#(select nimitys from varastopaikat where varastopaikat.tunnus=min(lasku.varasto)) varastonimi,
					count(*) riveja,
					lasku.yhtio yhtio,
					lasku.yhtio_nimi yhtio_nimi
					from lasku use index (tila_index)
					JOIN tilausrivi use index (yhtio_otunnus) ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus
					WHERE lasku.$logistiikka_yhtiolisa
					and lasku.tila					in ($tila)
					and lasku.alatila				= '$alatilareklamaatio'
					and tilausrivi.tyyppi			in ($tyyppi)
					and tilausrivi.var				in ('', 'H' $var_lisa)
					and tilausrivi.keratty	 		= ''
					and tilausrivi.kerattyaika		= '0000-00-00 00:00:00'
					and ((tilausrivi.laskutettu		= ''
					and tilausrivi.laskutettuaika 	= '0000-00-00') or lasku.mapvm != '0000-00-00')
					$haku
					$tilaustyyppi
					GROUP BY tunnus
					$jarjx";
		$result = mysql_query($query) or pupe_error($query);

		//piirret��n taulukko...
		if (mysql_num_rows($result)!=0) {

			echo "<br><table>";

			echo "<tr>";
			if ($logistiikka_yhtio != '') {
				echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='yhtio'; document.forms['find'].submit();\">".t("Yhti�")."</a></th>";
			}
			echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='prioriteetti'; document.forms['find'].submit();\">".t("Pri")."</a><br>";
			//echo "<a href='#' onclick=\"getElementById('jarj').value='varastonimi'; document.forms['find'].submit();\">".t("Varastoon")."</a></th>";
			echo "<a href='#'>".t("Varastoon")."</a></th>";

			echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='tunnus'; document.forms['find'].submit();\">".t("Tilaus")."</a></th>";

			echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='ytunnus'; document.forms['find'].submit();\">".t("Asiakas")."</a><br>
					  <a href='#' onclick=\"getElementById('jarj').value='asiakas'; document.forms['find'].submit();\">".t("Nimi")."</a></th>";


			echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='laadittu'; document.forms['find'].submit();\">".t("Laadittu")."</a><br>
				  	  <a href='#' onclick=\"getElementById('jarj').value='lasku.h1time'; document.forms['find'].submit();\">".t("Valmis")."</a><br>
						<a href='#' onclick=\"getElementById('jarj').value='lasku.lahetepvm'; document.forms['find'].submit();\">".t("Tulostettu")."</a></th>";

			echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='kerayspvm'; document.forms['find'].submit();\">".t("Ker�ysaika")."</a><br>
					  <a href='#' onclick=\"getElementById('jarj').value='toimaika'; document.forms['find'].submit();\">".t("Toimitusaika")."</a></th>";

			echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='toimitustapa'; document.forms['find'].submit();\">".t("Toimitustapa")."</a></th>";
			echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='riveja'; document.forms['find'].submit();\">".t("Riv")."</a></th>";
			echo "<th valign='top'>".t("Ker��")."</th>";

			echo "</tr></form>";

			$riveja_yht = 0;

			while ($row = mysql_fetch_assoc($result)) {
				echo "<tr class='aktiivi'>";

				if ($logistiikka_yhtio != '') {
					echo "<td valign='top'>$row[yhtio_nimi]</td>";
				}

				if (trim($row["ohjeet"]) != "") {
					echo "<div id='div_$row[tunnus]' class='popup' style='width: 500px;'>";
					echo t("Tilaukset").": ".$row["tunnukset"]."<br>";
					echo t("Laatija").": ".$row["laatija"]."<br><br>";
					echo str_replace("\n", "<br>", $row["ohjeet"])."<br>";
					echo "</div>";

					echo "<td valign='top' class='tooltip' id='$row[tunnus]'>$row[t_tyyppi] $row[prioriteetti] <img src='{$palvelin2}pics/lullacons/info.png'>";
				}
				else {
					echo "<td valign='top'>$row[t_tyyppi] $row[prioriteetti]";
				}

				if (isset($row['varastonimi'])) echo "<br>$row[varastonimi]";

				echo "</td>";
				echo "<td valign='top'>$row[tunnus]</td>";
				echo "<td valign='top'>$row[ytunnus]<br>$row[asiakas]</td>";

				$laadittu_e 	= tv1dateconv($row["laadittu"], "P", "LYHYT");
				$h1time_e		= tv1dateconv($row["h1time"], "P", "LYHYT");
				$lahetepvm_e	= tv1dateconv($row["lahetepvm"], "P", "LYHYT");
				$lahetepvm_e	= str_replace(substr($h1time_e, 0, strpos($h1time_e, " ")), "", $lahetepvm_e);
				$h1time_e		= str_replace(substr($laadittu_e, 0, strpos($laadittu_e, " ")), "", $h1time_e);

				echo "<td valign='top' nowrap align='right'>$laadittu_e<br>$h1time_e<br>$lahetepvm_e</td>";
				echo "<td valign='top' nowrap align='right'>".tv1dateconv($row["kerayspvm"], "", "LYHYT")."<br>".tv1dateconv($row["toimaika"], "", "LYHYT")."</td>";
				echo "<td valign='top'>$row[toimitustapa]</td>";
				echo "<td valign='top'>$row[riveja]</td>";

				$riveja_yht += $row['riveja'];

				echo "<td valign='top'>
						<form method='post' action='$PHP_SELF'>
						<input type='hidden' name='id' value='$row[tunnus]'>
						<input type='hidden' name='toim' value='$toim'>
						<input type='hidden' name='lasku_yhtio' value='$row[yhtio]'>
						<input type='submit' name='tila' value='".t("Ker��")."'></form></td></tr>";
			}

			$spanni = $logistiikka_yhtio != '' ? 7 : 6;

			echo "<tr>";
			echo "<td colspan='$spanni' style='text-align:right;' class='back'>".t("Rivej� yhteens�").":</td>";
			echo "<td valign='top' class='back'>$riveja_yht</td>";
			echo "</tr>";

			echo "</table>";
		}
		else {
			echo "<font class='message'>".t("Yht��n ker��m�t�nt� tilausta ei l�ytynyt")."...</font>";
		}
	}

	if ($id != 0 and (!isset($rahtikirjaan) or $rahtikirjaan == '')) {

		//p�ivit� ker�tyt formi
		$formi	= "rivit";
		$kentta	= "keraajanro";

		$otsik_row = array();
		$keraysklontti = FALSE;

		$query = "	SELECT kerayslista, varasto
					from lasku
					where yhtio	= '$kukarow[yhtio]'
					and tunnus	= '$id'";
		$testresult = mysql_query($query) or pupe_error($query);
		$testrow = mysql_fetch_assoc($testresult);

		// Koko kl�ntti kuuluu aina samaan varastoon joten otetaan t�m� t�ss� talteen
		$lp_varasto = $testrow["varasto"];

		if ($testrow['kerayslista'] > 0) {
			//haetaan kaikki t�lle kl�ntille kuuluvat otsikot
			$query = "	SELECT GROUP_CONCAT(DISTINCT tunnus ORDER BY tunnus SEPARATOR ',') tunnukset
						FROM lasku
						WHERE yhtio		= '$kukarow[yhtio]'
						and kerayslista	= '$id'
						and kerayslista != 0
						and tila		in ($tila)
						$tilaustyyppi
						HAVING tunnukset is not null";
			$toimresult = mysql_query($query) or pupe_error($query);

			//jos rivej� l�ytyy niin tiedet��n, ett� t�m� on ker�yskl�ntti
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

		echo "<table>";

		if ($toim == 'SIIRTOLISTA')	 {
			echo "<tr><th align='left'>".t("Siirtolista")."</th><td>$id</td></tr>";
		}
		if ($toim == 'MYYNTITILI')	 {
			echo "<tr><th align='left'>".t("Myyntitili")."</th><td>$id</td></tr>";
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
						LEFT JOIN toimitustapa ON lasku.yhtio = toimitustapa.yhtio and lasku.toimitustapa = toimitustapa.selite
						WHERE lasku.tunnus in ($tilausnumeroita)
						and lasku.yhtio = '$kukarow[yhtio]'
						and lasku.tila in ($tila)
						and lasku.alatila = '$alatilareklamaatio'";

			$result = mysql_query($query) or pupe_error($query);
			$otsik_row = mysql_fetch_assoc($result);

			echo "<tr><th>".t("Tilaus")."</th><th>".t("Ostaja") ."</th><th>".t("Toimitusosoite") ."</th></tr>";

			echo "<tr><td>$tilausnumeroita<br>$otsik_row[clearing]";

			if ($toim == 'VASTAANOTA_REKLAMAATIO') {
				echo "<br><form action='tilaus_myynti.php' method='POST'>";
				echo "<input type='hidden' name='tee' value = 'AKTIVOI'>";
				echo "<input type='hidden' name='toim' value = 'REKLAMAATIO'>";
				echo "<input type='hidden' name='tilausnumero' value = '$tilausnumeroita'>";
				echo "<input type='hidden' name='mista' value = 'keraa'>";
				echo "<input type='submit' value='".t("Muokkaa")."'/> ";
				echo "</form>";
			}
			echo "</td>";

			echo "<td>$otsik_row[nimi] $otsik_row[nimitark]<br> $otsik_row[osoite]<br> $otsik_row[postino] $otsik_row[postitp]<br>".maa($otsik_row["maa"])."</td>";
			echo "<td>$otsik_row[toim_nimi] $otsik_row[toim_nimitark]<br> $otsik_row[toim_osoite]<br> $otsik_row[toim_postino] $otsik_row[toim_postitp]<br> ".maa($otsik_row["toim_maa"])."</td></tr>";
		}

		echo "</table>";

		$select_lisa 	= "tilausrivi.tilkpl, tilausrivi.varattu, tilausrivi.jt,";
		$where_lisa 	= "";
		$pjat_sortlisa 	= "";

		if ($toim == "VALMISTUS") {
			$sorttauskentta = generoi_sorttauskentta($yhtiorow["valmistus_kerayslistan_jarjestys"]);
			$order_sorttaus = $yhtiorow["valmistus_kerayslistan_jarjestys_suunta"];

			if ($yhtiorow["valmistus_kerayslistan_palvelutjatuottet"] == "E") $pjat_sortlisa = "tuotetyyppi,";

			// Summataan rivit yhteen (HUOM! unohdetaan kaikki perheet!)
			if ($yhtiorow["valmistus_kerayslistan_jarjestys"] == "S") {
				$select_lisa = "sum(tilausrivi.tilkpl) tilkpl, sum(tilausrivi.varattu) varattu, sum(tilausrivi.jt) jt, group_concat(tilausrivi.tunnus) rivitunnukset,";
				$where_lisa = "GROUP BY tilausrivi.tuoteno, tilausrivi.hyllyalue, tilausrivi.hyllyvali, tilausrivi.hyllyalue, tilausrivi.hyllynro";
			}
		}
		else {
			$sorttauskentta = generoi_sorttauskentta($yhtiorow["kerayslistan_jarjestys"]);
			$order_sorttaus = $yhtiorow["kerayslistan_jarjestys_suunta"];

			if ($yhtiorow["kerayslistan_palvelutjatuottet"] == "E") $pjat_sortlisa = "tuotetyyppi,";

			// Summataan rivit yhteen (HUOM! unohdetaan kaikki perheet!)
			if ($yhtiorow["kerayslistan_jarjestys"] == "S") {
				$select_lisa = "sum(tilausrivi.tilkpl) tilkpl, sum(tilausrivi.varattu) varattu, sum(tilausrivi.jt) jt, group_concat(tilausrivi.tunnus) rivitunnukset,";
				$where_lisa = "GROUP BY tilausrivi.tuoteno, tilausrivi.hyllyalue, tilausrivi.hyllyvali, tilausrivi.hyllyalue, tilausrivi.hyllynro";
			}
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
					if (tuote.tuotetyyppi='K','2 Ty�t','1 Muut') tuotetyyppi
					FROM tilausrivi
					JOIN tuote ON tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno
					JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
					WHERE tilausrivi.yhtio	= '$kukarow[yhtio]'
					and tilausrivi.otunnus in ($tilausnumeroita)
					and tilausrivi.var in ('', 'H' $var_lisa)
					and tilausrivi.tyyppi in ($tyyppi)
					and tilausrivi.kerattyaika = '0000-00-00 00:00:00'
					$where_lisa
					ORDER BY $pjat_sortlisa sorttauskentta $order_sorttaus, tilausrivi.tunnus";
		$result = mysql_query($query) or pupe_error($query);
		$riveja = mysql_num_rows($result);

		if ($riveja > 0) {
			echo "<form name = 'rivit' method='post' action='$PHP_SELF' autocomplete='off'>";
			echo "	<input type='hidden' name='tee' value='P'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='id'  value='$id'>";

			echo "<br>";
			echo "<table>";
			echo "<tr><th>".t("Ker��j�")."</th><td><input type='text' size='5' name='keraajanro'> ".t("tai")." ";
			echo "<select name='keraajalist'>";

			$query = "	SELECT *
						from kuka
						where yhtio = '$kukarow[yhtio]'
						and extranet = ''
						and (keraajanro > 0 or kuka = '$kukarow[kuka]')";
			$kuresult = mysql_query($query) or pupe_error($query);

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
				$lokero_chk_res = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($lokero_chk_res) > 0) {
					$lokero_chk_row = mysql_fetch_assoc($lokero_chk_res);
					echo "<tr><th>".t("Pakkaamo")."</th><td>$lokero_chk_row[nimi]</td></tr><tr><th>".t("Lokero")."</th><td>$lokero_chk_row[lokero]</td></tr>";
				}
			}

			if ($otsik_row["tulostustapa"] != "X" and $yhtiorow['karayksesta_rahtikirjasyottoon'] == 'H' and $keraysklontti === FALSE) {
				echo "<tr><th>".t("Siirry rahtikirjan sy�tt��n")."</th><td><input type='checkbox' name='rahtikirjalle'>".t("Kyll�")."</td></tr>";
			}

			echo "</table><br>";

			echo "<table>
					<tr>
					<th>".t("Varastopaikka")."</th>
					<th>".t("Tuoteno")."</th>
					<th>".t("M��r�")."</th>
					<th>".t("Poikkeava m��r�")."</th>";

			$colspanni = 4;

			if ($yhtiorow["kerayspoikkeama_kasittely"] != '') {
				echo "<th>".t("Poikkeaman k�sittely")."</th>";
				$colspanni++;
			}

			echo "</tr>";

			$i = 0;

			while ($row = mysql_fetch_assoc($result)) {

				if ($row['var'] == 'P') {
					// jos kyseess� on puuterivi
					$puute 			= t("PUUTE");
					$row['varattu']	= $row['tilkpl'];
				}
				elseif ($row['var'] == 'J') {
					// jos kyseess� on JT-rivi
					$puute 			= t("**JT**");

					if ($yhtiorow["varaako_jt_saldoa"] == "") {
						$row['varattu']	= $row['jt'];
					}
					else {
						$row['varattu']	= $row['jt']+$row['varattu'];
					}
				}
				elseif ($row['var']=='H') {
					// jos kyseess� on v�kisinhyv�ksytty-rivi
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
					$puute .= " ".t("Verkkokaupassa etuk�teen maksettu tuote!");
				}

				// Reklamaation m��r�t ly�d��n lukkoon "vastaanota reklamaatio" vaiheessa
				if ($toim == 'VASTAANOTA_REKLAMAATIO') {
					$poikkeava_maara_disabled = "disabled";
				}

				if ($row['ei_saldoa'] != '') {
					echo "	<tr class='aktiivi'>
							<td>*</td>
							<td>$row[tuoteno]</td>
							<td>$row[varattu]</td>
							<td>".t("Saldoton tuote")."</td>";

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
						$results2 = mysql_query($query) or pupe_error($query);

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
						if ($yhtiorow['kerayserat'] == 'K' and trim($kukarow['keraysvyohyke']) != '' and $toim == "") {
							echo "<span id='maaran_paivitys_{$row['tunnus']}'></span>";
							echo "<input type='hidden' name='maara[$row[tunnus]]' id='maara_{$row['tunnus']}' value='' />";
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
						$sarjares = mysql_query($query) or pupe_error($query);
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
						$omavarastores = mysql_query($query) or pupe_error($query);

						$paikat 	= "<option value=''>".t("Valitse er�")."</option>";
						$selpaikka 	= "";

						$query	= "	SELECT sarjanumeroseuranta.sarjanumero era, sarjanumeroseuranta.parasta_ennen
				   					FROM sarjanumeroseuranta
				   					WHERE yhtio = '$kukarow[yhtio]'
									and tuoteno = '$row[puhdas_tuoteno]'
				   					and $tunken1 = '$row[tunnus]'
				   					LIMIT 1";
				   		$sarjares = mysql_query($query) or pupe_error($query);
				   		$sarjarow = mysql_fetch_assoc($sarjares);

						echo t("Er�").": ";

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

								//Jos er� on keksitty k�sin t��lt� ker�yksest�
								$query = "	SELECT tyyppi, (varattu+kpl+jt) kpl, tunnus, laskutettu
											FROM tilausrivi
											WHERE yhtio = '$kukarow[yhtio]'
											and tuoteno = '$row[puhdas_tuoteno]'
											and tunnus	= '$alkurow[ostorivitunnus]'";
								$lisa_res = mysql_query($query) or pupe_error($query);
								$lisa_row = mysql_fetch_assoc($lisa_res);

								// varmistetaan, ett� t�m� er� on k�ytett�viss�, eli ostorivitunnus pointtaa ostoriviin, hyvitysriviin tai laskutettuun myyntiriviin tai t�h�n riviin itsess��n
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
							echo "<option value='' SELECTED>".t("Ei k�sitell�")."</option>";
						}

						echo "<option value='JT' $selpk_JT>".t("JT")."</option>";
						echo "<option value='PU' $selpk_PU>".t("Puute")."</option>";

						if ($row["sarjanumeroseuranta"] == "E" or $row["sarjanumeroseuranta"] == "F" or $row["sarjanumeroseuranta"] == "G") {
							echo "<option value='UR' $selpk_UR>".t("Uusi rivi")."</option>";
						}

						echo "<option value='UT'>".t("Uusi tilaus")."</option>";
						echo "<option value='MI'>".t("Mit�t�i")."</option>";
						echo "</select></td>";
					}

					echo "</tr>";

					if ($yhtiorow['kerayserat'] == 'K' and trim($kukarow['keraysvyohyke']) != '' and $toim == "") {
						$query = "	SELECT *
									FROM kerayserat
									WHERE yhtio = '{$kukarow['yhtio']}'
									AND tilausrivi = '{$row['tunnus']}'
									ORDER BY pakkausnro ASC";
						$keraysera_res = mysql_query($query) or pupe_error($query);

						echo "<tr><td colspan='{$colspanni}'>";

						while ($keraysera_row = mysql_fetch_assoc($keraysera_res)) {
							echo chr((64+$keraysera_row['pakkausnro']))," <input type='text' name='keraysera_maara[{$keraysera_row['tunnus']}]' id='{$row['tunnus']}_{$keraysera_row['tunnus']}' value='{$keraysera_row['kpl']}' size='4' />&nbsp;";
						}

						echo "</td></tr>";
					}

				}
				$i++;
			}

			// Jos kyseess� ei ole valmistus tulostetaan virallinen l�hete
			$sel 		= "SELECTED";
			$oslappkpl 	= 0;
			$lahetekpl  = 0;

			if ($toim != 'VALMISTUS' and $otsik_row["tila"] != 'V') {
				$oslappkpl 	= $yhtiorow["oletus_oslappkpl"];
				$lahetekpl 	= $yhtiorow["oletus_lahetekpl"];
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
				$kirre = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($kirre) > 0 and $yhtiorow['pakkaamolokerot'] == '') {

					$prirow = mysql_fetch_assoc($kirre);

					// k�teinen muuttuja viritet��n tilaus-valmis.inc:iss� jos maksuehto on k�teinen
					// ja silloin pit�� kaikki l�hetteet tulostaa aina printteri5:lle (lasku printteri)
					if ($kateinen == 'X') {
						$sel_lahete[$prirow['printteri5']] = "SELECTED";	// laskuprintteri
						$sel_oslapp[$prirow['printteri5']] = "SELECTED";	// osoitelappuprintteri
					}
					else {
						$sel_lahete[$prirow['printteri1']] = "SELECTED";	// laskuprintteri
						$sel_oslapp[$prirow['printteri3']] = "SELECTED";	// osoitelappuprintteri
					}
				}

				echo "<tr><th>".t("L�hete").":</th><th colspan='$spanni'>";

				$query = "	SELECT *
							FROM kirjoittimet
							WHERE yhtio = '$kukarow[yhtio]'
							ORDER by kirjoitin";
				$kirre = mysql_query($query) or pupe_error($query);

				echo "<select name='valittu_tulostin'>";
				echo "<option value=''>".t("Ei tulosteta")."</option>";

				while ($kirrow = mysql_fetch_assoc($kirre)) {
					echo "<option value='$kirrow[tunnus]' ".$sel_lahete[$kirrow["tunnus"]].">$kirrow[kirjoitin]</option>";
				}

				echo "</select> ".t("Kpl").": <input type='text' size='4' name='lahetekpl' value='$lahetekpl'>";

				if ($yhtiorow["lahete_tyyppi_tulostus"] != '') {
					echo " ".t("L�hetetyyppi").": <select name='sellahetetyyppi'>";

					$query2 = "	SELECT lahetetyyppi
								FROM lasku
								JOIN asiakas on lasku.yhtio = asiakas.yhtio and lasku.liitostunnus = asiakas.tunnus
								WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tunnus = '$id'";
					$vresult2 = mysql_query($query2) or pupe_error($query2);
					$row2 = mysql_fetch_assoc($vresult2);

					$vresult = t_avainsana("LAHETETYYPPI");

					while ($row = mysql_fetch_assoc($vresult)) {
						$sel = "";
						if ($row["selite"] == $row2["lahetetyyppi"]) $sel = 'selected';
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
					echo "<option value='$kirrow[tunnus]' ".$sel_oslapp[$kirrow["tunnus"]].">$kirrow[kirjoitin]</option>";
				}

				echo "</select> ".t("Kpl").": <input type='text' size='4' name='oslappkpl' value='$oslappkpl'></th>";

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
				echo "<input type='submit' name='real_submit' id='real_submit' value='".t("Merkkaa ker�tyksi")."'></form>";
			}
			else {
				echo "<input type='submit' name='real_submit' id='real_submit' value='".t("Merkkaa toimitetuksi")."'></form>";
			}

			if ($otsik_row["tulostustapa"] != "X" and $yhtiorow['karayksesta_rahtikirjasyottoon'] == 'Y') {
				echo "<br><br><font class='message'>".t("Siirryt automaattisesti rahtikirjan sy�tt��n")."!</font>";
			}
			elseif ($otsik_row["tulostustapa"] != "X" and $yhtiorow['karayksesta_rahtikirjasyottoon'] == 'H' and $keraysklontti === FALSE) {
				echo "<br><br><font class='message'>".t("Voit halutessasi siirty� rahtikirjan sy�tt��n")."!</font>";
			}
		}
		else {
			echo t("T�ll� tilauksella ei ole yht��n ker�tt�v�� rivi�!");
		}
	}

	if (isset($rahtikirjaan) and $rahtikirjaan == 'mennaan') {
		echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL={$palvelin2}rahtikirja.php?toim=lisaa&id=$id&rakirno=$id&tunnukset=$tilausnumeroita&mista=keraa.php'>";
	}

	require ("inc/footer.inc");
?>