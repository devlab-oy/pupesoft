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
			if (currForm.elements[elementIdx].type == 'checkbox' && currForm.elements[elementIdx].name.substring(0,7) == nimi) {
				currForm.elements[elementIdx].checked = isChecked;
			}
		}
	}

	//-->
	</script>";

// piirrell‰‰n formi
echo "<form action='$PHP_SELF' name='formi' method='post' autocomplete='OFF'>";

if ($valitaan_useita == "") {
	
	echo "<table>";
	
	// n‰ytet‰‰n soveltuvat osastot
	$query = "SELECT * FROM avainsana WHERE yhtio='$kukarow[yhtio]' and laji='OSASTO' order by selite+0";
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
	$query = "SELECT * FROM avainsana WHERE yhtio='$kukarow[yhtio]' and laji='TRY' order by selite+0";
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
		$query = "SELECT * FROM avainsana WHERE yhtio='$kukarow[yhtio]' and laji='OSASTO' order by selite+0";
		$res2  = mysql_query($query) or die($query);
		
		if (mysql_num_rows($res2) > 11) {
			echo "<div style='height:265;overflow:auto;'>";
		}
	
		echo "<table>";
		echo "<tr><th colspan='2'>Valitse osasto(t):</th></tr>";
		echo "<tr><td><input type='checkbox' name='mul_osa' onclick='toggleAll(this);'></td><td>Ruksaa kaikki</td></tr>";
		
		
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

			$query = "SELECT * FROM avainsana WHERE yhtio='$kukarow[yhtio]' and laji='OSASTO' and selite='$kala'";
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
		$query = "SELECT * FROM avainsana WHERE yhtio='$kukarow[yhtio]' and laji='TRY' order by selite+0";
		$res2  = mysql_query($query) or die($query);
		
		if (mysql_num_rows($res2) > 11) {
			echo "<div style='height:265;overflow:auto;'>";
		}
		
		echo "<table>";
		echo "<tr><th colspan='2'>Valitse tuoteryhm‰(t):</th></tr>";
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
			
			$query = "SELECT * FROM avainsana WHERE yhtio='$kukarow[yhtio]' and laji='OSASTO' and selite='$kala'";
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
			
			$query = "SELECT * FROM avainsana WHERE yhtio='$kukarow[yhtio]' and laji='TRY' and selite='$kala'";
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

echo "<table>";

if ($merkki != '') {
	$chk = 'checked';
}
else {
	$chk = '';	
}

echo "<tr>";
echo "<th>".t("Aja tuotemerkeitt‰in").":</th><td><input type='checkbox' name='merkki' $chk></td>";
echo "</tr>";


echo "</table>";
echo "<br>";

if($valitaan_useita == '') {
	echo "<input type='submit' value='Laske varastonarvot'>";
}
else {
	echo "<input type='submit' name='valitaan_useita' value='Laske varastonarvot'>";
}

echo "</form>";

if ($sel_tuoteryhma != "" or $sel_osasto != "" or $osasto == "kaikki" or $tuoteryhma == "kaikki") {

	$trylisa 		= "";
	$merkkilisa1	= "";
	$merkkilisa2	= "";

	if ($tuoteryhma != "kaikki" and $sel_tuoteryhma != "") {
		$trylisa .= " and tuote.try in ('$sel_tuoteryhma') ";
	}
	if ($osasto != "kaikki" and $sel_osasto != "") {
		$trylisa .= " and tuote.osasto in ('$sel_osasto') ";
	}


	if ($merkki != '') {
		$merkkilisa1 = "tuote.tuotemerkki,";
		$merkkilisa2 = " GROUP BY tuote.tuotemerkki ORDER BY tuote.tuotemerkki";
	}

	// kuus kuukautta taaksepp‰in kuun eka p‰iv‰
	$kausi = date("Y-m-d", mktime(0, 0, 0, date("m")-12, 1, date("Y")));
	
	//varaston arvo
	$query = "	SELECT
				$merkkilisa1
				sum(
					if(	tuote.sarjanumeroseuranta = '', tuotepaikat.saldo*if(tuote.epakurantti1pvm='0000-00-00', tuote.kehahin, tuote.kehahin/2), 
						(	SELECT tuotepaikat.saldo*if(tuote.epakurantti1pvm='0000-00-00', avg(tilausrivi_osto.rivihinta/tilausrivi_osto.kpl), avg(tilausrivi_osto.rivihinta/tilausrivi_osto.kpl)/2)
							FROM sarjanumeroseuranta
							LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
							LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
							LEFT JOIN lasku lasku_myynti use index (PRIMARY) ON lasku_myynti.yhtio=sarjanumeroseuranta.yhtio and lasku_myynti.tunnus=tilausrivi_myynti.otunnus
							LEFT JOIN lasku lasku_osto   use index (PRIMARY) ON lasku_osto.yhtio=sarjanumeroseuranta.yhtio and lasku_osto.tunnus=tilausrivi_osto.uusiotunnus
							WHERE sarjanumeroseuranta.yhtio = tuotepaikat.yhtio and sarjanumeroseuranta.tuoteno = tuotepaikat.tuoteno
							and (tilausrivi_myynti.tunnus is null or (lasku_myynti.tila in ('N','L') and lasku_myynti.alatila != 'X'))
							and (lasku_osto.tila='U' or (lasku_osto.tila='K' and lasku_osto.alatila='X'))
						)
					)
				) varasto
				FROM tuotepaikat, tuote
				WHERE tuote.tuoteno 	  = tuotepaikat.tuoteno
				and tuote.yhtio 		  = tuotepaikat.yhtio
				and tuotepaikat.yhtio 	  = '$kukarow[yhtio]'
				and tuote.ei_saldoa 	  = ''
				and tuotepaikat.saldo    <> 0
				and tuote.epakurantti2pvm = '0000-00-00'
				$trylisa
				$merkkilisa2";
	$result = mysql_query($query) or pupe_error($query);
	
	echo "<table>";
	
	echo "<tr>";
	
	if ($merkki != '') {
		echo "<th>".t("Tuotemerkki")."</th>";
	}
	
	echo "<th>".t("Pvm")."</th><th>".t("Varastonarvo")."</th><th>".t("Tarkkuus")."</th></tr>";
	$varvo = 0;
		
	while ($row = mysql_fetch_array($result)) {
	
		$varvo  = $row["varasto"];

		echo "<tr>";
		
		if ($merkki != '') {
			echo "<td>$row[merkki]</td>";
		}
		
		echo "<td>".date("Y-m-d")."</td><td align='right'>".str_replace(".",",",sprintf("%.2f",$varvo))."</td><td>virallinen</td></tr>";
		
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
			
			echo "<td>$apukausi</td><td align='right'>".str_replace(".",",",sprintf("%.2f",$varvo))."</td><td>arvio</td></tr>";
		}
	}

	echo "</table>";
}

require ("../inc/footer.inc");

?>