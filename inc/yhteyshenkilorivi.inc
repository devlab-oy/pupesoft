<?php
	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys

	if ($i == 0) { //yhtiötä ei näytetä
		$tyyppi = 0;
	}
	
	if($popparista or $ajax_menu_yp != "") {
		if (mysql_field_name($result, $i) == "liitostunnus") {
			if ($liitostunnus != ''){
				$trow[$i] = $liitostunnus;
			}
			$tyyppi = 4;
		}
	}
	else {
		if (mysql_field_name($result, $i) == "asiakas") {
			if ($liitostunnus != ''){
				$trow[$i] = $liitostunnus;
			}
			$tyyppi = 3;
		}		
	}

	if (mysql_field_name($result, $i) == "tyyppi") {
		if($trow[$i] == "") {
			if($yhtyyppi == "asiakas") {
				$trow[$i]="A";
			}
			elseif($yhtyyppi == "toimi") {
				$trow[$i]="T";
			}
			$tyyppi=4;
		}
		else {
			$tyyppi=0;
		}
	}
	
	if (mysql_field_name($result, $i) == "rooli") {

			$query = "	SELECT avainsana.selite, avainsana.selitetark
						FROM avainsana
						WHERE avainsana.yhtio 	= '$kukarow[yhtio]' 
						and avainsana.laji 		= 'CRM_ROOLI'
						ORDER BY avainsana.jarjestys, avainsana.selite";
			$vresult = mysql_query($query) or pupe_error($query);

			$ulos = "<td><select name='$nimi'><option value=''>".t("Ei roolia")."</option>";

			while($row = mysql_fetch_array($vresult)) {
				$sel = "";
				if ($row[0] == $trow[$i]) $sel = 'selected';
				echo "<option value='$row[0]' $sel> $row[0] $row[1]</option>";
			}

			$ulos .= "</select></td>";
			$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "suoramarkkinointi") {

			$query = "	SELECT avainsana.selite, avainsana.selitetark
						FROM avainsana
						WHERE avainsana.yhtio 	= '$kukarow[yhtio]' 
						and avainsana.laji 		= 'CRM_SUORAMARKKI'
						ORDER BY avainsana.jarjestys, avainsana.selite";
			$vresult = mysql_query($query) or pupe_error($query);

			$ulos = "<td><select name='suoramarkkinointi'><option value=''>".t("Ei suoramarkkinointitietoja")."</option>";

			while($row = mysql_fetch_array($vresult)) {
				$sel = "";
				if ($row[0] == $trow[$i]) $sel = 'selected';
				echo "<option value='$row[0]' $sel> $row[0] $row[1]</option>";
			}

			$ulos .= "</select></td>";
			$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "tilausyhteyshenkilo") {
		
		
		if ($trow[$i] == "o") {
			$sel = "SELECTED";
		}
		
		
		$ulos = "<td><select name='$nimi'>";
		$ulos .= "<option value='' >".t("Ei")."</option>";
		$ulos .= "<option value='K' $sel>".t("Kyllä")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "oletusyhteyshenkilo") {
		
		
		if ($trow[$i] == "o") {
			$sel = "CHECKED";
		}
		
		
		$ulos = "<td><input type='checkbox' name='oletusyhteyshenkilo' $sel $state></td>";
		$jatko = 0;
	}
	
?>
