<?php

if ($tee == 'Q') { // Q = laskuja tilansa mukaan
	$ehto = "tila = '$itila' and alatila = '$ialatila'";
	$jarj = "tapvm";
}if ($tee == 'E') { // E = erääntyviä laskuja pvm:n mukaan
	$ehto = "tila in ('H','Y','M','P','Q') and erpcm='$pvm'";
	$jarj = "summa";
}
if ($tee == 'M') { // M = maksettavia laskuja pvm:n mukaan
	$ehto = "tila in ('H','Y','M','P','Q') and olmapvm='$pvm'";
	$jarj = "summa";
}
if ($tee == 'S') { // S = Etsitään summaa laskulta
	$summa1 = str_replace( ",", ".", $summa1);
	$summa2 = str_replace( ",", ".", $summa2);
	if (strlen($summa2) == 0) {
		$summa2 = $summa1;
	}
	$summa1 += 0;
	$summa2 += 0;

	$ehto = "tila in ('H','Y','M','P','Q') and ";
	$index = "";

	if ($summa1 == $summa2) {
		$index = " use index (yhtio_tila_summa)";
		$ehto .= "summa = " . $summa1;
		$jarj = "tapvm desc";
	}
	else {
		$index = " use index (yhtio_tila_summa)";
		$ehto .= "summa >= " . $summa1 . " and summa <= " . $summa2;
		$jarj = "summa, tapvm";
	}
}
if ($tee == 'N') { // N = Etsitään nimeä laskulta
	$ehto = "tila in ('H','Y','M','P','Q') and nimi like '%".$summa1."%'";
	$jarj = "nimi, tapvm desc";
}

if ($tee == 'V') { // V = Etsitään viitettä laskulta
	$ehto = "tila in ('H','Y','M','P','Q') and viite like '%$summa1%'";
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

	while ($trow=mysql_fetch_array ($result)) {
			echo "<tr>";
			for ($i=0; $i<mysql_num_fields($result)-2; $i++) {
			if ($i == 6) {
				if (strlen($trow[$i]) > 0) {
					$ebid = $trow[$i];
					require "inc/ebid.inc";
					echo "<td><a href='$url'>
						".t("Näytä lasku")."</a></td>";
				}
				else {
						echo "<td>".t("Paperilasku")."</td>";
				}
			}
			else {
				if ($i == 0) { // Linkki tositteelle, jos kayttaja saa sita tehda
					if ($kukarow['taso'] < 2) {
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
		echo "".t("Edelliset")." ";
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
			</select></td>
			<td><input type = 'text' name = 'summa1' size=8> - <input type = 'text' name = 'summa2' size=8></td>
			<td><input type = 'submit' value = '".t("Valitse")."'></td>
			</tr></table>

			</form>";
	$formi = 'valinta';
	$kentta = 'summa1';
}

?>
