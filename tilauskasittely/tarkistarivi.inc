<?php
	//sis‰‰n tulee $row jossa on tilausrivi kaikki tiedot

	$riviok 				= 0;
	$saako_hyvaksya 		= 0;
	$saako_jalkitoimittaa 	= 0;
	$varaosavirhe 			= "";

	$tquery	= "select * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$row[tuoteno]'";
	$tres	= mysql_query($tquery) or pupe_error($tquery);
	$trow 	= mysql_fetch_array($tres); // haetaan tuotteen tiedot..

	if (mysql_num_rows($tres) == 0) {
		$varaosavirhe .= t("VIRHE: Tuotetta ei lˆydy!")."<br>";
		$riviok++;
		$tilausok++;
	}

	if ($row["hinta"] == 0 and $row["var2"] != "OK" and $row["tyyppi"] != "V" and strtoupper($trow["tuotetyyppi"]) != 'R' and strtoupper($trow["tuotetyyppi"]) != 'M' and $toim != "VALMISTAVARASTOON" and $toim != "SIIRTOLISTA" and $toim != "SIIRTOTYOMAARAYS") {
		$varaosavirhe .= t("HUOM: Hinta puuttuu!")."<br>";
		$riviok++;
		$tilausok++;
		$saako_hyvaksya++;
	}

	if ($row["varattu"] == 0 and in_array($row["var"], array('','H')) and $row["tyyppi"] != "E" and $row["tyyppi"] != "G") {
		$varaosavirhe .= t("VIRHE: M‰‰r‰ puuttuu!")."<br>";
		$riviok++;
		$tilausok++;
	}
	
	if ($row["var"] == "J" and $row["tyyppi"] != "E") {
		if (($yhtiorow["varaako_jt_saldoa"] == "") and $row["jt"] == 0) {
			$varaosavirhe .= t("VIRHE: M‰‰r‰ puuttuu!")."<br>";
			$riviok++;
			$tilausok++;
		}
		if ($yhtiorow["varaako_jt_saldoa"] != "" and $row["varattu"] == 0) {
			$varaosavirhe .= t("VIRHE: M‰‰r‰ puuttuu!")."<br>";
			$riviok++;
			$tilausok++;
		}	
	}

	if ($row["jt"] == 0 and in_array($row["var"], array('S','U','T')) and $row["tyyppi"] != "E") {
		$varaosavirhe .= t("VIRHE: M‰‰r‰ puuttuu!")."<br>";
		$riviok++;
		$tilausok++;
	}

	if ($row["hinta"] < 0) {
		$varaosavirhe .= t("VIRHE: Hinta ei voi olla negatiivinen!")."<br>";
		$riviok++;
		$tilausok++;
	}

	if (($row["jt"] < 0 and in_array($row["var"], array('J','S','U','T'))) or ($row["varattu"] < 0 and $row["tyyppi"] == "E")) {
		$varaosavirhe .= t("VIRHE: M‰‰r‰ ei saa olla negatiivinen!")."<br>";
		$riviok++;
		$tilausok++;
	}

	if ($row["var"] == "J" and $row["tyyppi"] == "E") {
		$varaosavirhe .= t("VIRHE: Et voi syˆtt‰‰ jt-rivi‰ ennakkotilaukseen!")."<br>";
		$riviok++;
		$tilausok++;
	}

	//	Pakkokommentoitava tuote
	if ($trow["kommentoitava"] != "" and $row["kommentti"] == "") {
		$varaosavirhe .= t("VIRHE: Tuotetta on kommentoitava!")."<br>";
		$riviok++;
		$tilausok++;
	}

	if (!in_array($row["var"], array('','P','J','H','S','T','U','V','A','B'))) {
		// T‰ss‰ varrien selitykset
		// P - Puute
		// J - Jt
		// H - V‰kisinhyv‰ksytty
		// S - Suoratoimitus pupesoft toimittajalta suoraan asiakkaalle
		// T - Tilataan joltain toimittajalta omaan varastoon
		// U - Tilataan joltain toimittajalta suoraan asiakkaalle
		// V - Tehdaslis‰varusterivi
		// A - Myyntitilirivi joka laskutetaan asiakkaalta
		// B - Myyntitilirivi joka palautetaan myyntitilist‰ omaan varastoon


		$varaosavirhe .= t("VIRHE: Var voi olla tyhj‰, P, J, H, S, T, U, V, A, tai B!")."<br>";
		$riviok++;
		$tilausok++;
	}

	if ($row["var"] != '' and $toim == "SIIRTOTYOMAARAYS") {
		$varaosavirhe .= t("VIRHE: Sis‰isell‰ tyˆm‰‰r‰yksell‰ Var pit‰‰ olla tyhj‰!")."<br>";
		$riviok++;
		$tilausok++;
	}

	if (($row["perheid2"] == $row["tunnus"] or $row["perheid2"] == 0) and $row["sarjanumeroseuranta"] == "" and $toim == "SIIRTOTYOMAARAYS") {
		$varaosavirhe .= t("VIRHE: Jatkojalostettava tuote on oltava sarjanumeroeurannassa!")."<br>";
		$riviok++;
		$tilausok++;
	}
	
	if (($row["perheid2"] == $row["tunnus"] or $row["perheid2"] == 0) and $row["varattu"] != 1 and $toim == "SIIRTOTYOMAARAYS") {
		$varaosavirhe .= t("VIRHE: Jatkojalostettava m‰‰r‰ on oltava yksi!")."<br>";
		$riviok++;
		$tilausok++;
	}

	if ($row["perheid2"] == 0 and $row["sarjanumeroseuranta"] != "" and $toim == "SIIRTOTYOMAARAYS") {
		$varaosavirhe .= t("HUOM: Lis‰varusteita ei ole liitetty!")."<br>";
		$riviok++;
		$tilausok++;
	}

	if ($row["varattu"] <= 0 and $toim == "SIIRTOTYOMAARAYS") {
		$varaosavirhe .= t("HUOM: M‰‰r‰ on oltava suurempi kuin nolla!")."<br>";
		$riviok++;
		$tilausok++;
	}

	if (!in_array($row["netto"], array('','N','E'))) {
		$varaosavirhe .= t("VIRHE: Netto voi olla tyhj‰, N tai E!")."<br>";
		$riviok++;
		$tilausok++;
	}

	if ($row["netto"] == 'N' and (float) $row["ale"] != 0) {
		$varaosavirhe .= t("VIRHE: Nettoriville ei voi antaa alennusta!")."<br>";
		$riviok++;
		$tilausok++;
	}

	if ($row["ale"]!='' and ($row["ale"]<0 or 100<$row["ale"])) {
		$varaosavirhe .= t("VIRHE: Alennus on oltava 0-100!")."<br>";
		$riviok++;
		$tilausok++;
	}

	if ($kukarow["extranet"] == "" and in_array($trow["status"], array("P","X")) and $row["var2"] != "OK") {
		$varaosavirhe .= t("HUOM: T‰m‰ on poistuva tuote!")."<br>";
		$riviok++;
	}

	if (in_array($trow["status"], array("P","X")) and $row["var"] == "J") {
		$varaosavirhe .= t("VIRHE: T‰m‰ on poistuva tuote, ei voida j‰tt‰‰ j‰lkitoimitukseen!")."<br>";
		$riviok++;
		$tilausok++;
	}

	if (trim($trow["vienti"]) != '' and $row["tyyppi"] != "V" and $row["tyyppi"] != "W") {

		// vientikieltok‰sittely:
		// +maa tarkoittaa ett‰ myynti on kielletty t‰h‰n maahan
		// -maa tarkoittaa ett‰ ainoastaan t‰h‰n maahan saa myyd‰
		// eli n‰ytet‰‰n vaan tuotteet jossa vienti kent‰ss‰ on tyhj‰‰ tai -maa.. ja se ei saa olla +maa

		if (strpos(strtoupper($trow["vienti"]), strtoupper("+$laskurow[toim_maa]")) !== FALSE and strpos($trow["vienti"], "+") !== FALSE) {
			//ei saa myyd‰ t‰h‰n maahan
			$varaosavirhe .= t("VIRHE: T‰t‰ tuotetta ei saa myyd‰ maahan")." $laskurow[toim_maa]!<br>";
	 		$riviok++;
	 		$tilausok++;
			$kielletty++;
		}

		if (strpos(strtoupper($trow["vienti"]), strtoupper("-$laskurow[toim_maa]")) === FALSE and strpos($trow["vienti"], "-") !== FALSE) {
			//ei saa myyd‰ t‰h‰n maahan
			$varaosavirhe .= t("VIRHE: T‰t‰ tuotetta ei saa myyd‰ maahan")." $laskurow[toim_maa]!!<br>";
	 		$riviok++;
	 		$tilausok++;
			$kielletty++;
		}

	}

	if ($row["var"] == 'S') {
		// otetaan maksuehto selville..
		$query = "	select *
					from maksuehto
					where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
		$maksuehtoresult = mysql_query($query) or pupe_error($query);
		$maksuehtorow = mysql_fetch_array($maksuehtoresult);

		if ($maksuehtorow["jv"] != "") {
			$varaosavirhe .= t("VIRHE: Et voi suoratoimittaa tuotetta jos maksuehtona on j‰lkivaatimus!")."<br>";
			$riviok++;
			$tilausok++;
		}
	}


	// Otataan oieka kappalem‰‰r‰ k‰sittelyyn
	if ($row["var"] == 'J' or $row["var"] == 'S' or $row["var"] == 'T' or $row["var"] == 'U') {
		$kpl_st = $row['jt'];
	}
	elseif($row["var"] == 'P') {
		$kpl_st = $row['tilkpl'];
	}
	else {
		$kpl_st = $row['varattu'];
	}

	$paikat	= "";

	//T‰ss‰ on kaikki saldoihin ja varastopaikkoihin liittyv‰t tsekit, eli EI tehd‰ saldottomille tuotteille
	if ($trow["ei_saldoa"] == "" and $row["tyyppi"] != "E") {
		// tarkistetaan varaston tyyppi
		$query = "	SELECT *
					FROM varastopaikat
					WHERE
					concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper('$row[hyllyalue]') ,5,'0'),lpad(upper('$row[hyllynro]') ,5,'0')) and
					concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper('$row[hyllyalue]') ,5,'0'),lpad(upper('$row[hyllynro]') ,5,'0'))
					and yhtio = '$kukarow[yhtio]'";
		$varesult = mysql_query($query) or pupe_error($query);
		$varow = mysql_fetch_array($varesult);

		if ($varow["tyyppi"] == "V" and $laskurow["varasto"] != $varow["tunnus"] and $row["var2"] != "OK") {
			$varaosavirhe .= "HUOM: Tuote sijaitsee erikoisvarastossa!<br>";
			$riviok++;
			$tilausok++;
			$saako_hyvaksya++;
		}
		
		if ($yhtiorow["saldo_kasittely"] == "T") {
			$saldoaikalisa = $row["kerayspvm"];
		}
		else {
			$saldoaikalisa = "";
		}
				
		$paikatlask 	= 0;
		$selpaikka		= "";
		$selpaikkamaa	= "";
		$kotipaikatlask = 0;
		$omapaikatraja 	= 2;
		$selpaikkamyytavissa = 0;

		if ($kukarow["extranet"] == "" and ($toim == "TARJOUS" or $yhtiorow["tee_osto_myyntitilaukselta"] != '') and $row["var"] != 'V' and $toim != "SIIRTOTYOMAARAYS") {
			// Katsotaan onko t‰lle tuotteelle toimittajaa ja oletusvienti on jotain vaihto-omaisuutta (Tilaa-toimittajalta drop-downiin mukaan)
			$query = "	SELECT tyyppi_tieto, liitostunnus, toimi.nimi
						FROM tuotteen_toimittajat use index (yhtio_tuoteno)
						JOIN toimi ON (toimi.yhtio = tuotteen_toimittajat.yhtio and toimi.tunnus = tuotteen_toimittajat.liitostunnus)
						WHERE tuotteen_toimittajat.yhtio = '$kukarow[yhtio]'
						and tuotteen_toimittajat.tuoteno = '$row[tuoteno]'";
			$toimpaikres  = mysql_query($query) or pupe_error($query);

			if(mysql_num_rows($toimpaikres) > 0) {
				$omapaikatraja 	= 1;
			}
		}

		//Katotaan voidaanko t‰m‰ myyd‰ joltain toiselta paikalta
		if ($kpl_st > 0 and ($trow["sarjanumeroseuranta"] == "E" or $trow["sarjanumeroseuranta"] == "F")) {

			$paikat .= "<option value='EIERAAVIELA#EIEI'>".t("Valitse er‰")."</option>";
						
			if ($trow["sarjanumeroseuranta"] == "F") {
				$pepvmlisa1 = " sarjanumeroseuranta.parasta_ennen, ";
				$pepvmlisa2 = ", 17";
			}
			else {
				$pepvmlisa1 = "";
				$pepvmlisa2 = "";
			}
			$query = "	SELECT tuote.yhtio, tuote.tuoteno, tuote.ei_saldoa, varastopaikat.tunnus varasto, varastopaikat.tyyppi varastotyyppi, varastopaikat.maa varastomaa,
						tuotepaikat.oletus, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso,
						sarjanumeroseuranta.sarjanumero era,
						sarjanumeroseuranta.ostorivitunnus,
						$pepvmlisa1
						concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'),lpad(upper(tuotepaikat.hyllyvali), 5, '0'),lpad(upper(tuotepaikat.hyllytaso), 5, '0')) sorttauskentta,
						varastopaikat.nimitys, if(varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi
			 			FROM tuote
						JOIN tuotepaikat ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno
						JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
						and concat(rpad(upper(varastopaikat.alkuhyllyalue),  5, '0'),lpad(upper(varastopaikat.alkuhyllynro),  5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
						and concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'),lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
						JOIN sarjanumeroseuranta ON sarjanumeroseuranta.yhtio = tuote.yhtio
						and sarjanumeroseuranta.tuoteno = tuote.tuoteno
						and sarjanumeroseuranta.hyllyalue = tuotepaikat.hyllyalue
						and sarjanumeroseuranta.hyllynro  = tuotepaikat.hyllynro
						and sarjanumeroseuranta.hyllyvali = tuotepaikat.hyllyvali
						and sarjanumeroseuranta.hyllytaso = tuotepaikat.hyllytaso
						and sarjanumeroseuranta.myyntirivitunnus = 0
						and sarjanumeroseuranta.era_kpl != 0
						WHERE tuote.yhtio = '$kukarow[yhtio]'
						and tuote.tuoteno = '$row[tuoteno]'
						GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16 $pepvmlisa2
						ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";
		}
		else {
			$query = "	SELECT tuote.yhtio, tuote.tuoteno, tuote.ei_saldoa, varastopaikat.tunnus varasto, varastopaikat.tyyppi varastotyyppi, varastopaikat.maa varastomaa,
						tuotepaikat.oletus, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso,
						concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0')) sorttauskentta,
						varastopaikat.nimitys, if(varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi
			 			FROM tuote
						JOIN tuotepaikat ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno
						JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
						and concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
						and concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
						WHERE tuote.yhtio = '$kukarow[yhtio]'
						and tuote.tuoteno = '$row[tuoteno]'
						ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";
		}
		$omavarastores = mysql_query($query) or pupe_error($query);

		//Tutkitaan voidaanko t‰t‰ rivi suoratoimittaa mist‰‰n varastosta
		if ($trow["sarjanumeroseuranta"] == "" and $kpl_st > 0) {
			$suora_tuoteno 	= $row["tuoteno"];
			$suora_kpl 		= $kpl_st;

			require("suoratoimitusvalinta.inc");
		}

		// K‰‰yd‰‰n l‰pi oman varaston paikat
		if ( in_array($yhtiorow["puute_jt_oletus"], array('H','O')) or
			(in_array($row["var"], array('','H','S','T','U','J')) and mysql_num_rows($omavarastores) >= $omapaikatraja) or
			(in_array($row["var"], array('S','T','U','J')) and mysql_num_rows($omavarastores) > 0)) {

			if ($trow["sarjanumeroseuranta"] == "E" or $trow["sarjanumeroseuranta"] == "F") {
		   		$query	= "	SELECT sarjanumeroseuranta.sarjanumero era
		   					FROM sarjanumeroseuranta
		   					WHERE yhtio = '$kukarow[yhtio]'
							and tuoteno = '$row[tuoteno]'
		   					and myyntirivitunnus = '$row[tunnus]'
		   					LIMIT 1";
		   		$sarjares = mysql_query($query) or pupe_error($query);
		   		$sarjarow = mysql_fetch_array($sarjares);
			}

			while($alkurow = mysql_fetch_array($omavarastores)) {

				if ($alkurow["hyllyalue"] != "!!M" and
					($alkurow["varastotyyppi"] != "E" or
					$laskurow["varasto"] == $alkurow["varasto"] or
					($alkurow["hyllyalue"] == $row["hyllyalue"] and $alkurow["hyllynro"] == $row["hyllynro"] and $alkurow["hyllyvali"] == $row["hyllyvali"] and $alkurow["hyllytaso"] == $row["hyllytaso"]))) {

					list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($row["tuoteno"], '', '', '', $alkurow["hyllyalue"], $alkurow["hyllynro"], $alkurow["hyllyvali"], $alkurow["hyllytaso"], $laskurow["toim_maa"], $saldoaikalisa, $alkurow["era"]);
					
					$myytavissa = (float) $myytavissa;
					
					if ($trow["sarjanumeroseuranta"] == "E" or $trow["sarjanumeroseuranta"] == "F") {
						//Jos er‰ on keksitty k‰sin t‰‰lt‰ ker‰yksest‰
						$query = "	SELECT tyyppi, (varattu+kpl+jt) kpl, tunnus
									FROM tilausrivi
									WHERE yhtio = '$kukarow[yhtio]'
									and tuoteno = '$row[tuoteno]'
									and tunnus	= '$alkurow[ostorivitunnus]'";
						$lisa_res = mysql_query($query) or pupe_error($query);
						$lisa_row = mysql_fetch_array($lisa_res);
					}
					
					if (in_array($yhtiorow["puute_jt_oletus"], array('H','O')) or
						$myytavissa >= $kpl_st or
						($row["var"] != "P" and $toim != "TARJOUS" and
						(($alkurow["hyllyalue"] == $row["hyllyalue"] and $alkurow["hyllynro"] == $row["hyllynro"] and $alkurow["hyllyvali"] == $row["hyllyvali"] and $alkurow["hyllytaso"] == $row["hyllytaso"] and $trow["sarjanumeroseuranta"] != "E" and $trow["sarjanumeroseuranta"] != "F") or
						 ($alkurow["hyllyalue"] == $row["hyllyalue"] and $alkurow["hyllynro"] == $row["hyllynro"] and $alkurow["hyllyvali"] == $row["hyllyvali"] and $alkurow["hyllytaso"] == $row["hyllytaso"] and ($trow["sarjanumeroseuranta"] == "E" or $trow["sarjanumeroseuranta"] == "F") and ($lisa_row["tyyppi"] == "O" or $lisa_row["kpl"] < 0 or $lisa_row["tunnus"] == $row["tunnus"]) and $sarjarow["era"] == $alkurow["era"])))) {

						$sel = "";

						if ($trow["sarjanumeroseuranta"] != "E" and $trow["sarjanumeroseuranta"] != "F" and !in_array($row["var"], array("P","S")) and $alkurow["hyllyalue"] == $row["hyllyalue"] and $alkurow["hyllynro"] == $row["hyllynro"] and $alkurow["hyllyvali"] == $row["hyllyvali"] and $alkurow["hyllytaso"] == $row["hyllytaso"]) {
							$sel = "SELECTED";

							$selpaikka = "$alkurow[hyllyalue] $alkurow[hyllynro] $alkurow[hyllyvali] $alkurow[hyllytaso]";
							$selpaikkamaa = $alkurow['varastomaa'];
							$selpaikkamyytavissa = $myytavissa;
						}
						elseif (($trow["sarjanumeroseuranta"] == "E" or $trow["sarjanumeroseuranta"] == "F") and $sarjarow["era"] == $alkurow["era"] and !in_array($row["var"], array("P","S")) and $alkurow["hyllyalue"] == $row["hyllyalue"] and $alkurow["hyllynro"] == $row["hyllynro"] and $alkurow["hyllyvali"] == $row["hyllyvali"] and $alkurow["hyllytaso"] == $row["hyllytaso"]) {
							$sel = "SELECTED";

							$selpaikka = "$alkurow[hyllyalue] $alkurow[hyllynro] $alkurow[hyllyvali] $alkurow[hyllytaso]";
							$selpaikkamaa = $alkurow['varastomaa'];
							$selpaikkamyytavissa = $myytavissa;

							if ($trow["sarjanumeroseuranta"] == "E" or $trow["sarjanumeroseuranta"] == "F") {
								$selpaikka .= " $alkurow[era]";								
							}
						}

						$paikat .= "<option value='$alkurow[hyllyalue]#$alkurow[hyllynro]#$alkurow[hyllyvali]#$alkurow[hyllytaso]";

						if ($trow["sarjanumeroseuranta"] == "E" or $trow["sarjanumeroseuranta"] == "F") {
							$paikat .= "#$alkurow[era]";
						}

						$title  = "";

						if (strtoupper($alkurow['varastomaa']) != strtoupper($yhtiorow['maa'])) {
							$title .= strtoupper($alkurow['varastomaa'])." ";
						}

						$title .= "$alkurow[hyllyalue] $alkurow[hyllynro] $alkurow[hyllyvali] $alkurow[hyllytaso]";

						if ($trow["sarjanumeroseuranta"] == "E" or $trow["sarjanumeroseuranta"] == "F") {
							$title .= ", $alkurow[era]";
						}

						$title .= " ($myytavissa)";

						if ($trow["sarjanumeroseuranta"] == "F") {
							$title .= " ".tv1dateconv($alkurow["parasta_ennen"]);
						}

						$paikat .= "' $sel title='$title'>$title</option>";

						$paikatlask++;
						$kotipaikatlask++;
					}
				}
			}
		}
		//	Jos ollaan livahdettu puutteeksi koitetaan josko t‰t‰ voitasiin myyd‰ erikokisvarastosta
		elseif($row["var"] == "P" and mysql_num_rows($omavarastores) > 0) {
			while($alkurow = mysql_fetch_array($omavarastores)) {

				list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($row["tuoteno"], '', '', '', $alkurow["hyllyalue"], $alkurow["hyllynro"], $alkurow["hyllyvali"], $alkurow["hyllytaso"], $laskurow["toim_maa"], $saldoaikalisa, $alkurow["era"]);

				if($myytavissa > 0 and ($alkurow["varastotyyppi"] == "E" or $alkurow["hyllyalue"] == "!!M")) {
					
					if($kukarow["extranet"] != "") {
						if ($laskurow["toim_maa"] == $alkurow["varastomaa"]) {
							if ($yhtiorow["erikoisvarastomyynti_alarajasumma_rivi"] != 0 and $yhtiorow["erikoisvarastomyynti_alarajasumma_rivi"] < $row["hinta"]*$row["tilkpl"]*(1-$row["ale"]/100)) {
								$varaosakommentti .= t("Tuote saatavissa asiakaspalvelun kautta")."!<br>";
							}							
						}					
						
					}
					else {
						$varaosakommentti .= t("Tuote saatavilla erikoisvarastossa")."!<br>";
					}
						
					break;
				}
			}
		}	
		
		// K‰‰yd‰‰n l‰pi tuotteen toimittajat
		if ($kukarow["extranet"] == "" and in_array($row["var"], array('','H','S','T','U','J','P')) and ($toim == "TARJOUS" or $yhtiorow["tee_osto_myyntitilaukselta"] != '') and $kpl_st > 0 and $toim != "SIIRTOTYOMAARAYS") {
			if ($kotipaikatlask == 0 and $trow["sarjanumeroseuranta"] != "E" and $trow["sarjanumeroseuranta"] != "F") {
				$paikat = "<option value=''></option>".$paikat;
			}

			while ($krow  = mysql_fetch_array($toimpaikres)) {
				$sel1 = "";
				$sel2 = "";

				if ($row["toimittajan_tunnus"] == $krow["liitostunnus"] and $row["hyllyalue"] == "" and $row["hyllynro"] == "") {
					if ($row["var"] == 'T' or ($row["var"] == "J" and $row["suoraan_laskutukseen"] == "")) {
						$sel1 = "SELECTED";

						$selpaikka = "$krow[nimi] --> ".t("tilaa")." ".t("varastoon");
						$selpaikkamaa = "";
						$selpaikkamyytavissa = 0;
					}
					else {
						$sel2 = "SELECTED";

						$selpaikka = "$krow[nimi] --> ".t("tilaa")." ".t("asiakkaalle");
						$selpaikkamaa = "";
						$selpaikkamyytavissa = 0;
					}
				}

				// tallennetaan riville toimittajan tunnus
				$title = "$krow[nimi] --> ".t("tilaa")." ".t("varastoon");
				$paikat .= "<option value='°°°$krow[liitostunnus]' title='$title' $sel1>$title</option>";

				if ($yhtiorow["tee_osto_myyntitilaukselta"] == "Z" or $yhtiorow["tee_osto_myyntitilaukselta"] == "Y") {
					$title = "$krow[nimi] --> ".t("tilaa")." ".t("asiakkaalle");
					$paikat .= "<option value='!!!$krow[liitostunnus]' title='$title' $sel2>$title</option>";
				}

				$paikatlask=$paikatlask+2;
			}
		}
		elseif ($kotipaikatlask == 0 and $kpl_st > 0) {
			$paikat = "<option value='' $sel>".t("J‰t‰ puutteeksi")."</option>".$paikat;
		}

		if ($paikat != "" and $paikatlask > 1) {
			$paikat = "<select name='paikka' style='width:120px;' onchange='submit();'>".$paikat."</select>";
		}
		else {
			$paikat = "";
		}
	}
	elseif ($trow["ei_saldoa"] != "" and $row["tyyppi"] != "E" and $kpl_st > 0 and $row["tunnus"] == $row["perheid"] and in_array($row["var"], array('S','P','J'))) {

		// Suoratoimitush‰kki
		$query = "	select sum(if(var='S', 1, 0)) suoria, count(*) lapsia, count(distinct tilaajanrivinro) toimittajia, group_concat(distinct tilaajanrivinro) toimittaja
					from tilausrivi
					where yhtio = '$kukarow[yhtio]'
					and otunnus = '$row[otunnus]'
					and perheid = '$row[tunnus]'
					and tunnus != '$row[tunnus]'";
		$maksuehtoresult = mysql_query($query) or pupe_error($query);
		$maksuehtorow = mysql_fetch_array($maksuehtoresult);

		if ($maksuehtorow["suoria"] == $maksuehtorow["lapsia"] and $maksuehtorow["toimittajia"] == 1) {
			$query = "	update tilausrivi set
						var			= '',
						varattu 	= '$kpl_st',
						jt 			= 0
						where yhtio = '$kukarow[yhtio]'
						and tunnus 	= '$row[tunnus]'";
			$updres = mysql_query($query) or pupe_error($query);

			/*
			$query = "	update tilausrivi set
						kommentti = concat_ws(' ', kommentti, '".t("Tuoteperhe").": $row[nimitys]')
						where yhtio = '$kukarow[yhtio]'
						and otunnus = '$row[otunnus]'
						and perheid = '$row[tunnus]'
						and tunnus != '$row[tunnus]'";
			$updres = mysql_query($query) or pupe_error($query);
			*/

			$row["var"] = "";
		}

		$sel1 = $sel2 = "";

		if ($row["var"] == "P") {
			$sel1 = "SELECTED";
		}
		elseif ($row["var"] == "J") {
			$sel2 = "SELECTED";
		}
		else {
			$sel3 = "SELECTED";
		}

		$paikat  = "<option value=''  $sel1>".t("Oletus")."</option>";
		$paikat .= "<option value='P'  $sel1>".t("J‰t‰ puutteeksi")."</option>";
		$paikat .= "<option value='J' $sel2>".t("J‰lkitoimitus")."</option>";
		$paikat .= "<option value='X' $sel3>".t("Suoratoimita")."</option>";
		$paikat = "<select name='var' onchange='submit();'>".$paikat."</select>";
	}

	//	Onko t‰t‰ varattuna siirtolistalla?
	if ($kukarow["extranet"] == "" and $trow["ei_saldoa"] == "" and $row["tyyppi"] != "E" and $kpl_st > 0 and in_array($row["var"], array("J","U","T","P"))) {
		$query = "	SELECT kohde.nimitys kohde, lahde.nimitys lahde, tila, alatila, sum(varattu+jt+kpl) kpl, lasku.tunnus
					FROM lasku USE INDEX (tila_index)
					RIGHT JOIN tilausrivi USE INDEX (yhtio_otunnus) ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi != 'D' and tuoteno = '{$trow["tuoteno"]}')
					LEFT JOIN varastopaikat lahde USE INDEX (PRIMARY) ON (lahde.yhtio = lasku.yhtio and lahde.tunnus = lasku.varasto)
					LEFT JOIN varastopaikat kohde USE INDEX (PRIMARY) ON (kohde.yhtio = lasku.yhtio and kohde.tunnus = lasku.clearing)
					WHERE lasku.yhtio = '{$kukarow["yhtio"]}' and tila = 'G' and alatila != 'V'
					GROUP BY tuoteno, lasku.tunnus";
		$siirtores = mysql_query($query) or pupe_error($query);

		while($siirtorow = mysql_fetch_array($siirtores)) {
			$laskutyyppi = $siirtorow["tila"];
			$alatila	 = $siirtorow["alatila"];

			//tehd‰‰n selv‰kielinen tila/alatila
			require "inc/laskutyyppi.inc";

			$varaosakommentti .= ("Siirtolistalla").": {$siirtorow["kpl"]}{$trow["yksikko"]} {$siirtorow["lahde"]} -> {$siirtorow["kohde"]} <i>($alatila)</i><br>";
		}
	}

	//	Onko t‰t‰ ostotilauksella?
	if ($kukarow["extranet"] == "" and $trow["ei_saldoa"] == "" and $row["tilausrivilinkki"] > 0) {
		$query = "	SELECT tilausrivi.otunnus, lasku.nimi
					FROM tilausrivi
					JOIN lasku ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus)
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
					and tilausrivi.tunnus  = '$row[tilausrivilinkki]'";
		$siirtores = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($siirtores) > 0) {
			$siirtorow = mysql_fetch_array($siirtores);
			$varaosakommentti .= "Ostotilauksella: $siirtorow[nimi] $siirtorow[otunnus]<br>";
		}
	}

	if ($row["alv"] == -999.99) {
		$varaosavirhe .= t("VIRHE: Tuotteelle ei lˆytynyt ALV-prosenttia asiakkaan maahan!")."<br>";
		$riviok++;
		$tilausok++;
		$saako_jalkitoimittaa++;
		$saako_hyvaksya = 0;
	}

?>
