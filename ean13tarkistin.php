<?php
	$pupe_DataTables = array("eankoodi");
	require("inc/parametrit.inc");

	echo "<font class='head'>".t("Listataan kaikki tuotteet joiden EAN-koodi on viallinen")."</font><hr>\n";
	
	echo "<p>".t("Painamalla AJA-nappia, tehd‰‰n listaus tuotteista joiden ean13-koodi on syˆtetty mutta viallinen. T‰m‰ ajo voi kest‰‰ tovin.")."</p>";
	echo "<p>".t("Huom! mik‰li eankoodiksi on lyˆty joku muu viivakoodi, niin se n‰ytt‰‰ t‰ss‰ tulosteessa vialliselta")."</p>";			
	echo "<form method='post' action=''>";
	
	echo "<br><input type='submit' name='tee' value='aja'>";
	
	echo "</form><br>";
	
	if ($tee == "aja") {
		
		$query = " 	SELECT yhtio, eankoodi, tuoteno, nimitys, tunnus
					FROM tuote
					WHERE yhtio = '{$kukarow["yhtio"]}'
					AND status in ('A','E','T')
					AND eankoodi !=''";
		$result = pupe_query($query);
		$total = mysql_num_rows($result);
		$count = 0; 
		// piirret‰‰n taulua
		echo "<br>";
		pupe_DataTables(array(array($pupe_DataTables[0], 3, 4, true, true)));
		echo "<br>";
		echo "<table class='display dataTable' id='$pupe_DataTables[0]'>";
		echo "<thead>";
		echo "<tr>";
		echo "<th>".t("tuoteno")."</th>";
		echo "<th>".t("Nimitys")."</th>";
		echo "<th>".t("Eankoodi")."</th>";
		echo "<th>".t("Virhe")."</th>";
		echo "</tr>";
		echo "<tr>";
		echo "<td><input type='text' 	size='10' class='search_field' name='search_tuoteno_haku'></td>";
		echo "<td><input type='text' 	size='10' class='search_field' name='search_nimitys_haku'></td>";
		echo "<td><input type='text' 	size='10' class='search_field' name='search_eankoodi_haku'></td>";
		echo "<td><input type='hidden' 	size='10' class='search_field' name='search_virhe_haku'></td>";
		echo "</tr>";		
		echo "</thead>";
		echo "<tbody>";
		
		while ($rivi = mysql_fetch_assoc($result)) {
			$virhe = t("Viallinen ean13-koodi");
			$bool = "";
			$tuoteno = $rivi["tuoteno"];
			$ean = $rivi["eankoodi"];
			$nimitys = $rivi["nimitys"];
			
			if (tarkista_ean13($ean) === FALSE) {
				
				if (strlen($ean) != 13) {
					$virhe .= ",<br>".t("EAN-koodi v‰‰r‰npituinen")."";
				}
				$bool = preg_match('/[^0-9]/',$ean);
				if ($bool >0) {
					$virhe .= ",<br>".t("EAN-koodissa v‰‰ri‰ merkkej‰ tai v‰lilyˆnti")."";
				}
				
				echo "<tr>";
				echo "<td>$tuoteno</td>";
				echo "<td>$nimitys</td>";
				echo "<td>$ean</td>";
				echo "<td>{$virhe}</td>";
				echo "</tr>";
				$count++;
			}
		}
		
		$prosentti = round(($count/$total) * 100,2);
		echo "</tbody>";
		echo "</table>";
		echo "<br><p>".t("Kaikkien tuotteiden lukum‰‰r‰")." {$total}, ".t("prosenttim‰‰r‰")." {$prosentti}%</p>";
	}
	require("inc/footer.inc");
?>