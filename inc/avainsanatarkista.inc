<?php

if(!function_exists("avainsanatarkista")) {
	function avainsanatarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {
		global $kukarow, $yhtiorow, $alias_set, $ulosarray;
		
		static $seliteerrori, $laji;
		
		if (mysql_field_name($result, $i) == "selite" and trim($t[$i]) == '') {
			$seliteerrori = 1;
		}
		
		if (mysql_field_name($result, $i) == "selite" and !is_numeric($t[$i]) and in_array($laji, array("TRY", "OSASTO", "PIIRI"))) {
			$seliteerrori = 1;
		}

		if (mysql_field_name($result, $i) == "laji") {
			$laji = trim($t[$i]);
		}

		if (mysql_field_name($result, $i) == "selite" and $laji == 'VAKIOVIESTI' and trim($t[$i]) == '') {
			$virhe[$i] = t("Selite on pakollinen");
		}
		
		if ($seliteerrori == 1 and mysql_field_name($result, $i) == "selitetark" and trim($t[$i]) == '' and $laji != 'VAKIOVIESTI') {
			$virhe[$i] = t("Selite ja selitetark ei voi olla tyhjiä")."!";
		}

		if (mysql_field_name($result, $i) == "selite" and $laji == "PARAMETRI") {
			preg_match_all('/([a-zA-Z_])+/', $t[$i], $match);
			
			if ($match[0][0] != $t[$i]) {
				$virhe[$i] = t("Vain aakkoset ja alaviivat ovat sallittuja merkkejä");
			}
		}
	}
}
	
	
?>