<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys

	if (mysql_field_name($result, $i) == "tunnus" or
	    mysql_field_name($result, $i) == "yhtio") {
		$tyyppi = 0;
	}

	if (mysql_field_name($result, $i) == "sallitut_alustat") {
		$ulos = "<td>";

		$sel = explode(",", $trow[$i]);

		$query = "SELECT tunnus, pakkaus FROM pakkaus WHERE yhtio = '{$kukarow['yhtio']}'";
		$pakkaus_result = mysql_query($query) or pupe_error($query);

		$ulos .= "<input type='hidden' name='{$nimi}[]' value='default' />";

		while ($pakkaus_row = mysql_fetch_assoc($pakkaus_result)) {

			$chk = in_array($pakkaus_row['tunnus'], $sel) ? 'checked' : '';

			$ulos .= "<input type='checkbox' name='{$nimi}[]' value='{$pakkaus_row['tunnus']}' {$chk}/> {$pakkaus_row['pakkaus']}<br />";
		}

		$ulos .= "</td>";
		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "keraysjarjestys") {
		$ulos = "<td>";

		$sel = array();
		$sel[$trow[$i]] = " selected";

		$ulos .= "<select name='{$nimi}'>";
		$ulos .= "<option value='P'{$sel['P']}>".t("Tuotteen paino")."</option>";
		$ulos .= "<option value='V'{$sel['V']}>".t("Tuotteen varastopaikka")."</option>";
		$ulos .= "<option value='I'{$sel['I']}>".t("Varastopaikan indeksi")."</option>";
		$ulos .= "</select>";

		$ulos .= "</td>";
		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "terminaalialue") {
		$ulos = "<td>";

		$sel = array();
		$sel[$trow[$i]] = " selected";

		$ulos .= "<select name='{$nimi}'>";

		$terminaalialue_result = t_avainsana("TERMINAALIALUE");

		while ($terminaalialue_row = mysql_fetch_assoc($terminaalialue_result)) {
			$ulos .= "<option value='{$terminaalialue_row['selite']}'{$sel[$terminaalialue_row['selite']]}>{$terminaalialue_row['selite']}</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "ulkoinen_jarjestelma") {
		$ulos = "<td>";

		$sel = array();
		$sel[$trow[$i]] = " selected";

		$ulos .= "<select name='{$nimi}'>";
		$ulos .= "<option value=''>".t("Ei käytössä")."</option>";
		$ulos .= "<option value='K'{$sel['K']}>".t("Kardex")."</option>";
		$ulos .= "<option value='V'{$sel['V']}>".t("Vocollect")."</option>";
		$ulos .= "</select>";

		$ulos .= "</td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "yhdistelysaanto") {
		$ulos = "<td>";

		$sel = array();
		$sel[$trow[$i]] = " selected";

		$ulos .= "<select name='{$nimi}'>";
		$ulos .= "<option value=''>".t("Saa olla useita asiakkaita")."</option>";
		$ulos .= "<option value='K'{$sel['K']}>".t("Keräyserän alustoja voi olla useissa lähdöissä")."</option>";
		$ulos .= "<option value='P'{$sel['P']}>".t("Alustoissa voi olla useita prioriteettejä")."</option>";
		$ulos .= "</select>";

		$ulos .= "</td>";
		$jatko = 0;
	}
