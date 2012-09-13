<?php

if(!function_exists("kassalipastarkista")) {
	function kassalipastarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {
		global $kukarow, $yhtiorow, $alias_set, $ulosarray;

		if ((mysql_field_name($result, $i) == "kassa") or
			(mysql_field_name($result, $i) == "pankkikortti") or
			(mysql_field_name($result, $i) == "luottokortti") or
			(mysql_field_name($result, $i) == "kateistilitys") or
			(mysql_field_name($result, $i) == "kassaerotus")) {

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

		if (mysql_field_name($result, $i) == "nimi") {

			if ($t[$i] == '') {
				$virhe[$i] = t("Nimi on syötettävä");
			}

			$query = "	SELECT *
						FROM kassalipas
						WHERE yhtio = '$kukarow[yhtio]'
						and tunnus != '$tunnus'";
			$sresult = mysql_query($query) or pupe_error($query);

			while ($srow = mysql_fetch_array($sresult)) {
				if (strpos(strtoupper($t[$i]), strtoupper($srow["nimi"])) !== FALSE or strpos(strtoupper($srow["nimi"]), strtoupper($t[$i])) !== FALSE) {
					$virhe[$i] = t("Nimi on jo käytössä").": $srow[nimi]";
					break;
				}
			}
		}
	}
}


?>