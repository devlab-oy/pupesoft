<?php

	require ("inc/parametrit.inc");

	echo "<font class='head'>",t("Laskun ja tilauksen vertailu"),"</font><hr>";

	if (!isset($laskunro)) $laskunro = '';

	js_popup();

	echo "<br><table>";
	echo "<form method='post' action=''>";
	echo "<tr><th>",t("Laskunumero"),"</th><td><input type='text' name='laskunro' value='{$laskunro}' /></td><td class='back'><input type='submit' value='",t("Hae"),"' /></td></tr>";
	echo "</form>";
	echo "</table>";

	if (trim($laskunro) != '' and is_numeric($laskunro)) {

		$query = "	SELECT tapvm, erpcm, concat(nimi, ' ', nimitark) nimi, postitp, ytunnus, summa, valkoodi, laskunro, tunnus
					FROM lasku
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND laskunro = '{$laskunro}'";
		$lasku_res = mysql_query($query) or pupe_error($query);
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
					<td valign='top' style='text-align: right;'>{$lasku_row['laskunro']}</td>
				</tr>";
		echo "</table>";

		echo "<br/><br/><table>";

		list($invoice, $purchaseorder, $invoice_ei_loydy, $purchaseorder_ei_loydy, $loytyy_kummastakin, $purchaseorder_tilausnumero) = laskun_ja_tilauksen_vertailu($kukarow, $lasku_row["tunnus"]);

		if ($invoice !== FALSE and (count($invoice_ei_loydy) > 0 or count($loytyy_kummastakin) > 0)) {

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

			if (count($loytyy_kummastakin) > 0) {

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
					echo "<td valign='top'>",utf8_decode($invoice[$tuoteno]['nimitys']),"</td>";

					$error = $invoice[$tuoteno]['tilattumaara'] == $purchaseorder[$tuoteno]['tilattumaara'] ? "ok" : "error";
					echo "<td valign='top' style='text-align: right;'><font class='{$error}'>{$invoice[$tuoteno]['tilattumaara']}</font></td>";

					echo "<td valign='top' style='text-align: right;'>{$invoice[$tuoteno]['bruttohinta']}</td>";

					echo "<td valign='top' style='text-align: right;'>";

					if (isset($invoice[$tuoteno]['ale'])) {
						foreach ($invoice[$tuoteno]['ale'] as $k => $ale) {
							echo $ale;

							if (current($invoice[$tuoteno]['ale']) != end($invoice[$tuoteno]['ale'])) echo "<br />";
						}
					}
					else {
						echo "&nbsp;";
					}

					echo "</td>";

					if (trim($invoice[$tuoteno]['tilattumaara']) != '') {
						$invoice_summa += $invoice_nettohinta;
						$error = $invoice_nettohinta == $purchaseorder_nettohinta ? "ok" : "error";
					}
					else {
						$error = '';
					}

					echo "<td valign='top' style='text-align: right;'><font class='{$error}'>",sprintf('%.02f', $invoice_nettohinta),"</font></td>";

					echo "<td class='back'>&nbsp;</td>";

					if (trim($invoice[$tuoteno]['tilattumaara']) != '' and trim($purchaseorder[$tuoteno]['tilattumaara']) != '') {
						if ($invoice[$tuoteno]['tilattumaara'] - $purchaseorder[$tuoteno]['tilattumaara'] != 0) {
							echo "<td valign='top' style='text-align: right;'>",abs($invoice[$tuoteno]['tilattumaara'] - $purchaseorder[$tuoteno]['tilattumaara']),"</td>";
						}
						else {
							echo "<td>&nbsp;</td>";
						}

						if ($invoice_nettohinta - $purchaseorder_nettohinta != 0) {
							echo "<td valign='top' style='text-align: right;'>",abs(sprintf('%.02f', ($invoice_nettohinta - $purchaseorder_nettohinta))),"</td>";
						}
						else {
							echo "<td>&nbsp;</td>";
						}
					}
					else {
						echo "<td>&nbsp;</td>";
						echo "<td>&nbsp;</td>";
					}

					echo "<td class='back'>&nbsp;</td>";

					if (array_key_exists($tuoteno, $invoice)) {
						echo "<td valign='top'>{$tuoteno}</td>";
					}
					else {
						echo "<td valign='top'></td>";
					}

					echo "<td valign='top'>{$purchaseorder[$tuoteno]['nimitys']}</td>";

					$error = $invoice[$tuoteno]['tilattumaara'] == $purchaseorder[$tuoteno]['tilattumaara'] ? "ok" : "error";
					echo "<td valign='top' style='text-align: right;'><font class='{$error}'>{$purchaseorder[$tuoteno]['tilattumaara']}</font></td>";

					$purchaseorder_summa += $purchaseorder_nettohinta;

					if (trim($invoice[$tuoteno]['tilattumaara']) != '' and trim($purchaseorder[$tuoteno]['tilattumaara']) != 0) {

						$error = $invoice_nettohinta == $purchaseorder_nettohinta ? "ok" : "error";

						echo "<td valign='top' style='text-align: right;'><font class='{$error}'>",sprintf('%.02f', $purchaseorder_nettohinta),"</font></td>";
					}
					else {
						$error = '';
						echo "<td valign='top' style='text-align: right;'>&nbsp;</td>";
					}

					echo "</tr>";

					$i++;
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

			foreach ($invoice_ei_loydy as $tuoteno => $tuote) {
				echo "<tr class='aktiivi'>";
				echo "<td>{$tuoteno}</td>";

				foreach ($tuote as $key => $val) {

					if (is_array($val)) {
						echo "<td>";

						foreach ($val as $k => $v) {
							echo $v;
							if (current($val) != end($val)) echo "<br/>";
						}

						echo "</td>";
					}
					else {
						if ($key == 'nettohinta' or $key == 'bruttohinta') {
							if ($key == 'nettohinta') $invoice_summa += $val;
							echo "<td valign='top' style='text-align: right;'>",sprintf('%.02f', $val),"</td>";
						}
						elseif ($key == 'tilattumaara') {
							echo "<td valign='top' style='text-align: right;'>{$val}</td>";
						}
						else {
							echo "<td valign='top'>{$val}</td>";
						}
					}
				}

				echo "<td class='back'>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td class='back'>&nbsp;</td><td>&nbsp;</td><td><font class='error'>",t("Ei löydy tilaukselta"),"!</font></td><td>&nbsp;</td><td>&nbsp;</td>";
				echo "</tr>";
			}

			foreach ($purchaseorder_ei_loydy as $tuoteno => $tuote) {
				echo "<tr class='aktiivi'>";
				echo "<td>&nbsp;</td><td><font class='error'>",t("Ei löydy laskulta"),"!</font></td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td class='back'>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td class='back'>&nbsp;</td>";

				echo "<td>{$tuoteno}</td>";

				foreach ($tuote as $key => $val) {

					if ($key == 'nettohinta' or $key == 'bruttohinta') {
						if ($key == 'nettohinta') $purchaseorder_summa += $val;
						echo "<td valign='top' style='text-align: right;'>",sprintf('%.02f', $val),"</td>";
					}
					elseif ($key == 'tilattumaara') {
						echo "<td valign='top' style='text-align: right;'>{$val}</td>";
					}
					else {
						echo "<td valign='top'>{$val}</td>";
					}
				}

				echo "</tr>";
			}

			echo "<tr id='tr_summa'><th colspan='5'>",t("Summa"),"</th><th valign='top' style='text-align: right;'>{$invoice_summa} {$lasku_row['valkoodi']}</th><td class='back'>&nbsp;</td><td class='back'>&nbsp;</td><td class='back'>&nbsp;</td><td class='back'>&nbsp;</td><th colspan='3'>",t("Summa"),"</th><th valign='top' style='text-align: right;'>{$purchaseorder_summa} {$lasku_row['valkoodi']}</th></tr>";
		}
		else {
			echo "<tr><td class='message'>",t("Vertailukelpoista laskua ja tilausta ei löytynyt"),"!</tr>";
		}

		echo "</table><br><br>";

		if (tarkista_oikeus("hyvak.php")) {
			echo "<form action = 'hyvak.php' method='post'>
					<input type='hidden' name = 'tunnus' value='$lasku_row[tunnus]'>
					<input type='hidden' name = 'tee' value='H'>
					<td class='back'><input type='Submit' value='".t("Hyväksy lasku")."'></td>
					</form><br><br><br>";


			echo "<form action = 'hyvak.php' method='post'>
					<input type='hidden' name='tee' value='Z'>
					<input type='hidden' name='tunnus' value='$lasku_row[tunnus]'>
					<td class='back'><input type='Submit' value='".t("Pysäytä laskun käsittely")."'></td>
					</form>";
		}


	}
	else {
		// Summaus hyväksynnässä olevista laskuista
		echo "<br><br>";

		$query = "	SELECT lasku.laskunro,
					if(kuka.nimi is not null, kuka.nimi, lasku.hyvaksyja_nyt) hyvaknimi,
					lasku.nimi,
					round(lasku.summa *lasku.vienti_kurssi, 2) kotisumma,
					lasku.tunnus
					FROM lasku
					JOIN liitetiedostot ON (liitetiedostot.yhtio = lasku.yhtio and liitetiedostot.liitos = 'lasku' AND liitetiedostot.liitostunnus = lasku.tunnus AND liitetiedostot.kayttotarkoitus IN ('FINVOICE', 'EDI'))
					LEFT JOIN kuka ON kuka.yhtio=lasku.yhtio and kuka.kuka=lasku.hyvaksyja_nyt
					WHERE lasku.yhtio = '$kukarow[yhtio]'
					and lasku.tila = 'H'
					GROUP BY 1,2,3,4,5";
		$result = mysql_query($query) or pupe_error($query);

		echo "<table>";
		echo "<tr>";
		echo "<th>".t("Laskunumero")."</th>";
		echo "<th>".t("Hyväksyjä")."</th>";
		echo "<th>".t("Toimittaja")."</th>";
		echo "<th>".t("Summa")."</th>";
		echo "<th>".t("Vertailu")."</th>";
		echo "</tr>";

		while ($trow = mysql_fetch_array($result)) {
			echo "<tr class='aktiivi'>";
			echo "<td>$trow[laskunro]</td>";
			echo "<td>$trow[hyvaknimi]</td>";
			echo "<td>$trow[nimi]</td>";
			echo "<td align='right'>$trow[kotisumma]</td>";

			echo "<td valign='top'>";

			list($invoice, $purchaseorder, $invoice_ei_loydy, $purchaseorder_ei_loydy, $loytyy_kummastakin, $purchaseorder_tilausnumero) = laskun_ja_tilauksen_vertailu($kukarow, $trow['tunnus']);

			if ($invoice != FALSE and $invoice != 'ei_loydy_edia') {
				if (count($invoice_ei_loydy) == 0 and count($loytyy_kummastakin) > 0) {
					$ok = 'ok';

					foreach ($loytyy_kummastakin as $tuoteno => $null) {
						if ($invoice[$tuoteno]['tilattumaara'] != $purchaseorder[$tuoteno]['tilattumaara'] or abs($invoice[$tuoteno]['nettohinta'] - $purchaseorder[$tuoteno]['nettohinta']) > 1) {
							echo "<a href='laskujen_vertailu.php?laskunro=$trow[laskunro]&lopetus=$PHP_SELF////'>",t("Eroja"),"</a>";
							$ok = '';
							break;
						}
					}

					if ($ok == 'ok') {
						echo "<a href='laskujen_vertailu.php?laskunro=$trow[laskunro]&lopetus=$PHP_SELF////'><font class='ok'>",t("OK"),"</font></a>";
					}
				}
				else {
					echo "<a href='laskujen_vertailu.php?laskunro=$trow[laskunro]&lopetus=$PHP_SELF////'>",t("Eroja"),"</a>";
				}
			}
			elseif ($invoice == 'ei_loydy_edia') {
				echo "<font class='error'>".t("Tilaus ei löydy")."</font>";
			}
			else {
				echo "&nbsp;";
			}

			echo "</td>";
			echo "</tr>";
		}
		echo "</table><br>";
	}

	require ("inc/footer.inc");

?>