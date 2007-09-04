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
					and tilausrivi.kpl 				> 0
					and tilausrivi.laskutettuaika  >= '$paivamaara'
					order by sarjanumeroseuranta.sarjanumero";
		$vresult = mysql_query($query) or pupe_error($query);

		while ($vrow = mysql_fetch_array($vresult)) {
			// Haetaan sarjanumeron ostohinta
			$ostohinta = sarjanumeron_ostohinta("ostorivitunnus", $vrow["ostorivitunnus"]);	
			
			echo "jalkilaskentafunktio($vrow[tuoteno], , $ostohinta, $vrow[ostorivitunnus]);<br>";
									
			jalkilaskentafunktio($vrow["tuoteno"], "", $ostohinta, $vrow["ostorivitunnus"]);
		}
		
		/*
		$query = "	SELECT distinct tilausrivi.rivihinta/tilausrivi.kpl ostohinta, tilausrivi.tuoteno, sarjanumeroseuranta.ostorivitunnus, tilausrivin_lisatiedot.osto_vai_hyvitys, sarjanumeroseuranta.sarjanumero, sarjanumeroseuranta.kaytetty
					FROM tilausrivi 
					JOIN tuote on tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno and tuote.sarjanumeroseuranta = 'S'
					JOIN sarjanumeroseuranta ON tilausrivi.yhtio = sarjanumeroseuranta.yhtio and tilausrivi.tuoteno = sarjanumeroseuranta.tuoteno and tilausrivi.tunnus = sarjanumeroseuranta.ostorivitunnus
					LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
					WHERE tilausrivi.yhtio			= '$kukarow[yhtio]'
					and tilausrivi.tyyppi			= 'L'
					and tilausrivi.kpl 				< 0
					and tilausrivi.laskutettuaika  >= '$paivamaara'
					order by sarjanumeroseuranta.sarjanumero";
		$vresult = mysql_query($query) or pupe_error($query);

		while ($vrow = mysql_fetch_array($vresult)) {
			if ($vrow["osto_vai_hyvitys"] != 'O') {	
				// Haetaan hyvitettävien myyntirivien kautta alkuperäiset ostorivit
				$query  = "	SELECT sarjanumeroseuranta.*, tilausrivi.rivihinta/tilausrivi.kpl ostohinta
							FROM sarjanumeroseuranta
							JOIN tilausrivi use index (PRIMARY) ON tilausrivi.yhtio=sarjanumeroseuranta.yhtio and tilausrivi.tunnus=sarjanumeroseuranta.ostorivitunnus
							WHERE sarjanumeroseuranta.yhtio 	= '$kukarow[yhtio]' 
							and sarjanumeroseuranta.tuoteno 	= '$vrow[tuoteno]'
							and sarjanumeroseuranta.sarjanumero = '$vrow[sarjanumero]'
							and sarjanumeroseuranta.kaytetty 	= '$vrow[kaytetty]'
							and sarjanumeroseuranta.myyntirivitunnus > 0
							and sarjanumeroseuranta.ostorivitunnus   > 0
							ORDER BY sarjanumeroseuranta.tunnus DESC
							LIMIT 1";
				$sarjares1 = mysql_query($query) or pupe_error($query);
				$sarjarow1 = mysql_fetch_array($sarjares1);
				
				$kate = round($vrow["ostohinta"] - $sarjarow1["ostohinta"], 2);				
						
			
				echo "$vrow[tuoteno] $vrow[ostohinta] --> $sarjarow1[ostohinta] .. $kate<br>";
			}
		}
		*/
	
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
