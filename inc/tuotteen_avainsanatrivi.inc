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
		$ulos .= "<option value='oletusvalinta' $sel[oletusvalinta]>".t("Tuotteen tilauksen oletusvalinta")."</option>";
		$ulos .= "<option value='osasto' $sel[osasto]>".t("Tuotteen osasto")."</option>";
		$ulos .= "<option value='try' $sel[try]>".t("Tuotteen tuoteryhmä")."</option>";
		$ulos .= "<option value='pakkauskoko2' $sel[pakkauskoko2]>".t("Tuotteen pakkauskoko 2")."</option>";
		$ulos .= "<option value='pakkauskoko3' $sel[pakkauskoko3]>".t("Tuotteen pakkauskoko 3")."</option>";
		$ulos .= "<option value='hammastus' $sel[hammastus]>".t("Tuotteen hammastus")."</option>";
		$ulos .= "<option value='tyomaarayksen_ryhmittely' $sel[tyomaarayksen_ryhmittely]>".t("Työmääräyksen ryhmittely")."</option>";

		$sresult = t_avainsana("TUOTEULK");

		while ($srow = mysql_fetch_array($sresult)) {
			$ulos .= "<option value='$srow[selite]' ".$sel[$srow["selite"]].">$srow[selite] $srow[selitetark]</option>";
		}

		$vresult = t_avainsana("PARAMETRI");

		while ($vrow = mysql_fetch_assoc($vresult)) {
			$ulos .= "<option value='parametri_$vrow[selite]' ".$sel["parametri_".$vrow["selite"]].">".t("Tuotteen parametri").": $vrow[selitetark]</option>";
		}

		$lresult = t_avainsana("LISATIETO");

		while ($lrow = mysql_fetch_assoc($lresult)) {
			$ulos .= "<option value='lisatieto_$lrow[selite]' ".$sel["lisatieto_".$lrow["selite"]].">".t("Tuotteen lisätieto").": $lrow[selitetark]</option>";
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
		elseif($trow["laji"] == "oletusvalinta") {
			$sel = array($trow[$i] => "SELECTED");


			$ulos = "<td>
						<select name='$nimi'>
							<option value=''>".t("Ei oletustoimintoa")."</option>";

			$olquery = "	SELECT toimi.tunnus, toimi.nimi
							FROM tuotteen_toimittajat
							JOIN toimi ON toimi.yhtio=tuotteen_toimittajat.yhtio and toimi.tunnus=tuotteen_toimittajat.liitostunnus
							WHERE tuotteen_toimittajat.yhtio = '$kukarow[yhtio]' and tuoteno = '$trow[tuoteno]' and liitostunnus > 0";
			$oltarkres = pupe_query($olquery);
			if(mysql_num_rows($oltarkres) > 0) {
				while($olrow = mysql_fetch_array($oltarkres)) {
					$ulos .= "
						<option value='!!!{$olrow["tunnus"]}' ".$sel["!!!{$olrow["tunnus"]}"].">$olrow[nimi] --> ".t("Tilataan asiakkaalle")."</option>
						<option value='¡¡¡{$olrow["tunnus"]}' ".$sel["¡¡¡{$olrow["tunnus"]}"].">$olrow[nimi] --> ".t("Tilataan varastoon")."</option>";

				}
			}

			$olquery = "	SELECT tunnus
							FROM tuoteperhe
							WHERE yhtio = '$kukarow[yhtio]'
							AND isatuoteno = '$trow[tuoteno]'
							AND tyyppi = 'R'
							LIMIT 1";
			$oltarkres = pupe_query($olquery);
			if(mysql_num_rows($oltarkres) > 0) {
				while($olrow = mysql_fetch_array($oltarkres)) {
					$ulos .= "
							<option value='¡¡V' ".$sel["¡¡V"].">".t("Valmistetaan varastoon (oletuspaikka)")."</option>";

				}
			}
			$ulos .= "
						</select>
					</td>";
		}
		else {
			$ulos = "<td><textarea rows=10 cols=50 name='$nimi'>{$trow[$i]}</textarea></td>";
		}

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "selitetark") {

		if ($trow["laji"] == "pakkauskoko2" or $trow["laji"] == "pakkauskoko3") {
			$vresult = t_avainsana("Y");
			$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";

			while ($vrow=mysql_fetch_array($vresult)) {
				$sel="";
				if (strtoupper($trow[$i]) == strtoupper($vrow["selite"]) and $vrow["selite"] != "") {
					$sel = "selected";
				}
				$ulos .= "<option value = '$vrow[selite]' $sel>$vrow[selite] - $vrow[selitetark]</option>";
			}
			$ulos .= "</select></td>";
			$jatko = 0;
		}
		else {
			$ulos = "<td><textarea rows=10 cols=50 name='$nimi'>{$trow[$i]}</textarea></td>";
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

	if (mysql_field_name($result, $i) == 'status') {
		if (substr($trow['laji'], 0, 10) == 'lisatieto_') {
	 		$sel = array_fill_keys(array($trow[$i]), " selected") + array('P' => '', 'L' => '');

			$ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 300).">";
			$ulos .= "<option value=''>".t("Aktiivinen")."</option>";
			$ulos .= "<option value='L'{$sel['L']}>".t("Aktiivinen")." (".t("lukittu").")</option>";
			$ulos .= "<option value='P'{$sel['P']}>".t("Poistettu")."</option>";
			$ulos .= "</select></td>";

			$jatko = 0;
		}
		else {
			$tyyppi = 0;
		}
	}

	if (mysql_field_name($result, $i) == 'nakyvyys') {
		if (substr($trow['laji'], 0, 10) == 'lisatieto_') {
	 		$sel = array_fill_keys(array($trow[$i]), " selected") + array('o' => '', 'K' => '');

			$ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 300).">";
			$ulos .= "<option value=''>".t("Kyllä")."</option>";
			$ulos .= "<option value='K'>".t("Kyllä")." (".t("ei otsikkoa").")</option>";
			$ulos .= "<option value='o'{$sel['o']}>".t("Ei")."</option>";
			$ulos .= "</select></td>";

			$jatko = 0;
		}
		else {
			$tyyppi = 0;
		}
	}
