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

$p["height"] = 10;
$p["font"] = "Times-Roman";
$rectparam["width"] = 0.3;

//muutamat funktiot...
if (!function_exists('alku')) {
	function alku () {
		global $pdf, $image, $laskurow, $yhtiorow, $viite, $p, $kala, $lask, $rectparam, $kukarow, $kieli, $asrow;

		//oletukasia
		$kala = 638;
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
				$logoparam['scale'] = 1/($isizelogo[1]*0.35714/15);
								
				$placement = $pdf->image_place($image, mm_pt(277), mm_pt(7), $firstpage, $logoparam);
			}
		}
		else {
			$pdf->draw_text(30, 815, $yhtiorow["nimi"], 	$firstpage, $p);
			$pdf->draw_text(30, 805, $yhtiorow["osoite"], 	$firstpage, $p);
			$pdf->draw_text(30, 795, $yhtiorow["postino"], 	$firstpage, $p);
			$pdf->draw_text(60, 795, $yhtiorow["postitp"], 	$firstpage, $p);
			$pdf->draw_text(30, 785, $yhtiorow["maa"], 		$firstpage, $p);
		}
		
		$pdf->draw_text(250,810, t("Lähete", $kieli), $firstpage);
		$pdf->draw_text(250,795, t("Tilausnumero", $kieli).": ".$laskurow["tunnus"], $firstpage, $p);

		$i["height"] = 28;
		$i["font"] = "Times-Roman";
		$i["color"]['red']   = 1;
		$i["color"]['blue']  = 0;
		$i["color"]['green'] = 0;

		$pdf->draw_text(400, 795, $laskurow["hyvaksynnanmuutos"], $firstpage, $i);


		$pdf->draw_rectangle(780, 20,  680, 200, 		$firstpage, $rectparam);
		$pdf->draw_text(30, 760, t("Toimitusosoite", $kieli).":", 		$firstpage, $p);
		$pdf->draw_text(30, 740, $laskurow["toim_nimi"], 	$firstpage, $p);
		$pdf->draw_text(30, 730, $laskurow["toim_nimitark"], $firstpage, $p);
		$pdf->draw_text(30, 720, $laskurow["toim_osoite"], 	$firstpage, $p);
		$pdf->draw_text(30, 710, $laskurow["toim_postino"], 	$firstpage, $p);
		$pdf->draw_text(60, 710, $laskurow["toim_postitp"], 	$firstpage, $p);
		$pdf->draw_text(30, 700, $laskurow["toim_maa"].$querykiel, 		$firstpage, $p);
	    $pdf->draw_text(30, 690, $asrow["puhelin"], 		$firstpage, $p);

		$pdf->draw_rectangle(780, 200, 680, 380, $firstpage, $rectparam);
		$pdf->draw_text(210, 760, t("Laskutusosoite", $kieli).":", $firstpage, $p);
		$pdf->draw_text(210, 740, $laskurow["nimi"], $firstpage, $p);
		$pdf->draw_text(210, 730, $laskurow["nimitark"], $firstpage, $p);
		$pdf->draw_text(210, 720, $laskurow["osoite"], $firstpage, $p);
		$pdf->draw_text(210, 710, $laskurow["postino"], $firstpage, $p);
		$pdf->draw_text(240, 710, $laskurow["postitp"], $firstpage, $p);
		$pdf->draw_text(210, 700, $laskurow["maa"], $firstpage, $p);

		// haetaan maksuehdon tiedot
		$query  = "select * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
		$masresult = mysql_query($query) or pupe_error($query);
		$masrow = mysql_fetch_array($masresult);

		//maksuehto tekstinä
		$maksuehto = $masrow["teksti"]." ".$masrow["kassa_teksti"];

		$pdf->draw_rectangle(780, 380, 680, 580, $firstpage, $rectparam);
		$pdf->draw_text(390, 760, t("Asiakasnumero", $kieli).": ".$laskurow["ytunnus"], $firstpage, $p);
		$pdf->draw_text(390, 750, t("Päiväys", $kieli).": ".substr($laskurow["lahetepvm"],0,10) , $firstpage, $p);
		$pdf->draw_text(390, 740, t("Laatija", $kieli).": ".$laskurow["laatija"] . "@" . $laskurow["luontiaika"], $firstpage, $p);
		$pdf->draw_text(390, 720, t("Toimitustapa", $kieli).": ".$laskurow["toimitustapa"], $firstpage, $p);
		$pdf->draw_text(390, 710, t("Maksuehto", $kieli).": ".$maksuehto, $firstpage, $p);
		$pdf->draw_text(390, 690, t("Tilausviite", $kieli).": ".substr($laskurow["viesti"],0,18), $firstpage, $p);

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

		$pdf->draw_rectangle(670, 20,  650, 40,  $firstpage, $rectparam);
		$pdf->draw_rectangle(670, 40,  650, 80,  $firstpage, $rectparam);
		$pdf->draw_rectangle(670, 80,  650, 180, $firstpage, $rectparam);
		$pdf->draw_rectangle(670, 180, 650, 320, $firstpage, $rectparam);
		$pdf->draw_rectangle(670, 320, 650, 420, $firstpage, $rectparam);
		$pdf->draw_rectangle(670, 370, 650, 420, $firstpage, $rectparam);
		$pdf->draw_rectangle(670, 420, 650, 470, $firstpage, $rectparam);
		$pdf->draw_rectangle(670, 470, 650, 520, $firstpage, $rectparam);
		$pdf->draw_rectangle(670, 520, 650, 580, $firstpage, $rectparam);

		$pdf->draw_text(25,  657, "#",					$firstpage, $p);
		$pdf->draw_text(45,  657, t("Paikka", $kieli),			$firstpage, $p);
		$pdf->draw_text(90,  657, t("Tuotenumero", $kieli),		$firstpage, $p);
		$pdf->draw_text(190, 657, t("Tuotenimi", $kieli),		$firstpage, $p);
		$pdf->draw_text(330, 657, t("Tilattu", $kieli),			$firstpage, $p);
		$pdf->draw_text(375, 657, t("Toimitus", $kieli),		$firstpage, $p);
		$pdf->draw_text(425, 657, t("Yks.hinta", $kieli),		$firstpage, $p);
		$pdf->draw_text(473, 657, t("Alennus", $kieli),			$firstpage, $p);
		$pdf->draw_text(530, 657, t("Rivihinta", $kieli),		$firstpage, $p);

		$pdf->draw_rectangle(650, 20, 60, 580, $firstpage, $rectparam);

		return($firstpage);
	}
}

if (!function_exists('rivi')) {
	function rivi($firstpage) {
		global $pdf, $row, $kala, $rivinkorkeus, $lask, $rectparam, $kukarow, $rivinumerot, $kieli, $laskurow, $laji, $query, $nimresult, $nimrow, $yhtiorow, $perheid;

		$lisataa = 0;

		if ($row["perheid"] > 0) {

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
								round(sum((tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * tilausrivi.hinta * (1-(tilausrivi.ale/100))),2) rivihinta,
								round(round(sum((tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * tilausrivi.hinta * (1-(tilausrivi.ale/100))),2) / ($row[kpl]+$row[varattu]+$row[jt]), 2) hinta
								FROM tilausrivi
								WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
								and tilausrivi.otunnus in ($perherow[tunnukset])
								and tilausrivi.perheid = '$row[perheid]'";
					$riresult = mysql_query($query) or pupe_error($query);
					$perherow = mysql_fetch_array($riresult);
					
					$row["hinta"] = $perherow["hinta"];
					$row["rivihinta"] = $perherow["rivihinta"];
					$row["ale"] = "";
				}
			}
			else {
				// lapsia ei lisätä
				$lisataa = 1;
			}
		}

		if ($lisataa == 0) {
			
			$p["height"]	= 10;
			$p["font"]		= "Times-Roman";
			$pp["height"]	= 8;
			$pp["font"]		= "Times-Roman";

			$rivinkorkeus	= 15;

			// viivat rivien väliin...
			$x[0] = 20;
			$x[1] = 580;
			$y[0] = $y[1] = $kala + $rivinkorkeus - 4;

			$pdf->draw_line($x, $y, $firstpage, $rectparam);

			//jos on paljon rivejä tehdään uusi otsikko...
			if ($lask > 38) {
				loppu($firstpage, 0);
				$firstpage = alku();
			}

			if (strtolower($kieli) != strtolower($yhtiorow['kieli'])) {
				$laji = 'nimitys_'.strtolower($kieli);
				$query = "SELECT selite FROM tuotteen_avainsanat WHERE yhtio = '$kukarow[yhtio]' and laji = '$laji' and tuoteno = '$row[tuoteno]' LIMIT 1";
				$nimresult = mysql_query($query) or pupe_error($query);
				if (mysql_num_rows($nimresult)>0) {
					$nimrow=mysql_fetch_array($nimresult);
					$row['nimitys']=$nimrow['selite'];
				}
			}

			$varastopaikka = $row["hyllyalue"] . " " . $row["hyllynro"] . " " . $row["hyllyvali"] . " " . $row["hyllytaso"];
			
			$pdf->draw_text(23,  $kala, $rivinumerot[$row["tunnus"]],	$firstpage, $pp);
			$pdf->draw_text(42,  $kala, $varastopaikka,	$firstpage, $p);
			$pdf->draw_text(99,  $kala, $row["tuoteno"],	$firstpage, $p);
			$pdf->draw_text(330, $kala, ($row["tilkpl"]*1)." ".strtolower($row["yksikko"]), 	$firstpage, $p);

			if ($row["keratty"] != '' and !in_array($row["var"], array('J','P'))) {
				$pdf->draw_text(380, $kala, (($row['varattu']+$row['kpl'])*1)." ".strtolower($row["yksikko"]), 	$firstpage, $p);
			}
			else {
				if ($row["jt"] != 0) { // jälkitoimitus
					$pdf->draw_text(380, $kala, t("**JT**", $kieli), 	$firstpage, $p);
				}
				elseif ($row["var"] == 'P') { // puute
					$pdf->draw_text(380, $kala, t("PUUTE", $kieli), 	$firstpage, $p);
				}
				elseif ($row["var"] == 'H') { // väkisin hyväksytty
					$pdf->draw_text(380, $kala, "...........", 	$firstpage, $p);
				}
			}
			
			if ($laskurow['valkoodi'] != $yhtiorow['valkoodi']) {
				$row["hinta"] 	  = round(laskuval($row["hinta"], $laskurow["vienti_kurssi"]),2);
				$row["rivihinta"] = round(laskuval($row["rivihinta"], $laskurow["vienti_kurssi"]),2);
			}
			
			$pdf->draw_text(430, $kala, $row["hinta"], 		$firstpage, $p);
			$pdf->draw_text(480, $kala, $row["ale"],		$firstpage, $p);
			$pdf->draw_text(530, $kala, $row["rivihinta"], 	$firstpage, $p);

			if (strlen($row["nimitys"]) > 22) {
				$kala = $kala - $rivinkorkeus;
				$lask++;
			}
			$pdf->draw_text(190, $kala, $row["nimitys"], 	$firstpage, $p);

			$kala = $kala - $rivinkorkeus;
			$lask++;

			if (trim($row["kommentti"]) != '' or $row["tilaajanrivinro"] != 0) {

				if ($row["tilaajanrivinro"] != 0) {
					$futur = t("Rivinumero", $kieli).": ".$row["tilaajanrivinro"].". ";
				}
				else {
					$futur = "";
				}

				if($pdf->strlen("* $futur".$row["kommentti"], $p)>485) {
					$pdf->draw_paragraph($kala+$p["height"], 90, $kala-$p["height"]-2, 580, "* $futur".$row["kommentti"],	$firstpage, $p);			
					$kala-=$p["height"]+2;
				}
				else {
					$pdf->draw_text(90,  $kala, "* $futur".$row["kommentti"],	$firstpage, $p);
				}				


				$kala = $kala - $rivinkorkeus;
				$lask++;
			}

			// tutkitaan onko tuotteelle asiakaskommentti
			$askomquery = "	select * from asiakaskommentti
							where yhtio='$kukarow[yhtio]' and tuoteno='$row[tuoteno]' and ytunnus='$laskurow[ytunnus]'";
			$askomresul = mysql_query($askomquery) or pupe_error($askomquery);

			// tälle tuotteelle/asiakkaalle on erikoiskommentti
			if (mysql_num_rows($askomresul) == 1) {
				$askomrow = mysql_fetch_array($askomresul);

				// kirjoitetaan kommentti lähetteeseen
				$pdf->draw_text(90,  $kala, "* ".$askomrow["kommentti"],	$firstpage, $p);
				$kala = $kala - $rivinkorkeus;
				$lask++;
			}

			$perheid = $row["perheid"];
		}
		
		return($firstpage);
	}
}

if (!function_exists('loppu')) {
	function loppu ($firstpage, $tots) {
		global $pdf, $laskurow, $viite, $p, $total, $total_netto, $rectparam, $kieli, $yhtiorow;
		
		$pdf->draw_text(30, 50, $yhtiorow["nimi"], 		$firstpage, $p);
		$pdf->draw_text(30, 40, t("puh. ").$yhtiorow["puhelin"], 	$firstpage, $p);
		$pdf->draw_text(30, 30, t("email. ").$yhtiorow["email"], 	$firstpage, $p);
		$pdf->draw_text(30, 20, t("www. ").$yhtiorow["www"], 		$firstpage, $p);
	
		$pdf->draw_paragraph(60,150,40,440, t("Tilausviite", $kieli).": ".$laskurow["viesti"], $firstpage, $p);
		$pdf->draw_paragraph(40,150,10,440, t("Kommentti", $kieli).":   ".$laskurow["comments"], $firstpage, $p);
		
		if ($tots == 1) {
			$arvo = $total + $total_netto;			

			//erikoisalennus annetaan vain riveille joilla EI ole NETTOHINTAA
			if ($laskurow['erikoisale'] != 0) {
				$apu1 = round($laskurow['erikoisale']/100,2);	// erikoisale prosentti
				$apu2 = round($total*$apu1,2); 					// erikoisalen määrä
				$apu3 = round((1-$apu1)*$total,2);				// loppusumma								
				
				$pdf->draw_text(450, 50, t("Erikoisale", $kieli).":", $firstpage, $p);
				$pdf->draw_text(500, 50, sprintf("%01.2f", $apu2)." ".$laskurow["valkoodi"], $firstpage, $p);
			
				$kaikkiyhteensa = $apu3 + $total_netto;
			}
			else {
				$kaikkiyhteensa = $arvo;											
			}
			
			$pdf->draw_text(450, 40, t("Yhteensä", $kieli).":", $firstpage, $p);
			$pdf->draw_text(500, 40, sprintf("%01.2f", $kaikkiyhteensa)." ".$laskurow["valkoodi"], $firstpage, $p);
										
			$total			= 0;
			$total_netto 	= 0;
		}
		else {
			$pdf->draw_text(530, 23, t("Jatkuu", $kieli)."...", $firstpage, $p);
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