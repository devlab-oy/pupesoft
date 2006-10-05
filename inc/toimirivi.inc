<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys
	if ($i == 0) { //yhtiötä ei näytetä
		$tyyppi = 0;
	}
	if (mysql_field_name($result, $i) == "oletus_suoravel_pankki") { //suoraveloitus suoritetaan automaattisesti 
		$query = "SELECT nimi, tilino, tunnus
                          FROM yriti
	                  WHERE yhtio = '$kukarow[yhtio]'";
	        $vresult = mysql_query($query) or pupe_error($query);
	        $ulos = "<td><select name='$nimi'><option value = '0'>".t("Ei suoriteta automaattisesti")."";
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
	if (mysql_field_name($result, $i) == "oletus_suoraveloitus") {
		$sel = '';
		if ($trow[$i] == 'o') {
			$sel = "checked";
		}
		$ulos = "<td><input type='checkbox' name='$nimi' $sel>";
		$ulos .= "</td>";
		$jatko = 0;
	}
	if (mysql_field_name($result, $i) == "oletus_hyvaksynnanmuutos") {
		$sel = '';
		if ($trow[$i] == 'o') {
			$sel = "checked";
		}
		$ulos = "<td><input type='checkbox' name='$nimi' $sel>";
		$ulos .= "</td>";
		$jatko = 0;
	}
	if (mysql_field_name($result, $i) == "konserniyhtio") {
		$sel = '';
		if ($trow[$i] == 'o') {
			$sel = "checked";
		}
		$ulos = "<td><input type='checkbox' name='$nimi' $sel>";
		$ulos .= "</td>";
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
		$ulos = "<td><select name='$nimi'><option value=' '>".t("Ei kukaan")."";
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
		$ulos = "<td><select name='$nimi'><option value=' '>".t("Ei kustannuspaikkaa")."";
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
	        $ulos = "<td><select name='$nimi'><option value=' '>".t("Ei kohdetta")."";
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
	        $ulos = "<td><select name='$nimi'><option value=' '>".t("Ei projektia")."";
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
	
	if (mysql_field_name($result, $i) == "maakoodi") {
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
			elseif($trow[$i] == "" and strtoupper($vrow[0]) == strtoupper($yhtiorow["maakoodi"])) {
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
	
	if (mysql_field_name($result, $i) == "tyyppi") {

		$selo = $sels = "";
		if ($trow[$i] == 'O') $selo = "selected";
		if ($trow[$i] == 'S') $sels = "selected";

		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value=''>Normaali toimittaja</option>";
		$ulos .= "<option value='O' $selo>Online toimittaja</option>";
		$ulos .= "<option value='S' $sels>Sisäinen toimittaja</option>";
		$ulos .= "</select></td>";
		
		$jatko = 0;
	}

?>