<?php

require "../inc/parametrit.inc";
require_once '../inc/pupeExcel.inc';

echo "<font class='head'>".t('Matkalaskuraportti')."</font><hr>";

$request_params = array(
	"ajotapa" => $ajotapa,
	"tuotetyypit" => $tuotetyypit,
	"jarjestys" => $jarjestys,
	"mul_kustp" => $mul_kustp,
	"kenelta_kustp" => $kenelta_kustp,
	"ruksit" => $ruksit,
	"tuotenro" => $tuotenro,
	"toimittajanro" => $toimittajanro,
	"matkalaskunro" => $matkalaskunro,
	"tuotteet_lista" => $tuotteet_lista,
	"piilota_kappaleet" => $piilota_kappaleet,
	"nimitykset" => $nimitykset,
	"tilrivikomm" => $tilrivikomm,
	"laskunro" => $laskunro,
	"maksutieto" => $maksutieto,
	"tapahtumapaiva" => $tapahtumapaiva,
	"ppa" => $ppa,
	"kka" => $kka,
	"vva" => $vva,
	"ppl" => $ppl,
	"kkl" => $kkl,
	"vvl" => $vvl,
	"debug" => 1,
);

if($request_params['debug'] == 1) {
	echo "<pre>";
	var_dump($_REQUEST);
	echo "</pre>";
}

echo_matkalaskuraportti_form($request_params);

if($tee == 'aja_raportti') {
	$rivit = generoi_matkalaskuraportti_rivit($request_params);

	//päätetään mitä tehdään datalle
	//säilytetään mahdollisuus printata myös pdf:lle tiedot
	$request_params['tiedosto_muoto'] = "xls";
	if($request_params['tiedosto_muoto'] == "xls") {
		generoi_excel_tiedosto($rivit);
	}
}

function generoi_matkalaskuraportti_rivit($request_params) {
	global $kukarow;

	$where = generoi_where_ehdot($request_params);
	$select = generoi_select($request_params);
	$group = generoi_group_by($request_params);
	$tuote_join = generoi_tuote_join($request_params);
	$toimi_join = generoi_toimi_join($request_params);
	$kustannuspaikka_join = generoi_kustannuspaikka_join($request_params);

	$query = "	SELECT
				{$select}
				FROM lasku
				JOIN tilausrivi
				ON ( tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus AND tilausrivi.tyyppi = 'M')
				JOIN tilausrivin_lisatiedot
				ON ( tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus )
				JOIN tuote
				{$tuote_join}
				JOIN toimi
				{$toimi_join}
				JOIN kustannuspaikka
				{$kustannuspaikka_join}
				{$where}
				{$group}";

	if($request_params['debug'] == 1) {
		echo "<pre>";
		var_dump($query);
		echo "</pre>";
	}

	//$result = pupe_query($query);

	$rivit = array();
	while($rivi = mysql_fetch_assoc($result)) {
		$rivit[] = $rivi;
	}

	return $rivit;
}

function generoi_where_ehdot($request_params) {
	global $kukarow;

	$where = 'WHERE ';

	if(!empty($request_params['ppa']) and !empty($request_params['kka']) and !empty($request_params['vva']) and !empty($request_params['ppl']) and !empty($request_params['kkl']) and !empty($request_params['vvl'])) {
		$where .= aika_ehto_where($request_params);
	}

	if(!empty($request_params['ajotapa'])) {
		$where .= ajotapa_where($request_params);
	}

	if(!empty($request_params['matkalaskunro'])) {
		$where .= matkalaskunro_where($request_params);
	}

	return $where;
}

function aika_ehto_where($request_params) {
	global $kukarow;
	
	return "lasku.yhtio = '{$kukarow['yhtio']}'
		AND (lasku.tapvm >= '{$request_params['vva']}-{$request_params['kka']}-{$request_params['ppa']}'
			AND lasku.tapvm < '{$request_params['vvl']}-{$request_params['kkl']}-{$request_params['ppl']}') ";
}

function ajotapa_where($request_params) {
	$where = "";
	switch ($request_params['ajotapa']) {
		case 'keskeneraiset':
			$where .= "AND lasku.tila = 'H' AND lasku.alatila = 'M'";
			break;
		case 'maksamattomat':
			$where .= "AND lasku.tila = 'H' AND lasku.alatila = ''";
			break;
		case 'maksetut':
			$where .= "AND lasku.tila = 'Y' AND lasku.alatila = ''";
			break;
		case 'keskeneraiset_maksamattomat':
			$where .= "AND (lasku.tila = 'H' AND lasku.alatila = 'M') OR (lasku.tila = 'H' AND lasku.alatila = '')";
			break;
		case 'maksamattomat_maksetut':
			$where .= "AND (lasku.tila = 'H' AND lasku.alatila = '') OR (lasku.tila = 'Y' AND lasku.alatila = '')";
			break;
	}

	return $where;
}

function matkalaskunro_where($request_params) {
	//TODO MATKALASKU RAJAUS annetaanko tunnuksia pilkulla eroteltuna vai HÄ?
	return "AND lasku.tunnus IN ({$request_params['matkalaskunro']})";
}

function generoi_tuote_join($request_params) {
	$tuote_join = "ON ( tuote.yhtio = lasku.yhtio and tuote.tuoteno=tilausrivi.tuoteno ";
	if(!empty($request_params['tuotetyypit'])) {
		$tuote_join .= tuotetyypit_join($request_params);
	}

	if(!empty($request_params['tuotteet_lista'])) {
		$tuote_join .= tuotteet_tuoteno_join($request_params);
	}

	if($request_params['kenelta_kustp'] == "tuotteilta") {
		if(!empty($request_params['mul_kustp'])) {
			$tuote_join .= " AND tuote.kustp IN (".implode(',', $request_params['mul_kustp']).")";
		}
	}

	$tuote_join .= " )";
	return $tuote_join;
}

function tuotetyypit_join($request_params) {
	$tuotetyypit = implode("','", $request_params['tuotetyypit']);
	return " AND tuote.tuotetyyppi IN ('{$tuotetyypit}')";
}

function tuotteet_tuoteno_join($request_params) {
	//TODO tähän pitää varmaan laittaa jotain validaatiota sun muuta???????? pilkulla eroteltunako tulee
	return " AND tuote.tuoteno IN ({$request_params['tuotteet_lista']})";
}

function generoi_kustannuspaikka_join($request_params) {
	$kustannuspaikka_join = "ON ( kustannuspaikka.yhtio = lasku.yhtio ";

	switch($request_params['kenelta_kustp']) {
		case 'toimittajilta':
			$kustannuspaikka_join .= "AND kustannuspaikka.tunnus = toimi.kustannuspaikka";
			break;
		case 'tuotteilta':
			$kustannuspaikka_join .= " AND kustannuspaikka.tunnus = tuote.kustp";
			break;
	}

	$kustannuspaikka_join .= " )";

	return $kustannuspaikka_join;
}

function generoi_toimi_join($request_params) {
	$toimi_join = "ON ( toimi.yhtio = lasku.yhtio AND toimi.tunnus = lasku.liitostunnus";
	if($request_params['kenelta_kustp'] == "toimittajilta") {
		if(!empty($request_params['mul_kustp'])) {
			$toimi_join .= " AND toimi.kustannuspaikka IN (".implode(',', $request_params['mul_kustp']).")";
		}
	}

	$toimi_join .= " )";

	return $toimi_join;
}

function generoi_select($request_params) {
	$select = "";

	//group: matkalaskuittain
	if($request_params['ruksit']['matkalaskuittain'] != '') {
		//laskunumero näytetään kun: Piilota laskunumero not checked ja groupataan laskun mukaan
		if(!empty($request_params['laskunro'])) {
			$select .= "lasku.laskunro, ";
		}
		//näytetään: kun tapahtuma päivä checked ja groupataan matkalaskuittain
		if(!empty($request_params['tapahtumapaiva'])) {
			$select .= "lasku.tapvm, ";
		}
		$select .= "lasku.summa, ";
	}

	//group: tuotteittain
	if($request_params['ruksit']['tuotteittain'] != '') {
		//tuotteiden nimityksen näytetään kun: nimitykset checked ja grouptaan tuotteittain
		if(!empty($request_params['nimitykset'])) {
			$select .= "tilausrivi.nimitys, ";
		}
		//matkalasku rivin muita tietoja, kuin kpl halutaan näyttää vain jos grouptaan tuotteittain
		$select .= "tilausrivi.tuoteno, tilausrivi.keratty, tilausrivi.toimitettu, tuote.tuotetyyppi, ";
	}

	//group: kaikki
	if(empty($request_params['piilota_kappaleet'])) {
		//jos on mikä tahansa grouppi niin tilausrivi.kpl pitää summata
		if($request_params['ruksit']['tuotteittain'] or $request_params['ruksit']['toimittajittain'] or $request_params['ruksit']['matkalaskuittain'] or $request_params['ruksit']['tuotetyypeittain'] or $request_params['ruksit']['kustp']) {
			$select .= "sum(tilausrivi.kpl) as kpl, sum(tilausrivi.erikoisale) as ilmaiset_lounaat, ";
		}
		else {
			$select .= "tilausrivi.kpl, tilausrivi.erikoisale as ilmaiset_lounaat, ";
		}
	}

	if(!empty($request_params['tilrivikomm'])) {
		$select .= "tilausrivi.kommentti, ";
	}

	if(!empty($request_params['maksutieto'])) {
		//TODO mikä on maksutieto?
		$select .= "";
	}

	$select .= "tilausrivi.hinta, tilausrivi.rivihinta, toimi.tunnus, toimi.nimi, lasku.tunnus";

	return $select;
}

function generoi_group_by($request_params) {
	//selectoidaan vain valitut grouppaukset mukaan
	$group_by = "";

	$mukaan_tulevat = array();
	foreach($request_params['ruksit'] as $index => $value) {
		if($value != '') {
			$mukaan_tulevat[$index] = $request_params['jarjestys'][$index];
		}
	}
	//tässä meillä on mukaan tulevat grouppaukset, nyt array pitää sortata niin, että pienin prioriteetti tulee ensimmäiseksi ja tyhjät pohjalle
	asort($mukaan_tulevat);
	//tällä saadaan tyhjät valuet arrayn pohjalle
	$mukaan_tulevat = array_diff($mukaan_tulevat, array('')) + array_intersect($mukaan_tulevat, array(''));
	/*php > $arr = array(0 => '1', 1 => '3', 2 => '2', 3 => '', 4 => '', 5 => '6'); asort($arr); $re = array_diff($arr, array('')) + array_intersect($arr, array('')); echo print_r($re);
		Array
		(
			[0] => 1
			[2] => 2
			[1] => 3
			[5] => 6
			[3] =>
			[4] =>
		)
	 */

	if(!empty($mukaan_tulevat)) {
		$group_by = "GROUP BY ";
		foreach($mukaan_tulevat as $index => $value) {
			switch($index) {
				case 'kustp':
					$group_by .= "kustannuspaikka.tunnus, ";
					break;
				case 'toimittajittain':
					$group_by .= "toimi.tunnus, ";
					break;
				case 'tuotteittain':
					$group_by .= "tilausrivi.tuoteno, ";
					break;
				case 'tuotetyypeittain':
					$group_by .= "tuote.tuotetyyppi, ";
					break;
				case 'matkalaskuittain':
					$group_by .= "lasku.tunnus, ";
					break;
			}
		}
		//poistetaan viimeiset 2 merkkiä ", " group by:n lopusta
		$group_by = substr($group_by, 0, -2);
	}

	return $group_by;
}

function echo_matkalaskuraportti_form($request_params) {
	global $kukarow;

	if ($request_params['ruksit']['tuotetyypeittain'] != '')   	$ruk_tuotetyypeittain_chk	= "CHECKED";
	if ($request_params['ruksit']['tuotteittain'] != '')   		$ruk_tuotteittain_chk   	= "CHECKED";
	if ($request_params['ruksit']['toimittajittain'] != '')   	$ruk_toimittajittain_chk   	= "CHECKED";
	if ($request_params['ruksit']['matkalaskuittain'] != '')   	$ruk_matkalaskuittain_chk	= "CHECKED";
	if ($request_params['piilota_kappaleet'] != '')				$piilota_kappaleet_chk		= "CHECKED";
	if ($request_params['nimitykset'] != '')					$nimchk						= "CHECKED";
	if ($request_params['tilrivikomm'] != '')					$tilrivikommchk				= "CHECKED";
	if ($request_params['laskunro'] != '')						$laskunrochk   				= "CHECKED";
	if ($request_params['maksutieto'] != '')					$maksutietochk				= "CHECKED";
	if ($request_params['tapahtumapaiva'] != '')				$tapahtumapaivachk			= "CHECKED";
	$jarjestys['kustp'] = $request_params['jarjestys']['kustp'];
	$ruksit["kustp"] = $request_params['ruksit']['kustp'];
	//asetetaan toimittajilta default valueksi
	$kenelta_kustp = ($request_params['kenelta_kustp'] == ''? 'toimittajilta' : $request_params['kenelta_kustp']);
	
	$ajotavat = array(
		"keskeneraiset" => t("Keskeneräiset"),
		"maksamattomat" => t("Maksamattomat"),
		"maksetut" => t("Maksetut"),
		"keskeneraiset_maksamattomat" => t("Keskeneräiset ja maksamattomat"),
		"maksamattomat_maksetut" => t("Maksamattomat ja maksetut"),
	);
	$tuotetyypit = array(
		"A" => t("Päiväraha"),
		"B" => t("Muu kulu"),
	);

	echo "<form name='matkalaskuraportti' method='POST'>";
	echo "<input type='hidden' name='tee' value='aja_raportti' />";
	echo "<table id='ajotavat'>";

	echo "<tr>";
	echo "<th>".t("Valitse Ajotapa")."</th>";
	echo "<td>";
	echo "<select name='ajotapa'>";
	$sel = "";
	foreach($ajotavat as $ajotapa_key => $ajotapa_value) {
		if($ajotapa_key == $request_params['ajotapa']) {
			$sel = "SELECTED";
		}
		echo "<option value='{$ajotapa_key}' $sel>{$ajotapa_value}</option>";
		$sel = "";
	}
	echo "</select>";
	echo "</td>";
	echo "</tr>";

	echo "</table>";

	echo "<br/>";

	echo "<table id='tuotetyypit'>";
	echo "<tr>";
	echo "<th>".t("Valitse tuotetyypit")."</th>";
	echo "</tr>";
	echo "<tr>";
	echo "<td>";
	echo "<select id='tuotetyypit' multiple='multiple' name='tuotetyypit[]'>";
	$sel = "";
	foreach($tuotetyypit as $tuotetyyppi_key => $tuotetyyppi_value) {
		if(is_array($request_params['tuotetyypit']) and in_array($tuotetyyppi_key, $request_params['tuotetyypit'])) {
			$sel = "SELECTED";
		}
		echo "<option value='$tuotetyyppi_key' $sel>$tuotetyyppi_value</option>";
	}
	echo "</select>";
	echo "</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<th>";
	echo t("Prio").": <input type='text' name='jarjestys[tuotetyypeittain]' size='2' value='{$request_params['jarjestys']['tuotetyypeittain']}'> ";
	echo t("Tuotetyypeittäin")." <input type='checkbox' name='ruksit[tuotetyypeittain]' value='tuotetyypeittain' $ruk_tuotetyypeittain_chk>";
	echo "</th>";
	echo "</tr>";
	echo "</table>";

	$noautosubmit = TRUE;
	$monivalintalaatikot = array("<br>KUSTP");
	$monivalintalaatikot_normaali = array();

	require ("../tilauskasittely/monivalintalaatikot.inc");

	echo "<br/><br/>";

	echo "<table id='lisarajaus'>";
	echo "<tr>
			<th>".t("Lisärajaus")."</th>
			<th>".t("Prio")."</th>
			<th> x</th>
			<th>".t("Rajaus")."</th>
		</tr>";
	echo "<tr></tr>";
	echo "<tr>
			<th>".t("Listaa tuotteittain")."</th>
			<td><input type='text' name='jarjestys[tuotteittain]' size='2' value='{$request_params['jarjestys']['tuotteittain']}'></td>
			<td><input type='checkbox' name='ruksit[tuotteittain]' value='tuotteittain' {$ruk_tuotteittain_chk}></td>
			<td><input type='text' name='tuotenro' value='{$request_params['tuotenro']}'></td>
		</tr>";
	echo "<tr>
			<th>".t("Listaa toimittajittain")."</th>
			<td><input type='text' name='jarjestys[toimittajittain]' size='2' value='{$request_params['jarjestys']['toimittajittain']}'></td>
			<td><input type='checkbox' name='ruksit[toimittajittain]' value='toimittajittain' {$ruk_toimittajittain_chk}></td>
			<td><input type='text' name='toimittajanro' value='{$request_params['toimittajanro']}'></td>
		</tr>";
	echo "<tr>
			<th>".t("Listaa matkalaskuittain")."</th>
			<td><input type='text' name='jarjestys[matkalaskuittain]' size='2' value='{$request_params['jarjestys']['matkalaskuittain']}'></td>
			<td><input type='checkbox' name='ruksit[matkalaskuittain]' value='matkalaskuittain' {$ruk_matkalaskuittain_chk}></td>
			<td><input type='text' name='matkalaskunro' value='{$request_params['matkalaskunro']}'></td>
		</tr>";
	echo "</table>";

	echo "<br/><br/>";

	echo "<table id='tuotelista'>";
	echo "<tr>
			<th valign='top'>".t("Tuotelista")."<br>(".t("Rajaa näillä tuotteilla").")</th>
			<td colspan='3'><textarea name='tuotteet_lista' rows='5' cols='35'>{$request_params['tuotteet_lista']}</textarea></td>
		</tr>";
	echo "</table>";

	echo "<br/><br/>";

	echo "<table id='naytto'>";
	echo "<tr>
			<th>".t("Piilota kappaleet")."</th>
			<td colspan='3'><input type='checkbox' name='piilota_kappaleet' {$piilota_kappaleet_chk}></td>
		</tr>";
	echo "<tr>
			<th>".t("Näytä tuotteiden nimitykset")."</th>
			<td colspan='3'><input type='checkbox' name='nimitykset' {$nimchk}></td>
			<td class='back'>".t("(Toimii vain jos listaat tuotteittain)")."</td>
		</tr>";
	echo "<tr>
			<th>".t("Näytä tilausrivin kommentti")."</th>
			<td colspan='3'><input type='checkbox' name='tilrivikomm' {$tilrivikommchk}></td>
			<td class='back'>".t("(Listataan kaikki rivit)")."</td>
		</tr>";
	echo "<tr>
			<th>".t("Näytä myös laskunumero")."</th>
			<td colspan='3'><input type='checkbox' name='laskunro' {$laskunrochk}></td>
			<td class='back'>".t("(Toimii vain jos listaat matkalaskuittain)")."</td>
		</tr>";
	echo "<tr>
			<th>".t("Näytä myös maksuetieto")."</th>
			<td colspan='3'><input type='checkbox' name='maksutieto' {$maksutietochk}></td>
			<td class='back'>".t("(Toimii vain jos listaat matkalaskuittain)")."</td>
		</tr>";
	echo "<tr>
			<th>".t("Näytä myös tapahtumapäivä")."</th>
			<td colspan='3'><input type='checkbox' name='tapahtumapaiva' {$tapahtumapaivachk}></td>
			<td class='back'>".t("(Toimii vain jos listaat matkalaskuittain)")."</td>
		</tr>";
	echo "</table>";

	echo "<br/>";

	echo "<table>";
	echo "<tr>
			<th>".t("Syötä alkupäivämäärä (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppa' value='{$request_params['ppa']}' size='3'></td>
			<td><input type='text' name='kka' value='{$request_params['kka']}' size='3'></td>
			<td><input type='text' name='vva' value='{$request_params['vva']}' size='5'></td>
			</tr>
			<br/>
			<tr><th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppl' value='{$request_params['ppl']}' size='3'></td>
			<td><input type='text' name='kkl' value='{$request_params['kkl']}' size='3'></td>
			<td><input type='text' name='vvl' value='{$request_params['vvl']}' size='5'></td>
		</tr>
		<br/>";
	echo "</table>";
	echo "<br/>";

	echo nayta_kyselyt("myyntiseuranta");

	echo "<br/>";
	echo "<input type='submit' name='aja_raportti' value='".t("Aja raportti")."'>";
	echo "</form>";

	echo "<br/><br/>";
}

function generoi_excel_tiedosto($rivit) {
	$xls = new pupeExcel();

	xls_headerit($xls);
}

function xls_headerit(pupeExcel $xls) {

}

?>
