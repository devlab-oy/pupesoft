<?php

require ("../inc/parametrit.inc");

if (isset($livesearch_tee) and $livesearch_tee == "ASIAKASHAKU") {
	livesearch_asiakashaku();
	exit;
}

if (isset($livesearch_tee) and $livesearch_tee == "TUOTEHAKU") {
	livesearch_tuotehaku();
	exit;
}

if (isset($livesearch_tee) and $livesearch_tee == "TUOTEKATEGORIAHAKU") {
	livesearch_tuotekategoriahaku();
	exit;
}

if ($ajax_request) {
	//javascript lähettää kaiken stringinä
	if ($palkinto_rivi == 'true') {
		$return = hae_liveseach_kentta($rivi_kohde, 'palkinto', $ehto_rivi_id);
	}
	else {
		if (isset($aliehto_rivi_id)) {
			$return = hae_liveseach_kentta($rivi_kohde, 'aliehto', $ehto_rivi_id, $aliehto_rivi_id);
		}
		else {
			$return = hae_liveseach_kentta($rivi_kohde, 'ehto', $ehto_rivi_id);
		}
	}

	if (!empty($return)) {
		echo $return;
	}
	exit;
}

enable_ajax();

echo "<font class='head'>".t("Kampanjat")."</font><hr>";
?>
<style>
	#ehto_rivi_template {
		display: none;
	}
	#aliehto_rivi_template {
		display: none;
	}
	#palkinto_table_template {
		display: none;
	}
	.ehto_arvo {
		width: 140px;
	}
</style>
<script src="../js/kampanja/kampanja.js"></script>
<?php

$request = array(
	'kampanja_tunnus'	 => $kampanja_tunnus,
	'tee'				 => $tee,
	'kampanja_nimi'		 => $kampanja_nimi,
	'kampanja_ehdot'	 => $kampanja_ehdot,
	'palkinto_rivit'	 => $palkinto_rivit,
);

if ($request['tee'] == 'uusi_kampanja') {
	//Purkka: Jos requestista tulee kampanjan nimi niin voidaan olettaa että halutaan luoda uusi kampanja
	//TODO make some sense into this
	if (!empty($request['kampanja_nimi'])) {
		$onko_kampanja_ok = luo_uusi_kampanja($request);

		//uudelleen piirretään formi
		if (!$onko_kampanja_ok) {
			$kampanja = array();
			$kampanja['kampanja']['nimi'] = $request['kampanja_nimi'];
			$kampanja['kampanja']['kampanja_ehdot'] = $request['kampanja_ehdot'];
			$kampanja['kampanja']['kampanja_palkinnot'] = $request['palkinto_rivit'];
			echo_kayttoliittyma($kampanja);
		}
		else {
			$request['tee'] = '';
		}
	}
	else {
		if (!empty($request['kampanja_tunnus'])) {
			$request['kampanja'] = hae_kampanja($request['kampanja_tunnus']);
		}
		echo_kayttoliittyma($request);
	}
}

if ($request['tee'] == 'muokkaa_kampanjaa') {
	if ($request['kampanja_tunnus']) {
		$onko_kampanja_ok = muokkaa_kampanjaa($request);

		//uudelleen piirretään formi
		if (!$onko_kampanja_ok) {
			$kampanja = array();
			$kampanja['kampanja']['nimi'] = $request['kampanja_nimi'];
			$kampanja['kampanja']['tunnus'] = $request['kampanja_tunnus'];
			$kampanja['kampanja']['kampanja_ehdot'] = $request['kampanja_ehdot'];
			$kampanja['kampanja']['kampanja_palkinnot'] = $request['palkinto_rivit'];
			echo_kayttoliittyma($kampanja);
		}
		else {
			$request['tee'] = '';
		}
	}
}

if ($request['tee'] == '') {
	nayta_kampanjat();
}

function luo_uusi_kampanja($request) {
	global $kukarow, $yhtiorow;

	$kampanja_tunnus = luo_kampanja_otsikko($request['kampanja_nimi']);

	foreach ($request['kampanja_ehdot'] as $kampanja_ehto) {
		$onko_ehto_ok = validoi_kampanja_ehto_tai_aliehto($kampanja_ehto);
		if (!$onko_ehto_ok) {
			poista_kampanja_otsikko($kampanja_tunnus);
			poista_kampanja_ehdot($kampanja_tunnus);
			return false;
		}
		$kampanja_ehto_tunnus = luo_kampanja_ehto($kampanja_ehto, $kampanja_tunnus);

		if (empty($kampanja_ehto['aliehto_rivit'])) {
			continue;
		}

		foreach ($kampanja_ehto['aliehto_rivit'] as $kampanja_aliehto) {
			$onko_aliehto_ok = validoi_kampanja_ehto_tai_aliehto($kampanja_aliehto);
			if (!$onko_aliehto_ok) {
				poista_kampanja_otsikko($kampanja_tunnus);
				poista_kampanja_ehdot($kampanja_tunnus);
				return false;
			}

			luo_kampanja_aliehto($kampanja_aliehto, $kampanja_ehto_tunnus);
		}
	}

	foreach ($request['palkinto_rivit'] as $palkinto_rivi) {
		$onko_palkinto_ok = validoi_palkinto_rivi($palkinto_rivi);
		if (!$onko_palkinto_ok) {
			poista_kampanja_otsikko($kampanja_tunnus);
			poista_kampanja_ehdot($kampanja_tunnus);
			poista_kampanja_palkinnot($kampanja_tunnus);
			return false;
		}
		luo_palkinto_rivi($palkinto_rivi, $kampanja_tunnus);
	}

	return true;
}

function validoi_kampanja_ehto_tai_aliehto($ehto) {
	global $kukarow, $yhtiorow;

	$echo = "";
	switch ($ehto['kohde']) {
		case 'asiakas':
			$query = "	SELECT *
						FROM asiakas
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tunnus = '{$ehto['arvo']}'";
			$echo = t('Asiakasta ei löydy');
			break;
		case 'asiakas_ytunnus':
			$query = "	SELECT *
						FROM asiakas
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND ytunnus = '{$ehto['arvo']}'";
			$echo = t('Asiakasta ei löydy');
			break;
		case 'asiakaskategoria':
			$query = "	SELECT *
						FROM dynaaminen_puu
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND laji = 'Asiakas'
						AND tunnus = '{$ehto['arvo']}'";
			$echo = t('Asiakaskategoriaa ei löydy');
			break;
		case 'tuote':
			$query = "	SELECT *
						FROM tuote
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tuoteno = '{$ehto['arvo']}'";
			$echo = t('Tuotetta ei löydy');
			break;
		case 'tuotekategoria':
			$query = "	SELECT *
						FROM dynaaminen_puu
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND laji = 'Tuote'
						AND tunnus = '{$ehto['arvo']}'";
			$echo = t('Tuotekategoriaa ei löydy');
			break;
		case 'tuoteosasto':
			$query = "	SELECT *
						FROM avainsana
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND laji = 'OSASTO'
						AND selite = '{$ehto['arvo']}'";
			$echo = t('Tuoteosastoa ei löydy');
			break;
		case 'tuoteryhma':
			$query = "	SELECT *
						FROM avainsana
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND laji = 'TRY'
						AND selite = '{$ehto['arvo']}'";
			$echo = t('Tuoteryhmää ei löydy');
			break;
		case 'kappaleet':
			if (!is_numeric($ehto['arvo'])) {
				return false;
			}
			break;
		case 'arvo':
			if (!is_numeric($ehto['arvo'])) {
				return false;
			}
			break;
		default:
			echo "Rikki meni";
			return false;
			break;
	}

	if (!empty($query)) {
		$result = pupe_query($query);

		if (mysql_num_rows($result) == 0) {
			echo "<font class='error'>$echo</font>";
			return false;
		}
	}

	return true;
}

function validoi_palkinto_rivi($palkinto_rivi) {
	global $kukarow, $yhtiorow;

	$query = "	SELECT *
				FROM tuote
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tuoteno = '{$palkinto_rivi['tuoteno']}'";
	$result = pupe_query($query);

	if (mysql_num_rows($result) == 0) {
		echo t('Palkintorivin tuotetta ei löydy');
		return false;
	}

	if (!is_numeric($palkinto_rivi['kpl'])) {
		echo t('Palkintorivin kpl ei ole numero');
		return false;
	}

	return true;
}

function luo_kampanja_otsikko($kampanja_nimi) {
	global $kukarow, $yhtiorow;

	$query = "	INSERT INTO kampanjat
				SET yhtio = '{$kukarow['yhtio']}',
				nimi = '{$kampanja_nimi}',
				laatija = '{$kukarow['kuka']}',
				luontiaika = CURRENT_DATE";
	pupe_query($query);

	return mysql_insert_id();
}

function luo_kampanja_ehto($kampanja_ehto, $kampanja_tunnus) {
	global $kukarow, $yhtiorow;

	$query = "	INSERT INTO kampanja_ehdot
				SET yhtio = '{$kukarow['yhtio']}',
				kampanja = '{$kampanja_tunnus}',
				isatunnus = 0,
				kohde = '{$kampanja_ehto['kohde']}',
				rajoitin = '{$kampanja_ehto['rajoitin']}',
				arvo = '{$kampanja_ehto['arvo']}',
				laatija = '{$kukarow['kuka']}',
				luontiaika = CURRENT_DATE";
	pupe_query($query);

	return mysql_insert_id();
}

function luo_kampanja_aliehto($kampanja_aliehto, $kampanja_ehto_tunnus) {
	global $kukarow, $yhtiorow;

	$query = "	INSERT INTO kampanja_ehdot
				SET yhtio = '{$kukarow['yhtio']}',
				kampanja = 0,
				isatunnus = {$kampanja_ehto_tunnus},
				kohde = '{$kampanja_aliehto['kohde']}',
				rajoitin = '{$kampanja_aliehto['rajoitin']}',
				arvo = '{$kampanja_aliehto['arvo']}',
				laatija = '{$kukarow['kuka']}',
				luontiaika = CURRENT_DATE";
	pupe_query($query);

	return mysql_insert_id();
}

function luo_palkinto_rivi($palkinto_rivi, $kampanja_tunnus) {
	global $kukarow, $yhtiorow;

	$query = "	INSERT INTO kampanja_palkinnot
				SET yhtio = '{$kukarow['yhtio']}',
				kampanja = '{$kampanja_tunnus}',
				tuoteno = '{$palkinto_rivi['tuoteno']}',
				kpl = '{$palkinto_rivi['kpl']}',
				laatija = '{$kukarow['kuka']}',
				luontiaika = CURRENT_DATE";
	pupe_query($query);
}

function muokkaa_kampanjaa($request) {
	global $kukarow, $yhtirow;

	foreach ($request['kampanja_ehdot'] as $kampanja_ehto) {
		$onko_ehto_ok = validoi_kampanja_ehto_tai_aliehto($kampanja_ehto);
		if (!$onko_ehto_ok) {
			return false;
		}

		if (empty($kampanja_ehto['aliehto_rivit'])) {
			continue;
		}

		foreach ($kampanja_ehto['aliehto_rivit'] as $kampanja_aliehto) {
			$onko_aliehto_ok = validoi_kampanja_ehto_tai_aliehto($kampanja_aliehto);
			if (!$onko_aliehto_ok) {
				return false;
			}
		}
	}

	$query = "	UPDATE kampanjat
				SET nimi = '{$request['kampanja_nimi']}',
				muuttaja = '{$kukarow['kuka']}',
				muutospvm = CURRENT_DATE
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tunnus = '{$request['kampanja_tunnus']}'";
	pupe_query($query);

	poista_kampanja_ehdot($request['kampanja_tunnus']);
	poista_kampanja_palkinnot($request['kampanja_tunnus']);

	foreach ($request['kampanja_ehdot'] as $kampanja_ehto) {
		$kampanja_ehto_tunnus = luo_kampanja_ehto($kampanja_ehto, $request['kampanja_tunnus']);

		if (empty($kampanja_ehto['aliehto_rivit']))
			continue;

		foreach ($kampanja_ehto['aliehto_rivit'] as $kampanja_aliehto) {
			luo_kampanja_aliehto($kampanja_aliehto, $kampanja_ehto_tunnus);
		}
	}

	foreach ($request['palkinto_rivit'] as $palkinto_rivi) {
		luo_palkinto_rivi($palkinto_rivi, $request['kampanja_tunnus']);
	}

	return true;
}

function poista_kampanja_otsikko($kampanja_tunnus) {
	global $kukarow, $yhtiorow;

	$query = "	DELETE FROM kampanjat
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tunnus = '{$kampanja_tunnus}'";

	pupe_query($query);
}

function poista_kampanja_ehdot($kampanja_tunnus) {
	global $kukarow, $yhtiorow;

	//Poistetaan aliehdot
	$query = "	SELECT tunnus
				FROM kampanja_ehdot
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND kampanja = '{$kampanja_tunnus}'";
	$result = pupe_query($query);
	while ($kampanja = mysql_fetch_assoc($result)) {
		$query = "	DELETE FROM kampanja_ehdot
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND isatunnus = '{$kampanja['tunnus']}'";
		pupe_query($query);
	}

	//Poistetaan ehdot
	$query = "	DELETE FROM kampanja_ehdot
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND kampanja = '{$kampanja_tunnus}'";
	pupe_query($query);
}

function poista_kampanja_palkinnot($kampanja_tunnus) {
	global $kukarow, $yhtiorow;

	$query = "	DELETE FROM kampanja_palkinnot
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND kampanja = '{$kampanja_tunnus}'";
	pupe_query($query);
}

function echo_kayttoliittyma($request = array()) {
	//Templatet on javascriptiä varten, että voidaan kutsua .clone()
	echo_ehto_rivi_template();

	echo_ehto_alirivi_template();

	echo_palkinto_rivi_template();

	echo "<input type='hidden' id='ehto_arvo_tyhja_message' value='".t("Ehdon arvo on tyhjä")."' />";
	echo "<input type='hidden' id='aliehto_arvo_tyhja_message' value='".t("Aliehdon arvo on tyhjä")."' />";
	echo "<input type='hidden' id='ehto_minimi_message' value='".t("Ehtoja pitää olla vähintään yksi")."' />";
	echo "<input type='hidden' id='nimi_tyhja_message' value='".t("Nimi on tyhjä")."' />";

	echo "<form name='kampanja_form' method='POST' action='' class='multisubmit'>";

	if (!empty($request['kampanja']['tunnus'])) {
		echo "<input type='hidden' name='tee' value='muokkaa_kampanjaa' />";
	}
	else {
		echo "<input type='hidden' name='tee' value='uusi_kampanja' />";
	}

	echo "<font class='message'>", t("Nimi"), "</font>";
	echo "<br/>";
	echo "<br/>";

	echo "<div id='kampanja_header'>";

	echo "<input type='hidden' size=50 name='kampanja_tunnus' value='{$request['kampanja']['tunnus']}'/>";
	echo "<input type='text' id='kampanja_nimi' size=50 name='kampanja_nimi' value='{$request['kampanja']['nimi']}'/>";
	echo "</div>";

	echo "<hr/>";
	echo "<font class='message'>", t("Ehdot"), "</font>";
	echo "<br/>";
	echo "<br/>";

	echo "<table id='ehdot'>";
	echo "<thead>";
	echo "<tr>";
	echo "<th>".t("Kohde")."</th>";
	echo "<th>".t("Rajoitin")."</th>";
	echo "<th>".t("Arvo")."</th>";
	echo "<th>".t("Lisää aliehto")."</th>";
	echo "<th>".t("Poista ehto/aliehto")."</th>";
	echo "</tr>";
	echo "</thead>";
	if (!empty($request['kampanja']['kampanja_ehdot'])) {
		foreach ($request['kampanja']['kampanja_ehdot'] as $index => $kampanja_ehto) {
			echo_kampanja_ehto($index, $kampanja_ehto);
		}
	}
	echo "</table>";

	echo "<br/>";
	echo "<button type='button' id='uusi_ehto'>".t("Uusi ehto")."</button>";

	echo "<hr/>";
	echo "<font class='message'>", t("Palkinnot"), "</font>";
	echo "<br/>";
	echo "<br/>";

	echo "<div id='palkinnot'>";

	echo "<table id='palkinto_table'>";
	echo "<tr>";
	echo "<th>".t("Tuoteno")."</th>";
	echo "<th>".t("Kpl")."</th>";
	echo "<th></th>";
	echo "</tr>";
	if (!empty($request['kampanja']['kampanja_palkinnot'])) {
		foreach ($request['kampanja']['kampanja_palkinnot'] as $palkinto_index => $palkinto) {
			echo_kampanja_palkinto($palkinto_index, $palkinto);
		}
	}
	echo "</table>";
	echo "</div>";

	echo "<br/>";
	echo "<button type='button' id='uusi_palkinto'>".t("Uusi palkinto")."</button>";
	echo "<hr>";

	echo "<br/>";
	echo "<input id='submit_button' type='submit' value='".t("Tallenna kampanja")."' />";
	echo "</form>";
}

function echo_kampanja_ehto($index, $kampanja_ehto) {
	$ehdot = hae_ehdon_kohteet();
	$rajoittimet = hae_ehdon_rajoittimet();
	$arvo_input = hae_liveseach_kentta($kampanja_ehto['kohde'], 'ehto', $index, 0, $kampanja_ehto['arvo']);

	echo "<tr class='ehto_rivi'>";

	echo "<td>";
	echo "<input type='hidden' class='ehto_id' value='{$index}'/>";
	echo "<select class='ehto_kohde' name='kampanja_ehdot[{$index}][kohde]'>";
	foreach ($ehdot as $ehto) {
		$sel = "";
		if ($ehto['value'] == $kampanja_ehto['kohde']) {
			$sel = "SELECTED";
		}
		echo "<option value='{$ehto['value']}' {$sel}>{$ehto['text']}</option>";
	}
	echo "</select>";
	echo "</td>";

	echo "<td>";
	echo "<select class='ehto_rajoitin' name='kampanja_ehdot[{$index}][rajoitin]'>";
	foreach ($rajoittimet as $rajoitin) {
		$sel = "";
		if ($rajoitin['value'] == $kampanja_ehto['rajoitin']) {
			$sel = "SELECTED";
		}
		echo "<option value='{$rajoitin['value']}' {$sel}>{$rajoitin['text']}</option>";
	}
	echo "</select>";
	echo "</td>";

	echo "<td>";
	echo $arvo_input;
	echo "</td>";

	echo "<td>";
	if ($kampanja_ehto['kohde'] === 'tuote' || $kampanja_ehto['kohde'] === 'tuotekategoria' || $kampanja_ehto['kohde'] === 'tuoteosasto' || $kampanja_ehto['kohde'] === 'tuoteryhma') {
		echo "<button type='button' class='uusi_aliehto'>".t("Uusi aliehto")."</button>";
	}
	echo "</td>";

	echo "<td>";
	echo "<button type='button' class='poista_ehto'>".t("Poista ehto")."</button>";
	echo "</td>";

	echo "</tr>";

	if (!empty($kampanja_ehto['aliehdot'])) {
		foreach ($kampanja_ehto['aliehdot'] as $aliehto_index => $aliehto) {
			echo_kampanja_aliehto($index, $aliehto_index, $aliehto);
		}
	}

	echo "</tr>";
}

function echo_kampanja_aliehto($ehto_index, $aliehto_index, $aliehto) {
	$ehdot = hae_ehdon_kohteet();
	$rajoittimet = hae_ehdon_rajoittimet();
	$arvo_input = hae_liveseach_kentta($aliehto['kohde'], 'aliehto', $ehto_index, $aliehto_index, $aliehto['arvo']);

	echo "<tr class='aliehto_rivi'>";

	echo "<td>";
	echo "<input type='hidden' class='aliehto_id' value='{$aliehto_index}'/>";
	echo " &raquo; ";
	echo "<select class='aliehto_kohde' name='kampanja_ehdot[{$ehto_index}][aliehto_rivit][{$aliehto_index}][kohde]'>";
	foreach ($ehdot as $ehto) {
		if ($ehto['value'] === 'kappaleet' || $ehto['value'] === 'arvo') {
			$sel = "";
			if ($ehto['value'] == $aliehto['kohde']) {
				$sel = "SELECTED";
			}
			echo "<option value='{$ehto['value']}' {$sel}>{$ehto['text']}</option>";
		}
	}
	echo "</select>";
	echo "</td>";

	echo "<td>";
	echo "<select class='aliehto_rajoitin' name='kampanja_ehdot[{$ehto_index}][aliehto_rivit][{$aliehto_index}][rajoitin]'>";
	foreach ($rajoittimet as $rajoitin) {
		$sel = "";
		if ($rajoitin['value'] == $aliehto['rajoitin']) {
			$sel = "SELECTED";
		}
		echo "<option value='{$rajoitin['value']}' {$sel}>{$rajoitin['text']}</option>";
	}
	echo "</select>";
	echo "</td>";

	echo "<td>";
	echo $arvo_input;
	echo "</td>";

	echo "<td>";
	echo "</td>";

	echo "<td>";
	echo "<button type='button' class='poista_aliehto'>".t("Poista aliehto")."</button>";
	echo "</td>";

	echo"</tr>";

	echo "</div>";
}

function echo_ehto_rivi_template() {
	$ehdot = hae_ehdon_kohteet();
	$rajoittimet = hae_ehdon_rajoittimet();

	echo "<table>";
	echo "<tr id='ehto_rivi_template'>";

	echo "<td>";
	echo "<input type='hidden' class='ehto_id_template' />";
	echo "<select class='ehto_kohde'>";
	foreach ($ehdot as $ehto) {
		echo "<option value='{$ehto['value']}'>{$ehto['text']}</option>";
	}
	echo "</select>";
	echo "</td>";

	echo "<td>";
	echo "<select class='ehto_rajoitin'>";
	foreach ($rajoittimet as $rajoitin) {
		echo "<option value='{$rajoitin['value']}'>{$rajoitin['text']}</option>";
	}
	echo "</select>";
	echo "</td>";

	echo "<td>";
	echo "<input type='text' class='ehto_arvo' />";
	echo "</td>";

	echo "<td>";
	echo "<button type='button' class='uusi_aliehto'>".t("Uusi aliehto")."</button>";
	echo "</td>";

	echo "<td>";
	echo "<button type='button' class='poista_ehto'>".t("Poista ehto")."</button>";
	echo "</td>";

	echo "</tr>";

	echo "</table>";
}

function echo_ehto_alirivi_template() {
	$ehdot = hae_ehdon_kohteet();
	$rajoittimet = hae_ehdon_rajoittimet();

	echo "<table>";
	echo "<tr id='aliehto_rivi_template'>";

	echo "<td>";
	echo "<input type='hidden' class='aliehto_id_template' />";
	echo " &raquo; ";
	echo "<select class='aliehto_kohde'>";
	foreach ($ehdot as $ehto) {
		echo "<option value='{$ehto['value']}'>{$ehto['text']}</option>";
	}
	echo "</select>";
	echo "</td>";

	echo "<td>";
	echo "<select class='aliehto_rajoitin'>";
	foreach ($rajoittimet as $rajoitin) {
		echo "<option value='{$rajoitin['value']}'>{$rajoitin['text']}</option>";
	}
	echo "</select>";
	echo "</td>";

	echo "<td>";
	echo "<input type='text' class='aliehto_arvo' />";
	echo "</td>";

	echo "<td>";
	echo "</td>";

	echo "<td>";
	echo "<button type='button' class='poista_aliehto'>".t("Poista aliehto")."</button>";
	echo "</td>";

	echo "</tr>";
	echo "</table>";
}

function echo_kampanja_palkinto($palkinto_index, $palkinto) {
	global $kukarow, $yhtiorow;

	$tuoteno_input = hae_liveseach_kentta('tuote', 'palkinto', $palkinto_index, 0, $palkinto['tuoteno']);

	echo "<tr id='palkinto_rivi'>";
	echo "<td>";
	echo "<input type='hidden' class='palkinto_rivi_id' value='{$palkinto_index}'/>";
	echo $tuoteno_input;
	echo "</td>";

	echo "<td>";
	echo "<input type='text' class='palkinto_rivi_kpl' value='{$palkinto['kpl']}' name='palkinto_rivit[{$palkinto_index}][kpl]'/>";
	echo "</td>";

	echo "<td>";
	echo "<button type='button' class='poista_palkinto'>".t("Poista palkinto")."</button>";
	echo "</td>";
	echo "</tr>";
}

function echo_palkinto_rivi_template() {
	echo "<table id='palkinto_table_template'>";
	echo "<tr id='palkinto_rivi_template'>";
	echo "<td>";
	echo "<input type='hidden' class='palkinto_rivi_id' />";
	echo "<input type='text' class='palkinto_rivi_nimi' />";
	echo "</td>";

	echo "<td>";
	echo "<input type='text' class='palkinto_rivi_kpl' />";
	echo "</td>";

	echo "<td>";
	echo "<button type='button' class='poista_palkinto'>".t("Poista palkinto")."</button>";
	echo "</td>";
	echo "</tr>";

	echo "</table>";
}

function hae_liveseach_kentta($kohde, $tyyppi, $ehto_index, $aliehto_index = 0, $value = '') {
	if ($tyyppi == 'ehto') {
		if ($kohde == 'asiakas') {
			$return = livesearch_kentta("eisaaollaoikeaforminnimi", "ASIAKASHAKU", "kampanja_ehdot[{$ehto_index}][arvo]", 140, $value, '', '', 'ehto_arvo', 'ei_break_all');
		}
		else if ($kohde == 'tuote') {
			$return = livesearch_kentta("eisaaollaoikeaforminnimi", "TUOTEHAKU", "kampanja_ehdot[{$ehto_index}][arvo]", 140, $value, '', '', 'ehto_arvo', 'ei_break_all');
		}
		else if ($kohde == 'tuotekategoria') {
			$return = livesearch_kentta("eisaaollaoikeaforminnimi", "TUOTEKATEGORIAHAKU", "kampanja_ehdot[{$ehto_index}][arvo]", 140, $value, '', '', 'ehto_arvo', 'ei_break_all');
		}
		else {
			$return = "<input type='text' class='ehto_arvo' name='kampanja_ehdot[{$ehto_index}][arvo]' value='{$value}' />";
		}
	}
	else if ($tyyppi == 'aliehto') {
		if ($kohde == 'asiakas') {
			$return = livesearch_kentta("eisaaollaoikeaforminnimi", "ASIAKASHAKU", "kampanja_ehdot[{$ehto_index}][aliehto_rivit][{$aliehto_index}][arvo]", 140, $value, '', '', 'aliehto_arvo', 'ei_break_all');
		}
		else if ($kohde == 'tuote') {
			$return = livesearch_kentta("eisaaollaoikeaforminnimi", "TUOTEHAKU", "kampanja_ehdot[{$ehto_index}][aliehto_rivit][{$aliehto_index}][arvo]", 140, $value, '', '', 'aliehto_arvo', 'ei_break_all');
		}
		else if ($kohde == 'tuotekategoria') {
			$return = livesearch_kentta("eisaaollaoikeaforminnimi", "TUOTEKATEGORIAHAKU", "kampanja_ehdot[{$ehto_index}][aliehto_rivit][{$aliehto_index}][arvo]", 140, $value, '', '', 'ehto_arvo', 'ei_break_all');
		}
		else {
			$return = "<input type='text' class='aliehto_arvo' name='kampanja_ehdot[{$ehto_index}][aliehto_rivit][{$aliehto_index}][arvo]' value='{$value}' />";
		}
	}
	else {
		//palkinto rivit
		$return = livesearch_kentta("eisaaollaoikeaforminnimi", "TUOTEHAKU", "palkinto_rivit[{$ehto_index}][tuoteno]", 140, $value, '', '', 'palkinto_rivi_nimi', 'ei_break_all');
	}

	return $return;
}

function hae_ehdon_kohteet() {
	return array(
		array(
			'text'	 => t("Asiakas"),
			'value'	 => 'asiakas'
		),
		array(
			'text'	 => t("Asiakas ytunnus"),
			'value'	 => 'asiakas_ytunnus'
		),
		array(
			'text'	 => t("Asiakaskategoria"),
			'value'	 => 'asiakaskategoria'
		),
		array(
			'text'	 => t("Tuote"),
			'value'	 => 'tuote'
		),
		array(
			'text'	 => t("Tuotekategoria"),
			'value'	 => 'tuotekategoria'
		),
		array(
			'text'	 => t("Tuoteosasto"),
			'value'	 => 'tuoteosasto'
		),
		array(
			'text'	 => t("Tuoteryhmä"),
			'value'	 => 'tuoteryhma'
		),
		array(
			'text'	 => t("Kappaleet"),
			'value'	 => 'kappaleet'
		),
		array(
			'text'	 => t("Arvo"),
			'value'	 => 'arvo'
		),
	);
}

function hae_ehdon_rajoittimet() {
	return array(
		0	 => array(
			'text'	 => t("on"),
			'value'	 => 'on'
		),
		1	 => array(
			'text'	 => t("ei ole"),
			'value'	 => 'ei_ole'
		),
		2	 => array(
			'text'	 => t("on suurempi kuin"),
			'value'	 => 'suurempi_kuin'
		),
		3	 => array(
			'text'	 => t("on pienempi kuin"),
			'value'	 => 'pienempi_kuin'
		),
	);
}

function hae_kampanja($kampanja_tunnus) {
	global $kukarow, $yhtirow;

	$query = "	SELECT *
				FROM kampanjat
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tunnus = '{$kampanja_tunnus}'";
	$result = pupe_query($query);

	$kampanja = mysql_fetch_assoc($result);

	$kampanja['kampanja_ehdot'] = hae_kampanjan_ehdot($kampanja_tunnus);
	$kampanja['kampanja_palkinnot'] = hae_kampanjan_palkinnot($kampanja_tunnus);

	return $kampanja;
}

function nayta_kampanjat() {
	global $kukarow, $yhtiorow;

	$kampanjat = hae_kampanjat();

	echo_kampanjat($kampanjat);
}

function echo_kampanjat($kampanjat) {
	global $kukarow, $yhtiorow;

	if (count($kampanjat) > 0) {

		echo "<table id='ehdot'>";

		echo "<tr>";
		echo "<th>", t("Nimi"), "</th>";
		echo "<th></th>";
		echo "<tr>";

		foreach ($kampanjat as $kampanja) {
			echo "<tr class='aktiivi'>";
			echo "<td>";
			echo $kampanja['nimi'];
			echo "</td>";
			echo "<td>";
			echo "<form method='get' action='kampanja.php'>";
			echo "<input type='hidden' name='tee' value='uusi_kampanja'>";
			echo "<input type='hidden' name='kampanja_tunnus' value='{$kampanja['tunnus']}'>";
			echo "<input type='submit' value='".t("Muokkaa")."'>";
			echo "</form>";
			echo "</td>";
			echo "<tr>";
		}

		echo "</table>";

		echo "<hr>";
		echo "<br>";
	}

	echo "<form method='get' action='kampanja.php'>";
	echo "<input type='hidden' name='tee' value='uusi_kampanja'>";
	echo "<input type='submit' value='".t("Uusi kampanja")."'>";
	echo "</form>";
}
