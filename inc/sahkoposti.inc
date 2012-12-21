<?php

	### !!! HUOMHUOM !!! ###
	### Tämä ominaisuus on deprikoitu, käytä functions.inc:ssä määriteltyä pupesoft_sahkoposti -funktiota sähköpostin lähettämiseen!! ###

	// Tämä ohjelma haluaa $liite muuttujan jossa on tiedostonnimi joka liitetään
	// Jos liite halutaan nimetä uudestaan annetaan uusi nimi $liitenimi muuttujassa
	// Ja $kutsu jossa on tiedoston kuvaus
	// $ctype muuttuja jossa on liitettävän tiedoston MIME tyyppi
	// $content_body on viestin body
	// $subject tai $content_subject on poikkeava subject

	///*Kasataan käyttäjälle lähetettävä meili *///
	//tässa on kaikki failit jotka tarvitaan

	$varaposti 		= '';
	$varaposti_trig = FALSE;

	if (!isset($subject) or trim($subject) == '') {
		$subject = "$yhtiorow[nimi] - $kutsu";
	}

	if (isset($content_subject) and trim($content_subject) != "") {
		$subject = $content_subject;
	}

	if (substr($komento, 0, 12) == 'asiakasemail') {
		$varaposti_trig 	= TRUE;
		$varaposti 			= $kukarow['eposti'];
		$kukarow['eposti'] 	= substr($komento, 12);
	}

	$bound = uniqid(time()."_") ;

	$header  = "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n";

	if (isset($sahkoposti_cc) and $sahkoposti_cc != "") {
		$header .= "Cc: $sahkoposti_cc\n";
	}

	$header .= "MIME-Version: 1.0\n" ;
	$header .= "Content-Type: multipart/mixed; boundary=\"$bound\"\n" ;

	if (isset($content_body) and $content_body != "") {
		$content .= "--$bound\n";
		if ($ctype == "html") {
			$content .= "Content-type: text/html; charset=\"iso-8859-1\"\n";
		}
		else {
			$content .= "Content-type: text/plain; charset=iso-8859-1\n";
		}
		$content .= "Content-Transfer-Encoding: quoted-printable\n\n";
		$content .= "$content_body\n\n";
	}

	if (!isset($content)) {
		$content = "";
	}
	if (!isset($liite)) {
		$liite = array(0 => "");
	}

	if (!isset($kutsu)) {
		$kutsu = array(0 => "");
	}

	if (!isset($ctype)) {
		$ctype = array(0 => "");
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
		elseif ($ctype[$key] == "jpg" or $ctype[$key] == "JPG" or $ctype[$key] == "jpeg" or $ctype[$key] == "JPEG") {
			$content .= "Content-Type: image/jpeg; name=\"".$kutsu[$key]."\"\n" ;
			$content .= "Content-Transfer-Encoding: base64\n" ;
			$content .= "Content-Disposition: attachment; filename=\"".$kutsu[$key]."\"\n\n";
		}
		elseif ($ctype[$key] == "png" or $ctype[$key] == "PNG") {
			$content .= "Content-Type: image/png; name=\"".$kutsu[$key]."\"\n" ;
			$content .= "Content-Transfer-Encoding: base64\n" ;
			$content .= "Content-Disposition: attachment; filename=\"".$kutsu[$key]."\"\n\n";
		}
		elseif ($ctype[$key] == "gif" or $ctype[$key] == "GIF") {
			$content .= "Content-Type: image/gif; name=\"".$kutsu[$key]."\"\n" ;
			$content .= "Content-Transfer-Encoding: base64\n" ;
			$content .= "Content-Disposition: attachment; filename=\"".$kutsu[$key]."\"\n\n";
		}
		elseif ($ctype[$key] == "tif" or $ctype[$key] == "TIF" or $ctype[$key] == "tiff" or $ctype[$key] == "TIFF") {
			$content .= "Content-Type: image/tiff; name=\"".$kutsu[$key]."\"\n" ;
			$content .= "Content-Transfer-Encoding: base64\n" ;
			$content .= "Content-Disposition: attachment; filename=\"".$kutsu[$key]."\"\n\n";
		}
		elseif ($ctype[$key] == "bmp" or $ctype[$key] == "BMP") {
			$content .= "Content-Type: image/bmp; name=\"".$kutsu[$key]."\"\n" ;
			$content .= "Content-Transfer-Encoding: base64\n" ;
			$content .= "Content-Disposition: attachment; filename=\"".$kutsu[$key]."\"\n\n";
		}
		elseif ($ctype[$key] == "excel") {
			if (count($liitenimi) == 0) {
				$liitenimi[$key] = basename($liite[$key]);
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

		if ($liitelask == count($liite)) {
			$content .= "--$bound--\n";
		}
		else {
			$content .= "--$bound\n";
		}
	}

	$boob = mail($kukarow["eposti"], mb_encode_mimeheader($subject, "ISO-8859-1", "Q"), $content, $header, "-f $yhtiorow[postittaja_email]");

	if ($boob === FALSE) echo t("Sähköpostin lähetys epäonnistui").": $kukarow[eposti]<br>";

	//Nää muuttujat voi olla aika isoja joten unsetataan ne
	unset($content);
	unset($content_body);
	unset($content_subject);
	unset($ctype);
	if (strpos($_SERVER['SCRIPT_NAME'], "tulostakopio.php") === FALSE) unset($komento);
	unset($kutsu);
	unset($liite);
	unset($sahkoposti_cc);
	unset($sisalto);
	unset($subject);

	if ($varaposti_trig) {
		$kukarow['eposti'] = $varaposti;
	}

?>