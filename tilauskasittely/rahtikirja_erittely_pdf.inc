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
			global $yhtiorow, $kukarow, $pdf, $kieli, $iso, $toitarow, $kirj;

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

			$pdf->draw_text(302, 812, t("RAHTIKIRJAERITTELY", $kieli), $firstpage, $iso);

			if ($toitarow['erittely'] != 'k') {
				$pdf->draw_text(302, 797, $toitarow['selite'], $firstpage, $kirj);

				if (isset($laskurow["toimitustavan_lahto"]) and $laskurow["toimitustavan_lahto"] > 0) {
					$query = "	SELECT pvm, lahdon_kellonaika
								FROM lahdot
								WHERE yhtio = '{$kukarow["yhtio"]}'
								AND tunnus = '{$laskurow["toimitustavan_lahto"]}'";
					$chk_res = pupe_query($query);
					$chk_row = mysql_fetch_assoc($chk_res);

					$pdf->draw_text(302, 782, tv1dateconv($chk_row["pvm"]." ".$chk_row["lahdon_kellonaika"], "P"), $firstpage, $kirj);
				}
				else {
					$pdf->draw_text(302, 782, date("d.m.Y H:i:s"), $firstpage, $kirj);
				}
			}

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
			static $edytunnus, $kala1, $edliitostunnus;

			$otsikot = FALSE;
			$summatmenijo = FALSE;

			// Uusi sivu
			if (!isset($pdf) or $kala < 150 or ($toitarow['erittely'] == 'k' and isset($edytunnus) and $edytunnus != $laskurow['ytunnus'])) {

				if ($kala < 150) {
					// Piiretään edelliselle sivulle summatiedot
					list($kala, $kollityht, $kilotyht, $lavametriyht) = loppu_erittely($kala1, $kala, $firstpage, $kollityht, $kilotyht, $lavametriyht);
					$summatmenijo = TRUE;
				}
				elseif ($toitarow['erittely'] == 'k' and isset($edytunnus) and $edytunnus != $laskurow['ytunnus']) {
					// Piiretään edelliselle sivulle summatiedot
					list($kala, $kollityht, $kilotyht, $lavametriyht) = loppu_erittely($kala1, $kala, $firstpage, $kollityht, $kilotyht, $lavametriyht);
				}

				list($kala, $firstpage) = uusi_sivu_erittely($laskurow);
				$otsikot = TRUE;
			}

			// Asiakas vaihtuu, uudet otsikkotiedot
			if (!$summatmenijo and $toitarow['erittely'] != 'k' and ((isset($edytunnus) and $edytunnus != $laskurow['ytunnus']) or (isset($edliitostunnus) and $edliitostunnus != $laskurow['liitostunnus']))) {
				// Piiretään sivulle summatiedot
				list($kala, $kollityht, $kilotyht, $lavametriyht) = loppu_erittely($kala1, $kala, $firstpage, $kollityht, $kilotyht, $lavametriyht);
				$otsikot = TRUE;
			}

			if ($otsikot) {
				$pdf->draw_rectangle($kala, 60, $kala-50, 540, $firstpage, $rectparam);
			   	$pdf->draw_text(62, $kala-8, t("Asiakas", $kieli),	$firstpage, $norm);
			   	$pdf->draw_text(62, $kala-18, t("Ytunnus", $kieli)." ".$laskurow['ytunnus'],		$firstpage, $kirj);

			   	if ($toitarow['erittely'] == 'k') {
			   		$pdf->draw_text(62, $kala-28, $laskurow['nimi']." ".$laskurow['nimitark'],		$firstpage, $kirj);
			   		$pdf->draw_text(62, $kala-38, $laskurow['osoite'],	$firstpage, $kirj);
			   		$pdf->draw_text(62, $kala-48, $laskurow['postino']." ".$laskurow['postitp'],	$firstpage, $kirj);
			   	}
			   	else {
			   		$pdf->draw_text(62, $kala-28, $laskurow['toim_nimi']." ".$laskurow['toim_nimitark'],		$firstpage, $kirj);
			   		$pdf->draw_text(62, $kala-38, $laskurow['toim_osoite'],	$firstpage, $kirj);
			   		$pdf->draw_text(62, $kala-48, $laskurow['toim_postino']." ".$laskurow['toim_postitp'],	$firstpage, $kirj);
			   	}

			   	$query = "	SELECT yhteyshenkilo.nimi,
			   				IF(yhteyshenkilo.puh != '', yhteyshenkilo.puh, IF(yhteyshenkilo.gsm != '', yhteyshenkilo.gsm, '')) puh
			   				FROM lasku
			   				JOIN yhteyshenkilo ON (yhteyshenkilo.yhtio = lasku.yhtio AND yhteyshenkilo.nimi = lasku.tilausyhteyshenkilo AND yhteyshenkilo.tyyppi = 'A')
			   				WHERE lasku.yhtio = '{$kukarow['yhtio']}'
			   				AND lasku.tunnus IN ({$laskurow['tunnus']})
			   				AND lasku.tilausyhteyshenkilo != ''
			   				LIMIT 1";
			   	$yhres = pupe_query($query);

			   	if (mysql_num_rows($yhres) == 0) {
			   		$query = "	SELECT yhteyshenkilo.nimi,
			   					IF(yhteyshenkilo.puh != '', yhteyshenkilo.puh, IF(yhteyshenkilo.gsm != '', yhteyshenkilo.gsm, '')) puh
			   					FROM laskun_lisatiedot
			   					JOIN yhteyshenkilo ON (yhteyshenkilo.yhtio = laskun_lisatiedot.yhtio AND yhteyshenkilo.tunnus = laskun_lisatiedot.yhteyshenkilo_kaupallinen AND yhteyshenkilo.tyyppi = 'A')
			   					WHERE laskun_lisatiedot.yhtio = '{$kukarow['yhtio']}'
			   					AND laskun_lisatiedot.otunnus IN ({$laskurow['tunnus']})
			   					AND laskun_lisatiedot.yhteyshenkilo_tekninen > 0
			   					LIMIT 1";
			   		$yhres = pupe_query($query);
			   	}

				if (mysql_num_rows($yhres) == 0) {
		   			$query = "	SELECT '2' O, '' nimi,
		   						IF(puhelin != '', puhelin, IF(gsm != '', gsm, '')) puh
		   						FROM asiakas
		   						WHERE yhtio = '{$kukarow['yhtio']}'
		   						AND tunnus = '{$laskurow['liitostunnus']}'";
		   			$yhres = pupe_query($query);
		   		}

			   	if (mysql_num_rows($yhres) > 0) {
			   		$yhrow = mysql_fetch_assoc($yhres);

			   		$pdf->draw_text(244, $kala-38, $yhrow['nimi'],	$firstpage, $kirj);
			   		$pdf->draw_text(244, $kala-48, $yhrow['puh'],	$firstpage, $kirj);
			   	}

				$oikpos = $pdf->strlen(t("Allekirjoitus ja nimenselvennys", $kieli), $norm);
				$pdf->draw_text(530-$oikpos, $kala-45, t("Allekirjoitus ja nimenselvennys", $kieli), 	$firstpage, $norm);

				$kala -= 50;

				//kollitietot
				//ylärivi
				if ($toitarow['erittely'] == 'k') {
					$pdf->draw_rectangle($kala, 60, $kala-15, 110, $firstpage, $rectparam);
					$pdf->draw_text(62, $kala-8, t("Myymälänro", $kieli),	$firstpage, $norm);
					$pdf->draw_text(62, $kala-14, t("Astilnro", $kieli),	$firstpage, $norm);
					$pdf->draw_rectangle($kala, 110, $kala-15, 242,	$firstpage, $rectparam);
					$pdf->draw_text(113, $kala-8, t("Myymälän nimi", $kieli),	$firstpage, $norm);
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
			$edliitostunnus = $laskurow['liitostunnus'];

			return array($kala, $firstpage, $kollityht, $kilotyht, $lavametriyht);
		}
	}

	// Ei kaaduta erroriin jos jostain syystä tullaan tyhjällä
	if ($otunnukset == "") $otunnukset = 0;

	// Haetaan tulostetut tilaukset
	if ($toitarow['erittely'] == 'k') {
		$query = "	SELECT asiakkaan_tilausnumero, nimi, nimitark, osoite, postino, postitp, toim_nimi, toim_ovttunnus, toim_postitp, tunnus, ytunnus
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]'
					AND tunnus in ($otunnukset)
					ORDER BY ytunnus, tunnus";
	}
	else {
		$query = "	SELECT nimi,
					nimitark,
					osoite,
					postino,
					postitp,
					toim_nimi,
					toim_nimitark,
					toim_osoite,
					toim_postino,
					toim_postitp,
					ytunnus,
					liitostunnus,
					toimitustavan_lahto,
					group_concat(tunnus) tunnus
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]'
					AND tunnus in ($otunnukset)
					GROUP BY nimi, nimitark, osoite, postino, postitp, toim_nimi, toim_nimitark, toim_osoite, toim_postino, toim_postitp, ytunnus, liitostunnus, toimitustavan_lahto
					ORDER BY ytunnus, tunnus";
	}

	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) > 0) {

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
	}
