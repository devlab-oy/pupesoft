<?php

$ulos = "";
$jatko = 1; // oletetaan normaali käsittely
$tyyppi = 1; // oletetaan rivin näkyvyys

if (mysql_field_name($result, $i) == "tunnus" or mysql_field_name($result, $i) == "yhtio") {
	$tyyppi = 0;
}

if (mysql_field_type($result, $i) == "blob") {
	$ulos = "<td><textarea cols='50' rows='5' name='$nimi'>$trow[$i]</textarea></td>";
	$jatko = 0;
}

if (mysql_field_name($result, $i) == "kohde") {
	$query = "	SELECT tunnus, concat_ws(' ', nimi, osoite) as dropdown_text
				FROM kohde
				WHERE yhtio = '{$kukarow['yhtio']}'";
	$kohde_result = pupe_query($query);
	$kohteet = array();
	while ($kohde = mysql_fetch_assoc($kohde_result)) {
		if (empty($valittu_kohde)) {
			if ($trow[$i] == $kohde['tunnus']) {
				$kohde['selected'] = "SELECTED";
			}
			else {
				$kohde['selected'] = "";
			}
		}
		else {
			//jos valittu_kohde setattu ollaan tulossa asikkaan_laite_hallinnasta
			if ($valittu_kohde == $kohde['tunnus']) {
				$kohde['selected'] = "SELECTED";
			}
			else {
				$kohde['selected'] = "";
			}
		}
		$kohteet[] = $kohde;
	}

	$ulos = "<td>";
	$ulos .= "<select name='{$nimi}'>";
	foreach ($kohteet as $kohde) {
		$ulos .= "<option {$kohde['selected']} value='{$kohde['tunnus']}'>{$kohde['dropdown_text']}</option>";
	}
	$ulos .= "</select>";
	$ulos .= "</td>";

	$jatko = 0;
}

//TODO paikan tyyppi esim sammutinkaappi tai ajoneuvo esim trukki. ei ole tarkemmin sovittu että tyypitetään vielä
//if (mysql_field_name($result, $i) == "tyyppi") {
//	$query = "	SELECT avainsana.selite tyyppi, avainsana.selitetark selitetark
//				FROM avainsana
//				WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='paikka_tyyppi'";
//	$paikka_tyyppi_result = pupe_error($query);
//
//	if (mysql_num_rows($paikka_tyyppi_result) > 0) {
//		$ulos = "<td><select id='tyyppi' name='$nimi'>";
//		$ulos .= "<option value = ''>".t("Ei tyyppiä")."</option>";
//
//		while ($srow = mysql_fetch_array($paikka_tyyppi_result)) {
//			$sel = (strtoupper($trow[$i]) == strtoupper($srow["tyyppi"])) ? "selected" : "";
//			$ulos .= "<option value='$srow[tyyppi]' $sel>$srow[selitetark]</option>";
//		}
//
//		$ulos .= "</select></td>\n";
//		$jatko = 0;
//	}
//	else {
//		$ulos = "";
//		$tyyppi = 0;
//		$jatko = 0;
//	}
//}
//TODO paikan olosuhde mistä tulee? vois tehdä avainsanan. jos ulkona tai tärinässä -> tarkastuksia vuoden välein, sisätiloissa kahden vuoden välein
if (mysql_field_name($result, $i) == "olosuhde") {
	$query = "	SELECT *
				FROM avainsana
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND laji = 'OLOSUHDE'";
	$olosuhde_result = pupe_query($query);
	$olosuhteet = array();
	while ($olosuhde = mysql_fetch_assoc($olosuhde_result)) {
		if ($trow[$i] == $olosuhde['tunnus']) {
			$olosuhde['selected'] = "SELECTED";
		}
		else {
			$olosuhde['selected'] = "";
		}
		$olosuhteet[] = $olosuhde;
	}

	$ulos = "<td>";
	$ulos .= "<select name='{$nimi}'>";
	foreach ($olosuhteet as $olosuhde) {
		$ulos .= "<option value='{$olosuhde['selite']}' {$olosuhde['selected']}>{$olosuhde['selitetark']}</option>";
	}
	$ulos .= "</select>";
	$ulos .= "</td>";

	$jatko = 0;
}

if (!isset($redirect_echoed)) {
	$redirect_echoed = 1;
	if (!empty($valittu_kohde)) {
		//redirektiin tarvitaan valittu_asiakas joten haetaan kohteen asiakas
		$query = "	SELECT asiakas.tunnus
					FROM asiakas
					JOIN kohde
					ON ( kohde.yhtio = asiakas.yhtio
						AND kohde.asiakas = asiakas.tunnus
						AND kohde.tunnus = '{$valittu_kohde}')
					WHERE asiakas.yhtio = '{$kukarow['yhtio']}'";
		$asiakas_result = pupe_query($query);
		$asiakas_row = mysql_fetch_assoc($asiakas_result);
		$valittu_asiakas = $asiakas_row['tunnus'];
		echo "<input type='hidden' name='redirect_to' value='asiakkaan_laite_hallinta.php' />";
		echo "<input type='hidden' name='valittu_asiakas' value='{$valittu_asiakas}' />";
	}
	else {
		echo "<input type='hidden' name='redirect_to_laite_hallinta' value='0' />";
	}
}