<?php

require_once('../pdflib/pupepdf.class.php');

if (!function_exists('tulosta_tilausvahvistus')) {
	function tulosta_tilausvahvistus ($otunnus, $komento, $kieli = "", $tee) {
		global $yhtiorow, $kukarow, $fonts;

		// Haetaan laskun tiedot
		$query = "	SELECT lasku.*, lasku.tunnus tunnus, valkoodi valuutta, lasku.nimi nimi, lasku.luontiaika päiväys, laskun_lisatiedot.asiakkaan_kohde, lasku.tunnusnippu projekti, if(lasku.tunnusnippu=0, lasku.tunnus,tunnusnippu) tilaus, lasku.tunnus toimitus, viesti asiakkaan_viite, sisviesti1 lisätiedot, lasku.ytunnus asiakkaan_VAT, laskun_lisatiedot.rivihintoja_ei_nayteta,
					concat_ws(' ', postino, postitp) postiosoite,
					concat_ws(' ', toim_postino, toim_postitp) toim_postiosoite,					
					yhteyshenkilo_kaupallinen.nimi asiakkaan_kaupallinen_käsittelijä,
					yhteyshenkilo_tekninen.nimi asiakkaan_yhteyshenkilö,
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

		//	Aloituskorkeus		
		if (!function_exists('alku')) {
			function alku ($pdf, $laskurow, $kieli, $riviotsikot="") {
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

					$pdf->writeToTemplate($h, 1, 0, 0, t("TILAUSVAHVISTUS", $kieli), "BIG", "C", "B", $tid);

					//	Laitetaan perustiedot oikealle		
					$h=260;
					$s=$pdf->teeSarakkeet(array(105,30));
					if($laskurow["projekti"] > 0) {
						//	Onko nippulla projektia?
						$query = "select tunnus from lasku where yhtio ='$kukarow[yhtio]' and tunnusnippu='$laskurow[projekti]' and tila = 'R'";
						$abures = mysql_query($query) or pupe_error($query);
						if(mysql_num_rows($abures)>0) {
							if($laskurow["projekti"] == $laskurow["tunnus"]) {
								$kentat = array("projekti", "päiväys", "valuutta", "toimitustapa", "toimitusehto", "asiakkaan_VAT");
							}
							else {
								$kentat = array("projekti", "toimitus", "päiväys", "valuutta", "toimitustapa", "toimitusehto", "asiakkaan_VAT");
							}
						}
						else {
							if($laskurow["projekti"] == $laskurow["tunnus"]) {
								$kentat = array("tilaus", "päiväys", "valuutta", "toimitustapa", "toimitusehto", "asiakkaan_VAT");
							}
							else {
								$kentat = array("tilaus", "toimitus", "päiväys", "valuutta", "toimitustapa", "toimitusehto", "asiakkaan_VAT");
							}
						}
					}
					else {
						$kentat = array("tilaus", "päiväys", "valuutta", "toimitustapa", "toimitusehto", "asiakkaan_VAT");
					}

					foreach($kentat as $key) {
						if($key == "päiväys") {
							$laskurow[$key] = strftime("%x",strtotime($laskurow[$key]));
						}
						$pdf->writeToTemplate($h, 1, $s[0], $s[1], t(str_replace("_"," ",ucfirst($key)), $kieli).":", "norm", "L", "B", $tid);
						$pdf->writeToTemplate($h, 1, $s[1]+2, 0, $laskurow[$key], "norm", "L", "", $tid);
						$h-=$pdf->ln();					
					}
					$h-=$pdf->ln(0.5);

					//	Sitten yhteyshenkilöitä
					foreach(array("asiakkaan_viite", "toimittajan_yhteyshenkilö", "asiakkaan_yhteyshenkilö", "asiakkaan_kaupallinen_käsittelijä") as $key) {
						if($laskurow[$key] != "") {
							$pdf->writeToTemplate($h, 1, $s[0], 0, t(str_replace("_"," ",ucfirst($key)), $kieli).":", "norm", "L", "B", $tid);
							$h-=$pdf->ln();		
							$nb=$pdf->countParagraphHeight($laskurow[$key], "norm", (-1*($s[0]-5)));										
							$pdf->writeToTemplate($h, $nb, $s[0], 0, $laskurow[$key], "norm", "L", "", $tid);
							$h-=$pdf->ln($nb);					
						}
					}

					//	Laitetaan toimitustiedot vasemmalle
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
					$h-=$pdf->ln(1.5);

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
					
					$h=190;
					//	Tehdään template
					$tid2=$pdf->template->create();
										
					$s=$pdf->teeSarakkeet(array(105,20,25));
					$pdf->writeToTemplate($h, 1, 0, $s[0], t("Tuote", $kieli), "norm", "L", "B", $tid2);
					$pdf->writeToTemplate($h, 1, $s[0], $s[1], t("Määrä", $kieli), "norm", "R", "B", $tid2);				
					if($laskurow["rivihintoja_ei_nayteta"] == "") {
						$pdf->writeToTemplate($h, 1, $s[1], $s[2], t("Hinta/yks", $kieli), "norm", "R", "B", $tid2);
						$pdf->writeToTemplate($h, 1, $s[2], 0, t("Yhteensä", $kieli), "norm", "R", "B", $tid2);					
					}
					$h-=$pdf->ln();
					$pdf->writeToTemplate($h, 1, 0, $s[0], t("Kommentti", $kieli), "norm", "L", "B", $tid2);
					$pdf->writeToTemplate($h, 1, $s[0], $s[1], t("Toimituspvm", $kieli), "norm", "R", "B", $tid2);			
					if($alenaytetaan=="OK") {
						$pdf->writeToTemplate($h, 1, $s[1], $s[2], t("Ale", $kieli), "norm", "R", "B", $tid2);
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

				return $pdf;
			}			
		}
	
		$pdf=alku($pdf, $laskurow,$kieli, "OK");
		$h = 180;
		
		if($laskurow["tila"] == "R") {
			$query = "	SELECT GROUP_CONCAT(tunnus) tunnukset
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]' and tunnusnippu = '$laskurow[tunnus]' and tila IN ('L','G','E','V','W','N','R','A')";
			$result = mysql_query($query) or pupe_error($query);
			$tunnusrow=mysql_fetch_array($result);
			$tunnukset=$tunnusrow["tunnukset"];
		}
		else {
			$tunnukset=$laskurow["tunnus"];
		}
		
		//	Voidaanko näyttää alet?
		$query = "select tunnus from tilausrivi where yhtio = '$kukarow[yhtio]' and ale > 0 and otunnus IN ($tunnukset)";
		$abures = mysql_query($query) or pupe_error($query);
		if(mysql_num_rows($abures) > 0) {
			$alenaytetaan="OK";
		}
		else {
			$alenaytetaan="";
		}

		$sorttauskentta = generoi_sorttauskentta(4);
		$rivihinta = "round($hinta_riv * if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+$laskurow[erikoisale]-(tilausrivi.ale*$laskurow[erikoisale]/100))/100)),2)";
		$perherivihinta = "	(select sum(".str_replace("tilausrivi.","t.",$rivihinta).") from tilausrivi t where t.perheid=tilausrivi.perheid and tilausrivi.yhtio = '$kukarow[yhtio]' and tilausrivi.tyyppi!='D')";
		
		//	Käsitellään tuoterivit
		$query = "	SELECT tilausrivi.*, 
					if(tilausrivi.perheid=0,$rivihinta,$perherivihinta) rivihinta, (jt+varattu) kpl,
					tilausrivin_lisatiedot.positio, tilausrivin_lisatiedot.asiakkaan_positio, tilausrivin_lisatiedot.ei_nayteta, ei_saldoa, $sorttauskentta
					FROM tilausrivi
					JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
					LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
					WHERE tilausrivi.otunnus IN ($tunnukset) and (perheid=0 or perheid=tilausrivi.tunnus or tilausrivin_lisatiedot.ei_nayteta='' or tilausrivin_lisatiedot.ei_nayteta IS NULL)
					and tilausrivi.yhtio = '$kukarow[yhtio]'
					and tilausrivi.tyyppi!='D'
					$ennakkolisa
					ORDER BY otunnus, sorttauskentta";
		
		$grand_total=0;
		$riresult = mysql_query($query) or pupe_error($query);
		if(mysql_num_rows($riresult)>0) {

			$s=$pdf->teeSarakkeet(array(105,20,25));
			$summa = 0;
			unset($edperhe);
			
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
				
				if($h < 20) {
					$pdf = alku($pdf, $laskurow,$kieli, "OK");
					$h = 190;
				}
				
				//	Perheiden väliin laitetaan yksi extraväli..
				if(isset($edperhe) and $edperhe != $row["perheid"]) {
					$h-=$pdf->ln(1);
					unset($edperhe);
				}
				
				//	Jos meillä on "normaali tuote"
				if($row["perheid"]==0 or $row["perheid"]==$row["tunnus"]) {
					
					//	Jos meillä on isätuote fiksataan yksikköhinta
					if($row["perheid"]==$row["tunnus"]) {
						$row["hinta"] = round(($row["rivihinta"]/$row["kpl"]),2);
					}
					
					$pdf->write($h, 1, 0, $s[0], $row["tuoteno"]." ".$row["nimitys"], "norm", "L", "");
					$pdf->write($h, 1, $s[0], $s[1], $row["kpl"]." ".strtolower($row["yksikko"]), "norm", "R", "");
					if($laskurow["rivihintoja_ei_nayteta"] == "") {
						$pdf->write($h, 1, $s[1], $s[2], money_format('%!i',$row["hinta"]), "norm", "R", "");
						$pdf->write($h, 1, $s[2], 0, money_format('%!i',$row["rivihinta"]), "norm", "R", "");				
					}
					$h-=$pdf->ln();
					$pdf->write($h, 1, $s[0], $s[1], strftime("%x",strtotime($row["toimaika"])), "snadi", "R", "I");
					if($alenaytetaan=="OK" and $row["ale"] > 0) {
						$pdf->write($h, 1, $s[1], $s[2], $row["ale"]."%", "snadi", "R", "I");
					}

					$ei_valia = "";
					if($row["kommentti"] != "") {
						if($pdf->pt_mm($pdf->writeStrlen($row["kommentti"], "snadi")) > $s[0]) {
							$pdf->write($h, 2, 0, $s[0], "* ".$row["kommentti"], "snadi", "L", "I");	
							$h-=$pdf->ln(2);
						} 
						else {
							$pdf->write($h, 1, 0, $s[0], "* ".$row["kommentti"], "snadi", "L", "I");	
							$h-=$pdf->ln();
						}
						$ei_valia = "OK";
					}
					if($row["asiakkaan_positio"] > 0 and $row["ei_saldoa"] == "") {
						$pdf->write($h, 1, 0, $s[0], t("RS-koodi", $kieli).": $laskurow[asiakkaan_kohde]-$row[asiakkaan_positio]", "norm", "L", "I");
						$h-=$pdf->ln();
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
				//	Meillä on vaan näytettävä tuoteperhetuote
				else {
					$pdf->write($h, 1, 5, $s[0], $row["tuoteno"]." ".$row["nimitys"], "snadi", "L", "");
					$pdf->write($h, 1, $s[0], $s[1], $row["kpl"]." ".strtolower($row["yksikko"]), "snadi", "R", "");					
					$h-=$pdf->ln(1.5);

					if($row["kommentti"] != "") {
						$nb=$pdf->countParagraphHeight($row["kommentti"], "snadi", (5-$s[0]));
						$pdf->write($h, $nb, 5, $s[0], $row["kommentti"], "snadi", "L", "I");	
						$h-=$pdf->ln($nb);					
					}
					$edperhe=$row["perheid"];					
				}
			}
			$grand_total+=$summa;
			$h-=$pdf->ln(3);
			
		}			

		$pdf->write($h, 1, $s[0], $s[2], t("Tilaus yhteensä alv 0%",$kieli).": ", "norm", "R", "B");
		$pdf->write($h, 1, $s[2], 0, money_format('%i',$grand_total), "norm", "R", "B");
		$h-=$pdf->ln(3);
		
		if($h < 60) {
			$pdf = alku($pdf, $laskurow,$kieli);
			$h = 190;
		}
		
		if($laskurow["sisviesti1"] != "") {
			//	Maksusopimus
			$pdf->write($h, 1, 0, 0, t("LISÄTIEDOT", $kieli), "norm", "L", "B");
			$h-=$pdf->ln(1.25);
			$nb=$pdf->countParagraphHeight($laskurow["sisviesti1"], "norm")+2;
			$pdf->write($h, $nb, 0, 0, $laskurow["sisviesti1"], "norm", "L", "I");
			$h-=$pdf->ln($nb);					
		}
				
		$query = "	SELECT maksupositio.*, round(osuus,0) osuus, maksuehto.teksti
					FROM maksupositio
					LEFT JOIN maksuehto on maksuehto.yhtio=maksupositio.yhtio and maksuehto.tunnus=maksupositio.maksuehto
					WHERE maksupositio.yhtio = '$kukarow[yhtio]' and otunnus = $laskurow[jaksotettu]";
		$result = mysql_query($query) or pupe_error($query);
		if(mysql_num_rows($result)>0) {
			
			//	Koitetaan arpoa mahtuuko tämä samalle sivulle, vai ei..
			if($h < (mysql_num_rows($result)*$pdf->ln(2.75) + 10)) {
				$pdf = alku($pdf, $laskurow,$kieli);
				$h = 190;
			}

			//	Maksusopimus
			$pdf->write($h, 1, 0, 0, t("MAKSUEHTO", $kieli), "norm", "L", "B");
			$h-=$pdf->ln(1.25);
			
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
			$h-=$pdf->ln(1.25);
			
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
		//keksitään uudelle failille joku varmasti uniikki nimi:
		list($usec, $sec) = explode(' ', microtime());
		mt_srand((float) $sec + ((float) $usec * 100000));
		$pdf_tilausvahvistusfilenimi = "/tmp/Tilausvahvistus-".md5(uniqid(mt_rand(), true)).".pdf";

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
		$fh = fopen($pdf_tilausvahvistusfilenimi, "w");
		if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF create error $pdf_tilausvahvistusfilenimi");
		fclose($fh);
		
		// itse print komento...
		if ($komento == 'email' or substr($komento,0,12) == 'asiakasemail') {
			$liite = $pdf_tilausvahvistusfilenimi;
			if($laskurow["tunnusnippu"] > 0) {
				$kutsu = "Tilausvahvistus $laskurow[tunnusnippu]";
			}
			else {
				$kutsu = "Tilausvahvistus $laskurow[tunnus]";				
			}

			echo t("Tilausvahvistus lähetetään osoitteeseen '$kukarow[eposti]'")."...\n";

			if ($kukarow["extranet"] == '') {
				require("../inc/sahkoposti.inc");
			}
			else {
				require("sahkoposti.inc");
			}
		}
		elseif ($tee == 'NAYTATILAUS') {
			//Työnnetään tuo pdf vaan putkeen!
			echo file_get_contents($pdf_tilausvahvistusfilenimi);
		}
		elseif ($komento != '' and $komento != 'edi') {
			//	Haetaan kirjoittimen nimi..
			$querykieli = "	select *
							from kirjoittimet
							where yhtio='$kukarow[yhtio]' and komento='$komento'";
			$kires = mysql_query($querykieli) or pupe_error($querykieli);
			if(mysql_num_rows($kires) > 0) {
				$kirow = mysql_fetch_array($kires);
				echo t("Tilausvahvistus tulostuu kirjoittimelle").": $kirow[kirjoitin]<br>\n";						
				
				echo t("Tilausvahvistus tulostuu")."...<br>";
				unset($returnvalue);
				$line = exec("$komento $pdf_laskufilenimi", $output, $returnvalue);
			}
			if(mysql_num_rows($result) == 0 or $returnvalue!=0) {
				echo "<font class='error'>".t("Tilausvahvistuksen tulostus epäonnistui")."!!!</font><br>\n";	
			}
		}

		//poistetaan tmp file samantien kuleksimasta...
		system("rm -f $pdf_tilausvahvistusfilenimi");				
	}
}

?>
