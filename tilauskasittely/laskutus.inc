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
					where otunnus='$orow[tunnus]' 
					and yhtio='$kukarow[yhtio]' 
					and netto in ('','E')
					and tyyppi='L'";
		$result = mysql_query($query) or pupe_error($query);

		// nollataan erikoisalennus, ettei lasketa sitä enää uudelleen...
		$query = "update lasku set erikoisale=0 where tunnus='$orow[tunnus]' and yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
	}

	// Etsitään tilausrivit
	$query = "	SELECT tilausrivi.*, tilausrivin_lisatiedot.osto_vai_hyvitys
				FROM tilausrivi
				LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
				WHERE tilausrivi.otunnus 	= '$kukarow[kesken]' 
				and tilausrivi.yhtio 		= '$kukarow[yhtio]' 
				and tilausrivi.var 			not in ('P','J')
				and tilausrivi.tyyppi 		= 'L'
				ORDER BY tilausrivi.tunnus";
	$presult = mysql_query($query) or pupe_error($query);

	// Nollataan muuttujat
	$loppusumma 					= 0;
	$loppusumma_valuutassa 			= 0;
	$tothinta   					= 0;
	$tothinta_valuutassa			= 0;
	$totkate    					= 0;
	$kassa_loppusumma 				= 0;
	$kassa_loppusumma_valuutassa 	= 0;
	$ei_pyorist						= 0;
	$pyorerotus						= 0;
	$arvosumma						= 0;
	$katesumma 						= 0;
	$ei_pyorist_valuutassa			= 0;
	$pyorerotus_valuutassa			= 0;
	$arvosumma_valuutassa			= 0;
	$kassa_erapvm 					= "";
	
	while ($trow = mysql_fetch_array ($presult)) {
					
		// Marginaaliverotus
		if ($trow["alv"] >= 500) {
			$hinta_valuutassa	    = laskuval($trow["hinta"], $orow["vienti_kurssi"]) / (1 - $trow["ale"] / 100);				//Kappalehinta alennuksineen valuutassa
			$hinta 					= $trow["hinta"] * (1 - $trow["ale"] / 100);												//Kappalehinta alennuksineen
			
			$loppusumma_valuutassa += laskuval($hinta, $orow["vienti_kurssi"]) * $trow["varattu"];								//Rivihinta valuutassa 
			$loppusumma 		   += $hinta * $trow["varattu"];																//Rivihinta			
		}
		// Myyntihinnat sisältävät arvonlisäveron
		elseif ($yhtiorow["alv_kasittely"] == '') {
			$hinta 					= $trow["hinta"] * (1 - $trow["ale"] / 100);												//Verollinen kappalehinta alennuksineen
			
			$loppusumma_valuutassa += laskuval($hinta, $orow["vienti_kurssi"]) * $trow["varattu"];								//Verollinen rivihinta valuutassa 
			$loppusumma 		   += $hinta * $trow["varattu"];																//Verollinen rivihinta
			
			$hinta_valuutassa	    = laskuval($hinta, $orow["vienti_kurssi"]) / (1 + ($trow["alv"] / 100));					//Veroton kappalehinta alennuksineen valuutassa
			$hinta 					= $hinta / (1 + ($trow["alv"] / 100));														//Veroton kappalehinta alennuksineen			
		}
		// Myyntihinnat ovat verottomia
		else {
			$hinta_valuutassa	    = laskuval($trow["hinta"], $orow["vienti_kurssi"]) * (1 - $trow["ale"] / 100);				//Veroton kappalehinta alennuksineen valuutassa
			$hinta 					= $trow["hinta"] * (1 - $trow["ale"] / 100);												//Veroton kappalehinta alennuksineen
			
			$loppusumma_valuutassa += laskuval($hinta, $orow["vienti_kurssi"]) * $trow["varattu"] * (1 + ($trow["alv"] / 100));	//Verollinen rivihinta valuutassa 
			$loppusumma 		   += $hinta * $trow["varattu"] * (1 + ($trow["alv"] / 100));									//Verollinen rivihinta
		}
				
		// Etsitään tuote
		$query = "	SELECT kehahin, ei_saldoa, epakurantti25pvm, epakurantti50pvm, epakurantti75pvm, epakurantti100pvm, sarjanumeroseuranta
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
			if 		($turow['epakurantti100pvm'] != '0000-00-00') $turow['kehahin'] = 0;
			elseif	($turow['epakurantti75pvm'] != '0000-00-00') $turow['kehahin'] = $turow['kehahin'] * 0.25;
			elseif 	($turow['epakurantti50pvm'] != '0000-00-00') $turow['kehahin'] = $turow['kehahin'] * 0.5;
			elseif 	($turow['epakurantti25pvm'] != '0000-00-00') $turow['kehahin'] = $turow['kehahin'] * 0.75;


			$apuhinta1 = round($hinta,2);
			$apuhinta2 = round($trow['varattu'] * $hinta,2);
						
			//Paivitetaan tuotteen saldo jos saldoa on varattu
			if ($turow['ei_saldoa'] == '' and $trow['varattu'] != 0) {
				
				if ($turow["sarjanumeroseuranta"] == "E" or $turow["sarjanumeroseuranta"] == "F") {
					if ($trow["varattu"] > 0) {
						
						$query = "	SELECT ostorivitunnus
									FROM sarjanumeroseuranta
									WHERE yhtio			 = '$kukarow[yhtio]'
									and tuoteno			 = '$trow[tuoteno]'
									and myyntirivitunnus = '$trow[tunnus]'
									LIMIT 1";
						$lisa_res = mysql_query($query) or pupe_error($query);
						$lisa_row = mysql_fetch_array($lisa_res);
						
						$query = "	UPDATE sarjanumeroseuranta
									SET era_kpl = era_kpl-$trow[varattu]
									WHERE yhtio 			= '$kukarow[yhtio]' 
									and tuoteno				= '$trow[tuoteno]' 
									and ostorivitunnus 		= '$lisa_row[ostorivitunnus]'
									and myyntirivitunnus 	= 0
									LIMIT 1";
						$lisa_res = mysql_query($query) or pupe_error($query);
						
						$query = "	UPDATE sarjanumeroseuranta
									SET era_kpl = era_kpl+$trow[varattu]
									WHERE yhtio			 = '$kukarow[yhtio]'
									and tuoteno			 = '$trow[tuoteno]'
									and myyntirivitunnus = '$trow[tunnus]'
									LIMIT 1";
						$lisa_res = mysql_query($query) or pupe_error($query);
					}
				}
				
				
				
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
			
			if ($turow['ei_saldoa'] == '' and $turow["sarjanumeroseuranta"] == "S" and $trow["varattu"] > 0) {
				//Jos tuotteella ylläpidetään in-out varastonarvo ja kyseessä on myyntiä
				$ostohinta = sarjanumeron_ostohinta("myyntirivitunnus", $trow["tunnus"]);
					
				// Kate = Hinta - Ostohinta
				$kate = round($hinta - round($ostohinta/$trow["varattu"], 4), 2);				
				
				//Tämän sarjanumeroklöntin ostohinta keskimäärin
				$turow["kehahin"] = round($ostohinta/$trow["varattu"], 4);	
											
				//tapahtuman otsikko
				$tapahtumattyyppi = "laskutus";
			}
			elseif ($turow['ei_saldoa'] == '' and $turow["sarjanumeroseuranta"] == "S" and $trow["varattu"] < 0 and $trow["osto_vai_hyvitys"] == "O") {
				//Jos tuotteella ylläpidetään in-out varastonarvo ja kyseessä on OSTOA
				
				// Kate = 0
				$kate 				= 0;
				$turow["kehahin"] 	= round($hinta/$trow["varattu"],4) * -1;	
											
				//tapahtuman otsikko
				$tapahtumattyyppi = "tulo";
			}
			elseif ($turow['ei_saldoa'] == '' and $turow["sarjanumeroseuranta"] == "S" and $trow["varattu"] < 0 and $trow["osto_vai_hyvitys"] == "") {
				//Jos tuotteella ylläpidetään in-out varastonarvo ja kyseessä on HYVITYSTÄ
				
				//Tähän hyvitysriviin liitetyt sarjanumerot
				$query = "	SELECT *
							FROM sarjanumeroseuranta
							WHERE yhtio 		= '$kukarow[yhtio]' 
							and ostorivitunnus 	= '$trow[tunnus]'";
				$sarjares = mysql_query($query) or pupe_error($query);
				
				$ostohinta = 0;
				
				while($sarjarow = mysql_fetch_array($sarjares)) {
					
					// Haetaan hyvitettävien myyntirivien kautta alkuperäiset ostorivit
					$query  = "	SELECT sarjanumeroseuranta.*, tilausrivi.rivihinta/tilausrivi.kpl ostohinta
								FROM sarjanumeroseuranta
								JOIN tilausrivi use index (PRIMARY) ON tilausrivi.yhtio=sarjanumeroseuranta.yhtio and tilausrivi.tunnus=sarjanumeroseuranta.ostorivitunnus
								WHERE sarjanumeroseuranta.yhtio 	= '$kukarow[yhtio]' 
								and sarjanumeroseuranta.tuoteno 	= '$trow[tuoteno]'
								and sarjanumeroseuranta.sarjanumero = '$sarjarow[sarjanumero]'
								and sarjanumeroseuranta.kaytetty 	= '$sarjarow[kaytetty]'
								and sarjanumeroseuranta.myyntirivitunnus > 0
								and sarjanumeroseuranta.ostorivitunnus   > 0
								ORDER BY sarjanumeroseuranta.tunnus DESC
								LIMIT 1";
					$sarjares1 = mysql_query($query) or pupe_error($query);
					$sarjarow1 = mysql_fetch_array($sarjares1);
										
					$ostohinta += $sarjarow1["ostohinta"];
					
					//Tehdään sarjanumerotauluun uusi vapaa sarjanumero-olio
					$query = "	UPDATE sarjanumeroseuranta
								SET myyntirivitunnus=ostorivitunnus, ostorivitunnus='$sarjarow1[ostorivitunnus]'
								WHERE yhtio = '$kukarow[yhtio]' 
								and tunnus 	= '$sarjarow[tunnus]'";
					$sarjares1 = mysql_query($query) or pupe_error($query);
					
					$query = "	INSERT into sarjanumeroseuranta 
								(yhtio, tuoteno, sarjanumero, lisatieto, ostorivitunnus, kaytetty, laatija, luontiaika, takuu_alku, takuu_loppu)
								VALUES ('$kukarow[yhtio]','$sarjarow[tuoteno]','$sarjarow[sarjanumero]','$sarjarow[lisatieto]','$sarjarow1[ostorivitunnus]','$sarjarow1[kaytetty]','$kukarow[kuka]',now(),'$sarjarow1[takuu_alku]','$sarjarow1[takuu_loppu]')";
					$sarjares1 = mysql_query($query) or pupe_error($query);	
				}
				
				// Kate = Hinta - Alkuperäinen ostohinta
				$kate = round($hinta - round($ostohinta/abs($trow["varattu"]), 4), 2);				
								
				//Tämän sarjanumeroklöntin ostohinta keskimäärin
				$turow["kehahin"] = round($ostohinta/$trow["varattu"], 4) * -1;	
											
				//tapahtuman otsikko
				$tapahtumattyyppi = "laskutus";
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
		$totkate  			 += $kate;
		$tothinta 			 += $hinta;
		$tothinta_valuutassa += $hinta_valuutassa;

		// Päivitetään tilausrivi
		////// FUTURE: rivihinta_valuutassa = round($hinta_valuutassa,2), //////
		$query = "	UPDATE tilausrivi
					SET kate 		= round($kate,2),
					rivihinta 		= round($hinta,2),
					kpl 			= varattu,
					varattu 		= 0,
					laskutettu		= '$kukarow[kuka]',
					laskutettuaika	= '$laskutuspvm'
					WHERE tunnus = $trow[tunnus] 
					and yhtio	 = '$kukarow[yhtio]'";
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

		if ($xrow['abs_pvm'] == '0000-00-00' and $xrow["rel_pvm"] != 0) {
			$erapvm = "adddate('$laskutuspvm', interval $xrow[rel_pvm] day)";
		}
		elseif ($xrow['abs_pvm'] != '0000-00-00') {
			$erapvm = "'$xrow[abs_pvm]'";
		}
		else {
			$erapvm = "'$laskutuspvm'";
		}

		if ($xrow['kassa_teksti']!='') {
			if ($xrow['kassa_abspvm'] == '0000-00-00') {
				$kassa_erapvm = "adddate('$laskutuspvm', interval $xrow[kassa_relpvm] day)";
			}
			else {
				$kassa_erapvm = "'$xrow[kassa_abspvm]'";
			}
			$kassa_loppusumma 				= round($loppusumma*$xrow['kassa_alepros']/100, 2);
			$kassa_loppusumma_valuutassa 	= round($loppusumma_valuutassa*$xrow['kassa_alepros']/100, 2);
		}
		else {
			$kassa_erapvm 					= "''";
			$kassa_loppusumma 				= "";
			$kassa_loppusumma_valuutassa 	= "";
		}
	}
	else {
		// otetaan eräpvm suoraan tilaukselta
		$erapvm            				= "'$orow[erpcm]'";
		$kassa_erapvm 					= "''";
		$kassa_loppusumma 				= "";
		$kassa_loppusumma_valuutassa 	= "";
	}

	// Päivitetään lasku	
	if (isset($kateistyyppi) and $kateistyyppi == 'p') {
		// Pyöristetään käteismyynti lähimpään viiteen senttiin
		$ei_pyorist = round($loppusumma,2);
		$loppusumma = round(round($loppusumma / 0.05) * 0.05, 2);
		$pyorerotus = $ei_pyorist - $loppusumma;
		$arvosumma	= round($tothinta - $pyorerotus, 2);
		$katesumma  = round($totkate - $pyorerotus, 2);
		
		// Pyöristetään käteismyynti lähimpään viiteen senttiin myös laskun valuutassa
		$ei_pyorist_valuutassa = round($loppusumma_valuutassa,2);
		$loppusumma_valuutassa = round(round($loppusumma_valuutassa / 0.05) * 0.05, 2);
		$pyorerotus_valuutassa = $ei_pyorist_valuutassa - $loppusumma_valuutassa;
		$arvosumma_valuutassa  = round($tothinta_valuutassa - $pyorerotus_valuutassa, 2);		
	}
	else {
		$loppusumma 			= round($loppusumma,2);
		$loppusumma_valuutassa 	= round($loppusumma_valuutassa,2);
		$arvosumma				= round($tothinta,2);
		$arvosumma_valuutassa	= round($tothinta_valuutassa,2);
		$katesumma  			= round($totkate,2);
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
	
	//Käsin syötetty summa johon lasku pyöristetään joko kotivaluutassa tai laskun valuutassa
	if (abs($orow["hinta"]-$loppusumma) <= 0.5) {
		$loppusumma = sprintf("%.2f",$orow["hinta"]);
	}
	if (abs($orow["hinta"]-$loppusumma_valuutassa) <= 0.5) {
		$loppusumma_valuutassa = sprintf("%.2f",$orow["hinta"]);
	}

	// jos halutaan pyöristää laskun loppusumma
	if ($yhtiorow["laskunsummapyoristys"] == 'o' or $asrow["laskunsummapyoristys"] == 'o') {
		// jos ollaan kotivaluutassa pyoristetääm loppusumma
		if (trim(strtoupper($orow["valkoodi"])) == trim(strtoupper($yhtiorow["valkoodi"]))) {
        	$loppusumma = round($loppusumma,0);
		}
		// jos ollaan eri kuin kotivaluutassa pyöristetään loppusumma_valuutassa
		if (trim(strtoupper($orow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
			$loppusumma_valuutassa = round($loppusumma_valuutassa,0);
		}
	}
	
	$query = "	UPDATE lasku
				SET alatila 		= 'V',
				arvo 				= '$arvosumma',
				arvo_valuutassa 	= '$arvosumma_valuutassa',
				kate 				= '$katesumma',
				summa 				= '$loppusumma',
				summa_valuutassa 	= '$loppusumma_valuutassa',
				erpcm 				= $erapvm,
				kapvm 				= $kassa_erapvm,
				kasumma 			= '$kassa_loppusumma',
				kasumma_valuutassa	= '$kassa_loppusumma_valuutassa',
				viikorkopros 		= '$viivastyskorko',
				laskuttaja 			= '$kukarow[kuka]',
				tapvm 				= '$laskutuspvm',
				maksuehto 			= '$orow[maksuehto]',
				laskutettu 			= now()
				WHERE tunnus = '$kukarow[kesken]' 
				and yhtio	 = '$kukarow[yhtio]'";
	$laskutusres = mysql_query($query) or pupe_error($query);
	
	$id = 0; 
	
	// Ei enää kesken
	$query	= "UPDATE kuka set kesken=0 where yhtio='$kukarow[yhtio]' and kesken='$kukarow[kesken]'";
	$laskutusres = mysql_query($query) or pupe_error($query);	
} // end tilaus ei löydy else haara

//echotaan rudulle jos kyseessä ei ole batch-ajo
if ($tulos_ulos == "" and $silent == "") {
	echo $tulos_ulos_laskutus;
}

?>