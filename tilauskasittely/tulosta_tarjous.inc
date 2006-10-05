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
	
	$rectparam["width"]			= 0.2;
	
	
	//Haetaan pdflibrary
	require_once('../pdflib/phppdflib.class.php');
	
	//muutamat funktiot...
	if (!function_exists('alku')) {
		function alku_tarjous ($laskurow, $sivu, $kieli, $pdf_tarjous = "") {
			global $yhtiorow, $kukarow, $kasi_helvetica, $kymppi_helvetica, $seiska, $rectparam;
								
			$pdf_tarjous = new pdffile;
	
			$pdf_tarjous->set_default('margin-top', 20);
			$pdf_tarjous->set_default('margin-bottom', 0);
			$pdf_tarjous->set_default('margin-left', 0);
			$pdf_tarjous->set_default('margin-right', 0);
									
			$pdf_tarjous->enable('import');				
			
			// Haetaan pohja
			ob_start();

			$filename = "/var/www/trunk/pupesoft/vene_tarjous.pdf";
			
			$handle = fopen($filename, "r");
			$d = fread($handle, filesize($filename));
			fclose($handle);
		
			$pdf_tarjous->import->append($d);
		
			$pdf_tarjous_sivu = $pdf_tarjous->import->get_pages();
																																									
			ob_end_clean();
											
			
			if (trim($yhtiorow["lasku_logo"]) != '' and file_exists($yhtiorow["lasku_logo"])) {
				$filename = $yhtiorow["lasku_logo"];
				
				$fh = fopen($filename, "r");
				$data = fread($fh, filesize($filename));
				fclose($fh);
							
				$image = $pdf_tarjous->jfif_embed($data);
				
				if(!$image) {
					echo t("Logokuvavirhe").": ".$image.$data;
				}
				else {
					$placement = $pdf_tarjous->image_place($image, mm_pt(280), mm_pt(10), $pdf_tarjous_sivu[0]);
				}
			}
			
			$pp = substr($laskurow["luontiaika"],8,2);
			$kk = substr($laskurow["luontiaika"],5,2);
			$vv = substr($laskurow["luontiaika"],0,4);
			
			$voimassa = date("d.m.Y",mktime(0, 0, 0, $kk, $pp+14, $vv));
			$luotoaik = date("d.m.Y",mktime(0, 0, 0, $kk, $pp, $vv));
									
			$pdf_tarjous->draw_text(mm_pt(110),  mm_pt(280), t("TARJOUS"),									$pdf_tarjous_sivu[0]);
			$pdf_tarjous->draw_text(mm_pt(75),   mm_pt(280), t("Pvm").": $luotoaik",						$pdf_tarjous_sivu[0], $kasi_helvetica);
			$pdf_tarjous->draw_text(mm_pt(160),  mm_pt(284), t("Numero"),									$pdf_tarjous_sivu[0], $kasi_helvetica);
			
			//top left bottom rigth			
			$pdf_tarjous->draw_rectangle(mm_pt(287), mm_pt(158),  mm_pt(278), mm_pt(197),					$pdf_tarjous_sivu[0], $rectparam);
			
			$pdf_tarjous->draw_text(mm_pt(170),  mm_pt(280), $laskurow["tunnus"],							$pdf_tarjous_sivu[0]);
																		
			$pdf_tarjous->draw_text(mm_pt(10),  mm_pt(274), t("Myyjä"), 									$pdf_tarjous_sivu[0], $seiska);															
			$pdf_tarjous->draw_text(mm_pt(10),  mm_pt(270), $yhtiorow["nimi"], 								$pdf_tarjous_sivu[0]);
			$pdf_tarjous->draw_text(mm_pt(10),  mm_pt(265), $yhtiorow["osoite"], 							$pdf_tarjous_sivu[0]);
			$pdf_tarjous->draw_text(mm_pt(10),  mm_pt(260), $yhtiorow["postino"]." ".$yhtiorow["postitp"],	$pdf_tarjous_sivu[0]);
			$pdf_tarjous->draw_text(mm_pt(10),  mm_pt(255), t("Puh").": ".$yhtiorow["puhelin"],				$pdf_tarjous_sivu[0]);
			
			$pdf_tarjous->draw_text(mm_pt(75),  mm_pt(274), t("Ostaja"), 									$pdf_tarjous_sivu[0], $seiska);
			$pdf_tarjous->draw_text(mm_pt(75),  mm_pt(270), $laskurow["nimi"], 								$pdf_tarjous_sivu[0]);
			$pdf_tarjous->draw_text(mm_pt(75),  mm_pt(265), $laskurow["osoite"], 							$pdf_tarjous_sivu[0]);
			$pdf_tarjous->draw_text(mm_pt(75),  mm_pt(260), $laskurow["postino"]." ".$laskurow["postitp"],	$pdf_tarjous_sivu[0]);
			$pdf_tarjous->draw_text(mm_pt(75),  mm_pt(255), t("Puh").": ".$laskurow["puhelin"],				$pdf_tarjous_sivu[0]);
			
			$pdf_tarjous->draw_text(mm_pt(25),  mm_pt(150), $laskurow["toimitusehto"],						$pdf_tarjous_sivu[0], $kymppi_helvetica);
			
			$pdf_tarjous->draw_text(mm_pt(10),  mm_pt(245), t("TARJOUKSEN KOHDE"),							$pdf_tarjous_sivu[0], $kymppi_helvetica);
			$pdf_tarjous->draw_text(mm_pt(10),  mm_pt(138), t("VAIHTOKOHDE"),								$pdf_tarjous_sivu[0], $kymppi_helvetica);
			$pdf_tarjous->draw_text(mm_pt(25),  mm_pt(93),  t("TARJOAMME TEILLE YLLÄOLEVAN TARJOUKSEN KOHTEEN HINTAAN / VÄLIRAHAAN"), $pdf_tarjous_sivu[0], $kymppi_helvetica);
			
			$pdf_tarjous->draw_text(mm_pt(27),  mm_pt(154), t("Tarjouskohteen toimitusehto"),				$pdf_tarjous_sivu[0], $seiska);
			$pdf_tarjous->draw_text(mm_pt(126), mm_pt(151), t("Hinta yhteensä"),							$pdf_tarjous_sivu[0], $kymppi_helvetica);
			
			
			$pdf_tarjous->draw_text(mm_pt(126), mm_pt(101), t("Hyvityshinta yhteensä"),						$pdf_tarjous_sivu[0], $kymppi_helvetica);									
			$pdf_tarjous->draw_text(mm_pt(30),  mm_pt(104), t("Vaihtokohteen toimitusehto"),				$pdf_tarjous_sivu[0], $seiska);
			
			$pdf_tarjous->draw_text(mm_pt(10),  mm_pt(85), t("MUUTA"),										$pdf_tarjous_sivu[0], $kymppi_helvetica);
			
			$pdf_tarjous->draw_text(mm_pt(30),  mm_pt(85), t("Maksuehto"),									$pdf_tarjous_sivu[0], $kymppi_helvetica);
			$pdf_tarjous->draw_text(mm_pt(60),  mm_pt(85), $laskurow["maksuehto"],							$pdf_tarjous_sivu[0], $kasi_helvetica);			
			
			$pdf_tarjous->draw_text(mm_pt(30),  mm_pt(75), t("Arvonlisävero"),								$pdf_tarjous_sivu[0], $kymppi_helvetica);	
			$pdf_tarjous->draw_text(mm_pt(60),  mm_pt(75), t("Kun tarjouksen kohde on uusi, sen hinta sisältää arvonlisäveron")." ".$laskurow["alv"]."%", $pdf_tarjous_sivu[0], $kasi_helvetica);
												
			$pdf_tarjous->draw_text(mm_pt(30),  mm_pt(65), t("Voimassaolo"),								$pdf_tarjous_sivu[0], $kymppi_helvetica);
			$pdf_tarjous->draw_text(mm_pt(60),  mm_pt(65), t("Tarjous on voimassa välimyyntivarauksin")." ".$voimassa." ". t("saakka").".", $pdf_tarjous_sivu[0], $kasi_helvetica);
			
			$pdf_tarjous->draw_text(mm_pt(30),  mm_pt(55), t("Terveisin:"),									$pdf_tarjous_sivu[0], $kasi_helvetica);
			$pdf_tarjous->draw_text(mm_pt(30),  mm_pt(50), $kukarow["nimi"],								$pdf_tarjous_sivu[0], $kymppi_helvetica);
						
			//Palautetan luotu pdf ja sivu
			return array ($pdf_tarjous, $pdf_tarjous_sivu[0]);							
		}
	}
	
	if (!function_exists('rivi_tarjous')) {
		function rivi_tarjous($pdf_tarjous, $laskurow, $kala, $kieli, $page_tarjous, $selite, $row) {
			global $yhtiorow, $kukarow, $kasi_helvetica, $kymppi_helvetica, $seiska, $kymppi_courier;
		
			$rivinkorkeus	= 7.1;
								
			$pdf_tarjous->draw_text(mm_pt(22), mm_pt($kala+4), $selite, 								$page_tarjous, $seiska);
			$pdf_tarjous->draw_text(mm_pt(24), mm_pt($kala), $row["nimitys"], 							$page_tarjous, $kymppi_helvetica);
			//$pdf_tarjous->draw_text(mm_pt(158), mm_pt($kala), $row["ale"]."%", 						$page_tarjous, $kymppi_courier);
			$pdf_tarjous->draw_text(mm_pt(172), mm_pt($kala), sprintf('%10s', $row["rivihinta"]),		$page_tarjous, $kymppi_courier);
			
			$kala = $kala - $rivinkorkeus;
		
			return array ($page_tarjous, $kala);
		}
	}
	
	
	if (!function_exists('loppu_tarjous')) {
		function loppu_tarjous ($pdf_tarjous, $laskurow, $kieli, $page_tarjous, $tots, $total_uudet=0, $total_netto_uudet=0, $total_vanhat=0, $total_netto_vanhat=0) {
			global $yhtiorow, $kukarow, $kasi_helvetica, $kymppi_helvetica, $seiska, $kymppi_courier;
					
			if ($tots == 1) {
									
				/*
				$arvo = $total + $total_netto;
				
				//erikoisalennus annetaan vain riveille joilla EI ole NETTOHINTAA
				if ($laskurow['erikoisale'] != 0) {
					$apu1 = round($laskurow['erikoisale']/100,2);	// erikoisale prosentti
					$apu2 = round($total*$apu1,2); 					// erikoisalen määrä
					$apu3 = round((1-$apu1)*$total,2);				// loppusumma								
					
					$pdf_tarjous->draw_text(450, 37, t("Erikoisale", $kieli).":", $page_tarjous, $p);
					$pdf_tarjous->draw_text(500, 37, sprintf("%01.2f", $apu2)." ".$laskurow["valkoodi"], $page_tarjous, $p);
				
					$kaikkiyhteensa = $apu3 + $total_netto;
				}
				else {
					$kaikkiyhteensa = $arvo;											
				}
				*/							
				
				$uudet = $total_uudet + $total_netto_uudet;
				$vanha = $total_vanhat + $total_netto_vanhat;
				$arvo  = $uudet + $vanha;
				
				$pdf_tarjous->draw_text(mm_pt(172), mm_pt(151), sprintf('%10s', sprintf("%01.2f", $uudet)), $page_tarjous, $kymppi_courier);
				$pdf_tarjous->draw_text(mm_pt(162), mm_pt(151), $laskurow["valkoodi"], $page_tarjous, $kymppi_helvetica);
											
				$pdf_tarjous->draw_text(mm_pt(172), mm_pt(101), sprintf('%10s', sprintf("%01.2f", $vanha)), $page_tarjous, $kymppi_courier);
				$pdf_tarjous->draw_text(mm_pt(162), mm_pt(101), $laskurow["valkoodi"], $page_tarjous, $kymppi_helvetica);
											
				$pdf_tarjous->draw_text(mm_pt(172), mm_pt(92), sprintf('%10s', sprintf("%01.2f", $arvo)), $page_tarjous, $kymppi_courier);
											
				$total			= 0;
				$total_netto 	= 0;
			}
			else {
				$pdf_tarjous->draw_text(530, 23, t("Jatkuu", $kieli)."...", $page_tarjous, $p);
			}
		}
	}

	
	if (!function_exists('print_pdf_tarjous')) {
		function print_pdf_tarjous ($pdf_tarjous, $komento, $tee = "") {
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
			elseif ($komento == 'email') {
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
		function tulosta_tarjous ($otunnus, $komento, $kieli = "", $tee = "") {
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
			list ($pdf_tarjous, $page_tarjous) = alku_tarjous ($laskurow, $sivu, $kieli);
																																
			function printtaa_rivit_tarjous($pdf_tarjous, $laskurow, $kala, $lask, $kieli, $sivu, $page_tarjous, $osasto, $selite, $total, $total_netto) {
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
										
					list($page_tarjous, $kala) = rivi_tarjous($pdf_tarjous, $laskurow, $kala, $kieli, $page_tarjous, $selite, $row);
					
					$lask++;
					
					//jos on paljon rivejä tehdään uusi otsikko...
					if ($lask > 12) {						
						loppu_tarjous ($pdf_tarjous, $laskurow, $kieli, $page_tarjous, 0);
						
						$sivu++;
						$page_tarjous = alku_tarjous ($laskurow, $sivu, $kieli, $pdf_tarjous);
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
			
					
			//oletuksia
			$kala = 236;
			$lask = 0;
			
			// Uusi vene
			list ($lask, $kala, $total, $total_netto) = printtaa_rivit_tarjous($pdf_tarjous, $laskurow, $kala, $lask, $kieli, $sivu, $page_tarjous, "tuote.osasto = '1'", "Veneen merkki, malli (ja numero, jos tiedossa tarjoushetkellä)", $total, $total_netto);
			// Uusi moottori				
			list ($lask, $kala, $total, $total_netto) = printtaa_rivit_tarjous($pdf_tarjous, $laskurow, $kala, $lask, $kieli, $sivu, $page_tarjous, "tuote.osasto = '2'", "Moottorin merkki, malli (ja numero, jos tiedossa tarjoushetkellä)", $total, $total_netto);
			// Uudet tarvikkeet		
			list ($lask, $kala, $total, $total_netto) = printtaa_rivit_tarjous($pdf_tarjous, $laskurow, $kala, $lask, $kieli, $sivu, $page_tarjous, "tuote.osasto not in ('1','2','4')", "Muuta", $total, $total_netto);																															
																																		
			// Siirrytään vaihtolaitteiden kimppun
			$total_uudet 		= $total;
			$total_netto_uudet 	= $total_netto;	
																				
			$kala = 129;
			// Vaihtovene									
			list ($lask, $kala, $total, $total_netto) = printtaa_rivit_tarjous($pdf_tarjous, $laskurow, $kala, $lask, $kieli, $sivu, $page_tarjous, "tuote.osasto = '4'", "Vaihtoveneen merkki, malli (ja numero, jos tiedossa tarjoushetkellä)", $total, $total_netto);	
						
			// Lasketaan vähän hintoja yhteen
			$total_vanhat 		= $total - $total_uudet;
			$total_netto_vanhat = $total_netto - $total_netto_uudet;
																		
			// Lopputekstit
			loppu_tarjous ($pdf_tarjous, $laskurow, $kieli, $page_tarjous, 1, $total_uudet, $total_netto_uudet, $total_vanhat, $total_netto_vanhat);																	
			
			// Tulostetaan sivu
			print_pdf_tarjous ($pdf_tarjous, $komento, $tee);
			
			$tee = '';
		}
	}
?>
