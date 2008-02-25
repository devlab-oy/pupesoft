<?php
	// Tämä ohjelma haluaa $liite muuttujan jossa on tiedostonnimi joka liitetään
	// Ja $kutsu jossa on tiedoston kuvaus
	// $ctype muuttuja jossa on liitettävän tiedoston MIME tyyppi

	///*Kasataan käyttäjälle lähetettävä meili *///
	//tässa on kaikki failit jotka tarvitaan

	$varaposti = '';

	if (substr($komento,0,12) == 'asiakasemail') {
		$varaposti = $kukarow['eposti'];
		$kukarow['eposti'] = substr($komento,12);
	}

	$bound = uniqid(time()."_") ;

	$header  = "From: <$yhtiorow[postittaja_email]>\n";
	$header .= "MIME-Version: 1.0\n" ;
	$header .= "Content-Type: multipart/mixed; boundary=\"$bound\"\n" ;

	$content .= "--$bound\n";

	if ($ctype == "TEXT") {
		$content .= "Content-Type: text/plain; name=\"".$kutsu."\"\n" ;
		$content .= "Content-Transfer-Encoding: base64\n" ;
		$content .= "Content-Disposition: inline; filename=\"".$kutsu."\"\n\n";
	}
	elseif ($ctype == "ps") {
		$content .= "Content-Type: application/ps; name=\"".$kutsu."\"\n" ;
		$content .= "Content-Transfer-Encoding: base64\n" ;
		$content .= "Content-Disposition: inline; filename=\"".$kutsu."\"\n\n";
	}
	elseif ($ctype == "excel") {
		$content .= "Content-Type: application/excel; name=\"".$liite."\"\n" ;
		$content .= "Content-Transfer-Encoding: base64\n" ;
		$content .= "Content-Disposition: inline; filename=\"".$liite."\"\n\n";
	}
	else {
		$content .= "Content-Type: application/pdf; name=\"".$kutsu.".pdf\"\n" ;
		$content .= "Content-Transfer-Encoding: base64\n" ;
		$content .= "Content-Disposition: inline; filename=\"".$kutsu.".pdf\"\n\n";
	}

	$handle  = fopen($liite, "r");
	$sisalto = fread($handle, filesize($liite));
	fclose($handle);

	$content .= chunk_split(base64_encode($sisalto));
	$content .= "\n" ;
	
	$boob = mail($kukarow["eposti"],  "$yhtiorow[nimi] - $kutsu", $content, $header, "-f $yhtiorow[postittaja_email]");

	if ($boob === FALSE) echo t("Sähköpostin lähetys epäonnistui").": $kukarow[eposti]<br>";

	//Nää muuttujat voi olla aika isoja joten unsetataan ne
	unset($content);
	unset($sisalto);

	if ($varaposti != '') {
		$kukarow['eposti'] = $varaposti;
	}
?>
