<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys

	
	if (substr(mysql_field_name($result,$i),0,9) == 'printteri') {

		$sel = array();
		$sel[$trow[$i]] = "selected";

		$ulos = "<td><select name='$nimi'>";
		
		$query = "select * from kirjoittimet where yhtio='$kukarow[yhtio]'";
		$kires = mysql_query($query) or pupe_error($query);

		$ulos .= "<option value=''>".t("Ei kirjoitinta")."</option>";

		while ($kirow=mysql_fetch_array($kires)) {
			if ($kirow["tunnus"]==$row[$i]) $select='SELECTED';
			else $select = '';

			$ulos .= "<option value='$kirow[tunnus]' ".$sel[$kirow["tunnus"]].">$kirow[kirjoitin]</option>";
		}

		$ulos .= "</select></td>";

		$jatko=0;
	}
	
	if (mysql_field_name($result,$i) == 'tyyppi') {
		$sel = array();
		$sel[$trow[$i]] = "selected";

		$ulos = "<td><select name='$nimi'>";
		
		$ulos .= "<option value=''>".t("Normaalivarasto josta kaikki myyvät")."</option>";
		$ulos .= "<option value='V' $sel[V]>".t("Erikoisvarasto josta voi myydä")."</option>";
		$ulos .= "<option value='E' $sel[E]>".t("Erikoisvarasto josta automaattisesti ei myydä")."</option>";
		$ulos .= "</select>";

		$ulos .= "</select></td>";

		$jatko=0;
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
			if ($trow[$i] == $vrow[0]) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[0]' $sel>$vrow[1]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "tunnus" or
	    mysql_field_name($result, $i) == "yhtio") {
		$tyyppi = 0;
	}
		
	if (mysql_field_name($result, $i) == "tuoteno" and $uusi != 0) {
		$tyyppi = 1;
	}
		
?>
