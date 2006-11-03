<?php

	//Sis‰‰n haluamme
	// $otunnus jossa on kopioitavan tilauksen tunnus
	// $tilrivilisa muuttujalla voidaan rajailla mit‰ tilausrivej‰ kopsataan
	// $syot_varasto jos halutaan myyd‰ tietyst‰ varastosta
	// $syot_var voidaan ohjata var-kentt‰‰

	if (!function_exists("tilauksesta_myyntitilaus")) {
		function tilauksesta_myyntitilaus($otunnus, $tilrivilisa, $syot_varasto, $syot_var) {
			global $yhtiorow, $kukarow;

			$tilauksesta_myyntitilaus = "";


			$query = "	SELECT *
						FROM tilausrivi
						WHERE yhtio = '$kukarow[yhtio]'
						and otunnus = '$otunnus'
						and tyyppi != 'D'
						$tilrivilisa";
			$copresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($copresult) == 0) {
				$tilauksesta_myyntitilaus = t("Tarjouksella ei ollut yht‰‰n laskutettavaa rivi‰");
				return;
			}

			//Kopioidaan otsikko ja siirret‰‰n rivit uudelle otsikolle
			$query = "SELECT * FROM lasku WHERE tunnus='$otunnus' and yhtio ='$kukarow[yhtio]'";
			$monistares = mysql_query($query) or pupe_error($query);
			$monistarow = mysql_fetch_array($monistares);

			$fields = mysql_field_name($monistares,0);
			$values = "'".$monistarow[0]."'";

			for($i=1; $i < mysql_num_fields($monistares)-1; $i++) { // Ei monisteta tunnusta

				$fields .= ", ".mysql_field_name($monistares,$i);

				switch (mysql_field_name($monistares,$i)) {
					case 'luontiaika':
						$values .= ", now()";
						break;
					case 'alatila':
						$values .= ", ''";
						break;
					case 'tila':
						$values .= ", 'N'";
						break;
					case 'tilaustyyppi':
						$values .= ", ''";
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
					case 'viikorkoeur':
					case 'tullausnumero':
					case 'laskutuspvm':
					case 'erpcm':
					case 'laskuttaja':
					case 'laskutettu':
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
						$values .= ", '".$monistarow[$i]."'";
				}
			}

			$kysely  = "INSERT into lasku ($fields) VALUES ($values)";
			$insres  = mysql_query($kysely) or pupe_error($kysely);
			$utunnus = mysql_insert_id();

			//Siirret‰‰n maksusuunnitelma
			$query = "	SELECT *
						FROM maksupositio
						WHERE maksupositio.yhtio ='$kukarow[yhtio]'
						and maksupositio.otunnus ='$otunnus'
						ORDER BY tunnus";
			$copresult = mysql_query($query) or pupe_error($query);

			if(mysql_num_rows($copresult) > 0) {
				$tilauksesta_myyntitilaus .= "<font class='message'>Siirret‰‰n maksusuunnitelma tilaukselle: $utunnus</font><br>";

				while ($coprivirow = mysql_fetch_array($copresult)) {
					$query = "	INSERT INTO maksupositio
								SET yhtio 	= '$kukarow[yhtio]',
								otunnus 	= '$utunnus',
								luotu		= '$coprivirow[luotu]',
								luonut		= '$coprivirow[luonut]',
								osuus 		= '$coprivirow[osuus]',
								summa  		= '$coprivirow[summa]',
								kuvaus 		= '$coprivirow[kuvaus]',
								maksuehto 	= '$coprivirow[maksuehto]',
								lisatiedot 	= '$coprivirow[lisatiedot]',
								ohje 		= '$coprivirow[ohje]'";
					$result = mysql_query($query) or pupe_error($query);
				}
			}

			$kukarowkesken 		= $kukarow["kesken"];
			$kukarow["kesken"]	= $utunnus;

			$tilauksesta_myyntitilaus .= "<font class='message'>Lis‰t‰‰n valitut rivit myyntitilaukselle: $utunnus</font><br>";

			$query    = "select * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$utunnus'";
			$laskures = mysql_query($query);
			$laskurow = mysql_fetch_array($laskures);

			//Siirret‰‰n tilausrivit
			$query = "	SELECT *
						FROM tilausrivi
						WHERE yhtio = '$kukarow[yhtio]'
						and otunnus = '$otunnus'
						and tyyppi != 'D'
						$tilrivilisa
						ORDER by perheid, tunnus";
			$copresult = mysql_query($query) or pupe_error($query);

			while ($coprivirow = mysql_fetch_array($copresult)) {

				$tuoteno	= $coprivirow['tuoteno'];

				if ($coprivirow["var"] == "J" or $coprivirow["var"] == "S" or $coprivirow["var"] == "T") {
					$kpl	= $coprivirow['jt'];
				}
				elseif ($tilausrivi["var"] == "P") {
					$kpl	= $coprivirow['tilkpl'];
				}
				else {
					$kpl	= $coprivirow['varattu'];
				}

				$hinta		= $coprivirow['hinta'];
				$netto		= $coprivirow['netto'];
				$ale		= $coprivirow['ale'];
				$kommentti	= $coprivirow['kommentti'];
				$kerayspvm	= $coprivirow['kerayspvm'];
				$toimaika	= $coprivirow['toimaika'];
				$alv		= $coprivirow['alv'];
				$ytunnus	= $laskurow["ytunnus"];
				$rivinumero = $coprivirow['tilaajanrivinro'];

				$perheid	= $coprivirow['perheid'];

				$jaksotettu = $coprivirow['jaksotettu'];

				$paikka		= "";
				$paikat		= "";
				$rivitunnus = "";

				if ($syot_var != "") {
					$var	= $syot_var;
				}
				else {
					$var	= $coprivirow['var'];
				}

				if ((int) $syot_varasto > 0) {
					$varasto = $syot_varasto;
				}
				elseif ($laskurow["varasto"] > 0) {
					$varasto = $laskurow["varasto"];
				}
				else {
					$varasto = "";
				}

				$korvaavakielto		= "";
				$varataan_saldoa	= "";

				$query = "SELECT * FROM tuote WHERE yhtio='$kukarow[yhtio]' and tuoteno='$coprivirow[tuoteno]'";
				$tuote_result = mysql_query($query) or pupe_error($query);
				$trow = mysql_fetch_array($tuote_result);

				$tilauksesta_myyntitilaus .= "<font class='message'>Lis‰t‰‰n rivi $tuoteno $kpl kpl tilaukselle $utunnus</font><br>";

				require('lisaarivi.inc');


				// Korjataan sarjanumero-oliot, eli siirret‰‰n ne tarjousrivilt‰ tilausriville, jos kopioitava tilaus on tarjous
				if ($monistarow["tila"] == "T") {
					if ($coprivirow["varattu"] > 0) {

						$lisax = "myyntirivitunnus";
					}
					else {
						$lisax = "ostorivitunnus";
					}

					$query = "	SELECT $lisax, count(*) kpl
								FROM sarjanumeroseuranta
								WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
								and sarjanumeroseuranta.tuoteno = '$coprivirow[tuoteno]'
								and sarjanumeroseuranta.$lisax  = '$coprivirow[tunnus]'
								group by $lisax";
					$sarjares = mysql_query($query) or pupe_error($query);
					$sarjarow = mysql_fetch_array($sarjares);


					foreach($lisatyt_rivit as $lisatty_tun) {
						$query = "	SELECT *
									FROM tilausrivi
									WHERE yhtio = '$kukarow[yhtio]'
									and otunnus = '$utunnus'
									and tunnus  = '$lisatty_tun'";
						$lisres = mysql_query($query) or pupe_error($query);
						$lisrow = mysql_fetch_array($lisres);

						if ($lisrow["varattu"] != 0) {
							$lisrow["varattu"] = (int) abs(round($lisrow["varattu"], 0));

							$query = "	UPDATE sarjanumeroseuranta
										SET $lisax = '$lisrow[tunnus]'
										WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
										and sarjanumeroseuranta.tuoteno = '$coprivirow[tuoteno]'
										and sarjanumeroseuranta.$lisax  = '$coprivirow[tunnus]'
										LIMIT $lisrow[varattu]";
							$cores = mysql_query($query) or pupe_error($query);
						}
					}

					//Jos jotakin kopioitavan rivin sarjanumeroista ei saatu kopioitua niin irrotetaan se silt‰ rivilt‰
					$query = "	UPDATE sarjanumeroseuranta
								SET $lisax = 0
								WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
								and sarjanumeroseuranta.tuoteno = '$coprivirow[tuoteno]'
								and sarjanumeroseuranta.$lisax  = '$coprivirow[tunnus]'";
					$cores = mysql_query($query) or pupe_error($query);
				}
			}

			//Korjataan perheid:t uusilla riveill‰
			$query = "	SELECT perheid, min(tunnus) uusiperheid
						FROM tilausrivi
						WHERE yhtio = '$kukarow[yhtio]'
						and otunnus = '$utunnus'
						and perheid != 0
						GROUP by perheid";
			$copresult = mysql_query($query) or pupe_error($query);

			while ($coprivirow = mysql_fetch_array($copresult)) {
				$query = "	UPDATE tilausrivi
							SET perheid = '$coprivirow[uusiperheid]'
							WHERE yhtio = '$kukarow[yhtio]'
							and otunnus = '$utunnus'
							and perheid = '$coprivirow[perheid]'";
				$cores = mysql_query($query) or pupe_error($query);
			}

			//Jos kyseess‰ on laskutettava myyntitili
			if($monistarow["tila"] == "G" and $monistarow["alatila"] == "V" and $monistarow["tilaustyyppi"] == "M") {
				// P‰ivitet‰‰n kopsatun laskun eilahetett‰ kentt‰‰
				$query = "	UPDATE lasku
							SET eilahetetta = 'o'
							where yhtio = '$kukarow[yhtio]'
							and tunnus = '$kukarow[kesken]'";
				$result = mysql_query($query) or pupe_error($query);

				$query 	= "	select *
							from lasku
							where tunnus = '$kukarow[kesken]'
							and yhtio = '$kukarow[yhtio]'";
				$result  	= mysql_query($query) or pupe_error($query);
				$laskurow   = mysql_fetch_array($result);

				require("tilaus-valmis.inc");
			}

			//Korjataan globaali muuttuja
			$kukarow["kesken"] = $kukarowkesken;

			return $tilauksesta_myyntitilaus;
		}
	}
?>
