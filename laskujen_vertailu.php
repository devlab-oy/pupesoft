<?php

	//* Tämä skripti käyttää slave-tietokantapalvelinta *//
	$useslave = 1;

	if (isset($_REQUEST["tee"]) and $_REQUEST["tee"] == "NAYTATILAUS") {
		$no_head = "yes";

		header("Content-type: application/pdf");
		header("Content-length: ".strlen(urldecode($_REQUEST["pdf"])));
		header("Content-Disposition: inline; filename=Pupesoft_lasku");
		header("Content-Description: Pupesoft_lasku");

		flush();
	}

	require ("inc/parametrit.inc");

	if ($_REQUEST["tee"] == "NAYTATILAUS" and isset($_REQUEST["pdf"])) {
		$pdf = urldecode($_REQUEST["pdf"]);
		echo $pdf;
		exit;
	}

	// ekotetaan javascriptiä jotta saadaan pdf:ät uuteen ikkunaan
	js_openFormInNewWindow();

	js_popup();

	echo "<font class='head'>",t("Laskun ja tilauksen vertailu"),"</font><hr>";

	if (isset($tee) and $tee == "laheta_tiedosto") {
		echo "<font class='message'>",t("Lähetetään sähköposti osoitteeseen")," {$asiakasemail}.</font><br /><br />";
	}

	if (!isset($laskunro)) $laskunro = '';
	if (!isset($hyvaksyja)) $hyvaksyja = '';
	if (!isset($app)) $app = date('d', strtotime('-1 day'));
	if (!isset($akk)) $akk = date('m', strtotime('-1 day'));
	if (!isset($avv)) $avv = date('Y', strtotime('-1 day'));
	if (!isset($lpp)) $lpp = date('d');
	if (!isset($lkk)) $lkk = date('m');
	if (!isset($lvv)) $lvv = date('Y');

	echo "<br><table>";
	echo "<form method='post'>";
	echo "<tr><th>",t("Laskunumero"),"</th><td><input type='text' name='laskunro' value='{$laskunro}' /></td><td class='back'>&nbsp;</td></tr>";

	$query = "	SELECT kuka, nimi
				FROM kuka
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND hyvaksyja != ''
				AND extranet = ''
				ORDER BY nimi";
	$hyvaksyja_result = pupe_query($query);

	echo "<tr><th>",t("Hyväksyjä"),"</th>";
	echo "<td><select name='hyvaksyja' onchange='submit();'><option value=''>",t("Valitse hyväksyjä"),"</option>";

	while ($hyvaksyja_row = mysql_fetch_assoc($hyvaksyja_result)) {

		$sel = $hyvaksyja == $hyvaksyja_row['kuka'] ? ' selected' : '';

		echo "<option value='{$hyvaksyja_row['kuka']}'{$sel}>{$hyvaksyja_row['nimi']}</option>";
	}

	echo "</select></td><td class='back'>&nbsp;</td></tr>";

	// Tehdään kustannuspaikkapopup
	$query = "	SELECT tunnus, nimi, koodi
				FROM kustannuspaikka
				WHERE yhtio = '$kukarow[yhtio]'
				and tyyppi = 'K'
				and kaytossa != 'E'
				ORDER BY koodi+0, koodi, nimi";
	$vresult = pupe_query($query);

	echo "<tr><th>",t("Kustannuspaikka"),"</th>";

	echo "<td valign='top'>";

	if (mysql_num_rows($vresult) > 0) {
		echo "<select name='kustannuspaikka' onchange='submit();'>";
		echo "<option value ='0'>".t("Ei kustannuspaikkaa")."";

		while ($vrow = mysql_fetch_array($vresult)) {
			$sel = "";
			if ($kustannuspaikka == $vrow['tunnus']) {
				$sel = "selected";
			}
			echo "<option value ='$vrow[tunnus]' $sel>$vrow[koodi] $vrow[nimi]</option>";
		}

		echo "</select>";
	}

	echo "</td></tr>";

	$sel1='';
	$sel2='';
	$sel3='';

	if ($eroa == "") {
		$seli1 = 'selected';
	}
	if ($eroa == '1') {
		$seli2 = 'selected';
	}
	if ($eroa == '2') {
		$seli3 = 'selected';
	}

	echo "<tr><th>".t("Eroavaisuuksia")."</th>";
	echo "<td>";
	echo "<select name='eroa' onchange='submit();'>";
	echo "<option value = '' {$seli1}>".t("Ei valintaa")."</option>";
	echo "<option value = '1' {$seli2}>".t("Eroja")."</option>";
	echo "<option value = '2' {$seli3}>".t("Ok")."</option>";
	echo "</select>";
	echo "</td></tr>";

	echo "<tr><th>",t("Alkupäivämäärä"),"</th><td><input type='text' name='app' size='3' maxlength='2' value='{$app}' />&nbsp;<input type='text' name='akk' size='3' maxlength='2' value='{$akk}' />&nbsp;<input type='text' name='avv' size='5' maxlength='4' value='{$avv}' /></td><td class='back'>&nbsp;</td></tr>";
	echo "<tr><th>",t("Loppupäivämäärä"),"</th><td><input type='text' name='lpp' size='3' maxlength='2' value='{$lpp}' />&nbsp;<input type='text' name='lkk' size='3' maxlength='2' value='{$lkk}' />&nbsp;<input type='text' name='lvv' size='5' maxlength='4' value='{$lvv}' /></td>";
	echo "<td class='back'><input type='submit' value='",t("Hae"),"' /></td></tr>";
	echo "</form>";
	echo "</table>";

	if (trim($laskunro) != '' and is_numeric($laskunro)) {

		$query = "	SELECT tapvm, erpcm, concat(nimi, ' ', nimitark) nimi, postitp, ytunnus, summa, valkoodi, laskunro, tunnus, hyvaksyja_nyt, tila
					FROM lasku
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tila in ('H','Y','M','P','Q')
					AND laskunro = '{$laskunro}'
					ORDER BY tunnus DESC";
		$lasku_res = pupe_query($query);
		$lasku_row = mysql_fetch_assoc($lasku_res);

		echo "<br/><br/><table>";
		echo "	<tr>
					<th>",t("Tapvm/Erpvm"),"</th>
					<th>",t("Ytunnus"),"</th>
					<th>",t("Nimi"),"</th>
					<th>",t("Postitp"),"</th>
					<th>",t("Summa"),"</th>
					<th>",t("Laskunro"),"</th>
				</tr>";
		echo "	<tr>
					<td valign='top'>",tv1dateconv($lasku_row["tapvm"]),"<br/>",tv1dateconv($lasku_row["erpcm"]),"</td>
					<td valign='top'>{$lasku_row['ytunnus']}</td>
					<td valign='top'>{$lasku_row['nimi']}</td>
					<td valign='top'>{$lasku_row['postitp']}</td>
					<td valign='top' style='text-align: right;'>{$lasku_row['summa']} {$lasku_row['valkoodi']}</td>
					<td valign='top' style='text-align: right;'>{$lasku_row['laskunro']}</td>";

		// HUOM: Haetaan laskun liitekuva toisen firman kannasta
		if ($kukarow["yhtio"] == 'atarv') {

		    $query = "	SELECT tunnus
		    			FROM lasku
		    			WHERE yhtio  = 'artr'
		    			AND tila     = 'U'
		    			AND laskunro = '{$lasku_row['laskunro']}'";
		    $lres = pupe_query($query);

			if (mysql_num_rows($lres) == 1) {

		    	// Oma yhtiö
			    $alkup_yhtio = $kukarow["yhtio"];

			    // Vieras yhtiö
			    $kukarow["yhtio"] = $koti_yhtio;

			    if (tarkista_oikeus("tulostakopio.php", "lasku")) {
			    	$yhtiorow = hae_yhtion_parametrit($kukarow["yhtio"]);

			    	include_once("tilauskasittely/tulosta_lasku.inc");

			    	$laskupdf = tulosta_lasku("LASKU:".$lasku_row['laskunro'], "", "VERKKOLASKU_APIX", "", "", "", "");

					echo "<td class='back'><form id='form_1' name='form_1' method='post'>
				    	<input type='hidden' name = 'tee' value ='NAYTATILAUS'>
				    	<input type='hidden' name = 'pdf' value ='".urlencode(file_get_contents($laskupdf))."'>
				    	<input type='submit' value = '".t("Näytä Pdf")."' onClick=\"js_openFormInNewWindow('form_1', 'form_1'); return false;\"></form></td>";
				}

			    // Otetaan omat yhtiötiedot takaisin
			    $kukarow["yhtio"] = $alkup_yhtio;
			    $yhtiorow = hae_yhtion_parametrit($kukarow["yhtio"]);
			}
		}

		echo "</tr>";
		echo "</table>";

		echo "<br/><br/><table>";

		list($invoice, $purchaseorder, $invoice_ei_loydy, $purchaseorder_ei_loydy, $loytyy_kummastakin, $purchaseorder_tilausnumero) = laskun_ja_tilauksen_vertailu($kukarow, $lasku_row["tunnus"]);

		if ($invoice !== FALSE and (count($invoice_ei_loydy) > 0 or count($loytyy_kummastakin) > 0)) {

			if (isset($tee) and $tee == "laheta_tiedosto") {

				if (!@include('Spreadsheet/Excel/Writer.php')) {
					echo "<font class='error'>",t("VIRHE: Pupe-asennuksesi ei tue Excel-kirjoitusta."),"</font><br>";
					exit;
				}

				//keksitään failille joku varmasti uniikki nimi:
				list($usec, $sec) = explode(' ', microtime());
				mt_srand((float) $sec + ((float) $usec * 100000));
				$excelnimi = md5(uniqid(mt_rand(), true)).".xls";

				$workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
				$workbook->setVersion(8);
				$worksheet =& $workbook->addWorksheet('Sheet 1');

				$format_bold =& $workbook->addFormat();
				$format_bold->setBold();

				$workbook->setCustomColor(12, 255, 0, 0); // red
				$workbook->setCustomColor(13, 0, 180, 15); // green
				$workbook->setCustomColor(14, 255, 255, 255); // white

				$format_text_error =& $workbook->addFormat();
				$format_text_error->setFgColor(14);
				$format_text_error->setColor(12);
				$format_text_error->setBold();
				$format_text_error->setPattern(1);

				$format_text_ok =& $workbook->addFormat();
				$format_text_ok->setFgColor(14);
				$format_text_ok->setColor(13);
				$format_text_ok->setBold();
				$format_text_ok->setPattern(1);

				$format_summa =& $workbook->addFormat();
				$format_summa->setBold();
				$format_summa->setHAlign('right');

				$format_tuoteno =& $workbook->addFormat();
				$format_tuoteno->setHAlign('left');

				$format_ale =& $workbook->addFormat();
				$format_ale->setHAlign('right');

				$excelrivi 	 = 0;
				$excelsarake = 0;

				$worksheet->writeString($excelrivi, $excelsarake, t("Tapvm")."\n".t("Erpvm"), $format_bold);
				$excelsarake++;

				$worksheet->writeString($excelrivi, $excelsarake, t("Ytunnus"), $format_bold);
				$excelsarake++;

				$worksheet->writeString($excelrivi, $excelsarake, t("Nimi"), $format_bold);
				$excelsarake++;

				$worksheet->writeString($excelrivi, $excelsarake, t("Postitp"), $format_bold);
				$excelsarake++;

				$worksheet->writeString($excelrivi, $excelsarake, t("Summa"), $format_bold);
				$excelsarake++;

				$worksheet->writeString($excelrivi, $excelsarake, t("Laskunro"), $format_bold);
				$excelsarake++;

				$excelsarake = 0;
				$excelrivi++;

				$worksheet->writeString($excelrivi, $excelsarake, tv1dateconv($lasku_row["tapvm"])."\n".tv1dateconv($lasku_row["erpcm"]));
				$excelsarake++;

				$worksheet->writeString($excelrivi, $excelsarake, $lasku_row['ytunnus']);
				$excelsarake++;

				$worksheet->writeString($excelrivi, $excelsarake, $lasku_row['nimi']);
				$excelsarake++;

				$worksheet->writeString($excelrivi, $excelsarake, $lasku_row['postitp']);
				$excelsarake++;

				$worksheet->writeString($excelrivi, $excelsarake, $lasku_row['summa'].' '.$lasku_row['valkoodi']);
				$excelsarake++;

				$worksheet->writeString($excelrivi, $excelsarake, $lasku_row['laskunro']);
				$excelsarake++;

				$excelsarake = 0;
				$excelrivi += 2;

				$worksheet->writeString($excelrivi, $excelsarake, t("Tuoteno"), $format_bold);
				$excelsarake++;

				$worksheet->writeString($excelrivi, $excelsarake, t("Nimitys"), $format_bold);
				$excelsarake++;

				$worksheet->writeString($excelrivi, $excelsarake, t("Tilattumäärä"), $format_bold);
				$excelsarake++;

				$worksheet->writeString($excelrivi, $excelsarake, t("Bruttohinta"), $format_bold);
				$excelsarake++;

				$worksheet->writeString($excelrivi, $excelsarake, t("Ale"), $format_bold);
				$excelsarake++;

				$worksheet->writeString($excelrivi, $excelsarake, t("Nettohinta"), $format_bold);
				$excelsarake++;

				$worksheet->writeString($excelrivi, $excelsarake, t("Ero kpl"), $format_bold);
				$excelsarake++;

				$worksheet->writeString($excelrivi, $excelsarake, t("Ero hinta"), $format_bold);
				$excelsarake++;

				$worksheet->writeString($excelrivi, $excelsarake, t("Tuoteno"), $format_bold);
				$excelsarake++;

				$worksheet->writeString($excelrivi, $excelsarake, t("Nimitys"), $format_bold);
				$excelsarake++;

				$worksheet->writeString($excelrivi, $excelsarake, t("Tilattumäärä"), $format_bold);
				$excelsarake++;

				$worksheet->writeString($excelrivi, $excelsarake, t("Nettohinta"), $format_bold);

				$excelsarake = 0;
				$excelrivi++;
			}

			echo "<input type='hidden' id='sort_direction' name='sort_direction' value='desc'>";

			echo "	<tr>
						<th colspan='6' valign='top'>",t("Lasku")," {$laskunro}</th>
						<td class='back'>&nbsp;</td>
						<td class='back'>&nbsp;</td>
						<td class='back'>&nbsp;</td>
						<td class='back'>&nbsp;</td>
						<th colspan='6' valign='top'>",t("Tilaus")," {$purchaseorder_tilausnumero}</th>
					</tr>";

			echo "	<tr id='table_header'>
						<th>",t("Tuoteno"),"</th>
						<th>",t("Nimitys"),"</th>
						<th>",t("Tilattumäärä"),"</th>
						<th>",t("Bruttohinta"),"</th>
						<th>",t("Ale"),"</th>
						<th>",t("Nettohinta"),"</th>
						<td class='back'>&nbsp;</td>
						<th><a class='sort' id='th_kpl_sort'>",t("Ero kpl"),"</a></th>
						<th><a class='sort' id='th_hinta_sort'>",t("Ero hinta"),"</a></th>
						<td class='back'>&nbsp;</td>
						<th>",t("Tuoteno"),"</th>
						<th>",t("Nimitys"),"</th>
						<th>",t("Tilattumäärä"),"</th>
						<th>",t("Nettohinta"),"</th>
					</tr>";

			$invoice_summa = 0;
			$purchaseorder_summa = 0;

			if (is_array($loytyy_kummastakin) and count($loytyy_kummastakin) > 0) {

				$i = $x = 0;

				$js_array_hinta = $js_array_kpl = array();

				foreach ($loytyy_kummastakin as $tuoteno => $null) {
					$tmp = abs($invoice[$tuoteno]['nettohinta'] - $purchaseorder[$tuoteno]['nettohinta']);
					if ($tmp != 0) {
						$js_array_hinta[$x] = $tmp;
					}

					$tmp = abs($invoice[$tuoteno]['tilattumaara'] - $purchaseorder[$tuoteno]['tilattumaara']);
					if ($tmp != 0) {
						$js_array_kpl[$x] = $tmp;
					}

					$x++;
				}

				asort($js_array_hinta);
				asort($js_array_kpl);

				foreach ($loytyy_kummastakin as $tuoteno => $null) {

					$invoice_nettohinta 		= $invoice[$tuoteno]['nettohinta'];
					$purchaseorder_nettohinta 	= $purchaseorder[$tuoteno]['nettohinta'];

					echo "<tr class='aktiivi' ";

					if (trim($invoice[$tuoteno]['tilattumaara']) != '') {
						echo "id='tr_{$i}'";
					}

					echo ">";

					echo "<td valign='top'>{$tuoteno}</td>";

					if (isset($worksheet)) {
						$worksheet->write($excelrivi, $excelsarake, $tuoteno, $format_tuoteno);
						$excelsarake++;
					}

					echo "<td valign='top'>",utf8_decode($invoice[$tuoteno]['nimitys']),"</td>";

					if (isset($worksheet)) {
						$worksheet->write($excelrivi, $excelsarake, utf8_decode($invoice[$tuoteno]['nimitys']));
						$excelsarake++;
					}

					$error = $invoice[$tuoteno]['tilattumaara'] == $purchaseorder[$tuoteno]['tilattumaara'] ? "ok" : "error";
					echo "<td valign='top' style='text-align: right;'><font class='{$error}'>{$invoice[$tuoteno]['tilattumaara']}</font></td>";

					if (isset($worksheet)) {
						$worksheet->write($excelrivi, $excelsarake, $invoice[$tuoteno]['tilattumaara'], ${'format_text_'.$error});
						$excelsarake++;
					}

					echo "<td valign='top' style='text-align: right;'>{$invoice[$tuoteno]['bruttohinta']}</td>";

					if (isset($worksheet)) {
						$worksheet->write($excelrivi, $excelsarake, $invoice[$tuoteno]['bruttohinta']);
						$excelsarake++;
					}

					echo "<td valign='top' style='text-align: right;'>";

					$exceli_ale = '';

					if (isset($invoice[$tuoteno]['ale'])) {

						foreach ($invoice[$tuoteno]['ale'] as $k => $ale) {
							echo $ale;

							$exceli_ale .= $ale;

							if (current($invoice[$tuoteno]['ale']) != end($invoice[$tuoteno]['ale'])) {
								echo "<br />";
								$exceli_ale .= "\n";
							}
						}
					}
					else {
						echo "&nbsp;";
					}

					echo "</td>";

					if (isset($worksheet)) {
						$worksheet->write($excelrivi, $excelsarake, $exceli_ale, $format_ale);
						$excelsarake++;
					}

					if (trim($invoice[$tuoteno]['tilattumaara']) != '') {
						$invoice_summa += $invoice_nettohinta;
						$error = $invoice_nettohinta == $purchaseorder_nettohinta ? "ok" : "error";
					}
					else {
						$error = '';
					}

					echo "<td valign='top' style='text-align: right;'><font class='{$error}'>",sprintf('%.02f', $invoice_nettohinta),"</font></td>";

					if (isset($worksheet)) {
						$worksheet->write($excelrivi, $excelsarake, sprintf('%.02f', $invoice_nettohinta), ${'format_text_'.$error});
						$excelsarake++;
					}

					echo "<td class='back'>&nbsp;</td>";

					if (trim($invoice[$tuoteno]['tilattumaara']) != '' and trim($purchaseorder[$tuoteno]['tilattumaara']) != '') {
						if ($invoice[$tuoteno]['tilattumaara'] - $purchaseorder[$tuoteno]['tilattumaara'] != 0) {
							echo "<td valign='top' style='text-align: right;'>",abs($invoice[$tuoteno]['tilattumaara'] - $purchaseorder[$tuoteno]['tilattumaara']),"</td>";

							if (isset($worksheet)) {
								$worksheet->write($excelrivi, $excelsarake, abs($invoice[$tuoteno]['tilattumaara'] - $purchaseorder[$tuoteno]['tilattumaara']));
								$excelsarake++;
							}
						}
						else {
							echo "<td>&nbsp;</td>";

							if (isset($worksheet)) {
								$worksheet->write($excelrivi, $excelsarake, '');
								$excelsarake++;
							}
						}

						if ($invoice_nettohinta - $purchaseorder_nettohinta != 0) {
							echo "<td valign='top' style='text-align: right;'>",abs(sprintf('%.02f', ($invoice_nettohinta - $purchaseorder_nettohinta))),"</td>";

							if (isset($worksheet)) {
								$worksheet->write($excelrivi, $excelsarake, abs(sprintf('%.02f', ($invoice_nettohinta - $purchaseorder_nettohinta))));
								$excelsarake++;
							}
						}
						else {
							echo "<td>&nbsp;</td>";

							if (isset($worksheet)) {
								$worksheet->write($excelrivi, $excelsarake, '');
								$excelsarake++;
							}
						}
					}
					else {
						echo "<td>&nbsp;</td>";
						echo "<td>&nbsp;</td>";

						if (isset($worksheet)) {
							$worksheet->write($excelrivi, $excelsarake, '');
							$excelsarake++;

							$worksheet->write($excelrivi, $excelsarake, '');
							$excelsarake++;
						}
					}

					echo "<td class='back'>&nbsp;</td>";

					if (array_key_exists($tuoteno, $invoice)) {
						echo "<td valign='top'>{$tuoteno}</td>";

						if (isset($worksheet)) {
							$worksheet->write($excelrivi, $excelsarake, $tuoteno, $format_tuoteno);
							$excelsarake++;
						}
					}
					else {
						echo "<td valign='top'></td>";

						if (isset($worksheet)) {
							$worksheet->write($excelrivi, $excelsarake, '');
							$excelsarake++;
						}
					}

					echo "<td valign='top'>{$purchaseorder[$tuoteno]['nimitys']}</td>";

					if (isset($worksheet)) {
						$worksheet->write($excelrivi, $excelsarake, $purchaseorder[$tuoteno]['nimitys']);
						$excelsarake++;
					}

					$error = $invoice[$tuoteno]['tilattumaara'] == $purchaseorder[$tuoteno]['tilattumaara'] ? "ok" : "error";
					echo "<td valign='top' style='text-align: right;'><font class='{$error}'>{$purchaseorder[$tuoteno]['tilattumaara']}</font></td>";

					if (isset($worksheet)) {
						$worksheet->write($excelrivi, $excelsarake, $purchaseorder[$tuoteno]['tilattumaara'], ${'format_text_'.$error});
						$excelsarake++;
					}

					$purchaseorder_summa += $purchaseorder_nettohinta;

					if (trim($invoice[$tuoteno]['tilattumaara']) != '' and trim($purchaseorder[$tuoteno]['tilattumaara']) != 0) {

						$error = $invoice_nettohinta == $purchaseorder_nettohinta ? "ok" : "error";

						echo "<td valign='top' style='text-align: right;'><font class='{$error}'>",sprintf('%.02f', $purchaseorder_nettohinta),"</font></td>";

						if (isset($worksheet)) {
							$worksheet->write($excelrivi, $excelsarake, $purchaseorder_nettohinta, ${'format_text_'.$error});
							$excelsarake++;
						}
					}
					else {
						$error = '';
						echo "<td valign='top' style='text-align: right;'>&nbsp;</td>";

						if (isset($worksheet)) {
							$worksheet->write($excelrivi, $excelsarake, '');
							$excelsarake++;
						}
					}

					echo "</tr>";

					$i++;

					if (isset($worksheet)) {
						$excelsarake = 0;
						$excelrivi++;
					}
				}

				echo "	<script type='text/javascript'>
							$(function(){

								var js_array = [];

								$('#th_hinta_sort').click(function(){
									js_array = ".json_encode($js_array_hinta).";
								});

								$('#th_kpl_sort').click(function(){
									js_array = ".json_encode($js_array_kpl).";
								});

								$('.sort').click(function(){
									if ($('#sort_direction').val() == 'asc') {
										$('#sort_direction').val('desc');

										for (key in js_array) {
											$('#tr_summa').before($('#tr_'+key));
										}
									}
									else {
										$('#sort_direction').val('asc');

										for (key in js_array) {
											$('#table_header').after($('#tr_'+key));
										}
									}
								});

							});
						</script>";
			}

			if (is_array($invoice_ei_loydy) and count($invoice_ei_loydy) > 0) {
				foreach ($invoice_ei_loydy as $tuoteno => $tuote) {
					echo "<tr class='aktiivi'>";
					echo "<td>{$tuoteno}</td>";

					if (isset($worksheet)) {
						$excelsarake = 0;
						$worksheet->write($excelrivi, $excelsarake, $tuoteno, $format_tuoteno);
						$excelsarake++;
					}

					foreach ($tuote as $key => $val) {

						if (is_array($val)) {
							echo "<td>";

							$exceli_ale = '';

							foreach ($val as $k => $v) {
								echo $v;

								$exceli_ale .= $v;

								if (current($val) != end($val)) {
									echo "<br/>";
									$exceli_ale .= "\n";
								}
							}

							echo "</td>";

							if (isset($worksheet)) {
								$worksheet->write($excelrivi, $excelsarake, $exceli_ale, $format_ale);
							}
						}
						else {
							if ($key == 'nettohinta' or $key == 'bruttohinta') {

								if ($key == 'nettohinta') $invoice_summa += $val;

								echo "<td valign='top' style='text-align: right;'>",sprintf('%.02f', $val),"</td>";

								if (isset($worksheet)) {
									$worksheet->write($excelrivi, $excelsarake, $val);
								}
							}
							elseif ($key == 'tilattumaara') {
								echo "<td valign='top' style='text-align: right;'>{$val}</td>";

								if (isset($worksheet)) {
									$worksheet->write($excelrivi, $excelsarake, $val);
								}
							}
							else {
								echo "<td valign='top'>{$val}</td>";

								if (isset($worksheet)) {
									$worksheet->write($excelrivi, $excelsarake, $val);
								}
							}
						}

						$excelsarake++;
					}

					if (isset($worksheet)) {
						$worksheet->write($excelrivi, 9, t("Ei löydy tilaukselta").'!', $format_text_error);
						$excelrivi++;
					}

					echo "<td class='back'>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td class='back'>&nbsp;</td><td>&nbsp;</td><td><font class='error'>",t("Ei löydy tilaukselta"),"!</font></td><td>&nbsp;</td><td>&nbsp;</td>";
					echo "</tr>";
				}
			}

			if (is_array($purchaseorder_ei_loydy) and count($purchaseorder_ei_loydy) > 0) {
				foreach ($purchaseorder_ei_loydy as $tuoteno => $tuote) {
					echo "<tr class='aktiivi'>";
					echo "<td>&nbsp;</td><td><font class='error'>",t("Ei löydy laskulta"),"!</font></td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td class='back'>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td class='back'>&nbsp;</td>";

					if (isset($worksheet)) {
						$worksheet->write($excelrivi, 1, t("Ei löydy laskulta").'!', $format_text_error);
					}

					echo "<td>{$tuoteno}</td>";

					if (isset($worksheet)) {
						$excelsarake = 8;
						$worksheet->write($excelrivi, $excelsarake, $tuoteno, $format_tuoteno);
						$excelsarake++;
					}

					foreach ($tuote as $key => $val) {

						if ($key == 'nettohinta' or $key == 'bruttohinta') {

							if ($key == 'nettohinta') $purchaseorder_summa += $val;

							echo "<td valign='top' style='text-align: right;'>",sprintf('%.02f', $val),"</td>";

							if (isset($worksheet)) {
								$worksheet->write($excelrivi, $excelsarake, $val);
							}
						}
						elseif ($key == 'tilattumaara') {
							echo "<td valign='top' style='text-align: right;'>{$val}</td>";

							if (isset($worksheet)) {
								$worksheet->write($excelrivi, $excelsarake, $val);
							}
						}
						else {
							echo "<td valign='top'>{$val}</td>";

							if (isset($worksheet)) {
								$worksheet->write($excelrivi, $excelsarake, $val);
							}
						}

						$excelsarake++;
					}

					$excelrivi++;

					echo "</tr>";
				}
			}

			echo "<tr id='tr_summa'><th colspan='5'>",t("Summa"),"</th><th valign='top' style='text-align: right;'>{$invoice_summa} {$lasku_row['valkoodi']}</th><td class='back'>&nbsp;</td><td class='back'>&nbsp;</td><td class='back'>&nbsp;</td><td class='back'>&nbsp;</td><th colspan='3'>",t("Summa"),"</th><th valign='top' style='text-align: right;'>{$purchaseorder_summa} {$lasku_row['valkoodi']}</th></tr>";
			echo "</table>";

			if (isset($worksheet)) {
				$excelrivi++;
				$excelsarake = 4;

				$worksheet->write($excelrivi, $excelsarake, t("Summa"), $format_bold);
				$excelsarake++;

				$worksheet->write($excelrivi, $excelsarake, str_replace(".", ",", $invoice_summa)." ".$lasku_row['valkoodi'], $format_summa);

				$excelsarake = 10;

				$worksheet->write($excelrivi, $excelsarake, t("Summa"), $format_bold);
				$excelsarake++;

				$worksheet->write($excelrivi, $excelsarake, str_replace(".", ",", $purchaseorder_summa)." ".$lasku_row['valkoodi'], $format_summa);

				$workbook->close();
			}

			echo "<br /><br /><form method='post'>";
			echo "<table>";
			echo "<tr><th>",t("Lähetä raportti käyttäjälle"),":</th>";
			echo "<input type='hidden' name='tee' value='laheta_tiedosto'>";

			$query = "	SELECT nimi, eposti
						FROM kuka
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND eposti != ''
						AND extranet = ''";
			$eposti_result = pupe_query($query);

			echo "<td><select name='asiakasemail'><option value=''>",t("Valitse vastaanottaja"),"</option>";

			while ($eposti_row = mysql_fetch_assoc($eposti_result)) {
				echo "<option value='{$eposti_row['eposti']}'>{$eposti_row['nimi']}</option>";
			}

			echo "</select></td>";

			if (isset($laskupdf) and file_exists($laskupdf)) {
				echo "<tr><th>",t("Liitä lasku sähköpostiin"),":</th>";
				echo "<td><input type='checkbox' name='liitalasku' value='JES'></td></tr>";
			}

			echo "<tr><th>",t("Viesti"),":</th>";
			echo "<td><textarea name='emailviesti' cols='40' rows=5' wrap='hard'></textarea></td></tr>";


			echo "<tr><td class='back'><input type='submit' value='",t("Lähetä"),"' /></td></tr>";

			echo "</table></form>";

			if (isset($tee) and $tee == "laheta_tiedosto") {

				$sanitized_email = filter_var($asiakasemail, FILTER_SANITIZE_EMAIL);

				if (filter_var($sanitized_email, FILTER_VALIDATE_EMAIL)) {

					$komento = 'asiakasemail'.$sanitized_email;

					$liite = array();
					$kutsu = array();
					$ctype = array();

					$liite[0] = "/tmp/$excelnimi";
					$kutsu[0] = t("Laskujen")."_".t("vertailu")."_".t("raportti").".xls";
					$ctype[0] = "excel";

					if ($liitalasku != "" and isset($laskupdf) and file_exists($laskupdf)) {
						$liite[1] = $laskupdf;
						$kutsu[1] = t("Lasku")." ".$lasku_row["laskunro"];
						$ctype[1] = "pdf";
					}

					if (trim($emailviesti) != "") $content_body = $emailviesti."\n\n\n";

					$liitenimi[0] = t("Laskujen")."_".t("vertailu")."_".t("raportti").".xls";

					$subject = $yhtiorow['nimi']." - ".t("Laskujen täsmäytysraportti");

					require('inc/sahkoposti.inc');

					system("rm -f /tmp/$excelnimi");

					if ($boob) {
						echo "<br><font class='message'>",t("Lähetettiin sähköposti osoitteeseen")," {$sanitized_email}.</font><br /><br />";
					}
				}
			}


		}
		else {
			echo "<tr><td class='message'>",t("Vertailukelpoista laskua ja tilausta ei löytynyt"),"!</tr>";
		}

		echo "</table><br><br>";

		if ($lasku_row['tila'] == "H" and $lasku_row['hyvaksyja_nyt'] == $kukarow["kuka"] and tarkista_oikeus("hyvak.php")) {
			echo "<form action = 'hyvak.php' method='post'>
					<input type='hidden' name = 'tunnus' value='{$lasku_row['tunnus']}'>
					<input type='hidden' name = 'tee' value='H'>
					<td class='back'><input type='Submit' value='",t("Hyväksy lasku"),"'></td>
					</form><br><br><br>";


			echo "<form action = 'hyvak.php' method='post'>
					<input type='hidden' name='tee' value='Z'>
					<input type='hidden' name='tunnus' value='{$lasku_row['tunnus']}'>
					<td class='back'><input type='Submit' value='",t("Pysäytä laskun käsittely"),"'></td>
					</form>";
		}
	}
	else {
		// Summaus hyväksynnässä olevista laskuista
		echo "<br /><br />";

		$hyvaksyjalisa = '';
		$tiliointilisa = '';

		if ($kustannuspaikka != 0) {
			$tiliointilisa = " JOIN tiliointi on (tiliointi.yhtio = lasku.yhtio and tiliointi.ltunnus = lasku.tunnus and tiliointi.kustp = '$kustannuspaikka') ";
		}
		else {
			$tiliointilisa = '';
		}

		if (isset($hyvaksyja) and trim($hyvaksyja) != '') {
			$hyvaksyja = (string) $hyvaksyja;
			$hyvaksyjalisa = " and lasku.hyvaksyja_nyt = '{$hyvaksyja}' ";
		}

		$pvmlisa = '';

		if (isset($app) and is_numeric($app) and isset($akk) and is_numeric($akk) and isset($avv) and is_numeric($avv) and isset($lpp) and is_numeric($lpp) and isset($lkk) and is_numeric($lkk) and isset($lvv) and is_numeric($lvv)) {
			$pvmlisa = " and lasku.tapvm >= '".(int) $avv."-".(int) $akk."-".(int) $app."' and lasku.tapvm <= '".(int) $lvv."-".(int) $lkk."-".(int) $lpp."' ";
		}

		$query = "	SELECT lasku.laskunro,
					if(kuka.nimi is not null, kuka.nimi, lasku.hyvaksyja_nyt) hyvaknimi,
					lasku.nimi,
					round(lasku.summa *lasku.vienti_kurssi, 2) kotisumma,
					lasku.tunnus, lasku.tila
					FROM lasku
					JOIN liitetiedostot ON (liitetiedostot.yhtio = lasku.yhtio and liitetiedostot.liitos = 'lasku' AND liitetiedostot.liitostunnus = lasku.tunnus AND liitetiedostot.kayttotarkoitus IN ('FINVOICE', 'EDI'))
					$tiliointilisa
					LEFT JOIN kuka ON kuka.yhtio=lasku.yhtio and kuka.kuka=lasku.hyvaksyja_nyt
					WHERE lasku.yhtio = '{$kukarow['yhtio']}'
					and lasku.tila IN ('H', 'M')
					and lasku.alatila != 'H'
					and lasku.nimi = 'Örum Oy Ab'
					$hyvaksyjalisa
					$pvmlisa
					GROUP BY 1,2,3,4,5";
		$result = pupe_query($query);

		echo "<table>";

		echo "<tr>";
		echo "<th>",t("Laskunumero"),"</th>";
		echo "<th>",t("Hyväksyjä"),"</th>";
		echo "<th>",t("Toimittaja"),"</th>";
		echo "<th>",t("Summa"),"</th>";
		echo "<th>",t("Vertailu"),"</th>";
		echo "</tr>";

		while ($trow = mysql_fetch_assoc($result)) {

			list($invoice, $purchaseorder, $invoice_ei_loydy, $purchaseorder_ei_loydy, $loytyy_kummastakin, $purchaseorder_tilausnumero) = laskun_ja_tilauksen_vertailu($kukarow, $trow['tunnus']);

			if ($invoice != FALSE and $invoice != 'ei_loydy_edia' ) {

				$ok = 'ok';

				if (count($purchaseorder_ei_loydy) == 0 and count($invoice_ei_loydy) == 0 and count($loytyy_kummastakin) > 0) {
					foreach ($loytyy_kummastakin as $tuoteno => $null) {
						if (substr($tuoteno, 0, 15) != "Ei_tuotekoodia_" and ($invoice[$tuoteno]['tilattumaara'] != $purchaseorder[$tuoteno]['tilattumaara'] or abs($invoice[$tuoteno]['nettohinta'] - $purchaseorder[$tuoteno]['nettohinta']) > 1)) {
							$ok = '';
							break;
						}
					}
				}
				else {
					$ok = '';
				}

				if ($eroa == 2 and $ok == '') {
					continue;
				}

				if ($eroa == 1 and $ok == 'ok') {
					continue;
				}

				echo "<tr class='aktiivi'>";
				echo "<td>{$trow['laskunro']}</td>";
				echo "<td>{$trow['hyvaknimi']}</td>";
				echo "<td>{$trow['nimi']}</td>";
				echo "<td align='right'>{$trow['kotisumma']}</td>";

				echo "<td valign='top'>";
				echo "<a href='laskujen_vertailu.php?laskunro={$trow['laskunro']}&hyvaksyja={$hyvaksyja}&app={$app}&akk={$akk}&avv={$avv}&lpp={$lpp}&lkk={$lkk}&lvv={$lvv}&kustannuspaikka={$kustannuspaikka}&eroa={$eroa}&lopetus={$PHP_SELF}////hyvaksyja={$hyvaksyja}//app={$app}//akk={$akk}//avv={$avv}//lpp={$lpp}//lkk={$lkk}//lvv={$lvv}//kustannuspaikka={$kustannuspaikka}//eroa={$eroa}'>";

				if ($ok == 'ok') {
					echo "<font class='ok'>",t("OK"),"</font>";
				}
				else {
					echo t("Eroja");
				}

				echo "</a>";
				echo "</td>";
				echo "</tr>";
			}
			elseif ($invoice == 'ei_loydy_edia') {
				echo "<tr>";
				echo "<td colspan='5'>";
				echo "<font class='error'>".t("Tilaus ei löydy")."</font>";
				echo "</td>";
				echo "</tr>";
			}
		}
		echo "</table><br />";
	}

	require ("inc/footer.inc");

?>