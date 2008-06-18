<?php

// tarvitaan $kukarow[yhtio], $kukarow[kesken], $laskurow array, $yhtiorow array

$tilaus_valmis_ulos = "";

///* Reload ja back-nappulatsekki *///
if ($kukarow["kesken"] == '' or $kukarow["kesken"] == '0') {
	echo "<font class='error'> ".t("Taisit painaa takaisin tai p‰ivit‰ nappia. N‰in ei saa tehd‰")."! </font>";
	exit;
}

// p‰ivitet‰‰n vanhatunnus laskulle
$query  = "update lasku set vanhatunnus=tunnus where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[tunnus]' and vanhatunnus=0";
$oldtur = mysql_query($query) or pupe_error($query);

if ($laskurow["tilaustyyppi"] == 'E') {
	//Ennakkotilaukset

	//tila on E ja alatila on A kun on ennakkotilaus
	$query  = "	UPDATE lasku
				SET tila='E',
				alatila='A'
				WHERE tunnus='$laskurow[tunnus]'
				and tila in ('N','L','E')
				and yhtio='$kukarow[yhtio]'";
	$result = mysql_query($query) or pupe_error($query);

	// ennakkotilauksissa tyyppi on E ja rivit ei ole ennakkopoistossa
	$query = "	UPDATE tilausrivi
				SET tyyppi	= 'E'
				WHERE yhtio	= '$kukarow[yhtio]'
				and otunnus	= '$laskurow[tunnus]'
				and tyyppi in ('L','E')";
	$result = mysql_query($query) or pupe_error($query);


	//
	// LƒHETETƒƒN TILAUSVAHVISTUS
	//
	// tulostetaan t‰ss‰, niin saadaan vahvistukseen koko tilaus, ennenkun sen splitaatan eri varastoihin

	if (strpos(" ".$laskurow['tilausvahvistus'],'3'))
		$naytatvale = 3; // jos mell‰ on tilausvahvistuksessa kolmonen, ei haluta n‰hd‰ hintoja, pelk‰st‰‰n kpl-m‰‰r‰t
	else if (strpos(" ".$laskurow['tilausvahvistus'],'2'))
		$naytatvale = 2; // jos mell‰ on tilausvahvistuksessa kakkonen, ei haluta n‰h‰ aleja
	else
		$naytatvale = 1; // halutaan n‰h‰ alet

	if (strpos(" ".$laskurow['tilausvahvistus'],'E'))
		require("tilausvahvistus-edi.inc");

	if (strpos(" ".$laskurow['tilausvahvistus'],'S'))
		require("tilausvahvistus-email.inc");

	if (strpos(" ".$laskurow['tilausvahvistus'],'F'))
		require("tilausvahvistus-fax.inc");

	if (strpos(" ".$laskurow['tilausvahvistus'],'O'))
		require("tilausvahvistus-email.inc");

	if (strpos(" ".$laskurow['tilausvahvistus'],'U'))
		require("tilausvahvistus-futursoft.inc");

	if (strpos(" ".$laskurow['tilausvahvistus'],'K')) {
		$query = "	SELECT komento
					FROM kirjoittimet
					WHERE yhtio = '$kukarow[yhtio]' and
					tunnus = '$kukarow[kirjoitin]'";
		$tilvares = mysql_query($query) or pupe_error($query);

		if ($tilvakir = mysql_fetch_array($tilvares)) {
			$komento["Tilausvahvistus"] = $tilvakir["komento"];

			require_once ("tulosta_tilausvahvistus_pdf.inc");
		}
	}
}
elseif ($laskurow["tilaustyyppi"] == '0') {
		// Yll‰pitosopimus
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

	//tila on T kun kyseess‰ on tarjous
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
	// T‰ss‰ on normaalit tilaukset

	// katotaan ollaanko valittu joku poikkeava kassalipas
	if ($kertakassa != '') {
		$query = "UPDATE lasku SET kassalipas = '$kertakassa' where yhtio='$kukarow[yhtio]' and tunnus = '$laskurow[tunnus]'";
		$result = mysql_query($query) or pupe_error($query);
	}

	// Aktivoituiko "Odottaa jt-rivej‰" tilaus?
	if (($laskurow["tila"] == 'N' or $laskurow["tila"] == 'G') and $laskurow["alatila"] == 'T') {
		// Onko jotain tulostettavaa?
		$query = "	SELECT *
					from tilausrivi
					where yhtio = '$kukarow[yhtio]'
					and otunnus = '$laskurow[tunnus]'
					and keratty = ''
					and varattu > 0";
		$keres = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($keres) > 0) {
			//Muutetaan tulostettavaksi
			$query = "UPDATE lasku SET alatila = '' where yhtio='$kukarow[yhtio]' and tunnus = '$laskurow[tunnus]'";
			$result = mysql_query($query) or pupe_error($query);
		}
	}

	// otetaan maksuehto t‰ss‰ heti alussa selville.. k‰teinen ja itsetulostus muuttaa asioita
	$query = "select * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
	$result = mysql_query($query) or pupe_error($query);

	$kateinen = "";
	$jaksotettu = "";
	$itsetulostus = "";

	if (mysql_num_rows($result)==1) {
		$maksuehtorow = mysql_fetch_array($result);

		// jos kyseess‰ on k‰teiskauppaa
		if ($maksuehtorow['kateinen']!='') {
			$kateinen = "X";
		}
		// jos kyseess‰ on jaksotusta
		if ($maksuehtorow['jaksotettu']!='') {
			$jaksotettu = "X";
		}
		//jos kyseess‰ on itsetulostusmaksuehto
		if ($maksuehtorow['itsetulostus']!='') {
			$itsetulostus = "X";
		}
	}

	if ($yhtiorow["kerataanko_saldottomat"] == '') {
		$lisasalker = " and tuote.ei_saldoa = '' ";
	}
	else {
		$lisasalker = " ";
	}

	// Katotaan, onko t‰m‰ jo ker‰tty...
	$query = "	SELECT tilausrivi.*
				from tilausrivi use index (yhtio_otunnus)
				join tuote on tilausrivi.yhtio=tuote.yhtio and tilausrivi.tuoteno=tuote.tuoteno
				where tilausrivi.yhtio='$kukarow[yhtio]'
				and tilausrivi.otunnus='$laskurow[tunnus]'
				and tilausrivi.keratty!=''
				$lisasalker";
	$keres = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($keres) > 0 or ($laskurow["tila"] == 'L' and ($laskurow["alatila"] == 'C' or $laskurow["alatila"] == 'B' or $laskurow["alatila"] == 'D' or $laskurow["alatila"] == 'E' or $laskurow["alatila"] == 'J'))) {
		//tila L ja alatila C, B, D, E niin tilaus on ker‰tty otsikko
		//jos otsikko sanoo, ett‰ tilaus on ker‰tty mutta rivit kuitenkaan ei ole ker‰tty niin merkataan ne kumminkin ker‰tyiksi
		//tai jos yksikin rivi on ker‰tty (l‰hetett‰ ei tulosteta uudestaan) niin p‰ivitet‰‰n ker‰‰m‰ttˆm‰t rivit ker‰tyksi aktiiviselle k‰ytt‰j‰lle
		$query = "	UPDATE tilausrivi
					SET keratty='$kukarow[kuka]', kerattyaika=now()
					WHERE yhtio='$kukarow[yhtio]'
					and otunnus='$laskurow[tunnus]'
					and keratty=''
					and var != 'J'";
		$result = mysql_query($query) or pupe_error($query);

		//Laitetaan rivit toimitetuiksi jos otsikko sanoo toimitettu
		if ($laskurow["alatila"] == 'D' or $laskurow["alatila"] == 'E' or $laskurow["alatila"] == 'J') {
			$query = "	UPDATE tilausrivi
						SET toimitettu='$kukarow[kuka]', toimitettuaika=now()
						WHERE yhtio		 = '$kukarow[yhtio]'
						and otunnus		 = '$laskurow[tunnus]'
						and keratty		!= ''
						and toimitettu	 = ''
						and var 		!= 'J'
						and tyyppi 		!= 'D'";
			$result = mysql_query($query) or pupe_error($query);
		}

		$tilaus_valmis_ulos .= "<font class='message'>".t("Tilauksella oli jo ker‰ttyj‰ rivej‰, joten l‰hetett‰ ei tulosteta").".<br>".t("Jos haluat l‰hetteen tulosta l‰hetteen kopio. Tilausnumero on")." $laskurow[tunnus].</font><br><br>";

		// jos on myyntitilaus ja ei haluttu tulostaa l‰hetett‰, merkataan rivit ker‰tyksi ja toimitetuksi ja laitetaan alatila D
		if ($laskurow['eilahetetta'] != '' or $laskurow['sisainen'] != '') {
			if ($kateinen == 'X' or $itsetulostus == "X" or $laskurow["vienti"] != '') {
				// jos kyseess‰ on k‰teismyynti‰ tai vienti‰, merkataan tilaukset ker‰tyksi mutta ei toimitetuksi ja alatila C/B
				// myˆs jos maksuehtolla on itsetulostust‰pp‰ trigattuna niin laitetaan se t‰h‰n haaraan
				// Merkataan rivit ker‰tyiksi. JT-rivej‰ ei merkata ker‰tyiksi.

				$query  = "	UPDATE tilausrivi
							SET kerattyaika = now(),
							keratty = '$kukarow[kuka]'
							WHERE otunnus 	= '$laskurow[tunnus]'
							and yhtio 		= '$kukarow[yhtio]'
							and var not in ('P','J')
							and keratty 	= ''
							and toimitettu 	= ''";
				$result = mysql_query($query) or pupe_error($query);

				if ($laskurow['sisainen'] != '') {
					$alat = "D";
				}
				elseif($laskurow["vienti"] != '') {
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
				// jos kyseess‰  EI ole k‰teismyynti‰ eik‰ itsetulostusta niin merkataan tilaus toimitetuksi
				// t‰m‰ on kotimaan myyntia, mutta ei k‰teismyynti‰
				//t‰ss‰ toimitetaan ja ker‰t‰‰n tilaus samantien jos ei_l‰hetett‰ nappi oli ruksattuna
				// Merkataan rivit ker‰tyiksi ja toimitetuiksi. JT-rivej‰ ei merkata ker‰tyiksi.

				$query  = "	UPDATE tilausrivi
							SET kerattyaika = now(),
							keratty = '$kukarow[kuka]',
							toimitettuaika = now(),
							toimitettu = '$kukarow[kuka]'
							WHERE otunnus 	= '$laskurow[tunnus]'
							and yhtio		= '$kukarow[yhtio]'
							and var not in ('P','J')
							and keratty		= ''
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
				elseif($valittu_tulostin == 0) {
					$valittu_tulostin = "AUTOMAAGINEN_VALINTA";
				}

				require ("tilauskasittely/verkkolasku.php");
			}
		}
	}
	else {
		//t‰ss‰ k‰yd‰‰n l‰pi v‰h‰n n‰it‰ rahtijuttuja
		//jos rahtihinnoittelu tapahtuu tilauksen hinnan (alviton) perusteella niin tehd‰‰n se t‰ss‰
		//t‰m‰ olkoon hyv‰ arvaus rahtihinnasta, sit‰ voidaan sitten myˆhemmin tarkentaa
		if ($yhtiorow["rahti_hinnoittelu"] == "o") {

			$query = "	SELECT tunnus
						from tilausrivi
						where yhtio = '$kukarow[yhtio]'
						and otunnus = '$laskurow[tunnus]'
						and tuoteno = '$yhtiorow[rahti_tuotenumero]'";
			$result = mysql_query($query) or pupe_error($query);

			//jos tilauksella on jo rahtikulu niin ei lis‰t‰ sit‰ uudestaan
			if (mysql_num_rows($result) == 0) {

				$query = "SELECT * from toimitustapa where yhtio='$kukarow[yhtio]' and selite='$laskurow[toimitustapa]'";
				$result = mysql_query($query) or pupe_error($query);
				$torow = mysql_fetch_array($result);

				// jos toimitustapana on joku nouto niin ei lis‰t‰ rahtikulua
				if ($torow["nouto"] == "") {
					if ($yhtiorow["alv_kasittely"] == '') {
						$query = "	SELECT round(((varattu+jt)*hinta*(1-(ale/100)))/(1+alv/100),'$yhtiorow[hintapyoristys]') rivihinta, netto
									FROM tilausrivi
									WHERE otunnus 	= '$laskurow[tunnus]'
									and yhtio	  	= '$kukarow[yhtio]'
									and tyyppi		= 'L'";
					}
					else {
						$query = "	SELECT round(((varattu+jt)*hinta*(1-(ale/100))),'$yhtiorow[hintapyoristys]') rivihinta, netto
									FROM tilausrivi
									WHERE otunnus 	= '$laskurow[tunnus]'
									and yhtio	  	= '$kukarow[yhtio]'
									and tyyppi		= 'L'";
					}

					$presult = mysql_query($query) or pupe_error($query);

					$nyht = 0;
					$myht = 0;
					$kyht = 0;

					while ($prow = mysql_fetch_array ($presult)) {

						if ($prow["netto"] != 'N') {
							$myht += $prow["rivihinta"]; // lasketaan tilauksen loppusummaa MUUT RIVIT..
						}
						else {
							$nyht += $prow["rivihinta"]; // lasketaan tilauksen loppusummaa NETTORIVIT..
						}
					}
					//erikoisalennus lasketaan vain riveille joilla EI ole NETTOHINTAA
					if ($laskurow['erikoisale'] != 0) {
						$apu1 = round($laskurow['erikoisale']/100,2);	// erikoisale prosentti
						$apu2 = round($myht*$apu1,2); 					// erikoisalen m‰‰r‰
						$apu3 = round((1-$apu1)*$myht,2);				// loppusumma

						//Kakki yhteens‰
						$kyht = $apu3 + $nyht;
					}
					else {
						//Kakki yhteens‰
						$kyht = $myht + $nyht;
					}

					// $kyht on KOTIVALUUTASSA, k‰‰nnet‰‰n laskun valuuttaan
					if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
						$kyht = round(laskuval($kyht, $laskurow["vienti_kurssi"]), 2);
					}

					$query = "	SELECT rahtihinta
								from rahtimaksut
								where yhtio		 = '$kukarow[yhtio]'
								and toimitustapa = '$laskurow[toimitustapa]'
								and kilotalku   <= '$kyht'
								and kilotloppu  >= '$kyht'";
					$result = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($result) > 0) {
						$hirow = mysql_fetch_array($result);
					}
					else {
						$hirow = array();
						$hirow["rahtihinta"] = 0;
					}

					// jos halutaan rahtikulu, katotaan saadaanko siltikin ilmaseks jos ollaan yli rajojen
					if ($laskurow["rahtivapaa"] == "" or ($mista == 'jtselaus' and $yhtiorow["jt_rahti"] == "D")) {
						$as_que = "SELECT rahtivapaa_alarajasumma FROM asiakas WHERE yhtio='$kukarow[yhtio]' AND tunnus='$laskurow[liitostunnus]' and rahtivapaa_alarajasumma > 0";
						$as_res = mysql_query($as_que) or pupe_error($as_que);

						if (mysql_num_rows($as_res) > 0) {
							$as_row = mysql_fetch_array($as_res);

							if ($kyht > $as_row["rahtivapaa_alarajasumma"]) {
								$hirow = array();
								$hirow["rahtihinta"] = 0;

								$query = "UPDATE lasku SET rahtivapaa='o' WHERE yhtio='$kukarow[yhtio]' AND tunnus='$laskurow[tunnus]'";
								$rahtivapres = mysql_query($query) or pupe_error($query);
							}
						}
						else {
							if ($kyht > $yhtiorow["rahtivapaa_alarajasumma"] and $yhtiorow["rahtivapaa_alarajasumma"] != 0) {
								$hirow = array();
								$hirow["rahtihinta"] = 0;

								$query = "UPDATE lasku SET rahtivapaa='o' WHERE yhtio='$kukarow[yhtio]' AND tunnus='$laskurow[tunnus]'";
								$rahtivapres = mysql_query($query) or pupe_error($query);
							}
						}
					}
					else {
						$hirow = array();
						$hirow["rahtihinta"] = 0;
					}

					//lis‰t‰‰n rahtikulurivi vain jos rahtikulu on erisuuri kuin nolla, nollarivej‰ on ilmeisesti turha llis‰t‰
					if ($hirow["rahtihinta"] != 0) {
						$query = "SELECT * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$yhtiorow[rahti_tuotenumero]'";
						$rhire = mysql_query($query) or pupe_error($query);
						$trow  = mysql_fetch_array($rhire);

						$otunnus	 = $laskurow['tunnus'];
						$hinta		 = $hirow["rahtihinta"]; // rahtihinta
						$trow["alv"] = $laskurow["alv"];
						$nimitys	 = $laskurow["toimitustapa"];

						list($lis_hinta, $lis_netto, $lis_ale, $alehinta_alv, $alehinta_val) = alehinta($laskurow, $trow, '1', 'N', $hinta, 0);
						list($hinta, $alv) = alv($laskurow, $trow, $lis_hinta, '', $alehinta_alv);

						$query  = "	INSERT into tilausrivi (laadittu, hinta, netto, varattu, tilkpl, otunnus, tuoteno, nimitys, yhtio, tyyppi, alv, kommentti)
									values  (now(), '$hinta', 'N', '1', '1', '$otunnus', '$trow[tuoteno]', '$nimitys', '$kukarow[yhtio]', 'L', '$alv', '')";
						$addtil = mysql_query($query) or pupe_error($query);

						$tilaus_valmis_ulos .= t("Tilaukselle lis‰ttiin rahtikulua").": $hinta $laskurow[valkoodi]<br>";

						if ($torow["jvkulu"] != 0) {
							$otunnus	 = $laskurow['tunnus'];
							$hinta		 = $torow["jvkulu"]; // jvkulu
							$trow["alv"] = $laskurow["alv"];
							$nimitys	 = t("J‰lkivaatimuskulu");

							list($hinta, $alv) = alv($laskurow, $trow, $hinta, '', '');

							$query  = "	INSERT into tilausrivi (laadittu, hinta, netto, varattu, tilkpl, otunnus, tuoteno, nimitys, yhtio, tyyppi, alv, kommentti)
										values (now(), '$hinta', 'N', '1', '1', '$otunnus', '$trow[tuoteno]', '$nimitys', '$kukarow[yhtio]', 'L', '$alv', '')";
							$addtil = mysql_query($query) or pupe_error($query);

							$tilaus_valmis_ulos .= t("Tilaukselle lis‰ttiin j‰lkivaatimuskulua").": $hinta $laskurow[valkoodi]<br>";
						}
					}
				}
			}
		}

		//jos on kassamyynti‰ ja se on k‰teist‰ niin tulostetaan lasku heti ilman l‰hetett‰
		if (($kateinen == 'X' and $kateisohitus == "" and ($kukarow["kassamyyja"] != '' or $kertakassa != "") and $laskurow["vienti"] == '') or ($korkolasku == 'kylla')) {
			$query = "	UPDATE tilausrivi
						SET toimitettu = '$kukarow[kuka]',
						toimitettuaika = now()
						WHERE otunnus = '$laskurow[tunnus]'
						and var not in ('P','J')
						and yhtio = '$kukarow[yhtio]'
						and keratty != ''
						and tyyppi  != 'D'";
			$result = mysql_query($query) or pupe_error($query);

			$query = "	UPDATE lasku
						SET tila='L', alatila='D'
						WHERE tunnus = '$laskurow[tunnus]'
						and yhtio = '$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);

			//tulostetaan k‰teislasku...
			$laskutettavat	= $laskurow["tunnus"];
			$tee 			= "TARKISTA";
			$laskutakaikki 	= "KYLLA";
			$silent		 	= "VIENTI";

			if ($kukarow["kirjoitin"] != 0 and $valittu_tulostin == 0) {
				$valittu_tulostin = $kukarow["kirjoitin"];
			}
			elseif($valittu_tulostin == 0) {
				$valittu_tulostin = "AUTOMAAGINEN_VALINTA";
			}

			require ("tilauskasittely/verkkolasku.php");
		}
		// jos on myyntitilaus ja ei haluttu tulostaa l‰hetett‰, merkataan rivit ker‰tyksi ja toimitetuksi ja laitetaan alatila D
		elseif ($laskurow['eilahetetta'] != '' or $laskurow['sisainen'] != '' and $kateisohitus == "") {
			if ($kateinen == 'X' or $itsetulostus == "X" or $laskurow["vienti"] != '') {
				// jos kyseess‰ on k‰teismyynti‰ tai vienti‰, merkataan tilaukset ker‰tyksi mutta ei toimitetuksi ja alatila C/B
				// myˆs jos maksuehtolla on itsetulostust‰pp‰ trigattuna niin laitetaan se t‰h‰n haaraan
				// Merkataan rivit ker‰tyiksi. JT-rivej‰ ei merkata ker‰tyiksi.

				$query  = "	UPDATE tilausrivi
							SET kerattyaika = now(),
							keratty = '$kukarow[kuka]'
							WHERE otunnus 	= '$laskurow[tunnus]'
							and yhtio 		= '$kukarow[yhtio]'
							and var not in ('P','J')
							and keratty 	= ''
							and toimitettu 	= ''";
				$result = mysql_query($query) or pupe_error($query);

				if ($laskurow['sisainen'] != '') {
					$alat = "D";
				}
				elseif($laskurow["vienti"] != '') {
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
				// jos kyseess‰  EI ole k‰teismyynti‰ eik‰ itsetulostusta niin merkataan tilaus toimitetuksi
				// t‰m‰ on kotimaan myyntia, mutta ei k‰teismyynti‰
				//t‰ss‰ toimitetaan ja ker‰t‰‰n tilaus samantien jos ei_l‰hetett‰ nappi oli ruksattuna
				// Merkataan rivit ker‰tyiksi ja toimitetuiksi. JT-rivej‰ ei merkata ker‰tyiksi.

				$query  = "	UPDATE tilausrivi
							SET kerattyaika = now(),
							keratty = '$kukarow[kuka]',
							toimitettuaika = now(),
							toimitettu = '$kukarow[kuka]'
							WHERE otunnus 	= '$laskurow[tunnus]'
							and yhtio		= '$kukarow[yhtio]'
							and var not in ('P','J')
							and keratty		= ''
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
				elseif($valittu_tulostin == 0) {
					$valittu_tulostin = "AUTOMAAGINEN_VALINTA";
				}

				require ("tilauskasittely/verkkolasku.php");
			}
		}
		else {
			// Katotaan mit‰ tehd‰‰n l‰hetteelle
			// Ensin on pakko splitata l‰hete varastoittain
			//tyhjennet‰‰n laskun varastokentt‰, siell‰ oli tieto varastoon optimoinnista mutta nyt se kertoo mihin varastoon l‰hete kuuluu laittaa
			$laskurow['varasto'] = 0;

			//Toimitustavan hetier‰ tarvitaan
			$query = "SELECT tulostustapa from toimitustapa where yhtio='$kukarow[yhtio]' and selite='$laskurow[toimitustapa]'";
			$result = mysql_query($query) or pupe_error($query);
			$totarowx = mysql_fetch_array($result);

			// Maksuehdon jv tieto tarvitaan
			$query = "SELECT jv from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
			$result = mysql_query($query) or pupe_error($query);
			$maehrowx = mysql_fetch_array($result);

			if($yhtiorow["splittauskielto"] == "K") {
				$sorttaus = "kerayspvm, sorttauskentta";
			}
			else {
				$sorttaus = "sorttauskentta";
			}

			//haetaan kaikkien tilausrivien varastopaikat
			$query = "	SELECT tilausrivi.otunnus, tilausrivi.tunnus, tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso,  tilausrivi.kerayspvm, tilausrivi.toimaika,
						concat(lpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0')) sorttauskentta
						FROM tilausrivi
						LEFT JOIN tuote on tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and tilausrivi.otunnus = '$laskurow[tunnus]'
						and tilausrivi.hyllyalue != ''
						and varattu > 0
						ORDER BY $sorttaus";
			$result = mysql_query($query) or pupe_error($query);

			//t‰ss‰ muuttujassa on sit kaikki tunnukset splitin j‰lkeen
			$tilausnumerot	 = array();
			$tilausnumerot[] = $laskurow["tunnus"];

			//mihin varastoon l‰hete kuuluu. aluksi x
			$varasto = $edvarasto = $kerayspvm = $edkerayspvm = "x";

			while ($row = mysql_fetch_array($result)) {

				$varasto = kuuluukovarastoon($row["hyllyalue"], $row["hyllynro"]);

				$query = "SELECT * from varastopaikat where yhtio = '$kukarow[yhtio]' and tunnus = '$varasto'";
				$varaston_res = mysql_query($query) or pupe_error($query);
				$varaston_row = mysql_fetch_array($varaston_res);

				$ultilno = tarvitaanko_intrastat($varaston_row["maa"], $laskurow["toim_maa"]);

				$laskurow["maa_maara"] = $laskurow["toim_maa"];
				$laskurow["maa_lahetys"] = $varaston_row["maa"];

				if ($ultilno == '-1') {
					// vienti-ilmoitus
					// katotaan onko jotain oletuksia asiakkaan takaa
					$query  = "SELECT * from asiakas where yhtio = '$kukarow[yhtio]' and tunnus = '$laskurow[liitostunnus]'";
					$toiresult = mysql_query($query) or pupe_error($query);
					$aputoimirow = mysql_fetch_array($toiresult);

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
					$aputoimirow = mysql_fetch_array($toiresult);

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
				elseif ($varaston_row["maa"] == $laskurow["toim_maa"]) {
					// intrastattia ei l‰hetet‰ tulliin kun varasto ja toimitusosoite on samassa maassa
					$laskurow["kauppatapahtuman_luonne"] = '999';
				}

				//varasto vaihtuu, splitataan tilaus, paitsi jos kyseess‰ on jv-tilaus jonka rahtikirja tulostetaan heti, tai jos spliauskielto on trigattu
				if ((($varasto != $edvarasto and $edvarasto != 'x') and ($maehrowx["jv"] == '' or $totarowx["tulostustapa"] != 'H') and $laskurow["splittauskielto"] != "E") or ($row["kerayspvm"] != $edkerayspvm and $edkerayspvm != 'x' and $yhtiorow["splittauskielto"] == "K")) {

					// Meid‰n on p‰ivitett‰v‰ tunnusnippu
					if($yhtiorow["splittauskielto"] == "K" and $laskurow["tunnusnippu"] == 0) {
						$query = "UPDATE lasku set tunnusnippu = tunnus where yhtio = '$kukarow[yhtio]' and tunnus = '$laskurow[tunnus]' and tunnusnippu = 0";
						$upresult = mysql_query($query) or pupe_error($query);
						$laskurow["tunnusnippu"] = $laskurow["tunnus"];
					}

					// Tehd‰‰n uusi otsikko
					$query = "	INSERT into lasku SET
								yhtio_nimi							= '$laskurow[yhtio_nimi]',
								yhtio_osoite						= '$laskurow[yhtio_osoite]',
								yhtio_postino						= '$laskurow[yhtio_postino]',
								yhtio_postitp						= '$laskurow[yhtio_postitp]',
								yhtio_maa							= '$laskurow[yhtio_maa]',
								yhtio_ovttunnus						= '$laskurow[yhtio_ovttunnus]',
								yhtio_kotipaikka					= '$laskurow[yhtio_kotipaikka]',
								yhtio_toimipaikka					= '$laskurow[yhtio_toimipaikka]',
								alatila								= 'A',
								alv									= '$laskurow[alv]',
								chn									= '$laskurow[chn]',
								comments							= '$laskurow[comments]',
								erikoisale							= '$laskurow[erikoisale]',
								erpcm								= '$laskurow[erpcm]',
								kapvm								= '$laskurow[kapvm]',
								kasumma								= '$laskurow[kasumma]',
								kerayspvm							= '$laskurow[kerayspvm]',
								keraysvko							= '$laskurow[keraysvko]',
								ketjutus							= '$laskurow[ketjutus]',
								laatija								= '$laskurow[laatija]',
								laskunro							= '$laskurow[laskunro]',
								laskutusvkopv						= '$laskurow[laskutusvkopv]',
								luontiaika							= '$laskurow[luontiaika]',
								maa 								= '$laskurow[maa]',
								maksuehto							= '$laskurow[maksuehto]',
								myyja								= '$laskurow[myyja]',
								nimi								= '$laskurow[nimi]',
								nimitark							= '$laskurow[nimitark]',
								osoite								= '$laskurow[osoite]',
								ovttunnus							= '$laskurow[ovttunnus]',
								postino								= '$laskurow[postino]',
								postitp								= '$laskurow[postitp]',
								tapvm								= '$laskurow[tapvm]',
								kassalipas							= '$laskurow[kassalipas]',
								tila								= 'N',
								tilausvahvistus						= '$laskurow[tilausvahvistus]',
								toim_maa							= '$laskurow[toim_maa]',
								toim_nimi							= '$laskurow[toim_nimi]',
								toim_nimitark						= '$laskurow[toim_nimitark]',
								toim_osoite							= '$laskurow[toim_osoite]',
								toim_ovttunnus						= '$laskurow[toim_ovttunnus]',
								toim_postino						= '$laskurow[toim_postino]',
								toim_postitp						= '$laskurow[toim_postitp]',
								toimaika							= '$laskurow[toimaika]',
								toimvko								= '$laskurow[toimvko]',
								toimitusehto						= '$laskurow[toimitusehto]',
								kohdistettu 						= '$laskurow[kohdistettu]',
								toimitustapa						= '$laskurow[toimitustapa]',
								valkoodi							= '$laskurow[valkoodi]',
								vienti_kurssi						= '$laskurow[vienti_kurssi]',
								vanhatunnus							= '$laskurow[tunnus]',
								tunnusnippu							= '$laskurow[tunnusnippu]',
								verkkotunnus						= '$laskurow[verkkotunnus]',
								vienti 								= '$laskurow[vienti]',
								viesti								= '$laskurow[viesti]',
								tilausyhteyshenkilo					= '$laskurow[tilausyhteyshenkilo]',
								asiakkaan_tilausnumero				= '$laskurow[asiakkaan_tilausnumero]',
								kohde								= '$laskurow[kohde]',
								viikorkopros						= '$laskurow[viikorkopros]',
								viite								= '$laskurow[viite]',
								varasto 							= '$varasto',
								liitostunnus    					= '$laskurow[liitostunnus]',
								yhtio								= '$laskurow[yhtio]',
								clearing							= '$laskurow[clearing]',
								tilaustyyppi						= '$laskurow[tilaustyyppi]',
								osatoimitus							= '$laskurow[osatoimitus]',
								ytunnus								= '$laskurow[ytunnus]',
								jaksotettu							= '$laskurow[jaksotettu]',
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
								vahennettava_era 					= '$laskurow[vahennettava_era]',
								ultilno								= '$ultilno',
								piiri 								= '$laskurow[piiri]'";
					$uusires = mysql_query($query) or pupe_error($query);
					$laskurow['tunnus'] = (string) mysql_insert_id();
					$tilausnumerot[] = $laskurow["tunnus"];
				}
				else {
					//p‰ivitet‰‰n laskulle tieto mihin varastoon se kuuluu
					$query = "	UPDATE lasku set
								varasto 							= '$varasto',
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

				//	edellinen kerayspvm talteen
				$edkerayspvm = $row["kerayspvm"];

				//jos tilausrivin laskunumero on eri
				if ($row['otunnus'] != $laskurow['tunnus']) {
					$query = "UPDATE tilausrivi set otunnus='$laskurow[tunnus]' where tunnus='$row[tunnus]' and yhtio='$kukarow[yhtio]'";
					$ohno  = mysql_query($query) or pupe_error($query);
				}
			}

			// Jos tilaus splittaantui moneen osaan
			if(count($tilausnumerot) > 1) {
				foreach ($tilausnumerot as $tilausnumero) {
					$upviesti = t("Tilaus jakaantunut")." ".count($tilausnumerot)." ".t("tilaukseksi")."!";

					$query = "	UPDATE lasku set sisviesti2 = concat_ws(' ', sisviesti2, '$upviesti')
								where yhtio='$kukarow[yhtio]' and tunnus='$tilausnumero'";
					$upresult = mysql_query($query) or pupe_error($query);
				}
			}

			// Laitetaan splittien toim ja kerpvm ‰t kuntoon / viikkojutut on pakko nollata splitille
			if($yhtiorow["splittauskielto"] == "K" and count($tilausnumerot) > 1) {
				foreach ($tilausnumerot as $tilausnumero) {

					$query = "	SELECT min(kerayspvm) kerayspvm, min(toimaika) toimaika
					 			from tilausrivi
								where yhtio='$kukarow[yhtio]' and otunnus='$tilausnumero'";
					$result = mysql_query($query) or pupe_error($query);
					$toimaikatilrirow = mysql_fetch_array($result);

					$query = "	SELECT kerayspvm, toimaika
								from lasku
								where yhtio='$kukarow[yhtio]' and tunnus='$tilausnumero'";
					$result = mysql_query($query) or pupe_error($query);
					$toimaikalaskurow = mysql_fetch_array($result);

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

			//tyhjennet‰‰n muuttujat
			$varasto 	= '';
			$edvarasto 	= '';

			//loopataan kaikki yhdest‰ tilausesta syntyneet l‰hetteet
			if($laskurow["tunnusnippu"] > 0) {
				//
				// LƒHETETƒƒN TILAUSVAHVISTUS
				//
				// T‰ss‰ haarassa tulostetaan tilausvahvistus KOKO tilauksesta

				if (strpos(" ".$laskurow['tilausvahvistus'],'3'))
					$naytatvale = 3; // jos mell‰ on tilausvahvistuksessa kolmonen, ei haluta n‰hd‰ hintoja, pelk‰st‰‰n kpl-m‰‰r‰t
				else if (strpos(" ".$laskurow['tilausvahvistus'],'2'))
					$naytatvale = 2; // jos mell‰ on tilausvahvistuksessa kakkonen, ei haluta n‰h‰ aleja
				else
					$naytatvale = 1; // halutaan n‰h‰ alet

				if (strpos(" ".$laskurow['tilausvahvistus'],'E'))
					require("tilausvahvistus-edi.inc");

				if (strpos(" ".$laskurow['tilausvahvistus'],'S'))
					require("tilausvahvistus-email.inc");

				if (strpos(" ".$laskurow['tilausvahvistus'],'F'))
					require("tilausvahvistus-fax.inc");

				if (strpos(" ".$laskurow['tilausvahvistus'],'O'))
					require("tilausvahvistus-email.inc");

				if (strpos(" ".$laskurow['tilausvahvistus'],'U'))
					require("tilausvahvistus-futursoft.inc");

				if (strpos(" ".$laskurow['tilausvahvistus'],'K')) {
					$query = "	SELECT komento
								FROM kirjoittimet
								WHERE yhtio = '$kukarow[yhtio]' and
								tunnus = '$kukarow[kirjoitin]'";
					$tilvares = mysql_query($query) or pupe_error($query);

					if ($tilvakir = mysql_fetch_array($tilvares)) {
						$komento["Tilausvahvistus"] = $tilvakir["komento"];

						require("tulosta_tilausvahvistus_pdf.inc");
					}
				}

				///* T‰ss‰ katotaan onko l‰hetteell‰ tosiaan mit‰‰n ker‰tt‰v‰‰ *///

				//	Ja rullataan kaikki tilaukset l‰pi
				foreach ($tilausnumerot as $tilausnumero) {
					//luetaan laskun tiedot uudestaan t‰ss‰, per splitti
					$query = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$tilausnumero'";
					$result = mysql_query($query) or pupe_error($query);
					$laskurow = mysql_fetch_array($result);

					$kutsuja = "tilaus-valmis.inc";

					require("tilaus-valmis-valitsetila.inc");
				}
			}
			else {
				foreach ($tilausnumerot as $tilausnumero) {
					//luetaan laskun tiedot uudestaan t‰ss‰, per splitti
					$query = "select * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$tilausnumero'";
					$result = mysql_query($query) or pupe_error($query);
					$laskurow = mysql_fetch_array($result);

					//
					// LƒHETETƒƒN TILAUSVAHVISTUS
					//
					// tulostetaan t‰ss‰, niin saadaan vahvistukseen koko tilaus, ennenkun sen splitaatan eri varastoihin

					if (strpos(" ".$laskurow['tilausvahvistus'],'3'))
						$naytatvale = 3; // jos mell‰ on tilausvahvistuksessa kolmonen, ei haluta n‰hd‰ hintoja, pelk‰st‰‰n kpl-m‰‰r‰t
					else if (strpos(" ".$laskurow['tilausvahvistus'],'2'))
						$naytatvale = 2; // jos mell‰ on tilausvahvistuksessa kakkonen, ei haluta n‰h‰ aleja
					else
						$naytatvale = 1; // halutaan n‰h‰ alet

					if (strpos(" ".$laskurow['tilausvahvistus'],'E'))
						require("tilausvahvistus-edi.inc");

					if (strpos(" ".$laskurow['tilausvahvistus'],'S'))
						require("tilausvahvistus-email.inc");

					if (strpos(" ".$laskurow['tilausvahvistus'],'F'))
						require("tilausvahvistus-fax.inc");

					if (strpos(" ".$laskurow['tilausvahvistus'],'O'))
						require("tilausvahvistus-email.inc");

					if (strpos(" ".$laskurow['tilausvahvistus'],'U'))
						require("tilausvahvistus-futursoft.inc");

					if (strpos(" ".$laskurow['tilausvahvistus'],'K')) {
						$query = "	SELECT komento
									FROM kirjoittimet
									WHERE yhtio = '$kukarow[yhtio]' and
									tunnus = '$kukarow[kirjoitin]'";
						$tilvares = mysql_query($query) or pupe_error($query);

						if ($tilvakir = mysql_fetch_array($tilvares)) {
							$komento["Tilausvahvistus"] = $tilvakir["komento"];

							require("tulosta_tilausvahvistus_pdf.inc");
						}
					}

					///* T‰ss‰ katotaan onko l‰hetteell‰ tosiaan mit‰‰n ker‰tt‰v‰‰ *///
					$kutsuja = "tilaus-valmis.inc";

					require("tilaus-valmis-valitsetila.inc");
				}
			}
		}
	} // end jos yksikin rivi ker‰tty else haara
}
//tyhjennet‰‰n ett‰ se ei kummittele uudella tilauksella
$hinta = '';

// tilaus ei en‰‰ kesken...
$query	= "UPDATE kuka set kesken=0 where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
$result = mysql_query($query) or pupe_error($query);

if ($silent == "") {
	echo $tilaus_valmis_ulos;
}

?>
