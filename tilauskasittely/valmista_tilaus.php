<?php
	require ("../inc/parametrit.inc");

	if ($toim == 'KORJAA') {
		echo "<font class='head'>".t("Korjaa valmistus").":</font><hr>";		
	}
	elseif ($toim == 'TUOTE') {
		echo "<font class='head'>".t("Valmista tuotteita").":</font><hr>";
	}
	else {
		echo "<font class='head'>".t("Valmista tilaus").":</font><hr>";		
	}

	if ($tee == 'NAYTATILAUS') {
		require ("../raportit/naytatilaus.inc");
		echo "<hr>";
		$tee = "VALITSE";
	}

	if ($tee == "SYOTARIVI") {
		echo t("Tee uusi rivi").":<br>";

		echo "	<form method='post' action='$PHP_SELF' autocomplete='off'>";
		echo "	<input type='hidden' name='tee' value='LISAARIVI'>
				<input type='hidden' name='valmistettavat' value='$valmistettavat'>
				<input type='hidden' name='toim'  value='$toim'>
				<input type='hidden' name='perheid'  value='$perheid'>
				<input type='hidden' name='otunnus'  value='$otunnus'>";

		require('syotarivi.inc');
		require('../inc/footer.inc');
		exit;
	}

	if(!function_exists("onkokaikkivalmistettu")) {
		function onkokaikkivalmistettu ($valmkpllat) {
			global $kukarow, $tee, $valmistettavat;
			
			//katotaan onko enää mitään valmistettavaa
			foreach ($valmkpllat as $rivitunnus => $valmkpl) {
				//Haetaan tilausrivi
				$query = "	SELECT otunnus, uusiotunnus
							FROM tilausrivi
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus = $rivitunnus
							and tyyppi in ('W','M')";
				$roxresult = mysql_query($query) or pupe_error($query);
				$tilrivirow = mysql_fetch_array($roxresult);

				//Katsotaan onko yhtään valmistamatonta riviä tällä tilauksella/jobilla
				$query = "	SELECT tunnus
							FROM tilausrivi
							WHERE yhtio	= '$kukarow[yhtio]'
							and otunnus = $tilrivirow[otunnus]
							and tyyppi	in ('W','M')
							and tunnus	= perheid
							and toimitettuaika = '0000-00-00 00:00:00'";
				$chkresult1 = mysql_query($query) or pupe_error($query);

				//eli tilaus on kokonaan valmistettu
				if (mysql_num_rows($chkresult1) == 0) {
					//Jos kyseessä on varastovalmistus
					$query = "	UPDATE lasku
								SET alatila	= 'V'
								WHERE yhtio = '$kukarow[yhtio]'
								and tunnus  = $tilrivirow[otunnus]
								and tila	= 'V'
								and alatila = 'C'
								and tilaustyyppi = 'W'";
					$chkresult2 = mysql_query($query) or pupe_error($query);

					//Jos kyseessä on asiakaalle valmistus
					$query = "	UPDATE lasku
								SET tila	= 'L',
								alatila 	= 'C'
								WHERE yhtio = '$kukarow[yhtio]'
								and tunnus  = $tilrivirow[otunnus]
								and tila	= 'V'
								and alatila = 'C'
								and tilaustyyppi = 'V'";
					$chkresult2 = mysql_query($query) or pupe_error($query);

					$tee	 		= "";
					$valmistettavat = "";
				}
				else {
					$tee = "VALMISTA";
				}

				//jos rivit oli siirretty toiselta otsikolta niin siirretään ne nyt takaisin
				if ($tilrivirow["uusiotunnus"] != 0 and mysql_num_rows($chkresult1) == 0) {
					$query = "	UPDATE tilausrivi
								SET otunnus = uusiotunnus,
								uusiotunnus = 0
								WHERE yhtio = '$kukarow[yhtio]'
								and otunnus = $tilrivirow[otunnus]
								and uusiotunnus = $tilrivirow[uusiotunnus]
								and uusiotunnus != 0";
					$chkresult3 = mysql_query($query) or pupe_error($query);

					//tutkitaan pitääkö alkuperäisen otsikon tilat päivittää
					$query = "	SELECT tunnus
								FROM tilausrivi
								WHERE yhtio	= '$kukarow[yhtio]'
								and (otunnus = $tilrivirow[uusiotunnus] or uusiotunnus = $tilrivirow[uusiotunnus])
								and tyyppi	in ('W','M')
								and tunnus	= perheid
								and toimitettuaika = '0000-00-00 00:00:00'";
					$selres = mysql_query($query) or pupe_error($query);

					//eli alkuperäinen tilaus on kokonaan valmistettu
					if (mysql_num_rows($selres) == 0) {
						//Jos kyseessä on varastovalmistus
						$query = "	UPDATE lasku
									SET alatila	= 'V'
									WHERE yhtio = '$kukarow[yhtio]'
									and tunnus  = '$tilrivirow[uusiotunnus]'
									and tila	= 'V'
									and alatila in ('C','Y')
									and tilaustyyppi = 'W'";
						$chkresult4 = mysql_query($query) or pupe_error($query);

						//Jos kyseessä on asiakaalle valmistus
						$query = "	UPDATE lasku
									SET tila	= 'L',
									alatila 	= 'C'
									WHERE yhtio = '$kukarow[yhtio]'
									and tunnus  = '$tilrivirow[uusiotunnus]'
									and tila	= 'V'
									and alatila in ('C','Y')
									and tilaustyyppi = 'V'";
						$chkresult4 = mysql_query($query) or pupe_error($query);
					}
				}
			}
		}
	}
	
	if ($tee == "LISAARIVI") {

		$query	= "	SELECT *
					from tuote
					where tuoteno='$tuoteno' and yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {
			//Tuote löytyi
			$trow = mysql_fetch_array($result);

			$laskurow["tila"] 	= 'V';
			$kukarow["kesken"]	= $otunnus;
			$perheid 			= $perheid;

			require('lisaarivi.inc');

			$lisatyt_rivit = array_merge($lisatyt_rivit1, $lisatyt_rivit2);

			if ($lisatyt_rivit[0] > 0) {
				$valmistettavat .= ",".$lisatyt_rivit[0];

				$query = "	UPDATE tilausrivi
							SET toimitettu	= '$kukarow[kuka]',
							toimitettuaika	= now(),
							keratty			= '$kukarow[kuka]',
							kerattyaika		= now()
							WHERE yhtio	= '$kukarow[yhtio]'
							and tunnus	= '$lisatyt_rivit[0]'";
				$result = mysql_query($query) or pupe_error($query);

			}
		}
		else {
			echo t("Tuotetta ei löydy")."!<br>";
		}

		$tee = "VALMISTA";
	}

	//HUOMHUOM!!
	$query = "SET SESSION group_concat_max_len = 100000";
	$result = mysql_query($query) or pupe_error($query);

	if ($tee == 'alakorjaa') {
		//Päivitetään lasku niin, että se on takaisin tilassa valmistettu
		$query = "	SELECT lasku.tunnus, lasku.tila
					FROM tilausrivi, lasku
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
					and	tilausrivi.tunnus in ($valmistettavat)
					and lasku.tunnus=tilausrivi.otunnus
					and lasku.yhtio=tilausrivi.yhtio";
		$result = mysql_query($query) or pupe_error($query);

		while ($row = mysql_fetch_array($result)) {
			if ($row["tila"] == "L") {
				$kalatila = "X";
			}
			else {
				$kalatila = "V";
			}

			$query = "	UPDATE lasku
						SET alatila	= '$kalatila'
						WHERE yhtio = '$kukarow[yhtio]'
						and tunnus 	= '$row[tunnus]'
						and tila	= '$row[tila]'
						and alatila = 'K'";
			$chkresult4 = mysql_query($query) or pupe_error($query);
		}

		$valmistettavat = "";
		$tee = "";
	}

	if ($tee == 'TEEVALMISTUS' and count($valmkpllat) == 0 and count($tilkpllat) == 0) {

		//katotaan onko enää mitään valmistettavaa		
		onkokaikkivalmistettu ($valmisteet_chk);				
	}

	if ($tee == 'TEEVALMISTUS' and $vakisinhyvaksy == '') {

		if (isset($tuotenumerot) and is_array($tuotenumerot) and count($tuotenumerot) > 0) {

			$saldot = array();
			$saldot_valm = array();
			
			// Tän verran on saldoilla
			foreach ($tuotenumerot as $tuotenumero) {
				list ($saldo, $hyllyssa, $myytavissa, $true) = saldo_myytavissa($tuotenumero);
				
				$saldot[$tuotenumero] = $saldo;
				$saldot_valm[$tuotenumero] = $saldo;				
			}
			
			// Tän verran tuotetaan lisää saldoja tällä ajolla, otetaan nekin huomioon
			foreach ($valmkpllat as $rivitunnus => $valmkpl) {

				$valmkpl = str_replace(',','.',$valmkpl);

				//Haetaan tilausrivi
				$query = "	SELECT *, trim(concat_ws(' ', tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso)) paikka
							FROM tilausrivi
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus = '$rivitunnus'
							and tyyppi in ('W','M')
							and toimitettuaika = '0000-00-00 00:00:00'";
				$roxresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($roxresult) == 1) {
					$tilrivirow = mysql_fetch_array($roxresult);
					
					if (($valmkpl > 0 and $valmkpl <= $tilrivirow["varattu"]) or ($kokopros > 0 and $kokopros <= 100) or isset($kokovalmistus)) {

						if ($valmkpl > 0) {
							$atil = round($valmkpl, 2);
						}
						elseif($valmkpl == "") {
							$atil =  $tilrivirow["varattu"];
						}

						if ($kokopros > 0) {
							$atil = round($kokopros / 100 * $tilrivirow["varattu"],2);
						}
												
						$saldot_valm[$tilrivirow["tuoteno"]] += $atil;						
					}
				}
			}
			
			
			// Tehdään saldotsekit	
			foreach ($valmkpllat as $rivitunnus => $valmkpl) {

				$valmkpl = str_replace(',','.',$valmkpl);

				//Haetaan tilausrivi
				$query = "	SELECT tilausrivi.*, trim(concat_ws(' ', tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso)) paikka, tuote.sarjanumeroseuranta
							FROM tilausrivi
							JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
							WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
							and tilausrivi.tunnus = '$rivitunnus'
							and tilausrivi.tyyppi in ('W','M')
							and tilausrivi.toimitettuaika = '0000-00-00 00:00:00'";
				$roxresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($roxresult) == 1) {
					$tilrivirow = mysql_fetch_array($roxresult);
					
					//	Tarkastetaan, että sarjanumeroseurattuja tuotteita valmistetaan tasamäärä.
					if(($tilrivirow["sarjanumeroseuranta"] == "S" or $tilrivirow["sarjanumeroseuranta"] == "U") and $valmkpl != (int) $valmkpl) {
						echo "<font class='error'>".t("VIRHE: Sarjanumeroseurattua tuotetta ei voi valmistaa vain osittain!")."</font><br>";
						$tee = "VALMISTA";
					}
					elseif (($valmkpl > 0 and $valmkpl <= $tilrivirow["varattu"]) or ($kokopros > 0 and $kokopros <= 100) or isset($kokovalmistus)) {

						if ($valmkpl > 0) {
							$atil = round($valmkpl, 2);
						}
						elseif($valmkpl == "") {
							$atil =  $tilrivirow["varattu"];
						}

						if ($kokopros > 0) {
							$atil = round($kokopros / 100 * $tilrivirow["varattu"],2);
						}
						
						$akerroin = $atil / $tilrivirow["varattu"];

						//käytetään tilausriveillä olevia tuotteita
						$query = "	SELECT tilausrivi.*, trim(concat_ws(' ', tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso)) paikka, tuote.ei_saldoa, tuote.sarjanumeroseuranta
									FROM tilausrivi
									JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
									WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
									and tilausrivi.otunnus = '$tilrivirow[otunnus]'
									and tilausrivi.perheid = '$tilrivirow[perheid]'
									and tilausrivi.tyyppi = 'V'
									ORDER by tilausrivi.tuoteno";
						$perheresult = mysql_query($query) or pupe_error($query);

						//jos tuoteperhe on olemassa
						if (mysql_num_rows($perheresult) > 0) {
							while ($perherow = mysql_fetch_array($perheresult)) {
								if ($perherow["ei_saldoa"] == "") {
									
									//Näin paljon haluamme käyttää raaka-ainetta
									if ($kulukpllat[$perherow["tunnus"]] != 0) {
										$varataankpl = $kulukpllat[$perherow["tunnus"]];
									}
									else {
										$varataankpl = $perherow['varattu'] * $akerroin;
									}									
									
									//katotaan kanssa, että perheenjäsenet löytyy kannasta ja niitä on riittävästi
									if ($saldot_valm[$perherow["tuoteno"]] < $varataankpl) {
										echo "<font class='error'>Saldo ".$saldot[$perherow["tuoteno"]]." ei riitä! Tuotetta $perherow[tuoteno] kulutetaan $varataankpl ".ta($kieli, "Y", $perherow["yksikko"]).".</font><br>";
										$virheitaoli 	= "JOO";
										$tee 			= "VALMISTA";
									}
									
																		// Tän verran saldoa käytetään
									$saldot_valm[$perherow["tuoteno"]] -= $kpl_chk;									
								}
							}
						}
					}
					elseif ($valmkpl > 0) {
						echo "<font class='error'>VIRHE: Syötit liian ison kappalemäärän!</font><br>";
						$virheitaoli 	= "JOO";
						$tee 			= "VALMISTA";
					}
				}
			}
			echo "<br>";
		}	
	}

	if ($tee == 'TEEVALMISTUS' and isset($osatoimitus)) {
		// Osatoimitetaan valitut rivit
		if (count($osatoimitetaan) > 0) {
			$tilrivilisa = implode(',', $osatoimitetaan);

			require("tilauksesta_myyntitilaus.inc");

			$query = "	SELECT otunnus, group_concat(tunnus) tunnukset
						FROM tilausrivi
						WHERE yhtio = '$kukarow[yhtio]'
						and tunnus  in ($tilrivilisa)
						GROUP BY otunnus";
			$copresult = mysql_query($query) or pupe_error($query);

			while ($coprow = mysql_fetch_array($copresult)) {

				$tillisa = " and tilausrivi.tunnus in ($coprow[tunnukset]) ";

				$tilauksesta_myyntitilaus = tilauksesta_myyntitilaus($coprow["otunnus"], $tillisa, "", "", "", "JOO");

				echo "$tilauksesta_myyntitilaus<br>";

				$query = "	UPDATE tilausrivi
							SET tyyppi = 'D'
							WHERE yhtio = '$kukarow[yhtio]'
							$tillisa
							and tyyppi	= 'L'";
				$chkresult4 = mysql_query($query) or pupe_error($query);
			}
		}

		$tee = "VALMISTA";
	}

	if ($tee == 'TEEVALMISTUS') {
		//Käydään läpi rivien kappalemäärät ja tehdään samalla pieni tsekki, että onko rivi jo valmistettu
		foreach ($tilkpllat as $rivitunnus => $tilkpl) {

			$tilkpl = str_replace(',','.',$tilkpl);

			// Tarkistetaan ettei tilaus ole jo toimitettu/laskutettu
			if ($toim == "KORJAA") {
				$ylatilat	= " 'V','L' ";
				$alatilat 	= " 'K' ";
			}
			else {
				$ylatilat	= " 'V' ";
				$alatilat 	= " 'C' ";
			}

			$query = "	SELECT distinct lasku.tunnus
						FROM tilausrivi, lasku
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and tilausrivi.tunnus = '$rivitunnus'
						and lasku.tunnus 	= tilausrivi.otunnus
						and lasku.tila 		in ($ylatilat)
						and lasku.alatila 	in ($alatilat)";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 1) {
				$lasrow = mysql_fetch_array($result);

				//Haluaako käyttäjä muuttaa rivillä olevaa määrää
				if($edtilkpllat[$rivitunnus] != $tilkpl) {

					//	Lasketaan kerroin jos teemme rekursiivisesti
					if($rekru[$rivitunnus] != "") {
						//	Varmistetaan, että tämä on perheen isä
						$query = "	SELECT tunnus
									FROM tilausrivi
									WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$rivitunnus' and tunnus = perheid";
						$result = mysql_query($query) or pupe_error($query);
						if(mysql_num_rows($result) <> 1) {
							$virhe[$rivitunnus] = "<font class='message'>".t("Tuote ei ole perheen isä. Rekursiivinen päivitys ei onnistu.")."!</font>";
						}
						else {
							$perhekerroin = $tilkpl/$edtilkpllat[$rivitunnus];
							$query = "	UPDATE tilausrivi
										SET varattu = (varattu * $perhekerroin)
										WHERE yhtio = '$kukarow[yhtio]'
										and otunnus = '$lasrow[tunnus]'
										and perheid  = '$rivitunnus' and perheid > 0
										and tyyppi = 'V'";
							$updresult = mysql_query($query) or pupe_error($query);
							$virhe[$rivitunnus] = "<font class='message'>".t("Perheen lapset kerrottiin %s:lla.", $kieli, round($perhekerroin, 4))."!</font><br>";
						}
					}

					$laskurow = mysql_fetch_array($result);

					$query = "	UPDATE tilausrivi
								SET varattu = '$tilkpl'
								WHERE yhtio = '$kukarow[yhtio]'
								and tunnus  = '$rivitunnus'";
					$updresult = mysql_query($query) or pupe_error($query);

					$tee = "VALMISTA";
					$virhe[$rivitunnus] .= "<font class='message'>".t("Valmistettava määrä päivitettiin")."!</font>";
				}
			}
			else {
				echo "<font class='error'>".t("Valmistus on jo korjattu")."!</font><br><br>";
				$tee = "XXX";
				break;
			}
		}

		// Jatketaan valmistusta
		if ($tee == "TEEVALMISTUS") {
			if ($toim == "KORJAA") {
				foreach ($valmkpllat as $rivitunnus => $valmkpl) {

					$valmkpl = str_replace(',','.',$valmkpl);

					$query = "	SELECT *, trim(concat_ws(' ', tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso)) paikka
								FROM tilausrivi
								WHERE yhtio = '$kukarow[yhtio]'
								and tunnus = '$rivitunnus'
								and tyyppi in ('W','M')";
					$roxresult = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($roxresult) == 1) {
						$tilrivirow = mysql_fetch_array($roxresult);

						$tuoteno 		= $tilrivirow["tuoteno"];
						$tee			= "UV";
						$varastopaikka  = $tilrivirow["paikka"];

						if ($perutamakorj[$rivitunnus] != "") {
							$perutaan = "JOO";
						}
						else {
							$perutaan = "";
						}

						require ("korjaa_valmistus.inc");
					}
				}
				//Päivitetään lasku niin, että se on tilassa valmistettu
				$query = "	UPDATE lasku
							SET alatila	= 'V'
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus  = '$tilrivirow[otunnus]'
							and tila	= 'V'
							and alatila = 'K'";
				$chkresult4 = mysql_query($query) or pupe_error($query);

				echo "<br><br><font class='message'>Valmistus korjattu!</font><br>";
			}
			else {
				foreach ($valmkpllat as $rivitunnus => $valmkpl) {

					$valmkpl = str_replace(',','.',$valmkpl);

					//Haetaan tilausrivi
					$query = "	SELECT *, trim(concat_ws(' ', tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso)) paikka
								FROM tilausrivi
								WHERE yhtio = '$kukarow[yhtio]'
								and tunnus = '$rivitunnus'
								and tyyppi in ('W','M')
								and toimitettuaika = '0000-00-00 00:00:00'";
					$roxresult = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($roxresult) == 1) {
						$tilrivirow = mysql_fetch_array($roxresult);

						// Otetaan tiedot alkuperäiseltä tilaukselta
						if ($tilrivirow["uusiotunnus"] != 0) {
							$slisa = " and lasku.tunnus = tilausrivi.uusiotunnus";
						}
						else {
							$slisa = " and lasku.tunnus = tilausrivi.otunnus ";
						}

						$query = "	SELECT lasku.*
									FROM tilausrivi, lasku
									WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
									and tilausrivi.tunnus = '$rivitunnus'
									$slisa
									and lasku.tila = 'V'";
						$result = mysql_query($query) or pupe_error($query);
						$laskurow = mysql_fetch_array($result);

						if (($valmkpl > 0 and $valmkpl <= $tilrivirow["varattu"]) or ($kokopros > 0 and $kokopros <= 100) or isset($kokovalmistus)) {

							if ($valmkpl > 0) {
								$atil = round($valmkpl, 2);
							}
							elseif($valmkpl == "") {
								$atil =  $tilrivirow["varattu"];
							}

							if ($kokopros > 0) {
								$atil = round($kokopros / 100 * $tilrivirow["varattu"],2);
							}

							$akerroin 		= $atil / $tilrivirow["varattu"];
							$valmistetaan 	= "TILAUKSELTA";
							$tuoteno 		= $tilrivirow["tuoteno"];
							$tee			= "UV";
							$varastopaikka  = $tilrivirow["paikka"];
							$jaljella_tot	= 0;

							require ("../valmistatuotteita.inc");

							//jos valmistus meni ok, niin palautuu $tee == UV
							if (round($jaljella_tot, 2) == 0 and $tee == "UV") {
								//päivitetään tämä perhe valmistetuksi
								$query = "	UPDATE tilausrivi
											SET toimitettu = '$kukarow[kuka]',
											toimitettuaika = now(),
											varattu = 0
											WHERE yhtio = '$kukarow[yhtio]'
											and otunnus = '$tilrivirow[otunnus]'
											and tyyppi in ('W','V','M')
											and perheid = '$tilrivirow[perheid]'";
								$updresult = mysql_query($query) or pupe_error($query);
							}

							if ($tee != "UV") {
								$virheitaoli = "JOO";
								$valmkpllat2[$rivitunnus] = $valmkpl;
							}
						}
						elseif ($valmkpl == 0 or $valmkpl == "") {
							$virhe[$rivitunnus] = "<font class='message'>Kappalemäärä ei syötetty!</font>";
							$tee = "VALMISTA";
							$valmkpllat2[$rivitunnus] = $valmkpl;
						}
						else {
							$virhe[$rivitunnus] = "<font class='error'>VIRHE: Syötit liian ison kappalemäärän!</font>";
							$tee = "VALMISTA";
							$valmkpllat2[$rivitunnus] = $valmkpl;
						}
					}
				}

				//katotaan onko enää mitään valmistettavaa
				onkokaikkivalmistettu ($valmkpllat);	
			}

						$tee	 		= "";
					}
					}

	if ($tee == "VALMISTA" and $valmistettavat != "") {
		//Haetaan otsikoiden tiedot
		$query = "	SELECT
					GROUP_CONCAT(DISTINCT lasku.tunnus SEPARATOR ', ') 'Tilaus',
					GROUP_CONCAT(DISTINCT lasku.nimi SEPARATOR ', ') 'Asiakas/Nimi',
					GROUP_CONCAT(DISTINCT lasku.ytunnus SEPARATOR ', ') 'Ytunnus'
					FROM tilausrivi, lasku
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
					and	tilausrivi.tunnus in ($valmistettavat)
					and lasku.tunnus=tilausrivi.otunnus
					and lasku.yhtio=tilausrivi.yhtio";
		$result = mysql_query($query) or pupe_error($query);
		
		if (mysql_num_rows($result) == 0) {
			echo "<font class='error'>Yhtään tilausta ei löytynyt</font><br>";
			$tee = "";
		}
	}

	if ($tee == "VALMISTA" and $valmistettavat != "") {	
		$row = mysql_fetch_array($result);

		//Päivitetään lasku niin, että se on tilassa korjataan
		if ($toim == "KORJAA") {
			$query = "	UPDATE lasku
						SET alatila	= 'K'
						WHERE yhtio = '$kukarow[yhtio]'
						and tunnus  in ($row[Tilaus])
						and tila	in ('V','L')
						and alatila in ('V','X')";
			$chkresult4 = mysql_query($query) or pupe_error($query);
			
			$korjataan = " and tilausrivi.toimitettu != '' ";
		}

		//Jos valmistetaan per tuote niin valinnasta/hausta tulee vain valmisteiden tunnukset, tarvitsemme kuitenkin myös raaka-aineiden tunnukset joten haetaan ne tässä
		if ($toim == "TUOTE" and $tulin == "VALINNASTA") {
			$query = "	SELECT
						GROUP_CONCAT(DISTINCT tilausrivi.tunnus SEPARATOR ',') valmistettavat
						FROM tilausrivi
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and tilausrivi.otunnus in ($row[Tilaus])
						and	tilausrivi.perheid in ($valmistettavat)";
			$ktres = mysql_query($query) or pupe_error($query);
			$ktrow = mysql_fetch_array($ktres);

			$valmistettavat = $ktrow["valmistettavat"];
		}

		echo "<table>";
		for ($i=0; $i < mysql_num_fields($result); $i++) {
			echo "<tr><th align='left'>" . t(mysql_field_name($result,$i)) ."</th><td>$row[$i]</td></tr>";
		}
		echo "</table><br>";

		//Haetaan valmistettavat valmisteet ja käytettävät raaka-aineet
		$query = "	SELECT tilausrivi.nimitys,
					tilausrivi.tuoteno,
					tilkpl tilattu,
					if(tyyppi!='L', varattu, 0) valmistetaan,
					if(tyyppi='L' or tyyppi='D', varattu, 0) valmistettu,
					if(toimitettu!='', if(varattu!=0, varattu, kpl), 0) korjataan,
					if(toimitettu!='', kpl, 0) valmistettu_valmiiksi,
					if(tyyppi!='L', kpl, 0) kaytetty,
					toimaika,
					kerayspvm,
					tilausrivi.tunnus tunnus,
					tilausrivi.perheid,
					tilausrivi.tyyppi,
					tilausrivi.toimitettuaika,
					tilausrivi.otunnus otunnus,
					tilausrivi.uusiotunnus laskutettu,
					tilausrivi.kommentti,
					tuote.ei_saldoa,
					tilausrivi.kommentti,
					tuote.sarjanumeroseuranta,
					tilausrivi.varattu,
					tilausrivi.var
					FROM tilausrivi, tuote
					WHERE
					tilausrivi.otunnus in ($row[Tilaus])
					and tilausrivi.tunnus in ($valmistettavat)
					and tilausrivi.yhtio='$kukarow[yhtio]'
					and tuote.yhtio=tilausrivi.yhtio
					and tuote.tuoteno=tilausrivi.tuoteno
					and tyyppi in ('V','W','M','L','D')
					$korjataan
					ORDER BY perheid desc, tyyppi in ('W','M','L','D','V'), tunnus";
		$presult = mysql_query($query) or pupe_error($query);
		$riveja = mysql_num_rows($presult);

		echo "<table><tr>";
		$miinus = 3;

		echo "<th>#</th>";

		echo "<th>".t("Nimitys")."</th>";
		echo "<th>".t("Tuoteno")."</th>";
		echo "<th>".t("Tilattu")."</th>";
		echo "<th>".t("Valmistetaan")."</th>";
		echo "<th>".t("Valmistettu")."</th>";
		echo "<th>".t("Kommentti")."</th>";
		echo "<th>".t("Toimaka")."</th>";
		echo "<th>".t("Keräysaika")."</th>";

		if ($toim != 'KORJAA' and $toim != 'TUTKAA') {
			echo "<th>".t("Valmista")."</th>";
		}

		echo "</tr>";

		$rivkpl = mysql_num_rows($presult);
		$voikokorjata = 0;

		$vanhaid = "KALA";

		echo "	<form method='post' action='$PHP_SELF' autocomplete='off'>";
		echo "	<input type='hidden' name='tee' value='TEEVALMISTUS'>
				<input type='hidden' name='valmistettavat' value='$valmistettavat'>
				<input type='hidden' name='toim'  value='$toim'>";

		while ($prow = mysql_fetch_array ($presult)) {

			$class = '';

			if ($vanhaid != $prow["perheid"] and $vanhaid != 'KALA') {
				echo "<tr><td class='back' colspan='7'><br></td></tr>";
			}
			
			if($prow["tyyppi"] == 'W' or $prow["tyyppi"] == 'M') {
				// Nämä ovat valmisteita
				$class = "spec";				
				
				echo "<input type='hidden' name='valmisteet_chk[$prow[tunnus]]' value='$prow[tuoteno]'>";				
			}
			elseif ($prow["tyyppi"] == 'D') {
				// Nämä ovat jo valmistettu
				$class = "green";
			}
			else {
				// Tässä tulevat kaikki raaka-aineet
				
				// tehdään salditsekki vain saldollisille raaka-aineille
				if ($prow["ei_saldoa"] == "") {
					echo "<input type='hidden' name='tuotenumerot[$prow[tunnus]]' value='$prow[tuoteno]'>";
				}
			}

			echo "<tr>";

			echo "<td valign='top' class='$class'>$rivkpl</td>";
			$rivkpl--;
			
			
			$sarjalinkkilisa = "";
			if (($prow["sarjanumeroseuranta"] == "S" or $prow["sarjanumeroseuranta"] == "T" or $prow["sarjanumeroseuranta"] == "U" or $prow["sarjanumeroseuranta"] == "V" or (($prow["sarjanumeroseuranta"] == "E" or $prow["sarjanumeroseuranta"] == "F" or $prow["sarjanumeroseuranta"] == "G") and $prow["varattu"] < 0)) and $prow["var"] != 'P' and $prow["var"] != 'T' and $prow["var"] != 'U') {

				$query = "	SELECT count(*) kpl 
							from sarjanumeroseuranta 
							where yhtio='$kukarow[yhtio]' and tuoteno='$prow[tuoteno]' and ostorivitunnus='$prow[tunnus]'";
				$sarjares = mysql_query($query) or pupe_error($query);
				$sarjarow = mysql_fetch_array($sarjares);

				if ($sarjarow["kpl"] == abs($prow["varattu"]+$prow["jt"])) {
					$sarjalinkkilisa = " (<a href='sarjanumeroseuranta.php?tuoteno=$prow[tuoteno]&ostorivitunnus=$prow[tunnus]&return=valmistus&from=valmistus#".urlencode($sarjarow["sarjanumero"])."' style='color:00FF00'>".t("S:nro ok")."</font></a>)";
				}
				else {
					$sarjalinkkilisa = " (<a href='sarjanumeroseuranta.php?tuoteno=$prow[tuoteno]&ostorivitunnus=$prow[tunnus]&return=valmistus&from=valmistus'>".t("S:nro")."</a>)";

					if ($laskurow['sisainen'] != '' or $laskurow['ei_lahetetta'] != '') {
						$sarjapuuttuu++;
						$tilausok++;
					}
				}
			}
			
			echo "<td class='$class' valign='top'>".asana('nimitys_',$prow['tuoteno'],$prow['nimitys'])."</td>";
			echo "<td class='$class' valign='top'><a href='../tuote.php?tee=Z&tuoteno=$prow[tuoteno]'>$prow[tuoteno]</a> $sarjalinkkilisa</td>";
			echo "<input type='hidden' name='tuotenumerot[$prow[tunnus]]' value='$prow[tuoteno]'>";
			echo "<td class='$class' valign='top' align='right'>$prow[tilattu]</td>";


			if ($toim == "KORJAA" and  $prow["tyyppi"] == 'V') {
				echo "<td valign='top' class='$class' align='left'>
					<input type='hidden' name='edtilkpllat[$prow[tunnus]]'  value='$prow[korjataan]'>
					<input type='text' size='8' name='tilkpllat[$prow[tunnus]]' value='$prow[korjataan]'>";
				echo "</td>";

				echo "<td valign='top' class='$class' align='left'>$prow[valmistettu_valmiiksi]</td>";
			}
			elseif ($prow["tyyppi"] == 'L' or $prow["tyyppi"] == 'D' or $prow["perheid"] == 0) {
				echo "<td valign='top' class='$class' align='left'></td>";
				echo "<td valign='top' class='$class' align='left'>$prow[valmistettu]</td>";
			}
			elseif ($prow["toimitettuaika"] == "0000-00-00 00:00:00") {
				echo "<td valign='top' class='$class' align='left'>
						<input type='hidden' name='edtilkpllat[$prow[tunnus]]'  value='$prow[valmistetaan]'>
						<input type='text' size='8' name='tilkpllat[$prow[tunnus]]' value='$prow[valmistetaan]'>";
				
				if($prow["tyyppi"] == "W" or $prow["tyyppi"] == "M") {
					// Isätuotteet, päivitetäänkö valmistettavat kappaleet koko reseptille
					echo "<br>R:<input type = 'checkbox' name = 'rekru[$prow[tunnus]]' checked>";
				}

				echo "</td>";
				echo "<td valign='top' class='$class'></td>";
			}
			elseif ($prow["tyyppi"] == 'V') {
				echo "<td valign='top' class='$class' align='right'></td>";
				echo "<td valign='top' class='$class' align='right'>$prow[kaytetty]</td>";
			}
			else {
				echo "<td valign='top' class='$class' align='right'></td>";
				echo "<td valign='top' class='$class'></td>";
			}

			echo "<td valign='top' class='$class' align='left'> $prow[kommentti]</td>";
			echo "<td valign='top' class='$class' align='right'>".tv1dateconv($prow["toimaika"])."</td>";
			echo "<td valign='top' class='$class' align='right'>".tv1dateconv($prow["kerayspvm"])."</td>";

			if($prow["tunnus"] != $prow["perheid"] and $prow["perheid"] > 0 and $prow["tyyppi"] == "V" and $prow["toimitettuaika"] == "0000-00-00 00:00:00" and $toim != "KORJAA") {

				if ($valmkpllat2[$prow["perheid"]] != 0) {
					$lapsivalue = $kulukpllat[$prow["tunnus"]];
				}
				else {
					$lapsivalue = "";
				}

				echo "<td valign='top' align='center'><input type='text' name='kulukpllat[$prow[tunnus]]' value='$lapsivalue' size='5'></td>";
			}

			if ($prow["tyyppi"] == "V") {
				echo "<td valign='top' class='back'>".$virhe[$prow["tunnus"]]."</td>";
			}

			if ($prow["tunnus"] == $prow["perheid"] and ($prow["tyyppi"] == "W" or $prow["tyyppi"] == "M") and $prow["toimitettuaika"] == "0000-00-00 00:00:00" and $toim != "KORJAA") {
				echo "<td valign='top' align='center'><input type='text' name='valmkpllat[$prow[tunnus]]' value='".$valmkpllat2[$prow["tunnus"]]."' size='5'></td><td class='back'>".$virhe[$prow["tunnus"]]."</td>";
			}
			elseif ($prow["tunnus"] != $prow["perheid"] and ($prow["tyyppi"] == "W" or $prow["tyyppi"] == "M") and $prow["toimitettuaika"] == "0000-00-00 00:00:00" and $toim != "KORJAA") {
				echo "<td valign='top' align='center'>UVA</td>";
			}
			elseif($prow["tunnus"] == $prow["perheid"] and ($prow["tyyppi"] == "W" or $prow["tyyppi"] == "M") and $prow["toimitettuaika"] != "0000-00-00 00:00:00" and $toim == "KORJAA") {
				//tutkitaan kuinka paljon tätä nyt oli valmistettu
				$query = "	SELECT sum(kpl) valmistetut
							FROM tilausrivi
							WHERE yhtio	= '$kukarow[yhtio]'
							and otunnus = '$prow[otunnus]'
							and perheid = '$prow[perheid]'
							and tyyppi	= 'D'
							and toimitettuaika = '0000-00-00 00:00:00'";
				$sumres = mysql_query($query) or pupe_error($query);
				$sumrow = mysql_fetch_array($sumres);

				$query = "	SELECT count(*) laskuja
							FROM lasku
							WHERE yhtio	= '$kukarow[yhtio]'
							and tunnus 	= '$prow[laskutettu]'
							and tila 	= 'U'
							and alatila	= 'X'";
				$slres = mysql_query($query) or pupe_error($query);
				$slrow = mysql_fetch_array($slres);

				if ($sumrow["valmistetut"] != 0 and $slrow["laskuja"] == 0) {
					echo "<td valign='top'><input type='hidden' name='valmkpllat[$prow[tunnus]]' value='$sumrow[valmistetut]'>";
					echo "<input type='checkbox' name='perutamakorj[$prow[tunnus]]' value='$prow[tunnus]'> Peru tämä valmistus.";

					$voikokorjata++;
				}
				elseif($sumrow["valmistetut"] != 0 and $slrow["laskuja"] > 0) {
					echo "<td valign='top' class='back'><font class='error'>Riviä ei voida perua!</font>";
					echo "<input type='hidden' name='valmkpllat[$prow[tunnus]]' value='$sumrow[valmistetut]'>";
					$voikokorjata++;
				}
				else {
					echo "<td valign='top' class='back'><font class='error'>Riviä ei voida korjata!</font>";
				}

				echo "<br><a href='$PHP_SELF?toim=$toim&tee=SYOTARIVI&valmistettavat=$valmistettavat&perheid=$prow[perheid]&otunnus=$prow[otunnus]'>Lisää raaka-aine</a></td>";
				echo "<td valign='top' class='back'>".$virhe[$prow["tunnus"]]."</td>";
			}

			if ($prow["tyyppi"] == "L" and $toim != "KORJAA" and $toim != 'TUTKAA') {
				echo "<td valign='top' align='center'><input type='checkbox' name='osatoimitetaan[$prow[tunnus]]' value='$prow[tunnus]'></td>";
			}

			echo "</tr>";

			$vanhaid = $prow["perheid"];

		}

		echo "<tr><td colspan='9' class='back'><br></td></tr>";

		if ($virheitaoli == "JOO") {
			
			$forcenap = "<td>".t("Väkisinvalmista vaikka raaka-aineiden saldo ei riitä").". <input type='checkbox' name='vakisinhyvaksy' value='OK'></td>";
			
			if (isset($kokovalmistus)) 								$kokovalmistus_force = $forcenap;
			elseif (isset($osavalmistus) and (int) $kokopros > 0)	$osavalmistuspros_force = $forcenap;
			elseif (isset($osavalmistus))  							$osavalmistus_force = $forcenap;
			elseif (isset($osatoimitus))							$osatoimitus_force = $forcenap;						
		}
		
		if ($toim != 'KORJAA' and $toim != 'TUTKAA') {
			echo "<tr><td colspan='8'>Valmista syötetyt kappaleet:</td><td><input type='submit' name='osavalmistus' id='osavalmistus' value='".t("Valmista")."'></td>$osavalmistus_force</tr>";
			echo "<tr><td colspan='4'>Valmista prosentti koko tilauksesta:</td><td colspan='4' align='right'><input type='text' name='kokopros' size='5'> % </td><td><input type='submit' name='osavalmistus' id='osavalmistus' value='".t("Valmista")."'></td>$osavalmistuspros_force</tr>";
			echo "<tr><td colspan='8'>Siirrä valitut valmisteet uudelle tilaukselle:</td><td><input type='submit' name='osatoimitus' id='osatoimitus' value='".t("Osatoimita")."'></td>$osatoimitus_force</tr>";
			echo "<tr><td colspan='8'>Valmista koko tilaus:</td><td><input type='submit' name='kokovalmistus' id='kokovalmistus' value='".t("Valmista")."'></td>$kokovalmistus_force";
		}
		elseif($toim == 'KORJAA' and $voikokorjata > 0) {
			echo "<tr><td colspan='8'>Korjaa koko valmistus:</td><td class='back'><input type='submit' name='' value='".t("Korjaa")."'></td>";
		}

		echo "</tr>";
		echo "</form>";
		echo "</table><br><br>";

		echo "	<form method='post' action='$PHP_SELF' autocomplete='off'>";
		echo "<input type='hidden' name='toim'  value='$toim'>";
		if ($toim != 'KORJAA') {
			echo "<input type='hidden' name='tee' value=''>";
			echo "<input type='submit' name='' value='".t("Valmis")."'>";
		}
		else {
			echo "<input type='hidden' name='tee' value='alakorjaa'>";
			echo "<input type='hidden' name='valmistettavat' value='$valmistettavat'>";
			echo "<input type='submit' name='' value='".t("Älä korjaa")."'>";
		}
		echo "</form>";
	}

	// meillä ei ole valittua tilausta
	if ($tee == "") {
		$formi="find";
		$kentta="etsi";

		// tehdään etsi valinta
		echo "<br><form action='$PHP_SELF' name='find' method='post'>";
		echo "<input type='hidden' name='toim'  value='$toim'>";

		if ($toim == "TUOTE") {
			echo t("Etsi valmistetta/raaka-ainetta").": ";
		}
		else {
			echo t("Etsi asiakasta/valmistusta").": ";
		}

		echo "<input type='text' name='etsi'><input type='Submit' value='".t("Etsi")."'></form>";

		$haku='';

		if ($toim == "TUOTE" and $etsi != "") {
			$haku = " and tilausrivi.tuoteno = '$etsi' ";
		}
		else {
			if (is_string($etsi))  $haku = " and lasku.nimi LIKE '%$etsi%' ";
			if (is_numeric($etsi)) $haku = " and lasku.tunnus = '$etsi' ";
		}


		if ($toim == "TUOTE") {
			$query 		= "	SELECT tilausrivi.tuoteno,
							sum(tilausrivi.varattu) varattu,
							GROUP_CONCAT(DISTINCT lasku.tunnus ORDER BY lasku.tunnus SEPARATOR '<br>') tunnus,
							GROUP_CONCAT(DISTINCT lasku.ytunnus SEPARATOR '<br>') ytunnus,
							GROUP_CONCAT(DISTINCT lasku.nimi SEPARATOR '<br>') nimi,
							GROUP_CONCAT(DISTINCT tilausrivi.tunnus SEPARATOR ',') valmistettavat";

			$grouppi	= " GROUP BY tilausrivi.tuoteno";
			$orderby 	= " order by tuoteno, lasku.tunnus, varattu";
			$ylatilat	= " 'V' ";
			$alatilat 	= " 'C','B' ";
			$lisa 		= " and tilausrivi.tyyppi in ('W','M')
							and tilausrivi.toimitettu = ''
							and tilausrivi.varattu != 0";
		}
		elseif ($toim == "KORJAA") {
			$query	 	= "	SELECT lasku.ytunnus, lasku.tila, lasku.nimi, lasku.nimitark, lasku.osoite, lasku.postino, lasku.postitp,
							lasku.toim_nimi, lasku.toim_nimitark, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp,
							lasku.maksuehto, lasku.tunnus, lasku.viesti, count(tilausrivi.tunnus) riveja,
							GROUP_CONCAT(DISTINCT tilausrivi.tunnus SEPARATOR ',') valmistettavat";

			$grouppi	= " GROUP BY lasku.tunnus";
			$ylatilat	= " 'V' ";
			$alatilat 	= " 'V', 'K' ";
			$orderby 	= " order by lasku.tunnus desc";
			$lisa 		= " and tilausrivi.toimitettu != '' ";
			$limit 		= " LIMIT 100 ";
		}
		elseif ($toim == "TUTKAA") {
			$query	 	= "	SELECT lasku.ytunnus, lasku.tila, lasku.nimi, lasku.nimitark, lasku.osoite, lasku.postino, lasku.postitp,
							lasku.toim_nimi, lasku.toim_nimitark, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp,
							lasku.maksuehto, lasku.tunnus, lasku.viesti, count(tilausrivi.tunnus) riveja,
							GROUP_CONCAT(DISTINCT tilausrivi.tunnus SEPARATOR ',') valmistettavat";

			$grouppi	= " GROUP BY lasku.tunnus";
			$ylatilat	= " 'V','L' ";
			$alatilat 	= " 'V','K','X' ";
			$orderby 	= " order by lasku.tunnus desc";
			$lisa 		= " ";
			$limit 		= " LIMIT 100 ";
		}
		else {
			$query	 	= "	SELECT lasku.ytunnus, lasku.nimi, lasku.nimitark, lasku.osoite, lasku.postino, lasku.postitp,
							lasku.toim_nimi, lasku.toim_nimitark, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp,
							lasku.maksuehto, lasku.tunnus, lasku.viesti, count(tilausrivi.tunnus) riveja,
							GROUP_CONCAT(DISTINCT tilausrivi.tunnus SEPARATOR ',') valmistettavat";

			$grouppi	= " GROUP BY lasku.tunnus";
			$ylatilat	= " 'V' ";
			$alatilat 	= " 'C','B' ";
			$orderby 	= " order by lasku.tunnus";
			$lisa 		= " ";
			$limit 		= " LIMIT 100 ";
		}

		$query .= "	from tilausrivi, lasku
					where tilausrivi.yhtio = '$kukarow[yhtio]'
					and lasku.yhtio = tilausrivi.yhtio
					and lasku.tunnus = tilausrivi.otunnus
					and lasku.tila 	in ($ylatilat)
					and lasku.alatila  in ($alatilat)
					$lisa
					$haku
					$grouppi
					$orderby
					$limit";
		$tilre = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($tilre) > 0) {
			echo "<table>";

			if ($toim == "KORJAA") {
				echo "<tr><th>".t("Valmistus")."</th><th>".t("Tyyppi")."</th><th>".t("Asiakas/Varasto")."</th><th>".t("Ytunnus")."</th><th>".t("Valmiste")."</th><tr>";
			}
			elseif ($toim == "TUOTE") {
				echo "<tr><th>".t("Valmistus")."</th><th>".t("Asiakas/Varasto")."</th><th>".t("Ytunnus")."</th><th>".t("Valmiste")."</th><th>".t("Määrä")."</th><tr>";
			}
			else {
				echo "<tr><th>".t("Valmistus")."</th><th>".t("Asiakas/Varasto")."</th><th>".t("Ytunnus")."</th><th>".t("Viesti")."</th><tr>";
			}

			while ($tilrow = mysql_fetch_array($tilre)) {

				echo "<tr><td valign='top'>$tilrow[tunnus]</td>";

				if ($toim == "KORJAA" and $tilrow["tila"] == "L") {
					echo "<td valign='top'>".t("Asiakkaallevalmistus")."</td>";
				}
				elseif ($toim == "KORJAA" and $tilrow["tila"] == "V") {
					echo "<td valign='top'>".t("Varastoonvalmistus")."</td>";
				}

				echo "<td valign='top'>$tilrow[nimi] $tilrow[nimitark]</td><td valign='top'>$tilrow[ytunnus]</td>";


				if ($toim == "TUOTE") {
					echo "<td valign='top'>$tilrow[tuoteno]</td><td valign='top'>$tilrow[varattu]</td>";
				}
				else {
					echo "<td valign='top'>$tilrow[viesti]</td>";
				}

				echo "	<form method='post' action='$PHP_SELF'><td class='back'>
						<input type='hidden' name='tee' value='VALMISTA'>
						<input type='hidden' name='tulin' value='VALINNASTA'>
						<input type='hidden' name='toim'  value='$toim'>
						<input type='hidden' name='valmistettavat' value='$tilrow[valmistettavat]'>
						<input type='submit' value='".t("Valitse")."'></td></tr></form>";
			}
			echo "</table>";
		}
		else {
			echo "<br><br><font class='message'>".t("Yhtään valmistettavaa tilausta/tuotetta ei löytynyt")."...</font>";
		}
	}

	require "../inc/footer.inc";
?>
