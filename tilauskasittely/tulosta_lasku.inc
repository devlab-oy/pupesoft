<?php

require_once('pdflib/phppdflib.class.php');

$rectparam["width"] 	= 0.3;

$norm["height"] 		= 10;
$norm["font"] 			= "Times-Roman";

$pieni["height"] 		= 8;
$pieni["font"] 			= "Times-Roman";

$boldi["height"] 		= 10;
$boldi["font"] 			= "Times-Bold";

$pieni_boldi["height"] 	= 8;
$pieni_boldi["font"] 	= "Times-Bold";

$iso["height"] 			= 14;
$iso["font"] 			= "Helvetica-Bold";

// defaultteja
$sivu 	= 1;
$summa 	= 0;
$arvo 	= 0;

if (!function_exists('alku')) {
	function alku () {
		global $yhtiorow, $kukarow, $toim, $pdf, $laskurow, $sivu, $rectparam, $norm, $pieni, $iso, $maksuehto, $kateistyyppi, $kieli, $boldi, $kala;

		//PDF parametrit
		if (!isset($pdf)) {
			$pdf = new pdffile;
			$pdf->set_default('margin-top', 	0);
			$pdf->set_default('margin-bottom', 	0);
			$pdf->set_default('margin-left', 	0);
			$pdf->set_default('margin-right', 	0);
		}

		$thispage = $pdf->new_page("a4");

		if (trim($yhtiorow["lasku_logo"]) != '' and file_exists($yhtiorow["lasku_logo"])) {

			$filename = $yhtiorow["lasku_logo"];

			$fh = fopen($filename, "r");
			$data = fread($fh, filesize($filename));
			fclose($fh);

			$image = $pdf->jfif_embed($data);

			if(!$image) {
				echo t("Logokuvavirhe");
			}
			else {
				$isizelogo = getimagesize($yhtiorow["lasku_logo"]);

				$logoparam = array();

				if ($isizelogo[0] > $isizelogo[1] and $isizelogo[1] * (240 / $isizelogo[0]) <= 50) {
					$logoparam['scale'] = 240 / $isizelogo[0];
				}
				else {
					$logoparam['scale'] = 50  / $isizelogo[1];
				}

				$placement = $pdf->image_place($image, 830-($logoparam['scale']*$isizelogo[1]), 20, $thispage, $logoparam);
			}
		}
		else {
			$pdf->draw_text(30, 815,  $yhtiorow["nimi"], $thispage);
		}

		// Asiakkaan tiedot
		$query 	= "	SELECT *
					from asiakas
					where tunnus	= '$laskurow[liitostunnus]'
					and yhtio		= '$kukarow[yhtio]'";
		$result  	= mysql_query($query) or pupe_error($query);
		$asiakasrow = mysql_fetch_array($result);

		//Otsikko
		if ($laskurow["vienti"] == "") {
			if ($laskurow["summa"] >= 0) {
				if ($toim == 'PROFORMA') {
					$pdf->draw_text(310, 815, t("Proformalasku", $kieli), $thispage, $iso);
				}
				else {
					$pdf->draw_text(310, 815, t("Lasku", $kieli), $thispage, $iso);
				}
			}
			else {
				$pdf->draw_text(310, 815, t("Hyvityslasku", $kieli), $thispage, $iso);
			}
		}
		else {
			if ($toim == 'PROFORMA') {
				$pdf->draw_text(310, 815, t("Proformalasku", $kieli), $thispage, $iso);
			}
			elseif ($kateiskuitti != ""){
				$pdf->draw_text(310, 815, t("K‰teiskuitti", $kieli),  $thispage, $iso);
			}
			elseif ($laskurow["summa"] >= 0) {
				$pdf->draw_text(310, 815, t("Vientilasku", $kieli), $thispage, $iso);
			}
			else {
				$pdf->draw_text(310, 815, t("Viennin hyvityslasku", $kieli), $thispage, $iso);
			}
		}

		$pdf->draw_text(430, 815, t("Sivu", $kieli), $thispage, $norm);

		//Vasen sarake
		//$pdf->draw_rectangle(737, 20,  674, 300, $thispage, $rectparam);
		$pdf->draw_text(50, 729, t("Laskutusosoite", $kieli), 	$thispage, $pieni);
		$pdf->draw_text(50, 717, $laskurow["nimi"], 			$thispage, $norm);
		$pdf->draw_text(50, 707, $laskurow["nimitark"],			$thispage, $norm);
		$pdf->draw_text(50, 697, $laskurow["osoite"], 			$thispage, $norm);
		$pdf->draw_text(50, 687, $laskurow["postino"]." ".$laskurow["postitp"], $thispage, $norm);
		$pdf->draw_text(50, 677, $laskurow["maa"], 				$thispage, $norm);


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
		$pdf->draw_text(310, 792, t("Laskun pvm", $kieli), 	$thispage, $pieni);
		$pdf->draw_text(310, 782, tv1dateconv($laskurow["tapvm"]),		$thispage, $norm);
		$pdf->draw_text(430, 792, t("Er‰p‰iv‰", $kieli), 	$thispage, $pieni);

		if ($kateistyyppi != '') {
			$erapaiva = t('MAKSETTU');
		} else {
			$erapaiva = tv1dateconv($laskurow["erpcm"]);
		}

		$pdf->draw_text(430, 782, $erapaiva, $thispage, $norm);

		$pdf->draw_rectangle(779, 300, 758, 580, $thispage, $rectparam);
		$pdf->draw_rectangle(779, 420, 758, 580, $thispage, $rectparam);
		$pdf->draw_text(310, 771, t("Viitenumero", $kieli), 	$thispage, $pieni);
		$pdf->draw_text(310, 761, $laskurow["viite"], 			$thispage, $norm);
		$pdf->draw_text(430, 771, t("Laskun numero", $kieli), 	$thispage, $pieni);
		$pdf->draw_text(430, 761, $laskurow["laskunro"], 		$thispage, $boldi);

		$pdf->draw_rectangle(758, 300, 737, 580, $thispage, $rectparam);
		$pdf->draw_rectangle(758, 420, 737, 580, $thispage, $rectparam);
		$pdf->draw_text(310, 750, t("Maksuehto", $kieli), $thispage, $pieni);

		for($s = strlen($maksuehto); $s > 0; $s--) {
			if($pdf->strlen($maksuehto, $norm) > 110) {
				$maksuehto = substr($maksuehto, 0, $s);
			}
			else {
				break;
			}
		}

		$pdf->draw_text(310, 740, pdf_substr($maksuehto, 110, $pdf, $norm), $thispage, $norm);

		$pdf->draw_text(430, 750, t("Kassa-alennus", $kieli)." ".$laskurow["valkoodi"], $thispage, $pieni);

		if ($laskurow["kasumma"] != 0.00) {
			if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
				$pdf->draw_text(430, 740, $laskurow["kasumma_valuutassa"], $thispage, $norm);
			}
			else {
				$pdf->draw_text(430, 740, $laskurow["kasumma"], $thispage, $norm);
			}
		}

		$pdf->draw_text(430, 729, t("Kassa-alennus pvm", $kieli), 	$thispage, $pieni);

		if ($laskurow["kapvm"] != '0000-00-00') {
			$pdf->draw_text(430, 719, tv1dateconv($laskurow["kapvm"]), $thispage, $norm);
		}

		$pdf->draw_rectangle(737, 300, 716, 580, $thispage, $rectparam);
		$pdf->draw_rectangle(737, 420, 716, 580, $thispage, $rectparam);
		$pdf->draw_text(310, 729, t("Viiv‰styskorko", $kieli), 		$thispage, $pieni);
		$pdf->draw_text(310, 719, $laskurow["viikorkopros"],	 	$thispage, $norm);

		$pdf->draw_rectangle(716, 300, 695, 580, $thispage, $rectparam);
		$pdf->draw_rectangle(716, 420, 695, 580, $thispage, $rectparam);
		$pdf->draw_text(310, 708, t("Valuutta", $kieli), 		$thispage, $pieni);
		$pdf->draw_text(310, 698, $laskurow["valkoodi"], 		$thispage, $norm);
		$pdf->draw_text(430, 708, t("Asiaa hoitaa", $kieli), 	$thispage, $pieni);

		//etsit‰‰n myyj‰n nimi
		$query  = "	select nimi
					from kuka
					where tunnus='$laskurow[myyja]' and yhtio='$kukarow[yhtio]'";
		$myyresult = mysql_query($query) or pupe_error($query);
		$myyrow = mysql_fetch_array($myyresult);

		$pdf->draw_text(430, 698, $myyrow["nimi"], $thispage, $norm);

		$pdf->draw_rectangle(695, 300, 674, 580, $thispage, $rectparam);
		$pdf->draw_rectangle(695, 420, 674, 580, $thispage, $rectparam);
		$pdf->draw_text(310, 687, t("Asiakkaan Y-tunnus", $kieli), 	$thispage, $pieni);

		if (substr(trim(strtoupper($laskurow["ytunnus"])),0,2) != strtoupper($laskurow["maa"]) and
			trim(strtoupper($laskurow["maa"])) != trim(strtoupper($yhtiorow["maa"])) and
			trim(strtoupper($laskurow["maa"])) != "") {

			$laskurow["ytunnus"] = strtoupper($laskurow["maa"])."-".$laskurow["ytunnus"];
		}

		$pdf->draw_text(310, 677, $laskurow["ytunnus"], 			$thispage, $norm);
		$pdf->draw_text(430, 687, t("Asiakasnumero", $kieli), 		$thispage, $pieni);


		if(trim($asiakasrow["asiakasnro"]) != "") {
			$pdf->draw_text(430, 677, $asiakasrow["asiakasnro"], 		$thispage, $norm);
		}
		else {
			$pdf->draw_text(430, 677, $laskurow["ytunnus"], 			$thispage, $norm);
		}

		$pdf->draw_rectangle(674, 300, 653, 580, $thispage, $rectparam);
		$pdf->draw_rectangle(674, 420, 653, 580, $thispage, $rectparam);
		$pdf->draw_text(310, 666, t("Toimitustapa", $kieli), 	$thispage, $pieni);

		$pdf->draw_text(310, 656, pdf_substr($laskurow["toimitustapa"], 110, $pdf, $norm), 	$thispage, $norm);

		$pdf->draw_text(430, 666, t("Toimituspvm", $kieli), 	$thispage, $pieni);

		//	Onko meill‰ monta toimitusp‰iv‰‰ vai ei?
		if ($laskurow["tila"] == 'U') {
			$where = " uusiotunnus='$laskurow[tunnus]' ";
		}
		else {
			$where = " otunnus='$laskurow[tunnus]' ";
		}

		$query = "	SELECT date_format(toimitettuaika, '%Y-%m-%d') toimitettupaiva
					FROM tilausrivi
					WHERE yhtio='$kukarow[yhtio]'
					and $where
					and toimitettuaika!='0000-00-00'
					GROUP BY toimitettupaiva";
		$toimaikares = mysql_query($query) or pupe_error($query);

		if(mysql_num_rows($toimaikares) > 1) {
			$pdf->draw_text(430, 656, t("Useita"), 											$thispage, $norm);
		}
		else {
			$toimaikarow = mysql_fetch_array($toimaikares);

			$pdf->draw_text(430, 656, tv1dateconv($toimaikarow["toimitettupaiva"]),			$thispage, $norm);
		}

		$pdf->draw_rectangle(653, 300, 632, 580, $thispage, $rectparam);
		$pdf->draw_text(310, 645, t("Toimitusehto", $kieli), $thispage, $pieni);
		$pdf->draw_text(310, 635, pdf_substr($laskurow["toimitusehto"], 270, $pdf, $norm), $thispage, $norm);

		$pdf->draw_rectangle(632, 300, 611, 580, $thispage, $rectparam);
		$pdf->draw_text(310, 624, t("Tilausviite", $kieli), 	$thispage, $pieni);
		$pdf->draw_text(310, 614, pdf_substr($laskurow["viesti"], 270, $pdf, $norm), 		$thispage, $norm);

		$pdf->draw_text(310, 595, t("Mahdolliset muistutukset teht‰v‰ 8 p‰iv‰n kuluessa", $kieli), $thispage, $pieni);

		$komm = "";

		if (trim($laskurow['tilausyhteyshenkilo']) != '') {
			$komm .= "\n".t("Tilaaja", $kieli).":###".$laskurow['tilausyhteyshenkilo'];
		}

		if (trim($laskurow['asiakkaan_tilausnumero']) != '') {
			$komm .= "\n".t("Tilauksenne", $kieli).":###".$laskurow['asiakkaan_tilausnumero'];
		}

		if (trim($laskurow['kohde']) != '') {
			$komm .= "\n".t("Kohde", $kieli).":###".$laskurow['kohde'];
		}

		if (trim($laskurow['viesti']) != '' and $pdf->strlen($laskurow["viesti"], $norm) > 270) {
			$komm .= "\n".t("Tilausviite", $kieli).":###".$laskurow['viesti'];
		}

		if (trim($laskurow['sisviesti1']) != '') {
			$komm .= "\n".t("Kommentti", $kieli).":###".wordwrap(str_replace("\n","\n###", $laskurow['sisviesti1']), 100, "\n###");
		}

		if ($yhtiorow["laskutyyppi"] == 1 or $yhtiorow["laskutyyppi"] == 4) {
			$query 		= "SELECT ifnull(group_concat(distinct tunnus SEPARATOR ', '), 0) tilnot from lasku where yhtio='$kukarow[yhtio]' and laskunro='$laskurow[laskunro]' and tila = 'L'";
			$result  	= mysql_query($query) or pupe_error($query);
			$apulaskuro = mysql_fetch_array($result);

			if ($apulaskuro["tilnot"] != "0") {
				$komm .= "\n".t("Tilausnumero(t)", $kieli). ":###".wordwrap(str_replace("\n","\n###", $apulaskuro["tilnot"]), 100, "\n###");
			}
		}

		//Tulostetaan laskun kommentti
		if (trim($komm) != '') {
			$kommentit = explode("\n", trim($komm));

			$pohja  = 577;
			$maxoik = 0;

			foreach($kommentit as $kommentti) {
				if(strpos($kommentti, "###") !== FALSE) {
					list($o, $v) = explode("###", $kommentti);

					$oikpos = $pdf->strlen($o, $boldi);

					if ($oikpos > $maxoik) {
						$maxoik = $oikpos;
					}
				}
			}

			foreach($kommentit as $kommentti) {

				if (strpos($kommentti, "###") !== false) {
					list($o, $v) = explode("###", $kommentti);

					$pdf->draw_text(35, $pohja, trim($o), $thispage, $boldi);
					$pdf->draw_text(35+$maxoik+5, $pohja, trim($v), $thispage, $norm);
				}
				else {
					$pdf->draw_text(35, $pohja, trim($kommentti), $thispage, $norm);
				}

				$pohja = $pohja - 10;
			}

			$kala = $pohja-10;

		}
		else {
			$kala = 565;
		}

		rivi_otsikot($thispage);

		return($thispage);
	}
}

if (!function_exists('uusi_sivu')) {
	function uusi_sivu () {
		global $yhtiorow, $kukarow, $toim, $pdf, $laskurow, $sivu, $rectparam, $norm, $pieni, $maksuehto, $kateistyyppi, $kieli, $boldi, $kala;

		$thispage = $pdf->new_page("a4");

		if (trim($yhtiorow["lasku_logo"]) != '' and file_exists($yhtiorow["lasku_logo"])) {
			$filename = $yhtiorow["lasku_logo"];

			$fh = fopen($filename, "r");
			$data = fread($fh, filesize($filename));
			fclose($fh);

			$image = $pdf->jfif_embed($data);

			if(!$image) {
				echo t("Logokuvavirhe");
			}
			else {
				$logoparam = array();
				$logoparam['scale'] = 0.15;

				$isizelogo = getimagesize($yhtiorow["lasku_logo"]);
				$iy    = $isizelogo[1]*0.15*0.35714;	// kuvan y-korkeus millimetreiss‰

				$placement = $pdf->image_place($image, mm_pt(292-$iy), mm_pt(7), $thispage, $logoparam);
			}
		}
		else {
			$pdf->draw_text(30, 815,  $yhtiorow["nimi"], $thispage);
		}

		//Otsikko
		if ($laskurow["vienti"] == "") {
			if ($laskurow["summa"] >= 0) {
				if ($toim == 'PROFORMA') {
					$pdf->draw_text(310, 815, t("Proformalasku", $kieli), $thispage);
				}
				else {
					$pdf->draw_text(310, 815, t("Lasku", $kieli), $thispage);
				}
			}
			else {
				$pdf->draw_text(310, 815, t("Hyvityslasku", $kieli), $thispage);
			}
		}
		else {
			if ($toim == 'PROFORMA') {
				$pdf->draw_text(310, 815, t("Proformalasku", $kieli), $thispage);
			}
			elseif ($kateiskuitti != ""){
				$pdf->draw_text(310, 815, t("K‰teiskuitti", $kieli),  $thispage);
			}
			elseif ($laskurow["summa"] >= 0) {
				$pdf->draw_text(310, 815, t("Vientilasku", $kieli), $thispage);
			}
			else {
				$pdf->draw_text(310, 815, t("Viennin hyvityslasku", $kieli), $thispage);
			}
		}

		$pdf->draw_text(430, 815, t("Sivu", $kieli), $thispage, $norm);

		//Oikea sarake
		$pdf->draw_rectangle(800, 300, 779, 580, 					$thispage, $rectparam);
		$pdf->draw_rectangle(800, 420, 779, 580, 					$thispage, $rectparam);
		$pdf->draw_text(310, 792, t("Laskun pvm", $kieli), 			$thispage, $pieni);
		$pdf->draw_text(310, 782, tv1dateconv($laskurow["tapvm"]),	$thispage, $norm);
		$pdf->draw_text(430, 792, t("Er‰p‰iv‰", $kieli), 			$thispage, $pieni);

		if ($kateistyyppi != '') {
			$erapaiva = t('MAKSETTU');
		}
		else {
			$erapaiva = tv1dateconv($laskurow["erpcm"]);
		}

		$pdf->draw_text(430, 782, $erapaiva, $thispage, $norm);

		$pdf->draw_rectangle(779, 300, 758, 580, 				$thispage, $rectparam);
		$pdf->draw_rectangle(779, 420, 758, 580, 				$thispage, $rectparam);
		$pdf->draw_text(310, 771, t("Viitenumero", $kieli), 	$thispage, $pieni);
		$pdf->draw_text(310, 761, $laskurow["viite"], 			$thispage, $norm);
		$pdf->draw_text(430, 771, t("Laskun numero", $kieli), 	$thispage, $pieni);
		$pdf->draw_text(430, 761, $laskurow["laskunro"], 		$thispage, $boldi);

		$kala = 735;

		rivi_otsikot($thispage);

		return($thispage);
	}
}

if (!function_exists('rivi_otsikot')) {
	function rivi_otsikot ($thispage) {
		global $yhtiorow, $kukarow, $toim, $pdf, $laskurow, $sivu, $rectparam, $norm, $pieni, $maksuehto, $kateistyyppi, $kieli, $boldi, $kala;

		//Laskurivien otsikkotiedot
		if ($yhtiorow['laskutyyppi'] == 1 or $yhtiorow['laskutyyppi'] == 4) {
			//eka rivi
			$pdf->draw_text(20,  $kala, t("Tuotenimi", $kieli),				$thispage, $pieni);

			if ($yhtiorow['laskutyyppi'] == 1) {
				$pdf->draw_text(210, $kala, t("Tuotenumero", $kieli),			$thispage, $pieni);
			}
			else {
				$pdf->draw_text(250, $kala, t("Tuotenumero", $kieli),			$thispage, $pieni);
			}
			$pdf->draw_text(360, $kala, t("M‰‰r‰", $kieli),					$thispage, $pieni);
			$pdf->draw_text(430, $kala, t("A-hinta", $kieli),				$thispage, $pieni);
			$pdf->draw_text(475, $kala, t("Alennus", $kieli)."-%",			$thispage, $pieni);
			$pdf->draw_text(530, $kala, t("Veroton arvo", $kieli),			$thispage, $pieni);

			//toka rivi
			$pdf->draw_text(20,  $kala-9, t("Lis‰tietoja", $kieli),			$thispage, $pieni);
		}
		else {
			//eka rivi
			$pdf->draw_text(35,  $kala, t("Tuotenimi", $kieli),			$thispage, $pieni);
			$pdf->draw_text(190, $kala, t("Tuotenumero", $kieli),		$thispage, $pieni);
			$pdf->draw_text(340, $kala, t("M‰‰r‰", $kieli),				$thispage, $pieni);
			$pdf->draw_text(390, $kala, t("A-hinta", $kieli),			$thispage, $pieni);
			$pdf->draw_text(450, $kala, t("Alennus", $kieli)."-%",		$thispage, $pieni);
			$pdf->draw_text(490, $kala, t("Veroton arvo", $kieli),		$thispage, $pieni);
			$pdf->draw_text(550, $kala, t("Alv", $kieli)."-%",			$thispage, $pieni);

			//toka rivi
			$pdf->draw_text(35,  $kala-9, t("Tilausnumero", $kieli)."/".t("Lis‰tietoja", $kieli),		$thispage, $pieni);
			$pdf->draw_text(340, $kala-9, t("Yksikkˆ", $kieli),			$thispage, $pieni);
			$pdf->draw_text(390, $kala-9, t("Toimituspvm", $kieli),		$thispage, $pieni);
			$pdf->draw_text(490, $kala-9, t("Verollinen arvo", $kieli),	$thispage, $pieni);
		}

		$kala -= 25;
	}
}

if (!function_exists('rivi')) {
	function rivi ($thispage) {
		global $yhtiorow, $kukarow, $toim, $pdf, $laskurow, $sivu, $rectparam, $norm, $pieni, $maksuehto, $kateistyyppi, $kieli, $boldi, $kala;

		if ($yhtiorow['laskutyyppi'] == 1) {
			rivi_plain($thispage);
			return;
		}
		elseif ($yhtiorow['laskutyyppi'] == 2) {
			rivi_perhe($thispage);
			return;
		}
		elseif ($yhtiorow['laskutyyppi'] == 4) {
			rivi_plain_2($thispage);
			return;
		}
		else {
			rivi_original($thispage);
			return;
		}
	}
}

if (!function_exists('rivi_original')) {
	function rivi_original ($thispage) {
		global $yhtiorow, $kukarow, $toim, $pdf, $page, $row, $sivu, $rectparam, $norm, $summa, $arvo, $pieni, $kieli, $boldi, $kala, $laskurow;

		if ($kala <= 180) {
			$sivu++;

			loppu($thispage);

			$page[$sivu] = uusi_sivu();
			$thispage    = $page[$sivu];
		}

		if (strtolower($kieli) != strtolower($yhtiorow['kieli'])) {
			$query = "	SELECT selite
						FROM tuotteen_avainsanat
						WHERE yhtio = '$kukarow[yhtio]'
						and laji 	= 'nimitys_".strtolower($kieli)."'
						and tuoteno = '$row[tuoteno]'
						and selite != ''
						LIMIT 1";
			$nimresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($nimresult) > 0) {
				$nimrow = mysql_fetch_array($nimresult);
				$row['nimitys'] = $nimrow['selite'];
			}
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

		//Tuoteno
		$pdf->draw_text(35,  $kala, substr($row["nimitys"],0,22),						$thispage, $norm);

		//Nimitys
		$pdf->draw_text(190, $kala, $row["tuoteno"], 									$thispage, $norm);

		//M‰‰r‰
		$pdf->draw_text(340, $kala, $row["kpl"], 										$thispage, $norm);

		// A-hinta (voi olla verollinen tai veroton riippuen yhtiˆst‰)
		$pdf->draw_text(390, $kala, sprintf("%.".$yhtiorow['hintapyoristys']."f", laskuval($row["hinta"], $kurssi)),	$thispage, $norm);

		//Alennus
		if ($row["ale"] != 0) {
			$pdf->draw_text(450, $kala, ($row["ale"]*1),								$thispage, $norm);
		}

		//Rivihinta valuutassa
		if ($toim != "PROFORMA" and $kurssi != 1) {
			// T‰n rivin alviton rivihinta
			$row["rivihinta"] = laskuval($row["hinta"], $kurssi)*$row["kpl"]*(1-$row["ale"]/100);

			if ($yhtiorow["alv_kasittely"] == '') {
				$row["rivihinta"] = $row["rivihinta"] / (1+$row["alv"]/100);
			}
		}

		//Rivihinta
		$oikpos = $pdf->strlen(sprintf("%.".$yhtiorow['hintapyoristys']."f", $row["rivihinta"]), $norm);
		$pdf->draw_text(535-$oikpos, $kala, sprintf("%.".$yhtiorow['hintapyoristys']."f", $row["rivihinta"]), 		$thispage, $norm);

		//Arvonlis‰vero
		if ($row["alv"] >= 500) {
			$pdf->draw_text(550, $kala, t("M.V."),	 									$thispage, $norm);
		}
		else {
			$pdf->draw_text(550, $kala, ($row["alv"]*1)."%",							$thispage, $norm);
		}

		// Ensimm‰inen rivi piirretty
		$kala = $kala - 10;

		//Yksikkˆ
		$pdf->draw_text(340, $kala, $row["yksikko"],									$thispage, $pieni);

		//Toimitusaika
		if (substr($row["toimitettuaika"],0,10) != "0000-00-00") {
			$pdf->draw_text(390, $kala, tv1dateconv(substr($row["toimitettuaika"],0,10)), 	$thispage, $pieni);
		}

		//Verollinen rivihinta
		if ($row["alv"] < 500) {
			$oikpos = $pdf->strlen(sprintf("%.".$yhtiorow['hintapyoristys']."f", $row["rivihinta"]*(1+($row["alv"]/100))), $pieni);
			$pdf->draw_text(535-$oikpos, $kala, sprintf("%.".$yhtiorow['hintapyoristys']."f", $row["rivihinta"]*(1+($row["alv"]/100))), $thispage, $pieni);
		}

		//Jos rivikommentti on pitk‰ niin teemme siit‰ kauniimman
		if (strlen($row["kommentti"]) >= 140) {

			$row["kommentti"] = str_replace(' ' ,' ##', $row["kommentti"]);
			$row["kommentti"] = str_replace('.' ,'.##', $row["kommentti"]);
			$row["kommentti"] = str_replace(',' ,',##', $row["kommentti"]);

			$sanat = explode('##', $row["kommentti"]);

			$lauseet = array();
			$ind = 0;
			foreach($sanat as $sana) {
				if (strlen($lauseet[$ind]) >= 100) {
					$lauseet[$ind] .= "\n";
					$ind++;
				}
				$lauseet[$ind] .= $sana;
			}

			$row["kommentti"] = "";

			foreach($lauseet as $lause) {
				$row["kommentti"] .= $lause;
			}
		}

		// Jos ALV no yli 500 niin tied‰mme ett‰ t‰m‰ on marginaaliverotusta
		if ($row["alv"] >= 500) {
			$row["kommentti"] .= "\nEi sis‰ll‰ v‰hennett‰v‰‰ veroa.";
		}

		//Haetan sarjanumeron tiedot
		if ($row["varattu"]+$row["kpl"] < 0){
			$sarjanutunnus = "ostorivitunnus";
		}
		else {
			$sarjanutunnus = "myyntirivitunnus";
		}

		$query = "	select distinct sarjanumero
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
			$row["kommentti"] .= t("S:nro").": $sarjarow[sarjanumero]\n";
		}

		//Kommentti
		if (trim($row["kommentti"]) != '') {
			$kommentit = explode("\n", trim($row["kommentti"]));
			$pohja = $kala;

			foreach($kommentit as $kommentti) {
				$pdf->draw_text(35, $pohja, $row["otunnus"]."/".$kommentti, $thispage, $pieni);
				$pohja = $pohja - 10;
			}
			$pohja += 10;
		}
		else {
			$pdf->draw_text(35, $kala, $row["otunnus"],	$thispage, $pieni);
			$pohja = $kala;
		}

		if ($pohja < $kala) {
			$kala = $pohja;
		}

		$kala = $kala - 15;

		$arvo	+= $row["rivihinta"];
		$summa 	+= sprintf("%.".$yhtiorow['hintapyoristys']."f", $row["rivihinta"]*(1+($row["alv"]/100)));
	}
}

if (!function_exists('rivi_plain')) {
	function rivi_plain ($thispage) {
		global $yhtiorow, $kukarow, $toim, $pdf, $page, $row, $sivu, $rectparam, $norm, $summa, $arvo, $pieni, $kieli, $boldi, $kala, $laskurow;

		if ($kala <= 180) {
			$sivu++;

			loppu($thispage);

			$page[$sivu] = uusi_sivu();
			$thispage    = $page[$sivu];
		}

		if (strtolower($kieli) != strtolower($yhtiorow['kieli'])) {
			$query = "	SELECT selite
						FROM tuotteen_avainsanat
						WHERE yhtio = '$kukarow[yhtio]'
						and laji 	= 'nimitys_".strtolower($kieli)."'
						and tuoteno = '$row[tuoteno]'
						and selite != ''
						LIMIT 1";
			$nimresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($nimresult) > 0) {
				$nimrow = mysql_fetch_array($nimresult);
				$row['nimitys'] = $nimrow['selite'];
			}
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

		$pdf->draw_text(20,  $kala, substr($row["nimitys"],0,27),											$thispage, $norm);
		$pdf->draw_text(210, $kala, $row["tuoteno"], 														$thispage, $norm);

		$oikpos = $pdf->strlen($row["kpl"], $norm);
		$pdf->draw_text(380-$oikpos, $kala, $row["kpl"], 													$thispage, $norm);

		// A-hinta (voi olla verollinen tai veroton riippuen yhtiˆst‰)
		$oikpos = $pdf->strlen(sprintf("%.".$yhtiorow['hintapyoristys']."f", laskuval($row["hinta"], $kurssi)), $norm);
		$pdf->draw_text(455-$oikpos, $kala, sprintf("%.".$yhtiorow['hintapyoristys']."f", laskuval($row["hinta"], $kurssi)),	$thispage, $norm);

		//Alennus
		if ($row["ale"] != 0) {
			$oikpos = $pdf->strlen(($row["ale"]*1), $norm);
			$pdf->draw_text(510-$oikpos, $kala, ($row["ale"]*1), 			$thispage, $norm);
		}

		//Rivihinta valuutassa
		if ($toim != "PROFORMA" and $kurssi != 1) {
			// T‰n rivin alviton rivihinta
			$row["rivihinta"] = laskuval($row["hinta"], $kurssi)*$row["kpl"]*(1-$row["ale"]/100);

			if ($yhtiorow["alv_kasittely"] == '') {
				$row["rivihinta"] = $row["rivihinta"] / (1+$row["alv"]/100);
			}
		}

		//Rivihinta
		$oikpos = $pdf->strlen(sprintf("%.".$yhtiorow['hintapyoristys']."f", $row["rivihinta"]), $norm);
		$pdf->draw_text(575-$oikpos, $kala, sprintf("%.".$yhtiorow['hintapyoristys']."f", $row["rivihinta"]), 			$thispage, $norm);

		$kala = $kala - 10;

		$pdf->draw_text(20,  $kala, $row["kommentti"],	$thispage, $pieni);

		$kala = $kala - 15;

		$arvo	+= $row["rivihinta"];
		$summa 	+= sprintf("%.".$yhtiorow['hintapyoristys']."f", $row["rivihinta"]*(1+($row["alv"]/100)));
	}
}

if (!function_exists('rivi_plain_2')) {
	function rivi_plain_2 ($thispage) {
		global $yhtiorow, $kukarow, $toim, $pdf, $page, $row, $sivu, $rectparam, $norm, $summa, $arvo, $pieni, $kieli, $boldi, $kala, $laskurow;

		if ($kala <= 180) {
			$sivu++;

			loppu($thispage);

			$page[$sivu] = uusi_sivu();
			$thispage    = $page[$sivu];
		}

		if (strtolower($kieli) != strtolower($yhtiorow['kieli'])) {
			$query = "	SELECT selite
						FROM tuotteen_avainsanat
						WHERE yhtio = '$kukarow[yhtio]'
						and laji 	= 'nimitys_".strtolower($kieli)."'
						and tuoteno = '$row[tuoteno]'
						and selite != ''
						LIMIT 1";
			$nimresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($nimresult) > 0) {
				$nimrow = mysql_fetch_array($nimresult);
				$row['nimitys'] = $nimrow['selite'];
			}
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

		// rivitet‰‰n tuotenimitys
		// jos pidempi kuin 68 merkki‰ niin tehd‰‰n pidempi kommenttirivi
		// ja rivitet‰‰n
		if (strlen($row['nimitys']) > 68) {
			$kala2 = $kala;
			$rivit = explode("\n", wordwrap($row['nimitys'], 90, "\n"));
			$pos = 0;
			for ($i=0; $i<count($rivit); $i++) {
				$kala2 = $kala - (15 * $i);
				$pdf->draw_text(20,  $kala2, $rivit[$i],	$thispage, $norm);
				$pos += 27;
			}

			// asetetaan kala takaisin oikeaksi
			$kala = $kala2;
			$kala -= 15;
		}
		else {
			// lyhyt kommentti
			$pdf->draw_text(20,  $kala, $row['nimitys'],	$thispage, $norm);
		}

		$pdf->draw_text(250, $kala, $row["tuoteno"], 	$thispage, $norm);

		$oikpos = $pdf->strlen($row["kpl"], $norm);
		$pdf->draw_text(380-$oikpos, $kala, $row["kpl"], 			$thispage, $norm);


		// A-hinta (voi olla verollinen tai veroton riippuen yhtiˆst‰)
		$oikpos = $pdf->strlen(sprintf("%.".$yhtiorow['hintapyoristys']."f", laskuval($row["hinta"], $kurssi)), $norm);
		$pdf->draw_text(455-$oikpos, $kala, sprintf("%.".$yhtiorow['hintapyoristys']."f", laskuval($row["hinta"], $kurssi)), 			$thispage, $norm);


		//Alennus
		if ($row["ale"] != 0) {
			$oikpos = $pdf->strlen(($row["ale"]*1), $norm);
			$pdf->draw_text(510-$oikpos, $kala, ($row["ale"]*1), 			$thispage, $norm);
		}

		//Rivihinta valuutassa
		if ($toim != "PROFORMA" and $kurssi != 1) {
			// T‰n rivin alviton rivihinta
			$row["rivihinta"] = laskuval($row["hinta"], $kurssi)*$row["kpl"]*(1-$row["ale"]/100);

			if ($yhtiorow["alv_kasittely"] == '') {
				$row["rivihinta"] = $row["rivihinta"] / (1+$row["alv"]/100);
			}
		}

		//Rivihinta
		$oikpos = $pdf->strlen(sprintf("%.".$yhtiorow['hintapyoristys']."f", $row["rivihinta"]), $norm);
		$pdf->draw_text(575-$oikpos, $kala, sprintf("%.".$yhtiorow['hintapyoristys']."f", $row["rivihinta"]), 			$thispage, $norm);

		$kala = $kala - 10;

		$pdf->draw_text(20,  $kala, $row["kommentti"],	$thispage, $pieni);

		$kala = $kala - 15;

		$arvo	+= $row["rivihinta"];
		$summa 	+= sprintf("%.".$yhtiorow['hintapyoristys']."f", $row["rivihinta"]*(1+($row["alv"]/100)));
	}
}

if (!function_exists('rivi_perhe')) {
	function rivi_perhe ($thispage) {
		global $yhtiorow, $kukarow, $toim, $pdf, $page, $row, $sivu, $rectparam, $norm, $summa, $arvo, $pieni, $kieli, $boldi, $kala, $laskurow;

		$lisataa = 0;

		if ($row["perheid"] > 0) {

			// kyseess‰ on is‰
			if ($row["perheid"] == $row["tunnus"]) {

				$query = "	SELECT vanhatunnus
							FROM lasku
							WHERE yhtio	= '$kukarow[yhtio]'
							and tunnus	= '$row[otunnus]'";
				$vtunres = mysql_query($query) or pupe_error($query);
				$vtunrow = mysql_fetch_array($vtunres);

				if ($vtunrow["vanhatunnus"] != 0) {
					$query = " SELECT GROUP_CONCAT(distinct tunnus SEPARATOR ',') tunnukset
							FROM lasku use index (yhtio_vanhatunnus)
							WHERE yhtio         = '$kukarow[yhtio]'
							and vanhatunnus     = '$vtunrow[vanhatunnus]'";
					$perheresult = mysql_query($query) or pupe_error($query);
					$numrows = mysql_num_rows($perheresult);
				}

				if ($numrows == 0 or $vtunrow["vanhatunnus"] == 0) {
					$query = "  SELECT GROUP_CONCAT(distinct tunnus SEPARATOR ',') tunnukset
								FROM lasku use index (PRIMARY)
								WHERE yhtio    = '$kukarow[yhtio]'
								and tunnus    = '$row[otunnus]'";
					$perheresult = mysql_query($query) or pupe_error($query);
				}

				if (mysql_num_rows($perheresult) > 0) {
					$perherow = mysql_fetch_array($perheresult);

					// lasketaan is‰tuotteen riville lapsien hinnat yhteen
					$query = "	SELECT
								sum(tilausrivi.rivihinta) rivihinta,
								round(sum(tilausrivi.rivihinta) / ($row[kpl]), '$yhtiorow[hintapyoristys]') hinta
								FROM tilausrivi
								WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
								and tilausrivi.otunnus in ($perherow[tunnukset])
								and tilausrivi.perheid = '$row[perheid]'";
					$riresult = mysql_query($query) or pupe_error($query);
					$perherow = mysql_fetch_array($riresult);

					$row["hinta"] 		= $perherow["hinta"];
					$row["rivihinta"] 	= $perherow["rivihinta"];
					$row["ale"] 		= "";
				}
			}
			else {
				// lapsia ei lis‰t‰
				$lisataa = 1;
			}
		}

		if ($lisataa == 0) {
			rivi_original ($thispage);
		}
	}
}

if (!function_exists('loppu')) {
	function loppu ($thispage, $vikasivu = "") {

		global $yhtiorow, $kukarow, $toim, $pdf, $page, $sivu, $laskurow, $sivu, $rectparam, $norm, $summa, $arvo, $pieni, $kieli, $masrow, $boldi, $uytunnus, $ytunpit, $pieni_boldi, $kateistyyppi;

		if($vikasivu != "") {
			for ($pp=1; $pp<=$sivu; $pp++) {
				$pdf->draw_text(460, 815, "$pp / $sivu", $page[$pp], $norm);
			}
		}

		$vientikala = 0;

		if ($laskurow["vienti"] != "" or strtoupper($laskurow["maa"]) != strtoupper($yhtiorow["maa"])) {
			$vientikala = 20;
		}

		//yhteens‰rivi
		$pdf->draw_rectangle(110+$vientikala, 20, 90+$vientikala, 580,	$thispage, $rectparam);
		$pdf->draw_rectangle(110+$vientikala, 207, 90+$vientikala, 580,	$thispage, $rectparam);
		$pdf->draw_rectangle(110+$vientikala, 394, 90+$vientikala, 580,	$thispage, $rectparam);
		$pdf->draw_rectangle(110+$vientikala, 540, 90+$vientikala, 580,	$thispage, $rectparam);

		$pdf->draw_text(30,  102+$vientikala, t("Er‰p‰iv‰", $kieli),	$thispage, $pieni);

		if ($kateistyyppi != '') {
			$erapaiva = t('MAKSETTU', $kieli);
		}
		else {
			$erapaiva = tv1dateconv($laskurow["erpcm"]);
		}

		$pdf->draw_text(30,  92+$vientikala,  $erapaiva,		$thispage, $norm);
		$pdf->draw_text(217, 102+$vientikala, t("Viitenumero", $kieli),	$thispage, $pieni);
		$pdf->draw_text(217, 92+$vientikala,  $laskurow["viite"],		$thispage, $norm);



		if ($vikasivu != "") {
			$pdf->draw_text(404, 92+$vientikala,  t("Yhteens‰", $kieli).":",$thispage, $norm);

			if ($toim == 'PROFORMA') {
				// Etsit‰‰n asiakas
				$query = "	SELECT laskunsummapyoristys
							FROM asiakas
							WHERE tunnus='$laskurow[liitostunnus]' and yhtio='$kukarow[yhtio]'";
				$asres = mysql_query($query) or pupe_error($query);
				$asrow = mysql_fetch_array($asres);

				if ($yhtiorow["laskunsummapyoristys"] == 'o' or $asrow["laskunsummapyoristys"] == 'o') {
			        $summa = round($summa,0);
				}

				$pdf->draw_text(490, 92+$vientikala,  sprintf('%.2f', $summa),			$thispage, $boldi);
			}
			elseif ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
				$oikpos = $pdf->strlen(sprintf('%.2f', $laskurow["summa_valuutassa"]), $boldi);
				$pdf->draw_text(535-$oikpos, 92+$vientikala, sprintf('%.2f', $laskurow["summa_valuutassa"]), $thispage, $boldi);
			}
			else {
				$oikpos = $pdf->strlen(sprintf('%.2f', $laskurow["summa"]), $boldi);
				$pdf->draw_text(535-$oikpos, 92+$vientikala, sprintf('%.2f', $laskurow["summa"]), $thispage, $boldi);
			}

			$pdf->draw_text(550, 92+$vientikala,  $laskurow["valkoodi"],				$thispage, $boldi);
		}
		else {
			$pdf->draw_text(470, 92+$vientikala,  t("Jatkuu", $kieli)."...",			$thispage, $boldi);
		}

		//Pankkiyhteystiedot
		$pdf->draw_rectangle(90+$vientikala, 20, 20, 580,	$thispage, $rectparam);

		// jos meill‰ on lasku menossa ulkomaille ja se laskutetaan eri ovttunnuksella
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
				$yhtiorow['email']		= $alhiro['email'];

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

		//Haetaan factoringsopimuksen tiedot
		if ($masrow["factoring"] != '') {
			$query = "	SELECT *
						FROM factoring
						WHERE yhtio 		= '{$kukarow['yhtio']}'
						and factoringyhtio 	= '{$masrow['factoring']}'
						and valkoodi 		= '{$lasrow['valkoodi']}'";
			$fres = mysql_query($query) or pupe_error($query);
			$frow = mysql_fetch_array($fres);
		}
		else {
			unset($frow);
		}

		$pankkitiedot = array();

		//Laitetaan pankkiyhteystiedot kuntoon
		if ($masrow["factoring"] != "") {
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

	    if ($masrow["factoring"] != '') {
			// jos factoroidaan tulostaan eri kent‰t
			$query = "	SELECT *
						FROM factoring
						WHERE yhtio 		= '$kukarow[yhtio]'
						and factoringyhtio 	= '$masrow[factoring]'
						and valkoodi 		= '$laskurow[valkoodi]'";
			$fres = mysql_query($query) or pupe_error($query);
			$frow = mysql_fetch_array($fres);

			if ($laskurow["vienti"] != "" or strtoupper($laskurow["maa"]) != strtoupper($yhtiorow["maa"])) {
				// jos on vienti
				$pdf->draw_text(30,  102, t("Pankkiyhteys", $kieli),						$thispage, $pieni);
				$pdf->draw_text(30,  92, $frow["pankkinimi1"]."  ".$frow["pankkitili1"],	$thispage, $norm);

				if($frow["pankkiiban1"]) {
					$pdf->draw_text(217, 92, "IBAN: " .$frow["pankkiiban1"],				$thispage, $norm);
				}
				if($frow["pankkiswift1"]) {
					$pdf->draw_text(404, 92, "SWIFT: ".$frow["pankkiswift1"],				$thispage, $norm);
				}

				$pdf->draw_text(30,  82, $frow["pankkinimi2"]."  ".$frow["pankkitili2"],	$thispage, $norm);

				if($frow["pankkiiban2"]) {
					$pdf->draw_text(217, 82, "IBAN: " .$frow["pankkiiban2"],					$thispage, $norm);
				}
				if($frow["pankkiswift2"]) {
					$pdf->draw_text(404, 82, "SWIFT: ".$frow["pankkiswift2"],					$thispage, $norm);
				}
			}
			else {
				// ja kotimaisissa t‰ll‰st‰
				$pdf->draw_text(30, 82,  t("Pankkiyhteys", $kieli),							$thispage, $pieni);
	        	$pdf->draw_text(30, 72,  $frow["pankkinimi1"]." ".$frow["pankkitili1"],		$thispage, $norm);
	        	$pdf->draw_text(217, 72, $frow["pankkinimi2"]." ".$frow["pankkitili2"],		$thispage, $norm);
			}
		}
		else {
			// ei factoroida niin normitiedot
			if ($laskurow["vienti"] != "" or strtoupper($laskurow["maa"]) != strtoupper($yhtiorow["maa"])) {
				// vienniss‰ t‰ll‰st‰
				$pdf->draw_text(30,  102, t("Pankkiyhteys", $kieli),													$thispage, $pieni);

				$pdf->draw_text(30,  92, $pankkitiedot["pankkinimi1"]."  ".$pankkitiedot["pankkitili1"],						$thispage, $norm);

				if($pankkitiedot["pankkiiban1"]) {
					$pdf->draw_text(217, 92, "IBAN: ".$pankkitiedot["pankkiiban1"],											$thispage, $norm);
				}
				if($pankkitiedot["pankkiswift1"]) {
					$pdf->draw_text(404, 92, "SWIFT: ".$pankkitiedot["pankkiswift1"],										$thispage, $norm);
				}

				$pdf->draw_text(30,  82, $pankkitiedot["pankkinimi2"]."  ".$pankkitiedot["pankkitili2"],						$thispage, $norm);

				if($pankkitiedot["pankkiiban2"]) {
					$pdf->draw_text(217, 82, "IBAN: ".$pankkitiedot["pankkiiban2"],											$thispage, $norm);
				}
				if($pankkitiedot["pankkiswift2"]) {
					$pdf->draw_text(404, 82, "SWIFT: ".$pankkitiedot["pankkiswift2"],										$thispage, $norm);
				}

				$pdf->draw_text(30,  72, $pankkitiedot["pankkinimi3"]."  ".$pankkitiedot["pankkitili3"],						$thispage, $norm);

				if($pankkitiedot["pankkiiban3"]) {
					$pdf->draw_text(217, 72, "IBAN: ".$pankkitiedot["pankkiiban3"],											$thispage, $norm);
				}
				if($pankkitiedot["pankkiswift3"]) {
					$pdf->draw_text(404, 72, "SWIFT: ".$pankkitiedot["pankkiswift3"],										$thispage, $norm);
				}
			} else {
				// ja normitilauksissa t‰ll‰st‰
			    $pdf->draw_text(30, 82,  t("Pankkiyhteys", $kieli),														$thispage, $pieni);
	        	$pdf->draw_text(30, 72,  $pankkitiedot["pankkinimi1"]." ".$pankkitiedot["pankkitili1"],							$thispage, $norm);
	        	$pdf->draw_text(217, 72, $pankkitiedot["pankkinimi2"]." ".$pankkitiedot["pankkitili2"],							$thispage, $norm);
	        	$pdf->draw_text(404, 72, $pankkitiedot["pankkinimi3"]." ".$pankkitiedot["pankkitili3"],							$thispage, $norm);
            }
		}

		//Alimmat kolme laatikkoa, yhtiˆtietoja
		$pdf->draw_rectangle(70, 20, 20, 580,	$thispage, $rectparam);
		$pdf->draw_rectangle(70, 207, 20, 580,	$thispage, $rectparam);
		$pdf->draw_rectangle(70, 394, 20, 580,	$thispage, $rectparam);

		$pdf->draw_text(30, 55, $yhtiorow["nimi"],		$thispage, $pieni);
		$pdf->draw_text(30, 45, $yhtiorow["osoite"],	$thispage, $pieni);
		$pdf->draw_text(30, 35, $yhtiorow["postino"]."  ".$yhtiorow["postitp"],	$thispage, $pieni);
		$pdf->draw_text(30, 25, $yhtiorow["maa"],		$thispage, $pieni);

		$pdf->draw_text(217, 55, t("Puhelin", $kieli).":",			$thispage, $pieni);
		$pdf->draw_text(257, 55, $yhtiorow["puhelin"],				$thispage, $pieni);
		$pdf->draw_text(217, 45, t("Telefax", $kieli).":",			$thispage, $pieni);
		$pdf->draw_text(257, 45, $yhtiorow["fax"],					$thispage, $pieni);
		$pdf->draw_text(217, 35, t("S‰hkˆposti", $kieli).":",		$thispage, $pieni);
		$pdf->draw_text(257, 35, $yhtiorow["email"],				$thispage, $pieni);

		//jos on suomalainen yritys tehd‰‰n ytunnus n‰tiks
		if (strtoupper($yhtiorow["maa"])== 'FI') {
			//muutetaan ytunnus takas oikean n‰kˆseks
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
        }
		else {
    		$uytunnus = $yhtiorow["maa"]."-".$uytunnus;
        }


		$pdf->draw_text(404, 55, t("Y-tunnus", $kieli).":",			$thispage, $pieni);
		$pdf->draw_text(444, 55, $uytunnus,							$thispage, $pieni);
		$pdf->draw_text(404, 45, t("Kotipaikka", $kieli).":",		$thispage, $pieni);
		$pdf->draw_text(444, 45, $yhtiorow["kotipaikka"],			$thispage, $pieni);
		$pdf->draw_text(404, 35, t("Enn.per.rek", $kieli),			$thispage, $pieni);
		$pdf->draw_text(404, 25, t("Alv.rek", $kieli),				$thispage, $pieni);
	}
}

if (!function_exists('alvierittely')) {
	function alvierittely ($thispage) {
		global $yhtiorow, $kukarow, $toim, $pdf, $laskurow, $sivu, $rectparam, $norm, $pieni, $summa, $arvo, $kala, $kieli, $boldi;

		if ($kala <= 200) {
			$sivu++;

			loppu($thispage);

			$page[$sivu] = uusi_sivu();
			$thispage    = $page[$sivu];
		}

		$kala -=10;
		//loppusummat vain vikalle sivulle
		$pdf->draw_text(360, $kala, t("Veroton arvo yhteens‰", $kieli).":", 		$thispage, $norm);

		if ($toim == 'PROFORMA' or ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"])))) {
			$oikpos = $pdf->strlen(sprintf('%.2f', $arvo), $norm);
			$pdf->draw_text(535-$oikpos, $kala, sprintf('%.2f', $arvo), 			$thispage, $norm);
		}
		else {
			$oikpos = $pdf->strlen(sprintf('%.2f', $laskurow["arvo"]), $norm);
			$pdf->draw_text(535-$oikpos, $kala, sprintf('%.2f', $laskurow["arvo"]), $thispage, $norm);
		}

		$pdf->draw_text(550, $kala, $laskurow["valkoodi"], 							$thispage, $norm);


		if ($toim == 'PROFORMA') {
			$where = " otunnus = '$laskurow[tunnus]' ";
		}
		else {
			$where = " uusiotunnus = '$laskurow[tunnus]' ";
		}

		//Haetaan kaikki alvikannat riveilt‰
		$alvquery = "	SELECT distinct alv
						FROM tilausrivi
						WHERE $where
						and yhtio	= '$kukarow[yhtio]'
						and tyyppi	= 'L'
						ORDER BY alv";
		$alvresult = mysql_query($alvquery) or pupe_error($alvquery);
		$kala -= 10;

		while ($alvrow = mysql_fetch_array($alvresult)) {

			// Valuuttajutut kuntoon
			if ($alvrow["alv"] >= 500 and $toim != 'PROFORMA') {
				$aquery = "	SELECT
							round(sum(0),2) alvrivihinta,
							round(sum(tilausrivi.rivihinta/if(lasku.vienti_kurssi>0, lasku.vienti_kurssi, 1)),2) rivihinta
							FROM tilausrivi
							JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
							WHERE tilausrivi.uusiotunnus = '$laskurow[tunnus]' and tilausrivi.yhtio='$kukarow[yhtio]' and tilausrivi.alv='$alvrow[alv]' and tilausrivi.tyyppi='L'";
			}
			elseif ($alvrow["alv"] >= 500) {
				$aquery = "	SELECT
							round(sum(0),2) alvrivihinta,
							round(sum((tilausrivi.hinta/if(lasku.vienti_kurssi>0, lasku.vienti_kurssi, 1))*tilausrivi.varattu*(1-tilausrivi.ale/100)),2) rivihinta
							FROM tilausrivi
							JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
							WHERE tilausrivi.otunnus = '$laskurow[tunnus]' and tilausrivi.yhtio='$kukarow[yhtio]' and tilausrivi.alv='$alvrow[alv]' and tilausrivi.tyyppi='L'";
			}
			elseif ($alvrow["alv"] < 500 and $toim != 'PROFORMA') {
				$aquery = "	SELECT
							round(sum((tilausrivi.rivihinta/if(lasku.vienti_kurssi>0, lasku.vienti_kurssi, 1))*(tilausrivi.alv/100)),2) alvrivihinta,
							round(sum(tilausrivi.rivihinta/if(lasku.vienti_kurssi>0, lasku.vienti_kurssi, 1)),2) rivihinta
							FROM tilausrivi
							JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
							WHERE tilausrivi.uusiotunnus = '$laskurow[tunnus]' and tilausrivi.yhtio='$kukarow[yhtio]' and tilausrivi.alv='$alvrow[alv]' and tilausrivi.tyyppi='L'";
			}
			else {
				$aquery = "	SELECT
							round(sum((tilausrivi.rivihinta/if(lasku.vienti_kurssi>0, lasku.vienti_kurssi, 1)) / if('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * tilausrivi.varattu * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+$laskurow[erikoisale]-(tilausrivi.ale*$laskurow[erikoisale]/100))/100)) * (tilausrivi.alv/100)),2) alvrivihinta,
							round(sum((tilausrivi.rivihinta/if(lasku.vienti_kurssi>0, lasku.vienti_kurssi, 1)) / if('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * tilausrivi.varattu * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+$laskurow[erikoisale]-(tilausrivi.ale*$laskurow[erikoisale]/100))/100))),2) rivihinta
							FROM tilausrivi
							JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
							WHERE tilausrivi.otunnus = '$laskurow[tunnus]' and tilausrivi.yhtio='$kukarow[yhtio]' and tilausrivi.alv='$alvrow[alv]' and tilausrivi.tyyppi='L'";
			}
			$aresult = mysql_query($aquery) or pupe_error($aquery);
			$arow = mysql_fetch_array($aresult);

			$pdf->draw_text(360, $kala, t("Arvonlis‰vero", $kieli)." (".sprintf('%.2f',$arow["rivihinta"])."): ",	$thispage, $norm);

			$oikpos = $pdf->strlen(sprintf('%.2f', $arow["alvrivihinta"]), $norm);
			$pdf->draw_text(535-$oikpos, $kala, sprintf('%.2f', $arow["alvrivihinta"]), 		$thispage, $norm);

			if ($alvrow["alv"] >= 500) {
				$pdf->draw_text(550, $kala, t("M.V."), 											$thispage, $norm);
			}
			else {
				$pdf->draw_text(550, $kala, ($alvrow["alv"]*1)."%", 							$thispage, $norm);
			}

			$kala -= 10;
		}

		$pdf->draw_text(360, $kala, t("Lasku Yhteens‰", $kieli).":",							$thispage, $norm);

		if ($toim == 'PROFORMA') {
			$oikpos = $pdf->strlen(sprintf('%.2f', $summa), $norm);
			$pdf->draw_text(535-$oikpos, $kala, sprintf('%.2f', $summa), 						$thispage, $norm);
		}
		elseif ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
			$oikpos = $pdf->strlen(sprintf('%.2f', $laskurow["summa_valuutassa"]), $norm);
			$pdf->draw_text(535-$oikpos, $kala, sprintf('%.2f', $laskurow["summa_valuutassa"]), $thispage, $norm);
		}
		else {
			$oikpos = $pdf->strlen(sprintf('%.2f', $laskurow["summa"]), $norm);
			$pdf->draw_text(535-$oikpos, $kala, sprintf('%.2f', $laskurow["summa"]), 			$thispage, $norm);
		}

		$pdf->draw_text(550, $kala, $laskurow["valkoodi"], 										$thispage, $boldi);

		$kala -= 10;

		// Vikan sivun loppulaatikko
		loppu($thispage, 1);
	}
}

?>
