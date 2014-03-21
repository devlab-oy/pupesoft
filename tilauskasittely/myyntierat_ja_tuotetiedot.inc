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
	echo "<tr><th>".t("Valitse avainsanat ja kielet")."</th><td>";
	$query = "	SELECT DISTINCT kieli FROM tuotteen_avainsanat WHERE yhtio='{$yhtiorow['yhtio']}' and kieli != '{$yhtiorow['kieli']}'";
	$ressu = pupe_query($query);
	while ($row = mysql_fetch_assoc($ressu)) {
		echo "<input type='checkbox' name='kielet[]' value='{$row['kieli']}'> {$row['kieli']}";
	}
	echo "</td></tr>";
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

		//piirret‰‰n sarakkeiden headerit
		$worksheet->write($excelrivi, $excelsarake++, t("Tuotenumero"),	$format_bold);
		// 			nimitys
		// 			osasto
		// 			try
		// 			tuotemerkki
		// 			malli
		// 			mallitarkenne
		// 			kuvaus
		// 			lyhytkuvaus
		// 			mainosteksti
		// 			aleryhma
		// 			muuta
		// 			tilausrivi_kommentti
		// 			kerayskommentti
		// 			purkukommentti
		// 			myyntihinta
		// 			myyntihinta_maara
		// 			yksikko
		// 			ei_saldoa
		// 			kommentoitava
		// 			tuotetyyppi
		// 			myynninseuranta
		// 			hinnastoon
		// 			sarjanumeroseuranta
		// 			suoratoimitus
		// 			myymalahinta
		// 			nettohinta
		// 			halytysraja
		// 			varmuus_varasto
		// 			tilausmaara
		// 			ostoehdotus
		// 			tahtituote
		// 			tarrakerroin
		// 			tarrakpl
		// 			myynti_era
		// 			minimi_era
		// 			valmistuslinja
		// 			valmistusaika_sekunneissa
		// 			tullinimike1
		// 			vienti
		// 			tuotekorkeus
		// 			tuoteleveys
		// 			tuotesyvyys
		// 			tuotemassa
		// 			kuluprosentti
		// 			kuljetusohje
		// 			myyjanro
		// 			toiminto

		foreach ($lajit_array as $laji) {
			$worksheet->write($excelrivi, $excelsarake++, $laji, $format_bold);
			
			
		}

		$excelrivi++;

		//loopataan myyntier‰tiedot per tilausrivi
		mysql_data_seek($tilausrivit_talteen, 0);
		while ($tilausrivi = mysql_fetch_assoc($tilausrivit_talteen)) {
			$excelsarake = 0;
			
			$worksheet->write($excelrivi, $excelsarake++, $tilausrivi['tuoteno']);

			foreach ($lajit_array as $asanalaji) {
				
				$query = "	SELECT *
							FROM tuotteen_avainsanat
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND tuoteno = '{$tilausrivi['tuoteno']}'
							AND laji = '{$asanalaji}'";
				$result = pupe_query($query);

				if (mysql_num_rows($result) == 1) {
					$asanase = mysql_fetch_assoc($result);
					$worksheet->write($excelrivi, $excelsarake++, $asanase['selite']);
				}
				else $excelsarake++;

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




