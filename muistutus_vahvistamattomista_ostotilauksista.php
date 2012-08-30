<?php
	//* Tämä skripti käyttää slave-tietokantapalvelinta *//
	$useslave = 1;

	// Kutsutaanko CLI:stä
	if (php_sapi_name() != 'cli') {
		die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
	}

	if (isset($argv[1]) and trim($argv[1]) != '') {

		// otetaan tietokanta connect
		require ("inc/connect.inc");
		require ("inc/functions.inc");

		// hmm.. jännää
		$kukarow['yhtio'] = $argv[1];

		$query    = "SELECT * from yhtio where yhtio='$kukarow[yhtio]'";
		$yhtiores = pupe_query($query);

		if (mysql_num_rows($yhtiores) == 1) {
			$yhtiorow = mysql_fetch_array($yhtiores);

			// haetaan yhtiön parametrit
			$query = "	SELECT *
						FROM yhtion_parametrit
						WHERE yhtio='$yhtiorow[yhtio]'";
			$result = mysql_query($query) or die ("Kysely ei onnistu yhtio $query");

			if (mysql_num_rows($result) == 1) {
				$yhtion_parametritrow = mysql_fetch_array($result);
				// lisätään kaikki yhtiorow arrayseen, niin ollaan taaksepäinyhteensopivia
				foreach ($yhtion_parametritrow as $parametrit_nimi => $parametrit_arvo) {
					$yhtiorow[$parametrit_nimi] = $parametrit_arvo;
				}
			}
		}
		else {
			die ("Yhtiö $kukarow[yhtio] ei löydy!");
		}

		$query = "	SELECT tilausrivi.otunnus, lasku.laatija, kuka.eposti, lasku.nimi, lasku.ytunnus, vastuuostaja.eposti AS vo_eposti, COUNT(*) kpl
					FROM tilausrivi
					JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus and lasku.tila = 'O' and lasku.alatila != '')
					JOIN kuka ON (kuka.yhtio = tilausrivi.yhtio and kuka.kuka = lasku.laatija)
					JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
					JOIN kuka AS vastuuostaja ON (vastuuostaja.yhtio = tuote.yhtio AND vastuuostaja.myyja = tuote.ostajanro)
					WHERE lasku.yhtio = '$kukarow[yhtio]'
					AND tilausrivi.toimitettu = ''
					AND tilausrivi.tyyppi = 'O'
					AND tilausrivi.kpl = 0
					and tilausrivi.varattu != 0
					AND tilausrivi.jaksotettu = 0
					AND lasku.lahetepvm < SUBDATE(CURDATE(), INTERVAL 5 DAY)
					AND kuka.eposti != ''
					GROUP BY 1,2,3,4,5,6
					ORDER BY lasku.laatija";
		$result = pupe_query($query);

		$veposti = $meili = $vastuuostajaposti = "";

		while ($trow = mysql_fetch_array($result)) {

			if ($trow['eposti'] != $veposti and $veposti != "") {
				$meili = t("Sinulla on vahvistamatta seuraavien ostotilauksien rivit:").":\n\n" . $meili;
				$tulos = mail($veposti, mb_encode_mimeheader(t("Muistutus vahvistamattomista ostotilausriveistä"), "ISO-8859-1", "Q"), $meili, "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");
				$meili = "";
			}

			// setataan sähköpostimuuttuja
			$veposti = $trow['eposti'];
			$vastuuostajaposti = $trow['vo_eposti'];

			$meili .= "Ostotilaus: " . $trow['otunnus'] . "\n";
			$meili .= "Toimittaja: " . $trow['nimi'] . "\n";
			$meili .= "Vahvistamattomia rivejä: " . $trow['kpl'] . "\n\n";
		}

		if ($meili != '') {
			$meili = t("Sinulla on vahvistamatta seuraavien ostotilauksien rivit").":\n\n" . $meili;
			$tulos = mail($veposti, mb_encode_mimeheader(t("Muistutus vahvistamattomista ostotilausriveistä"), "ISO-8859-1", "Q"), $meili, "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");

			if ($vastuuostajaposti != '') {
				$meili = t("Vastuuostajalle ilmoitus vahvistamatta olevista ostotilauksien riveistä").":\n\n" . $meili;
				$tulos = mail($vastuuostajaposti, mb_encode_mimeheader(t("Muistutus vahvistamattomista ostotilausriveistä"), "ISO-8859-1", "Q"), $meili, "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");
			}
		}
	}
