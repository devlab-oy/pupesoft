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
		echo "<input type='hidden' name='toim'		 value='TYOMAARAYS' />";
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

	$xls->write($excelrivi, $excelsarake, t('Korjauksen tehneet').':', array('bold' => true));

	$excelrivi = $excelrivi + 2;
	$excelsarake = 0;

	excel_korjaaja_rivit($xls, $excelrivi, $excelsarake, $request);

	$xls_tiedosto = $xls->close();

	return $xls_tiedosto;
}

function excel_otsikko(&$xls, &$excelrivi, &$excelsarake, $request) {
	global $kukarow, $yhtiorow;

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
	$xls->write($excelrivi, $excelsarake, 'kunta');
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, t('Verkostoalue'), array('bold' => true));
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, t('TLA'));
	$excelrivi++;
	$excelsarake = 0;
	$xls->write($excelrivi, $excelsarake, t('Paikka').'/'.t('Osoite'));
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, 'osoite');
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, $request['tulostettava_vauriopoytakirja']['jalleenmyyja'], array(
		'bold' => true));
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, $request['tulostettava_vauriopoytakirja']['viite']);

	$excelrivi = $excelrivi + 2;
	$excelsarake = 0;

	$xls->write($excelrivi, $excelsarake, t('Vaurio tapahtui').':');
	$excelsarake = $excelsarake + 2;
	$xls->write($excelrivi, $excelsarake, t('Operaattorin tikettinumero'), array('bold' => true));
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, t('Kiireellisyys'));
	$excelrivi++;
	$excelsarake = 0;
	$xls->write($excelrivi, $excelsarake, $request['tulostettava_vauriopoytakirja']['vikakoodi']);
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, $request['tulostettava_vauriopoytakirja']['merkki']);
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, $request['tulostettava_vauriopoytakirja']['takuunumero'], array(
		'bold' => true));
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, $request['tulostettava_vauriopoytakirja']['prioriteetti']);
}

function excel_selite(&$xls, &$excelrivi, &$excelsarake, $request) {
	global $kukarow, $yhtiorow;

	$xls->write($excelrivi, $excelsarake, t('Ilmoitettu poliisille').':');
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, t('Kyllä / ei'));

	$excelrivi++;
	$excelsarake = 0;

	$xls->write($excelrivi, $excelsarake, t('Vahinkotapahtuma').':');
	$excelsarake++;
	if (true) {
		$xls->write($excelrivi, $excelsarake, t('Valokuvia ON saatavilla'));
	}
	else {
		$xls->write($excelrivi, $excelsarake, t('Valokuvia EI OLE saatavilla'));
	}

	$excelrivi++;
	$excelsarake = 0;

	$xls->write($excelrivi, $excelsarake, t('Kommentti tähän'));
}

function excel_aiheuttajat(&$xls, &$excelrivi, &$excelsarake, $request) {
	global $kukarow, $yhtiorow;

	$xls->write($excelrivi, $excelsarake, t('Aiheuttaja').':', array('bold' => true));
	$excelsarake = $excelsarake + 2;
	if (true) {
		$xls->write($excelrivi, $excelsarake, t('Aiheuttaja ON tuntematon'));
	}
	else {
		$xls->write($excelrivi, $excelsarake, t('Aiheuttaja EI OLE tuntematon'));
	}

	$excelrivi++;
	$excelsarake = 0;

	excel_yhteystiedot($xls, $excelrivi, $excelsarake, $request, array());

	$excelrivi++;
	$excelsarake = 0;

	$xls->write($excelrivi, $excelsarake, t('Laskutusosoite').':', array('bold' => true));

	$excelrivi++;
	$excelsarake = 0;

	excel_yhteystiedot($xls, $excelrivi, $excelsarake, $request, array());

	$excelrivi++;
	$excelsarake = 0;

	$xls->write($excelrivi, $excelsarake, t('Auton tai koneen rekisterinumero'));
	$excelrivi++;
	$xls->write($excelrivi, $excelsarake, t('Auton tai koneen rekisterinumero tähän'));
	$excelrivi++;
	$xls->write($excelrivi, $excelsarake, t('Vakuutusyhtiö'), array('bold' => true));
	$excelrivi++;
	$xls->write($excelrivi, $excelsarake, t('vak yhtiö tähä'));

	$excelrivi = $excelrivi + 2;
	$excelsarake = 0;

	$xls->write($excelrivi, $excelsarake, t('Aiheuttaja kieltäytyy korvausvastuusta').':');
	$excelsarake++;
	if (true) {
		$xls->write($excelrivi, $excelsarake, t('Kyllä'));
	}
	else {
		$xls->write($excelrivi, $excelsarake, t('Ei'));
	}
}

function excel_yhteystiedot(&$xls, &$excelrivi, &$excelsarake, $request, $yhteystiedot) {
	global $kukarow, $yhtiorow;

	$xls->write($excelrivi, $excelsarake, t('Nimi'), array('bold' => true));
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, 'nimi', array('bold' => true));
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, t('puhelinnumero'), array('bold' => true));
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, 'puh nro', array('bold' => true));
	$excelrivi++;
	$excelsarake = 0;
	$xls->write($excelrivi, $excelsarake, t('Osoite'), array('bold' => true));
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, 'osote tähä', array('bold' => true));
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, t('Matkapuhelin'), array('bold' => true));
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, 'mat puh tähä', array('bold' => true));
	$excelrivi++;
	$excelsarake = 0;
	$xls->write($excelrivi, $excelsarake, t('Postilokero'), array('bold' => true));
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, 'postilokero tähä', array('bold' => true));
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, t('Ytunnus'), array('bold' => true));
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, 'ytunnus tähä');
	$excelrivi++;
	$excelsarake = 0;
	$xls->write($excelrivi, $excelsarake, t('Postinumero'), array('bold' => true));
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, 'postinumero tähä', array('bold' => true));
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, t('Toimipaikka'), array('bold' => true));
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, 'toimipaikka tähä', array('bold' => true));
}

function excel_kaapeli(&$xls, &$excelrivi, &$excelsarake, $request) {
	global $kukarow, $yhtiorow;

	$xls->write($excelrivi, $excelsarake, t('Kaapelinäyttö suoritettu').':');
	$excelsarake++;
	if (true) {
		$xls->write($excelrivi, $excelsarake, t('Kyllä'));
	}
	else {
		$xls->write($excelrivi, $excelsarake, t('Ei'));
	}
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, t('Näyttöpöytäkirja lähetetty (pakollinen)').':');
	$excelsarake++;
	if (true) {
		$xls->write($excelrivi, $excelsarake, t('Kyllä'));
	}
	else {
		$xls->write($excelrivi, $excelsarake, t('Ei'));
	}

	$excelrivi++;
	$excelsarake = 0;

	$xls->write($excelrivi, $excelsarake, t('Karttakaivuu').':');
	$excelsarake++;
	if (true) {
		$xls->write($excelrivi, $excelsarake, t('Kyllä'));
	}
	else {
		$xls->write($excelrivi, $excelsarake, t('Ei'));
	}

	$excelrivi++;
	$excelsarake = 0;
	$xls->write($excelrivi, $excelsarake, t('Näyttäjä').':', array('bold' => true));
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, 'näyttäjä tähä', array('bold' => true));
	$excelrivi++;
	$excelsarake = 0;
	$xls->write($excelrivi, $excelsarake, t('Yhteystiedot').':', array('bold' => true));
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, 'yhteystiedot tähä', array('bold' => true));
}

function excel_lisatietoja(&$xls, &$excelrivi, &$excelsarake, $request) {
	global $kukarow, $yhtiorow;

	$xls->write($excelrivi, $excelsarake, t('Lisätietoja soneralle'));
	$excelrivi++;
	$xls->write($excelrivi, $excelsarake, 'lisätietoja tähä');

	$excelrivi = $excelrivi + 2;
	$excelsarake = 0;

	$xls->write($excelrivi, $excelsarake, t('Selvityksen antaja'), array('bold' => true));
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, 'selvityksen antaja tähä', array('bold' => true));
	$excelrivi++;
	$xls->write($excelrivi, $excelsarake, t('Puhelin'), array('bold' => true));
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, 'selvityksen antaja puhelin tähä', array(
		'bold' => true));
}

function excel_yhteystiedot2(&$xls, &$excelrivi, &$excelsarake, $request) {
	global $kukarow, $yhtiorow;

	$xls->write($excelrivi, $excelsarake, t('Yhteystiedot'), array('bold' => true));
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, 'yhteystiedot tähä', array('bold' => true));
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

	$xls->write($excelrivi, $excelsarake, t('Yhteystiedot'), array('bold' => true));
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, 'yhteystiedot tähä', array('bold' => true));
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, t('Yhteystiedot'), array('bold' => true));
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, 'yhteystiedot2 tähä', array('bold' => true));
}

function excel_korjaaja_rivit(&$xls, &$excelrivi, &$excelsarake, $request) {
	global $kukarow, $yhtiorow;

	$xls->write($excelrivi, $excelsarake, t('Nimi'), array('bold' => true));
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, t('Selvitys tehtävästä'), array('bold' => true));
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, t('Pvm'), array('bold' => true));
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, t('Alko klo'), array('bold' => true));
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, t('Päättyi klo'), array('bold' => true));
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, t('NT'), array('bold' => true));
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, t('YT'), array('bold' => true));
	foreach ($request['tulostettava_vauriopoytakirja']['korjaajat'] as $korjaaja) {
		$xls->write($excelrivi, $excelsarake, $korjaaja['nimi'], array('bold' => true));
		$excelsarake++;
		$xls->write($excelrivi, $excelsarake, $korjaaja['selvitys'], array('bold' => true));
		$excelsarake++;
		$xls->write($excelrivi, $excelsarake, $korjaaja['pvm'], array('bold' => true));
		$excelsarake++;
		$xls->write($excelrivi, $excelsarake, $korjaaja['alkoi_klo'], array('bold' => true));
		$excelsarake++;
		$xls->write($excelrivi, $excelsarake, $korjaaja['paattyi_klo'], array('bold' => true));
		$excelsarake++;
		$xls->write($excelrivi, $excelsarake, $korjaaja['nt'], array('bold' => true));
		$excelsarake++;
		$xls->write($excelrivi, $excelsarake, $korjaaja['yt'], array('bold' => true));
	}

	$excelrivi++;
	$excelsarake = 0;
	$excelsarake = $excelsarake + 4;
	$xls->write($excelrivi, $excelsarake, t('Tunnit yht'), array('bold' => true));
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, 'tunnit nt', array('bold' => true));
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, 'tunnit yt', array('bold' => true));
	$excelrivi++;
	$excelsarake = 0;
	$excelsarake = $excelsarake + 4;
	$xls->write($excelrivi, $excelsarake, t('Total'), array('bold' => true));
	$excelsarake++;
	$xls->write($excelrivi, $excelsarake, 'total tähä', array('bold' => true));
}
