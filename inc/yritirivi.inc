<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys
	if ($i == 0) { //yhtiötä ei näytetä
		$tyyppi = 0;
	}
 	if (mysql_field_name($result, $i) == "hyvak") { //hyväksyjät
		$query = "SELECT kuka, nimi
                          FROM kuka
                          WHERE yhtio = '$kukarow[yhtio]'
                          ORDER BY nimi";
		$vresult = mysql_query($query) or pupe_error($query);
		$ulos = "<td><select name='$nimi'><option value=''>".t("Ei kukaan")."";
		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
                        if ($trow[$i] == $vrow[0]) {
                                $sel = "selected";
			}
                        $ulos .= "<option value = '$vrow[0]' $sel>$vrow[1]";
		}
		$ulos .= "</select></td>";
		$jatko = 0;
	}
	if (mysql_field_name($result, $i) == "oletus_kustp") {
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
	if (mysql_field_name($result, $i) == "oletus_kohde") {
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
	if (mysql_field_name($result, $i) == "oletus_projekti") {
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

	if	((mysql_field_name($result, $i) == "oletus_kulutili") or
		(mysql_field_name($result, $i) == "oletus_rahatili")) {

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
?>
