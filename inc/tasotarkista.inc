<?php

if (mysql_field_name($result, $i) == "tyyppi") {
	$tyyppitalteen = $t[$i];
}

if (mysql_field_name($result, $i) == "taso") {

	if (trim($t[$i]) == '') {
		$virhe[$i] = t("Taso on syötettävä!");
		$errori = 1;
	}
	else {
		$query   = "select tunnus from taso where yhtio='$kukarow[yhtio]' and taso = BINARY '$t[$i]' and tyyppi='$tyyppitalteen' and tunnus != '$tunnus'";
		$sresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($sresult) > 0) {
			$virhe[$i] = t("Tämä taso on jo perustettu!");
			$errori = 1;
		}
	}
}

if (mysql_field_name($result, $i) == "nimi" and trim($t[$i]) == '') {
	$virhe[$i] = t("Syötä nimitys!");
	$errori = 1;
}

?>