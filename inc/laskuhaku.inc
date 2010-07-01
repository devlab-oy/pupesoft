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
	$summa1 = str_replace( ",", ".", $summa1);
	$summa2 = str_replace( ",", ".", $summa2);

	if (strlen($summa2) == 0) {
		$summa2 = $summa1;
	}

	$summa1 += 0;
	$summa2 += 0;

	$ehto = "tila in ('H','Y','M','P','Q') and ";
	$index = " use index (yhtio_tila_summa)";

	if ($summa1 == $summa2) {
		$ehto .= "summa = " . $summa1;
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
	$ehto = "tila in ('H','Y','M','P','Q') and asiakkaan_tilausnumero = '$summa1'";
	$jarj = "nimi, summa";
}

if (($tee == 'S' or $tee == 'N' or $tee == 'V' or $tee == 'K' or $tee == 'W' or $tee == 'L') and isset($ehto) and trim($ehto) != '' and is_numeric($alkuvv) and is_numeric($alkukk) and is_numeric($alkupp) and is_numeric($loppuvv) and is_numeric($loppukk) and is_numeric($loppupp)) {
	$ehto .= " and tapvm >= '$alkuvv-$alkukk-$alkupp' and tapvm <= '$loppuvv-$loppukk-$loppupp' ";
}

echo "<br><form name = 'valinta' action = 'raportit.php?selaus=n' method='post'>
		<input type = 'hidden' name = 'toim' value = 'laskuhaku'>
		<table>";

echo "<th valign='top'>".t("Alkukausi")."</th>";
echo "<td><select name='alkuvv'>";

$sel = array();
if (!isset($alkuvv) or $alkuvv == "") $alkuvv = date("Y") - 1;
$sel[$alkuvv] = "SELECTED";

for ($i = date("Y"); $i >= date("Y")-4; $i--) {
	echo "<option value='$i' $sel[$i]>$i</option>";
}

echo "</select>";

$sel = array();
if (!isset($alkukk) or $alkukk == "") $alkukk = date("m");
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

echo "</select></td><td class='back'>&nbsp;</td>";


echo "<tr>
	<th valign='top'>".t("Loppukausi")."</th>
	<td><select name='loppuvv'>";

$sel = array();
if (!isset($loppuvv) or $loppuvv == "") $loppuvv = date("Y");
$sel[$loppuvv] = "SELECTED";

for ($i = date("Y")+1; $i >= date("Y")-4; $i--) {
	echo "<option value='$i' $sel[$i]>$i</option>";
}

echo "</select>";

$sel = array();
if (!isset($loppukk) or $loppukk == "") $loppukk = date("m");
$sel[$loppukk] = "SELECTED";

echo "<select name='loppukk'>";

for ($i = 1; $i < 13; $i++) {
	$val = $i < 10 ? '0'.$i : $i;
	echo "<option value='$val' $sel[$val]>$val</option>";
}

echo "</select>";

$sel = array();
if (!isset($loppupp) or $loppupp == "") $loppupp = date("d", mktime(0, 0, 0, (date("m")+1), 0, date("Y")));
$sel[$loppupp] = "SELECTED";

echo "<select name='loppupp'>";

for ($i = 1; $i < 32; $i++) {
	$val = $i < 10 ? '0'.$i : $i;
	echo "<option value='$val' $sel[$val]>$val</option>";
}

echo "</select></td><td class='back'>&nbsp;</td></tr>";

$sel=array();
$sel[$tee] = "SELECTED";

echo	"<tr><th>".t("Etsi lasku")."</th>
		<td><select name = 'tee'>
		<option value = 'S' $sel[S]>".t("summalla")."
		<option value = 'N' $sel[N]>".t("nimellä")."
		<option value = 'V' $sel[V]>".t("viitteellä")."
		<option value = 'K' $sel[K]>".t("kommentilla")."
		<option value = 'W' $sel[W]>".t("viestillä")."
		<option value = 'L' $sel[L]>".t("Toimittajan laskunumerolla")."
		</select></td>
		<td><input type = 'text' name = 'summa1' value = '$summa1' size=8> - <input type = 'text' name = 'summa2' value = '$summa2' size=8></td>
		<td class='back'><input type = 'submit' value = '".t("Valitse")."'></td>
		</tr></table>
		</form><br><br>";
		
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

	echo "<table><tr>";
	echo "<th>".t("Tapvm/Erpvm")."</th>";
	echo "<th>".t("Nimi")."</th>";
	echo "<th>".t("Summa")."</th>";
	echo "<th>".t("Valuutta")."</th>";
	echo "<th>".t("Laskunro")."</th>";
	echo "<th>".t("Viite/Viesti")."</th>";
	echo "<th>".t("EBID")."</th>";
	echo "<th>".t("Tila/Vienti")."</th>";
	echo "</tr>";

	$yhteensa = 0;

	while ($trow = mysql_fetch_array($result)) {
		echo "<tr class='aktiivi'>";
		
		if ($kukarow['taso'] != 1 and $kukarow['taso'] != 2 and $kukarow['taso'] != 3) {
			echo "<td valign='top'>".tv1dateconv($trow["tapvm"])."<br>".tv1dateconv($trow["erpcm"])."</td>";
		}
		else {
			echo "<td valign='top'><a href = 'muutosite.php?tee=E&tunnus=$trow[tunnus]&lopetus=$PHP_SELF////toim=$toim//tee=$tee//summa1=$summa1//summa2=$summa2'>".tv1dateconv($trow["tapvm"])."</a><br>".tv1dateconv($trow["erpcm"])."</td>";
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
		
		$yhteensa += $trow["summa"];						
	
		echo "</tr>";
	}

	echo "<tr><th colspan='2'>".t("Yhteensä:")."</th><td align='right'>$yhteensa</td></tr>";
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

	$toim="";
}

?>
