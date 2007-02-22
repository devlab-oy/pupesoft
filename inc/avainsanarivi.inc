<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys
	if ($i == 0) { //yhtiötä ei näytetä
		$tyyppi = 0;
	}
	if ($i == 1) {
		if ($trow[$i] == 'T') {
			$sel01 = "selected";
		}
		if ($trow[$i] == 'O') {
			$sel02 = "selected";
		}
		if ($trow[$i] == 'Y') {
			$sel03 = "selected";
		}
		if ($trow[$i] == 'C') {
			$sel04 = "selected";
		}
		if ($trow[$i] == 'ASIAKASLUOKKA') {
			$sel05 = "selected";
		}
		if ($trow[$i] == 'S') {
			$sel06 = "selected";
		}
		if ($trow[$i] == 'KM') {
			$sel07 = "selected";
		}
		if ($trow[$i] == 'KT') {
			$sel08 = "selected";
		}
		if ($trow[$i] == 'pakkaus') {
			$sel09 = "selected";
		}
		if ($trow[$i] == 'TV') {
			$sel10 = "selected";
		}
		if ($trow[$i] == 'TRY') {
			$sel11 = "selected";
		}
		if ($trow[$i] == 'OSASTO') {
			$sel12 = "selected";
		}
		if ($trow[$i] == 'KALETAPA') {
			$sel13 = "selected";
		}
		if ($trow[$i] == 'TUOTEULK') {
			$sel14 = "selected";
		}
		if ($trow[$i] == 'ASIAKASOSASTO') {
			$sel15 = "selected";
		}
		if ($trow[$i] == 'ASIAKASRYHMA') {
			$sel16 = "selected";
		}
		if ($trow[$i] == 'ALV') {
			$sel17 = "selected";
		}
		if ($trow[$i] == 'LAHETETYYPPI') {
			$sel18 = "selected";
		}
		if ($trow[$i] == 'TULLI') {
			$sel19 = "selected";
		}
		if ($trow[$i] == 'KASSA') {
			$sel20 = "selected";
		}
		if ($trow[$i] == 'HENKILO_OSASTO') {
			$sel21 = "selected";
		}
		if ($trow[$i] == 'PIIRI') {
			$sel22 = "selected";
		}
		if ($trow[$i] == 'TOIMEHTO') {
			$sel23 = "selected";
		}
		if ($trow[$i] == 'RAHTIKIRJA') {
			$sel24 = "selected";
		}
		if ($trow[$i] == 'MYSQLALIAS') {
			$sel25 = "selected";
		}
		if ($trow[$i] == 'LASKUKUVAUS') {
			$sel26 = "selected";
		}
		if ($trow[$i] == 'KERAYSLISTA') {
			$sel27 = "selected";
		}
		if ($trow[$i] == 'PAKKAUSKUVAUS') {
			$sel28 = "selected";
		}
		if ($trow[$i] == 'ALVULK') {
			$sel29 = "selected";
		}

		$ulos = "<td><select name='$nimi' onchange='submit();'>";

		$ulos .= "<option value='T'"			 .$sel01.">".t("Toimitustapa")."</option>";
		$ulos .= "<option value='Y'"			 .$sel03.">".t("Yksikko")."</option>";
		$ulos .= "<option value='C'"			 .$sel04.">".t("CHN tietue")."</option>";
		$ulos .= "<option value='ASIAKASLUOKKA'" .$sel05.">".t("Asiakasluokka")."</option>";
		$ulos .= "<option value='ASIAKASOSASTO'" .$sel15.">".t("Asiakasosasto")."</option>";
		$ulos .= "<option value='ASIAKASRYHMA'"  .$sel16.">".t("Asiakasryhma")."</option>";
		$ulos .= "<option value='S'"			 .$sel06.">".t("Tuotteen status")."</option>";
		$ulos .= "<option value='KM'"			 .$sel07.">".t("Kuljetusmuoto")."</option>";
		$ulos .= "<option value='KT'"			 .$sel08.">".t("Kauppatapahtuman luonne")."</option>";
		$ulos .= "<option value='pakkaus'"		 .$sel09.">".t("Pakkaus")."</option>";
		$ulos .= "<option value='TV'"			 .$sel10.">".t("Tilausvahvistus")."</option>";
		$ulos .= "<option value='TRY'"			 .$sel11.">".t("Tuoteryhmä")."</option>";
		$ulos .= "<option value='OSASTO'"		 .$sel12.">".t("Tuoteosasto")."</option>";
		$ulos .= "<option value='KALETAPA'"		 .$sel13.">".t("CRM yhteydenottotapa")."</option>";
		$ulos .= "<option value='TUOTEULK'"		 .$sel14.">".t("Tuotteiden avainsanojen laji")."</option>";
		$ulos .= "<option value='ALV'"			 .$sel17.">".t("ALV%")."</option>";
		$ulos .= "<option value='LAHETETYYPPI'"	 .$sel18.">".t("Lähetetyyppi")."</option>";
		$ulos .= "<option value='TULLI'"		 .$sel19.">".t("Poistumistoimipaikka")."</option>";
		$ulos .= "<option value='KASSA'"		 .$sel20.">".t("Kassa")."</option>";
		$ulos .= "<option value='HENKILO_OSASTO'".$sel21.">".t("Henkilöosasto")."</option>";
		$ulos .= "<option value='TOIMEHTO'"      .$sel22.">".t("Toimitusehto")."</option>";
		$ulos .= "<option value='PIIRI'"         .$sel22.">".t("Asiakkaan piiri")."</option>";
		$ulos .= "<option value='TOIMEHTO'"      .$sel23.">".t("Toimitusehto")."</option>";
		$ulos .= "<option value='RAHTIKIRJA'"    .$sel24.">".t("Rahtikirjatyyppi")."</option>";
		$ulos .= "<option value='MYSQLALIAS'"    .$sel25.">".t("Tietokantasarakkeen nimialias")."</option>";
		$ulos .= "<option value='LASKUKUVAUS'"   .$sel26.">".t("Maksuposition kuvaus")."</option>";
		$ulos .= "<option value='KERAYSLISTA'"   .$sel27.">".t("Keräyslista")."</option>";
		$ulos .= "<option value='PAKKAUSKUVAUS'" .$sel28.">".t("Pakkauskuvauksen tarkenne")."</option>";
		$ulos .= "<option value='ALVULK'"        .$sel29.">".t("Ulkomaan ALV%")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "selite" and $sel27 == "selected") {

		$handle = opendir("tilauskasittely");

		$ulos  = "<td><select name='$nimi'>";

		while ($file = readdir($handle)) {
			if (strpos($file,"kerayslista") !== FALSE) {
				$sel = "";

				if ($file == $trow[$i]) {
					$sel = "SELECTED";
				}

				$ulos .= "<option value='$file' $sel>".t($file)."</option>";
			}
		}
		closedir($handle);

		$ulos .= "</select></td>";
		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "selite" and $sel24 == "selected") {

		$handle = opendir("tilauskasittely");

		$ulos  = "<td><select name='$nimi'>";

		$ulos .= "<option value=''></option>";

		while ($file = readdir($handle)) {
			if (substr($file,0,10) == 'rahtikirja') {
				$sel = "";

				if ($file == $trow[$i]) {
					$sel = "SELECTED";
				}

				$ulos .= "<option value='$file' $sel>".t($file)."</option>";
			}
		}
		closedir($handle);

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "selite" and $sel18 == "selected") {

		$handle = opendir("tilauskasittely");

		$ulos  = "<td><select name='$nimi'>";

		$ulos .= "<option value=''></option>";

		while ($file = readdir($handle)) {
			if (substr($file,0,14) == 'tulosta_lahete') {
				$sel = "";

				if ($file == $trow[$i]) {
					$sel = "SELECTED";
				}

				$ulos .= "<option value='$file' $sel>".t($file)."</option>";
			}
		}
		closedir($handle);

		$ulos .= "</select></td>";
		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "selitetark" and $sel29 == "selected") {

		$query = "	SELECT distinct koodi, nimi
					FROM maat
					WHERE nimi != ''
					ORDER BY koodi";
		$vresult = mysql_query($query) or pupe_error($query);
		$ulos = "<td><select name='$nimi'>";

		$ulos .= "<option value = '' ></option>";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if (strtoupper($trow[$i]) == strtoupper($vrow[0])) {
				$sel = "selected";
			}
			elseif($trow[$i] == "" and strtoupper($vrow[0]) == strtoupper($yhtiorow["maakoodi"])) {
				$sel = "selected";
			}
			$ulos .= "<option value = '".strtoupper($vrow[0])."' $sel>".t($vrow[1])."</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}

?>