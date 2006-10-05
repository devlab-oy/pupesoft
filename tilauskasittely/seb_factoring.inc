 <?php
 	//tarvitsemme $laskurow:n jossa on laskun tiedot
 	// ja $masrown jossa on maksuehdon tiedot
 	
 	// jos $fakt_lahetys on tyhjä niin luodaan aineisto
 	// jos $fakt_lahetys ei ole tyhjä niin aineisto lähetetään
 	
 	//softa palauttaa $sisalto-muutujan
 	
 	$sebfact_ulos = "";
 	
 	if ($fakt_lahetys == "") {
		//maksuehto tekstinä
		$maksuehto 		= $masrow["teksti"]." ".$masrow["kassa_teksti"];
		$kateistyyppi	= $masrow["kateinen"];
		
		require_once('tulosta_lasku_text.inc');
			
		// haetaan tilauksen kaikki rivit
		$query = "	SELECT *
					FROM tilausrivi
					WHERE uusiotunnus='$laskurow[tunnus]' and yhtio='$kukarow[yhtio]'
					ORDER BY otunnus, hyllyalue, hyllynro, hyllyvali, hyllytaso, tuoteno";
		$ffaktresult = mysql_query($query) or pupe_error($query);
		
		//kuollaan jos yhtään riviä ei löydy
		if (mysql_num_rows($ffaktresult) == 0) {
			echo t("VIRHE: Laskurivejä ei löytynyt");
		}
		// aloitellaan laskun teko
		$firstpage = text_alku();
		
		while ($row = mysql_fetch_array($ffaktresult)) {
			text_rivi();
		}
		text_alvierittely ();
		text_loppu();
	}
	
	if ($fakt_lahetys != "" and $sisalto != '') {
		//keksitään uudelle failille joku varmasti uniikki nimi:	
		$pdffilenimi  = "Autofakt-".substr(md5(uniqid(rand(),true)),0,10).".txt";
		
		//tässa on kaikki failit jotka tarvitaan
		$bound = uniqid(time()."_") ;
	
		$header  = "From: <mailer@pupesoft.com>\r\n";
		$header .= "MIME-Version: 1.0\r\n" ;
		$header .= "Content-Type: multipart/mixed; boundary=\"$bound\"\r\n" ;
	
		$content .= "--$bound\r\n";
		$content .= "Content-Type: text/plain; name=\"$pdffilenimi\"\r\n";
		$content .= "Content-Transfer-Encoding: base64\r\n";
		$content .= "Content-Description: $pdffilenimi\r\n";
		$content .= "Content-Disposition: attachment; filename=\"$pdffilenimi\"\r\n\r\n";
	
		$content .= chunk_split(base64_encode($sisalto));
		$content .= "\r\n" ;
	
		$subjekti = "5819 aire1142 ".$pdffilenimi;
		
		if($yhtiorow["factoring_email"] == '' or !mail($yhtiorow["factoring_email"], $subjekti, $content, $header)) {
			$sebfact_ulos .= sprintf(t("VIRHE: Laskutuksen factoring-sähköpostisiirtoa ei voitu suorittaa osoitteeseen: %s"),$yhtiorow['factoring_email'])."<br>\r\n";	
		}
		else {
			$sebfact_ulos .= sprintf(t("Laskutuksen factoring-sähköpostisiirto lähetettiin osoitteeseen: %s"),$yhtiorow['factoring_email'])."<br><br>\r\n";
		}
		
		//lähetetään meili myös yhtiön adminille
		mail($yhtiorow["admin_email"], $subjekti, $content, $header);
		
		//echotaan rudulle jos kyseessä ei ole batch-ajo
		if ($tulos_ulos == "") {
			echo $sebfact_ulos;
		}				
	}
?>