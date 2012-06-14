<?php

echo "<font class='head'>",t("Laskuhaku"),"</font><hr>";

if (!isset($tee)) $tee = '';
if (!isset($itila)) $itila = '';
if (!isset($ialatila)) $ialatila = '';
if (!isset($pvm)) $pvm = '';
if (!isset($summa1)) $summa1 = '';
if (!isset($summa2)) $summa2 = '';

$index = "";
$ehto  = "";
$jarj  = "";

// Q = laskuja tilansa mukaan
if ($tee == 'Q') {
	$index = " use index (tila_index) ";
	$ehto = " lasku.tila = '{$itila}' and lasku.alatila = '{$ialatila}' ";
	$jarj = " lasku.tapvm ";
}

// E = erääntyviä laskuja pvm:n mukaan
if ($tee == 'E') {
	$ehto = " lasku.tila in ('H','Y','M','P','Q') and lasku.erpcm='{$pvm}' ";
	$jarj = " lasku.summa ";
}

// M = maksettavia laskuja pvm:n mukaan
if ($tee == 'M') {
	$ehto = " lasku.tila in ('H','Y','M','P','Q') and lasku.olmapvm='{$pvm}' ";
	$jarj = " lasku.summa ";
}

// S = Etsitään summaa laskulta
if ($tee == 'S') {

	if (strlen($summa1) > 0) {
		$summa1 = (float) str_replace( ",", ".", $summa1);
	}

	if (strlen($summa2) > 0) {
		$summa2 = (float) str_replace( ",", ".", $summa2);
	}

	$ehto  = " lasku.tila in ('H','Y','M','P','Q') ";
	$index = " use index (yhtio_tila_summa)";
	$jarj  = " lasku.tapvm desc ";

	if (strlen($summa1) > 0 and strlen($summa2) == 0) {
		$ehto .= " and lasku.summa in ({$summa1}, ".($summa1*-1).") ";
	}
	elseif (strlen($summa1) > 0 and strlen($summa2) > 0) {
		$ehto .= " and lasku.summa >= {$summa1} and lasku.summa <= {$summa2} ";
		$jarj = " lasku.summa, lasku.tapvm";
	}
}

// N = Etsitään nimeä laskulta
if ($tee == 'N') {
	$ehto = " lasku.tila in ('H','Y','M','P','Q') and lasku.nimi like '%{$summa1}%'";
	$jarj = " lasku.nimi, lasku.tapvm desc";
}

// V = Etsitään viitettä laskulta
if ($tee == 'V') {
	$ehto = " lasku.tila in ('H','Y','M','P','Q') and lasku.viite like '%{$summa1}%'";
	$jarj = " lasku.nimi, lasku.summa";
}

// K = Etsitään kommenttia laskulta
if ($tee == 'K') {
	$ehto = " lasku.tila in ('H','Y','M','P','Q') and lasku.comments like '%{$summa1}%'";
	$jarj = " lasku.nimi, lasku.summa";
}

// W = Etsitään Viestillä laskulta
if ($tee == 'W') {
	$ehto = " lasku.tila in ('H','Y','M','P','Q') and lasku.viesti like '%{$summa1}%'";
	$jarj = " lasku.nimi, lasku.summa";
}

// T = Laskun tunnus
if ($tee == 'T') {
	$ehto  = " lasku.tila in ('H','Y','M','P','Q') and lasku.tunnus = '{$summa1}'";
	$jarj  = " lasku.nimi, lasku.summa";
	$index = " use index (PRIMARY) ";
}

// L = Toimittajan laskunumero
if ($tee == 'L') {
	$ehto  = " lasku.tila in ('H','Y','M','P','Q') and lasku.laskunro = '{$summa1}'";
	$jarj  = " lasku.nimi, lasku.summa";
	$index = " use index (yhtio_tila_laskunro) ";
}

if ($laskuntyyppi != "K") {
	$ehto .= " AND lasku.tila = '$laskuntyyppi' ";
}

if (($tee == 'S' or $tee == 'N' or $tee == 'V' or $tee == 'K' or $tee == 'W' or $tee == 'L') and isset($ehto) and trim($ehto) != '' and is_numeric($alkuvv) and is_numeric($alkukk) and is_numeric($alkupp) and is_numeric($loppuvv) and is_numeric($loppukk) and is_numeric($loppupp)) {
	$ehto .= " and lasku.tapvm >= '{$alkuvv}-{$alkukk}-{$alkupp}' and lasku.tapvm <= '{$loppuvv}-{$loppukk}-{$loppupp}' ";

	if ($index == "") $index = " use index (yhtio_tila_tapvm) ";
}

echo "<form name = 'valinta' action = 'raportit.php?selaus=n' method='post'>";
echo "<input type = 'hidden' name = 'toim' value = 'laskuhaku'>";
echo "<table>";

echo "<tr><th valign='top'>".t("Alkupvm")."</th>";
echo "<td><select name='alkuvv'>";

$sel = array();
if (!isset($alkuvv) or $alkuvv == "") $alkuvv = date("Y", mktime(0, 0, 0, date("m"), date("d"), date("Y")-1));
$sel[$alkuvv] = "SELECTED";

for ($i = date("Y"); $i >= date("Y")-4; $i--) {
	if (!isset($sel[$i])) $sel[$i] = "";
	echo "<option value='{$i}' {$sel[$i]}>{$i}</option>";
}

echo "</select>";

$sel = array();
if (!isset($alkukk) or $alkukk == "") $alkukk = date("m", mktime(0, 0, 0, date("m"), date("d"), date("Y")-1));
$sel[$alkukk] = "SELECTED";

echo "<select name='alkukk'>";

for ($i = 1; $i < 13; $i++) {
	$val = $i < 10 ? '0'.$i : $i;

	if (!isset($sel[$val])) $sel[$val] = "";

	echo "<option value='{$val}' {$sel[$val]}>{$val}</option>";
}

echo "</select>";

$sel = array();
if (!isset($alkupp) or $alkupp == "") $alkupp = date("d", mktime(0, 0, 0, date("m"), date("d"), date("Y")-1));
$sel[$alkupp] = "SELECTED";

echo "<select name='alkupp'>";

for ($i = 1; $i < 32; $i++) {
	$val = $i < 10 ? '0'.$i : $i;

	if (!isset($sel[$val])) $sel[$val] = "";

	echo "<option value='{$val}' {$sel[$val]}>{$val}</option>";
}

echo "</select></td><td class='back'>&nbsp;</td></tr>";

echo "<tr>
	<th valign='top'>",t("Loppupvm"),"</th>
	<td><select name='loppuvv'>";

$sel = array();
if (!isset($loppuvv) or $loppuvv == "") $loppuvv = date("y", mktime(0, 0, 0, (date("m")+6), 0, date("Y")));
$sel[$loppuvv] = "SELECTED";

for ($i = date("Y")+1; $i >= date("Y")-4; $i--) {

	if (!isset($sel[$i])) $sel[$i] = "";

	echo "<option value='{$i}' {$sel[$i]}>{$i}</option>";
}

echo "</select>";

$sel = array();
if (!isset($loppukk) or $loppukk == "") $loppukk = date("m", mktime(0, 0, 0, (date("m")+6), 0, date("Y")));
$sel[$loppukk] = "SELECTED";

echo "<select name='loppukk'>";

for ($i = 1; $i < 13; $i++) {
	$val = $i < 10 ? '0'.$i : $i;

	if (!isset($sel[$val])) $sel[$val] = "";

	echo "<option value='{$val}' {$sel[$val]}>{$val}</option>";
}

echo "</select>";

$sel = array();
if (!isset($loppupp) or $loppupp == "") $loppupp = date("d", mktime(0, 0, 0, (date("m")+6), 0, date("Y")));
$sel[$loppupp] = "SELECTED";

echo "<select name='loppupp'>";

for ($i = 1; $i < 32; $i++) {
	$val = $i < 10 ? '0'.$i : $i;

	if (!isset($sel[$val])) $sel[$val] = "";

	echo "<option value='{$val}' {$sel[$val]}>{$val}</option>";
}

echo "</select></td><td class='back'>&nbsp;</td></tr>";

$query = "	SELECT tunnus
			FROM kustannuspaikka
			WHERE yhtio = '$kukarow[yhtio]'
			and tyyppi = 'K'
			and kaytossa != 'E'
			LIMIT 1";
$result = pupe_query($query);

if (mysql_num_rows($result) > 0) {

	echo "<tr><th>".t("Kustannuspaikka")."</th><td>";

	$monivalintalaatikot = array("KUSTP");
	$noautosubmit = TRUE;
	$piirra_otsikot = FALSE;

	require ("tilauskasittely/monivalintalaatikot.inc");

	echo "</td></tr>";
}

$sel = array_fill_keys(array($laskuntyyppi), " selected") + array_fill_keys(array('H', 'Y', 'M', 'P', 'Q', 'K'), '');

echo "<tr><th>".t("Laskun tila")."</th>";
echo "<td><select name = 'laskuntyyppi'>
		<option {$sel['K']} value = 'K'>".t("Kaikki")."</option>
		<option {$sel['H']} value = 'H'>".t("Hyväksyttävänä")."</option>
		<option {$sel['M']} value = 'M'>".t("Valmis maksatukseen")."</option>
		<option {$sel['P']} value = 'P'>".t("Poiminnassa")."</option>
		<option {$sel['Q']} value = 'Q'>".t("Odottaa suoritusta")."</option>
		<option {$sel['Y']} value = 'Y'>".t("Maksettu")."</option>
		</select></td>
		</tr>";

$sel = array_fill_keys(array($tee), " selected") + array_fill_keys(array('S', 'N', 'V', 'W', 'K', 'L'), '');

echo "<tr>";
echo "<th>".t("Hakulaji")."</th>";
echo "<td><select name = 'tee'>";
echo "<option value = 'S' {$sel['S']}>".t("Summalla")."</option>";
echo "<option value = 'N' {$sel['N']}>".t("Nimellä")."</option>";
echo "<option value = 'V' {$sel['V']}>".t("Viitteellä")."</option>";
echo "<option value = 'W' {$sel['W']}>".t("Viestillä")."</option>";
echo "<option value = 'K' {$sel['K']}>".t("Kommentilla")."</option>";
echo "<option value = 'L' {$sel['L']}>".t("Laskunumerolla")."</option>";
echo "</select></td></tr>";

echo "<tr><th>".t("Haku")."</th><td><input type = 'text' name = 'summa1' value = '$summa1' size='13'> - <input type = 'text' name = 'summa2' value = '$summa2' size='13'></td>";
echo "<td class='back'><input type = 'submit' value = '".t("Etsi")."'></td>";
echo "</tr>";

echo "</table>";
echo "</form>";
echo "<hr><br><br>";

$formi = 'valinta';
$kentta = 'summa1';

if ($tee != "" and $summa1 == "" and $pvm == "" and (!isset($lisa) or $lisa == "")) {
	echo "<font class='error'>".t("Anna jokin hakukriteeri")."</font><br>";
	$tee = "";
}

if ($tee != '') {

	$alku += 0;

	if (isset($lisa) and strpos($lisa, "tiliointi.kustp") !== FALSE) {
		$tiliointilisa = " JOIN tiliointi on (tiliointi.yhtio = lasku.yhtio and tiliointi.ltunnus = lasku.tunnus) ";
	}
	else {
		$tiliointilisa = '';
	}

	$query = "	SELECT DISTINCT lasku.tapvm, lasku.erpcm, concat_ws('<br>', lasku.nimi, lasku.nimitark) nimi,
				lasku.summa, lasku.valkoodi, lasku.vienti, concat_ws(' ', lasku.viite, lasku.viesti) 'viite/viesti',
				lasku.ebid, lasku.tila, lasku.alatila, lasku.tunnus, if(lasku.laskunro = 0, '', lasku.laskunro) laskunro
			  	FROM lasku $index
				$tiliointilisa
			  	WHERE $ehto
				and lasku.yhtio = '$kukarow[yhtio]'
				$lisa
			  	ORDER BY $jarj
			  	LIMIT $alku, 50";
	$result = mysql_query($query) or pupe_error($query);
	$seraavako = mysql_num_rows($result);

	if (mysql_num_rows($result) == 0) {
		echo "<b>".t("Haulla ei löytynyt yhtään laskua")."</b>";

		require ("inc/footer.inc");
		exit;
	}

	pupe_DataTables(array(array($pupe_DataTables, 8, 8)));

	echo "<table class='display dataTable' id='$pupe_DataTables'>";

	echo "<thead>";
	echo "<tr>
			<th>".t("Tapvm/Erpvm")."</th>
			<th>".t("Nimi")."</th>
			<th>".t("Summa")."</th>
			<th>".t("Valuutta")."</th>
			<th>".t("Laskunro")."</th>
			<th>".t("Viite/Viesti")."</th>
			<th>".t("EBID")."</th>
			<th>".t("Tila/Vienti")."</th>
			</tr>
			<tr>
			<td><input type='text' class='search_field' name='search_tapvm'></td>
			<td><input type='text' class='search_field' name='search_nimi'></td>
			<td><input type='text' class='search_field' name='search_summa'></td>
			<td><input type='text' class='search_field' name='search_valuutta'></td>
			<td><input type='text' class='search_field' name='search_laskunro'></td>
			<td><input type='text' class='search_field' name='search_viite'></td>
			<td><input type='text' class='search_field' name='search_ebid'></td>
			<td><input type='text' class='search_field' name='search_tila'></td>
		</tr>";

	echo "</thead>";
	echo "<tbody>";

	$yhteensa = array();

	while ($trow = mysql_fetch_array($result)) {
		echo "<tr class='aktiivi'>";

		if ($kukarow['taso'] != 1 and $kukarow['taso'] != 2 and $kukarow['taso'] != 3) {
			echo "<td valign='top'>{$trow["tapvm"]}<br>{$trow["erpcm"]}</td>";
		}
		else {
			echo "<td valign='top'><a href = 'muutosite.php?tee=E&tunnus=$trow[tunnus]&lopetus=$PHP_SELF////toim=$toim//tee=$tee//summa1=$summa1//summa2=$summa2//alkuvv=$alkuvv//alkukk=$alkukk//alkupp=$alkupp//loppuvv=$loppuvv//loppukk=$loppukk//loppupp=$loppupp'>{$trow["tapvm"]}</a><br>{$trow["erpcm"]}</td>";
		}

		echo "<td valign='top'>$trow[nimi]</td>";
		echo "<td valign='top' align='right'>$trow[summa]</td>";
		echo "<td valign='top'>$trow[valkoodi]</td>";
		echo "<td valign='top'>$trow[laskunro]</td>";
		echo "<td valign='top'>".$trow["viite/viesti"]."</td>";

		// tehdään lasku linkki
		echo "<td valign='top'>".ebid($trow['tunnus']) ."</td>";

		$laskutyyppi = $trow["tila"];
		require "inc/laskutyyppi.inc";

		echo "<td valign='top'>".t($laskutyyppi)."<br>".ostolaskun_vienti($trow["vienti"])."</td>";

		$yhteensa["ZZ_".$yhtiorow["valkoodi"]] += $trow["summa"];

		if (strtoupper($trow["valkoodi"]) != $yhtiorow["valkoodi"]) {
			$yhteensa[$trow["valkoodi"]] += $trow["summa"];
		}

		echo "</tr>";
	}

	ksort($yhteensa);

	echo "</tbody>";
	echo "<tfoot>";

	foreach ($yhteensa as $val => $sum) {

		if ($val == "ZZ_".$yhtiorow["valkoodi"]) $val = $yhtiorow["valkoodi"];

		echo "<tr>
				<td class='tumma' colspan='2'>".t("Yhteensä:")."</td>
				<td class='tumma' align='right' name='yhteensa' id='yhteensa_$val'>".sprintf("%.2f", $sum)."</td>
				<td class='tumma'>$val</td>
				<td class='tumma' colspan='4'></td></tr>";
	}
	echo "</tfoot>";
	echo "</table><br>";

	if ($alku > 0) {
		$siirry = $alku - 50;
		echo "<a href = '$PHP_SELF?toim=laskuhaku&tee=$tee&pvm=$pvm&summa1=$summa1&summa2=$summa2&alku=$siirry&itila=$itila&ialatila=$ialatila&alkuvv=$alkuvv&alkukk=$alkukk&alkupp=$alkupp&loppuvv=$loppuvv&loppukk=$loppukk&loppupp=$loppupp'>".t("Edelliset")."</a> ";
	}
	else {
		echo t("Edelliset")." ";
	}

	if ($seraavako >= 50) {
		$siirry = $alku + 50;
		echo "<a href = '$PHP_SELF?toim=laskuhaku&tee=$tee&pvm=$pvm&summa1=$summa1&summa2=$summa2&alku=$siirry&itila=$itila&ialatila=$ialatila&alkuvv=$alkuvv&alkukk=$alkukk&alkupp=$alkupp&loppuvv=$loppuvv&loppukk=$loppukk&loppupp=$loppupp'>".t("Seuraavat")."</a> ";
	}

	echo "<br><br>";

 	$toim = "";
}

?>