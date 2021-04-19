<?php

	$useslave = ($_REQUEST['tee'] == 'tallenna' or $_REQUEST['tee'] == 'uusiraportti') ? '' : 1;

	require ("inc/parametrit.inc");

	echo "<font class='head'>".t("Tehtaan saldot")."</font><hr>";

	if (!isset($tee)) $tee = '';
	if (!isset($tuotteen_toimittaja)) $tuotteen_toimittaja = '';
	if (!isset($tuotenumeron_sijainti_pupessa)) $tuotenumeron_sijainti_pupessa = '';
	if (!isset($tuotenumeron_sarake)) $tuotenumeron_sarake = '';
	if (!isset($tehtaan_saldon_sarake)) $tehtaan_saldon_sarake = '';
	if (!isset($error)) $error = 0;
	if (!isset($ala_tallenna)) $ala_tallenna = array("kysely", "uusirappari");

	if ($tee == "tallenna" or $tee == "lataavanha") {
		if (isset($kysely) and trim($kysely) != '') {
			list($kysely_kuka, $kysely_mika) = explode("#", $kysely);

			if ($tee == "tallenna") {
				tallenna_muisti($kysely_mika, $ala_tallenna, $kysely_kuka);
				$tee = '';
			}

			if ($tee == "lataavanha") {
				hae_muisti($kysely_mika, $kysely_kuka);
				$kysely = "$kysely_kuka#$kysely_mika";
				$tee = '';
			}
		}
		else {
			echo "<font class='error'>",t("Et ole valinnut raporttia"),"!</font><br/>";
			$tee = '';
			$error++;
		}
	}

	if ($tee == 'uusiraportti') {
		if (trim($uusirappari) != '') {
			tallenna_muisti($uusirappari, $ala_tallenna);
			$kysely = "$kukarow[kuka]#$uusirappari";
			$tee = '';
		}
		else {
			echo "<font class='error'>",t("Tallennettavan raportin nimi ei saa olla tyhjä"),"!</font><br/>";
			$tee = '';
			$error++;
		}
	}

	if ($tee == 'GO' and $error == 0) {

		if (trim($tuotteen_toimittaja) == '') {
			echo "<font class='error'>",t("Et valinnut toimittajaa"),"!</font><br/>";
			$tee = '';
			$error++;
		}

		if (trim($tuotenumeron_sarake) == '') {
			echo "<font class='error'>",t("Et syöttänyt tuotenumeron sarakenumeroa"),"!</font><br/>";
			$tee = '';
			$error++;
		}
		elseif (!is_numeric($tuotenumeron_sarake)) {
			echo "<font class='error'>",t("Tuotenumeron sarakenumero täytyy olla numeerinen"),"!</font><br/>";
			$tee = '';
			$error++;
		}

		if (trim($tehtaan_saldon_sarake) == '') {
			echo "<font class='error'>",t("Et syöttänyt tehtaan saldon sarakenumeroa"),"!</font><br/>";
			$tee = '';
			$error++;
		}
		elseif (!is_numeric($tehtaan_saldon_sarake)) {
			echo "<font class='error'>",t("Tehtaan saldon sarakenumero täytyy olla numeerinen"),"!</font><br/>";
			$tee = '';
			$error++;
		}

		if ($tuotenumeron_sarake == $tehtaan_saldon_sarake) {
			echo "<font class='error'>",t("Tuotenumeron sarakenumero ja tehtaan saldon sarakenumero eivät saa olla samat"),"!</font><br/>";
			$tee = '';
			$error++;
		}

		if ($tuotenumeron_sarake == 0 or $tehtaan_saldon_sarake == 0) {
			echo "<font class='error'>",t("Tuotenumeron sarakenumero tai tehtaan saldon sarakenumero eivät saa olla nollaa"),"!</font><br/>";
			$tee = '';
			$error++;
		}

		if (!isset($_FILES['userfile']['error']) or $_FILES['userfile']['error'] == 4) {
			echo "<font class='error'>",t("Et valinnut tiedostoa"),"!</font><br/>";
			$tee = '';
			$error++;
		}
		else {

			$path_parts = pathinfo($_FILES['userfile']['name']);

			if (strtolower($path_parts['extension']) != 'xls' and strtolower($path_parts['extension']) != 'txt' and strtolower($path_parts['extension']) != 'csv') {
				echo "<font class='error'>",t("Virheellinen tiedostopääte"),"!</font><br/>";
				$tee = '';
				$error++;
			}

			if (is_uploaded_file($_FILES['userfile']['tmp_name']) == TRUE) {
				$file = tarkasta_liite("userfile", array("XLS","TXT","CSV"));
			}
			else {
				$error++;
			}

			if ($file === FALSE) $error++;
		}

		if ($error == 0) {

			$tuo_sarake = (int) $tuotenumeron_sarake - 1;
			$teh_sarake = (int) $tehtaan_saldon_sarake - 1;

			if (strtolower($path_parts['extension']) == 'xls') {
				require_once ('excel_reader/reader.php');

				// ExcelFile
				$data = new Spreadsheet_Excel_Reader();

				// Set output Encoding.
				$data->setOutputEncoding('CP1251');
				$data->setRowColOffset(0);
				$data->read($_FILES['userfile']['tmp_name']);

				$tuote = array();
				$saldo = array();

				for ($excei = 0; $excei < $data->sheets[0]['numRows']; $excei++) {

					if (!isset($data->sheets[0]['cells'][$excei][$tuo_sarake]) or !isset($data->sheets[0]['cells'][$excei][$teh_sarake])) {
						echo "<font class='error'>",t("Virheellinen sarakenumero"),"!</font><br/>";
						$error++;
						unset($tuote);
						break;
					}

					// luetaan rivi tiedostosta..
					$tuo = mysql_real_escape_string(trim($data->sheets[0]['cells'][$excei][$tuo_sarake]));
					$sal = (float) str_replace(",", ".", trim($data->sheets[0]['cells'][$excei][$teh_sarake]));

					if ($tuo != '' and $sal != '') {
						$tuote[] = $tuo;
						$saldo[] = $sal;
					}
				}
			}
			else {
				if ($file = fopen($_FILES['userfile']['tmp_name'],"r")) {
					while ($rivi = fgets($file)) {

						// luetaan rivi tiedostosta..
						if (strtolower($path_parts['extension']) == 'txt') {
							$rivi = explode("\t", trim($rivi));
						}
						else {
							$rivi = explode(",", trim($rivi));
						}

						$tuo = mysql_real_escape_string(trim($rivi[$tuo_sarake]));
						$sal = (float) str_replace(",", ".", trim($rivi[$teh_sarake]));

						if ($tuo != '' and $sal != '') {
							$tuote[] = $tuo;
							$saldo[] = $sal;
						}
					}

					fclose($file);
				}
				else {
					echo "<font class='error'>",t("Tiedoston avaus epäonnistui"),"!</font><br/>";
					$tee = '';
					$error++;
				}
			}

			if ($error == 0 and count($tuote) > 0) {
				$tee = 'OK';
			}
			else {
				echo "<font class='error'>",t("Tiedostosta ei luettu yhtään tuotetta"),"!</font><br/>";
				$tee = '';
				$error++;
			}

			if ($error == 0 and $tee == 'OK') {

				$tuotteen_toimittaja = mysql_real_escape_string($tuotteen_toimittaja);
				$tuotenumeron_sijainti_pupessa = mysql_real_escape_string($tuotenumeron_sijainti_pupessa);

				if ($toiminto == 'paivita_ja_poista') {
					$query = "	UPDATE tuotteen_toimittajat SET
								tehdas_saldo = '0'
								WHERE yhtio = '$kukarow[yhtio]'
								AND toimittaja = '$tuotteen_toimittaja'";
					$update_saldo_result = mysql_query($query) or pupe_error($query);
				}

				foreach ($tuote as $index => $tuoteno) {

					$query = "	UPDATE tuotteen_toimittajat SET
								tehdas_saldo = '$saldo[$index]'
								WHERE yhtio = '$kukarow[yhtio]'
								AND toimittaja = '$tuotteen_toimittaja'
								AND $tuotenumeron_sijainti_pupessa = '$tuoteno'";
					$update_saldo_result = mysql_query($query) or pupe_error($query);
				}

				echo "<font class='message'>",t("Päivitettiin")," ",count($tuote)," ",t("tuotteen tehdas saldot"),".</font><br/>";

				$tee = '';
			}
		}
	}

	if ($tee == '') {

		echo "<table>";
		echo "<form method='post' autocomplete='off' enctype='multipart/form-data'>";
		echo "<input type='hidden' name='tee' id='tee' value='GO'>";

		echo "<tr><th colspan='2'>",t("Toimittajan valinnat"),"</th></tr>";

		$query = "	SELECT DISTINCT tuotteen_toimittajat.toimittaja, toimi.nimi, toimi.nimitark
					FROM tuotteen_toimittajat
					JOIN toimi ON (toimi.yhtio = tuotteen_toimittajat.yhtio AND toimi.ytunnus = tuotteen_toimittajat.toimittaja)
					WHERE tuotteen_toimittajat.yhtio = '$kukarow[yhtio]'
					AND tuotteen_toimittajat.toimittaja != 'FI'";
		$tuotteen_toimittajat_result = mysql_query($query) or pupe_error($query);

		echo "<tr><td>",t("Tuotteen toimittaja"),"</td>";
		echo "<td><select name='tuotteen_toimittaja'><option value=''>",t("Valitse toimittaja"),"</option>";

		while ($tuotteen_toimittajat_row = mysql_fetch_assoc($tuotteen_toimittajat_result)) {

			$sel = $tuotteen_toimittaja == $tuotteen_toimittajat_row['toimittaja'] ? ' SELECTED' : '';

			echo "<option value='$tuotteen_toimittajat_row[toimittaja]'$sel>$tuotteen_toimittajat_row[nimi] $tuotteen_toimittajat_row[nimitark]</option>";
		}

		echo "</td></tr>";

		echo "<tr><td>",t("Toiminto"),"</td>";
		echo "<td><select name='toiminto'>";
		echo "<option value='paivita'>",t("Päivitetään tiedostossa olevat tehtaan saldot, ei poisteta muita tehtaan saldoja"),"</option>";
		echo "<option value='paivita_ja_poista'>",t("Päivitetään tiedostossa olevat tehtaan saldot, poistetaan muut tehtaan saldot"),"</option>";
		echo "</select></td></tr>";

		echo "<tr><td class='back'>&nbsp;</td></tr>";
		echo "<tr><th colspan='2'>",t("Tuotteen valinnat"),"</th></tr>";

		echo "<tr><td>",t("Luettu tuotenumero Pupesoftissa"),"</td>";
		echo "<td><select name='tuotenumeron_sijainti_pupessa'>";

		$sel1 = $sel2 = '';

		if ($tuotenumeron_sijainti_pupessa == 'toim_tuoteno') {
			$sel2 = ' SELECTED';
		}
		else {
			$sel1 = ' SELECTED';
		}

		echo "<option value='tuoteno'$sel1>",t("Tuotteen tuotenumero"),"</option>";
		echo "<option value='toim_tuoteno'$sel2>",t("Toimittajan tuotenumero"),"</option>";
		echo "</select></td></tr>";

		echo "<tr><td>",t("Tuotenumeron sarakenumero"),"</td><td><input type='text' name='tuotenumeron_sarake' value='$tuotenumeron_sarake'></td></tr>";
		echo "<tr><td>",t("Tehtaan saldon sarakenumero"),"</td><td><input type='text' name='tehtaan_saldon_sarake' value='$tehtaan_saldon_sarake'></td></tr>";

		echo "<tr><td class='back'>&nbsp;</td></tr>";
		echo "<tr><th colspan='2'>",t("Raportin valinnat"),"</th></tr>";

		//Haetaan tallennetut kyselyt
		$query = "	SELECT distinct kuka.nimi, kuka.kuka, tallennetut_parametrit.nimitys
					FROM tallennetut_parametrit
					JOIN kuka on (kuka.yhtio = tallennetut_parametrit.yhtio and kuka.kuka = tallennetut_parametrit.kuka)
					WHERE tallennetut_parametrit.yhtio = '$kukarow[yhtio]'
					and tallennetut_parametrit.sovellus = '$_SERVER[SCRIPT_NAME]'
					ORDER BY tallennetut_parametrit.nimitys";
		$sresult = mysql_query($query) or pupe_error($query);

		echo "<tr><td>",t("Valitse raportti"),":</td>";
		echo "<td><select name='kysely' onchange='document.getElementById(\"tee\").value = \"lataavanha\";submit();'>";
		echo "<option value=''>",t("Valitse"),"</option>";

		while ($srow = mysql_fetch_assoc($sresult)) {

			$sel = '';
			if ($kysely == $srow["kuka"]."#".$srow["nimitys"]) {
				$sel = "selected";
			}

			echo "<option value='$srow[kuka]#$srow[nimitys]' $sel>$srow[nimitys] ($srow[nimi])</option>";
		}

		echo "</select>&nbsp;";

		echo "<input type='button' value='",t("Tallenna"),"' onclick='document.getElementById(\"tee\").value = \"tallenna\";submit();'></td></tr>";

		echo "<tr><td>",t("Tallenna uusi raportti"),":</td>";
		echo "<td><input type='text' name='uusirappari' value=''>&nbsp;";
		echo "<input type='submit' id='tallenna_button' value='",t("Tallenna"),"' onclick=\"document.getElementById('tee').value = 'uusiraportti'\"></td>";
		echo "</tr>";

		echo "<tr><td class='back'>&nbsp;</td></tr>";
		echo "<tr><th colspan='2'>",t("Tiedoston syöttö"),"</th></tr>";
		echo "<tr><td>",t("Valitse tiedosto"),":</td><td><input type='hidden' name='MAX_FILE_SIZE' value='50000000'><input name='userfile' type='file'></td></tr>";

		echo "<tr><td class='back' colspan='2'><input type='submit' value='",t("Aja tehdas saldot"),"'></td></tr>";

		echo "</form></table>";
	}

	require ("inc/footer.inc");


?>