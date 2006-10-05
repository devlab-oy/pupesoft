<?php

	//Ohjelma vatii muuttujan $otunnus joka viittaa ostolaskuun johon rivit on kohdistettu.
	// sek‰ $laskurow arrayn jossa on select t‰hti from lasku jossa tunnus on $otunnus

	require_once('../pdflib/phppdflib.class.php');

	$pdf = new pdffile;
	$pdf->set_default('margin', 0);
	$pdf->set_default('margin-left', 5);
	$rectparam["width"] = 0.3;

	if (!function_exists('alku_purku')) {
		function alku_purku () {
			global $pdf, $image, $laskurow, $yhtiorow, $kukarow, $sivu, $rectparam;

			$firstpage = $pdf->new_page("11.5x8in");
			$pdf->enable('template');
			$tid = $pdf->template->create();
			$pdf->template->size($tid, 600, 830);

			$p["height"] = 10;
			$p["font"] = "Times-Roman";

			$pieni["height"] = 8;
			$pieni["font"] = "Times-Roman";


			$pdf->draw_rectangle(570, 20,  530, 808, $firstpage, $rectparam);
			$pdf->draw_text(30,  555, $yhtiorow["nimi"], 		$firstpage);
			$pdf->draw_text(250, 555, t("PURKULISTA"), 			$firstpage);
			$pdf->draw_text(550, 555, $laskurow["nimi"], 		$firstpage);

			$pdf->draw_text(30, 545, t("Sivu"), $firstpage, $pieni);
			$pdf->draw_text(30, 535, $sivu, $firstpage, $p);

			$pdf->draw_text(50,  545, t("Keikkanumero"),		$firstpage, $pieni);
			$pdf->draw_text(50,  535, $laskurow["laskunro"],	$firstpage, $p);

			$pdf->draw_text(110, 545, t("Ajoaika"), 			$firstpage, $pieni);
			$pdf->draw_text(110, 535, date("d-m-Y H:i"), 		$firstpage, $p);

			$pdf->draw_text(210, 545, t("Kommentti"), 			$firstpage, $pieni);
			$pdf->draw_text(210, 535, $laskurow["comments"],	$firstpage, $p);



			$pdf->draw_rectangle(530, 20,  510, 808, $firstpage, $rectparam);
			$pdf->draw_text(30,  520, t("Toimittajan koodi"),	$firstpage, $pieni);
			$pdf->draw_text(155, 520, t("Tuotekoodi"),			$firstpage, $pieni);
			$pdf->draw_text(280, 520, t("Nimitys"),				$firstpage, $pieni);
			$pdf->draw_text(440, 520, t("Paikka(o.saldo)"),		$firstpage, $pieni);
			$pdf->draw_text(520, 520, t("Yksikkˆ"),				$firstpage, $pieni);
			$pdf->draw_text(570, 520, t("M‰‰r‰"),				$firstpage, $pieni);
			$pdf->draw_text(620, 520, t("Kerroin"),				$firstpage, $pieni);
			$pdf->draw_text(675, 520, t("Toim.M‰‰r‰"),			$firstpage, $pieni);
			$pdf->draw_text(750, 520, t("Ostotilaus"),			$firstpage, $pieni);

			$pdf->draw_rectangle(530, 20, 10, 808, $firstpage, $rectparam);

			return($firstpage);
		}
	}

	if (!function_exists('print_pdf_purku')) {
		function print_pdf_purku () {
			global $pdf, $kukarow, $yhtiorow, $komento, $otunnus, $tee;

			//keksit‰‰n uudelle failille joku varmasti uniikki nimi:
			list($usec, $sec) = explode(' ', microtime());
			mt_srand((float) $sec + ((float) $usec * 100000));
			$pdffilenimi = "/tmp/Purkulista-".md5(uniqid(mt_rand(), true)).".pdf";

			//kirjoitetaan pdf faili levylle..
			$fh = fopen($pdffilenimi, "w");
			if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF Error $pdffilenimi");
			fclose($fh);

			//itse print komento tulee valitse_tulosin.incilt‰...
			if ($komento["Purkulista"] == 'email') {
				$liite = $pdffilenimi;
				$kutsu = "Purkulista";

				require("../inc/sahkoposti.inc");
				echo t("Tilaus tulostuu")."...<br>";
			}
			elseif ($tee == 'NAYTATILAUS') {
    			//Tyˆnnet‰‰n tuo pdf vaan putkeen!
    			echo file_get_contents($pdffilenimi);
    		}
			elseif ($komento["Purkulista"] != '' and $komento["Purkulista"] != 'edi') {
				$line = exec("$komento[Purkulista] $pdffilenimi");
			}

			//poistetaan tmp file samantien kuleksimasta...
			system("rm -f $pdffilenimi");
		}
	}

	if ($sorttaus == 'paikka') {
		$orde = " ORDER BY hyllyalue, hyllynro, hyllyvali, hyllytaso ";
	}
	elseif ($sorttaus == 'tuote') {
		$orde = " ORDER BY tuote.tuoteno ";
	}
	else {
		$orde = " ORDER BY tuotteen_toimittajat.toim_tuoteno ";
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

	if ($mista== 'tulostakopio') {
		$query = "	SELECT laskunro, ytunnus
					FROM lasku
					WHERE tunnus = '$otunnus' and yhtio='$kukarow[yhtio]' and tila = 'K' limit 1";
		$laskresult = mysql_query($query) or pupe_error($query);
		$laskrow = mysql_fetch_array($laskresult);

		$query = "	SELECT tuotteen_toimittajat.toim_tuoteno, tuote.tuoteno, hyllyalue, hyllynro, hyllyvali,
					hyllytaso, tilausrivi.yksikko, tilausrivi.varattu+tilausrivi.kpl varattu, tilausrivi.hinta, tuotteen_toimittajat.tuotekerroin,
					round(tuotteen_toimittajat.tuotekerroin*(tilausrivi.varattu+tilausrivi.kpl),2) toimmaara, tuote.nimitys
					FROM lasku
					JOIN tilausrivi ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.uusiotunnus $mitkalisa
					JOIN tuote ON lasku.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
					JOIN tuotteen_toimittajat ON lasku.yhtio = tuotteen_toimittajat.yhtio and tilausrivi.tuoteno = tuotteen_toimittajat.tuoteno and tuotteen_toimittajat.liitostunnus = '$laskurow[liitostunnus]'
					WHERE lasku.laskunro = '$laskrow[laskunro]'
					and tila = 'K'
					and vanhatunnus = 0
					and lasku.yhtio='$kukarow[yhtio]'
					$orde";
	}
	else {
		$query = "	SELECT tuotteen_toimittajat.toim_tuoteno, tuote.tuoteno, hyllyalue, hyllynro, hyllyvali,
					hyllytaso, tilausrivi.yksikko, tilausrivi.varattu+tilausrivi.kpl varattu, tilausrivi.hinta, tuotteen_toimittajat.tuotekerroin,
					round(tuotteen_toimittajat.tuotekerroin*(tilausrivi.varattu+tilausrivi.kpl),2) toimmaara, tuote.nimitys
					FROM tilausrivi
					JOIN tuote USING (yhtio, tuoteno)
					JOIN tuotteen_toimittajat ON tilausrivi.yhtio = tuotteen_toimittajat.yhtio and tilausrivi.tuoteno = tuotteen_toimittajat.tuoteno and tuotteen_toimittajat.liitostunnus = '$laskurow[liitostunnus]'
					WHERE tilausrivi.uusiotunnus = '$otunnus'
					and tilausrivi.yhtio='$kukarow[yhtio]'
					$mitkalisa
					$orde";
	}

	$result = mysql_query($query) or pupe_error($query);

	$kala = 490;
	$total = 0;
	$sivu = 1;
	
	$sumarum = 0;

	$firstpage = alku_purku();

	while ($row = mysql_fetch_array($result)) {
		if ($kala < 25) {
			$sivu++;
			$firstpage = alku_purku();
			$kala = 490;
		}

		$query = "SELECT saldo, hyllyalue, hyllynro, hyllyvali, hyllytaso FROM tuotepaikat WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$row[tuoteno]' and oletus = 'X'";
		$olesaldoresult = mysql_query($query) or pupe_error($query);
		$olesaldorow=mysql_fetch_array($olesaldoresult);

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

		$query = "SELECT * FROM tuotepaikat WHERE yhtio='$kukarow[yhtio]' and tuoteno='$row[tuoteno]' and hyllyalue='$row[hyllyalue]' and hyllynro='$row[hyllynro]' and hyllyvali='$row[hyllyvali]' and hyllytaso='$row[hyllytaso]'";
		$xolesaldoresult = mysql_query($query) or pupe_error($query);

		// tuotepaikoista ei lˆydy rivill‰ olevaa varastopaikkaa.. silloin ei echota mit‰‰n paikkaa
		if (mysql_num_rows($xolesaldoresult) == 0) {
			$row["hyllyalue"] = "";
			$row["hyllynro"]  = "";
			$row["hyllyvali"] = "";
			$row["hyllytaso"] = "";
		}

		$pdf->draw_text(30,  $kala, $row["toim_tuoteno"],	$firstpage, $p);
		$pdf->draw_text(155, $kala, $row["tuoteno"], 		$firstpage, $p);
		$pohja = $pdf->draw_paragraph($kala+10, 280, 5, 440, $row["nimitys"], $firstpage, $pieni);
		$lisaa = 0;
		
		if ($pohja < $kala){
			$lisaa = $kala-$pohja;
		}
		
		$pdf->draw_text(440, $kala, $row["hyllyalue"]." ".$row["hyllynro"]." ".$row["hyllyvali"]." ".$row["hyllytaso"]." (".$hyllyssa.") ",		$firstpage, $p);
		$pdf->draw_text(520, $kala, $row["yksikko"], 		$firstpage, $p);
		$pdf->draw_text(570, $kala, $row["varattu"], 		$firstpage, $p);
		$pdf->draw_text(620, $kala, $row["tuotekerroin"],	$firstpage, $p);
		$pdf->draw_text(675, $kala, $row["toimmaara"], 		$firstpage, $p);
		$pdf->draw_text(750, $kala, $row["otunnus"], 		$firstpage, $p);
		
		$sumarum += $row["varattu"];

		// viivat rivien v‰liin...
		$x[0] = 20;
		$x[1] = 808;
		$y[0] = $y[1] = $kala+$rivinkorkeus-4-$lisaa;
		$pdf->draw_line($x, $y, $firstpage, $rectparam);

		$kala = $kala - 15 - $lisaa;
		$total += $row["hinta"];
	}
    //Nyt tehd‰‰n vikaks boksit johon purkaja voi laittaa ajan ja nimen
	if ($kala < 95) {
		$sivu++;
		$firstpage = alku_purku();
		$kala = 490;
	}
	if ($kala < 490) {
        $kala = $kala - 10;
	}
	
	$kalasum = $kala + 11;
	$sumarum = sprintf('%.2f',$sumarum);
	$pdf->draw_text(520,  $kalasum, t("Yhteens‰"),	$firstpage, $p);
	$pdf->draw_text(570,  $kalasum, $sumarum,	$firstpage, $p);
	
	$kala = $kala + 8;
	$kala2 = $kala - 50;
	$pdf->draw_rectangle($kala, 20, $kala2, 200, $firstpage, $rectparam);
	$pdf->draw_rectangle($kala, 200, $kala2, 808, $firstpage, $rectparam);
	$kala = $kala - 8;
	$pdf->draw_text(30,  $kala, t("P‰iv‰m‰‰r‰ ja aika"),	$firstpage, $pieni);
	$pdf->draw_text(210,  $kala, t("Allekirjoitus"),	$firstpage, $pieni);
	$pdf->draw_text(504,  $kala, t("Nimenselvennys"),	$firstpage, $pieni);

	//kutsutaan lopuksi viel‰ print_pdf_purku funktiota
	print_pdf_purku();

	//p‰‰dyt‰‰n taas selailemaan toimittajia
	echo t("Purkulista tulostuu")."...<br>";
?>
