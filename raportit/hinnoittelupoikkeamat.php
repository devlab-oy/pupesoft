<?php

	if (isset($_POST["tee"])) {
		if ($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
		if ($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
	}

	require ("../inc/parametrit.inc");

	if (isset($tee) and $tee == "lataa_tiedosto") {
		readfile("/tmp/".$tmpfilenimi);
		exit;
	}
	else {

		echo "<font class='head'>",t("Hinnoittelupoikkeamat-raportti"),"</font><hr />";

		$app = !isset($app) ? 1 : (int) $app;
		$akk = !isset($akk) ? (int) date("m", mktime(0, 0, 0, date("m"), 0, date("Y"))) : (int) $akk;
		$avv = !isset($avv) ? (int) date("Y", mktime(0, 0, 0, date("m"), 0, date("Y"))) : (int) $avv;

		$lpp = !isset($lpp) ? (int) date("d", mktime(0, 0, 0, date("m"), 0, date("Y"))) : (int) $lpp;
		$lkk = !isset($lkk) ? (int) date("m", mktime(0, 0, 0, date("m"), 0, date("Y"))) : (int) $lkk;
		$lvv = !isset($lvv) ? (int) date("Y", mktime(0, 0, 0, date("m"), 0, date("Y"))) : (int) $lvv;

		if (!isset($myyja)) $myyja = 0;
		if (!isset($tee)) $tee = '';

		// Tarkistetaan viel‰ p‰iv‰m‰‰r‰t
		if (!checkdate($akk, $app, $avv)) {
			echo "<font class='error'>",t("VIRHE: Alkup‰iv‰m‰‰r‰ on virheellinen"),"!</font><br />";
			$tee = "";
		}

		if (!checkdate($lkk, $lpp, $lvv)) {
			echo "<font class='error'>",t("VIRHE: Loppup‰iv‰m‰‰r‰ on virheellinen"),"!</font><br />";
			$tee = "";
		}

		if ($tee != "" and strtotime("{$avv}-{$akk}-{$app}") > strtotime("{$lvv}-{$lkk}-{$lpp}")) {
			echo "<font class='error'>",t("VIRHE: Alkup‰iv‰m‰‰r‰ on suurempi kuin loppup‰iv‰m‰‰r‰"),"!</font><br />";
			$tee = "";
		}

		echo "<form method='post' action=''>";
		echo "<table>";

		echo "<tr>";
		echo "<th>",t("Alkup‰iv‰m‰‰r‰"),"</th>";
		echo "<td><input type='text' name='app' value='{$app}' size='5' />";
		echo "<input type='text' name='akk' value='{$akk}' size='5' />";
		echo "<input type='text' name='avv' value='{$avv}' size='5' /></td>";
		echo "</tr>";

		echo "<tr>";
		echo "<th>",t("Loppup‰iv‰m‰‰r‰"),"</th>";
		echo "<td><input type='text' name='lpp' value='{$lpp}' size='5' />";
		echo "<input type='text' name='lkk' value='{$lkk}' size='5' />";
		echo "<input type='text' name='lvv' value='{$lvv}' size='5' /></td>";
		echo "</tr>";

		echo "<tr>";
		echo "<th>",t("Myyj‰")."</th>";
		echo "<td><select name='myyja'>";
		echo "<option value='0'>",t("Valitse"),"</option>";

		$query = "	SELECT tunnus, kuka, nimi, myyja
					FROM kuka
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND extranet = ''
					AND nimi != ''
					AND myyja != ''
					ORDER BY nimi";
		$myyjares = pupe_query($query);

		while ($myyjarow = mysql_fetch_assoc($myyjares)) {

			$sel = $myyjarow['tunnus'] == $myyja ? " selected" : "";

			echo "<option value='{$myyjarow['tunnus']}'{$sel}>{$myyjarow['nimi']}</option>";
		}

		echo "</select></td>";
		echo "</tr>";

		echo "<tr><td class='back' colspan='2'>";
		echo "<input type='hidden' name='tee' value='hae' />";
		echo "<input type='submit' value='",t("Hae"),"' />";
		echo "</td></tr>";

		echo "</table>";
		echo "</form>";

		if ($tee == 'hae') {

			$myyja = (int) $myyja;

			$myyjalisa = $myyja != 0 ? "AND lasku.myyja = '{$myyja}'" : "";

			// Haetaan laskutetut tilaukset
			$query = "	SELECT lasku.*, kuka.nimi AS myyja, TRIM(CONCAT(lasku.nimi, ' ', lasku.nimitark)) AS nimi
						FROM lasku
						LEFT JOIN kuka ON (kuka.yhtio = lasku.yhtio AND kuka.tunnus = lasku.myyja)
						WHERE lasku.yhtio = '{$kukarow['yhtio']}'
						AND lasku.tila = 'L'
						AND lasku.alatila = 'X'
						AND lasku.tapvm >= '{$avv}-{$akk}-{$app}'
						AND lasku.tapvm <= '{$lvv}-{$lkk}-{$lpp}'
						{$myyjalisa}
						ORDER BY lasku.myyja, lasku.tapvm, lasku.tunnus";
			$laskures = pupe_query($query);

			echo "<table>";
			echo "<tr>";
			echo "<th>",t("Myyj‰"),"</th>";
			echo "<th>",t("Tilausnro"),"</th>";
			echo "<th>",t("Kokonaissumma"),"</th>";
			echo "<th>",t("Kokonaisrivim‰‰r‰"),"</th>";
			echo "<th>",t("Asiakas"),"</th>";
			echo "<th>",t("Sis‰inen kommentti"),"</th>";
			echo "<th>",t("Tuoteno"),"</th>";
			echo "<th>",t("Nimitys"),"</th>";
			echo "<th>",t("Kpl"),"</th>";
			echo "<th>",t("Asiakashinta"),"</th>";
			echo "<th>",t("Muutettu hinta"),"</th>";
			echo "<th>",t("Ero %"),"</th>";
			echo "<th>",t("Menetys"),"</th>";
			echo "</tr>";

			include('inc/pupeExcel.inc');

			$worksheet 	 = new pupeExcel();
			$format_bold = array("bold" => TRUE);

			$excelrivi 	 = 0;
			$excelsarake = 0;

			$data = array();

			$i = 0;

			while ($laskurow = mysql_fetch_assoc($laskures)) {

				$x = 1;

				$query = "	SELECT *
							FROM tilausrivi
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND otunnus = '{$laskurow['tunnus']}'
							AND tyyppi = 'L'";
				$tilausrivires = pupe_query($query);

				$num_rows = mysql_num_rows($tilausrivires);

				while ($tilausrivirow = mysql_fetch_assoc($tilausrivires)) {

					$query = "	SELECT *
								FROM tuote
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tuoteno = '{$tilausrivirow['tuoteno']}'";
					$tres = pupe_query($query);
					$trow = mysql_fetch_assoc($tres);

					list($lis_hinta, $lis_netto, $lis_ale_kaikki, $alehinta_alv, $alehinta_val) = alehinta($laskurow, $trow, $tilausrivirow['kpl'], '', '', array());

					if ($lis_hinta - $tilausrivirow['hinta'] == 0) continue;

					if ($x > 1) {
						echo "<tr>";
						echo "<td>&nbsp;</td>";
						echo "<td>&nbsp;</td>";
						echo "<td>&nbsp;</td>";
						echo "<td>&nbsp;</td>";
						echo "<td>&nbsp;</td>";
						echo "<td>&nbsp;</td>";

						$data[$i]['myyja'] = '';
						$data[$i]['tunnus'] = '';
						$data[$i]['num_rows'] = '';
						$data[$i]['nimi'] = '';
						$data[$i]['sisviesti3'] = '';
					}
					else {
						echo "
						<tr>
						<td>{$laskurow['myyja']}</td>
						<td>{$laskurow['tunnus']}</td>
						<td>{$laskurow['summa']}</td>
						<td>{$num_rows}</td>
						<td>{$laskurow['nimi']}</td>
						<td>{$laskurow['sisviesti3']}</td>";

						$data[$i]['myyja'] = $laskurow['myyja'];
						$data[$i]['tunnus'] = $laskurow['tunnus'];
						$data[$i]['num_rows'] = $num_rows;
						$data[$i]['nimi'] = $laskurow['nimi'];
						$data[$i]['sisviesti3'] = $laskurow['sisviesti3'];
					}

					$ero = $tilausrivirow['hinta'] - $lis_hinta;
					$eropros = $tilausrivirow['hinta'] == 0 ? 100 : abs(round((($ero) / $tilausrivirow['hinta']) * 100, 2));

					$lis_hinta = hintapyoristys($lis_hinta);
					$tilausrivirow['hinta'] = hintapyoristys($tilausrivirow['hinta']);
					$ero = hintapyoristys($ero);

					echo "<td>{$tilausrivirow['tuoteno']}</td>";
					echo "<td>{$tilausrivirow['nimitys']}</td>";
					echo "<td>{$tilausrivirow['kpl']}</td>";
					echo "<td>{$lis_hinta}</td>";
					echo "<td>{$tilausrivirow['hinta']}</td>";
					echo "<td>{$eropros}</td>";
					echo $ero < 0 ? "<td><font class='error'>{$ero}</font></td>" : "<td><font class='ok'>{$ero}</font></td>";

					echo "</tr>";

					$data[$i]['tuoteno'] = $tilausrivirow['tuoteno'];
					$data[$i]['nimitys'] = $tilausrivirow['nimitys'];
					$data[$i]['kpl'] = $tilausrivirow['kpl'];
					$data[$i]['lis_hinta'] = $lis_hinta;
					$data[$i]['hinta'] = $tilausrivirow['hinta'];
					$data[$i]['eropros'] = $eropros;
					$data[$i]['ero'] = $ero;

					$i++;
					$x++;
				}

				echo "</tr>";
			}

			echo "</table>";

			if (count($data) > 0) {

				foreach(array_keys($data[0]) AS $key) {
					$worksheet->writeString($excelrivi, $excelsarake, ucfirst(t($key)), $format_bold);
					$excelsarake++;
				}

				$excelsarake = 0;
				$excelrivi++;

				foreach($data as $set) {
					foreach($set as $k => $v) {
						$worksheet->write($excelrivi, $excelsarake, $v);
						$excelsarake++;
					}

					$excelsarake = 0;
					$excelrivi++;
				}

				$excelnimi = $worksheet->close();

				echo "<br />";
				echo "<form method='post' class='multisubmit'>";
				echo "<table>";
				echo "<tr><th>",t("Tallenna raportti (xlsx)"),":</th>";
				echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
				echo "<input type='hidden' name='kaunisnimi' value='Hinnoittelupoikkeamat-raportti.xlsx'>";
				echo "<input type='hidden' name='tmpfilenimi' value='{$excelnimi}'>";
				echo "<td class='back'><input type='submit' value='",t("Tallenna"),"'></td></tr>";
				echo "</table>";
				echo "</form><br />";

			}
		}

		require("inc/footer.inc");
	}
