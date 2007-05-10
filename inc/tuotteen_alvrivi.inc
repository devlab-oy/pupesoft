<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys

	if (mysql_field_name($result, $i) == "yhtio") {
		$tyyppi = 0;
	}

	if (mysql_field_name($result, $i) == "maa") {
		$query = "	SELECT distinct koodi, nimi
					FROM maat
					WHERE nimi != ''
					ORDER BY koodi";
		$vresult = mysql_query($query) or pupe_error($query);

		$ulos = "<td><select name='$nimi'>";

		while ($vrow = mysql_fetch_array($vresult)) {
			$sel = "";
			if ($trow[$i] == $vrow["koodi"]) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[koodi]' $sel>".t($vrow["nimi"])."</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}

?>
