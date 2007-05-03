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

	// katotaan ihan aluksi, onko t‰m‰ jo ker‰tty...
	$query = "	select *
				from tilausrivi use index (yhtio_otunnus)
				where yhtio='$kukarow[yhtio]'
				and otunnus='$laskurow[tunnus]'
				and keratty!=''";
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

		echo "<font class='message'>".t("Siirtolistalla oli jo ker‰ttyj‰ rivej‰, joten l‰hetett‰ ei tulosteta").".<br>".t("Jos haluat l‰hetteen tulosta l‰hetteen kopio, siirtolistanumero on")." $laskurow[tunnus].</font><br><br>";

		if ($laskurow["eilahetetta"] != '') {
			$query  = "	update lasku
						set alatila = 'C'
						where yhtio='$kukarow[yhtio]'
						and tunnus = '$laskurow[tunnus]'";
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
			$query = "select hetiera from toimitustapa where yhtio='$kukarow[yhtio]' and selite='$laskurow[toimitustapa]'";
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

				$query = "select * from varastopaikat where yhtio='$kukarow[yhtio]' and tunnus='$varasto'";
				$varaston_res = mysql_query($query) or pupe_error($query);
				$varaston_row = mysql_fetch_array($varaston_res);

				$ultilno = '';
				
				// katotaan pit‰‰kˆ tilaukselle tehd‰ intrastat
				// varasto pit‰‰ lˆyty‰, toimitusosoitteen maa ja varastonmaa pit‰‰ lˆyty‰ ja kauppatapahtuman_luonnetta ei saa olla annettu viel‰
				if (mysql_num_rows($varaston_res) == 1 and $laskurow["toim_maa"] != "" and $varaston_row["maa"] != "") {

					$query = "select distinct koodi from maat where koodi in ('$laskurow[toim_maa]','$varaston_row[maa]') and eu = 'ON'";
					$euapuresult = mysql_query($query) or pupe_error($query);

					// kummatkin maat pit‰‰ olla EU:ssa
					if (mysql_num_rows($euapuresult) == 2) {

						if ($laskurow["toim_maa"] == $varaston_row["maa"]) {
							$laskurow["kauppatapahtuman_luonne"] = '999'; // intrastattia ei l‰hetet‰ tulliin kun varasto ja toimitusosoite on samassa maassa
							$ultilno = '';
						}
						elseif ($varaston_row["maa"] == $yhtiorow["maakoodi"]) {
							$ultilno = '-1'; // miinus yks tarkoittaa, ett‰ lis‰tiedot pit‰‰ syˆtt‰‰ ja VIENTI-intrastat pit‰‰ l‰hett‰‰
						}
						elseif ($laskurow["toim_maa"] == $yhtiorow["maakoodi"]) {
							$ultilno = '-2'; // miinus kaks tarkoittaa, ett‰ lis‰tiedot pit‰‰ syˆtt‰‰ ja TUONTI-intrastat pit‰‰ l‰hett‰‰

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

				//varaasto vaihtuu, splitataan tilaus, paitsi jos kyseess‰ on jv-tilaus jonka rahtikirja tulostetaan heti, tai jos spliauskielto on trigattu
				if ($varasto != $edvarasto and $edvarasto != 'x' and ($maehrowx["jv"] == '' or $totarowx["hetiera"] != 'H') and $laskurow["splittauskielto"] != "E") {

					//tehd‰‰n uusi otsikko
					$query = "INSERT into lasku SET
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
							viitetxt				= '$laskurow[viitetxt]',
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
							maa_maara				= '$laskurow[maa_maara]'";

					$uusires = mysql_query($query) or pupe_error($query);
					$laskurow['tunnus'] = (string) mysql_insert_id();
					$tvs_tilausnumerot[] = $laskurow["tunnus"];
				}
				elseif ($laskurow["varasto"] == 0) {
					//p‰ivitet‰‰n laskulle tieto mihin varastoon se kuuluu
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
			$varasto = '';
			$edvarasto = '';

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
							and tuote.ei_saldoa = ''
							and tuote.yhtio = '$kukarow[yhtio]'
							and tuote.ei_saldoa = ''
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
						and tuote.ei_saldoa = ''
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

				// Generoidaan l‰hetteelle ja ker‰yslistalle rivinumerot
				if ($toim == "VALMISTAASIAKKAALLE" or $toim == "VALMISTAVARASTOON") {
					$query = " 	SELECT tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso, tilausrivi.tuoteno, 
								tilausrivi.yksikko, tilausrivi.var, tilausrivi.nimitys, 
								sum(tilausrivi.tilkpl) tilkpl, sum(tilausrivi.varattu) varattu, sum(tilausrivi.jt) jt,
								concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0')) sorttauskentta";
					$lisa = " 	and tilausrivi.tyyppi in ($tvs_tyyppi) 
								GROUP BY 1,2,3,4,5,6,7,8 ";
				}
				else {
					$query = " 	SELECT tilausrivi.*, 
								concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0')) sorttauskentta";
					$lisa = " ";
				}

				$query .= "	FROM tilausrivi use index (yhtio_otunnus), tuote
							WHERE tilausrivi.otunnus = '$laskurow[tunnus]'
							and tilausrivi.yhtio = '$kukarow[yhtio]'
							and tilausrivi.var in ('','H')
							and tuote.yhtio=tilausrivi.yhtio
							and tuote.tuoteno=tilausrivi.tuoteno
							and tuote.ei_saldoa = ''
							$lisa
							ORDER BY tilausrivi.perheid, sorttauskentta, tilausrivi.tuoteno, tilausrivi.tunnus";
				$result = mysql_query($query) or pupe_error($query);

				//korjataan t‰‰ p‰iv‰m‰‰r‰ kuntoon jota ei viel‰ ole
				$laskurow["lahetepvm"] = date("Y-m-d");

				$tilausnumeroita = $laskurow['tunnus'];

				//ker‰yslistan tulostusta varten
				if ($yhtiorow["kerailylistatyyppi"] != "" and file_exists($yhtiorow["kerailylistatyyppi"])) {
					require_once ($yhtiorow["kerailylistatyyppi"]);
				}
				else {
					require_once ("tulosta_lahete_kerayslista.inc");
				}

				//tehd‰‰n uusi PDF failin olio
				$pdf= new pdffile;
				$pdf->set_default('margin', 0);

				//pdf:n header..
				$firstpage = alku();

				while ($row = mysql_fetch_array($result)) {
					//piirr‰ rivi
					$firstpage = rivi($firstpage);
					$total += $row["rivihinta"];
				}

				//Vikan rivin loppuviiva
				$x[0] = 20;
				$x[1] = 580;
				$y[0] = $y[1] = $kala + $rivinkorkeus - 4;
				$pdf->draw_line($x, $y, $firstpage, $rectparam);

				loppu($firstpage, 1);

				if ($toim == "VALMISTAASIAKKAALLE" or $toim == "VALMISTAVARASTOON") {
					$query = " 	SELECT *
								FROM tilausrivi use index (yhtio_otunnus)
								WHERE otunnus = '$laskurow[tunnus]'
								and yhtio = '$kukarow[yhtio]'
								and var in ('','H')
								ORDER BY perheid, tunnus";
					$result = mysql_query($query) or pupe_error($query);

					require_once ("tulosta_valmistus.inc");

					//tehd‰‰n uusi PDF failin olio
					$pdf_valm= new pdffile;
					$pdf_valm->set_default('margin', 0);

					$firstpage_valm = alku_valm();

					while ($row = mysql_fetch_array($result)) {
						$firstpage_valm = rivi_valm($firstpage_valm);
						$total += $row["rivihinta"];
					}

					$x[0] = 20;
					$x[1] = 580;
					$y[0] = $y[1] = $kala_valm+$rivinkorkeus-4;
					$pdf_valm->draw_line($x, $y, $firstpage_valm, $rectparam);

					loppu_valm($firstpage_valm, 1);
				}


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

					echo "$tunnus ".t("Siirtolista tulostetaan kirjoittimelle").": $kirrow[kirjoitin]<br>";
				}

				//tulostetaan sivu
				print_pdf($komento);

				if ($toim == "VALMISTAASIAKKAALLE" or $toim == "VALMISTAVARASTOON") {
					print_pdf_valm($komento);
				}

				if ($returnvalue != 0) {
					echo "<font class='error'>".t("L‰hetteen tulostus ep‰onnistui")."! ".t("Tilaus $laskurow[tunnus]")." ".t("siirrettiin tulostusjonoon").".
							".t("K‰y tulostamassa l‰hete")." <a href='lahetteen_tulostusjono.php'>".t("tulostusjonosta")."</a>.</font><br>";
					$virheellinen = "X"; //Merkataan ep‰onnistuneeksi
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

					if ($toim == "VALMISTAASIAKKAALLE" or $toim == "VALMISTAVARASTOON") {
						echo t("Valmistus tulostuu")."!<br><br>";
					}
					else {
						echo t("Siirtolista tulostuu")."!<br><br>";	
					}
					
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
	}
?>