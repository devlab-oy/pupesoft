<?php

	require_once("pdflib/phppdflib.class.php");

	unset($pdf);

	$rectparam["width"] = 0.3;

	//fontit
	$norm["height"] = 6;
	$norm["font"] 	= "Times-Roman";

	$kirj["height"] = 8;
	$kirj["font"] 	= "Times-Bold";

	$iso["height"]	= 12;
	$iso["font"] 	= "Helvetica-Bold";

	$huge["height"] = 22;
	$huge["font"] 	= "Helvetica-Bold";

	if (!function_exists('uusi_sivu_erittely')) {
		function uusi_sivu_erittely ($laskurow) {
			global $yhtiorow, $kukarow, $pdf, $kieli;

			if (!isset($pdf)) {
				//PDF parametrit
				$pdf = new pdffile;
				$pdf->set_default('margin-top', 	0);
				$pdf->set_default('margin-bottom', 	0);
				$pdf->set_default('margin-left', 	0);
				$pdf->set_default('margin-right', 	0);
			}

			// tehdään pdf:n uusi sivu
			$firstpage = $pdf->new_page("a4");
			tulosta_logo_pdf($pdf, $firstpage, $laskurow);

			$pdf->draw_text(302, 812, "RAHTIKIRJAERITTELY", $firstpage, $iso);

			return array(780, $firstpage);
		}
	}

	if (!function_exists('rivi_erittely')) {
		function rivi_erittely ($tilaukset, $uusisivu, $kala, $firstpage) {
			global $yhtiorow, $kukarow, $pdf, $kieli, $rectparam, $norm, $kirj, $iso;

			// Otetaan ekan tilauksen tiedot
			$query = "	SELECT *
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]'
						AND tunnus in ($tilaukset)
						LIMIT 1";
			$result = mysql_query($query) or pupe_error($query);
			$laskurow = mysql_fetch_assoc($result);

			if (!isset($pdf) or $uusisivu or $kala < 100) list($kala, $firstpage) = uusi_sivu_erittely($laskurow);

		   	$pdf->draw_rectangle($kala, 60, $kala-50, 540, $firstpage, $rectparam);
		   	$pdf->draw_text(62, $kala-8, t("Asiakas", $kieli),	$firstpage, $norm);
		   	$pdf->draw_text(62, $kala-18, t("Ytunnus", $kieli)." ".$laskurow['ytunnus'],	$firstpage, $kirj);
		   	$pdf->draw_text(62, $kala-28, $laskurow['nimi']." ".$laskurow['nimitark'],		$firstpage, $kirj);
		   	$pdf->draw_text(62, $kala-38, $laskurow['osoite'],	$firstpage, $kirj);
		   	$pdf->draw_text(62, $kala-48, $laskurow['postino']." ".$laskurow['postitp'],	$firstpage, $kirj);

			$oikpos = $pdf->strlen(t("Allekirjoitus ja nimenselvennys", $kieli), $norm);
			$pdf->draw_text(530-$oikpos, $kala-45, t("Allekirjoitus ja nimenselvennys", $kieli), $firstpage, $norm);

			$kala -= 50;

			//kollitietot
			//ylärivi
			$pdf->draw_rectangle($kala, 60, $kala-15, 242, $firstpage, $rectparam);
			$pdf->draw_text(62, $kala-8, t("Tilausnumerot", $kieli),	$firstpage, $norm);
			$pdf->draw_rectangle($kala, 242, $kala-15, 415,	$firstpage, $rectparam);
			$pdf->draw_text(244, $kala-8, t("Kolliluku ja -laji", $kieli), $firstpage, $norm);
			$pdf->draw_rectangle($kala, 415, $kala-15, 450,	$firstpage, $rectparam);
			$pdf->draw_text(422, $kala-8, t("Brutto kg", $kieli), $firstpage, $norm);
			$pdf->draw_rectangle($kala, 450, $kala-15, 540, $firstpage, $rectparam);
			$pdf->draw_text(453, $kala-8, t("Tilavuus, m3", $kieli), $firstpage, $norm);

			$kala1 = $kala;
			$kala2 = $kala - 26;
			$kala -= 26;

			$tilausnumerot = wordwrap($tilaukset, 50, "\n");

			foreach (explode("\n", $tilausnumerot) as $tilausnumero) {
				$pdf->draw_text(62, $kala2, $tilausnumero, $firstpage, $kirj);
				$kala2 -= 10;
			}

			// summataan kaikki painot yhteen
			$query = "	SELECT pakkaus,
						pakkauskuvaus,
						sum(kilot) kilot,
						sum(kollit) kollit,
						sum(kuutiot) kuutiot,
						sum(lavametri) lavametri
						FROM rahtikirjat
						WHERE otsikkonro in ($tilaukset)
						AND yhtio = '$kukarow[yhtio]'
						GROUP BY 1,2
						ORDER BY 1,2";
			$pakka = mysql_query($query) or pupe_error($query);


			$kilotyht  		= 0;
			$kollityht 		= 0;
			$lavametriyht	= 0;


			while ($pakkarow = mysql_fetch_assoc($pakka)) {

				$pdf->draw_text(246, $kala, $pakkarow['kollit']."  ".$pakkarow['pakkaus'], $firstpage, $kirj);
				$pdf->draw_text(422, $kala, round($pakkarow['kilot'], 2),	$firstpage, $kirj);
				$pdf->draw_text(486, $kala, $pakkarow['kuutiot'], $firstpage, $kirj);

				$kollityht 		+= $pakkarow['kollit'];
				$kilotyht  		+= round($pakkarow['kilot'], 2);
				$lavametriyht	+= round($pakkarow['lavametri'], 2);
				$kala -= 10;
			}

			if ($kala2 < $kala) $kala = $kala2;

			$pdf->draw_rectangle($kala1, 60, $kala-22, 540,	$firstpage, $rectparam);

			//alarivi
			$pdf->draw_text(246, $kala-6 , t("Kollit yht", $kieli).".", $firstpage, $norm);
			$pdf->draw_text(246, $kala-20, $kollityht, $firstpage, $kirj);
			$pdf->draw_rectangle($kala, 111, $kala-22, 242,	$firstpage, $rectparam);
			$pdf->draw_text(365, $kala-6, t("Lavametrit", $kieli), $firstpage, $norm);
			$pdf->draw_text(365, $kala-20, $lavametriyht, $firstpage, $kirj);
			$pdf->draw_rectangle($kala, 363, $kala-22, 420,	$firstpage, $rectparam);
			$pdf->draw_text(422, $kala-6, t("Brutto yht", $kieli).". ".t("Rahdituspaino", $kieli).".", $firstpage, $norm);
			$pdf->draw_text(422, $kala-20, $kilotyht, $firstpage, $kirj);

			$pdf->draw_rectangle($kala, 60, $kala, 540,	$firstpage, $rectparam);
			$pdf->draw_rectangle($kala-22, 60, $kala-22, 540, $firstpage, $rectparam);

			$kala -= 50;

			return array($kala, $firstpage);
		}
	}

	// Tehdäänkö uusi sivu per asiakas
	$uusisivu = FALSE;

	if ($toitarow['erittely'] == 'k') {
		$uusisivu = TRUE;
	}

	// Haetaan tulostetut tilaukset
	$query = "	SELECT ytunnus, toim_nimi, toim_osoite, toim_postino, toim_postitp, toim_maa, group_concat(tunnus SEPARATOR ', ') tunnukset
				FROM lasku
				WHERE yhtio = '$kukarow[yhtio]'
				AND tunnus in ($otunnukset)
				GROUP BY 1
				ORDER BY 1";
	$result = mysql_query($query) or pupe_error($query);

	$kala = 780;
	$firstpage = 0;

	while ($tilauksetrow = mysql_fetch_assoc($result)) {

		$tilaukset = $tilauksetrow['tunnukset'];

		list($kala, $firstpage) = rivi_erittely($tilaukset, $uusisivu, $kala, $firstpage);
	}

	//keksitään uudelle failille joku varmasti uniikki nimi:
	$pdffilenimi = "/tmp/rahtikirjaerittely-".md5(uniqid(rand(),true)).".pdf";

	//kirjoitetaan pdf faili levylle..
	$fh = fopen($pdffilenimi, "w");
	if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus epäonnistui $pdffilenimi");
	fclose($fh);

	if ($tee == 'NAYTATILAUS') {
		//Työnnetään tuo pdf vaan putkeen!
		echo file_get_contents($pdffilenimi);
	}
	else {
		if ($kirjoitin == "email") {
			$liite = $pdffilenimi;
			$kutsu = "PDF-erittely";
			require("inc/sahkoposti.inc");
		}
		else {
			//itse print komento...
			$line = exec("$kirjoitin $pdffilenimi");
		}
	}

	system("rm -f $pdffilenimi");
?>