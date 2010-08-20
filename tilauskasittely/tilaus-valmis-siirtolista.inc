<?php

	if ($toim == "VALMISTAASIAKKAALLE" or $toim == "VALMISTAVARASTOON") {
		$tvs_tila 	= "V";
		$tvs_tyyppi	= "'V'";
		$tvs_tyyppi_2	= ",'W','M'";
	}
	elseif ($toim == "SIIRTOLISTA" or $toim == "MYYNTITILI") {
		$tvs_tila 	= "G";
		$tvs_tyyppi	= "'G'";
	}
	elseif ($toim == "SIIRTOTYOMAARAYS") {
		$tvs_tila 	= "S";
		$tvs_tyyppi	= "'G'";
	}

	if ($yhtiorow["kerataanko_saldottomat"] == '') {
		$lisasalker = " and tuote.ei_saldoa = '' ";
	}
	else {
		$lisasalker = " ";
	}

	// päivitetään vanhatunnus laskulle
	$query  = "	UPDATE lasku SET
	 			vanhatunnus = tunnus
				WHERE yhtio = '$kukarow[yhtio]'
				AND tunnus = '$laskurow[tunnus]'
				AND vanhatunnus = 0";
	$oldtur = mysql_query($query) or pupe_error($query);

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

	//otetaan varmuuden vuoksi alkuperäisen otsikon tunnus
	$alkuplaskutunnus = $laskurow["tunnus"];

	// katotaan ihan aluksi, onko tämä jo kerätty...
	$query = "	SELECT tilausrivi.*
				from tilausrivi use index (yhtio_otunnus)
				join tuote on tilausrivi.yhtio=tuote.yhtio and tilausrivi.tuoteno=tuote.tuoteno
				where tilausrivi.yhtio='$kukarow[yhtio]'
				and tilausrivi.otunnus='$laskurow[tunnus]'
				and tilausrivi.keratty!=''
				$lisasalker";
	$keres = mysql_query($query) or pupe_error($query);

	// jos yksikin rivi on kerätty (lähetettä ei tulosteta uudestaan)...
	// tai jos ollaan ruksattu suoraan valmiiksi (sisäiset työmääräykset)
	if (mysql_num_rows($keres) > 0 or $laskurow["eilahetetta"] != '') {

		//...niin päivitetään keräämättömät rivit kerätyksi aktiiviselle käyttäjälle
		$query = "	UPDATE tilausrivi use index (yhtio_otunnus)
					SET keratty = '$kukarow[kuka]', kerattyaika=now()
					where yhtio = '$kukarow[yhtio]'
					and otunnus = '$laskurow[tunnus]'
					and keratty = ''
					and var not in ('P','J','S')";
		$result = mysql_query($query) or pupe_error($query);

		if ($toim == "VALMISTAASIAKKAALLE" or $toim == "VALMISTAVARASTOON") {
			echo "<font class='message'>".t("Valmistuksella oli jo kerättyjä rivejä, joten lähetettä ei tulosteta").".<br>".t("Jos haluat lähetteen tulosta lähetteen kopio, valmistusnumero on")." $laskurow[tunnus].</font><br><br>";
		}
		else {
			echo "<font class='message'>".t("Siirtolistalla oli jo kerättyjä rivejä, joten lähetettä ei tulosteta").".<br>".t("Jos haluat lähetteen tulosta lähetteen kopio, siirtolistanumero on")." $laskurow[tunnus].</font><br><br>";
		}

		if ($laskurow["eilahetetta"] != '') {
			$query  = "	UPDATE lasku
						set alatila = 'C'
						where yhtio = '$kukarow[yhtio]'
						and tunnus  = '$laskurow[tunnus]'";
			$varresult = mysql_query($query) or pupe_error($query);
		}
	}
	else {

		//laitetaan lista tulostusjonoon
		if ($tulostetaan != 'OK') {
			///* Ensin on pakko splitata lähete varastoittain *///
			//tyhjennetään laskun varastokenttä, siellä oli tieto varastoon optimoinnista mutta nyt se kertoo mihin varastoon lähete kuuluu laittaa
			$laskurow['varasto'] = 0;

			//Toimitustava hetierä tarvitaan
			$query = "SELECT tulostustapa from toimitustapa where yhtio='$kukarow[yhtio]' and selite='$laskurow[toimitustapa]'";
			$result = mysql_query($query) or pupe_error($query);
			$totarowx = mysql_fetch_array($result);

			// Maksuehdon jv tieto tarvitaan
			$query = "SELECT jv from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
			$result = mysql_query($query) or pupe_error($query);
			$maehrowx = mysql_fetch_array($result);

			//haetaan kaikkien tilausrivien varastopaikat
			$query = "	SELECT otunnus, tunnus, hyllyalue, hyllynro, hyllyvali, hyllytaso,
						concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0')) sorttauskentta
						FROM tilausrivi
						WHERE otunnus 	= '$laskurow[tunnus]'
						and yhtio		= '$kukarow[yhtio]'
						and hyllyalue  != ''
						ORDER BY sorttauskentta";
			$result = mysql_query($query) or pupe_error($query);

			//tässä muuttujassa on sit kaikki tunnukset splitin jälkeen
			$tvs_tilausnumerot	 = array();
			$tvs_tilausnumerot[] = $laskurow["tunnus"];

			//mihin varastoon lähete kuuluu. aluksi x
			$varasto   = 'x';
			$edvarasto = 'x';
			$tulostusalue = $edtulostusalue = "x";

			while ($row = mysql_fetch_array($result)) {

				$varasto = kuuluukovarastoon($row["hyllyalue"], $row["hyllynro"]);
				$tulostusalue = onkotulostusalueita($row["hyllyalue"], $row["hyllynro"], $varasto);

				$query = "SELECT * from varastopaikat where yhtio = '$kukarow[yhtio]' and tunnus = '$varasto'";
				$varaston_res = mysql_query($query) or pupe_error($query);
				$varaston_row = mysql_fetch_array($varaston_res);

				$ultilno = tarvitaanko_intrastat($varaston_row["maa"], $laskurow["toim_maa"]);

				$laskurow["maa_maara"] = $laskurow["toim_maa"];
				$laskurow["maa_lahetys"] = $varaston_row["maa"];

				if ($ultilno == '-1') {
					// vienti-ilmoitus
					// katotaan onko jotain oletuksia varaston takaa
					$query = "SELECT * FROM varastopaikat WHERE yhtio = '$kukarow[yhtio]' AND tunnus in ('$clearing', '$varasto') AND maa = '$yhtiorow[maa]'";
					$result_intra = mysql_query($query) or pupe_error($query);
					$intrarow = mysql_fetch_array($result_intra);

					$laskurow["aktiivinen_kuljetus"] 				= $intrarow["aktiivinen_kuljetus"];
					$laskurow["aktiivinen_kuljetus_kansallisuus"]	= $intrarow["aktiivinen_kuljetus_kansallisuus"];
					$laskurow["bruttopaino"] 						= $intrarow["bruttopaino"];
					$laskurow["kauppatapahtuman_luonne"] 			= $intrarow["kauppatapahtuman_luonne"];
					$laskurow["kontti"]								= $intrarow["kontti"];
					$laskurow["kuljetusmuoto"]						= $intrarow["kuljetusmuoto"];
					$laskurow["lisattava_era"] 						= $intrarow["lisattava_era"];
					$laskurow["poistumistoimipaikka"] 				= $intrarow["poistumistoimipaikka"];
					$laskurow["poistumistoimipaikka_koodi"] 		= $intrarow["poistumistoimipaikka_koodi"];
					$laskurow["sisamaan_kuljetus"]					= $intrarow["sisamaan_kuljetus"];
					$laskurow["sisamaan_kuljetusmuoto"]  			= $intrarow["sisamaan_kuljetusmuoto"];
					$laskurow["sisamaan_kuljetus_kansallisuus"]		= $intrarow["sisamaan_kuljetus_kansallisuus"];
					$laskurow["vahennettava_era"] 					= $intrarow["vahennettava_era"];
				}
				elseif ($ultilno == '-2') {
					// tuonti-ilmoitus
					// katotaan onko jotain oletuksia toimitustavan takana
					$query  = "SELECT * FROM toimitustapa WHERE yhtio = '$kukarow[yhtio]' AND selite = '$laskurow[toimitustapa]'";
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
					// intrastattia ei lähetetä tulliin kun varasto ja toimitusosoite on samassa maassa
					$laskurow["kauppatapahtuman_luonne"] = '999';
				}

				//varaasto vaihtuu, splitataan tilaus, paitsi jos kyseessä on jv-tilaus jonka rahtikirja tulostetaan heti, tai jos spliauskielto on trigattu
				if ((($varasto != $edvarasto and $edvarasto != 'x') or ($tulostusalue != $edtulostusalue and $edtulostusalue != 'x' and $edtulostusalue != '' and $toim == "SIIRTOLISTA")) and ($maehrowx["jv"] == '' or $totarowx["tulostustapa"] != 'H') and $laskurow["splittauskielto"] != "E" and $toim != "SIIRTOTYOMAARAYS") {

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
							case 'vanhatunnus':
								$values .= ", '$alkuplaskutunnus'";
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

					$query = "	SELECT *
								FROM laskun_lisatiedot
								WHERE yhtio = '$kukarow[yhtio]'
								AND otunnus = '$laskurow[tunnus]'";
					$lisatiedot_result = mysql_query($query) or pupe_error($query);
					$lisatiedot_row = mysql_fetch_array($lisatiedot_result);

					$laskurow['tunnus'] = (string) mysql_insert_id();
					$tvs_tilausnumerot[] = $laskurow["tunnus"];

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
					$lisatiedot_result = mysql_query($query) or pupe_error($query);
				}
				else {
					//päivitetään laskulle tieto mihin varastoon se kuuluu
					$query  ="	UPDATE lasku SET
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
								where yhtio = '$kukarow[yhtio]' and
								tunnus = '$laskurow[tunnus]'";
					$varresult = mysql_query($query) or pupe_error($query);

					$laskurow["varasto"] = $varasto;
				}
				//edellinen varasto talteen
				$edvarasto=$varasto;

				// edellinen tulostusalue talteen
				$edtulostusalue = $tulostusalue;

				//jos tilausrivin laskunumero on eri
				if ($row['otunnus'] != $laskurow['tunnus']) {
					$query = "UPDATE tilausrivi set otunnus='$laskurow[tunnus]' where tunnus='$row[tunnus]' and yhtio='$kukarow[yhtio]'";
					$ohno  = mysql_query($query) or pupe_error($query);
				}
			}

			$query = "SELECT tunnus, sisviesti2 from lasku where yhtio = '$kukarow[yhtio]' and vanhatunnus = '$alkuplaskutunnus' and alatila = ''";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) > 1) {
				$tilauksia = 1;
				$montakotilausta = mysql_num_rows($result);
				$numerot = implode(', ',$tvs_tilausnumerot);

				while ($row = mysql_fetch_array($result)) {

					$upviesti = $row["sisviesti2"]." ".t("Tilaus jakaantunut")." $montakotilausta ".t("tilaukseksi").": ".$numerot." ".t("Tilaus").": ".$tilauksia."/".$montakotilausta;

					$query = "UPDATE lasku set sisviesti2 = '$upviesti' where yhtio = '$kukarow[yhtio]' and tunnus = '$row[tunnus]'";
					$upresult = mysql_query($query) or pupe_error($query);

					$tilauksia++;
				}
			}

			//tyhjennetään muuttujat
			$varasto 	= '';
			$edvarasto 	= '';

			//loopataan kaikki yhdestä tilausesta syntyneet lähetteet
			foreach ($tvs_tilausnumerot as $tvs_tilausnumero) {

				//luetaan laskun tiedot uudestaan tässä, per splitti
				$query = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$tvs_tilausnumero'";
				$result = mysql_query($query) or pupe_error($query);
				$laskurow = mysql_fetch_array($result);

				// Tässä katotaan onko lähetteellä tosiaan mitään kerättävää
				$query = "	SELECT tuote.tuoteno
							FROM tilausrivi, tuote
							WHERE otunnus = '$laskurow[tunnus]'
							and var in ('','H')
							and (varattu > 0 or tilkpl = 9999.99)
							$lisasalker
							and tuote.yhtio = '$kukarow[yhtio]'
							and tilausrivi.tyyppi in ($tvs_tyyppi$tvs_tyyppi_2)
							and tuote.tuoteno = tilausrivi.tuoteno
							and tilausrivi.yhtio = tuote.yhtio";
				$cresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($cresult) == 0) {
					// Mitään kerättävää ei ooo, mutta jt:tä voi olla
					$query = "	SELECT count(*) riveja, sum(if(var='J', 1, 0)) jteet
								FROM tilausrivi
								WHERE yhtio='$kukarow[yhtio]' and tyyppi in ($tvs_tyyppi) and otunnus='$laskurow[tunnus]'";
					$tresult = mysql_query($query) or pupe_error($query);
					$rivirow = mysql_fetch_array($tresult);

					//Tilauskella on pelkästään jt-rivejä
					if ($rivirow["riveja"] > 0 and $rivirow["jteet"] > 0) {
						//päivtetään tilaus tilaan 'odottaa jt-rivejä'
						$query = "UPDATE lasku SET tila='$tvs_tila', alatila='T' where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[tunnus]'";
						$yresult = mysql_query($query) or pupe_error($query);

						echo "<font class='message'>".t("Siirtolista siirrettiin jälkitoimitukseksi odottamaan tuotteita")." $laskurow[tunnus].<br><br>";
					}
					else {
						// Mitään järkeviä riviä ei ollut, mitätöidään lista
						$query = "UPDATE tilausrivi SET tyyppi='D' where yhtio='$kukarow[yhtio]' and otunnus='$kukarow[kesken]'";
						$result = mysql_query($query) or pupe_error($query);

						$query = "UPDATE lasku SET tila='D', alatila='$laskurow[tila]', comments='$kukarow[nimi] ($kukarow[kuka]) ".t("mitätöi siirtolistan")." ".date("d.m.y @ G:i:s")."' where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
						$result = mysql_query($query) or pupe_error($query);

						echo "<font class='message'>".t("Siirtolista mitätöitiin, koska sillä ei ollut mitään kerättävää")." $laskurow[tunnus].<br><br>";
					}
				}
				else {
					//Laitetaan lista tulostusjonoon
					$query = "	UPDATE lasku
								SET alatila = 'J'
								WHERE yhtio = '$kukarow[yhtio]'
								and tunnus = '$laskurow[tunnus]'
								and tila='$tvs_tila'";
					$result = mysql_query($query) or pupe_error($query);

					if ($toim == "VALMISTAASIAKKAALLE" or $toim == "VALMISTAVARASTOON") {
						echo "<font class='message'>".t("Valmistus siirretty tulostusjonoon")." $laskurow[tunnus].<br><br>";

						if ($toim == "VALMISTAASIAKKAALLE") {
							// Tilausvahvistukset muuttavat tätä niin otetaan talteen
							$laskurow_tvorig = $laskurow;

							//
							// LÄHETETÄÄN TILAUSVAHVISTUS
							//
							// tulostetaan tässä, niin saadaan vahvistukseen koko tilaus, ennenkun sen splitaatan eri varastoihin

							if (strpos($laskurow['tilausvahvistus'], '5') !== FALSE) {
								$naytatvale = 5; // jos mellä on tilausvahvistuksessa vitonen, niin haetaan hinnat toisesta pupesta
							}
							elseif (strpos($laskurow['tilausvahvistus'], '4') !== FALSE) {
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
						}
					}
					elseif ($yhtiorow["varastosiirto_tilausvahvistus"] == "K") {

						// pakotetaan tilausvanvistus PDF muodossa omaan sähköpostiin, ilman hintoja.
						$laskurow["tilausvahvistus"] = "3OP";

						if (strpos($laskurow['tilausvahvistus'], '5') !== FALSE) {
							$naytatvale = 5; // jos mellä on tilausvahvistuksessa vitonen, niin haetaan hinnat toisesta pupesta
						}
						elseif (strpos($laskurow['tilausvahvistus'], '4') !== FALSE) {
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

						// LÄHETETÄÄN TILAUSVAHVISTUS
						require("tilausvahvistus-email.inc");

						echo "<font class='message'>".t("Siirtolista siirretty tulostusjonoon")." $laskurow[tunnus].<br><br>";
					}
				}
			}

			// tilaus ei enää kesken...
			$query	= "UPDATE kuka set kesken=0 where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
			$result = mysql_query($query) or pupe_error($query);
		}
		else {
			//Tulostetaan lista
			$query = "	SELECT tuote.tuoteno, tilausrivi.*
						FROM tilausrivi use index (yhtio_otunnus), tuote use index (tuoteno_index)
						WHERE otunnus = '$laskurow[tunnus]'
						$lisasalker
						and tuote.yhtio = '$kukarow[yhtio]'
						and tilausrivi.tyyppi in ($tvs_tyyppi)
						and tilausrivi.var in ('','H')
						and tuote.tuoteno = tilausrivi.tuoteno
						and tilausrivi.yhtio = tuote.yhtio";
			$cresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($cresult) > 0) {
				//kerättävää on

				//katotaan speciaalirivit joilla tilkpl = 999999999 and varattu = 0
				//tällöin halutaan siirtää kaikki mitä hyllyssä on tällä hetkellä
				while ($crow = mysql_fetch_array($cresult)) {
					if ($crow['varattu'] == 0 and $crow['tilkpl'] == 9999.99) {
						//siirretään kaikki mitä paikalla on

						$vapaana = 0;

						$query = "	SELECT saldo
									from tuotepaikat
									where tuoteno	= '$crow[tuoteno]'
									and yhtio		= '$kukarow[yhtio]'
									and hyllyalue  	= '$crow[hyllyalue]'
									and hyllynro   	= '$crow[hyllynro]'
									and hyllyvali  	= '$crow[hyllyvali]'
									and hyllytaso  	= '$crow[hyllytaso]'";
						$alkuresult = mysql_query($query) or pupe_error($query);
						$alkurow = mysql_fetch_array($alkuresult);

						//ennakkopoistot
						$query = "	SELECT sum(varattu) keratty
									FROM tilausrivi
									WHERE tyyppi in ('L','G','V')
									and yhtio 		= '$kukarow[yhtio]'
									and tuoteno		= '$crow[tuoteno]'
									and varattu	    > 0
									and hyllyalue  	= '$crow[hyllyalue]'
									and hyllynro   	= '$crow[hyllynro]'
									and hyllyvali  	= '$crow[hyllyvali]'
									and hyllytaso  	= '$crow[hyllytaso]'";
						$varatutresult = mysql_query($query) or pupe_error($query);
						$varatutrow = mysql_fetch_array($varatutresult);


						$vapaana = $alkurow["saldo"] - $varatutrow["keratty"];

						if ($vapaana <= 0) {
							$vapaana = 0;
						}

						$query = "	UPDATE tilausrivi set tilkpl='$vapaana', varattu='$vapaana'
									where tunnus='$crow[tunnus]'
									and yhtio='$kukarow[yhtio]'";
						$res = mysql_query($query) or pupe_error($query);
					}
				}

				$laskurow['pakkaamo'] = pakkaamo($laskurow["tunnus"], "yes", $ei_pakkaamoa);

				// Tulostetaan keräyslista
				require_once ("tulosta_lahete_kerayslista.inc");

				//hatetaan asiakkaan tiedot
				$query = "  SELECT lahetetyyppi, luokka, puhelin, if(asiakasnro!='', asiakasnro, ytunnus) asiakasnro
							FROM asiakas
							WHERE tunnus='$laskurow[liitostunnus]' and yhtio='$kukarow[yhtio]'";
				$riresult = mysql_query($query) or pupe_error($query);
				$asrow = mysql_fetch_array($riresult);

				$sorttauskentta = generoi_sorttauskentta($yhtiorow["kerayslistan_jarjestys"]);

				if ($yhtiorow["kerayslistan_palvelutjatuottet"] == "E") $pjat_sortlisa = "tuotetyyppi,";
				else $pjat_sortlisa = "";

				// Keräyslistan rivit
				if ($toim == "VALMISTAASIAKKAALLE" or $toim == "VALMISTAVARASTOON") {
					$querysel = "	tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso, tilausrivi.tuoteno, tilausrivi.yksikko, tilausrivi.var, tilausrivi.nimitys,
									sum(tilausrivi.tilkpl) tilkpl,
									sum(tilausrivi.varattu) varattu,
									sum(tilausrivi.jt) jt,
									group_concat(tilausrivi.tunnus) tunnus, ";

					$lisa = "	and tilausrivi.tyyppi in ($tvs_tyyppi)
								GROUP BY tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso, tilausrivi.tuoteno, tilausrivi.yksikko, tilausrivi.var, tilausrivi.nimitys, sorttauskentta, tuotetyyppi";
				}
				else {
					$querysel = " tilausrivi.*, ";
					$lisa = " ";
				}

				$query = "	SELECT
							$querysel
							$sorttauskentta,
							if (tuote.tuotetyyppi='K','2 Työt','1 Muut') tuotetyyppi
							FROM tilausrivi use index (yhtio_otunnus)
							JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
							WHERE tilausrivi.otunnus = '$laskurow[tunnus]'
							and tilausrivi.yhtio = '$kukarow[yhtio]'
							and tilausrivi.var in ('','H')
							$lisasalker
							$lisa
							ORDER BY $pjat_sortlisa sorttauskentta $yhtiorow[kerayslistan_jarjestys_suunta], tilausrivi.tunnus";
				$result = mysql_query($query) or pupe_error($query);

				$tilausnumeroita = $laskurow["tunnus"];

				//generoidaan rivinumerot
				$rivinumerot = array();

				$kal = 1;

				while ($row = mysql_fetch_array($result)) {
					$rivinumerot[$row["tunnus"]] = $kal;
					$kal++;
				}

				mysql_data_seek($result,0);

				unset($pdf);
				unset($page);

				$sivu  = 1;
				$paino = 0;

				if ($toim == "VALMISTAASIAKKAALLE" or $toim == "VALMISTAVARASTOON") {
					$tyyppi = "";
				}
				else {
					$tyyppi = "SIIRTOLISTA";
				}

				// Aloitellaan lähetteen teko
				$page[$sivu] = alku_kerayslista($tyyppi);

				while ($row = mysql_fetch_array($result)) {
					rivi_kerayslista($page[$sivu], $tyyppi);
				}

				loppu_kerayslista($page[$sivu], 1);

				//tulostetaan failit ja valitaan sopivat printterit
				if ($laskurow["varasto"] == '') {
					$query = "	select *
								from varastopaikat
								where yhtio='$kukarow[yhtio]'
								order by alkuhyllyalue,alkuhyllynro
								limit 1";
				}
				else {
					$query = "	select *
								from varastopaikat
								where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[varasto]'
								order by alkuhyllyalue,alkuhyllynro";
				}
				$prires = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($prires)>0) {
					$prirow = mysql_fetch_array($prires);

					if ($valittu_tulostin == "oletukselle") {
						$apuprintteri = $prirow['printteri1']; // läheteprintteri
					}
					else {
						$apuprintteri = $valittu_tulostin;
					}

					//haetaan lähetteen tulostuskomento
					$query = "select * from kirjoittimet where yhtio='$kukarow[yhtio]' and tunnus='$apuprintteri'";
					$kirres= mysql_query($query) or pupe_error($query);
					$kirrow= mysql_fetch_array($kirres);
					$komento=$kirrow['komento'];
				}

				//tulostetaan sivu
				print_pdf_kerayslista($komento);

				if ($returnvalue != 0) {
					echo "<font class='error'>$komento".t("Keräyslistan tulostus epäonnistui")."! ".t("Tilaus $laskurow[tunnus]")." ".t("siirrettiin tulostusjonoon").".
							".t("Käy tulostamassa lähete")." <a href='lahetteen_tulostusjono.php'>".t("tulostusjonosta")."</a>.</font><br>";
					$virheellinen = "X"; //Merkataan epäonnistuneeksi
				}

				if ($toim == "VALMISTAASIAKKAALLE" or $toim == "VALMISTAVARASTOON") {

					// katotaan miten halutaan sortattavan
					// haetaan asiakkaan tietojen takaa sorttaustiedot
					$order_sorttaus = '';

					$asiakas_apu_query = "	SELECT lahetteen_jarjestys, lahetteen_jarjestys_suunta
											FROM asiakas
											WHERE yhtio='$kukarow[yhtio]'
											and tunnus='$laskurow[liitostunnus]'";
					$asiakas_apu_res = mysql_query($asiakas_apu_query) or pupe_error($asiakas_apu_query);

					if (mysql_num_rows($asiakas_apu_res) == 1) {
						$asiakas_apu_row = mysql_fetch_array($asiakas_apu_res);
						$sorttauskentta = generoi_sorttauskentta($asiakas_apu_row["lahetteen_jarjestys"] != "" ? $asiakas_apu_row["lahetteen_jarjestys"] : $yhtiorow["lahetteen_jarjestys"]);
						$order_sorttaus = $asiakas_apu_row["lahetteen_jarjestys_suunta"] != "" ? $asiakas_apu_row["lahetteen_jarjestys_suunta"] : $yhtiorow["lahetteen_jarjestys_suunta"];
					}
					else {
						$sorttauskentta = generoi_sorttauskentta($yhtiorow["lahetteen_jarjestys"]);
						$order_sorttaus = $yhtiorow["lahetteen_jarjestys_suunta"];
					}

					if ($yhtiorow["lahetteen_palvelutjatuottet"] == "E") $pjat_sortlisa = "tuotetyyppi,";
					else $pjat_sortlisa = "";

					$query = " 	SELECT tilausrivi.*,
								$sorttauskentta,
								if (tuote.tuotetyyppi='K','2 Työt','1 Muut') tuotetyyppi
								FROM tilausrivi use index (yhtio_otunnus)
								JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
								WHERE tilausrivi.otunnus = '$laskurow[tunnus]'
								and tilausrivi.yhtio = '$kukarow[yhtio]'
								and tilausrivi.var in ('','H')
								and tilausrivi.tyyppi != 'D'
								ORDER BY $pjat_sortlisa sorttauskentta $order_sorttaus, tilausrivi.tunnus";
					$result = mysql_query($query) or pupe_error($query);

					require_once ("tulosta_valmistus.inc");

					$tilausnumeroita = $laskurow["tunnus"];

					//generoidaan rivinumerot
					$rivinumerot = array();

					$kal = 1;

					while ($row = mysql_fetch_array($result)) {
						$rivinumerot[$row["tunnus"]] = $kal;
						$kal++;
					}

					mysql_data_seek($result,0);

					unset($pdf);
					unset($page);

					$sivu  = 1;
					$paino = 0;

					// Aloitellaan lähetteen teko
					$page[$sivu] = alku_valm($tyyppi);

					while ($row = mysql_fetch_array($result)) {
						rivi_valm($page[$sivu], $tyyppi);
					}

					loppu_valm($page[$sivu], 1);
					print_pdf_valm($komento);
				}

				// jos meillä oli lähetteen tulostusongelmia
				if ($virheellinen == 'X') {
					// merkataan lähete tulostusjonoon
					$query  ="	UPDATE lasku set
								alatila='J'
								where yhtio='$kukarow[yhtio]'
								and tunnus = '$laskurow[tunnus]'
								and tila='$tvs_tila'
								and alatila=''";
					$result = mysql_query($query) or pupe_error($query);
				}
				else {
					if ($yhtiorow["kerataanko_valmistukset"] == "E") {
						$query = "	UPDATE lasku
									SET alatila='C', lahetepvm=now()
									where yhtio='$kukarow[yhtio]'
									and tunnus = '$laskurow[tunnus]'
									and tila='$tvs_tila'
									and alatila in ('','J')";
						$result = mysql_query($query) or pupe_error($query);
					}
					else {
						$query = "	UPDATE lasku
									SET alatila='A', lahetepvm=now()
									where yhtio='$kukarow[yhtio]'
									and tunnus = '$laskurow[tunnus]'
									and tila='$tvs_tila'
									and alatila in ('','J')";
						$result = mysql_query($query) or pupe_error($query);
					}
				}
			}
			else {
				if ($toim == "VALMISTAASIAKKAALLE" or $toim == "VALMISTAVARASTOON") {
					echo t("Valmistuksella ei ollut mitään kerättävää")."!<br><br>";
					$eikeralatila = "C";
				}
				else {
					echo t("Siirtolistalla ei ollut mitään kerättävää")."!<br><br>";
					$eikeralatila = "E";
				}

				$query = "	UPDATE lasku
							SET alatila = '$eikeralatila', lahetepvm = now()
							where yhtio	= '$kukarow[yhtio]'
							and tunnus 	= '$laskurow[tunnus]'
							and tila	= '$tvs_tila'
							and alatila	in ('','J')";
				$result = mysql_query($query) or pupe_error($query);
			}
		}

		// ollaan käsitelty projektin osatoimitus joten palataan tunnusnipun otsikolle..
		if ($kukarow["extranet"] == "" and $laskurow["tunnusnippu"] > 0 and $projektilla > 0) {

			$aika=date("d.m.y @ G:i:s", time());
			echo "<font class='message'>".t("Osatoimitus")." $otsikko $kukarow[kesken] ".t("valmis")."! ($aika) $kaikkiyhteensa $laskurow[valkoodi]</font><br><br>";

			if ($projektilla > 0) {
				$tilausnumero = $laskurow["tunnusnippu"];

				//	Hypätään takaisin otsikolle
				echo "<font class='info'>".t("Palataan projektille odota hetki..")."</font><br>";
				echo "<META HTTP-EQUIV='Refresh'CONTENT='1;URL=$PHP_SELF?toim=PROJEKTI&valitsetoimitus=$tilausnumero'>";
				exit;
			}
		}
	}
?>