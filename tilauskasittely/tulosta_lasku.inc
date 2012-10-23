<?php

if (@!include_once('pdflib/phppdflib.class.php')) {
	include_once('phppdflib.class.php');
}

require_once("barcode/barcode.php");
require_once("barcode/c128cobject.php");

$rectparam["width"] 	= 0.3;

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

if (!function_exists('alku')) {
	function alku ($laskurow, $asiakasrow, $kieli, $pdf_lasku, $toim, $laskutyyppi, $kaanteinen_verotus, $page_lasku, $sivu) {
		global $yhtiorow, $kukarow, $rectparam, $norm, $pieni, $boldi, $pieni_boldi, $iso;

		$page_lasku[$sivu] = $pdf_lasku->new_page("a4");

		tulosta_logo_pdf($pdf_lasku, $page_lasku[$sivu], $laskurow);

		//Otsikko
		if ($laskurow["vienti"] == "") {
			if ($laskurow["summa"] >= 0) {
				if ($toim == 'PROFORMA') {
					$pdf_lasku->draw_text(310, 815, t("Proformalasku", $kieli), $page_lasku[$sivu], $iso);
				}
				else {
					$pdf_lasku->draw_text(310, 815, t("Lasku", $kieli), $page_lasku[$sivu], $iso);
				}
			}
			else {
				$pdf_lasku->draw_text(310, 815, t("Hyvityslasku", $kieli), $page_lasku[$sivu], $iso);
			}
		}
		else {
			if ($toim == 'PROFORMA') {
				$pdf_lasku->draw_text(310, 815, t("Proformalasku", $kieli), $page_lasku[$sivu], $iso);
			}
			elseif ($laskurow["kateistyyppi"] != '') {
				$pdf_lasku->draw_text(310, 815, t("Käteiskuitti", $kieli),  $page_lasku[$sivu], $iso);
			}
			elseif ($laskurow["summa"] >= 0) {
				$pdf_lasku->draw_text(310, 815, t("Vientilasku", $kieli), $page_lasku[$sivu], $iso);
			}
			else {
				$pdf_lasku->draw_text(310, 815, t("Viennin hyvityslasku", $kieli), $page_lasku[$sivu], $iso);
			}
		}

		//Vasen sarake
		//$pdf_lasku->draw_rectangle(737, 20,  674, 300, $page_lasku[$sivu], $rectparam);
		$query = "	SELECT laskutus_nimi,
					laskutus_nimitark,
					laskutus_osoite,
					laskutus_postino,
					laskutus_postitp,
					laskutus_maa
					FROM laskun_lisatiedot
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND laskutus_nimi != ''
					AND CONCAT(laskutus_nimi, laskutus_osoite, laskutus_postino, laskutus_postitp, laskutus_maa) != '$laskurow[nimi]$laskurow[osoite]$laskurow[postino]$laskurow[postitp]$laskurow[maa]'
					AND otunnus = '{$laskurow['tunnus']}'";
		$laskutusosoite_result = pupe_query($query);

		$osoitekenttia = 2;

		if (mysql_num_rows($laskutusosoite_result) > 0) {
			$laskutusosoite_row = mysql_fetch_assoc($laskutusosoite_result);

			$pdf_lasku->draw_text(50, 729, t("Laskutusosoite", $kieli), 				$page_lasku[$sivu], $pieni);
			$pdf_lasku->draw_text(50, 717, $laskutusosoite_row["laskutus_nimi"], 		$page_lasku[$sivu], $norm);
			$pdf_lasku->draw_text(50, 707, $laskutusosoite_row["laskutus_nimitark"],	$page_lasku[$sivu], $norm);
			$pdf_lasku->draw_text(50, 697, $laskutusosoite_row["laskutus_osoite"], 		$page_lasku[$sivu], $norm);
			$pdf_lasku->draw_text(50, 687, $laskutusosoite_row["laskutus_postino"]." ".$laskutusosoite_row["laskutus_postitp"], $page_lasku[$sivu], $norm);
			$pdf_lasku->draw_text(50, 677, $laskutusosoite_row["laskutus_maa"], 		$page_lasku[$sivu], $norm);

			$ostaja_kala = 644;
			$toim_kala = 583;
			$pohja = 504;
			$osoitekenttia = 3;

			$pdf_lasku->draw_text(50, 656, t("Ostaja", $kieli), 			$page_lasku[$sivu], $pieni);

		}
		else {
			$ostaja_kala = 717;
			$toim_kala = 656;
			$pohja  = 577;

			$pdf_lasku->draw_text(50, 729, t("Ostaja/Laskutusosoite", $kieli), 	$page_lasku[$sivu], $pieni);
		}

		$pdf_lasku->draw_text(50, $ostaja_kala, $laskurow["nimi"], 				$page_lasku[$sivu], $norm);
		$pdf_lasku->draw_text(50, $ostaja_kala-10, $laskurow["nimitark"],		$page_lasku[$sivu], $norm);
		$pdf_lasku->draw_text(50, $ostaja_kala-20, $laskurow["osoite"], 		$page_lasku[$sivu], $norm);
		$pdf_lasku->draw_text(50, $ostaja_kala-30, $laskurow["postino"]." ".$laskurow["postitp"], $page_lasku[$sivu], $norm);
		$pdf_lasku->draw_text(50, $ostaja_kala-40, $laskurow["maa"], 			$page_lasku[$sivu], $norm);

		//$pdf_lasku->draw_rectangle(674, 20,  611, 300, $page_lasku[$sivu], $rectparam);
		$pdf_lasku->draw_text(50, $toim_kala, t("Toimitusosoite", $kieli), 	$page_lasku[$sivu], $pieni);
		$pdf_lasku->draw_text(50, $toim_kala-12, $laskurow["toim_nimi"], 		$page_lasku[$sivu], $norm);
		$pdf_lasku->draw_text(50, $toim_kala-22, $laskurow["toim_nimitark"], 	$page_lasku[$sivu], $norm);
		$pdf_lasku->draw_text(50, $toim_kala-32, $laskurow["toim_osoite"],	 	$page_lasku[$sivu], $norm);
		$pdf_lasku->draw_text(50, $toim_kala-42, $laskurow["toim_postino"]." ".$laskurow["toim_postitp"], $page_lasku[$sivu], $norm);
		$pdf_lasku->draw_text(50, $toim_kala-52, $laskurow["toim_maa"], 		$page_lasku[$sivu], $norm);

		//Oikea sarake
		$otsik[1] = 792;
		$arvo[1]  = 782;

		$otsik[2] = 771;
		$arvo[2]  = 761;

		$otsik[3] = 750;
		$arvo[3]  = 740;

		$otsik[4] = 729;
		$arvo[4]  = 719;

		$otsik[5] = 708;
		$arvo[5]  = 698;

		$otsik[6] = 687;
		$arvo[6]  = 677;

		$otsik[7] = 666;
		$arvo[7]  = 656;

		$otsik[8] = 645;
		$arvo[8]  = 635;

		$otsik[9] = 624;
		$arvo[9]  = 614;

		// Oikean laidan ruudukko
		$pdf_lasku->draw_rectangle(800, 300, 779, 580, $page_lasku[$sivu], $rectparam);
		$pdf_lasku->draw_rectangle(800, 420, 779, 580, $page_lasku[$sivu], $rectparam);
		$pdf_lasku->draw_rectangle(779, 300, 758, 580, $page_lasku[$sivu], $rectparam);
		$pdf_lasku->draw_rectangle(779, 420, 758, 580, $page_lasku[$sivu], $rectparam);
		$pdf_lasku->draw_rectangle(758, 300, 737, 580, $page_lasku[$sivu], $rectparam);
		$pdf_lasku->draw_rectangle(758, 420, 737, 580, $page_lasku[$sivu], $rectparam);
		$pdf_lasku->draw_rectangle(737, 300, 716, 580, $page_lasku[$sivu], $rectparam);
		$pdf_lasku->draw_rectangle(737, 420, 716, 580, $page_lasku[$sivu], $rectparam);
		$pdf_lasku->draw_rectangle(716, 300, 695, 580, $page_lasku[$sivu], $rectparam);
		$pdf_lasku->draw_rectangle(716, 420, 695, 580, $page_lasku[$sivu], $rectparam);

		if ($laskutyyppi != "8") {
			$pdf_lasku->draw_rectangle(695, 300, 674, 580, $page_lasku[$sivu], $rectparam);
			$pdf_lasku->draw_rectangle(695, 420, 674, 580, $page_lasku[$sivu], $rectparam);
			$pdf_lasku->draw_rectangle(674, 300, 653, 580, $page_lasku[$sivu], $rectparam);
			$pdf_lasku->draw_rectangle(674, 420, 653, 580, $page_lasku[$sivu], $rectparam);
			$pdf_lasku->draw_rectangle(653, 300, 632, 580, $page_lasku[$sivu], $rectparam);
			$pdf_lasku->draw_rectangle(632, 300, 611, 580, $page_lasku[$sivu], $rectparam);
		}
		else {
			$pdf_lasku->draw_rectangle(695, 300, 674, 580, $page_lasku[$sivu], $rectparam);
			$pdf_lasku->draw_rectangle(674, 300, 653, 580, $page_lasku[$sivu], $rectparam);
		}



		// Oikean laidan ruudukko, vasen sarake
		// Riviindeksi
		$rivi_ind = 1;

		$pdf_lasku->draw_text(310, $otsik[$rivi_ind], t("Laskun pvm", $kieli), 					$page_lasku[$sivu], $pieni);
		$pdf_lasku->draw_text(310, $arvo[$rivi_ind], tv1dateconv($laskurow["tapvm"]),			$page_lasku[$sivu], $norm);
		$rivi_ind++;

		if ($laskutyyppi != "8") {
			$pdf_lasku->draw_text(310, $otsik[$rivi_ind], t("Viitenumero", $kieli), 			$page_lasku[$sivu], $pieni);
			$pdf_lasku->draw_text(310, $arvo[$rivi_ind], $laskurow["viite"], 					$page_lasku[$sivu], $boldi);
			$rivi_ind++;
		}

		$pdf_lasku->draw_text(310, $otsik[$rivi_ind], t("Maksuehto", $kieli), 					$page_lasku[$sivu], $pieni);
		for ($s = strlen($laskurow["maksuehto"]); $s > 0; $s--) {
			if ($pdf_lasku->strlen($laskurow["maksuehto"], $norm) > 110) {
				$laskurow["maksuehto"] = substr($laskurow["maksuehto"], 0, $s);
			}
			else break;
		}
		$pdf_lasku->draw_text(310, $arvo[$rivi_ind], pdf_substr($laskurow["maksuehto"], 110, $pdf_lasku, $norm), $page_lasku[$sivu], $norm);
		$rivi_ind++;

		$pdf_lasku->draw_text(310, $otsik[$rivi_ind], t("Viivästyskorko", $kieli), 								$page_lasku[$sivu], $pieni);
		$pdf_lasku->draw_text(310, $arvo[$rivi_ind], $laskurow["viikorkopros"],	 								$page_lasku[$sivu], $norm);
		$rivi_ind++;

		$pdf_lasku->draw_text(310, $otsik[$rivi_ind], t("Valuutta", $kieli), 									$page_lasku[$sivu], $pieni);
		$pdf_lasku->draw_text(310, $arvo[$rivi_ind], $laskurow["valkoodi"], 									$page_lasku[$sivu], $norm);
		$rivi_ind++;

		if ($laskutyyppi != "8") {
			$pdf_lasku->draw_text(310, $otsik[$rivi_ind], t("Asiakkaan Y-tunnus", $kieli), 							$page_lasku[$sivu], $pieni);
			$pdf_lasku->draw_text(310, $arvo[$rivi_ind], tulosta_ytunnus($laskurow["ytunnus"], $laskurow["maa"], $laskurow["vienti"]), $page_lasku[$sivu], $norm);
			$rivi_ind++;
		}

		$pdf_lasku->draw_text(310, $otsik[$rivi_ind], t("Toimitustapa", $kieli), 								$page_lasku[$sivu], $pieni);
		$pdf_lasku->draw_text(310, $arvo[$rivi_ind], pdf_substr(t_tunnus_avainsanat($laskurow['toimitustapa'], "selite", "TOIMTAPAKV", $kieli), 110, $pdf_lasku, $norm), $page_lasku[$sivu], $norm);



		// Oikean laidan ruudukko, oikea sarake
		// Riviindeksi
		$rivi_ind = 1;

		$pdf_lasku->draw_text(430, $otsik[$rivi_ind], t("Eräpäivä", $kieli), 									$page_lasku[$sivu], $pieni);
		if ($laskurow["kateistyyppi"] != '') $erapaiva = t('MAKSETTU', $kieli);
		else $erapaiva = tv1dateconv($laskurow["erpcm"]);
		$pdf_lasku->draw_text(430, $arvo[$rivi_ind], $erapaiva, 												$page_lasku[$sivu], $norm);
		$rivi_ind++;

		$pdf_lasku->draw_text(430, $otsik[$rivi_ind], t("Laskun numero", $kieli), 								$page_lasku[$sivu], $pieni);
		if ($laskurow["laskunro"] != 0) $pdf_lasku->draw_text(430, $arvo[$rivi_ind], $laskurow["laskunro"], 	$page_lasku[$sivu], $boldi);
		$rivi_ind++;

		if ($laskutyyppi != "8") {
			$pdf_lasku->draw_text(430, $otsik[$rivi_ind], t("Kassa-alennus", $kieli)." ".$laskurow["valkoodi"], 	$page_lasku[$sivu], $pieni);

			if ($laskurow["kasumma"] != 0.00) {
				if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
					$pdf_lasku->draw_text(430, $arvo[$rivi_ind], $laskurow["kasumma_valuutassa"], 					$page_lasku[$sivu], $norm);
				}
				else {
					$pdf_lasku->draw_text(430, $arvo[$rivi_ind], $laskurow["kasumma"], 								$page_lasku[$sivu], $norm);
				}
			}
			$rivi_ind++;

			$pdf_lasku->draw_text(430, $otsik[$rivi_ind], t("Kassa-alennus pvm", $kieli), 	$page_lasku[$sivu], $pieni);

			if ($laskurow["kapvm"] != '0000-00-00') {
				$pdf_lasku->draw_text(430, $arvo[$rivi_ind], tv1dateconv($laskurow["kapvm"]), $page_lasku[$sivu], $norm);
			}
			$rivi_ind++;
		}

		$pdf_lasku->draw_text(430, $otsik[$rivi_ind], t("Asiaa hoitaa", $kieli), 	$page_lasku[$sivu], $pieni);

		//etsitään myyjän nimi
		$query  = "	SELECT nimi
					from kuka
					where tunnus='$laskurow[myyja]' and yhtio='$kukarow[yhtio]'";
		$myyresult = pupe_query($query);
		$myyrow = mysql_fetch_assoc($myyresult);

		$pdf_lasku->draw_text(430, $arvo[$rivi_ind], $myyrow["nimi"], $page_lasku[$sivu], $norm);
		$rivi_ind++;

		$pdf_lasku->draw_text(430, $otsik[$rivi_ind], t("Asiakasnumero", $kieli), 		$page_lasku[$sivu], $pieni);

		if (trim($asiakasrow["asiakasnro"]) != "") {
			$pdf_lasku->draw_text(430, $arvo[$rivi_ind], $asiakasrow["asiakasnro"], 	$page_lasku[$sivu], $norm);
		}
		else {
			$pdf_lasku->draw_text(430, $arvo[$rivi_ind], $laskurow["ytunnus"], 		$page_lasku[$sivu], $norm);
		}
		$rivi_ind++;

		if ($laskutyyppi == "8") {
			$pdf_lasku->draw_text(430, $otsik[$rivi_ind], t("Asiakkaan Y-tunnus", $kieli), 							$page_lasku[$sivu], $pieni);
			$pdf_lasku->draw_text(430, $arvo[$rivi_ind], tulosta_ytunnus($laskurow["ytunnus"], $laskurow["maa"], $laskurow["vienti"]), $page_lasku[$sivu], $norm);
			$rivi_ind++;
		}

		if ($laskutyyppi != "8") {
			$pdf_lasku->draw_text(430, $otsik[$rivi_ind], t("Toimituspvm", $kieli), 	$page_lasku[$sivu], $pieni);

			//	Onko meillä monta toimituspäivää vai ei?
			if ($laskurow["tila"] == 'U') {
				$where = " uusiotunnus='$laskurow[tunnus]' ";
			}
			else {
				$where = " otunnus='$laskurow[tunnus]' ";
			}

			if ($yhtiorow["tilausrivien_toimitettuaika"] == 'K') {
				$toimitettuaikak = " if (keratty = 'saldoton' and toimaika != '0000-00-00', toimaika, toimitettuaika) ";
			}
			elseif ($yhtiorow["tilausrivien_toimitettuaika"] == 'X') {
				$toimitettuaikak = " if (toimaika != '0000-00-00', toimaika, toimitettuaika) ";
			}
			else {
				$toimitettuaikak = " toimitettuaika ";
			}

			$query = "	SELECT date_format($toimitettuaikak, '%Y-%m-%d') toimitettupaiva
						FROM tilausrivi
						WHERE yhtio = '$kukarow[yhtio]'
						and $where
						and toimitettuaika!='0000-00-00'
						GROUP BY toimitettupaiva";
			$toimaikares = pupe_query($query);

			if (mysql_num_rows($toimaikares) > 1) {
				$pdf_lasku->draw_text(430, $arvo[$rivi_ind], t("Useita", $kieli), 											$page_lasku[$sivu], $norm);
			}
			else {
				$toimaikarow = mysql_fetch_assoc($toimaikares);
				$pdf_lasku->draw_text(430, $arvo[$rivi_ind], tv1dateconv($toimaikarow["toimitettupaiva"]),			$page_lasku[$sivu], $norm);
			}
			$rivi_ind++;
		}


		// Leveämmät laatikot
		$toimehto = t_avainsana("TOIMEHTO", $kieli, " and concat(selite,' ',selitetark) = '{$laskurow["toimitusehto"]}'", "", "", "selitetark");
		if ($toimehto == "") $toimehto = $laskurow["toimitusehto"];
		else $toimehto = substr($laskurow["toimitusehto"], 0, 3)." ".$toimehto;

		$pdf_lasku->draw_text(310, $otsik[$rivi_ind], t("Toimitusehto", $kieli), $page_lasku[$sivu], $pieni);
		$pdf_lasku->draw_text(310, $arvo[$rivi_ind], pdf_substr($toimehto, 270, $pdf_lasku, $norm), $page_lasku[$sivu], $norm);
		$rivi_ind++;

		$pdf_lasku->draw_text(310, $otsik[$rivi_ind], t("Tilausviite", $kieli), 	$page_lasku[$sivu], $pieni);
		$pdf_lasku->draw_text(310, $arvo[$rivi_ind], pdf_substr($laskurow["viesti"], 270, $pdf_lasku, $norm), 		$page_lasku[$sivu], $norm);

		$pdf_lasku->draw_text(310, 595, t("Mahdolliset muistutukset tehtävä 8 päivän kuluessa", $kieli), $page_lasku[$sivu], $pieni);


		// Kommentit
		$komm = "";

		if ($kaanteinen_verotus) {
			$komm .= t("AVL 8c §", $kieli).":###".wordwrap(str_replace("\n","\n###", t_avainsana("KAANTALVVIESTI", $kieli, "", "", "", "selitetark")), 90, "\n###")."\n";
		}

		if (trim($laskurow['tilausyhteyshenkilo']) != '') {
			$komm .= "\n".t("Tilaaja", $kieli).":###".$laskurow['tilausyhteyshenkilo'];
		}

		if (trim($laskurow['asiakkaan_tilausnumero']) != '') {
			$komm .= "\n".t("Tilauksenne", $kieli).":###".$laskurow['asiakkaan_tilausnumero'];
		}

		if (trim($laskurow['kohde']) != '') {
			$komm .= "\n".t("Kohde", $kieli).":###".$laskurow['kohde'];
		}

		if (trim($laskurow['viesti']) != '' and $pdf_lasku->strlen($laskurow["viesti"], $norm) > 270) {
			$komm .= "\n".t("Tilausviite", $kieli).":###".$laskurow['viesti'];
		}

		if (trim($laskurow['sisviesti1']) != '') {
			$komm .= "\n".t("Kommentti", $kieli).":###".wordwrap(str_replace("\n","\n###", $laskurow['sisviesti1']), 90, "\n###");
		}

		// Tutkitaan onko tämä työmääräys
		if ($laskurow["tila"] == 'U') {
			$where = " lasku.laskunro = '$laskurow[laskunro]' ";
		}
		else {
			$where = " lasku.tunnus = '$laskurow[tunnus]' ";
		}

		$query = "	SELECT tyomaarays.*
					FROM lasku
					JOIN tyomaarays ON lasku.yhtio=tyomaarays.yhtio and lasku.tunnus=tyomaarays.otunnus
					WHERE lasku.yhtio = '$kukarow[yhtio]'
					and lasku.tilaustyyppi = 'A'
					and $where";
		$tyomres = pupe_query($query);

		if (mysql_num_rows($tyomres) > 0) {

			while ($tyomrow = mysql_fetch_assoc($tyomres)) {

				$komm .= "\n".t("Työmääräys", $kieli). ":###$tyomrow[otunnus]";

				$al_res = t_avainsana("TYOM_TYOKENTAT", $kieli, "and avainsana.selitetark != '' and avainsana.nakyvyys in ('K','L')");

				while ($al_row = mysql_fetch_assoc($al_res)) {

					$kentta = $al_row["selite"];

					if (((!is_numeric($tyomrow[$kentta]) and trim($tyomrow[$kentta]) != '') or (is_numeric($tyomrow[$kentta]) and $tyomrow[$kentta] != 0)) and trim($tyomrow[$kentta]) != '0000-00-00') {
						if (strtoupper($al_row["selitetark_2"]) == "TEXT") {
							$komm .= "\n$al_row[selitetark]:###".wordwrap(str_replace("\n","\n###", $tyomrow[$kentta]), 90, "\n###");
						}
						else {
							if (strtoupper($al_row["selitetark_2"]) == "DATE") {
								$tyomrow[$kentta] = tv1dateconv($tyomrow[$kentta]);
							}
							elseif ($kentta == "suorittaja") {
								$query = "	SELECT nimi
											FROM kuka
											WHERE yhtio = '$kukarow[yhtio]'
											and kuka  	= '".$tyomrow[$kentta]."'";
								$yresult = pupe_query($query);
								$tmrow = mysql_fetch_assoc($yresult);

								$tyomrow[$kentta] = $tmrow["nimi"];
							}
							elseif ($kentta == "merkki") {
								$yresult = t_avainsana("SARJANUMERON_LI", $kieli, "and avainsana.selite = 'MERKKI' and avainsana.selitetark = '".$tyomrow[$kentta]."'");

								if (mysql_num_rows($yresult) > 0) {
									$tmrow = mysql_fetch_assoc($yresult);

									$tyomrow[$kentta] = $tmrow["selitetark_2"];
								}
							}

							$komm .= "\n$al_row[selitetark]:###$tyomrow[$kentta]";
						}
					}
				}
			}

			$komm .= "\n";
		}

		if (isset($asiakasrow['laskutyyppi']) and $asiakasrow['laskutyyppi'] != -9) {
			$laskutyyppi = $asiakasrow['laskutyyppi'];
		}
		else {
			$laskutyyppi = $yhtiorow['laskutyyppi'];
		}

		if ($laskutyyppi == 1 or $laskutyyppi == 4 or $laskutyyppi == 6 or $laskutyyppi == 8) {
			$query 		= "	SELECT ifnull(group_concat(distinct tunnus SEPARATOR ', '), 0) tilnot
							FROM lasku
							WHERE yhtio = '$kukarow[yhtio]'
							and laskunro = '$laskurow[laskunro]'
							and tila = 'L'
							and laskunro != 0";
			$result  	= pupe_query($query);
			$apulaskuro = mysql_fetch_assoc($result);

			if ($apulaskuro["tilnot"] != "0") {
				$komm .= "\n".t("Tilausnumero(t)", $kieli). ":###".wordwrap(str_replace("\n","\n###", $apulaskuro["tilnot"]), 90, "\n###");
			}
		}

		//Tulostetaan laskun kommentti
		if (trim($komm) != '') {
			$kommentit = explode("\n", trim($komm));

			$maxoik = 0;

			foreach($kommentit as $kommentti) {
				if (strpos($kommentti, "###") !== FALSE) {
					list($o, $v) = explode("###", $kommentti);

					$oikpos = $pdf_lasku->strlen($o, $boldi);

					if ($oikpos > $maxoik) {
						$maxoik = $oikpos;
					}
				}
			}

			foreach ($kommentit as $kommentti) {

				if ($pohja <= 130) {
					loppu($laskurow, $asiakasrow, $kieli, $pdf_lasku, $toim, $page_lasku[$sivu], $masrow, $sivu, $summa, $arvo, $page_lasku, $vientikala, $laskutyyppi, "");

					$sivu++;

					list($page_lasku[$sivu], $pohja) = uusi_sivu($laskurow, $asiakasrow, $kieli, $pdf_lasku, $toim, $laskutyyppi, 'X');
				}

				if (strpos($kommentti, "###") !== false) {
					list($o, $v) = explode("###", $kommentti);

					$pdf_lasku->draw_text(35, $pohja, trim($o), $page_lasku[$sivu], $boldi);
					$pdf_lasku->draw_text(35+$maxoik+5, $pohja, trim($v), $page_lasku[$sivu], $norm);
				}
				else {
					$pdf_lasku->draw_text(35, $pohja, trim($kommentti), $page_lasku[$sivu], $norm);
				}

				$pohja = $pohja - 10;
			}

			$kala = $pohja-10;

		}
		else {
			if ($osoitekenttia == 3) {
				$kala = 510;
			}
			else {
				$kala = 565;
			}
		}

		list($page_lasku[$sivu], $kala) = rivi_otsikot($laskurow, $asiakasrow, $kieli, $pdf_lasku, $page_lasku[$sivu], $kala);

		return array($page_lasku, $sivu, $kala);
	}
}

if (!function_exists('uusi_sivu')) {
	function uusi_sivu ($laskurow, $asiakasrow, $kieli, $pdf_lasku, $toim, $laskutyyppi, $alvierittely = '') {
		global $yhtiorow, $kukarow, $rectparam, $norm, $pieni, $boldi, $pieni_boldi, $iso;

		$thispage = $pdf_lasku->new_page("a4");

		tulosta_logo_pdf($pdf_lasku, $thispage, $laskurow);

		//Otsikko
		if ($laskurow["vienti"] == "") {
			if ($laskurow["summa"] >= 0) {
				if ($toim == 'PROFORMA') {
					$pdf_lasku->draw_text(310, 815, t("Proformalasku", $kieli), $thispage);
				}
				else {
					$pdf_lasku->draw_text(310, 815, t("Lasku", $kieli), $thispage);
				}
			}
			else {
				$pdf_lasku->draw_text(310, 815, t("Hyvityslasku", $kieli), $thispage);
			}
		}
		else {
			if ($toim == 'PROFORMA') {
				$pdf_lasku->draw_text(310, 815, t("Proformalasku", $kieli), $thispage);
			}
			elseif ($laskurow["kateistyyppi"] != '') {
				$pdf_lasku->draw_text(310, 815, t("Käteiskuitti", $kieli),  $thispage);
			}
			elseif ($laskurow["summa"] >= 0) {
				$pdf_lasku->draw_text(310, 815, t("Vientilasku", $kieli), $thispage);
			}
			else {
				$pdf_lasku->draw_text(310, 815, t("Viennin hyvityslasku", $kieli), $thispage);
			}
		}

		//Oikea sarake
		$pdf_lasku->draw_rectangle(800, 300, 779, 580, 					$thispage, $rectparam);
		$pdf_lasku->draw_rectangle(800, 420, 779, 580, 					$thispage, $rectparam);
		$pdf_lasku->draw_text(310, 792, t("Laskun pvm", $kieli), 			$thispage, $pieni);
		$pdf_lasku->draw_text(310, 782, tv1dateconv($laskurow["tapvm"]),	$thispage, $norm);
		$pdf_lasku->draw_text(430, 792, t("Eräpäivä", $kieli), 			$thispage, $pieni);

		if ($laskurow["kateistyyppi"] != '') {
			$erapaiva = t('MAKSETTU', $kieli);
		}
		else {
			$erapaiva = tv1dateconv($laskurow["erpcm"]);
		}

		$pdf_lasku->draw_text(430, 782, $erapaiva, $thispage, $norm);

		$pdf_lasku->draw_rectangle(779, 300, 758, 580, 				$thispage, $rectparam);
		$pdf_lasku->draw_rectangle(779, 420, 758, 580, 				$thispage, $rectparam);

		if ($laskutyyppi != "8") {
			$pdf_lasku->draw_text(310, 771, t("Viitenumero", $kieli), 	$thispage, $pieni);
			$pdf_lasku->draw_text(310, 761, $laskurow["viite"], 			$thispage, $boldi);
		}

		$pdf_lasku->draw_text(430, 771, t("Laskun numero", $kieli), 	$thispage, $pieni);
		$pdf_lasku->draw_text(430, 761, $laskurow["laskunro"], 		$thispage, $boldi);

		$kala = 735;

		if ($alvierittely == '') {
			list($thispage, $kala) = rivi_otsikot($laskurow, $asiakasrow, $kieli, $pdf_lasku, $thispage, $kala);
		}

		return array($thispage, $kala);
	}
}

if (!function_exists('rivi_lasku')) {
	function rivi_lasku ($laskurow, $asiakasrow, $row, $kieli, $pdf_lasku, $thispage, $kala, $laskutyyppi, $toim, $summa, $arvo) {
		global $yhtiorow, $kukarow, $rectparam, $norm, $pieni, $boldi, $pieni_boldi, $iso;

		if($laskutyyppi == 10 or $laskutyyppi == 12) {
			$row["hinta"] = 0;
			$row["rivihinta"] = 0;
			for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
				$row["ale{$alepostfix}"] = 0;
			}
		}
		
		$row['nimitys'] = t_tuotteen_avainsanat($row, 'nimitys', $kieli);

		if ($row["vienti_kurssi"] > 0) {
			$kurssi = $row["vienti_kurssi"];
		}
		else {
			$kurssi = 1;
		}

		if ($yhtiorow['myynnin_alekentat'] > 1 or $row['erikoisale'] > 0) {

			$alekomm = "";

			for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
				if (trim($row["ale{$alepostfix}"]) > 0) {
					$alekomm .= t("Ale")."{$alepostfix} ".($row["ale{$alepostfix}"]*1)." %\n";
				}
			}

			if ($row['erikoisale'] > 0) {
				$alekomm .= t("Erikoisale")." ".($row["erikoisale"]*1)." %\n";
			}

			$row["kommentti"] = $alekomm.$row["kommentti"];
		}

		if ($row['tuoteno'] != $yhtiorow["kuljetusvakuutus_tuotenumero"] and $row['tuoteno'] != $yhtiorow["laskutuslisa_tuotenumero"] and trim($row['laskuviesti']) != '' and strpos($row["kommentti"], "$row[laskuviesti]") === FALSE and $laskutyyppi != 6) {
			$row["kommentti"] = trim($row["laskuviesti"])."\n".$row["kommentti"];
		}

		if ($row['tuoteno'] != $yhtiorow["kuljetusvakuutus_tuotenumero"] and $row['tuoteno'] != $yhtiorow["laskutuslisa_tuotenumero"] and trim($row['asiakkaan_tilausnumero']) != '' and strpos($row["kommentti"], "$row[asiakkaan_tilausnumero]") === FALSE and $laskutyyppi != 6) {
			$row["kommentti"] = trim($row["asiakkaan_tilausnumero"])."\n".$row["kommentti"];
		}

		$row["kommentti"] = trim($row["kommentti"]);

		// Kommentissa voi olla jotain sanoja jotka pitää käääntää:
		if ($kieli != $yhtiorow["kieli"]) {
			$row["kommentti"] = str_replace("Ennakkotilaus:", t("Ennakkotilaus", $kieli).":", $row["kommentti"]);
			$row["kommentti"] = str_replace("Tilaus:", t("Tilaus", $kieli).":", $row["kommentti"]);
			$row["kommentti"] = str_replace("Rahtikirja:", t("Rahtikirja", $kieli).":", $row["kommentti"]);
		}

		// Jos meillä on marginaalimyyntiä/käänteinen alv
		if ($row["alv"] >= 600) {
			$row["kommentti"] .= "\n".t("Ei lisättyä arvonlisäveroa, ostajan käännetty verovelvollisuus", $kieli).".";
		}
		elseif ($row["alv"] >= 500) {
			$row["kommentti"] .= "\n".t("Ei sisällä vähennettävää veroa", $kieli).".";
		}

		//Haetan sarjanumeron tiedot
		if ($row["sarjanumeroseuranta"] != "") {
			if ($row["uusiotunnus"] > 0 and $row["osto_vai_hyvitys"] == '' and $row["kpl"] < 0 and $row["var2"] == "EIOST") {
				$sarjanutunnus = "ostorivitunnus";
			}
			elseif ($row["uusiotunnus"] > 0 and $row["osto_vai_hyvitys"] == '' and $row["kpl"] < 0 and ($row['sarjanumeroseuranta'] == 'S' or $row['sarjanumeroseuranta'] == 'U' or $row['sarjanumeroseuranta'] == 'G')) {
				$sarjanutunnus = "myyntirivitunnus";
			}
			elseif ($row["kpl"] < 0){
				$sarjanutunnus = "ostorivitunnus";
			}
			else {
				$sarjanutunnus = "myyntirivitunnus";
			}

			$query = "	SELECT DISTINCT sarjanumero, parasta_ennen
						FROM sarjanumeroseuranta
						WHERE yhtio 		= '$kukarow[yhtio]'
						and tuoteno 		= '$row[tuoteno]'
						and $sarjanutunnus	in ($row[rivitunnukset])
						and sarjanumero    != ''
						ORDER BY sarjanumero";
			$sarjares = pupe_query($query);

			if (mysql_num_rows($sarjares) > 0) {
				if ($row["kommentti"] != '') {
					$row["kommentti"] .= "\n";
				}

				if ($row["sarjanumeroseuranta"] == "E" or $row["sarjanumeroseuranta"] == "F" or $row["sarjanumeroseuranta"] == "G") {
					$row["kommentti"] .= t("E:nro", $kieli).": ";
				}
				else {
					$row["kommentti"] .= t("S:nro", $kieli).": ";
				}

				while ($sarjarow = mysql_fetch_assoc($sarjares)) {
					if ($row["sarjanumeroseuranta"] == "F") {
						$row["kommentti"] .= $sarjarow["sarjanumero"]." ".t("Parasta ennen", $kieli).": ".tv1dateconv($sarjarow["parasta_ennen"]).", ";
					}
					else {
						$row["kommentti"] .= $sarjarow["sarjanumero"].", ";
					}
				}

				$row["kommentti"] = substr($row["kommentti"], 0, -2);
			}
		}

		if ($laskutyyppi == 7) {

			if ($row["eankoodi"] != "") {
				$row["kommentti"] = "EAN: $row[eankoodi]\n$row[kommentti]";
			}

			$query = "	SELECT kommentti
						FROM asiakaskommentti
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tuoteno = '{$row['tuoteno']}'
						AND ytunnus = '{$laskurow['ytunnus']}'
						ORDER BY tunnus";
			$asiakaskommentti_res = pupe_query($query);

			if (mysql_num_rows($asiakaskommentti_res) > 0) {
				while ($asiakaskommentti_row = mysql_fetch_assoc($asiakaskommentti_res)) {
					$row["kommentti"] .= "\n".$asiakaskommentti_row['kommentti'];
				}
			}
		}

		if ($laskutyyppi == 1) {
			list($thispage, $kala, $summa, $arvo) = rivi_plain($laskurow, $asiakasrow, $row, $kieli, $pdf_lasku, $thispage, $kala, $laskutyyppi, $toim, $summa, $arvo, $kurssi);
		}
		elseif ($laskutyyppi == 2 or $laskutyyppi == 12) {
			list($thispage, $kala, $summa, $arvo) = rivi_perhe($laskurow, $asiakasrow, $row, $kieli, $pdf_lasku, $thispage, $kala, $laskutyyppi, $toim, $summa, $arvo, $kurssi);
		}
		elseif ($laskutyyppi == 4 or $laskutyyppi == 8) {
			list($thispage, $kala, $summa, $arvo) = rivi_plain_2($laskurow, $asiakasrow, $row, $kieli, $pdf_lasku, $thispage, $kala, $laskutyyppi, $toim, $summa, $arvo, $kurssi);
		}
		else {
			list($thispage, $kala, $summa, $arvo) = rivi_original($laskurow, $asiakasrow, $row, $kieli, $pdf_lasku, $thispage, $kala, $laskutyyppi, $toim, $summa, $arvo, $kurssi);
		}

		return array($thispage, $kala, $summa, $arvo);
	}
}

if (!function_exists('rivi_otsikot')) {
	function rivi_otsikot ($laskurow, $asiakasrow, $kieli, $pdf_lasku, $thispage, $kala) {
		global $yhtiorow, $kukarow, $rectparam, $norm, $pieni, $boldi, $pieni_boldi, $iso;

		if (isset($asiakasrow['laskutyyppi']) and $asiakasrow['laskutyyppi'] != -9) {
			$laskutyyppi = $asiakasrow['laskutyyppi'];
		}
		else {
			$laskutyyppi = $yhtiorow['laskutyyppi'];
		}

		//Laskurivien otsikkotiedot
		if ($laskutyyppi == 1 or $laskutyyppi == 4 or $laskutyyppi == 8) {
			//eka rivi
			$pdf_lasku->draw_text(20,  $kala, t("Tuotenimi", $kieli),				$thispage, $pieni);

			if ($laskutyyppi == 1) {
				$pdf_lasku->draw_text(210, $kala, t("Tuotenumero", $kieli),			$thispage, $pieni);
			}
			else {
				$pdf_lasku->draw_text(250, $kala, t("Tuotenumero", $kieli),			$thispage, $pieni);
			}
			$pdf_lasku->draw_text(360, $kala, t("Määrä", $kieli),					$thispage, $pieni);
			$pdf_lasku->draw_text(430, $kala, t("A-hinta", $kieli),					$thispage, $pieni);
			$pdf_lasku->draw_text(475, $kala, t("Alennus", $kieli)."-%",			$thispage, $pieni);
			$pdf_lasku->draw_text(530, $kala, t("Veroton arvo", $kieli),			$thispage, $pieni);

			//toka rivi
			$pdf_lasku->draw_text(20,  $kala-9, t("Lisätietoja", $kieli),			$thispage, $pieni);
		}
		else {
			//eka rivi
			$pdf_lasku->draw_text(35,  $kala, t("Tuotenimi", $kieli),			$thispage, $pieni);
			$pdf_lasku->draw_text(190, $kala, t("Tuotenumero", $kieli),			$thispage, $pieni);
			$pdf_lasku->draw_text(340, $kala, t("Määrä", $kieli),				$thispage, $pieni);
			$pdf_lasku->draw_text(390, $kala, t("A-hinta", $kieli),				$thispage, $pieni);
			$pdf_lasku->draw_text(450, $kala, t("Alennus", $kieli)."-%",		$thispage, $pieni);
			$pdf_lasku->draw_text(490, $kala, t("Veroton arvo", $kieli),		$thispage, $pieni);
			$pdf_lasku->draw_text(550, $kala, t("Alv", $kieli)."-%",			$thispage, $pieni);

			//toka rivi
			if ($laskutyyppi != 6) {
				$pdf_lasku->draw_text(35,  $kala-9, t("Tilausnumero", $kieli)."/".t("Lisätietoja", $kieli),		$thispage, $pieni);
			}

			$pdf_lasku->draw_text(340, $kala-9, t("Yksikkö", $kieli),				$thispage, $pieni);

			if ($laskutyyppi != 6) {
				$pdf_lasku->draw_text(390, $kala-9, t("Toimituspvm", $kieli),		$thispage, $pieni);
			}

			if ($laskutyyppi != 5) {
				$pdf_lasku->draw_text(490, $kala-9, t("Verollinen arvo", $kieli),	$thispage, $pieni);
			}
		}

		$kala -= 25;

		return array($thispage, $kala);
	}
}

if (!function_exists('rivi_original')) {
	function rivi_original ($laskurow, $asiakasrow, $row, $kieli, $pdf_lasku, $thispage, $kala, $laskutyyppi, $toim, $summa, $arvo, $kurssi) {
		global $yhtiorow, $kukarow, $rectparam, $norm, $pieni, $boldi, $pieni_boldi, $iso;

		//Nimitys
		list($ff_string, $ff_font) = pdf_fontfit($row["nimitys"], 150, $pdf_lasku, $norm);
		$pdf_lasku->draw_text(35,  $kala, $ff_string, $thispage, $ff_font);

		//Tuoteno
		list($ff_string, $ff_font) = pdf_fontfit($row["tuoteno"], 145, $pdf_lasku, $norm);
		$pdf_lasku->draw_text(190, $kala, $ff_string, $thispage, $ff_font);

		//Määrä
		$pdf_lasku->draw_text(340, $kala, $row["kpl"], $thispage, $norm);

		// yksikköhinta pitää olla veroton
		if ($yhtiorow["alv_kasittely"] == '') {

			// Onko marginaaliverotusta
			$laskalv = $row["alv"];

			// Jos meillä on marginaalimyyntiä/käänteinen alv
			if ($row["alv"] >= 500) {
				$laskalv = 0;
			}

			if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
				$veroton_hinta = $row["hinta"] / (1 + $laskalv / 100);
			}
			else {
				$veroton_hinta = round($row["hinta"] / (1 + $laskalv / 100), 2);
			}
		}
		else {
			$veroton_hinta = $row["hinta"];
		}

		// A-hinta
		$pdf_lasku->draw_text(390, $kala, hintapyoristys(laskuval($veroton_hinta, $kurssi)),	$thispage, $norm);

		//Alennus
		$alennukset_yhteensa = generoi_alekentta_php($row, 'M', 'plain');
		if ($alennukset_yhteensa != 0) {
			$pdf_lasku->draw_text(450, $kala, $alennukset_yhteensa, $thispage, $norm);
		}

		if ($kurssi != 1) {
			//Veroton Rivihinta Valuutassa
			$oikpos = $pdf_lasku->strlen(hintapyoristys($row["rivihinta_valuutassa"]), $norm);
			$pdf_lasku->draw_text(535-$oikpos, $kala, hintapyoristys($row["rivihinta_valuutassa"]), $thispage, $norm);
		}
		else {
			// Veroton Rivihinta
			$oikpos = $pdf_lasku->strlen(hintapyoristys($row["rivihinta"]), $norm);
			$pdf_lasku->draw_text(535-$oikpos, $kala, hintapyoristys($row["rivihinta"]), $thispage, $norm);
		}

		//Arvonlisävero
		if ($row["alv"] >= 600) {
			$pdf_lasku->draw_text(550, $kala, t("K.V.", $kieli), $thispage, $norm);
		}
		elseif ($row["alv"] >= 500) {
			$pdf_lasku->draw_text(550, $kala, t("M.V.", $kieli), $thispage, $norm);
		}
		else {
			$pdf_lasku->draw_text(550, $kala, ($row["alv"]*1)."%", $thispage, $norm);
		}

		if ($row["myyntihinta_maara"] > 1) {
			$kala = $kala - 7;
			$pdf_lasku->draw_text(390, $kala, hintapyoristys($veroton_hinta*$row["myyntihinta_maara"])." / $row[myyntihinta_maara] $row[yksikko]", $thispage, $pieni);
			$kalakorjaus = 7;
		}
		else {
			$kalakorjaus = 0;
		}

		// Ensimmäinen rivi piirretty
		$kala = $kala - 10;

		//Yksikkö
		$pdf_lasku->draw_text(340, $kala, t_avainsana("Y", $kieli, "and avainsana.selite='$row[yksikko]'", "", "", "selitetark"),	$thispage, $pieni);

		//Toimitusaika
		if (substr($row["toimitettuaika"],0,10) != "0000-00-00" and $laskutyyppi != 6) {
			$pdf_lasku->draw_text(390, $kala, tv1dateconv(substr($row["toimitettuaika"],0,10)), 	$thispage, $pieni);
		}

		// Verollinen rivihinta
		if ($laskutyyppi != 5 and $row["alv"] < 500) {
			//Lasketaan saman kaavan mukaan kuin laskutus.inc:ssä, eli pyöristetään kaikki kerralla lopuksi!
			if ($kurssi != 1) {
				$verollrivi = $row["rivihinta_valuutassa"] * (1 + ($row["alv"] / 100));
			}
			else {
				$verollrivi = $row["rivihinta"] * (1 + ($row["alv"] / 100));
			}

			$oikpos = $pdf_lasku->strlen(hintapyoristys($verollrivi), $pieni);
			$pdf_lasku->draw_text(535-$oikpos, $kala, hintapyoristys($verollrivi), $thispage, $pieni);
		}

		// Kommentti
		if (trim($row["kommentti"]) != '') {

			$kommentit = explode("\n", trim($row["kommentti"]));

			if ($row['tuoteno'] != $yhtiorow["kuljetusvakuutus_tuotenumero"] and $row['tuoteno'] != $yhtiorow["laskutuslisa_tuotenumero"] and !preg_match("/".t("Tilaus", $kieli).":? ".$row["otunnus"]."/", $row["kommentti"])) {
				$pdf_lasku->draw_text(35, $kala+$kalakorjaus, t("Tilaus", $kieli).": ".$row["otunnus"], $thispage, $pieni);
				$kala = $kala - 10;
			}

			foreach ($kommentit as $akey => $kommentti) {

				if ($akey == 0) {
					$rpit1 = 300;
					$rpit2 = 250;
				}
				else {
					$rpit1 = 500;
					$rpit2 = 450;
				}

				// Jos rivikommentti on pitkä niin rivitämme sen
				if ($pdf_lasku->strlen($kommentti, $pieni) > $rpit1) {

					$kommentti = str_replace(' ' ,' ##', $kommentti);
					$kommentti = str_replace('.' ,'.##', $kommentti);
					$kommentti = str_replace(',' ,',##', $kommentti);

					$sanat = explode('##', $kommentti);
					$lause = "";

					foreach ($sanat as $sana) {
						if ($pdf_lasku->strlen($lause, $pieni) > $rpit2) {
							$pdf_lasku->draw_text(35, $kala+$kalakorjaus, trim($lause), $thispage, $pieni);
							$kala  = $kala - 10;
							$lause = "";
						}

						$lause .= $sana;
					}

					$kommentti = $lause;
				}

				if (trim($kommentti) != "") {
					$pdf_lasku->draw_text(35, $kala+$kalakorjaus, trim($kommentti), $thispage, $pieni);
					$kala = $kala - 10;
				}
			}
			$kala += 10;
		}
		elseif ($row['tuoteno'] != $yhtiorow["kuljetusvakuutus_tuotenumero"] and $row['tuoteno'] != $yhtiorow["laskutuslisa_tuotenumero"] and $laskutyyppi != 6) {
			$pdf_lasku->draw_text(35, $kala+$kalakorjaus, t("Tilaus", $kieli).": ".$row["otunnus"], $thispage, $pieni);
		}

		$kala = $kala - 15;

		// Jos meillä on marginaalimyyntiä/käänteinen alv
		$laskalv = $row["alv"];

		if ($row["alv"] >= 500) {
			$laskalv = 0;
		}

		if ($kurssi != 1) {
			$arvo += hintapyoristys($row["rivihinta_valuutassa"]);
			$summa += hintapyoristys($row["rivihinta_valuutassa"]*(1+($laskalv/100)));
		}
		else {
			$arvo += hintapyoristys($row["rivihinta"]);
			$summa += hintapyoristys($row["rivihinta"]*(1+($laskalv/100)));
		}

		return array($thispage, $kala, $summa, $arvo);
	}
}

if (!function_exists('rivi_plain')) {
	function rivi_plain ($laskurow, $asiakasrow, $row, $kieli, $pdf_lasku, $thispage, $kala, $laskutyyppi, $toim, $summa, $arvo, $kurssi) {
		global $yhtiorow, $kukarow, $rectparam, $norm, $pieni, $boldi, $pieni_boldi, $iso;

		//Nimitys
		list($ff_string, $ff_font) = pdf_fontfit($row["nimitys"], 180, $pdf_lasku, $norm);
		$pdf_lasku->draw_text(20,  $kala, $ff_string, $thispage, $ff_font);

		//Tuoteno
		list($ff_string, $ff_font) = pdf_fontfit($row["tuoteno"], 145, $pdf_lasku, $norm);
		$pdf_lasku->draw_text(210, $kala, $ff_string, $thispage, $ff_font);

		$oikpos = $pdf_lasku->strlen($row["kpl"], $norm);
		$pdf_lasku->draw_text(380-$oikpos, $kala, $row["kpl"], $thispage, $norm);

		// yksikköhinta pitää olla veroton
		if ($yhtiorow["alv_kasittely"] == '') {

			// Jos meillä on marginaalimyyntiä/käänteinen alv
			$laskalv = $row["alv"];

			if ($row["alv"] >= 500) {
				$laskalv = 0;
			}

			if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
				$veroton_hinta = $row["hinta"] / (1 + $laskalv / 100);
			}
			else {
				$veroton_hinta = round($row["hinta"] / (1 + $laskalv / 100), 2);
			}
		}
		else {
			$veroton_hinta = $row["hinta"];
		}

		// A-hinta
		$oikpos = $pdf_lasku->strlen(hintapyoristys(laskuval($veroton_hinta, $kurssi)), $norm);
		$pdf_lasku->draw_text(455-$oikpos, $kala, hintapyoristys(laskuval($veroton_hinta, $kurssi)), $thispage, $norm);

		//Alennus
		$alennukset_yhteensa = generoi_alekentta_php($row, 'M', 'plain');
		if ($alennukset_yhteensa != 0) {
			$oikpos = $pdf_lasku->strlen(($alennukset_yhteensa*1), $norm);
			$pdf_lasku->draw_text(510-$oikpos, $kala, ($alennukset_yhteensa*1), 			$thispage, $norm);
		}

		if ($kurssi != 1) {
			//Veroton Rivihinta Valuutassa
			$oikpos = $pdf_lasku->strlen(hintapyoristys($row["rivihinta_valuutassa"]), $norm);
			$pdf_lasku->draw_text(575-$oikpos, $kala, hintapyoristys($row["rivihinta_valuutassa"]), $thispage, $norm);
		}
		else {
			// Veroton Rivihinta
			$oikpos = $pdf_lasku->strlen(hintapyoristys($row["rivihinta"]), $norm);
			$pdf_lasku->draw_text(575-$oikpos, $kala, hintapyoristys($row["rivihinta"]), $thispage, $norm);
		}

		if (trim($row["kommentti"]) != '') {
			$kommentit = explode("\n", trim($row["kommentti"]));
			$kala -= 10;

			foreach ($kommentit as $akey => $kommentti) {

				if ($akey == 0) {
					$rpit1 = 300;
					$rpit2 = 250;
				}
				else {
					$rpit1 = 500;
					$rpit2 = 450;
				}

				// Jos rivikommentti on pitkä niin rivitämme sen
				if ($pdf_lasku->strlen($kommentti, $pieni) > $rpit1) {

					$kommentti = str_replace(' ' ,' ##', $kommentti);
					$kommentti = str_replace('.' ,'.##', $kommentti);
					$kommentti = str_replace(',' ,',##', $kommentti);

					$sanat = explode('##', $kommentti);
					$lause = "";

					foreach ($sanat as $sana) {
						if ($pdf_lasku->strlen($lause, $pieni) > $rpit2) {
							$pdf_lasku->draw_text(20, $kala, trim($lause), $thispage, $pieni);
							$kala  = $kala - 10;
							$lause = "";
						}

						$lause .= $sana;
					}

					$kommentti = $lause;
				}

				if (trim($kommentti) != "") {
					$pdf_lasku->draw_text(20, $kala, trim($kommentti), $thispage, $pieni);
					$kala = $kala - 10;
				}
			}
			$kala += 10;
		}

		$kala = $kala - 15;

		// Jos meillä on marginaalimyyntiä/käänteinen alv
		$laskalv = $row["alv"];

		if ($row["alv"] >= 500) {
			$laskalv = 0;
		}

		if ($kurssi != 1) {
			$arvo += hintapyoristys($row["rivihinta_valuutassa"]);
			$summa += hintapyoristys($row["rivihinta_valuutassa"]*(1+($laskalv/100)));
		}
		else {
			$arvo += hintapyoristys($row["rivihinta"]);
			$summa += hintapyoristys($row["rivihinta"]*(1+($laskalv/100)));
		}

		return array($thispage, $kala, $summa, $arvo);
	}
}

if (!function_exists('rivi_plain_2')) {
	function rivi_plain_2 ($laskurow, $asiakasrow, $row, $kieli, $pdf_lasku, $thispage, $kala, $laskutyyppi, $toim, $summa, $arvo, $kurssi) {
		global $yhtiorow, $kukarow, $rectparam, $norm, $pieni, $boldi, $pieni_boldi, $iso;

		//Tuoteno
		list($ff_string, $ff_font) = pdf_fontfit($row["tuoteno"], 100, $pdf_lasku, $norm);
		$pdf_lasku->draw_text(250, $kala, $ff_string, $thispage, $ff_font);

		$oikpos = $pdf_lasku->strlen($row["kpl"], $norm);
		$pdf_lasku->draw_text(380-$oikpos, $kala, $row["kpl"], 			$thispage, $norm);

		// yksikköhinta pitää olla veroton
		if ($yhtiorow["alv_kasittely"] == '') {

			// Jos meillä on marginaalimyyntiä/käänteinen alv
			$laskalv = $row["alv"];

			if ($row["alv"] >= 500) {
				$laskalv = 0;
			}

			if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
				$veroton_hinta = $row["hinta"] / (1 + $laskalv / 100);
			}
			else {
				$veroton_hinta = round($row["hinta"] / (1 + $laskalv / 100), 2);
			}
		}
		else {
			$veroton_hinta = $row["hinta"];
		}

		// A-hinta
		$oikpos = $pdf_lasku->strlen(hintapyoristys(laskuval($veroton_hinta, $kurssi)), $norm);
		$pdf_lasku->draw_text(455-$oikpos, $kala, hintapyoristys(laskuval($veroton_hinta, $kurssi)), $thispage, $norm);

		//Alennus
		$alennukset_yhteensa = generoi_alekentta_php($row, 'M', 'plain');
		if ($alennukset_yhteensa != 0) {
			$oikpos = $pdf_lasku->strlen(($alennukset_yhteensa*1), $norm);
			$pdf_lasku->draw_text(510-$oikpos, $kala, ($alennukset_yhteensa*1), 			$thispage, $norm);
		}

		if ($kurssi != 1) {
			//Veroton Rivihinta Valuutassa
			$oikpos = $pdf_lasku->strlen(hintapyoristys($row["rivihinta_valuutassa"]), $norm);
			$pdf_lasku->draw_text(575-$oikpos, $kala, hintapyoristys($row["rivihinta_valuutassa"]), $thispage, $norm);
		}
		else {
			// Veroton Rivihinta
			$oikpos = $pdf_lasku->strlen(hintapyoristys($row["rivihinta"]), $norm);
			$pdf_lasku->draw_text(575-$oikpos, $kala, hintapyoristys($row["rivihinta"]), $thispage, $norm);
		}

		// rivitetään tuotenimitys jos se ei mahdu lokeroonsa
		// Nimitys
		if ($pdf_lasku->strlen($row['nimitys'], $norm) > 200) {
			$pohja = $pdf_lasku->draw_paragraph($kala+$norm["height"], 20, 60, 200, $row['nimitys'],	$thispage, $norm);
			$kala = $pohja - $rivinkorkeus;
		}
		else {
			// lyhyt nimitys
			$pdf_lasku->draw_text(20,  $kala, $row['nimitys'],	$thispage, $norm);
		}

		if (trim($row["kommentti"]) != '') {
			$kommentit = explode("\n", trim($row["kommentti"]));
			$kala -= 10;

			foreach ($kommentit as $akey => $kommentti) {

				if ($akey == 0) {
					$rpit1 = 300;
					$rpit2 = 250;
				}
				else {
					$rpit1 = 500;
					$rpit2 = 450;
				}

				// Jos rivikommentti on pitkä niin rivitämme sen
				if ($pdf_lasku->strlen($kommentti, $pieni) > $rpit1) {

					$kommentti = str_replace(' ' ,' ##', $kommentti);
					$kommentti = str_replace('.' ,'.##', $kommentti);
					$kommentti = str_replace(',' ,',##', $kommentti);

					$sanat = explode('##', $kommentti);
					$lause = "";

					foreach ($sanat as $sana) {
						if ($pdf_lasku->strlen($lause, $pieni) > $rpit2) {
							$pdf_lasku->draw_text(20, $kala, trim($lause), $thispage, $pieni);
							$kala  = $kala - 10;
							$lause = "";
						}

						$lause .= $sana;
					}

					$kommentti = $lause;
				}

				if (trim($kommentti) != "") {
					$pdf_lasku->draw_text(20, $kala, trim($kommentti), $thispage, $pieni);
					$kala = $kala - 10;
				}
			}
			$kala += 10;
		}

		$kala = $kala - 15;

		// Jos meillä on marginaalimyyntiä/käänteinen alv
		$laskalv = $row["alv"];

		if ($row["alv"] >= 500) {
			$laskalv = 0;
		}

		if ($kurssi != 1) {
			$arvo += hintapyoristys($row["rivihinta_valuutassa"]);
			$summa += hintapyoristys($row["rivihinta_valuutassa"]*(1+($laskalv/100)));
		}
		else {
			$arvo += hintapyoristys($row["rivihinta"]);
			$summa += hintapyoristys($row["rivihinta"]*(1+($laskalv/100)));
		}

		return array($thispage, $kala, $summa, $arvo);
	}
}

if (!function_exists('rivi_perhe')) {
	function rivi_perhe ($laskurow, $asiakasrow, $row, $kieli, $pdf_lasku, $thispage, $kala, $laskutyyppi, $toim, $summa, $arvo, $kurssi) {
		global $yhtiorow, $kukarow, $rectparam, $norm, $pieni, $boldi, $pieni_boldi, $iso;

		$lisataa = 0;

		if ($row["perheid"] > 0) {
			// kyseessä on isä
			if ($row["perheid"] == $row["tunnus"]) {
				// lasketaan isätuotteen riville lapsien hinnat yhteen
				$query = "	SELECT
							sum(tilausrivi.rivihinta) rivihinta,
							round(sum(tilausrivi.rivihinta) / $row[kpl], '$yhtiorow[hintapyoristys]') hinta
							FROM tilausrivi
							WHERE tilausrivi.yhtio 		= '$kukarow[yhtio]'
							and tilausrivi.uusiotunnus 	= '$row[uusiotunnus]'
							and tilausrivi.perheid 		in ($row[perheideet])
							and tilausrivi.perheid 		> 0";
				$riresult = pupe_query($query);
				$perherow = mysql_fetch_assoc($riresult);

				$row["hinta"] 		= $perherow["hinta"];
				$row["rivihinta"] 	= $perherow["rivihinta"];

				// Nollataan alet, koska hinta lasketaan rivihinnasta jossa alet on jo huomioitu
				for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
					$row["ale{$alepostfix}"] = "";
				}

				$row["erikoisale"] = "";
			}
			else {
				// lapsia ei lisätä
				$lisataa = 1;
			}
		}

		if ($lisataa == 0) {
			list($thispage, $kala, $summa, $arvo) = rivi_original($laskurow, $asiakasrow, $row, $kieli, $pdf_lasku, $thispage, $kala, $laskutyyppi, $toim, $summa, $arvo, $kurssi);
		}

		return array($thispage, $kala, $summa, $arvo);
	}
}

if (!function_exists('loppu')) {
	function loppu ($laskurow, $asiakasrow, $kieli, $pdf_lasku, $toim, $thispage, $masrow, $sivu, $summa, $arvo, $page, $vientikala, $laskutyyppi, $vikasivu = "") {
		global $yhtiorow, $kukarow, $rectparam, $norm, $pieni, $boldi, $pieni_boldi, $iso;

		if ($yhtiorow["viivakoodi_laskulle"] == "K" and $laskurow["maa"] == "FI" and $laskurow["summa"] > 0 and $vikasivu != "" and function_exists("imagecreate") and $laskurow["vienti"] == "" and strtoupper($laskurow["maa"]) == strtoupper($yhtiorow["maa"])) {
			$piirra_viivakoodi = true;
			$barcode_korkeus = 50;
		}
		else {
			$piirra_viivakoodi = false;
			$barcode_korkeus = 0;
		}

		if ($vikasivu != "") {
			for ($pp=1; $pp<=$sivu; $pp++) {
				$pdf_lasku->draw_text(500, 815, t("Sivu", $kieli).": $pp / $sivu", $page[$pp], $norm);
			}
		}

		//yhteensärivi
		$pdf_lasku->draw_rectangle(110+$vientikala+$barcode_korkeus, 20,  90+$vientikala+$barcode_korkeus, 580,	$thispage, $rectparam);
		$pdf_lasku->draw_rectangle(110+$vientikala+$barcode_korkeus, 207, 90+$vientikala+$barcode_korkeus, 580,	$thispage, $rectparam);
		$pdf_lasku->draw_rectangle(110+$vientikala+$barcode_korkeus, 394, 90+$vientikala+$barcode_korkeus, 580,	$thispage, $rectparam);
		$pdf_lasku->draw_rectangle(110+$vientikala+$barcode_korkeus, 540, 90+$vientikala+$barcode_korkeus, 580,	$thispage, $rectparam);

		$pdf_lasku->draw_text(30,  102+$vientikala+$barcode_korkeus, t("Eräpäivä", $kieli),	$thispage, $pieni);

		if ($laskurow["kateistyyppi"] != '') {
			$erapaiva = t('MAKSETTU', $kieli);
		}
		else {
			$erapaiva = tv1dateconv($laskurow["erpcm"]);
		}

		$pdf_lasku->draw_text(30,  92+$vientikala+$barcode_korkeus,  $erapaiva,		$thispage, $norm);

		if ($laskutyyppi != 8) {
			$pdf_lasku->draw_text(217, 102+$vientikala+$barcode_korkeus, t("Viitenumero", $kieli),	$thispage, $pieni);
			$pdf_lasku->draw_text(217, 92+$vientikala+$barcode_korkeus,  $laskurow["viite"],		$thispage, $boldi);
		}

		if ($vikasivu != "") {
			$pdf_lasku->draw_text(404, 92+$vientikala+$barcode_korkeus,  t("Yhteensä", $kieli).":",$thispage, $norm);

			if ($toim == 'PROFORMA') {
				if ($yhtiorow["laskunsummapyoristys"] != '' or $asiakasrow["laskunsummapyoristys"] != '') {
			        $summa = round($summa, 0);
				}

				$pdf_lasku->draw_text(490, 92+$vientikala + $barcode_korkeus,  sprintf('%.2f', $summa),			$thispage, $boldi);
			}
			elseif ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
				$oikpos = $pdf_lasku->strlen(sprintf('%.2f', $laskurow["summa_valuutassa"]), $boldi);
				$pdf_lasku->draw_text(535-$oikpos, 92+$vientikala+$barcode_korkeus, sprintf('%.2f', $laskurow["summa_valuutassa"]), $thispage, $boldi);
			}
			else {
				$oikpos = $pdf_lasku->strlen(sprintf('%.2f', $laskurow["summa"]), $boldi);
				$pdf_lasku->draw_text(535-$oikpos, 92+$vientikala+$barcode_korkeus, sprintf('%.2f', $laskurow["summa"]), $thispage, $boldi);
			}

			$pdf_lasku->draw_text(550, 92+$vientikala+$barcode_korkeus,  $laskurow["valkoodi"],				$thispage, $boldi);
		}
		else {
			$pdf_lasku->draw_text(470, 92+$vientikala+$barcode_korkeus,  t("Jatkuu", $kieli)."...",			$thispage, $boldi);
		}

		//Haetaan factoringsopimuksen tiedot
		if ($masrow["factoring"] != '') {
			$query = "	SELECT *
						FROM factoring
						WHERE yhtio 		= '$kukarow[yhtio]'
						and factoringyhtio 	= '$masrow[factoring]'
						and valkoodi 		= '$laskurow[valkoodi]'";
			$fres = pupe_query($query);
			$frow = mysql_fetch_assoc($fres);
		}
		else {
			unset($frow);
		}

		$pankkitiedot = array();

		//Laitetaan pankkiyhteystiedot kuntoon
		if ($masrow["factoring"] != "") {
			$pankkitiedot["pankkinimi1"]  =	$frow["pankkinimi1"];
			$pankkitiedot["pankkitili1"]  =	$frow["pankkitili1"];
			$pankkitiedot["pankkiiban1"]  =	$frow["pankkiiban1"];
			$pankkitiedot["pankkiswift1"] =	$frow["pankkiswift1"];
			$pankkitiedot["pankkinimi2"]  =	$frow["pankkinimi2"];
			$pankkitiedot["pankkitili2"]  =	$frow["pankkitili2"];
			$pankkitiedot["pankkiiban2"]  =	$frow["pankkiiban2"];
			$pankkitiedot["pankkiswift2"] = $frow["pankkiswift2"];
			$pankkitiedot["pankkinimi3"]  =	"";
			$pankkitiedot["pankkitili3"]  =	"";
			$pankkitiedot["pankkiiban3"]  =	"";
			$pankkitiedot["pankkiswift3"] =	"";
		}
		elseif ($masrow["pankkinimi1"] != "") {
			$pankkitiedot["pankkinimi1"]  =	$masrow["pankkinimi1"];
			$pankkitiedot["pankkitili1"]  =	$masrow["pankkitili1"];
			$pankkitiedot["pankkiiban1"]  =	$masrow["pankkiiban1"];
			$pankkitiedot["pankkiswift1"] =	$masrow["pankkiswift1"];
			$pankkitiedot["pankkinimi2"]  =	$masrow["pankkinimi2"];
			$pankkitiedot["pankkitili2"]  =	$masrow["pankkitili2"];
			$pankkitiedot["pankkiiban2"]  =	$masrow["pankkiiban2"];
			$pankkitiedot["pankkiswift2"] = $masrow["pankkiswift2"];
			$pankkitiedot["pankkinimi3"]  =	$masrow["pankkinimi3"];
			$pankkitiedot["pankkitili3"]  =	$masrow["pankkitili3"];
			$pankkitiedot["pankkiiban3"]  =	$masrow["pankkiiban3"];
			$pankkitiedot["pankkiswift3"] =	$masrow["pankkiswift3"];
		}
		else {
			$pankkitiedot["pankkinimi1"]  =	$yhtiorow["pankkinimi1"];
			$pankkitiedot["pankkitili1"]  =	$yhtiorow["pankkitili1"];
			$pankkitiedot["pankkiiban1"]  =	$yhtiorow["pankkiiban1"];
			$pankkitiedot["pankkiswift1"] =	$yhtiorow["pankkiswift1"];
			$pankkitiedot["pankkinimi2"]  =	$yhtiorow["pankkinimi2"];
			$pankkitiedot["pankkitili2"]  =	$yhtiorow["pankkitili2"];
			$pankkitiedot["pankkiiban2"]  =	$yhtiorow["pankkiiban2"];
			$pankkitiedot["pankkiswift2"] = $yhtiorow["pankkiswift2"];
			$pankkitiedot["pankkinimi3"]  =	$yhtiorow["pankkinimi3"];
			$pankkitiedot["pankkitili3"]  =	$yhtiorow["pankkitili3"];
			$pankkitiedot["pankkiiban3"]  =	$yhtiorow["pankkiiban3"];
			$pankkitiedot["pankkiswift3"] =	$yhtiorow["pankkiswift3"];
		}

		if ($vientikala == 0) {
			$tileja = -20;
		}
		else {
			$tileja = 0;
		}

		// ei factoroida niin normitiedot
		$pdf_lasku->draw_text(30,  102 + $tileja + $barcode_korkeus, t("Pankkiyhteys", $kieli),													$thispage, $pieni);

		$pdf_lasku->draw_text(30,  92 + $tileja + $barcode_korkeus, $pankkitiedot["pankkinimi1"]."  ".$pankkitiedot["pankkitili1"],						$thispage, $norm);

		if ($pankkitiedot["pankkiiban1"]) {
			$pdf_lasku->draw_text(217, 92 + $tileja + $barcode_korkeus, "IBAN: ".$pankkitiedot["pankkiiban1"],											$thispage, $norm);
		}
		if ($pankkitiedot["pankkiswift1"]) {
			$pdf_lasku->draw_text(404, 92 + $tileja + $barcode_korkeus, "SWIFT: ".$pankkitiedot["pankkiswift1"],										$thispage, $norm);
		}

		$pdf_lasku->draw_text(30,  82 + $tileja + $barcode_korkeus, $pankkitiedot["pankkinimi2"]."  ".$pankkitiedot["pankkitili2"],						$thispage, $norm);

		if ($pankkitiedot["pankkiiban2"]) {
			$pdf_lasku->draw_text(217, 82 + $tileja + $barcode_korkeus, "IBAN: ".$pankkitiedot["pankkiiban2"],											$thispage, $norm);
		}
		if ($pankkitiedot["pankkiswift2"]) {
			$pdf_lasku->draw_text(404, 82 + $tileja + $barcode_korkeus, "SWIFT: ".$pankkitiedot["pankkiswift2"],										$thispage, $norm);
		}

		$pdf_lasku->draw_text(30,  72 + $tileja + $barcode_korkeus, $pankkitiedot["pankkinimi3"]."  ".$pankkitiedot["pankkitili3"],						$thispage, $norm);

		if ($pankkitiedot["pankkiiban3"]) {
			$pdf_lasku->draw_text(217, 72 + $tileja + $barcode_korkeus, "IBAN: ".$pankkitiedot["pankkiiban3"],											$thispage, $norm);
		}
		if ($pankkitiedot["pankkiswift3"]) {
			$pdf_lasku->draw_text(404, 72 + $tileja + $barcode_korkeus, "SWIFT: ".$pankkitiedot["pankkiswift3"],										$thispage, $norm);
		}

		if ($pankkitiedot["pankkinimi2"].$pankkitiedot["pankkitili2"].$pankkitiedot["pankkiiban2"] != '' and $vientikala == 0) {
			$tileja-=10;
		}

		if ($pankkitiedot["pankkinimi3"].$pankkitiedot["pankkitili3"].$pankkitiedot["pankkitili3"] != '' and $vientikala == 0) {
			$tileja-=10;
		}

		if ($vientikala == 0) {
			$tileja+=20;
		}

		//Pankkiyhteystiedot
		$pdf_lasku->draw_rectangle(90+$vientikala+$barcode_korkeus, 20, 20+$tileja+$barcode_korkeus, 580,	$thispage, $rectparam);

		// yhtiötiedot
		$y_tiedot_ok	= FALSE;
		$y_nimi 		= $laskurow["yhtio_nimi"];
		$y_osoite 		= $laskurow["yhtio_osoite"];
		$y_postino	 	= $laskurow["yhtio_postino"];
		$y_postitp	 	= $laskurow["yhtio_postitp"];
		$y_maa 			= $laskurow["yhtio_maa"];
		$y_kotipaikka	= $laskurow["yhtio_kotipaikka"];
		$y_vatnumero	= str_replace("0037", "", $laskurow["yhtio_ovttunnus"]);
		$y_iban 		= $yhtiorow['pankkiiban1'];
		$y_swift 		= $yhtiorow['pankkiswift1'];

		if ((int) $laskurow["yhtio_toimipaikka"] != 0) {
			// haetaan toimipaikan tiedot
			$alhqur = "SELECT puhelin, fax, email, www from yhtion_toimipaikat where yhtio='$laskurow[yhtio]' and tunnus='$laskurow[yhtio_toimipaikka]'";
			$alhire = mysql_query($alhqur) or pupe_error($alhqur);

			if (mysql_num_rows($alhire) == 1) {
				$alhiro = mysql_fetch_assoc($alhire);

				$y_tiedot_ok	= TRUE;
				$y_puhelin		= $alhiro['puhelin'];
				$y_fax			= $alhiro['fax'];
				$y_email		= $alhiro['email'];
				$y_www			= $alhiro['www'];
			}
		}

		// jos meillä on lasku menossa ulkomaille ja se laskutetaan eri ovttunnuksella
		if ($laskurow["yhtio_ovttunnus"] != "" and $laskurow["yhtio_ovttunnus"] != $yhtiorow["ovttunnus"]) {
			// haetaan toimipaikan tiedot
			$alhqur = "SELECT puhelin, fax, email, www from yhtion_toimipaikat where yhtio='$laskurow[yhtio]' and vat_numero='$laskurow[yhtio_ovttunnus]'";
			$alhire = mysql_query($alhqur) or pupe_error($alhqur);

			if (mysql_num_rows($alhire) == 1) {
				$alhiro = mysql_fetch_assoc($alhire);

				$y_tiedot_ok	= TRUE;
				$y_puhelin		= $alhiro['puhelin'];
				$y_fax			= $alhiro['fax'];
				$y_email		= $alhiro['email'];
				$y_www			= $alhiro['www'];
			}
		}

		// jos ei olla haettu muualta, niin haetaan tiedot yhtiöltä
		if (!$y_tiedot_ok) {
			// haetaan toimipaikan tiedot
			$alhqur = "SELECT puhelin, fax, email, www from yhtio where yhtio='$laskurow[yhtio]'";
			$alhire = mysql_query($alhqur) or pupe_error($alhqur);

			if (mysql_num_rows($alhire) == 1) {
				$alhiro = mysql_fetch_assoc($alhire);

				$y_puhelin		= $alhiro['puhelin'];
				$y_fax			= $alhiro['fax'];
				$y_email		= $alhiro['email'];
				$y_www			= $alhiro['www'];
			}
		}

		//Alimmat kolme laatikkoa, yhtiötietoja
		$pdf_lasku->draw_rectangle(70 + $tileja + $barcode_korkeus, 20, 20 + $tileja + $barcode_korkeus, 580,	$thispage, $rectparam);
		$pdf_lasku->draw_rectangle(70 + $tileja + $barcode_korkeus, 207, 20 + $tileja + $barcode_korkeus, 580,	$thispage, $rectparam);
		$pdf_lasku->draw_rectangle(70 + $tileja + $barcode_korkeus, 394, 20 + $tileja + $barcode_korkeus, 580,	$thispage, $rectparam);

		$pdf_lasku->draw_text(30, 55 + $tileja + $barcode_korkeus, $y_nimi,									$thispage, $pieni);
		$pdf_lasku->draw_text(30, 45 + $tileja + $barcode_korkeus, $y_osoite,								$thispage, $pieni);
		$pdf_lasku->draw_text(30, 35 + $tileja + $barcode_korkeus, $y_postino."  ".$y_postitp,				$thispage, $pieni);
		$pdf_lasku->draw_text(30, 25 + $tileja + $barcode_korkeus, $y_maa,									$thispage, $pieni);

		$pdf_lasku->draw_text(217, 55 + $tileja + $barcode_korkeus, t("Puhelin", $kieli).": $y_puhelin",	$thispage, $pieni);
		$pdf_lasku->draw_text(217, 45 + $tileja + $barcode_korkeus, t("Telefax", $kieli).": $y_fax",		$thispage, $pieni);
		$pdf_lasku->draw_text(217, 35 + $tileja + $barcode_korkeus, t("Sähköposti", $kieli).": $y_email",	$thispage, $pieni);
		$pdf_lasku->draw_text(217, 25 + $tileja + $barcode_korkeus, t("Web", $kieli).": $y_www",			$thispage, $pieni);

		$pdf_lasku->draw_text(404, 55 + $tileja + $barcode_korkeus, t("Y-tunnus", $kieli).": ".tulosta_ytunnus($y_vatnumero, $y_maa, $laskurow["vienti"]), $thispage, $pieni);
		$pdf_lasku->draw_text(404, 45 + $tileja + $barcode_korkeus, t("Kotipaikka", $kieli).": $y_kotipaikka",	$thispage, $pieni);
		$pdf_lasku->draw_text(404, 35 + $tileja + $barcode_korkeus, t("Enn.per.rek", $kieli),					$thispage, $pieni);
		$pdf_lasku->draw_text(404, 25 + $tileja + $barcode_korkeus, t("Alv.rek", $kieli),						$thispage, $pieni);

		// Viivakoodin piirtaminen
		if ($piirra_viivakoodi) {

			$width = 340;
			$height = $barcode_korkeus-1 * 1.2;
			$style = BCS_IMAGE_JPEG;

			$onko_tili1_iban = tarkista_iban($pankkitiedot["pankkitili1"]);
			$onko_iban1_iban = tarkista_iban($pankkitiedot["pankkiiban1"]);

			if ($onko_tili1_iban != "") {
				$code = PalautaPankkiviivakoodi($onko_tili1_iban, $laskurow['summa'], $erapaiva, $laskurow["viite"], 4);
			}
			elseif ($onko_iban1_iban != "") {
				$code = PalautaPankkiviivakoodi($onko_iban1_iban, $laskurow['summa'], $erapaiva, $laskurow["viite"], 4);
			}
			else {
				$code = PalautaPankkiviivakoodi($pankkitiedot["pankkitili1"], $laskurow['summa'], $erapaiva, $laskurow["viite"], 2);
			}

			$font = 5;
			$xres = 1;

			$obj = new C128CObject($width, $height, $style, $code);
			$filename = "/tmp/".md5(uniqid(rand(),true)).".jpg";

			if ($obj) {
				$obj->SetFont($font);
			    $obj->DrawObject($xres);
			  	$obj->SaveObject($filename);
			  	$obj->DestroyObject();
			  	unset($obj);
			}
			$fh = fopen($filename, "r");
			$data = fread($fh, filesize($filename));
			fclose($fh);

			if ($data) {
				$image = $pdf_lasku->jfif_embed($data);

				if (!$image) {
					echo t("Logokuvavirhe");
				}
				else {
					$logoparam = array();
					$logoparam['scale'] = 0.9;
					$placement = $pdf_lasku->image_place($image, 3, 160, $thispage, $logoparam);
				}
			}
			else {
				$pdf_lasku->draw_text(270, 10, "Virhe viivakoodin luomisessa", $thispage, $boldi);
			}

			system("rm -f $filename");
		}
	}
}

if (!function_exists('alvierittely')) {
	function alvierittely ($laskurow, $asiakasrow, $kieli, $pdf_lasku, $thispage, $toim, $kala, $summa, $arvo, $kassa_ale = '', $yhteensamaara=0) {
		global $yhtiorow, $kukarow, $rectparam, $norm, $pieni, $boldi, $pieni_boldi, $iso;

		$kassa_ale_lisa = '';
		$kassa_ale_lisateksti = '';

		if ($kassa_ale != '') {
			$kassa_ale = (float) $kassa_ale;
			$kassa_ale_lisa = " * (1-$kassa_ale/100) ";

			$kala -=10;
			$pdf_lasku->draw_text(360, $kala, t("Kassa-alennuksella", $kieli), 		$thispage, $norm);
			$kala -= 10;
		}

		if ($yhtiorow["naytayhteensamaarat"] == "K" and $kassa_ale == '') {
			$pdf_lasku->draw_text(190, $kala, t("Tuotteita yhteensä", $kieli).":", 	$thispage, $norm);
			$pdf_lasku->draw_text(340, $kala, sprintf('%.2f', $yhteensamaara), 		$thispage, $norm);
			$kala -=10;
		}

		//loppusummat vain vikalle sivulle
		$pdf_lasku->draw_text(360, $kala, t("Veroton arvo yhteensä", $kieli).":", 		$thispage, $norm);

		if ($toim == 'PROFORMA') {
			$arvo = $kassa_ale != '' ? $arvo * (1-$kassa_ale/100) : $arvo;

			$oikpos = $pdf_lasku->strlen(sprintf('%.2f', $arvo), $norm);
			$pdf_lasku->draw_text(535-$oikpos, $kala, sprintf('%.2f', $arvo), 			$thispage, $norm);
		}
		elseif ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
			$arvo_valuutassa = $kassa_ale != '' ? $laskurow["arvo_valuutassa"] * (1-$kassa_ale/100) : $laskurow["arvo_valuutassa"];

			$oikpos = $pdf_lasku->strlen(sprintf('%.2f', $arvo_valuutassa), $norm);
			$pdf_lasku->draw_text(535-$oikpos, $kala, sprintf('%.2f', $arvo_valuutassa), $thispage, $norm);
		}
		else {
			$arvo = $kassa_ale != '' ? $laskurow["arvo"] * (1-$kassa_ale/100) : $laskurow["arvo"];

			$oikpos = $pdf_lasku->strlen(sprintf('%.2f', $arvo), $norm);
			$pdf_lasku->draw_text(535-$oikpos, $kala, sprintf('%.2f', $arvo), $thispage, $norm);
		}

		$pdf_lasku->draw_text(550, $kala, $laskurow["valkoodi"], 							$thispage, $norm);


		if ($toim == 'PROFORMA') {
			$where = " otunnus = '$laskurow[tunnus]' ";
		}
		else {
			$where = " uusiotunnus = '$laskurow[tunnus]' ";
		}

		//Haetaan kaikki alvikannat riveiltä
		$alvquery = "	SELECT distinct alv
						FROM tilausrivi
						WHERE $where
						and yhtio	= '$kukarow[yhtio]'
						and tyyppi	= 'L'
						ORDER BY alv";
		$alvresult = pupe_query($alvquery);
		$kala -= 10;

		$query_ale_lisa = generoi_alekentta('M');

		while ($alvrow = mysql_fetch_assoc($alvresult)) {

			// Valuuttajutut kuntoon
			if ($alvrow["alv"] >= 500 and $toim != 'PROFORMA') {
				$aquery = "	SELECT
							sum(0) alvrivihinta,
							sum(round(tilausrivi.rivihinta $kassa_ale_lisa / if (lasku.vienti_kurssi > 0, lasku.vienti_kurssi, 1),2)) rivihinta
							FROM tilausrivi
							JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
							WHERE tilausrivi.uusiotunnus = '$laskurow[tunnus]' and tilausrivi.yhtio='$kukarow[yhtio]' and tilausrivi.alv='$alvrow[alv]' and tilausrivi.tyyppi='L'";
			}
			elseif ($alvrow["alv"] >= 500) {
				$aquery = "	SELECT
							sum(0) alvrivihinta,
							sum(round((tilausrivi.hinta $kassa_ale_lisa / if (lasku.vienti_kurssi > 0, lasku.vienti_kurssi, 1)) * tilausrivi.varattu * {$query_ale_lisa},2)) rivihinta
							FROM tilausrivi
							JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
							WHERE tilausrivi.otunnus = '$laskurow[tunnus]' and tilausrivi.yhtio='$kukarow[yhtio]' and tilausrivi.alv='$alvrow[alv]' and tilausrivi.tyyppi='L'";
			}
			elseif ($alvrow["alv"] < 500 and $toim != 'PROFORMA' and trim(strtoupper($laskurow["valkoodi"])) == trim(strtoupper($yhtiorow["valkoodi"]))) {
				$aquery = "	SELECT
							round(sum(tilausrivi.rivihinta $kassa_ale_lisa * (tilausrivi.alv / 100)),2) alvrivihinta,
							sum(tilausrivi.rivihinta $kassa_ale_lisa) rivihinta
							FROM tilausrivi
							JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
							WHERE tilausrivi.uusiotunnus = '$laskurow[tunnus]' and tilausrivi.yhtio='$kukarow[yhtio]' and tilausrivi.alv='$alvrow[alv]' and tilausrivi.tyyppi='L'";
			}
			elseif ($alvrow["alv"] < 500 and $toim != 'PROFORMA' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
				$aquery = "	SELECT
							round(sum((tilausrivi.hinta $kassa_ale_lisa / if (lasku.vienti_kurssi > 0, lasku.vienti_kurssi, 1)) / if ('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * tilausrivi.kpl * {$query_ale_lisa} * (tilausrivi.alv/100)),2) alvrivihinta,
							sum(round((tilausrivi.hinta $kassa_ale_lisa / if (lasku.vienti_kurssi > 0, lasku.vienti_kurssi, 1)) / if ('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * tilausrivi.kpl * {$query_ale_lisa},2)) rivihinta
							FROM tilausrivi
							JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
							WHERE tilausrivi.uusiotunnus = '$laskurow[tunnus]' and tilausrivi.yhtio='$kukarow[yhtio]' and tilausrivi.alv='$alvrow[alv]' and tilausrivi.tyyppi='L'";
			}
			else {
				$aquery = "	SELECT
							round(sum((tilausrivi.hinta $kassa_ale_lisa / if (lasku.vienti_kurssi > 0, lasku.vienti_kurssi, 1)) / if ('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.kpl) * {$query_ale_lisa} * (tilausrivi.alv/100)),2) alvrivihinta,
							sum(round((tilausrivi.hinta $kassa_ale_lisa / if (lasku.vienti_kurssi > 0, lasku.vienti_kurssi, 1)) / if ('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.kpl) * {$query_ale_lisa},2)) rivihinta
							FROM tilausrivi
							JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
							WHERE tilausrivi.otunnus = '$laskurow[tunnus]' and tilausrivi.yhtio='$kukarow[yhtio]' and tilausrivi.alv='$alvrow[alv]' and tilausrivi.tyyppi='L'";
			}
			$aresult = pupe_query($aquery);
			$arow = mysql_fetch_assoc($aresult);

			$pdf_lasku->draw_text(360, $kala, t("Arvonlisävero", $kieli)." (".sprintf('%.2f',$arow["rivihinta"])."): ",	$thispage, $norm);
			$oikpos = $pdf_lasku->strlen(sprintf('%.2f', $arow["alvrivihinta"]), $norm);
			$pdf_lasku->draw_text(535-$oikpos, $kala, sprintf('%.2f', $arow["alvrivihinta"]), 			$thispage, $norm);

			if ($alvrow["alv"] >= 600) {
				$pdf_lasku->draw_text(550, $kala, t("K.V.", $kieli), 									$thispage, $norm);
			}
			elseif ($alvrow["alv"] >= 500) {
				$pdf_lasku->draw_text(550, $kala, t("M.V.", $kieli), 									$thispage, $norm);
			}
			else {
				$pdf_lasku->draw_text(550, $kala, ($alvrow["alv"]*1)."%", 								$thispage, $norm);
			}

			$kala -= 10;
		}

		$pdf_lasku->draw_text(360, $kala, t("Lasku Yhteensä", $kieli).":",								$thispage, $norm);

		if ($toim == 'PROFORMA') {
			$summa = $kassa_ale != '' ? $summa * (1-$kassa_ale/100) : $summa;

			$oikpos = $pdf_lasku->strlen(sprintf('%.2f', $summa), $norm);
			$pdf_lasku->draw_text(535-$oikpos, $kala, sprintf('%.2f', $summa), 							$thispage, $norm);
			$pdf_lasku->draw_text(550, $kala, $laskurow["valkoodi"], 									$thispage, $norm);
		}
		elseif ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
			$summa_valuutassa = $kassa_ale != '' ? ($laskurow["summa_valuutassa"]+$laskurow["pyoristys_valuutassa"]) * (1-$kassa_ale/100) : $laskurow["summa_valuutassa"]+$laskurow["pyoristys_valuutassa"];

			$oikpos = $pdf_lasku->strlen(sprintf('%.2f', $summa_valuutassa), $norm);
			$pdf_lasku->draw_text(535-$oikpos, $kala, sprintf('%.2f', $summa_valuutassa),				$thispage, $norm);
			$pdf_lasku->draw_text(550, $kala, $laskurow["valkoodi"], 									$thispage, $norm);

			if ($laskurow["pyoristys_valuutassa"] != 0) {

				$pyoristys_valuutassa = $kassa_ale != '' ? $laskurow["pyoristys_valuutassa"] * (1-$kassa_ale/100) : $laskurow["pyoristys_valuutassa"];

				$kala -= 10;
				$pdf_lasku->draw_text(360, $kala, t("Pyöristys", $kieli).":",							$thispage, $norm);
				$oikpos = $pdf_lasku->strlen(sprintf('%.2f', $pyoristys_valuutassa * -1), $norm);
				$pdf_lasku->draw_text(535-$oikpos, $kala, sprintf('%.2f', $pyoristys_valuutassa * -1),$thispage, $norm);
				$pdf_lasku->draw_text(550, $kala, $laskurow["valkoodi"], 								$thispage, $norm);
				$kala -= 10;

				$pdf_lasku->draw_text(360, $kala, t("Yhteensä", $kieli).":",							$thispage, $norm);
				$oikpos = $pdf_lasku->strlen(sprintf('%.2f', $summa_valuutassa - $pyoristys_valuutassa), $norm);
				$pdf_lasku->draw_text(535-$oikpos, $kala, sprintf('%.2f', $summa_valuutassa - $pyoristys_valuutassa), $thispage, $norm);
				$pdf_lasku->draw_text(550, $kala, $laskurow["valkoodi"], 												$thispage, $norm);
			}
		}
		else {
			$summa = $kassa_ale != '' ? ($laskurow["summa"]+$laskurow["pyoristys"]) * (1-$kassa_ale/100) : $laskurow["summa"]+$laskurow["pyoristys"];

			$oikpos = $pdf_lasku->strlen(sprintf('%.2f', $summa), $norm);
			$pdf_lasku->draw_text(535-$oikpos, $kala, sprintf('%.2f', $summa), 						$thispage, $norm);
			$pdf_lasku->draw_text(550, $kala, $laskurow["valkoodi"], 									$thispage, $norm);

			if ($laskurow["pyoristys"] != 0) {
				$pyoristys = $kassa_ale != '' ? $laskurow["pyoristys"] * (1-$kassa_ale/100) : $laskurow["pyoristys"];

				$kala -= 10;
				$pdf_lasku->draw_text(360, $kala, t("Pyöristys", $kieli).":",							$thispage, $norm);
				$oikpos = $pdf_lasku->strlen(sprintf('%.2f', $pyoristys * -1), $norm);
				$pdf_lasku->draw_text(535-$oikpos, $kala, sprintf('%.2f', $pyoristys * -1), 			$thispage, $norm);
				$pdf_lasku->draw_text(550, $kala, $laskurow["valkoodi"], 								$thispage, $norm);
				$kala -= 10;

				$pdf_lasku->draw_text(360, $kala, t("Yhteensä", $kieli).":",							$thispage, $norm);
				$oikpos = $pdf_lasku->strlen(sprintf('%.2f', $summa - $pyoristys), $norm);
				$pdf_lasku->draw_text(535-$oikpos, $kala, sprintf('%.2f', $summa - $pyoristys), 		$thispage, $norm);
				$pdf_lasku->draw_text(550, $kala, $laskurow["valkoodi"], 								$thispage, $norm);
			}
		}

		$kala -= 10;

		return $kala;
	}
}

if (!function_exists('PalautaPankkiviivakoodi')) {
	function PalautaPankkiviivakoodi($tilinumero, $laskun_summa = 0, $erapv = "00.00.0000", $viitenumero = 0, $versio = 2) {

		if ($versio == '2') {
			$pankkitili = $tilinumero;
			include("inc/pankkitilinoikeellisuus.php");
		}
		else {
			// tarkista_iban ei osaa tarkistaa kuin Suomalaisia tilinumeroita
			$pankkitili = substr(tarkista_iban($tilinumero), 2); // Maatunnus pois
		}

		if ($pankkitili == "") {
			return;
		}

		$konekielinen_tilinumero = $pankkitili;

		// Laskun summa
		$konekielinen_summa = round(($laskun_summa) * 100);

		if ($konekielinen_summa > 99999999) {
			// Jos ylittaa kentan maksimipituuden
			$konekielinen_summa = 0;
		}

		$konekielinen_summa = sprintf("%08d", $konekielinen_summa);

		// Viitenumero
		if ($versio == '2' or $versio == '4' ) {
			$konekielinen_viitenumero = sprintf("%020d", $viitenumero);
		}
		elseif ($versio == '5') {
			// Annettu viite pitaisi olla muodossa RFxxyy...yy, jossa xx on tarkistenumero
			$tarkiste = substr($viitenumero,2,2);
			$viitenumero = substr($viitenumero,4);
			$konekielinen_viitenumero = $tarkiste.sprintf("%021d", $viitenumero);
		}

		// Erapaiva
		$pp = substr($erapv, 0, 2);
		$kk = substr($erapv, 3, 2);
		$vv = substr($erapv, 8, 2);

		$konekielinen_erapv = $vv.$kk.$pp;

		// Varalla
		if ($versio=='2') {
			$vara = "0000";
		}
		elseif ($versio=='4') {
			$vara = "000";
		}
		else $vara = '';

		// Koonti
		$pankkiviivakoodi = $versio.$konekielinen_tilinumero.$konekielinen_summa;

		if ($versio=='2') {
			$pankkiviivakoodi .= $konekielinen_viitenumero.$konekielinen_erapv.$vara;
		}
		else {
			$pankkiviivakoodi .= $vara.$konekielinen_viitenumero.$konekielinen_erapv;
		}

		// Tarkistusnumero
		$viite = $pankkiviivakoodi;

		// Vain versio kakkoseen tarkiste 1
		if ($versio == '2') {
			// Menee tasan samalla systeemilla kuin viitenumeron tarkistusnumeron laskeminen..
			include("inc/generoiviite.inc");
		}

		return($viite);
	}
}

if (!function_exists("tulosta_lasku")) {
	function tulosta_lasku ($lasku, $kieli = "", $tee = "", $toim = "", $valittu_tulostin = "", $valittu_kopio_tulostin = "", $saatekirje = "") {
		GLOBAL $kukarow, $yhtiorow;

		if (substr($lasku,0,6) == "LASKU:") {
			$lasku = substr($lasku, 6);

			$query = "	SELECT *
						FROM lasku
						WHERE yhtio 	= '$kukarow[yhtio]'
						and tila 		= 'U'
						and alatila 	= 'X'
						and laskunro	= '$lasku'";
		}
		else {
			$query = "	SELECT *
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]'
						and tunnus 	= '$lasku'";
		}

		$laresult = pupe_query($query);
		$laskurow = mysql_fetch_assoc($laresult);

		$asiakas_apu_query = "	SELECT *
								FROM asiakas
								WHERE yhtio = '$kukarow[yhtio]'
								and tunnus  = '{$laskurow["liitostunnus"]}'";
		$asiakas_apu_res = pupe_query($asiakas_apu_query);

		if (mysql_num_rows($asiakas_apu_res) == 1) {
			$asiakasrow = mysql_fetch_assoc($asiakas_apu_res);
		}
		else {
			$asiakasrow = array();
		}

		if ($kieli != "") {
			$kieli = trim(strtoupper($kieli));
		}
		elseif (trim($asiakasrow["kieli"]) != "") {
			$kieli = trim(strtoupper($asiakasrow["kieli"]));
		}
		elseif (trim($kukarow["kieli"]) != "") {
			$kieli = trim(strtoupper($kukarow["kieli"]));
		}
		else {
			$kieli = trim(strtoupper($yhtiorow["kieli"]));
		}

		// haetaan maksuehdon tiedot
		$query  = "	SELECT pankkiyhteystiedot.*, maksuehto.*
					FROM maksuehto
					LEFT JOIN pankkiyhteystiedot on (pankkiyhteystiedot.yhtio = maksuehto.yhtio and pankkiyhteystiedot.tunnus = maksuehto.pankkiyhteystiedot)
					WHERE maksuehto.yhtio = '$kukarow[yhtio]'
					and maksuehto.tunnus = '{$laskurow["maksuehto"]}'";
		$result = pupe_query($query);
		$masrow = mysql_fetch_assoc($result);

		// Maksuehto tekstinä
		$laskurow["maksuehto"] = $masrow["teksti"] = t_tunnus_avainsanat($masrow, "teksti", "MAKSUEHTOKV", $kieli);
		$laskurow["kateistyyppi"] = $masrow["kateinen"];

		//PDF parametrit
		$pdf_lasku = new pdffile;
		$pdf_lasku->set_default('margin-top', 	 0);
		$pdf_lasku->set_default('margin-bottom', 0);
		$pdf_lasku->set_default('margin-left', 	 0);
		$pdf_lasku->set_default('margin-right',  0);

		if ($laskurow["tila"] == 'U') {
			$where  = " and tilausrivi.uusiotunnus='$laskurow[tunnus]' and tilausrivi.tyyppi = 'L' ";
		}
		else {
			$where  = " and tilausrivi.otunnus='$laskurow[tunnus]' and tilausrivi.tyyppi in ('L','W','E') and (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) != 0 ";
		}

		// Laskutyyppi
		if (isset($asiakasrow['laskutyyppi']) and $asiakasrow['laskutyyppi'] != -9) {
			$laskutyyppi = $asiakasrow['laskutyyppi'];
		}
		else {
			$laskutyyppi = $yhtiorow['laskutyyppi'];
		}

		// katotaan miten halutaan sortattavan
		$order_sorttaus = '';

		if (isset($asiakasrow["laskun_jarjestys"])) {
			$sorttauskentta = generoi_sorttauskentta($asiakasrow["laskun_jarjestys"] != "" ? $asiakasrow["laskun_jarjestys"] : $yhtiorow["laskun_jarjestys"]);
			$order_sorttaus = $asiakasrow["laskun_jarjestys_suunta"] != "" ? $asiakasrow["laskun_jarjestys_suunta"] : $yhtiorow["laskun_jarjestys_suunta"];
		}
		else {
			$sorttauskentta = generoi_sorttauskentta($yhtiorow["laskun_jarjestys"]);
			$order_sorttaus = $yhtiorow["laskun_jarjestys_suunta"];
		}

		if ($yhtiorow["laskun_palvelutjatuottet"] == "E") $pjat_sortlisa = "tuotetyyppi,";
		else $pjat_sortlisa = "";

		$query_ale_lisa = generoi_alekentta('M');

		if ($toim == 'PROFORMA') {
			$laskurow["tapvm"] = date("Y-m-d");

			$lisa = " 	sum(tilausrivi.hinta / if ('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.kpl) * {$query_ale_lisa}) rivihinta,
						sum(tilausrivi.varattu+tilausrivi.kpl) kpl, ";
		}
		else {
			$lisa = "	sum(tilausrivi.rivihinta) rivihinta,
						sum(tilausrivi.kpl) kpl,";
		}

		$sivu 	 	= 1;
		$summa 	 	= 0;
		$arvo	 	= 0;
		$varasto 	= 0;
		$vientikala = 0;
		$page_lasku = array();

		if ($yhtiorow["viivakoodi_laskulle"] == "K" and $laskurow["maa"] == "FI" and $laskurow["summa"] > 0) {
			$barcode_korkeus = 50;
		}

		if ($laskurow["vienti"] != "" or strtoupper($laskurow["maa"]) != strtoupper($yhtiorow["maa"])) {
			$vientikala = 20;
		}

		// haetaan tilauksen kaikki rivit
		$query = "  SELECT
					if (tilausrivi.nimitys='Kuljetusvakuutus', tilausrivin_lisatiedot.vanha_otunnus, ifnull((SELECT vanha_otunnus from tilausrivin_lisatiedot t_lisa where t_lisa.yhtio=tilausrivi.yhtio and t_lisa.tilausrivitunnus=tilausrivi.perheid and t_lisa.omalle_tilaukselle != ''), tilausrivi.tunnus)) rivigroup,
					tilausrivi.ale1,
					tilausrivi.ale2,
					tilausrivi.ale3,
					tilausrivi.alv,
					tuote.eankoodi,
					tuote.ei_saldoa,
					tilausrivi.erikoisale,
					tilausrivi.nimitys,
					tilausrivin_lisatiedot.osto_vai_hyvitys,
					tuote.sarjanumeroseuranta,
					tilausrivi.tuoteno,
					tilausrivi.uusiotunnus,
					tilausrivi.yksikko,
					tilausrivi.hinta,
					tilausrivi.netto,
					lasku.vienti_kurssi,
					lasku.viesti laskuviesti,
					lasku.asiakkaan_tilausnumero,
					lasku.luontiaika tilauspaiva,
					if (tuote.tuotetyyppi = 'K','2 Työt','1 Muut') tuotetyyppi,
					if (tilausrivi.var2 = 'EIOST', 'EIOST', '') var2,
					if (tuote.myyntihinta_maara = 0, 1, tuote.myyntihinta_maara) myyntihinta_maara,
					min(tilausrivi.hyllyalue) hyllyalue,
					min(tilausrivi.hyllynro) hyllynro,
					min(tilausrivi.keratty) keratty,
					min(if (tilausrivi.toimaika = '0000-00-00', date_format(now(), '%Y-%m-%d'), tilausrivi.toimaika)) toimaika,
					min(if (date_format(tilausrivi.toimitettuaika, '%Y-%m-%d') = '0000-00-00', date_format(now(), '%Y-%m-%d'), date_format(tilausrivi.toimitettuaika, '%Y-%m-%d'))) toimitettuaika,
					min(tilausrivi.otunnus) otunnus,
					min(tilausrivi.perheid) perheid,
					min(tilausrivi.tunnus) tunnus,
					min(tilausrivi.kommentti) kommentti,
					min(tilausrivi.tilaajanrivinro) tilaajanrivinro,
					min(tilausrivi.laadittu) laadittu,
					sum(tilausrivi.tilkpl) tilkpl,
					sum((tilausrivi.hinta / {$laskurow["vienti_kurssi"]}) / if ('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.kpl) * {$query_ale_lisa}) rivihinta_valuutassa,
					group_concat(tilausrivi.tunnus) rivitunnukset,
					group_concat(distinct tilausrivi.perheid) perheideet,
					count(*) rivigroup_maara,
					$lisa
					$sorttauskentta
					FROM tilausrivi
					JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
					JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
					LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
					WHERE tilausrivi.yhtio  = '$kukarow[yhtio]'
					and (tilausrivi.perheid = 0 or tilausrivi.perheid=tilausrivi.tunnus or tilausrivin_lisatiedot.ei_nayteta !='E' or tilausrivin_lisatiedot.ei_nayteta is null)
					and tilausrivi.varattu+tilausrivi.kpl != 0
					$where
					GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23
					ORDER BY tilausrivi.otunnus, if(tilausrivi.tuoteno in ('$yhtiorow[kuljetusvakuutus_tuotenumero]','$yhtiorow[laskutuslisa_tuotenumero]'), 2, 1), $pjat_sortlisa sorttauskentta $order_sorttaus, tilausrivi.tunnus";
		$result = pupe_query($query);

		$kaanteinen_verotus = FALSE;

		while ($row = mysql_fetch_assoc($result)) {
			if ($row["alv"] >= 600) {
				$kaanteinen_verotus = TRUE;
				break;
			}
		}

		mysql_data_seek($result, 0);

		// aloitellaan laskun teko
		list($page_lasku, $sivu, $kala) = alku($laskurow, $asiakasrow, $kieli, $pdf_lasku, $toim, $laskutyyppi, $kaanteinen_verotus, $page_lasku, $sivu);

		$rivigrouppaus = FALSE;
		$yhteensamaara = 0;

		while ($row = mysql_fetch_assoc($result)) {

			// Rivin toimitusaika
			if ($yhtiorow["tilausrivien_toimitettuaika"] == 'K' and $row["keratty"] == "saldoton") {
				$row["toimitettuaika"] = $row["toimaika"];
			}
			elseif ($yhtiorow["tilausrivien_toimitettuaika"] == 'X') {
				$row["toimitettuaika"] = $row["toimaika"];
			}
			else {
				$row["toimitettuaika"] = $row["toimitettuaika"];
			}

			if ($row["rivigroup_maara"] > 1 and !$rivigrouppaus) {
				$rivigrouppaus = TRUE;
			}

			if (($laskutyyppi != 2 and $laskutyyppi != 12) or (($laskutyyppi == 2 or $laskutyyppi == 12) and $row["perheid"] > 0 and $row["perheid"] == $row["tunnus"])) {
				$yhteensamaara += $row["kpl"];
			}

			// Otetaan yhteensäkommentti pois jos summataan rivejä
			if ($rivigrouppaus) {
				$row["kommentti"] = preg_replace("/ ".t("yhteensä", $kieli).": [0-9\.]* [A-Z]{3}\./", "", $row["kommentti"]);
				$row["kommentti"] = preg_replace("/ ".t("yhteensä", $asiakasrow["kieli"]).": [0-9\.]* [A-Z]{3}\./", "", $row["kommentti"]);
				$row["kommentti"] = preg_replace("/ ".t("yhteensä").": [0-9\.]* [A-Z]{3}\./", "", $row["kommentti"]);
				$row["kommentti"] = preg_replace("/ "."yhteensä".": [0-9\.]* [A-Z]{3}\./", "", $row["kommentti"]);
			}

			if ($kala <= 130) {
				loppu($laskurow, $asiakasrow, $kieli, $pdf_lasku, $toim, $page_lasku[$sivu], $masrow, $sivu, $summa, $arvo, $page_lasku, $vientikala, $laskutyyppi, "");

				$sivu++;

				list($page_lasku[$sivu], $kala) = uusi_sivu($laskurow, $asiakasrow, $kieli, $pdf_lasku, $toim, $laskutyyppi, '');
			}

			// Mahtuuko rivi tälle sivulle
			$devnull = "";

			list($devnull, $devnullkala, $devnullsumma, $devnullarvo) = rivi_lasku($laskurow, $asiakasrow, $row, $kieli, $pdf_lasku, $devnull, $kala, $laskutyyppi, $toim, $summa, $arvo, '');

			// Jos on paljon rivejä tehdään uusi otsikko...
			if ($devnullkala <= 130) {
				loppu($laskurow, $asiakasrow, $kieli, $pdf_lasku, $toim, $page_lasku[$sivu], $masrow, $sivu, $summa, $arvo, $page_lasku, $vientikala, $laskutyyppi, "");

				$sivu++;

				list($page_lasku[$sivu], $kala) = uusi_sivu($laskurow, $asiakasrow, $kieli, $pdf_lasku, $toim, $laskutyyppi, '');
			}

			list($page_lasku[$sivu], $kala, $summa, $arvo) = rivi_lasku($laskurow, $asiakasrow, $row, $kieli, $pdf_lasku, $page_lasku[$sivu], $kala, $laskutyyppi, $toim, $summa, $arvo);

			// Tutkitaan mihin printteriin tämän laskun voisi tulostaa
			if ($valittu_tulostin == "AUTOMAAGINEN_VALINTA" and $varasto == 0 and $row["ei_saldoa"] == "") {
				//otetaan varastopaikat talteen
				$varasto = kuuluukovarastoon($row["hyllyalue"], $row["hyllynro"], "");
			}
		}

		// Mahtuuko erittely samalle sivulle?
		$devnullkala = alvierittely($laskurow, $asiakasrow, $kieli, $pdf_lasku, $devnull, $toim, $kala, $summa, $arvo, "", "");

		if ($devnullkala <= 110+$vientikala+$barcode_korkeus) {
			loppu($laskurow, $asiakasrow, $kieli, $pdf_lasku, $toim, $page_lasku[$sivu], $masrow, $sivu, $summa, $arvo, $page_lasku, $vientikala, $laskutyyppi, "");

			$sivu++;

			list($page_lasku[$sivu], $kala) = uusi_sivu($laskurow, $asiakasrow, $kieli, $pdf_lasku, $toim, $laskutyyppi, '');
		}

		$kala = alvierittely($laskurow, $asiakasrow, $kieli, $pdf_lasku, $page_lasku[$sivu], $toim, $kala, $summa, $arvo, "", $yhteensamaara);

		if ($masrow['kassa_alepros'] > 0) {

			// Mahtuuko erittely samalle sivulle?
			$devnullkala = alvierittely($laskurow, $asiakasrow, $kieli, $pdf_lasku, $devnull, $toim, $kala, $summa, $arvo, $masrow['kassa_alepros'], $yhteensamaara);

			if ($devnullkala <= 110+$vientikala+$barcode_korkeus) {
				loppu($laskurow, $asiakasrow, $kieli, $pdf_lasku, $toim, $page_lasku[$sivu], $masrow, $sivu, $summa, $arvo, $page_lasku, $vientikala, $laskutyyppi, "");

				$sivu++;

				list($page_lasku[$sivu], $kala) = uusi_sivu($laskurow, $asiakasrow, $kieli, $pdf_lasku, $toim, $laskutyyppi, '');
			}

			$kala = alvierittely($laskurow, $asiakasrow, $kieli, $pdf_lasku, $page_lasku[$sivu], $toim, $kala, $summa, $arvo, $masrow['kassa_alepros'], $yhteensamaara);
		}

		// Vikan sivun loppulaatikko
		loppu($laskurow, $asiakasrow, $kieli, $pdf_lasku, $toim, $page_lasku[$sivu], $masrow, $sivu, $summa, $arvo, $page_lasku, $vientikala, $laskutyyppi, 1);

		// Palautetaan PDF sellaisenaan
		if ($tee == 'VERKKOLASKU_MAVENTA') {
			return $pdf_lasku->generate();
		}

		// keksitään uudelle failille joku varmasti uniikki nimi:
		list($usec, $sec) = explode(' ', microtime());
		mt_srand((float) $sec + ((float) $usec * 100000));
		$pdf_laskufilenimi = "/tmp/Lasku-".md5(uniqid(mt_rand(), true)).".pdf";

		// kirjoitetaan pdf faili levylle..
		$fh = fopen($pdf_laskufilenimi, "w");
		if (fwrite($fh, $pdf_lasku->generate()) === FALSE) die("PDF kirjoitus epäonnistui $pdf_laskufilenimi");
		fclose($fh);

		// jos tämä haluttiin suoraan asiakkaalle sähköpostiin
		if ($tee == 'NAYTATILAUS') {
			//Työnnetään tuo pdf vaan putkeen!
			echo file_get_contents($pdf_laskufilenimi);
		}
		elseif ($tee == 'VERKKOLASKU_APIX') {
			// Palautetaan laskunumero ja $pdf_laskufilenimi jotta apixkoodi saa sen zipattua jne.
			return $pdf_laskufilenimi;
		}
		elseif ($tee == 'VERKKOLASKU' and ($yhtiorow['lasku_tulostin'] == -99 or $valittu_tulostin == -99)) {
			// Lasku lähtee asiakkaalle sähköpostilla. Generoidaan laskun saatekirje, joka haetaan avainsanoista
			include_once("inc/generoi_laskun_saate.inc");

			list($komento, $content_subject, $content_body) = generoi_laskun_saate($laskurow, $saatekirje, $kieli);

			// Jos asiakkaalla on laskutussähköposti tai jos $yhtiorow['lasku_tulostin'] != -99 niin silloin lasku menee kukarow[email]:iin jos $komento == ""
			if ($komento != "" or $yhtiorow['lasku_tulostin'] != -99) {

				// otetaan tämä talteen jotta saadaan vientierittely
				$GLOBALS['vientierittelymail'] = $komento;

				// lähetetään meili
				$kutsu = t("Lasku", $kieli)." ".$laskurow["laskunro"];
				$liite = $pdf_laskufilenimi;

				// lähetetään cc aina postittaja osoitteeseen
				$sahkoposti_cc = $yhtiorow["postittaja_email"];

				include("inc/sahkoposti.inc"); // sanotaan include eikä require niin ei kuolla
			}
		}
		else {
			// Setataan muuttuja
			$komento = "";

			//haetaan varaston tiedot
			if ($valittu_tulostin != "" and !is_numeric($valittu_tulostin) and $valittu_tulostin == "AUTOMAAGINEN_VALINTA") {
				if ($varasto != 0) {
					$query = "	SELECT *
								FROM varastopaikat
								WHERE yhtio = '$kukarow[yhtio]'
								AND tunnus = '$varasto'
								ORDER BY alkuhyllyalue,alkuhyllynro";
				}
				else {
					$query = "	SELECT *
								FROM varastopaikat
								WHERE yhtio = '$kukarow[yhtio]' AND tyyppi != 'P'
								ORDER BY alkuhyllyalue,alkuhyllynro
								LIMIT 1";
				}
				$prires = pupe_query($query);
				$prirow = mysql_fetch_assoc($prires);

				$valittu_tulostin = $prirow["printteri5"];

				if ($GLOBALS['silent'] == "") $GLOBALS['tulos_ulos'] .= t("Lasku tulostuu varastoon").": $prirow[nimitys]<br>\n";
			}
			elseif ($valittu_tulostin != "" and !is_numeric($valittu_tulostin) and $valittu_tulostin != "AUTOMAAGINEN_VALINTA") {
				// Tulostakopio päässää suoraan printterikomennon
				$komento = $valittu_tulostin;
			}

			if ($komento == "" and (int) $valittu_tulostin > 0) {
				$querykieli = "	SELECT *
								from kirjoittimet
								where yhtio = '$kukarow[yhtio]'
								and tunnus = '$valittu_tulostin'";
				$kires = pupe_query($querykieli);
				$kirow = mysql_fetch_assoc($kires);

				$komento = $kirow["komento"];

				if ($GLOBALS['silent'] == "") $GLOBALS['tulos_ulos'] .= t("Lasku tulostuu kirjoittimelle").": $kirow[kirjoitin]<br>\n";
			}

			// otetaan tämä talteen jotta saadaan vientierittely
			$GLOBALS['vientierittelykomento'] = $komento;

			if ($komento == "email" and $kukarow["eposti"] != '') {
				// lähetetään meili
				$komento = "";

				$kutsu = t("Lasku", $kieli)." ".$laskurow["laskunro"];

				if ($yhtiorow["liitetiedostojen_nimeaminen"] == "N") {
					$kutsu .= ", ".trim($laskurow["nimi"]);
				}

				$liite 				= $pdf_laskufilenimi;
				$sahkoposti_cc 		= "";
				$content_subject 	= "";
				$content_body 		= "";

				include("inc/sahkoposti.inc");
			}
			elseif ($komento != "" and $komento != "email") {
				// itse print komento...
				$line = exec("$komento $pdf_laskufilenimi");
			}

			// Kuittikopio
			if (isset($valittu_kopio_tulostin) and $valittu_kopio_tulostin != '') {
				$querykieli = "	SELECT *
								from kirjoittimet
								where yhtio = '$kukarow[yhtio]'
								and tunnus = '$valittu_kopio_tulostin'";
				$kires = pupe_query($querykieli);
				$kirow = mysql_fetch_assoc($kires);

				if ($GLOBALS['silent'] == "") $GLOBALS['tulos_ulos'] .= t("Kuittikopio tulostuu kirjoittimelle").": $kirow[kirjoitin]<br>\n";

				if ($kirow["komento"] == "email" and $kukarow["eposti"] != '') {
					// lähetetään meili
					$komento = "";

					$kutsu = t("Lasku", $kieli)." ".$laskurow["laskunro"];

					if ($yhtiorow["liitetiedostojen_nimeaminen"] == "N") {
						$kutsu .= ", ".trim($laskurow["nimi"]);
					}

					$liite 				= $pdf_laskufilenimi;
					$sahkoposti_cc 		= "";
					$content_subject 	= "";
					$content_body 		= "";

					include("inc/sahkoposti.inc");
				}
				elseif ($kirow["komento"] != "" and $kirow["komento"] != "email") {
					// itse print komento...
					$line = exec("$kirow[komento] $pdf_laskufilenimi");
				}
			}
		}

		// poistetaan tmp file samantien kuleksimasta...
		unlink($pdf_laskufilenimi);
		unset($pdf_lasku);
		unset($page_lasku);
	}
}
