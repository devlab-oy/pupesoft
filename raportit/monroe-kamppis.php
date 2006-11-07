<?php

require("../pdflib/phppdflib.class.php");
require("../inc/connect.inc");

echo "Haetaan asiakkaita: ";
$query = "select * from asiakas where yhtio='arwi' and fakta like '%monroe%'";
$result = mysql_query($query) or die($query);

echo mysql_num_rows($result)." lˆytyi.<br><br>";

while ($asiakasrow = mysql_fetch_array($result)) {

	$query = "select round(sum(kpl),0) pisteet, sum(rivihinta) myynti
				from lasku use index (yhtio_tila_ytunnus_lahetepvm)
				left join tilausrivi on lasku.yhtio=tilausrivi.yhtio and tilausrivi.uusiotunnus=lasku.tunnus and tilausrivi.osasto='2' and try='45'
				where lasku.yhtio='arwi'
				and tila='U' and alatila='X'
				and tapvm >= '2004-11-01'
				and tapvm <= '2005-03-31'
				and ytunnus='$asiakasrow[ytunnus]'";
	$res = mysql_query($query) or die($query);
	$row1 = mysql_fetch_array($res);
	
	$query = "select sum(rivihinta) vertailu
				from lasku use index (yhtio_tila_ytunnus_lahetepvm)
				left join tilausrivi on lasku.yhtio=tilausrivi.yhtio and tilausrivi.uusiotunnus=lasku.tunnus and tilausrivi.osasto='2' and try='45'
				where lasku.yhtio='arwi'
				and tila='U' and alatila='X'
				and tapvm >= '2003-11-01'
				and tapvm <= '2004-03-31'
				and ytunnus='$asiakasrow[ytunnus]'";
	$res = mysql_query($query) or die($query);
	$row2 = mysql_fetch_array($res);				
				
	$pdf = new PDFFile;
	$pdf->enable('import');

	ob_start(); // estet‰‰n outputti

	$filename = "/home/pupesoft/monroe.pdf"; // monroe kamppis sivu luetaan pohjaksi
	if (!$handle = fopen($filename, "r")) die("filen luku ep‰onnistui!");	
	$sisalto  = fread($handle, filesize($filename));
	fclose($handle);

	$pdf->import->append($sisalto);
	$pages = $pdf->import->get_pages();

	ob_end_clean(); // outputti takas p‰‰lle

	$pdf->set_default('margin-top', 	0);
	$pdf->set_default('margin-bottom', 	0);
	$pdf->set_default('margin-left', 	0);
	$pdf->set_default('margin-right', 	0);

	$rectparam1["width"] = 0.3;
	$rectparam2["width"] = 2;

	$pien["font"] = "Helvetica";	// fontti
	$pien["height"] = 9.0;			// fonttikoko

	$pienb["font"] = "Helvetica-Bold";	// fontti
	$pienb["height"] = 9.0;				// fonttikoko

	$norm["font"] = "Helvetica";	// fontti
	$norm["height"] = 15.0;			// fonttikoko

	$bold["font"] = "Helvetica";	// fontti
	$bold["height"] = 21.0;			// fonttikoko

	$iso["font"] = "Helvetica-Bold";	// fontti
	$iso["height"] = 24.0;				// fonttikoko

	// vaihtoehtoisia fontteja on:
	//
	// "Courier"
	// "Courier-Bold"
	// "Courier-Oblique"
	// "Courier-BoldOblique"
	// "Helvetica"
	// "Helvetica-Bold"
	// "Helvetica-Oblique"
	// "Helvetica-BoldOblique"
	// "Times-Roman"
	// "Times-Bold"
	// "Times-Italic"
	// "Times-BoldItalic"
	// "Symbol"
	// "ZapfDingbats"

	// t‰ytet‰‰n perusinfot
	$pdf->draw_text(0, 630, "Monroe-Arwidson kampanja 2004-2005", $pages[0], $iso); // x,y,teksti,sivu,fontti
	$pdf->draw_text(0, 600, "Kampanjaseurannan lopulliset tulokset", $pages[0], $iso); // x,y,teksti,sivu,fontti

	$pdf->draw_text(0, 550, "Suuri Monroe-Arwidson kampanja k‰ynnistyi eritt‰in vilkkaasti 1.11.2004.", $pages[0], $norm); // x,y,teksti,sivu,fontti
	$pdf->draw_text(0, 530, "Kiitos kaikille kampanjaan rekisterˆityneille ja tsemppi‰ iskareitten", $pages[0], $norm); // x,y,teksti,sivu,fontti
	$pdf->draw_text(0, 510, "myyntiin!", $pages[0], $norm); // x,y,teksti,sivu,fontti

	$pdf->draw_text(0, 480, "Kampanja p‰‰ttyy 31.3.2005, joten kaikki rekisterˆityneet palkitaan", $pages[0], $norm); // x,y,teksti,sivu,fontti
	$pdf->draw_text(0, 460, "ruhtinaallisesti. Jaossa on upeita Sony-elektroniikkatuotteita kaikille", $pages[0], $norm); // x,y,teksti,sivu,fontti
	$pdf->draw_text(0, 440, "tarvittavan m‰‰r‰n pisteit‰ ker‰nneille Monroe-asiakkaillemme.", $pages[0], $norm); // x,y,teksti,sivu,fontti
	$pdf->draw_text(0, 420, "Jokaista kampanjan aikana 1.11.2004-31.3.2005 ostamaasi", $pages[0], $norm); // x,y,teksti,sivu,fontti
	$pdf->draw_text(0, 400, "iskunvaimmenninta kohti saat siis yhden (1) pisteen, joita ker‰‰m‰ll‰ saat", $pages[0], $norm); // x,y,teksti,sivu,fontti
	$pdf->draw_text(0, 380, "kampanjan p‰‰tytty‰ valita Sony-elektroniikkatuotteista sen/ne, joihin", $pages[0], $norm); // x,y,teksti,sivu,fontti
	$pdf->draw_text(0, 360, "pisteesi oikeuttavat.", $pages[0], $norm); // x,y,teksti,sivu,fontti

	$pdf->draw_text(0, 320, "Ohessa viralliset v‰liaikatiedot, eli oma pistetilanne t‰h‰n menness‰.", $pages[0], $norm); // x,y,teksti,sivu,fontti

	$pdf->draw_text(0, 280, "Yrityksen nimi:", $pages[0], $norm); // x,y,teksti,sivu,fontti
	$pdf->draw_text(0, 240, "Asiakasnumero:", $pages[0], $norm); // x,y,teksti,sivu,fontti
	$pdf->draw_text(0, 200, "Pisteenne 1.11.2004-31.3.2005:", $pages[0], $norm); // x,y,teksti,sivu,fontti
	$pdf->draw_text(0, 160, "Monroe-ostonne 11/2003 - 03/2004:", $pages[0], $norm); // x,y,teksti,sivu,fontti
	$pdf->draw_text(0, 145, "(Vertailuluku)", $pages[0], $pien); // x,y,teksti,sivu,fontti
	$pdf->draw_text(0, 120, "Monroe-ostonne 1.11.2004-31.3.2005:", $pages[0], $norm); // x,y,teksti,sivu,fontti
	$pdf->draw_text(0, 105, "(Eurom‰‰r‰isesti eniten edellisen vuoden kampanja-aikaa vastaavaan ajankohtaan (vertailuluku 11/2003 -", $pages[0], $pien); // x,y,teksti,sivu,fontti
	$pdf->draw_text(0,  93, "03/2004 verrattuna ostojaan kasvattaneet palkitaan kampanjan p‰‰tytty‰, kun lopullinen summa on selvill‰.)", $pages[0], $pien); // x,y,teksti,sivu,fontti

	//muuttuvat tiedot
	$pdf->draw_text(135, 280, "$asiakasrow[nimi]", 		$pages[0], $bold); // x,y,teksti,sivu,fontti
	$pdf->draw_rectangle(278,130,278,450, 				$pages[0], $rectparam1); // y,x,y,x vaakaviiva
	$pdf->draw_text(135, 240, "$asiakasrow[ytunnus]", 	$pages[0], $bold); // x,y,teksti,sivu,fontti
	$pdf->draw_rectangle(238,130,238,450, 				$pages[0], $rectparam1); // y,x,y,x vaakaviiva
	$pdf->draw_text(265, 200, "$row1[pisteet]", 		$pages[0], $bold); // x,y,teksti,sivu,fontti
	$pdf->draw_rectangle(198,260,198,450, 				$pages[0], $rectparam1); // y,x,y,x vaakaviiva
	$pdf->draw_text(265, 160, "$row2[vertailu] EUR",	$pages[0], $bold); // x,y,teksti,sivu,fontti
	$pdf->draw_rectangle(158,260,158,450, 				$pages[0], $rectparam1); // y,x,y,x vaakaviiva
	$pdf->draw_text(265, 120, "$row1[myynti] EUR", 		$pages[0], $bold); // x,y,teksti,sivu,fontti
	$pdf->draw_rectangle(118,260,118,450, 				$pages[0], $rectparam1); // y,x,y,x vaakaviiva

	//yhteystiedot
	$pdf->draw_rectangle(66,0,66,450, 				$pages[0], $rectparam2); // y,x,y,x vaakaviiva
	$pdf->draw_text(0,  40, "OY ARWIDSON AB", 		$pages[0], $pienb); // x,y,teksti,sivu,fontti
	$pdf->draw_text(0,  30, "PL 30, 02271 ESPOO",	$pages[0], $pien); // x,y,teksti,sivu,fontti
	$pdf->draw_text(0,  20, "Puh. 09-887 11", 		$pages[0], $pien); // x,y,teksti,sivu,fontti
	$pdf->draw_text(0,  10, "Fax. 09-887 1330", 	$pages[0], $pien); // x,y,teksti,sivu,fontti
	$pdf->draw_text(0,   0, "www.arwidson.fi", 		$pages[0], $pien); // x,y,teksti,sivu,fontti

	$pdf->draw_text(160, 20, "LUOTETTAVISSA MERKEISSƒ", 	$pages[0], $norm); // x,y,teksti,sivu,fontti

	//keksit‰‰n uudelle failille joku varmasti uniikki nimi:
	$pdffilenimi = "/tmp/monroe-".md5(uniqid(rand(),true)).".pdf";

	//kirjoitetaan pdf faili levylle..
	$fh = fopen($pdffilenimi, "w");
	if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus ep‰onnistui $pdffilenimi");
	fclose($fh);

	// meilin tiedot, kenelle l‰hetet‰‰n
	if (trim($asiakasrow['email'])!='' and strpos($asiakasrow['fakta'], "monroef") === FALSE) {
		//$email = trim($asiakasrow['email']);
		$email     = "tony.selkamo@arwidson.fi";
	}
	else {
		$email     = "tony.selkamo@arwidson.fi";
	}
	
	$otsikko   = "Monroe-Arwidson kampanjaseuranta";
	$failinimi = "monroe-arwidson-$asiakasrow[ytunnus].pdf";

	$bound     = uniqid(time()."_") ;

	$header    = "From: <$yhtiorow[postittaja_email]>\r\n";
	$header   .= "MIME-Version: 1.0\r\n" ;
	$header   .= "Content-Type: multipart/mixed; boundary=\"$bound\"\r\n" ;

	$content   = "--$bound\r\n";
	$content  .= "Content-Type: application/pdf; name=\"$failinimi\"\r\n" ;
	$content  .= "Content-Transfer-Encoding: base64\r\n" ;
	$content  .= "Content-Disposition: inline; filename=\"$failinimi\"\r\n\r\n";

	$handle  = fopen($pdffilenimi, "r");
	$sisalto = fread($handle, filesize($pdffilenimi));
	fclose($handle);

	$content .= chunk_split(base64_encode($sisalto));
	$content .= "\r\n" ;
	$content .= "--$bound\r\n";
	
	if ($asiakasrow['fakta'] == 'monroe' and $email!='tony.selkamo@arwidson.fi') {
		$boob = mail("tony.selkamo@arwidson.fi", $otsikko, $content, $header);
		if ($boob===FALSE) {
			echo "Meilin l‰hetys ep‰onnistui tony.selkamo@arwidson.fi! Asiakas $asiakasrow[nimi].<br>";
		}
		else {
			echo "Asiakas $asiakasrow[nimi] k‰sitelty. Meili l‰hetetty tony.selkamo@arwidson.fi.<br>";
		}
	}

	$boob = mail($email, $otsikko, $content, $header);

	if ($boob===FALSE) {
		echo "Meilin l‰hetys ep‰onnistui $email! Asiakas $asiakasrow[nimi].<br>";
	}
	else {
		echo "Asiakas $asiakasrow[nimi] k‰sitelty. Meili l‰hetetty $email.<br>";
	}

	//poistetaan tmp file samantien kuleksimasta...
	system("rm -f $pdffilenimi");
	
	flush();
}

?>