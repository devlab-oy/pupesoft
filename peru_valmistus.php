<?php

	require ("inc/parametrit.inc");

	echo "<font class='head'>Peru valmistus:</font><hr>";
	
	if ($id != 0) {
		$query = "	select * 
					from lasku 
					where yhtio	 = '$kukarow[yhtio]'
					and tila	 = 'V'
					and alatila  in ('V','C')
					and tunnus	 = '$id'";
		$res = mysql_query($query) or pupe_error($query);
				
		if (mysql_num_rows($res) != 1) {
			echo "Valmistusta ei löydy!";
			exit;
		}
		$laskurow = mysql_fetch_array($res);
									
		// Laskun tiedot		
		echo "Laskun tiedot";
		echo "<table>";
		echo "<tr><th>Tunnus:</th>	<td>$laskurow[tunnus]</td></tr>";
		echo "<tr><th>Tila:</th>	<td>$laskurow[tila]</td></tr>";
		echo "<tr><th>Alatila:</th>	<td>$laskurow[alatila]</td></tr>";
		echo "<tr><th>Nimi:</th>	<td>$laskurow[nimi]</td></tr>";
		echo "<tr><th>Viite:</th>	<td>$laskurow[viesti]</td></tr>";
		echo "</table><br><br>";
		
	
		// Näytetään tilausrivit		
		$query = "	select * 
					from tilausrivi
					where yhtio = '$kukarow[yhtio]' 
					and otunnus = '$laskurow[tunnus]'
					and tyyppi in ('W','V')
					order by perheid, tunnus";
		$res = mysql_query($query) or pupe_error($query);
		
		echo "Tilausrivit, Tuote ja Tapahtumat:<br>";
		echo "<table>";
		echo "<form action='$PHP_SELF' method=POST'>";
		echo "<input type='hidden' name='id'  value='$laskurow[tunnus]'>";				
		
		while ($rivirow = mysql_fetch_array($res)) {
			
			if ($edtuote != $rivirow["perheid"]) {
				echo "<tr><td colspan='3' class='back'><hr></td></tr>";
			}												
			$edtuote = $rivirow["perheid"];					
			
			if ($rivirow["perheid"] == $rivirow["tunnus"]) {
				
				if ($tee != 'KORJAA') {
					echo "<tr><td colspan='3'><input type='checkbox' name='perutaan[$rivirow[tunnus]]' value='$rivirow[tunnus]'> Peru tuotteen valmistus</td></tr>";
				}
				
				//Flägätään voidaanko isä poistaa
				$isa_voidaankopoistaa = "";
				
				//Haetaan valmistetut tuotteet
				$query = "	select * 
							from tilausrivi
							where yhtio = '$kukarow[yhtio]' 
							and otunnus = '$laskurow[tunnus]'
							and perheid = '$rivirow[perheid]'
							and tyyppi in ('L','D') order by tunnus desc";
				$valm_res = mysql_query($query) or pupe_error($query);
				
				$valmkpl = 0;
				
				while($valm_row = mysql_fetch_array($valm_res)) {
					$valmkpl += $valm_row["kpl"];	
					
					if ($valm_row["tyyppi"] == "L" and ($valm_row["laskutettu"] != "" or $valm_row["laskutettuaika"] != "0000-00-00")) {
						$isa_voidaankopoistaa = "EI";
					}																	
				}
			}
			else {
				$valmkpl = 	$rivirow["kpl"];
			}
			
			$query = "	select * 
						from tuote 
						where yhtio = '$kukarow[yhtio]' 
						and tuoteno = '$rivirow[tuoteno]'";
			$tuoteres = mysql_query($query) or pupe_error($query);
			$tuoterow = mysql_fetch_array($tuoteres);

			if ($tee != 'KORJAA') {
				echo "<tr>";
				
				echo "<td class='back' valign='top'><table>";
				echo "<tr><th>Tuoteno:</th>			<td>$rivirow[tuoteno]</td></tr>";
				echo "<tr><th>Tilattu:</th>			<td>$rivirow[tilkpl]</td></tr>";
				echo "<tr><th>Varattu:</th>			<td>$rivirow[varattu]</td></tr>";
				echo "<tr><th>Valmistettu:</th>		<td>$valmkpl</td></tr>";
				echo "<tr><th>Valmistettu:</th>		<td>$rivirow[toimitettuaika]</td></tr>";
				
				if ($rivirow["tyyppi"] == "W" or $rivirow["tyyppi"] == "L") {
					echo "<tr><th>Kehahinta:</th>	<td>$tuoterow[kehahin]</td></tr>";
					echo "<tr><th>Otunnus:</th>		<td>$rivirow[otunnus]</td></tr>";
				}
				
				echo "</table></td>";
			}
									
			$query = "	select count(*) kpl
						from tapahtuma 
						where yhtio = '$kukarow[yhtio]' 
						and laji = 'valmistus'
						and rivitunnus = '$rivirow[tunnus]'";
			$countres = mysql_query($query) or pupe_error($query);
			$countrow = mysql_fetch_array($countres);
			
			//Montako kertaa tämä rivi on viety varastoon
			$vientikerrat = $countrow["kpl"];			
			
			// Tässä toi DESC sana ja LIMIT 1 ovat erityisen tärkeitä
			$order = "";
			if ($tee == 'KORJAA') {
				$order = "DESC LIMIT 1";
				
				//Tässä aloitetaan ykkösestä jotta voimme poistaa vientikerran jos niitä on monta vaikka meillä on order by desc ja se joka poistetaan tulee ekana järjestyksessä
				$vientikerta = 1;
			}
			else {
				//Normaalisti eka vientikerta saa indeksin nolla
				$vientikerta = 0;
			}
			
			//Haetaan tapahtuman tiedot
			$query = "	select * 
						from tapahtuma 
						where yhtio = '$kukarow[yhtio]' 
						and laji in ('valmistus','kulutus')
						and rivitunnus = '$rivirow[tunnus]'
						order by tunnus $order";
			$tapares = mysql_query($query) or pupe_error($query);
												
			while($taparow = mysql_fetch_array($tapares)) {																				
				
				if ($tee != 'KORJAA') {
					echo "<td class='back' valign='top'><table>";
					echo "<tr><th>Tuoteno:</th><td>$taparow[tuoteno]</td></tr>";
					echo "<tr><th>Kpl:</th><td>$taparow[kpl]</td></tr>";
					echo "<tr><th>Laatija Laadittu:</th><td>$taparow[laatija] $taparow[laadittu]</td></tr>";
					echo "<tr><th>Selite:</th><td>$taparow[selite]</td></tr>";
					
					if ($rivirow["tyyppi"] == "W" or $rivirow["tyyppi"] == "L") {	
						echo "<tr><th>Kehahinta:</th><td>$taparow[hinta]</td></tr>";								
					}
				}
							
				// Tutkitaan onko tätä tuotetta runkslattu tän tulon jälkeen
				$query = "	select count(*) kpl
							from tapahtuma 
							where yhtio = '$kukarow[yhtio]' 
							and laji in ('laskutus','inventointi','epäkurantti','valmistus')
							and tuoteno = '$taparow[tuoteno]'
							and tunnus > '$taparow[tunnus]'
							and rivitunnus != '$taparow[rivitunnus]'";
				$tapares2 = mysql_query($query) or pupe_error($query);
				$taparow2 = mysql_fetch_array($tapares2);	
														
				if ($rivirow["tyyppi"] == "W" or $rivirow["tyyppi"] == "L") {				
					$voidaankopoistaa = "";
					
					if ($isa_voidaankopoistaa == "EI") {
						$voidaankopoistaa = "Ei";
					}
					elseif ($taparow2["kpl"] > 0) {
						$voidaankopoistaa = "Ei";	
					}
					elseif($taparow2["kpl"] == 0) {
						$voidaankopoistaa = "Kyllä";
					}
					else {
						$voidaankopoistaa = "Ei";
					}															
				}
				
				if ($tee != 'KORJAA' and ($rivirow["tyyppi"] == "W" or $rivirow["tyyppi"] == "L")) {
					echo "<tr><th>Voidaankopoistaa:</th><td>$voidaankopoistaa</td></tr>";
				}
																																																						
				$query = "	select * 
							from tapahtuma 
							where yhtio = '$kukarow[yhtio]' 
							and laji in ('tulo','valmistus')
							and tuoteno = '$taparow[tuoteno]'
							and tunnus < '$taparow[tunnus]'
							ORDER BY tunnus DESC
							LIMIT 1";
				$tapares3 = mysql_query($query) or pupe_error($query);
				
				if (mysql_num_rows($tapares3) > 0) {				
					$taparow3 = mysql_fetch_array($tapares3);
				}
				else {
					$query = "	select * 
								from tapahtuma 
								where yhtio = '$kukarow[yhtio]' 
								and laji in ('laskutus')
								and tuoteno = '$taparow[tuoteno]'
								and tunnus < '$taparow[tunnus]'
								ORDER BY tunnus DESC
								LIMIT 1";
					$tapares3 = mysql_query($query) or pupe_error($query);
					$taparow3 = mysql_fetch_array($tapares3);
				}
				
				if ($tee != 'KORJAA') {
					
					if ($rivirow["tyyppi"] == "W" or $rivirow["tyyppi"] == "L") {
						echo "<tr><th>Kehahinta from --> to:</th><td>$taparow[hinta] --> $taparow3[hinta]</td></tr>";
					}
					echo "</table></td>";
				}
				
				if ($tee == 'KORJAA' and ($voidaankopoistaa == "Kyllä" or $vakisinpoista == 'Kyllä') and $perutaan[$rivirow["perheid"]] == $rivirow["perheid"] and $perutaan[$rivirow["perheid"]] != 0) {
															
					//Poistetaan tapahtuma			
					$query = "	delete from tapahtuma							
								where yhtio	  = '$kukarow[yhtio]' 
								and tunnus	  = '$taparow[tunnus]'";
					$korjres = mysql_query($query) or pupe_error($query);
					
					echo "$rivirow[tuoteno]: Poistetaan tapahtuma!<br>";			
					
																								
					//Haetaan valmistetut tuotteet ja poistetaan informativinen valmistusrivi
					if ($perutaan[$rivirow["tunnus"]] == $rivirow["tunnus"] and $perutaan[$rivirow["tunnus"]] != 0) {
						$query = "	select * 
									from tilausrivi
									where yhtio = '$kukarow[yhtio]' 
									and otunnus = '$laskurow[tunnus]'
									and perheid = '$rivirow[perheid]'
									and tyyppi in ('L','D')
									order by tunnus $order";
						$valm_res = mysql_query($query) or pupe_error($query);
											
						while($valm_row = mysql_fetch_array($valm_res)) {
							//Poistetaan tapahtuma			
							$query = "	delete from tilausrivi							
										where yhtio	 = '$kukarow[yhtio]' 
										and otunnus	 = '$laskurow[tunnus]'
										and tunnus	 = '$valm_row[tunnus]'";
							$korjres = mysql_query($query) or pupe_error($query);
							
							echo "$valm_row[tuoteno]: Poistetaan informatiivinen valmistusrivi!<br>";																																		
						}
					}
					
					//Laitetaan valmistetut kappaleet takaisin valmistukseen
					if($perutaan[$rivirow["tunnus"]] == $rivirow["tunnus"]) {
						$palkpl = $taparow["kpl"];
					}
					else {
						$palkpl = $taparow["kpl"] * -1;
					}
					
					$query = "	update tilausrivi 
								set varattu = varattu+$palkpl,
								kpl=kpl-$palkpl
								where yhtio = '$kukarow[yhtio]'
								and otunnus = '$laskurow[tunnus]'
								and tunnus  = '$rivirow[tunnus]'";
					$korjres = mysql_query($query) or pupe_error($query);
					
					echo "$rivirow[tuoteno]: Palautetaan $palkpl kappaletta takaisin valmistukseen!<br>";	
					
					
					// Laitetaan takas saldoille
					if ($tuoterow["ei_saldoa"] == "") {
						$query = "	update tuotepaikat
									set saldo 		= saldo - $taparow[kpl],
									saldoaika   	= now()	
									where yhtio	  	  = '$kukarow[yhtio]' 
									and tuoteno	  	  = '$rivirow[tuoteno]'
									and hyllyalue 	  = '$rivirow[hyllyalue]'
									and hyllynro	  = '$rivirow[hyllynro]'
									and hyllyvali	  = '$rivirow[hyllyvali]'
									and hyllytaso	  = '$rivirow[hyllytaso]'";
						$korjres = mysql_query($query) or pupe_error($query);
																		
						if (mysql_affected_rows() == 0) {
							echo "<font class='error'>".t("Varastopaikka")." $rivirow[hyllyalue]-$rivirow[hyllynro]-$rivirow[hyllytaso]-$rivirow[hyllyvali] ".t("ei löydy vaikka se on syötetty tilausriville! Koitetaan päivittää oletustapaikkaa!")."</font>";
							
							$query = "	update tuotepaikat
										set saldo = saldo - $taparow[kpl],
										saldoaika   = now()	
										where yhtio = '$kukarow[yhtio]' and 
										tuoteno     = '$rivirow[tuoteno]' and 
										oletus     != '' 
										limit 1";
							$korjres = mysql_query($query) or pupe_error($query);
							
							if (mysql_affected_rows() == 0) {
								
								echo "<br><font class='error'>".t("Tuotteella")." $rivirow[tuoteno] ".t("ei ole oletuspaikkaakaan")."!!!</font><br><br>";
						
								// haetaan firman eka varasto, tökätään kama sinne ja tehdään siitä oletuspaikka
								$query = "select alkuhyllyalue, alkuhyllynro from varastopaikat where yhtio='$kukarow[yhtio]' order by alkuhyllyalue, alkuhyllynro limit 1";
								$korjres = mysql_query($query) or pupe_error($query);
								$hyllyrow =  mysql_fetch_array($korjres);
				
								echo "<br><font class='error'>".t("Tehtiin oletuspaikka")." $rivirow[tuoteno]: $rivirow[alkuhyllyalue] $rivirow[alkuhyllynro] 0 0</font><br><br>";
				
								// lisätään paikka
								$query = "	INSERT INTO tuotepaikat set
											yhtio		= '$kukarow[yhtio]',
											tuoteno     = '$rivirow[tuoteno]',
											oletus      = 'X',
											saldo       = '$taparow[kpl]',
											saldoaika   = now(),
											hyllyalue   = '$hyllyrow[alkuhyllyalue]',
											hyllynro    = '$hyllyrow[alkuhyllynro]',
											hyllytaso   = '0',
											hyllyvali   = '0'";
								$korjres = mysql_query($query) or pupe_error($query);
				
								// tehdään tapahtuma
								$query = "	INSERT into tapahtuma set
											yhtio 		= '$kukarow[yhtio]',
											tuoteno 	= '$taparow[tuoteno]',
											kpl 		= '0',
											kplhinta	= '0',
											hinta 		= '0',
											laji 		= 'uusipaikka',
											selite 		= '".t("Lisättiin tuotepaikka")." $hyllyrow[alkuhyllyalue] $hyllyrow[alkuhyllynro] 0 0',
											laatija 	= '$kukarow[kuka]',
											laadittu 	= now()";
								$korjres = mysql_query($query) or pupe_error($query);
							}
							else {
								echo "<font class='error'>".t("Huh, se onnistui!")."</font><br>";
							}								
						}
					
						echo "$rivirow[tuoteno]: Päivitetään saldo ($taparow[kpl]) pois tuotepaikalta!<br>";
					}
										
					if ($rivirow["tyyppi"] == "W" or $rivirow["tyyppi"] == "L") {
						//Päivitetään tuotteen kehahinta
						$query = "	UPDATE tuote
									SET kehahin   = '$taparow3[hinta]'
									where yhtio	  = '$kukarow[yhtio]' 
									and tuoteno	  = '$rivirow[tuoteno]'";
						$korjres = mysql_query($query) or pupe_error($query);
						
						echo "$rivirow[tuoteno]: Päivitetään tuotteen keskihankintahinta ($taparow[hinta] --> $taparow3[hinta]) takaisin edelliseen arvoon!<br>";
					}
					

					//Päivitetään rivit takaisin keskeneräiseksi ja siivotaan samalla hieman pyöristyseroja jotka on tapahtunut perumisprosessissa
					$query = "	update tilausrivi 
								set toimitettu = '', toimitettuaika='0000-00-00 00:00:00'
								where yhtio = '$kukarow[yhtio]'
								and tunnus = '$rivirow[tunnus]'";
					$korjres = mysql_query($query) or pupe_error($query);
					
					echo "$rivirow[tuoteno]: Päivitetään tilausrivi takaisin tilaan ennen valmistusta<br>";
										
					echo "<br><br>";
				}
				$vientikerta++;
			}
			
			if ($tee != 'KORJAA') {
				echo "</tr>";
				echo "<tr><td colspan='2' class='back'><br></td></tr>";									
			}
		}
		echo "</table><br><br>";
		
		
		if ($tee == 'KORJAA') {
			$query = "	SELECT tunnus
						FROM tilausrivi
						WHERE yhtio	= '$kukarow[yhtio]' 
						and otunnus = $laskurow[tunnus]
						and toimitettuaika = '0000-00-00 00:00:00'";
			$chkresult1 = mysql_query($query) or pupe_error($query);
			
			//Eli tilaus on osittain valmistamaton
			if (mysql_num_rows($chkresult1) != 0) {															
				//Jos kyseessä on varastovalmistus
				$query = "	UPDATE lasku
							SET tila = 'V', alatila	= 'C'
							WHERE yhtio = '$kukarow[yhtio]' 
							and tunnus  = $laskurow[tunnus]";															
				$chkresult2 = mysql_query($query) or pupe_error($query);	
				
				echo "Päivitetään valmistus takaisin avoimeksi!<br>";	
			}
		}
		
		
		if ($tee != 'KORJAA') {
		
			echo "<input type='hidden' name='tee' value='KORJAA'>";
		
		
			echo "Väkisinkorjaa vaikka korjaaminen ei olisi järjevää: <input type='checkbox' name='vakisinpoista' value='Kyllä'><br><br>";
			
		
			echo t("Näyttää hyvältä ja haluan korjata").":<br><br>";
			echo "<input type='submit' value='Korjaa'><br><br>";
			
			echo "<font class='error'>HUOM: Poistaa vain yhden varastoonviennin per tuote per korjausajo!</font>";
			
		}
		else {
			echo "<a href='$PHP_SELF?id=$id'>".t("Näytää keikka uudestaan")."</a>";
		}
		
		echo "</form>";
	}
	
	if ($id == '') {
		echo "<br><table>";
		echo "<tr>";
		echo "<form action = '$PHP_SELF' method = 'post'>";
		echo "<th>".t("Syöta valmistuksen numero")."</th>";
		echo "<td><input type='text' size='30' name='id'></td>";				
		echo "<td><input type='submit' value='".t("Jatka")."'></td></form></tr>";
		echo "</table>";
	}
	
	
	require("inc/footer.inc");
?>