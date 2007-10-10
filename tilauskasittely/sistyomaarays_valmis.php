<?php

	require ("../inc/parametrit.inc");

	echo "<font class='head'>".t("Kuittaa sisäinen työmääräys valmiiksi").":<hr></font>";

	if ($tee == "VALMIS") {
		// katsotaan onko muilla aktiivisena
		$query = "select * from kuka where yhtio='$kukarow[yhtio]' and kesken='$tilausnumero' and kesken!=0";
		$result = mysql_query($query) or pupe_error($query);

		unset($row);

		if (mysql_num_rows($result) != 0) {
			$row=mysql_fetch_array($result);
		}

		if (isset($row) and $row['kuka'] != $kukarow['kuka']) {
			echo "<font class='error'>".t("Tilaus on aktiivisena käyttäjällä")." $row[nimi]. ".t("Tilausta ei voi tällä hetkellä muokata").".</font><br>";

			// poistetaan aktiiviset tilaukset jota tällä käyttäjällä oli
			$query = "update kuka set kesken='' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
			$result = mysql_query($query) or pupe_error($query);

			$tee = "";
		}
		else {
			// lock tables
			$query = "LOCK TABLES lasku WRITE, tilausrivi WRITE, sanakirja WRITE, tilausrivi as tilausrivi_osto READ, tuote READ, sarjanumeroseuranta WRITE, tuotepaikat WRITE, tilausrivin_lisatiedot WRITE";
			$locre = mysql_query($query) or pupe_error($query);
			
			$query = "	SELECT *
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]' and alatila='C' and tila = 'S' and tunnus = '$tilausnumero'";
			$result = mysql_query($query) or pupe_error($query);
		
			if (mysql_num_rows($result) == 1) {				
				$query = "	UPDATE lasku
							SET alatila = 'X'
							WHERE yhtio = '$kukarow[yhtio]' and alatila='C' and tila = 'S' and tunnus = '$tilausnumero'";
				$result = mysql_query($query) or pupe_error($query);
				
				//Haetaan jatkojalostettavat tuotteet
				$query    = "	SELECT tilausrivi.tunnus, tilausrivi.varattu varattu, tilausrivi.tuoteno
								FROM tilausrivi use index (yhtio_otunnus)
								JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
								WHERE tilausrivi.yhtio='$kukarow[yhtio]'
								and tilausrivi.otunnus='$tilausnumero'
								and tilausrivi.perheid2=tilausrivi.tunnus";
				$jres = mysql_query($query) or pupe_error($query);

				while($srow = mysql_fetch_array($jres)) {
					
					//Haetaan jatkojalostettavan tuotteen ostorivitunnus 
					$query = "	SELECT * 
								FROM sarjanumeroseuranta 
								WHERE yhtio='$kukarow[yhtio]' and tuoteno='$srow[tuoteno]' and siirtorivitunnus='$srow[tunnus]'";
					$sarjares = mysql_query($query) or pupe_error($query);
					$sarjarow = mysql_fetch_array($sarjares);

					// Haetaan lisävarusteet 
					$query    = "	SELECT tilausrivi.*, tuote.ei_saldoa, tuote.kehahin, tuote.sarjanumeroseuranta, tilausrivi.tunnus as rivitunnus
									FROM tilausrivi
									JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
									WHERE tilausrivi.yhtio	= '$kukarow[yhtio]'
									and tilausrivi.otunnus	= '$tilausnumero'
									and tilausrivi.perheid2	= '$srow[tunnus]' 
									and tilausrivi.tunnus  != '$srow[tunnus]'";
					$kres = mysql_query($query) or pupe_error($query);

					while ($lisarow = mysql_fetch_array($kres)) {
						$ostohinta = 0;
						$ostorivinsarjanumero = 0;
						
						if ($lisarow["sarjanumeroseuranta"] == "S") {
							//Hateaan lisävarusteen ostohinta
							$query = "	SELECT round(sum(tilausrivi_osto.rivihinta/tilausrivi_osto.kpl),2) ostohinta, group_concat(sarjanumeroseuranta.tunnus) tunnus
										FROM sarjanumeroseuranta
										JOIN tilausrivi tilausrivi_osto use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus 
										WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]' 
										and sarjanumeroseuranta.tuoteno = '$lisarow[tuoteno]' 
										and sarjanumeroseuranta.siirtorivitunnus = '$lisarow[rivitunnus]'";
							$sarjares = mysql_query($query) or pupe_error($query);
							$sarjarow2 = mysql_fetch_array($sarjares);

							$ostohinta 				= $sarjarow2["ostohinta"];
							$ostorivinsarjanumero	= $sarjarow2["tunnus"];
						}
						else {
							// Lisävaruste on bulkkikamaa
							$ostohinta = $lisarow["kehahin"];
						}
						
						$ostohinta = (int) $ostohinta;
						
						//Ruuvataan liitetyt lisävarusteet kiinni laitteen alkuperäiseen ostoriviin
						// Jos lisävauste on sarjanumeroitava, niin sarjanumeron tunnus menee
						// lisävarusterivin sistyomaarays_sarjatunnus-kentässä
						$query = "	UPDATE tilausrivi 
									SET perheid2	= '$sarjarow[ostorivitunnus]', 
									rivihinta		= round(varattu*$ostohinta,2), 
									toimitettu		= '$kukarow[kuka]', 
									toimitettuaika 	= now(), 
									tilkpl			= varattu, 
									varattu 		= 0
									WHERE yhtio = '$kukarow[yhtio]' 
									and tunnus  = '$lisarow[rivitunnus]'";
						$sarjares = mysql_query($query) or pupe_error($query);
						
						$query = "	UPDATE tilausrivin_lisatiedot
									SET sistyomaarays_sarjatunnus = '$ostorivinsarjanumero' 
									WHERE yhtio 			= '$kukarow[yhtio]' 
									and tilausrivitunnus  	= '$lisarow[rivitunnus]'";
						$sarjares = mysql_query($query) or pupe_error($query);

						if ($lisarow["ei_saldoa"] == '') {
							//Varataan lisävarusteen saldoa
							$query = "	UPDATE tuotepaikat 
										SET saldo_varattu = saldo_varattu + $lisarow[varattu] 
										WHERE yhtio		= '$kukarow[yhtio]' 
										and tuoteno		= '$lisarow[tuoteno]'
										and hyllyalue 	= '$lisarow[hyllyalue]'
										and hyllynro  	= '$lisarow[hyllynro]'
										and hyllyvali 	= '$lisarow[hyllyvali]'
										and hyllytaso 	= '$lisarow[hyllytaso]'
										LIMIT 1";
							$sarjares = mysql_query($query) or pupe_error($query);
						}
						
						//Päivitetään varmuuden vuoksi alkuperäisen ostorivin perheid2 (se voi olla nolla)
						$query = "	UPDATE tilausrivi 
									SET perheid2 = '$sarjarow[ostorivitunnus]'
									WHERE yhtio = '$kukarow[yhtio]' 
									and tunnus  = '$sarjarow[ostorivitunnus]'";
						$sarjares = mysql_query($query) or pupe_error($query);
						
						if ($lisarow["sarjanumeroseuranta"] != '') {
							//Varataan sarjanumero jatkojalostettavalle tuotteelle
							$query = "	UPDATE sarjanumeroseuranta 
										SET siirtorivitunnus = $sarjarow[ostorivitunnus] 
										WHERE yhtio				= '$kukarow[yhtio]' 
										and tuoteno				= '$lisarow[tuoteno]' 
										and siirtorivitunnus	= '$lisarow[rivitunnus]'";
							$sarjares = mysql_query($query) or pupe_error($query);
						}
					}
					
					//Irroitetaan jatkojalostettavan tuotteen sarjanumero
					$query = "	UPDATE sarjanumeroseuranta 
								SET siirtorivitunnus=0 
								WHERE yhtio='$kukarow[yhtio]' and tuoteno='$srow[tuoteno]' and siirtorivitunnus='$srow[tunnus]'";
					$sarjares = mysql_query($query) or pupe_error($query);
					
					//Päivitetään jatkojalostettava rivi valmiiksi
					$query = "	UPDATE tilausrivi 
								SET toimitettu='$kukarow[kuka]', toimitettuaika = now(), tilkpl=varattu, varattu = 0 
								WHERE yhtio='$kukarow[yhtio]' and otunnus='$tilausnumero' and tunnus='$srow[tunnus]'";
					$sarjares = mysql_query($query) or pupe_error($query);
				}
				
				echo "<font class='message'>".t("Sisäinen työmääräys merkattiin valmiiksi")."!</font><br><br>";
				$tee = "";
			}
			else {
				echo "<font class='error'>".t("VIRHE: Sisäinen työmääräys on väärässä tilassa")."!</font><br><br>";
				$tee = "";
			}
			
			$query = "UNLOCK TABLES";
			$locre = mysql_query($query) or pupe_error($query);
		}
	}

	
	if ($tee == "") {
		
		// Näytetään muuten vaan sopivia tilauksia
		echo "<br><form action='$PHP_SELF' method='post'>
				<input type='hidden' name='toim' value='$toim'>
				<font class='head'>".t("Etsi sisäinen työmääräys").":<hr></font>
				".t("Syötä tilausnumero, nimen tai laatijan osa").":
				<input type='text' name='etsi'>
				<input type='Submit' value = '".t("Etsi")."'>
				</form>";

		// pvm 30 pv taaksepäin
		$dd = date("d",mktime(0, 0, 0, date("m"), date("d")-30, date("Y")));
		$mm = date("m",mktime(0, 0, 0, date("m"), date("d")-30, date("Y")));
		$yy = date("Y",mktime(0, 0, 0, date("m"), date("d")-30, date("Y")));

		$haku='';
		if (is_string($etsi))  $haku="and (lasku.nimi like '%$etsi%' or lasku.laatija like '%$etsi%')";
		if (is_numeric($etsi)) $haku="and (lasku.tunnus like '$etsi%' or lasku.ytunnus like '$etsi%')";

		$query = "	SELECT tunnus tilaus, nimi varasto, ytunnus id, luontiaika, laatija, viesti tilausviite, alatila, tila
					FROM lasku use index (tila_index)
					WHERE lasku.yhtio = '$kukarow[yhtio]' and tila='S' and alatila='C'
					$haku
					order by luontiaika desc";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result)!=0) {

			echo "<table border='0' cellpadding='2' cellspacing='1'>";

			echo "<tr>";

			for ($i=0; $i < mysql_num_fields($result)-2; $i++) {
				echo "<th align='left'>".t(mysql_field_name($result,$i))."</th>";
			}
			echo "<th align='left'>".t("tyyppi")."</th></tr>";

			while ($row = mysql_fetch_array($result)) {

				echo "<tr>";

				for ($i=0; $i<mysql_num_fields($result)-2; $i++) {
					echo "<td>$row[$i]</td>";
				}

				$laskutyyppi=$row["tila"];
				$alatila=$row["alatila"];

				//tehdään selväkielinen tila/alatila
				require ("inc/laskutyyppi.inc");

				echo "<td>".t("$laskutyyppi")." ".t("$alatila")."</td>";

				echo "<td class='back'>	
						<form method='post' action='tilaus_myynti.php'>	
						<input type='hidden' name='toim' value='SIIRTOTYOMAARAYS'>
						<input type='hidden' name='tee' value='AKTIVOI'>
						<input type='hidden' name='tilausnumero' value='$row[tilaus]'>
						<input type='submit' value='".t("Muokkaa")."'>
						</form>
						</td>";
		
				echo "<td class='back'>	
						<form method='post' action='$PHP_SELF'>	
						<input type='hidden' name='tee' value='VALMIS'>
						<input type='hidden' name='tilausnumero' value='$row[tilaus]'>
						<input type='submit' value='".t("Valmis")."'>
						</form>
						</td>";
		
				echo "</tr>";
			}

			echo "</table>";

			if (is_array($sumrow)) {
				echo "<br><table cellpadding='5'><tr>";
				echo "<th>".t("Tilausten arvo yhteensä")." ($sumrow[kpl] ".t("kpl")."): </th>";
				echo "<td>$sumrow[arvo] $yhtiorow[valkoodi]</td>";
				echo "</tr></table>";
			}

		}
		else {
			echo t("Ei tilauksia")."...";
		}
	}
	require ("../inc/footer.inc");
?>
