<?php

require_once('../pdflib/pupepdf.class.php');

if (!function_exists('tulosta_tilausvahvistus')) {
	function tulosta_tilausvahvistus ($otunnus, $komento, $kieli = "", $tee) {
		global $yhtiorow, $kukarow, $fonts;

		// Haetaan laskun tiedot
		$query = "	SELECT lasku.*, lasku.tunnus tunnus, valkoodi valuutta, lasku.nimi nimi, lasku.luontiaika p‰iv‰ys, laskun_lisatiedot.asiakkaan_kohde, lasku.tunnusnippu projekti, lasku.tunnus tilaus, lasku.tunnus toimitus, viesti asiakkaan_viite, sisviesti1 lis‰tiedot, lasku.ytunnus asiakkaan_VAT, laskun_lisatiedot.rivihintoja_ei_nayteta,
					concat_ws(' ', postino, postitp) postiosoite,
					concat_ws(' ', toim_postino, toim_postitp) toim_postiosoite,					
					yhteyshenkilo_kaupallinen.nimi asiakkaan_kaupallinen_k‰sittelij‰,
					yhteyshenkilo_tekninen.nimi asiakkaan_yhteyshenkilˆ,
					SUBSTRING(lasku_maa.nimi FROM 5) maa,
					SUBSTRING(toim_maa.nimi FROM 5) toim_maa
					FROM lasku
					LEFT JOIN laskun_lisatiedot ON lasku.yhtio=laskun_lisatiedot.yhtio and lasku.tunnus=laskun_lisatiedot.otunnus
					LEFT JOIN yhteyshenkilo yhteyshenkilo_tekninen ON laskun_lisatiedot.yhtio=yhteyshenkilo_tekninen.yhtio and laskun_lisatiedot.yhteyshenkilo_tekninen=yhteyshenkilo_tekninen.tunnus					
					LEFT JOIN yhteyshenkilo yhteyshenkilo_kaupallinen ON laskun_lisatiedot.yhtio=yhteyshenkilo_kaupallinen.yhtio and laskun_lisatiedot.yhteyshenkilo_kaupallinen=yhteyshenkilo_kaupallinen.tunnus
					LEFT JOIN kuka AS laatija ON lasku.laatija=laatija.kuka
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
		
		//	Aloitellaan piirtely‰
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
			
			//	Tehd‰‰n uusi sivu
			$pdf->addPage("a4");
			$h=265;
			
			//	Liitet‰‰n kuva
			$yhtiorow["lasku_logo"] = "../pics/RTEC_laskulogo.png";
			if($yhtiorow["lasku_logo"] != "" and file_exists($yhtiorow["lasku_logo"])) {
				$pdf->placeImage(260,0,$yhtiorow["lasku_logo"]);
			}
						
			//	Kirjoitetaan otsikko
			$pdf->write($h, 2, 0, 0, t("TILAUSVAHVISTUS", $kieli), "BIG", "C", "B");
			
			//	Laitetaan perustiedot oikealle		
			$h=250;
			$s=$pdf->teeSarakkeet(array(105,30));
			if($laskurow["projekti"] != "" and $laskurow["projekti"] == $laskurow["tunnus"]) {
				$kentat = array("projekti", "p‰iv‰ys", "valuutta", "toimitustapa", "toimitusehto", "asiakkaan_VAT");
			}
			else {
				$kentat = array("tilaus", "p‰iv‰ys", "valuutta", "toimitustapa", "toimitusehto", "asiakkaan_VAT");
			}
			
			foreach($kentat as $key) {
				if($key == "p‰iv‰ys") {
					$laskurow[$key] = strftime("%x",strtotime($laskurow[$key]));
				}
				$pdf->write($h, 1, $s[0], $s[1], t(str_replace("_"," ",ucfirst($key)), $kieli).":", "norm", "L", "B");
				$pdf->write($h, 1, $s[1]+2, 0, $laskurow[$key], "norm", "L", "");
				$h-=$pdf->ln();					
			}
			$h-=$pdf->ln(0.5);
			//	Sitten yhteyshenkilˆit‰
			foreach(array("asiakkaan_viite", "lis‰tiedot", "toimittajan_yhteyshenkilˆ", "asiakkaan_yhteyshenkilˆ", "asiakkaan_kaupallinen_k‰sittelij‰") as $key) {
				if($laskurow[$key] != "") {
					$pdf->write($h, 1, $s[0], 0, t(str_replace("_"," ",ucfirst($key)), $kieli).":", "norm", "L", "B");
					$h-=$pdf->ln();					
					$pdf->write($h, 1, $s[0], 0, $laskurow[$key], "norm", "L", "");
					$h-=$pdf->ln(1.5);
				}
			}
			
			//	Laitetaan toimitustiedot vasemmalle
			$h=250;
			$pdf->write($h, 1, 0, $s[1], t("Laskutusosoite", $kieli).":", "norm", "L", "B");
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
			
			
			return $pdf;
		}
		
		$pdf=alku($pdf, $laskurow,$kieli);
		$h = 190;
		
		if($laskurow["tila"] == "R") {
			$query = "	SELECT GROUP_CONCAT(tunnus) tunnukset
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]' and tunnusnippu = '$laskurow[tunnus]' and tila IN ('U','N')";
			$result = mysql_query($query) or pupe_error($query);
			$tunnusrow=mysql_fetch_array($result);
			$tunnukset=$tunnusrow["tunnukset"];
		}
		else {
			$tunnukset=$laskurow["tunnus"];
		}
		
		//	Voidaanko n‰ytt‰‰ alet?
		$query = "select tunnus from tilausrivi where yhtio = '$kukarow[yhtio]' and ale > 0 and otunnus IN ($tunnukset)";
		$abures = mysql_query($query) or pupe_error($query);
		if(mysql_num_rows($abures) > 0) {
			$alenaytetaan="OK";
		}
		else {
			$alenaytetaan="";
		}
		
		//	K‰sitell‰‰n tuoterivit
		$query = "	SELECT tilausrivi.*, 
					round($hinta_riv * if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+$laskurow[erikoisale]-(tilausrivi.ale*$laskurow[erikoisale]/100))/100)),2) rivihinta, (jt+varattu) kpl,
					tilausrivin_lisatiedot.positio, tilausrivin_lisatiedot.asiakkaan_positio, ei_saldoa
					FROM tilausrivi
					JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
					LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
					WHERE tilausrivi.otunnus IN ($tunnukset)
					and tilausrivi.yhtio = '$kukarow[yhtio]'
					and tilausrivi.tyyppi in  ('L','T','U')
					$ennakkolisa";
		
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
			$pdf->write($h, 1, $s[0], $s[1], t("M‰‰r‰", $kieli), "norm", "R", "B");				
			if($laskurow["rivihintoja_ei_nayteta"] == "") {
				$pdf->write($h, 1, $s[1], $s[2], t("Hinta/yks", $kieli), "norm", "R", "B");
				$pdf->write($h, 1, $s[2], 0, t("Yhteens‰", $kieli), "norm", "R", "B");					
			}
			$h-=$pdf->ln();
			$pdf->write($h, 1, 0, $s[0], t("Kommentti", $kieli), "norm", "L", "B");
			$pdf->write($h, 1, $s[0], $s[1], t("Toimituspvm", $kieli), "norm", "R", "B");			
			if($alenaytetaan=="OK") {
				$pdf->write($h, 1, $s[1], $s[2], t("Ale", $kieli), "norm", "R", "B");
			}
			
			$h-=$pdf->ln(1.5);

			while ($row = mysql_fetch_array($riresult)) {
				
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
				
				if($h < 20) {
					$pdf = alku($pdf, $laskurow,$kieli);
					$h = 190;
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
			$grand_total+=$summa;
			$h-=$pdf->ln(3);
			
		}			

		$pdf->write($h, 1, $s[1], $s[2], t("Tilaus yhteens‰",$kieli).": ", "norm", "R", "B");
		$pdf->write($h, 1, $s[2], 0, money_format('%i',$grand_total), "norm", "R", "B");
		$h-=$pdf->ln(4);
		
		if($h < 50) {
			$pdf = alku($pdf, $laskurow,$kieli);
			$h = 190;
		}
		
		if($laskurow["sisviesti1"] != "") {
			//	Maksusopimus
			$pdf->write($h, 1, 0, 0, t("LISƒTIEDOT", $kieli), "norm", "L", "B");
			$h-=$pdf->ln(1.25);
			$pdf->write($h, 4, 0, 0, $laskurow["sisviesti1"], "norm", "L", "I");
			$h-=$pdf->ln(4);			
		}
		
		//	Maksusopimus
		$pdf->write($h, 1, 0, 0, t("MAKSUEHTO", $kieli), "norm", "L", "B");
		$h-=$pdf->ln(1.25);
		
		$query = "	SELECT maksupositio.*, round(osuus,0) osuus, maksuehto.teksti
					FROM maksupositio
					LEFT JOIN maksuehto on maksuehto.yhtio=maksupositio.yhtio and maksuehto.tunnus=maksupositio.maksuehto
					WHERE maksupositio.yhtio = '$kukarow[yhtio]' and otunnus = $laskurow[jaksotettu]";
		$result = mysql_query($query) or pupe_error($query);
		if(mysql_num_rows($result)>0) {
			$i=0;
			while($row = mysql_fetch_array($result)) {
				$i++;
				$pdf->write($h, 1, 0, 5, "$i.", "norm", "L", "");
				$pdf->write($h, 1, 5, 0, "$row[osuus]%  ".t($row["teksti"], $kieli).", ".t($row["kuvaus"], $kieli)."", "norm", "L", "");
				if($row["lisatiedot"] != "") {
					$h-=$pdf->ln(1.25);
					$pdf->write($h, 1, 5, 0, $row["lisatiedot"], "norm", "L", "I");
				}
				$h-=$pdf->ln(1.25);
			}
		}
		else {
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
		$h = 15;
		$s=$pdf->teeSarakkeet(array(70,15,58,15));
		$pdf->write($h, 1, 0, $s[0], 		$yhtiorow["nimi"], "norm", "L", "B");
		$pdf->write($h, 1, $s[0], $s[1], 	t("Puh", $kieli).":", "norm", "L", "");
		$pdf->write($h, 1, $s[1], $s[2], 	$yhtiorow["puhelin"], "norm", "L", "");		
		if($laskurow["maa"] == "FI" or trim($laskurow["maa"]) == "SUOMI") {
			$pdf->write($h, 1, $s[2], $s[3], 	t("Y-tunnus", $kieli).":", "norm", "L", "");					
			$pdf->write($h, 1, $s[3], 0, 		$yhtiorow["ytunnus"], "norm", "R", "");		
		}
		else {
			$pdf->write($h, 1, $s[2], $s[3], 	t("VAT", $kieli).":", "norm", "L", "");					
			$pdf->write($h, 1, $s[3], 0, 		$yhtiorow["maakoodi"].sprintf("%08.8s",ereg_replace("[^0-9]","",$yhtiorow["ytunnus"])), "norm", "R", "");
		}
		$h-=$pdf->ln();

		$pdf->write($h, 1, 0, $s[1], 		$yhtiorow["osoite"], "norm", "L", "");
		$pdf->write($h, 1, $s[0], $s[1], 	t("Fax", $kieli).":", "norm", "L", "");
		$pdf->write($h, 1, $s[1], $s[2], 	$yhtiorow["fax"], "norm", "L", "");		
		if($laskurow["maa"] == "FI" or trim($laskurow["maa"]) == "SUOMI") {
			$pdf->write($h, 1, $s[2], $s[3], 	t("VAT", $kieli).":", "norm", "L", "");					
			$pdf->write($h, 1, $s[3], 0, 		$yhtiorow["maakoodi"].sprintf("%08.8s",ereg_replace("[^0-9]","",$yhtiorow["ytunnus"])), "norm", "R", "");		
		}		
		$h-=$pdf->ln();

		$pdf->write($h, 1, 0, $s[1], 		$yhtiorow["maakoodi"].$yhtiorow["postino"]." ".$yhtiorow["postitp"], "norm", "L", "");
		$pdf->write($h, 1, $s[0], $s[1], 	t("Web", $kieli).":", "norm", "L", "");
		$pdf->write($h, 1, $s[1], $s[2], 	$yhtiorow["www"], "norm", "L", "");		
		$h-=$pdf->ln();

		$pdf->write($h, 1, 0, $s[0], 		$yhtiorow["maa"], "norm", "L", "");
		$pdf->write($h, 1, $s[0], $s[1], 	t("Email", $kieli).":", "norm", "L", "");
		$pdf->write($h, 1, $s[1], $s[2], 	$yhtiorow["email"], "norm", "L", "");		
		// Tulostetaan sivu
		//keksit‰‰n uudelle failille joku varmasti uniikki nimi:
		list($usec, $sec) = explode(' ', microtime());
		mt_srand((float) $sec + ((float) $usec * 100000));
		$pdf_tilausvahvistusfilenimi = "/tmp/test_pdf.pdf";

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

			echo t("Tilausvahvistus l‰hetet‰‰n osoitteeseen '$kukarow[eposti]'")."...\n";

			if ($kukarow["extranet"] == '') {
				require("../inc/sahkoposti.inc");
			}
			else {
				require("sahkoposti.inc");
			}
		}
		elseif ($tee == 'NAYTATILAUS') {
			//Tyˆnnet‰‰n tuo pdf vaan putkeen!
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
				echo "<font class='error'>".t("Tilausvahvistuksen tulostus ep‰onnistui")."!!!</font><br>\n";	
			}
		}

		//poistetaan tmp file samantien kuleksimasta...
		system("rm -f $pdf_tilausvahvistusfilenimi");				
	}
}

?>