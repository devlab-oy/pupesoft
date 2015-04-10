<?php

 // tarvitaan $laskurow array
// tarvitaan $yhtiorow array

if ($kukarow['extranet'] == '') {
	$tilvahvfilenimi = "../dataout/tilvahv_".$kukarow["yhtio"]."_".$laskurow["tunnus"].".dat";
}
else {
	$tilvahvfilenimi = "dataout/tilvahv_".$kukarow["yhtio"]."_".$laskurow["tunnus"].".dat";
}

if (!file_exists($tilvahvfilenimi)) {

	if (!$toot = fopen($tilvahvfilenimi, "w")) die("TILAUSVAHVISTUS: Tiedoston luonti ei onnistu!");

	if ($yhtiorow["tilausvahvistus_lahetys"] == 1 and $laskurow["vanhatunnus"] > 0) {
		$query = " 	SELECT group_concat(tunnus) tunnukset
					FROM lasku use index (yhtio_vanhatunnus)
					WHERE yhtio		= '$kukarow[yhtio]'
					and vanhatunnus = '$laskurow[vanhatunnus]'
					and tila IN ('N','L','A')";
		$abures = pupe_query($query);
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
		$abures = pupe_query($query);
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

	$query = "	SELECT tilausrivi.tunnus, tuote.eankoodi, tilausrivi.tuoteno,
				if(tilausrivi.var not in ('P','J','S'), tilausrivi.varattu, 0) varattu,
				if(tilausrivi.var in ('J','S'), tilausrivi.jt+tilausrivi.varattu , 0) jt,
				tilausrivi.toimaika, tilausrivi.tilkpl, tilausrivi.alv,
				tilausrivi.hinta, {$ale_query_select_lisa} tilausrivi.kommentti, tilausrivi.laatija, tilausrivi.tilaajanrivinro
				FROM tilausrivi
				LEFT JOIN tuote USING (yhtio, tuoteno)
				WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
				$tyyppilisa
				and $wherelisa
				and tilausrivi.tuoteno != '{$yhtiorow['ennakkomaksu_tuotenumero']}'
				ORDER BY tilausrivi.tunnus";
	$tilvahresult = pupe_query($query);

	if (mysql_num_rows($tilvahresult) > 0) {

		$ulos = "*IS\n";
		$ulos .= "*MS\n";
		$ulos .= "*RS TILVAHV " . $laskurow['tunnus'] . "\n";
		$ulos .= "TILVAHV.TLV_NRO:" . $laskurow['tunnus'] . "\n";
		$ulos .= "TILVAHV.TLV_AS_TILAUSNRO:" . $laskurow['asiakkaan_tilausnumero'] . "\n";
		$ulos .= "TILVAHV.TLV_TOIM_TILAUSNRO:" . $laskurow['tunnus'] . "\n";
		$ulos .= "TILVAHV.TLV_PVM:" . tv1dateconv($laskurow['luontiaika']) . "\n";
		$ulos .= "TILVAHV.TLV_KLO:" . substr($laskurow['luontiaika'],-8,8) . "\n";
		$ulos .= "TILVAHV.TLV_TILAAJA_OVT:" . $laskurow['ovttunnus'] . "\n";
		$ulos .= "TILVAHV.TLV_MYYJA_OVT:" . $yhtiorow['ovttunnus'] . "\n";
		$ulos .= "TILVAHV.TLV_VIITE:" . $laskurow['viesti'] . "\n";
		$ulos .= "TILVAHV.TLV_MERKKI:" . $laskurow['comments'] . "\n";

		$ulos .= "TILVAHV.TLV_MYYJA_NIMI:" . $yhtiorow['nimi'] . "\n";
		$ulos .= "TILVAHV.TLV_MYYJA_KATUOSOITE:" . $yhtiorow['osoite'] . "\n";
		$ulos .= "TILVAHV.TLV_MYYJA_POSTIOSOITE:" . $yhtiorow['postino'] . " " . $yhtiorow['postitp'] . "\n";

		$ulos .= "TILVAHV.TLV_LASK_NIMI:" . $laskurow['nimi'] . " ". $laskurow['nimitark'] . "\n";
		$ulos .= "TILVAHV.TLV_LASK_KATUOSOITE:" . $laskurow['osoite'] . "\n";
		$ulos .= "TILVAHV.TLV_LASK_POSTIOSOITE:" . $laskurow['postino'] . " " . $laskurow['postitp'] . "\n";

		$ulos .= "TILVAHV.TLV_TOIM_NIMI:" . $laskurow['toim_nimi'] . " " . $laskurow['toim_nimitark'] . "\n";
		$ulos .= "TILVAHV.TLV_TOIM_KATUOSOITE:" . $laskurow['toim_osoite'] . "\n";
		$ulos .= "TILVAHV.TLV_TOIM_POSTIOSOITE:" . $laskurow['toim_postino'] . " " . $laskurow['toim_postitp'] . "\n";

		$ulos .= "TILVAHV.TLV_TOIMITUSTAPA:".t_tunnus_avainsanat($laskurow['toimitustapa'], "selite", "TOIMTAPAKV", $kieli)."\n";
		$ulos .= "TILVAHV.TLV_SUMMA:" . $laskurow['summa'] . "\n";
		$ulos .= "TILVAHV.TLV_SISALTOKOODI:29\n";
		$ulos .= "*RE TILVAHV " . $laskurow['tunnus'] . "\n";

		$rivino = 1; // dummy rivino, kun elma ei handlaa pitkiä

		while ($tvtilausrivirow = mysql_fetch_array($tilvahresult)) {

			if ($tvtilausrivirow['yksikko'] == '') $tvtilausrivirow['yksikko'] = "PCE";
			if ($tvtilausrivirow['eankoodi'] == 0) $tvtilausrivirow['eankoodi'] = '';

			$ulos .= "*RS TILVAHVRIV "            . $rivino . "\n";
			$ulos .= "TILVAHVRIV.TVR_NRO:"        . $laskurow['tunnus'] . "\n";
			$ulos .= "TILVAHVRIV.TVR_RIVINRO:"    . $rivino . "\n";
			$ulos .= "TILVAHVRIV.TVR_TIL_RIVINRO:". $tvtilausrivirow['tilaajanrivinro'] . "\n";
			$ulos .= "TILVAHVRIV.TVR_TILAUSNRO:"  . $laskurow['asiakkaan_tilausnumero'] . "\n";
			$ulos .= "TILVAHVRIV.TVR_TUOTEKOODI:" . $tvtilausrivirow['tuoteno'] . "\n";
			$ulos .= "TILVAHVRIV.TVR_TILATTUKPL:" . $tvtilausrivirow['tilkpl'] . "\n";
			$ulos .= "TILVAHVRIV.TVR_TOIMKPL:"    . $tvtilausrivirow['varattu'] . "\n";
			$ulos .= "TILVAHVRIV.TVR_JT:"    	  . $tvtilausrivirow['jt'] . "\n";
			$ulos .= "TILVAHVRIV.TVR_OVH:"        . $tvtilausrivirow['hinta'] . "\n";

			$hinta = $tvtilausrivirow['hinta'];

			for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
				$hinta *= (1 - $tvtilausrivirow["ale{$alepostfix}"] / 100);
			}

			if ($tvtilausrivirow['var'] != 'N') {
				$hinta = $hinta * (1 - $laskurow['erikoisale'] / 100);
			}

			$hinta = round($hinta,2);

			$ulos .= "TILVAHVRIV.TVR_NETTO:" . $hinta . "\n";
			$ulos .= "TILVAHVRIV.TVR_VEROLLISUUS:1\n";
			$ulos .= "*RE TILVAHVRIV " . $rivino . "\n";

			$rivino++;

		}

		$ulos .= "*ME\n";
		$ulos .= "*IE\n";

		fputs ($toot,$ulos);
		fclose ($toot);

		// lähetetään ftpllä
		if ($laskurow["toim_ovttunnus"] == "") $laskurow["toim_ovttunnus"] = $laskurow["ovttunnus"];

		$ftpnimi = $kukarow["yhtio"].$laskurow["toim_ovttunnus"]."-".basename($tilvahvfilenimi);

		if ($GLOBALS["futurtilvahhost"] != "" and $GLOBALS["futurtilvahuser"] != "" and $GLOBALS["futurtilvahpass"] != "") {

			$conn_id = ftp_connect($GLOBALS["futurtilvahhost"]);

			// jos connectio ok, kokeillaan loginata
			if ($conn_id) {
				$login_result = ftp_login($conn_id, $GLOBALS["futurtilvahuser"], $GLOBALS["futurtilvahpass"]);
			}

			// jos login ok kokeillaan uploadata
			if ($login_result) {
				$upload = ftp_put($conn_id, $ftpnimi, realpath($tilvahvfilenimi), FTP_ASCII);
			}

			if ($conn_id) {
				ftp_close($conn_id);
			}

			// jos mikätahansa feilas niin palautetaan yks
			if ($conn_id === FALSE or $login_result === FALSE or $upload === FALSE) {
				$palautus = 1;
				echo "Ongelma Futursoft tilausvahvistuksen lähetyksessä!<br/><br/>";
			}
		}
		else {
			$palautus = 1;
			echo "Ongelma Futursoft tilausvahvistuksen lähetyksessä!<br/><br/>";
		}

	} // end rivejä löytyi
}

if (strpos($_SERVER['SCRIPT_NAME'], "tulostakopio.php") === FALSE and $yhtiorow['tilausvahvistus_tallenna'] == 'K') {
	$tilausvahvistus_tallenna = "/tmp/Tilausvahvistus-".md5(uniqid(mt_rand(), true)).".dat";
	file_put_contents($tilausvahvistus_tallenna, $ulos);
}
else {
	$tilausvahvistus_tallenna = "";
}

$ulos = "";

?>
