<?php

// tarvitaan $kukarow['kesken'] jossa on laskutettava tilaus
// ja $laskpp $laskkk $laskvv jos halutaan määritellä poikkeava laskutuspvm

$tulos_ulos_laskutus = "";

// jos erisuuri kun nollaa tai tyhjää niin käytetään laskutuspvm:ää
if ($poikkeava_pvm != '') {
	//poikkeava
	$laskutuspvm = $laskvv."-".$laskkk."-".$laskpp;
	$laskutus_tapahtumapvm = $laskvv."-".$laskkk."-".$laskpp." 23:59:59";
}
else {
	// today
	$laskutuspvm = date("Y-n-j");
	$laskutus_tapahtumapvm = date("Y-n-j H:i:s");
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
		$query = "	UPDATE tilausrivi set ale = round(ale + $orow[erikoisale] - (ale * $orow[erikoisale] / 100),2)
					WHERE otunnus = '$orow[tunnus]'
					and yhtio = '$kukarow[yhtio]'
					and netto in ('','E')
					and tyyppi = 'L'";
		$result = mysql_query($query) or pupe_error($query);

		// nollataan erikoisalennus, ettei lasketa sitä enää uudelleen...
		$query = "UPDATE lasku SET erikoisale = 0 WHERE tunnus = '$orow[tunnus]' and yhtio = '$kukarow[yhtio]'";
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

	// nollataan kumulatiivisia muuttujia
	$loppusumma 			= 0;
	$loppusumma_valuutassa 	= 0;
	$arvosumma				= 0;
	$arvosumma_valuutassa	= 0;
	$katesumma  			= 0;

	while ($trow = mysql_fetch_array ($presult)) {

		// Nollataan loopin muuttujat
		$hinta					= 0;
		$hinta_valuutassa	    = 0;
		$rivihinta				= 0;
		$rivihinta_valuutassa	= 0;
		$kate					= 0;

		// Marginaaliverotus
		if ($trow["alv"] >= 500) {
			$hinta					= $trow["hinta"] * (1 - $trow["ale"] / 100);														//Kappalehinta alennuksineen

			$rivihinta				= round((float) $hinta * $trow["varattu"], $yhtiorow["hintapyoristys"]);									//Rivihinta alennuksineen
			$rivihinta_valuutassa	= round((float) laskuval($hinta * $trow["varattu"], $orow["vienti_kurssi"]), $yhtiorow["hintapyoristys"]);	//Rivihinta alennuksineen valuutassa

			// kumuloidaan loppusummia
			$loppusumma 			+= $rivihinta;																						//Kumuloidaan verollinen loppusumma
			$loppusumma_valuutassa 	+= $rivihinta_valuutassa;																			//Kumuloidaan verollinen loppusumma valuutassa
			$arvosumma				+= $rivihinta;																						//Kumuloidaan veroton loppusumma
			$arvosumma_valuutassa	+= $rivihinta_valuutassa;																			//Kumuloidaan veroton loppusumma valuutassa

			$hinta_valuutassa	    = round((float) laskuval($hinta, $orow["vienti_kurssi"]), $yhtiorow["hintapyoristys"]);						//Kappalehinta alennuksineen valuutassa
			$hinta 					= round((float) $hinta, $yhtiorow["hintapyoristys"]);														//Kappalehinta alennuksineen
		}
		// Myyntihinnat sisältävät arvonlisäveron
		elseif ($yhtiorow["alv_kasittely"] == '') {
			$hinta 					= $trow["hinta"] * (1 - $trow["ale"] / 100);																						//Verollinen kappalehinta alennuksineen

			$rivihinta				= round((float) $hinta * $trow["varattu"] / (1 + ($trow["alv"] / 100)), $yhtiorow["hintapyoristys"]);										//Veroton rivihinta alennuksineen
			$rivihinta_valuutassa	= round((float) laskuval($hinta * $trow["varattu"] / (1 + ($trow["alv"] / 100)), $orow["vienti_kurssi"]), $yhtiorow["hintapyoristys"]);		//Veroton rivihinta alennuksineen valuutassa

			// kumuloidaan loppusummia
			$loppusumma			 	+= round((float) $hinta * $trow["varattu"], $yhtiorow["hintapyoristys"]);								                                    //Kumuloidaan verollinen loppusumma valuutassa
			$loppusumma_valuutassa	+= round((float) laskuval($hinta * $trow["varattu"], $orow["vienti_kurssi"]), $yhtiorow["hintapyoristys"]);									//Kumuloidaan verollinen loppusumma
			$arvosumma				+= $rivihinta;                                                                                                                      //Kumuloidaan veroton loppusumma
			$arvosumma_valuutassa	+= $rivihinta_valuutassa;                                                                                                           //Kumuloidaan veroton loppusumma valuutassa

			$hinta_valuutassa	    = round((float) laskuval($hinta / (1 + ($trow["alv"] / 100)), $orow["vienti_kurssi"]), $yhtiorow["hintapyoristys"]);						//Veroton kappalehinta alennuksineen valuutassa
			$hinta 					= round((float) $hinta / (1 + ($trow["alv"] / 100)), $yhtiorow["hintapyoristys"]);															//Veroton kappalehinta alennuksineen
		}
		// Myyntihinnat ovat verottomia
		else {
			$hinta 					= $trow["hinta"] * (1 - $trow["ale"] / 100);																						//Veroton kappalehinta alennuksineen

			$rivihinta				= round((float) $hinta * $trow["varattu"], $yhtiorow["hintapyoristys"]);																	//Veroton kappalehinta alennuksineen
			$rivihinta_valuutassa	= round((float) laskuval($hinta * $trow["varattu"], $orow["vienti_kurssi"]), $yhtiorow["hintapyoristys"]);									//Veroton kappalehinta alennuksineen valuutassa

			// kumuloidaan loppusummia
			$loppusumma			 	+= round((float) $hinta * $trow["varattu"] * (1 + ($trow["alv"] / 100)), $yhtiorow["hintapyoristys"]);									    //Kumuloidaan verollinen loppusumma valuutassa
			$loppusumma_valuutassa	+= round((float) laskuval($hinta * $trow["varattu"] * (1 + ($trow["alv"] / 100)), $orow["vienti_kurssi"]), $yhtiorow["hintapyoristys"]);	//Kumuloidaan verollinen loppusumma
			$arvosumma				+= $rivihinta;                                                                                                                      //Kumuloidaan veroton loppusumma
			$arvosumma_valuutassa	+= $rivihinta_valuutassa;                                                                                                           //Kumuloidaan veroton loppusumma valuutassa

			$hinta_valuutassa		= round((float) laskuval($hinta, $orow["vienti_kurssi"]), $yhtiorow["hintapyoristys"]);														//Veroton kappalehinta alennuksineen valuutassa
			$hinta					= round((float) $hinta, $yhtiorow["hintapyoristys"]);																						//Veroton kappalehinta alennuksineen
		}

		// Etsitään tuote
		$query = "	SELECT kehahin, ei_saldoa, epakurantti25pvm, epakurantti50pvm, epakurantti75pvm, epakurantti100pvm, sarjanumeroseuranta
					FROM tuote
					WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$trow[tuoteno]'";
		$laskutusres = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($laskutusres) == 0) {
			$tulos_ulos_laskutus .=  "<font class='error'>".t("Tuote")." $trow[tuoteno] ".t("ei löydy")."! ".t("Tilauksella")." $kukarow[kesken]!</font><br>\r\n";
			$kate					= 0;
			$hinta					= 0;
			$hinta_valuutassa	    = 0;
			$rivihinta_valuutassa	= 0;
			$rivihinta				= 0;
		}
		else {

			$turow = mysql_fetch_array ($laskutusres);

			// Lasketaan tilausrivin arvo ja kate
			if 		($turow['epakurantti100pvm'] != '0000-00-00') $turow['kehahin'] = 0;
			elseif	($turow['epakurantti75pvm'] != '0000-00-00') $turow['kehahin'] = $turow['kehahin'] * 0.25;
			elseif 	($turow['epakurantti50pvm'] != '0000-00-00') $turow['kehahin'] = $turow['kehahin'] * 0.5;
			elseif 	($turow['epakurantti25pvm'] != '0000-00-00') $turow['kehahin'] = $turow['kehahin'] * 0.75;

			//Paivitetaan tuotteen saldo jos saldoa on varattu
			if ($turow['ei_saldoa'] == '' and $trow['varattu'] != 0) {

				if ($turow["sarjanumeroseuranta"] == "E" or $turow["sarjanumeroseuranta"] == "F") {
					if ($trow["varattu"] > 0) {

						$query = "	SELECT ostorivitunnus, myyntirivitunnus
									FROM sarjanumeroseuranta
									WHERE yhtio			 = '$kukarow[yhtio]'
									and tuoteno			 = '$trow[tuoteno]'
									and myyntirivitunnus = '$trow[tunnus]'
									LIMIT 1";
						$lisa_res = mysql_query($query) or pupe_error($query);
						$lisa_row = mysql_fetch_array($lisa_res);

						if ($lisa_row["ostorivitunnus"] != $lisa_row["myyntirivitunnus"]) {
							// Normikeissi jossa erä on jo tuloutettu
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
						else {
							// Laskutetaan vaikka ei olla vielä ostettu
							$query = "	UPDATE sarjanumeroseuranta
										SET era_kpl = $trow[varattu]*-1,
										ostorivitunnus = 0
										WHERE yhtio 			= '$kukarow[yhtio]'
										and tuoteno				= '$trow[tuoteno]'
										and ostorivitunnus 		= '$trow[tunnus]'
										and myyntirivitunnus 	= 0
										LIMIT 1";
							$lisa_res = mysql_query($query) or pupe_error($query);

							$query = "	UPDATE sarjanumeroseuranta
										SET era_kpl = $trow[varattu]
										WHERE yhtio			 = '$kukarow[yhtio]'
										and tuoteno			 = '$trow[tuoteno]'
										and myyntirivitunnus = '$trow[tunnus]'
										LIMIT 1";
							$lisa_res = mysql_query($query) or pupe_error($query);
						}
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

			// lasketaan kate
			if ($turow['ei_saldoa'] == '' and $turow["sarjanumeroseuranta"] == "S" and $trow["varattu"] > 0) {
				//Jos tuotteella ylläpidetään in-out varastonarvo ja kyseessä on myyntiä
				$ostohinta = sarjanumeron_ostohinta("myyntirivitunnus", $trow["tunnus"]);

				// Kate = Hinta - Ostohinta
				$kate = round((float) $hinta - round($ostohinta/$trow["varattu"], 6), $yhtiorow["hintapyoristys"]);

				//Tämän sarjanumeroklöntin ostohinta keskimäärin
				$turow["kehahin"] = round((float) $ostohinta/$trow["varattu"], 6);

				//tapahtuman otsikko
				$tapahtumattyyppi = "laskutus";
			}
			elseif ($turow['ei_saldoa'] == '' and $turow["sarjanumeroseuranta"] == "S" and $trow["varattu"] < 0 and $trow["osto_vai_hyvitys"] == "O") {
				//Jos tuotteella ylläpidetään in-out varastonarvo ja kyseessä on OSTOA

				// Kate = 0
				$kate 				= 0;
				$turow["kehahin"] 	= round((float) $hinta/$trow["varattu"], 6) * -1;

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
								and sarjanumeroseuranta.tunnus != '$sarjarow[tunnus]'
								ORDER BY sarjanumeroseuranta.tunnus DESC
								LIMIT 1";
					$sarjares1 = mysql_query($query) or pupe_error($query);
					$sarjarow1 = mysql_fetch_array($sarjares1);

					$ostohinta += $sarjarow1["ostohinta"];

					// Tehdään sarjanumerotauluun uusi vapaa sarjanumero-olio
					$query = "	UPDATE sarjanumeroseuranta
								SET myyntirivitunnus=ostorivitunnus, ostorivitunnus='$sarjarow1[ostorivitunnus]'
								WHERE yhtio = '$kukarow[yhtio]'
								and tunnus 	= '$sarjarow[tunnus]'";
					$sarjares1 = mysql_query($query) or pupe_error($query);

					//Jos nyt hyvitettävä sarjanumero onkin jo myyty eteenpäin.
					if ($sarjarow["myyntirivitunnus"] > 0) {
						$myritu = $sarjarow["myyntirivitunnus"];
					}
					else {
						$myritu = 0;
					}

					$query = "	INSERT into sarjanumeroseuranta
								(yhtio, tuoteno, sarjanumero, lisatieto, ostorivitunnus, myyntirivitunnus, kaytetty, laatija, luontiaika, takuu_alku, takuu_loppu, hyllyalue, hyllynro, hyllyvali, hyllytaso)
								VALUES ('$kukarow[yhtio]','$sarjarow[tuoteno]','$sarjarow[sarjanumero]','$sarjarow[lisatieto]','$sarjarow1[ostorivitunnus]','$myritu','$sarjarow1[kaytetty]','$kukarow[kuka]',now(),'$sarjarow1[takuu_alku]','$sarjarow1[takuu_loppu]', '$sarjarow[hyllyalue]', '$sarjarow[hyllynro]', '$sarjarow[hyllyvali]', '$sarjarow[hyllytaso]')";
					$sarjares1 = mysql_query($query) or pupe_error($query);
				}

				// Kate = Hinta - Alkuperäinen ostohinta
				$kate = round((float) $hinta - round((float) $ostohinta/abs($trow["varattu"]), 6), $yhtiorow["hintapyoristys"]);

				//Tämän sarjanumeroklöntin ostohinta keskimäärin
				$turow["kehahin"] = round((float) $ostohinta/$trow["varattu"], 6) * -1;

				//tapahtuman otsikko
				$tapahtumattyyppi = "laskutus";
			}
			else {
				// Kate = Hinta - Kehahin (alviton)
				$kate = $hinta - $turow["kehahin"];

				//tapahtuman otsikko
				$tapahtumattyyppi = "laskutus";
			}

			// tehdään tapahtuma
			$query = "	INSERT into tapahtuma set
						yhtio      = '$kukarow[yhtio]',
						tuoteno    = '$trow[tuoteno]',
						kpl        =  $trow[varattu] * -1,
						hinta      = '$turow[kehahin]',
						kplhinta   = '$hinta',
						laji       = '$tapahtumattyyppi',
						selite     = '$orow[nimi] $orow[nimitark] ($hinta) [$rivihinta]',
						laatija    = '$kukarow[kuka]',
						rivitunnus = '$trow[tunnus]',
						laadittu   = '$laskutus_tapahtumapvm'";
			$updlaskutusres = mysql_query($query) or pupe_error($query);

			// Tilausrivin kate (alviton)
			$kate = round((float) $kate * $trow["varattu"], $yhtiorow["hintapyoristys"]);

		} // tuote ei löydy else haara

		// kumuloidaan
		$katesumma += $kate;

		// Päivitetään tilausrivi
		$query = "	UPDATE tilausrivi
					SET kate 				= '$kate',
					rivihinta 				= '$rivihinta',
					rivihinta_valuutassa	= '$rivihinta_valuutassa',
					kpl 					= varattu,
					varattu 				= 0,
					laskutettu				= '$kukarow[kuka]',
					laskutettuaika			= '$laskutuspvm'
					WHERE tunnus = $trow[tunnus]
					and yhtio = '$kukarow[yhtio]'";
		$laskutusres = mysql_query($query) or pupe_error($query);
	}

	// tehdään maksuehtokäsittely vain jos eräpäivää ei ole syötetty jo laskulle...
	if ($orow["erpcm"] == "0000-00-00") {
		// Etsitään maksuehto
		$query = "	SELECT *
					FROM maksuehto
					WHERE tunnus = '$orow[maksuehto]' and yhtio = '$kukarow[yhtio]'";
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
			$kassa_loppusumma 				= round((float) $loppusumma * $xrow['kassa_alepros']/100, 2);
			$kassa_loppusumma_valuutassa 	= round((float) $loppusumma_valuutassa * $xrow['kassa_alepros']/100, 2);
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


	$pyorerotus				= 0;
	$pyorerotus_valuutassa	= 0;
	$loppusumma 			= round((float) $loppusumma, 2);
	$loppusumma_valuutassa 	= round((float) $loppusumma_valuutassa, 2);
	$arvosumma				= round((float) $arvosumma, 2);
	$arvosumma_valuutassa	= round((float) $arvosumma_valuutassa, 2);
	$katesumma  			= round((float) $katesumma, 2);

	// jos maksetaan käteisellä pyöristetään lähimpään viiteen senttiin
	if ($xrow["kateinen"] == 'p') {
		// kotivaluutassa
		if (trim(strtoupper($orow["valkoodi"])) == trim(strtoupper($yhtiorow["valkoodi"]))) {
			$pyorerotus = round((float) $loppusumma, 2) - round((float) round((float) $loppusumma / 0.05) * 0.05, 2);
		}
		// valuutassa
		if (trim(strtoupper($orow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
			$pyorerotus_valuutassa = round((float) $loppusumma_valuutassa, 2) - round((float) round((float) $loppusumma_valuutassa / 0.05) * 0.05, 2);
		}
	}

	// Etsitään asiakas
	$query = "	SELECT laskunsummapyoristys
				FROM asiakas
				WHERE tunnus='$orow[liitostunnus]' and yhtio='$kukarow[yhtio]'";
	$asres = mysql_query($query) or pupe_error($query);
	$asrow = mysql_fetch_array($asres);

	// käsin syötetty summa johon lasku pyöristetään kotivaluutassa
	if (abs($orow["hinta"] - $loppusumma) <= 0.5 and trim(strtoupper($orow["valkoodi"])) == trim(strtoupper($yhtiorow["valkoodi"]))) {
		$pyorerotus = $loppusumma - $orow["hinta"];
	}

	// käsin syötetty summa johon lasku pyöristetään valuutassa
	if (abs($orow["hinta"] - $loppusumma_valuutassa) <= 0.5 and trim(strtoupper($orow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
		$pyorerotus_valuutassa = $loppusumma_valuutassa - $orow["hinta"];
	}

	// jos halutaan pyöristää laskun loppusumma lähimpään tasalukuun
	if ($yhtiorow["laskunsummapyoristys"] != '' or $asrow["laskunsummapyoristys"] != '') {
		// kotivaluutassa
		if (trim(strtoupper($orow["valkoodi"])) == trim(strtoupper($yhtiorow["valkoodi"]))) {
			$pyorerotus = $loppusumma - round((float) $loppusumma, 0);
		}
		// valuutassa
		if (trim(strtoupper($orow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
			$pyorerotus_valuutassa = $loppusumma_valuutassa - round((float) $loppusumma_valuutassa, 0);
		}
	}

	// katotaan mikä viivästyskorko laitetaan
	$viivastyskorko = $yhtiorow['viivastyskorko'];

	if (isset($korkolasku) and $korkolasku == "kylla") {
		$viivastyskorko = $orow['viivastyskorko'];
	}

	$query = "	UPDATE lasku
				SET alatila 			= 'V',
				arvo 					= '$arvosumma',
				arvo_valuutassa 		= '$arvosumma_valuutassa',
				kate 					= '$katesumma',
				summa 					= '$loppusumma',
				summa_valuutassa 		= '$loppusumma_valuutassa',
				pyoristys				= '$pyorerotus',
				pyoristys_valuutassa	= '$pyorerotus_valuutassa',
				erpcm 					= $erapvm,
				kapvm 					= $kassa_erapvm,
				kasumma 				= '$kassa_loppusumma',
				kasumma_valuutassa		= '$kassa_loppusumma_valuutassa',
				viikorkopros 			= '$viivastyskorko',
				laskuttaja 				= '$kukarow[kuka]',
				tapvm 					= '$laskutuspvm',
				maksuehto 				= '$orow[maksuehto]',
				laskutettu 				= now()
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