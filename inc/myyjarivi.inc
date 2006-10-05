<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys
	if ($i == 0) { //yhtiötä ei näytetä
		$tyyppi = 0;
	}

	if (mysql_field_name($result, $i) == "osasto") {
		$query = "	SELECT tunnus, selite
					FROM avainsana
					WHERE yhtio = '$kukarow[yhtio]' and laji = 'O'
					ORDER BY selite";
		$vresult = mysql_query($query) or pupe_error($query);
		$ulos = "<td><select name='$nimi'>";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow[0]) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[0]' $sel>$vrow[1]";
		}
		$ulos .= "</select></td></tr>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "paallikko") {
		$ulos = "<td><select name='$nimi'>";
		if($trow[3] == 1){
			$selP = 'selected';
		}
		$ulos .= "<option value = '0'>".t("Ei")."";
		$ulos .= "<option value = '1' $selP>".t("Kyllä")."";
		$ulos .= "</select></td></tr>";
		$jatko = 0;
	}
?>
