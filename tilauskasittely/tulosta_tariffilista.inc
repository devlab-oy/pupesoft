<?php
	//Ohjelma vatii muuttujan $otunnus joka viittaa ostolaskuun johon rivit on kohdistettu.
	// sek‰ $laskurow arrayn jossa on select t‰hti from lasku jossa tunnus on $otunnus

	require_once('../pdflib/phppdflib.class.php');

	$pdf = new pdffile;
	$pdf->set_default('margin', 0);
	$pdf->set_default('margin-left', 5);
	$rectparam["width"] = 0.3;

	if (!function_exists('alku_tariffi')) {
		function alku_tariffi () {
			global $pdf, $image, $laskurow, $yhtiorow, $kukarow, $sivu, $rectparam;

			$firstpage = $pdf->new_page("11.5x8in");

			$p["height"] = 10;
			$p["font"] = "Times-Roman";

			$pieni["height"] = 8;
			$pieni["font"] = "Times-Roman";


			$pdf->draw_rectangle(570, 20,  530, 808, $firstpage, $rectparam);
			$pdf->draw_text(30,  555, $yhtiorow["nimi"], $firstpage);
			$pdf->draw_text(250, 555, t("TARIFFILISTA"), $firstpage);
			$pdf->draw_text(550, 555, $laskurow["nimi"], $firstpage);

			$pdf->draw_text(30, 545, t("Sivu"), $firstpage, $pieni);
			$pdf->draw_text(30, 535, $sivu, $firstpage, $p);

			$pdf->draw_text(50,  545, t("Keikkanumero"),		$firstpage, $pieni);
			$pdf->draw_text(50,  535, $laskurow["laskunro"],	$firstpage, $p);

			$pdf->draw_text(110, 545, t("Ajoaika"), 			$firstpage, $pieni);
			$pdf->draw_text(110, 535, date("d-m-Y H:i"), 		$firstpage, $p);


			$pdf->draw_text(210, 545, t("Saapuneet laskut"), $firstpage, $pieni);
			$pdf->draw_text(210,  535, $laskurow["viesti"], $firstpage, $p);

			$pdf->draw_text(410, 545, t("Laskun p‰iv‰ykset"), $firstpage, $pieni);
			$pdf->draw_text(410, 535, $laskurow["tapvm"], $firstpage, $p);

			$pdf->draw_text(610, 545, t("Laskujen valuutta"), $firstpage, $pieni);
			$pdf->draw_text(610, 535, $laskurow["valkoodi"], $firstpage, $p);



			$pdf->draw_rectangle(530, 20,  510, 808, $firstpage, $rectparam);
			$pdf->draw_text(30,  520, t("Tuotekoodi"),			$firstpage, $pieni);
			$pdf->draw_text(155, 520, t("Toimittajan koodi"),	$firstpage, $pieni);
			$pdf->draw_text(270, 520, t("Nimitys"),			$firstpage, $pieni);
			$pdf->draw_text(420, 520, t("M‰‰r‰"),				$firstpage, $pieni);
			$pdf->draw_text(490, 520, t("Hinta"),				$firstpage, $pieni);
			$pdf->draw_text(560, 520, t("Rivihinta"),			$firstpage, $pieni);
			$pdf->draw_text(630, 520, t("Tavaranimike"),		$firstpage, $pieni);
			$pdf->draw_text(700, 520, t("Kohtelu"),			$firstpage, $pieni);
			$pdf->draw_text(750, 520, t("Am"),					$firstpage, $pieni);


			$pdf->draw_rectangle(530, 20, 10, 808, $firstpage, $rectparam);

			return($firstpage);
		}
	}
	if (!function_exists('print_pdf_tariffi')) {
		function print_pdf_tariffi () {
			global $pdf, $kukarow, $yhtiorow, $komento, $otunnus;

			//keksit‰‰n uudelle failille joku varmasti uniikki nimi:
			list($usec, $sec) = explode(' ', microtime());
			mt_srand((float) $sec + ((float) $usec * 100000));
			$pdffilenimi = "/tmp/Tariffilista-".md5(uniqid(mt_rand(), true)).".pdf";

			//kirjoitetaan pdf faili levylle..
			$fh = fopen($pdffilenimi, "w");
			if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus ep‰onnistui $pdffilenimi");
			fclose($fh);

			//itse print komento tulee valitse_tulosin.incilt‰...
			if ($komento["Tariffilista"] == 'email') {
				$liite = $pdffilenimi;
				$kutsu = "Tariffilista";

				require("../inc/sahkoposti.inc");
			}
			elseif ($komento["Tariffilista"] != '' and $komento["Tariffilista"] != 'edi') {
				$line = exec("$komento[Tariffilista] $pdffilenimi");
			}

			//poistetaan tmp file samantien kuleksimasta...
			system("rm -f $pdffilenimi");
		}
	}

    $query = "	SELECT tuotteen_toimittajat.toim_tuoteno, tuote.tuoteno, tuote.nimitys, tilausrivi.varattu+tilausrivi.kpl varattu, tilausrivi.hinta,
				round(if(tuotteen_toimittajat.tuotekerroin=0,1,tuotteen_toimittajat.tuotekerroin)*(tilausrivi.hinta*(tilausrivi.varattu+tilausrivi.kpl)), 2) rivihinta, tuote.tullinimike1, tuote.tullinimike2,
				tuote.tullikohtelu, tuotteen_toimittajat.alkuperamaa
				FROM tilausrivi
				JOIN tuote USING (yhtio, tuoteno)
				JOIN tuotteen_toimittajat ON tilausrivi.yhtio = tuotteen_toimittajat.yhtio and tilausrivi.tuoteno = tuotteen_toimittajat.tuoteno and tuotteen_toimittajat.liitostunnus = '$laskurow[liitostunnus]'
				WHERE tilausrivi.uusiotunnus = '$otunnus'
				and tilausrivi.yhtio='$kukarow[yhtio]'
				ORDER BY tilausrivi.toimaika, tuotteen_toimittajat.toim_tuoteno";
    $result = mysql_query($query) or pupe_error($query);

	$kala = 490;
	$lask = 1;
	$total = 0;
	$sivu = 1;

	$firstpage = alku_tariffi();

	while ($row = mysql_fetch_array($result)) {
		if ($lask == 41) {
			$sivu++;
			$firstpage = alku_tariffi();
			$kala = 490;
			$lask = 1;
		}

		$p["height"] = 10;
		$p["font"] = "Times-Roman";

		$pieni["height"] = 8;
		$pieni["font"] = "Times-Roman";

		$pdf->draw_text(30,  $kala, $row["tuoteno"],		$firstpage, $pieni);
		$pdf->draw_text(155, $kala, $row["toim_tuoteno"], 	$firstpage, $pieni);
		$pdf->draw_text(270, $kala, substr($row["nimitys"],0,25),		$firstpage, $pieni);
		$pdf->draw_text(420, $kala, $row["varattu"], 		$firstpage, $pieni);
		$pdf->draw_text(490, $kala, $row["hinta"], 			$firstpage, $pieni);
		$pdf->draw_text(560, $kala, $row["rivihinta"],		$firstpage, $pieni);
		$pdf->draw_text(630, $kala, $row["tullinimike1"].$row["tullinimike2"], 	$firstpage, $pieni);
		$pdf->draw_text(700, $kala, $row["tullikohtelu"], 	$firstpage, $pieni);
		$pdf->draw_text(750, $kala, $row["alkuperamaa"], 	$firstpage, $pieni);

		$kala = $kala - 12;
		$total += $row["hinta"];
		$lask++;
	}

	//kutsutaan lopuksi viel‰ print_pdf_tariffi funktiota
	print_pdf_tariffi();

	//p‰‰dyt‰‰n taas selailemaan toimittajia
	echo "".t("Tariffilista tulostuu")."...<br>";
?>
