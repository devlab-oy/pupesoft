<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali k‰sittely
	$tyyppi = 1; // oletetaan rivin n‰kyvyys
	
	if ($i == 0) { //yhtiˆt‰ ei n‰ytet‰
		$tyyppi = 0;
	}
	
	if (mysql_field_name($result, $i) == "ryhma") {
		$query = "	SELECT ryhma, if (ryhma!=selite, concat_ws(' - ', ryhma, selite), ryhma) selitetark
					FROM perusalennus
					WHERE yhtio='$kukarow[yhtio]'
					ORDER BY ryhma";
		$sresult = mysql_query($query) or pupe_error($query);
		$ulos = "<td><select name='$nimi'>";
		$ulos .= "<option value = '' >".t("Ei ryhm‰‰")."</option>";

		while ($srow = mysql_fetch_array($sresult)) {
			$sel = '';
			if ($trow[$i] == $srow["ryhma"]) {
				$sel = "selected";
			}
			$ulos .= "<option value='$srow[ryhma]' $sel>$srow[selitetark]</option>";
		}
		$ulos .= "</select></td>";
		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "asiakas_ryhma") {
		$query = "	SELECT avainsana.selite, ".avain('select')."
					FROM avainsana
					".avain('join','ASRYHMA_')."
					WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji = 'ASIAKASRYHMA'
					ORDER BY avainsana.jarjestys, avainsana.selite";
		$vresult = mysql_query($query) or pupe_error($query);

		$ulos = "<td><select name='$nimi'>";
		$ulos .= "<option value = '' >".t("Ei ryhm‰‰")."</option>";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow['selite']) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[selite]' $sel>$vrow[selite] - $vrow[selitetark]</option>";
		}
       	
		$ulos .= "</select></td>";
		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "ytunnus" and isset($alasveto[$i])) {
		$ulos = "<td>".$alasveto[$i]."</td>";
		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "asiakas" and isset($alasveto[$i])) {
		$ulos = "<td>".$alasveto[$i]."</td>";
		$jatko = 0;
	}
	elseif (mysql_field_name($result, $i) == "asiakas") {	
		if ((int) $trow[$i] > 0) {
			$query	= "	SELECT *
						FROM asiakas
						WHERE yhtio='$kukarow[yhtio]' and tunnus='$trow[$i]'";
			$sresult = mysql_query($query) or pupe_error($query);
			$srow = mysql_fetch_array($sresult);
			$ssize = strlen($trow[$i])+2;
				
			$ulos = "<td nowrap><input type = 'text' name = '$nimi' value = '$trow[$i]' size='$ssize' maxlength='$maxsize'> <table><tr><td>$srow[nimi] $srow[nimitark]</td></tr><tr><td>$srow[toim_nimi] $srow[toim_nimitark]</td></tr></table></td>";
			$jatko = 0;		
		}
		else {
			$ulos = "<td nowrap><input type = 'text' name = '$nimi' value = '' size='$size' maxlength='$maxsize'></td>";
			$jatko = 0;	
		}
	}
	
	if (mysql_field_name($result, $i) == "alkupvm" or 
		mysql_field_name($result, $i) == "loppupvm") {
		$tyyppi = 1.5;
	}
	
	if (mysql_field_name($result, $i) == "tuoteno" and isset($alasveto[$i])) {
		$ulos = "<td>".$alasveto[$i]."</td>";
		$jatko = 0;
	}

?>