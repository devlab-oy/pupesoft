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

if ($rahdinmaksaja=="Lähettäjä") $rahdinmaksaja="";

//PDF:n luonti ja defaultit
define (__TRACE_ENABLED__, false);
define (__DEBUG_ENABLED__, false);
require_once("barcode/barcode.php");
require_once("barcode/debug.php");
require_once("barcode/c128aobject.php");
require_once("pdflib/phppdflib.class.php");
	
//PDF parametrit
$pdf = new pdffile;
$pdf->set_default('margin-top', 	0);
$pdf->set_default('margin-bottom', 	0);
$pdf->set_default('margin-left', 	0);
$pdf->set_default('margin-right', 	0);
$rectparam["width"] = 0.3;

$norm["height"] = 8.5;
$norm["font"] = "Helvetica";

$pieni["height"] = 6;
$pieni["font"] = "Helvetica";

$pienibold["height"] = 7;
$pienibold["font"] = "Helvetica-Bold";

$iso["height"] = 9;
$iso["font"] = "Helvetica";

$isobold["height"] = 10.5;
$isobold["font"] = "Helvetica-Bold";

$huge["height"] = 20;
$huge["font"] = "Helvetica-Bold";

// defaultteja
$kala = 575;
$lask = 1;
$sivu = 1;

if ($tulostakopio == "kylla") {
	$splitti = array();
	$splitti = split(' ',trim($rakir));
	$tulostakolli = count($splitti);
}
else {
	if ($tulostuskpl == 0) {
		$tulostakolli = 0;
	}
	else {
		$tulostakolli = $tulostuskpl;
	}	
}

// tulostetaan niin monta lappua kun on kollejakin
for ($tulostuskpl=1; $tulostuskpl<=$tulostakolli; $tulostuskpl++) {

	$kuka    = sprintf("%02d",$tulostuskpl); 		// laitetaan tulostuskpl alkuun niin jokalapulla on uniikki viiva
	$oma     = sprintf("%09d",$rtunnus); 			// 9, pitkä numero tulee rahtikirja taulun tunnuksesta, pitää olaa uniikki
	
	$toitarow['sopimusnro'] = sprintf("%06d",$toitarow['sopimusnro']);	// sopimunumeron tulee olla kuus pitkä

	// tehdään viivakoodi (jonne laitetaan aina arwin sopimusnumero)	
	if ($tulostakopio == "kylla") {
		$viiva2 = $splitti[$tulostuskpl-1];
	}
	else {
		$viiva2  = "JJFI";
		$viiva2 .= $toitarow['sopimusnro'];
		$sopnro = $toitarow['sopimusnro']; // käytetään edi sanomassa
		$viiva2 .= $kuka.$oma;

		// erotellaan spacella jokainen rahtikirjanumero
		$rahtikirjanro .= "$viiva2 ";		
	}
	// tehdään pdf:n uusi sivu
	$firstpage = $pdf->new_page("a5");
	
	//tehdään JJFI viivakoodi
	//oletukset viivakoodeille
	$output   = "png";
	$width    = "275";
	$height   = "85";
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
	$obj = new C128AObject($width, $height, $style, $viiva2);

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
		$pdf->image_place($image, 270, 75, $firstpage); // Y, X

		unset($obj);

		//dellataan tmp filet kuleksimasta
		system("rm -f $nimi1 $nimi2");
	}
	else{
		echo "error";
		exit;
	}
	
	//tehdään tuoteviikoodi
	//oletukset viivakoodeille
	$output   = "png";
	$width    = "120";
	$height   = "45";
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
	$obj = new C128AObject($width, $height, $style, $tuoteviiva1);

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
		$tuoteviiva = $pdf->png_embed($data);

		if(!$tuoteviiva) {
			echo "$GLOBALS[LANG_virhe]".$image.$data;
			exit;
		}

		// piirretään viivakoodi
		$pdf->image_place($tuoteviiva, 540, 270, $firstpage); // Y, X

		unset($obj);

		//dellataan tmp filet kuleksimasta
		system("rm -f $nimi1 $nimi2");
	}
	else{
		echo "error";
		exit;
	}
	
	// tehdään postille tärkeä pikku puhelimen kuva!
	$fh = fopen("pics/phone.png", "r");
	$data = fread($fh, filesize("pics/phone.png"));
	fclose($fh);
	
	$image = $pdf->png_embed($data);
	if ($image) {
		// piirretään puhelin paprulle
		$pdf->image_place($image, 485, 199, $firstpage); // Y, X
	}
	
	// sitten aletaan piirtämään itse PDF sisältöä
	$pdf->draw_text(100,  570,  $keltainen 									,$firstpage, $isobold);
	$pdf->draw_text(100,  560,  $kuljetus									,$firstpage, $isobold);
	$pdf->draw_text(150,  562,  $keku	 									,$firstpage, $huge);

	$pdf->draw_text(18,  540,  "Lähettäjä Avsändare From", 					$firstpage, $pieni); // x,y
	$pdf->draw_text(18,  530, $yhtiorow["nimi"],	 						$firstpage, $norm);
	$pdf->draw_text(18,  520, $yhtiorow["osoite"],	 						$firstpage, $norm);
	$pdf->draw_text(18,  510, $yhtiorow["postino"]." ".$yhtiorow["postitp"],$firstpage, $norm);

	$pdf->draw_text(18,  490,  "Vastaanottaja Addressat To", 				$firstpage, $pieni); // x,y	
	$pdf->draw_text(18,  480,  $rakir_row["toim_nimi"],	 						$firstpage, $iso);
	$pdf->draw_text(18,  470,  $rakir_row["toim_nimitark"], 						$firstpage, $iso);
	$pdf->draw_text(18,  460,  $rakir_row["toim_osoite"],	 						$firstpage, $iso);
	$pdf->draw_text(18,  430,  $rakir_row["toim_postino"]." ".$rakir_row["toim_postitp"],$firstpage, $isobold);
	
	$pdf->draw_text(200,  480,  $asiakasrow["puhelin"],						$firstpage, $pieni);
	
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
	$pdf->draw_text(190, 405,  $rahdinmaksaja,								$firstpage, $iso); // x,y
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
	$pdf->draw_text(18,  242,  "Lähetys on noudettavissa Postistanne. Olkaa hyvä ja ottakaa ilmoitus mukaanne. Lisätietoja: 9800 7100, www.posti.fi", $firstpage, $pieni); // x,y
	$pdf->draw_text(18,  234,  "Ni kan hämta försändelsen på Ert postkontor. Vänligen ta med Er denna avi. Mera information: 9800 7100, www.posti.fi", $firstpage, $pieni); // x,y
	$pdf->draw_text(18,  226,  "Säilytysaika on 2 täyttä kalenteriviikkoa. Kvarligger 2 hela kalenderveckor.", $firstpage, $pieni); // x,y
	
	$pdf->draw_text(18,  215,  "Lähettäjä Avsändare From", 					$firstpage, $pieni); // x,y
	$pdf->draw_text(18,  206, $yhtiorow["nimi"],	 						$firstpage, $norm);
	$pdf->draw_text(18,  197, $yhtiorow["osoite"],	 						$firstpage, $norm);
	$pdf->draw_text(18,  188, $yhtiorow["postino"]." ".$yhtiorow["postitp"],$firstpage, $norm);

	$pdf->draw_text(18,  180,  "Vastaanottaja Addressat To", 				$firstpage, $pieni); // x,y	
	$pdf->draw_text(18,  170,  $rakir_row["toim_nimi"],	 						$firstpage, $iso);
	$pdf->draw_text(18,  160,  $rakir_row["toim_nimitark"], 						$firstpage, $iso);
	$pdf->draw_text(18,  150,  $rakir_row["toim_osoite"],	 						$firstpage, $iso);
	$pdf->draw_text(18,  130,  $rakir_row["toim_postino"]." ".$rakir_row["toim_postitp"],$firstpage, $iso);

	// viivakoodin alapuolella vasen laita
	$pdf->draw_text(322, 250,  $keltainen." ".$kuljetus." ".$keku, 			$firstpage, $pienibold);	
	$pdf->draw_text(250, 225,  "Lisätiedot Tilläggsuppgifter", 				$firstpage, $pieni); // x,y		
	
	$pdf->draw_text(250, 204,  "Sisältö Innehåll", 							$firstpage, $pieni); // x,y	
	$pdf->draw_text(250, 196,  "MPS", 										$firstpage, $pieni); // x,y	
	$pdf->draw_text(340, 188,  "kg", 										$firstpage, $pieni); // x,y	
	$pdf->draw_text(352, 196,  "Pvm Datum", 								$firstpage, $pieni); // x,y	
	$pdf->draw_text(250, 178,  "Postiennakkomäärä Postförskottsbelopp", 	$firstpage, $pieni); // x,y	
	$pdf->draw_text(250, 160,  "Tilinumero Kontonummer", 					$firstpage, $pieni); // x,y	
	$pdf->draw_text(250, 140,  "Viitenumero Referensnummer", 				$firstpage, $pieni); // x,y	

	$pdf->draw_rectangle(202,250,202,400, 									$firstpage, $rectparam); // y,x,y,x vaakaviiva
	$pdf->draw_rectangle(184,250,184,400, 									$firstpage, $rectparam); // y,x,y,x vaakaviiva
	$pdf->draw_rectangle(166,250,166,400, 									$firstpage, $rectparam); // y,x,y,x vaakaviiva
	$pdf->draw_rectangle(146,250,146,400, 									$firstpage, $rectparam); // y,x,y,x vaakaviiva
	$pdf->draw_rectangle(202,290,184,290, 									$firstpage, $rectparam); // y,x,y,x pystyviiva
	$pdf->draw_rectangle(202,350,184,350, 									$firstpage, $rectparam); // y,x,y,x pystyviiva
	
	// ja vähä arvoja
	$pdf->draw_text(255, 215,  "",											$firstpage, $iso); // x,y // tää on pikku lisätietoja kenttä JJFI koodin alla vasemmalla
	$pdf->draw_text(255, 188,  $kollityht,									$firstpage, $iso); // x,y
	$pdf->draw_text(297, 188,  $kilotyht,									$firstpage, $iso); // x,y
	$pdf->draw_text(357, 188,  date("d.m.Y"),								$firstpage, $iso); // x,y
	$pdf->draw_text(255, 170,  $postiennakkomaara,							$firstpage, $iso); // x,y
	$pdf->draw_text(255, 150,  $postiennakkotilino,							$firstpage, $iso); // x,y
	$pdf->draw_text(255, 132,  $postiennakkoviite,							$firstpage, $iso); // x,y

	// viiva alle
	$pdf->draw_rectangle(128,18,128,400, 									$firstpage, $rectparam); // y,x,y,x vaakaviiva
	$pdf->draw_text(18, 120,  "Kuittaus ja nimen selvennys Kvittering och namnförtydligande",$firstpage, $pieni); // x,y		
	$pdf->draw_text(40, 80,   ".               .                        klo kl.",$firstpage, $pieni); // x,y
	$pdf->draw_text(18, 70,   "Lisätiedot Tilläggsuppgifter",				$firstpage, $pieni); // x,y		
	$pdf->draw_text(18, 57,   $vakit[0],									$firstpage, $isobold); // x,y
	$pdf->draw_text(18, 46,   $vakit[1],									$firstpage, $isobold); // x,y
	$pdf->draw_text(18, 35,   $vakit[2],									$firstpage, $isobold); // x,y
	$pdf->draw_text(18, 24,   $vakit[3],									$firstpage, $isobold); // x,y
	$pdf->draw_text(18, 13,   $vakit[4],									$firstpage, $isobold); // x,y
	$pdf->draw_text(18,  2,   $vakit[5],									$firstpage, $isobold); // x,y
	// enempää rivejä ei mahu! toivottasti ei oo vakkeja enempää.. :)
	
	// koko kortin ympärille border
	$pdf->draw_rectangle(0, 0,  598, 418,	 								$firstpage, $rectparam);

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
	$line = exec("$kirjoitin $pdffilenimi");

	//väliaikaisesti myös emailiin
	//$liite = $pdffilenimi;
	//$kutsu = "PDF-rahtikirja";
	//require("inc/sahkoposti.inc");

	//poistetaan tmp file samantien kuleksimasta...
	system("rm -f $pdffilenimi");		
}

?>