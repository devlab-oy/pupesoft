<?php

if (mysql_field_name($result, $i) == "pankkitili1" or
	mysql_field_name($result, $i) == "pankkitili2" or
	mysql_field_name($result, $i) == "pankkitili3") {

	if (strtoupper($yhtiorow['maakoodi']) == "FI" and trim($t[$i]) != "") {

		$pankkitili = $t[$i];

		require ('pankkitilinoikeellisuus.php');

		if ($pankkitili == '') {
			$virhe[$i] = t("Pankkitili on virheellinen");
			$errori = 1;
		}
	}
}

?>