<?php

	// jos halutaan generoida ostotilaus tämän tilauksen riveistä
	// tarvitaan myyntitilauksen tunnus muuttujassa $otunnus
	
	if (!function_exists("tilauksesta_ostotilaus")) {
		function tilauksesta_ostotilaus($otunnus) {
			global $yhtiorow, $kukarow;
			
			$tilauksesta_ostotilaus = "";
						
			$query = "select * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$otunnus'";
			$result = mysql_query($query) or pupe_error($query);
			$myytilrow = mysql_fetch_array($result);
		
			$query = "	select * 
						from tilausrivi 
						where yhtio = '$kukarow[yhtio]' 
						and otunnus='$otunnus'
						and tyyppi != 'D'
						and var = 'T'
						and (perheid = tunnus or perheid = 0)
						order by tunnus";
			$result = mysql_query($query) or pupe_error($query);
				
			if (mysql_num_rows($result) > 0) {
		
				while ($rivi = mysql_fetch_array($result)) {
		
					// tehdään aluksi vähän oikeellisuustarkastuksia
					$query = "select * from tuotteen_toimittajat where yhtio='$kukarow[yhtio]' and tuoteno='$rivi[tuoteno]' and liitostunnus='$rivi[tilaajanrivinro]'";
					$erres = mysql_query($query) or pupe_error($query);
					$ttrow = mysql_fetch_array($erres);
					
					// tehdään aluksi vähän oikeellisuustarkastuksia
					$query = "select * from toimi where yhtio='$kukarow[yhtio]' and tunnus='$ttrow[liitostunnus]'";
					$erres = mysql_query($query) or pupe_error($query);
		
					if (mysql_num_rows($erres) != 1) {
						$tilauksesta_ostotilaus .= "<font class='error'>Toimittajan tietoja ei löytynyt!<br>";
					}
					else {
						// toimittaja löytyi, tehdään tästä ostotilaus
						$tiltoi = mysql_fetch_array($erres);
		
						// katsotaan onko toimittajalla avoimia ostotilauksia
						$query = "	select tunnus 
									from lasku 
									where yhtio = '$kukarow[yhtio]' 
									and liitostunnus = '$tiltoi[tunnus]' 
									and tila = 'O' 
									and alatila = '' 
									and left(luontiaika,10) = left(now(),10) 
									and laatija = '$kukarow[kuka]'";
						$jtsre = mysql_query($query) or pupe_error($query);
		
						// ei löydy, tehdään uus otsikko
						if (mysql_num_rows($jtsre) == 0) {
		
							$query = "	INSERT INTO lasku SET
										ytunnus				= '$tiltoi[ytunnus]',
										ovttunnus 			= '$tiltoi[ovttunnus]',
										nimi 				= '$tiltoi[nimi]',
										nimitark 			= '$tiltoi[nimitark]',
										osoite 				= '$tiltoi[osoite]',
										postino 			= '$tiltoi[postino]',
										postitp 			= '$tiltoi[postitp]',
										maa 				= '$tiltoi[maa]',
										toim_ovttunnus		= '$tiltoi[ovttunnus]',
										toim_nimi 			= '$myytilrow[toim_nimi]',
										toim_nimitark 		= '$myytilrow[toim_nimitark]',
										toim_osoite 		= '$myytilrow[toim_osoite]',
										toim_postino 		= '$myytilrow[toim_postino]',
										toim_postitp 		= '$myytilrow[toim_postitp]',
										toim_maa 			= '$myytilrow[toim_maa]',
										verkkotunnus		= '$myytilrow[liitostunnus]',
										toimaika 			= now(),
										kerayspvm 			= now(),
										luontiaika			= now(),
										ketjutus			= '1',
										maksuehto 			= '$tiltoi[maksuehto]',
										toimitustapa 		= '$myytilrow[toimitustapa]',
										toimitusehto 		= '$tiltoi[toimitusehto]',
										laatija 			= '$kukarow[kuka]',
										alv 				= '$tiltoi[alv]',
										comments 			= '$tiltoi[comments]',
										viesti 				= '$tiltoi[viesti]',
										kuljetus 			= '$tiltoi[kuljetus]',
										huolitsija 			= '$tiltoi[huolitsija]',
										maksuteksti 		= '$tiltoi[maksuteksti]',
										yhtio 				= '$kukarow[yhtio]',
										tilaustyyppi		= '$tiltoi[tilaustyyppi]',
										liitostunnus		= '$tiltoi[tunnus]',
										valkoodi 			= '$tiltoi[valkoodi]',
										viikorkopros 		= '$yhtiorow[viivastyskorko]',
										tila 				= 'O'";
							$updre = mysql_query($query) or pupe_error($query);
							$tunnus = (string) mysql_insert_id();
						}
						else {
							// tilaus löyty, otetaan tunnus
							$jtsro = mysql_fetch_array($jtsre);
							$tunnus = $jtsro["tunnus"];
						}
												
						$tilauksesta_ostotilaus .= "<font class='message'>Lisätään valitut rivit ostotilaukselle: $tunnus</font><br>";
						
						//Haetaan myös ns. lisävarusteperheet.
						$query = "	select *, jt+varattu kpl
									from tilausrivi 
									where yhtio = '$kukarow[yhtio]' 
									and otunnus = '$otunnus' 
									and tyyppi != 'D'
									and (tunnus='$rivi[tunnus]' or perheid='$rivi[tunnus]')
									order by tunnus";
						$tilrivires = mysql_query($query) or pupe_error($query);
						
						$pid = 0;
						
						while ($tilrivirow = mysql_fetch_array($tilrivires)) {
							// haetaan oletuspaikan tiedot niin laitetaan se riville
							$query = "select * from tuotepaikat where yhtio='$kukarow[yhtio]' and tuoteno='$tilrivirow[tuoteno]' and oletus!=''";
							$jtsre = mysql_query($query) or pupe_error($query);
							$jtstu = mysql_fetch_array($jtsre);
			
							//haetaan tuotteen ostohinta
							$query = "select ostohinta from tuotteen_toimittajat where yhtio='$kukarow[yhtio]' and tuoteno='$tilrivirow[tuoteno]' and liitostunnus='$ttrow[liitostunnus]'";
							$ossre = mysql_query($query) or pupe_error($query);
							$osstu = mysql_fetch_array($ossre);
														
							// lisätään ostotilausrivi
							$query = "	insert into tilausrivi
										(perheid, hinta, nimitys, tuoteno, tilkpl, varattu, otunnus, yhtio, tyyppi, hyllyalue, hyllynro, hyllyvali, hyllytaso) values
										('$pid','$osstu[ostohinta]','$tilrivirow[nimitys]','$tilrivirow[tuoteno]', '$tilrivirow[kpl]', '$tilrivirow[jt]', '$tunnus', '$kukarow[yhtio]', 'O','$jtstu[hyllyalue]','$jtstu[hyllynro]','$jtstu[hyllyvali]','$jtstu[hyllytaso]')";
							$updre = mysql_query($query) or pupe_error($query);
												
							$tilauksesta_ostotilaus .= "<font class='message'>Tuote: $tilrivirow[tuoteno] $tilrivirow[kpl] kpl lisätty ostotilaukselle: $tunnus</font><br>";
							
							if($tilrivirow["tunnus"] == $tilrivirow["perheid"] and mysql_num_rows($tilrivires) > 1) {
								$pid = mysql_insert_id();
								
								$query = "	update tilausrivi 
											set perheid = $pid
											where yhtio = '$kukarow[yhtio]' and tunnus=$pid";
								$perheres = mysql_query($query) or pupe_error($query);						
							}
						}										
					}
					// tehdään rivistä tavallinen JT
					$query = "update tilausrivi set var='J' where yhtio='$kukarow[yhtio]' and tunnus='$rivi[tunnus]'";
					$updre = mysql_query($query) or pupe_error($query);						
				}
			}
			return $tilauksesta_ostotilaus;
		}
	}
?>