<?php

if(!function_exists("avainsanatarkista")) {
	function avainsanatarkista (&$t, $i, $result, $tunnus, &$virhe) {
		global $kukarow, $yhtiorow, $alias_set, $ulosarray;
		
		static $seliteerrori, $laji;
		
		if (mysql_field_name($result, $i) == "selite" and trim($t[$i]) == '') {
			$seliteerrori = 1;
		}

		if ($seliteerrori == 1 and mysql_field_name($result, $i) == "selitetark" and trim($t[$i]) == '') {
			$virhe[$i] = t("Selite ja selitetark ei voi olla tyhjiä")."!";
		}

		if (mysql_field_name($result, $i) == "laji") {
			$laji = trim($t[$i]);
		}

		if ($laji == 'KASSA' and $seliteerrori == 0 and trim($t[$i]) != '' and mysql_field_name($result, $i) == 'selitetark_2') {
			$query = "	SELECT nimi
						FROM tili
						WHERE yhtio='$kukarow[yhtio]' and tilino = '$t[$i]'";
			$sresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($sresult) != 1) {
				//Olisiko siellä nimi
				if ($t[$i] != '') {
					$query = "	SELECT nimi, tilino
								FROM tili
								WHERE yhtio = '$kukarow[yhtio]' and nimi like '%$t[$i]%'
								ORDER BY tilino";
					$sresult = mysql_query($query) or pupe_error($query);
					if (mysql_num_rows($sresult) > 0) {
						$ulosarray[$i] = "<td><select name='t[$i]'><option value = '' $sel>".t("Ei valintaa")."</option>";
						while ($vrow=mysql_fetch_array($sresult)) {
							$sel="";
							if ($trow[$i] == $vrow[0]) {
								$sel = "selected";
							}
							$ulosarray[$i] .= "<option value = '$vrow[tilino]' $sel>$vrow[tilino] $vrow[nimi]</option>";
						}
						$ulosarray[$i] .= "</select></td>";
						$virhe[$i] = t("Valitse tili");
					}

				}
				if ($virhe[$i] == '') $virhe[$i] = t("Tili puuttuu tai sitä ei löydy");
			}
		}
	}
}
	
	
?>