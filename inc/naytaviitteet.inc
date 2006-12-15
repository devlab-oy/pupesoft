<?php
	#tietuetunnus on ensimmäinen merkki
	# 0 -> erätietue
	# 3 -> viitesiirto
	# 5 -> suoraveloitus
	# 9 -> summatietue

	$type=substr($tietue,0,1);
	
	if ($type == 0) {
		
		echo "<table>";
		echo "<tr><th>".t("Aineston luontipv")."</th><td>".substr($tietue,1,6)."</td></tr>";
		echo "<tr><th>".t("Aineston luontiaika")."</th><td>".substr($tietue,7,4)."</td></tr>";
		echo "<tr><th>".t("Rahalaitostunnus")."</th><td>".substr($tietue,11,2)."</td></tr>";
		echo "</table><br>";
		
		
		echo "<table>";
		//echo "<td>Laskuttajan palvelutunnus</td><td>".substr($tietue,13,9)."</td>";
		//echo "<td>Rahayksikön koodi</td><td>".substr($tietue,22,1)."</td>";
		//echo "<td>Varalla</td><td>".substr($tietue,23,67)."</td>";
		echo "<tr>";
		echo "<th>".t("Arkistotunnus")."</th>";
		echo "<th>".t("Tilinumero")."</th>";
		echo "<th>".t("Tapahtumapvm")."</th>";
		echo "<th>".t("Maksupvm")."</th>";
		echo "<th>".t("Maksaja")."</th>";
		echo "<th>".t("Summa")."</th>";
		echo "<th>".t("Viite")."</th>";
		echo "<th></th></tr>";
	} 
	elseif ($type == 3) {
		//echo "<td>Oikaisutunnus</td><td>".substr($tietue,87,1)."</td>";
		//echo "<td>Välitystapa</td><td>".substr($tietue,88,1)."</td>";
		//echo "<td>Palautekoodi</td><td>".substr($tietue,89,1)."</td>";
		//echo "<td>Maksajan nimen lähde</td><td>".substr($tietue,76,1)."</td>";


		echo "<tr><td>".substr($tietue,27,16)."</td>";
		echo "<td>".substr($tietue,1,14)."</td>";
		echo "<td>".substr($tietue,15,6)."</td>";
		echo "<td>".substr($tietue,21,6)."</td>";
		echo "<td>".substr($tietue,63,12)."</td>";
		echo "<td>". substr($tietue,77,10) / 100 ."</td>";
		echo "<td>".substr($tietue,43,20)."</td>";
		echo "<td>".t("Tapahtuma on käsitelty")." $tiliotedatarow[kasitelty]<br>";
		
		
		/* Tätä käytetään, jos käytössä on viitteet_laskuksi, muuten käytetään tuota ei kommentoitua
		$query = "SELECT tiliointi.tilino tilino, tili.nimi nimi, concat_ws('@',korjattu, korjausaika) korjattu, ltunnus
								FROM tiliointi
								LEFT JOIN tili on tili.yhtio=tiliointi.yhtio and tili.tilino=tiliointi.tilino
								WHERE tiliointi.tunnus = '$tiliotedatarow[tiliointitunnus]'";
		$tiliointiresult = mysql_query($query) or pupe_error($query);
		$tiliointirow = mysql_fetch_array ($tiliointiresult);
		echo "".t("Alkuperäinen tiliöinti on")." <a href='muutosite.php?tee=E&tunnus=$tiliointirow[ltunnus]'>$tiliointirow[tilino]/$tiliointirow[nimi]</a></font><br>";
		if (($tiliointirow['korjattu'] != "0000-00-00 00:00:00") and ($tiliointirow['korjattu'] != "@0000-00-00 00:00:00")) {
				echo "<br>".t("Tiliöinti korjattu")." $tiliointirow[korjattu]</font></td></tr>";
		}
		else {
				echo "</font></td></tr>";
		}
		*/
		
		$query = "	SELECT kohdpvm, nimi 
					FROM suoritus
					LEFT JOIN asiakas on asiakas_tunnus=asiakas.tunnus 
					WHERE suoritus.tunnus = '$tiliotedatarow[tiliointitunnus]'";
		$suoritusresult = mysql_query($query) or pupe_error($query);
		$suoritusrow = mysql_fetch_array ($suoritusresult);
		
		if ($suoritusrow['kohdpvm'] != '0000-00-00') { 
			echo t("Suoritus on kohdistettu laskuun") . " " .$suoritusrow['kohdpvm'];
		}
		else {
			if (mysql_num_rows($suoritusresult)==1) {
				if ($suoritusrow['nimi'] != '')
					echo t("Kohdistettu asiakkaaseen ")."$suoritusrow[nimi]<br>";
				else
					echo t("Kohdistamatta asiakkaaseen ")."<br>";
			}
			echo "</td></tr>";
		} 
	}
	elseif ($type == 9) {
		echo "</table><br><table>";
		echo "<tr>
				<td class='back'></td>
				<th>".t("Kpl")."</th>
				<th>".t("Summa")."</th>
				</tr>";
				
		echo "<tr><th>".t("Tapahtumia")."</th><td>".(int) substr($tietue,1,6)."</td><td>".round(substr($tietue,7,11)/100,2)."</td></tr>";
		echo "<tr><th>".t("Oikaisutapahtumia")."</th><td>".(int) substr($tietue,18,6)."</td><td>".round(substr($tietue,24,11)/100,2)."</td></tr>";
		echo "</table>";
		//echo "<td>Varalla</td><td>".substr($tietue,35,5)."</td></tr>";
	} 
	else {
		echo "<td>".t("Tuntematon tietuetunnus")." $type</td>";
	}
?>
