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

		while($row = mysql_fetch_assoc($vresult)) {
			$sel = "";

			if ($row["selite"] == $trow[$i]) {
				$sel = 'selected';
				$asavainsanatyyppi = $row["selitetark_2"];
				$asavainsanalaji = $row['selite'];
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
		elseif (stripos($asavainsanalaji, "OHJAUSMERKKI") !== FALSE) {

			$ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 300).">";

			$query = "	SELECT selite
						FROM toimitustapa
						WHERE yhtio = '{$kukarow['yhtio']}'
						ORDER BY 1";
			$toimitustapares = pupe_query($query);

			while ($toimitustaparow = mysql_fetch_assoc($toimitustapares)) {

				$sel = $trow[$i] == $toimitustaparow['selite'] ? " selected" : "";

				$ulos .= "<option value='{$toimitustaparow['selite']}'{$sel}>{$toimitustaparow['selite']}</option>";
			}

			$ulos .= "</select></td>";
			$jatko = 0;
		}
		elseif (stripos($asavainsanatyyppi, "TOIMITUSTAPA") !== FALSE) {
			$query = "	SELECT *
						FROM toimitustapa
						WHERE yhtio = '$kukarow[yhtio]'
						ORDER BY jarjestys, selite";
			$vresult = mysql_query($query) or pupe_error($query);
			$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";

			while ($vrow=mysql_fetch_assoc($vresult)) {
				$sel="";
				if ($trow[$i] == $vrow['selite']) {
					$sel = "selected";
				}
				$ulos .= "<option value = '$vrow[selite]' $sel>".t_tunnus_avainsanat($vrow, "selite", "TOIMTAPAKV")."</option>";
			}
			$ulos .= "</select></td>";
			$jatko = 0;
		}
		elseif (stripos($asavainsanatyyppi, "EXTRANET_TEEMA") !== FALSE) {
			$vresult = t_avainsana("EXTRANET_TEEMA");

			$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
			$ulos .= "<option value=''>".t("Oletus")."</option>";

			while ($row = mysql_fetch_assoc($vresult)) {
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

	if (mysql_field_name($result, $i) == "kieli") {

		$ulos = "<td><select name='$nimi'>";
		$sel  = "";

		$query  = "SHOW columns from sanakirja";
		$fields =  mysql_query($query);

		while ($apurow = mysql_fetch_array($fields)) {
			if (strlen($apurow[0]) == 2) {
				$sel = "";
				if ($trow[$i] == $apurow[0]) {
					$sel = "SELECTED";
				}
				elseif ($trow[$i] == "" and $apurow[0] == $yhtiorow["kieli"]) {
					$sel = "SELECTED";
				}
				$ulos .= "<option value='$apurow[0]' $sel>$apurow[0] - ".maa($apurow[0])."</option>";
			}
		}

		$ulos .= "</select></td>";

		$jatko = 0;
	}
