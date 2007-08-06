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

$p["height"]		= 10;
$p["font"]			= "Times-Roman";
$pp["height"]		= 8;
$pp["font"]			= "Times-Roman";
$rectparam["width"] = 0.3;
$rivinkorkeus		= 15;

//muutamat funktiot...
if (!function_exists('alku')) {
	function alku () {
		global $pdf, $sivu, $lahetetyyppi, $laskurow, $yhtiorow, $viite, $p, $kala, $rectparam, $kukarow, $kieli, $asrow;
	
		//PDF parametrit
		if (!isset($pdf)) {
			$pdf = new pdffile;
			$pdf->set_default('margin-top', 	0);
			$pdf->set_default('margin-bottom', 	0);
			$pdf->set_default('margin-left', 	0);
			$pdf->set_default('margin-right', 	0);
		}

		$thispage = $pdf->new_page("a4");
		
		
		//oletukasia
		$kala = 638;
	
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
			
		if (trim($yhtiorow["lasku_logo"]) != '' and file_exists($yhtiorow["lasku_logo"])) {
			
			$filename = $yhtiorow["lasku_logo"];

			$fh = fopen($filename, "r");
			$data = fread($fh, filesize($filename));
			fclose($fh);

			$image = $pdf->jfif_embed($data);

			if(!$image) {
				echo t("Logokuvavirhe").": ".$image.$data;
			}
			else {
				$logoparam = array();
				$logoparam['scale'] = 0.15;
				
				$isizelogo = getimagesize($yhtiorow["lasku_logo"]);
				$iy    = $isizelogo[1]*0.15*0.35714;	// kuvan y-korkeus millimetreissä
				
				$placement = $pdf->image_place($image, mm_pt(292-$iy), mm_pt(7), $thispage, $logoparam);
			}
		}
		else {
			$pdf->draw_text(30, 815, $yhtiorow["nimi"], 	$thispage, $p);
			$pdf->draw_text(30, 805, $yhtiorow["osoite"], 	$thispage, $p);
			$pdf->draw_text(30, 795, $yhtiorow["postino"], 	$thispage, $p);
			$pdf->draw_text(60, 795, $yhtiorow["postitp"], 	$thispage, $p);
			$pdf->draw_text(30, 785, $yhtiorow["maa"], 		$thispage, $p);
		}
		
		$pdf->draw_text(310,815, t("Lähete", $kieli), 	$thispage);		
		$pdf->draw_text(390, 815, t("Sivu", $kieli)." $sivu", 										$thispage, $p);
		$pdf->draw_text(310,785, t("Tilausnumero", $kieli).": ".$laskurow["tunnus"], 				$thispage, $p);
	
		$pdf->draw_rectangle(780, 20,  680, 200, $thispage, $rectparam);
		$pdf->draw_text(30, 760, t("Toimitusosoite", $kieli).":", 								$thispage, $p);
		$pdf->draw_text(30, 740, $laskurow["toim_nimi"], 										$thispage, $p);
		$pdf->draw_text(30, 730, $laskurow["toim_nimitark"], 									$thispage, $p);
		$pdf->draw_text(30, 720, $laskurow["toim_osoite"], 										$thispage, $p);
		$pdf->draw_text(30, 710, $laskurow["toim_postino"]."   ".$laskurow["toim_postitp"], 	$thispage, $p);
		$pdf->draw_text(30, 700, $laskurow["toim_maa"],			 								$thispage, $p);
	    $pdf->draw_text(30, 690, $asrow["puhelin"], 											$thispage, $p);
	    
		$pdf->draw_rectangle(780, 200, 680, 380, $thispage, $rectparam);
		$pdf->draw_text(210, 760, t("Laskutusosoite", $kieli).":", 								$thispage, $p);
		$pdf->draw_text(210, 740, $laskurow["nimi"], 											$thispage, $p);
		$pdf->draw_text(210, 730, $laskurow["nimitark"], 										$thispage, $p);
		$pdf->draw_text(210, 720, $laskurow["osoite"], 											$thispage, $p);
		$pdf->draw_text(210, 710, $laskurow["postino"]."   ".$laskurow["postitp"], 				$thispage, $p);
		$pdf->draw_text(210, 700, $laskurow["maa"], 											$thispage, $p);
	
		// haetaan maksuehdon tiedot
		$query  = "select * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
		$masresult = mysql_query($query) or pupe_error($query);
		$masrow = mysql_fetch_array($masresult);
	
		//maksuehto tekstinä
		$maksuehto = $masrow["teksti"]." ".$masrow["kassa_teksti"];
		
		// haetaan max(kerattyaika)
		$query  = "SELECT max(kerattyaika) as kerattyaika from tilausrivi where yhtio='$kukarow[yhtio]' and otunnus='$laskurow[tunnus]'";
		$keraresult = mysql_query($query) or pupe_error($query);
		$kerarow = mysql_fetch_array($keraresult);
		
		$pdf->draw_rectangle(780, 380, 680, 580, $thispage, $rectparam);
		$pdf->draw_text(390, 760, t("Asiakasnumero", $kieli).": ".$laskurow["ytunnus"], 							$thispage, $p);
		$pdf->draw_text(390, 750, t("Päiväys", $kieli).": ".tv1dateconv($kerarow["kerattyaika"]), 					$thispage, $p);
		$pdf->draw_text(390, 740, t("Laatija", $kieli).": ".$laskurow["laatija"] . "@" . tv1dateconv($laskurow["luontiaika"], "pitka"), 	$thispage, $p);
		$pdf->draw_text(390, 720, t("Toimitustapa", $kieli).": ".$laskurow["toimitustapa"], 						$thispage, $p);
		$pdf->draw_text(390, 710, t("Toimitusehto", $kieli).": ".$laskurow["toimitusehto"], 						$thispage, $p);
		$pdf->draw_text(390, 700, t("Maksuehto", $kieli).": ".$maksuehto, 											$thispage, $p);
		$pdf->draw_text(390, 690, t("Tilausviite", $kieli).": ".substr($laskurow["viesti"],0,18), 					$thispage, $p);
		
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
			$placement = $pdf->image_place($image, 783, 420, $thispage);
	
			unset($obj);
	
			//dellataan tmp filet kuleksimasta
			system("rm -f $nimi1 $nimi2");
		}
		else{
			echo t("Oliota ei voitu luoda").".";
			exit;
		}
	
		$pdf->draw_rectangle(650, 20, 60, 580, $thispage, $rectparam);
		
		rivi_otsikot($thispage);
		
		return($thispage);
	}
}

if (!function_exists('rivi_otsikot')) {
	function rivi_otsikot($thispage) {
		global $pdf, $kieli, $lahetetyyppi, $p, $rectparam;
		
		if (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete.inc") {
			// tulosta_lahete.inc
			
			$pdf->draw_rectangle(670, 20,  650, 40,  	$thispage, $rectparam);
			$pdf->draw_rectangle(670, 40,  650, 180, 	$thispage, $rectparam);
			$pdf->draw_rectangle(670, 180, 650, 320, 	$thispage, $rectparam);
			$pdf->draw_rectangle(670, 320, 650, 370, 	$thispage, $rectparam);
			$pdf->draw_rectangle(670, 370, 650, 420, 	$thispage, $rectparam);
			$pdf->draw_rectangle(670, 420, 650, 470, 	$thispage, $rectparam);
			$pdf->draw_rectangle(670, 470, 650, 520, 	$thispage, $rectparam);
			$pdf->draw_rectangle(670, 520, 650, 580, 	$thispage, $rectparam);
			$pdf->draw_text(25,  657, "#",							$thispage, $p);
			$pdf->draw_text(45,  657, t("Tuotenumero", $kieli),		$thispage, $p);
			$pdf->draw_text(190, 657, t("Tuotenimi", $kieli),		$thispage, $p);
			$pdf->draw_text(330, 657, t("Tilattu", $kieli),			$thispage, $p);
			$pdf->draw_text(375, 657, t("Toimitus", $kieli),		$thispage, $p);
			$pdf->draw_text(425, 657, t("Yks.hinta", $kieli),		$thispage, $p);
			$pdf->draw_text(478, 657, t("Alennus", $kieli),			$thispage, $p);
			$pdf->draw_text(530, 657, t("Rivihinta", $kieli),		$thispage, $p);
		}
		elseif (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_brutto.inc") {
			// tulosta_lahete_brutto.inc
			
			$pdf->draw_rectangle(670, 20,  650, 40,  	$thispage, $rectparam);
			$pdf->draw_rectangle(670, 40,  650, 140, 	$thispage, $rectparam);		
			$pdf->draw_rectangle(670, 140, 650, 420, 	$thispage, $rectparam);
			$pdf->draw_rectangle(670, 420, 650, 470, 	$thispage, $rectparam);
			$pdf->draw_rectangle(670, 470, 650, 520, 	$thispage, $rectparam);
			$pdf->draw_rectangle(670, 520, 650, 580, 	$thispage, $rectparam);
			$pdf->draw_text(25,  657, "#",							$thispage, $p);
			$pdf->draw_text(45,  657, t("Tuotenumero", $kieli),		$thispage, $p);
			$pdf->draw_text(145, 657, t("Tuotenimi", $kieli),		$thispage, $p);
			$pdf->draw_text(425, 657, t("Tilattu", $kieli),			$thispage, $p);
			$pdf->draw_text(475, 657, t("Toimitus", $kieli),		$thispage, $p);
			$pdf->draw_text(525, 657, t("Ovh/Kpl", $kieli),			$thispage, $p);
		}
		elseif (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_eialeja.inc") {
			//tulosta_lahete_eialeja.inc
			
			$pdf->draw_rectangle(670, 20,  650, 40,  	$thispage, $rectparam);
			$pdf->draw_rectangle(670, 40,  650, 180, 	$thispage, $rectparam);
			$pdf->draw_rectangle(670, 180, 650, 320, 	$thispage, $rectparam);
			$pdf->draw_rectangle(670, 320, 650, 370, 	$thispage, $rectparam);
			$pdf->draw_rectangle(670, 370, 650, 420, 	$thispage, $rectparam);
			$pdf->draw_rectangle(670, 420, 650, 470, 	$thispage, $rectparam);
			$pdf->draw_rectangle(670, 470, 650, 520, 	$thispage, $rectparam);
			$pdf->draw_rectangle(670, 520, 650, 580, 	$thispage, $rectparam);
			$pdf->draw_text(25,  657, "#",							$thispage, $p);
			$pdf->draw_text(45,  657, t("Tuotenumero", $kieli),		$thispage, $p);
			$pdf->draw_text(190, 657, t("Tuotenimi", $kieli),		$thispage, $p);
			$pdf->draw_text(330, 657, t("Tilattu", $kieli),			$thispage, $p);
			$pdf->draw_text(375, 657, t("Toimitus", $kieli),		$thispage, $p);
			$pdf->draw_text(425, 657, t("Yks.hinta", $kieli),		$thispage, $p);
			$pdf->draw_text(473, 657, t("Alekoodi", $kieli),		$thispage, $p);
			$pdf->draw_text(530, 657, t("Rivihinta", $kieli),		$thispage, $p);
		}
		elseif (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_viivakoodi.inc") {
			// tulosta_lahete_viivakoodi.inc
			
			$pdf->draw_rectangle(670, 20,  650, 40,  	$thispage, $rectparam);
			$pdf->draw_rectangle(670, 40,  650, 180,  	$thispage, $rectparam);
			$pdf->draw_rectangle(670, 180, 650, 480, 	$thispage, $rectparam);
			$pdf->draw_rectangle(670, 480, 650, 530, 	$thispage, $rectparam);
			$pdf->draw_rectangle(670, 530, 650, 580, 	$thispage, $rectparam);
			$pdf->draw_text(25,  657, "#",							$thispage, $p);
			$pdf->draw_text(45,  657, t("Tuotenumero", $kieli),		$thispage, $p);
			$pdf->draw_text(190, 657, t("Tuotenimi", $kieli),		$thispage, $p);
			$pdf->draw_text(490, 657, t("Tilattu", $kieli),			$thispage, $p);
			$pdf->draw_text(540, 657, t("Toimitus", $kieli),		$thispage, $p);
		}
		else{
			//tulosta_lahete_eiale_eihinta.inc
			
			$pdf->draw_rectangle(670, 20,  650, 40,  	$thispage, $rectparam);
			$pdf->draw_rectangle(670, 40,  650, 180,  	$thispage, $rectparam);
			$pdf->draw_rectangle(670, 180, 650, 480, 	$thispage, $rectparam);
			$pdf->draw_rectangle(670, 480, 650, 530, 	$thispage, $rectparam);
			$pdf->draw_rectangle(670, 530, 650, 580, 	$thispage, $rectparam);
			$pdf->draw_text(25,  657, "#",							$thispage, $p);
			$pdf->draw_text(45,  657, t("Tuotenumero", $kieli),		$thispage, $p);
			$pdf->draw_text(190, 657, t("Tuotenimi", $kieli),		$thispage, $p);
			$pdf->draw_text(490, 657, t("Tilattu", $kieli),			$thispage, $p);
			$pdf->draw_text(540, 657, t("Toimitus", $kieli),		$thispage, $p);
		}
	}
}

if (!function_exists('rivi')) {
	function rivi($thispage) {
		global $pdf, $page, $lahetetyyppi, $sivu, $row, $kala, $rectparam, $kukarow, $rivinumerot, $kieli, $laskurow, $yhtiorow, $perheid, $jtid, $summa, $p, $pp, $rivinkorkeus;
			
		// viivat rivien väliin...
		$x[0] = 20;
		$x[1] = 580;
		$y[0] = $y[1] = $kala + $rivinkorkeus - 4;
		
		if (($perheid == 0 or $perheid != $row["perheid"]) and isset($perheid)) {
			$pdf->draw_line($x, $y, $thispage, $rectparam);
		}
				
		//jos on paljon rivejä tehdään uusi otsikko...
		if ($kala <= 200) {
			$sivu++;

			loppu($thispage, 0);

			$page[$sivu] = alku();
			$thispage    = $page[$sivu];
		}
		
		// JT-rivit pikkasen alaspäin omalla otsikolla
		if ($jtid == "0" and $row["jtsort"] != "0") {						
			$kala = $kala-20;
			
			$x[0] = 20;
			$x[1] = 580;
			$y[0] = $y[1] = $kala - 4;
			$pdf->draw_line($x, $y, $page[$sivu], $rectparam);
				
			$pdf->draw_text(25,  $kala, "#",									$thispage, $p);
			$pdf->draw_text(45,  $kala, t("Jälkitoimitukseen", $kieli).":",		$thispage, $p);
			$kala = $kala-$rivinkorkeus;
		}
		
		//Kieliversiooitu tuotteen nimitys
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
		$query = "	SELECT round(if(isnull(hinnasto.hinta) or hinnasto.hinta='', round(tuote.myyntihinta/if('$laskurow[vienti_kurssi]'=0,1,'$laskurow[vienti_kurssi]'),2), hinnasto.hinta) / (1+(if('$yhtiorow[alv_kasittely]'='',0,tuote.alv)/100)), 2) myyntihinta,
       				tuote.aleryhma
                    FROM tuote
                    LEFT JOIN hinnasto on tuote.yhtio = hinnasto.yhtio and tuote.tuoteno = hinnasto.tuoteno and hinnasto.valkoodi = '$laskurow[valkoodi]'
                    WHERE tuote.tuoteno='$row[tuoteno]' and tuote.yhtio='$kukarow[yhtio]'";
        $asresult = mysql_query($query) or pupe_error($query);
		$asrow = mysql_fetch_array($asresult);
	
		//saadaan lähetteen summa menemään oikein, taaksepäin yhteensopivasti
		$row["brutto_rivihinta"] 	= $asrow["myyntihinta"] * ($row["kpl"]+$row["varattu"]+$row["jt"]);
		$row["myyntihinta"] 		= $asrow["myyntihinta"];
		$row["aleryhma"] 			= $asrow["aleryhma"];
		
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
				// lapsia ei lisätä
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
			if ($row["jt"] != 0) { // jälkitoimitus
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
		
		if(substr($lahetetyyppi, 0, 6) != "perhe_" or $piilotarivi == "") {
			if (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete.inc") {
				// tulosta_lahete.inc
				$summa += $row['rivihinta'];
				
				rivi_hinta_ale($thispage);
			}
			elseif (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_brutto.inc") {
				// tulosta_lahete_brutto.inc
				$summa += round($row['ovhhinta'] * $kpl_rivilla, 2);
				
				rivi_brutto($thispage);
			}
			elseif (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_eialeja.inc") {
				//tulosta_lahete_eialeja.inc
				$summa += $row['brutto_rivihinta'];
				
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
		global $pdf, $row, $kala, $rivinumerot, $p, $pp, $rivinkorkeus;
	
		if (trim($row["perhe_kommentti"]) != '') {
			$pohja = $pdf->draw_paragraph($kala+$p["height"], 45, 200, 480, $row["perhe_kommentti"],	$thispage, $p);						
			$kala = $pohja - $rivinkorkeus;
		}
		
		$pdf->draw_text(23,  $kala, $rivinumerot[$row["tunnus"]],							$thispage, $pp);
		$pdf->draw_text(45,  $kala, $row["tuoteno"],										$thispage, $p);
		
		$oikpos = $pdf->strlen($row["tilkpl"]." ".strtolower($row["yksikko"]), $p);
		$pdf->draw_text(365-$oikpos, $kala, $row["tilkpl"]." ".strtolower($row["yksikko"]), $thispage, $p);
				
		$oikpos = $pdf->strlen($row["varattu"], $p);
		$pdf->draw_text(415-$oikpos, $kala, $row["varattu"], 								$thispage, $p);
				
		$oikpos = $pdf->strlen(sprintf('%.2f', $row["hinta"]), $p);
		$pdf->draw_text(465-$oikpos, $kala, sprintf('%.2f', $row["hinta"]), 				$thispage, $p);
				
		$oikpos = $pdf->strlen(($row["ale"]*1)."%", $p);
		$pdf->draw_text(515-$oikpos, $kala, ($row["ale"]*1)."%", 							$thispage, $p);
		
		$oikpos = $pdf->strlen(sprintf('%.2f', $row["rivihinta"]), $p);
		$pdf->draw_text(575-$oikpos, $kala, sprintf('%.2f', $row["rivihinta"]), 			$thispage, $p);
				
		if ($pdf->strlen($row["nimitys"], $p) >= 135) {
			$kala = $kala - $rivinkorkeus;
		}
		
		$pdf->draw_text(190, $kala, $row["nimitys"], 										$thispage, $p);
		
		$kala = $kala - $rivinkorkeus;
		
		if (trim($row["kommentti"]) != '') {
			$pohja = $pdf->draw_paragraph($kala+$p["height"], 45, 200, 580, $row["kommentti"],	$thispage, $p);						
			$kala = $pohja - $rivinkorkeus;
		}
	}
}

if (!function_exists('rivi_brutto')) {
	function rivi_brutto($thispage) {
		global $pdf, $row, $kala, $rivinumerot, $p, $pp, $rivinkorkeus;
		
		$pdf->draw_text(23,  $kala, $rivinumerot[$row["tunnus"]],							$thispage, $pp);
		
		if (trim($row["perhe_kommentti"]) != '') {
			$pohja = $pdf->draw_paragraph($kala+$p["height"], 45, 200, 480, $row["perhe_kommentti"],	$thispage, $p);						
			$kala = $pohja - $rivinkorkeus;
		}
		
		$pdf->draw_text(45,  $kala, $row["tuoteno"],										$thispage, $p);
		
		$oikpos = $pdf->strlen($row["tilkpl"]." ".strtolower($row["yksikko"]), $p);
		$pdf->draw_text(465-$oikpos, $kala, $row["tilkpl"]." ".strtolower($row["yksikko"]), $thispage, $p);
				
		$oikpos = $pdf->strlen($row["varattu"], $p);
		$pdf->draw_text(515-$oikpos, $kala, $row["varattu"], 								$thispage, $p);
			
		$oikpos = $pdf->strlen(sprintf('%.2f', $row["ovhhinta"]), $p);
		$pdf->draw_text(575-$oikpos, $kala, sprintf('%.2f', $row["ovhhinta"]), 				$thispage, $p);
				
		if ($pdf->strlen($row["nimitys"], $p) >= 270) {
			$kala = $kala - $rivinkorkeus;
		}
		$pdf->draw_text(145, $kala, $row["nimitys"], 										$thispage, $p);
	
		$kala = $kala - $rivinkorkeus;
		
		if (trim($row["kommentti"]) != '') {
			$pohja = $pdf->draw_paragraph($kala+$p["height"], 45, 200, 580, $row["kommentti"],	$thispage, $p);						
			$kala = $pohja - $rivinkorkeus;
		}
	}
}

if (!function_exists('rivi_ei_ale_ei_hinta')) {
	function rivi_ei_ale_ei_hinta($thispage) {
		global $pdf, $row, $kala, $rivinumerot, $p, $pp, $rivinkorkeus;
		
		$pdf->draw_text(23,  $kala, $rivinumerot[$row["tunnus"]],							$thispage, $pp);
		
		if (trim($row["perhe_kommentti"]) != '') {
			$pohja = $pdf->draw_paragraph($kala+$p["height"], 45, 200, 480, $row["perhe_kommentti"],	$thispage, $p);						
			$kala = $pohja - $rivinkorkeus;
		}
		
		$pdf->draw_text(45,  $kala, $row["tuoteno"],										$thispage, $p);
		
		
		$oikpos = $pdf->strlen($row["tilkpl"]." ".strtolower($row["yksikko"]), $p);
		$pdf->draw_text(525-$oikpos, $kala, $row["tilkpl"]." ".strtolower($row["yksikko"]), $thispage, $p);
				
		$oikpos = $pdf->strlen($row["varattu"], $p);
		$pdf->draw_text(575-$oikpos, $kala, $row["varattu"], 								$thispage, $p);
				
		if ($pdf->strlen($row["nimitys"], $p) >= 290) {
			$kala = $kala - $rivinkorkeus;
		}
		$pdf->draw_text(190, $kala, $row["nimitys"], 										$thispage, $p);
	
		$kala = $kala - $rivinkorkeus;	
		
		if (trim($row["kommentti"]) != '') {
			$pohja = $pdf->draw_paragraph($kala+$p["height"], 45, 200, 580, $row["kommentti"],	$thispage, $p);						
			$kala = $pohja - $rivinkorkeus;
		}
	}
}

if (!function_exists('rivi_eialeja')) {
	function rivi_eialeja($thispage) {
		global $pdf, $row, $kala, $rivinumerot, $p, $pp, $rivinkorkeus;
	
		$pdf->draw_text(23,  $kala, $rivinumerot[$row["tunnus"]],							$thispage, $pp);
		
		if (trim($row["perhe_kommentti"]) != '') {
			$pohja = $pdf->draw_paragraph($kala+$p["height"], 45, 200, 480, $row["perhe_kommentti"],	$thispage, $p);						
			$kala = $pohja - $rivinkorkeus;
		}
		
		$pdf->draw_text(45,  $kala, $row["tuoteno"],										$thispage, $p);	
		
		$oikpos = $pdf->strlen($row["tilkpl"]." ".strtolower($row["yksikko"]), $p);
		$pdf->draw_text(365-$oikpos, $kala, $row["tilkpl"]." ".strtolower($row["yksikko"]), $thispage, $p);
				
		$oikpos = $pdf->strlen($row["varattu"], $p);
		$pdf->draw_text(415-$oikpos, $kala, $row["varattu"], 								$thispage, $p);
		
		
		$oikpos = $pdf->strlen($row["myyntihinta"], $p);
		$pdf->draw_text(465-$oikpos, $kala, $row["myyntihinta"], 							$thispage, $p);		
		
		$pdf->draw_text(475, $kala, $row["aleryhma"],										$thispage, $p);
		
		$oikpos = $pdf->strlen(sprintf('%.2f', $row["brutto_rivihinta"]), $p);
		$pdf->draw_text(575-$oikpos, $kala, sprintf('%.2f', $row["brutto_rivihinta"]), 		$thispage, $p);
	
		if ($pdf->strlen($row["nimitys"], $p) >= 135) {
			$kala = $kala - $rivinkorkeus;
		}
		$pdf->draw_text(190, $kala, $row["nimitys"], 	$thispage, $p);
	
		$kala = $kala - $rivinkorkeus;
		
		if (trim($row["kommentti"]) != '') {
			$pohja = $pdf->draw_paragraph($kala+$p["height"], 45, 200, 580, $row["kommentti"],	$thispage, $p);						
			$kala = $pohja - $rivinkorkeus;
		}
	}
}

if (!function_exists('rivi_viivakoodi')) {
	function rivi_viivakoodi($thispage) {
		global $pdf, $row, $kala, $rivinumerot, $p, $pp, $rivinkorkeus, $kukarow;
	
		$pdf->draw_text(23,  $kala, $rivinumerot[$row["tunnus"]],								$thispage, $pp);
		
		if (trim($row["perhe_kommentti"]) != '') {
			$pohja = $pdf->draw_paragraph($kala+$p["height"], 45, 200, 480, $row["perhe_kommentti"],	$thispage, $p);						
			$kala = $pohja - $rivinkorkeus;
		}
		
		$pdf->draw_text(45,  $kala, $row["tuoteno"],											$thispage, $p);
		
		$oikpos = $pdf->strlen($row["tilkpl"]." ".strtolower($row["yksikko"]), $p);
		$pdf->draw_text(525-$oikpos, $kala, $row["tilkpl"]." ".strtolower($row["yksikko"]), 	$thispage, $p);
				
		$oikpos = $pdf->strlen($row["varattu"], $p);
		$pdf->draw_text(575-$oikpos, $kala, $row["varattu"], 									$thispage, $p);
		
		if ($pdf->strlen($row["nimitys"], $p) >= 290) {
			$kala = $kala - $rivinkorkeus;
		}
		$pdf->draw_text(190, $kala, $row["nimitys"], 											$thispage, $p);
	
		$kala = $kala - $rivinkorkeus;
		
		if (trim($row["kommentti"]) != '') {
			$pohja = $pdf->draw_paragraph($kala+$p["height"], 45, 200, 580, $row["kommentti"],	$thispage, $p);						
			$kala = $pohja - $rivinkorkeus;
		}
		
		// tutkitaan onko tuotteelle eankoodi
		$eanquery = "	select eankoodi 
						from tuote
						where yhtio='$kukarow[yhtio]' and tuoteno='$row[tuoteno]'";
		$eanres = mysql_query($eanquery) or pupe_error($eanquery);
		$eanrow = mysql_fetch_array($eanres);
				
		if ($eanrow["eankoodi"] != 0) {
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
		global $pdf, $laskurow, $viite, $p, $summa, $rectparam, $kieli, $yhtiorow;
		
		$pdf->draw_text(30, 50, $yhtiorow["nimi"], 		$thispage, $p);
		$pdf->draw_text(30, 40, t("puh. ").$yhtiorow["puhelin"], 	$thispage, $p);
		$pdf->draw_text(30, 30, t("email. ").$yhtiorow["email"], 	$thispage, $p);
		$pdf->draw_text(30, 20, t("fax. ").$yhtiorow["fax"], 		$thispage, $p);
	
		$pdf->draw_paragraph(60,200,40,440, t("Tilausviite", $kieli).": ".$laskurow["viesti"], $thispage, $p);
		$pdf->draw_paragraph(40,200,10,440, t("Kommentti", $kieli).":   ".$laskurow["comments"], $thispage, $p);
		
		if ($tots == 1 and $summa != 0) {	
			$pdf->draw_text(450, 40, t("Yhteensä", $kieli).":", $thispage, $p);
			$pdf->draw_text(500, 40, sprintf("%.2f", $summa)." ".$laskurow["valkoodi"], $thispage, $p);
		}
		
		if ($tots != 1) {
			$pdf->draw_text(530, 23, t("Jatkuu", $kieli)."...", $thispage, $p);
		}
	}
}

if (!function_exists('print_pdf')) {
	function print_pdf ($komento) {
		global $pdf, $kukarow, $yhtiorow, $kateinen, $returnvalue, $kieli, $tee;
	
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
	
		// itse print komento...
		if ($komento == 'email') {
			$liite = $pdffilenimi;
			$kutsu = "Lahete";

			echo t("Lähete tulostuu")."...<br>";
			
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
			echo t("Lähete tulostuu")."...<br>";
			$line = exec("$komento $pdffilenimi", $output, $returnvalue);
		}
		
		//poistetaan tmp file samantien kuleksimasta...
		system("rm -f $pdffilenimi");
	}
}

?>