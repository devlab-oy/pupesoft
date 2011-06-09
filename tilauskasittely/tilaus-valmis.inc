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
$oldtur = pupe_query($query);

// Katsotaan onko asiakas myyntikiellossa
$query  = "	SELECT *
			FROM asiakas
			WHERE yhtio = '$kukarow[yhtio]'
			AND tunnus = '$laskurow[liitostunnus]'";
$tilausvalmis_asiakas_res = pupe_query($query);
$tilausvalmis_asiakas_row = mysql_fetch_assoc($tilausvalmis_asiakas_res);

if ($tilausvalmis_asiakas_row['myyntikielto'] == 'K') {
	$query = "	UPDATE lasku SET
				alatila = ''
				WHERE yhtio = '$kukarow[yhtio]'
				AND tunnus = '$laskurow[tunnus]'";
	$myyntikielto_res = pupe_query($query);

	echo "<font class='message'>",t("Asiakas on myyntikiellossa"),". ",t("Tilausta ei merkitä valmiiksi"),".</font><br/>";

}
elseif ($laskurow["tilaustyyppi"] == 'E') {
	//Ennakkotilaukset

	//tila on E ja alatila on A kun on ennakkotilaus
	$query  = "	UPDATE lasku
				SET tila = 'E',
				alatila = 'A'
				WHERE tunnus='$laskurow[tunnus]'
				and tila in ('N','L','E')
				and yhtio = '$kukarow[yhtio]'";
	$result = pupe_query($query);

	$laskurow["tila"] = "E";
	$laskurow["alatila"] = "A";

	// ennakkotilauksissa tyyppi on E ja rivit ei ole ennakkopoistossa
	$query = "	UPDATE tilausrivi
				SET tyyppi	= 'E'
				WHERE yhtio	= '$kukarow[yhtio]'
				and otunnus	= '$laskurow[tunnus]'
				and tyyppi in ('L','E')";
	$result = pupe_query($query);

	//
	// LÄHETETÄÄN TILAUSVAHVISTUS
	//
	// tulostetaan tässä, niin saadaan vahvistukseen koko tilaus, ennenkun sen splitaatan eri varastoihin
	$params_tilausvahvistus = array(
	'tee'						=> $tee,
	'toim'						=> $toim,
	'kieli'						=> $kieli,
	'komento'					=> $komento,
	'laskurow'					=> $laskurow,
	'naytetaanko_rivihinta'		=> $naytetaanko_rivihinta,
	'extranet_tilausvahvistus'	=> $extranet_tilausvahvistus,
	);

	laheta_tilausvahvistus($params_tilausvahvistus);
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
		$result = pupe_query($query);

		// tyyppi on 0 ja rivit ei ole ennakkopoistossa
		$query = "	UPDATE tilausrivi
					SET tyyppi	= '0'
					WHERE yhtio	= '$kukarow[yhtio]'
					and otunnus	= '$laskurow[tunnus]'
					and tyyppi in ('L','0')";
		$result = pupe_query($query);
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
	$result = pupe_query($query);

	// tarjouksissa tyyppi on T ja rivit ei ole ennakkopoistossa
	$query = "	UPDATE tilausrivi
				SET tyyppi	= 'T'
				WHERE yhtio	= '$kukarow[yhtio]'
				and otunnus	= '$laskurow[tunnus]'
				and tyyppi in ('L','T')";
	$result = pupe_query($query);
}
else {
	// Tässä on normaalit tilaukset

	// katotaan ollaanko valittu joku poikkeava kassalipas
	if ($kertakassa != '') {
		$query = "UPDATE lasku SET kassalipas = '$kertakassa' where yhtio='$kukarow[yhtio]' and tunnus = '$laskurow[tunnus]'";
		$result = pupe_query($query);

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
		$keres = pupe_query($query);

		if (mysql_num_rows($keres) > 0) {
			//Muutetaan tulostettavaksi
			$query = "UPDATE lasku SET alatila = '' where yhtio = '$kukarow[yhtio]' and tunnus = '$laskurow[tunnus]'";
			$result = pupe_query($query);
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
		$result = pupe_query($query);

		$laskurow['h1time'] = date('Y-m-d H:i:s');
		$laskurow['hyvak1'] = $kukarow["kuka"];
	}

	//päivitetään tilaukselle kuka tilauksen hyväksyi ja milloin
	if ($laskurow['tila'] == "N" and $laskurow['alatila'] == "F") {
		$query = "	UPDATE lasku
					SET h2time = now(),
					hyvak2 = '$kukarow[kuka]'
					WHERE yhtio = '$kukarow[yhtio]'
					AND tunnus = '$laskurow[tunnus]'";
		$result = pupe_query($query);

		$laskurow['h2time'] = date('Y-m-d H:i:s');
		$laskurow['hyvak2'] = $kukarow["kuka"];
	}

	// otetaan maksuehto tässä heti alussa selville.. käteinen ja itsetulostus muuttaa asioita
	$query = "	SELECT *
				FROM maksuehto
				WHERE yhtio = '$kukarow[yhtio]'
				AND tunnus = '$laskurow[maksuehto]'";
	$result = pupe_query($query);

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

	// Jos otsikko on kerätty tai pidemmällä
	if ($laskurow["tila"] == "L" and in_array($laskurow["alatila"], array('C','D','J','E'))) {
		$query = "	UPDATE tilausrivi
					SET keratty = '$kukarow[kuka]', kerattyaika = now()
					WHERE yhtio = '$kukarow[yhtio]'
					and otunnus = '$laskurow[tunnus]'
					and keratty = ''
					and var not in ('P','J','S')
					and tyyppi != 'D'";
		$result = pupe_query($query);

		// Laitetaan rivit toimitetuiksi jos otsikko sanoo toimitettu
		if ($laskurow["alatila"] == 'D' or $laskurow["alatila"] == 'J' or $laskurow["alatila"] == 'E') {
			$query = "	UPDATE tilausrivi
						SET toimitettu = '$kukarow[kuka]', toimitettuaika = now()
						WHERE yhtio = '$kukarow[yhtio]'
						and otunnus = '$laskurow[tunnus]'
						and keratty != ''
						and toimitettu = ''
						and var not in ('P','J','S')
						and tyyppi != 'D'";
			$result = pupe_query($query);
		}

		$tilaus_valmis_ulos .= "<font class='message'>".t("HUOM: Tilaus oli jo kerätty! Jos tarvitset keräyslistan, tulosta keräyslistan kopio").".</font><br><br>";

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
				$result = pupe_query($query);
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
				$result = pupe_query($query);

				if ($jaksotettu == 'X') {
					$query = "	UPDATE lasku SET
								tila = 'L',
								alatila = 'J',
								lahetepvm = now()
								WHERE tunnus = '$laskurow[tunnus]'
								AND yhtio = '$kukarow[yhtio]'
								AND alatila not IN ('J', 'D')";
					$result = pupe_query($query);

					$tilaus_valmis_ulos .= t("Maksusopimustilaus siirretty odottamaan loppulaskutusta").": $laskurow[tunnus] $laskurow[nimi]<br>\n";
				}
				else {
					$query  = "	UPDATE lasku set tila='L', alatila='D', lahetepvm=now()
								WHERE tunnus = '$laskurow[tunnus]'
								and yhtio = '$kukarow[yhtio]'";
					$result = pupe_query($query);
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
		$as_res = pupe_query($as_que);

		$rahtivapaa_alarajasumma = 0;

		if (mysql_num_rows($as_res) == 1) {
			$as_row = mysql_fetch_assoc($as_res);
			$rahtivapaa_alarajasumma = (float) $as_row["rahtivapaa_alarajasumma"];
		}
		else {
			$rahtivapaa_alarajasumma = (float) $yhtiorow["rahtivapaa_alarajasumma"];
		}

		$query_ale_lisa = generoi_alekentta('M');

		$tilausriviquery = "SELECT SUM(tilausrivi.hinta * (tilausrivi.varattu+tilausrivi.jt) * $query_ale_lisa) rivihinta
							FROM tilausrivi
							WHERE yhtio = '$kukarow[yhtio]'
							AND tilausrivi.otunnus = '$laskurow[tunnus]'";
		$rivihintaresult = pupe_query($tilausriviquery);
		$totaalirivi = mysql_fetch_assoc($rivihintaresult);

		// kaikkiyhteensä ja rahtivapaa_alarajasumma on verollisia jos myyntihinnat ovat verollisia, tai verottomia vice versa
		if ($totaalirivi["rivihinta"] > $rahtivapaa_alarajasumma and $rahtivapaa_alarajasumma != 0) {
			$tilaus_valmis_ulos .= t("Tilauksen arvo ylitti rahtivapaasumman! Merkattiin tilaus rahtivapaaksi.")."<br>";

			$query = "UPDATE lasku SET rahtivapaa = 'o' WHERE yhtio = '$kukarow[yhtio]' AND tunnus = '$laskurow[tunnus]'";
			$rahtivapres = pupe_query($query);

			$laskurow["rahtivapaa"] = 'o';
		}
		elseif ($yhtiorow["rahti_hinnoittelu"] == "o" or $yhtiorow["rahti_hinnoittelu"] == "P") {
			// jos rahtihinnoittelu tapahtuu tilauksen hinnan (alviton) perusteella
			// tai rahtihinnoittelu tapahtuu tilauksen painon mukaan automaattisesti niin tehdään se tässä

			// tähän kerätään tilausnumerot, joista lasketaan rahti
			$rahtimaksu_tunnukset = array();

			$query = "	SELECT *
						FROM toimitustapa
						WHERE yhtio = '$kukarow[yhtio]'
						AND selite = '$laskurow[toimitustapa]'";
			$result = pupe_query($query);
			$torow = mysql_fetch_assoc($result);

			$rahtinro_tuoteno_lisa = trim($torow['rahti_tuotenumero']) != '' ? "'{$yhtiorow['rahti_tuotenumero']}', '{$torow['rahti_tuotenumero']}'" : "'{$yhtiorow['rahti_tuotenumero']}'";

			// jos kyseessä on JT-tilaus, katsotaan pitääkö lisätä rahtikulua usealle tilaukselle
			if ($laskurow["clearing"] == "JT-TILAUS" and $yhtiorow["jt_rahti"] == "E") {
				// etsitään kaikki isätilaukset miltä nämä JT-tuotteet ovat tulleet ja katsotaan onko niillä jo rahtikulu (lisätään rahtikulu niille jolla ei ole)
				$query = "	SELECT DISTINCT vanha_otunnus
							FROM tilausrivi
							JOIN tilausrivin_lisatiedot USE INDEX (tilausrivitunnus) ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
							AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus)
							WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
							AND tilausrivi.otunnus = '$laskurow[tunnus]'";
				$step1re = pupe_query($query);

				while ($step1row = mysql_fetch_assoc($step1re)) {
					// etsitään istätilausten kaikki lapset
					$query = "	SELECT group_concat(DISTINCT tilausrivi.otunnus) uudet_otunnukset
								FROM tilausrivin_lisatiedot
								JOIN tilausrivi on (tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus)
								WHERE tilausrivin_lisatiedot.yhtio = '$kukarow[yhtio]'
								and tilausrivin_lisatiedot.vanha_otunnus = '$step1row[vanha_otunnus]'";
					$step2re = pupe_query($query);
					$step2row = mysql_fetch_assoc($step2re);

					// katsotaan onko lapsitilauksilla rahtikulu syötetty
					$query = "	SELECT tunnus
								FROM tilausrivi
								WHERE yhtio = '$kukarow[yhtio]'
								AND otunnus in ($step2row[uudet_otunnukset])
								AND tuoteno IN ($rahtinro_tuoteno_lisa)";
					$step3re = pupe_query($query);

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
						and tuoteno IN ($rahtinro_tuoteno_lisa)";
			$result = pupe_query($query);

			//jos tilauksella on jo rahtikulu niin ei lisätä sitä uudestaan
			if (mysql_num_rows($result) == 0) {

				// jos toimitustapana on joku nouto niin ei lisätä rahtikulua
				if ($torow["nouto"] == "") {

					// haetaan rahtimaksu
					$rahtihinta_array 		= hae_rahtimaksu($rahtimaksu_tunnukset);

					if (is_array($rahtihinta_array)) {
						$rahtihinta 		= $rahtihinta_array['rahtihinta'];
						$rahtihinta_ale 	= $rahtihinta_array['alennus'];
					}
					else {
						$rahtihinta = $rahtihinta_ale = 0;
					}

					// haetaan rahtituotteen tiedot
					$query = "	SELECT *
								FROM tuote
								WHERE yhtio = '$kukarow[yhtio]'
								AND tuoteno = '$yhtiorow[rahti_tuotenumero]'";
					$rhire = pupe_query($query);

					//lisätään rahtikulurivi vain jos rahtikulu on erisuuri kuin nolla, nollarivejä on ilmeisesti turha lisätä (ja rahtikulutuote löytyy)
					if ($rahtihinta != 0 and mysql_num_rows($rhire) == 1) {

						$trow  = mysql_fetch_assoc($rhire);

						$otunnus	 = $laskurow['tunnus'];
						$hinta		 = $rahtihinta;
						$trow["alv"] = $laskurow["alv"];
						$nimitys	 = $laskurow["toimitustapa"];
						$netto		 = $rahtihinta_ale != 0 ? '' : 'N';

						list($lis_hinta, $lis_netto, $lis_ale, $alehinta_alv, $alehinta_val) = alehinta($laskurow, $trow, '1', $netto, $hinta, $rahtihinta_ale);
						list($hinta, $alv) = alv($laskurow, $trow, $lis_hinta, '', $alehinta_alv);

						$query = "	INSERT into tilausrivi (laadittu, hinta, netto, varattu, tilkpl, otunnus, tuoteno, nimitys, yhtio, tyyppi, alv, kommentti, ale)
									values  (now(), '$hinta', '$netto', '1', '1', '$otunnus', '$trow[tuoteno]', '$nimitys', '$kukarow[yhtio]', 'L', '$alv', '', '$lis_ale')";
						$addtil = pupe_query($query);

						if ($rahtihinta_ale != 0) {
							$hinta *= (1 - ($rahtihinta_ale / 100));
						}

						$hinta = money_format('%.2n', $hinta);

						$tilaus_valmis_ulos .= t("Tilaukselle lisättiin rahtikulua").": $hinta $laskurow[valkoodi]<br>";
					}

					if ($yhtiorow["jalkivaatimus_tuotenumero"] == "") {
						$yhtiorow["jalkivaatimus_tuotenumero"] = $yhtiorow["rahti_tuotenumero"];
					}

					$query = "	SELECT tunnus
								FROM tilausrivi
								WHERE yhtio = '$kukarow[yhtio]'
								AND otunnus = '$laskurow[tunnus]'
								AND tuoteno = '$yhtiorow[jalkivaatimus_tuotenumero]'";
					$result = pupe_query($query);

					$query = "	SELECT *
								FROM tuote
								WHERE yhtio = '$kukarow[yhtio]'
								AND tuoteno = '$yhtiorow[jalkivaatimus_tuotenumero]'";
					$rhire = pupe_query($query);

					// lisätään jälkivaatimuskulu jos sitä ei ole tilauksella ja tuote löytyy
					if (mysql_num_rows($rhire) == 1 and mysql_num_rows($result) == 0 and $torow["jvkulu"] != 0 and $maksuehtorow["jv"] != "") {

						$trow = mysql_fetch_assoc($rhire);

						$otunnus	 = $laskurow['tunnus'];
						$hinta		 = $torow["jvkulu"]; // jvkulu
						$nimitys	 = t("Jälkivaatimuskulu");

						list($hinta, $alv) = alv($laskurow, $trow, $hinta, '', '');

						$query  = "	INSERT into tilausrivi (laadittu, hinta, netto, varattu, tilkpl, otunnus, tuoteno, nimitys, yhtio, tyyppi, alv, kommentti)
									values (now(), '$hinta', '$netto', '1', '1', '$otunnus', '$trow[tuoteno]', '$nimitys', '$kukarow[yhtio]', 'L', '$alv', '')";
						$addtil = pupe_query($query);

						$hinta = money_format('%.2n', $hinta);

						$tilaus_valmis_ulos .= t("Tilaukselle lisättiin jälkivaatimuskulua").": $hinta $laskurow[valkoodi]<br>";
					}
				}
			}

		}
		else {

			$query = "	SELECT *
						FROM toimitustapa
						WHERE yhtio = '$kukarow[yhtio]'
						AND selite = '$laskurow[toimitustapa]'";
			$result = pupe_query($query);
			$torow = mysql_fetch_assoc($result);

			$rahtinro_tuoteno_lisa = trim($torow['rahti_tuotenumero']) != '' ? "'{$yhtiorow['rahti_tuotenumero']}', '{$torow['rahti_tuotenumero']}'" : "'{$yhtiorow['rahti_tuotenumero']}'";

			// meillä on rahdituksessa syötettävään painoon perustuva rahtimaksu
			// katsotaan onko rahtituotenumero syötetty jo käsin
			$query = "	SELECT tunnus
						from tilausrivi
						where yhtio = '$kukarow[yhtio]'
						and otunnus = '$laskurow[tunnus]'
						and tuoteno IN ({$rahtinro_tuoteno_lisa})";
			$result = pupe_query($query);

			//jos tilauksella on jo rahtikulu niin merkataan se rahtivapaaksi, ettei raihtikulu mene uudestaan laskutuksen yhteydessä!
			if (mysql_num_rows($result) > 0) {
				$tilaus_valmis_ulos .= t("Tilauksella oli käsin syötetty rahtikulu! Merkattiin tilaus rahtivapaaksi, ettei kulua lisätä enää automaattisesti laskutuksessa.")."<br>";
				$query = "UPDATE lasku SET rahtivapaa = 'o' WHERE yhtio = '$kukarow[yhtio]' AND tunnus = '$laskurow[tunnus]'";
				$rahtivapres = pupe_query($query);
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
			$result = pupe_query($query);

			$query = "	UPDATE tilausrivi
						SET toimitettu = '$kukarow[kuka]', toimitettuaika = now()
						WHERE otunnus 	= '$laskurow[tunnus]'
						and var not in ('P','J')
						and yhtio 		= '$kukarow[yhtio]'
						and keratty    != ''
						and toimitettu  = ''
						and tyyppi     != 'D'";
			$result = pupe_query($query);

			$query = "	UPDATE lasku
						SET tila = 'L', alatila = 'D'
						WHERE tunnus = '$laskurow[tunnus]'
						and yhtio = '$kukarow[yhtio]'";
			$result = pupe_query($query);

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
				$params_tilausvahvistus = array(
				'tee'						=> $tee,
				'toim'						=> $toim,
				'kieli'						=> $kieli,
				'komento'					=> $komento,
				'laskurow'					=> $laskurow,
				'naytetaanko_rivihinta'		=> $naytetaanko_rivihinta,
				'extranet_tilausvahvistus'	=> $extranet_tilausvahvistus,
				);

				laheta_tilausvahvistus($params_tilausvahvistus);
			}

			// Laitetaan tässäkin haarassa Intrastat-tiedot kuntoon, muuten laskujen ketjutus ei onnaa. (varasto = 0 ,eli mennään yhtiön oletusmaalla)
			$laskurow = palauta_intrastat_tiedot($laskurow, 0, TRUE);

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
				$result = pupe_query($query);

				if ($laskurow["vienti"] != '') {
					$alat = "B";
				}
				else {
					$alat = "C";
				}

				$query  = "	UPDATE lasku set tila='L', alatila='$alat', lahetepvm=now()
							WHERE tunnus='$laskurow[tunnus]'
							and yhtio='$kukarow[yhtio]'";
				$result = pupe_query($query);
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

				if ($jaksotettu == 'X') {
					$query = "	UPDATE lasku SET
								tila = 'L',
								alatila = 'J',
								lahetepvm = now()
								WHERE tunnus = '$laskurow[tunnus]'
								AND yhtio = '$kukarow[yhtio]'
								AND alatila not IN ('J', 'D')";
					$result = pupe_query($query);

					$tilaus_valmis_ulos .= t("Maksusopimustilaus siirretty odottamaan loppulaskutusta").": $laskurow[tunnus] $laskurow[nimi]<br>\n";
				}
				else {
					$query  = "	UPDATE lasku set tila='L', alatila='D', lahetepvm=now()
								WHERE tunnus = '$laskurow[tunnus]'
								and yhtio = '$kukarow[yhtio]'";
					$result = pupe_query($query);
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
			$query = "SELECT * from toimitustapa where yhtio='$kukarow[yhtio]' and selite='$laskurow[toimitustapa]'";
			$result = pupe_query($query);
			$totarowx = mysql_fetch_assoc($result);

			// Maksuehdon jv tieto tarvitaan
			$query = "SELECT jv from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
			$result = pupe_query($query);
			$maehrowx = mysql_fetch_assoc($result);

			if ($yhtiorow["splittauskielto"] == "K") {
				$sorttaus = "tilausrivi.kerayspvm, sorttauskentta";
			}
			else {
				$sorttaus = "sorttauskentta";
			}

			//haetaan kaikkien tilausrivien varastopaikat
			$query = "	SELECT tilausrivi.otunnus, tilausrivi.tunnus, tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso,  tilausrivi.kerayspvm, tilausrivi.toimaika, tilausrivin_lisatiedot.erikoistoimitus_myynti,
						concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0')) sorttauskentta
						FROM tilausrivi
						JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus)
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and tilausrivi.otunnus = '$laskurow[tunnus]'
						and tilausrivi.hyllyalue != ''
						and tilausrivi.varattu+tilausrivi.kpl > 0
						and tilausrivi.var not in ('J','P')
						ORDER BY $sorttaus";
			$result = pupe_query($query);

			//tässä muuttujassa on sit kaikki tunnukset splitin jälkeen
			$tilausnumerot	 = array();
			$tilausnumerot[] = $laskurow["tunnus"];

			//mihin varastoon lähete kuuluu. aluksi x
			$varasto = $edvarasto = $kerayspvm = $edkerayspvm = "x";
			$tulostusalue = $edtulostusalue = "x";

			//jos tilaus splittaantuu, niin laitetaan muihin otsikoihin ensimmäisen tilauksen tunnus vanhaksitunnukseksi
			$temp_laskutunnus = $laskurow['tunnus'];

			$varasto_chk_array = array();

			while ($row = mysql_fetch_assoc($result)) {

				// Mihin varastoon
				$varasto = kuuluukovarastoon($row["hyllyalue"], $row["hyllynro"]);

				// Mikä tulostusalue
				$tulostusalue = onkotulostusalueita($row["hyllyalue"], $row["hyllynro"], $varasto);

				// Intrastat-tiedot
				$laskurow = palauta_intrastat_tiedot($laskurow, $varasto);

				// varasto vaihtuu, splitataan tilaus, paitsi jos kyseessä on jv-tilaus jonka rahtikirja tulostetaan heti, tai jos spliauskielto on trigattu
				if (((($varasto != $edvarasto and $edvarasto != 'x') or ($tulostusalue != $edtulostusalue and $edtulostusalue != 'x' and $edtulostusalue != '')) and ($maehrowx["jv"] == '' or $totarowx["tulostustapa"] != 'H') and $laskurow["splittauskielto"] != "E") or ($row["kerayspvm"] != $edkerayspvm and $edkerayspvm != 'x' and $yhtiorow["splittauskielto"] == "K")) {

					// Meidän on päivitettävä tunnusnippu
					if ($yhtiorow["splittauskielto"] == "K" and $laskurow["tunnusnippu"] == 0) {
						$query = "UPDATE lasku set tunnusnippu = tunnus where yhtio = '$kukarow[yhtio]' and tunnus = '$laskurow[tunnus]' and tunnusnippu = 0";
						$upresult = pupe_query($query);
						$laskurow["tunnusnippu"] = $laskurow["tunnus"];
					}

					if (!is_resource($laskusplitres)) {
						// Jotta saadaa lasku kopsattua kivasti jos se splittaantuu
						$query = "	SELECT *
									FROM lasku
									WHERE yhtio = '$kukarow[yhtio]'
									and tunnus = '$laskurow[tunnus]'";
						$laskusplitres = pupe_query($query);
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
							case 'rahtivapaa':
								if ($row['erikoistoimitus_myynti'] == 1 and $varaston_row['erikoistoimitus_toimitustapa'] != '') {
									$values .= ", 'o'";
									break;
								}
							case 'toimitustapa':
								if ($row['erikoistoimitus_myynti'] == 1 and $varaston_row['erikoistoimitus_toimitustapa'] != '') {
									$values .= ", '$varaston_row[erikoistoimitus_toimitustapa]'";
									break;
								}
							default:
								$values .= ", '".$laskurow[$fieldname]."'";
						}
					}

					$kysely  = "INSERT into lasku ($fields) VALUES ($values)";
					$uusires = pupe_query($kysely);

					$query = "	SELECT *
								FROM laskun_lisatiedot
								WHERE yhtio = '$kukarow[yhtio]'
								AND otunnus = '$laskurow[tunnus]'";
					$lisatiedot_result = pupe_query($query);
					$lisatiedot_row = mysql_fetch_array($lisatiedot_result);

					$laskurow['tunnus'] = (string) mysql_insert_id();
					$tilausnumerot[] = $laskurow["tunnus"];

					$query = "	SELECT ulkoinen_jarjestelma
								FROM varastopaikat
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tunnus = '{$varasto}'";
					$ulkoinen_jarjestelma_res = pupe_query($query);
					$ulkoinen_jarjestelma_row = mysql_fetch_assoc($ulkoinen_jarjestelma_res);

					$varasto_chk_array[$laskurow['tunnus']] = $ulkoinen_jarjestelma_row['ulkoinen_jarjestelma'];

					$fields = "yhtio";
					$values = "'$kukarow[yhtio]'";

					// Ei monisteta tunnusta
					for ($i = 1; $i < mysql_num_fields($lisatiedot_result) - 1; $i++) {

						$fieldname = mysql_field_name($lisatiedot_result, $i);

						$fields .= ", ".$fieldname;

						switch ($fieldname) {
							case 'otunnus':
								$values .= ", '$laskurow[tunnus]'";
								break;
							default:
								$values .= ", '".$lisatiedot_row[$fieldname]."'";
						}
					}

					$query = "INSERT into laskun_lisatiedot ($fields) VALUES ($values)";
					$lisatiedot_result = pupe_query($query);
				}
				else {

					$query = "	SELECT ulkoinen_jarjestelma
								FROM varastopaikat
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tunnus = '{$varasto}'";
					$ulkoinen_jarjestelma_res = pupe_query($query);
					$ulkoinen_jarjestelma_row = mysql_fetch_assoc($ulkoinen_jarjestelma_res);

					$varasto_chk_array[$laskurow['tunnus']] = $ulkoinen_jarjestelma_row['ulkoinen_jarjestelma'];

					$toimitustapalisa = ($row['erikoistoimitus_myynti'] == 1 and $varaston_row['erikoistoimitus_toimitustapa'] != '') ? ", toimitustapa = '$varaston_row[erikoistoimitus_toimitustapa]', rahtivapaa = 'o' " : '';

					//päivitetään laskulle tieto mihin varastoon se kuuluu
					$query = "	UPDATE lasku set
								varasto 							= '$varasto',
								tulostusalue						= '$tulostusalue',
								ultilno 							= '$laskurow[ultilno]',
								aktiivinen_kuljetus 				= '$laskurow[aktiivinen_kuljetus]',
								aktiivinen_kuljetus_kansallisuus	= '$laskurow[aktiivinen_kuljetus_kansallisuus]',
								bruttopaino 						= '$laskurow[bruttopaino]',
								kauppatapahtuman_luonne 			= '$laskurow[kauppatapahtuman_luonne]',
								kontti								= '$laskurow[kontti]',
								kuljetusmuoto						= '$laskurow[kuljetusmuoto]',
								lisattava_era 						= '$laskurow[lisattava_era]',
								maa_lahetys							= '$laskurow[maa_lahetys]',
								maa_maara							= '$laskurow[maa_maara]',
								poistumistoimipaikka 				= '$laskurow[poistumistoimipaikka]',
								poistumistoimipaikka_koodi 			= '$laskurow[poistumistoimipaikka_koodi]',
								sisamaan_kuljetus					= '$laskurow[sisamaan_kuljetus]',
								sisamaan_kuljetusmuoto  			= '$laskurow[sisamaan_kuljetusmuoto]',
								sisamaan_kuljetus_kansallisuus		= '$laskurow[sisamaan_kuljetus_kansallisuus]',
								vahennettava_era 					= '$laskurow[vahennettava_era]'
								$toimitustapalisa
								where yhtio = '$kukarow[yhtio]'
								and tunnus  = '$laskurow[tunnus]'";
					$varresult = pupe_query($query);

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
					$ohno  = pupe_query($query);
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
					$upresult = pupe_query($query);

					$tilauksia++;
				}
			}

			// Laitetaan splittien toim ja kerpvm ät kuntoon / viikkojutut on pakko nollata splitille
			if ($yhtiorow["splittauskielto"] == "K" and count($tilausnumerot) > 1) {
				foreach ($tilausnumerot as $tilausnumero) {
					$query = "	SELECT min(kerayspvm) kerayspvm, min(toimaika) toimaika
					 			from tilausrivi
								where yhtio='$kukarow[yhtio]' and otunnus='$tilausnumero'";
					$result = pupe_query($query);
					$toimaikatilrirow = mysql_fetch_assoc($result);

					$query = "	SELECT kerayspvm, toimaika
								from lasku
								where yhtio='$kukarow[yhtio]' and tunnus='$tilausnumero'";
					$result = pupe_query($query);
					$toimaikalaskurow = mysql_fetch_assoc($result);

					if ($toimaikatilrirow["kerayspvm"] != substr($toimaikalaskurow["kerayspvm"],0,10) or $toimaikatilrirow["toimaika"] != $toimaikalaskurow["toimaika"]) {
						$query = "	UPDATE lasku
									set kerayspvm 	= '$toimaikatilrirow[kerayspvm]',
									toimaika		= '$toimaikatilrirow[toimaika]',
									keraysvko		= '',
									toimvko			= ''
									where yhtio = '$kukarow[yhtio]' and tunnus = '$tilausnumero'";
						$upresult = pupe_query($query);
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
				$params_tilausvahvistus = array(
				'tee'						=> $tee,
				'toim'						=> $toim,
				'kieli'						=> $kieli,
				'komento'					=> $komento,
				'laskurow'					=> $laskurow,
				'naytetaanko_rivihinta'		=> $naytetaanko_rivihinta,
				'extranet_tilausvahvistus'	=> $extranet_tilausvahvistus,
				);

				laheta_tilausvahvistus($params_tilausvahvistus);

				///* Tässä katotaan onko lähetteellä tosiaan mitään kerättävää *///

				//	Ja rullataan kaikki tilaukset läpi
				foreach ($tilausnumerot as $tilausnumero) {
					//luetaan laskun tiedot uudestaan tässä, per splitti
					$query = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$tilausnumero'";
					$result = pupe_query($query);
					$laskurow = mysql_fetch_assoc($result);

					$kutsuja = "tilaus-valmis.inc";

					require("tilaus-valmis-valitsetila.inc");
				}
			}
			else {
				foreach ($tilausnumerot as $tilausnumero) {
					//luetaan laskun tiedot uudestaan tässä, per splitti
					$query = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$tilausnumero'";
					$result = pupe_query($query);
					$laskurow = mysql_fetch_assoc($result);

					// Tilausvahvistukset muuttavat tätä niin otetaan talteen
					$laskurow_tvorig = $laskurow;

					//
					// LÄHETETÄÄN TILAUSVAHVISTUS
					//
					// tulostetaan tässä, niin saadaan vahvistukseen koko tilaus, ennenkun sen splitaatan eri varastoihin
					$params_tilausvahvistus = array(
					'tee'						=> $tee,
					'toim'						=> $toim,
					'kieli'						=> $kieli,
					'komento'					=> $komento,
					'laskurow'					=> $laskurow,
					'naytetaanko_rivihinta'		=> $naytetaanko_rivihinta,
					'extranet_tilausvahvistus'	=> $extranet_tilausvahvistus,
					);

					laheta_tilausvahvistus($params_tilausvahvistus);

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
					$jt_chk_res = pupe_query($query);

					if (mysql_num_rows($jt_chk_res) > 0) {

						$kutsu = "";
						$subject = "";
						$content_body = "";

						$jt_chk_row = mysql_fetch_assoc($jt_chk_res);

						$header  = "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n";
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

						mail($yhtiorow['jt_email'], mb_encode_mimeheader($subject, "ISO-8859-1", "Q"), $content_body, $header, "-f $yhtiorow[postittaja_email]");
						echo t("Lähetettiin jälkitoimitus-sähköposti")."...<br><br>";
					}
				}
			} #end if jt_email

			// Örum-keissi
			if ($kukarow['yhtio'] == 'artr') {
				// katsotaan onko vielä JT-tilauksia Unikko-järjestelmään
				foreach ($tilausnumerot as $til_nro_x) {

					$query = "	SELECT lasku.*, tilausrivi.hyllyalue, tilausrivi.hyllynro
								FROM lasku
								JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio AND
													tilausrivi.otunnus = lasku.tunnus AND
													tilausrivi.var = 'J' AND
													tilausrivi.tyyppi = 'L' AND
													tilausrivi.jt+tilausrivi.varattu > 0)
								WHERE lasku.yhtio = '{$kukarow['yhtio']}'
								AND lasku.tunnus = '{$til_nro_x}'";
					$jt_chk_res = pupe_query($query);

					if (mysql_num_rows($jt_chk_res) > 0) {
						$jt_chk_row = mysql_fetch_assoc($jt_chk_res);

						// Mihin varastoon
						$varasto = kuuluukovarastoon($jt_chk_row["hyllyalue"], $jt_chk_row["hyllynro"]);

						$query = "	SELECT ulkoinen_jarjestelma
									FROM varastopaikat
									WHERE yhtio = '{$kukarow['yhtio']}'
									AND tunnus = '{$varasto}'";
						$ulkoinen_jarjestelma_res = pupe_query($query);
						$ulkoinen_jarjestelma_row = mysql_fetch_assoc($ulkoinen_jarjestelma_res);

						$varasto_chk_array[$til_nro_x] = $ulkoinen_jarjestelma_row['ulkoinen_jarjestelma'];
					}
				}
			}

			if (in_array("U", $varasto_chk_array) and (in_array($toim, array("PIKATILAUS", "RIVISYOTTO", "EXTRANET")) or $tullaanko_tilausten_sisaanluvusta == 'EDITILAUS' or ($toim == "ENNAKKO" and $mista == "jtselaus"))) {

				foreach ($tilausnumerot as $til_nro) {

					// Jos tilauksista yksikin menee "Unikko"-järjestelmään, päivitetään kaikki tilaukset rahtivapaiksi
					$query = "UPDATE lasku SET rahtivapaa = 'X' WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$til_nro}'";
					$rahtivapaa_upd_res = pupe_query($query);

					$query = "SELECT * FROM lasku WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$til_nro}'";
					$laskurow_edi_res = pupe_query($query);
					$laskurow_edi = mysql_fetch_assoc($laskurow_edi_res);

					// jos varaston ulkoinen järjestelmä on Unikko
					if ($varasto_chk_array[$til_nro] == 'U') {

						if ($laskurow_edi['tilaustyyppi'] == 7) {
							$pupesoft_tilaustyyppi = 7;
						}
						elseif ($laskurow_edi['tilaustyyppi'] == 2) {
							$pupesoft_tilaustyyppi = 2;
						}
						elseif ($laskurow_edi['tilaustyyppi'] == 8) {
							$pupesoft_tilaustyyppi = 8;
						}
						else {
							$pupesoft_tilaustyyppi = 1;
						}

						$query = "SELECT * FROM asiakas WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$laskurow_edi['liitostunnus']}'";
						$asiakas_chk_res = pupe_query($query);
						$asiakas_chk_row = mysql_fetch_assoc($asiakas_chk_res);

						$query = "	SELECT *
									FROM laskun_lisatiedot
									WHERE yhtio = '{$kukarow['yhtio']}'
									AND otunnus = '{$laskurow_edi['tunnus']}'";
						$lisatiedot_result = pupe_query($query);
						$laskun_lisatiedot = mysql_fetch_assoc($lisatiedot_result);

						$params = array(
							'laskurow' => $laskurow_edi,
							'lisatiedot' => $laskun_lisatiedot,
							'asiakasrow' => $asiakas_chk_row,
							'tilaustyyppi' => $pupesoft_tilaustyyppi,
							'toimitustapa' => $totarowx['herminator'],
							'toim' => $toim,
						);

						if (file_exists("editilaus_out_futur.inc")) {
							require("editilaus_out_futur.inc");
						}
						else if (file_exists("pupesoft/editilaus_out_futur.inc")) {
							require("pupesoft/editilaus_out_futur.inc");
						}
						else {
							require("tilauskasittely/editilaus_out_futur.inc");
						}

						if (editilaus_out_futur($params) === 0) {

							$query = "	UPDATE lasku SET
										tila = 'L',
										alatila = 'A',
										lahetepvm = now()
										WHERE yhtio = '$kukarow[yhtio]'
										AND tunnus = '{$til_nro}'";
							$upd_res = pupe_query($query);
						}
					}
				}
			}
		}
	} // end jos yksikin rivi kerätty else haara
}
//tyhjennetään että se ei kummittele uudella tilauksella
$hinta = '';

// tilaus ei enää kesken...
$query	= "UPDATE kuka set kesken=0 where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
$result = pupe_query($query);

if (!isset($silent) or $silent == "") {
	echo $tilaus_valmis_ulos;
}

?>