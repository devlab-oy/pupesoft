<?php

	# Formaatti on:
	# toimittajanumero "999999"
	# tyhjää x(4)
	# tilausnumero x(13)
	# tuotekoodi x(18)
	# määrä 9999

	$wabco_editilaus = $pupe_root_polku."/dataout/wabco-order-$kukarow[yhtio]-$laskurow[tunnus]-".date("Ymd")."-".md5(uniqid(rand(),true)).".txt";

	echo "$wabco_editilaus<br>";

	if (!$toot = fopen($wabco_editilaus, "w")) die("Filen $wabco_editilaus luonti epaonnistui!");

	$query = "  SELECT tilausrivi.tunnus,
				tilausrivi.otunnus,
				tilausrivi.varattu,
				tilausrivi.yksikko,
				ifnull(tuotteen_toimittajat.toim_tuoteno, tilausrivi.tuoteno) toim_tuoteno
				FROM tilausrivi
				JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio AND tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno AND tuotteen_toimittajat.liitostunnus = '$laskurow[liitostunnus]')
				WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
				AND tilausrivi.tyyppi  = 'O'
				AND tilausrivi.otunnus = '$laskurow[tunnus]'
				AND tilausrivi.varattu > 0
				ORDER BY tilausrivi.tunnus";
	$result = pupe_query($query);

	if (mysql_num_rows($result) == 0) {
		echo "<font class='error'>".t("Lähetettäviä tilausrivejä ei löydy")."</font>";
	}
	else {

		while ($tilausrivirow = mysql_fetch_assoc($result)) {

			$out  = sprintf("%-6.6s",   $toimirow["toimittajanro"]);		// toimittajanro
			$out .= sprintf("%-4.4s",   "");								// tyhjää
			$out .= sprintf("%-13.13s", $tilausrivirow['otunnus']);			// tilausnumero
			$out .= sprintf("%-18.18s", $tilausrivirow['toim_tuoteno']);	// tuotekoodi
			$out .= sprintf("%04d",   round($tilausrivirow['varattu']));	// määrä
			fwrite($toot, $out . "\n");
		}

		fclose($toot);

		// tarvitaan  $ftphost $ftpuser $ftppass $ftppath $ftpfile
		// palautetaan $palautus ja $syy
		$ftphost = $toimirow['edi_palvelin'];
		$ftpuser = $toimirow['edi_kayttaja'];
		$ftppass = $toimirow['edi_salasana'];
		$ftppath = $toimirow['edi_polku'];
		$ftpfile = realpath($wabco_editilaus);

		// Portti
		if (isset($toimirow['edi_portti'])) $ftpport = $toimirow['edi_portti'];

		require ("inc/ftp-send.inc");

		if ($palautus == 0) {
			echo "<font class='message'>".t("Tilaus lähetettiin onnistuneesti")."!</font><br>";

			$sqlquery = "	UPDATE lasku
							SET kirjoitin = 'edi'
							WHERE yhtio   = '$kukarow[yhtio]'
							AND tunnus	  = '$laskurow[tunnus]'";
			$result = pupe_query($sqlquery);
		}
		else {
			echo "<font class='error'>".t("Tilauksen lähetys epäonnistui")."!</font><br>";
		}
	}

	// Nollataan tarkoituksella lopetusmuuttuja
	$lopetus = "";

	$wabco_editilaus = "";
	$out = "";

?>