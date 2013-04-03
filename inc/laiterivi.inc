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

if (mysql_field_name($result, $i) == "paikka") {
	$query = "	SELECT tunnus, concat_ws(' ', nimi, osoite) as dropdown_text
				FROM paikka
				WHERE yhtio = '{$kukarow['yhtio']}'";
	$paikka_result = pupe_query($query);
	$paikat = array();
	while($paikka = mysql_fetch_assoc($paikka_result)) {
		$paikat[] = $paikka;
	}

	$ulos = "<td>";
	$ulos .= "<select name='{$nimi}'>";
	foreach ($paikat as $paikka) {
		//jos valittu_paikka setattu ollaan tulossa asikkaan_laite_hallinnasta
		if(!empty($valittu_paikka) and $valittu_paikka == $paikka['tunnus']) {
			$sel = "SELECTED";
		}
		else {
			$sel = "";
		}
		$ulos .= "<option {$sel} value='{$paikka['tunnus']}'>{$paikka['dropdown_text']}</option>";
	}
	$ulos .= "</select>";
	$ulos .= "</td>";

	$jatko = 0;
}

//TODO sarjanro. laitteeseen isketty sarjanumero. valmistenumero toisinsanoen. tämä pitää syöttää käsin
if (mysql_field_name($result, $i) == "sarjanro") {
	
}

//TODO oma_numero. kohteen omistajan antana numero. esim tussilla vedetty kylkeen. tämä pitää syöttää käsin
if (mysql_field_name($result, $i) == "oma_numero") {

}

//TODO omistaja. voi olla asiakas tai huoltofirma. ei ole relaatio. tekstikenttä johon voidaan syöttää poikkeava omistaja. tämän avulla saadaan esim. selville kuuluukoo joltain paikalta löytyvä laite ylipäätänsä huollon piiriin.
if (mysql_field_name($result, $i) == "omistaja") {

}

if (!isset($redirect_echoed)) {
	$redirect_echoed = 1;
	if (!empty($valittu_paikka)) {
		//redirektiin tarvitaan valittu_asiakas joten haetaan paikan kohde, jolla on asiakas asiakas
		$query = "	SELECT asiakas.tunnus
					FROM asiakas
					JOIN kohde
					ON ( kohde.yhtio = asiakas.yhtio
						AND kohde.asiakas = asiakas.tunnus)
					JOIN paikka
					ON ( paikka.yhtio = kohde.yhtio
						AND paikka.kohde = kohde.tunnus
						AND paikka.tunnus = '{$valittu_paikka}')
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