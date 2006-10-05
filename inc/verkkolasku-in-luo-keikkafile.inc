<?php
	// tarvitaan $trow jossa on toimittajan tiedot
	// tarvitaan $tpv-$tpk-$tpp jossa on tapvm
	// tarvitaan $omasumma jossa on laskun verollinen summa
	// tarvitaan $verkkolaskuveroton jossa on laskun veroton summa
	// $ytunnus josaa on ulaskun ytunnus
	// tarvitaan $viite jossa on laskun viite
	
	//haetaan seuraava vapaa keikkaid
	$query  = "LOCK TABLE lasku WRITE, sanakirja WRITE";
	$result = mysql_query($query) or pupe_error($query);
	
	$query  = "select max(laskunro) from lasku where yhtio='$kukarow[yhtio]' and tila='K'";
	$result = mysql_query($query) or pupe_error($query);
	$row    = mysql_fetch_array($result);
	$id		= $row[0]+1; // tämä on meidän keikannumero
	$keikkanumeroapu = $id; // talteen
	
	// meillä on trow haettuna ylhäällä
	$query = "	insert into lasku set
				yhtio        = '$kukarow[yhtio]',
				laskunro     = '$id',
				ytunnus	     = '$trow[ytunnus]',
				nimi         = '$trow[nimi]',
				valkoodi     = 'EUR',
				vienti       = '$trow[oletus_vienti]',
				maksu_kurssi = '1',
				vienti_kurssi= '1',
				toimitusehto = '$trow[toimitusehto]',
				osoite       = '$trow[osoite]',
				postitp      = '$trow[postitp]', 
				maa			 = '$trow[maa]', 
				maakoodi	 = '$trow[maakoodi]',
				maa_lahetys  = '$trow[maakoodi]',
				swift        = '$trow[swift]',
				liitostunnus = '$trow[tunnus]',
				tila         = 'K',
				luontiaika	 = now(),
				laatija		 = 'verkkolas',
				tapvm		 = '$tpv-$tpk-$tpp',
				summa		 = '$omasumma',
				arvo		 = '$verkkolaskuveroton'";
	$result = mysql_query($query) or pupe_error($query);

	$keikantunnus = mysql_insert_id(); // tätä tarvitaan tilausrivien uusiotunnukseen

	$laskuvirhe .= "\n".t("Perustettiin keikka").": $id $trow[nimi]\n";

	$query  = "UNLOCK TABLE";
	$result = mysql_query($query) or pupe_error($query);
	// Liitetään vastaanotettu verkkolasku uuteen keikkaan

	// tehdään liitosotsikko
	$query = "	insert into lasku set
				yhtio        = '$kukarow[yhtio]',
				laskunro     = '$id',
				ytunnus	     = '$ytunnus',
				nimi         = '$trow[nimi]',
				valkoodi     = 'EUR',
				maksu_kurssi = '1',
				vienti_kurssi= '1',
				toimitusehto = '$trow[toimitusehto]',
				vanhatunnus  = '$tunnus',
				arvo 		 = '$verkkolaskuveroton',
				viite		 = '$viite',
				summa        = '$omasumma',
				vienti       = '$trow[oletus_vienti]',
				liitostunnus = '$trow[tunnus]',
				luontiaika	 = now(),
				laatija 	 = 'verkkolas',
				tila         = 'K'";
	$result = mysql_query($query) or pupe_error($query);

	$laskuvirhe .= t("Liitettiin lasku").": $trow[nimi] $omasumma EUR\n";

	$kohdistettujariveja = 0;
	$suoratoimitus       = "";

	for ($i=0; $i<count($tuoteno); $i++) {

		// tehdään tää vaan jos meillä on rivinumero
		if ((int) $rrivino[$i] != 0) {
			
			$toim_tuoteno 	= $tuoteno[$i];
			$kpl			= (float) $rkpl[$i];
			$summa			= (float) $rsumma[$i];
		
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
						join lasku on lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus
						where tilausrivi.yhtio = '$kukarow[yhtio]' and 
						tilausrivi.tunnus = '$rrivino[$i]' and 
						tilausrivi.tyyppi = 'O'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 1) {
			
				$rivirow = mysql_fetch_array ($result);
				$kohdistettujariveja++;

				// jos ketjutus on yhdelläkin tilauksella 1 niin kyseessä on SUORATOIMITUS
				if ($rivirow["ketjutus"] == "1") {
					$suoratoimitus = "kylla";
				}

				// jos rivi löytyi ja sitä ei ole kohdistettu vielä, kohdistetaan keikkaan ja päivitetään kpl
				if ($rivirow["uusiotunnus"] == 0) {
					$query = "UPDATE tilausrivi SET uusiotunnus='$keikantunnus', varattu='$kpl', hinta='$hinta' WHERE tunnus='$rivirow[tunnus]' AND yhtio='$kukarow[yhtio]'";
					$result = mysql_query($query) or pupe_error($query);
					$laskuvirhe .= t("Tuotenumero").": $toim_tuoteno ".t("Kpl").": $kpl ".t("kohdistettu laskuun")."! ($keikantunnus/$rrivino[$i])\n";
				}
				// jos rivi löytyi, mutta se on jo kohdistettu.. lisätään uus samoilla tiedoilla
				else {
					$query = "	insert into tilausrivi 
								(uusiotunnus, hinta, tuoteno, tilkpl, varattu, otunnus, yhtio, tyyppi, hyllyalue, hyllynro, hyllyvali, hyllytaso) values
								('$keikantunnus', '$hinta', '$rivirow[tuoteno]', '$rivirow[tilkpl]', '$kpl', '$rivirow[otunnus]', '$kukarow[yhtio]', 'O', '$rivirow[hyllyalue]', '$rivirow[hyllynro]', '$rivirow[hyllyvali]', '$rivirow[hyllytaso]')";
					$result = mysql_query($query) or pupe_error($query);
					$laskuvirhe .= t("Tuotenumero").": $toim_tuoteno ".t("Kpl").": $kpl ".t("lisätty laskuun")."! ($keikantunnus/$rrivino[$i])\n";
				}
			}
			else {
				$laskuvirhe .= t("Tuotenumero").": $toim_tuoteno ".t("Kpl").": $kpl ".t("Ei voitu kohdistaa laskuun")."! ($keikantunnus/$rrivino[$i])\n";
			}

		} // end if rivitunnus erisuuri ku nolla

	} // end for looppi
	

	// tämän keikan voi viedä saldoille ja laskuttaa koska se on suoratoimitus
	if ($kohdistettujariveja > 0 and $suoratoimitus == "kylla") {
		$otunnus = $keikantunnus;

		$query = "select * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$otunnus'";
		$result = mysql_query($query) or pupe_error($query);
		$laskurow = mysql_fetch_array($result);

		$laskuvirhe .= t("Viedään suoratoimitus varastoon").". ($laskurow[laskunro])\n";

		// viedään varastoon...
		require ("tilauskasittely/varastoon.inc");
		// laskutetaan suoratoimitetut jtrivit jos niitä oli tällä keikalla...
		require ("tilauskasittely/jt_super_laskuta.inc");
	}
	elseif ($kohdistettujariveja == 0 and $aladellaa == "") {
		// dellataan perustettu keikka ku ei kerran löytyny rivejä.. jos ei olla asetettu ÄLÄDELLAA muuttujaaa
		$query = "delete from lasku WHERE yhtio='$kukarow[yhtio]' and tila='K' and laskunro='$keikkanumeroapu'";
		$result = mysql_query($query) or pupe_error($query);
		$laskuvirhe .= t("Poistettiin")." ".t("keikka")." $keikkanumeroapu ".t("Yhtään riviä ei löytynyt")."!\n";
	}
	else {
		$laskuvirhe .= t("Keikka käsitelty loppuun").": $keikkanumeroapu $trow[nimi]\n";
	}

?>