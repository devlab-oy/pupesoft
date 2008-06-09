<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys

	if (mysql_field_name($result, $i) == "tunnus" or
		mysql_field_name($result, $i) == "muutospvm" or
	    mysql_field_name($result, $i) == "yhtio") {
		$tyyppi = 0;
	}

	if (mysql_field_name($result, $i) == "toimitustapa") {
		$query = "	SELECT * from toimitustapa
					WHERE yhtio='$kukarow[yhtio]'";
		$sresult = mysql_query($query) or pupe_error($query);

		$ulos = "<td><select name='$nimi'>";
		while ($srow = mysql_fetch_array($sresult)) {
			$sel = '';
			if ($trow[$i] == $srow['selite'] or $srow['selite'] == $toimitustapa) {
				$sel = "selected";
			}
			$ulos .= "<option value='$srow[selite]' $sel>".asana('TOIMITUSTAPA_',$srow['selite'])."</option>";
		}
		$ulos .= "</select></td>";
		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "ytunnus" and !isset($alasveto[$i]) and $ytunnus != "") {
		$ulos = "<td><input type = 'text' name = '$nimi' value = '$ytunnus' size='$size' maxlength='" . mysql_field_len($result,$i) ."'>";
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
?>
