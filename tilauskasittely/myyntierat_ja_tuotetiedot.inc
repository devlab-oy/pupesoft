<?php
// k‰ytet‰‰n slavea
$useslave = 1;

//if (isset($lajit)) echo "POLVIPƒƒLLIK÷<br>";
//if (isset($lajit)) setcookie("avainsanalaji_keksi", serialize($lajit), 52552, "/");

//if (isset($_COOKIE['avainsanalaji_keksi'])) $lajit_array = unserialize($_COOKIE['avainsanalaji_keksi']);
//else $lajit_array = $lajit;

//echo "MORJENS OLEN $lajit_array<br>";

if (!isset($lajit_array) and isset($lajit)) $lajit_array = $lajit;
if (isset($_POST['piirtele_valikko'])) {
	// piirrell‰‰n vaihtoehdot aina jos ei olla subattu rapsaa
	echo "PIIRTELYVALIKKOON MENTY";
	echo "<br><br>";
	echo "<font class='head'>".t("Myyntier‰t ja tuotetiedot")."</font>";
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
		$tsekk = "";
		foreach ($lajit_array as $osuma) {
			if ($osuma == $row['laji']) {
				$tsekk = "CHECKED";
				break;
			}
		}
		echo "<td align='left' valign='top' width='100'><input type='checkbox' name='lajit[]' value='{$row['laji']}' $tsekk>{$row['laji']}</td>";
		if ($secretcounter == 2) {
			echo "</tr>"; 
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
				<input type='submit' name='aja_ja_tallenna' value='".t("Aja rapsa")."'>
				</form>
				</td>
			</tr>";
	echo "</table>";
	echo "</form>";
}
elseif(isset($lajit_array) and count($lajit_array) > 0) {
	// t‰‰ll‰ ajellaan rapsa ja tallennetaan henkseliin
	$keyo = implode("','",$lajit_array);
	$keyo = "'".$keyo."'";

	//$kveeri = "SELECT * FROM tuotteen_avainsanat WHERE yhtio = '{$kukarow['yhtio']}' AND laji IN ({$keyo}) ORDER BY laji";
	//$ressu = pupe_query($kveeri);
	//if (mysql_num_rows($ressu) > 0) {
	
		include('inc/pupeExcel.inc');

		$worksheet 	 = new pupeExcel();
		$format_bold = array("bold" => TRUE);
		$excelrivi 	 = 0;
		$excelsarake = 0;

		$totaalisarakemaara = mysql_num_rows();
		
		$kveeri = "SELECT distinct laji FROM tuotteen_avainsanat WHERE yhtio = '{$kukarow['yhtio']}' AND laji IN ({$keyo}) ORDER BY laji";
		$ressu = pupe_query($kveeri);
		//piirret‰‰n sarakkeiden headerit
		$worksheet->write($excelrivi, $excelsarake++, t("Tuotenumero"),	$format_bold);
		while ($row = mysql_fetch_assoc($ressu)) {
			$worksheet->write($excelrivi, $excelsarake++, $row['laji'],	$format_bold);
		}

		$excelrivi++;
		
		//haetaan tilausrivit
		//$query = "	SELECT * FROM tilausrivi WHERE yhtio='{$kukarow['yhtio']}' AND otunnus = {$tilausnumero}";
		//$tilres = pupe_query($query);
		//query_dump($query);

		//loopataan myyntier‰tiedot per tilausrivi
		mysql_data_seek($tilausrivit_talteen, 0);
		while ($tilausrivi = mysql_fetch_assoc($tilausrivit_talteen)) {
			$query = "	SELECT tuotteen_avainsanat.*
						FROM tuotteen_avainsanat
						WHERE tuotteen_avainsanat.yhtio='{$kukarow['yhtio']}'
						AND tuotteen_avainsanat.tuoteno='{$tilausrivi['tuoteno']}'
						AND tuotteen_avainsanat.laji IN ($keyo)
						ORDER BY tuotteen_avainsanat.laji";
			$result = pupe_query($query);
			query_dump($query);
			$excelsarake = 0;

			$worksheet->write($excelrivi, $excelsarake++, $tilausrivi['tuoteno']);
			while ($selirivi = mysql_fetch_assoc($result)) {
				if (in_array($selirivi["laji"], $lajit_array)) {
					$worksheet->write($excelrivi, $excelsarake++, $selirivi["selite"]);
				}
				else {
					$worksheet->write($excelrivi, $excelsarake++, "");
				}
			}
			$excelrivi++;
		}
		$excelnimi = $worksheet->close();
	
	
	if (isset($excelnimi)) {
		echo "<br><br><table>";
		echo "<tr><th>",t("Tallenna tulos"),":</th>";
		echo "<form method='post' class='multisubmit'>";
		echo "<input type='hidden' name='tappi' value='lataa_tiedosto'>";
		echo "<input type='hidden' name='lopetus' value='$lopetus'>";
		echo "<input type='hidden' name='otunnus' value='$tilausnumero'>";
		echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
		echo "<input type='hidden' name='mista' value = '$mista'>";
		echo "<input type='hidden' name='toim_nimitykset' value='$toim_nimitykset'>";
		echo "<input type='hidden' name='toim' value='$toim'>";
		echo "<input type='hidden' name='kaunisnimi' value='Myyntieratiedot.xlsx'>";
		echo "<input type='hidden' name='tmpfilenimi' value='{$excelnimi}'>";
		echo "<td class='back'><input type='submit' value='",t("Tallenna"),"'></td></tr></form>";
		echo "</table><br>";
	}	
	else {
		echo t("Tallennus ep‰onnistui")."!<br>";
	}
}




