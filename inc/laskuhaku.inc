<?php

echo "<font class='head'>".t("Laskuhaku")."</font><hr>";

if (isset($tee) and $summa1 == "" and $pvm == "") {
	echo "<font class='error'>".t("Anna jokin hakukriteeri")."</font><br>";
	unset($tee);
}

if (!isset($tee)) $tee='';

$index = "";
$ehto = "";
$jarj = "";

// Q = laskuja tilansa mukaan
if ($tee == 'Q') {
	$index = " use index (tila_index) ";
	$ehto = "tila = '$itila' and alatila = '$ialatila'";
	$jarj = "tapvm";
}

// E = erääntyviä laskuja pvm:n mukaan
if ($tee == 'E') {
	$ehto = "tila in ('H','Y','M','P','Q') and erpcm='$pvm'";
	$jarj = "summa";
}

// M = maksettavia laskuja pvm:n mukaan
if ($tee == 'M') {
	$ehto = "tila in ('H','Y','M','P','Q') and olmapvm='$pvm'";
	$jarj = "summa";
}

// S = Etsitään summaa laskulta
if ($tee == 'S') {
	
	if (strlen($summa2) == 0) {
		$summa2 = $summa1;
	}
	
	$summa1 = (float) str_replace( ",", ".", $summa1);
	$summa2 = (float) str_replace( ",", ".", $summa2);

	$ehto = "tila in ('H','Y','M','P','Q') and ";
	$index = " use index (yhtio_tila_summa)";

	if ($summa1 == $summa2) {
		$ehto .= "summa in ($summa1, ".($summa1*-1).") ";	
		$jarj = "tapvm desc";
	}
	else {
		$ehto .= "summa >= " . $summa1 . " and summa <= " . $summa2;
		$jarj = "summa, tapvm";
	}
}

// N = Etsitään nimeä laskulta
if ($tee == 'N') {
	$ehto = "tila in ('H','Y','M','P','Q') and nimi like '%".$summa1."%'";
	$jarj = "nimi, tapvm desc";
}

// V = Etsitään viitettä laskulta
if ($tee == 'V') {
	$ehto = "tila in ('H','Y','M','P','Q') and viite like '%$summa1%'";
	$jarj = "nimi, summa";
}

// K = Etsitään kommenttia laskulta
if ($tee == 'K') {
	$ehto = "tila in ('H','Y','M','P','Q') and comments like '%$summa1%'";
	$jarj = "nimi, summa";
}

// W = Etsitään Viestillä laskulta
if ($tee == 'W') {
	$ehto = "tila in ('H','Y','M','P','Q') and viesti like '%$summa1%'";
	$jarj = "nimi, summa";
}

// T = Laskun tunnus
if ($tee == 'T') {
	$ehto = "tila in ('H','Y','M','P','Q') and tunnus = '$summa1'";
	$jarj = "nimi, summa";
}

// L = Toimittajan laskunumero
if ($tee == 'L') {
	$ehto = "tila in ('H','Y','M','P','Q') and laskunro = '$summa1'";
	$jarj = "nimi, summa";
}

if (($tee == 'S' or $tee == 'N' or $tee == 'V' or $tee == 'K' or $tee == 'W' or $tee == 'L') and isset($ehto) and trim($ehto) != '' and is_numeric($alkuvv) and is_numeric($alkukk) and is_numeric($alkupp) and is_numeric($loppuvv) and is_numeric($loppukk) and is_numeric($loppupp)) {
	$ehto .= " and tapvm >= '$alkuvv-$alkukk-$alkupp' and tapvm <= '$loppuvv-$loppukk-$loppupp' ";
}

echo "<br>";
echo "<form name = 'valinta' action = 'raportit.php?selaus=n' method='post'>";
echo "<input type = 'hidden' name = 'toim' value = 'laskuhaku'>";
echo "<table>";

echo "<tr><th valign='top'>".t("Alkupvm")."</th>";
echo "<td><select name='alkuvv'>";

$sel = array();
if (!isset($alkuvv) or $alkuvv == "") $alkuvv = date("Y", mktime(0, 0, 0, date("m"), date("d"), date("Y")-1));
$sel[$alkuvv] = "SELECTED";

for ($i = date("Y"); $i >= date("Y")-4; $i--) {
	echo "<option value='$i' $sel[$i]>$i</option>";
}

echo "</select>";

$sel = array();
if (!isset($alkukk) or $alkukk == "") $alkukk = date("m", mktime(0, 0, 0, date("m"), date("d"), date("Y")-1));
$sel[$alkukk] = "SELECTED";

echo "<select name='alkukk'>";

for ($i = 1; $i < 13; $i++) {
	$val = $i < 10 ? '0'.$i : $i;
	echo "<option value='$val' $sel[$val]>$val</option>";
}

echo "</select>";

$sel = array();
if (!isset($alkupp) or $alkupp == "") $alkupp = date("d", mktime(0, 0, 0, date("m"), date("d"), date("Y")-1));
$sel[$alkupp] = "SELECTED";

echo "<select name='alkupp'>";

for ($i = 1; $i < 32; $i++) {
	$val = $i < 10 ? '0'.$i : $i;
	echo "<option value='$val' $sel[$val]>$val</option>";
}

echo "</select></td><td class='back'>&nbsp;</td></tr>";


echo "<tr>
	<th valign='top'>".t("Loppupvm")."</th>
	<td><select name='loppuvv'>";

$sel = array();
if (!isset($loppuvv) or $loppuvv == "") $loppuvv = date("y", mktime(0, 0, 0, (date("m")+6), 0, date("Y")));
$sel[$loppuvv] = "SELECTED";

for ($i = date("Y")+1; $i >= date("Y")-4; $i--) {
	echo "<option value='$i' $sel[$i]>$i</option>";
}

echo "</select>";

$sel = array();
if (!isset($loppukk) or $loppukk == "") $loppukk = date("m", mktime(0, 0, 0, (date("m")+6), 0, date("Y")));
$sel[$loppukk] = "SELECTED";

echo "<select name='loppukk'>";

for ($i = 1; $i < 13; $i++) {
	$val = $i < 10 ? '0'.$i : $i;
	echo "<option value='$val' $sel[$val]>$val</option>";
}

echo "</select>";

$sel = array();
if (!isset($loppupp) or $loppupp == "") $loppupp = date("d", mktime(0, 0, 0, (date("m")+6), 0, date("Y")));
$sel[$loppupp] = "SELECTED";

echo "<select name='loppupp'>";

for ($i = 1; $i < 32; $i++) {
	$val = $i < 10 ? '0'.$i : $i;
	echo "<option value='$val' $sel[$val]>$val</option>";
}

echo "</select></td><td class='back'>&nbsp;</td></tr>";

$sel=array();
$sel[$tee] = "SELECTED";

echo "<tr>";
echo "<th>".t("Etsi lasku")."</th>";
echo "<td><select name = 'tee'>";
echo "<option value = 'S' $sel[S]>".t("Summalla")."</option>";
echo "<option value = 'N' $sel[N]>".t("Nimellä")."</option>";
echo "<option value = 'V' $sel[V]>".t("Viitteellä")."</option>";
echo "<option value = 'W' $sel[W]>".t("Viestillä")."</option>";
echo "<option value = 'K' $sel[K]>".t("Kommentilla")."</option>";
echo "<option value = 'L' $sel[L]>".t("Laskunumerolla")."</option>";
echo "</select></td>";
echo "<td><input type = 'text' name = 'summa1' value = '$summa1' size='13'> - <input type = 'text' name = 'summa2' value = '$summa2' size='13'></td>";
echo "<td class='back'><input type = 'submit' value = '".t("Valitse")."'></td>";
echo "</tr>";

echo "</table>";
echo "</form>";
echo "<br><br>";

$formi = 'valinta';
$kentta = 'summa1';

if ($tee != '') {

	$alku += 0;

	$query = "	SELECT tapvm, erpcm, concat_ws('<br>', nimi, nimitark) nimi, summa, valkoodi, vienti, concat_ws(' ', viite, viesti) 'viite/viesti', ebid, tila, alatila, tunnus, if(laskunro = 0, '', laskunro) laskunro
			  	FROM lasku $index
			  	WHERE $ehto
				and yhtio = '$kukarow[yhtio]'
			  	ORDER BY $jarj
			  	LIMIT $alku, 50";
	$result = mysql_query($query) or pupe_error($query);
	$seraavako = mysql_num_rows($result);

	if (mysql_num_rows($result) == 0) {
		echo "<b>".t("Haulla ei löytynyt yhtään laskua")."</b>";

		require ("inc/footer.inc");
		exit;
	}

	$footerCallBack = ",
		\"fnFooterCallback\": function ( nRow, aaData, iStart, iEnd, aiDisplay ) {
			var summat = new Array();
			var valuutat = new Array();
			var valuutta = '';

			for (var i=iStart; i<iEnd; i++) {
				valuutta = aaData[aiDisplay[i]][3];
				valuutat[valuutta] = valuutta;

				if (typeof summat[valuutta] == 'undefined') summat[valuutta] = 0;
				summat[valuutta] += aaData[aiDisplay[i]][2]*1;
			}

			// Tyhjennetään kaikki summat
			$('td[name=yhteensa]').each(
				function() {
					$( this ).html('');
				}
			);
			
			// Ekotetaan vain ne valuutat jotka löytyy
			for (valuutta in valuutat) {
				if (typeof summat[valuutta] == 'undefined') summat[valuutta] = 0;
				$('#yhteensa_'+valuutta).html(summat[valuutta].toFixed(2));
			}
		}";

	pupe_DataTables($pupe_DataTables, 8, 8, $footerCallBack);

	echo "<table class='display' id='$pupe_DataTables'>";

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
			<td><input type='text' name='search_tapvm'></td>
			<td><input type='text' name='search_nimi'></td>
			<td><input type='text' name='search_summa'></td>
			<td><input type='text' name='search_valuutta'></td>
			<td><input type='text' name='search_laskunro'></td>
			<td><input type='text' name='search_viite'></td>
			<td><input type='text' name='search_ebid'></td>
			<td><input type='text' name='search_tila'></td>
		</tr>";

	echo "</thead>";
	echo "<tbody>";

	$yhteensa = array();

	while ($trow = mysql_fetch_array($result)) {
		echo "<tr class='aktiivi'>";

		if ($kukarow['taso'] != 1 and $kukarow['taso'] != 2 and $kukarow['taso'] != 3) {
			echo "<td valign='top'>".tv1dateconv($trow["tapvm"])."<br>".tv1dateconv($trow["erpcm"])."</td>";
		}
		else {
			echo "<td valign='top'><a href = 'muutosite.php?tee=E&tunnus=$trow[tunnus]&lopetus=$PHP_SELF////toim=$toim//tee=$tee//summa1=$summa1//summa2=$summa2//alkuvv=$alkuvv//alkukk=$alkukk//alkupp=$alkupp//loppuvv=$loppuvv//loppukk=$loppukk//loppupp=$loppupp'>".tv1dateconv($trow["tapvm"])."</a><br>".tv1dateconv($trow["erpcm"])."</td>";
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