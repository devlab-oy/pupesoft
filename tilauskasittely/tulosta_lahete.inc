<?php

//viivakoodien tulostamista varten
define (__TRACE_ENABLED__, false);
define (__DEBUG_ENABLED__, false);

require_once("barcode/barcode.php");
require_once("barcode/c39object.php");
require_once("pdflib/phppdflib.class.php");

if ($kieli== '') {
	$querykiel = "SELECT kieli FROM asiakas WHERE yhtio = '$kukarow[yhtio]' and ytunnus = '$laskurow[ytunnus]'";
	$kielresult = mysql_query($querykiel) or pupe_error($querykiel);
	$kielnum = mysql_num_rows($kielresult);
	$kielrow = mysql_fetch_array($kielresult);
	$kieli = strtolower($kielrow['kieli']);
}

$norm["height"] 		= 10;
$norm["font"] 			= "Times-Roman";

$pieni["height"] 		= 8;
$pieni["font"] 			= "Times-Roman";

$boldi["height"] 		= 10;
$boldi["font"] 			= "Times-Bold";

$pieni_boldi["height"] 	= 8;
$pieni_boldi["font"] 	= "Times-Bold";

$iso["height"] 			= 14;
$iso["font"] 			= "Helvetica-Bold";

$rectparam["width"] 	= 0.3;
$rivinkorkeus			= 15;

//muutamat funktiot...
if (!function_exists('alku')) {
	function alku($tyyppi = "") {
		global $pdf, $sivu, $lahetetyyppi, $laskurow, $yhtiorow, $viite, $pieni, $norm, $boldi, $pieni_boldi, $iso, $kala, $rectparam, $kukarow, $kieli, $asrow;
	
		//PDF parametrit
		if (!isset($pdf)) {
			$pdf = new pdffile;
			$pdf->set_default('margin-top', 	0);
			$pdf->set_default('margin-bottom', 	0);
			$pdf->set_default('margin-left', 	0);
			$pdf->set_default('margin-right', 	0);
		}

		$thispage = $pdf->new_page("a4");

		//oletukset viivakoodeille
		$output   = "png";
		$width    = "140";
		$height   = "20";
		$xres     = "1";
		$font     = "2";
		$drawtext = "off";
	
		$style  = BCS_ALIGN_CENTER;
		$style |= ($output  == "png" ) ? BCS_IMAGE_PNG  : 0;
		$style |= ($output  == "jpeg") ? BCS_IMAGE_JPEG : 0;
		$style |= ($border  == "on"  ) ? BCS_BORDER 	  : 0;
		$style |= ($drawtext== "on"  ) ? BCS_DRAW_TEXT  : 0;
		$style |= ($stretchtext== "on" ) ? BCS_STRETCH_TEXT  : 0;
		$style |= ($negative== "on"  ) ? BCS_REVERSE_COLOR  : 0;
			
		if (trim($yhtiorow["lasku_logo"]) != '' and file_exists($yhtiorow["lasku_logo"])) {
			
			$filename = $yhtiorow["lasku_logo"];

			$fh = fopen($filename, "r");
			$data = fread($fh, filesize($filename));
			fclose($fh);

			$image = $pdf->jfif_embed($data);

			if(!$image) {
				echo t("Logokuvavirhe");
			}
			else {
				$isizelogo = getimagesize($yhtiorow["lasku_logo"]);
				
				$logoparam = array();
				
				if ($isizelogo[0] > $isizelogo[1] and $isizelogo[1] * (240 / $isizelogo[0]) <= 50) {
					$logoparam['scale'] = 240 / $isizelogo[0];
				}
				else {
					$logoparam['scale'] = 50  / $isizelogo[1];
				}
								
				$placement = $pdf->image_place($image, 830-($logoparam['scale']*$isizelogo[1]), 20, $thispage, $logoparam);
			}
		}
		else {
			$pdf->draw_text(30, 815, $yhtiorow["nimi"], 	$thispage, $norm);
			$pdf->draw_text(30, 805, $yhtiorow["osoite"], 	$thispage, $norm);
			$pdf->draw_text(30, 795, $yhtiorow["postino"], 	$thispage, $norm);
			$pdf->draw_text(60, 795, $yhtiorow["postitp"], 	$thispage, $norm);
			$pdf->draw_text(30, 785, $yhtiorow["maa"], 		$thispage, $norm);
		}
		
		// haetaan maksuehdon tiedot
		$query  = "select * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
		$masresult = mysql_query($query) or pupe_error($query);
		$masrow = mysql_fetch_array($masresult);
	
		//maksuehto tekstin‰
		$maksuehto = $masrow["teksti"]." ".$masrow["kassa_teksti"];
		
		if ($tyyppi == "TILAUSVAHVISTUS") {
			$kerarow["kerattyaika"] = date("Y-m-d");
		}
		else {
			// haetaan max(kerattyaika)
			$query  = "SELECT max(kerattyaika) as kerattyaika from tilausrivi where yhtio='$kukarow[yhtio]' and otunnus='$laskurow[tunnus]'";
			$keraresult = mysql_query($query) or pupe_error($query);
			$kerarow = mysql_fetch_array($keraresult);
			
			if ($kerarow["kerattyaika"] == "0000-00-00 00:00:00") {
				$kerarow["kerattyaika"] = date("Y-m-d H:i:s");
			}
		}
		
		if ($tyyppi == "TILAUSVAHVISTUS") {
			$pdf->draw_text(310,815, t("Tilausvahvistus", $kieli), 	$thispage, $iso);
		}
		else {
			$pdf->draw_text(310,815, t("L‰hete", $kieli), 			$thispage, $iso);		
		}
		
		$pdf->draw_text(310, 803, t("Sivu", $kieli), 				$thispage, $norm);
		
		//Vasen sarake
		//$pdf->draw_rectangle(737, 20,  674, 300, $thispage, $rectparam);
		$pdf->draw_text(50, 729, t("Laskutusosoite", $kieli), 	$thispage, $pieni);
		$pdf->draw_text(50, 717, $laskurow["nimi"], 			$thispage, $norm);
		$pdf->draw_text(50, 707, $laskurow["nimitark"],			$thispage, $norm);
		$pdf->draw_text(50, 697, $laskurow["osoite"], 			$thispage, $norm);
		$pdf->draw_text(50, 687, $laskurow["postino"]." ".$laskurow["postitp"], $thispage, $norm);
		$pdf->draw_text(50, 677, $laskurow["maa"], 				$thispage, $norm);


		//$pdf->draw_rectangle(674, 20,  611, 300, $thispage, $rectparam);
		$pdf->draw_text(50, 656, t("Toimitusosoite", $kieli), 	$thispage, $pieni);
		$pdf->draw_text(50, 644, $laskurow["toim_nimi"], 		$thispage, $norm);
		$pdf->draw_text(50, 634, $laskurow["toim_nimitark"], 	$thispage, $norm);
		$pdf->draw_text(50, 624, $laskurow["toim_osoite"],	 	$thispage, $norm);
		$pdf->draw_text(50, 614, $laskurow["toim_postino"]." ".$laskurow["toim_postitp"], $thispage, $norm);
		$pdf->draw_text(50, 604, $laskurow["toim_maa"], 		$thispage, $norm);


		//Oikea sarake
		$pdf->draw_rectangle(800, 300, 779, 580, $thispage, $rectparam);
		$pdf->draw_rectangle(800, 420, 779, 580, $thispage, $rectparam);
		
		$pdf->draw_text(310, 792, t("Tilausnumero", $kieli), 	$thispage, $pieni);
		$pdf->draw_text(430, 792, t("Toimitusaika", $kieli), 	$thispage, $pieni);
		$pdf->draw_text(310, 782, $laskurow["tunnus"],			$thispage, $boldi);
		
		
		if ($laskurow["toimvko"] != '') {
			$DAY_ARRAY = array(1=>"Ma","Ti","Ke","To","Pe","La","Su");
			
			$taika = t("Vko")." ".date("W", strtotime($laskurow["toimaika"]));
			
			if ($laskurow['toimvko'] != '6') {
				$taika .= "/".$DAY_ARRAY[$laskurow["toimvko"]];
			}
			
			$pdf->draw_text(430, 782, $taika, 								$thispage, $norm);
		}
		else {			
			$pdf->draw_text(430, 782, tv1dateconv($laskurow["toimaika"]), 	$thispage, $norm);
		}

		if ($asrow["asiakasnro"] == "" and $laskurow["ytunnus"] != "") $asrow["asiakasnro"] = $laskurow["ytunnus"];
		
		$pdf->draw_rectangle(779, 300, 758, 580, $thispage, $rectparam);
		$pdf->draw_rectangle(779, 420, 758, 580, $thispage, $rectparam);
		$pdf->draw_text(310, 771, t("Asiakasnumero", $kieli), 					$thispage, $pieni);
		$pdf->draw_text(310, 761, $asrow["asiakasnro"], 						$thispage, $norm);
		$pdf->draw_text(430, 771, t("P‰iv‰ys", $kieli), 						$thispage, $pieni);
		$pdf->draw_text(430, 761, tv1dateconv($kerarow["kerattyaika"]), 		$thispage, $norm);
		$pdf->draw_text(430, 771, t("P‰iv‰ys", $kieli), 						$thispage, $pieni);
		$pdf->draw_text(430, 761, tv1dateconv($kerarow["kerattyaika"]), 		$thispage, $norm);

		$pdf->draw_rectangle(758, 300, 737, 580, $thispage, $rectparam);
		$pdf->draw_rectangle(758, 420, 737, 580, $thispage, $rectparam);
		$pdf->draw_text(310, 750, t("Myyj‰", $kieli), 						$thispage, $pieni);
		
		//etsit‰‰n myyj‰n nimi
		$query  = "	select nimi
					from kuka
					where tunnus='$laskurow[myyja]' and yhtio='$kukarow[yhtio]'";
		$myyresult = mysql_query($query) or pupe_error($query);
		$myyrow = mysql_fetch_array($myyresult);

		$pdf->draw_text(310, 740, $myyrow["nimi"], 										$thispage, $norm);
		
		$pdf->draw_text(430, 750, t("Tilauspvm", $kieli),							 	$thispage, $pieni);
		$pdf->draw_text(430, 740, tv1dateconv($laskurow["luontiaika"], "pitka"), 		$thispage, $norm);
	

		$pdf->draw_rectangle(737, 300, 716, 580, $thispage, $rectparam);
		$pdf->draw_text(310, 729, t("Toimitustapa", $kieli), 		$thispage, $pieni);
		$pdf->draw_text(310, 719, $laskurow["toimitustapa"],	 	$thispage, $norm);

		$pdf->draw_rectangle(716, 300, 695, 580, $thispage, $rectparam);
		$pdf->draw_text(310, 708, t("Toimitusehto", $kieli), 		$thispage, $pieni);
		$pdf->draw_text(310, 698, $laskurow["toimitusehto"], 		$thispage, $norm);

		$pdf->draw_rectangle(695, 300, 674, 580, $thispage, $rectparam);
		$pdf->draw_text(310, 687, t("Maksuehto", $kieli), 			$thispage, $pieni);
		$pdf->draw_text(310, 677, $maksuehto, 						$thispage, $norm);
		
		$pdf->draw_rectangle(674, 300, 653, 580, $thispage, $rectparam);
		$pdf->draw_text(310, 666, t("", $kieli), 	$thispage, $pieni);
		$pdf->draw_text(310, 656, $laskurow[""], 	$thispage, $norm);

		$pdf->draw_rectangle(653, 300, 632, 580, $thispage, $rectparam);
		$pdf->draw_text(310, 645, t("", $kieli), $thispage, $pieni);
		$pdf->draw_text(310, 635, $laskurow[""], $thispage, $norm);

		$pdf->draw_rectangle(632, 300, 611, 580, $thispage, $rectparam);
		$pdf->draw_text(310, 624, t("", $kieli), 	$thispage, $pieni);
		$pdf->draw_text(310, 614, $laskurow[""], 	$thispage, $norm);
		
		$komm = "";
		
		if (trim($laskurow['tilausyhteyshenkilo']) != '') {
			$komm .= "\n".t("Tilaaja", $kieli).":###".$laskurow['tilausyhteyshenkilo'];
		}
	
		if (trim($laskurow['asiakkaan_tilausnumero']) != '') {
			$komm .= "\n".t("Tilauksenne", $kieli).":###".$laskurow['asiakkaan_tilausnumero'];								
		}
	
		if (trim($laskurow['kohde']) != '') {
			$komm .= "\n".t("Kohde", $kieli).":###".$laskurow['kohde'];								
		}
	
		if (trim($laskurow['viesti']) != '') {
			$komm .= "\n".t("Tilausviite", $kieli).":###".$laskurow['viesti'];								
		}

		if (trim($laskurow['comments']) != '' and $tyyppi != "TILAUSVAHVISTUS") {
			$komm .= "\n".t("Kommentti", $kieli).":###".wordwrap(str_replace("\n","\n###", $laskurow['comments']), 100, "\n###");							
		}
		
		//Tulostetaan laskun kommentti
		if (trim($komm) != '') {
			$kommentit = explode("\n", trim($komm));

			$pohja  = 577;
			$maxoik = 0;
			
			foreach($kommentit as $kommentti) {
				if(strpos($kommentti, "###") !== FALSE) {
					list($o, $v) = explode("###", $kommentti);
				
					$oikpos = $pdf->strlen($o, $boldi);
				
					if ($oikpos > $maxoik) {
						$maxoik = $oikpos;
					}
				}
			}

			foreach($kommentit as $kommentti) {
				
				if (strpos($kommentti, "###") !== false) {
					list($o, $v) = explode("###", $kommentti);
					
					$pdf->draw_text(35, $pohja, trim($o), $thispage, $boldi);
					$pdf->draw_text(35+$maxoik+5, $pohja, trim($v), $thispage, $norm);
				}
				else {
					$pdf->draw_text(35, $pohja, trim($kommentti), $thispage, $norm);
				}
				
				$pohja = $pohja - 10;
			}
			$kala = $pohja-30;
		}
		else {
			$kala = 560;
		}
		
		//viivakoodin arvo
		$barcode  = $laskurow["tunnus"];
	
		//luodaan viivakoodiolio
		$obj = "";
		$obj = new C39Object($width, $height, $style, $barcode);
	
		if ($obj) {
			$obj->SetFont($font);
			$obj->DrawObject($xres);
	
			//flushataan pdf ja saadaam filenimi johon se tallennettiin
			$nimi1 = $obj->FlushObject();
	
			//keksit‰‰n uudelle failille joku varmasti uniikki nimi:
			list($usec, $sec) = explode(' ', microtime());
			mt_srand((float) $sec + ((float) $usec * 100000));
			$nimi2 = "/tmp/".md5(uniqid(mt_rand(), true)).".png";
	
			passthru("/usr/bin/convert -colors 2 ".$nimi1." ".$nimi2, $palautus);
	
			$fh = fopen($nimi2, "r");
			$data = fread($fh, filesize($nimi2));
			fclose($fh);
			$image = $pdf->png_embed($data);
	
			if(!$image) {
				echo t("Viivakoodivirhe").": ".$image.$data;
				exit;
			}
			$placement = $pdf->image_place($image, 805, 440, $thispage);
	
			unset($obj);
	
			//dellataan tmp filet kuleksimasta
			system("rm -f $nimi1 $nimi2");
		}
		else{
			echo t("Oliota ei voitu luoda").".";
			exit;
		}
	
		$pdf->draw_rectangle($kala+30, 20, 20, 580, $thispage, $rectparam);
		
		rivi_otsikot($thispage);
		
		return($thispage);
	}
}

if (!function_exists('uusi_sivu')) {
	function uusi_sivu ($tyyppi = "") {
		global $yhtiorow, $kukarow, $toim, $pdf, $laskurow, $sivu, $rectparam, $norm, $pieni, $iso, $maksuehto, $kateistyyppi, $kieli, $boldi, $kala;

		$thispage = $pdf->new_page("a4");

		//oletukset viivakoodeille
		$output   = "png";
		$width    = "140";
		$height   = "20";
		$xres     = "1";
		$font     = "2";
		$drawtext = "off";
	
		$style  = BCS_ALIGN_CENTER;
		$style |= ($output  == "png" ) ? BCS_IMAGE_PNG  : 0;
		$style |= ($output  == "jpeg") ? BCS_IMAGE_JPEG : 0;
		$style |= ($border  == "on"  ) ? BCS_BORDER 	  : 0;
		$style |= ($drawtext== "on"  ) ? BCS_DRAW_TEXT  : 0;
		$style |= ($stretchtext== "on" ) ? BCS_STRETCH_TEXT  : 0;
		$style |= ($negative== "on"  ) ? BCS_REVERSE_COLOR  : 0;
			
		if (trim($yhtiorow["lasku_logo"]) != '' and file_exists($yhtiorow["lasku_logo"])) {
			
			$filename = $yhtiorow["lasku_logo"];

			$fh = fopen($filename, "r");
			$data = fread($fh, filesize($filename));
			fclose($fh);

			$image = $pdf->jfif_embed($data);

			if(!$image) {
				echo t("Logokuvavirhe");
			}
			else {
				$isizelogo = getimagesize($yhtiorow["lasku_logo"]);
				
				$logoparam = array();
				
				if ($isizelogo[0] > $isizelogo[1] and $isizelogo[1] * (240 / $isizelogo[0]) <= 50) {
					$logoparam['scale'] = 240 / $isizelogo[0];
				}
				else {
					$logoparam['scale'] = 50  / $isizelogo[1];
				}
								
				$placement = $pdf->image_place($image, 830-($logoparam['scale']*$isizelogo[1]), 20, $thispage, $logoparam);
			}
		}
		else {
			$pdf->draw_text(30, 815, $yhtiorow["nimi"], 	$thispage, $norm);
			$pdf->draw_text(30, 805, $yhtiorow["osoite"], 	$thispage, $norm);
			$pdf->draw_text(30, 795, $yhtiorow["postino"], 	$thispage, $norm);
			$pdf->draw_text(60, 795, $yhtiorow["postitp"], 	$thispage, $norm);
			$pdf->draw_text(30, 785, $yhtiorow["maa"], 		$thispage, $norm);
		}
		
		// haetaan maksuehdon tiedot
		$query  = "select * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
		$masresult = mysql_query($query) or pupe_error($query);
		$masrow = mysql_fetch_array($masresult);
	
		//maksuehto tekstin‰
		$maksuehto = $masrow["teksti"]." ".$masrow["kassa_teksti"];
		
		if ($tyyppi == "TILAUSVAHVISTUS") {
			$kerarow["kerattyaika"] = date("Y-m-d");
		}
		else {
			// haetaan max(kerattyaika)
			$query  = "SELECT max(kerattyaika) as kerattyaika from tilausrivi where yhtio='$kukarow[yhtio]' and otunnus='$laskurow[tunnus]'";
			$keraresult = mysql_query($query) or pupe_error($query);
			$kerarow = mysql_fetch_array($keraresult);
			
			if ($kerarow["kerattyaika"] == "0000-00-00 00:00:00") {
				$kerarow["kerattyaika"] = date("Y-m-d H:i:s");
			}
		}
		
		if ($tyyppi == "TILAUSVAHVISTUS") {
			$pdf->draw_text(310,815, t("Tilausvahvistus", $kieli), 	$thispage, $iso);
		}
		else {
			$pdf->draw_text(310,815, t("L‰hete", $kieli), 			$thispage, $iso);		
		}
		
		$pdf->draw_text(310, 803, t("Sivu", $kieli), 				$thispage, $norm);
	
		//Oikea sarake
		$pdf->draw_rectangle(800, 300, 779, 580, $thispage, $rectparam);
		$pdf->draw_rectangle(800, 420, 779, 580, $thispage, $rectparam);
		
		$pdf->draw_text(310, 792, t("Tilausnumero", $kieli), 	$thispage, $pieni);
		$pdf->draw_text(430, 792, t("Toimitusaika", $kieli), 	$thispage, $pieni);
		$pdf->draw_text(310, 782, $laskurow["tunnus"],			$thispage, $boldi);
		
		
		if ($laskurow["toimvko"] != '') {
			$DAY_ARRAY = array(1=>"Ma","Ti","Ke","To","Pe","La","Su");
			
			$taika = t("Vko")." ".date("W", strtotime($laskurow["toimaika"]));
			
			if ($laskurow['toimvko'] != '6') {
				$taika .= "/".$DAY_ARRAY[$laskurow["toimvko"]];
			}
			
			$pdf->draw_text(430, 782, $taika, 								$thispage, $norm);
		}
		else {			
			$pdf->draw_text(430, 782, tv1dateconv($laskurow["toimaika"]), 	$thispage, $norm);
		}

		if ($asrow["asiakasnro"] == "" and $laskurow["ytunnus"] != "") $asrow["asiakasnro"] = $laskurow["ytunnus"];

		$pdf->draw_rectangle(779, 300, 758, 580, $thispage, $rectparam);
		$pdf->draw_rectangle(779, 420, 758, 580, $thispage, $rectparam);
		$pdf->draw_text(310, 771, t("Asiakasnumero", $kieli), 					$thispage, $pieni);
		$pdf->draw_text(310, 761, $asrow["asiakasnro"], 						$thispage, $norm);
		$pdf->draw_text(430, 771, t("P‰iv‰ys", $kieli), 						$thispage, $pieni);
		$pdf->draw_text(430, 761, tv1dateconv($kerarow["kerattyaika"]), 		$thispage, $norm);
		
		//viivakoodin arvo
		$barcode  = $laskurow["tunnus"];
	
		//luodaan viivakoodiolio
		$obj = "";
		$obj = new C39Object($width, $height, $style, $barcode);
	
		if ($obj) {
			$obj->SetFont($font);
			$obj->DrawObject($xres);
	
			//flushataan pdf ja saadaam filenimi johon se tallennettiin
			$nimi1 = $obj->FlushObject();
	
			//keksit‰‰n uudelle failille joku varmasti uniikki nimi:
			list($usec, $sec) = explode(' ', microtime());
			mt_srand((float) $sec + ((float) $usec * 100000));
			$nimi2 = "/tmp/".md5(uniqid(mt_rand(), true)).".png";
	
			passthru("/usr/bin/convert -colors 2 ".$nimi1." ".$nimi2, $palautus);
	
			$fh = fopen($nimi2, "r");
			$data = fread($fh, filesize($nimi2));
			fclose($fh);
			$image = $pdf->png_embed($data);
	
			if(!$image) {
				echo t("Viivakoodivirhe").": ".$image.$data;
				exit;
			}
			$placement = $pdf->image_place($image, 805, 440, $thispage);
	
			unset($obj);
	
			//dellataan tmp filet kuleksimasta
			system("rm -f $nimi1 $nimi2");
		}
		else{
			echo t("Oliota ei voitu luoda").".";
			exit;
		}

		$kala = 715;
	
		$pdf->draw_rectangle($kala+30, 20, 20, 580, $thispage, $rectparam);
		
		rivi_otsikot($thispage);
				
		return($thispage);
	}
}

if (!function_exists('rivi_otsikot')) {
	function rivi_otsikot($thispage) {
		global $pdf, $kieli, $lahetetyyppi, $norm, $rectparam, $kala;
		
		if (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete.inc") {
			// tulosta_lahete.inc
			
			$pdf->draw_rectangle($kala+30, 20,  $kala+10, 40,  	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 40,  $kala+10, 180, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 180, $kala+10, 320, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 320, $kala+10, 370, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 370, $kala+10, 420, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 420, $kala+10, 470, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 470, $kala+10, 520, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 520, $kala+10, 580, 	$thispage, $rectparam);
			$pdf->draw_text(25,  $kala+15, "#",							$thispage, $norm);
			$pdf->draw_text(45,  $kala+15, t("Tuotenumero", $kieli),	$thispage, $norm);
			$pdf->draw_text(190, $kala+15, t("Tuotenimi", $kieli),		$thispage, $norm);
			$pdf->draw_text(330, $kala+15, t("Tilattu", $kieli),		$thispage, $norm);
			$pdf->draw_text(375, $kala+15, t("Toimitus", $kieli),		$thispage, $norm);
			$pdf->draw_text(425, $kala+15, t("Yks.hinta", $kieli),		$thispage, $norm);
			$pdf->draw_text(478, $kala+15, t("Alennus", $kieli),		$thispage, $norm);
			$pdf->draw_text(530, $kala+15, t("Rivihinta", $kieli),		$thispage, $norm);
		}
		elseif (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_brutto.inc") {
			// tulosta_lahete_brutto.inc
			
			$pdf->draw_rectangle($kala+30, 20,  $kala+10, 40,  	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 40,  $kala+10, 140, 	$thispage, $rectparam);		
			$pdf->draw_rectangle($kala+30, 140, $kala+10, 420, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 420, $kala+10, 470, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 470, $kala+10, 520, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 520, $kala+10, 580, 	$thispage, $rectparam);
			$pdf->draw_text(25,  $kala+15, "#",							$thispage, $norm);
			$pdf->draw_text(45,  $kala+15, t("Tuotenumero", $kieli),	$thispage, $norm);
			$pdf->draw_text(145, $kala+15, t("Tuotenimi", $kieli),		$thispage, $norm);
			$pdf->draw_text(425, $kala+15, t("Tilattu", $kieli),		$thispage, $norm);
			$pdf->draw_text(475, $kala+15, t("Toimitus", $kieli),		$thispage, $norm);
			$pdf->draw_text(525, $kala+15, t("Ovh/Kpl", $kieli),		$thispage, $norm);
		}
		elseif (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_eialeja.inc") {
			//tulosta_lahete_eialeja.inc
			
			$pdf->draw_rectangle($kala+30, 20,  $kala+10, 40,  	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 40,  $kala+10, 180, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 180, $kala+10, 320, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 320, $kala+10, 370, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 370, $kala+10, 420, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 420, $kala+10, 470, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 470, $kala+10, 520, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 520, $kala+10, 580, 	$thispage, $rectparam);
			$pdf->draw_text(25,  $kala+15, "#",							$thispage, $norm);
			$pdf->draw_text(45,  $kala+15, t("Tuotenumero", $kieli),	$thispage, $norm);
			$pdf->draw_text(190, $kala+15, t("Tuotenimi", $kieli),		$thispage, $norm);
			$pdf->draw_text(330, $kala+15, t("Tilattu", $kieli),		$thispage, $norm);
			$pdf->draw_text(375, $kala+15, t("Toimitus", $kieli),		$thispage, $norm);
			$pdf->draw_text(425, $kala+15, t("Yks.hinta", $kieli),		$thispage, $norm);
			$pdf->draw_text(473, $kala+15, t("Alekoodi", $kieli),		$thispage, $norm);
			$pdf->draw_text(530, $kala+15, t("Rivihinta", $kieli),		$thispage, $norm);
		}
		elseif (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_viivakoodi.inc") {
			// tulosta_lahete_viivakoodi.inc
			
			$pdf->draw_rectangle($kala+30, 20,  $kala+10, 40,  	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 40,  $kala+10, 180,  $thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 180, $kala+10, 480, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 480, $kala+10, 530, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 530, $kala+10, 580, 	$thispage, $rectparam);
			$pdf->draw_text(25,  $kala+15, "#",							$thispage, $norm);
			$pdf->draw_text(45,  $kala+15, t("Tuotenumero", $kieli),	$thispage, $norm);
			$pdf->draw_text(190, $kala+15, t("Tuotenimi", $kieli),		$thispage, $norm);
			$pdf->draw_text(490, $kala+15, t("Tilattu", $kieli),		$thispage, $norm);
			$pdf->draw_text(540, $kala+15, t("Toimitus", $kieli),		$thispage, $norm);
		}
		else{
			//tulosta_lahete_eiale_eihinta.inc
			
			$pdf->draw_rectangle($kala+30, 20,  $kala+10, 40,  	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 40,  $kala+10, 180,  $thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 180, $kala+10, 480, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 480, $kala+10, 530, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 530, $kala+10, 580, 	$thispage, $rectparam);
			$pdf->draw_text(25,  $kala+15, "#",							$thispage, $norm);
			$pdf->draw_text(45,  $kala+15, t("Tuotenumero", $kieli),	$thispage, $norm);
			$pdf->draw_text(190, $kala+15, t("Tuotenimi", $kieli),		$thispage, $norm);
			$pdf->draw_text(490, $kala+15, t("Tilattu", $kieli),		$thispage, $norm);
			$pdf->draw_text(540, $kala+15, t("Toimitus", $kieli),		$thispage, $norm);
		}
	}
}

if (!function_exists('rivi')) {
	function rivi($thispage, $tyyppi="") {
		global $pdf, $page, $lahetetyyppi, $sivu, $row, $kala, $rectparam, $kukarow, $rivinumerot, $kieli, $laskurow, $yhtiorow, $perheid, $jtid, $summa, $norm, $pieni, $rivinkorkeus;
			
		// viivat rivien v‰liin...
		$x[0] = 20;
		$x[1] = 580;
		$y[0] = $y[1] = $kala + $rivinkorkeus - 4;
				
		if (($perheid == 0 or $perheid != $row["perheid"]) and isset($perheid)) {
			$pdf->draw_line($x, $y, $thispage, $rectparam);
		}

		// Jos on paljon rivej‰ tehd‰‰n uusi otsikko...
		if ($kala <= 70) {
			$sivu++;
			
			loppu($thispage, 0);

			$page[$sivu] = uusi_sivu($tyyppi);
			$thispage    = $page[$sivu];
		}
		
		// JT-rivit pikkasen alasp‰in omalla otsikolla
		if ($jtid == "0" and $row["jtsort"] != "0") {						
			$kala = $kala-20;
			
			$x[0] = 20;
			$x[1] = 580;
			$y[0] = $y[1] = $kala - 4;
			$pdf->draw_line($x, $y, $page[$sivu], $rectparam);
				
			$pdf->draw_text(25,  $kala, "#",									$thispage, $norm);
			$pdf->draw_text(45,  $kala, t("J‰lkitoimitukseen", $kieli).":",		$thispage, $norm);
			$kala = $kala-$rivinkorkeus;
		}
		
		//Kieliversioitu tuotteen nimitys
		if (strtolower($kieli) != strtolower($yhtiorow['kieli'])) {
			$laji = 'nimitys_'.strtolower($kieli);
			$query = "SELECT selite FROM tuotteen_avainsanat WHERE yhtio = '$kukarow[yhtio]' and laji = '$laji' and tuoteno = '$row[tuoteno]' LIMIT 1";
			$nimresult = mysql_query($query) or pupe_error($query);
			
			if (mysql_num_rows($nimresult)>0) {
				$nimrow	= mysql_fetch_array($nimresult);
				$row['nimitys'] = $nimrow['selite'];
			}
		}
		
		// Trimmataan
		$row["kommentti"] = trim($row["kommentti"]);
		
		// Tilaajan rivinumero
		if ($row["tilaajanrivinro"] != 0) {
			$row["kommentti"] .= "\n".t("Rivinumero", $kieli).": ".$row["tilaajanrivinro"].". ";	
		}

		// Tutkitaan onko tuotteelle asiakaskommentti
		$askomquery = "	select * from asiakaskommentti
						where yhtio='$kukarow[yhtio]' and tuoteno='$row[tuoteno]' and ytunnus='$laskurow[ytunnus]'";
		$askomresul = mysql_query($askomquery) or pupe_error($askomquery);
	
		if (mysql_num_rows($askomresul) == 1) {
			$askomrow = mysql_fetch_array($askomresul);
	
			$row["kommentti"] .= "\n".$askomrow["kommentti"];
		}
		
		$row["perhe_kommentti"] = "";
		
		// Info tuoteperheest‰
		if ($row["perheid"] > 0 and $perheid != $row["perheid"]) {
			$numrows = 0;
			
			$query = "	SELECT vanhatunnus
						FROM lasku
						WHERE yhtio	= '$kukarow[yhtio]'
						and tunnus	= '$row[otunnus]'";
			$vtunres = mysql_query($query) or pupe_error($query);
			$vtunrow = mysql_fetch_array($vtunres);
			
			if ($vtunrow["vanhatunnus"] != 0) {
				$query = " 	SELECT GROUP_CONCAT(distinct tunnus SEPARATOR ',') tunnukset
							FROM lasku use index (yhtio_vanhatunnus)
							WHERE yhtio		= '$kukarow[yhtio]'
							and vanhatunnus = '$vtunrow[vanhatunnus]'";
				$perheresult = mysql_query($query) or pupe_error($query);
				$numrows = mysql_num_rows($perheresult);
			}
			
			if ($numrows == 0 or $vtunrow["vanhatunnus"] == 0) {
				$query = "  SELECT GROUP_CONCAT(distinct tunnus SEPARATOR ',') tunnukset
							FROM lasku use index (PRIMARY)
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus  = '$row[otunnus]'";
				$perheresult = mysql_query($query) or pupe_error($query);
			}                        
			
			if (mysql_num_rows($perheresult) > 0) {
				$perherow = mysql_fetch_array($perheresult);
				
				$query = "	SELECT distinct tilausrivi.tuoteno, tilausrivi.nimitys, varattu
							FROM tilausrivi
							JOIN tuote tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
							WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
							and tilausrivi.otunnus in ($perherow[tunnukset]) 
							and tilausrivi.perheid = '$row[perheid]'
							and tuote.tuotetyyppi != 'R'
							and tilausrivi.tyyppi != 'V' 
							ORDER BY tilausrivi.tunnus";
				$perheresult = mysql_query($query) or pupe_error($query);
				
				if (mysql_num_rows($perheresult) > 1) {
					while ($perherow = mysql_fetch_array($perheresult)) {
						if ($row["perhe_kommentti"] == "") {
							$row["perhe_kommentti"] .= t("Tuoteperhe", $kieli).": ";
							$row["perhe_kommentti"] .= $perherow["tuoteno"]." (".$row["varattu"].") ".$perherow["nimitys"].".\n".t("Sis‰lt‰‰ tuotteet", $kieli).": ";
						}
						else {
							$row["perhe_kommentti"] .= strtoupper($perherow["tuoteno"]).", ";
						}
					}
					$row["perhe_kommentti"] = substr($row["perhe_kommentti"],0,-2);
				}
			}						
		}
		
		// Trimmataan uudestaan
		$row["kommentti"] = trim($row["kommentti"]);
		
		//hatetaan tuotteen myyntihinta
		$query = "	SELECT round(if(isnull(hinnasto.hinta) or hinnasto.hinta='', round(tuote.myyntihinta/if('$laskurow[vienti_kurssi]'=0,1,'$laskurow[vienti_kurssi]'),2), hinnasto.hinta) / (1+(if('$yhtiorow[alv_kasittely]'='',0,tuote.alv)/100)), 2) myyntihinta,
       				tuote.aleryhma
                    FROM tuote
                    LEFT JOIN hinnasto on tuote.yhtio = hinnasto.yhtio and tuote.tuoteno = hinnasto.tuoteno and hinnasto.valkoodi = '$laskurow[valkoodi]'
                    WHERE tuote.tuoteno='$row[tuoteno]' and tuote.yhtio='$kukarow[yhtio]'";
        $asresult = mysql_query($query) or pupe_error($query);
		$asrow1 = mysql_fetch_array($asresult);
	
		//saadaan l‰hetteen summa menem‰‰n oikein, taaksep‰in yhteensopivasti
		$row["brutto_rivihinta"] 	= $asrow1["myyntihinta"] * ($row["kpl"]+$row["varattu"]+$row["jt"]);
		$row["myyntihinta"] 		= $asrow1["myyntihinta"];
		$row["aleryhma"] 			= $asrow1["aleryhma"];
		
		if ($laskurow['valkoodi'] != $yhtiorow['valkoodi']) {
			$row["hinta"] 	  = round(laskuval($row["hinta"], $laskurow["vienti_kurssi"]),2);
			$row["rivihinta"] = round(laskuval($row["rivihinta"], $laskurow["vienti_kurssi"]),2);
			$row["ovhhinta"]  = round(laskuval($row["ovhhinta"], $laskurow["vienti_kurssi"]),2);
		}
		
		$perheid 	 = $row["perheid"];
		$jtid		 = $row["jtsort"];
		$piilotarivi = "";
		
		// Alennus kuntoon
		if($row["netto"] != 'N' and $laskurow["erikoisale"] > 0) { 
			$row["ale"] = round((1-(1-($row["ale"]+$laskurow["erikoisale"]-($row["ale"]*$laskurow["erikoisale"]/100))/100))*100,2);
		}
		
		if ($row["perheid"] > 0 and substr($lahetetyyppi, 0, 6) == "perhe_") {

			// kyseess‰ on is‰
			if ($row["perheid"] == $row["tunnus"]) {

				$query = "	SELECT vanhatunnus
							FROM lasku
							WHERE yhtio	= '$kukarow[yhtio]'
							and tunnus	= '$row[otunnus]'";
				$vtunres = mysql_query($query) or pupe_error($query);
				$vtunrow = mysql_fetch_array($vtunres);

				if ($vtunrow["vanhatunnus"] != 0) {
					$query = " SELECT GROUP_CONCAT(distinct tunnus SEPARATOR ',') tunnukset
							FROM lasku use index (yhtio_vanhatunnus)
							WHERE yhtio         = '$kukarow[yhtio]'
							and vanhatunnus     = '$vtunrow[vanhatunnus]'";
					$perheresult = mysql_query($query) or pupe_error($query);
					$numrows = mysql_num_rows($perheresult);
				}

				if ($numrows == 0 or $vtunrow["vanhatunnus"] == 0) {
					$query = "  SELECT GROUP_CONCAT(distinct tunnus SEPARATOR ',') tunnukset
								FROM lasku use index (PRIMARY)
								WHERE yhtio    = '$kukarow[yhtio]'
								and tunnus    = '$row[otunnus]'";
					$perheresult = mysql_query($query) or pupe_error($query);
				}

				if (mysql_num_rows($perheresult) > 0) {
					$perherow = mysql_fetch_array($perheresult);

					// lasketaan is‰tuotteen riville lapsien hinnat yhteen
					$query = "	SELECT 
								round(sum(tilausrivi.hinta * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) rivihinta,
								round(round(sum(tilausrivi.hinta * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl)),2) / ($row[kpl]+$row[varattu]+$row[jt]), 2) hinta
								FROM tilausrivi
								JOIN lasku ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus
								WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
								and tilausrivi.otunnus in ($perherow[tunnukset])
								and tilausrivi.perheid = '$row[perheid]'";
					$riresult = mysql_query($query) or pupe_error($query);
					$perherow = mysql_fetch_array($riresult);
					
					$row["hinta"] 		= $perherow["hinta"];
					$row["rivihinta"] 	= $perherow["rivihinta"];
					$row["ale"] 		= round(100 * (1 - ($perherow["rivihinta"] / ($perherow["hinta"] * ($row["kpl"]+$row["varattu"]+$row["jt"])))),2);
				}
			}
			else {
				// lapsia ei lis‰t‰
				$piilotarivi = 1;
			}
		}
		
		$row["tilkpl"] 	= ($row["tilkpl"]*1);
		$row["varattu"] = ($row["varattu"]*1);
		$row["kpl"] 	= ($row["kpl"]*1);
		$kpl_rivilla 	= ($row["kpl"]+$row["varattu"]+$row["jt"]);
		
		if ($row["keratty"] != '' and !in_array($row["var"], array('J','P'))) {
			$row['varattu'] = ($row['varattu']+$row['kpl'])." ".strtolower($row["yksikko"]);
		}
		else {	
			if ($row["jt"] != 0) { // j‰lkitoimitus
				$row['varattu'] = t("**JT**", $kieli);
			}
			elseif ($row["var"] == 'P') { // puute
				$row['varattu'] = t("PUUTE", $kieli);
			}
			else {
				$row["varattu"] = "";
				$row["kpl"] 	= "";
			}
		}
		
		// Mahtuuko rivi t‰lle sivulle
		$kala_nyt = $kala;
				
		if(substr($lahetetyyppi, 0, 6) != "perhe_" or $piilotarivi == "") {
			if (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete.inc") {
				rivi_hinta_ale($devnull);
			}
			elseif (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_brutto.inc") {
				rivi_brutto($devnull);
			}
			elseif (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_eialeja.inc") {
				rivi_eialeja($devnull);
			}
			elseif (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_viivakoodi.inc") {
				rivi_viivakoodi($devnull);
			}
			else{
				rivi_ei_ale_ei_hinta($devnull);
			}
		}
		
		// Jos on paljon rivej‰ tehd‰‰n uusi otsikko...
		if ($kala <= 70) {						
			$sivu++;

			loppu($thispage, 0);

			$page[$sivu] = uusi_sivu($tyyppi);
			$thispage    = $page[$sivu];
		}
		else {
			$kala = $kala_nyt;
		}
		
		if(substr($lahetetyyppi, 0, 6) != "perhe_" or $piilotarivi == "") {
			if (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete.inc") {
				// tulosta_lahete.inc
				if ($row["jt"] == 0 and $row["var"] != 'P') {
					$summa += $row['rivihinta'];
				}
				else {
					$row['rivihinta'] = 0;
				}
				
				rivi_hinta_ale($thispage);
			}
			elseif (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_brutto.inc") {
				// tulosta_lahete_brutto.inc
				if ($row["jt"] == 0 and $row["var"] != 'P') {
					$summa += round($row['ovhhinta'] * $kpl_rivilla, 2);
				}
				else {
					$row['rivihinta'] = 0;
				}
				
				rivi_brutto($thispage);
			}
			elseif (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_eialeja.inc") {
				//tulosta_lahete_eialeja.inc
				if ($row["jt"] == 0 and $row["var"] != 'P') {
					$summa += $row['brutto_rivihinta'];
				}
				else {
					$row['rivihinta'] = 0;
				}
				
				rivi_eialeja($thispage);
			}
			elseif (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_viivakoodi.inc") {
				// tulosta_lahete_viivakoodi.inc				
				rivi_viivakoodi($thispage);
			}
			else{
				//tulosta_lahete_eiale_eihinta.inc				
				rivi_ei_ale_ei_hinta($thispage);
			}
		}	
	}
}

if (!function_exists('rivi_hinta_ale')) {
	function rivi_hinta_ale($thispage) {
		global $pdf, $row, $kala, $rivinumerot, $norm, $pieni, $rivinkorkeus;
	
		if (trim($row["perhe_kommentti"]) != '') {
			$pohja = $pdf->draw_paragraph($kala+$norm["height"], 45, 60, 480, $row["perhe_kommentti"],	$thispage, $norm);						
			$kala = $pohja - $rivinkorkeus;
		}
		
		$pdf->draw_text(23,  $kala, $rivinumerot[$row["tunnus"]],							$thispage, $pieni);
		$pdf->draw_text(45,  $kala, $row["tuoteno"],										$thispage, $norm);
		
		$oikpos = $pdf->strlen($row["tilkpl"]." ".strtolower($row["yksikko"]), $norm);
		$pdf->draw_text(365-$oikpos, $kala, $row["tilkpl"]." ".strtolower($row["yksikko"]), $thispage, $norm);
				
		$oikpos = $pdf->strlen($row["varattu"], $norm);
		$pdf->draw_text(415-$oikpos, $kala, $row["varattu"], 								$thispage, $norm);
				
		$oikpos = $pdf->strlen(sprintf('%.2f', $row["hinta"]), $norm);
		$pdf->draw_text(465-$oikpos, $kala, sprintf('%.2f', $row["hinta"]), 				$thispage, $norm);
				
		$oikpos = $pdf->strlen(($row["ale"]*1)."%", $norm);
		$pdf->draw_text(515-$oikpos, $kala, ($row["ale"]*1)."%", 							$thispage, $norm);
		
		$oikpos = $pdf->strlen(sprintf('%.2f', $row["rivihinta"]), $norm);
		$pdf->draw_text(575-$oikpos, $kala, sprintf('%.2f', $row["rivihinta"]), 			$thispage, $norm);
				
		if ($pdf->strlen($row["nimitys"], $norm) >= 135) {
			$kala = $kala - $rivinkorkeus;
		}
		
		$pdf->draw_text(190, $kala, $row["nimitys"], 										$thispage, $norm);
		
		$kala = $kala - $rivinkorkeus;
		
		if ($row["kommentti"] != '') {
			$pohja = $pdf->draw_paragraph($kala+$norm["height"]+5, 45, 60, 580, $row["kommentti"],	$thispage, $norm);						
			$kala = $pohja - $rivinkorkeus;
		}
	}
}

if (!function_exists('rivi_brutto')) {
	function rivi_brutto($thispage) {
		global $pdf, $row, $kala, $rivinumerot, $norm, $pieni, $rivinkorkeus;
		
		$pdf->draw_text(23,  $kala, $rivinumerot[$row["tunnus"]],							$thispage, $pieni);
		
		if (trim($row["perhe_kommentti"]) != '') {
			$pohja = $pdf->draw_paragraph($kala+$norm["height"], 45, 60, 480, $row["perhe_kommentti"],	$thispage, $norm);						
			$kala = $pohja - $rivinkorkeus;
		}
		
		$pdf->draw_text(45,  $kala, $row["tuoteno"],										$thispage, $norm);
		
		$oikpos = $pdf->strlen($row["tilkpl"]." ".strtolower($row["yksikko"]), $norm);
		$pdf->draw_text(465-$oikpos, $kala, $row["tilkpl"]." ".strtolower($row["yksikko"]), $thispage, $norm);
				
		$oikpos = $pdf->strlen($row["varattu"], $norm);
		$pdf->draw_text(515-$oikpos, $kala, $row["varattu"], 								$thispage, $norm);
			
		$oikpos = $pdf->strlen(sprintf('%.2f', $row["ovhhinta"]), $norm);
		$pdf->draw_text(575-$oikpos, $kala, sprintf('%.2f', $row["ovhhinta"]), 				$thispage, $norm);
				
		if ($pdf->strlen($row["nimitys"], $norm) >= 270) {
			$kala = $kala - $rivinkorkeus;
		}
		$pdf->draw_text(145, $kala, $row["nimitys"], 										$thispage, $norm);
	
		$kala = $kala - $rivinkorkeus;
		
		if ($row["kommentti"] != '') {
			$pohja = $pdf->draw_paragraph($kala+$norm["height"]+5, 45, 60, 580, $row["kommentti"],	$thispage, $norm);						
			$kala = $pohja - $rivinkorkeus;
		}
	}
}

if (!function_exists('rivi_ei_ale_ei_hinta')) {
	function rivi_ei_ale_ei_hinta($thispage) {
		global $pdf, $row, $kala, $rivinumerot, $norm, $pieni, $rivinkorkeus;
		
		$pdf->draw_text(23,  $kala, $rivinumerot[$row["tunnus"]],							$thispage, $pieni);
		
		if (trim($row["perhe_kommentti"]) != '') {
			$pohja = $pdf->draw_paragraph($kala+$norm["height"], 45, 60, 480, $row["perhe_kommentti"],	$thispage, $norm);						
			$kala = $pohja - $rivinkorkeus;
		}
		
		$pdf->draw_text(45,  $kala, $row["tuoteno"],										$thispage, $norm);
		
		
		$oikpos = $pdf->strlen($row["tilkpl"]." ".strtolower($row["yksikko"]), $norm);
		$pdf->draw_text(525-$oikpos, $kala, $row["tilkpl"]." ".strtolower($row["yksikko"]), $thispage, $norm);
				
		$oikpos = $pdf->strlen($row["varattu"], $norm);
		$pdf->draw_text(575-$oikpos, $kala, $row["varattu"], 								$thispage, $norm);
				
		if ($pdf->strlen($row["nimitys"], $norm) >= 290) {
			$kala = $kala - $rivinkorkeus;
		}
		$pdf->draw_text(190, $kala, $row["nimitys"], 										$thispage, $norm);
	
		$kala = $kala - $rivinkorkeus;	
		
		if ($row["kommentti"] != '') {			
			$pohja = $pdf->draw_paragraph($kala+$norm["height"]+5, 45, 60, 580, $row["kommentti"],	$thispage, $norm);	
			$kala = $pohja - $rivinkorkeus;
		}
	}
}

if (!function_exists('rivi_eialeja')) {
	function rivi_eialeja($thispage) {
		global $pdf, $row, $kala, $rivinumerot, $norm, $pieni, $rivinkorkeus;
	
		$pdf->draw_text(23,  $kala, $rivinumerot[$row["tunnus"]],							$thispage, $pieni);
		
		if (trim($row["perhe_kommentti"]) != '') {
			$pohja = $pdf->draw_paragraph($kala+$norm["height"], 45, 60, 480, $row["perhe_kommentti"],	$thispage, $norm);						
			$kala = $pohja - $rivinkorkeus;
		}
		
		$pdf->draw_text(45,  $kala, $row["tuoteno"],										$thispage, $norm);	
		
		$oikpos = $pdf->strlen($row["tilkpl"]." ".strtolower($row["yksikko"]), $norm);
		$pdf->draw_text(365-$oikpos, $kala, $row["tilkpl"]." ".strtolower($row["yksikko"]), $thispage, $norm);
				
		$oikpos = $pdf->strlen($row["varattu"], $norm);
		$pdf->draw_text(415-$oikpos, $kala, $row["varattu"], 								$thispage, $norm);
		
		
		$oikpos = $pdf->strlen($row["myyntihinta"], $norm);
		$pdf->draw_text(465-$oikpos, $kala, $row["myyntihinta"], 							$thispage, $norm);		
		
		$pdf->draw_text(475, $kala, $row["aleryhma"],										$thispage, $norm);
		
		$oikpos = $pdf->strlen(sprintf('%.2f', $row["brutto_rivihinta"]), $norm);
		$pdf->draw_text(575-$oikpos, $kala, sprintf('%.2f', $row["brutto_rivihinta"]), 		$thispage, $norm);
	
		if ($pdf->strlen($row["nimitys"], $norm) >= 135) {
			$kala = $kala - $rivinkorkeus;
		}
		$pdf->draw_text(190, $kala, $row["nimitys"], 										$thispage, $norm);
	
		$kala = $kala - $rivinkorkeus;
		
		if ($row["kommentti"] != '') {
			$pohja = $pdf->draw_paragraph($kala+$norm["height"]+5, 45, 60, 580, $row["kommentti"],	$thispage, $norm);							
			$kala = $pohja - $rivinkorkeus;
		}
	}
}

if (!function_exists('rivi_viivakoodi')) {
	function rivi_viivakoodi($thispage) {
		global $pdf, $row, $kala, $rivinumerot, $norm, $pieni, $rivinkorkeus, $kukarow;
	
		$pdf->draw_text(23,  $kala, $rivinumerot[$row["tunnus"]],								$thispage, $pieni);
		
		if (trim($row["perhe_kommentti"]) != '') {
			$pohja = $pdf->draw_paragraph($kala+$norm["height"], 45, 60, 480, $row["perhe_kommentti"],	$thispage, $norm);						
			$kala = $pohja - $rivinkorkeus;
		}
		
		$pdf->draw_text(45,  $kala, $row["tuoteno"],											$thispage, $norm);
		
		$oikpos = $pdf->strlen($row["tilkpl"]." ".strtolower($row["yksikko"]), $norm);
		$pdf->draw_text(525-$oikpos, $kala, $row["tilkpl"]." ".strtolower($row["yksikko"]), 	$thispage, $norm);
				
		$oikpos = $pdf->strlen($row["varattu"], $norm);
		$pdf->draw_text(575-$oikpos, $kala, $row["varattu"], 									$thispage, $norm);
		
		if ($pdf->strlen($row["nimitys"], $norm) >= 290) {
			$kala = $kala - $rivinkorkeus;
		}
		$pdf->draw_text(190, $kala, $row["nimitys"], 											$thispage, $norm);
	
		$kala = $kala - $rivinkorkeus;
		
		if ($row["kommentti"] != '') {
			$pohja = $pdf->draw_paragraph($kala+$norm["height"]+5, 45, 60, 580, $row["kommentti"],	$thispage, $norm);						
			$kala = $pohja - $rivinkorkeus;
		}
		
		// tutkitaan onko tuotteelle eankoodi
		$eanquery = "	select eankoodi 
						from tuote
						where yhtio='$kukarow[yhtio]' and tuoteno='$row[tuoteno]'";
		$eanres = mysql_query($eanquery) or pupe_error($eanquery);
		$eanrow = mysql_fetch_array($eanres);
						
		if ($eanrow["eankoodi"] != "") {
						
			//oletukset viivakoodeille
			$output   = "png";
			$width    = "220";
			$height   = "20";
			$xres     = "1";
			$font     = "1";
			$drawtext = "on";
			
			$style  = BCS_ALIGN_CENTER;
			$style |= ($output  == "png") 	? BCS_IMAGE_PNG  	: 0;
			$style |= ($output  == "jpeg") 	? BCS_IMAGE_JPEG 	: 0;
			$style |= ($border  == "on") 	? BCS_BORDER 		: 0;
			$style |= ($drawtext== "on") 	? BCS_DRAW_TEXT  	: 0;
			$style |= ($stretchtext== "on") ? BCS_STRETCH_TEXT  : 0;
			$style |= ($negative== "on") 	? BCS_REVERSE_COLOR : 0;
		
			//viivakoodin arvo
			$barcode  = $eanrow["eankoodi"];
	
			//luodaan viivakoodiolio
			$obj = "";
			$obj = new C39Object($width, $height, $style, $barcode);
	
			if ($obj) {
				$obj->SetFont($font);
				$obj->DrawObject($xres);
	
				//flushataan pdf ja saadaam filenimi johon se tallennettiin
				$nimi1 = $obj->FlushObject();
	
				//keksit‰‰n uudelle failille joku varmasti uniikki nimi:
				list($usec, $sec) = explode(' ', microtime());
				mt_srand((float) $sec + ((float) $usec * 100000));
				$nimi2 = "/tmp/".md5(uniqid(mt_rand(), true)).".png";
	
				passthru("/usr/bin/convert -colors 2 ".$nimi1." ".$nimi2, $palautus);
	
				$fh = fopen($nimi2, "r");
				$data = fread($fh, filesize($nimi2));
				fclose($fh);
				$image = $pdf->png_embed($data);
	
				if(!$image) {
					echo t("Viivakoodivirhe").": ".$image.$data;
					exit;
				}
			
				$placement = $pdf->image_place($image, $kala-8, 350, $thispage);
				$kala = $kala - $rivinkorkeus-5;
	
				unset($obj);
	
				//dellataan tmp filet kuleksimasta
				system("rm -f $nimi1 $nimi2");
			}
		}
	}
}

if (!function_exists('loppu')) {
	function loppu ($thispage, $tots) {
		global $pdf, $sivu, $laskurow, $viite, $pieni, $norm, $summa, $rectparam, $kieli, $yhtiorow, $kala, $rivinkorkeus, $page;
		
		//Alimmat kolme laatikkoa, yhtiˆtietoja
		$pdf->draw_rectangle(70, 20, 20, 580,	$thispage, $rectparam);
		$pdf->draw_rectangle(70, 207, 20, 580,	$thispage, $rectparam);
		$pdf->draw_rectangle(70, 394, 20, 580,	$thispage, $rectparam);

		$pdf->draw_text(30, 55, $yhtiorow["nimi"],		$thispage, $pieni);
		$pdf->draw_text(30, 45, $yhtiorow["osoite"],	$thispage, $pieni);
		$pdf->draw_text(30, 35, $yhtiorow["postino"]."  ".$yhtiorow["postitp"],	$thispage, $pieni);
		$pdf->draw_text(30, 25, $yhtiorow["maa"],		$thispage, $pieni);

		$pdf->draw_text(217, 55, t("Puhelin", $kieli).":",			$thispage, $pieni);
		$pdf->draw_text(257, 55, $yhtiorow["puhelin"],				$thispage, $pieni);
		$pdf->draw_text(217, 45, t("Telefax", $kieli).":",			$thispage, $pieni);
		$pdf->draw_text(257, 45, $yhtiorow["fax"],					$thispage, $pieni);
		$pdf->draw_text(217, 35, t("S‰hkˆposti", $kieli).":",		$thispage, $pieni);
		$pdf->draw_text(257, 35, $yhtiorow["email"],				$thispage, $pieni);

		//jos on suomalainen yritys tehd‰‰n ytunnus n‰tiks
		if (strtoupper($yhtiorow["maa"])== 'FI') {
			//muutetaan ytunnus takas oikean n‰kˆseks
			$ytunpit = 8-strlen($yhtiorow["ytunnus"]);

			if ($ytunpit > 0) {
				$uytunnus = $yhtiorow["ytunnus"];
				while ($ytunpit > 0) {
				    $uytunnus = "0".$uytunnus; $ytunpit--;
				}
			}
			else {
				$uytunnus = $yhtiorow["ytunnus"];
			}

			$uytunnus = substr($uytunnus,0,7)."-".substr($uytunnus,7,1);
		}
		else {
			$uytunnus = $yhtiorow["ytunnus"];
		}

        if ($laskurow['yhtio_ovttunnus'] != $yhtiorow['ovttunnus'] and $laskurow['yhtio_ovttunnus'] != '') {
            $uytunnus = $laskurow['yhtio_ovttunnus'];
        } 
		else {
    		$uytunnus = $yhtiorow["maa"]."-".$uytunnus;
        }
        

		$pdf->draw_text(404, 55, t("Y-tunnus", $kieli).":",			$thispage, $pieni);
		$pdf->draw_text(444, 55, $uytunnus,							$thispage, $pieni);
		$pdf->draw_text(404, 45, t("Kotipaikka", $kieli).":",		$thispage, $pieni);
		$pdf->draw_text(444, 45, $yhtiorow["kotipaikka"],			$thispage, $pieni);
		$pdf->draw_text(404, 35, t("Enn.per.rek", $kieli),			$thispage, $pieni);
		$pdf->draw_text(404, 25, t("Alv.rek", $kieli),				$thispage, $pieni);
		
		if ($tots == 1 and $summa != 0) {	
			$pdf->draw_text(450, 72, t("Yhteens‰", $kieli).":", $thispage, $norm);
						
			$oikpos = $pdf->strlen(sprintf('%.2f',$summa)." ".$laskurow["valkoodi"], $norm);					
			$pdf->draw_text(575-$oikpos, 72, sprintf('%.2f',$summa)." ".$laskurow["valkoodi"], $thispage, $norm);
		}
		
		if ($tots != 1) {
			$pdf->draw_text(530, 72, t("Jatkuu", $kieli)."...", $thispage, $norm);
		}
		
		if ($tots == 1) {
			// viivat rivien v‰liin...
			$x[0] = 20;
			$x[1] = 580;
			$y[0] = $y[1] = $kala + $rivinkorkeus - 4;

			$pdf->draw_line($x, $y, $thispage, $rectparam);
			
			for ($pp=1; $pp<=$sivu; $pp++) {
				$pdf->draw_text(330, 803, "$pp / $sivu", $page[$pp], $norm);
			}
		}
	}
}

if (!function_exists('kuittaus')) {
	function kuittaus ($tyyppi="") {
		global $pdf, $laskurow, $viite, $p, $summa, $rectparam, $kieli, $yhtiorow;
		
		$sivu++;

		$page[$sivu] = alku($tyyppi);
		$thispage    = $page[$sivu];
		
		$pdf->draw_text(50, 400, t("Allekirjoitus").": _____________________", 	$thispage, $norm);
		$pdf->draw_text(300, 400, t("Nimenselvennys").": _____________________", 	$thispage, $norm);
	}
}

if (!function_exists('print_pdf')) {
	function print_pdf ($komento, $tyyppi="") {
		global $pdf, $kukarow, $yhtiorow, $kateinen, $returnvalue, $kieli, $tee;
	
		$oslapp='';
		$returnvalue=0;
	
		//keksit‰‰n uudelle failille joku varmasti uniikki nimi:
		list($usec, $sec) = explode(' ', microtime());
		mt_srand((float) $sec + ((float) $usec * 100000));
		$pdffilenimi = "/tmp/Lahete-".md5(uniqid(mt_rand(), true)).".pdf";
	
		//kirjoitetaan pdf faili levylle..
		$fh = fopen($pdffilenimi, "w");
		if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF create error $pdffilenimi");
		fclose($fh);			
	
		// itse print komento...		
		if ($komento == 'email' or substr($komento,0,12) == 'asiakasemail') {
			$liite = $pdffilenimi;
			
			if ($tyyppi == "TILAUSVAHVISTUS") {
				$kutsu = "Tilausvahvistus";
				echo t("Tilausvahvistus tulostuu")."...<br>";
			}
			else {
				$kutsu = "Lahete";
				echo t("L‰hete tulostuu")."...<br>";
			}
			
			if ($kukarow["extranet"] == '') {
				require("../inc/sahkoposti.inc");
			}
			else {
				require("sahkoposti.inc");
			}
		}
		elseif ($tee == 'NAYTATILAUS') {
			//Tyˆnnet‰‰n tuo pdf vaan putkeen!
			echo file_get_contents($pdffilenimi);
		}		
		elseif ($komento != '' and $komento != 'edi') {
			echo t("L‰hete tulostuu")."...<br>";
			$line = exec("$komento $pdffilenimi", $output, $returnvalue);
		}
		
		//poistetaan tmp file samantien kuleksimasta...
		system("rm -f $pdffilenimi");
	}
}

?>