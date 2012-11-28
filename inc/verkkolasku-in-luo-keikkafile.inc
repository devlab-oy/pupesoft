<?php

	// Tarvitaan $tunnus jossa on ostoreskontralaskun tunnus
	// Tarvitaan $trow jossa on toimittajan tiedot
	// Tarvitaan $rtuoteno jossa on array kaikista ostoreskontralaskun tuotteista

	// Haetaan ostoreskontralaskun tiedot
	$query = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$tunnus'";
	$osres = pupe_query($query);
	$osrow = mysql_fetch_array($osres);

	if ($osrow["ebid"] != "TECCOM-INVOICE") {
		$query  = "LOCK TABLE lasku WRITE, sanakirja WRITE, tiliointi READ";
		$result = pupe_query($query);

		// Haetaan seuraava vapaa keikkaid
		$query  = "	SELECT max(laskunro)+1
					from lasku
					where yhtio='$kukarow[yhtio]' and tila='K'";
		$result = pupe_query($query);
		$row    = mysql_fetch_array($result);

		$id	= $row[0]; 			// tämä on meidän keikannumero
		$keikkanumeroapu = $id; // talteen

		// Haetaan laskun kaikki verotiliöinnit jotta voidaan tallentaa myös veroton summa
		$query = "	SELECT sum(summa) summa
					from tiliointi
					where yhtio	= '$kukarow[yhtio]'
					and ltunnus	= '$osrow[tunnus]'
					and tilino  = '$yhtiorow[alv]'
					and korjattu = ''";
		$alvires = pupe_query($query);
		$alvirow = mysql_fetch_array($alvires);

		// Ostoreskontralaskun veroton arvo
		$osrow["arvo"] = $osrow["summa"] - $alvirow["summa"];

		// Tehdään itse keikkaotsikko
		$query = "	INSERT into lasku set
					yhtio        = '$kukarow[yhtio]',
					laskunro     = '$id',
					ytunnus	     = '$trow[ytunnus]',
					nimi         = '$trow[nimi]',
					valkoodi     = '$osrow[valkoodi]',
					vienti       = '$osrow[vienti]',
					maksu_kurssi = '$osrow[maksu_kurssi]',
					vienti_kurssi= '$osrow[vienti_kurssi]',
					toimitusehto = '$trow[toimitusehto]',
					osoite       = '$trow[osoite]',
					postitp      = '$trow[postitp]',
					maa			 = '$trow[maa]',
					maa_lahetys  = '$trow[maa]',
					kauppatapahtuman_luonne = '$trow[kauppatapahtuman_luonne]',
					kuljetusmuoto= '$trow[kuljetusmuoto]',
					swift        = '$trow[swift]',
					liitostunnus = '$trow[tunnus]',
					tila         = 'K',
					luontiaika	 = now(),
					laatija		 = 'verkkolas'";
		$result = pupe_query($query);

		$keikantunnus = mysql_insert_id(); // tätä tarvitaan tilausrivien uusiotunnukseen
		$laskuvirhe .= "\n".t("Perustettiin saapuminen").": $id $trow[nimi]\n";

		$query  = "UNLOCK TABLE";
		$result = pupe_query($query);

		// Liitetään vastaanotettu verkkolasku uuteen keikkaan
		// Tehdään liitosotsikko ostoreskontralaskulle
		verkkolasku_luo_liitosotsikko($tunnus, $keikantunnus);

		$laskuvirhe .= t("Liitettiin lasku").": $trow[nimi] $osrow[summa] $osrow[valkoodi]\n";
	}

	$kohdistettujariveja = 0;
	$kauttalaskutus		 = "";
	$kauttalaskutus_saldoton = array();
	$keikoilla	= array();
	$ostoilla	= array();
	$virhe		= "";
	$virheet 	= 0;

	for ($i=0; $i<count($rtuoteno); $i++) {
		// tehdään tää vaan jos meillä on rivinumero
		if ($rtuoteno[$i]["kauttalaskutus"] != "KAUTTALASKUTUS" and (int) $rtuoteno[$i]["tilaajanrivinro"] != 0 and $osrow['ebid'] != "TECCOM-INVOICE") {

			$toim_tuoteno 	= $rtuoteno[$i]["tuoteno"];
			$kpl			= (float) $rtuoteno[$i]["kpl"];
			$summa			= (float) $rtuoteno[$i]["rivihinta"];

			if ($kpl != 0) {
				$hinta = round($summa/$kpl, 2);
			}
			else {
				// jos kappaleet on nolla, oletetaan 1
				$hinta = $summa;
			}

			// etsitään hyvää osumaa!
			$query = "	SELECT tilausrivi.*, lasku.ketjutus
						FROM tilausrivi
						JOIN lasku on lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus and lasku.liitostunnus = '$trow[tunnus]'
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						AND tilausrivi.tunnus  = '{$rtuoteno[$i]["tilaajanrivinro"]}'
						AND tilausrivi.tyyppi  = 'O'";
			$result = pupe_query($query);

			if (mysql_num_rows($result) == 1) {

				$rivirow = mysql_fetch_array ($result);
				$kohdistettujariveja++;

				// jos rivi löytyi ja sitä ei ole kohdistettu vielä, kohdistetaan keikkaan ja päivitetään kpl
				if ($rivirow["uusiotunnus"] == 0) {
					$query = "UPDATE tilausrivi SET uusiotunnus='$keikantunnus', varattu='$kpl', hinta='$hinta' WHERE tunnus='$rivirow[tunnus]' AND yhtio='$kukarow[yhtio]'";
					$result = pupe_query($query);

					$laskuvirhe .= t("Tuotenumero").": $toim_tuoteno ".t("Kpl").": $kpl ".t("kohdistettu saapumiseen")."! ($keikantunnus/".$rtuoteno[$i]["tilaajanrivinro"].")\n";
				}
				// jos rivi löytyi, mutta se on jo kohdistettu.. lisätään uus samoilla tiedoilla
				else {
					$query = "	INSERT into tilausrivi
								(uusiotunnus, hinta, tuoteno, tilkpl, varattu, otunnus, yhtio, tyyppi, hyllyalue, hyllynro, hyllyvali, hyllytaso, tilaajanrivinro) values
								('$keikantunnus', '$hinta', '$rivirow[tuoteno]', '$rivirow[tilkpl]', '$kpl', '$rivirow[otunnus]', '$kukarow[yhtio]', 'O', '$rivirow[hyllyalue]', '$rivirow[hyllynro]', '$rivirow[hyllyvali]', '$rivirow[hyllytaso]','$rivirow[tilaajanrivinro]')";
					$result = pupe_query($query);
					$laskuvirhe .= t("Tuotenumero").": $toim_tuoteno ".t("Kpl").": $kpl ".t("lisätty saapumiseen")."! ($keikantunnus/".$rtuoteno[$i]["tilaajanrivinro"].")\n";
				}
			}
			else {
				$laskuvirhe .= t("Tuotenumero").": $toim_tuoteno ".t("Kpl").": $kpl ".t("Ei voitu kohdistaa saapumiseen")."! ($keikantunnus/".$rtuoteno[$i]["tilaajanrivinro"].")\n";
			}
		}
		elseif ($osrow["ebid"] == "TECCOM-INVOICE" and $rtuoteno[$i]["kauttalaskutus"] != "KAUTTALASKUTUS" and (int) $rtuoteno[$i]["tilaajanrivinro"] != 0) {
			// Onko ostolaskua ja onko kohdistamatta
			$query = "	SELECT tilausrivi.*
						FROM tilausrivi
						JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio
										AND lasku.tunnus = tilausrivi.otunnus)
						JOIN toimi ON (toimi.yhtio = tilausrivi.yhtio
										AND toimi.tunnus = lasku.liitostunnus
										AND toimi.tunnus = '$trow[tunnus]')
						JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio
														AND tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno
														AND tuotteen_toimittajat.toim_tuoteno = '".$rtuoteno[$i]["tuoteno"]."')
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						AND tilausrivi.tyyppi = 'O'
						AND tilausrivi.otunnus = '".$rtuoteno[$i]["ostotilausnro"]."'
						AND (tilausrivi.tunnus = '".$rtuoteno[$i]["tilaajanrivinro"]."' OR tilausrivi.tilaajanrivinro = '".$rtuoteno[$i]["tilaajanrivinro"]."')";
			$checkres = pupe_query($query);
			$tilausrivirow = mysql_fetch_array($checkres);

			if (mysql_num_rows($checkres) == 1 and $tilausrivirow['uusiotunnus'] == 0) {
				// löytyi, ei ole keikalla
				if ($rtuoteno[$i]["kpl"] <= $tilausrivirow["varattu"]) {
					$ostoilla[$i]["tunnus"] = $tilausrivirow['tunnus']; // tilausrivi tunnus
					$ostoilla[$i]["hinta"] = $rtuoteno[$i]["hinta"];
					$ostoilla[$i]["kpl"] = $rtuoteno[$i]["kpl"];
					$ostoilla[$i]["laskuntunnus"] = $rtuoteno[$i]["ostotilausnro"]; // laskun tunnus
				}
				else {
					echo "Virheellisiä kappalemääriä tilauksella ".$rtuoteno[$i]["ostotilausnro"]." tuote ".$rtuoteno[$i]["tuoteno"]." kpl ".$rtuoteno[$i]["kpl"]."\n";
					$virheet++;
					continue;
				}
			}
			elseif (mysql_num_rows($checkres) == 1 and $tilausrivirow['uusiotunnus'] != 0) {
				// löytyi, on jo keikalla
				if ($rtuoteno[$i]["kpl"] == $tilausrivirow["varattu"]) {
					$keikoilla[$i]["tunnus"] = $tilausrivirow['tunnus'];
					$keikoilla[$i]["uusiotunnus"] = $tilausrivirow['uusiotunnus'];
					$keikoilla[$i]["hinta"] = $rtuoteno[$i]["hinta"];
				}
				else {
					echo "Virheellisiä kappalemääriä tilauksella ".$rtuoteno[$i]["ostotilausnro"]." tuote ".$rtuoteno[$i]["tuoteno"]." kpl ".$rtuoteno[$i]["kpl"]."\n";
					$virheet++;
					continue;
				}
			}
			else {
				// ei löydy tai joku muu virhe
				echo "Virheellisiä kappalemääriä tilauksella ".$rtuoteno[$i]["ostotilausnro"]." tuote ".$rtuoteno[$i]["tuoteno"]." kpl ".$rtuoteno[$i]["kpl"]."\n";
				$virheet++;
				continue;
			}
		}
		elseif ($rtuoteno[$i]["kauttalaskutus"] == "KAUTTALASKUTUS" and $osrow['ebid'] != "TECCOM-INVOICE") {

			$query = "	SELECT *
						FROM tuote
						where yhtio = '$kukarow[yhtio]'
						and tuoteno = '".$rtuoteno[$i]["tuoteno"]."'";
			$result = pupe_query($query);
			$rivirow = mysql_fetch_array($result);

			if ($rivirow["tuoteno"] == "" or $rivirow["ei_saldoa"] != "") {
				// saldoton tai tuntematon tuote, viedään varastoon ja kauttalaskutetaan kuitenkin
				$rtuoteno[$i]["tuoteno"] = "1000";
			}

			$query = "	INSERT into tilausrivi set
						hyllyalue 		= '',
						hyllynro 		= '',
						hyllyvali 		= '',
						hyllytaso 		= '',
						tilaajanrivinro = '".$rtuoteno[$i]["tilaajanrivinro"]."',
						laatija 		= 'verkkolas',
						laadittu 		= now(),
						yhtio 			= '$kukarow[yhtio]',
						tuoteno 		= '".$rtuoteno[$i]["tuoteno"]."',
						varattu 		= '".$rtuoteno[$i]["kpl"]."',
						yksikko 		= '".$rtuoteno[$i]["yksikko"]."',
						kpl 			= 0,
						kpl2			= 0,
						tilkpl 			= '".$rtuoteno[$i]["kpl"]."',
						jt				= 0,
						ale1 			= '".$rtuoteno[$i]["ale"]."',
						alv 			= '',
						netto			= '',
						hinta 			= '".$rtuoteno[$i]["hinta"]."',
						rivihinta		= '".$rtuoteno[$i]["rivihinta"]."',
						kerayspvm 		= '',
						otunnus 		= '$keikantunnus',
						uusiotunnus 	= '$keikantunnus',
						tyyppi 			= 'O',
						toimaika 		= now(),
						kommentti 		= '".$rtuoteno[$i]["kommentti"]."',
						var 			= '',
						try				= '$rivirow[try]',
						osasto			= '$rivirow[osasto]',
						perheid			= '',
						perheid2		= '',
						nimitys 		= '".$rtuoteno[$i]["nimitys"]."',
						jaksotettu		= ''";
			$result = pupe_query($query);

			$kohdistettujariveja++;
			$kauttalaskutus = "kylla";
		}
	}

	// Jos kyseessä on TecCom oastolasku
	if ($osrow["ebid"] == "TECCOM-INVOICE") {
		// ensin ostoilta, sitten keikalta, sitten virheet.
		if (count($ostoilla) > 0 and count($keikoilla) == 0 and $virheet == 0) {

			$query = "	SELECT kurssi
						FROM valuu
						WHERE yhtio = '$kukarow[yhtio]'
						AND nimi = '$trow[oletus_valkoodi]'";
			$checkres = pupe_query($query);
			$row = mysql_fetch_array($checkres);
			$kurssi = $row["kurssi"];

			$query  = "LOCK TABLE lasku WRITE, sanakirja WRITE";
			$result = pupe_query($query);

			$query = "	SELECT max(laskunro)
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]'
						AND tila = 'K'";
			$checkres = pupe_query($query);
			$row = mysql_fetch_array($checkres);
			$id = $row[0] + 1;

			$maa_lahetys = $row['maa_lahetys'] != '' ? $trow['maa_lahetys'] : $trow['maa'];

			// meillä on $trow tullut parametrinä
			$query = "	INSERT into lasku set
						yhtio        			= '$kukarow[yhtio]',
						laskunro     			= '$id',
						ytunnus	     			= '$trow[ytunnus]',
						nimi         			= '$trow[nimi]',
						valkoodi     			= '$trow[oletus_valkoodi]',
						vienti       			= '$trow[oletus_vienti]',
						vienti_kurssi			= '$kurssi',
						toimitusehto 			= '$trow[toimitusehto]',
						osoite       			= '$trow[osoite]',
						postitp      			= '$trow[postitp]',
						maa			 			= '$trow[maa]',
						maa_lahetys 			= '$maa_lahetys',
						kauppatapahtuman_luonne	= '$trow[kauppatapahtuman_luonne]',
						kuljetusmuoto			= '$trow[kuljetusmuoto]',
						rahti					= '$trow[oletus_kulupros]',
						swift					= '$trow[swift]',
						liitostunnus 			= '$trow[tunnus]',
						comments				= '".t("Saapuminen luotu laskusta")." $osrow[laskunro]',
						tila         			= 'K',
						luontiaika	 			= now(),
						laatija		 			= '$kukarow[kuka]'";
			$insertres = pupe_query($query);
			$keikan_tunnus = mysql_insert_id();

			$query  = "UNLOCK TABLE";
			$result = pupe_query($query);

			verkkolasku_luo_liitosotsikko($tunnus, $keikan_tunnus);

			// lisätään tuotteet keikalle ja kohdistetaan
			foreach ($ostoilla as $pointteri) {

				$query = "	SELECT *
							FROM tilausrivi
							WHERE yhtio = '$kukarow[yhtio]'
							AND tunnus = '$pointteri[tunnus]'";
				$results = pupe_query($query);
				$tilausrivirow = mysql_fetch_assoc($results);

				// jos ei splitata, päivitetään hinta, mutta ei kohdisteta.
				if ($tilausrivirow['varattu'] == $pointteri["kpl"]) {
					$update = "	UPDATE tilausrivi SET
								hinta		= '$pointteri[hinta]',
								uusiotunnus = '$keikan_tunnus'
								WHERE yhtio = '$kukarow[yhtio]'
								AND tunnus = '$pointteri[tunnus]'";
					pupe_query($update);
				}
				elseif ($pointteri["kpl"] < $tilausrivirow["varattu"]) {
					// Splitataan tilausrivi, tallennetaan vanha rivitunnus tilausrivi.tilaajanrivinro kenttään

					$kappaleerotus = $tilausrivirow["varattu"] - $pointteri["kpl"];

					// Päivitetään alkuperäiselle riville saapunut kappalemäärä
					$query = "	UPDATE tilausrivi SET
								varattu = '$pointteri[kpl]'
								WHERE yhtio = '$kukarow[yhtio]'
								AND tunnus = '$tilausrivirow[tunnus]'";
					$upres = pupe_query($query);

					// Tehdään uusi rivi, jossa on jäljelle jääneet kappaleet
					$query = "	INSERT INTO tilausrivi SET
								yhtio = '$tilausrivirow[yhtio]',
								tyyppi = '$tilausrivirow[tyyppi]',
								toimaika = '$tilausrivirow[toimaika]',
								kerayspvm = '$tilausrivirow[kerayspvm]',
								otunnus = '$tilausrivirow[otunnus]',
								tuoteno = '$tilausrivirow[tuoteno]',
								try = '$tilausrivirow[try]',
								osasto = '$tilausrivirow[osasto]',
								nimitys = '$tilausrivirow[nimitys]',
								yksikko = '$tilausrivirow[yksikko]',
								varattu = '$kappaleerotus',
								hinta = '$tilausrivirow[hinta]',
								laatija = '$tilausrivirow[laatija]',
								laadittu = '$tilausrivirow[laadittu]',
								hyllyalue = '$tilausrivirow[hyllyalue]',
								hyllynro = '$tilausrivirow[hyllynro]',
								hyllytaso = '$tilausrivirow[hyllytaso]',
								hyllyvali = '$tilausrivirow[hyllyvali]',
								tilaajanrivinro = '$tilausrivirow[tunnus]'";
					$inskres = pupe_query($query);
				}
				else {
					echo "VIRHE !! Tuote ($tilausrivirow[tuoteno]) on toimitettu enemmän kuin on tilattu. Tarkista tilausnumero: $tilausrivirow[otunnus]\n";
				}
			}

		}
		elseif (count($ostoilla) == 0 and count($keikoilla) > 0 and $virheet == 0) {
			// jos tullaan tähän kohtaan niin kappalemäärät täsmää. On jo tarkistettu rivillä 166
			// muutetaan hinta ja rivi on ok.
			verkkolasku_luo_liitosotsikko($tunnus, $keikoilla[0]["uusiotunnus"]);

			foreach ($keikoilla as $pointteri) {
				$update = "UPDATE tilausrivi set hinta = '".$pointteri["hinta"]."' where yhtio='$kukarow[yhtio]' and tunnus = '".$pointteri["tunnus"]."'";
				pupe_query($update);
			}

		}
		else {
			echo "Laskulla oli virheitä, emme tehneet mitään.\n";
		}
	}

	// tämän keikan voi viedä saldoille ja laskuttaa
	if ($kohdistettujariveja > 0 and $kauttalaskutus == "kylla") {
		$otunnus = $keikantunnus;

		$query = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$otunnus'";
		$result = pupe_query($query);
		$laskurow = mysql_fetch_array($result);

		if ($kauttalaskutus == "kylla") {

			// jos kysessä on kotimainen vaihto-omaisuuslasku, pitää lisätä tuotteen hintaan alvi
			if ($laskurow['vienti'] == 'C' or $laskurow['vienti'] == 'J') {

				$alvit = "tuote.alv";

				if ($laskurow["maa"] != "" and $laskurow["maa"] != $yhtiorow["maa"]) {
					// tutkitaan ollaanko siellä alv-rekisteröity
					$alhqur = "SELECT * from yhtion_toimipaikat where yhtio='$kukarow[yhtio]' and maa='$laskurow[maa]' and vat_numero != ''";
					$alhire = pupe_query($alhqur);

					// ollaan alv-rekisteröity
					if (mysql_num_rows($alhire) == 1) {
						$alvit = "tuotteen_alv.alv";
					}
				}
			}
			else {
				$alvit = 0;
			}

			$query_ale_lisa = generoi_alekentta('O');

			// Katostaan stemmaako keikka?
			$query = "	SELECT sum((tilausrivi.varattu+tilausrivi.kpl)*if (tuotteen_toimittajat.tuotekerroin<=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin) * tilausrivi.hinta * {$query_ale_lisa} *
						(1+(if ((SELECT max(kaytetty) kaytetty
								FROM sarjanumeroseuranta
								WHERE sarjanumeroseuranta.yhtio=tilausrivi.yhtio
								and sarjanumeroseuranta.tuoteno=tilausrivi.tuoteno
								and ((tilausrivi.varattu+tilausrivi.kpl < 0 and sarjanumeroseuranta.myyntirivitunnus=tilausrivi.tunnus) or (tilausrivi.varattu+tilausrivi.kpl > 0 and sarjanumeroseuranta.ostorivitunnus=tilausrivi.tunnus))) = 'K', 0, $alvit)/100))) hinta
						FROM tilausrivi use index (uusiotunnus_index)
						JOIN tuote use index (tuoteno_index) ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
						LEFT JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio AND tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno AND tuotteen_toimittajat.liitostunnus = '$laskurow[liitostunnus]')
						LEFT JOIN tuotteen_alv ON (tuotteen_alv.yhtio = tilausrivi.yhtio AND tuotteen_alv.tuoteno = tilausrivi.tuoteno AND tuotteen_alv.maa = '$laskurow[maa]')
						WHERE tilausrivi.yhtio ='$kukarow[yhtio]'
						AND tilausrivi.uusiotunnus = '$otunnus'
						AND tilausrivi.tyyppi != 'D'";
			$result   = pupe_query($query);
			$hintarow = mysql_fetch_array($result);

			$valittusumma 			= round($hintarow["hinta"], 2);
			$laskunsumma  			= round($osrow["summa"]-$valittusumma, 2);
			$checksumma   			= $osrow["summa"]-$hintarow["hinta"];
			$kohdistettava_summa 	= round($osrow["summa"], 2);
			$valitutrivit 			= mysql_num_rows($result);

			if ($laskunsumma != 0.00 and $checksumma < 0.01 and $checksumma > -0.01 and $kohdistettava_summa == $valittusumma) {
				$laskunsumma = 0.00;
			}

			if ($laskunsumma != 0) {
				$rahti_etu = $laskunsumma;
			}
			else {
				$rahti_etu = 0;
			}

			// päivitetään samantien tämä tieto laskulle
			$query   = "UPDATE lasku set summa='$hintarow[hinta]', rahti_etu='$rahti_etu', kohdistettu='K' where yhtio ='$kukarow[yhtio]' and tunnus='$otunnus'";
			$hinresu = pupe_query($query);
		}

		$laskuvirhe .= t("Viedään kauttalaskutus varastoon").". ($laskurow[laskunro])\n";

		$toiminto = "kalkyyli";
		$tee = "varastoon";

		// viedään varastoon...
		require ("tilauskasittely/varastoon.inc");

		// laskutetaan suoratoimitetut jtrivit jos niitä oli tällä keikalla...
		if ($kauttalaskutus == "kylla") {
			$toiminto = "kaikkiok";
			$tee = "varma";

			// viedään varastoon TEHDÄÄN LOPPUKALKYYLI
			require ("tilauskasittely/varastoon.inc");

			require ("tilauskasittely/laskuta_kauttalaskutus.inc");
		}
	}
	elseif ($kohdistettujariveja == 0 and $aladellaa == "") {
		// dellataan perustettu keikka ku ei kerran löytyny rivejä.. jos ei olla asetettu ÄLÄDELLAA muuttujaaa
		$query = "DELETE from lasku WHERE yhtio='$kukarow[yhtio]' and tila='K' and laskunro='$keikkanumeroapu'";
		$result = pupe_query($query);

		$laskuvirhe .= t("Poistettiin")." ".t("saapuminen")." $keikkanumeroapu ".t("Yhtään riviä ei löytynyt")."!\n";
	}
	else {
		$laskuvirhe .= t("Saapuminen käsitelty loppuun").": $keikkanumeroapu $trow[nimi]\n";
	}
?>