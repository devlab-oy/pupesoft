<?php

$pupe_DataTables = "saldovahvistus";

$useslave = 1;
session_start();

//Tällä pystyy tyhjentämään valitut laskut.
//session_unset();
//die();

if (isset($_POST["tee"])) {
	if ($_POST["tee"] == 'lataa_tiedosto') {
		$lataa_tiedosto = 1;
	}
	if (isset($_POST["kaunisnimi"]) and $_POST["kaunisnimi"] != '') {
		$_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
	}
}

require ("../inc/parametrit.inc");
require('myyntires/paperitiliote_saldovahvistus.php');
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

if ($ajax_request) {
	if ($merkkaa_lahetettavaksi == '1') {
		$lasku_tunnukset_key = implode('', $lasku_tunnukset);
		if ($lisays == 'true') {
			$saldovahvistusrivi = array(
				'laskun_avoin_paiva'	 => $laskun_avoin_paiva,
				'saldovahvistus_viesti'	 => $saldovahvistus_viesti,
				'lasku_tunnukset'		 => $lasku_tunnukset,
			);
			lisaa_sessioon_saldovahvistus_rivi($lasku_tunnukset_key, $saldovahvistusrivi);

			echo true;
		}
		else {
			unset($_SESSION['valitut_laskut'][$lasku_tunnukset_key]);

			echo true;
		}
	}

	echo false;

	exit;
}

if (!isset($nayta_pdf)) {
	$nayta_pdf = 0;
}

if ($nayta_pdf != 1) {
	echo "<font class='head'>".t("Saldovahvistus")."</font><hr>";
}

if (!isset($pp)) {
	$pp = date('d');
}
if (!isset($kk)) {
	$kk = date('m');
}
if (!isset($vv)) {
	$vv = date('Y');
}

if (checkdate($kk, $pp, $vv)) {
	$paiva = date('Y-m-d', strtotime("{$vv}-{$kk}-{$pp}"));
}
else {
	$paiva = date('Y-m-d', strtotime("now"));
}

$request = array(
	'tee'					 => $tee,
	'ryhmittely_tyyppi'		 => $ryhmittely_tyyppi,
	'ryhmittely_arvo'		 => $ryhmittely_arvo,
	'pp'					 => $pp,
	'kk'					 => $kk,
	'vv'					 => $vv,
	'paiva'					 => $paiva,
	'saldovahvistus_viesti'	 => $saldovahvistus_viesti,
	'lasku_tunnukset'		 => $lasku_tunnukset,
	'tallenna_exceliin'		 => $tallenna_exceliin,
);

$request['laskut'] = array();
$request['valitut_laskut'] = array();

$request['ryhmittely_tyypit'] = array(
	'ytunnus'	 => t('Ytunnus'),
	'asiakasnro' => t('Asiakasnumero'),
);

$request['saldovahvistus_viestit'] = hae_saldovahvistus_viestit();

echo_kayttoliittyma($request);

echo "<br/>";
echo "<br/>";

if (!empty($_SESSION['valitut_laskut'])) {
	$lasku_tunnukset_temp = $request['lasku_tunnukset'];
	foreach ($_SESSION['valitut_laskut'] as $valittu_lasku) {
		$request['lasku_tunnukset'] = $valittu_lasku['lasku_tunnukset'];
		$lasku_temp = hae_myyntilaskuja_joilla_avoin_saldo($request, true);
		$lasku_temp['saldovahvistus_viesti'] = $valittu_lasku['saldovahvistus_viesti'];
		$lasku_temp['laskun_avoin_paiva'] = $valittu_lasku['laskun_avoin_paiva'];
		$request['valitut_laskut'][] = $lasku_temp;
	}
	$request['lasku_tunnukset'] = $lasku_tunnukset_temp;
}

if ($request['tee'] == 'aja_saldovahvistus') {
	$request['laskut'] = hae_myyntilaskuja_joilla_avoin_saldo($request);

	if (!empty($request['tallenna_exceliin'])) {
		$excel_filepath = generoi_custom_excel_tiedosto($request);

		echo_tallennus_formi($excel_filepath, t('Saldovahvistus'));
	}

	echo_saldovahvistukset($request);
}
else if ($request['tee'] == 'nayta_saldovahvistus_pdf' or $request['tee'] == 'tulosta_saldovahvistus_pdf') {
	//requestissa tulee tietyn ytunnuksen lasku_tunnuksia. Tällöin $laskut arrayssa on vain yksi solu
	$laskut = hae_myyntilaskuja_joilla_avoin_saldo($request);

	//Jos saldovahvistus_rivi löytyy jo valittujen rivien joukosta, niin haetaan riville tallennetut viesti ja päivämäärä sessiosta
	$lasku_tunnukset_temp = implode('', $laskut['lasku_tunnukset']);
	if (array_key_exists($lasku_tunnukset_temp, $_SESSION['valitut_laskut'])) {
		$laskut['saldovahvistus_viesti'] = search_array_key_for_value_recursive($request['saldovahvistus_viestit'], 'selite', $_SESSION['valitut_laskut'][$lasku_tunnukset_temp]['saldovahvistus_viesti']);
		$laskut['saldovahvistus_viesti'] = $laskut['saldovahvistus_viesti'][0];
		$laskut['laskun_avoin_paiva'] = $_SESSION['valitut_laskut'][$lasku_tunnukset_temp]['laskun_avoin_paiva'];
	}
	else {
		$laskut['saldovahvistus_viesti'] = search_array_key_for_value_recursive($request['saldovahvistus_viestit'], 'selite', $request['saldovahvistus_viesti']);
		$laskut['saldovahvistus_viesti'] = $laskut['saldovahvistus_viesti'][0];
		$laskut['laskun_avoin_paiva'] = $request['paiva'];
	}
	//Valittu saldovahvistusviesti
	$pdf_filepath = hae_saldovahvistus_pdf($laskut);

	if ($request['tee'] == 'nayta_saldovahvistus_pdf') {
		echo file_get_contents($pdf_filepath);
	}
	else if ($request['tee'] == 'tulosta_saldovahvistus_pdf') {
		$kirjoitin_komento = hae_kayttajan_kirjoitin();

		exec($kirjoitin_komento['komento'].' '.$pdf_filepath);
	}

	//unset, jotta käyttöliittymään tulisi rajausten mukaiset laskut.
	unset($request['lasku_tunnukset']);

	$request['laskut'] = hae_myyntilaskuja_joilla_avoin_saldo($request);
	echo_saldovahvistukset($request);
}
else if ($request['tee'] == 'laheta_sahkopostit') {
	generoi_sahkopostit($request);
	unset($_SESSION['valitut_laskut']);
}
?>
<style>
	tr.border_bottom td {
		border-bottom: 3pt solid black;
	}

	.hidden {
		display: none;
	}
</style>
<script>
	$(document).ready(function() {
		bind_saldovahvistus_rivi_valinta_checkbox_click();
		bind_valitse_kaikki_checkbox_click();
	});

	function bind_saldovahvistus_rivi_valinta_checkbox_click() {
		$('.saldovahvistus_rivi_valinta').change(function() {
			var lisays;
			if ($(this).is(':checked')) {
				lisays = true;
			}
			else {
				lisays = false;
			}

			var lasku_tunnukset = $(this).parent().parent().find('.nayta_pdf_td .lasku_tunnus').map(function() {
				return $(this).val();
			}).get();
			var laskun_avoin_paiva = $(this).parent().parent().find('.laskun_avoin_paiva').val();
			var saldovahvistus_viesti = $(this).parent().parent().find('.saldovahvistus_viesti').html();

			tallenna_sessioon(lasku_tunnukset, lisays, laskun_avoin_paiva, saldovahvistus_viesti);
		});
	}

	function bind_valitse_kaikki_checkbox_click() {
		$('#valitse_kaikki').click(function() {
			var $table = $(this).parent().parent().parent().parent();
			var lasku_tunnukset = $table.find('.nayta_pdf_td .lasku_tunnus').map(function() {
				return $(this).val();
			}).get();
			var laskun_avoin_paiva = $(this).parent().parent().find('.laskun_avoin_paiva').val();
			var saldovahvistus_viesti = $(this).parent().parent().find('.saldovahvistus_viesti').html();

			var lisays;
			if ($(this).is(':checked')) {
				lisays = true;
				$table.find('.saldovahvistus_rivi_valinta').attr('checked', 'checked');
			}
			else {
				lisays = false;
				$table.find('.saldovahvistus_rivi_valinta').removeAttr('checked');
			}

			$table.find('.saldovahvistus_rivi').each(function() {
				var lasku_tunnukset = $(this).find('.nayta_pdf_td .lasku_tunnus').map(function() {
					return $(this).val();
				}).get();
				var laskun_avoin_paiva = $(this).parent().parent().find('.laskun_avoin_paiva').val();
				var saldovahvistus_viesti = $(this).parent().parent().find('.saldovahvistus_viesti').html();

				tallenna_sessioon(lasku_tunnukset, lisays, laskun_avoin_paiva, saldovahvistus_viesti);
			});
		});
	}

	function tallenna_sessioon(lasku_tunnukset, lisays, laskun_avoin_paiva, saldovahvistus_viesti) {
		$.ajax({
			async: true,
			type: 'POST',
			data: {
				ajax_request: 1,
				no_head: 'yes',
				merkkaa_lahetettavaksi: 1,
				lisays: lisays,
				laskun_avoin_paiva: laskun_avoin_paiva,
				saldovahvistus_viesti: saldovahvistus_viesti,
				lasku_tunnukset: lasku_tunnukset
			},
			url: 'saldovahvistus.php'
		}).done(function(data) {
			if (console && console.log) {
				console.log('AJAX success');
				console.log(data);
			}
		});
	}
</script>
<?php

require('inc/footer.inc');

function lisaa_sessioon_saldovahvistus_rivi($lasku_tunnukset_key, $saldovahvistusrivi) {
	global $kukarow, $yhtiorow;

	if (!isset($_SESSION['valitut_laskut'][$lasku_tunnukset_key])) {
		$_SESSION['valitut_laskut'][$lasku_tunnukset_key] = $saldovahvistusrivi;
		return true;
	}

	return false;
}

function generoi_sahkopostit($request) {
	global $kukarow, $yhtiorow;

	foreach ($_SESSION['valitut_laskut'] as $valittu_saldovahvistus) {
		$request['lasku_tunnukset'] = $valittu_saldovahvistus['lasku_tunnukset'];
		$saldovahvistus = hae_myyntilaskuja_joilla_avoin_saldo($request);

		//Valittu saldovahvistusviesti
		$saldovahvistus['saldovahvistus_viesti'] = search_array_key_for_value_recursive($request['saldovahvistus_viestit'], 'selite', $valittu_saldovahvistus['saldovahvistus_viesti']);
		$saldovahvistus['saldovahvistus_viesti'] = $saldovahvistus['saldovahvistus_viesti'][0];
		$saldovahvistus['laskun_avoin_paiva'] = $valittu_saldovahvistus['laskun_avoin_paiva'];

		if ($saldovahvistus['asiakas']['talhal_email'] != '') {
			$pdf_filepath = hae_saldovahvistus_pdf($saldovahvistus);

			$params = array(
				"to"			 => $saldovahvistus['asiakas']['talhal_email'],
				"subject"		 => t('Saldovahvistus', $saldovahvistus['asiakas']['kieli']),
				"ctype"			 => "text",
				"body"			 => t('Oheessa avoinsaldotilanteenne pdf-liitteenä', $saldovahvistus['asiakas']['kieli']),
				"attachements"	 => array(
					array(
						"filename"		 => $pdf_filepath,
						"newfilename"	 => t('Saldovahvistus', $saldovahvistus['asiakas']['kieli']).".pdf",
						"ctype"			 => "pdf"
					)
				)
			);

			$onko_sahkoposti_lahetetty = pupesoft_sahkoposti($params);

			if ($onko_sahkoposti_lahetetty) {
				merkkaa_saldovahvistus_lahetetyksi($saldovahvistus);
			}
		}
	}
}

function merkkaa_saldovahvistus_lahetetyksi($saldovahvistus) {
	global $kukarow, $yhtiorow;

	$query = "	INSERT INTO karhukierros
				SET tyyppi = 'S',
				pvm = CURRENT_DATE,
				viesti = '{$saldovahvistus['saldovahvistus_viesti']}',
				avoin_saldo_pvm = '".date('Y-m-d', strtotime($saldovahvistus['laskun_avoin_paiva']))."',
				yhtio = '{$kukarow['yhtio']}'";
	pupe_query($query);
	$karhukierros_tunnus = mysql_insert_id();

	foreach ($saldovahvistus['lasku_tunnukset'] as $lasku_tunnus) {
		$query = "	INSERT INTO karhu_lasku
					SET ktunnus = '{$karhukierros_tunnus}',
					ltunnus = '{$lasku_tunnus}'";
		pupe_query($query);
	}
}

function echo_saldovahvistukset($request) {
	global $kukarow, $yhtiorow, $pupe_DataTables, $palvelin2;

//	pupe_DataTables(array(array($pupe_DataTables, 6, 8, false, false, true)));
//	echo "<table class='display'>";

	pupe_DataTables(array(array($pupe_DataTables, 6, 8, false, false, true)));

	echo "<table class='display dataTable' id='{$pupe_DataTables}'>";

	echo "<thead>";

	echo "<tr>";
	echo "<th>".t('Päivämäärä')."</th>";
	echo "<th>".t('Ytunnus')."</th>";
	echo "<th>".t('Asiakasnumero')."</th>";
	echo "<th>".t('Nimi')."</th>";
	echo "<th>".t('Saldo')."</th>";
	echo "<th>".t('Viesti')."</th>";
	echo "<th class='hidden'></th>";
	echo "<th class='hidden'></th>";
	echo "</tr>";

	echo "<tr>";
	echo "<td><input type='text' class='search_field' name='search_paivamaara'></td>";
	echo "<td><input type='text' class='search_field' name='search_ytunnus'></td>";
	echo "<td><input type='text' class='search_field' name='search_asiakasnumero'></td>";
	echo "<td><input type='text' class='search_field' name='search_nimi'></td>";
	echo "<td><input type='text' class='search_field' name='search_saldo'></td>";
	echo "<td><input type='text' class='search_field' name='search_viesti'></td>";
	//@TODO LAITA TÄMÄ CHECKED
	echo "<td><input type='checkbox' id='valitse_kaikki' /></td>";
	echo "<td class='hidden'></td>";
	echo "</tr>";

	echo "</thead>";

	echo "<tbody>";

	$kpl = count($request['valitut_laskut']);
	$i = 1;
	$viimeinen = false;
	foreach ($request['valitut_laskut'] as $lasku) {
		if ($i == $kpl) {
			$viimeinen = true;
		}
		echo_saldovahvistus_rivi($lasku, $request, true, $viimeinen);
		$i++;
	}

	foreach ($request['laskut'] as $lasku) {
		echo_saldovahvistus_rivi($lasku, $request, false);
	}

	echo "</tbody>";

	echo "</table>";

	echo "<form method='POST' action = ''>";
	echo "<input type='hidden' name='tee' value='laheta_sahkopostit' />";
	echo "<input type='submit' value='".t('Lähetä')."' />";
	echo "</form>";
}

function echo_saldovahvistus_rivi($saldovahvistusrivi, $request, $valitut = false, $viimeinen = false) {
	global $kukarow, $yhtiorow, $palvelin2;

	$lopetus = $palvelin2."tilauskasittely/saldovahvistus.php////tee={$request['tee']}//ryhmittely_tyyppi={$request['ryhmittely_tyyppi']}//ryhmittely_arvo={$request['ryhmittely_arvo']}//pp={$request['pp']}//kk={$request['kk']}//vv={$request['vv']}//saldovahvistus_viesti={$request['saldovahvistus_viesti']}";

	$tr_class = "";
	if ($viimeinen) {
		$tr_class = "border_bottom";
	}

	echo "<tr class='saldovahvistus_rivi aktiivi {$tr_class}'>";

	echo "<td valign='top'>";
	if ($valitut) {
		$saldovahvistusrivi['laskun_avoin_paiva'] = $saldovahvistusrivi['laskun_avoin_paiva'];
	}
	else {
		$saldovahvistusrivi['laskun_avoin_paiva'] = date('Y-m-d', strtotime($request['paiva']));
	}
	echo "<input type='hidden' class='laskun_avoin_paiva' value='{$saldovahvistusrivi['laskun_avoin_paiva']}' />";
	echo date('d.m.Y', strtotime($saldovahvistusrivi['laskun_avoin_paiva']));
	echo "</td>";

	echo "<td valign='top'>{$saldovahvistusrivi['ytunnus']}</td>";
	echo "<td valign='top'>";
	$i = 0;
	$asiakasnumerot_string = "";
	foreach ($saldovahvistusrivi['asiakasnumerot'] as $asiakasnumero) {
		$asiakasnumero['asiakasnumero'] = "<a href='{$palvelin2}yllapito.php?toim=asiakas&tunnus={$asiakasnumero['asiakas_tunnus']}&lopetus={$lopetus}'>{$asiakasnumero['asiakasnumero']}</a>";
		$asiakasnumerot_string .= $asiakasnumero['asiakasnumero'].' / ';
		if ($i != 0 and $i % 10 == 0) {
			$asiakasnumerot_string = substr($asiakasnumerot_string, 0, -3);
			$asiakasnumerot_string .= '<br/>';
		}
		$i++;
	}
	$asiakasnumerot_string = substr($asiakasnumerot_string, 0, -3);
	echo $asiakasnumerot_string;
	echo "</td>";
	echo "<td valign='top'>{$saldovahvistusrivi['asiakas_nimi']}</td>";
	echo "<td valign='top' align='right'>";
	echo $saldovahvistusrivi['avoin_saldo_summa'];
	echo "</td>";

	if ($valitut) {
		$saldovahvistusrivi['saldovahvistus_viesti'] = $saldovahvistusrivi['saldovahvistus_viesti'];
	}
	else {
		$saldovahvistusrivi['saldovahvistus_viesti'] = $request['saldovahvistus_viesti'];
	}

	echo "<td class='saldovahvistus_viesti' valign='top'>";
	echo $saldovahvistusrivi['saldovahvistus_viesti'];
	if ($saldovahvistusrivi['asiakas']['talhal_email'] == '') {
		echo "<br/>";
		echo "<font class='error'>".t('Email puuttuu')."</font>";
	}
	echo "</td>";

	echo "<td>";
	//@TODO POISTA $valitut iffi ja $chk muuttuja kun siirrytään tuotantoon
	$chk = "";
	if ($valitut) {
		$chk = "CHECKED";
	}
	echo "<input type='checkbox' class='saldovahvistus_rivi_valinta' {$chk}/>";
	echo "</td>";

	// .nayta_pdf_td ja .lasku_tunnus, jotta .saldovahvistus_rivi_valinta löytää lasku_tunnukset, jotka lähtee ajaxin mukana
	echo "<td class='back nayta_pdf_td'>";
	echo "<form method='POST' action=''>";
	echo "<input type='submit' value='".t('Näytä pdf')."' />";
	echo "<input type='hidden' name='tee' value='nayta_saldovahvistus_pdf' />";
	echo "<input type='hidden' name='nayta_pdf' value='1' />";
	echo "<input type='hidden' name='saldovahvistus_viesti' value='{$saldovahvistusrivi['saldovahvistus_viesti']}' />";
	echo "<input type='hidden' name='ryhmittely_tyyppi' value='{$request['ryhmittely_tyyppi']}' />";
	echo "<input type='hidden' name='ryhmittely_arvo' value='{$request['ryhmittely_arvo']}' />";
	foreach ($saldovahvistusrivi['lasku_tunnukset'] as $lasku_tunnus) {
		echo "<input type='hidden' class='lasku_tunnus' name='lasku_tunnukset[]' value='{$lasku_tunnus}' />";
	}
	echo "</form>";

	echo "<br/>";

	echo "<form method='POST' action=''>";
	echo "<input type='submit' value='".t('Tulosta pdf')."' />";
	echo "<input type='hidden' name='tee' value='tulosta_saldovahvistus_pdf' />";
	echo "<input type='hidden' name='saldovahvistus_viesti' value='{$saldovahvistusrivi['saldovahvistus_viesti']}' />";
	echo "<input type='hidden' name='ryhmittely_tyyppi' value='{$request['ryhmittely_tyyppi']}' />";
	echo "<input type='hidden' name='ryhmittely_arvo' value='{$request['ryhmittely_arvo']}' />";
	foreach ($saldovahvistusrivi['lasku_tunnukset'] as $lasku_tunnus) {
		echo "<input type='hidden' name='lasku_tunnukset[]' value='{$lasku_tunnus}' />";
	}
	echo "</form>";
	echo "</td>";

	echo "</tr>";

//	if (!$valitut) {
//		$lasku_tunnukset_key = implode('', $saldovahvistusrivi['lasku_tunnukset']);
//		lisaa_sessioon_saldovahvistus_rivi($lasku_tunnukset_key, $saldovahvistusrivi);
//	}
}

function echo_kayttoliittyma($request) {
	global $kukarow, $yhtiorow;

	echo "<form method='POST' action=''>";
	echo "<input type='hidden' name='tee' value='aja_saldovahvistus' />";
	echo "<table>";

	echo "<tr>";
	echo "<th>".t('Ryhmittely').":</th>";
	echo "<td>";
	echo "<select name='ryhmittely_tyyppi'>";
	$sel = "";
	foreach ($request['ryhmittely_tyypit'] as $ryhmittely_tyyppi_key => $ryhmittely_tyyppi) {
		if ($request['ryhmittely_tyyppi'] == $ryhmittely_tyyppi_key) {
			$sel = "SELECTED";
		}
		echo "<option value='{$ryhmittely_tyyppi_key}' {$sel}>{$ryhmittely_tyyppi}</option>";
		$sel = "";
	}
	echo "</select>";
	echo "<input type='text' name='ryhmittely_arvo' value='{$request['ryhmittely_arvo']}'/>";

	echo '('.t('tyhjä').' = '.t('saat kaikki ytunnukset').')';
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t('Päivämäärä').":</th>";
	echo "<td>";
	echo "<input type='text' name='pp' size='3' value='{$request['pp']}' />";
	echo "<input type='text' name='kk' size='3' value='{$request['kk']}' />";
	echo "<input type='text' name='vv' size='5' value='{$request['vv']}' />";
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t('Saldovahvistuksen viesti').":</th>";
	echo "<td>";
	echo "<select name='saldovahvistus_viesti'>";
	$sel = "";
	foreach ($request['saldovahvistus_viestit'] as $saldovahvistus_viesti) {
		if ($request['saldovahvistus_viesti'] == $saldovahvistus_viesti['selite']) {
			$sel = "SELECTED";
		}
		echo "<option value='{$saldovahvistus_viesti['selite']}' {$sel}>{$saldovahvistus_viesti['selite']}</option>";
		$sel = "";
	}
	echo "</select>";
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t('Tallenna exceliin')."</th>";
	echo "<td>";
	$chk = "";
	if (!empty($request['tallenna_exceliin'])) {
		$chk = "CHECKED";
	}
	echo "<input type='checkbox' name='tallenna_exceliin' {$chk} />";
	echo "</td>";
	echo "</tr>";

	echo "</table>";

	echo "<input type='submit' value='".t('Aja')."' />";

	echo "</form>";
}

function generoi_custom_excel_tiedosto($request) {
	global $kukarow, $yhtiorow;

	$xls = new pupeExcel();
	$rivi = 0;
	$sarake = 0;

	$xls->write($rivi, $sarake, t('Päivämäärä'), array("bold" => TRUE));
	$sarake++;

	$xls->write($rivi, $sarake, t('Ytunnus'), array("bold" => TRUE));
	$sarake++;

	$xls->write($rivi, $sarake, t('Asiakasnumero'), array("bold" => TRUE));
	$sarake++;

	$xls->write($rivi, $sarake, t('Nimi'), array("bold" => TRUE));
	$sarake++;

	$xls->write($rivi, $sarake, t('Saldo'), array("bold" => TRUE));
	$sarake++;

	$xls->write($rivi, $sarake, t('Viesti'), array("bold" => TRUE));
	$sarake++;

	$xls->write($rivi, $sarake, t('Valittu'), array("bold" => TRUE));
	$sarake++;

	$rivi++;
	$sarake = 0;

	foreach ($request['valitut_laskut'] as $valittu_rivi) {
		$xls->write($rivi, $sarake, date('d.m.Y', strtotime($valittu_rivi['laskun_avoin_paiva'])));
		$sarake++;

		$xls->write($rivi, $sarake, $valittu_rivi['ytunnus']);
		$sarake++;

		$asiakasnumerot_string = "";
		foreach ($valittu_rivi['asiakasnumerot'] as $asiakasnumero) {
			$asiakasnumerot_string .= $asiakasnumero['asiakasnumero'].' / ';
		}
		$asiakasnumerot_string = substr($asiakasnumerot_string, 0, -3);

		$xls->write($rivi, $sarake, $asiakasnumerot_string);
		$sarake++;

		$xls->write($rivi, $sarake, $valittu_rivi['asiakas_nimi']);
		$sarake++;

		$xls->write($rivi, $sarake, $valittu_rivi['avoin_saldo_summa']);
		$sarake++;

		$xls->write($rivi, $sarake, $valittu_rivi['saldovahvistus_viesti']);
		$sarake++;

		$xls->write($rivi, $sarake, t('Kyllä'));
		$sarake++;

		$rivi++;
		$sarake = 0;
	}

	foreach ($request['laskut'] as $saldovahvistusrivi) {
		$xls->write($rivi, $sarake, date('d.m.Y', strtotime($request['paiva'])));
		$sarake++;

		$xls->write($rivi, $sarake, $saldovahvistusrivi['ytunnus']);
		$sarake++;

		$asiakasnumerot_string = "";
		foreach ($saldovahvistusrivi['asiakasnumerot'] as $asiakasnumero) {
			$asiakasnumerot_string .= $asiakasnumero['asiakasnumero'].' / ';
		}
		$asiakasnumerot_string = substr($asiakasnumerot_string, 0, -3);

		$xls->write($rivi, $sarake, $asiakasnumerot_string);
		$sarake++;

		$xls->write($rivi, $sarake, $saldovahvistusrivi['asiakas_nimi']);
		$sarake++;

		$xls->write($rivi, $sarake, $saldovahvistusrivi['avoin_saldo_summa']);
		$sarake++;

		$xls->write($rivi, $sarake, $request['saldovahvistus_viesti']);
		$sarake++;

		$xls->write($rivi, $sarake, t('Ei'));
		$sarake++;

		$rivi++;
		$sarake = 0;
	}

	return $xls->close();
}
