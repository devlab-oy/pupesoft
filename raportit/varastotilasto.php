<?php

	if (isset($_POST["tee"])) {
		if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
		if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
	}

	//* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *//
	$useslave = 1;

	require ("../inc/parametrit.inc");

	if (isset($tee) and $tee == "lataa_tiedosto") {
		readfile("/tmp/".$tmpfilenimi);
		exit;
	}

	echo "<font class=head>".t("Varastotilasto")."</font><hr>";

	if (!isset($kka))
		$kka = '01';
	if (!isset($vva))
		$vva = date("Y");
	if (!isset($ppa))
		$ppa = '01';

	if (!isset($kkl))
		$kkl = date("m");
	if (!isset($vvl))
		$vvl = date("Y");
	if (!isset($ppl))
		$ppl = date("d");

	// k‰yttis
	echo "<form action='$PHP_SELF' method='POST'>";
	echo "<input type='hidden' name='tee' value='raportoi'>";

	echo "<table>";
	echo "<tr><th>".t("Alkup‰iv‰m‰‰r‰ (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppa' value='$ppa' size='3'></td>
			<td><input type='text' name='kka' value='$kka' size='3'></td>
			<td><input type='text' name='vva' value='$vva' size='5'></td></tr>";

	echo "<tr><th>".t("Loppup‰iv‰m‰‰r‰ (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppl' value='$ppl' size='3'></td>
			<td><input type='text' name='kkl' value='$kkl' size='3'></td>
			<td><input type='text' name='vvl' value='$vvl' size='5'></td></tr>";
			
	echo "<tr><th>".t("Rajaukset")."</th><td colspan='3'>";

	$monivalintalaatikot = array('OSASTO', 'TRY', '<br>TUOTEMERKKI');
	require ("tilauskasittely/monivalintalaatikot.inc");

	echo "</td></tr>";

	$nollapiilochk = "";
	if (isset($nollapiilo) and $nollapiilo != '') $nollapiilochk	= "CHECKED";
	echo "<tr><th>".t("Piilota nollarivit")."</th><td colspan='3'><input type='checkbox' name='nollapiilo' $nollapiilochk></td></tr>";

	echo "</table>";
	echo "<br><input type='submit' value='".t("Aja raportti")."' name='painoinnappia'>";
	echo "</form>";
	echo "<br><br>";

	if ($tee != "" and isset($painoinnappia)) {

		// valitun jakson vika p‰iv‰
		$ppl = date("t", mktime(0, 0, 0, $kkl, 1, $vvl));

		if (@include('Spreadsheet/Excel/Writer.php')) {

			//keksit‰‰n failille joku varmasti uniikki nimi:
			list($usec, $sec) = explode(' ', microtime());
			mt_srand((float) $sec + ((float) $usec * 100000));
			$excelnimi = md5(uniqid(mt_rand(), true)).".xls";

			$workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
			$workbook->setVersion(8);
			$worksheet =& $workbook->addWorksheet('Varastoluettelo');

			$format_bold =& $workbook->addFormat();
			$format_bold->setBold();

			$excelrivi = 0;
		}

		$query = "	SELECT *
					FROM tuote
					WHERE tuote.yhtio = '{$kukarow["yhtio"]}'
					{$lisa}
					and (tuote.status != 'P' or (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0) > 0)
					ORDER BY osasto, try, tuoteno";
		$eresult = pupe_query($query);

		$rivimaara   = mysql_num_rows($eresult);
		$rivilimitti = 1000;
		
		echo "<table>
				<tr>
				<th>",t("Valittu aikav‰li"),"</th>
				<td>{$ppa}</td>
				<td>{$kka}</td>
				<td>{$vva}</td>
				<th>-</th>
				<td>{$ppl}</td>
				<td>{$kkl}</td>
				<td>{$vvl}</td>
				</tr>
			</table><br><br>";

		if ($rivimaara > $rivilimitti) {
			echo "<br><font class='error'>",t("Hakutulos oli liian suuri"),"!</font><br>";
			echo "<font class='error'>",t("Tallenna/avaa tulos exceliss‰"),"!</font><br><br>";
		}
		else {
			echo "<table>";
			echo "<th>".t("Osasto")."</th>";
			echo "<th>".t("Tuoteryhm‰")."</th>";
			echo "<th>".t("Tuoteno")."</th>";
			echo "<th>".t("Nimitys")."</th>";
			echo "<th>".t("Varastosaldo")."</th>";
			echo "<th>".t("Myyntihinta")."</th>";
			echo "<th>".t("Varmuusvarasto")."</th>";
			echo "<th>".t("Tilattu")."</th>";
			echo "<th>".t("Toimaika")."</th>";
			echo "<th>".t("Varattu")."</th>";
			echo "<th>".t("Myynti")."<br>".t("aikav‰lill‰")."</th>";
			echo "<th>".t("Myynti")."<br>6kk</th>";
			echo "<th>".t("Myynti")."<br>3kk</th>";
		}

		if (isset($workbook)) {
			$excelsarake = 0;

			$worksheet->writeString($excelrivi, $excelsarake++, t("Osasto"));
			$worksheet->writeString($excelrivi, $excelsarake++, t("Tuoteryhm‰"));
			$worksheet->writeString($excelrivi, $excelsarake++, t("Tuoteno"));
			$worksheet->writeString($excelrivi, $excelsarake++, t("Nimitys"));
			$worksheet->writeString($excelrivi, $excelsarake++, t("Varastosaldo"));
			$worksheet->writeString($excelrivi, $excelsarake++, t("Myyntihinta"));
			$worksheet->writeString($excelrivi, $excelsarake++, t("Varmuusvarasto"));
			$worksheet->writeString($excelrivi, $excelsarake++, t("Tilattu m‰‰r‰"));
			$worksheet->writeString($excelrivi, $excelsarake++, t("Toimitus aika"));
			$worksheet->writeString($excelrivi, $excelsarake++, t("Varattu saldo"));
			$worksheet->writeString($excelrivi, $excelsarake++, t("Myynti")." $vvl");
			$worksheet->writeString($excelrivi, $excelsarake++, t("Myynti 6kk"));
			$worksheet->writeString($excelrivi, $excelsarake++, t("Myynti 3kk"));

			$excelrivi++;
		}

		while ($row = mysql_fetch_assoc($eresult)) {

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

			// myyntipuoli
			$query = "	SELECT
						round(sum(if(laskutettuaika >= '{$vvl}-01-01', rivihinta, 0)), 2) myyntiVA,
						round(sum(if(laskutettuaika >= date_sub('{$vvl}-{$kkl}-{$ppl}', interval 6 month), rivihinta, 0)), 2) myynti6kk,
						round(sum(if(laskutettuaika >= date_sub('{$vvl}-{$kkl}-{$ppl}', interval 3 month), rivihinta, 0)), 2) myynti3kk
						FROM tilausrivi
						WHERE yhtio = '{$kukarow["yhtio"]}'
						AND tuoteno = '{$row["tuoteno"]}'
						AND tyyppi = 'L'
						and laskutettuaika >= if(date_sub('{$vvl}-{$kkl}-{$ppl}',interval 6 month) < '{$vvl}-01-01', date_sub('{$vvl}-{$kkl}-{$ppl}',interval 6 month), '{$vvl}-01-01')
						and laskutettuaika >= if(date_sub('{$vvl}-{$kkl}-{$ppl}',interval 6 month) < '{$vvl}-01-01', date_sub('{$vvl}-{$kkl}-{$ppl}',interval 6 month), '{$vvl}-01-01')
						AND kpl != 0";
			$myyntiresult = pupe_query($query);

			$myyntirivi = mysql_fetch_assoc($myyntiresult);

			$osastores = t_avainsana("OSASTO", "", "and avainsana.selite ='$row[osasto]'");
			$osastorow = mysql_fetch_assoc($osastores);

			if ($osastorow['selitetark'] != "") $row['osasto'] = $row['osasto']." - ".$osastorow['selitetark'];

			$tryres = t_avainsana("TRY", "", "and avainsana.selite ='$row[try]'");
			$tryrow = mysql_fetch_assoc($tryres);

			if ($tryrow['selitetark'] != "") $row['try'] = $row['try']." - ".$tryrow['selitetark'];

			list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($row["tuoteno"]);

			$varattu = $saldo - $myytavissa;

			// Jos kaikki luvut on nollaa, niin skipataan rivi
			if ($nollapiilo != '' and (float) $saldo == 0 and (float) $ostorivi["tulossa"] == 0 and  (float) $varattu == 0 and (float) $myyntirivi["myyntiVA"] == 0) {
				continue;
			}

			if ($rivimaara <= $rivilimitti) {
				echo "<tr>";
				echo "<td>$row[osasto]</td>";
				echo "<td>$row[try]</td>";
				echo "<td><a href='{$palvelin2}tuote.php?tee=Z&tuoteno=".urlencode($row["tuoteno"])."'>$row[tuoteno]</a></td>";
				echo "<td>$row[nimitys]</td>";
				echo "<td align='right'>$saldo</td>";
				echo "<td align='right'>".hintapyoristys($row['myyntihinta'])."</td>";
				echo "<td align='right'>$row[varmuus_varasto]</td>";
				echo "<td align='right'>$ostorivi[tulossa]</td>";
				echo "<td align='right'>".tv1dateconv($ostorivi['toimaika'])."</td>";
				echo "<td align='right'>$varattu</td>";
				echo "<td align='right'>$myyntirivi[myyntiVA]</td>";
				echo "<td align='right'>$myyntirivi[myynti6kk]</td>";
				echo "<td align='right'>$myyntirivi[myynti3kk]</td>";
				echo "</tr>";
			}

			if (isset($workbook)) {
				$excelsarake = 0;

				$worksheet->writeString($excelrivi, $excelsarake++, $row["osasto"]);
				$worksheet->writeString($excelrivi, $excelsarake++, $row["try"]);
				$worksheet->writeString($excelrivi, $excelsarake++, $row["tuoteno"]);
				$worksheet->writeString($excelrivi, $excelsarake++, $row["nimitys"]);
				$worksheet->writeNumber($excelrivi, $excelsarake++, $saldo);
				$worksheet->writeNumber($excelrivi, $excelsarake++, $row["myyntihinta"]);
				$worksheet->writeNumber($excelrivi, $excelsarake++, $row["varmuus_varasto"]);
				$worksheet->writeNumber($excelrivi, $excelsarake++, $row["tulossa"]);
				$worksheet->writeString($excelrivi, $excelsarake++, $row["toimaika"]);
				$worksheet->writeNumber($excelrivi, $excelsarake++, $varattu);
				$worksheet->writeNumber($excelrivi, $excelsarake++, $myyntirivi["myyntiVA"]);
				$worksheet->writeNumber($excelrivi, $excelsarake++, $myyntirivi["myynti6kk"]);
				$worksheet->writeNumber($excelrivi, $excelsarake++, $myyntirivi["myynti3kk"]);

				$excelrivi++;
			}
		}

		if ($rivimaara <= $rivilimitti) {
			echo "</table>";
		}

		echo "<br>";

		if (isset($workbook)) {
			// We need to explicitly close the workbook
			$workbook->close();

			echo "<table>";
			echo "<tr><th>".t("Tallenna excel").":</th>";
			echo "<form method='post' action='$PHP_SELF'>";
			echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
			echo "<input type='hidden' name='kaunisnimi' value='Varastotilasto.xls'>";
			echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
			echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
			echo "</table><br>";
		}
	}

	require ("inc/footer.inc");
?>