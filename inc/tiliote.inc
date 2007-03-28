<?php

// Karsea ongelma polkujen kanssa
	$polku='';
	if (strpos($_SERVER['SCRIPT_NAME'], "myyntires")  !== FALSE) $polku='../';
	
	$tunnus = substr($tietue, 0,3);
	
	if ($tunnus != 'T11' and $maksu == 1) {
		echo "<tr><td></td><td></td><td>";
		
		// Tämä on case Sampo ja erittelyt onkin ilmoitettu tasoissa!
		// eli jos valmista maksua seuraava tapahuma on suurempitasoinen T10 ei tätä edeltävää maksua käsitellä
		if (substr($tietue, 187, 1) > $omataso and $tunnus == 'T10') { 
			$kuittikoodi='E';
		}
		// Jossain erityistapauksissa täytyy erittely käsitellä. Niille löytyy syötetyt säännöt
		if ($kuittikoodi == 'E') {
			require $polku. "inc/kasitteleerittelysaanto.inc";
		}
			
		if ($toim == 'K' and $kuittikoodi == ' ' and $tiliotedataka == '0000-00-00') {
			//Muutetaan maara kotivaluuttaan
			if ($valuuttarow['kurssi'] != 0 and $valuuttarow['kurssi'] != 1)
				$maara = round($maara * $valuuttarow['kurssi'],2);
				
			if ($sisviite != 0) { //Tämän pitiäisi toimia nyt aina!
				echo "<font class='message'> ".t("maksukanditaatti sisäinen viite & määrä")."</font><br>";
				
				$query = "	SELECT *, round(summa * vienti_kurssi, 2) 'vietysumma', round(kasumma * vienti_kurssi, 2) 'vietykasumma'
							FROM lasku
							WHERE tunnus = '$sisviite' and ((summa = $maara * -1) or (abs(summa + $maara - kasumma) < 0.01))  and yhtio = '$yritirow[yhtio]' and tila = 'Q'";
				$result = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($result) == 0) {
					echo "<font class='message'>".t("Sopivaa maksua")." $sisviite & $maara ".t("ei löytynyt")."!</font><br>";
					echo "<font class='message'>".t("Tiliöidään tiliotesääntöjen mukaan")."</font><br>";
					require $polku . "inc/teeselvittely.inc";
				}
				else {
					$trow = mysql_fetch_array ($result);
					echo "<font class='message'>".t("Maksu löytyi")." $trow[tunnus]</font><br>";
					require $polku . "inc/teemaksutosite.inc";
				}
			}
			else {
				switch ($koodi) {
				case '702' : // Maksettu jotain tililtä, etsitään tilinolla ja summalla....
					if (strlen(trim($tilino)) > 0) {
						echo "<font class='message'> ".t("maksukanditaatti tilino & määrä")."</font><br>";
						
						$query = "	SELECT *, round(summa * vienti_kurssi, 2) 'vietysumma', round(kasumma * vienti_kurssi, 2) 'vietykasumma'
									FROM lasku
									WHERE tilinumero = '$tilino' and ((summa = $maara * -1) or (abs(summa + $maara - kasumma) < 0.01)) and yhtio = '$yritirow[yhtio]' and tila = 'Q'";
						$result = mysql_query($query) or pupe_error($query);
	
						if (mysql_num_rows($result) == 0) {
							echo "<font class='message'>".t("Sopivaa maksua")." $tilino & $maara ".t("ei löytynyt")."!</font><br>";
							echo "<font class='message'>".t("Tiliöidään tiliotesääntöjen mukaan")."</font><br>";
							require $polku . "inc/teeselvittely.inc";
						}
						else {
							$trow = mysql_fetch_array ($result);
							echo "<font class='message'>".t("Maksu löytyi")." $trow[tunnus]</font><br>";
							require $polku . "inc/teemaksutosite.inc";
						}
					}
					else {
							echo "<font class='message'>".t("Meillä ei ole tietoa millä tämä tapahtuma voidaan kohdistaa")."</font><br>";
							echo "<font class='message'>".t("Tiliöidään tiliotesääntöjen mukaan")."</font><br>";
							require $polku . "inc/teeselvittely.inc";
					}
					$koodi = "";
					break;
				case '720' : // Maksumääräys maksettu jotain tililtä, etsitään nimellä ja summalla....
					if (strlen(trim($maksaa)) > 0) {
						echo "<font class='message'>".t("Maksukanditaatti nimi & määrä")."</font><br>";
	
						$query = "	SELECT *, round(summa * vienti_kurssi, 2) 'vietysumma', round(kasumma * vienti_kurssi, 2) 'vietykasumma'
									FROM lasku
									WHERE left(nimi,35) = left('$maksaa',35) and ((summa = $maara * -1) or (abs(summa + $maara - kasumma) < 0.01)) and yhtio = '$yritirow[yhtio]' and tila = 'Q'";
	
						$result = mysql_query($query) or pupe_error($query);
	
						if (mysql_num_rows($result) == 0) {
							echo "<font class='message'>".t("Sopivaa maksua")." $maksaa & $maara ".t("suoraan ei löytynyt")."</font><br>";
	
							//Poistetaan Oyt ja Abt ja katsotaan miten sitten käy....
							$maksaa = trim(preg_replace('/\b(oy|ab)\b/i', '', strtolower($maksaa)));
							$query = "	SELECT *, round(summa * vienti_kurssi, 2) 'vietysumma', round(kasumma * vienti_kurssi, 2) 'vietykasumma'
										FROM lasku
										WHERE left(nimi,35) LIKE '%$maksaa%' and ((summa = $maara * -1) or (abs(summa + $maara - kasumma) < 0.01)) and yhtio = '$yritirow[yhtio]' and tila = 'Q'";
							$result = mysql_query($query) or pupe_error($query);
	
							if (mysql_num_rows($result) == 0) {
								echo "<font class='message'>".t("Sopivaa maksua")." $maksaa & $maara ".t("vieläkään ei löytynyt")."</font><br>";					
								echo "<font class='message'>".t("Tiliöidään tiliotesääntöjen mukaan")."</font><br>";
								
								require $polku . "inc/teeselvittely.inc";
							}
							else {
								$trow = mysql_fetch_array ($result);
								echo "<font class='message'>".t("Maksu löytyi")." $trow[tunnus]</font><br>";
								
								require $polku . "inc/teemaksutosite.inc";
							}						
						}
						else {
							$trow = mysql_fetch_array ($result);
							echo "<font class='message'>".t("Maksu löytyi")." $trow[tunnus]</font><br>";
							
							require $polku . "inc/teemaksutosite.inc";
						}
					}
					else {
						echo "<font class='message'>".t("Meillä ei ole tietoa millä tämä tapahtuma voidaan kohdistaa")."</font><br>";
						echo "<font class='message'>".t("Tiliöidään tiliotesääntöjen mukaan")."</font><br>";
						require $polku . "inc/teeselvittely.inc";
					}
					$koodi = "";
					break;
				case '730' : // Pankin kulu
					echo "<font class='message'>".t("Maksukanditaatti pankinkulu")."</font><br>";
					echo "<font class='message'>".t("Rahat")." $yritirow[oletus_rahatili] ".t("kulut")." $yritirow[oletus_kulutili] ".t("summa")." $maara</font><br>";
					
					require $polku . "inc/teekulutosite.inc";
					break;
				default :
					echo "<font class='message'>".t("Tiliöidään tiliotesääntöjen mukaan")."</font><br>";
					
					require $polku . "inc/teeselvittely.inc";
				}
			}
		}
		else { // Miksi ei tiliöity?
			if ($kuittikoodi != ' ') echo "<font class='message'>".t("Hylätty, koska erittely tulossa")." ($kuittikoodi)</font><br>";
			if ($toim != 'K') echo "<font class='message'>".t("Tili, raha- tai kulutili puuttuu")."!</font><br>";
			if ($tiliotedataka != '0000-00-00') {
				$query = "	SELECT tiliointi.tilino tilino, tili.nimi nimi, concat_ws('@',korjattu, korjausaika) korjattu, ltunnus
							FROM tiliointi
							LEFT JOIN tili on tili.yhtio=tiliointi.yhtio and tili.tilino=tiliointi.tilino
							WHERE tiliointi.tunnus = '$tiliotedatatu'";
				$tiliointiresult = mysql_query($query) or pupe_error($query);
				$tiliointirow = mysql_fetch_array ($tiliointiresult);
				
				echo "<font class='message'>".t("Tapahtuma on käsitelty")." $tiliotedataka<br>";
				echo "".t("Alkuperäinen tiliöinti on")." <a href='".$polku."muutosite.php?tee=E&tunnus=$tiliointirow[ltunnus]'>$tiliointirow[tilino]/$tiliointirow[nimi]</a></font><br>";
				
				if (($tiliointirow['korjattu'] != "0000-00-00 00:00:00") and ($tiliointirow['korjattu'] != "@0000-00-00 00:00:00")) {
					echo "<font class='message'>".t("Tiliöinti korjattu")." $tiliointirow[korjattu]</font>";
				}
				else {
					echo "</font>";
				}	
			}
		}
		
		echo "<td></td><td>";
		
		//Jos tämä on selvittelyssä, niin annetaan mahdollisuus suorittaa!
		if ((($tiliointirow['korjattu'] == "0000-00-00 00:00:00") or ($tiliointirow['korjattu'] == "@0000-00-00 00:00:00")) and ($tiliointirow['tilino'] == $yhtiorow['selvittelytili']) and ($kuittikoodi==' ')) {
			//Olisiko tämä meidän toimittaja
			$tpv = substr($pvm,0,2);
			$tpk = substr($pvm,2,2);
			$tpp = substr($pvm,4,2);
				
			$query = "SELECT ytunnus FROM toimi WHERE yhtio = '$yritirow[yhtio]' and left(nimi,35) LIKE '%$maksaa%'";
			$suotoiresult = mysql_query($query) or pupe_error($query);
			
			if (mysql_num_rows($suotoiresult) > 0) {
				echo "<font class='message'><a href='".$polku."suori.php?tee=W&tiliote=Z&mav=$tpv&mak=$tpk&map=$tpp&mtili=$yritirow[tunnus]&tiliotesumma=". -1*$maara . "'>".t("Ostosuoritus")."</a></font><br>";
			}
			
			//Olisiko tämä meidän asiakas
			$query = "SELECT * FROM asiakas WHERE yhtio = '$yritirow[yhtio]' and left(nimi,35) LIKE '%$maksaa%'";
			$suoasiresult = mysql_query($query) or pupe_error($query);
			
			if (mysql_num_rows($suoasiresult) > 0) {
				$suoasirow = mysql_fetch_array ($suoasiresult);
				echo "<font class='message'><a href='".$polku."myyntires/maksunsyotto.php?asiakasid=$suoasirow[tunnus]&ytunnus=$suoasirow[ytunnus]&summa=$maara&ppa=$tpp&kka=$tpk&vva=$tpv&mtili=$yritirow[tunnus]&tiliote=Z'>".t("Myyntisuoritus")."</a></font>";
			}
		}
		echo "</td><td>";
		
		if ($kuittikoodi==' ') {
			if ($eiselitetta==1) $selite='';
			echo "<font class='message'><a href='".$polku."tiliotesaannot.php?tee=U&pankkitili=$yritirow[tilino]&koodi=$koodi&koodiselite=$koodiselite&nimitieto=$maksaa&selite=$selite&pvm=$pvm'>".t("Tee tiliointisääntö")."</a></font>";
		}
		
		echo "</td></tr>";
		$maksu = 0; // Maksu on käsitelty
		$maara = 0;
		$eiselitetta = 0;
	}

	switch ($tunnus) {
		case 'T00' :
			if ($tkesken == 1) { // Meillä on entinen tili auki
				// Tehdään vastakirjaus
				$maara = $vastavienti;
				$tkesken = 0;
				echo "</table><br><br><table>";
				echo "<tr><td colspan = '6'>";
				require $polku . "inc/teeselvittely.inc";
				echo "</td></tr>";
				echo "</table><br><br><table>";
			}
			else {
				echo "<table>";
				echo "<tr><td></td><td></td><td><a href='$PHP_SELF?tilino=$tiliotedatarow[tilino]&pvm=$tiliotedatarow[alku]&tee=X'>".t("Seuraava")." &gt&gt</a></td></tr>";
			}
			$laskuid = 0;
			$tkesken = 1;
			$maara = 0;
			$pankkitilino = substr($tietue, 9, 14);
			echo "<tr>";
			echo "<td>" . substr($tietue,182,40) . "</td>";
			echo "<td>".t("Tiliote")." " . substr($tietue,23,3) . "</td><td></td>";
			echo "</tr>";

			echo "<tr>";
			echo "<td rowspan='2'>". substr($tietue,222,40) . "<br>". substr($tietue,262,30) ."<br>". substr($tietue,292,30) ."</td>";
			echo "<td></td>";
			echo "<td>".t("Muodostettu")."<br>". substr($tietue,38,10) . "</td>";
			echo "</tr>";

			echo "<tr>";
			echo "<td>".t("Tulkittu asiakkaalla")."</td>";
			$alkupvm = substr($tietue,26,6);
			$loppupvm = substr($tietue,32,6);
			echo "<td>".t("Kausi")."<br> $alkupvm - $loppupvm</td>";
			echo "</tr>";

			$tilinvaluutta = substr($tietue,96,3);
			echo "<tr>";
			echo "<td>" . substr($tietue,147,18) . "</td>";
			echo "<td>" . substr($tietue,99,30) . "<br>$tilinvaluutta $pankkitilino</td>";
			echo "<td>".t("Myönnetty määrä")."<br>". substr($tietue, 117, 30) * 1 . "</td>";
			echo "</tr>";

			$query = "	SELECT *
						FROM yriti
						WHERE tilino = '$pankkitilino'";
			$result = mysql_query($query) or pupe_error($query);

			echo "<tr><td colspan='3'>";

			if (mysql_num_rows($result) != 1) {
				echo "<font class='error'> ".t("Tiliä")." '$pankkitilino' ".t("ei löytynyt")."!</font><br>";
				$toim='E';
			}
			else {
				$yritirow = mysql_fetch_array($result);
				echo "<font class='message'> ".t("tili löytyi yrityksestä")." '$yritirow[yhtio]' ".t("vastuussa")." '$yritirow[hyvak]'</font><br>";

				if ((strlen($yritirow['oletus_rahatili']) == 0) or (strlen($yritirow['oletus_kulutili']) == 0)) { // Tarkistetaan tilit
					echo "<font class='error'> ".t("Tililtä puuttuu kulu tai rahatili")."</font>";
					$toim='E';
				}
				else {
					echo "<font class='message'> ".t("Tilin rahatili on")." '$yritirow[oletus_rahatili]' ".t("ja kulutili")." '$yritirow[oletus_kulutili]'</font><br>";
				}

				$query = "	SELECT *
							FROM yhtio
							WHERE yhtio = '$yritirow[yhtio]'";
				$result = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($result) != 1) {
					echo "<font class='error'> ".t("Yritystä")." '$yritirow[yhtio]' ".t("ei löytynyt")."!</font><br>";
					$toim='E';
				}
				else {
					$yhtiorow = mysql_fetch_array($result);
					echo "<font class='message'> ".t("Yritys löytyi")." '$yhtiorow[nimi]'<br>".t("ostovelat")." '$yhtiorow[ostovelat]' ".t("konserniostovelat")." '$yhtiorow[konserniostovelat]'</font><br>";
					
					$toim="K";
				}

				$query = "	SELECT *
							FROM valuu
							WHERE yhtio = '$yritirow[yhtio]' and nimi = '$tilinvaluutta'";
				$result = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($result) != 1) {
					echo "<font class='error'> ".t("Valuuttaa")." '$tilinvaluutta' ".t("ei löytynyt")."!</font><br>";
					$toim='E';
				}
				else {
					$valuuttarow = mysql_fetch_array($result);
					if ($valuuttarow['kurssi'] != 1) {
						echo "<font class='message'> ".t("Tiliote kirjataan kurssiin")." ".(round(1/$valuuttarow['kurssi'],6))." ($valuuttarow[kurssi])!</font><br>";
					}
					$toim="K";
				}
			}

			echo "</td></tr></table>"; // Tämä päättää otsikon

			echo "<table>"; // Aloitetaan tapahtumat

			echo "<tr>";
			echo "<td>".t("Arkistointitunnus")."<br>".t("Saajan tilinumero")."</td>";
			echo "<td>".t("Maksup.")."<br>".t("Arvop.")."</td>";
			echo "<td>".t("Saaja/Maksaja")."<br>".t("Viesti")."</td>";
			echo "<td>".t("Tap.")."<br>".t("n:o")."</td>";
			echo "<td>".t("Tiliöinti")."</td>";
			echo "<td>".t("Määrä")."</td>";
			echo "</tr>";

			echo "<tr><td></td><td></td><td></td><td></td>";
			echo "<td>".t("Saldo")." " . substr($tietue,65,6) . "</td>";
			echo "<td align='right'>" . substr($tietue,71,19) / 100 . "</td>";
			echo "</tr>";

			break;

		case 'T10' :
			$maksu = 1;
			$sisviite = 0; // Ettei jää mitään kauhupointteria kummittelemaan....
			$tiliotedataid = $tiliotedatarow['tunnus']; 
			$tiliotedataka = $tiliotedatarow['kasitelty'];
			$tiliotedatatu = $tiliotedatarow['tiliointitunnus'];
			
			$koodi = substr($tietue, 49, 3);
			// Sampo häkki
			if ($koodi == '700') {
				$koodi = '702';
			}
			// Nordea limiittikorko häkki (Hoidetaan tiliöintisäännöllä)
			//if ($koodi == '740') {
			//	$koodi = '730';
			//}
			// Suoraveloitukset kohdistetaan kuin maksumääräykset
			if ($koodi == '704') {
				$koodi = '720';
			}
			$kirjpvm = substr($tietue, 30, 6);
			$pvm = substr($tietue, 36, 6);
			//Tilioidaan kuitenkin loppupeleissa kaikki kirjauspvm:lle.
			
			$pvm=$kirjpvm;

			if ($pvm == "000000") { // Haa, pankki ei antanutkaan arvopvmää, joten otetaan käyttään tapahtumapvm
				$pvm = $kirjpvm;
			}
			
			//echo "Tunnus: '" .  substr($tietue, 48, 1) . "'<br>";
			$etumerkki = substr($tietue, 87, 1);
			$maara = substr($tietue, 88, 18) / 100;
			if ($etumerkki == '-') {
				$maara *= -1;
			}
			$kuittikoodi = substr($tietue,106,1);
			$maksaa = trim(substr($tietue, 108, 35));
			$tilino = substr($tietue, 144, 14);
			$omataso = substr($tietue, 187, 1);
			$koodiselite = trim(substr($tietue, 52, 35));
			echo "<tr>";
			echo "<td>" . substr($tietue,12,18) . "</td>";
			echo "<td>$kirjpvm</td>";
			echo "<td>$maksaa</td>";
			echo "<td>" . substr($tietue, 6, 6) * 1 . "$kuittikoodi $omataso</td>";
			echo "<td></td>";
			echo "<td align='right'>".sprintf('%.2f',$maara) ."</td>";
			echo "</tr><tr>";
			echo "<td>$tilino</td>";
			echo "<td>$pvm</td>";
			echo "<td>$koodi $koodiselite</td>";
			echo "<td></td><td></td><td></td>";
			echo "</tr>";
			$vientiselite = "$maksaa<br>$koodi $koodiselite<br>";
			break;

		case 'T11' :
			$selite = '';
			$tyyppi = substr($tietue, 6, 2);
			$arvo =  substr($tietue,8,substr($tietue, 3, 3));
			echo "<tr><td></td><td></td>";
			switch ($tyyppi) {
			case '00' :
					$selite = "";
					$luottokuntatieto = "";
					for ($i = 0; $i < 13; $i++) {
						$osa = trim(substr($arvo, $i*35, 35));
						if (strlen($osa) > 0) {
							$selite .= $osa . "<br>";
						}
						// Luottokunnan käsittelyä varten
						if ($i == 1) $luottokuntatieto = $osa;
					}
					break;
			case '01' :
					$selite = "".t("tapahtumia").": " . substr($arvo, 0, 8) * 1;
					//Tällä taklataan Aktian virhettä, jossa tuo E puuttuu aineistosta
					if ((substr($pankkitilino,0,1) == '4') and ($kuittikoodi == ' ')) $kuittikoodi='Z';
					break;
			case'05' :
					$selite = "".t("vasta-arvo").": " .  substr($arvo, 0, 19) * 1 .  "<br>";
					$selite .= "".t("valuutta").": " .  substr($arvo, 20, 3) .  "<br>";
					$selite .= "".t("kurssi").": " .  substr($arvo, 24, 11) * 1;
					break;
			case '06' :
					$selite ="".t("Oma sisäinen viite").":";
					$selite .= trim(substr($arvo, 0, 35)) . "<br>";
					$selite .= trim(substr($arvo, 35, 35));
					$sisviite=trim(substr($arvo, 0, 35))*1;
					//Tämä on (kai) ongelma Analysten pankkisoftassa. Lisäävät joskus oman tunnuksen
					//ekaan sisäinenviite kenttään, silloin meidän onkin toisessa.
					if ((trim(substr($arvo, 35, 35))*1 != 0) and (trim(substr($arvo, 0, 35))*1 != 0))
							$sisviite = trim(substr($arvo, 35, 35))*1;
					break;
			case '07' :
					for ($i = 0; $i < 13; $i++) {
						$osa = trim(substr($arvo, $i*35, 35));
						if (strlen($osa) > 0) {
							$selite .= $osa . "<br>";
						}
					}
			case '08' :
					$selite = "".t("maksuaihekoodi").": " . substr($arvo, 0, 3) . "<br>";
					$selite .= "selite: " . trim(substr($arvo, 4, 31));
					break;
			case '09' :
					$selite = "".t("nimen tarkenne").": " .  trim(substr($arvo, 0, 35));
					break;
			default :
				$selite = "<font class='error'>*** ".t("OHITETTU LISÄTIETO")." ***</font>";
				$eiselitetta = 1;
			}
			echo "<td>$selite</td><td></td><td></td><td></td></tr>";
			$vientiselite .= $selite;
			break;
		case 'T40':
			echo "<tr><td></td><td></td><td></td><td></td><td>".t("Saldo")." " . substr($tietue,6,6) . "</td>";
			echo "<td>" . substr($tietue,31,19) / 100 . "</td></tr>";
			
			// Päivitetään saldo yriti-tauluun, jos sellainen löytyi
			if ($toim == 'K' and $tiliotedatarow['kasitelty'] == '0000-00-00') {

				if ($valuuttarow['kurssi'] != 0 and $valuuttarow['kurssi'] != 1) {
					$tilisaldo = substr($tietue,31,19);
					$tilisaldo = round($tilisaldo * $valuuttarow['kurssi'],2);
				}

				$query = "UPDATE yriti SET maksulimitti = '" . round($tilisaldo / 100, 2) . "' WHERE tunnus = '$yritirow[tunnus]'";
				$result = mysql_query($query) or pupe_error($query);
				
				$query = "UPDATE tiliotedata SET kasitelty = now() WHERE tunnus='$tiliotedatarow[tunnus]'";
				$result = mysql_query($query) or pupe_error($query);
				
				echo "<tr><td></td><td></td><td>";
				echo "<font class='message'>".t("Tilin saldo päivitettiin")."</font>";
				echo "</td><td></td><td></td><td></td></tr>";
			}
			break;
		case 'T50':
			if (substr($tietue,6,1) == 1) $jakso = "päivän";
			if (substr($tietue,6,1) == 2) $jakso = "tiliotteen";
			if (substr($tietue,6,1) == 3) $jakso = "kuukauden";
			if (substr($tietue,6,1) == 4) $jakso = "vuoden";
			echo "<tr><td colspan='3'>";
			echo "".t("Panot")." $jakso ".t("alusta")." ".t("kpl")." " . substr($tietue,13,8) * 1 . "</td>";
			echo "<td></td><td></td><td align='right'>" . substr($tietue,21,19) / 100 . "</td></tr>";
			echo "<tr><td colspan='3'>";
			echo "".t("Otot")." $jakso ".t("alusta")." kpl " . substr($tietue,40,8) * 1 . "</td>";
			echo "<td></td><td></td><td align='right'>" . substr($tietue,48,19) / 100 . "</td></tr>";
			break;
		default:
			if (trim($tunnus) != '') {
				echo "<tr><td colspan='6'>";
				echo t("Tunnistamaton tietue").": $tunnus</td></tr>";
			}
	}
?>
