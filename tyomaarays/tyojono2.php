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

//AJAX requestti tänne
if ($ajax_request) {

}

echo "<font class='head'>".t("Työjono2").":</font>";
echo "<hr/>";
echo "<br/>";
?>
<style>

</style>
<script>

</script>

<?php

$request = array(
	
);

$request['tyomaaraykset'] = hae_tyomaaraykset($request);

$request['tyojonot'] = hae_tyojonot($request);

$request['tyostatukset'] = hae_tyostatukset($request);

echo_tyojono_table($request);

function hae_tyomaaraykset($request) {
	global $kukarow, $yhtiorow;

	$query = "	SELECT
				lasku.tunnus as lasku_tunnus,
				lasku.ytunnus as asiakas_ytunnus,
				lasku.nimi as asiakas_nimi,
				kohde.nimi as kohde_nimi,
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
				AND lasku.tila = 'A'";
	$result = pupe_query($query);

	$tyomaaraykset = array();
	while ($tyomaarays = mysql_fetch_assoc($result)) {
		$tyomaaraykset[] = $tyomaarays;
	}

	return $tyomaaraykset;
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

	echo "</tr>";

	echo "</thead>";


	echo "<tbody>";

	foreach ($request['tyomaaraykset'] as $tyomaarays) {
		echo "<tr>";

		echo "<td>";
		echo $tyomaarays['lasku_tunnus'];
		echo "</td>";

		echo "<td>";
		echo $tyomaarays['asiakas_ytunnus'] . '<br/>' . $tyomaarays['asiakas_nimi'];
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

		echo "</tr>";
	}

	echo "</tbody>";
	echo "</table>";
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
