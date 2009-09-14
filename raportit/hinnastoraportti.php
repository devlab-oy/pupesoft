<?php

	if (isset($_REQUEST["tee"])) {
		if ($_REQUEST["tee"] == 'lataa_tiedosto') $lataa_tiedosto = 1;
	 	if ($_REQUEST["kaunisnimi"] != '') $_REQUEST["kaunisnimi"] = str_replace("/", "", $_REQUEST["kaunisnimi"]);
	}

	require ('inc/parametrit.inc');

	if (isset($tee) and $tee == "lataa_tiedosto") {
		readfile("/tmp/".$tmpfilenimi);
		exit;
	}

	echo "<font class='head'>".t("Hinnastoajo").":</font><hr>";

	if (include('Spreadsheet/Excel/Writer.php')) {
	    $excelnimi = "$kukarow[yhtio]-".date("YmdHis").".xls";
	    $workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
	    $workbook->setVersion(8);
	    $worksheet = $workbook->addWorksheet('Hinnasto');
	    $format_bold = $workbook->addFormat();
	    $format_bold->setBold();
	    $excelrivi = 0;
	    $i = 0;

		if (isset($workbook)) {
			echo "<font class='message'>".t("Tallenna raportti (xls)").": </font>";
			echo "<form method='post' action='$PHP_SELF'>";
			echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
			echo "<input type='hidden' name='kaunisnimi' value='hinnastoraportti.xls'>";
			echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
			echo "<input type='submit' value='".t("Tallenna")."'>";
			echo "</form>";
			echo "<br><br>";
		}
	}

	$productquery = "	SELECT tuoteno, try, nimitys, kehahin, myyntihinta, eankoodi
						FROM tuote
						WHERE yhtio = '$kukarow[yhtio]'
						AND hinnastoon != 'E'";
	$productqueryresult = mysql_query($productquery) or pupe_error($productquery);

	echo "<table>";
	echo "<tr>";
	echo "<th>".t("Tuoteno")."</th>";
	echo "<th>".t("Nimitys")."</th>";
	echo "<th>".t("Kehahin")."</th>";
	echo "<th>".t("Myyntihinta")."</th>";
	echo "<th>".t("Saldo")."</th>";
	echo "<th>".t("Try")."</th>";
	echo "<th>".t("Ean")."</th>";
	echo "</tr>";

	if (isset($workbook)) {
	    $worksheet->write($excelrivi, $i, t('Tuoteno'), $format_bold);
	    $i++;
	    $worksheet->write($excelrivi, $i, t('Nimitys'), $format_bold);
	    $i++;
	    $worksheet->write($excelrivi, $i, t('Kehahin'), $format_bold);
	    $i++;
	    $worksheet->write($excelrivi, $i, t('Myyntihinta'), $format_bold);
	    $i++;
	    $worksheet->write($excelrivi, $i, t('Saldo'), $format_bold);
	    $i++;
	    $worksheet->write($excelrivi, $i, t('TRY'), $format_bold);
	    $i++;
	    $worksheet->write($excelrivi, $i, t('EAN'), $format_bold);
	    $i=0;
	    $excelrivi++;
	}

    while ($productrow = mysql_fetch_array($productqueryresult)) {

		list(,,$apu_myytavissa) = saldo_myytavissa($productrow["tuoteno"]);
		$sresult = t_avainsana("TRY", "", "and avainsana.selite  = '$productrow[try]'");
		$srow = mysql_fetch_array($sresult);

		if (isset($workbook)) {
			$worksheet->writeString($excelrivi, $i, $productrow['tuoteno']);
			$i++;
			$worksheet->writeString($excelrivi, $i, $productrow['nimitys']);
			$i++;
			$worksheet->writeString($excelrivi, $i, $productrow['kehahin']);
			$i++;
			$worksheet->writeString($excelrivi, $i, $productrow['myyntihinta']);
			$i++;
			$worksheet->writeString($excelrivi, $i, $apu_myytavissa);
			$i++;
			$worksheet->writeString($excelrivi, $i, $srow["selitetark"]);
			$i++;
			$worksheet->writeString($excelrivi, $i, $productrow['eankoodi']);
			$i=0;
			$excelrivi++;
		}

		echo "<tr class='aktiivi'>";
		echo "<td>$productrow[tuoteno]</td>";
		echo "<td>$productrow[nimitys]</td>";
		echo "<td>$productrow[kehahin]</td>";
		echo "<td>$productrow[myyntihinta]</td>";
		echo "<td>$apu_myytavissa</td>";
		echo "<td>$srow[selitetark]</td>";
		echo "<td>$productrow[eankoodi]</td>";
		echo "</tr>";

    }

	echo "</table>";

	if (isset($workbook)) {
	    $workbook->close();
	}

	require ("inc/footer.inc");

?>