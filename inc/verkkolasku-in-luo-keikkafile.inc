<?php

	// Tarvitaan $tunnus jossa on ostoreskontralaskun tunnus
	// Tarvitaan $trow jossa on toimittajan tiedot
	// Tarvitaan $rtuoteno jossa on array kaikista ostoreskontralaskun tuotteista

	// Haetaan ostoreskontralaskun tiedot
	$query = "	SELECT *
				FROM lasku
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tunnus = '{$tunnus}'";
	$osres = pupe_query($query);
	$osrow = mysql_fetch_array($osres);

	$laskun_numero = $osrow["laskunro"]; // ‰l‰ mene muuttaamaan tai poistamaan t‰t‰ muuttujaa.!!! Tai no joo... Ongelma..

	if ($osrow["ebid"] != "TECCOM-INVOICE") {
		$query  = "LOCK TABLE lasku WRITE, sanakirja WRITE, tiliointi READ";
		$result = pupe_query($query);

		// Haetaan seuraava vapaa keikkaid
		$query  = "	SELECT MAX(laskunro) + 1
					FROM lasku
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tila = 'K'";
		$result = pupe_query($query);
		$row    = mysql_fetch_array($result);

		$id	= $row[0]; 			// t‰m‰ on meid‰n keikannumero
		$keikkanumeroapu = $id; // talteen

		// Haetaan laskun kaikki verotiliˆinnit jotta voidaan tallentaa myˆs veroton summa
		$query = "	SELECT SUM(summa) summa
					FROM tiliointi
					WHERE yhtio	= '{$kukarow['yhtio']}'
					AND ltunnus	= '{$osrow['tunnus']}'
					AND tilino  = '{$yhtiorow['alv']}'
					AND korjattu = ''";
		$alvires = pupe_query($query);
		$alvirow = mysql_fetch_assoc($alvires);

		// Ostoreskontralaskun veroton arvo
		$osrow["arvo"] = $osrow["summa"] - $alvirow["summa"];

		// Tehd‰‰n itse keikkaotsikko
		$query = "	INSERT INTO lasku SET
					yhtio        = '{$kukarow['yhtio']}',
					laskunro     = '{$id}',
					ytunnus	     = '{$trow['ytunnus']}',
					nimi         = '{$trow['nimi']}',
					valkoodi     = '{$osrow['valkoodi']}',
					vienti       = '{$osrow['vienti']}',
					maksu_kurssi = '{$osrow['maksu_kurssi']}',
					vienti_kurssi= '{$osrow['vienti_kurssi']}',
					toimitusehto = '{$trow['toimitusehto']}',
					osoite       = '{$trow['osoite']}',
					postitp      = '{$trow['postitp']}',
					maa			 = '{$trow['maa']}',
					maa_lahetys  = '{$trow['maa']}',
					kauppatapahtuman_luonne = '{$trow['kauppatapahtuman_luonne']}',
					kuljetusmuoto= '{$trow['kuljetusmuoto']}',
					swift        = '{$trow['swift']}',
					liitostunnus = '{$trow['tunnus']}',
					tila         = 'K',
					luontiaika	 = now(),
					laatija		 = 'verkkolas'";
		$result = pupe_query($query);

		$keikantunnus = mysql_insert_id(); // t‰t‰ tarvitaan tilausrivien uusiotunnukseen
		$laskuvirhe .= "\n".t("Perustettiin saapuminen").": $id $trow[nimi]\n";

		$query  = "UNLOCK TABLE";
		$result = pupe_query($query);

		// Liitet‰‰n vastaanotettu verkkolasku uuteen keikkaan
		// Tehd‰‰n liitosotsikko ostoreskontralaskulle
		verkkolasku_luo_liitosotsikko($tunnus, $keikantunnus);

		$laskuvirhe .= t("Liitettiin lasku").": $trow[nimi] $osrow[summa] $osrow[valkoodi]\n";
	}

	$kohdistettujariveja = 0;
	$kauttalaskutus		 = "";
	$kauttalaskutus_saldoton = array();
	$keikoilla	= (isset($tullaan_virhetarkistuksesta) and isset($keikoilla)) ? $keikoilla : array();
	$ostoilla	= (isset($tullaan_virhetarkistuksesta) and isset($ostoilla)) ? $ostoilla : array();
	$tullaan_virhetarkistuksesta = isset($tullaan_virhetarkistuksesta) ? $tullaan_virhetarkistuksesta : false;
	$virhe		= "";
	$virheet 	= 0;

	for ($i = 0; $i < count($rtuoteno); $i++) {

		// tehd‰‰n t‰‰ vaan jos meill‰ on rivinumero
		if ($rtuoteno[$i]["kauttalaskutus"] != "KAUTTALASKUTUS" and (int) $rtuoteno[$i]["tilaajanrivinro"] != 0 and $osrow['ebid'] != "TECCOM-INVOICE") {

			$toim_tuoteno 	= $rtuoteno[$i]["tuoteno"];
			$kpl			= (float) $rtuoteno[$i]["kpl"];
			$summa			= (float) $rtuoteno[$i]["rivihinta"];

			if ($kpl != 0) {
				$hinta = round($summa / $kpl, 2);
			}
			else {
				// jos kappaleet on nolla, oletetaan 1
				$hinta = $summa;
			}

			// etsit‰‰n hyv‰‰ osumaa!
			$query = "	SELECT tilausrivi.*, lasku.ketjutus
						FROM tilausrivi
						JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio AND lasku.tunnus = tilausrivi.otunnus)
						WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
						AND tilausrivi.tunnus = '{$rtuoteno[$i]['tilaajanrivinro']}'
						AND tilausrivi.tyyppi = 'O'";
			$result = pupe_query($query);

			if (mysql_num_rows($result) == 1) {

				$rivirow = mysql_fetch_array($result);
				$kohdistettujariveja++;

				// jos rivi lˆytyi ja sit‰ ei ole kohdistettu viel‰, kohdistetaan keikkaan ja p‰ivitet‰‰n kpl
				if ($rivirow["uusiotunnus"] == 0) {
					$query = "	UPDATE tilausrivi SET
								uusiotunnus = '{$keikantunnus}',
								varattu = '{$kpl}',
								hinta = '{$hinta}'
								WHERE tunnus = '{$rivirow['tunnus']}'
								AND yhtio = '{$kukarow['yhtio']}'";
					$result = pupe_query($query);

					$laskuvirhe .= t("Tuotenumero").": {$toim_tuoteno} ".t("Kpl").": {$kpl} ".t("kohdistettu saapumiseen")."! ({$keikantunnus}/{$rtuoteno[$i]['tilaajanrivinro']})\n";
				}
				// jos rivi lˆytyi, mutta se on jo kohdistettu.. lis‰t‰‰n uus samoilla tiedoilla
				else {
					$query = "	INSERT INTO tilausrivi
								(uusiotunnus, hinta, tuoteno, tilkpl, varattu, otunnus, yhtio, tyyppi, hyllyalue, hyllynro, hyllyvali, hyllytaso, tilaajanrivinro) VALUES
								('{$keikantunnus}', '{$hinta}', '{$rivirow['tuoteno']}', '{$rivirow['tilkpl']}', '{$kpl}', '{$rivirow['otunnus']}', '{$kukarow['yhtio']}', 'O', '{$rivirow['hyllyalue']}', '{$rivirow['hyllynro']}', '{$rivirow['hyllyvali']}', '{$rivirow['hyllytaso']}','{$rivirow['tilaajanrivinro']}')";
					$result = pupe_query($query);
					$laskuvirhe .= t("Tuotenumero").": {$toim_tuoteno} ".t("Kpl").": {$kpl} ".t("lis‰tty saapumiseen")."! ({$keikantunnus}/{$rtuoteno[$i]['tilaajanrivinro']})\n";
				}
			}
			else {
				$laskuvirhe .= t("Tuotenumero").": {$toim_tuoteno} ".t("Kpl").": {$kpl} ".t("Ei voitu kohdistaa saapumiseen")."! ({$keikantunnus}/{$rtuoteno[$i]['tilaajanrivinro']})\n";
			}
		}
		elseif ($osrow["ebid"] == "TECCOM-INVOICE" and $rtuoteno[$i]["kauttalaskutus"] != "KAUTTALASKUTUS" and !$tullaan_virhetarkistuksesta) {
			// Onko ostolaskua ja onko kohdistamatta

			// Mik‰li toimittajalla on kentt‰ "asn_sanomat" arvoja, ja ne on
			// K = tulee asn-sanoma ja lasku
			// S = tulee vain sanoma   <--- ei tehd‰ mit‰‰n
			// L = tulee vain lasku

			// ei k‰sitell‰ erolistalle siirrettyj‰ rivej‰.
			if ($rtuoteno[$i]['status'] == 'E') continue;

			$toim_tuotenolisa = "'{$rtuoteno[$i]['tuoteno']}','{$rtuoteno[$i]['tuoteno2']}'";

			if (isset($rtuoteno[$i]['tuoteno3']) and trim($rtuoteno[$i]['tuoteno3']) != "") $toim_tuotenolisa .= ",'{$rtuoteno[$i]['tuoteno3']}'";

			$kaytettavat_tilausrivit = array();

			$isalisa = (isset($rtuoteno[$i]["isa_olemassa"]) and trim($rtuoteno[$i]["isa_olemassa"]) != "") ? "and tilausrivi.perheid = '".(int) $rtuoteno[$i]["isa_olemassa"]."' and tilausrivi.perheid != tilausrivi.tunnus" : "";

			// tiukka 1
			$query = "	SELECT tilausrivi.*, toimi.asn_sanomat
						FROM tilausrivi
						JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio
										AND lasku.tunnus = tilausrivi.otunnus
										AND lasku.mapvm = '0000-00-00'
										AND lasku.tapvm = '0000-00-00')
						JOIN lasku lasku2 ON (lasku2.yhtio = tilausrivi.yhtio
										AND lasku2.tunnus = tilausrivi.uusiotunnus
										AND lasku2.mapvm = '0000-00-00'
										AND lasku2.tapvm = '0000-00-00')
						JOIN toimi ON (toimi.yhtio = tilausrivi.yhtio
										AND toimi.tunnus = lasku.liitostunnus
										AND toimi.tunnus = '{$trow['tunnus']}'
										AND toimi.tyyppi != 'P'
										AND toimi.asn_sanomat IN ('K', 'L'))
						JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio
														AND tuotteen_toimittajat.liitostunnus = toimi.tunnus
														AND tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno
														AND tuotteen_toimittajat.toim_tuoteno IN ({$toim_tuotenolisa}))
						JOIN tuote on (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tuotteen_toimittajat.tuoteno AND tuote.status != 'P')
						WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
						AND tilausrivi.tyyppi = 'O'
						AND tilausrivi.otunnus LIKE '%{$rtuoteno[$i]['ostotilausnro']}'
						AND tilausrivi.tilaajanrivinro = '{$rtuoteno[$i]['tilaajanrivinro']}'
						AND tilausrivi.uusiotunnus != 0
						{$isalisa}
						ORDER BY laadittu ASC";
			$kokeillaan = pupe_query($query);

			if (mysql_num_rows($kokeillaan) > 0) {
				#echo "<pre>",str_replace("\t", "", $query),"</pre>";

				$kaytettavat_tilausrivit = teccom_kaytettavat_tilausrivit($kokeillaan);
			}

			if (count($kaytettavat_tilausrivit) == 0) {
				// tiukka 2
				$query = "	SELECT tilausrivi.*, toimi.asn_sanomat
							FROM tilausrivi
							JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio
											AND lasku.tunnus = tilausrivi.uusiotunnus
											AND lasku.mapvm = '0000-00-00'
											AND lasku.tapvm = '0000-00-00')
							JOIN lasku lasku2 ON (lasku2.yhtio = tilausrivi.yhtio
											AND lasku2.tunnus = tilausrivi.otunnus
											AND lasku2.comments LIKE '%{$rtuoteno[$i]['ostotilausnro']}%'
											AND lasku2.mapvm = '0000-00-00'
											AND lasku2.tapvm = '0000-00-00')
							JOIN toimi ON (toimi.yhtio = tilausrivi.yhtio
											AND toimi.tunnus = lasku.liitostunnus
											AND toimi.tunnus = '{$trow['tunnus']}'
											AND toimi.tyyppi != 'P'
											AND toimi.asn_sanomat IN ('K', 'L'))
							JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio
															AND tuotteen_toimittajat.liitostunnus = toimi.tunnus
															AND tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno
															AND tuotteen_toimittajat.toim_tuoteno IN ({$toim_tuotenolisa}))
							JOIN tuote on (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tuotteen_toimittajat.tuoteno AND tuote.status != 'P')
							WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
							AND tilausrivi.tyyppi = 'O'
							AND tilausrivi.uusiotunnus != 0
							AND tilausrivi.tilaajanrivinro = '{$rtuoteno[$i]['tilaajanrivinro']}'
							{$isalisa}
							ORDER BY laadittu ASC";
				$kokeillaan = pupe_query($query);

				if (mysql_num_rows($kokeillaan) > 0) {
					#echo "<pre>",str_replace("\t", "", $query),"</pre>";

					$kaytettavat_tilausrivit = teccom_kaytettavat_tilausrivit($kokeillaan);
				}
			}

			if (count($kaytettavat_tilausrivit) == 0) {
				// v‰ljempi 1
				$query = "	SELECT tilausrivi.*, toimi.asn_sanomat
							FROM tilausrivi
							JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio
											AND lasku.tunnus = tilausrivi.otunnus
											AND lasku.mapvm = '0000-00-00'
											AND lasku.tapvm = '0000-00-00')
							JOIN lasku lasku2 ON (lasku2.yhtio = tilausrivi.yhtio
											AND lasku2.tunnus = tilausrivi.uusiotunnus
											AND lasku2.mapvm = '0000-00-00'
											AND lasku2.tapvm = '0000-00-00')
							JOIN toimi ON (toimi.yhtio = tilausrivi.yhtio
											AND toimi.tunnus = lasku.liitostunnus
											AND toimi.tunnus = '{$trow['tunnus']}'
											AND toimi.tyyppi != 'P'
											AND toimi.asn_sanomat IN ('K', 'L'))
							JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio
															AND tuotteen_toimittajat.liitostunnus = toimi.tunnus
															AND tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno
															AND tuotteen_toimittajat.toim_tuoteno IN ({$toim_tuotenolisa}))
							JOIN tuote on (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tuotteen_toimittajat.tuoteno AND tuote.status != 'P')
							WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
							AND tilausrivi.tyyppi = 'O'
							AND tilausrivi.otunnus LIKE '%{$rtuoteno[$i]['ostotilausnro']}'
							AND tilausrivi.uusiotunnus != 0
							{$isalisa}
							ORDER BY laadittu ASC";
				$kokeillaan = pupe_query($query);

				if (mysql_num_rows($kokeillaan) > 0) {
					#echo "<pre>",str_replace("\t", "", $query),"</pre>";

					$kaytettavat_tilausrivit = teccom_kaytettavat_tilausrivit($kokeillaan);
				}
			}

			if (count($kaytettavat_tilausrivit) == 0) {
				// v‰ljempi 2
				$query = "	SELECT tilausrivi.*, toimi.asn_sanomat
							FROM tilausrivi
							JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio
											AND lasku.tunnus = tilausrivi.uusiotunnus
											AND lasku.mapvm = '0000-00-00'
											AND lasku.tapvm = '0000-00-00')
							JOIN lasku lasku2 ON (lasku2.yhtio = tilausrivi.yhtio
											AND lasku2.tunnus = tilausrivi.otunnus
											AND lasku2.comments LIKE '%{$rtuoteno[$i]['ostotilausnro']}%'
											AND lasku2.mapvm = '0000-00-00'
											AND lasku2.tapvm = '0000-00-00')
							JOIN toimi ON (toimi.yhtio = tilausrivi.yhtio
											AND toimi.tunnus = lasku.liitostunnus
											AND toimi.tunnus = '{$trow['tunnus']}'
											AND toimi.tyyppi != 'P'
											AND toimi.asn_sanomat IN ('K', 'L'))
							JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio
															AND tuotteen_toimittajat.liitostunnus = toimi.tunnus
															AND tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno
															AND tuotteen_toimittajat.toim_tuoteno IN ({$toim_tuotenolisa}))
							JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tuotteen_toimittajat.tuoteno AND tuote.status != 'P')
							WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
							AND tilausrivi.tyyppi = 'O'
							AND tilausrivi.uusiotunnus != 0
							{$isalisa}
							ORDER BY laadittu ASC";
				$kokeillaan = pupe_query($query);

				if (mysql_num_rows($kokeillaan) > 0) {
					#echo "<pre>",str_replace("\t", "", $query),"</pre>";

					$kaytettavat_tilausrivit = teccom_kaytettavat_tilausrivit($kokeillaan);
				}
			}

			if (count($kaytettavat_tilausrivit) == 0) {
				// lˆys‰
				$query = "	SELECT tilausrivi.*, toimi.asn_sanomat
							FROM tilausrivi
							JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio
											AND lasku.tunnus = tilausrivi.otunnus
											AND lasku.mapvm = '0000-00-00'
											AND lasku.tapvm = '0000-00-00')
							JOIN lasku lasku2 ON (lasku2.yhtio = tilausrivi.yhtio
											AND lasku2.tunnus = tilausrivi.uusiotunnus
											AND lasku2.mapvm = '0000-00-00'
											AND lasku2.tapvm = '0000-00-00')
							JOIN toimi ON (toimi.yhtio = tilausrivi.yhtio
											AND toimi.tunnus = lasku.liitostunnus
											AND toimi.tunnus = '{$trow['tunnus']}'
											AND toimi.tyyppi != 'P'
											AND toimi.asn_sanomat IN ('K', 'L'))
							JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio
															AND tuotteen_toimittajat.liitostunnus = toimi.tunnus
															AND tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno
															AND tuotteen_toimittajat.toim_tuoteno IN ({$toim_tuotenolisa}))
							JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tuotteen_toimittajat.tuoteno AND tuote.status != 'P')
							WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
							AND tilausrivi.tyyppi = 'O'
							AND tilausrivi.uusiotunnus != 0
							{$isalisa}
							ORDER BY laadittu ASC";
				$kokeillaan = pupe_query($query);

				if (mysql_num_rows($kokeillaan) > 0) {
					#echo "<pre>",str_replace("\t", "", $query),"</pre>";

					$kaytettavat_tilausrivit = teccom_kaytettavat_tilausrivit($kokeillaan);
				}
			}

			if (count($kaytettavat_tilausrivit) == 0) {
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
															AND tuotteen_toimittajat.toim_tuoteno IN ({$toim_tuotenolisa}))
							JOIN tuote on (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tuotteen_toimittajat.tuoteno AND tuote.status != 'P')
							WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
							AND tilausrivi.tyyppi = 'O'
							AND tilausrivi.otunnus LIKE '%{$rtuoteno[$i]['ostotilausnro']}'
							AND tilausrivi.tilaajanrivinro = '{$rtuoteno[$i]['tilaajanrivinro']}'
							AND tilausrivi.uusiotunnus = 0
							{$isalisa}
							ORDER BY laadittu ASC";
				$kokeillaan = pupe_query($query);

				if (mysql_num_rows($kokeillaan) > 0) {
					#echo "<pre>",str_replace("\t", "", $query),"</pre>";

					$kaytettavat_tilausrivit = teccom_kaytettavat_tilausrivit($kokeillaan);
				}
			}

			if (count($kaytettavat_tilausrivit) == 0) {
				// tiukka 2
				$query = "	SELECT tilausrivi.*, toimi.asn_sanomat
							FROM tilausrivi
							JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio
											AND lasku.tunnus = tilausrivi.otunnus)
							JOIN lasku lasku2 ON (lasku2.yhtio = tilausrivi.yhtio
											AND lasku2.tunnus = tilausrivi.otunnus
											AND lasku2.comments LIKE '%{$rtuoteno[$i]['ostotilausnro']}%')
							JOIN toimi ON (toimi.yhtio = tilausrivi.yhtio
											AND toimi.tunnus = lasku.liitostunnus
											AND toimi.tunnus = '{$trow['tunnus']}'
											AND toimi.tyyppi != 'P'
											AND toimi.asn_sanomat IN ('K', 'L'))
							JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio
															AND tuotteen_toimittajat.liitostunnus = toimi.tunnus
															AND tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno
															AND tuotteen_toimittajat.toim_tuoteno IN ({$toim_tuotenolisa}))
							JOIN tuote on (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tuotteen_toimittajat.tuoteno AND tuote.status != 'P')
							WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
							AND tilausrivi.tyyppi = 'O'
							AND tilausrivi.tilaajanrivinro = '{$rtuoteno[$i]['tilaajanrivinro']}'
							AND tilausrivi.uusiotunnus = 0
							{$isalisa}
							ORDER BY laadittu ASC";
				$kokeillaan = pupe_query($query);

				if (mysql_num_rows($kokeillaan) > 0) {
					#echo "<pre>",str_replace("\t", "", $query),"</pre>";

					$kaytettavat_tilausrivit = teccom_kaytettavat_tilausrivit($kokeillaan);
				}
			}

			if (count($kaytettavat_tilausrivit) == 0) {
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
															AND tuotteen_toimittajat.toim_tuoteno IN ({$toim_tuotenolisa}))
							JOIN tuote on (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tuotteen_toimittajat.tuoteno AND tuote.status != 'P')
							WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
							AND tilausrivi.tyyppi = 'O'
							AND tilausrivi.otunnus LIKE '%{$rtuoteno[$i]['ostotilausnro']}'
							AND tilausrivi.uusiotunnus = 0
							{$isalisa}
							ORDER BY laadittu ASC";
				$kokeillaan = pupe_query($query);

				if (mysql_num_rows($kokeillaan) > 0) {
					#echo "<pre>",str_replace("\t", "", $query),"</pre>";

					$kaytettavat_tilausrivit = teccom_kaytettavat_tilausrivit($kokeillaan);
				}
			}

			if (count($kaytettavat_tilausrivit) == 0) {
				// v‰ljempi 2
				$query = "	SELECT tilausrivi.*, toimi.asn_sanomat
							FROM tilausrivi
							JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio
											AND lasku.tunnus = tilausrivi.otunnus)
							JOIN lasku lasku2 ON (lasku2.yhtio = tilausrivi.yhtio
											AND lasku2.tunnus = tilausrivi.otunnus
											AND lasku2.comments LIKE '%{$rtuoteno[$i]['ostotilausnro']}%')
							JOIN toimi ON (toimi.yhtio = tilausrivi.yhtio
											AND toimi.tunnus = lasku.liitostunnus
											AND toimi.tunnus = '{$trow['tunnus']}'
											AND toimi.tyyppi != 'P'
											AND toimi.asn_sanomat IN ('K', 'L'))
							JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio
															AND tuotteen_toimittajat.liitostunnus = toimi.tunnus
															AND tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno
															AND tuotteen_toimittajat.toim_tuoteno IN ({$toim_tuotenolisa}))
							JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tuotteen_toimittajat.tuoteno AND tuote.status != 'P')
							WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
							AND tilausrivi.tyyppi = 'O'
							AND tilausrivi.uusiotunnus = 0
							{$isalisa}
							ORDER BY laadittu ASC";
				$kokeillaan = pupe_query($query);

				if (mysql_num_rows($kokeillaan) > 0) {
					#echo "<pre>",str_replace("\t", "", $query),"</pre>";

					$kaytettavat_tilausrivit = teccom_kaytettavat_tilausrivit($kokeillaan);
				}
			}

			if (count($kaytettavat_tilausrivit) == 0) {
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
															AND tuotteen_toimittajat.toim_tuoteno IN ({$toim_tuotenolisa}))
							JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tuotteen_toimittajat.tuoteno AND tuote.status != 'P')
							WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
							AND tilausrivi.tyyppi = 'O'
							AND tilausrivi.uusiotunnus = 0
							{$isalisa}
							ORDER BY laadittu ASC";

				if (mysql_num_rows($kokeillaan) > 0) {
					$kaytettavat_tilausrivit = teccom_kaytettavat_tilausrivit($kokeillaan);
				}
			}

			if (count($kaytettavat_tilausrivit) > 0) {

				$tilausrivi_nro = "";

				// katsotaan m‰ts‰‰kˆ ensiksi laskulla olevalla kappalem‰‰r‰ll‰
				foreach ($kaytettavat_tilausrivit as $riv) {

					$query = "	SELECT *, kpl+varattu AS varattu
								FROM tilausrivi
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tunnus = '{$riv}'
								AND kpl+varattu = '{$rtuoteno[$i]['kpl']}'";
					$tilausrivires = pupe_query($query);

					if (mysql_num_rows($tilausrivires) != 0) {
						$tilausrivi_nro = $riv;
						break;
					}
				}

				if ($tilausrivi_nro != "") {
					$tilausrivirow = mysql_fetch_assoc($tilausrivires);
				}
				else {
					reset($kaytettavat_tilausrivit);

					$tmp_kaytettavat_tilausrivit = $kaytettavat_tilausrivit;

					$tilausrivi_nro = array_shift($tmp_kaytettavat_tilausrivit);

					$query = "	SELECT *, kpl+varattu AS varattu
								FROM tilausrivi
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tunnus = '{$tilausrivi_nro}'";
					$tilausrivires = pupe_query($query);
					$tilausrivirow = mysql_fetch_assoc($tilausrivires);
				}

				// tehd‰‰n tuoteperhe-satula-tapaus-checkki
				$lapsiperhesql = "	SELECT
									tuoteperhe.isatuoteno,
									tuoteperhe.tuoteno,
									tuote.tuoteno, tuote.try,tuote.osasto,
									tuote.nimitys,tuote.yksikko,tuote.myyntihinta,
									tilausrivi.yhtio, tilausrivi.tyyppi,tilausrivi.toimaika, tilausrivi.kerayspvm,
									tilausrivi.otunnus,tilausrivi.perheid,tilausrivi.tilkpl, tilausrivi.varattu, tilausrivi.kpl,
									tilausrivi.tunnus,tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllytaso, tuotepaikat.hyllyvali
									FROM tilausrivi
									JOIN tuoteperhe ON (tuoteperhe.yhtio = tilausrivi.yhtio and tilausrivi.tuoteno = tuoteperhe.isatuoteno AND tuoteperhe.tyyppi in ('P','')  AND tuoteperhe.ohita_kerays !='')
									JOIN tuote on (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tuoteperhe.tuoteno)
									JOIN tuotepaikat ON (tuotepaikat.yhtio = tilausrivi.yhtio and tuotepaikat.tuoteno = tuote.tuoteno and tuotepaikat.oletus !='')
									WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
									AND tilausrivi.tunnus = '{$tilausrivirow['tunnus']}'
									ORDER BY tuoteperhe.tuoteno, tuoteperhe.tunnus";
				$lapsiresult = pupe_query($lapsiperhesql);

				while ($lapsitieto = mysql_fetch_assoc($lapsiresult)) {
					// hae tuotteen ostohinta
					$laskuselect = "SELECT * FROM lasku WHERE yhtio = '{$kukarow['yhtio']}' and tunnus = '{$lapsitieto['otunnus']}'";
					$laskures	= pupe_query($laskuselect);
					$laskurow	= mysql_fetch_assoc($laskures);
					list($hinta,,$ale,) = alehinta_osto($laskurow, $lapsitieto, $lapsitieto["tilkpl"]);

					$query = "	SELECT tunnus
								FROM tilausrivi
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND perheid = '{$lapsitieto['tunnus']}'
								AND tuoteno = '{$lapsitieto['tuoteno']}'
								AND kpl = '{$lapsitieto['kpl']}'
								AND varattu = '{$lapsitieto['varattu']}'
								AND tyyppi = 'O'";
					$loytyiko_lapsi_res = pupe_query($query);

					if (mysql_num_rows($loytyiko_lapsi_res) == 0) {

						$lisainsert = "	INSERT INTO tilausrivi SET
										yhtio			= '{$lapsitieto['yhtio']}',
										tyyppi			= '{$lapsitieto['tyyppi']}',
										toimaika		= '{$lapsitieto['toimaika']}',
										kerayspvm		= '{$lapsitieto['kerayspvm']}',
										otunnus			= '{$lapsitieto['otunnus']}',
										tuoteno			= '{$lapsitieto['tuoteno']}',
										try				= '{$lapsitieto['try']}',
										osasto			= '{$lapsitieto['osasto']}',
										nimitys			= '{$lapsitieto['nimitys']}',
										yksikko			= '{$lapsitieto['yksikko']}',
										tilkpl			= '{$lapsitieto['tilkpl']}',
										varattu			= '{$lapsitieto['varattu']}',
										kpl 			= '{$lapsitieto['kpl']}',
										ale1			= '{$ale['ale1']}',
										ale2			= '{$ale['ale2']}',
										ale3			= '{$ale['ale3']}',
										hinta			= '{$hinta}',
										laatija			= 'lapset',
										kommentti		= 'TECCOM-lasku: TL:{$lapsitieto['tunnus']} tuotteelle: {$lapsitieto['isatuoteno']} lis‰t‰‰n lapsituote: {$lapsitieto['tuoteno']}',
										laadittu		=  now(),
										hyllyalue		= '{$lapsitieto['hyllyalue']}',
										hyllynro		= '{$lapsitieto['hyllynro']}',
										hyllytaso		= '{$lapsitieto['hyllytaso']}',
										hyllyvali		= '{$lapsitieto['hyllyvali']}',
										perheid			= '{$lapsitieto['tunnus']}'";
						$inskres = pupe_query($lisainsert);
					}

					// p‰ivitet‰‰n is‰
					$updateisa = "UPDATE tilausrivi SET perheid = tunnus WHERE yhtio = '{$kukarow['yhtio']}' and tunnus = '{$tilausrivirow['tunnus']}'";
					$updateres = pupe_query($updateisa);

					$tilausrivirow['perheid'] = $tilausrivirow['tunnus'];
				}

				if ($tilausrivirow['perheid'] == $tilausrivirow['tunnus']) {

					// etsit‰‰n lapset ja splitataan ne is‰n mukaan
					$query = "	SELECT *
								FROM tilausrivi
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND perheid = '{$tilausrivirow['perheid']}'
								AND tunnus != '{$tilausrivirow['perheid']}'";
					$lapsi_chk_res = pupe_query($query);

					while ($lapsi_chk_row = mysql_fetch_assoc($lapsi_chk_res)) {
						if ($rtuoteno[$i+1]["lapsi"] == "yes") $rtuoteno[$i+1]["isa_olemassa"] = $tilausrivirow['tunnus'];
					}
				}

				// jos ei splitata, p‰ivitet‰‰n hinta ja kohdistetaan.
				if ($tilausrivirow['varattu'] == $rtuoteno[$i]["kpl"]) {

					$update_asn = "	UPDATE asn_sanomat SET
									tilausrivi = '{$tilausrivirow['tunnus']}',
									keikkarivinhinta = '{$tilausrivirow['hinta']}',
									ale1 = '{$tilausrivirow['ale1']}',
									ale2 = '{$tilausrivirow['ale2']}',
									ale3 = '{$tilausrivirow['ale3']}',
									tuoteno = '{$tilausrivirow['tuoteno']}'
									WHERE yhtio = '{$kukarow['yhtio']}'
									AND tunnus = '{$rtuoteno[$i]['insert_id']}'
									AND laji = 'tec'";
					pupe_query($update_asn);

					if ($tilausrivirow['uusiotunnus'] == 0) {

						$indx = count($ostoilla);

						// lˆytyi, ei ole keikalla
						$ostoilla[$indx]["tunnus"] = $tilausrivirow['tunnus']; // tilausrivi tunnus
						$ostoilla[$indx]["hinta"] = $rtuoteno[$i]["hinta"];
						$ostoilla[$indx]["kpl"] = $rtuoteno[$i]["kpl"];
						$ostoilla[$indx]["laskuntunnus"] = $rtuoteno[$i]["ostotilausnro"]; // laskun tunnus
						$ostoilla[$indx]["tilaajanrivinro"] = $rtuoteno[$i]["tilaajanrivinro"];
						$ostoilla[$indx]["insert_id"] = $rtuoteno[$i]["insert_id"];
						$ostoilla[$indx]["lisakulu"] = isset($rtuoteno[$i]["lisakulu"]) ? $rtuoteno[$i]["lisakulu"] : 0;
						$ostoilla[$indx]["kulu"] = isset($rtuoteno[$i]["kulu"]) ? $rtuoteno[$i]["kulu"] : 0;
						$ostoilla[$indx]["ale1"] = isset($rtuoteno[$i]["ale1"]) ? $rtuoteno[$i]["ale1"] : 0;
						$ostoilla[$indx]["ale2"] = isset($rtuoteno[$i]["ale2"]) ? $rtuoteno[$i]["ale2"] : 0;
						$ostoilla[$indx]["ale3"] = isset($rtuoteno[$i]["ale3"]) ? $rtuoteno[$i]["ale3"] : 0;
						$ostoilla[$indx]['tuoteno'] = $tilausrivirow['tuoteno'];
					}
					else {

						$indx = count($keikoilla);

						// lˆytyi, on jo keikalla
						$keikoilla[$indx]["tunnus"] = $tilausrivirow['tunnus'];
						$keikoilla[$indx]["uusiotunnus"] = $tilausrivirow['uusiotunnus'];
						$keikoilla[$indx]["hinta"] = $rtuoteno[$i]["hinta"];
						$keikoilla[$indx]["kpl"] = $rtuoteno[$i]["kpl"];
						$keikoilla[$indx]["tilaajanrivinro"] = $rtuoteno[$i]["tilaajanrivinro"];
						$keikoilla[$indx]["insert_id"] = $rtuoteno[$i]["insert_id"];
						$keikoilla[$indx]["lisakulu"] = isset($rtuoteno[$i]["lisakulu"]) ? $rtuoteno[$i]["lisakulu"] : 0;
						$keikoilla[$indx]["kulu"] = isset($rtuoteno[$i]["kulu"]) ? $rtuoteno[$i]["kulu"] : 0;
						$keikoilla[$indx]["ale1"] = isset($rtuoteno[$i]["ale1"]) ? $rtuoteno[$i]["ale1"] : 0;
						$keikoilla[$indx]["ale2"] = isset($rtuoteno[$i]["ale2"]) ? $rtuoteno[$i]["ale2"] : 0;
						$keikoilla[$indx]["ale3"] = isset($rtuoteno[$i]["ale3"]) ? $rtuoteno[$i]["ale3"] : 0;
						$keikoilla[$indx]['tuoteno'] = $tilausrivirow['tuoteno'];
					}
				}
				elseif ($rtuoteno[$i]["kpl"] < $tilausrivirow["varattu"]) {
					// Splitataan tilausrivi, tallennetaan vanha rivitunnus tilausrivi.tilaajanrivinro kentt‰‰n

					$kappaleerotus = $tilausrivirow["varattu"] - $rtuoteno[$i]["kpl"];

					$poikkeukset = array(
						'tilausrivi.varattu' => ($tilausrivirow['kpl'] == 0 ? $kappaleerotus : 0),
						'tilausrivi.kpl' => ($tilausrivirow['kpl'] != 0 ? $kappaleerotus : 0),
						'tilausrivi.luontiaika' => $tilausrivirow['luontiaika'],
					);

					$tilausrivi_id = kopioi_tilausrivi($tilausrivirow['tunnus'], $poikkeukset);

					// katsotaan onko tuoteperheen is‰tuote
					if ($tilausrivirow['perheid'] == $tilausrivirow['tunnus']) {

						// etsit‰‰n lapset ja splitataan ne is‰n mukaan
						$query = "	SELECT *
									FROM tilausrivi
									WHERE yhtio = '{$kukarow['yhtio']}'
									AND perheid = '{$tilausrivirow['perheid']}'
									AND tunnus != '{$tilausrivirow['perheid']}'";
						$lapsi_chk_res = pupe_query($query);

						while ($lapsi_chk_row = mysql_fetch_assoc($lapsi_chk_res)) {

							$poikkeukset = array(
								'tilausrivi.varattu' => ($tilausrivirow['kpl'] == 0 ? $kappaleerotus : 0),
								'tilausrivi.kpl' => ($tilausrivirow['kpl'] != 0 ? $kappaleerotus : 0),
								'tilausrivi.perheid' => $tilausrivi_id,
								'tilausrivi.luontiaika' => $lapsi_chk_row['luontiaika'],
							);

							$lapsi_uusi_id = kopioi_tilausrivi($lapsi_chk_row['tunnus'], $poikkeukset);

							// jos rivi on jo viety varastoon ja yll‰ splitattiin se rivi, korjataan tapahtumat
							if ($lapsi_chk_row['kpl'] > 0) {
								$query = "	SELECT *
											FROM tapahtuma
											WHERE yhtio = '{$kukarow['yhtio']}'
											AND laji = 'tulo'
											AND rivitunnus = '{$lapsi_chk_row['tunnus']}'";
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
											$values .= ", '{$lapsi_uusi_id}'";
											break;
										case 'kpl':
											$values .= ", '{$kappaleerotus}'";
											break;
										default:
											$values .= ", '".$tapahtuma_row[$fieldname]."'";
									}
								}

								$kysely  = "INSERT INTO tapahtuma ({$fields}) VALUES ({$values})";
								$uusires = pupe_query($kysely);
								$tapahtuma_id = mysql_insert_id();

								$query = "	UPDATE tapahtuma
											SET kpl = '{$rtuoteno[$i]['kpl']}'
											WHERE yhtio = '{$kukarow['yhtio']}'
											AND laji = 'tulo'
											AND rivitunnus = '{$lapsi_chk_row['tunnus']}'";
								$upd_res = pupe_query($query);
							}
						}

						$query = "	UPDATE tilausrivi SET
									perheid = '{$tilausrivi_id}'
									WHERE yhtio = '{$kukarow['yhtio']}'
									AND tunnus = '{$tilausrivi_id}'";
						$upd_res = pupe_query($query);
					}

					$splitlisa = $tilausrivirow['kpl'] > 0 ? " varattu = '0' " : " varattu = '{$rtuoteno[$i]['kpl']}' ";
					$splitlisa2 = $tilausrivirow['kpl'] > 0 ? ", kpl = '{$rtuoteno[$i]['kpl']}'" : ", kpl = 0 ";
					$rivihintalisa = $tilausrivirow['kpl'] > 0 ? ", rivihinta = '".(($tilausrivirow['rivihinta'] / $tilausrivirow['kpl']) * $rtuoteno[$i]['kpl'])."'" : ", rivihinta = 0";

					// P‰ivitet‰‰n alkuper‰iselle riville saapunut kappalem‰‰r‰
					$query = "	UPDATE tilausrivi SET
								{$splitlisa}
								{$splitlisa2}
								{$rivihintalisa}
								WHERE yhtio = '$kukarow[yhtio]'
								AND tunnus = '{$tilausrivirow['tunnus']}'";
					$upres = pupe_query($query);

					$update_asn = "	UPDATE asn_sanomat SET
									tilausrivi = '{$tilausrivirow['tunnus']}',
									keikkarivinhinta = '{$tilausrivirow['hinta']}',
									ale1 = '{$tilausrivirow['ale1']}',
									ale2 = '{$tilausrivirow['ale2']}',
									ale3 = '{$tilausrivirow['ale3']}',
									tuoteno = '{$tilausrivirow['tuoteno']}'
									WHERE yhtio = '{$kukarow['yhtio']}'
									AND tunnus = '{$rtuoteno[$i]['insert_id']}'
									AND laji = 'tec'";
					$foo = pupe_query($update_asn);

					if ($tilausrivirow['uusiotunnus'] == 0) {

						$indx = count($ostoilla);

						// lˆytyi, ei ole keikalla
						$ostoilla[$indx]["tunnus"] 			= $tilausrivirow['tunnus']; // tilausrivi tunnus
						$ostoilla[$indx]["hinta"] 				= $rtuoteno[$i]["hinta"];
						$ostoilla[$indx]["kpl"] 				= $rtuoteno[$i]["kpl"];
						$ostoilla[$indx]["laskuntunnus"]		= $rtuoteno[$i]["ostotilausnro"]; // laskun tunnus
						$ostoilla[$indx]["tilaajanrivinro"]	= $rtuoteno[$i]["tilaajanrivinro"];
						$ostoilla[$indx]["insert_id"] 			= $rtuoteno[$i]["insert_id"];
						$ostoilla[$indx]["lisakulu"] 			= isset($rtuoteno[$i]["lisakulu"]) ? $rtuoteno[$i]["lisakulu"] : 0;
						$ostoilla[$indx]["kulu"] 				= isset($rtuoteno[$i]["kulu"]) ? $rtuoteno[$i]["kulu"] : 0;
						$ostoilla[$indx]["ale1"] 				= isset($rtuoteno[$i]["ale1"]) ? $rtuoteno[$i]["ale1"] : 0;
						$ostoilla[$indx]["ale2"] 				= isset($rtuoteno[$i]["ale2"]) ? $rtuoteno[$i]["ale2"] : 0;
						$ostoilla[$indx]["ale3"] 				= isset($rtuoteno[$i]["ale3"]) ? $rtuoteno[$i]["ale3"] : 0;
						$ostoilla[$indx]['tuoteno'] 			= $tilausrivirow['tuoteno'];
					}
					else {

						$indx = count($keikoilla);

						// lˆytyi, on jo keikalla
						$keikoilla[$indx]["tunnus"] 			= $tilausrivirow['tunnus'];
						$keikoilla[$indx]["uusiotunnus"] 		= $tilausrivirow['uusiotunnus'];
						$keikoilla[$indx]["kpl"] 				= $rtuoteno[$i]["kpl"];
						$keikoilla[$indx]["hinta"] 			= $rtuoteno[$i]["hinta"];
						$keikoilla[$indx]["tilaajanrivinro"] 	= $rtuoteno[$i]["tilaajanrivinro"];
						$keikoilla[$indx]["insert_id"] 		= $rtuoteno[$i]["insert_id"];
						$keikoilla[$indx]["lisakulu"] 			= isset($rtuoteno[$i]["lisakulu"]) ? $rtuoteno[$i]["lisakulu"] : 0;
						$keikoilla[$indx]["kulu"] 				= isset($rtuoteno[$i]["kulu"]) ? $rtuoteno[$i]["kulu"] : 0;
						$keikoilla[$indx]["ale1"] 				= isset($rtuoteno[$i]["ale1"]) ? $rtuoteno[$i]["ale1"] : 0;
						$keikoilla[$indx]["ale2"] 				= isset($rtuoteno[$i]["ale2"]) ? $rtuoteno[$i]["ale2"] : 0;
						$keikoilla[$indx]["ale3"] 				= isset($rtuoteno[$i]["ale3"]) ? $rtuoteno[$i]["ale3"] : 0;
						$keikoilla[$indx]['tuoteno'] 			= $tilausrivirow['tuoteno'];
					}

					// jos rivi on jo viety varastoon ja yll‰ splitattiin se rivi, korjataan tapahtumat
					if ($tilausrivirow['kpl'] > 0) {
						$query = "	SELECT *
									FROM tapahtuma
									WHERE yhtio = '{$kukarow['yhtio']}'
									AND laji = 'tulo'
									AND rivitunnus = '{$tilausrivirow['tunnus']}'";
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
									$values .= ", '".$tapahtuma_row[$fieldname]."'";
							}
						}

						$kysely  = "INSERT INTO tapahtuma ({$fields}) VALUES ({$values})";
						$uusires = pupe_query($kysely);
						$tapahtuma_id = mysql_insert_id();

						$query = "	UPDATE tapahtuma
									SET kpl = '{$rtuoteno[$i]['kpl']}'
									WHERE yhtio = '{$kukarow['yhtio']}'
									AND laji = 'tulo'
									AND rivitunnus = '{$tilausrivirow['tunnus']}'";
						$upd_res = pupe_query($query);
					}
				}
				else {
					// tuli enemm‰n kuin tilattu
					$laskuri = 0;
					$kohdistamatta = $rtuoteno[$i]["kpl"];
					$lisa_array = array();
					$split_array = array();

					// Lasketaan ensin kaikki rivit yhteen ja sen j‰lkeen katsotaan tarvitaanko lis‰rivi‰
					foreach ($kaytettavat_tilausrivit as $tilriv1) {

						if ($kohdistamatta <= 0) break;

						$query = "SELECT kpl+varattu AS varattu FROM tilausrivi WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$tilriv1}'";
						#echo "<pre>$query</pre>";
						$kpl_chk_res = pupe_query($query);
						$kpl_chk_row = mysql_fetch_assoc($kpl_chk_res);

						#echo "varattu: $kpl_chk_row[varattu]<br>";

						$laskuri += $kpl_chk_row["varattu"];
						$lisa_array[] = $tilriv1;
						$split_array[] = $tilriv1;
						$kohdistamatta -= $kpl_chk_row["varattu"];
					}

					$ylimaaraista = $rtuoteno[$i]["kpl"] - $laskuri; // liikaa miinus tilauksella

					#echo "$ylimaaraista = {$rtuoteno[$i]['kpl']} - $laskuri<br><br>";

					if ($ylimaaraista > 0) {

						$query = "	SELECT DISTINCT tilausrivi.tunnus, toimi.asn_sanomat,
									IF(tilausrivi.laskutettuaika = '0000-00-00', '9999-12-31', tilausrivi.laskutettuaika) AS laskutettuaika,
									IF(tilausrivi.uusiotunnus = 0, ".PHP_INT_MAX.", tilausrivi.uusiotunnus) AS uusiotunnus
									FROM tilausrivi
									JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio
													AND (lasku.tunnus = tilausrivi.uusiotunnus OR lasku.tunnus = tilausrivi.otunnus)
													AND lasku.mapvm = '0000-00-00'
													AND lasku.tapvm = '0000-00-00')
									JOIN toimi ON (toimi.yhtio = tilausrivi.yhtio
													AND toimi.tunnus = lasku.liitostunnus
													AND toimi.tunnus = '{$trow['tunnus']}'
													AND toimi.tyyppi != 'P'
													AND toimi.asn_sanomat IN ('K', 'L'))
									JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio
																	AND tuotteen_toimittajat.liitostunnus = toimi.tunnus
																	AND tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno
																	AND tuotteen_toimittajat.toim_tuoteno IN ({$toim_tuotenolisa}))
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
										AND tilausrivi LIKE '%{$kokeiltiin['tunnus']}%'";
							$chk_res = pupe_query($query);

							if (mysql_num_rows($chk_res) != 0 or in_array($kokeiltiin['tunnus'], $kaytettavat_tilausrivit)) continue;

							if ($kohdistamatta <= 0) break;

							if ($kokeiltiin['asn_sanomat'] == 'K') {
								$query = "	SELECT tunnus
											FROM asn_sanomat
											WHERE yhtio = '{$kukarow['yhtio']}'
											AND laji = 'asn'
											AND tilausrivi LIKE '%{$kokeiltiin['tunnus']}%'
											AND status = 'X'";
								$chk_res = pupe_query($query);

								if (mysql_num_rows($chk_res) == 0) {
									// echo "3 Debug VIRHEET++: tilausrivi $tilausrivirow[tunnus] tuoteno {$rtuoteno[$i]['tuoteno']} tuoteno2 {$rtuoteno[$i]['tuoteno2']} tuoteno3 {$rtuoteno[$i]['tuoteno3']}<br><br>";
									// $virheet++;
									continue;
								}
							}

							$query = "SELECT kpl+varattu AS varattu FROM tilausrivi WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$kokeiltiin['tunnus']}'";
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

						#echo "Jouduttiin haaraan ylim‰‰r‰ist‰ > 0 ($ylimaaraista > 0)<br><br>";

						// Tehd‰‰n uusi rivi, jossa on j‰ljelle j‰‰neet kappaleet
						$fields = "yhtio";
						$values = "'{$kukarow['yhtio']}'";

						// Ei monisteta tunnusta
						for ($ii = 1; $ii < mysql_num_fields($tilausrivires) - 1; $ii++) {

							$fieldname = mysql_field_name($tilausrivires,$ii);

							if ($fieldname == 'tunnus') continue;

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
								case 'rivihinta':
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
						$erotus			= ($split_row["kpl"] + $split_row['varattu']) - $positiivinen_arvo;

						$kaytetyt_tilausrivit[] = $vika_rivi;

						// P‰ivitet‰‰n alkuper‰iselle riville "ylim‰‰r‰inen" ja uudelle splitille "erotus"
						$poikkeukset = array(
							'tilausrivi.varattu' => ($split_row['kpl'] == 0 ? $positiivinen_arvo : 0),
							'tilausrivi.tilkpl' => $positiivinen_arvo,
							'tilausrivi.kpl' => ($split_row['kpl'] != 0 ? $positiivinen_arvo : 0),
							'tilausrivi.kommentti' => "TECCOM-laskulla 2: {$laskun_numero}. Tehtiin puuttuva tilausrivi m‰‰r‰lle {$positiivinen_arvo} tuotteelle {$split_row['tuoteno']}",
							'tilausrivi.laatija' => "split",
						);

						$tilausrivi_id = kopioi_tilausrivi($split_row['tunnus'], $poikkeukset);

						// katsotaan onko tuoteperheen is‰tuote
						if ($split_row['perheid'] == $split_row['tunnus']) {

							// etsit‰‰n lapset ja splitataan ne is‰n mukaan
							$query = "	SELECT *
										FROM tilausrivi
										WHERE yhtio = '{$kukarow['yhtio']}'
										AND perheid = '{$split_row['perheid']}'
										AND tunnus != '{$split_row['perheid']}'";
							$lapsi_chk_res = pupe_query($query);

							while ($lapsi_chk_row = mysql_fetch_assoc($lapsi_chk_res)) {

								$poikkeukset = array(
									'tilausrivi.varattu' => ($split_row['kpl'] == 0 ? $positiivinen_arvo : 0),
									'tilausrivi.kpl' => ($split_row['kpl'] != 0 ? $positiivinen_arvo : 0),
									'tilausrivi.perheid' => $tilausrivi_id,
									'tilausrivi.luontiaika' => $lapsi_chk_row['luontiaika'],
								);

								$lapsi_uusi_id = kopioi_tilausrivi($lapsi_chk_row['tunnus'], $poikkeukset);

								// jos rivi on jo viety varastoon ja yll‰ splitattiin se rivi, korjataan tapahtumat
								if ($lapsi_chk_row['kpl'] > 0) {
									$query = "	SELECT *
												FROM tapahtuma
												WHERE yhtio = '{$kukarow['yhtio']}'
												AND laji = 'tulo'
												AND rivitunnus = '{$lapsi_chk_row['tunnus']}'";
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
												$values .= ", '{$lapsi_uusi_id}'";
												break;
											case 'kpl':
												$values .= ", '{$positiivinen_arvo}'";
												break;
											default:
												$values .= ", '".$tapahtuma_row[$fieldname]."'";
										}
									}

									$kysely  = "INSERT INTO tapahtuma ({$fields}) VALUES ({$values})";
									$uusires = pupe_query($kysely);
									$tapahtuma_id = mysql_insert_id();

									$query = "	UPDATE tapahtuma
												SET kpl = '{$erotus}'
												WHERE yhtio = '{$kukarow['yhtio']}'
												AND laji = 'tulo'
												AND rivitunnus = '{$lapsi_chk_row['tunnus']}'";
									$upd_res = pupe_query($query);
								}
							}

							$query = "	UPDATE tilausrivi SET
										perheid = '{$tilausrivi_id}'
										WHERE yhtio = '{$kukarow['yhtio']}'
										AND tunnus = '{$tilausrivi_id}'";
							$upd_res = pupe_query($query);
						}

						$splitlisa = $split_row['kpl'] != 0 ? " varattu = '0', " : " varattu = '{$erotus}', ";
						$splitlisa2 = $split_row['kpl'] != 0 ? " kpl = '{$erotus}', " : " kpl = 0, ";
						$rivihintalisa = $split_row['kpl'] != 0 ? " rivihinta = '".(($split_row['rivihinta'] / $split_row['kpl']) * $erotus)."', " : " rivihinta = 0, ";

						$komm_lisa = $split_row['kpl'] + $split_row['varattu'];

						// P‰ivitet‰‰n alkuper‰iselle riville saapunut kappalem‰‰r‰
						$query = "	UPDATE tilausrivi SET
									{$splitlisa}
									{$splitlisa2}
									{$rivihintalisa}
									kommentti = concat(kommentti,' Ennen splittausta {$komm_lisa} : kohdistettiin {$erotus}')
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
							$query = "	SELECT *
										FROM tapahtuma
										WHERE yhtio = '{$kukarow['yhtio']}'
										AND laji = 'tulo'
										AND rivitunnus = '{$split_row['tunnus']}'";
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
										$values .= ", '".$tapahtuma_row[$fieldname]."'";
								}
							}

							$kysely  = "INSERT INTO tapahtuma ({$fields}) VALUES ({$values})";
							$uusires = pupe_query($kysely);
							$tapahtuma_id = mysql_insert_id();

							$query = "	UPDATE tapahtuma
										SET kpl = '{$erotus}'
										WHERE yhtio = '{$kukarow['yhtio']}'
										AND laji = 'tulo'
										AND rivitunnus = '{$split_row['tunnus']}'";
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
							$ostoilla[$indx]["lisakulu"] = isset($rtuoteno[$i]["lisakulu"]) ? $rtuoteno[$i]["lisakulu"] : 0;
							$ostoilla[$indx]["kulu"] = isset($rtuoteno[$i]["kulu"]) ? $rtuoteno[$i]["kulu"] : 0;
							$ostoilla[$indx]["ale1"] = isset($rtuoteno[$i]["ale1"]) ? $rtuoteno[$i]["ale1"] : 0;
							$ostoilla[$indx]["ale2"] = isset($rtuoteno[$i]["ale2"]) ? $rtuoteno[$i]["ale2"] : 0;
							$ostoilla[$indx]["ale3"] = isset($rtuoteno[$i]["ale3"]) ? $rtuoteno[$i]["ale3"] : 0;
							$ostoilla[$indx]['tuoteno'] = $tilausrivirow['tuoteno'];
						}
						else {

							$indx = count($keikoilla);

							// lˆytyi, on jo keikalla
							$keikoilla[$indx]["tunnus"] = $tilriv;
							$keikoilla[$indx]["uusiotunnus"] = $uusiotunnus_row['uusiotunnus'];
							$keikoilla[$indx]["kpl"] = $rtuoteno[$i]["kpl"];
							$keikoilla[$indx]["hinta"] = $rtuoteno[$i]["hinta"];
							$keikoilla[$indx]["tilaajanrivinro"] = $rtuoteno[$i]["tilaajanrivinro"];
							$keikoilla[$indx]["insert_id"] = $rtuoteno[$i]["insert_id"];
							$keikoilla[$indx]["lisakulu"] = isset($rtuoteno[$i]["lisakulu"]) ? $rtuoteno[$i]["lisakulu"] : 0;
							$keikoilla[$indx]["kulu"] = isset($rtuoteno[$i]["kulu"]) ? $rtuoteno[$i]["kulu"] : 0;
							$keikoilla[$indx]["ale1"] = isset($rtuoteno[$i]["ale1"]) ? $rtuoteno[$i]["ale1"] : 0;
							$keikoilla[$indx]["ale2"] = isset($rtuoteno[$i]["ale2"]) ? $rtuoteno[$i]["ale2"] : 0;
							$keikoilla[$indx]["ale3"] = isset($rtuoteno[$i]["ale3"]) ? $rtuoteno[$i]["ale3"] : 0;
							$keikoilla[$indx]['tuoteno'] = $tilausrivirow['tuoteno'];
						}
					}
				}
			}
			else {
				// ei lˆydy tai joku muu virhe
				#echo "Virheellisi‰ kappalem‰‰ri‰ tilauksella ".$rtuoteno[$i]["ostotilausnro"]." tuote ".$rtuoteno[$i]["tuoteno"]." kpl ".$rtuoteno[$i]["kpl"]."\n";
				#echo "7 Debug VIRHEET++: tilausrivi $tilausrivirow[tunnus] tuoteno {$rtuoteno[$i]['tuoteno']} tuoteno2 {$rtuoteno[$i]['tuoteno2']} tuoteno3: {$rtuoteno[$i]['tuoteno3']}<br><br>";
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

		// Mik‰li toimittajalla on kentt‰ "asn_sanomat" arvoja, ja ne on
		// K = tulee asn-sanoma ja lasku
		// S = tulee vain sanoma   <--- ei tehd‰ mit‰‰n
		// L = tulee vain lasku

		// ensin ostoilta, sitten keikalta, sitten virheet.
		// saadaan luoda keikka vain, jos tulee pelkk‰ lasku (eli toimittajan takana on asn_sanomat = 'L')
		if ($virheet == 0 and count($ostoilla) > 0 and count($keikoilla) == 0 and ($trow['asn_sanomat'] == 'L' or ($trow['asn_sanomat'] == 'K' and $tullaan_virhetarkistuksesta))) {

			#echo "Debug: kululaskut haara, count(ostoilla) > 0 and count(keikoilla) == 0 and virheet == 0<br><br>";

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
						comments				= '".t("Saapuminen luotu laskusta")." $osrow[laskunro]',
						tila         			= 'K',
						luontiaika	 			= now(),
						laatija		 			= '$kukarow[kuka]'";
			$insertres = pupe_query($query);
			$keikan_tunnus = mysql_insert_id();
			#echo "<pre>",str_replace("\t", "", $query),"</pre>Keikan tunnus:$keikan_tunnus<br><br>";

			#echo "<font class='message'>Liitetty saapumiseen {$id}</font><br><br>";

			$query  = "UNLOCK TABLE";
			$result = pupe_query($query);

			$laskun_kulut = array(
				'osto_kulu' => $osrow['osto_kulu'],
				'osto_kulu_alv' => $osrow['osto_kulu_alv'],
				'osto_rahti' => $osrow['osto_rahti'],
				'osto_rahti_alv' => $osrow['osto_rahti_alv'],
				'osto_rivi_kulu' => $osrow['osto_rivi_kulu'],
				'osto_rivi_kulu_alv' => $osrow['osto_rivi_kulu_alv']
			);

			verkkolasku_luo_liitosotsikko($tunnus, $keikan_tunnus, $laskun_kulut);

			// lis‰t‰‰n tuotteet keikalle ja kohdistetaan
			foreach ($ostoilla as $pointteri) {

				$query = "	SELECT hinta, otunnus, perheid, tuoteno, ale1, ale2, ale3
							FROM tilausrivi
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND tunnus = '{$pointteri['tunnus']}'";
				$hinta_chk_res = pupe_query($query);
				$hinta_chk_row = mysql_fetch_assoc($hinta_chk_res);

				$update_asn = "	UPDATE asn_sanomat SET
								#tilausrivi = '{$pointteri['tunnus']}',
								keikkarivinhinta = '{$hinta_chk_row['hinta']}',
								ale1 = '{$hinta_chk_row['ale1']}',
								ale2 = '{$hinta_chk_row['ale2']}',
								ale3 = '{$hinta_chk_row['ale3']}',
								status = 'X',
								tuoteno = '{$hinta_chk_row['tuoteno']}'
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tunnus = '{$pointteri['insert_id']}'
								AND laji = 'tec'";
				pupe_query($update_asn);

				if ($hinta_chk_row['perheid'] != 0) {

					$lapsen_lisakululisa = "";

					if (isset($pointteri["lisakulu"]) and trim($pointteri["lisakulu"]) != 0) {
						$lapsen_lisakululisa = " hinta = {$pointteri['lisakulu']} / {$pointteri['kpl']}, ";
					}

					$update = "	UPDATE tilausrivi SET
								{$lapsen_lisakululisa}
								ale1 = 0,
								ale2 = 0,
								ale3 = 0,
								uusiotunnus = '{$keikan_tunnus}'
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND otunnus = '{$hinta_chk_row['otunnus']}'
								AND perheid = '{$hinta_chk_row['perheid']}'
								AND tunnus != '{$hinta_chk_row['perheid']}'";
					pupe_query($update);
				}

				$ale1lisa = (isset($pointteri['ale1']) and trim($pointteri['ale1']) != "" and trim($pointteri['ale1']) != 0) ? " ale1 = '{$pointteri['ale1']}', " : " ale1 = 0, ";
				$ale2lisa = (isset($pointteri['ale2']) and trim($pointteri['ale2']) != "" and trim($pointteri['ale2']) != 0) ? " ale2 = '{$pointteri['ale2']}', " : " ale2 = 0, ";
				$ale3lisa = (isset($pointteri['ale3']) and trim($pointteri['ale3']) != "" and trim($pointteri['ale3']) != 0) ? " ale3 = '{$pointteri['ale3']}', " : " ale3 = 0, ";

				$update = "	UPDATE tilausrivi SET
							hinta = '{$pointteri['hinta']}',
							{$ale1lisa}
							{$ale2lisa}
							{$ale3lisa}
							uusiotunnus = '{$keikan_tunnus}',
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND tunnus = '{$pointteri['tunnus']}'";
				#echo "<pre>",str_replace("\t", "", $update),"</pre>";
				pupe_query($update);

				$update = "	UPDATE asn_sanomat SET
							toim_tuoteno = '{$pointteri['tuoteno']}'
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND tunnus = '{$pointteri['insert_id']}'";
				#echo "<pre>$query</pre>";
				pupe_query($update);
			}

			$query = "SELECT * FROM lasku WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$keikan_tunnus}'";
			$laskures = pupe_query($query);
			$laskurow = mysql_fetch_assoc($laskures);

			$tee_kululaskut = "liita";
 			$silent = "SILENT";
 			$keikanalatila = "";
 			$laskutunnus = $tunnus;
 			$otunnus = $keikan_tunnus;

			require("tilauskasittely/kululaskut.inc");
		}
		elseif (count($keikoilla) > 0 and $virheet == 0) {

			#echo "Debug: kululaskut haara, count(keikoilla) > 0 and virheet == 0<br><br>";

			// tarkistetaan onko keikalla ylim‰‰r‰isi‰ rivej‰ mit‰ ei ole laskulla
			// jos on, tehd‰‰n uusi keikka laskun rivej‰ varten
			$query = "	SELECT tunnus
						FROM tilausrivi
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND uusiotunnus = '{$keikoilla[0]['uusiotunnus']}'";
			$tunnus_fetch_res = pupe_query($query);

			$tunnus_chk_array = array();

			while ($tunnus_fetch_row = mysql_fetch_assoc($tunnus_fetch_res)) {
				$tunnus_chk_array["{$tunnus_fetch_row['tunnus']}"] = $tunnus_fetch_row['tunnus'];
			}

			#echo "N‰m‰ checkataaan: <pre>",var_dump($tunnus_chk_array),"</pre>";

			#echo "Keikoilla: <pre>",var_dump($keikoilla),"</pre>";

			// poistetaan arraysta laskulla olevat tilausrivitunnukset
			foreach ($keikoilla as $pointteri2) {
				if (isset($tunnus_chk_array["{$pointteri2['tunnus']}"])) {
					#echo "$pointteri2[tunnus] lˆytyi, unsetataan se!<br><br>";
					unset($tunnus_chk_array["{$pointteri2['tunnus']}"]);
				}
			}

			// keikalla oli muita kuin laskulla esiintyvi‰ rivej‰, tehd‰‰n uusi keikka
			if (count($tunnus_chk_array) > 0) {

				#echo "Keikalla oli muita kuin laskulla esiintyvi‰ rivej‰, tehd‰‰n uusi keikka<br>";
				#echo "<pre>",var_dump($tunnus_chk_array),"</pre>";

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
							comments				= '".t("Saapuminen luotu laskusta")." $osrow[laskunro]',
							tila         			= 'K',
							luontiaika	 			= now(),
							laatija		 			= '$kukarow[kuka]'";
				$insertres = pupe_query($query);
				$keikan_tunnus = mysql_insert_id();
				#echo "<pre>",str_replace("\t", "", $query),"</pre>Keikan tunnus:$keikan_tunnus<br><br>";

				$query  = "UNLOCK TABLE";
				$result = pupe_query($query);

				$keikoilla[0]['uusiotunnus'] = $keikan_tunnus;
			}

			$query = "SELECT laskunro FROM lasku WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$keikoilla[0]['uusiotunnus']}'";
			$saapuminen_nro_res = pupe_query($query);
			$saapuminen_nro_row = mysql_fetch_assoc($saapuminen_nro_res);

			#echo "<font class='message'>Liitetty saapumiseen {$saapuminen_nro_row['laskunro']}</font><br><br>";

			reset($keikoilla);

			$laskun_kulut = array(
				'osto_kulu' => $osrow['osto_kulu'],
				'osto_kulu_alv' => $osrow['osto_kulu_alv'],
				'osto_rahti' => $osrow['osto_rahti'],
				'osto_rahti_alv' => $osrow['osto_rahti_alv'],
				'osto_rivi_kulu' => $osrow['osto_rivi_kulu'],
				'osto_rivi_kulu_alv' => $osrow['osto_rivi_kulu_alv']
			);

			// jos tullaan t‰h‰n kohtaan niin kappalem‰‰r‰t t‰sm‰‰. On jo tarkistettu rivill‰ 166
			// muutetaan hinta ja rivi on ok.
			verkkolasku_luo_liitosotsikko($tunnus, $keikoilla[0]['uusiotunnus'], $laskun_kulut);

			foreach ($keikoilla as $pointteri) {

				$query = "	SELECT hinta, otunnus, perheid, tuoteno, uusiotunnus, suuntalava, ale1, ale2, ale3
							FROM tilausrivi
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND tunnus = '{$pointteri['tunnus']}'";
				$hinta_chk_res = pupe_query($query);
				$hinta_chk_row = mysql_fetch_assoc($hinta_chk_res);

				$update_asn = "	UPDATE asn_sanomat SET
								#tilausrivi = '{$pointteri['tunnus']}',
								keikkarivinhinta = '{$hinta_chk_row['hinta']}',
								ale1 = '{$hinta_chk_row['ale1']}',
								ale2 = '{$hinta_chk_row['ale2']}',
								ale3 = '{$hinta_chk_row['ale3']}',
								status = 'X',
								tuoteno = '{$hinta_chk_row['tuoteno']}'
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tunnus = '{$pointteri['insert_id']}'
								AND laji = 'tec'";
				#echo "Keikoilla<pre>",str_replace("\t", "", $update_asn),"</pre>";
				pupe_query($update_asn);

				if ($hinta_chk_row['perheid'] != 0) {

					$lapsen_lisakululisa = "";

					if (isset($pointteri["lisakulu"]) and trim($pointteri["lisakulu"]) != 0) {
						$lapsen_lisakululisa = " hinta = {$pointteri['lisakulu']} / {$pointteri['kpl']}, ";
					}

					$update = "	UPDATE tilausrivi SET
								{$lapsen_lisakululisa}
								ale1 = 0,
								ale2 = 0,
								ale3 = 0,
								uusiotunnus = '{$keikoilla[0]['uusiotunnus']}'
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND otunnus = '{$hinta_chk_row['otunnus']}'
								AND perheid = '{$hinta_chk_row['perheid']}'
								AND tunnus != '{$hinta_chk_row['perheid']}'";
					pupe_query($update);
				}

				$ale1lisa = (isset($pointteri['ale1']) and trim($pointteri['ale1']) != "" and trim($pointteri['ale1']) != 0) ? " ale1 = '{$pointteri['ale1']}', " : " ale1 = 0, ";
				$ale2lisa = (isset($pointteri['ale2']) and trim($pointteri['ale2']) != "" and trim($pointteri['ale2']) != 0) ? " ale2 = '{$pointteri['ale2']}', " : " ale2 = 0, ";
				$ale3lisa = (isset($pointteri['ale3']) and trim($pointteri['ale3']) != "" and trim($pointteri['ale3']) != 0) ? " ale3 = '{$pointteri['ale3']}', " : " ale3 = 0, ";

				$update = "	UPDATE tilausrivi SET
							hinta = '{$pointteri['hinta']}',
							{$ale1lisa}
							{$ale2lisa}
							{$ale3lisa}
							uusiotunnus = '{$keikoilla[0]['uusiotunnus']}'
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND tunnus = '{$pointteri['tunnus']}'";
				#echo "<pre>",str_replace("\t", "", $update),"</pre>";
				pupe_query($update);

				$update = "	UPDATE asn_sanomat SET
							toim_tuoteno = '{$pointteri['tuoteno']}'
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND tunnus = '{$pointteri['insert_id']}'";
				#echo "<pre>",str_replace("\t", "", $update),"</pre>";
				pupe_query($update);

				if ($hinta_chk_row['suuntalava'] != 0) {

					$query = "	SELECT tunnus
								FROM suuntalavat_saapuminen
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND suuntalava = '{$hinta_chk_row['suuntalava']}'
								AND saapuminen = '{$keikoilla[0]['uusiotunnus']}'";
					$suuntalavat_saapuminen_chk_res = pupe_query($query);

					if (mysql_num_rows($suuntalavat_saapuminen_chk_res) == 0) {

						$query = "	INSERT INTO suuntalavat_saapuminen SET
									yhtio = '{$kukarow['yhtio']}',
									suuntalava = '{$hinta_chk_row['suuntalava']}',
									saapuminen = '{$keikoilla[0]['uusiotunnus']}',
									laatija = '{$kukarow['kuka']}',
									luontiaika = now(),
									muutospvm = now(),
									muuttaja = '{$kukarow['kuka']}'";
						$suuntalava_upd_res = pupe_query($query);
						#echo "<pre>$query</pre>";
					}

					$query = "	SELECT GROUP_CONCAT(saapuminen) as saapumiset
								FROM suuntalavat_saapuminen
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND suuntalava = '{$hinta_chk_row['suuntalava']}'
								AND saapuminen != '{$keikoilla[0]['uusiotunnus']}'";
					$suuntalavat_saapuminen_chk_res = pupe_query($query);
					$suuntalavat_saapuminen_chk_row = mysql_fetch_assoc($suuntalavat_saapuminen_chk_res);

					if ($suuntalavat_saapuminen_chk_row['saapumiset'] != '') {

						$query = "	SELECT uusiotunnus, GROUP_CONCAT(tunnus) as tunnukset
									FROM tilausrivi
									WHERE yhtio = '{$kukarow['yhtio']}'
									AND suuntalava = '{$hinta_chk_row['suuntalava']}'
									AND uusiotunnus IN ({$suuntalavat_saapuminen_chk_row['saapumiset']})
									GROUP BY 1";
						$tilriv_tunnukset_res = pupe_query($query);

						while ($tilriv_tunnukset_row = mysql_fetch_assoc($tilriv_tunnukset_res)) {

							if ($tilriv_tunnukset_row['tunnukset'] != '') {
								$query = "	UPDATE suuntalavat SET
											kaytettavyys = 'L'
											WHERE yhtio = '{$kukarow['yhtio']}'
											AND tunnus = '{$hinta_chk_row['suuntalava']}'";
								$upd_kaytettavyys_res = pupe_query($query);
							}
							else {
								$query = "	DELETE FROM suuntalavat_saapuminen
											WHERE yhtio = '{$kukarow['yhtio']}'
											AND suuntalava = '{$hinta_chk_row['suuntalava']}'
											AND saapuminen = '{$tilriv_tunnukset_row['uusiotunnus']}'";
								$del_res = pupe_query($query);
							}
						}
					}
				}
			}

			foreach ($ostoilla as $pointteri1) {

				$query = "	SELECT hinta, otunnus, perheid, tuoteno, ale1, ale2, ale3, suuntalava
							FROM tilausrivi
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND tunnus = '{$pointteri1['tunnus']}'";
				$hinta_chk_res = pupe_query($query);
				$hinta_chk_row = mysql_fetch_assoc($hinta_chk_res);

				$update_asn = "	UPDATE asn_sanomat SET
								#tilausrivi = '{$pointteri1['tunnus']}',
								keikkarivinhinta = '{$hinta_chk_row['hinta']}',
								ale1 = '{$hinta_chk_row['ale1']}',
								ale2 = '{$hinta_chk_row['ale2']}',
								ale3 = '{$hinta_chk_row['ale3']}',
								status = 'X',
								tuoteno = '{$hinta_chk_row['tuoteno']}'
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tunnus = '{$pointteri1['insert_id']}'
								AND laji = 'tec'";
				#echo "Ostoilla:<pre>",str_replace("\t", "", $update_asn),"</pre>";
				pupe_query($update_asn);

				if ($hinta_chk_row['perheid'] != 0) {

					$lapsen_lisakululisa = "";

					if (isset($pointteri1["lisakulu"]) and trim($pointteri1["lisakulu"]) != 0) {
						$lapsen_lisakululisa = " hinta = {$pointteri1['lisakulu']} / {$pointteri1['kpl']}, ";
					}

					$update = "	UPDATE tilausrivi SET
								{$lapsen_lisakululisa}
								ale1 = 0,
								ale2 = 0,
								ale3 = 0,
								uusiotunnus = '{$keikoilla[0]['uusiotunnus']}'
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND otunnus = '{$hinta_chk_row['otunnus']}'
								AND perheid = '{$hinta_chk_row['perheid']}'
								AND tunnus != '{$hinta_chk_row['perheid']}'";
					pupe_query($update);
				}

				$ale1lisa = (isset($pointteri1['ale1']) and trim($pointteri1['ale1']) != "" and trim($pointteri1['ale1']) != 0) ? " ale1 = '{$pointteri1['ale1']}', " : " ale1 = 0, ";
				$ale2lisa = (isset($pointteri1['ale2']) and trim($pointteri1['ale2']) != "" and trim($pointteri1['ale2']) != 0) ? " ale2 = '{$pointteri1['ale2']}', " : " ale2 = 0, ";
				$ale3lisa = (isset($pointteri1['ale3']) and trim($pointteri1['ale3']) != "" and trim($pointteri1['ale3']) != 0) ? " ale3 = '{$pointteri1['ale3']}', " : " ale3 = 0, ";

				$update = "	UPDATE tilausrivi SET
							hinta = '{$pointteri1['hinta']}',
							{$ale1lisa}
							{$ale2lisa}
							{$ale3lisa}
							uusiotunnus = '{$keikoilla[0]['uusiotunnus']}'
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND tunnus = '{$pointteri1['tunnus']}'";
				#echo "<pre>",str_replace("\t", "", $update),"</pre>";
				pupe_query($update);

				$update = "	UPDATE asn_sanomat SET
							toim_tuoteno = '{$pointteri1['tuoteno']}'
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND tunnus = '{$pointteri1['insert_id']}'";
				#echo "<pre>",str_replace("\t", "", $update),"</pre>";
				pupe_query($update);

				if ($hinta_chk_row['suuntalava'] != 0) {

					$query = "	SELECT tunnus
								FROM suuntalavat_saapuminen
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND suuntalava = '{$hinta_chk_row['suuntalava']}'
								AND saapuminen = '{$keikoilla[0]['uusiotunnus']}'";
					$suuntalavat_saapuminen_chk_res = pupe_query($query);

					if (mysql_num_rows($suuntalavat_saapuminen_chk_res) == 0) {

						$query = "	INSERT INTO suuntalavat_saapuminen SET
									yhtio = '{$kukarow['yhtio']}',
									suuntalava = '{$hinta_chk_row['suuntalava']}',
									saapuminen = '{$keikoilla[0]['uusiotunnus']}',
									laatija = '{$kukarow['kuka']}',
									luontiaika = now(),
									muutospvm = now(),
									muuttaja = '{$kukarow['kuka']}'";
						$suuntalava_upd_res = pupe_query($query);
						#echo "<pre>$query</pre>";
					}

					$query = "	SELECT GROUP_CONCAT(saapuminen) as saapumiset
								FROM suuntalavat_saapuminen
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND suuntalava = '{$hinta_chk_row['suuntalava']}'
								AND saapuminen != '{$keikoilla[0]['uusiotunnus']}'";
					$suuntalavat_saapuminen_chk_res = pupe_query($query);
					$suuntalavat_saapuminen_chk_row = mysql_fetch_assoc($suuntalavat_saapuminen_chk_res);

					if ($suuntalavat_saapuminen_chk_row['saapumiset'] != '') {

						$query = "	SELECT uusiotunnus, GROUP_CONCAT(tunnus) as tunnukset
									FROM tilausrivi
									WHERE yhtio = '{$kukarow['yhtio']}'
									AND suuntalava = '{$hinta_chk_row['suuntalava']}'
									AND uusiotunnus IN ({$suuntalavat_saapuminen_chk_row['saapumiset']})
									GROUP BY 1";
						$tilriv_tunnukset_res = pupe_query($query);

						while ($tilriv_tunnukset_row = mysql_fetch_assoc($tilriv_tunnukset_res)) {

							if ($tilriv_tunnukset_row['tunnukset'] != '') {
								$query = "	UPDATE suuntalavat SET
											kaytettavyys = 'L'
											WHERE yhtio = '{$kukarow['yhtio']}'
											AND tunnus = '{$hinta_chk_row['suuntalava']}'";
								$upd_kaytettavyys_res = pupe_query($query);
							}
							else {
								$query = "	DELETE FROM suuntalavat_saapuminen
											WHERE yhtio = '{$kukarow['yhtio']}'
											AND suuntalava = '{$hinta_chk_row['suuntalava']}'
											AND saapuminen = '{$tilriv_tunnukset_row['uusiotunnus']}'";
								$del_res = pupe_query($query);
							}
						}
					}
				}
			}

			$tee_kululaskut = "liita";
 			$silent = "SILENT";
 			$keikanalatila = "";
 			$laskutunnus = $tunnus;
 			$otunnus = $keikoilla[0]['uusiotunnus'];

			require("tilauskasittely/kululaskut.inc");
		}
		else {
			$virheet++;
			echo "Laskulla oli virheit‰, emme tehneet mit‰‰n.\n";
		}
	}

	// t‰m‰n keikan voi vied‰ saldoille ja laskuttaa
	if ($kohdistettujariveja > 0 and $kauttalaskutus == "kylla") {
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

		$laskuvirhe .= t("Vied‰‰n kauttalaskutus varastoon").". ($laskurow[laskunro])\n";

		$toiminto = "kalkyyli";
		$tee = "varastoon";

		// vied‰‰n varastoon...
		require ("tilauskasittely/varastoon.inc");

		// laskutetaan suoratoimitetut jtrivit jos niit‰ oli t‰ll‰ keikalla...
		if ($kauttalaskutus == "kylla") {
			$toiminto = "kaikkiok";
			$tee = "varma";

			// vied‰‰n varastoon TEHDƒƒN LOPPUKALKYYLI
			require ("tilauskasittely/varastoon.inc");

			require ("tilauskasittely/laskuta_kauttalaskutus.inc");
		}
	}
	elseif ($kohdistettujariveja == 0 and $aladellaa == "") {

		if ((int) $keikkanumeroapu > 0) {
			// dellataan perustettu keikka ku ei kerran lˆytyny rivej‰.. jos ei olla asetettu ƒLƒDELLAA muuttujaaa
			$query = "DELETE from lasku WHERE yhtio='$kukarow[yhtio]' and tila='K' and laskunro='$keikkanumeroapu'";
			$result = pupe_query($query);
		}

		$laskuvirhe .= t("Poistettiin")." ".t("saapuminen")." $keikkanumeroapu ".t("Yht‰‰n rivi‰ ei lˆytynyt")."!\n";
	}
	else {
		$laskuvirhe .= t("Saapuminen k‰sitelty loppuun").": $keikkanumeroapu $trow[nimi]\n";
	}
?>
