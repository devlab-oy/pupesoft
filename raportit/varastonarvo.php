<?php

///* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *///
$useslave = 1;

require ("../inc/parametrit.inc");

echo "<font class='head'>".t("Varastonarvo")."</font><hr>";

// n‰it‰ k‰ytet‰‰n queryss‰
$sel_osasto = "";
$sel_tuoteryhma = "";

echo " <SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">
	<!--

	function toggleAll(toggleBox) {

		var currForm = toggleBox.form;
		var isChecked = toggleBox.checked;
		var nimi = toggleBox.name;

		for (var elementIdx=0; elementIdx<currForm.elements.length; elementIdx++) {
			if (currForm.elements[elementIdx].type == 'checkbox' && currForm.elements[elementIdx].name.substring(0,7) == nimi && currForm.elements[elementIdx].value != '".t("Ei valintaa")."') {
				currForm.elements[elementIdx].checked = isChecked;
			}
		}
	}

	//-->
	</script>";

// piirrell‰‰n formi
echo "<form action='$PHP_SELF' name='formi' method='post' autocomplete='OFF'>";

if ($valitaan_useita == "") {

	if ($sel_tuoteryhma == "" and $sel_osasto == "" and $valitse_yksittain != '') {
		echo "<font class='error'>".t("Valitse osasto tai tuoteryhm‰!!!")."</font>";
	}

	echo "<table>";

	// n‰ytet‰‰n soveltuvat osastot
	$query = "SELECT avainsana.selite, ".avain('select')." FROM avainsana ".avain('join','OSASTO_')." WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='OSASTO' order by avainsana.selite+0";
	$res2  = mysql_query($query) or die($query);

	$sel = "";
	if ($osasto == "kaikki") $sel = "selected";

	echo "<tr><th>Osasto:</th><td>";
	echo "<select name='osasto'>";
	echo "<option value=''>Valitse osasto</option>";
	echo "<option value='kaikki' $sel>N‰yt‰ kaikki</option>";

	while ($rivi = mysql_fetch_array($res2)) {
		$sel = "";
		if ($osasto == $rivi["selite"]) {
			$sel = "selected";
			$sel_osasto = $rivi["selite"];
		}
		echo "<option value='$rivi[selite]' $sel>$rivi[selite] - $rivi[selitetark]</option>";
	}

	echo "</select></td></tr>";

	$trylisa = "";
	$sort_osastot = "";
	if ($osasto != "kaikki" and $sel_osasto != "") {
		$trylisa = " and osasto='$sel_osasto' ";
		$sort_osastot = "&osasto=$sel_osasto";
	}

	// n‰ytet‰‰n soveltuvat tuoteryhm‰t
	$query = "SELECT avainsana.selite, ".avain('select')." FROM avainsana ".avain('join','TRY_')." WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='TRY' order by avainsana.selite+0";
	$res2   = mysql_query($query) or die($query);

	echo "<tr><th>Tuoteryhm‰:</th><td>";
	echo "<select name='tuoteryhma'>";
	echo "<option value=''>Valitse tuoteryhm‰</option>";

	$sel = "";
	if ($tuoteryhma == "kaikki") $sel = "selected";
	echo "<option value='kaikki' $sel>N‰yt‰ kaikki</option>";

	while ($rivi = mysql_fetch_array($res2)) {
		$sel = "";
		if ($tuoteryhma == $rivi["selite"]) {
			$sel = "selected";
			$sel_tuoteryhma = $rivi["selite"];
		}

		echo "<option value='$rivi[selite]' $sel>$rivi[selite] - $rivi[selitetark]</option>";
	}

	echo "</select></td>";

	$sort_tryt = "";
	if ($tuoteryhma != "kaikki" and $sel_tuoteryhma != "") {
		$sort_tryt = "&tuoteryhma=$sel_tuoteryhma";
	}

	echo "<td class='back'><input type='submit' name='valitaan_useita' value='Valitse useita'></td></tr>";
	echo "</table>";
}
else {
	if ($mul_osasto == "") {

		echo "<table><tr><td valign='top' class='back'>";

		// n‰ytet‰‰n soveltuvat osastot
		$query = "SELECT avainsana.selite, ".avain('select')." FROM avainsana ".avain('join','OSASTO_')." WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='OSASTO' order by avainsana.selite+0";
		$res2  = mysql_query($query) or die($query);

		if (mysql_num_rows($res2) > 11) {
			echo "<div style='height:265;overflow:auto;'>";
		}

		echo "<table>";
		echo "<tr><th colspan='2'>Valitse osasto(t):</th></tr>";

		echo "<tr><td><input type='checkbox' name='mul_osasto[]' value='".t("Ei valintaa")."'></td><td>".t("Ei valintaa")."</td></tr>";
		echo "<tr><td><input type='checkbox' name='mul_osa' onclick='toggleAll(this);'></td><td>".t("Ruksaa kaikki")."</td></tr>";


		while ($rivi = mysql_fetch_array($res2)) {
			echo "<tr><td><input type='checkbox' name='mul_osasto[]' value='$rivi[selite]'></td><td>$rivi[selite] - $rivi[selitetark]</td></tr>";
		}

		echo "</table>";

		if (mysql_num_rows($res2) > 11) {
			echo "</div>";
		}

		echo "<br>";
		echo "<input type='submit' name='valitaan_useita' value='Jatka'>";

		echo "</td><td valign='top' class='back'><input type='submit' name='dummy' value='Valitse yksitt‰in'></td></tr></table>";

	}
	elseif ($mul_try == "") {

		echo "<table><tr><td valign='top' class='back'>";

		if (count($mul_osasto) > 11) {
			echo "<div style='height:265;overflow:auto;'>";
		}

		echo "<table>";
		echo "<tr><th>Osasto(t):</th></tr>";

		$osastot = "";
		foreach ($mul_osasto as $kala) {
			echo "<input type='hidden' name='mul_osasto[]' value='$kala'>";

			$query = "SELECT avainsana.selite, ".avain('select')." FROM avainsana ".avain('join','OSASTO_')." WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='OSASTO' and avainsana.selite='$kala'";
			$res3   = mysql_query($query) or die($query);
			$selrow = mysql_fetch_array($res3);

			echo "<tr><td>$kala - $selrow[selitetark]</td></tr>";
			$osastot .= "'$kala',";
		}
		$osastot = substr($osastot,0,-1);

		echo "</table>";

		if (count($mul_osasto) > 11) {
			echo "</div>";
		}

		echo "</td><td valign='top' class='back'>";

		// n‰ytet‰‰n soveltuvat osastot
		$query = "SELECT avainsana.selite, ".avain('select')." FROM avainsana ".avain('join','TRY_')." WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='TRY' order by avainsana.selite+0";
		$res2  = mysql_query($query) or die($query);

		if (mysql_num_rows($res2) > 11) {
			echo "<div style='height:265;overflow:auto;'>";
		}

		echo "<table>";
		echo "<tr><th colspan='2'>Valitse tuoteryhm‰(t):</th></tr>";

		echo "<tr><td><input type='checkbox' name='mul_try[]' value='".t("Ei valintaa")."'></td><td>".t("Ei valintaa")."</td></tr>";
		echo "<tr><td><input type='checkbox' name='mul_try' onclick='toggleAll(this);'></td><td>Ruksaa kaikki</td></tr>";

		while ($rivi = mysql_fetch_array($res2)) {
			echo "<tr><td><input type='checkbox' name='mul_try[]' value='$rivi[selite]'></td><td>$rivi[selite] - $rivi[selitetark]</td></tr>";
		}

		echo "</table>";

		if (mysql_num_rows($res2) > 11) {
			echo "</div>";
		}

		echo "<br>";
		echo "<input type='submit' name='valitaan_useita' value='Jatka'>";

		echo "</td><td valign='top' class='back'><input type='submit' name='dummy' value='Valitse yksitt‰in'></td></tr></table>";


	}
	else {

		echo "<table><tr><td valign='top' class='back'>";

		if (count($mul_osasto) > 11) {
			echo "<div style='height:265;overflow:auto;'>";
		}

		echo "<table>";
		echo "<tr><th>Osasto(t):</th></tr>";

		$osastot = "";
		$sort_osastot = "";

		foreach ($mul_osasto as $kala) {
			echo "<input type='hidden' name='mul_osasto[]' value='$kala'>";

			$query = "SELECT avainsana.selite, ".avain('select')." FROM avainsana ".avain('join','OSASTO_')." WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='OSASTO' and avainsana.selite='$kala'";
			$res3   = mysql_query($query) or die($query);
			$selrow = mysql_fetch_array($res3);

			echo "<tr><td>$kala - $selrow[selitetark]</td></tr>";
			$osastot .= "'$kala',";
			$sort_osastot .= "&mul_osasto[]=$kala";
		}
		$osastot = substr($osastot,0,-2); // vika pilkku ja vika hipsu pois
		$osastot = substr($osastot, 1);   // eka hipsu pois

		echo "</table>";

		if (count($mul_osasto) > 11) {
			echo "</div>";
		}

		echo "</td><td valign='top' class='back'>";

		if (count($mul_try) > 11) {
			echo "<div style='height:265;overflow:auto;'>";
		}

		echo "<table>";
		echo "<tr><th colspan='2'>Tuoteryhm‰(t):</th></tr>";

		$tryt = "";
		$sort_tryt = "";

		foreach ($mul_try as $kala) {
			echo "<input type='hidden' name='mul_try[]' value='$kala'>";

			$query = "SELECT avainsana.selite, ".avain('select')." FROM avainsana ".avain('join','TRY_')." WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='TRY' and avainsana.selite='$kala'";
			$res3   = mysql_query($query) or die($query);
			$selrow = mysql_fetch_array($res3);

			echo "<tr><td>$kala - $selrow[selitetark]</td></tr>";
			$tryt .= "'$kala',";
			$sort_tryt .= "&mul_try[]=$kala";
		}
		$tryt = substr($tryt,0,-2);  // vika pilkku ja vika hipsu pois
		$tryt = substr($tryt, 1);    // eka hipsu pois

		$sel_osasto = $osastot;
		$sel_tuoteryhma = $tryt;

		echo "</table>";

		if (count($mul_try) > 11) {
			echo "</div>";
		}

		echo "<br>";

		echo "</td><td valign='top' class='back'>";

		echo "<input type='submit' name='valitaan_useita' value='Valitse useita'>";
		echo "<input type='submit' name='dummy' value='Valitse yksitt‰in'>";

		echo "</td></tr></table>";
	}
}

echo "<br><table>";

if ($merkki != '') {
	$chk = 'checked';
}
else {
	$chk = '';
}

echo "<tr>";
echo "<th>".t("Aja tuotemerkeitt‰in").":</th><td><input type='checkbox' name='merkki' $chk></td>";
echo "</tr>";

if ($varastot != '') {
	$chk = 'checked';
}
else {
	$chk = '';
}

echo "<tr><th valign=top>" . t('Varastot') . "<br /><br /><span style='font-size: 0.8em;'>"
	. t('Saat kaikki varastot jos et valitse yht‰‰n')
	. "</span></th>
    <td>";

$varastot = (isset($_POST['varastot']) && is_array($_POST['varastot'])) ? $_POST['varastot'] : array();

$query  = "SELECT tunnus, nimitys FROM varastopaikat WHERE yhtio='$kukarow[yhtio]'";
$vares = mysql_query($query) or pupe_error($query);

while ($varow = mysql_fetch_array($vares)) {
	$sel = '';
	if (in_array($varow['tunnus'], $varastot)) {
		$sel = 'checked';
	}

	echo "<input type='checkbox' name='varastot[]' value='{$varow['tunnus']}' $sel/>{$varow['nimitys']}<br />\n";
}

echo "</td></tr>";

/*
echo "<tr>";
echo "<th>".t("Aja varastoittain").":</th><td><input type='checkbox' name='varastomukaan' $chk></td>";
echo "</tr>";
*/

echo "</table>";
echo "<br>";

if($valitaan_useita == '') {
	echo "<input type='submit' name='valitse_yksittain' value='Laske varastonarvot'>";
}
else {
	echo "<input type='submit' name='valitaan_useita' value='Laske varastonarvot'>";
}

echo "</form><br><br>";

if ($sel_tuoteryhma != "" or $sel_osasto != "" or $osasto == "kaikki" or $tuoteryhma == "kaikki") {

	$trylisa 		= "";
	$merkkilisa1	= "";
	$merkkilisa2	= "";
	$varastojoini   = "";
	$varastosumma	= 0;
	$groupby = "GROUP BY ";
	$orderby = "ORDER BY ";

	if ($tuoteryhma != "kaikki" and $sel_tuoteryhma != "" and $sel_tuoteryhma != t("Ei valintaa")) {
		$trylisa .= " and tuote.try in ('$sel_tuoteryhma') ";
	}
	if ($osasto != "kaikki" and $sel_osasto != "" and $sel_osasto != t("Ei valintaa")) {
		$trylisa .= " and tuote.osasto in ('$sel_osasto') ";
	}

	if ($merkki != '') {
		$merkkilisa1 = "tuote.tuotemerkki,";
		$groupby .= "tuote.tuotemerkki";
		$orderby .= "tuote.tuotemerkki";
	}

	if ($varastot != '' && count($varastot) > 0) {
		
		$varasto = "";

		$varasto = "AND varastopaikat.tunnus IN (";
		
		// loopataan varastot
		foreach ($varastot as $var) {
			if (end($varastot) != $var) {
				$varasto .= "$var,";
			}
			else {
				$varasto .= "$var";
			}
		}

		$varasto .= ")";
		
		$merkkilisa1 .= "varastopaikat.nimitys,";
		
		if ($merkki != '') {
			$groupby .= ",varastopaikat.nimitys";
			$orderby .= ",varastopaikat.nimitys";
		}
		else {			
			$groupby .= "varastopaikat.nimitys";
			$orderby .= "varastopaikat.nimitys";
		}
		$varastojoini = "JOIN varastopaikat ON (varastopaikat.yhtio=tuotepaikat.yhtio $varasto AND
			concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0')) AND
			concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0')))";
	}
	else {
		$varastojoini = "";
	}
	
	if ($merkki == '' && $varasto == '' && count($varastot) == 0) {
		$groupby = "";
		$orderby = "";
	}

	// kuus kuukautta taaksepp‰in kuun eka p‰iv‰
	$kausi = date("Y-m-d", mktime(0, 0, 0, date("m")-12, 1, date("Y")));

	// Varaston arvo
	$query = "	SELECT
				$merkkilisa1
				sum(
					if(	tuote.sarjanumeroseuranta = 'S', 
						(	SELECT tuotepaikat.saldo*if(tuote.epakurantti75pvm='0000-00-00', if(tuote.epakurantti50pvm='0000-00-00', if(tuote.epakurantti25pvm='0000-00-00', avg(tilausrivi_osto.rivihinta/tilausrivi_osto.kpl), avg(tilausrivi_osto.rivihinta/tilausrivi_osto.kpl)*0.75), avg(tilausrivi_osto.rivihinta/tilausrivi_osto.kpl)*0.5), avg(tilausrivi_osto.rivihinta/tilausrivi_osto.kpl)*0.25)
							FROM sarjanumeroseuranta
							LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
							LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
							WHERE sarjanumeroseuranta.yhtio = tuotepaikat.yhtio 
							and sarjanumeroseuranta.tuoteno = tuotepaikat.tuoteno
							and sarjanumeroseuranta.myyntirivitunnus != -1
							and (tilausrivi_myynti.tunnus is null or tilausrivi_myynti.laskutettuaika = '0000-00-00')
							and tilausrivi_osto.laskutettuaika != '0000-00-00'
						), 
						tuotepaikat.saldo*if(tuote.epakurantti75pvm='0000-00-00', if(tuote.epakurantti50pvm='0000-00-00', if(tuote.epakurantti25pvm='0000-00-00', tuote.kehahin, tuote.kehahin*0.75), tuote.kehahin*0.5), tuote.kehahin*0.25)
					)
				) varasto
				FROM tuotepaikat
				JOIN tuote ON (tuote.tuoteno = tuotepaikat.tuoteno and tuote.yhtio = tuotepaikat.yhtio and tuote.ei_saldoa = '' and tuote.epakurantti100pvm = '0000-00-00' $trylisa)
				$varastojoini
				WHERE tuotepaikat.yhtio = '$kukarow[yhtio]'
				and tuotepaikat.saldo <> 0
				$groupby
				$orderby";
	$result = mysql_query($query) or pupe_error($query);

	echo "<table>";

	echo "<tr>";

	if ($merkki != '') {
		echo "<th>".t("Tuotemerkki")."</th>";
	}

	if ($varastot != '' && count($varastot) > 0) {
		echo "<th>".t("Varasto")."</th>";
	}

	echo "<th>".t("Pvm")."</th><th>".t("Varastonarvo")."</th></tr>";
	$varvo = 0;

	while ($row = mysql_fetch_array($result)) {
		$varvo  = $row["varasto"];

		echo "<tr>";

		if ($merkki != '' and $varastot != '' and count($varastot) > 0) {
			echo "<td>$row[0]</td>";
			echo "<td>$row[1]</td>";
			$varastosumma += $row["varasto"];
		}
		else if ($merkki != '' or count($varastot) > 0) {
			echo "<td>$row[0]</td>";
			$varastosumma += $row["varasto"];
		}

		echo "<td>".date("Y-m-d")."</td><td align='right'>".str_replace(".",",",sprintf("%.2f",$varvo))."</td></tr>";

		// jos on lis‰rajauksia ei tehd‰ historiaa
		if ($merkkilisa1 == "") {

			// tuotteen varastonarvon muutos
			$query  = "	SELECT date_format(laadittu, '%Y-%m') kausi, sum(kpl*hinta) muutos, date_format(laadittu, '%Y') yy, date_format(laadittu, '%m') mm
						FROM tuote use index (osasto_try_index)
						JOIN tapahtuma use index (yhtio_tuote_laadittu)
						ON tapahtuma.yhtio = tuote.yhtio
						and tapahtuma.tuoteno = tuote.tuoteno
						and tapahtuma.laadittu > '$kausi 00:00:00'
						WHERE tuote.yhtio = '$kukarow[yhtio]'
						and tuote.ei_saldoa = ''
						$trylisa
						group by kausi
						order by kausi desc";
			$xresult = mysql_query($query) or pupe_error($query);

			while ($xrow = mysql_fetch_array($xresult)) {				
				$varvo = $varvo - $xrow["muutos"];
				$apukausi = date("Y-m-d", mktime(0, 0, 0, $xrow["mm"], 0, $xrow["yy"]));

				echo "<tr>";

				if ($merkki != '') {
					echo "<td>$row[merkki]</td>";
				}

				echo "<td>$apukausi</td><td align='right'>".str_replace(".",",",sprintf("%.2f",$varvo))."</td></tr>";
			}

		}
	}
	
	if ($varastosumma != 0) {
		echo "<tr>";
		echo "<th colspan='";
		
		if ($merkki != '' and count($varastot) > 0) {
			echo 3;
		}
		else {
			echo 2;
		}
		
		echo "'>".t("Yhteens‰")."</th>";
		echo "<th>".str_replace(".",",",sprintf("%.2f",$varastosumma))."</th>";
		echo "</tr>";
	}

	echo "</table>";
}

require ("../inc/footer.inc");

?>