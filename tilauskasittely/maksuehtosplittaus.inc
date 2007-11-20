<?php

	//sis‰‰n tarvitaan $laskurow jossa on laskun tiedot.


	$tulos_ulos_ehtosplit = "";

	// otetaan maksuehto t‰ss‰ heti alussa selville
	$query = "	select *
				from maksuehto
				where yhtio	= '$kukarow[yhtio]'
				and tunnus	= '$laskurow[maksuehto]'";
	$yresult = mysql_query($query) or pupe_error($query);
	$mrow = mysql_fetch_array($yresult);


	//jos ehdot t‰yttyv‰t niin jaetaan tilaus kahteen osaan ja jaetaan loppusumma summanjakoprososa2:sen mukaan
	if ($mrow["summanjakoprososa2"] != 0 and $mrow["osamaksuehto1"] != 0 and $mrow["osamaksuehto2"] != 0) {

		$tulos_ulos_ehtosplit .= sprintf(t("Tilauksella %s on moniehto-maksuehto. Se jaettiin nyt kahdeksi tilaukseksi jakoprosentilla %s"), $laskurow["tunnus"], $mrow["summanjakoprososa2"])."<br>\n";

		if ($yhtiorow["alv_kasittely"] == '') {
			$query = "	SELECT round((varattu*hinta*(1-(ale/100)))/(1+alv/100),2) rivihinta, netto
						FROM tilausrivi
						WHERE otunnus 	= '$laskurow[tunnus]'
						and yhtio	  	= '$kukarow[yhtio]'
						and tyyppi		= 'L'";
		}
		else {
			$query = "	SELECT round((varattu*hinta*(1-(ale/100))),2) rivihinta, netto
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

		//laitetaan t‰lle alkuper‰iselle tilaukselle hyvitysrivi joka hyvitt‰‰ summanjakoprososa2:verran loopusummasta
		if ($yhtiorow["alv_kasittely"] == '') {
			$hyvitetaan = round($mrow["summanjakoprososa2"]/100*$kyht*(1+($laskurow["alv"]/100)),2);
		}
		else {
			$hyvitetaan = round($mrow["summanjakoprososa2"]/100*$kyht,2);
		}

		if ($hyvitetaan != 0) {
			$otunnus		= $laskurow['tunnus'];
			$hinta			= $hyvitetaan*-1;
			$alv 			= '';
			$trow["alv"]	= $laskurow["alv"];
			$nimitys		= t("Maksuehtohyvitys");

			if (trim($yhtiorow["maksuehto_tuotenumero"]) == "") {
				$tuoteno = t("Tuote");
			}
			else {
				$tuoteno = $yhtiorow["maksuehto_tuotenumero"];
			}

			require("alv.inc");

			$query  = "insert into tilausrivi (hinta, netto, varattu, tilkpl, otunnus, tuoteno, nimitys, yhtio, tyyppi, alv, kommentti, keratty, kerattyaika, toimitettu, toimitettuaika, toimaika) values  ('$hinta', 'N', '1', '1', '$otunnus', '$tuoteno', '$nimitys', '$kukarow[yhtio]', 'L', '$alv', '','$kukarow[kuka]',now(),'$kukarow[kuka]',now(),now())";
			$addtil = mysql_query($query) or pupe_error($query);

			//p‰ivitet‰‰n alkuper‰isen otsikon maksuehto
			$query = "	update lasku set
						maksuehto 	= '$mrow[osamaksuehto1]'
						where yhtio	= '$kukarow[yhtio]'
						and tunnus 	= '$laskurow[tunnus]'";
			$varresult = mysql_query($query) or pupe_error($query);

			$query = "	INSERT into lasku SET
						yhtio_nimi							= '$laskurow[yhtio_nimi]',
						yhtio_osoite						= '$laskurow[yhtio_osoite]',
						yhtio_postino						= '$laskurow[yhtio_postino]',
						yhtio_postitp						= '$laskurow[yhtio_postitp]',
						yhtio_maa							= '$laskurow[yhtio_maa]',
						yhtio_ovttunnus						= '$laskurow[yhtio_ovttunnus]',
						yhtio_kotipaikka					= '$laskurow[yhtio_kotipaikka]',
						yhtio_toimipaikka					= '$laskurow[yhtio_toimipaikka]',
						aktiivinen_kuljetus 				= '$laskurow[aktiivinen_kuljetus]',
						aktiivinen_kuljetus_kansallisuus	= '$laskurow[aktiivinen_kuljetus_kansallisuus]',
						alv									= '$laskurow[alv]',
						bruttopaino							= '$laskurow[bruttopaino]',
						chn									= '$laskurow[chn]',
						comments							= '$laskurow[comments]',
						erikoisale							= '$laskurow[erikoisale]',
						kauppatapahtuman_luonne			 	= '$laskurow[kauppatapahtuman_luonne]',
						kerayspvm							= '$laskurow[kerayspvm]',
						kontti								= '$laskurow[kontti]',
						kuljetusmuoto						= '$laskurow[kuljetusmuoto]',
						laatija								= '$laskurow[laatija]',
						laskutusvkopv						= '$laskurow[laskutusvkopv]',
						lisattava_era 						= '$laskurow[lisattava_era]',
						luontiaika							= '$laskurow[luontiaika]',
						maa 								= '$laskurow[maa]',
						maa_lahetys							= '$laskurow[maa_lahetys]',
						maa_maara							= '$laskurow[maa_maara]',
						maksuehto							= '$mrow[osamaksuehto2]',
						myyja								= '$laskurow[myyja]',
						nimi								= '$laskurow[nimi]',
						nimitark							= '$laskurow[nimitark]',
						osoite								= '$laskurow[osoite]',
						ovttunnus							= '$laskurow[ovttunnus]',
						poistumistoimipaikka 				= '$laskurow[poistumistoimipaikka]',
						poistumistoimipaikka_koodi 			= '$laskurow[poistumistoimipaikka_koodi]',
						postino								= '$laskurow[postino]',
						postitp								= '$laskurow[postitp]',
						sisainen							= '$laskurow[sisainen]',
						sisamaan_kuljetus 					= '$laskurow[sisamaan_kuljetus]',
						sisamaan_kuljetus_kansallisuus		= '$laskurow[sisamaan_kuljetus_kansallisuus]',
						sisamaan_kuljetusmuoto 				= '$laskurow[sisamaan_kuljetusmuoto]',
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
						kohdistettu							= '$laskurow[kohdistettu]',
						toimitustapa						= '$laskurow[toimitustapa]',
						tullausnumero						= '$laskurow[tullausnumero]',
						vahennettava_era					= '$laskurow[vahennettava_era]',
						valkoodi							= '$laskurow[valkoodi]',
						verkkotunnus						= '$laskurow[verkkotunnus]',
						vienti 								= '$laskurow[vienti]',
						viesti								= '$laskurow[viesti]',
						viikorkopros						= '$laskurow[viikorkopros]',
						yhtio								= '$laskurow[yhtio]',
						ytunnus								= '$laskurow[ytunnus]',
						liitostunnus						= '$laskurow[liitostunnus]',
						tilausyhteyshenkilo					= '$laskurow[tilausyhteyshenkilo]',
						asiakkaan_tilausnumero				= '$laskurow[asiakkaan_tilausnumero]',
						kohde								= '$laskurow[kohde]',
						sisviesti1							= '$laskurow[sisviesti1]',
						alatila								= 'D',
						tila								= 'L'";
			$uusires = mysql_query($query) or pupe_error($query);
			$uusitunnus = (string) mysql_insert_id();


			$otunnus		= $uusitunnus;
			$hinta			= $hyvitetaan;
			$alv 			= '';
			$trow["alv"]	= $laskurow["alv"];
			$nimitys		= t("Maksuehtoveloitus");

			if (trim($yhtiorow["maksuehto_tuotenumero"]) == "") {
				$tuoteno = t("Tuote");
			}
			else {
				$tuoteno = $yhtiorow["maksuehto_tuotenumero"];
			}

			require("alv.inc");

			$query  = "insert into tilausrivi (hinta, netto, varattu, tilkpl, otunnus, tuoteno, nimitys, yhtio, tyyppi, alv, kommentti, keratty, kerattyaika, toimitettu, toimitettuaika, toimaika) values  ('$hinta', 'N', '1', '1', '$otunnus', '$tuoteno', '$nimitys', '$kukarow[yhtio]', 'L', '$alv', '','$kukarow[kuka]',now(),'$kukarow[kuka]',now(),now())";
			$addtil = mysql_query($query) or pupe_error($query);
		}
	}

	//echotaan rudulle jos kyseess‰ ei ole batch-ajo
	if ($tulos_ulos == "" and  $silent == "") {
		echo "<br><font class='message'>".$tulos_ulos_ehtosplit."</font><br><br>";
	}

?>