<?php

require("../pdflib/phppdflib.class.php");

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

	// jos ollaan syˆtetty poikkeava p‰iv‰, k‰ytet‰‰n sit‰
	if ($pvm != '') {
		// t‰m‰vuosi ja viimevuosi
		$vvt = substr($pvm, 0, 4);
		$vvv = substr($pvm, 0, 4) - 1;
		$now = $pvm;
	}
	else {
		// t‰m‰vuosi ja viimevuosi
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
	$pdf->draw_text(300, 727, "Hyv‰ yhteistyˆkumppanimme,",		$firstpage, $norm);
	$pdf->draw_text(300, 707, "Ohessa t‰m‰nhetkinen ostotilanteenne.",		$firstpage, $norm);
	$pdf->draw_text(300, 687, "Kunnioittaen,",					$firstpage, $norm);
	$pdf->draw_text(300, 677, $yhtiorow["nimi"],				$firstpage, $norm);
	
	//otsikkotiedot	
	$pdf->draw_text(30,  600, "Tuoteryhm‰",			$firstpage, $pieni);
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
		// rivej‰ on tarpeeks, teh‰‰n uus headeri
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
		
	///summaillaan yhteens‰kentti‰
	$sumkpled	+= $row["kpled"]; 
	$sumkplva	+= $row["kplva"];
	$sumed		+= $row["ed"];
	$sumva		+= $row["va"];	
	
	
	$lask++;
}

function loppu ($firstpage) {
	global $pdf, $kala, $rectparam, $norm, $pieni, $sumkpled, $sumkplva, $sumed, $sumva, $sumindexi, $yhtiorow, $kukarow, $edasiakas, $edemail, $komento, $emailok, $asnum;

	//kirjoitetaan yhteens‰rivit loppuun
	$kala -= 15;
	$pdf->draw_text(250, $kala, "Yhteens‰:", 	$firstpage, $norm);
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
	
	//keksit‰‰n uudelle failille joku varmasti uniikki nimi:
	$pdffilenimi = "/tmp/vuosisop-".md5(uniqid(rand(),true)).".pdf";

	//kirjoitetaan pdf faili levylle..
	$fh = fopen($pdffilenimi, "w");
	if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus ep‰onnistui $pdffilenimi");
	fclose($fh);
	
	if ($asnum != '') { // ollaan valittu vain yksi asiakas, l‰hetet‰‰n meili ajajalle
		$edemail = $kukarow['eposti'];
	}

	// asiakkaalla on emailosoite ja ei olla ruksattu, l‰hetet‰‰n s‰hkˆposti 
	if ($edemail!='' and $emailok=='') {
		echo "L‰hetet‰‰n meili asiakkaalle $edasiakas ($edemail).. ";

		$bound = uniqid(time()."_") ;

		$header  = "From: <$yhtiorow[postittaja_email]>\r\n";
		$header .= "MIME-Version: 1.0\r\n" ;
		$header .= "Content-Type: multipart/mixed; boundary=\"$bound\"\r\n" ;

		$content  = "--$bound\r\n";

		$content .= "Content-Type: application/pdf; name=\"Ostoseuranta.pdf\"\r\n" ;
		$content .= "Content-Transfer-Encoding: base64\r\n" ;
		$content .= "Content-Disposition: inline; filename=\"Ostoseuranta.pdf\"\r\n\r\n";

		$handle  = fopen($pdffilenimi, "r");
		$sisalto = fread($handle, filesize($pdffilenimi));
		fclose($handle);

		$content .= chunk_split(base64_encode($sisalto));
		$content .= "\r\n" ;

		$content .= "--$bound\r\n";

		$boob     = mail($edemail,  $yhtiorow['nimi']." - Ostoseuranta ".date("d.m.Y"), $content, $header);
		//$boob     = mail($kukarow["eposti"],  $yhtiorow['nimi']." - Ostoseuranta ".date("d.m.Y"), $content, $header);

		if ($boob===FALSE) {
			echo "Meilin l‰hetys ep‰onnistui!<br>";
		}
		else {
			echo "ok.<br>";
		}
	}
	// asiakkaalla on email, mutta halutaan skipata emailien l‰hetys...
	elseif ($edemail!='' and $emailok!='') {
		echo "Skipataan asiakas $edasiakas.<br>";
	}
	// muissa keiseissa tulostetaan paprulle
	else {
		echo "Tulostetaan asiakkaan $edasiakas ostoseuranta.. ";
		$line = exec($komento." ".$pdffilenimi);
		echo "ok.<br>";
	}

	//poistetaan tmp file samantien kuleksimasta...
	system("rm -f $pdffilenimi");		
}

?>