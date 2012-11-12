<?php

	$tulosta_ostotilaus_ulos = "";

	$query = "	SELECT *
				FROM lasku
				WHERE tunnus = '$otunnus' and yhtio='$kukarow[yhtio]'";
	$sresult = pupe_query($query);
	$laskurow = mysql_fetch_array($sresult);

	if ($laskurow['tila'] != 'O' and $laskurow['tila'] != 'D' and $laskurow['tilaustyyppi'] != 'O') {
		$tulosta_ostotilaus_ulos .= t("Kesken oleva tilaus ei ole ostotilaus");
		exit;
	}

	//tässä tapauksessa tilaus lähetetään edinä
	if ($komento['Ostotilaus'] == 'edi') {

		// haetaan toimittajan tiedot
		$query    = "SELECT * from toimi where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[liitostunnus]'";
		$toimires = pupe_query($query);
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

		require_once("pdflib/phppdflib.class.php");

		if ($kieli== '') {
			$querykiel = "SELECT kieli FROM asiakas WHERE yhtio = '$kukarow[yhtio]' and ytunnus = '$laskurow[ytunnus]'";
			$kielresult = pupe_query($querykiel);
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
			function alku ($params) {
				global $yhtiorow, $kukarow;

				// Luodaan muuttujat
				extract($params);

				//PDF parametrit
				if (!isset($pdf)) {
					$pdf = new pdffile;
					$pdf->set_default('margin-top', 	0);
					$pdf->set_default('margin-bottom', 	0);
					$pdf->set_default('margin-left', 	0);
					$pdf->set_default('margin-right', 	0);
				}

				$thispage = $pdf->new_page("a4");

				tulosta_logo_pdf($pdf, $thispage, $laskurow);

				$pdf->draw_text(310,815, t("Ostotilaus", $kieli), 		$thispage, $iso);
				$pdf->draw_text(310, 803, t("Sivu", $kieli), 			$thispage, $norm);

				//Vasen sarake
				//$pdf->draw_rectangle(737, 20,  674, 300, $thispage, $rectparam);
				$pdf->draw_text(50, 729, t("Toimittaja", $kieli), 		$thispage, $pieni);
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
				$rarres = pupe_query($query);
				$toimirow = mysql_fetch_array($rarres);

				// Oikea sarake
				$pdf->draw_rectangle(800, 300, 779, 580, $thispage, $rectparam);
				$pdf->draw_rectangle(800, 420, 779, 580, $thispage, $rectparam);
				$pdf->draw_text(310, 792, t("Tilausnumero", $kieli), 	$thispage, $pieni);
				$pdf->draw_text(310, 782, $laskurow["tunnus"],			$thispage, $boldi);
				$pdf->draw_text(430, 792, t("Toimittajan fax", $kieli), $thispage, $pieni);
				$pdf->draw_text(430, 782, $toimirow["fax"],				$thispage, $norm);

				$pdf->draw_rectangle(779, 300, 758, 580, $thispage, $rectparam);
				$pdf->draw_rectangle(779, 420, 758, 580, $thispage, $rectparam);
				$pdf->draw_text(310, 771, t("Y-tunnus", $kieli), 	$thispage, $pieni);

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

				$pdf->draw_text(310, 761, $uytunnus, $thispage, $norm);
				$pdf->draw_text(430, 771, t("Päiväys", $kieli), $thispage, $pieni);
				$pdf->draw_text(430, 761, tv1dateconv(date("Y-m-d")), $thispage, $norm);

				$pdf->draw_rectangle(758, 300, 737, 580, $thispage, $rectparam);
				$pdf->draw_rectangle(758, 420, 737, 580, $thispage, $rectparam);
				$pdf->draw_text(310, 750, t("Asiaa hoitaa", $kieli), $thispage, $pieni);

				//etsitään myyjän nimi
				$query  = "	SELECT nimi
							FROM kuka
							WHERE tunnus='$laskurow[myyja]' AND yhtio='$kukarow[yhtio]'";
				$myyresult = pupe_query($query);
				$myyrow = mysql_fetch_array($myyresult);

				list($ff_string, $ff_font) = pdf_fontfit($myyrow["nimi"], 105, $pdf, $norm);
				$pdf->draw_text(310, 740, $ff_string, $thispage, $ff_font);


				$pdf->draw_text(430, 750, t("Toimitusaika", $kieli), $thispage, $pieni);

				if ($yhtiorow['ostotilauksen_tuloste'] == '' or $yhtiorow['ostotilauksen_tuloste'] == 'B') {
					$pdf->draw_text(430, 740, tv1dateconv($laskurow["toimaika"]), $thispage, $boldi);
				}

				$pdf->draw_rectangle(737, 300, 716, 580, $thispage, $rectparam);
				$pdf->draw_rectangle(737, 420, 716, 580, $thispage, $rectparam);

				$pdf->draw_text(310, 729, t("Yhteyshenkilö", $kieli), $thispage, $pieni);

				list($ff_string, $ff_font) = pdf_fontfit($laskurow["tilausyhteyshenkilo"], 105, $pdf, $norm);
				$pdf->draw_text(310, 719, $ff_string, $thispage, $ff_font);

				$pdf->draw_text(430, 729, t("Laadittu", $kieli), $thispage, $pieni);
				$pdf->draw_text(430, 719, tv1dateconv($laskurow["luontiaika"]),	$thispage, $norm);

				$pdf->draw_rectangle(716, 300, 695, 580, $thispage, $rectparam);
				$pdf->draw_text(310, 708, t("Toimitusehto", $kieli), $thispage, $pieni);
				$pdf->draw_text(310, 698, $laskurow["toimitusehto"], $thispage, $norm);

				$pdf->draw_rectangle(695, 300, 674, 580, $thispage, $rectparam);
				$pdf->draw_text(310, 687, t("Maksuehto", $kieli), $thispage, $pieni);
				$pdf->draw_text(310, 677, $laskurow["maksuteksti"], $thispage, $norm);

				$lakork = 674;

				if ($laskurow["kuljetus"] != "") {
					$pdf->draw_rectangle($lakork, 300, $lakork-21, 580, $thispage, $rectparam);
					$pdf->draw_text(310, $lakork-8, t("Kuljetus", $kieli), $thispage, $pieni);
					$pdf->draw_text(310, $lakork-18, $laskurow["kuljetus"], $thispage, $norm);
					$lakork -= 21;
				}

				if ($laskurow["huolitsija"] != "") {
					$pdf->draw_rectangle($lakork, 300, $lakork-21, 580, $thispage, $rectparam);
					$pdf->draw_text(310, $lakork-8, t("Huolitsija", $kieli), $thispage, $pieni);
					$pdf->draw_text(310, $lakork-18, $laskurow["huolitsija"], $thispage, $norm);
					$lakork -= 21;
				}

				if ($laskurow["jakelu"] != "") {
					$pdf->draw_rectangle($lakork, 300, $lakork-21, 580, $thispage, $rectparam);
					$pdf->draw_text(310, $lakork-8, t("Jakelu", $kieli), $thispage, $pieni);
					$pdf->draw_text(310, $lakork-18, $laskurow["jakelu"], $thispage, $norm);
					$lakork -= 21;
				}

				// suoratoimituksissa verkkotunnus-kenttään tallentuu vastaanottajan liitostunnus
				if ($laskurow['verkkotunnus'] != '') {
					$query = "	SELECT if(gsm != '', gsm, if(tyopuhelin != '', tyopuhelin, puhelin)) puh
								FROM asiakas
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tunnus = '{$laskurow['verkkotunnus']}'";
					$puhelinres = pupe_query($query);

					if (mysql_num_rows($puhelinres) > 0) {
						$puhelinrow = mysql_fetch_assoc($puhelinres);

						$pdf->draw_rectangle($lakork, 300, $lakork-21, 580, $thispage, $rectparam);
						$pdf->draw_text(310, $lakork-8, t("Vastaanottajan puhelinnumero", $kieli), $thispage, $pieni);
						$pdf->draw_text(310, $lakork-18, $puhelinrow['puh'], $thispage, $norm);
						$lakork -= 21;
					}
				}

				$pdf->draw_text(310, $lakork-10, t("Olkaa hyvä ja kirjoittakaa tilausnumero lähetteeseen, laskuun ja kirjeisiin", $kieli).".", $thispage, $pieni);

				// Ostotilaukselle teksti että hinnat ovat verottimia.
				$pdf->draw_text(310, $lakork-20, t("Ostotilauksen hinnat eivät sisällä arvonlisäveroa", $kieli).".", $thispage, $pieni);

				$komm = "";

				if (trim($laskurow['viesti']) != '') {
					$komm .= "\n".t("Tilausviite", $kieli).":###".wordwrap(str_replace("\n","\n###", $laskurow['viesti']), 90, "\n###");
				}
				if (trim($laskurow['sisviesti1']) != '') {
					$komm .= "\n".t("Kommentti", $kieli).":###".wordwrap(str_replace("\n","\n###", $laskurow['sisviesti1']), 90, "\n###");
				}

				//Tulostetaan laskun kommentti
				if (trim($komm) != '') {
					$kommentit = explode("\n", trim($komm));

					$pohja  = 577;
					$maxoik = 0;

					foreach($kommentit as $kommentti) {
						if (strpos($kommentti, "###") !== FALSE) {
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

						$pohja = $pohja - 12;
					}

					$kala = $pohja - 25;

				}
				else {
					$kala = 565;
				}

				$pdf->draw_rectangle($kala+30, 20, 70, 580, $thispage, $rectparam);
				$pdf->draw_rectangle($kala+30, 20,  $kala+10, 40, $thispage, $rectparam);
				$pdf->draw_rectangle($kala+30, 40,  $kala+10, 290, $thispage, $rectparam);
				$pdf->draw_rectangle($kala+30, 290, $kala+10, 350, $thispage, $rectparam);
				$pdf->draw_rectangle($kala+30, 350, $kala+10, 420, $thispage, $rectparam);
				$pdf->draw_rectangle($kala+30, 420, $kala+10, 480, $thispage, $rectparam);
				$pdf->draw_rectangle($kala+30, 480, $kala+10, 520, $thispage, $rectparam);
				$pdf->draw_rectangle($kala+30, 520, $kala+10, 580, $thispage, $rectparam);
				$pdf->draw_text(25,  $kala+15, "#", $thispage, $norm);
				$pdf->draw_text(42,  $kala+15, t("Tuotenumero", $kieli), $thispage, $norm);
				$pdf->draw_text(292, $kala+15, t("Toimitusaika", $kieli), $thispage, $norm);
				$pdf->draw_text(352, $kala+15, t("Määrä", $kieli), $thispage, $norm);
				$pdf->draw_text(422, $kala+15, t("Hinta", $kieli), $thispage, $norm);
				$pdf->draw_text(482, $kala+15, t("Ale", $kieli), $thispage, $norm);
				$pdf->draw_text(522, $kala+15, t("Yhteensä", $kieli), $thispage, $norm);

				// Luodaan palautettavat
				$return = compact(array_keys($params));
				return $return;
			}
		}

		if (!function_exists('rivi')) {
			function rivi($params) {
				global $yhtiorow, $kukarow;

				// Luodaan muuttujat
				extract($params);

				// Mahtuuko rivi tälle sivulle
				$kalatest = $kala;

				// Luodaan palautettavat
				$returntest = compact(array_keys($params));

				//Fuulataan tämä, jotta voidaan kokeilla mahtuuko rivi sivulle
				$returntest["thispage"] = "TESTISIVU";

				$returntest = piirra_rivi($returntest);

				// Otetaan vain kalamuuttuja tästä
				$kalatest = $returntest["kala"];

				// Jos on paljon rivejä tehdään uusi otsikko...
				if ($kalatest <= 70) {
					$sivu++;

					// Luodaan palautettavat
					$return = compact(array_keys($params));

					$return = loppu($return);
					$params = alku($return);

					// Luodaan muuttujat
					extract($params);

					$page[$sivu] = $thispage;
				}

				$return = compact(array_keys($params));

				$params = piirra_rivi($return);

				return $params;
			}
		}

		if (!function_exists('piirra_rivi')) {
			function piirra_rivi($params) {
				global $yhtiorow, $kukarow;

				// Luodaan muuttujat
				extract($params);

				// viivat rivien väliin...
				$x[0] = 20;
				$x[1] = 580;
				$y[0] = $y[1] = $kala + 15 - 5;

				$pdf->draw_line($x, $y, $thispage, $rectparam);

				$pdf->draw_text(25,  $kala, $rivinumerot[$row["tunnus"]],$thispage, $pieni);

				if ($toim == "HAAMU") {
					$pdf->draw_text(45, $kala, $row["tuoteno"],	$thispage, $norm);
				}
				else {

					if ($row["toim_tuoteno"] != "") {
						$pdf->draw_text(45,  $kala, $row["toim_tuoteno"],$thispage, $norm);
					}
					else {
						$pdf->draw_text(45,  $kala, $row["tuoteno"], $thispage, $norm);
					}

					if (strtoupper($row["toim_tuoteno"]) != strtoupper($row["tuoteno"])) {
						$oikpos = $pdf->strlen("(".$row["tuoteno"].")", $pieni);
						$pdf->draw_text(290-$oikpos, $kala, "(".$row["tuoteno"].")", $thispage, $pieni);
					}
				}

				if ($yhtiorow['ostotilauksen_tuloste'] == '' or $yhtiorow['ostotilauksen_tuloste'] == 'B') {
					$pdf->draw_text(300, $kala, tv1dateconv($row["toimaika"]), $thispage, $norm);
				}

				//	Tarkastetaan olisiko toimittajalla yksikkö!
				$query = "	SELECT toim_yksikko
							FROM tuotteen_toimittajat
							WHERE yhtio 		= '$kukarow[yhtio]'
							AND tuoteno 		= '$row[tuoteno]'
							AND liitostunnus 	= '$laskurow[liitostunnus]'
							LIMIT 1";
				$rarres = pupe_query($query);
				$rarrow	 = mysql_fetch_array($rarres);

				//	Jos toimittajalla on yksikkö ohitetaan tuotteen yksikkö
				if ($rarrow["toim_yksikko"] != "") {
					$omyks = $rarrow["toim_yksikko"];
				}
				else {
					$omyks = t_avainsana("Y", $kieli, "and avainsana.selite='$row[yksikko]'", "", "", "selite");
				}

				$oikpos = $pdf->strlen(($row["varattu"]*1)." ".$omyks, $norm);
				$pdf->draw_text(415-$oikpos, $kala, ($row["varattu"]*1)." ".$omyks, $thispage, $norm);

				$oikpos = $pdf->strlen(hintapyoristys($row["hinta"]), $norm);
				$pdf->draw_text(475-$oikpos, $kala, hintapyoristys($row["hinta"]), $thispage, $norm);

				if ($row["ale1"] != 0) {
					$oikpos = $pdf->strlen(($row["ale1"]*1)."%", $norm);
					$pdf->draw_text(510-$oikpos, $kala, ($row["ale1"]*1)."%", $thispage, $norm);
				}

				$oikpos = $pdf->strlen(hintapyoristys($row["rivihinta"]), $norm);
				$pdf->draw_text(575-$oikpos, $kala, hintapyoristys($row["rivihinta"]), $thispage, $norm);

            	if ($yhtiorow['ostotilauksen_tuloste'] == '' or $yhtiorow['ostotilauksen_tuloste'] == 'A') {

	 				//Käännetään nimitys
					$row['nimitys'] = t_tuotteen_avainsanat($row, 'nimitys', $kieli);

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

				// Luodaan palautettavat
				$return = compact(array_keys($params));
				return $return;

			}
		}

		if (!function_exists('loppu')) {
			function loppu ($params) {
				//global $pdf, $laskurow, $yhtiorow, $kukarow, $page, $sivu, $rectparam, $kieli, $kala, $norm, $pieni;
				global $yhtiorow, $kukarow;

				// Luodaan muuttujat
				extract($params);

				//Alimmat kolme laatikkoa, yhtiötietoja
				$pdf->draw_rectangle(70, 20, 20, 580, $thispage, $rectparam);
				$pdf->draw_rectangle(70, 207, 20, 580, $thispage, $rectparam);
				$pdf->draw_rectangle(70, 394, 20, 580, $thispage, $rectparam);

				$pdf->draw_text(30, 55, $yhtiorow["nimi"], $thispage, $pieni);
				$pdf->draw_text(30, 45, $yhtiorow["osoite"], $thispage, $pieni);
				$pdf->draw_text(30, 35, $yhtiorow["postino"]."  ".$yhtiorow["postitp"],	$thispage, $pieni);
				$pdf->draw_text(30, 25, $yhtiorow["maa"], $thispage, $pieni);

				$pdf->draw_text(217, 55, t("Puhelin", $kieli).":", $thispage, $pieni);
				$pdf->draw_text(257, 55, $yhtiorow["puhelin"],$thispage, $pieni);
				$pdf->draw_text(217, 45, t("Telefax", $kieli).":", $thispage, $pieni);
				$pdf->draw_text(257, 45, $yhtiorow["fax"], $thispage, $pieni);
				$pdf->draw_text(217, 35, t("Sähköposti", $kieli).":", $thispage, $pieni);
				$pdf->draw_text(257, 35, $yhtiorow["email"], $thispage, $pieni);

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

				$pdf->draw_text(404, 55, t("Y-tunnus", $kieli).":", $thispage, $pieni);
				$pdf->draw_text(454, 55, $uytunnus, $thispage, $pieni);
				$pdf->draw_text(404, 45, t("Kotipaikka", $kieli).":", $thispage, $pieni);
				$pdf->draw_text(454, 45, $yhtiorow["kotipaikka"], $thispage, $pieni);
				$pdf->draw_text(404, 35,  t("Pankkiyhteys", $kieli).":", $thispage, $pieni);
				$pdf->draw_text(454, 35,  $yhtiorow["pankkinimi1"]." ".$yhtiorow["pankkitili1"], $thispage, $pieni);
				$pdf->draw_text(404, 25, $yhtiorow["pankkiiban1"]." / ".$yhtiorow["pankkiswift1"], $thispage, $pieni);

				if ($laitetaanko_yhteensarivi == 1 and $total != 0) {
					$pdf->draw_text(430, 82, t("Yhteensä", $kieli).":", $thispage, $norm);

					$oikpos = $pdf->strlen(sprintf('%.2f',$total)." ".$laskurow["valkoodi"], $norm);
					$pdf->draw_text(555-$oikpos, 82, sprintf('%.2f',$total)." ".$laskurow["valkoodi"], $thispage, $norm);
				}

				if ($laitetaanko_yhteensarivi != 1) {
					$pdf->draw_text(540, 72, t("Jatkuu", $kieli)."...", $thispage, $norm);
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
					// Luodaan palautettavat
					$return = compact(array_keys($params));
					return $return;
			}
		}

		if (!function_exists('print_pdf')) {
			function print_pdf ($params) {
				global $kukarow, $yhtiorow, $komento, $tee, $tulosta_ostotilaus_ulos;

				// Luodaan muuttujat
				extract($params);

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
						$kutsu = t("Ostotilaus", $kieli)." $laskurow[tunnus]";

						if ($yhtiorow["liitetiedostojen_nimeaminen"] == "N") {
							$kutsu .= ", ".trim($laskurow["nimi"]).", ".trim($laskurow["toim_nimi"]);
						}

						require("inc/sahkoposti.inc");
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

		if ($mista == "tulostakopio") {
			$haamulisa = " and tilausrivi.tyyppi = 'D'";

		}
		else {
			$haamulisa = " and tilausrivi.tyyppi != 'D'";

		}

		$query_ale_lisa = generoi_alekentta('O');

		$query_ale_select_lisa = generoi_alekentta_select('erikseen', 'O');

		if ($toim != "HAAMU") {
			if ($mista == 'tulostakopio') {
				$query = "	SELECT tilausrivi.tunnus, toim_tuoteno, toim_nimitys, tuote.tuoteno, round(tilausrivi.tilkpl*if (tuotteen_toimittajat.tuotekerroin=0,1,tuotteen_toimittajat.tuotekerroin),2) varattu,
							tilausrivi.hinta, {$query_ale_select_lisa} round(tilausrivi.tilkpl*if (tuotteen_toimittajat.tuotekerroin=0,1,tuotteen_toimittajat.tuotekerroin)*tilausrivi.hinta*{$query_ale_lisa},'$yhtiorow[hintapyoristys]') rivihinta,
							substring(tuote.nimitys,1,80) nimitys, kommentti, tilausrivi.yksikko, toimaika, substring(tilausrivi.nimitys,1,80) rivinimitys,
							$sorttauskentta
							FROM tilausrivi
							join tuote USING (yhtio, tuoteno)
							join tuotteen_toimittajat on tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno and tuotteen_toimittajat.liitostunnus = '$laskurow[liitostunnus]'
							WHERE tilausrivi.otunnus = '$otunnus'
							and tilausrivi.yhtio	 = '$kukarow[yhtio]'
							and tilausrivi.tyyppi	!= 'D'
							ORDER BY sorttauskentta $yhtiorow[ostotilauksen_jarjestys_suunta], tilausrivi.tunnus";
			}
			else {
				$query = "	SELECT tilausrivi.tunnus, toim_tuoteno, toim_nimitys, tuote.tuoteno, round(tilausrivi.varattu*if (tuotteen_toimittajat.tuotekerroin=0,1,tuotteen_toimittajat.tuotekerroin),2) varattu,
							tilausrivi.hinta, {$query_ale_select_lisa} round(tilausrivi.varattu*if (tuotteen_toimittajat.tuotekerroin=0,1,tuotteen_toimittajat.tuotekerroin)*tilausrivi.hinta*{$query_ale_lisa},'$yhtiorow[hintapyoristys]') rivihinta,
							substring(tuote.nimitys,1,80) nimitys, kommentti, tilausrivi.yksikko, toimaika, substring(tilausrivi.nimitys,1,80) rivinimitys,
							$sorttauskentta
							FROM tilausrivi
							join tuote USING (yhtio, tuoteno)
							join tuotteen_toimittajat on tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno and tuotteen_toimittajat.liitostunnus = '$laskurow[liitostunnus]'
							WHERE tilausrivi.otunnus = '$otunnus'
							and tilausrivi.yhtio	 = '$kukarow[yhtio]'
							and tilausrivi.tyyppi	!= 'D'
							ORDER BY sorttauskentta $yhtiorow[ostotilauksen_jarjestys_suunta], tilausrivi.tunnus";
			}
		}
		else {
			$query = "	SELECT tilausrivi.tunnus, tilausrivi.tuoteno, tilausrivi.varattu,
						tilausrivi.hinta, {$query_ale_select_lisa} round(tilausrivi.varattu*tilausrivi.hinta*{$query_ale_lisa},'$yhtiorow[hintapyoristys]') rivihinta,
						substring(tilausrivi.kommentti,1,80) nimitys, tilausrivi.yksikko, tilausrivi.toimaika
						FROM tilausrivi
						WHERE tilausrivi.otunnus = '$otunnus'
						and tilausrivi.yhtio	 = '$kukarow[yhtio]'
						$haamulisa
						ORDER BY tilausrivi.tunnus";
		}

		$result = pupe_query($query);

		//generoidaan rivinumerot
		$rivinumerot = array();

		if ($yhtiorow["ostotilauksen_jarjestys_suunta"] == "ASC") {
			$kal = 1;
		}
		else {
			$kal = mysql_num_rows($result);
		}

		while ($row = mysql_fetch_array($result)) {
			$rivinumerot[$row["tunnus"]] = $kal;

			if ($yhtiorow["ostotilauksen_jarjestys_suunta"] == "ASC") {
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

		$params = array(
						'boldi'						=> $boldi,
						'iso'						=> $iso,
						'kala'						=> 0,
						'kieli'						=> $kieli,
						'laskurow'					=> $laskurow,
						'norm'						=> $norm,
						'page'						=> NULL,
						'pdf'						=> $pdf,
						'pieni'						=> $pieni,
						'pieni_boldi'				=> $pieni_boldi,
						'rectparam'					=> $rectparam,
						'row'						=> '',
						'sivu'						=> 1,
						'tee'						=> $tee,
						'thispage'					=> NULL,
						'toim'						=> $toim,
						'rivinumerot'				=> $rivinumerot,
						'laitetaanko_yhteensarivi'	=> 0,
						'total'						=> 0,
		);

		// Aloitellaan lähetteen teko
		$params = alku($params);

		while ($row = mysql_fetch_array($result)) {
			if (($row["tuoteno"] == "99" or $yhtiorow["nimityksen_muutos_tilauksella"] == "Y") and $row["rivinimitys"] != "" ) {
				$row["nimitys"] = $row["rivinimitys"];
			}
			$params["row"] = $row;
			$params = rivi($params);
			$total += $row["rivihinta"];
		}

		$params["laitetaanko_yhteensarivi"] = 1;
		$params["total"] = $total;
		//Viimeiselle sivulle myös lopputekstit
		$params = loppu($params);

		//kutsutaan lopuksi vielä print_pdf funktiota
		print_pdf($params);
	}

	if ($silent == "") {
		echo $tulosta_ostotilaus_ulos;
	}
