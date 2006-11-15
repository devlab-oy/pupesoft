<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys

	if ($i == 0) { //yhtiötä ei näytetä
		$tyyppi = 0;
	}

	if (mysql_field_name($result, $i) == "kieli") {

		$ulos = "<td><select name='$nimi'>";

		$query  = "show columns from sanakirja";
		$fields =  mysql_query($query);

		while ($apurow = mysql_fetch_array($fields)) {
			$sel = "";
			if ($trow[$i] == $apurow[0]) {
				$sel = "selected";
			}
			if ($apurow[0] != "tunnus") {
				$query = "select distinct nimi from maat where koodi='$apurow[0]'";
				$maare = mysql_query($query);
				$maaro = mysql_fetch_array($maare);
				$maa   = strtolower($maaro["nimi"]);
				if ($maa=="") $maa = $apurow[0];
				$ulos .= "<option value='$apurow[0]' $sel>".t("$maa")."</option>";
			}
		}

		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "maakoodi") {
		$query = "	SELECT distinct koodi, nimi
					FROM maat
					WHERE nimi != ''
					ORDER BY koodi";
		$vresult = mysql_query($query) or pupe_error($query);
		$ulos = "<td><select name='$nimi'>";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if (strtoupper($trow[$i]) == strtoupper($vrow[0])) {
				$sel = "selected";
			}
			$ulos .= "<option value = '".strtoupper($vrow[0])."' $sel>".t($vrow[1])."</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "valkoodi") { //valuutta
		$query = "SELECT nimi, tunnus
	                       FROM valuu
	                  WHERE yhtio = '$kukarow[yhtio]'
	                  ORDER BY jarjestys";
	        $vresult = mysql_query($query) or pupe_error($query);
	        $ulos = "<td><select name='$nimi'>";
	        while ($vrow=mysql_fetch_array($vresult)) {
		        $sel="";
		        if ($trow[$i] == $vrow['nimi']) {
		                $sel = "selected";
		        }
	        	$ulos .= "<option value = '$vrow[nimi]' $sel>$vrow[nimi]";
	        }
		$ulos .= "</select></td>";
		$jatko = 0;
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
		if ($ulosarray[$i] != '') {
			$ulos=$ulosarray[$i];
			$jatko = 0;
		}
		else {
			$ulos = "<td><input type = 'text' name = '$nimi' value = '$trow[$i]' size='$size' maxlength='" . mysql_field_len($result,$i) ."'>";
			$query = "	SELECT nimi
						FROM tili
						WHERE yhtio='$kukarow[yhtio]' and tilino = '$trow[$i]'";
			$vresult = mysql_query($query) or pupe_error($query);
			if (mysql_num_rows($vresult) == 1) {
				$vrow=mysql_fetch_array($vresult);
				$ulos .= " " . $vrow[nimi] . "</td>";
				$jatko = 0;
			}
		}
	}

	if (mysql_field_name($result, $i) == "factoring_siirtoteksti" or
		mysql_field_name($result, $i) == "factoring_vientiteksti") {
		$ulos = "<td><textarea cols='85' rows='5' name='$nimi'>$trow[$i]</textarea></td>";
		$jatko = 0;
	}

?>
