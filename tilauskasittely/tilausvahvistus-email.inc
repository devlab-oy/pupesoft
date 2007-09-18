<?php

// tarvitaan $laskurow array
// tarvitaan $yhtiorow array

require_once ("tulosta_tilausvahvistus_pdf.inc");

$query         = "select * from asiakas where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[liitostunnus]'";
$tilvahresult  = mysql_query($query) or pupe_error($query);
$tilvahasiakas = mysql_fetch_array($tilvahresult);

$virexx = 0;

// asiakkaan sähköpostiin
if (strpos($laskurow['tilausvahvistus'], 'S') !== FALSE) {

	if ($tilvahasiakas['email'] == '') {
		$tilvah_nimi = $tilvahasiakas["nimi"];
		if ($tilvahasiakas["toim_nimi"] != "" and $tilvahasiakas["toim_nimi"] != $tilvahasiakas["nimi"]) {
			$tilvah_nimi .= " / ".$tilvahasiakas["toim_nimi"];
		}
		echo "<font class='error'>".t("TILAUSVAHVISTUS: Asiakkaalla ei ole sähköpostiosoitetta")."! $tilvah_nimi ($tilvahasiakas[ytunnus])</font><br><br>";
		$virexx++;
	}
	else {
		$komento = "asiakasemail".$tilvahasiakas['email'];
		// lähetetään PDF vahvistus vain jos meillä on P kirjain vahvistuksessa
		if (strpos($laskurow['tilausvahvistus'], 'P') !== FALSE) {
			tulosta_tilausvahvistus($laskurow["tunnus"], $komento);
		}
	}
}

// omaan sähköpostiin
if (strpos($laskurow['tilausvahvistus'], 'O') !== FALSE) {

	if ($kukarow['eposti'] == '') {
		echo "<font class='error'>".t("TILAUSVAHVISTUS: Sinulla ei ole sähköpostiosoitetta")."!</font><br><br>";
		$virexx++;
	}
	else {
		$komento = "email";
		// lähetetään PDF vahvistus vain jos meillä on P kirjain vahvistuksessa
		if (strpos($laskurow['tilausvahvistus'], 'P') !== FALSE) {
			tulosta_tilausvahvistus($laskurow["tunnus"], $komento);
		}
	}
}

// lähetetään textimuotoinen tilausvahvistusmeili jos miellä ei ole P:kirjainta vahvistuksessa
if ($virexx == 0 and strpos($laskurow['tilausvahvistus'], 'P') === FALSE) {

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
			$ulos  = sprintf("%-50.s",$yhtiorow['nimi'])								.t("Tilausvahvistus Ennakkomyynti", $kieli)."\n";
		}
		else {
			$ulos  = sprintf("%-50.s",$yhtiorow['nimi'])								.t("Tilausvahvistus", $kieli)."\n";
		}

		$ulos .= sprintf("%-50.s",$yhtiorow['osoite'])								."\n";
		$ulos .= sprintf("%-50.s",$yhtiorow['postino']." ".$yhtiorow['postitp'])	.tv1dateconv($laskurow['luontiaika'])."\n";
		$ulos .= "\n";
		$ulos .= sprintf("%-50.s",t("Tilaaja", $kieli).":")									.t("Toimitusosoite", $kieli).":\n";
		$ulos .= sprintf("%-50.s",$laskurow['nimi'])								.$laskurow['toim_nimi']."\n";
		$ulos .= sprintf("%-50.s",$laskurow['nimitark'])							.$laskurow['toim_nimitark']."\n";
		$ulos .= sprintf("%-50.s",$laskurow['osoite'])								.$laskurow['toim_osoite']."\n";
		$ulos .= sprintf("%-50.s",$laskurow['postino']." ".$laskurow['postitp'])	.$laskurow['toim_postino']." ".$laskurow['toim_postitp']."\n";
		$ulos .= "\n";

		if ($laskurow['toimvko'] == 'V') {
			$laskurow["toimaika"] = t("Viikko")." ".date("W", strtotime($laskurow["toimaika"]));
		}

		$ulos .= sprintf("%-50.s",t("Toimitustapa", $kieli).": ".$laskurow['toimitustapa'])		.t("Toimitusaika", $kieli).": ".$laskurow['toimaika']."\n";
		$ulos .= sprintf("%-50.s",t("Tilausnumero", $kieli).": ".$laskurow['tunnus'])			.t("Tilausviite", $kieli).": ".$laskurow['viesti']."\n";
		$ulos .= sprintf("%-50.s",t("Tilaaja", $kieli).": ".$laskurow['tilausyhteyshenkilo'])	.t("Tilaajan tilausnumero", $kieli).": ".$laskurow['asiakkaan_tilausnumero']."\n";
		$ulos .= sprintf("%-50.s",t("Kohde", $kieli).": ".$laskurow['kohde'])					.t("Myyjä", $kieli).": ".$krow['nimi']."\n";

		if ($laskurow['erikoisale'] > 0) {
			$ulos .= sprintf("%-50.s",t("Erikoisalennus", $kieli).": ".$laskurow['erikoisale']."%\n");
		}

		if ($laskurow['comments']!='')
			$ulos .= t("Kommentti", $kieli).": ".$laskurow['comments']."\n";

		$ulos .= "\n";

		$ulos .= t("Nro", $kieli)." ".t("Nimitys", $kieli)."                       ".t("Tuotenumero", $kieli)."              ".t("EAN", $kieli)."          ".t("Kpl", $kieli)."   ".t("Hinta", $kieli)." ".t("Val", $kieli)."   ".t("Ale", $kieli)."\n";
		$ulos .= "----------------------------------------------------------------------------------------------\n";

		$rivino = 0;
		$liite  = t("Rivino", $kieli)."\t".t("Nimitys", $kieli)."\t".t("Tuoteno", $kieli)."\t".t("Varattu", $kieli)."\t".t("Hinta", $kieli)."\t".t("Ale", $kieli)."\n";

		while ($tvtilausrivirow=mysql_fetch_array($tilvahresult)) {
			$rivino++;

			if (strlen($tvtilausrivirow['nimitys']) > 29) {
				$nimitysloput = substr($tvtilausrivirow['nimitys'],29);
				$tvtilausrivirow['nimitys'] = substr($tvtilausrivirow['nimitys'],0,29);
			}
			$ulos .= sprintf("%03.0f",$rivino)." ";
			$ulos .= sprintf("%-30.s",$tvtilausrivirow['nimitys']);
			$ulos .= sprintf("%-20.s",$tvtilausrivirow['tuoteno']);
			$ulos .= sprintf("%-13.s",$tvtilausrivirow['eankoodi']);

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
				$ulos .= sprintf("%8.s"  ,round(laskuval($tvtilausrivirow['myyntihinta'], $laskurow['vienti_kurssi']),2))." ";
			}
			else {
				$ulos .= sprintf("%8.s"  ,round(laskuval($tvtilausrivirow['hinta'], $laskurow['vienti_kurssi']),2))." ";
			}

			$ulos .= sprintf("%3.s"  	,$laskurow['valkoodi'])." ";

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

			// Tehrään liitetiedosto
			$liite .= $rivino."\t".$tvtilausrivirow['nimitys']."\t".$tvtilausrivirow['tuoteno']."\t".$tvtilausrivirow['varattu']."\t";

			if ($naytatvale==2) {
				$liite .= round(laskuval($tvtilausrivirow['myyntihinta'], $laskurow['vienti_kurssi']),2)."\t";
			}
			else {
				$liite .= round(laskuval($tvtilausrivirow['hinta'], $laskurow['vienti_kurssi']),2)."\t";
			}

			if ($naytatvale==2) {
				$liite .= $tvtilausrivirow['aleryhma'];
			}
			else {
				$liite .= $tvtilausrivirow['ale'];
			}
			$liite .= "\n";

		}

	} // end rivejä löytyi

	// Kompostoidaan meili
	$bound = uniqid(time()."_") ;

	$header  = "From: <$yhtiorow[postittaja_email]>\n";
	$header .= "MIME-Version: 1.0\n";
	$header .= "Content-Type: multipart/mixed; boundary=\"$bound\"\n";

	$content = "--$bound\n";

	$content .= "Content-Type: text/plain;\n";
	$content .= "Content-Transfer-Encoding: quoted-printable\n";
	$content .= $ulos;
	$content .= "\n" ;

	$content .= "--$bound\n";

	$content .= "Content-Type: text/plain;\n" ;
	$content .= "Content-Transfer-Encoding: 7bit\n" ;
	$content .= "Content-Disposition: attachment; filename=\"tilvahv.txt\"\n\n";
	$content .= $liite;
	$content .= "\n" ;

	if (strpos($laskurow['tilausvahvistus'], 'S') !== FALSE) {
		unset($boob);
		$boob = mail($tilvahasiakas['email'], $yhtiorow['nimi']." - ".t("Tilausvahvistus", $kieli), $content, $header, "-f $yhtiorow[postittaja_email]");
		if ($boob===FALSE) echo t("Sähköpostin lähetys epäonnistui")."!<br>";
	}

	if (strpos($laskurow['tilausvahvistus'],'O') !== FALSE) {
		unset($boob);
		$boob = mail($kukarow['eposti']		, $yhtiorow['nimi']." - ".t("Tilausvahvistus", $kieli), $content, $header, "-f $yhtiorow[postittaja_email]");
		if ($boob===FALSE) echo t("Sähköpostin lähetys epäonnistui")."!<br>";
	}

}

$ulos = "";

?>
