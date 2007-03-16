<?php

	require_once("../barcode/barcode.php");
	require_once("../barcode/c39object.php");
	require_once("../pdflib/phppdflib.class.php");
	
	$rectparam["width"] = 0.3;
	
	$p["height"] = 10;
	$p["font"] = "Times-Roman";
	
	$p["height"]	= 10;
	$p["font"]		= "Times-Roman";
	$pp["height"]	= 8;
	$pp["font"]		= "Times-Roman";

	// defaultteja
	$sivu = 1;
	
	$pdf = new pdffile;
	$pdf->set_default('margin-top', 	0);
	$pdf->set_default('margin-bottom', 	0);
	$pdf->set_default('margin-left', 	0);
	$pdf->set_default('margin-right', 	0);
		
	// alku ja loppu functiot...
	if (!function_exists('alku')) {
		function alku () {
			global $pdf, $laskurow, $yhtiorow, $kukarow, $sivu, $rectparam, $norm, $p, $pp, $maksuehto, $kieli, $kala, $lask;

			$firstpage = $pdf->new_page("a4");
			
			$kala = 635;
			$lask = 1;
			
			$pdf->draw_rectangle(825, 20,  780, 580, 		$firstpage, $rectparam);
			
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
					$kalaxxx = array();
					$kalaxxx['scale'] = 0.15;
					$placement = $pdf->image_place($image, mm_pt(280), mm_pt(10), $firstpage, $kalaxxx);
				}
			}
			else {
				$pdf->draw_text(30, 815, $yhtiorow["nimi"], 	$firstpage, $p);
				$pdf->draw_text(30, 805, $yhtiorow["osoite"], 	$firstpage, $p);
				$pdf->draw_text(30, 795, $yhtiorow["postino"], 	$firstpage, $p);
				$pdf->draw_text(60, 795, $yhtiorow["postitp"], 	$firstpage, $p);
			}
				
			$pdf->draw_text(250,810, t("Tyˆm‰‰r‰ys", $kieli), $firstpage);
			$pdf->draw_text(250,795, t("Tyˆm‰‰r‰ysnumero", $kieli).": ".$laskurow["tunnus"], $firstpage, $p);
			$pdf->draw_text(390,795, t("Sivu", $kieli).": ".$sivu, $firstpage, $p);
			
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
				$placement = $pdf->image_place($image, 783, 430, $firstpage);

				unset($obj);

				//dellataan tmp filet kuleksimasta
				system("rm -f $nimi1 $nimi2");
			}
			
			$i["height"] = 28;
			$i["font"] = "Times-Roman";
			$i["color"]['red']   = 1;
			$i["color"]['blue']  = 0;
			$i["color"]['green'] = 0;
			
			$pdf->draw_text(400, 795, $laskurow["hyvaksynnanmuutos"], 		$firstpage, $i);	
			
		
			$pdf->draw_rectangle(780, 20,  680, 200, $firstpage, $rectparam);
			$pdf->draw_text(30, 760, t("Toimitusosoite", $kieli).":", 		$firstpage, $p);
			$pdf->draw_text(30, 740, $laskurow["toim_nimi"], 				$firstpage, $p);
			$pdf->draw_text(30, 730, $laskurow["toim_nimitark"], 			$firstpage, $p);
			$pdf->draw_text(30, 720, $laskurow["toim_osoite"], 				$firstpage, $p);
			$pdf->draw_text(30, 710, $laskurow["toim_postino"], 			$firstpage, $p);
			$pdf->draw_text(60, 710, $laskurow["toim_postitp"], 			$firstpage, $p);
			$pdf->draw_text(30, 700, $laskurow["toim_maa"], 				$firstpage, $p);
			$pdf->draw_text(30, 690, $asiakasrow["puhelin"], 					$firstpage, $p);
			
			$pdf->draw_rectangle(780, 200, 680, 380, $firstpage, $rectparam);
			$pdf->draw_text(210, 760, t("Laskutusosoite", $kieli).":", 		$firstpage, $p);
			$pdf->draw_text(210, 740, $laskurow["nimi"], 					$firstpage, $p);
			$pdf->draw_text(210, 730, $laskurow["nimitark"], 				$firstpage, $p);
			$pdf->draw_text(210, 720, $laskurow["osoite"], 					$firstpage, $p);
			$pdf->draw_text(210, 710, $laskurow["postino"], 				$firstpage, $p);
			$pdf->draw_text(240, 710, $laskurow["postitp"], 				$firstpage, $p);
			$pdf->draw_text(210, 700, $laskurow["maa"], 					$firstpage, $p);
		
			// haetaan maksuehdon tiedot
			$query  = "select * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
			$masresult = mysql_query($query) or pupe_error($query);
			$masrow = mysql_fetch_array($masresult);
		
			//maksuehto tekstin‰
			$maksuehto = $masrow["teksti"]." ".$masrow["kassa_teksti"];
			
			$pdf->draw_rectangle(780, 380, 680, 580, $firstpage, $rectparam);
			
			$pdf->draw_text(390, 760, t("Y-tunnus", $kieli).": ".$laskurow["ytunnus"], $firstpage, $p);
			$pdf->draw_text(390, 750, t("Asiakasnumero", $kieli).": ".$asiakasrow["asiakasnro"], $firstpage, $p);
			$pdf->draw_text(390, 740, t("P‰iv‰m‰‰r‰", $kieli).": ".tv1dateconv(substr($laskurow["luontiaika"],0,10)) , $firstpage, $p);
			$pdf->draw_text(390, 730, t("Laatija", $kieli).": ".$laskurow["laatija"] . "@" . $laskurow["luontiaika"], $firstpage, $p);
			$pdf->draw_text(390, 720, t("Toimitustapa", $kieli).": ".$laskurow["toimitustapa"], $firstpage, $p);
			$pdf->draw_text(390, 710, t("Maksuehto", $kieli).": ".$maksuehto, $firstpage, $p);
			$pdf->draw_text(390, 690, t("Tilausviite", $kieli).": ".substr($laskurow["viesti"],0,18), $firstpage, $p);
			
			
			$pdf->draw_rectangle(680, 20,  650, 200, $firstpage, $rectparam);
			
			$pdf->draw_text(210, 670, "Hyv‰ksyn allamainitut tyˆt suoritettavaksi:", $firstpage, $pp);
			
			$pdf->draw_rectangle(680, 200, 650, 580, $firstpage, $rectparam);
			$pdf->draw_text(30,  670, "Vastaanottajan kuittaus:", $firstpage, $pp);
							
			return($firstpage);
		}
	}

	if (!function_exists('tyokommentit')) {
		function tyokommentit($firstpage) {
			global $pdf, $kala, $lask, $rectparam, $p, $pp, $kieli, $laskurow, $kukarow;
			
			if ($laskurow["komm1"] != '') {
				$alkukala = $kala;
				$kommentit = explode("\n",$laskurow["komm1"]);
				$kommentit[] = "\n";
				
				if ($laskurow["rekno"] != "") {
					$kommentit[] = "Rek. no:!!!!$laskurow[rekno]\n";
				}
				if ($laskurow["valmnro"] != "") {
					$kommentit[] = "Sarjanumero:!!!!$laskurow[valmnro]\n";
				}
				if ($laskurow["mittarilukema"] != "") {
					$kommentit[] = "Mittarilukema:!!!!$laskurow[mittarilukema]\n";
				}
				if ($laskurow["merkki"] != "") {
					$query = "	SELECT *
								FROM avainsana
								WHERE yhtio		= '$kukarow[yhtio]' 
								and laji 		= 'sarjanumeron_li' 
								and selite 		= 'MERKKI' 
								and selitetark	= '$laskurow[merkki]'";
					$vresult = mysql_query($query) or pupe_error($query);
				echo "$query<br>";	
					if (mysql_num_rows($vresult) == 1) {
						$vrow=mysql_fetch_array($vresult);
						$kommentit[] = "Merkki ja malli:!!!!$vrow[selitetark_2]\n";
					}
					else {
						$kommentit[] = "Merkki ja malli:!!!!$laskurow[merkki]\n";
					}
				}
				if ($laskurow["mallivari"] != "") {
					$kommentit[] = "Malli/V‰rikoodi:!!!!$laskurow[mallivari]\n";
				}
				if ($laskurow["tuotu"] != "0000-00-00") {
					$kommentit[] = "Tuotu:!!!!".tv1dateconv($laskurow["tuotu"])."\n";
				}
				if ($laskurow["luvattu"] != "0000-00-00") {
					$kommentit[] = "Luvattu:!!!!".tv1dateconv($laskurow["luvattu"])."\n";
				}
				
				
				$pdf->draw_text(20,  $kala, t("Tyˆn kuvaus:", $kieli),		$firstpage,$p);	
				
				$kala = $kala - 15;
				
				foreach($kommentit as $komm) {
					list($komm1, $komm2) = explode("!!!!", $komm); 
					
					$pdf->draw_text(42,  $kala, $komm1, $firstpage,$p);
					$pdf->draw_text(120,  $kala, $komm2, $firstpage,$p);	
					
					$kala = $kala - 10;
				}
				$pdf->draw_rectangle($alkukala-4, 20, $kala+5, 580, $firstpage, $rectparam);
				$kala = $kala - 20;
			}	
		}
	}

	if (!function_exists('tyoriviotsikko')) {
		function tyoriviotsikko($firstpage, $tyyppi) {
			global $pdf, $kala, $lask, $rectparam, $p, $pp, $kieli;
			
			
			//jos on paljon rivej‰ tehd‰‰n uusi otsikko...
			if ($tyyppi == "VV") {
				if ($kala <= 190) {
					$sivu++;
					loppu($firstpage);
					$firstpage = alku();
					$kala = 695;
					$lask = 1;
				}

				$kala = $kala -15;

				$pdf->draw_text(20,  $kala, t("Varaosat ja tarvikkeet:", $kieli),		$firstpage, $p);
			}
			else {	
				$pdf->draw_text(20,  $kala, t("Tyˆn vaiheet:", $kieli),					$firstpage,$p);				
			}
			
			
			$kala = $kala - 5;
			
			$pdf->draw_rectangle($kala, 20,  $kala-20, 40,	$firstpage, $rectparam);
			$pdf->draw_rectangle($kala, 40,  $kala-20, 110,	$firstpage, $rectparam);
			$pdf->draw_rectangle($kala, 110, $kala-20, 320, $firstpage, $rectparam);
			$pdf->draw_rectangle($kala, 320, $kala-20, 420, $firstpage, $rectparam);
			$pdf->draw_rectangle($kala, 370, $kala-20, 420, $firstpage, $rectparam);
			$pdf->draw_rectangle($kala, 420, $kala-20, 470, $firstpage, $rectparam);
			$pdf->draw_rectangle($kala, 470, $kala-20, 520, $firstpage, $rectparam);
			$pdf->draw_rectangle($kala, 520, $kala-20, 580, $firstpage, $rectparam);
		
			$pdf->draw_text(25,  $kala-13, "#",						$firstpage, $p);
			$pdf->draw_text(45,  $kala-13, t("Paikka", $kieli),		$firstpage, $p);
			$pdf->draw_text(120,  $kala-13, t("Tuotenumero / Nimitys", $kieli),$firstpage, $p);
			
			$pdf->draw_text(330, $kala-13, t("Tilattu", $kieli),	$firstpage, $p);
			$pdf->draw_text(375, $kala-13, t("Toimitus", $kieli),	$firstpage, $p);
			$pdf->draw_text(425, $kala-13, t("Yks.hinta", $kieli),	$firstpage, $p);
			$pdf->draw_text(473, $kala-13, t("Alennus", $kieli),	$firstpage, $p);
			$pdf->draw_text(530, $kala-13, t("Rivihinta", $kieli),	$firstpage, $p);
			
			$kala = $kala-30;

			return($firstpage);
		}
	}

	if (!function_exists('rivi')) {
		function rivi($firstpage) {
			global $firstpage, $pdf, $row, $kala, $lask, $total, $rectparam, $p, $pp, $kukarow, $sivu, $rivinumerot, $kieli, $laskurow, $rivino, $edtuotetyyppi;

			//jos on paljon rivej‰ tehd‰‰n uusi otsikko...
			if ($lask > 32) {
				$sivu++;
				loppu($firstpage);
				$firstpage = alku();
				$kala = 635;
				$lask = 1;
			}

			if ($lask == 1 and $row["tuotetyyppi"] == "TT") {
				tyoriviotsikko($firstpage, "TT");
			}			
			if ($lask == 1 and $row["tuotetyyppi"] == "VV" or ($lask>1 and $row["tuotetyyppi"] != $edtuotetyyppi)) {
				tyoriviotsikko($firstpage, "VV");
			}
			
			$rivinkorkeus	= 15;
			$alkukala = $kala;

			$edtuotetyyppi = $row["tuotetyyppi"];			
			$varastopaikka = $row["hyllyalue"] . " " . $row["hyllynro"] . " " . $row["hyllyvali"] . " " . $row["hyllytaso"];
		
			$pdf->draw_text(23,  $kala, $rivino,											$firstpage, $pp);
			$pdf->draw_text(42,  $kala, $varastopaikka,										$firstpage, $p);
			$pdf->draw_text(122,  $kala, $row["tuoteno"],										$firstpage, $p);
			$pdf->draw_text(330, $kala, $row["tilkpl"]." ".strtolower($row["yksikko"]), 	$firstpage, $p);
			
			if ($row["keratty"] != '') {
				$pdf->draw_text(380, $kala, $row['varattu'], 	$firstpage, $p);
			}
			else {	
				if ($row["jt"] != 0) { // j‰lkitoimitus
					$pdf->draw_text(380, $kala, t("JT", $kieli), 	$firstpage, $p);
				}
				elseif ($row["var"] == 'P') { // puute
					$pdf->draw_text(380, $kala, "===", 	$firstpage, $p);
				}
				elseif ($row["var"] == 'H') { // v‰kisin hyv‰ksytty
					$pdf->draw_text(380, $kala, "......", 	$firstpage, $p);
				}
			}
			$pdf->draw_text(430, $kala, $row["hinta"], 		$firstpage, $p);
			$pdf->draw_text(480, $kala, $row["ale"],		$firstpage, $p);
			$pdf->draw_text(530, $kala, $row["rivihinta"], 	$firstpage, $p);
			
			$kala = $kala - $rivinkorkeus;
			$lask++;
			$pdf->draw_text(122, $kala, $row["nimitys"], 	$firstpage, $p);
		
			$kala = $kala - $rivinkorkeus;
			$lask++;	
			
			if (trim($row["kommentti"]) != '') {			
				$pdf->draw_text(122,  $kala, "* ".$row["kommentti"],	$firstpage, $p);
				
				$kala = $kala - $rivinkorkeus;
				$lask++;
			}
			
			$pdf->draw_rectangle($alkukala+10, 20, $kala+$rivinkorkeus-5, 580, $firstpage, $rectparam);
			
			return($firstpage);
		}
	}
	
	if (!function_exists('loppu')) {
		function loppu ($firstpage) {
		
			global $pdf, $laskurow, $yhtiorow, $kukarow, $sivu, $rectparam, $norm, $pieni, $kieli, $masrow, $p, $pp;
															
			$query  = "	SELECT *
						FROM kuka
						WHERE yhtio='$kukarow[yhtio]' and tunnus='$laskurow[myyja]'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 1) {
				$extra_asiakas = mysql_fetch_array($result);
				
				$kukarow["nimi"]  = $extra_asiakas["nimi"];
				$kukarow["puhno"]  = $extra_asiakas["puhno"];
			}
			
			$pdf->draw_rectangle(100, 20, 85, 580, $firstpage, $rectparam);
			$pdf->draw_text(22, 90, "Teit‰ palveli: $kukarow[nimi]   $kukarow[puhno]", $firstpage, $p);
			$pdf->draw_rectangle(100, 290, 85, 580, $firstpage, $rectparam);
			$pdf->draw_text(300, 90, "Tyˆn suorittaja:", $firstpage, $p);
			$pdf->draw_text(380, 90, $laskurow["suorittaja"], $firstpage, $p);
			
			$pdf->draw_rectangle(85, 20, 70, 580, $firstpage, $rectparam);
			$pdf->draw_text(22, 75, "S‰ilyt‰mme tyˆn veloituksetta 7pv:n ajan valmistumisesta. Huomautukset teht‰v‰ 8pv:n kuluessa ajoneuvon vastaanottamisesta", $firstpage, $pp);

			//Alimmat kolme laatikkoa, yhtiˆtietoja
			$pdf->draw_rectangle(70, 20, 20, 580,	$firstpage, $rectparam);
			$pdf->draw_rectangle(70, 207, 20, 580,	$firstpage, $rectparam);
			$pdf->draw_rectangle(70, 394, 20, 580,	$firstpage, $rectparam);
		
			$pdf->draw_text(30, 55, $yhtiorow["nimi"],		$firstpage, $p);
			$pdf->draw_text(30, 45, $yhtiorow["osoite"],	$firstpage, $p);
			$pdf->draw_text(30, 35, $yhtiorow["postino"]."  ".$yhtiorow["postitp"],	$firstpage, $p);
			$pdf->draw_text(30, 25, $yhtiorow["maa"],		$firstpage, $p);
		
			$pdf->draw_text(217, 55, t("Puhelin", $kieli).":",			$firstpage, $pp);
			$pdf->draw_text(257, 55, $yhtiorow["puhelin"],				$firstpage, $p);
			$pdf->draw_text(217, 45, t("Telefax", $kieli).":",			$firstpage, $pp);
			$pdf->draw_text(257, 45, $yhtiorow["fax"],					$firstpage, $p);
			$pdf->draw_text(217, 35, t("S‰hkˆposti", $kieli).":",		$firstpage, $pp);
			$pdf->draw_text(257, 35, $yhtiorow["email"],				$firstpage, $p);
		
			$pdf->draw_text(404, 55, t("Y-tunnus", $kieli).":",			$firstpage, $pp);
			$pdf->draw_text(444, 55, $yhtiorow["ytunnus"],				$firstpage, $p);
			$pdf->draw_text(404, 45, t("Kotipaikka", $kieli).":",		$firstpage, $pp);
			$pdf->draw_text(444, 45, $yhtiorow["kotipaikka"],			$firstpage, $p);
			$pdf->draw_text(404, 35, t("Enn.per.rek", $kieli),			$firstpage, $p);
			$pdf->draw_text(404, 25, t("Alv.rek", $kieli),				$firstpage, $p);
		}
	}
?>