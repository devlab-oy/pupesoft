<?php

	if ((mysql_field_name($result, $i) == "kotipaikka") or
		(mysql_field_name($result, $i) == "nimi") or
		(mysql_field_name($result, $i) == "kieli") or
		(mysql_field_name($result, $i) == "ovttunnus") or
		(mysql_field_name($result, $i) == "pankkinimi1")) {
		if (trim($t[$i]) == '') {
			$virhe[$i] = "".t("Tieto puuttuu!")."";
			$errori = 1;
		}
	}

	if ((mysql_field_name($result, $i) == "kassa") or
		(mysql_field_name($result, $i) == "myynti") or
		(mysql_field_name($result, $i) == "myynti_eu") or
		(mysql_field_name($result, $i) == "myynti_ei_eu") or
		(mysql_field_name($result, $i) == "myynti_marginaali") or
		(mysql_field_name($result, $i) == "osto_marginaali") or
		(mysql_field_name($result, $i) == "myyntisaamiset") or
		(mysql_field_name($result, $i) == "luottotappiot") or
		(mysql_field_name($result, $i) == "ostovelat") or
		(mysql_field_name($result, $i) == "valuuttaero") or
		(mysql_field_name($result, $i) == "myynninvaluuttaero") or
		(mysql_field_name($result, $i) == "matkalla_olevat") or
		(mysql_field_name($result, $i) == "kassaale") or
		(mysql_field_name($result, $i) == "myynninkassaale") or
		(mysql_field_name($result, $i) == "muutkulut") or
		(mysql_field_name($result, $i) == "pyoristys") or
		(mysql_field_name($result, $i) == "varasto") or
		(mysql_field_name($result, $i) == "varastonmuutos") or
		(mysql_field_name($result, $i) == "alv") or
		(mysql_field_name($result, $i) == "siirtosaamiset") or
		(mysql_field_name($result, $i) == "siirtovelka") or
		(mysql_field_name($result, $i) == "factoringsaamiset") or
		(mysql_field_name($result, $i) == "selvittelytili") or
		(mysql_field_name($result, $i) == "raaka_ainevarasto") or
		(mysql_field_name($result, $i) == "raaka_ainevarastonmuutos") or
		(mysql_field_name($result, $i) == "konsernisaamiset") or
		(mysql_field_name($result, $i) == "konsernivelat") or
		(mysql_field_name($result, $i) == "konserniostovelat") or
		(mysql_field_name($result, $i) == "konsernimyyntisaamiset")) {
		
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

	if (mysql_field_name($result, $i) == "ytunnus" and strtoupper($yhtiorow["maakoodi"]) == "FI") {
		$ytunnus = trim($t[$i]);
		require ("tarkistaytunnus.inc");

		if ($ok==0) {
			$virhe[$i] = "".t("Virheellinen y-tunnus!")." $t[$i] -> $ytunnus";
			$errori = 1;
		}
		else {
			$t[$i] = $ytunnus;
		}
	}


	if (mysql_field_name($result, $i) == "pankkitili1" or
		mysql_field_name($result, $i) == "pankkitili2" or
		mysql_field_name($result, $i) == "pankkitili3") {

		if (strtoupper($yhtiorow['maakoodi']) == "FI" and trim($t[$i]) != "") {

			$pankkitili = $t[$i];

			require ('pankkitilinoikeellisuus.php');

			if ($pankkitili == '') {
				$virhe[$i] = t("Pankkitili on virheellinen");
				$errori = 1;
			}
		}
	}

?>
