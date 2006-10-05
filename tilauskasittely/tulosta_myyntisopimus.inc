<?php
				
	//Fontit
	$seiska["height"] 			= 7;
	$seiska["font"] 			= "Times-Roman";
	
	$kymppi["height"] 			= 10;
	$kymppi["font"] 			= "Times-Roman";
	
	$kymppi_courier["height"] 	= 10;
	$kymppi_courier["font"] 	= "Courier";
	
	$kymppi_helvetica["height"] = 10;
	$kymppi_helvetica["font"] 	= "Helvetica";
	
	$kasi_helvetica["height"] 	= 8;
	$kasi_helvetica["font"] 	= "Helvetica";
	
	$rectparam["width"]			= 0.3;
	
	
	//Haetaan pdflibrary
	require_once('../pdflib/phppdflib.class.php');
	
	//muutamat funktiot...
	if (!function_exists('alku_myyntisopimus')) {
		function alku_myyntisopimus ($laskurow, $sivu, $kieli, $pdf_myyntisopimus = "") {
			global $yhtiorow, $kukarow, $kasi_helvetica, $kymppi_helvetica, $seiska;
													
			$pdf_myyntisopimus = new pdffile;
	
			$pdf_myyntisopimus->set_default('margin-top', 20);
			$pdf_myyntisopimus->set_default('margin-bottom', 0);
			$pdf_myyntisopimus->set_default('margin-left', 0);
			$pdf_myyntisopimus->set_default('margin-right', 0);
									
			$pdf_myyntisopimus->enable('import');				
			
			// Haetaan pohja
			ob_start();

			$filename = "/var/www/trunk/pupesoft/myyntisopimus.pdf";
			
			$handle = fopen($filename, "r");
			$d = fread($handle, filesize($filename));
			fclose($handle);
		
			$pdf_myyntisopimus->import->append($d);
		
			$pdf_myyntisopimus_sivu = $pdf_myyntisopimus->import->get_pages();
																																									
			ob_end_clean();
											
			/*
			//Tulostetaan viivotin
			for ($i = 10; $i < 291; $i++) {
				$x[0] = mm_pt(20);
				$y[0] = mm_pt($i);
				$x[1] = mm_pt(30);
				$y[1] = mm_pt($i);
				
				$param["width"] = 0.1;
				
				$pdf_myyntisopimus->draw_line($x, $y, $pdf_myyntisopimus_sivu[0], $param);
				
				if ($i % 10 == 0) {					
					$param["width"] = 0.8;				
					$pdf_myyntisopimus->draw_line($x, $y, $pdf_myyntisopimus_sivu[0], $param);				
					$pdf_myyntisopimus->draw_text(mm_pt(32),  mm_pt($i), $i, $pdf_myyntisopimus_sivu[0], $kasi_helvetica);	
				}
				else {
					$param["width"] = 0.2;				
					$pdf_myyntisopimus->draw_line($x, $y, $pdf_myyntisopimus_sivu[0], $param);
				}								
			}
			*/
									
			if (trim($yhtiorow["lasku_logo"]) != '' and file_exists($yhtiorow["lasku_logo"])) {
				$filename = $yhtiorow["lasku_logo"];
				
				$fh = fopen($filename, "r");
				$data = fread($fh, filesize($filename));
				fclose($fh);
							
				$image = $pdf_myyntisopimus->jfif_embed($data);
				
				if(!$image) {
					echo t("Logokuvavirhe").": ".$image.$data;
				}
				else {
					$placement = $pdf_myyntisopimus->image_place($image, mm_pt(280), mm_pt(10), $pdf_myyntisopimus_sivu[0]);
				}
			}
			
			$squery = "	SELECT *
						FROM asiakas
						WHERE tunnus = '$laskurow[liitostunnus]'";				
			$sresult = mysql_query($squery) or pupe_error($squery);
			$asiakasrow = mysql_fetch_array($sresult);
																							
			$pp = substr($laskurow["luontiaika"],8,2);
			$kk = substr($laskurow["luontiaika"],5,2);
			$vv = substr($laskurow["luontiaika"],0,4);
			
			$voimassa = date("d.m.Y",mktime(0, 0, 0, $kk, $pp+14, $vv));
			$luotoaik = date("d.m.Y",mktime(0, 0, 0, $kk, $pp, $vv));
						
			$pdf_myyntisopimus->draw_text(mm_pt(110),  mm_pt(280), t("MYYNTISOPIMUS"),							$pdf_myyntisopimus_sivu[0]);
			$pdf_myyntisopimus->draw_text(mm_pt(75),   mm_pt(280), t("Pvm").": $luotoaik",						$pdf_myyntisopimus_sivu[0], $kasi_helvetica);
			$pdf_myyntisopimus->draw_text(mm_pt(160),  mm_pt(284), t("Numero"),									$pdf_myyntisopimus_sivu[0], $kasi_helvetica);
			$pdf_myyntisopimus->draw_text(mm_pt(170),  mm_pt(280), $laskurow["tunnus"],							$pdf_myyntisopimus_sivu[0]);
			$pdf_myyntisopimus->draw_text(mm_pt(110),  mm_pt(275), t("Tämän sopimuksen ehdot on annettu ostajalle erillisenä liitteenä"),	$pdf_myyntisopimus_sivu[0], $seiska);
			
			
			$pdf_myyntisopimus->draw_text(mm_pt(10),   mm_pt(275), t("MYYJÄ"),									$pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
			$pdf_myyntisopimus->draw_text(mm_pt(11),   mm_pt(272), t("Nimi"),									$pdf_myyntisopimus_sivu[0], $seiska);
			$pdf_myyntisopimus->draw_text(mm_pt(77),   mm_pt(272), t("Y-tunnus"),								$pdf_myyntisopimus_sivu[0], $seiska);
			$pdf_myyntisopimus->draw_text(mm_pt(111),  mm_pt(272), t("Puhelin"),								$pdf_myyntisopimus_sivu[0], $seiska);
			$pdf_myyntisopimus->draw_text(mm_pt(153),  mm_pt(272), t("Pankkiyhteys"),							$pdf_myyntisopimus_sivu[0], $seiska);
			$pdf_myyntisopimus->draw_text(mm_pt(12),   mm_pt(267.5), $yhtiorow["nimi"], 						$pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
			$pdf_myyntisopimus->draw_text(mm_pt(78),   mm_pt(267.5), $yhtiorow["ytunnus"], 						$pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
			$pdf_myyntisopimus->draw_text(mm_pt(112),  mm_pt(267.5), $yhtiorow["puhelin"], 						$pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
			$pdf_myyntisopimus->draw_text(mm_pt(154),  mm_pt(267.5), $yhtiorow["pankkinimi1"]." ".$yhtiorow["pankkitili1"],	$pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
			
			
			$pdf_myyntisopimus->draw_text(mm_pt(11),   mm_pt(264), t("Lähiosoite"),								$pdf_myyntisopimus_sivu[0], $seiska);
			$pdf_myyntisopimus->draw_text(mm_pt(77),   mm_pt(264), t("Postinumero"),							$pdf_myyntisopimus_sivu[0], $seiska);
			$pdf_myyntisopimus->draw_text(mm_pt(111),  mm_pt(264), t("Postitoimipaikka"),						$pdf_myyntisopimus_sivu[0], $seiska);
			$pdf_myyntisopimus->draw_text(mm_pt(153),  mm_pt(264), t("Henkilön nimi"),							$pdf_myyntisopimus_sivu[0], $seiska);
			$pdf_myyntisopimus->draw_text(mm_pt(12),   mm_pt(260.5), $yhtiorow["osoite"], 						$pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
			$pdf_myyntisopimus->draw_text(mm_pt(78),   mm_pt(260.5), $yhtiorow["postino"], 						$pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
			$pdf_myyntisopimus->draw_text(mm_pt(112),  mm_pt(260.5), $yhtiorow["postitp"], 						$pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
			$pdf_myyntisopimus->draw_text(mm_pt(154),  mm_pt(260.5), $kukarow["nimi"],							$pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
			
						
			$pdf_myyntisopimus->draw_text(mm_pt(10),   mm_pt(254), t("VÄLITTÄJÄ"),								$pdf_myyntisopimus_sivu[0], $kymppi_helvetica);			
			$pdf_myyntisopimus->draw_text(mm_pt(12),   mm_pt(250), t("Nimi"),									$pdf_myyntisopimus_sivu[0], $seiska);
			$pdf_myyntisopimus->draw_text(mm_pt(79),   mm_pt(250), t("Lähiosoite"),								$pdf_myyntisopimus_sivu[0], $seiska);
			$pdf_myyntisopimus->draw_text(mm_pt(147),  mm_pt(250), t("Puhelin"),								$pdf_myyntisopimus_sivu[0], $seiska);			
			$pdf_myyntisopimus->draw_text(mm_pt(12),   mm_pt(246), $yhtiorow[""],								$pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
			$pdf_myyntisopimus->draw_text(mm_pt(79),   mm_pt(246), $yhtiorow[""], 								$pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
			$pdf_myyntisopimus->draw_text(mm_pt(147),  mm_pt(246), $yhtiorow[""], 								$pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
			
			
			$pdf_myyntisopimus->draw_text(mm_pt(10),   mm_pt(241.5), t("OSTAJA (Puolison tiedot merkitään vain jos yhteisvastuullinen)"),	$pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
												
			$pdf_myyntisopimus->draw_text(mm_pt(11),   mm_pt(239), t("Sukunimi ja puhutteluetunimi"),			$pdf_myyntisopimus_sivu[0], $seiska);
			$pdf_myyntisopimus->draw_text(mm_pt(61),   mm_pt(239), t("Muut etunimet"),							$pdf_myyntisopimus_sivu[0], $seiska);
			$pdf_myyntisopimus->draw_text(mm_pt(111),  mm_pt(239), t("Tarkastettu henkilötunnus"),				$pdf_myyntisopimus_sivu[0], $seiska);
			$pdf_myyntisopimus->draw_text(mm_pt(153),  mm_pt(239), t("Arvo tai ammatti"),						$pdf_myyntisopimus_sivu[0], $seiska);
			$pdf_myyntisopimus->draw_text(mm_pt(12),   mm_pt(234.5), $laskurow["nimi"], 						$pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
			$pdf_myyntisopimus->draw_text(mm_pt(62),   mm_pt(234.5), $laskurow["nimitark"], 					$pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
			$pdf_myyntisopimus->draw_text(mm_pt(112),  mm_pt(234.5), $laskurow["ovttunnus"],					$pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
			$pdf_myyntisopimus->draw_text(mm_pt(154),  mm_pt(234.5), $laskurow[""],								$pdf_myyntisopimus_sivu[0], $kymppi_helvetica);						
																		
																		
			$pdf_myyntisopimus->draw_text(mm_pt(11),   mm_pt(231.5), t("Lähiosoite"),							$pdf_myyntisopimus_sivu[0], $seiska);
			$pdf_myyntisopimus->draw_text(mm_pt(61),   mm_pt(231.5), t("Postinumero"),							$pdf_myyntisopimus_sivu[0], $seiska);
			$pdf_myyntisopimus->draw_text(mm_pt(111),  mm_pt(231.5), t("Postitoimipaikka"),						$pdf_myyntisopimus_sivu[0], $seiska);
			$pdf_myyntisopimus->draw_text(mm_pt(153),  mm_pt(231.5), t("Pankkiyhteys"),							$pdf_myyntisopimus_sivu[0], $seiska);
			$pdf_myyntisopimus->draw_text(mm_pt(12),   mm_pt(227.5), $laskurow["osoite"], 						$pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
			$pdf_myyntisopimus->draw_text(mm_pt(62),   mm_pt(227.5), $laskurow["postino"], 						$pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
			$pdf_myyntisopimus->draw_text(mm_pt(112),  mm_pt(227.5), $laskurow["postitp"], 						$pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
			$pdf_myyntisopimus->draw_text(mm_pt(154),  mm_pt(227.5), $laskurow["osoite"],						$pdf_myyntisopimus_sivu[0], $kymppi_helvetica);	
																		
																		
			$pdf_myyntisopimus->draw_text(mm_pt(11),   mm_pt(224.5), t("Kotipuhelin"),							$pdf_myyntisopimus_sivu[0], $seiska);
			$pdf_myyntisopimus->draw_text(mm_pt(41),   mm_pt(224.5), t("Matkapuhelin"),							$pdf_myyntisopimus_sivu[0], $seiska);
			$pdf_myyntisopimus->draw_text(mm_pt(75),   mm_pt(224.5), t("Sähköposti"),							$pdf_myyntisopimus_sivu[0], $seiska);
			$pdf_myyntisopimus->draw_text(mm_pt(111),  mm_pt(224.5), t("Työnantaja"),							$pdf_myyntisopimus_sivu[0], $seiska);
			$pdf_myyntisopimus->draw_text(mm_pt(12),   mm_pt(220), $asiakasrow["puhelin"], 						$pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
			$pdf_myyntisopimus->draw_text(mm_pt(42),   mm_pt(220), $asiakasrow["fax"], 							$pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
			$pdf_myyntisopimus->draw_text(mm_pt(76),   mm_pt(220), $asiakasrow["email"], 						$pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
			$pdf_myyntisopimus->draw_text(mm_pt(112),  mm_pt(220), $asiakasrow[""],								$pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
																					
			
			$pdf_myyntisopimus->draw_text(mm_pt(11),   mm_pt(217), t("Toisen ostajan sukunimi ja puhutteluetunimi"),	$pdf_myyntisopimus_sivu[0], $seiska);
			$pdf_myyntisopimus->draw_text(mm_pt(61),   mm_pt(217), t("Muut etunimet"),							$pdf_myyntisopimus_sivu[0], $seiska);
			$pdf_myyntisopimus->draw_text(mm_pt(111),  mm_pt(217), t("Tarkastettu henkilötunnus"),				$pdf_myyntisopimus_sivu[0], $seiska);
			$pdf_myyntisopimus->draw_text(mm_pt(153),  mm_pt(217), t("Arvo tai ammatti"),						$pdf_myyntisopimus_sivu[0], $seiska);
			$pdf_myyntisopimus->draw_text(mm_pt(12),   mm_pt(213), $laskurow[""], 								$pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
			$pdf_myyntisopimus->draw_text(mm_pt(62),   mm_pt(213), $laskurow[""], 								$pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
			$pdf_myyntisopimus->draw_text(mm_pt(112),  mm_pt(213), $laskurow[""], 								$pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
			$pdf_myyntisopimus->draw_text(mm_pt(154),  mm_pt(213), $laskurow[""],								$pdf_myyntisopimus_sivu[0], $kymppi_helvetica);			
																					
																		
			
			$pdf_myyntisopimus->draw_text(mm_pt(10.3), mm_pt(206), t("KAUPAN"),									$pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
			$pdf_myyntisopimus->draw_text(mm_pt(10.3), mm_pt(202), t("KOHDE"),									$pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
			
			$pdf_myyntisopimus->draw_text(mm_pt(10.3), mm_pt(112), t("VAIHTO"),									$pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
			$pdf_myyntisopimus->draw_text(mm_pt(10.3), mm_pt(108), t("KOHDE"),									$pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
			
			$pdf_myyntisopimus->draw_text(mm_pt(129),  mm_pt(118.5), t("Hinta yhteensä"),						$pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
			$pdf_myyntisopimus->draw_text(mm_pt(129),  mm_pt(81), t("Hyvitysinta yhteensä"),					$pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
			$pdf_myyntisopimus->draw_text(mm_pt(129),  mm_pt(73), t("Ostajan maksettavaksi jää"),				$pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
			
			
			$pdf_myyntisopimus->draw_text(mm_pt(10),   mm_pt(70), t("MAKSUN SUORITUS"),							$pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
			$pdf_myyntisopimus->draw_text(mm_pt(11),   mm_pt(66), t("Tilattaessa"),								$pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
			$pdf_myyntisopimus->draw_text(mm_pt(130),  mm_pt(66), t("Luovutettaessa suoritus"),					$pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
			$pdf_myyntisopimus->draw_text(mm_pt(130),  mm_pt(62), t("Luovutettaessa rahoitus"),					$pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
			$pdf_myyntisopimus->draw_text(mm_pt(130),  mm_pt(57.5), t("Luovutettaessa yhteensä"),				$pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
			
			
			
			$pdf_myyntisopimus->draw_text(mm_pt(10),   mm_pt(51), t("ALLEKIRJOITUKSET"),						$pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
			$pdf_myyntisopimus->draw_text(mm_pt(11),   mm_pt(48.5), t("Myyjä myy ja luovuttaa ostajalle yllämainitut tavarat tähän sopimukseen perustuvin ehdoin."), $pdf_myyntisopimus_sivu[0], $seiska);
			$pdf_myyntisopimus->draw_text(mm_pt(11),   mm_pt(46), t("Ostaja vakuuttaa, että hänen vaihtokohteestaan antamat tiedot ovat paikkansapitäviä, että se on täysin maksettu ja, että se on kokonaan hänen omaisuuttaan."), $pdf_myyntisopimus_sivu[0], $seiska);
			$pdf_myyntisopimus->draw_text(mm_pt(11),   mm_pt(42.5), t("Allekirjoituksellaan ostaja kuittaa vastaanottaneensa liitteenä tämän sopimuksen ehdot,"), $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
			$pdf_myyntisopimus->draw_text(mm_pt(11),   mm_pt(39), t("tutustuneensa niihin ja hyväksyvänsä sopimuksen täysin."), $pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
			$pdf_myyntisopimus->draw_text(mm_pt(11),   mm_pt(35), t("Paikka ja aika"),							$pdf_myyntisopimus_sivu[0], $seiska);
			$pdf_myyntisopimus->draw_text(mm_pt(11),   mm_pt(28), t("Ostajan allekirjoitus"),					$pdf_myyntisopimus_sivu[0], $seiska);
			$pdf_myyntisopimus->draw_text(mm_pt(71),   mm_pt(28), t("Toisen ostajan allekirjoitus"),			$pdf_myyntisopimus_sivu[0], $seiska);
			$pdf_myyntisopimus->draw_text(mm_pt(131),  mm_pt(35), t("Myyjän nimi ja allekirjoitus"),			$pdf_myyntisopimus_sivu[0], $seiska);
			
			$pdf_myyntisopimus->draw_text(mm_pt(11),   mm_pt(12.5), t("Omistusoikeus kaupan kohteeseen ei siirry ostajalle ennen kuin kauppahinta on maksettu ja muut tämän sopimuksen ostajan maksuvelvoitteet on täysin täytetty."), $pdf_myyntisopimus_sivu[0], $seiska);
			
			//Palautetan luotu pdf ja sivu
			return array ($pdf_myyntisopimus, $pdf_myyntisopimus_sivu[0]);						
		}
	}
	
	if (!function_exists('rivi_myyntisopimus')) {
		function rivi_myyntisopimus($pdf_myyntisopimus, $laskurow, $kala, $kieli, $page_myyntisopimus, $selite, $row) {
			global $yhtiorow, $kukarow, $kasi_helvetica, $kymppi_helvetica, $seiska, $kymppi_courier;
		
			$rivinkorkeus	= 7.1;
								
			$pdf_myyntisopimus->draw_text(mm_pt(26), mm_pt($kala+4), $selite, 								$page_myyntisopimus, $seiska);
			$pdf_myyntisopimus->draw_text(mm_pt(28), mm_pt($kala), $row["nimitys"], 						$page_myyntisopimus, $kymppi_helvetica);
			//$pdf_myyntisopimus->draw_text(mm_pt(158), mm_pt($kala), $row["ale"]."%", 						$page_myyntisopimus, $kymppi_courier);
			$pdf_myyntisopimus->draw_text(mm_pt(175), mm_pt($kala), sprintf('%10s', $row["rivihinta"]),		$page_myyntisopimus, $kymppi_courier);
			
			$kala = $kala - $rivinkorkeus;
						
			return array ($page_myyntisopimus, $kala);
		}
	}
	
	
	if (!function_exists('loppu_myyntisopimus')) {
		function loppu_myyntisopimus ($pdf_myyntisopimus, $laskurow, $kieli, $page_myyntisopimus, $tots, $total_uudet=0, $total_netto_uudet=0, $total_vanhat=0, $total_netto_vanhat=0, $tilattaessa=0, $toimitettaessa=0) {
			global $yhtiorow, $kukarow, $kasi_helvetica, $kymppi_helvetica, $seiska, $kymppi_courier;
					
			if ($tots == 1) {
				
				/*
				$arvo = $total + $total_netto;			
																
				//erikoisalennus annetaan vain riveille joilla EI ole NETTOHINTAA
				if ($laskurow['erikoisale'] != 0) {
					$apu1 = (float) $laskurow['erikoisale']/100;	// erikoisale prosentti
					$apu2 = round($total*$apu1,2); 					// erikoisalen määrä
					$apu3 = round((1-$apu1)*$total,2);				// loppusumma								
					
					$pdf_myyntisopimus->draw_text(450, 37, t("Erikoisale", $kieli).":", $page_myyntisopimus, $p);
					$pdf_myyntisopimus->draw_text(500, 37, sprintf("%01.2f", $apu2)." ".$laskurow["valkoodi"], $page_myyntisopimus, $p);
				
					$kaikkiyhteensa = $apu3 + $total_netto;
				}
				else {
					$kaikkiyhteensa = $arvo;											
				}
				*/
													
				$uudet = $total_uudet + $total_netto_uudet;
				$vanha = $total_vanhat + $total_netto_vanhat;
				
				$arvo  = $uudet + $vanha;
												
				$pdf_myyntisopimus->draw_text(mm_pt(175), mm_pt(118.5), sprintf('%10s', sprintf("%01.2f", $uudet)), $page_myyntisopimus, $kymppi_courier);
				$pdf_myyntisopimus->draw_text(mm_pt(165), mm_pt(118.5), $laskurow["valkoodi"], $page_myyntisopimus, $kymppi_helvetica);
											
				$pdf_myyntisopimus->draw_text(mm_pt(175), mm_pt(81), sprintf('%10s', sprintf("%01.2f", $vanha)), $page_myyntisopimus, $kymppi_courier);
				$pdf_myyntisopimus->draw_text(mm_pt(165), mm_pt(81), $laskurow["valkoodi"], $page_myyntisopimus, $kymppi_helvetica);
											
				$pdf_myyntisopimus->draw_text(mm_pt(175), mm_pt(73), sprintf('%10s', sprintf("%01.2f", $arvo)), $page_myyntisopimus, $kymppi_courier);															
			
			
				if($tilattaessa != 0) {
					$pdf_myyntisopimus->draw_text(mm_pt(32), mm_pt(66), $laskurow["valkoodi"], $page_myyntisopimus, $kymppi_helvetica);
					$pdf_myyntisopimus->draw_text(mm_pt(38), mm_pt(66), sprintf('%10s', sprintf("%01.2f", $tilattaessa)), $page_myyntisopimus, $kymppi_courier);
				}
			
				$toimi = $arvo + $toimitettaessa;
				
				$pdf_myyntisopimus->draw_text(mm_pt(175), mm_pt(66), sprintf('%10s', sprintf("%01.2f", $toimi)), $page_myyntisopimus, $kymppi_courier);
				$pdf_myyntisopimus->draw_text(mm_pt(175), mm_pt(57.5), sprintf('%10s', sprintf("%01.2f", $toimi)), $page_myyntisopimus, $kymppi_courier);
			}
			else {
				$pdf_myyntisopimus->draw_text(530, 23, t("Jatkuu", $kieli)."...", $page_myyntisopimus, $p);
			}
		}
	}
	
	
	if (!function_exists('ehdot_myyntisopimus')) {
		function ehdot_myyntisopimus ($pdf_myyntisopimus, $laskurow, $kieli) {
			global $yhtiorow, $kukarow, $kasi_helvetica, $kymppi_helvetica, $seiska;
			
			// Haetaan myyntiehdot
			ob_start();

			$filename = "/var/www/trunk/pupesoft/myyntisopimus_ehdot.pdf";
			
			$handle = fopen($filename, "r");
			$d = fread($handle, filesize($filename));
			fclose($handle);
		
			$pdf_myyntisopimus->import->append($d);
		
			$pdf_myyntisopimus_sivu = $pdf_myyntisopimus->import->get_pages();
																																									
			ob_end_clean();
			
			$pdf_myyntisopimus->draw_text(mm_pt(88),  mm_pt(281), $laskurow["luontiaika"],		$pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
			$pdf_myyntisopimus->draw_text(mm_pt(110), mm_pt(271), $yhtiorow["nimi"],			$pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
			$pdf_myyntisopimus->draw_text(mm_pt(38),  mm_pt(271), $laskurow["nimi"],			$pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
			$pdf_myyntisopimus->draw_text(mm_pt(136), mm_pt(265), $laskurow["tunnus"],			$pdf_myyntisopimus_sivu[0], $kymppi_helvetica);
			
		}
	}

	
	if (!function_exists('print_pdf_myyntisopimus')) {
		function print_pdf_myyntisopimus ($pdf_myyntisopimus, $komento, $tee = "") {
			global $yhtiorow, $kukarow;
		
			//keksitään uudelle failille joku varmasti uniikki nimi:
			list($usec, $sec) = explode(' ', microtime());
			mt_srand((float) $sec + ((float) $usec * 100000));
			$pdf_myyntisopimusfilenimi = "/tmp/Myyntisopimus-".md5(uniqid(mt_rand(), true)).".pdf";
		
			//kirjoitetaan pdf faili levylle..
			$fh = fopen($pdf_myyntisopimusfilenimi, "w");
			if (fwrite($fh, $pdf_myyntisopimus->generate()) === FALSE) die("PDF create error $pdf_myyntisopimusfilenimi");
			fclose($fh);			
					
			// itse print komento...
			if ($komento == 'RUUDULLE') {
				return $pdf_myyntisopimusfilenimi;			
			}
			elseif ($komento == 'email') {
				$liite = $pdf_myyntisopimusfilenimi;
				$kutsu = "Myyntisopimus";
	
				echo t("Myyntisopimus tulostuu")."...<br>";
				
				if ($kukarow["extranet"] == '') {
					require("../inc/sahkoposti.inc");
				}
				else {
					require("sahkoposti.inc");
				}
			}
			elseif ($tee == 'NAYTATILAUS') {
				//Työnnetään tuo pdf vaan putkeen!
				echo file_get_contents($pdf_myyntisopimusfilenimi);
			}		
			elseif ($komento != '' and $komento != 'edi') {
				echo t("Myyntisopimus tulostuu")."...<br>";
				$line = exec("$komento $pdf_myyntisopimusfilenimi", $output, $returnvalue);
			}
			
			//poistetaan tmp file samantien kuleksimasta...
			system("rm -f $pdf_myyntisopimusfilenimi");
		}
	}
	
	
	if (!function_exists('tulosta_myyntisopimus')) {
		function tulosta_myyntisopimus ($otunnus, $komento, $kieli = "", $tee = "") {
			global $yhtiorow, $kukarow;
			
			// Haetaan laskun tiedot		
			$query = "	SELECT *
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]'
						and tunnus  = '$otunnus'";
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
			$query  = "select * from maksuehto where tunnus='$otunnus'";
			$result = mysql_query($query) or pupe_error($query);
			$masrow = mysql_fetch_array($result);
	
			//maksuehto tekstinä
			$laskurow["maksuehto"] = $masrow["teksti"]." ".$masrow["kassa_teksti"];
			
			
			$sivu = 1;
			
			//Myyntisopimuksen pohja
			list ($pdf_myyntisopimus, $page_myyntisopimus) = alku_myyntisopimus ($laskurow, $sivu, $kieli);
																																
			function printtaa_rivit_myyntisopimus($pdf_myyntisopimus, $laskurow, $kala, $lask, $kieli, $sivu, $page_myyntisopimus, $osasto, $selite, $total, $total_netto) {
				global $yhtiorow, $kukarow;
								
				$ennakkolisa = "";
				if($yhtiorow["ennakkomaksu_tuotenumero"] != '') {
					$ennakkolisa = " and tilausrivi.tuoteno != '$yhtiorow[ennakkomaksu_tuotenumero]' ";
				}
				
				$query = "	SELECT tilausrivi.*, round((tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * tilausrivi.hinta * (1-(tilausrivi.ale/100)),2) rivihinta
							FROM tilausrivi, tuote
							WHERE tilausrivi.otunnus = '$laskurow[tunnus]' 
							and tilausrivi.yhtio = '$kukarow[yhtio]'
							and tilausrivi.yhtio = tuote.yhtio
							and tilausrivi.tuoteno = tuote.tuoteno
							and tilausrivi.tyyppi in  ('L','T')
							$ennakkolisa
							and $osasto";
				$riresult = mysql_query($query) or pupe_error($query);
				
				while ($row = mysql_fetch_array($riresult)) {																		
										
					list($page_myyntisopimus, $kala) = rivi_myyntisopimus($pdf_myyntisopimus, $laskurow, $kala, $kieli, $page_myyntisopimus, $selite, $row);
					
					$lask++;
					
					//jos on paljon rivejä tehdään uusi otsikko...
					if ($lask > 12) {						
						loppu_myyntisopimus ($pdf_myyntisopimus, $laskurow, $kieli, $page_myyntisopimus, 0);
						
						$sivu++;
						$page_myyntisopimus = alku_myyntisopimus ($laskurow, $sivu, $kieli, $pdf_myyntisopimus);
					}
															
					if ($row["netto"] != 'N' and $row["laskutettu"] == "") {
						$total += $row["rivihinta"]; // lasketaan tilauksen loppusummaa MUUT RIVIT.. (pitää olla laskuttamaton, muuten erikoisale on jo jyvitetty)
					}
					else {
						$total_netto += $row["rivihinta"]; // lasketaan tilauksen loppusummaa NETTORIVIT..
					}					
				}
				return array ($lask, $kala, $total, $total_netto);				
			}
							
			$total 			= 0;
			$total_netto 	= 0;
			
			$kala = 203.5;
			$lask = 0;
			
			// Uusi vene
			list ($lask, $kala, $total, $total_netto) = printtaa_rivit_myyntisopimus($pdf_myyntisopimus, $laskurow, $kala, $lask, $kieli, $sivu, $page_myyntisopimus, "tuote.osasto = '1'", "Veneen merkki, malli (ja numero, jos tiedossa tarjoushetkellä)", $total, $total_netto);
			// Uusi moottori				
			list ($lask, $kala, $total, $total_netto) = printtaa_rivit_myyntisopimus($pdf_myyntisopimus, $laskurow, $kala, $lask, $kieli, $sivu, $page_myyntisopimus, "tuote.osasto = '2'", "Moottorin merkki, malli (ja numero, jos tiedossa tarjoushetkellä)", $total, $total_netto);
			// Uudet tarvikkeet		
			list ($lask, $kala, $total, $total_netto) = printtaa_rivit_myyntisopimus($pdf_myyntisopimus, $laskurow, $kala, $lask, $kieli, $sivu, $page_myyntisopimus, "tuote.osasto not in ('1','2','4')", "Muuta", $total, $total_netto);																															
																																		
			// Siirrytään vaihtolaitteiden kimppun
			$total_uudet 		= $total;
			$total_netto_uudet 	= $total_netto;	
																				
			$kala = 109.5;
			// Vaihtovene									
			list ($lask, $kala, $total, $total_netto) = printtaa_rivit_myyntisopimus($pdf_myyntisopimus, $laskurow, $kala, $lask, $kieli, $sivu, $page_myyntisopimus, "tuote.osasto = '4'", "Vaihtoveneen merkki, malli (ja numero, jos tiedossa tarjoushetkellä)", $total, $total_netto);	
						
			if($yhtiorow["ennakkomaksu_tuotenumero"] != '') {
				$query = "	SELECT tilausrivi.*, round((tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * tilausrivi.hinta * (1-(tilausrivi.ale/100)),2) rivihinta
							FROM tilausrivi, tuote
							WHERE tilausrivi.otunnus = '$laskurow[tunnus]' 
							and tilausrivi.yhtio = '$kukarow[yhtio]'
							and tilausrivi.yhtio = tuote.yhtio
							and tilausrivi.tuoteno = tuote.tuoteno
							and tilausrivi.tyyppi in  ('L','T')
							and tilausrivi.tuoteno = '$yhtiorow[ennakkomaksu_tuotenumero]'";
				$riresult = mysql_query($query) or pupe_error($query);
				
				$tilattaessa = 0;
				$toimitettaessa = 0;
				
				if (mysql_num_rows($riresult) > 0) {
					while($rirow = mysql_fetch_array($riresult)) {
						if($rirow["rivihinta"] > 0) {
							$tilattaessa += $rirow["rivihinta"];
						}
						else {
							$toimitettaessa += $rirow["rivihinta"];
						}
					}
				}																
			}
												
			// Lasketaan vähän hintoja yhteen
			$total_vanhat 		= $total - $total_uudet;
			$total_netto_vanhat = $total_netto - $total_netto_uudet;
																		
			// Lopputekstit
			loppu_myyntisopimus ($pdf_myyntisopimus, $laskurow, $kieli, $page_myyntisopimus, 1, $total_uudet, $total_netto_uudet, $total_vanhat, $total_netto_vanhat, $tilattaessa, $toimitettaessa);
			
			// Sopimusehdot
			ehdot_myyntisopimus ($pdf_myyntisopimus, $laskurow, $kieli);
														
			// Tulostetaan sivu
			print_pdf_myyntisopimus ($pdf_myyntisopimus, $komento, $tee);
			
			$tee = '';
		}
	}
?>
