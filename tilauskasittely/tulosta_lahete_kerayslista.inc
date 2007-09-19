<?php

//viivakoodien tulostamista varten
define (__TRACE_ENABLED__, false);
define (__DEBUG_ENABLED__, false);

require_once("barcode/barcode.php");
require_once("barcode/c39object.php");
require_once("pdflib/phppdflib.class.php");

$p["height"] 	= 10;
$p["font"] 		= "Times-Roman";

$rectparam["width"] = 0.3;
$sivu = 0;

//muutamat funktiot...
if (!function_exists('alku')) {
	function alku () {
		global $pdf, $image, $laskurow, $yhtiorow, $viite, $p, $kala, $lask, $rectparam, $kukarow, $kieli, $tilausnumeroita, $sivu;

		//oletukasia
		$kala = 638;
		$sivu++;

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
				echo t("Logokuvavirhe");
			}
			else {
				$isizelogo = getimagesize($yhtiorow["lasku_logo"]);
				
				$logoparam = array();
				
				if ($isizelogo[0] > $isizelogo[1] and $isizelogo[1] * (240 / $isizelogo[0]) <= 20) {
					$logoparam['scale'] = 240 / $isizelogo[0];
				}
				else {
					$logoparam['scale'] = 20  / $isizelogo[1];
				}
					
				$placement = $pdf->image_place($image, 830-($logoparam['scale']*$isizelogo[1]), 20, $firstpage, $logoparam);
			}
		}
		else {
			$pdf->draw_text(30, 815, $yhtiorow["nimi"], 	$firstpage, $p);
			$pdf->draw_text(30, 805, $yhtiorow["osoite"], 	$firstpage, $p);
			$pdf->draw_text(30, 795, $yhtiorow["postino"], 	$firstpage, $p);
			$pdf->draw_text(60, 795, $yhtiorow["postitp"], 	$firstpage, $p);
			$pdf->draw_text(30, 785, $yhtiorow["maa"], 		$firstpage, $p);
		}
		
		$pdf->draw_text(310,815, t("Keräilylista", $kieli), 										$firstpage);
		$pdf->draw_text(270,785, t("Toimituspvm", $kieli).": ".tv1dateconv($laskurow["toimaika"]), 	$firstpage, $p);
		$pdf->draw_text(390, 815, t("Sivu", $kieli)." $sivu", 										$firstpage, $p);
		$pdf->draw_text(145,795, t("Tilausnumero(t)", $kieli).": ".$tilausnumeroita, 				$firstpage, $p);
		$pdf->draw_text(145,785, t("Keräyspvm", $kieli).": ".tv1dateconv(substr($laskurow["kerayspvm"],0,10)), 	$firstpage, $p);

		$i["height"] = 28;
		$i["font"] = "Times-Roman";
		$i["color"]['red']   = 1;
		$i["color"]['blue']  = 0;
		$i["color"]['green'] = 0;

		$pdf->draw_text(400, 785, $laskurow["hyvaksynnanmuutos"], $firstpage, $i);

		$pdf->draw_rectangle(780, 20,  680, 200, $firstpage, $rectparam);
		$pdf->draw_text(30, 760, t("Toimitusosoite", $kieli).":", 								$firstpage, $p);
		$pdf->draw_text(30, 740, $laskurow["toim_nimi"], 										$firstpage, $p);
		$pdf->draw_text(30, 730, $laskurow["toim_nimitark"], 									$firstpage, $p);
		$pdf->draw_text(30, 720, $laskurow["toim_osoite"], 										$firstpage, $p);
		$pdf->draw_text(30, 710, $laskurow["toim_postino"]."   ".$laskurow["toim_postitp"], 	$firstpage, $p);
		$pdf->draw_text(30, 700, $laskurow["toim_maa"],			 								$firstpage, $p);
	    $pdf->draw_text(30, 690, $asrow["puhelin"], 											$firstpage, $p);
	    
		$pdf->draw_rectangle(780, 200, 680, 380, $firstpage, $rectparam);
		$pdf->draw_text(210, 760, t("Laskutusosoite", $kieli).":", 								$firstpage, $p);
		$pdf->draw_text(210, 740, $laskurow["nimi"], 											$firstpage, $p);
		$pdf->draw_text(210, 730, $laskurow["nimitark"], 										$firstpage, $p);
		$pdf->draw_text(210, 720, $laskurow["osoite"], 											$firstpage, $p);
		$pdf->draw_text(210, 710, $laskurow["postino"]."   ".$laskurow["postitp"], 				$firstpage, $p);
		$pdf->draw_text(210, 700, $laskurow["maa"], 											$firstpage, $p);

		// haetaan maksuehdon tiedot
		$query  = "select * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
		$masresult = mysql_query($query) or pupe_error($query);
		$masrow = mysql_fetch_array($masresult);

		//maksuehto tekstinä
		$maksuehto = $masrow["teksti"]." ".$masrow["kassa_teksti"];

		$pdf->draw_rectangle(780, 380, 680, 580, $firstpage, $rectparam);
		$pdf->draw_text(390, 760, t("Asiakasnumero", $kieli).": ".$laskurow["ytunnus"], 											$firstpage, $p);
		$pdf->draw_text(390, 750, t("Päiväys", $kieli).": ".tv1dateconv(substr($laskurow["lahetepvm"],0,10)) , 						$firstpage, $p);
		$pdf->draw_text(390, 740, t("Laatija", $kieli).": ".$laskurow["laatija"] . "@" . tv1dateconv($laskurow["luontiaika"], 1), 	$firstpage, $p);
		$pdf->draw_text(390, 720, t("Toimitustapa", $kieli).": ".$laskurow["toimitustapa"], 										$firstpage, $p);
		$pdf->draw_text(390, 710, t("Toimitusehto", $kieli).": ".$laskurow["toimitusehto"], 										$firstpage, $p);
		$pdf->draw_text(390, 700, t("Maksuehto", $kieli).": ".$maksuehto, 															$firstpage, $p);
		$pdf->draw_text(390, 690, t("Tilausviite", $kieli).": ".substr($laskurow["viesti"],0,18), 									$firstpage, $p);
		
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

		$kala = 650;

		$pdf->draw_rectangle(670, 20,  $kala, 36,  $firstpage, $rectparam);
		$pdf->draw_rectangle(670, 36,  $kala, 80,  $firstpage, $rectparam);
		$pdf->draw_rectangle(670, 80,  $kala, 180, $firstpage, $rectparam);
		$pdf->draw_rectangle(670, 180, $kala, 320, $firstpage, $rectparam);
		$pdf->draw_rectangle(670, 320, $kala, 420, $firstpage, $rectparam);
		$pdf->draw_rectangle(670, 420, $kala, 470, $firstpage, $rectparam);
		$pdf->draw_rectangle(670, 470, $kala, 520, $firstpage, $rectparam);
		$pdf->draw_rectangle(670, 520, $kala, 580, $firstpage, $rectparam);

		$pdf->draw_rectangle($kala, 20, 60, 580, $firstpage, $rectparam);

		$kala += 7;

		$pdf->draw_text(25,  $kala, "#",								$firstpage, $p);
		$pdf->draw_text(45,  $kala, t("Paikka", $kieli),				$firstpage, $p);
		$pdf->draw_text(90,  $kala, t("Tuotenumero", $kieli),			$firstpage, $p);
		$pdf->draw_text(190, $kala, t("Tuotenimi", $kieli),				$firstpage, $p);
		$pdf->draw_text(330, $kala, t("Tomittajan tuoteno", $kieli),	$firstpage, $p);
		$pdf->draw_text(425, $kala, t("Hyllyssä", $kieli),				$firstpage, $p);
		$pdf->draw_text(473, $kala, t("Tilattu", $kieli),				$firstpage, $p);
		$pdf->draw_text(530, $kala, t("Toimitus", $kieli),				$firstpage, $p);

		$kala -= 20;

		//tehdään viestejä ja lasketaan niitten pituuksia
		if ($laskurow['sisviesti2'] != '') {

			$pdf->draw_text(35, $kala, t("Huomautukset", $kieli).":", $firstpage, $p);
			$pohja = $pdf->draw_paragraph(638, 35, 60, 550, $laskurow["sisviesti2"]."\n", $firstpage, $p); // top, left, bottom, right

			$laskurow['sisviesti2'] = "";

			if (!is_numeric($pohja)) {
				$laskurow['sisviesti2'] = $pohja;
				loppu($firstpage, 0);
				$firstpage = alku();
			}
			else {
				// viivat rivien väliin...
				$x[0] = 20;
				$x[1] = 580;
				$y[0] = $y[1] = $pohja - 4;
				$pdf->draw_line($x, $y, $firstpage, $rectparam);
				$kala = $pohja - 15;
			}

		}

		return($firstpage);
	}
}

if (!function_exists('rivi')) {
	function rivi($firstpage) {
		global $pdf, $row, $kala, $rivinkorkeus, $lask, $total, $rectparam, $kukarow, $yhtiorow, $rivinumerot, $kieli, $laskurow, $perheid;

		$p["height"]		= 10;
		$p["font"]			= "Times-Roman";
		$pp["height"]		= 8;
		$pp["font"]			= "Times-Roman";
		$boldi["height"] 	= 10;
		$boldi["font"] 		= "Times-Bold";

		$rivinkorkeus	= 15;

		// viivat rivien väliin...
		$x[0] = 20;
		$x[1] = 580;
		$y[0] = $y[1] = $kala + $rivinkorkeus - 4;

		if (($perheid == 0 or $perheid != $row["perheid"]) and isset($perheid)) {
			$pdf->draw_line($x, $y, $firstpage, $rectparam);
		}

		//jos ollaan liian pitkällä tehdään uusi otsikko...
		if ($kala < 75) {
			loppu($firstpage, 0);
			$firstpage = alku();
		}

		if ($row["var"] == "" or  $row["var"] == "H"
			or ($row["var"] == "P" and ($yhtiorow["puute_jt_kerataanko"] == "P" or $yhtiorow["puute_jt_kerataanko"] == "Q"))
			or ($row["var"] == "J" and ($yhtiorow["puute_jt_kerataanko"] == "J" or $yhtiorow["puute_jt_kerataanko"] == "Q"))) {

			$varastopaikka = $row["hyllyalue"] . " " . $row["hyllynro"] . " " . $row["hyllyvali"] . " " . $row["hyllytaso"];

			if ($row["perheid"] > 0 and $perheid != $row["perheid"]) {
				
				$query = "	SELECT vanhatunnus
							FROM lasku use index (PRIMARY)
							WHERE yhtio	= '$kukarow[yhtio]'
							and tunnus	= '$row[otunnus]'";
				$vtunres = mysql_query($query) or pupe_error($query);
				$vtunrow = mysql_fetch_array($vtunres);

				if ($vtunrow["vanhatunnus"] > 0) {
					$query = " 	SELECT GROUP_CONCAT(distinct tunnus SEPARATOR ',') tunnukset
								FROM lasku use index (yhtio_vanhatunnus)
								WHERE yhtio		= '$kukarow[yhtio]'
								and vanhatunnus	= '$vtunrow[vanhatunnus]'";
					$perheresult = mysql_query($query) or pupe_error($query);
					$perherow = mysql_fetch_array($perheresult);
				}
				
				if ($perherow["tunnukset"] != "") {
					$otunlisa = " and tilausrivi.otunnus in ($perherow[tunnukset]) ";
				}
				else {
					$otunlisa = " and tilausrivi.otunnus = '$row[otunnus]' ";
				}

				$query = "	SELECT tuoteno, nimitys
							FROM tilausrivi
							WHERE yhtio = '$kukarow[yhtio]'
							$otunlisa
							and perheid = '$row[perheid]'
							ORDER by tunnus";
				$perheresult = mysql_query($query) or pupe_error($query);

				$litania = '';
				while ($perherow = mysql_fetch_array($perheresult)) {
					if ($litania == "") {
							$litania .= $perherow["tuoteno"]." (".$row["varattu"].") ".$perherow["nimitys"].":  ";
					}
					else {
						$litania .= strtoupper($perherow["tuoteno"]).", ";
					}
				}
				$litania = substr($litania,0,-2);
				
				$pdf->draw_text(42,  $kala, "# ".t("Tuoteperhe", $kieli).": ".$litania,	$firstpage, $p);
				$kala = $kala - $rivinkorkeus;
			}

			$pdf->draw_text(23,  $kala, $rivinumerot[$row["tunnus"]],	$firstpage, $pp);
			$pdf->draw_text(38,  $kala, $varastopaikka,					$firstpage, $p);
			$pdf->draw_text(90,  $kala, $row["tuoteno"],				$firstpage, $p);


			$query = "	SELECT tuote.*, group_concat(distinct tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '/') toim_tuoteno
						FROM tuote
						LEFT JOIN tuotteen_toimittajat ON tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno
						WHERE tuote.yhtio='$kukarow[yhtio]' and tuote.tuoteno = '$row[tuoteno]'
						GROUP BY tuote.tuoteno";
			$tresult = mysql_query($query) or pupe_error($query);
			$trow	 = mysql_fetch_array($tresult);
			
			$pdf->draw_text(330, $kala, $trow["toim_tuoteno"], 	$firstpage, $p);

			
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

						list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($saldorow["tuoteno"], '', '', '', $saldorow["hyllyalue"], $saldorow["hyllynro"], $saldorow["hyllyvali"], $saldorow["hyllytaso"], '', '', $saldorow["era"]);

						if ($saldorow['hyllyalue'] == $row['hyllyalue'] and $saldorow['hyllynro'] == $row['hyllynro'] and $saldorow['hyllyvali'] == $row['hyllyvali'] and $saldorow['hyllytaso'] == $row['hyllytaso']){
							$rivipaikkahyllyssa  += $hyllyssa;
						}
						
						$rivivarastohyllyssa += $hyllyssa;
					}
				}

				if ($rivipaikkahyllyssa != $rivivarastohyllyssa) {
					$oikpos = $pdf->strlen($rivipaikkahyllyssa." (".$rivivarastohyllyssa.")", $p);					
					$pdf->draw_text(465-$oikpos, $kala, $rivipaikkahyllyssa." (".$rivivarastohyllyssa.")",	$firstpage, $p);
				}
				else {
					$oikpos = $pdf->strlen($rivipaikkahyllyssa, $p);					
					$pdf->draw_text(465-$oikpos, $kala, $rivipaikkahyllyssa,	$firstpage, $p);					
				}
			}
			
			$oikpos = $pdf->strlen(($row["tilkpl"]*1)." ".strtolower($row["yksikko"]), $boldi);
			$pdf->draw_text(515-$oikpos, $kala, ($row["tilkpl"]*1)." ".strtolower($row["yksikko"]), 	$firstpage, $boldi);

			if ($row["keratty"] != '' and !in_array($row["var"], array('J','P'))) {
				$oikpos = $pdf->strlen($row['varattu'], $p);
				$pdf->draw_text(575-$oikpos, $kala, $row['varattu'], 	$firstpage, $p);
			}
			else {
				if ($row["jt"] != 0) { // jälkitoimitus
					$oikpos = $pdf->strlen(t("**JT**", $kieli), $p);
					$pdf->draw_text(575-$oikpos, $kala, t("**JT**", $kieli), 	$firstpage, $p);
				}
				elseif ($row["var"] == 'P') { // puute
					$oikpos = $pdf->strlen(t("PUUTE", $kieli), $p);
					$pdf->draw_text(575-$oikpos, $kala, t("PUUTE", $kieli), 	$firstpage, $p);
				}
				elseif ($row["var"] == 'H') { // väkisin hyväksytty
					$oikpos = $pdf->strlen("...........", $p);
					$pdf->draw_text(575-$oikpos, $kala, "...........", 	$firstpage, $p);
				}
			}

			if (strlen(asana('nimitys_',$row['tuoteno'],$row['nimitys'])) > 22) {
				$kala = $kala - $rivinkorkeus;
			}
			$pdf->draw_text(190, $kala, asana('nimitys_',$row['tuoteno'],$row['nimitys']), 	$firstpage, $p);

			$kala = $kala - $rivinkorkeus;
			
			if ($row["sarjanumeroseuranta"] != "") {
				$query	= "	SELECT distinct sarjanumero, parasta_ennen
		   					FROM sarjanumeroseuranta
		   					WHERE yhtio = '$kukarow[yhtio]'
							and tuoteno = '$row[tuoteno]'
		   					and myyntirivitunnus = '$row[tunnus]'
		   					LIMIT 1";
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
			}

			$perheid = $row["perheid"];
		}
		return($firstpage);
	}
}

if (!function_exists('loppu')) {
	function loppu ($firstpage, $tots) {
		global $pdf, $laskurow, $viite, $p, $total, $rectparam, $kieli;

		$pdf->draw_rectangle(50, 20, 20, 580, $firstpage, $rectparam);

		$pdf->draw_text(30, 37,  t("Tilausviite", $kieli).": ".$laskurow["viesti"], $firstpage, $p);
		$pdf->draw_text(30, 25,  t("Kommentti", $kieli).":   ".$laskurow["comments"], $firstpage, $p);

		if ($tots != 1) {
			$pdf->draw_text(530, 23, t("Jatkuu", $kieli)."...", $firstpage, $p);
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
		$pdffilenimi = "/tmp/Lahete-".md5(uniqid(mt_rand(), true)).".pdf";

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
