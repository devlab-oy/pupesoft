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
	
	$query = "select * from lasku where yhtio='$kukarow[yhtio]' and tunnus in ($otunnukset) order by tunnus limit 1";
	$tempr = mysql_query($query) or pupe_error($query);
	$postirow = mysql_fetch_array($tempr);
	
	$query = "select * from asiakas where yhtio='$kukarow[yhtio]' and ytunnus='$postirow[liitostunnus]'";
	$tempr = mysql_query($query) or pupe_error($query);
	$asiakasrow = mysql_fetch_array($tempr);
	
	if ($postirow['rahtisopimus']=='') $postirow['rahtisopimus'] = "000000";
	if ($toitarow['sopimusnro']=='')   $toitarow['sopimusnro']   = "000000";
	
	if ($rahdinmaksaja=='Vastaanottaja') $rahdinmaksajan_nr = $postirow['rahtisopimus'];
	if ($rahdinmaksaja=='Vastaanottaja') $rahdinmaksaja = $rakir_row["nimi"];
	
	if ($rahdinmaksaja=='Lähettäjä') $rahdinmaksajan_nr = $toitarow['sopimusnro'];
	if ($rahdinmaksaja=='Lähettäjä') $rahdinmaksaja = $yhtiorow["nimi"];
			
	// postiennakko
	if ($rakir_row["jv"] != '') {
		$postiennakkomaara  = "$yhteensa $yhtiorow[valkoodi]";
		$postiennakkotilino = "$yhtiorow[pankkitili1]";
		$postiennakkoviite  = "$viite";
	}
	else {
		$postiennakkomaara  = "";
		$postiennakkotilino = "";
		$postiennakkoviite  = "";
	}
	
	//PDF:n luonti ja defaultit
	define (__TRACE_ENABLED__, false);
	define (__DEBUG_ENABLED__, false);
	require_once("barcode/barcode.php");
	require_once("barcode/debug.php");
	require_once("barcode/c39object.php");
	require_once("pdflib/phppdflib.class.php");
		
	//PDF parametrit
	$pdf = new pdffile;
	$pdf->set_default('margin-top', 	0);
	$pdf->set_default('margin-bottom', 	0);
	$pdf->set_default('margin-left', 	0);
	$pdf->set_default('margin-right', 	0);
	$rectparam["width"] = 0.3;
	
	$norm["height"] = 6;
	$norm["font"] = "Times-Roman";
	
	$kirj["height"] = 8;
	$kirj["font"] = "Times-Roman";
	
	$iso["height"] = 12;
	$iso["font"] = "Helvetica-Bold";
	
	$huge["height"] = 22;
	$huge["font"] = "Helvetica-Bold";
	
	// defaultteja
	$kala = 575;
	$lask = 1;
	$sivu = 1;
	
	//tulostetaan 3 sivua
	$tulostakolli = 3;
	
	for ($tulostuskpl=1; $tulostuskpl<=$tulostakolli; $tulostuskpl++) {
	
			
		// tehdään pdf:n uusi sivu
		$firstpage = $pdf->new_page("a4");
		
	
		$lahete	= sprintf("%04d", $otsikot[0]); 		//Laitetaan laheteen numero rahtikirjan alkuun
		$aika		= sprintf("%04d", date("mm.dd"));		//Loppuun laitetaan päiväys
		
		$toitarow['sopimusnro'] = sprintf("%06d",$toitarow['sopimusnro']);	// sopimunumeron tulee olla kuus pitkä
	
		// tehdään viivakoodi (jonne laitetaan aina arwin sopimusnumero)	
		$sopnro = $toitarow['sopimusnro']; // käytetään edi sanomassa
		$viiva2 	= $lahete.$aika;
		
		
		//Duusataan viivakoodia 
		$output   = "png";
		$width    = "250";
		$height   = "66";
		$xres     = "1";
		$font     = "2";
		$drawtext = "off";
		$border   = "on";
		$stretchtext = "off";
		$style    = BCS_ALIGN_CENTER;
		$style   |= ($output     == "png" ) ? BCS_IMAGE_PNG      : 0;
		$style   |= ($output     == "jpeg") ? BCS_IMAGE_JPEG     : 0;
		$style   |= ($border     == "on"  ) ? BCS_BORDER         : 0;
		$style   |= ($drawtext   == "on"  ) ? BCS_DRAW_TEXT      : 0;
		$style   |= ($stretchtext== "on"  ) ? BCS_STRETCH_TEXT   : 0;
		$style   |= ($negative   == "on"  ) ? BCS_REVERSE_COLOR  : 0;
		
		//luodaan viivakoodiolio
		$obj = "";
		$obj = new c39object($width, $height, $style, $viiva2);
	
		if ($obj) {
			$obj->SetFont($font);
			$obj->DrawObject($xres);
	
			//flushataan barcode ja saadaam filenimi johon se tallennettiin
			$nimi1 = $obj->FlushObject();
	
			//keksitään uudelle failille joku varmasti uniikki nimi:
			$nimi2 = "/tmp/".md5(uniqid(rand(),true)).".png";
	
			passthru("/usr/bin/convert -colors 2 ".$nimi1." ".$nimi2, $palautus);
	
			$fh = fopen($nimi2, "r");
			$data = fread($fh, filesize($nimi2));
			fclose($fh);
			$image = $pdf->png_embed($data);
	
			if(!$image) {
				echo "error";
				exit;
			}
			// piirretään viivakoodi paprulle
			$pdf->image_place($image, 635, 350, $firstpage); // Y, X
			$pdf->draw_text(330, 625,  $viiva2,											$firstpage, $norm);
	
	
			unset($obj);
	
			//dellataan tmp filet kuleksimasta
			system("rm -f $nimi1 $nimi2");
		}
		else {
			echo "error";
			exit;
		}
								
		// sitten aletaan piirtämään itse PDF sisältöä	
		$pdf->draw_text(302,  812,  "RAHTIKIRJA FRAKTSEDEL",								$firstpage, $iso);
	
		//osoitetiedot yms	
		//toimittaja
		$pdf->draw_rectangle(810,60,777,300,												$firstpage, $rectparam);
		$pdf->draw_text(62,  803,  "Lähettäjä Avsändare",									$firstpage, $norm);
		$pdf->draw_text(240, 803,  "Asiakasnro Kundnr",										$firstpage, $norm);
		$pdf->draw_text(65,  782,  $yhtiorow["nimi"],  										$firstpage, $kirj);
		$pdf->draw_text(240, 782,  $toitarow['sopimusnro'],									$firstpage, $kirj);
		$pdf->draw_rectangle(777,60,755,300,												$firstpage, $rectparam);
		$pdf->draw_text(65,  758,  $yhtiorow["osoite"],										$firstpage, $kirj);	
		$pdf->draw_rectangle(755,60,733,300,												$firstpage, $rectparam);
		$pdf->draw_text(65,  736,  $yhtiorow["postino"]."   ".$yhtiorow["postip"],			$firstpage, $kirj);
		//oikeeta
		$pdf->draw_text(302,  803,  "Lähetyspäivämäärä Avsändningsdatum",					$firstpage, $norm);
		$pdf->draw_text(302,  788,  date("d.m.Y"),											$firstpage, $kirj);
		$pdf->draw_text(302,  782,  "Lähettäjän viite Avsändarens referens",				$firstpage, $norm);
		$pdf->draw_text(302,  761,  "Vastaanottajan viite Mottagarens referens",			$firstpage, $norm);
	
		$pdf->draw_rectangle(733,60,733,545,												$firstpage, $rectparam);
		
		//vastaanottaja	
		$pdf->draw_rectangle(733,60,711,300,												$firstpage, $rectparam);
		$pdf->draw_text(62,  726,  "Vastaanottaja Mottagare",								$firstpage, $norm);
		$pdf->draw_text(240, 726,  "Asiakasnro Kundnr",										$firstpage, $norm);
		$pdf->draw_text(65,  714,  $rakir_row["nimi"],										$firstpage, $kirj);
		$pdf->draw_text(240, 714,  $postirow['rahtisopimus'],								$firstpage, $kirj);
		$pdf->draw_rectangle(711,60,689,300,												$firstpage, $rectparam);
		//$pdf->draw_text(65,  692,  $rakir_row["osoite"],									$firstpage, $kirj);
		$pdf->draw_rectangle(689,60,667,300,												$firstpage, $rectparam);
		//$pdf->draw_text(65,  670,  $rakir_row["postino"]." ".$rakir_row["postitp"],		$firstpage, $kirj);
		//oikee
		$pdf->draw_text(302,  726,  "Rahdinkuljettaja ja / tai huolitsijaTransportföretag och / eller Speditör", $firstpage, $norm);
		$pdf->draw_rectangle(667,60,667,545,												$firstpage, $rectparam);
	
		//toimitusosoite
		$pdf->draw_rectangle(667,60,645,300,												$firstpage, $rectparam); 
		$pdf->draw_text(62,  661, "Tavaran toimitusosoite",									$firstpage, $norm);
		$pdf->draw_text(65,  648,  $rakir_row['toim_nimitark']."  ".$rakir_row["toim_osoite"], $firstpage, $kirj); 
		$pdf->draw_rectangle(645,60,623,300,												$firstpage, $rectparam);
		//oikee
			
		//tähän viivakoodia
		$pdf->draw_rectangle(623,60,623,545,												$firstpage, $rectparam);
	
		//Lastauspaikka
		$pdf->draw_rectangle(623,60,535,300,												$firstpage, $rectparam);
		$pdf->draw_text(62,  617, "Lähtö- ja lastauspaikka",								$firstpage, $norm);
		$pdf->draw_text(65,  593,  $yhtiorow["postino"]."   ".$yhtiorow["postitp"],			$firstpage, $kirj);
		$pdf->draw_text(62,  573, "Määräpaikan postinumero ja -toimipaikka",				$firstpage, $norm);
		$pdf->draw_text(65,  550,  $rakir_row["toim_postino"]." ".$rakir_row["toim_postitp"], $firstpage, $kirj);
	
		//oikeeta laitaa
		$pdf->draw_text(302,  617, "Toimituslauseke Leveransklausul",						$firstpage, $norm);
		$pdf->draw_rectangle(579,300,579,545,												$firstpage, $rectparam);
		$pdf->draw_text(302,  573, "Rahdinmaksaja Fraktbetalare",							$firstpage, $norm);
		$pdf->draw_text(302,  559, $rahdinmaksaja,											$firstpage, $kirj);
		$pdf->draw_text(390,  573, "Asiakasnro Kundnr",										$firstpage, $norm);
		$pdf->draw_text(390,  559, $rahdinmaksajan_nr,										$firstpage, $kirj);  
		
		$pdf->draw_rectangle(557,300,557,545,												$firstpage, $rectparam);
		$pdf->draw_rectangle(557,300,535,420,												$firstpage, $rectparam);
		$pdf->draw_rectangle(535,300,535,545,												$firstpage, $rectparam);
		
		
		//kollitietot
		//ylärivi
		$pdf->draw_text(62,  529, "Merkki / nro",											$firstpage, $norm);
		$pdf->draw_text(62,  523, "Märke / nr",												$firstpage, $norm);
		$pdf->draw_rectangle(535,181,520,181,												$firstpage, $rectparam);
		$pdf->draw_text(183,  529, "Kolliluku ja -laji",									$firstpage, $norm);
		$pdf->draw_text(183,  523, "Kolliantal och -slag",									$firstpage, $norm);
		$pdf->draw_rectangle(535,242,520,242,												$firstpage, $rectparam);
		$pdf->draw_text(246,  529, "Sisältö, ulkomitat ja VAK-merkinnät",					$firstpage, $norm);
		$pdf->draw_text(246,  523, "Innehåll, yttermått och ADR-anmärkningar",				$firstpage, $norm);
		$pdf->draw_rectangle(535,420,520,420,												$firstpage, $rectparam);
		$pdf->draw_text(422,  529, "Brutto kg",												$firstpage, $norm);
		$pdf->draw_rectangle(535,484,520,484,												$firstpage, $rectparam);
		$pdf->draw_text(486,  529, "Tilavuus, m3",											$firstpage, $norm);
		$pdf->draw_text(486,  523, "Tilavuus, m3",											$firstpage, $norm);
				
		//kollit
		$pdf->draw_text(62, 510, $otsikot[0],												$firstpage, $kirj);
		$pdf->draw_text(183, 510, $kollit[0]."  ". $pakkaus[0],								$firstpage, $kirj); 
		//$pdf->draw_text(246, 510, "ulkomitat",											$firstpage, $kirj);
		$pdf->draw_text(246, 503, $vakit[0],												$firstpage, $kirj);	
		$pdf->draw_text(422, 510, $kilot[0],												$firstpage, $kirj);
		$pdf->draw_text(486, 510, $kuutiot[0],												$firstpage, $kirj);
		
		$pdf->draw_text(62, 490, $otsikot[1],												$firstpage, $kirj); 
		$pdf->draw_text(183, 490, $kollit[1]."  ". $pakkaus[1],								$firstpage, $kirj);
		//$pdf->draw_text(246, 490, "ulkomitat",											$firstpage, $kirj);  
		$pdf->draw_text(246, 484, $vakit[1],												$firstpage, $kirj);
		$pdf->draw_text(422, 490, $kilot[1],												$firstpage, $kirj); 
		$pdf->draw_text(486, 490 , $kuutiot[1],												$firstpage, $kirj); 	
		
		$pdf->draw_text(62, 470, $otsikot[2],												$firstpage, $kirj);
		$pdf->draw_text(183, 470, $kollit[2]."  ". $pakkaus[2],								$firstpage, $kirj);
		//$pdf->draw_text(246, 470, "ulkomitat",											$firstpage, $kirj);
		$pdf->draw_text(246, 464, $vakit[2],												$firstpage, $kirj);
		$pdf->draw_text(422, 470, $kilot[2],												$firstpage, $kirj);
		$pdf->draw_text(486, 470, $kuutiot[2],												$firstpage, $kirj);
	
		$pdf->draw_text(62, 450, $otsikot[3],												$firstpage, $kirj);
		$pdf->draw_text(183, 450, $kollit[3]."  ". $pakkaus[3],								$firstpage, $kirj);
		//$pdf->draw_text(246, 450, "ulkomitat",											$firstpage, $kirj);
		$pdf->draw_text(246, 444, $vakit[3],												$firstpage, $kirj);
		$pdf->draw_text(422, 450, $kilot[3],												$firstpage, $kirj);
		$pdf->draw_text(486, 450, $kuutiot[3],												$firstpage, $kirj);
	
		$pdf->draw_text(62, 430, $otsikot[4],												$firstpage, $kirj);
		$pdf->draw_text(183, 430, $kollit[4]."  ". $pakkaus[4],								$firstpage, $kirj);
		//$pdf->draw_text(246, 430, "ulkomitat",											$firstpage, $kirj);
		$pdf->draw_text(246, 424, $vakit[4],												$firstpage, $kirj);
		$pdf->draw_text(422, 430, $kilot[4],												$firstpage, $kirj);
		$pdf->draw_text(486, 430, $kuutiot[4],												$firstpage, $kirj);
		
	
		//alarivi
		$pdf->draw_text(183, 404 , "Kollit yht. Kolliantal tot.",							$firstpage, $norm);
		$pdf->draw_text(183, 390 , $kollityht,												$firstpage, $kirj);
		$pdf->draw_rectangle(410,181,388,242,												$firstpage, $rectparam);
		$pdf->draw_text(365, 404 , "Lavametrit Flakmeter",									$firstpage, $norm); 
		$pdf->draw_text(365, 390 , $lavametri[0],											$firstpage, $norm);  
		$pdf->draw_rectangle(410,363,388,420,												$firstpage, $rectparam);
		$pdf->draw_text(422, 404 , "Brutto yht. total., kg Rahditysp.  Fraktvikt",			$firstpage, $norm); 
		$pdf->draw_text(422, 390 , $kilotyht,												$firstpage, $kirj);
		//vähä vedetää viivaa
		$pdf->draw_rectangle(410,60,410,545,												$firstpage, $rectparam);
		$pdf->draw_rectangle(388,60,388,545,												$firstpage, $rectparam);
		
		//kuljetusohjeita
		$pdf->draw_text(62, 382 , "Kuljetusohjeet Transportinstruktioner",					$firstpage, $norm);
		$pdf->draw_text(62, 311 , "Muut tiedot Tillägsuppgifter",							$firstpage, $norm);
		//laatikot
		$pdf->draw_text(305, 325 , "Lisät eriteltynä:",										$firstpage, $norm);
		$pdf->draw_rectangle(278,303,256,420,												$firstpage, $rectparam);
		$pdf->draw_text(305, 272 , "Muu palvelu",											$firstpage, $norm);
		$pdf->draw_rectangle(300,303,278,420,												$firstpage, $rectparam);
		$pdf->draw_text(305, 294 , "Jakelu",												$firstpage, $norm);
		$pdf->draw_rectangle(322,303,300,420,												$firstpage, $rectparam);
		$pdf->draw_text(305, 316 , "Nouto",													$firstpage, $norm);
	
		//oikee eli jäkivaatimus
		$pdf->draw_rectangle(388,420,322,420,												$firstpage, $rectparam);
		$pdf->draw_text(422, 382 , "Jälkivaatimus, maksuviite Bet.ref. För efterkrav",		$firstpage, $norm);
		$pdf->draw_text(422, 369 , $jvrviite,												$firstpage, $norm);
		$pdf->draw_rectangle(366,420,366,545,												$firstpage, $rectparam);
		$pdf->draw_text(422, 360 , "Jälkivaatimus, tilinro Kontonr för efterkrav",			$firstpage, $norm); 
		$pdf->draw_text(422, 347 , $jvrtilino,												$firstpage, $norm);
		$pdf->draw_rectangle(344,420,344,545,												$firstpage, $rectparam);
		$pdf->draw_text(422, 338 , "Jälkivaat. Efterkrav",                        			$firstpage, $norm); 
		$pdf->draw_text(422, 325 , $jvrmaara,												$firstpage, $norm);
		$pdf->draw_rectangle(322,420,322,545,												$firstpage, $rectparam);
		$pdf->draw_text(422, 316 , "Rahti Frakt", 					             			$firstpage, $norm);  
		$pdf->draw_rectangle(300,420,300,545,												$firstpage, $rectparam);
		$pdf->draw_text(422, 294 , "Lisät yht. Extra avgift tot.", 			               	$firstpage, $norm);  
		$pdf->draw_rectangle(278,420,278,545,												$firstpage, $rectparam);
		$pdf->draw_text(422, 272 , "+ Alv. Moms",											$firstpage, $norm);
		//poikkiviiva
		$pdf->draw_rectangle(256,60,256,545,												$firstpage, $rectparam);
		//SFS
		$pdf->draw_text(25, 272 , "SFS",													$firstpage, $kirj);
		$pdf->draw_text(25, 262 , "5865",													$firstpage, $kirj);
	
	
		//varaumat
		$pdf->draw_text(62, 250 , "Varaumat Förbehåll",										$firstpage, $norm); 
	
		//alarivi
		$pdf->draw_rectangle(179,60,179,545,												$firstpage, $rectparam);
		$pdf->draw_text(62, 173 , "12 Nouto",												$firstpage, $norm); 
		$pdf->draw_rectangle(179,112,146,164,												$firstpage, $rectparam);
		$pdf->draw_text(114, 173 , "14 Jakelu",												$firstpage, $norm); 
		$pdf->draw_text(166, 173 , "27 LJP",												$firstpage, $norm);
		$pdf->draw_rectangle(179,216,146,268,												$firstpage, $rectparam);
		$pdf->draw_text(218, 173 , "26 VAK",												$firstpage, $norm);  
		$pdf->draw_text(270, 173 , "23 Yks",												$firstpage, $norm);
		$pdf->draw_rectangle(179,320,146,372,												$firstpage, $rectparam);
		$pdf->draw_text(322, 173 , "40 Yks lkm",											$firstpage, $norm);
		$pdf->draw_text(374, 173 , "69 Lisämaksu",											$firstpage, $norm);
	
		//oikee laita	
		$pdf->draw_text(422, 250 , "Käteinen yht. Kontant tot.",							$firstpage, $norm);
		$pdf->draw_rectangle(234,420,234,545,												$firstpage, $rectparam);
		$pdf->draw_text(422, 228 , "Käteismaksu, pvm,",										$firstpage, $norm);
		$pdf->draw_text(422, 222 , "paikka ja kuittaus",									$firstpage, $norm);
		$pdf->draw_text(422, 173 , "Krnro, kotip. HRnr, hemort/LY-tunnus AS-signum",		$firstpage, $norm);
	
		//poikki ja pystyviivaa
		$pdf->draw_rectangle(146,60,146,545,												$firstpage, $rectparam);	
		$pdf->draw_rectangle(256,420,146,420,			 									$firstpage, $rectparam);
	
		//allekirjoitukset
		$pdf->draw_text(62, 140 , "Kuljettajan luovutuskuittaus ja pvm. Chaufförens underskrift och dat", $firstpage, $norm);
		$pdf->draw_text(62, 134 , "Vastaanottajan allekirjoitus. Mottagarens underskift.", 	$firstpage, $norm);
		$pdf->draw_rectangle(146,242,55,242,												$firstpage, $rectparam);
		$pdf->draw_text(244, 140 , "Otettu kuljetettavaksi pvm ja kuljettajan vastaanottokuittaus",		$firstpage, $norm);
		$pdf->draw_text(244, 134 , "Chaufför, datum och underskrift",						$firstpage, $norm);
		$pdf->draw_rectangle(146,420,55,420,												$firstpage, $rectparam);
		$pdf->draw_text(422, 140 , "Lähettäjä, pvm ja allekirjoitus",						$firstpage, $norm);
		$pdf->draw_text(422, 134 , "Avsändare, datum och underskrift",						$firstpage, $norm);
		
		$pdf->draw_text(244, 42 , "Nimenselvennykset Namnförtydliganden",					$firstpage, $kirj); 
	
		//noita ulkokehän viivoja ja vähä muuta
		$pdf->draw_rectangle(810,60,55,60,													$firstpage, $rectparam);
		$pdf->draw_text(25, 70 , $tulostuskpl,												$firstpage, $huge);
		
		if	($tulostuskpl == 1) $pdf->draw_text(25, 30 , "Kuljetusliike",					$firstpage, $kirj);
		if	($tulostuskpl == 2) $pdf->draw_text(25, 30 , "Kuljetusliike",					$firstpage, $kirj);
		if	($tulostuskpl == 3) $pdf->draw_text(25, 30 , "Vastaanottaja",					$firstpage, $kirj);			
	}
	
	
	//keksitään uudelle failille joku varmasti uniikki nimi:
	$pdffilenimi = "/tmp/rahtikirja-".md5(uniqid(rand(),true)).".pdf";
	
	//kirjoitetaan pdf faili levylle..
	$fh = fopen($pdffilenimi, "w");
	if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus epäonnistui $pdffilenimi");
	fclose($fh);
	
	//tulostetaan PDF
	$line = exec("$kirjoitin $pdffilenimi");
	
	//väliaikaisesti myös emailiin
	//$liite = $pdffilenimi;
	//$kutsu = "PDF-rahtikirja";
	//require("inc/sahkoposti.inc");
	
	//poistetaan tmp file samantien kuleksimasta...
	system("rm -f $pdffilenimi");		
	
?>
