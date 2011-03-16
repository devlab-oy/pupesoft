<?php

	// jos halutaan generoida ostotilaus tämän tilauksen riveistä
	// tarvitaan myyntitilauksen tunnus muuttujassa $otunnus

	if (!function_exists("tilauksesta_ostotilaus")) {
		function tilauksesta_ostotilaus($otunnus, $tyyppi) {
			global $yhtiorow, $kukarow;

			$tilauksesta_ostotilaus = "";

			if ($tyyppi == "T") {
				$vars = " and tilausrivi.var  = 'T' ";
			}
			elseif ($tyyppi == "U") {
				$vars = " and tilausrivi.var  = 'U' ";
			}
			elseif ($tyyppi == "KAIKKI") {
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
			$myytilrow = mysql_fetch_assoc($result);

			//otetaan ensin vain lisävarusteettomat tuotteet tai lisävarusteperheiden isät
			$query = "	SELECT tilausrivin_lisatiedot.*, tilausrivi.*, tilausrivi.tunnus rivitunnus, tuote.ei_saldoa
						FROM tilausrivi
						LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus)
						JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno)
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and tilausrivi.otunnus = '$otunnus'
						and tilausrivi.tyyppi != 'D'
						$vars
						and (tilausrivi.perheid2 = tilausrivi.tunnus or tilausrivi.perheid2 = 0)
						ORDER BY tilausrivi.perheid2, tilausrivi.tunnus";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) > 0) {

				while ($rivi = mysql_fetch_assoc($result)) {

					// saldottomat tuotteet laitetaan jälkkäriin ja skipataan loopissa
					if ($rivi['ei_saldoa'] != '' and $rivi['perheid'] == $rivi['rivitunnus']) {
						$query = "	UPDATE tilausrivi
									SET var = 'J'
									WHERE yhtio = '$kukarow[yhtio]'
									AND tunnus = '$rivi[rivitunnus]'";
						$ei_saldoa_res = mysql_query($query) or pupe_error($query);
						continue;
					}

					//	Jos tehdään vaan tämä tilaus niin haetaan tämän oletustoimittaja, by default
					if ($tyyppi == "KAIKKI" and $rivi["toimittajan_tunnus"] == 0) {
						$query = "	SELECT liitostunnus, if (jarjestys = 0, 9999, jarjestys) sorttaus
									FROM tuotteen_toimittajat
									WHERE yhtio = '$kukarow[yhtio]'
									and tuoteno = '$rivi[tuoteno]'
									ORDER BY sorttaus
									LIMIT 1";
						$abures = mysql_query($query) or pupe_error($query);

						if (mysql_num_rows($abures) == 1) {
							$aburow = mysql_fetch_assoc($abures);

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
					$ttrow = mysql_fetch_assoc($erres);

					// tehdään aluksi vähän oikeellisuustarkastuksia
					$query = "SELECT * from toimi where yhtio='$kukarow[yhtio]' and tunnus='$ttrow[liitostunnus]'";
					$erres = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($erres) != 1) {
						$tilauksesta_ostotilaus .= "<font class='error'>Toimittajan tietoja ei löytynyt tuotteelta $rivi[tuoteno]!<br>";
					}
					else {
						// toimittaja löytyi, tehdään tästä ostotilaus
						$tiltoi = mysql_fetch_assoc($erres);

						// Katsotaan onko toimittajalla avoimia ostotilauksia
						// Pitää olla myös oikeat osoitetiedot
						if ($tyyppi == "U" or ($tyyppi == 'KAIKKI' and $yhtiorow["tee_osto_myyntitilaukselta"] == 'Q')) {
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

						$valkoodi = $tiltoi["oletus_valkoodi"];

						if ($valkoodi == '') {
							$valkoodi = $yhtiorow["valkoodi"];
						}

						$vquery = "	SELECT nimi, kurssi, tunnus
									FROM valuu
									WHERE yhtio = '$kukarow[yhtio]'
									and nimi = '$valkoodi'";
						$vresult = mysql_query($vquery) or pupe_error($vquery);
						$vrow = mysql_fetch_array($vresult);

						$commentsit = trim(t("Myyntitilaus").": $myytilrow[tunnus] \n".t("Myyntitilauksen toimitustapa").": $myytilrow[toimitustapa] \n".$tiltoi["comments"]);

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
										alatila 			= '',
										comments 			= '$commentsit',
										huolitsija 			= '$tiltoi[huolitsija]',
										jakelu 				= '$jakelu',
										kerayspvm 			= '$myytilrow[kerayspvm]',
										kuljetus 			= '$tiltoi[kuljetus]',
										laatija 			= '$kukarow[kuka]',
										liitostunnus		= '$tiltoi[tunnus]',
										luontiaika			= now(),
										maa 				= '$tiltoi[maa]',
										maksuteksti 		= '$tiltoi[maksuteksti]',
										myyja 				= '$myytilrow[myyja]',
										nimi 				= '$tiltoi[nimi]',
										nimitark 			= '$tiltoi[nimitark]',
										osoite 				= '$tiltoi[osoite]',
										ovttunnus 			= '$tiltoi[ovttunnus]',
										postino 			= '$tiltoi[postino]',
										postitp 			= '$tiltoi[postitp]',
										sisviesti1			= '$myytilrow[sisviesti1]',
										tila 				= 'O',
										tilaustyyppi		= '',
										tilausyhteyshenkilo	= '$kukarow[nimi]',
										toimaika 			= '$myytilrow[toimaika]',
										toimitusehto 		= '$tiltoi[toimitusehto]',
										toimitustapa 		= '$myytilrow[toimitustapa]',
										toim_maa 			= '$toim_maa',
										toim_nimi 			= '$toim_nimi',
										toim_nimitark 		= '$toim_nimitark',
										toim_osoite 		= '$toim_osoite',
										toim_ovttunnus		= '$tiltoi[ovttunnus]',
										toim_postino 		= '$toim_postino',
										toim_postitp 		= '$toim_postitp',
										valkoodi 			= '$valkoodi',
										vanhatunnus			= '',
										varasto 			= '',
										verkkotunnus		= '$myytilrow[liitostunnus]',
									 	vienti_kurssi 		= '$vrow[kurssi]',
										viesti 				= '$myytilrow[viesti]',
										viikorkopros 		= '$yhtiorow[viivastyskorko]',
										yhtio 				= '$kukarow[yhtio]',
										ytunnus				= '$tiltoi[ytunnus]'";
							$updre = mysql_query($query) or pupe_error($query);
							$tunnus = (string) mysql_insert_id();
						}
						else {
							// tilaus löyty, otetaan tunnus
							$jtsro = mysql_fetch_assoc($jtsre);
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

						while ($tilrivirow = mysql_fetch_assoc($tilrivires)) {
							// haetaan oletuspaikan tiedot niin laitetaan se riville
							$query = "SELECT * from tuotepaikat where yhtio='$kukarow[yhtio]' and tuoteno='$tilrivirow[tuoteno]' and oletus!=''";
							$jtsre = mysql_query($query) or pupe_error($query);
							$jtstu = mysql_fetch_assoc($jtsre);

							//haetaan tuotteen ostohinta
							$query = "SELECT ostohinta, alennus from tuotteen_toimittajat where yhtio='$kukarow[yhtio]' and tuoteno='$tilrivirow[tuoteno]' and liitostunnus='$ttrow[liitostunnus]'";
							$ossre = mysql_query($query) or pupe_error($query);
							$osstu = mysql_fetch_assoc($ossre);

							// lisätään ostotilausrivi
							$query = "	INSERT INTO tilausrivi
										(perheid2, hinta, ale, nimitys, tuoteno, tilkpl, varattu, otunnus, yhtio, tyyppi, kommentti, toimaika, kerayspvm, hyllyalue, hyllynro, hyllyvali, hyllytaso, laatija, laadittu) values
										('$pid','$osstu[ostohinta]','$osstu[alennus]','$tilrivirow[nimitys]','$tilrivirow[tuoteno]', '$tilrivirow[kpl]', '$tilrivirow[kpl]', '$tunnus', '$kukarow[yhtio]', 'O','$tilrivirow[kommentti]', '$tilrivirow[toimaika]', '$tilrivirow[kerayspvm]', '$jtstu[hyllyalue]','$jtstu[hyllynro]','$jtstu[hyllyvali]','$jtstu[hyllytaso]', '$kukarow[kuka]', now())";
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
							if ($rivi["var"] == "U") {
								$suoraan_laskutukseen = "o";
							}
							else {
								$suoraan_laskutukseen = "";
							}

							$query = "	UPDATE tilausrivin_lisatiedot
										SET tilausrivilinkki 	= '$lisatty_tun',
										toimittajan_tunnus		= '$ttrow[liitostunnus]',
										suoraan_laskutukseen	= '$suoraan_laskutukseen',
										muutospvm				= now(),
										muuttaja				= '$kukarow[kuka]'
										WHERE yhtio		 	 = '$kukarow[yhtio]'
										and tilausrivitunnus = '$tilrivirow[rivitunnus]'";
							$updres = mysql_query($query) or pupe_error($query);

							$tilauksesta_ostotilaus .= "<font class='message'>Tuote: $tilrivirow[tuoteno] $tilrivirow[kpl] kpl lisätty ostotilaukselle: $tunnus</font><br>";

							if ($tilrivirow["tunnus"] == $tilrivirow["perheid2"] and mysql_num_rows($tilrivires) > 1) {
								$pid = $lisatty_tun;

								$query = "	UPDATE tilausrivi
											set perheid2 = $pid
											where yhtio = '$kukarow[yhtio]' and tunnus=$pid";
								$perheres = mysql_query($query) or pupe_error($query);
							}
						}
					}

					// tehdään rivistä tavallinen JT jos meillä oli joku tilattava rivi
					if ($tyyppi == "KAIKKI" or $rivi["var"] == "T" or $rivi["var"] == "U") {
						$query = "UPDATE tilausrivi set var='J' where yhtio='$kukarow[yhtio]' and tunnus='$rivi[tunnus]'";
						$updre = mysql_query($query) or pupe_error($query);
					}
				}
			}
			return $tilauksesta_ostotilaus;
		}
	}
?>