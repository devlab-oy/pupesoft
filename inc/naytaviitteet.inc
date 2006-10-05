<?php
	#tietuetunnus on ensimmäinen merkki
	# 0 -> erätietue
	# 3 -> viitesiirto
	# 5 -> suoraveloitus
	# 9 -> summatietue

	$type=substr($tietue,0,1);
	if ($type == 0) {
		echo "<table>";
		//echo "<td>Tietuetunnus</td><td>$type\n";
		echo "<tr><td>".t("Aineston luontipv")."</td><td>".substr($tietue,1,6)."</td>";
		echo "<td>".t("Aineston luontiaika")."</td><td>".substr($tietue,7,4)."</td>";
		echo "<td>".t("Rahalaitostunnus")."</td><td>".substr($tietue,11,2)."</td></tr>";
		echo "</table><br><table>";
		//echo "<td>Laskuttajan palvelutunnus</td><td>".substr($tietue,13,9)."</td>";
		//echo "<td>Rahayksikön koodi</td><td>".substr($tietue,22,1)."</td>";
		//echo "<td>Varalla</td><td>".substr($tietue,23,67)."</td>";
		echo "<tr><td>".t("Arkistotunnus")."</td>";
		echo "<td>".t("Tilinumero")."</td>";
		echo "<td>".t("Tapahtumapvm")."</td>";
		echo "<td>".t("Maksupvm")."</td>";
		echo "<td>".t("Maksaja")."</td>";
		echo "<td>".t("Summa")."</td>";
		echo "<td>".t("Viite")."</td>";
		echo "<td></td></tr>";
	} elseif ($type == 3) {
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
		echo "<td><font class='message'>".t("Tapahtuma on käsitelty")." $tiliotedatarow[kasitelty]<br>";
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
		$query = "SELECT kohdpvm, nimi FROM suoritus
					LEFT JOIN asiakas on asiakas_tunnus=asiakas.tunnus 
					WHERE suoritus.tunnus = '$tiliotedatarow[tiliointitunnus]'";
		$suoritusresult = mysql_query($query) or pupe_error($query);
		$suoritusrow = mysql_fetch_array ($suoritusresult);
		if ($suoritusrow['kohdpvm'] != '0000-00-00') 
			echo t("Suoritus on kohdistettu laskuun") . " " .$suoritusrow['kohdpvm'];
		else
			if (mysql_num_rows($suoritusresult)==1) {
				if ($suoritusrow['nimi'] != '')
					echo t("Kohdistettu asiakkaaseen ")."$suoritusrow[nimi]<br>";
				else
					echo t("Kohdistamatta asiakkaaseen ")."<br>";
			}
		echo "</font></td></tr>";
		
	} elseif ($type == 9) {
		echo "</table><br><table>";
		//echo "<tr><td>Tietuetunnus</td><td colspan='5'>$type</td>";
		echo "<tr><td></td><td>".t("Kpl")."</td><td>".t("Summa")."</td></tr>";
		echo "<tr><td>".t("Tapahtumia")."</td><td>".(int) substr($tietue,1,6)."</td><td>".round(substr($tietue,7,11)/100,2)."</td></tr>";
		echo "<tr><td>".t("Oikaisutapahtumia")."</td><td>".(int) substr($tietue,18,6)."</td><td>".round(substr($tietue,24,11)/100,2)."</td></tr>";
		echo "</table>";
		//echo "<td>Varalla</td><td>".substr($tietue,35,5)."</td></tr>";
	} else {
		echo "<td>".t("Tuntematon tietuetunnus")." $type</td>";
	}
?>
