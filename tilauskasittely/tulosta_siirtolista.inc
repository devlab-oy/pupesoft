<?php

//viivakoodien tulostamista varten
define (__TRACE_ENABLED__, false);
define (__DEBUG_ENABLED__, false);

if ($kukarow["extranet"] == '') {
	require_once("../barcode/barcode.php");
	require_once("../barcode/c39object.php");
	require_once("../pdflib/phppdflib.class.php");
}
else {
	require_once("barcode/barcode.php");
	require_once("barcode/c39object.php");
	require_once("pdflib/phppdflib.class.php");
}

$p["height"] = 10;
$p["font"] = "Times-Roman";
$rectparam["width"] = 0.3;

//muutamat funktiot...
if (!function_exists('alku')) {
	function alku () {
		global $pdf, $image, $laskurow, $yhtiorow, $tid, $viite, $p, $kala, $lask, $rectparam, $kukarow, $kieli;
	
		//oletukasia
		$kala = 678;
		$lask = 1;
	
		//oletukset viivakoodeille
		$output   = "png";
		$width    = "140";
		$height   = "40";
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
	
		$firstpage = $pdf->new_page("a4");
		$pdf->enable('template');
		$tid = $pdf->template->create();
		$pdf->template->size($tid, 600, 830);
	
		$pdf->draw_rectangle(825, 20,  780, 580, 		$firstpage, $rectparam);
		$pdf->draw_text(30, 815, $yhtiorow["nimi"], 	$firstpage, $p);
		$pdf->draw_text(30, 805, $yhtiorow["osoite"], 	$firstpage, $p);
		$pdf->draw_text(30, 795, $yhtiorow["postino"], 	$firstpage, $p);
		$pdf->draw_text(60, 795, $yhtiorow["postitp"], 	$firstpage, $p);
		$pdf->draw_text(250,810, t("Varastosiirto", $kieli), 	$firstpage);
		$pdf->draw_text(250,795, t("Listanumero", $kieli).": ".$laskurow["tunnus"], $firstpage, $p);
		
		$i["height"] = 28;
		$i["font"] = "Times-Roman";
		$i["color"]['red']   = 1;
		$i["color"]['blue']  = 0;
		$i["color"]['green'] = 0;
		
		$pdf->draw_text(400, 795, $laskurow["hyvaksynnanmuutos"], $firstpage, $i);	
		
		//varastosta
		$query  = "	SELECT nimitys
					FROM varastopaikat
					WHERE yhtio='$kukarow[yhtio]' 
					and tunnus='$laskurow[varasto]'";
		$vares = mysql_query($query) or pupe_error($query);
		$paikkarow = mysql_fetch_array($vares);
		$varastosta = $paikkarow["nimitys"];
		
		$pdf->draw_rectangle(780, 20,  730, 200, $firstpage, $rectparam);
		$pdf->draw_text(30, 760, t("Varastosta", $kieli).":", 		$firstpage, $p);
		$pdf->draw_text(30, 740, $varastosta, 	$firstpage, $p);
		
		//varastoon
		$query  = "	SELECT nimitys
					FROM varastopaikat
					WHERE yhtio='$kukarow[yhtio]' 
					and tunnus='$laskurow[ytunnus]'";
		$vares = mysql_query($query) or pupe_error($query);
		$paikkarow = mysql_fetch_array($vares);
		$varastoon = $paikkarow["nimitys"];
		
		$pdf->draw_rectangle(780, 200, 730, 380, $firstpage, $rectparam);
		$pdf->draw_text(210, 760, t("Varastoon", $kieli).":", $firstpage, $p);
		$pdf->draw_text(210, 740, $varastoon, $firstpage, $p);
		
		$pdf->draw_rectangle(780, 380, 730, 580, $firstpage, $rectparam);
		$pdf->draw_text(390, 760, t("Päivämäärä", $kieli).": ".substr($laskurow["lahetepvm"],0,10) , $firstpage, $p);
		$pdf->draw_text(390, 750, t("Laatija", $kieli).": ".$laskurow["laatija"] . "@" . $laskurow["luontiaika"], $firstpage, $p);	
		$pdf->draw_text(390, 740, t("Tilausviite", $kieli).": ".substr($laskurow["viesti"],0,22), $firstpage, $p);
		
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
			$placement = $pdf->image_place($image, 783, 420, $firstpage);
	
			unset($obj);
	
			//dellataan tmp filet kuleksimasta
			system("rm -f $nimi1 $nimi2");
		}
		else{
			echo t("Oliota ei voitu luoda").".";
			exit;
		}
	
		$pdf->draw_rectangle(710, 20,  690, 40,  $firstpage, 	$rectparam);
		$pdf->draw_rectangle(710, 40,  690, 120, $firstpage, 	$rectparam);
		$pdf->draw_rectangle(710, 120, 690, 220, $firstpage,	$rectparam);
		$pdf->draw_rectangle(710, 220, 690, 460, $firstpage, 	$rectparam);
		$pdf->draw_rectangle(710, 460, 690, 520, $firstpage, 	$rectparam);
		$pdf->draw_rectangle(710, 520, 690, 580, $firstpage, 	$rectparam);
	
		$pdf->draw_text(25,  697, "#",						$firstpage, $p);
		$pdf->draw_text(45,  697, t("Paikka", $kieli),		$firstpage, $p);
		$pdf->draw_text(125, 697, t("Tuotenumero", $kieli),	$firstpage, $p);
		$pdf->draw_text(225, 697, t("Tuotenimi", $kieli),	$firstpage, $p);
		$pdf->draw_text(465, 697, t("Siirretään", $kieli),	$firstpage, $p);
		$pdf->draw_text(525, 697, t("Siirretty", $kieli),	$firstpage, $p);
	
		$pdf->draw_rectangle(710, 20, 60, 580, $firstpage, $rectparam);
	
		return($firstpage);
	}
}

if (!function_exists('rivi')) {
	function rivi($firstpage) {
		global $pdf, $row, $kala, $lask, $total, $rectparam, $kukarow, $rivinumerot, $kieli;
	
		$p["height"]	= 10;
		$p["font"]		= "Times-Roman";
		$pp["height"]	= 8;
		$pp["font"]		= "Times-Roman";
		
		$rivinkorkeus	= 15;
	
		//jos on paljon rivejä tehdään uusi otsikko...
		if ($lask > 40) {
			loppu($firstpage, 0);
			$firstpage = alku();
		}
	
		$varastopaikka = $row["hyllyalue"] . " " . $row["hyllynro"] . " " . $row["hyllyvali"] . " " . $row["hyllytaso"];
		if ($row["jt"] == 0) { // jälkitoimitus
			
			$pdf->draw_text(25,  $kala, $rivinumerot[$row["tunnus"]],	$firstpage, $pp);
			$pdf->draw_text(45,  $kala, $varastopaikka,					$firstpage, $p);
			$pdf->draw_text(125,  $kala, $row["tuoteno"],				$firstpage, $p);
			$pdf->draw_text(465, $kala, $row["tilkpl"]." ".strtolower($row["yksikko"]), 	$firstpage, $p);
		
			if ($row["keratty"] != '') {
				$pdf->draw_text(525, $kala, $row['varattu'], 	$firstpage, $p);
			}
		
			$hash = '';
			if ($row["perheid"] > 0) {
				$hash = '#';
			}
		
			if (strlen(asana('nimitys_',$row['tuoteno'],$row['nimitys'])) > 32) {
				$kala = $kala - $rivinkorkeus;
				$lask++;
			}
			$pdf->draw_text(225, $kala, $hash.asana('nimitys_',$row['tuoteno'],$row['nimitys']), 	$firstpage, $p);
	
			$kala = $kala - $rivinkorkeus;
			$lask++;	
		
			if ($row["tunnus"] == $row["perheid"]) {
				$query = "	SELECT * 
							FROM tuoteperhe 
							WHERE isatuoteno='$row[tuoteno]' and yhtio='$kukarow[yhtio]'
							and tyyppi='P'
							ORDER by tuoteno";
				$perheresult = mysql_query($query) or pupe_error($query);
			
				$litania = '';
				while ($perherow = mysql_fetch_array($perheresult)) {	
					$litania .= ", ".strtoupper($perherow["tuoteno"])." ";	
				}
		
				$pdf->draw_text(90,  $kala, "# ".t("Tuoteperhe", $kieli).": ".$row["tuoteno"].$litania,	$firstpage, $p);
			
				$kala = $kala - $rivinkorkeus;
				$lask++;
			}
		
			if (trim($row["kommentti"]) != '' or $row["tilaajanrivinro"] != 0) {
			
				if ($row["tilaajanrivinro"] != 0) {
					$futur = t("Rivinumero", $kieli).": ".$row["tilaajanrivinro"].". ";	
				}
				else {
					$futur = "";
				}
					
				$pdf->draw_text(90,  $kala, "* $futur".$row["kommentti"],	$firstpage, $p);
			
				$kala = $kala - $rivinkorkeus;
				$lask++;
			}
		
	
			// viivat rivien väliin...
			$x[0] = 20;
			$x[1] = 580;
			$y[0] = $y[1] = $kala+$rivinkorkeus-4;
			$pdf->draw_line($x, $y, $firstpage, $rectparam);
		}
		
		//$pdf->draw_rectangle($kala+$rivinkorkeus-4, 20, $kala+$rivinkorkeus-4, 580, $firstpage);
	
		return($firstpage);
	}
}

if (!function_exists('loppu')) {
	function loppu ($firstpage, $tots) {
		global $pdf, $laskurow, $viite, $p, $total, $rectparam, $kieli;
	
		$pdf->draw_rectangle(50, 20, 20, 580, $firstpage, $rectparam);
		
		if ($tots == 1) {
			$pdf->draw_text(30, 37,  t("Tilausviite", $kieli).": ".$laskurow["viesti"], $firstpage, $p);
			$pdf->draw_text(30, 25,  t("Kommentti", $kieli).":   ".$laskurow["comments"], $firstpage, $p);
		}
		else {
			$pdf->draw_text(530, 23, t("Jatkuu", $kieli)."...", $firstpage, $p);
		}
	}
}

if (!function_exists('print_pdf')) {
	function print_pdf ($komento) {
		global $pdf, $kukarow, $yhtiorow, $returnvalue, $kieli, $tee;
	
		$oslapp='';
		$returnvalue=0;
	
		//keksitään uudelle failille joku varmasti uniikki nimi:
		list($usec, $sec) = explode(' ', microtime());
		mt_srand((float) $sec + ((float) $usec * 100000));
		$pdffilenimi = "/tmp/Lahete-".md5(uniqid(mt_rand(), true)).".pdf";
	
		//kirjoitetaan pdf faili levylle..
		$fh = fopen($pdffilenimi, "w");
		if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF create error $pdffilenimi");
		fclose($fh);
		
		
		if ($komento == 'email') {
			$liite = $pdffilenimi;
			$kutsu = "Siirtolista";
			
			require("../inc/sahkoposti.inc");
		}
		elseif ($tee == 'NAYTATILAUS') {
			//Työnnetään tuo pdf vaan putkeen!
			echo file_get_contents($pdffilenimi);
		}
		elseif ($komento != '' and $komento != 'edi') {		
			$line = exec("$komento $pdffilenimi", $output, $returnvalue);
		}
		
		//poistetaan tmp file samantien kuleksimasta...
		system("rm -f $pdffilenimi");
	}
}

?>