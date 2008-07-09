<?php

require_once('pdflib/pupepdf.class.php');

if (!function_exists('tulosta_lasku')) {
	function tulosta_lasku ($otunnus, $komento, $kieli = "", $toim, $tee, $content_subject="", $content_body="") {
		global $yhtiorow, $kukarow, $fonts, $tulos_ulos, $_SERVER, $tid, $h;
		
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

		// Haetaan laskun tiedot ja joinataan viimeisimmän laskun_lisätiedot...
		$query = "	SELECT lasku.*, laskun_lisatiedot.*, if(laskunro=0, '', laskunro) laskunro, lasku.tunnus tunnus, valkoodi valuutta, lasku.nimi nimi, if(lasku.tapvm='0000-00-00', now(), lasku.tapvm) päiväys, lasku.tunnusnippu projekti, if(lasku.tunnusnippu=0, lasku.tunnus,tunnusnippu) tilaus, viesti asiakkaan_viite, sisviesti1 lisätiedot, lasku.ytunnus asiakkaan_VAT,
					(select if(count(distinct(left(toimitettuaika,10)))>1,'useita', max(toimitettuaika)) from tilausrivi where yhtio=lasku.yhtio and $mika=lasku.tunnus) toimituspvm, '$yhtiorow[viivastyskorko]%' viivästyskorko, 
					concat_ws(' ', postino, postitp) postiosoite,
					concat_ws(' ', toim_postino, toim_postitp) toim_postiosoite,
					concat_ws(' ', laskutus_postino, laskutus_postitp) laskutus_postiosoite,							
					yhteyshenkilo_kaupallinen.nimi asiakkaan_kaupallinen_käsittelijä,
					yhteyshenkilo_tekninen.nimi asiakkaan_yhteyshenkilö,
					lasku.tunnus toimitus,
					lasku.pyoristys
					FROM lasku
					LEFT JOIN laskun_lisatiedot ON lasku.yhtio=laskun_lisatiedot.yhtio and if(laskunro>0, (select max(tunnus) from lasku l where l.yhtio=lasku.yhtio and l.laskunro=lasku.laskunro and l.tila='L'), lasku.tunnus)=laskun_lisatiedot.otunnus
					LEFT JOIN yhteyshenkilo yhteyshenkilo_tekninen ON laskun_lisatiedot.yhtio=yhteyshenkilo_tekninen.yhtio and laskun_lisatiedot.yhteyshenkilo_tekninen=yhteyshenkilo_tekninen.tunnus					
					LEFT JOIN yhteyshenkilo yhteyshenkilo_kaupallinen ON laskun_lisatiedot.yhtio=yhteyshenkilo_kaupallinen.yhtio and laskun_lisatiedot.yhteyshenkilo_kaupallinen=yhteyshenkilo_kaupallinen.tunnus
					LEFT JOIN kuka AS laatija ON laatija.yhtio=lasku.yhtio and lasku.laatija=laatija.kuka
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
		
		if($laskurow["toim_nimi"] == "Useita") {
			$laskurow["toim_nimi"] = t("Useita", $kieli);
		}

		if($laskurow["toimituspvm"] == "useita") {
			$laskurow["toimituspvm"] = t("useita", $kieli);
		}
		
		if ($laskurow["vienti"] == "") {
			if ($laskurow["summa"] >= 0) {
				if ($toim == 'PROFORMA') {
					if($laskurow["toim_maa"] == "US") {
						$otsikko = "TULLAUSLASKU";
					}
					else {
						$otsikko = "PROFORMALASKU";
					}					
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
				if($laskurow["toim_maa"] == "US") {
					$otsikko = "TULLAUSLASKU";
				}
				else {
					$otsikko = "PROFORMALASKU";
				}					
			}
			elseif ($kateiskuitti != ""){
				$otsikko = "KÄTEISKUITTI";					
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
				global $yhtiorow, $kukarow, $fonts, $tid, $h;

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

					//	Tehdään sivu jotta saadaan currentPage kuntoon
					$pdf->addPage("a4");	

					//	Tehdään template
					static $tid;
					$tid=$pdf->template->create();
													
					$h=282;
							
					//	Liitetään kuva
					if( (int) $yhtiorow["lasku_logo"] > 0) {
						$liite = hae_liite($yhtiorow["lasku_logo"], "Yllapito", "array");
						$data = $liite["data"];
						$pdf->placeImageToTemplate($h,0,$liite, $tid, "57x100");

						unset($liite);
					}
					elseif(file_exists($yhtiorow["lasku_logo"])) {
						$pdf->placeImageToTemplate($h,0,$yhtiorow["lasku_logo"], $tid, "57x100");
					}

					$pdf->writeToTemplate($h, 1, 0, 0, t($otsikko, $kieli), "BIG", "C", "B", $tid);

					//	Laitetaan perustiedot oikealle		
					$h=260;
					$s=$pdf->teeSarakkeet(array(102,33));
					
					$query = "select tunnus from lasku where yhtio ='$kukarow[yhtio]' and tunnusnippu='$laskurow[projekti]' and tila = 'R' and tunnusnippu>0";
					$abures = mysql_query($query) or pupe_error($query);
					if(mysql_num_rows($abures) == 1) {
						$tyyppi = "projekti";
					}
					else {
						$tyyppi = "tilaus";
					}
					
					if($laskurow["tunnusnippu"]>0) {
						if($laskurow["tila"] != 'U') {
							$kentat = array("laskunro", "päiväys", "toimituspvm", $tyyppi, "toimitus", "valuutta", "viivästyskorko", "toimitustapa", "toimitusehto", "asiakkaan_VAT");	
						}
						else {
							$kentat = array("laskunro", "päiväys", "toimituspvm", $tyyppi, "valuutta", "viivästyskorko", "toimitustapa", "toimitusehto", "asiakkaan_VAT");
						}
					}
					else {
						if($laskurow["tila"] != 'U') {
							$kentat = array("laskunro", "päiväys", "toimituspvm", "tilaus", "valuutta", "viivästyskorko", "toimitustapa", "toimitusehto", "asiakkaan_VAT");	
						}
						else {
							$kentat = array("laskunro", "päiväys", "toimituspvm", "valuutta", "viivästyskorko", "toimitustapa", "toimitusehto", "asiakkaan_VAT");
						}
					}

					foreach($kentat as $key) {
						if($key == "päiväys" or $key == "toimituspvm") {
							$pdf->writeToTemplate($h, 1, $s[0], $s[1], t(str_replace("_"," ",ucfirst($key)), $kieli).":", "norm", "L", "B", $tid);
							$pdf->writeToTemplate($h, 1, $s[1]+2, 0, strftime("%x",strtotime($laskurow[$key])), "norm", "L", "", $tid);
							$h-=$pdf->ln();

						}
						elseif(trim($laskurow[$key])!="") {
							$pdf->writeToTemplate($h, 1, $s[0], $s[1], t(str_replace("_"," ",ucfirst($key)), $kieli).":", "norm", "L", "B", $tid);
							$pdf->writeToTemplate($h, 1, $s[1]+2, 0, $laskurow[$key], "norm", "L", "", $tid);
							$h-=$pdf->ln();
						}
					}
					$h-=$pdf->ln(0.5);

					//	Sitten yhteyshenkilöitä
					foreach(array("asiakkaan_tilausnumero", "asiakkaan_viite", "toimittajan_yhteyshenkilö", "asiakkaan_yhteyshenkilö", "asiakkaan_kaupallinen_käsittelijä") as $key) {
						if(trim($laskurow[$key]) != "") {
							$pdf->writeToTemplate($h, 1, $s[0], 0, t(str_replace("_"," ",ucfirst($key)), $kieli).":", "norm", "L", "B", $tid);
							$h-=$pdf->ln();					
							$nb=$pdf->countParagraphHeight($laskurow[$key], "norm", (-1*($s[0]-5)));										
							$pdf->writeToTemplate($h, $nb, $s[0], 0, $laskurow[$key], "norm", "L", "", $tid);
							$h-=$pdf->ln($nb)+0.25;
						}
					}

					//	Laitetaan osoitetiedot vasemmalle
					$h=260;
					if($laskurow["laskutus_nimi"] != "") {
						$pdf->writeToTemplate($h, 1, 0, $s[1], t("Laskutusosoite", $kieli).":", "norm", "L", "B", $tid);
						$h-=$pdf->ln();
						foreach(array("laskutus_nimi", "laskutus_nimitark", "laskutus_osoite", "laskutus_postiosoite", "laskutus_maa") as $key) {
							if($key == "laskutus_maa") {
								$pdf->writeToTemplate($h, 1, 0, $s[0], strtoupper(maa($laskurow[$key], $kieli)), "norm", "L", "", $tid);
							}
							else {
								$pdf->writeToTemplate($h, 1, 0, $s[0], $laskurow[$key], "norm", "L", "", $tid);
							}
							$h-=$pdf->ln();
						}
						$h-=$pdf->ln();	
						
						$pdf->writeToTemplate($h, 1, 0, $s[1], t("Ostaja", $kieli).":", "norm", "L", "B", $tid);
					}							
					else {
						$pdf->writeToTemplate($h, 1, 0, $s[1], t("Ostaja/Laskutusosoite", $kieli).":", "norm", "L", "B", $tid);
					}
					
					$h-=$pdf->ln();
					foreach(array("nimi", "nimitark", "osoite", "postiosoite", "maa") as $key) {
						if($key == "maa") {
							$pdf->writeToTemplate($h, 1, 0, $s[0], strtoupper(maa($laskurow[$key], $kieli)), "norm", "L", "", $tid);
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
							$pdf->writeToTemplate($h, 1, 0, $s[0], strtoupper(maa($laskurow[$key], $kieli)), "norm", "L", "", $tid);
						}
						else {
							$pdf->writeToTemplate($h, 1, 0, $s[0], $laskurow[$key], "norm", "L", "", $tid);
						}
						$h-=$pdf->ln();	
					}
					
					if($laskurow["laskutus_nimi"] != "") {
						$h=170;
					}
					else {
						$h=190;
					}
					
					//	Tehdään template
					$tid2=$pdf->template->create();
					
					$s=$pdf->teeSarakkeet(array(95,15,20,15));
					$pdf->writeToTemplate($h, 1, 0, $s[0], t("Tuote", $kieli), "norm", "L", "B", $tid2);
					$pdf->writeToTemplate($h, 1, $s[0], $s[1], t("Määrä", $kieli), "norm", "R", "B", $tid2);
					if($laskurow["rivihintoja_ei_nayteta"] == "") {
						$pdf->writeToTemplate($h, 1, $s[1], $s[2], t("Hinta/yks", $kieli), "norm", "R", "B", $tid2);
						$pdf->writeToTemplate($h, 1, $s[2], $s[3], t("Alv", $kieli), "norm", "R", "B", $tid2);
						$pdf->writeToTemplate($h, 1, $s[3], 0, t("Veroton arvo", $kieli), "norm", "R", "B", $tid2);	
					}
					$h-=$pdf->ln();

					if($laskurow != "U") {
						$pdf->writeToTemplate($h, 1, 0, $s[0], t("Kommentti", $kieli), "norm", "L", "B", $tid2);	
					}
					else {
						$pdf->writeToTemplate($h, 1, 0, $s[0], t("Tilaus", $kieli)."/".t("Kommentti", $kieli), "norm", "L", "B", $tid2);
					}
					if($laskurow["toimituspvm"] == "useita") {
						$pdf->writeToTemplate($h, 1, $s[0], $s[1], t("Toimituspvm", $kieli), "norm", "R", "B", $tid2);
					}					
					if($laskurow["rivihintoja_ei_nayteta"] == "") {
						if($alenaytetaan=="OK") {
							$pdf->writeToTemplate($h, 1, $s[1], $s[2], t("Ale", $kieli), "norm", "R", "B", $tid2);				
						}
						$pdf->writeToTemplate($h, 1, $s[3], 0, t("Verollinen arvo", $kieli), "norm", "R", "B", $tid2);					
					}

					//	Liitetään pohjat
					$pdf->placeTemplate($tid);
					$pdf->placeTemplate($tid2);					
				}
				else {
					$pdf->addPage("a4");					

					//	Liitetään pohjat									
					if($riviotsikot!="") {
						$pdf->placeTemplate($tid);
						$pdf->placeTemplate($tid2);						
					}
					else {
						$pdf->placeTemplate($tid);
					}
				}
				
				if($laskurow["laskutus_nimi"] != "") {
					$h=160;
				}
				else {
					$h=180;
				}
				
				return $pdf;
			}			
		}

		$pdf = alku($pdf, $laskurow, $toim, $otsikko, $kieli, "JOO");

		if ($toim == 'PROFORMA') {
			if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"])) and $laskurow["vienti_kurssi"] != 0) {
				$hinta_riv = "(tilausrivi.hinta/$laskurow[vienti_kurssi])";
			}
			else {
				$hinta_riv = "tilausrivi.hinta";
			}

			$lisa = " 	$hinta_riv / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+$laskurow[erikoisale]-(tilausrivi.ale*$laskurow[erikoisale]/100))/100)) rivihinta,
						(jt+varattu+kpl) kpl, ";
		}
		else {
			$lisa = "";
		}
		
		// katotaan miten halutaan sortattavan
		$sorttauskentta = generoi_sorttauskentta($yhtiorow["laskun_jarjestys"]);
		
		if ($laskurow["tila"] == 'U') {
			$where = " uusiotunnus='$otunnus' ";
		}
		else {
			$where = " otunnus='$otunnus' ";
		}

		//	Voidaanko näyttää alet?
		$query = "select tunnus from tilausrivi where yhtio = '$kukarow[yhtio]' and ale > 0 and $where";
		$abures = mysql_query($query) or pupe_error($query);
		
		if(mysql_num_rows($abures) > 0) {
			$alenaytetaan="OK";
		}
		else {
			$alenaytetaan="";
		}
		
		// haetaan tilauksen kaikki rivit
		$query = "  SELECT tilausrivi.*, if(perheid=0,rivihinta, (select sum(t.rivihinta) from tilausrivi t where t.yhtio='$kukarow[yhtio]' and $where and t.perheid=tilausrivi.perheid)) rivihinta, tuote.ei_saldoa ei_saldoa, tuote.tullinimike1, tuote.tullikohtelu, alkuperamaa,
					$lisa $sorttauskentta
					FROM tilausrivi
					JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
					LEFT JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio=tuote.yhtio and tuotteen_toimittajat.tuoteno = tuote.tuoteno 
					LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
					WHERE tilausrivi.yhtio='$kukarow[yhtio]' 
					and $where 
					and (tilausrivi.perheid = 0 or tilausrivi.perheid=tilausrivi.tunnus or tilausrivin_lisatiedot.ei_nayteta !='E' or tilausrivin_lisatiedot.ei_nayteta is null)
					GROUP BY tilausrivi.tunnus
					ORDER BY otunnus, sorttauskentta $yhtiorow[laskun_jarjestys_suunta], tilausrivi.tunnus";
		$riresult = mysql_query($query) or pupe_error($query);

		//kuollaan jos yhtään riviä ei löydy
		if (mysql_num_rows($riresult) == 0) {
			echo t("Laskurivejä ei löytynyt");
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
				}
				
				$query = "	SELECT vienti_kurssi 
							FROM lasku 
							WHERE yhtio = '$kukarow[yhtio]' 
							and tunnus 	= '$row[otunnus]'";
				$valres = mysql_query($query) or pupe_error($query);
				$valrow = mysql_fetch_array($valres);

				if ($valrow["vienti_kurssi"] > 0) {
					$kurssi = $valrow["vienti_kurssi"];
				}
				else {
					$kurssi = 1;
				}
				
				//	Etsitään käännöksiä
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
					// Tän rivin alviton rivihinta
					$row["rivihinta"] = laskuval($row["hinta"], $laskurow["vienti_kurssi"])*$row["kpl"]*(1-$row["ale"]/100);

					if ($yhtiorow["alv_kasittely"] == '') {
						$row["rivihinta"] = $row["rivihinta"] / (1+$row["alv"]/100);
					}
				}
				
				//	Perheiden väliin laitetaan yksi extraväli..
				if(isset($edperhe) and $edperhe != $row["perheid"]) {
					$h-=$pdf->ln(1);
					unset($edperhe);
				}
				
				//	Jos meillä on "normaali tuote"
				if($row["perheid"]==0 or $row["perheid"]==$row["tunnus"]) {
					$offset=0;					
					$style_rivi = "norm";
					$style_rivi2 = "snadi";					
					$style_komm = "snadi";
					//	Jos meillä on isätuote fiksataan yksikköhinta
					if($row["perheid"]==$row["tunnus"]) {
						$row["hinta"] = round(($row["rivihinta"]/$row["kpl"]),$yhtiorow['hintapyoristys']);
						
						//	Ale 100 on varmaan feikki joten se piilotetaan..
						if($row["ale"] == 100) {
							$row["ale"] = "";
						}
					}
					$rivi="isa";
				}
				//	Meillä on lapsituote
				else{
					$offset=5;
					$style_rivi = "snadi";
					$style_rivi2 = "snadi";										
					$style_komm = "snadi";
					$rivi="lapsi";
				}
				$pdf->write($h, 1, $offset, $s[0], $row["tuoteno"]." ".$row["nimitys"], $style_rivi, "L", "");
				$pdf->write($h, 1, $s[0], $s[1], round($row["kpl"], 2)." ".ta($kieli, "Y", $row["yksikko"]), $style_rivi, "R", "");
				
				if($rivi=="isa") {
					if($laskurow["rivihintoja_ei_nayteta"] == "") {
						$pdf->write($h, 1, $s[1], $s[2], money_format('%!i',laskuval($row["hinta"], $kurssi)), $style_rivi, "R", "");
						if ($row["alv"] >= 500) {
							$pdf->write($h, 1, $s[2], $s[3], t("M.V.", $kieli), $style_rivi, "R", "");					
						}
						else {
							$pdf->write($h, 1, $s[2], $s[3], $row["alv"]."%", $style_rivi, "R", "");					
						}
						//	veroton rivihinta
						$pdf->write($h, 1, $s[3], 0, money_format('%!i',$row["rivihinta"]), $style_rivi, "R", "");						
					}
					$h-=$pdf->ln();

					if (substr($row["toimitettuaika"],0,10) != "0000-00-00" and $laskurow["toimituspvm"] == "useita") {
						$pdf->write($h, 1, $s[0], $s[1], strftime("%x",strtotime($row["toimitettuaika"])), $style_rivi2, "R", "I");
					}

					if($laskurow["rivihintoja_ei_nayteta"] == "") {
						if($alenaytetaan=="OK" and $row["ale"] > 0) {
							$pdf->write($h, 1, $s[1], $s[2], $row["ale"]."%", $style_rivi2, "R", "I");
						}
						//	verollinen rivihinta jos ei marginaalimyyntiä
						if ($row["alv"] < 500) {
							$pdf->write($h, 1, $s[3], 0, money_format('%!i',$row["rivihinta"]*(1+($row["alv"]/100))), $style_rivi2, "R", "I");
						}
					}
				}
				else {
					$h-=$pdf->ln();
				}
				
				if($row["kommentti"] != "" and $rivi=="isa") {
					if($laskurow["tila"] != "U") {
						$nb=$pdf->countParagraphHeight($row["kommentti"], $style_komm, ($s[0]-$abuw));
						$pdf->write($h, $nb, $offset, $s[0], $row["kommentti"], $style_komm, "L", "I");	
					}
					else {
						$abuw=$pdf->pt_mm($pdf->strlen($row["otunnus"]."/", $fonts["snadi"])+1);
						$pdf->write($h, 1, $offset, $abuw, $row["otunnus"]."/", $style_komm, "L", "");
						$nb=$pdf->countParagraphHeight($row["kommentti"], $style_komm, ($s[0]-$abuw));
						$pdf->write($h, $nb, $abuw+$offset, $s[0], $row["kommentti"], $style_komm, "L", "I");	
					}
					$h-=$pdf->ln($nb);					
				}
				elseif($row["kommentti"] != "") {
					$nb=$pdf->countParagraphHeight($row["kommentti"], $style_komm, ($s[0]-$abuw));
					$pdf->write($h, $nb, 5+$offset, $s[0], $row["kommentti"], $style_komm, "L", "I");	
					$h-=$pdf->ln($nb);					
				}
				elseif($laskurow["tila"] != "U") {
					$pdf->write($h, 1, $offset, $s[0], $row["otunnus"]."/", $style_komm, "L", "I");	
					$h-=$pdf->ln();
				}

				if ($row["alv"] >= 500) {
					$pdf->write($h, 1, 5+$offset, $s[0], t("Ei sisällä vähennettävää veroa", $kieli), "snadi", "L", "I");	
					$h-=$pdf->ln();
				}

				//	Oisko sarjanumeroita
				if ($row["kpl"]+$row["varattu"] > 0){
					$sarjanutunnus = "myyntirivitunnus";
				}
				else {
					$sarjanutunnus = "ostorivitunnus";
				}

				$query = "	select *
							from sarjanumeroseuranta
							where yhtio = '$kukarow[yhtio]'
							and tuoteno = '$row[tuoteno]'
							and $sarjanutunnus='$row[tunnus]'
							and sarjanumero != ''";
				$sarjares = mysql_query($query) or pupe_error($query);
				
				if (mysql_num_rows($sarjares) > 0){
					while($sarjarow = mysql_fetch_array($sarjares)) {
						$pdf->write($h, 1, 5+$offset, $s[0], t("sn", $kieli).": $sarjarow[sarjanumero]", $style_komm, "L", "I");
						$h-=$pdf->ln();
					}
				}
				
				//	Vientilaskulle annetaan hieman lisäinfoa!
				if ($laskurow["vienti"] != "" and $row["ei_saldoa"] == "") {
					$h-=$pdf->ln(0.5);
					$pdf->write($h, 1, 5, 40, t("Alkuperämaa", $kieli).":", "snadi", "L", "I");	
					$pdf->write($h, 1, 40, 0, strtoupper(maa($row["alkuperamaa"], $kieli)), "snadi", "L", "I");						
					$h-=$pdf->ln();
					$pdf->write($h, 1, 5, 40, t("Tullinimike", $kieli).":", "snadi", "L", "I");	
					$pdf->write($h, 1, 40, 0, $row["tullinimike1"], "snadi", "L", "I");						
					$h-=$pdf->ln();
					if($row["tullikohtelu"] != "") {
						$pdf->write($h, 1, 5, 40, t("Vientikohtelu", $kieli).":", "snadi", "L", "I");	
						$pdf->write($h, 1, 40, 0, $row["tullikohtelu"], "snadi", "L", "I");						
						$h-=$pdf->ln();						
					}
				}

				if($row["asiakkaan_positio"] > 0 and $row["ei_saldoa"] == "") {
					$pdf->write($h, 1, 5+$offset, 0, t("RS-koodi", $kieli).": $laskurow[asiakkaan_kohde]-$row[asiakkaan_positio]", "snadi", "L", "I");
					$h-=$pdf->ln();
				}
				
				if($rivi=="isa") {
					$summa+=$row["rivihinta"];
					$summa_verolla+=($row["rivihinta"]*(1+($row["alv"]/100)));						
				}

				$h-=$pdf->ln(1.5);
			}
		}
		$h-=$pdf->ln(3);			
				
		// Alv erittely!
		$s=$pdf->teeSarakkeet(array(140,15,35));
		if($toim == "PROFORMA") {
			$pdf->write($h, 1, 0, 90, t("ARVO VAIN TULLIA VARTEN", $kieli), "norm", "R", "B");
		}
		$pdf->write($h, 1, 0, $s[0], t("Veroton arvo yhteensä", $kieli).":", "norm", "R", "B");
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

		//Haetaan kaikki alvikannat riveiltä
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
			
			if($arow["alvrivihinta"] <> 0) {
				$pdf->write($h, 1, 0, $s[0], t("Arvonlisävero", $kieli)." (".money_format("%i", $arow[rivihinta]).")", "norm", "R", "");

				if ($alvrow["alv"] >= 500) {
					$pdf->write($h, 1, $s[0], $s[1], "M.V.", "norm", "R", "");							
				}
				else {
					$pdf->write($h, 1, $s[0], $s[1], $alvrow["alv"]."%", "norm", "R", "");
				}

				$pdf->write($h, 1, $s[1], 0, money_format('%i', $arow["alvrivihinta"]), "norm", "R", "");	

				$h-=$pdf->ln();				
			}
		}

		$pdf->write($h, 1, 0, $s[0], t("Lasku yhteensä", $kieli).":", "norm", "R", "B");
		if ($toim == 'PROFORMA' or ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"])))) {
			$pdf->write($h, 1, $s[1], 0, money_format('%i',$summa_verolla), "norm", "R", "B");
			$laskusumma=$summa_verolla;
		}
		else {
			$pdf->write($h, 1, $s[1], 0, money_format('%i',$laskurow["summa"]-$laskurow["pyoristys"]), "norm", "R", "B");
			$laskusumma=$laskurow["summa"]-$laskurow["pyoristys"];			
		}
		$h-=$pdf->ln(4);
		
		
		// dynamiikkaa
		if($h < 50) {
			$pdf = alku($pdf, $laskurow, $toim, $otsikko, $kieli);
		}

		if($laskurow["sisviesti1"] != "") {
			//	Maksusopimus
			$pdf->write($h, 1, 0, 0, t("LISÄTIEDOT", $kieli), "norm", "L", "B");
			$h-=$pdf->ln(1.25);
			$nb=$pdf->countParagraphHeight($laskurow["sisviesti1"], "norm");
			$pdf->write($h, $nb, 0, 0, $laskurow["sisviesti1"], "norm", "L", "I");
			$h-=$pdf->ln($nb);					
		}
			
		if($h < 70) {
			$pdf = alku($pdf, $laskurow, $toim, $otsikko, $kieli);
		}

		$h = 70;
		if($toim != "PROFORMA") {
			$s=$pdf->teeSarakkeet(array(31,30,45,25,35));		
			$pdf->write($h, 1, 0, $s[1], t("MAKSUTIEDOT", $kieli), "norm", "L", "B");
			$h-=$pdf->ln(1.25);

			$query = "	SELECT *
						FROM maksuehto
						left join pankkiyhteystiedot on (pankkiyhteystiedot.yhtio=maksuehto.yhtio and pankkiyhteystiedot.tunnus=maksuehto.pankkiyhteystiedot)
						WHERE maksuehto.yhtio = '$kukarow[yhtio]' and maksuehto.tunnus = $laskurow[maksuehto]";
			$result = mysql_query($query) or pupe_error($query);
			$row = mysql_fetch_array($result);

			$masrow = $row;
			$maksuehto = $masrow["teksti"]." ".$masrow["kassa_teksti"];

			$pdf->write($h, 1, 0, 		$s[0], t("Laskun pvm", $kieli).":", "norm", "L", "");
			$pdf->write($h, 1, $s[0], 	$s[1], strftime("%x",strtotime($laskurow["tapvm"])), "norm", "L", "");
			$h-=$pdf->ln();
			$pdf->write($h, 1, 0, 		$s[0], t("Maksuehto", $kieli).":", "norm", "L", "");
			$pdf->write($h, 1, $s[0], 	$s[1], t($maksuehto, $kieli), "norm", "L", "");
			$h-=$pdf->ln();
			$pdf->write($h, 1, 0, 		$s[0], t("Eräpäivä", $kieli).":", "norm", "L", "");

			if ($row['kateinen'] != '') {
				$erapaiva = t('MAKSETTU', $kieli);
			} else {
				$erapaiva = strftime("%x",strtotime($laskurow["erpcm"]));
			}

			if ($laskurow["yhtio_ovttunnus"] != "") {

				// haetaan toimipaikan tiedot
				$alhqur = "select * from yhtion_toimipaikat where yhtio='$kukarow[yhtio]' and vat_numero='$laskurow[yhtio_ovttunnus]'";
				$alhire = mysql_query($alhqur) or pupe_error($alhqur);

				if (mysql_num_rows($alhire) == 1) {

					// haetaan toimipaikan tiedot
					$alhiro = mysql_fetch_array($alhire);

					// fax ja puhelin ei ole laskulla
					$yhtiorow['puhelin']	= $alhiro['puhelin'];
					$yhtiorow['fax']		= $alhiro['fax'];
				}

				// loput tiedot otetaan laskulta
				$yhtiorow["nimi"] 		= $laskurow["yhtio_nimi"];
				$yhtiorow["osoite"] 	= $laskurow["yhtio_osoite"];
				$yhtiorow["postino"] 	= $laskurow["yhtio_postino"];
				$yhtiorow["postitp"] 	= $laskurow["yhtio_postitp"];
				$yhtiorow["maa"] 		= $laskurow["yhtio_maa"];
				$yhtiorow["kotipaikka"]	= $laskurow["yhtio_kotipaikka"];
				$vatnumero				= $laskurow["yhtio_ovttunnus"];
			}

			//Laitetaan pankkiyhteystiedot kuntoon
			if ($masrow["factoring"] != "") {
				$query = "	SELECT *
							FROM factoring
							WHERE yhtio 		= '{$kukarow['yhtio']}'
							and factoringyhtio 	= '{$masrow['factoring']}'
							and valkoodi 		= '{$laskurow['valkoodi']}'";
				$fres = mysql_query($query) or pupe_error($query);
				$frow = mysql_fetch_array($fres);

				$pankkitiedot["pankkinimi1"]  =	$frow["pankkinimi1"];
				$pankkitiedot["pankkitili1"]  =	$frow["pankkitili1"];
				$pankkitiedot["pankkiiban1"]  =	$frow["pankkiiban1"];
				$pankkitiedot["pankkiswift1"] =	$frow["pankkiswift1"];
				$pankkitiedot["pankkinimi2"]  =	$frow["pankkinimi2"];
				$pankkitiedot["pankkitili2"]  =	$frow["pankkitili2"];
				$pankkitiedot["pankkiiban2"]  =	$frow["pankkiiban2"];
				$pankkitiedot["pankkiswift2"] = $frow["pankkiswift2"];
				$pankkitiedot["pankkinimi3"]  =	"";
				$pankkitiedot["pankkitili3"]  =	"";
				$pankkitiedot["pankkiiban3"]  =	"";
				$pankkitiedot["pankkiswift3"] =	"";

			}
			elseif ($masrow["pankkinimi1"] != "") {
				$pankkitiedot["pankkinimi1"]  =	$masrow["pankkinimi1"];
				$pankkitiedot["pankkitili1"]  =	$masrow["pankkitili1"];
				$pankkitiedot["pankkiiban1"]  =	$masrow["pankkiiban1"];
				$pankkitiedot["pankkiswift1"] =	$masrow["pankkiswift1"];
				$pankkitiedot["pankkinimi2"]  =	$masrow["pankkinimi2"];
				$pankkitiedot["pankkitili2"]  =	$masrow["pankkitili2"];
				$pankkitiedot["pankkiiban2"]  =	$masrow["pankkiiban2"];
				$pankkitiedot["pankkiswift2"] = $masrow["pankkiswift2"];
				$pankkitiedot["pankkinimi3"]  =	$masrow["pankkinimi3"];
				$pankkitiedot["pankkitili3"]  =	$masrow["pankkitili3"];
				$pankkitiedot["pankkiiban3"]  =	$masrow["pankkiiban3"];
				$pankkitiedot["pankkiswift3"] =	$masrow["pankkiswift3"];
			}
			else {
				$pankkitiedot["pankkinimi1"]  =	$yhtiorow["pankkinimi1"];
				$pankkitiedot["pankkitili1"]  =	$yhtiorow["pankkitili1"];
				$pankkitiedot["pankkiiban1"]  =	$yhtiorow["pankkiiban1"];
				$pankkitiedot["pankkiswift1"] =	$yhtiorow["pankkiswift1"];
				$pankkitiedot["pankkinimi2"]  =	$yhtiorow["pankkinimi2"];
				$pankkitiedot["pankkitili2"]  =	$yhtiorow["pankkitili2"];
				$pankkitiedot["pankkiiban2"]  =	$yhtiorow["pankkiiban2"];
				$pankkitiedot["pankkiswift2"] = $yhtiorow["pankkiswift2"];
				$pankkitiedot["pankkinimi3"]  =	$yhtiorow["pankkinimi3"];
				$pankkitiedot["pankkitili3"]  =	$yhtiorow["pankkitili3"];
				$pankkitiedot["pankkiiban3"]  =	$yhtiorow["pankkiiban3"];
				$pankkitiedot["pankkiswift3"] =	$yhtiorow["pankkiswift3"];
			}

			$pdf->write($h, 1, $s[0], 	$s[1], $erapaiva, "norm", "L", "");
			$h-=$pdf->ln();
			$pdf->write($h, 1, 0, 		$s[0], t("Tilinumero", $kieli).":", "norm", "L", "");
			$pdf->write($h, 1, $s[0], 	$s[1], 		$pankkitiedot["pankkitili1"], "norm", "L", "");
			$h-=$pdf->ln();
			$pdf->write($h, 1, 0, 		$s[0], t("Viitenumero", $kieli).":", "norm", "L", "");
			$pdf->write($h, 1, $s[0], 	$s[1], 		$laskurow["viite"], "norm", "L", "");
			$h-=$pdf->ln();
			$pdf->write($h, 1, 0, 		$s[0], t("Summa", $kieli).":", "norm", "L", "");
			$pdf->write($h, 1, $s[0], 	$s[1], 		money_format('%i',$laskusumma), "norm", "L", "");
			$h-=$pdf->ln();		
			//Duusataan pankkitiedot ovelsati reversenä!
			for($i=1;$i<3;$i++) {
				if($yhtiorow["pankkitili$i"] != "") {		
					$h+=$pdf->ln();		
					$pdf->write($h, 1, $s[1], 	$s[2], 	$pankkitiedot["pankkinimi$i"], "snadi", "L", "");
					$pdf->write($h, 1, $s[2], 	$s[3], 	$pankkitiedot["pankkitili$i"], "snadi", "L", "");
					$pdf->write($h, 1, $s[3], 	$s[4], 	$pankkitiedot["pankkiiban$i"], "snadi", "L", "");
					$pdf->write($h, 1, $s[4], 	0, 		$pankkitiedot["pankkiswift$i"], "snadi", "L", "");								
				} 
			}
			$h+=$pdf->ln();		
			$pdf->write($h, 1, $s[1], $s[2], t("Pankki", $kieli), "norm", "L", "");		
			$pdf->write($h, 1, $s[2], $s[3], t("Tilinumero", $kieli), "norm", "L", "");				
			$pdf->write($h, 1, $s[3], $s[4], t("IBAN", $kieli), "norm", "L", "");
			$pdf->write($h, 1, $s[4], 0, t("SWIFT", $kieli), "norm", "L", "");					
		}
		elseif($toim == "PROFORMA" and $laskurow["alatila"] != "X") {
			
			//	Olisiko jo pakkaustietoja
			$query = "	SELECT sum(kollit) kollit, round(sum(kilot), 2) kilot, round(sum(kuutiot), 2) kuutiot, round(sum(lavametri), 2) lavametrit, round(sum(nettopaino), 2) nettopaino, concat_ws(' ',pakkaus, pakkauskuvaus, lisatiedot) tiedot, toimitustapa
						FROM rahtikirjat
						WHERE yhtio = '{$kukarow["yhtio"]}' and otsikkonro = '{$laskurow["tunnus"]}'
						GROUP BY pakkaus, pakkauskuvaus, lisatiedot";
			$rahtires = mysql_query($query) or pupe_error($query);

			if(mysql_num_rows($rahtires)>0) {

				if(!in_array($laskurow["alatila"], array("B", "D","J","E","V","X"))) {
					$otsik = "PAKKAUSSUUNNITELMA";
				}
				else {
					$otsik = "PAKKAUSTIEDOT";
				}

				$pdf->write($h, 1, 0, 0, 		t($otsik, $kieli), "norm", "L", "B");

				$h-=$pdf->ln(1.5);
				while ($rahtirow = mysql_fetch_array($rahtires)) {
					$pdf->write($h, 1, 0, $s[0], 		$rahtirow["kollit"]." ".t("kpl", $kieli)." ".$rahtirow["tiedot"], "norm", "L", "");
					$h-=$pdf->ln(1.25);

					if($rahtirow["kilot"]>0) {
						$pdf->write($h, 1, 5, 40, 		t("Bruttopaino", $kieli).":", "norm", "L", "");
						$pdf->write($h, 1, 40, 0, 	$rahtirow["kilot"]." ".t("kg", $kieli), "norm", "L", "");					
						$h-=$pdf->ln(1);					
					}
					if($rahtirow["nettopaino"]>0) {
						$pdf->write($h, 1, 5, 40, 		t("Nettopaino", $kieli).":", "norm", "L", "");
						$pdf->write($h, 1, 40, 0, 	$rahtirow["nettopaino"]." ".t("kg", $kieli), "norm", "L", "");					
						$h-=$pdf->ln(1);					
					}
					if($rahtirow["kuutiot"]>0) {
						$pdf->write($h, 1, 5, 40, 		t("Kuutiot", $kieli).":", "norm", "L", "");
						$pdf->write($h, 1, 40, 0, 	$rahtirow["kuutiot"]." ".t("m³", $kieli), "norm", "L", "");					
						$h-=$pdf->ln(1);					
					}
					if($rahtirow["lavametrit"]>0) {
						$pdf->write($h, 1, 5, 40, 		t("Lavametrit", $kieli).":", "norm", "L", "");
						$pdf->write($h, 1, 40, 0, 	$rahtirow["lavametrit"]." ".t("m", $kieli), "norm", "L", "");
						$h-=$pdf->ln(1);					
					}

					$h-=$pdf->ln(1);				
				}				
			}
		}
		
		//	Allekirjoituspohjat
		if($h < 35) {
			$pdf = alku($pdf, $laskurow,$kieli);
			$h = 35;
		}
		else {
			$h = 35;
		}
		
		$pdf->write($h, 1, 0, 0, t("Parhain terveisin",$kieli), "norm", "L", "");
		$h-=$pdf->ln(1.25);

		$pdf->write($h, 1, 0, 0, $kukarow["nimi"], "norm", "L", "B");
		$h-=$pdf->ln();

		$pdf->write($h, 1, 0, 20, t("mobile", $kieli), "norm", "L", "");
		$pdf->write($h, 1, 20, 0, $kukarow["puhno"], "norm", "L", "");
		$h-=$pdf->ln();

		$pdf->write($h, 1, 0, 20, t("email", $kieli), "norm", "L", "");
		$pdf->write($h, 1, 20, 0, $kukarow["eposti"], "norm", "L", "");
		
		$h = 15;		
		$s=$pdf->teeSarakkeet(array(70,15,58,15));
		$pdf->write($h, 1, 0, $s[0], 		$yhtiorow["nimi"], "norm", "L", "B");
		$pdf->write($h, 1, $s[0], $s[1], 	t("Puh", $kieli).":", "norm", "L", "");
		$pdf->write($h, 1, $s[1], $s[2], 	$yhtiorow["puhelin"], "norm", "L", "");		
		$h-=$pdf->ln();

		$pdf->write($h, 1, 0, $s[1], 		$yhtiorow["osoite"], "norm", "L", "");
		$pdf->write($h, 1, $s[0], $s[1], 	t("Fax", $kieli).":", "norm", "L", "");
		$pdf->write($h, 1, $s[1], $s[2], 	$yhtiorow["fax"], "norm", "L", "");	
		
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
        } else {
		    $uytunnus = $yhtiorow["maa"]."-".$uytunnus;
        }
        
        //$yhtiorow["maa"].sprintf("%08.8s",ereg_replace("[^0-9]","",$yhtiorow["ytunnus"]))
		$pdf->write($h, 1, $s[2], $s[3], 	t("VAT", $kieli).":", "norm", "L", "");					
		$pdf->write($h, 1, $s[3], 0, $uytunnus, "norm", "R", "");		
		$h-=$pdf->ln();

		$pdf->write($h, 1, 0, $s[1], 		$yhtiorow["maa"].$yhtiorow["postino"]." ".$yhtiorow["postitp"], "norm", "L", "");
		$pdf->write($h, 1, $s[0], $s[1], 	t("Web", $kieli).":", "norm", "L", "");
		$pdf->write($h, 1, $s[1], $s[2], 	$yhtiorow["www"], "norm", "L", "");		
		$h-=$pdf->ln();

		$pdf->write($h, 1, 0, $s[0], 		strtoupper(maa($yhtiorow["maa"], $kieli)), "norm", "L", "");
		$pdf->write($h, 1, $s[0], $s[1], 	t("Email", $kieli).":", "norm", "L", "");
		$pdf->write($h, 1, $s[1], $s[2], 	$yhtiorow["email"], "norm", "L", "");
		
		// Tulostetaan sivu
		//keksitään uudelle failille joku varmasti uniikki nimi:
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
		
		if ($komento == 'email' or substr($komento,0,12) == 'asiakasemail') {
			$liite = $pdf_laskufilenimi;
			$kutsu = ucfirst(strtolower($otsikko))." $laskurow[laskunro]";
			
			echo t(ucfirst(strtolower($otsikko))." lähetetään osoitteeseen $kukarow[eposti]")."...<br>";

			if ($kukarow["extranet"] == '') {
				require("../inc/sahkoposti.inc");
			}
			else {
				require("sahkoposti.inc");
			}
		}
		elseif ($tee == 'NAYTATILAUS') {
			//Työnnetään tuo pdf vaan putkeen!
			echo file_get_contents($pdf_laskufilenimi);
		}
		elseif ($komento != '' and $komento != 'edi') {
			
			if ($yhtiorow["lasku_tulostin"] == "AUTOMAAGINEN_VALINTA") {
				if ($varasto != 0) {
					$query = "select * from varastopaikat where yhtio='$kukarow[yhtio]' and tunnus='$varasto' order by alkuhyllyalue,alkuhyllynro";
				}
				else {
					$query = "select * from varastopaikat where yhtio='$kukarow[yhtio]' order by alkuhyllyalue,alkuhyllynro limit 1";
				}
				$prires= mysql_query($query) or pupe_error($query);
				$prirow= mysql_fetch_array($prires);
				$komento = $prirow["printteri5"];

				$tulos_ulos .= t("Lasku tulostuu varastoon").": $prirow[nimitys]<br>\n";
			}
			
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
				echo "<font class='error'>".t("Laskun tulostus epäonnistui")."!!!</font><br>\n";	
			}
		}			

		//poistetaan tmp file samantien kuleksimasta...
		system("rm -f $pdf_laskufilenimi");				
	}
}

?>
