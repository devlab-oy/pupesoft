<?php

	$teccom_editilaus = "../dataout/teccom-order-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(),true)).".txt";

	if (!$toot = fopen($teccom_editilaus, "w")) die("Filen $teccom_editilaus luonti epaonnistui!");

	$query = "  SELECT tilausrivi.tunnus,
				tilausrivi.varattu,
				tilausrivi.yksikko,
				ifnull(tuotteen_toimittajat.toim_tuoteno, tilausrivi.tuoteno) toim_tuoteno
				FROM tilausrivi 
				LEFT JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio 
					AND tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno 
					AND tuotteen_toimittajat.liitostunnus = '$laskurow[liitostunnus]')
				WHERE tilausrivi.yhtio = '$kukarow[yhtio]' 
				AND tilausrivi.tyyppi = 'O'
				AND otunnus = '$laskurow[tunnus]'
				AND varattu > 0
				ORDER BY tunnus";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 0) {
		echo "<font class='error'>".t("Lähetettäviä tilausrivejä ei löydy")."</font>";
	}
	else {

		// Otsikkoa
		$out = sprintf("%-10.10s", "000ORDH");						// VAKIOALKU
		$out .= sprintf("%-9.9s", $toimirow['toimittajanro']);		// Toimittajan numero 6 merkkiä
		$out .= sprintf("%-20.20s", $laskurow['nimi']);				// Toimittajan nimi 20 merkkiä
		$out .= sprintf("%-20.20s", $yhtiorow['nimi']);				// Lähettäjän nimi 20 merkkiä
		$out .= sprintf("%08.8s", $laskurow['tunnus']);				// Ostotilauksen numero 8 merkkiä
		$out .= sprintf("%4.4s", "");								// Toimitustapa 4 merkkiä, ei käytössä
		$out .= sprintf("%-1.1s", "2");								// Tilaustapa, 1 = Pikatilaus, 2 = Normaalitilaus
		fwrite($toot, $out . "\n");
	
		while ($tilausrivirow = mysql_fetch_array($result)) {	
			$out = sprintf("%-7.7s", "000ORDI");							// VAKIOALKU
			$out .= sprintf("%-11.11s", $tilausrivirow['tunnus']);			// Tilausrivin numero 11 merkkiä
			$out .= sprintf("%-60.60s", $tilausrivirow['toim_tuoteno']);	// Toimittajan Tuoteno 60 merkkiä
			$out .= sprintf("%-6.6s", $toimirow['toimittajanro']);			// toimittajan numero 6 merkkiä
			$out .= sprintf("%05.0f", $tilausrivirow['varattu']);			// tilausmäärä 5 merkkiä
			$out .= sprintf("%03.3s",$tilausrivirow['yksikko']); 			// yksikkö 3 merkkiä
			fwrite($toot, $out . "\n"); 
		}

		fclose ($toot);

		// tarvitaan  $ftphost $ftpuser $ftppass $ftppath $ftpfile
		// palautetaan $palautus ja $syy
		$ftphost = $toimirow['edi_palvelin'];
		$ftpuser = $toimirow['edi_kayttaja'];
		$ftppass = $toimirow['edi_salasana'];
		$ftppath = $toimirow['edi_polku'];
		$ftpfile = realpath($teccom_editilaus);

		require ("inc/ftp-send.inc");

		if ($palautus == 0) {
			echo "<font class='message'>".t("Tilaus lähetettiin onnistuneesti")."!</font><br>";
			$sqlquery = "	UPDATE lasku SET 
							kirjoitin = 'edi' 
							WHERE yhtio = '$kukarow[yhtio]' 
							AND tunnus = '$laskurow[tunnus]'";
			$result = mysql_query($sqlquery) or pupe_error($sqlquery);
		}

	}

	$teccom_editilaus = "";
	$out = "";

?>