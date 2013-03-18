<?php
	#tietuetunnus on ensimmäinen merkki
	# 0 -> erätietue
	# 3 -> viitesiirto
	# 5 -> suoraveloitus
	# 9 -> summatietue
    # S -> Danske Bank factoring VIPN -rivi

	$type=substr($tietue,0,1);

	if ($type == '0' and substr($tietue, 0,3) != "\$\$E" and substr($tietue, 0,3) != "EOF") {

		$pvm = substr($tietue, 1, 6);
		$vv = substr($pvm,0,2)+2000;
		$kk = substr($pvm,2,2);
		$pp = substr($pvm,4,2);

		if (!isset($lopetus) or $lopetus == "") $lopetus = "/SPLIT/${palvelin2}tilioteselailu.php////tee=T//pvm=$vv-$kk-$pp//tyyppi=3//tilino=$tilino";

		echo "<table>";
		echo "<tr><td class='back'><a href='${palvelin2}tilioteselailu.php?tilino=$tiliotedatarow[tilino]&pvm=$tiliotedatarow[alku]&tee=XXS&lopetus=$lopetus$vii_lopetus'>&laquo; ".t("Edellinen")."</td><td class='back'><a href='${palvelin2}tilioteselailu.php?tilino=$tiliotedatarow[tilino]&pvm=$tiliotedatarow[alku]&tee=XS&lopetus=$lopetus$vii_lopetus'>".t("Seuraava")." &raquo;</a></td></tr>";
		echo "<tr><th>".t("Aineiston luontipv")."</th><td>".pankkipvm(substr($tietue,1,6))."</td></tr>";
		echo "<tr><th>".t("Aineiston luontiaika")."</th><td>".substr($tietue,7,4)."</td></tr>";
		echo "<tr><th>".t("Rahalaitostunnus")."</th><td>".substr($tietue,11,2)."</td></tr>";
		echo "<tr><th>".t("Aineisto käsitelty")."</th><td>".tv1dateconv($tiliotedatarow["kasitelty"])."</td></tr>";

		echo "</table><br>";

		echo "<table>";
		echo "<tr>";
		echo "<th>".t("Arkistotunnus")."</th>";
		echo "<th>".t("Tilinumero")."</th>";
		echo "<th>".t("Tapahtumapvm")."</th>";
		echo "<th>".t("Maksupvm")."</th>";
		echo "<th>".t("Maksaja")."</th>";
		echo "<th>".t("Summa")."</th>";
		echo "<th>".t("Viite")."</th>";
		echo "<th>".t("Tila")."</th></tr>";
	}
	elseif ($type == '3' or $type == 'S') {

		$viiteaineisto_summa = sprintf('%01.2f', substr($tietue, 77, 10) / 100);
		$viiteaineisto_viite = ltrim(substr($tietue, 43, 20), "0");
        if ($type == 'S') {
            $viiteaineisto_tilino = $tiliotedatarow['tilino'];
        }
        else {
            $viiteaineisto_tilino = substr($tietue,1,14);
        }

		echo "<tr class='aktiivi'><td>".substr($tietue,27,16)."</td>";
		echo "<td>".tilinumero_print($viiteaineisto_tilino)."</td>";
		echo "<td>".pankkipvm(substr($tietue,15,6))."</td>";
		echo "<td>".pankkipvm(substr($tietue,21,6))."</td>";
		echo "<td>".substr($tietue,63,12)."</td>";
		echo "<td align='right'>$viiteaineisto_summa</td>";
		echo "<td>$viiteaineisto_viite</td>";
		echo "<td>";

		$query = "	SELECT suoritus.kohdpvm, asiakas.nimi, ifnull(lasku.laskunro, 0) laskunro, lasku.tunnus
					FROM suoritus
					LEFT JOIN asiakas ON (suoritus.yhtio = asiakas.yhtio and suoritus.asiakas_tunnus = asiakas.tunnus)
					LEFT JOIN lasku ON (lasku.yhtio = suoritus.yhtio and lasku.tunnus = suoritus.ltunnus and lasku.tila = 'U')
					WHERE suoritus.yhtio = '$tiliotedatarow[yhtio]'
					AND suoritus.tunnus = '$tiliotedatarow[tiliointitunnus]'";
		$suoritusresult = mysql_query($query) or pupe_error($query);
		$suoritusrow = mysql_fetch_array ($suoritusresult);

		if ($suoritusrow['kohdpvm'] != '0000-00-00' and $suoritusrow["laskunro"] != 0) {
			echo "<font class='ok'>".t("Suoritus kohdistettu laskuun")." ";
			echo "<a href='${palvelin2}muutosite.php?tee=E&tunnus=$suoritusrow[tunnus]&lopetus=$lopetus$vii_lopetus'>$suoritusrow[laskunro]</a></font>";
		}
		else {
			if (mysql_num_rows($suoritusresult)==1) {
				if ($suoritusrow['nimi'] != '') {
					echo "<font class='error'>".t("Kohdistettu asiakkaaseen")." $suoritusrow[nimi]</font>";
				}
				else {
					echo "<font class='error'>".t("Kohdistamatta asiakkaaseen")."</font>";
				}
			}
		}
     	echo "</td></tr>";

	}
	elseif ($type == '9') {
		echo "</table><br><table>";
		echo "<tr>
				<td class='back'></td>
				<th>".t("Kpl")."</th>
				<th>".t("Summa")."</th>
				</tr>";

		echo "<tr><th>".t("Tapahtumia")."</th><td>".(int) substr($tietue,1,6)."</td><td>".sprintf("%01.2f",substr($tietue,7,11)/100)."</td></tr>";
		echo "<tr><th>".t("Oikaisutapahtumia")."</th><td>".(int) substr($tietue,18,6)."</td><td>".sprintf("%01.2f",substr($tietue,24,11)/100)."</td></tr>";
		echo "</table>";
		//echo "<td>Varalla</td><td>".substr($tietue,35,5)."</td></tr>";
	}
	elseif(substr($tietue, 0,3) != "\$\$E" and substr($tietue, 0,3) != "EOF") {
		echo "<td>".t("Tuntematon tietuetunnus")." $type</td>";
	}

