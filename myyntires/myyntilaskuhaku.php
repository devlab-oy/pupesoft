<?php

	//* Tämä skripti käyttää slave-tietokantapalvelinta *//
	$useslave = 1;

	// DataTables päälle
	$pupe_DataTables = "myyntilaskuhaku";

	require ("../inc/parametrit.inc");

	if (!isset($tee)) $tee = '';
	if (!isset($summa1)) $summa1 = '';
	if (!isset($summa2)) $summa2 = '';
	if (!isset($alku)) $alku = 0;
	if (!isset($pvm)) $pvm = '';
	if (!isset($itila)) $itila = '';
	if (!isset($ialatila)) $ialatila = '';

	echo "<font class='head'>",t("Myyntilaskuhaku"),"</font><hr>";

	$index = "";
	$lopetus = "${palvelin2}myyntires/myyntilaskuhaku.php////tee={$tee}//summa1={$summa1}//summa2={$summa2}";

	echo "<br><form name = 'valinta' action = '' method='post'>";

	$seldr = array_fill_keys(array($tee), " selected") + array('S','VS','N','V','L');

	echo "<table>";
	echo "<tr>";
	echo "<th>",t("Etsi lasku"),"</th>";
	echo "<td><select name = 'tee'>";
	echo "<option value = 'S'  {$seldr["S"]}>",t("summalla"),"</option>";
	echo "<option value = 'VS' {$seldr["VS"]}>",t("valuuttasummalla"),"</option>";
	echo "<option value = 'N'  {$seldr["N"]}>",t("nimellä"),"</option>";
	echo "<option value = 'V'  {$seldr["V"]}>",t("viitteellä"),"</option>";
	echo "<option value = 'L'  {$seldr["L"]}>",t("laskunnumerolla"),"</option>";
	echo "<option value = 'A'  {$seldr["A"]}>",t("asiakasnumerolla"),"</option>";
	echo "</select></td>";
	echo "<td><input type = 'text' name = 'summa1' size=13> - <input type = 'text' name = 'summa2' size=13></td>";
	echo "<td class='back'><input type = 'submit' value = '",t("Hae"),"'></td>";
	echo "</tr>";
	echo "</table>";
	echo "</form>";
	echo "<hr>";

	$formi = 'valinta';
	$kentta = 'summa1';

	if (trim($summa1) == "") {
		$tee = "";
	}

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
			$ehto .= "summa_valuutassa in ({$summa1}, ".($summa1*-1).") ";
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
			$ehto .= "summa in ({$summa1}, ".($summa1*-1).") ";
			$jarj = "tapvm desc";
		}
		else {
			$ehto .= "summa >= {$summa1} and summa <= {$summa2}";
			$jarj = "summa, tapvm";
		}
	}

	// N = Etsitään nimeä laskulta
	if ($tee == 'N') {
		$index = " use index (asiakasnimi) ";
		$ehto = "tila = 'U' and nimi like '%".$summa1."%'";
		$jarj = "nimi, tapvm desc";
	}

	// A = Etsitään asiakannumeroa laskulta
	if ($tee == 'A') {
		$query	= "	SELECT group_concat(tunnus) asiakkaat
					FROM asiakas
					WHERE yhtio = '{$kukarow['yhtio']}'
					and asiakasnro = '$summa1'
					and asiakasnro not in ('0','')";
		$result = pupe_query($query);
		$row = mysql_fetch_assoc($result);

		$liitostunnus = -1;

		if ($row["asiakkaat"] != "") {
			$liitostunnus = $row["asiakkaat"];
		}

		$index = "";
		$ehto = "tila = 'U' and liitostunnus in ($liitostunnus)";
		$jarj = "nimi, tapvm desc";
	}

	// V = viitteellä
	if ($tee == 'V') {
		$ehto = "tila = 'U' and viite = '{$summa1}'";
		$jarj = "nimi, summa";
	}

	// L = laskunumerolla
	if ($tee == 'L') {
		$index = " use index (yhtio_tila_laskunro) ";
		$ehto = "tila = 'U' and (laskunro = '".abs($summa1)."' or laskunro = '-".abs($summa1)."')";
		$jarj = "nimi, summa";
	}

	if ($tee != '') {
		$alku += 0;

		$query = "	SELECT tapvm, erpcm, laskunro, concat_ws(' ', nimi, nimitark) nimi,
					summa, valkoodi, ebid, tila, alatila, tunnus,
					mapvm, saldo_maksettu, ytunnus, liitostunnus
					FROM lasku {$index}
					WHERE {$ehto} and yhtio = '{$kukarow['yhtio']}'
					ORDER BY {$jarj}
					LIMIT {$alku}, 50";
		$result = pupe_query($query);

		if (mysql_num_rows($result) == 0) {
			echo "<b>",t("Haulla ei löytynyt yhtään laskua"),"</b>";
			$tee = '';
		}
		else {

			pupe_DataTables(array(array($pupe_DataTables, 8, 8)));

			echo "<table class='display dataTable' id='{$pupe_DataTables}'>";

			echo "<thead>
					<tr>
					<th>",t("Pvm"),"</th>
					<th>",t("Eräpäivä"),"</th>
					<th>",t("Laskunro"),"</th>
					<th>",t("Nimi"),"</th>
					<th>",t("Summa"),"</th>
					<th>",t("Valuutta"),"</th>
					<th>",t("Ebid"),"</th>
					<th>",t("Tila"),"</th>
					</tr>
					<tr>
					<td><input type='text' class='search_field' name='search_pvm'></td>
					<td><input type='text' class='search_field' name='search_erpvm'></td>
					<td><input type='text' class='search_field' name='search_laskunro'></td>
					<td><input type='text' class='search_field' name='search_nimi'></td>
					<td><input type='text' class='search_field' name='search_summa'></td>
					<td><input type='text' class='search_field' name='search_valuutta'></td>
					<td><input type='text' class='search_field' name='search_ebid'></td>
					<td><input type='text' class='search_field' name='search_tila'></td>
					</tr>
				</thead>";

			echo "<tbody>";

			while ($trow = mysql_fetch_assoc($result)) {
				echo "<tr class='aktiivi'>";

				if ($kukarow['taso'] < 2) {
					echo "<td valign='top'>",tv1dateconv($trow["tapvm"]),"</td>";
				}
				else {
					echo "<td valign='top'><a href = '../muutosite.php?tee=E&tunnus={$trow['tunnus']}&lopetus={$lopetus}'>",tv1dateconv($trow["tapvm"]),"</td>";
				}

				echo "<td valign='top'>",tv1dateconv($trow["erpcm"]),"</td>";
				echo "<td valign='top'><a href = '../tilauskasittely/tulostakopio.php?toim=LASKU&tee=ETSILASKU&laskunro={$trow['laskunro']}&lopetus={$lopetus}'>{$trow['laskunro']}</td>";
				echo "<td valign='top'><a name='$trow[tunnus]' href='".$palvelin2."myyntires/myyntilaskut_asiakasraportti.php?ytunnus=$trow[ytunnus]&asiakasid=$trow[liitostunnus]&alatila=Y&tila=tee_raportti&lopetus={$lopetus}'>{$trow['nimi']}</a></td>";
				echo "<td valign='top' align='right'>{$trow['summa']}</td>";
				echo "<td valign='top'>{$trow['valkoodi']}</td>";

				// tehdään lasku linkki
				echo "<td>",ebid($trow['tunnus']),"</td>";

				$maksuviesti = "";

				if ($trow['mapvm'] != "0000-00-00") {
					$maksuviesti = t("Maksettu");
				}
				elseif ($trow['mapvm'] == "0000-00-00" and $trow['saldo_maksettu'] != 0) {
					$maksuviesti = t("Osasuoritettu");

					if ($trow['mapvm'] == "0000-00-00" and str_replace("-", "", $trow['erpcm']) < date("Ymd")) {
						$maksuviesti .= " / ".t("Erääntynyt");
					}
				}
				elseif ($trow['mapvm'] == "0000-00-00" and str_replace("-", "", $trow['erpcm']) < date("Ymd")) {
					$maksuviesti = " ".t("Erääntynyt");
				}
				else {
					$maksuviesti = t("Avoin");
				}

				echo "<td>$maksuviesti</td>";
				echo "</tr>";
			}

			echo "</tbody>";
			echo "</table><br />";

			if ($alku > 0) {
				$siirry = $alku - 50;
				echo "<a href = '?tee={$tee}&pvm={$pvm}&summa1={$summa1}&summa2={$summa2}&alku={$siirry}&itila={$itila}&ialatila={$ialatila}'>",t("Edelliset"),"</a> ";
			}
			else {
				echo t("Edelliset")," ";
			}

			$siirry = $alku + 50;
			echo "<a href = '?tee={$tee}&pvm={$pvm}&summa1={$summa1}&summa2={$summa2}&alku={$siirry}&itila={$itila}&ialatila={$ialatila}'>",t("Seuraavat"),"</a> ";
			echo "<br /><br />";

			$toim = "";
		}
	}

	require ("inc/footer.inc");

?>