<?php

	// Tarvitaan $tunnus jossa on ostoreskontralaskun tunnus
	// Tarvitaan $trow jossa on toimittajan tiedot
	// Tarvitaan $rtuoteno jossa on array kaikista ostoreskontralaskun tuotteista

	$query  = "LOCK TABLE lasku WRITE, sanakirja WRITE, tiliointi READ";
	$result = mysql_query($query) or pupe_error($query);

	// Haetaan seuraava vapaa keikkaid
	$query  = "	SELECT max(laskunro)+1
				from lasku
				where yhtio='$kukarow[yhtio]' and tila='K'";
	$result = mysql_query($query) or pupe_error($query);
	$row    = mysql_fetch_array($result);

	$id	= $row[0]; 			// tämä on meidän keikannumero
	$keikkanumeroapu = $id; // talteen

	// Haetaan ostoreskontralaskun tiedot
	$query = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$tunnus'";
	$osres = mysql_query($query) or pupe_error($query);
	$osrow = mysql_fetch_array($osres);

	// Haetaan laskun kaikki verotiliöinnit jotta voidaan tallentaa myös veroton summa
	$query = "	SELECT sum(summa) summa
				from tiliointi
				where yhtio	= '$kukarow[yhtio]'
				and ltunnus	= '$osrow[tunnus]'
				and tilino  = '$yhtiorow[alv]'
				and korjattu = ''";
	$alvires = mysql_query($query) or pupe_error($query);
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
	$result = mysql_query($query) or pupe_error($query);

	$keikantunnus = mysql_insert_id(); // tätä tarvitaan tilausrivien uusiotunnukseen

	$laskuvirhe .= "\n".t("Perustettiin keikka").": $id $trow[nimi]\n";

	$query  = "UNLOCK TABLE";
	$result = mysql_query($query) or pupe_error($query);

	// Liitetään vastaanotettu verkkolasku uuteen keikkaan
	// Tehdään liitosotsikko ostoreskontralaskulle
	verkkolasku_luo_liitosotsikko($tunnus, $keikantunnus);

	$laskuvirhe .= t("Liitettiin lasku").": $trow[nimi] $osrow[summa] $osrow[valkoodi]\n";

	$kohdistettujariveja = 0;
	$suoratoimitus       = "";
	$kauttalaskutus		 = "";
	$kauttalaskutus_saldoton = array();

	for ($i=0; $i<count($rtuoteno); $i++) {

		// tehdään tää vaan jos meillä on rivinumero
		if ($rtuoteno[$i]["kauttalaskutus"] != "KAUTTALASKUTUS" and (int) $rtuoteno[$i]["tilaajanrivinro"] != 0) {

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
						join lasku on lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus
						where tilausrivi.yhtio = '$kukarow[yhtio]' and
						tilausrivi.tunnus = '".$rtuoteno[$i]["tilaajanrivinro"]."' and
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

					$laskuvirhe .= t("Tuotenumero").": $toim_tuoteno ".t("Kpl").": $kpl ".t("kohdistettu keikkaan")."! ($keikantunnus/".$rtuoteno[$i]["tilaajanrivinro"].")\n";
				}
				// jos rivi löytyi, mutta se on jo kohdistettu.. lisätään uus samoilla tiedoilla
				else {
					$query = "	INSERT into tilausrivi
								(uusiotunnus, hinta, tuoteno, tilkpl, varattu, otunnus, yhtio, tyyppi, hyllyalue, hyllynro, hyllyvali, hyllytaso, tilaajanrivinro) values
								('$keikantunnus', '$hinta', '$rivirow[tuoteno]', '$rivirow[tilkpl]', '$kpl', '$rivirow[otunnus]', '$kukarow[yhtio]', 'O', '$rivirow[hyllyalue]', '$rivirow[hyllynro]', '$rivirow[hyllyvali]', '$rivirow[hyllytaso]','$rivirow[tilaajanrivinro]')";
					$result = mysql_query($query) or pupe_error($query);
					$laskuvirhe .= t("Tuotenumero").": $toim_tuoteno ".t("Kpl").": $kpl ".t("lisätty keikkaan")."! ($keikantunnus/".$rtuoteno[$i]["tilaajanrivinro"].")\n";
				}
			}
			else {
				$laskuvirhe .= t("Tuotenumero").": $toim_tuoteno ".t("Kpl").": $kpl ".t("Ei voitu kohdistaa keikkaan")."! ($keikantunnus/".$rtuoteno[$i]["tilaajanrivinro"].")\n";
			}
		}
		elseif ($rtuoteno[$i]["kauttalaskutus"] == "KAUTTALASKUTUS") {

			$query = "	SELECT *
						FROM tuote
						where yhtio = '$kukarow[yhtio]'
						and tuoteno = '".$rtuoteno[$i]["tuoteno"]."'";
			$result = mysql_query($query) or pupe_error($query);
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
						ale 			= '".$rtuoteno[$i]["ale"]."',
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
			$result = mysql_query($query) or pupe_error($query);

			$kohdistettujariveja++;
			$kauttalaskutus = "kylla";
		}
	}

	// tämän keikan voi viedä saldoille ja laskuttaa koska se on suoratoimitus
	if ($kohdistettujariveja > 0 and ($suoratoimitus == "kylla" or $kauttalaskutus == "kylla")) {
		$otunnus = $keikantunnus;

		$query = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$otunnus'";
		$result = mysql_query($query) or pupe_error($query);
		$laskurow = mysql_fetch_array($result);

		if ($kauttalaskutus == "kylla") {
			// jos erikoisale niin otetaan sitä kanssa huomioon
			if ($laskurow['erikoisale'] <> 0) {
				$erikoisale = 1 - ($laskurow['erikoisale']/100);
			}
			else {
				$erikoisale = 1;
			}

			// jos kysessä on kotimainen vaihto-omaisuuslasku, pitää lisätä tuotteen hintaan alvi
			if ($laskurow['vienti'] == 'C' or $laskurow['vienti'] == 'J') {

				$alvit = "tuote.alv";

				if ($laskurow["maa"] != "" and $laskurow["maa"] != $yhtiorow["maa"]) {
					// tutkitaan ollaanko siellä alv-rekisteröity
					$alhqur = "SELECT * from yhtion_toimipaikat where yhtio='$kukarow[yhtio]' and maa='$laskurow[maa]' and vat_numero != ''";
					$alhire = mysql_query($alhqur) or pupe_error($alhqur);

					// ollaan alv-rekisteröity
					if (mysql_num_rows($alhire) == 1) {
						$alvit = "tuotteen_alv.alv";
					}
				}
			}
			else {
				$alvit = 0;
			}

			// Katostaan stemmaako keikka?
			$query = "	SELECT sum((tilausrivi.varattu+tilausrivi.kpl)*if (tuotteen_toimittajat.tuotekerroin<=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin) * tilausrivi.hinta * (1-(tilausrivi.ale/100)) * $erikoisale *
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
			$result   = mysql_query($query) or pupe_error($query);
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
			$hinresu = mysql_query($query) or pupe_error($query);
		}

		$laskuvirhe .= t("Viedään suoratoimitus varastoon").". ($laskurow[laskunro])\n";

		$toiminto = "kalkyyli";
		$tee = "varastoon";

		// viedään varastoon...
		require ("tilauskasittely/varastoon.inc");

		// laskutetaan suoratoimitetut jtrivit jos niitä oli tällä keikalla...
		if ($suoratoimitus == "kylla") {
			require ("tilauskasittely/jt_super_laskuta.inc");
		}
		elseif ($kauttalaskutus == "kylla") {
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
		$result = mysql_query($query) or pupe_error($query);

		$laskuvirhe .= t("Poistettiin")." ".t("keikka")." $keikkanumeroapu ".t("Yhtään riviä ei löytynyt")."!\n";
	}
	else {
		$laskuvirhe .= t("Keikka käsitelty loppuun").": $keikkanumeroapu $trow[nimi]\n";
	}
?>