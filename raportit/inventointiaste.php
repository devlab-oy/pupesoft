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

	#raportti_table {
		display: block;
		clear: both;
	}

	.inventointilajeittain_tr {
		display: none;
	}
	.inventointilajeittain_tr_not_hidden {
		
	}

	.inventointilajit_wrapper {
		display: none;
	}

	.tapahtumat_table {
		display: none;
	}

	.tapahtumat_table_not_hidden {
	}

	.tapahtumat_wrapper {
		display: none;
	}

	.tapahtumat_wrapper_not_hidden {
	}

	#footer {
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

		bind_kuukausittain_tr();
		bind_inventointilajeittain_tr();
	});

	function bind_kuukausittain_tr() {
		$('.kuukausittain_tr').click(function() {
			var kuukausittain_key = $(this).find('.kuukausittain_key').val();
			var children = $(this).parent().find('.' + kuukausittain_key);
			if ($(this).hasClass('not_hidden')) {
				$(children).addClass('inventointilajeittain_tr');
				$(children).removeClass('inventointilajeittain_tr_not_hidden');
				$(this).removeClass('not_hidden');
			}
			else {
				$(children).removeClass('inventointilajeittain_tr');
				$(children).addClass('inventointilajeittain_tr_not_hidden');
				$(this).addClass('not_hidden');
			}
		});
	}

	function bind_inventointilajeittain_tr() {
		$('.inventointilajeittain_tr').click(function() {
			var children = $(this).next();
			if ($(this).hasClass('not_hidden')) {
				$(children).addClass('tapahtumat_wrapper');
				$(children).removeClass('tapahtumat_wrapper_not_hidden');
				$(children).find('.tapahtumat_table').addClass('tapahtumat_table');
				$(children).find('.tapahtumat_table').removeClass('tapahtumat_table_not_hidden');
				$(this).removeClass('not_hidden');
			}
			else {
				$(children).removeClass('tapahtumat_wrapper');
				$(children).addClass('tapahtumat_wrapper_not_hidden');
				$(children).find('.tapahtumat_table').removeClass('tapahtumat_table');
				$(children).find('.tapahtumat_table').addClass('tapahtumat_table_not_hidden');
				$(this).addClass('not_hidden');
			}
		});
	}

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

		if ($('.varastot:checked').length === 0) {
			ok = false;
			alert($('#valitse_varasto_error_message').val());
		}

		var aika_arvot = $(".alku_aika").map(function() {
			return $(this).val();
		}).get();
		var alku_aika_not_empty_values = aika_arvot.filter(function(v) {
			if (v === '') {
				return 0;
			}
			else {
				return 1;
			}
		});

		aika_arvot = $(".loppu_aika").map(function() {
			return $(this).val();
		}).get();
		var loppu_aika_not_empty_values = aika_arvot.filter(function(v) {
			if (v === '') {
				return 0;
			}
			else {
				return 1;
			}
		});

		if ($('#valittu_tilikausi').val() === '' || (alku_aika_not_empty_values.length !== 3 && loppu_aika_not_empty_values.length !== 3)) {
			ok = false;
			alert($('#valitse_aika_error_message').val());
		}

		return ok;
	}

</script>

<?php

$request = array(
	'tee'						 => $tee,
	'ppa'						 => $ppa,
	'kka'						 => $kka,
	'vva'						 => $vva,
	'ppl'						 => $ppl,
	'kkl'						 => $kkl,
	'vvl'						 => $vvl,
	'valittu_tilikausi'			 => $valittu_tilikausi,
	'yhtio'						 => $yhtio,
	'valitut_varastot'			 => $varastot,
	'valitut_inventointilajit'	 => $inventointilajit,
);
echo "<div id='wrapper'>";
init($request);

echo "<div id='table_div'>";

echo_arvot($request);
echo_kayttoliittyma($request);

echo "</div>";
echo "<br/>";
echo "<br/>";

if ($request['tee'] == 'aja_raportti') {
	$rivit = hae_inventoinnit($request);
	$rivit = kasittele_rivit($rivit);

	echo_raportin_tulokset($rivit);
}

echo "</div>";

echo "<div id='footer'>";
require ("../inc/footer.inc");
echo "</div>";

function init(&$request) {
	echo "<input type='hidden' id='valitse_varasto_error_message' value='".t("Valitse varasto")."' />";
	echo "<input type='hidden' id='valitse_aika_error_message' value='".t("Syötä validi aika")."' />";

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

	$request['tilikaudet'] = hae_tilikaudet($request);
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
		//ensimmäinen sivulataus, requestista ei ole tullut valittuja inventointilajeja, rajataan käyttöliittymään esivalittujen inventointilajien perusteella
		foreach ($request['inventointilajit'] as $inventointilaji) {
			if (!empty($inventointilaji['checked'])) {
				$request['valitut_inventointilajit'][] = $inventointilaji['selite'];
			}
		}
	}
}

function echo_arvot(&$request) {

	$inventointien_lukumaara_12kk = hae_inventointien_lukumaara($request, '12kk');
	$inventointien_lukumaara_tilikausi = hae_inventointien_lukumaara($request, 'tilikausi');
	$varaston_hyllypaikkojen_lukumaara = hae_varaston_hyllypaikkojen_lukumaara($request);

	echo "<input type='hidden' id='inventointien_lukumaara_12kk' value='{$inventointien_lukumaara_12kk['inventointien_lukumaara']}' />";
	echo "<input type='hidden' id='inventointien_lukumaara_tilikausi' value='{$inventointien_lukumaara_tilikausi['inventointien_lukumaara']}' />";
	echo "<input type='hidden' id='varaston_hyllypaikkojen_lukumaara' value='{$varaston_hyllypaikkojen_lukumaara['varaston_hyllypaikkojen_lukumaara']}' />";

	//haetaan tämän tilikauden jäljellä olevien työpäivien lukumäärä
	foreach ($request['tilikaudet'] as $tilikausi) {
		if ($tilikausi['tilikausi_alku'] <= date('Y-m-d') and $tilikausi['tilikausi_loppu'] > date('Y-m-d')) {
			$tyopaivien_lukumaara = hae_tyopaivien_lukumaara($tilikausi['tilikausi_alku'], $tilikausi['tilikausi_loppu']);
			break;
		}
	}
	if (empty($tyopaivien_lukumaara)) {
		//fail safe, jos kannasta ei jostain syystä löydy tilikautta tälle ajanhetkelle
		$tyopaivien_lukumaara = hae_tyopaivien_lukumaara(date('Y-m-d'), date('Y-12-31'));
	}

	$inventointeja_per_paiva = ($varaston_hyllypaikkojen_lukumaara['varaston_hyllypaikkojen_lukumaara'] - $inventointien_lukumaara_tilikausi['inventointien_lukumaara']) / $tyopaivien_lukumaara;

	echo "<table>";
	echo "<tr>";
	echo "<th>".t("Hyllypaikkojen inventointeja pitää suorittaa per päivä")."</th>";
	echo "<td>".round($inventointeja_per_paiva, 0)."</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Hyllypaikkoja valituissa varastoissa")."</th>";
	echo "<td>{$varaston_hyllypaikkojen_lukumaara['varaston_hyllypaikkojen_lukumaara']}</td>";
	echo "</tr>";
	echo "</table>";
}

function echo_raportin_tulokset($rivit) {
	global $kukarow, $yhtiorow;

	echo "<table id='raportti_table'>";

	echo "<tr>";
	echo "<th>".t("Ajanjakso")."</th>";
	echo "<th>".t("Inventointilajit")."</th>";
	echo "<th>".t("Inventoitu positiivista")." {$yhtiorow['valkoodi']}</th>";
	echo "<th>".t("Inventoitu negatiivista")." {$yhtiorow['valkoodi']}</th>";
	echo "<th>".t("Inventointi erotus")." {$yhtiorow['valkoodi']}</th>";
	echo "</tr>";

	foreach ($rivit as $rivi_index => $rivi) {
		echo_table_first_layer($rivi_index, $rivi);
	}

	echo "</table>";
}

function echo_table_first_layer($rivi_index, $rivi) {
	echo "<tr class='kuukausittain_tr aktiivi'>";

	echo "<td>";
	echo "<input type='hidden' class='kuukausittain_key' value='{$rivi_index}'/>";
	echo $rivi_index;
	echo "</td>";

	echo "<td></td>";

	echo "<td>";
	echo (isset($rivi['kuukausittain_luvut']['pos']) ? round($rivi['kuukausittain_luvut']['pos'], 2) : 0);
	echo "</td>";

	echo "<td>";
	echo (isset($rivi['kuukausittain_luvut']['neg']) ? round($rivi['kuukausittain_luvut']['neg'], 2) : 0);
	echo "</td>";

	echo "<td>";
	echo (isset($rivi['kuukausittain_luvut']['ero']) ? round($rivi['kuukausittain_luvut']['ero'], 2) : 0);
	echo "</td>";

	echo "</tr>";

	echo_table_second_layer($rivi, $rivi_index);
}

function echo_table_second_layer($rivi, $rivi_index) {
	foreach ($rivi['inventointilajit'] as $inventointilaji) {
		echo "<tr class='inventointilajeittain_tr aktiivi {$rivi_index}'>";

		echo "<td></td>";
		echo "<td>";
		//$inventointilaji pitää sisällään ainoastaan tietyn inventointilajin inventointeja, tällöin voimme printata lajin nimityksen ensimmäisestä alkiosta
		echo $inventointilaji['tapahtumat'][0]['inventointilaji'];
		echo "</td>";

		echo "<td>";
		echo (isset($inventointilaji['inventointilajeittain_luvut']['pos']) ? round($inventointilaji['inventointilajeittain_luvut']['pos'], 2) : 0);
		echo "</td>";

		echo "<td>";
		echo (isset($inventointilaji['inventointilajeittain_luvut']['neg']) ? round($inventointilaji['inventointilajeittain_luvut']['neg'], 2) : 0);
		echo "</td>";

		echo "<td>";
		echo (isset($inventointilaji['inventointilajeittain_luvut']['ero']) ? round($inventointilaji['inventointilajeittain_luvut']['ero'], 2) : 0);
		echo "</td>";

		echo "</tr>";

		echo "<tr class='tapahtumat_wrapper'>";
		echo "<td></td>";
		echo "<td></td>";
		echo "<td colspan='3'>";
		echo_table_third_layer($inventointilaji);
		echo "</td>";
		echo "</tr>";
	}
}

function echo_table_third_layer($inventointilaji) {
	echo "<table class='tapahtumat_table'>";

	echo "<tr>";
	echo "<th>".t("Tuoteno")."</th>";
	echo "<th>".t("Nimitys")."</th>";
	echo "<th>".t("Hyllypaikka")."</th>";
	echo "<th>".t("Keräysvyöhyke")."</th>";
	echo "<th>".t("Kpl")."</th>";
	echo "<th>".t("Rahavaikutus")."</th>";
	echo "<th>".t("Selite")."</th>";
	echo "<th>".t("Kuka inventoi")."</th>";
	echo "<th>".t("Koska inventoitiin")."</th>";
	echo "</tr>";

	foreach ($inventointilaji['tapahtumat'] as $tapahtuma) {
		echo "<tr>";
		echo "<td>{$tapahtuma['tuoteno']}</td>";
		echo "<td>{$tapahtuma['tuote_nimitys']}</td>";
		echo "<td>{$tapahtuma['hyllypaikka']}</td>";
		echo "<td>{$tapahtuma['keraysvyohyke_nimitys']}</td>";
		echo "<td>{$tapahtuma['kpl']}</td>";
		echo "<td>".round($tapahtuma['inventointi_poikkeama_eur'], 2)."</td>";
		echo "<td>{$tapahtuma['selite']}</td>";
		echo "<td>{$tapahtuma['laatija']}</td>";
		echo "<td>{$tapahtuma['laadittu']}</td>";
		echo "</tr>";
	}

	echo "</table>";
}

function echo_kayttoliittyma($request) {
	global $kukarow;

	echo "<form method='POST' action='' name='inventointiaste_form'>";
	echo "<input type='hidden' action = '' name='tee' value='aja_raportti' />";

	echo "<table>";

	echo "<tr>";
	echo "<th>", t("Syötä alkupäivämäärä"), " (", t("pp-kk-vvvv"), ")</th>";
	echo "<td><input type='text' name='ppa' id='ppa' class='alku_aika' value='{$request['ppa']}' size='3'>";
	echo "<input type='text' name='kka' id='kka' class='alku_aika' value='{$request['kka']}' size='3'>";
	echo "<input type='text' name='vva' id='vva' class='alku_aika' value='{$request['vva']}' size='5'></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>", t("Syötä loppupäivämäärä"), " (", t("pp-kk-vvvv"), ")</th>";
	echo "<td><input type='text' name='ppl' id='ppl' class='loppu_aika' value='{$request['ppl']}' size='3'>";
	echo "<input type='text' name='kkl' id='kkl' class='loppu_aika' value='{$request['kkl']}' size='3'>";
	echo "<input type='text' name='vvl' id='vvl' class='loppu_aika' value='{$request['vvl']}' size='5'></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Tilikausi")."</th>";
	echo "<td>";
	echo "<select id='valittu_tilikausi' name='valittu_tilikausi'>";
	foreach ($request['tilikaudet'] as $tilikausi) {
		echo "<option value='{$tilikausi['tunnus']}' {$tilikausi['selected']}>{$tilikausi['tilikausi']}</option>";
	}
	echo "</select>";
	echo t("Tilikauden valinta yliajaa ylläolevan päivämäärä valinnan");
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
		echo "<input class='inventointilajit' type='checkbox' name='inventointilajit[]' value='{$inventointilaji['selite']}' {$inventointilaji['checked']} />";
		echo " {$inventointilaji['selite']}";
		echo "<br/>";
	}
	echo "</td>";
	echo "</tr>";

	echo "</table>";

	echo "<input type='submit' value='".t("Hae")."' onclick='return tarkista();' />";

	echo "</form>";
}

function hae_inventoinnit(&$request) {
	global $kukarow;

	parsi_paivat($request);

	$query = "	SELECT DATE(tapahtuma.laadittu) laadittu_pvm,
				tapahtuma.laadittu,
				( tapahtuma.kpl * tapahtuma.hinta ) AS inventointi_poikkeama_eur,
				tapahtuma.selite,
				substring( tapahtuma.selite, ( length(tapahtuma.selite)-locate( '>rb<',reverse(tapahtuma.selite)) ) +2 ) as inventointilaji,
				tapahtuma.tuoteno,
				tuote.nimitys AS tuote_nimitys,
				tapahtuma.kpl,
				Concat_ws('-', tapahtuma.hyllyalue, tapahtuma.hyllynro, tapahtuma.hyllytaso, tapahtuma.hyllyvali) AS hyllypaikka,
				tapahtuma.laatija,
				keraysvyohyke.nimitys AS keraysvyohyke_nimitys
				FROM   tuotepaikat
				JOIN tuote
				ON ( tuote.yhtio = tuotepaikat.yhtio
					AND tuote.tuoteno = tuotepaikat.tuoteno
					AND tuote.ei_saldoa = ''
					AND tuote.status = 'A' )
				JOIN tapahtuma
				ON ( tapahtuma.yhtio = tuotepaikat.yhtio
					AND tapahtuma.tuoteno = tuotepaikat.tuoteno
					AND tapahtuma.hyllyalue = tuotepaikat.hyllyalue
					AND tapahtuma.hyllynro = tuotepaikat.hyllynro
					AND tapahtuma.hyllyvali = tuotepaikat.hyllyvali
					AND tapahtuma.hyllytaso = tuotepaikat.hyllytaso
					AND tapahtuma.laadittu BETWEEN '{$request['raportti_alku_aika']}' AND '{$request['raportti_loppu_aika']}'
					AND tapahtuma.laji = 'Inventointi'
					{$request['inventointilaji_rajaus']}
					AND tapahtuma.kpl != 0 )
				JOIN varaston_hyllypaikat AS vh
				ON ( vh.yhtio = tapahtuma.yhtio
					AND vh.hyllyalue = tapahtuma.hyllyalue
					AND vh.hyllynro = tapahtuma.hyllynro
					AND vh.hyllytaso = tapahtuma.hyllytaso
					AND vh.hyllyvali = tapahtuma.hyllyvali )
				JOIN keraysvyohyke
				ON ( keraysvyohyke.yhtio = vh.yhtio
					AND keraysvyohyke.tunnus = vh.keraysvyohyke )
				JOIN varastopaikat
				ON ( varastopaikat.yhtio = tuotepaikat.yhtio
					AND concat(rpad(upper(varastopaikat.alkuhyllyalue), 5, '0'),lpad(upper(varastopaikat.alkuhyllynro), 5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
					AND concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'),lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
					AND varastopaikat.tunnus IN (".implode(', ', $request['valitut_varastot']).") )
				WHERE tuotepaikat.yhtio = '{$kukarow['yhtio']}'
				ORDER BY laadittu_pvm ASC";
	$result = pupe_query($query);

	$rivit = array();
	while ($rivi = mysql_fetch_assoc($result)) {
		$rivit[] = $rivi;
	}

	return $rivit;
}

function parsi_paivat(&$request) {

	if (!empty($request['valittu_tilikausi'])) {
		$tilikausi_temp = search_array_key_for_value_recursive($request['tilikaudet'], 'tunnus', $request['valittu_tilikausi']);
		//funktion on tarkoitus palauttaa ainoastaan yksi tilikausi, siksi voimme viitata indeksillä
		$request['raportti_alku_aika'] = $tilikausi_temp[0]['tilikausi_alku'];
		$request['raportti_loppu_aika'] = $tilikausi_temp[0]['tilikausi_loppu'];
	}
	else {
		//TODO onko formista tulevilla alku loppu ajoilla jotain maksimi arvoja? esim saa hakea inventointeja korkeintaan vuoden päähän tjsp
		$request['raportti_alku_aika'] = $request['vva'].'-'.$request['kka'].'-'.$request['ppa'];
		$request['raportti_loppu_aika'] = $request['vvl'].'-'.$request['kkl'].'-'.$request['ppl'];
	}
}

function kasittele_rivit($rivit) {

	$rivit_temp = array();
	foreach ($rivit as &$rivi) {
		if (!empty($rivi['inventointilaji'])) {
			//@TODO siirrä preg_replace mysql:n puolelle
			$inventointilaji = preg_replace('/[^a-zA-Z0-9]/', '_', $rivi['inventointilaji']);
		}
		else {
			$inventointilaji = "tuntematon";
		}
		$aika = date('Y-m', strtotime($rivi['laadittu_pvm']));

		//kerätään kuukausittain luvut suoraan kuukauden alle
		keraa_kuukausittain_luvut($rivi, $rivit_temp, $inventointilaji, $aika);

		//kerätään inventointilajeittain luvut suoraan inventointilajin alle
		keraa_inventointilajeittain_luvut($rivi, $rivit_temp, $inventointilaji, $aika);

		$rivit_temp[$aika]['inventointilajit'][$inventointilaji]['tapahtumat'][] = $rivi;
	}

	return $rivit_temp;
}

function keraa_kuukausittain_luvut(&$rivi, &$rivit_temp, $inventointilaji, $aika) {
	if ($rivi['inventointi_poikkeama_eur'] > 0) {
		if (!isset($rivit_temp[$aika]['kuukausittain_luvut']['pos'])) {
			$rivit_temp[$aika]['kuukausittain_luvut']['pos'] = $rivi['inventointi_poikkeama_eur'];
		}
		else {
			$rivit_temp[$aika]['kuukausittain_luvut']['pos'] += $rivi['inventointi_poikkeama_eur'];
		}
	}
	else {
		if (!isset($rivit_temp[$aika]['kuukausittain_luvut']['neg'])) {
			$rivit_temp[$aika]['kuukausittain_luvut']['neg'] = $rivi['inventointi_poikkeama_eur'];
		}
		else {
			$rivit_temp[$aika]['kuukausittain_luvut']['neg'] += $rivi['inventointi_poikkeama_eur'];
		}
	}

	if (!isset($rivit_temp[$aika]['kuukausittain_luvut']['ero'])) {
		$rivit_temp[$aika]['kuukausittain_luvut']['ero'] = $rivi['inventointi_poikkeama_eur'];
	}
	else {
		$rivit_temp[$aika]['kuukausittain_luvut']['ero'] += $rivi['inventointi_poikkeama_eur'];
	}
}

function keraa_inventointilajeittain_luvut(&$rivi, &$rivit_temp, $inventointilaji, $aika) {
	if ($rivi['inventointi_poikkeama_eur'] > 0) {
		if (!isset($rivit_temp[$aika]['inventointilajit'][$inventointilaji]['inventointilajeittain_luvut']['pos'])) {
			$rivit_temp[$aika]['inventointilajit'][$inventointilaji]['inventointilajeittain_luvut']['pos'] = $rivi['inventointi_poikkeama_eur'];
		}
		else {
			$rivit_temp[$aika]['inventointilajit'][$inventointilaji]['inventointilajeittain_luvut']['pos'] += $rivi['inventointi_poikkeama_eur'];
		}
	}
	else {
		if (!isset($rivit_temp[$aika]['inventointilajit'][$inventointilaji]['inventointilajeittain_luvut']['neg'])) {
			$rivit_temp[$aika]['inventointilajit'][$inventointilaji]['inventointilajeittain_luvut']['neg'] = $rivi['inventointi_poikkeama_eur'];
		}
		else {
			$rivit_temp[$aika]['inventointilajit'][$inventointilaji]['inventointilajeittain_luvut']['neg'] += $rivi['inventointi_poikkeama_eur'];
		}
	}

	if (!isset($rivit_temp[$aika]['inventointilajit'][$inventointilaji]['inventointilajeittain_luvut']['ero'])) {
		$rivit_temp[$aika]['inventointilajit'][$inventointilaji]['inventointilajeittain_luvut']['ero'] = $rivi['inventointi_poikkeama_eur'];
	}
	else {
		$rivit_temp[$aika]['inventointilajit'][$inventointilaji]['inventointilajeittain_luvut']['ero'] += $rivi['inventointi_poikkeama_eur'];
	}
}

function hae_inventointien_lukumaara(&$request, $aikavali_tyyppi = '') {
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
		$inventointilaji_rajaus .= " )";

		//laitetaan inventoinitilaji_rajaus requestiin talteen myöhempää käyttö varten
		$request['inventointilaji_rajaus'] = $inventointilaji_rajaus;
	}

	$query = "	SELECT count(*) as inventointien_lukumaara
				FROM tuotepaikat
				JOIN tuote
				ON ( tuote.yhtio = tuotepaikat.yhtio
					AND tuote.tuoteno = tuotepaikat.tuoteno
					AND tuote.ei_saldoa = ''
					AND tuote.status = 'A' )
				JOIN tapahtuma
				ON ( tapahtuma.yhtio = tuotepaikat.yhtio
					AND tapahtuma.tuoteno = tuotepaikat.tuoteno
					AND tapahtuma.hyllyalue = tuotepaikat.hyllyalue
					AND tapahtuma.hyllynro = tuotepaikat.hyllynro
					AND tapahtuma.hyllyvali = tuotepaikat.hyllyvali
					AND tapahtuma.hyllytaso = tuotepaikat.hyllytaso
					AND tapahtuma.laadittu BETWEEN '{$request['alku_aika']}' AND '{$request['loppu_aika']}'
					AND tapahtuma.laji = 'Inventointi'
					{$inventointilaji_rajaus} )
				JOIN varastopaikat
				ON ( varastopaikat.yhtio = tuotepaikat.yhtio
					AND concat(rpad(upper(varastopaikat.alkuhyllyalue), 5, '0'),lpad(upper(varastopaikat.alkuhyllynro), 5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
					AND concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'),lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
					AND varastopaikat.tunnus IN (".implode(', ', $request['valitut_varastot']).") )
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
					AND varastopaikat.tunnus IN (".implode(', ', $request['valitut_varastot']).") )
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
				WHERE yhtio = '{$kukarow['yhtio']}'
				ORDER BY tilikausi_alku DESC";
	$result = pupe_query($query);

	$tilikaudet = array();
	while ($tilikausi = mysql_fetch_assoc($result)) {
		if (!empty($request) and $request['tilikausi'] == $tilikausi['tunnus']) {
			$tilikausi['selected'] = 'selected';
		}
		else {
			//jos requestista ei tule valittua tilikautta, esivalitaan tämän hetkinen tilikausi
			if ($tilikausi['tilikausi_alku'] <= date('Y-m-d') and $tilikausi['tilikausi_loppu'] > date('Y-m-d')) {
				$tilikausi['selected'] = 'selected';
			}
			else {
				$tilikausi['selected'] = '';
			}
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
				if (in_array($inventointilaji['selite'], $request['valitut_inventointilajit'])) {
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

		$inventointilaji['array_key'] = preg_replace('/[^a-zA-Z0-9]/', '_', $inventointilaji['selite']);
		$inventointilajit[] = $inventointilaji;
	}

	return $inventointilajit;
}
