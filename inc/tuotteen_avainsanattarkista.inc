<?php
	if (mysql_field_name($result, $i) == "tuoteno") {
		$chtuoteno = $t[$i];
		if ($chtuoteno != '') {
			$query = "	SELECT tuoteno
						FROM tuote
						WHERE yhtio='$kukarow[yhtio]' and tuoteno = '$t[$i]'";
			$sresult = mysql_query($query) or pupe_error($query);
			
			if (mysql_num_rows($sresult) != 1) {
								
				$tuoteno 		= $t[$i]."*";
				$kutsuja 		= "yllapito.php";
				$ulos			= "";
				
				require ("inc/tuotehaku.inc");
								
				if ($ulos != "") {	
					$alasveto[$i] = "<select name='t[$i]'>".$ulos."</select>";
				}
				
				$virhe[$i] = t("Tuotenumero puuttuu tai sitä ei löydy!");
				$errori = 1;
			}
		}
	}
	
	if (mysql_field_name($result, $i) == "laji") {
		if ($t[$i] == '') {
			$virhe[$i] = "".t("Lajia ei voi jättää tyhjäksi. Skarppaa!")."";
			$errori = 1;
		}
		$lajitalteen = $t[$i];
	}
	
	if (mysql_field_name($result, $i) == "selite" and $lajitalteen == "SISTOIMITTAJA") {
		if ($t[$i] == '') {
			$virhe[$i] = t("Selitettä ei voi jättää tyhjäksi. Skarppaa!")."";
			$errori = 1;
		}
		else {
			// salitaan vaan konserniyhtiö joka ei ole me itse
			$query = "SELECT *
					FROM yhtio
					WHERE yhtio = '$t[$i]' and konserni='$yhtiorow[konserni]' and yhtio!='$kukarow[yhtio]'";
			$sresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($sresult) == 0) {
				$virhe[$i] = t("Yhtiötä ei löydy. Skarppaa!");
				$errori = 1;
			}
		}
	}
?>