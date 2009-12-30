<?php

if(!function_exists("tuotetarkista")) {
	function tuotetarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {
		global $kukarow, $yhtiorow, $alias_set, $laji, $poistolukko;

		static $tem_tuoteno, $tem_tullinimike1, $tem_eisaldoa, $tem_kehahin, $tem_kehahin_ind;

		if (mysql_field_name($result, $i) == "tuoteno") {
			if (trim($t[$i]) == '') {
				$virhe[$i] = t("Tieto puuttuu");
			}
			else {
				if ($tunnus == "") {
					$query  = "	SELECT yhtio
								FROM tuote
								WHERE yhtio = '$kukarow[yhtio]'
								and tuoteno = '".trim($t[$i])."'";
					$chkressiresult = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($chkressiresult) > 0) {
						$virhe[$i] = t("Tuotenumero on jo perustettu järjestelmään");
					}
				}
			}

			if ($poistolukko != "LUEDATA") {
				// Tuotteen saa poistaa mikäli sillä ei ole yhtään tapahtumaa
				$query = "SELECT yhtio FROM tapahtuma WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$t[$i]' LIMIT 1";
				$sdtres = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($sdtres) == 0) {
					$query = "SELECT yhtio FROM tilausrivi WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$t[$i]' and tyyppi IN ('B','E','G','L','O','V','W','F','N','M','Z','0','T') and laadittu > '0000-00-00 00:00:00' LIMIT 1";
					$sdtres = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($sdtres) == 0) {
						$poistolukko = "";
					}
				}
			}

			$tem_tuoteno = $t[$i];
		}

		if ((mysql_field_name($result, $i) == "projekti" or mysql_field_name($result, $i) == "kohde" or mysql_field_name($result, $i) == "kustp") and trim($t[$i]) != '') {

			if (mysql_field_name($result, $i) == "projekti") $kptyyppi = "P";
			if (mysql_field_name($result, $i) == "kohde") 	 $kptyyppi = "O";
			if (mysql_field_name($result, $i) == "kustp") 	 $kptyyppi = "K";

			$tpque = "SELECT tunnus from kustannuspaikka where yhtio='$kukarow[yhtio]' and tyyppi='$kptyyppi' and kaytossa != 'E' and tunnus='$t[$i]'";
			$tpres = mysql_query($tpque) or pupe_error($tpque);

			if (mysql_num_rows($tpres) == 0) {
				$virhe[$i] .= t("Kustannuspaikkaa ei löydy")."!";
			}
		}

		if ((mysql_field_name($result, $i) == "osasto" or mysql_field_name($result, $i) == "try" or mysql_field_name($result, $i) == "tuotemerkki" or mysql_field_name($result, $i) == "malli" or mysql_field_name($result, $i) == "mallitarkenne") and trim($t[$i]) != '') {

			$tpque = "SELECT tunnus FROM avainsana WHERE yhtio = '$kukarow[yhtio]' and LAJI = '".mysql_field_name($result, $i)."'";
			$tpres = mysql_query($tpque) or pupe_error($tpque);

			if (mysql_num_rows($tpres) > 0) {
				$tpque = "SELECT tunnus FROM avainsana WHERE yhtio = '$kukarow[yhtio]' and LAJI = '".mysql_field_name($result, $i)."' and SELITE = '$t[$i]'";
				$tpres = mysql_query($tpque) or pupe_error($tpque);

				if (mysql_num_rows($tpres) == 0) {
					$virhe[$i] .= t("Avainsanaa ei löydy")."!";
				}
			}
		}

		if (mysql_field_name($result, $i) == "kehahin" and $yhtiorow["palvelutuotteiden_kehahinnat"] == "K") {
			$tem_kehahin = $t[$i];
			$tem_kehahin_ind = $i;
		}

		if (mysql_field_name($result, $i) == "nimitys") {
			if (trim($t[$i]) == '') {
				$virhe[$i] .= t("Nimitys puuttuu")."!";
			}
		}

		if ((mysql_field_name($result, $i) == "tilino" or
			mysql_field_name($result, $i) == "tilino_eu" or
			mysql_field_name($result, $i) == "tilino_ei_eu" or
			mysql_field_name($result, $i) == "tilino_marginaali" or
			mysql_field_name($result, $i) == "tilino_osto_marginaali") and $t[$i] != 0) {

			$query = "	SELECT nimi
						FROM tili
						WHERE yhtio='$kukarow[yhtio]' and tilino = '$t[$i]'";
			$sresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($sresult) != 1) {
				$virhe[$i] = t("Tili puuttuu tai sitä ei loydy");
			}
		}

		if (mysql_field_name($result, $i) == "tilino" and trim($t[$i]) == "" and $laji == "V") {
			$virhe[$i] = t("Viranomaistuotteella on oltava tili")."!";
		}

		if ((mysql_field_name($result, $i) == "tullinimike1") and ($t[$i] != 0)) {
			$query = "	SELECT cn
						FROM tullinimike
						WHERE cn = '$t[$i]' and kieli = '$yhtiorow[kieli]'";
			$sresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($sresult) != 1) {
				$virhe[$i] = t("Virheellinen tullinimike");
			}

			$tem_tullinimike1 = $t[$i];
		}

		if (mysql_field_name($result, $i) == "tullinimike2" and $tem_tullinimike1 != "") {
			// jos tyhjää laitetaan nollaksi
			if ($t[$i] == "") $t[$i] = "00";
		}

		if (mysql_field_name($result, $i) == "tuotetyyppi") {
			// Tyyppiä ei saa vaihtaa, jos tuotteella on yksikin tapahtuma
			$query = "	SELECT tunnus
						FROM tapahtuma
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tuoteno = '$tem_tuoteno'
						LIMIT 1";
			$tapahtuma_chk_res = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($tapahtuma_chk_res) == 1) {
				$virhe[$i] = t("Tuotetyyppiä ei voida muuttaa, koska tuotteella on tapahtumia")."!";
			}
		}

		if (mysql_field_name($result, $i) == "sarjanumeroseuranta") {

			$query = "	SELECT count(*) saldo
						FROM tuotepaikat
						WHERE yhtio = '$kukarow[yhtio]'
						and tuoteno = '$tem_tuoteno'
						and saldo  != 0";
			$vresult = mysql_query($query) or pupe_error($query);
			$vrow = mysql_fetch_array($vresult);

			if ($poistolukko != "" and $t[$i] != $trow["sarjanumeroseuranta"]) {

				// Tietyt vaihdot sallitaan!!!, huomaa NOT iffissä, helpompi kirjoittaa näin päin
				if (!(($t[$i] == "S" and $vrow["saldo"] == 0 and in_array($trow["sarjanumeroseuranta"], array("S","U")))
				   or ($t[$i] == "U" and $vrow["saldo"] == 0 and in_array($trow["sarjanumeroseuranta"], array("S","U")))
				   or ($t[$i] == ""  and $vrow["saldo"] == 0 and in_array($trow["sarjanumeroseuranta"], array("","T","V","E","F")))
				   or ($t[$i] == "T" and $vrow["saldo"] == 0 and in_array($trow["sarjanumeroseuranta"], array("","T","V","E","F")))
				   or ($t[$i] == "V" and $vrow["saldo"] == 0 and in_array($trow["sarjanumeroseuranta"], array("","T","V","E","F")))
				   or ($t[$i] == "E" and (in_array($trow["sarjanumeroseuranta"], array("E","F")) or ($vrow["saldo"] == 0 and in_array($trow["sarjanumeroseuranta"], array("","T","V","E","F")))))
				   or ($t[$i] == "F" and (in_array($trow["sarjanumeroseuranta"], array("E","F")) or ($vrow["saldo"] == 0 and in_array($trow["sarjanumeroseuranta"], array("","T","V","E","F"))))))) {
					$virhe[$i] = t("Seurantatyyppiä ei voida muuttaa, koska tuotteella on tapahtumia")."!";
				}
			}
		}

		if (mysql_field_name($result, $i) == "ei_saldoa") {
			$query = "	SELECT *
						FROM tuotepaikat
						WHERE tuoteno = '$tem_tuoteno' and yhtio = '$kukarow[yhtio]'";
			$sresult = mysql_query($query) or pupe_error($query);

			if ($t[$i] != '') {
				if (mysql_num_rows($sresult) != 0) {
					$virhe[$i] = t("Tuotteella on varastopaikkoja. Poista ne ensin!");
				}
			}

			if ($poistolukko != "" and $t[$i] != $trow["ei_saldoa"]) {
				$virhe[$i] = t("Tuotteella on tapahtumia. Saldottomuutta ei voi vaihtaa")."!";
			}

			$tem_eisaldoa = $t[$i];
		}

		if (mysql_field_name($result, $i) == "vienti") {
			if ($t[$i] != '' and strpos($t[$i], "+") !== FALSE and strpos($t[$i], "-") !== FALSE) {
				$virhe[$i] = t("Laita samanaikaisesti vain + tai - maakoodeja!");
			}
		}

		if (mysql_field_name($result, $i) == "eankoodi") {
			if (trim($t[$i]) != 0 and trim($t[$i] != '')) {
				$query  = "	SELECT eankoodi
							FROM tuote
							WHERE yhtio = '$kukarow[yhtio]'
							and eankoodi = '".trim($t[$i])."'
							and tuoteno != '$tem_tuoteno'";
				$chkressiresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($chkressiresult) > 0) {
					$virhe[$i] = t("EAN-koodi on jo perustettu järjestelmään");
				}
			}
		}

		//tehdään nää jutut tässä koska tämä on aina viimeinen sarake
		if (mysql_field_name($result, $i) == "tunnus") {

			if ($tem_kehahin != "" and $tem_eisaldoa == "") {
				$virhe[$i] = $virhe[$tem_kehahin_ind] = t("Saldollisen tuotteen hankintahintaa ei voi muuttaa")."!";
			}

			if (count($virhe) == 0 and $tem_kehahin != "" and $tem_eisaldoa != "" and $yhtiorow["palvelutuotteiden_kehahinnat"] == "K") {
				$query  = "	SELECT kehahin
							FROM tuote
							WHERE yhtio = '$kukarow[yhtio]'
							and tuoteno = '$tem_tuoteno'";
				$chkressires = mysql_query($query) or pupe_error($query);
				$chkressirow = mysql_fetch_assoc($chkressires);

				$tem_kehahin = sprintf("%.6f", str_replace(",", ".", $tem_kehahin));

				if ($tem_kehahin != $chkressirow["kehahin"]) {
					$query = "	INSERT into tapahtuma set
								yhtio 		= '$kukarow[yhtio]',
								tuoteno 	= '$tem_tuoteno',
								kpl 		= '0',
								kplhinta	= '$tem_kehahin',
								hinta 		= '$tem_kehahin',
								laji 		= 'tulo',
								hyllyalue	= '',
								hyllynro 	= '',
								hyllyvali	= '',
								hyllytaso	= '',
								selite 		= '".t("Tuotteen kehahinta vaihdettiin")." $chkressirow[kehahin] --> $tem_kehahin',
								laatija 	= '$kukarow[kuka]',
								laadittu 	= now()";
					$chkressires = mysql_query($query) or pupe_error($query);
				}
			}

			if (count($virhe) == 0 and $tem_eisaldoa == "" and $yhtiorow["tuotteen_oletuspaikka"] != "") {

				$query = "	SELECT *
							FROM tuotepaikat
							WHERE tuoteno = '$tem_tuoteno' and yhtio = '$kukarow[yhtio]'";
				$sresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($sresult) == 0) {
					list($hyllyalue, $hyllynro, $hyllyvali, $hyllytaso) = explode("-", $yhtiorow["tuotteen_oletuspaikka"]);

					if ($hyllyalue == "" or !isset($hyllyalue)) {
						$hyllyalue = 0;
					}
					if ($hyllynro == "" or !isset($hyllynro)) {
						$hyllynro = 0;
					}
					if ($hyllyvali == "" or !isset($hyllyvali)) {
						$hyllyvali = 0;
					}
					if ($hyllytaso == "" or !isset($hyllytaso)) {
						$hyllytaso = 0;
					}

					$tuotepaikka_query = "	INSERT INTO tuotepaikat set
					 						yhtio			= '$kukarow[yhtio]',
								 			tuoteno     	= '$tem_tuoteno',
								 			oletus      	= 'X',
						   		 			saldoaika   	= now(),
											hyllyalue   	= '$hyllyalue',
											hyllynro    	= '$hyllynro',
											hyllyvali   	= '$hyllyvali',
											hyllytaso   	= '$hyllytaso',
											luontiaika		= now(),
											laatija			= '$kukarow[kuka]',
											muutospvm		= now(),
											muuttaja		= '$kukarow[kuka]'";
					$tuotepaikka_result = mysql_query($tuotepaikka_query) or pupe_error($tuotepaikka_query);
				}
			}
		}
	}
}

?>