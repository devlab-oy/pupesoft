<?php

	require ("../inc/parametrit.inc");

	echo "<font class='head'>".t("Irrota lisävarutse laitteesta").":<hr></font>";

	if ($tee == "VALMIS") {
		
		//Haetaan jatkojalostettavan tuotteen ostorivitunnus 
		$query = "	UPDATE sarjanumeroseuranta 
					SET siirtorivitunnus = 0 
					WHERE yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno' and siirtorivitunnus='$siirtorivitunnus'";
		$sarjares = mysql_query($query) or pupe_error($query);
		
				
		$query = "	UPDATE tilausrivi 
					SET perheid2 = 0 
					WHERE yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno' and tunnus='$rivitunnus'";
		$sarjares = mysql_query($query) or pupe_error($query);
		
		echo t("Lisävaruste irrotettu laitteesta")."!<br><br>";
		
		$tee = "";
	}

	
	if ($tee == "") {
		
		// Näytetään muuten vaan sopivia tilauksia
		echo "<br><form action='$PHP_SELF' method='post'>
				<input type='hidden' name='toim' value='$toim'>
				<font class='message'>".t("Etsi laite").":<hr></font>
				".t("Syötä sarjanumero").":
				<input type='text' name='etsi' value='$etsi'>
				<input type='Submit' value = '".t("Etsi")."'>
				</form>";


		if ($etsi != '') {

			//Tutkitaan lisävarusteita
			$query = "	SELECT tilausrivi_osto.perheid2, sarjanumeroseuranta.tuoteno, sarjanumeroseuranta.sarjanumero
						FROM sarjanumeroseuranta 
						JOIN tilausrivi tilausrivi_osto use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus and tilausrivi_osto.tunnus=tilausrivi_osto.perheid2
						WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]' 
						and sarjanumeroseuranta.sarjanumero  = '$etsi'";
			$sarjares = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($sarjares) == 1) {
				$sarjarow = mysql_fetch_array($sarjares);
				
				echo "<br><br><table>";
								
				// Haetaan muut lisävarusteet
				$query = "	SELECT tuoteno, perheid2, tilkpl, tyyppi, tunnus
							FROM tilausrivi use index (yhtio_perheid2)
							WHERE yhtio  = '$kukarow[yhtio]' 
							and perheid2 = '$sarjarow[perheid2]'
							and tyyppi  != 'D'
							and tunnus  != '$sarjarow[perheid2]'
							and perheid2!= 0";
				$sarjares1 = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($sarjares1) > 0) {
					echo "<tr><th>$sarjarow[tuoteno]</th><th>$sarjarow[sarjanumero]</th></tr>";
						
					while ($sarjarow1 = mysql_fetch_array($sarjares1)) {
						echo "<tr><td>$sarjarow1[tuoteno]</td><td>$sarjarow1[tilkpl]</td>";
						
						if ($sarjarow1["tyyppi"] == "G") {
							echo "<td class='back'>	
									<form method='post' action='$PHP_SELF'>	
									<input type='hidden' name='tee' value='VALMIS'>
									<input type='hidden' name='etsi' value='$etsi'>
									<input type='hidden' name='tuoteno' value='$sarjarow1[tuoteno]'>
									<input type='hidden' name='siirtorivitunnus' value='$sarjarow[perheid2]'>
									<input type='hidden' name='rivitunnus' value='$sarjarow1[tunnus]'>
									<input type='submit' value='".t("Irrota lisävaruste")."'>
									</form>
									</td>";
						}
						else {
							echo "<td class='back'>".t("Tehdaslisävarustetta ei voida irrottaa")."</td>";
						}
						
						echo "</tr>";
					}
				}
				echo "</table>";
			}
		}
	}
	
	/*
	// Näytetään kaikki
	$query	= "	SELECT sarjanumeroseuranta.*,
				if(tilausrivi_osto.nimitys!='', tilausrivi_osto.nimitys, tuote.nimitys) nimitys,
				tuote.myyntihinta 									tuotemyyntihinta,
				lasku_osto.tunnus									osto_tunnus,
				lasku_osto.nimi										osto_nimi,
				lasku_myynti.tunnus									myynti_tunnus,
				lasku_myynti.nimi									myynti_nimi,
				lasku_myynti.tila									myynti_tila,
				(tilausrivi_osto.rivihinta/tilausrivi_osto.kpl)		ostohinta,
				tilausrivi_osto.tunnus								osto_rivitunnus,
				tilausrivi_osto.perheid2							osto_perheid2,
				(tilausrivi_myynti.rivihinta/tilausrivi_myynti.kpl)	myyntihinta,
				varastopaikat.nimitys								varastonimi,
				sarjanumeroseuranta.lisatieto						lisatieto,
				sarjanumeroseuranta.hyllyalue, 
				sarjanumeroseuranta.hyllynro, 
				sarjanumeroseuranta.hyllyvali, 
				sarjanumeroseuranta.hyllytaso
				FROM sarjanumeroseuranta
				JOIN tuote use index (tuoteno_index) ON sarjanumeroseuranta.yhtio=tuote.yhtio and sarjanumeroseuranta.tuoteno=tuote.tuoteno
				LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
				LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
				LEFT JOIN lasku lasku_myynti use index (PRIMARY) ON lasku_myynti.yhtio=sarjanumeroseuranta.yhtio and lasku_myynti.tunnus=tilausrivi_myynti.otunnus
				LEFT JOIN lasku lasku_osto   use index (PRIMARY) ON lasku_osto.yhtio=sarjanumeroseuranta.yhtio and lasku_osto.tunnus=tilausrivi_osto.otunnus
				LEFT JOIN varastopaikat ON sarjanumeroseuranta.yhtio = varastopaikat.yhtio
				and concat(rpad(upper(varastopaikat.alkuhyllyalue)  ,5,'0'),lpad(upper(varastopaikat.alkuhyllynro)  ,5,'0')) <= concat(rpad(upper(sarjanumeroseuranta.hyllyalue) ,5,'0'),lpad(upper(sarjanumeroseuranta.hyllynro) ,5,'0'))
				and concat(rpad(upper(varastopaikat.loppuhyllyalue) ,5,'0'),lpad(upper(varastopaikat.loppuhyllynro) ,5,'0')) >= concat(rpad(upper(sarjanumeroseuranta.hyllyalue) ,5,'0'),lpad(upper(sarjanumeroseuranta.hyllynro) ,5,'0'))
				WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
				and sarjanumeroseuranta.myyntirivitunnus != -1
				and (tilausrivi_myynti.tunnus is null or tilausrivi_myynti.laskutettuaika = '0000-00-00')
				and tilausrivi_osto.laskutettuaika != '0000-00-00'
				HAVING osto_rivitunnus = osto_perheid2
				ORDER BY sarjanumeroseuranta.kaytetty, sarjanumeroseuranta.tuoteno, sarjanumeroseuranta.myyntirivitunnus";
	$sarjares = mysql_query($query) or pupe_error($query);
	
	while ($sarjarow = mysql_fetch_array($sarjares)) {
		
		// Haetaan muut lisävarusteet
		$query = "	SELECT tuoteno, perheid2, tilkpl, tyyppi, tunnus
					FROM tilausrivi use index (yhtio_perheid2)
					WHERE yhtio  = '$kukarow[yhtio]' 
					and perheid2 = '$sarjarow[osto_perheid2]'
					and tyyppi  != 'D'
					and tunnus  != '$sarjarow[osto_perheid2]'
					and perheid2!= 0";
		$sarjares1 = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($sarjares1) > 0) {
			echo "<br><br><table>";
			echo "<tr><th>$sarjarow[tuoteno]</th><th>$sarjarow[sarjanumero]</th><th>$sarjarow[hyllyalue] $sarjarow[hyllynro] $sarjarow[hyllyvali] $sarjarow[hyllytaso]</th></tr>";
				
			while ($sarjarow1 = mysql_fetch_array($sarjares1)) {
				echo "<tr><td>$sarjarow1[tuoteno]</td><td>$sarjarow1[tilkpl]</td>";
				
				$query = "	UPDATE tuotepaikat 
							set saldo_varattu 	= saldo_varattu+1				
							WHERE tuoteno 		= '$sarjarow1[tuoteno]' 
							and yhtio 			= '$kukarow[yhtio]' 
							and hyllyalue 		= '$sarjarow[hyllyalue]'
							and hyllynro  		= '$sarjarow[hyllynro]'
							and hyllyvali 		= '$sarjarow[hyllyvali]'
							and hyllytaso 		= '$sarjarow[hyllytaso]'";
				$rresult = mysql_query($query) or pupe_error($query);
				
				echo "</tr>";
			}
			echo "</table>";
		}
	}
	*/
	
	require ("../inc/footer.inc");
?>
