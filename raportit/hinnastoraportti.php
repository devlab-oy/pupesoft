<?php

	//* Tämä skripti käyttää slave-tietokantapalvelinta *//
	$useslave = 1;

	if (isset($_REQUEST["tee"])) {
		if ($_REQUEST["tee"] == 'lataa_tiedosto') $lataa_tiedosto = 1;
	 	if ($_REQUEST["kaunisnimi"] != '') $_REQUEST["kaunisnimi"] = str_replace("/", "", $_REQUEST["kaunisnimi"]);
	}

	require ('../inc/parametrit.inc');

	if (isset($tee) and $tee == "lataa_tiedosto") {
		readfile("/tmp/".$tmpfilenimi);
		exit;
	}

	echo "<font class='head'>".t("Hinnastoajo").":</font><hr>";

	if ($raptee == "AJA") {
		if (include('Spreadsheet/Excel/Writer.php')) {
		    $excelnimi = "$kukarow[yhtio]-".date("YmdHis").".xls";
		    $workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
		    $workbook->setVersion(8);
		    $worksheet = $workbook->addWorksheet('Hinnasto');
		    $format_bold = $workbook->addFormat();
		    $format_bold->setBold();
		    $excelrivi = 0;
		    $i = 0;
		}

		// Tarkastetaan yhtion parametreista tuotteiden_jarjestys_raportoinnissa (V = variaation, koon ja varin mukaan)
		if ($yhtiorow['tuotteiden_jarjestys_raportoinnissa'] == 'V') {
			// Order by lisa
			$order_extra = 'variaatio, vari, koko';

			// queryyn muutoksia jos lajitellaan näin
			$jarjestys_sel = ", 	t1.selite as variaatio,
									t2.selite as vari,
									t3.selite as kokoselite,
									if(t3.jarjestys = 0 or t3.jarjestys is null, 999999, t3.jarjestys) koko";

			$jarjestys_join = " LEFT JOIN tuotteen_avainsanat t1 ON tuote.yhtio = t1.yhtio AND tuote.tuoteno = t1.tuoteno AND t1.laji = 'parametri_variaatio' AND t1.kieli = '{$yhtiorow['kieli']}'
								LEFT JOIN tuotteen_avainsanat t2 ON tuote.yhtio = t2.yhtio AND tuote.tuoteno = t2.tuoteno AND t2.laji = 'parametri_vari' AND t2.kieli = '{$yhtiorow['kieli']}'
								LEFT JOIN tuotteen_avainsanat t3 ON tuote.yhtio = t3.yhtio AND tuote.tuoteno = t3.tuoteno AND t3.laji = 'parametri_koko' AND t3.kieli = '{$yhtiorow['kieli']}'";
		}
		else {
			$order_extra = 'tuoteno';
		}

		$productquery = "	SELECT tuote.tuoteno, tuote.try, tuote.nimitys, tuote.kehahin, tuote.myyntihinta, tuote.eankoodi
							$jarjestys_sel
							FROM tuote
							$jarjestys_join
							WHERE tuote.yhtio = '$kukarow[yhtio]'
							AND tuote.hinnastoon != 'E'
							AND tuote.tuotetyyppi not in ('A','B')
							AND (tuote.status != 'P' or (SELECT sum(tuotepaikat.saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0) > 0)
							ORDER BY $order_extra";
		$productqueryresult = mysql_query($productquery) or pupe_error($productquery);

		$showprod = TRUE;

		if (mysql_num_rows($productqueryresult) > 2000) {
			echo t("Tuotteita ei näytetä ruudulla, koska niitä on yli")." 2000.";

			require_once ('inc/ProgressBar.class.php');
			$bar = new ProgressBar();
			$elements = mysql_num_rows($productqueryresult); // total number of elements to process
			$bar->initialize($elements); // print the empty bar

			$showprod = FALSE;
		}

		if ($showprod) {
			echo "<table>";
			echo "<tr>";
			echo "<th>".t("Tuoteno")."</th>";
			echo "<th>".t("Nimitys")."</th>";
			if ($kehahinnat != "") echo "<th>".t("Kehahin")."</th>";
			echo "<th>".t("Myyntihinta")."</th>";
			echo "<th>".t("Saldo")."</th>";
			echo "<th>".t("Tryno")."</th>";
			echo "<th>".t("Try")."</th>";
			echo "<th>".t("Ean")."</th>";
			echo "</tr>";
		}

		if (isset($workbook)) {
		    $worksheet->write($excelrivi, $i, t('Tuoteno'), $format_bold);
		    $i++;
		    $worksheet->write($excelrivi, $i, t('Nimitys'), $format_bold);
		    $i++;

			if ($kehahinnat != "") {
				$worksheet->write($excelrivi, $i, t('Kehahin'), $format_bold);
		    	$i++;
	    	}

			$worksheet->write($excelrivi, $i, t('Myyntihinta'), $format_bold);
		    $i++;
		    $worksheet->write($excelrivi, $i, t('Saldo'), $format_bold);
		    $i++;
		    $worksheet->write($excelrivi, $i, t('Tryno'), $format_bold);
		    $i++;
		    $worksheet->write($excelrivi, $i, t('Try'), $format_bold);
		    $i++;
		    $worksheet->write($excelrivi, $i, t('EAN'), $format_bold);
		    $i=0;
		    $excelrivi++;
		}

	    while ($productrow = mysql_fetch_array($productqueryresult)) {

			list(,,$apu_myytavissa) = saldo_myytavissa($productrow["tuoteno"]);

			$sresult = t_avainsana("TRY", "", "and avainsana.selite  = '$productrow[try]'");
			$srow = mysql_fetch_array($sresult);

			if ($myytavissao == "" or $apu_myytavissa > 0) {
				if (isset($workbook)) {
					$worksheet->writeString($excelrivi, $i, $productrow['tuoteno']);
					$i++;
					$worksheet->writeString($excelrivi, $i, $productrow['nimitys']);
					$i++;

					if ($kehahinnat != "") {
						$worksheet->writeNumber($excelrivi, $i, $productrow['kehahin']);
						$i++;
					}

					$worksheet->writeNumber($excelrivi, $i, $productrow['myyntihinta']);
					$i++;
					$worksheet->writeNumber($excelrivi, $i, $apu_myytavissa);
					$i++;
					$worksheet->writeString($excelrivi, $i, $productrow["try"]);
					$i++;
					$worksheet->writeString($excelrivi, $i, $srow["selitetark"]);
					$i++;
					$worksheet->writeString($excelrivi, $i, $productrow['eankoodi']);
					$i=0;
					$excelrivi++;
				}

				if ($showprod) {
					echo "<tr class='aktiivi'>";
					echo "<td>$productrow[tuoteno]</td>";
					echo "<td>$productrow[nimitys]</td>";
					if ($kehahinnat != "") echo "<td align='right'>$productrow[kehahin]</td>";
					echo "<td align='right'>$productrow[myyntihinta]</td>";
					echo "<td align='right'>$apu_myytavissa</td>";
					echo "<td>$productrow[try]</td>";
					echo "<td>$srow[selitetark]</td>";
					echo "<td>$productrow[eankoodi]</td>";
					echo "</tr>";
				}
				else {
					$bar->increase();
				}
			}
		}

		if ($showprod) echo "</table>";

		if (isset($workbook)) {
		    $workbook->close();

			echo "<br><br>";
			echo "<font class='message'>".t("Tallenna raportti (xls)").": </font>";
			echo "<form method='post' class='multisubmit'>";
			echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
			echo "<input type='hidden' name='kaunisnimi' value='hinnastoraportti.xls'>";
			echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
			echo "<input type='submit' value='".t("Tallenna")."'>";
			echo "</form>";
			echo "<br><br>";
		}
	}

	if (isset($kehahinnat) and $kehahinnat != "") {
		$chk = "CHECKED";
	}
	else {
		$chk = "";
	}

	if (isset($myytavissao) and $myytavissao != "") {
		$chk2 = "CHECKED";
	}
	else {
		$chk2 = "";
	}

	echo "<br><form method='post'>";
	echo "<input type='hidden' name='raptee' value='AJA'>";
	echo "<table>";
	echo "<tr>
		<th>".t("Näytä myös keskihankintahinnat")."</th>
		<td><input type='checkbox' name='kehahinnat' $chk></td>
		</tr>\n";

	echo "<tr>
		<th>".t("Näytä vain myytävissä olevat tuotteet")."</th>
		<td><input type='checkbox' name='myytavissao' $chk2></td>
		</tr>\n";

	echo "</table><br>";
	echo "<br><input type='submit' value='".t("Tee hinnasto")."'>";
	echo "</form>";

	require ("../inc/footer.inc");

?>