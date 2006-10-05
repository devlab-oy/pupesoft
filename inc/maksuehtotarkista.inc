<?php

if (mysql_field_name($result, $i) == "summanjakoprososa2") {
	
	$prossa = (int) $t[$i];
	
	if ($yhtiorow["maksuehto_tuotenumero"] == "" and $prossa != 0) {
		$virhe[$i] = "".t("Et voi tehdä moniehto-maksuehtoa koska yhtiöltä puuttuu maksuehto_tuotenumero!");	
		$errori = 1;
	}
	if ($prossa!= 0) {
	    $moniehto = 1;
    }
}

if (mysql_field_name($result, $i) == "jv") {	
	if (trim($t[$i]) != "" and $moniehto != 0) {
		$virhe[$i] = t("Et voi tehdä tästä jv-maksuehtoa koska tämä on moniehto-maksuehto!");	
		$errori = 1;
	}	
}

if (mysql_field_name($result, $i) == "kateinen") {
	if (trim($t[$i]) != "" and $moniehto != 0) {
		$virhe[$i] = t("Et voi tehdä tästä käteismaksuehtoa koska tämä on moniehto-maksuehto!");	
		$errori = 1;
	}	
}


?>