<?php

	require ("../inc/parametrit.inc");

	echo "<script type='text/javascript' language='javascript'>";
	require_once("inc/jquery.min.js");
	echo "</script>";

	echo "<script type='text/javascript' src='https://www.google.com/jsapi'></script>";

	echo "	<script type='text/javascript' charset='utf-8'>

				google.load('visualization', '1', {packages:['gauge']});

				google.setOnLoadCallback(drawChart);

				function drawChart() {
					var data = new google.visualization.DataTable();
					data.addColumn('string', 'Label');
					data.addColumn('number', 'Value');
					data.addRows(1);
					data.setValue(0, 0, 'EUR');
					data.setValue(0, 1, 0);

					var chart = new google.visualization.Gauge(document.getElementById('chart_div'));
					var options = {	width: 800, 
									height: 220, 
									min: 0,
									max: 400000,
									redFrom: 200000, 
									redTo: 300000, 
									greenFrom: 350000, 
									greenTo: 400000,
									yellowFrom: 300000, 
									yellowTo: 350000, 
									minorTicks: 5,
									majorTicks: ['0', '100k', '200k', '300k', '400k']};
					chart.draw(data, options);

					var tilatut_eurot = parseInt($('#tilatut_eurot').val());

					// tilatut_eurot = 50000;

					if (tilatut_eurot / 4000 > 4) {

						var over = parseInt(tilatut_eurot / 80000);
						over = over < 2 ? 2 : over;
						over = 1 + '.' + over;

						var _dot120 = parseInt(tilatut_eurot * over);
						data.setValue(0, 1, _dot120);
						chart.draw(data, options);

						var _dot90 = parseInt(tilatut_eurot * 0.9);
						setTimeout(function(){data.setValue(0, 1, _dot90); chart.draw(data, options);}, 400);

						setTimeout(function(){data.setValue(0, 1, tilatut_eurot); chart.draw(data, options);}, 800);
					}
					else {
						data.setValue(0, 1, tilatut_eurot);
						chart.draw(data, options);
					}

					var body_bgcolor = $('body').css('background-color');
					$('#chart_div table tr td').css('background-color', body_bgcolor);
				}

				$(document).ready(function() {
				
					$('#naytetaan_tulos').change(function() {
						var date = new Date();

						if ($(this).val() == 'weekly' || $(this).val() == 'monthly') {
							$('#kka').val(1);
						}
						else {
							$('#kka').val(date.getMonth()+1);
						}

						$('#ppa').val(1);
						$('#vva').val(date.getFullYear());

						$('#ppl').val(date.getDate());
						$('#kkl').val(date.getMonth()+1);
						$('#vvl').val(date.getFullYear());
					});
					
				});
			</script>";

	echo "<font class='head'>",t("Myyntitilasto"),"</font><hr>";

	echo "<form method='post' action=''>";
	echo "<table><tr><td class='back' style='text-align: center'>";
	echo "<font class='head'>",t("Tilatut eurot"),"</font>";
	echo "<div id='chart_div'></div>";
	echo "</td><td class='back' style='vertical-align: middle'>";

	$query = "	SELECT ROUND(SUM(hinta), 0) AS tilatut_eurot
				FROM tilausrivi
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tyyppi = 'L'
				AND laadittu >= '2011-10-04 00:00:00'
				#AND laadittu >= CURDATE() + ' 00:00:00'
				AND laadittu <= CURDATE() + ' 23:59:59'";
	$result = pupe_query($query);
	$row = mysql_fetch_assoc($result);

	echo "<input type='hidden' id='tilatut_eurot' value='{$row['tilatut_eurot']}' />";
	echo "<input type='hidden' name='tee' value='laske' />";

	if (!isset($kka)) $kka = date("n",mktime(0, 0, 0, date("n"), 1, date("Y")));
	if (!isset($vva)) $vva = date("Y",mktime(0, 0, 0, date("n"), 1, date("Y")));
	if (!isset($ppa)) $ppa = date("j",mktime(0, 0, 0, date("n"), 1, date("Y")));

	if (!isset($kkl)) $kkl = date("n");
	if (!isset($vvl)) $vvl = date("Y");
	if (!isset($ppl)) $ppl = date("j");

	echo "<table>";
	echo "<tr>";
	echo "<th>",t("Syötä alkupäivämäärä")," (",t("pp-kk-vvvv"),")</th>";
	echo "<td><input type='text' name='ppa' id='ppa' value='{$ppa}' size='3'>";
	echo "<input type='text' name='kka' id='kka' value='{$kka}' size='3'>";
	echo "<input type='text' name='vva' id='vva' value='{$vva}' size='5'></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>",t("Syötä loppupäivämäärä")," (",t("pp-kk-vvvv"),")</th>";
	echo "<td><input type='text' name='ppl' id='ppl' value='{$ppl}' size='3'>";
	echo "<input type='text' name='kkl' id='kkl' value='{$kkl}' size='3'>";
	echo "<input type='text' name='vvl' id='vvl' value='{$vvl}' size='5'></td>";
	echo "</tr>";

	$query = "SELECT group_concat(yhtio) AS yhtiot FROM yhtio";
	$yhtio_res = pupe_query($query);
	$yhtio_array = mysql_fetch_assoc($yhtio_res);

	$query = "SELECT nimi, yhtio FROM yhtio";
	$yhtio_res = pupe_query($query);

	$numrows = mysql_num_rows($yhtio_res);

	echo "<tr>";
	echo "<input type='hidden' name='yhtiot[]' value='default' />";
	echo "<th rowspan='{$numrows}'>",t("Valitse yhtiö"),"</th>";

	$i = 0;

	if (!isset($yhtiot)) $yhtiot = array();

	$chk = array_fill_keys($yhtiot, " checked") + array_fill_keys(explode(",", $yhtio_array['yhtiot']), '');

	while ($yhtio_row = mysql_fetch_assoc($yhtio_res)) {

		if ($i > 0) {
			echo "</tr><tr>";
		}

		if (count($yhtiot) < 2 and $yhtio_row['yhtio'] == $kukarow['yhtio']) {
			$chk[$yhtio_row['yhtio']] = ' checked';
		}

		echo "<td><input type='checkbox' name='yhtiot[]' value='{$yhtio_row['yhtio']}'{$chk[$yhtio_row['yhtio']]}/> {$yhtio_row['nimi']}</td>";
		$i++;
	}

	echo "</tr>";

	if (!isset($naytetaan_tulos)) $naytetaan_tulos = '';

	$sel = array_fill_keys(array($naytetaan_tulos), " selected") + array('daily' => '', 'weekly' => '', 'monthly' => '');

	echo "<tr><th>",t("Näytetään tulos"),"</th>";
	echo "<td><select name='naytetaan_tulos' id='naytetaan_tulos'>";
	echo "<option value='daily'{$sel['daily']}>",t("Päivittäin"),"</option>";
	echo "<option value='weekly'{$sel['weekly']}>",t("Viikottain"),"</option>";
	echo "<option value='monthly'{$sel['monthly']}>",t("Kuukausittain"),"</option>";
	echo "</select></td></tr>";

	echo "<tr><td colspan='2' class='back'><input type='submit' value='",t("Hae"),"' /></td></tr>";

	echo "</table>";
	echo "</td></tr></table>";
	echo "</form>";

	if (!isset($tee)) $tee = '';

	if ($tee == 'laske') {

		$ppa = (int) $ppa;
		$kka = (int) $kka;
		$vva = (int) $vva;
		$ppl = (int) $ppl;
		$kkl = (int) $kkl;
		$vvl = (int) $vvl;

		$query = "	SELECT ROUND(SUM(hinta) / 1000, 0) AS tilatut_eurot
					FROM tilausrivi
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tyyppi = 'L'
					AND laadittu >= '{$vva}-{$kka}-{$ppa} 00:00:00'
					#AND laadittu >= CURDATE() + ' 00:00:00'
					AND laadittu <= '{$vvl}-{$kkl}-{$ppl} 23:59:59'";
		$result = pupe_query($query);

		echo "<br />";
		echo "<table>";
		echo "<tr>";
		echo "<th>";
		echo $naytetaan_tulos == 'monthly' ? t("Kuukausi") : ($naytetaan_tulos == 'weekly' ? t("Viikko") : t("Päivä"));
		echo "</th>";
		echo "<th>",t("Tilatut eurot"),"</th>";
		echo "<th>",t("Tilatut Kate%"),"</th>";
		echo "<th>",t("Laskutetut eurot"),"</th>";
		echo "<th>",t("Laskutetut Kate%"),"</th>";
		echo "</tr>";

		while ($row = mysql_fetch_assoc($result)) {
			echo "<tr>";
			echo "<td></td>";
			echo "<td align='right'>{$row['tilatut_eurot']}</td>";
			echo "<td></td>";
			echo "<td align='right'></td>";
			echo "<td></td>";
			echo "</tr>";
		}

		echo "</table>";


	}

	require ("inc/footer.inc");