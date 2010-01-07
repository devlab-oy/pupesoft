<?php

// tarvitaan $kukarow[yhtio], $kukarow[kesken], $laskurow array, $yhtiorow array
$tilaus_valmis_ulos = "";

///* Reload ja back-nappulatsekki *///
if ($kukarow["kesken"] == '' or $kukarow["kesken"] == '0') {
	echo "<font class='error'> ".t("Taisit painaa takaisin tai päivitä nappia. Näin ei saa tehdä")."! </font>";
	exit;
}

// päivitetään vanhatunnus laskulle
$query  = "UPDATE lasku set vanhatunnus=tunnus where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[tunnus]' and vanhatunnus=0";
$oldtur = mysql_query($query) or pupe_error($query);

if ($laskurow["tilaustyyppi"] == 'E') {
	//Ennakkotilaukset

	//tila on E ja alatila on A kun on ennakkotilaus
	$query  = "	UPDATE lasku
				SET tila = 'E',
				alatila = 'A'
				WHERE tunnus='$laskurow[tunnus]'
				and tila in ('N','L','E')
				and yhtio = '$kukarow[yhtio]'";
	$result = mysql_query($query) or pupe_error($query);

	$laskurow["tila"] = "E";
	$laskurow["alatila"] = "A";

	// ennakkotilauksissa tyyppi on E ja rivit ei ole ennakkopoistossa
	$query = "	UPDATE tilausrivi
				SET tyyppi	= 'E'
				WHERE yhtio	= '$kukarow[yhtio]'
				and otunnus	= '$laskurow[tunnus]'
				and tyyppi in ('L','E')";
	$result = mysql_query($query) or pupe_error($query);

	//
	// LÄHETETÄÄN TILAUSVAHVISTUS
	//
	// tulostetaan tässä, niin saadaan vahvistukseen koko tilaus, ennenkun sen splitaatan eri varastoihin

	if (strpos($laskurow['tilausvahvistus'], '4') !== FALSE) {
		$naytatvale = 4; // jos mellä on tilausvahvistuksessa nelonen, ei haluta nähdä alennuksia, näytetään tilausrivin hinta ja rivihinta
	}
	elseif (strpos($laskurow['tilausvahvistus'], '3') !== FALSE) {
		$naytatvale = 3; // jos mellä on tilausvahvistuksessa kolmonen, ei haluta nähdä hintoja, pelkästään kpl-määrät
	}
	elseif (strpos($laskurow['tilausvahvistus'], '2') !== FALSE) {
		$naytatvale = 2; // jos mellä on tilausvahvistuksessa kakkonen, ei haluta nähä aleja
	}
	else {
		$naytatvale = 1; // halutaan nähä alet
	}

	if (strpos($laskurow['tilausvahvistus'], 'E') !== FALSE) {
		require("tilausvahvistus-edi.inc");
	}

	if (strpos($laskurow['tilausvahvistus'], 'S') !== FALSE) {
		require("tilausvahvistus-email.inc");
	}

	if (strpos($laskurow['tilausvahvistus'], 'F') !== FALSE) {
		require("tilausvahvistus-fax.inc");
	}

	if (strpos($laskurow['tilausvahvistus'], 'O') !== FALSE) {
		require("tilausvahvistus-email.inc");
	}

	if (strpos($laskurow['tilausvahvistus'], 'U') !== FALSE) {
		require("tilausvahvistus-futursoft.inc");
	}

	if (strpos($laskurow['tilausvahvistus'], 'K') !== FALSE) {
		$query = "	SELECT komento
					FROM kirjoittimet
					WHERE yhtio = '$kukarow[yhtio]' and
					tunnus = '$kukarow[kirjoitin]'";
		$tilvares = mysql_query($query) or pupe_error($query);

		if ($tilvakir = mysql_fetch_assoc($tilvares)) {
			$komento["Tilausvahvistus"] = $tilvakir["komento"];

			$toim_save = $toim;
			if ($toim != "YLLAPITOSOPIMUS") $toim = "TILAUSVAHVISTUS";

			require("tulosta_tilausvahvistus_pdf.inc");
			$toim = $toim_save;
		}
	}

	if ($yhtiorow['tilausvahvistus_tallenna'] == 'K' and $tilausvahvistus_tallenna != '') {
		if (stristr(basename($tilausvahvistus_tallenna), ".pdf")) {
			$liite_tyyppi = "application/pdf";
		}
		else {
			$liite_tyyppi = "text/plain";
		}

		$file["name"] 		= basename($tilausvahvistus_tallenna);
		$file["type"] 		= $liite_tyyppi;
		$file["tmp_name"] 	= $tilausvahvistus_tallenna;
		$file["error"] 		= 0;
		$file["size"] 		= filesize($tilausvahvistus_tallenna);

		$_FILES['tilvah_liite'] = $file;

		$liitetied_id = tallenna_liite('tilvah_liite', 'lasku', $laskurow['tunnus'], t('Myyntilaskun tilausvahvistus'));

		//poistetaan tmp file samantien kuleksimasta...
		system("rm -f ".basename($tilausvahvistus_tallenna));
	}
}
elseif ($laskurow["tilaustyyppi"] == '0') {
		// Ylläpitosopimus
		// tila on 0 ja alatila on V
		$query  = "	UPDATE lasku
					SET tila = '0',
					alatila  = 'V'
					WHERE yhtio = '$kukarow[yhtio]'
					and tunnus  = '$laskurow[tunnus]'
					and tila in ('N','L','0')
					and alatila != 'X'";
		$result = mysql_query($query) or pupe_error($query);

		// tyyppi on 0 ja rivit ei ole ennakkopoistossa
		$query = "	UPDATE tilausrivi
					SET tyyppi	= '0'
					WHERE yhtio	= '$kukarow[yhtio]'
					and otunnus	= '$laskurow[tunnus]'
					and tyyppi in ('L','0')";
		$result = mysql_query($query) or pupe_error($query);
}
elseif ($laskurow["tila"] == 'T') {
	//Tarjoukset

	//tila on T kun kyseessä on tarjous
	$query  = "	UPDATE lasku
				SET tila='T',
				alatila='A'
				WHERE tunnus='$laskurow[tunnus]'
				and tila ='T'
				and yhtio='$kukarow[yhtio]'";
	$result = mysql_query($query) or pupe_error($query);

	// tarjouksissa tyyppi on T ja rivit ei ole ennakkopoistossa
	$query = "	UPDATE tilausrivi
				SET tyyppi	= 'T'
				WHERE yhtio	= '$kukarow[yhtio]'
				and otunnus	= '$laskurow[tunnus]'
				and tyyppi in ('L','T')";
	$result = mysql_query($query) or pupe_error($query);
}
else {
	// Tässä on normaalit tilaukset

	// katotaan ollaanko valittu joku poikkeava kassalipas
	if ($kertakassa != '') {
		$query = "UPDATE lasku SET kassalipas = '$kertakassa' where yhtio='$kukarow[yhtio]' and tunnus = '$laskurow[tunnus]'";
		$result = mysql_query($query) or pupe_error($query);

		$laskurow['kassalipas'] = $kertakassa;
	}

	// Aktivoituiko "Odottaa jt-rivejä" tilaus?
	if (($laskurow["tila"] == 'N' or $laskurow["tila"] == 'G') and ($laskurow["alatila"] == 'T' or $laskurow["alatila"] == 'U')) {
		// Onko jotain tulostettavaa?
		$query = "	SELECT *
					from tilausrivi
					where yhtio = '$kukarow[yhtio]'
					and otunnus = '$laskurow[tunnus]'
					and keratty = ''
					and var not in ('P','J','S')
					and tyyppi != 'D'
					and varattu > 0";
		$keres = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($keres) > 0) {
			//Muutetaan tulostettavaksi
			$query = "UPDATE lasku SET alatila = '' where yhtio = '$kukarow[yhtio]' and tunnus = '$laskurow[tunnus]'";
			$result = mysql_query($query) or pupe_error($query);
			$laskurow["alatila"] = "";
		}
	}

	//päivitetään tilaus valmisaika, kun painetaan ekan kerran tilaus valmiiksi
	if ($laskurow['h1time'] == "0000-00-00 00:00:00") {
		$query = "	UPDATE lasku
					SET h1time = now(),
					hyvak1 = '$kukarow[kuka]'
					WHERE yhtio = '$kukarow[yhtio]'
					AND tunnus = '$laskurow[tunnus]'";
		$result = mysql_query($query) or pupe_error($query);

		$laskurow['h1time'] = date('Y-m-d H:i:s');
		$laskurow['hyvak1'] = $kukarow["kuka"];
	}

	//päivitetään tilaus valmisaika, kun painetaan ekan kerran tilaus valmiiksi
	if ($laskurow['tila'] == "N" and $laskurow['alatila'] == "F") {
		$query = "	UPDATE lasku
					SET h2time = now(),
					hyvak2 = '$kukarow[kuka]'
					WHERE yhtio = '$kukarow[yhtio]'
					AND tunnus = '$laskurow[tunnus]'";
		$result = mysql_query($query) or pupe_error($query);

		$laskurow['h2time'] = date('Y-m-d H:i:s');
		$laskurow['hyvak2'] = $kukarow["kuka"];
	}

	// otetaan maksuehto tässä heti alussa selville.. käteinen ja itsetulostus muuttaa asioita
	$query = "	SELECT *
				FROM maksuehto
				WHERE yhtio = '$kukarow[yhtio]'
				AND tunnus = '$laskurow[maksuehto]'";
	$result = mysql_query($query) or pupe_error($query);

	$kateinen = "";
	$jaksotettu = "";
	$itsetulostus = "";

	if (mysql_num_rows($result) == 1) {
		$maksuehtorow = mysql_fetch_assoc($result);

		// jos kyseessä on käteiskauppaa
		if ($maksuehtorow['kateinen'] != '') {
			$kateinen = "X";
		}
		// jos kyseessä on jaksotusta
		if ($maksuehtorow['jaksotettu'] != '') {
			$jaksotettu = "X";
		}
		//jos kyseessä on itsetulostusmaksuehto
		if ($maksuehtorow['itsetulostus'] != '') {
			$itsetulostus = "X";
		}
	}

	if ($yhtiorow["kerataanko_saldottomat"] == '') {
		$lisasalker = " and tuote.ei_saldoa = '' ";
	}
	else {
		$lisasalker = " ";
	}

	// Katotaan, onko tämä jo kerätty...
	$query = "	SELECT tilausrivi.*
				FROM tilausrivi use index (yhtio_otunnus)
				JOIN tuote on tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
				WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
				AND tilausrivi.otunnus = '$laskurow[tunnus]'
				AND tilausrivi.keratty != ''
				$lisasalker";
	$keres = mysql_query($query) or pupe_error($query);

	// jos on yksikin kerätty rivi
	// lisäksi tila ei saa olla N (myyntitilaus kesken), koska sitä ei ole voitu kerätä tai jos tila on L (myyntitilaus) niin alatila ei saa olla A (tulostusjonossa), koska sitä ei olla voitu kerätä
	if (mysql_num_rows($keres) > 0 and ($laskurow["tila"] != "N" or ($laskurow["tila"] == "L" and $laskurow["alatila"] != "A"))) {
		$query = "	UPDATE tilausrivi
					SET keratty = '$kukarow[kuka]', kerattyaika = now()
					WHERE yhtio = '$kukarow[yhtio]'
					and otunnus = '$laskurow[tunnus]'
					and keratty = ''
					and var not in ('P','J','S')
					and tyyppi != 'D'";
		$result = mysql_query($query) or pupe_error($query);

		// Laitetaan rivit toimitetuiksi jos otsikko sanoo toimitettu
		if ($laskurow["alatila"] == 'D' or $laskurow["alatila"] == 'E' or $laskurow["alatila"] == 'J') {
			$query = "	UPDATE tilausrivi
						SET toimitettu = '$kukarow[kuka]', toimitettuaika = now()
						WHERE yhtio = '$kukarow[yhtio]'
						and otunnus = '$laskurow[tunnus]'
						and keratty != ''
						and toimitettu = ''
						and var not in ('P','J','S')
						and tyyppi != 'D'";
			$result = mysql_query($query) or pupe_error($query);
		}

		$tilaus_valmis_ulos .= "<font class='message'>".t("Tilauksella oli jo kerättyjä rivejä, joten lähetettä ei tulosteta").".<br>".t("Jos haluat lähetteen tulosta lähetteen kopio. Tilausnumero on")." $laskurow[tunnus].</font><br><br>";

		// jos on myyntitilaus ja ei haluttu tulostaa lähetettä, merkataan rivit kerätyksi ja toimitetuksi ja laitetaan alatila D
		if ($laskurow['eilahetetta'] != '' or $laskurow['sisainen'] != '') {
			if ($laskurow['sisainen'] == '' and ($kateinen == 'X' or $itsetulostus == "X" or $laskurow["vienti"] != '')) {
				// Jos kyseessä on käteismyyntiä tai maksuehtolla on itsetulostustäppä tai vientiä, merkataan tilaukset kerätyksi mutta ei toimitetuksi ja alatila C/B
				if ($laskurow["vienti"] != '') {
					$alat = "B";
				}
				else {
					$alat = "C";
				}

				$query  = "	UPDATE lasku set tila='L', alatila='$alat', lahetepvm=now()
							WHERE tunnus='$laskurow[tunnus]'
							and yhtio='$kukarow[yhtio]'";
				$result = mysql_query($query) or pupe_error($query);
			}
			else {
				// Jos kyseessä  EI ole käteismyyntiä eikä itsetulostusta eikä vientiä TAI tämä on sisäinen niin merkataan tilaus toimitetuksi
				$query  = "	UPDATE tilausrivi
							SET toimitettuaika = now(), toimitettu = '$kukarow[kuka]'
							WHERE otunnus 	= '$laskurow[tunnus]'
							and yhtio		= '$kukarow[yhtio]'
							and var not in ('P','J','S')
							and keratty    != ''
							and toimitettu	= ''
							and tyyppi 	   != 'D'";
				$result = mysql_query($query) or pupe_error($query);

				if ($jaksotettu == 'X') {
					$query = "	UPDATE lasku SET tila='L', alatila='J', lahetepvm=now()
								WHERE tunnus = '$laskurow[tunnus]'
								and yhtio = '$kukarow[yhtio]'";
					$result = mysql_query($query) or pupe_error($query);

					$tilaus_valmis_ulos .= t("Maksusopimustilaus siirretty odottamaan loppulaskutusta").": $laskurow[tunnus] $laskurow[nimi]<br>\n";
				}
				else {
					$query  = "	UPDATE lasku set tila='L', alatila='D', lahetepvm=now()
								WHERE tunnus = '$laskurow[tunnus]'
								and yhtio = '$kukarow[yhtio]'";
					$result = mysql_query($query) or pupe_error($query);
				}
			}

			if ($laskurow["sisainen"] != '') {
				//laskutetaan koko tilaus heti jos se on sisainen lasku
				$laskutettavat	= $laskurow["tunnus"];
				$tee 			= "TARKISTA";
				$laskutakaikki 	= "KYLLA";
				$silent		 	= "VIENTI";

				if ($kukarow["kirjoitin"] != 0 and $valittu_tulostin == 0) {
					$valittu_tulostin = $kukarow["kirjoitin"];
				}
				elseif ($valittu_tulostin == 0) {
					$valittu_tulostin = "AUTOMAAGINEN_VALINTA";
				}

				require ("tilauskasittely/verkkolasku.php");
			}
		}
	}
	else {
		// tässä käydään läpi vähän näitä rahtijuttuja
		// katotaan saadaanko tilaus ilmaseks jos ollaan yli rajojen
		// haetaan asiakkaan alarajasumma
		$as_que = "	SELECT rahtivapaa_alarajasumma
					FROM asiakas
					WHERE yhtio = '$kukarow[yhtio]'
					AND tunnus = '$laskurow[liitostunnus]'
					AND rahtivapaa_alarajasumma > 0";
		$as_res = mysql_query($as_que) or pupe_error($as_que);

		$rahtivapaa_alarajasumma = 0;

		if (mysql_num_rows($as_res) == 1) {
			$as_row = mysql_fetch_assoc($as_res);
			$rahtivapaa_alarajasumma = (float) $as_row["rahtivapaa_alarajasumma"];
		}
		else {
			$rahtivapaa_alarajasumma = (float) $yhtiorow["rahtivapaa_alarajasumma"];
		}

		if (isset($kaikkiyhteensa) and (float) $kaikkiyhteensa != 0) {
			$kaikkiyhteensa = yhtioval($kaikkiyhteensa, $laskurow["vienti_kurssi"]); // käännetään yhteensäsumma yhtiövaluuttaan
		}
		else {
			$kaikkiyhteensa = 0;
		}

		if ($kaikkiyhteensa > $rahtivapaa_alarajasumma and $rahtivapaa_alarajasumma != 0) {
			$tilaus_valmis_ulos .= t("Tilauksen arvo ylitti rahtivapaasumman! Merkattiin tilaus rahtivapaaksi.")."<br>";

			$query = "UPDATE lasku SET rahtivapaa = 'o' WHERE yhtio = '$kukarow[yhtio]' AND tunnus = '$laskurow[tunnus]'";
			$rahtivapres = mysql_query($query) or pupe_error($query);

			$laskurow["rahtivapaa"] = 'o';
		}
		elseif ($yhtiorow["rahti_hinnoittelu"] == "o" or $yhtiorow["rahti_hinnoittelu"] == "P") {
			// jos rahtihinnoittelu tapahtuu tilauksen hinnan (alviton) perusteella
			// tai rahtihinnoittelu tapahtuu tilauksen painon mukaan automaattisesti niin tehdään se tässä

			// tähän kerätään tilausnumerot, joista lasketaan rahti
			$rahtimaksu_tunnukset = array();

			// jos kyseessä on JT-tilaus, katsotaan pitääkö lisätä rahtikulua usealle tilaukselle
			if ($laskurow["clearing"] == "JT-TILAUS" and $yhtiorow["jt_rahti"] == "E") {
				// etsitään kaikki isätilaukset miltä nämä JT-tuotteet ovat tulleet ja katsotaan onko niillä jo rahtikulu (lisätään rahtikulu niille jolla ei ole)
				$query = "	SELECT DISTINCT vanha_otunnus
							FROM tilausrivi
							JOIN tilausrivin_lisatiedot USE INDEX (tilausrivitunnus) ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
							AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus)
							WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
							AND tilausrivi.otunnus = '$laskurow[tunnus]'";
				$step1re = mysql_query($query) or pupe_error($query);

				while ($step1row = mysql_fetch_assoc($step1re)) {
					// etsitään istätilausten kaikki lapset
					$query = "	SELECT group_concat(DISTINCT tilausrivi.otunnus) uudet_otunnukset
								FROM tilausrivin_lisatiedot
								JOIN tilausrivi on (tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus)
								WHERE tilausrivin_lisatiedot.yhtio = '$kukarow[yhtio]'
								and tilausrivin_lisatiedot.vanha_otunnus = '$step1row[vanha_otunnus]'";
					$step2re = mysql_query($query) or pupe_error($query);
					$step2row = mysql_fetch_assoc($step2re);

					// katsotaan onko lapsitilauksilla rahtikulu syötetty
					$query = "	SELECT tunnus
								FROM tilausrivi
								WHERE yhtio = '$kukarow[yhtio]'
								AND otunnus in ($step2row[uudet_otunnukset])
								AND tuoteno = '$yhtiorow[rahti_tuotenumero]'";
					$step3re = mysql_query($query) or pupe_error($query);

					// jos ei niin pitää rahdittaa!
					if (mysql_num_rows($step3re) == 0) {
						if (!in_array($step2row["uudet_otunnukset"], $rahtimaksu_tunnukset)) $rahtimaksu_tunnukset[] = $step2row["uudet_otunnukset"];
					}
				}
			}
			else {
				$rahtimaksu_tunnukset[] = $laskurow["tunnus"];
			}

			$rahtimaksu_tunnukset = implode(",", $rahtimaksu_tunnukset);

			$query = "	SELECT tunnus
						from tilausrivi
						where yhtio = '$kukarow[yhtio]'
						and otunnus = '$laskurow[tunnus]'
						and tuoteno = '$yhtiorow[rahti_tuotenumero]'";
			$result = mysql_query($query) or pupe_error($query);

			//jos tilauksella on jo rahtikulu niin ei lisätä sitä uudestaan
			if (mysql_num_rows($result) == 0) {

				$query = "	SELECT *
							FROM toimitustapa
							WHERE yhtio = '$kukarow[yhtio]'
							AND selite = '$laskurow[toimitustapa]'";
				$result = mysql_query($query) or pupe_error($query);
				$torow = mysql_fetch_assoc($result);

				// jos toimitustapana on joku nouto niin ei lisätä rahtikulua
				if ($torow["nouto"] == "") {

					// haetaan rahtimaksu
					$rahtihinta = hae_rahtimaksu($rahtimaksu_tunnukset);

					//lisätään rahtikulurivi vain jos rahtikulu on erisuuri kuin nolla, nollarivejä on ilmeisesti turha lisätä
					if ($rahtihinta != 0) {

						// haetaan rahtituotteen tiedot
						$query = "	SELECT *
									FROM tuote
									WHERE yhtio = '$kukarow[yhtio]'
									AND tuoteno = '$yhtiorow[rahti_tuotenumero]'";
						$rhire = mysql_query($query) or pupe_error($query);
						$trow  = mysql_fetch_assoc($rhire);

						$otunnus	 = $laskurow['tunnus'];
						$hinta		 = $rahtihinta;
						$trow["alv"] = $laskurow["alv"];
						$nimitys	 = $laskurow["toimitustapa"];

						list($lis_hinta, $lis_netto, $lis_ale, $alehinta_alv, $alehinta_val) = alehinta($laskurow, $trow, '1', 'N', $hinta, 0);
						list($hinta, $alv) = alv($laskurow, $trow, $lis_hinta, '', $alehinta_alv);

						$query = "	INSERT into tilausrivi (laadittu, hinta, netto, varattu, tilkpl, otunnus, tuoteno, nimitys, yhtio, tyyppi, alv, kommentti)
									values  (now(), '$hinta', 'N', '1', '1', '$otunnus', '$trow[tuoteno]', '$nimitys', '$kukarow[yhtio]', 'L', '$alv', '')";
						$addtil = mysql_query($query) or pupe_error($query);

						$tilaus_valmis_ulos .= t("Tilaukselle lisättiin rahtikulua").": $hinta $laskurow[valkoodi]<br>";

						if ($torow["jvkulu"] != 0 and $maksuehtorow["jv"] != "") {
							$otunnus	 = $laskurow['tunnus'];
							$hinta		 = $torow["jvkulu"]; // jvkulu
							$trow["alv"] = $laskurow["alv"];
							$nimitys	 = t("Jälkivaatimuskulu");

							list($hinta, $alv) = alv($laskurow, $trow, $hinta, '', '');

							$query  = "	INSERT into tilausrivi (laadittu, hinta, netto, varattu, tilkpl, otunnus, tuoteno, nimitys, yhtio, tyyppi, alv, kommentti)
										values (now(), '$hinta', 'N', '1', '1', '$otunnus', '$trow[tuoteno]', '$nimitys', '$kukarow[yhtio]', 'L', '$alv', '')";
							$addtil = mysql_query($query) or pupe_error($query);

							$tilaus_valmis_ulos .= t("Tilaukselle lisättiin jälkivaatimuskulua").": $hinta $laskurow[valkoodi]<br>";
						}
					}
				}
			}

		}
		else {
			// meillä on rahdituksessa syötettävään painoon perustuva rahtimaksu
			// katsotaan onko rahtituotenumero syötetty jo käsin
			$query = "	SELECT tunnus
						from tilausrivi
						where yhtio = '$kukarow[yhtio]'
						and otunnus = '$laskurow[tunnus]'
						and tuoteno = '$yhtiorow[rahti_tuotenumero]'";
			$result = mysql_query($query) or pupe_error($query);

			//jos tilauksella on jo rahtikulu niin merkataan se rahtivapaaksi, ettei raihtikulu mene uudestaan laskutuksen yhteydessä!
			if (mysql_num_rows($result) > 0) {
				$tilaus_valmis_ulos .= t("Tilauksella oli käsin syötetty rahtikulu! Merkattiin tilaus rahtivapaaksi, ettei kulua lisätä enää automaattisesti laskutuksessa.")."<br>";
				$query = "UPDATE lasku SET rahtivapaa = 'o' WHERE yhtio = '$kukarow[yhtio]' AND tunnus = '$laskurow[tunnus]'";
				$rahtivapres = mysql_query($query) or pupe_error($query);
				$laskurow["rahtivapaa"] = 'o';
			}
		}

		// Jos on kassamyyntiä ja se on käteistä niin tulostetaan lasku heti ilman lähetettä
		if (($kateinen == 'X' and $kateisohitus == "" and ($kukarow["kassamyyja"] != '' or $kertakassa != "") and $laskurow["vienti"] == '') or $korkolasku == 'kylla') {

			$query  = "	UPDATE tilausrivi
						SET kerattyaika = now(), keratty = '$kukarow[kuka]'
						WHERE otunnus 	= '$laskurow[tunnus]'
						and yhtio		= '$kukarow[yhtio]'
						and var not in ('P','J','S')
						and keratty		= ''
						and toimitettu	= ''
						and tyyppi 	   != 'D'";
			$result = mysql_query($query) or pupe_error($query);

			$query = "	UPDATE tilausrivi
						SET toimitettu = '$kukarow[kuka]', toimitettuaika = now()
						WHERE otunnus 	= '$laskurow[tunnus]'
						and var not in ('P','J')
						and yhtio 		= '$kukarow[yhtio]'
						and keratty    != ''
						and toimitettu  = ''
						and tyyppi     != 'D'";
			$result = mysql_query($query) or pupe_error($query);

			$query = "	UPDATE lasku
						SET tila='L', alatila='D'
						WHERE tunnus = '$laskurow[tunnus]'
						and yhtio = '$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);

			//tulostetaan käteislasku...
			$laskutettavat	= $laskurow["tunnus"];
			$tee 			= "TARKISTA";
			$laskutakaikki 	= "KYLLA";
			$silent		 	= "VIENTI";

			if ($kukarow["kirjoitin"] != 0 and $valittu_tulostin == 0) {
				$valittu_tulostin = $kukarow["kirjoitin"];
			}
			elseif ($valittu_tulostin == 0) {
				$valittu_tulostin = "AUTOMAAGINEN_VALINTA";
			}

			require ("tilauskasittely/verkkolasku.php");
		}
		// jos on myyntitilaus ja ei haluttu tulostaa lähetettä, merkataan rivit kerätyksi ja toimitetuksi ja laitetaan alatila D
		elseif (($laskurow['eilahetetta'] != '' or $laskurow['sisainen'] != '') and $kateisohitus == "") {

			// vaikka laitetaan sisäiseen laskutukseen tai ei haluttu lähetettä, mutta annetaan mahdollisuus lähettää tilausvahvistus omaan sähköpostiin
			if (strpos($laskurow['tilausvahvistus'], 'O') !== FALSE) {
				require("tilausvahvistus-email.inc");
			}

			if ($laskurow['sisainen'] == '' and ($kateinen == 'X' or $itsetulostus == "X" or $laskurow["vienti"] != '')) {
				// Jos kyseessä on käteismyyntiä tai jos maksuehtolla on itsetulostustäppä tai vientiä, merkataan tilaukset kerätyksi mutta ei toimitetuksi ja alatila C/B
				$query  = "	UPDATE tilausrivi
							SET kerattyaika = now(), keratty = '$kukarow[kuka]'
							WHERE otunnus 	= '$laskurow[tunnus]'
							and yhtio 		= '$kukarow[yhtio]'
							and var not in ('P','J','S')
							and keratty 	= ''
							and toimitettu 	= ''
							and tyyppi     != 'D'";
				$result = mysql_query($query) or pupe_error($query);

				if ($laskurow["vienti"] != '') {
					$alat = "B";
				}
				else {
					$alat = "C";
				}

				$query  = "	UPDATE lasku set tila='L', alatila='$alat', lahetepvm=now()
							WHERE tunnus='$laskurow[tunnus]'
							and yhtio='$kukarow[yhtio]'";
				$result = mysql_query($query) or pupe_error($query);
			}
			else {
				// Jos kyseessä  EI ole käteismyyntiä eikä itsetulostusta eikä vientiä TAI tämä on sisäinen niin merkataan tilaus toimitetuksi
				$query  = "	UPDATE tilausrivi
							SET kerattyaika = now(), keratty = '$kukarow[kuka]'
							WHERE otunnus 	= '$laskurow[tunnus]'
							and yhtio		= '$kukarow[yhtio]'
							and var not in ('P','J','S')
							and keratty		= ''
							and toimitettu	= ''
							and tyyppi 	   != 'D'";
				$result = mysql_query($query) or pupe_error($query);

				$query  = "	UPDATE tilausrivi
							SET toimitettuaika = now(), toimitettu = '$kukarow[kuka]'
							WHERE otunnus 	= '$laskurow[tunnus]'
							and yhtio		= '$kukarow[yhtio]'
							and var not in ('P','J','S')
							and keratty    != ''
							and toimitettu	= ''
							and tyyppi 	   != 'D'";
				$result = mysql_query($query) or pupe_error($query);

				if ($jaksotettu == 'X') {
					$query = "	UPDATE lasku SET tila='L', alatila='J', lahetepvm=now()
								WHERE tunnus = '$laskurow[tunnus]'
								and yhtio = '$kukarow[yhtio]'";
					$result = mysql_query($query) or pupe_error($query);

					$tilaus_valmis_ulos .= t("Maksusopimustilaus siirretty odottamaan loppulaskutusta").": $laskurow[tunnus] $laskurow[nimi]<br>\n";
				}
				else {
					$query  = "	UPDATE lasku set tila='L', alatila='D', lahetepvm=now()
								WHERE tunnus = '$laskurow[tunnus]'
								and yhtio = '$kukarow[yhtio]'";
					$result = mysql_query($query) or pupe_error($query);
				}
			}

			if ($laskurow["sisainen"] != '') {
				//laskutetaan koko tilaus heti jos se on sisainen lasku
				$laskutettavat	= $laskurow["tunnus"];
				$tee 			= "TARKISTA";
				$laskutakaikki 	= "KYLLA";
				$silent		 	= "VIENTI";

				if ($kukarow["kirjoitin"] != 0 and $valittu_tulostin == 0) {
					$valittu_tulostin = $kukarow["kirjoitin"];
				}
				elseif ($valittu_tulostin == 0) {
					$valittu_tulostin = "AUTOMAAGINEN_VALINTA";
				}

				require ("tilauskasittely/verkkolasku.php");
			}
		}
		else {
			// Katotaan mitä tehdään lähetteelle

			// Ensin on pakko splitata lähete varastoittain
			// tyhjennetään laskun varastokenttä, siellä oli tieto varastoon optimoinnista mutta nyt se kertoo mihin varastoon lähete kuuluu laittaa
			$laskurow['varasto'] = 0;

			//Toimitustavan hetierä tarvitaan
			$query = "SELECT tulostustapa from toimitustapa where yhtio='$kukarow[yhtio]' and selite='$laskurow[toimitustapa]'";
			$result = mysql_query($query) or pupe_error($query);
			$totarowx = mysql_fetch_assoc($result);

			// Maksuehdon jv tieto tarvitaan
			$query = "SELECT jv from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
			$result = mysql_query($query) or pupe_error($query);
			$maehrowx = mysql_fetch_assoc($result);

			if ($yhtiorow["splittauskielto"] == "K") {
				$sorttaus = "kerayspvm, sorttauskentta";
			}
			else {
				$sorttaus = "sorttauskentta";
			}

			//haetaan kaikkien tilausrivien varastopaikat
			$query = "	SELECT otunnus, tunnus, hyllyalue, hyllynro, hyllyvali, hyllytaso,  kerayspvm, toimaika,
						concat(lpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0')) sorttauskentta
						FROM tilausrivi
						WHERE yhtio = '$kukarow[yhtio]'
						and otunnus = '$laskurow[tunnus]'
						and hyllyalue != ''
						and varattu+kpl > 0
						and var not in ('J','P')
						ORDER BY $sorttaus";
			$result = mysql_query($query) or pupe_error($query);

			//tässä muuttujassa on sit kaikki tunnukset splitin jälkeen
			$tilausnumerot	 = array();
			$tilausnumerot[] = $laskurow["tunnus"];

			//mihin varastoon lähete kuuluu. aluksi x
			$varasto = $edvarasto = $kerayspvm = $edkerayspvm = "x";
			$tulostusalue = $edtulostusalue = "x";

			//jos tilaus splittaantuu, niin laitetaan muihin otsikoihin ensimmäisen tilauksen tunnus vanhaksitunnukseksi
			$temp_laskutunnus = $laskurow['tunnus'];

			while ($row = mysql_fetch_assoc($result)) {

				$varasto = kuuluukovarastoon($row["hyllyalue"], $row["hyllynro"]);
				$tulostusalue = onkotulostusalueita($row["hyllyalue"], $row["hyllynro"], $varasto);

				$query = "SELECT * from varastopaikat where yhtio = '$kukarow[yhtio]' and tunnus = '$varasto'";
				$varaston_res = mysql_query($query) or pupe_error($query);
				$varaston_row = mysql_fetch_assoc($varaston_res);

				$ultilno = tarvitaanko_intrastat($varaston_row["maa"], $laskurow["toim_maa"]);

				$laskurow["maa_maara"] = $laskurow["toim_maa"];
				$laskurow["maa_lahetys"] = $varaston_row["maa"];

				if ($ultilno == '-1') {
					// vienti-ilmoitus
					// katotaan onko jotain oletuksia asiakkaan takaa
					$query  = "SELECT * from asiakas where yhtio = '$kukarow[yhtio]' and tunnus = '$laskurow[liitostunnus]'";
					$toiresult = mysql_query($query) or pupe_error($query);
					$aputoimirow = mysql_fetch_assoc($toiresult);

					$laskurow["aktiivinen_kuljetus"] 				= $aputoimirow["aktiivinen_kuljetus"];
					$laskurow["aktiivinen_kuljetus_kansallisuus"]	= $aputoimirow["aktiivinen_kuljetus_kansallisuus"];
					$laskurow["bruttopaino"] 						= $aputoimirow["bruttopaino"];
					$laskurow["kauppatapahtuman_luonne"] 			= $aputoimirow["kauppatapahtuman_luonne"];
					$laskurow["kontti"]								= $aputoimirow["kontti"];
					$laskurow["kuljetusmuoto"]						= $aputoimirow["kuljetusmuoto"];
					$laskurow["lisattava_era"] 						= $aputoimirow["lisattava_era"];
					$laskurow["poistumistoimipaikka"] 				= $aputoimirow["poistumistoimipaikka"];
					$laskurow["poistumistoimipaikka_koodi"] 		= $aputoimirow["poistumistoimipaikka_koodi"];
					$laskurow["sisamaan_kuljetus"]					= $aputoimirow["sisamaan_kuljetus"];
					$laskurow["sisamaan_kuljetusmuoto"]  			= $aputoimirow["sisamaan_kuljetusmuoto"];
					$laskurow["sisamaan_kuljetus_kansallisuus"]		= $aputoimirow["sisamaan_kuljetus_kansallisuus"];
					$laskurow["vahennettava_era"] 					= $aputoimirow["vahennettava_era"];
				}
				elseif ($ultilno == '-2') {
					// tuonti-ilmoitus
					// katotaan onko jotain oletuksia toimitustavan takana
					$query  = "SELECT * from toimitustapa where yhtio = '$kukarow[yhtio]' and selite = '$laskurow[toimitustapa]'";
					$toiresult = mysql_query($query) or pupe_error($query);
					$aputoimirow = mysql_fetch_assoc($toiresult);

					$laskurow["aktiivinen_kuljetus"] 				= $aputoimirow["aktiivinen_kuljetus"];
					$laskurow["aktiivinen_kuljetus_kansallisuus"]	= $aputoimirow["aktiivinen_kuljetus_kansallisuus"];
					$laskurow["bruttopaino"] 						= $aputoimirow["bruttopaino"];
					$laskurow["kauppatapahtuman_luonne"] 			= $aputoimirow["kauppatapahtuman_luonne"];
					$laskurow["kontti"]								= $aputoimirow["kontti"];
					$laskurow["kuljetusmuoto"]						= $aputoimirow["kuljetusmuoto"];
					$laskurow["lisattava_era"] 						= $aputoimirow["lisattava_era"];
					$laskurow["poistumistoimipaikka"] 				= $aputoimirow["poistumistoimipaikka"];
					$laskurow["poistumistoimipaikka_koodi"] 		= $aputoimirow["poistumistoimipaikka_koodi"];
					$laskurow["sisamaan_kuljetus"]					= $aputoimirow["sisamaan_kuljetus"];
					$laskurow["sisamaan_kuljetusmuoto"]  			= $aputoimirow["sisamaan_kuljetusmuoto"];
					$laskurow["sisamaan_kuljetus_kansallisuus"]		= $aputoimirow["sisamaan_kuljetus_kansallisuus"];
					$laskurow["vahennettava_era"] 					= $aputoimirow["vahennettava_era"];
				}
				elseif ($varaston_row["maa"] == $laskurow["toim_maa"] and $laskurow["toim_maa"] != "") {
					// intrastattia ei lähetetä tulliin kun varasto ja toimitusosoite on samassa maassa
					$laskurow["kauppatapahtuman_luonne"] = '999';
				}

				//varasto vaihtuu, splitataan tilaus, paitsi jos kyseessä on jv-tilaus jonka rahtikirja tulostetaan heti, tai jos spliauskielto on trigattu
				if (((($varasto != $edvarasto and $edvarasto != 'x') or ($tulostusalue != $edtulostusalue and $edtulostusalue != 'x' and $edtulostusalue != '')) and ($maehrowx["jv"] == '' or $totarowx["tulostustapa"] != 'H') and $laskurow["splittauskielto"] != "E") or ($row["kerayspvm"] != $edkerayspvm and $edkerayspvm != 'x' and $yhtiorow["splittauskielto"] == "K")) {

					// Meidän on päivitettävä tunnusnippu
					if ($yhtiorow["splittauskielto"] == "K" and $laskurow["tunnusnippu"] == 0) {
						$query = "UPDATE lasku set tunnusnippu = tunnus where yhtio = '$kukarow[yhtio]' and tunnus = '$laskurow[tunnus]' and tunnusnippu = 0";
						$upresult = mysql_query($query) or pupe_error($query);
						$laskurow["tunnusnippu"] = $laskurow["tunnus"];
					}

					if (!is_resource($laskusplitres)) {
						// Jotta saadaa lasku kopsattua kivasti jos se splittaantuu
						$query = "	SELECT *
									FROM lasku
									WHERE yhtio = '$kukarow[yhtio]'
									and tunnus = '$laskurow[tunnus]'";
						$laskusplitres = mysql_query($query) or pupe_error($query);
					}

					$fields = "yhtio";
					$values = "'$kukarow[yhtio]'";

					// Ei monisteta tunnusta
					for($i=1; $i < mysql_num_fields($laskusplitres)-1; $i++) {

						$fieldname = mysql_field_name($laskusplitres,$i);

						$fields .= ", ".$fieldname;

						switch ($fieldname) {
							case 'alatila':
								$values .= ", 'A'";
								break;
							case 'tila':
								$values .= ", 'N'";
								break;
							case 'vanhatunnus':
								$values .= ", '$temp_laskutunnus'";
								break;
							case 'varasto':
								$values .= ", '$varasto'";
								break;
							case 'tulostusalue':
								$values .= ", '$tulostusalue'";
								break;
							case 'ultilno':
								$values .= ", '$ultilno'";
								break;
							default:
								$values .= ", '".$laskurow[$fieldname]."'";
						}
					}

					$kysely  = "INSERT into lasku ($fields) VALUES ($values)";
					$uusires = mysql_query($kysely) or pupe_error($kysely);

					$laskurow['tunnus'] = (string) mysql_insert_id();
					$tilausnumerot[] = $laskurow["tunnus"];

				}
				else {
					//päivitetään laskulle tieto mihin varastoon se kuuluu
					$query = "	UPDATE lasku set
								varasto 							= '$varasto',
								tulostusalue						= '$tulostusalue',
								ultilno 							= '$ultilno',
								maa_maara							= '$laskurow[maa_maara]',
								maa_lahetys							= '$laskurow[maa_lahetys]',
								kauppatapahtuman_luonne 			= '$laskurow[kauppatapahtuman_luonne]',
								kuljetusmuoto						= '$laskurow[kuljetusmuoto]',
								sisamaan_kuljetus					= '$laskurow[sisamaan_kuljetus]',
								sisamaan_kuljetusmuoto  			= '$laskurow[sisamaan_kuljetusmuoto]',
								sisamaan_kuljetus_kansallisuus		= '$laskurow[sisamaan_kuljetus_kansallisuus]',
								kontti								= '$laskurow[kontti]',
								aktiivinen_kuljetus 				= '$laskurow[aktiivinen_kuljetus]',
								aktiivinen_kuljetus_kansallisuus	= '$laskurow[aktiivinen_kuljetus_kansallisuus]',
								poistumistoimipaikka 				= '$laskurow[poistumistoimipaikka]',
								poistumistoimipaikka_koodi 			= '$laskurow[poistumistoimipaikka_koodi]',
								bruttopaino 						= '$laskurow[bruttopaino]',
								lisattava_era 						= '$laskurow[lisattava_era]',
								vahennettava_era 					= '$laskurow[vahennettava_era]'
								where yhtio ='$kukarow[yhtio]'
								and tunnus = '$laskurow[tunnus]'";
					$varresult = mysql_query($query) or pupe_error($query);

					$laskurow["varasto"] = $varasto;
				}

				//	edellinen varasto talteen
				$edvarasto = $varasto;

				// edellinen tulostusalue talteen
				$edtulostusalue = $tulostusalue;

				//	edellinen kerayspvm talteen
				$edkerayspvm = $row["kerayspvm"];

				//jos tilausrivin laskunumero on eri
				if ($row['otunnus'] != $laskurow['tunnus']) {
					$query = "UPDATE tilausrivi set otunnus='$laskurow[tunnus]' where tunnus='$row[tunnus]' and yhtio='$kukarow[yhtio]'";
					$ohno  = mysql_query($query) or pupe_error($query);
				}
			}

			// Jos tilaus splittaantui moneen osaan
			if (count($tilausnumerot) > 1) {
				$tilauksia = 1;
				$numerot = implode(', ',$tilausnumerot);

				foreach ($tilausnumerot as $tilausnumero) {

					$upviesti = t("Tilaus jakaantunut")." ".count($tilausnumerot)." ".t("tilaukseksi").": ".$numerot." ".t("Tilaus").": ".$tilauksia."/".count($tilausnumerot);

					$query = "	UPDATE lasku set sisviesti2 = concat_ws(' ', sisviesti2, '$upviesti')
								where yhtio='$kukarow[yhtio]' and tunnus='$tilausnumero'";
					$upresult = mysql_query($query) or pupe_error($query);

					$tilauksia++;
				}
			}

			// Laitetaan splittien toim ja kerpvm ät kuntoon / viikkojutut on pakko nollata splitille
			if ($yhtiorow["splittauskielto"] == "K" and count($tilausnumerot) > 1) {
				foreach ($tilausnumerot as $tilausnumero) {

					$query = "	SELECT min(kerayspvm) kerayspvm, min(toimaika) toimaika
					 			from tilausrivi
								where yhtio='$kukarow[yhtio]' and otunnus='$tilausnumero'";
					$result = mysql_query($query) or pupe_error($query);
					$toimaikatilrirow = mysql_fetch_assoc($result);

					$query = "	SELECT kerayspvm, toimaika
								from lasku
								where yhtio='$kukarow[yhtio]' and tunnus='$tilausnumero'";
					$result = mysql_query($query) or pupe_error($query);
					$toimaikalaskurow = mysql_fetch_assoc($result);

					if ($toimaikatilrirow["kerayspvm"] != substr($toimaikalaskurow["kerayspvm"],0,10) or $toimaikatilrirow["toimaika"] != $toimaikalaskurow["toimaika"]) {
						$query = "	UPDATE lasku
									set kerayspvm 	= '$toimaikatilrirow[kerayspvm]',
									toimaika		= '$toimaikatilrirow[toimaika]',
									keraysvko		= '',
									toimvko			= ''
									where yhtio = '$kukarow[yhtio]' and tunnus = '$tilausnumero'";
						$upresult = mysql_query($query) or pupe_error($query);
					}
				}
			}

			//tyhjennetään muuttujat
			$varasto 	= '';
			$edvarasto 	= '';

			// loopataan kaikki yhdestä tilauksesta syntyneet lähetteet
			// jos halutaan tilausvahvistus aluperäisestä tilauksesta
			if ($laskurow["tunnusnippu"] > 0 or $yhtiorow["tilausvahvistus_lahetys"] == 1) {
				//
				// LÄHETETÄÄN TILAUSVAHVISTUS
				//
				// Tässä haarassa tulostetaan tilausvahvistus KOKO tilauksesta

				if (strpos($laskurow['tilausvahvistus'], '4') !== FALSE) {
					$naytatvale = 4; // jos mellä on tilausvahvistuksessa nelonen, ei haluta nähdä alennuksia, näytetään tilausrivin hinta ja rivihinta
				}
				elseif (strpos($laskurow['tilausvahvistus'], '3') !== FALSE) {
					$naytatvale = 3; // jos mellä on tilausvahvistuksessa kolmonen, ei haluta nähdä hintoja, pelkästään kpl-määrät
				}
				elseif (strpos($laskurow['tilausvahvistus'], '2') !== FALSE) {
					$naytatvale = 2; // jos mellä on tilausvahvistuksessa kakkonen, ei haluta nähä aleja
				}
				else {
					$naytatvale = 1; // halutaan nähä alet
				}

				if (strpos($laskurow['tilausvahvistus'], 'E') !== FALSE) {
					require("tilausvahvistus-edi.inc");
				}

				if (strpos($laskurow['tilausvahvistus'], 'S') !== FALSE) {
					require("tilausvahvistus-email.inc");
				}

				if (strpos($laskurow['tilausvahvistus'], 'F') !== FALSE) {
					require("tilausvahvistus-fax.inc");
				}

				if (strpos($laskurow['tilausvahvistus'], 'O') !== FALSE) {
					require("tilausvahvistus-email.inc");
				}

				if (strpos($laskurow['tilausvahvistus'], 'U') !== FALSE) {
					require("tilausvahvistus-futursoft.inc");
				}

				if (strpos($laskurow['tilausvahvistus'], 'K') !== FALSE) {
					$query = "	SELECT komento
								FROM kirjoittimet
								WHERE yhtio = '$kukarow[yhtio]' and
								tunnus = '$kukarow[kirjoitin]'";
					$tilvares = mysql_query($query) or pupe_error($query);

					if ($tilvakir = mysql_fetch_assoc($tilvares)) {
						$komento["Tilausvahvistus"] = $tilvakir["komento"];

						$toim_save = $toim;
						if ($toim != "YLLAPITOSOPIMUS") $toim = "TILAUSVAHVISTUS";

						require("tulosta_tilausvahvistus_pdf.inc");
						$toim = $toim_save;
					}
				}

				if ($yhtiorow['tilausvahvistus_tallenna'] == 'K' and $tilausvahvistus_tallenna != '') {

					if (stristr(basename($tilausvahvistus_tallenna), ".pdf")) {
						$liite_tyyppi = "application/pdf";
					}
					else {
						$liite_tyyppi = "text/plain";
					}

					$file["name"] 		= basename($tilausvahvistus_tallenna);
					$file["type"] 		= $liite_tyyppi;
					$file["tmp_name"] 	= $tilausvahvistus_tallenna;
					$file["error"] 		= 0;
					$file["size"] 		= filesize($tilausvahvistus_tallenna);

					$_FILES['tilvah_liite'] = $file;

					$liitetied_id = tallenna_liite('tilvah_liite', 'lasku', $laskurow['tunnusnippu'], t('Myyntilaskun tilausvahvistus'));

					//poistetaan tmp file samantien kuleksimasta...
					system("rm -f ".basename($tilausvahvistus_tallenna));
				}
				///* Tässä katotaan onko lähetteellä tosiaan mitään kerättävää *///

				//	Ja rullataan kaikki tilaukset läpi
				foreach ($tilausnumerot as $tilausnumero) {
					//luetaan laskun tiedot uudestaan tässä, per splitti
					$query = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$tilausnumero'";
					$result = mysql_query($query) or pupe_error($query);
					$laskurow = mysql_fetch_assoc($result);

					$kutsuja = "tilaus-valmis.inc";

					require("tilaus-valmis-valitsetila.inc");
				}
			}
			else {
				foreach ($tilausnumerot as $tilausnumero) {
					//luetaan laskun tiedot uudestaan tässä, per splitti
					$query = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$tilausnumero'";
					$result = mysql_query($query) or pupe_error($query);
					$laskurow = mysql_fetch_assoc($result);

					// Tilausvahvistukset muuttavat tätä niin otetaan talteen
					$laskurow_tvorig = $laskurow;

					//
					// LÄHETETÄÄN TILAUSVAHVISTUS
					//
					// tulostetaan tässä, niin saadaan vahvistukseen koko tilaus, ennenkun sen splitaatan eri varastoihin

					if (strpos($laskurow['tilausvahvistus'], '4') !== FALSE) {
						$naytatvale = 4; // jos mellä on tilausvahvistuksessa nelonen, ei haluta nähdä alennuksia, näytetään tilausrivin hinta ja rivihinta
					}
					elseif (strpos($laskurow['tilausvahvistus'], '3') !== FALSE) {
						$naytatvale = 3; // jos mellä on tilausvahvistuksessa kolmonen, ei haluta nähdä hintoja, pelkästään kpl-määrät
					}
					elseif (strpos($laskurow['tilausvahvistus'], '2') !== FALSE) {
						$naytatvale = 2; // jos mellä on tilausvahvistuksessa kakkonen, ei haluta nähä aleja
					}
					else {
						$naytatvale = 1; // halutaan nähä alet
					}

					if (strpos($laskurow['tilausvahvistus'], 'E') !== FALSE) {
						require("tilausvahvistus-edi.inc");
					}

					if (strpos($laskurow['tilausvahvistus'], 'S') !== FALSE) {
						require("tilausvahvistus-email.inc");
					}

					if (strpos($laskurow['tilausvahvistus'], 'F') !== FALSE) {
						require("tilausvahvistus-fax.inc");
					}

					if (strpos($laskurow['tilausvahvistus'], 'O') !== FALSE) {
						require("tilausvahvistus-email.inc");
					}

					if (strpos($laskurow['tilausvahvistus'], 'U') !== FALSE) {
						require("tilausvahvistus-futursoft.inc");
					}

					if (strpos($laskurow['tilausvahvistus'], 'K') !== FALSE) {
						$query = "	SELECT komento
									FROM kirjoittimet
									WHERE yhtio = '$kukarow[yhtio]' and
									tunnus = '$kukarow[kirjoitin]'";
						$tilvares = mysql_query($query) or pupe_error($query);

						if ($tilvakir = mysql_fetch_assoc($tilvares)) {
							$komento["Tilausvahvistus"] = $tilvakir["komento"];

							$toim_save = $toim;
							if ($toim != "YLLAPITOSOPIMUS") $toim = "TILAUSVAHVISTUS";

							require("tulosta_tilausvahvistus_pdf.inc");
							$toim = $toim_save;
						}
					}

					if ($yhtiorow['tilausvahvistus_tallenna'] == 'K' and $tilausvahvistus_tallenna != '') {

						if (stristr(basename($tilausvahvistus_tallenna), ".pdf")) {
							$liite_tyyppi = "application/pdf";
						}
						else {
							$liite_tyyppi = "text/plain";
						}

						$file["name"] 		= basename($tilausvahvistus_tallenna);
						$file["type"] 		= $liite_tyyppi;
						$file["tmp_name"] 	= $tilausvahvistus_tallenna;
						$file["error"] 		= 0;
						$file["size"] 		= filesize($tilausvahvistus_tallenna);

						$_FILES['tilvah_liite'] = $file;

						$liitetied_id = tallenna_liite('tilvah_liite', 'lasku', $laskurow['tunnus'], t('Myyntilaskun tilausvahvistus'));

						//poistetaan tmp file samantien kuleksimasta...
						system("rm -f ".basename($tilausvahvistus_tallenna));
					}

					// takaisin käyttöön
					$laskurow = $laskurow_tvorig;

					///* Tässä katotaan onko lähetteellä tosiaan mitään kerättävää *///
					$kutsuja = "tilaus-valmis.inc";

					require("tilaus-valmis-valitsetila.inc");
				}
			}

			// katsotaan onko yhtään jt-riviä tilauksella
			// jos jt-rivejä löytyy ja yhtiön parametreissä jt_email ei ole tyhjä, lähetetään jälkitoimitus-sähköposti jt-email-kentässä olevaan sähköpostiosoitteeseen
			if ($yhtiorow['jt_email'] != '') {

				foreach ($tilausnumerot as $tilausnumero) {

					$query = "	SELECT lasku.nimi,
								lasku.nimitark,
								lasku.ytunnus,
								asiakas.asiakasnro,
								tilausrivi.tuoteno,
								tuote.nimitys,
								tuote.tuotemerkki,
								tuote.tahtituote,
								tuote.hinnastoon,
								tuote.status,
								tilausrivi.jt+tilausrivi.varattu kpl
								FROM lasku
								JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio AND
													tilausrivi.otunnus = lasku.tunnus AND
													tilausrivi.var = 'J' AND
													tilausrivi.tyyppi in ('L','G') AND
													tilausrivi.jt+tilausrivi.varattu > 0)
								JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
								JOIN asiakas ON (asiakas.yhtio = lasku.yhtio AND asiakas.tunnus = lasku.liitostunnus)
								WHERE lasku.yhtio = '$kukarow[yhtio]'
								AND lasku.tunnus = '$tilausnumero'";
					$jt_chk_res = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($jt_chk_res) > 0) {

						$kutsu = "";
						$subject = "";
						$content_body = "";

						$jt_chk_row = mysql_fetch_assoc($jt_chk_res);

						$header  = "From: <$yhtiorow[postittaja_email]>\n";
						$kutsu = "Jälkitoimitus";
						$subject = t("Tuotetta jätetty jälkitoimitukseen");
						$content_body = $yhtiorow['nimi']."\n".t("Asiakas", $kieli).": $jt_chk_row[nimi] $jt_chk_row[nimitark]\n\n";
						$content_body .= t("Ytunnus", $kieli).": $jt_chk_row[ytunnus]\n";
						$content_body .= t("Asiakasnumero", $kieli).": $jt_chk_row[asiakasnro]\n\n";

						mysql_data_seek($jt_chk_res, 0);

						while ($jt_chk_row = mysql_fetch_assoc($jt_chk_res)) {
							$content_body .= "$jt_chk_row[kpl] kpl ".t("jälkitoimituksen tuotenumerosta")." $jt_chk_row[tuoteno] ".t("tilauksella")." $tilausnumero\n\n";
							$content_body .= t("Nimitys").": $jt_chk_row[nimitys]\n";
							$content_body .= t("Tuotemerkki").": $jt_chk_row[tuotemerkki]\n";
							$content_body .= t("Tahtituote").": $jt_chk_row[tahtituote]\n";
							$content_body .= t("Hinnastoon").": $jt_chk_row[hinnastoon]\n";
							$content_body .= t("Status").": $jt_chk_row[status]\n\n\n";
						}

						mail($yhtiorow['jt_email'],  "$subject", $content_body, $header, "-f $yhtiorow[postittaja_email]");
						echo t("Lähetettiin jälkitoimitus-sähköposti")."...<br><br>";
					}
				}
			} #end if jt_email
		}
	} // end jos yksikin rivi kerätty else haara
}
//tyhjennetään että se ei kummittele uudella tilauksella
$hinta = '';

// tilaus ei enää kesken...
$query	= "UPDATE kuka set kesken=0 where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
$result = mysql_query($query) or pupe_error($query);

if ($silent == "") {
	echo $tilaus_valmis_ulos;
}

?>
