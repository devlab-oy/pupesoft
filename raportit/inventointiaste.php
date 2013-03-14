<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

require ("../inc/parametrit.inc");

ini_set("memory_limit", "5G");

echo "<font class='head'>".t("Inventointiaste")."</font><hr>";
?>
<script>
	$(document).ready(function() {
		var gauge_types = [
			'viimeinen_12',
			'kuluva_q'
		];

		var gauges = {};
		for (var gauge_type in gauge_types) {
			gauges[gauge_type] = create_and_init_gauge(gauge_type);
		}
	});

	function create_and_init_gauge(type) {
		var gauge = new Gauge();

		if (type === 'viimeinen_12') {
			var args = {
				tilatut: ['k".$yhtiorow["valkoodi"]."', 0]
			};

			var options = {forceIFrame: false,
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
		}
		else {
			var args = {
				tilatut: ['k".$yhtiorow["valkoodi"]."', 0]
			};

			var options = {forceIFrame: false,
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
		}

		gauge.init(args, options);

		return gauge;
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

$request['varastot'] = hae_varastot();

$request['inventointilajit'] = hae_inventointilajit();

gauge();

echo_kayttoliittyma($request);

function echo_kayttoliittyma($request) {
	global $kukarow;

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

	echo "<input type='submit' value='".t("Hae")."' />";

	echo "</form>";
}

function hae_yhtiot($request = array()) {
	global $kukarow;

	$query = "	SELECT *
				FROM yhtio";
	$result = pupe_query($query);

	$yhtiot = array();
	while ($yhtio = mysql_fetch_assoc($result)) {
		if(!empty($request) and $request['yhtio'] == $yhtio['tunnus']) {
			$yhtio['selected'] = 'selected';
		}
		else {
			$yhtio['selected'] = '';
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
		if(!empty($request) and $request['tilikausi'] == $tilikausi['tunnus']) {
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
				WHERE tyyppi != 'P'";
	$result = pupe_query($query);

	$varastot = array();
	while ($varasto = mysql_fetch_assoc($result)) {
		//jos requestista tulee valittuja varastoja valitaan ne
		if(!empty($request['valitut_varastot'])) {
			if(in_array($varasto['tunnus'], $request['valitut_varastot'])) {
				$varasto['checked'] = 'checked';
			}
			else {
				$varasto['checked'] = '';
			}
		}
		else {
			//requesista ei tullut valittuja varastoja
			//valitaan käyttäjän oletusvarasto jos asetettu
			if(!empty($kukarow['oletus_varasto'])) {
				if($kukarow['oletus_varasto'] == $varasto['tunnus']) {
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
		if(!empty($request) and in_array($inventointilaji['tunnus'], $request['valitut_inventointilajit'])) {
			$inventointilaji['checked'] = 'checked';
		}
		else {
			$inventointilaji['checked'] = '';
		}
		$inventointilajit[] = $inventointilaji;
	}

	return $inventointilajit;
}
