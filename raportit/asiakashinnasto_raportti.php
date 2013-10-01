<?php

$useslave = 1;

if (isset($_POST["tee"])) {
	if ($_POST["tee"] == 'lataa_tiedosto') {
		$lataa_tiedosto = 1;
	}
	if (isset($_POST["kaunisnimi"]) and $_POST["kaunisnimi"] != '') {
		$_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
	}
}

require ("../inc/parametrit.inc");
require ("inc/pupeExcel.inc");
require ('inc/ProgressBar.class.php');

if (isset($livesearch_tee) and $livesearch_tee == "ASIAKASHAKU") {
	livesearch_asiakashaku();
	exit;
}

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

enable_ajax();

echo "<font class='head'>".t("Asiakashinnasto raportti")."</font><hr>";
?>
<style>

</style>
<script>

</script>
<?php

$request = array(
	'valittu_asiakas'		 => $valittu_asiakas,
	'valittu_asiakasryhma'	 => $valittu_asiakasryhma,
	'mitka_tuotteet'		 => $mitka_tuotteet,
	'action'				 => $action,
);

$request['asiakasryhmat'] = hae_asiakasryhmat();
$request['aleryhmat'] = hae_aleryhmat();

if ($request['action'] == 'aja_raportti') {
	echo "<font class='message'>".t("Raporttia ajetaan")."</font>";
	echo "<br/>";
	flush();

	$request['tuotteet'] = hae_asiakashinta_ja_alennus_tuotteet($request);

	$tuotteet = hae_asiakasalennukset($request);

	$xls_tiedosto = generoi_custom_excel($tuotteet);

	if (!empty($xls_tiedosto)) {
		echo_tallennus_formi($xls_tiedosto, t('Asiakashinnasto_raportti'));
		echo "<br/>";
	}
	else {
		echo t('Asiakashinnaston tuotteita ei löytynyt');
		echo "<br/>";
	}
}

echo_kayttoliittyma($request);

require ("inc/footer.inc");

function echo_kayttoliittyma($request = array()) {
	global $kukarow, $yhtiorow;

	echo "<form action='' method='POST' name='asiakashinnasto_haku_form'>";

	echo "<input type='hidden' name='action' value='aja_raportti' />";

	echo "<table>";

	echo "<tr>";
	echo "<th>".t('Asiakas').":</th>";
	echo "<td>";
	echo livesearch_kentta("asiakashinnasto_haku_form", "ASIAKASHAKU", "valittu_asiakas", 315, $request['valittu_asiakas'], 'EISUBMIT', '', 'valittu_asiakas', 'ei_break_all');
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t('Asiakasryhmä').":</th>";
	echo "<td>";
	echo "<select id='valittu_asiakasryhma' name='valittu_asiakasryhma'>";
	foreach ($request['asiakasryhmat'] as $asiakasryhma) {
		$sel = "";
		//absoluuttinen vertaus tarvitaan, koska asiakasryhmän tunnuksessa voi olla leading zero 005023 == 5023
		if ($request['valittu_asiakasryhma'] === $asiakasryhma['selite']) {
			$sel = "SELECTED";
		}
		echo "<option value='{$asiakasryhma['selite']}' {$sel}>{$asiakasryhma['selite']} - {$asiakasryhma['selitetark']}</option>";
	}
	echo "</select>";
	echo "</td>";
	echo "</tr>";

	echo "<tr>";

	$sel = array(
		'kaikki'						 => $request['mitka_tuotteet'] == 'kaikki' ? 'CHECKED' : '',
		'tuotteet_joilla_asiakashinta'	 => $request['mitka_tuotteet'] == 'tuotteet_joilla_asiakashinta' ? 'CHECKED' : '',
	);
	if (empty($request['mitka_tuotteet'])) {
		$sel['tuotteet_joilla_asiakashinta'] = "CHECKED";
	}
	echo "<th>".t('Tuotteet').":</th>";

	echo "<td>";
	echo "<input type='radio' {$sel['kaikki']} name='mitka_tuotteet' value='kaikki' />";
	echo t('Kaikki tuotteet');
	echo "<br/>";
	echo "<br/>";
	echo "<input type='radio' {$sel['tuotteet_joilla_asiakashinta']} name='mitka_tuotteet' value='tuotteet_joilla_asiakashinta' />";
	echo t('Tuotteet joilla on asiakashinta');
	echo "</td>";

	echo "</tr>";
	echo "</table>";

	echo "<input type='submit' value='".t('Hae')."' />";

	echo "</form>";
}

function hae_asiakasryhmat() {
	global $kukarow, $yhtiorow;

	$query = "	SELECT *
				FROM avainsana
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND laji = 'ASIAKASRYHMA'";
	$result = pupe_query($query);

	$asiakasryhmat = array();
	while ($asiakasryhma = mysql_fetch_assoc($result)) {
		$asiakasryhmat[] = $asiakasryhma;
	}

	$ei_valintaa_array = array(
		'selite'	 => '',
		'selitetark' => t('Ei valintaa'),
	);
	array_unshift($asiakasryhmat, $ei_valintaa_array);
	return $asiakasryhmat;
}

function hae_aleryhmat() {
	global $kukarow, $yhtiorow;

	$query = "	SELECT *
				FROM perusalennus
				WHERE yhtio = '{$kukarow['yhtio']}'";
	$result = pupe_query($query);

	$aleryhmat = array();
	while ($aleryhma = mysql_fetch_assoc($result)) {
		$aleryhmat[] = $aleryhma;
	}

	return $aleryhmat;
}

function hae_asiakashinta_ja_alennus_tuotteet(&$request) {
	global $kukarow, $yhtiorow;

	$tuotteet = array();
	$ryhmat = array();

	$query = "	SELECT group_concat(parent.tunnus) tunnukset
				FROM puun_alkio
				JOIN dynaaminen_puu AS node ON (puun_alkio.yhtio = node.yhtio and puun_alkio.laji = node.laji and puun_alkio.puun_tunnus = node.tunnus)
				JOIN dynaaminen_puu AS parent ON (parent.yhtio = node.yhtio AND parent.laji = node.laji AND parent.lft <= node.lft AND parent.rgt >= node.lft AND parent.lft > 0)
				WHERE puun_alkio.yhtio = '{$kukarow['yhtio']}'
				AND puun_alkio.laji    = 'ASIAKAS'
				AND puun_alkio.liitos  = '{$request['valittu_asiakas']}'";
	$result = pupe_query($query);
	$puun_tunnukset = mysql_fetch_assoc($result);

	$asiakkaan_puiden_tunnukset = $puun_tunnukset['tunnukset'] !== NULL ? " OR asiakas_segmentti IN ({$puun_tunnukset['tunnukset']})" : "";

	$asiakashinnasto_where = "";
	if (!empty($request['valittu_asiakas'])) {
		//kun ollaan valittu asiakas, halutaan näyttää asiakkaan sekä asiakkaan asiakasryhmän asiakashinnat, alennukset
		$request['asiakas'] = hae_asiakas($request['valittu_asiakas']);
		$asiakashinnasto_where .= " AND (asiakas = '{$request['valittu_asiakas']}' OR asiakas_ryhma = '{$request['asiakas']['ryhma']}')";
	}
	if (!empty($request['valittu_asiakasryhma'])) {
		$asiakashinnasto_where .= " AND asiakas_ryhma = '{$request['valittu_asiakasryhma']}'";
	}

	$query = "	SELECT ryhma, tuoteno
				FROM asiakashinta
				WHERE yhtio = '{$kukarow['yhtio']}'
				{$asiakashinnasto_where}
				{$asiakkaan_puiden_tunnukset}";
	$result = pupe_query($query);

	while ($asiakashinta_row = mysql_fetch_assoc($result)) {
		if ($asiakashinta_row['ryhma'] != "") {
			$ryhmat[$asiakashinta_row['ryhma']] = 0;
		}
		elseif ($asiakashinta_row['tuoteno'] != "") {
			$tuotteet[$asiakashinta_row['tuoteno']] = 0;
		}
	}

	if ($request['mitka_tuotteet'] == 'kaikki') {
		$query = "	SELECT ryhma, tuoteno
					FROM asiakasalennus
					WHERE yhtio = '{$kukarow['yhtio']}'
					{$asiakashinnasto_where}";
		$result = pupe_query($query);

		while ($asiakasalennus_row = mysql_fetch_assoc($result)) {
			if ($asiakasalennus_row['ryhma'] != "") {
				$ryhmat[$asiakasalennus_row['ryhma']] = 0;
			}
			elseif ($asiakasalennus_row['tuoteno'] != "") {
				$tuotteet[$asiakasalennus_row['tuoteno']] = 0;
			}
		}

		$query = "	SELECT aleryhma, tuoteno
					FROM tuote
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND status != 'P'";
		$result = pupe_query($query);

		while ($tuote = mysql_fetch_assoc($result)) {
			$tuotteet[$tuote['tuoteno']] = 0;
		}
	}

	foreach ($ryhmat as $ryhma => $devnull) {

		$query = "	SELECT tuoteno
					FROM tuote
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND status != 'P'
					AND aleryhma = '{$ryhma}'";
		$ryhmares = pupe_query($query);

		while ($ryhmarow = mysql_fetch_assoc($ryhmares)) {
			$tuotteet[$ryhmarow['tuoteno']] = 0;
		}
	}

	return $tuotteet;
}

function hae_asiakasalennukset($request) {
	global $kukarow, $yhtiorow;

	$tuotenumerot = array_keys($request['tuotteet']);

	$query = "	SELECT *
				FROM tuote
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tuoteno IN ('".implode("','", $tuotenumerot)."')
				ORDER BY tuote.aleryhma ASC";
	$result = pupe_query($query);

	$tuotteet = array();
	while ($tuote = mysql_fetch_assoc($result)) {
		if (!empty($request['valittu_asiakas'])) {
			$query = "	SELECT tuoteno,
						hinta
						FROM asiakashinta
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tuoteno = '{$tuote['tuoteno']}'
						AND (
							asiakas = '{$request['valittu_asiakas']}'
							OR asiakas_ryhma = '{$request['asiakas']['ryhma']}'
						)
						ORDER BY asiakas DESC";
			$asiakashinta_result = pupe_query($query);
			$asiakashinta = mysql_fetch_assoc($asiakashinta_result);
			list($hinta, $netto, $ale, $alehinta_alv, $alehinta_val) = alehinta(array('liitostunnus' => $request['valittu_asiakas']), $tuote, 1, '', '', array(), '', '', '', '');
		}
		else {
			$query = "	SELECT tuoteno,
						hinta
						FROM asiakashinta
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tuoteno = '{$tuote['tuoteno']}'
						AND asiakas_ryhma = '{$request['valittu_asiakasryhma']}'";
			$asiakashinta_result = pupe_query($query);
			$asiakashinta = mysql_fetch_assoc($asiakashinta_result);
			list($hinta, $netto, $ale, $alehinta_alv, $alehinta_val) = alehinta(array(), $tuote, 1, '', '', array(), '', '', '', $request['valittu_asiakasryhma']);
		}

		$query = "	SELECT *
					FROM tuotteen_toimittajat
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tuoteno = '{$tuote['tuoteno']}'
					ORDER BY jarjestys ASC
					LIMIT 1";
		$tuotteen_toimittaja_result = pupe_query($query);
		$tuotteen_toimittaja_row = mysql_fetch_assoc($tuotteen_toimittaja_result);

		$alennettu_hinta = ( 1 - ( $ale['ale1'] / 100 ) ) * $tuote['myyntihinta'];

		$alennusryhma = search_array_key_for_value_recursive($request['aleryhmat'], 'ryhma', $tuote['aleryhma']);

		if (empty($asiakashinta['hinta'])) {
			if ($alennettu_hinta == 0) {
				$kateprosentti = number_format(0, 2);
			}
			else {
				$kateprosentti = number_format((1 - ($tuotteen_toimittaja_row['ostohinta'] / $alennettu_hinta)) * 100, 2);
			}

		}
		else {
			if ($asiakashinta['hinta'] == 0) {
				$kateprosentti = number_format(0, 2);
			}
			else {
				$kateprosentti = number_format((1 - ($tuotteen_toimittaja_row['ostohinta'] / $asiakashinta['hinta'])) * 100, 2);
			}
		}
		$tuote_temp = array(
			'aleryhma'			 => $alennusryhma[0],
			'tuoteno'			 => $tuote['tuoteno'],
			'tuote_nimi'		 => $tuote['nimitys'],
			'kappalemaara'		 => 1,
			'yksikko'			 => $tuote['yksikko'],
			'paivitys_pvm'		 => $tuote['muutospvm'],
			'ostohinta'			 => number_format($tuotteen_toimittaja_row['ostohinta'], 2),
			'kehahin'			 => number_format($tuote['kehahin'], 2),
			'ovh_hinta'			 => number_format($tuote['myyntihinta'], 2),
			'ryhman_ale'		 => number_format($alennettu_hinta, 2),
			'hinnasto_hinta'	 => (empty($asiakashinta['hinta']) ? '' : number_format($asiakashinta['hinta'], 2)),
			'ale_prosentti'		 => '',
			'tarjous_hinta'		 => '',
			'alennus_prosentti'	 => '',
			'kate_prosentti'	 => $kateprosentti,
		);

		$tuotteet[] = $tuote_temp;
	}

	return $tuotteet;
}

function generoi_custom_excel($tuotteet) {
	global $kukarow, $yhtiorow;

	if (count($tuotteet) == 0) {
		return false;
	}

	$xls_progress_bar = new ProgressBar(t("Tallennetaan exceliin"));
	$xls_progress_bar->initialize(count($tuotteet));

	$xls = new pupeExcel();
	$rivi = 0;
	$sarake = 0;
	$edellinen_ryhma = null;
	$headerit = array(
		'tuoteno'			 => t('Tuoteno'),
		'tuote_nimi'		 => t('Tuotteen nimi'),
		'kappalemaara'		 => t('Kappalemaara'),
		'yksikko'			 => t('Yksikkö'),
		'paivitys_pvm'		 => t('Päivitys päivämäärä'),
		'ostohinta'			 => t('Ostohinta'),
		'kehahin'			 => t('Keskihankintahinta'),
		'ovh_hinta'			 => t('Ovh').'-'.t('Hinta'),
		'ryhman_ale'		 => t('Ryhmän ale'),
		'hinnasto_hinta'	 => t('Hinnasto hinta'),
		'ale_prosentti'		 => t('Ale prosentti'),
		'tarjous_hinta'		 => t('Alennettu hinta'),
		'alennus_prosentti'	 => t('Alennus prosentti'),
		'kate_prosentti'	 => t('Kate prosentti'),
	);

	foreach ($headerit as $header) {
		$xls->write($rivi, $sarake, $header, array('bold' => true));
		$sarake++;
	}
	$sarake = 0;
	$rivi++;

	foreach ($tuotteet as $tuote) {
		if ($tuote['aleryhma']['ryhma'] != $edellinen_ryhma) {
			$xls->write($rivi, $sarake, t('Ryhmä'), array('bold' => true));
			$sarake++;
			$xls->write($rivi, $sarake, $tuote['aleryhma']['ryhma'], array('bold' => true));
			$sarake++;
			$xls->write($rivi, $sarake, $tuote['aleryhma']['selite'], array('bold' => true));

			$rivi++;
			$sarake = 0;
		}

		$xls->write($rivi, $sarake, $tuote['tuoteno']);
		$sarake++;
		$xls->write($rivi, $sarake, $tuote['tuote_nimi']);
		$sarake++;
		$xls->write($rivi, $sarake, $tuote['kappalemaara']);
		$sarake++;
		$xls->write($rivi, $sarake, $tuote['yksikko']);
		$sarake++;
		$xls->write($rivi, $sarake, date('d.m.Y', strtotime($tuote['paivitys_pvm'])));
		$sarake++;
		$xls->write($rivi, $sarake, $tuote['ostohinta']);
		$sarake++;
		$xls->write($rivi, $sarake, $tuote['kehahin']);
		$sarake++;
		$xls->write($rivi, $sarake, $tuote['ovh_hinta']);
		$sarake++;
		$xls->write($rivi, $sarake, $tuote['ryhman_ale']);
		$sarake++;
		$xls->write($rivi, $sarake, $tuote['hinnasto_hinta']);
		$sarake++;
		$xls->write($rivi, $sarake, $tuote['ale_prosentti']);
		$sarake++;
		$xls->write($rivi, $sarake, $tuote['tarjous_hinta']);
		$sarake++;
		$xls->write($rivi, $sarake, $tuote['alennus_prosentti']);
		$sarake++;
		$xls->write($rivi, $sarake, $tuote['kate_prosentti']);
		$sarake++;

		$xls_progress_bar->increase();

		$edellinen_ryhma = $tuote['aleryhma']['ryhma'];
		$sarake = 0;
		$rivi++;
	}

	echo "<br/>";

	$xls_tiedosto = $xls->close();

	return $xls_tiedosto;
}
