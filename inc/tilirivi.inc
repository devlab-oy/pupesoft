<?php

	$ulos   = "";
	$jatko  = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys

	// yhtiötä ei näytetä
	if ($i == 0) {
		$tyyppi = 0;
	}

	if (mysql_field_name($result, $i) == "oletusalv") {
			require ('alvpopup.inc');
			if ($trow[$i]=='') $trow[$i]=alv_oletus();
			$ulos = "<td>".alv_popup($nimi,$trow[$i])."</td>";
			$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "sisainen_taso") {

		$sel = array();
		$sel[$trow[$i]] = "SELECTED";

		$query = "select * from taso where yhtio='$kukarow[yhtio]' and tyyppi='S' order by taso+0";
		$tasoresu = mysql_query($query) or pupe_error($query);

		$ulos = "<td><select name='$nimi'>\n";
		$ulos .= "<option value = ''>Ei tasoa</option>\n";

		while ($tasorow = mysql_fetch_array($tasoresu)) {
			$tasoapu = $tasorow["taso"];
			$ulos .= "<option value = '$tasorow[taso]' $sel[$tasoapu]>$tasorow[taso] - $tasorow[nimi]</option>\n";
		}

		$ulos .= "</select></td>\n";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "ulkoinen_taso") {

		$sel = array();
		$sel[$trow[$i]] = "SELECTED";

		$query = "select * from taso where yhtio='$kukarow[yhtio]' and tyyppi='U' order by taso+0";
		$tasoresu = mysql_query($query) or pupe_error($query);

		$ulos = "<td><select name='$nimi'>\n";
		$ulos .= "<option value = ''>Ei tasoa</option>\n";

		while ($tasorow = mysql_fetch_array($tasoresu)) {
			$tasoapu = $tasorow["taso"];
			$ulos .= "<option value = '$tasorow[taso]' $sel[$tasoapu]>$tasorow[taso] - $tasorow[nimi]</option>\n";
		}

		$ulos .= "</select></td>\n";
		$jatko = 0;
	}

?>