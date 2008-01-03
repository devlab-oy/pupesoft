<?php

//viivakoodien tulostamista varten
define (__TRACE_ENABLED__, false);
define (__DEBUG_ENABLED__, false);

// jos php-gd on installoitu niin loidataab barcode library
if (in_array("gd", get_loaded_extensions())) {
	require_once("barcode/barcode.php");
	require_once("barcode/c39object.php");
}

require_once("pdflib/phppdflib.class.php");

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
$rivinkorkeus			= 17;

if (!function_exists('alku')) {
	function alku($tyyppi = "") {
		global $pdf, $sivu, $lahetetyyppi, $laskurow, $yhtiorow, $viite, $pieni, $norm, $boldi, $pieni_boldi, $iso, $kala, $rectparam, $kukarow, $kieli, $asrow, $tilausnumeroita;
	
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
			
		unset($data);		
		if( (int) $yhtiorow["lasku_logo"] > 0) {
			$liite = hae_liite($yhtiorow["lasku_logo"], "Yllapito", "array");
			$data = $liite["data"];
			$isizelogo[0] = $liite["image_width"];
			$isizelogo[1] = $liite["image_height"];
			unset($liite);
		}
		elseif(file_exists($yhtiorow["lasku_logo"])) {
			$filename = $yhtiorow["lasku_logo"];

			$fh = fopen($filename, "r");
			$data = fread($fh, filesize($filename));
			fclose($fh);
			
			$isizelogo = getimagesize($yhtiorow["lasku_logo"]);
		}
		
		if($data) {
			$image = $pdf->jfif_embed($data);
		
			if(!$image) {
				echo t("Logokuvavirhe");
			}
			else {
				
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
	
		//maksuehto tekstinä
		$maksuehto = $masrow["teksti"]." ".$masrow["kassa_teksti"];
		
		$pdf->draw_text(410, 815, $laskurow["hyvaksynnanmuutos"], 	$thispage, $iso);
		
		if ($tyyppi == "SIIRTOLISTA") {
			$pdf->draw_text(310,815, t("Siirtolista", $kieli), 			$thispage, $iso);	
		}
		else {
			$pdf->draw_text(310,815, t("Keräyslista", $kieli), 			$thispage, $iso);		
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
		$pdf->draw_text(310, 792, t("Tilausnumero(t)", $kieli), 			$thispage, $pieni);
		$pdf->draw_text(310, 782, $tilausnumeroita,							$thispage, $boldi);
		
		$pdf->draw_rectangle(779, 300, 758, 580, $thispage, $rectparam);
		$pdf->draw_rectangle(779, 420, 758, 580, $thispage, $rectparam);
		$pdf->draw_text(310, 771, t("Keräysaika", $kieli), 					$thispage, $pieni);
		
		if ($laskurow["keraysvko"] != '') {
			$DAY_ARRAY = array(1=>"Ma","Ti","Ke","To","Pe","La","Su");

			$taika = t("Vko")." ".date("W", strtotime($laskurow["kerayspvm"]));

			if ($laskurow['keraysvko'] != '6') {
				$taika .= "/".$DAY_ARRAY[$laskurow["keraysvko"]];
			}

			$pdf->draw_text(310, 761, $taika, 								$thispage, $boldi);
		}
		else {			
			$pdf->draw_text(310, 761, tv1dateconv($laskurow["kerayspvm"]), 	$thispage, $boldi);
		}
		
		$pdf->draw_text(430, 771, t("Toimitusaika", $kieli), 				$thispage, $pieni);
		
		if ($laskurow["toimvko"] != '') {
			$DAY_ARRAY = array(1=>"Ma","Ti","Ke","To","Pe","La","Su");

			$taika = t("Vko")." ".date("W", strtotime($laskurow["toimaika"]));

			if ($laskurow['toimvko'] != '6') {
				$taika .= "/".$DAY_ARRAY[$laskurow["toimvko"]];
			}

			$pdf->draw_text(430, 761, $taika, 								$thispage, $boldi);
		}
		else {			
			$pdf->draw_text(430, 761, tv1dateconv($laskurow["toimaika"]), 	$thispage, $boldi);
		}
		
		if ($asrow["asiakasnro"] == "" and $laskurow["ytunnus"] != "") $asrow["asiakasnro"] = $laskurow["ytunnus"];
		
		$pdf->draw_rectangle(758, 300, 737, 580, $thispage, $rectparam);
		$pdf->draw_rectangle(758, 420, 737, 580, $thispage, $rectparam);
		$pdf->draw_text(310, 750, t("Asiakasnumero", $kieli), 		$thispage, $pieni);
		$pdf->draw_text(310, 740, $asrow["asiakasnro"], 			$thispage, $norm);
		$pdf->draw_text(430, 750, t("Päiväys", $kieli),				$thispage, $pieni);
		$pdf->draw_text(430, 740, tv1dateconv(date("Y-m-d")), 		$thispage, $norm);
	

		$pdf->draw_rectangle(737, 300, 716, 580, $thispage, $rectparam);
		$pdf->draw_rectangle(737, 420, 716, 580, $thispage, $rectparam);
		$pdf->draw_text(310, 729, t("Myyjä", $kieli), 		$thispage, $pieni);
		
		//etsitään myyjän nimi
		$query  = "	select nimi
					from kuka
					where tunnus='$laskurow[myyja]' and yhtio='$kukarow[yhtio]'";
		$myyresult = mysql_query($query) or pupe_error($query);
		$myyrow = mysql_fetch_array($myyresult);

		$pdf->draw_text(310, 719, $myyrow["nimi"], 										$thispage, $norm);
		
		$pdf->draw_text(430, 729, t("Tilauspvm", $kieli),							 	$thispage, $pieni);
		$pdf->draw_text(430, 719, tv1dateconv($laskurow["luontiaika"], "pitka"), 		$thispage, $norm);

		$pdf->draw_rectangle(716, 300, 695, 580, $thispage, $rectparam);
		$pdf->draw_text(310, 708, t("Toimitustapa", $kieli), 		$thispage, $pieni);
		$pdf->draw_text(310, 698, $laskurow["toimitustapa"], 		$thispage, $boldi);
		
		//etsitään rahtisopimusnumero
		$query = "SELECT rahtisopimus from rahtisopimukset where ytunnus = '$laskurow[ytunnus]' and toimitustapa = '$laskurow[toimitustapa]'";
		$rahtiresult = mysql_query($query) or pupe_error($query);
		$rahtirow = mysql_fetch_array($rahtiresult);
		
		$pdf->draw_rectangle(716, 420, 695, 580, $thispage, $rectparam);
		$pdf->draw_text(430, 708, t("Rahtisopimus", $kieli), 		$thispage, $pieni);
		$pdf->draw_text(430, 698, $rahtirow["rahtisopimus"],	 	$thispage, $boldi);

		$pdf->draw_rectangle(695, 300, 674, 580, $thispage, $rectparam);
		$pdf->draw_text(310, 687, t("Toimitusehto", $kieli), 	$thispage, $pieni);
		$pdf->draw_text(310, 677, $laskurow["toimitusehto"], 	$thispage, $norm);
		
		$pdf->draw_rectangle(674, 300, 653, 580, $thispage, $rectparam);
		$pdf->draw_text(310, 666, t("Maksuehto", $kieli), 		$thispage, $pieni);
		$pdf->draw_text(310, 656, $maksuehto, 					$thispage, $norm);

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
		
		if (trim($laskurow['comments']) != '') {
			$komm .= "\n".t("Kommentti", $kieli).":###".wordwrap(str_replace("\n","\n###", $laskurow['comments']), 90, "\n###");							
		}
		
		if (trim($laskurow['sisviesti2']) != '') {
			$komm .= "\n".t("Kommentti", $kieli)." 2:###".wordwrap(str_replace("\n","\n###", $laskurow['sisviesti2']), 90, "\n###");							
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
		unset($obj);
		if (class_exists("C39Object")) { 
			$obj = new C39Object($width, $height, $style, $barcode);
		}

		if ($obj) {
			$obj->SetFont($font);
			$obj->DrawObject($xres);
	
			//flushataan pdf ja saadaam filenimi johon se tallennettiin
			$nimi1 = $obj->FlushObject();
	
			//keksitään uudelle failille joku varmasti uniikki nimi:
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
	
		rivi_otsikot($thispage);
		
		return($thispage);
	}
}

if (!function_exists('uusi_sivu')) {
	function uusi_sivu ($tyyppi = "") {
		global $yhtiorow, $kukarow, $toim, $pdf, $laskurow, $sivu, $rectparam, $norm, $pieni, $iso, $maksuehto, $kateistyyppi, $kieli, $boldi, $kala, $tilausnumeroita;

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
			
		unset($data);		
		if( (int) $yhtiorow["lasku_logo"] > 0) {
			$liite = hae_liite($yhtiorow["lasku_logo"], "Yllapito", "array");
			$data = $liite["data"];
			$isizelogo[0] = $liite["image_width"];
			$isizelogo[1] = $liite["image_height"];
			unset($liite);
		}
		elseif(file_exists($yhtiorow["lasku_logo"])) {
			$filename = $yhtiorow["lasku_logo"];

			$fh = fopen($filename, "r");
			$data = fread($fh, filesize($filename));
			fclose($fh);
			
			$isizelogo = getimagesize($yhtiorow["lasku_logo"]);
		}
		
		if($data) {
			$image = $pdf->jfif_embed($data);

			if(!$image) {
				echo t("Logokuvavirhe");
			}
			else {
				
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
	
		//maksuehto tekstinä
		$maksuehto = $masrow["teksti"]." ".$masrow["kassa_teksti"];
		
		$pdf->draw_text(410, 815, $laskurow["hyvaksynnanmuutos"], 			$thispage, $iso);
		
		
		if ($tyyppi == "SIIRTOLISTA") {
			$pdf->draw_text(310,815, t("Siirtolista", $kieli), 				$thispage, $iso);	
		}
		else {
			$pdf->draw_text(310,815, t("Keräyslista", $kieli), 				$thispage, $iso);		
		}
				
		$pdf->draw_text(310, 803, t("Sivu", $kieli), 						$thispage, $norm);

		//Oikea sarake
		$pdf->draw_rectangle(800, 300, 779, 580, $thispage, $rectparam);		
		$pdf->draw_text(310, 792, t("Tilausnumero(t)", $kieli), 			$thispage, $pieni);
		$pdf->draw_text(310, 782, $tilausnumeroita,							$thispage, $boldi);
		
		$pdf->draw_rectangle(779, 300, 758, 580, $thispage, $rectparam);
		$pdf->draw_rectangle(779, 420, 758, 580, $thispage, $rectparam);
		$pdf->draw_text(310, 771, t("Keräysaika", $kieli), 					$thispage, $pieni);
		
		if ($laskurow["keraysvko"] != '') {
			$DAY_ARRAY = array(1=>"Ma","Ti","Ke","To","Pe","La","Su");

			$taika = t("Vko")." ".date("W", strtotime($laskurow["kerayspvm"]));

			if ($laskurow['keraysvko'] != '6') {
				$taika .= "/".$DAY_ARRAY[$laskurow["keraysvko"]];
			}

			$pdf->draw_text(310, 761, $taika, 								$thispage, $boldi);
		}
		else {			
			$pdf->draw_text(310, 761, tv1dateconv($laskurow["kerayspvm"]), 	$thispage, $boldi);
		}
		
		$pdf->draw_text(430, 771, t("Toimitusaika", $kieli), 				$thispage, $pieni);
		
		if ($laskurow["toimvko"] != '') {
			$DAY_ARRAY = array(1=>"Ma","Ti","Ke","To","Pe","La","Su");

			$taika = t("Vko")." ".date("W", strtotime($laskurow["toimaika"]));

			if ($laskurow['toimvko'] != '6') {
				$taika .= "/".$DAY_ARRAY[$laskurow["toimvko"]];
			}

			$pdf->draw_text(430, 761, $taika, 								$thispage, $boldi);
		}
		else {			
			$pdf->draw_text(430, 761, tv1dateconv($laskurow["toimaika"]), 	$thispage, $boldi);
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
	
			//keksitään uudelle failille joku varmasti uniikki nimi:
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
		
		rivi_otsikot($thispage);
				
		return($thispage);
	}
}

if (!function_exists('rivi_otsikot')) {
	function rivi_otsikot ($thispage) {
		global $yhtiorow, $kukarow, $toim, $pdf, $laskurow, $sivu, $rectparam, $norm, $pieni, $maksuehto, $kateistyyppi, $kieli, $boldi, $kala;
	
		$pdf->draw_rectangle($kala+30, 20, 20, 580, $thispage, $rectparam);
		
		$pdf->draw_rectangle($kala+30, 20,  $kala+10, 40,  $thispage, 	$rectparam);
		$pdf->draw_rectangle($kala+30, 40,  $kala+10, 120, $thispage, 	$rectparam);
		$pdf->draw_rectangle($kala+30, 120, $kala+10, 320, $thispage, 	$rectparam);
		$pdf->draw_rectangle($kala+30, 320, $kala+10, 420, $thispage, 	$rectparam);
		$pdf->draw_rectangle($kala+30, 420, $kala+10, 470, $thispage, 	$rectparam);
		$pdf->draw_rectangle($kala+30, 470, $kala+10, 520, $thispage, 	$rectparam);
		$pdf->draw_rectangle($kala+30, 520, $kala+10, 580, $thispage, 	$rectparam);

		$pdf->draw_text(25,  $kala+15, "#",									$thispage, $norm);
		$pdf->draw_text(45,  $kala+15, t("Paikka", $kieli),					$thispage, $norm);
		$pdf->draw_text(125, $kala+15, t("Tuotenumero/Tuotenimi", $kieli),	$thispage, $norm);
		$pdf->draw_text(330, $kala+15, t("Toimittajan tuoteno", $kieli),	$thispage, $norm);
		$pdf->draw_text(425, $kala+15, t("Hyllyssä", $kieli),				$thispage, $norm);
		$pdf->draw_text(473, $kala+15, t("Tilattu", $kieli),				$thispage, $norm);
		$pdf->draw_text(530, $kala+15, t("Toimitus", $kieli),				$thispage, $norm);
		
		$kala -= 3;
	}
}

if (!function_exists('rivi')) {
	function rivi($thispage, $tyyppi="") {
		global $pdf, $page, $lahetetyyppi, $sivu, $row, $kala, $rectparam, $kukarow, $rivinumerot, $kieli, $laskurow, $yhtiorow, $perheid, $perheid2, $norm, $pieni, $rivinkorkeus, $paino;
		
		// viivat rivien väliin...
		$x[0] = 20;
		$x[1] = 580;
		$y[0] = $y[1] = $kala + $rivinkorkeus - 4;

		if ((($perheid == 0 and $perheid2 == 0) or $perheid != $row["perheid"] or $perheid2 != $row["perheid2"]) and isset($perheid) and isset($perheid2)) {
			$pdf->draw_line($x, $y, $thispage, $rectparam);
		}

		//jos ollaan liian pitkällä tehdään uusi otsikko...
		if ($kala < 95) {
			
			$sivu++;

			loppu($thispage, 0);

			$page[$sivu] = uusi_sivu($tyyppi);
			$thispage    = $page[$sivu];

		}

		if ($row["var"] == "" or  $row["var"] == "H"
			or ($row["var"] == "P" and ($yhtiorow["puute_jt_kerataanko"] == "P" or $yhtiorow["puute_jt_kerataanko"] == "Q"))
			or ($row["var"] == "J" and ($yhtiorow["puute_jt_kerataanko"] == "J" or $yhtiorow["puute_jt_kerataanko"] == "Q"))) {

			$varastopaikka = $row["hyllyalue"] . " " . $row["hyllynro"] . " " . $row["hyllyvali"] . " " . $row["hyllytaso"];

			$row["perhe_kommentti"] = "";

			// Info tuoteperheestä
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
								ORDER BY tilausrivi.tunnus";
					$perheresult = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($perheresult) > 1) {
						while ($perherow = mysql_fetch_array($perheresult)) {
							if ($row["perhe_kommentti"] == "") {
								$row["perhe_kommentti"] .= t("Tuoteperhe", $kieli).": ";
								$row["perhe_kommentti"] .= $perherow["tuoteno"]." (".$row["varattu"].") ".$perherow["nimitys"].".\n".t("Sisältää tuotteet", $kieli).": ";
							}
							else {
								$row["perhe_kommentti"] .= strtoupper($perherow["tuoteno"]).", ";
							}
						}
						$row["perhe_kommentti"] = substr($row["perhe_kommentti"],0,-2);
					}
				}						
			}
			
			if (trim($row["perhe_kommentti"]) != '') {
				$pohja = $pdf->draw_paragraph($kala+$norm["height"], 45, 60, 480, $row["perhe_kommentti"],	$thispage, $norm);						
				$kala = $pohja - $rivinkorkeus;
			}

			$pdf->draw_text(23,  $kala, $rivinumerot[$row["tunnus"]],	$thispage, $pieni);
			$pdf->draw_text(42,  $kala, $varastopaikka,					$thispage, $norm);
			$pdf->draw_text(125,  $kala, $row["tuoteno"],				$thispage, $norm);


			$query = "	SELECT tuote.*, group_concat(distinct tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '/') toim_tuoteno
						FROM tuote
						LEFT JOIN tuotteen_toimittajat ON tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno
						WHERE tuote.yhtio='$kukarow[yhtio]' and tuote.tuoteno = '$row[tuoteno]'
						GROUP BY tuote.tuoteno";
			$tresult = mysql_query($query) or pupe_error($query);
			$trow	 = mysql_fetch_array($tresult);
			
			
			$pdf->draw_text(330, $kala, pdf_substr($trow["toim_tuoteno"], 80, $pdf, $norm), $thispage, $norm);
			
			$rivipaikkahyllyssa  = 0;
			$rivivarastohyllyssa = 0;

			if ($trow["ei_saldoa"] == '') {
				//katotaan mihin varastooon tilausrivillä tuotepaikka kuuluu
				$rivipaikka = kuuluukovarastoon($row["hyllyalue"], $row["hyllynro"]);
				
				//saldot per varastopaikka			
				if ($trow["sarjanumeroseuranta"] == "E" or $trow["sarjanumeroseuranta"] == "F") {
					$query = "	SELECT tuote.yhtio, tuote.tuoteno, tuote.ei_saldoa, varastopaikat.tunnus varasto, varastopaikat.tyyppi varastotyyppi, varastopaikat.maa varastomaa, 
								tuotepaikat.oletus, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso,
								sarjanumeroseuranta.sarjanumero era,
								concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'),lpad(upper(tuotepaikat.hyllyvali), 5, '0'),lpad(upper(tuotepaikat.hyllytaso), 5, '0')) sorttauskentta,
								varastopaikat.nimitys, if(varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi
					 			FROM tuote
								JOIN tuotepaikat ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno
								JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
								and concat(rpad(upper(varastopaikat.alkuhyllyalue),  5, '0'),lpad(upper(varastopaikat.alkuhyllynro),  5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
								and concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'),lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
								and varastopaikat.tunnus = '$rivipaikka'
								JOIN sarjanumeroseuranta ON sarjanumeroseuranta.yhtio = tuote.yhtio 
								and sarjanumeroseuranta.tuoteno = tuote.tuoteno
								and sarjanumeroseuranta.hyllyalue = tuotepaikat.hyllyalue
								and sarjanumeroseuranta.hyllynro  = tuotepaikat.hyllynro
								and sarjanumeroseuranta.hyllyvali = tuotepaikat.hyllyvali
								and sarjanumeroseuranta.hyllytaso = tuotepaikat.hyllytaso
								and sarjanumeroseuranta.myyntirivitunnus = 0
								and sarjanumeroseuranta.era_kpl != 0
								WHERE tuote.yhtio = '$kukarow[yhtio]'
								and tuote.tuoteno = '$row[tuoteno]'
								GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15
								ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";
				}
				else {
					$query = "	SELECT tuote.yhtio, tuote.tuoteno, tuote.ei_saldoa, varastopaikat.tunnus varasto, varastopaikat.tyyppi varastotyyppi, varastopaikat.maa varastomaa, 
								tuotepaikat.oletus, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso,
								concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0')) sorttauskentta,
								varastopaikat.nimitys, if(varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi
					 			FROM tuote
								JOIN tuotepaikat ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno
								JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
								and concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
								and concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
								and varastopaikat.tunnus = '$rivipaikka'
								WHERE tuote.yhtio = '$kukarow[yhtio]'
								and tuote.tuoteno = '$row[tuoteno]'
								ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";
				}
				$sresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($sresult) > 0) {
					while ($saldorow = mysql_fetch_array ($sresult)) {
						if ($yhtiorow["saldo_kasittely"] == "T") {
							$saldoaikalisa = date("Y-m-d");
						}
						else {
							$saldoaikalisa = "";
						}

						list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($saldorow["tuoteno"], '', '', '', $saldorow["hyllyalue"], $saldorow["hyllynro"], $saldorow["hyllyvali"], $saldorow["hyllytaso"], '', $saldoaikalisa, $saldorow["era"]);

						if ($saldorow['hyllyalue'] == $row['hyllyalue'] and $saldorow['hyllynro'] == $row['hyllynro'] and $saldorow['hyllyvali'] == $row['hyllyvali'] and $saldorow['hyllytaso'] == $row['hyllytaso']){
							$rivipaikkahyllyssa  += $hyllyssa;
						}
						
						$rivivarastohyllyssa += $hyllyssa;
					}
				}

				if ($rivipaikkahyllyssa != $rivivarastohyllyssa) {
					$oikpos = $pdf->strlen($rivipaikkahyllyssa." (".$rivivarastohyllyssa.")", $norm);					
					$pdf->draw_text(465-$oikpos, $kala, $rivipaikkahyllyssa." (".$rivivarastohyllyssa.")",	$thispage, $norm);
				}
				else {
					$oikpos = $pdf->strlen($rivipaikkahyllyssa, $norm);					
					$pdf->draw_text(465-$oikpos, $kala, $rivipaikkahyllyssa,	$thispage, $norm);					
				}
			}

			$oikpos = $pdf->strlen(($row["tilkpl"]*1)." ".strtolower($row["yksikko"]), $boldi);
			$pdf->draw_text(520-$oikpos, $kala, ($row["tilkpl"]*1)." ".strtolower($row["yksikko"]), 	$thispage, $boldi);
			
			if ($row["keratty"] != '' and !in_array($row["var"], array('J','P'))) {
				$oikpos = $pdf->strlen($row['varattu']+$row['kpl'], $norm);
				$pdf->draw_text(575-$oikpos, $kala, $row['varattu']+$row['kpl'], 	$thispage, $norm);
			}
			else {
				if ($row["var"] == 'J') { // jälkitoimitus
					$oikpos = $pdf->strlen(t("**JT**", $kieli), $norm);
					$pdf->draw_text(575-$oikpos, $kala, t("**JT**", $kieli), 	$thispage, $norm);
				}
				elseif ($row["var"] == 'P') { // puute
					$oikpos = $pdf->strlen(t("PUUTE", $kieli), $norm);
					$pdf->draw_text(575-$oikpos, $kala, t("PUUTE", $kieli), 	$thispage, $norm);
				}
				elseif ($row["var"] == 'H') { // väkisin hyväksytty
					$oikpos = $pdf->strlen("...........", $norm);
					$pdf->draw_text(575-$oikpos, $kala, "...........", 	$thispage, $norm);
				}
			}

			$kala = $kala - $rivinkorkeus + 5;

			$pdf->draw_text(125, $kala, $row["nimitys"], 											$thispage, $norm);
			
			if($trow["tuotemassa"] > 0) {
				$ypaino = (float) round(($row['varattu']+$row['kpl'])*$trow["tuotemassa"], 2);
				
				$oikpos = $pdf->strlen((float) $trow["tuotemassa"]." / ".$ypaino." kg", $pieni);
				$pdf->draw_text(520-$oikpos, $kala, (float) $trow["tuotemassa"]." / ".$ypaino." kg",	$thispage, $pieni);
				
				$paino += $ypaino;
			}
			
			$kala = $kala - $rivinkorkeus;
			
			if ($row["sarjanumeroseuranta"] != "") {
				$query	= "	SELECT sarjanumero, parasta_ennen
		   					FROM sarjanumeroseuranta
		   					WHERE yhtio = '$kukarow[yhtio]'
							and tuoteno = '$row[tuoteno]'
		   					and myyntirivitunnus = '$row[tunnus]'";
		   		$sarjares = mysql_query($query) or pupe_error($query);
		   		
				while ($sarjarow = mysql_fetch_array($sarjares)) {
					if ($row["sarjanumeroseuranta"] == "S") {
						$row["kommentti"] .= " \n".t("S:nro").": ".$sarjarow["sarjanumero"];		
					}
					elseif ($row["sarjanumeroseuranta"] == "E") {
						$row["kommentti"] .= " \n".t("Erä").": ".$sarjarow["sarjanumero"];	
					}
					elseif ($row["sarjanumeroseuranta"] == "F") {
						$row["kommentti"] .= " \n".t("Erä").": ".$sarjarow["sarjanumero"]." ".t("Parasta ennen").": ".tv1dateconv($sarjarow["parasta_ennen"]);	
					}
				}
			}
				
			// Trimmataan uudestaan
			$row["kommentti"] = trim($row["kommentti"]);
			
			if ($row["kommentti"] != '') {
				$pohja = $pdf->draw_paragraph($kala+$norm["height"]+5, 125, 60, 580, $row["kommentti"],	$thispage, $norm);						
				$kala = $pohja - $rivinkorkeus;
			}

			$perheid  = $row["perheid"];
			$perheid2 = $row["perheid2"];
		}
		return($thispage);
	}
}

if (!function_exists('loppu')) {
	function loppu ($thispage, $tots) {
		global $pdf, $sivu, $laskurow, $viite, $pieni, $norm, $rectparam, $kieli, $yhtiorow, $kala, $rivinkorkeus, $page, $paino;
		
		//Alimmat kolme laatikkoa, yhtiötietoja
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
		$pdf->draw_text(217, 35, t("Sähköposti", $kieli).":",		$thispage, $pieni);
		$pdf->draw_text(257, 35, $yhtiorow["email"],				$thispage, $pieni);

		//jos on suomalainen yritys tehdään ytunnus nätiks
		if (strtoupper($yhtiorow["maa"])== 'FI') {
			//muutetaan ytunnus takas oikean näköseks
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

		if ($tots != 1) {
			$pdf->draw_text(530, 72, t("Jatkuu", $kieli)."...", 	$thispage, $norm);
		}
		
		if ($tots == 1) {
			if ($paino != 0) {
				$pdf->draw_text(440, 25, t("Tilauksen paino", $kieli).":",	$thispage, $norm);
				$oikpos = $pdf->strlen($paino." kg", $boldi);
				$pdf->draw_text(575-$oikpos, 25, $paino." kg",				$thispage, $boldi);
			}
			
			// viivat rivien väliin...
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

if (!function_exists('print_pdf')) {
	function print_pdf ($komento) {
		global $pdf, $kukarow, $yhtiorow, $kateinen, $returnvalue, $kieli, $tee;

		$returnvalue=0;

		//keksitään uudelle failille joku varmasti uniikki nimi:
		list($usec, $sec) = explode(' ', microtime());
		mt_srand((float) $sec + ((float) $usec * 100000));
		$pdffilenimi = "/tmp/Kerayslista-".md5(uniqid(mt_rand(), true)).".pdf";

		//kirjoitetaan pdf faili levylle..
		$fh = fopen($pdffilenimi, "w");
		if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF create error $pdffilenimi");
		fclose($fh);

		// itse print komento...
		if ($komento == 'email') {
			$liite = $pdffilenimi;
			$kutsu = "Lahete";
			echo t("Keräyslista tulostuu")."...<br>";

			if ($kukarow["extranet"] == '') {
				require("../inc/sahkoposti.inc");
			}
			else {
				require("sahkoposti.inc");
			}
		}
		elseif ($tee == 'NAYTATILAUS') {
			//Työnnetään tuo pdf vaan putkeen!
			echo file_get_contents($pdffilenimi);
		}
		elseif ($komento != '' and $komento != 'edi') {
			echo t("Keräyslista tulostuu")."...<br>";
			$line = exec("$komento $pdffilenimi", $output, $returnvalue);
		}

		//poistetaan tmp file samantien kuleksimasta...
		system("rm -f $pdffilenimi");
	}
}

?>
