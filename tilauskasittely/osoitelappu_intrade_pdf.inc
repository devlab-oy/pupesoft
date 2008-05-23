<?php

// tarvitaan tilauksen tunnus muutujassa $tunnus
// printterin komento muuttujassa $oslapp
// $kukarow[yhtio] jostain saadaan yhtio
// $yhtiorow array josta saada lähettäjän tiedot

if ($phpnimi != "rahtikirja_custom.php") {
	//Tulostetaan standardi kolliosoitelappu
	$query = "	SELECT lasku.*, if(yhteyshenkilo.nimi!='',concat_ws(' / ', yhteyshenkilo.nimi,if(yhteyshenkilo.gsm!='',yhteyshenkilo.gsm,if(yhteyshenkilo.puh!='',yhteyshenkilo.puh,NULL))),'') yhteyshenkilo
				FROM lasku
				LEFT JOIN laskun_lisatiedot ON lasku.yhtio=laskun_lisatiedot.yhtio and laskun_lisatiedot.otunnus=lasku.tunnus
				LEFT JOIN yhteyshenkilo ON yhteyshenkilo.yhtio=laskun_lisatiedot.yhtio and yhteyshenkilo.tunnus=laskun_lisatiedot.yhteyshenkilo_tekninen
				WHERE lasku.yhtio='$kukarow[yhtio]' and lasku.tunnus='$tunnus'";
	$tempr = mysql_query($query) or pupe_error($query);
	$laskurow = mysql_fetch_array($tempr);
}
else {
	$laskurow = $osoitelappurow;
}

$prikomento = explode('-#', $oslapp);

// haetaan printterin osoitetiedot
$query = "SELECT * from kirjoittimet where yhtio='$kukarow[yhtio]' and trim(komento) like concat(trim('$prikomento[0]'),'%') and nimi!=''";
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
// ean (8)
// lähetenro (6) viimeistä
// kollityht (2)
// tarkiste (1)

$apu_kollityht = $kollityht;
if ($kollityht > 100) {
	$apu_kollityht = 99; // varmuuden vuoks, kun ei saa olla ku kaks merkkiä
}
elseif ($kollityht == 0) {
	$apu_kollityht = 1;
}

if ($yhtiorow['ean'] == '') {
	$yhtiorow['ean'] = $yhtiorow["ytunnus"];
}

$sscc = 1;
$sscc .= sprintf("%-08.08s",$yhtiorow["ean"]);
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
require_once("barcode/c128cobject.php");
require_once("pdflib/phppdflib.class.php");

//PDF parametrit
$pdf = new pdffile;
$pdf->set_default('margin-top', 	0);
$pdf->set_default('margin-bottom', 	0);
$pdf->set_default('margin-left', 	0);
$pdf->set_default('margin-right', 	0);
$rectparam["width"] = 0.3;

//fontit
$otsik["height"] = 9;
$otsik["font"] = "Helvetica";

$pien["height"] = 10;
$pien["font"] = "Helvetica";

$norm["height"] = 10;
$norm["font"] = "Helvetica-Bold";

$piennorm["height"] = 9;
$piennorm["font"] = "Helvetica-Bold";

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
	//$height      = "50";
	$height      = mm_pt("25");
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
	$obj = new c128cobject($width, $height, $style, '400'.$laskurow['asiakkaan_tilausnumero'], 'FNC1');

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
		$pdf->image_place($image, mm_pt(70), mm_pt(30), $firstpage); // Y, X
		$pdf->draw_text(mm_pt(22), mm_pt(65), "Asiakastilaus: ",	$firstpage, $norm);
		$pdf->draw_text(mm_pt(50), mm_pt(65), "(400) $laskurow[asiakkaan_tilausnumero]",	$firstpage, $iso);
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
	//$height      = "50";
	$height      = mm_pt("25");
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
	$obj = new c128cobject($width, $height, $style, '00'.$sscc, 'FNC1');
	//$obj = new c128cobject($width, $height, $style, '00100000123016093006', 'FNC1');
	
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
		$sscc = substr($sscc,0,4).substr($sscc,4,1).substr($sscc,5,8).substr($sscc,13,8).substr($sscc,21,1);

		// piirretään viivakoodi paprulle
		$pdf->image_place($image, mm_pt(38), mm_pt(-15), $firstpage); // Y, X
		$pdf->draw_text(mm_pt(22), mm_pt(33), "Kolli - Item ID.: ", $firstpage, $otsik);
		$pdf->draw_text(mm_pt(45), mm_pt(33), "(00) $sscc", $firstpage, $iso);
		unset($obj);

		//dellataan tmp filet kuleksimasta
		system("rm -f $nimi1 $nimi2");
	}
	else {
		echo "object create error 2";
	}
	
	// sitten aletaan piirtämään itse PDF sisältöä
	// ensin isot viivat kohilleen
	$pdf->draw_rectangle(mm_pt(179),mm_pt(21.5),mm_pt(31),mm_pt(126.5), $firstpage, $rectparam);

	unset($data);		
	if( (int) $yhtiorow["lasku_logo"] > 0) {
		$liite = hae_liite($yhtiorow["lasku_logo"], "Yllapito", "array");
		$data = $liite["data"];
		$isizelogo[0] = $liite["image_width"];
		$isizelogo[1] = $liite["image_height"];
		unset($liite);
	}
	elseif(file_exists($yhtiorow["lasku_logo"])) {
		$filename = $yhtiorow["lasku_logo"];

		$fh = fopen($filename, "r");
		$data = fread($fh, filesize($filename));
		fclose($fh);
		
		$isizelogo = getimagesize($yhtiorow["lasku_logo"]);
	}
	
	if($data) {
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
	$pdf->draw_text(mm_pt(22), mm_pt(175), "Mistä - Från - From: ",							$firstpage, $otsik);
	$pdf->draw_text(mm_pt(22), mm_pt(170), $yhtiorow['nimi'],								$firstpage, $pien);
	if ($yhtiorow['nimitark'] != '') {
		$pdf->draw_text(mm_pt(22), mm_pt(165), $yhtiorow['nimitark'],						$firstpage, $pien);
		$pdf->draw_text(mm_pt(22), mm_pt(160), $yhtiorow['osoite']." ".$yhtiorow['postino']." ".strtoupper($yhtiorow['postitp']),$firstpage, $pien);
	}
	else {
		$pdf->draw_text(mm_pt(22), mm_pt(165), $yhtiorow['osoite'].", ".$yhtiorow['postino']." ".strtoupper($yhtiorow['postitp']),$firstpage, $pien);
	}
	
	$pdf->draw_text(mm_pt(100), mm_pt(175), "Lähetyspvm",									$firstpage, $norm);
	$pdf->draw_text(mm_pt(100), mm_pt(170), date("d.m.Y") ,									$firstpage, $norm);
	
	$pdf->draw_text(mm_pt(22), mm_pt(157), "Terminaalitoimitus" ,							$firstpage, $norm);
	$pdf->draw_text(mm_pt(22), mm_pt(152), "INEX PARTNERS OY" ,								$firstpage, $norm);
	$pdf->draw_text(mm_pt(22), mm_pt(149), "Logistiikkakeskus Hakkila ovet 69 - 73" ,		$firstpage, $piennorm);
	$pdf->draw_text(mm_pt(22), mm_pt(145), "Kanervantie 6, 01380 Vantaa" ,					$firstpage, $norm);

	$pdf->draw_text(mm_pt(22),	mm_pt(140), "Minne - Till - To: " ,							$firstpage, $otsik);

	$pdf->draw_text(mm_pt(22),	mm_pt(136), $laskurow['toim_nimi'],							$firstpage, $norm);
	$pdf->draw_text(mm_pt(22),	mm_pt(132), $laskurow['toim_nimitark'],						$firstpage, $norm);
	$pdf->draw_text(mm_pt(22),	mm_pt(127), $laskurow['toim_osoite'],						$firstpage, $norm);
	$pdf->draw_text(mm_pt(22),	mm_pt(120), $laskurow['toim_postino']." ".$laskurow['toim_postitp'],	$firstpage, $iso);
	
	
	$pdf->draw_text(mm_pt(100), mm_pt(140), "Toimituspvm",									$firstpage, $norm);
	$pdf->draw_text(mm_pt(100), mm_pt(135), date("d.m.Y") ,									$firstpage, $norm);

	// Kuljetusohjeet = viivakoodi tulee tähän
	$pdf->draw_text(mm_pt(22), mm_pt(112), "Kuljetusohjeet - Transportinstruktioner - Transport Instructions:",	$firstpage, $otsik);
	$pdf->draw_text(mm_pt(22), mm_pt(107), "Reitti:                            Jono:                            Lähetysehto:",	$firstpage, $otsik);
	$pdf->draw_text(mm_pt(22), mm_pt(97),  "Lämpötila +0....+30C",							$firstpage, $norm);
	
	
	$pdf->draw_text(mm_pt(105), mm_pt(97),  "Kolli - Item",									$firstpage, $otsik);

	//kolliluku
	$pdf->draw_text(mm_pt(105), mm_pt(92), $tulostuskpl."/".$tulostakolli,							$firstpage, $norm);	
	
}

//keksitään uudelle failille joku varmasti uniikki nimi:
$pdffilenimi = "/tmp/osoitelappu-intrade-".md5(uniqid(rand(),true)).".pdf";

//kirjoitetaan pdf faili levylle..
$fh = fopen($pdffilenimi, "w");
if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus epäonnistui $pdffilenimi");
fclose($fh);

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
