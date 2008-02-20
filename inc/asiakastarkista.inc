<?php

if(!function_exists("asiakastarkista")) {
	function asiakastarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {
		global $kukarow, $yhtiorow, $alias_set, $ulosarray, $ytunnus_select, $asiakasnro_select, $toim;
		
		static $asiak_laji, $ytunnus_tunnus, $ytunnus, $ovttunnus, $apuverkkotunnus;
		
		if (mysql_field_name($result, $i) == "laji") {
			$asiak_laji = $t[$i];
		}

		if (mysql_field_name($result, $i) == "ytunnus") {
			if ($ytunnus_select != '') {
				// Jos selectoitu drop downista niin katotaan, että tämä on vielä vapaa
				//jos konsernin asiakkaat synkronoidaan niin asiakkaiden yksilöivät tiedot on oltava konsernitasolla-yksilölliset
				$query = "	SELECT group_concat(concat('\'',yhtio.yhtio,'\'')) yhtiot
							FROM yhtio 
							JOIN yhtion_parametrit ON yhtion_parametrit.yhtio=yhtio.yhtio
							where konserni	 = '$yhtiorow[konserni]' 
							and synkronoi like '%$toim%'";
				$sresult = mysql_query($query) or pupe_error($query);
				$srowapu = mysql_fetch_array($vresult);

				if ($srowapu["yhtiot"] != "") {
					$tarkyhtio = $srowapu["yhtiot"];
				}
				else {
					$tarkyhtio = "'$kukarow[yhtio]'";
				}

				$query   = "select tunnus from asiakas where yhtio in ($tarkyhtio) and ytunnus='$ytunnus_select' and tunnus != '$tunnus'";
				$sresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($sresult) > 0) {
					if ($alias_set == "VAHITTAISMYYNTI") {
						$virhe[$i] = t("Valitsemasi asiakasnumero ei ollutkaan vapaa.");
					}
					else {
						$virhe[$i] = t("Valitsemasi ytunnus ei ollutkaan vapaa.");
					}
				}
				else {
					$t[$i] = $ytunnus_select;
				}
			}
		}

		if (mysql_field_name($result, $i) == "ytunnus" and $virhe[$i] == "") {

			$ytunnus = $t[$i];
			$ytunnus_tunnus = $i;

			if ($ytunnus == "") {
				$virhe[$i] = t("Y-tunnus puuttuu")."!";
			}

		}

		if (mysql_field_name($result, $i) == "asiakasnro") {
			if ($asiakasnro_select != '') {
				// Jos selectoitu drop downista niin katotaan, että tämä on vielä vapaa
				//jos konsernin asiakkaat synkronoidaan niin asiakkaiden yksilöivät tiedot on oltava konsernitasolla-yksilölliset
				$query = "	SELECT group_concat(concat('\'',yhtio.yhtio,'\'')) yhtiot
							FROM yhtio 
							JOIN yhtion_parametrit ON yhtion_parametrit.yhtio=yhtio.yhtio
							where konserni	 = '$yhtiorow[konserni]' 
							and synkronoi like '%$toim%'";
				$sresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($sresult) > 0) {
					$srowapu = mysql_fetch_array($sresult);
					
					if ($srowapu["yhtiot"] != '') {
						$tarkyhtio = $srowapu["yhtiot"];
					}
					else {
						$tarkyhtio = "'$kukarow[yhtio]'";
					}
				}
				else {
					$tarkyhtio = "'$kukarow[yhtio]'";
				}

				$query   = "SELECT tunnus from asiakas where yhtio in ($tarkyhtio) and asiakasnro='$asiakasnro_select' and tunnus != '$tunnus'";
				$sresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($sresult) > 0) {
					$virhe[$i] = t("Valitsemasi asiakasnumero ei ollutkaan vapaa.");
				}
				else {
					$t[$i] = $asiakasnro_select;
				}
			}
		}

		if (mysql_field_name($result, $i) == "ovttunnus") {
			$ovttunnus = $t[$i];	
		}

		if (mysql_field_name($result, $i) == "toim_ovttunnus") {

			$query   = "select tunnus from asiakas where yhtio = '$kukarow[yhtio]' and ytunnus='$ytunnus' and ovttunnus='$ovttunnus' and toim_ovttunnus='$t[$i]' and tunnus != '$tunnus'";
			$sresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($sresult) > 0) {
				if ($alias_set == "VAHITTAISMYYNTI") {
					$virhe[$i] = t("Samalla tiedoilla on useita asiakkaita! Lisää tarkenne Asiakasnumeroon/Henkilötunnukseen.");
				}
				else {
					$virhe[$i] = t("Samalla tiedoilla on useita asiakkaita! Lisää tarkenne Ytunnukseen/Ovttunnukseen/Toim_ovttunnukseen.");
				}
			}
		}

		if (mysql_field_name($result, $i) == "verkkotunnus") {
			// verkkotunnus tulee datassa ennen CHN kenttää, joten otetaan tämä talteen
			$apuverkkotunnus  = trim($t[$i]);
			$apuverkkotunnusi = $i;
		}

		if ((mysql_field_name($result, $i) == "tilino" or
			mysql_field_name($result, $i) == "tilino_eu" or
			mysql_field_name($result, $i) == "tilino_ei_eu" or
			mysql_field_name($result, $i) == "tilino_marginaali" or
			mysql_field_name($result, $i) == "tilino_osto_marginaali") and $t[$i] != '') {

			$query = "	SELECT nimi
						FROM tili
						WHERE yhtio='$kukarow[yhtio]' and tilino = '$t[$i]'";
			$sresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($sresult) != 1) {
				//Olisiko siellä nimi
				if ($t[$i] != '') {
					$query = "	SELECT nimi, tilino
								FROM tili
								WHERE yhtio = '$kukarow[yhtio]' and nimi like '%$t[$i]%'
								ORDER BY tilino";
					$sresult = mysql_query($query) or pupe_error($query);
					
					if (mysql_num_rows($sresult) > 0) {
						$ulosarray[$i] = "<td><select name='t[$i]'><option value = '' $sel>".t("Ei valintaa")."</option>";
						
						while ($vrow = mysql_fetch_array($sresult)) {
							$sel = "";
							if ($trow[$i] == $vrow[0]) {
								$sel = "selected";
							}
							$ulosarray[$i] .= "<option value = '$vrow[tilino]' $sel>$vrow[tilino] $vrow[nimi]</option>";
						}
						
						$ulosarray[$i] .= "</select></td>";
						$virhe[$i] = t("Valitse tili");
					}
					else {
						$virhe[$i] = t("Tili puuttuu tai sitä ei loydy");
					}

				}
				if ($virhe[$i] == '') $virhe[$i] = t("Tiliä ei löydy");
			}
		}

		if (mysql_field_name($result, $i) == "chn") {
			// jos ollaan valittu jotain muuta kuin PAPERI niin verkkotunnus pitää syöttää!
			if (isset($t[$i]) and trim($t[$i]) != '100' and $apuverkkotunnus == "") {
				$virhe[$i] = "".t("Olet valinnut laskutustavaksi muun kuin Paperi ja verkkotunnus on tyhjä! Laskutus ei onnistu")."!";
				$virhe[$apuverkkotunnusi] = "".t("Olet valinnut laskutustavaksi muun kuin Paperi ja verkkotunnus on tyhjä! Laskutus ei onnistu")."!";
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
			}

		}

		if (mysql_field_name($result, $i) == "maksuehto" and $t[$i] > 0) {

			$query   = "select tunnus from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$t[$i]'
						and (sallitut_maat = '' or sallitut_maat like '%$trow[maa]%')";
			$sresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($sresult) == 0) {
				$virhe[$i] = t("Tätä maksuehtoa ei saa käyttää asiakkaalla tässä maassa.");
			}
		}

		if (mysql_field_name($result, $i) == "toimitustapa" and trim($t[$i]) != '') {

			$query   = "select tunnus from toimitustapa where yhtio='$kukarow[yhtio]' and selite='$t[$i]'
						and (sallitut_maat = '' or sallitut_maat like '%$trow[toim_maa]%')";
			$sresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($sresult) == 0) {
				$virhe[$i] = t("Tätä toimitustapaa ei saa käyttää asiakkaalla tässä maassa.");
			}
		}

		if (mysql_field_name($result, $i) == "maa") {
			if (strtoupper($t[$i]) == 'FI' and ($yhtiorow["ytunnus_tarkistukset"] == "" or $yhtiorow["ytunnus_tarkistukset"] == "A")) {
				require ("tarkistaytunnus.inc");

				if ($ok == 0) {
					$virhe[$ytunnus_tunnus] = t("Virheellinen y-tunnus")."! $t[$i] -> $ytunnus";
				}
				else {
					 $t[$ytunnus_tunnus] = $ytunnus;
				}
			}
			elseif ($yhtiorow["maa"] == 'FI' and strtoupper($t[$i]) == 'SE' and strtoupper(substr($t[$ytunnus_tunnus], 0, 2)) != "SE" and $asiak_laji != "H") {
				$virhe[$ytunnus_tunnus] = t("Virheellinen y-tunnus")."! Laskutusosoite ruotsissa. Ytunnus pitää alkaa SE!";
			}

		}
	}
}
	
?>
