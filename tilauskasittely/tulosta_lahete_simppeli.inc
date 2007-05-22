<?php

require_once('../pdflib/pupepdf.class.php');

if (!function_exists('tulosta_lahete')) {
	function tulosta_lahete ($otunnus, $komento, $kieli = "", $tee, $tulostusversio) {
		global $yhtiorow, $kukarow, $fonts, $_SERVER, $lahete_param;
		
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
		
		//	Suorastaan h‰mment‰v‰‰ kikkailua..
		$lahete_param=array();
		switch ($tulostusversio) {
			case "tulosta_lahete_simppeli_ei_hintoja_ei_aleja.inc":
				$lahete_param["ale"]= "ei";
				$lahete_param["hinnat"]= "ei";				
				break;
			case "tulosta_lahete_simppeli_ei_aleja.inc":
				$lahete_param["ale"]= "ei";
				$lahete_param["hinnat"]= "on";				
				break;				
			default:
				$lahete_param["ale"]= "on";
				$lahete_param["hinnat"]= "on";				
				break;
		}

		// Haetaan laskun tiedot
		$query = "	SELECT lasku.*, lasku.tunnus tunnus, valkoodi valuutta, lasku.nimi nimi, lasku.tapvm p‰iv‰ys, laskun_lisatiedot.asiakkaan_kohde, lasku.tunnusnippu projekti, if(lasku.tunnusnippu=0, lasku.tunnus,tunnusnippu) tilaus, lasku.tunnus toimitus, viesti asiakkaan_viite, comments lis‰tiedot, lasku.ytunnus asiakkaan_VAT, toimaika toimitusaika, kerayspvm ker‰ysaika,
					(select if(count(distinct(keratty))>1,'".t("useita")."', keratty) from tilausrivi,tuote where tilausrivi.yhtio=lasku.yhtio and tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.ei_saldoa='' and $mika=lasku.tunnus) k‰sittelij‰,
					(select if(count(distinct(left(kerattyaika,10)))>1,'".t("useita")."', max(kerattyaika)) from tilausrivi,tuote where tilausrivi.yhtio=lasku.yhtio and tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.ei_saldoa='' and $mika=lasku.tunnus) k‰sitelty,
					concat_ws(' ', postino, postitp) postiosoite,
					concat_ws(' ', toim_postino, toim_postitp) toim_postiosoite,
					yhteyshenkilo_tekninen.nimi asiakkaan_yhteyshenkilˆ,
					SUBSTRING(lasku_maa.nimi FROM 6) maa,
					SUBSTRING(toim_maa.nimi FROM 6) toim_maa
					FROM lasku
					LEFT JOIN laskun_lisatiedot ON lasku.yhtio=laskun_lisatiedot.yhtio and lasku.tunnus=laskun_lisatiedot.otunnus
					LEFT JOIN yhteyshenkilo yhteyshenkilo_tekninen ON laskun_lisatiedot.yhtio=yhteyshenkilo_tekninen.yhtio and laskun_lisatiedot.yhteyshenkilo_tekninen=yhteyshenkilo_tekninen.tunnus					
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
		
		if($laskurow["rivihintoja_ei_nayteta"]) {
			$lahete_param["ale"]= "ei";
			$lahete_param["hinnat"]= "ei";				
		}
		
		if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"])) and $laskurow["vienti_kurssi"] != 0) {
			$hinta_riv = "round(tilausrivi.hinta/$laskurow[vienti_kurssi], 2)";
		}
		else {
			$hinta_riv = "tilausrivi.hinta";
		}
		
		$otsikko = "LƒHETE";
		
		if (!function_exists('alku')) {
			function alku ($pdf, $laskurow, $toim, $otsikko, $kieli, $lahete_param) {
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
							$kentat = array("toimitus", "projekti", "toimitusaika", "k‰sittelij‰", "k‰sitelty", "toimitustapa", "toimitusehto", "asiakkaan_VAT");							
						}
						else {
							$kentat = array("tilaus", "toimitusaika", "k‰sittelij‰", "k‰sitelty", "toimitustapa", "toimitusehto", "asiakkaan_VAT");
						}
					}
					else {
						$kentat = array("tilaus", "toimitusaika", "k‰sittelij‰", "k‰sitelty", "toimitustapa", "toimitusehto", "asiakkaan_VAT");
					}

					foreach($kentat as $key) {
							$pdf->writeToTemplate($h, 1, $s[0], $s[1], t(str_replace("_"," ",ucfirst($key)), $kieli).":", "norm", "L", "B", $tid);						
						if($key == "toimitusaika") {
							$pdf->writeToTemplate($h, 1, $s[1]+2, 0, strftime("%x",strtotime($laskurow[$key])), "norm", "L", "", $tid);
						}
						elseif($key == "k‰sitelty") {
							if($laskurow[$key]!='0000-00-00 00:00:00') {
								$pdf->writeToTemplate($h, 1, $s[1]+2, 0, strftime("%x %T",strtotime($laskurow[$key])), "norm", "L", "", $tid);
							}
						}
						else {
							$pdf->writeToTemplate($h, 1, $s[1]+2, 0, $laskurow[$key], "norm", "L", "", $tid);
						}
						$h-=$pdf->ln();													
					}
					$h-=$pdf->ln(0.5);

					//	Sitten yhteyshenkilˆit‰
					foreach(array("asiakkaan_viite", "lis‰tiedot", "toimittajan_yhteyshenkilˆ", "asiakkaan_yhteyshenkilˆ") as $key) {
						if($laskurow[$key] != "" or $key == "asiakkaan_viite") {
							$pdf->writeToTemplate($h, 1, $s[0], 0, t(str_replace("_"," ",ucfirst($key)), $kieli).":", "norm", "L", "B", $tid);
							$h-=$pdf->ln();					
							$pdf->writeToTemplate($h, 1, $s[0], 0, $laskurow[$key], "norm", "L", "", $tid);
							$h-=$pdf->ln(1.5);
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
					$s=$pdf->teeSarakkeet(array(95,15,20,15));
					$pdf->writeToTemplate($h, 1, 0, $s[0], t("Tuote", $kieli), "norm", "L", "B", $tid);
					$pdf->writeToTemplate($h, 1, $s[0], $s[1], t("Toimitettu", $kieli), "norm", "R", "B", $tid);
					if($lahete_param["ale"] == "on" and $lahete_param["hinnat"] == "on") {
						$pdf->writeToTemplate($h, 1, $s[1], $s[2], t("Hinta/yks", $kieli), "norm", "R", "B", $tid);
						$pdf->writeToTemplate($h, 1, $s[2], $s[3], t("Alv", $kieli), "norm", "R", "B", $tid);
						$pdf->writeToTemplate($h, 1, $s[3], 0, t("Veroton arvo", $kieli), "norm", "R", "B", $tid);					
					}
					$h-=$pdf->ln();
					$pdf->writeToTemplate($h, 1, 0, $s[0], t("Tilaus")."/".t("Kommentti", $kieli), "norm", "L", "B", $tid);
					$pdf->writeToTemplate($h, 1, $s[0], $s[1], t("Tilattu", $kieli), "norm", "R", "B", $tid);
					if($lahete_param["ale"] == "on" and $lahete_param["hinnat"] == "on") {
						if($alenaytetaan=="OK") {
							$pdf->writeToTemplate($h, 1, $s[1], $s[2], t("Ale", $kieli), "norm", "R", "B", $tid);				
						}
						$pdf->writeToTemplate($h, 1, $s[3], 0, t("Verollinen arvo", $kieli), "norm", "R", "B", $tid);					
					}

					//	Liitet‰‰n pohjat
					$pdf->placeTemplate($tid);
				}
				else {
					$pdf->addPage("a4");					

					//	Liitet‰‰n pohjat				
					$pdf->placeTemplate($tid);
					
				}

				return $pdf;
			}			
		}
		
		$pdf = alku($pdf, $laskurow, $toim, $otsikko, $kieli, $lahete_param);
		$h = 180;
		
		$sorttauskentta = generoi_sorttauskentta();
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
		
		$rivihinta = "round($hinta_riv * if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+$laskurow[erikoisale]-(tilausrivi.ale*$laskurow[erikoisale]/100))/100)),2)";
		$perherivihinta = "	(select sum(".str_replace("tilausrivi.","t.",$rivihinta).") from tilausrivi t where t.perheid=tilausrivi.perheid and tilausrivi.yhtio = '$kukarow[yhtio]' and tilausrivi.tyyppi!='D')";
		
		// haetaan tilauksen kaikki rivit
		$query = "  SELECT tilausrivi.*, maat.nimi alkuperamaa, tuote.ei_saldoa ei_saldoa, tuote.tullinimike1, tuote.tullikohtelu,
					if(tilausrivi.perheid=0,$rivihinta,$perherivihinta) rivihinta,
					$sorttauskentta
					FROM tilausrivi
					JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno and tuote.ei_saldoa=''
					LEFT JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio=tuote.yhtio and tuotteen_toimittajat.tuoteno = tuote.tuoteno 
					LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
					LEFT JOIN maat on maat.koodi=tuotteen_toimittajat.alkuperamaa
					WHERE $where and (perheid=0 or perheid=tilausrivi.tunnus or tilausrivin_lisatiedot.ei_nayteta='' or tilausrivin_lisatiedot.ei_nayteta IS NULL)
					and tilausrivi.tyyppi!='D'
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
			$summa=$summa_netto=0;
			unset($edperhe);
			
			while ($row = mysql_fetch_array($riresult)) {

				if($h < 20) {
					$pdf = alku($pdf, $laskurow, $toim, $otsikko, $kieli, $lahete_param);
					$h = 190;
				}
				
				if ($laskurow['valkoodi'] != $yhtiorow['valkoodi']) {
					$row["hinta"] 	  = round(laskuval($row["hinta"], $laskurow["vienti_kurssi"]),2);
					$row["rivihinta"] = round(laskuval($row["rivihinta"], $laskurow["vienti_kurssi"]),2);
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
			
				//	Perheiden v‰liin laitetaan yksi extrav‰li..
				if(isset($edperhe) and $edperhe != $row["perheid"]) {
					$h-=$pdf->ln(1);
					unset($edperhe);
				}
			
				if($row["perheid"]==0 or $row["perheid"]==$row["tunnus"]) {
					$pdf->write($h, 1, 0, $s[0], $row["tuoteno"]." ".$row["nimitys"], "norm", "L", "");

					if ($row["keratty"] != '' and !in_array($row["var"], array('J','P'))) {
						$pdf->write($h, 1, $s[0], $s[1], (($row['varattu']+$row['kpl'])*1)." ".strtolower($row["yksikko"]), "norm", "R", "");					
					}
					else {	
						if ($row["jt"] != 0) { // j‰lkitoimitus
							$pdf->write($h, 1, $s[0], $s[1], t("**JT**", $kieli), "norm", "R", "");						
							$pdf->draw_text(380, $kala, t("**JT**", $kieli), 	$firstpage, $p);
						}
						elseif ($row["var"] == 'P') { // puute
							$pdf->write($h, 1, $s[0], $s[1], t("PUUTE", $kieli), "norm", "R", "");						
						}
						elseif ($row["var"] == 'H') { // v‰kisin hyv‰ksytty
							$pdf->write($h, 1, $s[0], $s[1], "...........", "norm", "R", "");
						}
					}


					if($lahete_param["ale"] == "on" and $lahete_param["hinnat"] == "on") {
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

					$pdf->write($h, 1, $s[0], $s[1], ($row['tilkpl']*1)." ".strtolower($row["yksikko"]), "snadi", "R", "I");

					if($lahete_param["ale"] == "on" and $lahete_param["hinnat"] == "on") {
						if($alenaytetaan=="OK") {
							$pdf->write($h, 1, $s[1], $s[2], $row["ale"]."%", "snadi", "R", "I");				
						}
						//	verollinen rivihinta jos ei marginaalimyynti‰
						if ($row["alv"] < 500) {
							$pdf->write($h, 1, $s[3], 0, money_format('%!i',$row["rivihinta"]*(1+($row["alv"]/100))), "snadi", "R", "I");
						}
					}
					if($row["kommentti"] != "") {
						if($pdf->pt_mm($pdf->writeStrlen($row["otunnus"]."/".$row["kommentti"], "snadi")) > $s[0]) {
							$pdf->write($h, 2, 0, $s[0], $row["otunnus"]."/* ".$row["kommentti"], "snadi", "L", "I");	
							$h-=$pdf->ln(2);
						} 
						else {
							$pdf->write($h, 1, 0, $s[0], $row["otunnus"]."/* ".$row["kommentti"], "snadi", "L", "I");	
							$h-=$pdf->ln();
						}
					}
					else {
						$pdf->write($h, 1, 0, $s[0], $row["otunnus"], "snadi", "L", "I");	
						$h-=$pdf->ln();					
					}

					//	Vientilaskulle annetaan hieman lis‰infoa!
					if ($laskurow["vienti"] != "") {
						$pdf->write($h, 1, 5, $s[0], "Country of Origin: ".$row["alkuperamaa"], "snadi", "L", "I");	
						$h-=$pdf->ln();
						$pdf->write($h, 1, 5, $s[0], "Customs tariff number: ".$row["tullinimike1"], "snadi", "L", "I");	
						$h-=$pdf->ln();
						if($row["tullikohtelu"] != "") {
							$pdf->write($h, 1, 0, $s[0], t("Vientikohtelu", $kieli).": ".$row["tullikohtelu"], "snadi", "L", "I");	
							$h-=$pdf->ln();						
						}
					}

					if($row["asiakkaan_positio"] > 0 and $row["ei_saldoa"] == "") {
						$pdf->write($h, 1, 5, 0, t("RS-koodi", $kieli).": $laskurow[asiakkaan_kohde]-$row[asiakkaan_positio]", "snadi", "L", "I");
						$h-=$pdf->ln();
					}

					if($row["netto"] != "") {
						$summa_netto+=$row["rivihinta"];					
					}	
					else {
						$summa+=$row["rivihinta"];
					}

					$h-=$pdf->ln(0.5);	
				}
				else {
					$pdf->write($h, 1, 5, $s[0], $row["tuoteno"]." ".$row["nimitys"], "snadi", "L", "");

					if ($row["keratty"] != '' and !in_array($row["var"], array('J','P'))) {
						$pdf->write($h, 1, $s[0], $s[1], (($row['varattu']+$row['kpl'])*1)." ".strtolower($row["yksikko"]), "snadi", "R", "");					
					}
					else {	
						if ($row["jt"] != 0) { // j‰lkitoimitus
							$pdf->write($h, 1, $s[0], $s[1], t("**JT**", $kieli), "snadi", "R", "");						
							$pdf->draw_text(380, $kala, t("**JT**", $kieli), 	$firstpage, $p);
						}
						elseif ($row["var"] == 'P') { // puute
							$pdf->write($h, 1, $s[0], $s[1], t("PUUTE", $kieli), "snadi", "R", "");						
						}
						elseif ($row["var"] == 'H') { // v‰kisin hyv‰ksytty
							$pdf->write($h, 1, $s[0], $s[1], "...........", "snadi", "R", "");
						}
					}
					$h-=$pdf->ln();
					
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

		//erikoisalennus annetaan vain riveille joilla EI ole NETTOHINTAA
		if($lahete_param["ale"] == "on" and $lahete_param["hinnat"] == "on")  {
			if ($laskurow['erikoisale'] != 0) {
				$erikoisale=(1-round($laskurow['erikoisale']/100,2))*$summa;

				$kaikkiyhteensa = $summa + $summa_netto - $erikoisale;

				$pdf->write($h, 1, 0, $s[2], t("Erikoisale", $kieli).":", "norm", "R", "B");
				$pdf->write($h, 1, $s[2], 0, money_format('%i',$erikoisale), "norm", "R", "B");
				$h-=$pdf->ln(1);
			}
			else {
				$kaikkiyhteensa = $summa + $summa_netto;
			}

			$pdf->write($h, 1, 0, $s[2], t("L‰hetys yhteens‰", $kieli).":", "norm", "R", "B");
			$pdf->write($h, 1, $s[2], 0, money_format('%i',$kaikkiyhteensa), "norm", "R", "B");
		}		
		
				
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
		$pdf_lahetefilenimi = "/tmp/Lahete-".md5(uniqid(mt_rand(), true)).".pdf";

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
		$fh = fopen($pdf_lahetefilenimi, "w");
		if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF create error $pdf_lahetefilenimi");
		fclose($fh);

		if ($komento == 'email' or substr($komento,0,12) == 'asiakasemail') {
			$liite = $pdf_lahetefilenimi;
			$kutsu = ucfirst(strtolower($otsikko))." $laskurow[tunnus]";
			
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
			echo file_get_contents($pdf_lahetefilenimi);
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
				$line = exec("$komento $pdf_lahetefilenimi", $output, $returnvalue);
			}
			if(mysql_num_rows($result) == 0 or $returnvalue!=0) {
				echo "<font class='error'>".t("Laskun tulostus ep‰onnistui")."!!!</font><br>\n";	
			}
		}			

		//poistetaan tmp file samantien kuleksimasta...
		system("rm -f $pdf_lahetefilenimi");				
	}
}

?>
