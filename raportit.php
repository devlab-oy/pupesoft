<?php

///* Tämä skripti käyttää slave-tietokantapalvelinta *///
if (isset($_POST["tee"])) {
	if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
	if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
}

if ((($toim != 'hyvaksynta') or ($tee != 'T')) and ($toim !='maksuvalmius')) $useslave = 1;
require ("inc/parametrit.inc");

if (isset($tee) and $tee == "lataa_tiedosto") {
	readfile("/tmp/".$tmpfilenimi);
	exit;
}


if($excel == "YES") {
	if(include('Spreadsheet/Excel/Writer.php')) {
		
		//keksitään failille joku varmasti uniikki nimi:
		list($usec, $sec) = explode(' ', microtime());
		mt_srand((float) $sec + ((float) $usec * 100000));
		$excelnimi = md5(uniqid(mt_rand(), true)).".xls";

		$workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
		$worksheet =& $workbook->addWorksheet('Sheet 1');

		$format_bold =& $workbook->addFormat();
		$format_bold->setBold();

		$excelrivi = 0;		
	}	
}

require ("inc/".$toim.".inc");

if(isset($workbook) and $excelrivi>0) {
	// We need to explicitly close the workbook
	$workbook->close();

	echo "<table>";
	echo "<tr><th>".t("Tallenna tulos").":</th>";
	echo "<form method='post' action='$PHP_SELF'>";
	echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
	echo "<input type='hidden' name='kaunisnimi' value='".ucfirst(strtolower($toim)).".xls'>";
	echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
	echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
	echo "</table><br>";
}

require ("inc/footer.inc");
?>
