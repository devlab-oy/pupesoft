<?php

include 'inc/parametrit.inc';

echo "<font class='head'>Korjaa kassa-alennusten ALV-vientejä:</font><hr><br>";


if ($tee == '') {	

	echo "<a href='$PHP_SELF?tee=KORJAA'>Suorita korjausajo</a><br>";

}

if ($tee != '') {

	/*
	
	//Testausta varten
	
	$con = mysql_pconnect("d60.arwidson.fi", "","") or die("Yhteys tietokantaan epäonnistui!");
	$dba = mysql_select_db('pupesoft', $con); 
	
	
	//Hard koodataan yhtio
	$kukarow["yhtio"] = "";
	
	//ja haetaan yhtiön tiedot uudestaan 
	$query = "	SELECT *
				FROM yhtio 
				WHERE yhtio = '$kukarow[yhtio]'";
	$zresult = mysql_query($query) or pupe_error($query);
	$yhtiorow=mysql_fetch_array ($zresult);
	*/
	
	//Tässä on kauden laskut joilla on kassa-alennusta suorituksessa
	$query = "	SELECT nimi, lasku.tunnus, lasku.summa summa, tiliointi.summa kasumma, lasku.mapvm, lasku.tapvm, lasku.laskunro 
				FROM lasku, tiliointi 
				WHERE lasku.yhtio = tiliointi.yhtio 
				and lasku.tunnus = tiliointi.ltunnus 
				and lasku.yhtio	= '$kukarow[yhtio]' 
				and tila = 'U' 
				and mapvm > '2004-09-30' 
				and tilino = '$yhtiorow[myynninkassaale]' 
				and korjattu = ''";
	$result = mysql_query($query) or pupe_error($query);
	
	echo "<font class='message'>Mahdollisia laskuja on: " . mysql_num_rows($result) . " kappaletta.</font><br><br>";
	
	echo "<table>";
	echo "<tr><th>Viesti</th><th>Laskunro</th><th>Asiakas</th><th>Summa</th><th>Kassa-alennus</th><th>ALV-%</th><th>Kassa-ale veroton</th><th>Kassa-alen ALV</th></tr>";
	
	while ($laskurow = mysql_fetch_array($result)) {
		//Onko tämä jo ok?
		$query = "	SELECT tunnus 
					FROM tiliointi 
					WHERE ltunnus = '$laskurow[tunnus]' 
					and yhtio = '$kukarow[yhtio]' 
					and tapvm = '$laskurow[mapvm]' 
					and tilino = '$yhtiorow[alv]' 
					and korjattu = ''";
		$zresult = mysql_query($query) or pupe_error($query);
		
		if (mysql_num_rows($zresult) > 0) {
			//echo "<font class='message'>Tämä lienee ok! $laskurow[tunnus]</font><br>";
			$oikein ++;
		}
		else {
			$totkasumma = 0;
			$query = "	SELECT * 
						FROM tiliointi 
						WHERE ltunnus 	 = '$laskurow[tunnus]' 
						and yhtio 		 = '$kukarow[yhtio]' 
						and tapvm 		 = '$laskurow[tapvm]'
						and abs(summa)  <> 0 
						and tilino 		<> '$yhtiorow[myyntisaamiset]' 
						and tilino 		<> '$yhtiorow[alv]' 
						and tilino 		<> '$yhtiorow[varasto]' 
						and tilino 		<> '$yhtiorow[varastonmuutos]' 
						and tilino 		<> '$yhtiorow[pyoristys]' 
						and tilino 		<> '$yhtiorow[kassaale]' 
						and korjattu 	 = ''";
			$yresult = mysql_query($query) or pupe_error($query);
			
			if (mysql_num_rows($yresult) == 0) { // Jotain meni pahasti pieleen = vanha lasku ilman tiliöintejä
							
				if (($laskurow['laskunro'] > 399999) and ($laskurow['laskunro'] < 500000) or
					($laskurow['laskunro'] > 599999) and ($laskurow['laskunro'] < 700000) or
					($laskurow['yhtio'] != 'arwi')) {
					
					// Tässä lienee alvia!
					$alv = 0;
					$summa = $laskurow['kasumma'];
					//$alv:ssa on alennuksen alv:n maara
					$alv = round($summa - $summa / (1 + (22 / 100)),2);
					//$summa on alviton alennus
					$summa -= $alv;
					
					echo "<tr><td class='green'>Luodaan:</td><td>$laskurow[laskunro]</td><td>$laskurow[nimi]</td><td>$laskurow[summa]</td><td>$laskurow[kasumma]</td><td></td><td>$summa</td><td>$alv</td></tr>";
					
					$query = "	SELECT * 
								FROM tiliointi 
								WHERE ltunnus = '$laskurow[tunnus]' 
								and yhtio = '$kukarow[yhtio]' 
								and tapvm = '$laskurow[mapvm]' 
								and tilino = $yhtiorow[myynninkassaale] 
								and summa = $laskurow[kasumma] 
								and korjattu = ''";
					$wresult = mysql_query($query) or pupe_error($query);
					
					if (mysql_num_rows($wresult) == 1) {
						
						$korjattavarow=mysql_fetch_array ($wresult);
						
						$query = "	UPDATE tiliointi 
									SET vero=22, summa='$summa' 
									WHERE yhtio='$kukarow[yhtio]' 
									and tunnus = '$korjattavarow[tunnus]'";
						$qresult = mysql_query($query) or pupe_error($query);					
						
						$query = "	INSERT into tiliointi SET yhtio ='$kukarow[yhtio]', ltunnus = '$korjattavarow[ltunnus]',
									tilino = '$yhtiorow[alv]', tapvm = '$korjattavarow[tapvm]', summa = '$alv',
									vero = '', selite = '$korjattavarow[selite]', lukko = '1',
									laatija = 'alv-korjaus', laadittu = '$korjattavarow[laadittu]',
									aputunnus = '$korjattavarow[tunnus]'";
						$qresult = mysql_query($query) or pupe_error($query);										
					}
					else {
						echo "<tr><td class='red'>Virhe:</td><<td>$laskurow[laskunro]</td>td colspan='6'>Laskun kassa-ale tiliöinti katosi!</td></tr>";
					}
				}
				else {
					echo "<tr><td class='red'>Huom:</td><td>$laskurow[laskunro]</td><td colspan='6'>Tämä taitaa olla vientilasku!</td></tr>";
				}
				$rikki ++;
			}
			else {
				$totkasumma = 0;
				
				// Nyt korjaamme vain tapauksia, jossa on yksi myyntitili
				if (mysql_num_rows($yresult) == 1) {
					
					while ($tiliointirow=mysql_fetch_array ($yresult)) {
						$alv = 0;
						$summa = round($tiliointirow['summa'] * -1 * (1+$tiliointirow['vero']/100) / $laskurow['summa'] * $laskurow['kasumma'],2);
						
						if ($tiliointirow['vero'] != 0) { // Netotetaan alvi
							//$alv:ssa on alennuksen alv:n maara
							$alv = round($summa - $summa / (1 + ($tiliointirow['vero'] / 100)),2);
							//$summa on alviton alennus
							$summa -= $alv;
						}
					
						// Etsitään korjattava vienti
						if ($alv != 0) {
							$korjaa ++;
							
							echo "<tr><td class='green'>Korjataan:</td><td>$laskurow[laskunro]</td><td>$laskurow[nimi]</td><td>$laskurow[summa]</td><td>$laskurow[kasumma]</td><td>$tiliointirow[vero]</td><td>$summa</td><td>$alv</td></tr>";
							
							$query = "	SELECT * 
										FROM tiliointi 
										WHERE ltunnus = '$laskurow[tunnus]' 
										and yhtio = '$kukarow[yhtio]' 
										and tapvm = '$laskurow[mapvm]' 
										and tilino = '$yhtiorow[myynninkassaale]' 
										and summa = '$laskurow[kasumma]'
										and korjattu = ''";
							$wresult = mysql_query($query) or pupe_error($query);
							
							if (mysql_num_rows($wresult) == 1) {
								
								$korjattavarow=mysql_fetch_array ($wresult);
								
								$query = "	UPDATE tiliointi
											SET vero=22, summa='$summa' 
											WHERE yhtio='$kukarow[yhtio]' 
											and tunnus = '$korjattavarow[tunnus]'";
								$qresult = mysql_query($query) or pupe_error($query);
															
								$query = "	INSERT into tiliointi 
											SET yhtio ='$kukarow[yhtio]', ltunnus = '$korjattavarow[ltunnus]',
											tilino = '$yhtiorow[alv]', tapvm = '$korjattavarow[tapvm]', summa = '$alv',
											vero = '', selite = '$korjattavarow[selite]', lukko = '1',
											laatija = 'alv-korjaus', laadittu = '$korjattavarow[laadittu]',
											aputunnus = '$korjattavarow[tunnus]'";
								$qresult = mysql_query($query) or pupe_error($query);
															
							}
							else {
								echo "<tr><td class='red'>Virhe:</td><td>$laskurow[laskunro]</td><td colspan='6'>Laskun kassa-ale tiliöinti katosi!</td></tr>";
							}
						}
						
						// Kuinka plajon olemme kumulatiivisesti tiliöineet
						$totkasumma += $summa + $alv;
					}
					$heitto = round($totkasumma - $laskurow['kasumma'],2);
					
					if (abs($heitto) >= 0.01) {
						echo "<tr><td class='red'>Huom:</td><td>$laskurow[laskunro]</td><td colspan='6'>Laskulle muodostui kassa-alen alvpyöristysvirhe $heitto. </td></tr>";
					}
				}
				else {
					//Jaaha jos ne kaikki ovatkin alvittomia. Silloin kaikki on ok.
					$jotainvaarin=0;
					while ($tiliointirow=mysql_fetch_array ($yresult)) {
						if ($tiliointirow['vero'] != 0) $jotainvaarin=1;
					}
					if ($jotainvaarin==1) 
						echo "<tr><td class='red'>Virhe:</td><td>$laskurow[laskunro]</td><td colspan='6'>Laskulla on monta myyntitiliä/alvia ja vero != 0. Ei voida korjata!</td></tr>";
				}
				
			} // if (mysql_num_rows($yresult) == 0) else haara end
	
		} // if (mysql_num_rows($zresult) > 0 else haara end
	
	} // while laskurow end
	echo "</table><br><br>";
	echo "Oikein $oikein Korjattavia $korjaa Vanhoja $rikki";
}

require ("inc/footer.inc");
?>
