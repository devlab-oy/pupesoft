<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

require('../inc/parametrit.inc');

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

if ($tee != '') {
	$apaikka = strtoupper(sprintf("%-05s", $ahyllyalue)).strtoupper(sprintf("%05s", $ahyllynro)).strtoupper(sprintf("%05s", $ahyllyvali)).strtoupper(sprintf("%05s", $ahyllytaso));
	$lpaikka = strtoupper(sprintf("%-05s", $lhyllyalue)).strtoupper(sprintf("%05s", $lhyllynro)).strtoupper(sprintf("%05s", $lhyllyvali)).strtoupper(sprintf("%05s", $lhyllytaso));

	$lisa = "";

	if ($toppi != '') {
		$lisa = " LIMIT $toppi ";
	}

	if (!empty($summaa_varastopaikalle)) {
		$result = hae_rivit("PAIKKA", $kukarow, $vva, $kka, $ppa, $vvl, $kkl, $ppl, $apaikka, $lpaikka);
		$saldolliset = echo_rivit("PAIKKA", $result, $ppa, $kka, $vva, $ppl, $kkl, $vvl);
	}
	else {
		$result = hae_rivit("TUOTE", $kukarow, $vva, $kka, $ppa, $vvl, $kkl, $ppl, $apaikka, $lpaikka);
		$saldolliset = echo_rivit("TUOTE", $result, $ppa, $kka, $vva, $ppl, $kkl, $vvl);
	}

	echo_tulosta_inventointilista($saldolliset);
}

echo_kayttoliittyma($ppa, $kka, $vva, $ppl, $kkl, $vvl, $ahyllyalue, $ahyllynro, $ahyllyvali, $ahyllytaso, $lhyllyalue, $lhyllynro, $lhyllyvali, $lhyllytaso, $toppi, $summaa_varastopaikalle);

function hae_rivit($tyyppi, $kukarow, $vva, $kka, $ppa, $vvl, $kkl, $ppl, $apaikka, $lpaikka) {
	if (strtotime("$vva-$kka-$ppa") < strtotime('now - 12 months')) {
		$_date = "AND tapahtuma.laadittu >= '$vva-$kka-$ppa'
				  AND tapahtuma.laadittu <= '$vvl-$kkl-$ppl'";
	}
	else {
		$_date = "AND tapahtuma.laadittu >= Date_sub(Now(), INTERVAL 12 month)";
	}

	if ($tyyppi == "TUOTE") {
		$query = "	SELECT tapahtuma.hyllyalue hyllyalue_alias,
					tapahtuma.hyllynro hyllynro_alias,
					tapahtuma.hyllyvali hyllyvali_alias,
					tapahtuma.hyllytaso hyllytaso_alias,
					sum(if(tapahtuma.laadittu >= '$vva-$kka-$ppa' AND tapahtuma.laadittu <= '$vvl-$kkl-$ppl', 1, 0)) kpl_valittu_aika,
					sum(if(tapahtuma.laadittu >= '$vva-$kka-$ppa' AND tapahtuma.laadittu <= '$vvl-$kkl-$ppl', tapahtuma.kpl * -1, 0)) tuokpl_valittu_aika,
					sum(if(tapahtuma.laadittu >= Date_sub(Now(), INTERVAL 6 month), 1, 0)) kpl_6,
					sum(if(tapahtuma.laadittu >= Date_sub(Now(), INTERVAL 6 month), tapahtuma.kpl * -1, 0)) tuo_kpl_6,
					sum(if(tapahtuma.laadittu >= Date_sub(Now(), INTERVAL 12 month), 1, 0)) kpl_12,
					sum(if(tapahtuma.laadittu >= Date_sub(Now(), INTERVAL 12 month), tapahtuma.kpl * -1, 0)) tuo_kpl_12,
					sum(if(tuotepaikat.tunnus IS NULL , 1, 0)) poistettu,
					tapahtuma.tuoteno,
					tuotepaikat.saldo,
					tuotepaikat.tunnus paikkatun,
					tuote.nimitys
					FROM tapahtuma
					JOIN tuote ON ( tapahtuma.yhtio = tuote.yhtio AND tapahtuma.tuoteno = tuote.tuoteno )
					LEFT JOIN tuotepaikat
					ON (tapahtuma.yhtio = tuotepaikat.yhtio
						and tapahtuma.hyllyalue = tuotepaikat.hyllyalue
						and tapahtuma.hyllynro = tuotepaikat.hyllynro
						and tapahtuma.hyllyvali = tuotepaikat.hyllyvali
						and tapahtuma.hyllytaso = tuotepaikat.hyllytaso
						and tapahtuma.tuoteno = tuotepaikat.tuoteno)
					WHERE  tapahtuma.yhtio = '{$kukarow['yhtio']}'
					AND Concat(Rpad(Upper(tapahtuma.hyllyalue), 5, '0'),
					           Lpad(Upper(tapahtuma.hyllynro), 5, '0'),
					           Lpad(Upper(tapahtuma.hyllyvali), 5, '0'),
					           Lpad(Upper(tapahtuma.hyllytaso), 5, '0')) >= '{$apaikka}'
					AND Concat(Rpad(Upper(tapahtuma.hyllyalue), 5, '0'),
					           Lpad(Upper(tapahtuma.hyllynro), 5, '0'),
					           Lpad(Upper(tapahtuma.hyllyvali), 5, '0'),
					           Lpad(Upper(tapahtuma.hyllytaso), 5, '0')) <= '{$lpaikka}'
					AND tapahtuma.laji = 'laskutus'
					{$_date}
					GROUP BY tapahtuma.hyllyalue, tapahtuma.hyllynro, tapahtuma.hyllyvali, tapahtuma.hyllytaso, tapahtuma.tuoteno
					ORDER BY kpl_valittu_aika DESC";
	}
	else {

		$query = "	SELECT tapahtuma.hyllyalue hyllyalue_alias,
					tapahtuma.hyllynro hyllynro_alias,
					tapahtuma.hyllyvali hyllyvali_alias,
					tapahtuma.hyllytaso hyllytaso_alias,
					sum(if(tapahtuma.laadittu >= '$vva-$kka-$ppa' AND tapahtuma.laadittu <= '$vvl-$kkl-$ppl', 1, 0)) kpl_valittu_aika,
					sum(if(tapahtuma.laadittu >= '$vva-$kka-$ppa' AND tapahtuma.laadittu <= '$vvl-$kkl-$ppl', tapahtuma.kpl * -1, 0)) tuokpl_valittu_aika,
					sum(if(tapahtuma.laadittu >= Date_sub(Now(), INTERVAL 6 month), 1, 0)) kpl_6,
					sum(if(tapahtuma.laadittu >= Date_sub(Now(), INTERVAL 6 month), tapahtuma.kpl * -1, 0)) tuo_kpl_6,
					sum(if(tapahtuma.laadittu >= Date_sub(Now(), INTERVAL 12 month), 1, 0)) kpl_12,
					sum(if(tapahtuma.laadittu >= Date_sub(Now(), INTERVAL 12 month), tapahtuma.kpl * -1, 0)) tuo_kpl_12,
					sum(if(tuotepaikat.tunnus IS NULL , 1, 0)) poistettu,
					tuotepaikat.tunnus paikkatun
					FROM tapahtuma
					LEFT JOIN tuotepaikat
					ON (tapahtuma.yhtio = tuotepaikat.yhtio
						and tapahtuma.hyllyalue = tuotepaikat.hyllyalue
						and tapahtuma.hyllynro = tuotepaikat.hyllynro
						and tapahtuma.hyllyvali = tuotepaikat.hyllyvali
						and tapahtuma.hyllytaso = tuotepaikat.hyllytaso
						and tapahtuma.tuoteno = tuotepaikat.tuoteno)
					WHERE  tapahtuma.yhtio = '{$kukarow['yhtio']}'
				   	AND Concat(Rpad(Upper(tapahtuma.hyllyalue), 5, '0'),
				   	        Lpad(Upper(tapahtuma.hyllynro), 5, '0'),
				   	        Lpad(Upper(tapahtuma.hyllyvali), 5, '0'),
				   	        Lpad(Upper(tapahtuma.hyllytaso), 5, '0')) >= '{$apaikka}'
				   	AND Concat(Rpad(Upper(tapahtuma.hyllyalue), 5, '0'),
				   	        Lpad(Upper(tapahtuma.hyllynro), 5, '0'),
				   	        Lpad(Upper(tapahtuma.hyllyvali), 5, '0'),
				   	        Lpad(Upper(tapahtuma.hyllytaso), 5, '0')) <= '{$lpaikka}'
				   	AND tapahtuma.laji = 'laskutus'
				   	{$_date}
					GROUP BY tapahtuma.hyllyalue, tapahtuma.hyllynro, tapahtuma.hyllyvali, tapahtuma.hyllytaso
					ORDER BY kpl_valittu_aika DESC";
	}

	$result = mysql_query($query) or pupe_error($query);
	return $result;
}


function echo_rivit($tyyppi, $result, $ppa, $kka, $vva, $ppl, $kkl, $vvl) {

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
	echo "<tr>";

	if ($tyyppi == "TUOTE") {
		echo "<th>".t("Tuoteno")."</th>";
		echo "<th>".t("Nimitys")."</th>";
	}

	echo "<th>".t("Varastopaikka")."</th>";


	if ($tyyppi == "TUOTE") {
		echo "<th>".t("Saldo")."</th>";
	}

	echo "<th>".t("Keräystä")."</th>
				<th>".t("Keräystä/Päivä")."</th>
				<th>".t("Kpl/Keräys")."</th>
				<th>".t('Keräystä tästä päivästä 6kk')."</th>
				<th>".t('Keräystä tästä päivästä 12kk')."</th>
				<th>".t('Poistettu varastopaikka')."</th>";
	echo "</tr>";

	//päiviä aikajaksossa
	$epa1 = (int) date('U', mktime(0, 0, 0, $kka, $ppa, $vva));
	$epa2 = (int) date('U', mktime(0, 0, 0, $kkl, $ppl, $vvl));

	//Diff in workdays (5 day week)
	$pva = abs($epa2 - $epa1) / 60 / 60 / 24 / 7 * 5;

	$saldolliset = array();

	while ($row = mysql_fetch_assoc($result)) {
		echo "<tr>";

		if ($tyyppi == "TUOTE") {
			echo "<td>$row[tuoteno]</td>";
			echo "<td>$row[nimitys]</td>";
		}

		echo "<td>$row[hyllyalue_alias] $row[hyllynro_alias] $row[hyllyvali_alias] $row[hyllytaso_alias]</td>";

		if ($tyyppi == "TUOTE") {
			echo "<td align='right'>$row[saldo]</td>";
		}

		$kpl_kerays = $row["kpl_valittu_aika"] > 0 ? round($row["tuokpl_valittu_aika"] / $row["kpl_valittu_aika"]) : "";

		echo "<td align='right'>$row[kpl_valittu_aika]</td>";
		echo "<td align='right'>".round($row["kpl_valittu_aika"] / $pva)."</td>";
		echo "<td align='right'>$kpl_kerays</td>";
		echo "<td align='right'>$row[kpl_6]</td>";
		echo "<td align='right'>$row[kpl_12]</td>";

		if ($row['poistettu'] != 0) {
			echo "<td align='right'><font class='error'>".t('Poistettu')."</font></td>";
		}
		else {
			echo '<td></td>';
			$saldolliset[] = $row["paikkatun"];
		}

		echo "</tr>";
	}
	echo "</table><br/><br/>";

	return $saldolliset;
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

function echo_kayttoliittyma($ppa, $kka, $vva, $ppl, $kkl, $vvl, $ahyllyalue, $ahyllynro, $ahyllyvali, $ahyllytaso, $lhyllyalue, $lhyllynro, $lhyllyvali, $lhyllytaso, $toppi, $summaa_varastopaikalle) {
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
			<input type='text' name='vva' value='$vva' size='5' /></td>
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

	echo "</table>";
	echo '<br/>';
	echo "<input type='submit' value='".t("Aja raportti")."' />";
	echo '</form>';
}

require ("../inc/footer.inc");
