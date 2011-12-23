<?php

	if (!function_exists("uusi_karhukierros")) {
		function uusi_karhukierros($yhtio) {
			$query = "	SELECT tunnus
						FROM karhukierros
						where pvm  = current_date
						and yhtio  = '$yhtio'
						and tyyppi = ''";
			$result = pupe_query($query);

			if (!mysql_num_rows($result)) {
				$query = "INSERT INTO karhukierros (pvm,yhtio) values (current_date,'$yhtio')";
				$result = pupe_query($query);
				$uusid = mysql_insert_id();
			}
			else {
				$row = mysql_fetch_assoc($result);
				$uusid = $row["tunnus"];
			}

			return $uusid;
		}
	}

	if (!function_exists("liita_lasku")) {
		function liita_lasku($ktunnus,$ltunnus) {
			$query = "INSERT INTO karhu_lasku (ktunnus,ltunnus) values ($ktunnus,$ltunnus)";
			$result = pupe_query($query);
		}
	}

	if (!function_exists("alku")) {
		function alku ($viesti = null, $karhukierros_tunnus = '') {
			global $pdf, $asiakastiedot, $yhteyshenkilo, $yhtiorow, $kukarow, $kala, $sivu, $rectparam, $norm, $pieni, $boldi, $kaatosumma, $kieli, $_POST, $iso;

			$firstpage = $pdf->new_page("a4");

			if ($yhteyshenkilo == "") {
				$yhteyshenkilo = $kukarow["tunnus"];
			}

			//Haetaan yhteyshenkilon tiedot
			$apuqu = "	SELECT *
						from kuka
						where yhtio='$kukarow[yhtio]' and tunnus='$yhteyshenkilo'";
			$yres = pupe_query($apuqu);
			$yrow = mysql_fetch_assoc($yres);

			//Otsikko
			$pdf->draw_text(310, 815, t("Maksukehotus", $kieli), $firstpage, $iso);
			$pdf->draw_text(430, 815, t("Sivu", $kieli)." ".$sivu, 	$firstpage, $norm);

			tulosta_logo_pdf($pdf, $firstpage, "");

			if (isset($_POST['ekirje_laheta']) === false) {
				//Vasen sarake
				//$pdf->draw_rectangle(737, 20,  674, 300, $firstpage, $rectparam);
				$pdf->draw_text(50, 729, t("Laskutusosoite", $kieli), 	$firstpage, $pieni);
				$pdf->draw_text(50, 717, $asiakastiedot["nimi"], 		$firstpage, $norm);
				$pdf->draw_text(50, 707, $asiakastiedot["nimitark"], 	$firstpage, $norm);
				$pdf->draw_text(50, 697, $asiakastiedot["osoite"], 		$firstpage, $norm);
				$pdf->draw_text(50, 687, $asiakastiedot["postino"]." ".$asiakastiedot["postitp"], $firstpage, $norm);
				$pdf->draw_text(50, 677, $asiakastiedot["maa"], 		$firstpage, $norm);
			}
			else {
				// l�hett�j�
				$iiso = array('height' => 11, 'font' => 'Times-Roman');
				$pdf->draw_text(mm_pt(22), mm_pt(268), strtoupper($yhtiorow["nimi"]), 										$firstpage, $iiso);
				$pdf->draw_text(mm_pt(22), mm_pt(264), strtoupper($yhtiorow["nimitark"]), 									$firstpage, $iiso);
				$pdf->draw_text(mm_pt(22), mm_pt(260), strtoupper($yhtiorow["osoite"]), 									$firstpage, $iiso);
				$pdf->draw_text(mm_pt(22), mm_pt(256), strtoupper($yhtiorow["postino"]." ".$yhtiorow["postitp"]), 			$firstpage, $iiso);

				// vastaanottaja
				$pdf->draw_text(mm_pt(22), mm_pt(234), strtoupper($asiakastiedot["nimi"]), 									$firstpage, $iiso);
				$pdf->draw_text(mm_pt(22), mm_pt(230), strtoupper($asiakastiedot["nimitark"]), 								$firstpage, $iiso);
				$pdf->draw_text(mm_pt(22), mm_pt(226), strtoupper($asiakastiedot["osoite"]), 								$firstpage, $iiso);
				$pdf->draw_text(mm_pt(22), mm_pt(222), strtoupper($asiakastiedot["postino"]." ".$asiakastiedot["postitp"]), $firstpage, $iiso);

				// jos vastaanottaja on eri maassa kuin yhtio niin lis�t��n maan nimi
				if ($yhtiorow['maa'] != $asiakastiedot['maa']) {
					$query = sprintf(
							"SELECT nimi from maat where koodi='%s' AND ryhma_tunnus = ''",
							mysql_real_escape_string($asiakastiedot['maa'])
					);

					$maa_result = pupe_query($query);
					$maa_nimi = mysql_fetch_assoc($maa_result);
					$pdf->draw_text(mm_pt(22), mm_pt(218), $maa_nimi['nimi'], 												$firstpage, $iiso);
				}
			}

			//Oikea sarake
			$pdf->draw_rectangle(760, 320, 739, 575, 				$firstpage, $rectparam);
			$pdf->draw_rectangle(760, 420, 739, 575, 				$firstpage, $rectparam);
			$pdf->draw_text(330, 752, t("P�iv�m��r�", $kieli), 		$firstpage, $pieni);

			if ($karhukierros_tunnus != "") {
				$query = "	SELECT pvm
							FROM karhukierros
							WHERE tunnus = '$karhukierros_tunnus'
							LIMIT 1";
				$pvm_result = pupe_query($query);
				$pvm_row = mysql_fetch_assoc($pvm_result);
				$paiva = substr($pvm_row["pvm"], 8, 2);
				$kuu   = substr($pvm_row["pvm"], 5, 2);
				$year  = substr($pvm_row["pvm"], 0, 4);
			}
			else {
				$pvm_row = array();
				$pvm_row["pvm"] = date("Y-m-d");
				$paiva = date("j");
				$kuu   = date("n");
				$year  = date("Y");
			}

			$pdf->draw_text(330, 742, tv1dateconv($pvm_row["pvm"]),	$firstpage, $norm);
			$pdf->draw_text(430, 752, t("Asiaa hoitaa", $kieli), 	$firstpage, $pieni);
			$pdf->draw_text(430, 742, $yrow["nimi"], 				$firstpage, $norm);

			$pdf->draw_rectangle(739, 320, 718, 575, $firstpage, $rectparam);
			$pdf->draw_rectangle(739, 420, 718, 575, $firstpage, $rectparam);
			$pdf->draw_text(330, 731, t("Er�p�iv�", $kieli), $firstpage, $pieni);

			if ($yhtiorow['karhuerapvm'] > 0) {
				$seurday   = date("d",mktime(0, 0, 0, $kuu, $paiva+$yhtiorow['karhuerapvm'],  $year));
				$seurmonth = date("m",mktime(0, 0, 0, $kuu, $paiva+$yhtiorow['karhuerapvm'],  $year));
				$seuryear  = date("Y",mktime(0, 0, 0, $kuu, $paiva+$yhtiorow['karhuerapvm'],  $year));

				$pdf->draw_text(330, 721, tv1dateconv($seuryear."-".$seurmonth."-".$seurday), $firstpage, $norm);
			}
			else {
				$pdf->draw_text(330, 721, t("HETI", $kieli), $firstpage, $norm);
			}

			$pdf->draw_text(430, 731, t("Puhelin", $kieli), $firstpage, $pieni);
			$pdf->draw_text(430, 721, $yrow["puhno"], $firstpage, $norm);

			$pdf->draw_rectangle(718, 320, 697, 575, $firstpage, $rectparam);
			$pdf->draw_rectangle(718, 420, 697, 575, $firstpage, $rectparam);
			$pdf->draw_text(330, 710, t("Viiv�stykorko", $kieli), 			$firstpage, $pieni);
			$pdf->draw_text(330, 700, ($yhtiorow["viivastyskorko"]*1)."%", 	$firstpage, $norm);
			$pdf->draw_text(430, 710, t("S�hk�posti", $kieli), 				$firstpage, $pieni);
			$pdf->draw_text(430, 700, $yrow["eposti"], 						$firstpage, $norm);

			$pdf->draw_rectangle(697, 320, 676, 575, $firstpage, $rectparam);
			$pdf->draw_text(330, 689, t("Ytunnus/Asiakasnumero", $kieli), 	$firstpage, $pieni);
			$pdf->draw_text(330, 679, $asiakastiedot["ytunnus"], 			$firstpage, $norm);

			//Rivit alkaa t�s� kohtaa
			$kala = 540;

			// lis�t��n karhuviesti kirjeeseen
			if ($sivu == 1) {
				// tehd��n riveist� max 90 merkki�
				$viesti = wordwrap($viesti, 90, "\n");

	            $i = 0;
	            $rivit = explode("\n", $viesti);
				$rivit[] = '';
				$rivit[] = t("Yhteyshenkil�mme", $kieli) . ": $yrow[nimi] / $yrow[eposti] / $yrow[puhno]";

				foreach ($rivit as $rivi) {
					// laitetaan
	                $pdf->draw_text(80, $kala, $rivi, $firstpage, $norm);

					// seuraava rivi tulee 10 pistett� alemmas kuin t�m� rivi
					$kala -= 10;
	                $i++;
	            }
			}

			$kala -= 10;

			//Laskurivien otsikkotiedot
			//eka rivi
			$pdf->draw_text(30,  $kala, t("Laskun numero", $kieli)." / ".t("Viite", $kieli),		$firstpage, $pieni);
			$pdf->draw_text(180, $kala, t("Laskun pvm", $kieli),									$firstpage, $pieni);
			$pdf->draw_text(240, $kala, t("Er�p�iv�", $kieli),										$firstpage, $pieni);
			$pdf->draw_text(295, $kala, t("My�h�ss� pv", $kieli),									$firstpage, $pieni);
			$pdf->draw_text(455, $kala, t("Laskun summa", $kieli),									$firstpage, $pieni);

			if ($yhtiorow["maksukehotus_kentat"] == "") {
				$pdf->draw_text(360, $kala, t("Viimeisin muistutuspvm", $kieli),						$firstpage, $pieni);
				$pdf->draw_text(525, $kala, t("Perint�kerta", $kieli),									$firstpage, $pieni);
			}

			$kala -= 15;

			//toka rivi
			if ($kaatosumma != 0 and $sivu == 1) {
				$pdf->draw_text(30,  $kala, t("Kohdistamattomia suorituksia", $kieli),	$firstpage, $norm);

				$oikpos = $pdf->strlen(sprintf("%.2f", $kaatosumma), $norm);
				$pdf->draw_text(500-$oikpos, $kala, sprintf("%.2f", $kaatosumma),		$firstpage, $norm);
				$kala -= 13;
			}

			return($firstpage);
		}
	}

	if(!function_exists("rivi")) {
		function rivi ($firstpage, $summa) {
			global $firstpage, $pdf, $yhtiorow, $kukarow, $row, $kala, $sivu, $lask, $rectparam, $norm, $pieni, $lask, $kieli, $karhukertanro;

			// siirryt��nk� uudelle sivulle?
			if ($kala < 133) {
				$sivu++;
				loppu($firstpage,'');
				$firstpage = alku();
				$lask = 1;
			}

			$pdf->draw_text(30,  $kala, $row["laskunro"]." / ".$row["viite"],	$firstpage, $norm);
			$pdf->draw_text(180, $kala, tv1dateconv($row["tapvm"]), 			$firstpage, $norm);
			$pdf->draw_text(240, $kala, tv1dateconv($row["erpcm"]), 			$firstpage, $norm);

			$oikpos = $pdf->strlen($row["ika"], $norm);
			$pdf->draw_text(338-$oikpos, $kala, $row["ika"], 					$firstpage, $norm);

			if ($row["valkoodi"] != $yhtiorow["valkoodi"]) {
				$oikpos = $pdf->strlen($row["summa_valuutassa"], $norm);
				$pdf->draw_text(500-$oikpos, $kala, $row["summa_valuutassa"]." ".$row["valkoodi"], 	$firstpage, $norm);
			}
			else {
				$oikpos = $pdf->strlen($row["summa"], $norm);
				$pdf->draw_text(500-$oikpos, $kala, $row["summa"]." ".$row["valkoodi"], 				$firstpage, $norm);
			}

			if ($karhukertanro == "") {
				$karhukertanro = $row["karhuttu"] + 1;
			}

			if ($yhtiorow["maksukehotus_kentat"] == "") {
				$pdf->draw_text(365, $kala, tv1dateconv($row["kpvm"]), $firstpage, $norm);
				$oikpos = $pdf->strlen($karhukertanro, $norm);
				$pdf->draw_text(560-$oikpos, $kala, $karhukertanro, $firstpage, $norm);
			}

			$kala = $kala - 13;

			$lask++;

			if ($row["valkoodi"] != $yhtiorow["valkoodi"]) {
				$summa += $row["summa_valuutassa"];
			} else {
				$summa += $row["summa"];
			}
			return($summa);
		}
	}

	if(!function_exists("loppu")) {
		function loppu ($firstpage, $summa) {

			global $pdf, $yhtiorow, $kukarow, $sivu, $rectparam, $norm, $pieni, $kaatosumma, $kieli, $ktunnus, $maksuehtotiedot, $toimipaikkarow, $laskutiedot, $karhut_samalle_laskulle, $karhukertanro;
/*
			//yhteens�rivi
			$pdf->draw_rectangle(110, 20, 90, 580,	$firstpage, $rectparam);
			$pdf->draw_rectangle(110, 207, 90, 580,	$firstpage, $rectparam);
			$pdf->draw_rectangle(110, 394, 90, 580,	$firstpage, $rectparam);
			$pdf->draw_rectangle(110, 540, 90, 580,	$firstpage, $rectparam);
*/
			if ($karhut_samalle_laskulle == 1 or $karhukertanro != "") {
				$pdf->draw_text(380, 118,  t("YHTEENS�", $kieli).":",	$firstpage, $norm);

				$oikpos = $pdf->strlen(sprintf("%.2f", $summa), $norm);
				$pdf->draw_text(500-$oikpos, 118, sprintf("%.2f", $summa)." ".$laskutiedot["valkoodi"],		$firstpage, $norm);
			}

			$pankkitiedot = array();

			//Laitetaan pankkiyhteystiedot kuntoon
			if ($maksuehtotiedot["factoring"] != "") {
				$query = "	SELECT *
							FROM factoring
							WHERE yhtio = '$kukarow[yhtio]'
							AND factoringyhtio = '$maksuehtotiedot[factoring]'";
				$fac_result = pupe_query($query);
				$factoringrow = mysql_fetch_assoc($fac_result);

				$pankkitiedot["pankkinimi1"]  =	$factoringrow["pankkinimi1"];
				$pankkitiedot["pankkitili1"]  =	$factoringrow["pankkitili1"];
				$pankkitiedot["pankkiiban1"]  =	$factoringrow["pankkiiban1"];
				$pankkitiedot["pankkiswift1"] =	$factoringrow["pankkiswift1"];
				$pankkitiedot["pankkinimi2"]  =	$factoringrow["pankkinimi2"];
				$pankkitiedot["pankkitili2"]  =	$factoringrow["pankkitili2"];
				$pankkitiedot["pankkiiban2"]  =	$factoringrow["pankkiiban2"];
				$pankkitiedot["pankkiswift2"] = $factoringrow["pankkiswift2"];
				$pankkitiedot["pankkinimi3"]  =	"";
				$pankkitiedot["pankkitili3"]  =	"";
				$pankkitiedot["pankkiiban3"]  =	"";
				$pankkitiedot["pankkiswift3"] =	"";
			}
			elseif ($maksuehtotiedot["pankkinimi1"] != "") {
				$pankkitiedot["pankkinimi1"]  =	$maksuehtotiedot["pankkinimi1"];
				$pankkitiedot["pankkitili1"]  =	$maksuehtotiedot["pankkitili1"];
				$pankkitiedot["pankkiiban1"]  =	$maksuehtotiedot["pankkiiban1"];
				$pankkitiedot["pankkiswift1"] =	$maksuehtotiedot["pankkiswift1"];
				$pankkitiedot["pankkinimi2"]  =	$maksuehtotiedot["pankkinimi2"];
				$pankkitiedot["pankkitili2"]  =	$maksuehtotiedot["pankkitili2"];
				$pankkitiedot["pankkiiban2"]  =	$maksuehtotiedot["pankkiiban2"];
				$pankkitiedot["pankkiswift2"] = $maksuehtotiedot["pankkiswift2"];
				$pankkitiedot["pankkinimi3"]  =	$maksuehtotiedot["pankkinimi3"];
				$pankkitiedot["pankkitili3"]  =	$maksuehtotiedot["pankkitili3"];
				$pankkitiedot["pankkiiban3"]  =	$maksuehtotiedot["pankkiiban3"];
				$pankkitiedot["pankkiswift3"] =	$maksuehtotiedot["pankkiswift3"];
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

			//Pankkiyhteystiedot
			$pdf->draw_rectangle(115, 20, 68, 580,	$firstpage, $rectparam);

			$pdf->draw_text(30, 106,  t("Pankkiyhteys", $kieli),	$firstpage, $pieni);

			$pdf->draw_text(30,  94, $pankkitiedot["pankkinimi1"]." ".$pankkitiedot["pankkitili1"],	$firstpage, $norm);
			$pdf->draw_text(217, 94, $pankkitiedot["pankkinimi2"]." ".$pankkitiedot["pankkitili2"],	$firstpage, $norm);
			$pdf->draw_text(404, 94, $pankkitiedot["pankkinimi3"]." ".$pankkitiedot["pankkitili3"],	$firstpage, $norm);

			if ($pankkitiedot["pankkiiban1"] != "") {
				$pdf->draw_text(30,  83, "IBAN: ".$pankkitiedot["pankkiiban1"],	$firstpage, $pieni);
			}
			if ($pankkitiedot["pankkiiban2"] != "") {
				$pdf->draw_text(217, 83, "IBAN: ".$pankkitiedot["pankkiiban2"],	$firstpage, $pieni);
			}
			if ($pankkitiedot["pankkiiban3"] != "") {
				$pdf->draw_text(404, 83, "IBAN: ".$pankkitiedot["pankkiiban3"],	$firstpage, $pieni);
			}
			if ($pankkitiedot["pankkiswift1"] != "") {
				$pdf->draw_text(30,  72, "SWIFT: ".$pankkitiedot["pankkiswift1"],	$firstpage, $pieni);
			}
			if ($pankkitiedot["pankkiswift2"] != "") {
				$pdf->draw_text(217, 72, "SWIFT: ".$pankkitiedot["pankkiswift2"],	$firstpage, $pieni);
			}
			if ($pankkitiedot["pankkiswift3"] != "") {
				$pdf->draw_text(404, 72, "SWIFT: ".$pankkitiedot["pankkiswift3"],	$firstpage, $pieni);
			}

			//Alimmat kolme laatikkoa, yhti�tietoja
			$pdf->draw_rectangle(65, 20,  20, 580,	$firstpage, $rectparam);
			$pdf->draw_rectangle(65, 207, 20, 580,	$firstpage, $rectparam);
			$pdf->draw_rectangle(65, 394, 20, 580,	$firstpage, $rectparam);

			$pdf->draw_text(30, 55, $toimipaikkarow["nimi"],					$firstpage, $pieni);
			$pdf->draw_text(30, 45, $toimipaikkarow["osoite"],					$firstpage, $pieni);
			$pdf->draw_text(30, 35, $toimipaikkarow["postino"]."  ".$toimipaikkarow["postitp"],	$firstpage, $pieni);
			$pdf->draw_text(30, 25, $toimipaikkarow["maa"],						$firstpage, $pieni);

			$pdf->draw_text(217, 55, t("Puhelin", $kieli).":",					$firstpage, $pieni);
			$pdf->draw_text(247, 55, $toimipaikkarow["puhelin"],				$firstpage, $pieni);
			$pdf->draw_text(217, 45, t("Fax", $kieli).":",						$firstpage, $pieni);
			$pdf->draw_text(247, 45, $toimipaikkarow["fax"],					$firstpage, $pieni);
			$pdf->draw_text(217, 35, t("Email", $kieli).":",					$firstpage, $pieni);
			$pdf->draw_text(247, 35, $toimipaikkarow["email"],					$firstpage, $pieni);

			$pdf->draw_text(404, 55, t("Y-tunnus", $kieli).":",					$firstpage, $pieni);
			$pdf->draw_text(444, 55, $toimipaikkarow["vat_numero"],				$firstpage, $pieni);
			$pdf->draw_text(404, 45, t("Kotipaikka", $kieli).":",				$firstpage, $pieni);
			$pdf->draw_text(444, 45, $toimipaikkarow["kotipaikka"],				$firstpage, $pieni);
			$pdf->draw_text(404, 35, t("Enn.per.rek", $kieli),					$firstpage, $pieni);
			$pdf->draw_text(404, 25, t("Alv.rek", $kieli),						$firstpage, $pieni);

		}

	}

	require_once('pdflib/phppdflib.class.php');

	flush();

	//PDF parametrit
	$pdf = new pdffile;
	$pdf->set_default('margin-top', 	0);
	$pdf->set_default('margin-bottom', 	0);
	$pdf->set_default('margin-left', 	0);
	$pdf->set_default('margin-right', 	0);
	$rectparam["width"] = 0.3;

	$norm["height"] 	= 10;
	$norm["font"] 		= "Times-Roman";

	$boldi["height"] 	= 10;
	$boldi["font"] 		= "Times-Bold";

	$pieni["height"] 	= 8;
	$pieni["font"] 		= "Times-Roman";

	$iso["height"] 		= 14;
	$iso["font"] 		= "Helvetica-Bold";

	// defaultteja
	$lask = 1;
	$sivu = 1;

	// aloitellaan laskun teko
	if (is_array($lasku_tunnus)) {
		$ltunnukset = implode(",", $lasku_tunnus);
	}
	else {
		$ltunnukset = $lasku_tunnus;
	}

	if ($nayta_pdf == 1 and $karhutunnus != '') {
		$karhutunnus = mysql_real_escape_string($karhutunnus);
		$kjoinlisa = " and kl.ktunnus = '$karhutunnus' ";

		$query = "	SELECT count(distinct ktunnus) ktun
					FROM karhu_lasku
					JOIN karhukierros ON (karhukierros.tunnus = karhu_lasku.ktunnus AND karhukierros.tyyppi = '')
					WHERE ltunnus in ($ltunnukset)
					AND ktunnus <= $karhutunnus";
		$karhukertares = pupe_query($query);
		$karhukertarow = mysql_fetch_assoc($karhukertares);

		$karhukertanro = $karhukertarow["ktun"];
		$ikalaskenta = " TO_DAYS(kk.pvm) - TO_DAYS(l.erpcm) as ika, ";
	}
	else {
		$kjoinlisa = "";
		$karhukertanro = "";
		$ikalaskenta = " TO_DAYS(now()) - TO_DAYS(l.erpcm) as ika, ";
	}

	$query = "	SELECT l.tunnus, l.tapvm, l.liitostunnus,
				l.summa-l.saldo_maksettu summa,
				l.summa_valuutassa-l.saldo_maksettu_valuutassa summa_valuutassa,
				l.erpcm, l.laskunro, l.viite,
				l.yhtio_toimipaikka, l.valkoodi, l.maksuehto, l.maa,
				$ikalaskenta
				max(kk.pvm) as kpvm,
				count(distinct kl.ktunnus) as karhuttu
				FROM lasku l
				LEFT JOIN karhu_lasku kl on (l.tunnus = kl.ltunnus $kjoinlisa)
				LEFT JOIN karhukierros kk on (kk.tunnus = kl.ktunnus AND kk.tyyppi = '')
				WHERE l.tunnus in ($ltunnukset)
				and l.yhtio = '$kukarow[yhtio]'
				and l.tila = 'U'
				GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13
				ORDER BY l.erpcm";
	$result = pupe_query($query);

	//otetaan maksuehto- ja asiakastiedot ekalta laskulta
	$laskutiedot = mysql_fetch_assoc($result);

	$query = "	SELECT *
				FROM maksuehto
				LEFT JOIN pankkiyhteystiedot ON (pankkiyhteystiedot.yhtio = maksuehto.yhtio AND pankkiyhteystiedot.tunnus = maksuehto.pankkiyhteystiedot)
				WHERE maksuehto.yhtio='$kukarow[yhtio]' AND maksuehto.tunnus = '$laskutiedot[maksuehto]'";
	$maksuehtoresult = pupe_query($query);
	$maksuehtotiedot = mysql_fetch_assoc($maksuehtoresult);

	$query = "	SELECT *
				FROM asiakas
				WHERE yhtio='$kukarow[yhtio]' AND tunnus = '$laskutiedot[liitostunnus]'";
	$asiakasresult = pupe_query($query);
	$asiakastiedot = mysql_fetch_assoc($asiakasresult);

	//Otetaan t�ss� asiakkaan kieli talteen
	$kieli = $asiakastiedot["kieli"];

	//ja kelataan akuun
	mysql_data_seek($result, 0);

	$query = "	SELECT GROUP_CONCAT(distinct liitostunnus) liitokset
				FROM lasku
				WHERE lasku.yhtio = '$kukarow[yhtio]'
				and lasku.tunnus in ($ltunnukset)";
	$lires = pupe_query($query);
	$lirow = mysql_fetch_assoc($lires);

	// Karhuvaiheessa t�m� on tyhj�
	if ($laskutiedot["kpvm"] == "") {
		$laskutiedot["kpvm"] = date("Y-m-d");
	}

	$query = "	SELECT SUM(summa) summa
				FROM suoritus
				WHERE yhtio  = '$kukarow[yhtio]'
				and ltunnus <> 0
				and (kohdpvm = '0000-00-00' or kohdpvm >= '$laskutiedot[kpvm]')
				and kirjpvm <= '$laskutiedot[kpvm]'
				and asiakas_tunnus in ($lirow[liitokset])";
	$summaresult = pupe_query($query);
	$kaato = mysql_fetch_assoc($summaresult);

	// haetaan yhti�n toimipaikkojen yhteystiedot
	if ($laskutiedot["yhtio_toimipaikka"] != '' and $laskutiedot["yhtio_toimipaikka"] != 0) {
		$toimipaikkaquery = "	SELECT *
								FROM yhtion_toimipaikat
								WHERE yhtio	= '$kukarow[yhtio]'
								AND tunnus	= '$laskutiedot[yhtio_toimipaikka]'";
		$toimipaikkares = pupe_query($toimipaikkaquery);
		$toimipaikkarow = mysql_fetch_assoc($toimipaikkares);
	}
	else {
		$toimipaikkarow["nimi"] 		= $yhtiorow["nimi"];
		$toimipaikkarow["osoite"] 		= $yhtiorow["osoite"];
		$toimipaikkarow["postino"] 		= $yhtiorow["postino"];
		$toimipaikkarow["postitp"] 		= $yhtiorow["postitp"];
		$toimipaikkarow["maa"] 			= $yhtiorow["maa"];
		$toimipaikkarow["puhelin"] 		= $yhtiorow["puhelin"];
		$toimipaikkarow["fax"] 			= $yhtiorow["fax"];
		$toimipaikkarow["email"] 		= $yhtiorow["email"];
		$toimipaikkarow["vat_numero"] 	= $yhtiorow["ytunnus"];
		$toimipaikkarow["kotipaikka"] 	= $yhtiorow["kotipaikka"];
	}

	$kaatosumma=$kaato["summa"] * -1;
	if (!$kaatosumma) $kaatosumma='0.00';

    //	Arvotaan oikea karhuviesti
	if (!isset($karhuviesti)) {

		//	Lasketaan kuinka vanhoja laskuja t�ss� karhutaan
		$query = "	SELECT count(*) kpl
					FROM karhu_lasku
					WHERE ltunnus IN ($ltunnukset)
					GROUP BY ltunnus;";
		$res = mysql_query($query) or pupe_error();
		$r = 0;

		while ($a = mysql_fetch_assoc($res)) {
			$r += $a["kpl"];
		}

		//	T�m� on mik� on karhujen keskim��r�inen kierroskerta
		$avg = floor(($r/mysql_num_rows($res))+1);

		if ($tee_pdf == 'tulosta_karhu') {
			$avg--;
		}

		// Etsit��n asiakkaan kielell�:
		$query = "	SELECT tunnus
					FROM avainsana
					WHERE yhtio ='{$yhtiorow['yhtio']}' and laji = 'KARHUVIESTI' and jarjestys = '$avg' and kieli = '$kieli'";
		$res = mysql_query($query) or pupe_error();

		if (mysql_num_rows($res) == 0) {

			$query = "	SELECT tunnus
						FROM avainsana
						WHERE yhtio ='{$yhtiorow['yhtio']}' and laji = 'KARHUVIESTI' and jarjestys < '$avg' and kieli = '$kieli'
						ORDER BY jarjestys DESC
						LIMIT 1";
			$res = mysql_query($query) or pupe_error();

			if (mysql_num_rows($res) == 0) {

				$query = "	SELECT tunnus
							FROM avainsana
							WHERE yhtio ='{$yhtiorow['yhtio']}' and laji = 'KARHUVIESTI' and jarjestys > '$avg' and kieli = '$kieli'
							ORDER BY jarjestys ASC
							LIMIT 1";
				$res = mysql_query($query) or pupe_error();
			}
		}

		// Etsit��n yhti�n kielell�:
		if (mysql_num_rows($res) == 0) {
			$query = "	SELECT tunnus
						FROM avainsana
						WHERE yhtio ='{$yhtiorow['yhtio']}' and laji = 'KARHUVIESTI' and jarjestys = '$avg' and kieli = '$yhtiorow[kieli]'";
			$res = mysql_query($query) or pupe_error();

			if (mysql_num_rows($res) == 0) {

				$query = "	SELECT tunnus
							FROM avainsana
							WHERE yhtio ='{$yhtiorow['yhtio']}' and laji = 'KARHUVIESTI' and jarjestys < '$avg' and kieli = '$yhtiorow[kieli]'
							ORDER BY jarjestys DESC
							LIMIT 1";
				$res = mysql_query($query) or pupe_error();

				if (mysql_num_rows($res) == 0) {

					$query = "	SELECT tunnus
								FROM avainsana
								WHERE yhtio ='{$yhtiorow['yhtio']}' and laji = 'KARHUVIESTI' and jarjestys > '$avg' and kieli = '$yhtiorow[kieli]'
								ORDER BY jarjestys ASC
								LIMIT 1";
					$res = mysql_query($query) or pupe_error();
				}
			}
		}

		$kv = mysql_fetch_assoc($res);
		$karhuviesti = $kv["tunnus"];
	}

	$query = "	SELECT selitetark
				FROM avainsana
				WHERE tunnus = '$karhuviesti'
				AND laji	 = 'KARHUVIESTI'
				AND yhtio 	 = '{$yhtiorow['yhtio']}'";
	$res = mysql_query($query) or pupe_error();
	$viestit = mysql_fetch_assoc($res);

    $karhuviesti = $viestit["selitetark"];

	$firstpage = alku($karhuviesti, $karhutunnus);

	$summa = 0.0;
	$rivit = array();

	while ($row = mysql_fetch_assoc($result)) {
		$rivit[] = $row;
		$summa = rivi($firstpage, $summa);
	}

	// loppusumma
	$loppusumma = sprintf('%.2f', $summa+$kaatosumma);

	// viimenen sivu
	loppu($firstpage,$loppusumma);

	//keksit��n uudelle failille joku varmasti uniikki nimi:
	list($usec, $sec) = explode(' ', microtime());
	mt_srand((float) $sec + ((float) $usec * 100000));
	$pdffilenimi = "/tmp/karhukirje-".md5(uniqid(mt_rand(), true)).".pdf";

	//kirjoitetaan pdf faili levylle..
	$fh = fopen($pdffilenimi, "w");
	if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus ep�onnistui $pdffilenimi");
	fclose($fh);

	if ($nayta_pdf == 1) {
		echo file_get_contents($pdffilenimi);
	}

	// jos halutaan eKirje sek� configuraatio on olemassa niin
	// l�hetet��n eKirje
	if (isset($_POST['ekirje_laheta']) === true and (isset($ekirje_config) and is_array($ekirje_config))) {

		// ---------------------------------------------------------------------
		// t�h�n ekirjeen l�hetys

		// pdfekirje luokka
		require_once('inc/ekirje.inc');

		list($usec, $sec) = explode(' ', microtime());
		mt_srand((float) $sec + ((float) $usec * 100000));

		$ekirje_tunnus = md5(uniqid(mt_rand(), true));

		$info = array(
			'tunniste'              => $ekirje_tunnus, 			// asiakkaan oma kirjeen tunniste
	        'kirjeluokka'           => '1',                    	// 1 = priority, 2 = economy
	        'osasto'                => $kukarow['yhtio'],       // osastokohtainen erittely = mik� yritys
	        'file_id'               => $ekirje_tunnus,          // l�hett�j�n tunniste tiedostolle
	        'kirje_id'              => $ekirje_tunnus,          // kirjeen id
			'contact_name'          => $kukarow['nimi'],
			'contact_email'         => $kukarow['eposti'],
			'contact_phone'         => $kukarow['puhno'],
			'yritys_nimi'           => trim($yhtiorow['nimi'] . ' ' . $yhtiorow['nimitark']),
			'yritys_osoite'         => $yhtiorow['osoite'],
			'yritys_postino'        => $yhtiorow['postino'],
			'yritys_postitp'        => $yhtiorow['postitp'],
	        'yritys_maa'            => $yhtiorow['maa'],
	        'vastaanottaja_nimi'    => trim($asiakastiedot['nimi'] . ' ' . $asiakastiedot['nimitark']),
	        'vastaanottaja_osoite'  => $asiakastiedot['osoite'],
	        'vastaanottaja_postino' => $asiakastiedot['postino'],
	        'vastaanottaja_postitp' => $asiakastiedot['postitp'],
	        'vastaanottaja_maa'     => $asiakastiedot['maa'],
	        'sivumaara'             => $sivu,
		);

		// otetaan configuraatio filest� salasanat ja muut
		$info = array_merge($info, (array) $ekirje_config);

		$ekirje = new Pupe_Pdfekirje($info);

		//koitetaan l�hett�� eKirje
		$ekirje->send($pdffilenimi);

		// poistetaan filet omalta koneelta
		$ekirje->clean();
	}

	// ------------------------------------------------------------------------
	//
	// nyt kirjoitetaan tiedot vasta kantaan kun tiedet��n ett� kirje
	// on l�htenyt Itellaan tai tulostetaan kirje ainoastaan

	if ($tee_pdf != 'tulosta_karhu') {
		$karhukierros = uusi_karhukierros($kukarow['yhtio']);

		foreach ($rivit as $row) {
			liita_lasku($karhukierros, $row['tunnus']);
		}
	}

	// tulostetaan jos ei l�hetet� ekirjett�
	if (isset($_POST['ekirje_laheta']) === false and $tee_pdf != 'tulosta_karhu') {
		// itse print komento...
		$query = "	SELECT komento
					from kirjoittimet
					where yhtio='{$kukarow['yhtio']}' and tunnus = '{$kukarow['kirjoitin']}'";
		$kires = pupe_query($query);

		if (mysql_num_rows($kires) == 1) {
			$kirow = mysql_fetch_assoc($kires);
			if($kirow["komento"] == "email") {
				$liite = $pdffilenimi;
				$kutsu = "Karhukirje ".$asiakastiedot["ytunnus"];
				echo t("Karhukirje l�hetet��n osoitteeseen $kukarow[eposti]")."...\n<br>";

				require("inc/sahkoposti.inc");
			}
			else {
				$line = exec("{$kirow['komento']} $pdffilenimi");
			}
		}
	}
?>