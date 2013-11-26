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

echo "<font class='head'>".t("Vauriop�yt�kirja")."</font><hr>";

$request = array(
	'toim'					 => $toim,
	'action'				 => $action,
	'tilausnumero'			 => $tilausnumero,
	'tunnus'				 => $tunnus,
	'vauriokohteen_osoite'	 => $vauriokohteen_osoite,
	'asiakkaan_nimi'		 => $asiakkaan_nimi,
	'urakoitsija'			 => $urakoitsija,
	'vauriopoytakirjan_tila' => $vauriopoytakirjan_tila,
	'selvityksen_antaja'	 => $selvityksen_antaja
);

$request['tyomaarays_statukset'] = hae_tyomaarayksen_statukset();

echo_kayttoliittyma($request);

echo "<br/>";
echo "<br/>";

if ($request['action'] == 'generoi_excel') {
	$request['tulostettava_vauriopoytakirja'] = hae_vauriop�yt�kirjat($request);
	$request['tulostettava_vauriopoytakirja'] = $request['tulostettava_vauriopoytakirja'][0];
	$filename = generoi_custom_excel($request);

	echo_tallennus_formi($filename, t('Vauriop�yt�kirja'));

	$request['action'] = 'hae_vauriopoytakirjat';
}

if ($request['action'] == 'hae_vauriopoytakirjat') {
	$request['vauriopoytakirjat'] = hae_vauriop�yt�kirjat($request);

	if (!empty($request['vauriopoytakirjat'])) {
		echo_vauriopoytakirjat($request);
	}
	else {
		echo "<font class='message'>".t('Hakuehdoilla ei l�ytynyt vauriop�yt�kirjoja')."</font>";
	}
}

require('inc/footer.inc');

function echo_vauriopoytakirjat($request) {
	global $kukarow, $yhtiorow, $palvelin2;

	$lopetus = "{$palvelin2}tyomaarays/vauriopoytakirja.php////";
	foreach ($request as $key => $value) {
		if (!in_array($key, array('vauriopoytakirjat', 'tyomaarays_statukset'))) {
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
		echo $vauriopoytakirja['tyomaarays_viite'];
		echo "</td>";

		echo "<td>";
		echo $vauriopoytakirja['takuunumero'];
		echo "</td>";

		echo "<td>";
		echo $vauriopoytakirja['suorittaja'];
		echo "</td>";

		echo "<td>";
		$status = search_array_key_for_value_recursive($request['tyomaarays_statukset'], 'selite', $vauriopoytakirja['tyostatus']);
		$status = $status[0];
		echo $status['selitetark'];
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

		$pask = $vauriopoytakirja['takuunumero'];
		echo "<td class='back' nowrap>";
		echo "<form method='POST' action='' />";
		echo "<input type='hidden' name='action' value='generoi_excel' />";
		echo "<input type='hidden' name='tunnus' value='{$vauriopoytakirja['tunnus']}' />";
		echo "<input type='submit' value='".t('Excel')."' >";
		echo "</form>";
		echo "</td>";

		echo "</tr>";
	}
	echo "</tbody>";

	echo "</table>";
}

function hae_vauriop�yt�kirjat($request) {
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

	if (isset($request['selvityksen_antaja']) and $request['selvityksen_antaja'] != '') {
		$tyomaarays_where .= "	AND tyomaarays.suorittaja LIKE '%{$request['selvityksen_antaja']}%'";
	}

	if (isset($request['vauriokohteen_osoite']) and $request['vauriokohteen_osoite'] != '') {
		$tyomaarays_where .= "	AND ( lasku.ohjausmerkki LIKE '%{$request['vauriokohteen_osoite']}%' OR lasku.kohde LIKE '%{$request['vauriokohteen_osoite']}%' )";
	}

	if ($request['toim'] == 'asentaja') {
		$tyomaarays_where .= "	AND tyomaarays.tyostatus = '1'";
	}

	if ($request['action'] == 'generoi_excel') {
		$tyomaarays_where .= "	AND tyomaarays.otunnus = '{$request['tunnus']}'";
	}

	$query = "	SELECT tyomaarays.*,
				tyomaarays.viite as tyomaarays_viite,
				lasku.tunnus as tunnus,
				lasku.tila as tila,
				lasku.alatila as alatila,
				lasku.*
				FROM tyomaarays
				JOIN lasku
				ON ( lasku.yhtio = tyomaarays.yhtio
					AND lasku.tunnus = tyomaarays.otunnus )
				WHERE tyomaarays.yhtio = '{$kukarow['yhtio']}'
				{$tyomaarays_where}";
	$result = pupe_query($query);

	$vauriopoytakirjat = array();
	while ($vauriopoytakirja = mysql_fetch_assoc($result)) {
		if ($request['action'] == 'generoi_excel') {
			$vauriopoytakirja['tilausrivit'] = hae_vauriopoytakirja_rivit($vauriopoytakirja['tunnus']);
		}

		$vauriopoytakirjat[] = $vauriopoytakirja;
	}

	return $vauriopoytakirjat;
}

function hae_vauriopoytakirja_rivit($vauriopoytakirja_tunnus) {
	global $kukarow, $yhtiorow;

	if (empty($vauriopoytakirja_tunnus)) {
		return false;
	}

	$query = "	SELECT tilausrivi.*,
				tuote.tuotetyyppi
				FROM tilausrivi
				JOIN tuote
				ON ( tuote.yhtio = tilausrivi.yhtio
					AND tuote.tuoteno = tilausrivi.tuoteno )
				WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
				AND tilausrivi.otunnus = '{$vauriopoytakirja_tunnus}'";
	$result = pupe_query($query);

	$tilausrivit = array();
	while ($tilausrivi = mysql_fetch_assoc($result)) {
		if ($tilausrivi['tuotetyyppi'] == '') {
			if ($tilausrivi['try'] == 1) {
				$tilausrivit['kustannukset']['urakoitsija'][] = $tilausrivi;
			}
			else {
				$tilausrivit['kustannukset']['tsf'][] = $tilausrivi;
			}
		}
		else if ($tilausrivi['tuotetyyppi'] == 'K') {
			$tilausrivit['korjaajat'][] = $tilausrivi;
		}
	}

	return $tilausrivit;
}

function echo_kayttoliittyma($request) {
	global $kukarow, $yhtiorow;

	echo "<form action='' method='POST' name='vauriopoytakirja_form'>";
	echo "<input type='hidden' name='action' value='hae_vauriopoytakirjat' />";

	echo "<table>";

	echo "<tr>";
	echo "<th>".t('Operaattorin tikettinumero')."</th>";
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
	global $kukarow, $yhtiorow, $bold;

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

	$shiit = $request['tulostettava_vauriopoytakirja']['poistui'];
	$xls->write($excelrivi, $excelsarake++, t('Ty� alkoi'), $bold);
	$xls->write($excelrivi, $excelsarake++, date('d.m.Y H:i:s', strtotime($request['tulostettava_vauriopoytakirja']['poistui'])));
	$xls->write($excelrivi, $excelsarake++, t('Ty� p��ttyi'), $bold);
	$xls->write($excelrivi, $excelsarake, date('d.m.Y H:i:s', strtotime($request['tulostettava_vauriopoytakirja']['valmis'])));

	$excelrivi = $excelrivi + 2;
	$excelsarake = 0;

	$xls->write($excelrivi, $excelsarake++, t('Tapahtumapaikka').':', $bold);
	$excelrivi++;
	$excelsarake = 0;
	$xls->write($excelrivi, $excelsarake++, t('Kaupunki').'/'.t('Kunta'), $bold);
	$xls->write($excelrivi, $excelsarake++, $request['tulostettava_vauriopoytakirja']['ohjausmerkki']);
	$xls->write($excelrivi, $excelsarake++, t('Verkostoalue'), $bold);
	$xls->write($excelrivi, $excelsarake, t('TLA'), $bold);
	$excelrivi++;
	$excelsarake = 0;
	$xls->write($excelrivi, $excelsarake++, t('Paikka').'/'.t('Osoite'), $bold);
	$xls->write($excelrivi, $excelsarake++, $request['tulostettava_vauriopoytakirja']['kohde']);
	$xls->write($excelrivi, $excelsarake++, $request['tulostettava_vauriopoytakirja']['jalleenmyyja']);
	$xls->write($excelrivi, $excelsarake, $request['tulostettava_vauriopoytakirja']['tyomaarays_viite']);

	$excelrivi = $excelrivi + 2;
	$excelsarake = 0;

	$xls->write($excelrivi, $excelsarake, t('Vaurio tapahtui').':', $bold);
	$excelsarake = $excelsarake + 2;
	$xls->write($excelrivi, $excelsarake++, t('Operaattorin tikettinumero'), $bold);
	$xls->write($excelrivi, $excelsarake, t('Kiireellisyys'), $bold);
	$excelrivi++;
	$excelsarake = 0;
	$xls->write($excelrivi, $excelsarake++, $request['tulostettava_vauriopoytakirja']['vikakoodi']);
	$xls->write($excelrivi, $excelsarake++, $request['tulostettava_vauriopoytakirja']['merkki']);
	$xls->write($excelrivi, $excelsarake++, $request['tulostettava_vauriopoytakirja']['takuunumero']);
	$xls->write($excelrivi, $excelsarake, $request['tulostettava_vauriopoytakirja']['prioriteetti']);
}

function excel_selite(&$xls, &$excelrivi, &$excelsarake, $request) {
	global $kukarow, $yhtiorow, $bold;

	$xls->write($excelrivi, $excelsarake++, t('Ilmoitettu poliisille').':', $bold);

	if ($request['tulostettava_vauriopoytakirja']['kotipuh'] == 1) {
		$xls->write($excelrivi, $excelsarake, t('Kyll�'));
	}
	else {
		$xls->write($excelrivi, $excelsarake, t('Ei'));
	}

	$excelrivi++;
	$excelsarake = 0;

	$xls->write($excelrivi, $excelsarake++, t('Vahinkotapahtuma').':', $bold);
	if (onko_liitetiedostoja($request['tulostettava_vauriopoytakirja']['tunnus'])) {
		$xls->write($excelrivi, $excelsarake, t('Valokuvia saatavilla').': '.t('Kyll�'));
	}
	else {
		$xls->write($excelrivi, $excelsarake, t('Valokuvia saatavilla').': '.t('Ei'));
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
		
		$xls->write($excelrivi, $excelsarake, t('Kyll�'));
	}
	else {
		$xls->write($excelrivi, $excelsarake++, t('Aiheuttaja tuntematon').': ', $bold);
		$xls->write($excelrivi, $excelsarake++, t('Ei'));
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

	$xls->write($excelrivi, $excelsarake, t('Auton tai koneen rekisterinumero'), $bold);
	$excelrivi++;
	$xls->write($excelrivi, $excelsarake, $request['tulostettava_vauriopoytakirja']['mittarilukema']);
	$excelrivi++;
	$xls->write($excelrivi, $excelsarake, t('Vakuutusyhti�'), $bold);
	$excelrivi++;
	$xls->write($excelrivi, $excelsarake, $request['tulostettava_vauriopoytakirja']['myyjaliike']);

	$excelrivi = $excelrivi + 2;
	$excelsarake = 0;

	$xls->write($excelrivi, $excelsarake, t('Aiheuttaja kielt�ytyy korvausvastuusta').':', $bold);
	if ($request['tulostettava_vauriopoytakirja']['hyvaksy'] == 1) {
		$xls->write($excelrivi, $excelsarake, t('Kyll�'));
	}
	else {
		$xls->write($excelrivi, $excelsarake, t('Ei'));
	}
}

function excel_yhteystiedot(&$xls, &$excelrivi, &$excelsarake, $request, $yhteystiedot) {
	global $kukarow, $yhtiorow, $bold;

	$xls->write($excelrivi, $excelsarake++, t('Nimi'), $bold);
	$xls->write($excelrivi, $excelsarake++, $yhteystiedot['nimi']);
	$xls->write($excelrivi, $excelsarake++, t('Puhelinnumero'), $bold);
	$xls->write($excelrivi, $excelsarake, $yhteystiedot['puhelin']);
	$excelrivi++;
	$excelsarake = 0;
	$xls->write($excelrivi, $excelsarake++, t('Osoite'), $bold);
	$xls->write($excelrivi, $excelsarake++, $yhteystiedot['osoite']);
	$xls->write($excelrivi, $excelsarake++, t('Matkapuhelin'), $bold);
	$xls->write($excelrivi, $excelsarake, $yhteystiedot['gsm']);
	$excelrivi++;
	$excelsarake = 0;
	$xls->write($excelrivi, $excelsarake++, t('Postilokero'), $bold);
	$xls->write($excelrivi, $excelsarake++, '');
	$xls->write($excelrivi, $excelsarake++, t('Ytunnus'), $bold);
	$xls->write($excelrivi, $excelsarake, $yhteystiedot['ytunnus']);
	$excelrivi++;
	$excelsarake = 0;
	$xls->write($excelrivi, $excelsarake++, t('Postinumero'), $bold);
	$xls->write($excelrivi, $excelsarake++, $yhteystiedot['postino']);
	$xls->write($excelrivi, $excelsarake++, t('Toimipaikka'), $bold);
	$xls->write($excelrivi, $excelsarake, $yhteystiedot['postitp']);
}

function excel_kaapeli(&$xls, &$excelrivi, &$excelsarake, $request) {
	global $kukarow, $yhtiorow, $bold;

	$xls->write($excelrivi, $excelsarake++, t('Kaapelin�ytt� suoritettu').':', $bold);
	if ($request['tulostettava_vauriopoytakirja']['mallivari'] == 1) {
		$xls->write($excelrivi, $excelsarake++, t('Kyll�'));
	}
	else {
		$xls->write($excelrivi, $excelsarake++, t('Ei'));
	}
	$xls->write($excelrivi, $excelsarake++, t('N�ytt�p�yt�kirja l�hetetty (pakollinen)').':', $bold);
	if ($request['tulostettava_vauriopoytakirja']['valmnro'] == 1) {
		$xls->write($excelrivi, $excelsarake, t('Kyll�'));
	}
	else {
		$xls->write($excelrivi, $excelsarake, t('Ei'));
	}

	$excelrivi++;
	$excelsarake = 0;

	$xls->write($excelrivi, $excelsarake++, t('Karttakaivuu').':', $bold);
	if ($request['tulostettava_vauriopoytakirja']['tilno'] == 1) {
		$xls->write($excelrivi, $excelsarake, t('Kyll�'));
	}
	else {
		$xls->write($excelrivi, $excelsarake, t('Ei'));
	}

	$excelrivi++;
	$excelsarake = 0;
	$xls->write($excelrivi, $excelsarake++, t('N�ytt�j�').':', $bold);
	$xls->write($excelrivi, $excelsarake, $request['tulostettava_vauriopoytakirja']['tilausyhteyshenkilo']);
	$excelrivi++;
	$excelsarake = 0;
	$xls->write($excelrivi, $excelsarake++, t('Yhteystiedot').':', $bold);
	$xls->write($excelrivi, $excelsarake, $request['tulostettava_vauriopoytakirja']['kuljetus']);
}

function excel_lisatietoja(&$xls, &$excelrivi, &$excelsarake, $request) {
	global $kukarow, $yhtiorow, $bold;

	$xls->write($excelrivi, $excelsarake, t('Lis�tietoja TSF:lle'), $bold);
	$excelrivi++;
	$xls->write($excelrivi, $excelsarake, $request['tulostettava_vauriopoytakirja']['sisviesti1']);

	$excelrivi = $excelrivi + 2;
	$excelsarake = 0;

	$xls->write($excelrivi, $excelsarake++, t('Selvityksen antaja'), $bold);
	$xls->write($excelrivi, $excelsarake, $request['tulostettava_vauriopoytakirja']['suorittaja']);
	$excelrivi++;
	$excelsarake = 0;
	$xls->write($excelrivi, $excelsarake++, t('Puhelin'), $bold);
	$xls->write($excelrivi, $excelsarake, $request['tulostettava_vauriopoytakirja']['noutaja']);
}

function excel_yhteystiedot2(&$xls, &$excelrivi, &$excelsarake, $request) {
	global $kukarow, $yhtiorow, $bold;

	$xls->write($excelrivi, $excelsarake++, t('Yhteystiedot'), $bold);
	$xls->write($excelrivi, $excelsarake++, 'yhteystiedot t�h�');
	$xls->write($excelrivi, $excelsarake++, t('Kes�aika'), $bold);
	if (true) {
		$xls->write($excelrivi, $excelsarake, t('Kyll�'));
	}
	else {
		$xls->write($excelrivi, $excelsarake, t('Ei'));
	}
	$excelrivi++;
	$excelsarake = 0;

	$xls->write($excelrivi, $excelsarake++, t('Yhteystiedot'), $bold);
	$xls->write($excelrivi, $excelsarake++, 'yhteystiedot t�h�');
	$xls->write($excelrivi, $excelsarake++, t('Yhteystiedot'), $bold);
	$xls->write($excelrivi, $excelsarake, 'yhteystiedot2 t�h�');
}

function excel_korjaaja_rivit(&$xls, &$excelrivi, &$excelsarake, $request) {
	global $kukarow, $yhtiorow, $bold;

	$xls->write($excelrivi, $excelsarake++, t('Nimi'), $bold);
	$xls->write($excelrivi, $excelsarake++, t('Selvitys teht�v�st�'), $bold);
	$xls->write($excelrivi, $excelsarake++, t('Pvm'), $bold);
	$xls->write($excelrivi, $excelsarake++, t('Alko klo'), $bold);
	$xls->write($excelrivi, $excelsarake++, t('P��ttyi klo'), $bold);
	$xls->write($excelrivi, $excelsarake++, t('NT'), $bold);
	$xls->write($excelrivi, $excelsarake, t('YT'), $bold);
	$excelrivi++;
	$excelsarake = 0;
	$normaali_tyo_yhteensa = 0;
	$ylityo_yhteensa = 0;
	foreach ($request['tulostettava_vauriopoytakirja']['tilausrivit']['korjaajat'] as $korjaaja) {
		$xls->write($excelrivi, $excelsarake++, $korjaaja['nimitys']);
		$xls->write($excelrivi, $excelsarake++, $korjaaja['kommentti']);
		$xls->write($excelrivi, $excelsarake++, '-');
		$xls->write($excelrivi, $excelsarake++, '-');
		$xls->write($excelrivi, $excelsarake++, '-');
		$xls->write($excelrivi, $excelsarake++, $korjaaja['tilkpl']);
		$xls->write($excelrivi++, $excelsarake, 0);

		$excelsarake = 0;
		$normaali_tyo_yhteensa += $korjaaja['tilkpl'];
	}

	$excelrivi++;
	$excelsarake = 0;
	$excelsarake = $excelsarake + 4;
	$xls->write($excelrivi, $excelsarake++, t('Tunnit yht'), $bold);
	$xls->write($excelrivi, $excelsarake++, $normaali_tyo_yhteensa, $bold);
	$xls->write($excelrivi, $excelsarake, $ylityo_yhteensa, $bold);
	$excelrivi++;
	$excelsarake = 0;
	$excelsarake = $excelsarake + 4;
	$xls->write($excelrivi, $excelsarake++, t('Total'), $bold);
	$xls->write($excelrivi, $excelsarake, ($normaali_tyo_yhteensa + $ylityo_yhteensa), $bold);
}

function excel_kustannus_rivit(&$xls, &$excelrivi, &$excelsarake, $request) {
	global $kukarow, $yhtiorow, $bold;

	$xls->write($excelrivi, $excelsarake++, t('Kustannus'), $bold);
	$xls->write($excelrivi, $excelsarake++, t('kpl'), $bold);
	$xls->write($excelrivi, $excelsarake++, t('Yksikk�'), $bold);
	$xls->write($excelrivi, $excelsarake++, t('Hinta').'/'.t('yks'), $bold);
	$xls->write($excelrivi, $excelsarake, t('Yhteens�').'('.t('eur').')', $bold);
	$excelrivi++;
	$excelsarake = 0;
	foreach ($request['tulostettava_vauriopoytakirja']['tilausrivit']['kustannukset'] as $kustannusrivi) {
		$xls->write($excelrivi, $excelsarake++, $kustannusrivi['nimitys']);
		$xls->write($excelrivi, $excelsarake++, $kustannusrivi['tilkpl']);
		$xls->write($excelrivi, $excelsarake++, $kustannusrivi['yksikko']);
		if (!empty($kustannusrivi['tilkpl'])) {
			$xls->write($excelrivi, $excelsarake++, $kustannusrivi['hinta'] / $kustannusrivi['tilkpl']);
		}
		else {
			$xls->write($excelrivi, $excelsarake++, 0);
		}
		$xls->write($excelrivi, $excelsarake++, $kustannusrivi['rivihinta']);

		$viimeinen_sarake = $excelsarake;

		$excelrivi++;
		$excelsarake = 0;
	}
	$xls->write($excelrivi, $viimeinen_sarake, 'yhteens�', $bold);
}

function excel_materiaalit(&$xls, &$excelrivi, &$excelsarake, $request, $tyyppi = 'urakoitsija') {
	global $kukarow, $yhtiorow, $bold;

	if ($tyyppi == 'urakoitsija') {
		$materiaalit = $request['tulostettava_vauriopoytakirja']['tilausrivit']['kustannukset']['urakoitsija'];
		$otsikko = t('Urakoitsijan materiaali');
	}
	else {
		$materiaalit = $request['tulostettava_vauriopoytakirja']['tilausrivit']['kustannukset']['tsf'];
		$otsikko = t('TSF materiaali');
	}

	$xls->write($excelrivi, $excelsarake++, $otsikko, $bold);
	$xls->write($excelrivi, $excelsarake++, t('Tunnus'), $bold);
	$xls->write($excelrivi, $excelsarake++, t('kpl'), $bold);
	$xls->write($excelrivi, $excelsarake++, t('yksikk�').'/'.t('yks'), $bold);
	$xls->write($excelrivi, $excelsarake++, t('Hinta').'/'.t('yks'), $bold);
	$xls->write($excelrivi, $excelsarake, t('Yhteens�').'('.t('eur').')', $bold);
	$excelrivi++;
	$excelsarake = 0;
	if (empty($materiaalit)) {
		return;
	}
	foreach ($materiaalit as $u_materiaali) {
		$xls->write($excelrivi, $excelsarake++, $u_materiaali['nimitys']);
		$xls->write($excelrivi, $excelsarake++, $u_materiaali['tuoteno']);
		$xls->write($excelrivi, $excelsarake++, $u_materiaali['tilkpl']);
		$xls->write($excelrivi, $excelsarake++, $u_materiaali['yksikko']);
		$xls->write($excelrivi, $excelsarake++, $u_materiaali['hinta'] / $u_materiaali['tilkpl']);
		$xls->write($excelrivi, $excelsarake++, $u_materiaali['rivihinta']);

		$viimeinen_sarake = $excelsarake;

		$excelrivi++;
		$excelsarake = 0;
	}
	$xls->write($excelrivi, $viimeinen_sarake, 'yhteens�', $bold);
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

	$xls->write($excelrivi, $excelsarake, t('Yhteens�'), $bold);
	$excelsarake = $excelsarake + 4;
	$xls->write($excelrivi, $excelsarake, 'total yhteens�', $bold);
}

function excel_footer(&$xls, &$excelrivi, &$excelsarake, $request) {
	global $kukarow, $yhtiorow, $bold;

	$xls->write($excelrivi, $excelsarake, t('Lis�tietoja'), $bold);
	$excelrivi++;
	$excelsarake = 0;
	$xls->write($excelrivi, $excelsarake, $request['tulostettava_vauriopoytakirja']['sisviesti2']);

	$excelrivi = $excelrivi + 2;
	$excelsarake = 0;

	$xls->write($excelrivi, $excelsarake, t('Selvityksest� vastaava').':', $bold);
	$excelrivi++;
	$excelsarake = 0;
	$xls->write($excelrivi, $excelsarake++, t('Nimi').': ', $bold);
	$xls->write($excelrivi, $excelsarake++, $request['tulostettava_vauriopoytakirja']['huolitsija']);
	$xls->write($excelrivi, $excelsarake++, t('Puhelin').': ', $bold);
	$xls->write($excelrivi, $excelsarake++, $request['tulostettava_vauriopoytakirja']['jakelu']);
}
