<?php

	if ($toim == "VALMISTAASIAKKAALLE" or $toim == "VALMISTAVARASTOON") {
		$tvs_tila 		= "V";
		$tvs_tyyppi		= "'V','L'";
		$tvs_tyyppi_2	= ",'W','M'";
	}
	elseif ($toim == "SIIRTOLISTA" or $toim == "MYYNTITILI") {
		$tvs_tila 		= "G";
		$tvs_tyyppi		= "'G'";
		$tvs_tyyppi_2	= "";
	}
	elseif ($toim == "SIIRTOTYOMAARAYS") {
		$tvs_tila 		= "S";
		$tvs_tyyppi		= "'G'";
		$tvs_tyyppi_2	= "";
	}

	if ($yhtiorow["kerataanko_saldottomat"] == '') {
		$lisasalker = " and tuote.ei_saldoa = '' ";
	}
	else {
		$lisasalker = " ";
	}

	// p‰ivitet‰‰n vanhatunnus laskulle
	$query  = "	UPDATE lasku SET
	 			vanhatunnus = tunnus
				WHERE yhtio = '$kukarow[yhtio]'
				AND tunnus = '$laskurow[tunnus]'
				AND vanhatunnus = 0";
	$oldtur = mysql_query($query) or pupe_error($query);

	//p‰ivitet‰‰n tilaus valmisaika, kun painetaan ekan kerran tilaus valmiiksi
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

	//otetaan varmuuden vuoksi alkuper‰isen otsikon tunnus
	$alkuplaskutunnus = $laskurow["tunnus"];

	// katotaan ihan aluksi, onko t‰m‰ jo ker‰tty...
	$query = "	SELECT tilausrivi.*
				from tilausrivi use index (yhtio_otunnus)
				join tuote on tilausrivi.yhtio=tuote.yhtio and tilausrivi.tuoteno=tuote.tuoteno
				where tilausrivi.yhtio  = '$kukarow[yhtio]'
				and tilausrivi.otunnus  = '$laskurow[tunnus]'
				and tilausrivi.tyyppi  != 'D'
				and tilausrivi.keratty != ''
				$lisasalker";
	$keres = mysql_query($query) or pupe_error($query);

	// jos yksikin rivi on ker‰tty (l‰hetett‰ ei tulosteta uudestaan)...
	// tai jos ollaan ruksattu suoraan valmiiksi (sis‰iset tyˆm‰‰r‰ykset)
	if (mysql_num_rows($keres) > 0 or $laskurow["eilahetetta"] != '') {

		//...niin p‰ivitet‰‰n ker‰‰m‰ttˆm‰t rivit ker‰tyksi aktiiviselle k‰ytt‰j‰lle
		$query = "	UPDATE tilausrivi use index (yhtio_otunnus)
					SET keratty = '$kukarow[kuka]', kerattyaika=now()
					where yhtio = '$kukarow[yhtio]'
					and otunnus = '$laskurow[tunnus]'
					and keratty = ''
					and var not in ('P','J','S')";
		$result = mysql_query($query) or pupe_error($query);

		if ($toim == "VALMISTAASIAKKAALLE" or $toim == "VALMISTAVARASTOON") {
			echo "<font class='message'>".t("Valmistuksella oli jo ker‰ttyj‰ rivej‰, joten l‰hetett‰ ei tulosteta").".<br>".t("Jos haluat l‰hetteen tulosta l‰hetteen kopio, valmistusnumero on")." $laskurow[tunnus].</font><br><br>";
		}
		else {
			echo "<font class='message'>".t("Siirtolistalla oli jo ker‰ttyj‰ rivej‰, joten l‰hetett‰ ei tulosteta").".<br>".t("Jos haluat l‰hetteen tulosta l‰hetteen kopio, siirtolistanumero on")." $laskurow[tunnus].</font><br><br>";
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
			///* Ensin on pakko splitata l‰hete varastoittain *///
			//tyhjennet‰‰n laskun varastokentt‰, siell‰ oli tieto varastoon optimoinnista mutta nyt se kertoo mihin varastoon l‰hete kuuluu laittaa
			$laskurow['varasto'] = 0;

			//Toimitustava hetier‰ tarvitaan
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
						and tyyppi	   != 'D'
						ORDER BY sorttauskentta";
			$result = mysql_query($query) or pupe_error($query);

			//t‰ss‰ muuttujassa on sit kaikki tunnukset splitin j‰lkeen
			$tvs_tilausnumerot	 = array();
			$tvs_tilausnumerot[] = $laskurow["tunnus"];

			//mihin varastoon l‰hete kuuluu. aluksi x
			$varasto   = 'x';
			$edvarasto = 'x';
			$tulostusalue = $edtulostusalue = "x";
			unset($laskusplitres);

			while ($row = mysql_fetch_array($result)) {
				// Mihin varastoon
				$varasto = kuuluukovarastoon($row["hyllyalue"], $row["hyllynro"]);

				// Mik‰ tulostusalue
				$tulostusalue = onkotulostusalueita($row["hyllyalue"], $row["hyllynro"], $varasto);

				// Intrastat-tiedot
				$laskurow = palauta_intrastat_tiedot($laskurow, $varasto);

				//varaasto vaihtuu, splitataan tilaus, paitsi jos kyseess‰ on jv-tilaus jonka rahtikirja tulostetaan heti, tai jos spliauskielto on trigattu
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
					for ($i=1; $i < mysql_num_fields($laskusplitres)-1; $i++) {

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
					//p‰ivitet‰‰n laskulle tieto mihin varastoon se kuuluu
					$query  ="	UPDATE lasku SET
								varasto 							= '$varasto',
								tulostusalue						= '$tulostusalue',
								ultilno 							= '$laskurow[ultilno]',
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
								where yhtio = '$kukarow[yhtio]'
								and tunnus  = '$laskurow[tunnus]'";
					$varresult = mysql_query($query) or pupe_error($query);

					$laskurow["varasto"] = $varasto;
				}

				//edellinen varasto talteen
				$edvarasto = $varasto;

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

			//tyhjennet‰‰n muuttujat
			$varasto 	= '';
			$edvarasto 	= '';

			//loopataan kaikki yhdest‰ tilausesta syntyneet l‰hetteet
			foreach ($tvs_tilausnumerot as $tvs_tilausnumero) {

				//luetaan laskun tiedot uudestaan t‰ss‰, per splitti
				$query = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$tvs_tilausnumero'";
				$result = mysql_query($query) or pupe_error($query);
				$laskurow = mysql_fetch_array($result);

				// T‰ss‰ katotaan onko l‰hetteell‰ tosiaan mit‰‰n ker‰tt‰v‰‰
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
					// Mit‰‰n ker‰tt‰v‰‰ ei ooo, mutta jt:t‰ voi olla
					$query = "	SELECT count(*) riveja, sum(if(var='J', 1, 0)) jteet
								FROM tilausrivi
								WHERE yhtio = '$kukarow[yhtio]'
								and tyyppi in ($tvs_tyyppi)
								and otunnus = '$laskurow[tunnus]'";
					$tresult = mysql_query($query) or pupe_error($query);
					$rivirow = mysql_fetch_array($tresult);

					//Tilauskella on pelk‰st‰‰n jt-rivej‰
					if ($rivirow["riveja"] > 0 and $rivirow["jteet"] > 0) {
						//p‰ivtet‰‰n tilaus tilaan 'odottaa jt-rivej‰'
						$query = "UPDATE lasku SET tila='$tvs_tila', alatila='T' where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[tunnus]'";
						$yresult = mysql_query($query) or pupe_error($query);

						echo "<font class='message'>".t("Siirtolista siirrettiin j‰lkitoimitukseksi odottamaan tuotteita")." $laskurow[tunnus].<br><br>";
					}
					else {
						// Mit‰‰n j‰rkevi‰ rivi‰ ei ollut, mit‰tˆid‰‰n lista
						$query = "UPDATE tilausrivi SET tyyppi='D' where yhtio='$kukarow[yhtio]' and otunnus='$kukarow[kesken]'";
						$result = mysql_query($query) or pupe_error($query);

						$query = "UPDATE lasku SET tila='D', alatila='$laskurow[tila]', comments='$kukarow[nimi] ($kukarow[kuka]) ".t("mit‰tˆi siirtolistan")." ".date("d.m.y @ G:i:s")."' where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
						$result = mysql_query($query) or pupe_error($query);

						echo "<font class='message'>".t("Siirtolista mit‰tˆitiin, koska sill‰ ei ollut mit‰‰n ker‰tt‰v‰‰")." $laskurow[tunnus].<br><br>";
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

						if ($toim == "VALMISTAASIAKKAALLE" and trim($laskurow['tilausvahvistus']) != "") {
							//
							// LƒHETETƒƒN TILAUSVAHVISTUS
							//
							// tulostetaan t‰ss‰, niin saadaan vahvistukseen koko tilaus, ennenkun sen splitaatan eri varastoihin
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
					elseif ($yhtiorow["varastosiirto_tilausvahvistus"] == "K") {

						// pakotetaan tilausvanvistus PDF muodossa omaan s‰hkˆpostiin, ilman hintoja.
						$laskurow["tilausvahvistus"] = "3OP";

						// LƒHETETƒƒN TILAUSVAHVISTUS
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

						echo "<font class='message'>".t("Siirtolista siirretty tulostusjonoon")." $laskurow[tunnus].<br><br>";
					}
				}
			}

			// tilaus ei en‰‰ kesken...
			$query	= "UPDATE kuka set kesken=0 where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
			$result = mysql_query($query) or pupe_error($query);

			// L‰hetteet tulostetaan automaattisesti
			if (($toim == "MYYNTITILI" and $yhtiorow["myyntitilin_tulostustapa"] == "A") or ($toim == "VALMISTAASIAKKAALLE" and $yhtiorow["valmistuksen_tulostustapa"] == "A") or ($toim == "VALMISTAVARASTOON" and $yhtiorow["valmistuksen_tulostustapa"] == "A") or ($toim == "SIIRTOLISTA" and $yhtiorow["siirtolistan_tulostustapa"] == "A")) {
				#TODO: Otetaan t‰ss‰ vain alkuper‰inen tilaus ja tulostetaan se.. Jos tilaus splittaantui, muut j‰‰v‰t ker‰yslistajonoon.
				$query = "SELECT * FROM lasku where yhtio = '{$kukarow["yhtio"]}' and tunnus = '$alkuplaskutunnus'";
				$result = mysql_query($query) or pupe_error($query);
				$laskurow = mysql_fetch_assoc($result);

				$tee = "valmis";
				$tulostetaan = "OK";
			}
		}

		if ($tulostetaan == "OK") {

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
				//ker‰tt‰v‰‰ on

				//katotaan speciaalirivit joilla tilkpl = 999999999 and varattu = 0
				//t‰llˆin halutaan siirt‰‰ kaikki mit‰ hyllyss‰ on t‰ll‰ hetkell‰
				while ($crow = mysql_fetch_array($cresult)) {
					if ($crow['varattu'] == 0 and $crow['tilkpl'] == 9999.99) {
						//siirret‰‰n kaikki mit‰ paikalla on

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

				// Tulostetaan ker‰yslista
				require_once ("tulosta_lahete_kerayslista.inc");

				// Haetaan asiakkaan tiedot
				$query = "  SELECT luokka, puhelin, if(asiakasnro!='', asiakasnro, ytunnus) asiakasnro
							FROM asiakas
							WHERE tunnus='$laskurow[liitostunnus]' and yhtio='$kukarow[yhtio]'";
				$riresult = mysql_query($query) or pupe_error($query);
				$asrow = mysql_fetch_array($riresult);

				$sorttauskentta = generoi_sorttauskentta($yhtiorow["kerayslistan_jarjestys"]);

				if ($yhtiorow["kerayslistan_palvelutjatuottet"] == "E") $pjat_sortlisa = "tuotetyyppi,";
				else $pjat_sortlisa = "";

				// Ker‰yslistan rivit
				if ($toim == "VALMISTAASIAKKAALLE" or $toim == "VALMISTAVARASTOON") {

					$sorttauskentta = str_replace("as sorttauskentta", "", $sorttauskentta);

					$querysel = "	tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso, tilausrivi.tuoteno, tilausrivi.yksikko, tilausrivi.var, tilausrivi.nimitys,
									sum(tilausrivi.tilkpl) tilkpl,
									sum(tilausrivi.varattu) varattu,
									sum(tilausrivi.jt) jt,
									group_concat(tilausrivi.tunnus) tunnus,
									min($sorttauskentta) as sorttauskentta, ";

					$lisa = "	and tilausrivi.tyyppi in ($tvs_tyyppi)
								GROUP BY tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso, tilausrivi.tuoteno, tilausrivi.yksikko, tilausrivi.var, tilausrivi.nimitys, tuotetyyppi, myyntihinta_maara, tuote.sarjanumeroseuranta";
				}
				else {
					$querysel = " tilausrivi.*, $sorttauskentta,";
					$lisa = " ";
				}

				$query = "	SELECT
							$querysel
							if (tuote.tuotetyyppi='K','2 Tyˆt','1 Muut') tuotetyyppi,
							if (tuote.myyntihinta_maara=0, 1, tuote.myyntihinta_maara) myyntihinta_maara,
							tuote.sarjanumeroseuranta,
							tuote.eankoodi
							FROM tilausrivi use index (yhtio_otunnus)
							JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
							WHERE tilausrivi.otunnus = '$laskurow[tunnus]'
							and tilausrivi.yhtio = '$kukarow[yhtio]'
							and tilausrivi.var in ('','H')
							and tilausrivi.tyyppi != 'D'
							$lisasalker
							$lisa
							ORDER BY $pjat_sortlisa sorttauskentta $yhtiorow[kerayslistan_jarjestys_suunta], tilausrivi.tunnus";
				$riresult = mysql_query($query) or pupe_error($query);

				$tilausnumeroita = $laskurow["tunnus"];

				//generoidaan rivinumerot
				$rivinumerot = array();

				$kal = 1;

				while ($row = mysql_fetch_array($riresult)) {
					$rivinumerot[$row["tunnus"]] = $kal;
					$kal++;
				}

				mysql_data_seek($riresult,0);

				if ($toim == "VALMISTAASIAKKAALLE" or $toim == "VALMISTAVARASTOON") {
					$tyyppi = "";
				}
				elseif ($toim == "MYYNTITILI") {
					$tyyppi = "MYYNTITILI";
				}
				else {
					$tyyppi = "SIIRTOLISTA";
				}

				$params_kerayslista = array(
				'asrow'           	=> $asrow,
				'boldi'           	=> $boldi,
				'iso'             	=> $iso,
				'kala'            	=> 0,
				'kieli'           	=> $kieli,
				'komento'			=> '',
				'laskurow'        	=> $laskurow,
				'norm'            	=> $norm,
				'page'            	=> '',
				'paino'           	=> 0,
				'pdf'             	=> NULL,
				'perheid'         	=> 0,
				'perheid2'        	=> 0,
				'pieni'           	=> $pieni,
				'pieni_boldi'     	=> $pieni_boldi,
				'rectparam'       	=> $rectparam,
				'rivinkorkeus'    	=> $rivinkorkeus,
				'rivinumerot'    	=> $rivinumerot,
				'row'             	=> NULL,
				'sivu'            	=> 1,
				'tee'             	=> $tee,
				'thispage'			=> NULL,
				'tilausnumeroita' 	=> $tilausnumeroita,
				'toim'            	=> $toim,
				'tots'            	=> 0,
				'tyyppi'		  	=> $tyyppi,
				'kerayslistatyyppi'	=> '');

				// Aloitellaan ker‰yslistan teko
				$params_kerayslista = alku_kerayslista($params_kerayslista);

				while ($row = mysql_fetch_assoc($riresult)) {
					$params_kerayslista["row"] = $row;
					$params_kerayslista = rivi_kerayslista($params_kerayslista);
				}

				$params_kerayslista["tots"] = 1;
				$params_kerayslista = loppu_kerayslista($params_kerayslista);

				//tulostetaan failit ja valitaan sopivat printterit
				if ($laskurow["varasto"] == '') {
					$query = "	SELECT *
								from varastopaikat
								where yhtio='$kukarow[yhtio]'
								order by alkuhyllyalue,alkuhyllynro
								limit 1";
				}
				else {
					$query = "	SELECT *
								from varastopaikat
								where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[varasto]'
								order by alkuhyllyalue,alkuhyllynro";
				}
				$prires = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($prires) > 0) {
					$prirow = mysql_fetch_array($prires);

					if (!isset($valittu_tulostin) or $valittu_tulostin == "oletukselle") {
						$apuprintteri = $prirow['printteri1']; // l‰heteprintteri
					}
					else {
						$apuprintteri = $valittu_tulostin;
					}

					//haetaan l‰hetteen tulostuskomento
					$query = "SELECT * from kirjoittimet where yhtio='$kukarow[yhtio]' and tunnus='$apuprintteri'";
					$kirres= mysql_query($query) or pupe_error($query);
					$kirrow= mysql_fetch_array($kirres);
					$komento=$kirrow['komento'];
				}

				//tulostetaan sivu
				$params_kerayslista["komento"] = $komento;
				print_pdf_kerayslista($params_kerayslista);

				// jos halutaan MY÷S l‰hete
				if ($yhtiorow["lahetteen_tulostustapa"] == "L") {
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

				if ($returnvalue != 0) {
					echo "<font class='error'>$komento".t("Ker‰yslistan tulostus ep‰onnistui")."! ".t("Tilaus $laskurow[tunnus]")." ".t("siirrettiin tulostusjonoon").".
							".t("K‰y tulostamassa l‰hete")." <a href='lahetteen_tulostusjono.php'>".t("tulostusjonosta")."</a>.</font><br>";
					$virheellinen = "X"; //Merkataan ep‰onnistuneeksi
				}

				if ($toim == "VALMISTAASIAKKAALLE" or $toim == "VALMISTAVARASTOON") {

					// katotaan miten halutaan sortattavan
					// haetaan asiakkaan tietojen takaa sorttaustiedot
					$order_sorttaus = '';

					$asiakas_apu_query = "	SELECT lahetteen_jarjestys, lahetteen_jarjestys_suunta, email
											FROM asiakas
											WHERE yhtio = '$kukarow[yhtio]'
											and tunnus = '$laskurow[liitostunnus]'";
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
								if (tuote.tuotetyyppi='K','2 Tyˆt','1 Muut') tuotetyyppi,
								tuote.valmistuslinja
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

					// Aloitellaan l‰hetteen teko
					$page[$sivu] = alku_valm($tyyppi);

					while ($row = mysql_fetch_array($result)) {
						rivi_valm($page[$sivu], $tyyppi);
					}

					loppu_valm($page[$sivu], 1);
					print_pdf_valm($komento);
				}

				// jos meill‰ oli l‰hetteen tulostusongelmia
				if ($virheellinen == 'X') {
					// merkataan l‰hete tulostusjonoon
					$query  ="	UPDATE lasku set
								alatila='J'
								where yhtio='$kukarow[yhtio]'
								and tunnus = '$laskurow[tunnus]'
								and tila='$tvs_tila'
								and alatila=''";
					$result = mysql_query($query) or pupe_error($query);
				}
				else {
					if ($yhtiorow["kerataanko_valmistukset"] == "E" and ($toim == "VALMISTAASIAKKAALLE" or $toim == "VALMISTAVARASTOON")) {
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
					echo t("Valmistuksella ei ollut mit‰‰n ker‰tt‰v‰‰")."!<br><br>";
					$eikeralatila = "C";
				}
				else {
					echo t("Siirtolistalla ei ollut mit‰‰n ker‰tt‰v‰‰")."!<br><br>";
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

		// ollaan k‰sitelty projektin osatoimitus joten palataan tunnusnipun otsikolle..
		if ($kukarow["extranet"] == "" and $laskurow["tunnusnippu"] > 0 and $projektilla > 0) {

			$aika=date("d.m.y @ G:i:s", time());
			echo "<font class='message'>".t("Osatoimitus")." $otsikko $kukarow[kesken] ".t("valmis")."! ($aika) $kaikkiyhteensa $laskurow[valkoodi]</font><br><br>";

			if ($projektilla > 0) {
				$tilausnumero = $laskurow["tunnusnippu"];

				//	Hyp‰t‰‰n takaisin otsikolle
				echo "<font class='info'>".t("Palataan projektille odota hetki..")."</font><br>";
				echo "<META HTTP-EQUIV='Refresh'CONTENT='1;URL=$PHP_SELF?toim=PROJEKTI&valitsetoimitus=$tilausnumero'>";
				exit;
			}
		}
	}
?>