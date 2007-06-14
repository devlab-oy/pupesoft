<?php

	$tulosta_ostotilaus_ulos = "";
	
	$query = "	SELECT *
				FROM lasku
				WHERE tunnus = '$otunnus' and yhtio='$kukarow[yhtio]'";
	$sresult = mysql_query($query) or pupe_error($query);
	$laskurow = mysql_fetch_array($sresult);

	if ($laskurow['tila'] != 'O') {
		$tulosta_ostotilaus_ulos .= t("Kesken oleva tilaus ei ole ostotilaus");
		exit;
	}

	//tässä tapauksessa tilaus lähetetään edinä
	if ($komento['Ostotilaus'] == 'edi') {

		// haetaan toimittajan tiedot
		$query    = "select * from toimi where yhtio='$kukarow[yhtio]' and ytunnus='$laskurow[ytunnus]'";
		$toimires = mysql_query($query) or pupe_error($query);
		$toimirow = mysql_fetch_array($toimires);

		if (($toimirow['edi_palvelin']=='') or
			($toimirow['edi_kayttaja']=='') or
			($toimirow['edi_salasana']=='') or
			($toimirow['edi_kuvaus']=='') or
			($toimirow['edi_polku']=='')) {
			$tulosta_ostotilaus_ulos .= "<font class='error'>".t("Edi-tilauksen lähettämiseen tarvittavia tietoja ei ole syötetty")."! ".t("Tarkista toimittajan tiedot")."!</font>";
		}
		else {
			require($toimirow['edi_kuvaus']);
			$tee = '';
		}
	}
	//muuten siitä tehdään peedeeäffä
	else {
		
		if($yhtiorow["ostotilaustyyppi"]==1) {
			require_once('../pdflib/pupepdf.class.php');

			if (!function_exists('tulosta_ostotilaus')) {
				function tulosta_ostotilaus ($otunnus, $komento, $kieli = "", $tee) {
					global $yhtiorow, $kukarow, $fonts, $tulosta_ostotilaus_ulos;

					// Haetaan laskun tiedot
					$query = "	SELECT lasku.*, lasku.tunnus tunnus, lasku.nimi nimi, lasku.luontiaika päiväys, lasku.tunnusnippu projekti, lasku.tunnus tilaus, lasku.tunnus toimitus, viesti viite, comments lisätiedot,
								concat_ws(' ', postino, postitp) postiosoite,
								concat_ws(' ', toim_postino, toim_postitp) toim_postiosoite,					
								SUBSTRING(lasku_maa.nimi FROM 5) maa,
								SUBSTRING(toim_maa.nimi FROM 5) toim_maa
								FROM lasku
								LEFT JOIN maat lasku_maa ON lasku_maa.koodi=lasku.maa 
								LEFT JOIN maat toim_maa ON toim_maa.koodi=lasku.toim_maa
								WHERE lasku.yhtio = '$kukarow[yhtio]'
								and lasku.tunnus  = '$otunnus'";
					$result = mysql_query($query) or pupe_error($query);
					$laskurow = mysql_fetch_array($result);

					if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"])) and $laskurow["vienti_kurssi"] != 0) {
						$hinta_riv = "round(tilausrivi.hinta/$laskurow[vienti_kurssi], 2)";
					}
					else {
						$hinta_riv = "tilausrivi.hinta";
					}

					if ($kieli== '') {
						$querykiel = "SELECT kieli FROM toimi WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$laskurow[liitostunnus]'";
						$kielresult = mysql_query($querykiel) or pupe_error($querykiel);
						$kielnum = mysql_num_rows($kielresult);
						$kielrow = mysql_fetch_array($kielresult);
						$kieli = strtolower($kielrow['kieli']);
					}				

					//	Aloitellaan piirtelyä
					$pdf = new pdf;

					$pdf->set_default('margin-top', 	mm_pt(10));
					$pdf->set_default('margin-bottom', 	mm_pt(10));
					$pdf->set_default('margin-left', 	mm_pt(15));
					$pdf->set_default('margin-right',	mm_pt(15));

					//	Asetetaan localet
					$pdf->setLocales($laskurow["maa"], $laskurow["valkoodi"]);

					$fonts["snadi"]['height']	= 9;
					$fonts["snadi"]['font']		= "Helvetica";

					$fonts["norm"]['height']	= 10;
					$fonts["norm"]['font']		= "Helvetica";

					$fonts["BIG"]['height']		= 14;
					$fonts["BIG"]['font']		= "Helvetica";

					//	Aloituskorkeus		

					function alku ($pdf, $laskurow, $kieli) {
						global $yhtiorow;

						//	Tehdään uusi sivu
						$pdf->addPage("a4");
						$h=265;

						//	Liitetään kuva
						$yhtiorow["lasku_logo"] = "../pics/RTEC_laskulogo.png";
						if($yhtiorow["lasku_logo"] != "" and file_exists($yhtiorow["lasku_logo"])) {
							$pdf->placeImage(260,0,$yhtiorow["lasku_logo"]);
						}

						//	Kirjoitetaan otsikko
						$pdf->write($h, 2, 0, 0, t("OSTOTILAUS", $kieli), "BIG", "C", "B");

						//	Laitetaan perustiedot oikealle		
						$h=250;
						$s=$pdf->teeSarakkeet(array(100,35));
						foreach(array("tilaus", "päiväys", "huolitsija", "toimitusehto") as $key) {
							if($key == "päiväys") {
								$laskurow[$key] = strftime("%x",strtotime($laskurow[$key]));
							}
							$pdf->write($h, 1, $s[0], $s[1], t(str_replace("_"," ",ucfirst($key)), $kieli).":", "norm", "L", "B");
							$pdf->write($h, 1, $s[1]+2, 0, $laskurow[$key], "norm", "L", "");
							$h-=$pdf->ln();					
						}
						$h-=$pdf->ln(0.5);
						//	Sitten yhteyshenkilöitä
						foreach(array("viite", "lisätiedot") as $key) {
							if($laskurow[$key] != "") {
								$pdf->write($h, 1, $s[0], 0, t(str_replace("_"," ",ucfirst($key)), $kieli).":", "norm", "L", "B");
								$h-=$pdf->ln();					
								$pdf->write($h, 1, $s[0], 0, $laskurow[$key], "norm", "L", "");
								$h-=$pdf->ln(1.5);
							}
						}

						//	Laitetaan toimittajatiedot vasemmalle
						$h=240;
						$pdf->write($h, 1, 0, $s[1], t("Toimittaja", $kieli).":", "norm", "L", "B");
						$h-=$pdf->ln();
						foreach(array("nimi", "nimitark", "osoite", "postiosoite", "maa") as $key) {
							if($key == "maa") {
								$pdf->write($h, 1, 0, $s[0], t($laskurow[$key], $kieli), "norm", "L", "");
							}
							else {
								$pdf->write($h, 1, 0, $s[0], $laskurow[$key], "norm", "L", "");
							}
							$h-=$pdf->ln();	
						}
						$h-=$pdf->ln(1.5);
						$hmem=$h;
						
						$pdf->write($h, 1, 0, $s[1], t("Toimitusosoite", $kieli).":", "norm", "L", "B");
						$h-=$pdf->ln();
						foreach(array("toim_nimi", "toim_nimitark", "toim_osoite", "toim_postiosoite", "toim_maa") as $key) {
							if($key == "toim_maa") {
								$pdf->write($h, 1, 0, $s[0], t($laskurow[$key], $kieli), "norm", "L", "");
							}
							else {
								$pdf->write($h, 1, 0, $s[0], $laskurow[$key], "norm", "L", "");
							}
							$h-=$pdf->ln();	
						}
						$h-=$pdf->ln(1.5);
						
						$h = $hmem;
						//	Sitten vielä tilaajatiedot
						$pdf->write($h, 1, $s[0], 0, t("Laskutusosoite", $kieli).":", "norm", "L", "B");
						$h-=$pdf->ln();
						$pdf->write($h, 1, $s[0], 0, $yhtiorow["nimi"], "norm", "L", "");
						$h-=$pdf->ln();
						$pdf->write($h, 1, $s[0], 0, $yhtiorow["osoite"], "norm", "L", "");
						$h-=$pdf->ln();
						$pdf->write($h, 1, $s[0], 0, $yhtiorow["postino"]." ".$yhtiorow["positp"], "norm", "L", "");
						$h-=$pdf->ln();
						$pdf->write($h, 1, $s[0], 0, t($yhtiorow["maa"]), "norm", "L", "");
						$h-=$pdf->ln();	
																							
						$h-=$pdf->ln(1.5);

						$pdf->write($h, 1, 0, 0, t("Merkitkää tilausnumero lähetteeseen, laskuun sekä muihin dokumentteihin. Hinnat eivät sisällä arvonlisäveroa.", $kieli), "norm", "L", "");
						
						return $pdf;
					}

					$pdf=alku($pdf, $laskurow,$kieli);
					$h = 190;

					//	Käsitellään tilausrivit
					$query = "	SELECT toim_tuoteno, toim_nimitys, tuote.tuoteno, round(tilausrivi.varattu*if(tuotteen_toimittajat.tuotekerroin=0,1,tuotteen_toimittajat.tuotekerroin),2) varattu, toimaika,
								tilausrivi.hinta, tilausrivi.ale, round(tilausrivi.varattu*if(tuotteen_toimittajat.tuotekerroin=0,1,tuotteen_toimittajat.tuotekerroin)*tilausrivi.hinta*(1-ale/100),2) rivihinta, kommentti
								FROM tilausrivi
								join tuote USING (yhtio, tuoteno)
								join tuotteen_toimittajat on tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno and tuotteen_toimittajat.liitostunnus = '$laskurow[liitostunnus]'
								WHERE tilausrivi.otunnus = '$otunnus'
								and tilausrivi.yhtio='$kukarow[yhtio]'
								ORDER BY tilausrivi.toimaika, toim_tuoteno";
					$grand_total=0;
					$riresult = mysql_query($query) or pupe_error($query);

					if(mysql_num_rows($riresult)>0) {

						if($h < 40) {
							$pdf = alku($pdf, $laskurow,$kieli);
							$h = 190;
						}

						$summa = 0;
						$h-=$pdf->ln(3);
						$s=$pdf->teeSarakkeet(array(100,20,25));
						$pdf->write($h, 1, 0, $s[0], t("Tuote", $kieli), "norm", "L", "B");
						$pdf->write($h, 1, $s[0], $s[1], t("Määrä", $kieli), "norm", "R", "B");				
						$pdf->write($h, 1, $s[1], $s[2], t("Hinta/yks", $kieli), "norm", "R", "B");
						$pdf->write($h, 1, $s[2], 0, t("Yhteensä", $kieli), "norm", "R", "B");					

						$h-=$pdf->ln();
						$pdf->write($h, 1, 0, $s[0], t("Kommentti", $kieli), "norm", "L", "B");
						$pdf->write($h, 1, $s[0], $s[1], t("Toimituspvm", $kieli), "norm", "R", "B");			
						$pdf->write($h, 1, $s[1], $s[2], t("Ale", $kieli), "norm", "R", "B");

						$h-=$pdf->ln(1.5);

						while ($row = mysql_fetch_array($riresult)) {

							//	Etsitään käännöksiä
							if (strtolower($kieli) != strtolower($yhtiorow['kieli'])) {
								$laji = 'nimitys_'.strtolower($kieli);
								$query = "SELECT selite FROM tuotteen_avainsanat WHERE yhtio = '$kukarow[yhtio]' and laji = '$laji' and tuoteno = '$row[toim_tuoteno]' LIMIT 1";
								$nimresult = mysql_query($query) or pupe_error($query);
								if (mysql_num_rows($nimresult)>0) {
									$nimrow=mysql_fetch_array($nimresult);
									$row['toim_nimitys']=$nimrow['selite'];
								}
							}

							if($h < 20) {
								$pdf = alku($pdf, $laskurow,$kieli);
								$h = 190;
							}
							
							//	Tarkastetaan olisiko toimittajalla yksikkö!
							$query = "	SELECT toim_yksikko
										FROM tuotteen_toimittajat
										WHERE yhtio = '$kukarow[yhtio]'
										and tuoteno = '$row[tuoteno]'
										and liitostunnus = '$laskurow[liitostunnus]'
										LIMIT 1";
							$rarres = mysql_query($query) or pupe_error($query);
							$rarrow	 = mysql_fetch_array($rarres);

							//	Jos toimittajalla on yksikkö ohitetaan tuotteen yksikkö
							if($rarrow["toim_yksikko"]!= "") {
								$row["yksikko"] = $rarrow["toim_yksikko"];
							}

							$pdf->write($h, 1, 0, $s[0], $row["toim_tuoteno"]." ".$row["toim_nimitys"], "norm", "L", "");
							$pdf->write($h, 1, $s[0], $s[1], $row["varattu"]." ".strtolower($row["yksikko"]), "norm", "R", "");
							$pdf->write($h, 1, $s[1], $s[2], money_format('%!i',$row["hinta"]), "norm", "R", "");
							$pdf->write($h, 1, $s[2], 0, money_format('%!i',$row["rivihinta"]), "norm", "R", "");				
							$h-=$pdf->ln();
							$pdf->write($h, 1, $s[0], $s[1], strftime("%x",strtotime($row["toimaika"])), "snadi", "R", "I");
							if($row["ale"] > 0) {
								$pdf->write($h, 1, $s[1], $s[2], $row["ale"]."%", "snadi", "R", "I");
							}

							$ei_valia = "";
							if($row["kommentti"] != "") {
								if($pdf->pt_mm($pdf->writeStrlen($row["kommentti"], "snadi")) > $s[0]) {
									$h-=$pdf->ln();						
									$pdf->write($h, 2, 0, $s[0], "* ".$row["kommentti"], "snadi", "L", "I");	
									$h-=$pdf->ln();
								} 
								else {
									$pdf->write($h, 1, 0, $s[0], "* ".$row["kommentti"], "snadi", "L", "I");	
									$h-=$pdf->ln();
								}
								$ei_valia = "OK";
							}

							$summa += $row["rivihinta"];
							if($ei_valia == "OK") {
								$h-=$pdf->ln(0.5);
							}
							else {
								$h-=$pdf->ln(1.5);
							}
						}
						$grand_total+=$summa;
						$h-=$pdf->ln(3);

					}			

					$pdf->write($h, 1, $s[1], $s[2], t("Tilaus yhteensä",$kieli).": ", "norm", "R", "B");
					$pdf->write($h, 1, $s[2], 0, money_format('%i',$grand_total), "norm", "R", "B");
					$h-=$pdf->ln(4);

					if($h < 50) {
						$pdf = alku($pdf, $laskurow,$kieli);
						$h = 190;
					}

					if($laskurow["sisviesti1"] != "") {
						//	Maksusopimus
						$pdf->write($h, 1, 0, 0, t("LISÄTIEDOT", $kieli), "norm", "L", "B");
						$h-=$pdf->ln(1.25);
						$pdf->write($h, 4, 0, 0, $laskurow["sisviesti1"], "norm", "L", "I");
						$h-=$pdf->ln(4);			
					}

					//	Maksusopimus
					$pdf->write($h, 1, 0, 0, t("MAKSUEHTO", $kieli), "norm", "L", "B");
					$h-=$pdf->ln(1.25);
					$pdf->write($h, 1, 5, 0, "100% ".t("$laskurow[maksuteksti]"), "norm", "L", "");


					//	Allekirjoituspohjat
					if($h < 37) {
						$pdf = alku($pdf, $laskurow,$kieli);
						$h = 37;
					}
					else {
						$h = 37;
					}

					$pdf->write($h, 1, 0, 0, t("Parhain terveisin",$kieli), "norm", "L", "");
					$h-=$pdf->ln(1.5);

					$pdf->write($h, 1, 0, 0, $kukarow["nimi"], "norm", "L", "B");
					$h-=$pdf->ln();

					$pdf->write($h, 1, 0, 20, t("mobile", $kieli), "norm", "L", "");
					$pdf->write($h, 1, 20, 0, $kukarow["puhno"], "norm", "L", "");
					$h-=$pdf->ln();

					$pdf->write($h, 1, 0, 20, t("email", $kieli), "norm", "L", "");
					$pdf->write($h, 1, 20, 0, $kukarow["eposti"], "norm", "L", "");

					//	Loppuun duusataan yrityksen perustiedot
					$h = 15;
					$s=$pdf->teeSarakkeet(array(70,15,60,15));
					$pdf->write($h, 1, 0, $s[0], 		$yhtiorow["nimi"], "norm", "L", "B");
					$pdf->write($h, 1, $s[0], $s[1], 	t("Puh", $kieli).":", "norm", "L", "");
					$pdf->write($h, 1, $s[1], $s[2], 	$yhtiorow["puhelin"], "norm", "L", "");		
					if($laskurow["maa"] == "FI" or trim($laskurow["maa"]) == "SUOMI") {
						$pdf->write($h, 1, $s[2], $s[3], 	t("Y-tunnus", $kieli).":", "norm", "L", "");					
						$pdf->write($h, 1, $s[3], 0, 		$yhtiorow["ytunnus"], "norm", "R", "");		
					}
					else {
						$pdf->write($h, 1, $s[2], $s[3], 	t("VAT", $kieli).":", "norm", "L", "");					
						$pdf->write($h, 1, $s[3], 0, 		$yhtiorow["maa"].sprintf("%08.8s",ereg_replace("[^0-9]","",$yhtiorow["ytunnus"])), "norm", "R", "");
					}
					$h-=$pdf->ln();

					$pdf->write($h, 1, 0, $s[1], 		$yhtiorow["osoite"], "norm", "L", "");
					$pdf->write($h, 1, $s[0], $s[1], 	t("Fax", $kieli).":", "norm", "L", "");
					$pdf->write($h, 1, $s[1], $s[2], 	$yhtiorow["fax"], "norm", "L", "");		
					if($laskurow["maa"] == "FI" or trim($laskurow["maa"]) == "SUOMI") {
						$pdf->write($h, 1, $s[2], $s[3], 	t("VAT", $kieli).":", "norm", "L", "");					
						$pdf->write($h, 1, $s[3], 0, 		$yhtiorow["maa"].sprintf("%08.8s",ereg_replace("[^0-9]","",$yhtiorow["ytunnus"])), "norm", "R", "");		
					}		
					$h-=$pdf->ln();

					$pdf->write($h, 1, 0, $s[1], 		$yhtiorow["maa"].$yhtiorow["postino"]." ".$yhtiorow["postitp"], "norm", "L", "");
					$pdf->write($h, 1, $s[0], $s[1], 	t("Web", $kieli).":", "norm", "L", "");
					$pdf->write($h, 1, $s[1], $s[2], 	$yhtiorow["www"], "norm", "L", "");		
					$h-=$pdf->ln();

					$pdf->write($h, 1, 0, $s[0], 		$yhtiorow["maa"], "norm", "L", "");
					$pdf->write($h, 1, $s[0], $s[1], 	t("Email", $kieli).":", "norm", "L", "");
					$pdf->write($h, 1, $s[1], $s[2], 	$yhtiorow["email"], "norm", "L", "");		
					// Tulostetaan sivu
					//keksitään uudelle failille joku varmasti uniikki nimi:
					list($usec, $sec) = explode(' ', microtime());
					mt_srand((float) $sec + ((float) $usec * 100000));
					$pdf_ostofilenimi = "/tmp/Osto-".md5(uniqid(mt_rand(), true)).".pdf";

					//duusataan sivunumerot!
					foreach($pdf->objects as $key => $value) {
						if($value["type"] == "page") {
							$pages[] = $key;
						}
					}

					foreach($pages as $key => $value) {
						$pdf->write(270, 2, 0, 0, ($key+1)."/".count($pages), "snadi", "R", "I", $value);
					}

					//kirjoitetaan pdf faili levylle..
					$fh = fopen($pdf_ostofilenimi, "w");
					if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF create error $pdf_ostofilenimi");
					fclose($fh);
										
					// itse print komento...
					if ($komento == 'email' or substr($komento,0,12) == 'asiakasemail') {
						$liite = $pdf_ostofilenimi;
						if($laskurow["tunnusnippu"] > 0) {
							$kutsu = "Ostotilaus $laskurow[tunnusnippu]/$laskurow[tunnus]";
						}
						else {
							$kutsu = "Ostotilaus $laskurow[tunnus]";				
						}

						$tulosta_ostotilaus_ulos .= t("Ostotilaus lähetetään osoitteeseen '$kukarow[eposti]'")."...<br>";

						if ($kukarow["extranet"] == '') {
							require("../inc/sahkoposti.inc");
						}
						else {
							require("sahkoposti.inc");
						}
					}
					elseif ($tee == 'NAYTATILAUS') {
						//Työnnetään tuo pdf vaan putkeen!
						echo file_get_contents($pdf_ostofilenimi);
					}
					elseif ($komento != '' and $komento != 'edi') {
						//	Haetaan kirjoittimen nimi..
						$querykieli = "	select *
										from kirjoittimet
										where yhtio='$kukarow[yhtio]' and komento='$komento'";
						$kires = mysql_query($querykieli) or pupe_error($querykieli);
						if(mysql_num_rows($kires) > 0) {
							$kirow = mysql_fetch_array($kires);
							$tulosta_ostotilaus_ulos .= t("Ostotilaus tulostuu kirjoittimelle").": $kirow[kirjoitin]<br>\n";						
							
							$tulosta_ostotilaus_ulos .= t("Ostotilaus tulostuu")."...<br>";
							unset($returnvalue);
							$line = exec("$komento $pdf_ostofilenimi", $output, $returnvalue);
						}
						if(mysql_num_rows($result) == 0 or $returnvalue!=0) {
							$tulosta_ostotilaus_ulos .= "<font class='error'>".t("Ostotilauksen tulostus epäonnistui")."!!!</font><br>\n";	
						}
					}

					//poistetaan tmp file samantien kuleksimasta...
					system("rm -f $pdf_ostofilenimi");				
				}
			}
		
			tulosta_ostotilaus($otunnus, $komento["Ostotilaus"], $kieli = "", $tee);
		}
		else {
			require_once('../pdflib/phppdflib.class.php');

			$pdf = new pdffile;
			$pdf->set_default('margin', 0);
			$rectparam["width"] = 0.3;

			if (!function_exists('alku')) {
				function alku () {
					global $pdf, $image, $laskurow, $yhtiorow, $kukarow, $sivu, $rectparam, $kieli, $maa;

					$firstpage = $pdf->new_page("a4");
					$pdf->enable('template');
					$tid = $pdf->template->create();
					$pdf->template->size($tid, 600, 830);

					$p["height"] = 10;
					$p["font"] = "Times-Roman";

					$pieni["height"] = 8;
					$pieni["font"] = "Times-Roman";

		            if (strtoupper($yhtiorow["maa"]) == 'FI') {
	                    $maa = "FI ";
		            }
		            else {
	                    $maa = "";
		            }

					$pdf->draw_rectangle(827, 20,  780, 580, 				$firstpage, $rectparam);
					$pdf->draw_text(30, 815, $yhtiorow["nimi"], 			$firstpage);
					$pdf->draw_text(250, 815, t("Ostotilaus", $kieli), 		$firstpage);
					$pdf->draw_text(400, 815, t("Tilausnumero", $kieli).": ".$laskurow["tunnus"], $firstpage, $p);

					$pdf->draw_text(30,  800, t("Asiaa hoitaa", $kieli), 		$firstpage, $pieni);
					$pdf->draw_text(30,  790, $kukarow["nimi"], 				$firstpage, $p);
					$pdf->draw_text(250, 800, t("Päivämäärä", $kieli), 			$firstpage, $pieni);
					$pdf->draw_text(250, 790, date("j").".".date("n").".".date("Y"), 	$firstpage, $p);
					$pdf->draw_text(400, 800, t("Sivu", $kieli), 				$firstpage, $pieni);
					$pdf->draw_text(400, 790, $sivu, 							$firstpage, $p);

					$pdf->draw_rectangle(780, 20,  750, 300, 				$firstpage, $rectparam);
					$pdf->draw_rectangle(780, 300, 750, 580, 				$firstpage, $rectparam);
					$pdf->draw_text(30, 770,  t("Viite", $kieli), 			$firstpage, $pieni);
					if (trim($laskurow["viesti"]) == "") {
						$pdf->draw_text(30, 755,  $laskurow["tunnus"], 			$firstpage, $p);
					}
					else {
						$pdf->draw_text(30, 755,  $laskurow["tunnus"]." ".substr(trim($laskurow["viesti"]),0,50), 			$firstpage, $p);
					}

					if (trim($laskurow["comments"]) != "") {
						$pdf->draw_text(310, 767,  trim($laskurow["comments"]), 			$firstpage, $p);
					}

					$pdf->draw_text(310, 755, t("Olkaa hyvä ja kirjoittakaa tilausnumero lähetteeseen, laskuun ja kirjeisiin", $kieli).".", $firstpage, $pieni);

					$pdf->draw_rectangle(750, 20,  670, 300, 				$firstpage, $rectparam);
					$pdf->draw_text(30, 740, t("Toimittaja", $kieli),	 	$firstpage, $pieni);
					$pdf->draw_text(30, 720, $laskurow["nimi"], 			$firstpage, $p);
					$pdf->draw_text(30, 710, $laskurow["osoite"], 			$firstpage, $p);
					$pdf->draw_text(30, 700, $laskurow["postino"]." ".$laskurow["postitp"], $firstpage, $p);
					$pdf->draw_text(30, 690, $laskurow["maa"], 				$firstpage, $p);

					$pdf->draw_rectangle(750, 300, 670, 580, 											$firstpage, $rectparam);
					$pdf->draw_text(310, 740, t("Vastaanottaja", $kieli).":", 							$firstpage, $pieni);
					$pdf->draw_text(310, 720, $laskurow["toim_nimi"], 									$firstpage, $p);
					$pdf->draw_text(310, 710, $laskurow["toim_osoite"], 								$firstpage, $p);
					$pdf->draw_text(310, 700, $laskurow["toim_postino"]." ".$laskurow["toim_postitp"], 	$firstpage, $p);
					$pdf->draw_text(310, 690, $laskurow["toim_maa"],			 						$firstpage, $p);
					$pdf->draw_text(310, 680, t("Y-tunnus", $kieli).": ".$maa.$yhtiorow["ytunnus"],$firstpage, $p);

					$pdf->draw_rectangle(670, 20,  570, 300, 				$firstpage, $rectparam);
					$pdf->draw_text(30, 660, t("Kuljetus", $kieli), 		$firstpage, $pieni);
					$pdf->draw_text(30, 645, $laskurow["kuljetus"], 		$firstpage, $p);
					$pdf->draw_rectangle(640, 20,  570, 300, 				$firstpage, $rectparam);
					$pdf->draw_text(30, 630, t("Huolitsija", $kieli), 		$firstpage, $pieni);
					$pdf->draw_text(30, 615, $laskurow["huolitsija"], 		$firstpage, $p);
					$pdf->draw_rectangle(610, 20,  570, 300, 				$firstpage, $rectparam);
					$pdf->draw_text(30, 600, t("Jakelu", $kieli), 			$firstpage, $pieni);
					$pdf->draw_text(30, 583, $laskurow["jakelu"], 			$firstpage, $p);

					$pdf->draw_rectangle(670, 300, 570, 580, 				$firstpage, $rectparam);
					$pdf->draw_text(310, 660, t("Toimitusehto", $kieli), 	$firstpage, $pieni);
					$pdf->draw_text(310, 645, $laskurow["toimitusehto"], 	$firstpage, $p);
					$pdf->draw_text(500, 660, t("Toimitusaika", $kieli), 	$firstpage, $pieni);
					$pdf->draw_text(500, 645, tv1dateconv($laskurow["toimaika"]), 		$firstpage, $p);
					$pdf->draw_rectangle(640, 300, 570, 580,			 	$firstpage, $rectparam);
					$pdf->draw_text(310, 630, t("Maksuehto", $kieli), 		$firstpage, $pieni);
					$pdf->draw_text(310, 615, $laskurow["maksuteksti"], 	$firstpage, $p);
					$pdf->draw_rectangle(610, 300, 570, 580, $firstpage, 	$rectparam);
					$pdf->draw_text(310, 600, t("Tavaraerän merkinnät", $kieli), 	$firstpage, $pieni);
					$pdf->draw_text(310, 583, $laskurow[""], 				$firstpage, $p);


					$pdf->draw_text(45,  553, t("Tuotenumero", $kieli),		$firstpage, $pieni);
					$pdf->draw_text(360, 553, t("Määrä", $kieli),			$firstpage, $pieni);
					$pdf->draw_text(413, 553, t("Hinta", $kieli),			$firstpage, $pieni);
					$pdf->draw_text(463, 553, t("Ale", $kieli),			$firstpage, $pieni);
					$pdf->draw_text(512, 553, t("Yhteensä", $kieli),		$firstpage, $pieni);

					$pdf->draw_rectangle(570, 20, 70, 580, 					$firstpage, $rectparam);

					return($firstpage);
				}
			}

			if (!function_exists('loppu')) {
				function loppu ($firstpage, $total, $laitetaanko_yhteensarivi) {

					global $pdf, $laskurow, $yhtiorow, $kukarow, $sivu, $rectparam, $kieli;

					$norm["height"] = 10;
					$norm["font"] = "Times-Roman";

					$pieni["height"] = 8;
					$pieni["font"] = "Times-Roman";

					//yhteensärivi
					if ($laitetaanko_yhteensarivi == 1) {
						$pdf->draw_rectangle(110, 394, 90, 580,	$firstpage, $rectparam);
						$pdf->draw_text(400, 94,  t("YHTEENSÄ", $kieli).":  ".$total." ".$laskurow["valkoodi"],	$firstpage, $norm);
					}
					//Pankkiyhteystiedot
					$pdf->draw_rectangle(90, 20, 20, 580,	$firstpage, $rectparam);

					$pdf->draw_text(45, 82,  t("Pankkiyhteys", $kieli),	$firstpage, $pieni);
					$pdf->draw_text(45, 72,  $yhtiorow["pankkinimi1"],	$firstpage, $norm);
					$pdf->draw_text(105, 72,  $yhtiorow["pankkitili1"],	$firstpage, $norm);
					$pdf->draw_text(217, 72, $yhtiorow["pankkinimi2"],	$firstpage, $norm);
					$pdf->draw_text(267, 72, $yhtiorow["pankkitili2"],	$firstpage, $norm);
					$pdf->draw_text(404, 72, $yhtiorow["pankkinimi3"],	$firstpage, $norm);
					$pdf->draw_text(454, 72, $yhtiorow["pankkitili3"],	$firstpage, $norm);

					//Alimmat kolme laatikkoa, yhtiötietoja
					$pdf->draw_rectangle(70, 20, 20, 580,	$firstpage, $rectparam);
					$pdf->draw_rectangle(70, 207, 20, 580,	$firstpage, $rectparam);
					$pdf->draw_rectangle(70, 394, 20, 580,	$firstpage, $rectparam);

					$pdf->draw_text(45, 55, $yhtiorow["nimi"],				$firstpage, $pieni);
					$pdf->draw_text(45, 45, $yhtiorow["osoite"],			$firstpage, $pieni);
					$pdf->draw_text(45, 35, $yhtiorow["postino"]."  ".$yhtiorow["postitp"],	$firstpage, $pieni);
					$pdf->draw_text(45, 25, $yhtiorow["maa"],				$firstpage, $pieni);

					$pdf->draw_text(217, 55, t("Puhelin", $kieli).":",		$firstpage, $pieni);
					$pdf->draw_text(267, 55, $yhtiorow["puhelin"],			$firstpage, $pieni);
					$pdf->draw_text(217, 45, t("Telefax", $kieli).":",		$firstpage, $pieni);
					$pdf->draw_text(267, 45, $yhtiorow["fax"],				$firstpage, $pieni);
					$pdf->draw_text(217, 35, t("Sähköposti", $kieli).":",	$firstpage, $pieni);
					$pdf->draw_text(267, 35, $yhtiorow["email"],			$firstpage, $pieni);

					$pdf->draw_text(404, 55, t("Y-tunnus", $kieli).":",		$firstpage, $pieni);
					$pdf->draw_text(464, 55, $yhtiorow["ytunnus"],			$firstpage, $pieni);
					$pdf->draw_text(404, 45, t("Kotipaikka", $kieli).":",	$firstpage, $pieni);
					$pdf->draw_text(464, 45, $yhtiorow["kotipaikka"],		$firstpage, $pieni);
				}
			}

			if (!function_exists('print_pdf')) {
				function print_pdf () {
					global $pdf, $kukarow, $yhtiorow, $komento, $kieli, $tee, $tulosta_ostotilaus_ulos;

					if ($komento["Ostotilaus"] != '' or $tee == 'NAYTATILAUS') {
						//keksitään uudelle failille joku varmasti uniikki nimi:
						list($usec, $sec) = explode(' ', microtime());
						mt_srand((float) $sec + ((float) $usec * 100000));
						$pdffilenimi = "/tmp/Ostotilaus-".md5(uniqid(mt_rand(), true)).".pdf";

						//kirjoitetaan pdf faili levylle..
						$fh = fopen($pdffilenimi, "w+");
						if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus epäonnistui $pdffilenimi");
						fclose($fh);

						// itse print komento...
						if ($komento["Ostotilaus"] == 'email') {
							$liite = $pdffilenimi;
							$kutsu = "Ostotilaus";

							require("../inc/sahkoposti.inc");
							$tulosta_ostotilaus_ulos .= t("Tilaus tulostuu")."...<br>";
						}
						elseif ($tee == 'NAYTATILAUS') {
	            			//Työnnetään tuo pdf vaan putkeen!
	            			echo file_get_contents($pdffilenimi);
	            		}
						elseif ($komento["Ostotilaus"] != '') {
							$line = exec("$komento[Ostotilaus] $pdffilenimi");
						    $tulosta_ostotilaus_ulos .= t("Tilaus tulostuu")."...<br>";
						}

	            		$tee = "";
						//poistetaan tmp file samantien kuleksimasta...
						system("rm -f $pdffilenimi");
					}
				}
			}

			$query = "	SELECT toim_tuoteno, toim_nimitys, tuote.tuoteno, round(tilausrivi.varattu*if(tuotteen_toimittajat.tuotekerroin=0,1,tuotteen_toimittajat.tuotekerroin),2) varattu,
						tilausrivi.hinta, tilausrivi.ale, round(tilausrivi.varattu*if(tuotteen_toimittajat.tuotekerroin=0,1,tuotteen_toimittajat.tuotekerroin)*tilausrivi.hinta*(1-ale/100),2) rivihinta, 
						substring(tuote.nimitys,1,80) nimitys, kommentti, tilausrivi.yksikko, toimaika
						FROM tilausrivi
						join tuote USING (yhtio, tuoteno)
						join tuotteen_toimittajat on tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno and tuotteen_toimittajat.liitostunnus = '$laskurow[liitostunnus]'
						WHERE tilausrivi.otunnus = '$otunnus'
						and tilausrivi.yhtio='$kukarow[yhtio]'
						ORDER BY tilausrivi.toimaika, toim_tuoteno";
			$result = mysql_query($query) or pupe_error($query);

			$kala = 535;
			$lask = 1;
			$total = 0;
			$sivu = 1;

			$firstpage = alku();

			while ($row = mysql_fetch_array($result)) {
				if ($lask >= 34) {
					$sivu++;
					loppu($firstpage, $total, 0);

					$firstpage = alku();
					$kala = 540;
					$lask = 1;
				}

				$p["height"] = 10;
				$p["font"] = "Times-Roman";

				$pieni["height"] = 8;
				$pieni["font"] = "Times-Roman";
				
				//	Tarkastetaan olisiko toimittajalla yksikkö!
				$query = "	SELECT toim_yksikko
							FROM tuotteen_toimittajat
							WHERE yhtio = '$kukarow[yhtio]'
							and tuoteno = '$row[tuoteno]'
							and liitostunnus = '$laskurow[liitostunnus]'
							LIMIT 1";
				$rarres = mysql_query($query) or pupe_error($query);
				$rarrow	 = mysql_fetch_array($rarres);

				//	Jos toimittajalla on yksikkö ohitetaan tuotteen yksikkö
				if($rarrow["toim_yksikko"]!= "") {
					$row["yksikko"] = $rarrow["toim_yksikko"];
				}

				$pdf->draw_text(45,  $kala, $row["toim_tuoteno"],				$firstpage, $p);
				$pdf->draw_text(170, $kala, "(".$row["tuoteno"].")",			$firstpage, $p);
				
				if ($row["toimaika"] != $laskurow["toimaika"]) {
						$pdf->draw_text(285, $kala, tv1dateconv($row["toimaika"]),		$firstpage, $p);
				}
				
				$pdf->draw_text(360, $kala, sprintf('%.2f',$row["varattu"])." ".$row["yksikko"], 	$firstpage, $p);
				$pdf->draw_text(413, $kala, sprintf('%.2f',$row["hinta"]),		$firstpage, $p);
				$pdf->draw_text(463, $kala, sprintf('%.2f',$row["ale"])."%",		$firstpage, $p);
				$pdf->draw_text(512, $kala, sprintf('%.2f',$row["rivihinta"]), 	$firstpage, $p);

	            if ($nimitykset != "") {

					// jos meillä ei ole toimittajan nimitystä ja toimittaja kieli on eri kuin meillä, yritetään etsiä oikeankieliset nimitykset
	                if (strtoupper($kieli) != strtoupper($yhtiorow["kieli"]) and $row["toim_nimitys"] == "") {

	                    $nimkieli = "nimitys_$kieli";

	                    $query = "SELECT substring(selite,1,80) selite FROM tuotteen_avainsanat WHERE yhtio = '$kukarow[yhtio]' and laji = '$nimkieli' and tuoteno = '$row[tuoteno]' limit 1";
	                    $resultnim = mysql_query($query) or pupe_error($query);

	                    if (mysql_num_rows($resultnim) == 1) {
	                        $rownim = mysql_fetch_array($resultnim);
	                        $row["nimitys"] = $rownim["selite"];
	                    }
	                }

					// jos meillä on toimittajan nimitys syötetty käytetään sitä aina
					if ($row["toim_nimitys"] != "") $row["nimitys"] = $row["toim_nimitys"];

	                $kala = $kala - 13;
	                $pdf->draw_text(100,  $kala, $row["nimitys"], $firstpage, $pieni);
	                $lask++;
	            }

				if (trim($row["kommentti"]) != '') {
					$kala = $kala - 13;
					$pdf->draw_text(100,  $kala, $row["kommentti"],	$firstpage, $p);
					$lask++;
				}

				$kala = $kala - 13;
				$total += $row["rivihinta"];
				$lask++;
			}

			//Viimeiselle sivulle myös lopputekstit
			loppu($firstpage, $total, 1);

			//kutsutaan lopuksi vielä print_pdf funktiota
			print_pdf();
		}
	}
	
	if ($silent == "") {
		echo $tulosta_ostotilaus_ulos;
	}
?>
