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

		$query = "	SELECT *
					FROM pakkaus
					WHERE yhtio = '{$kukarow['yhtio']}'
					ORDER BY pakkaus";
		$pakkaus_result = mysql_query($query) or pupe_error($query);

		$ulos .= "<input type='hidden' name='{$nimi}[]' value='default' />";

		while ($pakkaus_row = mysql_fetch_assoc($pakkaus_result)) {

			$chk = in_array($pakkaus_row['tunnus'], $sel) ? 'checked' : '';

			$ulos .= "<input type='checkbox' name='{$nimi}[]' value='{$pakkaus_row['tunnus']}' {$chk}/> ".t_tunnus_avainsanat($pakkaus_row, "pakkaus", "PAKKAUSKV")." ".t_tunnus_avainsanat($pakkaus_row, "pakkauskuvaus", "PAKKAUSKV")."<br />";
		}

		$ulos .= "</td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "keraysjarjestys") {
		$ulos = "<td>";

		$sel = array_merge(array('P' => '', 'V' => '', 'I' => ''), array_fill_keys(explode(",", $trow[$i]), " selected"));

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

		$sel = '';
		$ulos .= "<select name='{$nimi}'>";

		$terminaalialue_result = t_avainsana("TERMINAALIALUE");

		while ($terminaalialue_row = mysql_fetch_assoc($terminaalialue_result)) {

			$sel = $terminaalialue_row['selite'] != $trow[$i] ? '' : ' selected';

			$ulos .= "<option value='{$terminaalialue_row['selite']}'{$sel}>{$terminaalialue_row['selite']}</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "ulkoinen_jarjestelma") {
		$ulos = "<td>";

		$sel = array_merge(array('K' => '', 'V' => ''), array_fill_keys(explode(",", $trow[$i]), " selected"));

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

		$sel = array_merge(array('S' => '', 'K' => '', 'P' => ''), array_fill_keys(explode(",", $trow[$i]), " checked='checked'"));

		$ulos .= "<input type='hidden' name='{$nimi}[]' value='default' />";

		$ulos .= "<input type='checkbox' name='{$nimi}[]' value='S'{$sel['S']}>".t("Saa olla useita asiakkaita")."<br />";
		$ulos .= "<input type='checkbox' name='{$nimi}[]' value='K'{$sel['K']}>".t("Keräyserän alustoja voi olla useista lähdöistä")."<br />";
		$ulos .= "<input type='checkbox' name='{$nimi}[]' value='P'{$sel['P']}>".t("Keräyserässä voi olla useita prioriteettejä")."<br />";

		$ulos .= "</td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "varasto") {
		$ulos = "<td><select name='{$nimi}'>";

		$query = "	SELECT *
					FROM varastopaikat
					WHERE yhtio = '{$kukarow['yhtio']}' AND tyyppi != 'P'
					ORDER BY tyyppi, nimitys";
		$varasto_res = mysql_query($query) or pupe_error($query);

		while ($varasto_row = mysql_fetch_assoc($varasto_res)) {

			$sel = $varasto_row['tunnus'] == $trow[$i] ? ' selected' : '';

			$ulos .= "<option value='{$varasto_row['tunnus']}'{$sel}>{$varasto_row['nimitys']}</option>";
		}

		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (substr(mysql_field_name($result,$i),0,9) == 'printteri') {

		$ulos = "<td><select name='$nimi'>";

		$printterilisa = mysql_field_name($result,$i) == 'printteri8' ? " and komento != 'email' " : '';

		$query = "	SELECT *
					FROM kirjoittimet
					WHERE yhtio = '{$kukarow['yhtio']}'
					{$printterilisa}
					AND komento != 'EDI'
					ORDER BY kirjoitin";
		$kires = mysql_query($query) or pupe_error($query);

		$ulos .= "<option value=''>".t("Ei kirjoitinta")."</option>";

		while ($kirow = mysql_fetch_assoc($kires)) {

			$sel = $trow[$i] == $kirow['tunnus'] ? ' selected' : '';

			$ulos .= "<option value='{$kirow['tunnus']}'{$sel}>{$kirow['kirjoitin']}</option>";
		}

		$ulos .= "</select></td>";

		$jatko=0;
	}