<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

require ("../inc/parametrit.inc");
require ("../inc/functions.inc");

echo "<font class='head'>".t("Inventointiaste")."</font><hr>";

gauge();
?>
<style>
	#wrapper {
		float: left;
	}

	#chart_div {
		display: block;
	}

	#table_div {
		display: block;
		clear: both;
	}
</style>
<script>
	$(document).ready(function() {
		var gauge_types = [
			'12kk',
			'tilikausi'
		];

		var gauges = {};
		for (var gauge_type in gauge_types) {
			gauges[gauge_types[gauge_type]] = init_and_draw_gauge(gauge_types[gauge_type]);
		}
	});

	function init_and_draw_gauge(type) {
		var gauge = new Gauge();

		if (type === '12kk') {
			var args = {
				prosentti12kk: ['%', 0]
			};
		}
		else {
			var args = {
				prosenttitilikausi: ['%', 0]
			};
		}


		var options = {forceIFrame: false,
			width: 800,
			height: 220,
			min: 0,
			max: 100,
			redFrom: 70,
			redTo: 80,
			greenFrom: 90,
			greenTo: 100,
			yellowFrom: 80,
			yellowTo: 90,
			minorTicks: 5,
			majorTicks: ['0', '10', '20', '30', '40', '50', '60', '70', '80', '90', '100'],
			animation: {
				easing: 'out',
				duration: 4000
			}};

		gauge.init(args, options);

		var lukumaara_string = 'inventointien_lukumaara_' + type;
		if (!isNaN($('#' + lukumaara_string).val()) && $('#varaston_hyllypaikkojen_lukumaara').val() !== 0) {
			var draw_options = {
				max: options.max,
				type: 'custom_parseint'
			};
			var inventointiaste = ($('#' + lukumaara_string).val() / $('#varaston_hyllypaikkojen_lukumaara').val()) * 100;
			gauge.draw(inventointiaste, draw_options);
		}

		return gauge;
	}

	function tarkista() {
		var ok = true;

		if ($('.varastot:checked').length > 0) {
			ok = false;
			alert($('#valitse_varasto_error_message').val());
		}

		return ok;
	}

</script>

<?php

$request = array(
	'ppa'						 => $ppa,
	'kka'						 => $kka,
	'vva'						 => $vva,
	'ppl'						 => $ppl,
	'kkl'						 => $kkl,
	'vvl'						 => $vvl,
	'tilikausi'					 => $tilikausi,
	'yhtio'						 => $yhtio,
	'valitut_varastot'			 => $varastot,
	'valitut_inventointilajit'	 => $inventointilajit,
);
echo "<div id='wrapper'>";
init($request);

echo_arvot($request);

echo_kayttoliittyma($request);

echo "</div>";

function init(&$request) {
	echo "<input type='hidden' id='valitse_varasto_error_message' value='".t("Valitse varasto")."' />";

	echo "<div id='chart_div'></div>";
	echo "<br/>";
	echo "<br/>";

	$request['yhtiot'] = hae_yhtiot($request);
	if (!empty($request['yhtiot'])) {
		array_unshift($request['yhtiot'], array('tunnus' => '', 'nimi'	 => t("Valitse yhtiö")));
	}
	else {
		$request['yhtiot'] = array('tunnus' => '', 'nimi'	 => t("Valitse yhtiö"));
	}

	$request['tilikaudet'] = hae_tilikaudet();
	if (!empty($request['tilikaudet'])) {
		array_unshift($request['tilikaudet'], array('tunnus'	 => '', 'tilikausi'	 => t("Valitse tilikausi tai syötä päivämäärä rajat")));
	}
	else {
		$request['tilikaudet'] = array('tunnus'	 => '', 'tilikausi'	 => t("Valitse tilikausi tai syötä päivämäärä rajat"));
	}

	$request['varastot'] = hae_varastot($request);

	if (empty($request['valitut_varastot'])) {
		//ensimmäinen sivulataus, requestista ei ole tullut valittuja varastoja, rajataan käyttöliittymään esivalittujen varastojen perusteella
		foreach ($request['varastot'] as $varasto) {
			if (!empty($varasto['checked'])) {
				$request['valitut_varastot'][] = $varasto['tunnus'];
			}
		}
	}

	$request['inventointilajit'] = hae_inventointilajit($request);

	if (empty($request['valitut_inventointilajit'])) {
		//ensimmäinen sivulataus, requestista ei ole tullut valittuja inventointilajeja, rajataan käyttöliittymään esivalittujen inventointilajien perusteella perusteella
		foreach ($request['valitut_inventointilajit'] as $inventointilaji) {
			if (!empty($inventointilaji['checked'])) {
				$request['valitut_inventointilajit'][] = $inventointilaji['selite'];
			}
		}
	}
}

function echo_arvot($request) {

	$inventointien_lukumaara_12kk = hae_inventointien_lukumaara($request, '12kk');
	$inventointien_lukumaara_tilikausi = hae_inventointien_lukumaara($request, 'tilikausi');
	$varaston_hyllypaikkojen_lukumaara = hae_varaston_hyllypaikkojen_lukumaara($request);

	echo "<input type='hidden' id='inventointien_lukumaara_12kk' value='{$inventointien_lukumaara_12kk['inventointien_lukumaara']}' />";
	echo "<input type='hidden' id='inventointien_lukumaara_tilikausi' value='{$inventointien_lukumaara_tilikausi['inventointien_lukumaara']}' />";
	echo "<input type='hidden' id='varaston_hyllypaikkojen_lukumaara' value='{$varaston_hyllypaikkojen_lukumaara['varaston_hyllypaikkojen_lukumaara']}' />";

	//haetaan tämän tilikauden jäljellä olevien työpäivien lukumäärä
	$tyopaivien_lukumaara = hae_tyopaivien_lukumaara(date('Y-m-d'), date('Y-12-31'));
	$inventointeja_per_paiva = ($varaston_hyllypaikkojen_lukumaara['varaston_hyllypaikkojen_lukumaara'] - $inventointien_lukumaara_tilikausi['inventointien_lukumaara']) / $tyopaivien_lukumaara;

	echo "<table>";
	echo "<tr>";
	echo "<th>".t("Hyllypaikkojen inventointeja pitää suorittaa per päivä")."</th>";
	echo "<td>{$inventointeja_per_paiva}</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Hyllypaikkoja valituissa varastoissa")."</th>";
	echo "<td>{$varaston_hyllypaikkojen_lukumaara['varaston_hyllypaikkojen_lukumaara']}</td>";
	echo "</tr>";
	echo "</table>";
}

function echo_kayttoliittyma($request) {
	global $kukarow;

	echo "<div id='table_div'>";
	echo "<form method='POST' action='' name='inventointiaste_form'>";

	echo "<table>";

	echo "<tr>";
	echo "<th>", t("Syötä alkupäivämäärä"), " (", t("pp-kk-vvvv"), ")</th>";
	echo "<td><input type='text' name='ppa' id='ppa' value='{$request['ppa']}' size='3'>";
	echo "<input type='text' name='kka' id='kka' value='{$request['kka']}' size='3'>";
	echo "<input type='text' name='vva' id='vva' value='{$request['vva']}' size='5'></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>", t("Syötä loppupäivämäärä"), " (", t("pp-kk-vvvv"), ")</th>";
	echo "<td><input type='text' name='ppl' id='ppl' value='{$request['ppl']}' size='3'>";
	echo "<input type='text' name='kkl' id='kkl' value='{$request['kkl']}' size='3'>";
	echo "<input type='text' name='vvl' id='vvl' value='{$request['vvl']}' size='5'></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Tilikausi")."</th>";
	echo "<td>";
	echo "<select name='tilikausi'>";
	foreach ($request['tilikaudet'] as $tilikausi) {
		echo "<option value='{$tilikausi['tunnus']}' {$tilikausi['selected']}>{$tilikausi['tilikausi']}</option>";
	}
	echo "</select>";
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Valitse yhtiö")."</th>";
	echo "<td>";
	echo "<select name='yhtio'>";
	foreach ($request['yhtiot'] as $yhtio) {
		echo "<option value='{$yhtio['tunnus']}' {$yhtio['selected']}>{$yhtio['nimi']}</option>";
	}
	echo "</select>";
	echo "</td>";
	echo "<tr>";

	echo "<tr>";
	echo "<th>".t("Varastot")."</th>";
	echo "<td>";
	foreach ($request['varastot'] as $varasto) {
		echo "<input class='varastot' type='checkbox' name='varastot[]' value='{$varasto['tunnus']}' {$varasto['checked']} />";
		echo " {$varasto['nimitys']}";
		echo "<br/>";
	}
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Inventointilajit")."</th>";
	echo "<td>";
	foreach ($request['inventointilajit'] as $inventointilaji) {
		echo "<input class='inventointilajit' type='checkbox' name='inventointilajit[]' value='{$inventointilaji['tunnus']}' {$inventointilaji['checked']} />";
		echo " {$inventointilaji['selite']}";
		echo "<br/>";
	}
	echo "</td>";
	echo "</tr>";

	echo "</table>";

	echo "<input type='submit' value='".t("Hae")."' onclick='return tarkista();' />";

	echo "</form>";

	echo "</div>";
}

function hae_inventointien_lukumaara($request, $aikavali_tyyppi = '') {
	global $kukarow;

	if ($aikavali_tyyppi == '12kk') {
		$request['alku_aika'] = date('Y-m-d', strtotime('now - 12 month'));
		$request['loppu_aika'] = date('Y-m-d');
	}
	elseif ($aikavali_tyyppi == 'tilikausi') {
		$request['alku_aika'] = date('Y-01-01');
		$request['loppu_aika'] = date('Y-12-31');
	}
	else {
		$request['alku_aika'] = $request['alku_aika'];
		$request['loppu_aika'] = $request['loppu_aika'];
	}

	if (!empty($request['valitut_inventointilajit'])) {
		$inventointilaji_rajaus = "AND ( ";
		foreach ($request['valitut_inventointilajit'] as $inventointilaji) {
			$inventointilaji_rajaus .= " tapahtuma.selite LIKE '%$inventointilaji' OR";
		}
		$inventointilaji_rajaus = substr($inventointilaji_rajaus, 0, -3);
	}

	$query = "	SELECT count(*) as inventointien_lukumaara
				FROM tuotepaikat
				JOIN tuote
				ON ( tuote.yhtio = tuotepaikat.yhtio
					  AND tuote.tuoteno = tuotepaikat.tuoteno
					  AND tuote.ei_saldoa = ''
					  AND tuote.status = 'A'
				 )
				JOIN tapahtuma
				ON ( tapahtuma.yhtio = tuotepaikat.yhtio
					  AND tapahtuma.tuoteno = tuotepaikat.tuoteno
					  AND tapahtuma.hyllyalue = tuotepaikat.hyllyalue
					  AND tapahtuma.hyllynro = tuotepaikat.hyllynro
					  AND tapahtuma.hyllyvali = tuotepaikat.hyllyvali
					  AND tapahtuma.hyllytaso = tuotepaikat.hyllytaso
					  AND tapahtuma.laadittu BETWEEN '{$request['alku_aika']}' AND '{$request['loppu_aika']}'
					  AND tapahtuma.laji = 'Inventointi'
					  {$inventointilaji_rajaus}
				)
				JOIN varastopaikat
				ON ( varastopaikat.yhtio = tuotepaikat.yhtio
					  AND concat(rpad(upper(varastopaikat.alkuhyllyalue), 5, '0'),lpad(upper(varastopaikat.alkuhyllynro), 5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
					  AND concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'),lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
					  AND varastopaikat.tunnus IN (".implode(', ', $request['valitut_varastot']).")
				)
				WHERE tuotepaikat.yhtio = '{$kukarow['yhtio']}'";
	$result = pupe_query($query);

	return mysql_fetch_assoc($result);
}

function hae_varaston_hyllypaikkojen_lukumaara($request) {
	global $kukarow;

	$query = "	SELECT count(*) as varaston_hyllypaikkojen_lukumaara
				FROM varaston_hyllypaikat
				JOIN varastopaikat
				ON ( varastopaikat.yhtio = varaston_hyllypaikat.yhtio
					AND varastopaikat.tunnus = varaston_hyllypaikat.varasto
					AND varastopaikat.tunnus IN (".implode(', ', $request['valitut_varastot']).")
				)
				WHERE varaston_hyllypaikat.yhtio = '{$kukarow['yhtio']}'";
	$result = pupe_query($query);

	return mysql_fetch_assoc($result);
}

function hae_yhtiot($request = array()) {
	global $kukarow;

	$query = "	SELECT *
				FROM yhtio";
	$result = pupe_query($query);

	$yhtiot = array();
	while ($yhtio = mysql_fetch_assoc($result)) {
		if (!empty($request) and !empty($request['yhtio'])) {
			if ($request['yhtio'] == $yhtio['tunnus']) {
				//jos requestista tulee valittu yhtio valitaan se
				$yhtio['selected'] = 'selected';
			}
			else {
				$yhtio['selected'] = '';
			}
		}
		else {
			//jos requestista ei tule valittua yhtiota esivalitaan käyttäjän yhtiö
			if ($yhtio['yhtio'] == $kukarow['yhtio']) {
				$yhtio['selected'] = 'selected';
			}
			else {
				$yhtio['selected'] = '';
			}
		}

		$yhtiot[] = $yhtio;
	}

	return $yhtiot;
}

function hae_tilikaudet($request = array()) {
	global $kukarow;

	$query = "	SELECT *
				FROM tilikaudet
				WHERE yhtio = '{$kukarow['yhtio']}'";
	$result = pupe_query($query);

	$tilikaudet = array();
	while ($tilikausi = mysql_fetch_assoc($result)) {
		if (!empty($request) and $request['tilikausi'] == $tilikausi['tunnus']) {
			$tilikausi['selected'] = 'selected';
		}
		else {
			$tilikausi['selected'] = '';
		}
		$tilikausi['tilikausi'] = date('d.m.Y', strtotime($tilikausi['tilikausi_alku'])).' - '.date('d.m.Y', strtotime($tilikausi['tilikausi_loppu']));
		$tilikaudet[] = $tilikausi;
	}

	return $tilikaudet;
}

function hae_varastot($request = array()) {
	global $kukarow;

	$query = "	SELECT *
				FROM varastopaikat
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tyyppi != 'P'";
	$result = pupe_query($query);

	$varastot = array();
	while ($varasto = mysql_fetch_assoc($result)) {
		//jos requestista tulee valittuja varastoja valitaan ne
		if (!empty($request) and !empty($request['valitut_varastot'])) {
			if (in_array($varasto['tunnus'], $request['valitut_varastot'])) {
				$varasto['checked'] = 'checked';
			}
			else {
				$varasto['checked'] = '';
			}
		}
		else {
			//requesista ei tullut valittuja varastoja
			//valitaan käyttäjän oletusvarasto jos asetettu
			if (!empty($kukarow['oletus_varasto'])) {
				if ($kukarow['oletus_varasto'] == $varasto['tunnus']) {
					$varasto['checked'] = 'checked';
				}
				else {
					$varasto['checked'] = '';
				}
			}
			else {
				//jos ei oletus varastoa ja requestista ei tule valittuja varastoja, esivalitaan kaikki varastot
				$varasto['checked'] = 'checked';
			}
		}


		$varastot[] = $varasto;
	}

	return $varastot;
}

function hae_inventointilajit($request = array()) {
	global $kukarow;

	$query = "	SELECT *
				FROM avainsana
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND laji = 'INVEN_LAJI'";
	$result = pupe_query($query);

	$inventointilajit = array();
	while ($inventointilaji = mysql_fetch_assoc($result)) {
		//jos requestista tulee valittuja inventointilajeja valitaan ne
		if (!empty($request)) {
			if (!empty($request['valitut_inventointilajit'])) {
				if (in_array($inventointilaji['tunnus'], $request['valitut_inventointilajit'])) {
					$inventointilaji['checked'] = 'checked';
				}
				else {
					$inventointilaji['checked'] = '';
				}
			}
			else {
				//oletuksena valitaan kaikki
				$inventointilaji['checked'] = 'checked';
			}
		}

		$inventointilajit[] = $inventointilaji;
	}

	return $inventointilajit;
}
