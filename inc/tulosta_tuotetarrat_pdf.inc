<?php

	$query = "	SELECT *
				FROM tuote
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tuoteno = '$tuoteno'";
	$eankoodires = mysql_query($query) or pupe_error($query);
	$eankoodirow = mysql_fetch_assoc($eankoodires);

	require("pdflib/phppdflib.class.php");

	//PDF parametrit
	$pdf = new pdffile;
	$pdf->set_default('margin-top', 	0);
	$pdf->set_default('margin-bottom', 	0);
	$pdf->set_default('margin-left', 	0);
	$pdf->set_default('margin-right', 	0);
	$rectparam["width"] = 0.3;
	
	//fontit
	$norm["height"] = 8;
	$norm["font"] = "Helvetica";
	
	// tehdään pdf:n uusi sivu
	$firstpage = $pdf->new_page("a4");

	// A4 pointteina 595x842

	if ($malli == 'PDF40') {
		$x = 10;
		$riville = 5;
		$leveys = 315;
		$kappalemaara = 41;
		$mm_pt = 35;
	}
	else {
		$x = 15;
		$riville = 3;
		$leveys = 450;
		$kappalemaara = 25;
		$mm_pt = 50;
	}

	$y = 285;

	$koodi = $toim == 'YKS' ? 'tuoteno' : 'eankoodi';

	for ($i = 1; $i < $kappalemaara; $i++) {

		$nimitys1 = $nimitys2 = '';

		if ($pdf->strlen($eankoodirow['nimitys'], $norm) >= mm_pt($mm_pt)) {
			list($ff_string, $ff_font) = pdf_fontfit($eankoodirow['nimitys'], mm_pt($mm_pt), $pdf, $norm);

			$nimitys1 = substr($eankoodirow['nimitys'], 0, strlen($ff_string));
			$pdf->draw_text(mm_pt($x), mm_pt($y), $nimitys1, $firstpage, $ff_font);		

			$nimitys2 = substr($eankoodirow['nimitys'], strlen($ff_string));

			if ($pdf->strlen($nimitys2, $norm) >= mm_pt($mm_pt)) {
				list($ff_string2, $ff_font2) = pdf_fontfit($nimitys2, mm_pt($mm_pt), $pdf, $norm);
				$nimitys2 = $ff_string2;
			}

			$pdf->draw_text(mm_pt($x), mm_pt($y - 4), $nimitys2, $firstpage, $ff_font);
			$pdf->draw_text(mm_pt($x), mm_pt($y - 8), t("Tuotenumero")." $eankoodirow[tuoteno]", $firstpage, $norm);
		}
		else {
			list($ff_string, $ff_font) = pdf_fontfit($eankoodirow['nimitys'], mm_pt($mm_pt), $pdf, $norm);
			$pdf->draw_text(mm_pt($x), mm_pt($y), $ff_string, $firstpage, $ff_font);		
			$pdf->draw_text(mm_pt($x), mm_pt($y - 4), t("Tuotenumero")." $eankoodirow[tuoteno]", $firstpage, $norm);
		}

		if ($koodi == 'eankoodi') {
			$data = viivakoodi($eankoodirow['eankoodi'], "ean", $leveys, 130, "zz");
		}
		else {
			$data = viivakoodi($eankoodirow["$koodi"], "codabar", $leveys, 130, "zz");
		}

		$image = $pdf->jfif_embed($data);

		if ($image) {
			$logoparam['scale'] = 0.1;

			// piirretään viivakoodi paprulle
			$pdf->image_place($image, mm_pt($y - 22.5), mm_pt($x), $firstpage, $logoparam); // Y, X
		}

		if ($malli == 'PDF40') {
			$x += 40;
		}
		else {
			$x += 68;
		}

		if ($i % $riville == 0) {
			if ($malli == 'PDF40') {
				$x = 10;
				$y -= 36;
			}
			else {
				$x = 15;
				$y -= 37;
			}
		}
	}

	//keksitään uudelle failille joku varmasti uniikki nimi:
	list($usec, $sec) = explode(' ', microtime());
	mt_srand((float) $sec + ((float) $usec * 100000));
	$pdffilenimi = "/tmp/tuotetarrat_ean_pdf-".md5(uniqid(mt_rand(), true)).".pdf";
	
	//kirjoitetaan pdf faili levylle..
	$fh = fopen($pdffilenimi, "w");
	if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus epäonnistui $pdffilenimi");
	fclose($fh);
	
	//Työnnetään tuo pdf vaan putkeen!
	echo file_get_contents($pdffilenimi);
	
	//poistetaan tmp file samantien kuleksimasta...
	system("rm -f $pdffilenimi");

?>