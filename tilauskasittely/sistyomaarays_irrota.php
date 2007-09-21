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
						
				// Ostorivi
				$query = "	SELECT if(kpl!=0, kpl, tilkpl) kpl
							FROM tilausrivi 
							WHERE yhtio  = '$kukarow[yhtio]' 
							and tunnus   = '$sarjarow[perheid2]'
							and perheid2!= 0";
				$sarjares = mysql_query($query) or pupe_error($query);
				$ostorow = mysql_fetch_array($sarjares);
								
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
	require ("../inc/footer.inc");
?>
