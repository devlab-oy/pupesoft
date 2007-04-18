<?php

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

if ($tee != '') {

	$alku += 0;

	$query = "SELECT tapvm, erpcm, olmapvm, concat_ws(' ', nimi, nimitark) nimi,
			  summa, valkoodi, ebid, tila, alatila, tunnus
			  FROM lasku $index
			  WHERE $ehto and yhtio='$kukarow[yhtio]'
			  ORDER BY $jarj
			  LIMIT $alku, 20";

	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 0) {
		echo "<b>".t("Haulla ei löytynyt yhtään laskua")."</b>";
		exit;
	}

	echo "<table><tr>";
	for ($i = 0; $i < mysql_num_fields($result)-2; $i++) {
		echo "<th>" . t(mysql_field_name($result,$i))."</th>";
	}
	echo "</tr>";

	while ($trow = mysql_fetch_array ($result)) {

			echo "<tr>";

			for ($i=0; $i<mysql_num_fields($result)-2; $i++) {

				if ($i == 6) {
					if (strlen($trow[$i]) > 0) {
						$ebid = $trow[$i];
						require "inc/ebid.inc";
						echo "<td><a href='$url'>".t("Näytä lasku")."</a></td>";
					}
					else {
						echo "<td>".t("Paperilasku")."</td>";
					}
				}
				else {
					if ($i == 0) { // Linkki tositteelle, jos kayttaja saa sita tehda
						if ($kukarow['taso'] != 1 and $kukarow['taso'] != 2) {
							echo "<td>$trow[tapvm]</td>";
						}
						else {
							echo "<td>";
							echo "<a href = 'muutosite.php?tee=E&tunnus=$trow[tunnus]'>";
							echo "$trow[$i]</td>";
						}
					}
					else {
						if ($i == 7) { // Laskun tila
							$laskutyyppi = $trow[$i];
							$alatila     = $trow['alatila'];
							require "inc/laskutyyppi.inc";
								echo "<td>".t("$laskutyyppi")." ".t("$alatila")."</td>";
						}
						else {
							echo "<td>$trow[$i]</td>";
						}
					}
				}
			}
		echo "</tr>";
	}

	echo "</table><br>";

	if ($alku > 0) {
		$siirry = $alku - 20;
		echo "<a href = '$PHP_SELF?toim=laskuhaku&tee=$tee&pvm=$pvm&summa1=$summa1&summa2=$summa2&alku=$siirry&itila=$itila&ialatila=$ialatila'>".t("Edelliset")."</a> ";
	}
	else {
		echo t("Edelliset")." ";
	}
	$siirry = $alku + 20;
	echo "<a href = '$PHP_SELF?toim=laskuhaku&tee=$tee&pvm=$pvm&summa1=$summa1&summa2=$summa2&alku=$siirry&itila=$itila&ialatila=$ialatila'>".t("Seuraavat")."</a> ";
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
			</select></td>
			<td><input type = 'text' name = 'summa1' size=8> - <input type = 'text' name = 'summa2' size=8></td>
			<td><input type = 'submit' value = '".t("Valitse")."'></td>
			</tr></table>

			</form>";
	$formi = 'valinta';
	$kentta = 'summa1';
}

?>
