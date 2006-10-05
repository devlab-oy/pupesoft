<?php

	if (mysql_field_name($result, $i) == "maa") {
		$tmp_maa = $t[$i];
	}

	if ((mysql_field_name($result, $i) == "ytunnus") and (trim($t[$i])!='')) {

		/*$ytunnus = trim($t[$i]);

		if (strtoupper($tmp_maa) == 'FI') {
			require ("tarkistaytunnus.inc");
		}

		if ($ok == 0) {
			$virhe[$i] = t("Virheellinen y-tunnus")."! $t[$i] -> $ytunnus";
			#$errori = 1;
		}
		else {
			$t[$i] = $ytunnus;
		}*/
	}

	if (mysql_field_name($result, $i) == "laatija") {
		if ($tunnus == "") {
			$t[$i] = $kukarow['kuka'];
		}
	}

	if (mysql_field_name($result, $i) == "luontiaika") {
		if ($tunnus == "") {
			$t[$i] = date('Y-m-d H:i:s');
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
?>
