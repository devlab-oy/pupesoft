<?php
	
	if (mysql_field_name($result, $i) == "selite" and trim($t[$i]) == '') {
		$seliteerrori = 1;
	}
	if ($seliteerrori == 1 and mysql_field_name($result, $i) == "selitetark" and trim($t[$i]) == '') {
		$virhe[$i] = t("Selite ja selitetark ei voi olla tyhjiä")."!";
		$errori = 1;
	}

?>