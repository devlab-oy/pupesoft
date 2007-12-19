<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys

	//yhtiötä ei näytetä
	if (mysql_field_name($result, $i) == "yhtio") {
		$tyyppi = 0;
	}

	if (mysql_field_name($result, $i) == "laji") {

		$ulos = "<td><select name='$nimi'>";

		$query = "	SELECT distinct selite, selitetark
					FROM avainsana
					WHERE yhtio='$kukarow[yhtio]' and laji='TUOTEULK'";
		$sresult = mysql_query($query) or pupe_error($query);
		
		if (mysql_num_rows($sresult) == 0) {
			$ulos .= "<option value='nimitys_en'>".t("Tuotteen englanninkielinen nimitys")."</option>";
			$ulos .= "<option value='nimitys_se'>".t("Tuotteen ruotsinkielinen nimitys")."</option>";
			$ulos .= "<option value='nimitys_ee'>".t("Tuotteen eestinkielinen nimitys")."</option>";
		}
		
		while ($srow = mysql_fetch_array($sresult)) {
			$sel = '';
			if ($trow[$i] == $srow[0]) {
				$sel = "selected";
			}
			$ulos .= "<option value='$srow[0]' $sel>$srow[0] $srow[1]</option>";
		}
		$ulos .= "</select></td>";
		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "tuoteno" and isset($alasveto[$i])) {
		$ulos = "<td>".$alasveto[$i]."</td>";
		$jatko = 0;
	}

?>
