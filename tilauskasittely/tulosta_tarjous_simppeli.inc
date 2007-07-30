<?php

require_once('pdflib/pupepdf.class.php');

if (!function_exists('tulosta_tarjous')) {
	function tulosta_tarjous ($otunnus, $komento, $kieli = "", $tee = "") {
		global $yhtiorow, $kukarow, $fonts;

		// Haetaan laskun tiedot
		$vquery="select count(*) from lasku l where l.yhtio=lasku.yhtio and l.tunnusnippu=lasku.tunnusnippu and l.tunnus<=lasku.tunnus and l.tila='T'";
		$query = "	SELECT lasku.*, lasku.tunnus tunnus, valkoodi valuutta, lasku.nimi nimi, lasku.luontiaika päiväys, laskun_lisatiedot.asiakkaan_kohde, laskun_lisatiedot.rivihintoja_ei_nayteta, viesti asiakkaan_viite,
					if(tunnusnippu>0,concat(lasku.tunnusnippu,'/',($vquery)),lasku.tunnus) tarjous,
					date_add(lasku.luontiaika, INTERVAL $yhtiorow[tarjouksen_voimaika] DAY) voimassa, 
					concat_ws(' ', postino, postitp) postiosoite,
					yhteyshenkilo_tekninen.nimi asiakkaan_yhteyshenkilö,
					laskun_lisatiedot.saate saate
					FROM lasku
					LEFT JOIN laskun_lisatiedot ON lasku.yhtio=laskun_lisatiedot.yhtio and lasku.tunnus=laskun_lisatiedot.otunnus
					LEFT JOIN yhteyshenkilo yhteyshenkilo_tekninen ON laskun_lisatiedot.yhtio=yhteyshenkilo_tekninen.yhtio and laskun_lisatiedot.yhteyshenkilo_tekninen=yhteyshenkilo_tekninen.tunnus					
					LEFT JOIN yhteyshenkilo yhteyshenkilo_kaupallinen ON laskun_lisatiedot.yhtio=yhteyshenkilo_kaupallinen.yhtio and laskun_lisatiedot.yhteyshenkilo_kaupallinen=yhteyshenkilo_kaupallinen.tunnus
					LEFT JOIN kuka AS laatija ON laatija.yhtio='$kukarow[yhtio]' and lasku.laatija=laatija.kuka
					WHERE lasku.yhtio = '$kukarow[yhtio]'
					and lasku.tunnus  = '$otunnus'
					GROUP BY lasku.tunnus";
		$result = mysql_query($query) or pupe_error($query);
		$laskurow = mysql_fetch_array($result);

		$ennakkolisa = "";
		if($yhtiorow["ennakkomaksu_tuotenumero"] != '') {
			$ennakkolisa = " and tilausrivi.tuoteno != '$yhtiorow[ennakkomaksu_tuotenumero]' ";
		}
		
		if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"])) and $laskurow["vienti_kurssi"] != 0) {
			$hinta_riv = "round(tilausrivi.hinta/$laskurow[vienti_kurssi], 2)";
		}
		else {
			$hinta_riv = "tilausrivi.hinta";
		}
		
		if ($kieli== '') {
			$querykiel = "SELECT kieli FROM asiakas WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$laskurow[liitostunnus]'";
			$kielresult = mysql_query($querykiel) or pupe_error($querykiel);
			$kielnum = mysql_num_rows($kielresult);
			$kielrow = mysql_fetch_array($kielresult);
			$kieli = strtolower($kielrow['kieli']);
		}				
		
		if (!function_exists('alku')) {
			function alku ($pdf, $laskurow, $kieli) {
				global $yhtiorow, $kukarow, $fonts, $tid;

				if(!is_object($pdf)) {
					//	Aloitellaan piirtelyä
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

					//	Tehdään uusi sivu
					$pdf->addPage("a4");

					//	Tehdään template
					$tid=$pdf->template->create();

					$h=280;

					//	Liitetään kuva
					if($yhtiorow["lasku_logo"] != "" and file_exists($yhtiorow["lasku_logo"])) {
						$pdf->placeImageToTemplate($h,0,$yhtiorow["lasku_logo"], $tid);
					}

					//	Kirjoitetaan otsikko
					$pdf->writeToTemplate($h, 1, 0, 0, t("TARJOUS", $kieli)." ".$laskurow["tarjous"], "BIG", "C", "B", $tid);

					//	Laitetaan perustiedot oikealle		
					$h=260;
					$s=$pdf->teeSarakkeet(array(100,35));
					foreach(array("tarjous", "päiväys", "voimassa","toimitusehto", "valuutta") as $key) {
						if($key == "päiväys" or $key == "voimassa") {
							$laskurow[$key] = strftime("%x",strtotime($laskurow[$key]));
						}
						$pdf->writeToTemplate($h, 1, $s[0], $s[1], t(ucfirst($key), $kieli).":", "norm", "L", "B", $tid);
						$pdf->writeToTemplate($h, 1, $s[1]+2, 0, $laskurow[$key], "norm", "L", "", $tid);
						$h-=$pdf->ln();					
					}
					$h-=$pdf->ln(0.5);

					foreach(array("asiakkaan_viite", "asiakkaan_yhteyshenkilö") as $key) {
						if($laskurow[$key] != "") {
							$pdf->writeToTemplate($h, 1, $s[0], 0, t(str_replace("_"," ",ucfirst($key)), $kieli).":", "norm", "L", "B", $tid);
							$h-=$pdf->ln();		
							$nb=$pdf->countParagraphHeight($laskurow[$key], "norm", (-1*($s[0])));										
							$pdf->writeToTemplate($h, $nb, $s[0], 0, $laskurow[$key], "norm", "L", "", $tid);
							$h-=$pdf->ln($nb);					
						}
					}
					
					//	Laitetaan toimitustiedot vasemmalle
					$h=250;
					$pdf->writeToTemplate($h, 1, 0, $s[0], t("Asiakas", $kieli).":", "norm", "L", "B", $tid);
					$h-=$pdf->ln();
					foreach(array("toim_nimi", "toim_nimitark", "toim_osoite", "toim_postiosoite") as $key) {
						if($laskurow[$key]!="") {
							$pdf->writeToTemplate($h, 1, 0, $s[0], $laskurow[$key], "norm", "L", "", $tid);
							$h-=$pdf->ln();					
						}
					}					
					//	Liitetään pohjat				
					$pdf->placeTemplate($tid);
				}
				else {
					$pdf->addPage("a4");					

					//	Liitetään pohjat				
					$pdf->placeTemplate($tid);
				}

				return $pdf;
			}
		}
		
		$pdf=alku($pdf, $laskurow,$kieli);
		$h = 220;		
		$nb=$pdf->countParagraphHeight($laskurow["saate"], "norm", 0);					
		$pdf->write($h, $nb, 0, 0, t($laskurow["saate"], $kieli), "norm", "L", "");
		$h-=$pdf->ln($nb+3);

		$sorttauskentta = generoi_sorttauskentta(8);
		$rivihinta = "round($hinta_riv * if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+$laskurow[erikoisale]-(tilausrivi.ale*$laskurow[erikoisale]/100))/100)),2)";
		$perherivihinta = "	(select sum(".str_replace("tilausrivi.","t.",$rivihinta).") from tilausrivi t where t.perheid=tilausrivi.perheid and tilausrivi.yhtio = '$kukarow[yhtio]' and tilausrivi.tyyppi in  ('T'))";
		
		//	Käsitellään tuoterivit
		$q = "	SELECT tilausrivi.*, 
					if(tilausrivi.perheid=0,$rivihinta,$perherivihinta) rivihinta,
					tilausrivin_lisatiedot.positio, tilausrivin_lisatiedot.asiakkaan_positio, ei_saldoa, $sorttauskentta
					FROM tilausrivi
					JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
					LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus";
					
		$w = "	WHERE tilausrivi.otunnus = '$laskurow[tunnus]' and (perheid=0 or perheid=tilausrivi.tunnus or tilausrivin_lisatiedot.ei_nayteta='' or tilausrivin_lisatiedot.ei_nayteta IS NULL)
					and tilausrivi.yhtio = '$kukarow[yhtio]'
					and tilausrivi.tyyppi in  ('T')
					$ennakkolisa";
		$grand_total=0;
		$s=$pdf->teeSarakkeet(array(55,30,30,30));		
		foreach(array("Tuotteet","Palvelut","Alennukset","Ylläpito", "Optio") as $key) {
			
			$query = "";
			if($key == "Tuotteet") {
				$query = "$q $w and (tuote.ei_saldoa = '' and tilkpl > 0 and ((tilausrivin_lisatiedot.positio != 'Optio' and tilausrivin_lisatiedot.positio != 'Ylläpito') or tilausrivin_lisatiedot.positio IS NULL) or tilausrivin_lisatiedot.positio = 'Tuotteet')";
			}
			elseif($key == "Palvelut"){
				$query = "$q $w and (tuote.ei_saldoa != '' and tilkpl > 0 and ((tilausrivin_lisatiedot.positio != 'Optio' and tilausrivin_lisatiedot.positio != 'Ylläpito') or tilausrivin_lisatiedot.positio IS NULL) or tilausrivin_lisatiedot.positio = 'Palvelut')";
			}
			elseif($key == "Alennukset"){
				$query = "$q $w and tilkpl < 0 and (tilausrivin_lisatiedot.positio != 'Optio' and ((tilausrivin_lisatiedot.positio != 'Optio' and tilausrivin_lisatiedot.positio != 'Ylläpito') or tilausrivin_lisatiedot.positio IS NULL) or tilausrivin_lisatiedot.positio = 'Alennukset')";
			}
			elseif($key == "Ylläpito"){
				$query = "$q $w and tilausrivin_lisatiedot.positio = 'Ylläpito'";
			}
			elseif($key == "Optio"){
				$query = "$q $w and tilausrivin_lisatiedot.positio = 'Optio'";
			}
			
			//	Ylläpitoa ei haluta mukaan grand totaliin
			if($key == "Ylläpito") {
				$pdf->write($h, 1, $s[1], $s[3], t("Tarjous yhteensä alv 0%",$kieli).": ", "norm", "R", "B");
				$pdf->write($h, 1, $s[3], 0, money_format('%i',$grand_total), "norm", "R", "B");
				$h-=$pdf->ln(3);
			}
			
			$query .= " ORDER BY otunnus, sorttauskentta";
			$riresult = mysql_query($query) or pupe_error($query);

			if(mysql_num_rows($riresult)>0) {
				
				if($h < 40) {
					$pdf = alku($pdf, $laskurow,$kieli);
					$h = 210;
				}
				
				unset($edperhe);				
				$summa = 0;
				$pdf->write($h, 1, 0, 0, t(mb_strtoupper($key), $kieli), "norm", "L", "B");
				
				//	Onko tällä selitettä?
				$query = "select selitetark_2 from avainsana where laji='TRIVITYYPPI' and selite='$key' and selitetark_2!=''";
				$ares=mysql_query($query) or pupe_error($query);
				if(mysql_num_rows($ares)==1) {
					$arow=mysql_fetch_array($ares);
					$h-=$pdf->ln(1.25);
					$pdf->write($h, 2, 0, 0, t($arow["selitetark_2"], $kieli), "snadi", "L", "I");
				}
				$h-=$pdf->ln(3);

				$pdf->write($h, 1, 0, $s[1], t("Tuote", $kieli)." / ".t("Kommentti", $kieli), "norm", "L", "B");
				$pdf->write($h, 1, $s[1], $s[2], t("Määrä", $kieli), "norm", "R", "B");
				if($laskurow["rivihintoja_ei_nayteta"] == "" or $key == "Optio") {
					$pdf->write($h, 1, $s[2], $s[3], t("Hinta/yks", $kieli), "norm", "R", "B");
					$pdf->write($h, 1, $s[3], $s[4], t("Yhteensä", $kieli), "norm", "R", "B");					
				}
				$h-=$pdf->ln(1.5);

				while ($row = mysql_fetch_array($riresult)) {
					
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
					
					// A-hinta (voi olla verollinen tai veroton riippuen yhtiöstä)
					if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
						$row["hinta"] = laskuval($row["hinta"], $laskurow["vienti_kurssi"]);
					}
					
					if($h < 20) {
						$pdf = alku($pdf, $laskurow,$kieli);
						$h = 210;
					}
					
					//	Perheiden väliin laitetaan yksi extraväli..
					if(isset($edperhe) and $edperhe != $row["perheid"]) {
						$h-=$pdf->ln(1);
						unset($edperhe);
					}
					
					if($row["perheid"]==0 or $row["perheid"]==$row["tunnus"]) {
						
						//	Jos meillä on isätuote fiksataan yksikköhinta
						if($row["perheid"]==$row["tunnus"]) {
							$row["hinta"] = round(($row["rivihinta"]/$row["tilkpl"]),2);
						}
						
						$pdf->write($h, 1, 0, $s[1], $row["tuoteno"]." ".$row["nimitys"], "norm", "L", "");
						$pdf->write($h, 1, $s[1], $s[2], $row["tilkpl"]." ".strtolower(ta($kieli, "Y", $row["yksikko"])), "norm", "R", "");
						if($laskurow["rivihintoja_ei_nayteta"] == "" or $key == "Optio") {
							$pdf->write($h, 1, $s[2], $s[3], money_format('%!i',$row["hinta"]), "norm", "R", "");
							$pdf->write($h, 1, $s[3], $s[4], money_format('%!i',$row["rivihinta"]), "norm", "R", "");						
						}
						$h-=$pdf->ln(1.25);
						if($row["kommentti"] != "") {
							$nb=$pdf->countParagraphHeight($row["kommentti"], "snadi", ($s[0]-5));
							$pdf->write($h, $nb, 5, $s[1], $row["kommentti"], "snadi", "L", "I");	
							$h-=$pdf->ln($nb);					
						}

						if($row["asiakkaan_positio"] > 0 and $row["ei_saldoa"] == "") {
							$pdf->write($h, 1, 0, 0, t("RS-koodi", $kieli).": $laskurow[asiakkaan_kohde]-$row[asiakkaan_positio]", "snadi", "L", "I");
							$h-=$pdf->ln();
						}

						$summa += $row["rivihinta"];
						$h-=$pdf->ln(0.5);						
					}
					//	Meillä on vaan näytettävä tuoteperhetuote					
					else {
						$pdf->write($h, 1, 5, $s[1], $row["tuoteno"]." ".$row["nimitys"], "snadi", "L", "");
						$pdf->write($h, 1, $s[1], $s[2], $row["tilkpl"]." ".strtolower(ta($kieli, "Y", $row["yksikko"])), "snadi", "R", "");
						$h-=$pdf->ln();
						
						if($row["kommentti"] != "") {
							if($pdf->pt_mm($pdf->writeStrlen($row["kommentti"], "snadi")) > $s[1]) {
								$pdf->write($h, 2, 5, $s[1], "* ".$row["kommentti"], "snadi", "L", "I");	
								$h-=$pdf->ln(2);
							} 
							else {
								$pdf->write($h, 1, 5, $s[1], "* ".$row["kommentti"], "snadi", "L", "I");	
								$h-=$pdf->ln();
							}
						}
						
						$edperhe=$row["perheid"];
					}
				}
				
				//	Perheiden väliin laitetaan yksi extraväli..
				if(isset($edperhe) and $edperhe != $row["perheid"]) {
					$h-=$pdf->ln(1);
					unset($edperhe);
				}

				if($laskurow["rivihintoja_ei_nayteta"] == "" and $key != "Optio") {
					$pdf->write($h, 1, $s[1], $s[3], t(ucfirst(strtolower($key))." yhteensä",$kieli).": ", "norm", "R", "B");				
					$pdf->write($h, 1, $s[3], 0, money_format('%!i',$summa), "norm", "R", "B");
				}
				$grand_total+=$summa;
				$h-=$pdf->ln(3);
				
			}			
		}
		
		$h-=$pdf->ln(2);
		
		if($h < 40) {
			$pdf = alku($pdf, $laskurow,$kieli);
			$h = 210;
		}

		if($laskurow["sisviesti1"] != "") {
			//	Maksusopimus
			$pdf->write($h, 1, 0, 0, t("LISÄTIEDOT", $kieli), "norm", "L", "B");
			$h-=$pdf->ln(2.25);
			$nb=$pdf->countParagraphHeight($laskurow["sisviesti1"], "norm")+2;
			$pdf->write($h, $nb, 0, 0, $laskurow["sisviesti1"], "norm", "L", "I");
			$h-=$pdf->ln($nb+0.5);
		}

		if($h < 60) {
			$pdf = alku($pdf, $laskurow,$kieli);
			$h = 210;
		}
		
		//	Maksusopimus		
		$query = "	SELECT maksupositio.*, round(osuus,0) osuus, maksuehto.teksti
					FROM maksupositio
					LEFT JOIN maksuehto on maksuehto.yhtio=maksupositio.yhtio and maksuehto.tunnus=maksupositio.maksuehto
					WHERE maksupositio.yhtio = '$kukarow[yhtio]' and otunnus = $laskurow[jaksotettu] order by tunnus";
		$result = mysql_query($query) or pupe_error($query);
		if(mysql_num_rows($result)>0) {
			
			//	Koitetaan arpoa mahtuuko tämä samalle sivulle, vai ei..
			if($h < (mysql_num_rows($result)*$pdf->ln(2.75) + 10)) {
				$pdf = alku($pdf, $laskurow,$kieli);
				$h = 190;
			}

			//	Maksusopimus
			$pdf->write($h, 1, 0, 0, t("MAKSUEHTO", $kieli), "norm", "L", "B");
			$h-=$pdf->ln(2.25);
			
			$i=0;
			while($row = mysql_fetch_array($result)) {
				$i++;
				$pdf->write($h, 1, 0, 5, "$i.", "norm", "L", "");
				$pdf->write($h, 1, 5, 15, "$row[osuus]%", "norm", "L", "");
				$pdf->write($h, 1, 15, 0, t($row["teksti"], $kieli).", ".t($row["kuvaus"], $kieli)."", "norm", "L");
				
				if($row["lisatiedot"] != "") {
					$h-=$pdf->ln(1.25);
					$pdf->write($h, 1, 15, 0, $row["lisatiedot"], "norm", "L", "I");
				}
				$h-=$pdf->ln(1.5);
			}
		}
		else {
			//	Katosotaan, että tämä mahtuu samalle sivulle
			if($h < 50) {
				$pdf = alku($pdf, $laskurow,$kieli);
				$h = 190;
			}
			
			//	Maksusopimus
			$pdf->write($h, 1, 0, 0, t("MAKSUEHTO", $kieli), "norm", "L", "B");
			$h-=$pdf->ln(2.25);
			
			$query = "	SELECT teksti
						FROM maksuehto
						WHERE yhtio = '$kukarow[yhtio]' and tunnus = $laskurow[maksuehto]";
			$result = mysql_query($query) or pupe_error($query);
			$row = mysql_fetch_array($result);
			$pdf->write($h, 1, 0, 5, "1.", "norm", "L", "");
			$pdf->write($h, 1, 5, 0, "100% ".t($row["teksti"], $kieli)." ".t("toimituksesta", $kieli), "norm", "L", "");
			$h-=$pdf->ln(1.25);			
		}
		

		
		
		//	Allekirjoituspohjat
		if($h < 41) {
			$pdf = alku($pdf, $laskurow,$kieli);
			$h = 41;
		}
		else {
			$h = 41;
		}
		
		$pdf->write($h, 1, 0, 0, t("Parhain terveisin",$kieli), "norm", "L", "");
		$h-=$pdf->ln(3);

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

		$pdf->write($h, 1, 0, $s[0], 		strtoupper(maa($yhtiorow["maa"],$kieli)), "norm", "L", "");
		$pdf->write($h, 1, $s[0], $s[1], 	t("Email", $kieli).":", "norm", "L", "");
		$pdf->write($h, 1, $s[1], $s[2], 	$yhtiorow["email"], "norm", "L", "");
				
		// Tulostetaan sivu
		//keksitään uudelle failille joku varmasti uniikki nimi:
		list($usec, $sec) = explode(' ', microtime());
		mt_srand((float) $sec + ((float) $usec * 100000));
		$pdf_tarjousfilenimi = "/tmp/Tarjous-".md5(uniqid(mt_rand(), true)).".pdf";
		//echo "<pre>".print_r($pdf->dbs,true)."</pre>";
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
		$fh = fopen($pdf_tarjousfilenimi, "w");
		if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF create error $pdf_tarjousfilenimi");
		fclose($fh);
		
		// itse print komento...
		if ($komento == 'email' or substr($komento,0,12) == 'asiakasemail') {
			$liite = $pdf_tarjousfilenimi;
			$kutsu = "Tarjous $laskurow[tarjous]";

			echo t("Tarjous lähetetään osoitteeseen")." $kukarow[eposti]...<br>";

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
			//	Haetaan kirjoittimen nimi..
			$querykieli = "	select *
							from kirjoittimet
							where yhtio='$kukarow[yhtio]' and komento='$komento'";
			$kires = mysql_query($querykieli) or pupe_error($querykieli);
			if(mysql_num_rows($kires) > 0) {
				$kirow = mysql_fetch_array($kires);
				echo t("Tarjous tulostuu kirjoittimelle").": $kirow[kirjoitin]<br>\n";						
				
				echo t("Tarjous tulostuu")."...<br>";
				unset($returnvalue);
				$line = exec("$komento $pdf_tarjousfilenimi", $output, $returnvalue);
			}
			if(mysql_num_rows($result) == 0 or $returnvalue!=0) {
				echo "<font class='error'>".t("Tarjouksen tulostus epäonnistui")."!!!</font><br>\n";	
			}
		}

		//poistetaan tmp file samantien kuleksimasta...
		system("rm -f $pdf_tarjousfilenimi");		
	}
}

?>
