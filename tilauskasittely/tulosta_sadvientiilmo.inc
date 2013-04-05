<?php

	$pdf2 = new pdffile;

	$pdf2->set_default('margin-top', 20);
	$pdf2->set_default('margin-bottom', 0);
	$pdf2->set_default('margin-left', 0);
	$pdf2->set_default('margin-right', 0);

	$pdf2->enable('import');

	ob_start();

	$filename = "../pupetullipohja.pdf";

	$handle = fopen($filename, "r");
	$d = fread($handle, filesize($filename));
	fclose($handle);

	$pdf2->import->append($d);

	$pages = $pdf2->import->get_pages();

	ob_end_clean();


	$kato["height"] = 14;
	$kato["font"] = "Times-Roman";

	$ky["height"] = 12;
	$ky["font"] = "Times-Roman";

	$kymppi["height"] = 12;
	$kymppi["font"] = "Courier";

	$kasi["height"] = 10;
	$kasi["font"] = "Times-Roman";

	$kutonen["height"] = 8;
	$kutonen["font"] = "Times-Roman";

	$nelonen["height"] = 6;
	$nelonen["font"] = "Times-Roman";

	$rectparam["width"] = 0.3;
	$rectparambold["width"] = 3.3;
	$rectparamleima["width"] = 1.0;

	if (!function_exists('sad_dateconv')) {
		function sad_dateconv ($date) {
			//kääntää mysqln vvvv-kk-mm muodon muotoon pp.kk.vv
			return substr($date,8,2).".".substr($date,5,2).".".substr($date,2,2);
		}
	}
	if (!function_exists('print_ytunnus')) {
		function print_ytunnus ($ytunnus) {
			//kääntää ytunnuksen kauniseen muotoon
			$ytunnus = sprintf('%08s', $ytunnus);
			return substr($ytunnus,0,7)."-".substr($ytunnus,-1);
		}
	}

	//tulostetaan kotitullausleima jos yhtiöllä on kotitullauslupa
	if ($yhtiorow["kotitullauslupa"] != "") {
		$pdf2->draw_rectangle(825, 405,  755, 568, 								$pages[0], $rectparamleima);
		$pdf2->draw_rectangle(825, 405,  800, 440, 								$pages[0], $rectparamleima);
		$pdf2->draw_text(415, 808, "FI", 										$pages[0], $kato);
		$pdf2->draw_rectangle(825, 440,  800, 568, 								$pages[0], $rectparamleima);
		$pdf2->draw_text(470, 808, "MAATULLI", 									$pages[0], $kato);
		$pdf2->draw_rectangle(800, 405,  780, 470,								$pages[0], $rectparamleima);
		$pdf2->draw_text(415, 785, $laskurow["laskunro"],						$pages[0], $ky);
		$pdf2->draw_rectangle(800, 470,  780, 568,								$pages[0], $rectparamleima);
		$pdf2->draw_text(497, 785, sad_dateconv($laskurow["tapvm"]),				$pages[0], $ky);

		$pdf2->draw_rectangle(780, 405,  755, 518, 								$pages[0], $rectparamleima);
		$pdf2->draw_text(407, 762, $yhtiorow["nimi"], 							$pages[0], $kasi);

		$pdf2->draw_rectangle(780, 518,  755, 568, 								$pages[0], $rectparamleima);
		$pdf2->draw_text(522, 762, $yhtiorow["tullin_lupanro"],					$pages[0], $ky);
	}

	//Kenttä 1 Imoitustyyppi, Ilmoitus
	$pdf2->draw_text(320, 785, "EX", 											$pages[0], $kymppi);
	$pdf2->draw_text(347, 785, "A",			 									$pages[0], $kymppi);

	//Kenttä 2 Viejä
	$pdf2->draw_text(61,  783, $yhtiorow["nimi"], 								$pages[0], $kymppi);
	$pdf2->draw_text(180, 794, $yhtiorow["maa"].print_ytunnus($yhtiorow["ytunnus"]),  $pages[0], $kymppi);
	$pdf2->draw_text(61,  771, $yhtiorow["osoite"], 							$pages[0], $kymppi);
	$pdf2->draw_text(61,  759, $yhtiorow["postino"]." ".$yhtiorow["postitp"], 	$pages[0], $kymppi);

	//Tehdään tässä välissä Kollien ja tavaran kuvausten tiedot. Tämä siks, et saatais heti tässä sivujen kokonaismäärä.
	$sivu = 1;

	//hetaan kaikki otunnukset jotka löytyvät tän uusiotunnuksen alta
	$query = "	SELECT distinct otunnus
				FROM tilausrivi
				WHERE tilausrivi.uusiotunnus = '$uusiotunnus'
				and tilausrivi.yhtio='$kukarow[yhtio]'";
	$uresult = mysql_query($query) or pupe_error($query);

	$tunnukset = '';

	while($urow = mysql_fetch_array($uresult)) {
		$tunnukset  .= "'".$urow['otunnus']."',";
	}

	$tunnukset = substr($tunnukset,0,-1);

	// varmistetaan, että löydettiin otsikoita
	if ($tunnukset != "") {

		//haetaan kollimäärä ja bruttopaino
		$query = "	SELECT *
					FROM rahtikirjat
					WHERE otsikkonro in ($tunnukset)
					and yhtio='$kukarow[yhtio]'";
		$rahtiresult = mysql_query($query) or pupe_error($query);

		$kilot  = 0;
		$kollit = 0;
		$pakkaus = "";
		$pakkauskuvaus = "";

		while($rahtirow = mysql_fetch_array($rahtiresult)) {
			if ($kilot == 0 and $kollit == 0) {
				$pakkaus .= $rahtirow["pakkaus"];
				$pakkauskuvaus .= $rahtirow["pakkauskuvaus"];
			}
			$kilot  += $rahtirow["kilot"];
			$kollit += $rahtirow["kollit"];
		}

		if ($laskurow["bruttopaino"] > 0) {
			$kilot = $laskurow["bruttopaino"];
		}

		$query = "	SELECT
					tuote.tullinimike1,
					tuote.tullinimike2,
					tuote.tullikohtelu,
					(SELECT alkuperamaa FROM tuotteen_toimittajat WHERE tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno and tuotteen_toimittajat.alkuperamaa!='' LIMIT 1) alkuperamaa,
					tullinimike.su,
					tullinimike.su_vientiilmo,
					tullinimike.dm,
					min(tilausrivi.nimitys) nimitys,
					GROUP_CONCAT(distinct trim(tuote.nimitys) ORDER BY tuote.nimitys SEPARATOR '##') nimitys_hieno,
					sum(tilausrivi.rivihinta) rivihinta,
					sum(tilausrivi.kpl) kpl,
					round(sum(if(tuote.tuotemassa!=0, tilausrivi.kpl*tuote.tuotemassa, (tilausrivi.rivihinta/$laskurow[summa])*$kilot)),2) nettop
					FROM tilausrivi
					JOIN tuote ON tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno and tuote.ei_saldoa = ''
					JOIN tullinimike ON tuote.tullinimike1 = tullinimike.cn and tullinimike.kieli = '$yhtiorow[kieli]' and tuote.tullinimike1!=''
					WHERE tilausrivi.uusiotunnus	= '$uusiotunnus'
					and tilausrivi.yhtio			= '$kukarow[yhtio]'
					and tilausrivi.kpl				> 0
					GROUP BY tuote.tullinimike1, tuote.tullinimike2, tuote.tullikohtelu, alkuperamaa, tullinimike.su, tullinimike.su_vientiilmo, tullinimike.dm";
		$riviresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($riviresult) < 1) {
			echo "VIRHE: Laskulla ei ollut yhtään tilausriviä/nimikettä!<br>";
		}
		elseif (mysql_num_rows($riviresult) == 1) {
			$rivirow = mysql_fetch_array($riviresult);

			//Kenttä 31 Kollit ja tavaran kuvaus
			$nimitykset = explode("##", $rivirow["nimitys_hieno"]);

			$apinalask = 495;
			for($nim=0; $nim<8; $nim++) {
				$pdf2->draw_text(61,  $apinalask, substr($nimitykset[$nim],0,35),	$pages[0], $kymppi);
				$apinalask -= 12;
			}

			//Kenttä 32 Tavaraerän järjestysnumero
			$pdf2->draw_text(350, 496, "1", 										$pages[0], $kymppi);

			//Kenttä 33 Taric koodi
			$pdf2->draw_text(385, 496, $rivirow["tullinimike1"], 					$pages[0], $kymppi);

			//Kenttä 34 Alkuperämaan koodi
			$pdf2->draw_text(392, 473, $rivirow["alkuperamaa"], 					$pages[0], $kymppi);

			//Kenttä 37 Tullimenettely
			$pdf2->draw_text(395,  448, substr($rivirow["tullikohtelu"],0,2), 		$pages[0], $kymppi);
			$pdf2->draw_text(425,  448, substr($rivirow["tullikohtelu"],2,2), 		$pages[0], $kymppi);

			//Kenttä 38 Nettopaino
			$pdf2->draw_text(480, 446, $rivirow["nettop"], 							$pages[0], $kymppi);

			//Kenttä 41 Muu paljous
			if ($rivirow["su"] != '') {
				$pdf2->draw_text(385, 397, $rivirow["kpl"]." ".$rivirow["su_vientiilmo"],		$pages[0], $kymppi);
			}

			//Kenttä 5 tavaraerien kokonaismäärä
			$pdf2->draw_text(322, 740, "1", 										$pages[0], $kymppi);

		}
		elseif (mysql_num_rows($riviresult) > 1) {

			//Kenttä 31 Kollit ja tavaran kuvaus
			$pdf2->draw_text(61,  491, "Tullitariffointi lisälehdellä",				$pages[0], $kymppi);
			$sivu = 2;

			if (!function_exists('alku2')) {
				if (trim($yhtiorow["kotitullauslupa"]) != "") {
					function alku2() {
						global $pdf2, $sivu, $kymppi, $kasi, $kutonen, $rectparamleima, $ky, $kato, $nelonen, $rivirow, $laskurow, $rahtirow, $rectparam, $rectparambold, $yhtiorow;

						$lisalehti = $pdf2->new_page("a4");

						$pdf2->draw_rectangle(825, 405,  755, 568, 							$lisalehti, $rectparamleima);
						$pdf2->draw_rectangle(825, 405,  800, 440, 							$lisalehti, $rectparamleima);
						$pdf2->draw_text(415, 808, "FI", 									$lisalehti, $kato);
						$pdf2->draw_rectangle(825, 440,  800, 568, 							$lisalehti, $rectparamleima);
						$pdf2->draw_text(470, 808, "MAATULLI", 								$lisalehti, $kato);
						$pdf2->draw_rectangle(800, 405,  780, 470,							$lisalehti, $rectparamleima);
						$pdf2->draw_text(415, 785, $laskurow["laskunro"],					$lisalehti, $ky);
						$pdf2->draw_rectangle(800, 470,  780, 568,							$lisalehti, $rectparamleima);
						$pdf2->draw_text(497, 785, sad_dateconv($laskurow["tapvm"]),			$lisalehti, $ky);
						$pdf2->draw_rectangle(780, 405,  755, 518, 							$lisalehti, $rectparamleima);
						$pdf2->draw_text(407, 762, $yhtiorow["nimi"], 						$lisalehti, $kasi);
						$pdf2->draw_rectangle(780, 518,  755, 568, 							$lisalehti, $rectparamleima);
						$pdf2->draw_text(522, 762, $yhtiorow["tullin_lupanro"],				$lisalehti, $ky);

						$pdf2->draw_text(20,  803, "LISÄLEHTI / ",							$lisalehti, $kasi);
						$pdf2->draw_text(85,  815, "YKSINKERTAISTETTU VIENTI - SU0MI",		$lisalehti, $kasi);
						$pdf2->draw_text(85,  803, "SIMPLIFIED EXPORTATION - FINLAND",		$lisalehti, $kasi);
						$pdf2->draw_text(85,  791, "ETY",									$lisalehti, $kasi);
						$pdf2->draw_text(115, 795, "nr",									$lisalehti, $kutonen);
						$pdf2->draw_text(115, 788, "nro",									$lisalehti, $kutonen);
						$pdf2->draw_text(140, 791, "2454 / 93",								$lisalehti, $kasi);
						$pdf2->draw_text(400, 791, "A VIENTITULLITOIMIPAIKAN MERKINNÄT",	$lisalehti, $kutonen);

						//Kenttä 1 Tulliselvitysmuoto
						$pdf2->draw_rectangle(800, 290,  760, 387, 							$lisalehti, $rectparam);
						$pdf2->draw_rectangle(785, 320,  760, 320, 							$lisalehti, $rectparam);
						$pdf2->draw_rectangle(785, 340,  760, 340, 							$lisalehti, $rectparam);
						$pdf2->draw_text(293, 791, "1. TULLISELVITYSMUOTO",					$lisalehti, $nelonen);
						$pdf2->draw_text(300, 764, "EX", 									$lisalehti, $kymppi);
						$pdf2->draw_text(327, 764, "A",			 							$lisalehti, $kymppi);

						//Kenttä 3 Lomakemäärä
						$pdf2->draw_rectangle(760, 290,  735, 387, 							$lisalehti, $rectparam);
						$pdf2->draw_rectangle(745, 315,  735, 315, 							$lisalehti, $rectparam);
						$pdf2->draw_rectangle(758, 332,  737, 386, 							$lisalehti, $rectparambold);
						$pdf2->draw_text(293, 753, "3 Lomake",								$lisalehti, $nelonen);
						$pdf2->draw_text(298, 747, "määrä",									$lisalehti, $nelonen);
						$pdf2->draw_text(298, 737, "$sivu",									$lisalehti, $kymppi);


						//Kenttä 2 Viejä
						$pdf2->draw_rectangle(785, 20,  735, 290, 							$lisalehti, $rectparam);
						$pdf2->draw_text(25,  776, "2. Lähetäjä/Viejä",						$lisalehti, $kutonen);
						$pdf2->draw_text(190, 776, "Asiakasnumero",							$lisalehti, $kutonen);
						$pdf2->draw_text(25,  765, $yhtiorow["nimi"], 						$lisalehti, $kymppi);
						$pdf2->draw_text(190, 765, $yhtiorow["maa"].print_ytunnus($yhtiorow["ytunnus"]), $lisalehti, $kymppi);
						$pdf2->draw_text(25,  753, $yhtiorow["osoite"], 					$lisalehti, $kymppi);
						$pdf2->draw_text(25,  741, $yhtiorow["postino"]." ".$yhtiorow["postitp"], $lisalehti, $kymppi);

						//Rivien  otsikkotiedot
						$pdf2->draw_rectangle(735, 20,  690, 570, 							$lisalehti, $rectparam);
						$pdf2->draw_text(25,  725, "32 T. erän järj. nro", 					$lisalehti, $kutonen);
						$pdf2->draw_text(25,  710, "31 Kolliluku ja laji",					$lisalehti, $kutonen);
						$pdf2->draw_text(25,  695, "44 Lisätietoja", 						$lisalehti, $kutonen);
						$pdf2->draw_text(110, 725, "33 Tullinimike", 						$lisalehti, $kutonen);

						$pdf2->draw_rectangle(735, 263,  700, 263, 							$lisalehti, $rectparam);
						$pdf2->draw_text(287, 725, "38 Nettopaino", 						$lisalehti, $kutonen);
						$pdf2->draw_text(287, 710, "41 Lisäpaljous ja",						$lisalehti, $kutonen);
						$pdf2->draw_text(297, 703, "laji",									$lisalehti, $kutonen);

						$pdf2->draw_rectangle(735, 365,  700, 365, 							$lisalehti, $rectparam);
						$pdf2->draw_text(370, 725, "37 Kohtelukoodi", 						$lisalehti, $kutonen);
						$pdf2->draw_text(370, 710, "46 Vientiarvo EUR",						$lisalehti, $kutonen);

						$pdf2->draw_rectangle(735, 450,  700, 450, 							$lisalehti, $rectparam);
						$pdf2->draw_text(455, 725, "34 AM 13 (Tk) 36 (Ts)", 				$lisalehti, $kutonen);
						$pdf2->draw_text(455, 710, "47 Verolaskelma",						$lisalehti, $kutonen);

						//sarakkeet
						$pdf2->draw_rectangle(690, 20,  20, 570, 							$lisalehti, $rectparam);
						$pdf2->draw_rectangle(690, 283, 20, 283, 							$lisalehti, $rectparam);
						$pdf2->draw_rectangle(690, 365, 20, 365, 							$lisalehti, $rectparam);
						$pdf2->draw_rectangle(690, 450, 20, 450, 							$lisalehti, $rectparam);

						return($lisalehti);
					}
				}
				else {
					function alku2() {
						global $pdf2, $sivu, $kymppi, $kasi, $kutonen, $rectparamleima, $ky, $kato, $nelonen, $rivirow, $laskurow, $kukarow, $rahtirow, $rectparam, $rectparambold, $yhtiorow;

						// Haetaan pohja
						ob_start();

						$filename = "../pupetullipohja_eikotitullaus.pdf";

						$handle = fopen($filename, "r");
						$d = fread($handle, filesize($filename));
						fclose($handle);

						$pdf2->import->append($d);

						$lisalehti = $pdf2->import->get_pages();

						ob_end_clean();

						//Kenttä 1 ILMOITUS
						$pdf2->draw_text(320, 794, "EX", 									$lisalehti[0], $kymppi);
						$pdf2->draw_text(347, 794, "A",			 							$lisalehti[0], $kymppi);

						//Kenttä 2 Viejä
						$pdf2->draw_text(62,  791, $yhtiorow["nimi"], 						$lisalehti[0], $kymppi);
						$pdf2->draw_text(190, 802, $yhtiorow["maa"].print_ytunnus($yhtiorow["ytunnus"]), $lisalehti[0], $kymppi);
						$pdf2->draw_text(62,  778, $yhtiorow["osoite"], 					$lisalehti[0], $kymppi);
						$pdf2->draw_text(62,  766, $yhtiorow["postino"]." ".$yhtiorow["postitp"], $lisalehti[0], $kymppi);

						//Kenttä 3 Lomakemäärä
						$pdf2->draw_text(320, 767, $sivu,									$lisalehti[0], $kymppi);

						//Kenttä C LÄHTÖTOIMIPAIKKA
						$pdf2->draw_text(mm_pt(155),  mm_pt(20), $yhtiorow["nimi"], 					$lisalehti[0], $kasi);
						$pdf2->draw_text(mm_pt(155),  mm_pt(15), $kukarow["nimi"], 						$lisalehti[0], $kasi);
						$pdf2->draw_text(mm_pt(155),  mm_pt(10), $yhtiorow["puhelin"], 					$lisalehti[0], $kasi);

						return($lisalehti[0]);
					}
				}
			}

			if (trim($yhtiorow["kotitullauslupa"]) != "") {
				$rivikohta = 675;
			}
			else {
				$rivikohta = 262;
			}

			$lisalehti[$sivu] = alku2();
			$lask1  = 1;
			$lask2	= 1;

			//ylimääräisten erien vaikutus
			$extrat = abs($laskurow["lisattava_era"])-abs($laskurow["vahennettava_era"]);

			while ($rivirow = mysql_fetch_array($riviresult)) {
				if (($lask2 >= 18 and trim($yhtiorow["kotitullauslupa"]) != "") or ($lask2 > 3 and trim($yhtiorow["kotitullauslupa"]) == "")) {
					$sivu++;
					$lask2= 1;

					if (trim($yhtiorow["kotitullauslupa"]) != "") {
						$rivikohta = 675;
					}
					else {
						$rivikohta = 262;
					}

					$lisalehti[$sivu] 	= alku2();
				}

				//tullausarvi lisäerineen
				if (($extrat != 0) and ($laskurow["summa"] != 0)) {
					$tullarvo = round(($rivirow["rivihinta"]/$laskurow["summa"]*$extrat)+$rivirow["rivihinta"],2);
				}
				else {
					$tullarvo = $rivirow["rivihinta"];
				}

				if (trim($yhtiorow["kotitullauslupa"]) != "") {
					//Kenttä 32 Tavaraerän järjestysnumero
					$pdf2->draw_text(25,   $rivikohta, $lask1, 								$lisalehti[$sivu], $kymppi);

					//Kenttä 33 Taric koodi
					$pdf2->draw_text(110,  $rivikohta, $rivirow["tullinimike1"], 			$lisalehti[$sivu], $kymppi);

					//Kenttä 38 Nettopaino
					//lasketaan nettopaino

					$pdf2->draw_text(287,  $rivikohta, $rivirow["nettop"],					$lisalehti[$sivu], $kymppi);

					//Kenttä 37 Kohtelukoodi
					$pdf2->draw_text(370,  $rivikohta, $rivirow["tullikohtelu"], 			$lisalehti[$sivu], $kymppi);

					//Kenttä 34 Alkuperämaan koodi
					$pdf2->draw_text(455,  $rivikohta, $rivirow["alkuperamaa"], 			$lisalehti[$sivu], $kymppi);

					//Kenttä 41 Muu paljous
					if ($rivirow["su"] != '') {
						$pdf2->draw_text(287, $rivikohta-11, $rivirow["kpl"]." ".$rivirow["su_vientiilmo"], $lisalehti[$sivu], $kymppi);
					}

					//Kenttä 46 Vientiarvo, tilastollinen!
					$pdf2->draw_text(370,  $rivikohta-12, sprintf("%01.2f", $tullarvo),	$lisalehti[$sivu], $kymppi);

					//Kenttä 31 Kollit ja tavaran kuvaus
					$nimitykset = explode("##", $rivirow["nimitys"]);

					foreach($nimitykset as $nimitys) {
						$pdf2->draw_text(110,  $rivikohta-12, substr($nimitys,0,20),	$lisalehti[$sivu], $kymppi);
						$rivikohta -= 12;
						$lask2=$lask2+0.33;
					}
					$rivikohta -= 36;
				}
				else {
					//Kenttä 32 Tavaraerän järjestysnumero
					$pdf2->draw_text(mm_pt(125),   mm_pt($rivikohta), $lask1,									$lisalehti[$sivu], $kymppi);

					//Kenttä 33 Taric koodi
					$pdf2->draw_text(mm_pt(135),  mm_pt($rivikohta), $rivirow["tullinimike1"], 					$lisalehti[$sivu], $kymppi);

					//Kenttä 38 Nettopaino
					//lasketaan nettopaino

					$pdf2->draw_text(mm_pt(160),  mm_pt($rivikohta-17), $rivirow["nettop"],						$lisalehti[$sivu], $kymppi);

					//Kenttä 37 Kohtelukoodi
					$pdf2->draw_text(mm_pt(140),  mm_pt($rivikohta-17), substr($rivirow["tullikohtelu"],0,2), 	$lisalehti[$sivu], $kymppi);
					$pdf2->draw_text(mm_pt(149),  mm_pt($rivikohta-17), substr($rivirow["tullikohtelu"],2,2), 	$lisalehti[$sivu], $kymppi);

					//Kenttä 34 Alkuperämaan koodi
					$pdf2->draw_text(mm_pt(138),  mm_pt($rivikohta-9), $rivirow["alkuperamaa"], 				$lisalehti[$sivu], $kymppi);

					//Kenttä 41 Muu paljous
					if ($rivirow["su"] != '') {
						$pdf2->draw_text(mm_pt(135), mm_pt($rivikohta-34), $rivirow["kpl"]." ".$rivirow["su_vientiilmo"],	$lisalehti[$sivu], $kymppi);
					}

					//Kenttä 46 Vientiarvo, tilastollinen!
					$pdf2->draw_text(mm_pt(145),  mm_pt($rivikohta-50), $laskurow["valkoodi"],					$lisalehti[$sivu], $kymppi);

					//Kenttä 46 Vientiarvo, tilastollinen!
					$pdf2->draw_text(mm_pt(170),  mm_pt($rivikohta-50), sprintf("%01.2f", $tullarvo),			$lisalehti[$sivu], $kymppi);

					//Kenttä 31 Kollit ja tavaran kuvaus
					$nimitykset = explode("##", $rivirow["nimitys_hieno"]);

					$rivikohta_bck = $rivikohta;

					for($nim=0; $nim<8; $nim++) {
						$pdf2->draw_text(mm_pt(22),  mm_pt($rivikohta), substr($nimitykset[$nim],0,35),			$lisalehti[$sivu], $kymppi);
						$rivikohta -= 5;
					}
					$rivikohta = $rivikohta_bck;

					$pdf2->draw_text(61,  mm_pt($rivikohta-36), "N380 $laskurow[laskunro] - ".tv1dateconv($laskurow["tapvm"]), 	$lisalehti[$sivu], $kymppi);
					$pdf2->draw_text(61,  mm_pt($rivikohta-41), "30400",														$lisalehti[$sivu], $kymppi);

					$rivikohta -= 59;
				}
				$lask1++;
				$lask2++;
			}

			//Kenttä 5 tavaraerien kokonaismäärä
			$pdf2->draw_text(322, 735, mysql_num_rows($riviresult),					$pages[0], $kymppi);

			//lisalehtiin piirretään kans lomakemäärän kokonaisluku
			for ($i = 2; $i <= $sivu; $i++) {
				$pdf2->draw_text(343, 767, $sivu,									$lisalehti[$i], $kymppi);
			}
		}

		//Kenttä 3 Lomakenro
		$pdf2->draw_text(322, 760, "1",	 											$pages[0], $kymppi);
		$pdf2->draw_text(342, 760, "$sivu",			 								$pages[0], $kymppi);

		//Kenttä 4
		//Voidaan jättää tyhjäksi

		//Kenttä 6 Kollimäärä
		$pdf2->draw_text(365, 735, $kollit, 										$pages[0], $kymppi);

		//Kenttä 7 Ilmoittajan viitenumero
		$pdf2->draw_text(425, 735, $yhtiorow["ytunnus"]." ".$laskurow["laskunro"],	$pages[0], $kymppi);

		//Kenttä 8
		$pdf2->draw_text(61,  709, $laskurow["nimi"], 								$pages[0], $kymppi);
		$pdf2->draw_text(61,  697, $laskurow["osoite"], 							$pages[0], $kymppi);
		$pdf2->draw_text(61,  685, $laskurow["postino"]."".$laskurow["postitp"], 	$pages[0], $kymppi);
		$pdf2->draw_text(61,  673, $laskurow["maa"], 								$pages[0], $kymppi);

		//Kenttä 9
		//Näitä ei tässä täytetä

		//Kenttä 10
		//Näitä ei tässä täytetä

		//Kenttä 11
		//Näitä ei tässä täytetä

		//Kenttä 12
		//Näitä ei tässä täytetä

		//Kenttä 13
		//Näitä ei tässä täytetä

		//Kenttä 14
		$pdf2->draw_text(61,  640, $yhtiorow["nimi"], 								$pages[0], $kymppi);
		$pdf2->draw_text(180, 649, $yhtiorow["maa"].print_ytunnus($yhtiorow["ytunnus"]),$pages[0], $kymppi);
		$pdf2->draw_text(61,  628, $yhtiorow["osoite"], 							$pages[0], $kymppi);
		$pdf2->draw_text(61,  616, $yhtiorow["postino"]."  ".$yhtiorow["postitp"], 	$pages[0], $kymppi);

		//Kenttä 15 Lähetysmaan koodi
		$pdf2->draw_text(350,  638, "",						 						$pages[0], $kymppi);
		$pdf2->draw_text(495,  638, "FI",				 							$pages[0], $kymppi);

		//Kenttä 16
		//Tätä ei tässä täytetä

		//Kenttä 17 Määrämaan koodi
		$pdf2->draw_text(525,  638, $laskurow["maa_maara"], 						$pages[0], $kymppi);

		//Kenttä 18 Sisämaan kuljetus
		$pdf2->draw_text(61,  592, $laskurow["sisamaan_kuljetus"], 					$pages[0], $kymppi);

		//Kenttä 19 Kontti
		$pdf2->draw_text(300,  592, $laskurow["kontti"], 							$pages[0], $kymppi);

		//Kenttä 20 Toimitusehto
		$pdf2->draw_text(320,  592, substr($laskurow["toimitusehto"],0,3),			$pages[0], $kymppi);
		$pdf2->draw_text(360,  592, substr($laskurow["toimitusehto"],4), 			$pages[0], $kymppi);

		//Kenttä 21 Akt kuljetusväline
		$pdf2->draw_text(61,  568, $laskurow["aktiivinen_kuljetus"],				$pages[0], $kymppi);
		$pdf2->draw_text(300, 568, $laskurow["aktiivinen_kuljetus_kansallisuus"],	$pages[0], $kymppi);

		//Kenttä 22 Valuutta ja kokonaissumma
		$pdf2->draw_text(320,  568, $laskurow["valkoodi"],							$pages[0], $kymppi);
		$pdf2->draw_text(360,  568, $laskurow["summa"],								$pages[0], $kymppi);

		//Kenttä 23 Muuntokurssi, vapaaehtoinen
		$pdf2->draw_text(465,  568, "",			 									$pages[0], $kymppi);

		//Kenttä 24 Kauppatapahtuman luonne
		$pdf2->draw_text(518,  568, $laskurow["kauppatapahtuman_luonne"], 			$pages[0], $kymppi);

		//Kenttä 25 Kuljetusmuoto
		$pdf2->draw_text(61,  544, $laskurow["kuljetusmuoto"], 						$pages[0], $kymppi);

		//Kenttä 26 Sisämaan kuljetusmuoto
		$pdf2->draw_text(130,  544, $laskurow["sisamaan_kuljetusmuoto"], 			$pages[0], $kymppi);

		//Kenttä 27
		//Tätä ei tässä täytetä

		//Kenttä 28 Maksu ja pankkitietoja
		$pdf2->draw_text(360,  544, $laskurow["summa"], 							$pages[0], $kymppi);
		$pdf2->draw_text(470,  544, $laskurow["vahennettava_era"],					$pages[0], $kymppi);
		$pdf2->draw_text(360,  520, $laskurow["lisattava_era"],						$pages[0], $kymppi);

		//lasketaan tilastoarvo, varmistetaan että summat menee oikein ottamalla abs-arvo eristä, jos käyttäjä siis on antanu jotain negatiivisia summia tai jotain
		$tilastoarvo = $laskurow["summa"]-abs($laskurow["vahennettava_era"])+abs($laskurow["lisattava_era"]);

		$pdf2->draw_text(470,  520, $tilastoarvo, 									$pages[0], $kymppi);

		//Kenttä 29 Postumispaikka
		$pdf2->draw_text(61, 520, $laskurow["poistumistoimipaikka_koodi"], 			$pages[0], $kymppi);

		//Kenttä 30 Tavaran sijaintipaikka
		$query = "	SELECT osoite
					FROM varastopaikat
					WHERE tunnus = '$laskurow[varasto]'
					and yhtio = '$kukarow[yhtio]'
					and osoite != ''";
		$varresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($varresult) > 0) {
			$varrow = mysql_fetch_array($varresult);
			$sijaintipaikka = $varrow["osoite"];
		}
		else {
			$sijaintipaikka = $yhtiorow["osoite"];
		}

		$pdf2->draw_text(195,  520, "O ".substr($sijaintipaikka,0,15), 							$pages[0], $kymppi);

		//Kenttä 35 Bruttopaino
		$pdf2->draw_text(480,  472, $kilot, 													$pages[0], $kymppi);

		//Kenttä 36
		//Tätä ei tässä täytetä

		//Kenttä 39
		//Tätä ei tässä täytetä

		//Kenttä 40
		//En tajuu????

		//Kenttä 42
		//Tätä ei tässä täytetä

		//Kenttä 43
		//Tätä ei tässä täytetä

		//Kenttä 44 Lisätietoja
		$pdf2->draw_text(61,  396, "N380 $laskurow[laskunro] - ".tv1dateconv($laskurow["tapvm"]), 	$pages[0], $kymppi);
		$pdf2->draw_text(61,  386, "30400",					 										$pages[0], $kymppi);

		$sanat = explode(' ', $laskurow["comments"]);
		$rivit = array();
		$ind = 0;

		// mahtuu kolme riviä kommenttia
		foreach($sanat as $sana) {
			if ($pdf2->strlen($rivit[$ind], $kymppi) > 280) {
				$rivit[$ind] .= "\n";
				$ind++;
				if ($ind > 2) break;
			}
			$rivit[$ind] .= "$sana ";
		}

		$apupointteri = 376;
		foreach ($rivit as $rivi) {
			$pdf2->draw_text(61, $apupointteri, $rivi, $pages[0], $kymppi);
			$apupointteri -= 10;
		}

		if ($yhtiorow["kotitullauslupa"] != "") {
			$pdf2->draw_text(61,  377, "Kotitullauslupa ".$yhtiorow["kotitullauslupa"].", Eteläinen tullipiiri",	$pages[0], $kymppi);
			$pdf2->draw_text(61,  366, "Yksinkertaistettu vienti", 					$pages[0], $kymppi);
		}

		//Kenttä 45
		$pdf2->draw_text(420,  352, $laskurow["valkoodi"], 							$pages[0], $kymppi);

		//Kenttä 46 Tilastoarvo
		$pdf2->draw_text(500,  352, $tilastoarvo, 									$pages[0], $kymppi);

		//Kenttä 47
		//Tätä ei tässä täytetä

		//Kenttä 48 Maksun lykkäys
		$pdf2->draw_text(330,  328, $yhtiorow["tullin_asiaknro"], 					$pages[0], $kymppi);

		//Kenttä 49
		//Tätä ei tässä täytetä

		//Kenttä 50 Poistumistoimipaikan alueelle sijoittautuneen välittäjän nimi ja osoite
		//Tätä ei välttämättä käytetä meillä????

		//Kenttä 51
		//Tätä ei tässä täytetä

		//Kenttä 52
		//Tätä ei tässä täytetä

		//Kenttä 53
		//Tätä ei tässä täytetä'

		$pp = date('d');
		$kk = date('m');
		$vv = date('Y');
		$pvm = $pp.".".$kk.".".$vv;

		//Kenttä 54 Tullausilmoituksen allekirjoitus
		$pdf2->draw_text(400,  90, $yhtiorow["kotipaikka"]." ".$pvm,				$pages[0], $kasi);
		$pdf2->draw_text(400,  66, $yhtiorow["nimi"], 								$pages[0], $kasi);
		$pdf2->draw_text(400,  54, $kukarow["nimi"], 								$pages[0], $kasi);
		$pdf2->draw_text(400,  42, $yhtiorow["puhelin"], 							$pages[0], $kasi);

		//Kenttä B Merkinnät kirjanpitoa varten
		if ($yhtiorow["kotitullauslupa"] != "") {
			$pdf2->draw_text(330,  300, "ATK TULLAUS", 									$pages[0], $kymppi);
			$pdf2->draw_text(330,  288, $laskurow["tullausnumero"], 					$pages[0], $kymppi);
		}
	}
	else {
		echo "<font class='error'>".t("Tilausta ei löytynyt")."!<br>$query<br></font>";
	}

?>
