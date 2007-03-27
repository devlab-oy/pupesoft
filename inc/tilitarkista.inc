<?php

if (mysql_field_name($result, $i) == "tilino" and trim($t[$i]) != '') {

	$query   = "select tunnus from tili where yhtio='$kukarow[yhtio]' and tilino='$t[$i]' and tunnus != '$tunnus'";
	$sresult = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($sresult) > 0) {
		$virhe[$i] = t("Tämä tilinumero on jo perustettu.");
		$errori = 1;
	}

}

?>