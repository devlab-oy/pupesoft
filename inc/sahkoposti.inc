<?php
	// Tämä ohjelma haluaa $liite muuttujan jossa on tiedostonnimi joka liitetään
	// Jos liite halutaan nimetä uudestaan annetaan uusi nimi $liitenimi muuttujassa
	// Ja $kutsu jossa on tiedoston kuvaus
	// $ctype muuttuja jossa on liitettävän tiedoston MIME tyyppi

	///*Kasataan käyttäjälle lähetettävä meili *///
	//tässa on kaikki failit jotka tarvitaan

	$varaposti = '';

	if (trim($subject) == '') {
		$subject = "$yhtiorow[nimi] - $kutsu";
	}

	if (substr($komento,0,12) == 'asiakasemail') {
		$varaposti = $kukarow['eposti'];
		$kukarow['eposti'] = substr($komento, 12);
	}

	$bound = uniqid(time()."_") ;

	$header  = "From: <$yhtiorow[postittaja_email]>\n";

	if ($sahkoposti_cc != "") {
		$header .= "Cc: $sahkoposti_cc\n";
	}

	$header .= "MIME-Version: 1.0\n" ;
	$header .= "Content-Type: multipart/mixed; boundary=\"$bound\"\n" ;

	if ($content_body != "" and $laskurow["chn"] == 666) {
		if ($content_subject != "") {
			$subject = $content_subject;
		}
		$content .= "--$bound\n";
		$content .= "Content-type: text/plain; charset=iso-8859-1\n";
		$content .= "Content-Transfer-Encoding: quoted-printable\n\n";
		$content .= "$content_body\n\n";
	}

	$content .= "--$bound\n";

	if (!is_array($liite)) {
		$liite = array(0 => $liite);
	}

	if (!is_array($kutsu)) {
		$kutsu = array(0 => $kutsu);
	}

	if (!is_array($ctype)) {
		$ctype = array(0 => $ctype);
	}
	
	$liitelask = 0;
	
	foreach ($liite as $key => $value) {
		$liitelask++;
		
		if ($ctype[$key] == "TEXT") {
			$content .= "Content-Type: text/plain; name=\"".$kutsu[$key]."\"\n" ;
			$content .= "Content-Transfer-Encoding: base64\n" ;
			$content .= "Content-Disposition: attachment; filename=\"".$kutsu[$key]."\"\n\n";
		}
		elseif ($ctype[$key] == "ps") {
			$content .= "Content-Type: application/ps; name=\"".$kutsu[$key]."\"\n" ;
			$content .= "Content-Transfer-Encoding: base64\n" ;
			$content .= "Content-Disposition: attachment; filename=\"".$kutsu[$key]."\"\n\n";
		}
		elseif ($ctype[$key] == "excel") {
			if ($liitenimi == '') {
				$liitenimi = basename($liite[$key]);
			}

			$content .= "Content-Type: application/excel; name=\"".$liitenimi[$key]."\"\n" ;
			$content .= "Content-Transfer-Encoding: base64\n" ;
			$content .= "Content-Disposition: attachment; filename=\"".$liitenimi[$key]."\"\n\n";
		}
		else {
			$content .= "Content-Type: application/pdf; name=\"".$kutsu[$key].".pdf\"\n" ;
			$content .= "Content-Transfer-Encoding: base64\n" ;
			$content .= "Content-Disposition: attachment; filename=\"".$kutsu[$key].".pdf\"\n\n";
		}

		$handle  = fopen($liite[$key], "r");
		$sisalto = fread($handle, filesize($liite[$key]));
		fclose($handle);

		$content .= chunk_split(base64_encode($sisalto));
		$content .= "\n" ;
		
		if ($liitelask = count($liite)) {
			$content .= "--$bound--\n";
		} 
		else {
			$content .= "--$bound\n";
		}		
	}
	
	$subject = mb_encode_mimeheader($subject, "ISO-8859-1", "Q");
	
	$boob = mail($kukarow["eposti"], $subject, $content, $header, "-f $yhtiorow[postittaja_email]");

	if ($boob === FALSE) echo t("Sähköpostin lähetys epäonnistui").": $kukarow[eposti]<br>";

	//Nää muuttujat voi olla aika isoja joten unsetataan ne
	unset($content);
	unset($sisalto);

	if ($varaposti != '') {
		$kukarow['eposti'] = $varaposti;
	}
?>