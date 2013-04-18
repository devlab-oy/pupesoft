<?php

if ($kollityht > 0) {

	if ($toitarow['logy_rahtikirjanumerot'] != "" and strpos($_SERVER['SCRIPT_NAME'], "rahtikirja-kopio.php") === FALSE and (!isset($muutos) or $muutos != 'yes')) {

		$logyq = "	SELECT min(rahtikirjanro) rahtikirjanro
					FROM rahtikirjanumero
					WHERE yhtio = '$kukarow[yhtio]'
					and kaytettyaika = '0000-00-00 00:00:00'";
		$logyres = pupe_query($logyq);
		$logyrow = mysql_fetch_assoc($logyres);

		$rahtikirjanro = $logyrow["rahtikirjanro"];

		$logyq = "	UPDATE rahtikirjanumero
					SET kayttaja = '$kukarow[kuka]',
					kaytettyaika = now()
					WHERE yhtio = '$kukarow[yhtio]'
					and rahtikirjanro = '$logyrow[rahtikirjanro]'";
		$logyres = pupe_query($logyq);
	}
	elseif (strpos($_SERVER['SCRIPT_NAME'], "rahtikirja-kopio.php") === FALSE and (!isset($muutos) or $muutos != 'yes')) {
		$rahtikirjanro = $lotsikot[0];
	}
	else {
		$rahtikirjanro = $rivi["rahtikirjanro"];
	}

	if ($phpnimi == "rahtikirja_custom.php") {
		$laskurow = $osoitelappurow;
		$rakir_row = $osoitelappurow;
	}
	else {
		//Tulostetaan standardi kolliosoitelappu
		$query = "	SELECT *
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]'
					and tunnus in ($otunnukset)
					ORDER BY tunnus
					LIMIT 1";
		$tempr = mysql_query($query) or pupe_error($query);
		$laskurow = mysql_fetch_array($tempr);

		// haetaan varaston osoitetiedot, käytetään niitä lähetystietoina
		$query = "	SELECT nimi, nimitark, osoite, postino, postitp, maa
					FROM varastopaikat
					WHERE yhtio = '$kukarow[yhtio]'
					AND tunnus = '$laskurow[varasto]'";
		$tempr = mysql_query($query) or pupe_error($query);
		$postirow_varasto = mysql_fetch_array($tempr);

		// jos varastolle on annettu joku osoite, käytetään sitä
		if ($postirow_varasto["nimi"] != "") {
			$laskurow["yhtio_nimi"]     = $postirow_varasto["nimi"];
			$laskurow['yhtio_nimitark']	= $postirow_varasto["nimitark"];
			$laskurow["yhtio_osoite"]   = $postirow_varasto["osoite"];
			$laskurow["yhtio_postino"]  = $postirow_varasto["postino"];
			$laskurow["yhtio_postitp"]  = $postirow_varasto["postitp"];
			$laskurow["yhtio_maa"]     	= $postirow_varasto["maa"];
		}

		// jos meillä on printterin takana joku spessu osoitetieto niin käytetään sen tietoja lähettäjän tietoina
		if ($print["nimi"] != "") {
			$laskurow["yhtio_nimi"]    = $print["nimi"];
			$laskurow["yhtio_osoite"]  = $print["osoite"];
			$laskurow["yhtio_postino"] = $print["postino"];
			$laskurow["yhtio_postitp"] = $print["postitp"];
		}
	}

	if (!function_exists('tarkiste')) {

		function tarkiste($sscc) {

			$kerroin = 3; // kerroin aluks 3
			$summa   = 0; // summa nolla tietty

			// loopataan luvut oikeelta vasemmalle
			for ($i = 16; $i >= 0; $i--) {
				$summa += $kerroin * (ord($sscc{$i})-48); // lisätään summaan ko. luku * kerroin (tää hanskaa kirjaimet )
				$kerroin = 4 - $kerroin; // kerroin on vuorotellen 3 tai 1
			}

			$sscc = ceil($summa / 10) * 10 - $summa; // tarkiste on luku mikä pitää lisätä, että päästään seuraavaan tasakymmeneen

			return $sscc;
		}
	}

	$apu_kollityht = $kollityht;
	if ($kollityht > 100) $apu_kollityht = 99; // varmuuden vuoks, kun ei saa olla ku kaks merkkiä

	//PDF:n luonti ja defaultit
	define (__TRACE_ENABLED__, false);
	define (__DEBUG_ENABLED__, false);

	// jos php-gd on installoitu niin loidataab barcode library
	if (in_array("gd", get_loaded_extensions())) {
		require_once("barcode/barcode.php");
		require_once("barcode/debug.php");
		require_once("barcode/c39object.php");
		require_once("barcode/c128aobject.php");
	}

	require_once("pdflib/phppdflib.class.php");

	//PDF parametrit
	$pdf = new pdffile;
	$pdf->set_default('margin-top', 	0);
	$pdf->set_default('margin-bottom', 	0);
	$pdf->set_default('margin-left', 	0);
	$pdf->set_default('margin-right', 	0);
	$rectparam["width"] = 0.3;

	//fontit
	$otsik["height"] = 10;
	$otsik["font"] = "Helvetica";

	$kirj["height"] = 12;
	$kirj["font"] = "Helvetica-Bold";

	$iso["height"] = 16;
	$iso["font"] = "Helvetica-Bold";

	$huge["height"] = 20;
	$huge["font"] = "Helvetica-Bold";

	$hugein["height"] = 28;
	$hugein["font"] = "Helvetica-Bold";

	//$tulostakolli = 1; // tulostetaan aina yksi kappale???miks???

	$tulostakolli = $kollityht;

	for ($tulostuskpl=1; $tulostuskpl<=$tulostakolli; $tulostuskpl++) {

		// tehdään SSCC :
		// (00)
		// 1
		// ytunnus (8)
		// lähetenro (6)
		// kollityht (2)
		// tarkiste (1)

		//$prefix = "(00)"; // tää on turha
		$sscc = 1;
		$sscc .= sprintf("%08.8s",$yhtiorow["ytunnus"]);
		$sscc .= sprintf('%06.6s', substr($laskurow["tunnus"], -6));
		$sscc .= sprintf('%02.2s', $tulostuskpl);
		$loppu = tarkiste($sscc);
		$sscc = $sscc.$loppu;

		// tehdään pdf:n uusi sivu
		$firstpage = $pdf->new_page("a5");

		//DPD-LOGOGO
		if (file_exists("pics/dpd_logo.jpg")) {

			$filename = "pics/dpd_logo.jpg";

			$fh = fopen($filename, "r");
			$data = fread($fh, filesize($filename));
			fclose($fh);

			$image = $pdf->jfif_embed($data);

			if(!$image) {
				echo t("Logokuvavirhe");
			}
			else {
				$kala = array();
				$kala['scale'] = 0.20;
				$pdf->image_place($image, mm_pt(180), mm_pt(100), $firstpage,$kala);
			}
		}

		//Duusataan viivakoodia
		$output      = "png";
		$width       = "220";
		$height      = "50";
		$xres        = "1";
		$font        = "2";
		$drawtext    = "off";
		$border      = "on";
		$stretchtext = "off";
		$style    = BCS_ALIGN_CENTER;
		$style   |= ($output     == "png" ) ? BCS_IMAGE_PNG      : 0;
		$style   |= ($output     == "jpeg") ? BCS_IMAGE_JPEG     : 0;
		$style   |= ($border     == "on"  ) ? BCS_BORDER         : 0;
		$style   |= ($drawtext   == "on"  ) ? BCS_DRAW_TEXT      : 0;
		$style   |= ($stretchtext== "on"  ) ? BCS_STRETCH_TEXT   : 0;
		$style   |= ($negative   == "on"  ) ? BCS_REVERSE_COLOR  : 0;

		if (class_exists("c39object")) {

			//luodaan viivakoodiolio kuljetusohjeelle = postino
			$obj = "";
			$obj = new c39object($width, $height, $style, $laskurow['toim_postino']);

			if ($obj) {
				$obj->SetFont($font);
				$obj->DrawObject($xres);

				//flushataan barcode ja saadaam filenimi johon se tallennettiin
				$nimi1 = $obj->FlushObject();

				//keksitään uudelle failille joku varmasti uniikki nimi:
				$nimi2 = "/tmp/".md5(uniqid(rand(),true)).".png";

				passthru("/usr/bin/convert -colors 2 ".$nimi1." ".$nimi2, $palautus);

				if ($fh = @fopen($nimi2, "r")) {
					$data = fread($fh, filesize($nimi2));
					fclose($fh);
					$image = $pdf->png_embed($data);

					if ($image) {
						// piirretään viivakoodi paprulle
						$pdf->image_place($image, mm_pt(80), mm_pt(30), $firstpage); // Y, X
					}
				}

				$pdf->draw_text(mm_pt(4), mm_pt(75), "Postinro - Postnr - Postal Code: $laskurow[toim_postino]",	$firstpage, $otsik);
				unset($obj);

				//dellataan tmp filet kuleksimasta
				system("rm -f $nimi1 $nimi2");
			}
			else {
				echo "object create error";
			}

			//Duusataan SSCC
			$output      = "png";
			$width       = "500";
			$height      = "50";
			$xres        = "1";
			$font        = "2";
			$drawtext    = "off";
			$border      = "on";
			$stretchtext = "off";
			$style    = BCS_ALIGN_CENTER;
			$style   |= ($output     == "png" ) ? BCS_IMAGE_PNG      : 0;
			$style   |= ($output     == "jpeg") ? BCS_IMAGE_JPEG     : 0;
			$style   |= ($border     == "on"  ) ? BCS_BORDER         : 0;
			$style   |= ($drawtext   == "on"  ) ? BCS_DRAW_TEXT      : 0;
			$style   |= ($stretchtext== "on"  ) ? BCS_STRETCH_TEXT   : 0;
			$style   |= ($negative   == "on"  ) ? BCS_REVERSE_COLOR  : 0;

			//luodaan viivakoodiolio kuljetusohjeelle = postino
			$obj = "";
			$obj = new c128aobject($width, $height, $style, $sscc);

			if ($obj) {
				$obj->SetFont($font);
				$obj->DrawObject($xres);

				//flushataan barcode ja saadaam filenimi johon se tallennettiin
				$nimi1 = $obj->FlushObject();

				//keksitään uudelle failille joku varmasti uniikki nimi:
				$nimi2 = "/tmp/".md5(uniqid(rand(),true)).".png";

				passthru("/usr/bin/convert -colors 2 ".$nimi1." ".$nimi2, $palautus);

				if ($fh = @fopen($nimi2, "r")) {
					$data = fread($fh, filesize($nimi2));
					fclose($fh);
					$image = $pdf->png_embed($data);

					if ($image) {
						// piirretään viivakoodi paprulle
						$pdf->image_place($image, mm_pt(10), mm_pt(-15), $firstpage); // Y, X
					}
				}

				//tehää tästä vielä kaunis kattella
				$sscc = substr($sscc,0,4)." ".substr($sscc,4,1)." ".substr($sscc,5,8)." ".substr($sscc,13,8)." ".substr($sscc,21,1);

				$pdf->draw_text(mm_pt(4), mm_pt(5), "Kolli - Item ID.: $sscc", $firstpage, $otsik);
				unset($obj);

				//dellataan tmp filet kuleksimasta
				system("rm -f $nimi1 $nimi2");
			}
			else {
				echo "object create error 2";
			}

		}

		// sitten aletaan piirtämään itse PDF sisältöä
		// ensin isot viivat kohilleen
		$pdf->draw_rectangle(585,5,5,415,														$firstpage, $rectparam);

		//Mistä
		$pdf->draw_text(mm_pt(4), mm_pt(200), "Mistä - Från - From: ",							$firstpage, $otsik);
		$pdf->draw_text(mm_pt(4), mm_pt(195), $laskurow['yhtio_nimi'],							$firstpage, $kirj);
		$pdf->draw_text(mm_pt(4), mm_pt(190), $laskurow['yhtio_nimitark'],						$firstpage, $kirj);
		$pdf->draw_text(mm_pt(4), mm_pt(185), $laskurow['yhtio_osoite'],						$firstpage, $kirj);
		$pdf->draw_text(mm_pt(4), mm_pt(180), $laskurow['yhtio_postino']." ".strtoupper($laskurow['yhtio_postitp']),	$firstpage, $kirj);

		$pdf->draw_text(mm_pt(4), mm_pt(175), "Puh -Tel - Tel: ",								$firstpage, $otsik);
		$pdf->draw_text(mm_pt(4), mm_pt(170), $yhtiorow['puhelin'],								$firstpage, $kirj);

		$pdf->draw_text(mm_pt(70), mm_pt(175), "Läh.pvm. - Avs.dat. -  Desp.Date: ",			$firstpage, $otsik);
		$pdf->draw_text(mm_pt(70), mm_pt(170), date("d.m.Y") ,									$firstpage, $kirj);

		$pdf->draw_text(mm_pt(4), mm_pt(165), "Lähettäjä - Avsändare - Sender: ",				$firstpage, $otsik);
		$pdf->draw_text(mm_pt(4), mm_pt(160), "$kukarow[nimi]" ,								$firstpage, $kirj);

		$pdf->draw_text(mm_pt(70), mm_pt(165), "Viite - Referens - Reference: ",				$firstpage, $otsik);
		$pdf->draw_text(mm_pt(70), mm_pt(160), "$laskurow[viesti]" ,							$firstpage, $kirj);

		$pdf->draw_text(mm_pt(4), mm_pt(155), "Tilaukset - Order - Orders: ",					$firstpage, $otsik);
		if ($tilausnumeroita == "") $tilausnumeroita = $laskurow["tunnus"];
		$pdf->draw_text(mm_pt(50), mm_pt(155), $tilausnumeroita, $firstpage, $kirj);

		//Minne
		$pdf->draw_rectangle(mm_pt(150),mm_pt(5),mm_pt(150),mm_pt(10),							$firstpage, $rectparam);
		$pdf->draw_rectangle(mm_pt(150),mm_pt(5),mm_pt(145),mm_pt(5),							$firstpage, $rectparam);

		$pdf->draw_rectangle(mm_pt(150),mm_pt(138),mm_pt(150),mm_pt(143),						$firstpage, $rectparam);
		$pdf->draw_rectangle(mm_pt(150),mm_pt(143),mm_pt(145),mm_pt(143),						$firstpage, $rectparam);

		$pdf->draw_text(mm_pt(15),	mm_pt(145), "Minne - Till - To: " ,							$firstpage, $otsik);
		$pdf->draw_text(mm_pt(15),	mm_pt(140), $laskurow['toim_nimi'],							$firstpage, $kirj);
		$pdf->draw_text(mm_pt(15),	mm_pt(135), $laskurow['toim_nimitark'],						$firstpage, $kirj);
		$pdf->draw_text(mm_pt(15),	mm_pt(130), $laskurow['toim_osoite'],						$firstpage, $kirj);
		$pdf->draw_text(mm_pt(15),	mm_pt(123), $laskurow['toim_postino']." ".$laskurow['toim_postitp'],	$firstpage, $iso);

		$query = "	SELECT *
					FROM maat
					WHERE koodi = '$laskurow[toim_maa]'";
		$tempr = mysql_query($query) or pupe_error($query);
		$maarow = mysql_fetch_array($tempr);

		$pdf->draw_text(mm_pt(15),	mm_pt(116), substr($maarow['nimi'],5),								$firstpage, $iso);

		$query = "	SELECT *
					FROM asiakas
					WHERE yhtio = '$kukarow[yhtio]'
					AND tunnus  = '$laskurow[liitostunnus]'";
		$tempr = mysql_query($query) or pupe_error($query);
		$asiakasrow = mysql_fetch_array($tempr);

		$pdf->draw_text(mm_pt(15),	mm_pt(112),  "Puh -Tel - Tel: ",		$firstpage, $otsik);
		$pdf->draw_text(mm_pt(38),	mm_pt(112), $asiakasrow['puhelin'],		$firstpage, $kirj);

		$pdf->draw_text(mm_pt(65), mm_pt(112), "Toimitustapa:",				$firstpage, $otsik);
		$pdf->draw_text(mm_pt(87), mm_pt(112), $laskurow["toimitustapa"],	$firstpage, $kirj);


		$pdf->draw_rectangle(mm_pt(110),mm_pt(5),mm_pt(110),mm_pt(10),		$firstpage, $rectparam);
		$pdf->draw_rectangle(mm_pt(110),mm_pt(5),mm_pt(115),mm_pt(5),		$firstpage, $rectparam);

		$pdf->draw_rectangle(mm_pt(110),mm_pt(138),mm_pt(110),mm_pt(143),	$firstpage, $rectparam);
		$pdf->draw_rectangle(mm_pt(110),mm_pt(143),mm_pt(115),mm_pt(143),	$firstpage, $rectparam);

		//Kuljetusohjeet = viivakoodi tulee tähän
		$pdf->draw_text(mm_pt(4), mm_pt(70), "Kuljetusohjeet - Transportinstruktioner - Transport Instructions:",	$firstpage, $otsik);
		$pdf->draw_text(mm_pt(20), mm_pt(61), $laskurow["toimitustapa"],											$firstpage, $hugein);
		$pdf->draw_text(mm_pt(20), mm_pt(56), "Paketteja - Paket - Packages: ".$kollityht,							$firstpage, $kirj);

		$komm = "";

		if ($rakir_row['tulostustapa'] == 'K') $komm .= $laskurow['sisviesti1']."\n";

		$komm .= $laskurow['viesti']."\n";

		if (is_array($vakit)) {
			$komm .= "\n";

			foreach($vakit as $vak) {
				$komm .= "$vak.\n";
			}
		}

		if ($kuljetusohjeet != "") {
			$komm .= $kuljetusohjeet;
		}

		$pdf->draw_paragraph(mm_pt(55), mm_pt(20), mm_pt(35), mm_pt(140), $komm, $firstpage, $kirj);

		// ylhäällä on "oikee" tapa tehdä, mutta me ei tiedetä tässä vaiheessa kollien määrää eikä painoa
		$pdf->draw_text(mm_pt(4), mm_pt(30), "Tilaukset - Order - Orders: ",	$firstpage, $otsik);
		if ($tilausnumeroita == "") $tilausnumeroita = $laskurow["tunnus"];
		$pdf->draw_text(mm_pt(50), mm_pt(30), $tilausnumeroita, $firstpage, $kirj);
	}

	//keksitään uudelle failille joku varmasti uniikki nimi:
	$pdffilenimi = "/tmp/dpdosoitelappu-".md5(uniqid(rand(),true)).".pdf";

	//kirjoitetaan pdf faili levylle..
	$fh = fopen($pdffilenimi, "w");
	if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus epäonnistui $pdffilenimi");
	fclose($fh);

	if ($kirjoitin == "email") {
		$liite = $pdffilenimi;
		$kutsu = "PDF-rahtikirja";
		require("inc/sahkoposti.inc");
	}
	else {
		//itse print komento...
		$line = exec("$kirjoitin $pdffilenimi");
	}

	//poistetaan tmp file samantien kuleksimasta...
	system("rm -f $pdffilenimi");
	$pdffilenimi='';
}

?>
