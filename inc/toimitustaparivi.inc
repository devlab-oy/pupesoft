<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali k‰sittely
	$tyyppi = 1; // oletetaan rivin n‰kyvyys

	if (mysql_field_name($result, $i) == "tunnus" or
	    mysql_field_name($result, $i) == "yhtio") {
		$tyyppi = 0;
	}

	if (mysql_field_name($result, $i) == "nouto" or
		mysql_field_name($result, $i) == "lauantai" or
		mysql_field_name($result, $i) == "multi_jv" or
		mysql_field_name($result, $i) == "kuljyksikko") {

		$sel='';
		if ($trow[$i]!='') $sel = 'CHECKED';

		$ulos = "<td><input type='checkbox' name='$nimi' $sel></td>";
		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "merahti") {
		$sel1='';
		$sel2='';
		if ($trow[$i]=='K') 
			$sel1 = 'SELECTED';
		else
			$sel2 = 'SELECTED';

		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value='K' $sel1>".t("L‰hett‰j‰")."</option>";
		$ulos .= "<option value=''  $sel2>".t("Vastaanottaja")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "hetiera") {
			$sel		= array();
			$pv			= $trow[$i];
			$sel[$pv]	= " selected";

			$ulos = "<td>
				<select name='$nimi'>
				<option value='E' $sel[E]>".t("Er‰tulostus")."</option>
				<option value='H' $sel[H]>".t("Hetitulostus")."</option>
				<option value='K' $sel[K]>".t("Koontitulostus")."</option>
				</select></td>";
			$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "extranet") {
		$sel1='';
		$sel2='';
		if ($trow[$i]=='') 
			$sel1 = 'SELECTED';
		else
			$sel2 = 'SELECTED';

		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value=''  $sel1>".t("Ei n‰ytet‰")."</option>";
		$ulos .= "<option value='K' $sel2>".t("N‰ytet‰‰n")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "rahtikirja") {
		
		$query = "	SELECT selite, selitetark
					FROM avainsana
					WHERE yhtio = '$kukarow[yhtio]' and laji = 'RAHTIKIRJA'
					ORDER BY jarjestys, selite";
		$vresult = mysql_query($query) or pupe_error($query);

		$ulos = "<td><select name='$nimi'>";

		while($row = mysql_fetch_array($vresult)) {
			$sel = "";
			if ($row[0] == $trow[$i]) $sel = 'selected';
			$ulos .= "<option value='$row[0]' $sel>$row[1]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}
	
?>
