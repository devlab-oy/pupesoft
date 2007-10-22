<?php

	// jos halutaan generoida ostotilaus tämän tilauksen riveistä
	// tarvitaan myyntitilauksen tunnus muuttujassa $otunnus

	if (!function_exists("tilauksesta_ostotilaus")) {
		function tilauksesta_ostotilaus($otunnus, $tyyppi) {
			global $yhtiorow, $kukarow;

			$tilauksesta_ostotilaus = "";

			if($tyyppi == "T") {
				$vars = " and tilausrivi.var  = 'T' ";
			}
			elseif($tyyppi == "U") {
				$vars = " and tilausrivi.var  = 'U' ";
			}
			elseif($tyyppi == "KAIKKI") {
				$vars = " and tilausrivi.var != 'P' ";
			}
			else {
				echo "VIRHE: Funktiota kutsuttiin väärällä parametrilla!<br><br>";
				return false;				
			}

			$query = "	SELECT * 
						FROM lasku 
						WHERE yhtio	= '$kukarow[yhtio]' 
						and tunnus	= '$otunnus'";
			$result = mysql_query($query) or pupe_error($query);
			$myytilrow = mysql_fetch_array($result);

			//otetaan ensin vain lisävarusteettomat tuotteet tai lisävarusteperheiden isät
			$query = "	SELECT tilausrivin_lisatiedot.*, tilausrivi.*, tilausrivi.tunnus rivitunnus
						FROM tilausrivi
						LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus=tilausrivi.tunnus)
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and tilausrivi.otunnus	='$otunnus'
						and tilausrivi.tyyppi != 'D'
						$vars
						and (tilausrivi.perheid2 = tilausrivi.tunnus or tilausrivi.perheid2 = 0)
						order by tilausrivi.perheid2, tilausrivi.tunnus";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) > 0) {

				while ($rivi = mysql_fetch_array($result)) {
					
					//	Jos tehdään vaan tämä tilaus niin haetaan tämän oletustoimittaja, by default
					if($tyyppi == "KAIKKI" and $rivi["toimittajan_tunnus"] == 0) {
						
						$query = "SELECT liitostunnus from tuotteen_toimittajat where yhtio='$kukarow[yhtio]' and tuoteno='$rivi[tuoteno]' limit 1";
						$abures = mysql_query($query) or pupe_error($query);
						
						if(mysql_num_rows($abures) == 1) {
							$aburow = mysql_fetch_array($abures);

							$query = "	UPDATE tilausrivin_lisatiedot
										SET 
										toimittajan_tunnus	= '$aburow[liitostunnus]',
										muutospvm			= now(),
										muuttaja			= '$kukarow[kuka]'
										WHERE yhtio		 	 = '$kukarow[yhtio]'
										and tilausrivitunnus = '$rivi[rivitunnus]'";
							$updres = mysql_query($query) or pupe_error($query);

							$rivi["toimittajan_tunnus"] = $aburow["liitostunnus"];							
						}
						else {
							$tilauksesta_ostotilaus .= "<font class='error'>Koitettiin päivittää riville toimittaja, mutta toimittajaa ei löydy! '$rivi[tunnus]'<br>";
						}
					}
					
					// tehdään aluksi vähän oikeellisuustarkastuksia
					$query = "SELECT * from tuotteen_toimittajat where yhtio='$kukarow[yhtio]' and tuoteno='$rivi[tuoteno]' and liitostunnus='$rivi[toimittajan_tunnus]'";
					$erres = mysql_query($query) or pupe_error($query);
					$ttrow = mysql_fetch_array($erres);

					// tehdään aluksi vähän oikeellisuustarkastuksia
					$query = "SELECT * from toimi where yhtio='$kukarow[yhtio]' and tunnus='$ttrow[liitostunnus]'";
					$erres = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($erres) != 1) {
						$tilauksesta_ostotilaus .= "<font class='error'>Toimittajan tietoja ei löytynyt!<br>";
					}
					else {
						// toimittaja löytyi, tehdään tästä ostotilaus
						$tiltoi = mysql_fetch_array($erres);

						// Katsotaan onko toimittajalla avoimia ostotilauksia
						// Pitää olla myös oikeat osoitetiedot
						if ($tyyppi == "U") {
							$toim_nimi 			= $myytilrow["toim_nimi"];
							$toim_nimitark 		= $myytilrow["toim_nimitark"];
							$toim_osoite 		= $myytilrow["toim_osoite"];
							$toim_postino 		= $myytilrow["toim_postino"];
							$toim_postitp 		= $myytilrow["toim_postitp"];
							$toim_maa 			= $myytilrow["toim_maa"];
						}
						else {
							$toim_nimi 			= $yhtiorow["nimi"];
							$toim_nimitark 		= $yhtiorow["nimitark"];
							$toim_osoite 		= $yhtiorow["osoite"];
							$toim_postino 		= $yhtiorow["postino"];
							$toim_postitp 		= $yhtiorow["postitp"];
							$toim_maa 			= $yhtiorow["maa"];
						}

						$query = "	SELECT tunnus
									from lasku
									where yhtio 		= '$kukarow[yhtio]'
									and liitostunnus 	= '$tiltoi[tunnus]'
									and tila 			= 'O'
									and alatila 		= ''
									and toim_nimi		= '$toim_nimi'
									and toim_nimitark	= '$toim_nimitark'
									and toim_osoite		= '$toim_osoite'
									and toim_postino	= '$toim_postino'
									and toim_postitp	= '$toim_postitp'
									and toim_maa		= '$toim_maa'
									and left(luontiaika,10) = left(now(),10)
									and laatija 		= '$kukarow[kuka]'";
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
										toim_nimi 			= '$toim_nimi',
										toim_nimitark 		= '$toim_nimitark',
										toim_osoite 		= '$toim_osoite',
										toim_postino 		= '$toim_postino',
										toim_postitp 		= '$toim_postitp',
										toim_maa 			= '$toim_maa',
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
										viesti 				= '$myytilrow[viesti]',
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

						//Haetaan myös ns. lisävarusteperheet.
						$query = "	SELECT tilausrivin_lisatiedot.*, tilausrivi.*, tilausrivi.tunnus rivitunnus, jt+varattu kpl
									FROM tilausrivi
									LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus=tilausrivi.tunnus)
									where tilausrivi.yhtio = '$kukarow[yhtio]'
									and tilausrivi.otunnus = '$otunnus'
									and tilausrivi.tyyppi != 'D'
									and (tilausrivi.tunnus='$rivi[tunnus]' or tilausrivi.perheid2='$rivi[tunnus]')
									order by tilausrivi.perheid2, tilausrivi.tunnus";
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
										(perheid2, hinta, nimitys, tuoteno, tilkpl, varattu, otunnus, yhtio, tyyppi, kommentti, toimaika, hyllyalue, hyllynro, hyllyvali, hyllytaso, laatija, laadittu) values
										('$pid','$osstu[ostohinta]','$tilrivirow[nimitys]','$tilrivirow[tuoteno]', '$tilrivirow[kpl]', '$tilrivirow[jt]', '$tunnus', '$kukarow[yhtio]', 'O','$tilrivirow[kommentti]', '$tilrivirow[toimaika]', '$jtstu[hyllyalue]','$jtstu[hyllynro]','$jtstu[hyllyvali]','$jtstu[hyllytaso]', '$kukarow[kuka]', now())";
							$updre = mysql_query($query) or pupe_error($query);
							$lisatty_tun = mysql_insert_id();
							
							// ostorivin lisätiedot							
							$query = "	INSERT INTO tilausrivin_lisatiedot
										SET tilausrivitunnus 	= '$lisatty_tun',
										luontiaika				= now(),
										laatija 				= '$kukarow[kuka]',
										yhtio		 	 		= '$kukarow[yhtio]'";
							$updres = mysql_query($query) or pupe_error($query);	
							
							// myyntirivin lisätiedot							
							$query = "	UPDATE tilausrivin_lisatiedot
										SET tilausrivilinkki 	= '$lisatty_tun', 
										toimittajan_tunnus		= '$ttrow[liitostunnus]',
										muutospvm				= now(),
										muuttaja				= '$kukarow[kuka]'
										WHERE yhtio		 	 = '$kukarow[yhtio]'
										and tilausrivitunnus = '$tilrivirow[rivitunnus]'";
							$updres = mysql_query($query) or pupe_error($query);
							
							$tilauksesta_ostotilaus .= "<font class='message'>Tuote: $tilrivirow[tuoteno] $tilrivirow[kpl] kpl lisätty ostotilaukselle: $tunnus</font><br>";

							if($tilrivirow["tunnus"] == $tilrivirow["perheid2"] and mysql_num_rows($tilrivires) > 1) {
								$pid = $lisatty_tun;

								$query = "	update tilausrivi
											set perheid2 = $pid
											where yhtio = '$kukarow[yhtio]' and tunnus=$pid";
								$perheres = mysql_query($query) or pupe_error($query);
							}
						}
					}
					
					// tehdään rivistä tavallinen JT jos meillä oli joku tilattava rivi
					if($rivi["var"]=="T" or $rivi["var"]=="U") {
						$query = "update tilausrivi set var='J' where yhtio='$kukarow[yhtio]' and tunnus='$rivi[tunnus]'";
						$updre = mysql_query($query) or pupe_error($query);						
					}
				}
			}
			return $tilauksesta_ostotilaus;
		}
	}
?>
