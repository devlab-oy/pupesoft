<?php

	$riviok = 0;
	$saako_hyvaksya = 0;
	$varaosavirhe = "";

	$query = "	SELECT *
				FROM tuote
				LEFT JOIN tuotteen_toimittajat ON tuote.yhtio	= tuotteen_toimittajat.yhtio and tuote.tuoteno	= tuotteen_toimittajat.tuoteno and tuotteen_toimittajat.liitostunnus = '$laskurow[liitostunnus]'
				LEFT JOIN toimi ON tuote.yhtio = toimi.yhtio and toimi.tunnus = '$laskurow[liitostunnus]'
				WHERE tuote.tuoteno	= '$prow[tuoteno]'
				and tuote.yhtio		= '$kukarow[yhtio]'";
	$rarresult = pupe_query($query);
	$trow = mysql_fetch_array($rarresult);

	if (mysql_num_rows($rarresult) == 0) {
		$varaosavirhe .= t("VIRHE: Tuotetta ei löydy")."!<br>";
		$riviok++;
		$tilausok++;
	}

	if ($trow["toimittaja"] == "" and $toim_tarkistus!= "EI" and $riviok == 0) {
		$varaosavirhe .= t("VIRHE: Tuotetta ei voi tilata tältä toimittajalta")."!<br>";
		$riviok++;
		$tilausok++;
	}

	// katsotaan, ettei tilata epäkuranttia tuotetta
	if (($trow["epakurantti25pvm"] != '0000-00-00' or $trow["epakurantti50pvm"] != '0000-00-00' or $trow["epakurantti75pvm"] != '0000-00-00' or $trow["epakurantti100pvm"] != '0000-00-00') and $riviok == 0) {
		$varaosavirhe .= t("VIRHE: Tuote on epäkurantti, sitä ei voi tilata!")."<br>1/4: $trow[epakurantti25pvm]<br>1/2: $trow[epakurantti50pvm]<br>3/4: $trow[epakurantti75pvm]<br>1/1: $trow[epakurantti100pvm]";
		$riviok++;
		$tilausok++;
	}

	//poistotuotekäsittely
	if (in_array($trow["status"], array('P','X')) and $riviok == 0) {
		$varaosavirhe .= t("VIRHE: Tämä on poistotuote, sitä ei voi tilata")."!<br>";
		$riviok++;
		$tilausok++;
	}

	if ($trow['ei_saldoa'] != '' and $riviok == 0) {
		$varaosavirhe .= t("HUOM: Tuote ei ole varastoitava, sitä ei voida saavuttaa varastoon")."!<br>";
		$riviok++;
	}

	if ($yhtiorow['ostoera_pyoristys'] == 'K' and isset($ostoerahuom) and is_array($ostoerahuom) and in_array($prow['tunnus'], $ostoerahuom) and $kpl_st != $org_kpl) {
		$varaosavirhe .= "<font class='message'>".t("HUOM: Rivin määrä on pyöristetty täyteen ostoerään!")."<br></font>";
	}

	if ((float) $prow["hinta"] == 0 and (float) $trow['ostohinta'] == 0 and $riviok == 0 and $prow["var2"] != "OK") {
		// $varaosavirhe .= t("HUOM: Tuotteelta puuttuu ostohinta")."!<br>";
		// $riviok++;
		// $tilausok++;
		// $saako_hyvaksya++;
	}

	if ($trow["myyntihinta_maara"] != 0 and $prow["hinta"] != 0) {
		$prow["hinta"] = round($prow["hinta"] * $trow["myyntihinta_maara"], $yhtiorow["hintapyoristys"]);
	}

	if ((float) $prow["hinta"] != (float) $trow['ostohinta'] and ($prow["var"] == "" or $toiminto == 'kohdista') and $trow["toimittaja"] != "" and $riviok == 0) {

		$paiv_hinta = (float) $paiv_hinta;

		if ($paivitaostohinta == 'ON' and ($ALPA != '' or $JOPA != '') and $prow["tuoteno"] == $paiv_tuoteno and $prow["tunnus"] == $paiv_rivitunnus) {
			$paok = '';
			if ($toiminto != 'kohdista') {
				$oshinq = "	UPDATE tilausrivi
							set var		= 'O'
							where yhtio	= '$kukarow[yhtio]'
							and tunnus	= '$prow[tunnus]'
							and otunnus = '$kukarow[kesken]'";
				$ishinr = pupe_query($oshinq);
			}
			else {
				$paok = 'ok';
			}

			$ALPA = '';

			if ($JOPA == '') {
				$paivitaostohinta = '';
			}
		}

		if ($paivitaostohinta == 'ON' and $JOPA != '' and $prow["tuoteno"] == $paiv_tuoteno and ($prow["tunnus"] == $paiv_rivitunnus or $toiminto == 'kohdista')) {
			$paok ='';
			$oshinq = "	UPDATE tuotteen_toimittajat
						SET ostohinta		= '$paiv_hinta'
						WHERE yhtio			= '$kukarow[yhtio]'
						AND tuoteno			= '$prow[tuoteno]'
						AND liitostunnus	= '$laskurow[liitostunnus]'";
			$ishinr = pupe_query($oshinq);

			$paok = 'ok';

			$JOPA = '';
			$paivitaostohinta = '';

			if ($toiminto == 'kohdista') {
				echo "<font class='message'>".t("Päivitettiin tuotteen")." $trow[tuoteno] ".t("ostohinta").": $trow[ostohinta] -> $paiv_hinta</font><br>";
			}
			else {
				$varaosavirhe .= "<font class='message'>".t("Päivitettiin tuotteen")." $trow[tuoteno] ".t("ostohinta").": $trow[ostohinta] -> $paiv_hinta</font>";
			}

		}

		$varquery = "SELECT var FROM tilausrivi WHERE yhtio = '$kukarow[yhtio]' and tunnus	= '$prow[tunnus]'";
		$varresult = pupe_query($varquery);
		$varrow  = mysql_fetch_array($varresult);

		if (($toiminto != 'kohdista' and ($varrow['var'] != 'O' or $prow['var'] != 'O') and ($prow["tunnus"] != $paiv_rivitunnus or $paiv_rivitunnus == '')) or (($toiminto == 'kohdista' and $paok == ''))) {
			$varaosavirhe .= "	<form name='tilaus' action='$PHP_SELF#$rivitunnus' method='post' autocomplete='off'>
								<input type='hidden' name='toim' 				value = '$toim'>
								<input type='hidden' name='lopetus' 			value = '$lopetus'>
								<input type='hidden' name='tilausnumero' 		value = '$tilausnumero'>
								<input type='hidden' name='toim_nimitykset' 	value = '$toim_nimitykset'>
								<input type='hidden' name='toim_tuoteno'		value = '$toim_tuoteno'>
								<input type='hidden' name='naytetaankolukitut' 	value = '$naytetaankolukitut'>
								<input type='hidden' name='toiminto' 			value = '$toiminto'>
								<input type='hidden' name='tee' 				value = 'Y'>
								<input type='hidden' name='muut_siirrettavat' 	value = '$muut_siirrettavat'>
								<input type='hidden' name='rivinotunnus' 		value = '$rivinotunnus'>
								<input type='hidden' name='paivitaostohinta' 	value = 'ON'>
								<input type='hidden' name='paiv_hinta' 			value = '$prow[hinta]'>
								<input type='hidden' name='paiv_tuoteno' 		value = '$prow[tuoteno]'>
								<input type='hidden' name='paiv_rivitunnus' 	value = '$prow[tunnus]'>";

			if ($toiminto == "kohdista") {
				$varaosavirhe .= "<input type='hidden' name='tuoteno' 	value = '$tuoteno'>";
				$varaosavirhe .= "<input type='hidden' name='kpl' 		value = '$kpl'>";
				$varaosavirhe .= "<input type='hidden' name='var' 		value = '$var'>";
				$varaosavirhe .= "<input type='hidden' name='hinta' 	value = '$hinta'>";
				$varaosavirhe .= "<input type='hidden' name='toimaika' 	value = '$toimaika'>";
				$varaosavirhe .= "<input type='hidden' name='alv' 		value = '$alv'>";
				$varaosavirhe .= "<input type='hidden' name='kommentti' value = '$kommentti'>";
			}

			$varaosavirhe .= "	<font class='message'>".t("Päivitä tuotteen ostohinta").":<br> ".hintapyoristys($trow["ostohinta"])." $trow[valuutta] -> ".hintapyoristys($prow["hinta"])." $trow[valuutta]</font><br>
								<input type='submit' name='JOPA' value='".t("Päivitä")."'>
								<input type='submit' name='ALPA' value='".t("Älä päivitä")."'>
								</form>";
		}
	}

	//toimitusajan päivitys toimittajan toimitusaikaan
	if ((($trow["toimitusaika"] != 0 and $trow["toimitusaika"] != '') or ($trow["oletus_toimaika"] != 0 and $trow["oletus_toimaika"] != '')) and $riviok == 0 and strpos($_SERVER['SCRIPT_NAME'], "tilaus_osto.php") !== FALSE and ($yhtiorow["ostotilaukseen_toimittajan_toimaika"] == '1' or $yhtiorow["ostotilaukseen_toimittajan_toimaika"] == '2')) {

		if ($trow["toimitusaika"] != 0 and $trow["toimitusaika"] != '') {
			$ehdotus_pvm = date('Y-m-d',time() + $trow["toimitusaika"] * 24 * 60 * 60);
		}
		elseif ($trow["oletus_toimaika"] != 0 and $trow["oletus_toimaika"] != '') {
			$ehdotus_pvm = date('Y-m-d',time() + $trow["oletus_toimaika"] * 24 * 60 * 60);
		}

		if ($paivitatoimaika == "ON" and $prow["tunnus"] == $paiv_rivitunnus and $JOPATO != '') {

			if ($JOPATO != '') {
				$paivita_toimaika = "	UPDATE tilausrivi
										SET toimaika = '$paiv_toimaika'
										WHERE yhtio = '$kukarow[yhtio]'
										and tunnus	= '$paiv_rivitunnus'
										and otunnus = '$kukarow[kesken]'";
				$paivita_res = pupe_query($paivita_toimaika);


			}

			$varaosavirhe .= "<font class='message'>".t("Päivitettiin tuotteen")." $trow[tuoteno] ".t("toimitusaika").": ".tv1dateconv($prow["toimaika"])." -> ".tv1dateconv($ehdotus_pvm)."</font>";
			$paivitetty_ok = "YES";
			$JOPATO = "";
		}
		elseif ($ehdotus_pvm == $prow["toimaika"]) {
			$paivitetty_ok = "SAMA";
		}
		else {
			$paivitetty_ok = "";
		}

		if ($ehdotus_pvm != $prow["toimaika"] and $paivitetty_ok == "") {

			if ($varaosavirhe != "") {
				$varaosavirhe .= "<br>";
			}

			$varaosavirhe .= "	<form name='tilaus' action='$PHP_SELF#$rivitunnus' method='post' autocomplete='off'>
								<input type='hidden' name='toim' 				value = '$toim'>
								<input type='hidden' name='lopetus' 			value = '$lopetus'>
								<input type='hidden' name='tilausnumero' 		value = '$tilausnumero'>
								<input type='hidden' name='toim_nimitykset' 	value = '$toim_nimitykset'>
								<input type='hidden' name='toim_tuoteno'		value = '$toim_tuoteno'>
								<input type='hidden' name='naytetaankolukitut' 	value = '$naytetaankolukitut'>
								<input type='hidden' name='toiminto' 			value = '$toiminto'>
								<input type='hidden' name='tee' 				value = 'Y'>
								<input type='hidden' name='muut_siirrettavat' 	value = '$muut_siirrettavat'>
								<input type='hidden' name='rivinotunnus' 		value = '$rivinotunnus'>
								<input type='hidden' name='paivitatoimaika' 	value = 'ON'>
								<input type='hidden' name='paiv_toimaika' 		value = '$ehdotus_pvm'>
								<input type='hidden' name='paiv_rivitunnus' 	value = '$prow[tunnus]'>
								<font class='message'>".t("Haluatko muuttaa toimitusajan")." ".tv1dateconv($prow["toimaika"])." ".t("tuotteen toimittajan toimitusaikaan")." ".tv1dateconv($ehdotus_pvm)."!</font><br>
								<input type='submit' name='JOPATO' value='".t("Päivitä")."'>
								</form>";
		}

	}
?>