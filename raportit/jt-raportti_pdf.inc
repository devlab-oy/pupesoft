<?php

$kukarow["kieli"] = $kieli = strtolower($asiakasrow["kieli"]);

$p["height"] 		= 10;
$p["font"] 			= "Times-Roman";

$px["height"] 		= 8;
$px["font"] 		= "Times-Roman";

$pz["height"] 		= 14;
$pz["font"] 		= "Times-Roman";

$pc["height"] 		= 10;
$pc["font"] 		= "Courier";

$pp["height"]		= 8;
$pp["font"]			= "Times-Roman";

$pb["height"]		= 10;
$pb["font"]			= "Times-Bold";

$rectparam["width"] = 0.3;

//muutamat funktiot...
if (!function_exists('alku')) {
	function alku ($pdf) {
		global $kukarow, $yhtiorow, $rectparam, $p, $asiakasrow, $pz, $toimrow;

		$page = $pdf->new_page("a4");

		$pdf->draw_text(30, 815, $asiakasrow["nimi"], 	$page, $p);
		$pdf->draw_text(30, 805, $asiakasrow["osoite"], 	$page, $p);
		$pdf->draw_text(30, 795, $asiakasrow["postino"]." ".$asiakasrow["postitp"], 	$page, $p);
		$pdf->draw_text(30, 785, $asiakasrow["maa"], 		$page, $p);

		if ($toimrow["nimi"] != "") {
			// haetaan toimipaikan tiedot jos ne lˆytyv‰t, muuten laitetaan yhtiˆn tiedot
			$pdf->draw_text(30, 760, $toimrow["nimi"], 	$page, $p);
			$pdf->draw_text(30, 750, $toimrow["osoite"], 	$page, $p);
			$pdf->draw_text(30, 740, $toimrow["postino"]." ".$toimrow["postitp"], 	$page, $p);
			$pdf->draw_text(30, 730, $toimrow["maa"], 		$page, $p);
		}
		else {
			$pdf->draw_text(30, 760, $yhtiorow["nimi"], 	$page, $p);
			$pdf->draw_text(30, 750, $yhtiorow["osoite"], 	$page, $p);
			$pdf->draw_text(30, 740, $yhtiorow["postino"]." ".$yhtiorow["postitp"], 	$page, $p);
			$pdf->draw_text(30, 730, $yhtiorow["maa"], 		$page, $p);
		}

		$title = t("JƒLKITOIMITUSRAPORTTI");
		$title = str_replace(array("‰", "ˆ", "Â"), array("ƒ", "÷", "≈"), $title);
		$title_position = 360;

		$pdf->draw_text($title_position,815, t($title, $kieli), 	$page);

		$pdf->draw_rectangle(710, 20,  690, 290, $page, $rectparam);
		$pdf->draw_rectangle(710, 290, 690, 360, $page, $rectparam);
		$pdf->draw_rectangle(710, 360, 690, 450, $page, $rectparam);
		$pdf->draw_rectangle(710, 450, 690, 520, $page, $rectparam);
		$pdf->draw_rectangle(710, 520, 690, 580, $page, $rectparam);

		$pdf->draw_text(25,  700, t("Nimitys", $kieli),	 $page, $p);
		$pdf->draw_text(295, 700, t("Tilausnumero", $kieli), $page, $p);
		$pdf->draw_text(365, 700, t("Tuotenumero", $kieli),	 $page, $p);
		$pdf->draw_text(455, 700, t("Tilattu", $kieli), 	 $page, $p);
		$pdf->draw_text(525, 700, t("Kpl", $kieli),		 	 $page, $p);

		$pdf->draw_rectangle(710, 20, 20, 580, $page, $rectparam);

		return(array($page,680));
	}
}

if (!function_exists('rivi')) {
	function rivi($pdf, $page, $kalakorkeus, $jtrow) {
		global $kukarow, $yhtiorow, $rectparam, $p, $pb, $pc, $px;

		$rivinkorkeus = 15;

		//jos on paljon rivej‰ tehd‰‰n uusi otsikko...
		if ($kalakorkeus < 70) {
			list($page,$kalakorkeus) = alku($pdf);
		}

		//Kieliversioitu tuotteen nimitys
		if (strtolower($kieli) != strtolower($yhtiorow['kieli'])) {
			$laji = 'nimitys_'.strtolower($kieli);
			$query = "SELECT selite FROM tuotteen_avainsanat WHERE yhtio = '$yhtiorow[yhtio]' and laji = '$laji' and tuoteno = '$jtrow[tuoteno]' LIMIT 1";
			$nimresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($nimresult) > 0) {
				$nimrow	= mysql_fetch_array($nimresult);
				$jtrow['nimitys'] = $nimrow['selite'];
			}
		}

		if (strlen($jtrow["nimitys"]) > 60) {
			$nimitys = substr($jtrow["nimitys"],0,60);
			$pdf->draw_text(25, $kalakorkeus, $nimitys,	 						$page, $px);
			$nimitys = substr($jtrow["nimitys"],60);
			$pdf->draw_text(25, $kalakorkeus-8, $nimitys,	 					$page, $px);
		}
		else {
			$pdf->draw_text(25, $kalakorkeus, $jtrow["nimitys"],	 			$page, $px);
		}
		$pdf->draw_text(295, $kalakorkeus, $jtrow["otunnus"],					$page, $p);
		$pdf->draw_text(365, $kalakorkeus, $jtrow["tuoteno"],					$page, $p);
		$pdf->draw_text(455, $kalakorkeus, tv1dateconv($jtrow["laadittu"]),		$page, $p);
		$pdf->draw_text(525, $kalakorkeus, $jtrow["tilkpl"], 					$page, $p);

		$kalakorkeus = $kalakorkeus - $rivinkorkeus;

		// viivat rivien v‰liin...
		$x[0] = 20;
		$x[1] = 580;
		$y[0] = $y[1] = $kalakorkeus;

		$pdf->draw_line($x, $y, $page, $rectparam);

		$kalakorkeus = $kalakorkeus - $rivinkorkeus;

		return(array($page, $kalakorkeus));
	}
}

if (!function_exists('print_pdf')) {
	function print_pdf ($pdf, $luetkpl) {
		global $kukarow, $yhtiorow, $tee, $asiakasrow;

		$returnvalue=0;

		//keksit‰‰n uudelle failille joku varmasti uniikki nimi:
		list($usec, $sec) = explode(' ', microtime());
		mt_srand((float) $sec + ((float) $usec * 100000));
		$pdffilenimi = "/tmp/".$yhtiorow["yhtio"]."-".t("jtraportti")."-".md5(uniqid(mt_rand(), true)).".pdf";

		//kirjoitetaan pdf faili levylle..
		$fh = fopen($pdffilenimi, "w");
		if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF create error $pdffilenimi");
		fclose($fh);

		$komento = 'email';
		$kukarow = array();
		// itse print komento...
		$liite = $pdffilenimi;
		$kutsu = t("J‰lkitoimitusraportti");

//		debuggaus-echo
//		echo t("Luettelo tulostuu")."...$asiakasrow[nimi]\n";

		$kukarow["eposti"] = $asiakasrow["email"];
//		$kukarow["eposti"] = "tomi@arwidson.fi";
		require("../inc/sahkoposti.inc");

		//poistetaan tmp file samantien kuleksimasta...
		system("rm -f $pdffilenimi");
	}
}

?>