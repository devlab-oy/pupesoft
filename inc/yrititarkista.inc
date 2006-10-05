<?php
	if (mysql_field_name($result, $i) == "nimi") {
		if (trim($t[$i]) == '') {
			$virhe[$i] = t("Tieto puuttuu");
			$errori = 1;
		}
	}
	if ((mysql_field_name($result, $i) == "oletus_rahatili") or
		(mysql_field_name($result, $i) == "oletus_kulutili")) {
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
					$ulosarray[$i] = "<td><select name='$nimi'><option value = '' $sel>".t("Ei valintaa")."</option>";
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
			$errori = 1;
		}
	}
	if (mysql_field_name($result, $i) == "tilino") {
		if (strtoupper($yhtiorow['maakoodi']) == 'FI') {
			if ((substr($t[$i], 0, 1) >= 0) and (substr($t[$i], 0, 1) <= '9')) {
				$pankkitili = $t[$i];
				require 'pankkitilinoikeellisuus.php';
				if ($pankkitili == '') {
					$virhe[$i] = "".t("Pankkitili on virheellinen")."";
					$errori = 1;
				}
				else {
					$t[$i]=$pankkitili;
				}
			}
		}
	}
?>
