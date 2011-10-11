<?php

///* Tämä skripti käyttää slave-tietokantapalvelinta *///
if (isset($_POST["tee"])) {
	if ($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
	if ($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
}

if ((($_REQUEST["toim"] != 'hyvaksynta') or ($_REQUEST["tee"] != 'T')) and ($_REQUEST["toim"] !='maksuvalmius')) $useslave = 1;


if ($_REQUEST["toim"] == 'avoimet') {
	// DataTables päälle
	$pupe_DataTables = array("avoimet0", "avoimet1");
}

if ($_REQUEST["toim"] == 'toimittajahaku' or $_REQUEST["toim"] == 'laskuhaku' or $_REQUEST["toim"] == 'myyrespaakirja') {
	// DataTables päälle
	$pupe_DataTables = $_REQUEST["toim"];
}

require ("inc/parametrit.inc");

if (isset($tee) and $tee == "lataa_tiedosto") {
	readfile("/tmp/".$tmpfilenimi);
	exit;
}

// Livesearch jutut
enable_ajax();

if (!isset($excel)) 		 $excel = "";
if (!isset($livesearch_tee)) $livesearch_tee = "";

if ($livesearch_tee == "TILIHAKU") {
	livesearch_tilihaku();
	exit;
}

if ($excel == "YES") {
	if (include('Spreadsheet/Excel/Writer.php')) {

		//keksitään failille joku varmasti uniikki nimi:
		list($usec, $sec) = explode(' ', microtime());
		mt_srand((float) $sec + ((float) $usec * 100000));
		$excelnimi = md5(uniqid(mt_rand(), true)).".xls";

		$workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
		$workbook->setVersion(8);
		$worksheet =& $workbook->addWorksheet('Sheet 1');

		$format_bold =& $workbook->addFormat();
		$format_bold->setBold();

		$excelrivi = 0;
	}
}

require ("inc/".$toim.".inc");

if (isset($workbook) and $excelrivi>0) {
	// We need to explicitly close the workbook
	$workbook->close();

	echo "<br><br><table>";
	echo "<tr><th>".t("Tallenna Excel").":</th>";
	echo "<form method='post' action='$PHP_SELF'>";
	echo "<input type='hidden' name='toim' value='$toim'>";
	echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
	echo "<input type='hidden' name='kaunisnimi' value='".ucfirst(strtolower($toim)).".xls'>";
	echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
	echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
	echo "</table><br>";
}

require ("inc/footer.inc");

?>