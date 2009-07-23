<?php


if(!function_exists("tuotetarkista")) {
	function tuotetarkista (&$t, $i, $result, $tunnus, &$virhe, $trow, $tuotteen_parametrit_keys = "", $tuotteen_parametrit_vals = "") {
		global $kukarow, $yhtiorow, $alias_set, $laji, $poistolukko;

		static $tem_tuoteno, $tem_tullinimike1, $tem_eisaldoa, $tem_kehahin;
		
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
			$tem_tuoteno = $t[$i];
		}
		
		if (mysql_field_name($result, $i) == "kehahin" and $yhtiorow["palvelutuotteiden_kehahinnat"] == "K") {
			$tem_kehahin = $t[$i];
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
			
			/* CHECKKI POIS TOISTAISEKSI, LIIAN JYRKKÄ
			// varmistetaan, että on numeerinen
			if (!is_numeric($t[$i])) {
				$virhe[$i] = t("Tullinimike2 tulee olla numeerinen");
			}
			else {
				// haetaan kaikkien toimittajien alkuperämaat
				$query = "	SELECT group_concat(distinct concat(\"'\",alkuperamaa,\"'\") separator ','), count(*) kpl
							FROM tuotteen_toimittajat
							WHERE yhtio = '$kukarow[yhtio]'
							and tuoteno = '$tem_tuoteno'
							and alkuperamaa != ''";
				$sresult = mysql_query($query) or pupe_error($query);
				$sresrow = mysql_fetch_array($sresult);

				// jos ei ole yhtään toimittajaa ei voida tehdä tätä testiä
				if ($sresrow["kpl"] > 0) {
					// käännetään tullinimike 2 pitkäsksi numeroksi nollapädätty
					$t[$i] = sprintf('%02s', (int) $t[$i]);

					// kokeillaan näillä tiedoilla onko tullinimike1+2 oikein
					$laskurow["maa_lahetys"] = trim($sresrow[0], "'"); // otetaan eka ja vika hipsu pois, niin ei rikota taricveroperusteet.incciä
					$tuorow["tullinimike1"] = $tem_tullinimike1;
					$tuorow["tullinimike2"] = $t[$i];
					$tuorow["tuoteno"] = $tem_tuoteno;

					ob_start();
					require("tilauskasittely/taric_veroperusteet.inc");
					$virhe[$i] = ob_get_contents();
					ob_end_clean();
				}
			}
			*/

		}

		if (mysql_field_name($result, $i) == "tuotetyyppi") {
			// Tyyppiä ei saa vaihtaa, jos tuotteella on saldoa
			$query  = "	SELECT tuotetyyppi
		    			FROM tuote
		    			WHERE yhtio = '$kukarow[yhtio]'
		    			and tuoteno = '$tem_tuoteno'";
		    $sresult = mysql_query($query) or pupe_error($query);
		    $srow = mysql_fetch_array($sresult);

		    if (trim(strtoupper($srow["tuotetyyppi"])) != trim(strtoupper($t[$i]))) {
		    	$query = "	SELECT count(*) kpl
		    				FROM tuotepaikat
		    				WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$tem_tuoteno' and saldo != 0";
		    	$sresult = mysql_query($query) or pupe_error($query);
		    	$srow = mysql_fetch_array($sresult);

		    	if ($srow["kpl"] > 0) {
					$virhe[$i] = t("Tuotetyyppiä ei voida muuttaa, koska tuotteella on saldoa")."!";
				}
		    }
		}

		if (mysql_field_name($result, $i) == "sarjanumeroseuranta") {
			if ($poistolukko != "" and $t[$i] != $trow["sarjanumeroseuranta"]) {
				$virhe[$i] = t("Seurantatyyppiä ei voida muuttaa, koska tuotteella on tapahtumia")."!";
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
				$virhe[$i] = t("Tuotteella on tapahtumia. Saldottomuutta ei voi vaihtaa!");
			}
			
			$tem_eisaldoa = $t[$i];
		}

		if (mysql_field_name($result, $i) == "vienti") {
			if ($t[$i] != '' and strpos($t[$i], "+") !== FALSE and strpos($t[$i], "-") !== FALSE) {
				$virhe[$i] = t("Laita samanaikaisesti vain + tai - maakoodeja!");
			}
		}

		if (mysql_field_name($result, $i) == "tuotteen_parametrit") {

			$tuotteen_parametrit = "";

			if (is_array($tuotteen_parametrit_keys)) {
				foreach ($tuotteen_parametrit_keys as $key => $val) {
					if ($tuotteen_parametrit_vals[$key] != '') {
						if (trim(strtolower($val)) == 'variaatio_jako' and trim(strtolower($tuotteen_parametrit_vals[$key])) == trim(strtolower('variaatio'))) {
							$virhe[$i] = t("Variaatio_jako ei saa olla 'variaatio'-niminen");
						}
						elseif (trim(strtolower($val)) == 'variaatio' and trim(strtolower($tuotteen_parametrit_vals[$key])) == trim(strtolower($t['tuoteno']))) {
							$virhe[$i] = t("Variaatio ei saa olla tuotteen tuotenumero");
						}
						else {
							$tuotteen_parametrit .= "$val=$tuotteen_parametrit_vals[$key];";
						}
					}
				}

				$tuotteen_parametrit_query = "	UPDATE tuote
												SET tuotteen_parametrit = '$tuotteen_parametrit'
												WHERE yhtio = '$kukarow[yhtio]'
												AND tuoteno = '$t[tuoteno]'";
				$tuotteen_parametrit_result = mysql_query($tuotteen_parametrit_query) or pupe_error($tuotteen_parametrit_query);
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
	
		//tehdään nää jutu tässä koska tämä on nyt viimeinen sarake tässä taulussa
		if (mysql_field_name($result, $i) == "ostajanro") {
			
			if (count($virhe) == 0 and $tem_eisaldoa == "") {
				if ($yhtiorow["tuotteen_oletuspaikka"] != "" and mysql_num_rows($sresult) == 0) {
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
			
			
			if (count($virhe) == 0 and $tem_kehahin != "" and $tem_eisaldoa != "" and $yhtiorow["palvelutuotteiden_kehahinnat"] == "K") {
				$query  = "	SELECT kehahin
							FROM tuote
							WHERE yhtio = '$kukarow[yhtio]'
							and tuoteno = '$tem_tuoteno'";
				$chkressires = mysql_query($query) or pupe_error($query);
				$chkressirow = mysql_fetch_assoc($chkressires);
				
				$tem_kehahin = sprintf("%.6f", $tem_kehahin);
				
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
		}
	}
}

?>