<?php
	if ($_REQUEST['malli'] == 'PDF24' or $_REQUEST['malli'] == 'PDF40') {
		$_REQUEST['nayta_pdf'] = 1;
		$nayta_pdf = 1;
	}

	require("inc/parametrit.inc");

	//$toim='YKS' tarkottaa yksinkertainen ja silloin ei v‰litet‰ onko tuotteella eankoodia vaan tulostetaan suoraan tuoteno viivakoodiin

	if (!isset($nayta_pdf)) echo "<font class='head'>",t("Tulosta tuotetarroja"),"</font><hr>";

	if (!isset($toim)) $toim = '';
	if (!isset($tuoteno)) $tuoteno = '';
	if (!isset($updateean)) $updateean = '';
	if (!isset($tee)) $tee = '';
	if (!isset($malli)) $malli = '';
	if (!isset($ulos)) $ulos = '';
	if (!isset($kirjoitin)) $kirjoitin = '';

	if (!isset($tulostakappale) or $tulostakappale == '') {
		$tulostakappale = 1;
	}

	$lets = '';
	$uusean = '';

	if ($updateean != '' and $uuseankoodi != '' and $tee != '' and $toim != 'YKS') {
		$query = "	UPDATE tuote
					SET eankoodi = '$uuseankoodi'
					WHERE yhtio  = '$kukarow[yhtio]'
					and tuoteno  = '$tuoteno'";
		$resulteankoodi = mysql_query($query) or pupe_error($query);
	}

	$koodi = 'eankoodi';

	if ($toim == 'YKS') {
		$koodi = 'tuoteno';
	}

	if ($tee == 'H') {

		if ($ahyllyalue == '' or $ahyllynro == '' or $ahyllyvali == '' or $ahyllytaso == '' or $lhyllyalue == '' or $lhyllynro == '' or $lhyllyvali == '' or $lhyllytaso == '') {
			$tee = 'Y';
			$varaosavirhe =  t("Sinun on annettava t‰ydellinen osoitev‰li")."<br>";
		}

		$lisa = "";

		if ($saldo == '1'){
			$lisa = " AND saldo > 0	";
		}

		if ($tee == 'H') {

			$sql = "SELECT tuotepaikat.tuoteno tuoteno
					FROM tuotepaikat
					WHERE tuotepaikat.yhtio = '$kukarow[yhtio]' and
					concat(rpad(upper(hyllyalue),  5, '0'),lpad(upper(hyllynro),  5, '0'),lpad(upper(hyllyvali),  5, '0'),lpad(upper(hyllytaso),  5, '0')) >= concat(rpad(upper('$ahyllyalue'), 5, '0'),lpad(upper('$ahyllynro'), 5, '0'),lpad(upper('$ahyllyvali'), 5, '0'),lpad(upper('$ahyllytaso'), 5, '0')) and
					concat(rpad(upper(hyllyalue),  5, '0'),lpad(upper(hyllynro),  5, '0'),lpad(upper(hyllyvali),  5, '0'),lpad(upper(hyllytaso),  5, '0')) <= concat(rpad(upper('$lhyllyalue'), 5, '0'),lpad(upper('$lhyllynro'), 5, '0'),lpad(upper('$lhyllyvali'), 5, '0'),lpad(upper('$lhyllytaso'), 5, '0'))
					$lisa
					ORDER BY 1";
			$res = mysql_query($sql) or pupe_error($sql);

			$tuotteet = array();

			while ($resrows = mysql_fetch_assoc($res)) {
				$tuotteet[$resrows['tuoteno']] = $resrows['tuoteno'];
			}

			$lets = "go";
		}
	}

	if ($tee == 'Z') {
		require "inc/tuotehaku.inc";
	}

	if ($ulos != "") {
			$formi = 'hakua';
			echo "<form method='post' name='$formi' autocomplete='off'>";
			echo "<input type='hidden' name='tee' value='Z'>";
			echo "<input type='hidden' name='tulostakappale' value='$tulostakappale'>";
			echo "<input type='hidden' name='kirjoitin' value='$kirjoitin'>";
			echo "<input type='hidden' name='toim' value='$toim'>";
			echo "<input type='hidden' name='malli' value='$malli'>";
			echo "<table><tr>";
			echo "<td>".t("Valitse listasta").":</td>";
			echo "<td>$ulos</td>";
			echo "<td class='back'><input type='Submit' value='".t("Valitse")."'></td>";
			echo "</tr></table>";
			echo "</form>";
	}

	if ($tee != 'H') {

		$query = "SELECT $koodi FROM tuote WHERE yhtio = '$kukarow[yhtio]' AND tuoteno = '$tuoteno'";
		$eankoodires = mysql_query($query) or pupe_error($query);
		$eankoodirow = mysql_fetch_array($eankoodires);

		if ($eankoodirow[0] != 0 and $eankoodirow[0] != '') {
			$lets='go';
		}
		elseif ($tee != '' and $tee !='Y' and $tee !='H') {
			if ($toim == 'YKS' and $eankoodirow[0] != '' and $eankoodirow[0] != "0") {
				$lets = 'go';
			}
			else {
				$tee = 'Y';
				$varaosavirhe = t("Tuotteella ei ole eankoodia. Anna se nyt niin se p‰ivitet‰‰n tuotteen tietoihin");
				$uusean = 'jeppis';
			}
		}
	}

	if ($malli == '' and ($tee == 'Z' or $tee == 'H')) {
		$tee = 'Y';
		$varaosavirhe = t("Sinun on valittava tulostusmalli");
	}

	if ($tee == 'Y') echo "<font class='error'>$varaosavirhe</font>";

	$tkpl = $tulostakappale;

	if (($tee == 'Z' or $tee == 'H') and $ulos == '') {

		if ($lets == 'go') {
			$query = "	SELECT komento
						FROM kirjoittimet
						WHERE yhtio = '$kukarow[yhtio]'
						and tunnus = '$kirjoitin'";
			$komres = mysql_query($query) or pupe_error($query);
			$komrow = mysql_fetch_array($komres);
			$komento = $komrow['komento'];

			if (!isset($tuotteet)) {
				$tuotteet = array();
				$tuotteet[$tuoteno] = $tuoteno;
			}

			require_once("pdflib/phppdflib.class.php");

			if ($malli == 'PDF24' or $malli == 'PDF40') {
				//PDF parametrit
				if (!isset($pdf)) {
					$pdf = new pdffile;
					$pdf->set_default('margin-top', 	0);
					$pdf->set_default('margin-bottom', 	0);
					$pdf->set_default('margin-left', 	0);
					$pdf->set_default('margin-right', 	0);
				}
			}

			foreach ($tuotteet as $key => $tuoteno) {
				if ($malli != 'Zebra' and $malli != 'Zebra_hylly' and $malli != 'Zebra_tuote') {
					for ($a = 0; $a < $tkpl; $a++) {
						if ($malli == 'Tec') {
							require("inc/tulosta_tuotetarrat_tec.inc");
						}
						elseif ($malli == 'Intermec') {
							require("inc/tulosta_tuotetarrat_intermec.inc");
						}
						elseif ($malli == 'PDF24' or $malli == 'PDF40') {
							require("inc/tulosta_tuotetarrat_pdf.inc");
						}
					}
				}
				else {
					require("inc/tulosta_tuotetarrat_zebra.inc");
				}
			}

			if ($malli == 'PDF24' or $malli == 'PDF40') {
				//keksit‰‰n uudelle failille joku varmasti uniikki nimi:
				list($usec, $sec) = explode(' ', microtime());
				mt_srand((float) $sec + ((float) $usec * 100000));
				$pdffilenimi = "/tmp/tuotetarrat_ean_pdf-".md5(uniqid(mt_rand(), true)).".pdf";

				//kirjoitetaan pdf faili levylle..
				$fh = fopen($pdffilenimi, "w");
				if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus ep‰onnistui $pdffilenimi");
				fclose($fh);

				//Tyˆnnet‰‰n tuo pdf vaan putkeen!
				echo file_get_contents($pdffilenimi);

				//poistetaan tmp file samantien kuleksimasta...
				system("rm -f $pdffilenimi");
			}

			$tuoteno = '';
			$tee = '';
		}
		else {
			echo t("nyt on jotain m‰t‰‰!!!!");
		}
	}

	if (!isset($nayta_pdf)) {
		$formi  = 'formi';
		$kentta = 'tuoteno';

		echo "<form method='post' name='$formi' autocomplete='off'>";
		echo "<input type='hidden' name='tee' value='Z'>";
		echo "<input type='hidden' name='toim' value='$toim'>";

		echo "<table>";
		echo "<tr><th colspan='4'><center>".t("Tulostetaan tuotetarrat tuotenumeron mukaan")."</center></th><tr>";
		echo "<tr>";
		echo "<th>".t("Tuotenumero")."</th>";
		echo "<th>".t("KPL")."</th>";
		echo "<th>".t("Kirjoitin")."</th>";
		echo "<th>".t("Malli")."</th>";
		if ($uusean!= '') {
			echo "<th>".t("Eankoodi")."</th>";
		}

		echo "<tr>";
		echo "<td><input type='text' name='tuoteno' size='20' maxlength='60' value='$tuoteno'></td>";
		echo "<td><input type='text' name='tulostakappale' size='3' value='$tulostakappale'></td>";
		echo "<td><select name='kirjoitin'>";
		echo "<option value=''>".t("Ei kirjoitinta")."</option>";

		$query = "	SELECT *
					FROM kirjoittimet
					WHERE yhtio = '$kukarow[yhtio]'
					and komento != 'email'
					order by kirjoitin";
		$kires = mysql_query($query) or pupe_error($query);

		while ($kirow = mysql_fetch_array($kires)) {
			if ($kirow['tunnus'] == $kirjoitin) $select = 'SELECTED';
			else $select = '';
			echo "<option value='$kirow[tunnus]' $select>$kirow[kirjoitin]</option>";
		}

		echo "</select></td>";

		//t‰h‰n arrayhin pit‰‰ lis‰t‰ uusia malleja jos tehd‰‰n uusia inccej‰ ja ylemp‰n‰ tehd‰ iffej‰.
		$pohjat = array();
		$pohjat[] = 'Tec';
		$pohjat[] = 'Intermec';
		$pohjat[] = 'Zebra';
		$pohjat[] = 'Zebra_hylly';
		$pohjat[] = 'Zebra_tuote';
		$pohjat[] = 'PDF24';
		$pohjat[] = 'PDF40';

		echo "<td><select name='malli'>";
		echo "<option value=''>".t("Ei mallia")."</option>";

		foreach ($pohjat as $pohja) {
			if ($pohja == $malli) $select = 'SELECTED';
			else $select = '';
			echo "<option value='$pohja' $select>$pohja</option>";
		}

		echo "</select></td>";

		if ($uusean != '') {
			echo "<input type='hidden' name='updateean' value='joo'>";
			echo "<td><input type='text' name='uuseankoodi' size='13' maxlength='13' value='$uuseankoodi'></td>";
		}

		echo "<td class='back'><input type='Submit' value='".t("Tulosta")."'></td>";
		echo "</tr>";
		echo "</table>";
		echo "</form>";
		 // t‰st‰ alkaa toinen formi
		$sel = "";
		$lisa = "";

		if ($saldo =='1') {
			$sel = "CHECKED";
		}

		echo "<form method='post' autocomplete='off'>";
		echo "<input type='hidden' name='tee' value='H'>";
		echo "<input type='hidden' name='toim' value='$toim'>";
		echo "<br>";

		echo "<table>";
		echo "<tr><th colspan='2'><center>".t("Tulostetaan tuotetarrat hyllyjen v‰lilt‰")."</center></th><tr>";
		echo "<tr><th>".t("Alkuosoite")."</th>";
		echo "<td>",tee_hyllyalue_input("ahyllyalue", $ahyllyalue);
		echo "-";
		echo "<input type='text' name='ahyllynro' size='5' maxlength='5' value='$ahyllynro'>";
		echo "-";
		echo "<input type='text' name='ahyllyvali' size='5' maxlength='5' value='$ahyllyvali'>";
		echo "-";
		echo "<input type='text' name='ahyllytaso' size='5' maxlength='5' value='$ahyllytaso'></td></tr>";

		echo "<tr><th>".t("Loppuosoite")."</th>";
		echo "<td>",tee_hyllyalue_input("lhyllyalue", $lhyllyalue);
		echo "-";
		echo "<input type='text' name='lhyllynro' size='5' maxlength='5' value='$lhyllynro'>";
		echo "-";
		echo "<input type='text' name='lhyllyvali' size='5' maxlength='5' value='$lhyllyvali'>";
		echo "-";
		echo "<input type='text' name='lhyllytaso' size='5' maxlength='5' value='$lhyllytaso'></td></tr>";
		echo "<tr><th>".t("Vain tuotteet joilla on saldoa hyllyill‰")."</th><td><input type='checkbox' name='saldo' value='1' $sel> </td>";

		echo "<tr><th>".t("KPL")."</th><td><input type='text' name='tulostakappale' size='3' value='$tulostakappale'></td><tr>";
		echo "<tr><th>".t("Kirjoitin")."</th><td><select name='kirjoitin'>";
		echo "<option value=''>".t("Ei kirjoitinta")."</option>";

		mysql_data_seek($kires,0);

		while ($kirow = mysql_fetch_array($kires)) {
			if ($kirow['tunnus'] == $kirjoitin) $select = 'SELECTED';
			else $select = '';
			echo "<option value='$kirow[tunnus]' $select>$kirow[kirjoitin]</option>";
		}

		echo "</select></td>";

		echo "</tr><tr><th>".t("Mallipohja")."</th><td><select name='malli'>";
		echo "<option value=''>".t("Ei mallia")."</option>";

		foreach ($pohjat as $pohja) {
			if ($pohja == $malli) $select = 'SELECTED';
			else $select = '';
			echo "<option value='$pohja' $select>$pohja</option>";
		}

		echo "</select></td>";
		echo "<tr><td class='back'></td><td class='back'><input type='Submit' value='".t("Tulosta tarrat")."'></td>";
		echo "</tr>";
		echo "</table>";
		echo "<br>";
		echo "</form>";
	}

	require("inc/footer.inc");
