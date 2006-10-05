<?php

	require("../inc/parametrit.inc");

	echo "<font class='head'>".t("Ennakkotilausrivit")."</font><hr>";
	
	if ($tee == 'TOIMITA') {
		$debug  = 1;
		$query  = "select * from lasku where yhtio='$kukarow[yhtio]' and laatija='$kukarow[kuka]' and alatila='E' and tila='N'";
		$jtrest = mysql_query($query) or pupe_error($query);

		while ($laskurow = mysql_fetch_array($jtrest)) {
			$query  = "UPDATE lasku SET tila='N', alatila='A' WHERE yhtio='$kukarow[yhtio]' and tunnus='$laskurow[tunnus]'";
			$apure  = mysql_query($query) or pupe_error($query);

			// tarvitaan $kukarow[yhtio], $kukarow[kesken], $laskurow ja $yhtiorow
			$kukarow["kesken"] = $laskurow["tunnus"];
			require("tilaus-valmis.inc");
		}

		$tee = '';
	}
		
	if ($tee == 'POIMI') {
		foreach($rivitunnus as $tunnus) {
			if ($kpl[$tunnus] > 0 or $loput[$tunnus] != '') {
				require ('tee_ennakko_tilaus.inc');
			}
		}
		$tee = 'JATKA';
	}
		
	$query = "	SELECT *
				FROM lasku
				WHERE yhtio = '$kukarow[yhtio]' and laatija='$kukarow[kuka]' and alatila='E' and tila = 'N'";
	$stresult = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($stresult) > 0) {
		echo "	<form name='valinta' action='$PHP_SELF' method='post'>
				<input type='hidden' name='tee' value='TOIMITA'>								
				<font class='error'>".t("HUOM! Sinulla on toimittamattomia ennakkorivejä")."</font><br>
				<table>
				<tr>
				<td>".t("Toimita poimitut ennakko-rivit").": </td>
				<td><input type='submit' value='".t("Toimita")."'></td>
				</tr>
				</table>
				</form><hr>";
	}
			
	//muokataan tilausriviä
	if ($tee == 'MUOKKAARIVI') {
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
		$kpl 			= $trow["varattu"];
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
	if ($tee == 'LISAARIVI') {
			
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
		
	if ($tee == "" or $tee == "JATKA") {
				
		if (isset($muutparametrit)) {
			list($tuotenumero,$tyyppi,$toimi,$automaaginen,$ytunnus,$asiakasno,$toimittaja) = explode('#', $muutparametrit);
		
			$varastot = explode('##', $tilausnumero);
		
			foreach ($varastot as $vara) {
				$varastosta[$vara] = $vara;
			}		
		}
		
		$muutparametrit = $tuotenumero."#".$tyyppi."#".$toimi."#".$automaaginen."#".$ytunnus."#".$asiakasno."#";				
		
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
		
		$muutparametrit = $tuotenumero."#".$tyyppi."#".$toimi."#".$automaaginen."#".$ytunnus."#".$asiakasno."#";
		
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
		if ($asiakasno != '') {
			$aslisa = " and lasku.ytunnus='$asiakasno' ";
		}
		if ($tuotenumero != '') {
			$tuotlisa = " and tilausrivi.tuoteno='$tuotenumero' ";
		}
		
	
		$query = "";
		
		if ($tyyppi == 'A') {
			$order = " ORDER BY lasku.ytunnus, tilausrivi.tuoteno ";
		}
	
		if ($tyyppi == 'T') {
			$order = " ORDER BY tilausrivi.tuoteno, lasku.ytunnus ";
		}
	
		if ($tyyppi == 'P') {
			$order = " ORDER BY lasku.luontiaika, tilausrivi.tuoteno, lasku.ytunnus ";
		}
	
		if (($tyyppi == 'A') or ($tyyppi == 'T') or ($tyyppi == 'P')) {
			//haetaan vain tuoteperheiden isät tai sellaset tuotteet jotka eivät kuulu tuoteperheisiin
			$query = "	SELECT distinct otunnus, if(perheid!=0, concat('JOPERHE',tilausrivi.perheid), concat('EIPERHE',tilausrivi.tunnus)) perheid
						FROM tilausrivi use index (yhtio_tyyppi_laskutettuaika) 
						JOIN lasku use index (PRIMARY) ON lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus and lasku.tila='E' and lasku.alatila='A'
						JOIN tuote use index (tuoteno_index) ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
						$tolisa1
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and tilausrivi.tyyppi = 'E'
						and tilausrivi.laskutettuaika = '0000-00-00'
						and tilausrivi.varattu > 0
						and (tilausrivi.tunnus = tilausrivi.perheid or tilausrivi.perheid=0)
						$tolisa2
						$aslisa
						$tuotlisa
						$order";
			$isaresult = mysql_query($query) or pupe_error($query);
			
			if (mysql_num_rows($isaresult) > 0) {
				if ($automaaginen == '') {
					echo "<table>";
					echo "<tr>";				
					echo "<th>".t("Tuoteno")."</th>";
					echo "<th>".t("Ytunnus")."</th>";
					echo "<th>".t("Nimi")."</th>";
					
					if ($kukarow["resoluutio"] == 'I') {
						echo "<th>".t("Toim_Nimi")."</th>";
						echo "<th>".t("Viesti.")."</th>";
					}
					
					echo "<th>".t("Kpl")."</th>";						
					echo "<th>".t("Status")."</th>";
					echo "<th>".t("Saldo")."</th>";
					echo "<th>".t("Toim. kaikki")."</th>";
					echo "<th>".t("Toim. Kpl")."</th>";
					echo "<th>".t("Poista lop.")."</th>";
					echo "<th>".t("Jätä lop.")."</th>";
					echo "<th>".t("Mitätöi")."</th>";
					echo "</tr>";
					
					echo "<form action='$PHP_SELF' method='post'>";
					echo "<input type='hidden' name='tee' value='POIMI'>";
					echo "<input type='hidden' name='tyyppi' value='$tyyppi'>";
					echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
					echo "<input type='hidden' name='asiakasid' value='$asiakasid'>";
					echo "<input type='hidden' name='asiakasno' value='$asiakasno'>";
					echo "<input type='hidden' name='toimittaja' value='$toimittaja'>";
					echo "<input type='hidden' name='toimi' value='$toimi'>";
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
				
				while ($isarow = mysql_fetch_array($isaresult)) {
					
					$lirt = "";
					
					if (substr($isarow["perheid"],0,7) == "JOPERHE") {
						$lirt = " and tilausrivi.perheid = '".substr($isarow["perheid"],7)."' ";
					}
					elseif(substr($isarow["perheid"],0,7) == "EIPERHE") {
						$lirt = " and tilausrivi.tunnus = '".substr($isarow["perheid"],7)."' ";
					}
					
					$query = "	SELECT tilausrivi.tuoteno, lasku.ytunnus, lasku.nimi, lasku.toim_nimi, lasku.viesti, tilausrivi.varattu, 
								lasku.tunnus ltunnus, tilausrivi.tunnus tunnus, tuote.ei_saldoa, tilausrivi.perheid, tilausrivi.otunnus
								FROM tilausrivi use index (yhtio_otunnus)
								JOIN lasku use index (PRIMARY) ON lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus and lasku.tila = 'E'	and lasku.alatila = 'A'
								JOIN tuote use index (tuoteno_index) ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
								WHERE tilausrivi.yhtio='$kukarow[yhtio]'
								and tilausrivi.otunnus = '$isarow[otunnus]'
								and tilausrivi.tyyppi = 'E'
								and tilausrivi.varattu > 0 
								$lirt
								$aslisa
								ORDER BY tilausrivi.tunnus";
					$etsresult2 = mysql_query($query) or pupe_error($query);
					
					while ($jtrow = mysql_fetch_array($etsresult2)) {
					
						// haetaan tuotteelle saldo
						$akeraysaika = date("Y-m-d");
						$tuoteno     = $jtrow["tuoteno"];
						$atil        = $jtrow["varattu"];
						unset($trow); // näin saadaan tuotetiedot haettua uudestaan
		
						
						if ($jtrow["ei_saldoa"] == "") {
							require("tarkistasaldo.inc");
			
							// varas muuttujassa on saldo tämän rivin toimituksen jälkeen
							// total on todellinen saldo
							$total = 0;
							$varas = 0;
							$onkomillaan = 0;
			
							for ($i=1; $i <= count($hyllyalue); $i++) {
			
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
							}
						}
						
						// saldo riittää tai halutaan nähdä kaikki rivit
						if ($total > 0 or $toimi == '' or $jtrow["ei_saldoa"] != "") {
							if ($automaaginen == '') {
								
	
								echo "<tr>";							
								$ins = "";
								$isa = "";
								if ($jtrow["tunnus"] != $jtrow["perheid"] and $jtrow["perheid"] != 0) {
									$ins = "-->";
								}
								if ($jtrow["tunnus"] == $jtrow["perheid"] and $jtrow["perheid"] != 0) {
									$ins = "<->";
								}
											
								echo "<td nowrap>$ins <a href='../tuote.php?tee=Z&tuoteno=$jtrow[tuoteno]'>$jtrow[tuoteno]</a></td>";
								echo "<td>$jtrow[ytunnus]</td>";
								echo "<td><a href='../tuote.php?tee=NAYTATILAUS&tunnus=$jtrow[ltunnus]'>$jtrow[nimi]</a></td>";
								
								if ($kukarow["resoluutio"] == 'I') {
									echo "<td>$jtrow[toim_nimi]</td>";
									echo "<td>$jtrow[viesti]</td>";
								}
																																																													
								echo "<td><a href='$PHP_SELF?tee=MUOKKAARIVI&rivitunnus=$jtrow[tunnus]&toimittajaid=$toimittajaid&asiakasid=$asiakasid&asiakasno=$asiakasno&toimittaja=$toimittaja&toimi=$toimi&tuotenumero=$tuotenumero&tyyppi=$tyyppi&tilausnumero=$tilausnumero'>$jtrow[varattu]</a></td>";																			
							}
		
							$query = "	SELECT sum(varattu) varattu, count(*) kpl
										FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
										WHERE yhtio = '$kukarow[yhtio]' 
										and tyyppi = 'E'
										and tuoteno = '$tuoteno' 
										and varattu > 0";	
							$juresult = mysql_query($query) or pupe_error($query);
							$jurow    = mysql_fetch_array ($juresult);
		
							// jos toimituksen jälkeen oleva määrä on enemmän kuin kaikki jt-kpllät miinus tämä tilaus niin riittää kaikille
							if (($varas >= ($jurow["varattu"] - $jtrow["varattu"]) and $total >= $jtrow["varattu"]) or $jtrow["ei_saldoa"] != "") {
								// jos haluttiin toimittaa tämä rivi automaagisesti
								if ($automaaginen!='') {
									echo "<font class='message'>".t("Tuote")." $tuoteno ".t("lisättiin tilaukseen")."!</font><br>";
									$tunnus = $jtrow["tunnus"];
									$loput[$tunnus] = "KAIKKI";
		
									require("tee_ennakko_tilaus.inc");
								}
								else {
									echo "<td><font color='#00FF00'>".t("Riittää kaikille")."!</font></td>";
									echo "<td>$total</td>";
									echo "	<input type='hidden' name='rivitunnus[]' value='$jtrow[tunnus]'>
											<td align='center'>".t("K")."<input type='radio' name='loput[$jtrow[tunnus]]' value='KAIKKI'></td>
											<td align='center'><input type='text' name='kpl[$jtrow[tunnus]]' size='4'></td>
											<td align='center'>".t("P")."<input type='radio' name='loput[$jtrow[tunnus]]' value='POISTA'></td>
											<td align='center'>".t("J")."<input type='radio' name='loput[$jtrow[tunnus]]' value='JATA'></td>
											<td align='center'>".t("M")."<input type='radio' name='loput[$jtrow[tunnus]]' value='MITA'></td>";
								}
							}
							// jos toimituksen jälkeen oleva määrä on nolla tai enemmän voidaan tämä rivi toimittaa
							elseif ($varas >= 0 and $total >= $jtrow["varattu"]) {
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
							elseif ($total > 0) {
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
									echo "<td>".t("Riviä ei voida toimittaa")."!</td>";
									echo "<td>$total</td>";
									echo "	<input type='hidden' name='rivitunnus[]' value='$jtrow[tunnus]'>
											<td align='center'></td>
											<td align='center'></td>
											<td align='center'></td>
											<td align='center'></td>
											<td align='center'>".t("M")."<input type='radio' name='loput[$jtrow[tunnus]]' value='MITA'></td>";
								}
							}
							if ($automaaginen == '') {
								echo "</tr>";
							}
						}
					}
				}
				if ($automaaginen == '') {
					echo "<tr>
							<td colspan='8' class='back'></td>
							<td colspan='3' class='back' align='right'><input type='submit' value='".t("Poimi")."'></td></tr>";
					echo "</table>";
					echo "</form><hr><br>";
				}
			}
			else {
				echo t("Yhtään riviä ei löytynyt")."!<br>";
			}
			$tee = '';
		}
	}

	


	if ($tee == '') {

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
						msg = '".t("Haluatko todella toimittaa kaikki selkeät ennakkorivit? Eli tiedätkö nyt aivan varmasti mitä olet tekemässä")."?';
						return confirm(msg);
					}
				</SCRIPT>";

		$sel = '';
		if ($automaaginen != '') $sel = 'CHECKED';
		
		echo "	<tr>
				<th>".t("Toimita selkeät ennakkorivit automaagisesti")."</th>
				<td><input type='checkbox' name='automaaginen' $sel onClick = 'return verify()'></td>
				</tr>";

		echo "<tr>
				<td class='back'></td>
				<td class='back'><input type='submit' value='".t("Näytä")."'></td>
			</tr>
			</table>
			</form>";		
	}
	require("../inc/footer.inc");
?>
