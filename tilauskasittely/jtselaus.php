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
		echo "<font class='head'>".t("JT rivit")."</font><hr>";
	}
	
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
						
	//JT-rivit on poimittu jtselauksessa
	if ($kukarow["extranet"] == "" and $tilaus_on_jo == "" and $tee == 'POIMI') {
		foreach($rivitunnus as $tunnus) {
			if ($kpl[$tunnus] > 0 or $loput[$tunnus] != '') {
				require ('tee_jt_tilaus.inc');
			}
		}
		$tee = 'JATKA';
	}
	
	//JT-rivit on poimittu tilaus-myynnissä
	if ($tee == "JT_TILAUKSELLE" and $tila == "jttilaukseen") {			
		asort($rivitunnus);
		
		foreach($rivitunnus as $tunnus) {									
			if ($kpl[$tunnus] > 0 or $loput[$tunnus] != '') {						
				//mennään aina tänne ja sit tuolla inkissä katotaan aiheuttaako toimenpiteitä.
				$mista = 'jtrivit_tilaukselle.inc';
				require("laskealetuudestaan.inc");
				
				require ('tee_jt_tilaus.inc');				
			}
		}
		
		if ($kukarow["extranet"] != "" and strpos($_SERVER['SCRIPT_NAME'], "jtselaus.php")  !== FALSE) {
			$tee = "JATKA";
		}
	}
						
	
	if ($kukarow["extranet"] == "" and $tilaus_on_jo == "" and $tee == 'TOIMITA') {
		$debug  = 1;
		$query  = "select * from lasku where yhtio='$kukarow[yhtio]' and laatija='$kukarow[kuka]' and ((alatila = 'J' and tila = 'N') or (alatila = 'P' and tila = 'G'))";
		$jtrest = mysql_query($query) or pupe_error($query);

		while ($laskurow = mysql_fetch_array($jtrest)) {
			$query  = "UPDATE lasku SET alatila='A' WHERE yhtio='$kukarow[yhtio]' and tunnus='$laskurow[tunnus]'";
			$apure  = mysql_query($query) or pupe_error($query);

			$mista='jtselaus';
			//mennään aina tänne ja sit tuolla inkissä katotaan aiheuttaako toimenpiteitä.
			require("laskealetuudestaan.inc");

			// tarvitaan $kukarow[yhtio], $kukarow[kesken], $laskurow ja $yhtiorow
			$kukarow["kesken"] = $laskurow["tunnus"];
			if ($laskurow['tila']== 'G') {
				require("tilaus-valmis-siirtolista.inc");
			}
			else {
				require("tilaus-valmis.inc");
			}
		}

		$tee = '';
	}
			
	//Tutkitaan onko käyttäjällä keskenolevia jt-rivejä
	if ($kukarow["extranet"] == "" and $tilaus_on_jo == "") {
		$query = "SELECT *
				FROM lasku
				WHERE yhtio = '$kukarow[yhtio]' and laatija='$kukarow[kuka]' and ((alatila = 'J' and tila = 'N') or (alatila = 'P' and tila = 'G'))";
		$stresult = mysql_query($query) or pupe_error($query);
	
		if (mysql_num_rows($stresult) > 0) {
			echo "	<form name='valinta' action='$PHP_SELF' method='post'>
					<input type='hidden' name='tee' value='TOIMITA'>				
					<font class='error'>".t("HUOM! Sinulla on toimittamattomia jt-rivejä")."</font><br>
					<table>				
					<tr>
					<td>".t("Laske alennukset uudelleen")."</td>
					<td><input type='checkbox' name='laskeuusix'></td></tr><tr>
					<td>".t("Toimita poimitut JT-rivit")."</td>
					<td><input type='submit' value='".t("Toimita")."'></td>
					</tr>
					</table>
					</form><hr>";
		}
	}
		
	//muokataan tilausriviä
	if ($kukarow["extranet"] == "" and $tilaus_on_jo == "" and $tee == 'MUOKKAARIVI') {
		$query = "	SELECT *
					FROM tilausrivi
					WHERE tunnus = '$rivitunnus' and yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
	
		if (mysql_num_rows($result) == 0) {
			echo t("Tilausriviä ei löydy")."! $query";
			exit;
		}
		$trow = mysql_fetch_array($result);
	
		$query = "	DELETE from tilausrivi
					WHERE tunnus = '$rivitunnus' and yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
	
		$tuoteno 		= $trow["tuoteno"];
		$hinta 			= $trow["hinta"];
		$kpl 			= $trow["jt"];
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
		echo "<input type='hidden' name='tee' value='LISAARIVI'>";
		echo "<input type='hidden' name='tyyppi' value='$tyyppi'>";
		echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
		echo "<input type='hidden' name='asiakasid' value='$asiakasid'>";
		echo "<input type='hidden' name='asiakasno' value='$asiakasno'>";
		echo "<input type='hidden' name='toimittaja' value='$toimittaja'>";
		echo "<input type='hidden' name='toimi' value='$toimi'>";
		echo "<input type='hidden' name='superit' value='$superit'>";
		echo "<input type='hidden' name='tuotenumero' value='$tuotenumero'>";
		echo "<input type='hidden' name='rivinotunnus' value='$rivinotunnus'>";
		
		if(is_array($varastosta)) {
			foreach ($varastosta as $vara) {
				$tilausnumero .= $vara."##";
			}
		}
		echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
		
		$toim = "RIVISYOTTO";
		
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
			list($tuotenumero,$tyyppi,$toimi,$superit,$automaaginen,$ytunnus,$asiakasno,$toimittaja) = explode('#', $muutparametrit);
		
			$varastot = explode('##', $tilausnumero);
		
			foreach ($varastot as $vara) {
				$varastosta[$vara] = $vara;
			}		
		}
		
		$muutparametrit = $tuotenumero."#".$tyyppi."#".$toimi."#".$superit."#".$automaaginen."#".$ytunnus."#".$asiakasno."#";				
		
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
		
		$muutparametrit = $tuotenumero."#".$tyyppi."#".$toimi."#".$superit."#".$automaaginen."#".$ytunnus."#".$asiakasno."#";
		
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
	
		if ($toimittaja != '') {
			$tolisa1 = " LEFT JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno ";
			$tolisa2 = " and tuotteen_toimittajat.toimittaja = '$toimittaja' ";
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
			$query = "	SELECT distinct otunnus, if(perheid!=0, concat('JOPERHE',tilausrivi.perheid), concat('EIPERHE',tilausrivi.tunnus)) perheid, tilaajanrivinro, tilausrivi.tuoteno 
						FROM tilausrivi use index (yhtio_tyyppi_var_keratty_kerattyaika_uusiotunnus)
						JOIN lasku use index (PRIMARY) ON lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus
						JOIN tuote use index (tuoteno_index) ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
						$tolisa1
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and tilausrivi.tyyppi in ('L','G')
						and tilausrivi.var = 'J'
						and tilausrivi.keratty = ''
						and tilausrivi.uusiotunnus = 0
						and tilausrivi.varattu = 0
						and tilausrivi.kpl = 0
						and tilausrivi.jt <> 0
						and (tilausrivi.tunnus = tilausrivi.perheid or tilausrivi.perheid=0)
						$tolisa2
						$aslisa
						$tuotlisa
						$order
						$limit";
			$isaresult = mysql_query($query) or pupe_error($query);
								
			if (mysql_num_rows($isaresult) > 0) {
				
				$jt_rivilaskuri = 0;
				
				while ($isarow = mysql_fetch_array($isaresult)) {
	
					//tutkitaan onko tämä suoratoimitusrivi 
					$onkosuper = "";
	
					if ($isarow["tilaajanrivinro"] != 0) {
						$query = "	SELECT *
									FROM toimi
									WHERE yhtio = '$kukarow[yhtio]'
									and tunnus  = '$isarow[tilaajanrivinro]'";
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
										and tilausrivi.tuoteno='$isarow[tuoteno]'";
							$sjtres = mysql_query($query) or pupe_error($query);
	
							if (mysql_num_rows($sjtres) > 0) {
								$onkosuper = "ON";
							}
						}
					}
	
					// ei näytetä suoratoimitusrivejä, eillei $superit ole ruksattu, sillon näytetään pelkästään suoratoimitukset 
					if (($onkosuper == "" and $superit == "") or ($onkosuper == "ON" and $superit != "")) { 
						
						$lirt = ""; 
	
						if (substr($isarow["perheid"],0,7) == "JOPERHE") {
							$lirt = " and tilausrivi.perheid = '".substr($isarow["perheid"],7)."' ";
						}
						elseif(substr($isarow["perheid"],0,7) == "EIPERHE") {
							$lirt = " and tilausrivi.tunnus = '".substr($isarow["perheid"],7)."' ";
						}
	
						$query = "	SELECT tilausrivi.tuoteno, tilausrivi.nimitys, lasku.ytunnus, tilausrivi.jt, lasku.nimi, lasku.toim_nimi, lasku.viesti, tilausrivi.tilkpl,
									lasku.tunnus ltunnus, tilausrivi.tunnus tunnus, tuote.ei_saldoa, tilausrivi.perheid, tilausrivi.otunnus
									FROM tilausrivi use index (yhtio_otunnus), lasku use index (PRIMARY), tuote use index (tuoteno_index)
									WHERE tilausrivi.yhtio='$kukarow[yhtio]'
									and tilausrivi.tyyppi in ('L','G')
									and tilausrivi.var = 'J'
									and lasku.yhtio=tilausrivi.yhtio
									and tilausrivi.otunnus=lasku.tunnus
									and tuote.yhtio=tilausrivi.yhtio
									and tuote.tuoteno=tilausrivi.tuoteno
									and tilausrivi.varattu = 0
									and tilausrivi.kpl = 0
									and tilausrivi.jt <> 0
									and tilausrivi.otunnus = '$isarow[otunnus]'
									$lirt
									$aslisa
									ORDER BY tilausrivi.tunnus";
						$etsresult2 = mysql_query($query) or pupe_error($query);
																								
						while ($jtrow = mysql_fetch_array($etsresult2)) {
																					
							// haetaan tuotteelle saldo
							$akeraysaika = date("Y-m-d");
							$tuoteno     = $jtrow["tuoteno"];
							$atil        = $jtrow["jt"];
							unset($trow); // näin saadaan tuotetiedot haettua uudestaan
	
							if ($jtrow["ei_saldoa"] == "") {
								require("tarkistasaldo.inc");
	
								// varas muuttujassa on saldo tämän rivin toimituksen jälkeen
								// total on todellinen saldo
								$total = 0;
								$varas = 0;
								$onkomillaan = 0;
	
								for ($i=1; $i <= count($hyllyalue); $i++) {
#TODO palauittaa varaston väärin
									$query = "	SELECT *
												FROM varastopaikat
												WHERE yhtio			=  '$kukarow[yhtio]'
												and alkuhyllyalue	<= '$hyllyalue[$i]'
												and loppuhyllyalue	>= '$hyllyalue[$i]'
												and alkuhyllynro	<= '$hyllynro[$i]'
												and loppuhyllynro	>= '$hyllynro[$i]'";
									$vares = mysql_query($query) or pupe_error($query);
	
									if (mysql_num_rows($vares)!=0) {
										$varow = mysql_fetch_array($vares);
	
										if ($tilaus_on_jo == "") {
											foreach ($varastosta as $vara) {
												if ($varow["tunnus"] == $vara) {
													if ($saldo[$i] < 0) {
														$saldo[$i] = $varastossa[$i];
													}
	
													$total += $varastossa[$i];
													$varas += $saldo[$i];
												}
											}
										}
										else {
											if ($varow["tyyppi"] == "") {
												if ($saldo[$i] < 0) {
													$saldo[$i] = $varastossa[$i];
												}
	
												$total += $varastossa[$i];
												$varas += $saldo[$i];
											}
										}
									}
								}
							}
							else {
								//saldottomat tuotteet
								$voidaankolisata = 1;
								$kappaleet[] = $atil;
							}
															
							// saldo riittää tai halutaan nähdä kaikki rivit
							if ($total > 0 or $toimi=='') {
								//Tulostetaan otsikot
								if ($automaaginen == '' and $jt_rivilaskuri == 0) {
									echo "<table>";
									echo "<tr>";
									echo "<th>".t("Tuoteno")."</th>";
					
									if ($kukarow["resoluutio"] == 'I' or $kukarow["extranet"] != "") {
										echo "<th>".t("Nimitys")."</th>";
									}
					
									if ($tilaus_on_jo == "") {
										echo "<th>".t("Ytunnus")."</th>";
										echo "<th>".t("Nimi")."</th>";
					
										if ($kukarow["resoluutio"] == 'I') {
											echo "<th>".t("Toim_Nimi")."</th>";
											echo "<th>".t("Viesti.")."</th>";
											echo "<th>".t("Tilausnro")."</th>";
					
										}
									}
					
									echo "<th>".t("JT")."</th>";
									echo "<th>".t("Status")."</th>";
									
									if ($kukarow["extranet"] == "") {
										echo "<th>".t("Saldo")."</th>";
										echo "<th>".t("Toim. kaikki")."</th>";
										echo "<th>".t("Toim. Kpl")."</th>";
										echo "<th>".t("Poista lop.")."</th>";
										echo "<th>".t("Jätä lop.")."</th>";
										echo "<th>".t("Mitätöi")."</th>";
									}
									else {
										echo "<th>".t("Toimita")."</th>";
										echo "<th>".t("Mitätöi")."</th>";
										echo "<th>".t("Älä tee mitään")."</th>";
									}
									
									echo "</tr>";
					
									echo "<form action='$PHP_SELF' method='post'>";
					
									if ($tilaus_on_jo == "") {
										echo "<input type='hidden' name='tee' value='POIMI'>";
										echo "<input type='hidden' name='tyyppi' value='$tyyppi'>";
										echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
										echo "<input type='hidden' name='asiakasid' value='$asiakasid'>";
										echo "<input type='hidden' name='asiakasno' value='$asiakasno'>";
										echo "<input type='hidden' name='toimittaja' value='$toimittaja'>";
										echo "<input type='hidden' name='toimi' value='$toimi'>";
										echo "<input type='hidden' name='superit' value='$superit'>";
										echo "<input type='hidden' name='tuotenumero' value='$tuotenumero'>";
					
										foreach ($varastosta as $vara) {
											echo "<input type='hidden' name='varastosta[$vara]' value='$vara'>";
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
										echo "	<input type='hidden' name='toim' value='$toim'>
												<input type='hidden' name='tilausnumero' value='$tilausnumero'>
												<input type='hidden' name='tee'  value='JT_TILAUKSELLE'>
												<input type='hidden' name='tila' value='jttilaukseen'>";
									}
								}
																																								
								if ($automaaginen == '') {
									echo "<tr>";
	
									$ins = "";
									if ($jtrow["tunnus"] != $jtrow["perheid"] and $jtrow["perheid"] != 0) {
										$ins = "-->";
									}
	
									if ($kukarow["extranet"] == "") {
										echo "<td nowrap>$ins <a href='../tuote.php?tee=Z&tuoteno=$jtrow[tuoteno]'>$jtrow[tuoteno]</a></td>";
									}
									else {
										echo "<td nowrap>$ins $jtrow[tuoteno]</td>";
									}
									
									if ($kukarow["resoluutio"] == 'I' or $kukarow["extranet"] != "") {
										echo "<td>$jtrow[nimitys]</td>";
									}
	
									if ($tilaus_on_jo == "") {
										echo "<td>$jtrow[ytunnus]</td>";
										
										if ($kukarow["extranet"] == "") {
											echo "<td><a href='../tuote.php?tee=NAYTATILAUS&tunnus=$jtrow[ltunnus]'>$jtrow[nimi]</a></td>";
										}
										else {
											echo "<td>$jtrow[nimi]</td>";
										}
											
										
										if ($kukarow["resoluutio"] == 'I') {
											echo "<td>$jtrow[toim_nimi]</td>";
											echo "<td>$jtrow[viesti]</td>";
											echo "<td>$jtrow[otunnus]</td>";
										}
									}
	
									if ($kukarow["extranet"] == "") {
										echo "<td><a href='$PHP_SELF?tee=MUOKKAARIVI&rivitunnus=$jtrow[tunnus]&toimittajaid=$toimittajaid&asiakasid=$asiakasid&asiakasno=$asiakasno&toimittaja=$toimittaja&toimi=$toimi&superit=$superit&tuotenumero=$tuotenumero&tyyppi=$tyyppi&tilausnumero=$tilausnumero'>$jtrow[jt]</a></td>";	
									}
									else {
										echo "<td>$jtrow[jt]</td>";	
									}
								}
	
								$query = "	SELECT sum(jt) jt, count(*) kpl
											FROM tilausrivi use index(yhtio_tyyppi_tuoteno_varattu)
											WHERE yhtio='$kukarow[yhtio]'
											and tyyppi in ('L','G')
											and tuoteno='$tuoteno'
											and varattu=0
											and jt<>0
											and kpl=0
											and var='J'";
	
								$juresult = mysql_query($query) or pupe_error($query);
								$jurow    = mysql_fetch_array ($juresult);
	
								// jos toimituksen jälkeen oleva määrä on enemmän kun kaikki jt-kpllät miinus tämä tilaus niin riittää kaikille
								if (($varas >= ($jurow["jt"] - $jtrow["jt"]) and $total >= $jtrow["jt"]) or $jtrow["ei_saldoa"] != "") {
									// jos haluttiin toimittaa tämä rivi automaagisesti
									if ($kukarow["extranet"] == "" and $automaaginen!='') {
										echo "<font class='message'>".t("Tuote")." $jtrow[tuoteno] ".t("lisättiin tilaukseen")."!</font><br>";
										$tunnus = $jtrow["tunnus"];
										$loput[$tunnus] = "KAIKKI";
	
										require("tee_jt_tilaus.inc");
									}
									else {
										echo "	<input type='hidden' name='rivitunnus[]' value='$jtrow[tunnus]'>";
										
										if ($kukarow["extranet"] == "") {
											echo "<td><font color='#00FF00'>".t("Riittää kaikille")."!</font></td>";
											echo "<td>$total</td>";
											echo "	<td align='center'>".t("K")."<input type='radio' name='loput[$jtrow[tunnus]]' value='KAIKKI'></td>
													<td align='center'><input type='text' name='kpl[$jtrow[tunnus]]' size='4'></td>
													<td align='center'>".t("P")."<input type='radio' name='loput[$jtrow[tunnus]]' value='POISTA'></td>
													<td align='center'>".t("J")."<input type='radio' name='loput[$jtrow[tunnus]]' value='JATA'></td>
													<td align='center'>".t("M")."<input type='radio' name='loput[$jtrow[tunnus]]' value='MITA'></td>";
										}
										elseif ($kukarow["extranet"] != "") {
											echo "<td><font color='#00FF00'>".t("Voidaan toimittaa")."!</font></td>";
											
											if ((int) $kukarow["kesken"] > 0) {
												echo "	<td align='center'>".t("Toimita")."<input type='radio' name='loput[$jtrow[tunnus]]' value='KAIKKI'></td>";
											}
											else {
												echo "<td>".t("Avaa uusi tilaus jotta voit toimittaa rivin").".</td>";
											}
												
											echo "	<td align='center'>".t("Mitätöi")."<input type='radio' name='loput[$jtrow[tunnus]]' value='MITA'></td>
													<td align='center'>".t("Älä tee mitään")."<input type='radio' name='loput[$jtrow[tunnus]]' value=''></td>";
											
										}
									}
								}
								// jos toimituksen jälkeen oleva määrä on nolla tai enemmän voidaan tämä rivi toimittaa
								elseif ($kukarow["extranet"] == "" and $varas >= 0 and $total >= $jtrow["jt"]) {
									if ($automaaginen == '') {
										echo "<td><font color='#FF4444'>".t("Ei riitä kaikille")."!</font></td>";
										echo "<td>$total</td>";
										echo "	<input type='hidden' name='rivitunnus[]' value='$jtrow[tunnus]'>
												<td align='center'>".t("K")."<input type='radio' name='loput[$jtrow[tunnus]]' value='KAIKKI'></td>
												<td align='center'><input type='text' name='kpl[$jtrow[tunnus]]' size='4'></td>
												<td align='center'>".t("P")."<input type='radio' name='loput[$jtrow[tunnus]]' value='POISTA'></td>
												<td align='center'>".t("J")."<input type='radio' name='loput[$jtrow[tunnus]]' value='JATA'></td>
												<td align='center'>".t("M")."<input type='radio' name='loput[$jtrow[tunnus]]' value='MITA'></td>";
									}
								}
								elseif ($kukarow["extranet"] == "" and $total > 0) {
									if ($automaaginen == '') {
										echo "<td><font color='#00FFFF'>".t("Ei riitä koko riville")."!</font></td>";
										echo "<td>$total</td>";
										echo "	<input type='hidden' name='rivitunnus[]' value='$jtrow[tunnus]'>
												<td align='center'></td>
												<td align='center'><input type='text' name='kpl[$jtrow[tunnus]]' size='4'></td>
												<td align='center'>".t("P")."<input type='radio' name='loput[$jtrow[tunnus]]' value='POISTA'></td>
												<td align='center'>".t("J")."<input type='radio' name='loput[$jtrow[tunnus]]' value='JATA'></td>
												<td align='center'>".t("M")."<input type='radio' name='loput[$jtrow[tunnus]]' value='MITA'></td>";
									}
								}
								// ja muuten ei voida sitten toimittaa ollenkaan
								else {
									if ($automaaginen == '') {										
										echo "<td><font color='#FF7777'>".t("Riviä ei voida toimittaa")."!</font></td>";																														
										echo "<input type='hidden' name='rivitunnus[]' value='$jtrow[tunnus]'>";
										
										if ($kukarow["extranet"] == "") {
											echo "<td>$total</td>";
											echo "	<td align='center'></td>
													<td align='center'></td>
													<td align='center'></td>
													<td align='center'></td>
													<td align='center'>".t("M")."<input type='radio' name='loput[$jtrow[tunnus]]' value='MITA'></td>";
										}
										else {
											echo "	<td align='center'></td>
													<td align='center'>".t("Mitätöi")."<input type='radio' name='loput[$jtrow[tunnus]]' value='MITA'></td>
													<td align='center'>".t("Älä tee mitään")."<input type='radio' name='loput[$jtrow[tunnus]]' value=''></td>";
										}
									}
								}
								
								if ($automaaginen == '') {
									echo "</tr>";
								}																
								
								$jt_rivilaskuri++;							
							}							
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

		$query = "	SELECT *
					FROM varastopaikat
					WHERE yhtio = '$kukarow[yhtio]'";
		$vtresult = mysql_query($query) or pupe_error($query);

		echo "	<form name='valinta' action='$PHP_SELF' method='post'>								
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
				<th>".t("Tyyppi")."</th>
				<td>
					<select name='tyyppi'>
					<option value='T' $selt>".t("Tuotteittain")."</option>
					<option value='A' $sela>".t("Asiakkaittain")."</option>
					<option value='P' $selp>".t("Päivämääräjärjestys")."</option>
					</select>
				</td>
			</tr>";

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

		echo "	<SCRIPT LANGUAGE=JAVASCRIPT>
					function verify(){
						msg = '".t("Haluatko todella toimittaa kaikki selkeät JT-Rivit? Eli tiedätkö nyt aivan varmasti mitä olet tekemässä")."?';
						return confirm(msg);
					}
				</SCRIPT>";
		
		
		$sel = '';
		if ($superit != '') $sel = 'CHECKED';
		
		echo "	<tr> 
				<th>".t("Näytä vain suoratoimitusrivit")."</th> 
				<td><input type='checkbox' name='superit' $sel></td><td class='back'>".t("Älä toimita suoratoimituksia, ellet ole 100% varma että voit niin tehdä")."!</td> 
				</tr>"; 		
		
		$sel = '';
		if ($automaaginen != '') $sel = 'CHECKED';
		
		echo "	<tr>
				<th>".t("Toimita selkeät jt-rivit automaagisesti")."</th>
				<td><input type='checkbox' name='automaaginen' $sel onClick = 'return verify()'></td>
			</tr>";

		echo "<tr>
				<td class='back'></td>
				<td class='back'><input type='submit' value='".t("Näytä")."'></td>
			</tr>
			</table>
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
