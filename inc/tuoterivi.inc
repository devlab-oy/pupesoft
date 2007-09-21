<?php
	
	$ulos = "";
	$jatko = 1; // oletetaan normaali k‰sittely
	$tyyppi = 1; // oletetaan rivin n‰kyvyys
	
	if (mysql_field_name($result, $i) == "yhtio") {
		$tyyppi = 0;
	}
	
	//	Tuoteno talteen
	if (mysql_field_name($result, $i) == "tuoteno") {
		$tuoteno = $trow[$i];
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
	
		$ulos = "<td><select name='$nimi'>";
		if($laji == "V") {
			$ulos .= "<option value='A' $sel[A]>".t("P‰iv‰raha")."</option>";
			$ulos .= "<option value='B' $sel[B]>".t("Muu kulu")."</option>";			
		}
		else {
			$ulos .= "<option value=''>".t("Normaali / Valmiste")."</option>";
			$ulos .= "<option value='R' $sel[R]>".t("Raaka-aine")."</option>";
			$ulos .= "<option value='K' $sel[K]>".t("Palvelu")."</option>";			
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

	if (mysql_field_name($result, $i) == "tuoteno") {
		$tuoteno = $trow[$i]; // otetaan t‰‰ talteen				
		
		if($uusi == "") {
			$tyyppi = 3;
		}
		else {
			$ulos = "<td><input type = 'hidden' name = 'uusi' value = '$uusi'><input type = 'text' name = '$nimi' value = '$trow[$i]' size='$size' maxlength='$maxsize'><td>";	
			$jatko=0;
		}
	}

	if (mysql_field_name($result, $i) == "try") {
		$query = "	SELECT distinct avainsana.selite, ".avain('select')."
					FROM avainsana
					".avain('join','TRY_')."
					WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='TRY'
					ORDER BY avainsana.selite+0";
		$sresult = mysql_query($query) or pupe_error($query);

		$ulos = "<td><select name='$nimi'>";
		while ($srow = mysql_fetch_array($sresult)) {
			$sel = '';
			if ($trow[$i] == $srow[0]) {
				$sel = "selected";
			}
			$ulos .= "<option value='$srow[0]' $sel>$srow[0] $srow[1]</option>";
		}
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "osasto") {
		$query = "	SELECT distinct avainsana.selite, ".avain('select')."
					FROM avainsana
					".avain('join','OSASTO_')."
					WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='OSASTO'
					ORDER BY avainsana.selite+0";
		$sresult = mysql_query($query) or pupe_error($query);

		$ulos = "<td><select name='$nimi'>";
		while ($srow = mysql_fetch_array($sresult)) {
			$sel = '';
			if ($trow[$i] == $srow[0]) {
				$sel = "selected";
			}
			$ulos .= "<option value='$srow[0]' $sel>$srow[0] $srow[1]</option>";
		}
		$ulos .= "</select></td>";
		$jatko = 0;
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

		$sel = $sela = "";
		if ($trow[$i] != '') {
			$sel = "SELECTED";
		}
		else {
			$sela = "SELECTED";
		}
	
		$ulos  = "<td><select name='$nimi'>";
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
		$ulos .= "<option value = '' $sela>".t("Normaalik‰sittely")."</option>";
		$ulos .= "<option value = 'o' $sel>".t("Tuotteelle on annettava rivikommentti")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "vaaditaan_kpl2") {

		$sel = $sela = "";
		if ($trow[$i] != '') {
			$sel = "SELECTED";
		}
		else {
			$sela = "SELECTED";
		}

		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value = '' $sela>".t("Normaalik‰sittely")."</option>";
		$ulos .= "<option value = 'P' $sel>".t("M‰‰r‰ on perheen is‰n position pituus")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "sarjanumeroseuranta") {

		$ulos = "<td><select name='$nimi'>";

		$sel = array();
		$sel[$trow[$i]] = "selected";

		$ulos .= "<option value = ''>".t("Ei seurantaa")."</option>";
		$ulos .= "<option value = 'S' $sel[S]>".t("Sarjanumeroseuranta. Osto-Myynti / In-Out varastonarvo")."</option>";
		$ulos .= "<option value = 'E' $sel[E]>".t("Er‰numeroseuranta. Osto-Myynti / Keskihinta-varastonarvo")."</option>";
		$ulos .= "<option value = 'F' $sel[F]>".t("Er‰numeroseuranta parasta-ennen p‰iv‰ll‰. Osto-Myynti / Keskihinta-varastonarvo")."</option>";
		$ulos .= "<option value = 'T' $sel[T]>".t("Sarjanumeroseuranta. Myynti / Keskihinta-varastonarvo")."</option>";
		$ulos .= "</select></td>";
	
		$jatko = 0;
	}


	if (mysql_field_name($result, $i) == "aleryhma") {
		$query = "	SELECT tunnus, ryhma, concat_ws(' - ', ryhma, selite) ryhma, alennus
					FROM perusalennus
					WHERE yhtio = '$kukarow[yhtio]'
					ORDER BY ryhma";
		$vresult = mysql_query($query) or pupe_error($query);
		$ulos = "<td><select name='$nimi'>";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow[1]) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[1]' $sel>$vrow[2]</option>";
		}
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "kuvaus" or mysql_field_name($result, $i) == "lyhytkuvaus") {
		$ulos = "<td><textarea cols='35' rows='7' name='$nimi'>$trow[$i]</textarea></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "yksikko") {
		$query = "	SELECT avainsana.tunnus, avainsana.selite, ".avain('select')."
					FROM avainsana
					".avain('join','Y_')."
					WHERE avainsana.yhtio = '$kukarow[yhtio]' and avainsana.laji = 'Y'
					ORDER BY avainsana.selite";
		$vresult = mysql_query($query) or pupe_error($query);
		$ulos = "<td><select name='$nimi'>";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if (strtoupper($trow[$i]) == strtoupper($vrow[1])) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[1]' $sel>$vrow[1] ($vrow[2])</option>";
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
		$query = "	SELECT avainsana.tunnus, avainsana.selite, ".avain('select')."
					FROM avainsana
					".avain('join','S_')."
					WHERE avainsana.yhtio = '$kukarow[yhtio]' and avainsana.laji = 'S'
					ORDER BY avainsana.selite";
		$vresult = mysql_query($query) or pupe_error($query);
		$ulos = "<td><select name='$nimi'>";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if (strtoupper($trow[$i]) == strtoupper($vrow[1])) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[1]' $sel>$vrow[1] ($vrow[2])</option>";
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

	if (mysql_field_name($result, $i) == "hinnastoon") {

		$sel = $sela = "";
		if ($trow[$i] == 'E') {
			$sel = "SELECTED";
		}
		else {
			$sela = "SELECTED";
		}

		$ulos = "<td><select name = '$nimi'>
			<option value='' $sela>".t("Tuote n‰ytet‰‰n hinnastossa")."</option>
			<option value='E' $sel>".t("Tuotetta ei n‰ytet‰ hinnastossa")."</option>
			</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "myyjanro") {

		$query = "select distinct myyja, nimi from kuka where yhtio='$kukarow[yhtio]' and myyja>0 order by myyja";
		$vresult = mysql_query($query) or pupe_error($query);

		$ulos = "<td><select name='$nimi'>";
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

		$query = "select distinct myyja, nimi from kuka where yhtio='$kukarow[yhtio]' and myyja>0 order by myyja";
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
	
	
	
	
	//* N‰m‰ sarakkeet eiv‰t v‰ltt‰m‰tt‰ lˆydy kaikista installaatioista (Laitemyyntispecific)*//
	if (mysql_field_name($result, $i) == "vari") {
		$query = "	SELECT *
					FROM avainsana
					WHERE yhtio='$kukarow[yhtio]' and laji = 'SARJANUMERON_LI' and selite = 'VARIRUNKO'
					ORDER BY jarjestys, selitetark_2";
		$vresult = mysql_query($query) or pupe_error($query);

		$ulos = "<td><select name='$nimi'>";
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
		$query = "	SELECT *
					FROM avainsana
					WHERE yhtio='$kukarow[yhtio]' and laji = 'SARJANUMERON_LI' and selite = 'TYYPPI'
					ORDER BY jarjestys, selitetark_2";
		$vresult = mysql_query($query) or pupe_error($query);

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
		$query = "	SELECT *
					FROM avainsana
					WHERE yhtio='$kukarow[yhtio]' and laji = 'SARJANUMERON_LI' and selite = 'RUNKOTYYPPI'
					ORDER BY jarjestys, selitetark_2";
		$vresult = mysql_query($query) or pupe_error($query);

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
		$query = "	SELECT *
					FROM avainsana
					WHERE yhtio='$kukarow[yhtio]' and laji = 'SARJANUMERON_LI' and selite = 'KILPI'
					ORDER BY jarjestys, selitetark_2";
		$vresult = mysql_query($query) or pupe_error($query);

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
		$query = "	SELECT *
					FROM avainsana
					WHERE yhtio='$kukarow[yhtio]' and laji = 'SARJANUMERON_LI' and selite = 'MATERIAALI'
					ORDER BY jarjestys, selitetark_2";
		$vresult = mysql_query($query) or pupe_error($query);

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
		$query = "	SELECT *
					FROM avainsana
					WHERE yhtio='$kukarow[yhtio]' and laji = 'SARJANUMERON_LI' and selite = 'KONEISTUS'
					ORDER BY jarjestys, selitetark_2";
		$vresult = mysql_query($query) or pupe_error($query);

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
		$query = "	SELECT *
					FROM avainsana
					WHERE yhtio='$kukarow[yhtio]' and laji = 'SARJANUMERON_LI' and selite = 'SPRINKLERI'
					ORDER BY jarjestys, selitetark_2";
		$vresult = mysql_query($query) or pupe_error($query);

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
