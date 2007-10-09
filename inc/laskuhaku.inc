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

if ($tee != '') {

	$alku += 0;

	$query = "	SELECT tapvm, erpcm, concat_ws('<br>', nimi, nimitark) nimi, summa, valkoodi, vienti, concat_ws(' ', viite, viesti) 'viite/viesti', ebid, tila, alatila, tunnus
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
			echo "<td valign='top'><a href = 'muutosite.php?tee=E&tunnus=$trow[tunnus]'>".tv1dateconv($trow["tapvm"])."</a><br>".tv1dateconv($trow["erpcm"])."</td>";
		}
		
		echo "<td valign='top'>$trow[nimi]</td>";
		echo "<td valign='top' align='right'>$trow[summa]</td>";
		echo "<td valign='top'>$trow[valkoodi]</td>";
		echo "<td valign='top'>".$trow["viite/viesti"]."</td>";
		
		// tehdään lasku linkki
		echo "<td valign='top'>".ebid($trow['tunnus']) ."</td>";
		
		$laskutyyppi = $trow["tila"];
		require "inc/laskutyyppi.inc";
		echo "<td valign='top'>$laskutyyppi<br>".ostolaskun_vienti($trow["vienti"])."</td>";
		
		$yhteensa += $trow["summa"];						
	
		echo "</tr>";
	}

	echo "<tr><th colspan='2'>".t("Yhteensä:")."</th><td align='right'>$yhteensa</td></tr>";
	echo "</table><br>";

	if ($alku > 0) {
		$siirry = $alku - 50;
		echo "<a href = '$PHP_SELF?toim=laskuhaku&tee=$tee&pvm=$pvm&summa1=$summa1&summa2=$summa2&alku=$siirry&itila=$itila&ialatila=$ialatila'>".t("Edelliset")."</a> ";
	}
	else {
		echo t("Edelliset")." ";
	}
	
	if ($seraavako >= 50) {
		$siirry = $alku + 50;
		echo "<a href = '$PHP_SELF?toim=laskuhaku&tee=$tee&pvm=$pvm&summa1=$summa1&summa2=$summa2&alku=$siirry&itila=$itila&ialatila=$ialatila'>".t("Seuraavat")."</a> ";
	}
	echo "<br><br>";

	$toim="";
}

if ($tee == '') {
	echo "<form name = 'valinta' action = 'raportit.php?selaus=n' method='post'>
			<input type = 'hidden' name = 'toim' value = 'laskuhaku'>

			<table><tr>
			<td>".t("Valitse lasku")."</td>
			<td><select name = 'tee'>
			<option value = 'S'>".t("summalla")."
			<option value = 'N'>".t("nimellä")."
			<option value = 'V'>".t("viitteellä")."
			<option value = 'K'>".t("kommentilla")."
			<option value = 'W'>".t("viestillä")."			
			</select></td>
			<td><input type = 'text' name = 'summa1' size=8> - <input type = 'text' name = 'summa2' size=8></td>
			<td><input type = 'submit' value = '".t("Valitse")."'></td>
			</tr></table>

			</form>";
	$formi = 'valinta';
	$kentta = 'summa1';
}

?>
