<?php

	function uusi_karhukierros($yhtio) {
		$query = "SELECT tunnus FROM karhukierros where pvm=current_date and yhtio='$yhtio'";
		$result = mysql_query($query) or pupe_error($query);
		$array = mysql_fetch_array($result);

		if (!mysql_num_rows($result)) {
			$query = "INSERT INTO karhukierros (pvm,yhtio) values (current_date,'$yhtio')";
			$result = mysql_query($query) or pupe_error($query);
			
			$query = "SELECT LAST_INSERT_ID() FROM karhukierros";
			$result = mysql_query($query) or pupe_error($query);
			$array = mysql_fetch_array($result);
		}
		$out = $array[0];
		return $out;

	}

	function liita_lasku($ktunnus,$ltunnus) {
		$query = "INSERT INTO karhu_lasku (ktunnus,ltunnus) values ($ktunnus,$ltunnus)";
		$result = mysql_query($query) or pupe_error($query);
	}

	function alku ($viesti = null) {
		global $pdf, $asiakastiedot, $yhteyshenkilo, $yhtiorow, $kukarow, $kala, $sivu,
			$rectparam, $norm, $pieni, $boldi, $kaatosumma, $kieli;

		$firstpage = $pdf->new_page("a4");
		$pdf->enable('template');
		$tid = $pdf->template->create();
		$pdf->template->size($tid, 600, 830);

		//Haetaan yhteyshenkilon tiedot
		$apuqu = "	select *
					from kuka
					where yhtio='$kukarow[yhtio]' and tunnus='$yhteyshenkilo'";
		$yres = mysql_query($apuqu) or pupe_error($apuqu);
		$yrow = mysql_fetch_array($yres);

		//Otsikko
		//$pdf->draw_rectangle(830, 20,  800, 580, $firstpage, $rectparam);
		$pdf->draw_text(30, 805,  $yhtiorow["nimi"], $firstpage);
		$pdf->draw_text(320, 780, t("MAKSUKEHOTUS", $kieli), 	$firstpage);
		$pdf->draw_text(470, 780, t("Sivu", $kieli)." ".$sivu, 	$firstpage, $norm);

		// lähettäjä
		//$pdf->draw_text(mm_pt(22), mm_pt(272), t("Lähettäjä", $kieli), 	$firstpage, $pieni);
		$iso = array('height' => 11, 'font' => 'Times-Roman');
		$pdf->draw_text(mm_pt(22), mm_pt(268), strtoupper($yhtiorow["nimi"]), 		$firstpage, $iso);
		$pdf->draw_text(mm_pt(22), mm_pt(264), strtoupper($yhtiorow["nimitark"]), 	$firstpage, $iso);
		$pdf->draw_text(mm_pt(22), mm_pt(260), strtoupper($yhtiorow["osoite"]), 		$firstpage, $iso);
		$pdf->draw_text(mm_pt(22), mm_pt(256), strtoupper($yhtiorow["postino"]." ".$yhtiorow["postitp"]), $firstpage, $iso);
		//$pdf->draw_text(mm_pt(22), mm_pt(252), strtoupper($yhtiorow["maa"]), 		$firstpage, $iso);
		
		// vastaanottaja
		//$pdf->draw_rectangle(737, 20,  674, 300, $firstpage, $rectparam);
		//$pdf->draw_text(mm_pt(22), mm_pt(237), t("Vastaanottaja", $kieli), 	$firstpage, $pieni);
		$pdf->draw_text(mm_pt(22), mm_pt(234), strtoupper($asiakastiedot["nimi"]), 		$firstpage, $iso);
		$pdf->draw_text(mm_pt(22), mm_pt(230), strtoupper($asiakastiedot["nimitark"]), 	$firstpage, $iso);
		$pdf->draw_text(mm_pt(22), mm_pt(226), strtoupper($asiakastiedot["osoite"]), 		$firstpage, $iso);
		$pdf->draw_text(mm_pt(22), mm_pt(222), strtoupper($asiakastiedot["postino"]." ".$asiakastiedot["postitp"]), $firstpage, $iso);
		
		// jos vastaanottaja on eri maassa kuin yhtio niin lisätään maan nimi
		if ($yhtiorow['maa'] != $asiakastiedot['maa']) {
			$query = sprintf(
					"SELECT nimi from maat where koodi='%s' AND ryhma_tunnus = ''",
					mysql_real_escape_string($asiakastiedot['maa'])
			);
			
			$maa_result = mysql_query($query) or pupe_error($query);
			$maa_nimi = mysql_fetch_array($maa_result);
			$pdf->draw_text(mm_pt(22), mm_pt(218), $maa_nimi['nimi'], $firstpage, $iso);
		}
		

		//Oikea sarake
		$pdf->draw_rectangle(760, 320, 739, 575, 				$firstpage, $rectparam);
		$pdf->draw_rectangle(760, 420, 739, 575, 				$firstpage, $rectparam);
		$pdf->draw_text(330, 752, t("Päivämäärä", $kieli), 		$firstpage, $pieni);
		$pdf->draw_text(330, 742, tv1dateconv(date('Y-m-d')), 	$firstpage, $norm);
		$pdf->draw_text(430, 752, t("Asiaa hoitaa", $kieli), 	$firstpage, $pieni);
		$pdf->draw_text(430, 742, $yrow["nimi"], 				$firstpage, $norm);

		$pdf->draw_rectangle(739, 320, 718, 575, $firstpage, $rectparam);
		$pdf->draw_rectangle(739, 420, 718, 575, $firstpage, $rectparam);
		$pdf->draw_text(330, 731, t("Eräpäivä", $kieli), $firstpage, $pieni);
		
		$paiva	   = date("j");
		$kuu   	   = date("n");
		$year  	   = date("Y");
		$seurday   = date("d",mktime(0, 0, 0, $kuu, $paiva+7,  $year));
		$seurmonth = date("m",mktime(0, 0, 0, $kuu, $paiva+7,  $year));
		$seuryear  = date("Y",mktime(0, 0, 0, $kuu, $paiva+7,  $year));
		
		$pdf->draw_text(330, 721, tv1dateconv($seuryear."-".$seurmonth."-".$seurday), $firstpage, $norm);
		
		$pdf->draw_text(430, 731, t("Puhelin", $kieli), $firstpage, $pieni);
		$pdf->draw_text(430, 721, $yrow["puhno"], $firstpage, $norm);
		
		$pdf->draw_rectangle(718, 320, 697, 575, $firstpage, $rectparam);
		$pdf->draw_rectangle(718, 420, 697, 575, $firstpage, $rectparam);
		$pdf->draw_text(330, 710, t("Viivästykorko", $kieli), 	$firstpage, $pieni);
		$pdf->draw_text(330, 700, $yhtiorow["viivastyskorko"], 	$firstpage, $norm);
		$pdf->draw_text(430, 710, t("Sähköposti", $kieli), 		$firstpage, $pieni);
		$pdf->draw_text(430, 700, $yrow["eposti"], 				$firstpage, $norm);
		
		$pdf->draw_rectangle(697, 320, 676, 575, $firstpage, $rectparam);
		$pdf->draw_text(330, 689, t("Ytunnus/Asiakasnumero", $kieli), 	$firstpage, $pieni);
		$pdf->draw_text(330, 679, $asiakastiedot["ytunnus"], 			$firstpage, $norm);

		//Rivit alkaa täsä kohtaa
		$kala = 540;
		
		// lisätään karhuviesti kirjeeseen
		if ($sivu == 1) {
			// tehdään riveistä max 90 merkkiä
			$viesti = wordwrap($viesti, 90, "\n");
			
            $i = 0;
            $rivit = explode("\n", $viesti);
			$rivit[] = '';
			$rivit[] = t("Yhteyshenkilömme", $kieli) . ": $yrow[nimi] / $yrow[eposti] / $yrow[puhno]";
            foreach ($rivit as $rivi) {
				// laitetaan 
                $pdf->draw_text(80, $kala, t($rivi, $kieli), $firstpage, $norm);
				
				// seuraava rivi tulee 10 pistettä alemmas kuin tämä rivi
				$kala -= 10;
                $i++;
            }
		}
		
		$kala -= 10;
		
		//Laskurivien otsikkotiedot
		//eka rivi
		$pdf->draw_text(30,  $kala, t("Laskun numero", $kieli),				$firstpage, $pieni);
		$pdf->draw_text(120, $kala, t("Laskun pvm", $kieli),				$firstpage, $pieni);
		$pdf->draw_text(200, $kala, t("Eräpäivä", $kieli),					$firstpage, $pieni);
		$pdf->draw_text(280, $kala, t("Myöhässä pv", $kieli),				$firstpage, $pieni);
		$pdf->draw_text(340, $kala, t("Viimeisin muistutuspvm", $kieli),	$firstpage, $pieni);
		$pdf->draw_text(440, $kala, t("Laskun summa", $kieli),				$firstpage, $pieni);
		$pdf->draw_text(520, $kala, t("Perintäkerta", $kieli),				$firstpage, $pieni);

		$kala -= 15;

		//toka rivi
		if ($kaatosumma != 0 and $sivu == 1) {
			$pdf->draw_text(30,  $kala, t("Kohdistamattomia suorituksia", $kieli),	$firstpage, $norm);
			$pdf->draw_text(440, $kala, $kaatosumma,								$firstpage, $norm);
			$kala -= 13;
		}


		return($firstpage);
	}

	function rivi ($firstpage, $summa) {
		global $firstpage, $pdf, $row, $kala, $sivu, $lask, $rectparam, $norm, $pieni, $lask, $kieli;
		
		// siirrytäänkö uudelle sivulle?
		if ($kala < 123) {
			$sivu++;
			loppu($firstpage,'');
			$firstpage = alku();
			$lask = 1;
		}
		
		$pdf->draw_text(30,  $kala, $row["laskunro"],					$firstpage, $norm);
		$pdf->draw_text(120, $kala, tv1dateconv($row["tapvm"]), 		$firstpage, $norm);
		$pdf->draw_text(200, $kala, tv1dateconv($row["erpcm"]), 		$firstpage, $norm);
		$pdf->draw_text(280, $kala, $row["ika"], 						$firstpage, $norm);
		$pdf->draw_text(340, $kala, tv1dateconv($row["kpvm"]),			$firstpage, $norm);
		$pdf->draw_text(440, $kala, $row["summa"], 						$firstpage, $norm);
		$pdf->draw_text(520, $kala, $row["karhuttu"]+1, 				$firstpage, $norm);
		$kala = $kala - 13;

		$lask++;
		$summa+=$row["summa"];
		return($summa);
	}


	function loppu ($firstpage, $summa) {

		global $pdf, $yhtiorow, $kukarow, $sivu, $rectparam, $norm, $pieni, $kaatosumma, $kieli, $ktunnus, $factoringrow, $maksuehtotiedot;

		//yhteensärivi
		$pdf->draw_rectangle(110, 20, 90, 580,	$firstpage, $rectparam);
		$pdf->draw_rectangle(110, 207, 90, 580,	$firstpage, $rectparam);
		$pdf->draw_rectangle(110, 394, 90, 580,	$firstpage, $rectparam);
		$pdf->draw_rectangle(110, 540, 90, 580,	$firstpage, $rectparam);

		$pdf->draw_text(404, 92,  t("YHTEENSÄ", $kieli).":",	$firstpage, $norm);
		$pdf->draw_text(464, 92,  $summa,						$firstpage, $norm);
		$pdf->draw_text(550, 92,  $yhtiorow["valkoodi"],		$firstpage, $norm);

		//Pankkiyhteystiedot
		$pdf->draw_rectangle(90, 20, 20, 580,	$firstpage, $rectparam);

		if ($ktunnus != 0) {
			$pdf->draw_text(30, 82,  t("Pankkiyhteys", $kieli),	$firstpage, $pieni);
			$pdf->draw_text(30, 72,  $factoringrow["pankkinimi1"]." ".$factoringrow["pankkitili1"],	$firstpage, $norm);
		}
		else {

			if ($maksuehtotiedot["pankkinimi1"] != "") {
				$yhtiorow["pankkinimi1"]	= $maksuehtotiedot["pankkinimi1"];
				$yhtiorow["pankkitili1"]	= $maksuehtotiedot["pankkitili1"];
				$yhtiorow["pankkiiban1"]	= $maksuehtotiedot["pankkiiban1"];
				$yhtiorow["pankkiswift1"]	= $maksuehtotiedot["pankkiswift1"];
				$yhtiorow["pankkinimi2"]	= $maksuehtotiedot["pankkinimi2"];
				$yhtiorow["pankkitili2"]	= $maksuehtotiedot["pankkitili2"];
				$yhtiorow["pankkiiban2"]	= $maksuehtotiedot["pankkiiban2"];
				$yhtiorow["pankkiswift2"]	= $maksuehtotiedot["pankkiswift2"];
				$yhtiorow["pankkinimi3"]	= $maksuehtotiedot["pankkinimi3"];
				$yhtiorow["pankkitili3"]	= $maksuehtotiedot["pankkitili3"];
				$yhtiorow["pankkiiban3"]	= $maksuehtotiedot["pankkiiban3"];
				$yhtiorow["pankkiswift3"]	= $maksuehtotiedot["pankkiswift3"];
			}

			$pdf->draw_text(30, 82,  t("Pankkiyhteys", $kieli),	$firstpage, $pieni);
			$pdf->draw_text(30, 72,  $yhtiorow["pankkinimi1"]." ".$yhtiorow["pankkitili1"],	$firstpage, $norm);
			$pdf->draw_text(217, 72, $yhtiorow["pankkinimi2"]." ".$yhtiorow["pankkitili2"],	$firstpage, $norm);
			$pdf->draw_text(404, 72, $yhtiorow["pankkinimi3"]." ".$yhtiorow["pankkitili3"],	$firstpage, $norm);
		}

		//Alimmat kolme laatikkoa, yhtiötietoja
		$pdf->draw_rectangle(70, 20, 20, 580,	$firstpage, $rectparam);
		$pdf->draw_rectangle(70, 207, 20, 580,	$firstpage, $rectparam);
		$pdf->draw_rectangle(70, 394, 20, 580,	$firstpage, $rectparam);

		$pdf->draw_text(30, 55, $yhtiorow["nimi"],		$firstpage, $pieni);
		$pdf->draw_text(30, 45, $yhtiorow["osoite"],	$firstpage, $pieni);
		$pdf->draw_text(30, 35, $yhtiorow["postino"]."  ".$yhtiorow["postitp"],	$firstpage, $pieni);
		$pdf->draw_text(30, 25, $yhtiorow["maa"],		$firstpage, $pieni);

		$pdf->draw_text(217, 55, t("Puhelin", $kieli).":",			$firstpage, $pieni);
		$pdf->draw_text(247, 55, $yhtiorow["puhelin"],				$firstpage, $pieni);
		$pdf->draw_text(217, 45, t("Fax", $kieli).":",				$firstpage, $pieni);
		$pdf->draw_text(247, 45, $yhtiorow["fax"],					$firstpage, $pieni);
		$pdf->draw_text(217, 35, t("Email", $kieli).":",			$firstpage, $pieni);
		$pdf->draw_text(247, 35, $yhtiorow["email"],				$firstpage, $pieni);

		$pdf->draw_text(404, 55, t("Y-tunnus", $kieli).":",			$firstpage, $pieni);
		$pdf->draw_text(444, 55, $yhtiorow["ytunnus"],				$firstpage, $pieni);
		$pdf->draw_text(404, 45, t("Kotipaikka", $kieli).":",		$firstpage, $pieni);
		$pdf->draw_text(444, 45, $yhtiorow["kotipaikka"],			$firstpage, $pieni);
		$pdf->draw_text(404, 35, t("Enn.per.rek", $kieli),			$firstpage, $pieni);
		$pdf->draw_text(404, 25, t("Alv.rek", $kieli),				$firstpage, $pieni);

	}

	require('pdflib/phppdflib.class.php');

	//echo "<font class='message'>Karhukirje tulostuu...</font>";
	flush();

	//PDF parametrit
	$pdf = new pdffile;
	$pdf->set_default('margin-top', 	0);
	$pdf->set_default('margin-bottom', 	0);
	$pdf->set_default('margin-left', 	0);
	$pdf->set_default('margin-right', 	0);
	$rectparam["width"] = 0.3;

	$norm["height"] = 10;
	$norm["font"] = "Times-Roman";

	$boldi["height"] = 10;
	$boldi["font"] = "Times-Bold";

	$pieni["height"] = 8;
	$pieni["font"] = "Times-Roman";

	// defaultteja
	$lask = 1;
	$sivu = 1;

	// aloitellaan laskun teko
	$xquery='';
	for ($i=0; $i<sizeof($lasku_tunnus); $i++) {
		if($i != 0) {
			$xquery=$xquery . ",";
		}

		$xquery .= "$lasku_tunnus[$i]";
	}

	$query = "	SELECT l.tunnus, l.tapvm, l.liitostunnus,
				l.summa-l.saldo_maksettu summa, l.erpcm, l.laskunro,
				TO_DAYS(now()) - TO_DAYS(l.erpcm) as ika, max(kk.pvm) as kpvm, count(distinct kl.ktunnus) as karhuttu
				FROM lasku l
				LEFT JOIN karhu_lasku kl on (l.tunnus=kl.ltunnus)
				LEFT JOIN karhukierros kk on (kk.tunnus=kl.ktunnus)
				WHERE l.tunnus in ($xquery) and l.yhtio='$kukarow[yhtio]' and l.tila='U'
				GROUP BY 1
				ORDER BY l.erpcm";
	$result = mysql_query($query) or pupe_error($query);

	//otetaan maksuehto- ja asiakastiedot ekalta laskulta
	$laskutiedot = mysql_fetch_array($result);

	$query = "	SELECT *
				FROM maksuehto
				WHERE yhtio='$kukarow[yhtio]' AND tunnus = '$laskutiedot[maksuehto]'";
	$maksuehtoresult = mysql_query($query) or pupe_error($query);
	$maksuehtotiedot = mysql_fetch_array($maksuehtoresult);

	$query = "	SELECT *
				FROM asiakas
				WHERE yhtio='$kukarow[yhtio]' AND tunnus = '$laskutiedot[liitostunnus]'";
	$asiakasresult = mysql_query($query) or pupe_error($query);
	$asiakastiedot = mysql_fetch_array($asiakasresult);

	//Otetaan tässä asiakkaan kieli talteen
	$kieli = $asiakastiedot["kieli"];

	//ja kelataan akuun
	mysql_data_seek($result,0);

	$query = "	SELECT GROUP_CONCAT(distinct liitostunnus) liitokset
				FROM lasku
				WHERE lasku.yhtio = '$kukarow[yhtio]'
				and lasku.tunnus in ($xquery)";
	$lires = mysql_query($query) or pupe_error($query);
	$lirow = mysql_fetch_array($lires);

	$query = "	SELECT SUM(summa) summa
				FROM suoritus
				WHERE yhtio  = '$kukarow[yhtio]'
				and ltunnus <> 0
				and asiakas_tunnus in ($lirow[liitokset])";
	$summaresult = mysql_query($query) or pupe_error($query);
	$kaato = mysql_fetch_array($summaresult);

	$kaatosumma=$kaato["summa"] * -1;
	if (!$kaatosumma) $kaatosumma='0.00';
    
    if (isset($_POST['karhuviesti'])) {
		$query 	 = "select selitetark from avainsana where tunnus='$karhuviesti' AND laji = 'KARHUVIESTI' AND yhtio ='{$yhtiorow['yhtio']}'";
		$res 	 = mysql_query($query) or pupe_error();
		$viestit = mysql_fetch_array($res);
		
        $karhuviesti = $viestit["selitetark"];
    } 
	else {
        // otetaan defaulttina eka viesti
        $karhuviesti = 'Virhe';
    }
	
	$firstpage = alku($karhuviesti);

	$summa=0.0;
	$rivit = array();
	while ($row = mysql_fetch_array($result)) {
		$rivit[] = $row;
		$summa = rivi($firstpage,$summa);
	}
	
	// loppusumma
	$loppusumma = sprintf('%.2f', $summa+$kaatosumma);
	
	// viimenen sivu
	loppu($firstpage,$loppusumma);
	
	//keksitään uudelle failille joku varmasti uniikki nimi:
	list($usec, $sec) = explode(' ', microtime());
	mt_srand((float) $sec + ((float) $usec * 100000));
	$pdffilenimi = "/tmp/karhukirje-".md5(uniqid(mt_rand(), true)).".pdf";

	//kirjoitetaan pdf faili levylle..
	$fh = fopen($pdffilenimi, "w");
	if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus epäonnistui $pdffilenimi");
	fclose($fh);
	
	
	// jos halutaan eKirje sekä configuraatio on olemassa niin
	// lähetetään eKirje
	if (isset($_POST['ekirje_laheta']) === true
	and (isset($ekirje_config) and is_array($ekirje_config))) {
		
		// ---------------------------------------------------------------------
		// tähän ekirjeen lähetys
	
		// pdfekirje luokka
		include 'inc/ekirje.inc';
		
		$ekirje_tunnus = date('dmY') . $asiakastiedot['tunnus'];
	
		$info = array(
			'tunniste'              => $ekirje_tunnus, 			// asiakkaan oma kirjeen tunniste
	        'kirjeluokka'           => '1',                    	// 1 = priority, 2 = economy
	        'osasto'                => $kukarow['yhtio'],       // osastokohtainen erittely = mikä yritys
	        'file_id'               => $ekirje_tunnus,          // lähettäjän tunniste tiedostolle
	        'kirje_id'              => $ekirje_tunnus,          // kirjeen id
			'contact_name'          => $kukarow['nimi'],
			'contact_email'         => $kukarow['eposti'],
			'contact_phone'         => $kukarow['puhno'],
			'yritys_nimi'           => trim($yhtiorow['nimi'] . ' ' . $yhtiorow['nimitark']),
			'yritys_osoite'         => $yhtiorow['osoite'],
			'yritys_postino'        => $yhtiorow['postino'],
			'yritys_postitp'        => $yhtiorow['postitp'],
	        'yritys_maa'            => $yhtiorow['maa'],
	        'vastaanottaja_nimi'    => trim($asiakastiedot['nimi'] . ' ' . $asiakastiedot['nimitark']),
	        'vastaanottaja_osoite'  => $asiakastiedot['osoite'],
	        'vastaanottaja_postino' => $asiakastiedot['postino'],
	        'vastaanottaja_postitp' => $asiakastiedot['postitp'],
	        'vastaanottaja_maa'     => $asiakastiedot['maa'],
	        'sivumaara'             => $sivu,
		);
	
		// otetaan configuraatio filestä salasanat ja muut
		$info = array_merge($info, (array) $ekirje_config);
	
		$ekirje = new Pupe_Pdfekirje($info);
	
		//koitetaan lähettää eKirje
		$ekirje->send($pdffilenimi);
		
		// poistetaan filet omalta koneelta
		$ekirje->clean();
	}
	
	// ------------------------------------------------------------------------
	//
	// nyt kirjoitetaan tiedot vasta kantaan kun tiedetään että kirje
	// on lähtenyt Itellaan tai tulostetaan kirje ainoastaan
	$karhukierros = uusi_karhukierros($kukarow['yhtio']);

	foreach ($rivit as $row) {
		liita_lasku($karhukierros,$row['tunnus']);
	}
	
	// tulostetaan jos ei lähetetä ekirjettä
	if (isset($_POST['ekirje_laheta']) === false) {
		
		// itse print komento...
		$query = "	select komento
					from kirjoittimet
					where yhtio='{$kukarow['yhtio']}' and tunnus = '{$kukarow['kirjoitin']}'";
		$kires = mysql_query($query) or pupe_error($query);
		
		if (mysql_num_rows($kires) == 1) {
			$kirow = mysql_fetch_array($kires);
			if($kirow["komento"] == "email") {
				$liite = $pdffilenimi;
				$kutsu = "Karhukirje ".$asiakastiedot["ytunnus"];
				echo t("Karhukirje lähetetään osoitteeseen $kukarow[eposti]")."...\n";

				require("inc/sahkoposti.inc");				
			}
			else {
				$line = exec("{$kirow['komento']} $pdffilenimi");
			}
			
		}
	}
?>
