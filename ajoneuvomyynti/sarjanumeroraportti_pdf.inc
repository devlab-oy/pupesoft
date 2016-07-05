<?php

if ($kieli== '') {
	$querykiel = "SELECT kieli FROM asiakas WHERE yhtio = '$kukarow[yhtio]' and ytunnus = '$laskurow[ytunnus]'";
	$kielresult = mysql_query($querykiel) or pupe_error($querykiel);
	$kielnum = mysql_num_rows($kielresult);
	$kielrow = mysql_fetch_array($kielresult);
	$kieli = strtolower($kielrow['kieli']);
}

$p["height"] 		= 10;
$p["font"] 			= "Times-Roman";

$pc["height"] 		= 10;
$pc["font"] 		= "Courier";

$pp["height"]		= 8;
$pp["font"]			= "Times-Roman";

$pb["height"]		= 10;
$pb["font"]			= "Times-Bold";

$rectparam["width"] = 0.3;

//muutamat funktiot...
if (!function_exists('alku')) {
	function alku ($pdf, $hinnat) {
		global $kukarow, $yhtiorow, $rectparam, $p;

		$page = $pdf->new_page("a4");

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
				$placement = $pdf->image_place($image, 800, mm_pt(10), $page, $logoparam);
			}
		}
		else {
			$pdf->draw_text(30, 815,  $yhtiorow["nimi"], $firstpage);
		}

		$pdf->draw_text(250,810, t("Laiteluettelo", $kieli), 	$page);

		$pdf->draw_rectangle(800, 20,  780, 160, 				$page, $rectparam);
		$pdf->draw_rectangle(800, 160, 780, 320, 				$page, $rectparam);
		$pdf->draw_rectangle(800, 320, 780, 480, 				$page, $rectparam);
		$pdf->draw_rectangle(800, 480, 780, 580, 				$page, $rectparam);

		$pdf->draw_text(25,  787, t("Tuotenimi", $kieli),		$page, $p);
		$pdf->draw_text(165, 787, t("Tuotenumero", $kieli),		$page, $p);
		$pdf->draw_text(325, 787, "**",							$page, $p);

		if ($hinnat == "MY") {
			$pdf->draw_text(485, 787, t("Myyntihinta", $kieli),			$page, $p);
		}
		else {
			$pdf->draw_text(485, 787, t("Hankintahinta", $kieli),		$page, $p);
		}

		$pdf->draw_rectangle(800, 20, 20, 580, $page, $rectparam);

		return(array($page,770));
	}
}

if (!function_exists('rivi')) {
	function rivi($pdf, $page, $kalakorkeus, $sarjarow, $kulurow, $hinnat, $tarkkuus, $kommentit, $text_output, $kuvalisa_bin, $hankintahinta, $fnlina22) {
		global $kukarow, $yhtiorow, $rectparam, $p, $pb, $pc;

		$rivinkorkeus	= 15;

		//jos on paljon rivejä tehdään uusi otsikko...
		if ($kalakorkeus < 70) {
			list($page,$kalakorkeus) = alku($pdf, $hinnat);
		}

		$pdf->draw_text(25, $kalakorkeus, $sarjarow["nimitys"], 		$page, $pb);
		$kalakorkeus = $kalakorkeus - $rivinkorkeus;


		$pdf->draw_text(25,  $kalakorkeus, $sarjarow["sarjanumero"],	$page, $p);
		$pdf->draw_text(165, $kalakorkeus, $sarjarow["tuoteno"],		$page, $p);

		if ($sarjarow["kaytetty"] != '') {
			$pdf->draw_text(325, $kalakorkeus, t("Käytetty"), 			$page, $p);
		}
		else {
			$pdf->draw_text(325, $kalakorkeus, t("Uusi"), 				$page, $p);
		}

		if ($hinnat == "MY") {
			if ($sarjarow["kaytetty"] == '') {
				$pdf->draw_text(485, $kalakorkeus, sprintf('%15s', sprintf('%.2f', $sarjarow["tuotemyyntihinta"])." ".$yhtiorow["valkoodi"]),	$page, $pc);
			}
			else {
				$query = "	SELECT Hinta
							FROM sarjanumeron_lisatiedot use index (yhtio_liitostunnus)
							WHERE sarjanumeron_lisatiedot.yhtio		 = '$kukarow[yhtio]'
							and sarjanumeron_lisatiedot.liitostunnus = '$sarjarow[tunnus]'";
				$lisatietores = mysql_query($query) or pupe_error($query);
				$lisatietorow = mysql_fetch_array($lisatietores);

				$pdf->draw_text(485, $kalakorkeus, sprintf('%15s', sprintf('%.2f', $lisatietorow["Hinta"])." ".$yhtiorow["valkoodi"]),	$page, $pc);
			}
		}
		else {
			$pdf->draw_text(485, $kalakorkeus, sprintf('%15s', sprintf('%.2f', $hankintahinta)." ".$yhtiorow["valkoodi"]),	$page, $pc);
		}

		$kalakorkeus = $kalakorkeus - $rivinkorkeus;

		$pdf->draw_text(25, $kalakorkeus, $sarjarow["lisatieto"], 							$page, $p);
		$pdf->draw_text(485, $kalakorkeus, $sarjarow["varastonimi"],						$page, $p);

		$kalakorkeus = $kalakorkeus - $rivinkorkeus;

		$pdf->draw_text(25, $kalakorkeus, $sarjarow["osto_nimi"], 							$page, $p);

		if ($sarjarow["myynti_tila"] == 'T') {
			$pdf->draw_text(325, $kalakorkeus, "(Tarjous: ".$sarjarow["myynti_nimi"].")", 	$page, $p);
		}
		else {
			$pdf->draw_text(325, $kalakorkeus, $sarjarow["myynti_nimi"], 					$page, $p);
		}

		$pdf->draw_text(485, $kalakorkeus, $sarjarow["tuotepaikka"],						$page, $p);

		$kalakorkeus = $kalakorkeus - $rivinkorkeus;

		if ($fnlina22 != "") {
			$pdf->draw_text(25, $kalakorkeus, $fnlina22,								$page, $p);

			$kalakorkeus = $kalakorkeus - $rivinkorkeus;
		}

		if ($tarkkuus == "") {
			if (strlen($kuvalisa_bin) > 0) {
				/*
				$image = $pdf->jfif_embed($kuvalisa_bin);

				if(!$image) {
					echo t("Logokuvavirhe").": ".$image.$kuvalisa_bin;
				}
				else {
					$logoparam = array();
					$logoparam['scale'] = 0.15;
					$placement = $pdf->image_place($image, $kalakorkeus-100, 325, $page, $logoparam);
				}
				*/
				$kalakorkeus = $kalakorkeus - $rivinkorkeus;
			}
			else {
				$kalakorkeus = $kalakorkeus - $rivinkorkeus;
			}

			$kommentit = explode("\n", trim($text_output));

			foreach($kommentit as $kommentti) {
				if ($kalakorkeus < 70) {
					list($page,$kalakorkeus) = alku($pdf, $hinnat);
				}

				$komm = explode("###", trim($kommentti));

				$pdf->draw_text(35,  $kalakorkeus, trim($komm[0]), 			$page, $p);
				$pdf->draw_text(165, $kalakorkeus, trim($komm[1]), 			$page, $p);
				$pdf->draw_text(325, $kalakorkeus, trim($komm[2]), 			$page, $p);
				$pdf->draw_text(485, $kalakorkeus, sprintf('%15s', trim($komm[3])),	$page, $pc);

				$kalakorkeus = $kalakorkeus - $rivinkorkeus;
			}
		}

		// viivat rivien väliin...
		$x[0] = 20;
		$x[1] = 580;
		$y[0] = $y[1] = $kalakorkeus;

		$pdf->draw_line($x, $y, $page, $rectparam);

		$kalakorkeus = $kalakorkeus - $rivinkorkeus;

		return(array($page, $kalakorkeus));
	}
}

if (!function_exists('print_pdf')) {
	function print_pdf ($pdf, $kirjoitin, $luetkpl) {
		global $kukarow, $yhtiorow, $tee;

		$oslapp='';
		$returnvalue=0;

		//keksitään uudelle failille joku varmasti uniikki nimi:
		list($usec, $sec) = explode(' ', microtime());
		mt_srand((float) $sec + ((float) $usec * 100000));
		$pdffilenimi = "/tmp/Lahete-".md5(uniqid(mt_rand(), true)).".pdf";

		//kirjoitetaan pdf faili levylle..
		$fh = fopen($pdffilenimi, "w");
		if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF create error $pdffilenimi");
		fclose($fh);

		$query = "	SELECT *
					from kirjoittimet
					where yhtio='$kukarow[yhtio]'
					and tunnus='$kirjoitin'";
		$kirres= mysql_query($query) or pupe_error($query);
		$kirrow= mysql_fetch_array($kirres);
		$komento=$kirrow['komento'];


		// itse print komento...
		if ($komento == 'email') {
			$liite = $pdffilenimi;
			$kutsu = "Luettelo";

			echo t("Luettelo tulostuu")."...<br>";

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
			echo t("Luettelo tulostuu")."...<br>";
			$line = exec("$komento $pdffilenimi", $output, $returnvalue);
		}

		//poistetaan tmp file samantien kuleksimasta...
		system("rm -f $pdffilenimi");
	}
}
