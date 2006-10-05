<?php
	
	if (mysql_field_name($result, $i) == "selite" and trim($t[$i]) == '') {
		$virhe[$i] = t("Selite ei voi olla tyhjä")."!";
		$errori = 1;
	}

?>