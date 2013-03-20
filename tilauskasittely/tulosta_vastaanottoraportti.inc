<?php

	// Ohjelma vatii muuttujan $otunnus joka viittaa saapumiseen johon rivit on kohdistettu.
	// sekä $laskurow arrayn jossa on keikka jossa tunnus on $otunnus
	require_once('pdflib/phppdflib.class.php');

	$select_lisa = "";
	$join_lisat  = "";
    $where_lisa  = "";

	$norm["height"] 	  = 10;
	$norm["font"] 		  = "Helvetica";

	$pieni["height"] 	  = 8;
	$pieni["font"] 		  = "Helvetica";

	$pieniboldi["height"] = 8;
	$pieniboldi["font"]	  = "Helvetica-Bold";

	$boldi2["height"] 	  = 14;
	$boldi2["font"]		  = "Helvetica-Bold";

	$rectparam["width"]   = 1;
	$rectparam2["width"]  = 0.5;

	$pdf = new pdffile;
	$pdf->set_default('margin-top', 	0);
	$pdf->set_default('margin-bottom', 	0);
	$pdf->set_default('margin-left', 	0);
	$pdf->set_default('margin-right', 	0);

	if (!function_exists('alku_vastaanotto')) {
		function alku_vastaanotto () {
			global $pdf, $laskurow, $yhtiorow, $kukarow, $sivu, $rectparam, $kieli, $norm, $pieni, $boldi2, $rectparam, $rectparam2, $pieniboldi;

			$firstpage = $pdf->new_page("a4");

 			$pdf->draw_text(310, 815, t("Vastaanottoraportti", $kieli), $firstpage, $boldi2);

			tulosta_logo_pdf($pdf, $firstpage, $laskurow);

			$pdf->draw_rectangle(785, 310, 765, 580, $firstpage, $rectparam);
 			$pdf->draw_text(315, 778, t("Saapuminen", $kieli), $firstpage, $pieni);
 			$pdf->draw_text(315, 768, $laskurow['laskunro'], $firstpage, $norm);

			$pdf->draw_rectangle(785, 445, 765, 445, $firstpage, $rectparam);
 			$pdf->draw_text(450, 778, t("Henkilö", $kieli), $firstpage, $pieni);
 			$pdf->draw_text(450, 768, $kukarow['nimi'], $firstpage, $norm);

			$pdf->draw_rectangle(765, 310, 745, 580, $firstpage, $rectparam);
 			$pdf->draw_text(315, 758, t("Toimittaja", $kieli), $firstpage, $pieni);
 			$pdf->draw_text(315, 748, $laskurow['nimi'].' '.$laskurow['nimitark'], $firstpage, $norm);

			$pdf->draw_rectangle(725, 15, 705, 580, $firstpage, $rectparam2);
 			$pdf->draw_text(20, 716, t("SSCC"), $firstpage, $pieniboldi);
 			$pdf->draw_text(20, 708, t("Keräysvyöhyke"), $firstpage, $pieniboldi);
 			$pdf->draw_text(100, 716, t("Paikka"), $firstpage, $pieniboldi);
 			$pdf->draw_text(175, 716, t("Tuoteno"), $firstpage, $pieniboldi);
 			$pdf->draw_text(175, 708, t("Nimitys"), $firstpage, $pieniboldi);

			if ($yhtiorow["vastaanottoraportti"] == "K") {
				$oikpos = $pdf->strlen(t("Saldo"), $pieniboldi);
				$pdf->draw_text(420-$oikpos, 716, t("Saldo"), $firstpage, $pieniboldi);
				$oikpos = $pdf->strlen(t("Vanha hinta"), $pieniboldi);
				$pdf->draw_text(505-$oikpos, 716, t("Vanha hinta"), $firstpage, $pieniboldi);
				$oikpos = $pdf->strlen(t("Uusi hinta"), $pieniboldi);
				$pdf->draw_text(505-$oikpos, 708, t("Uusi hinta"), $firstpage, $pieniboldi);
				$oikpos = $pdf->strlen(t("Tilattu"), $pieniboldi);
	 			$pdf->draw_text(565-$oikpos, 716, t("Tilattu"), $firstpage, $pieniboldi);
				$oikpos = $pdf->strlen(t("Saavutettu"), $pieniboldi);
	 			$pdf->draw_text(565-$oikpos, 708, t("Saavutettu"), $firstpage, $pieniboldi);
			}
			else {
	 			$pdf->draw_text(350, 716, t("Toimittajan tuoteno"), $firstpage, $pieniboldi);
	 			$pdf->draw_text(465, 716, t("Tilattu"), $firstpage, $pieniboldi);
	 			$pdf->draw_text(520, 716, t("Saavutettu"), $firstpage, $pieniboldi);
			}
			return($firstpage);
		}
	}

	if (!function_exists('print_pdf_vastaanotto')) {
		function print_pdf_vastaanotto () {
			global $pdf, $kukarow, $yhtiorow, $komento, $tee;

			//keksitään uudelle failille joku varmasti uniikki nimi:
			list($usec, $sec) = explode(' ', microtime());
			mt_srand((float) $sec + ((float) $usec * 100000));

			$pdffilenimi = "/tmp/Vastaanottoraportti-".md5(uniqid(mt_rand(), true)).".pdf";

			//kirjoitetaan pdf faili levylle..
			$fh = fopen($pdffilenimi, "w");
			if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF Error $pdffilenimi");
			fclose($fh);

			//itse print komento tulee valitse_tulosin.inciltä...
			if ($komento["Vastaanottoraportti"] == 'email') {
				$liite = $pdffilenimi;

				$kutsu = "Vastaanottoraportti";

				if (@include("../inc/sahkoposti.inc"));
				elseif (@include("inc/sahkoposti.inc"));
				else include("sahkoposti.inc");

				echo t("Vastaanottoraportti tulostuu"),"...<br>";
			}
			elseif ($tee == 'NAYTATILAUS') {
    			//Työnnetään tuo pdf vaan putkeen!
    			echo file_get_contents($pdffilenimi);
    		}
			elseif ($komento["Vastaanottoraportti"] != '' and $komento["Vastaanottoraportti"] != 'edi') {
				$line = exec("$komento[Vastaanottoraportti] $pdffilenimi");

				echo t("Vastaanottoraportti tulostuu"),"...<br>";
			}

			//poistetaan tmp file samantien kuleksimasta...
			system("rm -f $pdffilenimi");
		}
	}

	if ($yhtiorow["suuntalavat"] == "S") {
		$select_lisa = ", suuntalavat.sscc,	keraysvyohyke.nimitys AS keraysvyohyke_nimitys";
		$join_lisat	 = " JOIN suuntalavat ON (suuntalavat.yhtio = tilausrivi.yhtio AND suuntalavat.tila = 'P' AND suuntalavat.tunnus = tilausrivi.suuntalava)
						 JOIN suuntalavat_saapuminen ON (suuntalavat_saapuminen.yhtio = suuntalavat.yhtio AND suuntalavat_saapuminen.suuntalava = suuntalavat.tunnus AND suuntalavat_saapuminen.saapuminen = '{$otunnus}')
						 JOIN keraysvyohyke ON (keraysvyohyke.yhtio = suuntalavat.yhtio AND keraysvyohyke.tunnus = suuntalavat.keraysvyohyke)";
		$where_lisa	 = " AND tilausrivi.suuntalava != 0";
	}


	$query = "	SELECT tilausrivi.*,
				tuotteen_toimittajat.toim_tuoteno,
				tilausrivi.varattu+tilausrivi.kpl siskpl
				{$select_lisa}
				FROM tilausrivi
				{$join_lisat}
				JOIN tuote use index (tuoteno_index) ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
				LEFT JOIN tuotteen_toimittajat use index (yhtio_tuoteno) ON (tuotteen_toimittajat.yhtio = tuote.yhtio AND tuotteen_toimittajat.tuoteno = tuote.tuoteno AND tuotteen_toimittajat.liitostunnus = '{$laskurow['liitostunnus']}')
				WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
				AND tilausrivi.uusiotunnus = '{$otunnus}'
				AND tilausrivi.tyyppi = 'O'
				AND tilausrivi.kpl != 0
				{$where_lisa}";
	$vastaanottoraportti_result = pupe_query($query);

	$firstpage = alku_vastaanotto();
	$kala = 685;

	while ($row = mysql_fetch_assoc($vastaanottoraportti_result)) {

		if ($kala <= 40) {
			$pdf->draw_rectangle(695, 15, $kala+10, 580, $firstpage, $rectparam2);

			$firstpage = alku_vastaanotto();
			$kala = 685;
		}

		$pdf->draw_text(20, $kala, $row['sscc'], $firstpage, $pieni);
		$pdf->draw_text(20, $kala-10, $row['keraysvyohyke_nimitys'], $firstpage, $pieni);
		$pdf->draw_text(100, $kala, $row['hyllyalue'].' '.$row['hyllynro'].' '.$row['hyllyvali'].' '.$row['hyllytaso'], $firstpage, $pieni);
		$pdf->draw_text(175, $kala, $row['tuoteno'], $firstpage, $pieni);
		$pdf->draw_text(175, $kala-10, $row['nimitys'], $firstpage, $pieni);

		if ($yhtiorow["vastaanottoraportti"] == "K") {

			// haetaan tapahtumalta uusihinta ja aika
			$query1 = "	SELECT hinta, laadittu
						FROM tapahtuma
						WHERE yhtio = '{$kukarow["yhtio"]}'
						and tuoteno = '{$row["tuoteno"]}'
						and laji = 'tulo'
						and rivitunnus = '{$row["tunnus"]}'
						ORDER BY laadittu desc
						LIMIT 1";
			$result = pupe_query($query1);
			$uusirow = mysql_fetch_assoc($result);

			// haetaan tuotteelle vanhahinta
			$query2 = "	SELECT hinta
						FROM tapahtuma
						WHERE yhtio = '{$kukarow["yhtio"]}'
						and tuoteno = '{$row["tuoteno"]}'
						and laadittu < '{$uusirow["laadittu"]}'
						and laji = 'tulo'
						ORDER BY laadittu desc
						LIMIT 1";
			$result = pupe_query($query2);
			$vanharow = mysql_fetch_assoc($result);

			if ($laskurow["mapvm"] == 0000-00-00) {
				$nmerkki = "~ ";
			}
			else {
				$nmerkki = "";
			}

			if ($vanharow["hinta"] == "") {
				$vanharow["hinta"] = "0.00";
			}

			list(,, $varastosaldo) = saldo_myytavissa($row["tuoteno"], '', '', '', '', '', '', '', '', '');

			$oikpos = $pdf->strlen($varastosaldo, $pieni);
			$pdf->draw_text(420-$oikpos, $kala, $varastosaldo, $firstpage, $pieni);

			$oikpos = $pdf->strlen($vanharow["hinta"], $pieni);
			$pdf->draw_text(505-$oikpos, $kala, $vanharow["hinta"], $firstpage, $pieni);

			$oikpos = $pdf->strlen($nmerkki.$uusirow["hinta"], $pieni);
			$pdf->draw_text(505-$oikpos, $kala-8, $nmerkki.$uusirow["hinta"], $firstpage, $pieni);

			$siskpl = $row['siskpl'].' '.t_avainsana("Y", $kieli, "and avainsana.selite='$row[yksikko]'", "", "", "selite");
			$kpl = $row['kpl'].' '.t_avainsana("Y", $kieli, "and avainsana.selite='$row[yksikko]'", "", "", "selite");

			$oikpos = $pdf->strlen($siskpl, $pieni);
			$pdf->draw_text(565-$oikpos, $kala, $siskpl, $firstpage, $pieni);

			$oikpos = $pdf->strlen($kpl, $pieni);
			$pdf->draw_text(565-$oikpos, $kala-8, $kpl, $firstpage, $pieni);

		}
		else {
			$pdf->draw_text(350, $kala, $row['toim_tuoteno'], $firstpage, $pieni);
			$siskpl = $row['siskpl'].' '.t_avainsana("Y", $kieli, "and avainsana.selite='$row[yksikko]'", "", "", "selite");
			$kpl = $row['kpl'].' '.t_avainsana("Y", $kieli, "and avainsana.selite='$row[yksikko]'", "", "", "selite");

			$pdf->draw_text(465, $kala, $siskpl, $firstpage, $pieni);
			$pdf->draw_text(520, $kala, $kpl, $firstpage, $pieni);

		}

		$pdf->draw_rectangle($kala-15, 15, $kala-15, 580, $firstpage, $rectparam2);

		$kala -= 25;
	}

	if ($kala < 685) $pdf->draw_rectangle(695, 15, $kala+10, 580, $firstpage, $rectparam2);

	print_pdf_vastaanotto();

?>