<?php

// Haetaan tiedot kuittia varten lasku & kassa-tiliointi

$query = "select nimi, selite, l.tapvm, sum(t.summa) summa from lasku l
			left join tiliointi t on t.yhtio=l.yhtio and t.ltunnus=l.tunnus and tilino = '$yhtiorow[kassa]'
			where l.yhtio='$kukarow[yhtio]' and l.tunnus = '$tunnus'
			group by 1,2,3";
$tempr = mysql_query($query) or pupe_error($query);
if (mysql_num_rows($tempr) == 0) {
	echo "<font class='error'>".t("Tosite kateissa")." '$tunnus'</font><br>";
	exit;
}
$kuittirow = mysql_fetch_array($tempr);

//Haetaan kirjoitin
$query = "	select komento from kirjoittimet
					where yhtio='$kukarow[yhtio]' and tunnus = '$kukarow[kirjoitin]'";
$tempr = mysql_query($query) or pupe_error($query);
if (mysql_num_rows($tempr) == 0) {
	echo "<font class='error'>".t("Kirjoitin kateissa")." '$kukarow[kirjoitin]'</font><br>";
	exit;
}
$kirjoitinrow = mysql_fetch_array($tempr);

//Kuka on saaja ja kuka luovuttaja

if ($kuittirow['summa'] > 0) {
	$saaja = $yhtiorow['nimi'];
	$luovuttaja = $kuittirow['nimi'];
}
else {
	$saaja = $kuittirow['nimi'];
	$luovuttaja = $yhtiorow['nimi'];
	$kuittirow['summa'] = $kuittirow['summa'] * -1;
}

//PDF:n luonti ja defaultit
require_once("pdflib/phppdflib.class.php");
	
//PDF parametrit
$pdf = new pdffile;
$pdf->set_default('margin-top', 	0);
$pdf->set_default('margin-bottom', 	0);
$pdf->set_default('margin-left', 	0);
$pdf->set_default('margin-right', 	0);
$rectparam["width"] = 0.3;

//fontit

$pieni["height"] = 6;
$pieni["font"] = "Times-Roman";

$kirj["height"] = 12;
$kirj["font"] = "Times-Roman";

$huge["height"] = 22;
$huge["font"] = "Helvetica-Bold";
	
// sitten aletaan piirtämään itse PDF sisältöä
$firstpage = $pdf->new_page("a4");

//Tulostetaan kaksi kappaletta samalle sivulle

for ($siirto = 0; $siirto <= 426; $siirto += 426) {

	$pdf->draw_text(250,  812 - $siirto, "".t("KUITTI")." $tunnus", $firstpage, $huge);

	$pdf->draw_text(65,  782 - $siirto,  $luovuttaja,  					$firstpage, $kirj);

	$pdf->draw_text(65,  758 - $siirto,
		sprintf(t("on suorittanut %s %6.2f ").$yhtiorow['valkoodi'],$kuittirow['tapvm'], $kuittirow['summa']),
																		$firstpage, $kirj);
	$pdf->draw_text(65,  736 - $siirto,
		sprintf(t("%s:lle"), $saaja),										$firstpage, $kirj);

	$pdf->draw_text(65,  714 - $siirto, "".t("Lisätietoja").": " . $kuittirow['selite'],
																		$firstpage, $kirj);
		
	$pdf->draw_text(65,  635 - $siirto,  "".t("Allekirjoitus")."",  				$firstpage, $pieni);
	$pdf->draw_rectangle(645 - $siirto, 60, 623 - $siirto, 300,			$firstpage, $rectparam);
	$pdf->draw_text(65,  610 - $siirto,  $saaja,			  			$firstpage, $pieni);
}

//keksitään uudelle failille joku varmasti uniikki nimi:
$pdffilenimi = "/tmp/kuitti-".md5(uniqid(rand(),true)).".pdf";

//kirjoitetaan pdf faili levylle..
$fh = fopen($pdffilenimi, "w");
if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus epäonnistui $pdffilenimi");
fclose($fh);

//itse print komento...

$line = exec("$kirjoitinrow[komento] $pdffilenimi");

?>
