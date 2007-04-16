<?php

	//Tarvitsemme
	//$laskurow jossa on laskun tiedot
	//$kutsuja jossa on skripti josta kutsutaan

	$kerattavia 	= 0;
	$ei_kerattavia 	= 0;
	$tilaus_valmis_message = "";

	$query = "	SELECT count(*) riveja,
				sum(if(var = 'P' and tilkpl > 0, 1, 0)) puutteet,
				sum(if(var in ('J','S') and jt > 0, 1, 0)) jteet,
				sum(if(varattu < 0, 1, 0)) hyvitykset,
				sum(if(ei_saldoa = '' and varattu > 0, 1, 0)) normaalit_kerataan,
				sum(if(ei_saldoa != '' and varattu > 0, 1, 0)) saldottomat
				FROM tilausrivi
				LEFT JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
				WHERE tilausrivi.yhtio	= '$kukarow[yhtio]'
				and tilausrivi.otunnus 	= '$laskurow[tunnus]'
				and tilausrivi.tyyppi	= 'L'
				and tilausrivi.tuoteno != '$yhtiorow[rahti_tuotenumero]'";
	$tresult = mysql_query($query) or pupe_error($query);
	$rivirow = mysql_fetch_array($tresult);

	//Ker‰tt‰v‰t ja ei ker‰tt‰v‰ rivit
	$kerattavia = $rivirow["normaalit_kerataan"];
	$ei_kerattavia = $rivirow["saldottomat"] + $rivirow["hyvitykset"];

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
		//Jos yhtio haluaa ker‰t‰ Puuterivit
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
			//Meill‰ on pelk‰st‰‰n puutteita ker‰tt‰v‰n‰ ja niit‰ ei t‰ss‰ tapauksessa ker‰t‰
			$ei_kerattavia += $rivirow["puutteet"];
			$kerattavia	   -= $rivirow["puutteet"];
		}
		elseif ($rivirow["jteet"] == $kerattavia and ($yhtiorow["puute_jt_kerataanko"] == "J" or $yhtiorow["puute_jt_kerataanko"] == "Q") and ($yhtiorow["kerataanko_jos_vain_puute_jt"] == "" or $yhtiorow["kerataanko_jos_vain_puute_jt"] == "P")) {
			//Meill‰ on pelk‰st‰‰n JT-rivej‰ ker‰tt‰v‰n‰ ja niit‰ ei t‰ss‰ tapauksessa ker‰t‰
			$ei_kerattavia += $rivirow["jteet"];
			$kerattavia	   -= $rivirow["jteet"];
		}
		elseif ($rivirow["jteet"] + $rivirow["puutteet"] == $kerattavia and $yhtiorow["puute_jt_kerataanko"] == "Q" and $yhtiorow["kerataanko_jos_vain_puute_jt"] == "") {
			//Meill‰ on pelk‰st‰‰n JT-rivej‰ + puutteita ker‰tt‰v‰n‰ ja niit‰ ei t‰ss‰ tapauksessa ker‰t‰
			$ei_kerattavia += $rivirow["jteet"] + $rivirow["puutteet"];
			$kerattavia	   -= $rivirow["jteet"] + $rivirow["puutteet"];
		}
	}


	// debug
	/*
	echo "puute_jt_kerataanko: $yhtiorow[puute_jt_kerataanko]<br>";
	echo "kerataanko_jos_vain_puute_jt: $yhtiorow[kerataanko_jos_vain_puute_jt]<br>";
	echo "R: $rivirow[riveja]<br>";
	echo "P: $rivirow[puutteet]<br>";
	echo "J: $rivirow[jteet]<br>";
	echo "H: $rivirow[hyvitykset]<br>";
	echo "N: $rivirow[normaalit_kerataan]<br>";
	echo "S: $rivirow[saldottomat]<br>";
	echo "KER: $kerattavia<br>";
	echo "EIKER: $ei_kerattavia<br>";
	*/

	//Tutkitaan mit‰ tehd‰‰n tilaukselle
	if ($rivirow["riveja"] == 0 or ($kerattavia == 0 and $ei_kerattavia == 0 and $kutsuja == 'keraa.php')) {
		// Tilauksella ei ole mit‰‰n laskutettavaa eik‰ ker‰tt‰v‰‰
		$query  = "	UPDATE tilausrivi
					SET tyyppi='D'
					WHERE otunnus = '$laskurow[tunnus]' and yhtio = '$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		$komm = "(" . $kukarow['kuka'] . "@" . date('Y-m-d') .") ".t("Mit‰tˆi ohjelmassa tilaus-valmis-valitsetila.php (1)")."<br>";

		$query  = "	UPDATE lasku
					SET tila='D', alatila='$laskurow[tila]', comments = '$komm'
					where tunnus='$laskurow[tunnus]'
					and yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		$tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilaus %s mit‰tˆitiin koska tilauksella ei ollut yht‰‰n ker‰tt‰v‰‰/laskutettavaa rivi‰."), $laskurow["tunnus"])."</font><br><br>\n";

	}
	elseif ($rivirow["jteet"] > 0 and $laskurow["osatoimitus"] != '') {
		// Jos tilauksella oli yksikin jt-rivi ja osatoimitus on kielletty
		$query  = "	UPDATE lasku
					SET tila='N', alatila='U'
					where tunnus='$laskurow[tunnus]'
					and yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		$tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilauksella %s oli JT-rivej‰ ja osatoimitusta ei tehd‰, eli se j‰tettiin odottamaan JT-tuotteita."), $laskurow["tunnus"])."</font><br><br>\n";
	}
	elseif ($kerattavia == 0) {
		// Tilauskella on pelk‰st‰‰n puuterivej‰ joita ei ker‰t‰
		// merkataan otsikko mit‰tˆidyksi, mutta j‰tet‰‰n PUUTE rivit analyysej‰ varten...
		if ($rivirow["riveja"] == $rivirow["puutteet"]) {
			// Jos tilauksella oli vain puuterivej‰ niin poistetaan rahtikulurivi jos sellanen on
			$query  = "	DELETE FROM tilausrivi
						WHERE otunnus = '$laskurow[tunnus]' and yhtio = '$kukarow[yhtio]' and tuoteno='$yhtiorow[rahti_tuotenumero]'";
			$result = mysql_query($query) or pupe_error($query);

			$komm = "(" . $kukarow['kuka'] . "@" . date('Y-m-d') .") ".t("Mit‰tˆi ohjelmassa tilaus-valmis-valitsetila.php (2)")."<br>";

			$query  = "	UPDATE lasku
						SET tila='D', alatila='$laskurow[tila]', comments = '$komm'
						where tunnus='$laskurow[tunnus]'
						and yhtio='$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);

			$tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilauksella %s  oli pelk‰st‰‰n Puuterivej‰. Otsikko mit‰tˆitiin."), $laskurow["tunnus"])."</font><br><br>\n";
		}
		elseif ($rivirow["riveja"] == $rivirow["jteet"]) {
			// Jos tilauksella oli vain jt-rivej‰ niin poistetaan rahtikulurivi jos sellanen on
			$query  = "	DELETE FROM tilausrivi
						WHERE otunnus = '$laskurow[tunnus]' and yhtio = '$kukarow[yhtio]' and tuoteno='$yhtiorow[rahti_tuotenumero]'";
			$result = mysql_query($query) or pupe_error($query);

			$query  = "	UPDATE lasku
						SET tila='N', alatila='T'
						where tunnus='$laskurow[tunnus]'
						and yhtio='$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);

			$tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilauksella %s oli pelk‰st‰‰n JT-rivej‰ ja se j‰tettiin odottamaan JT-tuotteita."), $laskurow["tunnus"])."</font><br><br>\n";
		}
		elseif ($rivirow["riveja"] == $rivirow["jteet"] + $rivirow["puutteet"]) {
			// Tilauskella on pelk‰st‰‰n jt-rivej‰ ja puutteita joita ei ker‰t‰
			// Jos tilauksella oli vain jt/puute-rivej‰ niin poistetaan rahtikulurivi jos sellanen on
			$query  = "	DELETE FROM tilausrivi
						WHERE otunnus = '$laskurow[tunnus]' and yhtio = '$kukarow[yhtio]' and tuoteno='$yhtiorow[rahti_tuotenumero]'";
			$result = mysql_query($query) or pupe_error($query);

			//p‰ivitet‰‰n tilaus tilaan 'odottaa jt-rivej‰'
			$query  = "	UPDATE lasku
						SET tila='N', alatila='T'
						where tunnus='$laskurow[tunnus]'
						and yhtio='$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);

			$tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilauksella %s oli pelk‰st‰‰n JT-rivej‰ ja Puuterivej‰ ja se j‰tettiin odottamaan JT-tuotteita."), $laskurow["tunnus"])."</font><br><br>\n";
		}
		elseif ($ei_kerattavia > 0) {
			if ($kateinen == 'X' or $itsetulostus == "X" or $laskurow["vienti"] != '') {
				// jos on laskuettavia rivej‰ ja jos kyseess‰ on k‰teist‰, itsetulostus tai vienti‰
				// merkataan rivit ker‰tyiksi, mutta ei toimitetuiksi ja laskulle alatila alatila C/B
				$query  = "	UPDATE tilausrivi
							SET kerattyaika = now(),
							keratty = '$kukarow[kuka]'
							WHERE otunnus 	= '$laskurow[tunnus]'
							and yhtio 		= '$kukarow[yhtio]'
							and var not in ('P','J','S')
							and keratty 	= ''
							and toimitettu 	= ''";
				$result = mysql_query($query) or pupe_error($query);

				if($laskurow["vienti"] != '') {
					$alat = "B";
				}
				else {
					$alat = "C";
				}

				$query  = "	UPDATE lasku set tila='L', alatila='$alat', lahetepvm=now()
							where tunnus = '$laskurow[tunnus]'
							and yhtio	 = '$kukarow[yhtio]'";
				$result = mysql_query($query) or pupe_error($query);
			}
			else {
				// Jos on laskuettavia rivej‰ ja jos kyseess‰ ei ole k‰teinen, merkataan tilaukset ker‰tyksi ja toimitetuksi ja alatila 'D'
				$query  = "	UPDATE tilausrivi
							SET kerattyaika = now(),
							keratty = '$kukarow[kuka]',
							toimitettuaika = now(),
							toimitettu = '$kukarow[kuka]'
							WHERE otunnus 	= '$laskurow[tunnus]'
							and yhtio		= '$kukarow[yhtio]'
							and var not in ('P','J','S')
							and keratty		= ''
							and toimitettu	= ''";
				$result = mysql_query($query) or pupe_error($query);

				$query  = "	UPDATE lasku
							set tila='L', alatila='D', lahetepvm=now()
							where tunnus = '$laskurow[tunnus]'
							and yhtio	 = '$kukarow[yhtio]'";
				$result = mysql_query($query) or pupe_error($query);
			}

			$tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilauksella %s ei ollut yht‰‰n ker‰tt‰v‰‰ rivi‰, se merkattiin toimitetuksi."), $laskurow["tunnus"])."</font><br><br>\n";
		}
		else {
			$tilaus_valmis_message .= "<font class='message'>".sprintf(t("VIRHE: Tilausta %s ei voitu merkata valmiiksi. Aktivoi tilaus ja korjaa virheet"), $laskurow["tunnus"])."! (1)</font><br><br>\n";
		}
	}
	elseif($kerattavia > 0 and $kutsuja != 'keraa.php') {
		// T‰ss‰ meill‰ ON jotain ker‰tt‰v‰‰

		// laitetaan l‰hete tulostusjonoon
		$query  = "	UPDATE lasku SET
					tila    = 'N',
					alatila = 'A'
					WHERE yhtio = '$kukarow[yhtio]'
					and tunnus = '$laskurow[tunnus]'
					and tila in ('N','L')
					and alatila in ('','A','F')";
		$result = mysql_query($query) or pupe_error($query);

		///* T‰h‰n menn‰‰n jos l‰hete tosiaan tulostetaan *///
		if ($yhtiorow["lahetteen_tulostustapa"] != "K") {
			$tilausnumeroita 	= $laskurow["tunnus"];
			$kerayslista 		= "K";
			
			require("tilaus-valmis-tulostus.inc");
		}
		///* T‰h‰n menn‰‰n jos l‰hete vain siirret‰‰n tulostusjonoon *///
		else {
			$tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilaus %s siirretty tulostusjonoon"), $laskurow["tunnus"])."!</font><br><br>\n";
		}
	}
	elseif($kutsuja != 'keraa.php') {
		$tilaus_valmis_message .= "<font class='message'>".sprintf(t("VIRHE: Tilausta %s ei voitu merkata valmiiksi. Aktivoi tilaus ja korjaa virheet"), $laskurow["tunnus"])."! (2)</font><br><br>\n";
	}

	if ($silent == "") {
		echo $tilaus_valmis_message;
	}

?>
