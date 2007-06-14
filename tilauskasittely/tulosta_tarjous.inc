<?php

	//Fontit
	$headersin["font"] 					= "Helvetica-Bold";
	$headersin["height"]				= 14;
	$headersin['fillcolor']['red']		= 0.23;
	$headersin['fillcolor']['green']	= 0.44;
	$headersin['fillcolor']['blue']		= 0.58;
	
	$kymppi_helvetica["height"]				= 10;
	$kymppi_helvetica["font"] 				= "Helvetica";
	$kymppi_helvetica['fillcolor']['red']	= 0.4;
	$kymppi_helvetica['fillcolor']['green']	= 0.4;
	$kymppi_helvetica['fillcolor']['blue']	= 0.4;
	
	$kymppi_helvetica_b["height"]				= 10;
	$kymppi_helvetica_b["font"] 				= "Helvetica-Bold";
	$kymppi_helvetica_b['fillcolor']['red']		= 0.2;
	$kymppi_helvetica_b['fillcolor']['green']	= 0.2;
	$kymppi_helvetica_b['fillcolor']['blue']	= 0.2;

	$kasi_helvetica["height"] 				= 8;
	$kasi_helvetica["font"] 				= "Helvetica";
	$kasi_helvetica['fillcolor']['red']		= 0.2;
	$kasi_helvetica['fillcolor']['green']	= 0.2;
	$kasi_helvetica['fillcolor']['blue']	= 0.2;
	
	$footer_helvetica["height"]				= 8;
	$footer_helvetica["font"] 				= "Helvetica";
	$footer_helvetica["align"] 				= "right";
	$footer_helvetica['fillcolor']['red']	= 0;
	$footer_helvetica['fillcolor']['green']	= 0;
	$footer_helvetica['fillcolor']['blue']	= 0;

	$rectparam["width"]			= 0.2;

	//Haetaan pdflibrary
	require_once('pdflib/phppdflib.class.php');

	//muutamat funktiot...
	if (!function_exists('alku')) {
		function alku_tarjous ($laskurow, $asiakasrow, $sivu, $kieli, $pdf_tarjous) {
			global $yhtiorow, $kukarow, $kasi_helvetica, $kymppi_helvetica, $rectparam, $headersin, $footer_helvetica;
			
			$firstpage = $pdf_tarjous->new_page("a4");

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
					$kala = array();
					$kala['scale'] = 0.20;
					$placement = $pdf_tarjous->image_place($image, mm_pt(250), mm_pt(140), $firstpage,$kala);
				}
			}

			$pp = substr($laskurow["luontiaika"],8,2);
			$kk = substr($laskurow["luontiaika"],5,2);
			$vv = substr($laskurow["luontiaika"],0,4);

			$luotoaik = date("d.m.Y",mktime(0, 0, 0, $kk, $pp, $vv));
			
			if ($laskurow["toim_nimi"] != $laskurow["nimi"] and $laskurow["toim_nimi"] != '') {
				$laskunimi =  $laskurow["toim_nimi"];
			}
			else {
				$laskunimi =  $laskurow["nimi"];
			}
			
			$pdf_tarjous->draw_text(mm_pt(10),  mm_pt(219), $laskunimi, 										$firstpage, $headersin);
			$pdf_tarjous->draw_text(mm_pt(155),  mm_pt(232), t("Tarjous $luotoaik", $kieli),						$firstpage, $kasi_helvetica);
			
			// footeri
			
			$footerpala = $pdf_tarjous->strlen("T  ".$yhtiorow["puhelin"]."    W  ".$yhtiorow["www"], $footer_helvetica);
			
			$pdf_tarjous->draw_text(mm_pt(8),	mm_pt(15), $yhtiorow["nimi"]."    ".$yhtiorow["osoite"].",  ".$yhtiorow["postino"]." ".$yhtiorow["postitp"].",  ".$yhtiorow["maa"],		$firstpage, $footer_helvetica);
			$pdf_tarjous->draw_text(570-$footerpala,		mm_pt(15), "T  ".$yhtiorow["puhelin"]."    W  ".$yhtiorow["www"],		$firstpage, $footer_helvetica);
			
			//Palautetan luotu sivu
			return $firstpage;
		}
	}

	
	if (!function_exists('rivi_tarjous')) {
		function rivi_tarjous($pdf_tarjous, $laskurow, $kala, $kieli, $page_tarjous, $row) {
			global $yhtiorow, $kukarow, $kasi_helvetica, $kymppi_helvetica, $kymppi_helvetica_b;

			$rivinkorkeus	= 6;
			
			$varakala = $kala-$rivinkorkeus;
		
			// A-hinta (voi olla verollinen tai veroton riippuen yhtiöstä)
			if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
				$row["hinta"] = laskuval($row["hinta"], $laskurow["vienti_kurssi"]);
			}
		
			$pdf_tarjous->draw_text(mm_pt(15), mm_pt($kala), ucfirst(strtolower($row["tuoteno"])),		$page_tarjous, $kymppi_helvetica_b);
			
			if ($row["kommentti"] != '') {
				$kala -= 1;
				$pala = $pdf_tarjous->draw_paragraph(mm_pt($kala), mm_pt(15), mm_pt(12), mm_pt(195),  $row["kommentti"],		$page_tarjous, $kymppi_helvetica); // top, left, bottom, right
				$kala = pt_mm($pala);
			}
			
			$kala -= $rivinkorkeus;
			
			if($row["toimaika"] == '0000-00-00' or $row["toimaika"] == $laskurow["toimaika"]) {
				$pdf_tarjous->draw_text(mm_pt(15), mm_pt($kala), "Toimitusaika: Avoin",		$page_tarjous, $kymppi_helvetica);
			}
			else {
				$pdf_tarjous->draw_text(mm_pt(15), mm_pt($kala), "Toimitusaika ".tv1dateconv($row["toimaika"]),		$page_tarjous, $kymppi_helvetica);
			}
			
			$hintaa = $row["hinta"]*$row["tilkpl"]*(1-$row["ale"]/100);
			
			$hintab = str_pad("$hintaa $yhtiorow[valkoodi]", 15, " ", STR_PAD_LEFT);
			
			if ($hintaa == 0) {
				$hintab = str_pad("Tuotekehitys", 15, " ", STR_PAD_LEFT);
			}
			
			$pdf_tarjous->draw_text(mm_pt(170), mm_pt($kala), $hintab,		$page_tarjous, $kymppi_helvetica);
	
			$kala -= $rivinkorkeus+3;

			return array ($page_tarjous, $kala);
		}
	}
	
	
	
	if (!function_exists('loppu_tarjous')) {
		function loppu_tarjous ($pdf_tarjous, $laskurow, $kieli, $page_tarjous, $tots, $total=0) {
			global $yhtiorow, $kukarow, $kasi_helvetica, $kymppi_helvetica, $rectparam, $kymppi_helvetica_b;

			if ($tots == 1) {
				$arvo  = $total;
				
				$pp = substr($laskurow["luontiaika"],8,2);
				$kk = substr($laskurow["luontiaika"],5,2);
				$vv = substr($laskurow["luontiaika"],0,4);

				$voimassa = date("d.m.Y",mktime(0, 0, 0, $kk, $pp+14, $vv));
				
				//Käsin syötetty summa johon lasku pyöristetään
				if (abs($laskurow["hinta"]-$arvo) <= 0.5) {
					$arvo = sprintf("%.2f",$laskurow["hinta"]);
				}
				
				$pdf_tarjous->draw_text(mm_pt(172), mm_pt(93), sprintf('%10s', $arvo)." ".$yhtiorow["valkoodi"], 			$page_tarjous, $kymppi_helvetica);


	
				$pdf_tarjous->draw_text(mm_pt(15),  mm_pt(93),  t("Tarjoamme teille ylläolevan tarjouksen kohteet hintaan:", $kieli), 	$page_tarjous, $kymppi_helvetica);
				

				if ($laskurow['viesti'] != '') {
					$pdf_tarjous->draw_text(mm_pt(15),  mm_pt(80), $laskurow["viesti"], 			$page_tarjous, $kymppi_helvetica);	
				}

				if ($yhtiorow['alv_kasittely'] != '') {
					$pdf_tarjous->draw_text(mm_pt(15),  mm_pt(70), "Hinnat ovat nettohintoja joihin lisätään arvonlisävero.", $page_tarjous, $kasi_helvetica);
				}
				else {
					$pdf_tarjous->draw_text(mm_pt(15),  mm_pt(70), "Hinnat sisältävät arvonlisäveron.", $page_tarjous, $kasi_helvetica);
				}
				
				$pdf_tarjous->draw_text(mm_pt(15),  mm_pt(64), "Lisäksi veloitamme mahdollisesta toimituksesta aiheutuneet kulut.", $page_tarjous, $kasi_helvetica);
				$pdf_tarjous->draw_text(mm_pt(15),  mm_pt(60), "Maksuehtomme on ".trim($laskurow["maksuehto"]).".", $page_tarjous, $kasi_helvetica);
				$pdf_tarjous->draw_text(mm_pt(15),  mm_pt(56),  t("Tarjous on voimassa", $kieli)." ".$voimassa." ". t("saakka", $kieli).".", $page_tarjous, $kasi_helvetica);
				
				$pdf_tarjous->draw_text(mm_pt(15),  mm_pt(46.5), "Toivomme tarjouksemme kiinnostavan teitä ja johtavan tilaukseen.", $page_tarjous, $kymppi_helvetica);
				
				$pdf_tarjous->draw_text(mm_pt(15),  mm_pt(40), "Kunnioittavasti,", $page_tarjous, $kymppi_helvetica);
				
				$query  = "	select *
							from kuka
							where tunnus = '$laskurow[myyja]' and yhtio='$kukarow[yhtio]'";
				$myyresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($myyresult) == 1) {
					$myyrow = mysql_fetch_array($myyresult);	
				}
				else {
					$myyrow = $kukarow;
				}
				
				$pdf_tarjous->draw_text(mm_pt(15),  mm_pt(35), $myyrow["nimi"], $page_tarjous, $kymppi_helvetica_b);
				$pdf_tarjous->draw_text(mm_pt(15),  mm_pt(30), $yhtiorow["nimi"], $page_tarjous, $kymppi_helvetica_b);
				
				$total = 0;
			}
			else {
				$pdf_tarjous->draw_text(mm_pt(178), mm_pt(30), t("Jatkuu", $kieli)."...", $page_tarjous, $kymppi_helvetica);
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
		function tulosta_tarjous ($otunnus, $komento, $kieli = "", $tee = "") {
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
			$query  = "select * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
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
			$query  = "	select *
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
			
			function printtaa_rivit_tarjous($pdf_tarjous, $laskurow, $lask, $kala, $kieli, $sivu, $page_tarjous, $total) {
				global $yhtiorow, $kukarow;

				//Tehään tästä taas array tän funktion siäiseen käyttöön jotta saamme sivunvaihdot toimimaan
				$page_tarjous2[$sivu] = $page_tarjous;
				
				if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"])) and $laskurow["vienti_kurssi"] != 0) {
					$hinta_riv = "round(tilausrivi.hinta/$laskurow[vienti_kurssi], 2)";
				}
				else {
					$hinta_riv = "tilausrivi.hinta";
				}
				
				//	Käsitellään tuoterivit
				$query = "	SELECT tilausrivi.*, 
							round($hinta_riv * if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+$laskurow[erikoisale]-(tilausrivi.ale*$laskurow[erikoisale]/100))/100)),2) rivihinta, 
							tilausrivin_lisatiedot.positio, tilausrivin_lisatiedot.asiakkaan_positio, ei_saldoa
							FROM tilausrivi
							JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
							LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus	
							WHERE tilausrivi.otunnus = '$laskurow[tunnus]'
							and tilausrivi.yhtio = '$kukarow[yhtio]'
							and tilausrivi.tyyppi in  ('L','T','U')";
							
				$grand_total=0;

				$riresult = mysql_query($query) or pupe_error($query);
				
				if(mysql_num_rows($riresult)>0) {
					$tarvitaanuus = "";
					
					while ($row = mysql_fetch_array($riresult)) {
						//jos on paljon rivejä tehdään uusi otsikko...
						if ($tarvitaanuus != '') {
							loppu_tarjous ($pdf_tarjous, $laskurow, $kieli, $page_tarjous2[$sivu], 0);

							$sivu++;
							$lask = 0;
							$kala = 200;
							$tarvitaanuus = "";

							$page_tarjous2[$sivu] = alku_tarjous ($laskurow, $asiakasrow, $sivu, $kieli, $pdf_tarjous);
						}
						
						elseif ($kala <= 100) {
							$tarvitaanuus = 'NYT';
						}
						
						list($page_tarjous2[$sivu], $kala) = rivi_tarjous($pdf_tarjous, $laskurow, $kala, $kieli, $page_tarjous2[$sivu], $row);
						$lask++;
						$total += $row["rivihinta"];
					}
					
					if ($tarvitaanuus != '') {
						loppu_tarjous ($pdf_tarjous, $laskurow, $kieli, $page_tarjous2[$sivu], 0);

						$sivu++;
						$lask = 0;
						$kala = 200;
						$tarvitaanuus = "";

						$page_tarjous2[$sivu] = alku_tarjous ($laskurow, $asiakasrow, $sivu, $kieli, $pdf_tarjous);
					}
				}
				return array ($page_tarjous2[$sivu], $lask, $kala, $sivu, $total);
			}


			//oletuksia
			$kala 			= 200;
			$lask 			= 0;
			$total 			= 0;
			$sivu 			= 1;

			//tarjouksen pohja
			$pdf_tarjous = new pdffile;
 
 			$pdf_tarjous->set_default('margin-top', 20);
 			$pdf_tarjous->set_default('margin-bottom', 0);
 			$pdf_tarjous->set_default('margin-left', 0);
 			$pdf_tarjous->set_default('margin-right', 0);
 
 			$page_tarjous[$sivu] = alku_tarjous ($laskurow, $asiakasrow, $sivu, $kieli, $pdf_tarjous);
			
			list ($page_tarjous[$sivu], $lask, $kala, $sivu, $total) = printtaa_rivit_tarjous($pdf_tarjous, $laskurow, $lask, $kala, $kieli, $sivu, $page_tarjous[$sivu], $total);

			$lask--;
			
			// Lopputekstit
			loppu_tarjous ($pdf_tarjous, $laskurow, $kieli, $page_tarjous[$sivu], 1, $total);

			// Tulostetaan sivu
			print_pdf_tarjous ($pdf_tarjous, $komento, $tee);

			$tee = '';
		}
	}
?>
