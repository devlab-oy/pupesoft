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
		require_once('../pdflib/phppdflib.class.php');

		$pdf = new pdffile;
		$pdf->set_default('margin', 0);
		$rectparam["width"] = 0.3;

		if (!function_exists('alku')) {
			function alku () {
				global $pdf, $image, $laskurow, $yhtiorow, $kukarow, $sivu, $rectparam, $kieli, $maakoodi;

				$firstpage = $pdf->new_page("a4");
				$pdf->enable('template');
				$tid = $pdf->template->create();
				$pdf->template->size($tid, 600, 830);

				$p["height"] = 10;
				$p["font"] = "Times-Roman";

				$pieni["height"] = 8;
				$pieni["font"] = "Times-Roman";

	            if (strtoupper($yhtiorow["maakoodi"]) == 'FI') {
                    $maakoodi = "FI ";
	            }
	            else {
                    $maakoodi = "";
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
					$pdf->draw_text(310, 767,  trim($laskurow["comments"]), 			$firstpage, $pieni);
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
				$pdf->draw_text(310, 680, t("Y-tunnus", $kieli).": ".$maakoodi.$yhtiorow["ytunnus"],$firstpage, $p);

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
				$pdf->draw_rectangle(640, 300, 570, 580,			 	$firstpage, $rectparam);
				$pdf->draw_text(310, 630, t("Maksuehto", $kieli), 		$firstpage, $pieni);
				$pdf->draw_text(310, 615, $laskurow["maksuteksti"], 	$firstpage, $p);
				$pdf->draw_rectangle(610, 300, 570, 580, $firstpage, 	$rectparam);
				$pdf->draw_text(310, 600, t("Tavaraerän merkinnät", $kieli), 	$firstpage, $pieni);
				$pdf->draw_text(310, 583, $laskurow[""], 				$firstpage, $p);


				$pdf->draw_text(45,  553, t("Tuotenumero", $kieli),		$firstpage, $pieni);
				$pdf->draw_text(363, 553, t("Määrä", $kieli),			$firstpage, $pieni);
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
				global $pdf, $kukarow, $yhtiorow, $komento, $kieli, $tee;

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
					substring(tuote.nimitys,1,80) nimitys, kommentti
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

			$pdf->draw_text(45,  $kala, $row["toim_tuoteno"],				$firstpage, $p);
			$pdf->draw_text(190, $kala, "(".$row["tuoteno"].")",			$firstpage, $p);
			$pdf->draw_text(363, $kala, sprintf('%.2f',$row["varattu"]), 	$firstpage, $p);
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
	
	if ($silent == "") {
		echo $tulosta_ostotilaus_ulos;
	}
?>