<?php

	$ulos = "";
	$jatko = 1; 	// oletetaan normaali käsittely
	$tyyppi = 1; 	// oletetaan rivin näkyvyys
	if ($i == 0) { 	//yhtiötä ei näytetä
		$tyyppi = 0;
	}

	if (mysql_field_name($result, $i) == "ryhma") {
		$query = "	SELECT ryhma, concat_ws(' - ', ryhma, selite) ryhma
					FROM perusalennus
					WHERE yhtio='$kukarow[yhtio]'
					ORDER BY ryhma+0";
		$sresult = mysql_query($query) or pupe_error($query);
		$ulos = "<td><select name='$nimi'>";
		$ulos .= "<option value = '' >".t("Ei ryhmää")."</option>";

		while ($srow = mysql_fetch_array($sresult)) {
			$sel = '';
			if ($trow[$i] == $srow[0]) {
				$sel = "selected";
			}
			$ulos .= "<option value='$srow[0]' $sel>$srow[1]</option>";
		}
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "asiakas_ryhma") {
		$query = "	SELECT avainsana.selite, ".avain('select')."
					FROM avainsana
					".avain('join','ASRYHMA_')."
					WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji = 'ASIAKASRYHMA'
					ORDER BY avainsana.jarjestys";
		$vresult = mysql_query($query) or pupe_error($query);

		$ulos = "<td><select name='$nimi'>";
		$ulos .= "<option value = '' >".t("Ei ryhmää")."</option>";

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


	if (mysql_field_name($result, $i) == "valkoodi") {
		$query = "	SELECT nimi, tunnus
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
	
	if (mysql_field_name($result, $i) == "laji") {

		$chkn = Array();
		if ($trow[$i] == 'N') {
			$chkn[0] = 'SELECTED';
		}
		elseif($trow[$i] == "E") {
			$chke[1] = 'SELECTED';
		}
		elseif($trow[$i] == "") {
			$chke[2] = 'SELECTED';
		}

		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value='N' $chkn[0]>".t("N-Nettohinta")."</option>";
		$ulos .= "<option value='E' $chke[1]>".t("E-Nettohinta")."</option>";
		$ulos .= "<option value='' $chke[2]>".t("Yhtiön oletus")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "ytunnus" and isset($alasveto[$i])) {
		$ulos = "<td>".$alasveto[$i]."</td>";
		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "tuoteno" and isset($alasveto[$i])) {
		$ulos = "<td>".$alasveto[$i]."</td>";
		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "alkupvm" or 
		mysql_field_name($result, $i) == "loppupvm") {
		$tyyppi = 1.5;
	}
	


?>
