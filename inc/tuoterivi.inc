<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali k‰sittely
	$tyyppi = 1; // oletetaan rivin n‰kyvyys

	if (mysql_field_name($result, $i) == "yhtio") {
		$tyyppi = 0;
	}

	if (mysql_field_name($result, $i) == "tuoteno") {
		$tuoteno = $trow[$i]; // otetaan t‰‰ talteen

		if ($uusi == "") {
			$tyyppi = 3;

			// Tuotteen saa poistaa mik‰li sill‰ ei ole yht‰‰n tapahtumaa
			$query = "SELECT yhtio FROM tapahtuma WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$tuoteno' and laji in ('ep‰kurantti','inventointi','kulutus','laskutus','siirto','tulo','valmistus') LIMIT 1";
			$sdtres = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($sdtres) == 0) {
				// Ei saa olla myˆsk‰‰n yht‰‰n tilausrivi‰
				$query = "SELECT yhtio FROM tilausrivi WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$tuoteno' and tyyppi != 'D' LIMIT 1";
				$sdtres = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($sdtres) == 0) {
					$poistolukko = "";
				}
			}
		}
		else {
			$ulos = "<td><input type = 'hidden' name = 'uusi' value = '$uusi'><input type = 'text' name = '$nimi' value = '$trow[$i]' size='$size' maxlength='$maxsize'></td>";
			$jatko=0;

			// Jos on uusi tuote niin kaikki lukot on auki
			$poistolukko = "";
		}
	}

	if (mysql_field_name($result, $i) == "nimitys") {

		//	Jos meill‰ on jokin viranomaistuote huomautetaan ett‰ nimityst‰ ei venksailla
		$query  = "	SELECT tunnus
	    			FROM tuote
	    			WHERE yhtio = '$kukarow[yhtio]'
	    			and tuoteno = '$tuoteno'
					and tuotetyyppi in ('A','B')";
	    $sresult = mysql_query($query) or pupe_error($query);

		if(mysql_num_rows($sresult) != 0) {
			$ulos = "<td><input type = 'text' name = '$nimi' value = '$trow[$i]' size='$size' maxlength='$maxsize'></td><td class = 'back'><font class='info'>".t("Huom! Viranomaistuotteen nimiyst‰ ei tulisi muuttaa!")."</font></td>";
			$jatko=0;
		}
	}

	if (mysql_field_name($result, $i) == "tuotetyyppi") {
		$sel = array();
		$sel[$trow[$i]] = "selected";

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";

		if($laji == "V") {
			$ulos .= "<option value='A' $sel[A]>".t("P‰iv‰raha")."</option>";
			$ulos .= "<option value='B' $sel[B]>".t("Muu kulu")."</option>";
		}
		else {
			$ulos .= "<option value=''>".t("Normaali / Valmiste")."</option>";
			$ulos .= "<option value='R' $sel[R]>".t("Raaka-aine")."</option>";
			$ulos .= "<option value='K' $sel[K]>".t("Palvelu")."</option>";
			$ulos .= "<option value='M' $sel[M]>".t("Muu/Informatiivinen")."</option>";
		}
		$ulos .= "</select></td>";

		$jatko=0;
	}

	if (mysql_field_name($result, $i) == "kehahin" or
		mysql_field_name($result, $i) == "vihahin" or
		mysql_field_name($result, $i) == "vihapvm" or
		mysql_field_name($result, $i) == "saldo" or
		mysql_field_name($result, $i) == "saldoaika" or
		mysql_field_name($result, $i) == "epakurantti25pvm" or
		mysql_field_name($result, $i) == "epakurantti50pvm" or
		mysql_field_name($result, $i) == "epakurantti75pvm" or
		mysql_field_name($result, $i) == "epakurantti100pvm") {
		$tyyppi = 2;
	}

	if (mysql_field_name($result, $i) == "kehahin" and $yhtiorow["palvelutuotteiden_kehahinnat"] == "K" and ($trow["ei_saldoa"] != "" or $uusi != "")) {
		$tyyppi = 1;
	}

	if (mysql_field_name($result, $i) == "try"
		or mysql_field_name($result, $i) == "osasto"
		or mysql_field_name($result, $i) == "tuotemerkki"
		or mysql_field_name($result, $i) == "malli"
		or mysql_field_name($result, $i) == "mallitarkenne") {

		$sresult = t_avainsana(mysql_field_name($result, $i));

		// jos avainsanoja on perustettu tehd‰‰n dropdown
		if (mysql_num_rows($sresult) > 0) {
			$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";

			$ulos .= "<option id='option_$nimi' value = ''>".t("Ei valintaa")."</option>";

			while ($srow = mysql_fetch_array($sresult)) {
				$sel = '';
				if ($trow[$i] == $srow["selite"]) {
					$sel = "selected";
				}

				$ulos .= "<option value='$srow[selite]' $sel>$srow[selite] $srow[selitetark]</option>";
			}

			$ulos .= "</select>";
			$ulos .= " <a href='#' onclick=\"teeostry('iframe_$nimi','".mysql_field_name($result, $i)."');\"><img src='pics/lullacons/database-option-add.png'>".t("Uusi")." ".t(mysql_field_name($result, $i))."</a><div id='iframe_$nimi' style='display: inline;'></div></td>";

			echo "
			<script LANGUAGE='JavaScript'>
				function teeostry(tdid, lukitse_laji){
					var currenttd = document.getElementById(tdid);
					currenttd.innerHTML = \"<br><br><iframe id='avainsana_iframe' name='avainsana_iframe' src='yllapito.php?toim=avainsana&uusi=1&lukitse_laji=\"+lukitse_laji+\"&laji=\"+tdid+\"&from=yllapito&ohje=off' style='width: 500px; border: 0px; display: block;' border='0' frameborder='0'></iFrame>\";
				}
			</script>";

			$ulos .= "</select></td>";
			$jatko = 0;
		}
	}

	if (mysql_field_name($result, $i) == "valuutta") {
		$query = "	SELECT nimi, tunnus
					FROM valuu
					WHERE yhtio = '$kukarow[yhtio]'
					ORDER BY jarjestys";
	        $vresult = mysql_query($query) or pupe_error($query);
	        $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
	        while ($vrow=mysql_fetch_array($vresult)) {
		        $sel="";
		        if ($trow[$i] == $vrow[0]) {
		                $sel = "selected";
		        }
	        	$ulos .= "<option value = '$vrow[0]' $sel>$vrow[0]</option>";
	        }
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "ei_saldoa") {

		$sel = $sela = "";
		if ($trow[$i] != '') {
			$sel = "SELECTED";
		}
		else {
			$sela = "SELECTED";
		}

		if ($poistolukko != "") {
			$ulos  = "<td>";

			if ($sela != "") $ulos .= t("Saldollinen tuote");
			else $ulos .= t("Saldoton tuote");

			$ulos .= "<input type='hidden' name='$nimi' value='$trow[$i]'></td>";
		}
		else {
			$ulos  = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
			$ulos .= "<option value = '' $sela>".t("Saldollinen tuote")."</option>";
			$ulos .= "<option value = 'o' $sel>".t("Saldoton tuote")."</option>";
			$ulos .= "</select>$disablelisa</td>";
		}


		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "ei_varastoida") {

		$sel = $sela = "";
		if ($trow[$i] != '') {
			$sel = "SELECTED";
		}
		else {
			$sela = "SELECTED";
		}

		$ulos  = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
		$ulos .= "<option value = '' $sela>".t("Varastoitava tuote")."</option>";
		$ulos .= "<option value = 'o' $sel>".t("Tuotetta ei varastoida")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "kommentoitava") {

		$sel = $sela = "";
		if ($trow[$i] != '') {
			$sel = "SELECTED";
		}
		else {
			$sela = "SELECTED";
		}

		$ulos  = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
		$ulos .= "<option value = '' $sela>".t("Normaalik‰sittely")."</option>";
		$ulos .= "<option value = 'o' $sel>".t("Tuotteelle on annettava rivikommentti")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "vaaditaan_kpl2") {

		$sel = array();
		$sel[$trow[$i]] = "SELECTED";

		$ulos  = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
		$ulos .= "<option value = ''>".t("Normaalik‰sittely")."</option>";
		$ulos .= "<option value = 'P' $sel[P]>".t("Pituus positiolta, is‰tuotteen hinta korjataan")."</option>";
		$ulos .= "<option value = 'M' $sel[M]>".t("Manuaalisesti syˆtett‰v‰ pituus, is‰tuotteen m‰‰r‰‰ ei korjata")."</option>";
		$ulos .= "<option value = 'N' $sel[N]>".t("Manuaalisesti syˆtett‰v‰ m‰‰r‰, is‰n m‰‰r‰‰ korjataan")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "sarjanumeroseuranta") {

		$sel = array();
		$sel[$trow[$i]] = "selected";

		$query = "	SELECT count(*) saldo
					FROM tuotepaikat
					WHERE yhtio = '$kukarow[yhtio]'
					and tuoteno = '$tuoteno'
					and saldo  != 0";
		$vresult = mysql_query($query) or pupe_error($query);
		$vrow = mysql_fetch_array($vresult);

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";

		if ($poistolukko == "" or $trow[$i] == "" or ($vrow["saldo"] == 0 and in_array($trow[$i], array("","T","V","E","F")))) {
			$ulos .= "<option value = ''>".t("Ei seurantaa")."</option>";
		}
		if ($poistolukko == "" or $sel["S"] == "selected" or ($vrow["saldo"] == 0 and in_array($trow[$i], array("S","U")))) {
			$ulos .= "<option value = 'S' $sel[S]>".t("Sarjanumeroseuranta. Osto-Myynti / In-Out varastonarvo")." (S)</option>";
		}
		if ($poistolukko == "" or $sel["T"] == "selected" or ($vrow["saldo"] == 0 and in_array($trow[$i], array("","T","V","E","F")))) {
			$ulos .= "<option value = 'T' $sel[T]>".t("Sarjanumeroseuranta. Myynti / Keskihinta-varastonarvo")." (T)</option>";
		}
		if ($poistolukko == "" or $sel["U"] == "selected" or ($vrow["saldo"] == 0 and in_array($trow[$i], array("S","U")))) {
			$ulos .= "<option value = 'U' $sel[U]>".t("Sarjanumeroseuranta. Osto-Myynti / In-Out varastonarvo. Automaattinen sarjanumerointi")." (U)</option>";
		}
		if ($poistolukko == "" or $sel["V"] == "selected" or ($vrow["saldo"] == 0 and in_array($trow[$i], array("","T","V","E","F")))) {
			$ulos .= "<option value = 'V' $sel[V]>".t("Sarjanumeroseuranta. Osto-Myynti / Keskihinta-varastonarvo")." (V)</option>";
		}
		if ($poistolukko == "" or $sel["E"] == "selected" or in_array($trow[$i], array("E","F")) or ($vrow["saldo"] == 0 and in_array($trow[$i], array("","T","V","E","F")))) {
			$ulos .= "<option value = 'E' $sel[E]>".t("Er‰numeroseuranta. Osto-Myynti / Keskihinta-varastonarvo")." (E)</option>";
		}
		if ($poistolukko == "" or $sel["F"] == "selected" or in_array($trow[$i], array("E","F")) or ($vrow["saldo"] == 0 and in_array($trow[$i], array("","T","V","E","F")))) {
			$ulos .= "<option value = 'F' $sel[F]>".t("Er‰numeroseuranta parasta-ennen p‰iv‰ll‰. Osto-Myynti / Keskihinta-varastonarvo")." (F)</option>";
		}
		if ($poistolukko == "" or $sel["G"] == "selected") {
			$ulos .= "<option value = 'G' $sel[G]>".t("Er‰numeroseuranta. Osto-Myynti / In-Out varastonarvo")." (G)</option>";
		}

		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "aleryhma") {
		$query = "	SELECT ryhma, if (ryhma!=selite, concat_ws(' - ', ryhma, selite), ryhma) selitetark
					FROM perusalennus
					WHERE yhtio = '$kukarow[yhtio]'
					ORDER BY ryhma";
		$vresult = mysql_query($query) or pupe_error($query);

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
		$ulos .= "<option id='option_$nimi' value = ''>".t("Ei ryhm‰‰")."</option>";

		while ($srow = mysql_fetch_array($vresult)) {
			$sel = '';
			if ($trow[$i] == $srow["ryhma"]) {
				$sel = "selected";
			}
			$ulos .= "<option value='$srow[ryhma]' $sel>$srow[selitetark]</option>";
		}

		$ulos .= "</select>";
		$ulos .= " <a href='#' onclick=\"teekentta('iframe_$nimi');\"><img src='pics/lullacons/database-option-add.png'>".t("Uusi ryhm‰")."</a><div id='iframe_$nimi' style='display: inline;'></div></td>";

		echo "
		<script LANGUAGE='JavaScript'>
			function teekentta(tdid){
				var currenttd = document.getElementById(tdid);
				currenttd.innerHTML = \"<br><br><iframe id='perusalennus_iframe' name='perusalennus_iframe' src='yllapito.php?toim=perusalennus&uusi=1&laji=\"+tdid+\"&from=yllapito&ohje=off' style='width: 400px; border: 0px; display: block;' border='0' frameborder='0'></iFrame>\";
			}
		</script>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "kuvaus" or mysql_field_name($result, $i) == "lyhytkuvaus" or mysql_field_name($result, $i) == "mainosteksti") {
		$ulos = "<td><textarea cols='35' rows='7' name='$nimi'>$trow[$i]</textarea></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "yksikko") {
		$vresult = t_avainsana("Y");
		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if (strtoupper($trow[$i]) == strtoupper($vrow["selite"])) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[selite]' $sel>$vrow[selite] - $vrow[selitetark]</option>";
		}
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "alkuperamaa") {
		$query = "	SELECT distinct koodi, nimi
					FROM maat
					WHERE nimi != ''
					ORDER BY koodi";
		$vresult = mysql_query($query) or pupe_error($query);
		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";

		$ulos .= "<option value = '' ></option>";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow[0]) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[0]' $sel>$vrow[1]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "status") {
		$vresult = t_avainsana("S");
		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if (strtoupper($trow[$i]) == strtoupper($vrow["selite"])) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[selite]' $sel>$vrow[selite] - $vrow[selitetark]</option>";
		}
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "alv") {
			require ('alvpopup.inc');
			if ($trow[$i]=='') $trow[$i]=alv_oletus();
			$ulos = "<td>".alv_popup($nimi,$trow[$i])."</td>";
			$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "suoratoimitus") {
		$sele = "";

		if ($trow[$i] == 'S') {
			$sele = "SELECTED";
		}

		$ulos = "<td><select name='$nimi'".js_alasvetoMaxWidth($nimi, 300).">
			<option value=''>".t("Normaalituote")."</option>
			<option value='S' $sele>".t("Suoratoimitustuote")."</option>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "hinnastoon") {

		$sele = $selw = "";
		if ($trow[$i] == 'E') {
			$sele = "SELECTED";
		}
		if ($trow[$i] == 'W') {
			$selw = "SELECTED";
		}
		if ($trow[$i] == 'V') {
			$selv = "SELECTED";
		}

		$ulos = "<td><select name = '$nimi' ".js_alasvetoMaxWidth($nimi, 300).">
			<option value=''>".t("Tuote n‰ytet‰‰n hinnastossa, mutta ei verkkokaupassa")."</option>
			<option value='E' $sele>".t("Tuotetta ei n‰ytet‰ hinnastossa, eik‰ verkkokaupassa")."</option>
			<option value='W' $selw>".t("Tuote n‰kyy hinnastossa ja verkkokaupassa")."</option>
			<option value='V' $selv>".t("Tuote n‰kyy hinnastossa sek‰ verkkokaupassa jos asiakkaalla asiakasalennus tai asiakashinta")."</option>
			</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "myyjanro") {

		$query = "select distinct myyja, nimi from kuka where yhtio='$kukarow[yhtio]' and myyja>0 order by myyja";
		$vresult = mysql_query($query) or pupe_error($query);

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
		$ulos .= "<option value = '' >".t("Ei myyj‰‰")."</option>";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow['myyja']) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[myyja]' $sel>$vrow[myyja] - $vrow[nimi]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "ostajanro") {

		$query = "SELECT distinct myyja, nimi from kuka where yhtio='$kukarow[yhtio]' and myyja>0 order by myyja";
		$vresult = mysql_query($query) or pupe_error($query);

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
		$ulos .= "<option value = '' >".t("Ei ostajaa")."</option>";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow['myyja']) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[myyja]' $sel>$vrow[myyja] - $vrow[nimi]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "projekti" or mysql_field_name($result, $i) == "kohde" or mysql_field_name($result, $i) == "kustp") {

		if (mysql_field_name($result, $i) == "projekti") $kptyyppi = "P";
		if (mysql_field_name($result, $i) == "kohde") 	 $kptyyppi = "O";
		if (mysql_field_name($result, $i) == "kustp") 	 $kptyyppi = "K";

		$tpque = "	SELECT tunnus, nimi
					from kustannuspaikka
					where yhtio = '$kukarow[yhtio]'
					and tyyppi = '$kptyyppi'
					and kaytossa != 'E'";
		$vresult = mysql_query($tpque) or pupe_error($tpque);

		$sel="";
		if ($trow[$i] == "") {
			$sel = "selected";
		}
		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300)."><option value='' $sel>".t("Ei valintaa")."</option>";

		while ($vrow = mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow["tunnus"]) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[tunnus]' $sel>$vrow[nimi]</option>";
		}
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "kuvaus") {
		if ($laji == "V") {
			$sel[$trow[$i]] = "SELECTED";
			$ulos = "<td>";
			$ulos .= "<select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
			$ulos .= "<option value = '' >".t("Ei ilmoiteta vuosi-ilmoituksessa")."</option>";
			$ulos .= "<option value = '50' $sel[50]>".t("50 P‰iv‰rahat  ja ateriakorvaukset")."</option>";
			$ulos .= "<option value = '56' $sel[56]>".t("56 Verovapaa kilometrikorvaus")."</option>";
			$ulos .= "</select>";
			$ulos .= "</td>";
		}
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "tarratyyppi") {

		$sel=array($trow[$i] => "SELECTED");

		$vresult = t_avainsana("TARRATYYPPI");

		if(mysql_num_rows($vresult)>0) {
			$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">
							<option value=''>".t("Ei tilaustuotetarraa")."</option>";
			while ($vrow = mysql_fetch_array($vresult)) {
				$ulos .= "<option value='$vrow[selite]' ".$sel[$vrow["selite"]].">$vrow[selite] - $vrow[selitetark]</option>";
			}
			$ulos .= "</select></td>";
		}
		else {
			$tyyppi = 0;
		}

		$jatko = 0;
	}


	//* N‰m‰ sarakkeet eiv‰t v‰ltt‰m‰tt‰ lˆydy kaikista installaatioista (Laitemyyntispecific)*//
	if (mysql_field_name($result, $i) == "vari") {
		$vresult = t_avainsana("SARJANUMERON_LI","", "and avainsana.selite='VARIRUNKO'");

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
		$ulos .= "<option value = '' >".t("Valitse rungon v‰ri")."</option>";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow['selitetark']) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[selitetark]' $sel>$vrow[selitetark_2]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}
	if (mysql_field_name($result, $i) == "laitetyyppi") {
		$vresult = t_avainsana("SARJANUMERON_LI","", "and avainsana.selite='TYYPPI'");

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
		$ulos .= "<option value = '' >".t("Valitse tyyppi")."</option>";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow['selitetark']) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[selitetark]' $sel>$vrow[selitetark_2]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}
	if (mysql_field_name($result, $i) == "runkotyyppi") {
		$vresult = t_avainsana("SARJANUMERON_LI","", "and avainsana.selite='RUNKOTYYPPI'");

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
		$ulos .= "<option value = '' >".t("Valitse tyyppi")."</option>";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow['selitetark']) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[selitetark]' $sel>$vrow[selitetark_2]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}
	if (mysql_field_name($result, $i) == "kilpi") {
		$vresult = t_avainsana("SARJANUMERON_LI","", "and avainsana.selite='KILPI'");

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
		$ulos .= "<option value = '' >".t("Valitse kilpi")."</option>";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow['selitetark']) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[selitetark]' $sel>$vrow[selitetark_2]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}
	if (mysql_field_name($result, $i) == "materiaali") {
		$vresult = t_avainsana("SARJANUMERON_LI","", "and avainsana.selite='MATERIAALI'");

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
		$ulos .= "<option value = '' >".t("Valitse veneen materiaali")."</option>";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow['selitetark']) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[selitetark]' $sel>$vrow[selitetark_2]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}
	if (mysql_field_name($result, $i) == "koneistus") {
		$vresult = t_avainsana("SARJANUMERON_LI","", "and avainsana.selite='KONEISTUS'");

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
		$ulos .= "<option value = '' >".t("Valitse veneen koneistus")."</option>";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow['selitetark']) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[selitetark]' $sel>$vrow[selitetark_2]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}
	if (mysql_field_name($result, $i) == "sprinkleri") {
		$vresult = t_avainsana("SARJANUMERON_LI","", "and avainsana.selite='SPRINKLERI'");

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
		$ulos .= "<option value = '' >".t("Valitse tyyppi")."</option>";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow['selitetark']) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[selitetark]' $sel>$vrow[selitetark_2]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}

?>