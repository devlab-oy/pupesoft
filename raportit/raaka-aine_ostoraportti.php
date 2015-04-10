<?php

	require("../inc/parametrit.inc");

	// Otetaan jQuery mukaan
	echo "<script src='{$palvelin2}inc/jquery.min.js'></script>";

	// Salitaan vain numeroita ja piste/pilkku input kentiss‰
	echo '<script language="javascript">
	$(document).ready(function() {
	    $("#vain_numeroita").keydown(function(event) {
	        // sallitaan backspace (8) ja delete (46)
	        if ( event.keyCode == 46 || event.keyCode == 8 ) {
	            // anna sen vaan tapahtua...
	        }
	        else {
	            // 48-57 on normin‰pp‰imistˆn numerot, numpad numerot on 96-105, piste on 190 ja pilkku 188
	            if ((event.keyCode < 48 || event.keyCode > 57) && (event.keyCode < 96 || event.keyCode > 105 ) && event.keyCode != 188 && event.keyCode != 190) {
	                event.preventDefault();
	            }
	        }
	    });
	});
	</script>';

	echo "<font class='head'>".t("Raaka-aineiden ostoraportti")."</font><hr>";

	// Ehdotetaan oletuksena ehdotusta ensikuun myynnille sek‰ siit‰ plus 3 kk
	if (!isset($kka1)) $kka1 = date("m", mktime(0, 0, 0, date("m")+1, 1, date("Y")));
	if (!isset($vva1)) $vva1 = date("Y", mktime(0, 0, 0, date("m")+1, 1, date("Y")));
	if (!isset($kkl1)) $kkl1 = date("m", mktime(0, 0, 0, date("m")+4, 0, date("Y")));
	if (!isset($vvl1)) $vvl1 = date("Y", mktime(0, 0, 0, date("m")+4, 0, date("Y")));

	// P‰iv‰m‰‰r‰tarkistus
	if (!checkdate($kka1, 1, $vva1)) {
		echo "<font class='error'>".t("Virheellinen alkup‰iv‰!")."</font><br>";
		$tee = "";
	}
	else {
		$nykyinen_alku  = date("Y-m-d", mktime(0, 0, 0, $kka1, 1, $vva1));
		$edellinen_alku = date("Y-m-d", mktime(0, 0, 0, $kka1, 1, $vva1-1));
	}

	if (!checkdate($kkl1, 1, $vvl1)) {
		echo "<font class='error'>".t("Virheellinen loppup‰iv‰!")."</font><br>";
		$tee = "";
	}
	else {
		$nykyinen_loppu  = date("Y-m-d", mktime(0, 0, 0, $kkl1+1, 0, $vvl1));
		$edellinen_loppu = date("Y-m-d", mktime(0, 0, 0, $kkl1+1, 0, $vvl1-1));
	}

	if ($nykyinen_alku > $nykyinen_loppu) {
		echo "<font class='error'>".t("Virheelliset kaudet!")." $nykyinen_alku > $nykyinen_loppu</font><br>";
		$tee = "";
	}

	// Muuttujia
	$ytunnus = isset($ytunnus) ? trim($ytunnus) : "";
	$toimittajaid = isset($toimittajaid) ? trim($toimittajaid) : "";

	// T‰m‰ palauttaa yhden tuotteen ostosuosituksen tiedot
	function teerivi($tuoteno, $valittu_toimittaja) {

		// Kukarow ja p‰iv‰m‰‰r‰t globaaleina
		global $kukarow, $edellinen_alku, $edellinen_loppu, $nykyinen_alku, $nykyinen_loppu;

		// Tehd‰‰n kaudet p‰iv‰m‰‰rist‰
		$alku_kausi = substr(str_replace("-", "", $nykyinen_alku), 0, 6);
		$loppu_kausi = substr(str_replace("-", "", $nykyinen_loppu), 0, 6);

		// Haetaan lapsituotteen varastosaldo
		$query = "	SELECT ifnull(sum(saldo),0) saldo
					FROM tuotepaikat
					WHERE tuotepaikat.yhtio = '{$kukarow["yhtio"]}'
					AND tuotepaikat.tuoteno = '$tuoteno'";
		$result = pupe_query($query);
		$row = mysql_fetch_assoc($result);
		$lapsi_saldo = $row['saldo'];

		// Haetaan lapsituotteen vuosikulutus (rullaava 12 kk)
		$query = "	SELECT sum(tilausrivi.kpl) vuosikulutus
					FROM tilausrivi
					WHERE tilausrivi.yhtio = '{$kukarow["yhtio"]}'
					AND tilausrivi.tyyppi = 'V'
					AND tilausrivi.tuoteno = '$tuoteno'
					AND tilausrivi.toimitettuaika >= DATE_SUB(now(), INTERVAL 1 YEAR)";
		$result = pupe_query($query);
		$row = mysql_fetch_assoc($result);
		$lapsi_vuosikulutus = $row['vuosikulutus'];

		// Haetaan lapsituotteen tilauksessa oleva m‰‰r‰
		$query = "	SELECT sum(tilausrivi.varattu) tilattu
					FROM tilausrivi
					WHERE tilausrivi.yhtio = '{$kukarow["yhtio"]}'
					AND tilausrivi.tyyppi = 'O'
					AND tilausrivi.tuoteno = '$tuoteno'
					AND tilausrivi.varattu != 0";
		$result = pupe_query($query);
		$row = mysql_fetch_assoc($result);
		$lapsi_tilattu = $row['tilattu'];

		// Haetaan lapsituotteen varattu m‰‰r‰
		$query = "	SELECT sum(tilausrivi.varattu) varattu
					FROM tilausrivi
					WHERE tilausrivi.yhtio = '{$kukarow["yhtio"]}'
					AND tilausrivi.tyyppi = 'L'
					AND tilausrivi.tuoteno = '$tuoteno'
					AND tilausrivi.varattu != 0";
		$result = pupe_query($query);
		$row = mysql_fetch_assoc($result);
		$lapsi_varattu = $row['varattu'];

		// Jos ollaan rajattu toimittaja k‰yttˆliittym‰ss‰, haetaan sen tiedot eik‰ oletustoimittajaa
		$toimittaja_rajaus = ($valittu_toimittaja > 0) ? "and toimi.tunnus = '$valittu_toimittaja'" : "";

		// Haetaan lapsituotteen toimittajatiedot
		$query = "	SELECT if(tuotteen_toimittajat.toimitusaika > 0, tuotteen_toimittajat.toimitusaika, 0) toimitusaika,
					if(tuotteen_toimittajat.pakkauskoko > 0, tuotteen_toimittajat.pakkauskoko, 1) pakkauskoko,
					tuotteen_toimittajat.toimittaja,
					toimi.nimi,
					toimi.tunnus
					FROM tuotteen_toimittajat
					JOIN toimi ON (toimi.yhtio = tuotteen_toimittajat.yhtio AND toimi.tunnus = tuotteen_toimittajat.liitostunnus $toimittaja_rajaus)
					WHERE tuotteen_toimittajat.yhtio = '{$kukarow["yhtio"]}'
					AND tuotteen_toimittajat.tuoteno = '$tuoteno'
					ORDER BY if(jarjestys = 0, 9999, jarjestys)
					LIMIT 1";
		$result = pupe_query($query);

		if (mysql_num_rows($result) == 1) {
			$toimittajarow = mysql_fetch_assoc($result);
		}
		else {
			// Toimittajaa ei lˆydy -> alustetaan defaulttiarvot (lis‰‰ t‰h‰n jos muutat query‰)
			$toimittajarow = array(
							"toimitusaika" => 0,
							"pakkauskoko" => 1,
							"toimittaja" => "",
							"nimi" => t("Ei toimittajaa"),
							"tunnus" => 0,
							);
		}

		// Loopataan l‰pi lapsituotteen is‰tuotteet ja lasketaan osto ehdotukset
		$query = "	SELECT isatuoteno, kerroin
					FROM tuoteperhe
					WHERE yhtio = '{$kukarow["yhtio"]}'
					AND tuoteno = '{$tuoteno}'
					AND tyyppi = 'R'";
		$isatuote_result = pupe_query($query);

		// While loopissa k‰ytett‰v‰t muuttujat
		$lapsi_kulutusennuste = 0;

		while ($isatuote_row = mysql_fetch_assoc($isatuote_result)) {

			// Haetaan is‰tuotteen budjetoitu myynti
			$query = "	SELECT ifnull(sum(maara), 0) maara
						FROM budjetti_tuote
						WHERE budjetti_tuote.yhtio = '{$kukarow["yhtio"]}'
						AND budjetti_tuote.kausi >= '$alku_kausi'
						AND budjetti_tuote.kausi <= '$loppu_kausi'
						AND budjetti_tuote.tuoteno = '{$isatuote_row["isatuoteno"]}'";
			$result = pupe_query($query);
			$row = mysql_fetch_assoc($result);
			$isa_budjetoitu_myynti = $row['maara'];

			// Jos ei ole budjettia, otetaan edellisen kauden myynti ja k‰ytet‰‰n sit‰
			if ($isa_budjetoitu_myynti == 0) {
				$query = "	SELECT ifnull(sum(tilausrivi.kpl), 0) myynti_ed
							FROM tilausrivi
							WHERE tilausrivi.yhtio = '{$kukarow["yhtio"]}'
							AND tilausrivi.tyyppi = 'L'
							AND tilausrivi.tuoteno = '{$isatuote_row["isatuoteno"]}'
							AND tilausrivi.laskutettuaika >= '$edellinen_alku'
							AND tilausrivi.laskutettuaika <= '$edellinen_loppu'";
				$result = pupe_query($query);
				$row = mysql_fetch_assoc($result);
				$isa_budjetoitu_myynti = $row["myynti_ed"];
			}

			// Haetaan is‰tuotteiden varastosaldo
			$query = "	SELECT ifnull(sum(saldo), 0) saldo
						FROM tuotepaikat
						WHERE tuotepaikat.yhtio = '{$kukarow["yhtio"]}'
						AND tuotepaikat.tuoteno = '{$isatuote_row["isatuoteno"]}'";
			$result = pupe_query($query);
			$row = mysql_fetch_assoc($result);
			$isa_saldo = $row['saldo'];

			// Haetaan is‰tuotteen tilauksessa oleva m‰‰r‰
			$query = "	SELECT sum(tilausrivi.varattu) tilattu
						FROM tilausrivi
						WHERE tilausrivi.yhtio = '{$kukarow["yhtio"]}'
						AND tilausrivi.tyyppi = 'O'
						AND tilausrivi.tuoteno = '{$isatuote_row["isatuoteno"]}'
						AND tilausrivi.varattu != 0";
			$result = pupe_query($query);
			$row = mysql_fetch_assoc($result);
			$isa_tilattu = $row['tilattu'];

			// Haetaan is‰tuotteen varattu m‰‰r‰
			$query = "	SELECT sum(tilausrivi.varattu) varattu
						FROM tilausrivi
						WHERE tilausrivi.yhtio = '{$kukarow["yhtio"]}'
						AND tilausrivi.tyyppi = 'L'
						AND tilausrivi.tuoteno = '{$isatuote_row["isatuoteno"]}'
						AND tilausrivi.varattu != 0";
			$result = pupe_query($query);
			$row = mysql_fetch_assoc($result);
			$isa_varattu = $row['varattu'];

			// Is‰tuotteen myyntiennuste
			$isa_reaalisaldo = $isa_saldo + $isa_tilattu - $isa_varattu;
			$isa_myyntiennuste = $isa_budjetoitu_myynti - $isa_reaalisaldo;

			// Jos myyntiennuste on miinusta, nollataan ennuste, ettei se v‰henn‰ raaka-aine tarvetta (jo valmistetuista tuotteista ei voida k‰ytt‰‰ raaka-aineita)
			$isa_myyntiennuste = ($isa_myyntiennuste < 0) ? 0 : $isa_myyntiennuste;

			// Lapsen kulutusennuste
			$lapsi_kerroin = $isatuote_row["kerroin"];
			$lapsi_kulutusennuste += ($isa_myyntiennuste * $lapsi_kerroin);
		}

		// Lasketaan lapsituotteen ostosuositus
		$lapsi_reaalisaldo = $lapsi_saldo + $lapsi_tilattu - $lapsi_varattu;
		$lapsi_toimitusaika = $toimittajarow['toimitusaika'];
		$lapsi_paivakulutus = round($lapsi_vuosikulutus / 360);
		$lapsi_riittopv = ($lapsi_paivakulutus == 0) ? t("Ei tiedossa") : floor($lapsi_reaalisaldo / $lapsi_paivakulutus);
		$lapsi_kulutusennuste = $lapsi_kulutusennuste - $lapsi_reaalisaldo;
		$lapsi_maaraennuste = ($lapsi_paivakulutus * $lapsi_toimitusaika) + $lapsi_kulutusennuste;
		$lapsi_ostosuositus = ceil($lapsi_maaraennuste - $lapsi_reaalisaldo);
		$lapsi_ostettavamaara = ceil($lapsi_ostosuositus / $toimittajarow['pakkauskoko']) * $toimittajarow['pakkauskoko'];

		// Palautettava array
		$tuoterivi = array();
		$tuoterivi['kulutusennuste'] 		= $lapsi_kulutusennuste;
		$tuoterivi['maaraennuste']			= $lapsi_maaraennuste;
		$tuoterivi['riittopv'] 				= $lapsi_riittopv;
		$tuoterivi['reaalisaldo'] 			= $lapsi_reaalisaldo;
		$tuoterivi['ostosuositus'] 			= $lapsi_ostosuositus;
		$tuoterivi['ostoeramaara']			= $lapsi_ostettavamaara;
		$tuoterivi['toimittajan_tunnus'] 	= $toimittajarow['tunnus'];
		$tuoterivi['toimittajan_ytunnus'] 	= $toimittajarow['toimittaja'];
		$tuoterivi['toimittajan_nimi'] 		= $toimittajarow['nimi'];

		return $tuoterivi;
	}

	// Jos saadaan muut parametrit tehd‰‰n niist‰ muuttujat
	if (isset($muutparametrit)) {
		foreach (explode("##", $muutparametrit) as $muutparametri) {
			list($a, $b) = explode("=", $muutparametri);
			${$a} = $b;
		}
		$tee = "";
	}

	// Toimittajahaku
	if ($ytunnus != "" and $toimittajaid == "") {

		// Tehd‰‰n muut parametrit
		$muutparametrit = "";
		unset($_POST["toimittajaid"]);

		foreach ($_POST as $key => $value) {
			$muutparametrit .= $key."=".$value."##";
		}

		require ("inc/kevyt_toimittajahaku.inc");

		if ($toimittajaid == 0) {
			$tee = "ƒLƒMEEMIHINKƒƒN";
		}
		else {
			$tee = "";
		}
	}

	// Tehd‰‰n ostotilaukset
	if (isset($tee) and $tee == "TEE_OSTOTILAUKSET") {

		$rows = 0;
		$edellinen_toimittaja = false;

		foreach ($ostettava_maara as $index => $maara) {

			$maara = (float) str_replace(",", ".", $maara);
			$toimittaja = mysql_real_escape_string($ostettava_toimittaja[$index]);
			$tuoteno = mysql_real_escape_string($ostettava_tuoteno[$index]);

			// Oikellisuustarkastus hoidetaan javascriptill‰, ei voi tulla kun numeroita!
			if ($maara != 0) {

				if ($edellinen_toimittaja != $toimittaja) {

					$query = "	SELECT *
								FROM toimi
								WHERE toimi.yhtio = '{$kukarow["yhtio"]}'
								AND toimi.tunnus = '$toimittaja'";
					$result = pupe_query($query);
					$toimittajarow = mysql_fetch_assoc($result);

					$query = "	INSERT INTO lasku SET
								yhtio               = '{$kukarow["yhtio"]}',
								yhtio_nimi          = '{$yhtiorow["nimi"]}',
								yhtio_osoite        = '{$yhtiorow["osoite"]}',
								yhtio_postino       = '{$yhtiorow["postino"]}',
								yhtio_postitp       = '{$yhtiorow["postitp"]}',
								yhtio_maa           = '{$yhtiorow["maa"]}',
								toim_nimi           = '{$yhtiorow["nimi"]}',
								toim_osoite         = '{$yhtiorow["osoite"]}',
								toim_postino        = '{$yhtiorow["postino"]}',
								toim_postitp        = '{$yhtiorow["postitp"]}',
								toim_maa            = '{$yhtiorow["maa"]}',
								nimi                = '{$toimittajarow["nimi"]}',
								osoite              = '{$toimittajarow["osoite"]}',
								postino             = '{$toimittajarow["postino"]}',
								postitp             = '{$toimittajarow["postitp"]}',
								maa                 = '{$toimittajarow["maa"]}',
								valkoodi            = '{$toimittajarow["oletus_valkoodi"]}',
								toimaika            = now(),
								laatija             = '{$kukarow["kuka"]}',
								luontiaika          = now(),
								tila                = 'O',
								toimitusehto        = '{$toimittajarow["toimitusehto"]}',
								liitostunnus        = '{$toimittajarow["tunnus"]}',
								ytunnus             = '{$toimittajarow["ytunnus"]}',
								ovttunnus           = '{$toimittajarow["ovttunnus"]}',
								tilausyhteyshenkilo = '{$toimittajarow["yhteyshenkilo"]}'";
					$result = pupe_query($query);
					$otunnus = mysql_insert_id();

					$rows++;
					$edellinen_toimittaja = $toimittaja;
				}

				$query = "	SELECT tuote.try,
							tuote.osasto,
							tuote.nimitys,
							tuote.yksikko,
							tuotteen_toimittajat.ostohinta
							FROM tuote
							JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tuote.yhtio
								AND tuotteen_toimittajat.tuoteno = tuote.tuoteno
								AND tuotteen_toimittajat.liitostunnus = '{$toimittajarow["tunnus"]}')
							WHERE tuote.yhtio = '{$kukarow["yhtio"]}'
							AND tuote.tunnus = '$toimittaja'";
				$result = pupe_query($query);
				$tuoterow = mysql_fetch_assoc($result);

				$query = "	INSERT INTO tilausrivi SET
							yhtio     = '{$kukarow["yhtio"]}',
							tyyppi    = 'O',
							toimaika  = now(),
							kerayspvm = now(),
							otunnus   = '$otunnus',
							tuoteno   = '$tuoteno',
							try       = '{$tuoterow["try"]}',
							osasto    = '{$tuoterow["osasto"]}',
							nimitys   = '{$tuoterow["nimitys"]}',
							tilkpl    = '$maara',
							yksikko   = '{$tuoterow["yksikko"]}',
							varattu   = '$maara',
							hinta     = '{$tuoterow["ostohinta"]}',
							laatija   = '{$kukarow["kuka"]}',
							laadittu  = now()";
				$result = pupe_query($query);
			}
		}

		echo $rows." ".t('ostotilausta muodostettu.');
		echo "<br><br>";
		$tee = "";
	}

	// Tehd‰‰n raportti
	if (isset($tee) and $tee == "RAPORTOI") {

		$tuote_where       = ""; // tuote-rajauksia
		$toimittaja_join   = ""; // toimittaja-rajauksia
		$toimittaja_select = ""; // toimittaja-rajauksia
		$abc_join          = ""; // abc-rajauksia

		if ($osasto != '') {
			$tuote_where .= " and tuote.osasto = '$osasto'";
		}

		if ($tuoryh != '') {
			$tuote_where .= " and tuote.try = '$tuoryh'";
		}

		if ($tuotemerkki != '') {
			$tuote_where .= " and tuote.tuotemerkki = '$tuotemerkki'";
		}

		if ($toimittajaid != '') {
			// Jos ollaan rajattu toimittaja, niin otetaan vain sen toimittajan tuotteet ja laitetaan mukaan selectiin
			$toimittaja_join = "JOIN tuotteen_toimittajat ON (tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno and liitostunnus = '$toimittajaid')";
			$toimittaja_select = "tuotteen_toimittajat.toimittaja";
		}
		else {
			// Jos toimittajaa ei olla rajattu, haetaan tuotteen oletustoimittaja subqueryll‰
			$toimittaja_select = "(SELECT toimittaja FROM tuotteen_toimittajat WHERE tuotteen_toimittajat.yhtio = tuote.yhtio AND tuotteen_toimittajat.tuoteno = tuote.tuoteno ORDER BY if(jarjestys = 0, 9999, jarjestys), toimittaja LIMIT 1) toimittaja";
		}

		if ($abcrajaus != "") {
			// TODO: n‰it‰ kahta ei ole setattu ainakaan t‰ss‰ tiedostossa, voiko koko muuttujat poistaa?
			if(!isset($lisaa)) $lisaa = null;
			if(!isset($lisavarattu)) $lisavarattu = null;

			// katotaan JT:ss‰ olevat tuotteet ABC-analyysi‰ varten, koska ne pit‰‰ includata aina!
			$query = "	SELECT group_concat(distinct concat(\"'\",tilausrivi.tuoteno,\"'\") separator ',')
						FROM tilausrivi USE INDEX (yhtio_tyyppi_var_keratty_kerattyaika_uusiotunnus)
						JOIN tuote USE INDEX (tuoteno_index) ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno $lisaa)
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and tyyppi IN  ('L','G')
						and var = 'J'
						and jt $lisavarattu > 0";
			$vtresult = pupe_query($query);
			$vrow = mysql_fetch_array($vtresult);

			$jt_tuotteet = "''";

			if ($vrow[0] != "") {
				$jt_tuotteet = $vrow[0];
			}

			// joinataan ABC-aputaulu katteen mukaan lasketun luokan perusteella
			$abc_join = " JOIN abc_aputaulu use index (yhtio_tyyppi_tuoteno) ON (abc_aputaulu.yhtio = tuote.yhtio
						AND abc_aputaulu.tuoteno = tuote.tuoteno
						AND abc_aputaulu.tyyppi = '$abcrajaustapa'
						AND (luokka <= '$abcrajaus' or luokka_osasto <= '$abcrajaus' or luokka_try <= '$abcrajaus' or tuote_luontiaika >= date_sub(current_date, interval 12 month) or abc_aputaulu.tuoteno in ($jt_tuotteet))) ";
		}

		// Haetaan raaka-aineet, jotka osuvat hakuehtoihin
		$query = "	SELECT tuote.tuoteno,
					tuote.nimitys,
					$toimittaja_select
					FROM tuote
					JOIN tuoteperhe ON (tuote.tuoteno = tuoteperhe.tuoteno AND tuote.yhtio = tuoteperhe.yhtio AND tuoteperhe.tyyppi = 'R')
					$toimittaja_join
					$abc_join
					WHERE tuote.yhtio = '{$kukarow["yhtio"]}'
					AND tuote.ei_saldoa = ''
					$tuote_where
					GROUP BY 1, 2, 3
					ORDER BY toimittaja, tuote.try, tuote.tuoteno";
		$res = pupe_query($query);

		echo t("Tuotteita")." ".mysql_num_rows($res)." ".t("kpl").".<br>\n";
		echo t("N‰ytet‰‰n ostotarve aikav‰lille").": $nykyinen_alku - $nykyinen_loppu";

		$ostoehdotus_riveja = 0;

		if (mysql_num_rows($res) > 0) {

			// Kootaan raportti
			echo "<form action='$PHP_SELF' method='post'>";
			echo "<table>";

			$EDtoimittaja = false;
			$toimittaja_header_piirretty = false;
			$formin_pointteri = 0;

			// loopataan tuotteet l‰pi
			while ($row = mysql_fetch_assoc($res)) {

				// Haetaan tuotteen tiedot
				$tuoterivi = teerivi($row["tuoteno"], $toimittajaid);

				// Toimittaja vaihtuu
				if ($tuoterivi['toimittajan_tunnus'] != $EDtoimittaja or $EDtoimittaja === false) {
					$toimittaja_header = "<tr>";
					$toimittaja_header .= "<td class='back' colspan='8'><font class='head'><br>{$tuoterivi["toimittajan_ytunnus"]} {$tuoterivi["toimittajan_nimi"]}</font></td>";
					$toimittaja_header .= "</tr>";
					$toimittaja_header .= "<tr>";
					$toimittaja_header .= "<th>".t("Tuotenumero")."</th>";
					$toimittaja_header .= "<th>".t("Nimitys")."</th>";
					$toimittaja_header .= "<th>".t("Kulutusennuste")."</th>";
					$toimittaja_header .= "<th>".t("M‰‰r‰ennuste")."</th>";
					$toimittaja_header .= "<th>".t("Riitto Pv")."</th>";
					$toimittaja_header .= "<th>".t("Reaalisaldo")."</th>";
					$toimittaja_header .= "<th>".t("Ostosuositus")."</th>";
					$toimittaja_header .= "<th>".t("Ostoer‰m‰‰r‰")."</th>";
					$toimittaja_header .= "</tr>";
					$toimittaja_header_piirretty = false;
				}

				$EDtoimittaja = $tuoterivi['toimittajan_tunnus'];
				$EDtoimittaja_ytunnus = $tuoterivi['toimittajan_ytunnus'];
				$EDtoimittaja_nimi = $tuoterivi['toimittajan_nimi'];

				// Jos tuotetta ei tarvitse ostaa, ei n‰ytet‰ sit‰ ostoehdotuksessa
				if ($tuoterivi['ostoeramaara'] <= 0) {
					continue;
				}

				$ostoehdotus_riveja++;

				// Pit‰‰ s‰ilytt‰‰ table-headeria muuttujassa, sill‰ voi olla ett‰ toimittajalle ei tule yht‰‰n tuoterivi‰ ehdotukseen (eik‰ haluta piirt‰‰ turhaa headeri‰)
				if ($toimittaja_header_piirretty == false) {
					echo $toimittaja_header;
					$toimittaja_header_piirretty = true;
				}

				echo "<tr>";
				echo "<td>{$row["tuoteno"]}</td>";
				echo "<td>{$row["nimitys"]}</td>";
				echo "<td style='text-align: right;'>{$tuoterivi["kulutusennuste"]}</td>";
				echo "<td style='text-align: right;'>{$tuoterivi["maaraennuste"]}</td>";
				echo "<td style='text-align: right;'>{$tuoterivi["riittopv"]}</td>";
				echo "<td style='text-align: right;'>{$tuoterivi["reaalisaldo"]}</td>";
				echo "<td style='text-align: right;'>{$tuoterivi["ostosuositus"]}</td>";

				// Tuotteella ei ole toimittajaa
				if (empty($EDtoimittaja)) {
					echo "<td style='text-align: right;'>{$tuoterivi["ostoeramaara"]}</td>";
				}
				else {
					echo "<td style='text-align: right;'>";
					echo "<input size='8' style='text-align: right;' type='text' name='ostettava_maara[$formin_pointteri]' value='{$tuoterivi["ostoeramaara"]}' id='vain_numeroita'>";
					echo "<input type='hidden' name='ostettava_tuoteno[$formin_pointteri]' value='{$row["tuoteno"]}'>";
					echo "<input type='hidden' name='ostettava_toimittaja[$formin_pointteri]' value='{$tuoterivi["toimittajan_tunnus"]}'>";
					echo "</td>";
					$formin_pointteri++;
				}
				echo "</tr>";
			}

			echo "</table>";

			if ($ostoehdotus_riveja > 0) {
				echo "<br>";
				echo "<input type='hidden' name='tee' value='TEE_OSTOTILAUKSET' />";
				echo "<input type='submit' name='muodosta_ostotilaukset' value='".t('Muodosta ostotilaukset')."' />";
				echo "<br><br>";
			}

			echo "</form>";
			$tee = "";
		}

		if ($ostoehdotus_riveja == 0) {
			echo "<br><br>";
			echo "<font class='error'>Antamallasi rajauksella ei lˆydy yht‰‰n tuotetta ehdotukseen.</font><br>";
			echo "<br>";
			$tee = "";
		}
	}

	// N‰ytet‰‰n k‰yttˆliittym‰
	if (!isset($tee) or $tee == "") {

		echo "<form action='$PHP_SELF' method='post' autocomplete='off'>";
		echo "<input type='hidden' name='tee' value='RAPORTOI'>";

		echo "<table>";
		echo "<tr>";
		echo "<th>".t("Osasto")."</th>";
		echo "<td>";
		echo "<select name='osasto'>";
		echo "<option value=''>".t("N‰yt‰ kaikki")."</option>";

		// tehd‰‰n avainsana query
		$sresult = t_avainsana("OSASTO", "", "");

		while ($srow = mysql_fetch_array($sresult)) {
			$sel = '';
			if ($osasto == $srow["selite"]) {
				$sel = "selected";
			}
			echo "<option value='$srow[selite]' $sel>$srow[selite] - $srow[selitetark]</option>";
		}
		echo "</select>";
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<th>".t("Tuoteryhm‰")."</th>";
		echo "<td>";
		echo "<select name='tuoryh'>";
		echo "<option value=''>".t("N‰yt‰ kaikki")."</option>";

		// tehd‰‰n avainsana query
		$sresult = t_avainsana("TRY", "", "");

		while ($srow = mysql_fetch_array($sresult)) {
			$sel = '';
			if ($tuoryh == $srow["selite"]) {
				$sel = "selected";
			}
			echo "<option value='$srow[selite]' $sel>$srow[selite] - $srow[selitetark]</option>";
		}
		echo "</select>";

		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<th>".t("Tuotemerkki")."</th>";
		echo "<td>";

		//Tehd‰‰n osasto & tuoteryhm‰ pop-upit
		$query = "	SELECT distinct tuotemerkki
					FROM tuote
					WHERE yhtio = '$kukarow[yhtio]'
					AND tuotemerkki != ''
					ORDER BY tuotemerkki";
		$sresult = pupe_query($query);

		echo "<select name='tuotemerkki'>";
		echo "<option value=''>".t("N‰yt‰ kaikki")."</option>";

		while ($srow = mysql_fetch_array($sresult)) {
			$sel = '';
			if ($tuotemerkki == $srow["tuotemerkki"]) {
				$sel = "selected";
			}
			echo "<option value='$srow[tuotemerkki]' $sel>$srow[tuotemerkki]</option>";
		}
		echo "</select>";

		echo "</td>";
		echo "</tr>";

		// katotaan onko abc aputaulu rakennettu
		$query  = "SELECT count(*) from abc_aputaulu where yhtio = '$kukarow[yhtio]' and tyyppi in ('TK','TR','TP')";
		$abcres = pupe_query($query);
		$abcrow = mysql_fetch_array($abcres);

		// jos on niin n‰ytet‰‰n t‰ll‰nen vaihtoehto
		if ($abcrow[0] > 0) {
			echo "<tr><th>".t("ABC-luokkarajaus/rajausperuste")."</th><td>";

			$sel = array_fill(0, 9, "");
			$abcrajaus = (isset($abcrajaus)) ? (int) $abcrajaus : 0;

			echo "<select name='abcrajaus'>
			<option value=''>".t("Ei rajausta")."</option>
			<option $sel[0] value='0'>".t("Luokka A-30")."</option>
			<option $sel[1] value='1'>".t("Luokka B-20 ja paremmat")."</option>
			<option $sel[2] value='2'>".t("Luokka C-15 ja paremmat")."</option>
			<option $sel[3] value='3'>".t("Luokka D-15 ja paremmat")."</option>
			<option $sel[4] value='4'>".t("Luokka E-10 ja paremmat")."</option>
			<option $sel[5] value='5'>".t("Luokka F-05 ja paremmat")."</option>
			<option $sel[6] value='6'>".t("Luokka G-03 ja paremmat")."</option>
			<option $sel[7] value='7'>".t("Luokka H-02 ja paremmat")."</option>
			<option $sel[8] value='8'>".t("Luokka I-00 ja paremmat")."</option>
			</select>";

			$sel = array("TK" => "", "TR" => "", "TP" => "");
			$abcrajaustapa = (isset($abcrajaustapa)) ? $abcrajaustapa : "TK";
			$sel[$abcrajaustapa] = "SELECTED";

			echo "<select name='abcrajaustapa'>
			<option $sel[TK] value='TK'>".t("Myyntikate")."</option>
			<option $sel[TR] value='TR'>".t("Myyntirivit")."</option>
			<option $sel[TP] value='TP'>".t("Myyntikappaleet")."</option>
			</select>
			</td></tr>";
		}

		echo "<tr><th>".t("Toimittaja")."</th><td>";
		if ($toimittajaid == "") {
			echo "<input type='text' size='20' name='ytunnus' value='$ytunnus'>";
		}
		else {
			$query = "	SELECT *
						from toimi
						where yhtio = '{$kukarow["yhtio"]}'
						and tunnus = '$toimittajaid'";
			$result = mysql_query($query) or pupe_error($query);
			$toimittaja = mysql_fetch_assoc($result);

			echo "$toimittaja[nimi] $toimittaja[nimitark]";
			echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
		}
		echo "</td></tr>";

		echo "<tr>";
		echo "<th>".t("Alkup‰iv‰m‰‰r‰ (kk-vvvv)")."</th>";
		echo "<td>";
		echo "<input type='text' name='kka1' value='$kka1' size='5'>";
		echo "<input type='text' name='vva1' value='$vva1' size='5'>";
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<th>".t("Loppup‰iv‰m‰‰r‰ (kk-vvvv)")."</th>";
		echo "<td>";
		echo "<input type='text' name='kkl1' value='$kkl1' size='5'>";
		echo "<input type='text' name='vvl1' value='$vvl1' size='5'>";
		echo "</td>";
		echo "</tr>";

		echo "</table>";

		echo "<br><input type='submit' name='ehdotusnappi' value = '".t("Aja ostoehdotus")."'></form>";
	}

	require ("inc/footer.inc");

?>