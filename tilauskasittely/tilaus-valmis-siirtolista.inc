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

	// katotaan ihan aluksi, onko t‰m‰ jo ker‰tty...
	$query = "	select tilausrivi.*
				from tilausrivi use index (yhtio_otunnus)
				join tuote on tilausrivi.yhtio=tuote.yhtio and tilausrivi.tuoteno=tuote.tuoteno
				where tilausrivi.yhtio='$kukarow[yhtio]'
				and tilausrivi.otunnus='$laskurow[tunnus]'
				and tilausrivi.keratty!=''
				$lisasalker";
	$keres = mysql_query($query) or pupe_error($query);

	// jos yksikin rivi on ker‰tty (l‰hetett‰ ei tulosteta uudestaan)...
	// tai jos ollaan ruksattu suoraan valmiiksi (sis‰iset tyˆm‰‰r‰ykset)
	if (mysql_num_rows($keres) > 0 or $laskurow["eilahetetta"] != '') {

		//...niin p‰ivitet‰‰n ker‰‰m‰ttˆm‰t rivit ker‰tyksi aktiiviselle k‰ytt‰j‰lle
		$query = "	UPDATE tilausrivi use index (yhtio_otunnus)
					SET keratty='$kukarow[kuka]', kerattyaika=now()
					where yhtio='$kukarow[yhtio]'
					and otunnus='$laskurow[tunnus]'
					and keratty=''";
		$result = mysql_query($query) or pupe_error($query);


		if ($toim == "VALMISTAASIAKKAALLE" or $toim == "VALMISTAVARASTOON") {
			echo "<font class='message'>".t("Valmistuksella oli jo ker‰ttyj‰ rivej‰, joten l‰hetett‰ ei tulosteta").".<br>".t("Jos haluat l‰hetteen tulosta l‰hetteen kopio, valmistusnumero on")." $laskurow[tunnus].</font><br><br>";
		}
		else {
			echo "<font class='message'>".t("Siirtolistalla oli jo ker‰ttyj‰ rivej‰, joten l‰hetett‰ ei tulosteta").".<br>".t("Jos haluat l‰hetteen tulosta l‰hetteen kopio, siirtolistanumero on")." $laskurow[tunnus].</font><br><br>";
		}

		if ($laskurow["eilahetetta"] != '') {
			$query  = "	update lasku
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
			$query = "select tulostustapa from toimitustapa where yhtio='$kukarow[yhtio]' and selite='$laskurow[toimitustapa]'";
			$result = mysql_query($query) or pupe_error($query);
			$totarowx = mysql_fetch_array($result);

			// Maksuehdon jv tieto tarvitaan
			$query = "select jv from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
			$result = mysql_query($query) or pupe_error($query);
			$maehrowx = mysql_fetch_array($result);

			//haetaan kaikkien tilausrivien varastopaikat
			$query = "	SELECT otunnus, tunnus, hyllyalue, hyllynro, hyllyvali, hyllytaso,
						concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0')) sorttauskentta
						FROM tilausrivi
						WHERE otunnus = '$laskurow[tunnus]' and yhtio='$kukarow[yhtio]'
						ORDER BY sorttauskentta";
			$result = mysql_query($query) or pupe_error($query);

			//t‰ss‰ muuttujassa on sit kaikki tunnukset splitin j‰lkeen
			$tvs_tilausnumerot	 = array();
			$tvs_tilausnumerot[] = $laskurow["tunnus"];

			//mihin varastoon l‰hete kuuluu. aluksi x
			$varasto   = 'x';
			$edvarasto = 'x';

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
					// intrastattia ei l‰hetet‰ tulliin kun varasto ja toimitusosoite on samassa maassa
					$laskurow["kauppatapahtuman_luonne"] = '999';
				}

				//varaasto vaihtuu, splitataan tilaus, paitsi jos kyseess‰ on jv-tilaus jonka rahtikirja tulostetaan heti, tai jos spliauskielto on trigattu
				if ($varasto != $edvarasto and $edvarasto != 'x' and ($maehrowx["jv"] == '' or $totarowx["tulostustapa"] != 'H') and $laskurow["splittauskielto"] != "E" and $toim != "SIIRTOTYOMAARAYS") {

					//tehd‰‰n uusi otsikko
					$query = "INSERT into lasku SET
							yhtio_nimi				= '$laskurow[yhtio_nimi]',
							yhtio_osoite			= '$laskurow[yhtio_osoite]',
							yhtio_postino			= '$laskurow[yhtio_postino]',
							yhtio_postitp			= '$laskurow[yhtio_postitp]',
							yhtio_maa				= '$laskurow[yhtio_maa]',
							yhtio_ovttunnus			= '$laskurow[yhtio_ovttunnus]',
							yhtio_kotipaikka		= '$laskurow[yhtio_kotipaikka]',
							yhtio_toimipaikka		= '$laskurow[yhtio_toimipaikka]',
							alatila					= '$laskurow[alatila]',
							alv						= '$laskurow[alv]',
							chn						= '$laskurow[chn]',
							comments				= '$laskurow[comments]',
							erikoisale				= '$laskurow[erikoisale]',
							erpcm					= '$laskurow[erpcm]',
							kapvm					= '$laskurow[kapvm]',
							kasumma					= '$laskurow[kasumma]',
							kerayspvm				= '$laskurow[kerayspvm]',
							ketjutus				= '$laskurow[ketjutus]',
							laatija					= '$laskurow[laatija]',
							laskunro				= '$laskurow[laskunro]',
							laskutusvkopv			= '$laskurow[laskutusvkopv]',
							luontiaika				= '$laskurow[luontiaika]',
							maa 					= '$laskurow[maa]',
							maksuehto				= '$laskurow[maksuehto]',
							myyja					= '$laskurow[myyja]',
							nimi					= '$laskurow[nimi]',
							nimitark				= '$laskurow[nimitark]',
							osoite					= '$laskurow[osoite]',
							ovttunnus				= '$laskurow[ovttunnus]',
							postino					= '$laskurow[postino]',
							postitp					= '$laskurow[postitp]',
							tapvm					= '$laskurow[tapvm]',
							tila					= '$laskurow[tila]',
							tilausvahvistus			= '$laskurow[tilausvahvistus]',
							toim_maa				= '$laskurow[toim_maa]',
							toim_nimi				= '$laskurow[toim_nimi]',
							toim_nimitark			= '$laskurow[toim_nimitark]',
							toim_osoite				= '$laskurow[toim_osoite]',
							toim_ovttunnus			= '$laskurow[toim_ovttunnus]',
							toim_postino			= '$laskurow[toim_postino]',
							toim_postitp			= '$laskurow[toim_postitp]',
							toimaika				= '$laskurow[toimaika]',
							toimitusehto			= '$laskurow[toimitusehto]',
							kohdistettu 			= '$laskurow[kohdistettu]',
							toimitustapa			= '$laskurow[toimitustapa]',
							valkoodi				= '$laskurow[valkoodi]',
							vienti_kurssi			= '$laskurow[vienti_kurssi]',
							vanhatunnus				= '$laskurow[tunnus]',
							verkkotunnus			= '$laskurow[verkkotunnus]',
							vienti 					= '$laskurow[vienti]',
							viesti					= '$laskurow[viesti]',
							viikorkopros			= '$laskurow[viikorkopros]',
							viite					= '$laskurow[viite]',
							tilausyhteyshenkilo		= '$laskurow[tilausyhteyshenkilo]',
							asiakkaan_tilausnumero	= '$laskurow[asiakkaan_tilausnumero]',
							kohde					= '$laskurow[kohde]',
							varasto 				= '$varasto',
							liitostunnus    		= '$laskurow[liitostunnus]',
							yhtio					= '$laskurow[yhtio]',
							clearing				= '$laskurow[clearing]',
							tilaustyyppi			= '$laskurow[tilaustyyppi]',
							ytunnus					= '$laskurow[ytunnus]',
							jaksotettu				= '$laskurow[jaksotettu]',
							kuljetusmuoto			= '$laskurow[kuljetusmuoto]',
							kauppatapahtuman_luonne	= '$laskurow[kauppatapahtuman_luonne]',
							ultilno					= '$ultilno',
							maa_maara				= '$laskurow[maa_maara]',
							maa_lahetys				= '$laskurow[maa_lahetys]',
							piiri 					= '$laskurow[piiri]'";
					$uusires = mysql_query($query) or pupe_error($query);
					$laskurow['tunnus'] = (string) mysql_insert_id();
					$tvs_tilausnumerot[] = $laskurow["tunnus"];
				}
				else {
					//p‰ivitet‰‰n laskulle tieto mihin varastoon se kuuluu
					$query  ="	UPDATE lasku SET
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
								where yhtio = '$kukarow[yhtio]' and
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

			//tyhjennet‰‰n muuttujat
			$varasto 	= '';
			$edvarasto 	= '';

			//loopataan kaikki yhdest‰ tilausesta syntyneet l‰hetteet
			foreach ($tvs_tilausnumerot as $tvs_tilausnumero) {
				//luetaan laskun tiedot uudestaan t‰ss‰, per splitti
				$query = "select * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$tvs_tilausnumero'";
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
								WHERE yhtio='$kukarow[yhtio]' and tyyppi in ($tvs_tyyppi) and otunnus='$laskurow[tunnus]'";
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
					}
					else {
						echo "<font class='message'>".t("Siirtolista siirretty tulostusjonoon")." $laskurow[tunnus].<br><br>";
					}
				}
			}

			// tilaus ei en‰‰ kesken...
			$query	= "update kuka set kesken=0 where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
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
				//ker‰tt‰v‰‰ on

				//katotaan speciaalirivit joilla tilkpl = 999999999 and varattu = 0
				//t‰llˆin halutaan siirt‰‰ kaikki mit‰ hyllyss‰ on t‰ll‰ hetkell‰
				while($crow = mysql_fetch_array($cresult)) {
					if ($crow['varattu'] == 0 and $crow['tilkpl'] == 9999.99) {
						//siirret‰‰n kaikki mit‰ paikalla on

						$vapaana = 0;

						$query = "	select saldo
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

						$query = "	update tilausrivi set tilkpl='$vapaana', varattu='$vapaana'
									where tunnus='$crow[tunnus]'
									and yhtio='$kukarow[yhtio]'";
						$res = mysql_query($query) or pupe_error($query);
					}
				}

				// Tulostetaan ker‰yslista
				require_once ("tulosta_lahete_kerayslista.inc");

				//hatetaan asiakkaan tiedot
				$query = "  SELECT lahetetyyppi, luokka, puhelin, if(asiakasnro!='', asiakasnro, ytunnus) asiakasnro
							FROM asiakas
							WHERE tunnus='$laskurow[liitostunnus]' and yhtio='$kukarow[yhtio]'";
				$riresult = mysql_query($query) or pupe_error($query);
				$asrow = mysql_fetch_array($riresult);


				$sorttauskentta = generoi_sorttauskentta($yhtiorow["kerayslistan_jarjestys"]);

				// Ker‰yslistan rivit
				if ($toim == "VALMISTAASIAKKAALLE" or $toim == "VALMISTAVARASTOON") {
					$query = " 	SELECT tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso, tilausrivi.tuoteno, tilausrivi.yksikko, tilausrivi.var, tilausrivi.nimitys,
								sum(tilausrivi.tilkpl) tilkpl, sum(tilausrivi.varattu) varattu, sum(tilausrivi.jt) jt, group_concat(tilausrivi.tunnus) tunnus,";
					$lisa = " 	and tilausrivi.tyyppi in ($tvs_tyyppi)
								GROUP BY 1,2,3,4,5,6,7,8 ";
				}
				else {
					$query = " 	SELECT tilausrivi.*,";
					$lisa = " ";
				}

				$query .= "	$sorttauskentta
							FROM tilausrivi use index (yhtio_otunnus), tuote
							WHERE tilausrivi.otunnus = '$laskurow[tunnus]'
							and tilausrivi.yhtio = '$kukarow[yhtio]'
							and tilausrivi.var in ('','H')
							and tuote.yhtio=tilausrivi.yhtio
							and tuote.tuoteno=tilausrivi.tuoteno
							$lisasalker
							$lisa
							ORDER BY sorttauskentta $yhtiorow[kerayslistan_jarjestys_suunta], tilausrivi.tunnus";
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

				// Aloitellaan l‰hetteen teko
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
						$apuprintteri = $prirow['printteri1']; // l‰heteprintteri
					}
					else {
						$apuprintteri = $valittu_tulostin;
					}

					//haetaan l‰hetteen tulostuskomento
					$query = "select * from kirjoittimet where yhtio='$kukarow[yhtio]' and tunnus='$apuprintteri'";
					$kirres= mysql_query($query) or pupe_error($query);
					$kirrow= mysql_fetch_array($kirres);
					$komento=$kirrow['komento'];
				}

				//tulostetaan sivu
				print_pdf_kerayslista($komento);

				if ($returnvalue != 0) {
					echo "<font class='error'>$komento".t("Ker‰yslistan tulostus ep‰onnistui")."! ".t("Tilaus $laskurow[tunnus]")." ".t("siirrettiin tulostusjonoon").".
							".t("K‰y tulostamassa l‰hete")." <a href='lahetteen_tulostusjono.php'>".t("tulostusjonosta")."</a>.</font><br>";
					$virheellinen = "X"; //Merkataan ep‰onnistuneeksi
				}

				if ($toim == "VALMISTAASIAKKAALLE" or $toim == "VALMISTAVARASTOON") {

					$sorttauskentta = generoi_sorttauskentta($yhtiorow["lahetteen_jarjestys"]);

					$query = " 	SELECT *,
								$sorttauskentta
								FROM tilausrivi use index (yhtio_otunnus)
								WHERE otunnus = '$laskurow[tunnus]'
								and yhtio = '$kukarow[yhtio]'
								and var in ('','H')
								and tyyppi != 'D'
								ORDER BY sorttauskentta $yhtiorow[lahetteen_jarjestys_suunta], tilausrivi.tunnus";
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
					$query  ="	update lasku set
								alatila='J'
								where yhtio='$kukarow[yhtio]'
								and tunnus = '$laskurow[tunnus]'
								and tila='$tvs_tila'
								and alatila=''";
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
		if($kukarow["extranet"] == "" and $laskurow["tunnusnippu"] > 0 and $projektilla > 0) {

			$aika=date("d.m.y @ G:i:s", time());
			echo "<font class='message'>".t("Osatoimitus")." $otsikko $kukarow[kesken] ".t("valmis")."! ($aika) $kaikkiyhteensa $laskurow[valkoodi]</font><br><br>";

			if($projektilla>0) {
				$tilausnumero		= $laskurow["tunnusnippu"];

				//	Hyp‰t‰‰n takaisin otsikolle
				echo "<font class='info'>".t("Palataan projektille odota hetki..")."</font><br>";
				echo "<META HTTP-EQUIV='Refresh'CONTENT='1;URL=$PHP_SELF?toim=PROJEKTI&valitsetoimitus=$tilausnumero'>";
				die();
			}
		}
	}
?>
