<?php

if (mysql_field_name($result, $i) == "asiakas") {
	$query = "	SELECT ytunnus
				FROM asiakas
				WHERE yhtio='$kukarow[yhtio]' and tunnus = '$t[$i]'";
	$sresult = mysql_query($query) or pupe_error($query);
	
	if (mysql_num_rows($sresult) < 1) {
		$virhe[$i] = "".t("Asiakas puuttuu tai sitä ei löydy!")."";
		$errori = 1;
	}
	
	$asiakas = $t[$i];
}

// tarkistetaan onko kohde jo olemassa
if (mysql_field_name($result, $i) == "kohde") {
	$query = "	SELECT tunnus
				FROM asiakkaan_kohde
				WHERE yhtio='$kukarow[yhtio]' and kohde = '$t[$i]' and tunnus = '$asiakas'";
	$sresult = mysql_query($query) or pupe_error($query);
	if(mysql_num_rows($sresult)>1) {
		$virhe[$i] = t("Asiakkaalla on jo tämä kohde perustettuna!");
		$errori = 1;		
	}
}

if (mysql_field_name($result, $i) == "lisannyt") {
	if ($tunnus == "") {
		$t[$i] = $kukarow['kuka'];
	}
}

if (mysql_field_name($result, $i) == "lisatty") {
	if ($tunnus == "") {
		$t[$i] = date('Y-m-d H:i:s');
	}
}

if (mysql_field_name($result, $i) == "muokannut") {
	$t[$i] = $kukarow['kuka'];
}

if (mysql_field_name($result, $i) == "muokattu") {
	$t[$i] = date('Y-m-d H:i:s');
	
}

?>
