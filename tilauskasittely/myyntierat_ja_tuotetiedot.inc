<?php

// käytetään slavea
$useslave = 1;

if (isset($_POST["tee"])) {
	if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
	if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
}
echo "<br><br>";
echo "<font class='head'>".t("Myyntierät ja tuotetiedot")."</font>";
echo "<br><br>";

$query = "	SELECT distinct laji
			FROM tuotteen_avainsanat
			WHERE yhtio='{$kukarow['yhtio']}'
			ORDER BY laji";
$result = pupe_query($query);

echo "<table>";
echo "<tr><th>".t("Valitse sarakkeet")."</th></tr>";
while ($row = mysql_fetch_assoc($result)) {
	echo "<tr><td>{$row['laji']}<input type='checkbox' name='lajit[]' value='{$row['laji']}' class='right'></td></tr>";
}
echo "</table>";

if (isset($tee) and $tee == "lataa_tiedosto") {
	readfile("/tmp/".$tmpfilenimi);
	exit;
}

if (isset($tee) and $tee == "JOTAINKIVAA") {
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


