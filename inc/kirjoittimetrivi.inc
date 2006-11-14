<?php
	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys

	if (mysql_field_name($result, $i) == "tunnus" or
	    mysql_field_name($result, $i) == "yhtio") {
		$tyyppi = 0;
	}

	if (mysql_field_name($result, $i) == "merkisto") {
			$sel		= array();
			$pv			= $trow[$i];
			$sel[$pv]	= " selected";

			$ulos = "<td>
				<select name='$nimi'>
				<option value='0' $sel[0]>".t("Ei valintaa")."</option>
				<option value='1' $sel[1]>7 Bit</option>
				<option value='2' $sel[2]>DOS</option>
				<option value='3' $sel[3]>ANSI</option>
				<option value='4' $sel[4]>UTF8</option>
				</select></td>";
			$jatko = 0;
	}
?>
