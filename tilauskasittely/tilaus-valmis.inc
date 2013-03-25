<?php

// tarvitaan $kukarow[yhtio], $kukarow[kesken], $laskurow array, $yhtiorow array
$tilaus_valmis_ulos = "";
$ohjausmerkkilisa 	= "";
$luottorajavirhe 	= "";

///* Reload ja back-nappulatsekki *///
if ($kukarow["kesken"] == '' or $kukarow["kesken"] == '0') {
	echo "<font class='error'> ".t("Taisit painaa takaisin tai päivitä nappia. Näin ei saa tehdä")."! </font>";
	exit;
}

//jos tilaustyyppi on 2 ja ohjausmerkki on tyhjää, niin pistetään ohjausmerkiksi tilaustyypin selkokielinen nimi
if ($laskurow['tilaustyyppi'] == "2" AND trim($laskurow['ohjausmerkki']) == "") {
	$ohjausmerkkilisa = ", ohjausmerkki = 'Varastotäydennys'";
	$laskurow["ohjausmerkki"] = "Varastotäydennys";
}

// päivitetään vanhatunnus laskulle
$query  = "	UPDATE lasku SET vanhatunnus=tunnus $ohjausmerkkilisa
			WHERE yhtio='$kukarow[yhtio]'
			AND tunnus='$laskurow[tunnus]'
			AND vanhatunnus=0";
$oldtur = pupe_query($query);

$query = "	SELECT tarkenne
			FROM asiakkaan_avainsanat
			WHERE yhtio 	 = '{$kukarow['yhtio']}'
			AND laji 		 = 'OHJAUSMERKKI'
			AND liitostunnus = '{$laskurow['liitostunnus']}'
			AND avainsana 	 = '{$laskurow['toimitustapa']}'";
$ohjausmerkki_check_res = pupe_query($query);
$ohjausmerkki_check_row = mysql_fetch_assoc($ohjausmerkki_check_res);

if ($ohjausmerkki_check_row['tarkenne'] != "") {

	// Tarkistetaan löytyykö kyseinen ohjausmerkkiteksti otsikolta
	// Ei haluta lisätä tekstiä kahteen kertaan
	if (stripos($laskurow['ohjausmerkki'], $ohjausmerkki_check_row['tarkenne']) === FALSE) {

		if ($laskurow['ohjausmerkki'] != "") {
			$updlisa = " ohjausmerkki = CONCAT('{$ohjausmerkki_check_row['tarkenne']}', ' / ', ohjausmerkki)";
			$laskurow['ohjausmerkki'] = "{$ohjausmerkki_check_row['tarkenne']} / {$laskurow['ohjausmerkki']}";
		}
		else {
			$updlisa = " ohjausmerkki = '{$ohjausmerkki_check_row['tarkenne']}'";
			$laskurow['ohjausmerkki'] = $ohjausmerkki_check_row['tarkenne'];
		}

		$query = "	UPDATE lasku SET
					{$updlisa}
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tunnus  = '{$laskurow['tunnus']}'";
		$updres = pupe_query($query);
	}
}

// päivitetään myös muuttuja
$laskurow["vanhatunnus"] = $laskurow["tunnus"];

// Katsotaan onko asiakas myyntikiellossa
$query  = "	SELECT *
			FROM asiakas
			WHERE yhtio = '$kukarow[yhtio]'
			AND tunnus = '$laskurow[liitostunnus]'";
$tilausvalmis_asiakas_res = pupe_query($query);
$tilausvalmis_asiakas_row = mysql_fetch_assoc($tilausvalmis_asiakas_res);

// Myynti esteteään jos luottoraja on ylitetty (K - kaikki estetään, E -  kaikki muu ku myyjien tekemä estetään)
// samma på erapaivan_ylityksen_raja
$luottoraja_chk = (($yhtiorow["luottorajan_ylitys"] == "K"             or ($yhtiorow["luottorajan_ylitys"] == "E"             and ($kukarow["extranet"] != "" or $tilausvalmiskutsuja != "TILAUSMYYNTI"))) and $tilausvalmis_asiakas_row['luottoraja'] != 0) ? 1 : 0;
$erapaiva_chk   =  ($yhtiorow["erapaivan_ylityksen_toimenpide"] == "K" or ($yhtiorow["erapaivan_ylityksen_toimenpide"] == "E" and ($kukarow["extranet"] != "" or $tilausvalmiskutsuja != "TILAUSMYYNTI"))) ? 1 : 0;

if ($luottoraja_chk or $erapaiva_chk) {

	// Parametrejä saatanat.php:lle
	$sytunnus 	 	 = $laskurow['ytunnus'];
	$sliitostunnus	 = $laskurow['liitostunnus'];
	$eiliittymaa 	 = "ON";
	$luottorajavirhe = "";
	$jvvirhe 		 = "";
	$ylivito 		 = 0;
	$trattavirhe 	 = "";
	$laji 			 = "MA";
	$grouppaus       = ($yhtiorow["myyntitilaus_saatavat"] == "Y") ? "ytunnus" : "";

	ob_start();

	if ($toim != "EXTRANET") {
		require ("raportit/saatanat.php");
	}
	else {
		require ("saatanat.php");
	}

	ob_end_clean();

    if ($luottoraja_chk == 0) { $luottorajavirhe = ""; }
    if ($erapaiva_chk   == 0) { $ylivito         = 0; }
}

// otetaan maksuehto tässä heti alussa selville.. käteinen ja itsetulostus muuttaa asioita
$query = "	SELECT *
			FROM maksuehto
			WHERE yhtio = '{$kukarow['yhtio']}'
			AND tunnus = '{$laskurow['maksuehto']}'";
$maksuehto_result = pupe_query($query);
$maksuehtorow = mysql_fetch_assoc($maksuehto_result);

// Jos ennakkolaskun automatisointi on päällä (asiakas > yhtiön oletus) ja maksuehtona on "osamaksu", laitetaan tilaus holdiin
if ((!isset($vapauta_tilaus_keraykseen) or !$vapauta_tilaus_keraykseen)
	and $maksuehtorow['jaksotettu'] != ''
	and $laskurow['tila'] == 'N'
	and ($laskurow['alatila'] == '' or $laskurow['alatila'] == 'B')
	and ($tilausvalmis_asiakas_row['maksusopimus_toimitus'] == 'X' or ($tilausvalmis_asiakas_row['maksusopimus_toimitus'] == '' and $yhtiorow['maksusopimus_toimitus'] == 'X'))) {

	if ($laskurow['alatila'] == '') {
		$query = "	UPDATE lasku SET
					alatila = 'B'
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tunnus = '{$laskurow['tunnus']}'
					AND tila = 'N'";
		$myyntikielto_res = pupe_query($query);

		echo "<font class='error'>",t("Tilaus laitetaan odottamaan, kunnes ennakkolaskut ovat maksettu"),".</font><br/><br/>";
	}

	// Laitetaan lopetus tyhjäksi, niin ei refreshata ruutua jos tultiin muokkauksesta tms.
	$lopetus = "";
}
elseif ($luottorajavirhe != '' or $ylivito > 0) {

	$toimitustavan_lahto_update = "";
	
	if (($kutsuja == "EDITILAUS" or $kukarow["extranet"] != "") and $yhtiorow["luottorajan_ylitys"] == "E") {
		$alatila = "F";
	}
	else {
		//laitetaan tilaan Odottaa asiakkaan suoritusta
		$alatila = "G";
		//nollataan toimitustava_lahto
		$toimitustavan_lahto_update = ",toimitustavan_lahto = 0, toimitustavan_lahto_siirto = 0";
	}

	$query = "	UPDATE lasku SET
				alatila = '$alatila'
				{$toimitustavan_lahto_update}
				WHERE yhtio = '$kukarow[yhtio]'
				AND tunnus = '$laskurow[tunnus]'";
	$myyntikielto_res = pupe_query($query);

    $errortext = "";

    if ($luottorajavirhe != "") {
        $errortext = t("Asiakkaan luottoraja on ylitetty") . ". ";
    }

    if ($ylivito > 0) {
        $errortext .= t("Asiakkaalla on yli %s pv sitten erääntyneitä laskuja", $kukarow['kieli'], $yhtiorow['erapaivan_ylityksen_raja']) . ". ";
    }

	echo "<font class='error'>" . $errortext . t("Tilausta ei merkitä valmiiksi"),".</font><br/><br/>";

	// Laitetaan lopetus tyhjäksi, niin ei refreshata ruutua jos tultiin muokkauksesta tms.
	$lopetus = "";
}
elseif ($tilausvalmis_asiakas_row['myyntikielto'] == 'K') {
	$query = "	UPDATE lasku SET
				alatila = ''
				WHERE yhtio = '$kukarow[yhtio]'
				AND tunnus = '$laskurow[tunnus]'";
	$myyntikielto_res = pupe_query($query);

	echo "<font class='error'>",t("Asiakas on myyntikiellossa"),". ",t("Tilausta ei merkitä valmiiksi"),".</font><br/>";

	// Laitetaan lopetus tyhjäksi, niin ei refreshata ruutua jos tultiin muokkauksesta tms.
	$lopetus = "";
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

	if (trim($laskurow['tilausvahvistus']) != "") {
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

	// Jos meillä ei ole keräysprioriteettia tällä tilauksella,
	// katsotaan onko tälle prioriteetti tuoteryhmän takaa.
	if ($laskurow["hyvaksynnanmuutos"] == "") {

		$keraysprioriteetti_query = "	SELECT avainsana.selitetark_4
										FROM tilausrivi
										JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio
											AND tuote.tuoteno = tilausrivi.tuoteno)
										JOIN avainsana ON (avainsana.yhtio = tilausrivi.yhtio
											AND avainsana.laji = 'TRY'
											AND avainsana.selitetark_4 != ''
											AND avainsana.selite = tuote.try)
										WHERE tilausrivi.yhtio = '{$kukarow["yhtio"]}'
										AND tilausrivi.otunnus = '{$laskurow["tunnus"]}'
										AND tilausrivi.var NOT IN ('P','J')
										ORDER by avainsana.selitetark_4 ASC
										LIMIT 1";
		$keraysprioriteetti_res = pupe_query($keraysprioriteetti_query);

		// päivitetään prioriteetti laskulle
		if (mysql_num_rows($keraysprioriteetti_res) == 1) {
			$keraysprioriteetti_row = mysql_fetch_assoc($keraysprioriteetti_res);
			$keraysprioriteetti_query  = "	UPDATE lasku SET
											hyvaksynnanmuutos = '{$keraysprioriteetti_row["selitetark_4"]}'
											WHERE yhtio = '{$kukarow["yhtio"]}'
											AND tunnus = '{$laskurow["tunnus"]}'";
			$keraysprioriteetti_res = pupe_query($keraysprioriteetti_query);
		}
	}

	// katotaan ollaanko valittu joku poikkeava kassalipas
	if ($kertakassa != '') {

		// Ohitetaan kassamyynti
		if ($kertakassa == 'EI_KASSAMYYNTIA') {
			$kertakassa = "";
		}

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

	$kateinen = "";
	$jaksotettu = "";
	$itsetulostus = "";

	if (mysql_num_rows($maksuehto_result) == 1) {

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

				unset($valittu_tulostin);
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

		$tilausriviquery = "SELECT tilausrivi.tuoteno, (tilausrivi.hinta * (tilausrivi.varattu+tilausrivi.jt) * $query_ale_lisa) rivihinta, (tilausrivi.varattu+tilausrivi.jt) kplmaara
							FROM tilausrivi
							WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
							AND tilausrivi.otunnus = '$laskurow[tunnus]'
							AND tilausrivi.tyyppi != 'D'";
		$rivihintaresult = pupe_query($tilausriviquery);

		$rahtivapaa_alarajasumma_rivit = 0;

		while ($totaalirivi = mysql_fetch_assoc($rivihintaresult)) {
			// KAUTTALASKUTUSKIKKARE
			if (isset($GLOBALS['eta_yhtio']) and $GLOBALS['eta_yhtio'] != '' and $koti_yhtio == $kukarow['yhtio']) {
				$rahtivapaa_alarajasumma_rivit += etayhtio_hinta($totaalirivi["tuoteno"], $totaalirivi["kplmaara"], $laskurow);
			}
			else {
				$rahtivapaa_alarajasumma_rivit += $totaalirivi["rivihinta"];
			}
		}

		// kaikkiyhteensä ja rahtivapaa_alarajasumma on verollisia jos myyntihinnat ovat verollisia, tai verottomia vice versa
		if ($rahtivapaa_alarajasumma_rivit > $rahtivapaa_alarajasumma and $rahtivapaa_alarajasumma != 0) {
			$tilaus_valmis_ulos .= t("Tilauksen arvo ylitti rahtivapaasumman! Merkattiin tilaus rahtivapaaksi.")."<br>";

			// Rahtivapaat tilaukset lähetetään aina lähettäjän rahtisopimuksella
			$query = "	UPDATE lasku
						SET rahtivapaa  = 'o',
						kohdistettu 	= 'K'
						WHERE yhtio = '$kukarow[yhtio]'
						AND tunnus = '$laskurow[tunnus]'";
			$rahtivapres = pupe_query($query);

			$laskurow["kohdistettu"] = 'K';
			$laskurow["rahtivapaa"]  = 'o';
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
							AND tilausrivi.otunnus = '$laskurow[tunnus]'
							AND tilausrivi.tyyppi != 'D'";
				$step1re = pupe_query($query);

				while ($step1row = mysql_fetch_assoc($step1re)) {
					// etsitään istätilausten kaikki lapset
					$query = "	SELECT group_concat(DISTINCT tilausrivi.otunnus) uudet_otunnukset
								FROM tilausrivin_lisatiedot
								JOIN tilausrivi on (tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus AND tilausrivi.tyyppi != 'D')
								WHERE tilausrivin_lisatiedot.yhtio = '$kukarow[yhtio]'
								and tilausrivin_lisatiedot.vanha_otunnus = '$step1row[vanha_otunnus]'";
					$step2re = pupe_query($query);
					$step2row = mysql_fetch_assoc($step2re);

					// katsotaan onko lapsitilauksilla rahtikulu syötetty
					$query = "	SELECT tunnus
								FROM tilausrivi
								WHERE yhtio = '$kukarow[yhtio]'
								AND otunnus in ($step2row[uudet_otunnukset])
								AND tuoteno IN ($rahtinro_tuoteno_lisa)
								AND tyyppi != 'D'";
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
						FROM tilausrivi
						WHERE yhtio = '$kukarow[yhtio]'
						AND otunnus = '$laskurow[tunnus]'
						AND tyyppi != 'D'
						AND tuoteno IN ($rahtinro_tuoteno_lisa)";
			$result = pupe_query($query);

			//jos tilauksella on jo rahtikulu niin ei lisätä sitä uudestaan
			if (mysql_num_rows($result) == 0) {

				// jos toimitustapana on joku nouto niin ei lisätä rahtikulua
				if ($torow["nouto"] == "") {

					// haetaan rahtimaksu
					list($rah_hinta, $rah_ale, $rah_alv, $rah_netto) = hae_rahtimaksu($rahtimaksu_tunnukset);

					// haetaan rahtituotteen tiedot
					$query = "	SELECT *
								FROM tuote
								WHERE yhtio = '$kukarow[yhtio]'
								AND tuoteno = '$yhtiorow[rahti_tuotenumero]'";
					$rhire = pupe_query($query);

					//lisätään rahtikulurivi vain jos rahtikulu on erisuuri kuin nolla, nollarivejä on ilmeisesti turha lisätä (ja rahtikulutuote löytyy)
					if ($rah_hinta > 0 and mysql_num_rows($rhire) == 1) {

						$trow  	 = mysql_fetch_assoc($rhire);
						$otunnus = $laskurow['tunnus'];
						$nimitys = $laskurow["toimitustapa"];

						$query_ale_lrivi = "";
						$rah_alet = "";

						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							if (isset($rah_ale["ale{$alepostfix}"]) and $rah_ale["ale{$alepostfix}"] > 0) {
								$query_ale_lrivi .= " ale{$alepostfix} = '".$rah_ale['ale'.$alepostfix]."',";
								$rah_alet .= ", ".t("Ale")."$alepostfix: ".$rah_ale["ale{$alepostfix}"]."%";
							}
						}

						$query = "	INSERT into tilausrivi SET
									laadittu	= now(),
									hinta 		= '$rah_hinta',
									netto 		= '$rah_netto',
									varattu 	= '1',
									tilkpl 		= '1',
									otunnus 	= '$otunnus',
									tuoteno 	= '$trow[tuoteno]',
									nimitys 	= '$nimitys',
									yhtio 		= '$kukarow[yhtio]',
									tyyppi 		= 'L',
									$query_ale_lrivi
									alv 		= '$rah_alv'";
						$addtil = pupe_query($query);

						$tilaus_valmis_ulos .= t("Tilaukselle lisättiin rahtikulua").": $rah_hinta $laskurow[valkoodi]$rah_alet<br>";
					}

					if ($yhtiorow["jalkivaatimus_tuotenumero"] == "") {
						$yhtiorow["jalkivaatimus_tuotenumero"] = $yhtiorow["rahti_tuotenumero"];
					}

					$query = "	SELECT tunnus
								FROM tilausrivi
								WHERE yhtio = '$kukarow[yhtio]'
								AND otunnus = '$laskurow[tunnus]'
								AND tyyppi != 'D'
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
						AND tyyppi != 'D'
						and tuoteno IN ({$rahtinro_tuoteno_lisa})";
			$result = pupe_query($query);

			// jos tilauksella on jo rahtikulu niin merkataan se rahtivapaaksi, ettei rahtikulu mene uudestaan laskutuksen yhteydessä!
			if (mysql_num_rows($result) > 0) {
				$tilaus_valmis_ulos .= t("Tilauksella oli käsin syötetty rahtikulu! Merkattiin tilaus rahtivapaaksi, ettei kulua lisätä enää automaattisesti laskutuksessa.")."<br>";

				// Rahtivapaat tilaukset lähetetään aina lähettäjän rahtisopimuksella
				$query = "	UPDATE lasku
							SET rahtivapaa = 'O',
							kohdistettu = 'K'
							WHERE yhtio = '$kukarow[yhtio]'
							AND tunnus = '$laskurow[tunnus]'";
				$rahtivapres = pupe_query($query);

				$laskurow["kohdistettu"] = 'K';
				$laskurow["rahtivapaa"]  = 'o';
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

			unset($valittu_tulostin);
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

				unset($valittu_tulostin);
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

			// tässä välissä katsotaan onko tilausrivi_omalle_tilaukselle-parametri päällä
			// jos on, niin splitataan ne tilausrivit, joille ollaan valittu "omalle_tilaukselle" tilausriviä syötettäessä
			// splittaus tapahtuu niin, että jokaiselle tilausriville jää yksi kappale
			// eli jos "omalle_tilausriville"-tilausrivin kpl = 5, niin tehdään 4 uutta riviä ja jokaisella viidellä tilausrivillä on 1 kpl
			// Otetaan mukaan myös varattu = 1 rivit niin saadaan omalle_tilaukselle-täppä kuntoon myös mahdolliselle tuoteperheelle.
			if ($yhtiorow['tilausrivi_omalle_tilaukselle'] != '') {

				$query = "	SELECT tilausrivin_lisatiedot.omalle_tilaukselle, tilausrivi.*
							FROM tilausrivi
							JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus and tilausrivin_lisatiedot.omalle_tilaukselle != '')
							WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
							AND tilausrivi.otunnus = '{$laskurow['tunnus']}'
							AND tilausrivi.tyyppi != 'D'
							AND tilausrivi.varattu >= 1
							AND tilausrivi.var NOT IN ('J','P')
							AND (tilausrivi.perheid = 0 OR tilausrivi.perheid=tilausrivi.tunnus)
							ORDER BY tilausrivi.tunnus";
				$result = pupe_query($query);

				while ($row = mysql_fetch_assoc($result)) {

					$query = "  SELECT *
					            FROM tilausrivi
					            WHERE yhtio = '{$kukarow['yhtio']}'
					            AND tunnus = '{$row['tunnus']}'";
					$splitres = pupe_query($query);
					$splitrow = mysql_fetch_assoc($splitres);

					mysql_data_seek($splitres, 0);

					// tehdään $row[varattu] verran tilausrivejä
					for ($xx = 1; $xx < $splitrow['varattu']; $xx++) {

						$fields = "yhtio";
						$values = "'{$kukarow['yhtio']}'";

						// Ei monisteta tunnusta
						for ($i = 1; $i < mysql_num_fields($splitres) - 1; $i++) {

							$fieldname = mysql_field_name($splitres, $i);
							$fields .= ", ".$fieldname;

							switch ($fieldname) {
								case 'varattu':
									$values .= ", 1";
									break;
								default:
									$values .= ", '{$splitrow[$fieldname]}'";
							}
						}

						$kysely  = "INSERT INTO tilausrivi ({$fields}) VALUES ({$values})";
						$uusires = pupe_query($kysely);

						$split_tilausrivin_tunnus = mysql_insert_id();

						$query = "	SELECT *
									FROM tilausrivin_lisatiedot
									WHERE yhtio = '{$kukarow['yhtio']}'
									AND tilausrivitunnus = '{$row['tunnus']}'";
						$lisatiedot_result = pupe_query($query);
						$lisatiedot_row = mysql_fetch_array($lisatiedot_result);

						$fields = "yhtio";
						$values = "'{$kukarow['yhtio']}'";

						// Ei monisteta tunnusta
						for ($i = 1; $i < mysql_num_fields($lisatiedot_result) - 1; $i++) {

							$fieldname = mysql_field_name($lisatiedot_result, $i);
							$fields .= ", ".$fieldname;

							switch ($fieldname) {
								case 'tilausrivitunnus':
									$values .= ", '{$split_tilausrivin_tunnus}'";
									break;
								default:
									$values .= ", '".$lisatiedot_row[$fieldname]."'";
							}
						}

						$query = "INSERT into tilausrivin_lisatiedot ($fields) VALUES ($values)";
						$lisatiedot_result = pupe_query($query);

						// tuoteperhe-keissi
						if ($row['perheid'] > 0 and $row['tunnus'] == $row['perheid']) {
							// päivitetään splitatulle riville perheid
							$query = "	UPDATE tilausrivi
										SET perheid = '{$split_tilausrivin_tunnus}'
										WHERE yhtio = '{$kukarow['yhtio']}'
										AND tunnus  = '{$split_tilausrivin_tunnus}'";
							$update_res = pupe_query($query);

							$query = "	SELECT *
										FROM tilausrivi
										WHERE yhtio = '{$kukarow['yhtio']}'
										AND otunnus = '{$laskurow['tunnus']}'
										AND tyyppi != 'D'
										AND perheid = '{$row['tunnus']}'
										AND tunnus != '{$row['tunnus']}'";
							$lapsires = pupe_query($query);

							while ($lapsirow = mysql_fetch_assoc($lapsires)) {

								$fields = "yhtio";
								$values = "'{$kukarow['yhtio']}'";

								// Ei monisteta tunnusta
								for ($i = 1; $i < mysql_num_fields($lapsires) - 1; $i++) {

									$fieldname = mysql_field_name($lapsires, $i);
									$fields .= ", ".$fieldname;

									switch ($fieldname) {
										case 'varattu':
											$values .= ", '".($lapsirow['varattu'] / $row['varattu'])."'";
											break;
										case 'perheid':
											$values .= ", {$split_tilausrivin_tunnus}";
											break;
										default:
											$values .= ", '{$lapsirow[$fieldname]}'";
									}
								}

								$kysely  = "INSERT INTO tilausrivi ({$fields}) VALUES ({$values})";
								$uusires = pupe_query($kysely);

								$split_tilausrivin_tunnus_lapsi = mysql_insert_id();

								$query = "	SELECT *
											FROM tilausrivin_lisatiedot
											WHERE yhtio = '{$kukarow['yhtio']}'
											AND tilausrivitunnus = '{$lapsirow['tunnus']}'";
								$lisatiedot_result = pupe_query($query);
								$lisatiedot_row = mysql_fetch_array($lisatiedot_result);

								$fields = "yhtio";
								$values = "'{$kukarow['yhtio']}'";

								// Ei monisteta tunnusta
								for ($i = 1; $i < mysql_num_fields($lisatiedot_result) - 1; $i++) {

									$fieldname = mysql_field_name($lisatiedot_result, $i);
									$fields .= ", ".$fieldname;

									switch ($fieldname) {
										case 'tilausrivitunnus':
											$values .= ", '{$split_tilausrivin_tunnus_lapsi}'";
											break;
										case 'omalle_tilaukselle':
											$values .= ", 'K'";
											break;
										default:
											$values .= ", '".$lisatiedot_row[$fieldname]."'";
									}
								}

								$query = "INSERT into tilausrivin_lisatiedot ($fields) VALUES ($values)";
								$lisatiedot_result = pupe_query($query);
							}
						}
					}

					if ($row['perheid'] > 0 and $row['tunnus'] == $row['perheid']) {
						// jos on tuoteperhe, niin päivitetään lapsille varattu
						$query = "	SELECT *
									FROM tilausrivi
									WHERE yhtio = '{$kukarow['yhtio']}'
									AND otunnus = '{$laskurow['tunnus']}'
									AND tyyppi != 'D'
									AND perheid = '{$row['tunnus']}'
									AND tunnus != '{$row['tunnus']}'";
						$lapsires = pupe_query($query);

						while ($lapsirow = mysql_fetch_assoc($lapsires)) {
							$query = "	UPDATE tilausrivi
										SET varattu = '".($lapsirow['varattu'] / $row['varattu'])."'
										WHERE yhtio = '{$kukarow['yhtio']}'
										AND otunnus = '{$laskurow['tunnus']}'
										AND tunnus  = '{$lapsirow['tunnus']}'";
							$update_res = pupe_query($query);

							// päivitetään alkuoeräinen lapsirivi
							$query = "	UPDATE tilausrivin_lisatiedot
										SET omalle_tilaukselle = 'K'
										WHERE yhtio 		 = '{$kukarow['yhtio']}'
										AND tilausrivitunnus = '{$lapsirow['tunnus']}'";
							$lisatiedot_result = pupe_query($query);
						}
					}

					// päivitetään alkuperäisen rivin varattu-kenttään 1
					$query = "	UPDATE tilausrivi
								SET varattu = 1
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tunnus  = '{$row['tunnus']}'";
					$update_res = pupe_query($query);
				}
			}

			$kerayserat_select = $yhtiorow['kerayserat'] == 'K' ? "vh.keraysvyohyke, " : "";
			$kerayserat_join = $yhtiorow['kerayserat'] == 'K' ? " LEFT JOIN varaston_hyllypaikat vh ON (vh.yhtio = tilausrivi.yhtio AND vh.hyllyalue = tilausrivi.hyllyalue AND vh.hyllynro = tilausrivi.hyllynro AND vh.hyllyvali = tilausrivi.hyllyvali AND vh.hyllytaso = tilausrivi.hyllytaso)" : "";

			if ($yhtiorow["splittauskielto"] == "K") {
				$sorttaus = ", tilausrivi.kerayspvm, sorttauskentta";
			}
			else {
				$sorttaus = ", sorttauskentta";
			}

			if ($yhtiorow['kerayserat'] == 'K') {
				$sorttaus = ", vh.keraysvyohyke {$sorttaus}";
			}

			//haetaan kaikkien tilausrivien varastopaikat (varattu+kpl koska magenton tilaukset on laskutettu jo tässä vaiheessa...)
			$query = "	SELECT tilausrivi.otunnus,
						tilausrivi.perheid,
						tilausrivi.tunnus,
						tilausrivi.hyllyalue,
						tilausrivi.hyllynro,
						tilausrivi.hyllyvali,
						tilausrivi.hyllytaso,
						tilausrivi.kerayspvm,
						tilausrivi.toimaika,
						tilausrivin_lisatiedot.erikoistoimitus_myynti,
						if (tilausrivin_lisatiedot.omalle_tilaukselle != '', if(tilausrivi.perheid > 0, tilausrivi.perheid, tilausrivi.tunnus), 0) omalle_tilaukselle,
						if (tilausrivin_lisatiedot.omalle_tilaukselle != '', concat(tilausrivi.perheid,tilausrivi.tunnus), 0) omalle_tilaukselle_sort,
						tilausrivin_lisatiedot.ohita_kerays,
						{$kerayserat_select}
						concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0')) sorttauskentta
						FROM tilausrivi
						JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus AND tilausrivin_lisatiedot.ohita_kerays = '')
						{$kerayserat_join}
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and tilausrivi.otunnus = '$laskurow[tunnus]'
						AND tilausrivi.tyyppi != 'D'
						and (tilausrivi.hyllyalue != '' OR tilausrivin_lisatiedot.omalle_tilaukselle != '')
						and tilausrivi.varattu+tilausrivi.kpl > 0
						and tilausrivi.var not in ('J','P')
						ORDER BY omalle_tilaukselle_sort $sorttaus";
			$result = pupe_query($query);

			//tässä muuttujassa on sit kaikki tunnukset splitin jälkeen
			$tilausnumerot	 = array();
			$tilausnumerot[] = $laskurow["tunnus"];

			//mihin varastoon lähete kuuluu. aluksi x
			$varasto = $edvarasto = $kerayspvm = $edkerayspvm = $keraysvyohyke = $edkeraysvyohyke = $edomalle_tilaukselle = "x";
			$tulostusalue = $edtulostusalue = "x";

			//jos tilaus splittaantuu, niin laitetaan muihin otsikoihin ensimmäisen tilauksen tunnus vanhaksitunnukseksi
			$temp_laskutunnus = $laskurow['tunnus'];

			$varasto_chk_array = array();
			$varasto_kpl_array = array();
			$tulalue_kpl_array = array();

			$ohjausmerkki_nro	= 1;
			$tilaus_splitattu 	= FALSE;
			unset($laskusplitres);

			if ($yhtiorow['kerayserat'] == 'K' and isset($toimitustavan_lahto) and is_string($toimitustavan_lahto)) {
				$toimitustavan_lahto = unserialize(urldecode($toimitustavan_lahto));
			}

			while ($row = mysql_fetch_assoc($result)) {

				// Mihin varastoon
				$varasto = kuuluukovarastoon($row["hyllyalue"], $row["hyllynro"]);

				// Mikä tulostusalue
				$tulostusalue = onkotulostusalueita($row["hyllyalue"], $row["hyllynro"], $varasto);

				// Mikä keräysvyöhyke
				$keraysvyohyke = $row['keraysvyohyke'];

				// Mikä keräysvyöhyke
				$omalle_tilaukselle = $row['omalle_tilaukselle'];

				// Intrastat-tiedot
				$laskurow = palauta_intrastat_tiedot($laskurow, $varasto);

				// varasto vaihtuu, splitataan tilaus, paitsi jos kyseessä on jv-tilaus jonka rahtikirja tulostetaan heti, tai jos spliauskielto on trigattu
				// omalle_tilaukselle - tilauksia ei splitata muuten ku omiksi tilauksiksi (eli ei esim splitata varastoittain tai vyöhykkeittäin)
				if (	((($varasto != $edvarasto and $edvarasto != 'x' and $edomalle_tilaukselle == 0) or
						  ($tulostusalue != $edtulostusalue and $edtulostusalue != 'x' and $edtulostusalue != '' and $edomalle_tilaukselle == 0) or
						  ($keraysvyohyke != $edkeraysvyohyke and $edkeraysvyohyke != 'x' and $edkeraysvyohyke != '' and $edomalle_tilaukselle == 0)
						 ) and ($maehrowx["jv"] == '' or $totarowx["tulostustapa"] != 'H') and $laskurow["splittauskielto"] != "E"
						) or
						($row["kerayspvm"] != $edkerayspvm and $edkerayspvm != 'x' and $edomalle_tilaukselle == 0 and $yhtiorow["splittauskielto"] == "K") or
						($omalle_tilaukselle != $edomalle_tilaukselle and $edomalle_tilaukselle != 'x')
					) {

					$tilaus_splitattu = TRUE;

					// Meidän on päivitettävä tunnusnippu
					if ($yhtiorow["splittauskielto"] == "K" and $laskurow["tunnusnippu"] == 0) {
						$query = "UPDATE lasku set tunnusnippu = tunnus where yhtio = '$kukarow[yhtio]' and tunnus = '$laskurow[tunnus]' and tunnusnippu = 0";
						$upresult = pupe_query($query);
						$laskurow["tunnusnippu"] = $laskurow["tunnus"];
					}

					if (!is_resource($laskusplitres)) {
						// Jotta saadaa lasku ja sen lisätiedot kopsattua kivasti jos se splittaantuu
						$query = "	SELECT *
									FROM lasku
									WHERE yhtio = '$kukarow[yhtio]'
									and tunnus = '$laskurow[tunnus]'";
						$laskusplitres = pupe_query($query);

						$query = "	SELECT *
									FROM laskun_lisatiedot
									WHERE yhtio = '$kukarow[yhtio]'
									AND otunnus = '$laskurow[tunnus]'";
						$laskulisatiedotsplitres = pupe_query($query);
						$lisatiedot_row = mysql_fetch_assoc($laskulisatiedotsplitres);
					}

					$fields = "yhtio";
					$values = "'$kukarow[yhtio]'";

					$split_kohdistettu	= $laskurow["kohdistettu"];
					$split_rahtivapaa	= $laskurow["rahtivapaa"];
					$split_toimitustapa = $laskurow["toimitustapa"];

					$query = "	SELECT ulkoinen_jarjestelma, erikoistoimitus_toimitustapa
								FROM varastopaikat
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tunnus = '{$varasto}'";
					$ulkoinen_jarjestelma_res = pupe_query($query);
					$varaston_row = mysql_fetch_assoc($ulkoinen_jarjestelma_res);

					// Erikoistoimitukset rahtivapaana
					// Rahtivapaat tilaukset lähetetään aina lähettäjän rahtisopimuksella
					if ($row['erikoistoimitus_myynti'] == 1 and $varaston_row['erikoistoimitus_toimitustapa'] != '') {
						$split_kohdistettu	= "K";
						$split_rahtivapaa	= "o";
						$split_toimitustapa = $varaston_row["erikoistoimitus_toimitustapa"];
					}

					if ($yhtiorow['kerayserat'] == 'K' and isset($toimitustavan_lahto) and isset($toimitustavan_lahto[$varasto]) and $toimitustavan_lahto[$varasto] > 0) {
						$toimitustavan_lahto = $toimitustavan_lahto[$varasto];
					}
					else {
						$toimitustavan_lahto = 0;
					}

					// Ei monisteta tunnusta
					for ($i=1; $i < mysql_num_fields($laskusplitres)-1; $i++) {

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
							case 'toimitustavan_lahto':
								$values .= ", '$toimitustavan_lahto'";
								break;
							case 'ohjausmerkki':
								if ($row['omalle_tilaukselle'] > 0 and trim($laskurow['ohjausmerkki']) != "") {
									$values .= ", '{$ohjausmerkki_nro}-{$laskurow['ohjausmerkki']}'";
									$ohjausmerkki_nro++;
								}
								elseif ($row['omalle_tilaukselle'] > 0 and trim($laskurow['ohjausmerkki']) == "") {
									$values .= ", '{$ohjausmerkki_nro}-{$laskurow['vanhatunnus']}'";
									$ohjausmerkki_nro++;
								}
								else {
									$values .= ", '{$laskurow['ohjausmerkki']}'";
								}
								break;
							case 'kohdistettu':
								$values .= ", '$split_kohdistettu'";
								break;
							case 'rahtivapaa':
								$values .= ", '$split_rahtivapaa'";
								break;
							case 'toimitustapa':
								$values .= ", '$split_toimitustapa'";
								break;
							default:
								$values .= ", '".$laskurow[$fieldname]."'";
						}
					}

					$kysely  = "INSERT into lasku ($fields) VALUES ($values)";
					$uusires = pupe_query($kysely);

					$laskurow['tunnus'] = mysql_insert_id();

					// Laitetaan uuden tilauksen varaston "ulkoinen_jarjestelma"-tieto talteen
					$varasto_chk_array[$laskurow['tunnus']] = $varaston_row['ulkoinen_jarjestelma'];

					// Tallennetaan kaikki splitit
					$tilausnumerot[] = $laskurow["tunnus"];

					$fields = "yhtio";
					$values = "'$kukarow[yhtio]'";

					// Ei monisteta tunnusta
					for ($i = 1; $i < mysql_num_fields($laskulisatiedotsplitres) - 1; $i++) {

						$fieldname = mysql_field_name($laskulisatiedotsplitres, $i);

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
					$uusires = pupe_query($query);
				}
				elseif (!$tilaus_splitattu) {
					// Pidetään lukua kuinka paljon mistäkin varastosta on riviä, jotka jää alkuperäiselle tilaukselle
					if ($varasto > 0) {
						if (isset($varasto_kpl_array[$varasto])) $varasto_kpl_array[$varasto]++;
						else $varasto_kpl_array[$varasto] = 1;
					}

					if ($tulostusalue != "") {
						if (isset($tulalue_kpl_array[$tulostusalue])) $tulalue_kpl_array[$tulostusalue]++;
						else $tulalue_kpl_array[$tulostusalue] = 1;
					}
				}

				//	edellinen varasto talteen
				$edvarasto = $varasto;

				// edellinen tulostusalue talteen
				$edtulostusalue = $tulostusalue;

				// edellinen keraysvyohyke
				$edkeraysvyohyke = $keraysvyohyke;

				//	edellinen kerayspvm talteen
				$edkerayspvm = $row["kerayspvm"];

				//	edellinen omalle_tilaukselle talteen
				$edomalle_tilaukselle = $row["omalle_tilaukselle"];

				//jos tilausrivin laskunumero on eri
				if ($row['otunnus'] != $laskurow['tunnus']) {
					$query = "	UPDATE tilausrivi
								SET otunnus='$laskurow[tunnus]'
								WHERE tunnus='$row[tunnus]'
								AND yhtio='$kukarow[yhtio]'";
					$ohno  = pupe_query($query);

					if ($row['perheid'] > 0 and $row['perheid'] == $row['tunnus']) {

						$query = "	SELECT tilausrivin_lisatiedot.ohita_kerays
									FROM tilausrivi
									JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus AND tilausrivin_lisatiedot.ohita_kerays != '')
									WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
									AND tilausrivi.otunnus = '{$row['otunnus']}'
									AND tilausrivi.perheid = '{$row['perheid']}'
									AND tilausrivi.tunnus != '{$row['perheid']}'";
						$ohnoes_res = pupe_query($query);

						if (mysql_num_rows($ohnoes_res) > 0 or $row['omalle_tilaukselle'] != '') {
							$query = "	UPDATE tilausrivi
										SET otunnus = '{$laskurow['tunnus']}'
										WHERE yhtio = '{$kukarow['yhtio']}'
										AND otunnus = '{$row['otunnus']}'
										AND perheid = '{$row['tunnus']}'
										AND tunnus != '{$row['tunnus']}'";
							$ohno  = pupe_query($query);
						}
					}
				}
			}

			// Valitaan paras varasto ja tulostusalue alkuperäiselle osalla
			asort($varasto_kpl_array);
			asort($tulalue_kpl_array);

			$varasto_kpl_array = array_keys($varasto_kpl_array);
			$tulalue_kpl_array = array_keys($tulalue_kpl_array);

			$varasto = array_pop($varasto_kpl_array);
			$tulostusalue = array_pop($tulalue_kpl_array);

			$query = "	SELECT ulkoinen_jarjestelma, erikoistoimitus_toimitustapa
						FROM varastopaikat
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tunnus = '{$varasto}'";
			$ulkoinen_jarjestelma_res = pupe_query($query);
			$varaston_row = mysql_fetch_assoc($ulkoinen_jarjestelma_res);

			// Intrastat-tiedot
			$laskurow = palauta_intrastat_tiedot($laskurow, $varasto);

			// Laitetaan alkuperäisen tilauksen varaston "ulkoinen_jarjestelma"-tieto talteen
			$varasto_chk_array[$temp_laskutunnus] = $varaston_row['ulkoinen_jarjestelma'];

			// Rahtivapaat tilaukset lähetetään aina lähettäjän rahtisopimuksella
			$toimitustapalisa = ($row['erikoistoimitus_myynti'] == 1 and $varaston_row['erikoistoimitus_toimitustapa'] != '') ? ", toimitustapa = '$varaston_row[erikoistoimitus_toimitustapa]', rahtivapaa = 'o', kohdistettu = 'K' " : '';

			if ($yhtiorow['kerayserat'] == 'K' and isset($toimitustavan_lahto) and isset($toimitustavan_lahto[$varasto]) and $toimitustavan_lahto[$varasto] > 0) {
				$toimitustavan_lahto = $toimitustavan_lahto[$varasto];
			}
			else {
				$toimitustavan_lahto = 0;
			}

			$ohjausmerkkilisa = "";

			// Jos sarjatilaus ja ohjausmerkki puuttuu
			if ($omalle_tilaukselle > 0 AND trim($laskurow['ohjausmerkki']) == "") {
				$ohjausmerkkilisa = " , ohjausmerkki = '{$ohjausmerkki_nro}-{$laskurow['vanhatunnus']}' ";
			}

			//päivitetään laskulle tieto mihin varastoon se kuuluu
			$query = "	UPDATE lasku set
						varasto 							= '$varasto',
						tulostusalue						= '$tulostusalue',
						toimitustavan_lahto					= '$toimitustavan_lahto',
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
						{$ohjausmerkkilisa}
						{$toimitustapalisa}
						where yhtio = '$kukarow[yhtio]'
						and tunnus  = '$temp_laskutunnus'";
			$varresult = pupe_query($query);

			$laskurow["varasto"] = $varasto;

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
								where yhtio='$kukarow[yhtio]'
								and otunnus='$tilausnumero'
								AND tyyppi != 'D'";
					$result = pupe_query($query);
					$toimaikatilrirow = mysql_fetch_assoc($result);

					$query = "	SELECT kerayspvm, toimaika
								from lasku
								where yhtio = '$kukarow[yhtio]' and tunnus = '$tilausnumero'";
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

			// Varmistetaan, että jokaisella tilauksella on lähtö
			if ($yhtiorow['kerayserat'] == 'K') {
				foreach ($tilausnumerot as $tilausnumero) {
					$query = "	SELECT *
								FROM lasku
								WHERE yhtio = '$kukarow[yhtio]'
								AND tunnus 	= '$tilausnumero'
								AND toimitustavan_lahto = 0";
					$result = pupe_query($query);

					if (mysql_num_rows($result) > 0) {
						$lahtorow = mysql_fetch_assoc($result);

						// Tilaustyyppi 2, eli varastotäydennys, siirretään aina yhden päivän eteenpäin
						$eteenpain = ($lahtorow["tilaustyyppi"] == 2) ? 1 : 0;

						$lahdot = seuraavat_lahtoajat($lahtorow["toimitustapa"], $lahtorow["prioriteettinro"], $lahtorow["varasto"], 0, $eteenpain);

						if ($lahdot !== FALSE) {
							// Otetaan eka lähtö
							$valitu_lahto = array_shift($lahdot);

							$query = "	UPDATE lasku
										SET toimitustavan_lahto = '$valitu_lahto[tunnus]'
										WHERE yhtio = '$kukarow[yhtio]'
										AND tunnus  = '$tilausnumero'";
							$upresult = pupe_query($query);
						}
					}
				}
			}


			//tyhjennetään muuttujat
			$varasto 	= '';
			$edvarasto 	= '';

			// loopataan kaikki yhdestä tilauksesta syntyneet lähetteet
			// jos halutaan tilausvahvistus aluperäisestä tilauksesta
			if ($laskurow["tunnusnippu"] > 0 or $yhtiorow["tilausvahvistus_lahetys"] == 1) {

				if (trim($laskurow['tilausvahvistus']) != "") {
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
				}

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

					if (trim($laskurow['tilausvahvistus']) != "") {
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
													tilausrivi.tyyppi != 'D' AND
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
