<?php

	$debug = 0;

	if ($debug == 1) {
		echo " kukarow[extranet] = $kukarow[extranet] | toim = $toim | miniliittyma = $miniliittyma | syotarivi = $syotarivi<br>";
	}

	if (file_exists("../inc/alvpopup.inc")) {
		require ("../inc/alvpopup.inc");
	}
	else {
		require ("alvpopup.inc");
	}

	if ($kukarow["extranet"] == '' and $toim == "PIKATILAUS") {
		// Pikatilauksen syöttörivi
		echo "<table>
				<tr>
					<th align='left'>".t("Tuotenumero")."</th>
					<th align='left'>".t("Määrä")."</th>
					<th align='left'>".t("Var")."</th>
					<th align='left'>".t("Hinta")."</th>
					<th align='left'>".t("Ale")."</th>
					<th align='left'>".t("Netto")."</th>
					<td valign='top' class='back'></td>
				</tr>
			<tr>";


		if ($ulos == '') {
			echo "<td valign='top'><input type='text' name='tuoteno' size='15' maxlength='20' value='$tuoteno'></td>";
		}
		else {
			echo "<td valign='top'>$ulos</td>";
		}

		echo "	<td valign='top'><input type='text' name='kpl' size='5' maxlength='5' value='$kpl'></td>
				<td valign='top'><input type='text' name='var' size='2' maxlength='1' value='$var'></td>
				<td valign='top'><input type='text' name='hinta' size='5' maxlength='12' value='$hinta'></td>
				<td valign='top'><input type='text' name='ale' size='5' maxlength='6' value='$ale'></td>
				<td valign='top'><input type='text' name='netto' size='2' maxlength='1' value='$netto'></td>
				<td valign='top' class='back'><input type='submit' value='".t("Lisää")."'></td>
				<td valign='top' class='back'><input type='submit' name='tyhjenna' value='".t("Tyhjennä")."'> <font class='error'>$varaosavirhe</font></td>
			</tr>";

		// jos meillä on rivi muuttuja täällä, niin ollaan muokkaamassa rivejä..
		if ($rivitunnus!='') {
			echo "<tr>";

			echo "	<th align='left' colspan='2'>".t("Keräysaika")."</th>
					<th align='left' colspan='4'>".t("Toimitusaika")."</th>
					</tr>";
			echo "<tr>";

			if ($toimaika == "" or $toimaika == "0000-00-00") {
				$toimaika = $laskurow["toimaika"];
			}

			if ($kerayspvm == "" or $kerayspvm == "0000-00-00") {
				$kerayspvm = $laskurow["kerayspvm"];
			}

			if ($toimaika == "" or $toimaika == "0000-00-00") {
				$toimkka = date("m",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
				$toimvva = date("Y",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
				$toimppa = date("d",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
			}
			else {
				$toimkka = substr($toimaika, 5, 2);
				$toimvva = substr($toimaika, 0, 4);
				$toimppa = substr($toimaika, 8, 2);
			}

			if ($kerayspvm == "" or $kerayspvm == "0000-00-00") {
				$kerayskka = date("m",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
				$keraysvva = date("Y",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
				$keraysppa = date("d",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
			}
			else {
				$kerayskka = substr($kerayspvm, 5, 2);
				$keraysvva = substr($kerayspvm, 0, 4);
				$keraysppa = substr($kerayspvm, 8, 2);
			}

			echo "<td valign='top' colspan='2'><input type='text' size='3' name='keraysppa' value='$keraysppa'><input type='text' size='3' name='kerayskka' value='$kerayskka'><input type='text' size='5' name='keraysvva' value='$keraysvva'></td>";		
			echo "<td valign='top' colspan='4'><input type='text' size='3' name='toimppa' value='$toimppa'><input type='text' size='3' name='toimkka' value='$toimkka'><input type='text' size='5' name='toimvva' value='$toimvva'></td></tr>";
			echo "<tr>";
			
			
			echo "<tr><th colspan='6' align='left'>".t("Rivikommentti").":</tg></tr>";
			echo "<tr><td valign='top' colspan='6'><textarea name='kommentti' cols='50' rows='2' wrap='hard'>$kommentti</textarea></td></tr>";

			if ($yhtiorow["nimityksen_muutos_tilauksella"] == "Y" and ($toim == "TARJOUS" or $laskurow["tilaustyyppi"] == "T")) {
				echo "<tr>
						<th colspan='6' align='left'>".t("Tuotteen nimitys")."</th>
						</tr><tr>
						<td valign='top' class='back' colspan='6'><input type='text' maxlength='35' size='50' name='tuotenimitys' value = '$tuotenimitys'></td></tr>";
			}

			//Jos emillä on marginaalimyyntiä niin pitää muistaa ja kertoa se myös lisääriville
			if (isset($alv) and $alv >= 500) {
				echo "<input type='hidden' name='alv' value='$alv'>";
			}

			echo "<input type='hidden' name='rivitunnus' value='$rivitunnus'>";
			echo "<input type='hidden' name='menutila' value='$menutila'>";
			echo "<input type='hidden' name='rivinumero' value='$rivinumero'>";
			echo "<input type='hidden' name='myy_sarjatunnus' value='$myy_sarjatunnus'>";
			echo "<input type='hidden' name='jaksotettu' value='$jaksotettu'>";
			echo "<input type='hidden' name='paikka' value='$paikka'>";
		}

		echo "<input type='hidden' name='perheid' value = '$perheid'>";
		echo "<input type='hidden' name='perheid2' value = '$perheid2'>";

		echo "</form>";
		echo "</table>";
	}
	elseif ($kukarow["extranet"] == '' and ($toim == "RIVISYOTTO" or $toim == "VALMISTAASIAKKAALLE" or $toim == "TYOMAARAYS" or $aputoim == "RIVISYOTTO" or $toim == "YLLAPITO" or $toim == "TARJOUS")) {
		// Rivisyötön syöttörivi
		echo "<table>
				<tr>
				<th align='left'>".t("Tuoteno")."</th>
				<th align='left'>".t("Määrä")."</th>
				<th align='left'>".t("Var")."</th>
				<th align='left'>".t("Hinta")."</th>
				<th align='left'>".t("Ale")."</th>
				<th align='left'>".t("Netto")."</th>
				<th align='left'>".t("Alv")."</th>
				<td valign='top' class='back'></td>
				</tr>";


		echo "<tr>";

		if ($ulos == '') {
			echo "<td valign='top'><input type='text' name='tuoteno' size='15' maxlength='20' value='$tuoteno'></td>";
		}
		else {
			echo "<td valign='top'>$ulos</td>";
		}

		echo "
			<td valign='top'><input type='text' size='5' name='kpl' value='$kpl'></td>";

		$selh = $selj = $selp = "";

		if ($var == "H") {
			$selh = "SELECTED";
		}
		elseif ($var == "J") {
			$selj = "SELECTED";
		}
		elseif ($var == "P") {
			$selp = "SELECTED";
		}
		elseif ($var == "W") {
			$selw = "SELECTED";
		}

		echo "<td valign='top'><select name='var'>";
		echo "<option value=''>".t("Oletus")."</option>";
		echo "<option value='J' $selj>".t("JT")."</option>";
		echo "<option value='H' $selh>".t("Hyväksy")."</option>";
		echo "<option value='P' $selp>".t("Puute")."</option>";
		
		if($laskurow["tila"] == 'V' and $laskurow["tilaustyyppi"] == 'V') {
			echo "<option value='W' $selw>".t("Varastosta")."</option>";
		}
		
		echo "</select></td>";

		echo "<td valign='top'><input type='text' size='5' name='hinta' value='$hinta'></td>
			<td valign='top'><input type='text' size='5' name='ale' value='$ale'></td>";

		$chke = $chkn = "";

		if ($netto == 'N') {
			$chkn = 'SELECTED';
		}
		elseif($netto == "E") {
			$chke = 'SELECTED';
		}

		echo "<td valign='top'><select name='netto'>";
		echo "<option value=''>".t("Oletus")."</option>";
		echo "<option value='N' $chkn>".t("N-Netto")."</option>";
		echo "<option value='E' $chke>".t("E-Netto")."</option>";
		echo "</select></td>";

		if (isset($alv) and $alv >= 500) {
			echo "<td valign='top'>".t("M.V.")."</td>";
			echo "<input type='hidden' name='alv' value='$alv'>";
		}
		else {
			echo "<td valign='top'>".alv_popup_oletus('alv',$alv, $laskurow["maa"])."</td>";
		}

		echo "<td valign='top' class='back'><input type='submit' value='".t("Lisää")."'> <font class='error'>$varaosavirhe</font></td></tr>";

		echo "<tr>
				<th align='left'>".t("Keräysaika")."</th>
				<th align='left' colspan='2'>".t("Toimitusaika")."</th>
				<th colspan='4' align='left'>".t("Kommentti")."</th>
				<td valign='top' class='back'></td>
				</tr>";
		echo "<tr>";
		
		if ($toimaika == "" or $toimaika == "0000-00-00") {
			$toimaika = $laskurow["toimaika"];
		}

		if ($kerayspvm == "" or $kerayspvm == "0000-00-00") {
			$kerayspvm = $laskurow["kerayspvm"];
		}
		
		if ($toimaika == "" or $toimaika == "0000-00-00") {
			$toimkka = date("m",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
			$toimvva = date("Y",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
			$toimppa = date("d",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
		}
		else {
			$toimkka = substr($toimaika, 5, 2);
			$toimvva = substr($toimaika, 0, 4);
			$toimppa = substr($toimaika, 8, 2);
		}
		
		if ($kerayspvm == "" or $kerayspvm == "0000-00-00") {
			$kerayskka = date("m",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
			$keraysvva = date("Y",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
			$keraysppa = date("d",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
		}
		else {
			$kerayskka = substr($kerayspvm, 5, 2);
			$keraysvva = substr($kerayspvm, 0, 4);
			$keraysppa = substr($kerayspvm, 8, 2);
		}
		
		echo "<td valign='top'><input type='text' size='3' name='keraysppa' value='$keraysppa'><input type='text' size='3' name='kerayskka' value='$kerayskka'><input type='text' size='5' name='keraysvva' value='$keraysvva'></td>";		
		echo "<td valign='top' colspan='2'><input type='text' size='3' name='toimppa' value='$toimppa'><input type='text' size='3' name='toimkka' value='$toimkka'><input type='text' size='5' name='toimvva' value='$toimvva'></td>";		
		echo "<td valign='top' colspan='4'><textarea name='kommentti' cols='32' rows='2' wrap='hard'>$kommentti</textarea></td>";

		if ($yhtiorow["nimityksen_muutos_tilauksella"] == "Y" and ($toim == "RIVISYOTTO" or $toim == "TYOMAARAYS" or ($toim == "TARJOUS" or $laskurow["tilaustyyppi"] == "T"))) {
			echo "</tr>
				<tr>
					<th colspan='7' align='left'>".t("Tuotteen nimitys")."</th>
					</tr>
				<tr>
					<td valign='top' colspan='7'><input type='text' maxlength='35' size='75' name='tuotenimitys' value = '$tuotenimitys'></td>";
		}

		echo "<td valign='top' class='back'><input type='submit' name='tyhjenna' value='".t("Tyhjennä")."'></td></tr>";

		// jos meillä on rivi muuttuja täällä, niin ollaan muokkaamassa rivejä..
		if ($rivitunnus!='') {
			echo "<input type='hidden' name='rivitunnus' value='$rivitunnus'>";
			echo "<input type='hidden' name='menutila' value='$menutila'>";
			echo "<input type='hidden' name='myy_sarjatunnus' value='$myy_sarjatunnus'>";
			echo "<input type='hidden' name='jaksotettu' value='$jaksotettu'>";
			echo "<input type='hidden' name='rivinumero' value='$rivinumero'>";
			echo "<input type='hidden' name='paikka' value='$paikka'>";
		}

		echo "<input type='hidden' name='perheid' value = '$perheid'>";
		echo "<input type='hidden' name='perheid2' value = '$perheid2'>";

		echo "</form>";
		echo "</table>";
	}
	elseif ($kukarow["extranet"] == '' and ($toim == "SIIRTOLISTA" or $toim == "SIIRTOTYOMAARAYS" or $toim == "MYYNTITILI" or $toim == "VALMISTAVARASTOON")) {
		// Rivisyötön syöttörivi
		echo "<table>
				<tr>";

		if ($tila == "LISAARESEPTIIN") {
			echo "<th align='left'>".t("Perhe")."</th>";
		}

		echo "	<th align='left'>".t("Tuoteno")."</th>
				<th align='left'>".t("Määrä")."</th>
				</tr>";


		echo "<tr>";

		if ($tila == "LISAARESEPTIIN") {
			echo "<td valign='top'>$perheid $perheid2</td>";
		}

		if ($ulos == '') {
			echo "<td valign='top'><input type='text' name='tuoteno' size='15' maxlength='20' value='$tuoteno'></td>";
		}
		else {
			echo "<td valign='top'>$ulos</td>";
		}

		echo "<td valign='top'><input type='text' size='5' name='kpl' value='$kpl'></td>";

		echo "<td valign='top' class='back'><input type='submit' value='".t("Lisää")."'> <font class='error'>$varaosavirhe</font></td></tr>";

		
		echo "<tr>";
		
		if ($tila == "LISAARESEPTIIN") {
			echo "<th></th>";
		}
		
		echo "	<th align='left'>".t("Keräysaika")."</th>
				<th align='left'>".t("Toimitusaika")."</th>
				</tr>";
		echo "<tr>";
		
		if ($tila == "LISAARESEPTIIN") {
			echo "<td valign='top'></td>";
		}

		if ($toimaika == "" or $toimaika == "0000-00-00") {
			$toimaika = $laskurow["toimaika"];
		}

		if ($kerayspvm == "" or $kerayspvm == "0000-00-00") {
			$kerayspvm = $laskurow["kerayspvm"];
		}
		
		if ($toimaika == "" or $toimaika == "0000-00-00") {
			$toimkka = date("m",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
			$toimvva = date("Y",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
			$toimppa = date("d",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
		}
		else {
			$toimkka = substr($toimaika, 5, 2);
			$toimvva = substr($toimaika, 0, 4);
			$toimppa = substr($toimaika, 8, 2);
		}

		if ($kerayspvm == "" or $kerayspvm == "0000-00-00") {
			$kerayskka = date("m",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
			$keraysvva = date("Y",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
			$keraysppa = date("d",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
		}
		else {
			$kerayskka = substr($kerayspvm, 5, 2);
			$keraysvva = substr($kerayspvm, 0, 4);
			$keraysppa = substr($kerayspvm, 8, 2);
		}

		echo "<td valign='top'><input type='text' size='3' name='keraysppa' value='$keraysppa'><input type='text' size='3' name='kerayskka' value='$kerayskka'><input type='text' size='5' name='keraysvva' value='$keraysvva'></td>";		
		echo "<td valign='top'><input type='text' size='3' name='toimppa' value='$toimppa'><input type='text' size='3' name='toimkka' value='$toimkka'><input type='text' size='5' name='toimvva' value='$toimvva'></td></tr>";		
		
		echo "<tr>";
		
		if ($tila == "LISAARESEPTIIN") {
			echo "<th></th>";
		}
		
		echo "<th colspan='2' align='left'>".t("Kommentti")."</th>
				</tr>";

		echo "<tr>";
		
		if ($tila == "LISAARESEPTIIN") {
			echo "<td valign='top'></td>";
		}
				
		echo "<td valign='top' colspan='2'><textarea name='kommentti' cols='40' rows='2' wrap='hard'>$kommentti</textarea></td>
				<td valign='top' class='back'><input type='submit' name='tyhjenna' value='".t("Tyhjennä")."'></td></tr>";

		// jos meillä on rivi muuttuja täällä, niin ollaan muokkaamassa rivejä..
		if ($rivitunnus!='') {
			echo "<input type='hidden' name='rivitunnus' value='$rivitunnus'>";
			echo "<input type='hidden' name='menutila' value='$menutila'>";
			echo "<input type='hidden' name='myy_sarjatunnus' value='$myy_sarjatunnus'>";
			echo "<input type='hidden' name='jaksotettu' value='$jaksotettu'>";
			echo "<input type='hidden' name='rivinumero' value='$rivinumero'>";
			echo "<input type='hidden' name='paikka' value='$paikka'>";
		}

		echo "<input type='hidden' name='perheid' value = '$perheid'>";
		echo "<input type='hidden' name='perheid2' value = '$perheid2'>";

		echo "</form>";
		echo "</table>";
	}
	elseif ($miniliittyma == "JOO") {

		echo "	<form name='rivi' action = '$PHP_SELF?ojarj=$array[0]$ulisa$ankka' method='post' autocomplete='off'>
				<input type='hidden' name='tee' value = 'TI'>
				<input type='hidden' name='toim' value = '$toim'>
				<input type='hidden' name='lopetus' value='$lopetus'>
				<input type='hidden' name='toim_kutsu' value = '$toim_kutsu'>
				<input type='hidden' name='tilausnumero' value='$tilausnumero'>
				<input type='hidden' name='oldmerkki' value = '$merkki'>
				<input type='hidden' name='oldcc' value = '$cc'>
				<input type='hidden' name='oldmalli' value = '$malli'>
				<input type='hidden' name='oldvm' value = '$vm'>
				<input type='hidden' name='merkki' value = '$merkki'>
				<input type='hidden' name='cc' value = '$cc'>
				<input type='hidden' name='malli' value = '$malli'>
				<input type='hidden' name='vm' value = '$vm'>";

		// näytetään tilauksen syöttörivi... aina.
		echo "<table>";
		echo "<tr>";
		echo "<input type='hidden' name='tuoteno' value='$hi_tuoteno'>
				<td valign='top'><input type='text' name='kpl' size='5' maxlength='5' value='$kpl' onKeyUp='DisableText();'></td>";

		echo "	<td valign='top'> $varaosavirhe </td> ";

		echo "<td valign='top'><input type='submit' value='".t("Lisää")."'></td>";

		echo "</form>";
		echo "</tr>";
		echo "</table>";

	}
	elseif ($kukarow["extranet"] != '') {
		// Extranetin syöttörivi
		echo "<table>
				<tr>
					<th align='left'>".t("Tuotenumero")."</th>
					<th align='left'>".t("Määrä")."</th>
					<td valign='top' class='back'></td>
				</tr>
			<tr>";

		echo "<input type='hidden' name='var' value='$var'>";

		if ($ulos == '') {
			echo "<td valign='top'><input type='text' name='tuoteno' size='15' maxlength='20' value='$tuoteno'></td>";
		}
		else {
			echo "<td valign='top'>$ulos</td>";
		}

		echo "	<td valign='top'><input type='text' name='kpl' size='5' maxlength='5' value='$kpl'></td>
				<td valign='top' class='back'><input type='submit' value='".t("Lisää")."'></td>
				<td valign='top' class='back'><input type='submit' name='tyhjenna' value='".t("Tyhjennä")."'> <font class='error'>$varaosavirhe</font></td>
			</tr>";

		echo "<tr><th colspan='2'>".t("Rivikommentti").":</th></tr>";
		echo "<tr><td valign='top' class='back' colspan='2'><textarea name='kommentti' cols='30' rows='2' wrap='hard'>$kommentti</textarea></td></tr>";
		echo "</form>";
		echo "</table>";
	}

	// Kursorinohjausta
	if ($formi == '') {
		$formi = 'tilaus';
	}

   if ($kentta == '') {
       $kentta='tuoteno';
   }
?>
