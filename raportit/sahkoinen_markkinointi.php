<?php

	if (isset($_POST["tee"])) {
		if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
		if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
	}

	require ("../inc/parametrit.inc");

	if (isset($tee) and $tee == "lataa_tiedosto") {
		readfile("/tmp/".$tmpfilenimi);
		exit;
	}
	else {

		echo " <SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">
				<!--

				function nimi(e) {
					document.getElementById('tmpfilenimi').value = document.getElementById('tmp').value+'.'+e;

					if (document.getElementById('kaunisnimi').value == '') {
						document.getElementById('kaunisnimi').value = document.getElementById('tmp').value+'.'+e;
					}
					else {
						document.getElementById('kaunisnimi').value = document.getElementById('tiedostonimi').value+'.'+e;
					}

					document.getElementById('ext').value = e;
				}

				function nimi2(e) {
					document.getElementById('kaunisnimi').value = e+'.'+document.getElementById('ext').value;
				}

				//-->
				</script>";

		echo "<font class='head'>",t("Sähköinen markkinointi"),"</font><hr>";
	
		echo "<table>";
		echo "<form action='' method='post'>";

		echo "<tr><th colspan='2'>",t("Henkilötiedot"),"</th><th colspan='2'>",t("Ajoneuvontiedot"),"</th></tr>";
		echo "<tr><td>",t("Ytunnus"),"</td><td><input type='text' name='ytunnus' id='ytunnus' value='$ytunnus'></td><td>",t("Yhteensopivuustunnus"),"</td><td><input type='text' name='yhtsoptun' id='yhtsoptun' value='$yhtsoptun'></td></tr>";
		echo "<tr><td>",t("Nimi"),"</td><td><input type='text' name='nimi' id='nimi' value='$nimi'></td>";
		echo "<td>",t("Ajoneuvontyyppi"),"</td><td><select name='tyyppi' id='tyyppi'><option value=''>",t("Ei valintaa"),"</option>";
		$sel_x = '';

		if ($tyyppi == 'ei_ajoneuvoa') {
			$sel_x = ' selected';
		}

		echo "<option value='ei_ajoneuvoa'$sel_x>",t("Ei ajoneuvoa"),"</option>";

		$query = "	SELECT DISTINCT tyyppi
					FROM yhteensopivuus_mp
					WHERE yhtio = '{$kukarow['yhtio']}'";
		$tyyppi_res = mysql_query($query) or pupe_error($query);

		while ($tyyppi_row = mysql_fetch_assoc($tyyppi_res)) {
			$sel = '';

			if ($tyyppi == $tyyppi_row['tyyppi']) {
				$sel = ' selected';
			}

			echo "<option value='{$tyyppi_row['tyyppi']}'$sel>{$tyyppi_row['tyyppi']}</option>";
		}

		echo "</select></td></tr>";
		echo "<tr><td>",t("Postitoimipaikka"),"</td><td><input type='text' name='postitp' id='postitp' value='$postitp'></td><td>",t("Merkki"),"</td><td><input type='text' name='merkki' id='merkki' value='$merkki'></td></tr>";
		echo "<tr><td>",t("Postitoimialue"),"</td><td><input type='text' name='postino1' id='postino1' value='$postino1' size='6' maxlength='5'>-<input type='text' name='postino2' id='postino2' value='$postino2' size='6' maxlength='5'></td><td>",t("Malli"),"</td><td><input type='text' name='malli' id='malli' value='$malli'></td></tr>";
		echo "<tr><td>",t("Kieli"),"</td><td><select name='kieli'><option value=''>",t("Ei valintaa"),"</option>";

		$query = "	SELECT DISTINCT kieli
					FROM asiakas
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND laji = 'R'
					AND kieli != ''";
		$kieli_res = mysql_query($query) or pupe_error($query);
	
		while ($kieli_row = mysql_fetch_assoc($kieli_res)) {
			$sel = '';

			if ($kieli == $kieli_row['kieli']) {
				$sel = ' selected';
			}

			echo "<option value='{$kieli_row['kieli']}'$sel>{$kieli_row['kieli']}</option>";
		}

		echo "</select></td><td>",t("Kuutiotilavuus"),"</td><td><input type='text' name='cc' id='cc' value='$cc'></td></tr>";
		echo "<tr><td>",t("Sähköpostiosoite"),"</td><td><input type='text' name='email' id='email' value='$email'></td><td>",t("Vuosimalli"),"</td><td><input type='text' name='vm1' id='vm1' value='$vm1' size='6' maxlength='5'>-<input type='text' name='vm2' id='vm2' value='$vm2' size='6' maxlength='5'></td></tr>";

		echo "<tr><th colspan='4'>",t("Attribuutit"),"</th></tr>";

		$query = "	SELECT count(selite) laskuri, selitetark, selite, jarjestys
					FROM avainsana
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND kieli = 'fi'
					AND laji = 'ASAVAINSANA'
					AND selitetark_3 = 'mursu'
					GROUP BY selite	
					ORDER BY jarjestys, selite";
		$dynamic_res = mysql_query($query) or pupe_error($query);

		$riv = 0;

		while ($dynamic_row = mysql_fetch_assoc($dynamic_res)) {

			if ($riv == 0) {
				echo "<tr>";
			}

			echo "<input type='hidden' name='dyn[]' value='{$dynamic_row['selite']}'>";

			echo "<td>{$dynamic_row['selitetark']}</td>";

			if ($dynamic_row['laskuri'] > 1) {
				$query = "	SELECT selitetark_2
							FROM avainsana
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND kieli = 'fi'
							AND laji = 'ASAVAINSANA'
							AND selite = '{$dynamic_row[selite]}'
							ORDER BY selitetark_2";
				$filler_res = mysql_query($query) or pupe_error($query);

				echo "<td><select name='{$dynamic_row['selite']}'><option value=''>",t("Ei valintaa"),"</option>";

				$var = $dynamic_row['selite'];

				while ($filler_row = mysql_fetch_assoc($filler_res)) {
					$sel = '';

					if (in_array($var, $dyn)) {
						if ($$var != '' and $$var == $filler_row['selitetark_2']) {
							$sel = ' selected';
						}
					}

					echo "<option value='{$filler_row['selitetark_2']}'$sel>{$filler_row['selitetark_2']}</option>";

				}
				echo "</select></td>";
			}
			else {
				$query = "	SELECT DISTINCT avainsana
							FROM asiakkaan_avainsanat
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND laji = '{$dynamic_row['selite']}'
							AND avainsana != ''
							ORDER BY avainsana";
				$avain_res = mysql_query($query) or pupe_error($query);

				if (strtolower($dynamic_row['selite']) == 'syntymavuosi') {
					echo "<td><input type='text' name='ika1' id='ika1' value='$ika1' size='4' maxlength='3'>-<input type='text' name='ika2' id='ika2' value='$ika2' size='4' maxlength='3'></td>";
				}
				else {

					echo "<td><select name='{$dynamic_row['selite']}'><option value=''>",t("Ei valintaa"),"</option>";

					$var = $dynamic_row['selite'];

					while ($avain_row = mysql_fetch_assoc($avain_res)) {
						$sel = '';

						if (in_array($var, $dyn)) {
							if ($$var != '' and $$var == $avain_row['avainsana']) {
								$sel = ' selected';
							}
						}

						echo "<option value='{$avain_row['avainsana']}'$sel>{$avain_row['avainsana']}</option>";
					}

					echo "</select></td>";
				}
			}

			$riv++;

			if ($riv == 2) {
				echo "</tr>";
				$riv = 0;
			}
		}

		echo "<td class='back'><input type='submit' name='etsi' value='Etsi'></td></tr>";

		echo "</form>";

		if ($etsi != '') {

			$query = "	SELECT *
						FROM asiakas
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND laji = 'R'";

			if ($ytunnus != '') {
				$ytunnus = mysql_real_escape_string($ytunnus);

				$query .= " AND ytunnus = '$ytunnus' ";
			}

			if ($nimi != '') {
				$nimi = mysql_real_escape_string($nimi);

				$query .= " AND nimi like '%$nimi%' ";
			}

			if ($postitp != '') {
				$postitp = mysql_real_escape_string($postitp);

				$query .= " AND postitp = '$postitp' ";
			}

			if ($postino1 != '') {
				$postino1 = (int) $postino1;

				if (strlen($postino1) < 5) {
					$postino1 = str_pad($postino1, 5, "0", STR_PAD_LEFT);
				}

				$query .= " AND postino >= '$postino1' ";
			}

			if ($postino2 != '') {
				$postino2 = (int) $postino2;

				if (strlen($postino2) < 5) {
					$postino2 = str_pad($postino2, 5, "0", STR_PAD_LEFT);
				}

				$query .= " AND postino <= '$postino2' ";
			}

			if ($kieli != '') {
				$kieli = mysql_real_escape_string($kieli);

				$query .= " AND kieli = '$kieli' ";
			}

			if ($email != '') {
				$email = mysql_real_escape_string($email);

				$query .= " AND email = '$email' ";
			}

			if (trim($yhtsoptun) != '') {
				$yhtsoptun = trim($yhtsoptun);
				if (preg_match_all('/([0-9]+)/', $yhtsoptun, $match)) {
					$yhtsoptun = '';
					foreach ($match[0] as $key => $val) {
						$yhtsoptun .= "$val,";
					}
					$yhtsoptun = substr($yhtsoptun, 0, -1);
				}
				else {
					echo "<font class='error'>",t("Syötit virheellisen yhteensopivuustunnuksen"),"</font>";
					exit;
				}

				$yht_as_query = "	SELECT DISTINCT liitostunnus
									FROM asiakkaan_avainsanat
									WHERE yhtio = '{$kukarow['yhtio']}'
									AND laji = 'yhteensopivuus'
									AND avainsana in ($yhtsoptun)
									AND liitostunnus != ''";
				$yht_as_res = mysql_query($yht_as_query) or pupe_error($yht_as_query);

				if (mysql_num_rows($yht_as_res) > 0) {
					$query .= " AND tunnus IN (";

					while ($yht_as_row = mysql_fetch_array($yht_as_res)) {
						if ($yht_as_row['liitostunnus'] != '') {
							$query .= "$yht_as_row[liitostunnus],";
						}
					}

					$query = substr($query, 0, -1);
					$query .= ") ";
				}
				else {
					exit;
				}
			}

			$query .= "	ORDER BY nimi";

			$res = mysql_query($query) or pupe_error($query);

			echo "<form method='post' action=''>";
			echo "<tr><th colspan='4'>",t("Tallennustoiminnot"),"</th></tr>";

			echo "<tr><td>",t("Tallenna tulostetut"),":</td><td colspan='3'>";
			echo "<input type='hidden' name='ext' id='ext' value=''>";
			echo "CSV <input type='radio' name='paate' id='paate' value='csv' onclick='nimi(this.value);'> ";
			echo "Excel <input type='radio' name='paate' id='paate' value='xls' onclick='nimi(this.value);'></td>";
			echo "</tr>";

			echo "<tr><td>",t("Tallenna tiedosto nimellä (ilman päätettä)"),"</td>";
			echo "<td colspan='3'><input type='text' name='tiedostonimi' id='tiedostonimi' value='$tiedostonimi' onkeyup='nimi2(this.value);'>";
			echo "&nbsp;<font class='info'>(",t("Tyhjä nimi on muotoa postituslista-pvmkellonaika.pääte"),")</font></td>";

			echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
			echo "<input type='hidden' name='kaunisnimi' id='kaunisnimi' value=''>";
			echo "<input type='hidden' name='tmpfilenimi' id='tmpfilenimi' value=''";
			echo "<input type='hidden' name='tmp' id='tmp' value='Postituslista-".date("dmYHis")."'";

			echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td>";
			echo "</tr>";

			echo "<tr><th>",t("Ytunnus"),"</th><th>",t("Nimi"),"</th><th>",t("Sähköpostiosoite"),"</th><th>",t("Postitp Postino"),"</th><th>",t("Yhteensopivuustunnus"),"</th><th>",t("Tyyppi"),"</th><th>",t("Merkki"),"</th><th>",t("Malli"),"</th><th>",t("Cc"),"</th><th>",t("Vm"),"</th></tr>";

			$tmptiedostonimi = "Postituslista-".date("dmYHis").".xls";

			if(@include('Spreadsheet/Excel/Writer.php')) {

				$workbook = new Spreadsheet_Excel_Writer('/tmp/'.$tmptiedostonimi);
				$workbook->setVersion(8);
				$worksheet =& $workbook->addWorksheet('Sheet 1');

				$format_bold =& $workbook->addFormat();
				$format_bold->setBold();

				$excelrivi = 0;
			}

			// csv-tiedostoa varten
			$rivi = "";

			if (isset($workbook)) {
				$excelsarake = 0;
			
				$worksheet->writeString($excelrivi, $excelsarake, t("Ytunnus"),	$format_bold);
				$excelsarake++;
				$worksheet->writeString($excelrivi, $excelsarake, t("Nimi"), $format_bold);
				$excelsarake++;
				$worksheet->writeString($excelrivi, $excelsarake, t("Sähköpostiosoite"), $format_bold);
				$excelsarake++;
				$worksheet->writeString($excelrivi, $excelsarake, t("Postitp"), $format_bold);
				$excelsarake++;
				$worksheet->writeString($excelrivi, $excelsarake, t("Postino"), $format_bold);
				$excelsarake++;
				$worksheet->writeString($excelrivi, $excelsarake, t("Yhteensopivuustunnus"), $format_bold);
				$excelsarake++;
				$worksheet->writeString($excelrivi, $excelsarake, t("Tyyppi"), $format_bold);
				$excelsarake++;
				$worksheet->writeString($excelrivi, $excelsarake, t("Merkki"), $format_bold);
				$excelsarake++;
				$worksheet->writeString($excelrivi, $excelsarake, t("Malli"), $format_bold);
				$excelsarake++;
				$worksheet->writeString($excelrivi, $excelsarake, t("Cc"), $format_bold);
				$excelsarake++;
				$worksheet->writeString($excelrivi, $excelsarake, t("Vm"), $format_bold);
				$excelsarake++;

				$query = "	SELECT DISTINCT selitetark, jarjestys
							FROM avainsana
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND kieli = 'fi'
							AND laji = 'ASAVAINSANA'
							AND selitetark_3 = 'mursu'
							ORDER BY jarjestys";
				$otsikot_res = mysql_query($query) or pupe_error($query);

				while ($otsikot_row = mysql_fetch_assoc($otsikot_res)) {

					if (strtolower($otsikot_row['selitetark']) == 'ikä') {
						$worksheet->writeString($excelrivi, $excelsarake, t("Syntymävuosi"), $format_bold);
						$excelsarake++;
					}
					else {
						$worksheet->writeString($excelrivi, $excelsarake, t($otsikot_row['selitetark']), $format_bold);
						$excelsarake++;
					}
				}

				$excelrivi++;
				$excelsarake = 0;
			}

			$rows = 0;

			while ($row = mysql_fetch_assoc($res)) {

				$chk = '';

				$query = "	SELECT *
							FROM asiakkaan_avainsanat
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND liitostunnus = '{$row['tunnus']}'
							AND laji = 'yhteensopivuus'";
				$avain_res = mysql_query($query) or pupe_error($query);
				$avain_row = mysql_fetch_assoc($avain_res);

				if ($tyyppi != '' and $tyyppi != 'ei_ajoneuvoa' and $avain_row['avainsana'] == '') {
					continue;
				}

				if ($tyyppi == 'ei_ajoneuvoa' and $avain_row['avainsana'] != '') {
					continue;
				}

				if ($tyyppi != 'ei_ajoneuvoa') {

					$query = "	SELECT *
								FROM yhteensopivuus_mp
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tunnus = '{$avain_row['avainsana']}' ";

					if ($tyyppi != '') {
						$tyyppi = mysql_real_escape_string($tyyppi);

						$query .= "	AND tyyppi = '$tyyppi' ";
						$chk = 'x';
					}

					if ($merkki != '') {
						$merkki = mysql_real_escape_string($merkki);

						$query .= "	AND merkki = '$merkki' ";
						$chk = 'x';
					}

					if ($malli != '') {
						$malli = mysql_real_escape_string($malli);

						$query .= "	AND malli like '%$malli%' ";
						$chk = 'x';
					}

					if ($cc != '') {
						$cc = (int) $cc;

						$query .= "	AND cc = '$cc' ";
						$chk = 'x';
					}

					if ($vm1 != '') {
						$vm1 = (int) $vm1;

						$query .= "	AND vm >= '$vm1' ";
					}

					if ($vm2 != '') {
						$vm2 = (int) $vm2;

						$query .= "	AND vm <= '$vm2' ";
					}

					$yht_res = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($yht_res) == 0 and $chk == 'x') {
						continue;
					}

					if (mysql_num_rows($yht_res) == 0 and $tyyppi != '') {
						continue;
					}

					$yht_row = mysql_fetch_assoc($yht_res);
				}

				if (isset($dyn) and count($dyn) > 0) {
					foreach($dyn as $muuttuja) {
						if ($$muuttuja != '') {
							$$muuttuja = mysql_real_escape_string($$muuttuja);

							$query = "	SELECT *
										FROM asiakkaan_avainsanat
										WHERE yhtio = '{$kukarow['yhtio']}'
										AND liitostunnus = '{$row['tunnus']}'
										AND laji = '$muuttuja'
										AND avainsana = '${$muuttuja}'";
							$mainonta_res = mysql_query($query) or pupe_error($query);

							if (mysql_num_rows($mainonta_res) == 0) {
								continue 2;
							}
						}
					}
				}

				if ($ika1 != '') {
					$ika1 = mysql_real_escape_string($ika1);
				
					$query = "	SELECT *
								FROM asiakkaan_avainsanat
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND liitostunnus = '{$row['tunnus']}'
								AND laji = 'syntymavuosi'";
					$syntymavuosi_res = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($syntymavuosi_res) == 0) {
						continue;
					}

					$syntymavuosi_row = mysql_fetch_assoc($syntymavuosi_res);

					$vv = date("Y") - $syntymavuosi_row['avainsana'];

					if ($vv < $ika1) {
						continue;
					}
				}

				if ($ika2 != '') {
					$ika2 = mysql_real_escape_string($ika2);
				
					$query = "	SELECT *
								FROM asiakkaan_avainsanat
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND liitostunnus = '{$row['tunnus']}'
								AND laji = 'syntymavuosi'";
					$syntymavuosi_res = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($syntymavuosi_res) == 0) {
						continue;
					}

					$syntymavuosi_row = mysql_fetch_assoc($syntymavuosi_res);

					$vv = date("Y") - $syntymavuosi_row['avainsana'];

					if ($vv > $ika2) {
						continue;
					}
				}

				echo "<tr class='aktiivi'>";
				echo "<td>{$row['ytunnus']}</td><td>{$row['nimi']}</td><td>{$row['email']}</td><td>{$row['postino']} {$row['postitp']}</td>";
				echo "<td>{$avain_row['avainsana']}</td>";
				echo "<td>{$yht_row['tyyppi']}</td><td>{$yht_row['merkki']}</td><td>{$yht_row['malli']}</td><td>{$yht_row['cc']}</td><td>{$yht_row['vm']}</td>";
				echo "</tr>";

				$rivi .= str_replace(",", ".", $row['nimi']).",";
				$rivi .= str_replace(",", ".", $row['email']).",";
				$rivi .= str_replace(",", ".", $row['postino']).",";
				$rivi .= str_replace(",", ".", $row['postitp']).",";
				$rivi .= str_replace(",", ".", $avain_row['avainsana']).",";
				$rivi .= str_replace(",", ".", $yht_row['tyyppi']).",";
				$rivi .= str_replace(",", ".", $yht_row['merkki']).",";
				$rivi .= str_replace(",", ".", $yht_row['malli']).",";
				$rivi .= str_replace(",", ".", $yht_row['cc']).",";
				$rivi .= str_replace(",", ".", $yht_row['vm']).",";
				$rivi .= "\r\n";

				if (isset($workbook)) {
					$worksheet->writeString($excelrivi, $excelsarake, $row["ytunnus"],	$format_bold);
					$excelsarake++;
					$worksheet->writeString($excelrivi, $excelsarake, $row["nimi"], 	$format_bold);
					$excelsarake++;
					$worksheet->writeString($excelrivi, $excelsarake, $row["email"], 	$format_bold);
					$excelsarake++;
					$worksheet->writeString($excelrivi, $excelsarake, $row["postino"], 	$format_bold);
					$excelsarake++;
					$worksheet->writeString($excelrivi, $excelsarake, $row["postitp"], 	$format_bold);
					$excelsarake++;

					$worksheet->writeString($excelrivi, $excelsarake, $avain_row["avainsana"], 	$format_bold);
					$excelsarake++;

					$worksheet->writeString($excelrivi, $excelsarake, $yht_row["tyyppi"], 	$format_bold);
					$excelsarake++;
					$worksheet->writeString($excelrivi, $excelsarake, $yht_row["merkki"], 	$format_bold);
					$excelsarake++;
					$worksheet->writeString($excelrivi, $excelsarake, $yht_row["malli"], 	$format_bold);
					$excelsarake++;
					$worksheet->writeString($excelrivi, $excelsarake, $yht_row["cc"], 	$format_bold);
					$excelsarake++;
					$worksheet->writeString($excelrivi, $excelsarake, $yht_row["vm"], 	$format_bold);
					$excelsarake++;

					$query = "	SELECT DISTINCT selite, jarjestys
								FROM avainsana
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND kieli = 'fi'
								AND laji = 'ASAVAINSANA'
								AND selitetark_3 = 'mursu'
								ORDER BY jarjestys, selite";
					$otsikot_res = mysql_query($query) or pupe_error($query);

					while ($otsikot_row = mysql_fetch_assoc($otsikot_res)) {

						$query = "	SELECT *
									FROM asiakkaan_avainsanat
									WHERE yhtio = '{$kukarow['yhtio']}'
									AND liitostunnus = '{$row['tunnus']}'
									AND laji = '{$otsikot_row['selite']}'";
						$attr_res = mysql_query($query) or pupe_error($query);

						$attr_row = mysql_fetch_assoc($attr_res);
						$worksheet->writeString($excelrivi, $excelsarake, $attr_row['avainsana'], $format_bold);
						$excelsarake++;
					}

					$excelrivi++;
					$excelsarake = 0;
				}
				$rows++;
			}

			if (isset($workbook)) {
				$workbook->close();
			}

			$info = pathinfo($tmptiedostonimi);

			file_put_contents("/tmp/$info[filename].csv", $rivi);
			echo "<tr><th colspan='10' id='riveja'>",t("Rivejä")," $rows ",t("kappaletta"),"</th></tr>";
		}
		echo "</form>";
		echo "</table>";
	}

?>
