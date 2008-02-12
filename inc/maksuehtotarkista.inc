<?php

if(!function_exists("maksuehtotarkista")) {
	function maksuehtotarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {
		global $kukarow, $yhtiorow, $alias_set;
		
		static $prossa, $moniehto, $kassa_txt, $kassa_pros;
		if (mysql_field_name($result, $i) == "summanjakoprososa2") {

			$prossa = (int) $t[$i];

			if ($yhtiorow["maksuehto_tuotenumero"] == "" and $prossa != 0) {
				$virhe[$i] = "".t("Et voi tehdä moniehto-maksuehtoa koska yhtiöltä puuttuu maksuehto_tuotenumero!");	
			}
			if ($prossa!= 0) {
			    $moniehto = 1;
		    }
		}

		if (mysql_field_name($result, $i) == "jv") {	
			if (trim($t[$i]) != "" and $moniehto != 0) {
				$virhe[$i] = t("Et voi tehdä tästä jv-maksuehtoa koska tämä on moniehto-maksuehto!");	
			}	
		}

		if (mysql_field_name($result, $i) == "kateinen") {
			if (trim($t[$i]) != "" and $moniehto != 0) {
				$virhe[$i] = t("Et voi tehdä tästä käteismaksuehtoa koska tämä on moniehto-maksuehto!");	
			}	
		}
		
		if (mysql_field_name($result, $i) == "kassa_teksti") {
			$kassa_txt = $i;
		}
		
		if (mysql_field_name($result, $i) == "kassa_alepros") {			
			$kassa_pros = $i;						
		}
		
		if ($kassa_txt != 0 and $kassa_pros != 0) {
			if ($t[$kassa_txt] != "" and $t[$kassa_pros] == 0) {
				$virhe[$kassa_pros] = t("Kassa ale prosentti on nolla!");
				$virhe[$i] .= " ";
			}
			elseif ($t[$kassa_txt] == "" and $t[$kassa_pros] != 0) {
				$virhe[$kassa_txt] = t("Kassa teksti puuttuu!");
				$virhe[$i] .= " ";
			}			
		}
		
	}
}



?>