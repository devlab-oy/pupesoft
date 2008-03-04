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
		$query    = "SELECT * from toimi where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[liitostunnus]'";
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
		
		if($yhtiorow["ostotilaustyyppi"] == 1) {
			require_once('pdflib/pupepdf.class.php');

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
						$hinta_riv = "round(tilausrivi.hinta/$laskurow[vienti_kurssi], $yhtiorow[hintapyoristys])";
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
						
						if($yhtiorow["laskutus_nimi"] == "") {
							$pdf->write($h, 1, $s[0], 0, $yhtiorow["nimi"], "norm", "L", "");
							$h-=$pdf->ln();
							$pdf->write($h, 1, $s[0], 0, $yhtiorow["osoite"], "norm", "L", "");
							$h-=$pdf->ln();
							$pdf->write($h, 1, $s[0], 0, $yhtiorow["postino"]." ".$yhtiorow["postitp"], "norm", "L", "");
							$h-=$pdf->ln();
							$pdf->write($h, 1, $s[0], 0, t($yhtiorow["maa"]), "norm", "L", "");
						}
						else {
							$pdf->write($h, 1, $s[0], 0, $yhtiorow["laskutus_nimi"], "norm", "L", "");
							$h-=$pdf->ln();
							$pdf->write($h, 1, $s[0], 0, $yhtiorow["laskutus_osoite"], "norm", "L", "");
							$h-=$pdf->ln();
							$pdf->write($h, 1, $s[0], 0, $yhtiorow["laskutus_postino"]." ".$yhtiorow["laskutus_postitp"], "norm", "L", "");
							$h-=$pdf->ln();
							$pdf->write($h, 1, $s[0], 0, t($yhtiorow["laskutus_maa"]), "norm", "L", "");							
						}
						$h-=$pdf->ln();	
																							
						$h-=$pdf->ln(1.5);

						$pdf->write($h, 1, 0, 0, t("Merkitkää tilausnumero lähetteeseen, laskuun sekä muihin dokumentteihin. Hinnat eivät sisällä arvonlisäveroa.", $kieli), "norm", "L", "");
						
						return $pdf;
					}

					$pdf=alku($pdf, $laskurow,$kieli);
					$h = 190;

					//	Käsitellään tilausrivit
					$query = "	SELECT toim_tuoteno, toim_nimitys, tuote.tuoteno, round(tilausrivi.varattu*if(tuotteen_toimittajat.tuotekerroin=0,1,tuotteen_toimittajat.tuotekerroin),2) varattu, toimaika,
								tilausrivi.hinta, tilausrivi.ale, round(tilausrivi.varattu*if(tuotteen_toimittajat.tuotekerroin=0,1,tuotteen_toimittajat.tuotekerroin)*tilausrivi.hinta*(1-ale/100),'$yhtiorow[hintapyoristys]') rivihinta, kommentti
								FROM tilausrivi
								join tuote USING (yhtio, tuoteno)
								join tuotteen_toimittajat on tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno and tuotteen_toimittajat.liitostunnus = '$laskurow[liitostunnus]'
								WHERE tilausrivi.otunnus = '$otunnus'
								and tilausrivi.yhtio='$kukarow[yhtio]'
								and tyyppi != 'D'
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
							$pdf->write($h, 1, $s[0], $s[1], $row["varattu"]." ".strtolower(ta($kieli, "Y", $row["yksikko"])), "norm", "R", "");
							$pdf->write($h, 1, $s[1], $s[2], money_format('%!i',$row["hinta"]), "norm", "R", "");
							$pdf->write($h, 1, $s[2], 0, money_format('%!i',$row["rivihinta"]), "norm", "R", "");				
							$h-=$pdf->ln();
							$pdf->write($h, 1, $s[0], $s[1], strftime("%x",strtotime($row["toimaika"])), "snadi", "R", "I");
							if($row["ale"] > 0) {
								$pdf->write($h, 1, $s[1], $s[2], $row["ale"]."%", "snadi", "R", "I");
							}

							$ei_valia = "";
							if($row["kommentti"] != "") {
								$nb=$pdf->countParagraphHeight($row["kommentti"], "snadi", ($s[0]-5));
								$pdf->write($h, $nb, 0, $s[0], $row["kommentti"], "snadi", "L", "I");
								$h-=$pdf->ln($nb);

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

						$tulosta_ostotilaus_ulos .= t("Ostotilaus lähetetään osoitteeseen $kukarow[eposti]")."...<br>";

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

			$norm["height"] 		= 10;
			$norm["font"] 			= "Times-Roman";

			$pieni["height"] 		= 8;
			$pieni["font"] 			= "Times-Roman";

			$boldi["height"] 		= 10;
			$boldi["font"] 			= "Times-Bold";

			$pieni_boldi["height"] 	= 8;
			$pieni_boldi["font"] 	= "Times-Bold";

			$iso["height"] 			= 14;
			$iso["font"] 			= "Helvetica-Bold";

			$rectparam["width"] 	= 0.3;

			$pdf = new pdffile;
			$pdf->set_default('margin', 0);
			$rectparam["width"] = 0.3;

			if (!function_exists('alku')) {
				function alku () {
					global $pdf, $laskurow, $yhtiorow, $kukarow, $sivu, $rectparam, $pieni, $norm, $boldi, $pieni_boldi, $iso, $kieli, $kala;
		
					//PDF parametrit
					if (!isset($pdf)) {
						$pdf = new pdffile;
						$pdf->set_default('margin-top', 	0);
						$pdf->set_default('margin-bottom', 	0);
						$pdf->set_default('margin-left', 	0);
						$pdf->set_default('margin-right', 	0);
					}

					$thispage = $pdf->new_page("a4");

					//oletukset viivakoodeille
					$output   = "png";
					$width    = "140";
					$height   = "20";
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
					
					unset($data);		
					if( (int) $yhtiorow["lasku_logo"] > 0) {
						$liite = hae_liite($yhtiorow["lasku_logo"], "Yllapito", "array");
						$data = $liite["data"];
						$isizelogo[0] = $liite["image_width"];
						$isizelogo[1] = $liite["image_height"];
						unset($liite);
					}
					elseif(file_exists($yhtiorow["lasku_logo"])) {
						$filename = $yhtiorow["lasku_logo"];

						$fh = fopen($filename, "r");
						$data = fread($fh, filesize($filename));
						fclose($fh);

						$isizelogo = getimagesize($yhtiorow["lasku_logo"]);
					}

					if($data) {
						$image = $pdf->jfif_embed($data);
					
						if(!$image) {
							echo t("Logokuvavirhe");
						}
						else {

							$logoparam = array();

							if ($isizelogo[0] > $isizelogo[1] and $isizelogo[1] * (240 / $isizelogo[0]) <= 50) {
								$logoparam['scale'] = 240 / $isizelogo[0];
							}
							else {
								$logoparam['scale'] = 50  / $isizelogo[1];
							}

							$placement = $pdf->image_place($image, 830-($logoparam['scale']*$isizelogo[1]), 20, $thispage, $logoparam);
						}
					}
					else {
						$pdf->draw_text(30, 815, $yhtiorow["nimi"], 	$thispage, $norm);
						$pdf->draw_text(30, 805, $yhtiorow["osoite"], 	$thispage, $norm);
						$pdf->draw_text(30, 795, $yhtiorow["postino"]." ".$yhtiorow["postitp"], 	$thispage, $norm);
						$pdf->draw_text(30, 785, $yhtiorow["maa"], 		$thispage, $norm);
					}

					$pdf->draw_text(310,815, t("Ostotilaus", $kieli), 			$thispage, $iso);		
					$pdf->draw_text(310, 803, t("Sivu", $kieli), 				$thispage, $norm);

					//Vasen sarake
					//$pdf->draw_rectangle(737, 20,  674, 300, $thispage, $rectparam);
					$pdf->draw_text(50, 729, t("Toimittaja", $kieli), 	$thispage, $pieni);
					$pdf->draw_text(50, 717, $laskurow["nimi"], 			$thispage, $norm);
					$pdf->draw_text(50, 707, $laskurow["nimitark"],			$thispage, $norm);
					$pdf->draw_text(50, 697, $laskurow["osoite"], 			$thispage, $norm);
					$pdf->draw_text(50, 687, $laskurow["postino"]." ".$laskurow["postitp"], $thispage, $norm);
					$pdf->draw_text(50, 677, $laskurow["maa"], 				$thispage, $norm);


					//$pdf->draw_rectangle(674, 20,  611, 300, $thispage, $rectparam);
					$pdf->draw_text(50, 656, t("Vastaanottaja", $kieli), 	$thispage, $pieni);
					$pdf->draw_text(50, 644, $laskurow["toim_nimi"], 		$thispage, $norm);
					$pdf->draw_text(50, 634, $laskurow["toim_nimitark"], 	$thispage, $norm);
					$pdf->draw_text(50, 624, $laskurow["toim_osoite"],	 	$thispage, $norm);
					$pdf->draw_text(50, 614, $laskurow["toim_postino"]." ".$laskurow["toim_postitp"], $thispage, $norm);
					$pdf->draw_text(50, 604, $laskurow["toim_maa"], 		$thispage, $norm);

					$query = "	SELECT *
								FROM toimi
								WHERE yhtio = '$kukarow[yhtio]'
								and tunnus 	= '$laskurow[liitostunnus]'
								LIMIT 1";
					$rarres = mysql_query($query) or pupe_error($query);
					$toimirow = mysql_fetch_array($rarres);
					
					//Oikea sarake
					$pdf->draw_rectangle(800, 300, 779, 580, $thispage, $rectparam);
					$pdf->draw_rectangle(800, 420, 779, 580, $thispage, $rectparam);
					$pdf->draw_text(310, 792, t("Tilausnumero", $kieli), 	$thispage, $pieni);
					$pdf->draw_text(310, 782, $laskurow["tunnus"],			$thispage, $boldi);
					$pdf->draw_text(430, 792, t("Toimittajan fax", $kieli), $thispage, $pieni);
					$pdf->draw_text(430, 782, $toimirow["fax"],				$thispage, $norm);

					$pdf->draw_rectangle(779, 300, 758, 580, $thispage, $rectparam);
					$pdf->draw_rectangle(779, 420, 758, 580, $thispage, $rectparam);
					$pdf->draw_text(310, 771, t("Y-tunnus", $kieli), 		$thispage, $pieni);

					//jos on suomalainen yritys tehdään ytunnus nätiks
					if (strtoupper($yhtiorow["maa"])== 'FI') {
						//muutetaan ytunnus takas oikean näköseks
						$ytunpit = 8-strlen($yhtiorow["ytunnus"]);

						if ($ytunpit > 0) {
							$uytunnus = $yhtiorow["ytunnus"];
							while ($ytunpit > 0) {
							    $uytunnus = "0".$uytunnus; $ytunpit--;
							}
						}
						else {
							$uytunnus = $yhtiorow["ytunnus"];
						}

						$uytunnus = substr($uytunnus,0,7)."-".substr($uytunnus,7,1);
					}
					else {
						$uytunnus = $yhtiorow["ytunnus"];
					}

			        if ($laskurow['yhtio_ovttunnus'] != $yhtiorow['ovttunnus'] and $laskurow['yhtio_ovttunnus'] != '') {
			            $uytunnus = $laskurow['yhtio_ovttunnus'];
			        } 
					else {
			    		$uytunnus = $yhtiorow["maa"]."-".$uytunnus;
			        }

					$pdf->draw_text(310, 761, $uytunnus, 								$thispage, $norm);
					$pdf->draw_text(430, 771, t("Päiväys", $kieli), 					$thispage, $pieni);
					$pdf->draw_text(430, 761, tv1dateconv(date("Y-m-d")), 				$thispage, $norm);

					$pdf->draw_rectangle(758, 300, 737, 580, $thispage, $rectparam);
					$pdf->draw_rectangle(758, 420, 737, 580, $thispage, $rectparam);
					$pdf->draw_text(310, 750, t("Asiaa hoitaa", $kieli), 				$thispage, $pieni);
					$pdf->draw_text(310, 740, $kukarow["nimi"], 						$thispage, $norm);

					$pdf->draw_text(430, 750, t("Toimitusaika", $kieli),				$thispage, $pieni);
					$pdf->draw_text(430, 740, tv1dateconv($laskurow["toimaika"]), 		$thispage, $norm);


					$pdf->draw_rectangle(737, 300, 716, 580, $thispage, $rectparam);
					$pdf->draw_rectangle(737, 420, 716, 580, $thispage, $rectparam);
					
					$pdf->draw_text(310, 729, t("Yhteyshenkilö", $kieli),				$thispage, $pieni);
					$pdf->draw_text(310, 719, $laskurow["tilausyhteyshenkilo"], 		$thispage, $norm);
					
					$pdf->draw_text(430, 729, t("Kuljetus", $kieli), 					$thispage, $pieni);
					$pdf->draw_text(430, 719, $laskurow["kuljetus"],	 				$thispage, $norm);
					

					$pdf->draw_rectangle(716, 300, 695, 580, $thispage, $rectparam);
					$pdf->draw_text(310, 708, t("Toimitusehto", $kieli), 				$thispage, $pieni);
					$pdf->draw_text(310, 698, $laskurow["toimitusehto"], 				$thispage, $norm);

					$pdf->draw_rectangle(695, 300, 674, 580, $thispage, $rectparam);
					$pdf->draw_text(310, 687, t("Huolitsija", $kieli), 					$thispage, $pieni);
					$pdf->draw_text(310, 677, $laskurow["huolitsija"], 					$thispage, $norm);

					$pdf->draw_rectangle(674, 300, 653, 580, $thispage, $rectparam);
					$pdf->draw_text(310, 666, t("Maksuehto", $kieli), 					$thispage, $pieni);
					$pdf->draw_text(310, 656, $laskurow["maksuteksti"], 				$thispage, $norm);

					$pdf->draw_rectangle(653, 300, 632, 580, $thispage, $rectparam);
					$pdf->draw_text(310, 645, t("Jakelu", $kieli), 						$thispage, $pieni);
					$pdf->draw_text(310, 635, $laskurow["jakelu"], 						$thispage, $norm);

					$pdf->draw_rectangle(632, 300, 611, 580, $thispage, $rectparam);
					$pdf->draw_text(310, 624, t("Tilausviite", $kieli), 				$thispage, $pieni);
					$pdf->draw_text(310, 614, $laskurow["viesti"], 						$thispage, $norm);
					
					$pdf->draw_text(310, 600, t("Olkaa hyvä ja kirjoittakaa tilausnumero lähetteeseen, laskuun ja kirjeisiin", $kieli).".", $thispage, $pieni);
					
					$komm = "";
					
					if (trim($laskurow['sisviesti1']) != '') {
						$komm .= "\n".t("Kommentti", $kieli).":###".wordwrap(str_replace("\n","\n###", $laskurow['sisviesti1']), 90, "\n###");							
					}
					
					//Tulostetaan laskun kommentti
					if (trim($komm) != '') {
						$kommentit = explode("\n", trim($komm));

						$pohja  = 577;
						$maxoik = 0;

						foreach($kommentit as $kommentti) {
							if(strpos($kommentti, "###") !== FALSE) {
								list($o, $v) = explode("###", $kommentti);

								$oikpos = $pdf->strlen($o, $boldi);

								if ($oikpos > $maxoik) {
									$maxoik = $oikpos;
								}
							}
						}

						foreach($kommentit as $kommentti) {

							if (strpos($kommentti, "###") !== false) {
								list($o, $v) = explode("###", $kommentti);

								$pdf->draw_text(35, $pohja, trim($o), $thispage, $boldi);
								$pdf->draw_text(35+$maxoik+5, $pohja, trim($v), $thispage, $norm);
							}
							else {
								$pdf->draw_text(35, $pohja, trim($kommentti), $thispage, $norm);
							}

							$pohja = $pohja - 10;
						}

						$kala = $pohja - 25;

					}
					else {
						$kala = 565;
					}

					$pdf->draw_rectangle($kala+30, 20, 70, 580, $thispage, $rectparam);
					
					$pdf->draw_rectangle($kala+30, 20,  $kala+10, 40,  	$thispage, $rectparam);
					$pdf->draw_rectangle($kala+30, 40,  $kala+10, 290, 	$thispage, $rectparam);		
					$pdf->draw_rectangle($kala+30, 290, $kala+10, 350, 	$thispage, $rectparam);	
					$pdf->draw_rectangle($kala+30, 350, $kala+10, 420, 	$thispage, $rectparam);
					$pdf->draw_rectangle($kala+30, 420, $kala+10, 480, 	$thispage, $rectparam);
					$pdf->draw_rectangle($kala+30, 480, $kala+10, 520, 	$thispage, $rectparam);
					$pdf->draw_rectangle($kala+30, 520, $kala+10, 580, 	$thispage, $rectparam);
					$pdf->draw_text(25,  $kala+15, "#",							$thispage, $norm);
					$pdf->draw_text(42,  $kala+15, t("Tuotenumero", $kieli),	$thispage, $norm);
					$pdf->draw_text(292, $kala+15, t("Toimitusaika", $kieli),	$thispage, $norm);
					$pdf->draw_text(352, $kala+15, t("Määrä", $kieli),			$thispage, $norm);
					$pdf->draw_text(422, $kala+15, t("Hinta", $kieli),			$thispage, $norm);
					$pdf->draw_text(482, $kala+15, t("Ale", $kieli),			$thispage, $norm);
					$pdf->draw_text(522, $kala+15, t("Yhteensä", $kieli),		$thispage, $norm);

					return($thispage);
				}
			}
			
			if (!function_exists('rivi')) {
				function rivi($thispage) {
					global $pdf, $laskurow, $row, $yhtiorow, $kukarow, $sivu, $page, $rectparam, $pieni, $norm, $boldi, $pieni_boldi, $iso, $kieli, $kala, $nimitykset, $rivinumerot;
					
					// viivat rivien väliin...
					$x[0] = 20;
					$x[1] = 580;
					$y[0] = $y[1] = $kala + 15 - 5;

					$pdf->draw_line($x, $y, $thispage, $rectparam);

					// Jos on paljon rivejä tehdään uusi otsikko...
					if ($kala <= 100) {
						$sivu++;

						loppu($thispage, 0);

						$page[$sivu] = alku();
						$thispage    = $page[$sivu];
					}
					
					//	Tarkastetaan olisiko toimittajalla yksikkö!
					$query = "	SELECT toim_yksikko
								FROM tuotteen_toimittajat
								WHERE yhtio 		= '$kukarow[yhtio]'
								and tuoteno 		= '$row[tuoteno]'
								and liitostunnus 	= '$laskurow[liitostunnus]'
								LIMIT 1";
					$rarres = mysql_query($query) or pupe_error($query);
					$rarrow	 = mysql_fetch_array($rarres);

					//	Jos toimittajalla on yksikkö ohitetaan tuotteen yksikkö
					if($rarrow["toim_yksikko"]!= "") {
						$row["yksikko"] = $rarrow["toim_yksikko"];
					}

					$pdf->draw_text(25,  $kala, $rivinumerot[$row["tunnus"]],							$thispage, $pieni);					
					$pdf->draw_text(45,  $kala, $row["toim_tuoteno"],									$thispage, $norm);
					$pdf->draw_text(170, $kala, "(".$row["tuoteno"].")",								$thispage, $norm);
					$pdf->draw_text(300, $kala, tv1dateconv($row["toimaika"]),							$thispage, $norm);
					
					$oikpos = $pdf->strlen(($row["varattu"]*1)." ".$row["yksikko"], $norm);					
					$pdf->draw_text(415-$oikpos, $kala, ($row["varattu"]*1)." ".$row["yksikko"], 		$thispage, $norm);
					
					$oikpos = $pdf->strlen(sprintf("%.".$yhtiorow['hintapyoristys']."f",$row["hinta"]), $norm);					
					$pdf->draw_text(470-$oikpos, $kala, sprintf("%.".$yhtiorow['hintapyoristys']."f",$row["hinta"]), 					$thispage, $norm);
										
					if ($row["ale"] != 0) {
						$oikpos = $pdf->strlen(($row["ale"]*1)."%", $norm);
						$pdf->draw_text(515-$oikpos, $kala, ($row["ale"]*1)."%", 						$thispage, $norm);
					}
					
					$oikpos = $pdf->strlen(sprintf("%.".$yhtiorow['hintapyoristys']."f",$row["rivihinta"]), $norm);					
					$pdf->draw_text(575-$oikpos, $kala, sprintf("%.".$yhtiorow['hintapyoristys']."f",$row["rivihinta"]), 				$thispage, $norm);
					
		            if ($nimitykset != "") {

						// jos meillä ei ole toimittajan nimitystä ja toimittaja kieli on eri kuin meillä, yritetään etsiä oikeankieliset nimitykset
		                if (strtoupper($kieli) != strtoupper($yhtiorow["kieli"]) and $row["toim_nimitys"] == "") {

		                    $nimkieli = "nimitys_$kieli";

		                    $query = "	SELECT substring(selite,1,80) selite 
										FROM tuotteen_avainsanat 
										WHERE yhtio = '$kukarow[yhtio]' 
										and laji 	= '$nimkieli' 
										and tuoteno = '$row[tuoteno]' 
										limit 1";
		                    $resultnim = mysql_query($query) or pupe_error($query);

		                    if (mysql_num_rows($resultnim) == 1) {
		                        $rownim = mysql_fetch_array($resultnim);
		                        $row["nimitys"] = $rownim["selite"];
		                    }
		                }

						// jos meillä on toimittajan nimitys syötetty käytetään sitä aina
						if ($row["toim_nimitys"] != "") $row["nimitys"] = $row["toim_nimitys"];

		                $kala = $kala - 15;
		                $pdf->draw_text(45,  $kala, $row["nimitys"], $thispage, $norm);
		                $lask++;
		            }

					if (trim($row["kommentti"]) != '') {
						foreach(explode("\n", wordwrap(trim($row["kommentti"]), 90, "\n")) as $kommentti) {
							$kala = $kala - 15;
							$pdf->draw_text(45,  $kala, $kommentti,	$thispage, $norm);
							$lask++;
						}	
					}

					$kala = $kala - 15;
				}
			}

			if (!function_exists('loppu')) {
				function loppu ($thispage, $total, $laitetaanko_yhteensarivi) {
					global $pdf, $laskurow, $yhtiorow, $kukarow, $page, $sivu, $rectparam, $kieli, $kala, $norm, $pieni;

					//Alimmat kolme laatikkoa, yhtiötietoja
					$pdf->draw_rectangle(70, 20, 20, 580,	$thispage, $rectparam);
					$pdf->draw_rectangle(70, 207, 20, 580,	$thispage, $rectparam);
					$pdf->draw_rectangle(70, 394, 20, 580,	$thispage, $rectparam);

					$pdf->draw_text(30, 55, $yhtiorow["nimi"],		$thispage, $pieni);
					$pdf->draw_text(30, 45, $yhtiorow["osoite"],	$thispage, $pieni);
					$pdf->draw_text(30, 35, $yhtiorow["postino"]."  ".$yhtiorow["postitp"],	$thispage, $pieni);
					$pdf->draw_text(30, 25, $yhtiorow["maa"],		$thispage, $pieni);

					$pdf->draw_text(217, 55, t("Puhelin", $kieli).":",			$thispage, $pieni);
					$pdf->draw_text(257, 55, $yhtiorow["puhelin"],				$thispage, $pieni);
					$pdf->draw_text(217, 45, t("Telefax", $kieli).":",			$thispage, $pieni);
					$pdf->draw_text(257, 45, $yhtiorow["fax"],					$thispage, $pieni);
					$pdf->draw_text(217, 35, t("Sähköposti", $kieli).":",		$thispage, $pieni);
					$pdf->draw_text(257, 35, $yhtiorow["email"],				$thispage, $pieni);

					//jos on suomalainen yritys tehdään ytunnus nätiks
					if (strtoupper($yhtiorow["maa"])== 'FI') {
						//muutetaan ytunnus takas oikean näköseks
						$ytunpit = 8-strlen($yhtiorow["ytunnus"]);

						if ($ytunpit > 0) {
							$uytunnus = $yhtiorow["ytunnus"];
							while ($ytunpit > 0) {
							    $uytunnus = "0".$uytunnus; $ytunpit--;
							}
						}
						else {
							$uytunnus = $yhtiorow["ytunnus"];
						}

						$uytunnus = substr($uytunnus,0,7)."-".substr($uytunnus,7,1);
					}
					else {
						$uytunnus = $yhtiorow["ytunnus"];
					}

			        if ($laskurow['yhtio_ovttunnus'] != $yhtiorow['ovttunnus'] and $laskurow['yhtio_ovttunnus'] != '') {
			            $uytunnus = $laskurow['yhtio_ovttunnus'];
			        } 
					else {
			    		$uytunnus = $yhtiorow["maa"]."-".$uytunnus;
			        }

					$pdf->draw_text(404, 55, t("Y-tunnus", $kieli).":",		   							$thispage, $pieni);
					$pdf->draw_text(454, 55, $uytunnus,						   							$thispage, $pieni);
					$pdf->draw_text(404, 45, t("Kotipaikka", $kieli).":",	   							$thispage, $pieni);
					$pdf->draw_text(454, 45, $yhtiorow["kotipaikka"],									$thispage, $pieni);
					$pdf->draw_text(404, 35,  t("Pankkiyhteys", $kieli).":",							$thispage, $pieni);
					$pdf->draw_text(454, 35,  $yhtiorow["pankkinimi1"]." ".$yhtiorow["pankkitili1"],	$thispage, $pieni);

					if ($laitetaanko_yhteensarivi == 1 and $total != 0) {	
						$pdf->draw_text(450, 72, t("Yhteensä", $kieli).":", 							$thispage, $norm);
						
						$oikpos = $pdf->strlen(sprintf('%.2f',$total)." ".$laskurow["valkoodi"], $norm);					
						$pdf->draw_text(575-$oikpos, 72, sprintf('%.2f',$total)." ".$laskurow["valkoodi"], $thispage, $norm);						
					}

					if ($laitetaanko_yhteensarivi != 1) {
						$pdf->draw_text(530, 72, t("Jatkuu", $kieli)."...", 							$thispage, $norm);
					}

					if ($laitetaanko_yhteensarivi == 1) {
						// viivat rivien väliin...
						$x[0] = 20;
						$x[1] = 580;
						$y[0] = $y[1] = $kala + 15 - 4;

						$pdf->draw_line($x, $y, $thispage, $rectparam);

						for ($pp=1; $pp<=$sivu; $pp++) {
							$pdf->draw_text(330, 803, "$pp / $sivu", $page[$pp], $norm);
						}
					}
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
			
			// katotaan miten halutaan sortattavan
			$sorttauskentta = generoi_sorttauskentta($yhtiorow["ostotilauksen_jarjestys"]);

			$query = "	SELECT tilausrivi.tunnus, toim_tuoteno, toim_nimitys, tuote.tuoteno, round(tilausrivi.varattu*if(tuotteen_toimittajat.tuotekerroin=0,1,tuotteen_toimittajat.tuotekerroin),2) varattu,
						tilausrivi.hinta, tilausrivi.ale, round(tilausrivi.varattu*if(tuotteen_toimittajat.tuotekerroin=0,1,tuotteen_toimittajat.tuotekerroin)*tilausrivi.hinta*(1-ale/100),'$yhtiorow[hintapyoristys]') rivihinta, 
						substring(tuote.nimitys,1,80) nimitys, kommentti, tilausrivi.yksikko, toimaika,
						$sorttauskentta
						FROM tilausrivi
						join tuote USING (yhtio, tuoteno)
						join tuotteen_toimittajat on tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno and tuotteen_toimittajat.liitostunnus = '$laskurow[liitostunnus]'
						WHERE tilausrivi.otunnus = '$otunnus'
						and tilausrivi.yhtio	 = '$kukarow[yhtio]'
						ORDER BY sorttauskentta $yhtiorow[ostotilauksen_jarjestys_suunta], tilausrivi.tunnus";
			$result = mysql_query($query) or pupe_error($query);
			
			//generoidaan rivinumerot
			$rivinumerot = array();

			if($yhtiorow["ostotilauksen_jarjestys_suunta"] == "ASC") { 
				$kal = 1;
			}
			else {
				$kal = mysql_num_rows($result);
			}

			while ($row = mysql_fetch_array($result)) {
				$rivinumerot[$row["tunnus"]] = $kal;
				
				if($yhtiorow["ostotilauksen_jarjestys_suunta"] == "ASC") { 
					$kal++;
				}
				else {
					$kal--;
				}				
			}

			mysql_data_seek($result,0);

			unset($pdf);
			unset($page);

			$sivu  = 1;
			$total = 0;

			// Aloitellaan lähetteen teko
			$page[$sivu] = alku();

			while ($row = mysql_fetch_array($result)) {
				rivi($page[$sivu]);
				$total += $row["rivihinta"];
			}

			//Viimeiselle sivulle myös lopputekstit
			loppu($page[$sivu], $total, 1);

			//kutsutaan lopuksi vielä print_pdf funktiota
			print_pdf();
		}
	}
	
	if ($silent == "") {
		echo $tulosta_ostotilaus_ulos;
	}
?>
