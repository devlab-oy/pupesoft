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
	exit;
}

//functionc.inc require pitää olla ajax requestin jälkeen koska muute mennee headerit ketuiks
require('../inc/functions.inc');
require('../tilauskasittely/tarkastuspoytakirja_pdf.php');
require('../tilauskasittely/poikkeamaraportti_pdf.php');

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

		bind_select_kaikki_checkbox();
		bind_laite_checkbox();

		bind_aineisto_submit_button_click();
	});

	function bind_kohde_tr_click() {
		$('.kohde_tr, .kohde_tr_hidden').click(function(event) {
			if (console && console.log) {
				console.log(event.target.nodeName);
			}
			if (event.target.nodeName !== 'INPUT') {
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

	function bind_select_kaikki_checkbox() {
		$('.select_kaikki').click(function() {
			if ($(this).is(':checked')) {
				$(this).parent().parent().parent().find('.laite_checkbox').attr('checked', 'checked');

				$(this).parent().parent().parent().find('.lasku_tunnus').attr('name', 'lasku_tunnukset[]');
			}
			else {
				$(this).parent().parent().parent().find('.laite_checkbox').removeAttr('checked');

				//otetaan riveiltä laskutunnukset name atribuutti pois ettei lähde requestin mukana
				$(this).parent().parent().parent().find('.lasku_tunnus').removeAttr('name');
			}
		});
	}

	function bind_laite_checkbox() {
		$('.laite_checkbox').click(function() {
			if ($(this).is(':checked')) {
				$(this).attr('checked', 'checked');

				$(this).parent().find('.lasku_tunnus').attr('name', 'lasku_tunnukset[]');
			}
			else {
				$(this).removeAttr('checked');

				//otetaan riviltä laskutunnukset name atribuutti pois ettei lähde requestin mukana
				$(this).parent().find('.lasku_tunnus').removeAttr('name');
			}
		});
	}

	function bind_aineisto_submit_button_click() {
		$('#aineisto_tallennus_submit').click(function() {
			$(this).parent().parent().parent().parent().toggle();
			$('#progressbar').toggle();
		});
	}
</script>

<?php

$request = array(
	'tee'				 => $tee,
	'toim'				 => $toim,
	'lasku_tunnukset'	 => $lasku_tunnukset,
);

$request['tyojonot'] = hae_tyojonot($request);

$request['tyostatukset'] = hae_tyostatukset($request);

if ($toim == 'TEHDYT_TYOT') {
	if ($request['tee'] == 'tulosta_tarkastuspyotakirja' or $request['tee'] == 'tulosta_poikkeamaraportti') {
		$request['lasku_tunnukset'] = explode(',', $request['lasku_tunnukset']);


		$pdf_tiedostot = ($request['tee'] == 'tulosta_tarkastuspyotakirja' ? PDF\Tarkastuspoytakirja\hae_tarkastuspoytakirjat($request['lasku_tunnukset']) : PDF\Poikkeamaraportti\hae_poikkeamaraportit($request['lasku_tunnukset']));
		//$pdf_tiedostot = hae_tarkastuspoytakirjat($request['lasku_tunnukset']);

		foreach ($pdf_tiedostot as $pdf_tiedosto) {
			if (!empty($pdf_tiedosto)) {
				echo_tallennus_formi($pdf_tiedosto, ($request['tee'] == 'tulosta_tarkastuspyotakirja' ? t("Tarkastuspöytakirja") : t("Poikkeamaraportti")), 'pdf');
			}
		}
		//lasku_tunnukset pitää unsetata koska niitä käytetään hae_tyomaarays funkkarissa
		unset($request['lasku_tunnukset']);
	}

	$request['tyomaaraykset'] = hae_tyomaaraykset($request);
	$request['tyomaaraykset'] = kasittele_tyomaaraykset($request);
	echo_tyomaaraykset_table($request);
}
else {
	if ($request['tee'] == 'merkkaa_tehdyksi') {
		merkkaa_tyomaarays_tehdyksi($request);
		//lasku_tunnukset pitää unsetata koska niitä käytetään hae_tyomaarays funkkarissa
		unset($request['lasku_tunnukset']);
	}

	if ($request['tee'] == 'tulosta_tyolista') {
		$request['lasku_tunnukset'] = explode(',', $lasku_tunnukset);
		$tyomaaraykset = hae_tyomaaraykset($request);

		$header_values = array(
			'kohde_nimi'		 => array(
				'header' => t('Kohteen nimi'),
				'order'	 => 1
			),
			'paikka_nimi'		 => array(
				'header' => t('Paikan nimi'),
				'order'	 => 10
			),
			'paikka_olosuhde'	 => array(
				'header' => t('Olosuhde'),
				'order'	 => 20
			),
			'tuoteno'			 => array(
				'header' => t('Tuotenumero'),
				'order'	 => 9
			),
			'oma_numero'		 => array(
				'header' => t('Oma numero'),
				'order'	 => 0
			),
			'laite_sijainti'	 => array(
				'header' => t('Laitteen tarkempi sijainti'),
				'order'	 => 11
			),
			'toimaika'			 => array(
				'header' => t('Huoltoaika'),
				'order'	 => 30
			),
			'tyojono'			 => array(
				'header' => t('Työjono'),
				'order'	 => 40
			),
			'tyostatus'			 => array(
				'header' => t('Työstatus'),
				'order'	 => 50
			),
		);

		$force_to_string = array(
			'tuoteno'
		);

		$sulje_pois = array(
			'lasku_tunnus',
			'asiakas_ytunnus',
			'asiakas_nimi',
			'tyojonokoodi',
			'tyostatusvari',
			'tyostatus_koodi',
		);

		if (!empty($tyomaaraykset)) {
			$excel_filepath = generoi_excel_tiedosto($tyomaaraykset, $header_values, $force_to_string, $sulje_pois);
			echo_tallennus_formi($excel_filepath, t("Työlista"), 'xlsx');

			aseta_tyomaaraysten_status($request['lasku_tunnukset'], 'T');
			unset($request['lasku_tunnukset']);
		}
		else {
			echo t("Työmääräysten generointi epäonnistui");
		}
	}

	$request['tyomaaraykset'] = hae_tyomaaraykset($request);

	$request['tyomaaraykset'] = kasittele_tyomaaraykset($request);

	echo_tyomaaraykset_table($request);
}

function hae_tyomaaraykset($request) {
	global $kukarow, $yhtiorow;

	$lasku_where = "";
	if (!empty($request['lasku_tunnukset'])) {
		$lasku_where = "AND lasku.tunnus IN ('".implode("','", $request['lasku_tunnukset'])."')";
	}

	if ($request['toim'] == 'TEHDYT_TYOT') {
		$lasku_where .= "	AND lasku.tila = 'L'
							AND lasku.alatila = 'D'";
	}
	else {
		$lasku_where .= "	AND lasku.tila = 'A'";
	}

//	$query = "	SELECT
//				lasku.tunnus as lasku_tunnus,
//				lasku.ytunnus as asiakas_ytunnus,
//				lasku.nimi as asiakas_nimi,
//				kohde.tunnus as kohde_tunnus,
//				kohde.nimi as kohde_nimi,
//				paikka.nimi as paikka_nimi,
//				a3.selitetark as paikka_olosuhde,
//				laite.tuoteno,
//				laite.oma_numero,
//				laite.sijainti as laite_sijainti,
//				tuote.nimitys as toimenpide_tuote_nimitys,
//				lasku.toimaika,
//				a1.selite as tyojonokoodi,
//				a1.selitetark as tyojono,
//				a2.selite as tyostatus_koodi,
//				a2.selitetark as tyostatus,
//				a2.selitetark_2 as tyostatusvari,
//				tilausrivi.tunnus as tilausrivi_tunnus,
//				tilausrivin_lisatiedot.tilausrivilinkki as poikkeus_tunnus
//				FROM lasku
//				JOIN tyomaarays
//				ON ( tyomaarays.yhtio = lasku.yhtio
//					AND tyomaarays.otunnus = lasku.tunnus )
//				JOIN laskun_lisatiedot
//				ON ( laskun_lisatiedot.yhtio = lasku.yhtio
//					AND laskun_lisatiedot.otunnus = lasku.tunnus )
//				JOIN tilausrivi
//				ON ( tilausrivi.yhtio = lasku.yhtio
//					AND tilausrivi.otunnus = lasku.tunnus )
//				JOIN tilausrivin_lisatiedot
//				ON ( tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
//					AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus )
//				JOIN laite
//				ON ( laite.yhtio = tilausrivin_lisatiedot.yhtio
//					AND laite.tunnus = tilausrivin_lisatiedot.asiakkaan_positio )
//				JOIN paikka
//				ON ( paikka.yhtio = laite.yhtio
//					AND paikka.tunnus = laite.paikka )
//				JOIN avainsana a3
//				ON ( a3.yhtio = paikka.yhtio
//					AND a3.selite = paikka.olosuhde
//					AND a3.laji = 'OLOSUHDE')
//				JOIN kohde
//				ON ( kohde.yhtio = paikka.yhtio
//					AND kohde.tunnus = paikka.kohde )
//				LEFT JOIN avainsana a1
//				ON ( a1.yhtio = tyomaarays.yhtio
//					AND a1.laji = 'TYOM_TYOJONO'
//					AND a1.selite = tyomaarays.tyojono )
//				LEFT JOIN avainsana a2
//				ON ( a2.yhtio = tyomaarays.yhtio
//					AND a2.laji = 'TYOM_TYOSTATUS'
//					AND a2.selite = tyomaarays.tyostatus )
//				JOIN huoltosyklit_laitteet
//				ON ( huoltosyklit_laitteet.yhtio = laite.yhtio
//					AND huoltosyklit_laitteet.laite_tunnus = laite.tunnus )
//				JOIN huoltosykli
//				ON ( huoltosykli.yhtio = huoltosyklit_laitteet.yhtio
//					AND huoltosykli.tunnus = huoltosyklit_laitteet.huoltosykli_tunnus )
//				JOIN tuote
//				ON ( tuote.yhtio = huoltosykli.yhtio
//					AND tuote.tuoteno = huoltosykli.toimenpide )
//				WHERE lasku.yhtio = '{$kukarow['yhtio']}'
//				{$lasku_where}";

	$query = "	SELECT
				lasku.tunnus as lasku_tunnus,
				lasku.ytunnus as asiakas_ytunnus,
				lasku.nimi as asiakas_nimi,
				kohde.tunnus as kohde_tunnus,
				kohde.nimi as kohde_nimi,
				paikka.nimi as paikka_nimi,
				a3.selitetark as paikka_olosuhde,
				laite.tuoteno,
				laite.oma_numero,
				laite.sijainti as laite_sijainti,
				lasku.toimaika,
				a1.selite as tyojonokoodi,
				a1.selitetark as tyojono,
				a2.selite as tyostatus_koodi,
				a2.selitetark as tyostatus,
				a2.selitetark_2 as tyostatusvari,
				tilausrivi.tunnus as tilausrivi_tunnus,
				tilausrivin_lisatiedot.tilausrivilinkki as poikkeus_tunnus
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
				JOIN avainsana a3
				ON ( a3.yhtio = paikka.yhtio
					AND a3.selite = paikka.olosuhde
					AND a3.laji = 'OLOSUHDE')
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
				{$lasku_where}";
	$result = pupe_query($query);

	$tyomaaraykset = array();
	while ($tyomaarays = mysql_fetch_assoc($result)) {
		$tyomaaraykset[] = $tyomaarays;
	}

	return $tyomaaraykset;
}

function kasittele_tyomaaraykset($request) {
	global $kukarow, $yhtiorow;

	$tyomaaraykset_temp = array();
	foreach ($request['tyomaaraykset'] as $tyomaarays) {
		if (!isset($tyomaaraykset_temp[$tyomaarays['kohde_tunnus']])) {
			$tyomaaraykset_temp[$tyomaarays['kohde_tunnus']] = array(
				'asiakas_ytunnus'	 => $tyomaarays['asiakas_ytunnus'],
				'asiakas_nimi'		 => $tyomaarays['asiakas_nimi'],
				'kohde_tunnus'		 => $tyomaarays['kohde_tunnus'],
				'kohde_nimi'		 => $tyomaarays['kohde_nimi'],
				'toimaika'			 => $tyomaarays['toimaika'],
			);
		}

		//jos kohteen kaikilla työmääräyksillä on sama työstatus, asetetaan se myös kohteelle, jotta se voidaan näyttää ylemmällä tasolla taulukko näkymässä
		if (isset($tyomaaraykset_temp[$tyomaarays['kohde_tunnus']]['tyostatus'])) {
			if ($tyomaaraykset_temp[$tyomaarays['kohde_tunnus']]['tyostatus'] != $tyomaarays['tyostatus']) {
				$tyomaaraykset_temp[$tyomaarays['kohde_tunnus']]['tyostatus'] = '';
				$tyomaaraykset_temp[$tyomaarays['kohde_tunnus']]['tyostatus_koodi'] = '';
				$tyomaaraykset_temp[$tyomaarays['kohde_tunnus']]['tyostatusvari'] = '';
			}
			else {
				$tyomaaraykset_temp[$tyomaarays['kohde_tunnus']]['tyostatus'] = $tyomaarays['tyostatus'];
				$tyomaaraykset_temp[$tyomaarays['kohde_tunnus']]['tyostatus_koodi'] = $tyomaarays['tyostatus_koodi'];
				$tyomaaraykset_temp[$tyomaarays['kohde_tunnus']]['tyostatusvari'] = $tyomaarays['tyostatusvari'];
			}
		}
		else {
			$tyomaaraykset_temp[$tyomaarays['kohde_tunnus']]['tyostatus'] = $tyomaarays['tyostatus'];
			$tyomaaraykset_temp[$tyomaarays['kohde_tunnus']]['tyostatus_koodi'] = $tyomaarays['tyostatus_koodi'];
			$tyomaaraykset_temp[$tyomaarays['kohde_tunnus']]['tyostatusvari'] = $tyomaarays['tyostatusvari'];
		}

		//jos kohteen kaikilla työmääräyksillä on sama työjono, asetetaan se myös kohteelle, jotta se voidaan näyttää ylemmällä tasolla taulukko näkymässä
		if (isset($tyomaaraykset_temp[$tyomaarays['kohde_tunnus']]['tyojonokoodi'])) {
			if ($tyomaaraykset_temp[$tyomaarays['kohde_tunnus']]['tyojonokoodi'] != $tyomaarays['tyojonokoodi']) {
				$tyomaaraykset_temp[$tyomaarays['kohde_tunnus']]['tyojonokoodi'] = '';
				$tyomaaraykset_temp[$tyomaarays['kohde_tunnus']]['tyojono'] = '';
			}
			else {
				$tyomaaraykset_temp[$tyomaarays['kohde_tunnus']]['tyojonokoodi'] = $tyomaarays['tyojonokoodi'];
				$tyomaaraykset_temp[$tyomaarays['kohde_tunnus']]['tyojono'] = $tyomaarays['tyojono'];
			}
		}
		else {
			$tyomaaraykset_temp[$tyomaarays['kohde_tunnus']]['tyojonokoodi'] = $tyomaarays['tyojonokoodi'];
			$tyomaaraykset_temp[$tyomaarays['kohde_tunnus']]['tyojono'] = $tyomaarays['tyojono'];
		}

		$tyomaaraykset_temp[$tyomaarays['kohde_tunnus']]['lasku_tunnukset'][] = $tyomaarays['lasku_tunnus'];
		$tyomaaraykset_temp[$tyomaarays['kohde_tunnus']]['tyomaaraykset'][] = $tyomaarays;
	}

	return $tyomaaraykset_temp;
}

function aseta_tyomaaraysten_status($lasku_tunnukset, $status) {
	global $kukarow, $yhtiorow;

	$query = "	UPDATE tyomaarays
				SET tyostatus = '{$status}'
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tyomaarays.otunnus IN ('".implode("','", $lasku_tunnukset)."')";
	pupe_query($query);
}

function merkkaa_tyomaarays_tehdyksi($request) {
	global $kukarow, $yhtiorow;

	$query = "	UPDATE lasku
				SET tila = 'L',
				alatila = 'D'
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tunnus IN ('".implode("','", $request['lasku_tunnukset'])."')";
	pupe_query($query);

	aseta_tyomaaraysten_status($request['lasku_tunnukset'], 'X');
}

function echo_tyomaaraykset_table($request = array()) {
	global $kukarow, $yhtiorow, $palvelin2;

	echo "<table class='display dataTable'>";
	echo "<thead>";

	$toimitus_string = "Toimitetaan";
	if ($request['toim'] == 'TEHDYT_TYOT') {
		$toimitus_string = "Toimitettu";
	}
	echo "<tr>
			<th>".t("Työm").".<br>".t("Viite")."</th>
			<th>".t("Ytunnus")."<br>".t("Asiakas")."</th>
			<th>".t("Kohde")."</th>
			<th>".t($toimitus_string)."</th>
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
		if (count($tyomaarays['lasku_tunnukset']) < 5) {
			echo implode('<br/>', $tyomaarays['lasku_tunnukset']);
		}
		else {
			echo "monta";
		}
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

		if ($request['toim'] == 'TEHDYT_TYOT') {
			echo "<td>";
			echo $tyomaarays['tyojono'];
			echo "<br/>";
			echo $tyomaarays['tyostatus'];
			echo "</td>";

			echo "<td>";
			echo "<form class='multisubmit' name='tulosta_tarkastuspyotakirja'>";
			echo "<input type='hidden' name='tee' value='tulosta_tarkastuspyotakirja' />";
			echo "<input type='hidden' name='toim' value='{$request['toim']}' />";
			echo "<input type='hidden' name='lasku_tunnukset' value='".implode(',', $tyomaarays['lasku_tunnukset'])."' />";
			echo "<input type='submit' value='".t("Tulosta tarkastuspyötäkirja")."' />";
			echo "</form>";
			echo "</td>";
		}
		else {
			if (!empty($tyomaarays["tyostatusvari"])) {
				$td_taustavari = "style='background-color: {$tyomaarays['tyostatusvari']};'";
			}
			else {
				$td_taustavari = "";
			}
			echo "<td {$td_taustavari}>";

			echo "<select class='tyojono_muutos'>";
			foreach ($request['tyojonot'] as $tyojono) {
				$sel = $tyomaarays['tyojonokoodi'] == $tyojono['value'] ? ' SELECTED' : '';
				echo "<option value='{$tyojono['value']}' {$sel}>{$tyojono['text']}</option>";
			}
			echo "</select>";
			echo "<br/>";
			echo "<select class='tyostatus_muutos'>";
			foreach ($request['tyostatukset'] as $tyostatus) {
				$sel = $tyomaarays['tyostatus_koodi'] == $tyostatus['value'] ? ' SELECTED' : '';
				echo "<option value='{$tyostatus['value']}' {$sel}>{$tyostatus['text']}</option>";
			}
			echo "</select>";

			echo "</td>";

			echo "<td>";
			echo "<form class='multisubmit' name='tulosta_tyolista'>";
			echo "<input type='hidden' name='tee' value='tulosta_tyolista' />";
			echo "<input type='hidden' name='lasku_tunnukset' value='".implode(',', $tyomaarays['lasku_tunnukset'])."' />";
			echo "<input type='submit' value='".t("Tulosta työlistat")."' />";
			echo "</form>";
			echo "</td>";
		}

		echo "</tr>";

		echo_laitteet_table($request, $tyomaarays['tyomaaraykset']);
	}

	echo "</tbody>";
	echo "</table>";
}

function echo_laitteet_table($request, $laitteet) {
	global $kukarow, $yhtiorow, $palvelin2;

	echo "<tr class='laite_table_tr_hidden'>";

	echo "<form class='multisubmit' name='merkkaa_tehdyksi'>";
	echo "<input type='hidden' name='tee' value='merkkaa_tehdyksi' />";

	echo "<td colspan='1'>";
	echo "</td>";
	echo "<td colspan='4'>";

	echo "<table class='laite_table'>";
	echo "<tr>";
	echo "<th>";
	echo t("#");
	if ($request['toim'] != 'TEHDYT_TYOT') {
		echo "<br/>";
		echo "<input type='checkbox' class='select_kaikki' />";
	}
	echo "</th>";
	echo "<th>".t("Oma numero")."</th>";
	echo "<th>".t("Laite")."</th>";
	echo "<th>".t("Paikka")."</th>";
	echo "<th>".t("Sijainti")."</th>";
	echo "<th>".t("Tehtävä työ")."</th>";
	echo "<th>".t("Työjono")."/<br>".t("Työstatus")."</th>";
	echo "<th>".t("Poikkeama")."</th>";
	echo "</tr>";

	foreach ($laitteet as $laite) {
		echo "<tr class='laite_tr'>";

		echo "<td>";
		if ($request['toim'] != 'TEHDYT_TYOT') {
			echo "<input type='checkbox' class='laite_checkbox' />";
		}
		echo "<input type='hidden' class='lasku_tunnus' value='{$laite['lasku_tunnus']}' />";
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

		echo "<td>";
		//echo $laite['toimenpide_tuote_nimitys'];
		echo "</td>";

		if ($request['toim'] == 'TEHDYT_TYOT') {
			echo "<td>";
			echo $laite['tyojono'];
			echo "<br/>";
			echo $laite['tyojono'];
			echo "</td>";

			echo "<td>";
			if ($laite['poikkeus_tunnus']) {
				echo "<font class='error'>".t("Poikkeus")."</font>";
				echo "<br/>";
				echo "<form class='multisubmit' name='tulosta_poikkeamaraportti' method='POST'>";
				echo "<input type='hidden' name='toim' value='{$request['toim']}' />";
				echo "<input type='hidden' name='tee' value='tulosta_poikkeamaraportti' />";
				echo "<input type='hidden' name='lasku_tunnukset' value='{$laite['lasku_tunnus']}' />";
				echo "<input type='submit' value='".t('Tulosta poikkeamaraportti')."' />";
				echo "</form>";
			}
			echo "</td>";
		}
		else {
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
				$sel = $laite['tyostatus_koodi'] == $tyostatus['selite'] ? ' SELECTED' : '';
				echo "<option value='{$tyostatus['value']}' {$sel}>{$tyostatus['text']}</option>";
			}
			echo "</select>";

			echo "</td>";

			echo "<td>";
			//echo "<a href='{$palvelin2}asiakkaan_laite_hallinta.php?tee=hae_asiakas&ytunnus={$laite['asiakas_ytunnus']}&lopetus={$palvelin2}tyomaarays/tyojono2.php'><button type='button'>".t("Laitteen vaihto")."</button></a>";
			echo "<a href='{$palvelin2}tilauskasittely/laitteen_vaihto.php?tilausrivi_tunnus={$laite['tilausrivi_tunnus']}&lopetus={$palvelin2}tyomaarays/tyojono2.php'><button type='button'>".t("Laitteen vaihto")."</button></a>";
			echo "<br/>";
			echo "<a href='{$palvelin2}tilauskasittely/tilaus_myynti.php?toim=TYOMAARAYS&tilausnumero={$laite['lasku_tunnus']}&lopetus={$palvelin2}tyomaarays/tyojono2.php'><button type='button'>".t("Muu")."</button></a>";
			echo "</td>";
		}

		echo "</tr>";
	}

	echo "</table>";
	echo "</td>";

	if ($request['toim'] == 'TEHDYT_TYOT') {
		echo "<td>";
		echo "</td>";
	}
	else {
		echo "<td>";
		echo "<input type='submit' value='".t("Merkkaa tehdyksi")."' />";
		echo "</td>";
	}

	echo "</form>";
	echo "</tr>";
}

function hae_tyojonot($request = array()) {
	global $kukarow, $yhtiorow;

	$tyojono_result = t_avainsana("TYOM_TYOJONO");
	$tyojonot = array();
	$tyojonot[] = array(
		'value'	 => 'EIJONOA',
		'text'	 => t('Ei jonossa'),
	);
	while ($tyojono_row = mysql_fetch_assoc($tyojono_result)) {
		$tyojono_row['value'] = $tyojono_row['selite'];
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
		'value'	 => 'EISTATUSTA',
		'text'	 => t('Ei statusta'),
	);
	while ($tyostatus_row = mysql_fetch_assoc($tyostatus_result)) {
		$tyostatus_row['value'] = $tyostatus_row['selite'];
		$tyostatus_row['text'] = $tyostatus_row['selitetark'];
		$tyostatukset[] = $tyostatus_row;
	}

	return $tyostatukset;
}
require ("../inc/footer.inc");
?>
