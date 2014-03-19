<?php
// käytetään slavea
$useslave = 1;

// if (isset($_POST["ajannyt"])) {
// 	if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
// 	if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
// }
if (isset($lajit)) setcookie('avainsanalaji_keksi', serialize($lajit), strtotime('+30 days'), "/");
if (isset($_COOKIE['avainsanalaji_keksi'])) $lajit_array = unserialize($_COOKIE['avainsanalaji_keksi']);

if (!isset($aja_ja_tallenna) and !isset($_REQUEST['lajit'])) {
	// piirrellään vaihtoehdot aina jos ei olla subattu rapsaa
	echo "<br><br>";
	echo "<font class='head'>".t("Myyntierät ja tuotetiedot")."</font>";
	echo "<br><br>";

	$query = "	SELECT distinct laji
				FROM tuotteen_avainsanat
				WHERE yhtio = '{$kukarow['yhtio']}'
				ORDER BY laji";
	$result = pupe_query($query);

	echo "<form>";
	echo "<table border='0' cellpadding='5' cellspacing='0' width='600'>";
	echo "<tr><th>".t("Valitse sarakkeet")."</th></tr>";
	$secretcounter = 0;
	$eka_ajo = true;
	while ($row = mysql_fetch_assoc($result)) {
		//KISSA moro poista lengthrivi - debuggia
		if (strlen($row['laji']) < 4) continue;
		$nollaus = false;
		if ($secretcounter == 0) echo "<tr>";
		$tsekk = isset($lajit_array["{$row['laji']}"]) ? "CHECKED" : "";
		echo "<td align='left' valign='top' width='100'><input type='checkbox' name='lajit[]' value='{$row['laji']}' $tsekk>{$row['laji']}</td>";
		if ($secretcounter == 2) echo "</tr>";
		if ($secretcounter == 2) { 
			$secretcounter = 0;
			$nollaus = true;
		}
		$eka_ajo = false;
		if (!$nollaus) $secretcounter++;	
	}
	echo "<br><br>";
	echo "	<tr>
		  		<td align='left' class='back' valign='top'>
				<form name='aja_ja_tallenna' method='post'>
				<input type='hidden' name='lopetus' value='$lopetus'>
				<input type='hidden' name='otunnus' value='$tilausnumero'>
				<input type='hidden' name='tilausnumero' value='$tilausnumero'>
				<input type='hidden' name='mista' value = '$mista'>
				<input type='hidden' name='toim_nimitykset' value='$toim_nimitykset'>
				<input type='hidden' name='toim' value='$toim'>
				<input type='hidden' name='naantali' value='KIVAPAIKKA'>
				<input type='submit' name='aja_ja_tallenna' value='".t("Tallenna tilauksen myyntierätiedot exceliin")."'>
				</form>
				</td>
			</tr>";
			echo "</table>";
	echo "</form>";
}
else {
	// täällä ajellaan rapsa ja tallennetaan henkseliin
	$ressu = "kissa";
	if (mysql_num_rows($ressu) > 0) {

		include('inc/pupeExcel.inc');

		$worksheet 	 = new pupeExcel();
		$format_bold = array("bold" => TRUE);
		$excelrivi 	 = 0;
		$excelsarake = 0;

		$query = "	SELECT distinct laji
					FROM tuotteen_avainsanat
					WHERE yhtio='{$kukarow['yhtio']}'
					ORDER BY laji";

		$ressu = pupe_query($query);

		$totaalisarakemaara = mysql_num_rows();

		while ($row = mysql_fetch_assoc($ressu)) {
			$worksheet->write($excelrivi, $excelsarake++, $row['laji'],	$format_bold);
		}

		$excelrivi++;

		$query = "	SELECT *
					FROM tuotteen_avainsanat
					WHERE yhtio='{$kukarow['yhtio']}'
					ORDER BY laji";
		while ($row = mysql_fetch_assoc($result)) {

			$excelsarake = 0;
			$worksheet->write($excelrivi, $excelsarake++, $row["kukanimi"]);
			$excelrivi++;
		}

		$excelnimi = $worksheet->close();
	}	
}

// if (isset($tee) and $tee == "lataa_tiedosto") {
// 	readfile("/tmp/".$tmpfilenimi);
// 	exit;
// }



