<?php

	$ulos   = "";
	$jatko  = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys

	// yhtiötä ei näytetä
	if ($i == 0) {
		$tyyppi = 0;
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

		if ($trow[$i] == 'N') {
			$chkn = 'SELECTED';
		}
		elseif($trow[$i] == "E") {
			$chke = 'SELECTED';
		}

		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value=''>".t("Bruttohinta")."</option>";
		$ulos .= "<option value='N' $chkn>".t("N-Nettohinta")."</option>";
		$ulos .= "<option value='E' $chke>".t("E-Nettohinta")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}

?>