<?php

require ("../inc/parametrit.inc");

if ($ajax_request) {
	exit;
}

echo "<font class='head'>".t("Vauriopöytäkirja")."</font><hr>";
?>
<style>

</style>
<script>

</script>
<?php

$request = array(
	'toim'					 => $toim,
	'tilausnumero'			 => $tilausnumero,
	'vauriokohteen_osoite'	 => $vauriokohteen_osoite,
	'asiakkaan_nimi'		 => $asiakkaan_nimi,
	'urakoitsija'			 => $urakoitsija,
	'vauriopoytakirjan_tila' => $vauriopoytakirjan_tila,
	'selvityksen_antaja'	 => $selvityksen_antaja
);

$vauriopoytakirjat = hae_vauriopöytäkirjat($request);

echo_kayttoliittyma($request);

echo "<br/>";
echo "<br/>";

if (!empty($vauriopoytakirjat)) {
	echo_vauriopoytakirjat($vauriopoytakirjat);
}

require('inc/footer.inc');

function echo_vauriopoytakirjat($vauriopoytakirjat) {
	global $kukarow, $yhtiorow, $palvelin2;

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
	foreach ($vauriopoytakirjat as $vauriopoytakirja) {
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
		echo "<input type='hidden' name='lopetus' 	 value='{$palvelin2}tyomaarays/vauriopoytakirja.php'>";
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

	$query = "	SELECT tyomaarays.*,
				lasku.tunnus as tunnus,
				lasku.tila as tila,
				lasku.alatila as alatila
				FROM tyomaarays
				JOIN lasku
				ON ( lasku.yhtio = tyomaarays.yhtio
					AND lasku.tunnus = tyomaarays.otunnus )
				WHERE tyomaarays.yhtio = '{$kukarow['yhtio']}'
				AND tyomaarays.takuunumero = '{$request['tilausnumero']}'";
	$result = pupe_query($query);

	$vauriopoytakirjat = array();
	while ($vauriopoytakirja = mysql_fetch_assoc($result)) {
		$vauriopoytakirjat[] = $vauriopoytakirja;
	}

	return $vauriopoytakirjat;
}

function echo_kayttoliittyma($request) {
	global $kukarow, $yhtiorow;

	echo "<form action='' method='POST'>";

	echo "<table>";

	echo "<tr>";
	echo "<th>".t('Tilausnumero')."</th>";
	echo "<td>";
	echo "<input type='text' name='tilausnumero' value='{$request['tilausnumero']}'/>";
	echo "</td>";
	echo "</tr>";

	if ($request['toim'] == 'tarkastaja') {
		echo "<tr>";
		echo "<th>".t('Vauriokohteen osoite')."/".t('kunta')."</th>";
		echo "<td>";
		echo "<input type='text' name='vauriokohteen_osoite' />";
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<th>".t('Asiakkaan nimi')."</th>";
		echo "<td>";
		echo "<input type='text' name='asiakkaan_nimi' />";
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<th>".t('Urakoitsija')."</th>";
		echo "<td>";
		echo "<input type='text' name='urakoitsija' />";
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<th>".t('Tila')."</th>";
		echo "<td>";
		echo "<input type='text' name='vauriopoytakirjan_tila' />";
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<th>".t('Selvityksen_antaja')."</th>";
		echo "<td>";
		echo "<input type='text' name='selvityksen_antaja' />";
		echo "</td>";
		echo "</tr>";
	}

	echo "</table>";

	echo "<input type='submit' value='".t('Hae')."' />";
	echo "</form>";
}
