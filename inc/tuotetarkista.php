<?php
	if ((mysql_field_name($result, $i) == "nimitys") or
		(mysql_field_name($result, $i) == "try")) {
		$pakko[$i]=1;
		$virhe[$i] = "".t("Tieto puuttuu")."";
		$errori = 1;
	}
?>