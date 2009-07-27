<?php

if(!function_exists("tuotteen_avainsanattarkista")) {
	function tuotteen_avainsanattarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {
		global $kukarow, $yhtiorow, $alias_set, $alasveto;
		
		static $chtuoteno, $lajitalteen;
		
		if (mysql_field_name($result, $i) == "tuoteno") {
			$chtuoteno = $t[$i];
			
			if ($chtuoteno != '') {
				$query = "	SELECT tuoteno
							FROM tuote
							WHERE yhtio = '$kukarow[yhtio]' 
							AND tuoteno = '$t[$i]'";
				$sresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($sresult) != 1) {

					if (substr($t[$i], -1) != '*') $tuoteno = $t[$i]."*";
					else $tuoteno = $t[$i];
					
					$kutsuja 		= "yllapito.php";
					$ulos			= "";

					require ("inc/tuotehaku.inc");

					if ($ulos != "") {	
						$alasveto[$i] = "<select name='t[$i]'>".$ulos."</select>";
					}

					$virhe[$i] = t("Tuotenumeroa ei löydy!");
				}
			}
			else {
				$virhe[$i] = t("Tuotenumero puuttuu!");
			}
		}
		
		if (mysql_field_name($result, $i) == "laji") {
			if ($t[$i] == '') {
				$virhe[$i] = t("Lajia ei voi jättää tyhjäksi")."!";
			}
			$lajitalteen = $t[$i];
		}

		if (mysql_field_name($result, $i) == "selite") {
			if ($lajitalteen == "SISTOIMITTAJA") {
				// salitaan vaan konserniyhtiö joka ei ole me itse
				$query = "	SELECT *
							FROM yhtio
							WHERE yhtio = '$t[$i]' and konserni='$yhtiorow[konserni]' and yhtio!='$kukarow[yhtio]'";
				$sresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($sresult) == 0) {
					$virhe[$i] = t("Yhtiötä ei löydy. Skarppaa!");
				}
			}
			elseif (strtoupper($lajitalteen) == "OLETUSVALINTA") {
				if(!in_array(substr($t[$i], 0, 3), array("", "!!!", "¡¡¡", "¡¡V"))) {
					$virhe[$i] = t("Tuotteen %s oletusvalinta %s ei kelpaa!", $kieli, $chtuoteno, $t[$i]);
				}
			}
		}
	}
}

?>