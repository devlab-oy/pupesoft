<?php

	require ("../inc/parametrit.inc");

	echo "<script type='text/javascript' language='javascript'>";
	require_once("inc/jquery.min.js");
	echo "</script>";

	echo "<script type='text/javascript' src='https://www.google.com/jsapi'></script>";

	echo "	<script type='text/javascript' charset='utf-8'>

				google.load('visualization', '1', {packages:['gauge']});
				// google.setOnLoadCallback(drawChart);

				var Gauge = (function() {
					var data;
					var options;
					var chart;

					return {
						init: function(args, opt) {
							data = new google.visualization.DataTable();
							options = opt;

							data.addColumn('string', 'Label');
							data.addColumn('number', 'Value');

							// data.addRows(Object.keys(args).length);
							data.addRows(1);

							var div_id;
							var _i = 0;

							for (var i in args) {
								var _x = 0;
								div_id = i;

								for (var x in args[i]) {
									data.setValue(_i, _x, args[i][x]);
									_x++;
								}
								_i++;
							}

							$('#chart_div').append('<div id=\''+div_id+'\'></div>');
							$('#'+div_id).css('float', 'left');
							var div = document.getElementById(div_id);
							chart = new google.visualization.Gauge(div);

							chart.draw(data, options);

							var body_bgcolor = $('body').css('background-color');
							$('#chart_div *').css({'background-color': body_bgcolor});
						},
						draw: function(value) {

							value = parseInt(value);

							// debug
							// value = 50000;
						
							if (parseInt((value / (value / 5))) > 4) {
						
								var force = parseInt(value / 80000);
								force = force < 2 ? 2 : force;
								force = 1 + '.' + force;
						
								var _dot120 = parseInt(value * force);
								data.setValue(0, 1, _dot120);
								chart.draw(data, options);
						
								var _dot90 = parseInt(value * 0.9);
								setTimeout(function(){data.setValue(0, 1, _dot90); chart.draw(data, options);}, 400);
						
								setTimeout(function(){data.setValue(0, 1, value); chart.draw(data, options);}, 800);
							}
							else {
								data.setValue(0, 1, value);
								chart.draw(data, options);
							}

							chart.draw(data, options);
						}
					}
				});

				$(document).ready(function() {

					setTimeout(function() {
						
						var gauge = new Gauge();
						var args = {
							tilatut: ['Tilatut', 0]
						}
					
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
					
						gauge.init(args, options);

						gauge.draw($('#tilatut_eurot').val());

						var gauge = new Gauge();
						var args = {
							kate: ['Kate', 0]
						}
					
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
					
						gauge.init(args, options);

						gauge.draw($('#tilatut_kate').val());

						var gauge = new Gauge();
						var args = {
							katepros: ['Kate%', 0]
						}

						var options = {	width: 800, 
										height: 220, 
										min: 0,
										max: 100,
										redFrom: 50, 
										redTo: 75, 
										greenFrom: 90, 
										greenTo: 100,
										yellowFrom: 75, 
										yellowTo: 90, 
										minorTicks: 5,
										majorTicks: ['0', '25', '50', '75', '100']};

						gauge.init(args, options);

						gauge.draw($('#tilatut_katepros').val());
					}, 1);

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
	echo "<table><tr>";
	echo "<td class='back'>";

	$query_ale_lisa = generoi_alekentta('M');

	// $query = "	SELECT ROUND(SUM(hinta), 0) AS tilatut_eurot
	// 			FROM tilausrivi
	// 			WHERE yhtio = '{$kukarow['yhtio']}'
	// 			AND tyyppi = 'L'
	// 			AND laadittu >= '2011-10-05 00:00:00'
	// 			#AND laadittu >= CURDATE() + ' 00:00:00'
	// 			AND laadittu <= CURDATE() + ' 23:59:59'";

	$query = "	SELECT round(if(tilausrivi.laskutettu!='',tilausrivi.rivihinta,(tilausrivi.hinta*(tilausrivi.varattu+tilausrivi.jt))*{$query_ale_lisa}/if('{$yhtiorow['alv_kasittely']}'='',(1+tilausrivi.alv/100),1)), 0) AS 'tilatut_eurot',
				round(if(tilausrivi.laskutettu!='', tilausrivi.kate, (tilausrivi.hinta*(tilausrivi.varattu+tilausrivi.jt))*{$query_ale_lisa}/if('{$yhtiorow['alv_kasittely']}'='',(1+tilausrivi.alv/100),1)-(tuote.kehahin*(tilausrivi.varattu+tilausrivi.jt))),'{$yhtiorow['hintapyoristys']}') AS 'tilatut_kate'
				FROM tilausrivi
				JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
				WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
				AND tilausrivi.tyyppi = 'L'
				AND tilausrivi.laadittu >= '2011-10-05 00:00:00'
				AND tilausrivi.laadittu <= CURDATE() + ' 23:59:59'";
	$result = pupe_query($query);

	$arr = array('tilatut_eurot' => 0, 'tilatut_kate' => 0);

	while ($row = mysql_fetch_assoc($result)) {
		if (!isset($arr['tilatut_eurot'])) $arr['tilatut_eurot'] = 0;
		if (!isset($arr['tilatut_kate'])) $arr['tilatut_kate'] = 0;

		$arr['tilatut_eurot'] += $row['tilatut_eurot'];
		$arr['tilatut_kate'] += $row['tilatut_kate'];
	}

	$tilatut_eurot = round($arr['tilatut_eurot'], 0);
	$tilatut_kate = round($arr['tilatut_kate'], 0);
	$tilatut_katepros = round($arr['tilatut_kate'] / $arr['tilatut_eurot'] * 100, 1);

	echo "<input type='hidden' id='tilatut_eurot' value='{$tilatut_eurot}' />";
	echo "<input type='hidden' id='tilatut_kate' value='{$tilatut_kate}' />";
	echo "<input type='hidden' id='tilatut_katepros' value='{$tilatut_katepros}' />";
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
	echo "</td>";
	echo "<td class='back'><div id='chart_div'></div></td>";
	echo "</tr></table>";
	echo "</form>";

	if (!isset($tee)) $tee = '';

	if (isset($yhtiot) and count($yhtiot) == 1) {
		echo "<font class='error'>",t("Et valinnut yhtiötä"),"!</font>";
		$tee = '';
	}

	if ($tee == 'laske') {

		// poistetaan default
		unset($yhtiot[0]);

		$query_yhtiot = implode("','", $yhtiot);

		$ppa = (int) $ppa;
		$kka = (int) $kka;
		$vva = (int) $vva;
		$ppl = (int) $ppl;
		$kkl = (int) $kkl;
		$vvl = (int) $vvl;

		if ($naytetaan_tulos == 'weekly') {
			$pvmlisa = "WEEK(SUBSTRING(tilausrivi.laadittu, 1, 10), 7)";
		}
		elseif ($naytetaan_tulos == 'monthly') {
			$pvmlisa = "MONTH(SUBSTRING(tilausrivi.laadittu, 1, 10))";
		}
		else {
			$pvmlisa = "SUBSTRING(tilausrivi.laadittu, 1, 10)";
		}

		$query = "	SELECT {$pvmlisa} AS 'pvm', 
					round((tilausrivi.hinta*(tilausrivi.varattu+tilausrivi.jt))*{$query_ale_lisa}/if('{$yhtiorow['alv_kasittely']}'='',(1+tilausrivi.alv/100),1)-(tuote.kehahin*(tilausrivi.varattu+tilausrivi.jt)),'{$yhtiorow['hintapyoristys']}') AS 'tilatut_kate',
					(tilausrivi.hinta*(tilausrivi.varattu+tilausrivi.jt))*{$query_ale_lisa}/if('{$yhtiorow['alv_kasittely']}'='',(1+tilausrivi.alv/100),1) AS tilatut_eurot
					FROM tilausrivi
					JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
					WHERE tilausrivi.yhtio IN ('{$query_yhtiot}')
					AND tilausrivi.tyyppi = 'L'
					AND tilausrivi.laadittu >= '{$vva}-{$kka}-{$ppa} 00:00:00'
					AND tilausrivi.laadittu <= '{$vvl}-{$kkl}-{$ppl} 23:59:59'
					AND tilausrivi.laskutettu = ''
					ORDER BY tilausrivi.laadittu";
		// echo "<pre>",str_replace("\t", "", $query),"</pre>";
		$result = pupe_query($query);

		// $kateprosentti = round($row["kate"] / $row["summa"] * 100, 2);
		// Rivikeississä: $kateprosentti = round($row["kate"] / $row["rivihinta"] * 100, 2);
		// if(tilausrivi.laskutettu!='',tilausrivi.kate,round((tilausrivi.hinta*(tilausrivi.varattu+tilausrivi.jt))*{$query_ale_lisa}/if('$yhtiorow[alv_kasittely]'='',(1+tilausrivi.alv/100),1)-(tuote.kehahin*(tilausrivi.varattu+tilausrivi.jt)),'$yhtiorow[hintapyoristys]')) AS 'kate'

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

		$yhteensa = array(
			'tilatut_eurot' => 0,
			'laskutetut_eurot' => 0,
		);

		$arr = array();

		while ($row = mysql_fetch_assoc($result)) {
			if (!isset($arr[$row['pvm']]['tilatut_eurot'])) $arr[$row['pvm']]['tilatut_eurot'] = 0;
			if (!isset($arr[$row['pvm']]['tilatut_kate'])) $arr[$row['pvm']]['tilatut_kate'] = 0;

			$arr[$row['pvm']]['tilatut_eurot'] += $row['tilatut_eurot'];
			$arr[$row['pvm']]['tilatut_kate'] += $row['tilatut_kate'];
		}

		// echo "<pre>",var_dump($arr),"</pre>";

		$query = "	SELECT {$pvmlisa} AS 'pvm', 
					tilausrivi.kate AS 'laskutetut_kate',
					tilausrivi.rivihinta AS 'laskutetut_eurot'
					FROM tilausrivi
					JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
					WHERE tilausrivi.yhtio IN ('{$query_yhtiot}')
					AND tilausrivi.tyyppi = 'L'
					AND tilausrivi.laadittu >= '{$vva}-{$kka}-{$ppa} 00:00:00'
					AND tilausrivi.laadittu <= '{$vvl}-{$kkl}-{$ppl} 23:59:59'
					AND tilausrivi.laskutettu != ''
					ORDER BY tilausrivi.laadittu";
		// echo "<pre>",str_replace("\t", "", $query),"</pre>";
		$result = pupe_query($query);

		while ($row = mysql_fetch_assoc($result)) {
			if (!isset($arr[$row['pvm']]['laskutetut_eurot'])) $arr[$row['pvm']]['laskutetut_eurot'] = 0;
			if (!isset($arr[$row['pvm']]['laskutetut_kate'])) $arr[$row['pvm']]['laskutetut_kate'] = 0;

			$arr[$row['pvm']]['laskutetut_eurot'] += $row['laskutetut_eurot'];
			$arr[$row['pvm']]['laskutetut_kate'] += $row['laskutetut_kate'];
		}

		foreach ($arr as $pvm => $arvot) {

			if ($naytetaan_tulos == 'daily') {
				list($v, $k, $p) = explode("-", $pvm);
				$pvm = $p.$k;
			}

			$tilatut_katepros = round($arvot['tilatut_kate'] / $arvot['tilatut_eurot'] * 100, 1);

			$arvot['tilatut_eurot'] = round($arvot['tilatut_eurot'] / 1000, 0);

			$laskutetut_katepros = round($arvot['laskutetut_kate'] / $arvot['laskutetut_eurot'] * 100, 1);

			$arvot['laskutetut_eurot'] = round($arvot['laskutetut_eurot'] / 1000, 0);

			$arvot['tilatut_eurot'] = $arvot['tilatut_eurot'] + $arvot['laskutetut_eurot'];
			$tilatut_katepros = round(($tilatut_katepros + $laskutetut_katepros) / 2, 1);

			$yhteensa['tilatut_eurot'] += $arvot['tilatut_eurot'];
			$yhteensa['laskutetut_eurot'] += $arvot['laskutetut_eurot'];

			echo "<tr>";
			echo "<td align='right'>{$pvm}</td>";
			echo "<td align='right'>{$arvot['tilatut_eurot']}</td>";
			echo "<td align='right'>{$tilatut_katepros}</td>";
			echo "<td align='right'>{$arvot['laskutetut_eurot']}</td>";
			echo "<td align='right'>{$laskutetut_katepros}</td>";
			echo "</tr>";
		}

		echo "<tr>";
		echo "<th>",t("Yhteensä"),"</th>";
		echo "<td align='right'>{$yhteensa['tilatut_eurot']}</td>";
		echo "<td align='right'></td>";
		echo "<td align='right'>{$yhteensa['laskutetut_eurot']}</td>";
		echo "<td align='right'></td>";
		echo "</tr>";

		echo "</table>";


	}

	require ("inc/footer.inc");