<?php

if (isset($_POST["supertee"])) {
	if($_POST["supertee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
	if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
}

///* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *///
$useslave = 1;
require ("../inc/parametrit.inc");

if (isset($supertee)) {
	if ($supertee == "lataa_tiedosto") {
		readfile("/tmp/".$tmpfilenimi);
		exit;
	}
}

echo "<font class='head'>".t("Menossa olevat tilaukset")."</font><hr>";

if ($tee == 'NAYTATILAUS') {
		echo "<font class='head'>Tilausnro: $tunnus</font><hr>";
		require ("naytatilaus.inc");
		echo "<br><br><br>";
		$tee = "";
}

if ($ytunnus != '' and $ytunnus != 'TULKAIKKI') {
	require ("../inc/asiakashaku.inc");
}

if ($ytunnus != '' or $ytunnus == 'TULKAIKKI') {

	if ($ytunnus != 'TULKAIKKI') {
		$lisa = " and lasku.ytunnus = '$ytunnus' ";
	}
	else {
		$lisa = " ";
	}
	
	if ($suunta == '' or $suunta == "DESC") {
		$suunta = "ASC";
	}
	else {
		$suunta = "DESC";
	}

	echo "<table><tr>";
	echo "<th>".t("tilno")."</th>";
	echo "<th>".t("ytunnus")."</th>";
	echo "<th>".t("nimi")."</th>";
	echo "<th><a href='?suunta=$suunta&ytunnus=$ytunnus'>".t("Toimitusaika")."</a></th>";
	echo "<th>".t("rivim‰‰r‰")."</th>";
	echo "<th>".t("kplm‰‰r‰")."</th>";
	echo "<th>".t("arvo")."</th>";
	echo "<th>".t("valuutta")."</th>";
	echo "</tr>";
	
	$query = "	SELECT lasku.tunnus, lasku.nimi, tilausrivi.tuoteno, tilausrivi.toimaika, 
				count(*) maara, sum(tilausrivi.varattu) tilattu, sum(tilausrivi.varattu * tilausrivi.hinta) arvo, lasku.valkoodi, lasku.ytunnus
				from tilausrivi use index (yhtio_tyyppi_laskutettuaika)
				JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
				where tilausrivi.yhtio	= '$kukarow[yhtio]' 
				and tilausrivi.varattu 	> '0' 
				and tilausrivi.tyyppi 	= 'L'
				and tilausrivi.laskutettuaika = '0000-00-00' 
				$lisa
				group by 1,2,3,4
				order by tilausrivi.toimaika  $suunta, lasku.nimi, tilausrivi.tuoteno";
	$result = mysql_query($query) or pupe_error($query);
	
	if (($vain_excel != '' or $vain_excel_kaikki != '') and @include('Spreadsheet/Excel/Writer.php')) {
		//keksit‰‰n failille joku varmasti uniikki nimi:
		list($usec, $sec) = explode(' ', microtime());
		mt_srand((float) $sec + ((float) $usec * 100000));
		$excelnimi = md5(uniqid(mt_rand(), true)).".xls";

		$workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
		$workbook->setVersion(8);
		$worksheet =& $workbook->addWorksheet('Sheet 1');

		$format_bold =& $workbook->addFormat();
		$format_bold->setBold();

		$excelrivi = 0;
		
		if(isset($workbook)) {
			$excelsarake = 0;

			$worksheet->write($excelrivi, $excelsarake, t("Tilno"), $format_bold);
			$excelsarake++;
			$worksheet->write($excelrivi, $excelsarake, t("Ytunnus"), $format_bold);
			$excelsarake++;
			$worksheet->write($excelrivi, $excelsarake, t("Nimi"), $format_bold);
			$excelsarake++;
			$worksheet->write($excelrivi, $excelsarake, t("Toimitusaika"), $format_bold);
			$excelsarake++;
			$worksheet->write($excelrivi, $excelsarake, t("rivim‰‰r‰"), $format_bold);
			$excelsarake++;
			$worksheet->write($excelrivi, $excelsarake, t("kplm‰‰r‰"), $format_bold);
			$excelsarake++;
			$worksheet->write($excelrivi, $excelsarake, t("arvo"), $format_bold);
			$excelsarake++;
			$worksheet->write($excelrivi, $excelsarake, t("valuutta"), $format_bold);
			
			$excelsarake = 0;
			$excelrivi++;
		}
	}
	
	while ($tulrow = mysql_fetch_array($result)) {
		echo "<tr>";
		echo "<td><a href='$PHP_SELF?tee=NAYTATILAUS&tunnus=$tulrow[tunnus]&ytunnus=$ytunnus&suunta=$suunta'>$tulrow[tunnus]</a></td>";
		echo "<td>$tulrow[ytunnus]</td>";
		echo "<td>$tulrow[nimi]</td>";
		echo "<td>".tv1dateconv($tulrow["toimaika"])."</td>";
		echo "<td align='right'>$tulrow[maara]</td>";
		echo "<td align='right'>$tulrow[tilattu]</td>";
		echo "<td align='right'>$tulrow[arvo]</td>";
		echo "<td>$tulrow[valkoodi]</td>";
		echo "</tr>";
		
		if(isset($workbook)) {
			$excelsarake = 0;

			$worksheet->writeString($excelrivi, $excelsarake, $tulrow[tunnus], $format_bold);
			$excelsarake++;
			$worksheet->writeString($excelrivi, $excelsarake, $tulrow[ytunnus], $format_bold);
			$excelsarake++;
			$worksheet->writeString($excelrivi, $excelsarake, $tulrow[nimi], $format_bold);
			$excelsarake++;
			$worksheet->writeString($excelrivi, $excelsarake, tv1dateconv($tulrow["toimaika"]), $format_bold);
			$excelsarake++;
			$worksheet->writeNumber($excelrivi, $excelsarake, $tulrow[maara], $format_bold);
			$excelsarake++;
			$worksheet->writeNumber($excelrivi, $excelsarake, $tulrow[tilattu], $format_bold);
			$excelsarake++;
			$worksheet->writeNumber($excelrivi, $excelsarake, $tulrow[arvo], $format_bold);
			$excelsarake++;
			$worksheet->writeString($excelrivi, $excelsarake, $tulrow[valkoodi], $format_bold);
			
			$excelsarake = 0;
			$excelrivi++;
		}
	}

	echo "</table>";
	
	if(isset($workbook)) {

		// We need to explicitly close the workbook
		$workbook->close();

		echo "<br><table>";
		echo "<tr><th>".t("Tallenna tulos").":</th>";
		echo "<form method='post' action='$PHP_SELF'>";
		echo "<input type='hidden' name='supertee' value='lataa_tiedosto'>";
		echo "<input type='hidden' name='kaunisnimi' value='Menossa_olevat.xls'>";
		echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
		echo "<td valign='top' class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
		echo "</table><br>";
	}
	
	$ytunnus = '';
}

$vain_excelchk = "";
if ($vain_excel != '') {
	$vain_excelchk = "CHECKED";
}

$vain_excelchk_kaikki = "";
if ($vain_excel_kaikki != '') {
	$vain_excelchk_kaikki = "CHECKED";
}

echo "<br><form name=asiakas action='$PHP_SELF' method='post' autocomplete='off'>";
echo "<table><tr>";
echo "<th>".t("Anna ytunnus tai osa nimest‰")."</th>";
echo "<td><input type='text' name='ytunnus' value='$ytunnus'></td></tr>";
echo "<tr><th>".t("Raportti Exceliin")."</th>";
echo "<td><input type='checkbox' name='vain_excel' $vain_excelchk></td><tr>";
echo "<td class='back'><input type='submit' value='".t("Hae")."'></td>";
echo "</tr>";
echo "</form>";
echo "<tr><td class='back'><br><br></td></tr>";
echo "<form name=asiakas action='$PHP_SELF' method='post' autocomplete='off'>";
echo "<tr>";
echo "<th colspan='2'>".t("Listaa kaikki menossa olevat")."</th></tr>";
echo "<tr><th>".t("Raportti Exceliin")."</th>";
echo "<td><input type='checkbox' name='vain_excel_kaikki' $vain_excelchk_kaikki></td><tr>";
echo "<input type='hidden' name='ytunnus' value='TULKAIKKI'>";
echo "<td class='back'><input type='submit' value='".t("Listaa")."'></td>";
echo "</tr></table>";
echo "</form>";



// kursorinohjausta
$formi  = "asiakas";
$kentta = "ytunnus";

require ("../inc/footer.inc");

?>