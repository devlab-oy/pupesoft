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

	echo "<font class=head>".t("Keskeneräiset myyntitilit/siirtolistat asiakkaittain")."</font><hr>";

	// käyttis
	if (!isset($kka)) $kka = date("m",mktime(0, 0, 0, date("m"), 1, date("Y")));
	if (!isset($vva)) $vva = date("Y",mktime(0, 0, 0, date("m"), 1, date("Y")));
	if (!isset($ppa)) $ppa = date("d",mktime(0, 0, 0, date("m"), 1, date("Y")));
	if (!isset($kkl)) $kkl = date("m");
	if (!isset($vvl)) $vvl = date("Y");
	if (!isset($ppl)) $ppl = date("d");

	$sel = array("M" => "", "S" => "");
	if (isset($ajotapa) and $ajotapa == "myyntitili") $sel["M"] = " selected";
	if (isset($ajotapa) and $ajotapa == "siirtolista") $sel["S"] = " selected";


	echo "<br>";
	echo "<form method='POST'>";
	echo "<input type='hidden' name='tee' value='raportoi'>";
	echo "<table>";

	echo "<tr><th>".t("Ajotapa")."</th><td>";
	echo "<select name='ajotapa'>";
	echo "<option value=''>".t("Listaa myyntitilejä ja siirtolistoja")."</option>";
	echo "<option value='myyntitili'{$sel["M"]}>".t("Listaa vain myyntitilejä")."</option>";
	echo "<option value='siirtolista'{$sel["S"]}>".t("Listaa vain siirtolistoja")."</option>";
	echo "</select>";
	echo "</td></tr>";

	echo "<tr><th>".t("Tuoterajaukset")."</th><td>";

	$monivalintalaatikot = array('OSASTO','TRY','TUOTEMERKKI');
	require ("tilauskasittely/monivalintalaatikot.inc");

	echo "</td></tr>";
	echo "<tr>
		<th>",t("Syötä alkupäivämäärä (pp-kk-vvvv)"),"</th>
			<td>
				<input type='text' name='ppa' value='{$ppa}' size='3'>
				<input type='text' name='kka' value='{$kka}' size='3'>
				<input type='text' name='vva' value='{$vva}' size='5'>
			</td>
		</tr>\n
		<tr><th>",t("Syötä loppupäivämäärä (pp-kk-vvvv)"),"</th>
			<td>
				<input type='text' name='ppl' value='{$ppl}' size='3'>
				<input type='text' name='kkl' value='{$kkl}' size='3'>
				<input type='text' name='vvl' value='{$vvl}' size='5'>
			</td>
		</tr>\n";
	echo "</table>";

	echo "<br><input type='submit' value='".t("Aja raportti")."' name='painoinnappia'>";
	echo "</form>";
	echo "<br><br>";

	if ($tee != "" and isset($painoinnappia) and ($ppa == "" or $kka == "" or $vva == "" or $ppl == "" or $kkl == "" or $vvl == "")) {
		echo "<font class='error'>", t("Anna päivämäärärajaus"), "!</font>";
		$tee = "";
	}

	if ($tee != "" and isset($painoinnappia)) {

		if (@include('Spreadsheet/Excel/Writer.php')) {

			//keksitään failille joku varmasti uniikki nimi:
			list($usec, $sec) = explode(' ', microtime());
			mt_srand((float) $sec + ((float) $usec * 100000));
			$excelnimi = md5(uniqid(mt_rand(), true)).".xls";

			$workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
			$workbook->setVersion(8);
			$worksheet =& $workbook->addWorksheet('Myyntitilituotteet');

			$format_bold =& $workbook->addFormat();
			$format_bold->setBold();

			$excelrivi = 0;
		}

		$table_data = "<table>";
		$table_data .= "<th>".t("Tyyppi")."</th>";
		$table_data .= "<th>".t("Numero")."</th>";
		$table_data .= "<th>".t("Luontiaika")."</th>";
		$table_data .= "<th>".t("Ytunnus")."</th>";
		$table_data .= "<th>".t("Nimi")."</th>";
		$table_data .= "<th>".t("Tuoteosasto")."</th>";
		$table_data .= "<th>".t("Tuoteryhmä")."</th>";
		$table_data .= "<th>".t("Tuoteno")."</th>";
		$table_data .= "<th>".t("Nimitys")."</th>";
		$table_data .= "<th>".t("Kappalemäärä")."</th>";

		if (isset($workbook)) {
			$excelsarake = 0;
			$worksheet->writeString($excelrivi, $excelsarake++, t("Tyyppi"));
			$worksheet->writeString($excelrivi, $excelsarake++, t("Numero"));
			$worksheet->writeString($excelrivi, $excelsarake++, t("Luontiaika"));
			$worksheet->writeString($excelrivi, $excelsarake++, t("Ytunnus"));
			$worksheet->writeString($excelrivi, $excelsarake++, t("Nimi"));
			$worksheet->writeString($excelrivi, $excelsarake++, t("Tuoteosasto"));
			$worksheet->writeString($excelrivi, $excelsarake++, t("Tuoteryhmä"));
			$worksheet->writeString($excelrivi, $excelsarake++, t("Tuoteno"));
			$worksheet->writeString($excelrivi, $excelsarake++, t("Nimitys"));
			$worksheet->writeString($excelrivi, $excelsarake++, t("Kappalemäärä"));
			$excelrivi++;
		}

		// Halutaanko vain siirtolistoja, myyntitilejä vai molempia
		if ($ajotapa == "myyntitili") {
			$rajaus = "AND lasku.tilaustyyppi = 'M' AND tilausrivi.kpl != 0";
		}
		elseif ($ajotapa == "siirtolista") {
			$rajaus = "AND lasku.tilaustyyppi = 'G' AND lasku.alatila NOT IN ('X', 'V')";
		}
		else {
			$rajaus = "AND ((lasku.tilaustyyppi = 'M' AND tilausrivi.kpl != 0) OR (lasku.tilaustyyppi = 'G' AND lasku.alatila NOT IN ('X', 'V')))";
		}

		// Etsitään kaikki myyntitili-/siirtolistarivit, joissa on jotain keskeneräistä
		$query = "	SELECT lasku.tilaustyyppi,
					lasku.tunnus,
					left(lasku.luontiaika, 10) luontiaika,
					lasku.ytunnus,
					lasku.nimi,
					tuote.osasto,
					tuote.try,
					tuote.tuoteno,
					tuote.nimitys,
					tilausrivi.kpl + tilausrivi.varattu + tilausrivi.jt AS kpl
					FROM lasku use index (tila_index)
					JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio
						AND tilausrivi.otunnus = lasku.tunnus
						AND tilausrivi.tyyppi != 'D')
					JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio
						AND tuote.tuoteno = tilausrivi.tuoteno
						{$lisa})
					WHERE lasku.yhtio = '{$kukarow["yhtio"]}'
					AND lasku.tila = 'G'
					AND lasku.luontiaika >= '{$vva}-{$kka}-{$ppa} 00:00:00'
					AND lasku.luontiaika <= '{$vvl}-{$kkl}-{$ppl} 23:59:59'
					{$rajaus}
					ORDER BY lasku.liitostunnus, lasku.luontiaika DESC";
		$result = pupe_query($query);
		$total_rows = mysql_num_rows($result);

		if ($total_rows > 0) {

			echo "<font class='message'>", t("Löytyi"), " $total_rows ", t("riviä"), ".</font>";

			while ($row = mysql_fetch_assoc($result)) {

				$osastores = t_avainsana("OSASTO", "", "and avainsana.selite ='$row[osasto]'");
				$osastorow = mysql_fetch_assoc($osastores);

				if ($osastorow['selitetark'] != "") $row['osasto'] = $row['osasto']." - ".$osastorow['selitetark'];

				$tryres = t_avainsana("TRY", "", "and avainsana.selite ='$row[try]'");
				$tryrow = mysql_fetch_assoc($tryres);

				if ($tryrow['selitetark'] != "") $row['try'] = $row['try']." - ".$tryrow['selitetark'];

				$row["tilaustyyppi"] = ($row["tilaustyyppi"] == "M") ? t("Myyntitili") : t("Siirtolista");

				$table_data .= "<tr class='aktiivi'>";
				$table_data .= "<td>{$row["tilaustyyppi"]}</td>";
				$table_data .= "<td>{$row["tunnus"]}</td>";
				$table_data .= "<td>{$row["luontiaika"]}</td>";
				$table_data .= "<td>{$row["ytunnus"]}</td>";
				$table_data .= "<td>{$row["nimi"]}</td>";
				$table_data .= "<td>{$row["osasto"]}</td>";
				$table_data .= "<td>{$row["try"]}</td>";
				$table_data .= "<td>{$row["tuoteno"]}</td>";
				$table_data .= "<td>{$row["nimitys"]}</td>";
				$table_data .= "<td align='right'>{$row["kpl"]}</td>";
				$table_data .= "</tr>";

				if (isset($workbook)) {
					$excelsarake = 0;
					$worksheet->writeNumber($excelrivi, $excelsarake++, $row["tilaustyyppi"]);
					$worksheet->writeNumber($excelrivi, $excelsarake++, $row["tunnus"]);
					$worksheet->writeString($excelrivi, $excelsarake++, $row["luontiaika"]);
					$worksheet->writeString($excelrivi, $excelsarake++, $row["ytunnus"]);
					$worksheet->writeString($excelrivi, $excelsarake++, $row["nimi"]);
					$worksheet->writeString($excelrivi, $excelsarake++, $row["osasto"]);
					$worksheet->writeString($excelrivi, $excelsarake++, $row["try"]);
					$worksheet->writeString($excelrivi, $excelsarake++, $row["tuoteno"]);
					$worksheet->writeString($excelrivi, $excelsarake++, $row["nimitys"]);
					$worksheet->writeNumber($excelrivi, $excelsarake++, $row["kpl"]);
					$excelrivi++;
				}
			}

			$table_data .= "</table>";

			echo "<br>";

			if ($total_rows > 0) {
				echo "<br>", $table_data;

				if (isset($workbook)) {
					$workbook->close();
					echo "<br>";
					echo "<table>";
					echo "<tr><th>".t("Tallenna excel").":</th>";
					echo "<form method='post' class='multisubmit'>";
					echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
					echo "<input type='hidden' name='kaunisnimi' value='Myyntitilituotteet.xls'>";
					echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
					echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
					echo "</table>";
				}
			}
		}

		if ($total_rows == 0) {
			echo "<font class='message'>", t("Yhtään soveltuvaa riviä ei löytynyt"), ".</font>";
		}
	}

	require ("inc/footer.inc");
