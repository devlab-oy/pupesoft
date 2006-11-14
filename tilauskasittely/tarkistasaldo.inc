<?php

	// Sisään tultava $atil = tarvittava määrä, $tuoteno = tuotenumero, $akeraysaika = minkä hetken saldo halutaan

	// Jos $tuoteno on tyhjä sitä haetaan myös $trow[tuoteno]sta
	// Jos $akeraysaika on tyhjä sitä etsitään $laskurow[kerayspvm]stä. Jos ei löydy sieltäkään, oletetaan hetitoimitus

	// Palautetaan $trow missä on tuotteen tiedot, $toimitus ensimmäinen toimituspvm, $varaosavirhe tuotteen tilanne tekstinä
	// $ahyvaksy sisältää mitä käyttäjän olisi hyvä tehdä (P=puute, J=jälkitoimitus)
	// $varastossa sisältää toimitukseen kelpaavan määrän, jos toimittajan toimitukset eivät toteudu
	// $saldo on toimitettavissa oleva saldo rivin toimituksen jälkeen
	// $tee = "Y", jos tavaraa ei löydy, muuten $tee muuttujaan ei kosketa

	// Jos $debug on 1 tulostetaan kaikki muuttujat

	$saldo 			= array(); // Kunkin varastopaikan vapaa saldo
	$varastossa 	= array(); // Myytävissä ilman sisääntuloja
	$varastosaldo	= array(); // The virallinen kirjanpidollinen saldo
	$hyllyalue 		= array(); // Paikka
	$hyllynro		= array();
	$hyllyvali 		= array();
	$hyllytaso 		= array();
	$toimitus 		= 0;	// Ensimmäinen toimituspvm
	$oletuspaikka 	= 0;	// Indeksi mikä paikoista on oletuspaikka
	$loytyy 		= 0;	// Onko rivi toimitettavissa (=1)
	$ahyvaksy 		= '';	// Mitä kannattaa tehdä (J=jälkitoimitus, P=puute)
	$varasto 		= 0;	// mistä varastosta tavaraa löytyy
	$paikattext		= "";	//voidaan sopia rivin slittaamisesta usealle paikalle heti ekalla kierroksella
	
	$i 		= 1;
	$debug 	= 0;

	if ($atil != 0) {

		if($akeraysaika == '') {
			$akeraysaika = $laskurow["kerayspvm"];
		}
		if($akeraysaika == '') {
			$akeraysaika = date('Y-m-d');
		}

		if ($tuoteno == '') $tuoteno = $trow['tuoteno'];

		if (!is_array($trow)) { // Etsitään tuotteen tiedot
			$query = "	SELECT *
						FROM tuote
						WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$tuoteno'";
			$qwresult = mysql_query($query) or pupe_error($query);
			$trow=mysql_fetch_array($qwresult);
		}
		
		$query = "	SELECT *, varastopaikat.tunnus varasto, tuotepaikat.tunnus tuotepaikka
					FROM tuotepaikat
					LEFT JOIN varastopaikat
					ON varastopaikat.yhtio = tuotepaikat.yhtio
					and concat(lpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(lpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
					and concat(lpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(lpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
					WHERE tuotepaikat.tuoteno = '$tuoteno'
					and tuotepaikat.yhtio = '$kukarow[yhtio]'";
		$qvresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($qvresult) == 0) {
			if ($kukarow['extranet'] == '') {
				$varaosavirhe .= "".t("Tuotteella ei ole yhtään varastopaikkaa")."!<br>";
			}
			else {
				$varaosavirhe .= "".t("Tuottetta ei ole varastossa")."!<br>";
			}
			$loytyy 	= 0;
			$varasto 	= 0;
			$ahyvaksy 	= 'XXX';
		}
		else {
			while ($zrow = mysql_fetch_array($qvresult)) { // Käydään kaikki varastopaikat läpi

				///* Lasketaan vain normaalivarastojen saldoja, paitsi jos erikoisvarastokäsittely on triggeröity *///
				if ($zrow["tyyppi"] == '' or ($laskurow['varasto'] == $zrow["varasto"]) or ($naytakaikkipaikat == "ON")) {
					// Varatut
					$query = "	SELECT sum(varattu)
								FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
								WHERE yhtio = '$kukarow[yhtio]'
								and tuoteno = '$tuoteno'
								and varattu > 0
								and tyyppi in ('L','G','V')
								and hyllyalue = '$zrow[hyllyalue]' and hyllynro = '$zrow[hyllynro]' and hyllyvali = '$zrow[hyllyvali]' and hyllytaso = '$zrow[hyllytaso]'";
					$qwresult = mysql_query($query) or pupe_error($query);
					$vrow=mysql_fetch_array($qwresult);

					if ($zrow['oletus'] != '') {
						// Tilauksessa olevat
						$query = "	SELECT sum(varattu), min(toimaika)
									FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
									WHERE yhtio = '$kukarow[yhtio]' 
									and tuoteno = '$tuoteno' 
									and toimaika <= '$akeraysaika' 
									and tyyppi = 'O' 
									and varattu > 0";
						$qwresult = mysql_query($query) or pupe_error($query);
						$xrow=mysql_fetch_array($qwresult);

						// Ensimmaisen saapuvan eran kappalemaara, erä voi olla tulevaisuudessa tai menneisyydessä
						$query = "	SELECT min(toimaika), varattu
									FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
									WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$tuoteno' and tyyppi = 'O' and varattu > 0 
									group by toimaika";
						$qwresult = mysql_query($query) or pupe_error($query);
						$irow=mysql_fetch_array($qwresult);
					}
					else {
						$xrow = array();
						$irow = array();
					}

					// Muutetaan numeroiksi
					$vrow[0] += 0;
					$xrow[0] += 0;
					$zrow['saldo'] += 0;

					$hyllyalue[$i] 		= $zrow['hyllyalue'];
					$hyllynro[$i]  		= $zrow['hyllynro'];
					$hyllyvali[$i] 		= $zrow['hyllyvali'];
					$hyllytaso[$i] 		= $zrow['hyllytaso'];
					$hyllytunnus[$i]	= $zrow['tuotepaikka'];
					$varastotunnus[$i]	= $zrow['varasto'];

					
					// Vapaa saldo on nyky saldo - vanhat varatut - uudet varatut + tilatut, jotkatulevat ennen toimitusta - tilattu määrä
					//HUOM! Tätä funtsimme vielä, eli xrow nollaa. pitääkö vanhentuneet tilauksessa olevat rivit huomioda myytävässä saldossa
					//HUOM2! xrow nolla otettiin nyt pois käytöstä
					$saldo[$i] = $zrow['saldo'] - $vrow[0] - $atil;
					// Tämä saldo löytyy vaikka toimittaja pettää
					$varastossa[$i] = $zrow['saldo'] - $vrow[0];
					//tämä on siis kirjanpidollinen varastosaldo eli select saldo from tuotepaikat
					$varastosaldo[$i] = $zrow['saldo'];
					
					if ($saldo[$i] < 0 and $atil > 0) {
						if ($kukarow["extranet"] == '' and $eikayttajaa != 'ON') {
							$varaosavirhe .= t("Saldo ei riitä")."! ";

							if($zrow['oletus'] != '') $varaosavirhe .= t("Oletuspaikalla")."";
							else $varaosavirhe .= t("Paikalla");

							$varaosavirhe .= ": $hyllyalue[$i] $hyllynro[$i] $hyllyvali[$i] $hyllytaso[$i]: $varastossa[$i] $trow[yksikko]<br>";
						}
						else {
							//$varaosavirhe .= "VIRHE: Saldo ei riitä. Paikalla on $varastossa[$i] kappaletta.<br>";
							$varaosavirhe .= "";
						}


						if (($irow[0] != 0) and ($zrow['oletus'] != '') and ($eikayttajaa != 'ON')) {
							$varaosavirhe .= t("Saapuu")." $irow[0]: $irow[1] $trow[yksikko].<br>";
							$ahyvaksy ='J';
						}
						//eka pvm ku tuotetta tulee lisää
						$toimitus = $irow["toimaika"];
					}
					else {
						$kalavarasto = $i;
						$loytyy++;
					}

					if ($zrow['oletus'] != '') {
						$oletuspaikka = $i;
					}


					///* Jos kutsutaan käyttöliittymättömästä liittymästä niin hyvitysrivit viedään aina oletuspaikalle *///
					if ($atil < 0 and $oletuspaikka != '' and $eikayttajaa == 'ON') {
						$varasto = $oletuspaikka;
						$loytyy = 1;
					}


					///* Jos kutsutaan käyttöliittymättömästä liittymästä silloin tehään tää *///
					if ($eikayttajaa == 'ON' and $loytyy > 1) {
						//myydään siis viimeiseksi löytyvältä sopivalta varastopaikalta
						$varasto = $kalavarasto;
						$loytyy = 1;
					}

					if ($debug == 1) echo t("Saldotark")." $tuoteno ".t("Tilataan")." $atil ".t("Saldo")." $zrow[saldo] ".t("Tilattu")." $xrow[0] ".t("Ennpois")." " . ($vrow[0]) ." ".t("Oletus")." '$zrow[oletus]'<br>";

					//Tätä litaniaa tarvitaan mikrotilauksessa
					$paikattext .= "?$hyllyalue[$i]#$hyllynro[$i]#$hyllyvali[$i]#$hyllytaso[$i]";

					$i++;
				}
			}
			
			//tämä tehdään vain jos on joku tilaus kyseessä, ei esim varastopaikkojen muutos
			if ($tilausnumero != 0 and $laskurow["tila"] != 'G' and $varataan != 'EI') {
				//tsekataan jtssä olevat $vrsum on myytäsvissä oleva määrä $jtsum on jtssä oleva määrä
				$query = "	SELECT sum(jt) jt, lasku.ytunnus ytunnus
							FROM tilausrivi use index (yhtio_tyyppi_var_keratty_kerattyaika_uusiotunnus), lasku use index (PRIMARY)
							WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
							and tilausrivi.tuoteno = '$trow[tuoteno]'
							and lasku.yhtio=tilausrivi.yhtio
							and tilausrivi.otunnus=lasku.tunnus
							and tilausrivi.varattu=0
							and tilausrivi.kpl=0
							and tilausrivi.var='J'
							and tilausrivi.jt!=0
							and tilausrivi.tyyppi='L'
							GROUP BY lasku.ytunnus";
				$jsres  = mysql_query($query) or pupe_error($query);
				
				$jtssa = "";
				$jtsum = 0;
				$vrsum = 0;
							
				foreach($varastossa as $vars) {
					$vrsum += $vars;
				}
							
				while($jsrow  = mysql_fetch_array($jsres)) {	
					$jtssa .= t("HUOM: Asiakkaalle")." $jsrow[ytunnus] ".t("on ennaltavarattu")." $jsrow[jt] ".t("kpl tätä tuotetta")."!<br>";				
					$jtsum += $jsrow["jt"];
				}
				
				//katotaan pitääky jterrori triggeröidä vai hylätä
				if ($atil < ($vrsum - $jtsum)) {
					$jtssa = "";
					$jtsum = 0;
				}
			}
			
			
		}
		// Ei missään varastossa eikä tilauksessa --> puute, paitsi jos varataan flägi on triggeröity --> varastosiirtojuttuja
		if ($ahyvaksy == '' and $loytyy == 0 and $varataan != 'EI') {
			$varaosavirhe .= t("Saldo ei riitä, tilauksessakaan ei ole tavaraa").".<br>";
			$ahyvaksy = 'P';
		}

		if ($debug == 1) echo t("Oletuspaikka").": '$hyllyalue[$oletuspaikka]' '$hyllynro[$oletuspaikka]' '$hyllyvali[$oletuspaikka]' '$hyllytaso[$oletuspaikka]' ".t("Oletuspaikan indeksi")." $oletuspaikka ".t("Hyväksy")." '$hyvaksy'<br>";
	}
	else {
		$varaosavirhe .= t("VIRHE: Syötä tilattava määrä")."!<br>";
	}
?>
