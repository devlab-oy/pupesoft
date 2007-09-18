<?php

// tarvitaan tilauksen tunnus muutujassa $tunnus
// printterin komento muuttujassa $oslapp
// $kukarow[yhtio] jostain saadaan yhtio
// $yhtiorow array josta saada lähettäjän tiedot


//Tulostetaan standardi kolliosoitelappu
$query = "	SELECT lasku.*, if(yhteyshenkilo.nimi!='',concat_ws(' / ', yhteyshenkilo.nimi,if(yhteyshenkilo.gsm!='',yhteyshenkilo.gsm,if(yhteyshenkilo.puh!='',yhteyshenkilo.puh,NULL))),'') yhteyshenkilo
			FROM lasku
			LEFT JOIN laskun_lisatiedot ON lasku.yhtio=laskun_lisatiedot.yhtio and laskun_lisatiedot.otunnus=lasku.tunnus
			LEFT JOIN yhteyshenkilo ON yhteyshenkilo.yhtio=laskun_lisatiedot.yhtio and yhteyshenkilo.tunnus=laskun_lisatiedot.yhteyshenkilo_tekninen
			WHERE lasku.yhtio='$kukarow[yhtio]' and lasku.tunnus='$tunnus'";
$tempr = mysql_query($query) or pupe_error($query);
$laskurow = mysql_fetch_array($tempr);

$prikomento = explode('-#', $oslapp);

// haetaan printterin osoitetiedot
$query = "select * from kirjoittimet where yhtio='$kukarow[yhtio]' and trim(komento) like concat(trim('$prikomento[0]'),'%') and nimi!=''";
$pres  = mysql_query($query) or pupe_error($query);

if (mysql_num_rows($pres) > 0) {
	$print = mysql_fetch_array($pres);

	$yhtiorow["nimi"]    = $print["nimi"];
	$yhtiorow["osoite"]  = $print["osoite"];
	$yhtiorow["postino"] = $print["postino"];
	$yhtiorow["postitp"] = $print["postitp"];
	$yhtiorow["puhelin"] = $print["puhelin"];
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

// tehdään SSCC :
// (00) 
// 1 
// ytunnus (8)
// lähetenro (6)
// kollityht (2)
// tarkiste (1)

$apu_kollityht = $kollityht;
if ($kollityht > 100) $apu_kollityht = 99; // varmuuden vuoks, kun ei saa olla ku kaks merkkiä

//$prefix = "(00)"; // tää on turha
$sscc = 1;
$sscc .= sprintf("%08.08s",$yhtiorow["ytunnus"]);
$sscc .= sprintf('%06.06d', substr($laskurow["tunnus"], -6));
$sscc .= sprintf('%02.02d', $apu_kollityht);
$loppu = tarkiste($sscc);
$sscc = $sscc.$loppu;

//PDF:n luonti ja defaultit
define (__TRACE_ENABLED__, false);
define (__DEBUG_ENABLED__, false);
require_once("barcode/barcode.php");
require_once("barcode/debug.php");
require_once("barcode/c39object.php");
require_once("barcode/c128aobject.php");
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

$tulostakolli = 1; // tulostetaan aina yksi kappale???

for ($tulostuskpl=1; $tulostuskpl<=$tulostakolli; $tulostuskpl++) {

	// tehdään pdf:n uusi sivu
	$firstpage = $pdf->new_page("420x590");

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

		$fh = fopen($nimi2, "r");
		$data = fread($fh, filesize($nimi2));
		fclose($fh);
		$image = $pdf->png_embed($data);

		if(!$image) {
			echo "image create error";
		}
		// piirretään viivakoodi paprulle
		$pdf->image_place($image, mm_pt(80), mm_pt(30), $firstpage); // Y, X
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

		$fh = fopen($nimi2, "r");
		$data = fread($fh, filesize($nimi2));
		fclose($fh);
		$image = $pdf->png_embed($data);

		if(!$image) {
			echo "image create error 2";
		}

		//tehää tästä vielä kaunis kattella
		$sscc = substr($sscc,0,4)." ".substr($sscc,4,1)." ".substr($sscc,5,8)." ".substr($sscc,13,8)." ".substr($sscc,21,1);

		// piirretään viivakoodi paprulle
		$pdf->image_place($image, mm_pt(10), mm_pt(-15), $firstpage); // Y, X
		$pdf->draw_text(mm_pt(4), mm_pt(5), "Kolli - Item ID.: $sscc", $firstpage, $otsik);
		unset($obj);

		//dellataan tmp filet kuleksimasta
		system("rm -f $nimi1 $nimi2");
	}
	else {
		echo "object create error 2";
	}

	// sitten aletaan piirtämään itse PDF sisältöä
	// ensin isot viivat kohilleen
	$pdf->draw_rectangle(585,5,5,415, $firstpage, $rectparam);

	if ($yhtiorow["oslapp_rakir_logo"] != '' and trim($yhtiorow["lasku_logo"]) != '' and file_exists($yhtiorow["lasku_logo"])) {
		
		$filename = $yhtiorow["lasku_logo"];

		$fh = fopen($filename, "r");
		$data = fread($fh, filesize($filename));
		fclose($fh);

		$image = $pdf->jfif_embed($data);

		if(!$image) {
			echo t("Logokuvavirhe");
		}
		else {
			$isizelogo = getimagesize($yhtiorow["lasku_logo"]);
			
			$logoparam = array();
							
			// Logosta tulee max: 50pt korkea ja 120pt leveä (17.64mm x 42.33mm)
			if ($isizelogo[0] > $isizelogo[1] and $isizelogo[1] * (120 / $isizelogo[0]) <= 50) {
				$logoparam['scale'] = 120 / $isizelogo[0];
			}
			else {
				$logoparam['scale'] = 50  / $isizelogo[1];
			}
							
			$placement = $pdf->image_place($image, 580-($logoparam['scale']*$isizelogo[1]), 410-($logoparam['scale']*$isizelogo[0]), $firstpage, $logoparam);
		}
	}
	
	//Mistä
	$pdf->draw_text(mm_pt(4), mm_pt(200), "Mistä - Från - From: ",							$firstpage, $otsik);
	$pdf->draw_text(mm_pt(4), mm_pt(195), $yhtiorow['nimi'],								$firstpage, $kirj);
	$pdf->draw_text(mm_pt(4), mm_pt(190), $yhtiorow['nimitark'],							$firstpage, $kirj);
	$pdf->draw_text(mm_pt(4), mm_pt(185), $yhtiorow['osoite'],								$firstpage, $kirj);
	$pdf->draw_text(mm_pt(4), mm_pt(180), $yhtiorow['postino']." ".strtoupper($yhtiorow['postitp']),	$firstpage, $kirj);

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
	$pdf->draw_text(mm_pt(15),	mm_pt(140), $laskurow['toim_nimi'],							$firstpage, $iso);
	$pdf->draw_text(mm_pt(15),	mm_pt(133), $laskurow['toim_nimitark'],						$firstpage, $iso);
	$pdf->draw_text(mm_pt(15),	mm_pt(126), $laskurow['toim_osoite'],						$firstpage, $iso);
	$pdf->draw_text(mm_pt(15),	mm_pt(119), $laskurow['toim_maa']."-".$laskurow['toim_postino']." ".$laskurow['toim_postitp'],	$firstpage, $huge);
	
	$query = "	select *
				from asiakas
				where yhtio = '$kukarow[yhtio]'
				and tunnus  = '$laskurow[liitostunnus]'";
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

	// Kuljetusohjeet = viivakoodi tulee tähän
	$pdf->draw_text(mm_pt(4), mm_pt(60), "Kuljetusohjeet - Transportinstruktioner - Transport Instructions:",	$firstpage, $otsik);
	
	// Onko tilaus jälkivaatimus
	$apuqu = "select * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
	$meapu = mysql_query($apuqu) or pupe_error($apuqu);
	$meapurow = mysql_fetch_array($meapu);
	
	if ($meapurow["jv"] != "") {
		$jvlisays =  " - ".t("Jälkivaatimus");
	}
	else {
		$jvlisays =  "";	
	}
	
	$pdf->draw_text(mm_pt(20), mm_pt(55), $laskurow["toimitustapa"].$jvlisays,									$firstpage, $kirj);
	$pdf->draw_text(mm_pt(20), mm_pt(50), $laskurow['viesti'],													$firstpage, $kirj);
	
	//kolliluku
	$query = "	SELECT sum(kollit) kollit
				FROM rahtikirjat
				WHERE yhtio='$kukarow[yhtio]' and otsikkonro = '$laskurow[tunnus]'";
	$pakka = mysql_query($query) or pupe_error($query);
	$pak = mysql_fetch_array($pakka);

	if ((int) $pak["kollit"] > 0) {
		$pdf->draw_text(mm_pt(20), mm_pt(45), t("Kollimäärä").": ".$pak["kollit"],								$firstpage, $kirj);
	}
	
	if($laskurow['yhteyshenkilo'] != "") {
		$pdf->draw_text(mm_pt(20), mm_pt(40), $laskurow['yhteyshenkilo'],										$firstpage, $kirj);
	}
	
	//tänne ohjeisiin tulee lopullisen asiakkaan tiedot jos kyseessä on koontikuljetus
	$query = "	select tulostustapa
				from toimitustapa
				where yhtio = '$kukarow[yhtio]'
				and selite  = '$laskurow[toimitustapa]'";
	$hetir = mysql_query($query) or pupe_error($query);
	$hetirow = mysql_fetch_array($hetir);
	
	if ($hetirow['tulostustapa'] == 'K') {
		$pdf->draw_paragraph(mm_pt(50), mm_pt(20), mm_pt(25), mm_pt(140), $laskurow['sisviesti1'], $firstpage, $kirj);
	}
	
	// ylhäällä on "oikee" tapa tehdä, mutta me ei tiedetä tässä vaiheessa kollien määrää eikä painoa
	$pdf->draw_text(mm_pt(4), mm_pt(30), "Tilaukset - Order - Orders: ",	$firstpage, $otsik);
	if ($tilausnumeroita == "") $tilausnumeroita = $laskurow["tunnus"];
	$pdf->draw_text(mm_pt(50), mm_pt(30), $tilausnumeroita, $firstpage, $kirj);
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

	require("inc/sahkoposti.inc");
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

?>
