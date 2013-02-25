<?php

// Enabloidaan, että Apache flushaa kaiken mahdollisen ruudulle kokoajan.
ini_set('zlib.output_compression', 0);
ini_set('implicit_flush', 1);
ob_implicit_flush(1);

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

require('../inc/parametrit.inc');
require '../inc/pupeExcel.inc';
require '../inc/ProgressBar.class.php';

ini_set("memory_limit", "2G");

if ($tee == 'lataa_tiedosto') {
	$filepath = "/tmp/".$tmpfilenimi;
	if (file_exists($filepath)) {
		readfile($filepath);
		unlink($filepath);
	}
	exit;
}

// ehdotetaan 7 päivää taaksepäin
if (!isset($kka)) $kka = date("m", mktime(0, 0, 0, date("m") - 3, date("d"), date("Y")));
if (!isset($vva)) $vva = date("Y", mktime(0, 0, 0, date("m") - 3, date("d"), date("Y")));
if (!isset($ppa)) $ppa = date("d", mktime(0, 0, 0, date("m") - 3, date("d"), date("Y")));

if (!isset($kkl)) $kkl = date("m");
if (!isset($vvl)) $vvl = date("Y");
if (!isset($ppl)) $ppl = date("d");

if(!isset($tee))      	$tee = "";
if(!isset($toppi))      $toppi = "";
if(!isset($ahyllyalue)) $ahyllyalue = "";
if(!isset($ahyllynro))  $ahyllynro  = "";
if(!isset($ahyllyvali)) $ahyllyvali = "";
if(!isset($ahyllytaso)) $ahyllytaso = "";
if(!isset($lhyllyalue)) $lhyllyalue = "";
if(!isset($lhyllynro))  $lhyllynro  = "";
if(!isset($lhyllyvali)) $lhyllyvali = "";
if(!isset($lhyllytaso)) $lhyllytaso = "";
if(!isset($summaa_varastopaikalle)) $summaa_varastopaikalle = "";

echo "<font class='head'>".t("Varastopaikkojen keräysseuranta")."</font><hr>";

$kaikki_lisa_kentat = array(
	0 => array(
		'kolumni' => 'tuote.tuotekorkeus',
		'checked' => '',
	),
	1 => array(
		'kolumni' => 'tuote.tuoteleveys',
		'checked' => '',
	),
	2 => array(
		'kolumni' => 'tuote.tuotesyvyys',
		'checked' => '',
	),
	3 => array(
		'kolumni' => 'tuote.tuotemassa',
		'checked' => '',
	),
	4 => array(
		'kolumni' => 'tuote.status',
		'checked' => '',
	),
	5 => array(
		'kolumni' => 'tuote.luontiaika',
		'checked' => '',
	),
	6 => array(
		'kolumni' => 'tuote.tuoteno',
		'checked' => '',
	),
	7 => array(
		'kolumni' => 'tuote.nimitys',
		'checked' => '',
	),
	8 => array(
		'kolumni' => 'tuote.ostoehdotus',
		'checked' => '',
	),
);
//for looppi vain sen takia, että saadaan synkattua formista valitut kentät, mahdollisien kenttien kanssa
foreach($kaikki_lisa_kentat as $kentat_index => &$kentat_value) {
	if (isset($lisa_kentat) and in_array($kentat_index, $lisa_kentat)) {
		$kentat_value['checked'] = "checked='checked'";
	}
	else {
		$kentat_value['checked'] = "";
	}
}

$query = "	SELECT tunnus,
			nimitys
			FROM varastopaikat
			WHERE yhtio = '{$kukarow['yhtio']}'
			ORDER BY nimitys ASC";
$result = pupe_query($query);
$kaikki_varastot = array();
while($varasto = mysql_fetch_assoc($result)) {
	if (isset($varastot) and in_array($varasto['tunnus'], $varastot)) {
		$checked = "checked = 'checked'";
	}
	else {
		$checked = "";
	}
	$kaikki_varastot[$varasto['tunnus']] = array(
		'nimitys' => $varasto['nimitys'],
		'checked' => $checked,
	);
}

$query = "	SELECT tunnus, nimitys
			FROM keraysvyohyke
			WHERE yhtio = '{$kukarow['yhtio']}'
			AND nimitys != ''";
$result = pupe_query($query);
$kaikki_keraysvyohykkeet = array();
while($keraysvyohyke = mysql_fetch_assoc($result)) {
	if (isset($keraysvyohykkeet) and in_array($keraysvyohyke['tunnus'], $keraysvyohykkeet)) {
		$checked = "checked = 'checked'";
	}
	else {
		$checked = "";
	}
	$kaikki_keraysvyohykkeet[$keraysvyohyke['tunnus']] = array(
		'nimitys' => $keraysvyohyke['nimitys'],
		'checked' => $checked,
	);
}

if ($tee != '') {
	$apaikka = strtoupper(sprintf("%-05s", $ahyllyalue)).strtoupper(sprintf("%05s", $ahyllynro)).strtoupper(sprintf("%05s", $ahyllyvali)).strtoupper(sprintf("%05s", $ahyllytaso));
	$lpaikka = strtoupper(sprintf("%-05s", $lhyllyalue)).strtoupper(sprintf("%05s", $lhyllynro)).strtoupper(sprintf("%05s", $lhyllyvali)).strtoupper(sprintf("%05s", $lhyllytaso));

	$apaikka = array(
		'ahyllyalue' => $ahyllyalue,
		'ahyllynro' => $ahyllynro,
		'ahyllyvali' => $ahyllyvali,
		'ahyllytaso' => $ahyllytaso,

	);
	$lpaikka = array(
		'lhyllyalue' => $lhyllyalue,
		'lhyllynro' => $lhyllynro,
		'lhyllyvali' => $lhyllyvali,
		'lhyllytaso' => $lhyllytaso,
	);
	$lisa = "";

	if ($toppi != '') {
		$lisa = " LIMIT $toppi ";
	}

	$header_values = array(
		'tuoteno' => t('Tuoteno'),
		'nimitys' => t('Tuotteen nimi'),
		'varaston_nimitys' => t('Varasto'),
		'keraysvyohykkeen_nimitys' => t('Keräysvyöhyke'),
		'hylly' => t('Varastopaikka'),
		'saldo' => t('Saldo'),
		'kpl_valittu_aika' => t('Keräystä'),
		'kpl_valittu_aika_pvm' => t('Keräystä/Päivä'),
		'kpl_kerays' => t('Kpl/Keräys'),
		'kpl_6' => t('Keräystä tästä päivästä 6kk'),
		'kpl_12' => t('Keräystä tästä päivästä 12kk'),
		'poistettu' => t('Poistettu varastopaikka'),
		'tuotekorkeus' => t('Tuotteen korkeus'),
		'tuoteleveys' => t('Tuotteen leveys'),
		'tuotesyvyys' => t('Tuotteen syvyys'),
		'tuotemassa' => t('Tuotteen massa'),
		'status' => t('Status'),
		'luontiaika' => t('Tuotteen Luontiaika'),
		'ostoehdotus' => t('Ostoehdotus'),
	);
	$force_to_string = array(
		'tuoteno'
	);

	if (!empty($summaa_varastopaikalle)) {
		list($rivit, $saldolliset) = hae_rivit("PAIKKA", $kukarow, $vva, $kka, $ppa, $vvl, $kkl, $ppl, $apaikka, $lpaikka, $varastot, $keraysvyohykkeet, $kaikki_lisa_kentat);
		if(count($rivit) > 0) {
			$xls_filename = generoi_excel_tiedosto($rivit, $header_values, $force_to_string);
			echo_tallennus_formi($xls_filename);

			nayta_ruudulla($rivit, $header_values, $force_to_string, $ppa, $kka, $vva, $ppl, $kkl, $vvl);
		}
	}
	else {
		list($rivit, $saldolliset) = hae_rivit("TUOTE", $kukarow, $vva, $kka, $ppa, $vvl, $kkl, $ppl, $apaikka, $lpaikka, $varastot, $keraysvyohykkeet, $kaikki_lisa_kentat);
		if(count($rivit) > 0) {
			$xls_filename = generoi_excel_tiedosto($rivit, $header_values, $force_to_string);
			echo_tallennus_formi($xls_filename);

			nayta_ruudulla($rivit, $header_values, $force_to_string, $ppa, $kka, $vva, $ppl, $kkl, $vvl);
		}
	}

	echo_tulosta_inventointilista($saldolliset);
}

echo_kayttoliittyma($ppa, $kka, $vva, $ppl, $kkl, $vvl, $ahyllyalue, $ahyllynro, $ahyllyvali, $ahyllytaso, $lhyllyalue, $lhyllynro, $lhyllyvali, $lhyllytaso, $toppi, $summaa_varastopaikalle, $kaikki_varastot, $kaikki_keraysvyohykkeet, $kaikki_lisa_kentat);

function hae_rivit($tyyppi, $kukarow, $vva, $kka, $ppa, $vvl, $kkl, $ppl, $apaikka, $lpaikka, $varastot, $keraysvyohykkeet, $lisa_kentat) {
	$ostoehdotukset = array(
		'' => t("Ehdotetaan ostoehdotusohjelmissa tilattavaksi"),
		'E' => ("Ei ehdoteta ostoehdotusohjelmissa tilattavaksi"),
	);
	if (strtotime("$vva-$kka-$ppa") < strtotime('now - 12 months')) {
		$_date = "AND tapahtuma.laadittu >= '$vva-$kka-$ppa'
				  AND tapahtuma.laadittu <= '$vvl-$kkl-$ppl'";
	}
	else {
		$_date = "AND tapahtuma.laadittu >= Date_sub(Now(), INTERVAL 12 month)";
	}

	$tuotepaikka_where = "";
	$a = array_filter($apaikka);
	$l = array_filter($lpaikka);
	if(!empty($a) or !empty($l)) {
		$ahyllyalue = $apaikka['ahyllyalue'];
		$ahyllynro = $apaikka['ahyllynro'];
		$ahyllyvali = $apaikka['ahyllyvali'];
		$ahyllytaso = $apaikka['ahyllytaso'];
		$lhyllyalue = $lpaikka['lhyllyalue'];
		$lhyllynro = $lpaikka['lhyllynro'];
		$lhyllyvali = $lpaikka['lhyllyvali'];
		$lhyllytaso = $lpaikka['lhyllytaso'];
		
		$tuotepaikka_where = "and concat(rpad(upper(tapahtuma.hyllyalue) ,5,'0'),lpad(upper(tapahtuma.hyllynro) ,5,'0'),lpad(upper(tapahtuma.hyllyvali) ,5,'0'),lpad(upper(tapahtuma.hyllytaso) ,5,'0')) >=
					concat(rpad(upper('$ahyllyalue'), 5, '0'),lpad(upper('$ahyllynro'), 5, '0'),lpad(upper('$ahyllyvali'), 5, '0'),lpad(upper('$ahyllytaso'),5, '0'))
					and concat(rpad(upper(tapahtuma.hyllyalue) ,5,'0'),lpad(upper(tapahtuma.hyllynro) ,5,'0'),lpad(upper(tapahtuma.hyllyvali) ,5,'0'),lpad(upper(tapahtuma.hyllytaso) ,5,'0')) <=
					concat(rpad(upper('$lhyllyalue'), 5, '0'),lpad(upper('$lhyllynro'), 5, '0'),lpad(upper('$lhyllyvali'), 5, '0'),lpad(upper('$lhyllytaso'),5, '0'))";
	}

	$varasto_join = "";
	if(!empty($varastot)) {
		$varasto_join = " AND varastopaikat.tunnus IN (".implode(",", $varastot).") ";
	}

	$keraysvyohyke_join = "";
	if(!empty($keraysvyohykkeet)) {
		$keraysvyohyke_join = " AND keraysvyohyke.tunnus IN (".implode(",", $keraysvyohykkeet).") ";
	}

	if ($tyyppi == "TUOTE") {
		$vresult = t_avainsana("S");
		$tuote_statukset = array();
		while($status = mysql_fetch_assoc($vresult)) {
			$tuote_statukset[$status['selite']] = $status['selitetark'];
		}

		if(!empty($lisa_kentat)) {
			$tuote_select = "";
			foreach($lisa_kentat as $lisa_kentta) {
				if(!empty($lisa_kentta['checked'])) {
					$tuote_select .= $lisa_kentta['kolumni'] . ', ';
				}
			}
		}
		$tuotepaikat_select = "tuotepaikat.saldo, tuotepaikat.tunnus paikkatun, ";
		$group = "GROUP BY tapahtuma.hyllyalue, tapahtuma.hyllynro, tapahtuma.hyllyvali, tapahtuma.hyllytaso, tapahtuma.tuoteno";
	}
	else {
		$tuote_select = "";
		$tuotepaikat_select = "";
		$group = "GROUP BY tapahtuma.hyllyalue, tapahtuma.hyllynro, tapahtuma.hyllyvali, tapahtuma.hyllytaso";
	}

	$query = "	SELECT varastopaikat.nimitys as varaston_nimitys,
					keraysvyohyke.nimitys as keraysvyohykkeen_nimitys,
					CONCAT_WS(' ', tapahtuma.hyllyalue, tapahtuma.hyllynro, tapahtuma.hyllyvali, tapahtuma.hyllytaso) as hylly,
					sum(if(tapahtuma.laadittu >= '$vva-$kka-$ppa' AND tapahtuma.laadittu <= '$vvl-$kkl-$ppl', 1, 0)) kpl_valittu_aika,
					sum(if(tapahtuma.laadittu >= '$vva-$kka-$ppa' AND tapahtuma.laadittu <= '$vvl-$kkl-$ppl', tapahtuma.kpl * -1, 0)) tuokpl_valittu_aika,
					sum(if(tapahtuma.laadittu >= Date_sub(Now(), INTERVAL 6 month), 1, 0)) kpl_6,
					sum(if(tapahtuma.laadittu >= Date_sub(Now(), INTERVAL 6 month), tapahtuma.kpl * -1, 0)) tuo_kpl_6,
					sum(if(tapahtuma.laadittu >= Date_sub(Now(), INTERVAL 12 month), 1, 0)) kpl_12,
					sum(if(tapahtuma.laadittu >= Date_sub(Now(), INTERVAL 12 month), tapahtuma.kpl * -1, 0)) tuo_kpl_12,
					{$tuote_select}
					{$tuotepaikat_select}
					sum(if(tuotepaikat.tunnus IS NULL , 1, 0)) poistettu
					FROM tapahtuma
					JOIN tuote ON ( tapahtuma.yhtio = tuote.yhtio AND tapahtuma.tuoteno = tuote.tuoteno )
					LEFT JOIN tuotepaikat
					ON (tapahtuma.yhtio = tuotepaikat.yhtio
						and tapahtuma.hyllyalue = tuotepaikat.hyllyalue
						and tapahtuma.hyllynro = tuotepaikat.hyllynro
						and tapahtuma.hyllyvali = tuotepaikat.hyllyvali
						and tapahtuma.hyllytaso = tuotepaikat.hyllytaso
						and tapahtuma.tuoteno = tuotepaikat.tuoteno)
					JOIN varastopaikat
					ON (
						varastopaikat.yhtio = tapahtuma.yhtio
						AND concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(tapahtuma.hyllyalue), 5, '0'),lpad(upper(tapahtuma.hyllynro), 5, '0'))
						AND concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(tapahtuma.hyllyalue), 5, '0'),lpad(upper(tapahtuma.hyllynro), 5, '0'))
						$varasto_join
					)
					JOIN keraysvyohyke
					ON (
						keraysvyohyke.yhtio = varastopaikat.yhtio
						AND keraysvyohyke.varasto = varastopaikat.tunnus
						{$keraysvyohyke_join}
					)
					WHERE  tapahtuma.yhtio = '{$kukarow['yhtio']}'
					AND tapahtuma.laji = 'laskutus'
					{$tuotepaikka_where}
					{$_date}
					{$group}
					ORDER BY kpl_valittu_aika DESC";

//	echo "<pre>";
//	echo $query;
//	echo "</pre>";
					
	$result = mysql_query($query) or pupe_error($query);

	//päiviä aikajaksossa
	$epa1 = (int) date('U', mktime(0, 0, 0, $kka, $ppa, $vva));
	$epa2 = (int) date('U', mktime(0, 0, 0, $kkl, $ppl, $vvl));
	//Diff in workdays (5 day week)
	$pva = abs($epa2 - $epa1) / 60 / 60 / 24 / 7 * 5;

	$poistettu = t('Poistettu');

	$rows = array();
	$saldolliset = array();
	$progress_bar = new ProgressBar(t("Haetaan tiedot"));
	$progress_bar->initialize(mysql_num_rows($result));
	while($row = mysql_fetch_assoc($result)) {
		$progress_bar->increase();
		if($tyyppi == 'TUOTE') {
			$row['nimitys'] = t_tuotteen_avainsanat($row, 'nimitys');
			if(isset($row['status']) and array_key_exists($row['status'], $tuote_statukset)) {
				$row['status'] = $tuote_statukset[$row['status']];
			}

			if(isset($row['ostoehdotus']) and array_key_exists($row['ostoehdotus'], $ostoehdotukset)) {
				$row['ostoehdotus'] = $ostoehdotukset[$row['ostoehdotus']];
			}
			else if(isset($row['ostoehdotus']) and !array_key_exists($row['ostoehdotus'], $ostoehdotukset)) {
				$row['ostoehdotus'] = t("Tuntematon");
			}
		}
		
		$row['kpl_kerays'] = $row["kpl_valittu_aika"] > 0 ? round($row["tuokpl_valittu_aika"] / $row["kpl_valittu_aika"]) : "";
		$row['kpl_valittu_aika_pvm'] = round($row["kpl_valittu_aika"] / $pva);

		if ($row['poistettu'] != 0) {
			$row['poistettu'] = $poistettu;
		}
		else {
			$saldolliset[] = $row["paikkatun"];
			$row['poistettu'] = '';
		}

		unset($row['tuokpl_valittu_aika']);
		unset($row['tuo_kpl_6']);
		unset($row['tuo_kpl_12']);
		unset($row['paikkatun']);

		$rows[] = $row;
	}

	echo "<br/>";

	return array($rows, $saldolliset);
}

function echo_tulosta_inventointilista($saldolliset) {
	echo "<form method='POST' action='../inventointi_listat.php'>";
	echo "<input type='hidden' name='tee' value='TULOSTA'>";

	$saldot = "";
	foreach ($saldolliset as $saldo) {
		$saldot .= "$saldo,";
	}
	$saldot = substr($saldot, 0, -1);

	echo "<input type='hidden' name='saldot' value='$saldot'>";
	echo "<input type='hidden' name='tulosta' value='JOO'>";
	echo "<input type='hidden' name='tila' value='SIIVOUS'>";
	echo "<input type='hidden' name='ei_inventointi' value='EI'>";
	echo "<input type='submit' value='".t("Tulosta inventointilista")."'></form><br><br>";
}

function echo_kayttoliittyma($ppa, $kka, $vva, $ppl, $kkl, $vvl, $ahyllyalue, $ahyllynro, $ahyllyvali, $ahyllytaso, $lhyllyalue, $lhyllynro, $lhyllyvali, $lhyllytaso, $toppi, $summaa_varastopaikalle, $varastot, $keraysvyohykkeet, $lisa_kentat) {
	//Käyttöliittymä
	echo "<br>";
	echo "<form method='POST'>";
	echo "<table>";
	echo "<input type='hidden' name='tee' value='kaikki' />";

	if (!empty($summaa_varastopaikalle)) {
		$checked = 'checked = "checked"';
	}
	else {
		$checked = '';
	}

	echo "<tr><th>".t('Summaa per varastopaikka')."</th>
			<td><input type='checkbox' name='summaa_varastopaikalle' $checked /></td></tr>";

	echo "<tr><th>".t("Syötä alkupäivämäärä (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppa' value='$ppa' size='3'>
			<input type='text' name='kka' value='$kka' size='3' />
			<input type='text' name='vva' value='$vva' size='5' /> ".t("Alkupäivämäärä voi olla korkeintaan 12kk päässä nykyhetkestä")."</td>
			</tr><tr><th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppl' value='$ppl' size='3' />
			<input type='text' name='kkl' value='$kkl' size='3' />
			<input type='text' name='vvl' value='$vvl' size='5' /></td>";

	echo "<tr><th>".t("Anna alkuvarastopaikka:")."</th>
			<td><input type='text' size='6' name='ahyllyalue' value='$ahyllyalue' />
			<input type='text' size='6' name='ahyllynro' value='$ahyllynro' />
			<input type='text' size='6' name='ahyllyvali' value='$ahyllyvali' />
			<input type='text' size='6' name='ahyllytaso' value='$ahyllytaso' />
			</td></tr>";

	echo "<tr><th>".t("ja loppuvarastopaikka:")."</th>
			<td><input type='text' size='6' name='lhyllyalue' value='$lhyllyalue' />
			<input type='text' size='6' name='lhyllynro' value='$lhyllynro' />
			<input type='text' size='6' name='lhyllyvali' value='$lhyllyvali' />
			<input type='text' size='6' name='lhyllytaso' value='$lhyllytaso' />
			</td></tr>";

	echo "<tr><th>".t("Listaa vain näin monta kerätyintä tuotetta:")."</th>
			<td><input type='text' size='6' name='toppi' value='$toppi' /></td>";

	echo "<tr>";
	echo "<th>";
	echo t("Varastot");
	echo "</th>";
	echo "<td>";
	foreach($varastot as $varasto_index => $varasto) {
		echo "<input class='varastot' type='checkbox' name='varastot[]' value='{$varasto_index}' {$varasto['checked']} />";
		echo " {$varasto['nimitys']}";
		echo "<br/>";
	}
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>";
	echo t("Keräysvyöhykkeet");
	echo "</th>";
	echo "<td>";
	foreach($keraysvyohykkeet as $keraysvyohykkeet_index => $keraysvyohykkeet) {
		echo "<input class='keraysvyohykkeet' type='checkbox' name='keraysvyohykkeet[]' value='{$keraysvyohykkeet_index}' {$keraysvyohykkeet['checked']} />";
		echo " {$keraysvyohykkeet['nimitys']}";
		echo "<br/>";
	}
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>";
	echo t("Lisäkentät");
	echo "</th>";
	echo "<td>";
	foreach($lisa_kentat as $lisa_kentat_index => $lisa_kentat) {
		echo "<input class='keraysvyohykkeet' type='checkbox' name='lisa_kentat[]' value='{$lisa_kentat_index}' {$lisa_kentat['checked']} />";
		echo " {$lisa_kentat['kolumni']}";
		echo "<br/>";
	}
	echo "</td>";
	echo "</tr>";

	echo "</table>";
	echo '<br/>';
	echo "<input type='submit' value='".t("Aja raportti")."' />";
	echo '</form>';
}

function echo_tallennus_formi($xls_filename) {
	echo "<form method='post' class='multisubmit'>";
	echo "<table>";
	echo "<tr>";
	echo "<th>".t("Tallenna excel aineisto").":</th>";
	echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
	echo "<input type='hidden' name='lataa_tiedosto' value='1'>";
	echo "<input type='hidden' name='kaunisnimi' value='".t('Keraysseuranta').".xlsx'>";
	echo "<input type='hidden' name='tmpfilenimi' value='{$xls_filename}'>";
	echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td>";
	echo "</tr>";
	echo "</table>";
	echo "</form>";
	echo "<br/>";
}

function generoi_excel_tiedosto(&$rivit, $header_values, $force_to_string) {
	$xls = new pupeExcel();
	$rivi = 0;
	$sarake = 0;

	xls_headerit($xls, $rivit, $rivi, $sarake, $header_values);

	xls_rivit($xls, $rivit, $rivi, $sarake, $force_to_string);

	$xls_tiedosto = $xls->close();

	return $xls_tiedosto;
}

function xls_headerit(pupeExcel &$xls, &$rivit, &$rivi, &$sarake, $header_values) {
	$style = array("bold" => TRUE);
	foreach ($rivit[0] as $header_text => $value) {
		if (!stristr($header_text, 'tunnus')) {
			if (array_key_exists($header_text, $header_values)) {
				kirjoita_header_solu($xls, $header_values[$header_text], $rivi, $sarake, $style);
			}
			else {
				//fail safe
				kirjoita_header_solu($xls, $header_text, $rivi, $sarake, $style);
			}
		}
	}
	$rivi++;
	$sarake = 0;
}

function xls_rivit(pupeExcel &$xls, &$rivit, &$rivi, &$sarake, $force_to_string) {
	$xls_progress_bar = new ProgressBar(t("Tallennetaan exceliin"));
	$xls_progress_bar->initialize(count($rivit));
	
	foreach ($rivit as $rivi) {
		foreach ($rivi as $header => $solu) {
			if (!stristr($header, 'tunnus')) {
				kirjoita_solu($xls, $header, $solu, $rivi, $sarake, $force_to_string);
			}
		}
		$rivi++;
		$sarake = 0;

		$xls_progress_bar->increase();
	}

	echo "<br/>";
}

function kirjoita_solu(&$xls, $key, $string, &$rivi, &$sarake, $force_to_string) {
	if (is_numeric($string) and !in_array($key, $force_to_string)) {
		$xls->writeNumber($rivi, $sarake, $string);
	}
	else if (valid_date($string) != 0 and valid_date($string) !== false and !in_array($key, $force_to_string)) {
		$xls->writeDate($rivi, $sarake, $string);
	}
	else {
		$xls->write($rivi, $sarake, $string);
	}
	$sarake++;
}

function kirjoita_header_solu(&$xls, $string, &$rivi, &$sarake, $style = array()) {
	$xls->write($rivi, $sarake, $string, $style);
	$sarake++;
}

function valid_date($date) {
	//preg_match() returns 1 if the pattern matches given subject, 0 if it does not, or FALSE if an error occurred.
	return (preg_match("/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/", $date));
}

function nayta_ruudulla($rivit, $header_values, $force_to_string, $ppa, $kka, $vva, $ppl, $kkl, $vvl) {
	echo "<table><tr>
	<th>".t("Valittu kausi")."</th>
	<td>{$ppa}</td>
	<td>{$kka}</td>
	<td>{$vva}</td>
	<th>-</th>
	<td>{$ppl}</td>
	<td>{$kkl}</td>
	<td>{$vvl}</td>
	</tr></table><br>";
		
	echo "<table>";
	echo_headers($rivit[0], $header_values);
	echo_rivit($rivit, $force_to_string);
	echo "</table>";
}

function echo_headers($rivi, $header_values) {
	echo "<tr>";
	foreach ($rivi as $header_text => $value) {
		if (!stristr($header_text, 'tunnus')) {
			if (array_key_exists($header_text, $header_values)) {
				echo "<th>{$header_values[$header_text]}</th>";
			}
			else {
				//fail safe
				echo "<th>{$header_text}</th>";
			}
		}
	}
	echo "</tr>";
}

function echo_rivit($rivit, $force_to_string) {
	foreach ($rivit as $rivi) {
		echo "<tr>";
		foreach ($rivi as $header => &$solu) {
			if (!stristr($header, 'tunnus')) {
				if (is_numeric($solu) and floatval($solu) and !in_array($header, $force_to_string)) {
					$solu = number_format($solu, 2);
				}
				echo "<td>{$solu}</td>";
			}
		}
		echo "</tr>";
	}
}

require ("../inc/footer.inc");
