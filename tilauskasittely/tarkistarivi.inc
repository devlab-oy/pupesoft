<?php
	//sis‰‰n tulee $row jossa on tilausrivi kaikki tiedot

	$riviok = 0;
	$saako_hyvaksya = 0;
	$varaosavirhe = "";

	$tquery	= "select * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$row[tuoteno]'";
	$tres	= mysql_query($tquery) or pupe_error($tquery);
	$trow 	= mysql_fetch_array($tres); // haetaan tuotteen tiedot..

	if (mysql_num_rows($tres) == 0) {
		$varaosavirhe .= t("VIRHE: Tuotetta ei lˆydy!")."<br>";
		$riviok++;
		$tilausok++;
	}

	if ($row["hinta"] == 0 and !in_array($row["var"], array('J','S','P','O','H','V')) and strtoupper($trow["tuotetyyppi"]) != 'R') {
		$varaosavirhe .= t("HUOM: Hinta puuttuu!")."<br>";
		$riviok++;
		$tilausok++;
		$saako_hyvaksya++;
	}

	if ($row["varattu"] == 0 and in_array($row["var"], array('','','O','H')) and $row["tyyppi"] != "E" and $row["tyyppi"] != "G") {
		$varaosavirhe .= t("VIRHE: M‰‰r‰ puuttuu!")."<br>";
		$riviok++;
		$tilausok++;
	}

	if ($row["jt"] == 0 and in_array($row["var"], array('J','S')) and $row["tyyppi"] != "E") {
		$varaosavirhe .= t("VIRHE: M‰‰r‰ puuttuu!")."<br>";
		$riviok++;
		$tilausok++;
	}

	if (!in_array($row["var"], array('','P','J','H','S','O','T','U','V','A','B'))) {
		// T‰ss‰ varrien selitykset
		// P - Puute
		// J - Jt
		// H - V‰kisinhyv‰ksytty
		// S - Suoratoimitus pupesoft toimittajalta suoraan asiakkaalle
		// O - Yleinen OK:aus, ohittaa tiettyj‰ tsekkej‰
		// T - Tilataan joltain toimittajalta omaan varastoon
		// U - Tilataan joltain toimittajalta suoraan asiakkaalle
		// V - Tehdaslis‰varusterivi
		// A - Myyntitilirivi joka laskutetaan asiakkaalta
		// B - Myyntitilirivi joka palautetaan myyntitilist‰ omaan varastoon


		$varaosavirhe .= t("VIRHE: Var voi olla tyhj‰, P, J, H, S, T, U, V, A, B tai O!")."<br>";
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

	if ($trow["status"] == 'P' and $row["var"] == "") {
		$varaosavirhe .= t("HUOM: T‰m‰ on poistuva tuote!")."<br>";
		$riviok++;
		$tilausok++;
		$saako_hyvaksya++;
	}

	if ($trow["status"] == 'P' and $row["var"] == "J") {
		$varaosavirhe .= t("VIRHE: T‰m‰ on poistuva tuote, ei voida j‰tt‰‰ j‰lkitoimitukseen!")."<br>";
		$riviok++;
		$tilausok++;
	}

	if ($trow["vienti"] == 'K' and $laskurow["vienti"] != '') {
		$varaosavirhe .= t("VIRHE: T‰m‰ on vientikiellossa oleva tuote, ei voida myyd‰ vientiasiakkaalle!")."<br>";
		$riviok++;
		$tilausok++;
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


	// Otataan oieka kappalem‰‰r k‰sittelyyn
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
	if ($trow["ei_saldoa"] == "" and $row["tyyppi"] != "E" and $kpl_st > 0) {
		// tarkistetaan varaston tyyppi
		$query = "	SELECT *
					FROM varastopaikat
					WHERE
					concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(alkuhyllynro  ,5,'0')) <= concat(rpad(upper('$row[hyllyalue]') ,5,'0'),lpad('$row[hyllynro]' ,5,'0')) and
					concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(loppuhyllynro ,5,'0')) >= concat(rpad(upper('$row[hyllyalue]') ,5,'0'),lpad('$row[hyllynro]' ,5,'0'))
					and yhtio = '$kukarow[yhtio]'";
		$varesult = mysql_query($query) or pupe_error($query);
		$varow = mysql_fetch_array($varesult);

		if ($varow["tyyppi"] == "V" and !in_array($row["var"], array('H','O','P','J','S'))) {
			$varaosavirhe .= "HUOM: Tuote sijaitsee erikoisvarastossa!<br>";
			$riviok++;
			$tilausok++;
			$saako_hyvaksya++;
		}

		//jtrivik‰sittely
		/*
		$query  = "	select count(distinct otunnus) kpl, sum(tilausrivi.jt) varattu
					from tilausrivi use index (yhtio_tyyppi_var_keratty_kerattyaika_uusiotunnus)
					where tilausrivi.yhtio = '$kukarow[yhtio]'
					and tilausrivi.tyyppi  = 'L'
					and tilausrivi.var	 	= 'J'
					and tilausrivi.keratty = ''
					and tilausrivi.kerattyaika = '0000-00-00 00:00:00'
					and tilausrivi.uusiotunnus = 0
					and tilausrivi.tuoteno = '$row[tuoteno]'
					and tilausrivi.otunnus != '$kukarow[kesken]'";
		$jtresult = mysql_query($query) or pupe_error($query);
		$jtrow = mysql_fetch_array($jtresult);


		t‰t‰ ei saa nyt herjata, liian monimutkaista.. herja pit‰isi tulla vain jos ollaan myym‰ss‰ niin paljon ett‰ sen j‰lkeen ei riit‰ saldoa kaikille JT pokille

		if ($jtrow["varattu"] > 0 and $row["var"] == '') {
			$varaosavirhe .= t("HUOM: J‰lkitoimituksessa")." $jtrow[kpl] ".t("asiakkaalla")." $jtrow[varattu] t("kpl")."!<br>";
			$riviok++;
			$tilausok++;
			$saako_hyvaksya++;
		}
		*/

		$paikatlask 	= 0;
		$kotipaikatlask = 0;

		$omapaikatraja 	= 2;

		//Katotaan voidaanko t‰m‰ myyd‰ joltain toiselta paikalta
		$query = "  select tuotepaikat.*, varastopaikat.tyyppi varastotyyppi, varastopaikat.tunnus varastotunnus, varastopaikat.maa varastomaa
					FROM tuotepaikat
					LEFT JOIN varastopaikat ON tuotepaikat.yhtio = varastopaikat.yhtio
					and concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(tuotepaikat.hyllynro ,5,'0')) >= concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(alkuhyllynro  ,5,'0'))
					and concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(tuotepaikat.hyllynro ,5,'0')) <= concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(loppuhyllynro ,5,'0'))
					WHERE tuotepaikat.tuoteno	= '$row[tuoteno]'
					and tuotepaikat.yhtio		= '$kukarow[yhtio]'
					and tuotepaikat.hyllyalue  != '!!M'";
		$omavarastores = mysql_query($query) or pupe_error($query);

		if (($toim == "TARJOUS" or $yhtiorow["tee_osto_myyntitilaukselta"] != '') and $row["var"] != 'V') {
			// Katsotaan onko t‰lle tuotteelle toimittajaa ja oletusvienti on jotain vaihto-omaisuutta
			$query = "	select tyyppi_tieto, liitostunnus, toimi.nimi
						from tuotteen_toimittajat, toimi
						where tuotteen_toimittajat.yhtio = '$kukarow[yhtio]'
						and tuotteen_toimittajat.tuoteno = '$row[tuoteno]'
						and toimi.yhtio	 = tuotteen_toimittajat.yhtio
						and toimi.tunnus = tuotteen_toimittajat.liitostunnus";
			$toimpaikres  = mysql_query($query) or pupe_error($query);

			if(mysql_num_rows($toimpaikres) > 0) {
				$omapaikatraja 	= 1;
			}
		}

		//Tutkitaan voidaanko t‰t‰ rivi suoratoimittaa mist‰‰n varastosta
		$suora_tuoteno 	= $row["tuoteno"];
		$suora_kpl 		= $kpl_st;

		require("suoratoimitusvalinta.inc");

		// K‰‰yd‰‰n l‰pi oman varaston paikat
		if ((in_array($row["var"], array('','O','H','S','T','U')) and mysql_num_rows($omavarastores) >= $omapaikatraja) or (in_array($row["var"], array('S','T','U')) and mysql_num_rows($omavarastores) > 0)) {

			while($alkurow = mysql_fetch_array($omavarastores)) {
				if ($alkurow["varastotyyppi"] != "E" or $laskurow["varasto"] == $alkurow["varastotunnus"] or ($alkurow["hyllyalue"] == $row["hyllyalue"] and $alkurow["hyllynro"] == $row["hyllynro"] and $alkurow["hyllyvali"] == $row["hyllyvali"] and $alkurow["hyllytaso"] == $row["hyllytaso"])) {
					//ennakkopoistot
					$query = "	SELECT sum(varattu) varattu
								FROM tilausrivi
								WHERE tyyppi in ('L','G','V')
								and yhtio = '$kukarow[yhtio]'
								and tuoteno = '$row[tuoteno]'
								and varattu > 0
								and hyllyalue = '$alkurow[hyllyalue]'
								and hyllynro  = '$alkurow[hyllynro]'
								and hyllyvali = '$alkurow[hyllyvali]'
								and hyllytaso = '$alkurow[hyllytaso]'";
					$varatutresult = mysql_query($query) or pupe_error($query);
					$varatutrow = mysql_fetch_array($varatutresult);

					$vapaana = $alkurow["saldo"] - $varatutrow["varattu"];

					if ($vapaana >= $kpl_st or ($row["var"] != "P" and $toim != "TARJOUS" and $alkurow["hyllyalue"] == $row["hyllyalue"] and $alkurow["hyllynro"] == $row["hyllynro"] and $alkurow["hyllyvali"] == $row["hyllyvali"] and $alkurow["hyllytaso"] == $row["hyllytaso"])) {

						$sel = "";

						if ($row["var"] != "P" and $alkurow["hyllyalue"] == $row["hyllyalue"] and $alkurow["hyllynro"] == $row["hyllynro"] and $alkurow["hyllyvali"] == $row["hyllyvali"] and $alkurow["hyllytaso"] == $row["hyllytaso"]) {
							$sel = "SELECTED";
						}

						$varastomaa = '';
						if (strtoupper($alkurow['varastomaa']) != strtoupper($yhtiorow['maakoodi'])) {
							$varastomaa = strtoupper($alkurow['varastomaa']);
						}

						$paikat .= "<option value='$alkurow[hyllyalue]#$alkurow[hyllynro]#$alkurow[hyllyvali]#$alkurow[hyllytaso]' $sel>$varastomaa $alkurow[hyllyalue] $alkurow[hyllynro] $alkurow[hyllyvali] $alkurow[hyllytaso] ($vapaana)</option>";
						$paikatlask++;
						$kotipaikatlask++;
					}
				}
			}
		}

		// K‰‰yd‰‰n l‰pi tuotteen toimittajat
		if (in_array($row["var"], array('','O','H','S','T','U')) and ($toim == "TARJOUS" or $yhtiorow["tee_osto_myyntitilaukselta"] != '')) {
			if ($kotipaikatlask == 0) {
				$paikat = "<option value=''>".t("Tuotetta ei ole omassa varastossa")."</option>".$paikat;
			}

			while ($krow  = mysql_fetch_array($toimpaikres)) {
				$sel1 = "";
				$sel2 = "";

				if ($row["tilaajanrivinro"] == $krow["liitostunnus"] and $row["hyllyalue"] == "" and $row["hyllynro"] == "") {
					if ($row["var"] == 'T') {
						$sel1 = "SELECTED";
					}
					else {
						$sel2 = "SELECTED";
					}
				}

				// tallennetaan riville toimittajan tunnus
				$paikat .= "<option value='###$krow[liitostunnus]' $sel1>$krow[nimi] -> ".t("varastoon")."</option>";
				$paikat .= "<option value='!!!$krow[liitostunnus]' $sel2>$krow[nimi] -> ".t("asiakkaalle")."</option>";
				$paikatlask=$paikatlask+2;
			}
		}
		elseif ($kotipaikatlask == 0) {
			$paikat = "<option value='' $sel>".t("Tuote loppu, j‰t‰ puutteeksi")."</option>".$paikat;
		}

		if ($paikat != "" and $paikatlask > 1) {
			$paikat = "<select Style='{font-size: 8pt; padding:0;}' name='paikka' onchange='submit();'>".$paikat."</select>";
		}
		else {
			$paikat = "";
		}
	}
?>