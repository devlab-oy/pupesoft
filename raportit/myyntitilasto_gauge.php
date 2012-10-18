<?php

	//* Tämä skripti käyttää slave-tietokantapalvelinta *//
	$useslave = 1;

	require ("../inc/parametrit.inc");

	ini_set("memory_limit", "5G");

	gauge();

	echo "	<script type='text/javascript' charset='utf-8'>

				$(document).ready(function() {

					$('td.toggleable, th.toggleable').toggle(
						function() {
							var id = $(this).attr('id');
							var child = $('.'+id);

							if (!$(child).is(':visible')) {
								$('#img_'+id).attr('src', '{$palvelin2}pics/lullacons/bullet-arrow-down.png');
								child.show();
							}
							else {
								$('#img_'+id).attr('src', '{$palvelin2}pics/lullacons/bullet-arrow-right.png');
								child.hide();
							}
						},
						function() {
							var id = $(this).attr('id');
							var child = $('.'+id);

							if (!$(child).is(':visible')) {
								$('#img_'+id).attr('src', '{$palvelin2}pics/lullacons/bullet-arrow-down.png');
								child.show();
							}
							else {

								if ($(child).hasClass('osasto')) {
									$('tr.try:visible').hide();
								}

								$('#img_'+id).attr('src', '{$palvelin2}pics/lullacons/bullet-arrow-right.png');
								child.hide();
							}
						}
					);

					setTimeout(function() {

						var gauge = new Gauge();
						var args = {
							tilatut: ['k".$yhtiorow["valkoodi"]."', 0]
						}

						var options = {	forceIFrame: false,
										width: 800,
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
										majorTicks: ['0', '50', '100', '150', '200', '250', '300', '350', '400'],
										animation: {
											easing: 'out',
											duration: 4000
										}};

						gauge.init(args, options);

						draw_options = {
							max: options.max,
							type: 'custom_parseint'
						}

						if (!isNaN($('#tilatut_eurot').val()) && $('#tilatut_eurot').val() != '') gauge.draw($('#tilatut_eurot').val(), draw_options);

						var gauge = new Gauge();
						var args = {
							kate: ['Rivit', 0]
						}

						var options = {	forceIFrame: false,
										width: 800,
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
										majorTicks: ['0', '1', '2', '3', '4', '5', '6', '7', '8'],
										animation: {
											easing: 'out',
											duration: 4000
										}};

						gauge.init(args, options);

						draw_options = {
							max: options.max,
							type: 'custom_parseint'
						}

						if (!isNaN($('#toimitetut_rivit').val()) && $('#toimitetut_rivit').val() != '') gauge.draw($('#toimitetut_rivit').val(), draw_options);

						var gauge = new Gauge();
						var args = {
							katepros: ['Kate%', 0]
						}

						var options = {	forceIFrame: false,
										width: 800,
										height: 220,
										min: 0,
										max: 50,
										redFrom: 25,
										redTo: 30,
										greenFrom: 40,
										greenTo: 50,
										yellowFrom: 30,
										yellowTo: 40,
										minorTicks: 2,
										majorTicks: ['0', '5', '10', '15', '20', '25', '30', '35', '40', '45', '50'],
										animation: {
											easing: 'out',
											duration: 4000
										}};

						gauge.init(args, options);

						draw_options = {
							max: options.max,
							type: 'custom_parsefloat'
						}

						if (!isNaN($('#tilatut_katepros').val()) && $('#tilatut_katepros').val() != '') gauge.draw($('#tilatut_katepros').val(), draw_options);
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

	echo "<form method='post'>";
	echo "<table><tr>";
	echo "<td class='back'><div id='chart_div'></div></td>";
	echo "</tr><tr>";
	echo "<td class='back'>";

	$query_ale_lisa = generoi_alekentta('M');

	$alku = date("Y-m-d")." 00:00:00";
	$lopu = date("Y-m-d")." 23:59:59";

	$query = "	SELECT
				round(sum(if(tilausrivi.laskutettu!='',tilausrivi.rivihinta,(tilausrivi.hinta*(tilausrivi.varattu+tilausrivi.jt))*{$query_ale_lisa}/if('{$yhtiorow['alv_kasittely']}'='',(1+tilausrivi.alv/100),1))), 0) AS 'tilatut_eurot',
				round(sum(if(tilausrivi.laskutettu!='', tilausrivi.kate, (tilausrivi.hinta*(tilausrivi.varattu+tilausrivi.jt))*{$query_ale_lisa}/if('{$yhtiorow['alv_kasittely']}'='',(1+tilausrivi.alv/100),1)-(tuote.kehahin*(tilausrivi.varattu+tilausrivi.jt)))), 0) AS 'tilatut_kate',
				sum(if(tilausrivi.toimitettu!='', 1, 0)) AS 'toimitetut_rivit'
				FROM tilausrivi
				JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno AND tuote.tuotetyyppi != 'N')
				JOIN lasku on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus)
				JOIN asiakas use index (PRIMARY) ON (asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus and asiakas.myynninseuranta = '')
				WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
				AND tilausrivi.tyyppi = 'L'
				AND tilausrivi.laadittu >= '$alku'
				AND tilausrivi.laadittu <= '$lopu'";
	$result = pupe_query($query);
	$row = mysql_fetch_assoc($result);

	echo "<input type='hidden' id='tilatut_eurot' value='{$row['tilatut_eurot']}' />";
	echo "<input type='hidden' id='toimitetut_rivit' value='{$row['toimitetut_rivit']}' />";
	echo "<input type='hidden' id='tilatut_katepros' value='",($row['tilatut_eurot'] != 0 ? round($row['tilatut_kate'] / $row['tilatut_eurot'] * 100, 1) : 0),"' />";
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

	if ($yhtiorow['konserni'] != "") {
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
		echo "<th rowspan='{$numrows}'>",t("Valitse yhtiö"),"</th>";

		$i = 0;

		while ($yhtio_row = mysql_fetch_assoc($yhtio_res)) {

			if ($i > 0) {
				echo "</tr><tr>";
			}

			$chk = "";

			if (!isset($yhtiot) and $yhtio_row['yhtio'] == $kukarow['yhtio']) {
				$chk = "CHECKED";
			}

			if (isset($yhtiot[$yhtio_row['yhtio']]) and $yhtiot[$yhtio_row['yhtio']] != "") {
				$chk = "CHECKED";
			}

			echo "<td><input type='checkbox' name='yhtiot[{$yhtio_row['yhtio']}]' value='{$yhtio_row['yhtio']}' $chk> {$yhtio_row['nimi']}</td>";
			$i++;
		}

		echo "</tr>";
	}
	else {
		$yhtiot = array($kukarow['yhtio']);
	}

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

	if ($tee == 'laske' and (!isset($yhtiot) or count($yhtiot) == 0)) {
		echo "<font class='error'>",t("Et valinnut yhtiötä"),"!</font>";
		$tee = '';
	}

	if ((isset($ppa) and (int) $ppa == 0) or (isset($kka) and (int) $kka == 0) or (isset($vva) and (int) $vva == 0) or (isset($ppl) and (int) $ppl == 0) or (isset($kkl) and (int) $kkl == 0) or (isset($vvl) and (int) $vvl == 0)) {
		echo "<font class='error'>",t("Päivämäärässä on virhe"),"!</font>";
		$tee = '';
	}

	if ($tee == 'laske') {

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
					if(tilausrivi.laskutettu != '', tilausrivi.rivihinta, (tilausrivi.hinta*(tilausrivi.varattu+tilausrivi.jt))*{$query_ale_lisa}/if('{$yhtiorow['alv_kasittely']}'='',(1+tilausrivi.alv/100),1)) AS tilatut_eurot,
					kustannuspaikka.nimi AS kustannuspaikka,
					tuote.osasto, tuote.try
					FROM tilausrivi
					JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno AND tuote.tuotetyyppi != 'N')
					JOIN lasku on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus)
					JOIN asiakas use index (PRIMARY) ON (asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus and asiakas.myynninseuranta = '')
					LEFT JOIN kustannuspaikka ON (kustannuspaikka.yhtio = tilausrivi.yhtio AND kustannuspaikka.tunnus = asiakas.kustannuspaikka)
					WHERE tilausrivi.yhtio IN ('{$query_yhtiot}')
					AND tilausrivi.tyyppi = 'L'
					AND tilausrivi.laadittu >= '{$vva}-{$kka}-{$ppa} 00:00:00'
					AND tilausrivi.laadittu <= '{$vvl}-{$kkl}-{$ppl} 23:59:59'
					ORDER BY tilausrivi.laadittu";
		$result = pupe_query($query);

		echo "<br />";
		echo "<table>";
		echo "<tr>";
		echo "<th>",t("Kustp"),"</th>";
		echo "<th>",t("Osasto"),"<br />",t("Try"),"</th>";
		echo "<th>";
		echo $naytetaan_tulos == 'monthly' ? t("Kuukausi") : ($naytetaan_tulos == 'weekly' ? t("Viikko") : t("Päivä"));
		echo "</th>";
		echo "<th>",t("Tilatut")," k{$yhtiorow["valkoodi"]}</th>";
		echo "<th>",t("Tilatut Kate%"),"</th>";
		echo "<th>",t("Tilatut Rivit"),"</th>";
		echo "<th>",t("Laskutetut")," k{$yhtiorow["valkoodi"]}</th>";
		echo "<th>",t("Laskutetut Kate%"),"</th>";
		echo "<th>",t("Laskutetut Rivit"),"</th>";
		echo "</tr>";

		$yhteensa = array(
			'tilatut_eurot' => 0,
			'tilatut_kate' => 0,
			'tilatut_rivit' => 0,
			'laskutetut_eurot' => 0,
			'laskutetut_kate' => 0,
			'laskutetut_rivit' => 0,
		);

		$arr = $arr_kustp = $arr_kustp_osasto = $arr_kustp_osasto_try = $arr_osasto = $arr_try = $yhteensa_kustp = $yhteensa_osasto = $yhteensa_try = $yhteensa_kustp_osasto = $yhteensa_kustp_osasto_try = array();

		while ($row = mysql_fetch_assoc($result)) {

			$pvm = $row['pvm'];
			$kustp = $row['kustannuspaikka'];
			$osasto = $row['osasto'];
			$try = $row['try'];

			if (!isset($arr[$pvm]['tilatut_eurot'])) 	$arr[$pvm]['tilatut_eurot'] = 0;
			if (!isset($arr[$pvm]['tilatut_kate'])) 	$arr[$pvm]['tilatut_kate'] = 0;
			if (!isset($arr[$pvm]['tilatut_rivit'])) 	$arr[$pvm]['tilatut_rivit'] = 0;

			if (!isset($arr_kustp[$pvm][$kustp]['tilatut_eurot'])) 	$arr_kustp[$pvm][$kustp]['tilatut_eurot'] = 0;
			if (!isset($arr_kustp[$pvm][$kustp]['tilatut_kate'])) 	$arr_kustp[$pvm][$kustp]['tilatut_kate'] = 0;
			if (!isset($arr_kustp[$pvm][$kustp]['tilatut_rivit'])) 	$arr_kustp[$pvm][$kustp]['tilatut_rivit'] = 0;

			if (!isset($arr_kustp_osasto[$pvm][$kustp][$osasto]['tilatut_eurot'])) $arr_kustp_osasto[$pvm][$kustp][$osasto]['tilatut_eurot'] = 0;
			if (!isset($arr_kustp_osasto[$pvm][$kustp][$osasto]['tilatut_kate']))  $arr_kustp_osasto[$pvm][$kustp][$osasto]['tilatut_kate'] = 0;
			if (!isset($arr_kustp_osasto[$pvm][$kustp][$osasto]['tilatut_rivit'])) $arr_kustp_osasto[$pvm][$kustp][$osasto]['tilatut_rivit'] = 0;

			if (!isset($arr_kustp_osasto_try[$pvm][$kustp][$osasto][$try]['tilatut_eurot'])) 	$arr_kustp_osasto_try[$pvm][$kustp][$osasto][$try]['tilatut_eurot'] = 0;
			if (!isset($arr_kustp_osasto_try[$pvm][$kustp][$osasto][$try]['tilatut_kate'])) 	$arr_kustp_osasto_try[$pvm][$kustp][$osasto][$try]['tilatut_kate'] = 0;
			if (!isset($arr_kustp_osasto_try[$pvm][$kustp][$osasto][$try]['tilatut_rivit'])) 	$arr_kustp_osasto_try[$pvm][$kustp][$osasto][$try]['tilatut_rivit'] = 0;

			if (!isset($arr_osasto[$pvm][$osasto]['tilatut_eurot'])) $arr_osasto[$pvm][$osasto]['tilatut_eurot'] = 0;
			if (!isset($arr_osasto[$pvm][$osasto]['tilatut_kate'])) $arr_osasto[$pvm][$osasto]['tilatut_kate'] = 0;
			if (!isset($arr_osasto[$pvm][$osasto]['tilatut_rivit'])) $arr_osasto[$pvm][$osasto]['tilatut_rivit'] = 0;

			if (!isset($arr_try[$pvm][$osasto][$try]['tilatut_eurot'])) $arr_try[$pvm][$osasto][$try]['tilatut_eurot'] = 0;
			if (!isset($arr_try[$pvm][$osasto][$try]['tilatut_kate'])) $arr_try[$pvm][$osasto][$try]['tilatut_kate'] = 0;
			if (!isset($arr_try[$pvm][$osasto][$try]['tilatut_rivit'])) $arr_try[$pvm][$osasto][$try]['tilatut_rivit'] = 0;

			$arr[$pvm]['tilatut_eurot'] += $row['tilatut_eurot'];
			$arr[$pvm]['tilatut_kate'] += $row['tilatut_kate'];
			$arr[$pvm]['tilatut_rivit']++;

			$arr_kustp[$pvm][$kustp]['tilatut_eurot'] += $row['tilatut_eurot'];
			$arr_kustp[$pvm][$kustp]['tilatut_kate'] += $row['tilatut_kate'];
			$arr_kustp[$pvm][$kustp]['tilatut_rivit']++;

			$arr_kustp_osasto[$pvm][$kustp][$osasto]['tilatut_eurot'] += $row['tilatut_eurot'];
			$arr_kustp_osasto[$pvm][$kustp][$osasto]['tilatut_kate'] += $row['tilatut_kate'];
			$arr_kustp_osasto[$pvm][$kustp][$osasto]['tilatut_rivit']++;

			$arr_kustp_osasto_try[$pvm][$kustp][$osasto][$try]['tilatut_eurot'] += $row['tilatut_eurot'];
			$arr_kustp_osasto_try[$pvm][$kustp][$osasto][$try]['tilatut_kate'] += $row['tilatut_kate'];
			$arr_kustp_osasto_try[$pvm][$kustp][$osasto][$try]['tilatut_rivit']++;

			$arr_osasto[$pvm][$osasto]['tilatut_eurot'] += $row['tilatut_eurot'];
			$arr_osasto[$pvm][$osasto]['tilatut_kate'] += $row['tilatut_kate'];
			$arr_osasto[$pvm][$osasto]['tilatut_rivit']++;

			$arr_try[$pvm][$osasto][$try]['tilatut_eurot'] += $row['tilatut_eurot'];
			$arr_try[$pvm][$osasto][$try]['tilatut_kate'] += $row['tilatut_kate'];
			$arr_try[$pvm][$osasto][$try]['tilatut_rivit']++;
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
					tilausrivi.rivihinta AS 'laskutetut_eurot',
					kustannuspaikka.nimi AS kustannuspaikka,
					tuote.osasto, tuote.try
					FROM tilausrivi
					JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno AND tuote.tuotetyyppi != 'N')
					JOIN lasku on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus)
					JOIN asiakas use index (PRIMARY) ON (asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus and asiakas.myynninseuranta = '')
					LEFT JOIN kustannuspaikka ON (kustannuspaikka.yhtio = tilausrivi.yhtio AND kustannuspaikka.tunnus = asiakas.kustannuspaikka)
					WHERE tilausrivi.yhtio IN ('{$query_yhtiot}')
					AND tilausrivi.tyyppi = 'L'
					AND tilausrivi.laskutettuaika >= '{$vva}-{$kka}-{$ppa}'
					AND tilausrivi.laskutettuaika <= '{$vvl}-{$kkl}-{$ppl}'
					AND tilausrivi.laskutettu != ''
					ORDER BY tilausrivi.laadittu";
		$result = pupe_query($query);

		while ($row = mysql_fetch_assoc($result)) {

			$pvm = $row['pvm'];
			$kustp = $row['kustannuspaikka'];
			$osasto = $row['osasto'];
			$try = $row['try'];

			if (!isset($arr[$pvm]['laskutetut_eurot'])) $arr[$pvm]['laskutetut_eurot'] = 0;
			if (!isset($arr[$pvm]['laskutetut_kate'])) $arr[$pvm]['laskutetut_kate'] = 0;
			if (!isset($arr[$pvm]['laskutetut_rivit'])) $arr[$pvm]['laskutetut_rivit'] = 0;

			if (!isset($arr_kustp[$pvm][$kustp]['laskutetut_eurot'])) $arr_kustp[$pvm][$kustp]['laskutetut_eurot'] = 0;
			if (!isset($arr_kustp[$pvm][$kustp]['laskutetut_kate'])) $arr_kustp[$pvm][$kustp]['laskutetut_kate'] = 0;
			if (!isset($arr_kustp[$pvm][$kustp]['laskutetut_rivit'])) $arr_kustp[$pvm][$kustp]['laskutetut_rivit'] = 0;

			if (!isset($arr_kustp_osasto[$pvm][$kustp][$osasto]['laskutetut_eurot'])) $arr_kustp_osasto[$pvm][$kustp][$osasto]['laskutetut_eurot'] = 0;
			if (!isset($arr_kustp_osasto[$pvm][$kustp][$osasto]['laskutetut_kate']))  $arr_kustp_osasto[$pvm][$kustp][$osasto]['laskutetut_kate'] = 0;
			if (!isset($arr_kustp_osasto[$pvm][$kustp][$osasto]['laskutetut_rivit'])) $arr_kustp_osasto[$pvm][$kustp][$osasto]['laskutetut_rivit'] = 0;

			if (!isset($arr_kustp_osasto_try[$pvm][$kustp][$osasto][$try]['laskutetut_eurot'])) $arr_kustp_osasto_try[$pvm][$kustp][$osasto][$try]['laskutetut_eurot'] = 0;
			if (!isset($arr_kustp_osasto_try[$pvm][$kustp][$osasto][$try]['laskutetut_kate'])) 	$arr_kustp_osasto_try[$pvm][$kustp][$osasto][$try]['laskutetut_kate'] = 0;
			if (!isset($arr_kustp_osasto_try[$pvm][$kustp][$osasto][$try]['laskutetut_rivit'])) $arr_kustp_osasto_try[$pvm][$kustp][$osasto][$try]['laskutetut_rivit'] = 0;

			if (!isset($arr_osasto[$pvm][$osasto]['laskutetut_eurot'])) $arr_osasto[$pvm][$osasto]['laskutetut_eurot'] = 0;
			if (!isset($arr_osasto[$pvm][$osasto]['laskutetut_kate'])) $arr_osasto[$pvm][$osasto]['laskutetut_kate'] = 0;
			if (!isset($arr_osasto[$pvm][$osasto]['laskutetut_rivit'])) $arr_osasto[$pvm][$osasto]['laskutetut_rivit'] = 0;

			if (!isset($arr_try[$pvm][$osasto][$try]['laskutetut_eurot'])) $arr_try[$pvm][$osasto][$try]['laskutetut_eurot'] = 0;
			if (!isset($arr_try[$pvm][$osasto][$try]['laskutetut_kate'])) $arr_try[$pvm][$osasto][$try]['laskutetut_kate'] = 0;
			if (!isset($arr_try[$pvm][$osasto][$try]['laskutetut_rivit'])) $arr_try[$pvm][$osasto][$try]['laskutetut_rivit'] = 0;

			$arr[$pvm]['laskutetut_eurot'] += $row['laskutetut_eurot'];
			$arr[$pvm]['laskutetut_kate'] += $row['laskutetut_kate'];
			$arr[$pvm]['laskutetut_rivit']++;

			$arr_kustp[$pvm][$kustp]['laskutetut_eurot'] += $row['laskutetut_eurot'];
			$arr_kustp[$pvm][$kustp]['laskutetut_kate'] += $row['laskutetut_kate'];
			$arr_kustp[$pvm][$kustp]['laskutetut_rivit']++;

			$arr_kustp_osasto[$pvm][$kustp][$osasto]['laskutetut_eurot'] += $row['laskutetut_eurot'];
			$arr_kustp_osasto[$pvm][$kustp][$osasto]['laskutetut_kate'] += $row['laskutetut_kate'];
			$arr_kustp_osasto[$pvm][$kustp][$osasto]['laskutetut_rivit']++;

			$arr_kustp_osasto_try[$pvm][$kustp][$osasto][$try]['laskutetut_eurot'] += $row['laskutetut_eurot'];
			$arr_kustp_osasto_try[$pvm][$kustp][$osasto][$try]['laskutetut_kate'] += $row['laskutetut_kate'];
			$arr_kustp_osasto_try[$pvm][$kustp][$osasto][$try]['laskutetut_rivit']++;

			$arr_osasto[$pvm][$osasto]['laskutetut_eurot'] += $row['laskutetut_eurot'];
			$arr_osasto[$pvm][$osasto]['laskutetut_kate'] += $row['laskutetut_kate'];
			$arr_osasto[$pvm][$osasto]['laskutetut_rivit']++;

			$arr_try[$pvm][$osasto][$try]['laskutetut_eurot'] += $row['laskutetut_eurot'];
			$arr_try[$pvm][$osasto][$try]['laskutetut_kate'] += $row['laskutetut_kate'];
			$arr_try[$pvm][$osasto][$try]['laskutetut_rivit']++;
		}

		foreach ($arr as $pvm => $arvot) {

			$_pvm = $pvm;

			if ($naytetaan_tulos == 'daily') {
				list($v, $k, $p) = explode("-", $pvm);
				$_pvm = $p.".".$k;
			}

			$yhteensa['tilatut_eurot'] 		+= $arvot['tilatut_eurot'];
			$yhteensa['tilatut_kate'] 		+= $arvot['tilatut_kate'];
			$yhteensa['tilatut_rivit'] 		+= $arvot['tilatut_rivit'];
			$yhteensa['laskutetut_eurot'] 	+= (isset($arvot['laskutetut_eurot']) and $arvot['laskutetut_eurot'] != '') ? $arvot['laskutetut_eurot'] : 0;
			$yhteensa['laskutetut_kate'] 	+= (isset($arvot['laskutetut_kate']) and $arvot['laskutetut_kate'] != '') ? $arvot['laskutetut_kate'] : 0;
			$yhteensa['laskutetut_rivit'] 	+= (isset($arvot['laskutetut_rivit']) and $arvot['laskutetut_rivit'] != '') ? $arvot['laskutetut_rivit'] : 0;

			$tilatut_katepros = (isset($arvot['tilatut_eurot']) and $arvot['tilatut_eurot'] != 0) ? round($arvot['tilatut_kate'] / $arvot['tilatut_eurot'] * 100, 1) : 0;
			$laskutetut_katepros = (isset($arvot['laskutetut_kate']) and isset($arvot['laskutetut_eurot'])) ? round($arvot['laskutetut_kate'] / $arvot['laskutetut_eurot'] * 100, 1) : '';

			$arvot['tilatut_eurot'] = round($arvot['tilatut_eurot'] / 1000, 0);
			$arvot['laskutetut_eurot'] = isset($arvot['laskutetut_eurot']) ? round($arvot['laskutetut_eurot'] / 1000, 0) : '';
			$arvot['laskutetut_rivit'] = isset($arvot['laskutetut_rivit']) ? $arvot['laskutetut_rivit'] : '';

			echo "<tr class='aktiivi'>";
			echo "<td align='left' class='toggleable' id='{$pvm}'><img style='float:left;' id='img_{$pvm}' src='{$palvelin2}pics/lullacons/bullet-arrow-right.png' /></td>";
			echo "<td align='left' class='toggleable' id='{$pvm}_osasto'><img style='float:left;' id='img_{$pvm}_osasto' src='{$palvelin2}pics/lullacons/bullet-arrow-right.png' /></td>";
			echo "<td align='left'>{$_pvm}</td>";
			echo "<td align='right'>{$arvot['tilatut_eurot']}</td>";
			echo "<td align='right'>{$tilatut_katepros}</td>";
			echo "<td align='right'>{$arvot['tilatut_rivit']}</td>";
			echo "<td align='right'>{$arvot['laskutetut_eurot']}</td>";
			echo "<td align='right'>{$laskutetut_katepros}</td>";
			echo "<td align='right'>{$arvot['laskutetut_rivit']}</td>";
			echo "</tr>";

			ksort($arr_kustp[$pvm]);

			foreach ($arr_kustp[$pvm] as $kustp => $vals) {

				if (!isset($vals['tilatut_eurot'])) $vals['tilatut_eurot'] = 0;
				if (!isset($vals['tilatut_kate'])) $vals['tilatut_kate'] = 0;
				if (!isset($vals['tilatut_rivit'])) $vals['tilatut_rivit'] = 0;

				$tilatut_katepros = $vals['tilatut_eurot'] != 0 ? round($vals['tilatut_kate'] / $vals['tilatut_eurot'] * 100, 1) : '';
				$laskutetut_katepros = (isset($vals['laskutetut_kate']) and isset($vals['laskutetut_eurot']) and $vals['laskutetut_eurot'] != 0) ? round($vals['laskutetut_kate'] / $vals['laskutetut_eurot'] * 100, 1) : '';

				if (!isset($yhteensa_kustp[$kustp]['tilatut_eurot'])) $yhteensa_kustp[$kustp]['tilatut_eurot'] = 0;
				if (!isset($yhteensa_kustp[$kustp]['tilatut_kate'])) $yhteensa_kustp[$kustp]['tilatut_kate'] = 0;
				if (!isset($yhteensa_kustp[$kustp]['tilatut_rivit'])) $yhteensa_kustp[$kustp]['tilatut_rivit'] = 0;
				if (!isset($yhteensa_kustp[$kustp]['laskutetut_eurot'])) $yhteensa_kustp[$kustp]['laskutetut_eurot'] = 0;
				if (!isset($yhteensa_kustp[$kustp]['laskutetut_kate'])) $yhteensa_kustp[$kustp]['laskutetut_kate'] = 0;
				if (!isset($yhteensa_kustp[$kustp]['laskutetut_rivit'])) $yhteensa_kustp[$kustp]['laskutetut_rivit'] = 0;

				$yhteensa_kustp[$kustp]['tilatut_eurot'] 		+= $vals['tilatut_eurot'];
				$yhteensa_kustp[$kustp]['tilatut_kate'] 		+= $vals['tilatut_kate'];
				$yhteensa_kustp[$kustp]['tilatut_rivit'] 		+= $vals['tilatut_rivit'];
				$yhteensa_kustp[$kustp]['laskutetut_eurot'] 	+= (isset($vals['laskutetut_eurot']) and $vals['laskutetut_eurot'] != '') ? $vals['laskutetut_eurot'] : 0;
				$yhteensa_kustp[$kustp]['laskutetut_kate'] 		+= (isset($vals['laskutetut_kate']) and $vals['laskutetut_kate'] != '') ? $vals['laskutetut_kate'] : 0;
				$yhteensa_kustp[$kustp]['laskutetut_rivit'] 	+= (isset($vals['laskutetut_rivit']) and $vals['laskutetut_rivit'] != '') ? $vals['laskutetut_rivit'] : 0;
				$yhteensa_kustp[$kustp]['pvm'][$pvm]			= $pvm;

				$vals['tilatut_eurot'] = $vals['tilatut_eurot'] != '' ? round($vals['tilatut_eurot'] / 1000, 0) : '';
				$vals['laskutetut_eurot'] = isset($vals['laskutetut_eurot']) ? round($vals['laskutetut_eurot'] / 1000, 0) : '';
				$vals['laskutetut_rivit'] = isset($vals['laskutetut_rivit']) ? $vals['laskutetut_rivit'] : '';

				$id = str_replace(" ", "", $pvm.'_'.$kustp);

				echo "<tr class='{$pvm} spec kustp' style='display:none;'>";
				echo "<td align='right' class='toggleable' id='{$id}_osasto'><img style='float:left;' id='img_{$id}_osasto' src='{$palvelin2}pics/lullacons/bullet-arrow-right.png' />&nbsp;{$kustp}</td>";
				echo "<td align='right'></td>";
				echo "<td align='right'></td>";
				echo "<td align='right'>{$vals['tilatut_eurot']}</td>";
				echo "<td align='right'>{$tilatut_katepros}</td>";
				echo "<td align='right'>{$vals['tilatut_rivit']}</td>";
				echo "<td align='right'>{$vals['laskutetut_eurot']}</td>";
				echo "<td align='right'>{$laskutetut_katepros}</td>";
				echo "<td align='right'>{$vals['laskutetut_rivit']}</td>";
				echo "</tr>";

				ksort($arr_kustp_osasto[$pvm][$kustp]);

				foreach ($arr_kustp_osasto[$pvm][$kustp] as $osasto => $vals) {

					if (!isset($vals['tilatut_eurot'])) $vals['tilatut_eurot'] = 0;
					if (!isset($vals['tilatut_kate'])) $vals['tilatut_kate'] = 0;
					if (!isset($vals['tilatut_rivit'])) $vals['tilatut_rivit'] = 0;

					$tilatut_katepros = $vals['tilatut_eurot'] != 0 ? round($vals['tilatut_kate'] / $vals['tilatut_eurot'] * 100, 1) : '';
					$laskutetut_katepros = (isset($vals['laskutetut_kate']) and isset($vals['laskutetut_eurot']) and $vals['laskutetut_eurot'] != 0) ? round($vals['laskutetut_kate'] / $vals['laskutetut_eurot'] * 100, 1) : '';

					if (!isset($yhteensa_kustp_osasto[$kustp][$osasto]['tilatut_eurot'])) 		$yhteensa_kustp_osasto[$kustp][$osasto]['tilatut_eurot'] = 0;
					if (!isset($yhteensa_kustp_osasto[$kustp][$osasto]['tilatut_kate'])) 		$yhteensa_kustp_osasto[$kustp][$osasto]['tilatut_kate'] = 0;
					if (!isset($yhteensa_kustp_osasto[$kustp][$osasto]['tilatut_rivit'])) 		$yhteensa_kustp_osasto[$kustp][$osasto]['tilatut_rivit'] = 0;
					if (!isset($yhteensa_kustp_osasto[$kustp][$osasto]['laskutetut_eurot'])) 	$yhteensa_kustp_osasto[$kustp][$osasto]['laskutetut_eurot'] = 0;
					if (!isset($yhteensa_kustp_osasto[$kustp][$osasto]['laskutetut_kate'])) 	$yhteensa_kustp_osasto[$kustp][$osasto]['laskutetut_kate'] = 0;
					if (!isset($yhteensa_kustp_osasto[$kustp][$osasto]['laskutetut_rivit'])) 	$yhteensa_kustp_osasto[$kustp][$osasto]['laskutetut_rivit'] = 0;

					$yhteensa_kustp_osasto[$kustp][$osasto]['tilatut_eurot'] 		+= $vals['tilatut_eurot'];
					$yhteensa_kustp_osasto[$kustp][$osasto]['tilatut_kate'] 		+= $vals['tilatut_kate'];
					$yhteensa_kustp_osasto[$kustp][$osasto]['tilatut_rivit'] 		+= $vals['tilatut_rivit'];
					$yhteensa_kustp_osasto[$kustp][$osasto]['laskutetut_eurot'] 	+= (isset($vals['laskutetut_eurot']) and $vals['laskutetut_eurot'] != '') ? $vals['laskutetut_eurot'] : 0;
					$yhteensa_kustp_osasto[$kustp][$osasto]['laskutetut_kate'] 	+= (isset($vals['laskutetut_kate']) and $vals['laskutetut_kate'] != '') ? $vals['laskutetut_kate'] : 0;
					$yhteensa_kustp_osasto[$kustp][$osasto]['laskutetut_rivit'] 	+= (isset($vals['laskutetut_rivit']) and $vals['laskutetut_rivit'] != '') ? $vals['laskutetut_rivit'] : 0;
					$yhteensa_kustp_osasto[$kustp][$osasto]['pvm'][$pvm]			= $pvm;

					$vals['tilatut_eurot'] = $vals['tilatut_eurot'] != '' ? round($vals['tilatut_eurot'] / 1000, 0) : '';
					$vals['laskutetut_eurot'] = isset($vals['laskutetut_eurot']) ? round($vals['laskutetut_eurot'] / 1000, 0) : '';
					$vals['laskutetut_rivit'] = isset($vals['laskutetut_rivit']) ? $vals['laskutetut_rivit'] : '';

					echo "<tr class='{$id}_osasto tumma osasto' style='display:none;'>";
					echo "<td align='right'></td>";
					echo "<td align='right' class='toggleable' id='{$id}_{$osasto}_try'><img style='float:left;' id='img_{$id}_{$osasto}_try' src='{$palvelin2}pics/lullacons/bullet-arrow-right.png' />&nbsp;{$osasto} ",t_avainsana("OSASTO", "", "and avainsana.selite ='{$osasto}'", "", "", "selitetark"),"</td>";
					echo "<td align='right'></td>";
					echo "<td align='right'>{$vals['tilatut_eurot']}</td>";
					echo "<td align='right'>{$tilatut_katepros}</td>";
					echo "<td align='right'>{$vals['tilatut_rivit']}</td>";
					echo "<td align='right'>{$vals['laskutetut_eurot']}</td>";
					echo "<td align='right'>{$laskutetut_katepros}</td>";
					echo "<td align='right'>{$vals['laskutetut_rivit']}</td>";
					echo "</tr>";

					ksort($arr_kustp_osasto_try[$pvm][$kustp][$osasto]);

					foreach ($arr_kustp_osasto_try[$pvm][$kustp][$osasto] as $try => $vals) {

						if (!isset($vals['tilatut_eurot'])) $vals['tilatut_eurot'] = 0;
						if (!isset($vals['tilatut_kate'])) $vals['tilatut_kate'] = 0;
						if (!isset($vals['tilatut_rivit'])) $vals['tilatut_rivit'] = 0;
						if (!isset($vals['laskutetut_eurot'])) $vals['laskutetut_eurot'] = 0;
						if (!isset($vals['laskutetut_kate'])) $vals['laskutetut_kate'] = 0;
						if (!isset($vals['laskutetut_rivit'])) $vals['laskutetut_rivit'] = 0;

						$tilatut_katepros = $vals['tilatut_eurot'] != 0 ? round($vals['tilatut_kate'] / $vals['tilatut_eurot'] * 100, 1) : '';
						$laskutetut_katepros = (isset($vals['laskutetut_kate']) and isset($vals['laskutetut_eurot']) and $vals['laskutetut_eurot'] != 0) ? round($vals['laskutetut_kate'] / $vals['laskutetut_eurot'] * 100, 1) : '';

						if (!isset($yhteensa_kustp_osasto_try[$kustp][$osasto][$try]['tilatut_eurot'])) 	$yhteensa_kustp_osasto_try[$kustp][$osasto][$try]['tilatut_eurot'] = 0;
						if (!isset($yhteensa_kustp_osasto_try[$kustp][$osasto][$try]['tilatut_kate'])) 		$yhteensa_kustp_osasto_try[$kustp][$osasto][$try]['tilatut_kate'] = 0;
						if (!isset($yhteensa_kustp_osasto_try[$kustp][$osasto][$try]['tilatut_rivit'])) 	$yhteensa_kustp_osasto_try[$kustp][$osasto][$try]['tilatut_rivit'] = 0;
						if (!isset($yhteensa_kustp_osasto_try[$kustp][$osasto][$try]['laskutetut_eurot'])) 	$yhteensa_kustp_osasto_try[$kustp][$osasto][$try]['laskutetut_eurot'] = 0;
						if (!isset($yhteensa_kustp_osasto_try[$kustp][$osasto][$try]['laskutetut_kate']))	$yhteensa_kustp_osasto_try[$kustp][$osasto][$try]['laskutetut_kate'] = 0;
						if (!isset($yhteensa_kustp_osasto_try[$kustp][$osasto][$try]['laskutetut_rivit'])) 	$yhteensa_kustp_osasto_try[$kustp][$osasto][$try]['laskutetut_rivit'] = 0;

						$yhteensa_kustp_osasto_try[$kustp][$osasto][$try]['tilatut_eurot'] 		+= $vals['tilatut_eurot'];
						$yhteensa_kustp_osasto_try[$kustp][$osasto][$try]['tilatut_kate'] 		+= $vals['tilatut_kate'];
						$yhteensa_kustp_osasto_try[$kustp][$osasto][$try]['tilatut_rivit'] 		+= $vals['tilatut_rivit'];
						$yhteensa_kustp_osasto_try[$kustp][$osasto][$try]['laskutetut_eurot'] 	+= (isset($vals['laskutetut_eurot']) and $vals['laskutetut_eurot'] != '') ? $vals['laskutetut_eurot'] : 0;
						$yhteensa_kustp_osasto_try[$kustp][$osasto][$try]['laskutetut_kate'] 	+= (isset($vals['laskutetut_kate']) and $vals['laskutetut_kate'] != '') ? $vals['laskutetut_kate'] : 0;
						$yhteensa_kustp_osasto_try[$kustp][$osasto][$try]['laskutetut_rivit'] 	+= (isset($vals['laskutetut_rivit']) and $vals['laskutetut_rivit'] != '') ? $vals['laskutetut_rivit'] : 0;
						$yhteensa_kustp_osasto_try[$kustp][$osasto][$try]['pvm'][$pvm]			= $pvm;

						$vals['tilatut_eurot'] = $vals['tilatut_eurot'] != '' ? round($vals['tilatut_eurot'] / 1000, 0) : '';
						$vals['laskutetut_eurot'] = isset($vals['laskutetut_eurot']) ? round($vals['laskutetut_eurot'] / 1000, 0) : '';
						$vals['laskutetut_rivit'] = isset($vals['laskutetut_rivit']) ? $vals['laskutetut_rivit'] : '';

						echo "<tr class='{$id}_{$osasto}_try spec try' style='display:none;'>";
						echo "<td align='right'></td>";
						echo "<td align='right'>{$try} ",t_avainsana("TRY", "", "and avainsana.selite ='{$try}'", "", "", "selitetark"),"</td>";
						echo "<td align='right'></td>";
						echo "<td align='right'>{$vals['tilatut_eurot']}</td>";
						echo "<td align='right'>{$tilatut_katepros}</td>";
						echo "<td align='right'>{$vals['tilatut_rivit']}</td>";
						echo "<td align='right'>{$vals['laskutetut_eurot']}</td>";
						echo "<td align='right'>{$laskutetut_katepros}</td>";
						echo "<td align='right'>{$vals['laskutetut_rivit']}</td>";
						echo "</tr>";
					}
				}
			}

			ksort($arr_osasto[$pvm]);

			foreach ($arr_osasto[$pvm] as $osasto => $vals) {

				if (!isset($vals['tilatut_eurot'])) $vals['tilatut_eurot'] = 0;
				if (!isset($vals['tilatut_kate'])) $vals['tilatut_kate'] = 0;
				if (!isset($vals['tilatut_rivit'])) $vals['tilatut_rivit'] = 0;

				$tilatut_katepros = $vals['tilatut_eurot'] != 0 ? round($vals['tilatut_kate'] / $vals['tilatut_eurot'] * 100, 1) : '';
				$laskutetut_katepros = (isset($vals['laskutetut_kate']) and isset($vals['laskutetut_eurot']) and $vals['laskutetut_eurot'] != 0) ? round($vals['laskutetut_kate'] / $vals['laskutetut_eurot'] * 100, 1) : '';

				if (!isset($yhteensa_osasto[$osasto]['tilatut_eurot'])) $yhteensa_osasto[$osasto]['tilatut_eurot'] = 0;
				if (!isset($yhteensa_osasto[$osasto]['tilatut_kate'])) $yhteensa_osasto[$osasto]['tilatut_kate'] = 0;
				if (!isset($yhteensa_osasto[$osasto]['tilatut_rivit'])) $yhteensa_osasto[$osasto]['tilatut_rivit'] = 0;
				if (!isset($yhteensa_osasto[$osasto]['laskutetut_eurot'])) $yhteensa_osasto[$osasto]['laskutetut_eurot'] = 0;
				if (!isset($yhteensa_osasto[$osasto]['laskutetut_kate'])) $yhteensa_osasto[$osasto]['laskutetut_kate'] = 0;
				if (!isset($yhteensa_osasto[$osasto]['laskutetut_rivit'])) $yhteensa_osasto[$osasto]['laskutetut_rivit'] = 0;

				$yhteensa_osasto[$osasto]['tilatut_eurot'] 		+= $vals['tilatut_eurot'];
				$yhteensa_osasto[$osasto]['tilatut_kate'] 		+= $vals['tilatut_kate'];
				$yhteensa_osasto[$osasto]['tilatut_rivit'] 		+= $vals['tilatut_rivit'];
				$yhteensa_osasto[$osasto]['laskutetut_eurot'] 	+= (isset($vals['laskutetut_eurot']) and $vals['laskutetut_eurot'] != '') ? $vals['laskutetut_eurot'] : 0;
				$yhteensa_osasto[$osasto]['laskutetut_kate'] 	+= (isset($vals['laskutetut_kate']) and $vals['laskutetut_kate'] != '') ? $vals['laskutetut_kate'] : 0;
				$yhteensa_osasto[$osasto]['laskutetut_rivit'] 	+= (isset($vals['laskutetut_rivit']) and $vals['laskutetut_rivit'] != '') ? $vals['laskutetut_rivit'] : 0;
				$yhteensa_osasto[$osasto]['pvm'][$pvm]			= $pvm;

				$vals['tilatut_eurot'] = $vals['tilatut_eurot'] != '' ? round($vals['tilatut_eurot'] / 1000, 0) : '';
				$vals['laskutetut_eurot'] = isset($vals['laskutetut_eurot']) ? round($vals['laskutetut_eurot'] / 1000, 0) : '';
				$vals['laskutetut_rivit'] = isset($vals['laskutetut_rivit']) ? $vals['laskutetut_rivit'] : '';

				echo "<tr class='{$pvm}_osasto tumma osasto' style='display:none;'>";
				echo "<td align='right'></td>";
				echo "<td align='right' class='toggleable' id='{$pvm}_{$osasto}_try'><img style='float:left;' id='img_{$pvm}_{$osasto}_try' src='{$palvelin2}pics/lullacons/bullet-arrow-right.png' />&nbsp;{$osasto} ",t_avainsana("OSASTO", "", "and avainsana.selite ='{$osasto}'", "", "", "selitetark"),"</td>";
				echo "<td align='right'></td>";
				echo "<td align='right'>{$vals['tilatut_eurot']}</td>";
				echo "<td align='right'>{$tilatut_katepros}</td>";
				echo "<td align='right'>{$vals['tilatut_rivit']}</td>";
				echo "<td align='right'>{$vals['laskutetut_eurot']}</td>";
				echo "<td align='right'>{$laskutetut_katepros}</td>";
				echo "<td align='right'>{$vals['laskutetut_rivit']}</td>";
				echo "</tr>";

				ksort($arr_try[$pvm][$osasto]);

				foreach ($arr_try[$pvm][$osasto] as $try => $vals) {

					if (!isset($vals['tilatut_eurot'])) $vals['tilatut_eurot'] = 0;
					if (!isset($vals['tilatut_kate'])) $vals['tilatut_kate'] = 0;
					if (!isset($vals['tilatut_rivit'])) $vals['tilatut_rivit'] = 0;
					if (!isset($vals['laskutetut_eurot'])) $vals['laskutetut_eurot'] = 0;
					if (!isset($vals['laskutetut_kate'])) $vals['laskutetut_kate'] = 0;
					if (!isset($vals['laskutetut_rivit'])) $vals['laskutetut_rivit'] = 0;

					$tilatut_katepros = $vals['tilatut_eurot'] != 0 ? round($vals['tilatut_kate'] / $vals['tilatut_eurot'] * 100, 1) : '';
					$laskutetut_katepros = (isset($vals['laskutetut_kate']) and isset($vals['laskutetut_eurot']) and $vals['laskutetut_eurot'] != 0) ? round($vals['laskutetut_kate'] / $vals['laskutetut_eurot'] * 100, 1) : '';

					if (!isset($yhteensa_try[$osasto][$try]['tilatut_eurot'])) 		$yhteensa_try[$osasto][$try]['tilatut_eurot'] = 0;
					if (!isset($yhteensa_try[$osasto][$try]['tilatut_kate'])) 		$yhteensa_try[$osasto][$try]['tilatut_kate'] = 0;
					if (!isset($yhteensa_try[$osasto][$try]['tilatut_rivit'])) 		$yhteensa_try[$osasto][$try]['tilatut_rivit'] = 0;
					if (!isset($yhteensa_try[$osasto][$try]['laskutetut_eurot'])) 	$yhteensa_try[$osasto][$try]['laskutetut_eurot'] = 0;
					if (!isset($yhteensa_try[$osasto][$try]['laskutetut_kate']))	$yhteensa_try[$osasto][$try]['laskutetut_kate'] = 0;
					if (!isset($yhteensa_try[$osasto][$try]['laskutetut_rivit'])) 	$yhteensa_try[$osasto][$try]['laskutetut_rivit'] = 0;

					$yhteensa_try[$osasto][$try]['tilatut_eurot'] 		+= $vals['tilatut_eurot'];
					$yhteensa_try[$osasto][$try]['tilatut_kate'] 		+= $vals['tilatut_kate'];
					$yhteensa_try[$osasto][$try]['tilatut_rivit'] 		+= $vals['tilatut_rivit'];
					$yhteensa_try[$osasto][$try]['laskutetut_eurot'] 	+= (isset($vals['laskutetut_eurot']) and $vals['laskutetut_eurot'] != '') ? $vals['laskutetut_eurot'] : 0;
					$yhteensa_try[$osasto][$try]['laskutetut_kate'] 	+= (isset($vals['laskutetut_kate']) and $vals['laskutetut_kate'] != '') ? $vals['laskutetut_kate'] : 0;
					$yhteensa_try[$osasto][$try]['laskutetut_rivit'] 	+= (isset($vals['laskutetut_rivit']) and $vals['laskutetut_rivit'] != '') ? $vals['laskutetut_rivit'] : 0;
					$yhteensa_try[$osasto][$try]['pvm'][$pvm]			= $pvm;

					$vals['tilatut_eurot'] = $vals['tilatut_eurot'] != '' ? round($vals['tilatut_eurot'] / 1000, 0) : '';
					$vals['laskutetut_eurot'] = isset($vals['laskutetut_eurot']) ? round($vals['laskutetut_eurot'] / 1000, 0) : '';
					$vals['laskutetut_rivit'] = isset($vals['laskutetut_rivit']) ? $vals['laskutetut_rivit'] : '';

					echo "<tr class='{$pvm}_{$osasto}_try spec try' style='display:none;'>";
					echo "<td align='right'></td>";
					echo "<td align='right'>{$try} ",t_avainsana("TRY", "", "and avainsana.selite ='{$try}'", "", "", "selitetark"),"</td>";
					echo "<td align='right'></td>";
					echo "<td align='right'>{$vals['tilatut_eurot']}</td>";
					echo "<td align='right'>{$tilatut_katepros}</td>";
					echo "<td align='right'>{$vals['tilatut_rivit']}</td>";
					echo "<td align='right'>{$vals['laskutetut_eurot']}</td>";
					echo "<td align='right'>{$laskutetut_katepros}</td>";
					echo "<td align='right'>{$vals['laskutetut_rivit']}</td>";
					echo "</tr>";
				}
			}
		}

		echo "<tr class='aktiivi'>";
		echo "<th class='toggleable' id='yhteensa_kustp'><img style='float:left;' id='img_yhteensa_kustp' src='{$palvelin2}pics/lullacons/bullet-arrow-right.png' />&nbsp;",t("Yhteensä"),"<br />",t("Kustp"),"</th>";
		echo "<th class='toggleable' id='yhteensa_osasto'><img style='float:left;' id='img_yhteensa_osasto' src='{$palvelin2}pics/lullacons/bullet-arrow-right.png' />&nbsp;",t("Yhteensä"),"<br />",t("os / try"),"</th>";
		echo "<td align='right'></td>";
		echo "<td align='right'>",round($yhteensa['tilatut_eurot'] / 1000, 0),"</td>";
		echo "<td align='right'>",round($yhteensa['tilatut_kate'] / $yhteensa['tilatut_eurot'] * 100, 1),"</td>";
		echo "<td align='right'>",round($yhteensa['tilatut_rivit']),"</td>";
		echo "<td align='right'>",round($yhteensa['laskutetut_eurot'] / 1000, 0),"</td>";
		echo "<td align='right'>",(round($yhteensa['laskutetut_kate'] / $yhteensa['laskutetut_eurot'] * 100, 1)),"</td>";
		echo "<td align='right'>",round($yhteensa['laskutetut_rivit']),"</td>";
		echo "</tr>";

		ksort($yhteensa_kustp);

		foreach ($yhteensa_kustp as $kustp => $vals) {

			$_kustp = $kustp;

			if ($kustp == '') $_kustp = t("Ei kustannuspaikkaa");
			$kustp_id = str_replace(" ", "", $kustp);

			echo "<tr class='yhteensa_kustp aktiivi' style='display:none;'>";
			echo "<th class='toggleable' id='yhteensa_{$kustp_id}_osasto'><img style='float:left;' id='img_yhteensa_{$kustp_id}_osasto' src='{$palvelin2}pics/lullacons/bullet-arrow-right.png' />&nbsp;",t("Yhteensä")," {$_kustp}</th>";
			echo "<td align='right'></td>";
			echo "<td align='right'></td>";
			echo "<td align='right'>",round($vals['tilatut_eurot'] / 1000, 0),"</td>";
			echo "<td align='right'>",($vals['tilatut_eurot'] != 0 ? round($vals['tilatut_kate'] / $vals['tilatut_eurot'] * 100, 1) : ''),"</td>";
			echo "<td align='right'>",round($vals['tilatut_rivit']),"</td>";
			echo "<td align='right'>",round($vals['laskutetut_eurot'] / 1000, 0),"</td>";
			echo "<td align='right'>",($vals['laskutetut_eurot'] != 0 ? round($vals['laskutetut_kate'] / $vals['laskutetut_eurot'] * 100, 1) : ''),"</td>";
			echo "<td align='right'>",round($vals['laskutetut_rivit']),"</td>";
			echo "</tr>";

			ksort($yhteensa_kustp_osasto);

			unset($osasto);

			foreach ($yhteensa_kustp_osasto[$kustp] as $osasto => $vals) {

				$_osasto = $osasto == '' ? t("Ei osastoa") : $osasto;

				$id = str_replace(" ", "", "{$kustp}_{$osasto}");

				echo "<tr class='yhteensa_{$kustp_id}_osasto aktiivi osasto' style='display:none;'>";
				echo "<td align='right'></td>";
				echo "<th class='toggleable' id='yhteensa_{$id}_try'><img style='float:left;' id='img_{$id}_try' src='{$palvelin2}pics/lullacons/bullet-arrow-right.png' />&nbsp;",t("Yhteensä")," {$_osasto} ",t_avainsana("OSASTO", "", "and avainsana.selite ='{$osasto}'", "", "", "selitetark"),"</th>";
				echo "<td align='right'></td>";
				echo "<td align='right'>",round($vals['tilatut_eurot'] / 1000, 0),"</td>";
				echo "<td align='right'>",($vals['tilatut_eurot'] != 0 ? round($vals['tilatut_kate'] / $vals['tilatut_eurot'] * 100, 1) : ''),"</td>";
				echo "<td align='right'>",round($vals['tilatut_rivit']),"</td>";
				echo "<td align='right'>",round($vals['laskutetut_eurot'] / 1000, 0),"</td>";
				echo "<td align='right'>",($vals['laskutetut_eurot'] != 0 ? round($vals['laskutetut_kate'] / $vals['laskutetut_eurot'] * 100, 1) : ''),"</td>";
				echo "<td align='right'>",round($vals['laskutetut_rivit']),"</td>";
				echo "</tr>";

				unset($try);

				foreach ($yhteensa_kustp_osasto_try[$kustp][$osasto] as $try => $vals) {

					if ($try == '') $try = t("Ei tuoteryhmää");

					echo "<tr class='yhteensa_{$id}_try spec aktiivi try' style='display:none;'>";
					echo "<td align='right'></td>";
					echo "<td align='left' class='tumma'>",t("Yhteensä")," {$try} ",t_avainsana("TRY", "", "and avainsana.selite ='{$try}'", "", "", "selitetark"),"</td>";
					echo "<td align='right'></td>";
					echo "<td align='right'>",round($vals['tilatut_eurot'] / 1000, 0),"</td>";
					echo "<td align='right'>",($vals['tilatut_eurot'] != 0 ? round($vals['tilatut_kate'] / $vals['tilatut_eurot'] * 100, 1) : ''),"</td>";
					echo "<td align='right'>",round($vals['tilatut_rivit']),"</td>";
					echo "<td align='right'>",round($vals['laskutetut_eurot'] / 1000, 0),"</td>";
					echo "<td align='right'>",($vals['laskutetut_eurot'] != 0 ? round($vals['laskutetut_kate'] / $vals['laskutetut_eurot'] * 100, 1) : ''),"</td>";
					echo "<td align='right'>",round($vals['laskutetut_rivit']),"</td>";
					echo "</tr>";


				}
			}
		}

		ksort($yhteensa_osasto);

		unset($osasto);

		foreach ($yhteensa_osasto as $osasto => $vals) {

			$_osasto = $osasto == '' ? t("Ei osastoa") : $osasto;

			echo "<tr class='yhteensa_osasto aktiivi osasto' style='display:none;'>";
			echo "<td align='right'></td>";
			echo "<th class='toggleable' id='{$osasto}_try'><img style='float:left;' id='img_{$osasto}_try' src='{$palvelin2}pics/lullacons/bullet-arrow-right.png' />&nbsp;",t("Yhteensä")," {$_osasto} ",t_avainsana("OSASTO", "", "and avainsana.selite ='{$osasto}'", "", "", "selitetark"),"</th>";
			echo "<td align='right'></td>";
			echo "<td align='right'>",round($vals['tilatut_eurot'] / 1000, 0),"</td>";
			echo "<td align='right'>",($vals['tilatut_eurot'] != 0 ? round($vals['tilatut_kate'] / $vals['tilatut_eurot'] * 100, 1) : ''),"</td>";
			echo "<td align='right'>",round($vals['tilatut_rivit']),"</td>";
			echo "<td align='right'>",round($vals['laskutetut_eurot'] / 1000, 0),"</td>";
			echo "<td align='right'>",($vals['laskutetut_eurot'] != 0 ? round($vals['laskutetut_kate'] / $vals['laskutetut_eurot'] * 100, 1) : ''),"</td>";
			echo "<td align='right'>",round($vals['laskutetut_rivit']),"</td>";
			echo "</tr>";

			unset($try);

			foreach ($yhteensa_try[$osasto] as $try => $vals) {

				if ($try == '') $try = t("Ei tuoteryhmää");

				echo "<tr class='{$osasto}_try spec aktiivi try' style='display:none;'>";
				echo "<td align='right'></td>";
				echo "<td align='left' class='tumma'>",t("Yhteensä")," {$try} ",t_avainsana("TRY", "", "and avainsana.selite ='{$try}'", "", "", "selitetark"),"</td>";
				echo "<td align='right'></td>";
				echo "<td align='right'>",round($vals['tilatut_eurot'] / 1000, 0),"</td>";
				echo "<td align='right'>",($vals['tilatut_eurot'] != 0 ? round($vals['tilatut_kate'] / $vals['tilatut_eurot'] * 100, 1) : ''),"</td>";
				echo "<td align='right'>",round($vals['tilatut_rivit']),"</td>";
				echo "<td align='right'>",round($vals['laskutetut_eurot'] / 1000, 0),"</td>";
				echo "<td align='right'>",($vals['laskutetut_eurot'] != 0 ? round($vals['laskutetut_kate'] / $vals['laskutetut_eurot'] * 100, 1) : ''),"</td>";
				echo "<td align='right'>",round($vals['laskutetut_rivit']),"</td>";
				echo "</tr>";


			}
		}

		echo "</table>";

	}

	require ("inc/footer.inc");
