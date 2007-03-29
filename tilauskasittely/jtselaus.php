<?php

	if (strpos($_SERVER['SCRIPT_NAME'], "jtselaus.php")  !== FALSE) {
		if (file_exists("../inc/parametrit.inc")) {
			require ("../inc/parametrit.inc");
		}
		else {
			require ("parametrit.inc");
		}
	}

	if ($tee != "JT_TILAUKSELLE") {		
		if ($toim == "ENNAKKO") {
			echo "<font class='head'>".t("Ennakkotilausrivit")."</font><hr>";
		}
		else {
			echo "<font class='head'>".t("JT rivit")."</font><hr>";	
		}

		if ($vainvarastosta != "") {
			$varastosta = array();
			$varastosta[$vainvarastosta] = $vainvarastosta;
		}
	}
	
	$asiakasmaa = "";
	
	//Extranet käyttäjille pakotetaan aina tiettyjä arvoja
	if ($kukarow["extranet"] != "") {
		$query  = "	SELECT *
					FROM asiakas
					WHERE yhtio='$kukarow[yhtio]' and tunnus='$kukarow[oletus_asiakas]'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 1) {
			$asiakas = mysql_fetch_array($result);

			$toimittaja		= "";
			$toimittajaid	= "";

			$asiakasno 		= $asiakas["ytunnus"];
			$asiakasid		= $asiakas["tunnus"];
			if ($asiakas["toim_nimi"] == "") {
				$asiakasmaa = $asiakas["maa"];
			}
			else {
				$asiakasmaa = $asiakas["toim_maa"];				
			}

			$tyyppi 		= "T";
			$tuotenumero	= "";
			$toimi			= "";
			$superit		= "";
			$tilaus_on_jo	= "KYLLA";

			if ($tee == "") {
				$tee = "JATKA";
			}
		}
	}
	elseif ($tilaus_on_jo != '') {
		$query  = "	SELECT *
					FROM lasku
					WHERE yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 1) {
			$asiakas = mysql_fetch_array($result);
			$asiakasmaa = $asiakas["toim_maa"];
		}
	}

	//JT-rivit on poimittu
	if ($tee == 'POIMI' or $tee == "JT_TILAUKSELLE") {
		foreach($jt_rivitunnus as $tunnukset) {
			
			$tunnusarray = explode(',', $tunnukset);
			
			if ($kpl[$tunnukset] > 0 or $loput[$tunnukset] != '' or $suoratoimpaikka[$tunnukset] != "") {
				
				// Tutkitaan hintoja ja alennuksia
				if ($tee == "JT_TILAUKSELLE" and $tila == "jttilaukseen" and $toim != "ENNAKKO" and $toim != 'SIIRTOLISTA') {
					$mista = 'jtrivit_tilaukselle.inc';
					require("laskealetuudestaan.inc");
				}
				
				require ('tee_jt_tilaus.inc');
			}
		}
		
		$tee = "JATKA";
	}

	if ($kukarow["extranet"] == "" and $tilaus_on_jo == "" and $tee == 'TOIMITA') {
		if ($toim == "ENNAKKO") {
			$query  = "	select * 
						from lasku 
						where yhtio='$kukarow[yhtio]' 
						and laatija='$kukarow[kuka]' 
						and alatila='E' and tila='N'";
		}
		else {
			$query  = "	select * 
						from lasku 
						where yhtio='$kukarow[yhtio]' 
						and laatija='$kukarow[kuka]' 
						and ((alatila = 'J' and tila = 'N') or (alatila = 'P' and tila = 'G'))";
		}
		$jtrest = mysql_query($query) or pupe_error($query);

		while ($laskurow = mysql_fetch_array($jtrest)) {
			$query  = "UPDATE lasku SET alatila='A' WHERE yhtio='$kukarow[yhtio]' and tunnus='$laskurow[tunnus]'";
			$apure  = mysql_query($query) or pupe_error($query);

			if ($toim != "ENNAKKO") {
				//mennään aina tänne ja sit tuolla inkissä katotaan aiheuttaako toimenpiteitä.
				$mista = 'jtselaus';
				require("laskealetuudestaan.inc");
			}

			if ($laskurow["tila"] == "N" and $automaaginen == "") {
				// katsotaan ollaanko tehty JT-supereita..
				require("jt_super.inc");
				$jt_super = jt_super($laskurow["tunnus"]);

				echo "$jt_super<br><br>";

				//Pyydetään tilaus-valmista olla echomatta mitään
				$silent = "SILENT";
			}


			// tarvitaan $kukarow[yhtio], $kukarow[kesken], $laskurow ja $yhtiorow
			$kukarow["kesken"] = $laskurow["tunnus"];
			
			if ($laskurow['tila']== 'G') {
				$vanhatoim = $toim;
				$toim = "SIIRTOLISTA";

				require("tilaus-valmis-siirtolista.inc");

				$toim = $vanhatoim;
				$vanhatoim = "";
			}
			else {
				require("tilaus-valmis.inc");
			}
		}
		$tee = '';
	}

	//Tutkitaan onko käyttäjällä keskenolevia jt-rivejä
	if ($kukarow["extranet"] == "" and $tilaus_on_jo == "") {
		
		if ($toim == "ENNAKKO") {
			$query = "	SELECT *
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]' and laatija='$kukarow[kuka]' and alatila='E' and tila = 'N'";
		}
		else {
			$query = "	SELECT *
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]' and laatija='$kukarow[kuka]' and ((alatila = 'J' and tila = 'N') or (alatila = 'P' and tila = 'G'))";
		}
		$stresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($stresult) > 0) {
			echo "	<form name='valinta' action='$PHP_SELF' method='post'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='tee' value='TOIMITA'>";
					
			if ($toim == "ENNAKKO") {
				echo "	<font class='error'>".t("HUOM! Sinulla on toimittamattomia ennakkorivejä")."</font><br>
						<table>
						<tr>
						<td>".t("Toimita poimitut ennakko-rivit").": </td>";
			}
			else {
				echo "	<font class='error'>".t("HUOM! Sinulla on toimittamattomia jt-rivejä")."</font><br>
						<table>
						<tr>
						<td>".t("Laske alennukset uudelleen")."</td>
						<td><input type='checkbox' name='laskeuusix'></td></tr><tr>
						<td>".t("Toimita poimitut JT-rivit")."</td>";
			}		
					
			echo "	<td><input type='submit' value='".t("Toimita")."'></td>
					</tr>
					</table>
					</form><hr>";
		}
	}

	//muokataan tilausriviä
	if ($kukarow["extranet"] == "" and $tilaus_on_jo == "" and $tee == 'MUOKKAARIVI') {
		$query = "	SELECT *
					FROM tilausrivi
					WHERE tunnus = '$jt_rivitunnus' and yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			echo t("Tilausriviä ei löydy")."! $query";
			exit;
		}
		$trow = mysql_fetch_array($result);

		$query = "	DELETE from tilausrivi
					WHERE tunnus = '$jt_rivitunnus' and yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		$tuoteno 		= $trow["tuoteno"];
		$hinta 			= $trow["hinta"];
		
		if ($toim == "ENNAKKO") {
			$kpl 		= $trow["varattu"];
		}
		else {
			$kpl 		= $trow["jt"];
		}
		
		$ale 			= $trow["ale"];
		$toimaika 		= $trow["toimaika"];
		$kerayspvm		= $trow["kerayspvm"];
		$alv 			= $trow["alv"];
		$var	 		= $trow["var"];
		$netto			= $trow["netto"];
		$perheid		= $trow["perheid"];
		$kommentti 		= $trow["kommentti"];
		$rivinotunnus	= $trow["otunnus"];

		echo t("Muuta riviä").":<br>";

		echo "<form action='$PHP_SELF' method='post'>";
		echo "<input type='hidden' name='toim' value='$toim'>";
		echo "<input type='hidden' name='tee' value='LISAARIVI'>";
		echo "<input type='hidden' name='tyyppi' value='$tyyppi'>";
		echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
		echo "<input type='hidden' name='asiakasid' value='$asiakasid'>";
		echo "<input type='hidden' name='asiakasno' value='$asiakasno'>";
		echo "<input type='hidden' name='toimittaja' value='$toimittaja'>";
		echo "<input type='hidden' name='toimi' value='$toimi'>";
		echo "<input type='hidden' name='superit' value='$superit'>";
		echo "<input type='hidden' name='suorana' value='$suorana'>";
		echo "<input type='hidden' name='tuotenumero' value='$tuotenumero'>";
		echo "<input type='hidden' name='rivinotunnus' value='$rivinotunnus'>";

		if(is_array($varastosta)) {
			foreach ($varastosta as $vara) {
				$tilausnumero .= $vara."##";
			}
		}
		echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";

		$aputoim = "RIVISYOTTO";

		require('syotarivi.inc');
		exit;
	}

	//Lisätään muokaattu tilausrivi
	if ($kukarow["extranet"] == "" and $tilaus_on_jo == "" and $tee == 'LISAARIVI') {

		if ($kpl > 0) {
			$query = "	SELECT *
						FROM tuote
						WHERE tuoteno='$tuoteno' and yhtio='$kukarow[yhtio]'";
			$tuoteresult = mysql_query($query) or pupe_error($query);

			if(mysql_num_rows($tuoteresult) == 1) {

				$trow = mysql_fetch_array($tuoteresult);

				$query = "	SELECT *
							FROM lasku
							WHERE yhtio='$kukarow[yhtio]' and tunnus='$rivinotunnus'";
				$laskures = mysql_query($query) or pupe_error($query);
				$laskurow = mysql_fetch_array($laskures);

				$varataan_saldoa 			= "EI";
				$kukarow["kesken"] 			= $rivinotunnus;

				require ('lisaarivi.inc');
			}
			else {
				$varaosavirhe = t("VIRHE: Tuotetta ei löydy")."!<br>";
			}
		}

		$varastot = explode('##', $tilausnumero);

		foreach ($varastot as $vara) {
			$varastosta[$vara] = $vara;
		}
		$tee = "JATKA";
	}

	if ($kukarow["extranet"] == "" and $tilaus_on_jo == "" and ($tee == "" or $tee == "JATKA")) {

		if (isset($muutparametrit)) {
			list($tuotenumero,$tyyppi,$toimi,$superit,$automaaginen,$ytunnus,$asiakasno,$toimittaja,$suorana,$tuoteosasto,$tuoteryhma,$tuotemerkki) = explode('#', $muutparametrit);

			$varastot = explode('##', $tilausnumero);

			foreach ($varastot as $vara) {
				$varastosta[$vara] = $vara;
			}
		}

		$muutparametrit = "$tuotenumero#$tyyppi#$toimi#$superit#$automaaginen#$ytunnus#$asiakasno#$toimittaja#$suorana#$tuoteosasto#$tuoteryhma#$tuotemerkki#";

		if(is_array($varastosta)) {
			foreach ($varastosta as $vara) {
				$tilausnumero .= $vara."##";
			}
		}

		if ($ytunnus != '' or is_array($varastosta)) {
			if ($ytunnus != '' and !isset($ylatila) and is_array($varastosta)) {
				require("../inc/kevyt_toimittajahaku.inc");

				if ($ytunnus != '') {
					$toimittaja = $ytunnus;
					$tee = "JATKA";
				}
			}
			elseif($ytunnus != '' and isset($ylatila)) {
				$tee = "JATKA";
			}
			elseif(is_array($varastosta)) {
				$tee = "JATKA";
			}
			else {
				$tee = "";
			}
		}
		$muutparametrit = "$tuotenumero#$tyyppi#$toimi#$superit#$automaaginen#$ytunnus#$asiakasno#$toimittaja#$suorana#$tuoteosasto#$tuoteryhma#$tuotemerkki#";

		if(is_array($varastosta)) {
			foreach ($varastosta as $vara) {
				$tilausnumero .= $vara."##";
			}
		}

		if ($asiakasno != '' and $tee == "JATKA") {
			$muutparametrit .= $ytunnus;

			if ($asiakasid == "") {
				$ytunnus = $asiakasno;
			}

			require("../inc/asiakashaku.inc");

			if ($ytunnus != '') {
				$tee = "JATKA";
				$asiakasno = $ytunnus;
				$ytunnus = $toimittaja;
			}
			else {
				$asiakasno = $ytunnus;
				$ytunnus = $toimittaja;

				$tee = "";
			}
		}
	}
	
	if ($tee == "JATKA") {

		$aslisa   = '';
		$tolisa1  = '';
		$tolisa2  = '';
		$tuotlisa = '';
		$siirtolisa = '';
		$tuotelisa = '';

		if ($toimittaja != '') {
			$tolisa1 = " LEFT JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno ";
			$tolisa2 = " and tuotteen_toimittajat.liitostunnus = '$toimittajaid' ";
		}

		if ($tilaus_on_jo == "KYLLA" and $asiakasid != '') {
			$aslisa = " and lasku.liitostunnus='$asiakasid' ";
		}
		elseif ($tilaus_on_jo == "" and $asiakasno != '') {
			$aslisa = " and lasku.ytunnus='$asiakasno' ";
		}

		if ($tuotenumero != '') {
			$tuotlisa = " and tilausrivi.tuoteno='$tuotenumero' ";
		}

		if ($tilaus_on_jo == "KYLLA" and $toim == 'SIIRTOLISTA' and $laskurow['clearing'] != '') {
		 	$siirtolisa = " and lasku.clearing = '$laskurow[clearing]' ";
		}

		if ($tuoteryhma != '') {
			$tuotelisa .= " and tuote.try = '$tuoteryhma' ";
		}

		if ($tuoteosasto != '') {
			$tuotelisa .= " and tuote.osasto = '$tuoteosasto' ";
		}

		if ($tuotemerkki != '') {
			$tuotelisa .= " and tuote.tuotemerkki = '$tuotemerkki' ";
		}

		$query = "";

		if ($automaaginen == '') {
			$limit = " LIMIT 1000 ";
		}
		else {
			$limit = "";
		}

		if ($tyyppi == 'A') {
			$order = " ORDER BY lasku.ytunnus, tuote.tuoteno ";
		}

		if ($tyyppi == 'T') {
			$order = " ORDER BY tuote.tuoteno, lasku.ytunnus ";
		}

		if ($tyyppi == 'P') {
			$order = " ORDER BY lasku.luontiaika, tuote.tuoteno, lasku.ytunnus ";
		}

		if (($tyyppi == 'A') or ($tyyppi == 'T') or ($tyyppi == 'P')) {
			//haetaan vain tuoteperheiden isät tai sellaset tuotteet jotka eivät kuulu tuoteperheisiin
			if ($toim == "ENNAKKO") {
				$query = "	SELECT tilausrivi.tuoteno, tilausrivi.nimitys, tilausrivi.tilaajanrivinro, lasku.ytunnus, tilausrivi.varattu jt, lasku.nimi, lasku.toim_nimi, lasku.viesti, tilausrivi.tilkpl, tilausrivi.hinta, tilausrivi.ale,
							lasku.tunnus ltunnus, tilausrivi.tunnus tunnus, tuote.ei_saldoa, tilausrivi.perheid, tilausrivi.perheid2, tilausrivi.otunnus, lasku.clearing, lasku.varasto, tuote.yksikko
							FROM tilausrivi use index (yhtio_tyyppi_laskutettuaika) 
							JOIN lasku use index (PRIMARY) ON lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus and lasku.tila='E' and lasku.alatila='A'
							JOIN tuote use index (tuoteno_index) ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
							$tolisa1
							WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
							and tilausrivi.tyyppi = 'E'
							and tilausrivi.laskutettuaika = '0000-00-00'
							and tilausrivi.varattu > 0
							and ((tilausrivi.tunnus=tilausrivi.perheid and tilausrivi.perheid2=0) or (tilausrivi.tunnus=tilausrivi.perheid2) or (tilausrivi.perheid=0 and tilausrivi.perheid2=0))
							$tolisa2
							$aslisa
							$tuotlisa
							$order";
			}
			else {
				$query = "	SELECT tilausrivi.tuoteno, tilausrivi.nimitys, tilausrivi.tilaajanrivinro, lasku.ytunnus, tilausrivi.jt, lasku.nimi, lasku.toim_nimi, lasku.viesti, tilausrivi.tilkpl, tilausrivi.hinta, tilausrivi.ale,
							lasku.tunnus ltunnus, tilausrivi.tunnus tunnus, tuote.ei_saldoa, tilausrivi.perheid, tilausrivi.perheid2, tilausrivi.otunnus, lasku.clearing, lasku.varasto, tuote.yksikko
							FROM tilausrivi use index (yhtio_tyyppi_var_keratty_kerattyaika_uusiotunnus)
							JOIN lasku use index (PRIMARY) ON lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus and lasku.osatoimitus=''
							JOIN tuote use index (tuoteno_index) ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno $tuotelisa
							$tolisa1
							WHERE tilausrivi.yhtio 	= '$kukarow[yhtio]'
							and tilausrivi.tyyppi in ('L','G')
							and tilausrivi.var 			= 'J'
							and tilausrivi.keratty 		= ''
							and tilausrivi.uusiotunnus 	= 0
							and tilausrivi.varattu 		= 0
							and tilausrivi.kpl 			= 0
							and tilausrivi.jt 		   <> 0
							and ((tilausrivi.tunnus=tilausrivi.perheid and tilausrivi.perheid2=0) or (tilausrivi.tunnus=tilausrivi.perheid2) or (tilausrivi.perheid=0 and tilausrivi.perheid2=0))
							$tolisa2
							$aslisa
							$tuotlisa
							$siirtolisa
							$order
							$limit";
			}
			$isaresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($isaresult) > 0) {

				$jt_rivilaskuri = 1;

				while ($jtrow = mysql_fetch_array($isaresult)) {

					//tutkitaan onko tämä suoratoimitusrivi
					$onkosuper = "";

					if ($jtrow["tilaajanrivinro"] != 0) {
						$query = "	SELECT *
									FROM toimi
									WHERE yhtio = '$kukarow[yhtio]'
									and tunnus  = '$jtrow[tilaajanrivinro]'";
						$sjtres = mysql_query($query) or pupe_error($query);

						if (mysql_num_rows($sjtres) == 1) {
							$sjtrow = mysql_fetch_array($sjtres);

							// Tutkitaan onko tätä tuotetta jollain ostotilauksella auki tällä toimittajalla
							// jos on niin tiedetään, että tämä on suoratoimitusrivi
							$query = "	SELECT *
										FROM lasku use index (yhtio_liitostunnus), tilausrivi
										WHERE lasku.yhtio = '$kukarow[yhtio]'
										and lasku.liitostunnus = '$sjtrow[tunnus]'
										and lasku.tila='O'
										and lasku.yhtio=tilausrivi.yhtio
										and lasku.tunnus=tilausrivi.otunnus
										and tilausrivi.tyyppi='O'
										and tilausrivi.uusiotunnus=0
										and tilausrivi.varattu!=0
										and tilausrivi.tuoteno='$jtrow[tuoteno]'";
							$sjtres = mysql_query($query) or pupe_error($query);

							if (mysql_num_rows($sjtres) > 0) {
								$onkosuper = "ON";
							}
						}
					}

					// ei näytetä suoratoimitusrivejä, ellei $superit ole ruksattu, sillon näytetään pelkästään suoratoimitukset
					if (($onkosuper == "" and $superit == "") or ($onkosuper == "ON" and $superit != "")) {

						$kokonaismyytavissa = 0;
						
						if ($jtrow["perheid"] > 0 or $jtrow["perheid2"] > 0) {
							if ($jtrow["perheid"] == 0) {
								$pklisa = " and perheid2 = '$jtrow[perheid2]'";
							}
							else {
								$pklisa = " and (perheid = '$jtrow[perheid]' or perheid2 = '$jtrow[perheid]')";
							}
							
							unset($perherow);
							
							$query = "	SELECT vanhatunnus
										FROM lasku use index (PRIMARY)
										WHERE yhtio	= '$kukarow[yhtio]'
										and tunnus	= '$jtrow[ltunnus]'";
							$vtunres = mysql_query($query) or pupe_error($query);
							$vtunrow = mysql_fetch_array($vtunres);

							if ($vtunrow["vanhatunnus"] > 0) {
								$query = " 	SELECT GROUP_CONCAT(distinct tunnus SEPARATOR ',') tunnukset
											FROM lasku use index (yhtio_vanhatunnus)
											WHERE yhtio		= '$kukarow[yhtio]'
											and vanhatunnus	= '$vtunrow[vanhatunnus]'";
								$perheresult = mysql_query($query) or pupe_error($query);
								$perherow = mysql_fetch_array($perheresult);
							}
							
							if ($perherow["tunnukset"] != "") {
								$otunlisa = " and tilausrivi.otunnus in ($perherow[tunnukset]) ";
							}
							else {
								$otunlisa = " and tilausrivi.otunnus = '$jtrow[ltunnus]' ";
							}
						}
						
						// Jos tuote on tuoteperheen isä
						unset($lapsires);
						
						if ($toim == "ENNAKKO" and ($jtrow["perheid"] > 0 or $jtrow["perheid2"] > 0)) {							
							$query = "	SELECT tilausrivi.tuoteno, tilausrivi.nimitys, tilausrivi.varattu jt, tilausrivi.tilkpl, tilausrivi.hinta, tilausrivi.ale,
										tilausrivi.tunnus tunnus, tuote.ei_saldoa, tilausrivi.perheid, tilausrivi.perheid2, tilausrivi.otunnus, tuote.yksikko
										FROM tilausrivi use index (yhtio_otunnus)
										JOIN tuote use index (tuoteno_index) ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
										WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
										$otunlisa
										$pklisa
										and tilausrivi.varattu > 0
										and tilausrivi.tunnus != '$jtrow[tunnus]'
										ORDER BY tilausrivi.tunnus";
							$lapsires = mysql_query($query) or pupe_error($query);
						}
						elseif ($jtrow["perheid"] > 0 or $jtrow["perheid2"] > 0) {														
							$query = "	SELECT tilausrivi.tuoteno, tilausrivi.nimitys, tilausrivi.jt, tilausrivi.tilkpl, tilausrivi.hinta, tilausrivi.ale,
										tilausrivi.tunnus tunnus, tuote.ei_saldoa, tilausrivi.perheid, tilausrivi.perheid2, tilausrivi.otunnus, tuote.yksikko
										FROM tilausrivi use index (yhtio_otunnus)
										JOIN tuote use index (tuoteno_index) ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
										WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
										$otunlisa
										$pklisa
										and tilausrivi.jt > 0 
										and tilausrivi.tunnus != '$jtrow[tunnus]'
										ORDER BY tilausrivi.tunnus";
							$lapsires = mysql_query($query) or pupe_error($query);							
						}
					
						unset($perherow);
						$perheok = 0;
						
						//Käsiteltävät rivitunnukset (isä ja mahdolliset lapset)
						$tunnukset = $jtrow["tunnus"].",";
						
						if (is_resource($lapsires) and mysql_num_rows($lapsires) > 0) {													
							while($perherow = mysql_fetch_array($lapsires)) {
								$lapsitoimittamatta = $perherow["jt"];

								if ($perherow["ei_saldoa"] == "") {
									foreach ($varastosta as $vara) {
										list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($perherow["tuoteno"], "", $vara, "", "", "", "", "", $asiakasmaa);

										$lapsitoimittamatta -= $myytavissa;
									}
								}
								else {
									$lapsitoimittamatta	= 0;
								}
								
								$tunnukset .= $perherow["tunnus"].",";
								
								
								if ($lapsitoimittamatta > 0) {
									//tämän lapsen saldo ei riitä
									$perheok++;
								}
							}
						}

						$tunnukset = substr($tunnukset, 0, -1);						
						
						if ($jtrow["ei_saldoa"] == "") {
							foreach ($varastosta as $vara) {
								list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($jtrow["tuoteno"], "", $vara, "", "", "", "", "", $asiakasmaa);
								$kokonaismyytavissa += $myytavissa;
							}

							//jos ei ole automaaginen ja halutaan suoratoimittaa ja omasta varastosta ei löydy yhtään niin katotaan suoratoimitusmahdollisuus
							if ($automaaginen == '' and $kukarow["extranet"] == '' and $onkosuper == "" and $toim != 'SIIRTOLISTA' and ($suorana != '' or $tilaus_on_jo == 'KYLLA')) {
								$suora_tuoteno 	= $jtrow["tuoteno"];
								$suora_kpl 		= $jtrow["jt"];
								$paikatlask 	= 0;
								$paikat 		= '';
								$mista 			= 'selaus';

								require("suoratoimitusvalinta.inc");
							}
							else {
								$paikatlask = 0;
								$paikat 	= '';
							}
						}

						// Saldoa on tai halutaan nähdä kaikki rivit tai suoratoimituspaikkoja löytyi
						if ($kokonaismyytavissa > 0 or $toimi == '' or $paikatlask > 0) {
							
							//Tulostetaan otsikot
							if ($automaaginen == '' and $jt_rivilaskuri == 1) {
								echo "<table>";
								echo "<tr>";
								echo "<th>#</th>";
								
								echo "<th valign='top'>".t("Tuoteno");

								if ($kukarow["resoluutio"] == 'I' or $kukarow["extranet"] != "") {
									echo "<br>".t("Nimitys");
								}
								
								echo "</th>";

								if ($tilaus_on_jo == "") {
									echo "<th valign='top'>".t("Ytunnus")."<br>".t("Nimi")."<br>".t("Toim_Nimi")."</th>";

									if ($kukarow["resoluutio"] == 'I') {
										echo "<th valign='top'>".t("Tilausnro")."<br>".t("Viesti.")."</th>";
									}
								}

								echo "<th valign='top'>".t("JT")."<br>".t("Hinta")."<br>".t("Ale")."</th>";
								echo "<th valign='top'>".t("Status")."</th>";

								if ($kukarow["extranet"] == "") {
									echo "<th valign='top'>".t("Toimita")."<br>".t("kaikki")."</th>";
									echo "<th valign='top'>".t("Toimita")."<br>".t("kpl")."</th>";
									echo "<th valign='top'>".t("Poista")."<br>".t("loput")."</th>";
									echo "<th valign='top'>".t("Jätä")."<br>".t("loput")."</th>";
									echo "<th valign='top'>".t("Mitätöi")."<br>".t("rivi")."</th>";
								}
								else {
									echo "<th valign='top'>".t("Toimita")."</th>";
									echo "<th valign='top'>".t("Mitätöi")."</th>";
									echo "<th valign='top'>".t("Älä tee mitään")."</th>";
								}

								echo "</tr>";

								echo "<form action='$PHP_SELF' method='post'>";

								if ($tilaus_on_jo == "") {
									echo "<input type='hidden' name='tee' value='POIMI'>";
									echo "<input type='hidden' name='toim' value='$toim'>";
									echo "<input type='hidden' name='tyyppi' value='$tyyppi'>";
									echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
									echo "<input type='hidden' name='asiakasid' value='$asiakasid'>";
									echo "<input type='hidden' name='asiakasno' value='$asiakasno'>";
									echo "<input type='hidden' name='toimittaja' value='$toimittaja'>";
									echo "<input type='hidden' name='toimi' value='$toimi'>";
									echo "<input type='hidden' name='superit' value='$superit'>";
									echo "<input type='hidden' name='suorana' value='$suorana'>";
									echo "<input type='hidden' name='tuotenumero' value='$tuotenumero'>";

									if(is_array($varastosta)) {
										foreach ($varastosta as $vara) {
											echo "<input type='hidden' name='varastosta[$vara]' value='$vara'>";
										}
									}

									//Tehdään apumuuttuja jotta muokkaa_rivi linkki toimisi kunnolla
									$tilausnumero = "";
									
									if(is_array($varastosta)) {
										foreach ($varastosta as $vara) {
											$tilausnumero .= $vara."##";
										}
									}
								}
								else {
									if ($kukarow["extranet"] == "") {
										if ($asiakasmaa != '') {
											$asiakasmaalisa = "and (varastopaikat.sallitut_maat like '%$asiakasmaa%' or varastopaikat.sallitut_maat = '')";
										}
										
										$query = "	SELECT *
													FROM varastopaikat
													WHERE yhtio = '$kukarow[yhtio]' $asiakasmaalisa";
										$vtresult = mysql_query($query) or pupe_error($query);

										if (mysql_num_rows($vtresult) > 1) {
											echo "<b>".t("Näytä saatavuus vain varastosta").": </b> <select name='vainvarastosta' onchange='submit();'>";
											echo "<option value=''>Kaikki varastot</option>";

											while ($vrow = mysql_fetch_array($vtresult)) {
												if ($vrow["tyyppi"] != 'E' or $kukarow["varasto"] == $vrow["tunnus"]) {

													$sel = "";
													if ($vainvarastosta == $vrow["tunnus"]) {
														$sel = 'SELECTED';
													}

													echo "<option value='$vrow[tunnus]' $sel>$vrow[nimitys]</option>";
												}
											}

											echo "</select>";

										}
										echo "<input type='hidden' name='jt_kayttoliittyma' value='kylla'>";
									}

									echo "	<input type='hidden' name='toim' value='$toim'>
											<input type='hidden' name='tilausnumero' value='$tilausnumero'>
											<input type='hidden' name='tee'  value='JT_TILAUKSELLE'>
											<input type='hidden' name='tila' value='jttilaukseen'>";
								}
							}

							if ($automaaginen == '') {
								// Tuoteperheiden lapsille ei näytetä rivinumeroa
								if ($jtrow["perheid"] == $jtrow["tunnus"] or ($jtrow["perheid2"] == $jtrow["tunnus"] and $jtrow["perheid"] == 0)) {								
									$query = "	select count(*)
												from tilausrivi
												where yhtio = '$kukarow[yhtio]'
												$otunlisa
												$pklisa";
									$pkres = mysql_query($query) or pupe_error($query);
									$pkrow = mysql_fetch_array($pkres);

									$pknum 		= $pkrow[0];
									$borderlask = $pkrow[0];

									echo "<tr><td valign='top' rowspan='$pknum' style='border-top: 1px solid; border-left: 1px solid; border-bottom: 1px solid;' >$jt_rivilaskuri</td>";
								}
								elseif($jtrow["perheid"] == 0 and $jtrow["perheid2"] == 0) {
									echo "<tr><td valign='top'>$jt_rivilaskuri</td>";					
								}

								$classlisa 	= "";
								$class 		= "";
								
								if($borderlask == 1 and $pkrow[0] == 1 and $pknum == 1) {
									$classlisa = $class." style='border-top: 1px solid; border-bottom: 1px solid; border-right: 1px solid;' ";
									$class    .= " style=' border-top: 1px solid; border-bottom: 1px solid;' ";

									$borderlask--;
								}
								elseif($borderlask == $pkrow[0] and $pkrow[0] > 0) {
									$classlisa = $class." style='border-top: 1px solid; border-right: 1px solid;' ";
									$class    .= " style='border-top: 1px solid;' ";
									$borderlask--;
								}
								elseif($borderlask == 1) {
									if ($pknum > 1) {
										$classlisa = $class." style='font-style:italic; border-bottom: 1px solid; border-right: 1px solid;' ";
										$class    .= " style='font-style:italic; border-bottom: 1px solid;' ";
									}
									else {
										$classlisa = $class." style='border-bottom: 1px solid; border-right: 1px solid;' ";
										$class    .= " style='border-bottom: 1px solid;' ";
									}

									$borderlask--;
								}
								elseif($borderlask > 0 and $borderlask < $pknum) {
									$classlisa = $class." style='font-style:italic; border-right: 1px solid;' ";
									$class    .= " style='font-style:italic;' ";
									$borderlask--;
								}

								if ($kukarow["extranet"] == "") {
									echo "<td valign='top' $class>$ins <a href='../tuote.php?tee=Z&tuoteno=$jtrow[tuoteno]'>$jtrow[tuoteno]</a>";
								}
								else {
									echo "<td valign='top' $class>$ins $jtrow[tuoteno]";
								}

								if ($kukarow["resoluutio"] == 'I' or $kukarow["extranet"] != "") {
									echo "<br>$jtrow[nimitys]</td>";
								}
								echo "</td>";

								if ($tilaus_on_jo == "") {
									echo "<td valign='top' $class>$jtrow[ytunnus]<br>";

									if ($kukarow["extranet"] == "") {
										echo "<a href='../tuote.php?tee=NAYTATILAUS&tunnus=$jtrow[ltunnus]'>$jtrow[nimi]</a><br>";
									}
									else {
										echo "$jtrow[nimi]<br>";
									}
									
									echo "$jtrow[toim_nimi]</td>";

									if ($kukarow["resoluutio"] == 'I') {
										echo "<td valign='top' $class>$jtrow[otunnus]<br>$jtrow[viesti]</td>";
									}
								}

								if ($kukarow["extranet"] == "") {
									echo "<td valign='top' $class><a href='$PHP_SELF?toim=$toim&tee=MUOKKAARIVI&jt_rivitunnus=$jtrow[tunnus]&toimittajaid=$toimittajaid&asiakasid=$asiakasid&asiakasno=$asiakasno&toimittaja=$toimittaja&toimi=$toimi&superit=$superit&suorana=$suorana&tuotenumero=$tuotenumero&tyyppi=$tyyppi&tilausnumero=$tilausnumero'>$jtrow[jt]</a><br>";
								}
								else {
									echo "<td valign='top' align='right' $class>$jtrow[jt]<br>";
								}

								echo "$jtrow[hinta]<br>$jtrow[ale]%</td>";

							}
							
							if ($toim == "ENNAKKO") {
								$query = "	SELECT sum(varattu) jt, count(*) kpl
											FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
											WHERE yhtio = '$kukarow[yhtio]' 
											and tyyppi = 'E'
											and tuoteno = '$jtrow[tuoteno]' 
											and varattu > 0";
							}
							else {
								$query = "	SELECT sum(jt) jt, count(*) kpl
											FROM tilausrivi use index(yhtio_tyyppi_tuoteno_varattu)
											WHERE yhtio='$kukarow[yhtio]'
											and tyyppi in ('L','G')
											and tuoteno='$jtrow[tuoteno]'
											and varattu=0
											and jt<>0
											and kpl=0
											and var='J'";
							}
							$juresult = mysql_query($query) or pupe_error($query);
							$jurow    = mysql_fetch_array ($juresult);

							// Jos riittää kaikille
							if (($kokonaismyytavissa >= $jurow["jt"] or $jtrow["ei_saldoa"] != "")  and $perheok==0) {	
								
								// Jos haluttiin toimittaa tämä rivi automaagisesti
								if ($kukarow["extranet"] == "" and $automaaginen!='') {
									echo "<font class='message'>".t("Tuote")." $jtrow[tuoteno] ".t("lisättiin tilaukseen")."!</font><br>";
									
									// Pomitaan tämä rivi/perhe
									$loput[$tunnukset] = "KAIKKI";
									$tunnusarray = explode(',', $tunnukset);

									require("tee_jt_tilaus.inc");
								}
								else {
									echo "<input type='hidden' name='jt_rivitunnus[]' value='$tunnukset'>";

									if ($kukarow["extranet"] == "") {
										echo "<td valign='top' $class>$kokonaismyytavissa $jtrow[yksikko]<br><font color='#00FF00'>".t("Riittää kaikille")."!</font></td>";
										echo "<td valign='top' align='center' $class>".t("K")."<input type='radio' name='loput[$tunnukset]' value='KAIKKI'></td>";
										echo "<td valign='top' align='center' $class><input type='text' name='kpl[$tunnukset]' size='4'></td>";
										echo "<td valign='top' align='center' $class>".t("P")."<input type='radio' name='loput[$tunnukset]' value='POISTA'></td>";
										echo "<td valign='top' align='center' $class>".t("J")."<input type='radio' name='loput[$tunnukset]' value='JATA'></td>";
										echo "<td valign='top' align='center' $classlisa>".t("M")."<input type='radio' name='loput[$tunnukset]' value='MITA'></td>";
									}
									elseif ($kukarow["extranet"] != "") {
										echo "<td valign='top' $class><font color='#00FF00'>".t("Voidaan toimittaa")."!</font></td>";

										if ((int) $kukarow["kesken"] > 0) {
											echo "	<td valign='top' align='center' $class>".t("Toimita")."<input type='radio' name='loput[$tunnukset]' value='KAIKKI'></td>";
										}
										else {
											echo "<td valign='top' $class>".t("Avaa uusi tilaus jotta voit toimittaa rivin").".</td>";
										}

										echo "	<td valign='top' align='center' $class>".t("Mitätöi")."<input type='radio' name='loput[$tunnukset]' value='MITA'></td>
												<td valign='top' align='center' $classlisa>".t("Älä tee mitään")."<input type='radio' name='loput[$tunnukset]' value=''></td>";

									}
								}
							}
							// Riittää tälle riville mutta ei kaikille
							elseif ($kukarow["extranet"] == "" and $kokonaismyytavissa >= $jtrow["jt"] and $perheok==0) {
								if ($automaaginen == '') {
									echo "<td valign='top' $class>$kokonaismyytavissa $jtrow[yksikko]<br><font color='#FF4444'>".t("Ei riitä kaikille")."!</font></td>";
									echo "	<input type='hidden' name='jt_rivitunnus[]' value='$tunnukset'>
											<td valign='top' align='center' $class>".t("K")."<input type='radio' name='loput[$tunnukset]' value='KAIKKI'></td>
											<td valign='top' align='center' $class><input type='text' name='kpl[$tunnukset]' size='4'></td>
											<td valign='top' align='center' $class>".t("P")."<input type='radio' name='loput[$tunnukset]' value='POISTA'></td>
											<td valign='top' align='center' $class>".t("J")."<input type='radio' name='loput[$tunnukset]' value='JATA'></td>
											<td valign='top' align='center' $classlisa>".t("M")."<input type='radio' name='loput[$tunnukset]' value='MITA'></td>";
								}
							}
							// Suoratoimitus
							elseif ($paikatlask > 0 and $automaaginen == ''and $kukarow['extranet'] == '') {
								echo "<input type='hidden' name='jt_rivitunnus[]' value='$tunnukset'>";

								if ($suoratoim_totaali >= $jurow["jt"]) {
									echo "<td valign='top' $class><font color='#00FF00'>".t("Riittää kaikille")."!</font></td>";
								}
								elseif ($suoratoim_totaali >= $jtrow["jt"]) {
									echo "<td valign='top' $class><font color='#FF4444'>".t("Ei riitä kaikille")."!</font></td>";
								}
								else {
									echo "<td valign='top' $class><font color='#00FFFF'>".t("Ei riitä koko riville")."!</font></td>";
								}

								//suoratoimituksille annetaan dropdowni
								$ddpaikat = "<option value=''>".t("Ei toimiteta")."</option>".$paikat;

								echo "<td valign='top' colspan='4' $class><select Style='{font-size: 8pt; padding:0;}' name='suoratoimpaikka[$tunnukset]'".$ddpaikat."</select></td>";

								echo "<td valign='top' align='center' $classlisa>".t("M")."<input type='radio' name='loput[$tunnukset]' value='MITA'></td>";
							}
							elseif ($kukarow["extranet"] == "" and $kokonaismyytavissa > 0 and $perheok==0) {
								if ($automaaginen == '') {
									echo "<td valign='top' $class>$kokonaismyytavissa $jtrow[yksikko]<br><font color='#00FFFF'>".t("Ei riitä koko riville")."!</font></td>";
									echo "	<input type='hidden' name='jt_rivitunnus[]' value='$tunnukset'>
											<td valign='top' align='center' $class>&nbsp;</td>
											<td valign='top' align='center' $class><input type='text' name='kpl[$tunnukset]' size='4'></td>
											<td valign='top' align='center' $class>".t("P")."<input type='radio' name='loput[$tunnukset]' value='POISTA'></td>
											<td valign='top' align='center' $class>".t("J")."<input type='radio' name='loput[$tunnukset]' value='JATA'></td>
											<td valign='top' align='center' $classlisa>".t("M")."<input type='radio' name='loput[$tunnukset]' value='MITA'></td>";
								}
							}
							// ja muuten ei voida sitten toimittaa ollenkaan
							else {
								if ($automaaginen == '') {
									echo "<td valign='top' $class>$kokonaismyytavissa $jtrow[yksikko]<br><font color='#FF7777'>".t("Riviä ei voida toimittaa")."!</font></td>";
									echo "<input type='hidden' name='jt_rivitunnus[]' value='$tunnukset'>";

									if ($kukarow["extranet"] == "") {
										echo "	<td valign='top' align='center' $class>&nbsp;</td>
												<td valign='top' align='center' $class>&nbsp;</td>
												<td valign='top' align='center' $class>&nbsp;</td>
												<td valign='top' align='center' $class>&nbsp;</td>
												<td valign='top' align='center' $classlisa>".t("M")."<input type='radio' name='loput[$tunnukset]' value='MITA'></td>";
									}
									else {
										echo "	<td valign='top' align='center' $class>&nbsp;</td>
												<td valign='top' align='center' $class>".t("Mitätöi")."<input type='radio' name='loput[$tunnukset]' value='MITA'></td>
												<td valign='top' align='center' $classlisa>".t("Älä tee mitään")."<input type='radio' name='loput[$tunnukset]' value=''></td>";
									}
								}
							}

							if ($automaaginen == '') {
								echo "</tr>";
							}
							
							if (is_resource($lapsires) and mysql_num_rows($lapsires) > 0 and $automaaginen == '') {
								
								mysql_data_seek($lapsires, 0);
								
								while($perherow = mysql_fetch_array($lapsires)) {
									
									$classlisa 	= "";
									$class 		= "";

									if($borderlask == 1 and $pkrow[1] == 1 and $pknum == 1) {
										$classlisa = $class." style='border-top: 1px solid; border-bottom: 1px solid; border-right: 1px solid;' ";
										$class    .= " style=' border-top: 1px solid; border-bottom: 1px solid;' ";

										$borderlask--;
									}
									elseif($borderlask == $pkrow[1] and $pkrow[1] > 0) {
										$classlisa = $class." style='border-top: 1px solid; border-right: 1px solid;' ";
										$class    .= " style='border-top: 1px solid;' ";
										$borderlask--;
									}
									elseif($borderlask == 1) {
										if ($pknum > 1) {
											$classlisa = $class." style='font-style:italic; border-bottom: 1px solid; border-right: 1px solid;' ";
											$class    .= " style='font-style:italic; border-bottom: 1px solid;' ";
										}
										else {
											$classlisa = $class." style='border-bottom: 1px solid; border-right: 1px solid;' ";
											$class    .= " style='border-bottom: 1px solid;' ";
										}

										$borderlask--;
									}
									elseif($borderlask > 0 and $borderlask < $pknum) {
										$classlisa = $class." style='font-style:italic; border-right: 1px solid;' ";
										$class    .= " style='font-style:italic;' ";
										$borderlask--;
									}
									
									echo "<tr>";
								
									$kokonaismyytavissa = 0;

									foreach ($varastosta as $vara) {
										list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($perherow["tuoteno"], "", $vara, "", "", "", "", "", $asiakasmaa);

										$kokonaismyytavissa += $myytavissa;
									}
								
									if ($kukarow["extranet"] == "") {
										echo "<td valign='top' $class><a href='../tuote.php?tee=Z&tuoteno=$perherow[tuoteno]'>$perherow[tuoteno]</a>";
									}
									else {
										echo "<td valign='top' $class>$perherow[tuoteno]";
									}

									if ($kukarow["resoluutio"] == 'I' or $kukarow["extranet"] != "") {
										echo "<br>$perherow[nimitys]</td>";
									}
									echo "</td>";

									if ($tilaus_on_jo == "") {
										echo "<td valign='top' $class>$perherow[ytunnus]<br>";

										if ($kukarow["extranet"] == "") {
											echo "<a href='../tuote.php?tee=NAYTATILAUS&tunnus=$perherow[ltunnus]'>$perherow[nimi]</a><br>";
										}
										else {
											echo "$perherow[nimi]<br>";
										}
									
										echo "$perherow[toim_nimi]</td>";

										if ($kukarow["resoluutio"] == 'I') {
											echo "<td valign='top' $class>$perherow[otunnus]<br>$perherow[viesti]</td>";
										}
									}

									if ($kukarow["extranet"] == "") {
										echo "<td valign='top' $class><a href='$PHP_SELF?toim=$toim&tee=MUOKKAARIVI&jt_rivitunnus=$perherow[tunnus]&toimittajaid=$toimittajaid&asiakasid=$asiakasid&asiakasno=$asiakasno&toimittaja=$toimittaja&toimi=$toimi&superit=$superit&suorana=$suorana&tuotenumero=$tuotenumero&tyyppi=$tyyppi&tilausnumero=$tilausnumero'>$perherow[jt]</a><br>";
									}
									else {
										echo "<td valign='top' align='right' $class>$perherow[jt]<br>";
									}

									echo "$perherow[hinta]<br>$perherow[ale]%</td>";
									echo "<td valign='top' $class>$kokonaismyytavissa $perherow[yksikko]<br></font></td>";
									
									if ($kukarow["extranet"] == "") {
										echo "	<td valign='top' align='center' $class>&nbsp;</td>
												<td valign='top' align='center' $class>&nbsp;</td>
												<td valign='top' align='center' $class>&nbsp;</td>
												<td valign='top' align='center' $class>&nbsp;</td>
												<td valign='top' align='center' $classlisa>&nbsp;</td>";
									}
									else {
										echo "	<td valign='top' align='center' $class>&nbsp;</td>
												<td valign='top' align='center' $class>&nbsp;</td>
												<td valign='top' align='center' $classlisa>&nbsp;</td>";
									}
									
									echo "</tr>";
								}
								
								unset($lapsires);
							}

							$jt_rivilaskuri++;
						}
					}
				}

				if ($automaaginen == '' and $jt_rivilaskuri > 0) {
					echo "<tr><td colspan='8' class='back'></td><td colspan='3' class='back' align='right'><input type='submit' value='".t("Poimi")."'></td></tr>";
					echo "</table>";
					echo "</form><br>";
				}
				else {
					echo t("Yhtään riviä ei löytynyt")."!<br>";
				}
			}
			else {
				echo t("Yhtään riviä ei löytynyt")."!<br>";
			}
			$tee = '';
		}
	}

	if ($tilaus_on_jo == "" and $tee == '') {

		echo "<br><font class='message'>".t("Valinnat")."</font><br><br>";

		$query = "	SELECT *
					FROM varastopaikat
					WHERE yhtio = '$kukarow[yhtio]'";
		$vtresult = mysql_query($query) or pupe_error($query);

		echo "	<form name='valinta' action='$PHP_SELF' method='post'>
				<input type='hidden' name='toim' value='$toim'>
				<table>";

		while ($vrow = mysql_fetch_array($vtresult)) {
			if (($vrow["tyyppi"] != 'E') or ($kukarow["varasto"] == $vrow["tunnus"])) {

				$sel = "";
				if ($varastosta[$vrow["tunnus"]] == $vrow["tunnus"]) {
					$sel = 'CHECKED';
				}

				echo "<tr><th>".t("Toimita varastosta:")." $vrow[nimitys]</th><td><input type='checkbox' name='varastosta[$vrow[tunnus]]' value='$vrow[tunnus]' $sel></td></tr>";
			}
		}

		$query = "	select tyyppi_tieto
					from toimi
					where toimi.yhtio = '$kukarow[yhtio]'
					and toimi.tyyppi        = 'S'
					and toimi.tyyppi_tieto != ''
					and toimi.edi_palvelin != ''
					and toimi.edi_kayttaja != ''
					and toimi.edi_salasana != ''
					and toimi.edi_polku    != ''
					and toimi.oletus_vienti in ('C','F','I')";
		$superjtres  = mysql_query($query) or pupe_error($query);
		if (mysql_num_rows($superjtres) > 0) {

			$sel = "";
			if ($suorana != '') $sel = 'CHECKED';
			echo "<tr><th>".t("Toimita suoratoimituksena")."</th><td><input type='checkbox' name='suorana' value='suora' $sel></td></tr>";

		}

		$selt = "";
		$sela = "";
		$selp = "";

		if($tyyppi == "T") {
			$selt = "SELECTED";
		}
		elseif($tyyppi == "A") {
			$sela = "SELECTED";
		}
		elseif($tyyppi == "P") {
			$selp = "SELECTED";
		}

		echo "<tr>
				<th>".t("Järjestys")."</th>
				<td>
					<select name='tyyppi'>
					<option value='T' $selt>".t("Tuotteittain")."</option>
					<option value='A' $sela>".t("Asiakkaittain")."</option>
					<option value='P' $selp>".t("Päivämääräjärjestys")."</option>
					</select>
				</td>
			</tr>";

		$sel = '';
		if ($automaaginen != '') $sel = 'CHECKED';

		echo "	<tr>
				<th>".t("Toimita selkeät rivit automaagisesti")."</th>
				<td><input type='checkbox' name='automaaginen' $sel onClick = 'return verify()'></td>
			</tr>";

		echo "</table>";


		echo "<table>";

		echo "<br><font class='message'>".t("Rajaukset")."</font><br><br>";

		echo "<tr>
				<th>".t("Toimittaja")."</th>
				<td>
				<input type='text' size='20' name='ytunnus' value='$toimittaja'>
				</td>
			</tr>";

		echo "<tr>
				<th>".t("Asiakas")."</th>
				<td>
				<input type='text' size='20' name='asiakasno' value='$asiakasno'>
				</td>
				</td>
			</tr>";

		echo "<tr>";
		echo "<th>".t("Tuoteosasto")."</th>";

		$query = "	SELECT *
					FROM avainsana
					WHERE yhtio = '$kukarow[yhtio]' and laji='OSASTO'
					ORDER BY selite+0";
		$result = mysql_query($query) or pupe_error($query);

		echo "<td><select name='tuoteosasto'>";
		echo "<option value=''>".t("Tuoteosasto")."</option>";

		while ($row = mysql_fetch_array($result)) {
			if ($tuoteosasto == $row["selite"]) $sel = "selected";
			else $sel = "";
			echo "<option value='$row[selite]' $sel>$row[selite] $row[selitetark]</option>";
		}

		echo "</select></td>";
		echo "</tr>";

		echo "<tr>";
		echo "<th>".t("Tuoteryhmä")."</th>";

		$query = "	SELECT *
					FROM avainsana
					WHERE yhtio = '$kukarow[yhtio]' and laji='TRY'
					ORDER BY selite+0";
		$result = mysql_query($query) or pupe_error($query);

		echo "<td><select name='tuoteryhma'>";
		echo "<option value=''>".t("Tuoteryhmä")."</option>";

		while ($row = mysql_fetch_array($result)) {
			if ($tuoteryhma == $row["selite"]) $sel = "selected";
			else $sel = "";
			echo "<option value='$row[selite]' $sel>$row[selite] $row[selitetark]</option>";
		}

		echo "</select></td>";
		echo "</tr>\n";

		echo "<tr>";
		echo "<th>".t("Tuotemerkki")."</th>";

		$query = "	SELECT distinct tuotemerkki
					FROM tuote use index (yhtio_tuotemerkki)
					WHERE yhtio='$kukarow[yhtio]'
					and tuotemerkki != ''
					ORDER BY tuotemerkki";
		$result = mysql_query($query) or pupe_error($query);

		echo "<td><select name='tuotemerkki'>";
		echo "<option value=''>".t("Tuotemerkki")."</option>";

		while ($row = mysql_fetch_array($result)) {
			if ($tuotemerkki == $row["tuotemerkki"]) $sel = "selected";
			else $sel = "";
			echo "<option value='$row[tuotemerkki]' $sel>$row[tuotemerkki]</option>";
		}

		echo "</select></td>";
		echo "</tr>\n";

		echo "<tr>
				<th>".t("Tuotenumero")."</th>
				<td>
				<input type='text' name='tuotenumero' value='$tuotenumero' size='10'>
				</td>
				</td>
			</tr>";

		$sel = '';
		if ($toimi != '') $sel = 'CHECKED';

		echo "<tr>
				<th>".t("Näytä vain toimitettavat rivit")."</th>
				<td><input type='checkbox' name='toimi' $sel></td>
			</tr>";

		if ($toim == "ENNAKKO") {
			echo "	<SCRIPT LANGUAGE=JAVASCRIPT>
					function verify(){
						msg = '".t("Haluatko todella toimittaa kaikki selkeät ennakkorivit? Eli tiedätkö nyt aivan varmasti mitä olet tekemässä")."?';
						return confirm(msg);
					}
					</SCRIPT>";
		}
		else {
			echo "	<SCRIPT LANGUAGE=JAVASCRIPT>
					function verify(){
						msg = '".t("Haluatko todella toimittaa kaikki selkeät JT-Rivit? Eli tiedätkö nyt aivan varmasti mitä olet tekemässä")."?';
						return confirm(msg);
					}
					</SCRIPT>";
		}

		$sel = '';
		if ($superit != '') $sel = 'CHECKED';

		echo "	<tr>
				<th>".t("Näytä vain suoratoimitusrivit")."</th>
				<td><input type='checkbox' name='superit' $sel></td><td class='back'>".t("Älä toimita suoratoimituksia, ellet ole 100% varma että voit niin tehdä")."!</td>
				</tr>";

		echo "</table>

			<br><input type='submit' value='".t("Näytä")."'>
			</form>";
	}

	if (strpos($_SERVER['SCRIPT_NAME'], "jtselaus.php")  !== FALSE) {
		if (file_exists("../inc/footer.inc")) {
			require ("../inc/footer.inc");
		}
		else {
			require ("footer.inc");
		}
	}

?>