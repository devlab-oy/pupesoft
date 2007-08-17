<?php

// tarvitaan $laskurow array
// tarvitaan $yhtiorow array

if ($kukarow['extranet'] == '') {
	$tilvahvfilenimi = "../dataout/tilvahvi-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(),true)).".txt";
}
else {
	$tilvahvfilenimi = "dataout/tilvahvi-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(),true)).".txt";
}

if (!$toot = fopen($tilvahvfilenimi, "w")) die("TILAUSVAHVISTUS: Tiedoston luonti ei onnistu!");

$query = "SELECT tilausrivi.tunnus, eankoodi,
		tilausrivi.tuoteno, varattu, toimaika, kommentti, tilkpl, tilausrivi.alv, tilausrivi.nimitys, tilausrivi.hinta, tilausrivi.ale, tilausrivi.kommentti, tilausrivi.laatija, tilausrivi.tilaajanrivinro
		FROM tilausrivi
		LEFT JOIN tuote USING (yhtio, tuoteno)
		WHERE otunnus = '$laskurow[tunnus]' and tilausrivi.yhtio='$kukarow[yhtio]'
		ORDER BY tunnus";
$tilvahresult = mysql_query($query) or pupe_error($query);

//Myyj‰n email

$query = "SELECT * FROM kuka WHERE tunnus='$laskurow[myyja]' and yhtio='$kukarow[yhtio]'";
$kukaresult = mysql_query($query) or pupe_error($query);
$myyjarow = mysql_fetch_array($kukaresult);

//Maskuehto

$query = "select * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
$xresult = mysql_query($query) or pupe_error($query);
if (mysql_num_rows($xresult) == 0)
{
	echo "TILAUSVAHVISTUS: Maksuehto on kateissa!<br><br>";
}
else {
	$mehtorow=mysql_fetch_array($xresult);
}

if (mysql_num_rows($tilvahresult) > 0)
{
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
	$ulos .= "RRFFCO_NU:" . $laskurow['viitetxt'] . "\n";  // Futursoftin asiakkaan tilausnumero
	$ulos .= "RFTXGENTX:" . $laskurow['viesti'] . "\n";
	$ulos .= "RFTXGENTX:" . $laskurow['comments'] . "\n";
	$ulos .= "RNADBY_PC:" . sprintf ("%-18.18s", $laskurow['ovttunnus']) . "100 91". "\n";
	$ulos .= "RNADBY_RF:" . "IT /" . $laskurow['ytunnus'] . "\n";
	$ulos .= "RNADBY_NA:" . $laskurow['nimi'] . "\n";
	$ulos .= "RNADBY_NX:" . $laskurow['nimitark'] . "\n";
	$ulos .= "RNADBY_SA:" . $laskurow['osoite'] . "\n";
	$ulos .= "RNADBY_PO:" . $laskurow['postino'] . "\n";
	$ulos .= "RNADBY_CI:" . $laskurow['postitp'] . "\n";
	$ulos .= "RNADBY_CN:" . $laskurow['maa'] . "\n";
	#$ulos .= "RNADBY_DO:" . $laskurow['tilausvahvistus'] . "\n"; // mimmosen tilausvahvistuksen asiakas haluaa... F = fax, E = edi
	$ulos .= "RNADBY_DO:E\n"; // mimmosen tilausvahvistuksen asiakas haluaa... aina edi ni ei tule ongelmia
	$ulos .= "RNADDP_RF:" . "ZZ /" . $laskurow['ytunnus'] . "\n";
	// jos tyhj‰‰ ni ei saa l‰hett‰‰‰.. weird.
	if (trim($laskurow['toim_ovttunnus'])!='') {
		$ulos .= "RNADDP_PC:" . sprintf ("%-18.18s", $laskurow['toim_ovttunnus']) . "100 91". "\n";
	}
	$ulos .= "RNADDP_NA:" . $laskurow['toim_nimi'] . "\n";
	$ulos .= "RNADDP_NX:" . $laskurow['toim_nimitark'] . "\n";
	$ulos .= "RNADDP_SA:" . $laskurow['toim_osoite'] . "\n";
	$ulos .= "RNADDP_PO:" . $laskurow['toim_postino'] . "\n";
	$ulos .= "RNADDP_CI:" . $laskurow['toim_postitp'] . "\n";
	$ulos .= "RNADDP_CN:" . $laskurow['toim_maa'] . "\n";

	$ulos .= "RNADSE_RF:" . "VA  " . $yhtiorow['maa'] . "-" . $yhtiorow['ytunnus'] . "\n";
	$ulos .= "RNADSE_PC:" . sprintf ("%-18.18s", $yhtiorow['ovttunnus']) . "100 91". "\n";
	$ulos .= "RNADSE_NA:" . $yhtiorow['nimi'] . "\n";
	$ulos .= "RNADSE_SA:" . $yhtiorow['osoite'] . "\n";
	$ulos .= "RNADSE_PO:" . $yhtiorow['postino'] . "\n";
	$ulos .= "RNADSE_CI:" . $yhtiorow['postitp'] . "\n";
	$ulos .= "RNADSE_CN:" . $yhtiorow['maa'] . "\n";
	$ulos .= "RNADSE_CC:" . "EB @" . $myyjarow['kuka']. "\n";
	$ulos .= "RNADSE_EM:" . "SR @" . $myyjarow['eposti'] . "\n";

	$ulos .= "RPAT1__TP:" . $mehtorow['kassa_teksti'] . "\n";
	$ulos .= "RPAT1__PC:" . $mehtorow['kassa_alepros'] . "\n";
	$ulos .= "RPAT2__TP:" . $mehtorow['teksti'] . "\n";
	$ulos .= "RPAT2__PC:" . sprintf ("%010.2f", (float) $yhtiorow['viivastyskorko']) . " 15\n";

	$ulos .= "RCUX5__CR:" . $laskurow['valkoodi'] . "\n";
	$ulos .= "RTDT20_TT:" . "ZNN " . $laskurow['toimitustapa'] . "\n";
	$ulos .= "RHDR__END:" . $yhtiorow['yhtio'] . $laskurow['tunnus'] . "\n";
	fputs($toot,$ulos);

	$rivino=1; // dummy rivino, kun elma ei handlaa pitki‰

	while ($tvtilausrivirow=mysql_fetch_array ($tilvahresult)) {
		if ($tvtilausrivirow['yksikko'] == '') $tvtilausrivirow['yksikko'] = "PCE";
		if ($tvtilausrivirow['eankoodi'] == 0) $tvtilausrivirow['eankoodi'] = '';

		$ulos  = "RLINSTART:" . $rivino . "\n";
		$ulos .= "RLINABORF:                                   @ " . $tvtilausrivirow['tilaajanrivinro'] . "\n"; //Futursoftin tilauksen rivinumero
		$ulos .= "RLINSA_NU:" . $tvtilausrivirow['tuoteno'] . "\n";
		$ulos .= "RLINEN_NU:" . $tvtilausrivirow['eankoodi'] . "\n";
		$ulos .= "RLIN8__IF:" . $tvtilausrivirow['nimitys'] . "\n";

		$laskurow['valkoodi'] = sprintf("%-3.s",$laskurow['valkoodi']);

		$ulos .= "RLINCONMA:" . sprintf("%014.2f", (float) $tvtilausrivirow['hinta']) . $laskurow['valkoodi'] ."  ". $tvtilausrivirow['yksikko'] . "\n";
		$ulos .= "RLIN21_QT:" . sprintf("%014.2f", (float) $tvtilausrivirow['tilkpl']) .  "     " . $tvtilausrivirow['yksikko'] . "\n";
		$ulos .= "RLIN113QT:" . sprintf("%014.2f", (float) $tvtilausrivirow['varattu']) . "     " . $tvtilausrivirow['yksikko'] . "\n";
		$ulos .= "RLIN___PA:" . sprintf("%07.2f",  (float) $tvtilausrivirow['ale']) . "\n";
		$ulos .= "RLIN2__DT:" . tv2dateconv($tvtilausrivirow['toimaika']) . "\n";
		$ulos .= "RLINVATTX:" . $tvtilausrivirow['alv'] . " S" . "\n";
		$ulos .= "RLINLINFC:" . $tvtilausrivirow['kommentti'] . "\n";
	//		$ulos .= "RLINLINFC:" . $tvtilausrivirow['kommentti'] . "\n"; // J‰lkitoimitusrivitiedot
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

	require ("../inc/ftp-send.inc");


#	// n‰ytet‰‰n faili ruudulla...
#	$toot = fopen(tilvahvfilenimi,"r");
#
#	$contents = "";
#	while (!feof ($toot)) {
#		$buffer = fgets($toot, 4096);
#		$contents .= $buffer;
#	}
#	fclose ($toot);
#	echo "<pre>".htmlentities($contents)."</pre><br><br>";

} // end rivej‰ lˆytyi

$ulos = "";

?>