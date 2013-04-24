<?php

require('../inc/parametrit.inc');

if ($tee == 'lataa_tiedosto') {
	$filepath = "/tmp/".$tmpfilenimi;
	if (file_exists($filepath)) {
		readfile($filepath);
		unlink($filepath);
	}
	exit;
}

//AJAX requestit tänne
if ($ajax_request) {
	if ($tulosta_tyolista) {
		$request = array(
			'lasku_tunnukset' => $lasku_tunnukset,
		);
		$tyomaaraykset = hae_tyomaaraykset($request);
		lasku.tunnus as lasku_tunnus,
				lasku.ytunnus as asiakas_ytunnus,
				lasku.nimi as asiakas_nimi,
				kohde.tunnus as kohde_tunnus,
				kohde.nimi as kohde_nimi,
				paikka.nimi as paikka_nimi,
				paikka.olosuhde as paikka_olosuhde,
				laite.tuoteno,
				laite.oma_numero,
				laite.sijainti as laite_sijainti,
				lasku.toimaika,
				a1.selite as tyojonokoodi,
				a1.selitetark as tyojono,
				a2.selitetark as tyostatus,
				a2.selitetark_2 as tyostatusvari

		$header_values = array(
			'kohde_nimi'	 => array(
				'header' => t('Kohteen nimi'),
				'order'	 => 1
			),
			'paikka_nimi'	 => array(
				'header' => t('Paikan nimi'),
				'order'	 => 10
			),
			'paikka_olosuhde'	 => array(
				'header' => t('Olosuhde'),
				'order'	 => 20
			),
			'tuoteno'	 => array(
				'header' => t('Tuotenumero'),
				'order'	 => 9
			),
			'oma_numero'	 => array(
				'header' => t('Oma numero'),
				'order'	 => 0
			),
			'laite_sijainti'	 => array(
				'header' => t('Laitteen tarkempi sijainti'),
				'order'	 => 11
			),
			'toimaika'	 => array(
				'header' => t('Huoltoaika'),
				'order'	 => 30
			),
			'tyojono'	 => array(
				'header' => t('Työjono'),
				'order'	 => 40
			),
			'tyostatus'	 => array(
				'header' => t('Työstatus'),
				'order'	 => 50
			),
		);
	}
	exit;
}

echo "<font class='head'>".t("Työjono2").":</font>";
echo "<hr/>";
echo "<br/>";
?>
<style>
	.laite_table_tr_hidden {
		display: none;
	}
	.laite_table_tr {
		background-color: #DADDDE;
	}

	.laite_tr {
		background-color: #DADDDE;
	}
</style>
<script>
	$(document).ready(function() {
		bind_kohde_tr_click();

		bind_tulosta_tyolista_button();
	});

	function bind_kohde_tr_click() {
		$('.kohde_tr, .kohde_tr_hidden').click(function(event) {
			if (event.target.nodeName !== 'BUTTON') {
				var laite_table_tr = $(this).next();
				if ($(this).hasClass('kohde_tr_hidden')) {
					$(this).addClass('kohde_tr');
					$(this).removeClass('kohde_tr_hidden');

					$(laite_table_tr).addClass('laite_table_tr');
					$(laite_table_tr).removeClass('laite_table_tr_hidden');
				}
				else {
					$(this).removeClass('kohde_tr');
					$(this).addClass('kohde_tr_hidden');

					$(laite_table_tr).removeClass('laite_table_tr');
					$(laite_table_tr).addClass('laite_table_tr_hidden');
				}
			}
		});
	}

	function bind_tulosta_tyolista_button() {
		$('.tulosta_tyolista').click(function(event) {
			event.preventDefault();
			var lasku_tunnukset = $(this).parent().parent().find('.lasku_tunnukset').val();
			$.ajax({
				async: true,
				dataType: 'json',
				type: 'GET',
				data: {
					lasku_tunnukset: lasku_tunnukset
				},
				url: 'tyojono2.php?ajax_request=1&no_head=yes&tulosta_tyolista=1'
			}).done(function(data) {
				if (console && console.log) {
					console.log('Onnas');
					console.log(data);
				}
			});
		});
	}
</script>

<?php

$request = array(
);

$request['tyomaaraykset'] = hae_tyomaaraykset($request);

$request['tyomaaraykset'] = kasittele_tyomaaraykset($request['tyomaaraykset']);

$request['tyojonot'] = hae_tyojonot($request);

$request['tyostatukset'] = hae_tyostatukset($request);

echo_tyojono_table($request);

function hae_tyomaaraykset($request) {
	global $kukarow, $yhtiorow;

	$lasku_where = "";
	if (!empty($request['lasku_tunnukset'])) {
		$lasku_where = "AND lasku.tunnus IN ('".implode("','", $request['lasku_tunnukset'])."')";
	}

	$query = "	SELECT
				lasku.tunnus as lasku_tunnus,
				lasku.ytunnus as asiakas_ytunnus,
				lasku.nimi as asiakas_nimi,
				kohde.tunnus as kohde_tunnus,
				kohde.nimi as kohde_nimi,
				paikka.nimi as paikka_nimi,
				paikka.olosuhde as paikka_olosuhde,
				laite.tuoteno,
				laite.oma_numero,
				laite.sijainti as laite_sijainti,
				lasku.toimaika,
				a1.selite as tyojonokoodi,
				a1.selitetark as tyojono,
				a2.selitetark as tyostatus,
				a2.selitetark_2 as tyostatusvari
				FROM lasku
				JOIN tyomaarays
				ON ( tyomaarays.yhtio = lasku.yhtio
					AND tyomaarays.otunnus = lasku.tunnus )
				JOIN laskun_lisatiedot
				ON ( laskun_lisatiedot.yhtio = lasku.yhtio
					AND laskun_lisatiedot.otunnus = lasku.tunnus )
				JOIN tilausrivi
				ON ( tilausrivi.yhtio = lasku.yhtio
					AND tilausrivi.otunnus = lasku.tunnus )
				JOIN tilausrivin_lisatiedot
				ON ( tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
					AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus )
				JOIN laite
				ON ( laite.yhtio = tilausrivin_lisatiedot.yhtio
					AND laite.tunnus = tilausrivin_lisatiedot.asiakkaan_positio )
				JOIN paikka
				ON ( paikka.yhtio = laite.yhtio
					AND paikka.tunnus = laite.paikka )
				JOIN kohde
				ON ( kohde.yhtio = paikka.yhtio
					AND kohde.tunnus = paikka.kohde )
				LEFT JOIN avainsana a1
				ON ( a1.yhtio = tyomaarays.yhtio
					AND a1.laji = 'TYOM_TYOJONO'
					AND a1.selite = tyomaarays.tyojono )
				LEFT JOIN avainsana a2
				ON ( a2.yhtio = tyomaarays.yhtio
					AND a2.laji = 'TYOM_TYOSTATUS'
					AND a2.selite = tyomaarays.tyostatus )
				WHERE lasku.yhtio = '{$kukarow['yhtio']}'
				{$lasku_where}
				AND lasku.tila = 'A'";
	$result = pupe_query($query);

	$tyomaaraykset = array();
	while ($tyomaarays = mysql_fetch_assoc($result)) {
		$tyomaaraykset[] = $tyomaarays;
	}

	return $tyomaaraykset;
}

function kasittele_tyomaaraykset($tyomaaraykset) {
	global $kukarow, $yhtiorow;

	$tyomaaraykset_temp = array();
	foreach ($tyomaaraykset as $tyomaarays) {
		if (!isset($tyomaaraykset_temp[$tyomaarays['kohde_tunnus']])) {
			$tyomaaraykset_temp[$tyomaarays['kohde_tunnus']] = array(
				'asiakas_ytunnus'	 => $tyomaarays['asiakas_ytunnus'],
				'asiakas_nimi'		 => $tyomaarays['asiakas_nimi'],
				'kohde_tunnus'		 => $tyomaarays['kohde_tunnus'],
				'kohde_nimi'		 => $tyomaarays['kohde_nimi'],
				'toimaika'			 => $tyomaarays['toimaika'],
			);
		}
		$tyomaaraykset_temp[$tyomaarays['kohde_tunnus']]['lasku_tunnukset'][] = $tyomaarays['lasku_tunnus'];
		$tyomaaraykset_temp[$tyomaarays['kohde_tunnus']]['tyomaaraykset'][] = $tyomaarays;
	}

	return $tyomaaraykset_temp;
}

function echo_tyojono_table($request = array()) {
	global $kukarow, $yhtiorow;

	echo "<table class='display dataTable'>";
	echo "<thead>";

	echo "<tr>
			<th>".t("Työm").".<br>".t("Viite")."</th>
			<th>".t("Ytunnus")."<br>".t("Asiakas")."</th>
			<th>".t("Kohde")."</th>
			<th>".t("Toimitetaan")."</th>
			<th>".t("Työjono")."/<br>".t("Työstatus")."</th>
			<th></th>
		</tr>";

	echo "<tr>";

	echo "<td valign='top'><input type='text' size='10' class='search_field' name='search_tyomaarays_haku'></td>";

	echo "<td valign='top'><input type='text' size='10' class='search_field' name='search_asiakasnimi_haku'></td>";
	echo "<td valign='top'><input type='text' size='10' class='search_field' name='search_kohde_haku'></td>";
	echo "<td valign='top'><input type='text' size='10' class='search_field' name='search_toimitetaan_haku'></td>";

	echo "<td>";

	echo "<select class='tyojono_sort'>";
	foreach ($request['tyojonot'] as $tyojono) {
		echo "<option value='{$tyojono['value']}'>{$tyojono['text']}</option>";
	}
	echo "</select>";
	echo "<br/>";
	echo "<select class='tyostatus_sort'>";
	foreach ($request['tyostatukset'] as $tyostatus) {
		echo "<option value='{$tyostatus['value']}'>{$tyostatus['text']}</option>";
	}
	echo "</select>";
	echo "</td>";

	echo "<td>";
	echo "</td>";

	echo "</tr>";

	echo "</thead>";

	echo "<tbody>";

	foreach ($request['tyomaaraykset'] as $tyomaarays) {
		echo "<tr class='kohde_tr_hidden aktiivi'>";

		echo "<td>";
		echo "<input type='hidden' class='lasku_tunnukset' value='".implode(',', $tyomaarays['lasku_tunnukset'])."' />";
		echo implode('<br/>', $tyomaarays['lasku_tunnukset']);
		echo "</td>";

		echo "<td>";
		echo $tyomaarays['asiakas_ytunnus'].'<br/>'.$tyomaarays['asiakas_nimi'];
		echo "</td>";

		echo "<td>";
		echo $tyomaarays['kohde_nimi'];
		echo "</td>";

		echo "<td>";
		echo $tyomaarays['toimaika'];
		echo "</td>";

		if (!empty($tyomaarays["tyostatusvari"])) {
			$td_taustavari = "style='background-color: {$tyomaarays['tyostatusvari']};'";
		}
		else {
			$td_taustavari = "";
		}
		echo "<td {$td_taustavari}>";

		echo "<select class='tyojono_muutos'>";
		foreach ($request['tyojonot'] as $tyojono) {
			$sel = $tyomaarays['tyojono'] == $tyojono['selitetark'] ? ' SELECTED' : '';
			echo "<option value='{$tyojono['selite']}' {$sel}>{$tyojono['selitetark']}</option>";
		}
		echo "</select>";
		echo "<br/>";
		echo "<select class='tyostatus_muutos'>";
		foreach ($request['tyostatukset'] as $tyostatus) {
			$sel = $tyomaarays['tyostatus'] == $tyostatus['selitetark'] ? ' SELECTED' : '';
			echo "<option value='{$tyostatus['selite']}' {$sel}>{$tyostatus['selitetark']}</option>";
		}
		echo "</select>";

		echo "</td>";

		echo "<td>";
		echo "<button class='tulosta_tyolista'>".t("Tulosta työlistat")."</button>";
		echo "</td>";

		echo "</tr>";

		echo_laitteet_table($request, $tyomaarays['tyomaaraykset']);
	}

	echo "</tbody>";
	echo "</table>";
}

function echo_laitteet_table($request, $laitteet) {
	global $kukarow, $yhtiorow;

	echo "<tr class='laite_table_tr_hidden'>";
	echo "<td colspan='1'>";
	echo "</td>";
	echo "<td colspan='4'>";
	echo "<table class='laite_table'>";
	echo "<tr>";
	echo "<th>".t("#")."</th>";
	echo "<th>".t("Oma numero")."</th>";
	echo "<th>".t("Laite")."</th>";
	echo "<th>".t("Paikka")."</th>";
	echo "<th>".t("Sijainti")."</th>";
	echo "<th>".t("Työjono")."/<br>".t("Työstatus")."</th>";
	echo "</tr>";

	foreach ($laitteet as $laite) {
		echo "<tr class='laite_tr'>";

		echo "<td>";
		echo "<input type='checkbox' />";
		echo "</td>";

		echo "<td>";
		echo $laite['oma_numero'];
		echo "</td>";

		echo "<td>";
		echo $laite['tuoteno'];
		echo "</td>";

		echo "<td>";
		echo $laite['paikka_nimi'];
		echo "</td>";

		echo "<td>";
		echo $laite['sijainti'];
		echo "</td>";

		if (!empty($laite["tyostatusvari"])) {
			$td_taustavari = "style='background-color: {$laite['tyostatusvari']};'";
		}
		else {
			$td_taustavari = "";
		}
		echo "<td {$td_taustavari}>";

		echo "<select class='tyojono_muutos'>";
		foreach ($request['tyojonot'] as $tyojono) {
			$sel = $laite['tyojono'] == $tyojono['selitetark'] ? ' SELECTED' : '';
			echo "<option value='{$tyojono['selite']}' {$sel}>{$tyojono['selitetark']}</option>";
		}
		echo "</select>";
		echo "<br/>";
		echo "<select class='tyostatus_muutos'>";
		foreach ($request['tyostatukset'] as $tyostatus) {
			$sel = $laite['tyostatus'] == $tyostatus['selitetark'] ? ' SELECTED' : '';
			echo "<option value='{$tyostatus['selite']}' {$sel}>{$tyostatus['selitetark']}</option>";
		}
		echo "</select>";

		echo "</td>";

		echo "</tr>";
	}

	echo "</table>";
	echo "</td>";
	echo "</tr>";
}

function hae_tyojonot($request = array()) {
	global $kukarow, $yhtiorow;

	$tyojono_result = t_avainsana("TYOM_TYOJONO");
	$tyojonot = array();
	$tyojonot[] = array(
		'value'	 => '',
		'text'	 => t('Ei rajausta'),
	);
	$tyojonot[] = array(
		'value'	 => 'EIJONOA',
		'text'	 => t('Ei jonossa'),
	);
	while ($tyojono_row = mysql_fetch_assoc($tyojono_result)) {
		$tyojono_row['value'] = $tyojono_row['selitetark'];
		$tyojono_row['text'] = $tyojono_row['selitetark'];
		$tyojonot[] = $tyojono_row;
	}

	return $tyojonot;
}

function hae_tyostatukset($request = array()) {
	global $kukarow, $yhtiorow;

	$tyostatus_result = t_avainsana("TYOM_TYOSTATUS");
	$tyostatukset = array();
	$tyostatukset[] = array(
		'value'	 => '',
		'text'	 => t('Ei rajausta'),
	);
	$tyostatukset[] = array(
		'value'	 => 'EISTATUSTA',
		'text'	 => t('Ei statusta'),
	);
	while ($tyostatus_row = mysql_fetch_assoc($tyostatus_result)) {
		$tyostatus_row['value'] = $tyostatus_row['selitetark'];
		$tyostatus_row['text'] = $tyostatus_row['selitetark'];
		$tyostatukset[] = $tyostatus_row;
	}

	return $tyostatukset;
}
require ("../inc/footer.inc");
?>
