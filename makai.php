<?php
	
	if (isset($_POST["tee"])) {
		if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
		if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
	}
		
	require("inc/parametrit.inc");
	
	if (isset($tee)) {	
		if ($tee == "lataa_tiedosto") {
			readfile("dataout/".$filenimi);
			exit;
		}
	}
	else {
		
		if (strtoupper($yhtiorow['maa']) == 'FI') {
			$kotimaa = "FI";
		}
		elseif (strtoupper($yhtiorow['maa']) == 'SE') {
			$kotimaa = "SE";
		}
		if (strtoupper($yhtiorow['maa']) != 'FI' and strtoupper($yhtiorow['maa']) != 'SE') {
			echo "<font class='error'>".t("Yrityksen maa ei ole sallittu")." (FI, SE) '$yhtiorow[maa]'</font><br>";
			exit;
		}
	
		if ($kotimaa == "FI") {
			echo "<font class='head'>LM03-maksuaineisto</font><hr>";
		}
		else {
			echo "<font class='head'>Betalningsuppdrang via Bankgirot - Inrikesbetalningar</font><hr>";
		}
		
		if ($kotimaa == "FI") {
			// Tarkistetaan yrityksen pankkitilien oikeellisuudet
			$query = "SELECT tilino, nimi, tunnus, asiakastunnus
					  FROM yriti
					  WHERE yhtio ='$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);
	
			//Haetaan funktio joka tuo pankin tietoja
			require_once ("inc/pankkitiedot.inc");			
			$pankkitiedot = array();

			while ($row = mysql_fetch_array($result)) {

				if (substr($row["tilino"], 0, 1) >= '0' and substr($row["tilino"], 0, 1) <= '9') {

					$pankkitili = $row["tilino"];
					
					require("inc/pankkitilinoikeellisuus.php");

					if ($pankkitili == "") {
						echo "<font class='error'>Pankkitili $row[nimi], '$row[tilino]' on virheellinen</font><br>";
						exit;
					}
					elseif ($row["tilino"] != $pankkitili) {
						$query = "UPDATE yriti SET tilino = '$pankkitili' WHERE tunnus = $row[tunnus]";
						$xresult = mysql_query($query) or pupe_error($query);
						
						echo "P‰ivitin tilin $row[nimi]<br><br>";
					}
										
					//Haetaan tilinumeron perusteella pankin tiedot
					$pankkitiedot[$pankkitili] = pankkitiedot($pankkitili, $row["asiakastunnus"]);	
				}
			}
		}
				
		// --- LM03/Eli kotimaan maksujen aineisto
		
		//Tutkitaan onko kotimaan aineistossa monta maksup‰iv‰‰?
		$query = "	SELECT distinct(olmapvm)
					FROM lasku
					WHERE yhtio 	= '$kukarow[yhtio]' 
					and tila 		= 'P' 
					and maa			= '$kotimaa' 
					and maksaja		= '$kukarow[kuka]'
					ORDER BY 1";
		$pvmresult = mysql_query($query) or pupe_error($query);
	
		if (mysql_num_rows($pvmresult) != 0) {
		
			$generaatio = 1;
			
			//P‰‰tet‰‰m maksuaineston tiedostonimi
			if ($kotimaa == "FI") {
				$kaunisnimi = "lm03-$kukarow[yhtio]-" . date("d.m.y.H.i.s") . "-". $generaatio . ".txt";
			}
			else {
				$kaunisnimi = "bg-$kukarow[yhtio]-" . date("d.m.y.H.i.s") . "-" . $generaatio . ".txt";	
			}
			
			$toot = fopen("dataout/".$kaunisnimi,"w+");
		
			if (!$toot) {
				echo t("En saanut tiedostoa auki! Tarkista polku")." dataout/$kaunisnimi !";
				exit;
			}
			
			echo "<table>";
			echo "<tr><th>".t("Kotimaan maksujen tiedoston nimi on").":</th><td>$kaunisnimi</td></tr>";
			echo "<tr><td class='back'><br></td></tr>";
		}
		else {
			echo "<font class='message'>".t("Sopivia laskuja ei lˆydy")."</font>";
		}
		
		$totkpl 	= 0;
		$totsumma	= 0;
		
		while ($pvmrow = mysql_fetch_array($pvmresult)) {			
			echo "<tr><th>".t("Maksup‰iv‰").":</th><td>".tv1dateconv($pvmrow[0])."</td></tr>";
			
			$makskpl 	 = 0;
			$makssumma 	 = 0;
			$tilinoarray = array();

			//Tutkitaan onko kotimaan aineistossa hyvityslaskuja
			$query = "	SELECT maksu_tili, tilinumero, nimi
						FROM lasku
						WHERE yhtio  = '$kukarow[yhtio]' 
						and tila 	 = 'P' 
						and maa		 = '$kotimaa' 
						and summa 	 < 0 
						and maksaja  = '$kukarow[kuka]' 
						and olmapvm  = '$pvmrow[olmapvm]'
						GROUP BY maksu_tili, tilinumero";
			$result = mysql_query($query) or pupe_error($query);
	
			//Lˆytyykˆ hyvityksi‰?
			if (mysql_num_rows($result) != 0) {
				echo "<tr><th colspan='2'>".t("Tarkistan hyvityslaskut!")."</th></tr>";
			
				while ($laskurow = mysql_fetch_array ($result)) {
					$query = "	SELECT *
								FROM lasku
								WHERE yhtio 	= '$kukarow[yhtio]' 
								and tila 		= 'P' 
								and maa		 	= '$kotimaa' 
								and maksaja 	= '$kukarow[kuka]' 
								and tilinumero	= '$laskurow[tilinumero]' 
								and maksu_tili	= '$laskurow[maksu_tili]' 
								and olmapvm 	= '$pvmrow[olmapvm]'";
					$xresult = mysql_query($query) or pupe_error($query);
				
					$hyvityssumma=0;
					while ($hyvitysrow=mysql_fetch_array ($xresult)) {
						//Meneekˆ tilitys plussalle??
						if ($hyvitysrow['alatila'] == 'K') { // maksetaan k‰teisalennuksella
							$hyvityssumma += $hyvitysrow['summa'] - $hyvitysrow['kasumma'];
						}
						else {
							$hyvityssumma += $hyvitysrow['summa'];
						}
					}
				
					$hyvityssumma = round($hyvityssumma,2);
					$summaarray[$laskurow['maksu_tili']] [$laskurow['tilinumero']]=$hyvityssumma;
					$tilinoarray[$laskurow['maksu_tili']][$laskurow['tilinumero']]=$laskurow['tilinumero'];
				
					if ($hyvityssumma < 0.01) {
						echo "<tr><th>".t("Virhe hyvityslaskuissa").":</th><td><font class='error'>$laskurow[nimi] ($laskurow[tilinumero]) ".t("tililt‰")." $laskurow[maksu_tili] ".t("hyvitykset suuremmat kuin veloitukset. Koko aineisto hyl‰t‰‰n")."!</font></td></tr>";
						exit;
					}
				}
				echo "<tr><th>".t("Hyvityslaskut").":</th><td>Status OK!</td></tr>";
			}
		
			// --- LM03 AINEISTO MAKSUTILIT	
			$query = "	SELECT yriti.tunnus, yriti.tilino, yriti.nimi nimi
						FROM lasku, yriti
						WHERE lasku.yhtio 	= '$kukarow[yhtio]' 
						and tila 			= 'P' 
						and maa		 		= '$kotimaa' 
						and yriti.tunnus 	= maksu_tili 
						and yriti.yhtio 	= lasku.yhtio 
						and maksaja 		= '$kukarow[kuka]' 
						and olmapvm 		= '$pvmrow[olmapvm]'
						GROUP BY yriti.tilino";
			$yritiresult = mysql_query($query) or pupe_error($query);
				
			if (mysql_num_rows($yritiresult) != 0) {

				while ($yritirow = mysql_fetch_array ($yritiresult)) {
			
					$yritystilino 	= $yritirow['tilino'];
					$yrityytunnus 	= $yhtiorow['ytunnus'];
					$maksupvm 		= $pvmrow['olmapvm'];
					$yritysnimi 	= $yhtiorow['nimi'];
					
					if (!is_resource($toot)) {
						$generaatio++;
						if ($kotimaa == "FI") {
							$kaunisnimi = "lm03-$kukarow[yhtio]-" . date("d.m.y.H.i.s") . "-". $generaatio . ".txt";
						}
						else {
							$kaunisnimi = "bg-$kukarow[yhtio]-" . date("d.m.y.H.i.s") . "-" . $generaatio . ".txt";	
						}

						$toot = fopen("dataout/".$kaunisnimi,"w+");

						if (!$toot) {
							echo t("En saanut tiedostoa auki! Tarkista polku")." dataout/$kaunisnimi !";
							exit;
						}
					}
												
					if($kotimaa == "FI") {
						//haetaan t‰m‰n tilin tiedot
						if(isset($pankkitiedot[$yritystilino])) {
							foreach($pankkitiedot[$yritystilino] as $key => $value) {
								${$key} = $value;
							}
						}
						else { 
							die(t("Kadotin t‰m‰n pankin maksuaineistotiedot!"));						
						}

						require("inc/lm03otsik.inc");
						
						//T‰‰ll‰ k‰istell‰‰n kaikki laskut joihin liittyy hyvityksi‰
						require("inc/lm03hyvitykset.inc");
					}
					else{
						require("inc/bginotsik.inc");				
					}
					
					// Yrit‰mme nyt v‰litt‰‰ maksupointterin $laskusis1:ss‰ --> $laskurow[9] --> lasku.tunnus
					$query = "	SELECT maksu_tili,
								left(concat_ws(' ', lasku.nimi, nimitark),30) nimi,
								left(concat_ws(' ', osoite, osoitetark),20) osoite,
								left(concat_ws(' ', postino, postitp),20) postitp,
								summa, lasku.valkoodi, viite, viesti, 
								tilinumero, lasku.tunnus, sisviesti2, 
								yriti.tilino ytilino, alatila, kasumma
								FROM lasku, yriti
								WHERE lasku.yhtio 	= '$kukarow[yhtio]' 
								and tila 			= 'P' 
								and maa		 		= '$kotimaa' 
								and yriti.tunnus 	= maksu_tili 
								and yriti.yhtio 	= lasku.yhtio 
								and maksaja 		= '$kukarow[kuka]' 
								and maksu_tili 		= $yritirow[tunnus] 
								and olmapvm 		= '$pvmrow[olmapvm]'
								ORDER BY tilinumero, summa desc";			
					$result = mysql_query($query) or pupe_error($query);
			
					while ($laskurow = mysql_fetch_array ($result)) {
						$laskutapahtuma	= '10';
						$yritystilino 	= $laskurow["ytilino"];
						$laskunimi1 	= $laskurow["nimi"];
						$laskunimi2 	= $laskurow["osoite"];
						$laskunimi3 	= $laskurow["postitp"];
					
						if ($laskurow["alatila"] == 'K') { // maksetaan k‰teisalennuksella
							$laskusumma = $laskurow["summa"] - $laskurow["kasumma"];
						}
						else {
							$laskusumma = $laskurow["summa"];
						}
					
						$laskutilno 	= $laskurow["tilinumero"];
						$laskusis1  	= $laskurow["tunnus"];
						$laskusis2  	= $laskurow["sisviesti2"];
				  	 	$laskutyyppi	= 5;
				  	 	$laskuviesti 	= $laskurow["viesti"];
			  	 	
						if (strlen($laskurow["viite"]) > 0) {
							$laskuviesti = sprintf ('%020s',$laskurow["viite"]); //Etunollat‰yttˆ
				  	 		$laskutyyppi = 1;
				  	 	}
						
						if ($kotimaa == "FI") {
							require("inc/lm03rivi.inc");
						}
						else {
							require("inc/bginrivi.inc");
						}
						
						$makskpl += 1;
						$makssumma += $laskusumma;
					
						$totkpl += 1;
						$totsumma += $laskusumma;
					}
					
					if($kotimaa == "FI") {
						require("inc/lm03summa.inc");
					}
					else{
						require("inc/bginsumma.inc");
					}
					
					echo "<tr><td>".sprintf(t("Tililt‰ %s siirret‰‰n maksuun"), $yritirow["nimi"]).":</td><td>".sprintf('%.2f',$makssumma)."</td>"; 
					echo "<tr><td>".t("Summa koostuu").":</td><td>$makskpl ".t("laskusta")."</td></tr>";
				
					if ($yhtiorow['pankkitiedostot']!='') {
						if (is_resource($toot)) { 
							fclose($toot);	
							
							if ($tiedostonimi == "") {
								$tiedostonimi = $kaunisnimi;
							}				

							echo "<tr><th>".t("Tallenna aineisto").":</th>";
							echo "<form method='post' action='$PHP_SELF'>";
							echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
							echo "<input type='hidden' name='kaunisnimi' value='$tiedostonimi'>";
							echo "<input type='hidden' name='filenimi' value='$kaunisnimi'>";
							echo "<td><input type='submit' value='".t("Tallenna")."'></td></tr></form>";

						}
						else {
							echo "OOOOH We have problems! Toot is not a resource<br>";
						}
					}

					$query = "	UPDATE lasku 
								SET tila = 'Q'
					          	WHERE yhtio 	= '$kukarow[yhtio]' 
					          	and tila 		= 'P' 
								and maa		 	= '$kotimaa' 
					          	and maksaja 	= '$kukarow[kuka]' 
					          	and maksu_tili	= '$yritirow[tunnus]' 
					          	and olmapvm 	= '$pvmrow[olmapvm]'
					          	ORDER BY yhtio, tila";
					$result = mysql_query($query) or pupe_error($query);
					
					$makskpl 	= 0;
					$makssumma 	= 0;
				}
	
				$makssumma = 0;
			}
			else {
				echo "<font class='message'>".t("Sopivia laskuja ei lˆydy")."!</font>";
			}
		}
	
		//Suljetaan faili
		if ($yhtiorow['pankkitiedostot']=='') {
			if (is_resource($toot)) { 
				fclose($toot);
				
				if ($tiedostonimi == "") {
					$tiedostonimi = $kaunisnimi;
				}
				
				echo "<tr><td class='back'><br></td></tr>";
				echo "<tr><th>".t("Aineiston kokonaissumma on").":</th><td>".sprintf('%.2f',$totsumma)."</td></tr>";
				echo "<tr><th>".t("Summa koostuu").":</th><td>$totkpl ".t("laskusta")."</td></tr>";
				
				echo "<tr><td class='back'><br></td></tr>";
				echo "<tr><th>".t("Tallenna aineisto").":</th>";
				echo "<form method='post' action='$PHP_SELF'>";
				echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
				echo "<input type='hidden' name='kaunisnimi' value='$tiedostonimi'>";
				echo "<input type='hidden' name='filenimi' value='$kaunisnimi'>";
				echo "<td><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
				echo "</table>";	
			}
		}
		
		if ($yhtiorow['pankkitiedostot']!='') {
			if (is_resource($toot)) { 
				fclose($toot);
			}
			if ($totkpl > 0) {	
				echo "<tr><td class='back'><br></td></tr>";
				echo "<tr><th>".t("Maksujen kokonaissumma on").":</th><td>".sprintf('%.2f',$totsumma)."</td></tr>";
				echo "<tr><th>".t("Summa koostuu").":</th><td>$totkpl ".t("laskusta")."</td></tr></table>";
			}
		}
		
		//----------- LUM2 AINEISTO --------------------------
		
		if ($kotimaa == "FI") {
			echo "<br><br><br><font class='head'>LUM2-maksuaineisto</font><hr>";
		}
		else {
			echo "<br><br><br><font class='head'>Betalningsuppdrang via Bankgirot - Utlandsbetalningar</font><hr>";
		}
	
		$makskpl 	= 0;
		$makssumma 	= 0;
		$maksulk 	= 0;
		$totkpl 	= 0;
		$totsumma 	= 0;
		$generaatio=1;

		//Etsit‰‰n aineistot
		$query = "	SELECT maksu_tili, lasku.valkoodi, yriti.tilino ytilino
					FROM lasku, yriti
					WHERE lasku.yhtio = '$kukarow[yhtio]' 
					and tila = 'P' 
					and maa <> '$kotimaa'
					and maksaja = '$kukarow[kuka]'
					and yriti.tunnus = maksu_tili 
					and yriti.yhtio = lasku.yhtio
					GROUP BY maksu_tili, lasku.valkoodi";
		$pvmresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($pvmresult) != 0) {
			while ($pvmrow = mysql_fetch_array($pvmresult)) {
				echo "<table>";
				if ($yhtiorow['pankkitiedostot'] != '' or !(is_resource($toot))) {
					if ($kotimaa == "FI") {
						$kaunisnimi = "lum3-$kukarow[yhtio]-" . date("d.m.y.H.i.s") . "-". $generaatio . ".txt";
					}
					else {
						$kaunisnimi = "bgut-$kukarow[yhtio]-" . date("d.m.y.H.i.s") . "-". $generaatio . ".txt";
					}
					$generaatio++;
		
					$toot = fopen("dataout/".$kaunisnimi,"w+");
				}
				unset($edmaksu_tili);

				if (!$toot) {
					echo t("En saanut tiedostoa auki! Tarkista polku")." dataout/$kaunisnimi !";
					exit;
				}
				
				echo "<tr><th>".t("Ulkomaan maksujen tiedoston nimi on")."</th><td>$kaunisnimi</td></tr>";
				echo "<tr><td class='back'><br></td></tr>";
				
				echo "<tr><th>".t("Maksutili")."<td>$pvmrow[ytilino]</td></tr><tr><th>".t("Laskujen valuutta")."</th><td>$pvmrow[valkoodi]</td></tr>";

				//Maksetaan hyvityslaskut alta pois, jos niit‰ on
				$query = "SELECT maksu_tili, valkoodi, olmapvm, ultilno, swift, pankki1, pankki2, pankki3, pankki4, sum(if(alatila='K', summa-kasumma, summa)) summa
							FROM lasku
							WHERE lasku.yhtio = '$kukarow[yhtio]' 
							and tila = 'P' 
							and maa <> '$kotimaa'
							and maksaja = '$kukarow[kuka]'
							and summa < 0
							and maksu_tili = '$pvmrow[maksu_tili]'
							and valkoodi = '$pvmrow[valkoodi]'
							GROUP BY maksu_tili, valkoodi, olmapvm, ultilno, swift, pankki1, pankki2, pankki3, pankki4";
				$hyvitysresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($hyvitysresult) > 0 ) {
					echo "<tr><th>".t("Maksan hyvityslaskut").":</th><td>".mysql_num_rows($hyvitysresult)."</td></tr>";
					
					while ($hyvitysrow=mysql_fetch_array($hyvitysresult)) {
						$query = "	SELECT maksu_tili,
							left(concat_ws(' ', lasku.nimi, nimitark),45) nimi,
							left(concat_ws(' ', osoite, osoitetark),45) osoite,
							left(concat_ws(' ', postino, postitp),45) postitp,
							sum(if(alatila='K', summa-kasumma, summa)) summa, lasku.valkoodi,
							group_concat(viite) viite, group_concat(viesti) viesti,
							ultilno, group_concat(lasku.tunnus) tunnus,
							yriti.tilino ytilino, yriti.nimi tilinimi,
							maa, pankki1, pankki2, pankki3, pankki4,
							swift, ytunnus, yriti.valkoodi yritivalkoodi
							FROM lasku, yriti, valuu
							WHERE lasku.yhtio = '$kukarow[yhtio]' 
							and tila = 'P' 
							and maa <> '$kotimaa' 
							and yriti.tunnus = maksu_tili 
							and yriti.yhtio = lasku.yhtio 
							and valuu.nimi = lasku.valkoodi 
							and valuu.yhtio = lasku.yhtio 
							and maksaja = '$kukarow[kuka]'
							and maksu_tili = '$pvmrow[maksu_tili]'
							and lasku.valkoodi = '$pvmrow[valkoodi]'
							and olmapvm = '$hyvitysrow[olmapvm]'
							and maksu_tili = '$hyvitysrow[maksu_tili]'
							and ultilno = '$hyvitysrow[ultilno]'
							and swift = '$hyvitysrow[swift]'
							and pankki1 = '$hyvitysrow[pankki1]'
							and pankki2 = '$hyvitysrow[pankki2]'
							and pankki3 = '$hyvitysrow[pankki3]'
							and pankki4 = '$hyvitysrow[pankki4]'
							GROUP BY maksu_tili, lasku.valkoodi, olmapvm, ultilno, swift, pankki1, pankki2, pankki3, pankki4";
						$maksuresult = mysql_query($query) or pupe_error($query);
						if (mysql_num_rows($maksuresult) > 0 ) {

							while ($laskurow=mysql_fetch_array($maksuresult)) {
								if (!isset($edmaksu_tili)) {
									$yritystilino =  $laskurow["ytilino"];
									$yrityytunnus =  $yhtiorow['ytunnus'];
									
									if ($kotimaa == "FI") {
										//haetaan t‰m‰n tilin tiedot
										if(isset($pankkitiedot[$yritystilino])) {
											foreach($pankkitiedot[$yritystilino] as $key => $value) {
												${$key} = $value;
											}
										}
										else { 
											die(t("Kadotin t‰m‰n pankin maksuaineistotiedot!"));						
										}
										require("inc/lum2otsik.inc");
									}
									else {
										require("inc/bgutotsik.inc");
									}
										
									$edmaksu_tili 		= $laskurow["maksu_tili"];
									$edvalkoodi 		= $laskurow["valkoodi"];
									$edyritystilino 	= $yritystilino;
									$edyritystilinimi 	= $laskurow["tilinimi"];
								}
								
								$yritysnimi 	= strtoupper($yhtiorow["nimi"]);
								$yritysosoite 	= strtoupper($yhtiorow["osoite"]);
								$yritystilino 	= $laskurow["ytilino"];
								$laskunimi1 	= $laskurow["nimi"];
								$laskunimi2 	= $laskurow["osoite"];
								$laskunimi3 	= $laskurow["postitp"];
								$laskusumma 	= $laskurow["summa"];
								$laskuvaluutta 	= $laskurow["valkoodi"];
								$laskutilino 	= $laskurow["ultilno"];
								$laskuaihe 		= $laskurow["viesti"] . " " . $laskurow["tunnus"];
								$laskumaakoodi 	= $laskurow["maa"];
								$laskupankki1  	= $laskurow["pankki1"];
								$laskupankki2  	= $laskurow["pankki2"];
								$laskupankki3  	= $laskurow["pankki3"];
								$laskupankki4  	= $laskurow["pankki4"];
								$laskuswift 	= $laskurow["swift"];
								$laskuyritivaluutta  = $laskurow["yritivalkoodi"];
								
								if ($kotimaa == "FI") {
									require("inc/lum2rivi.inc");
								}
								else {
									require("inc/bgutrivi.inc");
								}
								
								$makskpl += 1;
								$makssumma += $laskusumma;
								$maksulk += $ulklaskusumma;	//viritet‰‰n bgutrivi.inc-failissa
							}
							$query = "	UPDATE lasku SET tila = 'Q'
										WHERE lasku.yhtio = '$kukarow[yhtio]' 
										and tila = 'P' 
										and maa <> '$kotimaa' 
										and maksaja = '$kukarow[kuka]'
										and olmapvm = '$hyvitysrow[olmapvm]'
										and maksu_tili = '$hyvitysrow[maksu_tili]'
										and ultilno = '$hyvitysrow[ultilno]'
										and swift = '$hyvitysrow[swift]'
										and pankki1 = '$hyvitysrow[pankki1]'
										and pankki2 = '$hyvitysrow[pankki2]'
										and pankki3 = '$hyvitysrow[pankki3]'
										and pankki4 = '$hyvitysrow[pankki4]'
									    ORDER BY yhtio, tila";
							$result = mysql_query($query) or pupe_error($query);

						}
						else {
							echo "Meill‰ oli hyvityksi‰, mutta ne kaikki katosivat yhdistelyss‰!";
						}
					}
				}		
				// Yrit‰mme nyt v‰litt‰‰ maksupointterin $laskusis1:ss‰ --> $laskurow[9] --> tunnus
				$query = "	SELECT maksu_tili,
							left(concat_ws(' ', lasku.nimi, nimitark),45) nimi,
							left(concat_ws(' ', osoite, osoitetark),45) osoite,
							left(concat_ws(' ', postino, postitp),45) postitp,
							summa, lasku.valkoodi, viite, viesti,
							ultilno, lasku.tunnus, sisviesti2, 
							yriti.tilino ytilino, yriti.nimi tilinimi,
							maa, pankki1, pankki2, pankki3, pankki4,
							swift, alatila, kasumma, kurssi, ytunnus, yriti.valkoodi yritivalkoodi
							FROM lasku, yriti, valuu
							WHERE lasku.yhtio = '$kukarow[yhtio]' 
							and tila = 'P' 
							and maa <> '$kotimaa' 
							and yriti.tunnus = maksu_tili 
							and yriti.yhtio = lasku.yhtio 
							and valuu.nimi = lasku.valkoodi 
							and valuu.yhtio = lasku.yhtio 
							and maksaja = '$kukarow[kuka]'
							and maksu_tili = '$pvmrow[maksu_tili]'
							and lasku.valkoodi = '$pvmrow[valkoodi]'
							ORDER BY summa";
				$result = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($result) > 0) {

					while ($laskurow=mysql_fetch_array ($result)) {
						$yritysnimi 	= strtoupper($yhtiorow["nimi"]);
						$yritysosoite 	= strtoupper($yhtiorow["osoite"]);
						$yritystilino 	= $laskurow["ytilino"];
						$laskunimi1 	= $laskurow["nimi"];
						$laskunimi2 	= $laskurow["osoite"];
						$laskunimi3 	= $laskurow["postitp"];
						
						if ($laskurow["alatila"] == 'K') { // maksetaan k‰teisalennuksella
							$laskusumma = $laskurow["summa"] - $laskurow["kasumma"];
						}
						else {
							$laskusumma = $laskurow["summa"];
						}
						
						$laskuvaluutta 	= $laskurow["valkoodi"];
						$laskutilino 	= $laskurow["ultilno"];
						$laskuaihe 		= $laskurow["viesti"] . " " . $laskurow["tunnus"];
						$laskumaakoodi 	= $laskurow["maa"];
						$laskupankki1  	= $laskurow["pankki1"];
						$laskupankki2  	= $laskurow["pankki2"];
						$laskupankki3  	= $laskurow["pankki3"];
						$laskupankki4  	= $laskurow["pankki4"];
						$laskuswift 	= $laskurow["swift"];
						$laskuyritivaluutta  = $laskurow["yritivalkoodi"];
						
						//haetaan t‰m‰n tilin tiedot
						if ($kotimaa == "FI") {				
							if(isset($pankkitiedot[$yritystilino])) {
								foreach($pankkitiedot[$yritystilino] as $key => $value) {
									${$key} = $value;
								}
							}
							else { 
								die(t("Kadotin t‰m‰n pankin maksuaineistotiedot!"));
							}
						
							if($lum_eumaksu != "") {
								//haetaan automaagisesti EU maksu jos ehdot t‰yttyv‰t, muuten normaalilla maksum‰‰r‰kysen‰
								//T‰m‰ siis siksi ett‰ myˆs OP osaa ottaa laskut maksuun... 

								//onko t‰m‰ laskun eurom‰‰r‰ alle 50 000 eur?
								if($laskusumma < 50000 and strtoupper($laskuvaluutta) == 'EUR') {
									//t‰sm‰‰kˆ maatunnukset
									$tinoalut = $laskutilino{0}.$laskutilino{1};
									$swiftmaa = $laskuswift{4}.$laskuswift{5};
									
									if($tinoalut == $swiftmaa) {
										//onko EU maksun saaja EU alueella?
										$query = "	SELECT koodi
													FROM maat
													WHERE koodi = '$laskumaakoodi'
													and eu != ''
													and ryhma_tunnus = ''";
										$aburesult = mysql_query($query) or pupe_error($query);

										if(mysql_num_rows($aburesult) == 1) {
											//meill‰ on siis EU maksukelpoinen lasku
											//t‰m‰ vaatii seuraavat tiedot pois
											$laskupankki1 ='';
											$laskupankki2 ='';
											$laskupankki3 ='';
											$laskupankki4 ='';
										
											// eli t‰m‰ on kelpo eumaksu joten se me myˆs tehd‰‰n
											$lum_maksutapa = $lum_eumaksu;

											echo "$laskunimi oli EU-maksu<br>";
										}
										else {
											echo "$laskunimi ei EU-maksu, maa ei EU alueella. Tehtiin maksum‰‰r‰ys.<br>";
										}
									}
									else {
										echo "$laskunimi ei EU-maksu SWIFT maatunnus ($swiftmaa) ja IBAN maatunnus ($tinoalut) eiv‰t t‰sm‰‰. Tehtiin maksum‰‰r‰ys.<br>";
									}
								}
								else {
									echo "$laskunimi ei EU-maksu laskusumma on yli 50 000 eur tai valuutta ei ole EUR. Tehtiin maksum‰‰r‰ys.<br>";
								}			
							}
						}


						if (!isset($edmaksu_tili)) {
							$yritystilino =  $laskurow["ytilino"];
							$yrityytunnus =  $yhtiorow['ytunnus'];
							
							if ($kotimaa == "FI") {
								require("inc/lum2otsik.inc");
							}
							else {
								require("inc/bgutotsik.inc");
							}
								
							$edmaksu_tili 		= $laskurow["maksu_tili"];
							$edvalkoodi 		= $laskurow["valkoodi"];
							$edyritystilino 	= $yritystilino;
							$edyritystilinimi 	= $laskurow["tilinimi"];
						}
						
						if ($kotimaa == "FI") {
							require("inc/lum2rivi.inc");
						}
						else {
							require("inc/bgutrivi.inc");
						}
						
						$makskpl += 1;
						$makssumma += $laskusumma;
						$maksulk += $ulklaskusumma;	//viritet‰‰n bgutrivi.inc-failissa
					}
				}
				if (isset($edmaksu_tili)) {
					if ($kotimaa == "FI") {
						require("inc/lum2summa.inc");
					}
					else {
						require("inc/bgutsumma.inc");
					}
					
					echo "<tr><td>".sprintf(t("Tililt‰ %s siirret‰‰n maksuun"), $edyritystilinimi." (".$edvalkoodi.")").":</td><td>".sprintf('%.2f',$makssumma)."</td>"; 
					echo "<tr><td>".t("Summa koostuu").":</td><td>$makskpl ".t("laskusta")."</td></tr>";
							
					$totkpl += $makskpl;
					$totsumma += $makssumma;
					$makskpl = 0;
					$makssumma = 0;
					unset($edmaksu_tili);
					

					if ($yhtiorow['pankkitiedostot'] != '') {
						fclose($toot);
						
						if ($tiedostonimilum2 == "") {
							$tiedostonimilum2 = $kaunisnimi;
						}

						echo "<tr><td class='back'><br></td></tr>";
						echo "<tr><th>".t("Tallenna aineisto").":</th>";
						echo "<form method='post' action='$PHP_SELF'>";
						echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
						echo "<input type='hidden' name='kaunisnimi' value='$tiedostonimilum2'>";
						echo "<input type='hidden' name='filenimi' value='$kaunisnimi'>";
						echo "<td><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
					}
		
					$query = "	UPDATE lasku SET tila = 'Q'
								WHERE lasku.yhtio = '$kukarow[yhtio]' 
								and tila = 'P' 
								and maa <> '$kotimaa' 
								and maksaja = '$kukarow[kuka]'
								and maksu_tili = '$pvmrow[maksu_tili]'
								and valkoodi = '$pvmrow[valkoodi]'
							    ORDER BY yhtio, tila";
					$result = mysql_query($query) or pupe_error($query);
				}
			}

			if (is_resource($toot)) {
				fclose($toot);
				
				if ($tiedostonimilum2 == "") {
					$tiedostonimilum2 = $kaunisnimi;
				}

				echo "<tr><td class='back'><br></td></tr>";
				echo "<tr><th>".t("Tallenna aineisto").":</th>";
				echo "<form method='post' action='$PHP_SELF'>";
				echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
				echo "<input type='hidden' name='kaunisnimi' value='$tiedostonimilum2'>";
				echo "<input type='hidden' name='filenimi' value='$kaunisnimi'>";
				echo "<td><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
			}

			echo "<tr><td class='back'><br></td></tr>";
			echo "<tr><th>".t("Aineiston kokonaissumma on").":</th><td>".sprintf('%.2f',$totsumma)."</td></tr>";
			echo "<tr><th>".t("Summa koostuu").":</th><td>$totkpl ".t("laskusta")."</td></tr></table>";
		}
		else {
			echo "<font class='message'>".t("Sopivia laskuja ei lˆydy")."</font>";
		}
		
		require ("inc/footer.inc");
	}
?>