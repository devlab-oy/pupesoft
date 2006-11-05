<?php

require_once('../pdflib/phppdflib.class.php');

//PDF parametrit
$pdf = new pdffile;
$pdf->set_default('margin-top', 	0);
$pdf->set_default('margin-bottom', 	0);
$pdf->set_default('margin-left', 	0);
$pdf->set_default('margin-right', 	0);
$rectparam["width"] = 0.3;

$norm["height"] = 10;
$norm["font"] = "Times-Roman";

$pieni["height"] = 8;
$pieni["font"] = "Times-Roman";

$boldi["height"] = 10;
$boldi["font"] = "Times-Bold";

$pieni_boldi["height"] = 8;
$pieni_boldi["font"] = "Times-Bold";

// defaultteja
$kala = 540;
$sivu = 1;


// laskun alku ja loppu functiot...
if (!function_exists('alku')) {
	function alku () {
		global $yhtiorow, $kukarow, $kutsuja, $pdf, $laskurow, $sivu, $rectparam, $norm, $pieni, $maksuehto, $kieli, $boldi, $kala;

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
			if ($kutsuja == 'proforma') {
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
			$pdf->draw_text(430, 740, $laskurow["kasumma"], $firstpage, $norm);
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

		//tehdään viestejä ja lasketaan niitten pituuksia
		if ($laskurow['sisviesti1'] != '') {

			$pdf->draw_text(35, 543, t("Huomautukset", $kieli).":", $firstpage, $norm);
			$pohja = $pdf->draw_paragraph(543, 35, 60, 550, trim($laskurow["sisviesti1"])."\n", $firstpage, $norm); // top, left, bottom, right

			$laskurow['sisviesti1'] = "";

			if (!is_numeric($pohja)) {
				$laskurow['sisviesti1'] = $pohja;
				loppu($firstpage, 0);
				$firstpage = alku();
			}
			else {
				// viivat rivien väliin...
				$x[0] = 20;
				$x[1] = 580;
				$y[0] = $y[1] = $pohja - 4;
				$pdf->draw_line($x, $y, $firstpage, $rectparam);
				$kala = $pohja - 15;
			}

		}

		return($firstpage);
	}
}

if (!function_exists('rivi')) {
	function rivi ($firstpage) {
		global $yhtiorow, $kukarow, $kutsuja, $firstpage, $pdf, $row, $kala, $sivu, $lask, $rectparam, $norm, $summa, $arvo, $pieni, $lask, $kieli, $laskurow;

		if ($kala <= 180) {
			$sivu++;

			loppu($firstpage);

			$firstpage = alku();

			$kala = 540;
		}

		$rivihinta_veroton = 0;
		$rivihinta_verolli = 0;

		$pdf->draw_text(35,  $kala, substr($row["nimitys"],0,22),	$firstpage, $norm);
		$pdf->draw_text(190, $kala, $row["tuoteno"], 				$firstpage, $norm);

		if ($kutsuja != 'proforma') {
			$pdf->draw_text(290, $kala, $row["kpl"], 				$firstpage, $norm);
		}
		else {
			$pdf->draw_text(290, $kala, $row["varattu"], 			$firstpage, $norm);
		}

		// A-hinta (voi olla verollinen tai veroton riippuen yhtiöstä)
		$pdf->draw_text(350, $kala, $row["hinta"], 					$firstpage, $norm);

		if ($kutsuja != 'proforma') {
			$pdf->draw_text(410, $kala, $row["ale"],				$firstpage, $norm);
			$pdf->draw_text(470, $kala, $row["rivihinta"], 			$firstpage, $norm);
		}
		else {
			///* Otetaan huomioon erikoisalennus *///
			if (strtoupper($row["netto"]) != 'N') {
				$ale = round($row["ale"] + $laskurow["erikoisale"] - ($row["ale"] * $laskurow["erikoisale"] / 100),2);
			}
			else {
				$ale = $row["ale"];
			}

			if ($yhtiorow["alv_kasittely"] == '') {
				$rivihinta_veroton = ($row["hinta"]*$row["varattu"]*(1-($ale/100)))  / (1+$row["alv"]/100);
			}
			else {
				$rivihinta_veroton = $row["hinta"]*$row["varattu"]*(1-($ale/100));
			}


			if ($row["alv"] >= 500) {
				$rivihinta_verolli  = $rivihinta_veroton;
			}
			else {
				$rivihinta_verolli  = $rivihinta_veroton * (1+$row["alv"]/100);
			}

			$pdf->draw_text(410, $kala, sprintf('%.2f', $ale),							$firstpage, $norm);
			$pdf->draw_text(470, $kala, sprintf('%.2f',round($rivihinta_veroton,2)), 	$firstpage, $norm);

			$summa += $rivihinta_verolli;
			$arvo  += $rivihinta_veroton;
		}

		if ($row["alv"] >= 500) {
			$pdf->draw_text(530, $kala, t("M.V."),	 				$firstpage, $norm);
		}
		else {
			$pdf->draw_text(530, $kala, $row["alv"], 				$firstpage, $norm);
		}

		// Ensimmäinen rivi piirretty
		$kala = $kala - 10;


		// Jos ALV no yli 500 niin tiedämme että tämä on marginaaliverotusta
		if ($row["alv"] >= 500) {
			$row["kommentti"] .= "\nEi sisällä vähennettävää veroa.";
		}

		if ($row["kommentti"] != ''){
			$pohja = $pdf->draw_paragraph($kala+8, 35, 160, 280, $row["otunnus"]."/".$row["kommentti"].$marginaalilisa, $firstpage, $pieni);

			if (!is_numeric($pohja)) {
				$pohja = 160;
			}
		}
		else {
			$pdf->draw_text(35,  $kala, $row["otunnus"],	$firstpage, $pieni);
		}

		$pdf->draw_text(290, $kala, $row["yksikko"],	$firstpage, $pieni);

		if (substr($row["toimitettuaika"],0,10) != "0000-00-00") {
			$pdf->draw_text(350, $kala, tv1dateconv(substr($row["toimitettuaika"],0,10)), 	$firstpage, $pieni);
		}

		if ($row["alv"] < 500) {
			if ($kutsuja != 'proforma') {
				$verollinenhinta = sprintf('%.2f', $row["rivihinta"]*(1+($row["alv"]/100)));
			}
			else {
				$verollinenhinta = sprintf('%.2f', $rivihinta_verolli);
			}

			$pdf->draw_text(470, $kala, $verollinenhinta, $firstpage, $pieni);
		}

		if ($pohja > 0) {
			$kala = $pohja;
		}

		$kala = $kala - 15;
	}
}

if (!function_exists('loppu')) {
	function loppu ($firstpage) {

		global $yhtiorow, $kukarow, $kutsuja, $pdf, $laskurow, $sivu, $rectparam, $norm, $summa, $arvo, $pieni, $kieli, $masrow, $boldi, $uytunnus, $ytunpit, $pieni_boldi;

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


		if ($kutsuja != 'proforma') {
			$pdf->draw_text(464, 92+$vientikala,  $laskurow["summa"],					$firstpage, $boldi);
		}
		else {
			$pdf->draw_text(464, 92+$vientikala,  sprintf('%.2f',round($summa, 2)),	$firstpage, $boldi);
		}

		$pdf->draw_text(550, 92+$vientikala,  $laskurow["valkoodi"],	$firstpage, $boldi);

		//Pankkiyhteystiedot
		$pdf->draw_rectangle(90+$vientikala, 20, 20, 580,	$firstpage, $rectparam);

	    if ($masrow["factoring"] != '') {
			$pdf->draw_text(30, 82,  t("Pankkiyhteys", $kieli),	$firstpage, $pieni);
        	$pdf->draw_text(30, 72,  $yhtiorow["factoring_pankkinimi1"]." ".$yhtiorow["factoring_pankkitili1"],	$firstpage, $norm);
        	$pdf->draw_text(217, 72, $yhtiorow["factoring_pankkinimi2"]." ".$yhtiorow["factoring_pankkitili2"],	$firstpage, $norm);
        	$pdf->draw_text(404, 72, $yhtiorow["factoring_pankkinimi3"]." ".$yhtiorow["factoring_pankkitili3"],	$firstpage, $norm);
		}
		elseif ($laskurow["vienti"] != "") {
			$pdf->draw_text(30,  102, t("Pankkiyhteys", $kieli),								$firstpage, $pieni);
			$pdf->draw_text(30,  92, $yhtiorow["pankkinimi1"]."  ".$yhtiorow["pankkitili1"],			$firstpage, $norm);
			$pdf->draw_text(217, 92, "IBAN: ".$yhtiorow["pankkiiban1"],		$firstpage, $norm);
			$pdf->draw_text(404, 92, "SWIFT: ".$yhtiorow["pankkiswift1"],	$firstpage, $norm);

			$pdf->draw_text(30,  82, $yhtiorow["pankkinimi2"]."  ".$yhtiorow["pankkitili2"],			$firstpage, $norm);
			$pdf->draw_text(217, 82, "IBAN: ".$yhtiorow["pankkiiban2"],		$firstpage, $norm);
			$pdf->draw_text(404, 82, "SWIFT: ".$yhtiorow["pankkiswift2"],	$firstpage, $norm);

			$pdf->draw_text(30,  72, $yhtiorow["pankkinimi3"]."  ".$yhtiorow["pankkitili3"],			$firstpage, $norm);
			$pdf->draw_text(217, 72, "IBAN: ".$yhtiorow["pankkiiban3"],		$firstpage, $norm);
			$pdf->draw_text(404, 72, "SWIFT: ".$yhtiorow["pankkiswift3"],	$firstpage, $norm);
		}
		else {
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
	function alvierittely ($firstpage, $kala) {
		global $yhtiorow, $kukarow, $kutsuja, $pdf, $laskurow, $sivu, $rectparam, $norm, $pieni, $summa, $arvo, $kala, $lask, $kieli, $boldi;

		if ($lask >= 29) {
			$sivu++;
			loppu($firstpage);
			$firstpage = alku();
			$kala = 540;
			$lask = 1;
			$loppumyos = 1;
		}

		$kala -=10;
		//loppusummat vain vikalle sivulle
		$pdf->draw_text(360, $kala, t("Veroton arvo yhteensä", $kieli).":", 	$firstpage, $norm);

		if ($kutsuja != 'proforma') {
			$pdf->draw_text(470, $kala, $laskurow["arvo"], 						$firstpage, $norm);
		}
		else {
			$pdf->draw_text(470, $kala, sprintf('%.2f',round($arvo,2)), 		$firstpage, $norm);
		}

		$pdf->draw_text(530, $kala, $laskurow["valkoodi"], 						$firstpage, $norm);


		if ($kutsuja != 'proforma') {
			$where = " uusiotunnus = '$laskurow[tunnus]' ";
		}
		else {
			$where = " otunnus = '$laskurow[tunnus]' ";
		}

		//Haetaan kaikki alikannat riveiltä
		$alvquery = "	SELECT distinct alv
						FROM tilausrivi
						WHERE $where and yhtio='$kukarow[yhtio]'
						ORDER BY alv";
		$alvresult = mysql_query($alvquery) or pupe_error($alvquery);
		$kala -= 10;

		while ($alvrow = mysql_fetch_array($alvresult)) {

			if ($alvrow["alv"] >= 500 and $kutsuja != 'proforma') {
				$aquery = "	SELECT sum(0) alvrivihinta, sum(rivihinta) rivihinta
							FROM tilausrivi
							WHERE uusiotunnus = '$laskurow[tunnus]' and yhtio='$kukarow[yhtio]' and alv='$alvrow[alv]'";
			}
			elseif ($alvrow["alv"] >= 500) {
				$aquery = "	SELECT round(0) alvrivihinta, round(sum(hinta*varattu*(1-ale/100)),2) rivihinta
							FROM tilausrivi
							WHERE otunnus = '$laskurow[tunnus]' and yhtio='$kukarow[yhtio]' and alv='$alvrow[alv]'";
			}
			elseif ($alvrow["alv"] < 500 and $kutsuja != 'proforma') {
				$aquery = "	SELECT round(sum(rivihinta*(alv/100)),2) alvrivihinta, sum(rivihinta) rivihinta
							FROM tilausrivi
							WHERE uusiotunnus = '$laskurow[tunnus]' and yhtio='$kukarow[yhtio]' and alv='$alvrow[alv]'";
			}
			else {
				$aquery = "	SELECT
							round(sum(hinta / if('$yhtiorow[alv_kasittely]' = '', (1+alv/100), 1) * varattu * if(netto='N', (1-ale/100), (1-(ale+$laskurow[erikoisale]-(ale*$laskurow[erikoisale]/100))/100)) * (alv/100)),2) alvrivihinta,
							round(sum(hinta / if('$yhtiorow[alv_kasittely]' = '', (1+alv/100), 1) * varattu * if(netto='N', (1-ale/100), (1-(ale+$laskurow[erikoisale]-(ale*$laskurow[erikoisale]/100))/100))),2) rivihinta
							FROM tilausrivi
							WHERE otunnus = '$laskurow[tunnus]' and yhtio='$kukarow[yhtio]' and alv='$alvrow[alv]'";
			}
			$aresult = mysql_query($aquery) or pupe_error($aquery);
			$arow = mysql_fetch_array($aresult);

			$pdf->draw_text(360, $kala, t("Arvonlisävero", $kieli)." (".$arow["rivihinta"]."): ",	$firstpage, $norm);


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

		if ($kutsuja != 'proforma') {
			$pdf->draw_text(470, $kala, $laskurow["summa"], 				$firstpage, $boldi);
		}
		else {
			$pdf->draw_text(470, $kala, sprintf('%.2f',round($summa,2)),	$firstpage, $boldi);
		}

		$pdf->draw_text(530, $kala, $laskurow["valkoodi"], 					$firstpage, $boldi);
		$kala -= 10;

		if ($loppumyos == 1) {
			loppu($firstpage);
		}
	}
}
?>
