<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys

	if (mysql_field_name($result, $i) == "tunnus" or
	    mysql_field_name($result, $i) == "yhtio") {
		$tyyppi = 0;
	}

	if (mysql_field_name($result, $i) == "nouto") {

		$sel='';
		if ($trow[$i]!='') $sel = 'SELECTED';

		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value=''>".t("Ei noutoa")."</option>";
		$ulos .= "<option value='o' $sel>".t("Asiakas noutaa")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "lauantai") {

		$sel='';
		if ($trow[$i]!='') $sel = 'SELECTED';

		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value=''>".t("Ei lauantaijakelua")."</option>";
		$ulos .= "<option value='o' $sel>".t("Lauantaijakelu")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "multi_jv") {

		$sel='';
		if ($trow[$i]!='') $sel = 'SELECTED';

		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value=''>".t("Ei käytössä")."</option>";
		$ulos .= "<option value='o' $sel>".t("Ei käytössä")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "kuljyksikko") {

		$sel='';
		if ($trow[$i]!='') $sel = 'SELECTED';

		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value=''>".t("Ei")."</option>";
		$ulos .= "<option value='o' $sel>".t("Kyllä")."</option>";
		$ulos .= "</select></td>";
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
		$ulos .= "<option value='K' $sel1>".t("Lähettäjä")."</option>";
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
				<option value='E' $sel[E]>".t("Erätulostus")."</option>
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
		$ulos .= "<option value=''  $sel1>".t("Ei näytetä")."</option>";
		$ulos .= "<option value='K' $sel2>".t("Näytetään")."</option>";
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
