<?php

// tarvitaan $laskurow array
// tarvitaan $yhtiorow array

$query         = "select * from asiakas where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[liitostunnus]'";
$tilvahresult  = mysql_query($query) or pupe_error($query);
$tilvahasiakas = mysql_fetch_array($tilvahresult);

$kieli = strtolower($tilvahasiakas['kieli']);

if ($tilvahasiakas['fax'] == '') {

	$tilvah_nimi = $tilvahasiakas["nimi"];
	if ($tilvahasiakas["toim_nimi"] != "" and $tilvahasiakas["toim_nimi"] != $tilvahasiakas["nimi"]) {
		$tilvah_nimi .= " / ".$tilvahasiakas["toim_nimi"];
	}

	if ($silent == "") {
		echo "<font class='error'>".t("TILAUSVAHVISTUS: Asiakkaalta puuttuu faxinumero")."! $tilvah_nimi ($tilvahasiakas[ytunnus])</font><br><br>";
	}
}
else {

	if ($kukarow['extranet'] == '') {
		$tilvahvfilenimi = "../dataout/tilvafax-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(),true)).".txt";
	}
	else {
		$tilvahvfilenimi = "dataout/tilvafax-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(),true)).".txt";
	}

	if (!$toot = fopen($tilvahvfilenimi, "w")) die(t("TILAUSVAHVISTUS: Tiedoston luonti ep‰onnistui")."!");

	$query = "	SELECT tilausrivi.tunnus, tuote.eankoodi, tuote.myyntihinta, tuote.aleryhma, tilausrivi.tuoteno,
				if(tilausrivi.var not in ('P','J','S'), tilausrivi.varattu, 0) varattu,
				if(tilausrivi.var in ('J','S'), tilausrivi.jt $lisavarattu, 0) jt,
				tilausrivi.toimaika, tilausrivi.kommentti, tilausrivi.tilkpl, tilausrivi.alv,
				tilausrivi.nimitys, tilausrivi.hinta, tilausrivi.ale, tilausrivi.kommentti, tilausrivi.var
				FROM tilausrivi
				LEFT JOIN tuote USING (yhtio, tuoteno)
				WHERE tilausrivi.otunnus = '$laskurow[tunnus]'
				and tilausrivi.yhtio	 = '$kukarow[yhtio]'
				ORDER BY tilausrivi.tunnus";
	$tilvahresult = mysql_query($query) or pupe_error($query);

	$query = "	SELECT nimi, eposti
				FROM kuka
				WHERE yhtio = '$kukarow[yhtio]'
				and tunnus  = '$laskurow[myyja]'";
	$kresult = mysql_query($query) or pupe_error($query);
	$krow = mysql_fetch_array($kresult);

	if (mysql_num_rows($tilvahresult) > 0) {
		$ulos  = "FAX: ".ereg_replace("[^0-9]", "", $tilvahasiakas['fax'])."\n";

		if ($laskurow["tilaustyyppi"] == 'E') {
			$ulos .= sprintf("%-50.s",$yhtiorow['nimi'])								.t("Tilausvahvistus Ennakkomyynti", $kieli)."\n";
		}
		else {
			$ulos .= sprintf("%-50.s",$yhtiorow['nimi'])								.t("Tilausvahvistus", $kieli)."\n";
		}

		$ulos .= sprintf("%-50.s",$yhtiorow['osoite'])								."\n";
		$ulos .= sprintf("%-50.s",$yhtiorow['postino']." ".$yhtiorow['postitp'])	.tv1dateconv($laskurow['luontiaika'])."\n";
		$ulos .= "\r\n";
		$ulos .= sprintf("%-50.s",t("Tilaaja", $kieli).":")									.t("Toimitusosoite", $kieli).":\n";
		$ulos .= sprintf("%-50.s",$laskurow['nimi'])								.$laskurow['toim_nimi']."\n";
		$ulos .= sprintf("%-50.s",$laskurow['nimitark'])							.$laskurow['toim_nimitark']."\n";
		$ulos .= sprintf("%-50.s",$laskurow['osoite'])								.$laskurow['toim_osoite']."\n";
		$ulos .= sprintf("%-50.s",$laskurow['postino']." ".$laskurow['postitp'])	.$laskurow['toim_postino']." ".$laskurow['toim_postitp']."\n";
		$ulos .= "\r\n";

		if ($laskurow['toimvko'] == 'V') {
			$laskurow["toimaika"] = t("Viikko")." ".date("W", strtotime($laskurow["toimaika"]));
		}

		$ulos .= sprintf("%-50.s",t("Toimitustapa", $kieli).": ".$laskurow['toimitustapa'])	.t("Toimitusaika", $kieli).": ".$laskurow['toimaika']."\n";
		$ulos .= sprintf("%-50.s",t("Tilausnumero", $kieli).": ".$laskurow['tunnus'])		.t("Tilausviite", $kieli).": ".$laskurow['viesti']."\n";
		$ulos .= sprintf("%-50.s",t("Myyj‰", $kieli).": ".$krow['nimi'])."\n";

		if ($laskurow['comments']!='')
			$ulos .= t("Kommentti", $kieli).": ".$laskurow['comments']."\n";
		$ulos .= "\n";

		$ulos .= t("Nro", $kieli)." ".t("Nimitys", $kieli)."                       ".t("Tuotenumero", $kieli)."              ".t("Kpl", $kieli);

		if ($naytatvale != 3) {
			$ulos .= "   ".t("Hinta", $kieli)." ".t("Val", $kieli)."   ".t("Ale", $kieli);
		}

		$ulos .= "\n";
		$ulos .= "---------------------------------------------------------------------------------\n";

		$rivino=0;
		while ($tvtilausrivirow=mysql_fetch_array($tilvahresult)) {
			$rivino++;
			if (strlen($tvtilausrivirow['nimitys']) > 29) {
				$nimitysloput = substr($tvtilausrivirow['nimitys'],29);
				$tvtilausrivirow['nimitys'] = substr($tvtilausrivirow['nimitys'],0,29);
			}
			$ulos .= sprintf("%03.0f",$rivino)." ";
			$ulos .= sprintf("%-30.s",$tvtilausrivirow['nimitys']);
			$ulos .= sprintf("%-20.s",$tvtilausrivirow['tuoteno']);

			if ($laskurow["tilaustyyppi"] == 'E') {
				$ulos .= sprintf("%8.s"  ,$tvtilausrivirow['tilkpl']);
			}
			elseif ($tvtilausrivirow['var']!= 'J') {
				$ulos .= sprintf("%8.s"  ,$tvtilausrivirow['varattu']);
			}
			else {
				$ulos .= sprintf("%8.s"  ,$tvtilausrivirow['jt']).t("J‰lkitoimitukseen", $kieli);
			}

			if ($naytatvale==3) {
				// menn‰‰n ohi koska ei haluta n‰ytt‰‰ hintoja
			}
			else if ($naytatvale==2) {
				$ulos .= sprintf("%8.s"  ,$tvtilausrivirow['myyntihinta'])." ";
			}
			else {
				$ulos .= sprintf("%8.s"  ,$tvtilausrivirow['hinta'])." ";
			}

			// kolmosella ei haluta n‰ytt‰‰ myˆsk‰‰n valkoodia
			if ($naytatvale != 3) {
				$ulos .= sprintf("%3.s"  ,$laskurow['valkoodi'])." ";
			}

			if ($naytatvale==3) {
				// menn‰‰n ohi koska ei haluta n‰ytt‰‰ alea
			}
			else if ($naytatvale==2) {
				$ulos .= sprintf("%6.s"  ,$tvtilausrivirow['aleryhma']);
			}
			else {
				$ulos .= sprintf("%5.s"  ,$tvtilausrivirow['ale'])."%";
			}
			$ulos .= "\n";

			if ($nimitysloput != "") {
				$ulos .= "    ".sprintf("%-77.77s",$nimitysloput)."\n";
			}

			if ($tvtilausrivirow['kommentti'] != "") {
				$ulos .= "     * ".sprintf("%-75.75s",$tvtilausrivirow['kommentti'])."\n";
			}

		}

	} // end rivej‰ lˆytyi

	fputs($toot,$ulos);
	fclose ($toot);

	$ftphost = $faxhost;
	$ftpuser = $faxuser;
	$ftppass = $faxpass;
	$ftppath = $faxpath;
	$ftpfile = realpath($tilvahvfilenimi);

	if ($kukarow["extranet"] == '') {
		require ("../inc/ftp-send.inc");
	}
	else {
		require ("ftp-send.inc");
	}
	#echo "<pre>$ulos</pre>";

} // end email lˆytyi

if ($yhtiorow['tilausvahvistus_tallenna'] == 'K') {
	$tilausvahvistus_tallenna = "/tmp/Tilausvahvistus-".md5(uniqid(mt_rand(), true)).".txt";
	file_put_contents($tilausvahvistus_tallenna, $ulos);
}
else {
	$tilausvahvistus_tallenna = "";
}

$ulos = "";

?>
