<?php

	if (mysql_field_name($result, $i) == "ytunnus") {
		if ($ytunnus_select != '') {
			$t[$i] = $ytunnus_select;
		}
	}

	if (mysql_field_name($result, $i) == "ytunnus") {

		$ytunnus = trim($t[$i]);
		$ytunnus_tunnus = $i;

		if ($ytunnus == "") {
			$virhe[$i] = t("Y-tunnus puuttuu")."!";
			$errori = 1;
		}

	}

	if (mysql_field_name($result, $i) == "asiakasnro") {
		if ($asiakasnro_select != '') {
			$t[$i] = $asiakasnro_select;
		}
	}

	if (mysql_field_name($result, $i) == "toim_ovttunnus" and trim($t[$i]) != '') {

		$query   = "select tunnus from asiakas where yhtio='$kukarow[yhtio]' and toim_ovttunnus='$t[$i]'";
		$sresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($sresult) > 0) {
			$srowapu = mysql_fetch_array($sresult);
			// ei löydetty itteämme
			if ($srowapu['tunnus'] != $tunnus) {
				$virhe[$i] = t("Samalla toim_ovttunnuksella on useita asiakkaita! Lisää tarkenne toim_ovttunnukseen.");
				$errori = 1;
			}
		}
	}

	if (mysql_field_name($result, $i) == "verkkotunnus") {
		// verkkotunnus tulee datassa ennen CHN kenttää, joten otetaan tämä talteen
		$apuverkkotunnus  = trim($t[$i]);
		$apuverkkotunnusi = $i;
	}

	if (mysql_field_name($result, $i) == "chn") {
		// jos ollaan valittu jotain muuta kuin PAPERI niin verkkotunnus pitää syöttää!
		if (trim($t[$i]) != '100' and $apuverkkotunnus == "") {
			$virhe[$i] = "".t("Olet valinnut laskutustavaksi muun kuin Paperi ja verkkotunnus on tyhjä! Laskutus ei onnistu")."!";
			$virhe[$apuverkkotunnusi] = "".t("Olet valinnut laskutustavaksi muun kuin Paperi ja verkkotunnus on tyhjä! Laskutus ei onnistu")."!";
			$errori = 1;
		}
	}

	if (mysql_field_name($result, $i) == "email" and trim($t[$i]) != '') {
		// katotaan löytyykö email osoitteen domain...
		list($nimi, $domain) = split("@", trim($t[$i]));

		if ($domain != "") {
			exec("host -t MX $domain | grep \"not found\"", $output);
		}

		if (count($output) > 0 or $domain == "") {
			$virhe[$i] = t("Sähköpostin domain ei löydy")."! ($domain)";
			$errori = 1;
		}

	}

	if (mysql_field_name($result, $i) == "maksuehto" and trim($t[$i]) != '') {

		$query   = "select tunnus from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$t[$i]'
					and (sallitut_maat = '' or sallitut_maat like '%$trow[maa]%')";
		$sresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($sresult) == 0) {
			$virhe[$i] = t("Tätä maksuehtoa ei saa käyttää asiakkaalla tässä maassa.");
			$errori = 1;
		}
	}

	if (mysql_field_name($result, $i) == "toimitustapa" and trim($t[$i]) != '') {

		$query   = "select tunnus from toimitustapa where yhtio='$kukarow[yhtio]' and selite='$t[$i]'
					and (sallitut_maat = '' or sallitut_maat like '%$trow[toim_maa]%')";
		$sresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($sresult) == 0) {
			$virhe[$i] = t("Tätä toimitustapaa ei saa käyttää asiakkaalla tässä maassa.");
			$errori = 1;
		}
	}

	if (mysql_field_name($result, $i) == "maa") {

		if (strtoupper($t[$i]) == 'FI') {
			require ("tarkistaytunnus.inc");

			if ($ok == 0) {
				//$virhe[$ytunnus_tunnus] = t("Virheellinen y-tunnus")."! $t[$i] -> $ytunnus";
				//$errori = 1;
			}
			else {
				$t[$ytunnus_tunnus] = $ytunnus;
			}
		}
		elseif ($yhtiorow["maa"] == 'FI' and strtoupper($t[$i]) == 'SE' and strtoupper(substr($t[$ytunnus_tunnus], 0, 2)) != "SE") {
			$virhe[$ytunnus_tunnus] = t("Virheellinen y-tunnus")."! Laskutusosoite ruotsissa. Ytunnus pitää alkaa SE!";
			$errori = 1;
		}

	}
?>
