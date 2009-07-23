<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys

	if (mysql_field_name($result, $i) == "yhtio") {
		$tyyppi = 0;
	}

	if (mysql_field_name($result, $i) == "tuoteno") {
		$tuoteno = $trow[$i]; // otetaan tää talteen

		if($uusi == "") {
			$tyyppi = 3;
		}
		else {
			$ulos = "<td><input type = 'hidden' name = 'uusi' value = '$uusi'><input type = 'text' name = '$nimi' value = '$trow[$i]' size='$size' maxlength='$maxsize'></td>";
			$jatko=0;
		}

		// Tuotteen saa poistaa mikäli sillä ei ole yhtään tapahtumaa
		$query = "SELECT yhtio FROM tapahtuma WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$tuoteno' LIMIT 1";
		$sdtres = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($sdtres) == 0) {
			$query = "SELECT yhtio FROM tilausrivi WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$tuoteno' and tyyppi != 'D' LIMIT 1";
			$sdtres = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($sdtres) == 0) {
				$poistolukko = "";
			}
		}
	}

	if (mysql_field_name($result, $i) == "nimitys") {

		//	Jos meillä on jokin viranomaistuote huomautetaan että nimitystä ei venksailla
		$query  = "	SELECT tunnus
	    			FROM tuote
	    			WHERE yhtio = '$kukarow[yhtio]'
	    			and tuoteno = '$tuoteno'
					and tuotetyyppi in ('A','B')";
	    $sresult = mysql_query($query) or pupe_error($query);

		if(mysql_num_rows($sresult) != 0) {
			$ulos = "<td><input type = 'text' name = '$nimi' value = '$trow[$i]' size='$size' maxlength='$maxsize'></td><td class = 'back'><font class='info'>".t("Huom! Viranomaistuotteen nimiystä ei tulisi muuttaa!")."</font></td>";
			$jatko=0;
		}
	}

	if (mysql_field_name($result, $i) == "tuotetyyppi") {
		$sel = array();
		$sel[$trow[$i]] = "selected";

		$ulos = "<td><select name='$nimi'>";

		if($laji == "V") {
			$ulos .= "<option value='A' $sel[A]>".t("Päiväraha")."</option>";
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

	if (mysql_field_name($result, $i) == "kehahin" and $yhtiorow["palvelutuotteiden_kehahinnat"] == "K" and $trow["ei_saldoa"] != "") {
		$tyyppi = 1;
	}

	if (mysql_field_name($result, $i) == "try"
		or mysql_field_name($result, $i) == "osasto"
		or mysql_field_name($result, $i) == "tuotemerkki"
		or mysql_field_name($result, $i) == "malli"
		or mysql_field_name($result, $i) == "mallitarkenne") {

		$sresult = t_avainsana(mysql_field_name($result, $i));

		// jos avainsanoja on perustettu tehdään dropdown
		if (mysql_num_rows($sresult) > 0) {
			$ulos = "<td><select name='$nimi' id = '$nimi' Style=\"width: 300px;\" onmousedown=\"alasvetoMuutakoko('$nimi', 0);\" onblur=\"alasvetoMuutakoko('$nimi', 300);\">";

			while ($srow = mysql_fetch_array($sresult)) {
				$sel = '';
				if ($trow[$i] == $srow["selite"]) {
					$sel = "selected";
				}

				$ulos .= "<option value='$srow[selite]' $sel>$srow[selite] $srow[selitetark]</option>";
			}

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
	        $ulos = "<td><select name='$nimi'>";
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

		if ($poistolukko != "") {
			$disablointi = "DISABLED";
		}
		else {
			$disablointi = "";
		}

		$sel = $sela = "";
		if ($trow[$i] != '') {
			$sel = "SELECTED";
		}
		else {
			$sela = "SELECTED";
		}

		$ulos  = "<td><select name='$nimi' $disablointi>";
		$ulos .= "<option value = '' $sela>".t("Saldollinen tuote")."</option>";
		$ulos .= "<option value = 'o' $sel>".t("Saldoton tuote")."</option>";
		$ulos .= "</select></td>";
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

		$ulos  = "<td><select name='$nimi'>";
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

		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value = '' $sela>".t("Normaalikäsittely")."</option>";
		$ulos .= "<option value = 'o' $sel>".t("Tuotteelle on annettava rivikommentti")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "vaaditaan_kpl2") {

		$sel = array();
		$sel[$trow[$i]] = "SELECTED";

		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value = '' {$sel[""]}>".t("Normaalikäsittely")."</option>";
		$ulos .= "<option value = 'P' {$sel["P"]}>".t("Hintakerroin on perheen isän position pituus")."</option>";
		$ulos .= "<option value = 'K' {$sel["K"]}>".t("Määräkerroin on perheen isän pituus")."</option>";
		$ulos .= "<option value = 'M' {$sel["M"]}>".t("Manuaalisesti syötettävä pituus")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "sarjanumeroseuranta") {
		
		if ($poistolukko != "") {
			$disablointi = "DISABLED";
		}
		else {
			$disablointi = "";
		}

		$ulos = "<td><select name='$nimi' id = '$nimi' Style=\"width: 300px;\" onmousedown=\"alasvetoMuutakoko('$nimi', 0);\" onblur=\"alasvetoMuutakoko('$nimi', 300);\" $disablointi>";

		$sel = array();
		$sel[$trow[$i]] = "selected";

		$ulos .= "<option value = ''>".t("Ei seurantaa")."</option>";
		$ulos .= "<option value = 'S' $sel[S]>".t("Sarjanumeroseuranta. Osto-Myynti / In-Out varastonarvo")." (S)</option>";
		$ulos .= "<option value = 'T' $sel[T]>".t("Sarjanumeroseuranta. Myynti / Keskihinta-varastonarvo")." (T)</option>";
		$ulos .= "<option value = 'U' $sel[U]>".t("Sarjanumeroseuranta. Osto-Myynti / In-Out varastonarvo. Automaattinen sarjanumerointi")." (U)</option>";
		$ulos .= "<option value = 'V' $sel[V]>".t("Sarjanumeroseuranta. Osto-Myynti / Keskihinta-varastonarvo")." (V)</option>";

		$ulos .= "<option value = 'E' $sel[E]>".t("Eränumeroseuranta. Osto-Myynti / Keskihinta-varastonarvo")." (E)</option>";
		$ulos .= "<option value = 'F' $sel[F]>".t("Eränumeroseuranta parasta-ennen päivällä. Osto-Myynti / Keskihinta-varastonarvo")." (F)</option>";
		$ulos .= "<option value = 'G' $sel[G]>".t("Eränumeroseuranta. Osto-Myynti / In-Out varastonarvo")." (G)</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "aleryhma") {
		$query = "	SELECT ryhma, if (ryhma!=selite, concat_ws(' - ', ryhma, selite), ryhma) selitetark
					FROM perusalennus
					WHERE yhtio = '$kukarow[yhtio]'
					ORDER BY ryhma";
		$vresult = mysql_query($query) or pupe_error($query);

		$ulos = "<td><select name='$nimi'>";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow["ryhma"]) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[ryhma]' $sel>$vrow[selitetark]</option>";
		}
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "kuvaus" or mysql_field_name($result, $i) == "lyhytkuvaus" or mysql_field_name($result, $i) == "mainosteksti") {
		$ulos = "<td><textarea cols='35' rows='7' name='$nimi'>$trow[$i]</textarea></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "yksikko") {
		$vresult = t_avainsana("Y");
		$ulos = "<td><select name='$nimi'>";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if (strtoupper($trow[$i]) == strtoupper($vrow[1])) {
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
		$ulos = "<td><select name='$nimi'>";

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
		$ulos = "<td><select name='$nimi'>";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if (strtoupper($trow[$i]) == strtoupper($vrow[1])) {
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

		$ulos = "<td><select name = '$nimi'>
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

		$ulos = "<td><select name = '$nimi' id = '$nimi' Style=\"width: 300px;\" onmousedown=\"alasvetoMuutakoko('$nimi', 0);\" onblur=\"alasvetoMuutakoko('$nimi', 300);\">
			<option value=''>".t("Tuote näytetään hinnastossa, mutta ei verkkokaupassa")."</option>
			<option value='E' $sele>".t("Tuotetta ei näytetä hinnastossa, eikä verkkokaupassa")."</option>
			<option value='W' $selw>".t("Tuote näkyy hinnastossa ja verkkokaupassa")."</option>
			<option value='V' $selv>".t("Tuote näkyy hinnastossa sekä verkkokaupassa jos asiakkaalla asiakasalennus tai asiakashinta")."</option>
			</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "myyjanro") {

		$query = "select distinct myyja, nimi from kuka where yhtio='$kukarow[yhtio]' and myyja>0 order by myyja";
		$vresult = mysql_query($query) or pupe_error($query);

		$ulos = "<td><select name='$nimi'>";
		$ulos .= "<option value = '' >".t("Ei myyjää")."</option>";

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

		$ulos = "<td><select name='$nimi'>";
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

	if (mysql_field_name($result, $i) == "kustp") {
		$query = "	SELECT tunnus, nimi
					FROM kustannuspaikka
					WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'K'
					ORDER BY nimi";
		$vresult = mysql_query($query) or pupe_error($query);

		$sel="";
		if ($trow[$i] == "") {
			$sel = "selected";
		}
		$ulos = "<td><select name='$nimi'><option value='' $sel>".t("Ei kustannuspaikkaa")."</option>";

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

	if (mysql_field_name($result, $i) == "kohde") {
		$query = "	SELECT tunnus, nimi
					FROM kustannuspaikka
					WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'O'
					ORDER BY nimi";
		$vresult = mysql_query($query) or pupe_error($query);

		$sel="";
		if ($trow[$i] == "") {
			$sel = "selected";
		}
		$ulos = "<td><select name='$nimi'><option value='' $sel>".t("Ei kohdetta")."</option>";

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

	if (mysql_field_name($result, $i) == "projekti") {
		$query = "	SELECT tunnus, nimi
					FROM kustannuspaikka
					WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'P'
					ORDER BY nimi";
		$vresult = mysql_query($query) or pupe_error($query);

		$sel="";
		if ($trow[$i] == "") {
			$sel = "selected";
		}
		$ulos = "<td><select name='$nimi'><option value='' $sel>".t("Ei projektia")."</option>";

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

	if (mysql_field_name($result, $i) == "tuotteen_parametrit") {

		if($laji == "V") {
			$ulos = "<td><select name='$nimi'>";
			$sel[$trow[$i]] = "SELECTED";
			$ulos .= "<option value = '' >".t("Ei ilmoiteta vuosi-ilmoituksessa")."</option>";
			$ulos .= "<option value = '50' $sel[50]>".t("50 Päivärahat  ja ateriakorvaukset")."</option>";
			$ulos .= "<option value = '56' $sel[56]>".t("56 Verovapaa kilometrikorvaus")."</option>";

			$ulos .= "</td>";
		}
		else {
			$vresult = t_avainsana("PARAMETRI");

			$ulos = "<td>";
			$ulos .= "<input type='hidden' name='tuotteen_parametriti' value='$i'>";

			$tuotteen_parametrit_split = array();
			$tuotteen_parametrit = array();

			$tuotteen_parametrit_split = split(";", $trow[$i]);

			foreach ($tuotteen_parametrit_split as $params) {
				list ($key, $val) = split("=", $params);
				$key = strtolower($key);
				$tuotteen_parametrit[$key] = $val;
			}

			$xxx = 0;
			$ulos .= "<table>";

			while ($vrow = mysql_fetch_array($vresult)) {
				$ulos .= "<tr><td>$vrow[selitetark]</td><td>";
				$ulos .= "<input type='hidden' name='tuotteen_parametrit_keys[$xxx]' value='".strtolower($vrow["selite"])."'>";
				$ulos .= "<input type='text' name='tuotteen_parametrit_vals[$xxx]' size='10' value='".$tuotteen_parametrit[strtolower($vrow["selite"])]."'>";
				$ulos .= "</td></tr>";
				$xxx++;
			}

			$ulos .= "</table>";

			$ulos .= "</td>";
		}
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "tarratyyppi") {

		$sel=array($trow[$i] => "SELECTED");

		$vresult = t_avainsana("TARRATYYPPI");

		if(mysql_num_rows($vresult)>0) {
			$ulos = "<td><select name='$nimi'>
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


	//* Nämä sarakkeet eivät välttämättä löydy kaikista installaatioista (Laitemyyntispecific)*//
	if (mysql_field_name($result, $i) == "vari") {
		$vresult = t_avainsana("SARJANUMERON_LI","", "and avainsana.selite='VARIRUNKO'");

		$ulos = "<td><select name='$nimi'>";
		$ulos .= "<option value = '' >".t("Valitse rungon väri")."</option>";

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

		$ulos = "<td><select name='$nimi'>";
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

		$ulos = "<td><select name='$nimi'>";
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

		$ulos = "<td><select name='$nimi'>";
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

		$ulos = "<td><select name='$nimi'>";
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

		$ulos = "<td><select name='$nimi'>";
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

		$ulos = "<td><select name='$nimi'>";
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