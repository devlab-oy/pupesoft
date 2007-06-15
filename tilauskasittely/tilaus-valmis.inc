<?php
// tarvitaan $kukarow[yhtio], $kukarow[kesken], $laskurow array, $yhtiorow array

$tilaus_valmis_ulos = "";

///* Reload ja back-nappulatsekki *///
if ($kukarow["kesken"] == '' or $kukarow["kesken"] == '0') {
	echo "<font class='error'> ".t("Taisit painaa takaisin tai päivitä nappia. Näin ei saa tehdä")."! </font>";
	exit;
}

// päivitetään vanhatunnus laskulle
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
	// LÄHETETÄÄN TILAUSVAHVISTUS
	//
	// tulostetaan tässä, niin saadaan vahvistukseen koko tilaus, ennenkun sen splitaatan eri varastoihin

	if (strpos(" ".$laskurow['tilausvahvistus'],'2'))
		$naytatvale = 2; // jos mellä on tilausvahvistuksessa kakkonen, ei haluta nähä aleja
	else
		$naytatvale = 1; // halutaan nähä alet

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

	// Aktivoituiko "Odottaa jt-rivejä" tilaus?
	if (($laskurow["tila"] == 'N' or $laskurow["tila"] == 'G') and $laskurow["alatila"] == 'T') {
		// Onko jotain tulostettavaa?
		$query = "	select *
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

	// otetaan maksuehto tässä heti alussa selville.. käteinen ja itsetulostus muuttaa asioita
	$query = "select * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
	$result = mysql_query($query) or pupe_error($query);

	$kateinen = "";
	$jaksotettu = "";
	$itsetulostus = "";

	if (mysql_num_rows($result)==1) {
		$maksuehtorow = mysql_fetch_array($result);

		// jos kyseessä on käteiskauppaa
		if ($maksuehtorow['kateinen']!='') {
			$kateinen = "X";
		}
		// jos kyseessä on jaksotusta
		if ($maksuehtorow['jaksotettu']!='') {
			$jaksotettu = "X";
		}
		//jos kyseessä on itsetulostusmaksuehto
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

	// Katotaan, onko tämä jo kerätty...
	$query = "	select tilausrivi.*
				from tilausrivi use index (yhtio_otunnus)
				join tuote on tilausrivi.yhtio=tuote.yhtio and tilausrivi.tuoteno=tuote.tuoteno
				where tilausrivi.yhtio='$kukarow[yhtio]'
				and tilausrivi.otunnus='$laskurow[tunnus]'
				and tilausrivi.keratty!=''
				$lisasalker";
	$keres = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($keres) > 0 or ($laskurow["tila"] == 'L' and ($laskurow["alatila"] == 'C' or $laskurow["alatila"] == 'B' or $laskurow["alatila"] == 'D' or $laskurow["alatila"] == 'E'))) {
		//tila L ja alatila C, B, D, E niin tilaus on kerätty otsikko
		//jos otsikko sanoo, että tilaus on kerätty mutta rivit kuitenkaan ei ole kerätty niin merkataan ne kumminkin kerätyiksi
		//tai jos yksikin rivi on kerätty (lähetettä ei tulosteta uudestaan) niin päivitetään keräämättömät rivit kerätyksi aktiiviselle käyttäjälle
		
		$query = "	UPDATE tilausrivi 
					SET keratty='$kukarow[kuka]', kerattyaika=now() 
					WHERE yhtio='$kukarow[yhtio]' 
					and otunnus='$laskurow[tunnus]' 
					and keratty='' 
					and var != 'J'";
		$result = mysql_query($query) or pupe_error($query);

		//Laitetaan rivit toimitetuiksi jos otsikko sanoo toimitettu
		if ($laskurow["alatila"] == 'D' or $laskurow["alatila"] == 'E') {
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

		$tilaus_valmis_ulos .= "<font class='message'>".t("Tilauksella oli jo kerättyjä rivejä, joten lähetettä ei tulosteta").".<br>".t("Jos haluat lähetteen tulosta lähetteen kopio. Tilausnumero on")." $laskurow[tunnus].</font><br><br>";
		
		// jos on myyntitilaus ja ei haluttu tulostaa lähetettä, merkataan rivit kerätyksi ja toimitetuksi ja laitetaan alatila D
		if ($laskurow['eilahetetta'] != '' or $laskurow['sisainen'] != '') {
			if ($kateinen == 'X' or $itsetulostus == "X" or $laskurow["vienti"] != '') {
				// jos kyseessä on käteismyyntiä tai vientiä, merkataan tilaukset kerätyksi mutta ei toimitetuksi ja alatila C/B
				// myös jos maksuehtolla on itsetulostustäppä trigattuna niin laitetaan se tähän haaraan
				// Merkataan rivit kerätyiksi. JT-rivejä ei merkata kerätyiksi.

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
				// jos kyseessä  EI ole käteismyyntiä eikä itsetulostusta niin merkataan tilaus toimitetuksi
				// tämä on kotimaan myyntia, mutta ei käteismyyntiä
				//tässä toimitetaan ja kerätään tilaus samantien jos ei_lähetettä nappi oli ruksattuna
				// Merkataan rivit kerätyiksi ja toimitetuiksi. JT-rivejä ei merkata kerätyiksi.

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
				$silent		 	= "KYLLA";

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
		//tässä käydään läpi vähän näitä rahtijuttuja
		//jos rahtihinnoittelu tapahtuu tilauksen hinnan (alviton) perusteella niin tehdään se tässä
		//tämä olkoon hyvä arvaus rahtihinnasta, sitä voidaan sitten myöhemmin tarkentaa
		if ($yhtiorow["rahti_hinnoittelu"] != "") {

			$query = "	select tunnus
						from tilausrivi
						where yhtio = '$kukarow[yhtio]'
						and otunnus = '$laskurow[tunnus]'
						and tuoteno = '$yhtiorow[rahti_tuotenumero]'";
			$result = mysql_query($query) or pupe_error($query);

			//jos tilauksella on jo rahtikulu niin ei lisätä sitä uudestaan
			if (mysql_num_rows($result) == 0) {

				$query = "select * from toimitustapa where yhtio='$kukarow[yhtio]' and selite='$laskurow[toimitustapa]'";
				$result = mysql_query($query) or pupe_error($query);
				$torow = mysql_fetch_array($result);

				// jos toimitustapana on joku nouto niin ei lisätä rahtikulua
				if ($torow["nouto"] == "") {
					if ($yhtiorow["alv_kasittely"] == '') {
						$query = "	SELECT round(((varattu+jt)*hinta*(1-(ale/100)))/(1+alv/100),2) rivihinta, netto
									FROM tilausrivi
									WHERE otunnus 	= '$laskurow[tunnus]'
									and yhtio	  	= '$kukarow[yhtio]'
									and tyyppi		= 'L'";
					}
					else {
						$query = "	SELECT round(((varattu+jt)*hinta*(1-(ale/100))),2) rivihinta, netto
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
						$apu2 = round($myht*$apu1,2); 					// erikoisalen määrä
						$apu3 = round((1-$apu1)*$myht,2);				// loppusumma

						//Kakki yhteensä
						$kyht = $apu3 + $nyht;
					}
					else {
						//Kakki yhteensä
						$kyht = $myht + $nyht;
					}

					$query = "	select rahtihinta
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


					//lisätään rahtikulurivi vain jos rahtikulu on erisuuri kuin nolla, nollarivejä on ilmeisesti turha llisätä
					if ($hirow["rahtihinta"] != 0) {
						$query = "select * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$yhtiorow[rahti_tuotenumero]'";
						$rhire = mysql_query($query) or pupe_error($query);
						$trow  = mysql_fetch_array($rhire);

						$otunnus	= $laskurow['tunnus'];
						$hinta		= $hirow["rahtihinta"]; // rahtihinta
						$alv 		= '';
						$trow["alv"]= $laskurow["alv"];
						$nimitys	= $laskurow["toimitustapa"];

						require("alv.inc");

						$query  = "insert into tilausrivi (laadittu, hinta, netto, varattu, tilkpl, otunnus, tuoteno, nimitys, yhtio, tyyppi, alv, kommentti) values  (now(), '$hinta', 'N', '1', '1', '$otunnus', '$trow[tuoteno]', '$nimitys', '$kukarow[yhtio]', 'L', '$alv', '')";
						$addtil = mysql_query($query) or pupe_error($query);

						$tilaus_valmis_ulos .= t("Tilaukselle lisättiin rahtikulua").": $hinta $laskurow[valkoodi]<br>";

						if ($torow["jvkulu"] != 0) {
							$otunnus	= $laskurow['tunnus'];
							$hinta	= $torow["jvkulu"]; // jvkulu
							$alv 		= '';
							$nimitys	= t("Jälkivaatimuskulu");

							require("alv.inc");

							$query  = "insert into tilausrivi (laadittu, hinta, netto, varattu, tilkpl, otunnus, tuoteno, nimitys, yhtio, tyyppi, alv, kommentti) values  (now(), '$hinta', 'N', '1', '1', '$otunnus', '$trow[tuoteno]', '$nimitys', '$kukarow[yhtio]', 'L', '$alv', '')";
							$addtil = mysql_query($query) or pupe_error($query);

							$tilaus_valmis_ulos .= t("Tilaukselle lisättiin jälkivaatimuskulua").": $hinta $laskurow[valkoodi]<br>";
						}
					}
				}
			}
		}

		//jos on kassamyyntiä ja se on käteistä niin tulostetaan lasku heti ilman lähetettä
		if (($kateinen == 'X' and ($kukarow["kassamyyja"] != '' or $kertakassa != "") and $laskurow["vienti"] == '') or ($korkolasku == 'kylla')) {
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

			//tulostetaan käteislasku...
			$laskutettavat	= $laskurow["tunnus"];
			$tee 			= "TARKISTA";
			$laskutakaikki 	= "KYLLA";
			$silent		 	= "KYLLA";

			if ($kukarow["kirjoitin"] != 0 and $valittu_tulostin == 0) {
				$valittu_tulostin = $kukarow["kirjoitin"];
			}
			elseif($valittu_tulostin == 0) {
				$valittu_tulostin = "AUTOMAAGINEN_VALINTA";
			}

			require ("tilauskasittely/verkkolasku.php");
		}
		// jos on myyntitilaus ja ei haluttu tulostaa lähetettä, merkataan rivit kerätyksi ja toimitetuksi ja laitetaan alatila D
		elseif ($laskurow['eilahetetta'] != '' or $laskurow['sisainen'] != '') {
			if ($kateinen == 'X' or $itsetulostus == "X" or $laskurow["vienti"] != '') {
				// jos kyseessä on käteismyyntiä tai vientiä, merkataan tilaukset kerätyksi mutta ei toimitetuksi ja alatila C/B
				// myös jos maksuehtolla on itsetulostustäppä trigattuna niin laitetaan se tähän haaraan
				// Merkataan rivit kerätyiksi. JT-rivejä ei merkata kerätyiksi.

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
				// jos kyseessä  EI ole käteismyyntiä eikä itsetulostusta niin merkataan tilaus toimitetuksi
				// tämä on kotimaan myyntia, mutta ei käteismyyntiä
				//tässä toimitetaan ja kerätään tilaus samantien jos ei_lähetettä nappi oli ruksattuna
				// Merkataan rivit kerätyiksi ja toimitetuiksi. JT-rivejä ei merkata kerätyiksi.

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
				$silent		 	= "KYLLA";

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
			// Katotaan mitä tehdään lähetteelle
			// Ensin on pakko splitata lähete varastoittain
			//tyhjennetään laskun varastokenttä, siellä oli tieto varastoon optimoinnista mutta nyt se kertoo mihin varastoon lähete kuuluu laittaa
			$laskurow['varasto'] = 0;

			//Toimitustavan hetierä tarvitaan
			$query = "select hetiera from toimitustapa where yhtio='$kukarow[yhtio]' and selite='$laskurow[toimitustapa]'";
			$result = mysql_query($query) or pupe_error($query);
			$totarowx = mysql_fetch_array($result);

			// Maksuehdon jv tieto tarvitaan
			$query = "select jv from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
			$result = mysql_query($query) or pupe_error($query);
			$maehrowx = mysql_fetch_array($result);

			//haetaan kaikkien tilausrivien varastopaikat
			$query = "	SELECT tilausrivi.otunnus, tilausrivi.tunnus, tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso,
						concat(lpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0')) sorttauskentta
						FROM tilausrivi
						LEFT JOIN tuote on tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and tilausrivi.otunnus = '$laskurow[tunnus]'
						and tilausrivi.hyllyalue != ''
						ORDER BY sorttauskentta";
			$result = mysql_query($query) or pupe_error($query);

			//tässä muuttujassa on sit kaikki tunnukset splitin jälkeen
			$tilausnumerot	 = array();
			$tilausnumerot[] = $laskurow["tunnus"];

			//mihin varastoon lähete kuuluu. aluksi x
			$varasto   = 'x';
			$edvarasto = 'x';

			while ($row = mysql_fetch_array($result)) {

				$varasto = kuuluukovarastoon($row["hyllyalue"], $row["hyllynro"]);

				$query = "select * from varastopaikat where yhtio='$kukarow[yhtio]' and tunnus='$varasto'";
				$varaston_res = mysql_query($query) or pupe_error($query);
				$varaston_row = mysql_fetch_array($varaston_res);

				$ultilno = '';
				
				// katotaan pitääkö tilaukselle tehdä intrastat
				// varasto pitää löytyä, toimitusosoitteen maa ja varastonmaa pitää löytyä ja kauppatapahtuman_luonnetta ei saa olla annettu vielä
				if (mysql_num_rows($varaston_res) == 1 and $laskurow["toim_maa"] != "" and $varaston_row["maa"] != "") {

					$query = "select distinct koodi from maat where koodi in ('$laskurow[toim_maa]','$varaston_row[maa]') and eu = 'ON'";
					$euapuresult = mysql_query($query) or pupe_error($query);

					// kummatkin maat pitää olla EU:ssa
					if (mysql_num_rows($euapuresult) == 2) {

						if ($laskurow["toim_maa"] == $varaston_row["maa"]) {
							$laskurow["kauppatapahtuman_luonne"] = '999'; // intrastattia ei lähetetä tulliin kun varasto ja toimitusosoite on samassa maassa
							$ultilno = '';
						}
						elseif ($varaston_row["maa"] == $yhtiorow["maa"]) {
							$ultilno = '-1'; // miinus yks tarkoittaa, että lisätiedot pitää syöttää ja VIENTI-intrastat pitää lähettää
						}
						elseif ($laskurow["toim_maa"] == $yhtiorow["maa"]) {
							$ultilno = '-2'; // miinus kaks tarkoittaa, että lisätiedot pitää syöttää ja TUONTI-intrastat pitää lähettää

							// katotaan onko toimitustavalle jotain intrastat oletuksia
							$query  = "select * from toimitustapa where yhtio='$kukarow[yhtio]' and selite='$laskurow[toimitustapa]'";
							$toiresult = mysql_query($query) or pupe_error($query);
							$aputoimirow = mysql_fetch_array($toiresult);

							$laskurow["kauppatapahtuman_luonne"] 			= $aputoimirow["kauppatapahtuman_luonne"];
							$laskurow["kuljetusmuoto"]						= $aputoimirow["kuljetusmuoto"];
							$laskurow["sisamaan_kuljetus"]					= $aputoimirow["sisamaan_kuljetus"];
							$laskurow["sisamaan_kuljetusmuoto"]  			= $aputoimirow["sisamaan_kuljetusmuoto"];
							$laskurow["sisamaan_kuljetus_kansallisuus"]		= $aputoimirow["sisamaan_kuljetus_kansallisuus"];
							$laskurow["kontti"]								= $aputoimirow["kontti"];
							$laskurow["aktiivinen_kuljetus"] 				= $aputoimirow["aktiivinen_kuljetus"];
							$laskurow["aktiivinen_kuljetus_kansallisuus"]	= $aputoimirow["aktiivinen_kuljetus_kansallisuus"];
							$laskurow["poistumistoimipaikka"] 				= $aputoimirow["poistumistoimipaikka"];
							$laskurow["poistumistoimipaikka_koodi"] 		= $aputoimirow["poistumistoimipaikka_koodi"];
							$laskurow["bruttopaino"] 						= $aputoimirow["bruttopaino"];
							$laskurow["lisattava_era"] 						= $aputoimirow["lisattava_era"];
							$laskurow["vahennettava_era"] 					= $aputoimirow["vahennettava_era"];
							$laskurow["maa_maara"] 							= $aputoimirow["maa_maara"];
							$laskurow["maa_lahetys"] 						= $varaston_row["maa"];
						}

					}

				}

				//varaasto vaihtuu, splitataan tilaus, paitsi jos kyseessä on jv-tilaus jonka rahtikirja tulostetaan heti, tai jos spliauskielto on trigattu
				if ($varasto != $edvarasto and $edvarasto != 'x' and ($maehrowx["jv"] == '' or $totarowx["hetiera"] != 'H') and $laskurow["splittauskielto"] != "E") {

					//tehdään uusi otsikko
					$query = "INSERT into lasku SET
							yhtio_nimi							= '$laskurow[yhtio_nimi]',
							yhtio_osoite						= '$laskurow[yhtio_osoite]',
							yhtio_postino						= '$laskurow[yhtio_postino]',
							yhtio_postitp						= '$laskurow[yhtio_postitp]',
							yhtio_maa							= '$laskurow[yhtio_maa]',
							yhtio_ovttunnus						= '$laskurow[yhtio_ovttunnus]',
							yhtio_kotipaikka					= '$laskurow[yhtio_kotipaikka]',
							alatila								= 'A',
							alv									= '$laskurow[alv]',
							chn									= '$laskurow[chn]',
							comments							= '$laskurow[comments]',
							erikoisale							= '$laskurow[erikoisale]',
							erpcm								= '$laskurow[erpcm]',
							kapvm								= '$laskurow[kapvm]',
							kasumma								= '$laskurow[kasumma]',
							kerayspvm							= '$laskurow[kerayspvm]',
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
							viikorkopros						= '$laskurow[viikorkopros]',
							viite								= '$laskurow[viite]',
							viitetxt							= '$laskurow[viitetxt]',
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
							ultilno								= '$ultilno'";
					$uusires = mysql_query($query) or pupe_error($query);
					$laskurow['tunnus'] = (string) mysql_insert_id();
					$tilausnumerot[] = $laskurow["tunnus"];
				}
				elseif ($laskurow["varasto"] == 0) {
					//päivitetään laskulle tieto mihin varastoon se kuuluu
					$query  ="	update lasku set
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
								where yhtio ='$kukarow[yhtio]' and
								tunnus = '$laskurow[tunnus]'";
					$varresult = mysql_query($query) or pupe_error($query);

					$laskurow["varasto"] = $varasto;
				}
				//edellinen varasto talteen
				$edvarasto=$varasto;

				//jos tilausrivin laskunumero on eri
				if ($row['otunnus'] != $laskurow['tunnus']) {
					$query = "update tilausrivi set otunnus='$laskurow[tunnus]' where tunnus='$row[tunnus]' and yhtio='$kukarow[yhtio]'";
					$ohno  = mysql_query($query) or pupe_error($query);
				}
			}

			$query = "select tunnus, sisviesti2 from lasku where yhtio = '$kukarow[yhtio]' and vanhatunnus = '$laskurow[tunnus]'";
			$result = mysql_query($query) or pupe_error($query);
			if (mysql_num_rows($result) > 1) {

				$montakotilausta = mysql_num_rows($result);

				while ($row = mysql_fetch_array($result)) {

					$upviesti = $row["sisviesti2"]." ".t("tilaus jakaantunut")." $montakotilausta ".t("tilaukseksi")."!";

					$query = "update lasku set sisviesti2 = '$upviesti' where yhtio = '$kukarow[yhtio]' and tunnus = '$row[tunnus]'";
					$upresult = mysql_query($query) or pupe_error($query);
				}
			}

			//tyhjennetään muuttujat
			$varasto 	= '';
			$edvarasto 	= '';

			//loopataan kaikki yhdestä tilausesta syntyneet lähetteet
			foreach ($tilausnumerot as $tilausnumero) {
				//luetaan laskun tiedot uudestaan tässä, per splitti
				$query = "select * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$tilausnumero'";
				$result = mysql_query($query) or pupe_error($query);
				$laskurow = mysql_fetch_array($result);

				//
				// LÄHETETÄÄN TILAUSVAHVISTUS
				//
				// tulostetaan tässä, niin saadaan vahvistukseen koko tilaus, ennenkun sen splitaatan eri varastoihin

				if (strpos(" ".$laskurow['tilausvahvistus'],'2'))
					$naytatvale = 2; // jos mellä on tilausvahvistuksessa kakkonen, ei haluta nähä aleja
				else
					$naytatvale = 1; // halutaan nähä alet

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

				///* Tässä katotaan onko lähetteellä tosiaan mitään kerättävää *///
				$kutsuja = "tilaus-valmis.inc";

				require("tilaus-valmis-valitsetila.inc");
			}
		}
	} // end jos yksikin rivi kerätty else haara
}
//tyhjennetään että se ei kummittele uudella tilauksella
$hinta = '';

// tilaus ei enää kesken...
$query	= "update kuka set kesken=0 where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
$result = mysql_query($query) or pupe_error($query);

if ($silent == "") {
	echo $tilaus_valmis_ulos;
}

?>