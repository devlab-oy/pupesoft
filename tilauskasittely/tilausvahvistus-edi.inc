<?php

// tarvitaan $laskurow array
// tarvitaan $yhtiorow array

if ($kukarow['extranet'] == '') {
	$tilvahvfilenimi = "../dataout/tilvahvi-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(),true)).".txt";
}
else {
	$tilvahvfilenimi = "dataout/tilvahvi-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(),true)).".txt";
}

if (!file_exists($tilvahvfilenimi)) {

	if (!$toot = fopen($tilvahvfilenimi, "w")) die("TILAUSVAHVISTUS: Tiedoston luonti ei onnistu!");

	if ($yhtiorow["tilausvahvistus_lahetys"] == 1 and $laskurow["vanhatunnus"] > 0) {
		$query = " 	SELECT group_concat(tunnus) tunnukset
					FROM lasku use index (yhtio_vanhatunnus)
					WHERE yhtio		= '$kukarow[yhtio]'
					and vanhatunnus = '$laskurow[vanhatunnus]'
					and tila IN ('N','L','A')";
		$abures = mysql_query($query) or pupe_error($query);
		$aburow = mysql_fetch_assoc($abures);

		if ($aburow["tunnukset"] != "") {
			$wherelisa = " tilausrivi.otunnus IN ($aburow[tunnukset])";
			$laskurow["nippu"] = $aburow["tunnukset"];
		}
		else {
			$wherelisa = "tilausrivi.otunnus = '$laskurow[tunnus]'";
			$laskurow["nippu"] = $laskurow["tunnus"];
		}
	}
	elseif ($laskurow["tunnusnippu"] > 0 and $laskurow["clearing"] != "JT-TILAUS") {
		$query = "  SELECT group_concat(tunnus) tunnukset
					FROM lasku
					WHERE yhtio 	= '$kukarow[yhtio]'
					and tunnusnippu = '$laskurow[tunnusnippu]'
					and tila IN ('N','L','A')";
		$abures = mysql_query($query) or pupe_error($query);
		$aburow = mysql_fetch_assoc($abures);

		if ($aburow["tunnukset"] != "") {
			$wherelisa = " tilausrivi.otunnus IN ($aburow[tunnukset])";
			$laskurow["nippu"] = $aburow["tunnukset"];
		}
		else {
			$wherelisa = "tilausrivi.otunnus = '$laskurow[tunnus]'";
			$laskurow["nippu"] = $laskurow["tunnus"];
		}
	}
	else {
		$wherelisa = "tilausrivi.otunnus = '$laskurow[tunnus]'";
		$laskurow["nippu"] = $laskurow["tunnus"];
	}

	$ale_query_select_lisa = '';

	for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
		$ale_query_select_lisa .= " tilausrivi.ale{$alepostfix},";
	}

	if ($laskurow["tila"] == "L" or $laskurow["tila"] == "N") {
		$tyyppilisa = " and tilausrivi.tyyppi in ('L') ";
	}
	else {
		$tyyppilisa = " and tilausrivi.tyyppi in ('L','G','W','E','T') ";
	}

	$query = "	SELECT tilausrivi.tunnus, tuote.eankoodi,
				tilausrivi.tuoteno, tilausrivi.varattu, tilausrivi.toimaika, tilausrivi.kommentti, tilausrivi.tilkpl, tilausrivi.alv, tilausrivi.nimitys,
				tilausrivi.hinta, {$ale_query_select_lisa} tilausrivi.kommentti, tilausrivi.laatija, tilausrivi.tilaajanrivinro, tilausrivi.yksikko
				FROM tilausrivi
				LEFT JOIN tuote USING (yhtio, tuoteno)
				WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
				$tyyppilisa
				and $wherelisa
				ORDER BY tilausrivi.tunnus";
	$tilvahresult = mysql_query($query) or pupe_error($query);

	//Myyjän email
	$query = "SELECT * FROM kuka WHERE tunnus='$laskurow[myyja]' and yhtio='$kukarow[yhtio]'";
	$kukaresult = mysql_query($query) or pupe_error($query);
	$myyjarow = mysql_fetch_array($kukaresult);

	//Maskuehto
	$query = "SELECT * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
	$xresult = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($xresult) == 0) {
		echo "TILAUSVAHVISTUS: Maksuehto on kateissa!<br><br>";
	}
	else {
		$mehtorow = mysql_fetch_array($xresult);
	}

	if (mysql_num_rows($tilvahresult) > 0) {

		$ulos  = "ICHGSTART:" . $yhtiorow['yhtio'] . $laskurow['tunnus'] . "\n";
		$ulos .= "ICHG_TYPE:POS\n";
		$ulos .= "RMSGSTART:" . $yhtiorow['yhtio'] . $laskurow['tunnus'] . "\n";
		$ulos .= "RHDRSTART:" . $yhtiorow['yhtio'] . $laskurow['tunnus'] . "\n";
		$ulos .= "RBGMITYPE:231\n";
		$ulos .= "RBGMINUMB:" . $laskurow['tunnus'] . "\n";
		$ulos .= "RBGMIACKR:NA\n";
		$ulos .= "RDTM4__DT:" . tv2dateconv($laskurow['luontiaika']) . "\n";
		$ulos .= "RRFFCR_NU:" . tv2dateconv($laskurow['luontiaika']) . "\n";
		$ulos .= "RRFFVN_NU:" . $laskurow['tunnus'] . "\n";
		$ulos .= "RRFFCO_NU:" . $laskurow['asiakkaan_tilausnumero'] . "\n";
		$ulos .= "RFTXGENTX:" . $laskurow['viesti'] . "\n";
		$ulos .= "RFTXGENTX:" . $laskurow['comments'] . "\n";
		$ulos .= "RNADBY_PC:" . sprintf ("%-18.18s", $laskurow['ovttunnus']) . "100 91"."\n";
		$ulos .= "RNADBY_RF:" . "IT /".preg_replace("/[^0-9]/", "", $laskurow['ytunnus'])."\n";
		$ulos .= "RNADBY_NA:" . $laskurow['nimi'] . "\n";
		$ulos .= "RNADBY_NX:" . $laskurow['nimitark'] . "\n";
		$ulos .= "RNADBY_SA:" . $laskurow['osoite'] . "\n";
		$ulos .= "RNADBY_PO:" . $laskurow['postino'] . "\n";
		$ulos .= "RNADBY_CI:" . $laskurow['postitp'] . "\n";
		$ulos .= "RNADBY_CN:" . $laskurow['maa'] . "\n";
		#$ulos .= "RNADBY_DO:" . $laskurow['tilausvahvistus'] . "\n"; // mimmosen tilausvahvistuksen asiakas haluaa... F = fax, E = edi
		$ulos .= "RNADBY_DO:E\n"; // mimmosen tilausvahvistuksen asiakas haluaa... aina edi ni ei tule ongelmia
		$ulos .= "RNADDP_RF:" . "ZZ /".preg_replace("/[^0-9]/", "", $laskurow['ytunnus'])."\n";
		// jos tyhjää ni ei saa lähettäää.. weird.
		if (trim($laskurow['toim_ovttunnus']) != '') {
			$ulos .= "RNADDP_PC:".sprintf ("%-18.18s", $laskurow['toim_ovttunnus']) . "100 91". "\n";
		}
		$ulos .= "RNADDP_NA:" . $laskurow['toim_nimi'] . "\n";
		$ulos .= "RNADDP_NX:" . $laskurow['toim_nimitark'] . "\n";
		$ulos .= "RNADDP_SA:" . $laskurow['toim_osoite'] . "\n";
		$ulos .= "RNADDP_PO:" . $laskurow['toim_postino'] . "\n";
		$ulos .= "RNADDP_CI:" . $laskurow['toim_postitp'] . "\n";
		$ulos .= "RNADDP_CN:" . $laskurow['toim_maa'] . "\n";

		$ulos .= "RNADSE_RF:" . "VA  ".$yhtiorow['maa'] . "-" . preg_replace("/[^0-9]/", "", $yhtiorow['ytunnus'])."\n";
		$ulos .= "RNADSE_PC:" . sprintf ("%-18.18s", $yhtiorow['ovttunnus']) . "100 91". "\n";
		$ulos .= "RNADSE_NA:" . $yhtiorow['nimi'] . "\n";
		$ulos .= "RNADSE_SA:" . $yhtiorow['osoite'] . "\n";
		$ulos .= "RNADSE_PO:" . $yhtiorow['postino'] . "\n";
		$ulos .= "RNADSE_CI:" . $yhtiorow['postitp'] . "\n";
		$ulos .= "RNADSE_CN:" . $yhtiorow['maa'] . "\n";
		$ulos .= "RNADSE_CC:" . "EB @" . $myyjarow['kuka']. "\n";
		$ulos .= "RNADSE_EM:" . "SR @" . $myyjarow['eposti'] . "\n";

		$ulos .= "RPAT1__TP:" . $mehtorow['teksti'] . "\n";
		$ulos .= "RPAT1__PC:" . $mehtorow['kassa_alepros'] . "\n";
		$ulos .= "RPAT2__TP:" . $mehtorow['teksti'] . "\n";
		$ulos .= "RPAT2__PC:" . sprintf ("%010.2f", (float) $yhtiorow['viivastyskorko']) . " 15\n";

		$ulos .= "RCUX5__CR:" . $laskurow['valkoodi'] . "\n";
		$ulos .= "RTDT20_TT:" . "ZNN " . $laskurow['toimitustapa'] . "\n";
		$ulos .= "RHDR__END:" . $yhtiorow['yhtio'] . $laskurow['tunnus'] . "\n";
		fputs($toot,$ulos);

		$rivino = 1; // dummy rivino, kun elma ei handlaa pitkiä

		while ($tvtilausrivirow = mysql_fetch_array($tilvahresult)) {
			if ($tvtilausrivirow['yksikko'] == '') $tvtilausrivirow['yksikko'] = "PCE";
			if ($tvtilausrivirow['eankoodi'] == 0) $tvtilausrivirow['eankoodi'] = '';

			$ulos  = "RLINSTART:" . $rivino . "\n";
			$ulos .= "RLINABORF:                                   @ " . $tvtilausrivirow['tilaajanrivinro'] . "\n"; //Futursoftin tilauksen rivinumero
			$ulos .= "RLINSA_NU:" . $tvtilausrivirow['tuoteno'] . "\n";
			$ulos .= "RLINEN_NU:" . $tvtilausrivirow['eankoodi'] . "\n";
			$ulos .= "RLIN8__IF:" . t_tuotteen_avainsanat($tvtilausrivirow, 'nimitys', $kieli) . "\n";

			$laskurow['valkoodi'] = sprintf("%-3.s",$laskurow['valkoodi']);

			$ulos .= "RLINCONMA:" . sprintf("%014.2f", (float) $tvtilausrivirow['hinta']) . $laskurow['valkoodi'] ."  ". $tvtilausrivirow['yksikko'] . "\n";
			$ulos .= "RLIN21_QT:" . sprintf("%014.2f", (float) $tvtilausrivirow['tilkpl']) .  "     " . $tvtilausrivirow['yksikko'] . "\n";
			$ulos .= "RLIN113QT:" . sprintf("%014.2f", (float) $tvtilausrivirow['varattu']) . "     " . $tvtilausrivirow['yksikko'] . "\n";
			$ulos .= "RLIN___PA:" . sprintf("%07.2f",  (float) $tvtilausrivirow['ale1']) . "\n";
			$ulos .= "RLIN2__DT:" . tv2dateconv($tvtilausrivirow['toimaika']) . "\n";
			$ulos .= "RLINVATTX:" . $tvtilausrivirow['alv'] . " S" . "\n";
			$ulos .= "RLINLINFC:" . str_replace("\n", " ", $tvtilausrivirow['kommentti']) . "\n";
			//$ulos .= "RLINLINFC:" . $tvtilausrivirow['kommentti'] . "\n"; // Jälkitoimitusrivitiedot
			$ulos .= "RLIN__END:" . $rivino . "\n";
			fputs($toot,$ulos);
			$rivino++;
		}
		$ulos  = "RMSG__END:" . $yhtiorow['yhtio'] . $laskurow['tunnus'] . "\n";
		$ulos .= "ICHG__END:" . $yhtiorow['yhtio'] . $laskurow['tunnus'] . "\n";
		fputs($toot,$ulos);
		fclose ($toot);

		// tarvitaan  $ftphost $ftpuser $ftppass $ftppath $ftpfile
		// palautetaan $palautus ja $syy

		$ftphost = $tilvahhost;
		$ftpuser = $tilvahuser;
		$ftppass = $tilvahpass;
		$ftppath = $tilvahpath;
		$ftpfile = realpath($tilvahvfilenimi);

		if ($kukarow["extranet"] == '') {
			require ("../inc/ftp-send.inc");
		}
		else {
			require ("ftp-send.inc");
		}
	}
}

if (strpos($_SERVER['SCRIPT_NAME'], "tulostakopio.php") === FALSE and $yhtiorow['tilausvahvistus_tallenna'] == 'K') {
	$tilausvahvistus_tallenna = "/tmp/Tilausvahvistus-".md5(uniqid(mt_rand(), true)).".txt";
	file_put_contents($tilausvahvistus_tallenna, $ulos);
}
else {
	$tilausvahvistus_tallenna = "";
}

$ulos = "";

?>