<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys

	if (file_exists("../inc/alvpopup.inc")) {
		require ("../inc/alvpopup.inc");
	}
	else {
		require ("alvpopup.inc");
	}

	if (mysql_field_name($result, $i) == "yhtio") {
		$tyyppi = 0;
	}

	if (mysql_field_name($result, $i) == "maa") {

		$maa=$trow[$i];

		$query = "	SELECT distinct koodi, nimi
					FROM maat
					WHERE nimi != ''
					ORDER BY koodi";
		$vresult = mysql_query($query) or pupe_error($query);

		$ulos = "<td><select name='$nimi' onchange='submit();'>";

		while ($vrow = mysql_fetch_array($vresult)) {
			$sel = "";
			if ($trow[$i] == $vrow["koodi"]) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[koodi]' $sel>".t($vrow["nimi"])."</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "kustp") {
		$query = "	SELECT tunnus, nimi
					FROM kustannuspaikka
					WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'K'
					ORDER BY nimi";
		$vresult = mysql_query($query) or pupe_error($query);
		$ulos = "<td><select name='$nimi'><option value=' '>".t("Ei kustannuspaikkaa")."</option>";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow[0]) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[0]' $sel>$vrow[1]</option>";
		}
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "kohde") {
		$query = "	SELECT tunnus, nimi
					FROM kustannuspaikka
					WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'O'
					ORDER BY nimi";
		$vresult = mysql_query($query) or pupe_error($query);
		$ulos = "<td><select name='$nimi'><option value=' '>".t("Ei kohdetta")."</option>";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow[0]) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[0]' $sel>$vrow[1]</option>";
		}
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "projekti") {
		$query = "	SELECT tunnus, nimi
					FROM kustannuspaikka
					WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'P'
					ORDER BY nimi";
		$vresult = mysql_query($query) or pupe_error($query);
		$ulos = "<td><select name='$nimi'><option value=' '>".t("Ei projektia")."</option>";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow[0]) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[0]' $sel>$vrow[1]</option>";
		}
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "alv") {
		
		if($maa!="") {
			//	Osataanko tämän maan alv=
			$query = "	SELECT selite
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]' and laji = 'alvulk' and selitetark_2='$maa'
						ORDER BY jarjestys,selite";
			$vresult = mysql_query($query) or pupe_error($query);
			if (mysql_num_rows($vresult) > 0) {
				$ulos = "<td><select name='$nimi'>";
				$sel = '';

				$ulos .= "<option value = ''>".t("Valitse oikea ALV");

				while ($vrow=mysql_fetch_array($vresult)) {
					$sel = "";
					if ($trow[$i] == $vrow['selite']) {
						$sel = "selected";
					}
					$ulos .= "<option value = '$vrow[selite]' $sel>$vrow[selite] %";
				}
				$ulos .= "</select></td>";

				$jatko=0;
			}
			else {
				
			}
		}
	}
	
?>
