<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys

	if (mysql_field_name($result, $i) == "tunnus" or
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
			if ($trow[$i] == $srow['selite']) {
				$sel = "selected";
			}
			$ulos .= "<option value='$srow[selite]' $sel>$srow[selite]</option>";
		}
		$ulos .= "</select></td>";
		$jatko = 0;
	}
?>
