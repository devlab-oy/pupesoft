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

hae_tyomaaraykset($request);

echo_tyojono_table($request);

function hae_tyomaaraykset($request) {
	global $kukarow, $yhtiorow;

	$query = "	SELECT *
				FROM lasku
				JOIN tyomaarays
				ON ( tyomaarays.yhtio = lasku.tunnus
					AND tyomaarays.otunnus = lasku.tunnus )
				JOIN laskun_lisatiedot
				ON ( laskun_lisatiedot.yhtio = lasku.yhtio
					AND laskun_lisatiedot.otunnus = lasku.tunnus )
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

function generoi_haku() {
	$lisa = "";
	
	if ($myyntitilaus_haku != "") {
		$lisa .= " and lasku.tunnus='$myyntitilaus_haku' ";
	}

	if ($viesti_haku != "") {
		$lisa .= " and lasku.viesti like '%$viesti_haku%' ";
	}

	if ($asiakasnimi_haku != "") {
		$lisa .= " and lasku.nimi like '%$asiakasnimi_haku%' ";
	}

	if ($asiakasnumero_haku != "") {
		$lisa .= " and lasku.ytunnus like '$asiakasnumero_haku%' ";
	}

	if ($tyojono_haku != "") {
		$lisa .= " and a1.selitetark like '$tyojono_haku%' ";
	}

	if ($tyostatus_haku != "") {
		$lisa .= " and a2.selitetark like '$tyostatus_haku%' ";
	}

	if ($suorittaja_haku != "") {
		$lisa2 .= " HAVING suorittajanimi like '%$suorittaja_haku%' or asekalsuorittajanimi like '%$suorittaja_haku%' ";
	}

	return $lisa;
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
			<th>".t("Muokkaa")."</th>
			<th style='visibility:hidden; display:none;'></th>
		</tr>";

	echo "<tr>";

	echo "<td valign='top'><input type='text' size='10' class='search_field' name='search_tyomaarays_haku'></td>";

	echo "<td valign='top'><input type='text' size='10' class='search_field' name='search_asiakasnimi_haku'></td>";
	echo "<td valign='top'><input type='text' size='10' class='search_field' name='search_kohde_haku'></td>";
	echo "<td valign='top'><input type='text' size='10' class='search_field' name='search_toimitetaan_haku'></td>";

	echo "<td>";

	echo "<select class='tyojono_sort'>";
	echo "<option value=''>".t('Ei rajausta')."</option>";
	echo "<option value='EIJONOA'>".t("Ei jonossa")."</option>";

	// Haetaan tyojono avainsanat
	$tyojono_result = t_avainsana("TYOM_TYOJONO");
	while ($tyojono_row = mysql_fetch_assoc($tyojono_result)) {
		echo "<option value='$tyojono_row[selitetark]'>$tyojono_row[selitetark]</option>";
	}
	echo "</select><br>";
	echo "<select class='tyostatus_sort'>";
	echo "<option value=''>".t('Ei rajausta')."</option>";
	echo "<option value='EISTATUSTA'>".t("Ei statusta")."</option>";

	// Haetaan tyostatus avainsanat
	$tyostatus_result = t_avainsana("TYOM_TYOSTATUS");
	while ($tyostatus_row = mysql_fetch_assoc($tyostatus_result)) {
		echo "<option value='$tyostatus_row[selitetark]'>$tyostatus_row[selitetark]</option>";
	}
	echo "</select>";
	echo "</td>";

	echo "<td><input type='hidden' class='search_field' name='search_muokkaa_haku'></td>";
	echo "<td style='visibility:hidden; display:none;'><input type='hidden' class='search_field' name='search_statusjono_haku'></td>";
	echo "</tr>";
	echo "</thead>";


}
require ("../inc/footer.inc");
?>
