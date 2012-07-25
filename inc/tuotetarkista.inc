<?php

if(!function_exists("tuotetarkista")) {
	function tuotetarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {
		global $kukarow, $yhtiorow, $alias_set, $laji, $poistolukko;

		static $tem_tuoteno, $tem_tullinimike1, $tem_eisaldoa, $tem_kehahin, $tem_kehahin_ind, $tem_tuotetyyppi, $tem_status;

		if (mysql_field_name($result, $i) == "tuoteno") {
			if (trim($t[$i]) == '') {
				$virhe[$i] = t("Tieto puuttuu");
			}
			else {
				$temp_ekakirjain = (substr(trim($t[$i]),0,1));

				if (($temp_ekakirjain == '*') or ($temp_ekakirjain == '@') or ($temp_ekakirjain == '?')) {
					$virhe[$i] = t("Tuotenumero ei voi alkaa merkeillä *, @ tai ?");
				}
				else {
					if (substr(trim($t[$i]),-1) == '*') {
						$virhe[$i] = t("Tuotenumero ei voi loppua merkkiin *");
					}
					else {
						if ($tunnus == "") {
							$query  = "	SELECT yhtio
										FROM tuote
										WHERE yhtio = '$kukarow[yhtio]'
										and tuoteno = '".trim($t[$i])."'";
							$chkressiresult = pupe_query($query);

							if (mysql_num_rows($chkressiresult) > 0) {
								$virhe[$i] = t("Tuotenumero on jo perustettu järjestelmään");
							}
						}
					}
				}
			}

			if ($poistolukko != "LUEDATA") {
				// Tuotteen saa poistaa mikäli sillä ei ole yhtään tapahtumaa
				$query = "	SELECT yhtio
							FROM tapahtuma USE INDEX (yhtio_laji_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and laji in ('epäkurantti','inventointi','kulutus','laskutus','siirto','tulo','valmistus')
							and tuoteno = '{$t[$i]}'
							LIMIT 1";
				$sdtres = pupe_query($query);

				if (mysql_num_rows($sdtres) == 0) {
					// Ei saa olla myöskään yhtään tilausriviä (Puuterivit sallitaan)
					$query = "	SELECT yhtio
								FROM tilausrivi USE INDEX (yhtio_tyyppi_tuoteno_laadittu)
								WHERE yhtio = '$kukarow[yhtio]'
								and tyyppi in ('0','B','E','F','G','L','M','N','O','T','V','W','Z')
								and tuoteno = '{$t[$i]}'
								and laadittu > '0000-00-00 00:00:00'
								and var != 'P'
								LIMIT 1";
					$sdtres = pupe_query($query);

					if (mysql_num_rows($sdtres) == 0) {
						$poistolukko = "";
					}
				}
			}

			$tem_tuoteno = $t[$i];
		}

		if (mysql_field_name($result, $i) == "panttitili" and trim($t[$i]) == '') {

			if (trim($t[$i]) != trim($trow[$i])) {
				$query = "	SELECT *
							FROM panttitili
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND asiakas != ''
							AND status = ''
							AND kaytettypvm = '0000-00-00'
							AND kaytettytilausnro = 0
							AND tuoteno = '{$tem_tuoteno}'";
				$chk_res = pupe_query($query);

				if (mysql_num_rows($chk_res) > 0) {
					$virhe[$i] = t("Asiakkaalla panttitilillä aktiivisia tuotteita, panttitiliä ei voi kytkeä pois päältä").".";
					$errori = 1;
				}
			}
		}

		if ((mysql_field_name($result, $i) == "projekti" or mysql_field_name($result, $i) == "kohde" or mysql_field_name($result, $i) == "kustp") and (int) trim($t[$i]) != 0) {

			if (mysql_field_name($result, $i) == "projekti") $kptyyppi = "P";
			if (mysql_field_name($result, $i) == "kohde") 	 $kptyyppi = "O";
			if (mysql_field_name($result, $i) == "kustp") 	 $kptyyppi = "K";

			$tpque = "	SELECT tunnus
						from kustannuspaikka
						where yhtio = '$kukarow[yhtio]'
						and tyyppi = '$kptyyppi'
						and kaytossa != 'E'
						and tunnus = '$t[$i]'";
			$tpres = pupe_query($tpque);

			if (mysql_num_rows($tpres) == 0) {
				$virhe[$i] .= t("Kustannuspaikkaa ei löydy")."!";
			}
		}

		if ((mysql_field_name($result, $i) == "osasto" or mysql_field_name($result, $i) == "try" or mysql_field_name($result, $i) == "tuotemerkki" or mysql_field_name($result, $i) == "malli" or mysql_field_name($result, $i) == "mallitarkenne") and trim($t[$i]) != '') {

			$tpque = "SELECT tunnus FROM avainsana WHERE yhtio = '$kukarow[yhtio]' and LAJI = '".mysql_field_name($result, $i)."'";
			$tpres = pupe_query($tpque);

			if (mysql_num_rows($tpres) > 0) {
				$tpque = "SELECT tunnus FROM avainsana WHERE yhtio = '$kukarow[yhtio]' and LAJI = '".mysql_field_name($result, $i)."' and SELITE = '$t[$i]'";
				$tpres = pupe_query($tpque);

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
			mysql_field_name($result, $i) == "tilino_osto_marginaali") and $t[$i] != "") {

			$query = "	SELECT yhtio
						FROM tili
						WHERE yhtio = '$kukarow[yhtio]'
						and tilino = '$t[$i]'";
			$sresult = pupe_query($query);

			if (mysql_num_rows($sresult) != 1) {
				$virhe[$i] = t("Tiliä ei löydy");
				$errori = 1;
			}
		}

		if (mysql_field_name($result, $i) == "tilino" and trim($t[$i]) == "" and $laji == "V") {
			$virhe[$i] = t("Viranomaistuotteella on oltava tili")."!";
		}

		if ((mysql_field_name($result, $i) == "tullinimike1") and ($t[$i] != 0)) {
			$query = "	SELECT cn
						FROM tullinimike
						WHERE cn = '$t[$i]' and kieli = '$yhtiorow[kieli]'";
			$sresult = pupe_query($query);

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
			if (isset($t[$i])) {
				// Tyyppiä ei saa vaihtaa, jos tuotteella on yksikin tapahtuma
			    if (trim(strtoupper($trow["tuotetyyppi"])) != trim(strtoupper($t[$i]))) {
					$query = "	SELECT tunnus
								FROM tapahtuma
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tuoteno = '$tem_tuoteno'
								LIMIT 1";
					$tapahtuma_chk_res = pupe_query($query);

					if (trim(strtoupper($trow["tuotetyyppi"])) == "" or trim(strtoupper($trow["tuotetyyppi"])) == "N") {
						if (mysql_num_rows($tapahtuma_chk_res) == 1 and ($t[$i] == 'R' or $t[$i] == 'K'or $t[$i] == 'M')) {
							$virhe[$i] = t("Tuotetyyppiä ei voida muuttaa, koska tuotteella on tapahtumia")."!";
						}
					}
					else {
						if (mysql_num_rows($tapahtuma_chk_res) == 1) {
							$virhe[$i] = t("Tuotetyyppiä ei voida muuttaa, koska tuotteella on tapahtumia")."!";
						}
					}
				}

				$tem_tuotetyyppi = $t[$i];
			}
		}

		if (mysql_field_name($result, $i) == "sarjanumeroseuranta") {

			$query = "	SELECT count(*) saldo
						FROM tuotepaikat
						WHERE yhtio = '$kukarow[yhtio]'
						and tuoteno = '$tem_tuoteno'
						and saldo  != 0";
			$vresult = pupe_query($query);
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
			if (isset($t[$i])) {
				$query = "	SELECT *
							FROM tuotepaikat
							WHERE tuoteno = '$tem_tuoteno' and yhtio = '$kukarow[yhtio]'";
				$sresult = pupe_query($query);

				if ($t[$i] != '') {
					if (mysql_num_rows($sresult) != 0) {
						$virhe[$i] = t("Tuotteella on varastopaikkoja. Poista ne ensin!");
					}
				}

				if ($poistolukko != "" and isset($trow['ei_saldoa']) and $t[$i] != $trow["ei_saldoa"]) {
					$virhe[$i] = t("Tuotteella on tapahtumia. Saldottomuutta ei voi vaihtaa")."!";
				}

				$tem_eisaldoa = $t[$i];
			}
		}

		// Viranomaistuotteita ei tsekata...
		if (mysql_field_name($result, $i) == "vienti" and $laji != "V") {

			// Katsotaan, ettei ole sekaisin plussia ja miinuksia
			if ($t[$i] != '' and strpos($t[$i], "+") !== FALSE and strpos($t[$i], "-") !== FALSE) {
				$virhe[$i] = t("Laita samanaikaisesti vain + tai - maakoodeja!")."<br>";
			}
			elseif ($t[$i] != '') {

				// Käyttäjät voi erotella myös maat pilkuilla, joten vaihdetaan ne tässä spaceiksi, trimmataan ja uppercasetaan
				$t[$i] = str_replace(",", " ", strtoupper(trim($t[$i])));

				// Poistetaan ylimääräiset spacet (kaksi tai enemmän spaceja yhdeksi spaceksi)
				$t[$i] = preg_replace("/ {2,}/", " ", $t[$i]);

				// Käydään läpi syötetyt arvot, splitataan välilyönnistä
				$apu_vientikiellot = explode(" ", $t[$i]);

				foreach ($apu_vientikiellot as $apu_vientikielto) {

					// varmistetaan, että löytyy plus tai miinusmerkki
					if (strpos($apu_vientikielto, "+") === FALSE and strpos($apu_vientikielto, "-") === FALSE) {
						$virhe[$i] .= t("Maakoodi virheellisessä muodossa").": '$apu_vientikielto'<br>";
					}
					// varmistetaan, että plus tai miinus on eka merkki
					elseif (strpos($apu_vientikielto, "+") != 0 or strpos($apu_vientikielto, "-") != 0) {
						$virhe[$i] .= t("Maakoodi virheellisessä muodossa").": '$apu_vientikielto'<br>";
					}
					else {
						// Otetaan eka +/- pois
						$apu_vientikielto = substr($apu_vientikielto, 1);

						// Katsotaan löytyykö annettu maakoodi
						$query = "	SELECT distinct koodi
									FROM maat
									WHERE koodi = '$apu_vientikielto'";
						$vientikielto_result = pupe_query($query);

						if (mysql_num_rows($vientikielto_result) == 0) {
							$virhe[$i] .= "Virheellinen maakoodi $apu_vientikielto!<br>";
						}
					}
				}
			}

			if ($virhe[$i] != "") {
				$virhe[$i] .= t("Syötä tieto muodossa +MAAKOODI tai -MAAKOODI ja erottele maat välilyönnillä.");
			}
		}

		if (mysql_field_name($result, $i) == "eankoodi") {
			if (trim($t[$i]) != 0 and trim($t[$i] != '')) {
				$query  = "	SELECT eankoodi
							FROM tuote
							WHERE yhtio = '$kukarow[yhtio]'
							and eankoodi = '".trim($t[$i])."'
							and tuoteno != '$tem_tuoteno'";
				$chkressiresult = pupe_query($query);

				if (mysql_num_rows($chkressiresult) > 0) {
					$virhe[$i] = t("EAN-koodi on jo perustettu järjestelmään");
				}

				if ($yhtiorow["tarkista_eankoodi"] == "K" and tarkista_ean13(trim($t[$i])) === FALSE) {
					$virhe[$i] = t("EAN-koodi on virheellinen");
				}

			}
		}

		if (mysql_field_name($result, $i) == 'status') {
			$tem_status = $t[$i];
		}

		//tehdään nää jutut tässä koska tämä on aina viimeinen sarake
		if (mysql_field_name($result, $i) == "tunnus") {

			if ($tem_kehahin != "" and $tem_kehahin != $trow["kehahin"] and $tem_eisaldoa == "") {
				// Pitää aina setata myös "tämän" kentän virhe, muuten luedata ei toimi
				$virhe[$i] = $virhe[$tem_kehahin_ind] = t("Saldollisen tuotteen hankintahintaa ei voi muuttaa")."!";
			}

			if ($tem_kehahin != "" and $tem_kehahin != $trow["kehahin"] and $tem_eisaldoa == "" and count($virhe) == 0 and $yhtiorow["palvelutuotteiden_kehahinnat"] == "K") {

				$tem_kehahin = sprintf("%.6f", $tem_kehahin);

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
							selite 		= '".t("Tuotteen kehahinta vaihdettiin")." $trow[kehahin] --> $tem_kehahin',
							laatija 	= '$kukarow[kuka]',
							laadittu 	= now()";
				$chkressires = pupe_query($query);
			}

			if (count($virhe) == 0 and $tem_eisaldoa == "" and $yhtiorow["tuotteen_oletuspaikka"] != "") {

				$query = "	SELECT *
							FROM tuotepaikat
							WHERE tuoteno = '$tem_tuoteno'
							and yhtio = '$kukarow[yhtio]'";
				$sresult = pupe_query($query);

				if (mysql_num_rows($sresult) == 0) {

					$_counter = 0;

					foreach (explode(",", $yhtiorow["tuotteen_oletuspaikka"]) as $_tuotepaikka) {
						list($hyllyalue, $hyllynro, $hyllyvali, $hyllytaso) = explode("-", trim($_tuotepaikka));

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

						$oletus_x = $_counter == 0 ? 'X' : '';

						$tuotepaikka_query = "	INSERT INTO tuotepaikat SET
						 						yhtio			= '{$kukarow['yhtio']}',
									 			tuoteno     	= '{$tem_tuoteno}',
									 			oletus      	= '{$oletus_x}',
							   		 			saldoaika   	= now(),
												hyllyalue   	= '{$hyllyalue}',
												hyllynro    	= '{$hyllynro}',
												hyllyvali   	= '{$hyllyvali}',
												hyllytaso   	= '{$hyllytaso}',
												luontiaika		= now(),
												laatija			= '{$kukarow['kuka']}',
												muutospvm		= now(),
												muuttaja		= '{$kukarow['kuka']}'";
						$tuotepaikka_result = pupe_query($tuotepaikka_query);

						$tapahtumaquery = "	INSERT into tapahtuma set
											yhtio 		= '{$kukarow['yhtio']}',
											tuoteno 	= '{$tem_tuoteno}',
											kpl 		= 0,
											kplhinta	= 0,
											hinta 		= 0,
											laji 		= 'uusipaikka',
											hyllyalue   = '{$hyllyalue}',
											hyllynro    = '{$hyllynro}',
											hyllyvali   = '{$hyllyvali}',
											hyllytaso   = '{$hyllytaso}',
											selite 		= '".t("Tuoteylläpidossa lisättiin tuotepaikka")." $hyllyalue $hyllynro $hyllyvali $hyllytaso',
											laatija 	= '$kukarow[kuka]',
											laadittu 	= now()";
						$tapahtumaresult = pupe_query($tapahtumaquery);

						$_counter++;
					}

					if ($kukarow['yhtio'] == 'artr') {

						$hyllyalue		= "";
						$hyllynro 		= $hyllyvali = $hyllytaso = 0;
						$tuotepaikka_id = 0;

						if ($tem_status == 'A') {
							$hyllyalue	= "UUSI";

							$tuotepaikka_query = "	INSERT INTO tuotepaikat SET
							 						yhtio			= '{$kukarow['yhtio']}',
										 			tuoteno     	= '{$tem_tuoteno}',
										 			oletus      	= '',
								   		 			saldoaika   	= now(),
													hyllyalue   	= 'UUSI',
													hyllynro    	= '0',
													hyllyvali   	= '0',
													hyllytaso   	= '0',
													luontiaika		= now(),
													laatija			= '{$kukarow['kuka']}',
													muutospvm		= now(),
													muuttaja		= '{$kukarow['kuka']}'
													ON DUPLICATE KEY UPDATE muuttaja = '{$kukarow['kuka']}', muutospvm = now()";
							$tuotepaikka_result = pupe_query($tuotepaikka_query);
							$tuotepaikka_id = mysql_insert_id();
						}
						elseif ($tem_status == 'T') {
							$hyllyalue	= "T";
							$tuotepaikka_query = "	INSERT INTO tuotepaikat SET
							 						yhtio			= '{$kukarow['yhtio']}',
										 			tuoteno     	= '{$tem_tuoteno}',
										 			oletus      	= '',
								   		 			saldoaika   	= now(),
													hyllyalue   	= 'T',
													hyllynro    	= '0',
													hyllyvali   	= '0',
													hyllytaso   	= '0',
													luontiaika		= now(),
													laatija			= '{$kukarow['kuka']}',
													muutospvm		= now(),
													muuttaja		= '{$kukarow['kuka']}'
													ON DUPLICATE KEY UPDATE muuttaja = '{$kukarow['kuka']}', muutospvm = now()";
							$tuotepaikka_result = pupe_query($tuotepaikka_query);
							$tuotepaikka_id = mysql_insert_id();
						}

						// tämä tulee vain jos ollaan insertöity, jos valutaan "ON DUPLICATE KEY UPDATE" puolelle niin mysql_insert_id() palauttaa nollan
						if ($tuotepaikka_id > 0) {
							$tapahtumaquery = "	INSERT into tapahtuma set
												yhtio 		= '{$kukarow['yhtio']}',
												tuoteno 	= '{$tem_tuoteno}',
												kpl 		= 0,
												kplhinta	= 0,
												hinta 		= 0,
												laji 		= 'uusipaikka',
												hyllyalue   = '{$hyllyalue}',
												hyllynro    = '{$hyllynro}',
												hyllyvali   = '{$hyllyvali}',
												hyllytaso   = '{$hyllytaso}',
												selite 		= '".t("Tuoteylläpidossa lisättiin tuotepaikka")." $hyllyalue $hyllynro $hyllyvali $hyllytaso',
												laatija 	= '$kukarow[kuka]',
												laadittu 	= now()";
							$tapahtumaresult = pupe_query($tapahtumaquery);
						}
					}
				}
			}
		}
	}
}

?>