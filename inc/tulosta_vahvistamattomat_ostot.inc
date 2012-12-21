<?php


	require_once('pdflib/pupepdf.class.php');

	if (!function_exists('tulosta_ostotilaus')) {
		function tulosta_ostotilaus ($otunnus, $komento, $kieli = "", $tee) {
			global $yhtiorow, $kukarow, $fonts, $tulosta_ostotilaus_ulos;

			if (strpos($otunnus, ",") !== FALSE) {
				$otunnuslisa = " and lasku.tunnus in($otunnus)";
			}
			else {
				$otunnuslisa = " and lasku.tunnus  = '$otunnus'";
			}

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
						$otunnuslisa";
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
				$pdf->write($h, 2, 0, 0, t("TOIMITUKSEN VAHVISTUSPYYNTÖ", $kieli), "BIG", "C", "B");

				//	Laitetaan perustiedot oikealle
				$h=250;
				$s=$pdf->teeSarakkeet(array(100,35));

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

				$pdf->write($h, 1, 0, $s[1], t("Asiakas", $kieli).":", "norm", "L", "B");
				$h-=$pdf->ln();
				$pdf->write($h, 1, 0, $s[0], 		$yhtiorow["nimi"], "norm", "L", "B");
				$h-=$pdf->ln();
				$pdf->write($h, 1, 0, $s[0], 		$yhtiorow["osoite"], "norm", "L", "");
				$h-=$pdf->ln();
				$pdf->write($h, 1, 0, $s[0], 		$yhtiorow["maa"].$yhtiorow["postino"]." ".$yhtiorow["postitp"], "norm", "L", "");
				$h-=$pdf->ln();
				$pdf->write($h, 1, 0, $s[0], 		$yhtiorow["maa"], "norm", "L", "");
				$h-=$pdf->ln();


				$h-=$pdf->ln(1.5);

				$pdf->write($h, 1, 0, 0, t("HUOM: Tämä ei ole ostotilaus.", $kieli), "norm", "L", "B");

				return $pdf;
			}

			$pdf=alku($pdf, $laskurow,$kieli);
			$h = 190;

			if (strpos($otunnus, ",") !== FALSE) {
				$otunnuslisa = " tilausrivi.otunnus in($otunnus) ";
			}
			else {
				$otunnuslisa = " tilausrivi.otunnus  = '$otunnus'";
			}

			$query_ale_lisa = generoi_alekentta('O');
			$ale_select_lisa = generoi_alekentta_select('erikseen', 'O');

			//	Käsitellään tilausrivit
			$query = "	SELECT otunnus, toim_tuoteno, toim_nimitys, tuote.tuoteno, round(tilausrivi.varattu*if(tuotteen_toimittajat.tuotekerroin=0,1,tuotteen_toimittajat.tuotekerroin),2) varattu, toimaika,
						tilausrivi.hinta, {$ale_select_lisa} round(tilausrivi.varattu*if(tuotteen_toimittajat.tuotekerroin=0,1,tuotteen_toimittajat.tuotekerroin)*tilausrivi.hinta * {$query_ale_lisa},'$yhtiorow[hintapyoristys]') rivihinta, kommentti
						FROM tilausrivi
						join tuote USING (yhtio, tuoteno)
						join tuotteen_toimittajat on tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno and tuotteen_toimittajat.liitostunnus = '$laskurow[liitostunnus]'
						WHERE $otunnuslisa
						and tilausrivi.yhtio='$kukarow[yhtio]'
						and tyyppi = 'O'
						and tilausrivi.varattu 	> '0'
						and tilausrivi.jaksotettu = '0'
						ORDER BY tilausrivi.otunnus, tilausrivi.toimaika, toim_tuoteno";
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
				$pdf->write($h, 1, $s[0], $s[1], t("Tilaus", $kieli), "norm", "R", "B");
				$pdf->write($h, 1, $s[2], 0, t("Määrä", $kieli), "norm", "R", "B");

				$h-=$pdf->ln();
				$pdf->write($h, 1, 0, $s[0], t("Kommentti", $kieli), "norm", "L", "B");
				$pdf->write($h, 1, $s[1], 0, t("Toimituspvm", $kieli), "norm", "R", "B");
				$h-=$pdf->ln(1.5);

				while ($row = mysql_fetch_array($riresult)) {

					if ($h < 37) {
						$h = 37;

						$h-=$pdf->ln(1.5);
						$h-=$pdf->ln();
						$h-=$pdf->ln();


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




						$pdf = alku($pdf, $laskurow,$kieli);
						$h = 190;
						$h-=$pdf->ln(3);

						$s=$pdf->teeSarakkeet(array(100,20,25));
						$pdf->write($h, 1, 0, $s[0], t("Tuote", $kieli), "norm", "L", "B");
						$pdf->write($h, 1, $s[0], $s[1], t("Tilaus", $kieli), "norm", "R", "B");
						$pdf->write($h, 1, $s[2], 0, t("Määrä", $kieli), "norm", "R", "B");

						$h-=$pdf->ln();
						$pdf->write($h, 1, 0, $s[0], t("Kommentti", $kieli), "norm", "L", "B");
						$pdf->write($h, 1, $s[1], 0, t("Toimituspvm", $kieli), "norm", "R", "B");
						$h-=$pdf->ln(1.5);
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
					$pdf->write($h, 1, $s[0], $s[1], $row["otunnus"], "norm", "R", "");
					$pdf->write($h, 1, $s[0], 0, $row["varattu"]." ".t_avainsana("Y", "", "and avainsana.selite='$row[yksikko]'", "", "", "selite"), "norm", "R", "");
					$h-=$pdf->ln();
					$pdf->write($h, 1, $s[0], 0, strftime("%x",strtotime($row["toimaika"])), "snadi", "R", "I");

					$ei_valia = "";
					if($row["kommentti"] != "") {
						$nb=$pdf->countParagraphHeight($row["kommentti"], "snadi", ($s[0]-5));
						$pdf->write($h, $nb, 0, $s[0], $row["kommentti"], "snadi", "L", "I");
						$h-=$pdf->ln($nb);

						$ei_valia = "OK";
					}

					if($ei_valia == "OK") {
						$h-=$pdf->ln(0.5);
					}
					else {
						$h-=$pdf->ln(1.5);
					}
				}
				$h-=$pdf->ln(3);

			}

			$h-=$pdf->ln(4);

			if($h < 50) {
				$pdf = alku($pdf, $laskurow,$kieli);
				$h = 190;
			}

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
					$kutsu = "Toimituksen vahvistuspyyntö $laskurow[tunnusnippu]/$laskurow[tunnus]";
				}
				else {
					$kutsu = "Toimituksen vahvistuspyyntö $laskurow[tunnus]";
				}

				$tulosta_ostotilaus_ulos .= t("Toimituksen vahvistuspyyntö lähetetään osoitteeseen")." $kukarow[eposti]...<br>";

				if ($kukarow["extranet"] == '') {
					require("../inc/sahkoposti.inc");
				}
				else {
					require("sahkoposti.inc");
				}
			}


			//poistetaan tmp file samantien kuleksimasta...
			system("rm -f $pdf_ostofilenimi");
		}
	}

	tulosta_ostotilaus($otunnus, $komento, $kieli = "", $tee);
?>