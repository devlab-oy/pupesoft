<?php

//viivakoodien tulostamista varten
define (__TRACE_ENABLED__, false);
define (__DEBUG_ENABLED__, false);

require_once("barcode/barcode.php");
require_once("barcode/c39object.php");
require_once("pdflib/phppdflib.class.php");

$p["height"] 	= 10;
$p["font"] 		= "Times-Roman";

$rectparam["width"] = 0.3;
$sivu = 0;

//muutamat funktiot...
if (!function_exists('alku')) {
	function alku () {
		global $pdf, $image, $laskurow, $yhtiorow, $tid, $viite, $p, $kala, $lask, $rectparam, $kukarow, $kieli, $tilausnumeroita, $sivu;

		//oletukasia
		$kala = 638;
		$sivu++;

		//oletukset viivakoodeille
		$output   = "png";
		$width    = "140";
		$height   = "40";
		$xres     = "1";
		$font     = "2";
		$drawtext = "off";

		$style  = BCS_ALIGN_CENTER;
		$style |= ($output  == "png" ) ? BCS_IMAGE_PNG  : 0;
		$style |= ($output  == "jpeg") ? BCS_IMAGE_JPEG : 0;
		$style |= ($border  == "on"  ) ? BCS_BORDER 	  : 0;
		$style |= ($drawtext== "on"  ) ? BCS_DRAW_TEXT  : 0;
		$style |= ($stretchtext== "on" ) ? BCS_STRETCH_TEXT  : 0;
		$style |= ($negative== "on"  ) ? BCS_REVERSE_COLOR  : 0;

		$firstpage = $pdf->new_page("a4");
		$pdf->enable('template');
		$tid = $pdf->template->create();
		$pdf->template->size($tid, 600, 830);

		$pdf->draw_rectangle(825, 20,  780, 580, 		$firstpage, $rectparam);
		$pdf->draw_text(30, 815, $yhtiorow["nimi"], 	$firstpage, $p);
		$pdf->draw_text(30, 805, $yhtiorow["osoite"], 	$firstpage, $p);
		$pdf->draw_text(30, 795, $yhtiorow["postino"], 	$firstpage, $p);
		$pdf->draw_text(60, 795, $yhtiorow["postitp"], 	$firstpage, $p);
		$pdf->draw_text(30, 785, $yhtiorow["maa"], 		$firstpage, $p);
		$pdf->draw_text(250,810, t("Keräilylista", $kieli), 	$firstpage);
		$pdf->draw_text(260,785, t("Toimituspvm", $kieli).": ".tv1dateconv($laskurow["toimaika"]), 	$firstpage, $p);
		$pdf->draw_text(320,810, t("Sivu", $kieli)." $sivu", 	$firstpage,$p);
		$pdf->draw_text(135,795, t("Tilausnumero(t)", $kieli).": ".$tilausnumeroita, $firstpage, $p);
		$pdf->draw_text(135,785, t("Keräyspvm", $kieli).": ".tv1dateconv(substr($laskurow["kerayspvm"],0,10)), 	$firstpage, $p);


		$i["height"] = 28;
		$i["font"] = "Times-Roman";
		$i["color"]['red']   = 1;
		$i["color"]['blue']  = 0;
		$i["color"]['green'] = 0;

		$pdf->draw_text(400, 795, $laskurow["hyvaksynnanmuutos"], $firstpage, $i);


		$pdf->draw_rectangle(780, 20,  680, 200, 		$firstpage, $rectparam);
		$pdf->draw_text(30, 760, t("Toimitusosoite", $kieli).":", 		$firstpage, $p);
		$pdf->draw_text(30, 740, $laskurow["toim_nimi"], 	$firstpage, $p);
		$pdf->draw_text(30, 730, $laskurow["toim_nimitark"], $firstpage, $p);
		$pdf->draw_text(30, 720, $laskurow["toim_osoite"], 	$firstpage, $p);
		$pdf->draw_text(30, 710, $laskurow["toim_postino"], 	$firstpage, $p);
		$pdf->draw_text(60, 710, $laskurow["toim_postitp"], 	$firstpage, $p);
		$pdf->draw_text(30, 700, $laskurow["toim_maa"], 		$firstpage, $p);

		$pdf->draw_rectangle(780, 200, 680, 380, $firstpage, $rectparam);
		$pdf->draw_text(210, 760, t("Laskutusosoite", $kieli).":", $firstpage, $p);
		$pdf->draw_text(210, 740, $laskurow["nimi"], $firstpage, $p);
		$pdf->draw_text(210, 730, $laskurow["nimitark"], $firstpage, $p);
		$pdf->draw_text(210, 720, $laskurow["osoite"], $firstpage, $p);
		$pdf->draw_text(210, 710, $laskurow["postino"], $firstpage, $p);
		$pdf->draw_text(240, 710, $laskurow["postitp"], $firstpage, $p);
		$pdf->draw_text(210, 700, $laskurow["maa"], $firstpage, $p);

		// haetaan maksuehdon tiedot
		$query  = "select * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
		$masresult = mysql_query($query) or pupe_error($query);
		$masrow = mysql_fetch_array($masresult);

		//maksuehto tekstinä
		$maksuehto = $masrow["teksti"]." ".$masrow["kassa_teksti"];

		$pdf->draw_rectangle(780, 380, 680, 580, $firstpage, $rectparam);
		$pdf->draw_text(390, 760, t("Asiakasnumero", $kieli).": ".$laskurow["ytunnus"], $firstpage, $p);
		$pdf->draw_text(390, 750, t("Päivämäärä", $kieli).": ".tv1dateconv(substr($laskurow["lahetepvm"],0,10)) , $firstpage, $p);
		$pdf->draw_text(390, 740, t("Laatija", $kieli).": ".$laskurow["laatija"] . "@" . $laskurow["luontiaika"], $firstpage, $p);
		$pdf->draw_text(390, 720, t("Toimitustapa", $kieli).": ".$laskurow["toimitustapa"], $firstpage, $p);
		$pdf->draw_text(390, 710, t("Toimitusehto", $kieli).": ".$laskurow["toimitusehto"], $firstpage, $p);
		$pdf->draw_text(390, 700, t("Maksuehto", $kieli).": ".$maksuehto, $firstpage, $p);
		$pdf->draw_text(390, 690, t("Tilausviite", $kieli).": ".substr($laskurow["viesti"],0,18), $firstpage, $p);
		
		//viivakoodin arvo
		$barcode  = $laskurow["tunnus"];

		//luodaan viivakoodiolio
		$obj = "";
		$obj = new C39Object($width, $height, $style, $barcode);

		if ($obj) {
			$obj->SetFont($font);
			$obj->DrawObject($xres);

			//flushataan pdf ja saadaam filenimi johon se tallennettiin
			$nimi1 = $obj->FlushObject();

			//keksitään uudelle failille joku varmasti uniikki nimi:
			list($usec, $sec) = explode(' ', microtime());
			mt_srand((float) $sec + ((float) $usec * 100000));
			$nimi2 = "/tmp/".md5(uniqid(mt_rand(), true)).".png";

			passthru("/usr/bin/convert -colors 2 ".$nimi1." ".$nimi2, $palautus);
			
						$fh = fopen($nimi2, "r");
						$data = fread($fh, filesize($nimi2));
						fclose($fh);
						$image = $pdf->png_embed($data);
			
						if(!$image) {
							echo t("Viivakoodivirhe").": ".$image.$data;
							exit;
						}
						$placement = $pdf->image_place($image, 783, 420, $firstpage);
			
						unset($obj);
			
						//dellataan tmp filet kuleksimasta
						system("rm -f $nimi1 $nimi2");
		}
		else{
			echo t("Oliota ei voitu luoda").".";
			exit;
		}

		$kala = 650;

		$pdf->draw_rectangle(670, 20,  $kala, 36,  $firstpage, $rectparam);
		$pdf->draw_rectangle(670, 36,  $kala, 80,  $firstpage, $rectparam);
		$pdf->draw_rectangle(670, 80,  $kala, 180, $firstpage, $rectparam);
		$pdf->draw_rectangle(670, 180, $kala, 320, $firstpage, $rectparam);
		$pdf->draw_rectangle(670, 320, $kala, 420, $firstpage, $rectparam);
		$pdf->draw_rectangle(670, 420, $kala, 470, $firstpage, $rectparam);
		$pdf->draw_rectangle(670, 470, $kala, 520, $firstpage, $rectparam);
		$pdf->draw_rectangle(670, 520, $kala, 580, $firstpage, $rectparam);

		$pdf->draw_rectangle($kala, 20, 60, 580, $firstpage, $rectparam);

		$kala += 7;

		$pdf->draw_text(25,  $kala, "#",								$firstpage, $p);
		$pdf->draw_text(45,  $kala, t("Paikka", $kieli),				$firstpage, $p);
		$pdf->draw_text(90,  $kala, t("Tuotenumero", $kieli),			$firstpage, $p);
		$pdf->draw_text(190, $kala, t("Tuotenimi", $kieli),				$firstpage, $p);
		$pdf->draw_text(330, $kala, t("Tomittajan tuoteno", $kieli),	$firstpage, $p);
		$pdf->draw_text(425, $kala, t("Hyllyssä", $kieli),				$firstpage, $p);
		$pdf->draw_text(473, $kala, t("Tilattu", $kieli),				$firstpage, $p);
		$pdf->draw_text(530, $kala, t("Toimitus", $kieli),				$firstpage, $p);

		$kala -= 20;

		//tehdään viestejä ja lasketaan niitten pituuksia
		if ($laskurow['sisviesti2'] != '') {

			$pdf->draw_text(35, $kala, t("Huomautukset", $kieli).":", $firstpage, $p);
			$pohja = $pdf->draw_paragraph(638, 35, 60, 550, $laskurow["sisviesti2"]."\n", $firstpage, $p); // top, left, bottom, right

			$laskurow['sisviesti2'] = "";

			if (!is_numeric($pohja)) {
				$laskurow['sisviesti2'] = $pohja;
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
	function rivi($firstpage) {
		global $pdf, $row, $kala, $rivinkorkeus, $lask, $total, $rectparam, $kukarow, $yhtiorow, $rivinumerot, $kieli, $laskurow, $perheid;

		$p["height"]		= 10;
		$p["font"]			= "Times-Roman";
		$pp["height"]		= 8;
		$pp["font"]			= "Times-Roman";
		$boldi["height"] 	= 10;
		$boldi["font"] 		= "Times-Bold";

		$rivinkorkeus	= 15;

		// viivat rivien väliin...
		$x[0] = 20;
		$x[1] = 580;
		$y[0] = $y[1] = $kala + $rivinkorkeus - 4;

		if (($perheid == 0 or $perheid != $row["perheid"]) and isset($perheid)) {
			$pdf->draw_line($x, $y, $firstpage, $rectparam);
		}

		//jos ollaan liian pitkällä tehdään uusi otsikko...
		if ($kala < 75) {
			loppu($firstpage, 0);
			$firstpage = alku();
		}

		if ($row["var"] == "" or  $row["var"] == "H"
			or ($row["var"] == "P" and ($yhtiorow["puute_jt_kerataanko"] == "P" or $yhtiorow["puute_jt_kerataanko"] == "Q"))
			or ($row["var"] == "J" and ($yhtiorow["puute_jt_kerataanko"] == "J" or $yhtiorow["puute_jt_kerataanko"] == "Q"))) {

			$varastopaikka = $row["hyllyalue"] . " " . $row["hyllynro"] . " " . $row["hyllyvali"] . " " . $row["hyllytaso"];

			if ($row["perheid"] > 0 and $perheid != $row["perheid"]) {
				
				$query = "	SELECT vanhatunnus
							FROM lasku use index (PRIMARY)
							WHERE yhtio	= '$kukarow[yhtio]'
							and tunnus	= '$row[otunnus]'";
				$vtunres = mysql_query($query) or pupe_error($query);
				$vtunrow = mysql_fetch_array($vtunres);

				if ($vtunrow["vanhatunnus"] > 0) {
					$query = " 	SELECT GROUP_CONCAT(distinct tunnus SEPARATOR ',') tunnukset
								FROM lasku use index (yhtio_vanhatunnus)
								WHERE yhtio		= '$kukarow[yhtio]'
								and vanhatunnus	= '$vtunrow[vanhatunnus]'";
					$perheresult = mysql_query($query) or pupe_error($query);
					$perherow = mysql_fetch_array($perheresult);
				}
				
				if ($perherow["tunnukset"] != "") {
					$otunlisa = " and tilausrivi.otunnus in ($perherow[tunnukset]) ";
				}
				else {
					$otunlisa = " and tilausrivi.otunnus = '$row[otunnus]' ";
				}

				$query = "	SELECT tuoteno, nimitys
							FROM tilausrivi
							WHERE yhtio = '$kukarow[yhtio]'
							$otunlisa
							and perheid = '$row[perheid]'
							ORDER by tunnus";
				$perheresult = mysql_query($query) or pupe_error($query);

				$litania = '';
				while ($perherow = mysql_fetch_array($perheresult)) {
					if ($litania == "") {
							$litania .= $perherow["tuoteno"]." (".$row["varattu"].") ".$perherow["nimitys"].":  ";
					}
					else {
						$litania .= strtoupper($perherow["tuoteno"]).", ";
					}
				}
				$litania = substr($litania,0,-2);
				
				$pdf->draw_text(42,  $kala, "# ".t("Tuoteperhe", $kieli).": ".$litania,	$firstpage, $p);
				$kala = $kala - $rivinkorkeus;
			}

			$pdf->draw_text(23,  $kala, $rivinumerot[$row["tunnus"]],	$firstpage, $pp);
			$pdf->draw_text(38,  $kala, $varastopaikka,					$firstpage, $p);
			$pdf->draw_text(90,  $kala, $row["tuoteno"],				$firstpage, $p);


			$query = "	SELECT tuote.*, group_concat(distinct tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '/') toim_tuoteno
						FROM tuote
						LEFT JOIN tuotteen_toimittajat ON tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno
						WHERE tuote.yhtio='$kukarow[yhtio]' and tuote.tuoteno = '$row[tuoteno]'
						GROUP BY tuote.tuoteno";
			$tresult = mysql_query($query) or pupe_error($query);
			$trow	 = mysql_fetch_array($tresult);

			$query = "	SELECT *
						FROM tuotepaikat
						WHERE yhtio   = '$kukarow[yhtio]'
						and tuoteno   = '$row[tuoteno]'";
			$vresult = mysql_query($query) or pupe_error($query);

			//katotaan mihin varastooon tilausrivillä tuotepaikka kuuluu
			$rivipaikka = kuuluukovarastoon($row["hyllyalue"], $row["hyllynro"]);

			$rivipaikkahyllyssa = 0;
			$rivivarastohyllyssa = 0;

			while ($vrow = mysql_fetch_array($vresult)) {
				if ($vrow['hyllyalue'] == $row['hyllyalue'] and $vrow['hyllynro'] == $row['hyllynro'] and $vrow['hyllyvali'] == $row['hyllyvali'] and $vrow['hyllytaso'] == $row['hyllytaso']){
					$query = "	SELECT sum(varattu) keratty
								FROM tilausrivi	use index (yhtio_tyyppi_tuoteno_varattu)
								WHERE yhtio = '$kukarow[yhtio]'
								and tyyppi 			   in ('L','G','V')
								and tuoteno   			= '$row[tuoteno]'
								and varattu 		   != 0
								and keratty 		   != ''
								and laskutettu 			= ''
								and hyllyalue 			= '$vrow[hyllyalue]'
								and hyllynro  			= '$vrow[hyllynro]'
								and hyllyvali 			= '$vrow[hyllyvali]'
								and hyllytaso 			= '$vrow[hyllytaso]'";
					$vvresult = mysql_query($query) or pupe_error($query);
					$vvrow	 = mysql_fetch_array($vvresult);
					$rivipaikkahyllyssa += $vrow['saldo'] - $vvrow['keratty'];
					$rivivarastohyllyssa += $vrow['saldo'] - $vvrow['keratty'];
				}
				elseif (kuuluukovarastoon($vrow["hyllyalue"], $vrow["hyllynro"]) == $rivipaikka) {
					$query = "	SELECT sum(varattu) keratty
								FROM tilausrivi	use index (yhtio_tyyppi_tuoteno_varattu)
								WHERE yhtio = '$kukarow[yhtio]'
								and tyyppi 			   in ('L','G','V')
								and tuoteno   			= '$row[tuoteno]'
								and varattu 		   != 0
								and keratty 		   != ''
								and laskutettu 			= ''
								and hyllyalue 			= '$vrow[hyllyalue]'
								and hyllynro  			= '$vrow[hyllynro]'
								and hyllyvali 			= '$vrow[hyllyvali]'
								and hyllytaso 			= '$vrow[hyllytaso]'";
					$vvresult = mysql_query($query) or pupe_error($query);
					$vvrow	 = mysql_fetch_array($vvresult);
					$rivivarastohyllyssa += $vrow['saldo'] - $vvrow['keratty'];
				}
			}


			$pdf->draw_text(330, $kala, $trow["toim_tuoteno"], 	$firstpage, $p);
			if ($rivipaikkahyllyssa != $rivivarastohyllyssa) {
				$pdf->draw_text(430, $kala, $rivipaikkahyllyssa." (".$rivivarastohyllyssa.")",	$firstpage, $p);
			}
			else {
				$pdf->draw_text(430, $kala, $rivipaikkahyllyssa,		 						$firstpage, $p);
			}

			$pdf->draw_text(480, $kala, ($row["tilkpl"]*1)." ".strtolower($row["yksikko"]), 	$firstpage, $boldi);

			if ($row["keratty"] != '' and !in_array($row["var"], array('J','P'))) {
				$pdf->draw_text(530, $kala, $row['varattu'], 	$firstpage, $p);
			}
			else {
				if ($row["jt"] != 0) { // jälkitoimitus
					$pdf->draw_text(530, $kala, t("**JT**", $kieli), 	$firstpage, $p);
				}
				elseif ($row["var"] == 'P') { // puute
					$pdf->draw_text(530, $kala, t("PUUTE", $kieli), 	$firstpage, $p);
				}
				elseif ($row["var"] == 'H') { // väkisin hyväksytty
					$pdf->draw_text(530, $kala, "...........", 	$firstpage, $p);
				}
			}

			if (strlen(asana('nimitys_',$row['tuoteno'],$row['nimitys'])) > 22) {
				$kala = $kala - $rivinkorkeus;
			}
			$pdf->draw_text(190, $kala, asana('nimitys_',$row['tuoteno'],$row['nimitys']), 	$firstpage, $p);

			$kala = $kala - $rivinkorkeus;

			if (trim($row["kommentti"]) != '' or $row["tilaajanrivinro"] != 0) {

				if ($row["tilaajanrivinro"] != 0) {
					$futur = t("Rivinumero", $kieli).": ".$row["tilaajanrivinro"].". ";
				}
				else {
					$futur = "";
				}
				
				if($pdf->strlen("* $futur".$row["kommentti"], $p)>485) {
					$pdf->draw_paragraph($kala+$p["height"], 90, $kala-$p["height"]-2, 580, "* $futur".$row["kommentti"],	$firstpage, $p);			
					$kala-=$p["height"]+2;
				}
				else {
					$pdf->draw_text(90,  $kala, "* $futur".$row["kommentti"],	$firstpage, $p);
				}				

				$kala = $kala - $rivinkorkeus;
			}

			$perheid = $row["perheid"];
		}
		return($firstpage);
	}
}

if (!function_exists('loppu')) {
	function loppu ($firstpage, $tots) {
		global $pdf, $laskurow, $viite, $p, $total, $rectparam, $kieli;

		$pdf->draw_rectangle(50, 20, 20, 580, $firstpage, $rectparam);

		$pdf->draw_text(30, 37,  t("Tilausviite", $kieli).": ".$laskurow["viesti"], $firstpage, $p);
		$pdf->draw_text(30, 25,  t("Kommentti", $kieli).":   ".$laskurow["comments"], $firstpage, $p);

		if ($tots != 1) {
			$pdf->draw_text(530, 23, t("Jatkuu", $kieli)."...", $firstpage, $p);
		}
	}
}

if (!function_exists('print_pdf')) {
	function print_pdf ($komento) {
		global $pdf, $kukarow, $yhtiorow, $kateinen, $returnvalue, $kieli, $tee;

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
		if ($komento == 'email') {
			$liite = $pdffilenimi;
			$kutsu = "Lahete";
			echo t("Keräyslista tulostuu")."...<br>";

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
			echo t("Keräyslista tulostuu")."...<br>";
			$line = exec("$komento $pdffilenimi", $output, $returnvalue);
		}

		//poistetaan tmp file samantien kuleksimasta...
		system("rm -f $pdffilenimi");
	}
}

?>