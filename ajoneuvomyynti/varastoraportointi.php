<?php

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

		echo "<font class='head'>".t("Varastoraportointi sarjanumerolliset tuotteet")."</font><hr>";

		if ($raptee == "raportti") {

			$lisa = "";

			// osastorajaus
			if ($osasto != "" and $osasto != "kaikki") {
				$lisa .= " and tuote.osasto = '$osasto'";
			}

			// tuoteryhm‰rajaus
			if ($tuoteryhma != "" and $tuoteryhma != "kaikki") {
				$lisa .= " and tuote.try = '$tuoteryhma'";
			}

			// grouppaustaso
			if ($raportti == "1") {
				$group = " group by sarjanumero, tuoteno";
			}
			else {
				$group = " group by kaytetty, tuoteno";
			}

			// tuotteen k‰ytettystatus 1=uudet, 2=vanhat
			if ($kaytetty_haku == '1') {
				$lisa .= " and sarjanumeroseuranta.kaytetty  = '' ";
			}
			elseif ($kaytetty_haku == '2') {
				$lisa .= " and sarjanumeroseuranta.kaytetty != '' ";
			}

			// varastostatus tyhj‰=kaikki, 1=vapaana, 2=varattu
			if ($varasto_haku == '') {
				$lisa .= " and (tilausrivi_myynti.tunnus is null or tilausrivi_myynti.laskutettuaika = '0000-00-00') and tilausrivi_osto.laskutettuaika != '0000-00-00' ";
			}
			elseif ($varasto_haku == '1') {
				$lisa .= " and tilausrivi_myynti.tunnus is null and tilausrivi_osto.laskutettuaika != '0000-00-00' ";
			}
			elseif ($varasto_haku == '2') {
				$lisa .= " and tilausrivi_myynti.tunnus is not null and tilausrivi_myynti.laskutettuaika = '0000-00-00' and tilausrivi_osto.laskutettuaika != '0000-00-00' ";
			}

			// t‰ss‰ query
			$query = "	SELECT tuote.tuoteno,
						group_concat(if(tilausrivi_osto.nimitys is null or tilausrivi_osto.nimitys='', tuote.nimitys, tilausrivi_osto.nimitys) SEPARATOR '<br>') nimitys,
						group_concat(sarjanumeroseuranta.sarjanumero SEPARATOR '<br>') sarjanumero,
						group_concat(concat(\"'\", sarjanumeroseuranta.sarjanumero, \"'\")) sarjanumero_clean,
						group_concat(distinct if(sarjanumeroseuranta.kaytetty = '', 'U', sarjanumeroseuranta.kaytetty) SEPARATOR ' ') kaytetty,
						sum(if(tilausrivi_myynti.tunnus is null and tilausrivi_osto.laskutettuaika != '0000-00-00', 1, 0)) vapaana,
						sum(if((tilausrivi_myynti.tunnus is null or tilausrivi_myynti.laskutettuaika = '0000-00-00') and tilausrivi_osto.laskutettuaika != '0000-00-00', tilausrivi_osto.rivihinta / tilausrivi_osto.kpl, 0)) vararvo,
						sum(if((tilausrivi_myynti.tunnus is null or tilausrivi_myynti.laskutettuaika = '0000-00-00') and tilausrivi_osto.laskutettuaika != '0000-00-00', 1, 0)) kaikki,
						sum(if(tilausrivi_myynti.tunnus is not null and tilausrivi_myynti.laskutettuaika = '0000-00-00' and tilausrivi_osto.laskutettuaika != '0000-00-00', 1, 0)) varattu
						FROM tuote
						LEFT JOIN sarjanumeroseuranta ON (sarjanumeroseuranta.yhtio = tuote.yhtio and sarjanumeroseuranta.tuoteno = tuote.tuoteno and sarjanumeroseuranta.myyntirivitunnus != -1)
						LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON (tilausrivi_myynti.yhtio = sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus = sarjanumeroseuranta.myyntirivitunnus)
						LEFT JOIN tilausrivi tilausrivi_osto use index (PRIMARY) ON (tilausrivi_osto.yhtio = sarjanumeroseuranta.yhtio and tilausrivi_osto.tunnus = sarjanumeroseuranta.ostorivitunnus)
						WHERE tuote.yhtio = '$kukarow[yhtio]'
						and tuote.sarjanumeroseuranta = 'S'
						$lisa
						$group";
			$result = mysql_query($query) or pupe_error($query);

			echo "<table>";

			if(@include('Spreadsheet/Excel/Writer.php')) {

				//keksit‰‰n failille joku varmasti uniikki nimi:
				list($usec, $sec) = explode(' ', microtime());
				mt_srand((float) $sec + ((float) $usec * 100000));
				$excelnimi = md5(uniqid(mt_rand(), true)).".xls";

				$workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
				$workbook->setVersion(8);
				$worksheet = $workbook->addWorksheet('Sheet 1');

				$format_bold = $workbook->addFormat();
				$format_bold->setBold();

				$excelrivi = 0;
			}

			echo "<tr>";
			echo "<th>tuoteno</th>";
			echo "<th>sarjanumero</th>";
			echo "<th>nimitys</th>";
			echo "<th>k‰ytetty</th>";
			echo "<th>sarjanro<br>vapaana</th>";
			echo "<th>sarjanro<br>varattu</th>";
			echo "<th>sarjanro<br>total</th>";
			echo "<th>sarjanro<br>varastonarvo</th>";
			echo "<th>saldo<br>vapaana</th>";
			echo "<th>saldo<br>varattu</th>";
			echo "<th>saldo<br>total</th>";
			echo "<th>saldo<br>varastonarvo</th>";
			echo "</tr>";

			if(isset($workbook)) {
				$i=0;
				$worksheet->write($excelrivi, $i, "tuoteno", 				$format_bold);
				$i++;
				$worksheet->write($excelrivi, $i, "sarjanumero", 			$format_bold);
				$i++;
				$worksheet->write($excelrivi, $i, "nimitys", 				$format_bold);
				$i++;
				$worksheet->write($excelrivi, $i, "k‰ytetty", 				$format_bold);
				$i++;
				$worksheet->write($excelrivi, $i, "sarjanro vapaana", 		$format_bold);
				$i++;
				$worksheet->write($excelrivi, $i, "sarjanro varattu", 		$format_bold);
				$i++;
				$worksheet->write($excelrivi, $i, "sarjanro saldo", 		$format_bold);
				$i++;
				$worksheet->write($excelrivi, $i, "sarjanro varastonarvo", 	$format_bold);
				$i++;
				$worksheet->write($excelrivi, $i, "saldo vapaana", 			$format_bold);
				$i++;
				$worksheet->write($excelrivi, $i, "saldo varattu", 			$format_bold);
				$i++;
				$worksheet->write($excelrivi, $i, "saldo saldo", 			$format_bold);
				$i++;
				$worksheet->write($excelrivi, $i, "saldo varastonarvo", 	$format_bold);
				$i++;
				$excelrivi++;
			}
			while ($row = mysql_fetch_array($result)) {

				echo "<tr class='aktiivi'>";
				echo "<td valign='top'><a href='".$palvelin2."tuote.php?tee=Z&tuoteno=".urlencode($row["tuoteno"])."'>$row[tuoteno]</a></td>";
				echo "<td valign='top'>$row[sarjanumero]</td>";
				echo "<td valign='top'>$row[nimitys]</td>";
				echo "<td valign='top'>$row[kaytetty]</td>";
				echo "<td valign='top' align='right'>$row[vapaana]</td>";
				echo "<td valign='top' align='right'>$row[varattu]</td>";
				echo "<td valign='top' align='right'>$row[kaikki]</td>";
				echo "<td valign='top' align='right'>".sprintf("%.2f", $row["vararvo"])."</td>";

				list ($saldo, $hyllyssa, $myytavissa, $bool) = saldo_myytavissa($row["tuoteno"]);

				echo "<td valign='top' align='right'>$myytavissa</td>";
				echo "<td valign='top' align='right'>".($saldo-$myytavissa)."</td>";
				echo "<td valign='top' align='right'>$saldo</td>";

				$query	= "	SELECT avg(tilausrivi_osto.rivihinta/tilausrivi_osto.kpl) kehahin
							FROM sarjanumeroseuranta
							LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
							LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
							WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
							and sarjanumeroseuranta.tuoteno = '$row[tuoteno]'
							and sarjanumeroseuranta.myyntirivitunnus != -1
							and (tilausrivi_myynti.tunnus is null or tilausrivi_myynti.laskutettuaika = '0000-00-00')
							and tilausrivi_osto.laskutettuaika != '0000-00-00'";
				$sarjares = mysql_query($query) or pupe_error($query);
				$sarjarow = mysql_fetch_array($sarjares);

				echo "<td valign='top' align='right'>".sprintf("%.2f", $saldo*$sarjarow["kehahin"])."</td>";
				echo "</tr>";

				if(isset($workbook)) {
					$i=0;
					$worksheet->writeString($excelrivi, $i, $row["tuoteno"]);
					$i++;
					$worksheet->writeString($excelrivi, $i, str_replace("<br>", "\n", $row["sarjanumero"]));
					$i++;
					$worksheet->writeString($excelrivi, $i, str_replace("<br>", "\n", $row["nimitys"]));
					$i++;
					$worksheet->write($excelrivi, $i, $row["kaytetty"]);
					$i++;
					$worksheet->writeNumber($excelrivi, $i, $row["vapaana"]);
					$i++;
					$worksheet->writeNumber($excelrivi, $i, $row["varattu"]);
					$i++;
					$worksheet->writeNumber($excelrivi, $i, $row["kaikki"]);
					$i++;
					$worksheet->writeNumber($excelrivi, $i, sprintf("%.2f", $row["vararvo"]));
					$i++;
					$worksheet->writeNumber($excelrivi, $i, $myytavissa);
					$i++;
					$worksheet->writeNumber($excelrivi, $i, $saldo-$myytavissa);
					$i++;
					$worksheet->writeNumber($excelrivi, $i, $saldo);
					$i++;
					$worksheet->writeNumber($excelrivi, $i, sprintf("%.2f", $saldo*$sarjarow["kehahin"]));
					$i++;
					$excelrivi++;
				}
			}

			echo "</table><br>";

			if(isset($workbook)) {

				// We need to explicitly close the workbook
				$workbook->close();

				echo "<br><table>";
				echo "<tr><th>".t("Tallenna lista").":</th>";
				echo "<form method='post' class='multisubmit'>";
				echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
				echo "<input type='hidden' name='kaunisnimi' value='Varastoraportti.xls'>";
				echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
				echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
				echo "</table><br>";
			}
		}

		// Piirrell‰‰n formi
		echo "<form name='haku' method='post'>";
		echo "<input type='hidden' name='raptee' value='raportti'>";

		echo "<table>";

		// N‰ytet‰‰n soveltuvat osastot
		// tehd‰‰n avainsana query
		$res2 = t_avainsana("OSASTO");

		$sel = "";
		if ($osasto == "kaikki") $sel = "selected";

		echo "<tr><th>Osasto:</th><td>";
		echo "<select name='osasto'>";
		echo "<option value=''>Valitse osasto</option>";
		echo "<option value='kaikki' $sel>N‰yt‰ kaikki</option>";

		while ($rivi = mysql_fetch_array($res2)) {
			$sel = "";
			if ($osasto == $rivi["selite"]) {
				$sel = "selected";
				$sel_osasto = $rivi["selite"];
			}
			echo "<option value='$rivi[selite]' $sel>$rivi[selite] - $rivi[selitetark]</option>";
		}

		echo "</select></td></tr>";

		// n‰ytet‰‰n soveltuvat tuoteryhm‰t
		// tehd‰‰n avainsana query
		$res2 = t_avainsana("TRY");

		echo "<tr><th>Tuoteryhm‰:</th><td>";
		echo "<select name='tuoteryhma'>";
		echo "<option value=''>Valitse tuoteryhm‰</option>";

		$sel = "";
		if ($tuoteryhma == "kaikki") $sel = "selected";
		echo "<option value='kaikki' $sel>N‰yt‰ kaikki</option>";

		while ($rivi = mysql_fetch_array($res2)) {
			$sel = "";
			if ($tuoteryhma == $rivi["selite"]) {
				$sel = "selected";
				$sel_tuoteryhma = $rivi["selite"];
			}

			echo "<option value='$rivi[selite]' $sel>$rivi[selite] - $rivi[selitetark]</option>";
		}

		echo "</select></td></tr>";

		$chk = array();
		$chk[$raportti] = "selected";

		echo "<tr><th>".t("Raportointitaso")."</th><td>
				<select name='raportti'>
				<option value=''>".t("Listaa tuotteittain")."</option>
				<option value='1' $chk[1]>".t("Listaa sarjanumeroittain")."</option>
				</select>
			</td></tr>";

		$chk = array();
		$chk[$kaytetty_haku] = "selected";

		echo "<tr><th>".t("Varastostatus")."</th><td>
				<select name='kaytetty_haku'>
		 		<option value=''>".t("Kaikki")."</option>
				<option value='1' $chk[1]>".t("Uudet")."</option>
				<option value='2' $chk[2]>".t("K‰ytetyt")."</option>
				</select>
			</td></tr>";

		$chk = array();
		$chk[$varasto_haku] = "selected";

		echo "<tr><th>".t("Varastostatus")."</th><td>
				<select name='varasto_haku'>
				<option value=''>".t("Kaikki varastossa olevat")."</option>
				<option value='1' $chk[1]>".t("Vain vapaana varastossa olevat")."</option>
				<option value='2' $chk[2]>".t("Vain varattuina varastossa olevat")."</option>
				</select>
			</td></tr>";


		echo "</table><br>";
		echo "<input type='submit' value='Aja raportti'>";
		echo "</form>";

		require ("../inc/footer.inc");
	}
?>