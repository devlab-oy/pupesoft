<?php

require_once('../pdflib/pupepdf.class.php');

if (!function_exists('tulosta_lasku')) {
	function tulosta_lasku ($otunnus, $komento, $kieli = "", $toim, $tee) {
		global $yhtiorow, $kukarow, $fonts, $tulos_ulos, $_SERVER;
		
		//	Tarkastetaan tila...
		$query = "select tila from lasku where yhtio='$kukarow[yhtio]' and tunnus='$otunnus'";
		$res=mysql_query($query) or pupe_error($query);
		$laskurow=mysql_fetch_array($res);
		if ($laskurow["tila"] == 'U') {
			$mika = "uusiotunnus";
		}
		else {
			$mika = "otunnus";
		}

		// Haetaan laskun tiedot
		$query = "	SELECT lasku.*, lasku.tunnus tunnus, valkoodi valuutta, lasku.nimi nimi, lasku.tapvm p‰iv‰ys, laskun_lisatiedot.asiakkaan_kohde, lasku.tunnusnippu projekti, if(lasku.tunnusnippu=0, lasku.tunnus,tunnusnippu) tilaus, viesti asiakkaan_viite, sisviesti1 lis‰tiedot, lasku.ytunnus asiakkaan_VAT,
					(select if(count(distinct(left(toimitettuaika,10)))>1,'".t("useita")."', max(toimitettuaika)) from tilausrivi where yhtio=lasku.yhtio and $mika=lasku.tunnus) toimituspvm, '$yhtiorow[viivastyskorko]%' viiv‰styskorko, 
					concat_ws(' ', postino, postitp) postiosoite,
					concat_ws(' ', toim_postino, toim_postitp) toim_postiosoite,
					yhteyshenkilo_kaupallinen.nimi asiakkaan_kaupallinen_k‰sittelij‰,
					yhteyshenkilo_tekninen.nimi asiakkaan_yhteyshenkilˆ,
					SUBSTRING(lasku_maa.nimi FROM 6) maa,
					SUBSTRING(toim_maa.nimi FROM 6) toim_maa
					FROM lasku
					LEFT JOIN laskun_lisatiedot ON lasku.yhtio=laskun_lisatiedot.yhtio and lasku.tunnus=laskun_lisatiedot.otunnus
					LEFT JOIN yhteyshenkilo yhteyshenkilo_tekninen ON laskun_lisatiedot.yhtio=yhteyshenkilo_tekninen.yhtio and laskun_lisatiedot.yhteyshenkilo_tekninen=yhteyshenkilo_tekninen.tunnus					
					LEFT JOIN yhteyshenkilo yhteyshenkilo_kaupallinen ON laskun_lisatiedot.yhtio=yhteyshenkilo_kaupallinen.yhtio and laskun_lisatiedot.yhteyshenkilo_kaupallinen=yhteyshenkilo_kaupallinen.tunnus
					LEFT JOIN kuka AS laatija ON laatija.yhtio='$kukarow[yhtio]' and lasku.laatija=laatija.kuka
					LEFT JOIN maat lasku_maa ON lasku_maa.koodi=lasku.maa 
					LEFT JOIN maat toim_maa ON toim_maa.koodi=lasku.toim_maa
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
		
		if ($laskurow["vienti"] == "") {
			if ($laskurow["summa"] >= 0) {
				if ($toim == 'PROFORMA') {
					$otsikko = "PROFORMALASKU";
				}
				else {
					$otsikko = "LASKU";
				}
			}
			else {
				$otsikko = "HYVITYSLASKU";
			}
		}
		else {
			if ($toim == 'PROFORMA') {
				$otsikko = "PROFORMALASKU";
			}
			elseif ($kateiskuitti != ""){
				$otsikko = "KƒTEISKUITTI";					
			}
			elseif ($laskurow["summa"] >= 0) {
				$otsikko = "VIENTILASKU";
			}
			else {
				$otsikko = "VIENNIN HYVITYSLASKU";					
			}
		}
		
		if (!function_exists('alku')) {
			function alku ($pdf, $laskurow, $toim, $otsikko, $kieli, $riviotsikot="") {
				global $yhtiorow, $kukarow, $fonts;

				if(!is_object($pdf)) {

					//	Aloitellaan PDF dokkari
					$pdf = new pdf;
					$pdf->enable('template');	

					$pdf->set_default('margin-top', 	mm_pt(10));
					$pdf->set_default('margin-bottom', 	mm_pt(10));
					$pdf->set_default('margin-left', 	mm_pt(15));
					$pdf->set_default('margin-right',	mm_pt(15));

					$fonts["snadi"]['height']	= 9;
					$fonts["snadi"]['font']		= "Helvetica";

					$fonts["norm"]['height']	= 10;
					$fonts["norm"]['font']		= "Helvetica";

					$fonts["BIG"]['height']		= 14;
					$fonts["BIG"]['font']		= "Helvetica";				

					//	Asetetaan localet
					$pdf->setLocales($laskurow["maa"], $laskurow["valkoodi"]);

					//	Tehd‰‰n sivu jotta saadaan currentPage kuntoon
					$pdf->addPage("a4");	

					//	Tehd‰‰n template
					static $tid;
					$tid=$pdf->template->create();
													
					$h=280;
					//	Liitet‰‰n kuva
					if($yhtiorow["lasku_logo"] != "" and file_exists($yhtiorow["lasku_logo"])) {
						$pdf->placeImageToTemplate($h,0,$yhtiorow["lasku_logo"], $tid);
					}

					$pdf->writeToTemplate($h, 1, 0, 0, t($otsikko, $kieli), "BIG", "C", "B", $tid);

					//	Laitetaan perustiedot oikealle		
					$h=260;
					$s=$pdf->teeSarakkeet(array(105,30));
					if($laskurow["projekti"] > 0) {
						//	Onko nippulla projektia?
						$query = "select tunnus from lasku where yhtio ='$kukarow[yhtio]' and tunnusnippu='$laskurow[projekti]' and tila = 'R'";
						$abures = mysql_query($query) or pupe_error($query);
						if(mysql_num_rows($abures)>0) {
							$kentat = array("laskunro", "p‰iv‰ys", "toimituspvm", "projekti", "valuutta", "viiv‰styskorko", "toimitustapa", "toimitusehto", "asiakkaan_VAT");							
						}
						else {
							$kentat = array("laskunro", "p‰iv‰ys", "toimituspvm", "tilaus", "valuutta", "viiv‰styskorko", "toimitustapa", "toimitusehto", "asiakkaan_VAT");
						}
					}
					else {
						$kentat = array("laskunro", "p‰iv‰ys", "toimituspvm", "tilaus", "valuutta", "viiv‰styskorko", "toimitustapa", "toimitusehto", "asiakkaan_VAT");
					}

					foreach($kentat as $key) {
						if($key == "p‰iv‰ys") {
							$pdf->writeToTemplate($h, 1, $s[0], $s[1], t(str_replace("_"," ",ucfirst($key)), $kieli).":", "norm", "L", "B", $tid);
							$pdf->writeToTemplate($h, 1, $s[1]+2, 0, strftime("%x",strtotime($laskurow[$key])), "norm", "L", "", $tid);

						}
						else {
							$pdf->writeToTemplate($h, 1, $s[0], $s[1], t(str_replace("_"," ",ucfirst($key)), $kieli).":", "norm", "L", "B", $tid);
							$pdf->writeToTemplate($h, 1, $s[1]+2, 0, $laskurow[$key], "norm", "L", "", $tid);
						}
						$h-=$pdf->ln();
					}
					$h-=$pdf->ln(0.5);

					//	Sitten yhteyshenkilˆit‰
					foreach(array("asiakkaan_viite", "lis‰tiedot", "toimittajan_yhteyshenkilˆ", "asiakkaan_yhteyshenkilˆ", "asiakkaan_kaupallinen_k‰sittelij‰") as $key) {
						if($laskurow[$key] != "" or $key == "asiakkaan_viite") {
							$pdf->writeToTemplate($h, 1, $s[0], 0, t(str_replace("_"," ",ucfirst($key)), $kieli).":", "norm", "L", "B", $tid);
							$h-=$pdf->ln();					
							$nb=$pdf->countParagraphHeight($laskurow[$key], "norm", (-1*($s[0]-5)));										
							$pdf->writeToTemplate($h, $nb, $s[0], 0, $laskurow[$key], "norm", "L", "", $tid);
							$h-=$pdf->ln($nb);					
						}
					}

					//	Laitetaan osoitetiedot vasemmalle
					$h=250;
					$pdf->writeToTemplate($h, 1, 0, $s[1], t("Laskutusosoite", $kieli).":", "norm", "L", "B", $tid);
					$h-=$pdf->ln();
					foreach(array("nimi", "nimitark", "osoite", "postiosoite", "maa") as $key) {
						if($key == "maa") {
							$pdf->writeToTemplate($h, 1, 0, $s[0], t($laskurow[$key], $kieli), "norm", "L", "", $tid);
						}
						else {
							$pdf->writeToTemplate($h, 1, 0, $s[0], $laskurow[$key], "norm", "L", "", $tid);
						}
						$h-=$pdf->ln();	
					}

					$h-=$pdf->ln();
					$pdf->writeToTemplate($h, 1, 0, $s[1], t("Toimitusosoite", $kieli).":", "norm", "L", "B", $tid);
					$h-=$pdf->ln();
					foreach(array("toim_nimi", "toim_nimitark", "toim_osoite", "toim_postiosoite", "toim_maa") as $key) {
						if($key == "toim_maa") {
							$pdf->writeToTemplate($h, 1, 0, $s[0], t($laskurow[$key], $kieli), "norm", "L", "", $tid);
						}
						else {
							$pdf->writeToTemplate($h, 1, 0, $s[0], $laskurow[$key], "norm", "L", "", $tid);
						}
						$h-=$pdf->ln();	
					}

					$h=195;
					//	Tehd‰‰n template
					$tid2=$pdf->template->create();
					
					$s=$pdf->teeSarakkeet(array(95,15,20,15));
					$pdf->writeToTemplate($h, 1, 0, $s[0], t("Tuote", $kieli), "norm", "L", "B", $tid2);
					$pdf->writeToTemplate($h, 1, $s[0], $s[1], t("M‰‰r‰", $kieli), "norm", "R", "B", $tid2);
					if($laskurow["rivihintoja_ei_nayteta"] == "") {
						$pdf->writeToTemplate($h, 1, $s[1], $s[2], t("Hinta/yks", $kieli), "norm", "R", "B", $tid2);
						$pdf->writeToTemplate($h, 1, $s[2], $s[3], t("Alv", $kieli), "norm", "R", "B", $tid2);
						$pdf->writeToTemplate($h, 1, $s[3], 0, t("Veroton arvo", $kieli), "norm", "R", "B", $tid2);	
					}
					$h-=$pdf->ln();
					$pdf->writeToTemplate($h, 1, 0, $s[0], t("Tilaus")."/".t("Kommentti", $kieli), "norm", "L", "B", $tid2);
					$pdf->writeToTemplate($h, 1, $s[0], $s[1], t("Toimituspvm", $kieli), "norm", "R", "B", $tid2);
					if($laskurow["rivihintoja_ei_nayteta"] == "") {
						if($alenaytetaan=="OK") {
							$pdf->writeToTemplate($h, 1, $s[1], $s[2], t("Ale", $kieli), "norm", "R", "B", $tid2);				
						}
						$pdf->writeToTemplate($h, 1, $s[3], 0, t("Verollinen arvo", $kieli), "norm", "R", "B", $tid2);					
					}

					//	Liitet‰‰n pohjat
					$pdf->placeTemplate($tid);
					$pdf->placeTemplate($tid2);					
				}
				else {
					$pdf->addPage("a4");					

					//	Liitet‰‰n pohjat									
					if($riviotsikot!="") {
						$pdf->placeTemplate($tid);
						$pdf->placeTemplate($tid2);						
					}
					else {
						$pdf->placeTemplate($tid);
					}
				}

				return $pdf;
			}			
		}
		
		$pdf = alku($pdf, $laskurow, $toim, $otsikko, $kieli, "JOO");
		$h = 180;
		if ($toim == 'PROFORMA') {
			if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"])) and $laskurow["vienti_kurssi"] != 0) {
				$hinta_riv = "(tilausrivi.hinta/$laskurow[vienti_kurssi])";
			}
			else {
				$hinta_riv = "tilausrivi.hinta";
			}

			$lisa = " 	$hinta_riv / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+$laskurow[erikoisale]-(tilausrivi.ale*$laskurow[erikoisale]/100))/100)) rivihinta,
						varattu kpl, ";
		}
		else {
			$lisa = "";
		}
		
		$sorttauskentta = generoi_sorttauskentta(4);
		if ($laskurow["tila"] == 'U') {
			$where = " uusiotunnus='$otunnus' ";
		}
		else {
			$where = " otunnus='$otunnus' ";
		}

		//	Voidaanko n‰ytt‰‰ alet?
		$query = "select tunnus from tilausrivi where yhtio = '$kukarow[yhtio]' and ale > 0 and $where";
		$abures = mysql_query($query) or pupe_error($query);
		if(mysql_num_rows($abures) > 0) {
			$alenaytetaan="OK";
		}
		else {
			$alenaytetaan="";
		}
		
		// haetaan tilauksen kaikki rivit
		$query = "  SELECT tilausrivi.*, if(perheid=0,rivihinta, (select sum(t.rivihinta) from tilausrivi t where $where and t.perheid=tilausrivi.perheid)) rivihinta, maat.nimi alkuperamaa, tuote.ei_saldoa ei_saldoa, tuote.tullinimike1, tuote.tullikohtelu,
					$lisa $sorttauskentta
					FROM tilausrivi
					JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
					LEFT JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio=tuote.yhtio and tuotteen_toimittajat.tuoteno = tuote.tuoteno 
					LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
					LEFT JOIN maat on maat.koodi=tuotteen_toimittajat.alkuperamaa
					WHERE $where and (perheid=0 or perheid=tilausrivi.tunnus or tilausrivin_lisatiedot.ei_nayteta='' or tilausrivin_lisatiedot.ei_nayteta IS NULL)
					GROUP BY tilausrivi.tunnus
					ORDER BY otunnus, sorttauskentta, tilausrivi.tuoteno, tilausrivi.tunnus";
		$riresult = mysql_query($query) or pupe_error($query);

		//kuollaan jos yht‰‰n rivi‰ ei lˆydy
		if (mysql_num_rows($riresult) == 0) {
			echo t("Laskurivej‰ ei lˆytynyt");
			exit;
		}
		
		$riresult = mysql_query($query) or pupe_error($query);

		// HUOM sama kuin alku funktiossa!
		
		if(mysql_num_rows($riresult)>0) {
		
			$s=$pdf->teeSarakkeet(array(95,15,20,15));	
			$summa=$summa_verolla=0;
			unset($edperhe);
			
			while ($row = mysql_fetch_array($riresult)) {

				if($h < 20) {
					$pdf = alku($pdf, $laskurow, $toim, $otsikko, $kieli, "JOO");
					$h = 190;
				}
				
				//	Etsit‰‰n k‰‰nnˆksi‰
				if (strtolower($kieli) != strtolower($yhtiorow['kieli'])) {
					$laji = 'nimitys_'.strtolower($kieli);
					$query = "SELECT selite FROM tuotteen_avainsanat WHERE yhtio = '$kukarow[yhtio]' and laji = '$laji' and tuoteno = '$row[tuoteno]' LIMIT 1";
					$nimresult = mysql_query($query) or pupe_error($query);
					if (mysql_num_rows($nimresult)>0) {
						$nimrow=mysql_fetch_array($nimresult);
						$row['nimitys']=$nimrow['selite'];
					}
				}

				if ($toim != "PROFORMA" and $laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
					// T‰n rivin alviton rivihinta
					$row["rivihinta"] = laskuval($row["hinta"], $laskurow["vienti_kurssi"])*$row["kpl"]*(1-$row["ale"]/100);

					if ($yhtiorow["alv_kasittely"] == '') {
						$row["rivihinta"] = $row["rivihinta"] / (1+$row["alv"]/100);
					}
				}
				
				//	Perheiden v‰liin laitetaan yksi extrav‰li..
				if(isset($edperhe) and $edperhe != $row["perheid"]) {
					$h-=$pdf->ln(1);
					unset($edperhe);
				}
				
				if($row["perheid"]==0 or $row["perheid"]==$row["tunnus"]) {
					$pdf->write($h, 1, 0, $s[0], $row["tuoteno"]." ".$row["nimitys"], "norm", "L", "");
					$pdf->write($h, 1, $s[0], $s[1], $row["kpl"]." ".strtolower($row["yksikko"]), "norm", "R", "");
					if($laskurow["rivihintoja_ei_nayteta"] == "") {
						$pdf->write($h, 1, $s[1], $s[2], money_format('%!i',$row["hinta"]), "norm", "R", "");
						if ($row["alv"] >= 500) {
							$pdf->write($h, 1, $s[2], $s[3], t("M.V.", $kieli), "norm", "R", "");					
						}
						else {
							$pdf->write($h, 1, $s[2], $s[3], $row["alv"]."%", "norm", "R", "");					
						}
						//	veroton rivihinta
						$pdf->write($h, 1, $s[3], 0, money_format('%!i',$row["rivihinta"]), "norm", "R", "");						
					}
					$h-=$pdf->ln();

					if (substr($row["toimitettuaika"],0,10) != "0000-00-00") {
						$pdf->write($h, 1, $s[0], $s[1], strftime("%x",strtotime($row["toimitettuaika"])), "snadi", "R", "I");
					}

					if($laskurow["rivihintoja_ei_nayteta"] == "") {
						if($alenaytetaan=="OK") {
							$pdf->write($h, 1, $s[1], $s[2], $row["ale"]."%", "snadi", "R", "I");				
						}
						//	verollinen rivihinta jos ei marginaalimyynti‰
						if ($row["alv"] < 500) {
							$pdf->write($h, 1, $s[3], 0, money_format('%!i',$row["rivihinta"]*(1+($row["alv"]/100))), "snadi", "R", "I");
						}
					}
					if($row["kommentti"] != "") {
						$abuw=$pdf->pt_mm($pdf->strlen($row["otunnus"]."/", $fonts["snadi"])+1);
						$pdf->write($h, 1, 0, $abuw, $row["otunnus"]."/", "snadi", "L", "");
						$nb=$pdf->countParagraphHeight($row["kommentti"], "snadi", ($s[0]-$abuw));
						$pdf->write($h, $nb, $abuw, $s[0], $row["kommentti"], "snadi", "L", "I");	
						$h-=$pdf->ln($nb);					
					}
					else {
						$pdf->write($h, 1, 0, $s[0], $row["otunnus"], "snadi", "L", "I");	
						$h-=$pdf->ln();					
					}

					if ($row["alv"] >= 500) {
						$pdf->write($h, 1, 0, $s[0], t("Ei sis‰ll‰ v‰hennett‰v‰‰ veroa", $kieli), "snadi", "L", "I");	
						$h-=$pdf->ln();
					}

					//	Vientilaskulle annetaan hieman lis‰infoa!
					if ($laskurow["vienti"] != "" and $row["ei_saldoa"] == "") {
						$pdf->write($h, 1, 5, $s[0], "Country of Origin: ".$row["alkuperamaa"], "snadi", "L", "I");	
						$h-=$pdf->ln();
						$pdf->write($h, 1, 5, $s[0], "Customs tariff number: ".$row["tullinimike1"], "snadi", "L", "I");	
						$h-=$pdf->ln();
						if($row["tullikohtelu"] != "") {
							$pdf->write($h, 1, 5, $s[0], t("Vientikohtelu", $kieli).": ".$row["tullikohtelu"], "snadi", "L", "I");	
							$h-=$pdf->ln();						
						}
					}

					if($row["asiakkaan_positio"] > 0 and $row["ei_saldoa"] == "") {
						$pdf->write($h, 1, 0, 0, t("RS-koodi", $kieli).": $laskurow[asiakkaan_kohde]-$row[asiakkaan_positio]", "snadi", "L", "I");
						$h-=$pdf->ln();
					}
					$summa+=$row["rivihinta"];
					$summa_verolla+=($row["rivihinta"]*(1+($row["alv"]/100)));

					$h-=$pdf->ln(0.5);					
				}
				else {
					$pdf->write($h, 1, 5, $s[0], $row["tuoteno"]." ".$row["nimitys"], "norm", "L", "");
					$pdf->write($h, 1, $s[0], $s[1], $row["kpl"]." ".strtolower($row["yksikko"]), "norm", "R", "");
					$h-=$pdf->ln(1);

					if($row["kommentti"] != "") {
						if($pdf->pt_mm($pdf->writeStrlen($row["otunnus"]."/".$row["kommentti"], "snadi")) > $s[0]) {
							$pdf->write($h, 2, 5, $s[0], $row["otunnus"]."/* ".$row["kommentti"], "snadi", "L", "I");	
							$h-=$pdf->ln(2);
						} 
						else {
							$pdf->write($h, 1, 5, $s[0], $row["otunnus"]."/* ".$row["kommentti"], "snadi", "L", "I");	
							$h-=$pdf->ln();
						}
					}
					
					$edperhe=$row["perheid"];
				}
			}
		}
		$h-=$pdf->ln(3);			
		
		// Alv erittely!
		$s=$pdf->teeSarakkeet(array(140,15,35));
		$pdf->write($h, 1, 0, $s[0], t("Veroton arvo yhteens‰", $kieli).":", "norm", "R", "B");
		if ($toim == 'PROFORMA' or ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"])))) {
			$pdf->write($h, 1, $s[1], 0, money_format('%i',$summa), "norm", "R", "B");
		}
		else {
			$pdf->write($h, 1, $s[1], 0, money_format('%i',$laskurow["arvo"]), "norm", "R", "B");
		}
		$h-=$pdf->ln();

		if ($toim == 'PROFORMA') {
			$where = " otunnus = '$laskurow[tunnus]' ";
		}
		else {
			$where = " uusiotunnus = '$laskurow[tunnus]' ";
		}

		//Haetaan kaikki alvikannat riveilt‰
		$alvquery = "	SELECT alv
						FROM tilausrivi
						WHERE $where and yhtio='$kukarow[yhtio]'
						GROUP BY alv";
		$alvresult = mysql_query($alvquery) or pupe_error($alvquery);

		while ($alvrow = mysql_fetch_array($alvresult)) {
			// Valuuttajutu kuntoon
			if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"])) and $laskurow["vienti_kurssi"] != 0) {
				$rivihinta_q 	= "(rivihinta/$laskurow[vienti_kurssi])";
				$hinta_q 		= "(hinta/$laskurow[vienti_kurssi])";
			}
			else {
				$rivihinta_q 	= "rivihinta";
				$hinta_q 		= "hinta";
			}

			if ($alvrow["alv"] >= 500 and $toim != 'PROFORMA') {
				$aquery = "	SELECT sum(0) alvrivihinta, sum($rivihinta_q) rivihinta
							FROM tilausrivi
							WHERE uusiotunnus = '$laskurow[tunnus]' and yhtio='$kukarow[yhtio]' and alv='$alvrow[alv]'";
			}
			elseif ($alvrow["alv"] >= 500) {
				$aquery = "	SELECT round(0) alvrivihinta, round(sum($hinta_q*varattu*(1-ale/100)),2) rivihinta
							FROM tilausrivi
							WHERE otunnus = '$laskurow[tunnus]' and yhtio='$kukarow[yhtio]' and alv='$alvrow[alv]'";
			}
			elseif ($alvrow["alv"] < 500 and $toim != 'PROFORMA') {
				$aquery = "	SELECT round(sum($rivihinta_q*(alv/100)),2) alvrivihinta, sum($rivihinta_q) rivihinta
							FROM tilausrivi
							WHERE uusiotunnus = '$laskurow[tunnus]' and yhtio='$kukarow[yhtio]' and alv='$alvrow[alv]'";
			}
			else {
				$aquery = "	SELECT
							round(sum($hinta_q / if('$yhtiorow[alv_kasittely]' = '', (1+alv/100), 1) * varattu * if(netto='N', (1-ale/100), (1-(ale+$laskurow[erikoisale]-(ale*$laskurow[erikoisale]/100))/100)) * (alv/100)),2) alvrivihinta,
							round(sum($hinta_q / if('$yhtiorow[alv_kasittely]' = '', (1+alv/100), 1) * varattu * if(netto='N', (1-ale/100), (1-(ale+$laskurow[erikoisale]-(ale*$laskurow[erikoisale]/100))/100))),2) rivihinta
							FROM tilausrivi
							WHERE otunnus = '$laskurow[tunnus]' and yhtio='$kukarow[yhtio]' and alv='$alvrow[alv]'";
			}
			$aresult = mysql_query($aquery) or pupe_error($aquery);
			$arow = mysql_fetch_array($aresult);

			$pdf->write($h, 1, 0, $s[0], t("Arvonlis‰vero", $kieli)." ($arow[rivihinta])", "norm", "R", "");

			if ($alvrow["alv"] >= 500) {
				$pdf->write($h, 1, $s[0], $s[1], "M.V.", "norm", "R", "");							
			}
			else {
				$pdf->write($h, 1, $s[0], $s[1], $alvrow["alv"]."%", "norm", "R", "");
			}
			
			$pdf->write($h, 1, $s[1], 0, money_format('%i', $arow["alvrivihinta"]), "norm", "R", "");	
			
			$h-=$pdf->ln();
		}

		$pdf->write($h, 1, 0, $s[0], t("Lasku yhteens‰", $kieli).":", "norm", "R", "B");
		if ($toim == 'PROFORMA' or ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"])))) {
			$pdf->write($h, 1, $s[1], 0, money_format('%i',$summa_verolla), "norm", "R", "B");
			$laskusumma=$summa_verolla;
		}
		else {
			$pdf->write($h, 1, $s[1], 0, money_format('%i',$laskurow["summa"]), "norm", "R", "B");
			$laskusumma=$laskurow["summa"];			
		}
		$h-=$pdf->ln(4);
				
		if($h < 35) {
			$pdf = alku($pdf, $laskurow, $toim, $otsikko, $kieli);
		}

		$h=50;
		$s=$pdf->teeSarakkeet(array(25,30,45,25,35));		
		$pdf->write($h, 1, 0, $s[1], t("MAKSUTIEDOT", $kieli), "norm", "L", "B");
		$h-=$pdf->ln(1.25);

		$query = "	SELECT *
					FROM maksuehto
					WHERE yhtio = '$kukarow[yhtio]' and tunnus = $laskurow[maksuehto]";
		$result = mysql_query($query) or pupe_error($query);
		$row = mysql_fetch_array($result);
		$pdf->write($h, 1, 0, 		$s[0], t("Laskun pvm", $kieli).":", "norm", "L", "");
		$pdf->write($h, 1, $s[0], 	$s[1], strftime("%x",strtotime($laskurow["tapvm"])), "norm", "L", "");
		$h-=$pdf->ln();
		$pdf->write($h, 1, 0, 		$s[0], t("Maksuehto", $kieli).":", "norm", "L", "");
		$pdf->write($h, 1, $s[0], 	$s[1], t($row["teksti"], $kieli), "norm", "L", "");
		$h-=$pdf->ln();
		$pdf->write($h, 1, 0, 		$s[0], t("Er‰p‰iv‰", $kieli).":", "norm", "L", "");
		$pdf->write($h, 1, $s[0], 	$s[1], strftime("%x",strtotime($laskurow["erpcm"])), "norm", "L", "");
		$h-=$pdf->ln();
		$pdf->write($h, 1, 0, 		$s[0], t("Tilinumero", $kieli).":", "norm", "L", "");
		$pdf->write($h, 1, $s[0], 	$s[1], 		$yhtiorow["pankkitili1"], "norm", "L", "");
		$h-=$pdf->ln();
		$pdf->write($h, 1, 0, 		$s[0], t("Viitenumero", $kieli).":", "norm", "L", "");
		$pdf->write($h, 1, $s[0], 	$s[1], 		$laskurow["viite"], "norm", "L", "");
		$h-=$pdf->ln();
		$pdf->write($h, 1, 0, 		$s[0], t("Summa", $kieli).":", "norm", "L", "");
		$pdf->write($h, 1, $s[0], 	$s[1], 		money_format('%i',$laskusumma), "norm", "L", "");
		$h-=$pdf->ln();		
		//Duusataan pankkitiedot ovelsati reversen‰!
		for($i=1;$i<3;$i++) {
			if($yhtiorow["pankkitili$i"] != "") {		
				$h+=$pdf->ln();		
				$pdf->write($h, 1, $s[1], 	$s[2], 	$yhtiorow["pankkinimi$i"], "snadi", "L", "");
				$pdf->write($h, 1, $s[2], 	$s[3], 	$yhtiorow["pankkitili$i"], "snadi", "L", "");
				$pdf->write($h, 1, $s[3], 	$s[4], 	$yhtiorow["pankkiiban$i"], "snadi", "L", "");
				$pdf->write($h, 1, $s[4], 	0, 		$yhtiorow["pankkiswift$i"], "snadi", "L", "");								
			} 
		}
		$h+=$pdf->ln();		
		$pdf->write($h, 1, $s[1], $s[2], t("Pankki", $kieli), "norm", "L", "");		
		$pdf->write($h, 1, $s[2], $s[3], t("Tilinumero", $kieli), "norm", "L", "");				
		$pdf->write($h, 1, $s[3], $s[4], t("IBAN", $kieli), "norm", "L", "");
		$pdf->write($h, 1, $s[4], 0, t("SWIFT", $kieli), "norm", "L", "");		
		
		//	Loppuun duusataan yrityksen perustiedot
		$query="select SUBSTRING(nimi FROM 6) nimi from maat where koodi='$yhtiorow[maa]'";
		$maares=mysql_query($query) or pupe_error($query);
		$maa=mysql_fetch_array($maares);
		
		$h = 15;
		$s=$pdf->teeSarakkeet(array(70,15,58,15));
		$pdf->write($h, 1, 0, $s[0], 		$yhtiorow["nimi"], "norm", "L", "B");
		$pdf->write($h, 1, $s[0], $s[1], 	t("Puh", $kieli).":", "norm", "L", "");
		$pdf->write($h, 1, $s[1], $s[2], 	$yhtiorow["puhelin"], "norm", "L", "");		
		if($laskurow["maa"] == "FI") {
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
		if($laskurow["maa"] == "FI") {
			$pdf->write($h, 1, $s[2], $s[3], 	t("VAT", $kieli).":", "norm", "L", "");					
			$pdf->write($h, 1, $s[3], 0, 		$yhtiorow["maa"].sprintf("%08.8s",ereg_replace("[^0-9]","",$yhtiorow["ytunnus"])), "norm", "R", "");		
		}		
		$h-=$pdf->ln();

		$pdf->write($h, 1, 0, $s[1], 		$yhtiorow["maa"].$yhtiorow["postino"]." ".$yhtiorow["postitp"], "norm", "L", "");
		$pdf->write($h, 1, $s[0], $s[1], 	t("Web", $kieli).":", "norm", "L", "");
		$pdf->write($h, 1, $s[1], $s[2], 	$yhtiorow["www"], "norm", "L", "");		
		$h-=$pdf->ln();

		$pdf->write($h, 1, 0, $s[0], 		t($maa["nimi"],$kieli), "norm", "L", "");
		$pdf->write($h, 1, $s[0], $s[1], 	t("Email", $kieli).":", "norm", "L", "");
		$pdf->write($h, 1, $s[1], $s[2], 	$yhtiorow["email"], "norm", "L", "");
		
		// Tulostetaan sivu
		//keksit‰‰n uudelle failille joku varmasti uniikki nimi:
		list($usec, $sec) = explode(' ', microtime());
		mt_srand((float) $sec + ((float) $usec * 100000));
		$pdf_laskufilenimi = "/tmp/Lasku-".md5(uniqid(mt_rand(), true)).".pdf";

		//duusataan sivunumerot!
		foreach($pdf->objects as $key => $value) {
			if($value["type"] == "page") {
				$pages[] = $key;
			}
		}		
		foreach($pages as $key => $value) {
			$pdf->write(280, 2, 0, 0, ($key+1)."/".count($pages), "snadi", "R", "I", $value);
		}
		
		//kirjoitetaan pdf faili levylle..
		$fh = fopen($pdf_laskufilenimi, "w");
		if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF create error $pdf_laskufilenimi");
		fclose($fh);
		
		//	Jos ollaan verkkolaskumoduulissa tulostus hoidetaan toisella tapaa
		if(!strpos($_SERVER["SCRIPT_NAME"], "tulostakopio.php")) {
			//haetaan varaston tiedot
			if ($yhtiorow["lasku_tulostin"] == "AUTOMAAGINEN_VALINTA") {
				if ($varasto != 0) {
					$query = "select * from varastopaikat where yhtio='$kukarow[yhtio]' and tunnus='$varasto' order by alkuhyllyalue,alkuhyllynro";
				}
				else {
					$query = "select * from varastopaikat where yhtio='$kukarow[yhtio]' order by alkuhyllyalue,alkuhyllynro limit 1";
				}
				$prires= mysql_query($query) or pupe_error($query);
				$prirow= mysql_fetch_array($prires);
				$yhtiorow['lasku_tulostin'] = $prirow["printteri5"];

				$tulos_ulos .= t("Lasku tulostuu varastoon").": $prirow[nimitys]<br>\n";
			}

			$querykieli = "	select *
							from kirjoittimet
							where yhtio='$kukarow[yhtio]' and tunnus='$yhtiorow[lasku_tulostin]'";
			$kires = mysql_query($querykieli) or pupe_error($querykieli);
			$kirow = mysql_fetch_array($kires);

			$tulos_ulos .= t("Lasku tulostuu kirjoittimelle").": $kirow[kirjoitin]<br>\n";

			if ($kirow["komento"] != "email") {
				// itse print komento...
				$line = exec("$kirow[komento] $pdf_laskufilenimi");
			}
			elseif ($kukarow["eposti"] != '') {
				// l‰hetet‰‰n meili
				$kutsu = ucfirst(strtolower($otsikko))." $laskurow[laskunro]";	
				$liite = $pdf_laskufilenimi;
				include ("inc/sahkoposti.inc"); // sanotaan include eik‰ require niin ei kuolla
			}

			if ($valittu_kopio_tulostin != '') {
				$querykieli = "	select *
								from kirjoittimet
								where yhtio='$kukarow[yhtio]' and tunnus='$valittu_kopio_tulostin'";
				$kires = mysql_query($querykieli) or pupe_error($querykieli);
				$kirow = mysql_fetch_array($kires);

				$tulos_ulos .= t("Laskukopio tulostuu kirjoittimelle").": $kirow[kirjoitin]<br>\n";

				if ($kirow["komento"] != "email") {
					// itse print komento...
					$line = exec("$kirow[komento] $pdf_laskufilenimi");
				}
				elseif ($kukarow["eposti"] != '') {
					// l‰hetet‰‰n meili
					$kutsu = ucfirst(strtolower($otsikko))." $laskurow[laskunro]";
					$liite = $pdf_laskufilenimi;
					include ("inc/sahkoposti.inc"); // sanotaan include eik‰ require niin ei kuolla
				}
			}
		}
		else {
			if ($komento == 'email' or substr($komento,0,12) == 'asiakasemail') {
				$liite = $pdf_laskufilenimi;
				$kutsu = ucfirst(strtolower($otsikko))." $laskurow[laskunro]";
				
				echo t(ucfirst(strtolower($otsikko))." l‰hetet‰‰n osoitteeseen '$kukarow[eposti]'")."...<br>";

				if ($kukarow["extranet"] == '') {
					require("../inc/sahkoposti.inc");
				}
				else {
					require("sahkoposti.inc");
				}
			}
			elseif ($tee == 'NAYTATILAUS') {
				//Tyˆnnet‰‰n tuo pdf vaan putkeen!
				echo file_get_contents($pdf_laskufilenimi);
			}
			elseif ($komento != '' and $komento != 'edi') {
				//	Haetaan kirjoittimen nimi..
				$querykieli = "	select *
								from kirjoittimet
								where yhtio='$kukarow[yhtio]' and komento='$komento'";
				$kires = mysql_query($querykieli) or pupe_error($querykieli);
				if(mysql_num_rows($kires) > 0) {
					$kirow = mysql_fetch_array($kires);
					echo t("Lasku tulostuu kirjoittimelle").": $kirow[kirjoitin]<br>\n";						
					
					echo t("Lasku tulostuu")."...<br>";
					unset($returnvalue);
					$line = exec("$komento $pdf_laskufilenimi", $output, $returnvalue);
				}
				if(mysql_num_rows($result) == 0 or $returnvalue!=0) {
					echo "<font class='error'>".t("Laskun tulostus ep‰onnistui")."!!!</font><br>\n";	
				}
			}			
		}

		//poistetaan tmp file samantien kuleksimasta...
		system("rm -f $pdf_laskufilenimi");				
	}
}

?>
