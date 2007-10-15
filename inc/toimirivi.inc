<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys

	if ($i == 0) { //yhtiötä ei näytetä
		$tyyppi = 0;
	}
	
	if (mysql_field_name($result, $i) == "tilino") {
		if ($ulosarray[$i] != '') {
			$ulos=$ulosarray[$i];
			$jatko = 0;
		}
		else {
			$ulos = "<td><input type = 'text' name = '$nimi' value = '$trow[$i]' size='$size' maxlength='" . mysql_field_len($result,$i) ."'>";
			$query = "	SELECT nimi
						FROM tili
						WHERE yhtio='$kukarow[yhtio]' and tilino = '$trow[$i]'";
			$vresult = mysql_query($query) or pupe_error($query);
			if (mysql_num_rows($vresult) == 1) {
				$vrow=mysql_fetch_array($vresult);
				$ulos .= " " . $vrow[nimi] . "</td>";
				$jatko = 0;
			}
		}
	}
	
	if (mysql_field_name($result, $i) == "tilinumero") { //suoraveloitus suoritetaan automaattisesti 
		$maxsize++; //Tilaa mahdolliselle viivalle
	}

	if (mysql_field_name($result, $i) == "oletus_suoravel_pankki") { //suoraveloitus suoritetaan automaattisesti 

		$query = "SELECT nimi, tilino, tunnus
				FROM yriti
				WHERE yhtio = '$kukarow[yhtio]'";
		$vresult = mysql_query($query) or pupe_error($query);
		
		$sel="";
		if((int)$trow[$i] == 0) {
			$sel="selected";
		}
		$ulos = "<td><select name='$nimi'><option value = '0' $sel>".t("Ei suoriteta automaattisesti")."";

		while ($vrow=mysql_fetch_array($vresult)) {

			$sel="";
			if ($trow[$i] == $vrow['tunnus']) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[tunnus]' $sel>$vrow[nimi] ($vrow[tilino])";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if  ((mysql_field_name($result, $i) == "oletus_suoraveloitus") or
		(mysql_field_name($result, $i) == "oletus_hyvaksynnanmuutos") or
		(mysql_field_name($result, $i) == "konserniyhtio")) {

		$ulos  = "<td><select name='$nimi'>";
		
		$chk1 = '';
		$chk2 = '';

		if (trim($trow[$i]) == "") {
			$chk1 = 'selected';
		}
		else {
			$chk2 = 'selected';
		}

		$ulos .= "<option value=''  $chk1>".t("Ei")."</option>";
		$ulos .= "<option value='o' $chk2>".t("Kyllä")."</option>";
		
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "oletus_valkoodi") { //valuutta
		$query = "SELECT nimi, tunnus
					FROM valuu
					WHERE yhtio = '$kukarow[yhtio]'
					ORDER BY jarjestys";
		$vresult = mysql_query($query) or pupe_error($query);
		$ulos = "<td><select name='$nimi'>";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";

			if ($trow[$i] == $vrow['nimi']) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[nimi]' $sel>$vrow[nimi]";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}

    if ((mysql_field_name($result, $i) == "oletus_hyvak1") or
    	(mysql_field_name($result, $i) == "oletus_hyvak2") or
    	(mysql_field_name($result, $i) == "oletus_hyvak3") or
    	(mysql_field_name($result, $i) == "oletus_hyvak4") or
    	(mysql_field_name($result, $i) == "oletus_hyvak5")) { //hyväksyjät
		$query = "SELECT kuka, nimi
					FROM kuka
					WHERE yhtio = '$kukarow[yhtio]' and hyvaksyja = 'o'
					ORDER BY nimi";
		$vresult = mysql_query($query) or pupe_error($query);
		
		$sel="";
		if (trim($trow[$i]) == "") {
			$sel = "selected";
		}
		$ulos = "<td><select name='$nimi'><option value='' $sel>".t("Ei kukaan")."";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow['kuka']) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[kuka]' $sel>$vrow[nimi]";
		}
		$ulos .= "</select></td>";
		$jatko = 0;
	}

    if (mysql_field_name($result, $i) == "kustannuspaikka") { //kustannuspaikat
		$query = "	SELECT tunnus, nimi
					FROM kustannuspaikka
					WHERE yhtio = '$kukarow[yhtio]' and
					tyyppi = 'K'
					ORDER BY nimi";
		$vresult = mysql_query($query) or pupe_error($query);
		
		$sel="";
		if (trim($trow[$i]) == "") {
			$sel = "selected";
		}
		$ulos = "<td><select name='$nimi'><option value='' $sel>".t("Ei kustannuspaikkaa")."";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";

			if ($trow[$i] == $vrow['tunnus']) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[tunnus]' $sel>$vrow[nimi]";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "kohde") { //kohteet
		$query = "	SELECT tunnus, nimi
						FROM kustannuspaikka
						WHERE yhtio = '$kukarow[yhtio]' and
						tyyppi = 'O'
						ORDER BY nimi";
		$vresult = mysql_query($query) or pupe_error($query);

		$sel="";
		if (trim($trow[$i]) == "") {
			$sel = "selected";
		}
		$ulos = "<td><select name='$nimi'><option value='' $sel>".t("Ei kohdetta")."";
		
		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow['tunnus']) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[tunnus]' $sel>$vrow[tunnus]  - $vrow[nimi]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "projekti") { //projektit

		$query = "	SELECT tunnus, nimi
					FROM kustannuspaikka
					WHERE yhtio = '$kukarow[yhtio]' and
					tyyppi = 'P'
					ORDER BY nimi";
		$vresult = mysql_query($query) or pupe_error($query);

		$sel="";
		if (trim($trow[$i]) == "") {
			$sel = "selected";
		}
		$ulos = "<td><select name='$nimi'><option value='' $sel>".t("Ei projektia")."";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow['tunnus']) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[tunnus]' $sel>$vrow[tunnus] - $vrow[nimi]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;

	}

	if (mysql_field_name($result, $i) == "oletus_vienti") { //oletusvienti

		if ($trow[$i]=='A') $vientia='selected';
		if ($trow[$i]=='B') $vientib='selected';
		if ($trow[$i]=='C') $vientic='selected';
		if ($trow[$i]=='D') $vientid='selected';
		if ($trow[$i]=='E') $vientie='selected';
		if ($trow[$i]=='F') $vientif='selected';
		if ($trow[$i]=='G') $vientig='selected';
		if ($trow[$i]=='H') $vientih='selected';
		if ($trow[$i]=='I') $vientii='selected';

		$ulos = "<td>
			<select name='$nimi'>
				<option value='A' $vientia>".t("Kotimaa")."</option>
				<option value='B' $vientib>".t("Kotimaa huolinta/rahti")."</option>
				<option value='C' $vientic>".t("Kotimaa vaihto-omaisuus")."</option>
				<option value='D' $vientid>".t("EU")."</option>
				<option value='E' $vientie>".t("EU huolinta/rahti")."</option>
				<option value='F' $vientif>".t("EU vaihto-omaisuus")."</option>
				<option value='G' $vientig>".t("ei-EU")."</option>
				<option value='H' $vientih>".t("ei-EU huolinta/rahti")."</option>
				<option value='I' $vientii>".t("ei-EU vaihto-omaisuus")."</option>
			</select>
			</td>";

		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "kauppatapahtuman_luonne") {

		$query = "	SELECT avainsana.selite, ".avain('select')."
					FROM avainsana
					".avain('join','KT_')."
					WHERE avainsana.yhtio = '$kukarow[yhtio]' and avainsana.laji='KT'
					ORDER BY avainsana.jarjestys, avainsana.selite";
		$vresult = mysql_query($query) or pupe_error($query);
		$ulos = "<td><select name='$nimi'>";

		$sel="";
		if ((int)$trow[$i] == 0) {
			$sel = "selected";
		}
		$ulos .= "<option value = '' $sel>".t("Ei valintaa")."</option>";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if (strtoupper($trow[$i]) == strtoupper($vrow[0])) {
				$sel = "selected";
			}
			$ulos .= "<option value = '".strtoupper($vrow[0])."' $sel>$vrow[1]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "kuljetusmuoto") {
		$query = "	SELECT avainsana.selite, ".avain('select')."
					FROM avainsana
					".avain('join','KM_')."
					WHERE avainsana.yhtio = '$kukarow[yhtio]' and avainsana.laji='KM'
					ORDER BY avainsana.jarjestys, avainsana.selite";
		$vresult = mysql_query($query) or pupe_error($query);
		$ulos = "<td><select name='$nimi'>";

		$sel="";
		if ((int)$trow[$i] == 0) {
			$sel = "selected";
		}
		$ulos .= "<option value = '' $sel>".t("Ei valintaa")."</option>";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if (strtoupper($trow[$i]) == strtoupper($vrow[0])) {
				$sel = "selected";
			}
			$ulos .= "<option value = '".strtoupper($vrow[0])."' $sel>$vrow[1]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "maa") {
		$query = "	SELECT distinct koodi, nimi
					FROM maat
					WHERE nimi != ''
					ORDER BY koodi";
		$vresult = mysql_query($query) or pupe_error($query);
		$ulos = "<td><select name='$nimi'>";

		$ulos .= "<option value = '' ></option>";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if (strtoupper($trow[$i]) == strtoupper($vrow[0])) {
				$sel = "selected";
			}
			elseif($trow[$i] == "" and strtoupper($vrow[0]) == strtoupper($yhtiorow["maa"])) {
				$sel = "selected";
			}
			$ulos .= "<option value = '".strtoupper($vrow[0])."' $sel>".t($vrow[1])."</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "kieli") {	
			
		$ulos = "<td><select name='$nimi'>";
		$sel  = "";

		$query  = "show columns from sanakirja";
		$fields =  mysql_query($query);			
	
		while ($apurow = mysql_fetch_array($fields)) {				
			$sel = "";
			if ($trow[$i] == $apurow[0]) {
				$sel = "selected";
			}
			if ($apurow[0] != "tunnus") {
				$query = "select distinct nimi from maat where koodi='$apurow[0]'";
				$maare = mysql_query($query);
				$maaro = mysql_fetch_array($maare);
				$maa   = strtolower($maaro["nimi"]);
				if ($maa=="") $maa = $apurow[0];
				$ulos .= "<option value='$apurow[0]' $sel>".t("$maa")."</option>";
			}
		}
		
		$ulos .= "</select></td>";
		
		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "fakta") {
		$ulos = "<td><textarea cols='55' rows='7' name='$nimi'>$trow[$i]</textarea></td>";
		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "automaatti_toimitus") {

		$selo = $sels = "";
		if ($trow[$i] == 'S') $sels = "selected";
		else $selo = "selected";
		
		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value='' $selo>Ei automaattitoimitusta</option>";
		$ulos .= "<option value='S' $sels>Suoratoimitukset toimitetaan automaattisesti</option>";
		$ulos .= "</select></td>";
		
		$jatko = 0;
	}
	
	
	if (mysql_field_name($result, $i) == "tyyppi") {

		$sel = array();
		$sel[$trow[$i]] = "selected";
		
		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value=''>".t("Normaali toimittaja")."</option>";
		$ulos .= "<option value='O' $sel[O]>".t("Online toimittaja")."</option>";
		$ulos .= "<option value='S' $sel[S]>".t("Sisäinen toimittaja")."</option>";
		$ulos .= "<option value='L' $sel[L]>".t("Kaikkien tuotteiden toimittaja")."</option>";
		$ulos .= "<option value='P' $sel[P]>".t("Poistettu / Ei käytössä")."</option>";
		$ulos .= "</select></td>";
		
		$jatko = 0;
	}



?>