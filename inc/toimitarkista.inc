<?php

if(!function_exists("toimitarkista")) {
	function toimitarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {
		global $kukarow, $yhtiorow, $alias_set, $ulosarray, $toimtyyppi;

		static $tmp_maakoodi, $ytunnus, $hetu, $tmp_ytunnus, $tmp_valkoodi, $tmp_iban, $tmp_swift, $tmp_tilinumeropos, $tmp_nimi, $tmp_ultilno_maa;

		if ((mysql_field_name($result, $i) == "nimi") or
			(mysql_field_name($result, $i) == "maa") or
			(mysql_field_name($result, $i) == "ytunnus") or
			(mysql_field_name($result, $i) == "oletus_hyvak1") or
			(mysql_field_name($result, $i) == "oletus_valkoodi")) {


			if (mysql_field_name($result, $i) == "oletus_hyvak1" and $alias_set == "KAYTTAJA") {
				$t[$i] = $tmp_nimi;
			}

			if (trim($t[$i]) == '') {
				$virhe[$i] = t("Tieto puuttuu");
			}

			if (mysql_field_name($result, $i) == "maa") {
				$t[$i] = strtoupper($t[$i]);
				$tmp_maakoodi = $t[$i];
			}
		}

		if (mysql_field_name($result, $i) == "nimi") {
			$tmp_nimi = $t[$i];
		}

		if (mysql_field_name($result, $i) == "ultilno_maa") {
			$tmp_ultilno_maa = $t[$i];
		}

		if (mysql_field_name($result, $i) == "tilino" and $t[$i] != "") {
			$query = "	SELECT yhtio
						FROM tili
						WHERE yhtio = '$kukarow[yhtio]'
						and tilino = '$t[$i]'";
			$sresult = pupe_query($query);

			if (mysql_num_rows($sresult) != 1) {
				$virhe[$i] = t("Tili‰ ei lˆydy");
				$errori = 1;
			}
		}

		if (mysql_field_name($result, $i) == "tilinumero") {
			$t[$i] = trim($t[$i]);

			if ($tmp_maakoodi == strtoupper($yhtiorow['maa'])) {
				if ((int) trim($t[$i]) != 0) {

					$pankkitili = $t[$i];

					if ($tmp_maakoodi == 'FI') {

						require 'pankkitilinoikeellisuus.php';

						if ($pankkitili == '') {
							$virhe[$i] = t("Pankkitili %s on virheellinen", "", $t[$i]);
						}
						else {
							$t[$i]=$pankkitili;
							$vastaus = luoiban($t[$i]);
							$tmp_iban = $vastaus['iban'];
							$tmp_swift = $vastaus['swift'];
						}
					}
				}
				else {
					$tmp_tilinumeropos = $i;
				}
			}
		}

		if (mysql_field_name($result, $i) == "ultilno") {

			if ($tmp_maakoodi != strtoupper($yhtiorow['maa']) and $t[$i] == '') {
				$virhe[$i] = t("Tieto puuttuu");
			}

			if ($t[$i] == '') $t[$i] = $tmp_iban;

			// Vaaditaan isot kirjaimet
			$t[$i] = strtoupper($t[$i]);

			if ($tmp_ultilno_maa != "") $ibanmaa = $tmp_ultilno_maa;
			else $ibanmaa = $tmp_maakoodi;

			// Jos SEPA-maa, tarkistetaan IBAN
			if (tarkista_sepa($ibanmaa) and tarkista_iban($t[$i]) != $t[$i]) {
				$virhe[$i] = t("Virheellinen IBAN!")." $t[$i]";
				$errori = 1;
			}
			elseif (tarkista_bban($t[$i]) === FALSE) {
				$virhe[$i] = t("Virheellinen BBAN!")." $t[$i] ".t("Tilinumerossa saa olla vain kirjaimia A-Z ja/tai numeroita 0-9");
				$errori = 1;
			}

			// generoidaan ibanista vanhanaikainen kotimainen tilinumero
			if (!isset($errori) and isset($tmp_tilinumeropos) and $tmp_maakoodi == strtoupper($yhtiorow['maa'])) {
				$t[$tmp_tilinumeropos] = substr($t[$i], 4);
				unset($virhe[$tmp_tilinumeropos]);
			}
			elseif (isset($tmp_tilinumeropos)) {
				$virhe[$tmp_tilinumeropos] = t("Tieto puuttuu");
				$errori = 1;
			}
		}

		if (mysql_field_name($result, $i) == "swift") {

			if ($t[$i] == '') $t[$i] = $tmp_swift;

			// Vaaditaan isot kirjaimet
			$t[$i] = trim(strtoupper($t[$i]));

			if ($tmp_ultilno_maa != "") $ibanmaa = $tmp_ultilno_maa;
			else $ibanmaa = $tmp_maakoodi;

			// Jos SEPA-maa, tarkistetaan BIC
			if (tarkista_sepa($ibanmaa) and tarkista_bic($t[$i]) === FALSE) {
				$virhe[$i] = t("Virheellinen BIC!")." $t[$i]";
				$errori = 1;
			}
		}

		if ((mysql_field_name($result, $i) == "ytunnus") and trim($t[$i])!='') {
			$ytunnus = trim($t[$i]);

			if ($tmp_maakoodi == 'FI' and ($yhtiorow["ytunnus_tarkistukset"] == "" or $yhtiorow["ytunnus_tarkistukset"] == "T")) {


				if (strlen($ytunnus) == 11) {
					$hetu = $ytunnus;

					// katotaan oliko kyseess‰ hetu
					require ("tarkistahetu.inc");

					// ei ollu oikee hetukaan
					if ($hetuok == 1) {
						$virhe[$i] = t("Virheellinen henkilˆtunnus!");
						$errori = 1;
					}

				}
				else {
					require ("tarkistaytunnus.inc");

					if ($ok == 0) {
						$virhe[$i] = t("Virheellinen ytunnus!")." $t[$i] -> $ytunnus. ".t("Voit tarkistaa ytunnuksen")." <a target='uusiytj' href='http://www.ytj.fi/yrit_sel2.asp?kielikoodi=1'>".t("t‰‰lt‰")."</a>.";
					}
					else {
						$t[$i] = $ytunnus;
					}
				}
			}
			else { // ulkomaalainen saa olla mit‰ se haluaa
				$t[$i] = $ytunnus;
			}

			$tmp_ytunnus = $t[$i];

			// Jos ytunnus vaihtuu niin korjataan myˆs tuotteiden toimittajat
			if (trim($t[$i]) != "" and $trow[$i] != $t[$i] and $errori != 1 and (int) $tunnus > 0) {
				$query = "	UPDATE tuotteen_toimittajat
							SET toimittaja = '$t[$i]'
							WHERE tuotteen_toimittajat.yhtio		= '$kukarow[yhtio]'
							and tuotteen_toimittajat.liitostunnus 	= $tunnus";
				$vres = pupe_query($query);
			}
		}

		if (mysql_field_name($result, $i) == "ohjeitapankille") {

			if ($tmp_maakoodi=='FI' and strlen($t[$i]) != '') {
				$virhe[$i] = t("K‰ytˆss‰ vain ulkomaisille toimittajille");
			}
			else {
				$t[$i]=trim($t[$i]);
				if (strlen($t[$i]) > 350) {
					$virhe[$i] = t("Maksimipituus on 350 merkki‰");
				}
			}
		}

		if (mysql_field_name($result, $i) == "ovttunnus") {
			if ($tmp_maakoodi=='FI') {

				if (trim($t[$i]) == '') {
					// t‰ytet‰‰n ovttunnus jos sit‰ ei ole
					$t[$i] = "0037".sprintf("%08d", preg_replace("/[^0-9]/", "", $tmp_ytunnus));
				}

				$query = "	SELECT ytunnus, tunnus
							FROM toimi
							WHERE yhtio		= '$kukarow[yhtio]'
							and ovttunnus	= '$t[$i]'
							and tyyppi 	   != 'P'";
				$sresult = pupe_query($query);

				if (mysql_num_rows($sresult)>0) {
					$srowapu = mysql_fetch_assoc($sresult);

					// ei lˆydetty itte‰mme
					if ($srowapu['tunnus'] != $tunnus) {
						$virhe[$i] = t("Samalla ytunnuksella %s on useita toimittajia! Lis‰‰ tarkenne ovt-tunnkseen.", "", $tmp_ytunnus);
					}
				}
			}
		}

		if (mysql_field_name($result, $i) == "email" and trim($t[$i]) != '') {
			// Tarkistetaan s‰hkˆpostiosoite
			$email_virhe = tarkista_email($t[$i]);

			if ($email_virhe != "") {
				$virhe[$i] = t("S‰hkˆpostin domain ei lˆydy")."! ($email_virhe)";
				$errori = 1;
			}
		}

		if (mysql_field_name($result, $i) == "oletus_valkoodi") {
			$tmp_valkoodi = $t[$i];
		}

		if (mysql_field_name($result, $i) == "tyyppi") {
			$toimtyyppi = $t[$i]; // otetaan t‰‰ talteen
		}

		if (mysql_field_name($result, $i) == "tyyppi_tieto") {

			if ($toimtyyppi == "S") {
				$query = "SELECT * from yhtio where konserni='$yhtiorow[konserni]' and yhtio='$t[$i]' and yhtio!='$kukarow[yhtio]'";
				$tyychkre = pupe_query($query);

				if (mysql_num_rows($tyychkre) == 0) {
					$virhe[$i] = "V‰‰r‰ yhtiˆtunnus: $t[$i]!";
				}
			}

			if ($toimtyyppi == "O" and trim($t[$i]) == "") {
				$virhe[$i] = "Tieto puuttuu!";
			}

			if ($toimtyyppi == "L" and $tunnus > 0 and $errori != 1) {
				// injisoidaan tuotteen_toimittajat kaikille tuotteille
				$query = "	INSERT into tuotteen_toimittajat (yhtio, tuoteno, toim_tuoteno, toimittaja, liitostunnus, valuutta, alkuperamaa, tuotekerroin, laatija, luontiaika)
							SELECT yhtio, tuoteno, tuoteno, '$tmp_ytunnus', '$tunnus', '$tmp_valkoodi', '$tmp_maakoodi', 1, '$kukarow[kuka]', now()
							FROM tuote
							WHERE tuote.yhtio='$kukarow[yhtio]' and tuote.ei_saldoa='' and tuote.status != 'P'
							ON DUPLICATE KEY UPDATE
							tuotteen_toimittajat.liitostunnus 	= $tunnus,
							tuotteen_toimittajat.toimittaja		= '$tmp_ytunnus',
							tuotteen_toimittajat.valuutta		= '$tmp_valkoodi',
							tuotteen_toimittajat.alkuperamaa	= '$tmp_maakoodi'";
				$vres = pupe_query($query);
			}

			if ($toimtyyppi == "PP" and $tunnus > 0 and $errori != 1) {
				// poistetaan tuotteen_toimittajat kaikille tuotteille
				$query = "	SELECT lasku.yhtio, group_concat(DISTINCT tilausrivi.otunnus) tilaukset
							from lasku
							join tilausrivi on tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi='O' and tilausrivi.varattu != 0
							where lasku.yhtio		= '$kukarow[yhtio]'
							and lasku.liitostunnus	= '$tunnus'
							and lasku.tila = 'O'
							group by 1";
				$tyychkre1 = pupe_query($query);

				$query = "	SELECT lasku.yhtio, group_concat(DISTINCT lasku.laskunro) tilaukset
							from lasku
							where lasku.yhtio		= '$kukarow[yhtio]'
							and lasku.liitostunnus	= '$tunnus'
							and lasku.tila 	= 'K'
							and lasku.alatila != 'X'
							and lasku.vanhatunnus = 0
							group by 1";
				$tyychkre2 = pupe_query($query);

				if (mysql_num_rows($tyychkre1) == 0 and mysql_num_rows($tyychkre2) == 0) {

					$query = "	DELETE FROM tuotteen_toimittajat
								WHERE tuotteen_toimittajat.yhtio		= '$kukarow[yhtio]'
								and tuotteen_toimittajat.liitostunnus 	= $tunnus";
					$vres = pupe_query($query);
				}
				else {
					$tyychkro1 = mysql_fetch_array($tyychkre1);
					$tyychkro2 = mysql_fetch_array($tyychkre2);

					$virhe[$i] = "Tuotteen toimittajia ei voitu poistaa!<br>Tilaukset: ($tyychkro1[tilaukset]) ovat auki.<br>Keikat: ($tyychkro2[tilaukset]) ovat auki.";
				}
			}
		}

		if (mysql_field_name($result, $i) == "edi_kuvaus") {
			$tmp_asn = $t[$i];
	}

		if (mysql_field_name($result, $i) == "asn_sanomat") {
			$asn_tyyppi = trim($t[$i]);
			if ($tmp_asn == "editilaus_out_teccom.inc" and $asn_tyyppi == "") {
				$virhe[$i] = t("Toimittajalle on laitettu TecCom-editilaus. Mit‰ sanomia toimittaja l‰hett‰‰")." ?";
}

		}
	}
}


?>
