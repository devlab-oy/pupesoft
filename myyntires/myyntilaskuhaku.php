<?php

	$useslave = 1; // käytetään slavea

	// DataTables päälle
	$pupe_DataTables = "myyntilaskuhaku";

	require ("../inc/parametrit.inc");

	echo "<font class='head'>".t("Myyntilaskuhaku")."</font><hr>";

	$index = "";
	$lopetus = "${palvelin2}myyntires/myyntilaskuhaku.php////tee=$tee//summa1=$summa1//summa2=$summa2";

	// VS = Etsitään valuuttasummaa laskulta
	if ($tee == 'VS') {
		
		if (strlen($summa2) == 0) {
			$summa2 = $summa1;
		}
		
		$summa1 = (float) str_replace( ",", ".", $summa1);
		$summa2 = (float) str_replace( ",", ".", $summa2);
		
		$ehto = "tila = 'U' and ";
		$index = " use index (yhtio_tila_summavaluutassa) ";

		if ($summa1 == $summa2) {						
			$ehto .= "summa_valuutassa in ($summa1, ".($summa1*-1).") ";
			$jarj = "tapvm desc";
		}
		else {
			$ehto .= "summa_valuutassa >= " . $summa1 . " and summa_valuutassa <= " . $summa2;
			$jarj = "summa_valuutassa, tapvm";
		}
	}

	// S = Etsitään summaa laskulta
	if ($tee == 'S') {
		
		if (strlen($summa2) == 0) {
			$summa2 = $summa1;
		}
		
		$summa1 = (float) str_replace(",", ".", $summa1);
		$summa2 = (float) str_replace(",", ".", $summa2);

		$ehto = "tila = 'U' and ";
		$index = " use index (yhtio_tila_summa) ";

		if ($summa1 == $summa2) {
			$ehto .= "summa in ($summa1, ".($summa1*-1).") ";
			$jarj = "tapvm desc";
		}
		else {
			$ehto .= "summa >= $summa1 and summa <= $summa2";
			$jarj = "summa, tapvm";
		}
	}

	// N = Etsitään nimeä laskulta
	if ($tee == 'N') {
		$index = " use index (asiakasnimi) ";
		$ehto = "tila = 'U' and nimi like '%".$summa1."%'";
		$jarj = "nimi, tapvm desc";
	}

	// V = viitteellä
	if ($tee == 'V') {
		$ehto = "tila = 'U' and viite = '$summa1'";
		$jarj = "nimi, summa";
	}

	// L = laskunumerolla
	if ($tee == 'L') {
		$index = " use index (yhtio_tila_laskunro) ";
		$ehto = "tila = 'U' and laskunro = '$summa1'";
		$jarj = "nimi, summa";
	}

	if ($tee != '') {
		$alku += 0;

		$query = "	SELECT tapvm, erpcm, laskunro, concat_ws(' ', nimi, nimitark) nimi,
					summa, valkoodi, ebid, tila, alatila, tunnus
					FROM lasku $index
					WHERE $ehto and yhtio='$kukarow[yhtio]'
					ORDER BY $jarj
					LIMIT $alku, 50";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			echo "<b>".t("Haulla ei löytynyt yhtään laskua")."</b>";
			$tee = '';
		}
		else {

			pupe_DataTables($pupe_DataTables, 8, 8);

			echo "<table class='display' id='$pupe_DataTables'>";

			echo "<thead>
					<tr>
					<th>".t("Pvm")."</th>
					<th>".t("Eräpäivä")."</th>
					<th>".t("Laskunro")."</th>
					<th>".t("Nimi")."</th>
					<th>".t("Summa")."</th>
					<th>".t("Valuutta")."</th>
					<th>".t("Ebid")."</th>
					<th>".t("Tila")."</th>
					</tr>
					<tr>
					<td><input type='text' name='search_pvm'></td>
					<td><input type='text' name='search_erpvm'></td>
					<td><input type='text' name='search_laskunro'></td>
					<td><input type='text' name='search_nimi'></td>
					<td><input type='text' name='search_summa'></td>
					<td><input type='text' name='search_valuutta'></td>
					<td><input type='text' name='search_ebid'></td>
					<td><input type='text' name='search_tila'></td>
					</tr>
				</thead>";

			echo "<tbody>";

			while ($trow = mysql_fetch_array ($result)) {
				echo "<tr class='aktiivi'>";

				if ($kukarow['taso'] < 2) {
					echo "<td valign='top'>".tv1dateconv($trow["tapvm"])."</td>";
				}
				else {
					echo "<td valign='top'><a href = '../muutosite.php?tee=E&tunnus=$trow[tunnus]&lopetus=$lopetus'>".tv1dateconv($trow["tapvm"])."</td>";
				}

				echo "<td valign='top'>".tv1dateconv($trow["erpcm"])."</td>";
				echo "<td valign='top'><a href = '../tilauskasittely/tulostakopio.php?toim=LASKU&tee=ETSILASKU&laskunro=$trow[laskunro]&lopetus=$lopetus'>$trow[laskunro]</td>";
				echo "<td valign='top'>$trow[nimi]</td>";
				echo "<td valign='top' align='right'>$trow[summa]</td>";
				echo "<td valign='top'>$trow[valkoodi]</td>";

				// tehdään lasku linkki
				echo "<td>".ebid($trow['tunnus']) ."</td>";

				$laskutyyppi = $trow["tila"];
				$alatila     = $trow["alatila"];

				require "inc/laskutyyppi.inc";

				echo "<td>".t("$laskutyyppi")." ".t("$alatila")."</td>";
				echo "</tr>";
			}

			echo "</tbody>";
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

		echo "<form name = 'valinta' action = '$PHP_SELF' method='post'>";

		echo "<table>";
		echo "<tr>";
		echo "<th>".t("Etsi lasku")."</th>";
		echo "<td><select name = 'tee'>";
		echo "<option value = 'S'>".t("summalla")."</option>";
		echo "<option value = 'VS'>".t("valuuttasummalla")."</option>";
		echo "<option value = 'N'>".t("nimellä")."</option>";
		echo "<option value = 'V'>".t("viitteellä")."</option>";
		echo "<option value = 'L'>".t("laskunnumerolla")."</option>";
		echo "</select></td>";
		echo "<td><input type = 'text' name = 'summa1' size=13> - <input type = 'text' name = 'summa2' size=13></td>";
		echo "<td class='back'><input type = 'submit' value = '".t("Hae")."'></td>";
		echo "</tr>";
		echo "</table>";

		echo "</form>";

		$formi = 'valinta';
		$kentta = 'summa1';
	}

	require ("inc/footer.inc");

?>
