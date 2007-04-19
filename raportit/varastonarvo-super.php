<?php

// käytetään slavea jos sellanen on
$useslave = 1;

require ("../inc/parametrit.inc");

echo "<font class='head'>".t("Varastonarvo tuotteittain")."</font><hr>";

if (!isset($pp)) $pp = date("d");
if (!isset($kk)) $kk = date("m");
if (!isset($vv)) $vv = date("Y");

// tutkaillaan saadut muuttujat
$osasto = trim($osasto);
$try    = trim($try);
$pp 	= sprintf("%02d", trim($pp));
$kk 	= sprintf("%02d", trim($kk));
$vv 	= sprintf("%04d", trim($vv));

// härski oikeellisuustzekki
if ($pp == "00" or $kk == "00" or $vv == "0000") $tee = $pp = $kk = $vv = "";

// näitä käytetään queryssä
$sel_osasto = "";
$sel_tuoteryhma = "";

echo " <SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">
	<!--

	function toggleAll(toggleBox) {

		var currForm = toggleBox.form;
		var isChecked = toggleBox.checked;
		var nimi = toggleBox.name;

		for (var elementIdx=1; elementIdx<currForm.elements.length; elementIdx++) {
			if (currForm.elements[elementIdx].type == 'checkbox' && currForm.elements[elementIdx].name.substring(0,7) == nimi && currForm.elements[elementIdx].value != '".t("Ei valintaa")."') {
				currForm.elements[elementIdx].checked = isChecked;
			}
		}
	}

	//-->
	</script>";

// piirrellään formi
echo "<form action='$PHP_SELF' name='formi' method='post' autocomplete='OFF'>";

if ($valitaan_useita == "") {
	
	echo "<table>";
	
	// näytetään soveltuvat osastot
	$query = "SELECT avainsana.selite, ".avain('select')." FROM avainsana ".avain('join','OSASTO_')." WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='OSASTO' order by avainsana.selite+0";
	$res2  = mysql_query($query) or die($query);

	$sel = "";
	$seltyhjat = "";
	if ($osasto == "kaikki") $sel = "selected";
	if ($osasto == "tyhjat") $seltyhjat = "selected";
	
	echo "<tr><th>Osasto:</th><td>";
	echo "<select name='osasto'>";
	echo "<option value=''>Valitse osasto</option>";
	echo "<option value='kaikki' $sel>Näytä kaikki</option>";
	echo "<option value='tyhjat' $seltyhjat>Osasto puuttuu</option>";

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

	// näytetään soveltuvat tuoteryhmät
	$query = "SELECT avainsana.selite, ".avain('select')." FROM avainsana ".avain('join','TRY_')." WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='TRY' order by avainsana.selite+0";
	$res2   = mysql_query($query) or die($query);

	echo "<tr><th>Tuoteryhmä:</th><td>";
	echo "<select name='tuoteryhma'>";
	echo "<option value=''>Valitse tuoteryhmä</option>";

	$sel = "";
	$seltyhjat = "";
	if ($tuoteryhma == "kaikki") $sel = "selected";
	if ($tuoteryhma == "tyhjat") $seltyhjat = "selected";
	echo "<option value='kaikki' $sel>Näytä kaikki</option>";
	echo "<option value='tyhjat' $seltyhjat>Tuoteryhmä puuttuu</option>";

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
		
		// näytetään soveltuvat osastot
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

		echo "</td><td valign='top' class='back'><input type='submit' name='dummy' value='Valitse yksittäin'></td></tr></table>";

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
		
		// näytetään soveltuvat osastot
		$query = "SELECT avainsana.selite, ".avain('select')." FROM avainsana ".avain('join','TRY_')." WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='TRY' order by avainsana.selite+0";
		$res2  = mysql_query($query) or die($query);
		
		if (mysql_num_rows($res2) > 11) {
			echo "<div style='height:265;overflow:auto;'>";
		}
		
		echo "<table>";
		echo "<tr><th colspan='2'>Valitse tuoteryhmä(t):</th></tr>";
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

		echo "</td><td valign='top' class='back'><input type='submit' name='dummy' value='Valitse yksittäin'></td></tr></table>";


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
		echo "<tr><th colspan='2'>Tuoteryhmä(t):</th></tr>";

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
		echo "<input type='submit' name='dummy' value='Valitse yksittäin'>";

		echo "</td></tr></table>";
	}
}

echo "<br><table>";
echo "<tr>";
echo "<th>Syötä vvvv-kk-pp:</th>";
echo "<td colspan='2'><input type='text' name='vv' size='7' value='$vv'><input type='text' name='kk' size='5' value='$kk'><input type='text' name='pp' size='5' value='$pp'></td>";
echo "</tr>";

echo "<tr>";
echo "<th>Näytetäänkö tuotteet:</th>";

if ($naytarivit != '') {
	$chk = "CHECKED";
}
else {
	$chk = "";
}

echo "<td colspan='2'><input type='checkbox' name='naytarivit' $chk> (Listaus lähetetään sähköpostiisi)</td>";
echo "</tr>";

echo "<tr>";
echo "<th>Tyyppi:</th>";

$sel1 = "";
$sel2 = "";
$sel3 = "";

if ($tyyppi == "A") {
	$sel1 = "SELECTED";
}
elseif($tyyppi == "B") {
	$sel2 = "SELECTED";
}
elseif($tyyppi == "C") {
	$sel3 = "SELECTED";
}


echo "<td>
		<select name='tyyppi'>
		<option value='A' $sel1>".t("Näytetään tuotteet joilla on saldoa")."</option>
		<option value='B' $sel2>".t("Näytetään tuotteet joilla ei ole saldoa")."</option>
		<option value='C' $sel3>".t("Näytetään tuotteet joilla on saldoa, mutta ei arvoa")."</option>
		</select>
		</td>";

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


if ($sel_tuoteryhma != "" or $sel_osasto != "" or $osasto == "kaikki" or $tuoteryhma == "kaikki" or $osasto == "tyhjat" or $tuoteryhma == "tyhjat") {

	$trylisa = "";

	if ($tuoteryhma != "kaikki" and $sel_tuoteryhma != "" and $sel_tuoteryhma != t("Ei valintaa")) {
		$trylisa .= " and try in ('$sel_tuoteryhma') ";
	}
	if ($osasto != "kaikki" and $sel_osasto != "" and $sel_osasto != t("Ei valintaa")) {
		$trylisa .= " and osasto in ('$sel_osasto') ";
	}
	if ($tuoteryhma == "tyhjat") {
		$trylisa .= " and (try = 0 or try is null) ";
	}
	if ($osasto == "tyhjat") {
		$trylisa .= " and (osasto = 0 or osasto is null) ";
	}
		
	// haetaan halutut tuotteet
	$query  = "	SELECT tuote.tuoteno, 
				atry.selite try, 
				aosa.selite osasto, 
				tuote.nimitys, tuote.kehahin, tuote.epakurantti1pvm, tuote.epakurantti2pvm, tuote.sarjanumeroseuranta
				FROM tuote
				LEFT JOIN avainsana atry on atry.yhtio=tuote.yhtio and atry.selite=tuote.try and atry.laji='TRY'
				LEFT JOIN avainsana aosa on aosa.yhtio=tuote.yhtio and aosa.selite=tuote.try and aosa.laji='OSASTO'
				WHERE tuote.yhtio = '$kukarow[yhtio]'
				and tuote.ei_saldoa = ''
				$trylisa
				ORDER BY tuote.osasto, tuote.try, tuote.tuoteno";
	$result = mysql_query($query) or pupe_error($query);
			
	$lask  = 0;
	$varvo = 0; // tähän summaillaan

	if ($naytarivit != "") {
		$ulos  = "osasto\t";
		$ulos .= "try\t";
		$ulos .= "tuoteno\t";
		$ulos .= "nimitys\t";
		$ulos .= "saldo\t";
		$ulos .= "kehahin\t";
		$ulos .= "vararvo\n";
	}

	while ($row = mysql_fetch_array($result)) {

	   // tuotteen määrä varastossa nyt
	   $query = "	SELECT sum(saldo) varasto
		   			FROM tuotepaikat use index (tuote_index)
		   			WHERE yhtio = '$kukarow[yhtio]'
		   			and tuoteno = '$row[tuoteno]'";
		$vres = mysql_query($query) or pupe_error($query);
		$vrow = mysql_fetch_array($vres);
		
		$kehahin = 0;
		
		// Jos tuote on sarjanumeroseurannassa niin kehahinta lasketaan yksilöiden ostohinnoista (ostetut yksilöt jotka eivät vielä ole myyty(=laskutettu))
		if ($row["sarjanumeroseuranta"] != '') {
			$query	= "	SELECT avg(tilausrivi_osto.rivihinta/tilausrivi_osto.kpl) kehahin
						FROM sarjanumeroseuranta
						LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
						LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
						WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]' and sarjanumeroseuranta.tuoteno = '$row[tuoteno]'
						and (tilausrivi_myynti.tunnus is null or tilausrivi_myynti.laskutettuaika = '0000-00-00')
						and tilausrivi_osto.laskutettuaika != '0000-00-00'";
			$sarjares = mysql_query($query) or pupe_error($query);
			$sarjarow = mysql_fetch_array($sarjares);
						
			$kehahin = sprintf('%.2f', $sarjarow["kehahin"]);
		}
		else {
			$kehahin = sprintf('%.2f', $row["kehahin"]);
		}
		
		// tuotteen muutos varastossa annetun päivän jälkeen
		$query = "	SELECT sum(kpl * if(laji in ('tulo','valmistus'), kplhinta, hinta)) muutoshinta, sum(kpl) muutoskpl
		 			FROM tapahtuma use index (yhtio_tuote_laadittu)
		 			WHERE yhtio = '$kukarow[yhtio]'
		 			and tuoteno = '$row[tuoteno]'
		 			and laadittu > '$vv-$kk-$pp 23:59:59'";
		$mres = mysql_query($query) or pupe_error($query);
		$mrow = mysql_fetch_array($mres);

		// katotaan onko tuote epäkurantti nyt
		$kerroin = 1;
		if ($row['epakurantti1pvm'] != '0000-00-00') {
			$kerroin = 0.5;
		}
		if ($row['epakurantti2pvm'] != '0000-00-00') {
			$kerroin = 0;
		}

		// arvo historiassa: lasketaan (nykyinen varastonarvo) - muutoshinta
		$muutoshinta = ($vrow["varasto"] * $kehahin * $kerroin) - $mrow["muutoshinta"];

		// saldo historiassa: lasketaan nykyiset kpl - muutoskpl
		$muutoskpl = $vrow["varasto"] - $mrow["muutoskpl"];

		
		if ($tyyppi == "A" and $muutoskpl != 0) {
			$ok = "GO";
		}
		elseif ($tyyppi == "B" and $muutoskpl == 0) {
			$ok = "GO";
		}
		elseif ($tyyppi == "C" and $muutoskpl!=0 and $muutoshinta==0) {
			$ok = "GO";
		}
		else {
			$ok = "NO-GO";
		}
		
		if ($ok == "GO") {
			
			$lask++;
			
			// summataan varastonarvoa
			$varvo += $muutoshinta;

			if ($naytarivit != "") {

				// yritetään kaivaa listaan vielä sen hetkinen kehahin jos se halutaan kerran nähdä
				$kehasilloin = $kehahin * $kerroin; // nykyinen kehahin
				$kehalisa = "\t~"; // laitetaan about merkki failiin jos ei löydetä tapahtumista mitää

				// jos ollaan annettu tämä päivä niin ei ajeta tätä , koska nykyinen kehahin on oikein ja näin on nopeempaa! wheee!
				if ($pp != date("d") or $kk != date("m") or $vv != date("Y")) {
					// katotaan mikä oli tuotteen viimeisin hinta annettuna päivänä tai sitten sitä ennen
					$query = "	SELECT hinta
								FROM tapahtuma use index (yhtio_tuote_laadittu)
								WHERE yhtio = '$kukarow[yhtio]'
								and tuoteno = '$row[tuoteno]'
								and laadittu <= '$vv-$kk-$pp 23:59:59'
								and hinta <> 0
								ORDER BY laadittu desc
								LIMIT 1";
					$ares = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($ares) == 1) {
						// löydettiin keskihankintahinta tapahtumista käytetään
						$arow = mysql_fetch_array($ares);
						$kehasilloin = $arow["hinta"];
						$kehalisa = "";
					}
					else {
						// ei löydetty alaspäin, kokeillaan kattoo lähin hinta ylöspäin
						$query = "	SELECT hinta
									FROM tapahtuma use index (yhtio_tuote_laadittu)
									WHERE yhtio = '$kukarow[yhtio]'
									and tuoteno = '$row[tuoteno]'
									and laadittu >= '$vv-$kk-$pp 23:59:59'
									and hinta <> 0
									ORDER BY laadittu
									LIMIT 1";
						$ares = mysql_query($query) or pupe_error($query);

						if (mysql_num_rows($ares) == 1) {
							// löydettiin keskihankintahinta tapahtumista käytetään
							$arow = mysql_fetch_array($ares);
							$kehasilloin = $arow["hinta"];
							$kehalisa = "\t!";
						}
					}
				}

	   			$ulos .= "$row[osasto]\t";
	   			$ulos .= "$row[try]\t";
	   			$ulos .= "$row[tuoteno]\t";
	   			$ulos .= "".asana('nimitys_',$row['tuoteno'],$row['nimitys'])."\t";
	   			$ulos .= str_replace(".",",",$muutoskpl)."\t";
	   			$ulos .= str_replace(".",",",$kehasilloin)."\t";
	   			$ulos .= str_replace(".",",",$muutoshinta)."$kehalisa\n";
			}
		}

	} // end while
	
	echo "<br><br>Löytyi $lask tuotetta.<br><br>";

	if ($naytarivit != "") {		
		// lähetetään meili
		$bound = uniqid(time()."_") ;

		$header  = "From: <$yhtiorow[postittaja_email]>\n";
		$header .= "MIME-Version: 1.0\n" ;
		$header .= "Content-Type: multipart/mixed; boundary=\"$bound\"\n" ;

		$content = "--$bound\n";

		$content .= "Content-Type: text/x-comma-separated-values; name=\"".t("varastonarvo")."-$kukarow[yhtio].txt\"\n" ;
		$content .= "Content-Transfer-Encoding: base64\n" ;
		$content .= "Content-Disposition: attachment; filename=\"".t("varastonarvo")."-$kukarow[yhtio].txt\"\n\n";

		$content .= chunk_split(base64_encode($ulos));
		$content .= "\n" ;

		$content .= "--$bound\n";

		$boob = mail($kukarow["eposti"],  "$yhtiorow[nimi] - ".t("Varastonarvo"), $content, $header, "-f $yhtiorow[postittaja_email]");

		echo "<font class='message'>".t("Lähetetään sähköposti");
		if ($boob === FALSE) echo " - ".t("Email lähetys epäonnistui!")."<br>";
		else echo " $kukarow[eposti].<br>";
		echo "</font><br>";
	}

	echo "<table>";
	echo "<tr><th>Pvm</th><th>Varastonarvo</th></tr>";
	echo "<tr><td>$vv-$kk-$pp</td><td align='right'>".sprintf("%.2f",$varvo)."</td></tr>";
	echo "</table>";

}

require ("../inc/footer.inc");

?>
