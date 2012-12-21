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
				<option value='5' $sel[5]>Scandic off</option>
				</select></td>";
			$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "mediatyyppi") {
			$sel		= array();
			$pv			= $trow[$i];
			$sel[$pv]	= " selected";

			$ulos = "<td>
				<select name='$nimi'>
				<option value=''>".t("Ei valintaa")."</option>
				<option value='A4' $sel[A4]>A4</option>
				<option value='A5' $sel[A5]>A5</option>
				<option value='LSN149X104' $sel[LSN149X104]>".t("Lämpösiirto/nauha")." 149X104mm</option>
				<option value='LSN59X40' $sel[LSN59X40]>".t("Lämpösiirto/nauha")." 59X40mm</option>
				<option value='LS149X104' $sel[LS149X104]>".t("Lämpösiirto")." 149X104mm</option>
				<option value='LS59X40' $sel[LS59X40]>".t("Lämpösiirto")." 59X40mm</option>
				</select></td>";
			$jatko = 0;
	}
