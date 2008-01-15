<?php

if(!function_exists("rahtisopimuksettarkista")) {
	function rahtisopimuksettarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {
		global $kukarow, $yhtiorow, $alias_set, $alasveto;
		
		static $chytunnus;
		if ((mysql_field_name($result, $i) == "ytunnus") or
			(mysql_field_name($result, $i) == "rahtisopimus")) {
			if (trim($t[$i]) == '') {
				$virhe[$i] = "".t("Tieto puuttuu")."";
			}
		}


		if (mysql_field_name($result, $i) == "ytunnus") {
			$chytunnus = $t[$i];

			if ($chytunnus != '') {
				$query = "	SELECT ytunnus
							FROM asiakas
							WHERE yhtio='$kukarow[yhtio]' and ytunnus = '$t[$i]'";
				$sresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($sresult) == 0) {

					$ytunnus 		= $chytunnus;
					$kutsuja 		= "yllapito.php";
					$ulos 			= "";				
					require("asiakashaku.inc");

					if ($ulos != "" and $ytunnus == "") {
						$alasveto[$i] = "<select name='t[$i]'>".$ulos."</select>";
						$virhe[$i]    = t("Valitse asiakas!");
					}
					elseif ($ytunnus != "") {
						$t[$i]  = $ytunnus;
						$virhe[$i] = t("Asiakas löytyi!");
					}	
					else {
						$virhe[$i] = t("Asiakas puuttuu tai sitä ei löydy!");	
					}	
				}
			}	
		}
	}
}

	
?>