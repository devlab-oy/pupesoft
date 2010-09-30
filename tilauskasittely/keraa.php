<?php

	require ("../inc/parametrit.inc");

	$logistiikka_yhtio = '';
	$logistiikka_yhtiolisa = '';

	if ($yhtiorow['konsernivarasto'] != '' and $konsernivarasto_yhtiot != '') {
		$logistiikka_yhtio = $konsernivarasto_yhtiot;
		$logistiikka_yhtiolisa = "yhtio in ($logistiikka_yhtio)";

		if ($lasku_yhtio != '') {
			$kukarow['yhtio'] = mysql_real_escape_string($lasku_yhtio);

			$yhtiorow = hae_yhtion_parametrit($lasku_yhtio);
		}
	}
	else {
		$logistiikka_yhtiolisa = "yhtio = '$kukarow[yhtio]'";
	}

	js_popup();

	if ($toim == 'SIIRTOLISTA') {
		echo "<font class='head'>".t("Kerää siirtolista").":</font><hr>";
		$tila = "'G'";
		$tyyppi = "'G'";
		$tilaustyyppi = " and tilaustyyppi!='M' ";
	}
	elseif ($toim == 'SIIRTOTYOMAARAYS') {
		echo "<font class='head'>".t("Kerää sisäinen työmääräys").":</font><hr>";
		$tila = "'S'";
		$tyyppi = "'G'";
		$tilaustyyppi = " and tilaustyyppi='S' ";
	}
	elseif ($toim == 'MYYNTITILI') {
		echo "<font class='head'>".t("Kerää myyntitili").":</font><hr>";
		$tila = "'G'";
		$tyyppi = "'G'";
		$tilaustyyppi = " and tilaustyyppi='M' ";
	}
	elseif ($toim == 'VALMISTUS') {
		echo "<font class='head'>".t("Kerää valmistus").":</font><hr>";
		$tila = "'V'";
		$tyyppi = "'V','L'";
		$tilaustyyppi = "";
	}
	elseif ($toim == 'VALMISTUSMYYNTI') {
		echo "<font class='head'>".t("Kerää tilaus tai valmistus").":</font><hr>";
		$tila = "'V','L'";
		$tyyppi = "'V','L'";
		$tilaustyyppi = "";
	}
	else {
		echo "<font class='head'>".t("Kerää tilaus").":</font><hr>";
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

	$keraysvirhe = 0;
	$muuttuiko	 = '';

	if ($tee == 'P') {

		$query = "SELECT kerayslista from lasku where yhtio = '$kukarow[yhtio]' and tunnus = '$id'";
		$testresult = mysql_query($query) or pupe_error($query);
		$testrow = mysql_fetch_assoc($testresult);


		if ($testrow['kerayslista'] > 0) {
			//haetaan kaikki tälle klöntille kuuluvat otsikot
			$query = "	SELECT GROUP_CONCAT(DISTINCT tunnus ORDER BY tunnus SEPARATOR ',') tunnukset
						FROM lasku
						WHERE yhtio		= '$kukarow[yhtio]'
						and kerayslista	= '$id'
						and kerayslista != 0
						and tila		in ($tila)
						$tilaustyyppi
						HAVING tunnukset is not null";
			$toimresult = mysql_query($query) or pupe_error($query);

			//jos rivejä löytyy niin tiedetään, että tämä on keräysklöntti
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

		// katotaan aluks onko yhtään tuotetta sarjanumeroseurannassa tällä keräyslistalla
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
						echo "<font class='error'>".t("Sarjanumeroseurannassa oleville tuotteille on liitettävä sarjanumero ennen keräystä")."! ".t("Tuote").": $toimrow[tuoteno].</font><br>";
						$keraysvirhe++;
					}
				}
				else {

					//Siivotaan hieman käyttäjän syöttämää kappalemäärää
					$eratsekkpl = (float) str_replace( ",", ".", $maara[$toimrow["tunnus"]]);

					// Muutetaanko eräseurattavan tuotteen kappalemäärää
					if (trim($maara[$toimrow["tunnus"]]) != '' and $eratsekkpl >= 0) {
						if ($eratsekkpl < $toimrow["varattu"]) {
							//Jos erä on keksitty käsin täältä keräyksestä
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

					// Päivitetään erä
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
							echo "<font class='error'>".t("Eränumeroseurannassa oleville tuotteille on liitettävä eränumero ennen keräystä")."! ".t("Tuote").": $toimrow[tuoteno].</font><br>";
							$keraysvirhe++;
						}
					}
				}
			}
		}
	}

	if ($tee == 'P') {

		if ((int) $keraajanro == 0) $keraajanro = $keraajalist;

		$tilausnumerot = array();
		$poikkeamat = array();

		$query = "SELECT * from kuka where yhtio='$kukarow[yhtio]' and keraajanro='$keraajanro'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result)==0) {
			echo "<font class='error'>".t("Kerääjää")." $keraajanro ".t("ei löydy")."!</font><br>";
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

						//Kysessä voi olla keräysklöntti, haetaan muuttuneen rivin otunnus
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

						//Aloitellaan tilausrivi päivitysqueryä
						$query = "	UPDATE tilausrivi
									SET yhtio = yhtio ";

						if ($tilrivirow["var"] != "J" and $keraysvirhe == 0 and $real_submit == 'yes') {
							//Muut kuin JT-rivit päivitetään aina kerätyiksi jos virhetsekit menivät ok
							$query .= ", keratty = '$who',
										 kerattyaika = now()";
						}

						// Käyttäjä on syöttänyt jonkun luvun, päivitetään vaikka virhetsekit menisivät pepulleen
						if (trim($maara[$apui]) != '') {

							// Siivotaan hieman käyttäjän syöttämää kappalemäärää
							$maara[$apui] = str_replace (",", ".", $maara[$apui]);
							$maara[$apui] = (float) $maara[$apui];

							// Kerätään tietoa poikkeama-maileja varten
							$poikkeamat[$tilrivirow["otunnus"]][$i]["tuoteno"] 	= $tilrivirow["tuoteno"];
							$poikkeamat[$tilrivirow["otunnus"]][$i]["nimitys"] 	= $tilrivirow["nimitys"];
							$poikkeamat[$tilrivirow["otunnus"]][$i]["tilkpl"] 	= $tilrivirow["tilkpl"];
							$poikkeamat[$tilrivirow["otunnus"]][$i]["var"] 		= $tilrivirow["var"];
							$poikkeamat[$tilrivirow["otunnus"]][$i]["maara"] 	= $maara[$apui];

							$rotunnus = 0;

							if ($tilrivirow["var"] == 'P' and $maara[$apui] > 0) {

								// Puuterivi löytyi poistetaan VAR
								$query .= "	, var		= ''
											, varattu	= '".$maara[$apui]."'";

								//Poistetaan 'tuote loppu'-kommentti jos tuotetta sittenkin löytyi
								$korvataan_pois = t("Tuote Loppu.");
								$query .= "	, kommentti	= replace(kommentti, '$korvataan_pois', '') ";

								// PUUTE-riville tehdään osatoimitus ja loput jätetään puuteriviksi
								if ($maara[$apui] < $tilrivirow['tilkpl']) {

									$poikkeamat[$tilrivirow["otunnus"]][$i]["loput"] = "Jätettiin puuteriviksi.";

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
								// JT-rivi löytyi, poistetaan VAR ja merkataan rivi kerätyksi, jos virhetsekit ok
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

								// JT-riville tehdään osatoimitus ja loput jätetään jälkitoimitukseen
								if ($maara[$apui] < $jtsek) {

									$poikkeamat[$tilrivirow["otunnus"]][$i]["loput"] = "Jätettiin JT-riviksi.";

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

								// Varastomiehellä on nyt oikeus nollata myös JT-rivi jos hän saa lähetettyä $poikkeama_kasittely[$apui] == "MI"
								if ($keraysvirhe == 0) {
									$query .= ", keratty = '$who',
												 kerattyaika = now()";
								}

								$query .= "	, var			= ''
											, jt			= 0
											, varattu		= 0";
							}
							elseif ((!isset($poikkeama_kasittely[$apui]) or $poikkeama_kasittely[$apui] == "") and $maara[$apui] >= 0 and $maara[$apui] < $tilrivirow['varattu'] and ($otsikkorivi['clearing'] == 'ENNAKKOTILAUS' or $otsikkorivi['clearing'] == 'JT-TILAUS')) {
								// Jos tämä on toimitettava ennakkotilaus tai jt-tilaus niin yritetään laittaa poikkeama jollekin sopivalle otsikolle
								// Tähän haaraan ei mennä jos poikkeamat ohjataan manuaalisesti

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

								// Etsitään sopiva otsikko jolle rivi laitetaan
								// Samat ehdot kuin tee_jt_tilaus.inc:ssä rivillä ~180
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

								// Sopiva otsikko löytyi
								if (mysql_num_rows($stresult) > 0) {
									$strow = mysql_fetch_assoc($stresult);

									// Sopivin otsikko oli dellattu, elvytetään se!
									if ($otsikkorivi['clearing'] == 'ENNAKKOTILAUS' and $strow["tila"] == "D") {

										// E, A - Ennakkotilaus lepäämässä
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
									// Laitetaan tälle otsikolle, voi mennä solmuun, mutta ei katoa ainakaan kokonaan
									$rotunnus = $tilrivirow['otunnus'];
								}
							}
							elseif ($tilrivirow["var"] != 'J' and $tilrivirow["var"] != 'P') {
								// Jos tämä on normaali rivi
								if ($maara[$apui] < 0) {
									// Jos kerääjä kuittaa alle nollan niin ei tehdä mitään
									$query .= ", varattu = varattu";

									$poikkeamat[$tilrivirow["otunnus"]][$i]["loput"] = "Määrä nollaa pienempi. Poikkeamaa ei hyväksytty.";
								}
								elseif ($maara[$apui] >= 0 and $maara[$apui] < $tilrivirow['varattu']) {

									$query .= ", varattu = '".$maara[$apui]."'";

									if ($poikkeama_kasittely[$apui] != "") {

										if ($maara[$apui] == 0) {
											// Mitätöidään nollarivi koska poikkeamalle kuitenkin tehdään jotain fiksua
											$query .= ", tyyppi = 'D', kommentti=trim(concat(kommentti, ' Mitätöitiin koska keräyspoikkeamasta tehtiin: ".$poikkeama_kasittely[$apui]."'))";
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
									//Päivitetään vain määrä jos se on isompi kuin alkuperäinen varattumäärä
									$query .= ", varattu = '".$maara[$apui]."'";
								}
							}

							if ($poikkeama_kasittely[$apui] != "" and $rotunnus != 0) {
								// Käyttäjän valitsemia poikkeamakäsittelysääntöjä
								if ($poikkeama_kasittely[$apui] == "PU") {

									$poikkeamat[$tilrivirow["otunnus"]][$i]["loput"] = "Jätettiin puuteriviksi.";

									// Riville tehdään osatoimitus ja loput jätetään puuteriviksi
									$rvarattu	= 0;
									$rjt  		= 0;
									$rvar		= "P";
									$keratty	= "'$who'";
									$kerattyaik	= "now()";
									$rkomm 		= t("Tuote Loppu.");
								}
								elseif ($poikkeama_kasittely[$apui] == "JT") {

									$poikkeamat[$tilrivirow["otunnus"]][$i]["loput"] = "Jätettiin JT-riviksi.";

									// Riville tehdään osatoimitus ja loput jätetään jälkkäriin (ennakkotilauksilla takaisin ennakkoon)
									if ($otsikkorivi['clearing'] == 'ENNAKKOTILAUS') {
										$rvarattu	= $rtilkpl;
										$rjt  		= 0;
										$rvar		= "";
									}
									else {
										if ($yhtiorow["varaako_jt_saldoa"] == "") {
											$rvarattu	= 0;
											$rjt  		= $rtilkpl;
										}
										else {
											$rvarattu	= $rtilkpl;
											$rjt  		= 0;
										}

										$rvar		= "J";
									}

									$keratty	= "''";
									$kerattyaik	= "''";
									$rkomm 		= $tilrivirow["kommentti"];
								}
								elseif ($poikkeama_kasittely[$apui] == "MI") {

									$poikkeamat[$tilrivirow["otunnus"]][$i]["loput"] = "Mitätöitiin.";

									// Riville tehdään osatoimitus ja loput mitätöidään
									$rotunnus	= 0;
								}
								elseif ($poikkeama_kasittely[$apui] == "UR") {

									$poikkeamat[$tilrivirow["otunnus"]][$i]["loput"] = "Siirrettiin uudelle riville.";

									// Riville tehdään osatoimitus ja loput kopsataan uudelle riville
									$rvarattu	= $rtilkpl;
									$rjt  		= 0;
									$rvar		= "";
									$keratty	= "''";
									$kerattyaik	= "''";
									$rkomm 		= $tilrivirow["kommentti"];
								}
								elseif ($poikkeama_kasittely[$apui] == "UT") {
									// Riville tehdään osatoimitus ja loput siirretään ihan uudelle tilaukselle
									if (!isset($tilausnumerot[$tilrivirow["otunnus"]])) {

										// Jotta saadaa lasku kopsattua kivasti jos se splittaantuu
										$laspliq = "SELECT *
													FROM lasku
													WHERE yhtio = '$kukarow[yhtio]'
													and tunnus = '$tilrivirow[otunnus]'";
										$laskusplitres = mysql_query($laspliq) or pupe_error($laspliq);
										$laskusplitrow = mysql_fetch_assoc($laskusplitres);

										if ($laskusplitrow["tunnusnippu"] == 0) {
											// Laitetaan uusi tilaus osatoimitukseksi alkuperäiselle tilaukselle
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

							// Tässä tehdään uusi rivi
							if ($rotunnus != 0) {

								// Aina jos rivi splitataan niin päivitetään alkuperäisen rivin tilkpl
								$query .= ", tilkpl = '".$maara[$apui]."'";

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
											ale 		= '$tilrivirow[ale]',
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

								$queryera = "SELECT sarjanumeroseuranta FROM tuote WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$tilrivirow[tuoteno]'";
								$sarjares2 = mysql_query($queryera) or pupe_error($queryera);
								$erarow = mysql_fetch_assoc($sarjares2);

								if ($erarow['sarjanumeroseuranta'] == 'E' or $erarow['sarjanumeroseuranta'] == 'F') {
									echo "<font class='error'>".t("Eränumeroseurannassa oleville tuotteille on liitettävä eränumero ennen keräystä")."! ".t("Tuote").": $tilrivirow[tuoteno].</font><br>";
								}
							}

							//päivitetään tuoteperheiden saldottomat jäsenet oikeisiin määriin (ne voi olla alkuperäiselläkin lähetteellä == vanhatunnus)
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

									// oltiin muokkaamassa isätuotteen kappalemäärää, päivitetään saldottomien lasten määrät kertoimella
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

						//päivitetään alkuperäinen rivi
						$query .= " WHERE tunnus='$kerivi[$i]' and yhtio='$kukarow[yhtio]'";
						$result = mysql_query($query) or pupe_error($query);
					}

					//Keräämätön rivi
					$keraamaton++;
				}
				else {
					echo t("HUOM: Tämä rivi oli jo kerätty! Ei voida kerätä uudestaan.")."<br>";
				}
			}
		}

		if ($keraysvirhe > 0) {
			$tee = '';
		}

		// Jos keräyspoikkeamia syntyi, niin lähetetään mailit myyjälle ja asiakkaalle
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

				$ulos .= "<font class='head'>".t("Keräyspoikkeamat", $kieli)."</font><hr><br><br><table>";

				$ulos .= "<tr><th>".t("Yhtiö", $kieli)."</th></tr>";
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
				$ulos .= "<tr><th>".t("Toimitustapa", $kieli).":</th><td>$laskurow[toimitustapa]</td></tr>";
				$ulos .= "<tr><th>".t("Myyjä", $kieli).":</th><td>$laskurow[kukanimi]</td></tr>";

				if ($laskurow['comments'] != '') {
					$ulos .= "<tr><th>".t("Kommentti", $kieli).":</th><td>".$laskurow['comments']."</td></tr>";
				}

				$ulos .= "</table><br><br>";

				$ulos .= "<table>";
				$ulos .= "<tr><th>".t("Nimitys", $kieli)."</th><th>".t("Tuotenumero", $kieli)."</th><th>".t("Tilattu", $kieli)."</th><th>".t("Toimitetaan", $kieli)."</th>";
				if ($yhtiorow["kerayspoikkeama_kasittely"] != '') $ulos .= "<th>".t("Poikkeaman käsittely", $kieli)."</th>";
				$ulos .= "</tr>";
				$ulos .= $rivit;
				$ulos .= "</table><br><br>";

				$ulos .= t("Tämä on automaattinen viesti. Tähän sähköpostiin ei tarvitse vastata.", $kieli)."<br><br>";
				$ulos .= "</body></html>";

				$subject = mb_encode_mimeheader("$yhtiorow[nimi] - ".t("Keräyspoikkeamat", $kieli), "ISO-8859-1", "Q");

				// Lähetetään keräyspoikkeama asiakkaalle
				if ($laskurow["email"] != '' and $laskurow["kerayspoikkeama"] == 0) {
					$boob = mail($laskurow["email"], $subject, $ulos, $header, "-f $yhtiorow[postittaja_email]");
					if ($boob === FALSE) echo " - ".t("Email lähetys epäonnistui")."!<br>";
				}

				// Lähetetään keräyspoikkeama myyjälle
				if ($laskurow["kukamail"] != '' and ($laskurow["kerayspoikkeama"] == 0 or $laskurow["kerayspoikkeama"] == 2)) {

					$uloslisa = "";

					if (($laskurow["email"] == '' or $boob === FALSE) and $laskurow["kerayspoikkeama"] == 0) {
						$uloslisa .= t("Asiakkaalta puuttuu sähköpostiosoite! Keräyspoikkeamia ei voitu lähettää!")."<br><br>";
					}
					elseif ($laskurow["kerayspoikkeama"] == 2) {
						$uloslisa .= t("Asiakkaalle on merkitty että hän ei halua keräyspoikkeama ilmoituksia!")."<br><br>";
					}
					else {
						$uloslisa .= t("Tämä viesti on lähetetty myös asiakkaalle")."!<br><br>";
					}

					$uloslisa .= t("Tilauksen keräsi").": $keraaja[nimi]<br><br>";

					$ulos = str_replace("</font><hr><br><br><table>", "</font><hr><br><br>$uloslisa<table>", $ulos);

					$boob = mail($laskurow["kukamail"], $subject, $ulos, $header, "-f $yhtiorow[postittaja_email]");
					if ($boob === FALSE) echo " - ".t("Email lähetys epäonnistui")."!<br>";
				}

				unset($subject);
				unset($ulos);
				unset($header);
			}
		}

		if ($tee == 'P' and $real_submit == 'yes') {
			if ($keraamaton > 0) {
				// Jos tilauksella oli yhtään keräämätöntä riviä
				$query = "	SELECT lasku.tunnus, lasku.vienti, lasku.tila,
							toimitustapa.rahtikirja,
							toimitustapa.tulostustapa,
							toimitustapa.nouto,
							lasku.varasto
							FROM lasku
							LEFT JOIN toimitustapa ON lasku.yhtio = toimitustapa.yhtio and lasku.toimitustapa = toimitustapa.selite and toimitustapa.nouto=''
							where lasku.yhtio = '$kukarow[yhtio]'
							and lasku.tunnus in ($tilausnumeroita)
							and lasku.tila in ($tila)
							and lasku.alatila = 'A'";
				$lasresult = mysql_query($query) or pupe_error($query);

				$lask_nro = "";

				while ($laskurow = mysql_fetch_assoc($lasresult)) {

					if ($laskurow["tila"] == 'L' and $laskurow["vienti"] == '' and $laskurow["tulostustapa"] == "X" and $laskurow["nouto"] == "") {
						$alatilak = "D";

						// Päivitetään myös rivit toimitetuiksi
						$query = "	UPDATE tilausrivi
									SET toimitettu = '$kukarow[kuka]', toimitettuaika = now()
									WHERE otunnus 	= '$laskurow[tunnus]'
									and var not in ('P','J')
									and yhtio 		= '$kukarow[yhtio]'
									and keratty    != ''
									and toimitettu  = ''
									and tyyppi 		= 'L'";
						$yoimresult = mysql_query($query) or pupe_error($query);
					}
					elseif ($laskurow["tila"] == 'G' and $laskurow["vienti"] == '' and $laskurow["tulostustapa"] == "X" and $laskurow["nouto"] == "") {
						$alatilak = "D";
					}
					else {
						$alatilak = "C";
					}

					$query  = "	UPDATE lasku
								set alatila = '$alatilak'
								where yhtio = '$kukarow[yhtio]'
								and tunnus  = '$laskurow[tunnus]'";
					$result = mysql_query($query) or pupe_error($query);

					if ($lask_nro == '') {
						$lask_nro = $laskurow['tunnus'];
					}

					// jos meillä on pakkaamolokerot käytössä (eli pakkaamotsydeema), niin esisyötetään rahtikirjat jos käyttäjä on antanut kollien/rullakkojen määrät
					// ei kuiteskaan tehdä tästä virallisesti esisyötettyä rahtikirjaa!
					if ($yhtiorow['pakkaamolokerot'] != '') {
						if ($pakkaamo_kolli != '') {
							$pakkaamo_kolli = (int)$pakkaamo_kolli;

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

						if ($pakkaamo_rullakko != '') {
							$pakkaamo_rullakko = (int)$pakkaamo_rullakko;

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

				// Tutkitaan vielä aivan lopuksi mihin tilaan me laitetaan tämä otsikko
				// Keräysvaiheessahan tilausrivit muuttuvat ja tarkastamme nyt tilanteen uudestaan
				// Tämä tehdään vain myyntitilauksille
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

				// Tulostetaan uusi lähete jos käyttäjä valitsi drop-downista printterin
				// Paitsi jos tilauksen tila päivitettiin sellaiseksi, että lähetettä ei kuulu tulostaa
				$query = "	SELECT *
							FROM lasku
							WHERE tunnus in ($tilausnumeroita)
							and yhtio = '$kukarow[yhtio]'
							and alatila in ('C','D')";
				$lasresult = mysql_query($query) or pupe_error($query);

				while ($laskurow = mysql_fetch_assoc($lasresult)) {

					if ($valittu_tulostin != "") {
						//haetaan lähetteen tulostuskomento
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

					if ($valittu_tulostin != '' and $komento != "" and $lahetekpl > 0) {

						$otunnus = $laskurow["tunnus"];

						//hatetaan asiakkaan lähetetyyppi
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
							//Haetaan yhtiön oletuslähetetyyppi
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

						$utuotteet_mukaan = 0;

						if ($lahetetyyppi == "tulosta_lahete_alalasku.inc") {
							require_once ("tulosta_lahete_alalasku.inc");
						}
						else {
							require_once ("tulosta_lahete.inc");
							$utuotteet_mukaan = 1;
						}

						//	Jos meillä on funktio tulosta_lahete meillä on suora funktio joka hoitaa koko tulostuksen
						if (function_exists("tulosta_lahete")) {
							if ($vrow["selite"] != '') {
								$tulostusversio = $vrow["selite"];
							}
							else {
								$tulostusversio = $asrow["lahetetyyppi"];
							}

							tulosta_lahete($otunnus, $komento["Lähete"], $kieli = "", $toim, $tee, $tulostusversio);
						}
						else {
							// katotaan miten halutaan sortattavan
							// haetaan asiakkaan tietojen takaa sorttaustiedot
							$order_sorttaus = '';

							$asiakas_apu_query = "	SELECT lahetteen_jarjestys, lahetteen_jarjestys_suunta
													FROM asiakas
													WHERE yhtio='$kukarow[yhtio]'
													and tunnus='$laskurow[liitostunnus]'";
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

							//generoidaan lähetteelle ja keräyslistalle rivinumerot
							$query = "  SELECT tilausrivi.*,
										round(if (tuote.myymalahinta != 0, tuote.myymalahinta, tilausrivi.hinta * if ('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1)),'$yhtiorow[hintapyoristys]') ovhhinta,
										round(tilausrivi.hinta * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * if (tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100)),'$yhtiorow[hintapyoristys]') rivihinta,
										$sorttauskentta,
										if (tilausrivi.tuoteno='$yhtiorow[rahti_tuotenumero]', 2, if(tilausrivi.var='J', 1, 0)) jtsort,
										if (tuote.tuotetyyppi='K','2 Työt','1 Muut') tuotetyyppi
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

							//generoidaan rivinumerot
							$rivinumerot = array();

							while ($row = mysql_fetch_assoc($riresult)) {
								$rivinumerot[$row["tunnus"]] = $row["tunnus"];
							}

							sort($rivinumerot);

							$kal = 1;

							foreach($rivinumerot as $rivino) {
								$rivinumerot[$rivino] = $kal;
								$kal++;
							}

							mysql_data_seek($riresult,0);


							unset($pdf);
							unset($page);

							$sivu  = 1;
							$total = 0;
							$save_tyyppi =	$tyyppi;

							if ($laskurow["tila"] == "G") {
								$lah_tyyppi = "SIIRTOLISTA";
							}
							else {
								$lah_tyyppi = "";
							}

							// Aloitellaan lähetteen teko
							$page[$sivu] = alku_lahete($lah_tyyppi);

							while ($row = mysql_fetch_assoc($riresult)) {
								rivi_lahete($page[$sivu], $lah_tyyppi);

								$total+= $row["rivihinta"];
							}

							//Haetaan erikseen toimitettavat tuotteet
							if ($laskurow["vanhatunnus"] != 0 and $utuotteet_mukaan == 1) {
								$query = " 	SELECT GROUP_CONCAT(distinct tunnus SEPARATOR ',') tunnukset
											FROM lasku use index (yhtio_vanhatunnus)
											WHERE yhtio		= '$kukarow[yhtio]'
											and vanhatunnus = '$laskurow[vanhatunnus]'
											and tunnus != '$laskurow[tunnus]'";
								$perheresult = mysql_query($query) or pupe_error($query);
								$tunrow = mysql_fetch_assoc($perheresult);

								//generoidaan lähetteelle ja keräyslistalle rivinumerot
								if ($tunrow["tunnukset"] != "") {

									$toimitettulisa = "";

									if ($laskurow["clearing"] == "ENNAKKOTILAUS" or $laskurow["clearing"] == "JT-TILAUS") {
										$toimitettulisa = " and tilausrivi.toimitettu = '' ";
									}

									$query = "  SELECT tilausrivi.*,
												round(if (tuote.myymalahinta != 0, tuote.myymalahinta, tilausrivi.hinta * if ('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1)),'$yhtiorow[hintapyoristys]') ovhhinta,
												round(tilausrivi.hinta * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * if (tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100)),'$yhtiorow[hintapyoristys]') rivihinta,
												$sorttauskentta,
												if (tilausrivi.var='J', 1, 0) jtsort,
												if (tuote.tuotetyyppi='K','2 Työt','1 Muut') tuotetyyppi
												FROM tilausrivi
												JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
												JOIN lasku ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus
												WHERE tilausrivi.otunnus in ('$tunrow[tunnukset]')
												and tilausrivi.yhtio = '$kukarow[yhtio]'
												$tyyppilisa
												$toimitettulisa
												ORDER BY jtsort, $pjat_sortlisa sorttauskentta $order_sorttaus, tilausrivi.tunnus";
									$riresult = mysql_query($query) or pupe_error($query);

									while ($row = mysql_fetch_assoc($riresult)) {

										if ($row['toimitettu'] == '') {
											$row['kommentti'] .= "\n*******".t("Toimitetaan erikseen",$kieli).".*******";
										}
										else {
											$row['kommentti'] .= "\n*******".t("Toimitettu erikseen tilauksella",$kieli)." ".$row['otunnus'].".*******";
										}

										$row['rivihinta'] 	= "";
										$row['varattu'] 	= "";
										$row['kpl']			= "";
										$row['jt'] 			= "";
										$row['d_erikseen'] 	= "JOO";

										rivi_lahete($page[$sivu], $lah_tyyppi);
									}
								}
							}

							//Vikan rivin loppuviiva
							$x[0] = 20;
							$x[1] = 580;
							$y[0] = $y[1] = $kala + $rivinkorkeus - 4;
							$pdf->draw_line($x, $y, $page[$sivu], $rectparam);

							loppu_lahete($page[$sivu], 1);

							//katotaan onko laskutus nouto
							$query = "  SELECT toimitustapa.nouto, maksuehto.kateinen
										FROM lasku
										JOIN toimitustapa ON lasku.yhtio = toimitustapa.yhtio and lasku.toimitustapa = toimitustapa.selite
										JOIN maksuehto ON lasku.yhtio = maksuehto.yhtio and lasku.maksuehto = maksuehto.tunnus
										WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tunnus = '$laskurow[tunnus]'
										and toimitustapa.nouto != '' and maksuehto.kateinen = ''";
							$kures = mysql_query($query) or pupe_error($query);

							if (mysql_num_rows($kures) > 0 and $yhtiorow["lahete_nouto_allekirjoitus"] != "") {
								kuittaus_lahete();
							}

							if ($lahetetyyppi == "tulosta_lahete_alalasku.inc") {
								alvierittely_lahete($page[$sivu]);
							}

							//tulostetaan sivu
							if ($lahetekpl > 1 and $komento != "email") {
								$komento .= " -#$lahetekpl ";
							}

							print_pdf_lahete($komento);
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

		echo "<option value='KAIKKI'>".t("Näytä kaikki")."</option>";

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
		echo "<option value='KAIKKI'>".t("Näytä kaikki")."</option>";
		echo "<option value='NORMAA' $sela>".t("Näytä normaalitilaukset")."</option>";
		echo "<option value='ENNAKK' $selb>".t("Näytä ennakkotilaukset")."</option>";
		echo "<option value='JTTILA' $selc>".t("Näytä jt-tilaukset")."</option>";

		echo "</select></td></tr>";

		echo "<tr><td>".t("Valitse toimitustapa:")."</td><td><select name='tutoimtapa' onchange='submit()'>";

		$query = "	SELECT selite, min(tunnus) tunnus
					FROM toimitustapa
					WHERE $logistiikka_yhtiolisa
					GROUP BY selite
					ORDER BY selite";
		$result = mysql_query($query) or pupe_error($query);

		echo "<option value='KAIKKI'>".t("Näytä kaikki")."</option>";

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
					group_concat(DISTINCT concat_ws('\n\n', if (comments!='',concat('".t("Lähetteen lisätiedot").":\n',comments),NULL), if (sisviesti2!='',concat('".t("Keräyslistan lisätiedot").":\n',sisviesti2),NULL)) SEPARATOR '\n') ohjeet,
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
					and lasku.alatila				= 'A'
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

		//piirretään taulukko...
		if (mysql_num_rows($result)!=0) {

			echo "<br><table>";

			echo "<tr>";
			if ($logistiikka_yhtio != '') {
				echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='yhtio'; document.forms['find'].submit();\">".t("Yhtiö")."</a></th>";
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

			echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='kerayspvm'; document.forms['find'].submit();\">".t("Keräysaika")."</a><br>
					  <a href='#' onclick=\"getElementById('jarj').value='toimaika'; document.forms['find'].submit();\">".t("Toimitusaika")."</a></th>";

			echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='toimitustapa'; document.forms['find'].submit();\">".t("Toimitustapa")."</a></th>";
			echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='riveja'; document.forms['find'].submit();\">".t("Riv")."</a></th>";
			echo "<th valign='top'>".t("Kerää")."</th>";

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

					echo "<td valign='top' class='tooltip' id='$row[tunnus]'>$row[t_tyyppi] $row[prioriteetti] <img src='$palvelin2/pics/lullacons/info.png'>";
				}
				else {
					echo "<td valign='top'>$row[t_tyyppi] $row[prioriteetti]";
				}

				echo "<br>$row[varastonimi]</td>";
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
						<input type='submit' name='tila' value='".t("Kerää")."'></form></td></tr>";
			}

			$spanni = $logistiikka_yhtio != '' ? 7 : 6;

			echo "<tr>";
			echo "<td colspan='$spanni' style='text-align:right;' class='back'>".t("Rivejä yhteensä").":</td>";
			echo "<td valign='top' class='back'>$riveja_yht</td>";
			echo "</tr>";

			echo "</table>";
		}
		else {
			echo "<font class='message'>".t("Yhtään keräämätöntä tilausta ei löytynyt")."...</font>";
		}
	}

	if ($id != 0 and $rahtikirjaan == '') {

		//päivitä kerätyt formi
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

		// Koko klöntti kuuluu aina samaan varastoon joten otetaan tämä tässä talteen
		$lp_varasto = $testrow["varasto"];

		if ($testrow['kerayslista'] > 0) {
			//haetaan kaikki tälle klöntille kuuluvat otsikot
			$query = "	SELECT GROUP_CONCAT(DISTINCT tunnus ORDER BY tunnus SEPARATOR ',') tunnukset
						FROM lasku
						WHERE yhtio		= '$kukarow[yhtio]'
						and kerayslista	= '$id'
						and kerayslista != 0
						and tila		in ($tila)
						$tilaustyyppi
						HAVING tunnukset is not null";
			$toimresult = mysql_query($query) or pupe_error($query);

			//jos rivejä löytyy niin tiedetään, että tämä on keräysklöntti
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
			$query = "	SELECT concat_ws(' ',lasku.nimi, lasku.nimitark) nimi,
						concat_ws(' ', lasku.toim_nimi, lasku.toim_nimitark) toim_nimi,
						lasku.osoite,
						concat_ws(' ', lasku.postino, lasku.postitp) postitp,
						lasku.toim_osoite,
						concat_ws(' ', lasku.toim_postino, lasku.toim_postitp) toim_postitp,
						lasku.clearing,
						lasku.tila,
						lasku.mapvm,
						lasku.pakkaamo,
						lasku.yhtio,
						toimitustapa.tulostustapa,
						toimitustapa.nouto
						FROM lasku
						LEFT JOIN kuka ON lasku.myyja = kuka.tunnus
						LEFT JOIN toimitustapa ON lasku.yhtio = toimitustapa.yhtio and lasku.toimitustapa = toimitustapa.selite
						WHERE lasku.tunnus in ($tilausnumeroita)
						and lasku.yhtio = '$kukarow[yhtio]'
						and lasku.tila in ($tila)
						and lasku.alatila = 'A'";
			$result = mysql_query($query) or pupe_error($query);
			$otsik_row = mysql_fetch_assoc($result);

			echo "<tr><th>".t("Tilaus") ."</th><td>$tilausnumeroita $otsik_row[clearing]</td></tr>";
			echo "<tr><th>".t("Asiakas") ."</th><td>$otsik_row[nimi]<br>$otsik_row[toim_nimi]</td></tr>";
			echo "<tr><th>".t("Laskutusosoite") ."</th><td>$otsik_row[osoite], $otsik_row[postitp]</td></tr>";
			echo "<tr><th>".t("Toimitusosoite") ."</th><td>$otsik_row[toim_osoite], $otsik_row[toim_postitp]</td></tr>";
		}

		if ($toim == "VALMISTUS") {
			$sorttauskentta = generoi_sorttauskentta($yhtiorow["valmistus_kerayslistan_jarjestys"]);
			$order_sorttaus = $yhtiorow["valmistus_kerayslistan_jarjestys_suunta"];

			if ($yhtiorow["valmistus_kerayslistan_palvelutjatuottet"] == "E") $pjat_sortlisa = "tuotetyyppi,";
			else $pjat_sortlisa = "";
		}
		else {
			$sorttauskentta = generoi_sorttauskentta($yhtiorow["kerayslistan_jarjestys"]);
			$order_sorttaus = $yhtiorow["kerayslistan_jarjestys_suunta"];

			if ($yhtiorow["kerayslistan_palvelutjatuottet"] == "E") $pjat_sortlisa = "tuotetyyppi,";
			else $pjat_sortlisa = "";
		}

		// Summataan rivit yhteen
		if ($yhtiorow[$sorttaus] == "S") {
			$select_lisa	= "sum(tilausrivi.tilkpl) tilkpl, sum(tilausrivi.varattu) varattu, sum(tilausrivi.jt) jt, group_concat(tilausrivi.tunnus) rivitunnukset,";
			$group_lisa 	= "GROUP BY tilausrivi.tuoteno, tilausrivi.hyllyalue, tilausrivi.hyllyvali, tilausrivi.hyllyalue, tilausrivi.hyllynro";
		}
		else {
			$select_lisa 	= "tilausrivi.tilkpl, tilausrivi.varattu, tilausrivi.jt,";
			$group_lisa 	= "";
		}

		$query = "	SELECT
					concat_ws(' ',tilausrivi.tuoteno, tilausrivi.nimitys) tuoteno,
					tilausrivi.tuoteno puhdas_tuoteno,
					tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso,
					concat_ws(' ',tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso) varastopaikka,
					tuote.ei_saldoa,
					tuote.sarjanumeroseuranta,
					tilausrivi.keratty,
					tilausrivi.tunnus,
					tilausrivi.var,
					lasku.jtkielto,
					$select_lisa
					$sorttauskentta,
					if (tuote.tuotetyyppi='K','2 Työt','1 Muut') tuotetyyppi
					FROM tilausrivi
					JOIN tuote ON tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno
					JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
					WHERE tilausrivi.yhtio	= '$kukarow[yhtio]'
					and tilausrivi.otunnus in ($tilausnumeroita)
					and tilausrivi.var in ('', 'H' $var_lisa)
					and tilausrivi.tyyppi in ($tyyppi)
					and tilausrivi.kerattyaika = '0000-00-00 00:00:00'
					$group_lisa
					ORDER BY $pjat_sortlisa sorttauskentta $order_sorttaus, tilausrivi.tunnus";
		$result = mysql_query($query) or pupe_error($query);
		$riveja = mysql_num_rows($result);

		if ($riveja > 0) {
			echo "<form name = 'rivit' method='post' action='$PHP_SELF' autocomplete='off'>";
			echo "	<input type='hidden' name='tee' value='P'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='id'  value='$id'>";
			echo "<th>".t("Kerääjä")."</th><td><input type='text' size='5' name='keraajanro'> ".t("tai")." ";
			echo "<select name='keraajalist'>";

			if ($kukarow["keraajanro"] != 0) {
				echo "<option value='$kukarow[keraajanro]'>$kukarow[nimi]</option>";
			}

			$query = "select * from kuka where yhtio='$kukarow[yhtio]' and keraajanro!=0";
			$kuresult = mysql_query($query) or pupe_error($query);

			while ($kurow = mysql_fetch_assoc($kuresult)) {
				if ($kukarow['kuka']!=$kurow['kuka'])
					echo "<option value='$kurow[keraajanro]'>$kurow[nimi]</option>";
			}
			echo "</select></td>";

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
				echo "<tr><th>".t("Haluatko mennä rahtikirjan syöttöön")."</th><td><input type='checkbox' name='rahtikirjalle'>".t("Kyllä")."</td></tr>";
			}

			echo "</table><br><br>";

			echo "	<table>
					<tr>
					<th>".t("Varastopaikka")."</th>
					<th>".t("Tuoteno")."</th>
					<th>".t("Määrä")."</th>
					<th>".t("Poikkeava määrä")."</th>";

			if ($yhtiorow["kerayspoikkeama_kasittely"] != '') {
				echo "<th>".t("Poikkeaman käsittely")."</th>";
			}

			echo "</tr>";

			$i = 0;

			while ($row = mysql_fetch_assoc($result)) {

				if ($row['var']=='P') {
					// jos kyseessä on puuterivi
					$puute 			= t("PUUTE");
					$row['varattu']	= $row['tilkpl'];
				}
				elseif ($row['var']=='J') {
					// jos kyseessä on JT-rivi
					$puute 			= t("**JT**");

					if ($yhtiorow["varaako_jt_saldoa"] == "") {
						$row['varattu']	= $row['jt'];
					}
					else {
						$row['varattu']	= $row['jt']+$row['varattu'];
					}
				}
				elseif ($row['var']=='H') {
					// jos kyseessä on väkisinhyväksytty-rivi
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
					$puute .= " ".t("Verkkokaupassa etukäteen maksettu tuote!");
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

					if ($yhtiorow[$sorttaus] == "S" and strpos($row["rivitunnukset"], ",") > 0) {
						foreach(explode(",", $row["rivitunnukset"]) as $tunn) {
							$tunn = trim($tunn);
							echo "<input type='hidden' name='kerivi[]' value='$tunn'>";
						}
					}
					else {
						echo "<input type='hidden' name='kerivi[]' value='$row[tunnus]'>
								<input type='hidden' name='maara[$row[tunnus]]' $poikkeava_maara_disabled>";
					}
				}
				else {
					echo "<tr class='aktiivi'>
							<td>$row[varastopaikka]</td>
							<td>$row[tuoteno]</td>
							<td>$row[varattu]</td>
							<td>";

					//	kaikki gruupatut tunnukset mukaan!
					if ($yhtiorow[$sorttaus] == "S" and strpos($row["rivitunnukset"], ",") > 0) {
						foreach(explode(",", $row["rivitunnukset"]) as $tunn) {
							$tunn = trim($tunn);
							echo "<input type='hidden' name='kerivi[]' value='$tunn'>";
						}
					}
					else {
						echo "<input type='text' size='4' name='maara[$row[tunnus]]' value='$maara[$i]' $poikkeava_maara_disabled> $puute";
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

						$paikat 	= "<option value=''>".t("Valitse erä")."</option>";
						$selpaikka 	= "";

						$query	= "	SELECT sarjanumeroseuranta.sarjanumero era, sarjanumeroseuranta.parasta_ennen
				   					FROM sarjanumeroseuranta
				   					WHERE yhtio = '$kukarow[yhtio]'
									and tuoteno = '$row[puhdas_tuoteno]'
				   					and $tunken1 = '$row[tunnus]'
				   					LIMIT 1";
				   		$sarjares = mysql_query($query) or pupe_error($query);
				   		$sarjarow = mysql_fetch_assoc($sarjares);

						echo t("Erä").": ";

						while($alkurow = mysql_fetch_assoc($omavarastores)) {

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

								//Jos erä on keksitty käsin täältä keräyksestä
								$query = "	SELECT tyyppi, (varattu+kpl+jt) kpl, tunnus
											FROM tilausrivi
											WHERE yhtio = '$kukarow[yhtio]'
											and tuoteno = '$row[puhdas_tuoteno]'
											and tunnus	= '$alkurow[ostorivitunnus]'";
								$lisa_res = mysql_query($query) or pupe_error($query);
								$lisa_row = mysql_fetch_assoc($lisa_res);

								if (($lisa_row["tyyppi"] == "O" or $lisa_row["kpl"] < 0 or $lisa_row["tunnus"] == $row["tunnus"]) and

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
							echo "<option value='' SELECTED>".t("Ei käsitellä")."</option>";
						}

						echo "<option value='JT' $selpk_JT>".t("JT")."</option>";
						echo "<option value='PU' $selpk_PU>".t("Puute")."</option>";

						if ($row["sarjanumeroseuranta"] == "E" or $row["sarjanumeroseuranta"] == "F" or $row["sarjanumeroseuranta"] == "G") {
							echo "<option value='UR' $selpk_UR>".t("Uusi rivi")."</option>";
						}

						echo "<option value='UT'>".t("Uusi tilaus")."</option>";
						echo "<option value='MI'>".t("Mitätöi")."</option>";
						echo "</select></td>";
					}

					echo "</tr>";
				}
				$i++;
			}

			// Jos kyseessä ei ole valmistus tulostetaan virallinen lähete
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

			if ($otsik_row['pakkaamo'] == 0 or $yhtiorow['pakkaamolokerot'] == '') {

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

					// käteinen muuttuja viritetään tilaus-valmis.inc:issä jos maksuehto on käteinen
					// ja silloin pitää kaikki lähetteet tulostaa aina printteri5:lle (lasku printteri)
					if ($kateinen == 'X') {
						$sel_lahete[$prirow['printteri5']] = "SELECTED";	// laskuprintteri
						$sel_oslapp[$prirow['printteri5']] = "SELECTED";	// osoitelappuprintteri
					}
					else {
						$sel_lahete[$prirow['printteri1']] = "SELECTED";	// laskuprintteri
						$sel_oslapp[$prirow['printteri3']] = "SELECTED";	// osoitelappuprintteri
					}
				}

				echo "<tr><th>".t("Lähete").":</th><th colspan='$spanni'>";

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
					echo " ".t("Lähetetyyppi").": <select name='sellahetetyyppi'>";

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

			if ($otsik_row['pakkaamo'] > 0 and $yhtiorow['pakkaamolokerot'] != '') {
				echo "<tr><th>".t("Kolli")."</th><th colspan='$spanni'><input type='text' name='pakkaamo_kolli' size='5'/></th>";

				if ($yhtiorow["kerayspoikkeama_kasittely"] != '') {
					echo "<th>&nbsp;</th>";
				}
				echo "</tr>";
			}

			echo "<tr>";

			if (($yhtiorow['karayksesta_rahtikirjasyottoon'] == '' or $otsik_row["tulostustapa"] == "X") and ($otsik_row['pakkaamo'] == 0 or $yhtiorow['pakkaamolokerot'] == '')) {
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

			if ($otsik_row["tulostustapa"] != "X" or $otsik_row["nouto"] != "") {
				echo "<input type='submit' name='real_submit' id='real_submit' value='".t("Merkkaa kerätyksi")."'></form>";
			}
			else {
				echo "<input type='submit' name='real_submit' id='real_submit' value='".t("Merkkaa toimitetuksi")."'></form>";
			}

			if ($otsik_row["tulostustapa"] != "X" and $yhtiorow['karayksesta_rahtikirjasyottoon'] == 'Y') {
				echo "<br><br><font class='message'>".t("Siirryt automaattisesti rahtikirjan syöttöön")."!</font>";
			}
			elseif ($otsik_row["tulostustapa"] != "X" and $yhtiorow['karayksesta_rahtikirjasyottoon'] == 'H' and $keraysklontti === FALSE) {
				echo "<br><br><font class='message'>".t("Voit halutessasi siirtyä rahtikirjan syöttöön")."!</font>";
			}
		}
		else {
			echo t("Tällä tilauksella ei ole yhtään kerättävää riviä!");
		}
	}

	if ($rahtikirjaan == 'mennaan') {
		echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=../rahtikirja.php?toim=lisaa&id=$id&rakirno=$id&tunnukset=$tilausnumeroita&mista=keraa.php'>";
		exit;
	}

	require ("../inc/footer.inc");
?>
