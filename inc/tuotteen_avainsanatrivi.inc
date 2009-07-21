<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys

	//yhtiötä ei näytetä
	if (mysql_field_name($result, $i) == "yhtio") {
		$tyyppi = 0;
	}

	if (mysql_field_name($result, $i) == "laji") {

		$sel = array($trow[$i] => "SELECTED");

		$ulos = "<td><select name='$nimi' onchange='submit();'>";
		$ulos .= "<option value='nimitys' $sel[nimitys]>".t("Tuotteen nimitys")."</option>";
		$ulos .= "<option value='lyhytkuvaus' $sel[lyhytkuvaus]>".t("Tuotteen lyhytkuvaus")."</option>";
		$ulos .= "<option value='kuvaus' $sel[kuvaus]>".t("Tuotteen kuvaus")."</option>";
		$ulos .= "<option value='mainosteksti' $sel[mainosteksti]>".t("Tuotteen mainosteksti")."</option>";
		$ulos .= "<option value='tarratyyppi' $sel[tarratyyppi]>".t("Tuotteen tarratyyppi")."</option>";
		$ulos .= "<option value='sistoimittaja' $sel[sistoimittaja]>".t("Tuotteen sisäinen toimittaja")."</option>";

		$sresult = t_avainsana("TUOTEULK");
		
		while ($srow = mysql_fetch_array($sresult)) {
			$ulos .= "<option value='$srow[selite]' ".$sel[$srow["selite"]].">$srow[selite] $srow[selitetark]</option>";
		}
		
		$vresult = t_avainsana("PARAMETRI");

		while ($vrow = mysql_fetch_array($vresult)) {
			$ulos .= "<option value='parametri_$vrow[selite]' ".$sel["parametri_".$vrow["selite"]].">".t("Tuotteen parametri").": $vrow[selitetark]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "tuoteno") {
		if($lukitse_avaimeen != "") {
			$ulos = "<td><input type='hidden' name='$nimi' value='$lukitse_avaimeen'>$lukitse_avaimeen</td>";
			$jatko = 0;
		}
		elseif(isset($alasveto[$i])) {
			$ulos = "<td>".$alasveto[$i]."</td>";
			$jatko = 0;
		}
	}

	if (mysql_field_name($result, $i) == "selite") {

		if ($trow["laji"] == "tarratyyppi") {

			$vresult = t_avainsana("TARRATYYPPI");

			if (mysql_num_rows($vresult) > 0) {
				$sel = array($trow[$i] => "SELECTED");
				$ulos = "<td><select name='$nimi'>";
				$ulos .= "<option value=''>".t("Ei tilaustuotetarraa")."</option>";

				while ($vrow = mysql_fetch_array($vresult)) {
					$ulos .= "<option value='$vrow[selite]' ".$sel[$vrow["selite"]].">$vrow[selitetark]</option>";
				}
				$ulos .= "</select></td>";
			}

			$jatko = 0;
		}
		else {
			$ulos = "<td><textarea rows=10 cols=50 name='$nimi'>{$trow[$i]}</textarea></td>";
		}

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "kieli") {

		$kieli = $trow[$i];

		$ulos = "<td><select name='$nimi'>";
		$sel  = "";

		//	näytetäään vain kielet joilla on
		if ($perhe > 0) {
			$query = "	SELECT group_concat(kieli) kielet FROM avainsana WHERE yhtio = '$kukarow[yhtio]'";
			$res = mysql_query($query) or pupe_error($query);
			$kilerow = mysql_fetch_array($res);
			$kielet = $kilerow["kielet"];
		}

		$query  = "SHOW columns from sanakirja";
		$fields =  mysql_query($query);

		while ($apurow = mysql_fetch_array($fields)) {
			if (strlen($apurow[0]) == 2 and (strpos($kielet, $apurow[0]) === false or $apurow[0] == $trow[$i])) {
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

?>
