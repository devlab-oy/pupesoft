 <?php
 	//tarvitsemme $laskurow:n jossa on laskun tiedot
 	// ja $masrown jossa on maksuehdon tiedot

 	// jos $fakt_lahetys on tyhjä niin luodaan aineisto
 	// jos $fakt_lahetys ei ole tyhjä niin aineisto lähetetään

 	//softa palauttaa $sisalto-muutujan

 	$sebfact_ulos = "";

 	if ($fakt_lahetys == "") {
		//maksuehto tekstinä
		$maksuehto 		= t_tunnus_avainsanat($masrow, "teksti", "MAKSUEHTOKV");
		$kateistyyppi	= $masrow["kateinen"];

		require_once('tulosta_lasku_text.inc');

		// haetaan tilauksen kaikki rivit
		$query = "	SELECT *, concat(lpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0')) sorttauskentta
					FROM tilausrivi
					WHERE uusiotunnus='$laskurow[tunnus]' and yhtio='$kukarow[yhtio]'
					ORDER BY otunnus, sorttauskentta, tuoteno";
		$ffaktresult = mysql_query($query) or pupe_error($query);

		//kuollaan jos yhtään riviä ei löydy
		if (mysql_num_rows($ffaktresult) == 0) {
			echo t("VIRHE: Laskurivejä ei löytynyt");
		}
		// aloitellaan laskun teko
		$firstpage = text_alku();

		while ($row = mysql_fetch_array($ffaktresult)) {

			// Rivin toimitusaika
			if ($yhtiorow["tilausrivien_toimitettuaika"] == 'K' and $row["keratty"] == "saldoton") {
				$row["toimitettuaika"] = $row["toimaika"];
			}
			elseif ($yhtiorow["tilausrivien_toimitettuaika"] == 'X') {
				$row["toimitettuaika"] = $row["toimaika"];
			}
			else {
				$row["toimitettuaika"] = $row["toimitettuaika"];
			}

			text_rivi();
		}

		text_alvierittely();
		text_loppu();
	}

	if ($fakt_lahetys != "" and $sisalto != '') {
		//keksitään uudelle failille joku varmasti uniikki nimi:
		$pdffilenimi  = "Autofakt-".substr(md5(uniqid(rand(),true)),0,10).".txt";
		
		/* Lisätty 17.2.2014, lisätty taulukot liitteille ja niiden nimille */
		$liitteet = array();
		$liite_nimet = array();

		//tässa on kaikki failit jotka tarvitaan
		$bound = uniqid(time()."_") ;

		$header  = "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\r\n";
		$header .= "MIME-Version: 1.0\r\n" ;
		$header .= "Content-Type: multipart/mixed; boundary=\"$bound\"\r\n" ;

		$content .= "--$bound\r\n";
		$content .= "Content-Type: text/plain; name=\"$pdffilenimi\"\r\n";
		$content .= "Content-Transfer-Encoding: base64\r\n";
		$content .= "Content-Description: $pdffilenimi\r\n";
		$content .= "Content-Disposition: attachment; filename=\"$pdffilenimi\"\r\n\r\n";

		$content .= chunk_split(base64_encode($sisalto));
		$content .= "\r\n" ;
		
		/* Lisätty 17.2.2014, lisätään liiteet ja niiden nimet taulukoihin */
		array_push($liitteet, $sisalto);
		array_push($liite_nimet, $pdffilenimi);
		
		$content_body = "";

		$subjekti = $frow["sopimusnumero"]." ".$pdffilenimi;

		/* Muokattu 14.2.2014, kommentoitu vanha mail() -funktio pois ja lisätty paranneltu sendMail */
		//if($frow["email"] == '' or !mail($frow["email"], mb_encode_mimeheader($subjekti, "ISO-8859-1", "Q"), $content, $header, "-f $yhtiorow[postittaja_email]")) {
		include_once '/var/www/html/lib/functions/sendMail.php';  // Lisätään sendMail funktio
		if($frow["email"] == '' or !sendMail($yhtiorow['postittaja_email'], $frow["email"], $subjekti, $content_body, false, $liitteet, $liite_nimet)) {
			$sebfact_ulos .= sprintf(t("VIRHE: Laskutuksen factoring-sähköpostisiirtoa ei voitu suorittaa osoitteeseen: %s"),$frow["email"])."<br>\r\n";
		}
		else {
			$sebfact_ulos .= sprintf(t("Laskutuksen factoring-sähköpostisiirto lähetettiin osoitteeseen: %s"),$frow["email"])."<br><br>\r\n";
		}

		//lähetetään meili myös yhtiön adminille
		/* Muokattu 14.2.2014, kommentoitu vanha mail() -funktio pois ja lisätty paranneltu sendMail */
		//mail($yhtiorow["alert_email"], mb_encode_mimeheader($subjekti, "ISO-8859-1", "Q"), $content, $header, "-f $yhtiorow[postittaja_email]");
		include_once '/var/www/html/lib/functions/sendMail.php';  // Lisätään sendMail funktio
		$posti = sendMail($yhtiorow['postittaja_email'], $yhtiorow["alert_email"], $subjekti, $content_body, false, $liitteet, $liite_nimet);

		//echotaan rudulle jos kyseessä ei ole batch-ajo
		if ($tulos_ulos == "") {
			echo $sebfact_ulos;
		}
	}
?>