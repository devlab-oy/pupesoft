<?php

	//Ohjelma vatii muuttujan $otunnus joka viittaa ostolaskuun johon rivit on kohdistettu.
	// sek‰ $laskurow arrayn jossa on select t‰hti from lasku jossa tunnus on $otunnus

	require_once('pdflib/phppdflib.class.php');

	$pdf = new pdffile;
	$pdf->set_default('margin', 0);
	$pdf->set_default('margin-left', 5);
	$rectparam["width"] = 0.3;

	if (!function_exists('alku_purku')) {
		function alku_purku () {
			global $pdf, $laskurow, $yhtiorow, $kukarow, $sivu, $rectparam, $kieli, $mista;

			$firstpage = $pdf->new_page("11.5x8in");

			$p["height"] 		= 10;
			$p["font"] 			= "Times-Roman";

			$pieni["height"] 	= 8;
			$pieni["font"] 		= "Times-Roman";
			
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

					if ($isizelogo[0] > $isizelogo[1] and $isizelogo[1] * (120 / $isizelogo[0]) <= 25) {
						$logoparam['scale'] = 120 / $isizelogo[0];
					}
					else {
						$logoparam['scale'] = 25  / $isizelogo[1];
					}

					$placement = $pdf->image_place($image, 568-($logoparam['scale']*$isizelogo[1]), 22, $firstpage, $logoparam);
				}			
			}
			else {
				$pdf->draw_text(30,  555, $yhtiorow["nimi"], 				$firstpage);
			}


			$pdf->draw_rectangle(570, 20,  530, 808, $firstpage, 			$rectparam);
			
			if ($mista == 'vastaanota') {
				$pdf->draw_text(280, 555, t("VASTAANOTETUT", $kieli), 				$firstpage);
			}
			else {
				$pdf->draw_text(280, 555, t("PURKULISTA", $kieli), 				$firstpage);
			}
			
			
			$pdf->draw_text(440, 555, $laskurow["nimi"], 					$firstpage);

			$pdf->draw_text(155, 545, t("Sivu", $kieli), 					$firstpage, $pieni);
			$pdf->draw_text(155, 535, $sivu, 								$firstpage, $p);

			if ($mista == 'vastaanota') {
				$pdf->draw_text(280,  545, t("Tilaus", $kieli),			$firstpage, $pieni);
				$pdf->draw_text(280,  535, $laskurow["tunnus"],				$firstpage, $p);
			}
			else {
				$pdf->draw_text(280,  545, t("Keikkanumero", $kieli),			$firstpage, $pieni);
				$pdf->draw_text(280,  535, $laskurow["laskunro"],				$firstpage, $p);
			}
			
			$pdf->draw_text(440, 545, t("Ajoaika", $kieli), 				$firstpage, $pieni);
			$pdf->draw_text(440, 535, tv1dateconv(date("Y-m-d H:i"), 'P'), 	$firstpage, $p);
			
			if ($mista != 'vastaanota') {
				$pdf->draw_text(520, 545, t("Kommentti", $kieli), 				$firstpage, $pieni);
				$pdf->draw_text(520, 535, $laskurow["comments"],				$firstpage, $p);
			}
			
			$pdf->draw_rectangle(530, 20,  510, 808, $firstpage, $rectparam);
			
			if ($mista == 'vastaanota') {
				$pdf->draw_text(30, 520, t("Tuotekoodi", $kieli),			$firstpage, $pieni);
			}
			else {
				$pdf->draw_text(30,  520, t("Toimittajan koodi", $kieli),	$firstpage, $pieni);
				$pdf->draw_text(155, 520, t("Tuotekoodi", $kieli),			$firstpage, $pieni);
			}
			
			if ($mista == 'vastaanota') {
				$pdf->draw_text(155, 520, t("Nimitys", $kieli),				$firstpage, $pieni);
			}
			else {
				$pdf->draw_text(280, 520, t("Nimitys", $kieli),				$firstpage, $pieni);
			}
			
			if ($mista == 'vastaanota') {
				$pdf->draw_text(315, 520, t("Paikasta", $kieli),		$firstpage, $pieni);
				$pdf->draw_text(475, 520, t("Paikalle", $kieli),		$firstpage, $pieni);
			}
			else {
				$pdf->draw_text(440, 520, t("Paikka(o.saldo)", $kieli),		$firstpage, $pieni);
			}
			
			
			if ($mista == 'vastaanota') {
				$pdf->draw_text(725, 520, t("M‰‰r‰", $kieli),				$firstpage, $pieni);
				
			}
			else {
				$pdf->draw_text(600, 520, t("M‰‰r‰", $kieli),				$firstpage, $pieni);
				$pdf->draw_text(650, 520, t("Kerroin", $kieli),				$firstpage, $pieni);
				$pdf->draw_text(705, 520, t("Toim.M‰‰r‰", $kieli),			$firstpage, $pieni);
			}
			
			$pdf->draw_text(770, 520, t("Yksikkˆ", $kieli),				$firstpage, $pieni);

			$pdf->draw_rectangle(530, 20, 10, 808, $firstpage, $rectparam);

			return($firstpage);
		}
	}

	if (!function_exists('print_pdf_purku')) {
		function print_pdf_purku () {
			global $pdf, $kukarow, $yhtiorow, $komento, $otunnus, $tee, $kieli, $mista;

			//keksit‰‰n uudelle failille joku varmasti uniikki nimi:
			list($usec, $sec) = explode(' ', microtime());
			mt_srand((float) $sec + ((float) $usec * 100000));
			
			if ($mista == "vastaanota") {
				$pdffilenimi = "/tmp/Vastaanotetut-".md5(uniqid(mt_rand(), true)).".pdf";
			}
			else {
				$pdffilenimi = "/tmp/Purkulista-".md5(uniqid(mt_rand(), true)).".pdf";
			}
			

			//kirjoitetaan pdf faili levylle..
			$fh = fopen($pdffilenimi, "w");
			if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF Error $pdffilenimi");
			fclose($fh);

			//itse print komento tulee valitse_tulosin.incilt‰...
			if ($komento["Purkulista"] == 'email' or $komento["Vastaanotetut"] == 'email') {
				$liite = $pdffilenimi;
				
				if ($mista == "vastaanota") {
					$kutsu = "Vastaanotetut";
				}
				else {
					$kutsu = "Purkulista";
				}				

				require("../inc/sahkoposti.inc");
				
			}
			elseif ($tee == 'NAYTATILAUS') {
    			//Tyˆnnet‰‰n tuo pdf vaan putkeen!
    			echo file_get_contents($pdffilenimi);
    		}
			elseif (($komento["Purkulista"] != '' and $komento["Purkulista"] != 'edi') or ($komento["Vastaanotetut"] != '' and $komento["Vastaanotetut"] != 'edi')) {
				
				if ($mista == "vastaanota") {
					$line = exec("$komento[Vastaanotetut] $pdffilenimi");
				}
				else {
					$line = exec("$komento[Purkulista] $pdffilenimi");
				}
				
			}

			//poistetaan tmp file samantien kuleksimasta...
			system("rm -f $pdffilenimi");
		}
	}

	if ($sorttaus == 'paikka') {
		$sorttauskentta = generoi_sorttauskentta("1");
	}
	elseif ($sorttaus == 'tuote') {
		$sorttauskentta = generoi_sorttauskentta("5");
	}
	else {
		$sorttauskentta = generoi_sorttauskentta("10", $laskurow["liitostunnus"]);
	}

	if ($mitkarivit == "viematta") {
		$mitkalisa = " and tilausrivi.varattu != 0 ";
	}
	elseif ($mitkarivit == "viedyt") {
		$mitkalisa = " and tilausrivi.varattu = 0 ";
	}
	else {
		$mitkalisa = "";
	}

	if ($mista == "vastaanota" and $vainlistaus == '') {
		$query = "	SELECT tuote.tuoteno, tuote.sarjanumeroseuranta, tapahtuma.hyllyalue, tapahtuma.hyllynro, tapahtuma.hyllyvali, tapahtuma.hyllytaso,
					tilausrivi.yksikko, tilausrivi.varattu+tilausrivi.kpl varattu, tilausrivi.hinta, 					
					tuote.nimitys, tilausrivi.perheid2, tilausrivi.kommentti, tilausrivi.otunnus, tilausrivi.tunnus, tilausrivi.hyllyalue trhyllyalue, tilausrivi.hyllynro trhyllynro, tilausrivi.hyllyvali trhyllyvali, tilausrivi.hyllytaso trhyllytaso
					FROM tilausrivi
					JOIN tuote USING (yhtio, tuoteno)
					JOIN tapahtuma ON tilausrivi.yhtio = tapahtuma.yhtio and tilausrivi.tunnus = tapahtuma.rivitunnus and tapahtuma.kpl > 0 and laji = 'siirto'
					WHERE tilausrivi.otunnus = '$otunnus'
					and tilausrivi.yhtio='$kukarow[yhtio]'
					ORDER BY tilausrivi.tunnus";
	}
	elseif ($mista == "vastaanota" and $vainlistaus != '') {
		$query = "	SELECT tuote.tuoteno, tuote.sarjanumeroseuranta,
					tilausrivi.yksikko, tilausrivi.varattu+tilausrivi.kpl varattu, tilausrivi.hinta, 					
					tuote.nimitys, tilausrivi.perheid2, tilausrivi.kommentti, tilausrivi.otunnus, tilausrivi.tunnus, tilausrivi.hyllyalue trhyllyalue, tilausrivi.hyllynro trhyllynro, tilausrivi.hyllyvali trhyllyvali, tilausrivi.hyllytaso trhyllytaso
					FROM tilausrivi
					JOIN tuote USING (yhtio, tuoteno)
					WHERE tilausrivi.otunnus = '$otunnus'
					and tilausrivi.yhtio='$kukarow[yhtio]'
					ORDER BY tilausrivi.tunnus";
	}
	else {
		$query = "	SELECT tuotteen_toimittajat.toim_tuoteno, tuote.tuoteno, hyllyalue, hyllynro, hyllyvali, tuote.sarjanumeroseuranta,
					hyllytaso, tilausrivi.yksikko, tilausrivi.varattu+tilausrivi.kpl varattu, tilausrivi.hinta, if(tuotteen_toimittajat.tuotekerroin=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin) tuotekerroin,
					round(if(tuotteen_toimittajat.tuotekerroin=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin)*(tilausrivi.varattu+tilausrivi.kpl),2) toimmaara, 
					tuote.nimitys, tilausrivi.perheid2, tilausrivi.kommentti, tilausrivi.otunnus, tilausrivi.tunnus,
					$sorttauskentta
					FROM tilausrivi
					JOIN tuote USING (yhtio, tuoteno)
					JOIN tuotteen_toimittajat ON tilausrivi.yhtio = tuotteen_toimittajat.yhtio and tilausrivi.tuoteno = tuotteen_toimittajat.tuoteno and tuotteen_toimittajat.liitostunnus = '$laskurow[liitostunnus]'
					WHERE tilausrivi.uusiotunnus = '$otunnus'
					and tilausrivi.yhtio='$kukarow[yhtio]'
					$mitkalisa
					ORDER BY sorttauskentta, tilausrivi.tunnus";
	}	
	
	$result = mysql_query($query) or pupe_error($query);
				
	$kala 		= 500;
	$total 		= 0;
	$sivu 		= 1;
	$sumarum	= 0;

	$firstpage = alku_purku();

	while ($row = mysql_fetch_array($result)) {
		if ($kala < 50) {
			$sivu++;
			$firstpage = alku_purku();
			$kala = 500;
		}

		$query = "	SELECT saldo, hyllyalue, hyllynro, hyllyvali, hyllytaso 
					FROM tuotepaikat 
					WHERE yhtio = '$kukarow[yhtio]' 
					and tuoteno = '$row[tuoteno]' 
					and oletus  = 'X'";
		$olesaldoresult = mysql_query($query) or pupe_error($query);
		$olesaldorow = mysql_fetch_array($olesaldoresult);

		$query = "	SELECT ifnull(sum(if(keratty!='',tilausrivi.varattu,0)),0) keratty,	ifnull(sum(tilausrivi.varattu),0) ennpois
					FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
					WHERE yhtio 	= '$kukarow[yhtio]'
					and tyyppi 		in ('L','G','V')
					and tuoteno		= '$row[tuoteno]'
					and varattu    <> '0'
					and laskutettu 	= ''
					and hyllyalue	= '$olesaldorow[hyllyalue]'
					and hyllynro 	= '$olesaldorow[hyllynro]'
					and hyllyvali 	= '$olesaldorow[hyllyvali]'
					and hyllytaso 	= '$olesaldorow[hyllytaso]'";
		$hylresult = mysql_query($query) or pupe_error($query);
		$hylrow = mysql_fetch_array($hylresult);

		$hyllyssa = $olesaldorow['saldo']-$hylrow['keratty'];

		$p["height"] = 10;
		$p["font"] = "Times-Roman";

		$pieni["height"] = 8;
		$pieni["font"] = "Times-Roman";

		$query = "	SELECT * 
					FROM tuotepaikat 
					WHERE yhtio   = '$kukarow[yhtio]' 
					and tuoteno   = '$row[tuoteno]' 
					and hyllyalue = '$row[hyllyalue]' 
					and hyllynro  = '$row[hyllynro]' 
					and hyllyvali = '$row[hyllyvali]' 
					and hyllytaso = '$row[hyllytaso]'";
		$xolesaldoresult = mysql_query($query) or pupe_error($query);

		// tuotepaikoista ei lˆydy rivill‰ olevaa varastopaikkaa.. silloin ei echota mit‰‰n paikkaa
		if (mysql_num_rows($xolesaldoresult) == 0) {
			$row["hyllyalue"] = "";
			$row["hyllynro"]  = "";
			$row["hyllyvali"] = "";
			$row["hyllytaso"] = "";
		}
		
		// viivat rivien v‰liin...
		$x[0] = 20;
		$x[1] = 808;
		$y[0] = $y[1] = $kala + 15 - 4;
		

		if (($perheid == 0 or $perheid != $row["perheid2"]) and isset($perheid)) {
			$pdf->draw_line($x, $y, $firstpage, $rectparam);
		}
		
		$perheid = $row["perheid2"];


		if ($mista == "vastaanota") {
			$pdf->draw_text(30, $kala, $row["tuoteno"], 		$firstpage, $p);
		}
		else {
			$pdf->draw_text(30,  $kala, $row["toim_tuoteno"],	$firstpage, $p);
			$pdf->draw_text(155, $kala, $row["tuoteno"], 		$firstpage, $p);
		}
		
		if ($mista == "vastaanota") {
			$pohja = $pdf->draw_paragraph($kala+10, 155, 5, 315, asana('nimitys_',$row['tuoteno'],$row['nimitys']), $firstpage, $pieni);
		}
		else {
			$pohja = $pdf->draw_paragraph($kala+10, 280, 5, 440, asana('nimitys_',$row['tuoteno'],$row['nimitys']), $firstpage, $pieni);
		}
		
		$lisaa = 0;
		
		if ($pohja < $kala){
			$lisaa = $kala-$pohja;
		}
		
		if ($mista == "vastaanota") {
			$pdf->draw_text(315, $kala, $row["trhyllyalue"]." ".$row["trhyllynro"]." ".$row["trhyllyvali"]." ".$row["trhyllytaso"],		$firstpage, $p);
			
			if ($vainlistaus != '') {
				$pdf->draw_text(475, $kala, $t1[$row[tunnus]]." ".$t2[$row[tunnus]]." ".$t3[$row[tunnus]]." ".$t4[$row[tunnus]],		$firstpage, $p);
			}
			else {
				$pdf->draw_text(475, $kala, $row["hyllyalue"]." ".$row["hyllynro"]." ".$row["hyllyvali"]." ".$row["hyllytaso"],		$firstpage, $p);
			}
			
		}
		else {
			$pdf->draw_text(440, $kala, $row["hyllyalue"]." ".$row["hyllynro"]." ".$row["hyllyvali"]." ".$row["hyllytaso"]." (".$hyllyssa.") ",		$firstpage, $p);
		}
		
					
		if ($mista == "vastaanota") {	
			$oikpos = $pdf->strlen((float) $row["varattu"], $p);
			$pdf->draw_text(745-$oikpos, $kala, (float) $row["varattu"], 	$firstpage, $p);			
		}
		else {
			$oikpos = $pdf->strlen((float) $row["varattu"], $p);
			$pdf->draw_text(620-$oikpos, $kala, (float) $row["varattu"], 	$firstpage, $p);
			
			$oikpos = $pdf->strlen((float) $row["tuotekerroin"], $p);
			$pdf->draw_text(675-$oikpos, $kala, (float) $row["tuotekerroin"], 	$firstpage, $p);

			$oikpos = $pdf->strlen((float) $row["toimmaara"], $p);
			$pdf->draw_text(745-$oikpos, $kala, (float) $row["toimmaara"], 	$firstpage, $p);
		}		
		
		
		$pdf->draw_text(770, $kala, $row["yksikko"], 					$firstpage, $p);
		
		
		$kala = $kala - 15 - $lisaa;
		
		if ($row["sarjanumeroseuranta"] != "") {
			
			if ($row["varattu"] < 0) {
				$tunken = "myyntirivitunnus";
				$tunind = "yhtio_myyntirivi";
			}
			else {
				$tunken = "ostorivitunnus";
				$tunind = "yhtio_ostorivi";
			}
			
			$query	= "	SELECT distinct sarjanumero, parasta_ennen
	   					FROM sarjanumeroseuranta
	   					WHERE yhtio = '$kukarow[yhtio]'
						and tuoteno = '$row[tuoteno]'
	   					and $tunken = '$row[tunnus]'";
	   		$sarjares = mysql_query($query) or pupe_error($query);

			while ($sarjarow = mysql_fetch_array($sarjares)) {
				if ($row["sarjanumeroseuranta"] == "S") {
					$row["kommentti"] .= " \n".t("S:nro", $kieli).": ".$sarjarow["sarjanumero"];		
				}
				elseif ($row["sarjanumeroseuranta"] == "E") {
					$row["kommentti"] .= " \n".t("Er‰", $kieli).": ".$sarjarow["sarjanumero"];	
				}
				elseif ($row["sarjanumeroseuranta"] == "F") {
					$row["kommentti"] .= " \n".t("Er‰", $kieli).": ".$sarjarow["sarjanumero"]." ".t("Parasta ennen", $kieli).": ".tv1dateconv($sarjarow["parasta_ennen"]);	
				}
			}
		}
			
		// Trimmataan uudestaan
		$row["kommentti"] = trim($row["kommentti"]);
		
		if ($row["kommentti"] != '') {
			$pohja = $pdf->draw_paragraph($kala+$p["height"]+5, 160, 5, 440, $row["kommentti"],	$firstpage, $p);						
			$kala = $pohja - 15;
		}
		$kala -= 15;
		
		$sumarum += $row["varattu"];
		$total   += $row["hinta"];
	}
		
	// viivat rivien v‰liin...
	$x[0] = 20;
	$x[1] = 808;
	$y[0] = $y[1] = $kala + 15 - 4;
	$pdf->draw_line($x, $y, $firstpage, $rectparam);
	
	if ($mista != "vastaanota") {
		$sumarum = sprintf('%.2f',$sumarum);
		$pdf->draw_text(520,  $kala, t("Yhteens‰", $kieli),	$firstpage, $p);
		$pdf->draw_text(570,  $kala, $sumarum,	$firstpage, $p);
	
	
		$pdf->draw_rectangle(40, 20,  10, 200, $firstpage, $rectparam);
		$pdf->draw_rectangle(40, 200, 10, 808, $firstpage, $rectparam);
		$pdf->draw_rectangle(40, 500, 10, 808, $firstpage, $rectparam);
		$pdf->draw_text(30,   30, t("P‰iv‰m‰‰r‰ ja aika", $kieli),	$firstpage, $pieni);
		$pdf->draw_text(210,  30, t("Allekirjoitus", $kieli),	$firstpage, $pieni);
		$pdf->draw_text(504,  30, t("Nimenselvennys", $kieli),	$firstpage, $pieni);
	}
	//kutsutaan lopuksi viel‰ print_pdf_purku funktiota
	print_pdf_purku();

	//p‰‰dyt‰‰n taas selailemaan toimittajia
	if ($mista == "vastaanota") {
		echo t("Vastaanotetut lista tulostuu")."...<br>";
	}
	else {
		echo t("Purkulista tulostuu")."...<br>";
	}
	
?>
