<?php

require_once('../pdflib/phppdflib.class.php');

$rectparam["width"] = 0.3;

$norm["height"] = 10;
$norm["font"] 	= "Times-Roman";

$pieni["height"] 	= 8;
$pieni["font"] 		= "Times-Roman";

$boldi["height"] 	= 10;
$boldi["font"] 		= "Times-Bold";

$pieni_boldi["height"] 	= 8;
$pieni_boldi["font"] 	= "Times-Bold";

// defaultteja
$sivu 	= 1;
$summa 	= 0;
$arvo 	= 0;

// laskun alku ja loppu funktiot...
if (!function_exists('alku')) {
	function alku () {
		global $yhtiorow, $kukarow, $toim, $pdf, $laskurow, $sivu, $rectparam, $norm, $pieni, $maksuehto, $kieli, $boldi, $kala;

		//PDF parametrit
		if (!isset($pdf)) {
			$pdf = new pdffile;
			$pdf->set_default('margin-top', 	0);
			$pdf->set_default('margin-bottom', 	0);
			$pdf->set_default('margin-left', 	0);
			$pdf->set_default('margin-right', 	0);
		}

		$firstpage = $pdf->new_page("a4");

		if (trim($yhtiorow["lasku_logo"]) != '' and file_exists($yhtiorow["lasku_logo"])) {
			$filename = $yhtiorow["lasku_logo"];

			$fh = fopen($filename, "r");
			$data = fread($fh, filesize($filename));
			fclose($fh);

			$image = $pdf->jfif_embed($data);

			if(!$image) {
				echo t("Logokuvavirhe").": ".$image.$data;
			}
			else {
				$logoparam = array();
				$logoparam['scale'] = 0.15;
				$placement = $pdf->image_place($image, mm_pt(280), mm_pt(10), $firstpage, $logoparam);
			}
		}
		else {
			$pdf->draw_text(30, 815,  $yhtiorow["nimi"], $firstpage);
		}

		//Otsikko
		if ($laskurow["vienti"] == "") {
			if ($laskurow["summa"] >= 0) {
				$pdf->draw_text(310, 815, t("Lasku", $kieli), $firstpage);
			}
			else {
				$pdf->draw_text(310, 815, t("Hyvityslasku", $kieli), $firstpage);
			}
		}
		else {
			if ($toim == 'PROFORMA') {
				$pdf->draw_text(310, 815, t("Proformalasku", $kieli), $firstpage);
			}
			elseif ($kateiskuitti != ""){
				$pdf->draw_text(310, 815, t("Käteiskuitti", $kieli),  $firstpage);
			}
			elseif ($laskurow["summa"] >= 0) {
				$pdf->draw_text(310, 815, t("Vientilasku", $kieli), $firstpage);
			}
			else {
				$pdf->draw_text(310, 815, t("Viennin hyvityslasku", $kieli), $firstpage);
			}
		}

		$pdf->draw_text(430, 815, t("Sivu", $kieli)." ".$sivu, $firstpage, $norm);

		//Vasen sarake
		//$pdf->draw_rectangle(737, 20,  674, 300, $firstpage, $rectparam);
		$pdf->draw_text(50, 729, t("Laskutusosoite", $kieli), 	$firstpage, $pieni);
		$pdf->draw_text(50, 717, $laskurow["nimi"], 			$firstpage, $norm);
		$pdf->draw_text(50, 707, $laskurow["nimitark"],			$firstpage, $norm);
		$pdf->draw_text(50, 697, $laskurow["osoite"], 			$firstpage, $norm);
		$pdf->draw_text(50, 687, $laskurow["postino"]." ".$laskurow["postitp"], $firstpage, $norm);
		$pdf->draw_text(50, 677, $laskurow["maa"], 				$firstpage, $norm);


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
		$pdf->draw_text(310, 792, t("Laskun pvm", $kieli), 	$firstpage, $pieni);
		$pdf->draw_text(310, 782, tv1dateconv($laskurow["tapvm"]),		$firstpage, $norm);
		$pdf->draw_text(430, 792, t("Eräpäivä", $kieli), 	$firstpage, $pieni);
		$pdf->draw_text(430, 782, tv1dateconv($laskurow["erpcm"]), 		$firstpage, $norm);

		$pdf->draw_rectangle(779, 300, 758, 580, $firstpage, $rectparam);
		$pdf->draw_rectangle(779, 420, 758, 580, $firstpage, $rectparam);
		$pdf->draw_text(310, 771, t("Viitenumero", $kieli), 	$firstpage, $pieni);
		$pdf->draw_text(310, 761, $laskurow["viite"], 			$firstpage, $norm);
		$pdf->draw_text(430, 771, t("Laskun numero", $kieli), 	$firstpage, $pieni);
		$pdf->draw_text(430, 761, $laskurow["laskunro"], 		$firstpage, $boldi);

		$pdf->draw_rectangle(758, 300, 737, 580, $firstpage, $rectparam);
		$pdf->draw_rectangle(758, 420, 737, 580, $firstpage, $rectparam);
		$pdf->draw_text(310, 750, t("Maksuehto", $kieli), $firstpage, $pieni);
		$pdf->draw_text(310, 740, $maksuehto, $firstpage, $norm);
		$pdf->draw_text(430, 750, t("Kassa-alennus", $kieli)." ".$laskurow["valkoodi"], $firstpage, $pieni);
		
		if ($laskurow["kasumma"] != 0.00) {
			if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
				$pdf->draw_text(430, 740, $laskurow["kasumma_valuutassa"], $firstpage, $norm);
			}
			else {
				$pdf->draw_text(430, 740, $laskurow["kasumma"], $firstpage, $norm);
			}
		}

		$pdf->draw_rectangle(737, 300, 716, 580, $firstpage, $rectparam);
		$pdf->draw_rectangle(737, 420, 716, 580, $firstpage, $rectparam);
		$pdf->draw_text(310, 729, t("Viivästyskorko", $kieli), 		$firstpage, $pieni);
		$pdf->draw_text(310, 719, $laskurow["viikorkopros"],	 	$firstpage, $norm);
		$pdf->draw_text(430, 729, t("Kassa-alennus pvm", $kieli), 	$firstpage, $pieni);

		if ($laskurow["kapvm"] != '0000-00-00') {
			$pdf->draw_text(430, 719, tv1dateconv($laskurow["kapvm"]), $firstpage, $norm);
		}

		$pdf->draw_rectangle(716, 300, 695, 580, $firstpage, $rectparam);
		$pdf->draw_rectangle(716, 420, 695, 580, $firstpage, $rectparam);
		$pdf->draw_text(310, 708, t("Valuutta", $kieli), 		$firstpage, $pieni);
		$pdf->draw_text(310, 698, $laskurow["valkoodi"], 		$firstpage, $norm);
		$pdf->draw_text(430, 708, t("Asiaa hoitaa", $kieli), 	$firstpage, $pieni);

		//etsitään myyjän nimi
		$query  = "	select nimi
					from kuka
					where tunnus='$laskurow[myyja]' and yhtio='$kukarow[yhtio]'";
		$myyresult = mysql_query($query) or pupe_error($query);
		$myyrow = mysql_fetch_array($myyresult);

		$pdf->draw_text(430, 698, $myyrow["nimi"], $firstpage, $norm);

		$pdf->draw_rectangle(695, 300, 674, 580, $firstpage, $rectparam);
		$pdf->draw_rectangle(695, 420, 674, 580, $firstpage, $rectparam);
		$pdf->draw_text(310, 687, t("Asiakkaan Y-tunnus", $kieli), 	$firstpage, $pieni);

		if (substr(trim(strtoupper($laskurow["ytunnus"])),0,2) != strtoupper($laskurow["maa"]) and trim(strtoupper($laskurow["maa"])) != trim(strtoupper($yhtiorow["maakoodi"]))) {
			$laskurow["ytunnus"] = strtoupper($laskurow["maa"])."-".$laskurow["ytunnus"];
		}

		$pdf->draw_text(310, 677, $laskurow["ytunnus"], 			$firstpage, $norm);
		$pdf->draw_text(430, 687, t("Asiakasnumero", $kieli), 		$firstpage, $pieni);
		$pdf->draw_text(430, 677, $laskurow["ytunnus"], 			$firstpage, $norm);

		$pdf->draw_rectangle(674, 300, 653, 580, $firstpage, $rectparam);
		$pdf->draw_rectangle(674, 420, 653, 580, $firstpage, $rectparam);
		$pdf->draw_text(310, 666, t("Toimitustapa", $kieli), 	$firstpage, $pieni);
		$pdf->draw_text(310, 656, $laskurow["toimitustapa"], 	$firstpage, $norm);
		$pdf->draw_text(430, 666, t("Toimituspvm", $kieli), 	$firstpage, $pieni);
		$pdf->draw_text(430, 656, tv1dateconv($laskurow["toimaika"]), 		$firstpage, $norm);

		$pdf->draw_rectangle(653, 300, 632, 580, $firstpage, $rectparam);
		$pdf->draw_text(310, 645, t("Toimitusehto", $kieli), $firstpage, $pieni);
		$pdf->draw_text(310, 635, $laskurow["toimitusehto"], $firstpage, $norm);

		$pdf->draw_rectangle(632, 300, 611, 580, $firstpage, $rectparam);
		$pdf->draw_text(310, 624, t("Lisätietoja", $kieli), 	$firstpage, $pieni);
		$pdf->draw_text(310, 614, $laskurow["viesti"], 			$firstpage, $norm);

		$pdf->draw_text(310, 595, t("Mahdolliset muistutukset tehtävä 8 päivän kuluessa", $kieli), $firstpage, $pieni);

		//Tulostetaan laskun kommentti
		if (trim($laskurow['sisviesti1']) != '') {
			$pdf->draw_text(35, 577, t("Huomautukset", $kieli).":", $firstpage, $norm);

			$kommentit = explode("\n", trim($laskurow['sisviesti1']));

			$pohja = 565;

			foreach($kommentit as $kommentti) {
				$pdf->draw_text(35, $pohja, trim($kommentti), $firstpage, $norm);
				$pohja = $pohja - 10;
			}

			// viivat rivien väliin...
			$x[0] = 35;
			$x[1] = 550;
			$y[0] = $y[1] = $pohja+5;
			$pdf->draw_line($x, $y, $firstpage, $rectparam);

			$kala = $pohja - 35;

		}
		else {
			$pohja = 582;
			$kala  = 540;
		}

		//Laskurivien otsikkotiedot
		//eka rivi

		$pdf->draw_text(35,  $pohja-10, t("Tuotenimi", $kieli),			$firstpage, $pieni);
		$pdf->draw_text(190, $pohja-10, t("Tuotenumero", $kieli),		$firstpage, $pieni);
		$pdf->draw_text(290, $pohja-10, t("Määrä", $kieli),				$firstpage, $pieni);
		$pdf->draw_text(350, $pohja-10, t("A-hinta", $kieli),			$firstpage, $pieni);
		$pdf->draw_text(410, $pohja-10, t("Alennus", $kieli)."-%",		$firstpage, $pieni);
		$pdf->draw_text(470, $pohja-10, t("Veroton arvo", $kieli),		$firstpage, $pieni);
		$pdf->draw_text(530, $pohja-10, t("Alv", $kieli)."-%",			$firstpage, $pieni);
		//toka rivi
		$pdf->draw_text(35,  $pohja-19, t("Tilausnumero", $kieli)."/".t("Lisätietoja", $kieli),		$firstpage, $pieni);
		$pdf->draw_text(190, $pohja-19, "",								$firstpage, $pieni);
		$pdf->draw_text(290, $pohja-19, t("Yksikkö", $kieli),			$firstpage, $pieni);
		$pdf->draw_text(350, $pohja-19, t("Toimituspvm", $kieli),		$firstpage, $pieni);
		$pdf->draw_text(410, $pohja-19, "",								$firstpage, $pieni);
		$pdf->draw_text(470, $pohja-19, t("Verollinen arvo", $kieli),	$firstpage, $pieni);

		return($firstpage);
	}
}

if (!function_exists('rivi')) {
	function rivi ($firstpage) {
		global $yhtiorow, $kukarow, $toim, $firstpage, $pdf, $row, $kala, $sivu, $rectparam, $norm, $summa, $arvo, $pieni, $lask, $kieli, $laskurow;

		if ($kala <= 180) {
			$sivu++;

			loppu($firstpage);

			$firstpage = alku();
		}

		$rivihinta_veroton = 0;
		$rivihinta_verolli = 0;

		if (strtolower($kieli) != strtolower($yhtiorow['kieli'])) {
			$laji = 'nimitys_'.strtolower($kieli);
			$query = "SELECT selite FROM tuotteen_avainsanat WHERE yhtio = '$kukarow[yhtio]' and laji = '$laji' and tuoteno = '$row[tuoteno]' LIMIT 1";
			$nimresult = mysql_query($query) or pupe_error($query);
			
			if (mysql_num_rows($nimresult)>0) {
				$nimrow=mysql_fetch_array($nimresult);
				$row['nimitys']=$nimrow['selite'];
			}
		}

		//Tuoteno
		$pdf->draw_text(35,  $kala, substr($row["nimitys"],0,22),					$firstpage, $norm);

		//Nimitys
		$pdf->draw_text(190, $kala, $row["tuoteno"], 								$firstpage, $norm);

		//Määrä
		$pdf->draw_text(290, $kala, $row["kpl"], 									$firstpage, $norm);

		// A-hinta (voi olla verollinen tai veroton riippuen yhtiöstä)
		if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
			$pdf->draw_text(350, $kala, sprintf('%.2f', laskuval($row["hinta"], $laskurow["vienti_kurssi"])),	$firstpage, $norm);
		}
		else {
			$pdf->draw_text(350, $kala, $row["hinta"], 								$firstpage, $norm);
		}
		
		//Alennus
		$pdf->draw_text(410, $kala, sprintf('%.2f', $ale),							$firstpage, $norm);

		//Rivihinta valuutassa
		if ($toim != "PROFORMA" and $laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
			// Tän rivin alviton rivihinta
			$row["rivihinta"] = laskuval($row["hinta"], $laskurow["vienti_kurssi"])*$row["kpl"]*(1-$row["ale"]/100);

			if ($yhtiorow["alv_kasittely"] == '') {
				$row["rivihinta"] = $row["rivihinta"] / (1+$row["alv"]/100);
			}			
		}
		
		//Rivihinta
		$pdf->draw_text(470, $kala, sprintf('%.2f', $row["rivihinta"]), 			$firstpage, $norm);
		
		//Arvonlisävero
		if ($row["alv"] >= 500) {
			$pdf->draw_text(530, $kala, t("M.V."),	 								$firstpage, $norm);
		}
		else {
			$pdf->draw_text(530, $kala, $row["alv"], 								$firstpage, $norm);
		}

		// Ensimmäinen rivi piirretty
		$kala = $kala - 10;

		//Yksikkö
		$pdf->draw_text(290, $kala, $row["yksikko"],								$firstpage, $pieni);

		//Toimitusaika
		if (substr($row["toimitettuaika"],0,10) != "0000-00-00") {
			$pdf->draw_text(350, $kala, tv1dateconv(substr($row["toimitettuaika"],0,10)), 	$firstpage, $pieni);
		}

		//Verollinen rivihinta
		if ($row["alv"] < 500) {
			$pdf->draw_text(470, $kala, sprintf('%.2f', $row["rivihinta"]*(1+($row["alv"]/100))),	$firstpage, $pieni);
		}

		// Jos ALV no yli 500 niin tiedämme että tämä on marginaaliverotusta
		if ($row["alv"] >= 500) {
			$row["kommentti"] .= "\nEi sisällä vähennettävää veroa.";
		}

		//Hetaan sarjanumeron tiedot
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

		if ($row["kommentti"] != '' and mysql_num_rows($sarjares) > 0){
			$row["kommentti"] .= "\n";
		}
		while($sarjarow = mysql_fetch_array($sarjares)) {
			$row["kommentti"] .= "# $sarjarow[sarjanumero]\n";
		}

		//Kommentti
		if (trim($row["kommentti"]) != '') {
			$kommentit = explode("\n", trim($row["kommentti"]));
			$pohja = $kala;

			foreach($kommentit as $kommentti) {
				$pdf->draw_text(35, $pohja, $row["otunnus"]."/".$kommentti, $firstpage, $pieni);
				$pohja = $pohja - 10;
			}
			$pohja += 10;
		}
		else {
			$pdf->draw_text(35, $kala, $row["otunnus"],	$firstpage, $pieni);
			$pohja = $kala;
		}

		if ($pohja < $kala) {
			$kala = $pohja;
		}
		
		$kala = $kala - 15;
	
		$arvo	+= $row["rivihinta"];
		$summa 	+= sprintf('%.2f', $row["rivihinta"]*(1+($row["alv"]/100)));
	}
}

if (!function_exists('loppu')) {
	function loppu ($firstpage) {

		global $yhtiorow, $kukarow, $toim, $pdf, $laskurow, $sivu, $rectparam, $norm, $summa, $arvo, $pieni, $kieli, $masrow, $boldi, $uytunnus, $ytunpit, $pieni_boldi;

		$vientikala = 0;

		if ($laskurow["vienti"] != "") {
			$vientikala = 20;
		}

		//yhteensärivi
		$pdf->draw_rectangle(110+$vientikala, 20, 90+$vientikala, 580,	$firstpage, $rectparam);
		$pdf->draw_rectangle(110+$vientikala, 207, 90+$vientikala, 580,	$firstpage, $rectparam);
		$pdf->draw_rectangle(110+$vientikala, 394, 90+$vientikala, 580,	$firstpage, $rectparam);
		$pdf->draw_rectangle(110+$vientikala, 540, 90+$vientikala, 580,	$firstpage, $rectparam);

		$pdf->draw_text(30,  102+$vientikala, t("Eräpäivä", $kieli),	$firstpage, $pieni);
		$pdf->draw_text(30,  92+$vientikala,  tv1dateconv($laskurow["erpcm"]),		$firstpage, $norm);
		$pdf->draw_text(217, 102+$vientikala, t("Viitenumero", $kieli),	$firstpage, $pieni);
		$pdf->draw_text(217, 92+$vientikala,  $laskurow["viite"],		$firstpage, $norm);
		$pdf->draw_text(404, 92+$vientikala,  t("Yhteensä", $kieli).":",$firstpage, $norm);

		if ($toim == 'PROFORMA') {
			// Etsitään asiakas
			$query = "	SELECT laskunsummapyoristys
						FROM asiakas
						WHERE tunnus='$laskurow[liitostunnus]' and yhtio='$kukarow[yhtio]'";
			$asres = mysql_query($query) or pupe_error($query);
			$asrow = mysql_fetch_array($asres);

			if ($yhtiorow["laskunsummapyoristys"] == 'o' or $asrow["laskunsummapyoristys"] == 'o') {
		        $summa = round($summa,0);
			}
			
			$pdf->draw_text(464, 92+$vientikala,  sprintf('%.2f', $summa),			$firstpage, $boldi);
		}
		elseif ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
			$pdf->draw_text(464, 92+$vientikala,  $laskurow["summa_valuutassa"],	$firstpage, $boldi);
		}
		else {
			$pdf->draw_text(464, 92+$vientikala,  $laskurow["summa"],				$firstpage, $boldi);
		}

		$pdf->draw_text(550, 92+$vientikala,  $laskurow["valkoodi"],				$firstpage, $boldi);

		//Pankkiyhteystiedot
		$pdf->draw_rectangle(90+$vientikala, 20, 20, 580,	$firstpage, $rectparam);

	    if ($masrow["factoring"] != '') {
			// jos factoroidaan tulostaan eri kentät
			$query = "	SELECT *
						FROM factoring
						WHERE yhtio 		= '$kukarow[yhtio]'
						and factoringyhtio 	= '$masrow[factoring]'
						and valkoodi 		= '$laskurow[valkoodi]'";
			$fres = mysql_query($query) or pupe_error($query);
			$frow = mysql_fetch_array($fres);

			if ($laskurow["vienti"] != "") {
				// jos on vienti
				$pdf->draw_text(30,  102, t("Pankkiyhteys", $kieli),						$firstpage, $pieni);
				$pdf->draw_text(30,  92, $frow["pankkinimi1"]."  ".$frow["pankkitili1"],	$firstpage, $norm);
				$pdf->draw_text(217, 92, "IBAN: " .$frow["pankkiiban1"],					$firstpage, $norm);
				$pdf->draw_text(404, 92, "SWIFT: ".$frow["pankkiswift1"],					$firstpage, $norm);

				$pdf->draw_text(30,  82, $frow["pankkinimi2"]."  ".$frow["pankkitili2"],	$firstpage, $norm);
				$pdf->draw_text(217, 82, "IBAN: " .$frow["pankkiiban2"],					$firstpage, $norm);
				$pdf->draw_text(404, 82, "SWIFT: ".$frow["pankkiswift2"],					$firstpage, $norm);
			}
			else {
				// ja kotimaisissa tällästä
				$pdf->draw_text(30, 82,  t("Pankkiyhteys", $kieli),							$firstpage, $pieni);
	        	$pdf->draw_text(30, 72,  $frow["pankkinimi1"]." ".$frow["pankkitili1"],		$firstpage, $norm);
	        	$pdf->draw_text(217, 72, $frow["pankkinimi2"]." ".$frow["pankkitili2"],		$firstpage, $norm);
			}
		}
		else {
			// ei factoroida niin normitiedot
			if ($laskurow["vienti"] != "") {
				// viennissä tällästä
				$pdf->draw_text(30,  102, t("Pankkiyhteys", $kieli),													$firstpage, $pieni);
				$pdf->draw_text(30,  92, $yhtiorow["pankkinimi1"]."  ".$yhtiorow["pankkitili1"],						$firstpage, $norm);
				$pdf->draw_text(217, 92, "IBAN: ".$yhtiorow["pankkiiban1"],												$firstpage, $norm);
				$pdf->draw_text(404, 92, "SWIFT: ".$yhtiorow["pankkiswift1"],											$firstpage, $norm);

				$pdf->draw_text(30,  82, $yhtiorow["pankkinimi2"]."  ".$yhtiorow["pankkitili2"],						$firstpage, $norm);
				$pdf->draw_text(217, 82, "IBAN: ".$yhtiorow["pankkiiban2"],												$firstpage, $norm);
				$pdf->draw_text(404, 82, "SWIFT: ".$yhtiorow["pankkiswift2"],											$firstpage, $norm);

				$pdf->draw_text(30,  72, $yhtiorow["pankkinimi3"]."  ".$yhtiorow["pankkitili3"],						$firstpage, $norm);
				$pdf->draw_text(217, 72, "IBAN: ".$yhtiorow["pankkiiban3"],												$firstpage, $norm);
				$pdf->draw_text(404, 72, "SWIFT: ".$yhtiorow["pankkiswift3"],											$firstpage, $norm);
			}
			else {
				// ja normitilauksissa tällästä
			    $pdf->draw_text(30, 82,  t("Pankkiyhteys", $kieli),														$firstpage, $pieni);
	        	$pdf->draw_text(30, 72,  $yhtiorow["pankkinimi1"]." ".$yhtiorow["pankkitili1"],							$firstpage, $norm);
	        	$pdf->draw_text(217, 72, $yhtiorow["pankkinimi2"]." ".$yhtiorow["pankkitili2"],							$firstpage, $norm);
	        	$pdf->draw_text(404, 72, $yhtiorow["pankkinimi3"]." ".$yhtiorow["pankkitili3"],							$firstpage, $norm);
	        }
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
		$pdf->draw_text(257, 55, $yhtiorow["puhelin"],				$firstpage, $pieni);
		$pdf->draw_text(217, 45, t("Telefax", $kieli).":",			$firstpage, $pieni);
		$pdf->draw_text(257, 45, $yhtiorow["fax"],					$firstpage, $pieni);
		$pdf->draw_text(217, 35, t("Sähköposti", $kieli).":",		$firstpage, $pieni);
		$pdf->draw_text(257, 35, $yhtiorow["email"],				$firstpage, $pieni);

		//jos on suomalainen yritys tehdään ytunnus nätiks
		if (strtoupper($yhtiorow["maakoodi"])== 'FI') {
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

		$uytunnus = $yhtiorow["maakoodi"]."-".$uytunnus;

		$pdf->draw_text(404, 55, t("Y-tunnus", $kieli).":",			$firstpage, $pieni);
		$pdf->draw_text(444, 55, $uytunnus,							$firstpage, $pieni);
		$pdf->draw_text(404, 45, t("Kotipaikka", $kieli).":",		$firstpage, $pieni);
		$pdf->draw_text(444, 45, $yhtiorow["kotipaikka"],			$firstpage, $pieni);
		$pdf->draw_text(404, 35, t("Enn.per.rek", $kieli),			$firstpage, $pieni);
		$pdf->draw_text(404, 25, t("Alv.rek", $kieli),				$firstpage, $pieni);
	}
}

if (!function_exists('alvierittely')) {
	function alvierittely ($firstpage) {
		global $yhtiorow, $kukarow, $toim, $pdf, $laskurow, $sivu, $rectparam, $norm, $pieni, $summa, $arvo, $kala, $kieli, $boldi;

		if ($kala <= 200) {
			loppu($firstpage);

			$sivu++;
			$loppumyos 	= 1;

			$firstpage 	= alku();
		}

		$kala -=10;
		//loppusummat vain vikalle sivulle
		$pdf->draw_text(360, $kala, t("Veroton arvo yhteensä", $kieli).":", 	$firstpage, $norm);

		if ($toim == 'PROFORMA' or ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"])))) {
			$pdf->draw_text(470, $kala, sprintf('%.2f', $arvo), 				$firstpage, $norm);
		}
		else {
			$pdf->draw_text(470, $kala, $laskurow["arvo"], 						$firstpage, $norm);
		}

		$pdf->draw_text(530, $kala, $laskurow["valkoodi"], 						$firstpage, $norm);


		if ($toim == 'PROFORMA') {
			$where = " otunnus = '$laskurow[tunnus]' ";
		}
		else {
			$where = " uusiotunnus = '$laskurow[tunnus]' ";
		}

		//Haetaan kaikki alvikannat riveiltä
		$alvquery = "	SELECT distinct alv
						FROM tilausrivi
						WHERE $where and yhtio='$kukarow[yhtio]'
						ORDER BY alv";
		$alvresult = mysql_query($alvquery) or pupe_error($alvquery);
		$kala -= 10;

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

			$pdf->draw_text(360, $kala, t("Arvonlisävero", $kieli)." (".sprintf('%.2f',$arow["rivihinta"])."): ",	$firstpage, $norm);
			$pdf->draw_text(470, $kala, sprintf('%.2f', $arow["alvrivihinta"]), $firstpage, $norm);

			if ($alvrow["alv"] >= 500) {
				$pdf->draw_text(530, $kala, t("M.V."), $firstpage, $norm);
			}
			else {
				$pdf->draw_text(530, $kala, $alvrow["alv"]."%", $firstpage, $norm);
			}

			$kala -= 10;
		}

		$pdf->draw_text(360, $kala, t("Lasku Yhteensä", $kieli).":",		$firstpage, $norm);

		if ($toim == 'PROFORMA') {		
			$pdf->draw_text(470, $kala, sprintf('%.2f', $summa),			$firstpage, $boldi);
		}
		elseif ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {		
			$pdf->draw_text(470, $kala, $laskurow["summa_valuutassa"],		$firstpage, $boldi);
		}
		else {
			$pdf->draw_text(470, $kala, $laskurow["summa"], 				$firstpage, $boldi);
		}

		$pdf->draw_text(530, $kala, $laskurow["valkoodi"], 					$firstpage, $boldi);
		$kala -= 10;

		if ($loppumyos == 1) {
			loppu($firstpage);
		}
	}
}
?>
