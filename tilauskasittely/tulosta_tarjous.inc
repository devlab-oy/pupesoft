<?php

if (@include_once("pdflib/phppdflib.class.php")) {
	include_once("viivakoodi/Barcode.php");
}
else {
	include_once("phppdflib.class.php");
	include_once("Barcode.php");
}

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
if (!function_exists('alku_tarjous')) {
	function alku_tarjous ($laskurow, $asiakasrow, $sivu, $kieli, $pdf_tarjous) {
		global $tarjoustyyppi, $yhtiorow, $kukarow, $pieni, $norm, $boldi, $pieni_boldi, $iso, $rectparam;
	
		$page_tarjous = $pdf_tarjous->new_page("a4");
			
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
			$image = $pdf_tarjous->jfif_embed($data);
		
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
								
				$placement = $pdf_tarjous->image_place($image, 830-($logoparam['scale']*$isizelogo[1]), 20, $page_tarjous, $logoparam);
			}
		}
		else {
			$pdf_tarjous->draw_text(30, 815, $yhtiorow["nimi"], 	$page_tarjous, $norm);
			$pdf_tarjous->draw_text(30, 805, $yhtiorow["osoite"], 	$page_tarjous, $norm);
			$pdf_tarjous->draw_text(30, 795, $yhtiorow["postino"]." ".$yhtiorow["postitp"], 	$page_tarjous, $norm);
			$pdf_tarjous->draw_text(30, 785, $yhtiorow["maa"], 		$page_tarjous, $norm);
		}
		
		$pdf_tarjous->draw_text(310,815, t("Tarjous", $kieli), 		$page_tarjous, $iso);
		$pdf_tarjous->draw_text(310, 803, t("Sivu", $kieli), 		$page_tarjous, $norm);
		
		//Vasen sarake
		//$pdf_tarjous->draw_rectangle(737, 20,  674, 300, $page_tarjous, $rectparam);
		$pdf_tarjous->draw_text(50, 729, t("Laskutusosoite", $kieli), 	$page_tarjous, $pieni);
		$pdf_tarjous->draw_text(50, 717, $laskurow["nimi"], 			$page_tarjous, $norm);
		$pdf_tarjous->draw_text(50, 707, $laskurow["nimitark"],			$page_tarjous, $norm);
		$pdf_tarjous->draw_text(50, 697, $laskurow["osoite"], 			$page_tarjous, $norm);
		$pdf_tarjous->draw_text(50, 687, $laskurow["postino"]." ".$laskurow["postitp"], $page_tarjous, $norm);
		$pdf_tarjous->draw_text(50, 677, $laskurow["maa"], 				$page_tarjous, $norm);


		//$pdf_tarjous->draw_rectangle(674, 20,  611, 300, $page_tarjous, $rectparam);
		$pdf_tarjous->draw_text(50, 656, t("Toimitusosoite", $kieli), 	$page_tarjous, $pieni);
		$pdf_tarjous->draw_text(50, 644, $laskurow["toim_nimi"], 		$page_tarjous, $norm);
		$pdf_tarjous->draw_text(50, 634, $laskurow["toim_nimitark"], 	$page_tarjous, $norm);
		$pdf_tarjous->draw_text(50, 624, $laskurow["toim_osoite"],	 	$page_tarjous, $norm);
		$pdf_tarjous->draw_text(50, 614, $laskurow["toim_postino"]." ".$laskurow["toim_postitp"], $page_tarjous, $norm);
		$pdf_tarjous->draw_text(50, 604, $laskurow["toim_maa"], 		$page_tarjous, $norm);


		//Oikea sarake
		$pdf_tarjous->draw_rectangle(800, 300, 779, 580, $page_tarjous, $rectparam);
		$pdf_tarjous->draw_rectangle(800, 420, 779, 580, $page_tarjous, $rectparam);
		
		if($laskurow["tunnusnippu"] > 0) {
			$pdf_tarjous->draw_text(310, 792, t("Tilausnumero/Tarjous", $kieli), 					$page_tarjous, $pieni);
			$pdf_tarjous->draw_text(310, 782, $laskurow["tunnusnippu"]."/".$laskurow["tunnus"],		$page_tarjous, $boldi);
		}
		else {
			$pdf_tarjous->draw_text(310, 792, t("Tarjous", $kieli), 	$page_tarjous, $pieni);
			$pdf_tarjous->draw_text(310, 782, $laskurow["tunnus"],			$page_tarjous, $boldi);			
		}
		
		$pdf_tarjous->draw_text(430, 792, t("Toimitusaika", $kieli), 	$page_tarjous, $pieni);
		
		$useita = "EI";
		
		if($laskurow["nippu"] > 0) {
			$query = "	SELECT count(distinct(toimaika))
						FROM tilausrivi
						WHERE yhtio='$kukarow[yhtio]'
						and otunnus IN ({$laskurow["nippu"]})";
			$toimaikares = mysql_query($query) or pupe_error($query);
			$toimaikarow = mysql_fetch_array($toimaikares);
			
			if($toimaikarow[0] > 1) {
				$useita = "JOO";
			}
		}
		
		if($useita == "JOO") {
			$pdf_tarjous->draw_text(430, 782, t("Useita", $kieli), 	$page_tarjous, $norm);
		}
		elseif ($laskurow["toimvko"] != '') {
			$DAY_ARRAY = array(1=>"Ma","Ti","Ke","To","Pe","La","Su");
			
			$taika = t("Vko")." ".date("W", strtotime($laskurow["toimaika"]));
			
			if ($laskurow['toimvko'] != '7') {
				$taika .= "/".$DAY_ARRAY[$laskurow["toimvko"]];
			}
			
			$pdf_tarjous->draw_text(430, 782, $taika, 								$page_tarjous, $norm);
		}
		else {			
			$pdf_tarjous->draw_text(430, 782, tv1dateconv($laskurow["toimaika"]), 	$page_tarjous, $norm);
		}

		if ($asiakasrow["asiakasnro"] == "" and $laskurow["ytunnus"] != "") $asiakasrow["asiakasnro"] = $laskurow["ytunnus"];
		
		$pdf_tarjous->draw_rectangle(779, 300, 758, 580, $page_tarjous, $rectparam);
		$pdf_tarjous->draw_rectangle(779, 420, 758, 580, $page_tarjous, $rectparam);
		$pdf_tarjous->draw_text(310, 771, t("Asiakasnumero", $kieli), 					$page_tarjous, $pieni);
		$pdf_tarjous->draw_text(310, 761, $asiakasrow["asiakasnro"], 						$page_tarjous, $norm);
		$pdf_tarjous->draw_text(430, 771, t("Päiväys", $kieli), 						$page_tarjous, $pieni);
		$pdf_tarjous->draw_text(430, 761, tv1dateconv($laskurow["muutospvm"]), 		$page_tarjous, $norm);

		$pdf_tarjous->draw_rectangle(758, 300, 737, 580, $page_tarjous, $rectparam);
		$pdf_tarjous->draw_rectangle(758, 420, 737, 580, $page_tarjous, $rectparam);
		$pdf_tarjous->draw_text(310, 750, t("Myyjä", $kieli), 						$page_tarjous, $pieni);
		
		//etsitään myyjän nimi
		$query  = "	SELECT nimi
					from kuka
					where tunnus='$laskurow[myyja]' and yhtio='$kukarow[yhtio]'";
		$myyresult = mysql_query($query) or pupe_error($query);
		$myyrow = mysql_fetch_array($myyresult);

		$pdf_tarjous->draw_text(310, 740, $myyrow["nimi"], 										$page_tarjous, $norm);
		
		
		$pdf_tarjous->draw_text(430, 750, t("Tilauspvm", $kieli),							 	$page_tarjous, $pieni);
		$pdf_tarjous->draw_text(430, 740, tv1dateconv($laskurow["luontiaika"], "pitka"), 		$page_tarjous, $norm);
	

		$pdf_tarjous->draw_rectangle(737, 300, 716, 420, $page_tarjous, $rectparam);
		$pdf_tarjous->draw_text(310, 729, t("Toimitustapa", $kieli), 		$page_tarjous, $pieni);
		$pdf_tarjous->draw_text(310, 719, $laskurow["toimitustapa"],	 	$page_tarjous, $norm);
		
		//etsitään rahtisopimusnumero
		$query = "SELECT rahtisopimus from rahtisopimukset where ytunnus = '$laskurow[ytunnus]' and toimitustapa = '$laskurow[toimitustapa]'";
		$rahtiresult = mysql_query($query) or pupe_error($query);
		$rahtirow = mysql_fetch_array($rahtiresult);
		
		$pdf_tarjous->draw_rectangle(737, 420, 716, 580, $page_tarjous, $rectparam);
		$pdf_tarjous->draw_text(430, 729, t("Rahtisopimus", $kieli), 		$page_tarjous, $pieni);
		$pdf_tarjous->draw_text(430, 719, $rahtirow["rahtisopimus"],	 	$page_tarjous, $norm);
		

		$pdf_tarjous->draw_rectangle(716, 300, 695, 580, $page_tarjous, $rectparam);
		$pdf_tarjous->draw_text(310, 708, t("Toimitusehto", $kieli), 		$page_tarjous, $pieni);
		$pdf_tarjous->draw_text(310, 698, $laskurow["toimitusehto"], 		$page_tarjous, $norm);
		
		$pdf_tarjous->draw_rectangle(695, 300, 674, 580, $page_tarjous, $rectparam);
				
		$pdf_tarjous->draw_text(310, 687, t("Maksuehto", $kieli), 			$page_tarjous, $pieni);
		$pdf_tarjous->draw_text(310, 677, $laskurow["maksuehto"], 			$page_tarjous, $norm);
		
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
		
		// Tulostetaan laskun kommentti
		if (trim($komm) != '') {
			$kommentit = explode("\n", trim($komm));

			$pohja  = 577;
			$maxoik = 0;
			
			foreach($kommentit as $kommentti) {
				if(strpos($kommentti, "###") !== FALSE) {
					list($o, $v) = explode("###", $kommentti);
				
					$oikpos = $pdf_tarjous->strlen($o, $boldi);
				
					if ($oikpos > $maxoik) {
						$maxoik = $oikpos;
					}
				}
			}

			foreach($kommentit as $kommentti) {
				
				if (strpos($kommentti, "###") !== false) {
					list($o, $v) = explode("###", $kommentti);
					
					$pdf_tarjous->draw_text(35, $pohja, trim($o), $page_tarjous, $boldi);
					$pdf_tarjous->draw_text(35+$maxoik+5, $pohja, trim($v), $page_tarjous, $norm);
				}
				else {
					$pdf_tarjous->draw_text(35, $pohja, trim($kommentti), $page_tarjous, $norm);
				}
				
				$pohja = $pohja - 10;
			}
			$kala = $pohja-30;
		}
		else {
			$kala = 560;
		}
		
		//viivakoodi
		$nimi = "/tmp/".md5(uniqid(rand(),true)).".jpg";
			
		imagejpeg(Image_Barcode::draw($laskurow["tunnus"], 'Code39', 'jpg', false, 1, 20), $nimi, 90);

		$fh = fopen($nimi, "r");
		$data = fread($fh, filesize($nimi));
		fclose($fh);

		$image = $pdf_tarjous->jfif_embed($data);
		$pdf_tarjous->image_place($image, 805, 440, $page_tarjous);
				
		system("rm -f $nimi");
			
		tarjous_rivi_otsikot($pdf_tarjous, $page_tarjous, $kieli, $kala);
		
		return array($page_tarjous, $kala);
	}
}

if (!function_exists('uusi_sivu_tarjous')) {
	function uusi_sivu_tarjous ($laskurow, $asiakasrow, $sivu, $kieli, $pdf_tarjous) {
		global $tarjoustyyppi, $yhtiorow, $kukarow, $pieni, $norm, $boldi, $pieni_boldi, $iso, $rectparam;
		
		$page_tarjous = $pdf_tarjous->new_page("a4");
		
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
			$image = $pdf_tarjous->jfif_embed($data);
			
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
								
				$placement = $pdf_tarjous->image_place($image, 830-($logoparam['scale']*$isizelogo[1]), 20, $page_tarjous, $logoparam);
			}
		}
		else {
			$pdf_tarjous->draw_text(30, 815, $yhtiorow["nimi"], 	$page_tarjous, $norm);
			$pdf_tarjous->draw_text(30, 805, $yhtiorow["osoite"], 	$page_tarjous, $norm);
			$pdf_tarjous->draw_text(30, 795, $yhtiorow["postino"], 	$page_tarjous, $norm);
			$pdf_tarjous->draw_text(60, 795, $yhtiorow["postitp"], 	$page_tarjous, $norm);
			$pdf_tarjous->draw_text(30, 785, $yhtiorow["maa"], 		$page_tarjous, $norm);
		}
			
		$pdf_tarjous->draw_text(310,815, t("Tarjous", $kieli), 		$page_tarjous, $iso);
		$pdf_tarjous->draw_text(310, 803, t("Sivu", $kieli), 		$page_tarjous, $norm);
	
		//Oikea sarake
		$pdf_tarjous->draw_rectangle(800, 300, 779, 580, $page_tarjous, $rectparam);
		$pdf_tarjous->draw_rectangle(800, 420, 779, 580, $page_tarjous, $rectparam);
		
		$pdf_tarjous->draw_text(310, 792, t("Tarjous", $kieli), 		$page_tarjous, $pieni);
		$pdf_tarjous->draw_text(430, 792, t("Toimitusaika", $kieli), 	$page_tarjous, $pieni);
		$pdf_tarjous->draw_text(310, 782, $laskurow["tunnus"],			$page_tarjous, $boldi);
		
		
		if ($laskurow["toimvko"] != '') {
			$DAY_ARRAY = array(1=>"Ma","Ti","Ke","To","Pe","La","Su");
			
			$taika = t("Vko")." ".date("W", strtotime($laskurow["toimaika"]));
			
			if ($laskurow['toimvko'] != '7') {
				$taika .= "/".$DAY_ARRAY[$laskurow["toimvko"]];
			}
			
			$pdf_tarjous->draw_text(430, 782, $taika, 								$page_tarjous, $norm);
		}
		else {			
			$pdf_tarjous->draw_text(430, 782, tv1dateconv($laskurow["toimaika"]), 	$page_tarjous, $norm);
		}

		if ($asiakasrow["asiakasnro"] == "" and $laskurow["ytunnus"] != "") $asiakasrow["asiakasnro"] = $laskurow["ytunnus"];

		$pdf_tarjous->draw_rectangle(779, 300, 758, 580, $page_tarjous, $rectparam);
		$pdf_tarjous->draw_rectangle(779, 420, 758, 580, $page_tarjous, $rectparam);
		$pdf_tarjous->draw_text(310, 771, t("Asiakasnumero", $kieli), 					$page_tarjous, $pieni);
		$pdf_tarjous->draw_text(310, 761, $asiakasrow["asiakasnro"], 					$page_tarjous, $norm);
		$pdf_tarjous->draw_text(430, 771, t("Päiväys", $kieli), 						$page_tarjous, $pieni);
		$pdf_tarjous->draw_text(430, 761, tv1dateconv($kerarow["kerattyaika"]), 		$page_tarjous, $norm);
		
		//viivakoodi
		$nimi = "/tmp/".md5(uniqid(rand(),true)).".jpg";
			
		imagejpeg(Image_Barcode::draw($laskurow["tunnus"], 'Code39', 'jpg', false, 1, 20), $nimi, 90);

		$fh = fopen($nimi, "r");
		$data = fread($fh, filesize($nimi));
		fclose($fh);

		$image = $pdf_tarjous->jfif_embed($data);
		$pdf_tarjous->image_place($image, 805, 440, $page_tarjous);
				
		system("rm -f $nimi");

		$kala = 715;
			
		tarjous_rivi_otsikot($pdf_tarjous, $page_tarjous, $kieli, $kala);
				
		return array($page_tarjous, $kala);
	}
}

if (!function_exists('tarjous_rivi_otsikot')) {
	function tarjous_rivi_otsikot($pdf_tarjous, $page_tarjous, $kieli, $kala) {
		global $tarjoustyyppi, $yhtiorow, $kukarow, $norm, $rectparam;
		
		if (str_replace("perhe_", "", $tarjoustyyppi) == "") {
			$pdf_tarjous->draw_text(25,  $kala+15, "#",							$page_tarjous, $norm);
			$pdf_tarjous->draw_text(45,  $kala+15, t("Tuotenumero", $kieli),	$page_tarjous, $norm);
			$pdf_tarjous->draw_text(190, $kala+15, t("Tuotenimi", $kieli),		$page_tarjous, $norm);
			$pdf_tarjous->draw_text(390, $kala+15, t("Tilattu", $kieli),		$page_tarjous, $norm);
			$pdf_tarjous->draw_text(425, $kala+15, t("Yks.hinta", $kieli),		$page_tarjous, $norm);
			$pdf_tarjous->draw_text(478, $kala+15, t("Alennus", $kieli),		$page_tarjous, $norm);
			$pdf_tarjous->draw_text(530, $kala+15, t("Rivihinta", $kieli),		$page_tarjous, $norm);
		}
	}
}

if (!function_exists('rivi_tarjous')) {
	function rivi_tarjous($pdf_tarjous, $laskurow, $kala, $kieli, $page_tarjous, $sarjanutunnus, $selite, $row, $hinnat, $lask, $sivu) {
		global $yhtiorow, $kukarow, $norm, $pieni;
		
		$rivinkorkeus = 15;
		
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
							and tuote.tuotetyyppi != 'R'
							and tilausrivi.tyyppi != 'V' 
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
		
		// Trimmataan uudestaan
		$row["kommentti"] = trim($row["kommentti"]);
		
		//hatetaan tuotteen myyntihinta
		$query = "	SELECT round(if(isnull(hinnasto.hinta) or hinnasto.hinta='', round(tuote.myyntihinta/if('$laskurow[vienti_kurssi]'=0,1,'$laskurow[vienti_kurssi]'),'$yhtiorow[hintapyoristys]'), hinnasto.hinta) / (1+(if('$yhtiorow[alv_kasittely]'='',0,tuote.alv)/100)), '$yhtiorow[hintapyoristys]') myyntihinta,
       				tuote.aleryhma
                    FROM tuote
                    LEFT JOIN hinnasto on tuote.yhtio = hinnasto.yhtio and tuote.tuoteno = hinnasto.tuoteno and hinnasto.valkoodi = '$laskurow[valkoodi]'
                    WHERE tuote.tuoteno='$row[tuoteno]' and tuote.yhtio='$kukarow[yhtio]'";
        $asresult = mysql_query($query) or pupe_error($query);
		$asrow1 = mysql_fetch_array($asresult);
	
		//saadaan lähetteen summa menemään oikein, taaksepäin yhteensopivasti
		$row["brutto_rivihinta"] 	= $asrow1["myyntihinta"] * ($row["kpl"]+$row["varattu"]+$row["jt"]);
		$row["myyntihinta"] 		= $asrow1["myyntihinta"];
		$row["aleryhma"] 			= $asrow1["aleryhma"];
		
		if ($laskurow['valkoodi'] != $yhtiorow['valkoodi']) {
			$row["hinta"] 	  = round(laskuval($row["hinta"], $laskurow["vienti_kurssi"]),$yhtiorow['hintapyoristys']);
			$row["rivihinta"] = round(laskuval($row["rivihinta"], $laskurow["vienti_kurssi"]),$yhtiorow['hintapyoristys']);
			$row["ovhhinta"]  = round(laskuval($row["ovhhinta"], $laskurow["vienti_kurssi"]),$yhtiorow['hintapyoristys']);
		}
		
		$perheid 	 = $row["perheid"];
		$jtid		 = $row["jtsort"];
		$piilotarivi = "";
		
		// Alennus kuntoon
		if($row["netto"] != 'N' and $laskurow["erikoisale"] > 0) { 
			$row["ale"] = round((1-(1-($row["ale"]+$laskurow["erikoisale"]-($row["ale"]*$laskurow["erikoisale"]/100))/100))*100,2);
		}
		
		if ($row["perheid"] > 0 and substr($tarjoustyyppi, 0, 6) == "perhe_") {

			// kyseessä on isä
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

					// lasketaan isätuotteen riville lapsien hinnat yhteen
					$query = "	SELECT 
								round(sum(tilausrivi.hinta * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),'$yhtiorow[hintapyoristys]') rivihinta,
								round(round(sum(tilausrivi.hinta * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl)),'$yhtiorow[hintapyoristys]') / ($row[kpl]+$row[varattu]+$row[jt]),'$yhtiorow[hintapyoristys]') hinta
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
				// lapsia ei lisätä
				$piilotarivi = 1;
			}
		}
		
		if($useita == "JOO") {
			$row["kommentti"] .= "\n".t("Toimitusaika", $kieli).": ".tv1dateconv($row["toimaika"]);
		}
		
		$row["tilkpl"] 	= ($row["tilkpl"]*1);
		$row["varattu"] = ($row["varattu"]*1);
		$row["kpl"] 	= ($row["kpl"]*1);
		$kpl_rivilla 	= ($row["kpl"]+$row["varattu"]+$row["jt"]);
		
		if ($row["keratty"] != '' and !in_array($row["var"], array('J','P'))) {
			$row['varattu'] = ($row['varattu']+$row['kpl'])." ".strtolower($row["yksikko"]);
			$row['varattu_viivakoodi'] = $kpl_rivilla;
		}
		else {	
			if ($row["var"] == 'J') { // jälkitoimitus
				$row['varattu'] = t("**JT**", $kieli);
				$row['varattu_viivakoodi'] = 0;
			}
			elseif ($row["var"] == 'P') { // puute
				$row['varattu'] = t("PUUTE", $kieli);
				$row['varattu_viivakoodi'] = 0;
			}
			else {
				$row["varattu"] = "";
				$row["kpl"] 	= "";
				$row['varattu_viivakoodi'] = 0;
			}
		}
		
		//Haetan sarjanumeron tiedot
		if ($row["varattu"]+$row["kpl"] < 0){
			$sarjanutunnus = "ostorivitunnus";
		}
		else {
			$sarjanutunnus = "myyntirivitunnus";
		}
		
		$query = "	SELECT distinct sarjanumero
					from sarjanumeroseuranta
					where yhtio = '$kukarow[yhtio]'
					and tuoteno = '$row[tuoteno]'
					and $sarjanutunnus='$row[tunnus]'
					and sarjanumero != ''";
		$sarjares = mysql_query($query) or pupe_error($query);

		if ($row["kommentti"] != '' and mysql_num_rows($sarjares) > 0){
			$row["kommentti"] .= "\n";
		}
		while($sarjarow = mysql_fetch_array($sarjares)) {
			$row["kommentti"] .= t("S:nro").": $sarjarow[sarjanumero]\n";
		}
		
		if(substr($tarjoustyyppi, 0, 6) != "perhe_" or $piilotarivi == "") {
			if (str_replace("perhe_", "", $tarjoustyyppi) == "") {
				// tulosta_tarjous.inc
				if ($row["var"] != 'J' and $row["var"] != 'P') {
					$summa += $row['rivihinta'];
				}
				else {
					$row['rivihinta'] = 0;
				}
				
				$kala = tarjous_rivi_hinta_ale($pdf_tarjous, $laskurow, $kala, $kieli, $page_tarjous, $sarjanutunnus, $selite, $row, $hinnat, $lask, $sivu);
			}
		}
		
		return array ($page_tarjous, $kala, $lask);	
	}
}

if (!function_exists('tarjous_rivi_hinta_ale')) {
	function tarjous_rivi_hinta_ale($pdf_tarjous, $laskurow, $kala, $kieli, $page_tarjous, $sarjanutunnus, $selite, $row, $hinnat, $lask, $sivu) {
		global $yhtiorow, $kukarow, $norm, $pieni;
		
		$rivinkorkeus = 15;
	
		if (trim($row["perhe_kommentti"]) != '') {
			$pohja = $pdf_tarjous->draw_paragraph($kala+$norm["height"], 45, 60, 300, $row["perhe_kommentti"],	$page_tarjous, $norm);						
			$kala = $pohja - $rivinkorkeus;
		}
		
		$pdf_tarjous->draw_text(23,  $kala, $rivinumerot[$row["tunnus"]],							$page_tarjous, $pieni);
		$pdf_tarjous->draw_text(45,  $kala, $row["tuoteno"],										$page_tarjous, $norm);
				
		$oikpos = $pdf_tarjous->strlen($row["varattu"], $norm);
		$pdf_tarjous->draw_text(415-$oikpos, $kala, $row["varattu"], 								$page_tarjous, $norm);
				
		$oikpos = $pdf_tarjous->strlen(sprintf("%.".$yhtiorow['hintapyoristys']."f", $row["hinta"]), $norm);
		$pdf_tarjous->draw_text(465-$oikpos, $kala, sprintf("%.".$yhtiorow['hintapyoristys']."f", $row["hinta"]), 				$page_tarjous, $norm);
				
		$oikpos = $pdf_tarjous->strlen(($row["ale"]*1)."%", $norm);
		$pdf_tarjous->draw_text(515-$oikpos, $kala, ($row["ale"]*1)."%", 							$page_tarjous, $norm);
		
		$oikpos = $pdf_tarjous->strlen(sprintf("%.".$yhtiorow['hintapyoristys']."f", $row["rivihinta"]), $norm);
		$pdf_tarjous->draw_text(575-$oikpos, $kala, sprintf("%.".$yhtiorow['hintapyoristys']."f", $row["rivihinta"]), 			$page_tarjous, $norm);
				
		if ($pdf_tarjous->strlen($row["nimitys"], $norm) >= 135) {
			$kala = $kala - $rivinkorkeus;
		}
		
		$pdf_tarjous->draw_text(190, $kala, $row["nimitys"], 										$page_tarjous, $norm);
		
		$kala = $kala - $rivinkorkeus;
		
		if ($row["kommentti"] != '') {
			$pohja = $pdf_tarjous->draw_paragraph($kala+$norm["height"]+5, 45, 60, 300, $row["kommentti"],	$page_tarjous, $norm);						
			$kala = $pohja - $rivinkorkeus;
		}
		
		$kala = $kala - 10;
		
		return($kala);
	}
}

if (!function_exists('loppu_tarjous')) {	
	function loppu_tarjous ($pdf_tarjous, $laskurow, $kieli, $page_tarjous, $tots, $total_uudet=0, $total_vanhat=0) {
		global $kukarow, $yhtiorow, $boldi, $pieni, $norm, $rectparam;
		
		if ($tots == 1 and $total_uudet != 0) {
			$pdf_tarjous->draw_text(mm_pt(15),  140,  t("Tarjoamme teille ylläolevan tarjouksen kohteet hintaan:", $kieli),   $page_tarjous, $boldi);
									
			$oikpos = $pdf_tarjous->strlen(sprintf("%.2f",$total_uudet)." ".$laskurow["valkoodi"], $boldi);					
			$pdf_tarjous->draw_text(575-$oikpos, 140, sprintf("%.2f",$total_uudet)." ".$laskurow["valkoodi"], $page_tarjous, $boldi);
		}
		
		if ($yhtiorow['alv_kasittely'] != '') {
        	$pdf_tarjous->draw_text(mm_pt(15),  120, "Hinnat ovat nettohintoja joihin lisätään arvonlisävero.", $page_tarjous, $norm);
		}
		else {
        	$pdf_tarjous->draw_text(mm_pt(15),  120, "Hinnat sisältävät arvonlisäveron.", $page_tarjous, $norm);
		}
		
		$pdf_tarjous->draw_text(mm_pt(15),  110,"Lisäksi veloitamme mahdollisesta toimituksesta aiheutuneet kulut.", $page_tarjous, $norm);
		$pdf_tarjous->draw_text(mm_pt(15),  100, "Maksuehtomme on ".trim($laskurow["maksuehto"]).".", $page_tarjous, $norm);
		$pdf_tarjous->draw_text(mm_pt(15),  90,  t("Tarjous on voimassa", $kieli)." ".$voimassa." ". t("saakka", $kieli).".", $page_tarjous, $norm);
		
		$pdf_tarjous->draw_text(mm_pt(15),  75, "Toivomme tarjouksemme kiinnostavan teitä ja johtavan tilaukseen.", $page_tarjous, $boldi);
		
		$pdf_tarjous->draw_text(mm_pt(15),  60, "Kunnioittavasti,", $page_tarjous, $boldi);
		
		$query  = "	SELECT *
		            from kuka
		            where tunnus = '$laskurow[myyja]' and yhtio='$kukarow[yhtio]'";
		$myyresult = mysql_query($query) or pupe_error($query);
		
		if (mysql_num_rows($myyresult) == 1) {
			$myyrow = mysql_fetch_array($myyresult);       
		}
		else {
			$myyrow = $kukarow;
		}
		
		$pdf_tarjous->draw_text(mm_pt(17),  50, $myyrow["nimi"], $page_tarjous, $boldi);
		$pdf_tarjous->draw_text(mm_pt(17),  40, $yhtiorow["nimi"], $page_tarjous, $boldi);
		
		if ($tots != 1) {
			$pdf_tarjous->draw_text(530, 140, t("Jatkuu", $kieli)."...", $page_tarjous, $boldi);
		}
	}
}

if (!function_exists('print_pdf_tarjous')) {
	function print_pdf_tarjous ($pdf_tarjous, $komento, $tee) {
		global $yhtiorow, $kukarow;

		$oslapp='';
		$returnvalue=0;

		//keksitään uudelle failille joku varmasti uniikki nimi:
		list($usec, $sec) = explode(' ', microtime());
		mt_srand((float) $sec + ((float) $usec * 100000));
		$pdf_tarjousfilenimi = "/tmp/Tarjous-".md5(uniqid(mt_rand(), true)).".pdf";

		//kirjoitetaan pdf faili levylle..
		$fh = fopen($pdf_tarjousfilenimi, "w");
		if (fwrite($fh, $pdf_tarjous->generate()) === FALSE) die("PDF create error $pdf_tarjousfilenimi");
		fclose($fh);

		// itse print komento...
		if ($komento == 'RUUDULLE') {
			return $pdf_tarjousfilenimi;
		}
		elseif ($komento == 'email' or substr($komento,0,12) == 'asiakasemail') {
			$liite = $pdf_tarjousfilenimi;
			$kutsu = "Tarjous";

			echo t("Tarjous tulostuu")."...<br>";

			if ($kukarow["extranet"] == '') {
				require("../inc/sahkoposti.inc");
			}
			else {
				require("sahkoposti.inc");
			}
		}
		elseif ($tee == 'NAYTATILAUS') {
			//Työnnetään tuo pdf vaan putkeen!
			echo file_get_contents($pdf_tarjousfilenimi);
		}
		elseif ($komento != '' and $komento != 'edi') {
			echo t("Tarjous tulostuu")."...<br>";
			$line = exec("$komento $pdf_tarjousfilenimi", $output, $returnvalue);
		}

		//poistetaan tmp file samantien kuleksimasta...
		system("rm -f $pdf_tarjousfilenimi");
	}
}

if (!function_exists('tulosta_tarjous')) {
	function tulosta_tarjous ($otunnus, $komento, $kieli = "", $tee = "", $hinnat = "") {
		global $yhtiorow, $kukarow;

		// Haetaan laskun tiedot
		$query = "	SELECT *, lasku.laatija laatija, lasku.luontiaika luontiaika, lasku.tunnus tunnus
					FROM lasku
					LEFT JOIN laskun_lisatiedot ON lasku.yhtio=laskun_lisatiedot.yhtio and lasku.tunnus=laskun_lisatiedot.otunnus
					WHERE lasku.yhtio = '$kukarow[yhtio]'
					and lasku.tunnus  = '$otunnus'";
		$result = mysql_query($query) or pupe_error($query);
		$laskurow = mysql_fetch_array($result);

		if ($kieli== '') {
			$querykiel = "SELECT kieli FROM asiakas WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$laskurow[liitostunnus]'";
			$kielresult = mysql_query($querykiel) or pupe_error($querykiel);
			$kielnum = mysql_num_rows($kielresult);
			$kielrow = mysql_fetch_array($kielresult);
			$kieli = strtolower($kielrow['kieli']);
		}

		// haetaan maksuehdon tiedot
		$query  = "SELECT * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
		$result = mysql_query($query) or pupe_error($query);
		$masrow = mysql_fetch_array($result);

		//maksuehto tekstinä
		$laskurow["maksuehto"] = $masrow["teksti"]." ".$masrow["kassa_teksti"];

		$query  = "	SELECT *
					FROM asiakas
					WHERE yhtio='$kukarow[yhtio]' and tunnus='$laskurow[liitostunnus]'";
		$result = mysql_query($query) or pupe_error($query);
		$asiakasrow = mysql_fetch_array($result);

		//korjataan yhtiön tietoja
		$query  = "	SELECT *
					from kuka 
					where tunnus = '$laskurow[myyja]' 
					and yhtio = '$kukarow[yhtio]'";
		$myyresult = mysql_query($query) or pupe_error($query);
		
		if (mysql_num_rows($myyresult) == 1) {
			$myyrow = mysql_fetch_array($myyresult);
			
			$kukarow["toimipaikka"] = $myyrow["toimipaikka"];
		}
		
		$query = "	SELECT *
					FROM yhtion_toimipaikat
					WHERE yhtio='$kukarow[yhtio]' and tunnus='$kukarow[toimipaikka]'";
		$result = mysql_query($query) or die ("Kysely ei onnistu yhtio $query");

		if (mysql_num_rows($result) == 1) {
			$yhtion_toimipaikkarow = mysql_fetch_array($result);
			
			$yhtiorow["lasku_logo"] = $yhtion_toimipaikkarow["lasku_logo"];
			$yhtiorow["nimi"] 		= $yhtion_toimipaikkarow["nimi"];
			$yhtiorow["osoite"] 	= $yhtion_toimipaikkarow["osoite"];
			$yhtiorow["postino"] 	= $yhtion_toimipaikkarow["postino"];
			$yhtiorow["postitp"] 	= $yhtion_toimipaikkarow["postitp"];
			$yhtiorow["puhelin"] 	= $yhtion_toimipaikkarow["puhelin"];
			
		}
		
		
		function printtaa_rivit_tarjous($pdf_tarjous, $laskurow, $lask, $kala, $kieli, $sivu, $page_tarjous, $sarjanutunnus, $osasto, $selite, $total, $hinnat) {
			global $yhtiorow, $kukarow;

			//Tehään tästä taas array tän funktion siäiseen käyttöön jotta saamme sivunvaihdot toimimaan
			$page_tarjous2[$sivu] = $page_tarjous;

			if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"])) and $laskurow["vienti_kurssi"] != 0) {
				$hinta_riv = "round(tilausrivi.hinta/$laskurow[vienti_kurssi], 2)";
			}
			else {
				$hinta_riv = "tilausrivi.hinta";
			}
			
			$sorttauskentta = generoi_sorttauskentta($yhtiorow["tilauksen_jarjestys"]);

			$query = "	SELECT tilausrivi.*, 
						round($hinta_riv * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+$laskurow[erikoisale]-(tilausrivi.ale*$laskurow[erikoisale]/100))/100)),2) rivihinta,
						round($hinta_riv * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl),2) bruttorivihinta,							
						tilausrivin_lisatiedot.positio,
						$sorttauskentta
						FROM tilausrivi
						JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
						LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
						WHERE tilausrivi.otunnus = '$laskurow[tunnus]'
						and tilausrivi.yhtio = '$kukarow[yhtio]'
						and tilausrivi.tyyppi in  ('L','T','U')
						$ennakkolisa
						$osasto
						ORDER BY sorttauskentta $yhtiorow[tilauksen_jarjestys_suunta], tilausrivi.tunnus";
			$riresult = mysql_query($query) or pupe_error($query);
											
			while ($row = mysql_fetch_array($riresult)) {
				
				// Mahtuuko rivi tälle sivulle
				$kala_nyt = $kala;

				if(substr($tarjoustyyppi, 0, 6) != "perhe_" or $piilotarivi == "") {
					if (str_replace("perhe_", "", $tarjoustyyppi) == "") {
						$kala = tarjous_rivi_hinta_ale($pdf_tarjous, $laskurow, $kala, $kieli, $devnull, $sarjanutunnus, $selite, $row, $hinnat, $lask, $sivu);
					}
				}

				// Jos on paljon rivejä tehdään uusi otsikko...
				if ($kala <= 150) {						
					loppu_tarjous ($pdf_tarjous, $laskurow, $kieli, $page_tarjous2[$sivu], 0);
					
					list($page_tarjous2[$sivu], $kala) = uusi_sivu_tarjous ($laskurow, $asiakasrow, $sivu, $kieli, $pdf_tarjous);
				}
				else {
					$kala = $kala_nyt;
				}
				
				list($page_tarjous2[$sivu], $kala, $lask, $sivu) = rivi_tarjous($pdf_tarjous, $laskurow, $kala, $kieli, $page_tarjous2[$sivu], $sarjanutunnus, $selite, $row, $hinnat, $lask, $sivu);

				$lask++;
				
				if ($row["hinta"] != 0 and $hinnat == "BR" and (strpos($selite, 'Vene') !== FALSE or strpos($selite, 'Vaihtovene') !== FALSE)) {				
					$total += $row["bruttorivihinta"];
				}
				else {
					$total += $row["rivihinta"];
				}					
			}
			return array ($page_tarjous2[$sivu], $lask, $kala, $sivu, $total);
		}


		//oletuksia
		$lask 			= 0;
		$total 			= 0;
		$sivu 			= 1;

		//Myyntisopimuksen pohja
		$pdf_tarjous = new pdffile;

		$pdf_tarjous->set_default('margin-top', 20);
		$pdf_tarjous->set_default('margin-bottom', 0);
		$pdf_tarjous->set_default('margin-left', 0);
		$pdf_tarjous->set_default('margin-right', 0);

		$pdf_tarjous->enable('import');

		list($page_tarjous[$sivu], $kala) = alku_tarjous ($laskurow, $asiakasrow, $sivu, $kieli, $pdf_tarjous);

		// Rivit
		list ($page_tarjous[$sivu], $lask, $kala, $sivu, $total) = printtaa_rivit_tarjous($pdf_tarjous, $laskurow, $lask, $kala, $kieli, $sivu, $page_tarjous[$sivu], "", "", "", $total, $hinnat);
		
		$total_uudet  = $total;
		$total_vanhat = $total - $total_uudet;
		
		for ($pp=1; $pp<=$sivu; $pp++) {
			$pdf_tarjous->draw_text(330, 803, "$pp / $sivu", $page_tarjous[$pp], $norm);
		}
		
		// Lopputekstit
		loppu_tarjous ($pdf_tarjous, $laskurow, $kieli, $page_tarjous[$sivu], 1, $total_uudet, $total_vanhat);

		// Tulostetaan sivu
		print_pdf_tarjous ($pdf_tarjous, $komento, $tee);
	}
}

?>
