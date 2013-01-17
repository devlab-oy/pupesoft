<?php

	if (!isset($lopetus))			$lopetus = "";
	if (!isset($lopetuslisa))		$lopetuslisa = "";
	if (!isset($silent))			$silent = "";
	if (!isset($tee))				$tee = "";
	if (!isset($tiliotejarjestys))	$tiliotejarjestys = "";
	if (!isset($td_perheid))		$td_perheid = $tiliotedatarow['perheid'];

	$tunnus = substr($tietue, 0, 3);

	if ($tunnus != 'T11' and $tunnus != 'T81' and $maksu == 1) {

		echo "<tr class='aktiivi'><td name='td_{$td_perheid}' class='$class'></td><td name='td_{$td_perheid}' class='$class'></td><td name='td_{$td_perheid}' class='$class'>";

		if ($tee == 'S') {
			echo "</td><td name='td_{$td_perheid}' class='$class'>";
		}

		// Jossain erityistapauksissa täytyy erittely käsitellä. Niille löytyy syötetyt säännöt
		if ($kuittikoodi == 'E') {
			require "inc/kasitteleerittelysaanto.inc";
		}

		if ($toim == 'K' and $kuittikoodi == ' ' and $tiliotedataka == '0000-00-00') {

			if (is_numeric($vasta_kurssi) and $vasta_kurssi != 0 and $vasta_kurssi != 1) {
				$kurssi = 1 / $vasta_kurssi;
			}
			elseif ($valuuttarow['kurssi'] != 0 and $valuuttarow['kurssi'] != 1) {
				$kurssi = $valuuttarow['kurssi'];
			}
			else {
				$kurssi = 1;
			}

			//Muutetaan määrät oikeaan valuuttaan
			if ($valuuttatili == "" and $kurssi != 1) {
				// Määrä valuutassa
				$kohdm = round($maara / $kurssi, 2);
				$kohdm_valkoodilisa = "";
			}
			elseif ($valuuttatili == "ON" and $kurssi != 1) {
				// Määrä valuutassa
				$kohdm = $maara;
				$kohdm_valkoodilisa = "";

				if ($vasta_arvo != 0) {
					// Määrä yhtiön valuutassa
					$maara = $vasta_arvo;
				}
				else {
					// Ei saatu summaa yhtiön valuutassa tiliotteelta, käännetään se kurssilla
					$maara = round($kohdm * $kurssi, 2);
				}
			}
			else {
				$kohdm = $maara;
				$kohdm_valkoodilisa = " and lasku.valkoodi = '$yhtiorow[valkoodi]' ";
			}

			if ($sisviite != 0) {
				echo "<font class='message'> ".t("Maksukandidaatti sisäinen viite & määrä")."</font><br>\n";

				$query = "	SELECT *, round(summa * vienti_kurssi, 2) 'vietysumma', round(kasumma * vienti_kurssi, 2) 'vietykasumma'
							FROM lasku
							WHERE tunnus = '$sisviite'
							and ((summa = $kohdm * -1) or (abs(summa + $kohdm - kasumma) < 0.01))
							and yhtio	= '$yritirow[yhtio]'
							and tila	= 'Q'
							$kohdm_valkoodilisa";
				$result = pupe_query($query);

				if (mysql_num_rows($result) == 0) {
					echo "<font class='error'>".t("Sopivaa maksua")." $sisviite $kohdm $tilinvaluutta ".t("ei löytynyt")."!</font><br>\n";
					echo "<font class='message'>".t("Tiliöidään tiliotesääntöjen mukaan")."</font><br>\n";
					require "inc/teeselvittely.inc";
				}
				else {
					$trow = mysql_fetch_assoc($result);

					echo "<font class='ok'>".t("Maksu löytyi")." $trow[nimi] $trow[summa] $trow[valkoodi]</font><br>\n";
					require "inc/teemaksutosite.inc";
				}
			}
			else {
				switch ($koodi) {
					case '702' : // Maksettu jotain tililtä, etsitään tilinolla ja summalla....
					case '720' : // Maksumääräys maksettu jotain tililtä, etsitään nimellä ja summalla....

						$tehdaanselvittelytosite = TRUE;
						unset($result);

						if (strlen(trim($tilino)) > 0) {
							echo "<font class='message'> ".t("Maksukandidaatti tilino & määrä")."</font><br>\n";

							$query = "	SELECT *, round(summa * vienti_kurssi, 2) 'vietysumma', round(kasumma * vienti_kurssi, 2) 'vietykasumma'
										FROM lasku
										WHERE tilinumero = '$tilino'
										and ((summa = $kohdm * -1) or (abs(summa + $kohdm - kasumma) < 0.01))
										and yhtio 	= '$yritirow[yhtio]'
										and tila 	= 'Q'
										and alatila = 'K'
										$kohdm_valkoodilisa
										ORDER BY popvm
										LIMIT 1";
							$result = pupe_query($query);
							if (mysql_num_rows($result) == 0) {
								$query = "	SELECT *, round(summa * vienti_kurssi, 2) 'vietysumma'
											FROM lasku
											WHERE tilinumero = '$tilino'
											and ((summa = $kohdm * -1) or (abs(summa + $kohdm) < 0.01))
											and yhtio 	= '$yritirow[yhtio]'
											and tila 	= 'Q'
											and alatila = ''
											$kohdm_valkoodilisa
											ORDER BY popvm
											LIMIT 1";
								$result = pupe_query($query);
							}
						}

						if ((!isset($result) or mysql_num_rows($result) != 1) and strlen(trim($maksaa)) > 0) {
							echo "<font class='message'>".t("Maksukandidaatti nimi & määrä")."</font><br>\n";

							// tarkka 35 merkkiä <-> 35 merkkiä nimihaku käteisalennuksella
							$query = "	SELECT *, round(summa * vienti_kurssi, 2) 'vietysumma', round(kasumma * vienti_kurssi, 2) 'vietykasumma'
										FROM lasku
										WHERE left(nimi, 35) = left('$maksaa', 35)
										and ((summa = $kohdm * -1) or (abs(summa + $kohdm - kasumma) < 0.01))
										and yhtio 	= '$yritirow[yhtio]'
										and tila 	= 'Q'
										and alatila = 'K'
										$kohdm_valkoodilisa";
							$result = pupe_query($query);

							// tarkka 35 merkkiä <-> 35 merkkiä nimihaku ilman käteisalennusta
							if (mysql_num_rows($result) != 1) {
								"	SELECT *, round(summa * vienti_kurssi, 2) 'vietysumma')
											FROM lasku
											WHERE left(nimi, 35) = left('$maksaa', 35)
											and ((summa = $kohdm * -1) or (abs(summa + $kohdm) < 0.01))
											and yhtio 	= '$yritirow[yhtio]'
											and tila 	= 'Q'
											and alatila = ''
											$kohdm_valkoodilisa";
								$result = pupe_query($query);
							}

							if (mysql_num_rows($result) != 1) {
								// tarkka 35 merkkiä <-> 35 merkkiä NIMI ja NIMITARK haku käteisalennuksella
								$query = "	SELECT *, round(summa * vienti_kurssi, 2) 'vietysumma', round(kasumma * vienti_kurssi, 2) 'vietykasumma'
											FROM lasku
											WHERE left(concat_ws(' ', nimi, nimitark), 35) = left('$maksaa', 35)
											and ((summa = $kohdm * -1) or (abs(summa + $kohdm - kasumma) < 0.01))
											and yhtio 	= '$yritirow[yhtio]'
											and tila 	= 'Q'
											and alatila = 'K'
											$kohdm_valkoodilisa";
								$result = pupe_query($query);

								if (mysql_num_rows($result) != 1) {
									// tarkka 35 merkkiä <-> 35 merkkiä NIMI ja NIMITARK haku ilman käteisalennusta
									$query = "	SELECT *, round(summa * vienti_kurssi, 2) 'vietysumma'
												FROM lasku
												WHERE left(concat_ws(' ', nimi, nimitark), 35) = left('$maksaa', 35)
												and ((summa = $kohdm * -1) or (abs(summa + $kohdm) < 0.01))
												and yhtio 	= '$yritirow[yhtio]'
												and tila 	= 'Q'
												and alatila = ''
												$kohdm_valkoodilisa";
									$result = pupe_query($query);
								}
							}

							if (mysql_num_rows($result) != 1) {
								// kokeillaan samalla kaavalla kun lum2 rivissä käteisalennuksella
								$query = "	SELECT *, round(summa * vienti_kurssi, 2) 'vietysumma', round(kasumma * vienti_kurssi, 2) 'vietykasumma'
											FROM lasku
											WHERE replace(left(concat_ws(' ', if(pankki_haltija != '', pankki_haltija, concat_ws(' ', nimi, if(nimitark!='', nimitark, NULL))), osoite, postitp), 30), '&', ' ') = left('$maksaa', 30)
											and ((summa = $kohdm * -1) or (abs(summa + $kohdm - kasumma) < 0.01))
											and yhtio	= '$yritirow[yhtio]'
											and tila 	= 'Q'
											and alatila = 'K'
											$kohdm_valkoodilisa";
								$result = pupe_query($query);

								if (mysql_num_rows($result) != 1) {
									// kokeillaan samalla kaavalla kun lum2 rivissä ilman käteisalennusta
									$query = "	SELECT *, round(summa * vienti_kurssi, 2) 'vietysumma'
												FROM lasku
												WHERE replace(left(concat_ws(' ', if(pankki_haltija != '', pankki_haltija, concat_ws(' ', nimi, if(nimitark!='', nimitark, NULL))), osoite, postitp), 30), '&', ' ') = left('$maksaa', 30)
												and ((summa = $kohdm * -1) or (abs(summa + $kohdm ) < 0.01))
												and yhtio	= '$yritirow[yhtio]'
												and tila 	= 'Q'
												and alatila = ''
												$kohdm_valkoodilisa";
									$result = pupe_query($query);
								}
							}

							if (mysql_num_rows($result) != 1) {
								// poistetaan oy:t ja ab:t
								$maksaa = trim(preg_replace('/\b(OY|AB)\b/i', '', strtoupper($maksaa)));

								$query = "	SELECT *, round(summa * vienti_kurssi, 2) 'vietysumma', round(kasumma * vienti_kurssi, 2) 'vietykasumma'
											FROM lasku
											WHERE left(nimi,35) LIKE '%$maksaa%'
											and ((summa = $kohdm * -1) or (abs(summa + $kohdm - kasumma) < 0.01))
											and yhtio	= '$yritirow[yhtio]'
											and tila 	= 'Q'
											and alatila = 'K'
											$kohdm_valkoodilisa";
								$result = pupe_query($query);

								if (mysql_num_rows($result) != 1) {
									$query = "	SELECT *, round(summa * vienti_kurssi, 2) 'vietysumma'
												FROM lasku
												WHERE left(nimi,35) LIKE '%$maksaa%'
												and ((summa = $kohdm * -1) or (abs(summa + $kohdm) < 0.01))
												and yhtio	= '$yritirow[yhtio]'
												and tila 	= 'Q'
												and alatila = ''
												$kohdm_valkoodilisa";
									$result = pupe_query($query);
								}
							}

							if (mysql_num_rows($result) != 1) {
								// konvertoidaan ääkköset
								$maksaa = str_replace ("å", "a", $maksaa);
								$maksaa = str_replace ("ä", "a", $maksaa);
								$maksaa = str_replace ("ö", "o", $maksaa);
								$maksaa = str_replace ("Å", "A", $maksaa);
								$maksaa = str_replace ("Ä", "A", $maksaa);
								$maksaa = str_replace ("Ö", "O", $maksaa);

								$query = "	SELECT *, round(summa * vienti_kurssi, 2) 'vietysumma', round(kasumma * vienti_kurssi, 2) 'vietykasumma'
											FROM lasku
											WHERE replace(replace(replace(replace(replace(replace(left(nimi,35),'å','a'), 'ä','a'), 'ö','o'), 'Å','A'), 'Ä','A'), 'Ö','O') LIKE '%$maksaa%'
											and ((summa = $kohdm * -1) or (abs(summa + $kohdm - kasumma) < 0.01))
											and yhtio 	= '$yritirow[yhtio]'
											and tila 	= 'Q'
												and alatila = 'K'
											$kohdm_valkoodilisa";
								$result = pupe_query($query);

								if (mysql_num_rows($result) != 1) {
									$query = "	SELECT *, round(summa * vienti_kurssi, 2) 'vietysumma'
												FROM lasku
												WHERE replace(replace(replace(replace(replace(replace(left(nimi,35),'å','a'), 'ä','a'), 'ö','o'), 'Å','A'), 'Ä','A'), 'Ö','O') LIKE '%$maksaa%'
												and ((summa = $kohdm * -1) or (abs(summa + $kohdm) < 0.01))
												and yhtio 	= '$yritirow[yhtio]'
												and tila 	= 'Q'
												and alatila = ''
												$kohdm_valkoodilisa";
									$result = pupe_query($query);
								}
							}
						}

						if (isset($result) and mysql_num_rows($result) == 1) {
							$trow = mysql_fetch_assoc($result);
							echo "<font class='ok'>".t("Maksu löytyi")." $trow[nimi] $trow[summa] $trow[valkoodi]</font><br>\n";

							$tehdaanselvittelytosite = FALSE;

							require "inc/teemaksutosite.inc";
						}

						if ($tehdaanselvittelytosite) {
							echo "<font class='error'>".t("Meillä ei ole tietoa millä tämä tapahtuma voidaan kohdistaa")."</font><br>\n";
							echo "<font class='message'>".t("Tiliöidään tiliotesääntöjen mukaan")."</font><br>\n";
							require "inc/teeselvittely.inc";
						}

						$koodi = "";
						break;
					case '730' : // Pankin kulu
						echo "<font class='message'>".t("Maksukandidaatti pankinkulu")."</font><br>\n";
						echo "<font class='ok'>".t("Rahat")." $yritirow[oletus_rahatili] ".t("kulut")." $yritirow[oletus_kulutili] ".t("summa")." $maara</font><br>\n";
						require "inc/teekulutosite.inc";
						break;
					default :
						echo "<font class='message'>".t("Tiliöidään tiliotesääntöjen mukaan")."</font><br>\n";
						require "inc/teeselvittely.inc";
				}
			}
		}
		else {
			// Miksi ei tiliöity?
			if ($kuittikoodi != ' ') {
				echo "<font class='message'>".t("Hylätty, koska erittely tulossa")." ($kuittikoodi) </font><br>\n";

				if ($erialla == "E") {
					echo "<font class='message'>".t("Erittely alla").".</font><br>\n";
				}
				else {

					$erilinkkiekotus = FALSE;

					if ($koodi == "702") {
						// Luultavimmin LMP-ainesisto
						$query = "	SELECT *
									FROM tiliotedata
									WHERE alku = '".(substr($pvm,0,2)+2000)."-".substr($pvm,2,2)."-".substr($pvm,4,2)."'
									and tilino = '$tiliotedatarow[tilino]'
									and tyyppi = '2'
									ORDER BY alku, tunnus";
						$tiliointiresult = pupe_query($query);

						if ($tiliointirow = mysql_fetch_assoc ($tiliointiresult)) {
							echo "<a href='{$palvelin2}tilioteselailu.php?tee=T&pvm=$pvm&tyyppi=2&tilino=$tiliotedatarow[tilino]&tiliotejarjestys=$tiliotejarjestys&lopetus=$lopetus$lopetuslisa'>".t("Avaa LMP-erittely")."</a><br>\n";

							$erilinkkiekotus = TRUE;
						}
					}

					if (!$erilinkkiekotus) {
						$query = "	SELECT *
									FROM tiliotedata
									WHERE alku = '".(substr($pvm,0,2)+2000)."-".substr($pvm,2,2)."-".substr($pvm,4,2)."'
									and tilino = '$tiliotedatarow[tilino]'
									and tyyppi = '3'
									ORDER BY alku, tunnus";
						$tiliointiresult = pupe_query($query);

						if ($tiliointirow = mysql_fetch_assoc ($tiliointiresult)) {
							echo "<a href='{$palvelin2}tilioteselailu.php?tee=T&pvm=$pvm&tyyppi=3&tilino=$tiliotedatarow[tilino]&tiliotejarjestys=$tiliotejarjestys&lopetus=$lopetus$lopetuslisa'>".t("Avaa viiteaineisto")."</a><br>\n";

							$erilinkkiekotus = TRUE;
						}
					}

					if (!$erilinkkiekotus) {
						echo "<font class='message'>".t("VIRHE: Erittely kateissa")."!</font><br>\n";
					}
				}
			}

			if ($toim != 'K') {
				echo "<font class='message'>".t("Tili, rahatili, kulutili tai tilin valuutta puuttuu/virheellinen")."!</font><br>\n";
			}

			if ($tiliotedataka != '0000-00-00') {
				$query = "	SELECT tiliointi.tilino tilino, tili.nimi nimi, concat_ws('@',korjattu, korjausaika) korjattu, ltunnus
							FROM tiliointi
							LEFT JOIN tili on tili.yhtio=tiliointi.yhtio and tili.tilino=tiliointi.tilino
							WHERE tiliointi.tunnus = '$tiliotedatatu'";
				$tiliointiresult = pupe_query($query);

				if ($tiliointirow = mysql_fetch_assoc($tiliointiresult)) {
					echo "<font class='message'>".t("Tapahtuma on käsitelty")." ".tv1dateconv($tiliotedataka)."<br>\n";
					echo t("Alkuperäinen tiliöinti on")."</font> <a href=\"javascript:lataaiframe('{$tiliotedatarow['tunnus']}', '{$palvelin2}muutosite.php?tee=E&tunnus=$tiliointirow[ltunnus]&ohje=off');\">$tiliointirow[tilino]/$tiliointirow[nimi]</a><br>\n";

					if (($tiliointirow['korjattu'] != "0000-00-00 00:00:00") and ($tiliointirow['korjattu'] != "@0000-00-00 00:00:00")) {
						echo "<font class='message'>".t("Tiliöinti korjattu")." $tiliointirow[korjattu]</font>";
					}
				}
				elseif ($kuittikoodi == ' ') {
					echo "<font class='error'>".t("VIRHE: Tiliöinti puuttuu")."!</font>";
				}
			}
		}

		echo "<td name='td_{$td_perheid}' class='$class'></td><td name='td_{$td_perheid}' class='$class'>";

		//Jos tämä on selvittelyssä, niin annetaan mahdollisuus suorittaa!
		if (isset($tiliointirow) and $kuitattu_checked == '' and ($tiliointirow['korjattu'] == "0000-00-00 00:00:00" or $tiliointirow['korjattu'] == "@0000-00-00 00:00:00") and ($tiliointirow['tilino'] == $yhtiorow['selvittelytili'] or $tiliointirow['tilino'] == $yritirow['oletus_selvittelytili']) and $kuittikoodi == ' ') {
			//Olisiko tämä meidän toimittaja
			$tpv = substr($pvm,0,2);
			$tpk = substr($pvm,2,2);
			$tpp = substr($pvm,4,2);

			$query = "SELECT ytunnus FROM toimi WHERE yhtio = '$yritirow[yhtio]' and nimi LIKE '%$maksaa%'";
			$suotoiresult = pupe_query($query);

			$boob = 0;

			if (mysql_num_rows($suotoiresult) > 0) {
				echo "<a href=\"javascript:lataaiframe('{$tiliotedatarow['tunnus']}', '{$palvelin2}suori.php?tee=W&tiliote=Z&mav=$tpv&mak=$tpk&map=$tpp&mtili=$yritirow[tunnus]&tiliotesumma=".(-1*$maara)."&ohje=off');\">".t("Ostosuoritus")."</a><br>\n";
				$boob = 1;
			}

			//Olisiko tämä meidän asiakas
			$query = "SELECT * FROM asiakas WHERE yhtio = '$yritirow[yhtio]' and nimi LIKE '%$maksaa%'";
			$suoasiresult = pupe_query($query);

			if (mysql_num_rows($suoasiresult) == 1) {
				$suoasirow = mysql_fetch_assoc ($suoasiresult);
				echo "<a href=\"javascript:lataaiframe('{$tiliotedatarow['tunnus']}', '{$palvelin2}myyntires/maksunsyotto.php?asiakasid=$suoasirow[tunnus]&ytunnus=$suoasirow[ytunnus]&summa=$maara&ppa=$tpp&kka=$tpk&vva=$tpv&mtili=$yritirow[tunnus]&tiliote=Z&selite=$selite_maksuavarten&ohje=off');\">".t("Myyntisuoritus")." ".substr($suoasirow["nimi"], 0, 34)."</a><br>\n";
				$boob = 1;
			}

			if ($boob == 0) {
				echo "<a href=\"javascript:lataaiframe('{$tiliotedatarow['tunnus']}', '{$palvelin2}suori.php?tee=W&tiliote=Z&mav=$tpv&mak=$tpk&map=$tpp&mtili=$yritirow[tunnus]&tiliotesumma=".(-1*$maara)."&ohje=off');\">".t("Ostosuoritus")."</a></font><br>\n";
			}

			//	Annetaan Muu aina
			echo "<a href=\"javascript:lataaiframe('{$tiliotedatarow['tunnus']}', '{$palvelin2}myyntires/maksunsyotto.php?summa=$maara&ppa=$tpp&kka=$tpk&vva=$tpv&mtili=$yritirow[tunnus]&tiliote=Z&selite=$selite_maksuavarten&maksaja_haku=$maksaa&ohje=off');\">".t("Myyntisuoritus muu")."</a>";

		}
		echo "</td><td name='td_{$td_perheid}' class='$class'>";

		if ($kuittikoodi == ' ') {
			if ($eiselitetta == 1) $selite = '';
			echo "<a href=\"javascript:lataaiframe('{$tiliotedatarow['tunnus']}', '{$palvelin2}tiliotesaannot.php?tee=U&pankkitili=$yritirow[tilino]&tiliote=Z&koodi=$koodi&koodiselite=$koodiselite&nimitieto=$maksaa&selite=$selite&pvm=$pvm&ohje=off');\">".t("Tee tiliotesääntö")."</a>";
		}

		echo "</td><td class='back' style='width:200px;'></td></tr>";

		$maksu = 0; // Maksu on käsitelty
		$maara = 0;
		$eiselitetta = 0;
	}

	switch ($tunnus) {

		case 'T00' :

			/*
			// Tiliotteen perustietue (00)

			Kenttä Tieto                             Pakollisuus Muoto Pituus    Sisältö
			1      Aineistotunnus                    P              AN      1    T
			2      Tietuetunnus                      P              AN      2    00
			3      Tietueen pituus                   P               N      3
			4      Versionumero                      P              AN      3    100
			5      Tilinumero                        P              AN     14
			6      Tiliotteen numero                 P               N      3
			7      Tiliotekausi
			7.1      Alkupäivä                       P               N      6    VVKKPP
			7.2      Loppupäivä                      P               N      6    VVKKPP
			8      Muodostumisaika
			8.1      Päivä                                             N      6    VVKKPP
			8.2      Kellonaika                                        N      4    HHMM
			9      Asiakastunnus                     P              AN     17
			10     Alkusaldon pvm                    P               N      6
			11     Tiliotteen alkusaldo
			11.1     Etumerkki                       P              AN      1
			11.2     Määrä                           P               N     18    16 kok + 2 desim
			12     Tiliotteen tietueiden lukumäärä   P               N      6
			13     Tilin valuutan tunnus                            AN      3
			14     Tilin nimi                                       AN     30
			15     Tilin limiitti                    P               N     18    16 kok + 2 desim
			16     Tilin omistajan nimi              P              AN     35
			17     Yhteydenottotieto-1               P              AN     40    Pankin nimi
			18     Yhteydenottotieto-2                              AN     40    Konttorin nro ja nimi
			19     Pankkikohtaista tietoa-1                         AN     30    Konsernitili ja nro
			20     Pankkikohtaista tietoa-2                         AN     30    Iban ja swif-tunnus
			       YHTEENSÄ                                               322
			*/

			// Meillä on entinen tili auki
			if ($tkesken == 1) {
				// Tehdään vastakirjaus
				$maara 		= $vastavienti;
				$kohdm		= $vastavienti_valuutassa;
				$tkesken 	= 0;

				if ($silent == "") {
					echo "</table><br>\n";
				}

				require "inc/teeselvittely.inc";

				if ($silent == "") {
					echo "<br>\n<br>\n<table>";
				}
			}
			else {
				echo "<table>";

				if ($silent == "") {

					$seltiliotejarjestys = "";
					if ($tiliotejarjestys != "") $seltiliotejarjestys = "SELECTED";

					echo "<tr>
							<th><a href='{$palvelin2}tilioteselailu.php?tilino=$tiliotedatarow[tilino]&pvm=$tiliotedatarow[alku]&tee=XX&tiliotejarjestys=$tiliotejarjestys&lopetus=$lopetus'>&laquo; ".t("Edellinen")."</th>
							<th>";
					echo "<form action='{$palvelin2}tilioteselailu.php' method='post'>";
					echo "<input type='hidden' name='tee'     value='T'>";
					echo "<input type='hidden' name='tyyppi'  value='$lopp_tyyppi'>";
					echo "<input type='hidden' name='tilino'  value='$lopp_tilino'>";
					echo "<input type='hidden' name='pvm' 	  value='$lopp_pvm'>";
					echo "<input type='hidden' name='lopetus' value='$lopetus'>";

					echo "<select name='tiliotejarjestys' onchange='submit();'>
							<option value=''>".t("Oletusjärjestys")."</option>
							<option value='K' $seltiliotejarjestys>".t("Kuitattaamattomat ensin")."</option>
							</select>
							</form>";
					echo " </th>
							<th><a href='{$palvelin2}tilioteselailu.php?tilino=$tiliotedatarow[tilino]&pvm=$tiliotedatarow[alku]&tee=X&tiliotejarjestys=$tiliotejarjestys&lopetus=$lopetus'>".t("Seuraava")." &raquo;</a></th></tr>";
				}
			}

			$laskuid 	  = 0;
			$tkesken 	  = 1;
			$maara 		  = 0;
			$pankkitilino = substr($tietue, 9, 14);
			$toim         = "K";

			if ($silent == "") {
				echo "<tr>";
				echo "<td>".substr($tietue,182,40)."</td>";
				echo "<td>".t("Tiliote")." ".substr($tietue,23,3)."</td><td></td>";
				echo "</tr>";

				echo "<tr>";
				echo "<td rowspan='2'>". substr($tietue,222,40)."<br>\n". substr($tietue,262,30) ."<br>\n". substr($tietue,292,30) ."</td>";
				echo "<td></td>";
				echo "<td>".t("Muodostettu")."<br>\n". pankkipvm(substr($tietue,38,6)) ." ".substr($tietue,44,4) ."</td>";
				echo "</tr>";

				echo "<tr>";
				echo "<td>".t("Tulkittu asiakkaalla")."</td>";
			}

			$alkupvm  = substr($tietue,26,6);
			$loppupvm = substr($tietue,32,6);

			if ($silent == "") {
				echo "<td>".t("Kausi")."<br>\n". pankkipvm($alkupvm) ." - ". pankkipvm($loppupvm)."</td>";
				echo "</tr>";

				echo "<tr>";
				echo "<td>".substr($tietue, 147, 18)."</td>";
				echo "<td>".substr($tietue, 99, 30)."<br>\n$tilinvaluutta".tilinumero_print($pankkitilino)."</td>";

				$tililimiitti = (int) (substr($tietue, 117, 30) / 100);

				if ($tililimiitti > 0) {
					$tililimiitti =  sprintf("%01.2f", $tililimiitti);
				}
				else {
					$tililimiitti =  "<br>\n";
				}

				echo "<td>".t("Myönnetty määrä")."<br>\n". $tililimiitti."</td>";
				echo "</tr>";
			}

			$query = "	SELECT *
						FROM yriti
						WHERE tilino = '$pankkitilino'
						and kaytossa = ''";
			$result = pupe_query($query);

			if ($silent == "") {
				echo "<tr><td colspan='3'>";
			}

			if (mysql_num_rows($result) != 1) {
				echo "<font class='error'> ".t("Tiliä")." '$pankkitilino' ".t("ei löytynyt")."!</font><br>\n";
				$toim = 'E';
			}
			else {
				$yritirow = mysql_fetch_assoc($result);

				$query = "	SELECT *
							FROM yhtio
							WHERE yhtio = '$yritirow[yhtio]'";
				$result = pupe_query($query);

				if (mysql_num_rows($result) != 1) {
					echo "<font class='error'>".t("Yritystä")." '$yritirow[yhtio]' ".t("ei löytynyt")."!</font><br>\n";
					$toim = 'E';
				}
				else {
					$yhtiorow = mysql_fetch_assoc($result);

					// Setataan kukarow-yhtiö
					$kukarow["yhtio"] = $yritirow["yhtio"];

					if ($silent == "") {
						echo "<font class='message'>".t("Tiliote yritykselle")." $yhtiorow[nimi].<br>\n";
					}

					if ($yritirow["hyvak"] != "") {
						if ($silent == "") {
							echo t("Vastuussa")." '$yritirow[hyvak]'.<br>\n";
						}
					}

					if ($silent == "") {
						echo t("Ostovelat")." $yhtiorow[ostovelat], ".t("Konserniostovelat")." $yhtiorow[konserniostovelat], </font>";
					}
				}

 				// Tarkistetaan tilit
				if (strlen($yritirow['oletus_rahatili']) == 0 or strlen($yritirow['oletus_kulutili']) == 0) {
					echo "<font class='error'>".t("Tililtä puuttuu kulu tai rahatili")."</font>";
					$toim = 'E';
				}
				else {
					if ($silent == "") {
						if($yritirow["oletus_selvittelytili"] > 0) {
							$selvittelytili = $yritirow["oletus_selvittelytili"];
						}
						else {
							$selvittelytili = $yhtiorow['selvittelytili'];
						}
						echo "<font class='message'>".t("Rahatili")." $yritirow[oletus_rahatili], ".t("Kulutili")." $yritirow[oletus_kulutili], ".t("Selvittelytili")." $selvittelytili</font><br>\n";
					}
				}

				$tilinvaluutta = substr($tietue,96,3);

				// Onko tämä valuuttatili
				if ($tilinvaluutta != '' and trim(strtoupper($tilinvaluutta)) != trim(strtoupper($yhtiorow["valkoodi"]))) {
					$valuuttatili = "ON";
				}
				else {
					$valuuttatili = "";
				}

				$query = "	SELECT *
							FROM valuu
							WHERE yhtio = '$yritirow[yhtio]' and nimi = '$tilinvaluutta'";
				$result = pupe_query($query);

				if (mysql_num_rows($result) != 1) {
					echo "<font class='error'>".t("Valuuttaa")." '$tilinvaluutta' ".t("ei löytynyt")."!</font><br>\n";
					$toim = 'E';
				}
				else {
					$valuuttarow = mysql_fetch_assoc($result);

					if ($valuuttarow['kurssi'] != 1) {
						if ($silent == "") {
							echo "<font class='message'>".t("Tiliote kirjataan kurssiin")." ".(round(1/$valuuttarow['kurssi'], 9))." ($valuuttarow[kurssi]).</font><br>\n";
						}
					}

					if ($valuuttatili == "ON" and trim(strtoupper($tilinvaluutta)) != trim(strtoupper($yritirow["valkoodi"]))) {
						echo "<font class='error'>".t("Tiliotteen valuuttakoodi on eri kuin yrityksen pankkitilin valuutta")."!</font>";
						$toim = 'E';
					}
				}
			}

			if ($silent == "") {
				echo "</td></tr></table>"; 	// Tämä päättää otsikon
				echo "<br>\n<table>"; 		// Aloitetaan tapahtumat
			}

			echo "<tr>";
			if ($tee == 'S') echo "<th>",t("Kuitattu"),"</th>";
			echo "<th>".t("Arkistointitunnus")."<br>\n".t("Saajan tilinumero")."</th>";
			echo "<th>".t("Maksup.")."<br>\n".t("Arvop.")."</th>";
			echo "<th>".t("Saaja/Maksaja")."<br>\n".t("Viesti")."</th>";
			echo "<th>".t("Tap.")."<br>\n".t("n:o")."</th>";
			echo "<th>".t("Tiliöinti")."</th>";
			echo "<th>".t("Määrä")."</th>";
			echo "<td class='back' style='width:200px;'></td></tr>";

			echo "<tr><td></td><td></td><td></td><td></td>";
			if ($tee == 'S') echo "<td></td>";
			echo "<td>".t("Saldo")." ".pankkipvm(substr($tietue,65,6))."</td>";
			echo "<td style='text-align:right;'>".sprintf("%01.2f",((substr($tietue,71,19) / 100)-$tililimiitti))."</td>";
			echo "<td class='back' style='width:200px;'></td></tr>";

			if ($tililimiitti != "") {
				echo "<tr><td></td><td></td><td></td><td></td>";
				if ($tee == 'S') echo "<td></td>";
				echo "<td>".t("Käyttövara")."</td>";
				echo "<td style='text-align:right;'>".(substr($tietue,71,19) / 100)."</td>";
				echo "<td class='back' style='width:200px;'></td></tr>";
			}

			break;

		case 'T10' :

			/*
			// Tapahtuman perustietue (10, 80)

			Kenttä  Tieto                  Pakollisuus Muoto Pituus   Sisältö
			1       Aineistotunnus         P              AN      1   T
			2       Tietuetunnus           P              AN      2   10, 80
			3       Tietueen pituus        P               N      3
			4       Tapahtuman numero      P               N      6   Juokseva nro
			5       Arkistointitunnus                     AN     18
			6       Kirjauspäivä           P               N      6   VVKKPP
			7       Arvopäivä                              N      6   VVKKPP
			8       Maksupäivä                             N      6   VVKKPP
			9       Tapahtumatunnus        P              AN      1   1, 2, 3, 4
			10      Kirjausselite
			10,1    Koodi                  P              AN      3
			10,2    Seliteteksti           P              AN     35
			11      Tapahtuman rahamäärä
			11,1    Etumerkki              P              AN      1
			11,2    Määrä                  P               N     18   16kok+ 2desim
			12      Kuittikoodi            P              AN      1
			13      Välitystapa            P              AN      1
			14      Saaja/Maksaja
			14,1    Nimi                                  AN     35
			14,2    Nimen lähde                           AN      1
			15      Saajan tili
			15,1    Tilinumero                            AN     14
			15,2    Tili muuttunut -tieto                 AN      1   tyhjämerkki, *
			16      Viite                                  N     20
			17      Lomakkeen numero                      AN      8
			18      Tasotunnus                            AN      1
			        YHTEENSÄ                                    188
			*/

			$maksu			= 1;
			$sisviite		= 0;
			$vasta_arvo		= 0;
			$vasta_kurssi	= 1;

			// Nollataan tämä tässä
			$selite_maksuavarten = "";

			$tiliotedataid = $tiliotedatarow['tunnus'];
			$tiliotedatape = $tiliotedatarow['perheid'];
			$tiliotedataka = $tiliotedatarow['kasitelty'];
			$tiliotedatatu = $tiliotedatarow['tiliointitunnus'];

			$koodi = substr($tietue, 49, 3);

			// Sampo häkki
			if ($koodi == '700') {
				$koodi = '702';
			}

			// Suoraveloitukset kohdistetaan kuin maksumääräykset
			if ($koodi == '704') {
				$koodi = '720';
			}

			// VVKKPP
			$kirjpvm = substr($tietue, 30, 6);
			$pvm 	 = substr($tietue, 36, 6);

			// Tiliöidään kuitenkin loppupeleissä kaikki kirjauspvm:lle.
			$pvm = $kirjpvm;

			if ($pvm == "000000") { // Haa, pankki ei antanutkaan arvopvmää, joten otetaan käyttöön tapahtumapvm
				$pvm = $kirjpvm;
			}

			//echo "Tunnus: '". substr($tietue, 48, 1)."'<br>\n";
			$etumerkki = substr($tietue, 87, 1);
			$maara = substr($tietue, 88, 18) / 100;

			if ($etumerkki == '-') {
				$maara *= -1;
			}

			$maara 		 = sprintf('%.2f', $maara);
			$kuittikoodi = substr($tietue,106,1);
			$erialla 	 = "";
			$maksaa 	 = trim(substr($tietue, 108, 35));
			$tilino 	 = substr($tietue, 144, 14);
			$omataso 	 = substr($tietue, 187, 1);
			$koodiselite = trim(substr($tietue, 52, 35));

			// Tutkitaan seuraava maksu ja sen avulla laitetaan kuittikoodi ja erialla arvot kuntoon
			$tdq = "	SELECT tiliotedata.*
						FROM tiliotedata
						WHERE yhtio = '$tiliotedatarow[yhtio]'
						and alku 	= '$tiliotedatarow[alku]'
						and tilino  = '$tiliotedatarow[tilino]'
						and tyyppi  = '$tiliotedatarow[tyyppi]'
						and tunnus  > '$tiliotedatarow[tunnus]'
						ORDER BY perheid, tunnus";
			$tdres2peek = pupe_query($tdq);

			while ($tdrow2peek = mysql_fetch_assoc($tdres2peek)) {
				$tdr2tunn = substr($tdrow2peek['tieto'], 0, 3);

				if ($tdr2tunn != 'T11' and $tdr2tunn != 'T81') {

					// Tämä on case Sampo ja erittelyt onkin ilmoitettu tasoissa!
					// eli jos valmista maksua seuraava tapahuma on suurempitasoinen T10 ei tätä edeltävää maksua käsitellä
					if (substr($tdrow2peek['tieto'], 187, 1) > $omataso and $tdr2tunn == 'T10') {
						$kuittikoodi 	= "E";
						$erialla 		= "E";
					}

					break;
				}
			}

			if ($kuittikoodi == "E" and $koodi == "710" and strtoupper(trim($koodiselite)) == "PANO") {

				$query = "	SELECT *
							FROM tiliotedata
							WHERE yhtio = '$tiliotedatarow[yhtio]'
							and alku 	= '$tiliotedatarow[alku]'
							and tilino 	= '$tiliotedatarow[tilino]'
							and tyyppi 	= '$tiliotedatarow[tyyppi]'
							and substr(tieto,13,18) = '".substr($tietue,12,18)."'
							and tunnus != '$tiliotedatarow[tunnus]'";
				$tdres2peek = mysql_query($query) or pupe_error($query);

				# Jos koodi on 710 ja koodiselite on PANO ja kuittikoodi on "E", mutta samalla arkistointitunnuksella EI löydy toista tapahtumaa, niin nollataan kuittikoodi.
				if (mysql_num_rows($tdres2peek) == 0) {
					$kuittikoodi = " ";
				}
			}

			if ($kuittikoodi == "E" and $tiliotedatarow["kuitattu"] == "") {
				$query = "UPDATE tiliotedata SET kuitattu = 'tiliote', kuitattuaika = now() WHERE yhtio = '$tiliotedatarow[yhtio]' and perheid = '$tiliotedatarow[perheid]'";
				$dummyresult = pupe_query($query);

				$tiliotedatarow["kuitattu"] = "tiliote";
				$tiliotedatarow["kukanimi"] = "tiliote";
				$tiliotedatarow["kuitattuaika"] = date("Y-m-d H:i:s");
			}

			echo "<tr><td colspan='8' class='back' style='width:100%; padding:0; margin:0;'><div id = 'ifd_{$tiliotedatarow['tunnus']}' style='width:100%; border:1px solid; display:none'></div><br></td></tr>";

			$td_perheid = $tiliotedatarow['perheid'];

			echo "<tr class='aktiivi'>";

			$class = '';

			if ($tee == 'S') {

				//Pidetään tallessa
				$tiliotedatarowtunnus = $tiliotedatarow["tunnus"];

				$lopetuslisa = "/SPLIT/${palvelin2}tilioteselailu.php////tee=T//tyyppi=$lopp_tyyppi//tilino=$lopp_tilino//pvm=$lopp_pvm//tiliotejarjestys=$tiliotejarjestys///kuitattu_$tiliotedatarowtunnus";

				// Kuitattu checkbox
				$kuitattu_checked = ($tiliotedatarow['kuitattu'] != '' and $tiliotedatarow['kuitattuaika'] > '0000-00-00 00:00:00') ? ' CHECKED' : '';

				$class = $kuitattu_checked != '' ? 'spec' : '';

				$anklinkki = $tiliotedatarow["tunnus"];

				if ($tiliotejarjestys != "") {
					// Peek ahead
					if ($tilioterivilaskuri < $tilioterivimaara) {
						while ($tilrow_seuraava = mysql_fetch_assoc($tiliotedataresult)) {
							if ($tilrow_seuraava["perheid"] > $tiliotedatarow["perheid"] ) {
								$anklinkki = $tilrow_seuraava["perheid"];
								break;
							}
						}

						mysql_data_seek($tiliotedataresult, $tilioterivilaskuri);

					}
					else {
						$anklinkki = $tiliotedatarow["tunnus"];
					}
				}

				echo "<td name='td_{$td_perheid}' class='$class'>";
				echo "<form name='kuittaus' action='javascript:sndReq(\"tiliote\", \"{$palvelin2}tilioteselailu.php?tee=T&kuitattava_tiliotedata_tunnus={$tiliotedatarow['tunnus']}\");' method='GET'>";
				echo "<input type='checkbox' name='kuitattu' $kuitattu_checked onchange=\"vaihdacss('{$tiliotedatarow['perheid']}'); submit();\">";
				echo "</form>";
				echo "</td>";
			}

			echo "<td name='td_{$td_perheid}' class='$class'>".substr($tietue,12,18)."</td>";
			echo "<td name='td_{$td_perheid}' class='$class'>".pankkipvm($kirjpvm)."</td>";
			echo "<td name='td_{$td_perheid}' class='$class'>$maksaa</td>";
			echo "<td name='td_{$td_perheid}' class='$class'>".(substr($tietue, 6, 6) * 1)."$kuittikoodi $omataso</td>";
			echo "<td name='td_{$td_perheid}' id='kuitattu_{$td_perheid}' class='$class'>";

			if ($kuitattu_checked != '') {
				echo "<font class='ok'>",t("Kuitattu"),": $tiliotedatarow[kukanimi] @ ",tv1dateconv($tiliotedatarow['kuitattuaika'], "pitkä"),"</font>";
			}

			echo "</td>";
			echo "<td name='td_{$td_perheid}' class='$class' style='text-align:right;'>$maara</td>";
			echo "<td class='back' style='width:200px;'></td></tr>";
			echo "<tr class='aktiivi'>";

			if ($tee == 'S') echo "<td name='td_{$td_perheid}' class='$class'></td>";

			echo "<td name='td_{$td_perheid}' class='$class'>".tilinumero_print($tilino)."</td>";
			echo "<td name='td_{$td_perheid}' class='$class'>".pankkipvm($pvm)."</td>";
			echo "<td name='td_{$td_perheid}' class='$class'>$koodi $koodiselite</td>";
			echo "<td name='td_{$td_perheid}' class='$class'></td>
				  <td name='td_{$td_perheid}' class='$class'></td>
				  <td name='td_{$td_perheid}' class='$class'></td>";
			echo "<td class='back' style='width:200px;'></td></tr>";
			$vientiselite = "$maksaa<br>\n$koodi $koodiselite<br>\n";
			break;

		case 'T11' :

			/*
			// Tapahtuman lisätietue (11, 81)

			Kenttä     Tieto                                        Pakollisuus Muoto Pituus  Sisältö
			1          Aineistotunnus                               P           AN    1       T
			2          Tietuetunnus                                 P           AN    2       11, 81
			3          Tietueen pituus                              N           3
			4          Lisätiedon tyyppi                            P           AN    2
			5          Lisätieto                                    P           AN    nnn
			YHTEENSÄ                                                                  8 + nnn

			Vapaa viesti, lisätiedon tyyppi = 00
			5.1        Viesti-1                                     P           AN    35
			5.2        Viesti-2                                                 AN    35
			...
			5.12       Viesti-12                                                AN    35
			YHTEENSÄ                                                                  max 420

			Kappalemäärätiedot, lisätiedon tyyppi = 01
			5.1        Tapahtumien kappalemäärä                     P           N     8
			YHTEENSÄ                                                                  8

			Laskutapahtuman tiedot, lisätiedon tyyppi = 02
			5.1        Asiakasnumero                                P           AN    10
			5.2        Tyhjää                                       P           AN    1
			5.3        Laskun numero                                P           AN    15
			5.4        Tyhjää                                       P           AN    1
			5.5        Laskun päiväys                               P           AN    6       VVKKPP
			YHTEENSÄ                                                                  33

			Korttitapahtuman tiedot, lisätiedon tyyppi = 03
			5.1        Kortin numero                                P           AN    19
			5.2        Tyhjää                                                   AN    1
			5.3        Kaupan arkistoviite                                      AN    14
			YHTEENSÄ                                                                  34

			Korjaustapahtuman tiedot, lisätiedon tyyppi = 04
			5.1        Korjattavan tapahtuman alkup.arkistointitun. P           AN    18
			YHTEENSÄ                                                                  18

			Valuuttatapahtuman tiedot, lisätiedon tyyppi = 05
			5.1        Vasta-arvo
			.1         Etumerkki                                    P           AN    1
			.2         Määrä                                        P           N     18      16 kok + 2 desim
			5.2        Tyhjää                                       P           AN    1
			5.3        Valuutan ISO-koodi                           P           AN    3
			5.4        Tyhjää                                       P           AN    1
			5.5        Valuuttakurssi                               P           N     11      4 kok + 7 desim
			5.6        Kurssiviite                                              AN    6
			YHTEENSÄ                                                                  41

			Toimeksiantajan tiedot, lisätiedon tyyppi = 06
			5.1        Toimeksiantajan tieto-1                      P           AN    35
			5.2        Toimeksiantajan tieto-2                                  AN    35
			YHTEENSÄ                                                                  70

			Pankin antamat lisätiedot, lisätiedon tyyppi = 07
			5.1        Lisätieto-1                                  P           AN    35
			5.2        Lisätieto-2                                              AN    35
			...
			5.12       Lisätieto-12                                             AN    35
			YHTEENSÄ                                                                  max 420

			Maksunaiheen tiedot, lisätiedon tyyppi = 08
			5.1        Maksunaihekoodi                              P           N     3
			5.2        Tyhjää                                       P           AN    1
			5.3        Maksunaiheen selite                          P           AN    31
			YHTEENSÄ                                                                  35

			Nimitarkenteen tiedot, lisätiedon tyyppi = 09
			5.1        Saajan/maksajan nimen tarkenne               P           AN    35
			YHTEENSÄ                                                                  35

			Euromaksualueen tilisiirron lisätyyppi, lisätiedon tyyppi = 11
			5.1        Maksajan viite                               V           AN    35      Maksajan tapahtumalle antama yksilöivä viite (end-to-end ID), joka näkyy sekä ,maksajan että saajan tiliotteella
			5.2        IBAN tilinumero                              V           AN    35      Saajan IBAN tilinumero maksajan tiliotteella
			5.3        BIC-koodi                                    V           AN    35      Saajan pankin BIC- koodi maksajan tiliotteella
			5.4        Saajan nimitarkenne                          V           AN    70      Maksajan antama saajan nimitarkenne sekä saajan että maksajan tiliotteella
			5.5        Maksajan nimitarkenne                        V           AN    70      Maksajan antama maksajan nimitarkenne sekä saajan että maksajan tiliotteella
			5.6        Maksajan tunniste                            V           AN    35      Maksajan tunnisteen selite (BIC, IBEI, BEI, EANGLN, UNSCHU, DUNS, BkPtyId, TaxIdNb, PrtyId) ja tunnisteen arvo.
			5.7        Arkistointitunnus                            V           AN    35      SEPA arkistointitunnus täysimittaisena. Tieto on myös tapahtuman perustietueella (T10) kentässä Arkistointitunnus, mutta pituuserosta johtuen se on k
			YHTEENSÄ   315

			*/

			$selite 	= '';
			$tyyppi 	= substr($tietue, 6, 2);
			$arvo 		= substr($tietue, 8, substr($tietue, 3, 3));

			// Nordea Viro häkki
			if ($yhtiorow['maa'] == "EE" and $koodi == '705' and $kuittikoodi != "E" and stripos($arvo, "REFERENCE PAYMENT") !== FALSE) {
				$kuittikoodi = "E";
			}

			echo "<tr class='aktiivi'><td name='td_{$td_perheid}' class='$class'></td><td name='td_{$td_perheid}' class='$class'></td>";

			if ($tee == 'S') echo "<td name='td_{$td_perheid}' class='$class'></td>";

			switch ($tyyppi) {

				// Vapaa viesti
				case '00' :
				   	$selite = "";
				   	$luottokuntatieto = "";
					$selite_maksuavarten = htmlspecialchars(trim(strip_tags($arvo)));

					for ($i = 0; $i < 13; $i++) {
				   		$osa = trim(substr($arvo, $i*35, 35));

						if (strlen($osa) > 0) {
				   			$selite .= $osa."<br>\n";
				   		}
						// Luottokunnan käsittelyä varten
						if ($i == 1) $luottokuntatieto = $osa;
				   	}
					break;

				// Kappalemäärätiedot
				case '01' :
					$selite = t("Tapahtumia").": ".substr($arvo, 0, 8) * 1;
					//Tällä taklataan Aktian virhettä, jossa tuo E puuttuu aineistosta
					if ((substr($pankkitilino,0,1) == '4') and ($kuittikoodi == ' ')) $kuittikoodi='Z';
					break;

				// Laskutapahtuman tiedot
				case '02' :
					$osa_osa1 = trim(substr($tietue,  8, 10));
					$osa_osa2 = trim(substr($tietue, 19, 15));
					$osa_osa3 = trim(substr($tietue, 35,  6));
					$selite = "";

					if ($osa_osa1 != "" and $osa_osa1 != 0) {
						$selite .= t("Asiakasnumero").": $osa_osa1<br>\n";
					}
					if ($osa_osa2 != "" and $osa_osa2 != 0) {
						$selite .= t("Laskun numero").": $osa_osa2<br>\n";
					}
					if ($osa_osa3 != "" and $osa_osa3 != 0) {
						$selite .= t("Laskun päiväys").": $osa_osa3";
					}
					break;

				// Valuuttatapahtuman tiedot
				case '05' :
					$vasta_arvo	= sprintf('%.2f', substr($arvo, 0, 19)/100);
					$vasta_kurssi = substr($arvo, 24, 11) * 1 / 10000000;

					$selite  = t("Vasta-arvo").": ".sprintf('%.2f', substr($arvo, 0, 19)/100)."<br>\n";
					$selite .= t("Valuutta").": ".substr($arvo, 20, 3)."<br>\n";
					$selite .= t("Kurssi").": ".substr($arvo, 24, 11) * 1 / 10000000;
					break;

				// Toimeksiantajan tiedot itselleen
				case '06' :
					$selite = t("Oma sisäinen viite").": ";
					$selite .= trim(substr($arvo, 0, 35))."<br>\n";
					$selite .= trim(substr($arvo, 35, 35));
					$sisviite = trim(substr($arvo, 0, 35));

					//Tämä on (kai) ongelma Analysten pankkisoftassa. Lisäävät joskus oman tunnuksen
					//ekaan sisäinenviite kenttään, silloin meidän onkin toisessa.
					if ((trim(substr($arvo, 35, 35))*1 != 0) and (trim(substr($arvo, 0, 35))*1 != 0)) {
						$sisviite = trim(substr($arvo, 35, 35));
					}

					// sepa-aineistoon lisatään tähän kenttään merkillä '-' erotettuna timestamp,
					// jotta saadaan uniikki EndToEndId. Tässä timestamp poistetaan.
					$dash_pos = strpos($sisviite, "-");

					if ($dash_pos !== FALSE) {
						$sisviite = substr($sisviite, 0, $dash_pos);
					}

					$sisviite = (int) $sisviite;
					break;

				// Pankin antamat lisätiedot
				case '07' :
					for ($i = 0; $i < 13; $i++) {
						$osa = trim(substr($arvo, $i*35, 35));
						if (strlen($osa) > 0) {
							$selite .= $osa."<br>\n";
						}
					}
					break;

				// Maksunaiheen tiedot
				case '08' :
					$selite = t("Maksuaihekoodi").": ".substr($arvo, 0, 3)."<br>\n";
					$selite .= "Selite: ".trim(substr($arvo, 4, 31));
					break;

				// Nimitarkenteen tiedot
				case '09' :
					$selite = t("Nimen tarkenne").": ".trim(substr($arvo, 0, 35));
					break;

				// Euromaksualueen tilisiirron lisätyyppi
				case '11' :
					$selite = "";

					if (trim(substr($arvo, 0,   35)) != "") $selite .= t("Maksajan viite").": ".		trim(substr($arvo, 0,   35))."<br>\n";
					if (trim(substr($arvo, 35,  35)) != "") $selite .= t("IBAN tilinumero").": ".		trim(substr($arvo, 35,  35))."<br>\n";
					if (trim(substr($arvo, 70,  35)) != "") $selite .= t("BIC-koodi").": ".				trim(substr($arvo, 70,  35))."<br>\n";
					if (trim(substr($arvo, 105, 70)) != "") $selite .= t("Saajan nimitarkenne").": ".	trim(substr($arvo, 105, 70))."<br>\n";
					if (trim(substr($arvo, 175, 70)) != "") $selite .= t("Maksajan nimitarkenne").": ".	trim(substr($arvo, 175, 70))."<br>\n";
					if (trim(substr($arvo, 245, 35)) != "") $selite .= t("Maksajan tunniste").": ".		trim(substr($arvo, 245, 35))."<br>\n";
					if (trim(substr($arvo, 280, 35)) != "") $selite .= t("SEPA Arkistointitunnus").": ".trim(substr($arvo, 280, 35))."<br>\n";
					break;

				// Oletus
				default :
					$selite = t("Tuntematon lisätieto")." '$tyyppi': $arvo";
					$eiselitetta = 1;
			}

			echo "<td name='td_{$td_perheid}' class='$class'>$selite</td><td name='td_{$td_perheid}' class='$class'></td><td name='td_{$td_perheid}' class='$class'></td><td name='td_{$td_perheid}' class='$class'></td><td class='back' style='width:200px;'></td></tr>";

			$vientiselite .= $selite;
			break;

		case 'T40':

			/*
			// Saldotietue (40)

			Kenttä     Tieto                PakollisuuMuoto Pituus  Sisältö
			1          Aineistotunnus       P         AN    1       T
			2          Tietuetunnus         P         AN    2       40
			3          Tietueen pituus      P         N     3
			4          Kirjauspäivä         P         N     6       VVKKPP
			5          Kirjauspäivän loppusaldo
			.1         Etumerkki            P         AN    1
			.2         Määrä                P         N     18      16 kok + 2 desi
			6          Käytettävissä oleva saldo
			.1         Etumerkki            P         AN    1
			.2         Määrä                P         N     18      16 kok + 2 desi
			YHTEENSÄ                                        50
			*/

			echo "<tr><td colspan='8' class='back' style='width:100%; padding:0; margin:0;'><div id = 'ifd_{$tiliotedatarow['tunnus']}' style='width:100%; border:1px solid; display:none'></div><br></td></tr>";

			echo "<tr>";

			if ($tee == 'S') echo "<td></td>";

			echo "<td></td><td></td><td></td><td></td><td>".t("Saldo")." ".pankkipvm(substr($tietue,6,6))."</td>";
			echo "<td style='text-align:right;'>". sprintf("%01.2f",((substr($tietue,12,19) / 100)))."</td><td class='back' style='width:200px;'></td></tr>";

			if ($tililimiitti != "") {
				echo "<tr>";

				if ($tee == 'S') echo "<td></td>";

				echo "<td></td><td></td><td></td><td></td><td>".t("Käyttövara")."</td>";
				echo "<td style='text-align:right;'>".sprintf("%01.2f",substr($tietue,31,19) / 100)."</td><td class='back' style='width:200px;'></td></tr>";
			}

			// Päivitetään saldo yriti-tauluun, jos sellainen löytyi
			if ($toim == 'K' and $tiliotedatarow['kasitelty'] == '0000-00-00') {

				$tilisaldo = substr($tietue,31,19);
				if ($valuuttarow['kurssi'] != 0 and $valuuttarow['kurssi'] != 1) {
					$tilisaldo = round($tilisaldo * $valuuttarow['kurssi'],2);
				}

				$query = "UPDATE yriti SET maksulimitti = '".round($tilisaldo / 100, 2)."' WHERE tunnus = '$yritirow[tunnus]'";
				$result = pupe_query($query);

				$query = "UPDATE tiliotedata SET kasitelty = now() WHERE tunnus='$tiliotedatarow[tunnus]'";
				$result = pupe_query($query);

				echo "<tr><td></td><td></td><td>";

				if ($tee == 'S') echo "</td><td>";

				echo "<font class='message'>".t("Tilin saldo päivitettiin")."</font>";
				echo "</td><td></td><td></td><td></td><td class='back' style='width:200px;'></td></tr>";
			}
			break;
		case 'T50':

			/*
			// Kumulatiivinen perustietue (50)

			Kenttä     Tieto             Pakollisuus Muoto PituusSisältö
			1          Aineistotunnus    P           AN    1     T
			2          Tietuetunnus      P           AN    2     50
			3          Tietueen pituus   P           N     3
			4          Jakson tunnus     P           AN    1     1, 2, 3, 4
			5          Jaksopäivä        P           N     6     VVKKPP
			6          Tapahtumat
			6.1        Panot
			6.1.1      Kappalemäärä      P           N     8
			6.1.2      Summa
			6.1.2.1    Etumerkki         P           AN    1
			6.1.2.2    Määrä             P           N     18    16 kok + 2 desi
			6.2        Otot
			6.2.1      Kappalemäärä      P           N     8
			6.2.2      Summa
			6.2.2.1    Etumerkki         P           AN    1
			6.2.2.2    Määrä             P           N     18    16 kok + 2 desi
			YHTEENSÄ                                       67
			*/

			if (substr($tietue,6,1) == 1) $jakso = "päivän";
			if (substr($tietue,6,1) == 2) $jakso = "tiliotteen";
			if (substr($tietue,6,1) == 3) $jakso = "kuukauden";
			if (substr($tietue,6,1) == 4) $jakso = "vuoden";

			if ($silent == "") {
				$colspan = $tee == 'S' ? 6 : 5;

				echo "<tr><th colspan='$colspan'>".t("Panot")." $jakso ".t("alusta")." ".t("kpl")." ".(substr($tietue,13,8) * 1)."</th><th style='text-align:right;'>".sprintf("%01.2f",substr($tietue,21,19) / 100)."</th><td class='back' style='width:200px;'></td></tr>";
				echo "<tr><th colspan='$colspan'>".t("Otot")." $jakso ".t("alusta")." kpl ".(substr($tietue,40,8) * 1)."</th><th style='text-align:right;'>".sprintf("%01.2f",substr($tietue,48,19) / 100)."</th><td class='back' style='width:200px;'></td></tr>";
			}
			break;
		case 'T60':
			/*
			// Erityistietue (60)
				Pankkikohtainen tieto!
			*/

			// Tässä kuvataan Nordea
			if (substr($tietue,6,3) == '200') {

				$colspan = $tee == 'S' ? 7 : 6;

				echo "<tr><td colspan='$colspan'>".t("Koronlaskentatiedot")."</td><td class='back' style='width:200px;'></td></tr>";
				echo "<tr><th colspan='$colspan'>".t("Korkokausi")." ".substr($tietue,11,13)."</th><td class='back' style='width:200px;'></td></tr>";

				if (substr($tietue,24,1) == '1') {
					echo "<tr><th colspan='colspan'>".t("Talletuksen keskisaldo")." ".(substr($tietue,25,1).substr($tietue,26,18) / 100)."</th><td class='back' style='width:200px;'></td></tr>";
				}
				if (substr($tietue,44,1) == '1') {
					echo "<tr><th colspan='$colspan'>".t("Talletuskorko")." ".(substr($tietue,45,7) / 1000)."</th><td class='back' style='width:200px;'></td></tr>";
				}
				if (substr($tietue,52,1) == '1') {
					echo "<tr><th colspan='$colspan'>".t("Luoton keskisaldo")." ".(substr($tietue,53,1).substr($tietue,54,18) / 100)."</th><td class='back' style='width:200px;'></td></tr>";
				}
				if (substr($tietue,72,1) == '1') {
					echo "<tr><th colspan='$colspan'>".t("Luottokorko")." ".(substr($tietue,73,7) / 1000)."</th><td class='back' style='width:200px;'></td></tr>";
				}
				if (substr($tietue,80,1) == '1') {
					echo "<tr><th colspan='$colspan'>".t("Limiitinkäyttöaste")." ".(substr($tietue,81,7) / 1000)."</th><td class='back' style='width:200px;'></td></tr>";
				}
				if (substr($tietue,88,1) == '1') {
					echo "<tr><th colspan='$colspan'>".t("Pysyväsaldo")." ".(substr($tietue,89,1).substr($tietue,90,18) / 100)."</th><td class='back' style='width:200px;'></td></tr>";
				}
				if (substr($tietue,108,1) == '1') {
					echo "<tr><th colspan='$colspan'>".t("Talletusviitekorko")." ".substr($tietue,109,35)." ".(substr($tietue,144,7) / 1000)."</th><td class='back' style='width:200px;'></td></tr>";
				}
				if (substr($tietue,151,1) == '1') {
					echo "<tr><th colspan='$colspan'>".t("Luottoviitekorko")." ".substr($tietue,152,35)." ".(substr($tietue,187,7) / 1000)."</th><td class='back' style='width:200px;'></td></tr>";
				}
			}
			else {
				echo "<tr><td colspan='$colspan'>";
				echo t("Tietue T60 ei ole tunnistettu pankkiryhmälle").": ". substr($tietue,6,3)."</td><td class='back' style='width:200px;'></td></tr>";
			}
			break;
		case 'T70':

			/*
			// Tiedotetietue (70)

			Kenttä     Tieto                   Pakollisuus Muoto Pituus  Sisältö
			1          Aineistotunnus          P           AN    1       T
			2          Tietuetunnus            P           AN    2       70
			3          Tietueen pituus         P           N     3
			4          Pankkiryhmän tunnus     P           AN    3
			5          Tiedote
			.1         Rivi-1                  P           AN    80
			.2         Rivi-2                              AN    80
			...
			.6         Rivi-6                              AN    80
			YHTEENSÄ                                             max 489

			*/

			if ($silent == "") { // Tietue sisältää tiliotteeseen liittyviä tiedotteita
				echo "<tr><td class='back'></td><td class='back' style='width:200px;'></td></tr>";

				echo "<tr>";

				if ($tee == 'S') echo "<td></td>";

				 echo "<td colspan='6'>". substr($tietue,9) ."</td>";
			}
			break;
		case '$$E':
			break;
		case 'EOF':
			break;
		default:

			/*
				Kumulatiivinen korjaustietue (51)
			*/
			if (trim($tunnus) != '') {

				$colspan = $tee == 'S' ? 7 : 6;

				echo "<tr><td colspan='$colspan'>";
				echo t("Tunnistamaton tietue").": $tunnus</td><td class='back' style='width:200px;'></td></tr>";
			}
	}
?>