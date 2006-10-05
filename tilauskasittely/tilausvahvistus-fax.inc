<?php

// tarvitaan $laskurow array
// tarvitaan $yhtiorow array

$query         = "select * from asiakas where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[liitostunnus]'";
$tilvahresult  = mysql_query($query) or pupe_error($query);
$tilvahasiakas = mysql_fetch_array($tilvahresult);

if ($tilvahasiakas['fax']=='')
{
	echo "<font class='error'>".t("TILAUSVAHVISTUS: Asiakkaalta puuttuu faxinumero")."!</font><br><br>";
}
else
{
	if ($kukarow['extranet'] == '') {
		$nimi = "../dataout/tilvafax-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(),true)).".txt";
	}
	else {
		$nimi = "dataout/tilvafax-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(),true)).".txt";
	}

	if (!$toot = fopen($nimi, "w")) die(t("TILAUSVAHVISTUS: Tiedoston luonti epäonnistui")."!");

	$query = "SELECT tilausrivi.tunnus, eankoodi, myyntihinta, aleryhma,
					tilausrivi.tuoteno, varattu, toimaika, kommentti, tilkpl, tilausrivi.alv, tilausrivi.nimitys, tilausrivi.hinta, tilausrivi.ale, tilausrivi.kommentti, jt, var
					FROM tilausrivi
					LEFT JOIN tuote USING (yhtio, tuoteno)
					WHERE otunnus = '$laskurow[tunnus]' and tilausrivi.yhtio='$kukarow[yhtio]'
					ORDER BY tunnus";
	$tilvahresult = mysql_query($query) or pupe_error($query);

	$query = "	SELECT nimi, eposti
				FROM kuka
				WHERE yhtio='$kukarow[yhtio]' and tunnus='$laskurow[myyja]'";
	$kresult = mysql_query($query) or pupe_error($query);
	$krow = mysql_fetch_array($kresult);

	if (mysql_num_rows($tilvahresult) > 0) {
		$ulos  = "FAX: ".$tilvahasiakas['fax']."\n";

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
		$ulos .= sprintf("%-50.s",t("Toimitustapa", $kieli).": ".$laskurow['toimitustapa'])	.t("Tilausnumero", $kieli).": ".$laskurow['tunnus']."\n";
		$ulos .= sprintf("%-50.s",t("Tilausviite", $kieli).": ".$laskurow['viesti'])		.t("Myyjä", $kieli).": ".$krow['nimi']."\n";

		if ($laskurow['comments']!='')
			$ulos .= t("Kommentti", $kieli).": ".$laskurow['comments']."\n";
		$ulos .= "\n";

		$ulos .= t("Nro", $kieli)." ".t("Nimitys", $kieli)."                       ".t("Tuotenumero", $kieli)."              ".t("Kpl", $kieli)."   ".t("Hinta", $kieli)." ".t("Val", $kieli)."   ".t("Ale", $kieli)."\n";
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
				$ulos .= sprintf("%8.s"  ,$tvtilausrivirow['jt']).t("Jälkitoimitukseen", $kieli);
			}

			if ($naytatvale==2) {
				$ulos .= sprintf("%8.s"  ,$tvtilausrivirow['myyntihinta'])." ";
			}
			else {
				$ulos .= sprintf("%8.s"  ,$tvtilausrivirow['hinta'])." ";
			}

			$ulos .= sprintf("%3.s"  ,$laskurow['valkoodi'])." ";

			if ($naytatvale==2) {
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

	} // end rivejä löytyi

	fputs($toot,$ulos);
	fclose ($toot);

	$ftphost = $faxhost;
	$ftpuser = $faxuser;
	$ftppass = $faxpass;
	$ftppath = $faxpath;
	$ftpfile = realpath($nimi);

	if ($kukarow["extranet"] == '') {
		require ("../inc/ftp-send.inc");
	}
	else {
		require ("ftp-send.inc");
	}
	#echo "<pre>$ulos</pre>";

} // end email löytyi

$ulos = "";

?>
