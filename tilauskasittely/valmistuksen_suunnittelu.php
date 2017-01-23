<?php

	require("../inc/parametrit.inc");

	echo "<font class='head'>".t("Valmistusten suunnittelu")."</font><hr>";

	// Ehdotetaan oletuksena ehdotusta ensikuun valmistuksille sek‰ siit‰ plus 3 kk
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

	// T‰m‰ palauttaa yhden tuotteen valmistuksen tiedot 
	function teerivi( $tuoteno, $valittu_toimittaja ){

		// Kukarow k‰yttˆˆn ja p‰iv‰m‰‰r‰t globaaleina
		global $kukarow, $edellinen_alku, $edellinen_loppu, $nykyinen_alku, $nykyinen_loppu;
		
		// Tehd‰‰n kaudet p‰iv‰m‰‰rist‰
		$alku_kausi = substr(str_replace("-", "", $nykyinen_alku), 0, 6);
		$loppu_kausi = substr(str_replace("-", "", $nykyinen_loppu), 0, 6);
		
		// Haetaan samankaltaiset is‰tuotteet
		$query = "	SELECT isatuoteno
					FROM tuoteperhe
					WHERE yhtio = '{$kukarow["yhtio"]}'
					AND tuoteno = '{$tuoteno}'
					AND tyyppi = 'S'";
		$isatuote_result = pupe_query($query);
		
		// Loopataan is‰tuotteet l‰pi ja haetaan sisaret
		$sisaret = null;
		while($isatuote_row = mysql_fetch_assoc($isatuote_result)){
			// Haetaan samankaltaiset is‰tuotteet
			$query = "	SELECT group_concat(tuoteno separator ', ') sisaret
						FROM tuoteperhe
						WHERE yhtio = '{$kukarow["yhtio"]}'
						AND isatuoteno = '{$isatuote_row["isatuoteno"]}'
						AND tyyppi = 'S'";
			$sisar_result = pupe_query($query);
			$sisar_row = mysql_fetch_assoc($sisar_result);
			
			$sisaret .= $sisar_row['sisaret'];
		}
		
		// Haetaan tuotteen varastosaldo
		$query = "	SELECT ifnull(sum(saldo),0) saldo 
					FROM tuotepaikat
					WHERE tuotepaikat.yhtio = '{$kukarow["yhtio"]}'
					AND tuotepaikat.tuoteno = '$tuoteno'";
		$result = pupe_query($query);
		$row = $row = mysql_fetch_assoc($result);
		$varastosaldo = $row['saldo'];
		
		// Haetaan tuotteen vuosikulutus
		$query = "	SELECT sum(tilausrivi.kpl) vuosikulutus
					FROM tilausrivi
					WHERE tilausrivi.yhtio = '{$kukarow["yhtio"]}'
					AND tilausrivi.tyyppi = 'L'
					AND tilausrivi.tuoteno = '$tuoteno'
					AND tilausrivi.toimitettuaika >= DATE_SUB(now(), INTERVAL 1 YEAR)";
		$result = pupe_query($query);
		$row = mysql_fetch_assoc($result);
		$vuosikulutus = $row['vuosikulutus'];
		
		// Haetaan tuotteen tilauksessa oleva m‰‰r‰
		$query = "	SELECT sum(tilausrivi.varattu) tilattu
					FROM tilausrivi
					WHERE tilausrivi.yhtio = '{$kukarow["yhtio"]}'
					AND tilausrivi.tyyppi = 'O'
					AND tilausrivi.tuoteno = '$tuoteno'
					AND tilausrivi.varattu != 0";
		$result = pupe_query($query);
		$row = mysql_fetch_assoc($result);
		$tilattu = $row['tilattu'];
		
		// Haetaan tuotteen varattu m‰‰r‰
		$query = "	SELECT sum(tilausrivi.varattu) varattu
					FROM tilausrivi
					WHERE tilausrivi.yhtio = '{$kukarow["yhtio"]}'
					AND tilausrivi.tyyppi = 'V'
					AND tilausrivi.tuoteno = '$tuoteno'
					AND tilausrivi.varattu != 0";
		$result = pupe_query($query);
		$row = mysql_fetch_assoc($result);
		$varattu = $row['varattu'];
		
		// Haetaan valmistettavana oleva m‰‰r‰
		$query = " 	SELECT sum(tilausrivi.varattu) valmistuksessa
					FROM tilausrivi
					WHERE tilausrivi.yhtio = '{$kukarow["yhtio"]}'
					AND tilausrivi.tyyppi in ('V','W')
					AND tilausrivi.tuoteno = '$tuoteno'
					AND tilausrivi.varattu != 0
					AND tilausrivi.kerayspvm >= '$nykyinen_alku'
					AND tilausrivi.kerayspvm <= '$nykyinen_loppu'"; //TODO: katsotaanko p‰iv‰m‰‰r‰ehto oikeasta p‰iv‰m‰‰r‰st‰
		$result = pupe_query($query);
		$row = mysql_fetch_assoc($result);
		$valmistuksessa = ($row['valmistuksessa']>0 ? $row['valmistuksessa'] : 0);
		
		// Jos ollaan rajattu toimittaja k‰yttˆliittym‰ss‰, haetaan sen tiedot eik‰ oletustoimittajaa
		$toimittaja_rajaus = ($valittu_toimittaja > 0) ? "and toimi.tunnus = '$valittu_toimittaja'" : "";
		
		// Haetaan tuotteen toimittajatiedot
		$query = "	SELECT if(tuotteen_toimittajat.toimitusaika > 0, tuotteen_toimittajat.toimitusaika, 0) toimitusaika,
					if(tuotteen_toimittajat.pakkauskoko > 0, tuotteen_toimittajat.pakkauskoko, 1) pakkauskoko,
					tuotteen_toimittajat.toimittaja,
					toimi.nimi,
					toimi.tunnus
					FROM tuotteen_toimittajat
					JOIN toimi ON (toimi.yhtio = tuotteen_toimittajat.yhtio AND toimi.tunnus = tuotteen_toimittajat.liitostunnus $toimittaja_rajaus)
					WHERE tuotteen_toimittajat.yhtio = '{$kukarow["yhtio"]}'
					AND tuotteen_toimittajat.tuoteno = '$tuoteno'
					$toimittaja_rajaus
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
		
		// Haetaan budjetoitu myynti
		$query = "	SELECT ifnull(sum(maara), 0) maara
					FROM budjetti_tuote
					WHERE budjetti_tuote.yhtio = '{$kukarow["yhtio"]}'
					AND budjetti_tuote.kausi >= '$alku_kausi'
					AND budjetti_tuote.kausi <= '$loppu_kausi'
					AND budjetti_tuote.tuoteno = '$tuoteno'";
		$result = pupe_query($query);
		$row = mysql_fetch_assoc($result);
		$budjetoitu_myynti = $row['maara'];

		// Jos ei ole budjettia, otetaan edellisen kauden myynti ja k‰ytet‰‰n sit‰
		if ($budjetoitu_myynti == 0) {
			$query = "	SELECT ifnull(sum(tilausrivi.kpl), 0) myynti_ed
						FROM tilausrivi
						WHERE tilausrivi.yhtio = '{$kukarow["yhtio"]}'
						AND tilausrivi.tyyppi = 'L'
						AND tilausrivi.tuoteno = '$tuoteno'
						AND tilausrivi.laskutettuaika >= '$edellinen_alku'
						AND tilausrivi.laskutettuaika <= '$edellinen_loppu'";
			$result = pupe_query($query);
			$row = mysql_fetch_assoc($result);
			$budjetoitu_myynti = $row["myynti_ed"];
		}
		
		// Lasketaan valmistussuositus
		$reaalisaldo = $varastosaldo - $tilattu - $varattu;
		$myyntiennuste = $budjetoitu_myynti - $reaalisaldo;
		$myyntiennuste = ($myyntiennuste < 0) ? 0 : $myyntiennuste;
		$toimitusaika = $toimittajarow['toimitusaika'];
		$paivakulutus = round($vuosikulutus / 360);
		$riittopv = ($paivakulutus == 0) ? t("Ei tiedossa") : floor($reaalisaldo / $paivakulutus);
		$kulutusennuste = $myyntiennuste - $reaalisaldo;
		$maaraennuste = ($paivakulutus * $toimitusaika) + $kulutusennuste;
		$valmistussuositus = ceil($maaraennuste - $reaalisaldo);
		$valmistusmaara = ceil($valmistussuositus / $toimittajarow['pakkauskoko']) * $toimittajarow['pakkauskoko'];
		
		// Palautettava array
		$tuoterivi = array();
		$tuoterivi['sisartuote'] 			= $sisaret;
		$tuoterivi['valmistuksessa'] 		= $valmistuksessa;
		$tuoterivi['riittopv'] 				= $riittopv;
		$tuoterivi['valmistustarve'] 		= $valmistusmaara;
		$tuoterivi['toimittajan_tunnus'] 	= $toimittajarow['tunnus'];

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
	
	if (isset($tee) and $tee == "TEE_VALMISTUKSET") {

		$rows = 0;
		$edellinen_toimittaja = false;
		
		foreach ($valmistettava_maara as $index => $maara) {

			$maara = (float) $maara;
			$toimittaja = mysql_real_escape_string($valmistettava_toimittaja[$index]);
			$tuoteno = mysql_real_escape_string($valmistettava_tuoteno[$index]);
		echo "$maara,$toimittaja,$tuoteno<br/>"; continue;
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
								tila                = 'L',
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
							tyyppi    = 'W',
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
		
		echo $rows." ".t('valmistusta muodostettu.');
		echo "<br><br>";
		$tee = "";
	}
	
	// Tehd‰‰n raportti
	if (isset($tee) and $tee == "RAPORTOI") {

		$tuote_where       = ""; // tuote-rajauksia
		$toimittaja_join   = ""; // toimittaja-rajauksia
		$toimittaja_select = ""; // toimittaja-rajauksia
		$abc_join          = ""; // abc-rajauksia
		$abc_select		   = ""; // abc-rajauksia

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
			$abc_select = "abc_aputaulu.luokka,";
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
			$vtresult = mysql_query($query) or pupe_error($query);
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
		
		// Haetaan tehdyt valmistukset
		$query = "	
			SELECT 
			lasku.kohde valmistuslinja
			, tilausrivi.tuoteno
			, tilausrivi.osasto
			, tilausrivi.try
			, tilausrivi.kpl+tilausrivi.tilkpl maara
			, DATE_FORMAT( lasku.luontiaika, GET_FORMAT(DATE, 'EUR')) pvm
			, lasku.alatila tila
			FROM tilausrivi
			JOIN tuote ON (tilausrivi.tuoteno = tuote.tuoteno AND tilausrivi.yhtio = tuote.yhtio)
			$abc_join
			JOIN lasku use index (PRIMARY) ON (lasku.yhtio=tilausrivi.yhtio AND lasku.tunnus=tilausrivi.otunnus AND lasku.tila != 'D')
			WHERE lasku.yhtio = '{$kukarow["yhtio"]}'
			AND tilausrivi.tyyppi = 'W'
			AND lasku.luontiaika >= '$nykyinen_alku 00:00:00'
			AND lasku.luontiaika < '$nykyinen_loppu 00:00:00'
			$tuote_where
			ORDER BY lasku.kohde DESC, lasku.luontiaika ASC"; 
		$res = mysql_query($query) or pupe_error($query);
		
		if(mysql_num_rows($res) > 0){
			// N‰ytet‰‰n tehdyt ja suunnitellut valmistukset
			$EDlinja = false;
		
			echo t("N‰ytet‰‰n tehdyt ja suunnitellut valmistukset aikav‰lille").": $nykyinen_alku - $nykyinen_loppu <br>\n";
			echo t("Valmistuksia")." ".mysql_num_rows($res)." ".t("kpl").".<br>\n";
		
			echo "<table>";
			while($row = mysql_fetch_assoc($res)){
				// Valmistuslinja vaihtuu
				if ($row['valmistuslinja'] != $EDlinja or $EDlinja === false) {
					$valmistuslinja = ( !empty($row["valmistuslinja"]) ? $row["valmistuslinja"] : t("Ei valmistuslinjaa") );
		
					echo "<tr>";
					echo "<td class='back' colspan='8'><font class='head'><br>$valmistuslinja</font></td>";
					echo "</tr>";
					
					echo "<tr>";
					echo "<th>".t("Tuotenumero")."</th>";
					echo "<th>".t("Osasto")."</th>";
					echo "<th>".t("Tuoteryhm‰")."</th>";
					echo "<th>".t("Valmistuksen m‰‰r‰")."</th>";
					echo "<th>".t("Valmistuksen pvm")."</th>";
					echo "<th>".t("Valmistuksen tila")."</th>";
					echo "</tr>";
				}
				$EDlinja = $row['valmistuslinja'];
			
				echo "<tr>";
				echo "<td>{$row["tuoteno"]}</td>";
				echo "<td>{$row["osasto"]}</td>";
				echo "<td>{$row["try"]}</td>";
				echo "<td>{$row["maara"]}</td>";
				echo "<td>{$row["pvm"]}</td>";
				echo "<td>{$row["tila"]}</td>"; // TODO: t‰h‰n pit‰isi saada jotain selkokielist‰
				echo "</tr>";
			}
			echo "</table>"; 
		}
		else echo t("Annetuilla rajauksilla ei lˆytynyt tehtyj‰ tai suunniteltuja valmistuksia.");
		
		// Haetaan valmisteet, jotka osuvat hakuehtoihin
		$query = "	SELECT tuoteperhe.isatuoteno tuoteno,
					tuote.nimitys,
					tuote.kohde valmistuslinja,
					$abc_select
					$toimittaja_select
					FROM tuote
					JOIN tuoteperhe ON (tuote.tuoteno = tuoteperhe.isatuoteno AND tuote.yhtio = tuoteperhe.yhtio AND tuoteperhe.tyyppi = 'R')
					$toimittaja_join
					$abc_join
					WHERE tuote.yhtio = '{$kukarow["yhtio"]}'
					AND tuote.ei_saldoa = ''
					$tuote_where
					GROUP BY tuoteperhe.isatuoteno
					ORDER BY tuoteperhe.isatuoteno, tuote.try, tuote.tuoteno";
		$res = mysql_query($query) or pupe_error($query);
		
		echo "<br/><br/><font class='head'>".t("Ehdotetut valmistukset")."</font><br/>";
		
		$valmistus_riveja = 0;
		
		if (mysql_num_rows($res) > 0) {
			
				// Kootaan raportti
				echo "<form action='$PHP_SELF' method='post'>";
				echo "<table>";

				$EDlinja = false;
				$valmistaja_header_piirretty = false;
				$formin_pointteri = 0;


			// loopataan tuotteet l‰pi
			while ($row = mysql_fetch_assoc($res)) {
				// Haetaan tuotteen tiedot
				$tuoterivi = teerivi($row["tuoteno"], $toimittajaid);
				$abcluokka = ( isset($row["luokka"]) ? $row["luokka"] : false );

				// Valmistuslinja vaihtuu
				if ($row['valmistuslinja'] != $EDlinja or $EDlinja === false) {
					$valmistaja_header = "<tr>";
					$valmistaja_header .= "<td class='back' colspan='8'><font class='head'><br>".t("Valmistuslinja")." {$row["valmistuslinja"]}</font></td>";
					$valmistaja_header .= "</tr>";
					$valmistaja_header .= "<tr>";
					$valmistaja_header .= "<th>".t("Tuotenumero")."</th>";
					$valmistaja_header .= "<th>".t("Nimitys")."</th>";
					$valmistaja_header .= "<th>".t("Sisartuotteet")."</th>";
					$valmistaja_header .= "<th>".t("ABC-luokka")."</th>";
					$valmistaja_header .= "<th>".t("Valmistuksessa")."</th>";
					$valmistaja_header .= "<th>".t("Riitto Pv")."</th>";
					$valmistaja_header .= "<th>".t("Tuotantolinja")."</th>";
					$valmistaja_header .= "<th>".t("Valmistustarve")."</th>";
					$valmistaja_header .= "</tr>";
					$valmistaja_header_piirretty = false;
				}
				
				$EDlinja = $row['valmistuslinja'];
				
				// Jos tuotetta ei tarvitse valmistaa, ei n‰ytet‰ sit‰ ostoehdotuksessa
				if ($tuoterivi['valmistustarve'] <= 0) {
					continue;
				}
				
				$valmistus_riveja++;
				
				// Pit‰‰ s‰ilytt‰‰ table-headeria muuttujassa, sill‰ voi olla ett‰ valmistuslinjalle ei tule yht‰‰n tuoterivi‰ ehdotukseen (eik‰ haluta piirt‰‰ turhaa headeri‰)
				if ($valmistaja_header_piirretty == false) {
					echo $valmistaja_header;
					$valmistaja_header_piirretty = true;
				}
				
				echo "<tr>";
				echo "<td>{$row["tuoteno"]}</td>";
				echo "<td>{$row["nimitys"]}</td>";
				echo "<td>{$tuoterivi["sisartuote"]}</td>";
				echo "<td>$abcluokka</td>";
				echo "<td style='text-align: right;'>{$tuoterivi["valmistuksessa"]}</td>";
				echo "<td style='text-align: right;'>{$tuoterivi["riittopv"]}</td>";
				echo "<td>{$row["valmistuslinja"]}</td>"; // TODO: meinataanko tuotantolinjalla samaa kuin valmistuslinjalla?
				
				echo "<td style='text-align: right;'>";
				echo "<input size='8' style='text-align: right;' type='text' name='valmistettava_maara[$formin_pointteri]' value='{$tuoterivi["valmistustarve"]}'>";
				echo "<input type='hidden' name='valmistettava_tuoteno[$formin_pointteri]' value='{$row["tuoteno"]}'>";
				echo "<input type='hidden' name='valmistettava_toimittaja[$formin_pointteri]' value='{$tuoterivi["toimittajan_tunnus"]}'>";
				echo "</td>";
				$formin_pointteri++;
				echo "</tr>";
			}
			
			echo "</table>";
			
			echo t("Valmistettavia tuotenimikkeit‰")." $formin_pointteri ".t("kpl").".<br>\n";

			if ($valmistus_riveja > 0) {
				echo "<br>";
				echo "<input type='hidden' name='tee' value='TEE_VALMISTUKSET' />";
				echo "<input type='submit' name='muodosta_valmistukset' value='".t('Muodosta valmistukset')."' />";
				echo "<br><br>";
			}

			echo "</form>";
			$tee = "";
		}
		
		if ($valmistus_riveja == 0) {
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
		$sresult = mysql_query($query) or pupe_error($query);

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
		$abcres = mysql_query($query) or pupe_error($query);
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

		echo "<br><input type='submit' name='ehdotusnappi' value = '".t("Suunnittele valmistus")."'></form>";
	}

	require ("inc/footer.inc");

?>