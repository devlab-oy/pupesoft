<?php

if (@include_once("pdflib/phppdflib.class.php"));
else include_once("phppdflib.class.php");

// jos php-gd on installoitu niin loidataab barcode library
if (in_array("gd", get_loaded_extensions())) {
	if (@include_once("viivakoodi/Barcode.php"));
	else include_once("Barcode.php");
}

if ($kieli== '') {
	$querykiel = "SELECT kieli FROM asiakas WHERE yhtio = '$kukarow[yhtio]' and ytunnus = '$laskurow[ytunnus]'";
	$kielresult = mysql_query($querykiel) or pupe_error($querykiel);
	$kielnum = mysql_num_rows($kielresult);
	$kielrow = mysql_fetch_array($kielresult);
	$kieli = strtolower($kielrow['kieli']);
}

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

$rectparam["width"] 	= 0.3;
$rivinkorkeus			= 15;

//muutamat funktiot...
if (!function_exists('alku_lahete')) {
	function alku_lahete($tyyppi = "") {
		global $pdf, $sivu, $lahetetyyppi, $laskurow, $yhtiorow, $viite, $pieni, $norm, $boldi, $pieni_boldi, $iso, $kala, $rectparam, $kukarow, $kieli, $asrow, $useita, $extranet_tilausvahvistus;

		//PDF parametrit
		if (!isset($pdf)) {
			$pdf = new pdffile;
			$pdf->set_default('margin-top', 	0);
			$pdf->set_default('margin-bottom', 	0);
			$pdf->set_default('margin-left', 	0);
			$pdf->set_default('margin-right', 	0);
		}

		$thispage = $pdf->new_page("a4");

		if (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_hae_hinnat.inc" and $GLOBALS['eta_yhtio'] != '') {
			$query = "	SELECT nimi, nimitark, osoite, postino, postitp, maa, osasto
						FROM asiakas
						WHERE yhtio = '{$GLOBALS['eta_yhtio']}'
						AND ytunnus = '{$laskurow['ytunnus']}'
						AND toim_ovttunnus = '{$laskurow['toim_ovttunnus']}'";
			$asiakas_tunnus_res = mysql_query($query) or pupe_error($query);
			$asiakas_tunnus_row = mysql_fetch_assoc($asiakas_tunnus_res);

			if ($asiakas_tunnus_row['osasto'] != '6') {
				unset($GLOBALS['eta_yhtio']);
				$lahetetyyppi = "tulosta_lahete_eiale_eihinta.inc";
			}
			else {
				$laskurow["eta_yhtio"] 		= $GLOBALS['eta_yhtio'];
				$laskurow["yhtio_nimi"] 	= $GLOBALS['eta_yhtio_nimi'];
				$laskurow["yhtio_osoite"] 	= $GLOBALS['eta_yhtio_osoite'];
				$laskurow["yhtio_postino"] 	= $GLOBALS['eta_yhtio_postino'];
				$laskurow["yhtio_postitp"] 	= $GLOBALS['eta_yhtio_postitp'];
				$laskurow["yhtio_maa"] 		= $GLOBALS['eta_yhtio_maa'];
			}
		}

		tulosta_logo_pdf($pdf, $thispage, $laskurow);

		if ($tyyppi == "TILAUSVAHVISTUS") {
			$kerarow["kerattyaika"] = date("Y-m-d");
		}
		elseif ($tyyppi == "YLLAPITOSOPIMUS") {
			$kerarow["kerattyaika"] = date("Y-m-d");
		}
		else {
			// haetaan max(kerattyaika)
			$query  = "	SELECT max(kerattyaika) kerattyaika
						from tilausrivi
						where yhtio = '$kukarow[yhtio]'
						and otunnus = '$laskurow[tunnus]'";
			$keraresult = mysql_query($query) or pupe_error($query);
			$kerarow = mysql_fetch_array($keraresult);

			if ($kerarow["kerattyaika"] == "0000-00-00 00:00:00") {
				$kerarow["kerattyaika"] = date("Y-m-d H:i:s");
			}
		}

		if ($tyyppi == "TILAUSVAHVISTUS") {
			if (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_custom.inc") {
				$otsikko_len = $pdf->strlen(t("TILAUSVAHVISTUS", $kieli), $iso);
				$pdf->draw_text((595 / 2) - ($otsikko_len / 2), 760, t("TILAUSVAHVISTUS", $kieli), 		$thispage, $iso);
			}
			else {
				$pdf->draw_text(310,815, t("Tilausvahvistus", $kieli), 		$thispage, $iso);
			}
		}
		elseif ($tyyppi == "YLLAPITOSOPIMUS") {
			$pdf->draw_text(310,815, t("Ylläpitosopimus", $kieli), 		$thispage, $iso);
		}
		elseif ($tyyppi == "SIIRTOLISTA") {
			$pdf->draw_text(310,815, t("Lähete/Siirtolista", $kieli), 	$thispage, $iso);
		}
		else {
			$pdf->draw_text(310,815, t("Lähete", $kieli), 				$thispage, $iso);
		}

		if (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_custom.inc") {
			$pdf->draw_text(530, 803, t("Sivu", $kieli), 					$thispage, $pieni);
		}
		else {
			$pdf->draw_text(310, 803, t("Sivu", $kieli), 					$thispage, $norm);
		}

		if (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_custom.inc") {

			//Vasen sarake
			$pdf->draw_text(50, 625, t("Asiakas", $kieli), 	$thispage, $boldi);

			$pdf->draw_text(50, 612, t("Nimi", $kieli), $thispage, $norm);
			$pdf->draw_text(150, 612, $laskurow["nimi"].' '.$laskurow["nimitark"], $thispage, $norm);

			$pdf->draw_text(50, 599, t("Osoite", $kieli), $thispage, $norm);
			$pdf->draw_text(150, 599, $laskurow["osoite"], $thispage, $norm);
			$pdf->draw_text(150, 586, $laskurow["postino"]." ".$laskurow["postitp"], $thispage, $norm);

			$query = "	SELECT gsm, puhelin, ytunnus
						FROM asiakas
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tunnus = '{$laskurow['liitostunnus']}'";
			$puh_res = mysql_query($query) or pupe_error($query);
			$puh_row = mysql_fetch_assoc($puh_res);

			$pdf->draw_text(50, 573, t("Puhelinnumero", $kieli), $thispage, $norm);
			$pdf->draw_text(150, 573, $puh_row['puhelin'], $thispage, $norm);

			$pdf->draw_text(50, 560, t("GSM", $kieli), $thispage, $norm);
			$pdf->draw_text(150, 560, $puh_row['gsm'], $thispage, $norm);

			$pdf->draw_text(50, 547, t("Y-tunnus/Sotu", $kieli), $thispage, $norm);
			$pdf->draw_text(150, 547, $puh_row['ytunnus'], $thispage, $norm);

			$pdf->draw_text(350, 625, t("Toimitusosoite", $kieli), 	$thispage, $boldi);
			$pdf->draw_text(350, 612, $laskurow["toim_nimi"].' '.$laskurow["toim_nimitark"], $thispage, $norm);
			$pdf->draw_text(350, 599, $laskurow["toim_osoite"], $thispage, $norm);
			$pdf->draw_text(350, 586, $laskurow["toim_postino"]." ".$laskurow["toim_postitp"], $thispage, $norm);
		}
		else {
			//Vasen sarake
			$pdf->draw_text(50, 729, t("Ostaja", $kieli), 	$thispage, $pieni);

			if (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_hae_hinnat.inc" and $GLOBALS['eta_yhtio'] != '') {
				$pdf->draw_text(50, 717, $asiakas_tunnus_row["nimi"], 			$thispage, $norm);
				$pdf->draw_text(50, 707, $asiakas_tunnus_row["nimitark"],			$thispage, $norm);
				$pdf->draw_text(50, 697, $asiakas_tunnus_row["osoite"], 			$thispage, $norm);
				$pdf->draw_text(50, 687, $asiakas_tunnus_row["postino"]." ".$asiakas_tunnus_row["postitp"], $thispage, $norm);
				$pdf->draw_text(50, 677, $asiakas_tunnus_row["maa"], 				$thispage, $norm);
			}
			else {
				//$pdf->draw_rectangle(737, 20,  674, 300, $thispage, $rectparam);
				$pdf->draw_text(50, 717, $laskurow["nimi"], 			$thispage, $norm);
				$pdf->draw_text(50, 707, $laskurow["nimitark"],			$thispage, $norm);
				$pdf->draw_text(50, 697, $laskurow["osoite"], 			$thispage, $norm);
				$pdf->draw_text(50, 687, $laskurow["postino"]." ".$laskurow["postitp"], $thispage, $norm);
				$pdf->draw_text(50, 677, $laskurow["maa"], 				$thispage, $norm);
			}

			//$pdf->draw_rectangle(674, 20,  611, 300, $thispage, $rectparam);
			$pdf->draw_text(50, 656, t("Toimitusosoite", $kieli), 	$thispage, $pieni);
			$pdf->draw_text(50, 644, $laskurow["toim_nimi"], 		$thispage, $boldi);
			$pdf->draw_text(50, 634, $laskurow["toim_nimitark"], 	$thispage, $boldi);
			$pdf->draw_text(50, 624, $laskurow["toim_osoite"],	 	$thispage, $boldi);
			$pdf->draw_text(50, 614, $laskurow["toim_postino"]." ".$laskurow["toim_postitp"], $thispage, $boldi);
			$pdf->draw_text(50, 604, $laskurow["toim_maa"], 		$thispage, $boldi);
		}

		if (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_custom.inc") {
			$pdf->draw_text(530, 785, t("Numero", $kieli), $thispage, $pieni);
			$pdf->draw_text(530, 775, $laskurow["tunnus"], $thispage, $pieni);

			$pvm_len = $pdf->strlen(tv1dateconv($laskurow["luontiaika"]), $pieni);
			$pdf->draw_text((595 / 2) - ($pvm_len / 2), 750, tv1dateconv($laskurow["luontiaika"]), 		$thispage, $pieni);
		}
		else {
			//Oikea sarake
			$pdf->draw_rectangle(800, 300, 779, 580, $thispage, $rectparam);
			$pdf->draw_rectangle(800, 420, 779, 580, $thispage, $rectparam);

			if ($laskurow["tunnusnippu"] > 0) {
				if ($tyyppi == "YLLAPITOSOPIMUS" or $laskurow["nippu"] > 0) {
					$pdf->draw_text(310, 792, t("Tilausnumero", $kieli), 						$thispage, $pieni);
					$pdf->draw_text(310, 782, $laskurow["tunnusnippu"],							$thispage, $boldi);
				}
				else {
					$pdf->draw_text(310, 792, t("Tilausnumero/toimitus", $kieli), 				$thispage, $pieni);
					$pdf->draw_text(310, 782, $laskurow["tunnusnippu"]."/".$laskurow["tunnus"],	$thispage, $boldi);
				}
			}
			else {
				$pdf->draw_text(310, 792, t("Tilausnumero", $kieli), 	$thispage, $pieni);
				$pdf->draw_text(310, 782, $laskurow["tunnus"],			$thispage, $boldi);
			}

			$pdf->draw_text(430, 792, t("Toimitusaika", $kieli), 	$thispage, $pieni);

			$useita = "EI";

			if ($laskurow["nippu"] > 0) {
				$query = "	SELECT distinct(toimaika)
							FROM tilausrivi
							WHERE yhtio = '$kukarow[yhtio]'
							and otunnus IN ($laskurow[nippu])
							and toimaika != '0000-00-00'";
				$toimaikares = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($toimaikares) > 1) {
					$useita = "JOO";
				}
			}

			if ($useita == "JOO") {
				$pdf->draw_text(430, 782, t("Useita", $kieli), 	$thispage, $norm);
			}
			elseif ($laskurow["toimvko"] != '') {
				$DAY_ARRAY = array(1 => t("Ma", $kieli), t("Ti", $kieli), t("Ke", $kieli), t("To", $kieli), t("Pe", $kieli), t("La", $kieli), t("Su", $kieli));

				$taika = t("Vko", $kieli)." ".date("W", strtotime($laskurow["toimaika"]));

				if ($laskurow['toimvko'] != '7') {
					$taika .= "/" .$DAY_ARRAY[$laskurow["toimvko"]];
				}

				$pdf->draw_text(430, 782, $taika, 								$thispage, $norm);
			}
			else {
				$pdf->draw_text(430, 782, tv1dateconv($laskurow["toimaika"]), 	$thispage, $norm);
			}

			if (($asrow["asiakasnro"] == "" or $asrow["asiakasnro"] == 0) and $laskurow["ytunnus"] != "") {
				$asrow["asiakasnro"] = tulosta_ytunnus($laskurow["ytunnus"], $laskurow["maa"], $laskurow["vienti"]);
			}

			$pdf->draw_rectangle(779, 300, 758, 580, $thispage, 						$rectparam);
			$pdf->draw_rectangle(779, 420, 758, 580, $thispage, 						$rectparam);
			$pdf->draw_text(310, 771, t("Asiakasnumero", $kieli), 						$thispage, $pieni);
			$pdf->draw_text(310, 761, $asrow["asiakasnro"], 							$thispage, $norm);

			$pdf->draw_text(430, 771, t("Vastaanotettu", $kieli),						$thispage, $pieni);
			$pdf->draw_text(430, 761, tv1dateconv($laskurow["luontiaika"], "pitka"), 	$thispage, $norm);
		}

		if (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_custom.inc") {
			$pdf->draw_text(50, 730, t("Myyjä", $kieli), 								$thispage, $boldi);
		}
		else {
			$pdf->draw_rectangle(758, 300, 737, 580, $thispage, $rectparam);
			$pdf->draw_rectangle(758, 420, 737, 580, $thispage, $rectparam);
			$pdf->draw_text(310, 750, t("Myyjä", $kieli), 								$thispage, $pieni);
		}

		//etsitään myyjän nimi
		$query  = "	SELECT nimi, kuka
					from kuka
					where tunnus='$laskurow[myyja]' and yhtio='$kukarow[yhtio]'";
		$myyresult = mysql_query($query) or pupe_error($query);
		$myyrow = mysql_fetch_array($myyresult);

		if (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_custom.inc") {
			list($ff_string, $ff_font) = pdf_fontfit($myyrow["nimi"], 105, $pdf, $boldi);
			$pdf->draw_text(150, 730, $ff_string, $thispage, $ff_font);

			$pdf->draw_text(50, 717, t("Y-tunnus", $kieli), $thispage, $boldi);
			$pdf->draw_text(150, 717, tulosta_ytunnus(str_replace("0037", "", $laskurow["yhtio_ovttunnus"]), $laskurow["yhtio_maa"], $laskurow["vienti"]), $thispage, $norm);

			$pdf->draw_text(50, 704, t("Nimi", $kieli), $thispage, $boldi);
			$pdf->draw_text(150, 704, $kukarow['nimi'], $thispage, $norm);

			$pdf->draw_text(50, 691, t("Osoite", $kieli), $thispage, $boldi);
			$pdf->draw_text(150, 691, $yhtiorow['osoite'], $thispage, $norm);
			$pdf->draw_text(150, 678, $yhtiorow['postino'].' '.$yhtiorow['postitp'], $thispage, $norm);

			$pdf->draw_text(50, 665, t("Puhelin", $kieli), $thispage, $boldi);
			$pdf->draw_text(150, 665, $kukarow['puhno'], $thispage, $norm);

			$pdf->draw_text(50, 652, t("Sähköposti", $kieli), $thispage, $boldi);
			$pdf->draw_text(150, 652, $kukarow['eposti'], $thispage, $norm);
		}
		else {
			list($ff_string, $ff_font) = pdf_fontfit($myyrow["nimi"], 105, $pdf, $norm);
			$pdf->draw_text(310, 740, $ff_string, 										$thispage, $ff_font);

			$pdf->draw_text(430, 750, t("Käsittelyssä", $kieli), 						$thispage, $pieni);
			$pdf->draw_text(430, 740, tv1dateconv($laskurow['h1time'], "pitka"), 		$thispage, $norm);

			$pdf->draw_rectangle(737, 300, 716, 420, $thispage, $rectparam);
			$pdf->draw_rectangle(737, 420, 716, 580, $thispage, $rectparam);

			if ($myyrow['kuka'] != $laskurow['laatija']) {
				//etsitään laatijan nimi
				$query  = "	SELECT nimi
							from kuka
							where kuka='$laskurow[laatija]' and yhtio='$kukarow[yhtio]'";
				$laaresult = mysql_query($query) or pupe_error($query);
				$laarow = mysql_fetch_array($laaresult);

				$pdf->draw_text(310, 729, t("Laatija", $kieli), $thispage, $pieni);

				list($ff_string, $ff_font) = pdf_fontfit($laarow["nimi"], 105, $pdf, $norm);
				$pdf->draw_text(310, 719, $ff_string, 									$thispage, $ff_font);
			}

			$pdf->draw_text(430, 729, t("Päiväys", $kieli), 							$thispage, $pieni);
			$pdf->draw_text(430, 719, tv1dateconv($kerarow["kerattyaika"], "pitka"), 	$thispage, $norm);

			$pdf->draw_rectangle(716, 300, 695, 580, $thispage, $rectparam);

			if ($extranet_tilausvahvistus != 1) {
				//etsitään löytyykö rahtisopimusta
				$rahtirow = hae_rahtisopimusnumero($laskurow["toimitustapa"], $laskurow["ytunnus"], $laskurow["liitostunnus"]);

				$pdf->draw_text(310, 708, t("Toimitustapa", $kieli)." / ".t("Rahtisopimus", $kieli), $thispage, $pieni);
				$pdf->draw_text(310, 698, $laskurow["toimitustapa"]." ".$rahtirow["rahtisopimus"],	 $thispage, $boldi);
			}

			$pdf->draw_text(310, 687, t("Toimitusehto", $kieli), 		$thispage, $pieni);
			$pdf->draw_text(310, 677, $laskurow["toimitusehto"], 		$thispage, $norm);

			$pdf->draw_rectangle(695, 300, 674, 580, $thispage, $rectparam);

			// maksuehto tekstinä jos on jokin muu kuin hinnaton maksuehto
			// extranet tilausvahvistuksessa ei näytetä maksuehtoa (jos halutaan näyttää niin setataan muuttuja nollaksi)
			if (str_replace("perhe_", "", $lahetetyyppi) != "tulosta_lahete_eiale_eihinta.inc" and $extranet_tilausvahvistus != 1) {
				// haetaan maksuehdon tiedot
				$query  = "SELECT * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
				$masresult = mysql_query($query) or pupe_error($query);
				$masrow = mysql_fetch_array($masresult);

				$pdf->draw_rectangle(674, 300, 653, 580, $thispage, $rectparam);
				$pdf->draw_text(310, 666, t("Maksuehto", $kieli), 	$thispage, $pieni);
				$pdf->draw_text(310, 656, t_tunnus_avainsanat($masrow, "teksti", "MAKSUEHTOKV", $kieli), 		$thispage, $boldi);
			}

			#$pdf->draw_rectangle(653, 300, 632, 580, $thispage, $rectparam);
			#$pdf->draw_text(310, 645, "1", $thispage, $pieni);
			#$pdf->draw_text(310, 635, "1", $thispage, $norm);

			#$pdf->draw_rectangle(632, 300, 611, 580, $thispage, $rectparam);
			#$pdf->draw_text(310, 624, "2", 	$thispage, $pieni);
			#$pdf->draw_text(310, 614, "2", 	$thispage, $norm);
		}

		$komm = "";

		if (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_custom.inc") {
			if (trim($laskurow['viesti']) != '') {
				$komm .= "\n".t("Tilausviite", $kieli)."###".$laskurow['viesti'];
			}
		}
		else {
			if (trim($laskurow['tilausyhteyshenkilo']) != '') {
				$komm .= "\n".t("Tilaaja", $kieli).":###".$laskurow['tilausyhteyshenkilo'];
			}

			if (trim($laskurow['asiakkaan_tilausnumero']) != '') {
				$komm .= "\n".t("Tilauksenne", $kieli).":###".$laskurow['asiakkaan_tilausnumero'];
			}

			if (trim($laskurow['kohde']) != '') {
				$komm .= "\n".t("Kohde", $kieli).":###".$laskurow['kohde'];
			}

			if (trim($laskurow['viesti']) != '') {
				$komm .= "\n".t("Tilausviite", $kieli).":###".$laskurow['viesti'];
			}

			if (trim($laskurow['comments']) != '') {
				$komm .= "\n".t("Kommentti", $kieli).":###".wordwrap(str_replace("\n","\n###", $laskurow['comments']), 90, "\n###");
			}
		}

		// Tulostetaan laskun kommentti
		if (trim($komm) != '') {
			$kommentit = explode("\n", trim($komm));

			if (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_custom.inc") {
				$pohja  = 525;
			}
			else {
				$pohja  = 577;
			}

			$maxoik = 0;

			foreach($kommentit as $kommentti) {
				if (strpos($kommentti, "###") !== FALSE) {
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

					if (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_custom.inc") {
						$pdf->draw_text(50, $pohja, trim($o), $thispage, $boldi);
						$pdf->draw_text(50+$maxoik+55, $pohja, trim($v), $thispage, $norm);
					}
					else {
						$pdf->draw_text(35, $pohja, trim($o), $thispage, $boldi);
						$pdf->draw_text(35+$maxoik+5, $pohja, trim($v), $thispage, $norm);
					}

					if (class_exists("Image_Barcode")) {
						if (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_asiakviivakoodi.inc" and substr($o, 0, -1) == t("Tilausviite", $kieli)) {
							//viivakoodi
							$nimi = "/tmp/".md5(uniqid(rand(),true)).".jpg";
							$oikpos = 35+$maxoik+10+$pdf->strlen($v, $norm);

							imagejpeg(Image_Barcode::draw(trim($v), 'Code39', 'jpg', false, 1, 15), $nimi, 90);

							$fh = fopen($nimi, "r");
							$data = fread($fh, filesize($nimi));
							fclose($fh);

							$image = $pdf->jfif_embed($data);
							$pdf->image_place($image, $pohja-8, $oikpos, $thispage);

							$kala = $kala - 20;

							system("rm -f $nimi");

							$pohja = $pohja - 10;
						}
					}
				}
				else {
					if (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_custom.inc") {
						$pdf->draw_text(50, $pohja, trim($kommentti), $thispage, $norm);
					}
					else {
						$pdf->draw_text(35, $pohja, trim($kommentti), $thispage, $norm);
					}
				}

				$pohja = $pohja - 10;
			}
			$kala = $pohja-30;
		}
		else {
			if (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_custom.inc") {
				$kala = 465;
			}
			else {
				$kala = 560;
			}
		}

		if (str_replace("perhe_", "", $lahetetyyppi) != "tulosta_lahete_custom.inc") {
			if (class_exists("Image_Barcode")) {
				//viivakoodi
				$nimi = "/tmp/".md5(uniqid(rand(),true)).".jpg";

				imagejpeg(Image_Barcode::draw($laskurow["tunnus"], 'Code39', 'jpg', false, 1, 20), $nimi, 90);

				$fh = fopen($nimi, "r");
				$data = fread($fh, filesize($nimi));
				fclose($fh);

				$image = $pdf->jfif_embed($data);
				$pdf->image_place($image, 805, 440, $thispage);

				system("rm -f $nimi");
			}

			$pdf->draw_rectangle($kala+30, 20, 20, 580, $thispage, $rectparam);
		}

		rivi_otsikot_lahete($thispage);

		return($thispage);
	}
}

if (!function_exists('uusi_sivu_lahete')) {
	function uusi_sivu_lahete ($tyyppi = "", $ei_otsikoita = "") {
		global $yhtiorow, $kukarow, $toim, $pdf, $laskurow, $sivu, $rectparam, $norm, $pieni, $iso, $kateistyyppi, $kieli, $boldi, $kala, $useita, $lahetetyyppi;

		$thispage = $pdf->new_page("a4");

		if (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_hae_hinnat.inc" and $GLOBALS['eta_yhtio'] != '') {
			$query = "	SELECT nimi, nimitark, osoite, postino, postitp, maa, osasto
						FROM asiakas
						WHERE yhtio = '{$GLOBALS['eta_yhtio']}'
						AND ytunnus = '{$laskurow['ytunnus']}'
						AND toim_ovttunnus = '{$laskurow['toim_ovttunnus']}'";
			$asiakas_tunnus_res = mysql_query($query) or pupe_error($query);
			$asiakas_tunnus_row = mysql_fetch_assoc($asiakas_tunnus_res);

			if ($asiakas_tunnus_row['osasto'] != '6') {
				unset($GLOBALS['eta_yhtio']);
				$lahetetyyppi = "tulosta_lahete_eiale_eihinta.inc";
			}
			else {
				$laskurow["eta_yhtio"] 		= $GLOBALS['eta_yhtio'];
				$laskurow["yhtio_nimi"] 	= $GLOBALS['eta_yhtio_nimi'];
				$laskurow["yhtio_osoite"] 	= $GLOBALS['eta_yhtio_osoite'];
				$laskurow["yhtio_postino"] 	= $GLOBALS['eta_yhtio_postino'];
				$laskurow["yhtio_postitp"] 	= $GLOBALS['eta_yhtio_postitp'];
				$laskurow["yhtio_maa"] 		= $GLOBALS['eta_yhtio_maa'];
			}
		}

		tulosta_logo_pdf($pdf, $thispage, $laskurow);

		if ($tyyppi == "TILAUSVAHVISTUS") {
			$kerarow["kerattyaika"] = date("Y-m-d");
		}
		elseif ($tyyppi == "YLLAPITOSOPIMUS") {
			$kerarow["kerattyaika"] = "";
		}
		else {
			// haetaan max(kerattyaika)
			$query  = "SELECT max(kerattyaika) as kerattyaika from tilausrivi where yhtio='$kukarow[yhtio]' and otunnus='$laskurow[tunnus]'";
			$keraresult = mysql_query($query) or pupe_error($query);
			$kerarow = mysql_fetch_array($keraresult);

			if ($kerarow["kerattyaika"] == "0000-00-00 00:00:00") {
				$kerarow["kerattyaika"] = date("Y-m-d H:i:s");
			}
		}

		if ($tyyppi == "TILAUSVAHVISTUS") {

			if (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_custom.inc") {
				$otsikko_len = $pdf->strlen(t("TILAUSVAHVISTUS", $kieli), $iso);
				$pdf->draw_text((595 / 2) - ($otsikko_len / 2), 760, t("TILAUSVAHVISTUS", $kieli), $thispage, $iso);
			}
			else {
				$pdf->draw_text(310,815, t("Tilausvahvistus", $kieli), 		$thispage, $iso);
			}
		}
		elseif ($tyyppi == "YLLAPITOSOPIMUS") {
			$pdf->draw_text(310,815, t("Ylläpitosopimus", $kieli), 		$thispage, $iso);
		}
		elseif ($tyyppi == "SIIRTOLISTA") {
			$pdf->draw_text(310,815, t("Lähete/Siirtolista", $kieli), 	$thispage, $iso);
		}
		else {
			$pdf->draw_text(310,815, t("Lähete", $kieli), 				$thispage, $iso);
		}

		if (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_custom.inc") {
			$pdf->draw_text(530, 803, t("Sivu", $kieli), $thispage, $pieni);
		}
		else {
			$pdf->draw_text(310, 803, t("Sivu", $kieli), 					$thispage, $norm);
		}

		if (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_custom.inc") {
			$pdf->draw_text(530, 785, t("Numero", $kieli), $thispage, $pieni);
			$pdf->draw_text(530, 775, $laskurow["tunnus"], $thispage, $pieni);

			$pvm_len = $pdf->strlen(tv1dateconv($laskurow["luontiaika"]), $pieni);
			$pdf->draw_text((595 / 2) - ($pvm_len / 2), 750, tv1dateconv($laskurow["luontiaika"]), 		$thispage, $pieni);
		}
		else {
			//Oikea sarake
			$pdf->draw_rectangle(800, 300, 779, 580, $thispage, $rectparam);
			$pdf->draw_rectangle(800, 420, 779, 580, $thispage, $rectparam);

			$pdf->draw_text(310, 792, t("Tilausnumero", $kieli), 	$thispage, $pieni);
			$pdf->draw_text(430, 792, t("Toimitusaika", $kieli), 	$thispage, $pieni);
			$pdf->draw_text(310, 782, $laskurow["tunnus"],			$thispage, $boldi);

			if ($useita == "JOO") {
				$pdf->draw_text(430, 782, t("Useita", $kieli), 	$thispage, $norm);
			}
			elseif ($laskurow["toimvko"] != '') {
				$DAY_ARRAY = array(1 => t("Ma", $kieli), t("Ti", $kieli), t("Ke", $kieli), t("To", $kieli), t("Pe", $kieli), t("La", $kieli), t("Su", $kieli));

				$taika = t("Vko", $kieli)." ".date("W", strtotime($laskurow["toimaika"]));

				if ($laskurow['toimvko'] != '7') {
					$taika .= "/" .$DAY_ARRAY[$laskurow["toimvko"]];
				}

				$pdf->draw_text(430, 782, $taika, 								$thispage, $norm);
			}
			else {
				$pdf->draw_text(430, 782, tv1dateconv($laskurow["toimaika"]), 	$thispage, $norm);
			}

			if ($asrow["asiakasnro"] == "" and $laskurow["ytunnus"] != "") $asrow["asiakasnro"] = $laskurow["ytunnus"];

			$pdf->draw_rectangle(779, 300, 758, 580, $thispage, $rectparam);
			$pdf->draw_rectangle(779, 420, 758, 580, $thispage, $rectparam);
			$pdf->draw_text(310, 771, t("Asiakasnumero", $kieli), 						$thispage, $pieni);
			$pdf->draw_text(310, 761, $asrow["asiakasnro"], 							$thispage, $norm);
			$pdf->draw_text(430, 771, t("Päiväys", $kieli), 							$thispage, $pieni);
			$pdf->draw_text(430, 761, tv1dateconv($kerarow["kerattyaika"], "pitka"), 	$thispage, $norm);

			//viivakoodi
			$nimi = "/tmp/".md5(uniqid(rand(),true)).".jpg";

			imagejpeg(Image_Barcode::draw($laskurow["tunnus"], 'Code39', 'jpg', false, 1, 20), $nimi, 90);

			$fh = fopen($nimi, "r");
			$data = fread($fh, filesize($nimi));
			fclose($fh);

			$image = $pdf->jfif_embed($data);
			$pdf->image_place($image, 805, 440, $thispage);

			system("rm -f $nimi");
		}

		$kala = 715;

		if (str_replace("perhe_", "", $lahetetyyppi) != "tulosta_lahete_custom.inc") {
			$pdf->draw_rectangle($kala+30, 20, 20, 580, $thispage, $rectparam);
		}

		if (trim($ei_otsikoita) == '') {
			rivi_otsikot_lahete($thispage);
		}

		return($thispage);
	}
}

if (!function_exists('rivi_otsikot_lahete')) {
	function rivi_otsikot_lahete($thispage) {
		global $pdf, $kieli, $lahetetyyppi, $norm, $rectparam, $kala, $extranet_tilausvahvistus, $boldi, $laskurow;

		if (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete.inc") {
			// tulosta_lahete.inc

			$pdf->draw_rectangle($kala+30, 20,  $kala+10, 40,  	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 40,  $kala+10, 180, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 180, $kala+10, 320, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 320, $kala+10, 370, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 370, $kala+10, 420, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 420, $kala+10, 470, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 470, $kala+10, 495, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 495, $kala+10, 520, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 520, $kala+10, 580, 	$thispage, $rectparam);
			$pdf->draw_text(25,  $kala+15, "#",							$thispage, $norm);
			$pdf->draw_text(45,  $kala+15, t("Tuotenumero", $kieli),	$thispage, $norm);
			$pdf->draw_text(190, $kala+15, t("Tuotenimi", $kieli),		$thispage, $norm);
			$pdf->draw_text(330, $kala+15, t("Tilattu", $kieli),		$thispage, $norm);
			$pdf->draw_text(375, $kala+15, t("Toimitus", $kieli),		$thispage, $norm);
			$pdf->draw_text(425, $kala+15, t("Yks.hinta", $kieli),		$thispage, $norm);
			$pdf->draw_text(475, $kala+15, t("Ale", $kieli),			$thispage, $norm);
			$pdf->draw_text(500, $kala+15, t("Alv", $kieli),			$thispage, $norm);
			$pdf->draw_text(530, $kala+15, t("Rivihinta", $kieli),		$thispage, $norm);
		}
		elseif (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_brutto.inc") {
			// tulosta_lahete_brutto.inc

			$pdf->draw_rectangle($kala+30, 20,  $kala+10, 40,  	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 40,  $kala+10, 140, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 140, $kala+10, 420, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 420, $kala+10, 470, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 470, $kala+10, 520, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 520, $kala+10, 580, 	$thispage, $rectparam);
			$pdf->draw_text(25,  $kala+15, "#",							$thispage, $norm);
			$pdf->draw_text(45,  $kala+15, t("Tuotenumero", $kieli),	$thispage, $norm);
			$pdf->draw_text(145, $kala+15, t("Tuotenimi", $kieli),		$thispage, $norm);
			$pdf->draw_text(425, $kala+15, t("Tilattu", $kieli),		$thispage, $norm);
			$pdf->draw_text(475, $kala+15, t("Toimitus", $kieli),		$thispage, $norm);
			$pdf->draw_text(525, $kala+15, t("Ovh/Kpl", $kieli),		$thispage, $norm);
		}
		elseif (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_eialeja.inc") {
			//tulosta_lahete_eialeja.inc

			$pdf->draw_rectangle($kala+30, 20,  $kala+10, 40,  	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 40,  $kala+10, 180, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 180, $kala+10, 320, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 320, $kala+10, 370, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 370, $kala+10, 420, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 420, $kala+10, 470, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 470, $kala+10, 520, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 520, $kala+10, 580, 	$thispage, $rectparam);
			$pdf->draw_text(25,  $kala+15, "#",							$thispage, $norm);
			$pdf->draw_text(45,  $kala+15, t("Tuotenumero", $kieli),	$thispage, $norm);
			$pdf->draw_text(190, $kala+15, t("Tuotenimi", $kieli),		$thispage, $norm);
			$pdf->draw_text(330, $kala+15, t("Tilattu", $kieli),		$thispage, $norm);
			$pdf->draw_text(375, $kala+15, t("Toimitus", $kieli),		$thispage, $norm);
			$pdf->draw_text(425, $kala+15, t("Yks.hinta", $kieli),		$thispage, $norm);
			if ($extranet_tilausvahvistus != 1) {
				$pdf->draw_text(473, $kala+15, t("Alekoodi", $kieli),		$thispage, $norm);
			}
			$pdf->draw_text(530, $kala+15, t("Rivihinta", $kieli),		$thispage, $norm);
		}
		elseif (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_viivakoodi.inc") {
			// tulosta_lahete_viivakoodi.inc

			$pdf->draw_rectangle($kala+30, 20,  $kala+10, 40,  	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 40,  $kala+10, 180,  $thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 180, $kala+10, 480, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 480, $kala+10, 530, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 530, $kala+10, 580, 	$thispage, $rectparam);
			$pdf->draw_text(25,  $kala+15, "#",							$thispage, $norm);
			$pdf->draw_text(45,  $kala+15, t("Tuotenumero", $kieli),	$thispage, $norm);
			$pdf->draw_text(190, $kala+15, t("Tuotenimi", $kieli),		$thispage, $norm);
			$pdf->draw_text(490, $kala+15, t("Tilattu", $kieli),		$thispage, $norm);
			$pdf->draw_text(540, $kala+15, t("Toimitus", $kieli),		$thispage, $norm);
		}
		elseif (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_asiakviivakoodi.inc") {
			// tulosta_lahete_asiakviivakoodi.inc

			$pdf->draw_rectangle($kala+30, 20,  $kala+10, 40,  	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 40,  $kala+10, 180,  $thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 180, $kala+10, 480, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 480, $kala+10, 530, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 530, $kala+10, 580, 	$thispage, $rectparam);
			$pdf->draw_text(25,  $kala+15, "#",							$thispage, $norm);
			$pdf->draw_text(45,  $kala+15, t("Tuotenumero", $kieli),	$thispage, $norm);
			$pdf->draw_text(190, $kala+15, t("Tuotenimi", $kieli),		$thispage, $norm);
			$pdf->draw_text(490, $kala+15, t("Tilattu", $kieli),		$thispage, $norm);
			$pdf->draw_text(540, $kala+15, t("Toimitus", $kieli),		$thispage, $norm);
		}
		elseif (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_viivakoodi_ean13.inc") {
			// tulosta_lahete_viivakoodi_ean13.inc

			$pdf->draw_rectangle($kala+30, 20,  $kala+10, 40,  	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 40,  $kala+10, 180,  $thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 180, $kala+10, 420, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 420, $kala+10, 480, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 480, $kala+10, 530, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 530, $kala+10, 580, 	$thispage, $rectparam);
			$pdf->draw_text(25,  $kala+15, "#",							$thispage, $norm);
			$pdf->draw_text(45,  $kala+15, t("Tuotenumero", $kieli),	$thispage, $norm);
			$pdf->draw_text(190, $kala+15, t("Tuotenimi", $kieli),		$thispage, $norm);
			$pdf->draw_text(425, $kala+15, t("As.hinta(kpl)", $kieli),		$thispage, $norm);
			$pdf->draw_text(490, $kala+15, t("Tilattu", $kieli),		$thispage, $norm);
			$pdf->draw_text(540, $kala+15, t("Toimitus", $kieli),		$thispage, $norm);
		}
		elseif (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_eialehintoja.inc") {
			//tulosta_lahete_eialehintoja.inc

			$pdf->draw_rectangle($kala+30, 20,  $kala+10, 40,  	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 40,  $kala+10, 180, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 180, $kala+10, 320, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 320, $kala+10, 370, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 370, $kala+10, 420, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 420, $kala+10, 470, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 470, $kala+10, 520, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 520, $kala+10, 580, 	$thispage, $rectparam);
			$pdf->draw_text(25,  $kala+15, "#",							$thispage, $norm);
			$pdf->draw_text(45,  $kala+15, t("Tuotenumero", $kieli),	$thispage, $norm);
			$pdf->draw_text(190, $kala+15, t("Tuotenimi", $kieli),		$thispage, $norm);
			$pdf->draw_text(330, $kala+15, t("Tilattu", $kieli),		$thispage, $norm);
			$pdf->draw_text(375, $kala+15, t("Toimitus", $kieli),		$thispage, $norm);
			$pdf->draw_text(425, $kala+15, t("Yks.hinta", $kieli),		$thispage, $norm);
			$pdf->draw_text(473, $kala+15, t("Rivihinta", $kieli),		$thispage, $norm);
		}
		elseif (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_hae_hinnat.inc" and $GLOBALS['eta_yhtio'] != '') {
			//tulosta_lahete_hae_hinnat.inc

			$pdf->draw_rectangle($kala+30, 20,  $kala+10, 40,  	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 40,  $kala+10, 180, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 180, $kala+10, 320, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 320, $kala+10, 370, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 370, $kala+10, 420, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 420, $kala+10, 470, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 470, $kala+10, 495, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 495, $kala+10, 520, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 520, $kala+10, 580, 	$thispage, $rectparam);
			$pdf->draw_text(25,  $kala+15, "#",							$thispage, $norm);
			$pdf->draw_text(45,  $kala+15, t("Tuotenumero", $kieli),	$thispage, $norm);
			$pdf->draw_text(190, $kala+15, t("Tuotenimi", $kieli),		$thispage, $norm);
			$pdf->draw_text(330, $kala+15, t("Tilattu", $kieli),		$thispage, $norm);
			$pdf->draw_text(375, $kala+15, t("Toimitus", $kieli),		$thispage, $norm);
			$pdf->draw_text(425, $kala+15, t("Yks.hinta", $kieli),		$thispage, $norm);
			$pdf->draw_text(475, $kala+15, t("Ale", $kieli),			$thispage, $norm);
			$pdf->draw_text(500, $kala+15, t("Alv", $kieli),			$thispage, $norm);
			$pdf->draw_text(530, $kala+15, t("Rivihinta", $kieli),		$thispage, $norm);
		}
		elseif (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_custom.inc") {
			$pdf->draw_text(50,  $kala+15, t("Tuotenumero", $kieli),	$thispage, $boldi);
			$pdf->draw_text(150, $kala+15, t("Tuotenimi", $kieli),		$thispage, $boldi);
			$pdf->draw_text(330, $kala+15, t("Toimitus", $kieli),		$thispage, $boldi);
			$pdf->draw_text(380, $kala+15, t("Määrä", $kieli),			$thispage, $boldi);
			$pdf->draw_text(425, $kala+15, t("Yksikkö", $kieli),		$thispage, $boldi);
			$pdf->draw_text(473, $kala+15, t("Hinta alv.", $kieli), $thispage, $boldi);

			if (($laskurow['alv']*1) > 0) {
				$pdf->draw_text(473, $kala+5, t("Verollinen", $kieli), $thispage, $boldi);
			}
			else {
				$pdf->draw_text(473, $kala+5, t("Veroton"),	$thispage, $boldi);
			}

			$kala -= 10;
		}
		else {
			//tulosta_lahete_eiale_eihinta.inc

			$pdf->draw_rectangle($kala+30, 20,  $kala+10, 40,  	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 40,  $kala+10, 180,  $thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 180, $kala+10, 480, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 480, $kala+10, 530, 	$thispage, $rectparam);
			$pdf->draw_rectangle($kala+30, 530, $kala+10, 580, 	$thispage, $rectparam);
			$pdf->draw_text(25,  $kala+15, "#",							$thispage, $norm);
			$pdf->draw_text(45,  $kala+15, t("Tuotenumero", $kieli),	$thispage, $norm);
			$pdf->draw_text(190, $kala+15, t("Tuotenimi", $kieli),		$thispage, $norm);
			$pdf->draw_text(490, $kala+15, t("Tilattu", $kieli),		$thispage, $norm);
			$pdf->draw_text(540, $kala+15, t("Toimitus", $kieli),		$thispage, $norm);
		}
	}
}

if (!function_exists('rivi_lahete')) {
	function rivi_lahete($thispage, $tyyppi="") {
		global $pdf, $page, $lahetetyyppi, $sivu, $row, $kala, $rectparam, $kukarow, $rivinumerot, $kieli, $laskurow, $yhtiorow, $perheid, $jtid, $summa, $norm, $pieni, $rivinkorkeus, $useita, $arvo;

		// viivat rivien väliin...
		$x[0] = 20;
		$x[1] = 580;
		$y[0] = $y[1] = $kala + $rivinkorkeus - 4;

		if (($perheid == 0 or $perheid != $row["perheid"]) and isset($perheid) and str_replace("perhe_", "", $lahetetyyppi) != "tulosta_lahete_custom.inc") {
			$pdf->draw_line($x, $y, $thispage, $rectparam);
		}

		// Jos on paljon rivejä tehdään uusi otsikko...
		if ($kala <= 70) {
			$sivu++;

			loppu_lahete($thispage, 0, $tyyppi);

			$page[$sivu] = uusi_sivu_lahete($tyyppi);
			$thispage    = $page[$sivu];
		}

		// JT-rivit pikkasen alaspäin omalla otsikolla
		if ((!isset($jtid) or $jtid == 0) and $row["jtsort"] == 1) {

			if (isset($jtid)) $kala = $kala-10;

			$x[0] = 20;
			$x[1] = 580;
			$y[0] = $y[1] = $kala - 4;
			$pdf->draw_line($x, $y, $page[$sivu], $rectparam);

			$pdf->draw_text(25,  $kala, "#",									$thispage, $norm);
			$pdf->draw_text(45,  $kala, t("Jälkitoimitukseen", $kieli).":",		$thispage, $norm);
			$kala = $kala-$rivinkorkeus;
		}

		// Rahtituote pikkasen alaspäin omalla otsikolla
		if (isset($jtid) and$jtid != 2 and $row["jtsort"] == 2) {

			$kala = $kala-10;

			$x[0] = 20;
			$x[1] = 580;
			$y[0] = $y[1] = $kala - 4;
			$pdf->draw_line($x, $y, $page[$sivu], $rectparam);

			$pdf->draw_text(25,  $kala, "#",									$thispage, $norm);
			$pdf->draw_text(45,  $kala, t("Toimituskulut", $kieli).":",		$thispage, $norm);
			$kala = $kala-$rivinkorkeus;
		}

		//Käännetään nimitys
		$row['nimitys'] = t_tuotteen_avainsanat($row, 'nimitys', $kieli);

		// Trimmataan
		$row["kommentti"] = trim($row["kommentti"]);

		// Tilaajan rivinumero
		if ($row["tilaajanrivinro"] != 0) {
			$row["kommentti"] .= "\n".t("Rivinumero", $kieli).": ".$row["tilaajanrivinro"].". ";
		}

		// Tutkitaan onko tuotteelle asiakaskommentti
		if (str_replace("perhe_", "", $lahetetyyppi) != "tulosta_lahete_asiakviivakoodi.inc") {
			$askomquery = "	SELECT * from asiakaskommentti
							where yhtio='$kukarow[yhtio]' and tuoteno='$row[tuoteno]' and ytunnus='$laskurow[ytunnus]'";
			$askomresul = mysql_query($askomquery) or pupe_error($askomquery);

			if (mysql_num_rows($askomresul) > 0) {
				while ($askomrow = mysql_fetch_array($askomresul)) {
					$row["kommentti"] .= "\n".$askomrow["kommentti"];
				}
			}
		}

		$row["perhe_kommentti"] = "";

		// Info tuoteperheestä
		if ($yhtiorow["tuoteperheinfo_lahetteella"] == "" and $row["perheid"] > 0 and $perheid != $row["perheid"]) {
			$numrows = 0;

			$query = "	SELECT vanhatunnus
						FROM lasku
						WHERE yhtio	= '$kukarow[yhtio]'
						and tunnus	= '$row[otunnus]'";
			$vtunres = mysql_query($query) or pupe_error($query);
			$vtunrow = mysql_fetch_array($vtunres);

			if ($vtunrow["vanhatunnus"] != 0) {
				$query = " 	SELECT GROUP_CONCAT(distinct tunnus SEPARATOR ',') tunnukset
							FROM lasku use index (yhtio_vanhatunnus)
							WHERE yhtio		= '$kukarow[yhtio]'
							and vanhatunnus = '$vtunrow[vanhatunnus]'";
				$perheresult = mysql_query($query) or pupe_error($query);
				$numrows = mysql_num_rows($perheresult);
			}

			if ($numrows == 0 or $vtunrow["vanhatunnus"] == 0) {
				$query = "  SELECT GROUP_CONCAT(distinct tunnus SEPARATOR ',') tunnukset
							FROM lasku use index (PRIMARY)
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus  = '$row[otunnus]'";
				$perheresult = mysql_query($query) or pupe_error($query);
			}

			if (mysql_num_rows($perheresult) > 0) {
				$perherow = mysql_fetch_array($perheresult);

				$query = "	SELECT distinct tilausrivi.tuoteno, tilausrivi.nimitys, varattu
							FROM tilausrivi
							JOIN tuote tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
							WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
							and tilausrivi.otunnus in ($perherow[tunnukset])
							and tilausrivi.perheid = '$row[perheid]'
							and tuote.tuotetyyppi != 'R'
							and tilausrivi.tyyppi != 'V'
							ORDER BY tilausrivi.tunnus";
				$perheresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($perheresult) > 1) {
					while ($perherow = mysql_fetch_array($perheresult)) {
						if ($row["perhe_kommentti"] == "") {
							$row["perhe_kommentti"] .= t("Tuoteperhe", $kieli).": ";
							$row["perhe_kommentti"] .= $perherow["tuoteno"]." (".$row["varattu"].") ".$perherow["nimitys"].".\n".t("Sisältää tuotteet", $kieli).": ";
						}
						else {
							$row["perhe_kommentti"] .= strtoupper($perherow["tuoteno"]).", ";
						}
					}
					$row["perhe_kommentti"] = substr($row["perhe_kommentti"],0,-2);
				}
			}
		}

		// Trimmataan uudestaan
		$row["kommentti"] = trim($row["kommentti"]);

		//hatetaan tuotteen myyntihinta
		$query = "	SELECT *
                    FROM tuote
                    WHERE tuote.tuoteno='$row[tuoteno]' and tuote.yhtio='$kukarow[yhtio]'";
        $asresult = mysql_query($query) or pupe_error($query);
		$asrow1 = mysql_fetch_array($asresult);

		//saadaan lähetteen summa menemään oikein, taaksepäin yhteensopivasti
		$row['myyntihinta']			= tuotteen_myyntihinta($laskurow, $asrow1, ($row["kpl"]+$row["varattu"]+$row["jt"]), '');
		$row["brutto_rivihinta"] 	= $row["myyntihinta"] * ($row["kpl"]+$row["varattu"]+$row["jt"]);
		$row["aleryhma"] 			= $asrow1["aleryhma"];

		if ($laskurow['valkoodi'] != $yhtiorow['valkoodi']) {
			if ($row["netto"] == 'N') {
				$row["rivihinta"] = $row["hinta"] * ($row["kpl"]+$row["varattu"]+$row["jt"]) * (1-$row["ale"]/100);
			}
			else {
				$row["rivihinta"] = $row["hinta"] * ($row["kpl"]+$row["varattu"]+$row["jt"]) * (1-($row["ale"]+$laskurow["erikoisale"]-($row["ale"]*$laskurow["erikoisale"]/100))/100);
			}

			$row["rivihinta"] = round(laskuval($row["rivihinta"], $laskurow["vienti_kurssi"]), $yhtiorow['hintapyoristys']);
			$row["hinta"] 	  = round(laskuval($row["hinta"], $laskurow["vienti_kurssi"]),$yhtiorow['hintapyoristys']);
			$row["ovhhinta"]  = round(laskuval($row["ovhhinta"], $laskurow["vienti_kurssi"]), $yhtiorow['hintapyoristys']);
		}

		$perheid 	 = $row["perheid"];
		$jtid		 = $row["jtsort"];
		$piilotarivi = "";

		// Alennus kuntoon
		if ($row["netto"] != 'N' and $laskurow["erikoisale"] > 0) {
			$row["ale"] = round((1-(1-($row["ale"]+$laskurow["erikoisale"]-($row["ale"]*$laskurow["erikoisale"]/100))/100))*100,2);
		}

		if ($row["perheid"] > 0 and substr($lahetetyyppi, 0, 6) == "perhe_") {

			// kyseessä on isä
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

					$isahintarivikpl = (float) $row["kpl"]+$row["varattu"]+$row["jt"];

					// lasketaan isätuotteen riville lapsien hinnat yhteen
					$query = "	SELECT
								round(sum(tilausrivi.hinta * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * if (tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),'$yhtiorow[hintapyoristys]') rivihinta,
								round(round(sum(tilausrivi.hinta * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl)),'$yhtiorow[hintapyoristys]') / $isahintarivikpl,'$yhtiorow[hintapyoristys]') hinta,
								GROUP_CONCAT(CONCAT(tilausrivi.tuoteno, '¡!¡!', tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl)) perheen_tuotteet
								FROM tilausrivi
								JOIN lasku ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus
								WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
								and tilausrivi.otunnus in ($perherow[tunnukset])
								and tilausrivi.perheid = '$row[perheid]'";
					$riresult = mysql_query($query) or pupe_error($query);
					$perherow = mysql_fetch_array($riresult);

					$row["hinta"] 		= $perherow["hinta"];
					$row["rivihinta"] 	= $perherow["rivihinta"];

					$row['myyntihinta'] 		= 0;
					$row['brutto_rivihinta'] 	= 0;
					$myyntihinta_temp 			= 0;

					foreach (explode(",", $perherow['perheen_tuotteet']) as $perhetuote) {

						list($perhetuoteno, $perhekpl) = explode('¡!¡!', $perhetuote);

						$query = "SELECT * FROM tuote WHERE yhtio = '{$kukarow['yhtio']}' AND tuoteno = '$perhetuoteno'";
						$lapsi_res = mysql_query($query) or pupe_error($query);
						$lapsi_row = mysql_fetch_assoc($lapsi_res);

						$myyntihinta_temp = tuotteen_myyntihinta($laskurow, $lapsi_row, $perhekpl, '');
						$row['myyntihinta'] += $myyntihinta_temp;
						$row['brutto_rivihinta'] += $myyntihinta_temp * $perhekpl;
					}

					$row["ale"] 		= round(100 * (1 - ($perherow["rivihinta"] / ($perherow["hinta"] * ($row["kpl"]+$row["varattu"]+$row["jt"])))),2);
				}
			}
			else {
				// lapsia ei lisätä
				$piilotarivi = 1;
			}
		}

		if ($useita == "JOO" and $row["toimaika"] != "0000-00-00") {
			$row["kommentti"] .= "\n".t("Toimitusaika", $kieli).": ".tv1dateconv($row["toimaika"]);
		}


		$row["tilkpl"] 	= ($row["tilkpl"]*1);
		$row["varattu"] = ($row["varattu"]*1);
		$row["kpl"] 	= ($row["kpl"]*1);
		$kpl_rivilla = (($row["kpl"]+$row["varattu"]+$row["jt"])*1);

		$row['varattu_orig'] = $kpl_rivilla;

		// Erikseen toimitetuille riveille ei näytetä toimitettu-saraketta
		if ($row["d_erikseen"] == "") {
			if ($row["keratty"] != '' and !in_array($row["var"], array('J','P'))) {
				$row['varattu'] = (($row['varattu']+$row['kpl'])*1)." ".t_avainsana("Y", $kieli, "and avainsana.selite='$row[yksikko]'", "", "", "selite");
				$row['varattu_viivakoodi'] = $kpl_rivilla*1;
			}
			else {
				if ($row["var"] == 'J') { // jälkitoimitus
					$row['varattu'] = t("**JT**", $kieli);
					$row['varattu_viivakoodi'] = 0;
				}
				elseif ($row["var"] == 'P') { // puute
					$row['varattu'] = t("PUUTE", $kieli);
					$row['varattu_viivakoodi'] = 0;
				}
				else {
					$row["varattu"] = "";
					$row["kpl"] 	= "";
					$row['varattu_viivakoodi'] = 0;
				}
			}
		}
		else {
			$row["varattu"] = "";
		}

		//Haetan sarjanumeron tiedot
		if ($tyyppi == "SIIRTOLISTA") {
			$sarjanutunnus = "siirtorivitunnus";
		}
		elseif ($row["varattu"]+$row["kpl"] < 0){
			$sarjanutunnus = "ostorivitunnus";
		}
		else {
			$sarjanutunnus = "myyntirivitunnus";
		}

		$query = "	SELECT distinct sarjanumero
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
			if ($asrow1["sarjanumeroseuranta"] == "E" or $asrow1["sarjanumeroseuranta"] == "F" or $asrow1["sarjanumeroseuranta"] == "G") {
				$row["kommentti"] .= t("E:nro", $kieli).": $sarjarow[sarjanumero]\n";
			}
			else {
				$row["kommentti"] .= t("S:nro", $kieli).": $sarjarow[sarjanumero]\n";
			}
		}

		// Mahtuuko rivi tälle sivulle
		$kala_nyt = $kala;

		if (substr($lahetetyyppi, 0, 6) != "perhe_" or $piilotarivi == "") {
			if (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete.inc") {
				rivi_hinta_ale_lahete($devnull);
			}
			elseif (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_brutto.inc") {
				rivi_brutto_lahete($devnull);
			}
			elseif (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_eialeja.inc") {
				rivi_eialeja_lahete($devnull);
			}
			elseif (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_viivakoodi.inc") {
				rivi_viivakoodi_lahete($devnull);
			}
			elseif (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_asiakviivakoodi.inc") {
				rivi_asiakviivakoodi_lahete($devnull);
			}
			elseif (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_viivakoodi_ean13.inc") {
				rivi_viivakoodi_lahete_ean13($devnull);
			}
			elseif (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_eialehintoja.inc") {
				rivi_eialehintoja_lahete($devnull);
			}
			elseif (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_hae_hinnat.inc" and $GLOBALS['eta_yhtio'] != '') {
				rivi_hae_hinnat_lahete($devnull);
			}
			elseif (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_custom.inc") {
				rivi_custom_lahete($devnull);
			}
			else {
				rivi_ei_ale_ei_hinta_lahete($devnull);
			}
		}

		// Jos on paljon rivejä tehdään uusi otsikko...
		if ($kala <= 70 or $kala > $kala_nyt) {
			$sivu++;

			loppu_lahete($thispage, 0, $tyyppi);

			$page[$sivu] = uusi_sivu_lahete($tyyppi);
			$thispage    = $page[$sivu];
		}
		else {
			$kala = $kala_nyt;
		}

		if (substr($lahetetyyppi, 0, 6) != "perhe_" or $piilotarivi == "") {
			if (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete.inc") {
				// tulosta_lahete.inc
				if (($row["var"] != 'J' or ($tyyppi == "TILAUSVAHVISTUS" and strpos($laskurow['tilausvahvistus'], 'JT') !== FALSE)) and $row["var"] != 'P') {
					$summa += $row['rivihinta'];
				}
				else {
					$row['rivihinta'] = 0;
				}

				rivi_hinta_ale_lahete($thispage);
			}
			elseif (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_brutto.inc") {
				// tulosta_lahete_brutto.inc
				if (($row["var"] != 'J' or ($tyyppi == "TILAUSVAHVISTUS" and strpos($laskurow['tilausvahvistus'], 'JT') !== FALSE)) and $row["var"] != 'P') {
					$summa += round($row['ovhhinta'] * $kpl_rivilla, 2);
				}
				else {
					$row['rivihinta'] = 0;
				}

				rivi_brutto_lahete($thispage);
			}
			elseif (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_eialeja.inc") {
				//tulosta_lahete_eialeja.inc
				if (($row["var"] != 'J' or ($tyyppi == "TILAUSVAHVISTUS" and strpos($laskurow['tilausvahvistus'], 'JT') !== FALSE)) and $row["var"] != 'P') {
					$summa += $row['brutto_rivihinta'];
				}
				else {
					$row['rivihinta'] = 0;
				}

				rivi_eialeja_lahete($thispage);
			}
			elseif (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_viivakoodi.inc") {
				// tulosta_lahete_viivakoodi.inc
				rivi_viivakoodi_lahete($thispage);
			}
			elseif (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_asiakviivakoodi.inc") {
				// tulosta_lahete_asiakviivakoodi.inc
				rivi_asiakviivakoodi_lahete($thispage);
			}
			elseif (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_viivakoodi_ean13.inc") {
				rivi_viivakoodi_lahete_ean13($thispage);
			}
			elseif (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_eialehintoja.inc") {
				rivi_eialehintoja_lahete($thispage);
			}
			elseif (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_hae_hinnat.inc" and $GLOBALS['eta_yhtio'] != '') {
				rivi_hae_hinnat_lahete($thispage);
			}
			elseif (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_custom.inc") {
				$summa += $row['rivihinta'];
				$arvo += ($row['rivihinta'] / (1 + $row['alv'] / 100));

				rivi_custom_lahete($thispage);
			}
			else {
				//tulosta_lahete_eiale_eihinta.inc
				//tulosta_lahete_eiale_eihinta_iso.inc
				rivi_ei_ale_ei_hinta_lahete($thispage);
			}
		}
	}
}

if (!function_exists('rivi_hinta_ale_lahete')) {
	function rivi_hinta_ale_lahete($thispage) {
		global $pdf, $row, $kala, $rivinumerot, $norm, $pieni, $rivinkorkeus, $yhtiorow, $extranet_tilausvahvistus, $kieli;

		if (trim($row["perhe_kommentti"]) != '') {
			$pohja = $pdf->draw_paragraph($kala+$norm["height"], 45, 60, 480, $row["perhe_kommentti"],	$thispage, $norm);
			$kala = $pohja - $rivinkorkeus;
		}

		$pdf->draw_text(23,  $kala, $rivinumerot[$row["tunnus"]],							$thispage, $pieni);

		list($ff_string, $ff_font) = pdf_fontfit($row["tuoteno"], 140, $pdf, $norm);
		$pdf->draw_text(45, $kala, $ff_string, $thispage, $ff_font);

		$omyks = t_avainsana("Y", $kieli, "and avainsana.selite='$row[yksikko]'", "", "", "selite");

		$oikpos = $pdf->strlen($row["tilkpl"]." ".$omyks, $norm);
		$pdf->draw_text(365-$oikpos, $kala, $row["tilkpl"]." ".$omyks, $thispage, $norm);

		$oikpos = $pdf->strlen($row["varattu"], $norm);
		$pdf->draw_text(415-$oikpos, $kala, $row["varattu"], 								$thispage, $norm);

		$oikpos = $pdf->strlen(sprintf("%.".$yhtiorow['hintapyoristys']."f", $row["hinta"]), $norm);
		$pdf->draw_text(465-$oikpos, $kala, sprintf("%.".$yhtiorow['hintapyoristys']."f", $row["hinta"]), 				$thispage, $norm);


		list($ff_string, $ff_font) = pdf_fontfit(($row["ale"]*1)."%", 25, $pdf, $norm);

		$oikpos = $pdf->strlen($ff_string, $ff_font);
		$pdf->draw_text(495-$oikpos, $kala, $ff_string, $thispage, $ff_font);

		if ($yhtiorow["alv_kasittely"] != '') {
			$row["alv"] = "0";
		}

		$oikpos = $pdf->strlen(($row["alv"]*1)."%", $norm);
		$pdf->draw_text(520-$oikpos, $kala, ($row["alv"]*1)."%", 							$thispage, $norm);

		$oikpos = $pdf->strlen(sprintf("%.".$yhtiorow['hintapyoristys']."f", $row["rivihinta"]), $norm);
		$pdf->draw_text(575-$oikpos, $kala, sprintf("%.".$yhtiorow['hintapyoristys']."f", $row["rivihinta"]), 			$thispage, $norm);

		if ($pdf->strlen($row["nimitys"], $norm) >= 130) {
			$kala = $kala - $rivinkorkeus;
		}

		$pdf->draw_text(190, $kala, $row["nimitys"], 										$thispage, $norm);

		$kala = $kala - $rivinkorkeus;

		if ($row["kommentti"] != '') {
			$pohja = $pdf->draw_paragraph($kala+$norm["height"]+5, 45, 60, 580, $row["kommentti"],	$thispage, $norm);
			$kala = $pohja - $rivinkorkeus;
		}
	}
}

if (!function_exists('rivi_custom_lahete')) {
	function rivi_custom_lahete($thispage) {
		global $pdf, $row, $kala, $rivinumerot, $norm, $pieni, $rivinkorkeus, $yhtiorow, $extranet_tilausvahvistus, $kieli, $laskurow, $boldi;

		list($ff_string, $ff_font) = pdf_fontfit($row["tuoteno"], 140, $pdf, $boldi);
		$pdf->draw_text(50, $kala, $ff_string, $thispage, $ff_font);

		list($vv, $kk, $pp) = explode("-", $laskurow["toimaika"]);
		$vko = date("W", mktime(0, 0, 0, $kk, $pp, $vv));

		if ($vko == date("W", mktime(0, 0, 0, 12, 31, $vv))) {
			$vko .= ' - 1';
		}
		else {
			$vko .= ' - '.(date("W", mktime(0, 0, 0, $kk, $pp, $vv)) + 1);
		}

		$pdf->draw_text(330, $kala, t("Vko", $kieli)." $vko", $thispage, $norm);

		$pdf->draw_text(380, $kala, $row["tilkpl"], $thispage, $norm);

		$omyks = t_avainsana("Y", $kieli, "and avainsana.selite='$row[yksikko]'", "", "", "selite");
		$pdf->draw_text(425, $kala, $omyks, $thispage, $norm);

		if ($yhtiorow["alv_kasittely"] != '') {
			$row["alv"] = "0";
		}

		$oikpos = $pdf->strlen(sprintf("%.".$yhtiorow['hintapyoristys']."f", $row["rivihinta"]), $norm);
		$pdf->draw_text(473, $kala, sprintf("%.".$yhtiorow['hintapyoristys']."f", $row["rivihinta"]), 			$thispage, $norm);

		if ($pdf->strlen($row["nimitys"], $norm) >= 130) {
			$kala = $kala - $rivinkorkeus;
		}

		$pdf->draw_text(150, $kala, $row["nimitys"]." $arvo", $thispage, $norm);

		$kala = $kala - $rivinkorkeus;

		if ($row["kommentti"] != '') {
			$pohja = $pdf->draw_paragraph($kala+$norm["height"]+5, 150, 60, 580, $row["kommentti"],	$thispage, $norm);
			$kala = $pohja - $rivinkorkeus;
		}
	}
}

if (!function_exists('rivi_brutto_lahete')) {
	function rivi_brutto_lahete($thispage) {
		global $pdf, $row, $kala, $rivinumerot, $norm, $pieni, $rivinkorkeus, $yhtiorow, $kieli;

		$pdf->draw_text(23,  $kala, $rivinumerot[$row["tunnus"]],							$thispage, $pieni);

		if (trim($row["perhe_kommentti"]) != '') {
			$pohja = $pdf->draw_paragraph($kala+$norm["height"], 45, 60, 480, $row["perhe_kommentti"],	$thispage, $norm);
			$kala = $pohja - $rivinkorkeus;
		}

		list($ff_string, $ff_font) = pdf_fontfit($row["tuoteno"], 100, $pdf, $norm);
		$pdf->draw_text(45, $kala, $ff_string, $thispage, $ff_font);

		$omyks = t_avainsana("Y", $kieli, "and avainsana.selite='$row[yksikko]'", "", "", "selite");

		$oikpos = $pdf->strlen($row["tilkpl"]." ".$omyks, $norm);
		$pdf->draw_text(465-$oikpos, $kala, $row["tilkpl"]." ".$omyks, $thispage, $norm);

		$oikpos = $pdf->strlen($row["varattu"], $norm);
		$pdf->draw_text(515-$oikpos, $kala, $row["varattu"], 								$thispage, $norm);

		$oikpos = $pdf->strlen(sprintf("%.".$yhtiorow['hintapyoristys']."f", $row["ovhhinta"]), $norm);
		$pdf->draw_text(575-$oikpos, $kala, sprintf("%.".$yhtiorow['hintapyoristys']."f", $row["ovhhinta"]), 				$thispage, $norm);

		if ($pdf->strlen($row["nimitys"], $norm) >= 270) {
			$kala = $kala - $rivinkorkeus;
		}
		$pdf->draw_text(145, $kala, $row["nimitys"], 										$thispage, $norm);

		$kala = $kala - $rivinkorkeus;

		if ($row["kommentti"] != '') {
			$pohja = $pdf->draw_paragraph($kala+$norm["height"]+5, 45, 60, 580, $row["kommentti"],	$thispage, $norm);
			$kala = $pohja - $rivinkorkeus;
		}
	}
}

if (!function_exists('rivi_ei_ale_ei_hinta_lahete')) {
	function rivi_ei_ale_ei_hinta_lahete($thispage) {
		global $pdf, $row, $kala, $rivinumerot, $norm, $pieni, $rivinkorkeus, $kieli, $lahetetyyppi;

		$lisarivi = 0;
		$pdf->draw_text(23,  $kala, $rivinumerot[$row["tunnus"]],							$thispage, $pieni);

		if (trim($row["perhe_kommentti"]) != '') {
			$pohja = $pdf->draw_paragraph($kala+$norm["height"], 45, 60, 480, $row["perhe_kommentti"],	$thispage, $norm);
			$kala = $pohja - $rivinkorkeus;
			$lisarivi++;
		}

		list($ff_string, $ff_font) = pdf_fontfit($row["tuoteno"], 140, $pdf, $norm);
		$pdf->draw_text(45, $kala, $ff_string, $thispage, $ff_font);

		$omyks = t_avainsana("Y", $kieli, "and avainsana.selite='$row[yksikko]'", "", "", "selite");

		$oikpos = $pdf->strlen($row["tilkpl"]." ".$omyks, $norm);
		$pdf->draw_text(525-$oikpos, $kala, $row["tilkpl"]." ".$omyks, $thispage, $norm);

		$oikpos = $pdf->strlen($row["varattu"], $norm);
		$pdf->draw_text(575-$oikpos, $kala, $row["varattu"], 								$thispage, $norm);

		if ($pdf->strlen($row["nimitys"], $norm) >= 290) {
			$kala = $kala - $rivinkorkeus;
			$lisarivi++;
		}
		$pdf->draw_text(190, $kala, $row["nimitys"], 										$thispage, $norm);

		$kala = $kala - $rivinkorkeus;

		if ($row["kommentti"] != '') {
			$pohja = $pdf->draw_paragraph($kala+$norm["height"]+5, 45, 60, 580, $row["kommentti"],	$thispage, $norm);
			$kala = $pohja - $rivinkorkeus;
			$lisarivi++;
		}

		// jos halutaan isommat rivit niin tehdään yks tyhjä (eillei oo jo kommenttia tai pitkää nimitystä)
		if (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_eiale_eihinta_iso.inc" and $lisarivi == 0) {
			$kala = $kala - $rivinkorkeus;
		}

	}
}

if (!function_exists('rivi_eialeja_lahete')) {
	function rivi_eialeja_lahete($thispage) {
		global $pdf, $row, $kala, $rivinumerot, $norm, $pieni, $rivinkorkeus, $yhtiorow, $extranet_tilausvahvistus, $kieli;

		$pdf->draw_text(23,  $kala, $rivinumerot[$row["tunnus"]],							$thispage, $pieni);

		if (trim($row["perhe_kommentti"]) != '') {
			$pohja = $pdf->draw_paragraph($kala+$norm["height"], 45, 60, 480, $row["perhe_kommentti"],	$thispage, $norm);
			$kala = $pohja - $rivinkorkeus;
		}

		list($ff_string, $ff_font) = pdf_fontfit($row["tuoteno"], 140, $pdf, $norm);
		$pdf->draw_text(45, $kala, $ff_string, $thispage, $ff_font);

		$omyks = t_avainsana("Y", $kieli, "and avainsana.selite='$row[yksikko]'", "", "", "selite");

		$oikpos = $pdf->strlen($row["tilkpl"]." ".$omyks, $norm);
		$pdf->draw_text(365-$oikpos, $kala, $row["tilkpl"]." ".$omyks, $thispage, $norm);

		$oikpos = $pdf->strlen($row["varattu"], $norm);
		$pdf->draw_text(415-$oikpos, $kala, $row["varattu"], 								$thispage, $norm);


		$oikpos = $pdf->strlen(sprintf("%.".$yhtiorow['hintapyoristys']."f", $row["myyntihinta"]), $norm);
		$pdf->draw_text(465-$oikpos, $kala, sprintf("%.".$yhtiorow['hintapyoristys']."f", $row["myyntihinta"]), 							$thispage, $norm);

		if ($extranet_tilausvahvistus != 1) {
			list($ff_string, $ff_font) = pdf_fontfit($row["aleryhma"], 50, $pdf, $norm);
			$pdf->draw_text(475, $kala, $ff_string, $thispage, $ff_font);
		}

		$oikpos = $pdf->strlen(sprintf("%.".$yhtiorow['hintapyoristys']."f", $row["brutto_rivihinta"]), $norm);
		$pdf->draw_text(575-$oikpos, $kala, sprintf("%.".$yhtiorow['hintapyoristys']."f", $row["brutto_rivihinta"]), 		$thispage, $norm);

		if ($pdf->strlen($row["nimitys"], $norm) >= 135) {
			$kala = $kala - $rivinkorkeus;
		}
		$pdf->draw_text(190, $kala, $row["nimitys"], 										$thispage, $norm);

		$kala = $kala - $rivinkorkeus;

		if ($row["kommentti"] != '') {
			$pohja = $pdf->draw_paragraph($kala+$norm["height"]+5, 45, 60, 580, $row["kommentti"],	$thispage, $norm);
			$kala = $pohja - $rivinkorkeus;
		}
	}
}

if (!function_exists('rivi_eialehintoja_lahete')) {
	function rivi_eialehintoja_lahete($thispage) {
		global $pdf, $row, $kala, $rivinumerot, $norm, $pieni, $rivinkorkeus, $yhtiorow, $extranet_tilausvahvistus, $kieli;

		$pdf->draw_text(23,  $kala, $rivinumerot[$row["tunnus"]],							$thispage, $pieni);

		if (trim($row["perhe_kommentti"]) != '') {
			$pohja = $pdf->draw_paragraph($kala+$norm["height"], 45, 60, 480, $row["perhe_kommentti"],	$thispage, $norm);
			$kala = $pohja - $rivinkorkeus;
		}

		list($ff_string, $ff_font) = pdf_fontfit($row["tuoteno"], 140, $pdf, $norm);
		$pdf->draw_text(45, $kala, $ff_string, $thispage, $ff_font);

		$omyks = t_avainsana("Y", $kieli, "and avainsana.selite='$row[yksikko]'", "", "", "selite");

		$oikpos = $pdf->strlen($row["tilkpl"]." ".$omyks, $norm);
		$pdf->draw_text(365-$oikpos, $kala, $row["tilkpl"]." ".$omyks, $thispage, $norm);

		$oikpos = $pdf->strlen($row["varattu"], $norm);
		$pdf->draw_text(415-$oikpos, $kala, $row["varattu"], 								$thispage, $norm);

		$oikpos = $pdf->strlen($row["hinta"], $norm);
		$pdf->draw_text(465-$oikpos, $kala, $row["hinta"], 							$thispage, $norm);

		$rivihinta = $row['hinta'] * $row['tilkpl'];

		$oikpos = $pdf->strlen(sprintf("%.".$yhtiorow['hintapyoristys']."f", $rivihinta), $norm);
		$pdf->draw_text(515-$oikpos, $kala, sprintf("%.".$yhtiorow['hintapyoristys']."f", $rivihinta), 		$thispage, $norm);

		if ($pdf->strlen($row["nimitys"], $norm) >= 135) {
			$kala = $kala - $rivinkorkeus;
		}
		$pdf->draw_text(190, $kala, $row["nimitys"], 										$thispage, $norm);

		$kala = $kala - $rivinkorkeus;

		if ($row["kommentti"] != '') {
			$pohja = $pdf->draw_paragraph($kala+$norm["height"]+5, 45, 60, 580, $row["kommentti"],	$thispage, $norm);
			$kala = $pohja - $rivinkorkeus;
		}
	}
}

if (!function_exists('rivi_hae_hinnat_lahete')) {
	function rivi_hae_hinnat_lahete($thispage) {
		global $pdf, $row, $kala, $rivinumerot, $norm, $pieni, $rivinkorkeus, $yhtiorow, $extranet_tilausvahvistus, $kieli, $laskurow;

		if (trim($row["perhe_kommentti"]) != '') {
			$pohja = $pdf->draw_paragraph($kala+$norm["height"], 45, 60, 480, $row["perhe_kommentti"],	$thispage, $norm);
			$kala = $pohja - $rivinkorkeus;
		}

		$pdf->draw_text(23,  $kala, $rivinumerot[$row["tunnus"]],							$thispage, $pieni);

		list($ff_string, $ff_font) = pdf_fontfit($row["tuoteno"], 140, $pdf, $norm);
		$pdf->draw_text(45, $kala, $ff_string, $thispage, $ff_font);

		$omyks = t_avainsana("Y", $kieli, "and avainsana.selite='$row[yksikko]'", "", "", "selite");

		$oikpos = $pdf->strlen($row["tilkpl"]." ".$omyks, $norm);
		$pdf->draw_text(365-$oikpos, $kala, $row["tilkpl"]." ".$omyks, $thispage, $norm);

		$oikpos = $pdf->strlen($row["varattu"], $norm);
		$pdf->draw_text(415-$oikpos, $kala, $row["varattu"], 								$thispage, $norm);

		$query = "	SELECT *
					FROM tuote
					WHERE yhtio = '{$GLOBALS['eta_yhtio']}'
					AND tuoteno = '{$row['tuoteno']}'";
		$tres = mysql_query($query) or pupe_error($query);
		$trow = mysql_fetch_assoc($tres);

		list($lis_hinta, $lis_netto, $lis_ale, $alehinta_alv, $alehinta_val) = alehinta($laskurow, $trow, $row["varattu"], '', '', '', '', $GLOBALS['eta_yhtio']);

		$oikpos = $pdf->strlen(sprintf("%.".$yhtiorow['hintapyoristys']."f", $lis_hinta), $norm);
		$pdf->draw_text(465-$oikpos, $kala, sprintf("%.".$yhtiorow['hintapyoristys']."f", $lis_hinta), 				$thispage, $norm);

		list($ff_string, $ff_font) = pdf_fontfit(($lis_ale*1)."%", 25, $pdf, $norm);

		$oikpos = $pdf->strlen($ff_string, $ff_font);
		$pdf->draw_text(495-$oikpos, $kala, $ff_string, $thispage, $ff_font);

		if ($yhtiorow["alv_kasittely"] != '') {
			$row["alv"] = "0";
		}

		$oikpos = $pdf->strlen(($row["alv"]*1)."%", $norm);
		$pdf->draw_text(520-$oikpos, $kala, ($row["alv"]*1)."%", 							$thispage, $norm);

		$rivihinta = $lis_hinta * (1-($lis_ale/100)) * $row['varattu_orig'];

		$oikpos = $pdf->strlen(sprintf("%.".$yhtiorow['hintapyoristys']."f", $rivihinta), $norm);
		$pdf->draw_text(575-$oikpos, $kala, sprintf("%.".$yhtiorow['hintapyoristys']."f", $rivihinta), 			$thispage, $norm);

		if ($pdf->strlen($row["nimitys"], $norm) >= 130) {
			$kala = $kala - $rivinkorkeus;
		}

		$pdf->draw_text(190, $kala, $row["nimitys"], 										$thispage, $norm);

		$kala = $kala - $rivinkorkeus;

		if ($row["kommentti"] != '') {
			$pohja = $pdf->draw_paragraph($kala+$norm["height"]+5, 45, 60, 580, $row["kommentti"],	$thispage, $norm);
			$kala = $pohja - $rivinkorkeus;
		}
	}
}

if (!function_exists('rivi_viivakoodi_lahete')) {
	function rivi_viivakoodi_lahete($thispage) {
		global $pdf, $row, $kala, $rivinumerot, $norm, $pieni, $rivinkorkeus, $kukarow, $kieli;

		$pdf->draw_text(23,  $kala, $rivinumerot[$row["tunnus"]],								$thispage, $pieni);

		if (trim($row["perhe_kommentti"]) != '') {
			$pohja = $pdf->draw_paragraph($kala+$norm["height"], 45, 60, 480, $row["perhe_kommentti"],	$thispage, $norm);
			$kala = $pohja - $rivinkorkeus;
		}

		list($ff_string, $ff_font) = pdf_fontfit($row["tuoteno"], 140, $pdf, $norm);
		$pdf->draw_text(45, $kala, $ff_string, $thispage, $ff_font);

		$omyks = t_avainsana("Y", $kieli, "and avainsana.selite='$row[yksikko]'", "", "", "selite");

		$oikpos = $pdf->strlen($row["tilkpl"]." ".$omyks, $norm);
		$pdf->draw_text(525-$oikpos, $kala, $row["tilkpl"]." ".$omyks, 	$thispage, $norm);

		$oikpos = $pdf->strlen($row["varattu"], $norm);
		$pdf->draw_text(575-$oikpos, $kala, $row["varattu"], 									$thispage, $norm);

		if ($pdf->strlen($row["nimitys"], $norm) >= 290) {
			$kala = $kala - $rivinkorkeus;
		}
		$pdf->draw_text(190, $kala, $row["nimitys"], 											$thispage, $norm);

		$kala = $kala - $rivinkorkeus;

		if ($row["kommentti"] != '') {
			$pohja = $pdf->draw_paragraph($kala+$norm["height"]+5, 45, 60, 580, $row["kommentti"],	$thispage, $norm);
			$kala = $pohja - $rivinkorkeus;
		}

		// tutkitaan onko tuotteelle eankoodi
		$eanquery = "	select eankoodi
						from tuote
						where yhtio='$kukarow[yhtio]' and tuoteno='$row[tuoteno]'";
		$eanres = mysql_query($eanquery) or pupe_error($eanquery);
		$eanrow = mysql_fetch_array($eanres);

		if ($eanrow["eankoodi"] != "") {
			//viivakoodi
			$nimi = "/tmp/".md5(uniqid(rand(),true)).".jpg";

			imagejpeg(Image_Barcode::draw($eanrow["eankoodi"], 'Code39', 'jpg', false, 1, 25, false), $nimi, 90);

			$fh = fopen($nimi, "r");
			$data = fread($fh, filesize($nimi));
			fclose($fh);

			$image = $pdf->jfif_embed($data);
			$pdf->image_place($image, $kala-$rivinkorkeus, 320, $thispage);

			system("rm -f $nimi");

			$kala = $kala-$rivinkorkeus-$rivinkorkeus;
		}

	}
}

if (!function_exists('rivi_asiakviivakoodi_lahete')) {
	function rivi_asiakviivakoodi_lahete($thispage) {
		global $pdf, $row, $laskurow, $kala, $rivinumerot, $norm, $pieni, $rivinkorkeus, $kukarow, $kieli;

		$pdf->draw_text(23,  $kala, $rivinumerot[$row["tunnus"]],								$thispage, $pieni);

		if (trim($row["perhe_kommentti"]) != '') {
			$pohja = $pdf->draw_paragraph($kala+$norm["height"], 45, 60, 480, $row["perhe_kommentti"],	$thispage, $norm);
			$kala = $pohja - $rivinkorkeus;
		}

		list($ff_string, $ff_font) = pdf_fontfit($row["tuoteno"], 140, $pdf, $norm);
		$pdf->draw_text(45, $kala, $ff_string, $thispage, $ff_font);

		$omyks = t_avainsana("Y", $kieli, "and avainsana.selite='$row[yksikko]'", "", "", "selite");

		$oikpos = $pdf->strlen($row["tilkpl"]." ".$omyks, $norm);
		$pdf->draw_text(525-$oikpos, $kala, $row["tilkpl"]." ".$omyks, 	$thispage, $norm);

		$oikpos = $pdf->strlen($row["varattu"], $norm);
		$pdf->draw_text(575-$oikpos, $kala, $row["varattu"], 									$thispage, $norm);

		if ($pdf->strlen($row["nimitys"], $norm) >= 290) {
			$kala = $kala - $rivinkorkeus;
		}
		$pdf->draw_text(190, $kala, $row["nimitys"], 											$thispage, $norm);

		$kala = $kala - $rivinkorkeus;

		if ($row["kommentti"] != '') {
			$pohja = $pdf->draw_paragraph($kala+$norm["height"]+5, 45, 60, 580, $row["kommentti"],	$thispage, $norm);
			$kala = $pohja - $rivinkorkeus;
		}

		// tutkitaan onko tuotteelle kommentti
		$eanquery = "	select kommentti
						from asiakaskommentti
						where yhtio='$kukarow[yhtio]' and ytunnus='$laskurow[ytunnus]' and tuoteno='$row[tuoteno]'";
		$eanres = mysql_query($eanquery) or pupe_error($eanquery);
		$eanrow = mysql_fetch_array($eanres);

		if ($eanrow["kommentti"] != "") {
			//viivakoodi
			$nimi = "/tmp/".md5(uniqid(rand(),true)).".jpg";

			imagejpeg(Image_Barcode::draw($eanrow["kommentti"], 'Code39', 'jpg', false, 1, 25, false), $nimi, 90);

			$fh = fopen($nimi, "r");
			$data = fread($fh, filesize($nimi));
			fclose($fh);

			$image = $pdf->jfif_embed($data);
			$pdf->image_place($image, $kala-15, 45, $thispage);

			//viivakoodi
			$nimi = "/tmp/".md5(uniqid(rand(),true)).".jpg";

			imagejpeg(Image_Barcode::draw($row['varattu_viivakoodi'], 'Code39', 'jpg', false, 1, 25, false), $nimi, 90);

			$fh = fopen($nimi, "r");
			$data = fread($fh, filesize($nimi));
			fclose($fh);

			$image = $pdf->jfif_embed($data);
			$pdf->image_place($image, $kala-15, 450, $thispage);

			system("rm -f $nimi");


			$kala = $kala - 30;

			system("rm -f $nimi");
		}
	}
}

if (!function_exists('rivi_viivakoodi_lahete_ean13')) {
	function rivi_viivakoodi_lahete_ean13($thispage) {
		global $pdf, $row, $kala, $rivinumerot, $norm, $pieni, $rivinkorkeus, $kukarow, $kieli;

		$pdf->draw_text(23,  $kala, $rivinumerot[$row["tunnus"]],								$thispage, $pieni);

		if (trim($row["perhe_kommentti"]) != '') {
			$pohja = $pdf->draw_paragraph($kala+$norm["height"], 45, 60, 480, $row["perhe_kommentti"],	$thispage, $norm);
			$kala = $pohja - $rivinkorkeus;
		}

		list($ff_string, $ff_font) = pdf_fontfit($row["tuoteno"], 140, $pdf, $norm);
		$pdf->draw_text(45, $kala, $ff_string, $thispage, $ff_font);

		$as_hinta = $row["hinta"] * (1 - $row["ale"] / 100);

		$oikpos = $pdf->strlen(sprintf("%.".$yhtiorow['hintapyoristys']."f", $as_hinta), $norm);
		$pdf->draw_text(475-$oikpos, $kala, sprintf("%.".$yhtiorow['hintapyoristys']."f", $as_hinta), 			$thispage, $norm);

		$omyks = t_avainsana("Y", $kieli, "and avainsana.selite='$row[yksikko]'", "", "", "selite");

		$oikpos = $pdf->strlen($row["tilkpl"]." ".$omyks, $norm);
		$pdf->draw_text(525-$oikpos, $kala, $row["tilkpl"]." ".$omyks, 	$thispage, $norm);

		$oikpos = $pdf->strlen($row["varattu"], $norm);
		$pdf->draw_text(575-$oikpos, $kala, $row["varattu"], 									$thispage, $norm);

		if ($pdf->strlen($row["nimitys"], $norm) >= 290) {
			$kala = $kala - $rivinkorkeus;
		}
		$pdf->draw_text(190, $kala, $row["nimitys"], 											$thispage, $norm);

		$kala = $kala - $rivinkorkeus;

		if ($row["kommentti"] != '') {
			$pohja = $pdf->draw_paragraph($kala+$norm["height"]+5, 45, 60, 580, $row["kommentti"],	$thispage, $norm);
			$kala = $pohja - $rivinkorkeus;
		}

		// tutkitaan onko tuotteelle eankoodi
		$eanquery = "	select eankoodi
						from tuote
						where yhtio='$kukarow[yhtio]' and tuoteno='$row[tuoteno]'";
		$eanres = mysql_query($eanquery) or pupe_error($eanquery);
		$eanrow = mysql_fetch_array($eanres);


		if ($eanrow["eankoodi"] != "" and $eanrow["eankoodi"] != "0" and strlen($eanrow["eankoodi"]) == "13" and is_numeric($eanrow["eankoodi"])) {
			//viivakoodi
			$nimi = "/tmp/".md5(uniqid(rand(),true)).".jpg";

			imagejpeg(Image_Barcode::draw($eanrow["eankoodi"], 'ean13', 'jpg', false, 1, 10, false), $nimi, 90);

			$fh = fopen($nimi, "r");
			$data = fread($fh, filesize($nimi));
			fclose($fh);

			$kala -= 5;
			$image = $pdf->jfif_embed($data);
			$pdf->image_place($image, $kala-$rivinkorkeus, 320, $thispage);

			system("rm -f $nimi");

			$kala = $kala-$rivinkorkeus-$rivinkorkeus;
		}

	}
}

if (!function_exists('loppu_lahete')) {
	function loppu_lahete($thispage, $tots, $tyyppi = "") {
		global $pdf, $sivu, $laskurow, $viite, $pieni, $norm, $summa, $rectparam, $kieli, $yhtiorow, $kala, $rivinkorkeus, $page, $lahetetyyppi, $boldi, $arvo, $kukarow;

		// yhtiötiedot
		$y_puhelin		= $yhtiorow['puhelin'];
		$y_fax			= $yhtiorow['fax'];
		$y_email		= $yhtiorow['email'];
		$y_www			= $yhtiorow['www'];
		$y_nimi 		= $laskurow["yhtio_nimi"];
		$y_osoite 		= $laskurow["yhtio_osoite"];
		$y_postino	 	= $laskurow["yhtio_postino"];
		$y_postitp	 	= $laskurow["yhtio_postitp"];
		$y_maa 			= $laskurow["yhtio_maa"];
		$y_kotipaikka	= $laskurow["yhtio_kotipaikka"];
		$y_vatnumero	= str_replace("0037", "", $laskurow["yhtio_ovttunnus"]);
		$y_iban 		= $yhtiorow['pankkiiban1'];
		$y_swift 		= $yhtiorow['pankkiswift1'];

		if ((int) $laskurow["yhtio_toimipaikka"] != 0) {
			// haetaan toimipaikan tiedot
			$alhqur = "SELECT * from yhtion_toimipaikat where yhtio='$laskurow[yhtio]' and vat_numero='$laskurow[yhtio_toimipaikka]'";
			$alhire = mysql_query($alhqur) or pupe_error($alhqur);

			if (mysql_num_rows($alhire) == 1) {
				// haetaan toimipaikan tiedot
				$alhiro = mysql_fetch_array($alhire);
				$y_puhelin		= $alhiro['puhelin'];
				$y_fax			= $alhiro['fax'];
				$y_email		= $alhiro['email'];
			}
		}

		// jos meillä on lasku menossa ulkomaille ja se laskutetaan eri ovttunnuksella
		if ($laskurow["yhtio_ovttunnus"] != "" and $laskurow["yhtio_ovttunnus"] != $yhtiorow["ovttunnus"]) {
			// haetaan toimipaikan tiedot
			$alhqur = "SELECT * from yhtion_toimipaikat where yhtio='$laskurow[yhtio]' and vat_numero='$laskurow[yhtio_ovttunnus]'";
			$alhire = mysql_query($alhqur) or pupe_error($alhqur);

			if (mysql_num_rows($alhire) == 1) {
				// haetaan toimipaikan tiedot
				$alhiro = mysql_fetch_array($alhire);
				$y_puhelin		= $alhiro['puhelin'];
				$y_fax			= $alhiro['fax'];
				$y_email		= $alhiro['email'];
			}
		}

		if (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_hae_hinnat.inc" and $GLOBALS['eta_yhtio'] != '') {
			// etäyhtiön yhtiötiedot
			$y_puhelin		= $GLOBALS['eta_yhtio_puhelin'];
			$y_fax			= $GLOBALS['eta_yhtio_fax'];
			$y_email		= $GLOBALS['eta_yhtio_email'];
			$y_www			= $GLOBALS['eta_yhtio_email'];
			$y_nimi 		= $GLOBALS['eta_yhtio_nimi'];
			$y_osoite 		= $GLOBALS['eta_yhtio_osoite'];
			$y_postino	 	= $GLOBALS['eta_yhtio_postino'];
			$y_postitp	 	= $GLOBALS['eta_yhtio_postitp'];
			$y_maa 			= $GLOBALS['eta_yhtio_maa'];
			$y_kotipaikka	= $GLOBALS['eta_yhtio_kotipaikka'];
			$y_vatnumero	= str_replace("0037", "", $GLOBALS['eta_yhtio_ovttunnus']);
			$y_kotipaikka	= $GLOBALS['eta_yhtio_kotipaikka'];
			$y_iban 		= "";
			$y_swift 		= "";
		}

		if (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_custom.inc") {

			$yhteystiedot_len = $pdf->strlen("$y_nimi, $y_osoite, $y_postino $y_postitp, $y_maa", $pieni);
			$pdf->draw_text((595 / 2) - ($yhteystiedot_len / 2), 55, "$y_nimi, $y_osoite, $y_postino $y_postitp, $y_maa", $thispage, $pieni);

			$yhteystiedot_len = $pdf->strlen($y_www, $pieni);
			$pdf->draw_text((595 / 2) - ($yhteystiedot_len / 2), 42, $y_www, $thispage, $pieni);
		}
		else {
			//Alimmat kolme laatikkoa, yhtiötietoja
			$pdf->draw_rectangle(70, 20, 20, 580,	$thispage, $rectparam);
			$pdf->draw_rectangle(70, 207, 20, 580,	$thispage, $rectparam);
			$pdf->draw_rectangle(70, 394, 20, 580,	$thispage, $rectparam);

			$pdf->draw_text(30, 55, $y_nimi,		$thispage, $pieni);
			$pdf->draw_text(30, 45, $y_osoite,		$thispage, $pieni);
			$pdf->draw_text(30, 35, $y_postino."  ".$y_postitp,	$thispage, $pieni);
			$pdf->draw_text(30, 25, $y_maa,			$thispage, $pieni);

			$pdf->draw_text(217, 55, t("Puhelin", $kieli).":",			$thispage, $pieni);
			$pdf->draw_text(257, 55, $y_puhelin,						$thispage, $pieni);
			$pdf->draw_text(217, 45, t("Telefax", $kieli).":",			$thispage, $pieni);
			$pdf->draw_text(257, 45, $y_fax,							$thispage, $pieni);
			$pdf->draw_text(217, 35, t("Sähköposti", $kieli).":",		$thispage, $pieni);
			$pdf->draw_text(257, 35, $y_email,							$thispage, $pieni);
			$pdf->draw_text(217, 25, t("Web", $kieli).":",				$thispage, $pieni);
			$pdf->draw_text(257, 25, $y_www,							$thispage, $pieni);

			$pdf->draw_text(404, 55, t("Y-tunnus", $kieli).":",			$thispage, $pieni);
			$pdf->draw_text(444, 55, tulosta_ytunnus($y_vatnumero, $y_maa, $laskurow["vienti"]), $thispage, $pieni);
			$pdf->draw_text(404, 45, t("Kotipaikka", $kieli).":",		$thispage, $pieni);
			$pdf->draw_text(444, 45, $y_kotipaikka,						$thispage, $pieni);
			$pdf->draw_text(404, 35, t("Alv.rek", $kieli),				$thispage, $pieni);
			$pdf->draw_text(404, 25, $y_iban." / ".$y_swift,			$thispage, $pieni);
		}

		if ($tots == 1 and $summa != 0) {

			if (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_custom.inc") {

				$kala -= 10;
				$ed_sivu = $sivu;
				$thispage = alvierittely($thispage, '', $tyyppi);

				if ($sivu != $ed_sivu) {
					$yhteystiedot_len = $pdf->strlen("$y_nimi, $y_osoite, $y_postino $y_postitp, $y_maa", $pieni);
					$pdf->draw_text((595 / 2) - ($yhteystiedot_len / 2), 55, "$y_nimi, $y_osoite, $y_postino $y_postitp, $y_maa", $thispage, $pieni);

					$yhteystiedot_len = $pdf->strlen($y_www, $pieni);
					$pdf->draw_text((595 / 2) - ($yhteystiedot_len / 2), 42, $y_www, $thispage, $pieni);
				}

				if ($kala-90 <= 100) {
					$sivu++;

					loppu_lahete($thispage, 0, $tyyppi);

					$page[$sivu] = uusi_sivu_lahete($tyyppi, 'ei_otsikoita');
					$thispage    = $page[$sivu];

					$yhteystiedot_len = $pdf->strlen("$y_nimi, $y_osoite, $y_postino $y_postitp, $y_maa", $pieni);
					$pdf->draw_text((595 / 2) - ($yhteystiedot_len / 2), 55, "$y_nimi, $y_osoite, $y_postino $y_postitp, $y_maa", $thispage, $pieni);

					$yhteystiedot_len = $pdf->strlen($y_www, $pieni);
					$pdf->draw_text((595 / 2) - ($yhteystiedot_len / 2), 42, $y_www, $thispage, $pieni);

					$kala += 36;
				}

				$pdf->draw_text(50, $kala-25, t("Toimitustapa"), $thispage, $boldi);
				$pdf->draw_text(200, $kala-25, $laskurow['toimitustapa'], $thispage, $norm);

				$query  = "SELECT * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
				$masresult = mysql_query($query) or pupe_error($query);
				$masrow = mysql_fetch_array($masresult);

				$pdf->draw_text(50, $kala-38, t("Maksuehto", $kieli), $thispage, $boldi);
				$pdf->draw_text(200, $kala-38, t_tunnus_avainsanat($masrow, "teksti", "MAKSUEHTOKV", $kieli), $thispage, $norm);

				$pdf->draw_text(50, $kala-51, t("Myynti- ja toimitusehdot", $kieli), $thispage, $boldi);
				$pdf->draw_text(200, $kala-51, $yhtiorow['nimi'].t(":n yleiset kauppaehdot", $kieli), $thispage, $norm);

				$pdf->draw_text(50, $kala-66, t("Liitteet", $kieli), $thispage, $boldi);
				$pdf->draw_text(200, $kala-66, "________", $thispage, $norm);

				$pdf->draw_text(50, $kala-77, t("Vakuutus", $kieli), $thispage, $boldi);
				$pdf->draw_text(200, $kala-77, t("Tilaaja ottaa kustannuksellaan kohteeseen työaikaisen rakennustyövakuutuksen ennen", $kieli).' ', $thispage, $norm);
				$pdf->draw_text(200, $kala-90, t("töiden alkamista ja toimittaa vakuutuskirjan", $kieli)." {$yhtiorow['nimi']}".t(":lle", $kieli), $thispage, $norm);

				// Jos on paljon rivejä tehdään uusi otsikko...
				if ($kala-253 <= 300) {
					$sivu++;

					loppu_lahete($thispage, 0, $tyyppi);

					$page[$sivu] = uusi_sivu_lahete($tyyppi, 'ei_otsikoita');
					$thispage    = $page[$sivu];

					$yhteystiedot_len = $pdf->strlen("$y_nimi, $y_osoite, $y_postino $y_postitp, $y_maa", $pieni);
					$pdf->draw_text((595 / 2) - ($yhteystiedot_len / 2), 55, "$y_nimi, $y_osoite, $y_postino $y_postitp, $y_maa", $thispage, $pieni);

					$yhteystiedot_len = $pdf->strlen($y_www, $pieni);
					$pdf->draw_text((595 / 2) - ($yhteystiedot_len / 2), 42, $y_www, $thispage, $pieni);

					$kala += 110;
				}

				$pdf->draw_text(50, $kala-120, t("Sopimusasiakirjojen etusijajärjestys on seuraava", $kieli).':', $thispage, $pieni);
				$pdf->draw_text(50, $kala-130, '1. '.t("Kauppasopimus / tilaus", $kieli), $thispage, $pieni);
				$pdf->draw_text(50, $kala-140, "2. $yhtiorow[nimi]".t(":n yleiset toimitusehdot", $kieli)." 1.5.2010", $thispage, $pieni);
				$pdf->draw_text(50, $kala-150, "3. ".t("Rakennustuotteiden yleisiä hankinta- ja toimitusehtoja (RYHT 2000) ja betonielementtien asennusehdot", $kieli).'.', $thispage, $pieni);

				$pdf->draw_text(50, $kala-170, t("Olen tutustunut", $kieli)." $yhtiorow[nimi]".t(":n yleisiin sopimusehtoihin ja hyväksyn ne sellaisenaan").'.', $thispage, $pieni);

				$x[0] = 50;
				$x[1] = 230;
				$y[0] = $y[1] = $kala-210;
				$pdf->draw_line($x, $y, $thispage, $rectparam);

				//etsitään myyjän nimi
				$query  = "	SELECT nimi, kuka
							from kuka
							where tunnus='$laskurow[myyja]' and yhtio='$kukarow[yhtio]'";
				$myyresult = mysql_query($query) or pupe_error($query);
				$myyrow = mysql_fetch_assoc($myyresult);

				$pdf->draw_text(50, $kala-223, $myyrow['nimi'], $thispage, $norm);
				$pdf->draw_text(50, $kala-253, t("Myyjän allekirjoitus"), $thispage, $boldi);

				$x[0] = 330;
				$x[1] = 510;
				$y[0] = $y[1] = $kala-210;
				$pdf->draw_line($x, $y, $thispage, $rectparam);

				$x[0] = 330;
				$x[1] = 510;
				$y[0] = $y[1] = $kala-240;
				$pdf->draw_line($x, $y, $thispage, $rectparam);
				$pdf->draw_text(330, $kala-253, t("Ostajan allekirjoitus ja nimenselvennys"), $thispage, $boldi);
			}
			else {
				if ($laskurow['alv'] > 0 and $yhtiorow["alv_kasittely"] == '') {
					$pdf->draw_text(410, 72, t("Yhteensä verollinen", $kieli).":", $thispage, $norm);
				}
				else {
					$pdf->draw_text(410, 72, t("Yhteensä veroton", $kieli).":", $thispage, $norm);
				}

				$oikpos = $pdf->strlen(sprintf("%.2f",$summa)." ".$laskurow["valkoodi"], $norm);
				$pdf->draw_text(575-$oikpos, 72, sprintf("%.2f",$summa)." ".$laskurow["valkoodi"], $thispage, $norm);
			}
		}

		if ($tots != 1) {
			$pdf->draw_text(530, 72, t("Jatkuu", $kieli)."...", $thispage, $norm);
		}

		if ($tots == 1) {
			// viivat rivien väliin...
			$x[0] = 20;
			$x[1] = 580;
			$y[0] = $y[1] = $kala + $rivinkorkeus - 4;

			if (str_replace("perhe_", "", $lahetetyyppi) == "tulosta_lahete_custom.inc") {
				for ($pp=1; $pp<=$sivu; $pp++) {
					$pdf->draw_text(555, 803, "$pp / $sivu", $page[$pp], $pieni);
				}
			}
			else {

				$pdf->draw_line($x, $y, $thispage, $rectparam);

				for ($pp=1; $pp<=$sivu; $pp++) {
					$pdf->draw_text(330, 803, "$pp / $sivu", $page[$pp], $norm);
				}
			}
		}
	}
}

if (!function_exists('kuittaus_lahete')) {
	function kuittaus_lahete ($tyyppi="") {
		global $pdf, $laskurow, $viite, $p, $summa, $rectparam, $kieli, $yhtiorow;

		$sivu++;

		$page[$sivu] = alku_lahete($tyyppi);
		$thispage    = $page[$sivu];

		$pdf->draw_text(50, 400, t("Allekirjoitus", $kieli).": _____________________", 	$thispage, $norm);
		$pdf->draw_text(300, 400, t("Nimenselvennys", $kieli).": _____________________", 	$thispage, $norm);
	}
}

if (!function_exists('print_pdf_lahete')) {
	function print_pdf_lahete ($komento, $tyyppi="") {
		global $pdf, $kukarow, $yhtiorow, $kateinen, $returnvalue, $kieli, $tee, $laskurow;

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

		// itse print komento...
		if ($komento == 'email' or substr($komento,0,12) == 'asiakasemail') {
			$liite = $pdffilenimi;
			$content_body = "";

			if ($tyyppi == "TILAUSVAHVISTUS") {
				echo t("Tilausvahvistus tulostuu")."...<br>";
				$kutsu = t("Tilausvahvistus %s", $kieli, $laskurow["tunnus"]);
				$content_body = t("Kiitos tilauksestanne", $kieli).",\n\n";
				$content_body .= t("Tilausnumeronne on", $kieli)." $laskurow[tunnus]\n\n";
				$content_body .= t("Tämä on automaattinen vahvistus tilauksestanne, tähän sähköpostiin ei tarvitse vastata.", $kieli)."\n\n";
				$content_body .= t("Ystävällisin terveisin", $kieli).",\n";
				$content_body .= $yhtiorow["nimi"]."\n";
			}
			elseif ($tyyppi == "YLLAPITOSOPIMUS") {
				echo t("Ylläpitosopimus tulostuu")."...<br>";
				$kutsu = t("Ylläpitosopimus %s", $kieli, $laskurow["tilaus"]);
			}
			else {
				echo t("Lähete tulostuu")."...<br>";
				$kutsu = t("Lähete %s", $kieli, $laskurow["tilaus"]);
			}

			if (@include("../inc/sahkoposti.inc"));
			elseif (@include("inc/sahkoposti.inc"));
			else include("sahkoposti.inc");
		}
		elseif ($tee == 'NAYTATILAUS') {
			//Työnnetään tuo pdf vaan putkeen!
			echo file_get_contents($pdffilenimi);
		}
		elseif ($komento != '' and $komento != 'edi') {
			echo t("Lähete tulostuu")."...<br>";
			$line = exec("$komento $pdffilenimi", $output, $returnvalue);
		}

		unset($pdf);
		unset($GLOBALS['pdf']);

		//poistetaan tmp file samantien kuleksimasta...
		system("rm -f $pdffilenimi");
	}
}

if (!function_exists('alvierittely')) {
	function alvierittely ($thispage, $kassa_ale = '', $tyyppi = "") {
		global $yhtiorow, $kukarow, $toim, $pdf, $laskurow, $sivu, $page, $rectparam, $norm, $pieni, $summa, $arvo, $kala, $kieli, $boldi, $lahetetyyppi;

		if ($yhtiorow["viivakoodi_laskulle"] == "K" and $laskurow["maa"] == "FI" and $laskurow["summa"] > 0) {
			$barcode_korkeus = 50;
		}
		else {
			$barcode_korkeus = 0;
		}

		$sivu_vaihtui = false;

		if ($kala <= 150 + $barcode_korkeus) {
			$sivu++;
		
			if ($kassa_ale == '') {
				loppu_lahete($thispage, 0, $tyyppi);
			}
		
			$page[$sivu] = uusi_sivu_lahete($toim, 'ei_otsikoita');
			$thispage    = $page[$sivu];
		
			$sivu_vaihtui = true;
		}

		$kassa_ale_lisa = '';
		$kassa_ale_lisateksti = '';

		if ($kassa_ale != '') {
			$kassa_ale = (float) $kassa_ale;
			$kassa_ale_lisa = " * (1-$kassa_ale/100) ";

			$kala -=10;
			$pdf->draw_text(150, $kala, t("Kassa-alennuksella", $kieli), 		$thispage, $boldi);
		}

		//loppusummat vain vikalle sivulle
		$pdf->draw_text(150, $kala, t("Veroton arvo yhteensä", $kieli).":", 		$thispage, $boldi);

		if ($toim == 'PROFORMA') {
			$arvo = $kassa_ale != '' ? $arvo * (1-$kassa_ale/100) : $arvo;

			$oikpos = $pdf->strlen(sprintf('%.2f', $arvo), $boldi);
			$pdf->draw_text(535-$oikpos, $kala, sprintf('%.2f', $arvo), 			$thispage, $boldi);
		}
		elseif ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
			$arvo_valuutassa = $kassa_ale != '' ? $laskurow["arvo_valuutassa"] * (1-$kassa_ale/100) : $laskurow["arvo_valuutassa"];

			$oikpos = $pdf->strlen(sprintf('%.2f', $arvo_valuutassa), $boldi);
			$pdf->draw_text(535-$oikpos, $kala, sprintf('%.2f', $arvo_valuutassa), $thispage, $boldi);
		}
		else {

			if ($laskurow["arvo"] > 0) {
				$arvo = $kassa_ale != '' ? $laskurow["arvo"] * (1-$kassa_ale/100) : $laskurow["arvo"];
			}
			else {
				$arvo = $kassa_ale != '' ? $arvo * (1-$kassa_ale/100) : $arvo;
			}

			$oikpos = $pdf->strlen(sprintf('%.2f', $arvo), $norm);
			$pdf->draw_text(470-$oikpos, $kala, sprintf('%.2f', $arvo), $thispage, $boldi);
		}

		$pdf->draw_text(473, $kala, $laskurow["valkoodi"], $thispage, $boldi);

		if ($toim == 'PROFORMA' or $toim == 'TILAUSVAHVISTUS') {
			$where = " otunnus = '$laskurow[tunnus]' ";
		}
		else {
			$where = " uusiotunnus = '$laskurow[tunnus]' ";
		}

		//Haetaan kaikki alvikannat riveiltä
		$alvquery = "	SELECT distinct alv
						FROM tilausrivi
						WHERE $where
						and yhtio	= '$kukarow[yhtio]'
						and tyyppi	= 'L'
						ORDER BY alv";
		$alvresult = mysql_query($alvquery) or pupe_error($alvquery);
		$kala -= 10;

		while ($alvrow = mysql_fetch_assoc($alvresult)) {

			// Valuuttajutut kuntoon
			if ($alvrow["alv"] >= 500 and $toim != 'PROFORMA') {
				$aquery = "	SELECT
							sum(0) alvrivihinta,
							sum(round(tilausrivi.rivihinta $kassa_ale_lisa / if (lasku.vienti_kurssi > 0, lasku.vienti_kurssi, 1),2)) rivihinta
							FROM tilausrivi
							JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
							WHERE tilausrivi.otunnus = '$laskurow[tunnus]' and tilausrivi.yhtio='$kukarow[yhtio]' and tilausrivi.alv='$alvrow[alv]' and tilausrivi.tyyppi='L'";
			}
			elseif ($alvrow["alv"] >= 500) {
				$aquery = "	SELECT
							sum(0) alvrivihinta,
							sum(round((tilausrivi.hinta $kassa_ale_lisa / if (lasku.vienti_kurssi > 0, lasku.vienti_kurssi, 1)) * tilausrivi.varattu * (1 - tilausrivi.ale / 100),2)) rivihinta
							FROM tilausrivi
							JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
							WHERE tilausrivi.otunnus = '$laskurow[tunnus]' and tilausrivi.yhtio='$kukarow[yhtio]' and tilausrivi.alv='$alvrow[alv]' and tilausrivi.tyyppi='L'";
			}
			elseif ($alvrow["alv"] < 500 and $toim != 'PROFORMA' and trim(strtoupper($laskurow["valkoodi"])) == trim(strtoupper($yhtiorow["valkoodi"]))) {
				$aquery = "	SELECT
							round(sum(tilausrivi.hinta $kassa_ale_lisa / if ('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.kpl+tilausrivi.jt) * (1-tilausrivi.ale/100) $kassa_ale_lisa * (tilausrivi.alv / 100)),2) alvrivihinta,
							sum(round(tilausrivi.hinta $kassa_ale_lisa / if ('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.kpl+tilausrivi.jt) * (1-tilausrivi.ale/100),2)) rivihinta
							FROM tilausrivi
							JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
							WHERE tilausrivi.otunnus = '$laskurow[tunnus]' and tilausrivi.yhtio='$kukarow[yhtio]' and tilausrivi.alv='$alvrow[alv]' and tilausrivi.tyyppi='L'";
			}
			elseif ($alvrow["alv"] < 500 and $toim != 'PROFORMA' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
				$aquery = "	SELECT
							round(sum((tilausrivi.hinta $kassa_ale_lisa / if (lasku.vienti_kurssi > 0, lasku.vienti_kurssi, 1)) / if ('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * tilausrivi.kpl * (1-tilausrivi.ale/100) * (tilausrivi.alv/100)),2) alvrivihinta,
							sum(round((tilausrivi.hinta $kassa_ale_lisa / if (lasku.vienti_kurssi > 0, lasku.vienti_kurssi, 1)) / if ('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * tilausrivi.kpl * (1-tilausrivi.ale/100),2)) rivihinta
							FROM tilausrivi
							JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
							WHERE tilausrivi.otunnus = '$laskurow[tunnus]' and tilausrivi.yhtio='$kukarow[yhtio]' and tilausrivi.alv='$alvrow[alv]' and tilausrivi.tyyppi='L'";
			}
			else {
				$aquery = "	SELECT
							round(sum((tilausrivi.hinta $kassa_ale_lisa / if (lasku.vienti_kurssi > 0, lasku.vienti_kurssi, 1)) / if ('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * tilausrivi.varattu * if (tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+$laskurow[erikoisale]-(tilausrivi.ale*$laskurow[erikoisale]/100))/100)) * (tilausrivi.alv/100)),2) alvrivihinta,
							sum(round((tilausrivi.hinta $kassa_ale_lisa / if (lasku.vienti_kurssi > 0, lasku.vienti_kurssi, 1)) / if ('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * tilausrivi.varattu * if (tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+$laskurow[erikoisale]-(tilausrivi.ale*$laskurow[erikoisale]/100))/100)),2)) rivihinta
							FROM tilausrivi
							JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
							WHERE tilausrivi.otunnus = '$laskurow[tunnus]' and tilausrivi.yhtio='$kukarow[yhtio]' and tilausrivi.alv='$alvrow[alv]' and tilausrivi.tyyppi='L'";
			}
			$aresult = mysql_query($aquery) or pupe_error($aquery);
			$arow = mysql_fetch_assoc($aresult);

			$pdf->draw_text(150, $kala, t("Arvonlisävero", $kieli)." (".sprintf('%.2f',$arow["rivihinta"])."): ",	$thispage, $boldi);
			$oikpos = $pdf->strlen(sprintf('%.2f', $arow["alvrivihinta"]), $boldi);
			$pdf->draw_text(470-$oikpos, $kala, sprintf('%.2f', $arow["alvrivihinta"]), 		$thispage, $boldi);

			if ($alvrow["alv"] >= 500) {
				$pdf->draw_text(473, $kala, t("M.V.", $kieli), 									$thispage, $boldi);
			}
			else {
				$pdf->draw_text(473, $kala, ($alvrow["alv"]*1)."%", 							$thispage, $boldi);
			}

			$kala -= 10;
		}

		$pdf->draw_text(150, $kala, t("Lasku Yhteensä", $kieli).":",							$thispage, $boldi);

		if ($toim == 'PROFORMA') {
			$summa = $kassa_ale != '' ? $summa * (1-$kassa_ale/100) : $summa;

			$oikpos = $pdf->strlen(sprintf('%.2f', $summa), $boldi);
			$pdf->draw_text(535-$oikpos, $kala, sprintf('%.2f', $summa), 						$thispage, $boldi);
			$pdf->draw_text(550, $kala, $laskurow["valkoodi"], 									$thispage, $boldi);
		}
		elseif ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
			$summa_valuutassa = $kassa_ale != '' ? $laskurow["summa_valuutassa"] * (1-$kassa_ale/100) : $laskurow["summa_valuutassa"];

			$oikpos = $pdf->strlen(sprintf('%.2f', $summa_valuutassa), $boldi);
			$pdf->draw_text(535-$oikpos, $kala, sprintf('%.2f', $summa_valuutassa),				$thispage, $boldi);
			$pdf->draw_text(550, $kala, $laskurow["valkoodi"], 									$thispage, $boldi);

			if ($laskurow["pyoristys_valuutassa"] != 0) {

				$pyoristys_valuutassa = $kassa_ale != '' ? $laskurow["pyoristys_valuutassa"] * (1-$kassa_ale/100) : $laskurow["pyoristys_valuutassa"];

				$kala -= 10;
				$pdf->draw_text(360, $kala, t("Pyöristys", $kieli).":",							$thispage, $norm);
				$oikpos = $pdf->strlen(sprintf('%.2f', $pyoristys_valuutassa * -1), $norm);
				$pdf->draw_text(535-$oikpos, $kala, sprintf('%.2f', $pyoristys_valuutassa * -1),$thispage, $norm);
				$pdf->draw_text(550, $kala, $laskurow["valkoodi"], 								$thispage, $norm);
				$kala -= 10;

				$pdf->draw_text(360, $kala, t("Yhteensä", $kieli).":",							$thispage, $norm);
				$oikpos = $pdf->strlen(sprintf('%.2f', $summa_valuutassa - $pyoristys_valuutassa), $norm);
				$pdf->draw_text(535-$oikpos, $kala, sprintf('%.2f', $summa_valuutassa - $pyoristys_valuutassa), $thispage, $norm);
				$pdf->draw_text(550, $kala, $laskurow["valkoodi"], 												$thispage, $norm);
			}
		}
		else {
			if ($laskurow['summa'] > 0) {
				$summa = $kassa_ale != '' ? $laskurow["summa"] * (1-$kassa_ale/100) : $laskurow["summa"];
			}
			else {
				$summa = $kassa_ale != '' ? $summa * (1-$kassa_ale/100) : $summa;
			}

			$oikpos = $pdf->strlen(sprintf('%.2f', $summa), $boldi);
			$pdf->draw_text(470-$oikpos, $kala, sprintf('%.2f', $summa), 						$thispage, $boldi);
			$pdf->draw_text(473, $kala, $laskurow["valkoodi"], 									$thispage, $boldi);

			if ($laskurow["pyoristys"] != 0) {
				$pyoristys = $kassa_ale != '' ? $laskurow["pyoristys"] * (1-$kassa_ale/100) : $laskurow["pyoristys"];

				$kala -= 10;
				$pdf->draw_text(150, $kala, t("Pyöristys", $kieli).":",							$thispage, $boldi);
				$oikpos = $pdf->strlen(sprintf('%.2f', $pyoristys * -1), $boldi);
				$pdf->draw_text(470-$oikpos, $kala, sprintf('%.2f', $pyoristys * -1), 			$thispage, $boldi);
				$pdf->draw_text(473, $kala, $laskurow["valkoodi"], 								$thispage, $boldi);
				$kala -= 10;

				$pdf->draw_text(150, $kala, t("Yhteensä", $kieli).":",							$thispage, $boldi);
				$oikpos = $pdf->strlen(sprintf('%.2f', $summa - $pyoristys), $boldi);
				$pdf->draw_text(470-$oikpos, $kala, sprintf('%.2f', $summa - $pyoristys), 		$thispage, $boldi);
				$pdf->draw_text(473, $kala, $laskurow["valkoodi"], 								$thispage, $boldi);
			}
		}

		return $thispage;
	}
}

?>