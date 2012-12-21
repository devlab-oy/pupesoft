<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys

	if (mysql_field_name($result, $i) == "tunnus" or
	    mysql_field_name($result, $i) == "yhtio") {
		$tyyppi = 0;
	}

	if (substr(mysql_field_name($result,$i),0,9) == 'printteri') {

		$sel = array();
		$sel[$trow[$i]] = "selected";

		$ulos = "<td><select name='$nimi'>";

		$query = "	SELECT *
					FROM kirjoittimet
					WHERE yhtio = '$kukarow[yhtio]'
					AND komento != 'EDI'
					ORDER BY kirjoitin";
		$kires = mysql_query($query) or pupe_error($query);

		$ulos .= "<option value=''>".t("Ei kirjoitinta")."</option>";

		while ($kirow = mysql_fetch_array($kires)) {
			if ($kirow["tunnus"] == $row[$i]) {
				$select = 'SELECTED';
			}
			else {
				$select = '';
			}

			$ulos .= "<option value='$kirow[tunnus]' ".$sel[$kirow["tunnus"]].">$kirow[kirjoitin]</option>";
		}

		$ulos .= "</select></td>";

		$jatko=0;
	}

	if (mysql_field_name($result, $i) == "varasto") {
		$sel = array();
		$sel[$trow[$i]] = "selected";

		$ulos = "<td><select name='$nimi'>";

		$query = "	SELECT *
					FROM varastopaikat
					WHERE yhtio = '$kukarow[yhtio]' AND tyyppi != 'P'
					ORDER BY tyyppi, nimitys";
		$varasto_res = mysql_query($query) or pupe_error($query);

		while ($varasto_row = mysql_fetch_array($varasto_res)) {
			if ($varasto_row["tunnus"] == $row[$i]) {
				$select = 'SELECTED';
			}
			else {
				$select = '';
			}

			$ulos .= "<option value='$varasto_row[tunnus]' ".$sel[$varasto_row["tunnus"]].">$varasto_row[nimitys]</option>";
		}

		$ulos .= "</select></td>";

		$jatko = 0;
	}
?>