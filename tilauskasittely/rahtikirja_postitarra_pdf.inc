<?php

	// nyt on kaikki tiedot rahtikirjaa varten haettu..
	//
	// arrayt:
	// toitarow, otsikot, pakkaus, kilot, kollit, kuutiot, lavametri, vakit
	// $rakir_row:sta löytyy asiakkaan tiedot ja $rivi:stä ytunnus
	//
	// muuttujat:
	// otunnukset, rahdinmaksaja, rahtihinta, pvm, toimitustapa, kolliyht, kilotyht, kuutiotyht, kirjoitin
	// jv tapauksissa on myös yhteensa, summa, jvhinta, lasno ja viite muuttujat
	//
	// tulostetaan rahtikirja

	if ($phpnimi == "rahtikirja_custom.php") {
		$postirow = $osoitelappurow;
		$rakir_row = $osoitelappurow;
	}
	else {
		$query = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus in ($otunnukset) order by tunnus limit 1";
		$tempr = mysql_query($query) or pupe_error($query);
		$postirow = mysql_fetch_array($tempr);
	}

	$query = "SELECT * from asiakas where yhtio='$kukarow[yhtio]' and ytunnus='$postirow[liitostunnus]'";
	$tempr = mysql_query($query) or pupe_error($query);
	$asiakasrow = mysql_fetch_array($tempr);

	if ($postirow['rahtisopimus']=='') $postirow['rahtisopimus'] = "000000";
	if ($toitarow['sopimusnro']=='')   $toitarow['sopimusnro']	 = "000000";

	// postiennakko
	if ($rakir_row["jv"] != '' or $mehto['jv'] != '') {
		$postiennakkomaara  = "$yhteensa $postirow[valkoodi]";
		$postiennakkotilino = "$yhtiorow[pankkitili1]";
		$postiennakkoviite  = "$viite";
	}
	else {
		$postiennakkomaara  = "";
		$postiennakkotilino = "";
		$postiennakkoviite  = "";
	}

	$x = array();
	if ($yhteensa!='')					$x[]='1'; // postiennakko
	if ($kollityht>1)    				$x[]='2'; // monipakettilähetys
	if ($rakir_row['merahti']!='K')		$x[]='3'; // vastaanottaja maksaa
	if (count($vakit)>0)				$x[]='4'; // erikoiskäsiteltävää
	if ($toitarow['lauantai']!='')		$x[]='6'; // lauantaijakelu
	if ($toitarow['kuljyksikko']!='')	$x[]='7'; // kuljetusyksikkökuljetus

	$keku = "16";
	if (strpos($toitarow['selite'], "9"))  $keku="9";
	if (strpos($toitarow['selite'], "00")) $keku="00";
	if (strpos($toitarow['selite'], "14")) $keku="14";
	if (strpos($toitarow['selite'], "21")) $keku="21";

	$keltainen = "Keltainen";
	$kuljetus  = "Kuljetus";

	if ($keku=="00") {
		$keltainen = "";
		$kuljetus  = "EXP";
	}

	// konvertoidaan keku numero postin koodiksi
	switch ($keku) {
		case "00":
			$tuoteviiva1="2W2124";
			break;
		case "9":
			$tuoteviiva1="2W2101";
			break;
		case "14":
			$tuoteviiva1="2W2102";
			break;
		case "16":
			$tuoteviiva1="2W2103";
			break;
		case "21":
			$tuoteviiva1="2W2104"; // tämä on varamaan väärin, mutta en jaksa etsiä mikä on oikea 
			break;		
	}

	if ($rahdinmaksaja == 'Lähettäjä') {
		$rahdinmaksaja		= "";
		$rahdinmaksajan_nr 	= "";
	}
	elseif(trim($rakir_row['rahtisopimus']) != "") {
		$rahdinmaksaja		= "Maksaja muu kuin lähettäjä. ";
		$rahdinmaksajan_nr 	= $rakir_row['rahtisopimus'];	
	}
	else {
		$rahdinmaksaja		= "Maksaja muu kuin lähettäjä. ";
		$rahdinmaksajan_nr 	= "";
	}

	require_once("viivakoodi/Barcode.php");
	require_once("pdflib/phppdflib.class.php");
	
	//PDF parametrit
	$pdf = new pdffile;
	$pdf->set_default('margin-top', 	0);
	$pdf->set_default('margin-bottom', 	0);
	$pdf->set_default('margin-left', 	0);
	$pdf->set_default('margin-right', 	0);

	$rectparam["width"] 	= 0.3;

	$norm["height"] 		= 8.5;
	$norm["font"] 			= "Helvetica";

	$pieni["height"] 		= 6;
	$pieni["font"] 			= "Helvetica";

	$pienibold["height"] 	= 7;
	$pienibold["font"] 		= "Helvetica-Bold";

	$iso["height"] 			= 9;
	$iso["font"] 			= "Helvetica";

	$isobold["height"] 		= 10.5;
	$isobold["font"] 		= "Helvetica-Bold";

	$huge["height"] = 20;
	$huge["font"] = "Helvetica-Bold";

	// defaultteja
	$kala = 575;
	$lask = 1;
	$sivu = 1;

	$tulostakolli = $tulostuskpl;

	// tulostetaan niin monta lappua kun on kollejakin
	for ($tulostuskpl=1; $tulostuskpl<=$tulostakolli; $tulostuskpl++) {

		$kuka    = sprintf("%02d",$tulostuskpl); 		// laitetaan tulostuskpl alkuun niin jokalapulla on uniikki viiva
		$oma     = sprintf("%09d",$rtunnus); 			// 9, pitkä numero tulee rahtikirja taulun tunnuksesta, pitää olaa uniikki
	
		$toitarow['sopimusnro'] = sprintf("%06d",$toitarow['sopimusnro']);	// sopimunumeron tulee olla kuus pitkä

		// tehdään viivakoodi (jonne laitetaan aina meidän sopimusnumero)	
		$viiva2  = "JJFI";
		$viiva2 .= $toitarow['sopimusnro'];
		$sopnro  = $toitarow['sopimusnro']; // käytetään edi sanomassa
		$viiva2 .= $kuka.$oma;

		// erotellaan spacella jokainen rahtikirjanumero
		$rahtikirjanro .= "$viiva2 ";		
	
		// tehdään pdf:n uusi sivu
		$firstpage = $pdf->new_page("a5");
	
		//tehdään JJFI viivakoodi
		$nimi = "/tmp/".md5(uniqid(rand(),true)).".jpg";

		imagejpeg(Image_Barcode::draw($viiva2, 'code128', 'jpg', false, 3, 170), $nimi);

		$fh = fopen($nimi, "r");
		$data = fread($fh, filesize($nimi));
		fclose($fh);
	
		$image = $pdf->jfif_embed($data);
		
		$logoparam['scale'] = 122/282;
		$pdf->image_place($image, 275, 30, $firstpage, $logoparam);		
		system("rm -f $nimi");

		// postin koodiksi
		$nimi = "/tmp/".md5(uniqid(rand(),true)).".jpg";

		imagejpeg(Image_Barcode::draw($tuoteviiva1, 'code128', 'jpg', false, 3, 130), $nimi);

		$fh = fopen($nimi, "r");
		$data = fread($fh, filesize($nimi));
		fclose($fh);
	
		$image = $pdf->jfif_embed($data);
		
		$logoparam['scale'] = 122/282;
		$pdf->image_place($image, 540, 255, $firstpage, $logoparam);
		system("rm -f $nimi");
	
		// sitten aletaan piirtämään itse PDF sisältöä
		$pdf->draw_text(100,  570,  $keltainen 									,$firstpage, $isobold);
		$pdf->draw_text(100,  560,  $kuljetus									,$firstpage, $isobold);
		$pdf->draw_text(150,  562,  $keku	 									,$firstpage, $huge);

		$pdf->draw_text(18,  540,  "Lähettäjä Avsändare From", 					$firstpage, $pieni); // x,y
		$pdf->draw_text(18,  530, $yhtiorow["nimi"],	 						$firstpage, $norm);
		$pdf->draw_text(18,  520, $yhtiorow["osoite"],	 						$firstpage, $norm);
		$pdf->draw_text(18,  510, $yhtiorow["postino"]." ".$yhtiorow["postitp"],$firstpage, $norm);

		$pdf->draw_text(18,  490,  "Vastaanottaja Addressat To", 				$firstpage, $pieni); // x,y	
		$pdf->draw_text(18,  480,  $rakir_row["toim_nimi"],	 					$firstpage, $iso);
		$pdf->draw_text(18,  470,  $rakir_row["toim_nimitark"], 				$firstpage, $iso);
		$pdf->draw_text(18,  460,  $rakir_row["toim_osoite"],	 				$firstpage, $iso);
		$pdf->draw_text(18,  430,  $rakir_row["toim_postino"]." ".$rakir_row["toim_postitp"],$firstpage, $isobold);
	
		if ($asiakasrow["puhelin"] != '') {
			$pdf->draw_text(100,  490,  "p.".$asiakasrow["puhelin"],			$firstpage, $norm);
		}
		
		// tuoteviivakoodin alle
		$pdf->draw_text(320, 532,  $tuoteviiva1, 								$firstpage, $pieni); // x,y
		$pdf->draw_text(345, 510,  "EDI",		 								$firstpage, $huge); // x,y
		$pdf->draw_text(250, 490,  "Jätetty postin kuljetettavaksi Överlämnat till Posten", $firstpage, $pieni); // x,y
		$pdf->draw_text(250, 478,  date("d.m.Y"), 								$firstpage, $norm); // x,y
	
		$pdf->draw_rectangle(495,245,450,245, 									$firstpage, $rectparam); // y,x,y,x pystyviiva
		$pdf->draw_rectangle(472,245,472,400, 									$firstpage, $rectparam); // y,x,y,x vaakaviiva
		$pdf->draw_rectangle(472,322,450,322, 									$firstpage, $rectparam); // y,x,y,x pystyviiva
		$pdf->draw_rectangle(450,245,450,400, 									$firstpage, $rectparam); // y,x,y,x vaakaviiva
	
		$pdf->draw_text(255, 458,  $kilotyht,	 								$firstpage, $norm); // x,y
		$pdf->draw_text(312, 458,  "kg",		 								$firstpage, $pieni); // x,y
		$pdf->draw_text(330, 458,  $kuutiotyht, 								$firstpage, $norm); // x,y
		$pdf->draw_text(390, 458,  "m3",		 								$firstpage, $pieni); // x,y
	
		// asiakkaan postitoimipaikan alle
		$pdf->draw_rectangle(428,18,428,300, 									$firstpage, $rectparam); // y,x,y,x vaakaviiva
		$pdf->draw_rectangle(400,18,400,300, 									$firstpage, $rectparam); // y,x,y,x vaakaviiva
		$pdf->draw_rectangle(380,18,380,400, 									$firstpage, $rectparam); // y,x,y,x vaakaviiva
		$pdf->draw_rectangle(428,300,400,300, 									$firstpage, $rectparam); // y,x,y,x pystyviiva
		$pdf->draw_rectangle(428,280,400,280, 									$firstpage, $rectparam); // y,x,y,x pystyviiva	
		$pdf->draw_rectangle(428,110,400,110, 									$firstpage, $rectparam); // y,x,y,x pystyviiva
		$pdf->draw_rectangle(400,220,380,220, 									$firstpage, $rectparam); // y,x,y,x pystyviiva
		$pdf->draw_text(18,  422,  "Lisäpalvelut Tilläggstjänster",				$firstpage, $pieni); // x,y
		$pdf->draw_text(282, 422,  "MPS",										$firstpage, $pieni); // x,y
		$pdf->draw_text(112, 422,  "Maksaja muu kuin lähettäjä Betalaren annan än avsändaren",$firstpage, $pieni); // x,y
		$pdf->draw_text(112, 408,  "Maksajan sopimustunnus",					$firstpage, $pieni); // x,y
		$pdf->draw_text(112, 402,  "Betalarens avtalskod",						$firstpage, $pieni); // x,y
		$pdf->draw_text(18,  394,  "Postiennakkomäärä Postförskottsbelopp",		$firstpage, $pieni); // x,y
		$pdf->draw_text(222, 394,  "Tilinumero Kontonummer",					$firstpage, $pieni); // x,y
		$pdf->draw_text(18,  374,  "Viitenumero Referensnummer",				$firstpage, $pieni); // x,y

		// vähä arvoja
		$pdf->draw_text(190, 405,  $rahdinmaksajan_nr,							$firstpage, $isobold); // x,y
		$pdf->draw_text(18,  384,  $postiennakkomaara,							$firstpage, $iso); // x,y
		$pdf->draw_text(222, 384,  $postiennakkotilino,							$firstpage, $iso); // x,y
		$pdf->draw_text(112, 370,  $postiennakkoviite,							$firstpage, $iso); // x,y
		$pdf->draw_text(285, 405,  $kollityht,									$firstpage, $iso); // x,y
	
		// lisäpalvelut laatikot
		$pdf->draw_rectangle(402,18,420,38, 									$firstpage, $rectparam); // y,x,y,x 
		$pdf->draw_rectangle(402,40,420,60, 									$firstpage, $rectparam); // y,x,y,x 
		$pdf->draw_rectangle(402,62,420,82, 									$firstpage, $rectparam); // y,x,y,x 
		$pdf->draw_rectangle(402,84,420,104, 									$firstpage, $rectparam); // y,x,y,x 
		$pdf->draw_text(23,  406,  $x[0],										$firstpage, $iso); // x,y
		$pdf->draw_text(45,  406,  $x[1],										$firstpage, $iso); // x,y
		$pdf->draw_text(67,  406,  $x[2],										$firstpage, $iso); // x,y
		$pdf->draw_text(89,  406,  $x[3],										$firstpage, $iso); // x,y	
	
		// viiva viivakooditekstin ylä ja alapuolelle
		$pdf->draw_rectangle(260,18,260,400, 									$firstpage, $rectparam); // y,x,y,x
		$pdf->draw_rectangle(365,18,365,400, 									$firstpage, $rectparam); // y,x,y,x
		$pdf->draw_text(160, 262,  $viiva2,										$firstpage, $pieni); // x,y
		$pdf->draw_text(160, 357,  $viiva2,										$firstpage, $pieni); // x,y
	
		// viivakoodin alapuolella vasen laita
		$pdf->draw_text(18,  250,  "SAAPUMISILMOITUS ANKOMSTAVI", 				$firstpage, $pienibold);	
		$pdf->draw_text(18,  242,  "Lähetys on noudettavissa Postistanne. Olkaa hyvä ja ottakaa ilmoitus mukaanne. Lisätietoja: 0200 71000, www.posti.fi", $firstpage, $pieni); // x,y
		$pdf->draw_text(18,  234,  "Ni kan hämta försändelsen på Ert postkontor. Vänligen ta med Er denna avi. Mera information: 0200 27100, www.posti.fi", $firstpage, $pieni); // x,y
		$pdf->draw_text(18,  226,  "Säilytysaika on 2 täyttä kalenteriviikkoa. Kvarligger 2 hela kalenderveckor.", $firstpage, $pieni); // x,y
	
		$pdf->draw_text(18,  215,  "Lähettäjä Avsändare From", 					$firstpage, $pieni); // x,y
		$pdf->draw_text(18,  206, $yhtiorow["nimi"],	 						$firstpage, $norm);
		$pdf->draw_text(18,  197, $yhtiorow["osoite"],	 						$firstpage, $norm);
		$pdf->draw_text(18,  188, $yhtiorow["postino"]." ".$yhtiorow["postitp"],$firstpage, $norm);

		$pdf->draw_text(18,  180,  "Vastaanottaja Addressat To", 				$firstpage, $pieni); // x,y	
		$pdf->draw_text(18,  170,  $rakir_row["toim_nimi"],	 					$firstpage, $iso);
		$pdf->draw_text(18,  160,  $rakir_row["toim_nimitark"], 				$firstpage, $iso);
		$pdf->draw_text(18,  150,  $rakir_row["toim_osoite"],	 				$firstpage, $iso);
		$pdf->draw_text(18,  130,  $rakir_row["toim_postino"]." ".$rakir_row["toim_postitp"],$firstpage, $iso);

		if ($asiakasrow["puhelin"] != '') {
			$pdf->draw_text(100,  180,  "p.".$asiakasrow["puhelin"],			$firstpage, $norm);
		}

		// viivakoodin alapuolella vasen laita
		$pdf->draw_text(322, 250,  $keltainen." ".$kuljetus." ".$keku, 			$firstpage, $pienibold);	
		$pdf->draw_text(250, 225,  "Lisätiedot Tilläggsuppgifter", 				$firstpage, $pieni); // x,y		
		
		$apkal = 216;
		
		if ($kollityht>1) {   				
			$pdf->draw_text(250, $apkal,  "Monipakettilähetys.", 				$firstpage, $norm); // x,y
			$apkal-= 9;
		}
		if ($rahdinmaksaja != "") {
			$pdf->draw_text(250, $apkal,  "Maksaja muu kuin lähettäjä.", 		$firstpage, $norm); // x,y		
		}
		
		$pdf->draw_text(250, 194,  "Sisältö Innehåll", 							$firstpage, $pieni); // x,y	
		$pdf->draw_text(250, 186,  "MPS", 										$firstpage, $pieni); // x,y	
		$pdf->draw_text(340, 178,  "kg", 										$firstpage, $pieni); // x,y	
		$pdf->draw_text(352, 186,  "Pvm Datum", 								$firstpage, $pieni); // x,y	
		$pdf->draw_text(250, 168,  "Postiennakkomäärä Postförskottsbelopp", 	$firstpage, $pieni); // x,y	
		$pdf->draw_text(250, 150,  "Tilinumero Kontonummer", 					$firstpage, $pieni); // x,y	
		$pdf->draw_text(250, 130,  "Viitenumero Referensnummer", 				$firstpage, $pieni); // x,y	

		$pdf->draw_rectangle(192,250,192,400, 									$firstpage, $rectparam); // y,x,y,x vaakaviiva
		$pdf->draw_rectangle(174,250,174,400, 									$firstpage, $rectparam); // y,x,y,x vaakaviiva
		$pdf->draw_rectangle(156,250,156,400, 									$firstpage, $rectparam); // y,x,y,x vaakaviiva
		$pdf->draw_rectangle(136,250,136,400, 									$firstpage, $rectparam); // y,x,y,x vaakaviiva
		$pdf->draw_rectangle(192,290,174,290, 									$firstpage, $rectparam); // y,x,y,x pystyviiva
		$pdf->draw_rectangle(192,350,174,350, 									$firstpage, $rectparam); // y,x,y,x pystyviiva
	
		// ja vähä arvoja
		$pdf->draw_text(255, 205,  "",											$firstpage, $iso); // x,y // tää on pikku lisätietoja kenttä JJFI koodin alla vasemmalla
		$pdf->draw_text(255, 178,  $kollityht,									$firstpage, $iso); // x,y
		$pdf->draw_text(297, 178,  $kilotyht,									$firstpage, $iso); // x,y
		$pdf->draw_text(357, 178,  date("d.m.Y"),								$firstpage, $iso); // x,y
		$pdf->draw_text(255, 160,  $postiennakkomaara,							$firstpage, $iso); // x,y
		$pdf->draw_text(255, 140,  $postiennakkotilino,							$firstpage, $iso); // x,y
		$pdf->draw_text(255, 122,  $postiennakkoviite,							$firstpage, $iso); // x,y

		// viiva alle
		$pdf->draw_rectangle(118,18,118,400, 									$firstpage, $rectparam); // y,x,y,x vaakaviiva
								
		// Lisätiedot
		$pdf->draw_text(18, 110,  "Kuittaus ja nimen selvennys Kvittering och namnförtydligande",	$firstpage, $pieni); // x,y		
		$pdf->draw_text(40, 80,   ".               .                        klo kl.",				$firstpage, $pieni); // x,y
		$pdf->draw_text(18, 70,   "Lisätiedot Tilläggsuppgifter",									$firstpage, $pieni); // x,y		
				
		$komm = "";
		
		if ($kollityht > 1) {   				
			$komm .= "MONIPAKETTILÄHETYS.\n";
		}
		if ($rahdinmaksaja != "") {
			$komm .= "MAKSAJA MUU KUIN LÄHETTÄJÄ.\n";
		}
		if (is_array($vakit)) {
			foreach($vakit as $vak) {
				$komm .= "$vak\n";
			}
		}
		
		if (trim($komm) != '') {
			$kommentit = explode("\n", trim($komm));

			$pohja  = 60;
			
			for ($o = 0; $o <= 5; $o++) {
				$pdf->draw_text(18, $pohja, $kommentit[$o], $firstpage, $isobold);	
				$pohja -= 10;
			}
		}		
		
		// koko kortin ympärille border
		$pdf->draw_rectangle(0, 0,  598, 418, $firstpage, $rectparam);

		//lähetetään postin edisanoma
		if ($tulostakopio == "") {
			require("postiedi.inc");
		}
	}

	if ($tulostakolli > 0) {
		//keksitään uudelle failille joku varmasti uniikki nimi:
		$pdffilenimi = "/tmp/rahtikirja-".md5(uniqid(rand(),true)).".pdf";

		//kirjoitetaan pdf faili levylle..
		$fh = fopen($pdffilenimi, "w");
		if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus epäonnistui $pdffilenimi");
		fclose($fh);

		//tulostetaan PDF
		if ($kirjoitin == 'email') {
			$liite = $pdffilenimi;
			$kutsu = "Postitarra";
		
			require("inc/sahkoposti.inc");
		}
		else {
			$line = exec("$kirjoitin $pdffilenimi");
		}

		//poistetaan tmp file samantien kuleksimasta...
		system("rm -f $pdffilenimi");		
	}

?>