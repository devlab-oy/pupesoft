<?php

	if (!function_exists("asn_kohdistus")) {

		function asn_kohdistus() {

			global $yhtiorow, $kukarow;
			
			$php_cli = FALSE;

			if (php_sapi_name() == 'cli') {
				$php_cli = TRUE;
			}
			
			if (!$php_cli) {
				$d = "<br><br>";
			}
			else {
				$d = "\n\n";
			}

			// M‰‰ritell‰‰n k‰ytett‰v‰t muuttujat
			unset($edellinen_toimittaja);
			unset($edellinen_asn_numero);
			unset($edellinen_paketinnumero);
			unset($edellinen_paketintunniste);
			$paketin_tunnukset = array();
			$paketin_rivit = array();
			$paketin_tuotteet = array();
			$virheet = 0;
			$virhe = "";

			// Haetaan kaikki k‰sittelem‰ttˆm‰t ASN-rivit
			$query = "	SELECT *
						FROM asn_sanomat
						WHERE yhtio = '$kukarow[yhtio]'
						AND status = ''
						AND tilausrivi = ''
						AND laji = 'asn'
						ORDER BY toimittajanumero, asn_numero, paketinnumero";
			$result = pupe_query($query);

			$kaytetyt_tilausrivit = array();

			// Loopataan tuoterivit l‰pi
			while ($row = mysql_fetch_assoc($result)) {
				$loysin_lapsen = FALSE;
				
				if (count($kaytetyt_tilausrivit) == 0) {
					$tilausrivi_muuttuja = "";
				}
				else {
					$tilausrivi_muuttuja = "and tilausrivi.tunnus not in ('".implode("','", $kaytetyt_tilausrivit)."')";
				}

				// Toimittaja vaihtuu tai ASN-sanoma vaihtuu tai paketti vaihtuu ja ei ole virheit‰, teid‰‰n keikka ja suuntalava sek‰ kohdistetaan rivit
				if ((isset($edellinen_toimittaja) and $edellinen_toimittaja != $row["toimittajanumero"]) or (isset($edellinen_asn_numero) and $edellinen_asn_numero != $row["asn_numero"]) or (isset($edellinen_paketinnumero) and $edellinen_paketinnumero != $row["paketinnumero"])) {
					if ($virheet == 0) {
						asn_kohdista_suuntalava($edellinen_toimittaja, $edellinen_asn_numero, $paketin_rivit, $paketin_tuotteet, $paketin_tunnukset, $edellinen_paketintunniste,'');
					}
					else {
						$virheet = 0;
						echo "VIRHE: Paketti hylatty. Toimittaja $edellinen_toimittaja ASN-sanoma $edellinen_asn_numero $virhe Paketti $edellinen_paketinnumero $d";
					}
					$paketin_rivit = array();
					$paketin_tunnukset = array();
					$paketin_tuotteet = array();
				}

				// Katsotaan, lˆytyykˆ t‰m‰ rivi ostotilaukselta
				// Siirtym‰vaiheen j‰lkeen poista lasku.comments tilausnumero ehto !!!!

				// poikkeusk‰sittely‰ muutamalle toimittajalle.
				// Kun haetaan ja kohdistetaan niin t‰ll‰ toimittajalla pit‰‰ ensin "t‰ydell‰", sitten lopusta 3-merkki‰ pois, sen j‰lkeen viel‰ lis‰t‰‰n "090"

				if ($row["toimittajanumero"] == "123067") {
					$orgtuote = $row["toim_tuoteno"];
					$lyhennetty_tuoteno = substr($row["toim_tuoteno"], 0, -3);
					$jatkettu_tuoteno = $lyhennetty_tuoteno."090";
					
					if ($row["toim_tuoteno2"] != "") {
						$toinen_tuoteno = ",'{$row["toim_tuoteno2"]}'";
					}
					
					$poikkeus_tuoteno =" in ('$orgtuote','$lyhennetty_tuoteno','$jatkettu_tuoteno' $toinen_tuoteno)";
				}
				elseif ($row["toimittajanumero"] == "123453") {
					$suba = substr($row["toim_tuoteno"],0,3);
					$subb = substr($row["toim_tuoteno"],3);
					$tuote = $suba."-".$subb;
					$yhteen = $row["toim_tuoteno"];
					
					if ($row["toim_tuoteno2"] != "") {
						$toinen_tuoteno = ",'{$row["toim_tuoteno2"]}'";
					}
					
					$poikkeus_tuoteno = " in ('$tuote','$yhteen' $toinen_tuoteno) ";
				}
				else {
					
					if ($row["toim_tuoteno2"] != "") {
						$toinen_tuoteno = ",'{$row["toim_tuoteno2"]}'";
					}
					
					$poikkeus_tuoteno = " in ('$row[toim_tuoteno]' $toinen_tuoteno) ";
				}

				// tiukka 1
				$query = "	SELECT tilausrivi.*
							FROM tilausrivi
							JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio
											AND lasku.tunnus = tilausrivi.otunnus)
							JOIN toimi ON (toimi.yhtio = tilausrivi.yhtio
											AND toimi.tunnus = lasku.liitostunnus
											AND toimi.toimittajanro = '$row[toimittajanumero]'
											AND toimi.tyyppi !='P')
							JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio
															AND tuotteen_toimittajat.liitostunnus = toimi.tunnus
															AND tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno
															AND tuotteen_toimittajat.toim_tuoteno {$poikkeus_tuoteno})
							JOIN tuote on (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno and tuote.status != 'P')
							WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
							AND tilausrivi.tyyppi = 'O'
							AND tilausrivi.uusiotunnus = 0
							AND tilausrivi.otunnus = '$row[tilausnumero]' AND tilausrivi.tilaajanrivinro = '$row[tilausrivinpositio]'
							{$tilausrivi_muuttuja}
							ORDER BY laadittu asc";
				$kokeillaan = pupe_query($query);

				if (mysql_num_rows($kokeillaan) == 0) {
					// tiukka 2
					$query = "	SELECT tilausrivi.*
								FROM tilausrivi
								JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio
												AND lasku.tunnus = tilausrivi.otunnus)
								JOIN toimi ON (toimi.yhtio = tilausrivi.yhtio
												AND toimi.tunnus = lasku.liitostunnus
												AND toimi.toimittajanro = '$row[toimittajanumero]'
												AND toimi.tyyppi !='P')
								JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio
																AND tuotteen_toimittajat.liitostunnus = toimi.tunnus
																AND tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno
																AND tuotteen_toimittajat.toim_tuoteno {$poikkeus_tuoteno})
								JOIN tuote on (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno and tuote.status != 'P')
								WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
								AND tilausrivi.tyyppi = 'O'
								AND tilausrivi.uusiotunnus = 0
								AND lasku.comments like '%{$row["tilausnumero"]}%' AND tilausrivi.tilaajanrivinro = '$row[tilausrivinpositio]'
								{$tilausrivi_muuttuja}
								ORDER BY laadittu asc";
					$kokeillaan = pupe_query($query);
				}

				if (mysql_num_rows($kokeillaan) == 0) {
					// v‰ljempi 1
					$query = "	SELECT tilausrivi.*
								FROM tilausrivi
								JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio
												AND lasku.tunnus = tilausrivi.otunnus)
								JOIN toimi ON (toimi.yhtio = tilausrivi.yhtio
												AND toimi.tunnus = lasku.liitostunnus
												AND toimi.toimittajanro = '$row[toimittajanumero]'
												AND toimi.tyyppi !='P')
								JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio
																AND tuotteen_toimittajat.liitostunnus = toimi.tunnus
																AND tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno
																AND tuotteen_toimittajat.toim_tuoteno {$poikkeus_tuoteno})
								JOIN tuote on (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno and tuote.status != 'P')
								WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
								AND tilausrivi.tyyppi = 'O'
								AND tilausrivi.uusiotunnus = 0
								AND tilausrivi.otunnus = '$row[tilausnumero]'
								{$tilausrivi_muuttuja}
								ORDER BY laadittu asc";
					$kokeillaan = pupe_query($query);
				}

				if (mysql_num_rows($kokeillaan) == 0) {
					// v‰ljempi 2
					$query = "	SELECT tilausrivi.*
								FROM tilausrivi
								JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio
												AND lasku.tunnus = tilausrivi.otunnus)
								JOIN toimi ON (toimi.yhtio = tilausrivi.yhtio
												AND toimi.tunnus = lasku.liitostunnus
												AND toimi.toimittajanro = '$row[toimittajanumero]'
												AND toimi.tyyppi !='P')
								JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio
																AND tuotteen_toimittajat.liitostunnus = toimi.tunnus
																AND tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno
																AND tuotteen_toimittajat.toim_tuoteno {$poikkeus_tuoteno})
								JOIN tuote on (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno and tuote.status != 'P')
								WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
								AND tilausrivi.tyyppi = 'O'
								AND tilausrivi.uusiotunnus = 0
								AND lasku.comments like '%{$row["tilausnumero"]}%'
								{$tilausrivi_muuttuja}
								ORDER BY laadittu asc";
					$kokeillaan = pupe_query($query);

				}

				if (mysql_num_rows($kokeillaan) == 0) {
					// lˆys‰
					$query = "	SELECT tilausrivi.*
								FROM tilausrivi
								JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio
												AND lasku.tunnus = tilausrivi.otunnus)
								JOIN toimi ON (toimi.yhtio = tilausrivi.yhtio
												AND toimi.tunnus = lasku.liitostunnus
												AND toimi.toimittajanro = '$row[toimittajanumero]'
												AND toimi.tyyppi !='P')
								JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio
																AND tuotteen_toimittajat.liitostunnus = toimi.tunnus
																AND tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno
																AND tuotteen_toimittajat.toim_tuoteno {$poikkeus_tuoteno})
								JOIN tuote on (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno and tuote.status != 'P')
								WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
								AND tilausrivi.tyyppi = 'O'
								AND tilausrivi.uusiotunnus = 0
								{$tilausrivi_muuttuja}
								ORDER BY laadittu asc";
				}

				$checkres = pupe_query($query);
				
				$tilausrivirow = mysql_fetch_array($checkres);
				
				// tehd‰‰n tuoteperhe-satula-tapaus-checkki
				$tuoteperhesql = "	SELECT tuoteperhe.*, tuote.*, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllytaso, tuotepaikat.hyllyvali
									FROM tuoteperhe
									JOIN tuote on (tuote.yhtio = tuoteperhe.yhtio and tuote.tuoteno = tuoteperhe.tuoteno)
									JOIN tuotepaikat ON (tuotepaikat.yhtio = tuoteperhe.yhtio and tuotepaikat.tuoteno = tuote.tuoteno and tuotepaikat.oletus !='')
									WHERE tuoteperhe.yhtio = '{$kukarow["yhtio"]}'
									AND tuoteperhe.isatuoteno = '{$tilausrivirow["tuoteno"]}'
									AND tuoteperhe.tyyppi in ('P','')
									AND tuoteperhe.ohita_kerays !=''
									ORDER BY tuoteperhe.tuoteno, tuoteperhe.tunnus
									LIMIT 1";
				$tuoteresult = pupe_query($tuoteperhesql);
				
				if (mysql_num_rows($tuoteresult) > 0) {
					$lisatavarat = mysql_fetch_assoc($tuoteresult);
					// Jos t‰nne menn‰‰n niin jotenkin lis‰t‰‰n tilausriville runko.
					// Fundeeraus paussi.
					$loysin_lapsen = TRUE;
				}

				// Tarkistetaan kappaleet ja splitataan ostotilausrivi, jos kappaleita on asn-sanomalla VƒHEMMƒN kuin tilausrivill‰, vanhan tilausrivin tunnus j‰‰ j‰ljell‰
				if (mysql_num_rows($checkres) == 0) {
					// muutos. Jos tulee enemm‰n kuin ollaan tilattu, niin t‰m‰ ei ole virhe, vaan lis‰t‰‰n se tilaukselle uudeksi riviksi.
					$virheet++;
					echo "VIRHE: Sopivaa ostotilausrivia ei loydy. Toimittaja $row[toimittajanumero] ASN-sanoma $row[asn_numero] Paketti $row[paketinnumero] Tuote $row[toim_tuoteno] $d";
				}
				elseif ($row["kappalemaara"] == $tilausrivirow["varattu"]) {
					// p‰ivitet‰‰n tilausrivin tunnus talteen asn_sanomat-tauluun, koska t‰m‰ kohdistuu kyseiseen riviin
					$query = "	UPDATE asn_sanomat SET
								tilausrivi = '{$tilausrivirow['tunnus']}',
								tuoteno = '{$tilausrivirow['tuoteno']}'
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tunnus = '{$row['tunnus']}'";
					$upd_res = pupe_query($query);
					$kaytetyt_tilausrivit[] = $tilausrivirow['tunnus'];
					
					// Jos lˆytyy lapsi (runko) niin lis‰t‰‰n kappalem‰‰r‰n mukaisesti lapsia tilaukselle.
					if ($loysin_lapsen === TRUE) {
						// Lis‰t‰‰n puuttuvat rungot
						// Tehd‰‰n uusi rivi, jossa on puuttuvat 'Rungot'
						$lisainsert = "	INSERT INTO tilausrivi SET
									yhtio			= '$tilausrivirow[yhtio]',
									tyyppi			= '$tilausrivirow[tyyppi]',
									toimaika		= '$tilausrivirow[toimaika]',
									kerayspvm		= '$tilausrivirow[kerayspvm]',
									otunnus			= '$tilausrivirow[otunnus]',
									tuoteno			= '$lisatavarat[tuoteno]',
									try				= '$lisatavarat[try]',
									osasto			= '$lisatavarat[osasto]',
									nimitys			= '$lisatavarat[nimitys]',
									yksikko			= '$lisatavarat[yksikko]',
									tilkpl			= '$row[kappalemaara]',
									varattu			= '$row[kappalemaara]',
									hinta			= '$lisatavarat[myyntihinta]',
									laatija			= 'lapset',
									kommentti		= 'ASN-sanomalla: {$row["asn_numero"]} tuotteelle: {$tilausrivirow["tuoteno"]} lis‰t‰‰n lapsituote: {$lisatavarat["tuoteno"]}',
									laadittu		=  now(),
									hyllyalue		= '$lisatavarat[hyllyalue]',
									hyllynro		= '$lisatavarat[hyllynro]',
									hyllytaso		= '$lisatavarat[hyllytaso]',
									hyllyvali		= '$lisatavarat[hyllyvali]'";
						$inskres			= pupe_query($lisainsert);
						$paketin_rivit[]	= mysql_insert_id();
					}
				}
				elseif ($row["kappalemaara"] > $tilausrivirow["varattu"] and $tilausrivirow["varattu"] > 0) {
					
					$laskuri = 0;
					$kohdistamatta = $row["kappalemaara"];
					$lisa_array = array();
					$split_array = array();
					// Lasketaan ensin kaikki rivit yhteen ja sen j‰lkeen katsotaan tarvitaanko lis‰rivi‰
					$olen_apu_result = pupe_query($query);
					while ($roll = mysql_fetch_assoc($olen_apu_result)) {
						if ($kohdistamatta <= 0) continue; //test
						$laskuri += $roll["varattu"];
						$lisa_array[] = $roll["tunnus"];
						$split_array[] = $roll["tunnus"]; // test
						$kohdistamatta -= $roll["varattu"]; // test
					}
					
					$ylimaaraista = $row["kappalemaara"] - $laskuri; // liikaa miinus tilauksella
				//	echo "Debug, ylim‰‰r‰ist‰: $ylimaaraista kpl: {$row["kappalemaara"]} laskuri: $laskuri kohdistamatta: $kohdistamatta <br>";
							
					if ($ylimaaraista > 0) {
						// Tehd‰‰n uusi rivi, jossa on ylim‰‰r‰iset kappaleet
						$lisaaquery = "	INSERT INTO tilausrivi SET
									yhtio			= '$tilausrivirow[yhtio]',
									tyyppi			= '$tilausrivirow[tyyppi]',
									toimaika		= '$tilausrivirow[toimaika]',
									kerayspvm		= '$tilausrivirow[kerayspvm]',
									otunnus			= '$tilausrivirow[otunnus]',
									tuoteno			= '$tilausrivirow[tuoteno]',
									try				= '$tilausrivirow[try]',
									osasto			= '$tilausrivirow[osasto]',
									nimitys			= '$tilausrivirow[nimitys]',
									yksikko			= '$tilausrivirow[yksikko]',
									tilkpl			= '$ylimaaraista',
									varattu			= '$ylimaaraista',
									hinta			= '$tilausrivirow[hinta]',
									laatija			= 'extraa',
									kommentti		= 'ASN-sanomalla: {$row["asn_numero"]} tuli {$row["kappalemaara"]} ja tilaukselta lˆytyi {$laskuri} Tehtiin tilausrivi {$ylimaaraista} m‰‰r‰lle',
									laadittu		=  now(),
									hyllyalue		= '$tilausrivirow[hyllyalue]',
									hyllynro		= '$tilausrivirow[hyllynro]',
									hyllytaso		= '$tilausrivirow[hyllytaso]',
									hyllyvali		= '$tilausrivirow[hyllyvali]',
									tilaajanrivinro = '$tilausrivirow[tilaajanrivinro]'";
						$inskres = pupe_query($lisaaquery);
						$extraa = mysql_insert_id();
					
						$kaytetyt_tilausrivit	= array_unique(array_merge($kaytetyt_tilausrivit, $lisa_array));
						$paketin_rivit			= array_unique(array_merge($paketin_rivit, $lisa_array));
						$siistitty 				= implode(",", $lisa_array);
					
						// p‰ivitet‰‰n tilausrivin tunnus talteen asn_sanomat-tauluun, koska t‰m‰ kohdistuu kyseiseen riviin
						$updatequery = "UPDATE asn_sanomat SET
										tilausrivi = '{$siistitty},{$extraa}',
										tuoteno = '{$tilausrivirow['tuoteno']}'
										WHERE yhtio = '{$kukarow['yhtio']}'
										AND tunnus = '{$row['tunnus']}'";
						$upd_res = pupe_query($updatequery);					
						$kaytetyt_tilausrivit[] = $extraa;
						$paketin_rivit[]		= $extraa;
						
						// Jos lˆytyy lapsi (runko) niin lis‰t‰‰n kappalem‰‰r‰n mukaisesti lapsia tilaukselle.
						if ($loysin_lapsen === TRUE) {
							// Lis‰t‰‰n puuttuvat rungot
							// Tehd‰‰n uusi rivi, jossa on puuttuvat 'Rungot'
							$lisainsert = "	INSERT INTO tilausrivi SET
										yhtio			= '$tilausrivirow[yhtio]',
										tyyppi			= '$tilausrivirow[tyyppi]',
										toimaika		= '$tilausrivirow[toimaika]',
										kerayspvm		= '$tilausrivirow[kerayspvm]',
										otunnus			= '$tilausrivirow[otunnus]',
										tuoteno			= '$lisatavarat[tuoteno]',
										try				= '$lisatavarat[try]',
										osasto			= '$lisatavarat[osasto]',
										nimitys			= '$lisatavarat[nimitys]',
										yksikko			= '$lisatavarat[yksikko]',
										tilkpl			= '$row[kappalemaara]',
										varattu			= '$row[kappalemaara]',
										hinta			= '$lisatavarat[myyntihinta]',
										laatija			= 'lapset',
										kommentti		= 'ASN-sanomalla: {$row["asn_numero"]} tuotteelle: {$tilausrivirow["tuoteno"]} lis‰t‰‰n lapsituote: {$lisatavarat["tuoteno"]}',
										laadittu		=  now(),
										hyllyalue		= '$lisatavarat[hyllyalue]',
										hyllynro		= '$lisatavarat[hyllynro]',
										hyllytaso		= '$lisatavarat[hyllytaso]',
										hyllyvali		= '$lisatavarat[hyllyvali]'";
							$inskres			= pupe_query($lisainsert);
							$paketin_rivit[]	= mysql_insert_id();
						}
					}
					elseif ($ylimaaraista == 0) {
						// Jos menee tasan tilatut ja tulleet.
						$kaytetyt_tilausrivit	= array_unique(array_merge($kaytetyt_tilausrivit, $lisa_array));
						$paketin_rivit			= array_unique(array_merge($paketin_rivit, $lisa_array));
						$siistitty 				= implode(",", $lisa_array);
					
						// p‰ivitet‰‰n tilausrivin tunnus talteen asn_sanomat-tauluun, koska t‰m‰ kohdistuu kyseiseen riviin
						$updatequery = "UPDATE asn_sanomat SET
										tilausrivi = '{$siistitty}',
										tuoteno = '{$tilausrivirow['tuoteno']}'
										WHERE yhtio = '{$kukarow['yhtio']}'
										AND tunnus = '{$row['tunnus']}'";
						$upd_res = pupe_query($updatequery);
						
						// Jos lˆytyy lapsi (runko) niin lis‰t‰‰n kappalem‰‰r‰n mukaisesti lapsia tilaukselle.
						if ($loysin_lapsen === TRUE) {
							// Lis‰t‰‰n puuttuvat rungot
							// Tehd‰‰n uusi rivi, jossa on puuttuvat 'Rungot'
							$lisainsert = "	INSERT INTO tilausrivi SET
										yhtio			= '$tilausrivirow[yhtio]',
										tyyppi			= '$tilausrivirow[tyyppi]',
										toimaika		= '$tilausrivirow[toimaika]',
										kerayspvm		= '$tilausrivirow[kerayspvm]',
										otunnus			= '$tilausrivirow[otunnus]',
										tuoteno			= '$lisatavarat[tuoteno]',
										try				= '$lisatavarat[try]',
										osasto			= '$lisatavarat[osasto]',
										nimitys			= '$lisatavarat[nimitys]',
										yksikko			= '$lisatavarat[yksikko]',
										tilkpl			= '$row[kappalemaara]',
										varattu			= '$row[kappalemaara]',
										hinta			= '$lisatavarat[myyntihinta]',
										laatija			= 'lapset',
										kommentti		= 'ASN-sanomalla: {$row["asn_numero"]} tuotteelle: {$tilausrivirow["tuoteno"]} lis‰t‰‰n lapsituote: {$lisatavarat["tuoteno"]}',
										laadittu		=  now(),
										hyllyalue		= '$lisatavarat[hyllyalue]',
										hyllynro		= '$lisatavarat[hyllynro]',
										hyllytaso		= '$lisatavarat[hyllytaso]',
										hyllyvali		= '$lisatavarat[hyllyvali]'";
							$inskres			= pupe_query($lisainsert);
							$paketin_rivit[]	= mysql_insert_id();
						}
					}
					elseif ($ylimaaraista < 0) {
						$positiivinen_arvo = abs($ylimaaraista);
						$vika_rivi = array_pop($split_array);
						// vika_rivi on se tilausrivi joka splitataan ett‰ m‰‰r‰ t‰sm‰‰
						
						$split_query	= "SELECT * from tilausrivi where yhtio = '{$kukarow["yhtio"]}' and tunnus = '$vika_rivi'";
						$split_result	= pupe_query($split_query);
						$split_row		= mysql_fetch_assoc($split_result);
						$erotus			= $split_row["tilkpl"] - $positiivinen_arvo;
						
						// P‰ivitet‰‰n alkuper‰iselle riville "ylim‰‰r‰inen" ja uudelle splitille "erotus"
						$query = "	UPDATE tilausrivi SET
									varattu = '$positiivinen_arvo',
									tilkpl	= '$positiivinen_arvo', 
									kommentti = concat(kommentti,' ennen splittausta {$split_row["tilkpl"]} : {$split_row["varattu"]}')
									WHERE yhtio = '$kukarow[yhtio]'
									AND tunnus = '$split_row[tunnus]'";
						$upres = pupe_query($query);
						
						$kaytetyt_tilausrivit	= array_unique(array_merge($kaytetyt_tilausrivit, $lisa_array));
						$paketin_rivit			= array_unique(array_merge($paketin_rivit, $lisa_array));
						$siistitty 				= implode(",", $lisa_array);
						
						// p‰ivitet‰‰n tilausrivin tunnus talteen asn_sanomat-tauluun, koska t‰m‰ kohdistuu kyseiseen riviin
						$updatequery = "UPDATE asn_sanomat SET
										tilausrivi = '{$siistitty}',
										tuoteno = '{$tilausrivirow['tuoteno']}'
										WHERE yhtio = '{$kukarow['yhtio']}'
										AND tunnus = '{$row['tunnus']}'";
						$upd_res = pupe_query($updatequery);
						
						// Tehd‰‰n uusi rivi, jossa on j‰ljelle j‰‰neet kappaleet
						$query = "	INSERT INTO tilausrivi SET
									yhtio			= '$split_row[yhtio]',
									tyyppi			= '$split_row[tyyppi]',
									toimaika		= '$split_row[toimaika]',
									kerayspvm		= '$split_row[kerayspvm]',
									otunnus			= '$split_row[otunnus]',
									tuoteno			= '$split_row[tuoteno]',
									try				= '$split_row[try]',
									osasto			= '$split_row[osasto]',
									nimitys			= '$split_row[nimitys]',
									yksikko			= '$split_row[yksikko]',
									tilkpl			= '$erotus',
									varattu			= '$erotus',
									hinta			= '$split_row[hinta]',
									laatija			= 'split',
									kommentti		= 'ASN-sanomalla: {$row["asn_numero"]}. Tehtiin puuttuva tilausrivi m‰‰r‰lle {$erotus} tuotteelle {$split_row["tuoteno"]}',
									laadittu		=  now(),
									hyllyalue		= '$split_row[hyllyalue]',
									hyllynro		= '$split_row[hyllynro]',
									hyllytaso		= '$split_row[hyllytaso]',
									hyllyvali		= '$split_row[hyllyvali]',
									tilaajanrivinro = '$split_row[tilaajanrivinro]'";
						$inskres = pupe_query($query);

						// Jos lˆytyy lapsi (runko) niin lis‰t‰‰n lapsia tilaukselle.
						if ($loysin_lapsen === TRUE) {
							// Tehd‰‰n uusi rivi, jossa on puuttuvat 'Rungot'
							$lisainsert = "	INSERT INTO tilausrivi SET
										yhtio			= '$tilausrivirow[yhtio]',
										tyyppi			= '$tilausrivirow[tyyppi]',
										toimaika		= '$tilausrivirow[toimaika]',
										kerayspvm		= '$tilausrivirow[kerayspvm]',
										otunnus			= '$tilausrivirow[otunnus]',
										tuoteno			= '$lisatavarat[tuoteno]',
										try				= '$lisatavarat[try]',
										osasto			= '$lisatavarat[osasto]',
										nimitys			= '$lisatavarat[nimitys]',
										yksikko			= '$lisatavarat[yksikko]',
										tilkpl			= '$row[kappalemaara]',
										varattu			= '$row[kappalemaara]',
										hinta			= '$lisatavarat[myyntihinta]',
										laatija			= 'lapset',
										kommentti		= 'ASN-sanomalla: {$row["asn_numero"]} tuotteelle: {$tilausrivirow["tuoteno"]} lis‰t‰‰n lapsituote: {$lisatavarat["tuoteno"]}',
										laadittu		=  now(),
										hyllyalue		= '$lisatavarat[hyllyalue]',
										hyllynro		= '$lisatavarat[hyllynro]',
										hyllytaso		= '$lisatavarat[hyllytaso]',
										hyllyvali		= '$lisatavarat[hyllyvali]'";
							$inskres			= pupe_query($lisainsert);
							$paketin_rivit[]	= mysql_insert_id();
						}
						
					}
				}
				elseif ($row["kappalemaara"] < $tilausrivirow["varattu"]) {
					// Splitataan tilausrivi, tallennetaan vanha rivitunnus tilausrivi.tilaajanrivinro kentt‰‰n
					$kappaleerotus = $tilausrivirow["varattu"] - $row["kappalemaara"];

					// P‰ivitet‰‰n alkuper‰iselle riville saapunut kappalem‰‰r‰
					$query = "	UPDATE tilausrivi SET
								varattu = '$row[kappalemaara]',
								tilkpl	= '$row[kappalemaara]', 
								kommentti = concat(kommentti,' ennen splittausta {$tilausrivirow["tilkpl"]} : {$tilausrivirow["varattu"]}')
								WHERE yhtio = '$kukarow[yhtio]'
								AND tunnus = '$tilausrivirow[tunnus]'";
					$upres = pupe_query($query);

					// p‰ivitet‰‰n tilausrivin tunnus talteen asn_sanomat-tauluun, koska t‰m‰ kohdistuu kyseiseen riviin
					$query = "	UPDATE asn_sanomat SET
								tilausrivi = '{$tilausrivirow['tunnus']}',
								tuoteno = '{$tilausrivirow['tuoteno']}'
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tunnus = '{$row['tunnus']}'";
					$upd_res = pupe_query($query);

					$kaytetyt_tilausrivit[] = $tilausrivirow['tunnus'];

					// Tehd‰‰n uusi rivi, jossa on j‰ljelle j‰‰neet kappaleet
					$query = "	INSERT INTO tilausrivi SET
								yhtio			= '$tilausrivirow[yhtio]',
								tyyppi			= '$tilausrivirow[tyyppi]',
								toimaika		= '$tilausrivirow[toimaika]',
								kerayspvm		= '$tilausrivirow[kerayspvm]',
								otunnus			= '$tilausrivirow[otunnus]',
								tuoteno			= '$tilausrivirow[tuoteno]',
								try				= '$tilausrivirow[try]',
								osasto			= '$tilausrivirow[osasto]',
								nimitys			= '$tilausrivirow[nimitys]',
								yksikko			= '$tilausrivirow[yksikko]',
								tilkpl			= '$row[kappalemaara]',
								varattu			= '$row[kappalemaara]',
								hinta			= '$tilausrivirow[hinta]',
								laatija			= 'split',
								kommentti		= 'ASN-sanomalla: {$row["asn_numero"]} tuli {$row["kappalemaara"]} ja tilaukselta lˆytyi {$tilausrivirow["tilkpl"]} Tehtiin tilausrivi {$kappaleerotus} m‰‰r‰lle',
								laadittu		=  now(),
								hyllyalue		= '$tilausrivirow[hyllyalue]',
								hyllynro		= '$tilausrivirow[hyllynro]',
								hyllytaso		= '$tilausrivirow[hyllytaso]',
								hyllyvali		= '$tilausrivirow[hyllyvali]',
								tilaajanrivinro = '$tilausrivirow[tilaajanrivinro]'";
					$inskres = pupe_query($query);
					
					// Jos lˆytyy lapsi (runko) niin lis‰t‰‰n lapsia tilaukselle.
					if ($loysin_lapsen === TRUE) {
						// Tehd‰‰n uusi rivi, jossa on puuttuvat 'Rungot'
						$lisainsert = "	INSERT INTO tilausrivi SET
									yhtio			= '$tilausrivirow[yhtio]',
									tyyppi			= '$tilausrivirow[tyyppi]',
									toimaika		= '$tilausrivirow[toimaika]',
									kerayspvm		= '$tilausrivirow[kerayspvm]',
									otunnus			= '$tilausrivirow[otunnus]',
									tuoteno			= '$lisatavarat[tuoteno]',
									try				= '$lisatavarat[try]',
									osasto			= '$lisatavarat[osasto]',
									nimitys			= '$lisatavarat[nimitys]',
									yksikko			= '$lisatavarat[yksikko]',
									tilkpl			= '$row[kappalemaara]',
									varattu			= '$row[kappalemaara]',
									hinta			= '$lisatavarat[myyntihinta]',
									laatija			= 'lapset',
									kommentti		= 'ASN-sanomalla: {$row["asn_numero"]} tuotteelle: {$tilausrivirow["tuoteno"]} lis‰t‰‰n lapsituote: {$lisatavarat["tuoteno"]}',
									laadittu		=  now(),
									hyllyalue		= '$lisatavarat[hyllyalue]',
									hyllynro		= '$lisatavarat[hyllynro]',
									hyllytaso		= '$lisatavarat[hyllytaso]',
									hyllyvali		= '$lisatavarat[hyllyvali]'";
						$inskres			= pupe_query($lisainsert);
						$paketin_rivit[]	= mysql_insert_id();
					}
				}
				else {
					echo "Kaasuu lis‰‰, puut senkun isonee<br>";
				}

				// Otetaan talteen t‰m‰n kierroksen tiedot
				$edellinen_toimittaja = $row["toimittajanumero"];
				$edellinen_asn_numero = $row["asn_numero"];
				$edellinen_paketinnumero = $row["paketinnumero"];
				$edellinen_paketintunniste = $row["paketintunniste"];
				$paketin_tunnukset[] = $row["tunnus"];
				$paketin_rivit[] = $tilausrivirow["tunnus"];
				$paketin_tuotteet[] = $tilausrivirow["tuoteno"];
			}

			if ($virheet == 0 and count($paketin_rivit) > 0) {
				asn_kohdista_suuntalava($edellinen_toimittaja, $edellinen_asn_numero, $paketin_rivit, $paketin_tuotteet, $paketin_tunnukset, $edellinen_paketintunniste,'');
			}
			elseif ($virheet != 0) {
				echo "VIRHE: Paketti hylatty. Toimittaja $edellinen_toimittaja ASN-sanoma $edellinen_asn_numero $virhe Paketti $edellinen_paketinnumero $d";
			}

		}

	}

	if (!function_exists("asn_kohdista_suuntalava")) {

		function asn_kohdista_suuntalava($edellinen_toimittaja, $edellinen_asn_numero, $paketin_rivit, $paketin_tuotteet, $paketin_tunnukset, $edellinen_paketintunniste,$SSCC='') {

			global $yhtiorow, $kukarow;

			// Haetaan toimittajan tiedot
			$toimhaku = "	SELECT *
							FROM toimi
							WHERE yhtio = '$kukarow[yhtio]'
							AND toimittajanro = '$edellinen_toimittaja'
							AND tyyppi !='P'";
			$checkres = pupe_query($toimhaku);

			if (mysql_num_rows($checkres) != 1) {
				echo "VIRHE: Toimittajanumero: $edellinen_toimittaja ei lˆydy jarjestelmasta!\n";
			}
			else {
				// Jos kaikki paketin rivit olivat kunnossa, tehd‰‰n suuntalava ja linkataan ostotilausrivi keikkaan sek‰ suuntalavaan
				$toimittajarow = mysql_fetch_array($checkres);

				// Tehd‰‰n keikka, jos sit‰ ei viel‰ ole. ASN-numero lˆytyy comments kent‰st‰.
				$query = "	SELECT lasku.tunnus
							FROM lasku
							WHERE yhtio = '$kukarow[yhtio]'
							AND tila = 'K'
							AND vanhatunnus = 0
							AND liitostunnus = '$toimittajarow[tunnus]'
							AND comments = '$edellinen_asn_numero'";
				$checkres = pupe_query($query);

				// Tehd‰‰n keikka
				if (mysql_num_rows($checkres) == 0) {

					$query = "	SELECT kurssi
								FROM valuu
								WHERE yhtio = '$kukarow[yhtio]'
								AND nimi = '$toimittajarow[oletus_valkoodi]'";
					$checkres = pupe_query($query);
					$row = mysql_fetch_array($checkres);
					$kurssi = $row["kurssi"];

					$query  = "LOCK TABLE lasku WRITE";
					$result = pupe_query($query);

					$query = "	SELECT max(laskunro)
								FROM lasku
								WHERE yhtio = '$kukarow[yhtio]'
								AND tila = 'K'";
					$checkres = pupe_query($query);
					$row = mysql_fetch_array($checkres);
					$id = $row[0] + 1;

					$maa_lahetys = $toimittajarow['maa_lahetys'] != '' ? $toimittajarow['maa_lahetys'] : $toimittajarow['maa'];

					// meill‰ on $toimittajarow haettuna ylh‰‰ll‰
					$query = "	INSERT into lasku set
								yhtio        			= '$kukarow[yhtio]',
								laskunro     			= '$id',
								ytunnus	     			= '$toimittajarow[ytunnus]',
								nimi         			= '$toimittajarow[nimi]',
								valkoodi     			= '$toimittajarow[oletus_valkoodi]',
								vienti       			= '$toimittajarow[oletus_vienti]',
								vienti_kurssi			= '$kurssi',
								toimitusehto 			= '$toimittajarow[toimitusehto]',
								osoite       			= '$toimittajarow[osoite]',
								postitp      			= '$toimittajarow[postitp]',
								maa			 			= '$toimittajarow[maa]',
								maa_lahetys 			= '$maa_lahetys',
								comments				= '$edellinen_asn_numero',
								kauppatapahtuman_luonne	= '$toimittajarow[kauppatapahtuman_luonne]',
								kuljetusmuoto			= '$toimittajarow[kuljetusmuoto]',
								rahti					= '$toimittajarow[oletus_kulupros]',
								swift					= '$toimittajarow[swift]',
								liitostunnus 			= '$toimittajarow[tunnus]',
								tila         			= 'K',
								luontiaika	 			= now(),
								laatija		 			= '$kukarow[kuka]'";
					$insertres = pupe_query($query);
					$keikan_tunnus = mysql_insert_id();

					$query  = "UNLOCK TABLE";
					$result = pupe_query($query);
				}
				else {
					$keikkarow = mysql_fetch_array($checkres);
					$keikan_tunnus = $keikkarow["tunnus"];
				}
				$query = "	SELECT th.keraysvyohyke, keraysvyohyke.terminaalialue, count(*)
							FROM tuotepaikat as tp
							JOIN varaston_hyllypaikat as th on (th.yhtio = tp.yhtio and th.hyllyalue=tp.hyllyalue and th.hyllynro=tp.hyllynro and th.hyllyvali=tp.hyllyvali and th.hyllytaso=tp.hyllyvali)
							JOIN keraysvyohyke on (keraysvyohyke.yhtio = tp.yhtio and keraysvyohyke.tunnus = th.keraysvyohyke)
							WHERE tp.yhtio = '$kukarow[yhtio]'
							AND tp.tuoteno IN ('".implode("','", $paketin_tuotteet)."')
							AND tp.oletus = 'x'
							GROUP BY 1
							ORDER BY 3 DESC
							LIMIT 1";
/*
$VANHA_TALTEEN_query = "	SELECT tuote.keraysvyohyke, keraysvyohyke.terminaalialue, count(*)
							FROM tuote
							JOIN keraysvyohyke on (keraysvyohyke.yhtio = tuote.yhtio and keraysvyohyke.tunnus = tuote.keraysvyohyke)
							WHERE tuote.yhtio = '$kukarow[yhtio]'
							AND tuote.tuoteno IN ('".implode("','", $paketin_tuotteet)."')
							GROUP BY 1
							ORDER BY 3 DESC
							LIMIT 1";
*/
				$checkres = pupe_query($query);
				$row = mysql_fetch_array($checkres);

				// Tehd‰‰n uusi suuntalava
				$tee = "lisaa";
				$otunnus = $keikan_tunnus;
				$sscc = $edellinen_paketintunniste;
				$tyyppi = 10;
				$keraysvyohyke = $row['keraysvyohyke'];
				$kaytettavyys = "Y";
				$terminaalialue = $row['terminaalialue'];
				$korkeus = 0;
				$paino = 0;
				$alkuhyllyalue = "";
				$alkuhyllynro = "";
				$alkuhyllyvali = "";
				$alkuhyllytaso = "";
				$loppuhyllyalue = "";
				$loppuhyllynro = "";
				$loppuhyllyvali = "";
				$loppuhyllytaso = "";
				$automaattinen_paivitys = "";
				$suuntalavat_ei_kayttoliittymaa = "KYLLA";

				require ("tilauskasittely/suuntalavat.inc");
				// Saadaan $uusi_suuntalavan_tunnus ^ incist‰.
				// P‰ivitet‰‰n paketin ostotilausrivit kohdistetuksi keikkan ja oikealle suuntalavalle
				$query = "	UPDATE tilausrivi SET
							uusiotunnus = '$keikan_tunnus',
							suuntalava = '$uusi_suuntalavan_tunnus'
							WHERE yhtio = '$kukarow[yhtio]'
							AND tilausrivi.tunnus IN (".implode(",", $paketin_rivit).")";
				$updateres = pupe_query($query);


				$query = "	UPDATE asn_sanomat SET
							status = 'X'
							WHERE yhtio = '$kukarow[yhtio]'
							AND tunnus IN (".implode(",", $paketin_tunnukset).")";
				$updateres = pupe_query($query);

				
				
				// Laitetaan suuntalava siirtovalmiiksi.
				$suuntalavan_tunnus = $uusi_suuntalavan_tunnus;
				$tee = "siirtovalmis";
				$suuntalavat_ei_kayttoliittymaa = "KYLLA";
				require ("tilauskasittely/suuntalavat.inc");

			}

		}
	}

?>