<?php

	// Tarvitaan $tunnus jossa on ostoreskontralaskun tunnus
	// Tarvitaan $trow jossa on toimittajan tiedot
	// Tarvitaan $rtuoteno jossa on array kaikista ostoreskontralaskun tuotteista

	// Haetaan ostoreskontralaskun tiedot
	$query = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$tunnus'";
	
	$osres = pupe_query($query);
	$osrow = mysql_fetch_array($osres);
	$laskun_numero = $osrow["laskunro"]; // ‰l‰ mene muuttaamaan tai poistamaan t‰t‰ muuttujaa.!!! Tai no joo... Ongelma..

	if ($osrow["ebid"] != "TECCOM-INVOICE") {
		$query  = "LOCK TABLE lasku WRITE, sanakirja WRITE, tiliointi READ";
		$result = pupe_query($query);

		// Haetaan seuraava vapaa keikkaid
		$query  = "	SELECT max(laskunro)+1
					from lasku
					where yhtio='$kukarow[yhtio]' and tila='K'";
		$result = pupe_query($query);
		$row    = mysql_fetch_array($result);

		$id	= $row[0]; 			// t‰m‰ on meid‰n keikannumero
		$keikkanumeroapu = $id; // talteen

		// Haetaan laskun kaikki verotiliˆinnit jotta voidaan tallentaa myˆs veroton summa
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

		// Tehd‰‰n itse keikkaotsikko
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

		$keikantunnus = mysql_insert_id(); // t‰t‰ tarvitaan tilausrivien uusiotunnukseen
		$laskuvirhe .= "\n".t("Perustettiin keikka").": $id $trow[nimi]\n";

		$query  = "UNLOCK TABLE";
		$result = pupe_query($query);

		// Liitet‰‰n vastaanotettu verkkolasku uuteen keikkaan
		// Tehd‰‰n liitosotsikko ostoreskontralaskulle
		verkkolasku_luo_liitosotsikko($tunnus, $keikantunnus);

		$laskuvirhe .= t("Liitettiin lasku").": $trow[nimi] $osrow[summa] $osrow[valkoodi]\n";
	}

	$kohdistettujariveja = 0;
	$suoratoimitus       = "";
	$kauttalaskutus		 = "";
	$kauttalaskutus_saldoton = array();
	$keikoilla	= array();
	$ostoilla	= array();
	$virhe		= "";
	$virheet 	= 0;

	for ($i=0; $i<count($rtuoteno); $i++) {
		// tehd‰‰n t‰‰ vaan jos meill‰ on rivinumero
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

			// etsit‰‰n hyv‰‰ osumaa!
			$query = "	SELECT tilausrivi.*, lasku.ketjutus
						FROM tilausrivi
						join lasku on lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus
						where tilausrivi.yhtio = '$kukarow[yhtio]' and
						tilausrivi.tunnus = '".$rtuoteno[$i]["tilaajanrivinro"]."' and
						tilausrivi.tyyppi = 'O'";
			$result = pupe_query($query);

			if (mysql_num_rows($result) == 1) {

				$rivirow = mysql_fetch_array ($result);
				$kohdistettujariveja++;

				// jos ketjutus on yhdell‰kin tilauksella 1 niin kyseess‰ on SUORATOIMITUS
				if ($rivirow["ketjutus"] == "1") {
					$suoratoimitus = "kylla";
				}

				// jos rivi lˆytyi ja sit‰ ei ole kohdistettu viel‰, kohdistetaan keikkaan ja p‰ivitet‰‰n kpl
				if ($rivirow["uusiotunnus"] == 0) {
					$query = "UPDATE tilausrivi SET uusiotunnus='$keikantunnus', varattu='$kpl', hinta='$hinta' WHERE tunnus='$rivirow[tunnus]' AND yhtio='$kukarow[yhtio]'";
					$result = pupe_query($query);

					$laskuvirhe .= t("Tuotenumero").": $toim_tuoteno ".t("Kpl").": $kpl ".t("kohdistettu keikkaan")."! ($keikantunnus/".$rtuoteno[$i]["tilaajanrivinro"].")\n";
				}
				// jos rivi lˆytyi, mutta se on jo kohdistettu.. lis‰t‰‰n uus samoilla tiedoilla
				else {
					$query = "	INSERT into tilausrivi
								(uusiotunnus, hinta, tuoteno, tilkpl, varattu, otunnus, yhtio, tyyppi, hyllyalue, hyllynro, hyllyvali, hyllytaso, tilaajanrivinro) values
								('$keikantunnus', '$hinta', '$rivirow[tuoteno]', '$rivirow[tilkpl]', '$kpl', '$rivirow[otunnus]', '$kukarow[yhtio]', 'O', '$rivirow[hyllyalue]', '$rivirow[hyllynro]', '$rivirow[hyllyvali]', '$rivirow[hyllytaso]','$rivirow[tilaajanrivinro]')";
					$result = pupe_query($query);
					$laskuvirhe .= t("Tuotenumero").": $toim_tuoteno ".t("Kpl").": $kpl ".t("lis‰tty keikkaan")."! ($keikantunnus/".$rtuoteno[$i]["tilaajanrivinro"].")\n";
				}
			}
			else {
				$laskuvirhe .= t("Tuotenumero").": $toim_tuoteno ".t("Kpl").": $kpl ".t("Ei voitu kohdistaa keikkaan")."! ($keikantunnus/".$rtuoteno[$i]["tilaajanrivinro"].")\n";
			}
		}
		elseif ($osrow["ebid"] == "TECCOM-INVOICE" and $rtuoteno[$i]["kauttalaskutus"] != "KAUTTALASKUTUS" and (int) $rtuoteno[$i]["tilaajanrivinro"] != 0) {
			// Onko ostolaskua ja onko kohdistamatta

			// Mik‰li toimittajalla on kentt‰ "asn_sanomat" arvoja, ja ne on
			// K = tulee asn-sanoma ja lasku
			// S = tulee vain sanoma   <--- ei tehd‰ mit‰‰n
			// L = tulee vain lasku

			// tiukka 1
			$query = "	SELECT tilausrivi.*, toimi.asn_sanomat
						FROM tilausrivi
						JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio
										AND lasku.tunnus = tilausrivi.uusiotunnus
										AND lasku.mapvm = '0000-00-00')
						JOIN toimi ON (toimi.yhtio = tilausrivi.yhtio
										AND toimi.tunnus = lasku.liitostunnus
										AND toimi.tunnus = '{$trow['tunnus']}'
										AND toimi.tyyppi != 'P'
										AND toimi.asn_sanomat IN ('K', 'L'))
						JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio
														AND tuotteen_toimittajat.liitostunnus = toimi.tunnus
														AND tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno
														AND tuotteen_toimittajat.toim_tuoteno = '{$rtuoteno[$i]['tuoteno']}')
						JOIN tuote on (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tuotteen_toimittajat.tuoteno AND tuote.status != 'P')
						WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
						AND tilausrivi.tyyppi = 'O'
						AND tilausrivi.otunnus = '{$rtuoteno[$i]['ostotilausnro']}' AND tilausrivi.tilaajanrivinro = '{$rtuoteno[$i]['tilaajanrivinro']}'
						AND tilausrivi.uusiotunnus != 0
						ORDER BY laadittu ASC";
			$kokeillaan = pupe_query($query);

			if (mysql_num_rows($kokeillaan) == 0) {
				// tiukka 2
				$query = "	SELECT tilausrivi.*, toimi.asn_sanomat
							FROM tilausrivi
							JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio
											AND lasku.tunnus = tilausrivi.uusiotunnus
											AND lasku.mapvm = '0000-00-00')
							JOIN toimi ON (toimi.yhtio = tilausrivi.yhtio
											AND toimi.tunnus = lasku.liitostunnus
											AND toimi.tunnus = '{$trow['tunnus']}'
											AND toimi.tyyppi != 'P'
											AND toimi.asn_sanomat IN ('K', 'L'))
							JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio
															AND tuotteen_toimittajat.liitostunnus = toimi.tunnus
															AND tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno
															AND tuotteen_toimittajat.toim_tuoteno = '{$rtuoteno[$i]['tuoteno']}')
							JOIN tuote on (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tuotteen_toimittajat.tuoteno AND tuote.status != 'P')
							WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
							AND tilausrivi.tyyppi = 'O'
							AND tilausrivi.uusiotunnus != 0
							AND lasku.comments LIKE '%{$rtuoteno[$i]['ostotilausnro']}%' AND tilausrivi.tilaajanrivinro = '{$rtuoteno[$i]['tilaajanrivinro']}'
							ORDER BY laadittu ASC";
				$kokeillaan = pupe_query($query);
			}

			if (mysql_num_rows($kokeillaan) == 0) {
				// v‰ljempi 1
				$query = "	SELECT tilausrivi.*, toimi.asn_sanomat
							FROM tilausrivi
							JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio
											AND lasku.tunnus = tilausrivi.uusiotunnus
											AND lasku.mapvm = '0000-00-00')
							JOIN toimi ON (toimi.yhtio = tilausrivi.yhtio
											AND toimi.tunnus = lasku.liitostunnus
											AND toimi.tunnus = '{$trow['tunnus']}'
											AND toimi.tyyppi != 'P'
											AND toimi.asn_sanomat IN ('K', 'L'))
							JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio
															AND tuotteen_toimittajat.liitostunnus = toimi.tunnus
															AND tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno
															AND tuotteen_toimittajat.toim_tuoteno = '{$rtuoteno[$i]['tuoteno']}')
							JOIN tuote on (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tuotteen_toimittajat.tuoteno AND tuote.status != 'P')
							WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
							AND tilausrivi.tyyppi = 'O'
							AND tilausrivi.otunnus = '{$rtuoteno[$i]['ostotilausnro']}'
							AND tilausrivi.uusiotunnus != 0
							ORDER BY laadittu ASC";
				$kokeillaan = pupe_query($query);
			}

			if (mysql_num_rows($kokeillaan) == 0) {
				// v‰ljempi 2
				$query = "	SELECT tilausrivi.*, toimi.asn_sanomat
							FROM tilausrivi
							JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio
											AND lasku.tunnus = tilausrivi.uusiotunnus
											AND lasku.mapvm = '0000-00-00')
							JOIN toimi ON (toimi.yhtio = tilausrivi.yhtio
											AND toimi.tunnus = lasku.liitostunnus
											AND toimi.tunnus = '{$trow['tunnus']}'
											AND toimi.tyyppi != 'P'
											AND toimi.asn_sanomat IN ('K', 'L'))
							JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio
															AND tuotteen_toimittajat.liitostunnus = toimi.tunnus
															AND tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno
															AND tuotteen_toimittajat.toim_tuoteno = '{$rtuoteno[$i]['tuoteno']}')
							JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tuotteen_toimittajat.tuoteno AND tuote.status != 'P')
							WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
							AND tilausrivi.tyyppi = 'O'
							AND tilausrivi.uusiotunnus != 0
							AND lasku.comments LIKE '%{$rtuoteno[$i]['ostotilausnro']}%'
							ORDER BY laadittu ASC";
				$kokeillaan = pupe_query($query);
			}

			if (mysql_num_rows($kokeillaan) == 0) {
				// lˆys‰
				$query = "	SELECT tilausrivi.*, toimi.asn_sanomat
							FROM tilausrivi
							JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio
											AND lasku.tunnus = tilausrivi.uusiotunnus
											AND lasku.mapvm = '0000-00-00')
							JOIN toimi ON (toimi.yhtio = tilausrivi.yhtio
											AND toimi.tunnus = lasku.liitostunnus
											AND toimi.tunnus = '{$trow['tunnus']}'
											AND toimi.tyyppi != 'P'
											AND toimi.asn_sanomat IN ('K', 'L'))
							JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio
															AND tuotteen_toimittajat.liitostunnus = toimi.tunnus
															AND tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno
															AND tuotteen_toimittajat.toim_tuoteno = '{$rtuoteno[$i]['tuoteno']}')
							JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tuotteen_toimittajat.tuoteno AND tuote.status != 'P')
							WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
							AND tilausrivi.tyyppi = 'O'
							AND tilausrivi.uusiotunnus != 0
							ORDER BY laadittu ASC";
				$kokeillaan = pupe_query($query);
			}

			if (mysql_num_rows($kokeillaan) == 0) {
				// tiukka 1
				$query = "	SELECT tilausrivi.*, toimi.asn_sanomat
							FROM tilausrivi
							JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio
											AND lasku.tunnus = tilausrivi.otunnus)
							JOIN toimi ON (toimi.yhtio = tilausrivi.yhtio
											AND toimi.tunnus = lasku.liitostunnus
											AND toimi.tunnus = '{$trow['tunnus']}'
											AND toimi.tyyppi != 'P'
											AND toimi.asn_sanomat IN ('K', 'L'))
							JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio
															AND tuotteen_toimittajat.liitostunnus = toimi.tunnus
															AND tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno
															AND tuotteen_toimittajat.toim_tuoteno = '{$rtuoteno[$i]['tuoteno']}')
							JOIN tuote on (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tuotteen_toimittajat.tuoteno AND tuote.status != 'P')
							WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
							AND tilausrivi.tyyppi = 'O'
							AND tilausrivi.otunnus = '{$rtuoteno[$i]['ostotilausnro']}' AND tilausrivi.tilaajanrivinro = '{$rtuoteno[$i]['tilaajanrivinro']}'
							AND tilausrivi.uusiotunnus = 0
							ORDER BY laadittu ASC";
				$kokeillaan = pupe_query($query);
			}

			if (mysql_num_rows($kokeillaan) == 0) {
				// tiukka 2
				$query = "	SELECT tilausrivi.*, toimi.asn_sanomat
							FROM tilausrivi
							JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio
											AND lasku.tunnus = tilausrivi.otunnus)
							JOIN toimi ON (toimi.yhtio = tilausrivi.yhtio
											AND toimi.tunnus = lasku.liitostunnus
											AND toimi.tunnus = '{$trow['tunnus']}'
											AND toimi.tyyppi != 'P'
											AND toimi.asn_sanomat IN ('K', 'L'))
							JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio
															AND tuotteen_toimittajat.liitostunnus = toimi.tunnus
															AND tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno
															AND tuotteen_toimittajat.toim_tuoteno = '{$rtuoteno[$i]['tuoteno']}')
							JOIN tuote on (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tuotteen_toimittajat.tuoteno AND tuote.status != 'P')
							WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
							AND tilausrivi.tyyppi = 'O'
							AND lasku.comments LIKE '%{$rtuoteno[$i]['ostotilausnro']}%' AND tilausrivi.tilaajanrivinro = '{$rtuoteno[$i]['tilaajanrivinro']}'
							AND tilausrivi.uusiotunnus = 0
							ORDER BY laadittu ASC";
				$kokeillaan = pupe_query($query);
			}

			if (mysql_num_rows($kokeillaan) == 0) {
				// v‰ljempi 1
				$query = "	SELECT tilausrivi.*, toimi.asn_sanomat
							FROM tilausrivi
							JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio
											AND lasku.tunnus = tilausrivi.otunnus)
							JOIN toimi ON (toimi.yhtio = tilausrivi.yhtio
											AND toimi.tunnus = lasku.liitostunnus
											AND toimi.tunnus = '{$trow['tunnus']}'
											AND toimi.tyyppi != 'P'
											AND toimi.asn_sanomat IN ('K', 'L'))
							JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio
															AND tuotteen_toimittajat.liitostunnus = toimi.tunnus
															AND tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno
															AND tuotteen_toimittajat.toim_tuoteno = '{$rtuoteno[$i]['tuoteno']}')
							JOIN tuote on (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tuotteen_toimittajat.tuoteno AND tuote.status != 'P')
							WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
							AND tilausrivi.tyyppi = 'O'
							AND tilausrivi.otunnus = '{$rtuoteno[$i]['ostotilausnro']}'
							AND tilausrivi.uusiotunnus = 0
							ORDER BY laadittu ASC";
				$kokeillaan = pupe_query($query);
			}

			if (mysql_num_rows($kokeillaan) == 0) {
				// v‰ljempi 2
				$query = "	SELECT tilausrivi.*, toimi.asn_sanomat
							FROM tilausrivi
							JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio
											AND lasku.tunnus = tilausrivi.otunnus)
							JOIN toimi ON (toimi.yhtio = tilausrivi.yhtio
											AND toimi.tunnus = lasku.liitostunnus
											AND toimi.tunnus = '{$trow['tunnus']}'
											AND toimi.tyyppi != 'P'
											AND toimi.asn_sanomat IN ('K', 'L'))
							JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio
															AND tuotteen_toimittajat.liitostunnus = toimi.tunnus
															AND tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno
															AND tuotteen_toimittajat.toim_tuoteno = '{$rtuoteno[$i]['tuoteno']}')
							JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tuotteen_toimittajat.tuoteno AND tuote.status != 'P')
							WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
							AND tilausrivi.tyyppi = 'O'
							AND lasku.comments LIKE '%{$rtuoteno[$i]['ostotilausnro']}%'
							AND tilausrivi.uusiotunnus = 0
							ORDER BY laadittu ASC";
				$kokeillaan = pupe_query($query);
			}

			if (mysql_num_rows($kokeillaan) == 0) {
				// lˆys‰
				$query = "	SELECT tilausrivi.*, toimi.asn_sanomat
							FROM tilausrivi
							JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio
											AND lasku.tunnus = tilausrivi.otunnus)
							JOIN toimi ON (toimi.yhtio = tilausrivi.yhtio
											AND toimi.tunnus = lasku.liitostunnus
											AND toimi.tunnus = '{$trow['tunnus']}'
											AND toimi.tyyppi != 'P'
											AND toimi.asn_sanomat IN ('K', 'L'))
							JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio
															AND tuotteen_toimittajat.liitostunnus = toimi.tunnus
															AND tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno
															AND tuotteen_toimittajat.toim_tuoteno = '{$rtuoteno[$i]['tuoteno']}')
							JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tuotteen_toimittajat.tuoteno AND tuote.status != 'P')
							WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
							AND tilausrivi.tyyppi = 'O'
							AND tilausrivi.uusiotunnus = 0
							ORDER BY laadittu ASC";
			}

			$checkres = pupe_query($query);

			$kaytettavat_tilausrivit = array();

			// if (mysql_num_rows($checkres) == 1 and $tilausrivirow['uusiotunnus'] == 0 and $tilausrivirow['asn_sanomat'] == 'L') {
			if (mysql_num_rows($checkres) == 1) {

				/*
				// lˆytyi, ei ole keikalla
				$ostoilla[$i]["tunnus"] = $tilausrivirow['tunnus']; // tilausrivi tunnus
				$ostoilla[$i]["hinta"] = $rtuoteno[$i]["hinta"];
				$ostoilla[$i]["kpl"] = $rtuoteno[$i]["kpl"];
				$ostoilla[$i]["laskuntunnus"] = $rtuoteno[$i]["ostotilausnro"]; // laskun tunnus
				$ostoilla[$i]["tilaajanrivinro"] = $rtuoteno[$i]["tilaajanrivinro"];
				$ostoilla[$i]["insert_id"] = $rtuoteno[$i]["insert_id"];

				// lˆytyi, on jo keikalla
				$keikoilla[$i]["tunnus"] = $tilausrivirow['tunnus'];
				$keikoilla[$i]["uusiotunnus"] = $tilausrivirow['uusiotunnus'];
				$keikoilla[$i]["hinta"] = $rtuoteno[$i]["hinta"];
				$keikoilla[$i]["tilaajanrivinro"] = $rtuoteno[$i]["tilaajanrivinro"];
				$keikoilla[$i]["insert_id"] = $rtuoteno[$i]["insert_id"];

				*/

				while ($tilausrivirow = mysql_fetch_assoc($checkres)) {
					// tilausrivi ei saa olla jo kohdistettu
					$query = "	SELECT tunnus 
								FROM asn_sanomat
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND laji = 'tec'
								AND tilausrivi = '{$tilausrivirow['tunnus']}'";
					$chk_res = pupe_query($query);

					if (mysql_num_rows($chk_res) == 0) {
						$kaytettavat_tilausrivit[] = $tilausrivirow['tunnus'];
					}
				}

				$tilausrivi_nro = array_shift($kaytettavat_tilausrivit);

				$query = "	SELECT *
							FROM tilausrivi
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND tunnus = '{$tilausrivi_nro}'";
				$tilausrivires = pupe_query($query);
				$tilausrivirow = mysql_fetch_assoc($tilausrivires);

				// Mik‰li toimittajalla on kentt‰ "asn_sanomat" arvoja, ja ne on
				// K = tulee asn-sanoma ja lasku
				// S = tulee vain sanoma   <--- ei tehd‰ mit‰‰n
				// L = tulee vain lasku

				$asn_sanomat = $tilausrivirow['uusiotunnus'] == 0 ? "L" : "K";

				// jos ei splitata, p‰ivitet‰‰n hinta ja kohdistetaan.
				if ($tilausrivirow['varattu'] == $rtuoteno[$i]["kpl"]) {

					$update_asn = "	UPDATE asn_sanomat SET
									tilausrivi = '{$tilausrivirow['tunnus']}',
									keikkarivinhinta = '{$tilausrivirow['hinta']}'
									WHERE yhtio = '{$kukarow['yhtio']}'
									AND tunnus = '{$rtuoteno[$i]['insert_id']}' ## v‰‰r‰ tilausnumero, nyt menee toimittajan laskun numero eik‰ pupen ostonumero !! KORJAA !!!
									AND laji = 'tec'";
					pupe_query($update_asn);

					if ($tilausrivirow['uusiotunnus'] == 0) {
						// lˆytyi, ei ole keikalla
						$ostoilla[$i]["tunnus"] = $tilausrivirow['tunnus']; // tilausrivi tunnus
						$ostoilla[$i]["hinta"] = $rtuoteno[$i]["hinta"];
						$ostoilla[$i]["kpl"] = $rtuoteno[$i]["kpl"];
						$ostoilla[$i]["laskuntunnus"] = $rtuoteno[$i]["ostotilausnro"]; // laskun tunnus
						$ostoilla[$i]["tilaajanrivinro"] = $rtuoteno[$i]["tilaajanrivinro"];
						$ostoilla[$i]["insert_id"] = $rtuoteno[$i]["insert_id"];
					}
					else {
						// lˆytyi, on jo keikalla
						$keikoilla[$i]["tunnus"] = $tilausrivirow['tunnus'];
						$keikoilla[$i]["uusiotunnus"] = $tilausrivirow['uusiotunnus'];
						$keikoilla[$i]["hinta"] = $rtuoteno[$i]["hinta"];
						$keikoilla[$i]["tilaajanrivinro"] = $rtuoteno[$i]["tilaajanrivinro"];
						$keikoilla[$i]["insert_id"] = $rtuoteno[$i]["insert_id"];
					}

					// $update = "	UPDATE tilausrivi SET
					// 			hinta		= '{$rtuoteno[$i]['hinta']}',
					// 			uusiotunnus = '{$keikan_tunnus}'
					// 			WHERE yhtio = '{$kukarow['yhtio']}'
					// 			AND tunnus = '{$tilausrivirow['tunnus']}'";
					// pupe_query($update);

				}
				elseif ($rtuoteno[$i]["kpl"] < $tilausrivirow["varattu"]) {
					// Splitataan tilausrivi, tallennetaan vanha rivitunnus tilausrivi.tilaajanrivinro kentt‰‰n

					$kappaleerotus = $tilausrivirow["varattu"] - $rtuoteno[$i]["kpl"];

					// // Tehd‰‰n uusi rivi, jossa on j‰ljelle j‰‰neet kappaleet
					$fields = "yhtio";
					$values = "'{$kukarow['yhtio']}'";

					// Ei monisteta tunnusta
					for ($ii = 1; $ii < mysql_num_fields($tilausrivires) - 1; $ii++) {

						$fieldname = mysql_field_name($tilausrivires,$ii);

						$fields .= ", ".$fieldname;

						switch ($fieldname) {
							case 'varattu':
								$values .= ", '{$kappaleerotus}'";
								break;
							case 'kpl':
								if ($tilausrivirow['kpl'] == 0) {
									$values .= ", '".$tilausrivirow[$fieldname]."'";
								}
								else {
									$values .= ", '{$kappaleerotus}'";
								}
								break;
							default:
								$values .= ", '".$tilausrivirow[$fieldname]."'";
						}
					}

					$kysely  = "INSERT INTO tilausrivi ({$fields}) VALUES ({$values})";
					$uusires = pupe_query($kysely);
					$tilausrivi_id = mysql_insert_id();

					$splitlisa = $tilausrivirow['kpl'] > 0 ? ", kpl = '{$rtuoteno[$i]['kpl']}'" : "";

					// P‰ivitet‰‰n alkuper‰iselle riville saapunut kappalem‰‰r‰
					$query = "	UPDATE tilausrivi SET
								varattu = '{$rtuoteno[$i]['kpl']}'
								{$splitlisa}
								WHERE yhtio = '$kukarow[yhtio]'
								AND tunnus = '{$tilausrivirow['tunnus']}'";
					$upres = pupe_query($query);

					$update_asn = "	UPDATE asn_sanomat SET
									tilausrivi = '{$tilausrivirow['tunnus']}',
									keikkarivinhinta = '{$tilausrivirow['hinta']}'
									WHERE yhtio = '{$kukarow['yhtio']}'
									AND tunnus = '{$rtuoteno[$i]['insert_id']}' ## v‰‰r‰ tilausnumero, nyt menee toimittajan laskun numero eik‰ pupen ostonumero !! KORJAA !!!
									AND laji = 'tec'";
					$foo = pupe_query($update_asn);

					if ($tilausrivirow['uusiotunnus'] == 0) {
						// lˆytyi, ei ole keikalla
						$ostoilla[$i]["tunnus"] = $tilausrivirow['tunnus']; // tilausrivi tunnus
						$ostoilla[$i]["hinta"] = $rtuoteno[$i]["hinta"];
						$ostoilla[$i]["kpl"] = $rtuoteno[$i]["kpl"];
						$ostoilla[$i]["laskuntunnus"] = $rtuoteno[$i]["ostotilausnro"]; // laskun tunnus
						$ostoilla[$i]["tilaajanrivinro"] = $rtuoteno[$i]["tilaajanrivinro"];
						$ostoilla[$i]["insert_id"] = $rtuoteno[$i]["insert_id"];
					}
					else {
						// lˆytyi, on jo keikalla
						$keikoilla[$i]["tunnus"] = $tilausrivirow['tunnus'];
						$keikoilla[$i]["uusiotunnus"] = $tilausrivirow['uusiotunnus'];
						$keikoilla[$i]["hinta"] = $rtuoteno[$i]["hinta"];
						$keikoilla[$i]["tilaajanrivinro"] = $rtuoteno[$i]["tilaajanrivinro"];
						$keikoilla[$i]["insert_id"] = $rtuoteno[$i]["insert_id"];
					}

					// jos rivi on jo viety varastoon ja yll‰ splitattiin se rivi, korjataan tapahtumat
					if ($tilausrivirow['kpl'] > 0) {
						$query = "SELECT * FROM tapahtuma WHERE yhtio = '{$kukarow['yhtio']}' AND rivitunnus = '{$tilausrivirow['tunnus']}'";
						$tapahtuma_res = pupe_query($query);
						$tapahtuma_row = mysql_fetch_assoc($tapahtuma_res);

						// Tehd‰‰n uusi rivi, jossa on j‰ljelle j‰‰neet kappaleet
						$fields = "yhtio";
						$values = "'{$kukarow['yhtio']}'";

						// Ei monisteta tunnusta
						for ($ii = 1; $ii < mysql_num_fields($tapahtuma_res) - 1; $ii++) {

							$fieldname = mysql_field_name($tapahtuma_res,$ii);

							$fields .= ", ".$fieldname;

							switch ($fieldname) {
								case 'rivitunnus':
									$values .= ", '{$tilausrivi_id}'";
									break;
								case 'kpl':
									$values .= ", '{$kappaleerotus}'";
									break;
								default:
									$values .= ", '".$tapahtuma_res[$fieldname]."'";
							}
						}

						$kysely  = "INSERT INTO tapahtuma ({$fields}) VALUES ({$values})";
						$uusires = pupe_query($kysely);
						$tapahtuma_id = mysql_insert_id();

						$query = "UPDATE tapahtuma SET kpl = '{$rtuoteno[$i]['kpl']}' WHERE yhtio = '{$kukarow['yhtio']}' AND rivitunnus = '{$tilausrivirow['tunnus']}'";
						$upd_res = pupe_query($query);
					}
				}
				else {
					// tuli enemm‰n kuin tilattu
					$laskuri = 0;
					$kohdistamatta = $tilausrivirow["varattu"];
					$lisa_array = array();
					$split_array = array();

					// Lasketaan ensin kaikki rivit yhteen ja sen j‰lkeen katsotaan tarvitaanko lis‰rivi‰
					foreach ($kaytettavat_tilausrivit as $tilriv) {

						if ($kohdistamatta <= 0) break;

						$query = "SELECT varattu FROM tilausrivi WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$tilriv}'";
						$kpl_chk_res = pupe_query($query);
						$kpl_chk_row = mysql_fetch_assoc($kpl_chk_res);

						$laskuri += $kpl_chk_row["varattu"];
						$lisa_array[] = $tilriv;
						$split_array[] = $tilriv;
						$kohdistamatta -= $kpl_chk_row["varattu"];
					}

					$ylimaaraista = $rtuoteno[$i]["kpl"] - $laskuri; // liikaa miinus tilauksella

					if ($ylimaaraista > 0) {

						$query = "	SELECT tilausrivi.*, toimi.asn_sanomat,
									IF(tilausrivi.laskutettuaika = '0000-00-00', '9999-12-31', tilausrivi.laskutettuaika) AS laskutettuaika,
									IF(tilausrivi.uusiotunnus = 0, ".PHP_INT_MAX.", tilausrivi.uusiotunnus) AS uusiotunnus
									FROM tilausrivi
									JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio
													AND (lasku.uusiotunnus = tilausrivi.otunnus OR lasku.tunnus = tilausrivi.otunnus)
													AND lasku.mapvm = '0000-00-00')
									JOIN toimi ON (toimi.yhtio = tilausrivi.yhtio
													AND toimi.tunnus = lasku.liitostunnus
													AND toimi.tunnus = '{$trow['tunnus']}'
													AND toimi.tyyppi != 'P'
													AND toimi.asn_sanomat IN ('K', 'L'))
									JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio
																	AND tuotteen_toimittajat.liitostunnus = toimi.tunnus
																	AND tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno
																	AND tuotteen_toimittajat.toim_tuoteno = '{$rtuoteno[$i]['tuoteno']}')
									JOIN tuote on (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tuotteen_toimittajat.tuoteno AND tuote.status != 'P')
									WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
									AND tilausrivi.tyyppi = 'O'
									ORDER BY uusiotunnus ASC, laskutettuaika ASC, laadittu ASC";
						$kokeillaan = pupe_query($query);

						while ($kokeiltiin = mysql_fetch_assoc($kokeillaan)) {

							// tilausrivi ei saa olla jo kohdistettu
							$query = "	SELECT tunnus 
										FROM asn_sanomat
										WHERE yhtio = '{$kukarow['yhtio']}'
										AND laji = 'tec'
										AND tilausrivi = '{$tilausrivirow['tunnus']}'";
							$chk_res = pupe_query($query);

							if (mysql_num_rows($chk_res) != 0 or in_array($kokeiltiin['tunnus'], $kaytettavat_tilausrivit)) continue;

							if ($kohdistamatta <= 0) break;

							$query = "SELECT varattu FROM tilausrivi WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$kokeiltiin['tunnus']}'";
							$kpl_chk_res = pupe_query($query);
							$kpl_chk_row = mysql_fetch_assoc($kpl_chk_res);

							$laskuri += $kpl_chk_row["varattu"];
							$lisa_array[] = $kokeiltiin['tunnus'];
							$split_array[] = $kokeiltiin['tunnus'];
							$kohdistamatta -= $kpl_chk_row["varattu"];
						}

						if (mysql_num_rows($kokeillaan) > 0) $ylimaaraista = $rtuoteno[$i]["kpl"] - $laskuri; // liikaa miinus tilauksella
					}

					if ($ylimaaraista > 0) {

						// Tehd‰‰n uusi rivi, jossa on j‰ljelle j‰‰neet kappaleet
						$fields = "yhtio";
						$values = "'{$kukarow['yhtio']}'";

						// Ei monisteta tunnusta
						for ($ii = 1; $ii < mysql_num_fields($tilausrivires) - 1; $ii++) {

							$fieldname = mysql_field_name($tilausrivires,$ii);

							$fields .= ", ".$fieldname;

							switch ($fieldname) {
								case 'tilkpl':
								case 'varattu':
									$values .= ", '{$ylimaaraista}'";
									break;
								case 'laatija':
									$values .= ", 'extraa'";
									break;
								case 'kommentti':
									$values .= ", 'TECCOM-laskulla 1: {$laskun_numero} tuli {$rtuoteno[$i]['kpl']} ja tilaukselta lˆytyi {$laskuri} Tehtiin tilausrivi {$ylimaaraista} m‰‰r‰lle'";
									break;
								case 'laadittu':
									$values .= ", now()";
									break;
								case 'kpl':
									$values .= ", 0";
									break;
								case 'laskutettuaika':
									$values .= ", '0000-00-00'";
									break;
								default:
									$values .= ", '".$tilausrivirow[$fieldname]."'";
							}
						}

						$kysely  = "INSERT INTO tilausrivi ({$fields}) VALUES ({$values})";
						$uusires = pupe_query($kysely);
						$extraa = mysql_insert_id();
						$lisa_array[]			= $extraa;

						$kaytetyt_tilausrivit	= array_unique(array_merge($kaytetyt_tilausrivit, $lisa_array));
						$siistitty 				= implode(",", $lisa_array);

						// p‰ivitet‰‰n tilausrivin tunnus talteen asn_sanomat-tauluun, koska t‰m‰ kohdistuu kyseiseen riviin
						$updatequery = "UPDATE asn_sanomat SET
										tilausrivi = '{$siistitty}',
										tuoteno = '{$tilausrivirow['tuoteno']}'
										WHERE yhtio = '{$kukarow['yhtio']}'
										AND tunnus = '{$rtuoteno[$i]['insert_id']}'";
						$upd_res = pupe_query($updatequery);
					}
					elseif ($ylimaaraista == 0) {
						// Jos menee tasan tilatut ja tulleet.
						$kaytetyt_tilausrivit	= array_unique(array_merge($kaytetyt_tilausrivit, $lisa_array));
						$siistitty 				= implode(",", $lisa_array);

						// p‰ivitet‰‰n tilausrivin tunnus talteen asn_sanomat-tauluun, koska t‰m‰ kohdistuu kyseiseen riviin
						$updatequery = "UPDATE asn_sanomat SET
										tilausrivi = '{$siistitty}',
										tuoteno = '{$tilausrivirow['tuoteno']}'
										WHERE yhtio = '{$kukarow['yhtio']}'
										AND tunnus = '{$rtuoteno[$i]['insert_id']}'";
						$upd_res = pupe_query($updatequery);
					}
					elseif ($ylimaaraista < 0) {

						$positiivinen_arvo = abs($ylimaaraista);
						$vika_rivi = array_pop($split_array);
						// vika_rivi on se tilausrivi joka splitataan ett‰ m‰‰r‰ t‰sm‰‰

						$split_query	= "SELECT * FROM tilausrivi WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$vika_rivi}'";
						$split_result	= pupe_query($split_query);
						$split_row		= mysql_fetch_assoc($split_result);
						$erotus			= $split_row["tilkpl"] - $positiivinen_arvo;

						$kaytetyt_tilausrivit[] = $vika_rivi;

						// P‰ivitet‰‰n alkuper‰iselle riville "ylim‰‰r‰inen" ja uudelle splitille "erotus"
						$fields = "yhtio";
						$values = "'{$kukarow['yhtio']}'";

						// Ei monisteta tunnusta
						for ($ii = 1; $ii < mysql_num_fields($split_result) - 1; $ii++) {

							$fieldname = mysql_field_name($split_result,$ii);

							$fields .= ", ".$fieldname;

							switch ($fieldname) {
								case 'varattu':
								case 'tilkpl':
									$values .= ", '{$positiivinen_arvo}'";
									break;
								case 'kpl':
									if ($split_row['kpl'] == 0) {
										$values .= ", '0'";
									}
									else {
										$values .= ", '{$positiivinen_arvo}'";
									}
									break;
								case 'kommentti':
									$values .= ", 'TECCOM-laskulla 2: {$laskun_numero}. Tehtiin puuttuva tilausrivi m‰‰r‰lle {$positiivinen_arvo} tuotteelle {$split_row['tuoteno']}'";
									break;
								case 'laatija':
									$values .= ", 'split'";
									break;
								case 'laadittu':
									$values .= ", now()";
								default:
									$values .= ", '".$split_row[$fieldname]."'";
							}
						}

						$kysely  = "INSERT INTO tilausrivi ({$fields}) VALUES ({$values})";
						$uusires = pupe_query($kysely);
						$tilausrivi_id = mysql_insert_id();

						$splitlisa = $split_row['kpl'] != 0 ? " kpl = '{$erotus}', " : "";

						// P‰ivitet‰‰n alkuper‰iselle riville saapunut kappalem‰‰r‰
						$query = "	UPDATE tilausrivi SET
									varattu = '{$erotus}',
									{$splitlisa}
									kommentti = concat(kommentti,' Ennen splittausta {$split_row['tilkpl']} : kohdistettiin {$erotus}')
									WHERE yhtio = '$kukarow[yhtio]'
									AND tunnus = '{$split_row['tunnus']}'";
						$upres = pupe_query($query);

						$kaytetyt_tilausrivit	= array_unique(array_merge($kaytetyt_tilausrivit, $lisa_array));
						$siistitty 				= implode(",", $lisa_array);

						// p‰ivitet‰‰n tilausrivin tunnus talteen asn_sanomat-tauluun, koska t‰m‰ kohdistuu kyseiseen riviin
						$updatequery = "UPDATE asn_sanomat SET
										tilausrivi = '{$siistitty}',
										tuoteno = '{$split_row['tuoteno']}'
										WHERE yhtio = '{$kukarow['yhtio']}'
										AND tunnus = '{$rtuoteno[$i]['insert_id']}'";
						$upd_res = pupe_query($updatequery);

						// jos rivi on jo viety varastoon ja yll‰ splitattiin se rivi, korjataan tapahtumat
						if ($split_row['kpl'] > 0) {
							$query = "SELECT * FROM tapahtuma WHERE yhtio = '{$kukarow['yhtio']}' AND rivitunnus = '{$split_row['tunnus']}'";
							$tapahtuma_res = pupe_query($query);
							$tapahtuma_row = mysql_fetch_assoc($tapahtuma_res);

							// Tehd‰‰n uusi rivi, jossa on j‰ljelle j‰‰neet kappaleet
							$fields = "yhtio";
							$values = "'{$kukarow['yhtio']}'";

							// Ei monisteta tunnusta
							for ($ii = 1; $ii < mysql_num_fields($tapahtuma_res) - 1; $ii++) {

								$fieldname = mysql_field_name($tapahtuma_res,$ii);

								$fields .= ", ".$fieldname;

								switch ($fieldname) {
									case 'rivitunnus':
										$values .= ", '{$tilausrivi_id}'";
										break;
									case 'kpl':
										$values .= ", '{$positiivinen_arvo}'";
										break;
									default:
										$values .= ", '".$tapahtuma_res[$fieldname]."'";
								}
							}

							$kysely  = "INSERT INTO tapahtuma ({$fields}) VALUES ({$values})";
							$uusires = pupe_query($kysely);
							$tapahtuma_id = mysql_insert_id();

							$query = "UPDATE tapahtuma SET kpl = '{$erotus}' WHERE yhtio = '{$kukarow['yhtio']}' AND rivitunnus = '{$split_row['tunnus']}'";
							$upd_res = pupe_query($query);
						}
					}

					foreach($lisa_array as $tilriv) {

						$query = "SELECT uusiotunnus FROM tilausrivi WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$tilriv}'";
						$uusiotunnus_res = pupe_query($query);
						$uusiotunnus_row = mysql_fetch_assoc($uusiotunnus_res);

						if ($uusiotunnus_row['uusiotunnus'] == 0) {

							$indx = count($ostoilla);

							// lˆytyi, ei ole keikalla
							$ostoilla[$indx]["tunnus"] = $tilriv; // tilausrivi tunnus
							$ostoilla[$indx]["hinta"] = $rtuoteno[$i]["hinta"];
							$ostoilla[$indx]["kpl"] = $rtuoteno[$i]["kpl"];
							$ostoilla[$indx]["laskuntunnus"] = $rtuoteno[$i]["ostotilausnro"]; // laskun tunnus
							$ostoilla[$indx]["tilaajanrivinro"] = $rtuoteno[$i]["tilaajanrivinro"];
							$ostoilla[$indx]["insert_id"] = $rtuoteno[$i]["insert_id"];
						}
						else {
							$indx = count($keikoilla);

							// lˆytyi, on jo keikalla
							$keikoilla[$indx]["tunnus"] = $tilriv;
							$keikoilla[$indx]["uusiotunnus"] = $uusiotunnus_row['uusiotunnus'];
							$keikoilla[$indx]["hinta"] = $rtuoteno[$i]["hinta"];
							$keikoilla[$indx]["tilaajanrivinro"] = $rtuoteno[$i]["tilaajanrivinro"];
							$keikoilla[$indx]["insert_id"] = $rtuoteno[$i]["insert_id"];
						}
					}
				}
			}
			else {
				// ei lˆydy tai joku muu virhe
				echo "Virheellisi‰ kappalem‰‰ri‰ tilauksella ".$rtuoteno[$i]["ostotilausnro"]." tuote ".$rtuoteno[$i]["tuoteno"]." kpl ".$rtuoteno[$i]["kpl"]."\n";
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
				// saldoton tai tuntematon tuote, vied‰‰n varastoon ja kauttalaskutetaan kuitenkin
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

	// Jos kyseess‰ on TecCom oastolasku
	if ($osrow["ebid"] == "TECCOM-INVOICE") {
		$update_asn_chk = true;

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

			// meill‰ on $trow tullut parametrin‰
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
						comments				= '".t("Keikka luotu laskusta")." $osrow[laskunro]',
						tila         			= 'K',
						luontiaika	 			= now(),
						laatija		 			= '$kukarow[kuka]'";
			$insertres = pupe_query($query);
			$keikan_tunnus = mysql_insert_id();

			$query  = "UNLOCK TABLE";
			$result = pupe_query($query);

			verkkolasku_luo_liitosotsikko($tunnus, $keikan_tunnus);

			// lis‰t‰‰n tuotteet keikalle ja kohdistetaan
			foreach ($ostoilla as $pointteri) {

				$query = "SELECT hinta, otunnus FROM tilausrivi WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$pointteri['tunnus']}'";
				$hinta_chk_res = pupe_query($query);
				$hinta_chk_row = mysql_fetch_assoc($hinta_chk_res);

				$update_asn = "	UPDATE asn_sanomat SET
								#tilausrivi = '{$pointteri['tunnus']}',
								keikkarivinhinta = '{$hinta_chk_row['hinta']}'
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tunnus = '{$pointteri['insert_id']}'
								AND laji = 'tec'";
				pupe_query($update_asn);

				$update = "UPDATE tilausrivi set hinta = '{$pointteri['hinta']}', uusiotunnus = '{$keikan_tunnus}' where yhtio = '{$kukarow['yhtio']}' and tunnus = '{$pointteri['tunnus']}'";
				pupe_query($update);
			}

			$query = "SELECT * FROM lasku WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$keikan_tunnus}'";
			$laskures = pupe_query($query);
			$laskurow = mysql_fetch_assoc($laskures);

			$tee_kululaskut = "liita";
 			$silent = "SILENT";
 			$keikanalatila = "";
 			$laskutunnus = $tunnus;

			require("tilauskasittely/kululaskut.inc");
		}
		elseif (count($keikoilla) > 0 and $virheet == 0) {
		#elseif (count($ostoilla) == 0 and count($keikoilla) > 0 and $virheet == 0) {
			// jos tullaan t‰h‰n kohtaan niin kappalem‰‰r‰t t‰sm‰‰. On jo tarkistettu rivill‰ 166
			// muutetaan hinta ja rivi on ok.
			verkkolasku_luo_liitosotsikko($tunnus, $keikoilla[0]['uusiotunnus']);

			foreach ($keikoilla as $pointteri) {

				// $query = "	SELECT tunnus 
				// 			FROM asn_sanomat
				// 			WHERE yhtio = '{$kukarow['yhtio']}'
				// 			#AND tilausnumero = '{$hinta_chk_row['otunnus']}'
				// 			#AND tilausrivinpositio = '{$pointteri['tilaajanrivinro']}'
				// 			AND laji = 'asn'
				// 			AND tilausrivi = '{$pointteri['tunnus']}'";
				// $chk_res = pupe_query($query);

				// if (mysql_num_rows($chk_res) == 0) {
				// 	$virheet++;
				// 	#continue;
				// }

				$query = "SELECT hinta, otunnus FROM tilausrivi WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$pointteri['tunnus']}'";
				$hinta_chk_res = pupe_query($query);
				$hinta_chk_row = mysql_fetch_assoc($hinta_chk_res);

				$update_asn = "	UPDATE asn_sanomat SET
								#tilausrivi = '{$pointteri['tunnus']}',
								keikkarivinhinta = '{$hinta_chk_row['hinta']}'
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tunnus = '{$pointteri['insert_id']}'
								AND laji = 'tec'";
				pupe_query($update_asn);

				$update = "UPDATE tilausrivi set hinta = '{$pointteri['hinta']}', uusiotunnus = '{$keikoilla[0]['uusiotunnus']}' where yhtio = '{$kukarow['yhtio']}' and tunnus = '{$pointteri['tunnus']}'";
				pupe_query($update);
			}

			foreach ($ostoilla as $pointteri) {

				$query = "SELECT hinta, otunnus FROM tilausrivi WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$pointteri['tunnus']}'";
				$hinta_chk_res = pupe_query($query);
				$hinta_chk_row = mysql_fetch_assoc($hinta_chk_res);

				$update_asn = "	UPDATE asn_sanomat SET
								#tilausrivi = '{$pointteri['tunnus']}',
								keikkarivinhinta = '{$hinta_chk_row['hinta']}'
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tunnus = '{$pointteri['insert_id']}'
								AND laji = 'tec'";
				pupe_query($update_asn);

				$update = "UPDATE tilausrivi set hinta = '{$pointteri['hinta']}', uusiotunnus = '{$keikoilla[0]['uusiotunnus']}' where yhtio = '{$kukarow['yhtio']}' and tunnus = '{$pointteri['tunnus']}'";
				pupe_query($update);
			}

			$query = "SELECT * FROM lasku WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$keikoilla[0]['uusiotunnus']}'";
			$laskures = pupe_query($query);
			$laskurow = mysql_fetch_assoc($laskures);

			$tee_kululaskut = "liita";
 			$silent = "SILENT";
 			$keikanalatila = "";
 			$laskutunnus = $tunnus;

			require("tilauskasittely/kululaskut.inc");

		}
		else {
			echo "Laskulla oli virheit‰, emme tehneet mit‰‰n.\n";
			$update_asn_chk = false;
		}

		if ($update_asn_chk) {

			if (count($ostoilla) > 0 and count($keikoilla) == 0 and $virheet == 0) {

				$upd_chk = true;

				foreach ($ostoilla as $osto_loop) {
					$query = "	SELECT tilausrivi
								FROM asn_sanomat
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tilausnumero = '{$laskun_numero}'
								AND tilausrivinpositio = '{$osto_loop['tilaajanrivinro']}'";
					$chk_res = pupe_query($query);
					$chk_row = mysql_fetch_assoc($chk_res);

					if ($chk_row['tilausrivi'] == '') $upd_chk = false;
				}

				if ($upd_chk) {
					$query = "	UPDATE asn_sanomat SET
								status = 'X'
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tilausnumero = '{$laskun_numero}'";
					$chk_res = pupe_query($query);
				}
			}
			elseif (count($ostoilla) == 0 and count($keikoilla) > 0 and $virheet == 0) {

				$upd_chk = true;

				foreach ($keikoilla as $keikka_loop) {
					$query = "	SELECT tilausrivi
								FROM asn_sanomat
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tilausnumero = '{$laskun_numero}'
								AND tilausrivinpositio = '{$keikka_loop['tilaajanrivinro']}'";
					$chk_res = pupe_query($query);
					$chk_row = mysql_fetch_assoc($chk_res);

					if ($chk_row['tilausrivi'] == '') $upd_chk = false;
				}

				if ($upd_chk) {
					$query = "	UPDATE asn_sanomat SET
								status = 'X'
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tilausnumero = '{$laskun_numero}'";
					$chk_res = pupe_query($query);					
				}
			}
		}
	}

	// t‰m‰n keikan voi vied‰ saldoille ja laskuttaa koska se on suoratoimitus
	if ($kohdistettujariveja > 0 and ($suoratoimitus == "kylla" or $kauttalaskutus == "kylla")) {
		$otunnus = $keikantunnus;

		$query = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$otunnus'";
		$result = pupe_query($query);
		$laskurow = mysql_fetch_array($result);

		if ($kauttalaskutus == "kylla") {

			// jos kysess‰ on kotimainen vaihto-omaisuuslasku, pit‰‰ lis‰t‰ tuotteen hintaan alvi
			if ($laskurow['vienti'] == 'C' or $laskurow['vienti'] == 'J') {

				$alvit = "tuote.alv";

				if ($laskurow["maa"] != "" and $laskurow["maa"] != $yhtiorow["maa"]) {
					// tutkitaan ollaanko siell‰ alv-rekisterˆity
					$alhqur = "SELECT * from yhtion_toimipaikat where yhtio='$kukarow[yhtio]' and maa='$laskurow[maa]' and vat_numero != ''";
					$alhire = pupe_query($alhqur);

					// ollaan alv-rekisterˆity
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

			// p‰ivitet‰‰n samantien t‰m‰ tieto laskulle
			$query   = "UPDATE lasku set summa='$hintarow[hinta]', rahti_etu='$rahti_etu', kohdistettu='K' where yhtio ='$kukarow[yhtio]' and tunnus='$otunnus'";
			$hinresu = pupe_query($query);
		}

		$laskuvirhe .= t("Vied‰‰n suoratoimitus varastoon").". ($laskurow[laskunro])\n";

		$toiminto = "kalkyyli";
		$tee = "varastoon";

		// vied‰‰n varastoon...
		require ("tilauskasittely/varastoon.inc");

		// laskutetaan suoratoimitetut jtrivit jos niit‰ oli t‰ll‰ keikalla...
		if ($suoratoimitus == "kylla") {
			require ("tilauskasittely/jt_super_laskuta.inc");
		}
		elseif ($kauttalaskutus == "kylla") {
			$toiminto = "kaikkiok";
			$tee = "varma";

			// vied‰‰n varastoon TEHDƒƒN LOPPUKALKYYLI
			require ("tilauskasittely/varastoon.inc");

			require ("tilauskasittely/laskuta_kauttalaskutus.inc");
		}
	}
	elseif ($kohdistettujariveja == 0 and $aladellaa == "") {
		// dellataan perustettu keikka ku ei kerran lˆytyny rivej‰.. jos ei olla asetettu ƒLƒDELLAA muuttujaaa
		$query = "DELETE from lasku WHERE yhtio='$kukarow[yhtio]' and tila='K' and laskunro='$keikkanumeroapu'";
		$result = pupe_query($query);

		$laskuvirhe .= t("Poistettiin")." ".t("keikka")." $keikkanumeroapu ".t("Yht‰‰n rivi‰ ei lˆytynyt")."!\n";
	}
	else {
		$laskuvirhe .= t("Keikka k‰sitelty loppuun").": $keikkanumeroapu $trow[nimi]\n";
	}
?>