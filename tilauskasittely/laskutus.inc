<?php

// tarvitaan $kukarow['kesken'] jossa on laskutettava tilaus
// ja $laskpp $laskkk $laskvv jos halutaan määritellä poikkeava laskutuspvm

$tulos_ulos_laskutus = "";

// jos erisuuri kun nollaa tai tyhjää niin käytetään laskutuspvm:ää
if ($poikkeava_pvm != '') {
	//poikkeava
	$laskutuspvm = $laskvv."-".$laskkk."-".$laskpp;
}
else {
	// today
	$laskutuspvm = date("Y-n-j");
}

// Etsitään lasku
$query = "	SELECT *
			FROM lasku
			WHERE tunnus = '$kukarow[kesken]' and yhtio='$kukarow[yhtio]' and tila='L' and alatila!='V' and alatila!='X'";
$laskutusres = mysql_query($query) or pupe_error($query);

if (mysql_num_rows($laskutusres) == 0) {
	$tulos_ulos_laskutus .=  "<font class='error'>".t("Tilaus ei löydy! Oletko painanut back-nappia! Jos et ole, ilmoita virheestä")."!</font>\r\n<br>$query\r\n";
}
else {

	// tilaus löytyi kaikki hyvin....
	$orow = mysql_fetch_array($laskutusres);

	if ($orow['erikoisale'] != 0) {

		// jos laskulla on erikoisalennus, siirretään se riveille, mutta EI riveille joilla on NETTOHINTA...
		$query = "	update tilausrivi set ale = round(ale + $orow[erikoisale] - (ale * $orow[erikoisale] / 100),2)
					where otunnus='$orow[tunnus]' and yhtio='$kukarow[yhtio]' and netto in ('','E')";
		$result = mysql_query($query) or pupe_error($query);

		// nollataan erikoisalennus, ettei lasketa sitä enää uudelleen...
		$query = "update lasku set erikoisale=0 where tunnus='$orow[tunnus]' and yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
	}

	// Etsitään tilausrivit
	$query = "	SELECT *
				FROM tilausrivi
				WHERE otunnus = '$kukarow[kesken]' and yhtio='$kukarow[yhtio]' and var not in ('P','J')
				ORDER BY tunnus";
	$presult = mysql_query($query) or pupe_error($query);

	// estetään häkkejä
	$loppusumma = 0;
	$tothinta   = 0;
	$totkate    = 0;
	
	while ($trow = mysql_fetch_array ($presult)) {
					
		// Marginaaliverotus
		if ($trow["alv"] >= 500) {
			$hinta = $trow["hinta"] * (1 - $trow["ale"] / 100);	//Verollinen kappalehinta alennuksineen
			$loppusumma += $hinta * $trow["varattu"];			//Verollinen loppuhinta
		}
		// Myyntihinnat sisältävät arvonlisäveron
		elseif ($yhtiorow["alv_kasittely"] == '') {
			$hinta = $trow["hinta"] * (1 - $trow["ale"] / 100);	//Verollinen kappalehinta alennuksineen
			$loppusumma += $hinta * $trow["varattu"];			//Verollinen loppuhinta
			
			$hinta = $hinta / (1 + ($trow["alv"] / 100));		//Veroton kappalehinta alennuksineen
		}
		// Myyntihinnat ovat verottomia
		else {
			$hinta = $trow["hinta"] * (1 - $trow["ale"] / 100);						//Veroton kappalehinta alennuksineen	
			$loppusumma += $hinta * $trow["varattu"] * (1 + ($trow["alv"] / 100));	//Verollinen loppuhinta
		}
		
		
		// Etsitään tuote
		$query = "	SELECT kehahin, ei_saldoa, epakurantti1pvm, epakurantti2pvm, sarjanumeroseuranta
					FROM tuote
					WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$trow[tuoteno]'";
		$laskutusres = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($laskutusres) == 0) {
			$tulos_ulos_laskutus .=  "<font class='error'>".t("Tuote")." $trow[tuoteno] ".t("ei löydy")."! ".t("Tilauksella")." $kukarow[kesken]!</font><br>\r\n";
			$kate  = 0;
			$hinta = 0;
		}
		else {

			$turow = mysql_fetch_array ($laskutusres);

			// Lasketaan tilausrivin arvo ja kate
			if ($turow['epakurantti1pvm'] != '0000-00-00') $turow['kehahin'] = $turow['kehahin'] / 2;
			if ($turow['epakurantti2pvm'] != '0000-00-00') $turow['kehahin'] = 0;

			$apuhinta1 = round($hinta,2);
			$apuhinta2 = round($trow['varattu'] * $hinta,2);
						
			//Paivitetaan tuotteen saldo jos saldoa on varattu
			if ($turow['ei_saldoa'] == '' and $trow['varattu'] != 0) {
				///* Päivitetään saldo tilausrivin osoittamalla paikalla *///
				$query = "	UPDATE tuotepaikat
							SET saldo = saldo-$trow[varattu],
							saldoaika = now()
							WHERE yhtio   = '$kukarow[yhtio]' 
							and tuoteno   = '$trow[tuoteno]' 
							and hyllyalue = '$trow[hyllyalue]' 
							and hyllynro  = '$trow[hyllynro]' 
							and hyllytaso = '$trow[hyllytaso]' 
							and hyllyvali = '$trow[hyllyvali]'";
				$laskutusres = mysql_query($query) or pupe_error($query);

				if (mysql_affected_rows() == 0) {
					$tulos_ulos_laskutus .= "<font class='error'>".t("Saldoa tuotteelle")." $trow[tuoteno] ".t("ei voitu päivittää paikalla")." $trow[hyllyalue]-$trow[hyllynro]-$trow[hyllytaso]-$trow[hyllyvali].</font><br>\r\n";
					
					///* Päivitetään saldo tuotteen oletuspaikalla jos tilausrivillä ei ollut sopivaa paikkaa *///
					$query = "	UPDATE tuotepaikat
								SET saldo = saldo-$trow[varattu],
								saldoaika = now()
								WHERE yhtio = '$kukarow[yhtio]' 
								and tuoteno	= '$trow[tuoteno]' 
								and oletus != '' 
								LIMIT 1";
					$laskutusres = mysql_query($query) or pupe_error($query);
					
					if (mysql_affected_rows() == 0) {
						$tulos_ulos_laskutus .= "<font class='error'>".t("Saldoa tuotteelle")." $trow[tuoteno] ".t("ei voitu päivittää oletuspaikalla").".<br></font>\r\n";
						
						///* Päivitetään saldo jollekin sopivalle paikalle jos oletuspaikkaa ei löytynyt *///
						$query = "	UPDATE tuotepaikat
									SET saldo = saldo-$trow[varattu],
									saldoaika = now(),
									oletus = 'X'
									WHERE yhtio = '$kukarow[yhtio]' 
									and tuoteno	= '$trow[tuoteno]' 
									LIMIT 1";
						$laskutusres = mysql_query($query) or pupe_error($query);
						
						if (mysql_affected_rows() == 0) {						
							$tulos_ulos_laskutus .= "<font class='error'>".t("Tuotteella")." $trow[tuoteno] ".t("ei ole yhtään varastopaikkaa").". ".t("Perustetaan uusi").".<br></font>\r\n";
							
							$query = "	INSERT into tuotepaikat (yhtio, hyllyalue, hyllynro, hyllyvali, hyllytaso, oletus, tuoteno, saldo, saldoaika)
										VALUES ('$kukarow[yhtio]','Z','99','99','99','X','$trow[tuoteno]',$trow[varattu]*-1,now())";
							$laskutusres = mysql_query($query) or pupe_error($query);											
						}					
					}
				}
			}
			
			if ($turow['ei_saldoa'] == '' and $turow["sarjanumeroseuranta"] != "" and $trow["varattu"] > 0) {
				//Jos tuotteella ylläpidetään in-out varastonarvo ja kyseessä on myyntiä
				$query = "	SELECT GROUP_CONCAT(ostorivitunnus SEPARATOR ',') ostotunnukset
							FROM sarjanumeroseuranta 
							WHERE yhtio = '$kukarow[yhtio]' 
							and myyntirivitunnus = '$trow[tunnus]'";
				$sarjares1 = mysql_query($query) or pupe_error($query);
				
				$ostohinta = 0;
												
				if(mysql_num_rows($sarjares1) > 0) {
					$sarjarow1 = mysql_fetch_array($sarjares1);
					
					//Tätä pitää fiilata koska jos ostorivi onkin myyntitilauksella niin hintaan voi kuulua esim erikoisalennus
					if ($sarjarow1["ostotunnukset"]  != '') {
						$query = "	SELECT round(sum(hinta*(1-ale/100)),2) ostosumma					
									FROM tilausrivi 
									WHERE yhtio='$kukarow[yhtio]' 
									and tunnus in ($sarjarow1[ostotunnukset])";
						$sarjares2 = mysql_query($query) or pupe_error($query);
						$sarjarow2 = mysql_fetch_array($sarjares2);
									
						$ostohinta += $sarjarow2["ostosumma"];	
					}
					else {
						// Ei löydetty ostohintaa
						$ostohinta += 0;	
					}			
				}
																			
				// Kate = Hinta - Ostohinta
				$kate = round($hinta - round($ostohinta/$trow["varattu"], 4), 2);				
				
				//Tämän sarjanumeroklöntin ostohinta keskimäärin
				$turow["kehahin"] = round($ostohinta/$trow["varattu"], 4);	
											
				//tapahtuman otsikko
				$tapahtumattyyppi = "laskutus";
			}
			elseif ($turow['ei_saldoa'] == '' and $turow["sarjanumeroseuranta"] != "" and $trow["varattu"] < 0) {
				//Jos tuotteella ylläpidetään in-out varastonarvo ja kyseessä on ostoa
				
				// Kate = Hinta
				$kate 				= round($hinta/$trow["varattu"],2);
				$turow["kehahin"] 	= round($hinta/$trow["varattu"],4);	
											
				//tapahtuman otsikko
				$tapahtumattyyppi = "tulo";
			}
			else {
				// Kate = Hinta - Kehahin (alviton)
				$kate = $hinta - $turow["kehahin"];
				
				//tapahtuman otsikko
				$tapahtumattyyppi = "laskutus";
			}
			
			$query = "	INSERT into tapahtuma set
						yhtio      = '$kukarow[yhtio]',
						tuoteno    = '$trow[tuoteno]',
						kpl        =  $trow[varattu] * -1,
						hinta      = '$turow[kehahin]',
						kplhinta   = '$apuhinta1',
						laji       = '$tapahtumattyyppi',
						selite     = '$orow[nimi] $orow[nimitark] ($apuhinta1) [$apuhinta2]',
						laatija    = '$kukarow[kuka]',
						rivitunnus = '$trow[tunnus]',
						laadittu   = now()";
			$updlaskutusres = mysql_query($query) or pupe_error($query);
			
			// Tilausrivin kate (alviton) 
			$kate = $kate * $trow["varattu"];
			
			// Rivihinta alviton
			$hinta = $hinta * $trow["varattu"];
						
		} // tuote ei löydy else haara

		// Kumuloidaan
		$totkate  += $kate;
		$tothinta += $hinta;

		// Päivitetään tilausrivi
		$query = "	UPDATE tilausrivi
					SET kate 		= round($kate,2),
					rivihinta 		= round($hinta,2),
					kpl 			= varattu,
					varattu 		= 0,
					laskutettu		= '$kukarow[kuka]',
					laskutettuaika	= '$laskutuspvm'
					WHERE tunnus = $trow[tunnus] and yhtio='$kukarow[yhtio]'";
		$laskutusres = mysql_query($query) or pupe_error($query);
	}

	// tehdään maksuehtokäsittely vain jos eräpäivää ei ole syötetty jo laskulle...
	if ($orow["erpcm"] == "0000-00-00") {
		
		// Etsitään maksuehto
		$query = "	SELECT *
					FROM maksuehto
					WHERE tunnus='$orow[maksuehto]' and yhtio='$kukarow[yhtio]'";
		$presult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($presult) == 0) {
			$tulos_ulos_laskutus .= "Laskutus.inc: ".t("Maksuehtoa ei löydy! Maksuehto")." $orow[maksuehto] ".t("Yhtiö")." $kukarow[yhtio] ".t("Tilaus")." $kukarow[kesken]<br>\r\n";
		}

		$xrow = mysql_fetch_array($presult);

		if ($xrow['abs_pvm'] == '0000-00-00') {
			$erapvm = "adddate('$laskutuspvm', interval $xrow[rel_pvm] day)";
		}
		else {
			$erapvm = "'$xrow[abs_pvm]'";
		}

		if ($xrow['kassa_teksti']!='') {
			if ($xrow['kassa_abspvm'] == '0000-00-00') {
				$kassa_erapvm = "adddate('$laskutuspvm', interval $xrow[kassa_relpvm] day)";
			}
			else {
				$kassa_erapvm = "'$xrow[kassa_abspvm]'";
			}
			$kassa_loppusumma = round($loppusumma*$xrow['kassa_alepros']/100, 2);
		}
		else {
			$kassa_erapvm = "''";
			$kassa_loppusumma = "";
		}
	}
	else {
		// otetaan eräpvm suoraan tilaukselta
		$erapvm            = "'$orow[erpcm]'";
		$kassa_erapvm      = "''";
		$kassa_loppusumma  = "";
	}

	// Päivitetään lasku
	$tothinta += 0;
	$totkate  += 0;
	
	if (isset($kateistyyppi) and $kateistyyppi == 'p') {
		//pöristetään käteismyynti lähimpään viiteen senttiin
		
		$ei_pyorist = round($loppusumma,2);
		$loppusumma = round(round($loppusumma / 0.05) * 0.05, 2);
		
		$pyorerotus = $ei_pyorist - $loppusumma;
		
		$arvosumma	= round($tothinta - $pyorerotus, 2);
		$katesumma  = round($totkate - $pyorerotus, 2);
	}
	else {
		$loppusumma = round($loppusumma,2);
		$arvosumma	= round($tothinta,2);
		$katesumma  = round($totkate,2);
	}
	
	$viivastyskorko = $yhtiorow['viivastyskorko'];
	
	if(isset($korkolasku) and $korkolasku == "kylla") {
		$viivastyskorko = $orow['viivastyskorko'];
	}
	
	// Etsitään asiakas
	$query = "	SELECT laskunsummapyoristys
				FROM asiakas
				WHERE tunnus='$orow[liitostunnus]' and yhtio='$kukarow[yhtio]'";
	$asres = mysql_query($query) or pupe_error($query);
	$asrow = mysql_fetch_array($asres);
	
	// Laskun loppusumma pyöristetään vain jos laskun valuutta on yhtiön kotivaluutassa (Valuuuttalaskujen loppusummat rundataan vain tulosteeseen)
	if (trim(strtoupper($orow["valkoodi"])) == trim(strtoupper($yhtiorow["valkoodi"])) and ($yhtiorow["laskunsummapyoristys"] == 'o' or $asrow["laskunsummapyoristys"] == 'o')) {
        $loppusumma = round($loppusumma,0);
	}
	
	$query = "	UPDATE lasku
				SET alatila = 'V',
				arvo = '$arvosumma',
				kate = '$katesumma',
				summa = '$loppusumma',
				erpcm = $erapvm,
				kapvm = $kassa_erapvm,
				kasumma = '$kassa_loppusumma',
				viikorkopros = '$viivastyskorko',
				laskuttaja = '$kukarow[kuka]',
				tapvm = '$laskutuspvm',
				maksuehto = '$orow[maksuehto]',
				laskutettu = now()
				WHERE tunnus = '$kukarow[kesken]' and yhtio='$kukarow[yhtio]'";
	$laskutusres = mysql_query($query) or pupe_error($query);
	
	$id = 0; // Ei enää kesken
	
} // end tilaus ei löydy else haara

//echotaan rudulle jos kyseessä ei ole batch-ajo
if ($tulos_ulos == "" and $silent == "") {
	echo $tulos_ulos_laskutus;
}

?>
