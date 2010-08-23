<?php

	if (@include_once("pdflib/phppdflib.class.php")) {
	}
	else {
		include_once("phppdflib.class.php");
	}

	$query = "	SELECT *
				FROM lasku
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tunnus = '{$tunnus}'";
	$laskures = mysql_query($query) or pupe_error($query);
	$laskurow = mysql_fetch_assoc($laskures);

	if ($kieli == '') {
		$querykiel = "SELECT kieli FROM asiakas WHERE yhtio = '{$kukarow['yhtio']}' and ytunnus = '{$laskurow['ytunnus']}'";
		$kielresult = mysql_query($querykiel) or pupe_error($querykiel);
		$kielnum = mysql_num_rows($kielresult);
		$kielrow = mysql_fetch_assoc($kielresult);
		$kieli = strtolower($kielrow['kieli']);
	}

	$norm["height"] 		= 10;
	$norm["font"] 			= "Times-Roman";

	$pieni["height"] 		= 8;
	$pieni["font"] 			= "Times-Roman";

	$boldi["height"] 		= 10;
	$boldi["font"] 			= "Times-Bold";

	$pieni_boldi["height"] 	= 8;
	$pieni_boldi["font"] 	= "Times-Bold";

	$iso["height"] 			= 14;
	$iso["font"] 			= "Helvetica-Bold";

	$rectparam["width"] 	= 0.3;

	if (!function_exists('tulosta_maksusopimus')) {
		function tulosta_maksusopimus($kukarow, $yhtiorow, $laskurow, $kieli) {
			global $norm, $pieni, $boldi, $pieni_boldi, $rectparam;

			$pdf = new pdffile;

			$pdf->set_default('margin-top',   20);
			$pdf->set_default('margin-bottom', 0);
			$pdf->set_default('margin-left',   0);
			$pdf->set_default('margin-right',  0);

			$page = $pdf->new_page("a4");

			tulosta_logo_pdf($pdf, $page, $laskurow);

			$query = "	SELECT osuus, summa, lisatiedot, ohje, erpcm 
						FROM maksupositio
						WHERE yhtio = '{$kukarow['yhtio']}' and otunnus = '{$laskurow['jaksotettu']}' 
						ORDER BY erpcm ASC";
			$result = mysql_query($query) or pupe_error($query);

			$otsikko_len = $pdf->strlen(t("Tilauksen {$laskurow['tunnus']} maksupositiot", $kieli), $iso);
			$pdf->draw_text((595 / 2) - ($otsikko_len / 2), 750, t("Tilauksen", $kieli)." {$laskurow['tunnus']} ".t("maksupositiot", $kieli), $page, $iso);

			$pdf->draw_rectangle(713, 25, 695, 565, $page, $rectparam);

			$pdf->draw_text(30, 700, t("Eränro", $kieli), $page, $boldi);
			$pdf->draw_text(100, 700, t("Summa", $kieli), $page, $boldi);
			$pdf->draw_text(160, 700, t("Osuus", $kieli), $page, $boldi);
			$pdf->draw_text(210, 700, t("Lisätiedot", $kieli), $page, $boldi);
			$pdf->draw_text(390, 700, t("Ohje", $kieli), $page, $boldi);

			$i = 1;

			$kala = 680;
			$rivikorkeus = 15;

			while ($row = mysql_fetch_assoc($result)) {

				if ($kala < 150) {
					$page = $pdf->new_page("a4");

					tulosta_logo_pdf($pdf, $page, $laskurow);

					$otsikko_len = $pdf->strlen(t("Tilauksen {$laskurow['tunnus']} maksupositiot", $kieli), $iso);
					$pdf->draw_text((595 / 2) - ($otsikko_len / 2), 750, t("Tilauksen", $kieli)." {$laskurow['tunnus']} ".t("maksupositiot", $kieli), $page, $iso);

					$pdf->draw_rectangle(713, 25, 695, 565, $page, $rectparam);

					$pdf->draw_text(30, 700, t("Eränro", $kieli), $page, $boldi);
					$pdf->draw_text(100, 700, t("Summa", $kieli), $page, $boldi);
					$pdf->draw_text(160, 700, t("Osuus", $kieli), $page, $boldi);
					$pdf->draw_text(210, 700, t("Lisätiedot", $kieli), $page, $boldi);
					$pdf->draw_text(390, 700, t("Ohje", $kieli), $page, $boldi);

					$kala = 680;
				}

				$pdf->draw_text(30, $kala, $i.'. '.tv1dateconv($row['erpcm']), $page, $norm);
				$pdf->draw_text(100, $kala, $row['summa'].' '.$laskurow['valkoodi'], $page, $norm);
				$pdf->draw_text(160, $kala, round($row['osuus'], 2).' %', $page, $norm);

				$valekala = $kala;

				if ($pdf->strlen($row['lisatiedot'], $norm) > 175) {
					$pohja = $pdf->draw_paragraph($kala+$norm['height']+2, 210, 60, 385, $row["lisatiedot"], $page, $norm);			    
					$valekala = $pohja - $rivikorkeus;
				}
				else {
					$pdf->draw_text(210, $kala, $row['lisatiedot'], $page, $norm);
				}

				if ($pdf->strlen($row['ohje'], $norm) > 170) {
					$pohja = $pdf->draw_paragraph($kala+$norm['height']+2, 390, 60, 560, $row["ohje"], $page, $norm);

					$kala = $valekala < ($pohja - $rivikorkeus) ? $valekala : ($pohja - $rivikorkeus);
				}
				else {
					$pdf->draw_text(390, $kala, $row['ohje'], $page, $norm);
					$kala -= $rivikorkeus;
				}

				$x[0] = 30;
				$x[1] = 560;
				$y[0] = $y[1] = $kala + $rivikorkeus - 4;

				$pdf->draw_line($x, $y, $page, $rectparam);

				$kala -= 5;

				$i++;
			}

			$kala -= 30;

			$pdf->draw_text(100, $kala, t("Asiakkaan allekirjoitus", $kieli), $page, $boldi);

			$x[0] = 100;
			$x[1] = 250;
			$y[0] = $y[1] = $kala - 30;
			$pdf->draw_line($x, $y, $page, $rectparam);

			$pdf->draw_text(330, $kala, t("Myyjän allekirjoitus", $kieli), $page, $boldi);

			$x[0] = 330;
			$x[1] = 480;
			$y[0] = $y[1] = $kala - 30;
			$pdf->draw_line($x, $y, $page, $rectparam);

			//keksitään uudelle failille joku varmasti uniikki nimi:
			list($usec, $sec) = explode(' ', microtime());
			mt_srand((float) $sec + ((float) $usec * 100000));
			$pdf_filenimi = "/tmp/Maksusopimus-".md5(uniqid(mt_rand(), true)).".pdf";

			//kirjoitetaan pdf faili levylle..
			$fh = fopen($pdf_filenimi, "w");
			if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF create error $pdf_filenimi");
			fclose($fh);

			echo file_get_contents($pdf_filenimi);

			//poistetaan tmp file samantien kuleksimasta...
			system("rm -f $pdf_filenimi");
		}
	}

?>