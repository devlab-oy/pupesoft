<?php

if (isset($_POST["tee"])) {
	if ($_POST["tee"] == 'lataa_tiedosto') {
		$lataa_tiedosto = 1;
	}
	if (isset($_POST["kaunisnimi"]) and $_POST["kaunisnimi"] != '') {
		$_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
	}
}

require ("../inc/parametrit.inc");
require('inc/pupeExcel.inc');

if ($tee == 'lataa_tiedosto') {
	$filepath = "/tmp/".$tmpfilenimi;
	if (file_exists($filepath)) {
		readfile($filepath);
		unlink($filepath);
	}
	else {
		echo "<font class='error'>".t("Tiedostoa ei ole olemassa")."</font>";
	}
	exit;
}

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

if ($request['action'] == 'generoi_excel') {
	$request['tulostettava_vauriopoytakirja'] = hae_vauriopöytäkirjat($request);
	$filename = generoi_custom_excel($request);

	echo_tallennus_formi($filename, t('Vauriopöytäkirja'));

	$request['action'] = 'hae_vauriopoytakirjat';
}

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
		echo "<input type='hidden' name='lopetus' 	 value='{$lopetus}' />";
		echo "<input type='hidden' name='mista'		 value='vauriopoytakirja' />";
		echo "<input type='hidden' name='toim'		 value='VAURIOPOYTAKIRJA' />";
		echo "<input type='hidden' name='orig_tila'	 value='{$vauriopoytakirja["tila"]}' />";
		echo "<input type='hidden' name='orig_alatila' value='{$vauriopoytakirja["alatila"]}' />";
		echo "<input type='hidden' name='tilausnumero' value='{$vauriopoytakirja['tunnus']}' />";

		echo "<input type='submit' value='".t('Valitse')."' >";
		echo "</form>";
		echo "</td>";

		echo "<td class='back' nowrap>";
		echo "<form method='POST' action='' />";
		echo "<input type='hidden' name='action' value='generoi_excel' />";
		echo "<input type='hidden' name='tilausnumero' value='{$request['tilausnumero']}' />";
		echo "<input type='hidden' name='vauriokohteen_osoite' value='{$request['vauriokohteen_osoite']}' />";
		echo "<input type='hidden' name='asiakkaan_nimi' value='{$request['asiakkaan_nimi']}' />";
		echo "<input type='hidden' name='urakoitsija' value='{$request['urakoitsija']}' />";
		echo "<input type='hidden' name='vauriopoytakirjan_tila' value='{$request['vauriopoytakirjan_tila']}' />";
		echo "<input type='hidden' name='selvityksen_antaja' value='{$request['selvityksen_antaja']}' />";

		echo "<input type='submit' value='".t('Excel')."' >";
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

	if (isset($request['tilausnumero']) and $request['tilausnumero'] != '' and $request['action'] != 'generoi_excel') {
		$tyomaarays_where .= "	AND tyomaarays.takuunumero LIKE '%{$request['tilausnumero']}%'";
	}

	if (isset($request['vauriopoytakirjan_tila']) and $request['vauriopoytakirjan_tila'] != '') {
		$tyomaarays_where .= "	AND tyomaarays.tyostatus = '{$request['vauriopoytakirjan_tila']}'";
	}

	$tilausrivi_join = "";
	$tilausrivi_select = "";
	if ($request['action'] == 'generoi_excel') {
		$tilausrivi_join = "	JOIN tilausrivi
								ON ( tilausrivi.yhtio = lasku.yhtio
									AND tilausrivi.otunnus = lasku.tunnus )";
		$tilausrivi_select = "tilausrivi.*,";
		$tyomaarays_where .= "	AND tyomaarays.takuunumero = '{$request['tilausnumero']}'";
	}

	$query = "	SELECT tyomaarays.*,
				tyomaarays.viite as tyomaarays_viite,
				{$tilausrivi_select}
				lasku.tunnus as tunnus,
				lasku.tila as tila,
				lasku.alatila as alatila
				FROM tyomaarays
				JOIN lasku
				ON ( lasku.yhtio = tyomaarays.yhtio
					AND lasku.tunnus = tyomaarays.otunnus )
				{$tilausrivi_join}
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
	while ($tyomaarays_status = mysql_fetch_assoc($status_result)) {
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
		foreach ($request['tyomaarays_statukset'] as $tyomaarays_status) {
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

function generoi_custom_excel($request) {
	global $kukarow, $yhtiorow;

	$bold = array('bold' => true);

	$xls = new pupeExcel();
	$excelrivi = 0;
	$excelsarake = 0;

	excel_otsikko($xls, $excelrivi, $excelsarake, $request);

	$excelrivi = $excelrivi + 2;
	$excelsarake = 0;

	excel_selite($xls, $excelrivi, $excelsarake, $request);

	$excelrivi = $excelrivi + 2;
	$excelsarake = 0;

	excel_aiheuttajat($xls, $excelrivi, $excelsarake, $request);

	$excelrivi = $excelrivi + 2;
	$excelsarake = 0;

	excel_kaapeli($xls, $excelrivi, $excelsarake, $request);

	$excelrivi = $excelrivi + 2;
	$excelsarake = 0;

	excel_lisatietoja($xls, $excelrivi, $excelsarake, $request);

	$excelrivi = $excelrivi + 2;
	$excelsarake = 0;

	excel_yhteystiedot2($xls, $excelrivi, $excelsarake, $request);

	$excelrivi = $excelrivi + 2;
	$excelsarake = 0;

	$xls->write($excelrivi, $excelsarake, t('Korjauksen tehneet').':', $bold);

	$excelrivi = $excelrivi + 2;
	$excelsarake = 0;

	excel_korjaaja_rivit($xls, $excelrivi, $excelsarake, $request);

	$excelrivi = $excelrivi + 2;
	$excelsarake = 0;

	excel_kustannus_rivit($xls, $excelrivi, $excelsarake, $request);

	$excelrivi = $excelrivi + 2;
	$excelsarake = 0;

	excel_materiaalit($xls, $excelrivi, $excelsarake, $request);

	$excelrivi = $excelrivi + 2;
	$excelsarake = 0;

	excel_materiaalit($xls, $excelrivi, $excelsarake, $request, 'tsf');

	$excelrivi++;
	$excelsarake = 0;

	excel_yhteensa($xls, $excelrivi, $excelsarake, $request);

	$excelrivi = $excelrivi + 2;
	$excelsarake = 0;

	excel_footer($xls, $excelrivi, $excelsarake, $request);

	$xls_tiedosto = $xls->close();

	return $xls_tiedosto;
}

function excel_otsikko(&$xls, &$excelrivi, &$excelsarake, $request) {
	global $kukarow, $yhtiorow, $bold;

	$xls->write($excelrivi, $excelsarake, t('Työ alkoi'));
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, date('d.m.Y H:i:s', $request['tulostettava_vauriopoytakirja']['poistui']));
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, t('Työ päättyi'));
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, date('d.m.Y H:i:s', $request['tulostettava_vauriopoytakirja']['valmis']));

	$excelrivi = $excelrivi + 2;
	$excelsarake = 0;

	$xls->write($excelrivi, $excelsarake, t('Tapahtumapaikka').':');
	$excelrivi++;
	$xls->write($excelrivi, $excelsarake, t('Kaupunki').'/'.t('Kunta'));
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, $request['tulostettava_vauriopoytakirja']['ohjausmerkki']);
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, t('Verkostoalue'), $bold);
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, t('TLA'));
	$excelrivi++;
	$excelsarake = 0;
	$xls->write($excelrivi, $excelsarake, t('Paikka').'/'.t('Osoite'));
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, $request['tulostettava_vauriopoytakirja']['kohde']);
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, $request['tulostettava_vauriopoytakirja']['jalleenmyyja'], $bold);
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, $request['tulostettava_vauriopoytakirja']['tyomaarays_viite']);

	$excelrivi = $excelrivi + 2;
	$excelsarake = 0;

	$xls->write($excelrivi, $excelsarake, t('Vaurio tapahtui').':');
	$excelsarake = $excelsarake + 2;
	$xls->write($excelrivi, $excelsarake, t('Operaattorin tikettinumero'), $bold);
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, t('Kiireellisyys'));
	$excelrivi++;
	$excelsarake = 0;
	$xls->write($excelrivi, $excelsarake, $request['tulostettava_vauriopoytakirja']['vikakoodi']);
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, $request['tulostettava_vauriopoytakirja']['merkki']);
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, $request['tulostettava_vauriopoytakirja']['takuunumero'], $bold);
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, $request['tulostettava_vauriopoytakirja']['prioriteetti']);
}

function excel_selite(&$xls, &$excelrivi, &$excelsarake, $request) {
	global $kukarow, $yhtiorow, $bold;

	$xls->write($excelrivi, $excelsarake, t('Ilmoitettu poliisille').':');
	$excelsarake++;
	if ($request['tulostettava_vauriopoytakirja']['kotipuh'] == 1) {
		$xls->write($excelrivi, $excelsarake, t('Kyllä'));
	}
	else {
		$xls->write($excelrivi, $excelsarake, t('Ei'));
	}

	$excelrivi++;
	$excelsarake = 0;

	$xls->write($excelrivi, $excelsarake, t('Vahinkotapahtuma').':');
	$excelsarake++;
	if ($request['tulostettava_vauriopoytakirja']['kontti'] == 1) {
		$xls->write($excelrivi, $excelsarake, t('Valokuvia ON saatavilla'));
	}
	else {
		$xls->write($excelrivi, $excelsarake, t('Valokuvia EI OLE saatavilla'));
	}

	$excelrivi++;
	$excelsarake = 0;

	$xls->write($excelrivi, $excelsarake, $request['tulostettava_vauriopoytakirja']['comments']);
}

function excel_aiheuttajat(&$xls, &$excelrivi, &$excelsarake, $request) {
	global $kukarow, $yhtiorow, $bold;

	$xls->write($excelrivi, $excelsarake, t('Aiheuttaja').':', $bold);
	$excelsarake = $excelsarake + 2;
	if ($request['tulostettava_vauriopoytakirja']['nimi'] == '') {
		$xls->write($excelrivi, $excelsarake, t('Aiheuttaja ON tuntematon'));
	}
	else {
		$xls->write($excelrivi, $excelsarake, t('Aiheuttaja EI OLE tuntematon'));
	}

	$excelrivi++;
	$excelsarake = 0;

	$yhteystiedot = array(
		'nimi'		 => $request['tulostettava_vauriopoytakirja']['nimi'],
		'osoite'	 => $request['tulostettava_vauriopoytakirja']['osoite'],
		'puhelin'	 => $request['tulostettava_vauriopoytakirja']['puhelin'],
		'gsm'		 => $request['tulostettava_vauriopoytakirja']['gsm'],
		'ytunnus'	 => $request['tulostettava_vauriopoytakirja']['ytunnus'],
		'postino'	 => $request['tulostettava_vauriopoytakirja']['postino'],
		'postitp'	 => $request['tulostettava_vauriopoytakirja']['postitp'],
	);
	excel_yhteystiedot($xls, $excelrivi, $excelsarake, $request, $yhteystiedot);

	$excelrivi++;
	$excelsarake = 0;

	$xls->write($excelrivi, $excelsarake, t('Laskutusosoite').':', $bold);

	$excelrivi++;
	$excelsarake = 0;

	$yhteystiedot = array(
		'nimi'		 => $request['tulostettava_vauriopoytakirja']['laskutus_nimi'],
		'osoite'	 => $request['tulostettava_vauriopoytakirja']['laskutus_osoite'],
		'puhelin'	 => $request['tulostettava_vauriopoytakirja']['laskutus_puhelin'],
		'gsm'		 => $request['tulostettava_vauriopoytakirja']['laskutus_gsm'],
		'ytunnus'	 => $request['tulostettava_vauriopoytakirja']['laskutus_ytunnus'],
		'postino'	 => $request['tulostettava_vauriopoytakirja']['laskutus_postino'],
		'postitp'	 => $request['tulostettava_vauriopoytakirja']['laskutus_postitp'],
	);
	excel_yhteystiedot($xls, $excelrivi, $excelsarake, $request, $yhteystiedot);

	$excelrivi++;
	$excelsarake = 0;

	$xls->write($excelrivi, $excelsarake, t('Auton tai koneen rekisterinumero'));
	$excelrivi++;
	$xls->write($excelrivi, $excelsarake, $request['tulostettava_vauriopoytakirja']['mittarilukema']);
	$excelrivi++;
	$xls->write($excelrivi, $excelsarake, t('Vakuutusyhtiö'), $bold);
	$excelrivi++;
	$xls->write($excelrivi, $excelsarake, $request['tulostettava_vauriopoytakirja']['myyjaliike']);

	$excelrivi = $excelrivi + 2;
	$excelsarake = 0;

	$xls->write($excelrivi, $excelsarake, t('Aiheuttaja kieltäytyy korvausvastuusta').':');
	$excelsarake++;
	if ($request['tulostettava_vauriopoytakirja']['hyvaksy'] == 1) {
		$xls->write($excelrivi, $excelsarake, t('Kyllä'));
	}
	else {
		$xls->write($excelrivi, $excelsarake, t('Ei'));
	}
}

function excel_yhteystiedot(&$xls, &$excelrivi, &$excelsarake, $request, $yhteystiedot) {
	global $kukarow, $yhtiorow, $bold;

	$xls->write($excelrivi, $excelsarake, t('Nimi'), $bold);
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, $yhteystiedot['nimi'], $bold);
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, t('puhelinnumero'), $bold);
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, $yhteystiedot['puhelin'], $bold);
	$excelrivi++;
	$excelsarake = 0;
	$xls->write($excelrivi, $excelsarake, t('Osoite'), $bold);
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, $yhteystiedot['osoite'], $bold);
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, t('Matkapuhelin'), $bold);
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, $yhteystiedot['gsm'], $bold);
	$excelrivi++;
	$excelsarake = 0;
	$xls->write($excelrivi, $excelsarake, t('Postilokero'), $bold);
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, '', $bold);
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, t('Ytunnus'), $bold);
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, $yhteystiedot['ytunnus']);
	$excelrivi++;
	$excelsarake = 0;
	$xls->write($excelrivi, $excelsarake, t('Postinumero'), $bold);
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, $yhteystiedot['postino'], $bold);
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, t('Toimipaikka'), $bold);
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, $yhteystiedot['postitp'], $bold);
}

function excel_kaapeli(&$xls, &$excelrivi, &$excelsarake, $request) {
	global $kukarow, $yhtiorow, $bold;

	$xls->write($excelrivi, $excelsarake, t('Kaapelinäyttö suoritettu').':');
	$excelsarake++;
	if ($request['tulostettava_vauriopoytakirja']['mallivari'] == 1) {
		$xls->write($excelrivi, $excelsarake, t('Kyllä'));
	}
	else {
		$xls->write($excelrivi, $excelsarake, t('Ei'));
	}
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, t('Näyttöpöytäkirja lähetetty (pakollinen)').':');
	$excelsarake++;
	if ($request['tulostettava_vauriopoytakirja']['valmnro'] == 1) {
		$xls->write($excelrivi, $excelsarake, t('Kyllä'));
	}
	else {
		$xls->write($excelrivi, $excelsarake, t('Ei'));
	}

	$excelrivi++;
	$excelsarake = 0;

	$xls->write($excelrivi, $excelsarake, t('Karttakaivuu').':');
	$excelsarake++;
	if ($request['tulostettava_vauriopoytakirja']['tilno'] == 1) {
		$xls->write($excelrivi, $excelsarake, t('Kyllä'));
	}
	else {
		$xls->write($excelrivi, $excelsarake, t('Ei'));
	}

	$excelrivi++;
	$excelsarake = 0;
	$xls->write($excelrivi, $excelsarake, t('Näyttäjä').':', $bold);
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, $request['tulostettava_vauriopoytakirja']['tilausyhteyshenkilo'], $bold);
	$excelrivi++;
	$excelsarake = 0;
	$xls->write($excelrivi, $excelsarake, t('Yhteystiedot').':', $bold);
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, $request['tulostettava_vauriopoytakirja']['kuljetus'], $bold);
}

function excel_lisatietoja(&$xls, &$excelrivi, &$excelsarake, $request) {
	global $kukarow, $yhtiorow, $bold;

	$xls->write($excelrivi, $excelsarake, t('Lisätietoja TSF:lle'));
	$excelrivi++;
	$xls->write($excelrivi, $excelsarake, $request['tulostettava_vauriopoytakirja']['sisviesti1']);

	$excelrivi = $excelrivi + 2;
	$excelsarake = 0;

	$xls->write($excelrivi, $excelsarake, t('Selvityksen antaja'), $bold);
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, $request['tulostettava_vauriopoytakirja']['suorittaja'], $bold);
	$excelrivi++;
	$excelsarake = 0;
	$xls->write($excelrivi, $excelsarake, t('Puhelin'), $bold);
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, $request['tulostettava_vauriopoytakirja']['noutaja'], $bold);
}

function excel_yhteystiedot2(&$xls, &$excelrivi, &$excelsarake, $request) {
	global $kukarow, $yhtiorow, $bold;

	$xls->write($excelrivi, $excelsarake, t('Yhteystiedot'), $bold);
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, 'yhteystiedot tähä', $bold);
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, t('Kesäaika'));
	$excelsarake++;
	if (true) {
		$xls->write($excelrivi, $excelsarake, t('Kyllä'));
	}
	else {
		$xls->write($excelrivi, $excelsarake, t('Ei'));
	}
	$excelrivi++;
	$excelsarake = 0;

	$xls->write($excelrivi, $excelsarake, t('Yhteystiedot'), $bold);
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, 'yhteystiedot tähä', $bold);
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, t('Yhteystiedot'), $bold);
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, 'yhteystiedot2 tähä', $bold);
}

function excel_korjaaja_rivit(&$xls, &$excelrivi, &$excelsarake, $request) {
	global $kukarow, $yhtiorow, $bold;

	$xls->write($excelrivi, $excelsarake, t('Nimi'), $bold);
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, t('Selvitys tehtävästä'), $bold);
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, t('Pvm'), $bold);
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, t('Alko klo'), $bold);
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, t('Päättyi klo'), $bold);
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, t('NT'), $bold);
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, t('YT'), $bold);
	foreach ($request['tulostettava_vauriopoytakirja']['korjaajat'] as $korjaaja) {
		$xls->write($excelrivi, $excelsarake, $korjaaja['nimi'], $bold);
		$excelsarake++;
		$xls->write($excelrivi, $excelsarake, $korjaaja['selvitys'], $bold);
		$excelsarake++;
		$xls->write($excelrivi, $excelsarake, $korjaaja['pvm'], $bold);
		$excelsarake++;
		$xls->write($excelrivi, $excelsarake, $korjaaja['alkoi_klo'], $bold);
		$excelsarake++;
		$xls->write($excelrivi, $excelsarake, $korjaaja['paattyi_klo'], $bold);
		$excelsarake++;
		$xls->write($excelrivi, $excelsarake, $korjaaja['nt'], $bold);
		$excelsarake++;
		$xls->write($excelrivi, $excelsarake, $korjaaja['yt'], $bold);
	}

	$excelrivi++;
	$excelsarake = 0;
	$excelsarake = $excelsarake + 4;
	$xls->write($excelrivi, $excelsarake, t('Tunnit yht'), $bold);
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, 'tunnit nt', $bold);
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, 'tunnit yt', $bold);
	$excelrivi++;
	$excelsarake = 0;
	$excelsarake = $excelsarake + 4;
	$xls->write($excelrivi, $excelsarake, t('Total'), $bold);
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, 'total tähä', $bold);
}

function excel_kustannus_rivit(&$xls, &$excelrivi, &$excelsarake, $request) {
	global $kukarow, $yhtiorow, $bold;

	$xls->write($excelrivi, $excelsarake, t('Kustannus'), $bold);
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, t('kpl'), $bold);
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, t('Yksikkö'), $bold);
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, t('Hinta').'/'.t('yks'), $bold);
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, t('Yhteensä').'('.t('eur').')', $bold);
	$excelrivi++;
	$excelsarake = 0;
	foreach ($request['tulostettava_vauriopoytakirja']['kustannusrivit'] as $kustannusrivi) {
		$xls->write($excelrivi, $excelsarake, $kustannusrivi['nimitys'], $bold);
		$excelsarake++;
		$xls->write($excelrivi, $excelsarake, $kustannusrivi['tilkpl'], $bold);
		$excelsarake++;
		$xls->write($excelrivi, $excelsarake, $kustannusrivi['yksikko'], $bold);
		$excelsarake++;
		$xls->write($excelrivi, $excelsarake, $kustannusrivi['hinta'] / $kustannusrivi['tilkpl'], $bold);
		$excelsarake++;
		$xls->write($excelrivi, $excelsarake, $kustannusrivi['rivihinta'], $bold);
		$excelsarake++;

		$viimeinen_sarake = $excelsarake;

		$excelrivi++;
		$excelsarake = 0;
	}
	$xls->write($excelrivi, $viimeinen_sarake, 'yhteensä', $bold);
}

function excel_materiaalit(&$xls, &$excelrivi, &$excelsarake, $request, $tyyppi = 'urakoitsija') {
	global $kukarow, $yhtiorow, $bold;

	if ($tyyppi == 'urakoitsija') {
		$materiaalit = $request['tulostettava_vauriopoytakirja']['urakoitsijan_materiaalit'];
		$otsikko = t('Urakoitsijan materiaali');
	}
	else {
		$materiaalit = $request['tulostettava_vauriopoytakirja']['tsf_materiaalit'];
		$otsikko = t('TSF materiaali');
	}

	$xls->write($excelrivi, $excelsarake, $otsikko, $bold);
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, t('Tunnus'), $bold);
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, t('kpl'), $bold);
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, t('yksikkö').'/'.t('yks'), $bold);
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, t('Hinta').'/'.t('yks'), $bold);
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, t('Yhteensä').'('.t('eur').')', $bold);
	$excelrivi++;
	$excelsarake = 0;
	foreach ($materiaalit as $u_materiaali) {
		$xls->write($excelrivi, $excelsarake, $u_materiaali['nimitys'], $bold);
		$excelsarake++;
		$xls->write($excelrivi, $excelsarake, $u_materiaali['tuoteno'], $bold);
		$excelsarake++;
		$xls->write($excelrivi, $excelsarake, $u_materiaali['tilkpl'], $bold);
		$excelsarake++;
		$xls->write($excelrivi, $excelsarake, $u_materiaali['yksikko'], $bold);
		$excelsarake++;
		$xls->write($excelrivi, $excelsarake, $u_materiaali['hinta'] / $u_materiaali['tilkpl'], $bold);
		$excelsarake++;
		$xls->write($excelrivi, $excelsarake, $u_materiaali['rivihinta'], $bold);
		$excelsarake++;

		$viimeinen_sarake = $excelsarake;

		$excelrivi++;
		$excelsarake = 0;
	}
	$xls->write($excelrivi, $viimeinen_sarake, 'yhteensä', $bold);
}

function excel_yhteensa(&$xls, &$excelrivi, &$excelsarake, $request) {
	global $kukarow, $yhtiorow, $bold;

	$xls->write($excelrivi, $excelsarake, t('TSF:lle aiheutuneet kustannukset'), $bold);
	$excelsarake = $excelsarake + 2;

	$xls->write($excelrivi, $excelsarake, 'yht kpl', $bold);

	$excelsarake = $excelsarake + 3;

	$xls->write($excelrivi, $excelsarake, 'yht', $bold);
	$excelrivi++;
	$xls->write($excelrivi, $excelsarake, 'yht2', $bold);

	$excelrivi++;
	$excelsarake = 0;

	$xls->write($excelrivi, $excelsarake, t('ALV'), $bold);
	$excelsarake = $excelsarake + 2;
	$xls->write($excelrivi, $excelsarake, $request['tulostettava_vauriopoytakirja']['alv'].'%', $bold);
	$excelsarake = $excelsarake + 3;
	$xls->write($excelrivi, $excelsarake, 'alv maara', $bold);

	$excelrivi++;
	$excelsarake = 0;

	$xls->write($excelrivi, $excelsarake, t('Yhteensä'), $bold);
	$excelsarake = $excelsarake + 4;
	$xls->write($excelrivi, $excelsarake, 'total yhteensä', $bold);
}

function excel_footer(&$xls, &$excelrivi, &$excelsarake, $request) {
	global $kukarow, $yhtiorow, $bold;

	$xls->write($excelrivi, $excelsarake, t('Lisätietoja'));
	$excelrivi++;
	$excelsarake = 0;
	$xls->write($excelrivi, $excelsarake, $request['tulostettava_vauriopoytakirja']['sisviesti2']);

	$excelrivi = $excelrivi + 2;
	$excelsarake = 0;

	$xls->write($excelrivi, $excelsarake, t('Selvityksestä vastaava').':');
	$excelrivi++;
	$excelsarake = 0;
	$xls->write($excelrivi, $excelsarake, t('Nimi').': '.$request['tulostettava_vauriopoytakirja']['huolitsija']);
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, t('Puhelin').': '.$request['tulostettava_vauriopoytakirja']['jakelu']);
}