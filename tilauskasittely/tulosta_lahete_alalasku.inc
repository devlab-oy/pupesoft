<?php

//viivakoodien tulostamista varten
define (__TRACE_ENABLED__, false);
define (__DEBUG_ENABLED__, false);

require_once("barcode/barcode.php");
require_once("barcode/c39object.php");
require_once("pdflib/phppdflib.class.php");

$rectparam["width"] = 0.3;
$norm["height"] 	= 10;
$norm["font"] 		= "Times-Roman";
$pieni["height"] 	= 8;
$pieni["font"] 		= "Times-Roman";

// laskun alku ja loppu functiot...
if (!function_exists('alku')) {
	function alku () {
		global $pdf, $laskurow, $yhtiorow, $kukarow, $sivu, $rectparam, $norm, $pieni, $maksuehto, $kieli, $kala, $lask;

		//PDF parametrit
		if (!isset($pdf)) {
			$pdf = new pdffile;
			$pdf->set_default('margin-top', 	0);
			$pdf->set_default('margin-bottom', 	0);
			$pdf->set_default('margin-left', 	0);
			$pdf->set_default('margin-right', 	0);
		}
		
		// defaultteja
		$kala = 540;
		$lask = 1;

		$thispage = $pdf->new_page("a4");
		
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
	
		//Otsikko
		//$pdf->draw_rectangle(830, 20,  800, 580, $thispage, $rectparam);
		$pdf->draw_text(30, 815,  $laskurow["nimi"], $thispage);
		$pdf->draw_text(310, 815, "Lasku", $thispage);
		$pdf->draw_text(430, 815, "Sivu ".$sivu, $thispage, $norm);
	
		//Vasen sarake
		//$pdf->draw_rectangle(737, 20,  674, 300, $thispage, $rectparam);
		$pdf->draw_text(50, 729, t("Laskutusosoite", $kieli), 	$thispage, $pieni);
		$pdf->draw_text(50, 717, $laskurow["toim_nimi"], 			$thispage, $norm);
		$pdf->draw_text(50, 707, $laskurow["toim_nimitark"],			$thispage, $norm);
		$pdf->draw_text(50, 697, $laskurow["toim_osoite"], 			$thispage, $norm);
		$pdf->draw_text(50, 687, $laskurow["toim_postino"]." ".$laskurow["toim_postitp"], $thispage, $norm);
		$pdf->draw_text(50, 677, $laskurow["toim_maa"], 				$thispage, $norm);
	
		//$pdf->draw_rectangle(674, 20,  611, 300, $thispage, $rectparam);
		$pdf->draw_text(50, 656, t("Toimitusosoite", $kieli), 	$thispage, $pieni);
		$pdf->draw_text(50, 644, $laskurow["toim_nimi"], 		$thispage, $norm);
		$pdf->draw_text(50, 634, $laskurow["toim_nimitark"], 	$thispage, $norm);
		$pdf->draw_text(50, 624, $laskurow["toim_osoite"],	 	$thispage, $norm);
		$pdf->draw_text(50, 614, $laskurow["toim_postino"]." ".$laskurow["toim_postitp"], $thispage, $norm);
		$pdf->draw_text(50, 604, $laskurow["toim_maa"], 		$thispage, $norm);
		
		//Oikea sarake
		$pdf->draw_rectangle(800, 300, 779, 580, 	$thispage, $rectparam);
		$pdf->draw_rectangle(800, 420, 779, 580, 	$thispage, $rectparam);
		$pdf->draw_text(310, 792, "Laskun pvm", 	$thispage, $pieni);
		$pdf->draw_text(310, 782, "$kervv-$kerkk-$kerpp",	$thispage, $norm);
		$pdf->draw_text(430, 792, "Laskunumero", 	$thispage, $pieni);
		$pdf->draw_text(430, 782, $laskurow["tunnus"], 		$thispage, $norm);

		$pdf->draw_rectangle(779, 300, 758, 580, $thispage, $rectparam);
		$pdf->draw_rectangle(779, 420, 758, 580, $thispage, $rectparam);
		$pdf->draw_text(310, 771, "Asiakasnumero", 				$thispage, $pieni);
		$pdf->draw_text(310, 761, $asiakmyyrow["asiakasnro"], 	$thispage, $norm);
		$pdf->draw_text(430, 771, "Valuutta", 					$thispage, $pieni);
		$pdf->draw_text(430, 761, $laskurow["valkoodi"], 		$thispage, $norm);

		$pdf->draw_rectangle(758, 300, 737, 580, $thispage, $rectparam);
		$pdf->draw_rectangle(758, 420, 737, 580, $thispage, $rectparam);
		$pdf->draw_text(310, 750, "Toimitustapa", $thispage, $pieni);
		$pdf->draw_text(310, 740, $laskurow["toimitustapa"], $thispage, $norm);
		$pdf->draw_text(430, 750, "Toimituspvm", $thispage, $pieni);
		$pdf->draw_text(430, 740, $laskurow["toimaika"], $thispage, $norm);
		
		$pdf->draw_rectangle(737, 300, 716, 580, $thispage, $rectparam);
		$pdf->draw_rectangle(737, 420, 716, 580, $thispage, $rectparam);
		$pdf->draw_text(310, 729, "Myyjä", 		$thispage, $pieni);
		$pdf->draw_text(310, 719, $myyrow["nimi"],	 	$thispage, $norm);
		
		$pdf->draw_rectangle(716, 300, 695, 580, $thispage, $rectparam);
		$pdf->draw_rectangle(716, 420, 695, 580, $thispage, $rectparam);
		$pdf->draw_text(310, 708, "Maksuehto", 		$thispage, $pieni);
		$pdf->draw_text(310, 698, "45 pv netto", 		$thispage, $norm);
		$pdf->draw_text(430, 708, "Eräpäivä", 	$thispage, $pieni);
		$pdf->draw_text(430, 698, date("Y-m-d",mktime(0, 0, 0, $kerkk, $kerpp+45, $kervv)), $thispage, $norm);
	
		$pdf->draw_rectangle(695, 300, 674, 580, $thispage, $rectparam);
		$pdf->draw_rectangle(695, 420, 674, 580, $thispage, $rectparam);

	
		$pdf->draw_rectangle(674, 300, 653, 580, $thispage, $rectparam);
		$pdf->draw_rectangle(674, 420, 653, 580, $thispage, $rectparam);
	
		$pdf->draw_rectangle(653, 300, 632, 580, $thispage, $rectparam);
		$pdf->draw_text(310, 645, t("Toimitusehto", $kieli), $thispage, $pieni);
		$pdf->draw_text(310, 635, $laskurow["toimitusehto"], 			$thispage, $norm);
	
		$pdf->draw_rectangle(632, 300, 611, 580, $thispage, $rectparam);
		$pdf->draw_text(310, 624, t("Lisätietoja", $kieli), 	$thispage, $pieni);
		$pdf->draw_text(310, 614, $laskurow["viesti"], 			$thispage, $norm);
	
		$pdf->draw_text(280, 595, t("Mahdolliset muistutukset tehtävä 8 päivän kuluessa.", $kieli). " Viivästyskorko sopimuksen mukaan.", $thispage, $pieni);
	
		//Laskurivien otsikkotiedot
		//eka rivi
		$pdf->draw_text(35,  572, t("Tuotenimi", $kieli),		$thispage, $pieni);
		$pdf->draw_text(190, 572, t("Tuotenumero", $kieli),		$thispage, $pieni);
		$pdf->draw_text(290, 572, t("Määrä", $kieli),			$thispage, $pieni);
		$pdf->draw_text(350, 572, t("A-hinta", $kieli),			$thispage, $pieni);
		$pdf->draw_text(410, 572, t("Alennus", $kieli)."-%",	$thispage, $pieni);
		$pdf->draw_text(470, 572, t("Veroton arvo", $kieli),	$thispage, $pieni);
		$pdf->draw_text(530, 572, t("Alv", $kieli)."-%",		$thispage, $pieni);
		//toka rivi 
		$pdf->draw_text(35,  563, t("Tilausnumero", $kieli)."/".t("Lisätietoja", $kieli),		$thispage, $pieni);
		$pdf->draw_text(190, 563, "",							$thispage, $pieni);
		$pdf->draw_text(290, 563, t("Yksikkö", $kieli),			$thispage, $pieni);
		$pdf->draw_text(350, 563, t("Toimituspvm", $kieli),		$thispage, $pieni);
		$pdf->draw_text(410, 563, "",							$thispage, $pieni);
		$pdf->draw_text(470, 563, t("Verollinen arvo", $kieli),	$thispage, $pieni);
	
		return($thispage);
	}
}

if (!function_exists('rivi')) {
	function rivi ($thispage) {
		global $pdf, $page, $row, $kala, $sivu, $lask, $rectparam, $norm, $pieni, $lask, $kieli;
	
		if ($lask == 16) {
			$sivu++;
			loppu($thispage, 0);

			$page[$sivu] = alku();
			$thispage    = $page[$sivu];
		}

		$verotonhinta = round($row["rivihinta"]/(1+$row["alv"]/100),2);
		$verotonhinta = sprintf('%.2f',$verotonhinta);

		$apukpl = $row["varattu"] + $row["kpl"];
		$pdf->draw_text(35,  $kala, substr($row["nimitys"],0,22),	$thispage, $norm);
		$pdf->draw_text(190, $kala, $row["tuoteno"], 	$thispage, $norm);
		$pdf->draw_text(290, $kala, $apukpl, 			$thispage, $norm);
		$pdf->draw_text(350, $kala, $row["hinta"], 		$thispage, $norm);
		$pdf->draw_text(410, $kala, $row["ale"],		$thispage, $norm);
		$pdf->draw_text(470, $kala, $verotonhinta, 		$thispage, $norm);
		$pdf->draw_text(530, $kala, $row["alv"], 		$thispage, $norm);
		$kala = $kala - 10;
		
		if ($row["kommentti"] != ''){
			$pdf->draw_text(35,  $kala, $row["otunnus"]."/".$row["kommentti"],	$thispage, $pieni);
		}
		else {
			$pdf->draw_text(35,  $kala, $row["otunnus"],	$thispage, $pieni);
		}
		
		$pdf->draw_text(290, $kala, $row["yksikko"],	$thispage, $pieni);	
		$pdf->draw_text(350, $kala, $row["toimaika"], 	$thispage, $pieni);
			
		$pdf->draw_text(470, $kala, $row["rivihinta"], $thispage, $pieni);
		
		$kala = $kala - 15;
		$lask++;		
	}
}

if (!function_exists('loppu')) {
	function loppu ($thispage, $tots) {

		global $pdf, $laskurow, $yhtiorow, $kukarow, $sivu, $rectparam, $norm, $pieni, $kieli, $masrow, $total;
			
		//yhteensärivi
		$pdf->draw_rectangle(110, 20, 90, 580,	$thispage, $rectparam);
		$pdf->draw_rectangle(110, 207, 90, 580,	$thispage, $rectparam);
		$pdf->draw_rectangle(110, 394, 90, 580,	$thispage, $rectparam);
		$pdf->draw_rectangle(110, 540, 90, 580,	$thispage, $rectparam);

		$pdf->draw_text(404, 92,  t("Yhteensä", $kieli).":",	$thispage, $norm);
		$pdf->draw_text(464, 92,  sprintf('%.2f', $total),		$thispage, $norm);
		$pdf->draw_text(550, 92,  $laskurow["valkoodi"],		$thispage, $norm);
	
		//Pankkiyhteystiedot
		$pdf->draw_rectangle(90, 20, 20, 580,	$thispage, $rectparam);
	
		//Alimmat kolme laatikkoa, yhtiötietoja
		$pdf->draw_rectangle(70, 20, 20, 580,	$thispage, $rectparam);
		$pdf->draw_rectangle(70, 207, 20, 580,	$thispage, $rectparam);
		$pdf->draw_rectangle(70, 394, 20, 580,	$thispage, $rectparam);
	
		$pdf->draw_text(30, 55, "Myyjä: ".$laskurow["nimi"],		$thispage, $pieni);
		$pdf->draw_text(30, 45, "Ytunnus: ".$laskurow["ytunnus"],	$thispage, $pieni);
		$pdf->draw_text(30, 35, $laskurow["osoite"],				$thispage, $pieni);
		$pdf->draw_text(30, 25, $laskurow["postino"]." ".$laskurow["postitp"],		$thispage, $pieni);

		$pdf->draw_text(217, 55, "Nordea: 157130-15058",			$thispage, $pieni);
		$pdf->draw_text(217, 45, "OKO: 500001-2141469",				$thispage, $pieni);
		$pdf->draw_text(217, 35, "Aktia: 405511-212384",			$thispage, $pieni);
		$pdf->draw_text(217, 25, "Sampo: 800010-86901",				$thispage, $pieni);
	
		$pdf->draw_text(404, 55, "Toimittaja: $yhtiorow[nimi]",		$thispage, $pieni);
		$pdf->draw_text(404, 45, "Puh: $yhtiorow[puhelin]",			$thispage, $pieni);
		$pdf->draw_text(404, 35, "Fax: $yhtiorow[fax]",				$thispage, $pieni);
		$pdf->draw_text(404, 25, "Email: $yhtiorow[email]",			$thispage, $pieni);
	}
}

if (!function_exists('alvierittely')) {
	function alvierittely ($thispage) {
		global $pdf, $page, $kala, $laskurow, $yhtiorow, $kukarow, $sivu, $rectparam, $norm, $pieni, $lask, $kieli, $total;
		
		if ($lask >= 29) {
			$sivu++;
			
			loppu($thispage, 0);
			
			$page[$sivu] = alku();
			$thispage    = $page[$sivu];
			
			$loppumyos = 1;
		}

		//Haetaan kaikki alvikannat riveiltä
		$alvquery = "	SELECT distinct alv
						FROM tilausrivi
						WHERE otunnus = '$laskurow[tunnus]' 
						and yhtio='$kukarow[yhtio]'
						and tyyppi='L'
						ORDER BY alv";
		$alvresult = mysql_query($alvquery) or pupe_error($alvquery);
		$kala -= 20;
		$lsum  = 0;
		
		while ($alvrow = mysql_fetch_array($alvresult)) {
			if ($alvrow["alv"] >= 500) {
				$aquery = "	SELECT round(0) alvrivihinta, round(sum(hinta*varattu*(1-ale/100)),2) rivihinta
							FROM tilausrivi
							WHERE otunnus = '$laskurow[tunnus]' and yhtio='$kukarow[yhtio]' and alv='$alvrow[alv]' and tyyppi='L'";
			}
			else {
				$aquery = "	SELECT
							round(sum(hinta / if('$yhtiorow[alv_kasittely]' = '', (1+alv/100), 1) * varattu * if(netto='N', (1-ale/100), (1-(ale+$laskurow[erikoisale]-(ale*$laskurow[erikoisale]/100))/100)) * (alv/100)),2) alvrivihinta,
							round(sum(hinta / if('$yhtiorow[alv_kasittely]' = '', (1+alv/100), 1) * varattu * if(netto='N', (1-ale/100), (1-(ale+$laskurow[erikoisale]-(ale*$laskurow[erikoisale]/100))/100))),2) rivihinta
							FROM tilausrivi
							WHERE otunnus = '$laskurow[tunnus]' and yhtio='$kukarow[yhtio]' and alv='$alvrow[alv]' and tyyppi='L'";
			}
			$aresult = mysql_query($aquery) or pupe_error($aquery);
			$arow = mysql_fetch_array($aresult);

			$pdf->draw_text(360, $kala, t("Arvonlisävero", $kieli)." ($arow[rivihinta]): ",	$thispage, $norm);
			$pdf->draw_text(470, $kala, $arow["alvrivihinta"],								$thispage, $norm);
			
			if ($alvrow["alv"] >= 500) {
				$pdf->draw_text(550, $kala, t("M.V."), 											$thispage, $norm);
			}
			else {
				$pdf->draw_text(550, $kala, ($alvrow["alv"]*1)."%", 							$thispage, $norm);
			}
			
			$lsum += $arow["rivihinta"];
			$kala -= 10;
		}

		$pdf->draw_text(360, $kala, t("Lasku Yhteensä", $kieli).":",		$thispage, $norm);
		$pdf->draw_text(470, $kala, sprintf('%.2f', $lsum),					$thispage, $norm);
		$pdf->draw_text(550, $kala, $laskurow["valkoodi"], 					$thispage, $norm);
		$kala -= 10;
		
		if ($loppumyos == 1) {
			loppu($thispage, 0);
		}		
	}
}

if (!function_exists('print_pdf')) {
	function print_pdf ($komento) {

		global $tee, $pdf, $laskurow, $yhtiorow, $kukarow, $kieli;
		
		$oslapp = '';
		$returnvalue = 0;
	
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