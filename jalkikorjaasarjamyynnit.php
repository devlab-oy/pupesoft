<?php

	require('inc/parametrit.inc');
	
	echo "<font class='head'>".t("Korjaa sarjanumeromyyyntejä").":</font><hr><br>";
	
	if ($tee == "PAIVITA" and $paivamaara != '') {
		$query = "	SELECT distinct tilausrivi.tuoteno, sarjanumeroseuranta.ostorivitunnus
					FROM tilausrivi 
					JOIN tuote on tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno and tuote.sarjanumeroseuranta = 'S'
					JOIN sarjanumeroseuranta ON tilausrivi.yhtio = sarjanumeroseuranta.yhtio and tilausrivi.tuoteno = sarjanumeroseuranta.tuoteno and tilausrivi.tunnus = sarjanumeroseuranta.myyntirivitunnus
					WHERE tilausrivi.yhtio			= '$kukarow[yhtio]'
					and tilausrivi.tyyppi			= 'L'
					and tilausrivi.laskutettuaika  >= '$paivamaara'
					order by sarjanumeroseuranta.sarjanumero";
		$vresult = mysql_query($query) or pupe_error($query);

		while ($vrow = mysql_fetch_array($vresult)) {
			// Haetaan sarjanumeron ostohinta
			$ostohinta = sarjanumeron_ostohinta("ostorivitunnus", $vrow["ostorivitunnus"]);	
			
			echo "jalkilaskentafunktio($vrow[tuoteno], , $ostohinta, $vrow[ostorivitunnus]);<br>";
									
			jalkilaskentafunktio($vrow["tuoteno"], "", $ostohinta, $vrow["ostorivitunnus"]);
		}
	
		$tee = "";
	}
	
	if ($tee == "") {
		echo "<br><br>";
		echo "Syötä päivämäärä josta lähtien korjataan: (vvvv-kk-pp)<br>";
		echo "<form method='post' action='$PHP_SELF'>";
		echo "<input type='hidden' name='tee' value='PAIVITA'>";
		echo "<input type='text' name='paivamaara' size='10'>";
		echo "<input type='submit' value='Korjaa'>";
		echo "</form>";
	}
	
	require ("inc/footer.inc");
	
?>
