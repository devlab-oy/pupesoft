<?php

	# Formaatti on:
	# toimittajanumero
	# tilausnumero
	# tuotekoodi
	# määrä

	$query = "  SELECT tilausrivi.tunnus,
				tilausrivi.otunnus,
				tilausrivi.varattu,
				tilausrivi.yksikko,
				ifnull(tuotteen_toimittajat.toim_tuoteno, tilausrivi.tuoteno) toim_tuoteno
				FROM tilausrivi
				JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio AND tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno AND tuotteen_toimittajat.liitostunnus = '$laskurow[liitostunnus]')
				WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
				AND tilausrivi.tyyppi  = 'O'
				AND tilausrivi.otunnus = '$laskurow[tunnus]'
				AND tilausrivi.varattu > 0
				ORDER BY tilausrivi.tunnus";
	$result = pupe_query($query);

	if (mysql_num_rows($result) == 0) {
		echo "<font class='error'>".t("Lähetettäviä tilausrivejä ei löydy")."</font>";
	}
	else {

		require_once('Spreadsheet/Excel/Writer.php');

		$excel_editilaus = $pupe_root_polku."/dataout/excel-order-$laskurow[tunnus].xls";

		$workbook = new Spreadsheet_Excel_Writer($excel_editilaus);

		$workbook->setVersion(8);

		$thispage = $workbook->addWorksheet($laskurow["tunnus"]);

		$boldi = $workbook->addFormat();
		$boldi->setBold();

		$excelrivi = 0;

		$thispage->writeString($excelrivi, 0, "Supplier number", $boldi);
		$thispage->writeString($excelrivi, 1, "Order number", $boldi);
		$thispage->writeString($excelrivi, 2, "Product number", $boldi);
		$thispage->writeString($excelrivi, 3, "Quantity", $boldi);
		$excelrivi++;

		while ($tilausrivirow = mysql_fetch_assoc($result)) {

			$thispage->writeString($excelrivi, 0, $toimirow["toimittajanro"]);
			$thispage->writeString($excelrivi, 1, $tilausrivirow['otunnus']);
			$thispage->writeString($excelrivi, 2, $tilausrivirow['toim_tuoteno']);
			$thispage->writeNumber($excelrivi, 3, $tilausrivirow['varattu']);
			$excelrivi++;
		}

		// We need to explicitly close the workbook
		$workbook->close();

		echo "<table>";
		echo "<tr><th>".t("Tallenna ostotilaus").":</th>";
		echo "<form method='post' action='$PHP_SELF'>";
		echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
		echo "<input type='hidden' name='kaunisnimi' value='Order_{$laskurow["tunnus"]}.xls'>";
		echo "<input type='hidden' name='filenimi' value='$excel_editilaus'>";
		echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
		echo "</table><br>";
	}

	// Nollataan tarkoituksella lopetusmuuttuja
	$lopetus = "";

	$wabco_editilaus = "";
	$out = "";

?>