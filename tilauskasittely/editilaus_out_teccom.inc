<?php

	$teccom_editilaus = $pupe_root_polku."/dataout/tc-".$laskurow['tunnus'].".txt";

	if (!$toot = fopen($teccom_editilaus, "w")) die("Filen $teccom_editilaus luonti epaonnistui!");

	$query = "  SELECT tilausrivi.tunnus,
				tilausrivi.yksikko,
				round(tilausrivi.varattu * IF(tuotteen_toimittajat.tuotekerroin = 0, 1, tuotteen_toimittajat.tuotekerroin), 2) AS varattu,
				ifnull(tuotteen_toimittajat.toim_tuoteno, tilausrivi.tuoteno) toim_tuoteno
				FROM tilausrivi
				JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio
				AND tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno
				AND tuotteen_toimittajat.liitostunnus = '$laskurow[liitostunnus]')
				WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
				AND tilausrivi.tyyppi  = 'O'
				AND tilausrivi.otunnus = '$laskurow[tunnus]'
				AND tilausrivi.varattu > 0
				ORDER BY tilausrivi.tunnus";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 0) {
		echo "<font class='error'>".t("Lähetettäviä tilausrivejä ei löydy")."</font>";
	}
	else {

		if ($laskurow['tilaustyyppi'] == '' or $laskurow['tilaustyyppi'] == '0') {
			$laskurow['tilaustyyppi'] = '2';
		}

		$tec_toimtapa = ($laskurow['tilaustyyppi'] == '1') ? '0003' : '';

		// Otsikkoa
		$out = sprintf("%-10.10s", "000ORDH");						// VAKIOALKU 10 merkkiä
		$out .= sprintf("%-9.9s", $toimirow['toimittajanro']);		// Toimittajan numero 9 merkkiä
		$out .= sprintf("%-20.20s", $laskurow['nimi']);				// Toimittajan nimi 20 merkkiä
		$out .= sprintf("%-20.20s", $yhtiorow['nimi']);				// Lähettäjän nimi 20 merkkiä
		$out .= sprintf("%06.6s", substr($laskurow['tunnus'], -6));	// Ostotilauksen numero 6 merkkiä
		$out .= sprintf("%4.4s", $tec_toimtapa);					// Toimitustapa 4 merkkiä
		$out .= sprintf("%-1.1s", $laskurow['tilaustyyppi']);		// Tilaustapa, 1 = Pikatilaus, 2 = Normaalitilaus, (3 = Yölähetys, ei tuettu)
		fwrite($toot, $out . "\n");

		$lask = 1;

		while ($tilausrivirow = mysql_fetch_array($result)) {
			$out = sprintf("%-7.7s", "000ORDI");							// VAKIOALKU 7 merkkiä
			$out .= sprintf("%03.3s", $lask);								// Tilausrivin numero 3 merkkiä
			$out .= sprintf("%-22.22s", $tilausrivirow['toim_tuoteno']);	// Toimittajan Tuoteno 22 merkkiä
			$out .= sprintf("%-6.6s", $toimirow['toimittajanro']);			// toimittajan numero 6 merkkiä
			$out .= sprintf("%05.5s", round($tilausrivirow['varattu']));	// tilausmäärä 5 merkkiä
			$out .= sprintf("%2.2s", "01");						 			// yksikkö 2 merkkiä
			fwrite($toot, $out . "\n");

			$lask++;
		}

		fclose($toot);

		// tarvitaan  $ftphost $ftpuser $ftppass $ftppath $ftpfile
		// palautetaan $palautus ja $syy
		$ftphost = $toimirow['edi_palvelin'];
		$ftpuser = $toimirow['edi_kayttaja'];
		$ftppass = $toimirow['edi_salasana'];
		$ftppath = $toimirow['edi_polku'];
		$ftpfile = realpath($teccom_editilaus);

		// Portti
		if (isset($toimirow['edi_portti'])) $ftpport = $toimirow['edi_portti'];

		require ("inc/ftp-send.inc");

		if ($palautus == 0) {
			echo "<font class='message'>".t("Tilaus lähetettiin onnistuneesti")."!</font><br>";

			//päivitetään lisäksi aina teccom-ostotilauksissa tilausrivi.tunnus tilausrivi.tilaajanrivinro:ksi,
			//jotta sitten teccom asn/ostolaskuissa osataan pointata oikeaan riviin, ja handlataan tilanne, missä rivi voi splittaantua jne (rivinumero on tallessa!)
			mysql_data_seek($result, 0);

			$lask = 1;

			while ($tilausrivirow = mysql_fetch_assoc($result)) {
				$sqlquery = "	UPDATE tilausrivi SET
								tilaajanrivinro = $lask
								WHERE yhtio = '$kukarow[yhtio]'
								AND tunnus  = '$tilausrivirow[tunnus]'";
				$tresult = pupe_query($sqlquery, $masterlink);

				$lask++;
			}

			$sqlquery = "	UPDATE lasku
							SET kirjoitin = 'edi'
							WHERE yhtio = '$kukarow[yhtio]'
							AND tunnus  = '$laskurow[tunnus]'";
			$result = pupe_query($sqlquery, $masterlink);
		}
		else {
			echo "<font class='error'>".t("Tilauksen lähetys epäonnistui")."!</font><br>";
		}
	}

	// Nollataan tarkoituksella lopetusmuuttuja
	$lopetus 		  = "";
	$teccom_editilaus = "";
	$out 			  = "";
