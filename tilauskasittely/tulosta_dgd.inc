<?php

require_once ('Zend/Pdf.php');
require_once ('Zend/Barcode.php');

/**
* Returns the total width in points of the string using the specified font and
* size.
*
* @param string $string
* @param Zend_Pdf_Resource_Font $font
* @param float $fontSize Font size in points
* @return float
*/
function widthForStringUsingFontSize($string, $font, $fontSize) {
	$drawingString = iconv('UTF-8', 'UTF-16BE//IGNORE', $string);
	$characters = array();

	for ($i = 0; $i < strlen($drawingString); $i++) {
		$characters[] = (ord($drawingString[$i++]) << 8) | ord($drawingString[$i]);
	}

	$glyphs = $font->cmap->glyphNumbersForCharacters($characters);
	$widths = $font->widthsForGlyphs($glyphs);
	$stringWidth = (array_sum($widths) / $font->getUnitsPerEm()) * $fontSize;

	return $stringWidth;
}

if (!function_exists('alku_dgd')) {
	function alku_dgd ($params) {
		global $kukarow, $yhtiorow;

		// Luodaan muuttujat
		foreach ($params as $key => $val) {
			${$key} = $val;
		}

		//PDF parametrit
		if ($pdf === NULL) {
			$pdf = Zend_Pdf::load("DGDpohja.pdf");
		}

		// Fontit
		$norm = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);
		$bold = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD);

		$pdf->pages[$sivu]->setFont($norm, 10);
		$pdf->pages[$sivu]->drawText($yhtiorow["nimi"], mm_pt(15), mm_pt(262), 'LATIN1');
		$pdf->pages[$sivu]->drawText($yhtiorow["osoite"], mm_pt(15), mm_pt(258), 'LATIN1');
		$pdf->pages[$sivu]->drawText($yhtiorow["postino"]." ".$yhtiorow["postitp"], mm_pt(15), mm_pt(254), 'LATIN1');
		$pdf->pages[$sivu]->drawText($yhtiorow["maa"], mm_pt(15), mm_pt(250), 'LATIN1');

		$pdf->pages[$sivu]->drawText($laskurow["nimi"], mm_pt(15), mm_pt(242), 'LATIN1');
		$pdf->pages[$sivu]->drawText($laskurow["osoite"], mm_pt(15), mm_pt(238), 'LATIN1');
		$pdf->pages[$sivu]->drawText($laskurow["postino"]." ".$laskurow["postitp"], mm_pt(15), mm_pt(234), 'LATIN1');
		$pdf->pages[$sivu]->drawText($laskurow["maa"], mm_pt(15), mm_pt(230), 'LATIN1');

		// Ruksit
		$pdf->pages[$sivu]->drawLine(mm_pt(15), mm_pt(202), mm_pt(55), mm_pt(197));
		$pdf->pages[$sivu]->drawLine(mm_pt(15), mm_pt(197), mm_pt(55), mm_pt(202));

		$pdf->pages[$sivu]->drawLine(mm_pt(58), mm_pt(202), mm_pt(101), mm_pt(197));
		$pdf->pages[$sivu]->drawLine(mm_pt(58), mm_pt(197), mm_pt(101), mm_pt(202));

		$pdf->pages[$sivu]->drawText($yhtiorow["nimi"], mm_pt(15), mm_pt(61), 'LATIN1');
		$pdf->pages[$sivu]->drawText($kukarow["nimi"], mm_pt(15), mm_pt(50), 'LATIN1');
		$pdf->pages[$sivu]->drawText($yhtiorow["kotipaikka"]." / ".tv1dateconv(date("Y-m-d")), mm_pt(15), mm_pt(41), 'LATIN1');

		$pdf->pages[$sivu]->drawText($yhtiorow["nimi"], mm_pt(143), mm_pt(61), 'LATIN1');
		$pdf->pages[$sivu]->drawText($kukarow["nimi"], mm_pt(143), mm_pt(50), 'LATIN1');
		$pdf->pages[$sivu]->drawText($yhtiorow["kotipaikka"]." / ".tv1dateconv(date("Y-m-d")), mm_pt(143), mm_pt(41), 'LATIN1');

		# Viivoitin
		#$pdf->pages[$sivu]->setLineWidth(0.5);
		#$pdf->pages[$sivu]->setFont($norm, 5);

		#for ($i=5; $i < 295; $i++) {
		#	$pdf->pages[$sivu]->drawText($i, mm_pt(6), mm_pt($i));
		#	$pdf->pages[$sivu]->drawLine(mm_pt(10), mm_pt($i), mm_pt(13), mm_pt($i));
		#}

		# KUVAT:
		#$image = Zend_Pdf_Image::imageWithPath("/tmp/logo.jpg");
		#$pdf->pages[0]->drawImage($image, 20, 830, 150, 800);
		#$pdf->pages[0] = $pdf->newPage(Zend_Pdf_Page::SIZE_A4);
		#$pdf->save("/tmp/kakal.pdf");

		# VIIVAKOODIT:
		#Zend_Barcode::setBarcodeFont("/Library/Fonts/Arial Black.ttf");
		#$barcodeOptions = array('text' => 'ZEND-FRAMEWORK-1');
		#$rendererOptions = array('topOffset' => 50, 'leftOffset' => 50);
		#$pdfWithBarcode = Zend_Barcode::factory('code39', 'pdf', $barcodeOptions, $rendererOptions)->setResource($pdf)->draw();

		// Luodaan palautettavat
		$return = array();

		foreach ($params as $key => $null) {
			$return[$key] = ${$key};
		}

		return $return;
	}
}

if (!function_exists('uusi_sivu_dgd')) {
	function uusi_sivu_dgd ($params) {
		global $kukarow, $yhtiorow;

		// Luodaan muuttujat
		foreach ($params as $key => $val) {
			${$key} = $val;
		}

		// Loadataan lisäsivun pohja omaan olioon
		$pdf2 = Zend_Pdf::load("DGDpohja_lisasivu.pdf");

		// Kloonataan sivu jotta saadaan se liitettyä alkuperäiseen dokumenttiin
		$pdf->pages[] = clone $pdf2->pages[0];

		$pdf->pages[$sivu]->setFont($norm, 10);
		$pdf->pages[$sivu]->drawText($yhtiorow["nimi"], mm_pt(15), mm_pt(262), 'LATIN1');
		$pdf->pages[$sivu]->drawText($yhtiorow["osoite"], mm_pt(15), mm_pt(258), 'LATIN1');
		$pdf->pages[$sivu]->drawText($yhtiorow["postino"]." ".$yhtiorow["postitp"], mm_pt(15), mm_pt(254), 'LATIN1');
		$pdf->pages[$sivu]->drawText($yhtiorow["maa"], mm_pt(15), mm_pt(250), 'LATIN1');

		// Luodaan palautettavat
		$return = array();

		foreach ($params as $key => $null) {
			$return[$key] = ${$key};
		}

		return $return;
	}
}

if (!function_exists('rivi_dgd')) {
	function rivi_dgd($params) {
		global $kukarow, $yhtiorow;

		// Luodaan muuttujat
		foreach ($params as $key => $val) {
			${$key} = $val;
		}

		$kala = 175;

		$pdf->pages[$sivu]->setFont($norm, 10);

		while ($row = mysql_fetch_assoc($riresult2)) {

			$query = "	SELECT *
						from pakkaus
						WHERE yhtio = '$kukarow[yhtio]'
						and pakkaus = '$row[pakkaus]'
					 	and pakkauskuvaus = '$row[pakkauskuvaus]'";
			$sresult = mysql_query($query) or pupe_error($query);
			$srow = mysql_fetch_assoc($sresult);

			$pakkaus_en = t_tunnus_avainsanat($srow, "pakkaus", "PAKKAUSKV", "en");

			$pdf->pages[$sivu]->drawText("$row[kollit] $pakkaus_en containing:", mm_pt(45), mm_pt($kala), 'LATIN1');

			#$oikpos = widthForStringUsingFontSize($row["kilot"], $norm, 10);
			$pdf->pages[$sivu]->drawText($row["kilot"], mm_pt(145), mm_pt($kala), 'LATIN1');
			$kala-=5;
		}

		$edvakkoodi = "";
		$pdf->pages[$sivu]->setFont($norm, 8);

		while ($row = mysql_fetch_assoc($riresult1)) {

			$eramaara = $row["kpl"]/$row["era"];

			$pdf->pages[$sivu]->drawText("$eramaara ctns each $row[era] x $row[tilavuus] (".t($row["materiaali"], "en").")", mm_pt(45), mm_pt($kala), 'LATIN1');
			$kala-=5;

			if ($row["vakkoodi"] != $edvakkoodi and $edvakkoodi != "") {
				$pdf->pages[$sivu]->setFont($norm, 10);
				$pdf->pages[$sivu]->drawText($edvakkoodi, mm_pt(45), mm_pt($kala), 'LATIN1');
				$pdf->pages[$sivu]->setFont($norm, 8);
				$kala-=5;
			}

			if ($kala < 125) {

				$pdf->pages[$sivu]->drawText("Continues....", mm_pt(45), mm_pt($kala), 'LATIN1');

				$sivu++;

				// Luodaan palautettavat
				$return = array();

				foreach ($params as $key => $null) {
					$return[$key] = ${$key};
				}

				$params = uusi_sivu_dgd($return);

				// Luodaan muuttujat
				foreach ($params as $key => $val) {
					${$key} = $val;
				}

				$kala = 240;
				$pdf->pages[$sivu]->setFont($norm, 8);
			}

			$edvakkoodi = $row["vakkoodi"];
		}

		$pdf->pages[$sivu]->setFont($norm, 10);
		$pdf->pages[$sivu]->drawText($edvakkoodi, mm_pt(45), mm_pt($kala), 'LATIN1');

		// Luodaan palautettavat
		$return = array();

		foreach ($params as $key => $null) {
			$return[$key] = ${$key};
		}

		return $return;
	}
}

if (!function_exists('loppu_dgd')) {
	function loppu_dgd ($params) {
		global $kukarow, $yhtiorow;

		// Luodaan muuttujat
		foreach ($params as $key => $val) {
			${$key} = $val;
		}

		$pdf->pages[0]->setFont($norm, 10);

		// Sivunumerot
		for ($pp=0; $pp<=$sivu; $pp++) {
			$csiv = $pp+1;
			$tsiv = $sivu+1;

			$pdf->pages[$pp]->drawText("$csiv       $tsiv", mm_pt(114), mm_pt(259), 'LATIN1');
		}

		// Luodaan palautettavat
		$return = array();

		foreach ($params as $key => $null) {
			$return[$key] = ${$key};
		}

		return $return;
	}
}

if (!function_exists('print_pdf_dgd')) {
	function print_pdf_dgd ($params) {
		global $kukarow, $yhtiorow;

		// Luodaan muuttujat
		foreach ($params as $key => $val) {
			${$key} = $val;
		}

		echo $pdf->render();
	}
}

?>