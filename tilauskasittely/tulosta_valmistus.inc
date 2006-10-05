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
if (!function_exists('alku_valm')) {
	function alku_valm () {
		global $pdf_valm, $image, $laskurow, $yhtiorow, $viite, $p, $kala_valm, $lask_valm, $rectparam, $kukarow, $kieli, $tilausnumeroita;
	
		//oletukasia
		$kala_valm = 638;
		$lask_valm = 1;
	
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
	
		$firstpage_valm = $pdf_valm->new_page("a4");
		
		$pdf_valm->draw_rectangle(825, 20,  780, 580, 		$firstpage_valm, $rectparam);
		$pdf_valm->draw_text(30, 815, $yhtiorow["nimi"], 	$firstpage_valm, $p);
		$pdf_valm->draw_text(30, 805, $yhtiorow["osoite"], 	$firstpage_valm, $p);
		$pdf_valm->draw_text(30, 795, $yhtiorow["postino"], 	$firstpage_valm, $p);
		$pdf_valm->draw_text(60, 795, $yhtiorow["postitp"], 	$firstpage_valm, $p);
		$pdf_valm->draw_text(250,810, t("Valmistuslista", $kieli), 	$firstpage_valm);
		$pdf_valm->draw_text(150,795, t("Tilausnumero", $kieli).": ".$tilausnumeroita, $firstpage_valm, $p);
		
		$i["height"] = 28;
		$i["font"] = "Times-Roman";
		$i["color"]['red']   = 1;
		$i["color"]['blue']  = 0;
		$i["color"]['green'] = 0;
		
		$pdf_valm->draw_text(400, 795, $laskurow["hyvaksynnanmuutos"], $firstpage_valm, $i);	
		
	
		$pdf_valm->draw_rectangle(780, 20,  680, 200, 		$firstpage_valm, $rectparam);
		$pdf_valm->draw_text(30, 760, t("Toimitusosoite", $kieli).":", 		$firstpage_valm, $p);
		$pdf_valm->draw_text(30, 740, $laskurow["toim_nimi"], 		$firstpage_valm, $p);
		$pdf_valm->draw_text(30, 730, $laskurow["toim_nimitark"], 	$firstpage_valm, $p);
		$pdf_valm->draw_text(30, 720, $laskurow["toim_osoite"], 	$firstpage_valm, $p);
		$pdf_valm->draw_text(30, 710, $laskurow["toim_postino"], 	$firstpage_valm, $p);
		$pdf_valm->draw_text(60, 710, $laskurow["toim_postitp"], 	$firstpage_valm, $p);
		$pdf_valm->draw_text(30, 700, $laskurow["toim_maa"], 		$firstpage_valm, $p);
	
		$pdf_valm->draw_rectangle(780, 200, 680, 380, $firstpage_valm, $rectparam);
		$pdf_valm->draw_text(210, 760, t("Laskutusosoite", $kieli).":", $firstpage_valm, $p);
		$pdf_valm->draw_text(210, 740, $laskurow["nimi"], 		$firstpage_valm, $p);
		$pdf_valm->draw_text(210, 730, $laskurow["nimitark"], 	$firstpage_valm, $p);
		$pdf_valm->draw_text(210, 720, $laskurow["osoite"], 	$firstpage_valm, $p);
		$pdf_valm->draw_text(210, 710, $laskurow["postino"], 	$firstpage_valm, $p);
		$pdf_valm->draw_text(240, 710, $laskurow["postitp"], 	$firstpage_valm, $p);
		$pdf_valm->draw_text(210, 700, $laskurow["maa"], 		$firstpage_valm, $p);
	
		// haetaan maksuehdon tiedot
		$query  = "select * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
		$masresult = mysql_query($query) or pupe_error($query);
		$masrow = mysql_fetch_array($masresult);
	
		//maksuehto tekstinä
		$maksuehto = $masrow["teksti"]." ".$masrow["kassa_teksti"];
		
		$pdf_valm->draw_rectangle(780, 380, 680, 580, $firstpage_valm, $rectparam);
		$pdf_valm->draw_text(390, 760, t("Asiakasnumero", $kieli).": ".$laskurow["ytunnus"], $firstpage_valm, $p);
		$pdf_valm->draw_text(390, 750, t("Päivämäärä", $kieli).": ".substr($laskurow["lahetepvm"],0,10) , $firstpage_valm, $p);
		$pdf_valm->draw_text(390, 740, t("Laatija", $kieli).": ".$laskurow["laatija"] . "@" . $laskurow["luontiaika"], $firstpage_valm, $p);	
		$pdf_valm->draw_text(390, 720, t("Toimitustapa", $kieli).": ".$laskurow["toimitustapa"], $firstpage_valm, $p);
		$pdf_valm->draw_text(390, 710, t("Maksuehto", $kieli).": ".$maksuehto, $firstpage_valm, $p); 	
		$pdf_valm->draw_text(390, 690, t("Tilausviite", $kieli).": ".substr($laskurow["viesti"],0,18), $firstpage_valm, $p);
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
			$image = $pdf_valm->png_embed($data);
	
			if(!$image) {
				echo t("Viivakoodivirhe").": ".$image.$data;
				exit;
			}
			$placement = $pdf_valm->image_place($image, 783, 420, $firstpage_valm);
	
			unset($obj);
	
			//dellataan tmp filet kuleksimasta
			system("rm -f $nimi1 $nimi2");
		}
		else{
			echo t("Oliota ei voitu luoda").".";
			exit;
		}
	
		$pdf_valm->draw_rectangle(670, 20,  650, 40,  $firstpage_valm, 	$rectparam);
		$pdf_valm->draw_rectangle(670, 40,  650, 120, $firstpage_valm, 	$rectparam);
		$pdf_valm->draw_rectangle(670, 120, 650, 220, $firstpage_valm,	$rectparam);
		$pdf_valm->draw_rectangle(670, 220, 650, 460, $firstpage_valm, 	$rectparam);
		$pdf_valm->draw_rectangle(670, 460, 650, 520, $firstpage_valm, 	$rectparam);
		$pdf_valm->draw_rectangle(670, 520, 650, 580, $firstpage_valm, 	$rectparam);
	
		$pdf_valm->draw_text(25,  657, "#",							$firstpage_valm, $p);
		$pdf_valm->draw_text(45,  657, t("Paikka", $kieli),			$firstpage_valm, $p);
		$pdf_valm->draw_text(125, 657, t("Tuotenumero", $kieli),	$firstpage_valm, $p);
		$pdf_valm->draw_text(225, 657, t("Tuotenimi", $kieli),		$firstpage_valm, $p);
		$pdf_valm->draw_text(465, 657, t("Valmistetaan", $kieli),	$firstpage_valm, $p);
		$pdf_valm->draw_text(525, 657, t("Valmistettu", $kieli),	$firstpage_valm, $p);
	
		$pdf_valm->draw_rectangle(670, 20, 60, 580, $firstpage_valm, $rectparam);
	
		return($firstpage_valm);
	}
}

if (!function_exists('rivi_valm')) {
	function rivi_valm($firstpage_valm) {
		global $pdf_valm, $row, $kala_valm, $lask_valm, $total, $rectparam, $kukarow, $rivinumerot, $kieli, $perhe, $rivinkorkeus, $isakpl, $pohja;
	
		$p["height"]	= 10;
		$p["font"]		= "Times-Roman";
		$pp["height"]	= 8;
		$pp["font"]		= "Times-Roman";
		
		$rivinkorkeus	= 15;
	
		//jos on paljon rivejä tehdään uusi otsikko...
		if ($lask_valm > 39) {
			loppu_valm($firstpage_valm, 0);
			$firstpage_valm = alku_valm();
		}
	
		$varastopaikka = $row["hyllyalue"] . " " . $row["hyllynro"] . " " . $row["hyllyvali"] . " " . $row["hyllytaso"];
		
		if ($row["jt"] == 0) {
			
			if ($perhe != $row["perheid"] and $perhe != 'KALA') {		
				$x[0] = 20;
				$x[1] = 580;
				$y[0] = $y[1] = $kala_valm+$rivinkorkeus-4;
				$pdf_valm->draw_line($x, $y, $firstpage_valm, $rectparam);
			}
			
			
			
			if ($row["tunnus"] == $row["perheid"]) {
				$pdf_valm->draw_text(35,  $kala_valm, "Valmiste:",	$firstpage_valm, $p);
				
				$kala_valm = $kala_valm - $rivinkorkeus;
				$lask_valm++;
				
				$isakpl = $row["tilkpl"];
				$lapskpl = '';
			}
			else {
				$lapskpl = $row["tilkpl"]/$isakpl;
			}
			
			$pdf_valm->draw_text(25,  $kala_valm, $rivinumerot[$row["tunnus"]],	$firstpage_valm, $pp);
			$pdf_valm->draw_text(45,  $kala_valm, $varastopaikka,					$firstpage_valm, $p);
			
			
			$pdf_valm->draw_text(125, $kala_valm, $row["tuoteno"],				$firstpage_valm, $p);
			$pdf_valm->draw_text(465, $kala_valm, round($lapskpl,8)." (".$row["tilkpl"].") ".strtolower($row["yksikko"]), 	$firstpage_valm, $p);
			
			if ($row["keratty"] != '') {
				$pdf_valm->draw_text(525, $kala_valm, $row['varattu'], 	$firstpage_valm, $p);
			}
			
			if (strlen($row["nimitys"]) > 32) {
				$kala_valm = $kala_valm - $rivinkorkeus;
				$lask_valm++;
			}
			
			$pdf_valm->draw_text(225, $kala_valm, $row["nimitys"], 	$firstpage_valm, $p);
	
			$kala_valm = $kala_valm - $rivinkorkeus;
			$lask_valm++;
		
			if (trim($row["kommentti"]) != '') {
				$pdf_valm->draw_text(90,  $kala_valm, "* ".$row["kommentti"],	$firstpage_valm, $p);
			
				$kala_valm = $kala_valm - $rivinkorkeus;
				$lask_valm++;
			}
			
			if ($row["tunnus"] == $row["perheid"]) {
				$query = "SELECT fakta FROM tuoteperhe WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'R' and isatuoteno = '$row[tuoteno]' and fakta is not null ORDER BY isatuoteno, tuoteno LIMIT 1";
				$ressu = mysql_query($query) or pupe_error($query);
				if (mysql_num_rows($ressu) == 1) {
					$faktarow = mysql_fetch_array($ressu);
					$kala_valm += 10;
					$pohja = '';
					$pohja = $pdf_valm->draw_paragraph($kala_valm, 35, 60, 570, $faktarow["fakta"], $firstpage_valm, $pp);
					$lask_valm += ceil(($kala_valm - $pohja) / $rivinkorkeus);
					$kala_valm = $pohja - $rivinkorkeus;
					
				}
				
				$pdf_valm->draw_text(35,  $kala_valm, "Raaka-aineet:",	$firstpage_valm, $p);
				
				$kala_valm = $kala_valm - $rivinkorkeus;
				$lask_valm++;
			}
	
			$perhe = $row["perheid"];
		}
		
		return($firstpage_valm);
	}
}

if (!function_exists('loppu_valm')) {
	function loppu_valm ($firstpage_valm, $tots) {
		global $pdf_valm, $laskurow, $viite, $p, $total, $rectparam, $kieli;
	
		$pdf_valm->draw_rectangle(50, 20, 20, 580, $firstpage_valm, $rectparam);
		
		if ($tots == 1) {
			$pdf_valm->draw_text(30, 37,  t("Tilausviite", $kieli).": ".$laskurow["viesti"], $firstpage_valm, $p);
			$pdf_valm->draw_text(30, 25,  t("Kommentti", $kieli).":   ".$laskurow["comments"], $firstpage_valm, $p);
		}
		else {
			$pdf_valm->draw_text(530, 23, t("Jatkuu", $kieli)."...", $firstpage_valm, $p);
		}
	}
}

if (!function_exists('print_pdf_valm')) {
	function print_pdf_valm ($komento) {
		global $pdf_valm, $kukarow, $yhtiorow, $returnvalue, $kieli, $tee;
	
		$oslapp='';
		$returnvalue=0;
	
		//keksitään uudelle failille joku varmasti uniikki nimi:
		list($usec, $sec) = explode(' ', microtime());
		mt_srand((float) $sec + ((float) $usec * 100000));
		$pdf_valmfilenimi = "/tmp/Lahete-".md5(uniqid(mt_rand(), true)).".pdf";
	
		//kirjoitetaan pdf faili levylle..
		$fh = fopen($pdf_valmfilenimi, "w");
		if (fwrite($fh, $pdf_valm->generate()) === FALSE) die("PDF create error $pdf_valmfilenimi");
		fclose($fh);
		
		
		if ($komento == 'email') {
			$liite = $pdf_valmfilenimi;
			$kutsu = "Siirtolista";
			
			require("../inc/sahkoposti.inc");
		}
		elseif ($tee == 'NAYTATILAUS') {
			//Työnnetään tuo pdf vaan putkeen!
			echo file_get_contents($pdf_valmfilenimi);
		}
		elseif ($komento != '' and $komento != 'edi') {		
			$line = exec("$komento $pdf_valmfilenimi", $output, $returnvalue);
		}
		
		//poistetaan tmp file samantien kuleksimasta...
		system("rm -f $pdf_valmfilenimi");
	}
}
?>