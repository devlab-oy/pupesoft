<?php

// tarvitaan tilauksen tunnus muutujassa $tunnus
// printterin komento muuttujassa $oslapp
// $kukarow[yhtio] jostain saadaan yhtio
// $yhtiorow array josta saada lähettäjän tiedot

if ($toim == 'EXTRANET_REKLAMAATIO' and $tunnus == '') {
	$tunnus = $kukarow['kesken'];
}

if ($phpnimi != "rahtikirja_custom.php") {
	//Tulostetaan standardi kolliosoitelappu
	$query = "	SELECT lasku.*, if(yhteyshenkilo.nimi!='',concat_ws(' / ', yhteyshenkilo.nimi,if(yhteyshenkilo.gsm!='',yhteyshenkilo.gsm,if(yhteyshenkilo.puh!='',yhteyshenkilo.puh,NULL))),'') yhteyshenkilo
				FROM lasku
				LEFT JOIN laskun_lisatiedot ON lasku.yhtio=laskun_lisatiedot.yhtio and laskun_lisatiedot.otunnus=lasku.tunnus
				LEFT JOIN yhteyshenkilo ON yhteyshenkilo.yhtio=laskun_lisatiedot.yhtio and yhteyshenkilo.tunnus=laskun_lisatiedot.yhteyshenkilo_tekninen and yhteyshenkilo.tyyppi = 'A'
				WHERE lasku.yhtio = '$kukarow[yhtio]'
				and lasku.tunnus = '$tunnus'";
	$tempr = pupe_query($query);
	$laskurow = mysql_fetch_array($tempr);

	// Käytetäänkö VAK-tietokantaa
	if ($yhtiorow["vak_kasittely"] != "") {
		$vakselect = "concat_ws(' ', concat('UN',yk_nro), nimi_ja_kuvaus, lipukkeet, pakkausryhma)";
		$vakjoin   = "JOIN vak ON tuote.yhtio = vak.yhtio and tuote.vakkoodi = vak.tunnus";
	}
	else {
		$vakselect = "tuote.vakkoodi";
		$vakjoin   = "";
	}

	// Haetaan kaikki vakkoodit arrayseen
	$query = "	SELECT group_concat(distinct $vakselect SEPARATOR ', ') vakkoodi
				FROM tilausrivi
				JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno and tuote.vakkoodi not in ('','0'))
				$vakjoin
				where tilausrivi.otunnus = '$laskurow[tunnus]'
				and tilausrivi.yhtio = '$kukarow[yhtio]'
				and tilausrivi.var in ('','H')
				and tilausrivi.tyyppi in ('L','G')
				ORDER BY tuote.vakkoodi";
	$vres = pupe_query($query);

	if (mysql_num_rows($vres) > 0) {
		$vak = mysql_fetch_assoc($vres);
		$vakit = $vak["vakkoodi"];
	}

	$query = "	SELECT *
				from asiakas
				where yhtio = '$kukarow[yhtio]'
				and tunnus  = '$laskurow[liitostunnus]'";
	$tempr = pupe_query($query);
	$asiakasrow = mysql_fetch_array($tempr);
}
else {
	$laskurow = $osoitelappurow;
}

if ($toim == 'EXTRANET_REKLAMAATIO' and $yhtiorow['reklamaation_vastaanottovarasto'] != 0) {
	if ($yhtiorow['reklamaation_vastaanottovarasto'] !='') {
		$laskurow['varasto'] = $yhtiorow['reklamaation_vastaanottovarasto'];
	}
}

// haetaan varaston osoitetiedot, käytetään niitä lähetystietoina
$query = "	SELECT nimi, nimitark, osoite, postino, postitp, maa
			FROM varastopaikat
			WHERE yhtio = '$kukarow[yhtio]'
			AND tunnus = '$laskurow[varasto]'";
$tempr = pupe_query($query);
$postirow_varasto = mysql_fetch_array($tempr);

// jos varastolle on annettu joku osoite, käytetään sitä
if ($postirow_varasto["nimi"] != "") {
	$laskurow["yhtio_nimi"]     = $postirow_varasto["nimi"];
	$laskurow['yhtio_nimitark']	= $postirow_varasto["nimitark"];
	$laskurow["yhtio_osoite"]   = $postirow_varasto["osoite"];
	$laskurow["yhtio_postino"]  = $postirow_varasto["postino"];
	$laskurow["yhtio_postitp"]  = $postirow_varasto["postitp"];
	$laskurow["yhtio_maa"]      = $postirow_varasto["maa"];
}

if ($toim == 'EXTRANET_REKLAMAATIO' and $yhtiorow['reklamaation_vastaanottovarasto'] == 0) {
	$query = "	SELECT nimi, osoite, postino, postitp, maa
				FROM yhtio
				WHERE yhtio = '$yhtiorow[yhtio]'";
	$tempr = pupe_query($query);
	$postirow_varasto = mysql_fetch_array($tempr);
}


// haetaan printterin osoitetiedot
$query = "	SELECT *
			FROM kirjoittimet
			WHERE yhtio = '{$kukarow['yhtio']}'
			AND TRIM(komento) LIKE CONCAT(TRIM('{$oslapp}'),'%')
			AND nimi != ''";
$pres  = pupe_query($query);
$print = mysql_fetch_assoc($pres);

// jos meillä on printterin takana joku spessu osoitetieto niin käytetään sen tietoja lähettäjän tietoina
if ($print["nimi"] != "") {
	$laskurow["yhtio_nimi"]    = $print["nimi"];
	$laskurow["yhtio_osoite"]  = $print["osoite"];
	$laskurow["yhtio_postino"] = $print["postino"];
	$laskurow["yhtio_postitp"] = $print["postitp"];
}

//PDF:n luonti ja defaultit
require_once("pdflib/phppdflib.class.php");

// jos php-gd on installoitu niin loidataab barcode library
if (in_array("gd", get_loaded_extensions())) {
	if (@include_once("viivakoodi/Barcode.php"));
	else include_once("Barcode.php");
}

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

for ($tulostuskpl = 1; $tulostuskpl <= $oslappkpl; $tulostuskpl++) {

	// tehdään SSCC :
	// (00)
	// 1
	// ytunnus (8)
	// lähetenro (6)
	// kollityht (2)
	// tarkiste (1)
	$sscc = 1;
	$sscc .= sprintf("%08.08s",$yhtiorow["ytunnus"]);
	$sscc .= sprintf('%06.06d', substr($laskurow["tunnus"], -6));
	$sscc .= sprintf('%02.02d', $tulostuskpl);
	$loppu = tarkiste($sscc);
	$sscc = $sscc.$loppu;

	// tehdään pdf:n uusi sivu
	$firstpage = $pdf->new_page("a5");

	if (class_exists("Image_Barcode")) {
		//luodaan viivakoodiolio kuljetusohjeelle = postino
		$nimi = "/tmp/".md5(uniqid(rand(),true)).".jpg";

		if ($toim == 'EXTRANET_REKLAMAATIO') {
			imagejpeg(Image_Barcode::draw($postirow_varasto['postino'], 'code128', 'jpg', false, 3, 170), $nimi);
		}
		else {
			imagejpeg(Image_Barcode::draw($laskurow['toim_postino'], 'code128', 'jpg', false, 3, 170), $nimi);
		}

		$fh = fopen($nimi, "r");
		$data = fread($fh, filesize($nimi));
		fclose($fh);

		$image = $pdf->jfif_embed($data);

		$logoparam['scale'] = 90/282;
		$pdf->image_place($image, mm_pt(80), mm_pt(5), $firstpage, $logoparam);
		system("rm -f $nimi");

		$nimi = "/tmp/".md5(uniqid(rand(),true)).".jpg";

		imagejpeg(Image_Barcode::draw($sscc, 'code128', 'jpg', false, 3, 170), $nimi);

		$fh = fopen($nimi, "r");
		$data = fread($fh, filesize($nimi));
		fclose($fh);

		$image = $pdf->jfif_embed($data);

		$logoparam['scale'] = 90/282;
		$pdf->image_place($image, mm_pt(9), mm_pt(5), $firstpage, $logoparam);
		system("rm -f $nimi");
	}

	if ($toim == 'EXTRANET_REKLAMAATIO') {
		$pdf->draw_text(mm_pt(5), mm_pt(75), "Postinro - Postnr - Postal Code: $postirow_varasto[postino]",	$firstpage, $otsik);
	}
	else {
		$pdf->draw_text(mm_pt(5), mm_pt(75), "Postinro - Postnr - Postal Code: $laskurow[toim_postino]",	$firstpage, $otsik);
	}

	//tehää tästä vielä kaunis kattella
	$sscc = substr($sscc,0,4)." ".substr($sscc,4,1)." ".substr($sscc,5,8)." ".substr($sscc,13,8)." ".substr($sscc,21,1);

	$pdf->draw_text(mm_pt(5), mm_pt(5), "Kolli - Item ID.: $sscc", $firstpage, $otsik);

	$apu_yhtiorow = array();

	// varmistetaan, että kopiossakin tulee oikean toimipaikan logo
	if ((int) $laskurow["yhtio_toimipaikka"] != 0) {
		$query = "	SELECT *
					FROM yhtion_toimipaikat
					WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$laskurow[yhtio_toimipaikka]' and lasku_logo != ''";
		$result = mysql_query($query) or die ("Kysely ei onnistu yhtio $query");

		if (mysql_num_rows($result) == 1) {
			$yhtion_toimipaikkarow = mysql_fetch_array($result);
			$apu_yhtiorow["lasku_logo"] = $yhtion_toimipaikkarow["lasku_logo"];
	 	}
	}
	else {
		$query = "	SELECT *
					FROM yhtion_parametrit
					WHERE yhtio = '$laskurow[yhtio]'";
		$result = mysql_query($query) or die ("Kysely ei onnistu yhtio $query");

		if (mysql_num_rows($result) == 1) {
			$yhtion_parametritrow = mysql_fetch_array($result);
			$apu_yhtiorow["lasku_logo"] = $yhtion_parametritrow["lasku_logo"];
		}
	}

	unset($data);
	if( (int) $apu_yhtiorow["lasku_logo"] > 0) {
		$liite = hae_liite($apu_yhtiorow["lasku_logo"], "Yllapito", "array");
		$data = $liite["data"];
		$isizelogo[0] = $liite["image_width"];
		$isizelogo[1] = $liite["image_height"];
		unset($liite);
	}
	elseif(file_exists($apu_yhtiorow["lasku_logo"])) {
		$filename = $apu_yhtiorow["lasku_logo"];

		$fh = fopen($filename, "r");
		$data = fread($fh, filesize($filename));
		fclose($fh);

		$isizelogo = getimagesize($apu_yhtiorow["lasku_logo"]);
	}

	if(isset($data) !== FALSE and $yhtiorow["oslapp_rakir_logo"] == '') {
		$image = $pdf->jfif_embed($data);

		if(!$image) {
			echo t("Logokuvavirhe");
		}
		else {

			$logoparam = array();

			if ($isizelogo[0] > $isizelogo[1] and $isizelogo[1] * (200 / $isizelogo[0]) <= 40) {
				$logoparam['scale'] = 200 / $isizelogo[0];
			}
			else {
				$logoparam['scale'] = 40  / $isizelogo[1];
			}

			$placement = $pdf->image_place($image, 580-($logoparam['scale']*$isizelogo[1]), 410-($logoparam['scale']*$isizelogo[0]), $firstpage, $logoparam);
		}
	}

	//Mistä
	if ($toim == 'EXTRANET_REKLAMAATIO') {

		$pdf->draw_text(mm_pt(5), mm_pt(200), "Mistä - Från - From: ",							$firstpage, $otsik);
		$pdf->draw_text(mm_pt(5), mm_pt(195), $laskurow['nimi'],							$firstpage, $kirj);
		$pdf->draw_text(mm_pt(5), mm_pt(190), $laskurow['nimitark'],						$firstpage, $kirj);
		$pdf->draw_text(mm_pt(5), mm_pt(185), $laskurow['osoite'],						$firstpage, $kirj);
		$pdf->draw_text(mm_pt(5), mm_pt(180), $laskurow['postino']." ".strtoupper($laskurow['postitp']),	$firstpage, $kirj);
	}
	else {
		$pdf->draw_text(mm_pt(5), mm_pt(200), "Mistä - Från - From: ",							$firstpage, $otsik);
		$pdf->draw_text(mm_pt(5), mm_pt(195), $laskurow['yhtio_nimi'],							$firstpage, $kirj);
		$pdf->draw_text(mm_pt(5), mm_pt(190), $laskurow['yhtio_nimitark'],						$firstpage, $kirj);
		$pdf->draw_text(mm_pt(5), mm_pt(185), $laskurow['yhtio_osoite'],						$firstpage, $kirj);
		$pdf->draw_text(mm_pt(5), mm_pt(180), $laskurow['yhtio_postino']." ".strtoupper($laskurow['yhtio_postitp']),	$firstpage, $kirj);
	}

	//kolliluku
	if ($rahtikirja_tulostus == "yep" and $toimitustaparow['osoitelappu'] == 'tiivistetty') {
		$pdf->draw_text(mm_pt(70), mm_pt(180), t("Kollimäärä").": ".$s." (".$kollityht.") ",								$firstpage, $kirj);
	}
	elseif ($toimitustaparow['osoitelappu'] == 'tiivistetty') {
		$query = "	SELECT sum(kollit) kollit
					FROM rahtikirjat
					WHERE yhtio='$kukarow[yhtio]' and otsikkonro = '$laskurow[tunnus]'";
		$pakka = pupe_query($query);
		$pak = mysql_fetch_array($pakka);

		if ((int) $pak["kollit"] > 0) {
			$pdf->draw_text(mm_pt(70), mm_pt(180), t("Kollimäärä").": ".$pak["kollit"],								$firstpage, $kirj);
		}
	}

	$pdf->draw_text(mm_pt(5), mm_pt(175), "Puh -Tel - Tel: ",								$firstpage, $otsik);
	if ($toim == 'EXTRANET_REKLAMAATIO') {
		$pdf->draw_text(mm_pt(5), mm_pt(170), $asiakasrow['puhelin'],								$firstpage, $kirj);
		$pdf->draw_text(mm_pt(70), mm_pt(175), "Läh.pvm. - Avs.dat. -  Desp.Date: ",			$firstpage, $otsik);
	}
	else {
		$pdf->draw_text(mm_pt(5), mm_pt(170), $yhtiorow['puhelin'],								$firstpage, $kirj);
		$pdf->draw_text(mm_pt(70), mm_pt(175), "Läh.pvm. - Avs.dat. -  Desp.Date: ",			$firstpage, $otsik);
		$pdf->draw_text(mm_pt(70), mm_pt(170), date("d.m.Y") ,									$firstpage, $kirj);

	}

	$pdf->draw_text(mm_pt(5), mm_pt(165), "Lähettäjä - Avsändare - Sender: ",				$firstpage, $otsik);
	$pdf->draw_text(mm_pt(5), mm_pt(160), "$kukarow[nimi]" ,								$firstpage, $kirj);

	$pdf->draw_text(mm_pt(70), mm_pt(165), "Viite - Referens - Reference: ",				$firstpage, $otsik);

	if ($rahtikirja_tulostus == "yep") {
		$pdf->draw_text(mm_pt(70), mm_pt(160), $viestirarrow["viesti"] ,					$firstpage, $kirj);
	}
	else {
		$pdf->draw_text(mm_pt(70), mm_pt(160), "$laskurow[viesti]" ,							$firstpage, $kirj);
	}

	$pdf->draw_text(mm_pt(5), mm_pt(155), "Tilaukset - Order - Orders: ",					$firstpage, $otsik);
	if ($tilausnumeroita == "") $tilausnumeroita = $laskurow["tunnus"];

	if ($rahtikirja_tulostus == "yep") {
		$pdf->draw_text(mm_pt(50), mm_pt(155), $kaikki_lotsikot, $firstpage, $kirj);
	}
	else {
		$pdf->draw_text(mm_pt(50), mm_pt(155), $tilausnumeroita, $firstpage, $kirj);
	}


	//Minne
	$pdf->draw_rectangle(mm_pt(150),mm_pt(5),mm_pt(150),mm_pt(10),							$firstpage, $rectparam);
	$pdf->draw_rectangle(mm_pt(150),mm_pt(5),mm_pt(145),mm_pt(5),							$firstpage, $rectparam);

	$pdf->draw_rectangle(mm_pt(150),mm_pt(138),mm_pt(150),mm_pt(143),						$firstpage, $rectparam);
	$pdf->draw_rectangle(mm_pt(150),mm_pt(143),mm_pt(145),mm_pt(143),						$firstpage, $rectparam);

	if ($tiedot == "toimitusta") {
		$laskurow["toim_nimi"] 			= $toimitustaparow["toim_nimi"];
		$laskurow["toim_nimitark"] 		= $toimitustaparow["toim_nimitark"];
		$laskurow["toim_osoite"] 		= $toimitustaparow["toim_osoite"];
		$laskurow["toim_maa"] 			= $toimitustaparow["toim_maa"];
		$laskurow["toim_postino"] 		= $toimitustaparow["toim_postino"];
		$laskurow["toim_postitp"] 		= $toimitustaparow["toim_postitp"];
	}

	$pdf->draw_text(mm_pt(15),	mm_pt(145), "Minne - Till - To: " ,							$firstpage, $otsik);

	if ($toim == 'EXTRANET_REKLAMAATIO') {
		$pdf->draw_text(mm_pt(15),	mm_pt(140), $postirow_varasto['nimi'],					$firstpage, $iso);
		$pdf->draw_text(mm_pt(15),	mm_pt(133), $postirow_varasto['nimitark'],				$firstpage, $iso);
		$pdf->draw_text(mm_pt(15),	mm_pt(126), $postirow_varasto['osoite'],				$firstpage, $iso);
		$pdf->draw_text(mm_pt(15),	mm_pt(119), $postirow_varasto['maa']."-".$postirow_varasto['postino']." ".$postirow_varasto['postitp'],	$firstpage, $huge);
	}
	else {
		$pdf->draw_text(mm_pt(15),	mm_pt(140), $laskurow['toim_nimi'],						$firstpage, $iso);
		$pdf->draw_text(mm_pt(15),	mm_pt(133), $laskurow['toim_nimitark'],					$firstpage, $iso);
		$pdf->draw_text(mm_pt(15),	mm_pt(126), $laskurow['toim_osoite'],					$firstpage, $iso);
		$pdf->draw_text(mm_pt(15),	mm_pt(119), $laskurow['toim_maa']."-".$laskurow['toim_postino']." ".$laskurow['toim_postitp'],	$firstpage, $huge);
	}

	$pdf->draw_text(mm_pt(15),	mm_pt(112),  "Puh -Tel - Tel: ",		$firstpage, $otsik);

	if ($toim == 'EXTRANET_REKLAMAATIO') {
		$pdf->draw_text(mm_pt(38),	mm_pt(112), substr($yhtiorow['puhelin'],0,15),		$firstpage, $kirj);
		$pdf->draw_text(mm_pt(75), mm_pt(112), "Toimitustapa:",							$firstpage, $otsik);
	}
	else {
		$pdf->draw_text(mm_pt(38),	mm_pt(112), substr($asiakasrow['puhelin'],0,15),		$firstpage, $kirj);
		$pdf->draw_text(mm_pt(75), mm_pt(112), "Toimitustapa:",				$firstpage, $otsik);
		$pdf->draw_text(mm_pt(97), mm_pt(112), pdf_substr($laskurow["toimitustapa"], mm_pt(65), $pdf, $kirj),	$firstpage, $kirj);
	}

	$pdf->draw_rectangle(mm_pt(110),mm_pt(5),mm_pt(110),mm_pt(10),		$firstpage, $rectparam);
	$pdf->draw_rectangle(mm_pt(110),mm_pt(5),mm_pt(115),mm_pt(5),		$firstpage, $rectparam);

	$pdf->draw_rectangle(mm_pt(110),mm_pt(138),mm_pt(110),mm_pt(143),	$firstpage, $rectparam);
	$pdf->draw_rectangle(mm_pt(110),mm_pt(143),mm_pt(115),mm_pt(143),	$firstpage, $rectparam);

	// Kuljetusohjeet = viivakoodi tulee tähän
	$pdf->draw_text(mm_pt(5), mm_pt(70), "Kuljetusohjeet - Transportinstruktioner - Transport Instructions:",	$firstpage, $otsik);

	// Onko tilaus jälkivaatimus
	$apuqu = "SELECT * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
	$meapu = pupe_query($apuqu);
	$meapurow = mysql_fetch_array($meapu);

	if ($meapurow["jv"] != "") {
		$jvlisays =  " - ".t("Jälkivaatimus");
	}
	else {
		$jvlisays =  "";
	}

	if ($toim != 'EXTRANET_REKLAMAATIO') {
		$pdf->draw_text(mm_pt(5), mm_pt(65), $laskurow["toimitustapa"].$jvlisays,		$firstpage, $kirj);
	}

	$salakalapala = 65;

	if ($laskurow["viesti"] != "" or $laskurow['yhteyshenkilo'] != "" or $viestirarrow["viesti"] != "") {
		if ($rahtikirja_tulostus == "yep") {
			$salakalapala -= 5;
			$pdf->draw_text(mm_pt(5), mm_pt($salakalapala), trim($laskurow['yhteyshenkilo']." ".$viestirarrow["viesti"]),	$firstpage, $kirj);
		}
		else {
			$salakalapala -= 5;
			$pdf->draw_text(mm_pt(5), mm_pt($salakalapala), trim($laskurow['yhteyshenkilo']." ".$laskurow["viesti"]),		$firstpage, $kirj);
		}
	}

	if (isset($vakit) and $vakit != "") {
		$salakalapala -= 5;
		list($ff_string, $ff_font) = pdf_fontfit("VAK: $vakit", 400, $pdf, $otsik);
		$pdf->draw_text(mm_pt(5), mm_pt($salakalapala), $ff_string, $firstpage, $ff_font);
	}

	//kolliluku
	if ($rahtikirja_tulostus == "yep") {
		$salakalapala -= 5;
		$pdf->draw_text(mm_pt(5), mm_pt($salakalapala), t("Kollimäärä").": ".$s." (".$kollityht.") ", $firstpage, $kirj);
	}
	else {
		$query = "	SELECT sum(kollit) kollit
					FROM rahtikirjat
					WHERE yhtio='$kukarow[yhtio]' and otsikkonro = '$laskurow[tunnus]'";
		$pakka = pupe_query($query);
		$pak = mysql_fetch_array($pakka);

		if ((int) $pak["kollit"] > 0) {
			$salakalapala -= 5;
			$pdf->draw_text(mm_pt(5), mm_pt($salakalapala), t("Kollimäärä").": ".$pak["kollit"], $firstpage, $kirj);
		}
	}

	//tänne ohjeisiin tulee lopullisen asiakkaan tiedot jos kyseessä on koontikuljetus
	$query = "	SELECT tulostustapa
				from toimitustapa
				where yhtio = '$kukarow[yhtio]'
				and selite  = '$laskurow[toimitustapa]'";
	$hetir = pupe_query($query);
	$hetirow = mysql_fetch_array($hetir);

	if ($hetirow['tulostustapa'] == 'K') {
		$salakalapala -= 5;
		$pdf->draw_paragraph(mm_pt($salakalapala), mm_pt(5), mm_pt(25), mm_pt(140), $laskurow['sisviesti1'], $firstpage, $kirj);
	}

	// ylhäällä on "oikee" tapa tehdä, mutta me ei tiedetä tässä vaiheessa kollien määrää eikä painoa
	$pdf->draw_text(mm_pt(5), mm_pt(30), "Tilaukset - Order - Orders: ",	$firstpage, $otsik);
	if ($tilausnumeroita == "") $tilausnumeroita = $laskurow["tunnus"];

	if ($rahtikirja_tulostus == "yep") {
		$pdf->draw_text(mm_pt(50), mm_pt(30), $kaikki_lotsikot, $firstpage, $kirj);
	}
	else {
		$pdf->draw_text(mm_pt(50), mm_pt(30), $tilausnumeroita, $firstpage, $kirj);
	}
}

//keksitään uudelle failille joku varmasti uniikki nimi:
$pdffilenimi = "/tmp/kolliosoitelappu-".md5(uniqid(rand(),true)).".pdf";

//kirjoitetaan pdf faili levylle..
$fh = fopen($pdffilenimi, "w");
if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus epäonnistui $pdffilenimi");
fclose($fh);

//testaukseen voi myös laittaa sähköpostiin
//$liite = $pdffilenimi;
//$aihe = "Kolliosoitelappu";
//require("inc/sahkoposti.inc");

//itse print komento...
// itse print komento...

if ($oslapp == 'email') {
	$liite = $pdffilenimi;
	$kutsu = "Osoitelappu";

	if ($toim == 'EXTRANET_REKLAMAATIO') {
		include_once("generoi_laskun_saate.inc");
		list($komento, $content_subject, $content_body) = generoi_laskun_saate($laskurow, $saatekirje, $kieli,$toim);
		$content_subject .= " ".$laskurow['tunnus'];
		require("sahkoposti.inc");
	}
	else {
		require("inc/sahkoposti.inc");
	}
}
elseif ($tee == 'NAYTATILAUS') {
	//Työnnetään tuo pdf vaan putkeen!
	echo file_get_contents($pdffilenimi);
}
elseif ($oslapp != '' and $oslapp != 'edi') {
	$line = exec("$oslapp $pdffilenimi");
	echo t("Osoitelappu tulostuu")."...<br>";
}

//poistetaan tmp file samantien kuleksimasta...
system("rm -f $pdffilenimi");
$pdffilenimi='';
$tilausnumeroita="";
