<?php

// tarvitaan $laskurow array
// tarvitaan $yhtiorow array

if ($kukarow['extranet'] == '') {
	$nimi = "../dataout/tilvahv_".$kukarow["yhtio"]."_".$laskurow["tunnus"].".dat";
}
else {
	$nimi = "dataout/tilvahv_".$kukarow["yhtio"]."_".$laskurow["tunnus"].".dat";
}

if (!$toot = fopen($nimi, "w")) die("TILAUSVAHVISTUS: Tiedoston luonti ei onnistu!");

$query = "SELECT tilausrivi.tunnus, eankoodi,
		tilausrivi.tuoteno, varattu, toimaika, kommentti, tilkpl, tilausrivi.alv, tilausrivi.nimitys, tilausrivi.hinta, tilausrivi.ale, tilausrivi.kommentti, tilausrivi.laatija, tilausrivi.tilaajanrivinro
		FROM tilausrivi
		LEFT JOIN tuote USING (yhtio, tuoteno)
		WHERE otunnus = '$laskurow[tunnus]' and tilausrivi.yhtio='$kukarow[yhtio]'
		ORDER BY tunnus";
$tilvahresult = mysql_query($query) or pupe_error($query);

if (mysql_num_rows($tilvahresult) > 0) {

	$ulos = "*IS\n";
	$ulos .= "*MS\n";
	$ulos .= "*RS TILVAHV " . $laskurow['tunnus'] . "\n";
	$ulos .= "TILVAHV.TLV_NRO:" . $laskurow['tunnus'] . "\n";
	$ulos .= "TILVAHV.TLV_AS_TILAUSNRO:" . $laskurow['viitetxt'] . "\n";
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

	$ulos .= "TILVAHV.TLV_TOIMITUSTAPA:" . $laskurow['toimitustapa'] . "\n";
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
		$ulos .= "TILVAHVRIV.TVR_TILAUSNRO:"  . $laskurow['viitetxt'] . "\n";
		$ulos .= "TILVAHVRIV.TVR_TUOTEKOODI:" . $tvtilausrivirow['tuoteno'] . "\n";
		$ulos .= "TILVAHVRIV.TVR_TILATTUKPL:" . $tvtilausrivirow['tilkpl'] . "\n";
		$ulos .= "TILVAHVRIV.TVR_TOIMKPL:"    . $tvtilausrivirow['varattu'] . "\n";
		$ulos .= "TILVAHVRIV.TVR_OVH:"        . $tvtilausrivirow['hinta'] . "\n";

		$hinta = round($tvtilausrivirow['hinta'] * (1 - $tvtilausrivirow['ale'] / 100),2);

		if ($tvtilausrivirow['var'] != 'N') {
			$hinta = round($hinta * (1 - $laskurow['erikoisale'] / 100),2);
		}

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

	$ftpnimi = $kukarow["yhtio"].$laskurow["toim_ovttunnus"]."-".basename($nimi);

	if ($futurtilvahhost != "" and $futurtilvahuser != "" and $futurtilvahpass != "") {

		$conn_id = ftp_connect($futurtilvahhost);

		// jos connectio ok, kokeillaan loginata
		if ($conn_id) {
			$login_result = ftp_login($conn_id, $futurtilvahuser, $futurtilvahpass);
		}

		// jos login ok kokeillaan uploadata
		if ($login_result) {
			$upload = ftp_put($conn_id, $ftpnimi, realpath($nimi), FTP_ASCII);
		}

		if ($conn_id) {
			ftp_close($conn_id);
		}

		// jos mikätahansa feilas niin palautetaan yks
		if ($conn_id === FALSE or $login_result === FALSE or $upload === FALSE) {
			$palautus = 1;
		}
	}
	else {
		$palautus = 1;
	}

} // end rivejä löytyi

$ulos = "";

?>
