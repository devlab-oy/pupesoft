<?php

	$useslave = 1; // käytetään slavea

	require ("../inc/parametrit.inc");
	echo "<font class='head'>".t("Myyntilaskuhaku")."</font><hr>";

	$index = "";

	if ($tee == 'VS') { // S = Etsitään summaa laskulta
		$summa1 = str_replace( ",", ".", $summa1);
		$summa2 = str_replace( ",", ".", $summa2);
	
		if (strlen($summa2) == 0) {
			$summa2 = $summa1;
		}
	
		$summa1 += 0;
		$summa2 += 0;

		$ehto = "tila = 'U' and ";

		$index = " use index (yhtio_tila_summavaluutassa) ";
	
		if ($summa1 == $summa2) {
			$ehto .= "summa_valuutassa = " . $summa1;
			$jarj = "tapvm desc";
		}
		else {
			$ehto .= "summa_valuutassa >= " . $summa1 . " and summa_valuutassa <= " . $summa2;
			$jarj = "summa_valuutassa, tapvm";
		}
	}

	if ($tee == 'S') { // S = Etsitään summaa laskulta
		$summa1 = str_replace( ",", ".", $summa1);
		$summa2 = str_replace( ",", ".", $summa2);
	
		if (strlen($summa2) == 0) {
			$summa2 = $summa1;
		}
	
		$summa1 += 0;
		$summa2 += 0;

		$ehto = "tila = 'U' and ";

		$index = " use index (yhtio_tila_summa) ";
	
		if ($summa1 == $summa2) {
			$ehto .= "summa = " . $summa1;
			$jarj = "tapvm desc";
		}
		else {
			$ehto .= "summa >= " . $summa1 . " and summa <= " . $summa2;
			$jarj = "summa, tapvm";
		}
	}
	if ($tee == 'N') { // S = Etsitään nimeä laskulta
		$index = " use index (asiakasnimi) ";
		$ehto = "tila = 'U' and nimi like '%".$summa1."%'";
		$jarj = "nimi, tapvm desc";
	}

	if ($tee == 'V') { // V = viitteellä
		$ehto = "tila = 'U' and viite = '$summa1'";
		$jarj = "nimi, summa";
	}

	if ($tee == 'L') { // L = viitteellä
		$index = " use index (yhtio_tila_laskunro) ";
		$ehto = "tila = 'U' and  laskunro = '$summa1'";
		$jarj = "nimi, summa";
	}

	if ($tee != '') {
		$alku += 0;
	
		$query = "SELECT tapvm, erpcm, laskunro, concat_ws(' ', nimi, nimitark) nimi,
				  summa, valkoodi, ebid, tila, alatila, tunnus
				  FROM lasku $index
				  WHERE $ehto and yhtio='$kukarow[yhtio]'
				  ORDER BY $jarj
				  LIMIT $alku, 50";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			echo "<b>".t("Haulla ei löytynyt yhtään laskua")."</b>";
			$tee='';
		}
		else {
			echo "<table><tr>";
		
			for ($i = 0; $i < mysql_num_fields($result)-2; $i++) {
				echo "<th>" . t(mysql_field_name($result,$i))."</th>";
			}
			echo "</tr>";

			while ($trow = mysql_fetch_array ($result)) {
				echo "<tr class='aktiivi'>";

				if ($kukarow['taso'] < 2) {
					echo "<td valign='top'>".tv1dateconv($trow["tapvm"])."</td>";
				}
				else {
					echo "<td valign='top'><a href = '../muutosite.php?tee=E&tunnus=$trow[tunnus]'>".tv1dateconv($trow["tapvm"])."</td>";
				}
			
				echo "<td valign='top'>".tv1dateconv($trow["erpcm"])."</td>";
				echo "<td valign='top'><a href = '../tilauskasittely/tulostakopio.php?toim=LASKU&laskunro=$trow[laskunro]'>$trow[laskunro]</td>";
				echo "<td valign='top'>$trow[nimi]</td>";
				echo "<td valign='top' align='right'>$trow[summa]</td>";
				echo "<td valign='top'>$trow[valkoodi]</td>";
			
				// tehdään lasku linkki
				echo "<td>".ebid($trow['tunnus']) ."</td>";
			
				$laskutyyppi = $trow["tila"];
				$alatila     = $trow["alatila"];
				
				require "../inc/laskutyyppi.inc";
				
				echo "<td>".t("$laskutyyppi")." ".t("$alatila")."</td>";
				echo "</tr>";
			}
			echo "</table><br>";

			if ($alku > 0) {
				$siirry = $alku - 50;
				echo "<a href = '$PHP_SELF?tee=$tee&pvm=$pvm&summa1=$summa1&summa2=$summa2&alku=$siirry&itila=$itila&ialatila=$ialatila'>".t("Edelliset")."</a> ";
			}
			else {
				echo t("Edelliset")." ";
			}
		
			$siirry = $alku + 50;
			echo "<a href = '$PHP_SELF?tee=$tee&pvm=$pvm&summa1=$summa1&summa2=$summa2&alku=$siirry&itila=$itila&ialatila=$ialatila'>".t("Seuraavat")."</a> ";
			echo "<br><br>";
		
			$toim = "";
		}
	}

	if ($tee == '') {
		echo "<form name = 'valinta' action = '$PHP_SELF' method='post'>
				<table><tr>
				<td>Valitse lasku</td>
				<td><select name = 'tee'>
				<option value = 'S'>".t("summalla")."
				<option value = 'VS'>".t("valuuttasummalla")."
				<option value = 'N'>".t("nimellä")."
				<option value = 'V'>".t("viitteellä")."
				<option value = 'L'>".t("laskunnumerolla")."
				</select></td>
				<td><input type = 'text' name = 'summa1' size=8> - <input type = 'text' name = 'summa2' size=8></td>
				<td><input type = 'submit' value = '".t("Valitse")."'></td>
				</tr></table>
				</form>";
			
		$formi = 'valinta';
		$kentta = 'summa1';
	}

	require ("../inc/footer.inc");

?>