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

		echo "<tr>";
		echo "<th colspan='2'>",t("Asiakastiedot"),"</th>";
		echo "</tr>";
		echo "<tr>";
		echo "<td>",t("Ytunnus"),"</td>";
		echo "<td><input type='text' name='ytunnus' id='ytunnus' value='$ytunnus'></td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td>",t("Nimi"),"</td>";
		echo "<td><input type='text' name='nimi' id='nimi' value='$nimi'></td>";
		echo "<tr>";
		echo "<td>",t("Postitoimipaikka"),"</td><td><input type='text' name='postitp' id='postitp' value='$postitp'></td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td>",t("Postitoimialue"),"</td>";
		echo "<td><input type='text' name='postino1' id='postino1' value='$postino1' size='6' maxlength='5'>-<input type='text' name='postino2' id='postino2' value='$postino2' size='6' maxlength='5'></td>";
		echo "</tr>";
		echo "<tr><td>",t("Kieli"),"</td>";
		echo "<td><select name='kieli'><option value=''>",t("Ei valintaa"),"</option>";

		$query = "	SELECT DISTINCT kieli
					FROM asiakas
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND laji = 'R'
					AND kieli != ''";
		$kieli_res = pupe_query($query);

		while ($kieli_row = mysql_fetch_assoc($kieli_res)) {
			$sel = '';

			if ($kieli == $kieli_row['kieli']) {
				$sel = ' selected';
			}

			echo "<option value='{$kieli_row['kieli']}'$sel>{$kieli_row['kieli']}</option>";
		}

		echo "</select></td>";
		echo "</tr>";
		echo "<tr><td>",t("Sähköpostiosoite"),"</td>";
		echo "<td><input type='text' name='email' id='email' value='$email'></td>";
		echo "</tr>";
		echo "<tr><td colspan='2' class='back'>&nbsp;</td></tr>";
		echo "<tr><th colspan='2'>".t("Myynnintiedot")."</th></tr>";
		echo "<tr>";
		echo "<td>".t("Veroton myynti vähintään")."</td>";
		echo "<td><input type='text' name='myynti' id='myynti' value='$myynti'></td>";

		// laitetaan oletuspäiviä
		if (!isset($pvm1)) 		$pvm1 = '01';
		if (!isset($kk1))		$kk1 = date("m");
		if (!isset($vuosi1))	$vuosi1 = date("Y");

		if (!isset($pvm2))		$pvm2 = date("d");
		if (!isset($kk2))		$kk2 = date("m");
		if (!isset($vuosi2))	$vuosi2 = date("Y");

		echo "<tr>";
		echo "<td>".t("Alkupäivämäärä")."</td><td><input type='text' name='pvm1' value='$pvm1' size='2'> <input type='text' name='kk1' value='$kk1' size='2'> <input type='text' name='vuosi1' value='$vuosi1' size='4'></td>";
		echo "</tr><tr>";
		echo "<td>".t("Loppupäivämäärä")."</td><td><input type='text' name='pvm2' value='$pvm2' size='2'> <input type='text' name='kk2' value='$kk2' size='2'> <input type='text' name='vuosi2' value='$vuosi2' size='4'></td>";
		echo "</tr>";

		echo "<tr><td>".t("Kategoria")."</th><td>";

		$monivalintalaatikot = array("OSASTO", "TRY", "TUOTEMERKKI", "<br>MALLI/MALLITARK");
		$monivalintalaatikot_normaali = array();
		require ("tilauskasittely/monivalintalaatikot.inc");

		echo "</td></tr>";

		echo "</table>";
		echo "<br>";
		echo "<table>";
		echo "<tr><th colspan='4'>",t("Asiakkaan avainsanat"),"</th></tr>";

		$query = "	SELECT count(selite) laskuri, selitetark, selite, jarjestys, min(tunnus) mintunnus
					FROM avainsana
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND kieli = 'fi'
					AND laji = 'ASAVAINSANA'
					GROUP BY selite
					ORDER BY jarjestys, selite";
		$dynamic_res = pupe_query($query);

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
							AND selite = '{$dynamic_row["selite"]}'
							ORDER BY selitetark_2";
				$filler_res = pupe_query($query);

				echo "<td><select name='{$dynamic_row['selite']}' ".js_alasvetoMaxWidth($dynamic_row['mintunnus'], 300)."><option value=''>",t("Ei valintaa"),"</option>";

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
				$avain_res = pupe_query($query);

				if (strtolower($dynamic_row['selite']) == 'syntymavuosi') {
					echo "<td><input type='text' name='ika1' id='ika1' value='$ika1' size='4' maxlength='3'>-<input type='text' name='ika2' id='ika2' value='$ika2' size='4' maxlength='3'></td>";
				}
				else {

					echo "<td><select name='{$dynamic_row['selite']}' ".js_alasvetoMaxWidth($dynamic_row['mintunnus'], 300)."><option value=''>",t("Ei valintaa"),"</option>";

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

		echo "</table><br>";
		echo "<input type='submit' name='etsi' value='Etsi'>";
		echo "</form><br><br>";

		if (isset($etsi)) {

			$query = "	SELECT asiakas.*
						FROM asiakas
						JOIN asiakkaan_avainsanat on (asiakkaan_avainsanat.yhtio = asiakas.yhtio and asiakas.tunnus = asiakkaan_avainsanat.liitostunnus)
						WHERE asiakas.yhtio = '{$kukarow["yhtio"]}' ";

			if (isset($dyn) and count($dyn) > 0) {
				foreach($dyn as $muuttuja) {
					if ($$muuttuja != '') {
						$$muuttuja = mysql_real_escape_string($$muuttuja);

						$av_laji .= "'".$muuttuja."',";
						$av_sana .= "'".${$muuttuja}."',";
					}
				}
				$av_laji = substr($av_laji,0,-1);
				$av_sana = substr($av_sana,0,-1);

				if ($av_laji != "" and $av_sana != "") {
					$query2 .= " AND asiakkaan_avainsanat.laji in ({$av_laji}) and asiakkaan_avainsanat.avainsana in ({$av_sana}) ";
				}
			}

			$query .= $query2;

			if (checkdate($kk1, $pvm1, $vuosi1)) {
				$alkaa = $vuosi1."-".$kk1."-".$pvm1;
			}
			else {
				$alkaa = date("Y-m-d");
			}

			if (checkdate($kk2, $pvm2, $vuosi2)) {
				$loppuu = $vuosi2."-".$kk2."-".$pvm2;
			}
			else {
				$loppuu = date("Y-m-d");
			}

			if ($ytunnus != '') {
				$ytunnus = mysql_real_escape_string($ytunnus);
				$query .= " AND asiakas.ytunnus like '%$ytunnus%' ";
			}

			if ($nimi != '') {
				$nimi = mysql_real_escape_string($nimi);
				$query .= " AND asiakas.nimi like '%$nimi%' ";
			}

			if ($postitp != '') {
				$postitp = mysql_real_escape_string($postitp);
				$query .= " AND (asiakas.postitp = '$postitp' or asiakas.toim_postitp = '$postitp') ";
			}

			if ($postino1 != '') {
				$postino1 = (int) $postino1;

				if (strlen($postino1) < 5) {
					$postino1 = str_pad($postino1, 5, "0", STR_PAD_LEFT);
				}
				$query .= " AND asiakas.postino >= '$postino1' ";
			}

			if ($postino2 != '') {
				$postino2 = (int) $postino2;

				if (strlen($postino2) < 5) {
					$postino2 = str_pad($postino2, 5, "0", STR_PAD_LEFT);
				}
				$query .= " AND asiakas.postino <= '$postino2' ";
			}

			if ($kieli != '') {
				$kieli = mysql_real_escape_string($kieli);
				$query .= " AND asiakas.kieli = '$kieli' ";
			}
			else {
				$query .= " AND asiakas.kieli != '' ";
			}

			if ($email != '') {
				$email = mysql_real_escape_string($email);
				$query .= " AND (asiakas.email = '$email' or asiakas.email like '%$email%') ";
			}

			$query .= "	GROUP BY tunnus order by postitp, nimi";
			$res = pupe_query($query);

			echo "<form method='post' action=''>";
			echo "<table>";
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
			echo "<input type='hidden' name='tmpfilenimi' id='tmpfilenimi' value=''>";
			echo "<input type='hidden' name='tmp' id='tmp' value='Postituslista-".date("dmYHis")."'>";

			echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td>";
			echo "</tr>";

			echo "</table>";
			echo "<br>";
			echo "<table>";

			echo "<tr>";
			echo "<th>",t("Ytunnus"),"</th>";
			echo "<th>",t("Nimi"),"</th>";
			echo "<th>",t("Sähköpostiosoite"),"</th>";
			echo "<th>",t("Postino")." ".t("Postitp"),"</th>";
			echo "<th>",t("Myyntisumma"),"</th>";

			echo "</tr>";

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
				$worksheet->writeString($excelrivi, $excelsarake, t("Myynti aikavälillä"), $format_bold);
				$excelsarake++;


				$query = "	SELECT DISTINCT selitetark, jarjestys
							FROM avainsana
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND kieli = 'fi'
							AND laji = 'ASAVAINSANA'
							ORDER BY jarjestys";
				$otsikot_res = pupe_query($query);

				while ($otsikot_row = mysql_fetch_assoc($otsikot_res)) {
					$worksheet->writeString($excelrivi, $excelsarake, t($otsikot_row['selitetark']), $format_bold);
					$excelsarake++;
				}

				$excelrivi++;
				$excelsarake = 0;
			}

			// Haetaan asikasavainsanat
			$query = "	SELECT DISTINCT selitetark, jarjestys, selite
						FROM avainsana
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND kieli = 'fi'
						AND laji = 'ASAVAINSANA'
						ORDER BY jarjestys";
			$otsikot_res = pupe_query($query);

			$rows = 0;

			while ($row = mysql_fetch_assoc($res)) {

				$myynti = (float) $myynti;
				$mlisa  = "";

				if ($myynti != 0) {
					$mlisa = " HAVING rivin_summa >= {$myynti} ";
				}

				// Subquery. Haetaan riveiltä yhteissummia
				$query = "	SELECT sum(tilausrivi.rivihinta) rivin_summa
							FROM lasku USE INDEX (yhtio_tila_liitostunnus_tapvm)
							JOIN tilausrivi on (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus and tilausrivi.tyyppi != 'D')
							JOIN tuote on (lasku.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno)
							WHERE lasku.yhtio = '{$kukarow["yhtio"]}'
							AND lasku.tila = 'L'
							AND lasku.alatila = 'X'
							and lasku.liitostunnus = '{$row["tunnus"]}'
							AND lasku.tapvm >= '{$alkaa}'
							AND lasku.tapvm <= '{$loppuu}'
							{$lisa}
							{$mlisa}";
				$summress = pupe_query($query);
				$rivit = mysql_fetch_assoc($summress);


				if ((mysql_num_rows($summress) > 0 and $myynti != 0) or $myynti == 0) {

					echo "<tr class='aktiivi'>";
					echo "<td>{$row['ytunnus']}</td>";
					echo "<td>{$row['nimi']}</td>";
					echo "<td>{$row['email']}</td>";
					echo "<td>{$row['postino']} {$row['postitp']}</td>";
					echo "<td align='right'>". round($rivit['rivin_summa'], $yhtiorow["hintapyoristys"])."</td>";

					echo "</tr>";

					$rivi .= str_replace(",", ".", $row['ytunnus']).",";
					$rivi .= str_replace(",", ".", $row['nimi']).",";
					$rivi .= str_replace(",", ".", $row['email']).",";
					$rivi .= str_replace(",", ".", $row['postino']).",";
					$rivi .= str_replace(",", ".", $row['postitp']).",";
					$rivi .= str_replace(",", ".", round($rivit['rivin_summa'], $yhtiorow["hintapyoristys"])).",";
					$rivi .= "\r\n";

					if (isset($workbook)) {

						$worksheet->writeString($excelrivi, $excelsarake, $row["ytunnus"]);
						$excelsarake++;
						$worksheet->writeString($excelrivi, $excelsarake, $row["nimi"]);
						$excelsarake++;
						$worksheet->writeString($excelrivi, $excelsarake, $row["email"]);
						$excelsarake++;
						$worksheet->writeString($excelrivi, $excelsarake, $row["postino"]);
						$excelsarake++;
						$worksheet->writeString($excelrivi, $excelsarake, $row["postitp"]);
						$excelsarake++;
						$worksheet->writeString($excelrivi, $excelsarake, round($rivit['rivin_summa'], $yhtiorow["hintapyoristys"]));
						$excelsarake++;

						mysql_data_seek($otsikot_res, 0);

						while ($otsikot_row = mysql_fetch_assoc($otsikot_res)) {

							$query = "	SELECT *
										FROM asiakkaan_avainsanat
										WHERE yhtio = '{$kukarow['yhtio']}'
										AND liitostunnus = '{$row['tunnus']}'
										AND laji = '{$otsikot_row['selite']}'";
							$attr_res = pupe_query($query);
							$attr_row = mysql_fetch_assoc($attr_res);

							$worksheet->writeString($excelrivi, $excelsarake, $attr_row['avainsana']);
							$excelsarake++;
						}

						$excelrivi++;
						$excelsarake = 0;
					}
					$rows++;
				}
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

		require ("../inc/footer.inc");
	}
?>