<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys
	
	if ($i == 0) { //yhtiötä ei näytetä
		$tyyppi = 0; 
	}
	
	if ((mysql_field_name($result, $i) == "jv") 
		or (mysql_field_name($result, $i) == "suoraveloitus") 
		or (mysql_field_name($result, $i) == "erapvmkasin") 
		or (mysql_field_name($result, $i) == "itsetulostus") 
		or (mysql_field_name($result, $i) == "factoring")) {
		$sel = '';
		if ($trow[$i] == 'o') {
			$sel = "checked";
		}
		$ulos = "<td><input type='checkbox' name='$nimi' $sel>";
		$ulos .= "</td>";
		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "kateinen") {
		
		$sel1 = '';
		$sel2 = '';
		$sel3 = '';
		
		if ($trow[$i] == '') {
			$sel1 = "checked";
		}
		if ($trow[$i] == 'o') {
			$sel2 = "checked";
		}
		if ($trow[$i] == 'p') {
			$sel3 = "checked";
		}
		
		$ulos  = "<td>";				
		$ulos .= "<input type='radio' name='$nimi' value=''  $sel1> ".t("Tämä ei ole käteismaksuehto.")."<br>";
		$ulos .= "<input type='radio' name='$nimi' value='o' $sel2> ".t("Käteismaksuehto, ei pyöristetä.")."<br>";
		$ulos .= "<input type='radio' name='$nimi' value='p' $sel3> ".t("Käteismaksuehto, pyöristetään.")."";		
		$ulos .= "</td>";
		
		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "osamaksuehto1") {
		$query = "	SELECT tunnus, concat(teksti,' ',kassa_teksti)
					FROM maksuehto
					WHERE yhtio = '$kukarow[yhtio]'
					ORDER BY jarjestys";
		$vresult = mysql_query($query) or pupe_error($query);
		$ulos = "<td><select name='$nimi'>";
		$ulos .= "<option value = ''>".t("Tämä ei ole moniehto-maksuehto");

		while ($vrow=mysql_fetch_row($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow[0]) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[0]' $sel>$vrow[1]";
		}
		$ulos .= "</select></td>";
		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "osamaksuehto2") {
		$query = "	SELECT tunnus, concat(teksti,' ',kassa_teksti)
					FROM maksuehto
					WHERE yhtio = '$kukarow[yhtio]'
					ORDER BY jarjestys";
		$vresult = mysql_query($query) or pupe_error($query);
		$ulos = "<td><select name='$nimi'>";
		
		$ulos .= "<option value = ''>".t("Tämä ei ole moniehto-maksuehto");
		
		while ($vrow=mysql_fetch_row($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow[0]) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[0]' $sel>$vrow[1]";
		}
		$ulos .= "</select></td>";
		$jatko = 0;
	}
	
?>
