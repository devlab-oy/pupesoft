<?php

require ("../inc/parametrit.inc");

if (isset($livesearch_tee) and $livesearch_tee == "VAURIOPOYTAKIRJAHAKU") {
	livesearch_vauriopoytakirjahaku();
	exit;
}

enable_ajax();

echo "<font class='head'>".t("Vauriopöytäkirja")."</font><hr>";
?>
<style>

</style>
<script>

</script>
<?php

$request = array(
	'toim'					 => $toim,
	'action'				 => $action,
	'tilausnumero'			 => $tilausnumero,
	'vauriokohteen_osoite'	 => $vauriokohteen_osoite,
	'asiakkaan_nimi'		 => $asiakkaan_nimi,
	'urakoitsija'			 => $urakoitsija,
	'vauriopoytakirjan_tila' => $vauriopoytakirjan_tila,
	'selvityksen_antaja'	 => $selvityksen_antaja
);

$request['tyomaarays_statukset'] = hae_tyomaarayksen_statukset();

if ($request['action'] == 'hae_vauriopoytakirjat') {
	$request['vauriopoytakirjat'] = hae_vauriopöytäkirjat($request);
}

echo_kayttoliittyma($request);

echo "<br/>";
echo "<br/>";

if (!empty($request['vauriopoytakirjat'])) {
	echo_vauriopoytakirjat($request);
}
else {
	echo "<font class='message'>".t('Hakuehdoilla ei löytynyt vauriopöytäkirjoja')."</font>";
}

require('inc/footer.inc');

function echo_vauriopoytakirjat($request) {
	global $kukarow, $yhtiorow, $palvelin2;

	$lopetus = "{$palvelin2}tyomaarays/vauriopoytakirja.php////";
	foreach ($request as $key => $value) {
		if (!in_array($key, array('vauriopoytakirjat'))) {
			$lopetus .= "{$key}={$value}//";
		}
	}
	$lopetus = substr($lopetus, 0, -2);

	echo "<table>";

	echo "<thead>";
	echo "<tr>";
	echo "<th>".t('Tapahtumapaikka')."</th>";
	echo "<th>".t('Verkostoalue')."</th>";
	echo "<th>".t('TLA')."</th>";
	echo "<th>".t('Operaattorin tikettinumero')."</th>";
	echo "<th>".t('Selvityksen antaja')."</th>";
	echo "<th>".t('Tila')."</th>";
	echo "</tr>";
	echo "</thead>";

	echo "<tbody>";
	foreach ($request['vauriopoytakirjat'] as $vauriopoytakirja) {
		echo "<tr>";

		echo "<td>";
		echo "</td>";

		echo "<td>";
		echo $vauriopoytakirja['jalleenmyyja'];
		echo "</td>";

		echo "<td>";
		echo $vauriopoytakirja['viite'];
		echo "</td>";

		echo "<td>";
		echo $vauriopoytakirja['takuunumero'];
		echo "</td>";

		echo "<td>";
		echo $vauriopoytakirja['suorittaja'];
		echo "</td>";

		echo "<td>";
		echo "</td>";

		echo "<td class='back' nowrap>";
		echo "<form method='POST' action='../tilauskasittely/tilaus_myynti.php' />";
		echo "<input type='hidden' name='lopetus' 	 value='{$lopetus}'>";
		echo "<input type='hidden' name='mista'		 value='vauriopoytakirja'>";
		echo "<input type='hidden' name='toim'		 value='TYOMAARAYS'>";
		echo "<input type='hidden' name='orig_tila'	 value='{$vauriopoytakirja["tila"]}'>";
		echo "<input type='hidden' name='orig_alatila' value='{$vauriopoytakirja["alatila"]}'>";
		echo "<input type='hidden' name='tilausnumero' value='{$vauriopoytakirja['tunnus']}'>";

		echo "<input type='submit' value='".t('Valitse')."' >";
		echo "</form>";
		echo "</td>";

		echo "</tr>";
	}
	echo "</tbody>";

	echo "</table>";
}

function hae_vauriopöytäkirjat($request) {
	global $kukarow, $yhtiorow;

	$tyomaarays_where = "";
	if (isset($request['urakoitsija']) and $request['urakoitsija'] != '') {
		$tyomaarays_where .= "	AND tyomaarays.suorittaja LIKE '%{$request['urakoitsija']}%'";
	}

	if (isset($request['tilausnumero']) and $request['tilausnumero'] != '') {
		$tyomaarays_where .= "	AND tyomaarays.takuunumero LIKE '%{$request['tilausnumero']}%'";
	}

	if (isset($request['vauriopoytakirjan_tila']) and $request['vauriopoytakirjan_tila'] != '') {
		$tyomaarays_where .= "	AND tyomaarays.tyostatus = '{$request['vauriopoytakirjan_tila']}'";
	}

	$query = "	SELECT tyomaarays.*,
				lasku.tunnus as tunnus,
				lasku.tila as tila,
				lasku.alatila as alatila
				FROM tyomaarays
				JOIN lasku
				ON ( lasku.yhtio = tyomaarays.yhtio
					AND lasku.tunnus = tyomaarays.otunnus )
				WHERE tyomaarays.yhtio = '{$kukarow['yhtio']}'
				{$tyomaarays_where}";
	$result = pupe_query($query);

	$vauriopoytakirjat = array();
	while ($vauriopoytakirja = mysql_fetch_assoc($result)) {
		$vauriopoytakirjat[] = $vauriopoytakirja;
	}

	return $vauriopoytakirjat;
}

function hae_tyomaarayksen_statukset() {
	global $kukarow, $yhtiorow;

	$status_result = t_avainsana('TYOM_TYOSTATUS');

	$tyomaarays_statukset = array();
	while($tyomaarays_status = mysql_fetch_assoc($status_result)) {
		$tyomaarays_statukset[] = $tyomaarays_status;
	}

	return $tyomaarays_statukset;
}

function echo_kayttoliittyma($request) {
	global $kukarow, $yhtiorow;

	echo "<form action='' method='POST' name='vauriopoytakirja_form'>";
	echo "<input type='hidden' name='action' value='hae_vauriopoytakirjat' />";

	echo "<table>";

	echo "<tr>";
	echo "<th>".t('Tilausnumero')."</th>";
	echo "<td>";
	echo livesearch_kentta("vauriopoytakirja_form", "VAURIOPOYTAKIRJAHAKU", "tilausnumero", 136, $request['tilausnumero'], 'EISUBMIT', '', '', '');
	echo "</td>";
	echo "</tr>";

	if ($request['toim'] == 'tarkastaja') {
		echo "<tr>";
		echo "<th>".t('Vauriokohteen osoite')."/".t('kunta')."</th>";
		echo "<td>";
		echo "<input type='text' name='vauriokohteen_osoite' value='{$request['vauriokohteen_osoite']}' />";
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<th>".t('Asiakkaan nimi')."</th>";
		echo "<td>";
		echo "<input type='text' name='asiakkaan_nimi' value='{$request['asiakkaan_nimi']}' />";
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<th>".t('Urakoitsija')."</th>";
		echo "<td>";
		echo "<input type='text' name='urakoitsija' value='{$request['urakoitsija']}' />";
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<th>".t('Tila')."</th>";
		echo "<td>";
		echo "<select name='vauriopoytakirjan_tila'>";
		echo "<option value=''>".t('Kaikki')."</option>";
		$sel = "";
		foreach($request['tyomaarays_statukset'] as $tyomaarays_status) {
			if ($request['vauriopoytakirjan_tila'] == $tyomaarays_status['selite']) {
				$sel = "SELECTED";
			}
			echo "<option value='{$tyomaarays_status['selite']}' {$sel}>{$tyomaarays_status['selitetark']}</option>";
			$sel = "";
		}
		echo "</select>";
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<th>".t('Selvityksen_antaja')."</th>";
		echo "<td>";
		echo "<input type='text' name='selvityksen_antaja' value='{$request['selvityksen_antaja']}' />";
		echo "</td>";
		echo "</tr>";
	}

	echo "</table>";

	echo "<input type='submit' value='".t('Hae')."' />";
	echo "</form>";
}
