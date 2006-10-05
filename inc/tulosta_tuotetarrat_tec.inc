<?php
//Kun tullaan tänne tarvitaan $komento joka on se komento jolla tulostetaan esim. lpr -P tarrakirjoitin
//ja tarvitaan myös $tuoteno
$query = "select * from tuote where yhtio = '$kukarow[yhtio]' and tuoteno = '$tuoteno'";
$tarrares = mysql_query($query) or pupe_error($query);
//tuote pitää löytyä
if (mysql_num_rows($tarrares) == 1) {
	$tarrarow = mysql_fetch_array($tarrares);
	//jos tullaan yksinkertaisesta näkymästä tulostetaan tuoteno eankoodin sijaan
	if ($toim== 'YKS') {
		$tarrarow['eankoodi'] = $tarrarow['tuoteno'];
	}
	//jos eankoodi kenttä on tyhjä ei viittitä edes lähteä tulostelemaan
	if ($tarrarow['eankoodi']!= '0') {
		$eankoodi = trim($tarrarow['eankoodi']);
		$nimitys = $tarrarow['nimitys'];
		$nimitys2 = '';
		//jos nimitys on pidempi kuin 22 merkkiä niin se ei mahdu yhdelle riville vaan pitää jakaa kahtia
		if (strlen($nimitys) > 22) {
			$nimitys = substr($tarrarow['nimitys'],0,22);
			$nimitys2 = substr($tarrarow['nimitys'],22,22);
		}
		$tuoteno = $tarrarow['tuoteno'];
		//tässä arvataan merkkimäärän perusteella minkälainen viivakoodi pitää tehdä
		$countti = strlen($eankoodi);
		if ($countti== 13) {
			$laji = 'EAN13';
		}
		elseif ($countti== 12) {
			$laji = 'UPC12';
		}
		else {
			$laji = '';
		}

		$tulos = false;
		//tässä lasketaan koodin virallisuus
		if (is_numeric($eankoodi) and $eankoodi >= 0) // vaan positiivinen numeerinen tietue
		{
			$pit  = strlen($eankoodi);
			$factor		= 3;
			$summa		= 0;
			$ajetaanko	= false;
			if (!empty($laji)) $laji = strtoupper($laji);

			if (($laji=="EAN8") && ($pit==8))							$ajetaanko = true;
			elseif ((($laji=="EAN13") || ($laji=="ILN")) && ($pit==13))	$ajetaanko = true;
			elseif (($laji=="EAN14") && ($pit==14))						$ajetaanko = true;
			elseif (($laji=="UPC8") && ($pit==8))						$ajetaanko = true;
			elseif (($laji=="UPC12") && ($pit==12))						$ajetaanko = true;
			elseif (($laji=="NVE18") && ($pit==18))						$ajetaanko = true;

			if ($ajetaanko)
			{
				$rest = substr($eankoodi,-1);
				$wert = substr($eankoodi,0,$pit-1);
				$sum = '';
				for ($i=($pit-1);$i>0;--$i)
				{
					$sum	= $sum + substr($wert,$i-1,1) * $factor;
					$factor	= 4 - $factor;
				}
				if ($rest==((1000-$sum)%10))	$tulos = true;
			}
		}
		//EAN-13
		if ($laji== 'EAN13' and $tulos== true) {
			//echo "on ean<br>";
			$koodi = '5,1';
		}
		//UPC-A
		elseif ($laji== 'UPC12' and $tulos== true) {
			//echo "on UPC<br>";
			$koodi = 'K,1';
		}
		//JOTAIN MUUTA
		else {
			$koodi = '9,4';
			//echo "ei ole MIKAAN $laji<br>";
		}
		//tarvittaessa tehdään toinen nimitysrivi
		$nimity2sots = '';
		$nimity2sriv = '';
		if ($nimitys2 != '') {
			$nimity2sots = '{PC002;0040,0130,10,10,G,00,B|}';
			$nimity2sriv = '{RC002;'.$nimitys2.'|}';
		}
		//jos tuoteno on yli 14 merkkiä piennenetään fonttia
		$tuotekoko = '15,15';
		if (strlen($tuoteno) > 14) {
			$tuotekoko = '10,10';
		}
		//tässä tehdään aineisto jota TEC tulostin tajuaa
		$sivu = "
*** ETIKETIN KOKO ***
{D0300,0500,0300|}
{AX;+000,+000,+00|}
{AY;+05,0|}
{C|}

*** TÄSTÄ SE ALKAA ***
{PC000;0040,0050,$tuotekoko,G,00,B|}
{RC000;$tuoteno|}
{PC001;0040,0090,10,10,G,00,B|}
{RC001;$nimitys|}
$nimity2sots
$nimity2sriv

*** VIIVAKOODI ***
{XB00;0060,0150,$koodi,02,0,0080,+0000000000,000,1,00|}
{RB00;$eankoodi|}

*** TULOSTUS  ***
{XS;I,0001,0002C3210|}

".chr(12);
		//konvertoidaan ääkköset printterin ymmärtämään muotoon
		$from = array ('ä','å','ö','Ä','Å','Ö');
		$to   = array (chr(132),chr(134),chr(148),chr(142),chr(143),chr(153)); // DOS charset
		$sivu = str_replace($from, $to, $sivu);											// Tehdään käännös

		$sivu = escapeshellarg($sivu);
		$line = exec(" echo \"$sivu\" | $komento");
	}
	else {
		echo "<font class='error'>".t("Tuotteella")." $tuoteno ".t("ei ole viivakoodia järjestelmässä joten tarrojen tulostus taitaa olla turhaa")."!!!<br></font>";
	}
}
else {
	echo "<font class='error'>".t("Tuotetta")." $tuoteno ".t("ei löydy järjestelmästä")."!!!<br></font>";
}
?>