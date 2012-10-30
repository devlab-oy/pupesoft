<?php

require("pdflib/phppdflib.class.php");

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

// defaultteja
$kala = 575;
$lask = 1;
$sivu = 1;

function alku ($laji = '') {
	global $pdf, $asiakasrow, $yhtiorow, $kukarow, $asrow, $sivu, $rectparam, $norm, $pieni, $maksuehto, $pvm, $alkuvv, $alkukk, $alkupp, $loppuvv, $loppukk, $loppupp;

	// jos ollaan syötetty poikkeava päivä, käytetään sitä
	if ($pvm != '') {
		// tämävuosi ja viimevuosi
		$vvt = substr($pvm, 0, 4);
		$vvv = substr($pvm, 0, 4) - 1;
		$now = $pvm;
	}
	else {
		// tämävuosi ja viimevuosi
		$vvt = date("Y");
		$vvv = date("Y") - 1;
		$now = date("Y-m-d");
	}

	$firstpage = $pdf->new_page("a4");

	//otsikko
	$pdf->draw_text(30,  815,  $yhtiorow["nimi"], 				$firstpage);
	$pdf->draw_text(300, 815, "OSTOSEURANTA", 					$firstpage);
	$pdf->draw_text(530, 815, "Sivu $sivu", 					$firstpage, $norm);

	//vasen sarake
	$pdf->draw_text(50, 729, $asiakasrow["ytunnus"], 					$firstpage, $pieni);
	$pdf->draw_text(50, 717, $asiakasrow["nimi"], 						$firstpage, $norm);
	$pdf->draw_text(50, 707, $asiakasrow["nimitark"], 					$firstpage, $norm);
	$pdf->draw_text(50, 697, $asiakasrow["osoite"], 					$firstpage, $norm);
	$pdf->draw_text(50, 687, $asiakasrow["postino"]." ".$asiakasrow["postitp"], $firstpage, $norm);
	$pdf->draw_text(50, 677, $asiakasrow["maa"], 						$firstpage, $norm);

	//oikea sarake
	$pdf->draw_text(300, 727, "Hyvä yhteistyökumppanimme,",		$firstpage, $norm);
	$pdf->draw_text(300, 707, "Ohessa tämänhetkinen ostotilanteenne.",		$firstpage, $norm);
	$pdf->draw_text(300, 687, "Kunnioittaen,",					$firstpage, $norm);
	$pdf->draw_text(300, 677, $yhtiorow["nimi"],				$firstpage, $norm);

	//aika
	$pdf->draw_text(300,  610, "Rajaus: ",			$firstpage, $pieni);
	$pdf->draw_text(350,  610, tv1dateconv("$alkuvv-$alkukk-$alkupp")." - ".tv1dateconv("$loppuvv-$loppukk-$loppupp"),			$firstpage, $pieni);

	//otsikkotiedot
	if ($laji == "osasto") {
		$pdf->draw_text(30,  600, "Osasto",			$firstpage, $pieni);
	}
	else {
		//otsikkotiedot
		$pdf->draw_text(30,  600, "Tuoteryhmä",			$firstpage, $pieni);
	}

	$oikpos = $pdf->strlen("Kpl $vvt", $pieni);
	$pdf->draw_text(330-$oikpos, 600, "Kpl $vvt",			$firstpage, $pieni);

	$oikpos = $pdf->strlen("Kpl $vvv", $pieni);
	$pdf->draw_text(380-$oikpos, 600, "Kpl $vvv",			$firstpage, $pieni);

	$oikpos = $pdf->strlen("Ostot $vvt", $pieni);
	$pdf->draw_text(460-$oikpos, 600, "Ostot $vvt",			$firstpage, $pieni);

	$oikpos = $pdf->strlen("Ostot $vvv", $pieni);
	$pdf->draw_text(530-$oikpos, 600, "Ostot $vvv",			$firstpage, $pieni);

	$oikpos = $pdf->strlen("Indeksi", $pieni);
	$pdf->draw_text(575-$oikpos, 600, "Indeksi",			$firstpage, $pieni);

	return($firstpage);
}

function rivi ($firstpage, $laji = '') {
	global $firstpage, $pdf, $row, $kala, $sivu, $lask, $rectparam, $norm, $pieni, $lask, $sumkpled, $sumkplva, $sumed, $sumva, $kukarow, $yhtiorow, $edosasto;

	if ($lask >= 34) {
		// rivejä on tarpeeks, tehään uus headeri
		$sivu++;
		$kala = 575;
		$lask = 1;
		$firstpage = alku($laji);
	}

	if ($row["ed"] != 0) {
		$indexi = round($row["va"]/$row["ed"],2)*100;
	}
	else {
		$indexi = 0;
	}

	if ($laji == 'osasto') {
		// tehdään avainsana query
		$tryre = t_avainsana("OSASTO", "", "and avainsana.selite ='$row[osasto]'");
		$tryro = mysql_fetch_array($tryre);
		$asananumero = $row["osasto"];
	}
	else {
		// tehdään avainsana query
		$tryre = t_avainsana("TRY", "", "and avainsana.selite ='$row[try]'");
		$tryro = mysql_fetch_array($tryre);
		$asananumero = $row["try"];
	}

	if ($edosasto != $row["osasto"] and $laji == "") {
		$edosasto = $row["osasto"];

		$tryre = t_avainsana("OSASTO", "", "and avainsana.selite ='$row[osasto]'");
		$osrow = mysql_fetch_array($tryre);

		if ($edosasto != "" and $lask > 1) {
			$kala = $kala - 20;
			$lask++;
		}

		$pdf->draw_text(30, $kala, "Osasto $row[osasto] - $osrow[selitetark]", $firstpage, $norm);
		$kala = $kala - 20;
		$lask++;
	}

	$pdf->draw_text(30,  $kala, $asananumero." - ".$tryro["selitetark"],	$firstpage, $norm);

	$oikpos = $pdf->strlen(sprintf("%.0f", $row["kplva"]), $norm);
	$pdf->draw_text(330-$oikpos, $kala, sprintf("%.0f", $row["kplva"]), 	$firstpage, $norm);

	$oikpos = $pdf->strlen(sprintf("%.0f", $row["kpled"]), $norm);
	$pdf->draw_text(380-$oikpos, $kala, sprintf("%.0f", $row["kpled"]), 	$firstpage, $norm);

	$oikpos = $pdf->strlen(hintapyoristys($row["va"]), $norm);
	$pdf->draw_text(460-$oikpos, $kala, hintapyoristys($row["va"]),			$firstpage, $norm);

	$oikpos = $pdf->strlen(hintapyoristys($row["ed"]), $norm);
	$pdf->draw_text(530-$oikpos, $kala, hintapyoristys($row["ed"]), 		$firstpage, $norm);

	$oikpos = $pdf->strlen($indexi, $norm);
	$pdf->draw_text(575-$oikpos, $kala, $indexi, $firstpage, $norm);
	$kala = $kala - 15;

	$lask++;
}

function loppu ($firstpage, $sendemail = '') {
	global $pdf, $kala, $rectparam, $norm, $pieni, $sumkpled, $sumkplva, $sumed, $sumva, $sumindexi, $yhtiorow, $kukarow, $edasiakas, $edemail, $edasiakasno, $komento, $emailok, $asnum, $myyja_eposti;

	//kirjoitetaan yhteensärivit loppuun
	$kala -= 15;

	$pdf->draw_text(250, $kala, "Yhteensä:", 	$firstpage, $norm);

	$oikpos = $pdf->strlen(sprintf("%.0f", $sumkplva), $norm);
	$pdf->draw_text(330-$oikpos, $kala, sprintf("%.0f", $sumkplva), 		$firstpage, $norm);

	$oikpos = $pdf->strlen(sprintf("%.0f", $sumkpled), $norm);
	$pdf->draw_text(380-$oikpos, $kala, sprintf("%.0f", $sumkpled), 		$firstpage, $norm);

	$oikpos = $pdf->strlen(hintapyoristys($sumva), $norm);
	$pdf->draw_text(460-$oikpos, $kala, hintapyoristys($sumva),			$firstpage, $norm);

	$oikpos = $pdf->strlen(hintapyoristys($sumed), $norm);
	$pdf->draw_text(530-$oikpos, $kala, hintapyoristys($sumed), 		$firstpage, $norm);

	if ($sumed != 0) {
		$sumindexi = round($sumva/$sumed,2)*100;
	}
	else {
		$sumindexi = 0;
	}

	$oikpos = $pdf->strlen($sumindexi, $norm);
	$pdf->draw_text(575-$oikpos, $kala, $sumindexi,	 	$firstpage, $norm);

	//ja nollataan muuttujat
	$sumkpled  = 0;
	$sumkplva  = 0;
	$sumed     = 0;
	$sumva     = 0;
	$sumindexi = 0;

	if ($sendemail != "") {
		return;
	}

	//keksitään uudelle failille joku varmasti uniikki nimi:
	$pdffilenimi = "/tmp/vuosisop-".md5(uniqid(rand(),true)).".pdf";

	//kirjoitetaan pdf faili levylle..
	$fh = fopen($pdffilenimi, "w");
	if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus epäonnistui $pdffilenimi");
	fclose($fh);

	switch (htmlentities(trim($_REQUEST['laheta_sahkopostit']))) {
		case 'ajajalle':
			$edemail = $kukarow['eposti'];
			//ajajalle ja asiakaan_myyjalle lähetettäessä sähköpostilla, raportit halutaan zippiin ja yhteen emailiin. muilla tavoilla normi komennolla
			if($komento == 'email') {
				laheta_koottu_sahkoposti(htmlentities(trim($_REQUEST['laheta_sahkopostit'])), $edemail, $edasiakasno, $pdffilenimi, $komento);
			}
			else {
				tulosta_raportti($edasiakasno, $pdffilenimi, $komento);
			}
			break;
		case 'asiakkaalle':
			if($komento == 'email') {
				laheta_raportit(htmlentities(trim($_REQUEST['laheta_sahkopostit'])), $edemail, $edasiakasno, $pdffilenimi, $komento);
			}
			else {
				tulosta_raportti($edasiakasno, $pdffilenimi, $komento);
			}
			break;
		case 'asiakkaan_myyjalle':
			$edemail = $myyja_eposti;
			if($komento == 'email') {
				laheta_koottu_sahkoposti(htmlentities(trim($_REQUEST['laheta_sahkopostit'])), $edemail, $edasiakasno, $pdffilenimi, $komento);
			}
			else {
				tulosta_raportti($edasiakasno, $pdffilenimi, $komento);
			}
			break;
		default:
			//lähetetään ajajalle
			$edemail = $kukarow['eposti'];
			//ajajalle ja asiakaan_myyjalle lähetettäessä sähköpostilla, raportit halutaan zippiin ja yhteen emailiin. muilla tavoilla normi komennolla
			if($komento == 'email') {
				laheta_koottu_sahkoposti(htmlentities(trim($_REQUEST['laheta_sahkopostit'])), $edemail, $edasiakasno, $pdffilenimi, $komento);
			}
			else {
				tulosta_raportti($edasiakasno, $pdffilenimi, $komento);
			}
	}
}

function laheta_raportit($laheta_sahkopostit, $edemail, $edasiakasno, $pdffilenimi, $komento) {
	global $kukarow, $yhtiorow;

	echo "Lähetetään meili {$laheta_sahkopostit} osoitteeseen {$edemail} ";

	$bound = uniqid(time()."_") ;

	$header  = "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n";
	$header .= "MIME-Version: 1.0\n" ;
	$header .= "Content-Type: multipart/mixed; boundary=\"$bound\"\n" ;

	$content  = "--$bound\n";

	$content .= "Content-Type: application/pdf; name=\"Ostoseuranta_{$edasiakasno}.pdf\"\n" ;
	$content .= "Content-Transfer-Encoding: base64\n" ;
	$content .= "Content-Disposition: inline; filename=\"Ostoseuranta_{$edasiakasno}.pdf\"\n\n";

	$handle  = fopen($pdffilenimi, "r");
	$sisalto = fread($handle, filesize($pdffilenimi));
	fclose($handle);

	$content .= chunk_split(base64_encode($sisalto));
	$content .= "\n" ;

	$content .= "--$bound\n";

	$boob     = mail($edemail, mb_encode_mimeheader($yhtiorow['nimi']." - Ostoseuranta ".date("d.m.Y"), "ISO-8859-1", "Q"), $content, $header, "-f $yhtiorow[postittaja_email]");

	if ($boob===FALSE) {
		echo "Meilin lähetys epäonnistui!<br>";
	}
	else {
		echo "ok.<br>";
	}
	//poistetaan tmp file samantien kuleksimasta...
	system("rm -f $pdffilenimi");
}

function tulosta_raportit($edasiakasno, $pdffilenimi, $komento) {
	echo "Tulostetaan asiakkaan $edasiakasno ostoseuranta tulostimeen {$komento}";
	$line = exec($komento." ".$pdffilenimi);
}

function laheta_koottu_sahkoposti($laheta_sahkopostit, $edemail, $edasiakasno, $pdffilenimi, $komento) {
	global $kukarow, $yhtiorow;
}

?>