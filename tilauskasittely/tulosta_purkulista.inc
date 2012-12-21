<?php

	//Ohjelma vatii muuttujan $otunnus joka viittaa ostolaskuun johon rivit on kohdistettu.
	// sek‰ $laskurow arrayn jossa on select t‰hti from lasku jossa tunnus on $otunnus

	require_once('pdflib/phppdflib.class.php');

	// jos php-gd on installoitu niin loidataab barcode library
	if (in_array("gd", get_loaded_extensions())) {
		if (@include_once("viivakoodi/Barcode.php")) {
		}
		else {
			include_once("Barcode.php");
		}
	}

	$pdf = new pdffile;
	$pdf->set_default('margin-top', 	0);
	$pdf->set_default('margin-bottom', 	0);
	$pdf->set_default('margin-left', 	0);
	$pdf->set_default('margin-right', 	0);
	$rectparam["width"] = 0.3;

	if (!function_exists('alku_purku')) {
		function alku_purku () {
			global $pdf, $laskurow, $yhtiorow, $kukarow, $sivu, $rectparam, $kieli, $mista;

			$firstpage = $pdf->new_page("842x595");

			$p["height"] 		= 10;
			$p["font"] 			= "Times-Roman";

			$pieni["height"] 	= 8;
			$pieni["font"] 		= "Times-Roman";

			tulosta_logo_pdf($pdf, $firstpage, $laskurow, 555, 22, 25, 120);

			$pdf->draw_rectangle(570, 20,  530, 808, $firstpage, 			$rectparam);

			if ($mista == 'vastaanota') {
				$pdf->draw_text(280, 555, t("VASTAANOTETUT", $kieli), 		$firstpage);
			}
			else {
				$pdf->draw_text(280, 555, t("PURKULISTA", $kieli), 			$firstpage);
			}

			$pdf->draw_text(440, 555, $laskurow["nimi"], 					$firstpage);

			$pdf->draw_text(155, 545, t("Sivu", $kieli), 					$firstpage, $pieni);
			$pdf->draw_text(155, 535, $sivu, 								$firstpage, $p);

			if ($mista == 'vastaanota') {
				$pdf->draw_text(280,  545, t("Tilaus", $kieli),				$firstpage, $pieni);
				$pdf->draw_text(280,  535, $laskurow["tunnus"],				$firstpage, $p);
			}
			else {
				$pdf->draw_text(280,  545, t("Saapumisnumero", $kieli),		$firstpage, $pieni);
				$pdf->draw_text(280,  535, $laskurow["laskunro"],			$firstpage, $p);
			}

			$pdf->draw_text(440, 545, t("Ajoaika", $kieli), 				$firstpage, $pieni);
			$pdf->draw_text(440, 535, tv1dateconv(date("Y-m-d H:i"), 'P'), 	$firstpage, $p);

			if ($mista != 'vastaanota') {
				$pdf->draw_text(520, 545, t("Kommentti", $kieli), 			$firstpage, $pieni);
				$pdf->draw_text(520, 535, $laskurow["comments"],			$firstpage, $p);
			}

			$pdf->draw_rectangle(530, 20,  510, 808, $firstpage, $rectparam);

			if ($mista == 'vastaanota') {
				$pdf->draw_text(30, 520, t("Tuotekoodi", $kieli),			$firstpage, $pieni);
			}
			else {
				$pdf->draw_text(30,  520, t("Toimittajan koodi /", $kieli),	$firstpage, $pieni);
				$pdf->draw_text(30, 513, t("Tuotekoodi", $kieli),			$firstpage, $pieni);
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

			$pdf->draw_rectangle(530, 20, 20, 808, $firstpage, $rectparam);

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
				$pdffilenimi = "/tmp/".t("Vastaanotetut", $kieli)."-".md5(uniqid(mt_rand(), true)).".pdf";
			}
			else {
				$pdffilenimi = "/tmp/".t("Purkulista", $kieli)."-".md5(uniqid(mt_rand(), true)).".pdf";
			}

			//kirjoitetaan pdf faili levylle..
			$fh = fopen($pdffilenimi, "w");
			if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF Error $pdffilenimi");
			fclose($fh);

			//itse print komento tulee valitse_tulosin.incilt‰...
			if ($komento["Purkulista"] == 'email' or $komento["Vastaanotetut"] == 'email') {
				$liite = $pdffilenimi;

				if ($mista == "vastaanota") {
					$kutsu = t("Vastaanotetut", $kieli);
				}
				else {
					$kutsu = t("Purkulista", $kieli);
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
	elseif ($mitkarivit == "koodittomat") {
		$eanlisa = " and tuote.eankoodi = '' ";
	}
	else {
		$mitkalisa = "";
	}



	if ($yhtiorow['suuntalavat'] == 'S' and is_array($suuntalavat) and count($suuntalavat) > 0) {

		$query = "	SELECT GROUP_CONCAT(saapuminen) keikkatunnus
					FROM suuntalavat_saapuminen
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND suuntalava IN (".implode(",", $suuntalavat).")";
		$uusiotunnus_chk_res = pupe_query($query);
		$uusiotunnus_chk_row = mysql_fetch_assoc($uusiotunnus_chk_res);

		$otunnuslisa = "tilausrivi.uusiotunnus IN ({$uusiotunnus_chk_row['keikkatunnus']})";

		$selectlisa = ", suuntalavat.sscc ";
		$suuntalavajoin = " JOIN suuntalavat ON (suuntalavat.yhtio = tilausrivi.yhtio AND suuntalavat.tunnus IN (".implode(",", $suuntalavat).") AND suuntalavat.tunnus = tilausrivi.suuntalava)
							JOIN suuntalavat_saapuminen ON (suuntalavat_saapuminen.yhtio = suuntalavat.yhtio AND suuntalavat_saapuminen.suuntalava = suuntalavat.tunnus AND suuntalavat_saapuminen.saapuminen = tilausrivi.uusiotunnus) ";
		$orderlisa = " suuntalavat.sscc, ";
	}
	else {
		$otunnuslisa 	= "tilausrivi.uusiotunnus = '{$otunnus}'";
		$selectlisa 	= "";
		$suuntalavajoin = "";
		$orderlisa 		= "";
	}

	if ($mista == "vastaanota" and $vainlistaus == '') {
		$query = "	SELECT tuote.tuoteno,
					tuote.sarjanumeroseuranta,
					tapahtuma.hyllyalue,
					tapahtuma.hyllynro,
					tapahtuma.hyllyvali,
					tapahtuma.hyllytaso,
					tilausrivi.yksikko,
					tilausrivi.varattu+tilausrivi.kpl varattu,
					tilausrivi.hinta,
					tuote.nimitys,
					tilausrivi.perheid2,
					tilausrivi.kommentti,
					tilausrivi.otunnus,
					tilausrivi.tunnus,
					tilausrivi.hyllyalue trhyllyalue,
					tilausrivi.hyllynro trhyllynro,
					tilausrivi.hyllyvali trhyllyvali,
					tilausrivi.hyllytaso trhyllytaso,
					concat(lpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0')) sorttauskentta,
					tuote.purkukommentti,
					tilausrivi.laadittu
					FROM tilausrivi
					JOIN tuote USING (yhtio, tuoteno)
					JOIN tapahtuma ON tilausrivi.yhtio = tapahtuma.yhtio and tilausrivi.tunnus = tapahtuma.rivitunnus and tapahtuma.kpl > 0 and laji = 'siirto'
					WHERE tilausrivi.otunnus = '$otunnus'
					and tilausrivi.yhtio='$kukarow[yhtio]'
					ORDER BY sorttauskentta, tuote.tuoteno";
	}
	elseif ($mista == "vastaanota" and $vainlistaus != '') {
		$query = "	SELECT tuote.tuoteno,
					tuote.sarjanumeroseuranta,
					tilausrivi.yksikko,
					tilausrivi.varattu+tilausrivi.kpl varattu,
					tilausrivi.hinta,
					tuote.nimitys,
					tilausrivi.perheid2,
					tilausrivi.kommentti,
					tilausrivi.otunnus,
					tilausrivi.tunnus,
					tilausrivi.hyllyalue trhyllyalue,
					tilausrivi.hyllynro trhyllynro,
					tilausrivi.hyllyvali trhyllyvali,
					tilausrivi.hyllytaso trhyllytaso,
					concat(lpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0')) sorttauskentta,
					tuote.purkukommentti,
					tilausrivi.laadittu
					FROM tilausrivi
					JOIN tuote USING (yhtio, tuoteno)
					WHERE tilausrivi.otunnus = '$otunnus'
					and tilausrivi.yhtio='$kukarow[yhtio]'
					ORDER BY sorttauskentta, tuote.tuoteno";
	}
	else {
		$query = "	SELECT tuotteen_toimittajat.toim_tuoteno,
					tuote.tuoteno,
					tilausrivi.hyllyalue,
					tilausrivi.hyllynro,
					tilausrivi.hyllyvali,
					tuote.sarjanumeroseuranta,
					tilausrivi.hyllytaso,
					tilausrivi.yksikko,
					tilausrivi.varattu+tilausrivi.kpl varattu,
					tilausrivi.hinta,
					if(tuotteen_toimittajat.tuotekerroin=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin) tuotekerroin,
					round(if(tuotteen_toimittajat.tuotekerroin=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin)*(tilausrivi.varattu+tilausrivi.kpl),2) toimmaara,
					tuote.nimitys,
					tilausrivi.perheid2,
					tilausrivi.kommentti,
					tilausrivi.otunnus,
					tilausrivi.tunnus,
					tuote.purkukommentti,
					tilausrivi.laadittu,
					$sorttauskentta
					$selectlisa
					FROM tilausrivi
					$suuntalavajoin
					JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno $eanlisa
					LEFT JOIN tuotteen_toimittajat ON tilausrivi.yhtio = tuotteen_toimittajat.yhtio and tilausrivi.tuoteno = tuotteen_toimittajat.tuoteno and tuotteen_toimittajat.liitostunnus = '$laskurow[liitostunnus]'
					WHERE {$otunnuslisa}
					and tilausrivi.yhtio='$kukarow[yhtio]'
					$mitkalisa
					ORDER BY $orderlisa sorttauskentta, tilausrivi.tunnus";
	}

	$result = pupe_query($query);

	$kala 		= 500;
	$total 		= 0;
	$sivu 		= 1;
	$sumarum	= 0;
	$apukala 	= 0;
	$ekakala	= '';
	$edellinen_sscc = '';

	// Haetaan tulostimen parametrit
	$tulostin_query = " SELECT * FROM kirjoittimet WHERE yhtio = '{$kukarow["yhtio"]}' and komento = '{$komento["Purkulista"]}' ORDER BY tunnus ASC limit 1";
	$tulostin_result = pupe_query($tulostin_query);
	$tulostin_row = mysql_fetch_assoc($tulostin_result);

	if ($tulostin_row["merkisto"] != 0) {

		// RIVI 1
		$out  = chr(10);
		$out .= sprintf('%-20.20s',	$yhtiorow["nimi"]);
		$out .= sprintf('%4s',		' ');
		$out .= sprintf('%-26.26s',	t("Purkulista"));
		$out .= sprintf('%16s', 	' ');
		$out .= sprintf('%-6.6s', 	t("Ajopvm"));
		$out .= sprintf('%1s', 		' ');
		$out .= sprintf('%8.8s', 	date("d/m/y")); // Ajopvm
		$out .= chr(10).chr(13);	// Line feed (Rivinvaihto) + Carriage return (Siirret‰‰n kirjoitusp‰‰ rivin alkuun)

		// RIVI 2
		$out .= sprintf("%'-95s", '-');
		$out .= chr(10).chr(13);

		// RIVI 3
		$out .= sprintf('%-62.62s',	$laskurow["ytunnus"].' '.$laskurow["nimi"]);	// Mist‰ tavara tulee
		$out .= sprintf('%1s',		' ');
		$out .= sprintf('%10s',		t("Saapuminen").':');
		$out .= sprintf('%-11.11s',	$laskurow["laskunro"]);							// Keikan numero
		$out .= chr(10).chr(13);

		// RIVI 4
		$out .= sprintf("%'-95s",	'-');
		$out .= chr(10).chr(13);

		// RIVI 5
		$out .= sprintf('%-47s',	t("Tuoteno")." / ".t("Nimitys"));
		$out .= sprintf('%-15s',	t("Hylly"));
		$out .= sprintf('%17s',		t("Tilattu"));
		$out .= sprintf('%1s',		' ');
		$out .= sprintf('%3.3s',	t("Yks"));
		$out .= sprintf('%12.12s',	t("Saapunut"));
		$out .= chr(10).chr(13);

		while ($row = mysql_fetch_array($result)) {

			$yksikko = t_avainsana("Y", $kieli, "and avainsana.selite='$row[yksikko]'", "", "", "selite");
			$varattu = ($yksikko != "") ? $row["varattu"]." ".$yksikko : $row["varattu"];

			$hyllyalue_value = $yhtiorow['varastontunniste'] != "" ? substr("{$row["hyllyalue"]}", $yhtiorow['varastontunniste']) : $row["hyllyalue"];

			// RIVI 1
			$out .= sprintf('%-46.46s',	$row["tuoteno"]." ".$row["nimitys"]);
			$out .= sprintf('%1s',		' ');
			$out .= sprintf('%-23.23s',	$hyllyalue_value." ".$row["hyllynro"]." ".$row["hyllyvali"]." ".$row["hyllytaso"]);
			$out .= sprintf('%13.13s',	$varattu); // tilattu
			$out .= sprintf('%16s',		' ');
			$out .= sprintf('%-15.15s',	$row["tuoteno"]);
			$out .= chr(10).chr(13);

			// RIVI 2
			$out .= sprintf('%-99s',	$row["toim_tuoteno"]);
			$out .= sprintf('%-15.15s',	$hyllyalue_value." ".$row["hyllynro"]." ".$row["hyllyvali"]." ".$row["hyllytaso"]);
			$out .= chr(10).chr(10).chr(13);
		}

		# Rivinvaihto alkuun, jotta printit osuvat tarroille
		# $out = chr(10).chr(13).$out;

		// Form Feedi kahdesti, jotta ei tarvitse rullata k‰sin
		$out .= chr(12).chr(12);

		lpr($out, NULL, $komento["Purkulista"]);
	}
	else {
		$firstpage = alku_purku();

		if ($yhtiorow['suuntalavat'] == 'S' and trim($suuntalavajoin) != '') {

			$row = mysql_fetch_assoc($result);

			$p["height"] = 10;
			$p["font"] = "Times-Roman";

			$pieni["height"] = 8;
			$pieni["font"] = "Times-Roman";

			$pdf->draw_text(185, 545, "SSCC-".t("koodi", $kieli), 					$firstpage, $pieni);
			$pdf->draw_text(185, 535, $row['sscc'], 								$firstpage, $p);

			$edellinen_sscc = $row['sscc'];

			mysql_data_seek($result, 0);
		}

		while ($row = mysql_fetch_array($result)) {

			if ($yhtiorow['suuntalavat'] == 'S' and $edellinen_sscc != $row['sscc']) {
				$pdf->draw_text(185, 545, "SSCC-".t("koodi", $kieli), 					$firstpage, $pieni);
				$pdf->draw_text(185, 535, $edellinen_sscc, 								$firstpage, $p);

				$sivu++;
				$firstpage = alku_purku();
				$kala = 500;

				$pdf->draw_text(185, 545, "SSCC-".t("koodi", $kieli), 					$firstpage, $pieni);
				$pdf->draw_text(185, 535, $row['sscc'], 								$firstpage, $p);
			}
			elseif ($yhtiorow['viivakoodi_purkulistaan'] == 'K' and $mista != "vastaanota") {
				if ($kala < 50) {
					if ($yhtiorow['suuntalavat'] == 'S' and $edellinen_sscc == $row['sscc']) {
						$pdf->draw_text(185, 545, "SSCC-".t("koodi", $kieli), 					$firstpage, $pieni);
						$pdf->draw_text(185, 535, $row['sscc'], 								$firstpage, $p);
					}

					$sivu++;
					$firstpage = alku_purku();
					$kala = 500;

					if ($yhtiorow['suuntalavat'] == 'S') {
						$pdf->draw_text(185, 545, "SSCC-".t("koodi", $kieli), 					$firstpage, $pieni);
						$pdf->draw_text(185, 535, $row['sscc'], 								$firstpage, $p);
					}
				}
			}
			else {
				if ($kala < 50) {
					$sivu++;
					$firstpage = alku_purku();
					$kala = 500;
				}
			}

			$query = "	SELECT saldo, hyllyalue, hyllynro, hyllyvali, hyllytaso
						FROM tuotepaikat
						WHERE yhtio = '$kukarow[yhtio]'
						and tuoteno = '$row[tuoteno]'
						and oletus  = 'X'";
			$olesaldoresult = pupe_query($query);
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
			$hylresult = pupe_query($query);
			$hylrow = mysql_fetch_array($hylresult);

			$hyllyssa = $olesaldorow['saldo']-$hylrow['keratty'];

			$p["height"] = 10;
			$p["font"] = "Times-Roman";

			$pieni["height"] = 8;
			$pieni["font"] = "Times-Roman";

			$boldi["height"] = 8;
			$boldi["font"] = "Times-Bold";

			$b["height"] = 10;
			$b["font"] = "Times-Bold";

			$query = "	SELECT *
						FROM tuotepaikat
						WHERE yhtio   = '$kukarow[yhtio]'
						and tuoteno   = '$row[tuoteno]'
						and hyllyalue = '$row[hyllyalue]'
						and hyllynro  = '$row[hyllynro]'
						and hyllyvali = '$row[hyllyvali]'
						and hyllytaso = '$row[hyllytaso]'";
			$xolesaldoresult = pupe_query($query);

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
				$pdf->draw_text(30,  $kala, $row["toim_tuoteno"],	$firstpage, $b);
				$pdf->draw_text(30, $kala-10, $row["tuoteno"], 		$firstpage, $b);
			}

			if ($mista == "vastaanota") {
				$pohja = $pdf->draw_paragraph($kala+10, 155, 5, 315, t_tuotteen_avainsanat($row, 'nimitys'), $firstpage, $pieni);
			}
			else {
				$pohja = $pdf->draw_paragraph($kala+10, 280, 5, 440, t_tuotteen_avainsanat($row, 'nimitys'), $firstpage, $boldi);
			}

			$lisaa = 0;

			if ($pohja < $kala){
				$lisaa = $kala-$pohja;
			}

			if ($mista == "vastaanota") {

				$hyllyalue_value = $yhtiorow['varastontunniste'] != "" ? substr("{$row["trhyllyalue"]}", $yhtiorow['varastontunniste']) : $row["trhyllyalue"];

				$pdf->draw_text(315, $kala, $hyllyalue_value." ".$row["trhyllynro"]." ".$row["trhyllyvali"]." ".$row["trhyllytaso"],		$firstpage, $p);

				if ($vainlistaus != '') {

					$query = "	SELECT saldo, hyllyalue, hyllynro, hyllyvali, hyllytaso
								FROM tuotepaikat
								WHERE yhtio = '$kukarow[yhtio]'
								and tuoteno = '$row[tuoteno]'
								and hyllyalue = '".$t1[$row['tunnus']]."'
								and hyllynro  = '".$t2[$row['tunnus']]."'
								and hyllyvali = '".$t3[$row['tunnus']]."'
								and hyllytaso = '".$t4[$row['tunnus']]."'";
					$olesaldoresult = pupe_query($query);
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
					$hylresult = pupe_query($query);
					$hylrow = mysql_fetch_array($hylresult);

					$hyllyssa = $olesaldorow['saldo']-$hylrow['keratty'];

					$pdf->draw_text(475, $kala, $t1[$row['tunnus']]." ".$t2[$row['tunnus']]." ".$t3[$row['tunnus']]." ".$t4[$row['tunnus']]." (".$hyllyssa.") ",		$firstpage, $p);
				}
				else {

					$query = "	SELECT saldo, hyllyalue, hyllynro, hyllyvali, hyllytaso
								FROM tuotepaikat
								WHERE yhtio = '$kukarow[yhtio]'
								and tuoteno = '$row[tuoteno]'
								and hyllyalue = '$row[hyllyalue]'
								and hyllynro = '$row[hyllynro]'
								and hyllyvali = '$row[hyllyvali]'
								and hyllytaso = '$row[hyllytaso]'";
					$olesaldoresult = pupe_query($query);
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
					$hylresult = pupe_query($query);
					$hylrow = mysql_fetch_array($hylresult);

					$hyllyssa = $olesaldorow['saldo']-$hylrow['keratty'];

					$hyllyalue_value = $yhtiorow['varastontunniste'] != "" ? substr("{$row["hyllyalue"]}", $yhtiorow['varastontunniste']) : $row["hyllyalue"];

					$pdf->draw_text(475, $kala, $hyllyalue_value." ".$row["hyllynro"]." ".$row["hyllyvali"]." ".$row["hyllytaso"]." (".$hyllyssa.") ",		$firstpage, $p);
				}

			}
			else {

				if ($yhtiorow['suuntalavat'] == 'S') {
					$hyllyalue_value = $yhtiorow['varastontunniste'] != "" ? substr("{$olesaldorow["hyllyalue"]}", $yhtiorow['varastontunniste']) : $olesaldorow["hyllyalue"];

					$pdf->draw_text(440, $kala, $hyllyalue_value." ".$olesaldorow["hyllynro"]." ".$olesaldorow["hyllyvali"]." ".$olesaldorow["hyllytaso"]." (".$hyllyssa.") ",		$firstpage, $p);
				}
				else {

					$hyllyalue_value = $yhtiorow['varastontunniste'] != "" ? substr("{$row["hyllyalue"]}", $yhtiorow['varastontunniste']) : $row["hyllyalue"];

					$pdf->draw_text(440, $kala, $hyllyalue_value." ".$row["hyllynro"]." ".$row["hyllyvali"]." ".$row["hyllytaso"]." (".$hyllyssa.") ",		$firstpage, $p);
				}
			}


			if ($mista == "vastaanota") {
				$oikpos = $pdf->strlen((float) $row["varattu"], $p);
				$pdf->draw_text(745-$oikpos, $kala, (float) $row["varattu"], 		$firstpage, $p);
			}
			else {
				$oikpos = $pdf->strlen((float) $row["varattu"], $p);
				$pdf->draw_text(620-$oikpos, $kala, (float) $row["varattu"], 		$firstpage, $p);

				$oikpos = $pdf->strlen((float) $row["tuotekerroin"], $p);
				$pdf->draw_text(675-$oikpos, $kala, (float) $row["tuotekerroin"], 	$firstpage, $p);

				$oikpos = $pdf->strlen((float) $row["toimmaara"], $p);
				$pdf->draw_text(745-$oikpos, $kala, (float) $row["toimmaara"], 		$firstpage, $p);
			}


			$pdf->draw_text(770, $kala, t_avainsana("Y", $kieli, "and avainsana.selite='$row[yksikko]'", "", "", "selite"), 			$firstpage, $p);
			$apukala = $kala;

			$lisaa = 0;
			$kala = $kala - 25 - $lisaa;

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
		   		$sarjares = pupe_query($query);

				while ($sarjarow = mysql_fetch_array($sarjares)) {
					if ($row["sarjanumeroseuranta"] == "S") {
						$row["kommentti"] .= " \n".t("S:nro", $kieli).": ".$sarjarow["sarjanumero"];
					}
					elseif ($row["sarjanumeroseuranta"] == "E" or $row["sarjanumeroseuranta"] == "G") {
						$row["kommentti"] .= " \n".t("Er‰", $kieli).": ".$sarjarow["sarjanumero"];
					}
					elseif ($row["sarjanumeroseuranta"] == "F") {
						$row["kommentti"] .= " \n".t("Er‰", $kieli).": ".$sarjarow["sarjanumero"]." ".t("Parasta ennen", $kieli).": ".tv1dateconv($sarjarow["parasta_ennen"]);
					}
				}
			}

			if (trim($row['purkukommentti']) !="") {
				$row["kommentti"] .= " \n".$row['purkukommentti'];
			}

			if ($yhtiorow['viivakoodi_purkulistaan'] == 'K' and $mista != "vastaanota") {
				if (class_exists("Image_Barcode")) {
					//viivakoodi
					$nimi = "/tmp/".md5(uniqid(rand(),true)).".jpg";

					imagejpeg(Image_Barcode::draw($row["tuoteno"], 'code128', 'jpg', false, 1, 20), $nimi, 90);

					$fh = fopen($nimi, "r");
					$data = fread($fh, filesize($nimi));
					fclose($fh);

					$image = $pdf->jfif_embed($data);

					$apukala -= 33; // 23

					$pdf->image_place($image, $apukala, 21, $firstpage);
					system("rm -f $nimi");
					$kala -= 15;
				}
			}

			// katsotaan onko tuotetta j‰lkitoimituksessa
			$query = "	SELECT sum(varattu+jt) kpl
						FROM tilausrivi USE INDEX (yhtio_tyyppi_tuoteno_laskutettuaika)
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tyyppi = 'L'
						AND tuoteno = '{$row['tuoteno']}'
						AND laskutettuaika = '0000-00-00'
						AND var = 'J'";
			$jt_check_res = pupe_query($query);
			$jt_check_row = mysql_fetch_assoc($jt_check_res);

			if ($jt_check_row['kpl'] > 0) {
				$row['kommentti'] .= "\n".t("J‰lkitoimituksessa")." {$jt_check_row['kpl']} ".t_avainsana("Y", "", "and avainsana.selite='{$row["yksikko"]}'", "", "", "selite");
			}

			// Trimmataan uudestaan
			$row["kommentti"] = trim($row["kommentti"]);

			if ($row["kommentti"] != '') {
				$pohja = $pdf->draw_paragraph($kala+$p["height"]+5, 280, 5, 740, $row["kommentti"],	$firstpage, $p);
				$kala = $pohja - 10;
			}

			$kala -= 7;

			$edellinen_sscc = $row['sscc'];

			$sumarum += $row["varattu"];
			$total   += $row["hinta"];
		}

		// viivat rivien v‰liin...
		$x[0] = 20;
		$x[1] = 808;
		$y[0] = $y[1] = $kala + 15 - 4;
		$pdf->draw_line($x, $y, $firstpage, $rectparam);

		if ($mista != "vastaanota") {

			if ($kala < 50) {
				$sivu++;
				$firstpage = alku_purku();
				$kala = 500;
			}

			$sumarum = sprintf('%.2f',$sumarum);
			$pdf->draw_text(520,  $kala, t("Yhteens‰", $kieli),	$firstpage, $p);
			$pdf->draw_text(570,  $kala, $sumarum,	$firstpage, $p);


			$pdf->draw_rectangle(40, 20,  20, 200, $firstpage, $rectparam);
			$pdf->draw_rectangle(40, 200, 20, 808, $firstpage, $rectparam);
			$pdf->draw_rectangle(40, 500, 20, 808, $firstpage, $rectparam);
			$pdf->draw_text(30,   30, t("P‰iv‰m‰‰r‰ ja aika", $kieli),	$firstpage, $pieni);
			$pdf->draw_text(210,  30, t("Allekirjoitus", $kieli),	$firstpage, $pieni);
			$pdf->draw_text(504,  30, t("Nimenselvennys", $kieli),	$firstpage, $pieni);
		}

		print_pdf_purku();
	}

	//p‰‰dyt‰‰n taas selailemaan toimittajia
	if ($mista == "vastaanota") {
		echo t("Vastaanotetut lista tulostuu")."...<br>";
	}
	else {
		echo t("Purkulista tulostuu")."...<br>";
	}
