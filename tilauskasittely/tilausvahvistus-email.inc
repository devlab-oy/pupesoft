<?php

// tarvitaan $laskurow array
// tarvitaan $yhtiorow array

$query         = "select * from asiakas where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[liitostunnus]'";
$tilvahresult  = mysql_query($query) or pupe_error($query);
$tilvahasiakas = mysql_fetch_array($tilvahresult);

$virexx = 0;
if (strpos(" ".$laskurow['tilausvahvistus'],'S')) {
	if ($tilvahasiakas['email']=='') {

		$tilvah_nimi = $tilvahasiakas["nimi"];
		if ($tilvahasiakas["toim_nimi"] != "" and $tilvahasiakas["toim_nimi"] != $tilvahasiakas["nimi"]) {
			$tilvah_nimi .= " / ".$tilvahasiakas["toim_nimi"];
		}

		echo "<font class='error'>".t("TILAUSVAHVISTUS: Asiakkaalla ei ole sähköpostiosoitetta")."! $tilvah_nimi ($tilvahasiakas[ytunnus])</font><br><br>";
		$virexx++;
	}
}
if (strpos(" ".$laskurow['tilausvahvistus'],'O')) {
	if ($kukarow['eposti']=='') {
		echo "<font class='error'>".t("TILAUSVAHVISTUS: Sinulla ei ole sähköpostiosoitetta")."!</font><br><br>";
		$virexx++;
	}
}


if ($virexx == 0) {
	$query = "	SELECT tilausrivi.tunnus, eankoodi, myyntihinta, aleryhma,
				tilausrivi.tuoteno, varattu, toimaika, kommentti, tilkpl, tilausrivi.alv, tilausrivi.nimitys, tilausrivi.hinta, tilausrivi.ale, tilausrivi.kommentti, jt, var
				FROM tilausrivi
				LEFT JOIN tuote USING (yhtio, tuoteno)
				WHERE otunnus = '$laskurow[tunnus]'
				and tilausrivi.yhtio='$kukarow[yhtio]'
				ORDER BY tunnus";
	$tilvahresult = mysql_query($query) or pupe_error($query);

	$query = "	SELECT nimi, eposti
				FROM kuka
				WHERE yhtio='$kukarow[yhtio]' and tunnus='$laskurow[myyja]'";
	$kresult = mysql_query($query) or pupe_error($query);

	$krow = mysql_fetch_array($kresult);

	if (mysql_num_rows($tilvahresult) > 0) {

		if ($laskurow["tilaustyyppi"] == 'E') {
			$ulos  = sprintf("%-50.s",$yhtiorow['nimi'])								.t("Tilausvahvistus Ennakkomyynti", $kieli)."\r\n";
		}
		else {
			$ulos  = sprintf("%-50.s",$yhtiorow['nimi'])								.t("Tilausvahvistus", $kieli)."\r\n";
		}

		$ulos .= sprintf("%-50.s",$yhtiorow['osoite'])								."\r\n";
		$ulos .= sprintf("%-50.s",$yhtiorow['postino']." ".$yhtiorow['postitp'])	.tv1dateconv($laskurow['luontiaika'])."\r\n";
		$ulos .= "\r\n";
		$ulos .= sprintf("%-50.s",t("Tilaaja", $kieli).":")									.t("Toimitusosoite", $kieli).":\r\n";
		$ulos .= sprintf("%-50.s",$laskurow['nimi'])								.$laskurow['toim_nimi']."\r\n";
		$ulos .= sprintf("%-50.s",$laskurow['nimitark'])							.$laskurow['toim_nimitark']."\r\n";
		$ulos .= sprintf("%-50.s",$laskurow['osoite'])								.$laskurow['toim_osoite']."\r\n";
		$ulos .= sprintf("%-50.s",$laskurow['postino']." ".$laskurow['postitp'])	.$laskurow['toim_postino']." ".$laskurow['toim_postitp']."\r\n";
		$ulos .= "\r\n";
		$ulos .= sprintf("%-50.s",t("Toimitustapa", $kieli).": ".$laskurow['toimitustapa'])	.t("Tilausnumero", $kieli).": ".$laskurow['tunnus']."\r\n";
		$ulos .= sprintf("%-50.s",t("Tilausviite", $kieli).": ".$laskurow['viesti'])		.t("Myyjä", $kieli).": ".$krow['nimi']."\r\n";

		if ($laskurow['erikoisale'] > 0) {
			$ulos .= sprintf("%-50.s",t("Erikoisalennus", $kieli).": ".$laskurow['erikoisale']."%\n");
		}

		if ($laskurow['comments']!='')
			$ulos .= t("Kommentti", $kieli).": ".$laskurow['comments']."\n";

		$ulos .= "\r\n";

		$ulos .= t("Nro", $kieli)." ".t("Nimitys", $kieli)."                       ".t("Tuotenumero", $kieli)."              ".t("Kpl", $kieli)."   ".t("Hinta", $kieli)." ".t("Val", $kieli)."   ".t("Ale", $kieli)."\r\n";
		$ulos .= "---------------------------------------------------------------------------------\r\n";

		$rivino = 0;
		$liite  = t("Rivino", $kieli)."\t".t("Nimitys", $kieli)."\t".t("Tuoteno", $kieli)."\t".t("Varattu", $kieli)."\t".t("Hinta", $kieli)."\t".t("Ale", $kieli)."\r\n";

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
				$ulos .= sprintf("%8.s"  ,$tvtilausrivirow['jt']).t(" Jälkitoimitukseen", $kieli);
			}

			if ($naytatvale==2) {
				$ulos .= sprintf("%8.s"  ,$tvtilausrivirow['myyntihinta'])." ";
			}
			else {
				$ulos .= sprintf("%8.s"  ,$tvtilausrivirow['hinta'])." ";
			}

			$ulos .= sprintf("%3.s"  	,$laskurow['valkoodi'])." ";

			if ($naytatvale==2) {
				$ulos .= sprintf("%6.s"  ,$tvtilausrivirow['aleryhma']);
			}
			else {
				$ulos .= sprintf("%5.s"  ,$tvtilausrivirow['ale'])."%";
			}

			$ulos .= "\r\n";

			if ($nimitysloput != "") {
				$ulos .= "    ".sprintf("%-77.77s",$nimitysloput)."\n";
			}

			if ($tvtilausrivirow['kommentti'] != "") {
				$ulos .= "     * ".sprintf("%-75.75s",$tvtilausrivirow['kommentti'])."\n";
			}

			///* Tehrään liitetiedosto *///
			$liite .= $rivino."\t".$tvtilausrivirow['nimitys']."\t".$tvtilausrivirow['tuoteno']."\t".$tvtilausrivirow['varattu']."\t";

			if ($naytatvale==2) {
				$liite .= $tvtilausrivirow['myyntihinta']."\t";
			}
			else {
				$liite .= $tvtilausrivirow['hinta']."\t";
			}

			if ($naytatvale==2) {
				$liite .= $tvtilausrivirow['aleryhma'];
			}
			else {
				$liite .= $tvtilausrivirow['ale'];
			}
			$liite .= "\r\n";

		}

	} // end rivejä löytyi

	///* Kompostoidaan meili *///
	$bound = uniqid(time()."_") ;

	$header  = "From: <$yhtiorow[postittaja_email]>\r\n";
	$header .= "MIME-Version: 1.0\r\n";
	$header .= "Content-Type: multipart/mixed; boundary=\"$bound\"\r\n";

	$content = "--$bound\r\n";

	$content .= "Content-Type: text/plain;\r\n";
	$content .= "Content-Transfer-Encoding: quoted-printable\r\n";
	$content .= $ulos;
	$content .= "\r\n" ;

	$content .= "--$bound\r\n";

	$content .= "Content-Type: text/plain;\r\n" ;
	$content .= "Content-Transfer-Encoding: 7bit\r\n" ;
	$content .= "Content-Disposition: attachment; filename=\"tilvahv.txt\"\r\n\r\n";
	$content .= $liite;
	$content .= "\r\n" ;



	if (strpos(" ".$laskurow['tilausvahvistus'],'S')) {
		unset($boob);
		$boob = mail($tilvahasiakas['email'], $yhtiorow['nimi']." - ".t("Tilausvahvistus", $kieli), $content, $header, "-f $yhtiorow[postittaja_email]");
		if ($boob===FALSE) echo t("Sähköpostin lähetys epäonnistui")."!<br>";
	}

	if (strpos(" ".$laskurow['tilausvahvistus'],'O')) {
		unset($boob);
		$boob = mail($kukarow['eposti']		, $yhtiorow['nimi']." - ".t("Tilausvahvistus", $kieli), $content, $header, "-f $yhtiorow[postittaja_email]");
		if ($boob===FALSE) echo t("Sähköpostin lähetys epäonnistui")."!<br>";
	}
} // end email löytyi

$ulos = "";

?>
