<?php
	$ulos 	= "";
	$jatko	= 1; // oletetaan normaali käsittely
	$tyyppi	= 1; // oletetaan rivin näkyvyys

	if ($i == 0) { //yhtiötä ei näytetä
		$tyyppi = 0;
	}

	if (mysql_field_name($result, $i) == "liitostunnus") {
		if ($lukitse_avaimeen != "") {
			$tyyppi = 4;
			if ($lukitse_avaimeen != "") {
				$ulos = "<input type='hidden' name='$nimi' value='$lukitse_avaimeen'>";
				$jatko = 0;
			}
		}
		else {
			$tyyppi = 2;
		}
	}

	if (mysql_field_name($result, $i) == "laji") {
		$vresult = t_avainsana("ASAVAINSANA");

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300)." onchange='submit();'>";
		$ulos .= "<option value=''>".t("Oletus")."</option>";

		while($row = mysql_fetch_array($vresult)) {
			$sel = "";

			if ($row["selite"] == $trow[$i]) {
				$sel = 'selected';
				$asavainsanatyyppi = $row["selitetark_2"];
			}

			$ulos .= "<option value='$row[selite]' $sel>$row[selitetark]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "avainsana") {

		if (stripos($asavainsanatyyppi, "DATE") !== FALSE) {
			$tyyppi = 1.5;
		}
		elseif (stripos($asavainsanatyyppi, "TOIMITUSTAPA") !== FALSE) {
			$query = "	SELECT *
						FROM toimitustapa
						WHERE yhtio = '$kukarow[yhtio]'
						ORDER BY jarjestys, selite";
			$vresult = mysql_query($query) or pupe_error($query);
			$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";

			while ($vrow=mysql_fetch_array($vresult)) {
				$sel="";
				if ($trow[$i] == $vrow['selite']) {
					$sel = "selected";
				}
				$ulos .= "<option value = '$vrow[selite]' $sel>".t_tunnus_avainsanat($vrow, "selite", "TOIMTAPAKV")."</option>";
			}
			$ulos .= "</select></td>";
			$jatko = 0;
		}
		elseif (strpos($asavainsanatyyppi, "/") !== FALSE) {
			$sel = array();
			$sel[$trow[$i]] = "SELECTED";

			$ulos  = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";

			foreach(explode("/", $asavainsanatyyppi) as $astyy) {
				$ulos .= "<option value = '$astyy' ".$sel[$astyy].">$astyy</option>";
			}

			$ulos .= "</select></td>";
			$jatko = 0;
		}
	}
?>