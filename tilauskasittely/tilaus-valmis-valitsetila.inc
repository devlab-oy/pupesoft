<?php

	// Tarvitsemme
	// $laskurow jossa on laskun tiedot
	// $kutsuja jossa on skripti josta kutsutaan

	$kerattavia 	= 0;
	$ei_kerattavia 	= 0;
	$tilaus_valmis_message = "";
	$voiko_toimittaa = true;

	// Pamautetaan toimitusvahvistus asiakkaalle jos menn‰‰n haaraan jossa ei toimiteta mit‰‰n
	$toimitusvahvitus_laheta = FALSE;

	// etuk‰teen maksettu lasku, pit‰‰ huomioida myˆs kpl
	if (($laskurow["tila"] == "L" or $laskurow["tila"] == "N") and $laskurow["mapvm"] != '0000-00-00' and $laskurow["mapvm"] != '') {
		$lis_plus_kpl = " + tilausrivi.kpl";
	}
	else {
		$lis_plus_kpl = "";
	}

	if ($yhtiorow["varaako_jt_saldoa"] != "") {
		$lisavarattu = " + tilausrivi.varattu $lis_plus_kpl";
	}
	else {
		$lisavarattu = "";
	}

	$query = "	SELECT count(*) riveja,
				sum(if (tilausrivi.var = 'P' and tilausrivi.tilkpl $lis_plus_kpl > 0, 1, 0)) puutteet,
				sum(if (tilausrivi.var in ('J','S') and tilausrivi.jt $lisavarattu > 0, 1, 0)) jteet,
				sum(if (tilausrivi.var not in ('P','J','S') and tilausrivi.varattu $lis_plus_kpl < 0, 1, 0)) hyvitykset,
				sum(if (tilausrivi.var not in ('P','J','S') and (('$yhtiorow[kerataanko_saldottomat]' = '' and tuote.ei_saldoa = '') or '$yhtiorow[kerataanko_saldottomat]' = 'S') and tilausrivi.varattu $lis_plus_kpl > 0, 1, 0)) normaalit_kerataan,
				sum(if (tilausrivi.var not in ('P','J','S') and '$yhtiorow[kerataanko_saldottomat]' = '' and tuote.ei_saldoa != '' and tilausrivi.varattu $lis_plus_kpl > 0, 1, 0)) saldottomat,
				sum(if (tilausrivi.toimitettu != '', 1, 0)) toimitetut
				FROM tilausrivi
				LEFT JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
				WHERE tilausrivi.yhtio	= '$kukarow[yhtio]'
				and tilausrivi.otunnus 	= '$laskurow[tunnus]'
				and tilausrivi.tyyppi	= 'L'
				and tilausrivi.tuoteno != '$yhtiorow[rahti_tuotenumero]'";
	$tresult = pupe_query($query);
	$rivirow = mysql_fetch_array($tresult);

	// Ker‰tt‰v‰t ja ei ker‰tt‰v‰ rivit
	$kerattavia = $rivirow["normaalit_kerataan"];
	$ei_kerattavia = $rivirow["saldottomat"] + $rivirow["hyvitykset"];

	// Toimitettuja rivej‰ ei ker‰t‰ ikin‰
	$ei_kerattavia += $rivirow["toimitetut"];
	$kerattavia -= $rivirow["toimitetut"];

	/*
	puute_jt_kerataanko
	''  Puuterivej‰ ja JT-rivej‰ EI ker‰t‰
	'P' Puuterivit ker‰t‰‰n
	'J' JT-rivit ker‰t‰‰n
	'Q' Puuterivit ja JT-rivit ker‰t‰‰n

	kerataanko_jos_vain_puute_jt
	''  Tilausta EI ker‰t‰ jos listalla on vain Puuterivej‰ JA/TAI JT-rivej‰
	'P' Tilaus ker‰t‰‰n jos listalla on vain Puuterivej‰
	'J' Tilaus ker‰t‰‰n jos listalla on vain JT-rivej‰
	'Q' Tilaus ker‰t‰‰n jos listalla on vain Puuterivej‰ JA/TAI JT-rivej‰
	*/

	// ei lasketa ker‰tt‰vi‰/eiker‰tt‰vi‰ m‰‰ri‰ jos ollaan ker‰‰.phpssa, koska silloin kaikki on varmasti ker‰tt‰vi‰ jos ne siell‰ n‰ky
	if (($yhtiorow["puute_jt_kerataanko"] == "P" or $yhtiorow["puute_jt_kerataanko"] == "Q") and $kutsuja != 'keraa.php') {
		// Jos yhtio haluaa ker‰t‰ Puuterivit
		$kerattavia += $rivirow["puutteet"];
	}
	else {
		$ei_kerattavia += $rivirow["puutteet"];
	}

	if (($yhtiorow["puute_jt_kerataanko"] == "J" or $yhtiorow["puute_jt_kerataanko"] == "Q") and $kutsuja != 'keraa.php') {
		// Jos yhtiˆ haluaa ker‰t‰ JT-rivit
		$kerattavia += $rivirow["jteet"];
	}
	else {
		$ei_kerattavia += $rivirow["jteet"];
	}

	// jos yhtiˆ haluaa ker‰t‰ puuterivej‰ niin katotaan onko meill‰ pelk‰st‰‰n tilauksella jt/puuterivej‰
	if ($yhtiorow["puute_jt_kerataanko"] != "" and $kutsuja != 'keraa.php') {
		if ($rivirow["puutteet"] == $kerattavia and ($yhtiorow["puute_jt_kerataanko"] == "P" or $yhtiorow["puute_jt_kerataanko"] == "Q") and ($yhtiorow["kerataanko_jos_vain_puute_jt"] == "" or $yhtiorow["kerataanko_jos_vain_puute_jt"] == "J")) {
			// Meill‰ on pelk‰st‰‰n puutteita ker‰tt‰v‰n‰ ja niit‰ ei t‰ss‰ tapauksessa ker‰t‰
			$ei_kerattavia += $rivirow["puutteet"];
			$kerattavia	   -= $rivirow["puutteet"];
		}
		elseif ($rivirow["jteet"] == $kerattavia and ($yhtiorow["puute_jt_kerataanko"] == "J" or $yhtiorow["puute_jt_kerataanko"] == "Q") and ($yhtiorow["kerataanko_jos_vain_puute_jt"] == "" or $yhtiorow["kerataanko_jos_vain_puute_jt"] == "P")) {
			// Meill‰ on pelk‰st‰‰n JT-rivej‰ ker‰tt‰v‰n‰ ja niit‰ ei t‰ss‰ tapauksessa ker‰t‰
			$ei_kerattavia += $rivirow["jteet"];
			$kerattavia	   -= $rivirow["jteet"];
		}
		elseif ($rivirow["jteet"] + $rivirow["puutteet"] == $kerattavia and $yhtiorow["puute_jt_kerataanko"] == "Q" and $yhtiorow["kerataanko_jos_vain_puute_jt"] == "") {
			// Meill‰ on pelk‰st‰‰n JT-rivej‰ + puutteita ker‰tt‰v‰n‰ ja niit‰ ei t‰ss‰ tapauksessa ker‰t‰
			$ei_kerattavia += $rivirow["jteet"] + $rivirow["puutteet"];
			$kerattavia	   -= $rivirow["jteet"] + $rivirow["puutteet"];
		}
	}

	if ($laskurow['osatoimitus'] == 'e' and $laskurow['tila'] == 'N') {
		$query = "	SELECT tunnus,
					tuoteno,
					(varattu + jt) as kpl
					FROM tilausrivi
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND otunnus = '{$laskurow['tunnus']}'";
		$tilausrivi_result = pupe_query($query);
		while ($tilausrivi_row = mysql_fetch_assoc($tilausrivi_result)) {
			//haetaan kaikki tilausrivit t‰ll‰ tuotenumerolla, joita ei ole viel‰ ker‰tty tai toimitettu ja katsotaan riitt‰‰kˆ t‰ll‰ hetkell‰ k‰sittelyss‰ olevalle riville saldoa.
			$query = "	SELECT tunnus,
						tuoteno,
						tilkpl,
						(varattu + jt) as kpl
						FROM tilausrivi
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tuoteno = '{$tilausrivi_row['tuoteno']}'
						AND tyyppi = 'L'
						AND kerattyaika = '0000-00-00 00:00:00'
						AND toimitettuaika = '0000-00-00 00:00:00'
						ORDER BY laadittu ASC";
			$tuotteet_result = pupe_query($query);
			$tuote_varattu = 0;
			list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($tilausrivi_row['tuoteno'], 'JTSPEC', 0, $kukarow['yhtio'], '', '', '', '', '', '', '');
			while ($tuote = mysql_fetch_assoc($tuotteet_result)) {
				if ($tilausrivi_row['tunnus'] == $tuote['tunnus'] and ($saldo - $tuote_varattu) < $tilausrivi_row['kpl']) {
					//kun tilaus listalta tulee k‰sittelyss‰ oleva tilausrivi tarkastetaan saldo. jos saldo ei riit‰ koko tilausta ei voida toimittaa.
					$voiko_toimittaa = false;
					break;
				}
				$tuote_varattu += $tuote['tilkpl'];
			}
		}
	}

	/*
	echo "puute_jt_kerataanko: $yhtiorow[puute_jt_kerataanko]<br>";
	echo "kerataanko_jos_vain_puute_jt: $yhtiorow[kerataanko_jos_vain_puute_jt]<br>";
	echo "R: $rivirow[riveja]<br>";
	echo "P: $rivirow[puutteet]<br>";
	echo "J: $rivirow[jteet]<br>";
	echo "H: $rivirow[hyvitykset]<br>";
	echo "N: $rivirow[normaalit_kerataan]<br>";
	echo "S: $rivirow[saldottomat]<br>";
	echo "T: $rivirow[toimitetut]<br>";
	echo "KER: $kerattavia<br>";
	echo "EIKER: $ei_kerattavia<br>";
	// */

	// Tutkitaan mit‰ tehd‰‰n tilaukselle
	if ($rivirow["riveja"] == 0 or ($kerattavia == 0 and $ei_kerattavia == 0 and $kutsuja == 'keraa.php')) {
		// Tilauksella ei ole mit‰‰n laskutettavaa eik‰ ker‰tt‰v‰‰
		$query  = "	UPDATE tilausrivi
					SET tyyppi='D'
					WHERE otunnus = '$laskurow[tunnus]' and yhtio = '$kukarow[yhtio]'";
		$result = pupe_query($query);

		$komm = "(" . $kukarow['kuka'] . "@" . date('Y-m-d') .") ".t("Mit‰tˆi ohjelmassa tilaus-valmis-valitsetila.php (1)")."<br>";

		$query  = "	UPDATE lasku
					SET tila='D', alatila='$laskurow[tila]', comments = '$komm'
					where tunnus='$laskurow[tunnus]'
					and yhtio='$kukarow[yhtio]'";
		$result = pupe_query($query);

		$tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilaus %s mit‰tˆitiin koska tilauksella ei ollut yht‰‰n ker‰tt‰v‰‰/laskutettavaa rivi‰."), $laskurow["tunnus"])."</font><br><br>\n";
		$toimitusvahvitus_laheta = TRUE;
	}
	elseif (!$voiko_toimittaa) {
		$query = "	UPDATE lasku
					SET tila = 'N',
					alatila  = 'U',
					toimaika = DATE_ADD(current_date, INTERVAL 1 MONTH)
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tunnus  = '{$laskurow['tunnus']}'";
		pupe_query($query);

		$tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilauksella %s oli JT-rivej‰ joille ei riitt‰nyt saldoa ja osatoimitusta ei tehd‰, eli se j‰tettiin odottamaan JT-tuotteita."), $laskurow["tunnus"])."</font><br><br>\n";
	}
	elseif ($rivirow["jteet"] > 0 and $laskurow["osatoimitus"] == 'o') {
		// Jos tilauksella oli yksikin jt-rivi ja osatoimitus on kielletty
		$query  = "	UPDATE lasku
					SET tila='N', alatila='U'
					where tunnus='$laskurow[tunnus]'
					and yhtio='$kukarow[yhtio]'";
		$result = pupe_query($query);

		$tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilauksella %s oli JT-rivej‰ ja osatoimitusta ei tehd‰, eli se j‰tettiin odottamaan JT-tuotteita."), $laskurow["tunnus"])."</font><br><br>\n";
	}
	elseif ($rivirow["jteet"] > 0 and $laskurow["osatoimitus"] == 'e') {
		// Jos tilauksella oli yksikin jt-rivi ja osatoimitus on kielletty
		$query  = "	UPDATE lasku
					SET tila='N', alatila='A'
					where tunnus='$laskurow[tunnus]'
					and yhtio='$kukarow[yhtio]'";
		$result = pupe_query($query);

		$tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilaus %s laitettiin tulostusjonoon."), $laskurow["tunnus"])."</font><br><br>\n";
	}
	elseif ($kerattavia == 0) {
		// Tilauskella on pelk‰st‰‰n puuterivej‰ joita ei ker‰t‰
		// merkataan otsikko mit‰tˆidyksi, mutta j‰tet‰‰n PUUTE rivit analyysej‰ varten...
		if ($rivirow["riveja"] == $rivirow["puutteet"]) {
			// Jos tilauksella oli vain puuterivej‰ niin poistetaan rahtikulurivi jos sellanen on
			$query  = "	DELETE FROM tilausrivi
						WHERE otunnus = '$laskurow[tunnus]' and yhtio = '$kukarow[yhtio]' and tuoteno='$yhtiorow[rahti_tuotenumero]'";
			$result = pupe_query($query);

			$komm = "(" . $kukarow['kuka'] . "@" . date('Y-m-d') .") ".t("Mit‰tˆi ohjelmassa tilaus-valmis-valitsetila.php (2)")."<br>";

			$query  = "	UPDATE lasku
						SET tila='D', alatila='$laskurow[tila]', comments = '$komm'
						where tunnus='$laskurow[tunnus]'
						and yhtio='$kukarow[yhtio]'";
			$result = pupe_query($query);

			$tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilauksella %s  oli pelk‰st‰‰n Puuterivej‰. Otsikko mit‰tˆitiin."), $laskurow["tunnus"])."</font><br><br>\n";
			$toimitusvahvitus_laheta = TRUE;
		}
		elseif ($rivirow["riveja"] == $rivirow["jteet"]) {
			// Jos tilauksella oli vain jt-rivej‰ niin poistetaan rahtikulurivi jos sellanen on
			$query  = "	DELETE FROM tilausrivi
						WHERE otunnus = '$laskurow[tunnus]' and yhtio = '$kukarow[yhtio]' and tuoteno='$yhtiorow[rahti_tuotenumero]'";
			$result = pupe_query($query);

			$query  = "	UPDATE lasku
						SET tila='N', alatila='T'
						where tunnus='$laskurow[tunnus]'
						and yhtio='$kukarow[yhtio]'";
			$result = pupe_query($query);

			$tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilauksella %s oli pelk‰st‰‰n JT-rivej‰ ja se j‰tettiin odottamaan JT-tuotteita."), $laskurow["tunnus"])."</font><br><br>\n";
		}
		elseif ($rivirow["riveja"] == $rivirow["jteet"] + $rivirow["puutteet"]) {
			// Tilauskella on pelk‰st‰‰n jt-rivej‰ ja puutteita joita ei ker‰t‰
			// Jos tilauksella oli vain jt/puute-rivej‰ niin poistetaan rahtikulurivi jos sellanen on
			$query  = "	DELETE FROM tilausrivi
						WHERE otunnus = '$laskurow[tunnus]' and yhtio = '$kukarow[yhtio]' and tuoteno='$yhtiorow[rahti_tuotenumero]'";
			$result = pupe_query($query);

			// P‰ivitet‰‰n tilaus tilaan 'odottaa jt-rivej‰'
			$query  = "	UPDATE lasku
						SET tila='N', alatila='T'
						where tunnus='$laskurow[tunnus]'
						and yhtio='$kukarow[yhtio]'";
			$result = pupe_query($query);

			$tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilauksella %s oli pelk‰st‰‰n JT-rivej‰ ja Puuterivej‰ ja se j‰tettiin odottamaan JT-tuotteita."), $laskurow["tunnus"])."</font><br><br>\n";
		}
		elseif ($ei_kerattavia > 0) {
			if ($kateinen == 'X' or $itsetulostus == "X" or $laskurow["vienti"] != '') {
				// jos on laskuettavia rivej‰ ja jos kyseess‰ on k‰teist‰, itsetulostus tai vienti‰
				// merkataan rivit ker‰tyiksi, mutta ei toimitetuiksi ja laskulle alatila alatila C/B
				$query  = "	UPDATE tilausrivi
							SET kerattyaika = now(), keratty = '$kukarow[kuka]'
							WHERE otunnus 	= '$laskurow[tunnus]'
							and yhtio 		= '$kukarow[yhtio]'
							and var not in ('P','J','S')
							and keratty 	= ''
							and toimitettu 	= ''
							and tyyppi 	   != 'D'";
				$result = pupe_query($query);

				// Jos on vienti‰ ja vain saldottomia niin menn‰‰n suoraan toimitetuksi
				if ($laskurow["vienti"] != '' and $rivirow["riveja"] == $rivirow["saldottomat"]) {

					// Jos meill‰ on maksupositioita laskulla, tulee se siirt‰‰ alatilaan J
					if ($laskurow['jaksotettu'] != 0) {
						$alat = "J";
					}
					else {
						$alat = "D";
					}

					$tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilauksella %s ei ollut yht‰‰n ker‰tt‰v‰‰ rivi‰, se merkattiin toimitetuksi."), $laskurow["tunnus"])."</font><br><br>\n";

					$query  = "	UPDATE tilausrivi
								SET toimitettuaika = now(), toimitettu = '$kukarow[kuka]'
								WHERE otunnus 	= '$laskurow[tunnus]'
								and yhtio 		= '$kukarow[yhtio]'
								and var not in ('P','J','S')
								and keratty    != ''
								and toimitettu 	= ''
								and tyyppi 	   != 'D'";
					$result = pupe_query($query);
				}
				elseif ($laskurow["vienti"] != '') {
					$alat = "B";
					$tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilauksella %s ei ollut yht‰‰n ker‰tt‰v‰‰ rivi‰, se merkattiin toimitetuksi. Viennin lis‰tiedot syˆtett‰v‰."), $laskurow["tunnus"])."</font><br><br>\n";

					$query  = "	UPDATE tilausrivi
								SET toimitettuaika = now(), toimitettu = '$kukarow[kuka]'
								WHERE otunnus 	= '$laskurow[tunnus]'
								and yhtio 		= '$kukarow[yhtio]'
								and var not in ('P','J','S')
								and keratty    != ''
								and toimitettu 	= ''
								and tyyppi 	   != 'D'";
					$result = pupe_query($query);
				}
				else {
					$alat = "C";
					$tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilauksella %s ei ollut yht‰‰n ker‰tt‰v‰‰ rivi‰, se merkattiin ker‰tyksi."), $laskurow["tunnus"])."</font><br><br>\n";
				}

				$query  = "	UPDATE lasku SET
							tila = 'L',
							alatila = '$alat',
							lahetepvm = now()
							WHERE tunnus = '$laskurow[tunnus]'
							AND yhtio = '$kukarow[yhtio]'";
				$result = pupe_query($query);
			}
			else {
				// Jos on laskuettavia rivej‰ ja jos kyseess‰ ei ole k‰teinen, merkataan tilaukset ker‰tyksi ja toimitetuksi ja alatila 'D'
				$query  = "	UPDATE tilausrivi
							SET kerattyaika = now(), keratty = '$kukarow[kuka]'
							WHERE otunnus 	= '$laskurow[tunnus]'
							and yhtio		= '$kukarow[yhtio]'
							and var not in ('P','J','S')
							and keratty		= ''
							and toimitettu	= ''";
				$result = pupe_query($query);

				$query  = "	UPDATE tilausrivi
							SET toimitettuaika = now(), toimitettu = '$kukarow[kuka]'
							WHERE otunnus 	= '$laskurow[tunnus]'
							and yhtio		= '$kukarow[yhtio]'
							and var not in ('P','J','S')
							and keratty    != ''
							and toimitettu	= ''
							and tyyppi 	   != 'D'";
				$result = pupe_query($query);

				// Jos meill‰ on maksupositioita laskulla, tulee se siirt‰‰ alatilaan J
				if ($laskurow['jaksotettu'] != 0) {
					$tilaus_valmis_alataila = 'J';
				}
				else {
					$tilaus_valmis_alataila = 'D';
				}

				$query  = "	UPDATE lasku SET
							tila = 'L',
							alatila = '$tilaus_valmis_alataila',
							lahetepvm = now()
							WHERE tunnus = '$laskurow[tunnus]'
							AND yhtio = '$kukarow[yhtio]'";
				$result = pupe_query($query);

				$tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilauksella %s ei ollut yht‰‰n ker‰tt‰v‰‰ rivi‰, se merkattiin toimitetuksi."), $laskurow["tunnus"])."</font><br><br>\n";
			}
		}
		else {
			$tilaus_valmis_message .= "<font class='message'>".sprintf(t("VIRHE: Tilausta %s ei voitu merkata valmiiksi. Aktivoi tilaus ja korjaa virheet"), $laskurow["tunnus"])."! (1)</font><br><br>\n";
		}
	}
	elseif ($kerattavia > 0 and $kutsuja != 'keraa.php') {
		// T‰ss‰ meill‰ ON jotain ker‰tt‰v‰‰
		// laitetaan l‰hete tulostusjonoon
		$query  = "	UPDATE lasku SET
					tila    = 'N',
					alatila = 'A'
					WHERE yhtio = '$kukarow[yhtio]'
					and tunnus = '$laskurow[tunnus]'
					and tila in ('N','L')
					and alatila in ('','A','F','U', 'G')";
		$result = pupe_query($query);

		///* T‰h‰n menn‰‰n jos l‰hete tosiaan tulostetaan *///
		if ($yhtiorow["lahetteen_tulostustapa"] == "A" or $yhtiorow["lahetteen_tulostustapa"] == "B") {
			$tilausnumeroita = $laskurow["tunnus"];
			require("tilaus-valmis-tulostus.inc");

			// jos halutaan MY÷S l‰hete samantien
			if ($yhtiorow["lahetteen_tulostustapa"] == "B") {
				$params = array(
					'laskurow'					=> $laskurow,
					'sellahetetyyppi' 			=> "",
					'extranet_tilausvahvistus' 	=> "",
					'naytetaanko_rivihinta'		=> "",
					'tee'						=> "",
					'toim'						=> $toim,
					'komento' 					=> $komento,
					'lahetekpl'					=> "",
					'kieli' 					=> ""
					);

				pupesoft_tulosta_lahete($params);
			}
		}

		///* T‰h‰n menn‰‰n jos l‰hete vain siirret‰‰n tulostusjonoon *///
		elseif (mysql_affected_rows() > 0) {
			$tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilaus %s siirretty tulostusjonoon"), $laskurow["tunnus"])."!</font><br><br>\n";
		}
	}
	elseif ($kutsuja != 'keraa.php') {
		$tilaus_valmis_message .= "<font class='message'>".sprintf(t("VIRHE: Tilausta %s ei voitu merkata valmiiksi. Aktivoi tilaus ja korjaa virheet"), $laskurow["tunnus"])."! (2)</font><br><br>\n";
	}

	if ($toimitusvahvitus_laheta) {
		$query = "	SELECT toimitusvahvistus
					FROM asiakas
					WHERE yhtio	= '$kukarow[yhtio]'
					and tunnus = '$laskurow[liitostunnus]'";
		$tresult = pupe_query($query);
		$asirow = mysql_fetch_array($tresult);

		if ($asirow['toimitusvahvistus'] != '') {

			// haetaan toimitustavan tiedot
			$query    = "SELECT * from toimitustapa where yhtio = '$kukarow[yhtio]' and selite = '$laskurow[toimitustapa]'";
			$toitares = pupe_query($query);
			$toitarow = mysql_fetch_assoc($toitares);

			$otunnukset		= $laskurow["tunnus"];
			$tunnukset		= 0;
			$rahtikirjanro	= 0;
			$tulostauna		= "";

			if ($asirow["toimitusvahvistus"] == "toimitusvahvistus_desadv_una.inc") {
				$tulostauna = "KYLLA";
				$asirow["toimitusvahvistus"] = "toimitusvahvistus_desadv.inc";
			}

			if (@include("tilauskasittely/$asirow[toimitusvahvistus]")) {
				$tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilauksesta %s l‰hetettiin toimitusvahvistus."), $laskurow["tunnus"])."</font><br><br>\n";
			}
		}
	}

	if (!isset($silent) or $silent == "") {
		echo $tilaus_valmis_message;
	}

?>