<?php

	if (isset($_POST["tee"])) {
		if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
		if(isset($_POST["kaunisnimi"]) and $_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
	}

	//* Tämä skripti käyttää slave-tietokantapalvelinta *//
	$useslave = 1;

	require ("../inc/parametrit.inc");


	if (isset($tee) and $tee == "lataa_tiedosto") {
		readfile("/tmp/".$tmpfilenimi);
		exit;
	}

	echo "<font class=head>".t("Tuotteet joiden tulossa oleva saldo ei riitä")."</font><hr>";

	if ($ytunnus != '') {
		$toimittajaid = "";
		require ("inc/kevyt_toimittajahaku.inc");

		if ($toimittajaid == "") {
			exit;
		}
	}
	else {
		$ytunnus = "";
		$toimittajaid = "";
	}

	// käyttis
	echo "<br><form method='POST'>";
	echo "<input type='hidden' name='tee' value='raportoi'>";
	echo "<table>";
	echo "<tr><th>".t("Rajaukset")."</th><td>";

	$monivalintalaatikot = array('OSASTO','TRY','TUOTEMERKKI');
	require ("tilauskasittely/monivalintalaatikot.inc");

	echo "</td></tr>";
	echo "<tr>";
	echo "<th>".t("Toimittaja")."</th>";
	echo "<td><input type='text' name='ytunnus' value='$ytunnus'> ";
	echo "{$toimittajarow["nimi"]} {$toimittajarow["nimitark"]}";
	echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";

	echo "<br><input type='submit' value='".t("Aja raportti")."' name='painoinnappia'>";
	echo "</form>";
	echo "<br><br>";

	if ($tee != "" and isset($painoinnappia) and $lisa == "" and $toimittajaid == "") {
		echo "<font class='error'>", t("Anna jokin rajaus"), "!</font>";
		$tee = "";
	}

	if ($tee != "" and isset($painoinnappia)) {

		if ($yhtiorow["varaako_jt_saldoa"] != "") {
			$lisavarattu = " + tilausrivi.varattu";
		}
		else {
			$lisavarattu = "";
		}

		if ($toimittajaid != "") {
			$toimittaja_join = "	JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tuote.yhtio
									AND tuotteen_toimittajat.tuoteno = tuote.tuoteno
									AND tuotteen_toimittajat.liitostunnus = '$toimittajaid')";
		}
		else {
			$toimittaja_join = "";
		}

		if (@include('Spreadsheet/Excel/Writer.php')) {

			//keksitään failille joku varmasti uniikki nimi:
			list($usec, $sec) = explode(' ', microtime());
			mt_srand((float) $sec + ((float) $usec * 100000));
			$excelnimi = md5(uniqid(mt_rand(), true)).".xls";

			$workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
			$workbook->setVersion(8);
			$worksheet =& $workbook->addWorksheet('Vajaasaldot');

			$format_bold =& $workbook->addFormat();
			$format_bold->setBold();

			$excelrivi = 0;
		}


		$vajaasaldot_table = "<table>";
		$vajaasaldot_table .= "<th>".t("Osasto")."</th>";
		$vajaasaldot_table .= "<th>".t("Tuoteryhmä")."</th>";
		$vajaasaldot_table .= "<th>".t("Tuoteno")."</th>";
		$vajaasaldot_table .= "<th>".t("Nimitys")."</th>";
		$vajaasaldot_table .= "<th>".t("Varastosaldo")."</th>";
		$vajaasaldot_table .= "<th>".t("Vapaa saldo")."</th>";
		$vajaasaldot_table .= "<th>".t("Tulossa")."</th>";
		$vajaasaldot_table .= "<th>".t("Toimaika")."</th>";

		if (isset($workbook)) {
			$excelsarake = 0;
			$worksheet->writeString($excelrivi, $excelsarake++, t("Osasto"));
			$worksheet->writeString($excelrivi, $excelsarake++, t("Tuoteryhmä"));
			$worksheet->writeString($excelrivi, $excelsarake++, t("Tuoteno"));
			$worksheet->writeString($excelrivi, $excelsarake++, t("Nimitys"));
			$worksheet->writeString($excelrivi, $excelsarake++, t("Varastosaldo"));
			$worksheet->writeString($excelrivi, $excelsarake++, t("Vapaa saldo"));
			$worksheet->writeString($excelrivi, $excelsarake++, t("Tulossa"));
			$worksheet->writeString($excelrivi, $excelsarake++, t("Toimitus aika"));
			$excelrivi++;
		}

		$query = "	SELECT tuote.tuoteno, tuote.nimitys, tuote.osasto, tuote.try
					FROM tuote
					{$toimittaja_join}
					WHERE tuote.yhtio = '{$kukarow["yhtio"]}'
					{$lisa}
					AND (tuote.status != 'P' OR (	SELECT sum(tuotepaikat.saldo)
													FROM tuotepaikat
													WHERE tuotepaikat.yhtio = tuote.yhtio
													AND tuotepaikat.tuoteno = tuote.tuoteno
													AND tuotepaikat.saldo > 0) > 0)
					ORDER BY tuote.osasto, tuote.try, tuote.tuoteno";
		$eresult = pupe_query($query);

		$total_rows = mysql_num_rows($eresult);
		$current_row = 0;

		if ($total_rows > 0) {

			echo "<font class='message'>", t("Käsitellään"), " $total_rows ", t("tuotetta"), ".</font>";
			require('inc/ProgressBar.class.php');

			$bar = new ProgressBar();
			$bar->initialize($total_rows); // print the empty bar

			while ($row = mysql_fetch_assoc($eresult)) {

				$bar->increase();

				// ostopuoli
				$query = "	SELECT min(toimaika) toimaika,
							sum(varattu) tulossa
							FROM tilausrivi
							WHERE yhtio = '{$kukarow["yhtio"]}'
							AND tuoteno = '{$row["tuoteno"]}'
							AND tyyppi 	= 'O'
							AND varattu > 0";
				$ostoresult = pupe_query($query);
				$ostorivi = mysql_fetch_assoc($ostoresult);

				// Ajetaan saldomyytävissä niin, että JT-rivejä ei huomioida suuntaaan eikä toiseen
				list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($row["tuoteno"], 'JTSPEC');

				$query = "	SELECT sum(jt $lisavarattu) jt
							FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
							WHERE yhtio	= '{$kukarow["yhtio"]}'
							and tyyppi 	in ('L','G')
							and tuoteno	= '{$row["tuoteno"]}'
							and laskutettuaika = '0000-00-00'
							and jt $lisavarattu > 0
							and kpl		= 0
							and var		= 'J'";
				$juresult = pupe_query($query);
				$jurow    = mysql_fetch_assoc($juresult);

				if ($myytavissa - $jurow["jt"] + $ostorivi["tulossa"] < 0) {

					$osastores = t_avainsana("OSASTO", "", "and avainsana.selite ='$row[osasto]'");
					$osastorow = mysql_fetch_assoc($osastores);

					if ($osastorow['selitetark'] != "") $row['osasto'] = $row['osasto']." - ".$osastorow['selitetark'];

					$tryres = t_avainsana("TRY", "", "and avainsana.selite ='$row[try]'");
					$tryrow = mysql_fetch_assoc($tryres);

					if ($tryrow['selitetark'] != "") $row['try'] = $row['try']." - ".$tryrow['selitetark'];

					$vajaasaldot_table .= "<tr class='aktiivi'>";
					$vajaasaldot_table .= "<td>$row[osasto]</td>";
					$vajaasaldot_table .= "<td>$row[try]</td>";
					$vajaasaldot_table .= "<td><a href='{$palvelin2}tuote.php?tee=Z&tuoteno=".urlencode($row["tuoteno"])."'>$row[tuoteno]</a></td>";
					$vajaasaldot_table .= "<td>$row[nimitys]</td>";
					$vajaasaldot_table .= "<td align='right'>$saldo</td>";
					$vajaasaldot_table .= "<td align='right'>".($myytavissa - $jurow["jt"])."</td>";
					$vajaasaldot_table .= "<td align='right'>$ostorivi[tulossa]</td>";
					$vajaasaldot_table .= "<td>$ostorivi[toimaika]</td>";
					$vajaasaldot_table .= "</tr>";

					if (isset($workbook)) {
						$excelsarake = 0;
						$worksheet->writeString($excelrivi, $excelsarake++, $row["osasto"]);
						$worksheet->writeString($excelrivi, $excelsarake++, $row["try"]);
						$worksheet->writeString($excelrivi, $excelsarake++, $row["tuoteno"]);
						$worksheet->writeString($excelrivi, $excelsarake++, $row["nimitys"]);
						$worksheet->writeNumber($excelrivi, $excelsarake++, $saldo);
						$worksheet->writeNumber($excelrivi, $excelsarake++, ($myytavissa - $jurow["jt"]));
						$worksheet->writeNumber($excelrivi, $excelsarake++, $ostorivi["tulossa"]);
						$worksheet->writeString($excelrivi, $excelsarake++, $ostorivi["toimaika"]);

						$excelrivi++;
					}

					$current_row++;
				}
			}

			$vajaasaldot_table .= "</table>";

			echo "<br>";

			if ($current_row > 0) {
				echo "<br>", $vajaasaldot_table;

				if (isset($workbook)) {
					$workbook->close();
					echo "<br>";
					echo "<table>";
					echo "<tr><th>".t("Tallenna excel").":</th>";
					echo "<form method='post' class='multisubmit'>";
					echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
					echo "<input type='hidden' name='kaunisnimi' value='Vajaasaldot.xls'>";
					echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
					echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
					echo "</table><br>";
				}
			}
		}

		if ($total_rows == 0 or $current_row == 0) {
			echo "<font class='message'>", t("Yhtään soveltuvaa tuotetta ei löytynyt"), ".</font>";
		}
	}

	require ("inc/footer.inc");
?>