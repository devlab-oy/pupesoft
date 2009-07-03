<?php

	$ulos   = "";
	$jatko  = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys

	if (mysql_field_name($result, $i) == "yhtio") {
		$tyyppi = 0;
	}
	
	if (mysql_field_name($result, $i) == "tuoteno") {
		if ($lukitse_avaimeen != "") {
			$ulos = "<td><input type='hidden' name='$nimi' value='$lukitse_avaimeen'>$lukitse_avaimeen</td>";
			$jatko = 0;
		}
	}
	
	if (mysql_field_name($result, $i) == "toimittaja") {
		if(isset($alasveto[$i])) {
			$ulos = "<td>".$alasveto[$i]."</td>";
			$jatko = 0;
		}
		else {
			$query = "	SELECT * 
						from toimi 
						where yhtio = '$kukarow[yhtio]'
						and tyyppi != 'P' 
						and tunnus = ".(int) $trow["liitostunnus"];
			$sresult = mysql_query($query) or pupe_error($query);
			
			if (mysql_num_rows($sresult) == 1) {
				$srow = mysql_fetch_assoc($sresult);
					
				$ulos = "<td>
						<input type='hidden' name='toimi_liitostunnus' value='$srow[tunnus]'>
						<input type='hidden' name='toimi_ytunnus' value='$srow[ytunnus]'>
						<input type = 'text' name = '$nimi' value = '$trow[$i]' size='$size' maxlength='$maxsize'> $srow[nimi] $srow[nimitark]</td>";
				$jatko=0;
			}	
		}
	}
	
	if (mysql_field_name($result, $i) == "liitostunnus") {
		$tyyppi = 4;
	}
	
	if (mysql_field_name($result, $i) == "alkuperamaa") {
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
	
	if (mysql_field_name($result, $i) == "valuutta") {
		$query = "	SELECT nimi, tunnus
					FROM valuu
					WHERE yhtio = '$kukarow[yhtio]'
					ORDER BY jarjestys";
	        $vresult = mysql_query($query) or pupe_error($query);
	        $ulos = "<td><select name='$nimi'>";
	        while ($vrow=mysql_fetch_array($vresult)) {
		        $sel="";
		        if ($trow[$i] == $vrow[0]) {
		                $sel = "selected";
		        }
	        	$ulos .= "<option value = '$vrow[0]' $sel>$vrow[0]</option>";
	        }
		$ulos .= "</select></td>";
		$jatko = 0;
	}
?>
