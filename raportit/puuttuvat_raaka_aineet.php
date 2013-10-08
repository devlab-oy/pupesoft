<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

require("../inc/parametrit.inc");
require('valmistuslinjat.inc');
require('validation/Validation.php');

echo "<font class='head'>".t("Puuttuvat raaka-aineet")."</font><hr>";

$request = array(
	'tee'				 => $tee,
	'alku_pp'			 => $alku_pp,
	'alku_kk'			 => $alku_kk,
	'alku_vv'			 => $alku_vv,
	'loppu_pp'			 => $loppu_pp,
	'loppu_kk'			 => $loppu_kk,
	'loppu_vv'			 => $loppu_vv,
	'alku_pvm'			 => '',
	'loppu_pvm'			 => '',
	'valmistuksen_tila'	 => $valmistuksen_tila,
	'valmistuslinja'	 => $valmistuslinja,
	'mul_osasto'		 => $mul_osasto,
	'mul_try'			 => $mul_try,
	'mul_tme'			 => $mul_tme,
);

$request['valmistuslinjat'] = hae_valmistuslinjat();
$request['valmistuksien_tilat'] = hae_valmistuksien_tilat();

init($request);

$valid = validate($request);

echo_kayttoliittyma($request);

echo "<br/>";
echo "<br/>";
if ($request['tee'] == 'ajaraportti') {
	if ($valid) {
		$request['valmistukset'] = hae_valmistukset_joissa_raaka_aine_ei_riita($request);

		echo_valmistukset_joissa_raaka_aine_ei_riita($request);
	}
}

function hae_valmistukset_joissa_raaka_aine_ei_riita($request) {
	global $kukarow, $yhtiorow;

	$lasku_where = "";
	$valmistuksen_tila = search_array_key_for_value_recursive($request['valmistuksien_tilat'], 'value', $request['valmistuksen_tila']);
	$lasku_where .= $valmistuksen_tila[0]['query_where'];

	if (isset($request['valmistuslinja']) and $request['valmistuslinja'] != '') {
		$lasku_where .= "	AND lasku.kohde = '{$request['valmistuslinja']}'";
	}

	$tuote_join = "";
	if (!empty($request['mul_osasto'])) {
		$tuote_join .= "	AND tuote.osasto IN ('".implode("','", $request['mul_osasto'])."')";
	}

	if (!empty($request['mul_try'])) {
		$tuote_join .= "	AND tuote.try IN ('".implode("','", $request['mul_try'])."')";
	}

	if (!empty($request['mul_tme'])) {
		$tuote_join .= "	AND tuote.tuotemerkki IN ('".implode("','", $request['mul_tme'])."')";
	}

	//Haetaan valmisteet
	$query = "	SELECT lasku.tunnus AS lasku_tunnus,
				tilausrivi.tunnus AS tilausrivi_tunnus,
				tilausrivi.tuoteno,
				tilausrivi.nimitys,
				tilausrivi.tyyppi,
				lasku.kohde,
				lasku.tila,
				lasku.alatila,
				lasku.kerayspvm,
				lasku.toimaika,
				(	SELECT toimi.nimi
					FROM tuotteen_toimittajat
					JOIN toimi
					ON ( toimi.yhtio = tuotteen_toimittajat.yhtio
						AND toimi.tunnus = tuotteen_toimittajat.liitostunnus )
					WHERE tuotteen_toimittajat.yhtio = '{$kukarow['yhtio']}'
					AND tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno
					ORDER BY tuotteen_toimittajat.jarjestys ASC
					LIMIT 1
				) AS toimittaja,
				sum(tilausrivi.varattu) AS valmistettava_kpl
				FROM lasku
				JOIN tilausrivi
				ON ( tilausrivi.yhtio = lasku.yhtio
					AND tilausrivi.otunnus = lasku.tunnus
					AND tilausrivi.tyyppi IN ('W')
					AND tilausrivi.varattu > 0)
				JOIN tuote
				ON ( tuote.yhtio = tilausrivi.yhtio
					AND tuote.tuoteno = tilausrivi.tuoteno
					AND tuote.tuotetyyppi not in ('A', 'B')
					AND tuote.ei_saldoa = ''
					{$tuote_join} )
				WHERE lasku.yhtio = '{$kukarow['yhtio']}'
				AND lasku.toimaika BETWEEN '{$request['alku_pvm']}' AND '{$request['loppu_pvm']}'
				{$lasku_where}
				GROUP BY 1,2,3,4,5,6,7,8,9,10,11
				ORDER BY lasku.tunnus ASC, tilausrivi.perheid ASC, tilausrivi.tyyppi DESC";
	$result = pupe_query($query);

	$valmistukset_joissa_raaka_aine_ei_riita = array();
	while($valmiste_rivi = mysql_fetch_assoc($result)) {
		//Haetaan valmisteen raaka-aineet
		$query = "	SELECT lasku.tunnus AS lasku_tunnus,
					tilausrivi.tunnus AS tilausrivi_tunnus,
					tilausrivi.tuoteno,
					tilausrivi.nimitys,
					tilausrivi.tyyppi,
					lasku.kohde,
					lasku.tila,
					lasku.alatila,
					lasku.kerayspvm,
					lasku.toimaika,
					(	SELECT toimi.nimi
						FROM tuotteen_toimittajat
						JOIN toimi
						ON ( toimi.yhtio = tuotteen_toimittajat.yhtio
							AND toimi.tunnus = tuotteen_toimittajat.liitostunnus )
						WHERE tuotteen_toimittajat.yhtio = '{$kukarow['yhtio']}'
						AND tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno
						ORDER BY tuotteen_toimittajat.jarjestys ASC
						LIMIT 1
					) AS toimittaja,
					sum(tilausrivi.varattu) AS valmistettava_kpl
					FROM lasku
					JOIN tilausrivi
					ON ( tilausrivi.yhtio = lasku.yhtio
						AND tilausrivi.otunnus = lasku.tunnus
						AND tilausrivi.tyyppi IN ('V')
						AND tilausrivi.varattu > 0
						AND tilausrivi.otunnus = '{$valmiste_rivi['lasku_tunnus']}'
						AND tilausrivi.perheid = '{$valmiste_rivi['tilausrivi_tunnus']}' )
					JOIN tuote
					ON ( tuote.yhtio = tilausrivi.yhtio
						AND tuote.tuoteno = tilausrivi.tuoteno
						AND tuote.tuotetyyppi not in ('A', 'B')
						AND tuote.ei_saldoa = '' )
					WHERE lasku.yhtio = '{$kukarow['yhtio']}'
					GROUP BY 1,2,3,4,5,6,7,8,9,10,11";
		$valmistus_result = pupe_query($query);

		while($valmistus_rivi = mysql_fetch_assoc($valmistus_result)) {
			list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($valmistus_rivi['tuoteno']);

			if ($saldo < $valmistus_rivi['valmistettava_kpl']) {

				$valmistus_rivi['tilattu'] = hae_tilattu_kpl($valmistus_rivi['tuoteno']);

				$valmistus_rivi['saldo'] = $saldo;
				$valmistus_rivi['hyllyssa'] = $hyllyssa;
				$valmistus_rivi['myytavissa'] = $myytavissa;

				if (empty($valmistukset_joissa_raaka_aine_ei_riita[$valmiste_rivi['lasku_tunnus']]['tilausrivit'][$valmiste_rivi['tilausrivi_tunnus']])) {
					$valmistukset_joissa_raaka_aine_ei_riita[$valmiste_rivi['lasku_tunnus']]['tilausrivit'][$valmiste_rivi['tilausrivi_tunnus']] = $valmiste_rivi;
				}

				$valmistukset_joissa_raaka_aine_ei_riita[$valmiste_rivi['lasku_tunnus']]['tilausrivit'][$valmiste_rivi['tilausrivi_tunnus']]['raaka_aineet'][] = $valmistus_rivi;
			}
		}
	}

	return $valmistukset_joissa_raaka_aine_ei_riita;
}

function hae_tilattu_kpl($tuoteno) {
	global $kukarow, $yhtiorow;

	$query = "	SELECT ifnull(sum(varattu), 0) as tilattu
				FROM tilausrivi
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tyyppi = 'O'
				AND varattu > 0
				AND tuoteno = '{$tuoteno}'";
	$result = pupe_query($query);

	$tilattu_row = mysql_fetch_assoc($result);

	return $tilattu_row['tilattu'];
}

function echo_valmistukset_joissa_raaka_aine_ei_riita($request) {
	global $kukarow, $yhtiorow;

	echo "<table>";
	foreach ($request['valmistukset'] as $valmistus) {
		foreach ($valmistus['tilausrivit'] as $tilausrivi) {
			echo "<thead>";
			echo "<tr>";
			echo "<th>".t('Valmisteen tuoteno')."</th>";
			echo "<th>".t('Valmisteen nimitys')."</th>";
			echo "<th>".t('Valmistusnumero')."</th>";
			echo "<th>".t('Valmistuslinja')."</th>";
			echo "<th>".t('Valmistetaan kpl')."</th>";
			echo "<th>".t('Valmistuksen tila')."</th>";
			echo "<th>".t('Keräyspäivä')."</th>";
			echo "<th>".t('Valmistuspäivä')."</th>";
			echo "</tr>";
			echo "</thead>";

			echo "<tbody>";
			echo "<tr class='aktiivi'>";

			echo "<td>";
			echo $tilausrivi['tuoteno'];
			echo "</td>";

			echo "<td>";
			echo $tilausrivi['nimitys'];
			echo "</td>";

			echo "<td>";
			echo $tilausrivi['lasku_tunnus'];
			echo "</td>";

			echo "<td>";
			$valmistuslinja = search_array_key_for_value_recursive($request['valmistuslinjat'], 'selite', $tilausrivi['valmistuslinja']);
			$valmistuslinja = $valmistuslinja[0];
			if (empty($valmistuslinja)) {
				echo t('Ei valmistuslinjaa');
			}
			else {
				echo $valmistuslinja['selitetark'];
			}
			echo "</td>";

			echo "<td>";
			echo $tilausrivi['valmistettava_kpl'];
			echo "</td>";

			echo "<td>";
			$laskutyyppi = $tilausrivi['tila'];
			$alatila = $tilausrivi['alatila'];
			require('inc/laskutyyppi.inc');
			echo $laskutyyppi.' '.$alatila;
			echo "</td>";

			echo "<td>";
			echo date('d.m.Y', strtotime($tilausrivi['kerayspvm']));
			echo "</td>";

			echo "<td>";
			echo date('d.m.Y', strtotime($tilausrivi['toimaika']));
			echo "</td>";

			echo "</tr>";

			echo "<tr>";
			echo "<td colspan='8'>";
			echo "&nbsp;";
			echo "</td>";
			echo "</tr>";

			echo "</tbody>";

			echo "<thead>";
			echo "<tr>";
			echo "<th>".t('Raaka-aineen tuoteno')."</th>";
			echo "<th>".t('Raaka-aineen nimitys')."</th>";
			echo "<th>".t('Valmistusnumero')."</th>";
			echo "<th>".t('Saldo')."</th>";
			echo "<th>".t('Hyllyssä')."</th>";
			echo "<th>".t('Myytävissä')."</th>";
			echo "<th>".t('Tilattu')."</th>";
			echo "<th>".t('Toimittaja')."</th>";
			echo "</tr>";
			echo "</thead>";

			echo "<tbody>";
			foreach ($tilausrivi['raaka_aineet'] as $raaka_aine) {
				echo "<tr class='aktiivi'>";

				echo "<td>";
				echo $raaka_aine['tuoteno'];
				echo "</td>";

				echo "<td>";
				echo $raaka_aine['nimitys'];
				echo "</td>";

				echo "<td>";
				echo $raaka_aine['lasku_tunnus'];
				echo "</td>";

				echo "<td>";
				echo $raaka_aine['saldo'];
				echo "</td>";

				echo "<td>";
				echo $raaka_aine['hyllyssa'];
				echo "</td>";

				echo "<td>";
				echo $raaka_aine['myytavissa'];
				echo "</td>";

				echo "<td>";
				echo $raaka_aine['tilattu'];
				echo "</td>";

				echo "<td>";
				echo $raaka_aine['toimittaja'];
				echo "</td>";

				echo "</tr>";
			}
			echo "<tr>";
			echo "<td class='back' colspan='8'>";
			echo "&nbsp;";
			echo "</td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td class='back' colspan='8'>";
			echo "&nbsp;";
			echo "</td>";
			echo "</tr>";

			echo "</tbody>";
		}
	}
	echo "</table>";
}

function init(&$request) {
	global $kukarow, $yhtiorow;

	if (empty($request['alku_pp'])) {
		$request['alku_pp'] = 1;
	}
	if (empty($request['alku_kk'])) {
		$request['alku_kk'] = date('m', strtotime('now - 1 month'));
	}
	if (empty($request['alku_vv'])) {
		$request['alku_vv'] = date('Y');
	}

	if (empty($request['loppu_pp'])) {
		$request['loppu_pp'] = date('t', strtotime('now - 1 month'));
	}
	if (empty($request['loppu_kk'])) {
		$request['loppu_kk'] = date('m', strtotime('now - 1 month'));
	}
	if (empty($request['loppu_vv'])) {
		$request['loppu_vv'] = date('Y');
	}

	$request['alku_pvm'] = "{$request['alku_vv']}-{$request['alku_kk']}-{$request['alku_pp']}";
	$request['loppu_pvm'] = "{$request['loppu_vv']}-{$request['loppu_kk']}-{$request['loppu_pp']}";
}

function validate($request) {
	global $kukarow, $yhtiorow;

	$validations = array(
		'alku_pvm'	 => 'paiva',
		'loppu_pvm'	 => 'paiva',
	);

	$required = array(
		'alku_pvm',
		'loppu_pvm'
	);
	$validator = new FormValidator($validations, $required);
	$valid = $validator->validate($request);

	if ($valid and strtotime($request['alku_pvm']) > strtotime($request['loppu_pvm'])) {
		echo "<font class='error'>".t('Alkupäivämäärä on myöhemmin kuin loppupäivämäärä')."</font>";
		echo "<br/>";
		echo "<br/>";
		$valid = false;
	}

	if (!$valid) {
		echo $validator->getScript();
	}

	return $valid;
}

function echo_kayttoliittyma($request) {
	global $kukarow, $yhtiorow;

	echo "<form method='POST' action='' name='puuttuvat_raaka_aineet_form'>";

	echo "<table>";

	echo "<tr>";
	echo "<th>".t("Syötä alkupäivämäärä (pp-kk-vvvv)")."</th>";
	echo "<td>";
	echo "<input type='text' name='alku_pp' value='{$request['alku_pp']}' size='3'>";
	echo "<input type='text' name='alku_kk' value='{$request['alku_kk']}' size='3'>";
	echo "<input type='text' name='alku_vv' value='{$request['alku_vv']}' size='5'>";
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>";
	echo "<td>";
	echo "<input type='text' name='loppu_pp' value='{$request['loppu_pp']}' size='3'>";
	echo "<input type='text' name='loppu_kk' value='{$request['loppu_kk']}' size='3'>";
	echo "<input type='text' name='loppu_vv' value='{$request['loppu_vv']}' size='5'>";
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t('Valmistuksen tila')."</th>";
	echo "<td>";
	echo "<select name='valmistuksen_tila'>";
	foreach ($request['valmistuksien_tilat'] as $tila) {
		$sel = "";
		if ($request['valmistuksen_tila'] == $tila['value']) {
			$sel = "SELECTED";
		}
		echo "<option value='{$tila['value']}' {$sel}>{$tila['dropdown_text']}</option>";
	}
	echo "</select>";
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t('Valmistuslinja')."</th>";
	echo "<td>";
	echo "<select name='valmistuslinja'>";
	echo "<option value=''>".t('Kaikki valmistuslinjat')."</option>";
	foreach ($request['valmistuslinjat'] as $_valmistuslinja) {
		$sel = "";
		if ($request['valmistuslinja'] == $_valmistuslinja['selite']) {
			$sel = "SELECTED";
		}
		echo "<option value='{$_valmistuslinja['selite']}' {$sel}>{$_valmistuslinja['selitetark']}</option>";
	}
	echo "</select>";
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Rajaa tuotekategorialla")."</th>";
	echo "<td>";
	$monivalintalaatikot = array('OSASTO', 'TRY', 'TUOTEMERKKI');
	$mul_osasto = $request['mul_osasto'];
	$mul_try = $request['mul_try'];
	$mul_tme = $request['mul_tme'];
	require ("tilauskasittely/monivalintalaatikot.inc");
	echo "</td>";
	echo "</tr>";

	echo "<tr class='back'>";
	echo "<td>";
	echo "</td>";
	echo "<td>";
	echo "<input type='hidden' value='ajaraportti' name='tee' />";
	echo "</td>";
	echo "</tr>";

	echo "</table>";

	echo "<br/>";
	echo "<input type='submit' name='submit_nappi' value='".t("Aja raportti")."'>";
	echo "</form>";
	echo "<br/>";
}

require ("inc/footer.inc");