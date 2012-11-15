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
			global $yhtiorow, $kukarow, $pdf, $kieli, $iso;

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

	if (!function_exists('loppu_erittely')) {
		function loppu_erittely ($kala1, $kala, $firstpage, $kollityht, $kilotyht, $lavametriyht) {
			global $yhtiorow, $kukarow, $pdf, $kieli, $rectparam, $norm, $kirj;

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

			return array($kala, 0, 0, 0);
		}
	}

	if (!function_exists('rivi_erittely')) {
		function rivi_erittely ($laskurow, $kala, $firstpage, $kollityht, $kilotyht, $lavametriyht, $vikarivi) {
			global $yhtiorow, $kukarow, $pdf, $kieli, $rectparam, $norm, $kirj, $iso, $toitarow;
			static $edytunnus, $kala1;

			$otsikot = FALSE;

			// Uusi sivu
			if (!isset($pdf) or $kala < 100 or ($toitarow['erittely'] == 'k' and isset($edytunnus) and $edytunnus != $laskurow['ytunnus'])) {

				if ($kala < 100) {
					// Piiretään edelliselle sivulle sopivan kokonen laatikko
					$pdf->draw_rectangle($kala1, 60, $kala-22, 540,	$firstpage, $rectparam);
				}
				elseif ($toitarow['erittely'] == 'k' and isset($edytunnus) and $edytunnus != $laskurow['ytunnus']) {
					// Piiretään edelliselle sivulle summatiedot
					list($kala, $kollityht, $kilotyht, $lavametriyht) = loppu_erittely($kala1, $kala, $firstpage, $kollityht, $kilotyht, $lavametriyht);
				}

				list($kala, $firstpage) = uusi_sivu_erittely($laskurow);
				$otsikot = TRUE;
			}

			// Asiakas vaihtuu, uudet otsikkotiedot
			if ($toitarow['erittely'] != 'k' and isset($edytunnus) and $edytunnus != $laskurow['ytunnus']) {
				// Piiretään sivulle summatiedot
				list($kala, $kollityht, $kilotyht, $lavametriyht) = loppu_erittely($kala1, $kala, $firstpage, $kollityht, $kilotyht, $lavametriyht);
				$otsikot = TRUE;
			}

			if ($otsikot) {
				$pdf->draw_rectangle($kala, 60, $kala-50, 540, $firstpage, $rectparam);
			   	$pdf->draw_text(62, $kala-8, t("Asiakas", $kieli),	$firstpage, $norm);
			   	$pdf->draw_text(62, $kala-18, t("Ytunnus", $kieli)." ".$laskurow['ytunnus'],		$firstpage, $kirj);
			   	$pdf->draw_text(62, $kala-28, $laskurow['nimi']." ".$laskurow['nimitark'],		$firstpage, $kirj);
			   	$pdf->draw_text(62, $kala-38, $laskurow['osoite'],	$firstpage, $kirj);
			   	$pdf->draw_text(62, $kala-48, $laskurow['postino']." ".$laskurow['postitp'],	$firstpage, $kirj);

				$oikpos = $pdf->strlen(t("Allekirjoitus ja nimenselvennys", $kieli), $norm);
				$pdf->draw_text(530-$oikpos, $kala-45, t("Allekirjoitus ja nimenselvennys", $kieli), 	$firstpage, $norm);

				$kala -= 50;

				//kollitietot
				//ylärivi
				if ($toitarow['erittely'] == 'k') {
					$pdf->draw_rectangle($kala, 60, $kala-15, 110, $firstpage, $rectparam);
					$pdf->draw_text(62, $kala-8, "Myymälänro",	$firstpage, $norm);
					$pdf->draw_text(62, $kala-14, "Astilnro",	$firstpage, $norm);
					$pdf->draw_rectangle($kala, 110, $kala-15, 242,	$firstpage, $rectparam);
					$pdf->draw_text(113, $kala-8, "Myymälän nimi",	$firstpage, $norm);
				}
				else {
					$pdf->draw_rectangle($kala, 60, $kala-15, 242, $firstpage, $rectparam);
					$pdf->draw_text(62, $kala-8, t("Tilausnumerot", $kieli),	$firstpage, $norm);
				}

				$pdf->draw_rectangle($kala, 242, $kala-15, 415,	$firstpage, $rectparam);
				$pdf->draw_text(244, $kala-8, t("Kolliluku ja -laji", $kieli), $firstpage, $norm);
				$pdf->draw_rectangle($kala, 415, $kala-15, 450,	$firstpage, $rectparam);
				$pdf->draw_text(422, $kala-8, t("Brutto kg", $kieli), $firstpage, $norm);
				$pdf->draw_rectangle($kala, 450, $kala-15, 540, $firstpage, $rectparam);
				$pdf->draw_text(453, $kala-8, t("Tilavuus, m3", $kieli), $firstpage, $norm);

				$kala1 = $kala;
			}

			$kala2 = $kala - 26;
			$kala -= 26;

			if ($toitarow['erittely'] == 'k') {
				$pdf->draw_text(62,  $kala, $laskurow['toim_ovttunnus'],			$firstpage, $kirj);
				$pdf->draw_text(112, $kala, $laskurow['toim_nimi'],					$firstpage, $kirj);
				$pdf->draw_text(112, $kala-10, $laskurow['toim_postitp'],			$firstpage, $kirj);
				$pdf->draw_text(62,  $kala-10, $laskurow['asiakkaan_tilausnumero'],	$firstpage, $kirj);
				$kala2 -= 15;
			}
			else {
				$tilausnumerot = wordwrap($laskurow['tunnus'], 50, "\n");

				foreach (explode("\n", $tilausnumerot) as $tilausnumero) {
					$pdf->draw_text(62, $kala2, $tilausnumero, $firstpage, $kirj);
					$kala2 -= 10;
				}
			}

			// summataan kaikki painot yhteen
			$query = "	SELECT pakkaus,
						pakkauskuvaus,
						sum(kilot) kilot,
						sum(kollit) kollit,
						sum(kuutiot) kuutiot,
						sum(lavametri) lavametri
						FROM rahtikirjat
						WHERE otsikkonro in ({$laskurow['tunnus']})
						AND yhtio = '$kukarow[yhtio]'
						GROUP BY 1,2
						ORDER BY 1,2";
			$pakka = mysql_query($query) or pupe_error($query);

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


			if ($vikarivi) {
				list($kala, $kollityht, $kilotyht, $lavametriyht) = loppu_erittely($kala1, $kala, $firstpage, $kollityht, $kilotyht, $lavametriyht);
			}

			$edytunnus = $laskurow['ytunnus'];

			return array($kala, $firstpage, $kollityht, $kilotyht, $lavametriyht);
		}
	}

	// Haetaan tulostetut tilaukset
	if ($toitarow['erittely'] == 'k') {
		$query = "	SELECT asiakkaan_tilausnumero, nimi, nimitark, osoite, postino, postitp, toim_nimi, toim_ovttunnus, toim_postitp, tunnus, ytunnus
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]'
					AND tunnus in ($otunnukset)
					ORDER BY ytunnus, tunnus";
	}
	else {
		$query = "	SELECT nimi, nimitark, osoite, postino, postitp, ytunnus, group_concat(tunnus) tunnus
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]'
					AND tunnus in ($otunnukset)
					GROUP BY nimi, nimitark, osoite, postino, postitp, ytunnus
					ORDER BY ytunnus, tunnus";
	}

	$result = mysql_query($query) or pupe_error($query);

	$kala		  = 780;
	$firstpage 	  = 0;
	$kollityht	  = 0;
	$kilotyht	  = 0;
	$lavametriyht = 0;
	$rivimaara 	  = mysql_num_rows($result);
	$lask 		  = 0;
	$vikarivi	  = FALSE;

	while ($laskurow = mysql_fetch_assoc($result)) {
		$lask++;
		if ($lask == $rivimaara) $vikarivi = TRUE;

		list($kala, $firstpage, $kollityht, $kilotyht, $lavametriyht) = rivi_erittely($laskurow, $kala, $firstpage, $kollityht, $kilotyht, $lavametriyht, $vikarivi);
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