<?php
	require ("../inc/parametrit.inc");

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
					and tulostustapa != 'H'
					and maksuehto.jv = ''";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			$yhtiorow['karayksesta_rahtikirjasyottoon'] = '';
		}
	}

	$var_lisa = "";

	if ($yhtiorow["puute_jt_kerataanko"] == "J" or $yhtiorow["puute_jt_kerataanko"] == "Q") {
		$var_lisa .= ",'J'";
	}

	if ($yhtiorow["puute_jt_kerataanko"] == "P" or $yhtiorow["puute_jt_kerataanko"] == "Q") {
		$var_lisa .= ",'P'";
	}

	if ($tee == 'P') {
		//haetaan kaikki tälle klöntille kuuluvat otsikot
		$query = "	SELECT GROUP_CONCAT(DISTINCT tunnus ORDER BY tunnus SEPARATOR ',') tunnukset
					FROM lasku
					WHERE yhtio='$kukarow[yhtio]'
					and kerayslista='$id'
					and kerayslista != 0
					and tila in ($tila)
					$tilaustyyppi
					HAVING tunnukset is not null";
		$toimresult = mysql_query($query) or pupe_error($query);

		//jos rivejä löytyy niin tiedetään, että tämä on keräysklöntti
		if (mysql_num_rows($toimresult) > 0) {
			$toimrow = mysql_fetch_array($toimresult);
			$tilausnumeroita = $toimrow["tunnukset"];
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

		while ($toimrow = mysql_fetch_array($toimresult)) {

			if ($toim == 'SIIRTOTYOMAARAYS' or $toim == 'SIIRTOLISTA') {
				$tunken = "siirtorivitunnus";
			}
			elseif ($toimrow["varattu"] < 0) {
				$tunken = "ostorivitunnus";
			}
			else {
				$tunken = "myyntirivitunnus";
			}

			$query = "select count(*) kpl from sarjanumeroseuranta where yhtio='$kukarow[yhtio]' and tuoteno='$toimrow[tuoteno]' and $tunken='$toimrow[tunnus]'";
			$sarjares = mysql_query($query) or pupe_error($query);
			$sarjarow = mysql_fetch_array($sarjares);

			if ($sarjarow["kpl"] != abs($toimrow["varattu"])) {
				echo "<font class='error'>".t("Sarjanumeroseurannassa oleville tuotteille on liitettävä sarjanumero ennen keräystä")."! ".t("Tuote").": $toimrow[tuoteno].</font><br><br>";
				$tee = "";
			}
		}
	}

	if ($tee == 'P') {

		if ((int) $keraajanro == 0) $keraajanro=$keraajalist;

		$query = "select * from kuka where yhtio='$kukarow[yhtio]' and keraajanro='$keraajanro'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result)==0) {
			echo "<font class='error'>".t("Kerääjää")." $keraajanro ".t("ei löydy")."!</font><br>";
		}
		else {
			$keraaja = mysql_fetch_array($result);
			$who = $keraaja['kuka'];

			$muuttuiko='';
			$mitkamuuttu='';

			for ($i=0; $i < count($kerivi); $i++) {

				$query1 = "	SELECT if(kerattyaika='0000-00-00 00:00:00', 'keraamaton', 'keratty') status
							FROM tilausrivi
							WHERE tunnus = '$kerivi[$i]' and yhtio='$kukarow[yhtio]'";
				$ktresult = mysql_query($query1) or pupe_error($query1);
				$statusrow = mysql_fetch_array($ktresult);
				$keraamaton=0;

				if ($statusrow["status"] == "keraamaton") {
					if($kerivi[$i] > 0) {

						$apui = $kerivi[$i];

						//Kysessä voi olla keräysklöntti, haetaan muuttuneen rivin otunnus
						$query1 = "	SELECT otunnus
									FROM tilausrivi
									WHERE tunnus = '$kerivi[$i]' and yhtio='$kukarow[yhtio]'";
						$result  = mysql_query($query1) or pupe_error($query1);
						$otsikko = mysql_fetch_array($result);

						//Haetaan otsikon kaikki tiedot
						$query1 = "	SELECT *
									FROM lasku
									WHERE tunnus = '$otsikko[otunnus]' and yhtio='$kukarow[yhtio]'";
						$result = mysql_query($query1) or pupe_error($query1);
						$otsikkorivi = mysql_fetch_array($result);

						//Haetaan tilausrivin kaikki tiedot
						$query1 = "	SELECT *
									FROM tilausrivi
									WHERE tunnus = '$kerivi[$i]' and yhtio='$kukarow[yhtio]'";
						$result = mysql_query($query1) or pupe_error($query1);
						$tilrivirow = mysql_fetch_array($result);


						//Aloitellaan tilausrivi päivitysqueryä
						if ($tilrivirow["var"] != "J") {
							//Muut kuin JT-rivit päivitetään aina kerätyiksi
							$query = "	UPDATE tilausrivi
										SET keratty = '$who',
										kerattyaika = now() ";
						}
						else {
							//JT-rivit päivitetään kerätyksi varauksella
							$query = "	UPDATE tilausrivi
										SET yhtio=yhtio ";
						}

						// Käyttäjä on syöttänyt jonkun luvun
						if (trim($maara[$apui]) != '') {

							//Siivotaan hieman käyttäjän syöttämää kappalemäärää
							$maara[$apui] = str_replace ( ",", ".", $maara[$apui]);
							$maara[$apui] = (float) $maara[$apui];

							$rotunnus = 0;

							if ($tilrivirow["var"] == 'P' and $maara[$apui] > 0) {
								// Puuterivi löytyi poistetaan VAR
								$query .= "	, var		= ''
											, varattu	= '".$maara[$apui]."'";

								//Poistetaan 'tuote loppu'-kommentti jos tuotetta sittenkin löytyi
								$korvataan_pois = t("Tuote Loppu.");
								$query .= "	, kommentti	= replace(kommentti, '$korvataan_pois', '') ";


								// PUUTE-riville tehdään osatoimitus ja loput jätetään puuteriviksi
								if($maara[$apui] < $tilrivirow['tilkpl']) {
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
							elseif($tilrivirow["var"] == 'J' and $maara[$apui] > 0) {
								// JT-rivi löytyi, poistetaan VAR ja merkataan rivi kerätyksi
								$query .= "	, keratty 		= '$who'
											, kerattyaika 	= now()
											, var			= ''
											, jt			= 0
											, varattu		= '".$maara[$apui]."'";

								// JT-riville tehdään osatoimitus ja loput jätetään jälkitoimitukseen
								if($maara[$apui] < $tilrivirow['jt']) {
									$rotunnus	= $tilrivirow['otunnus'];
									$rtyyppi	= $tilrivirow['tyyppi'];
									$rtilkpl 	= round($tilrivirow['jt']-$maara[$apui],2);
									$rvarattu	= 0;
									$rjt  		= round($tilrivirow['jt']-$maara[$apui],2);
									$rvar		= $tilrivirow['var'];
									$keratty	= "''";
									$kerattyaik	= "''";
									$rkomm 		= $tilrivirow["kommentti"];
								}
							}
							elseif ($maara[$apui] >= 0 and $maara[$apui] < $tilrivirow['varattu'] and ($otsikkorivi['clearing'] == 'ENNAKKOTILAUS' or $otsikkorivi['clearing'] == 'JT-TILAUS')) {
								// Jos tämä on toimitettava ennakkotilaus tai jt-tilaus
								$query .= ", varattu='".$maara[$apui]."'";

								if($otsikkorivi['clearing'] == 'ENNAKKOTILAUS') {
									$ejttila    = "('E')";
									$ejtalatila = "('A')";

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
								if($otsikkorivi['clearing'] == 'JT-TILAUS') {
									$ejttila    = "('N','L')";
									$ejtalatila = "('T','X')";

									$rotunnus	= 0;
									$rtyyppi	= "L";
									$rtilkpl 	= round($tilrivirow['varattu']-$maara[$apui],2);
									$rvarattu	= 0;
									$rjt  		= round($tilrivirow['varattu']-$maara[$apui],2);
									$rvar		= "J";
									$keratty	= "''";
									$kerattyaik	= "''";
									$rkomm 		= $tilrivirow["kommentti"];
								}

								//Etsitään ennakko/jt-otsikko jolle rivi laitetaan
								$query1 = "	SELECT tunnus
											FROM lasku
											WHERE tila		in $ejttila
											and alatila		in $ejtalatila
											and yhtio		= '$kukarow[yhtio]'
											and ytunnus		= '$otsikkorivi[ytunnus]' and
											nimi 			= '$otsikkorivi[nimi]' and
											nimitark 		= '$otsikkorivi[nimitark]' and
											osoite 			= '$otsikkorivi[osoite]' and
											postino			= '$otsikkorivi[postino]' and
											postitp 		= '$otsikkorivi[postitp]' and
											toim_nimi		= '$otsikkorivi[toim_nimi]' and
											toim_nimitark	= '$otsikkorivi[toim_nimitark]' and
											toim_osoite 	= '$otsikkorivi[toim_osoite]' and
											toim_postino 	= '$otsikkorivi[toim_postino]' and
											toim_postitp 	= '$otsikkorivi[toim_postitp]' and
											toimitustapa 	= '$otsikkorivi[toimitustapa]' and
											maksuehto 		= '$otsikkorivi[maksuehto]' and
											vienti	 		= '$otsikkorivi[vienti]' and
											alv		 		= '$otsikkorivi[alv]' and
											ketjutus 		= '$otsikkorivi[ketjutus]' and
											kohdistettu		= '$otsikkorivi[kohdistettu]' and
											toimitusehto	= '$otsikkorivi[toimitusehto]'";
								$stresult = mysql_query($query1) or pupe_error($query1);

								// Sopiva otsikko löytyi
								if (mysql_num_rows($stresult) > 0) {
									$strow = mysql_fetch_array($stresult);

									$rotunnus	= $strow["tunnus"];
								}
							}
							elseif($tilrivirow["var"] != 'J' and $tilrivirow["var"] != 'P') {
								// Jos tämä on normaali rivi
								if($maara[$apui] < 0) {
									// Jos kerääjä kuittaa alle nollan niin ei tehdä mitään
									$query .= ", varattu = varattu";
								}
								elseif($maara[$apui] >= 0 and $maara[$apui] < $tilrivirow['varattu']) {
									$query .= ", varattu = '".$maara[$apui]."'";

									if($poikkeama_kasittely[$apui] != "") {
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


							if($poikkeama_kasittely[$apui] != "" and $rotunnus != 0) {
								// Käyttäjän valitsemia poikkeamakäsittelysääntöjä
								if ($poikkeama_kasittely[$apui] == "PU") {
									// Riville tehdään osatoimitus ja loput jätetään puuteriviksi
									$rvarattu	= 0;
									$rjt  		= 0;
									$rvar		= "P";
									$keratty	= "'$who'";
									$kerattyaik	= "now()";
									$rkomm 		= t("Tuote Loppu.");
								}
								elseif($poikkeama_kasittely[$apui] == "JT") {
									// Riville tehdään osatoimitus ja loput jätetään jälkkäriin (ennakkotilauksilla takaisin ennakkoon)
									if($otsikkorivi['clearing'] == 'ENNAKKOTILAUS') {
										$rvarattu	= $rtilkpl;
										$rjt  		= 0;
										$rvar		= "";
									}
									else {
										$rvarattu	= 0;
										$rjt  		= $rtilkpl;
										$rvar		= "J";
									}

									$keratty	= "''";
									$kerattyaik	= "''";
									$rkomm 		= $tilrivirow["kommentti"];
								}
								elseif($poikkeama_kasittely[$apui] == "MI") {
									// Riville tehdään osatoimitus ja loput mitätöidään
									$rotunnus	= 0;
								}
								elseif($poikkeama_kasittely[$apui] == "UT") {
									// Riville tehdään osatoimitus ja loput siirretään ihan uudelle tilaukselle
									$querym = "SELECT * FROM lasku WHERE tunnus='$tilrivirow[otunnus]' and yhtio ='$kukarow[yhtio]'";
									$monistares = mysql_query($querym) or pupe_error($querym);
									$monistarow = mysql_fetch_array($monistares);

									$fields = mysql_field_name($monistares,0);
									$values = "'".$monistarow[0]."'";

									for($iii=1; $iii < mysql_num_fields($monistares)-1; $iii++) { // Ei monisteta tunnusta

										$fields .= ", ".mysql_field_name($monistares,$iii);

										switch (mysql_field_name($monistares,$iii)) {
											case 'kerayspvm':
											case 'toimaika':
											case 'luontiaika':
												$values .= ", now()";
												break;
											case 'alatila':
												$values .= ", ''";
												break;
											case 'tila':
												$values .= ", 'N'";
												break;
											case 'tunnus':
											case 'kapvm':
											case 'tapvm':
											case 'olmapvm':
											case 'summa':
											case 'kasumma':
											case 'hinta':
											case 'kate':
											case 'arvo':
											case 'maksuaika':
											case 'lahetepvm':
											case 'viite':
											case 'laskunro':
											case 'mapvm':
											case 'tilausvahvistus':
											case 'viikorkoeur':
											case 'tullausnumero':
											case 'laskutuspvm':
											case 'erpcm':
											case 'laskuttaja':
											case 'laskutettu':
											case 'lahetepvm':
											case 'maksaja':
											case 'maksettu':
											case 'maa_maara':
											case 'kuljetusmuoto':
											case 'kauppatapahtuman_luonne':
											case 'sisamaan_kuljetus':
											case 'sisamaan_kuljetusmuoto':
											case 'poistumistoimipaikka':
											case 'poistumistoimipaikka_koodi':
												$values .= ", ''";
												break;
											case 'laatija':
												$values .= ", '$kukarow[kuka]'";
												break;
											default:
												$values .= ", '".$monistarow[$iii]."'";
										}
									}

									$kysely  = "INSERT into lasku ($fields) VALUES ($values)";
									$insres  = mysql_query($kysely) or pupe_error($kysely);
									$utunnus = mysql_insert_id($link);

									$rotunnus	= $utunnus;
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
								$querys = "	INSERT into tilausrivi set
											hyllyalue		= '$tilrivirow[hyllyalue]',
											hyllynro		= '$tilrivirow[hyllynro]',
											hyllyvali		= '$tilrivirow[hyllyvali]',
											hyllytaso		= '$tilrivirow[hyllytaso]',
											varattu 		= '$rvarattu',
											tilkpl 			= '$rtilkpl',
											jt				= '$rjt',
											otunnus 		= '$rotunnus',
											var				= '$rvar',
											keratty			= $keratty,
											kerattyaika		= $kerattyaik,
											kerayspvm 		= now(),
											laatija			= '$kukarow[kuka]',
											laadittu		= now(),
											toimaika 		= now(),
											yhtio 			= '$tilrivirow[yhtio]',
											tuoteno 		= '$tilrivirow[tuoteno]',
											ale 			= '$tilrivirow[ale]',
											netto 			= '$tilrivirow[netto]',
											yksikko 		= '$tilrivirow[yksikko]',
											try 			= '$tilrivirow[try]',
											osasto 			= '$tilrivirow[osasto]',
											alv 			= '$tilrivirow[alv]',
											hinta 			= '$tilrivirow[hinta]',
											nimitys 		= '$tilrivirow[nimitys]',
											tyyppi 			= '$rtyyppi',
											kommentti 		= '$rkomm'";
								$riviresult = mysql_query($querys) or pupe_error($querys);
							}

							//päivitetään tuoteperheiden saldottomat jäsenet oikeisiin määriin (ne voi olla alkuperäiselläkin lähetteelä == vanhatunnus)
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

								while ($tilrivirow2 = mysql_fetch_array($result)) {
									$query2 = "	SELECT kerroin
												FROM tuoteperhe
												WHERE yhtio = '$kukarow[yhtio]' AND
												isatuoteno = '$tilrivirow[tuoteno]' AND
												tuoteno = '$tilrivirow2[tuoteno]'";
									$result2 = mysql_query($query2) or pupe_error($query2);

									// oltiin muokkaamassa isätuotteen kappalemäärää, päivitetään saldottomien lasten määrät kertoimella
									if (mysql_num_rows($result2) == 1) {
										$kerroinrow = mysql_fetch_array($result2);
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
							$mitkamuuttu .= "'".$kerivi[$i]."',";
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

			if ($keraamaton > 0) {
				// Jos tilauksella oli yhtään keräämätöntä riviä
				$query  = "	update lasku
							set alatila = 'C'
							where yhtio = '$kukarow[yhtio]'
							and tunnus in ($tilausnumeroita)
							and tila in ($tila)
							and alatila = 'A'";
				$result = mysql_query($query) or pupe_error($query);

				//Jos keräyspoikkeamia syntyi, niin lähetetään mailit myyjälle ja asiakkaalle
				if ($muuttuiko == 'kylsemuuttu') {

					$mitkamuuttu = substr($mitkamuuttu,0,-1);

					$query = "	SELECT distinct(otunnus)
								FROM tilausrivi
								WHERE tunnus in ($mitkamuuttu) and yhtio='$kukarow[yhtio]'";
					$otsresult = mysql_query($query) or pupe_error($query);

					while ($otsrow = mysql_fetch_array($otsresult)) {

						$query = "	SELECT *
									FROM tilausrivi
									WHERE tunnus in ($mitkamuuttu) and otunnus='$otsrow[otunnus]' and yhtio='$kukarow[yhtio]'";
						$result = mysql_query($query) or pupe_error($query);

						$rivit = '';
						while ($tvtilausrivirow = mysql_fetch_array($result)) {

							$nimitysloput = '';

							if (strlen(asana('nimitys_',$tvtilausrivirow['tuoteno'],$tvtilausrivirow['nimitys'])) > 27) {
								$nimitysloput = substr(asana('nimitys_',$tvtilausrivirow['tuoteno'],$tvtilausrivirow['nimitys']),28);
								$tvtilausrivirow['nimitys'] = substr(asana('nimitys_',$tvtilausrivirow['tuoteno'],$tvtilausrivirow['nimitys']),0,28);
							}

							$rivit .= sprintf("%-30.s",$tvtilausrivirow['nimitys']);
							$rivit .= sprintf("%-20.s",$tvtilausrivirow['tuoteno']);
							$rivit .= sprintf("%8.s"  ,$tvtilausrivirow['tilkpl']);
							$rivit .= sprintf("%23.s"  ,$tvtilausrivirow['varattu']);
							$rivit .= "\r\n";

							if ($nimitysloput != '') {
								$rivit .= sprintf("%-75.s",$nimitysloput);
								$rivit .= "\r\n";
							}
						}


						$query = "	SELECT lasku.*, asiakas.email, asiakas.kerayspoikkeama, kuka.nimi kukanimi
									FROM lasku
									JOIN asiakas on asiakas.yhtio=lasku.yhtio and asiakas.tunnus=lasku.liitostunnus
									LEFT JOIN kuka on kuka.yhtio=lasku.yhtio and kuka.tunnus=lasku.myyja
									WHERE lasku.tunnus='$otsrow[otunnus]' and lasku.yhtio='$kukarow[yhtio]'";
						$result = mysql_query($query) or pupe_error($query);
						$laskurow = mysql_fetch_array($result);

						$header  = "From: <$yhtiorow[postittaja_email]>\r\n";

						$ulos  = sprintf("%-50.s",$yhtiorow['nimi'])								."".t("Keräyspoikkeamat")."\r\n";
						$ulos .= sprintf("%-50.s",$yhtiorow['osoite'])								."\r\n";
						$ulos .= sprintf("%-50.s",$yhtiorow['postino']." ".$yhtiorow['postitp'])	.tv1dateconv($laskurow['luontiaika'])."\r\n";
						$ulos .= "\r\n";
						$ulos .= sprintf("%-50.s","".t("Tilaaja").":")										."".t("Toimitusosoite").":\r\n";
						$ulos .= sprintf("%-50.s",$laskurow['nimi'])								.$laskurow['toim_nimi']."\r\n";
						$ulos .= sprintf("%-50.s",$laskurow['nimitark'])							.$laskurow['toim_nimitark']."\r\n";
						$ulos .= sprintf("%-50.s",$laskurow['osoite'])								.$laskurow['toim_osoite']."\r\n";
						$ulos .= sprintf("%-50.s",$laskurow['postino']." ".$laskurow['postitp'])	.$laskurow['toim_postino']." ".$laskurow['toim_postitp']."\r\n";
						$ulos .= "\r\n";
						$ulos .= sprintf("%-50.s","".t("Toimitus").": ".$laskurow['toimitustapa'])			."".t("Tilausnumero").": ".$laskurow['tunnus']."\r\n";
						$ulos .= sprintf("%-50.s","".t("Tilausviite").": ".$laskurow['viesti'])				."".t("Myyjä").": ".$laskurow['kukanimi']."\r\n";

						if ($laskurow['comments']!='')
							$ulos .= "".t("Kommentti").": ".$laskurow['comments']."\n";
							$ulos .= "\r\n";
							$ulos .= "".t("Nimitys")."                       ".t("Tuotenumero")."          ".t("Tilattu")."            ".t("Toimitetaan")."\r\n";
							$ulos .= "---------------------------------------------------------------------------------\r\n";
							$ulos .= $rivit."\r\n";


						if ($laskurow["email"] != '' and $laskurow["kerayspoikkeama"] == 0) {
							$boob = mail($laskurow["email"],  "$yhtiorow[nimi] - ".t("Keräyspoikkeamat")."", $ulos, $header, "-f $yhtiorow[postittaja_email]");
							if ($boob===FALSE) echo " - ".t("Email lähetys epäonnistui")."!<br>";
						}

						if ($kukarow["eposti"] != '') {
							if (($laskurow["email"] == '' or $boob === FALSE) and $laskurow["kerayspoikkeama"] == 0) {
								$ulos = t("Asiakkaalta puuttuu sähköpostiosoite! Keräyspoikkeamia ei voitu lähettää!")."\r\n\r\n\r\n".$ulos;
							}
							elseif ($laskurow["kerayspoikkeama"] == 1) {
								$ulos = t("Asiakkaalle on merkitty että hän ei halua keräyspoikkeama ilmoituksia!")."\r\n\r\n\r\n".$ulos;
							}
							else {
								$ulos = t("Tämä viesti on lähetetty myös asiakkaalle")."!\r\n\r\n\r\n".$ulos;
							}

							$ulos = t("Tilauksen keräsi").": $keraaja[nimi]\r\n\r\n".$ulos;

							$boob = mail($kukarow["eposti"],  "$yhtiorow[nimi] - ".t("Keräyspoikkeamat")."", $ulos, $header, "-f $yhtiorow[postittaja_email]");
							if ($boob===FALSE) echo " - ".t("Email lähetys epäonnistui")."!<br>";
						}
					}
				}

				// Tutkitaan vielä aivan lopuksi mihin tilaan me laitetaan tämä otsikko
				// Keräysvaiheessahan tilausrivit muuttuvat ja tarkastamme nyt tilanteen uudestaan
				// Tämä tehdään vain myyntitilauksille
				if ($tila == "L") {
					$kutsuja = "keraa.php";

					$query = "	SELECT *
								FROM lasku
								WHERE tunnus in ($tilausnumeroita)
								and yhtio = '$kukarow[yhtio]'
								and tila  = 'L'";
					$lasresult = mysql_query($query) or pupe_error($query);

					while($laskurow = mysql_fetch_array($lasresult)) {
						require("tilaus-valmis-valitsetila.inc");
					}
				}

				//Tulostetaan uusi lähete jos käyttäjä valitsi drop-downista printterin
				//Paitsi jos tilauksen tila päivitettiin sellaiseksi, että lähetettä ei kuulu tulostaa
				$query = "	SELECT *
							FROM lasku
							WHERE tunnus in ($tilausnumeroita)
							and yhtio = '$kukarow[yhtio]'
							and alatila = 'C'";
				$lasresult = mysql_query($query) or pupe_error($query);

				while($laskurow = mysql_fetch_array($lasresult)) {

					//tulostetaan faili ja valitaan sopivat printterit
					if ($laskurow["varasto"] == '') {
						$query = "	select *
									from varastopaikat
									where yhtio='$kukarow[yhtio]'
									order by alkuhyllyalue,alkuhyllynro
									limit 1";
					}
					else {
						$query = "	select *
									from varastopaikat
									where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[varasto]'
									order by alkuhyllyalue,alkuhyllynro";
					}
					$prires = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($prires) > 0) {

						$prirow= mysql_fetch_array($prires);

						// käteinen muuttuja viritetään tilaus-valmis.inc:issä jos maksuehto on käteinen
						// ja silloin pitää kaikki lähetteet tulostaa aina printteri5:lle (lasku printteri)
						if ($kateinen == 'X') {
							$apuprintteri = $prirow['printteri5']; // laskuprintteri
						}
						else {
							if ($valittu_tulostin == "oletukselle") {
								$apuprintteri = $prirow['printteri1']; // läheteprintteri
							}
							else {
								$apuprintteri = $valittu_tulostin;
							}
						}

						//haetaan lähetteen tulostuskomento
						$query   = "select * from kirjoittimet where yhtio='$kukarow[yhtio]' and tunnus='$apuprintteri'";
						$kirres  = mysql_query($query) or pupe_error($query);
						$kirrow  = mysql_fetch_array($kirres);
						$komento = $kirrow['komento'];


						if ($valittu_oslapp_tulostin == "oletukselle") {
							$apuprintteri = $prirow['printteri3']; // osoitelappuprintteri
						}
						else {
							$apuprintteri = $valittu_oslapp_tulostin;
						}

						//haetaan osoitelapun tulostuskomento
						$query  = "select * from kirjoittimet where yhtio='$kukarow[yhtio]' and tunnus='$apuprintteri'";
						$kirres = mysql_query($query) or pupe_error($query);
						$kirrow = mysql_fetch_array($kirres);
						$oslapp = $kirrow['komento'];
					}

					if ($valittu_tulostin != '' and $komento != "" and $lahetekpl > 0) {

						$otunnus = $laskurow["tunnus"];

						//hatetaan asiakkaan lähetetyyppi
						$query = "  SELECT lahetetyyppi, luokka, puhelin
									FROM asiakas
									WHERE tunnus='$laskurow[liitostunnus]' and yhtio='$kukarow[yhtio]'";
						$result = mysql_query($query) or pupe_error($query);
						$asrow = mysql_fetch_array($result);

						$lahetetyyppi = "";

						if ($asrow["lahetetyyppi"] != '') {
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
							$vrow = mysql_fetch_array($vres);

							if ($vrow["selite"] != '' and file_exists($vrow["selite"])) {
								$lahetetyyppi = $vrow["selite"];
							}
						}

						if ($lahetetyyppi == "tulosta_lahete_alalasku.inc") {
							require_once ("tulosta_lahete_alalasku.inc");
						}
						elseif (strpos($lahetetyyppi,'simppeli') !== FALSE) {
							require_once ("$lahetetyyppi");
						}
						else {
							require_once ("tulosta_lahete.inc");
						}

						//	Jos meillä on funktio tulosta_lahete meillä on suora funktio joka hoitaa koko tulostuksen
						if(function_exists("tulosta_lahete")) {
							if($vrow["selite"] != '') {
								$tulostusversio = $vrow["selite"];
							}
							else {
								$tulostusversio = $asrow["lahetetyyppi"];
							}

							tulosta_lahete($otunnus, $komento["Lähete"], $kieli = "", $toim, $tee, $tulostusversio);
						}
						else {
							// katotaan miten halutaan sortattavan
							$sorttauskentta = generoi_sorttauskentta();

							if($laskurow["tila"] == "L" or $laskurow["tila"] == "N") {
								$tyyppilisa = " and tilausrivi.tyyppi in ('L') ";
							}
							else {
								$tyyppilisa = " and tilausrivi.tyyppi in ('L','G','W') ";
							}

							//generoidaan lähetteelle ja keräyslistalle rivinumerot
							$query = "  SELECT tilausrivi.*,
										round(if(tuote.myymalahinta != 0, tuote.myymalahinta, tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1)),2) ovhhinta,
										round(tilausrivi.hinta * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100)),2) rivihinta,
										$sorttauskentta,
										if(tilausrivi.var='J', 1, 0) jtsort
										FROM tilausrivi
										JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
										JOIN lasku ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus
										WHERE tilausrivi.otunnus = '$otunnus'
										and tilausrivi.yhtio = '$kukarow[yhtio]'
										$tyyppilisa
										ORDER BY jtsort, sorttauskentta";
							$riresult = mysql_query($query) or pupe_error($query);

							//generoidaan rivinumerot
							$rivinumerot = array();

							while ($row = mysql_fetch_array($riresult)) {
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

							// Aloitellaan lähetteen teko
							$page[$sivu] = alku();

							while ($row = mysql_fetch_array($riresult)) {
								rivi($page[$sivu]);
								$total+= $row["rivihinta"];
							}

							//Vikan rivin loppuviiva
							$x[0] = 20;
							$x[1] = 580;
							$y[0] = $y[1] = $kala + $rivinkorkeus - 4;
							$pdf->draw_line($x, $y, $page[$sivu], $rectparam);

							loppu($page[$sivu], 1);
							
							//katotaan onko laskutus nouto
							$query = "  SELECT toimitustapa.nouto, maksuehto.kateinen
										FROM lasku 
										JOIN toimitustapa ON lasku.yhtio = toimitustapa.yhtio and lasku.toimitustapa = toimitustapa.selite
										JOIN maksuehto ON lasku.yhtio = maksuehto.yhtio and lasku.maksuehto = maksuehto.tunnus
										WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tunnus = '$laskurow[tunnus]'
										and toimitustapa.nouto != '' and maksuehto.kateinen = ''";
							$kures = mysql_query($query) or pupe_error($query);

							if (mysql_num_rows($kures) > 0) {
								kuittaus();
							}

							if ($lahetetyyppi == "tulosta_lahete_alalasku.inc") {
								alvierittely($page[$sivu]);
							}

							//tulostetaan sivu
							if ($lahetekpl > 1) {
								$komento .= " -#$lahetekpl ";
							}

							print_pdf($komento);
						}
					}


					if ($yhtiorow['karayksesta_rahtikirjasyottoon'] != '') {
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

						require ("osoitelappu_pdf.inc");
					}

					echo "<br><br>";
				} //tulostetaan uusi lähete jos käyttäjä ruksasi ruudun. lopuu tähän
			}


			$boob    			= '';
			$header  			= '';
			$content 			= '';
			$rivit   			= '';


			if ($yhtiorow['karayksesta_rahtikirjasyottoon'] != '') {
				$query =	"SELECT tunnus FROM lasku
							WHERE lasku.yhtio = '$kukarow[yhtio]'
							and lasku.tila = 'L'
							and lasku.alatila = 'C'
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
				$tilausnumeroita 	= '';
				$rahtikirjaan = '';
				$id = 0;
			}
		}
	}

	if ($id == '') $id = 0;

	// meillä ei ole valittua tilausta
	if ($id == 0) {

		$formi	= "find";
		$kentta	= "etsi";

		echo "<table>";
		echo "<form action='$PHP_SELF' name='find' method='post'>";
		echo "<input type='hidden' name='toim' value='$toim'>";
		echo "<tr><td>".t("Valitse varasto:")."</td><td><select name='tuvarasto' onchange='submit()'>";

		$query = "	SELECT tunnus, nimitys
					FROM varastopaikat
					WHERE yhtio = '$kukarow[yhtio]'
					ORDER BY nimitys";
		$result = mysql_query($query) or pupe_error($query);

		echo "<option value='KAIKKI'>".t("Näytä kaikki")."</option>";

		while ($row = mysql_fetch_array($result)){
			$sel = '';
			if (($row[0] == $tuvarasto) or ($kukarow['varasto'] == $row[0] and $tuvarasto=='')) {
				$sel = 'selected';
				$tuvarasto = $row[0];
			}
			echo "<option value='$row[0]' $sel>$row[1]</option>";
		}
		echo "</select>";

		$query = "	SELECT distinct maa
					FROM varastopaikat
					WHERE maa != '' and yhtio = '$kukarow[yhtio]'
					ORDER BY maa";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 1) {
			echo "<select name='tumaa' onchange='submit()'>";
			echo "<option value=''>".t("Kaikki")."</option>";

			while ($row = mysql_fetch_array($result)){
				$sel = '';
				if ($row[0] == $tumaa) {
					$sel = 'selected';
					$tumaa = $row[0];
				}
				echo "<option value='$row[0]' $sel>$row[0]</option>";
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
		echo "<option value='ENNAKK' $selb>".t("Näytä ennakkotilausket")."</option>";
		echo "<option value='JTTILA' $selc>".t("Näytä jt-tilausket")."</option>";

		echo "</select></td></tr>";

		echo "<tr><td>".t("Valitse toimitustapa:")."</td><td><select name='tutoimtapa' onchange='submit()'>";

		$query = "	SELECT selite
					FROM toimitustapa
					WHERE yhtio = '$kukarow[yhtio]'
					ORDER BY selite";
		$result = mysql_query($query) or pupe_error($query);

		echo "<option value='KAIKKI'>".t("Näytä kaikki")."</option>";

		while($row = mysql_fetch_array($result)){
			$sel = '';
			if($row[0] == $tutoimtapa) {
				$sel = 'selected';
				$tutoimtapa = $row[0];
			}
			echo "<option value='$row[0]' $sel>".asana('TOIMITUSTAPA_',$row[0])."</option>";
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
						WHERE maa != '' and yhtio = '$kukarow[yhtio]' and maa = '$tumaa'";
			$maare = mysql_query($query) or pupe_error($query);
			$maarow = mysql_fetch_array($maare);
			$haku .= " and lasku.varasto in ($maarow[tunnukset]) ";
		}

		if ($tutoimtapa != '' and $tutoimtapa != 'KAIKKI') {
			$haku .= " and lasku.toimitustapa='$tutoimtapa' ";
		}

		if ($tutyyppi != '' and $tutyyppi != 'KAIKKI') {
			if ($tutyyppi == "NORMAA") {
				$haku .= " and lasku.clearing='' ";
			}
			elseif($tutyyppi == "ENNAKK") {
				$haku .= " and lasku.clearing='ENNAKKOTILAUS' ";
			}
			elseif($tutyyppi == "JTTILA") {
				$haku .= " and lasku.clearing='JT-TILAUS' ";
			}
		}

		$query = "	select distinct
					if(lasku.kerayslista!=0, lasku.kerayslista, lasku.tunnus) tunnus,
					concat_ws(' ', lasku.toim_nimi, lasku.toim_nimitark) asiakas,
					date_format(lasku.luontiaika, '%Y-%m-%d') laadittu,
					lasku.laatija,
					lasku.kerayspvm,
					lasku.toimaika,
					concat_ws('<br><br>',if(comments!='',concat('".t("Lähetteen lisätiedot").":<br>',comments),NULL), if(sisviesti2!='',concat('".t("Keräyslistan lisätiedot").":<br>',sisviesti2),NULL)) ohjeet
					from lasku use index (tila_index),
					tilausrivi use index (yhtio_otunnus)
					where
					lasku.yhtio						= '$kukarow[yhtio]'
					and lasku.tila					in ($tila)
					and lasku.alatila				= 'A'
					and tilausrivi.yhtio			= lasku.yhtio
					and tilausrivi.otunnus			= lasku.tunnus
					and tilausrivi.tyyppi			in ($tyyppi)
					and tilausrivi.var				in ('', 'H' $var_lisa)
					and tilausrivi.keratty	 		= ''
					and tilausrivi.kerattyaika		= '0000-00-00 00:00:00'
					and tilausrivi.laskutettu		= ''
					and tilausrivi.laskutettuaika 	= '0000-00-00'
					$haku
					$tilaustyyppi
					GROUP BY tunnus
					ORDER BY laadittu";
		$result = mysql_query($query) or pupe_error($query);

		//piirretään taulukko...
		if (mysql_num_rows($result)!=0) {

			echo "<br><table>";

			echo "<tr>";
			echo "<th>".t("Tilaus")."</th>";
			echo "<th>".t("Asiakas")."</th>";
			echo "<th>".t("Laatija")."</th>";
			echo "<th>".t("Laadittu")."</th>";

			if ($kukarow['resoluutio'] == 'I') {
				echo "<th>".t("Kerayspvm")."</th>";
				echo "<th>".t("Toimaika")."</th>";
			}

			echo "</tr>";

			while ($row = mysql_fetch_array($result)) {
				echo "<tr class='aktiivi'>";

				if(trim($row["ohjeet"]) != "") {
					echo "<div id='$row[tunnus]' class='popup' style='width: 500px;'>";
					echo $row["ohjeet"]."<br>";
					echo "</div>";
					echo "<td valign='top'><a class='menu' onmouseout=\"popUp(event,'$row[tunnus]')\" onmouseover=\"popUp(event,'$row[tunnus]')\">$row[tunnus]</a></td>";
				}
				else {
					echo "<td valign='top'>$row[tunnus]</td>";
				}

				echo "<td valign='top'>$row[asiakas]</td>";
				echo "<td valign='top'>$row[laatija]</td>";
				echo "<td valign='top'>".tv1dateconv($row["laadittu"])."</td>";

				if ($kukarow['resoluutio'] == 'I') {
					echo "<td valign='top'>".tv1dateconv($row["kerayspvm"])."</td>";
					echo "<td valign='top'>".tv1dateconv($row["toimaika"])."</td>";
				}

				echo "<form method='post' action='$PHP_SELF'><td class='back'>
						<input type='hidden' name='id' value='$row[tunnus]'>
						<input type='hidden' name='toim' value='$toim'>
						<input type='submit' name='tila' value='".t("Kerää")."'></td></tr></form>";
			}

			echo "</table>";
		}
		else {
			echo "<font class='message'>".t("Yhtään keräämätöntä tilausta ei löytynyt")."...</font>";
		}
	}

	if ($id != 0 and $rahtikirjaan == '') {

		//päivitä kerätyt formi
		$formi="rivit";
		$kentta="keraajanro";

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
			$toimrow = mysql_fetch_array($toimresult);
			$tilausnumeroita = $toimrow["tunnukset"];
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
						osoite, concat_ws(' ', postino, postitp) postitp,
						toim_osoite, concat_ws(' ', toim_postino, toim_postitp) toim_postitp,
						clearing,
						tila
						FROM lasku LEFT JOIN kuka ON lasku.myyja = kuka.tunnus
						WHERE lasku.tunnus in ($tilausnumeroita)
						and lasku.yhtio = '$kukarow[yhtio]'
						and tila in ($tila)
						and alatila	= 'A'";
			$result = mysql_query($query) or pupe_error($query);
			$otsik_row    = mysql_fetch_array($result);

			echo "<tr><th>" . t("Tilaus") ."</th><td>$tilausnumeroita $otsik_row[clearing]</td></tr>";
			echo "<tr><th>" . t("Asiakas") ."</th><td>$otsik_row[nimi]<br>$otsik_row[toim_nimi]</td></tr>";
			echo "<tr><th>" . t("Laskutusosoite") ."</th><td>$otsik_row[osoite], $otsik_row[postitp]</td></tr>";
			echo "<tr><th>" . t("Toimitusosoite") ."</th><td>$otsik_row[toim_osoite], $otsik_row[toim_postitp]</td></tr>";
		}

		$query = "	SELECT
					concat_ws(' ',tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso) varastopaikka,
					concat_ws(' ',tilausrivi.tuoteno, tilausrivi.nimitys) tuoteno,
					tilausrivi.tuoteno puhdas_tuoteno,
					tilausrivi.varattu, tilausrivi.jt, tilausrivi.keratty, tilausrivi.tunnus, tilausrivi.var, tilausrivi.tilkpl,
					tuote.ei_saldoa, tuote.sarjanumeroseuranta, tuote.tuoteno tuote,
					concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0')) sorttauskentta
					FROM tilausrivi, tuote
					WHERE tuote.yhtio=tilausrivi.yhtio
					and tuote.tuoteno=tilausrivi.tuoteno
					and tilausrivi.var in ('', 'H' $var_lisa)
					and otunnus in ($tilausnumeroita)
					and tilausrivi.yhtio	= '$kukarow[yhtio]'
					and tilausrivi.tyyppi	in ($tyyppi)
					and tilausrivi.kerattyaika = '0000-00-00 00:00:00'
					ORDER BY tilausrivi.perheid, sorttauskentta, tilausrivi.tuoteno, tilausrivi.tunnus";
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

			while($kurow = mysql_fetch_array($kuresult)) {
				if ($kukarow['kuka']!=$kurow['kuka'])
					echo "<option value='$kurow[keraajanro]'>$kurow[nimi]</option>";
			}

			echo "</table><br><br>";

			echo "	<table>
					<tr>
					<th>".t("Varastopaikka")."</th>
					<th>".t("Tuoteno")."</th>
					<th>".t("Kpl")."</th>
					<th>".t("Poikkeava määrä")."</th>";

			if ($yhtiorow["kerayspoikkeama_kasittely"] != '') {
				echo "<th>".t("Poikkeaman käsittely")."</th>";
			}

			echo "</tr>";

			$i=0;

			while($row = mysql_fetch_array($result)) {

				if ($row['var']=='P') {
					// jos kyseessä on puuterivi
					$puute 			= t("PUUTE");
					$row['varattu']	= $row['tilkpl'];
				}
				elseif ($row['var']=='J') {
					// jos kyseessä on JT-rivi
					$puute 			= t("**JT**");
					$row['varattu']	= $row['jt'];
				}
				elseif ($row['var']=='H') {
					// jos kyseessä on väkisinhyväksytty-rivi
					$puute 			= "...........";
				}
				else {
					$puute			= '';
					$ker			= '';
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

					echo "	</tr>
							<input type='hidden' name='kerivi[]' value='$row[tunnus]'>
							<input type='hidden' name='maara[$row[tunnus]]'>";
				}
				else {
					echo "	<tr class='aktiivi'>
							<td>$row[varastopaikka]</td>
							<td>$row[tuoteno]</td>
							<td>$row[varattu]</td>
							<td><input type='text' size='4' name='maara[$row[tunnus]]' value='$maara[$i]'> $puute";

					if ($row["sarjanumeroseuranta"] == "S" or $row["sarjanumeroseuranta"] == "T") {

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

						$query = "	select count(*) kpl, min(sarjanumero) sarjanumero
									from sarjanumeroseuranta
									where yhtio  = '$kukarow[yhtio]'
									and tuoteno  = '$row[puhdas_tuoteno]'
									and $tunken1 = '$row[tunnus]'";
						$sarjares = mysql_query($query) or pupe_error($query);
						$sarjarow = mysql_fetch_array($sarjares);

						if ($sarjarow["kpl"] == abs($row["varattu"])) {
							echo " (<a href='sarjanumeroseuranta.php?tuoteno=$row[puhdas_tuoteno]&$tunken2=$row[tunnus]&from=KERAA&aputoim=$toim&otunnus=$id#$sarjarow[sarjanumero]' style='color:00FF00'>".t("S:nro OK")."</font></a>)";
						}
						else {
							echo " (<a href='sarjanumeroseuranta.php?tuoteno=$row[puhdas_tuoteno]&$tunken2=$row[tunnus]&from=KERAA&aputoim=$toim&otunnus=$id#$sarjarow[sarjanumero]'>".t("S:nro")."</a>)";
						}
					}
					elseif ($row["sarjanumeroseuranta"] == "E" or $row["sarjanumeroseuranta"] == "F") {
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

						$query = "	select sarjanumero, parasta_ennen
									from sarjanumeroseuranta
									where yhtio  = '$kukarow[yhtio]'
									and tuoteno  = '$row[puhdas_tuoteno]'
									and $tunken1 = '$row[tunnus]'
									LIMIT 1";
						$sarjares = mysql_query($query) or pupe_error($query);
						$sarjarow = mysql_fetch_array($sarjares);

						echo t("Erä").": $sarjarow[sarjanumero]";

						if ($row["sarjanumeroseuranta"] == "F") {
							echo " ".t("Parasta ennen").": ".tv1dateconv($sarjarow["parasta_ennen"]);
						}
					}

					echo "</td>";

					if ($yhtiorow["kerayspoikkeama_kasittely"] != '') {
						echo "<td><select name='poikkeama_kasittely[$row[tunnus]]'>";
						echo "<option value=''>".t("Oletus")."</option>";
						echo "<option value='JT'>".t("JT")."</option>";
						echo "<option value='PU'>".t("Puute")."</option>";
						echo "<option value='UT'>".t("Uusi tilaus")."</option>";
						echo "<option value='MI'>".t("Mitätöi")."</option>";
						echo "</select></td>";
					}

					echo "<input type='hidden' name='kerivi[]' value='$row[tunnus]'></tr>";
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

			$spanni = 2;

			if ($yhtiorow['karayksesta_rahtikirjasyottoon'] != '') {
				$spanni = 3;
			}

			echo "<tr><th>".t("Lähete").":</th><th colspan='$spanni'>";

			$query = "	SELECT *
						FROM kirjoittimet
						WHERE
						yhtio='$kukarow[yhtio]'
						ORDER by kirjoitin";
			$kirre = mysql_query($query) or pupe_error($query);

			echo "<select name='valittu_tulostin'>";

			echo "<option value=''>".t("Ei tulosteta")."</option>";
			echo "<option value='oletukselle' $sel>".t("Oletustulostimelle")."</option>";

			while ($kirrow = mysql_fetch_array($kirre)) {
				echo "<option value='$kirrow[tunnus]'>$kirrow[kirjoitin]</option>";
			}

			echo "</select> ".t("Kpl").": <input type='text' size='4' name='lahetekpl' value='$lahetekpl'></th><th></th>";

			if ($yhtiorow["kerayspoikkeama_kasittely"] != '') {
				echo "<th></th>";
			}

			echo "</tr>";

			echo "<tr>";

			if ($yhtiorow['karayksesta_rahtikirjasyottoon'] == '') {
				echo "<th>".t("Osoitelappu").":</th>";

				echo "<th colspan='$spanni'>";

				mysql_data_seek($kirre, 0);

				echo "<select name='valittu_oslapp_tulostin'>";
				echo "<option value=''>".t("Ei tulosteta")."</option>";
				echo "<option value='oletukselle' $sel>".t("Oletustulostimelle")."</option>";

				while ($kirrow = mysql_fetch_array($kirre)) {
					echo "<option value='$kirrow[tunnus]'>$kirrow[kirjoitin]</option>";
				}

				echo "</select> ".t("Kpl").": <input type='text' size='4' name='oslappkpl' value='$oslappkpl'></th>";
			}
			else {
				echo "<th></th><th></th>";
			}

			if ($yhtiorow["kerayspoikkeama_kasittely"] != '') {
				echo "<th></th>";
			}

			echo "<th><input type='submit' value='".t("Merkkaa kerätyksi")."'></th></form></tr>";
			echo "</table>";

			if ($yhtiorow['karayksesta_rahtikirjasyottoon'] != '') {
				echo "<br><font class='message'>".t("Siirryt automaattisesti rahtikirjan syöttöön")."!</font>";
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
