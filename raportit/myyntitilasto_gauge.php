<?php

	//* Tämä skripti käyttää slave-tietokantapalvelinta *//
	$useslave = 1;

	require ("../inc/parametrit.inc");

	echo "<script type='text/javascript' language='javascript'>";
	require_once("inc/jquery.min.js");
	echo "</script>";

	gauge();

	echo "	<script type='text/javascript' charset='utf-8'>

				$(document).ready(function() {

					setTimeout(function() {

						var gauge = new Gauge();
						var args = {
							tilatut: ['k".$yhtiorow["valkoodi"]."', 0]
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
										majorTicks: ['0', '50', '100', '150', '200', '250', '300', '350', '400']};

						gauge.init(args, options);

						gauge.draw($('#tilatut_eurot').val(), options.max);

						var gauge = new Gauge();
						var args = {
							kate: ['Rivit', 0]
						}

						var options = {	width: 800,
										height: 220,
										min: 0,
										max: 8000,
										redFrom: 4000,
										redTo: 6000,
										yellowFrom: 6000,
										yellowTo: 7000,
										greenFrom: 7000,
										greenTo: 8000,
										minorTicks: 5,
										majorTicks: ['0', '1', '2', '3', '4', '5', '6', '7', '8']};

						gauge.init(args, options);

						gauge.draw($('#toimitetut_rivit').val(), options.max);

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
										majorTicks: ['0', '10', '20', '30', '40', '50', '60', '70', '80', '90', '100']};

						gauge.init(args, options);

						gauge.draw($('#tilatut_katepros').val(), options.max);
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
	echo "<td class='back'><div id='chart_div'></div></td>";
	echo "</tr><tr>";
	echo "<td class='back'>";

	$query_ale_lisa = generoi_alekentta('M');

	$alku = date("Y-m-d")." 00:00:00";
	$lopu = date("Y-m-d")." 23:59:59";

	$query = "	SELECT
				round(sum(round(if(tilausrivi.laskutettu!='',tilausrivi.rivihinta,(tilausrivi.hinta*(tilausrivi.varattu+tilausrivi.jt))*{$query_ale_lisa}/if('{$yhtiorow['alv_kasittely']}'='',(1+tilausrivi.alv/100),1)), 0)), 0) AS 'tilatut_eurot',
				round(sum(round(if(tilausrivi.laskutettu!='', tilausrivi.kate, (tilausrivi.hinta*(tilausrivi.varattu+tilausrivi.jt))*{$query_ale_lisa}/if('{$yhtiorow['alv_kasittely']}'='',(1+tilausrivi.alv/100),1)-(tuote.kehahin*(tilausrivi.varattu+tilausrivi.jt))),'{$yhtiorow['hintapyoristys']}')), 0) AS 'tilatut_kate',
				round(sum(if(tilausrivi.toimitettu!='', 1, 0)), 0) AS 'toimitetut_rivit'
				FROM tilausrivi
				JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno AND tuote.tuotetyyppi != 'N')
				WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
				AND tilausrivi.tyyppi = 'L'
				AND tilausrivi.laadittu >= '$alku'
				AND tilausrivi.laadittu <= '$lopu'";
	$result = pupe_query($query);
	$row = mysql_fetch_assoc($result);

	echo "<input type='hidden' id='tilatut_eurot' value='{$row['tilatut_eurot']}' />";
	echo "<input type='hidden' id='toimitetut_rivit' value='{$row['toimitetut_rivit']}' />";
	echo "<input type='hidden' id='tilatut_katepros' value='".round($row['tilatut_kate'] / $row['tilatut_eurot'] * 100, 1)."' />";
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

	$query = "	SELECT group_concat(yhtio) AS yhtiot
				FROM yhtio
				WHERE konserni = '$yhtiorow[konserni]'
				and konserni != ''";
	$yhtio_res = pupe_query($query);
	$yhtio_array = mysql_fetch_assoc($yhtio_res);

	$query = "	SELECT nimi, yhtio
				FROM yhtio
				WHERE konserni = '$yhtiorow[konserni]'
				and konserni != ''";
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

	if (isset($yhtiot) and count($yhtiot) == 1) {
		echo "<font class='error'>",t("Et valinnut yhtiötä"),"!</font>";
		$tee = '';
	}

	if ((isset($ppa) and (int) $ppa == 0) or (isset($kka) and (int) $kka == 0) or (isset($vva) and (int) $vva == 0) or (isset($ppl) and (int) $ppl == 0) or (isset($kkl) and (int) $kkl == 0) or (isset($vvl) and (int) $vvl == 0)) {
		echo "<font class='error'>",t("Päivämäärässä on virhe"),"!</font>";
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
					if(tilausrivi.laskutettu != '', tilausrivi.kate, (tilausrivi.hinta*(tilausrivi.varattu+tilausrivi.jt))*{$query_ale_lisa}/if('{$yhtiorow['alv_kasittely']}'='',(1+tilausrivi.alv/100),1)-(tuote.kehahin*(tilausrivi.varattu+tilausrivi.jt))) AS 'tilatut_kate',
					if(tilausrivi.laskutettu != '', tilausrivi.rivihinta, (tilausrivi.hinta*(tilausrivi.varattu+tilausrivi.jt))*{$query_ale_lisa}/if('{$yhtiorow['alv_kasittely']}'='',(1+tilausrivi.alv/100),1)) AS tilatut_eurot
					FROM tilausrivi
					JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno AND tuote.tuotetyyppi != 'N')
					JOIN lasku on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus)
					JOIN asiakas use index (PRIMARY) ON (asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus and asiakas.myynninseuranta = '')
					WHERE tilausrivi.yhtio IN ('{$query_yhtiot}')
					AND tilausrivi.tyyppi = 'L'
					AND tilausrivi.laadittu >= '{$vva}-{$kka}-{$ppa} 00:00:00'
					AND tilausrivi.laadittu <= '{$vvl}-{$kkl}-{$ppl} 23:59:59'
					ORDER BY tilausrivi.laadittu";
		$result = pupe_query($query);

		echo "<br />";
		echo "<table>";
		echo "<tr>";
		echo "<th>";
		echo $naytetaan_tulos == 'monthly' ? t("Kuukausi") : ($naytetaan_tulos == 'weekly' ? t("Viikko") : t("Päivä"));
		echo "</th>";
		echo "<th>",t("Tilatut")," k{$yhtiorow["valkoodi"]}</th>";
		echo "<th>",t("Tilatut Kate%"),"</th>";
		echo "<th>",t("Laskutetut")," k{$yhtiorow["valkoodi"]}</th>";
		echo "<th>",t("Laskutetut Kate%"),"</th>";
		echo "</tr>";

		$yhteensa = array(
			'tilatut_eurot' => 0,
			'tilatut_kate' => 0,
			'laskutetut_eurot' => 0,
			'laskutetut_kate' => 0,
		);

		$arr = array();

		while ($row = mysql_fetch_assoc($result)) {
			if (!isset($arr[$row['pvm']]['tilatut_eurot'])) $arr[$row['pvm']]['tilatut_eurot'] = 0;
			if (!isset($arr[$row['pvm']]['tilatut_kate'])) $arr[$row['pvm']]['tilatut_kate'] = 0;

			$arr[$row['pvm']]['tilatut_eurot'] += $row['tilatut_eurot'];
			$arr[$row['pvm']]['tilatut_kate'] += $row['tilatut_kate'];
		}

		if ($naytetaan_tulos == 'weekly') {
			$pvmlisa = "WEEK(SUBSTRING(tilausrivi.laskutettuaika, 1, 10), 7)";
		}
		elseif ($naytetaan_tulos == 'monthly') {
			$pvmlisa = "MONTH(SUBSTRING(tilausrivi.laskutettuaika, 1, 10))";
		}
		else {
			$pvmlisa = "SUBSTRING(tilausrivi.laskutettuaika, 1, 10)";
		}

		$query = "	SELECT {$pvmlisa} AS 'pvm',
					tilausrivi.kate AS 'laskutetut_kate',
					tilausrivi.rivihinta AS 'laskutetut_eurot'
					FROM tilausrivi
					JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno AND tuote.tuotetyyppi != 'N')
					JOIN lasku on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus)
					JOIN asiakas use index (PRIMARY) ON (asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus and asiakas.myynninseuranta = '')
					WHERE tilausrivi.yhtio IN ('{$query_yhtiot}')
					AND tilausrivi.tyyppi = 'L'
					AND tilausrivi.laskutettuaika >= '{$vva}-{$kka}-{$ppa}'
					AND tilausrivi.laskutettuaika <= '{$vvl}-{$kkl}-{$ppl}'
					AND tilausrivi.laskutettu != ''
					ORDER BY tilausrivi.laadittu";
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
				$pvm = $p.".".$k;
			}

			$yhteensa['tilatut_eurot'] += $arvot['tilatut_eurot'];
			$yhteensa['tilatut_kate'] += $arvot['tilatut_kate'];
			$yhteensa['laskutetut_eurot'] += $arvot['laskutetut_eurot'];
			$yhteensa['laskutetut_kate'] += $arvot['laskutetut_kate'];

			$tilatut_katepros = round($arvot['tilatut_kate'] / $arvot['tilatut_eurot'] * 100, 1);
			$laskutetut_katepros = round($arvot['laskutetut_kate'] / $arvot['laskutetut_eurot'] * 100, 1);

			$arvot['tilatut_eurot'] = round($arvot['tilatut_eurot'] / 1000, 0);
			$arvot['laskutetut_eurot'] = round($arvot['laskutetut_eurot'] / 1000, 0);

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
		echo "<td align='right'>",round($yhteensa['tilatut_eurot'] / 1000, 0),"</td>";
		echo "<td align='right'>",round($yhteensa['tilatut_kate'] / $yhteensa['tilatut_eurot'] * 100, 1),"</td>";
		echo "<td align='right'>",round($yhteensa['laskutetut_eurot'] / 1000, 0),"</td>";
		echo "<td align='right'>",(round($yhteensa['laskutetut_kate'] / $yhteensa['laskutetut_eurot'] * 100, 1)),"</td>";
		echo "</tr>";

		echo "</table>";

	}

	require ("inc/footer.inc");

?>