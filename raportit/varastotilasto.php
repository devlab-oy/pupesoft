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
		echo "<font class='head'>".t("Varastotilasto")."</font><hr>";

		// käytetään slavea
		$useslave = 1;
		require ("inc/connect.inc");

		if(count($_POST) > 0) {
			
				$lisa = "";

				if (is_array($mul_osasto) and count($mul_osasto) > 0) {
					$sel_osasto = "('".str_replace(array('PUPEKAIKKIMUUT', ','), array('', '\',\''), implode(",", $mul_osasto))."')";
					$lisa .= " and tuote.osasto in $sel_osasto ";
				}

				if (is_array($mul_try) and count($mul_try) > 0) {
					$sel_tuoteryhma = "('".str_replace(array('PUPEKAIKKIMUUT', ','), array('', '\',\''), implode(",", $mul_try))."')";
					$lisa .= " and tuote.try in $sel_tuoteryhma ";
				}
				
				$vvaa = $vva - '1';
				$vvll = $vvl - '1';

				$query = "	SELECT
							tuote.osasto, 
							tuote.try tuoteryhmä,
							tuote.tuoteno, 
							tuote.nimitys, 
							(	SELECT sum(saldo) 
								FROM tuotepaikat 
								WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno) saldo,

							(	SELECT sum(if(tilausrivin_lisatiedot.osto_vai_hyvitys = '' or tilausrivin_lisatiedot.osto_vai_hyvitys is null, varattu, 0)) 
								FROM tilausrivi USE INDEX (yhtio_tyyppi_tuoteno_varattu)
								LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus=tilausrivi.tunnus)
								WHERE tilausrivi.yhtio=tuote.yhtio and tilausrivi.tyyppi = 'L' and tilausrivi.tuoteno=tuote.tuoteno and tilausrivi.varattu <> 0) varattu,

							(	SELECT sum(if(tilausrivin_lisatiedot.osto_vai_hyvitys = 'O' or tilausrivin_lisatiedot.osto_vai_hyvitys = 'H', varattu * -1, 0))
								FROM tilausrivi USE INDEX (yhtio_tyyppi_tuoteno_varattu)
								LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus=tilausrivi.tunnus)
								WHERE tilausrivi.yhtio=tuote.yhtio and tilausrivi.tyyppi = 'L' and tilausrivi.tuoteno=tuote.tuoteno and tilausrivi.varattu < 0) + 
							(	SELECT sum(varattu) 
								FROM tilausrivi USE INDEX (yhtio_tyyppi_tuoteno_varattu) 
								WHERE tilausrivi.yhtio=tuote.yhtio and tilausrivi.tyyppi = 'O' and tilausrivi.tuoteno=tuote.tuoteno and tilausrivi.varattu > 0) tulossa,
							
							(	SELECT sum(if(tilausrivin_lisatiedot.osto_vai_hyvitys = 'O' or tilausrivin_lisatiedot.osto_vai_hyvitys = 'H', kpl * -1, 0))
								FROM tilausrivi USE INDEX (yhtio_tyyppi_tuoteno_laskutettuaika)
								LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus=tilausrivi.tunnus)
								WHERE tilausrivi.yhtio=tuote.yhtio and tilausrivi.tyyppi = 'L' and tilausrivi.tuoteno=tuote.tuoteno and tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'  and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl' and tilausrivi.kpl < 0) + 
							(	SELECT sum(kpl) 
								FROM tilausrivi USE INDEX (yhtio_tyyppi_tuoteno_laskutettuaika) 
								WHERE tilausrivi.yhtio=tuote.yhtio and tilausrivi.tyyppi = 'O' and tilausrivi.tuoteno=tuote.tuoteno and tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'  and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl') ostot,
							
							(	SELECT sum(if(tilausrivin_lisatiedot.osto_vai_hyvitys = '' or tilausrivin_lisatiedot.osto_vai_hyvitys is null, kpl, 0)) 
								FROM tilausrivi USE INDEX (yhtio_tyyppi_tuoteno_laskutettuaika)
								LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus=tilausrivi.tunnus)
								WHERE tilausrivi.yhtio=tuote.yhtio and tilausrivi.tyyppi = 'L' and tilausrivi.tuoteno=tuote.tuoteno and tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'  and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl') myynti,
							
							(	SELECT sum(if(tilausrivin_lisatiedot.osto_vai_hyvitys = '' or tilausrivin_lisatiedot.osto_vai_hyvitys is null, kpl, 0)) 
								FROM tilausrivi USE INDEX (yhtio_tyyppi_tuoteno_laskutettuaika)
								LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus=tilausrivi.tunnus)
								WHERE tilausrivi.yhtio=tuote.yhtio and tilausrivi.tyyppi = 'L' and tilausrivi.tuoteno=tuote.tuoteno and tilausrivi.laskutettuaika >= '$vvaa-$kka-$ppa'  and tilausrivi.laskutettuaika <= '$vvll-$kkl-$ppl') myynti_ed	
							FROM tuote
							WHERE tuote.yhtio = '$kukarow[yhtio]'
							$lisa
							ORDER BY 1,2,3,4";
				$result = mysql_query($query) or pupe_error($query);

				$rivilimitti = 1000;
				
				if (mysql_num_rows($result) > $rivilimitti) {
					echo "<br><font class='error'>".t("Hakutulos oli liian suuri")."!</font><br>";
					echo "<font class='error'>".t("Tallenna/avaa tulos excelissä")."!</font><br><br>";
				}

				if (mysql_num_rows($result) > 0) {
					if(@include('Spreadsheet/Excel/Writer.php')) {

						//keksitään failille joku varmasti uniikki nimi:
						list($usec, $sec) = explode(' ', microtime());
						mt_srand((float) $sec + ((float) $usec * 100000));
						$excelnimi = md5(uniqid(mt_rand(), true)).".xls";

						$workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
						$workbook->setVersion(8);
						$worksheet =& $workbook->addWorksheet('Sheet 1');

						$format_bold =& $workbook->addFormat();
						$format_bold->setBold();

						$excelrivi = 0;
					}
					
					echo "<table>";
					echo "<tr>
						<th>".t("Kausi nyt")."</th>
						<td>$ppa</td>
						<td>$kka</td>
						<td>$vva</td>
						<th>-</th>
						<td>$ppl</td>
						<td>$kkl</td>
						<td>$vvl</td>
						</tr>\n";
					echo "<tr>
						<th>".t("Kausi ed")."</th>
						<td>$ppa</td>
						<td>$kka</td>
						<td>$vvaa</td>
						<th>-</th>
						<td>$ppl</td>
						<td>$kkl</td>
						<td>$vvll</td>
						</tr>\n";
					echo "</table><br>";

					if (mysql_num_rows($result) <= $rivilimitti) echo "<table><tr>";

					// echotaan kenttien nimet
					for ($i=0; $i < mysql_num_fields($result); $i++) {
						if (mysql_num_rows($result) <= $rivilimitti) echo "<th>".t(mysql_field_name($result,$i))."</th>";
					}

					if(isset($workbook)) {
						for ($i=0; $i < mysql_num_fields($result); $i++) $worksheet->write($excelrivi, $i, ucfirst(t(mysql_field_name($result,$i))), $format_bold);
						$excelrivi++;
					}

					if (mysql_num_rows($result) <= $rivilimitti) echo "</tr>\n";

					if (mysql_num_rows($result) > $rivilimitti) {
						
						require_once ('inc/ProgressBar.class.php');
						$bar = new ProgressBar();
						$elements = mysql_num_rows($result); // total number of elements to process
						$bar->initialize($elements); // print the empty bar
					}

					while ($row = mysql_fetch_array($result)) {

						if (mysql_num_rows($result) > $rivilimitti) $bar->increase();

						if (mysql_num_rows($result) <= $rivilimitti) echo "<tr>";

						// echotaan kenttien sisältö
						for ($i=0; $i < mysql_num_fields($result); $i++) {
							
							// jos kyseessa on tuote
							if (mysql_field_name($result, $i) == "tuoteno") {
								$row[$i] = "<a href='../tuote.php?tee=Z&tuoteno=$row[$i]'>$row[$i]</a>";
							}							
							

							// jos kyseessa on tuoteosasto, haetaan sen nimi
							if (mysql_field_name($result, $i) == "osasto") {
								$query = "	SELECT avainsana.selite, ".avain('select')."
											FROM avainsana
											".avain('join','OSASTO_')."
 											WHERE avainsana.yhtio = '$kukarow[yhtio]' and avainsana.laji='OSASTO' and avainsana.selite='$row[$i]'
											limit 1";
								$osre = mysql_query($query) or pupe_error($query);
								if (mysql_num_rows($osre) == 1) {
									$osrow = mysql_fetch_array($osre);
									$row[$i] = $row[$i] ." ". $osrow['selitetark'];
								}
							}

							// jos kyseessa on tuoteryhmä, haetaan sen nimi
							if (mysql_field_name($result, $i) == "tuoteryhmä") {
								$query = "	SELECT avainsana.selite, ".avain('select')."
											FROM avainsana
											".avain('join','TRY_')."
											WHERE avainsana.yhtio = '$kukarow[yhtio]' and avainsana.laji='TRY' and avainsana.selite='$row[$i]'
											limit 1";
								$osre = mysql_query($query) or pupe_error($query);
								if (mysql_num_rows($osre) == 1) {
									$osrow = mysql_fetch_array($osre);
									$row[$i] = $row[$i] ." ". $osrow['selitetark'];
								}
							}

							// hoidetaan pisteet piluiksi!!
							if (is_numeric($row[$i]) and $row[$i] == 0) {
								if (mysql_num_rows($result) <= $rivilimitti) echo "<td></td>";

								if(isset($workbook)) {
									$worksheet->writeString($excelrivi, $i, "");
								}
							}
							elseif (is_numeric($row[$i]) and (mysql_field_type($result,$i) == 'real' or mysql_field_type($result,$i) == 'int')) {
								if (mysql_num_rows($result) <= $rivilimitti) echo "<td valign='top' align='right'>".sprintf("%.02f",$row[$i])."</td>";

								if(isset($workbook)) {
									$worksheet->writeNumber($excelrivi, $i, sprintf("%.02f",$row[$i]));
								}
							}
							else {
								if (mysql_num_rows($result) <= $rivilimitti) echo "<td valign='top'>$row[$i]</td>";

								if(isset($workbook)) {
									$worksheet->writeString($excelrivi, $i, strip_tags(str_replace("<br>", " / ", $row[$i])));
								}
							}
						}

						if (mysql_num_rows($result) <= $rivilimitti) echo "</tr>\n";
						$excelrivi++;

					}

					if (mysql_num_rows($result) <= $rivilimitti) echo "</table>";

					echo "<br>";

					if(isset($workbook)) {
						// We need to explicitly close the workbook
						$workbook->close();

						echo "<table>";
						echo "<tr><th>".t("Tallenna tulos").":</th>";
						echo "<form method='post' action='$PHP_SELF'>";
						echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
						echo "<input type='hidden' name='kaunisnimi' value='Varastotilasto.xls'>";
						echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
						echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
						echo "</table><br>";
					}
				}
				echo "<br><br><hr>";
			
		}

		if ($lopetus == "") {
			//Käyttöliittymä
			if (!isset($kka)) $kka = date("m",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
			if (!isset($vva)) $vva = date("Y",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
			if (!isset($ppa)) $ppa = date("d",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
			if (!isset($kkl)) $kkl = date("m");
			if (!isset($vvl)) $vvl = date("Y");
			if (!isset($ppl)) $ppl = date("d");

			echo "<br>\n\n\n";
			echo "<form method='post' action='$PHP_SELF'>";
			echo "<input type='hidden' name='tee' value='go'>";

			echo "<table><tr>";

			echo "<th>".t("Valitse tuoteosastot").":</th>";
			echo "<th>".t("Valitse tuoteryhmät").":</th></tr>";

			echo "<tr>";
			echo "<td valign='top'>";

			// näytetään soveltuvat osastot
			$query = "	SELECT avainsana.selite, ".avain('select')."
						FROM avainsana ".avain('join','OSASTO_')."
						WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='OSASTO'
						order by avainsana.jarjestys, avainsana.selite";
			$res2  = mysql_query($query) or die($query);

			echo "<select name='mul_osasto[]' multiple='TRUE' size='10' style='width:100%;'>";

			$mul_check = '';
			if ($mul_osasto!="") {
				if (in_array("PUPEKAIKKIMUUT", $mul_osasto)) {
					$mul_check = 'SELECTED';
				}
			}
			echo "<option value='PUPEKAIKKIMUUT' $mul_check>".t("Ei tuoteosastoa")."</option>";

			while ($rivi = mysql_fetch_array($res2)) {
				$mul_check = '';
				if ($mul_osasto!="") {
					if (in_array($rivi['selite'],$mul_osasto)) {
						$mul_check = 'SELECTED';
					}
				}

				echo "<option value='$rivi[selite]' $mul_check>$rivi[selite] - $rivi[selitetark]</option>";
			}

			echo "</select>";

			echo "</td>";
			echo "<td valign='top' class='back'>";

			// näytetään soveltuvat tryt
			$query = "	SELECT avainsana.selite, ".avain('select')."
						FROM avainsana ".avain('join','TRY_')."
						WHERE avainsana.yhtio='$kukarow[yhtio]'
						and avainsana.laji='TRY'
						order by avainsana.jarjestys, avainsana.selite";
			$res2  = mysql_query($query) or die($query);

			echo "<select name='mul_try[]' multiple='TRUE' size='10' style='width:100%;'>";

			$mul_check = '';
			if ($mul_try!="") {
				if (in_array("PUPEKAIKKIMUUT", $mul_try)) {
					$mul_check = 'SELECTED';
				}
			}
			echo "<option value='PUPEKAIKKIMUUT' $mul_check>".t("Ei tuoterymää")."</option>";

			while ($rivi = mysql_fetch_array($res2)) {
				$mul_check = '';
				if ($mul_try!="") {
					if (in_array($rivi['selite'],$mul_try)) {
						$mul_check = 'SELECTED';
					}
				}

				echo "<option value='$rivi[selite]' $mul_check>$rivi[selite] - $rivi[selitetark]</option>";
			}

			echo "</select>";
			echo "</td>";
			echo "</tr>";
			echo "</table><br>\n";
			
			// päivämäärärajaus
			echo "<table>";
			echo "<tr>
				<th>".t("Syötä alkupäivämäärä (pp-kk-vvvv)")."</th>
				<td><input type='text' name='ppa' value='$ppa' size='3'></td>
				<td><input type='text' name='kka' value='$kka' size='3'></td>
				<td><input type='text' name='vva' value='$vva' size='5'></td>
				</tr>\n
				<tr><th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>
				<td><input type='text' name='ppl' value='$ppl' size='3'></td>
				<td><input type='text' name='kkl' value='$kkl' size='3'></td>
				<td><input type='text' name='vvl' value='$vvl' size='5'></td>
				</tr>\n";
			echo "</table><br>";
			
			echo "<br>";
			echo "<input type='submit' value='".t("Aja raportti")."'>";
			echo "</form>";
		}

		require ("../inc/footer.inc");
	}
?>
