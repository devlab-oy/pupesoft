<?php

	$ulos   = "";
	$jatko  = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys

	// yhtiötä ei näytetä
	if ($i == 0) {
		$tyyppi = 0;
	}

	if ((mysql_field_name($result, $i) == "kassa") or
		(mysql_field_name($result, $i) == "pankkikortti") or
		(mysql_field_name($result, $i) == "luottokortti") or
		(mysql_field_name($result, $i) == "kateistilitys") or
		(mysql_field_name($result, $i) == "kassaerotus") or
		(mysql_field_name($result, $i) == "kateisotto")) {

		if ($ulosarray[$i] != '') {
			$ulos=$ulosarray[$i];
			$jatko = 0;
		}
		else {
			$ulos = "<td><input type = 'text' name = '$nimi' value = '$trow[$i]' size='$size' maxlength='" . mysql_field_len($result,$i) ."'>";

			$query = "	SELECT nimi
						FROM tili
						WHERE yhtio = '$kukarow[yhtio]'
						and tilino = '$trow[$i]'";
			$vresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($vresult) == 1) {
				$vrow = mysql_fetch_array($vresult);
				$ulos .= " $vrow[nimi]</td>";
				$jatko = 0;
			}
		}
	}

	if (mysql_field_name($result, $i) == "kustp") {

		$ulos = "<td><select name='$nimi'><option value=' '>".t("Ei kustannuspaikkaa")."</option>";
		$query = "	SELECT tunnus, nimi
					FROM kustannuspaikka
					WHERE yhtio = '$kukarow[yhtio]'
					and kaytossa != 'E'
					and tyyppi = 'K'
					ORDER BY koodi+0, koodi, nimi";
		$vresult = mysql_query($query) or pupe_error($query);

		while ($vrow = mysql_fetch_array($vresult)) {
			$sel = "";
			if ($trow[$i] == $vrow[0]) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[0]' $sel>$vrow[1]</option>";
		}
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "toimipaikka") {

		$ulos = "<td><select name='{$nimi}'><option value='0'>".t("Ei toimipaikkaa")."</option>";
		$query = "	SELECT tunnus, ovtlisa, postitp, nimi
					FROM yhtion_toimipaikat
					WHERE yhtio = '{$kukarow['yhtio']}'
					ORDER BY nimi, ovtlisa, postitp";
		$vresult = mysql_query($query) or pupe_error($query);

		while ($vrow = mysql_fetch_assoc($vresult)) {
			$sel = $trow[$i] == $vrow['tunnus'] ? " selected" : "";
			$ulos .= "<option value = '{$vrow['tunnus']}'{$sel}>{$vrow['nimi']} {$vrow['ovtlisa']} {$vrow['postitp']}</option>";
		}
		$ulos .= "</select></td>";
		$jatko = 0;
	}
