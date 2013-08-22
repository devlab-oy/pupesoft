<?php

	//* Tämä skripti käyttää slave-tietokantapalvelinta *//
	$useslave = 1;

	// DataTables päälle
	$pupe_DataTables = "myyntilaskuhaku";

	if (isset($_GET["dtss"]) and $_GET["dtss"] == "TRUE") {
		$no_head = "yes";
	}

	require ("../inc/parametrit.inc");

	if (isset($_GET["dtss"]) and $_GET["dtss"] == "TRUE") {

		/* Array of database columns which should be read and sent back to DataTables. Use a space where
		 * you want to insert a non-database field (for example a counter or static image)
		 */
		$aColumns = array('tapvm', 'erpcm', 'laskunro', 'nimi', 'summa', 'valkoodi', 'ebid', 'tila', 'laatija');

		/*
		 * Indexed column (used for fast and accurate table cardinality)
		 */
		$sIndexColumn = "tunnus";

		/*
		 * DB table to use
		 */
		$sTable = "lasku";

		list($sUseIndex, $sInitialWhere, $sInitialOrder) = unserialize($_GET["serversideparams"]);

		require("server_processing_getdata.php");
		exit;
	}

	if (!isset($tee)) $tee = '';
	if (!isset($summa1)) $summa1 = '';
	if (!isset($summa2)) $summa2 = '';
	if (!isset($pvm)) $pvm = '';

	if (!function_exists("kuka_kayttaja")) {
		function kuka_kayttaja($keta_haetaan) {
			global $kukarow, $yhtiorow;

			$query = "	SELECT kuka.nimi
						FROM kuka
						WHERE kuka.yhtio = '{$kukarow['yhtio']}'
						AND kuka.kuka ='$keta_haetaan'";
			$kukares = pupe_query($query);
			$row = mysql_fetch_assoc($kukares);

			if ($row["nimi"] !="") {
				return $row["nimi"];
			}
			else {
				return $keta_haetaan;
			}
		}
	}

	echo "<font class='head'>",t("Myyntilaskuhaku"),"</font><hr>";

	$index = "";
	$lopetus = "${palvelin2}myyntires/myyntilaskuhaku.php////tee={$tee}//summa1={$summa1}//summa2={$summa2}";

	echo "<br><form name = 'valinta' method='post'>";

	$seldr = array_fill_keys(array($tee), " selected") + array('S','VS','N','V','L','LN');

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
	echo "<option value = 'LN' {$seldr["LN"]}>",t("Laatijan/myyjän nimellä"),"</option>";
	echo "</select></td>";
	echo "<td><input type = 'text' name = 'summa1' size='13'> - <input type = 'text' name = 'summa2' size='13'></td>";
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

	// LN = Etsitään myyjän tai laatijan nimellä
	if ($tee == 'LN') {
		// haetaan vain aktiivisia käyttäjiä
		$query = "	SELECT group_concat(distinct concat('\'',kuka.kuka,'\'')) kuka, group_concat(distinct concat(if(kuka.myyja=0, null, kuka.myyja))) myyja
					FROM kuka
					JOIN oikeu ON (oikeu.yhtio = kuka.yhtio AND oikeu.kuka = kuka.kuka)
					WHERE kuka.yhtio = '{$kukarow['yhtio']}'
					AND (kuka.kuka like '%$summa1%' or kuka.nimi like '%$summa1%')";
		$kukares = pupe_query($query);

		$row = mysql_fetch_assoc($kukares);

		if ($row["myyja"] !="") {
			$myyja = " or myyja in ({$row["myyja"]})";
		}

		// Jos ei löytynyt käyttäjistä niin kokeillaan hakusanalla
		if ($row["kuka"] == "") {
			$row["kuka"] = "'".$summa1."'";
		}

		$index = " use index (tila_index) ";
		$ehto = "tila = 'U' and (laatija in ({$row["kuka"]}) $myyja)";
		$jarj = "nimi, tapvm desc";
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

	if ($tee != '' and $ehto != "") {

		pupe_DataTables(array(array($pupe_DataTables, 9, 9, false, false, true, true, urlencode(serialize(array($index, $ehto, $jarj))))));

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
				<th>",t("Laatija"),"</th>
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
				<td><input type='text' class='search_field' name='search_laatija'></td>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td colspan='9' class='dataTables_empty'>".t("Ladataan")."...</td>
				</tr>
			</tbody>
			</table><br /><br />";

		$toim = "";
	}

	require ("inc/footer.inc");
