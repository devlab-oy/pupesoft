<?php

	# Formaatti on:
	# toimittajanumero "999999"
	# tyhjää x(4)
	# tilausnumero x(13)
	# tuotekoodi x(18)
	# määrä 9999

	$query = "  SELECT tilausrivi.tunnus,
				tilausrivi.otunnus,
				round(tilausrivi.varattu * IF(tuotteen_toimittajat.tuotekerroin = 0, 1, tuotteen_toimittajat.tuotekerroin), 2) AS varattu,
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

		$wabco_editilaus = $pupe_root_polku."/dataout/wabco-order-$laskurow[tunnus].xls";

		if (!$toot = fopen($wabco_editilaus, "w")) die("Filen $wabco_editilaus luonti epaonnistui!");

		while ($tilausrivirow = mysql_fetch_assoc($result)) {

			$out  = sprintf("%-6.6s",   $toimirow['edi_kayttaja']);			// toimittajanro
			$out .= sprintf("%-4.4s",   "");								// tyhjää
			$out .= sprintf("%-13.13s", $tilausrivirow['otunnus']);			// tilausnumero
			$out .= sprintf("%-18.18s", $tilausrivirow['toim_tuoteno']);	// tuotekoodi
			$out .= sprintf("%04d",   round($tilausrivirow['varattu']));	// määrä
			fwrite($toot, $out . "\n");
		}

		fclose($toot);

		echo "<table>";
		echo "<tr><th>".t("Tallenna ostotilaus").":</th>";
		echo "<form method='post' action='{$palvelin2}tilauskasittely/tilaus_osto.php'>";
		echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
		echo "<input type='hidden' name='kaunisnimi' value='{$laskurow["tunnus"]}.txt'>";
		echo "<input type='hidden' name='filenimi' value='$wabco_editilaus'>";
		echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
		echo "</table><br>";
	}

	// Nollataan tarkoituksella lopetusmuuttuja
	$lopetus = "";

	$wabco_editilaus = "";
	$out = "";

?>