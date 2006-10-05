<?php

//viivakoodien tulostamista varten
define (__TRACE_ENABLED__, false);
define (__DEBUG_ENABLED__, false);

if ($kukarow["extranet"] == '') {
	require_once("../barcode/barcode.php");
	require_once("../barcode/c39object.php");
	require_once("../pdflib/phppdflib.class.php");
}
else {
	require_once("barcode/barcode.php");
	require_once("barcode/c39object.php");
	require_once("pdflib/phppdflib.class.php");
}

$rectparam["width"] = 0.3;
$norm["height"] = 10;
$norm["font"] = "Times-Roman";
$pieni["height"] = 8;
$pieni["font"] = "Times-Roman";

// defaultteja
$kala = 540;
$lask = 1;
$sivu = 1;


// laskun alku ja loppu functiot...
if (!function_exists('alku')) {
	function alku () {
		global $pdf, $laskurow, $yhtiorow, $kukarow, $sivu, $rectparam, $norm, $pieni, $maksuehto, $kieli;

		//etsitään myyjän nimi
		$query  = "	select nimi
					from kuka
					where tunnus='$laskurow[myyja]' and yhtio='$kukarow[yhtio]'";
		$myyresult = mysql_query($query) or pupe_error($query);
		$myyrow = mysql_fetch_array($myyresult);
		
		if ($myyrow["nimi"] == "") $myyrow["nimi"] = $kukarow["nimi"];

		// haetaan keräysaika
		$query = "select left(kerattyaika,10) kerattyaika from tilausrivi where yhtio='$kukarow[yhtio]' and otunnus='$laskurow[tunnus]' order by kerattyaika limit 1";
		$myyresult = mysql_query($query) or pupe_error($query);
		$kerattyrow = mysql_fetch_array($myyresult);
		list ($kervv, $kerkk, $kerpp) = split("-", $kerattyrow["kerattyaika"]);

		//etsitään asiakkasnumero
		$query  = "	select asiakasnro
					from asiakas
					where ytunnus='$laskurow[ytunnus]' and yhtio='$kukarow[yhtio]' and toim_ovttunnus='$laskurow[toim_ovttunnus]'";
		$myyresult = mysql_query($query) or pupe_error($query);
		$asiakmyyrow = mysql_fetch_array($myyresult);
	
		$firstpage = $pdf->new_page("a4");
		$pdf->enable('template');
		$tid = $pdf->template->create();
		$pdf->template->size($tid, 600, 830);
	
		//Otsikko
		//$pdf->draw_rectangle(830, 20,  800, 580, $firstpage, $rectparam);
		$pdf->draw_text(30, 815,  $laskurow["nimi"], $firstpage);
		$pdf->draw_text(310, 815, "Lasku", $firstpage);
		$pdf->draw_text(430, 815, "Sivu ".$sivu, $firstpage, $norm);
	
		//Vasen sarake
		//$pdf->draw_rectangle(737, 20,  674, 300, $firstpage, $rectparam);
		$pdf->draw_text(50, 729, t("Laskutusosoite", $kieli), 	$firstpage, $pieni);
		$pdf->draw_text(50, 717, $laskurow["toim_nimi"], 			$firstpage, $norm);
		$pdf->draw_text(50, 707, $laskurow["toim_nimitark"],			$firstpage, $norm);
		$pdf->draw_text(50, 697, $laskurow["toim_osoite"], 			$firstpage, $norm);
		$pdf->draw_text(50, 687, $laskurow["toim_postino"]." ".$laskurow["toim_postitp"], $firstpage, $norm);
		$pdf->draw_text(50, 677, $laskurow["toim_maa"], 				$firstpage, $norm);
	
		//$pdf->draw_rectangle(674, 20,  611, 300, $firstpage, $rectparam);
		$pdf->draw_text(50, 656, t("Toimitusosoite", $kieli), 	$firstpage, $pieni);
		$pdf->draw_text(50, 644, $laskurow["toim_nimi"], 		$firstpage, $norm);
		$pdf->draw_text(50, 634, $laskurow["toim_nimitark"], 	$firstpage, $norm);
		$pdf->draw_text(50, 624, $laskurow["toim_osoite"],	 	$firstpage, $norm);
		$pdf->draw_text(50, 614, $laskurow["toim_postino"]." ".$laskurow["toim_postitp"], $firstpage, $norm);
		$pdf->draw_text(50, 604, $laskurow["toim_maa"], 		$firstpage, $norm);
		
		//Oikea sarake
		$pdf->draw_rectangle(800, 300, 779, 580, 	$firstpage, $rectparam);
		$pdf->draw_rectangle(800, 420, 779, 580, 	$firstpage, $rectparam);
		$pdf->draw_text(310, 792, "Laskun pvm", 	$firstpage, $pieni);
		$pdf->draw_text(310, 782, "$kervv-$kerkk-$kerpp",	$firstpage, $norm);
		$pdf->draw_text(430, 792, "Laskunumero", 	$firstpage, $pieni);
		$pdf->draw_text(430, 782, $laskurow["tunnus"], 		$firstpage, $norm);

		$pdf->draw_rectangle(779, 300, 758, 580, $firstpage, $rectparam);
		$pdf->draw_rectangle(779, 420, 758, 580, $firstpage, $rectparam);
		$pdf->draw_text(310, 771, "Asiakasnumero", 				$firstpage, $pieni);
		$pdf->draw_text(310, 761, $asiakmyyrow["asiakasnro"], 	$firstpage, $norm);
		$pdf->draw_text(430, 771, "Valuutta", 					$firstpage, $pieni);
		$pdf->draw_text(430, 761, $laskurow["valkoodi"], 		$firstpage, $norm);

		$pdf->draw_rectangle(758, 300, 737, 580, $firstpage, $rectparam);
		$pdf->draw_rectangle(758, 420, 737, 580, $firstpage, $rectparam);
		$pdf->draw_text(310, 750, "Toimitustapa", $firstpage, $pieni);
		$pdf->draw_text(310, 740, $laskurow["toimitustapa"], $firstpage, $norm);
		$pdf->draw_text(430, 750, "Toimituspvm", $firstpage, $pieni);
		$pdf->draw_text(430, 740, $laskurow["toimaika"], $firstpage, $norm);
		
		$pdf->draw_rectangle(737, 300, 716, 580, $firstpage, $rectparam);
		$pdf->draw_rectangle(737, 420, 716, 580, $firstpage, $rectparam);
		$pdf->draw_text(310, 729, "Myyjä", 		$firstpage, $pieni);
		$pdf->draw_text(310, 719, $myyrow["nimi"],	 	$firstpage, $norm);
//		$pdf->draw_text(430, 729, t("Kassa-alennus pvm", $kieli), 	$firstpage, $pieni);
//		$pdf->draw_text(430, 719, $laskurow["kapvm"], $firstpage, $norm);
		
		$pdf->draw_rectangle(716, 300, 695, 580, $firstpage, $rectparam);
		$pdf->draw_rectangle(716, 420, 695, 580, $firstpage, $rectparam);
		$pdf->draw_text(310, 708, "Maksuehto", 		$firstpage, $pieni);
		$pdf->draw_text(310, 698, "45 pv netto", 		$firstpage, $norm);
		$pdf->draw_text(430, 708, "Eräpäivä", 	$firstpage, $pieni);
		$pdf->draw_text(430, 698, date("Y-m-d",mktime(0, 0, 0, $kerkk, $kerpp+45, $kervv)), $firstpage, $norm);
	
		$pdf->draw_rectangle(695, 300, 674, 580, $firstpage, $rectparam);
		$pdf->draw_rectangle(695, 420, 674, 580, $firstpage, $rectparam);
//		$pdf->draw_text(310, 687, t("Asiakasnumero", $kieli), 	$firstpage, $pieni);
//		$pdf->draw_text(310, 677, $asiakmyyrow["asiakasnro"], 	$firstpage, $norm);
//		$pdf->draw_text(430, 687, t("Asiakasnumero", $kieli), 		$firstpage, $pieni);
//		$pdf->draw_text(430, 677, $laskurow["asiakasnro"], 			$firstpage, $norm);
	
		$pdf->draw_rectangle(674, 300, 653, 580, $firstpage, $rectparam);
		$pdf->draw_rectangle(674, 420, 653, 580, $firstpage, $rectparam);
//		$pdf->draw_text(310, 666, t("Toimitustapa", $kieli), 	$firstpage, $pieni);
//		$pdf->draw_text(310, 656, $laskurow["toimitustapa"], 	$firstpage, $norm);
//		$pdf->draw_text(430, 666, t("Toimituspvm", $kieli), 	$firstpage, $pieni);
//		$pdf->draw_text(430, 656, $laskurow["toimaika"], 		$firstpage, $norm);
	
		$pdf->draw_rectangle(653, 300, 632, 580, $firstpage, $rectparam);
		$pdf->draw_text(310, 645, t("Toimitusehto", $kieli), $firstpage, $pieni);
		//$pdf->draw_text(310, 635, $laskurow["toimitusehto"], $firstpage, $norm);
	
		$pdf->draw_rectangle(632, 300, 611, 580, $firstpage, $rectparam);
		$pdf->draw_text(310, 624, t("Lisätietoja", $kieli), 	$firstpage, $pieni);
		$pdf->draw_text(310, 614, $laskurow["viesti"], 			$firstpage, $norm);
	
		$pdf->draw_text(280, 595, t("Mahdolliset muistutukset tehtävä 8 päivän kuluessa.", $kieli). " Viivästyskorko sopimuksen mukaan.", $firstpage, $pieni);
	
		//Laskurivien otsikkotiedot
		//eka rivi
		$pdf->draw_text(35,  572, t("Tuotenimi", $kieli),		$firstpage, $pieni);
		$pdf->draw_text(190, 572, t("Tuotenumero", $kieli),		$firstpage, $pieni);
		$pdf->draw_text(290, 572, t("Määrä", $kieli),			$firstpage, $pieni);
		$pdf->draw_text(350, 572, t("A-hinta", $kieli),			$firstpage, $pieni);
		$pdf->draw_text(410, 572, t("Alennus", $kieli)."-%",	$firstpage, $pieni);
		$pdf->draw_text(470, 572, t("Veroton arvo", $kieli),	$firstpage, $pieni);
		$pdf->draw_text(530, 572, t("Alv", $kieli)."-%",		$firstpage, $pieni);
		//toka rivi 
		$pdf->draw_text(35,  563, t("Tilausnumero", $kieli)."/".t("Lisätietoja", $kieli),		$firstpage, $pieni);
		$pdf->draw_text(190, 563, "",							$firstpage, $pieni);
		$pdf->draw_text(290, 563, t("Yksikkö", $kieli),			$firstpage, $pieni);
		$pdf->draw_text(350, 563, t("Toimituspvm", $kieli),		$firstpage, $pieni);
		$pdf->draw_text(410, 563, "",							$firstpage, $pieni);
		$pdf->draw_text(470, 563, t("Verollinen arvo", $kieli),	$firstpage, $pieni);
	
		return($firstpage);
	}
}

if (!function_exists('rivi')) {
	function rivi ($firstpage) {
		global $firstpage, $pdf, $row, $kala, $sivu, $lask, $rectparam, $norm, $pieni, $lask, $kieli;
	
		if ($lask == 16) {
			$sivu++;
			loppu($firstpage,1);
			$firstpage = alku();
			$kala = 540;
			$lask = 1;
		}

		$verotonhinta = round($row["rivihinta"]/(1+$row["alv"]/100),2);
		$verotonhinta = sprintf('%.2f',$verotonhinta);

		$apukpl = $row["varattu"] + $row["kpl"];
		$pdf->draw_text(35,  $kala, substr($row["nimitys"],0,22),	$firstpage, $norm);
		$pdf->draw_text(190, $kala, $row["tuoteno"], 	$firstpage, $norm);
		$pdf->draw_text(290, $kala, $apukpl, 			$firstpage, $norm);
		$pdf->draw_text(350, $kala, $row["hinta"], 		$firstpage, $norm);
		$pdf->draw_text(410, $kala, $row["ale"],		$firstpage, $norm);
		$pdf->draw_text(470, $kala, $verotonhinta, 		$firstpage, $norm);
		$pdf->draw_text(530, $kala, $row["alv"], 		$firstpage, $norm);
		$kala = $kala - 10;
		
		if ($row["kommentti"] != ''){
			$pdf->draw_text(35,  $kala, $row["otunnus"]."/".$row["kommentti"],	$firstpage, $pieni);
		}
		else {
			$pdf->draw_text(35,  $kala, $row["otunnus"],	$firstpage, $pieni);
		}
		
		$pdf->draw_text(290, $kala, $row["yksikko"],	$firstpage, $pieni);	
		$pdf->draw_text(350, $kala, $row["toimaika"], 	$firstpage, $pieni);
			
		$pdf->draw_text(470, $kala, $row["rivihinta"], $firstpage, $pieni);
		
		$kala = $kala - 15;
		$lask++;
		
		return($firstpage);
	}
}

if (!function_exists('loppu')) {
	function loppu ($firstpage, $tots) {

		global $pdf, $laskurow, $yhtiorow, $kukarow, $sivu, $rectparam, $norm, $pieni, $kieli, $masrow, $total;
			
		//yhteensärivi
		$pdf->draw_rectangle(110, 20, 90, 580,	$firstpage, $rectparam);
		$pdf->draw_rectangle(110, 207, 90, 580,	$firstpage, $rectparam);
		$pdf->draw_rectangle(110, 394, 90, 580,	$firstpage, $rectparam);
		$pdf->draw_rectangle(110, 540, 90, 580,	$firstpage, $rectparam);
	
//		$pdf->draw_text(30,  102, t("Eräpäivä", $kieli),	$firstpage, $pieni);
//		$pdf->draw_text(30,  92,  $laskurow["erpcm"],		$firstpage, $norm);
//		$pdf->draw_text(217, 102, t("Viitenumero", $kieli),	$firstpage, $pieni);
//		$pdf->draw_text(217, 92,  $laskurow["viite"],		$firstpage, $norm);
		$pdf->draw_text(404, 92,  t("Yhteensä", $kieli).":",$firstpage, $norm);
		$pdf->draw_text(464, 92,  sprintf('%.2f', $total),					$firstpage, $norm);
		$pdf->draw_text(550, 92,  $laskurow["valkoodi"],	$firstpage, $norm);
	
		//Pankkiyhteystiedot
		$pdf->draw_rectangle(90, 20, 20, 580,	$firstpage, $rectparam);
	    
//		    $pdf->draw_text(30, 82,  t("Pankkiyhteys", $kieli),	$firstpage, $pieni);
//	       	$pdf->draw_text(30, 72,  $yhtiorow["pankkinimi1"],	$firstpage, $norm);
//	       	$pdf->draw_text(80, 72,  $yhtiorow["pankkitili1"],	$firstpage, $norm);
//	       	$pdf->draw_text(217, 72, $yhtiorow["pankkinimi2"],	$firstpage, $norm);
//	       	$pdf->draw_text(257, 72, $yhtiorow["pankkitili2"],	$firstpage, $norm);
//	       	$pdf->draw_text(404, 72, $yhtiorow["pankkinimi3"],	$firstpage, $norm);
//	       	$pdf->draw_text(444, 72, $yhtiorow["pankkitili3"],	$firstpage, $norm);
	
		//Alimmat kolme laatikkoa, yhtiötietoja
		$pdf->draw_rectangle(70, 20, 20, 580,	$firstpage, $rectparam);
		$pdf->draw_rectangle(70, 207, 20, 580,	$firstpage, $rectparam);
		$pdf->draw_rectangle(70, 394, 20, 580,	$firstpage, $rectparam);
	
		$pdf->draw_text(30, 55, "Myyjä: ".$laskurow["nimi"],		$firstpage, $pieni);
		$pdf->draw_text(30, 45, "Ytunnus: ".$laskurow["ytunnus"],	$firstpage, $pieni);
		$pdf->draw_text(30, 35, $laskurow["osoite"],				$firstpage, $pieni);
		$pdf->draw_text(30, 25, $laskurow["postino"]." ".$laskurow["postitp"],		$firstpage, $pieni);

		$pdf->draw_text(217, 55, "Nordea: 157130-15058",			$firstpage, $pieni);
		$pdf->draw_text(217, 45, "OKO: 500001-2141469",				$firstpage, $pieni);
		$pdf->draw_text(217, 35, "Aktia: 405511-212384",			$firstpage, $pieni);
		$pdf->draw_text(217, 25, "Sampo: 800010-86901",				$firstpage, $pieni);
	
		$pdf->draw_text(404, 55, "Toimittaja: $yhtiorow[nimi]",		$firstpage, $pieni);
		$pdf->draw_text(404, 45, "Puh: $yhtiorow[puhelin]",			$firstpage, $pieni);
		$pdf->draw_text(404, 35, "Fax: $yhtiorow[fax]",				$firstpage, $pieni);
		$pdf->draw_text(404, 25, "Email: $yhtiorow[email]",			$firstpage, $pieni);
	}
}

if (!function_exists('alvierittely')) {
	function alvierittely ($firstpage, $kala) {
		
		global $pdf, $laskurow, $yhtiorow, $kukarow, $sivu, $rectparam, $norm, $pieni, $lask, $kieli, $total;
	
		if ($lask >= 29) {
			$sivu++;
			loppu($firstpage, 1);
			$firstpage = alku();
			$kala = 540;
			$lask = 1;
			$loppumyos = 1;
		}
		
//		$kala -=10;
		//loppusummat vain vikalle sivulle

//		$pdf->draw_text(360, $kala, t("Veroton arvo yhteensä", $kieli).":", 	$firstpage, $norm);
//		$pdf->draw_text(470, $kala, $laskurow["arvo"], 			$firstpage, $norm);
//		$pdf->draw_text(530, $kala, $laskurow["valkoodi"], 		$firstpage, $norm);
		
		$alvquery = "	SELECT distinct alv
						FROM tilausrivi
						WHERE otunnus = '$laskurow[tunnus]' and yhtio='$kukarow[yhtio]'
						ORDER BY alv";
		$alvresult = mysql_query($alvquery) or pupe_error($alvquery);
		$kala -= 10;

		while ($alvrow = mysql_fetch_array($alvresult)) {
			$aquery = "	SELECT round(sum((varattu+kpl)*hinta*(1-ale/100)/(1+alv/100)),2) veroton, round(sum((kpl+varattu)*hinta*(1-ale/100)),2) verollinen
						FROM tilausrivi
						WHERE otunnus = '$laskurow[tunnus]' and yhtio='$kukarow[yhtio]' and alv='$alvrow[alv]' and tyyppi='L' and var in ('','H')
						ORDER BY alv";
			$aresult = mysql_query($aquery) or pupe_error($aquery);
			$arow = mysql_fetch_array($aresult);

			$alvihinta = $arow["verollinen"] - $arow["veroton"];
			$pdf->draw_text(360, $kala, t("Arvonlisävero", $kieli)." ($arow[veroton]): ",	$firstpage, $norm);
			$pdf->draw_text(470, $kala, $alvihinta, $firstpage, $norm);
			$pdf->draw_text(530, $kala, $alvrow["alv"]."%", $firstpage, $norm);
			$kala -= 10;
		}

		$pdf->draw_text(360, $kala, t("Lasku Yhteensä", $kieli).":",	$firstpage, $norm);
		$pdf->draw_text(470, $kala, sprintf('%.2f', $arow["verollinen"]),$firstpage, $norm);
		$pdf->draw_text(530, $kala, $laskurow["valkoodi"], 				$firstpage, $norm);
		$kala -= 10;
		
		if ($loppumyos == 1) {
			loppu($firstpage,1);
		}		
	}
}

if (!function_exists('print_pdf')) {
	function print_pdf ($komento) {

		global $tee, $kala, $firstpage, $kateinen, $returnvalue, $pdf, $laskurow, $yhtiorow, $kukarow, $sivu, $rectparam, $norm, $pieni, $lask, $kieli, $total;
		
		alvierittely($firstpage, $kala);
	
		$oslapp='';
		$returnvalue=0;
	
		//keksitään uudelle failille joku varmasti uniikki nimi:
		list($usec, $sec) = explode(' ', microtime());
		mt_srand((float) $sec + ((float) $usec * 100000));
		$pdffilenimi = "/tmp/Lahete-".md5(uniqid(mt_rand(), true)).".pdf";
	
		//kirjoitetaan pdf faili levylle..
		$fh = fopen($pdffilenimi, "w");
		if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF create error $pdffilenimi");
		fclose($fh);			
	
		// itse print komento...
		if ($komento == 'email') {
			$liite = $pdffilenimi;
			$kutsu = "Lahete";

			echo t("Lähete tulostuu")."...<br>";
			
			if ($kukarow["extranet"] == '') {
				require("../inc/sahkoposti.inc");
			}
			else {
				require("sahkoposti.inc");
			}
		}
		elseif ($tee == 'NAYTATILAUS') {
			//Työnnetään tuo pdf vaan putkeen!
			echo file_get_contents($pdffilenimi);
		}		
		elseif ($komento != '' and $komento != 'edi') {
			echo t("Lähete tulostuu")."...<br>";
			$line = exec("$komento $pdffilenimi", $output, $returnvalue);
		}
		
		//poistetaan tmp file samantien kuleksimasta...
		system("rm -f $pdffilenimi");
	}
}

?>