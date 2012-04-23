<?php

	# Formaatti on:
	# toimittajanumero
	# tilausnumero
	# tuotekoodi
	# määrä

	$query_ale_lisa = generoi_alekentta("O");

	$query = "  SELECT tilausrivi.tunnus,
				tilausrivi.otunnus,
				round(tilausrivi.varattu * IF(tuotteen_toimittajat.tuotekerroin = 0, 1, tuotteen_toimittajat.tuotekerroin), 2) AS varattu,
				tilausrivi.yksikko,
				tilausrivi.tuoteno,
				ifnull(tuotteen_toimittajat.toim_tuoteno, tilausrivi.tuoteno) toim_tuoteno,
				round(tilausrivi.hinta*if (tuotteen_toimittajat.tuotekerroin=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin)*{$query_ale_lisa},'$yhtiorow[hintapyoristys]') nettohinta
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

		$thispage->setColumn(1,2,25);

		$excelrivi = 1;
		$thispage->writeString($excelrivi++,   1, trim($laskurow["toim_nimi"]." ".$laskurow["toim_nimitark"]), $boldi);
		$thispage->writeString($excelrivi++, 1, $laskurow["toim_osoite"], $boldi);
		$thispage->writeString($excelrivi++, 1, $laskurow["toim_maa"]."-".$laskurow["toim_postino"]." ".$laskurow["toim_postitp"], $boldi);

		$thispage->writeString($excelrivi, 1, "Order Number:", $boldi);
		$thispage->writeString($excelrivi++, 2, $laskurow["tunnus"]);
		$thispage->writeString($excelrivi, 1, "Date of order:", $boldi);
		$thispage->writeString($excelrivi++, 2, date("Y-m-d"));

		$excelrivi++;
		$thispage->writeString($excelrivi, 0, "Line", $boldi);
		$thispage->writeString($excelrivi, 1, "Your product code", $boldi);
		$thispage->writeString($excelrivi, 2, "Our product code", $boldi);
		$thispage->writeString($excelrivi, 4, "Quantity", $boldi);
		$thispage->writeString($excelrivi, 5, "Price", $boldi);
		$excelrivi++;

		$lask = 1;

		while ($tilausrivirow = mysql_fetch_assoc($result)) {

			$thispage->writeString($excelrivi, 0, $lask);
			$thispage->writeString($excelrivi, 1, $tilausrivirow['toim_tuoteno']);
			$thispage->writeString($excelrivi, 2, $tilausrivirow['tuoteno']);
			$thispage->writeNumber($excelrivi, 4, $tilausrivirow['varattu']);
			$thispage->writeNumber($excelrivi, 5, $tilausrivirow['nettohinta']);
			$excelrivi++;

			$lask++;
		}

		// We need to explicitly close the workbook
		$workbook->close();

		echo "<table>";
		echo "<tr><th>".t("Tallenna ostotilaus").":</th>";
		echo "<form method='post' action='{$palvelin2}tilauskasittely/tilaus_osto.php'>";
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