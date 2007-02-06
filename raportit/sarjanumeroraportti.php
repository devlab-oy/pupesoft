<?php

	require("../inc/parametrit.inc");

	echo "<font class='head'>".t("Sarjanumeroraportointi")."</font><hr>";

	echo "<table>";
	echo "<tr>";
	echo "<th>".t("Sarjanumero")."</th>";
	echo "<th>".t("Tuoteno")."</th>";
	echo "<th>".t("Nimitys")."</th>";
	echo "<th>".t("Ostotilaus")."</th>";
	echo "<th>".t("Myyntitilaus")."</th>";
	echo "<th>".t("Hinnat")."</th>";
	echo "<th>".t("Lisätiedot")."</th>";
	echo "</tr>";

	//Kursorinohjaus
	$formi	= "haku";
	$kentta = "sarjanumero_haku";

	echo "<form name='haku' action='$PHP_SELF' method='post'>";
	echo "<input type='hidden' name='tee' value = 'RAPORTOI'>";

	echo "<tr>";
	echo "<td><input type='text' size='10' name='sarjanumero_haku' 		value='$sarjanumero_haku'></td>";
	echo "<td><input type='text' size='10' name='tuoteno_haku' 			value='$tuoteno_haku'></td>";
	echo "<td><input type='text' size='10' name='nimitys_haku' 			value='$nimitys_haku'></td>";
	echo "<td><input type='text' size='10' name='ostotilaus_haku' 		value='$ostotilaus_haku'></td>";
	echo "<td><input type='text' size='10' name='myyntitilaus_haku'		value='$myyntitilaus_haku'></td>";
	echo "<td></td><td></td><td class='back'><input type='submit' value='Hae'></td>";
	echo "</tr>";
	echo "</form>";

	
	if ($tee == "RAPORTOI") {
		$lisa  = "";

		if ($ostotilaus_haku != "") {
			if (is_numeric($ostotilaus_haku)) {
				$lisa .= " and lasku_osto.tunnus='$ostotilaus_haku' ";
			}
			else {
				$lisa .= " and match (lasku_osto.nimi) against ('$ostotilaus_haku*' IN BOOLEAN MODE) ";
			}
		}

		if ($myyntitilaus_haku != "") {
			if (is_numeric($myyntitilaus_haku)) {
				$lisa .= " and lasku_myynti.tunnus='$myyntitilaus_haku' ";
			}
			else {
				$lisa .= " and match (lasku_myynti.nimi) against ('$myyntitilaus_haku*' IN BOOLEAN MODE) ";
			}
		}

		if ($lisatieto_haku) {
			$lisa .= " and sarjanumeroseuranta.lisatieto like '$lisatieto_haku%' ";
		}

		if ($tuoteno_haku) {
			$lisa .= " and sarjanumeroseuranta.tuoteno like '$tuoteno_haku%' ";
		}

		if ($nimitys_haku) {
			$lisa .= " and tuote.nimitys like '$nimitys_haku%' ";
		}

		if ($sarjanumero_haku) {
			$lisa .= " and sarjanumeroseuranta.sarjanumero like '$sarjanumero_haku%' ";
		}
	
	
		if ($lisa == "") {
			$lisa = " HAVING osto_tunnus is null or myynti_tunnus is null";
		}

		// Näytetään kaikki
		$query	= "	SELECT sarjanumeroseuranta.*,
					if(sarjanumeroseuranta.lisatieto = '', tuote.nimitys, concat(tuote.nimitys, '<br><i>',left(sarjanumeroseuranta.lisatieto,50),'</i>')) nimitys,
					lasku_osto.tunnus				osto_tunnus,
					lasku_osto.nimi					osto_nimi,
					lasku_myynti.tunnus				myynti_tunnus,
					lasku_myynti.nimi				myynti_nimi,
					tilausrivi_osto.rivihinta		ostohinta,
					tilausrivi_osto.perheid2		osto_perheid2,
					tilausrivi_myynti.rivihinta		myyntihinta
					FROM sarjanumeroseuranta
					LEFT JOIN tuote use index (tuoteno_index) ON sarjanumeroseuranta.yhtio=tuote.yhtio and sarjanumeroseuranta.tuoteno=tuote.tuoteno
					LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
					LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
					LEFT JOIN lasku lasku_myynti use index (PRIMARY) ON lasku_myynti.yhtio=sarjanumeroseuranta.yhtio and lasku_myynti.tunnus=tilausrivi_myynti.otunnus
					LEFT JOIN lasku lasku_osto   use index (PRIMARY) ON lasku_osto.yhtio=sarjanumeroseuranta.yhtio and lasku_osto.tunnus=tilausrivi_osto.otunnus
					WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
					$lisa
					ORDER BY tuoteno, myyntirivitunnus";
		$sarjares = mysql_query($query) or pupe_error($query);
	
		if (file_exists('sarjanumeron_lisatiedot_popup.inc')) {
			require("sarjanumeron_lisatiedot_popup.inc");
		}
	
		while ($sarjarow = mysql_fetch_array($sarjares)) {

			$sarjarow["nimitys"] = str_replace("\n", "<br>", $sarjarow["nimitys"]);
		
			echo "<tr>";
			echo "<td valign='top'>$sarjarow[sarjanumero]</td>";
			echo "<td colspan='2' valign='top'>$sarjarow[tuoteno]<br>$sarjarow[nimitys]</td>";

			if ($sarjarow["ostorivitunnus"] == 0) {
				$sarjarow["ostorivitunnus"] = "";
			}
			if ($sarjarow["myyntirivitunnus"] == 0) {
				$sarjarow["myyntirivitunnus"] = "";
			} 
		
			echo "<td colspan='2' valign='top'><a href='../raportit/asiakkaantilaukset.php?toim=OSTO&tee=NAYTATILAUS&tunnus=$sarjarow[osto_tunnus]'>$sarjarow[osto_tunnus] $sarjarow[osto_nimi]</a><br><a href='../raportit/asiakkaantilaukset.php?toim=MYYNTI&tee=NAYTATILAUS&tunnus=$sarjarow[myynti_tunnus]'>$sarjarow[myynti_tunnus] $sarjarow[myynti_nimi]</a></td>";
		
			//Haetaan myös ns. lisävarusteiden hinnat
			if ($sarjarow["osto_perheid2"] != 0) {
				$query = "	select sum(rivihinta) rivihinta
							from tilausrivi
							where yhtio 	= '$yhtiorow[yhtio]'
							and perheid2 	= '$sarjarow[osto_perheid2]'
							and tyyppi 	   != 'D'
							and tunnus     != perheid2
							order by tunnus";
				$tilrivires = mysql_query($query) or pupe_error($query);
				$tilrivirow = mysql_fetch_array($tilrivires);
			}
			else {
				$tilrivirow = "";
			}
		
			$sarjarow["ostohinta"] 		= sprintf('%.2f', $sarjarow["ostohinta"]);
			$sarjarow["myyntihinta"] 	= sprintf('%.2f', $sarjarow["myyntihinta"]);
			$kulurow["summa"] 			= sprintf('%.2f', $kulurow["summa"]);
			$yhteensa = $sarjarow["myyntihinta"] - $sarjarow["ostohinta"] - $kulurow["summa"] - $tilrivirow["rivihinta"];
		
			echo "<td valign='top' align='right' nowrap>";
			if ($sarjarow["ostohinta"] != 0) 	echo "-$sarjarow[ostohinta]<br>";
			if ($tilrivirow["rivihinta"] != 0) 	echo "-$tilrivirow[rivihinta]<br>";
			if ($kulurow["summa"] != 0) 		echo "-$kulurow[summa]<br>";
			if ($sarjarow["myyntihinta"] != 0) 	echo "+$sarjarow[myyntihinta]<br>";
			if ($sarjarow["ostohinta"] != 0 or $kulurow["summa"] != 0 or $sarjarow["myyntihinta"] != 0) echo "=$yhteensa";
		
			echo "</td>";
	

			if (function_exists("sarjanumeronlisatiedot_popup")) {
				echo "<td>".sarjanumeronlisatiedot_popup ($sarjarow["tunnus"])."</td>";
			}
			
			echo "</tr>";
		}
	}
	echo "</table>";

	require ("../inc/footer.inc");
?>