<?php

	//* Tämä skripti käyttää slave-tietokantapalvelinta *//
	$useslave = 1;

	if (isset($_POST["tee"])) {
		if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
		if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
	}

	require("../inc/parametrit.inc");

	if (isset($tee)) {
		if ($tee == "lataa_tiedosto") {
			readfile("/tmp/".$tmpfilenimi);
			exit;
		}
	}
	else {
		echo "<font class='head'>".t("Myynti asiakkaittain").":</font><hr>";

		//Käyttöliittymä
		if (!isset($kka))
			$kka = date("m",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
		if (!isset($vva))
			$vva = date("Y",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
		if (!isset($ppa))
			$ppa = date("d",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));

		if (!isset($kkl))
			$kkl = date("m");
		if (!isset($vvl))
			$vvl = date("Y");
		if (!isset($ppl))
			$ppl = date("d");

		$chk = "";
		$chk1 = "";

		if (trim($summaa) == 'summaa') {
			$chk = "CHECKED";
		}

		if (trim($summaa) == 'summaa_ytunnus') {
			$chk1 = "CHECKED";
		}

		echo "<form method='post' action='$PHP_SELF'>";
		echo "<input type='hidden' name='matee' value='kaikki'>";

		echo "<table style='display:inline;padding-right:4px;'>";
		echo "<tr><th>".t("Syötä alkupäivämäärä (pp-kk-vvvv)")."</th>
					<td><input type='text' name='ppa' value='$ppa' size='3'></td>
					<td><input type='text' name='kka' value='$kka' size='3'></td>
					<td><input type='text' name='vva' value='$vva' size='5'></td>
					</tr><tr><th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>
					<td><input type='text' name='ppl' value='$ppl' size='3'></td>
					<td><input type='text' name='kkl' value='$kkl' size='3'></td>
					<td><input type='text' name='vvl' value='$vvl' size='5'></td></tr>";

		echo "<tr><th>".t("Summaa myynnit per asiakas").":</th><td colspan='3'><input type='radio' name='summaa' value='summaa' $chk></td></tr>";
		echo "<tr><th>".t("Summaa myynnit per ytunnus").":</th><td colspan='3'><input type='radio' name='summaa' value='summaa_ytunnus' $chk1></td></tr>";

		echo "</table>";

		// Monivalintalaatikot (osasto, try tuotemerkki...)
		// Määritellään mitkä latikot halutaan mukaan
		$monivalintalaatikot = array("OSASTO", "TRY");

		require ("../tilauskasittely/monivalintalaatikot.inc");

		echo "<br><input type='submit' name='AJA' value='".t("Aja raportti")."'>";
		echo "</form><br><br>";

		if ($matee != '' and isset($AJA)) {

			if (include('Spreadsheet/Excel/Writer.php')) {

				//keksitään failille joku varmasti uniikki nimi:
				list($usec, $sec) = explode(' ', microtime());
				mt_srand((float) $sec + ((float) $usec * 100000));
				$excelnimi = md5(uniqid(mt_rand(), true)).".xls";

				$workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
				$workbook->setVersion(8);
				$worksheet = $workbook->addWorksheet('Sheet 1');

				$format_bold = $workbook->addFormat();
				$format_bold->setBold();

				$excelrivi = 0;

				$worksheet->writeString($excelrivi, 0, t("Myynti asiakkaittain"));

				$excelrivi ++;
			}

			$select = "lasku.liitostunnus, asiakas.piiri, tuote.aleryhma, max(lasku.ytunnus) ytunnus, max(lasku.nimi) nimi, max(lasku.nimitark) nimitark, ";
			$group  = "lasku.liitostunnus, asiakas.piiri, tuote.aleryhma";

			if ($summaa == 'summaa') {
				$select = "lasku.liitostunnus, max(asiakas.ytunnus) ytunnus, max(asiakas.nimi) nimi, max(asiakas.nimitark) nimitark, ";
				$group  = "lasku.liitostunnus";
			}

			if ($summaa == 'summaa_ytunnus') {
				$select = "lasku.ytunnus, max(asiakas.nimi) nimi, max(asiakas.nimitark) nimitark, ";
				$group  = "lasku.ytunnus";
			}

			$query = "	SELECT $select
						sum(tilausrivi.rivihinta) summa,
						sum(tilausrivi.kate) kate,
						sum(tilausrivi.kpl) kpl
						FROM lasku
						JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.uusiotunnus = lasku.tunnus and tilausrivi.tyyppi = 'L')
						JOIN asiakas ON (asiakas.yhtio = tilausrivi.yhtio and asiakas.tunnus = lasku.liitostunnus)
						JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno)
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and lasku.tila = 'U'
						and lasku.alatila = 'X'
						and lasku.tapvm >= '$vva-$kka-$ppa'
						and lasku.tapvm <= '$vvl-$kkl-$ppl'
						$lisa
						GROUP BY $group
						ORDER BY nimi, nimitark, ytunnus";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) < 2000) {
				echo "<table>";
				echo "<tr>";
				echo "<th>".t("Ytunnus")."</th>";
				echo "<th>".t("Nimi")."</th>";
				echo "<th>".t("Nimitark")."</th>";
				if ($summaa == '') echo "<th>".t("Aleryhmä")."</th>";

				if ($summaa == '') {
					for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
						echo "<th>",t("Alennus"),"{$alepostfix}</th>";
					}
				}

				if ($summaa == '') echo "<th>".t("Piiri")."</th>";
				echo "<th>".t("Määrä")."</th>";
				echo "<th>".t("Summa")."</th>";
				echo "<th>".t("Kate")."</th>";
				echo "<th>".t("Katepros")."</th>";
			}
			else {
				echo "<br><font class='error'>".t("Hakutulos oli liian suuri")."!</font><br>";
				echo "<font class='error'>".t("Tallenna/avaa tulos excelissä")."!</font><br><br>";
			}

			if (isset($workbook)) {
				$sarake = 0;
				$worksheet->write($excelrivi, $sarake, t("Ytunnus"), $format_bold);
				$worksheet->write($excelrivi, $sarake++, t("Nimi"), $format_bold);
				$worksheet->write($excelrivi, $sarake++, t("Nimitark"), $format_bold);
				if ($summaa == '') $worksheet->write($excelrivi, $sarake++, t("Aleryhmä"), $format_bold);

				if ($summaa == '') {
					for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
						$worksheet->write($excelrivi, $sarake++, t("Alennus").$alepostfix, $format_bold);
					}
				}

				if ($summaa == '') $worksheet->write($excelrivi, $sarake++, t("Piiri"), $format_bold);
				$worksheet->write($excelrivi, $sarake++, t("Määrä"), $format_bold);
				$worksheet->write($excelrivi, $sarake++, t("Summa"), $format_bold);
				$worksheet->write($excelrivi, $sarake++, t("Kate"), $format_bold);
				$worksheet->write($excelrivi, $sarake++, t("Katepros"), $format_bold);

				$excelrivi++;
			}

			while ($lrow = mysql_fetch_array($result)) {
				if ($summaa == '') {

					for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
						${'ale'.$alepostfix} = 0;
					}

					//haetaan tuoteryhmmän alennusryhmä
					$query = "	SELECT alennus, alennuslaji
								FROM asiakasalennus
								WHERE yhtio='$kukarow[yhtio]' and ryhma = '$lrow[aleryhma]' and ytunnus = '$lrow[ytunnus]'";
					$hresult = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($hresult) != 0) {
						$hrow = mysql_fetch_array ($hresult);

						if ($hrow["alennus"] > 0) {
							${'ale'.$hrow["alennuslaji"]} = $hrow[0];
						}
					}
					else {
						// Pudotaan perusalennukseen
						$query = "	SELECT alennus
									FROM perusalennus
									WHERE yhtio='$kukarow[yhtio]' and ryhma = '$lrow[aleryhma]'";
						$hresult = mysql_query($query) or pupe_error($query);

						if (mysql_num_rows($hresult) != 0) {
							$hrow=mysql_fetch_array ($hresult);

							if ($hrow["alennus"] > 0) {
								$ale1 = $hrow[0];
							}

							for ($alepostfix = 2; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								${'ale'.$alepostfix} = '';
							}
						}
					}
				}

				$katepros=0;

				if ($lrow["summa"] != 0) {
					$katepros = round($lrow["kate"]/$lrow["summa"]*100,2);
				}

				if (mysql_num_rows($result) < 2000) {
					echo "<tr class='aktiivi'>";
					echo "<td>".$lrow["ytunnus"]."</td>";
					echo "<td>".$lrow["nimi"]."</td>";
					echo "<td>".$lrow["nimitark"]."</td>";
					if ($summaa == '') echo "<td>$lrow[aleryhma]</td>";

					if ($summaa == '') {
						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							echo "<td>",${'ale'.$alepostfix},"</td>";
						}
					}

					if ($summaa == '') echo "<td align='right'>".$lrow["piiri"]."</td>";
					echo "<td align='right'>".sprintf("%.2f", $lrow["kpl"])."</td>";
					echo "<td align='right'>".sprintf("%.2f", $lrow["summa"])."</td>";
					echo "<td align='right'>".sprintf("%.2f", $lrow["kate"])."</td>";
					echo "<td align='right'>".sprintf("%.2f", $katepros)."%</td>";
					echo "</tr>";
				}

				if (isset($workbook)) {
					$sarake = 0;
					$worksheet->writeString($excelrivi, $sarake, $lrow["ytunnus"]);
					$worksheet->write($excelrivi, $sarake++, $lrow["nimi"]);
					$worksheet->write($excelrivi, $sarake++, $lrow["nimitark"]);
					if ($summaa == '') $worksheet->write($excelrivi, $sarake++, $lrow["aleryhma"]);

					if ($summaa == '') {
						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							$worksheet->write($excelrivi, $sarake++, ${'ale'.$alepostfix});
						}
					}

					if ($summaa == '') $worksheet->write($excelrivi, $sarake++, $lrow["piiri"]);
					$worksheet->write($excelrivi, $sarake++, $lrow["kpl"]);
					$worksheet->write($excelrivi, $sarake++, $lrow["summa"]);
					$worksheet->write($excelrivi, $sarake++, $lrow["kate"]);
					$worksheet->write($excelrivi, $sarake++, $katepros);

					$excelrivi++;
				}
			}

			if (mysql_num_rows($result) < 2000) echo "</table>";

			if (isset($workbook)) {
				// We need to explicitly close the workbook
				$workbook->close();

				echo "<br><br><table>";
				echo "<tr><th>".t("Tallenna tulos").":</th>";
				echo "<form method='post' action='$PHP_SELF'>";
				echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
				echo "<input type='hidden' name='kaunisnimi' value='Myyntiasiakkaittain.xls'>";
				echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
				echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
				echo "</table><br>";
			}
		}

		require ("../inc/footer.inc");
	}
?>