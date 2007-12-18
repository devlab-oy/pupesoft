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

function alku () {
	global $pdf, $row, $yhtiorow, $kukarow, $sivu, $rectparam, $norm, $pieni, $maksuehto, $pvm;

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
	$pdf->draw_text(50, 729, $row["ytunnus"], 					$firstpage, $pieni);
	$pdf->draw_text(50, 717, $row["nimi"], 						$firstpage, $norm);
	$pdf->draw_text(50, 707, $row["nimitark"], 					$firstpage, $norm);
	$pdf->draw_text(50, 697, $row["osoite"], 					$firstpage, $norm);
	$pdf->draw_text(50, 687, $row["postino"]." ".$row["postitp"], $firstpage, $norm);
	$pdf->draw_text(50, 677, $row["maa"], 						$firstpage, $norm);

	//oikea sarake
	$pdf->draw_text(300, 727, "Hyvä yhteistyökumppanimme,",		$firstpage, $norm);
	$pdf->draw_text(300, 707, "Ohessa tämänhetkinen ostotilanteenne.",		$firstpage, $norm);
	$pdf->draw_text(300, 687, "Kunnioittaen,",					$firstpage, $norm);
	$pdf->draw_text(300, 677, $yhtiorow["nimi"],				$firstpage, $norm);
	
	//otsikkotiedot	
	$pdf->draw_text(30,  600, "Tuoteryhmä",			$firstpage, $pieni);
	$pdf->draw_text(300, 600, "Kpl $vvv",			$firstpage, $pieni);
	$pdf->draw_text(350, 600, "Kpl $vvt",			$firstpage, $pieni);
	$pdf->draw_text(425, 600, "Ostot $vvv",			$firstpage, $pieni);
	$pdf->draw_text(475, 600, "Ostot $vvt",			$firstpage, $pieni);
	$pdf->draw_text(550, 600, "Indeksi",			$firstpage, $pieni);
	
	return($firstpage);
}

function rivi ($firstpage) {
	global $firstpage, $pdf, $row, $kala, $sivu, $lask, $rectparam, $norm, $pieni, $lask, $sumkpled, $sumkplva, $sumed, $sumva, $kukarow;

	if ($lask >= 37) {
		// rivejä on tarpeeks, tehään uus headeri
		$sivu++;
		$kala = 575;
		$lask = 1;
		$firstpage = alku();
	}
	
	if ($row["ed"] != 0) {
		$indexi = round($row["va"]/$row["ed"],2)*100;
	}
	else {
		$indexi = 0;
	}

	$query = "select selitetark from avainsana where laji='try' and selite='$row[try]' and yhtio='$kukarow[yhtio]'";
	$tryre = mysql_query($query) or pupe_error($query);
	$tryro = mysql_fetch_array($tryre);

	$pdf->draw_text(30,  $kala, $row["try"],		$firstpage, $norm);		
	$pdf->draw_text(55,  $kala, $tryro["selitetark"],$firstpage, $norm);	
	$pdf->draw_text(300, $kala, $row["kpled"], 		$firstpage, $norm);
	$pdf->draw_text(350, $kala, $row["kplva"], 		$firstpage, $norm);
	$pdf->draw_text(425, $kala, $row["ed"],			$firstpage, $norm);
	$pdf->draw_text(475, $kala, $row["va"], 		$firstpage, $norm);
	$pdf->draw_text(550, $kala, $indexi,	 		$firstpage, $norm);
	$kala = $kala - 15;
		
	///summaillaan yhteensäkenttiä
	$sumkpled	+= $row["kpled"]; 
	$sumkplva	+= $row["kplva"];
	$sumed		+= $row["ed"];
	$sumva		+= $row["va"];	
	
	
	$lask++;
}

function loppu ($firstpage) {
	global $pdf, $kala, $rectparam, $norm, $pieni, $sumkpled, $sumkplva, $sumed, $sumva, $sumindexi, $yhtiorow, $kukarow, $edasiakas, $edemail, $komento, $emailok, $asnum;

	//kirjoitetaan yhteensärivit loppuun
	$kala -= 15;
	$pdf->draw_text(250, $kala, "Yhteensä:", 	$firstpage, $norm);
	$pdf->draw_text(300, $kala, $sumkpled, 		$firstpage, $norm);
	$pdf->draw_text(350, $kala, $sumkplva, 		$firstpage, $norm);
	$pdf->draw_text(425, $kala, $sumed,			$firstpage, $norm);
	$pdf->draw_text(475, $kala, $sumva, 		$firstpage, $norm);

	if ($sumed != 0) {
		$sumindexi = round($sumva/$sumed,2)*100;
	}
	else {
		$sumindexi = 0;
	}								
	$pdf->draw_text(550, $kala, $sumindexi,	 	$firstpage, $norm);

	//ja nollataan muuttujat
	$sumkpled  = 0; 
	$sumkplva  = 0; 
	$sumed     = 0;
	$sumva     = 0;
	$sumindexi = 0;
	
	//keksitään uudelle failille joku varmasti uniikki nimi:
	$pdffilenimi = "/tmp/vuosisop-".md5(uniqid(rand(),true)).".pdf";

	//kirjoitetaan pdf faili levylle..
	$fh = fopen($pdffilenimi, "w");
	if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus epäonnistui $pdffilenimi");
	fclose($fh);
	
	if ($asnum != '') { // ollaan valittu vain yksi asiakas, lähetetään meili ajajalle
		$edemail = $kukarow['eposti'];
	}

	// asiakkaalla on emailosoite ja ei olla ruksattu, lähetetään sähköposti 
	if ($edemail!='' and $emailok=='') {
		echo "Lähetetään meili asiakkaalle $edasiakas ($edemail).. ";

		$bound = uniqid(time()."_") ;

		$header  = "From: <$yhtiorow[postittaja_email]>\n";
		$header .= "MIME-Version: 1.0\n" ;
		$header .= "Content-Type: multipart/mixed; boundary=\"$bound\"\n" ;

		$content  = "--$bound\n";

		$content .= "Content-Type: application/pdf; name=\"Ostoseuranta.pdf\"\n" ;
		$content .= "Content-Transfer-Encoding: base64\n" ;
		$content .= "Content-Disposition: inline; filename=\"Ostoseuranta.pdf\"\n\n";

		$handle  = fopen($pdffilenimi, "r");
		$sisalto = fread($handle, filesize($pdffilenimi));
		fclose($handle);

		$content .= chunk_split(base64_encode($sisalto));
		$content .= "\n" ;

		$content .= "--$bound\n";

		$boob     = mail($edemail,  $yhtiorow['nimi']." - Ostoseuranta ".date("d.m.Y"), $content, $header, "-f $yhtiorow[postittaja_email]");
		//$boob     = mail($kukarow["eposti"],  $yhtiorow['nimi']." - Ostoseuranta ".date("d.m.Y"), $content, $header, "-f $yhtiorow[postittaja_email]");

		if ($boob===FALSE) {
			echo "Meilin lähetys epäonnistui!<br>";
		}
		else {
			echo "ok.<br>";
		}
	}
	// asiakkaalla on email, mutta halutaan skipata emailien lähetys...
	elseif ($edemail!='' and $emailok!='') {
		echo "Skipataan asiakas $edasiakas.<br>";
	}
	// muissa keiseissa tulostetaan paprulle ta sähköpostiin
	else {
		echo "Tulostetaan asiakkaan $edasiakas ostoseuranta.. ";
		
		if ($komento == "email") {
			$liite = $pdffilenimi;
			$ctype = '';
			$kutsu = "Ostoseuranta.pdf";
			
			require("../inc/sahkoposti.inc");
		}
		else{
			$line = exec($komento." ".$pdffilenimi);
		}
		
		
		echo "ok.<br>";
	}

	//poistetaan tmp file samantien kuleksimasta...
	system("rm -f $pdffilenimi");		
}

?>