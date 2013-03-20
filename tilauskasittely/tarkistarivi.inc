<?php
	//sis‰‰n tulee $row jossa on tilausrivi kaikki tiedot

	$riviok 				= 0;
	$saako_hyvaksya 		= 0;
	$saako_jalkitoimittaa 	= 0;
	$vastaavattuotteet		= 0;
	$varaosavirhe 			= "";
	$vastaavat_html			= "";

	$tquery	= "SELECT * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$row[tuoteno]'";
	$tres	= pupe_query($tquery);
	$trow 	= mysql_fetch_assoc($tres); // haetaan tuotteen tiedot..

	if ($toim == "YLLAPITO") {
		// Jos ollaan syˆtetty poikkeavat sopimuksen alku-/loppup‰iv‰t tilausriville, tarkistetaan ett‰ ne on oikeelliset
		if ($row["kerayspvm"] != '0000-00-00' or $row["toimaika"] != '0000-00-00') {

			$sopimusquery = "	SELECT sopimus_alkupvm, sopimus_loppupvm
								FROM laskun_lisatiedot
								WHERE yhtio = '{$kukarow["yhtio"]}'
								AND otunnus = '{$laskurow["tunnus"]}'";
			$sopimus_result = pupe_query($sopimusquery);
			$sopimus_row = mysql_fetch_assoc($sopimus_result);

			// tilausrivi.kerayspvm == poikkeava alkup‰iv‰
			if ($row["kerayspvm"] != '0000-00-00' and $row["kerayspvm"] < $sopimus_row["sopimus_alkupvm"]) {
				$varaosavirhe .= t("VIRHE: Poikkeava alkup‰iv‰ ei voi olla vanhempi kun sopimuksen alkup‰iv‰!")."<br>";
				$riviok++;
				$tilausok++;
			}

			if ($row["kerayspvm"] != '0000-00-00' and $row["kerayspvm"] > $sopimus_row["sopimus_loppupvm"]) {
				$varaosavirhe .= t("VIRHE: Poikkeava alkup‰iv‰ ei voi olla uudempi kun sopimuksen loppup‰iv‰!")."<br>";
				$riviok++;
				$tilausok++;
			}

			// tilausrivi.toimaika == poikkeava loppup‰iv‰
			if ($row["toimaika"] != '0000-00-00' and $row["toimaika"] < $sopimus_row["sopimus_alkupvm"]) {
				$varaosavirhe .= t("VIRHE: Poikkeava loppup‰iv‰ ei voi olla vanhempi kun sopimuksen alkup‰iv‰!")."<br>";
				$riviok++;
				$tilausok++;
			}

			if ($row["toimaika"] != '0000-00-00' and $row["toimaika"] > $sopimus_row["sopimus_loppupvm"] and $sopimus_row["sopimus_loppupvm"] != "0000-00-00") {
				$varaosavirhe .= t("VIRHE: Poikkeava loppup‰iv‰ ei voi olla uudempi kun sopimuksen loppup‰iv‰!")."<br>";
				$riviok++;
				$tilausok++;
			}

			if ($row["kerayspvm"] != '0000-00-00' and $row["toimaika"] != '0000-00-00' and $row["kerayspvm"] > $row["toimaika"]) {
				$varaosavirhe .= t("VIRHE: Poikkeava alkup‰iv‰ ei voi olla uudempi kun poikkeava loppup‰iv‰!")."<br>";
				$riviok++;
				$tilausok++;
			}
		}
	}

	if ($trow['panttitili'] == 'K' and in_array($toim, array('PIKATILAUS', 'RIVISYOTTO', 'REKLAMAATIO'))) {
		$query = "SELECT panttitili FROM asiakas WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$laskurow['liitostunnus']}'";
		$chk_res = pupe_query($query);
		$chk_row = mysql_fetch_assoc($chk_res);

		if ($chk_row['panttitili'] == 'K' and ($toim == 'RIVISYOTTO' or $toim == 'PIKATILAUS' or $toim == 'REKLAMAATIO')) {
			if ($row['varattu'] < 0) {
				if ($row['var2'] == 'PANT') {
					$varaosavirhe .= t("HUOM: Panttituote, hyvitet‰‰n p‰itt‰in alkuper‰inen panttiveloitus")."<br>";
				}
				else {
					$varaosavirhe .= t("HUOM: Panttitilillinen tuote, tuote hyvitet‰‰n panttitililt‰")."<br>";
				}
			}
			else {
				if ($row['var2'] == 'PANT') {
					$varaosavirhe .= t("HUOM: Panttituote, veloitetaan asiakkaalta")."<br>";
				}
				else {
					$varaosavirhe .= t("HUOM: Panttitilillinen tuote, veloitus siirret‰‰n panttitilille")."<br>";
				}
			}
		}
	}

	if ($yhtiorow["korvaavat_hyvaksynta"] != "" and $alkupera_trow["tuoteno"] != $row["tuoteno"] and !in_array($row["var2"], array("OK", "PK")) and $korvattiin_korvaavalla == "HIIOHEI!") {
		$varaosavirhe .= t("HUOM: t‰m‰ on korvaava tuote, haluatko hyv‰ksy‰?")."<br>";
		$riviok++;
		$tilausok++;
		$saako_hyvaksya++;
	}

	if ($kukarow['extranet'] != '' and $trow['sarjanumeroseuranta'] != '' and $row['varattu'] != 1) {
		$varaosavirhe .= t("HUOM: T‰m‰ on sarjanumeroseurannallinen tuote. Kappalem‰‰r‰ pit‰‰ olla 1")."!<br>";
		$riviok++;
		$tilausok++;
	}

	$korvattiin_korvaavalla = "";

	if (mysql_num_rows($tres) == 0) {
		$varaosavirhe .= t("VIRHE: Tuotetta ei lˆydy!")."<br>";
		$riviok++;
		$tilausok++;
	}

	if ($row["var2"] == "PK" and ($toim == "REKLAMAATIO" or $toim == "EXTRANET_REKLAMAATIO") AND $yhtiorow['reklamaation_hinnoittelu'] == 'K') {
		$varaosavirhe .= t("Palautuskielto!")."<br>";
		$riviok++;
		$tilausok++;
	}

	if (
		$row["hinta"] == 0
		and $row["tyyppi"] != "V"
		and !in_array($row["var2"], array("OK", "PK"))
		and !in_array(strtoupper($trow["tuotetyyppi"]), array('R', 'M'))
		and !in_array($toim, array("VALMISTAVARASTOON", "SIIRTOLISTA", "SIIRTOTYOMAARAYS", "MYYNTITILI"))
	) {
		if ($trow['ei_saldoa'] == '' and ($row['perheid'] == 0 or $row['perheid'] == $row['tunnus'])) {
			$varaosavirhe .= t("HUOM: Hinta puuttuu!")."<br>";
			$riviok++;
			$tilausok++;
			$saako_hyvaksya++;
		}
	}

	if ($row["varattu"] == 0 and in_array($row["var"], array('','H')) and $row["tyyppi"] != "E" and $row["tyyppi"] != "G") {
		if ($kukarow['extranet'] != '' and $row['positio'] == 'Ei varaa saldoa') {
			// $varaosavirhe .= t("HUOM: Rivi on liian vanha ja tuote ei varaa saldoa")."! ($row[tilkpl] ".t("kpl").")<br/>";
		}
		else {
			$varaosavirhe .= t("VIRHE: M‰‰r‰ puuttuu!")."<br>";
		}
		$riviok++;
		$tilausok++;
	}

	if (in_array($row["var"], array('S','U','T','R', 'J')) and $row["tyyppi"] != "E") {
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

	if ($row["hinta"] < 0) {
		$varaosavirhe .= t("VIRHE: Hinta ei voi olla negatiivinen!")."<br>";
		$riviok++;
		$tilausok++;
	}

	if (
		(in_array($row["var"], array('J' ,'S' ,'U', 'T')) and $row["jt"] < 0)
		or (in_array($row["tyyppi"], array('V', 'W', 'M', 'E')) and $row["varattu"] < 0 )
	) {
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

	if (!in_array($row["var"], array('','P','J','H','S','T','U','V','A','B','R'))) {
		// T‰ss‰ varrien selitykset
		// P - Puute
		// J - Jt
		// H - V‰kisinhyv‰ksytty
		// T - Tilataan joltain toimittajalta omaan varastoon
		// U - Tilataan joltain toimittajalta suoraan asiakkaalle
		// V - Tehdaslis‰varusterivi
		// A - Myyntitilirivi joka laskutetaan asiakkaalta
		// B - Myyntitilirivi joka palautetaan myyntitilist‰ omaan varastoon
		// R - Rakennettava rivi
		// S - who knows?


		$varaosavirhe .= t("VIRHE: Var voi olla tyhj‰, P, J, H, S, T, U, V, A, B tai R!")."<br>";
		$riviok++;
		$tilausok++;
	}

	if ($row["tyyppi"] == 'V' and $row["perheid"] == 0 and $toim == "VALMISTAVARASTOON") {
		$varaosavirhe .= t("VIRHE: Tuote ei kuulu mihink‰‰n reseptiin!")."<br>";
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

	for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
		if ($row["netto"] == 'N' and (float) $row["ale{$alepostfix}"] != 0) {
			$varaosavirhe .= t("VIRHE: Nettoriville ei voi antaa alennusta!")."<br>";
			$riviok++;
			$tilausok++;
		}

		if ($row["ale{$alepostfix}"] != '' and ($row["ale{$alepostfix}"] < 0 or 100 < $row["ale{$alepostfix}"])) {
			$varaosavirhe .= t("VIRHE: Alennus on oltava 0-100!")."<br>";
			$riviok++;
			$tilausok++;
		}
	}

	if ($kukarow["extranet"] == "" and in_array($trow["status"], array("P","X")) and !in_array($row["var2"], array("OK", "PK"))) {
		$varaosavirhe .= t("HUOM: T‰m‰ on poistuva tuote!")."<br>";
		$riviok++;
	}

	if (in_array($trow["status"], array("P","X")) and $row["var"] == "J") {
		$varaosavirhe .= t("VIRHE: T‰m‰ on poistuva tuote, ei voida j‰tt‰‰ j‰lkitoimitukseen!")."<br>";
		$riviok++;
		$tilausok++;
	}

	// Jos tilaus on tehdastilaus, niin tilaukselle saa laittaa vain tehdastilaustuotteita
	if ($laskurow['tilaustyyppi'] == '7' and $trow['status'] != 'T' and $trow['panttitili'] == '' and $trow["ei_saldoa"] == "") {
		$varaosavirhe .= t("VIRHE: Tuote ei ole tehdastilaustuote")."!<br>";
		$riviok++;
		$tilausok++;
	}

	if ($yhtiorow['tilausrivi_omalle_tilaukselle'] != '' and $row['omalle_tilaukselle'] != '' and fmod($row['varattu'], 1) != 0) {
		$varaosavirhe .= t("VIRHE: Kappalem‰‰r‰ pit‰‰ olla kokonaisluku")."!<br>";
		$riviok++;
		$tilausok++;
	}

	if (trim($trow["vienti"]) != '' and $row["tyyppi"] != "V" and $row["tyyppi"] != "W" and ($row["var2"] != "OK" or $yhtiorow["vientikiellon_ohitus"] != "K")) {

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

			// Vientikiellossa olevan tuotteen voi hyv‰ksy‰ myyntitilaukselle
			if ($yhtiorow["vientikiellon_ohitus"] == "K" and $kukarow["extranet"] == "") {
				$saako_hyvaksya++;
			}
		}

		if (strpos(strtoupper($trow["vienti"]), strtoupper("-$laskurow[toim_maa]")) === FALSE and strpos($trow["vienti"], "-") !== FALSE) {
			//ei saa myyd‰ t‰h‰n maahan
			$varaosavirhe .= t("VIRHE: T‰t‰ tuotetta ei saa myyd‰ maahan")." $laskurow[toim_maa]!!<br>";
	 		$riviok++;
	 		$tilausok++;
			$kielletty++;

			// Vientikiellossa olevan tuotteen voi hyv‰ksy‰ myyntitilaukselle
			if ($yhtiorow["vientikiellon_ohitus"] == "K" and $kukarow["extranet"] == "") {
				$saako_hyvaksya++;
			}
		}
	}

	if ($row["var"] == 'S') {
		// otetaan maksuehto selville..
		$query = "	SELECT *
					from maksuehto
					where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
		$maksuehtoresult = pupe_query($query);
		$maksuehtorow = mysql_fetch_assoc($maksuehtoresult);

		if ($maksuehtorow["jv"] != "") {
			$varaosavirhe .= t("VIRHE: Et voi suoratoimittaa tuotetta jos maksuehtona on j‰lkivaatimus!")."<br>";
			$riviok++;
			$tilausok++;
		}
	}

	// Otetaan oikea kappalem‰‰r‰ k‰sittelyyn
	if (in_array($row["var"], array('J', 'S', 'T', 'U'))) {
		// jos yhtiˆn parametreiss‰ jt-rivit varaavat saldoa, laitetaan "varattu kpl"-m‰‰r‰, muuten "jt kpl"-m‰‰r‰
		$kpl_st = $yhtiorow['varaako_jt_saldoa'] != '' ? $row['varattu'] : $row['jt'];
	}
	elseif($row["var"] == 'P') {
		$kpl_st = $row['tilkpl'];
	}
	else {
		$kpl_st = $row['varattu'];
	}

	if (isset($myyntierahuom) and is_array($myyntierahuom) and in_array($row['tunnus'], $myyntierahuom) and $kpl_st != $org_kpl) {

		$mimyhuom = "HUOM: Rivin m‰‰r‰ on pyˆristetty";

		if ($trow["minimi_era"] > 0) {
			$mimyhuom .= " minimier‰‰n";
		}

		if ($trow['myynti_era'] > 0 and $yhtiorow['myyntiera_pyoristys'] == 'K') {
			if ($trow["minimi_era"] > 0) {
				$mimyhuom .= " tai";
			}

			$mimyhuom .= " t‰yteen myyntier‰‰n";
		}

		// K‰‰nnet‰‰n teksti
		$mimyhuom = t($mimyhuom)."!";

		if ($trow['myynti_era'] > 0) {
			$mimyhuom .= " ".t("Myyntier‰ on").": $trow[myynti_era]";
		}

		if ($trow["minimi_era"] > 0) {
			$mimyhuom .= " ".t("Minimier‰ on").": $trow[minimi_era]";
		}

		$varaosavirhe .= $mimyhuom."<br>";
	}

	//T‰ss‰ on kaikki saldoihin ja varastopaikkoihin liittyv‰t tsekit, eli EI tehd‰ saldottomille tuotteille, eik‰ tuoteperheen saldollisille lapsille jos koko perhe suoratoimitetaan
	$paikat	= "";

	if (
		$trow["ei_saldoa"] == ""
		and !in_array($row["tyyppi"], array('E', 'W'))
		and (
			!in_array($row["var"], array('T', 'U'))
			or $yhtiorow['tuoteperhe_suoratoimitus'] == ''
			or $row["perheid"] == 0
			or $row["perheid"] == $row["tunnus"]
		)
	) {
		// tarkistetaan varaston tyyppi
		$query = "	SELECT *
					FROM varastopaikat
					WHERE
					concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper('$row[hyllyalue]') ,5,'0'),lpad(upper('$row[hyllynro]') ,5,'0')) and
					concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper('$row[hyllyalue]') ,5,'0'),lpad(upper('$row[hyllynro]') ,5,'0'))
					and yhtio = '$kukarow[yhtio]'";
		$varesult = pupe_query($query);
		$varow = mysql_fetch_assoc($varesult);

		if ($varow["tyyppi"] == "V" and $laskurow["varasto"] != $varow["tunnus"] and !in_array($row["var2"], array("OK", "PK"))) {
			$varaosavirhe .= t("HUOM: Tuote sijaitsee erikoisvarastossa")."!<br>";
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

		// Katsotaan onko t‰lle tuotteelle toimittajaa ja oletusvienti on jotain vaihto-omaisuutta (Tilaa-toimittajalta drop-downiin mukaan)
		if (in_array($yhtiorow["tee_osto_myyntitilaukselta"], array('A', 'B', 'C', 'I', 'J')) and $yksi_suoratoimittaja != '') {
			$where_yksi_suoratoimittaja = " AND toimi.tunnus = {$yksi_suoratoimittaja} ";
		}
		else {
			$where_yksi_suoratoimittaja = "";
		}

		if (($kukarow["extranet"] == "" or $yhtiorow['tuoteperhe_suoratoimitus'] == 'E') and ($toim == "TARJOUS" or ($yhtiorow["tee_osto_myyntitilaukselta"] != '' and ($toim == 'RIVISYOTTO' or $toim == 'PIKATILAUS'))) and $row["var"] != 'V') {
			$query = "	SELECT tyyppi_tieto, liitostunnus, toimi.nimi, toimi.suoratoimitus
						FROM tuotteen_toimittajat use index (yhtio_tuoteno)
						JOIN toimi ON (toimi.yhtio = tuotteen_toimittajat.yhtio and toimi.tunnus = tuotteen_toimittajat.liitostunnus)
						WHERE tuotteen_toimittajat.yhtio = '$kukarow[yhtio]'
						and tuotteen_toimittajat.tuoteno = '$row[tuoteno]'
						$where_yksi_suoratoimittaja
						ORDER BY if (tuotteen_toimittajat.jarjestys = 0, 9999, tuotteen_toimittajat.jarjestys)";
			$toimpaikres  = pupe_query($query);

			if (mysql_num_rows($toimpaikres) > 0) {
				$omapaikatraja = 1;
			}
		}

		// katotaan jos myyntitilauksella voidaan tehd‰ valmistusta, niin pudotetaan dropdownin rajaa
		if (($kukarow["extranet"] == "" and $yhtiorow["tee_valmistus_myyntitilaukselta"] != '') and ($toim == 'RIVISYOTTO' or $toim == 'PIKATILAUS')) {
			$omapaikatraja = 1;
		}

		// Katotaan voidaanko t‰m‰ myyd‰ joltain toiselta paikalta
		if (!in_array($row["var"], array('J','S','U','T','P')) and $kpl_st > 0 and in_array($trow["sarjanumeroseuranta"], array('E', 'F', 'G'))) {

			$paikat .= "<option value='EIERAAVIELA#!°!#EIEI'>".t("Valitse er‰")."</option>";
			$paikatlask++;

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
						varastopaikat.nimitys, if(varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi, varastopaikat.erikoistoimitus_alarajasumma, tuote.vakkoodi
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
						GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17 $pepvmlisa2
						ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";
		}
		else {
			$query = "	SELECT tuote.yhtio, tuote.tuoteno, tuote.ei_saldoa, varastopaikat.tunnus varasto, varastopaikat.tyyppi varastotyyppi, varastopaikat.maa varastomaa,
						tuotepaikat.oletus, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso,
						concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0')) sorttauskentta,
						varastopaikat.nimitys, if(varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi, varastopaikat.erikoistoimitus_alarajasumma, tuote.vakkoodi, '' era
			 			FROM tuote
						JOIN tuotepaikat ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno
						JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
						and concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
						and concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
						WHERE tuote.yhtio = '$kukarow[yhtio]'
						and tuote.tuoteno = '$row[tuoteno]'
						ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";
		}

		$omavarastores  = pupe_query($query);
		$varastot_array = array();

		if ($kukarow["varasto"] != "") {
			$varastot_array = explode(",", $kukarow["varasto"]);
		}

		// K‰yd‰‰n l‰pi oman varaston paikat
		if (
			in_array($yhtiorow["puute_jt_oletus"], array('H','O'))
			or (in_array($row["var"], array('','H','S','T','U','J')) and mysql_num_rows($omavarastores) >= $omapaikatraja)
			or (in_array($row["var"], array('S','T','U','J')) and mysql_num_rows($omavarastores) > 0)
		) {
			if ($trow["sarjanumeroseuranta"] == "E" or $trow["sarjanumeroseuranta"] == "F" or $trow["sarjanumeroseuranta"] == "G") {
		   		$query	= "	SELECT sarjanumeroseuranta.sarjanumero era
		   					FROM sarjanumeroseuranta
		   					WHERE yhtio = '$kukarow[yhtio]'
							and tuoteno = '$row[tuoteno]'
		   					and myyntirivitunnus = '$row[tunnus]'
		   					LIMIT 1";
		   		$sarjares = pupe_query($query);
		   		$sarjarow = mysql_fetch_assoc($sarjares);
			}

			while ($alkurow = mysql_fetch_assoc($omavarastores)) {

				if ($alkurow["hyllyalue"] != "!!M"
					and (
						(
							$alkurow['varastotyyppi'] == 'E'
							and $kukarow['kassamyyja'] == ''
							and $maksuehtorow["jv"] == ""
							and $alkurow['erikoistoimitus_alarajasumma'] > 0
							and (float) $row['myyntihinta'] >= (float) $alkurow['erikoistoimitus_alarajasumma']
						)
						or ($alkurow["varastotyyppi"] != "E" and $laskurow["varasto"] == 0 and (in_array($alkurow["varasto"], $varastot_array) or count($varastot_array) == 0))
						or $laskurow["varasto"] == $alkurow["varasto"]
						or (in_array($alkurow["varasto"], $varastot_array) and $laskurow["varasto"] == 0)
						or ($alkurow["hyllyalue"] == $row["hyllyalue"] and $alkurow["hyllynro"] == $row["hyllynro"] and $alkurow["hyllyvali"] == $row["hyllyvali"] and $alkurow["hyllytaso"] == $row["hyllytaso"])
					)
				) {

					list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($row["tuoteno"], '', '', '', $alkurow["hyllyalue"], $alkurow["hyllynro"], $alkurow["hyllyvali"], $alkurow["hyllytaso"], $laskurow["toim_maa"], $saldoaikalisa, $alkurow["era"]);

					$myytavissa = (float) $myytavissa;

					if ($trow["sarjanumeroseuranta"] == "E" or $trow["sarjanumeroseuranta"] == "F" or $trow["sarjanumeroseuranta"] == "G") {
						//Jos er‰ on keksitty k‰sin t‰‰lt‰ ker‰yksest‰
						$query = "	SELECT tyyppi, (varattu+kpl+jt) kpl, tunnus
									FROM tilausrivi
									WHERE yhtio = '$kukarow[yhtio]'
									and tuoteno = '$row[tuoteno]'
									and tunnus	= '$alkurow[ostorivitunnus]'";
						$lisa_res = pupe_query($query);
						$lisa_row = mysql_fetch_assoc($lisa_res);
					}

					if ((in_array($yhtiorow["puute_jt_oletus"], array('H','O')) and $row["tyyppi"] != 'V') or
						$myytavissa >= $kpl_st or
						($row["var"] != "P" and $toim != "TARJOUS" and
						(($alkurow["hyllyalue"] == $row["hyllyalue"] and $alkurow["hyllynro"] == $row["hyllynro"] and $alkurow["hyllyvali"] == $row["hyllyvali"] and $alkurow["hyllytaso"] == $row["hyllytaso"] and $trow["sarjanumeroseuranta"] != "E" and $trow["sarjanumeroseuranta"] != "F" and $trow["sarjanumeroseuranta"] != "G") or
						 ($alkurow["hyllyalue"] == $row["hyllyalue"] and $alkurow["hyllynro"] == $row["hyllynro"] and $alkurow["hyllyvali"] == $row["hyllyvali"] and $alkurow["hyllytaso"] == $row["hyllytaso"] and ($trow["sarjanumeroseuranta"] == "E" or $trow["sarjanumeroseuranta"] == "F" or $trow["sarjanumeroseuranta"] == "G") and ($lisa_row["tyyppi"] == "O" or $lisa_row["kpl"] < 0 or $lisa_row["tunnus"] == $row["tunnus"]) and $sarjarow["era"] == $alkurow["era"])))) {

						$sel = "";

						if ($trow["sarjanumeroseuranta"] != "E" and $trow["sarjanumeroseuranta"] != "F" and $trow["sarjanumeroseuranta"] != "G" and !in_array($row["var"], array("P","S")) and $alkurow["hyllyalue"] == $row["hyllyalue"] and $alkurow["hyllynro"] == $row["hyllynro"] and $alkurow["hyllyvali"] == $row["hyllyvali"] and $alkurow["hyllytaso"] == $row["hyllytaso"]) {
							$sel = "SELECTED";

							$selpaikka = "$alkurow[hyllyalue] $alkurow[hyllynro] $alkurow[hyllyvali] $alkurow[hyllytaso]";
							$selpaikkamaa = $alkurow['varastomaa'];
							$selpaikkamyytavissa = $myytavissa;
						}
						elseif (($trow["sarjanumeroseuranta"] == "E" or $trow["sarjanumeroseuranta"] == "F" or $trow["sarjanumeroseuranta"] == "G") and $sarjarow["era"] == $alkurow["era"] and !in_array($row["var"], array("P","S")) and $alkurow["hyllyalue"] == $row["hyllyalue"] and $alkurow["hyllynro"] == $row["hyllynro"] and $alkurow["hyllyvali"] == $row["hyllyvali"] and $alkurow["hyllytaso"] == $row["hyllytaso"]) {
							$sel = "SELECTED";

							$selpaikka = "$alkurow[hyllyalue] $alkurow[hyllynro] $alkurow[hyllyvali] $alkurow[hyllytaso]";
							$selpaikkamaa = $alkurow['varastomaa'];
							$selpaikkamyytavissa = $myytavissa;

							if ($trow["sarjanumeroseuranta"] == "E" or $trow["sarjanumeroseuranta"] == "F" or $trow["sarjanumeroseuranta"] == "G") {
								$selpaikka .= " $alkurow[era]";
							}
						}

						$paikat .= "<option value='$alkurow[hyllyalue]#!°!#$alkurow[hyllynro]#!°!#$alkurow[hyllyvali]#!°!#$alkurow[hyllytaso]";

						if ($trow["sarjanumeroseuranta"] == "E" or $trow["sarjanumeroseuranta"] == "F" or $trow["sarjanumeroseuranta"] == "G") {
							$paikat .= "#!°!#$alkurow[era]";
						}

						$title  = "";

						if (strtoupper($alkurow['varastomaa']) != strtoupper($yhtiorow['maa'])) {
							$title .= strtoupper($alkurow['varastomaa'])." ";
						}

						$title .= "$alkurow[hyllyalue] $alkurow[hyllynro] $alkurow[hyllyvali] $alkurow[hyllytaso]";

						if ($trow["sarjanumeroseuranta"] == "E" or $trow["sarjanumeroseuranta"] == "F" or $trow["sarjanumeroseuranta"] == "G") {
							$title .= ", $alkurow[era]";
						}

						$title .= " ($myytavissa)";

						if ($trow["sarjanumeroseuranta"] == "F") {
							$title .= " ".tv1dateconv($alkurow["parasta_ennen"]);
						}

						$paikat .= "' $sel title='$title'>$title</option>";

						// jos toimitetaan erikoisvarastosta, niin k‰ytt‰j‰n pit‰‰ hyv‰ksy‰ se
						if ($alkurow['varasto'] != $laskurow["varasto"] and $tm_toimitustaparow["nouto"] == "" and $sel != '' and !in_array($row["var2"], array("OK", "PK")) and $kukarow['kassamyyja'] == '' and $row['varattu'] > 0 and $alkurow['varastotyyppi'] == 'E' and $alkurow['erikoistoimitus_alarajasumma'] > 0 and (float) $row['myyntihinta'] >= (float) $alkurow['erikoistoimitus_alarajasumma']) {

							if ($alkurow['vakkoodi'] != '' and $alkurow['vakkoodi'] != '0') {
								$varaosavirhe .= t("HUOM: VAK-tuotteita ei toimiteta erikoisvarastosta")."!<br/>";
							}
							else {
								$varaosavirhe .= t("HUOM: toimitetaan erikoisvarastosta, toimitusaika 2pv. Haluatko hyv‰ksy‰")."?<br/>";
								$saako_hyvaksya++;
							}

							$riviok++;
							$tilausok++;
						}

						$paikatlask++;
						$kotipaikatlask++;
					}
				}

			}
		}
		//	Jos ollaan livahdettu puutteeksi koitetaan josko t‰t‰ voitasiin myyd‰ erikoisvarastosta
		elseif ($row["var"] == "P" and mysql_num_rows($omavarastores) > 0) {
			while ($alkurow = mysql_fetch_assoc($omavarastores)) {

				list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($row["tuoteno"], '', '', '', $alkurow["hyllyalue"], $alkurow["hyllynro"], $alkurow["hyllyvali"], $alkurow["hyllytaso"], $laskurow["toim_maa"], $saldoaikalisa, $alkurow["era"]);

				if($myytavissa > 0 and ($alkurow["varastotyyppi"] == "E" or $alkurow["hyllyalue"] == "!!M")) {

					if($kukarow["extranet"] != "") {
						if ($laskurow["toim_maa"] == $alkurow["varastomaa"]) {

							$ale_lisa_kala = generoi_alekentta_php($row, 'M', 'kerto');

							if ($yhtiorow["erikoisvarastomyynti_alarajasumma_rivi"] != 0 and $yhtiorow["erikoisvarastomyynti_alarajasumma_rivi"] > $row["hinta"]*$row["tilkpl"]*$ale_lisa_kala) {
								$varaosakommentti .= t("Tuote ainoastaan noudettavissa myym‰lˆist‰ joissa varastoituna")."!<br>";
							}
							elseif ($yhtiorow["erikoisvarastomyynti_alarajasumma_rivi"] != 0 and $yhtiorow["erikoisvarastomyynti_alarajasumma_rivi"] < $row["hinta"]*$row["tilkpl"]*$ale_lisa_kala) {
								//j‰tet‰‰n puutteeksi
							}
						}

					}
					else {
						$varaosakommentti .= t("Tuote saatavilla erikoisvarastossa")."!<br>";
					}

					break;
				}
			}

			if ($row['var'] == 'P' and $trow['status'] == 'T') {
				$varaosakommentti .= " ".t("T‰m‰ on tehdastilaustuote ja on saatavilla tilauksena")."!<br>";
			}
		}
		else {
			// meill‰ on vaan yks paikka..
			$alkurow = mysql_fetch_assoc($omavarastores);
			list(, , $selpaikkamyytavissa) = saldo_myytavissa($row["tuoteno"], '', '', '', $alkurow["hyllyalue"], $alkurow["hyllynro"], $alkurow["hyllyvali"], $alkurow["hyllytaso"], $laskurow["toim_maa"], $saldoaikalisa, $alkurow["era"]);
		}

		// K‰yd‰‰n l‰pi tuotteen toimittajat
		if ((
				$kukarow["extranet"] == ""
				or $yhtiorow['tuoteperhe_suoratoimitus'] == 'E'
			)
			and in_array($row["var"], array('','H','S','T','U','J','P'))
			and (
				$toim == "TARJOUS"
				or (!in_array($yhtiorow["tee_osto_myyntitilaukselta"], array('','Z','Q')))
			)
			and $kpl_st > 0
			and $toim != "SIIRTOTYOMAARAYS"
		) {
			if ($kotipaikatlask == 0 and !in_array($trow["sarjanumeroseuranta"], array('E', 'F', 'G'))) {
				$paikat = "<option value=''>".t("J‰t‰ puutteeksi")."</option>".$paikat;
				$paikatlask++;
			}

			while ($krow = mysql_fetch_assoc($toimpaikres)) {
				$sel1 = "";
				$sel2 = "";

				// tarkistetaan voiko tuotteen tilata suoraan asiakkaalle tai varastoon. oletuksena voi...
				$tmp_varastoon   = 1;
				$tmp_asiakkaalle = 1;

				// ...kiellet‰‰n tilaamasta varastoon jos yhtiˆparametri ohjaa katsomaan tuotteelta ja siell‰ ei ole sallittu...
				if (in_array($yhtiorow["tee_osto_myyntitilaukselta"], array('V', 'W', 'I', 'J'))
					and !in_array($trow['suoratoimitus'], array('S', 'X'))) { 				// vain S ja X sallivat tilauksen varastoon
					$tmp_varastoon = 0;
				}

				// ...kiellet‰‰n tilaamasta asiakkaalle jos yhtiˆparametri ohjaa katsomaan tuotteelta ja siell‰ ei ole sallittu...
				if (in_array($yhtiorow["tee_osto_myyntitilaukselta"], array('W', 'J'))
				 	and !in_array($trow['suoratoimitus'], array('S', 'U'))) {				// vain S ja U sallivat tilauksen asiakkaalle
					$tmp_asiakkaalle = 0;
				}

				// katsotaan onko tuote suoratoimituskelpoinen (vain ei tuoteperheet, tai tuoteperheiden saldolliset is‰t tai jos yhtiˆll‰ suoratoimitetaan perheet per tuote)
				if ((   // jos on tuoteperheen lapsi, pit‰‰ lapsien suoratoimitus olla sallittu
						$yhtiorow['tuoteperhe_suoratoimitus'] == ''
						or $row["perheid"] == 0
						or $row["perheid"] == $row["tunnus"]
					)
					and ( // onko suoratoimitus yleens‰k‰‰n sallittu
						in_array($yhtiorow["tee_osto_myyntitilaukselta"], array('U', 'X', 'Y', 'A', 'B', 'C'))
						or (in_array($yhtiorow["tee_osto_myyntitilaukselta"], array('V', 'W', 'I', 'J')) and ($tmp_varastoon == 1 or $tmp_asiakkaalle == 1))
					)
				) {
					// piirret‰‰nkˆ oletukseksi tilata suoratoimituksena varastoon? ...
					if ($row["toimittajan_tunnus"] == $krow["liitostunnus"] and $row["hyllyalue"] == "" and $row["hyllynro"] == "") {

						if (($row["var"] == 'T' or ($row["var"] == "J" and $row["suoraan_laskutukseen"] == "")) and in_array($krow["suoratoimitus"], array('', 'X')) and $tmp_varastoon == 1) {
							$sel1 				 = "SELECTED";
							$selpaikka 			 = "$krow[nimi] --> ".t("tilaa")." ".t("varastoon");
							$selpaikkamaa 		 = "";
							$selpaikkamyytavissa = 0;
						}
						// ...ent‰ asiakkaalle?
						elseif (in_array($krow["suoratoimitus"], array('', 'U')) and $tmp_asiakkaalle == 1) {
							$sel2 				 = "SELECTED";
							$selpaikka 			 = "$krow[nimi] --> ".t("tilaa")." ".t("asiakkaalle");
							$selpaikkamaa 		 = "";
							$selpaikkamyytavissa = 0;
						}
					}

					if (!in_array($yhtiorow["tee_osto_myyntitilaukselta"], array('U', 'B')) and in_array($krow["suoratoimitus"], array('', 'X')) and $tmp_varastoon == 1) {
						// tallennetaan riville toimittajan tunnus
						$title = "$krow[nimi] --> ".t("tilaa")." ".t("varastoon");
						$paikat .= "<option value='°°°$krow[liitostunnus]' title='$title' $sel1>$title</option>";
						$paikatlask++;
					}

					if (in_array($yhtiorow["tee_osto_myyntitilaukselta"], array('U', 'Y', 'W', 'J', 'B', 'C')) and in_array($krow["suoratoimitus"], array('', 'U')) and $tmp_asiakkaalle == 1) {
						$title = "$krow[nimi] --> ".t("tilaa")." ".t("asiakkaalle");
						$paikat .= "<option value='!!!$krow[liitostunnus]' title='$title' $sel2>$title</option>";
						$paikatlask++;
					}
				}
			}
		}
		elseif ($kotipaikatlask == 0 and $kpl_st > 0 and $laskurow["tila"] != 'V') {
			$paikat = "<option value=''>".t("J‰t‰ puutteeksi")."</option>".$paikat;
			$paikatlask++;
		}

		//	Tarkistetaan viel‰ voisimmeko kenties ehk‰ mahdollisesti valmistaa t‰t‰ asiakkaalle?
		if ($kukarow["extranet"] == "" and $yhtiorow["tee_valmistus_myyntitilaukselta"] != '' and ($toim == 'RIVISYOTTO' or $toim == 'PIKATILAUS')) {

			$query = "	SELECT *
						FROM tuoteperhe
						WHERE yhtio = '$kukarow[yhtio]'
						and isatuoteno = '$row[tuoteno]'
						and tyyppi = 'R'
						LIMIT 1";
			$pres = pupe_query($query);

			if (mysql_num_rows($pres) == 1) {
				$query = "	SELECT varastopaikat.nimitys varasto, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali,tuotepaikat.hyllytaso
							FROM tuote
							JOIN tuotepaikat ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno
							JOIN varastopaikat ON varastopaikat.yhtio = tuote.yhtio
								and concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
								and	concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
							WHERE tuote.yhtio = '$kukarow[yhtio]'
							and tuote.tuoteno = '$row[tuoteno]'";
				$valmpaikatres = pupe_query($query);

				while ($valmpaikatrow  = mysql_fetch_assoc($valmpaikatres)) {
					$sel = "";
					if ($row["hyllyalue"] == $valmpaikatrow["hyllyalue"] and $row["hyllynro"] == $valmpaikatrow["hyllynro"] and $row["hyllyvali"] == $valmpaikatrow["hyllyvali"] and $row["hyllytaso"] == $valmpaikatrow["hyllytaso"] and ($row["var"] == "R" or ($row["tilausrivilinkki"] > 0))) {
						$sel = "SELECTED";
					}

					// tallennetaan riville toimittajan tunnus
					$title = "$valmpaikatrow[varasto] --> ".t("valmista")." ($valmpaikatrow[hyllyalue]-$valmpaikatrow[hyllynro]-$valmpaikatrow[hyllyvali]-$valmpaikatrow[hyllytaso])";
					$paikat .= "<option value='°°V$valmpaikatrow[hyllyalue]#!°!#$valmpaikatrow[hyllynro]#!°!#$valmpaikatrow[hyllyvali]#!°!#$valmpaikatrow[hyllytaso]' title='$title' $sel>$title</option>";
					$paikatlask++;
				}
			}
		}

		if ($paikat != "" and $paikatlask > 1 and $row["hyllyalue"] != "!!M") {
			$paikat = "<select name='paikka' style='width:120px;' onchange='submit();'>".$paikat."</select>";
		}
		elseif ($row["hyllyalue"] == "!!M") {
			$paikat = " $row[hyllyalue] $row[hyllynro] $row[hyllyvali] $row[hyllytaso]";
		}
		else {
			$paikat = "";
		}
	}
	// voidaan valita paikka myˆs valmistusten valmisteille
	elseif (
		$trow["ei_saldoa"] == ""
		and $row["tyyppi"] == 'W'
		and ($row["perheid"] == 0
			or $row["perheid"] == $row["tunnus"]
		)
	) {

		$query = "	SELECT tuote.yhtio, tuote.tuoteno, tuote.ei_saldoa, varastopaikat.tunnus varasto, varastopaikat.tyyppi varastotyyppi, varastopaikat.maa varastomaa,
					tuotepaikat.oletus, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso,
					concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0')) sorttauskentta,
					varastopaikat.nimitys, if(varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi, varastopaikat.erikoistoimitus_alarajasumma, tuote.vakkoodi, '' era
		 			FROM tuote
					JOIN tuotepaikat ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno and tuotepaikat.hyllyalue != '!!M'
					JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
					and concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
					and concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
					WHERE tuote.yhtio = '$kukarow[yhtio]'
					and tuote.tuoteno = '$row[tuoteno]'
					ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";
		$omavarastores  = pupe_query($query);
		$varastot_array = array();

		if ($kukarow["varasto"] != "") {
			$varastot_array = explode(",", $kukarow["varasto"]);
		}

		// K‰yd‰‰n l‰pi oman varaston paikat
		while ($alkurow = mysql_fetch_assoc($omavarastores)) {

			if ($laskurow["varasto"] == $alkurow["varasto"]
				or (in_array($alkurow["varasto"], $varastot_array) and $laskurow["varasto"] == 0)
				or ($laskurow["varasto"] == 0 and count($varastot_array) == 0)
				or ($alkurow["hyllyalue"] == $row["hyllyalue"] and $alkurow["hyllynro"] == $row["hyllynro"] and $alkurow["hyllyvali"] == $row["hyllyvali"] and $alkurow["hyllytaso"] == $row["hyllytaso"])
			) {

				$sel = "";

				if ($alkurow["hyllyalue"] == $row["hyllyalue"] and $alkurow["hyllynro"] == $row["hyllynro"] and $alkurow["hyllyvali"] == $row["hyllyvali"] and $alkurow["hyllytaso"] == $row["hyllytaso"]) {
					$sel = "SELECTED";

					$selpaikka = "$alkurow[hyllyalue] $alkurow[hyllynro] $alkurow[hyllyvali] $alkurow[hyllytaso]";
					$selpaikkamaa = $alkurow['varastomaa'];
				}

				$paikat .= "<option value='$alkurow[hyllyalue]#!°!#$alkurow[hyllynro]#!°!#$alkurow[hyllyvali]#!°!#$alkurow[hyllytaso]";

				$title  = "";

				if (strtoupper($alkurow['varastomaa']) != strtoupper($yhtiorow['maa'])) {
					$title .= strtoupper($alkurow['varastomaa'])." ";
				}

				$title .= "$alkurow[hyllyalue] $alkurow[hyllynro] $alkurow[hyllyvali] $alkurow[hyllytaso]";

				$paikat .= "' $sel title='$title'>$title</option>";
				$paikatlask++;
			}
		}

		if ($paikat != "" and $paikatlask > 1) {
			$paikat = "<select name='paikka' style='width:120px;' onchange='submit();'>".$paikat."</select>";
		}
		elseif ($row["hyllyalue"] == "!!M") {
			$paikat = " $row[hyllyalue] $row[hyllynro] $row[hyllyvali] $row[hyllytaso]";
		}
		else {
			$paikat = "";
		}
	}
	// jos kyseess‰ on tuoteperheen suoratoimitus suoraan asiakkaalle, niin tehd‰‰n is‰dropdown (dropdownin sis‰ltˆ haetaan lapsilta).
	elseif (
		(	$yhtiorow['tuoteperhe_suoratoimitus'] == 'E'
			or ($kukarow['extranet'] == '' and $yhtiorow['tuoteperhe_suoratoimitus'] == 'M'))
		and in_array($yhtiorow["tee_osto_myyntitilaukselta"], array('U', 'W', 'J', 'Y', 'B', 'C'))
		and $trow["ei_saldoa"] != ""
		and $row["tyyppi"] == "L"
		and $kpl_st > 0
		and $row["tunnus"] == $row["perheid"]
		and in_array($row["var"], array('P','J','U','T'))
	) {

		$query = "	SELECT tuoteno
					FROM tilausrivi
					WHERE yhtio = '$kukarow[yhtio]'
					AND perheid = '$row[tunnus]'
					AND otunnus = '$row[otunnus]'";
		$perhe_tuoteno_res = pupe_query($query);

		$perheen_tuotteet = '';

		while ($perhe_tuoteno_row = mysql_fetch_assoc($perhe_tuoteno_res)) {
			$perheen_tuotteet .= "'$perhe_tuoteno_row[tuoteno]',";
		}

		$perheen_tuotteet = substr($perheen_tuotteet, 0, -1);

		$query = "	SELECT DISTINCT liitostunnus, tyyppi_tieto, toimi.nimi
					FROM tuotteen_toimittajat use index (yhtio_tuoteno)
					JOIN toimi ON (toimi.yhtio = tuotteen_toimittajat.yhtio and toimi.tunnus = tuotteen_toimittajat.liitostunnus)
					WHERE tuotteen_toimittajat.yhtio = '$kukarow[yhtio]'
					and tuotteen_toimittajat.tuoteno in ($perheen_tuotteet)
					ORDER BY if (tuotteen_toimittajat.jarjestys = 0, 9999, tuotteen_toimittajat.jarjestys)";
		$toimpaikres  = pupe_query($query);

		$paikat = "<option value=''>".t("J‰t‰ j‰lkitoimitukseen")."</option>".$paikat;

		while ($krow  = mysql_fetch_assoc($toimpaikres)) {
			$sel1 = "";

			if ($row["toimittajan_tunnus"] == $krow["liitostunnus"] and $row["hyllyalue"] == "" and $row["hyllynro"] == "") {
				$sel1 = 'SELECTED';
			}

			$title = "$krow[nimi] --> ".t("tilaa")." ".t("asiakkaalle");
			$paikat .= "<option value='!!!$krow[liitostunnus]' title='$title' $sel1>$title</option>";
		}

		$paikat = "<select name='paikka' style='width:120px;' onchange='submit();'>".$paikat."</select>";
	}

	//	Onko t‰t‰ varattuna siirtolistalla?
	if ($kukarow["extranet"] == "" and $trow["ei_saldoa"] == "" and $row["tyyppi"] != "E" and $kpl_st > 0 and in_array($row["var"], array("J","U","T","P","R"))) {

		$query = "	SELECT kohde.nimitys kohde, lahde.nimitys lahde, tila, alatila, sum(varattu+jt+kpl) kpl, lasku.tunnus, tilausrivi.yksikko
					FROM lasku USE INDEX (tila_index)
					JOIN tilausrivi USE INDEX (yhtio_otunnus) ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi != 'D' and tilausrivi.tuoteno = '$trow[tuoteno]')
					LEFT JOIN varastopaikat lahde USE INDEX (PRIMARY) ON (lahde.yhtio = lasku.yhtio and lahde.tunnus = lasku.varasto)
					LEFT JOIN varastopaikat kohde USE INDEX (PRIMARY) ON (kohde.yhtio = lasku.yhtio and kohde.tunnus = lasku.clearing)
					WHERE lasku.yhtio = '$kukarow[yhtio]'
					and lasku.tila = 'G'
					and lasku.alatila in ('','J','A','B','C','D','E','T','P')
					and lasku.tunnus != '{$laskurow["tunnus"]}'
					GROUP BY tuoteno, lasku.tunnus";
		$siirtores = pupe_query($query);

		while ($siirtorow = mysql_fetch_assoc($siirtores)) {
			$laskutyyppi = $siirtorow["tila"];
			$alatila	 = $siirtorow["alatila"];

			//tehd‰‰n selv‰kielinen tila/alatila
			require "inc/laskutyyppi.inc";

			$varaosakommentti .= t("Siirtolistalla")." ($siirtorow[tunnus]) on: $siirtorow[kpl] {".t_avainsana("Y", "", "and avainsana.selite='$siirtorow[yksikko]'", "", "", "selite")."} $siirtorow[lahde] -> $siirtorow[kohde] ($alatila)<br>";
		}

		// katotaan onko viel‰ ostotilauksella!
		$tulossa_query = " 	SELECT IFNULL(min(toimaika),'') paivamaara, IFNULL(max(toimaika),'') paivamaara2, sum(varattu) kpl, max(yksikko) yksikko
		 					FROM tilausrivi
							WHERE yhtio = '$kukarow[yhtio]'
							AND tuoteno = '$row[tuoteno]'
							AND varattu > 0
							AND tyyppi IN ('O','W')";
		$tulossa_result = pupe_query($tulossa_query);
		$tulossa_row = mysql_fetch_assoc($tulossa_result);

		if (mysql_num_rows($tulossa_result) > 0 and $tulossa_row["paivamaara"] != '' and $tulossa_row["kpl"] > 0) {
			$varaosakommentti .= "$tulossa_row[kpl] ".t_avainsana("Y", "", "and avainsana.selite='$tulossa_row[yksikko]'", "", "", "selite")." ".t("Tulossa")." ".tv1dateconv($tulossa_row['paivamaara']);
			if ($tulossa_row["paivamaara"] != $tulossa_row["paivamaara2"]) {
				$varaosakommentti .= " - ".tv1dateconv($tulossa_row['paivamaara2']);
			}
			$varaosakommentti .= "<br>";
		}
	}

	//	Onko t‰t‰ ostotilauksella?
	if ($kukarow["extranet"] == "" and $trow["ei_saldoa"] == "" and $row["tilausrivilinkki"] > 0) {
		$query = "	SELECT tilausrivi.otunnus, lasku.nimi, lasku.tila
					FROM tilausrivi
					JOIN lasku ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus)
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
					and tilausrivi.tunnus  = '$row[tilausrivilinkki]'";
		$siirtores = pupe_query($query);

		if (mysql_num_rows($siirtores) > 0) {
			$siirtorow = mysql_fetch_assoc($siirtores);
			if ($siirtorow["tila"] == "O") {
				$tilaustyypilla = t("Ostotilauksella");
			}
			elseif($siirtorow["tila"] == "V" or $siirtorow["tila"] == "W") {
				$tilaustyypilla = t("Valmistuksella");
			}

			$nayta_sostolisateksti = "TOTTA";
			$varaosakommentti .= "$tilaustyypilla: $siirtorow[nimi] $siirtorow[otunnus]<br>";
		}
	}

	if ($row["alv"] == -999.99) {
		$varaosavirhe .= t("VIRHE: Tuotteelle ei lˆytynyt ALV-prosenttia asiakkaan maahan!")."<br>";
		$riviok++;
		$tilausok++;
		$saako_jalkitoimittaa++;
		$saako_hyvaksya = 0;
	}

	if (($row["var"] == 'J' or $row["var"] == 'P') and $row['tuoteno'] != '' and !in_array($row["var2"], array("OK", "PK"))) {

		//vastaavat tuotteet
		$query  = "SELECT * FROM vastaavat WHERE tuoteno = '$row[tuoteno]' AND yhtio = '$kukarow[yhtio]' ORDER BY if(jarjestys=0, 9999, jarjestys), tuoteno";
		$vastaresult = pupe_query($query);

		if (mysql_num_rows($vastaresult) > 0) {
			$kutsuttu = 0;

			$alku = "<table width='100%'><tr><th>".t("Vastaava")." ".t("Tuoteno")."</th><th>".t("Nimitys")."</th><th>".t("Myyt‰viss‰")."</th><th>".t("Myyntihinta")."</th>";
			$alku .= "<th><form method='post' name='hyvaksy'>
								<input type='hidden' name='toim' 			value = '$toim'>
								<input type='hidden' name='lopetus' 		value = '$lopetus'>
								<input type='hidden' name='ruutulimit' 		value = '$ruutulimit'>
								<input type='hidden' name='projektilla' 	value = '$projektilla'>
								<input type='hidden' name='tilausnumero' 	value = '$tilausnumero'>
								<input type='hidden' name='mista' 			value = '$mista'>
								<input type='hidden' name='rivitunnus' 		value = '$row[tunnus]'>
								<input type='hidden' name='rivilaadittu' 	value = '$row[laadittu]'>
								<input type='hidden' name='menutila' 		value = '$menutila'>
								<input type='hidden' name='tila' 			value = 'OOKOOAA'>
								<input type='hidden' name='vastaavienkasittely' value = 'kylla'>
								<input type='Submit' value='".t("Piilota vastaavat tuotteet")."'>
								</form></th>";
			$alku .= "</tr>";

			if ($row['kommentti'] != "") {
				$alku = "<br>".$alku;
			}

			// tuote lˆytyi, joten haetaan sen id...
			$vrow    = mysql_fetch_array($vastaresult);
			$id		= $vrow['id'];

			// Haetaan vastaavat ketju
			$query = "SELECT * FROM vastaavat WHERE id='$id' AND tuoteno<>'$row[tuoteno]' AND yhtio='$kukarow[yhtio]' ORDER BY jarjestys, tuoteno";

			$vasta2result = pupe_query($query);

			while ($vrow = mysql_fetch_array($vasta2result)) {

				$vaihtoehtoinen = ($vrow['vaihtoehtoinen'] == 'K') ? '<font color=#F00>Vaihtoehtoinen</font>' : '';
				list(, , $vmyytavissa) = saldo_myytavissa($vrow["tuoteno"]);

				if ($vmyytavissa > 0) {

					if ($kutsuttu == 0) {
						$vastaavat_html = $alku;
						$kutsuttu++;
					}

					$tvsql = "SELECT * FROM tuote where yhtio='$kukarow[yhtio]' and tuoteno = '$vrow[tuoteno]'";
					$tvresult = pupe_query($tvsql);
					$tvrow = mysql_fetch_assoc($tvresult);

					list($V_hinta, $V_netto, $V_ale, $V_alehinta_alv, $V_alehinta_val) = alehinta($laskurow, $tvrow, $row['tilkpl'], '', '', '');

					 $vastaavat_html .= 	"<tr class='aktiivi'>"
					 			."<td>{$vrow["tuoteno"]} $vaihtoehtoinen</td>"
								."<td>{$tvrow["nimitys"]}</td>"
					 			."<td align='right'>".sprintf("%.2f", $vmyytavissa)."</td>"
					 			."<td align='right'>".sprintf("%.2f", $V_hinta)."</td>"
					 			."<td align='left'>"
					 			." <form method='post' name='jalkitoimita'>
					 			   	<input type='hidden' name='toim' 			value = '$toim'>
					 			   	<input type='hidden' name='lopetus' 		value = '$lopetus'>
					 			   	<input type='hidden' name='ruutulimit' 		value = '$ruutulimit'>
					 			   	<input type='hidden' name='projektilla' 	value = '$projektilla'>
					 			   	<input type='hidden' name='tilausnumero' 	value = '$tilausnumero'>
					 			   	<input type='hidden' name='mista' 			value = '$mista'>
					 			   	<input type='hidden' name='rivitunnus' 		value = '$row[tunnus]'>
					 			   	<input type='hidden' name='vastaavatuoteno' value = '$vrow[tuoteno]'>
					 			   	<input type='hidden' name='menutila' 		value = '$menutila'>
					 			   	<input type='hidden' name='tila' 			value = 'MUUTA'>
					 			   	<input type='hidden' name='tapa' 			value = 'MYYVASTAAVA'>
					 			   	<input type='Submit' value='".t("Tilaa tuote")."'>
					 			   	</form> "
					 			."</td></tr>";
				}
			}

			if ($kutsuttu > 0) {
				$vastaavat_html .= "</table>";
				$vastaavattuotteet = 1;
			}
		}
	}
