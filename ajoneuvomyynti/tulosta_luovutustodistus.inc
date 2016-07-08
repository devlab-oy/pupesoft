<?php

	//Fontit
	$seiska["height"] 			= 7;
	$seiska["font"] 			= "Times-Roman";

	$kymppi["height"] 			= 10;
	$kymppi["font"] 			= "Times-Roman";

	$kymppi_courier["height"] 	= 10;
	$kymppi_courier["font"] 	= "Courier";

	$kymppi_helvetica["height"] = 10;
	$kymppi_helvetica["font"] 	= "Helvetica";

	$kasto_helvetica["height"]  = 18;
	$kasto_helvetica["font"] 	= "Helvetica";

	$kasi_helvetica["height"] 	= 8;
	$kasi_helvetica["font"] 	= "Helvetica";

	$rectparam["width"]			= 0.2;


	//Haetaan pdflibrary
	require_once('pdflib/phppdflib.class.php');

	//muutamat funktiot...
	if (!function_exists('alku')) {
		function alku_luovari ($laskurow, $sivu, $kieli, $tyyppi, $pdf_luovari) {
			global $yhtiorow, $kukarow, $pupe_root_polku, $kasi_helvetica, $kymppi_helvetica, $seiska, $kasto_helvetica, $rectparam;

			// Haetaan pohja
			ob_start();

			$filename = "{$pupe_root_polku}/ajoneuvomyynti/luovutustodistus.pdf";

			$handle = fopen($filename, "r");
			$d = fread($handle, filesize($filename));
			fclose($handle);

			$pdf_luovari->import->append($d);

			$pdf_luovari_sivu = $pdf_luovari->import->get_pages();

			ob_end_clean();

			$apu_yhtiorow = array();

			// varmistetaan, että kopiossakin tulee oikean toimipaikan logo
			if ((int) $laskurow["yhtio_toimipaikka"] != 0) {
				$query = "	SELECT *
							FROM yhtion_toimipaikat
							WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$laskurow[yhtio_toimipaikka]' and lasku_logo != ''";
				$result = mysql_query($query) or die ("Kysely ei onnistu yhtio $query");

				if (mysql_num_rows($result) == 1) {
					$yhtion_toimipaikkarow = mysql_fetch_array($result);
					$apu_yhtiorow["lasku_logo"] = $yhtion_toimipaikkarow["lasku_logo"];
			 	}
			}
			else {
				$query = "	SELECT *
							FROM yhtion_parametrit
							WHERE yhtio = '$laskurow[yhtio]'";
				$result = mysql_query($query) or die ("Kysely ei onnistu yhtio $query");

				if (mysql_num_rows($result) == 1) {
					$yhtion_parametritrow = mysql_fetch_array($result);
					$apu_yhtiorow["lasku_logo"] = $yhtion_parametritrow["lasku_logo"];
				}
			}

			unset($data);

			if( (int) $apu_yhtiorow["lasku_logo"] > 0) {
				$liite = hae_liite($apu_yhtiorow["lasku_logo"], "Yllapito", "array");
				$data = $liite["data"];
				$isizelogo[0] = $liite["image_width"];
				$isizelogo[1] = $liite["image_height"];
				unset($liite);
			}
			elseif(file_exists($apu_yhtiorow["lasku_logo"])) {
				$filename = $apu_yhtiorow["lasku_logo"];

				$fh = fopen($filename, "r");
				$data = fread($fh, filesize($filename));
				fclose($fh);

				$isizelogo = getimagesize($apu_yhtiorow["lasku_logo"]);
			}

			if($data) {
				$image = $pdf_luovari->jfif_embed($data);

				if(!$image) {
					echo t("Logokuvavirhe");
				}
				else {

					$logoparam = array();

					$lasku_logo_koko = 50;
					$lasku_logo_positio = 830;

					if ((int) $yhtiorow["lasku_logo_koko"] > 0) {
						$lasku_logo_koko = (int) $yhtiorow["lasku_logo_koko"];
					}
					if ((int) $yhtiorow["lasku_logo_positio"] > 0) {
						$lasku_logo_positio = (int) $yhtiorow["lasku_logo_positio"];
					}

					if ($isizelogo[0] > $isizelogo[1] and $isizelogo[1] * (180 / $isizelogo[0]) <= $lasku_logo_koko) {
						$logoparam['scale'] = 180 / $isizelogo[0];
					}
					else {
						$logoparam['scale'] = $lasku_logo_koko  / $isizelogo[1];
					}

					$placement = $pdf_luovari->image_place($image, $lasku_logo_positio-($logoparam['scale']*$isizelogo[1]), 20, $pdf_luovari_sivu[0], $logoparam);
				}
			}
			else {
				$pdf_luovari->draw_text(30, 815,  $laskurow["yhtio_nimi"], $pdf_luovari_sivu[0]);
			}

			$pp = substr($laskurow["luontiaika"],8,2);
			$kk = substr($laskurow["luontiaika"],5,2);
			$vv = substr($laskurow["luontiaika"],0,4);

			$voimassa = date("d.m.Y",mktime(0, 0, 0, $kk, $pp+14, $vv));
			$luotoaik = date("d.m.Y",mktime(0, 0, 0, $kk, $pp, $vv));

			if ($tyyppi == "VAIHTOVENE") {
				$pdf_luovari->draw_text(mm_pt(10),  mm_pt(267), t("LUOVUTUSTODISTUS VAIHTOVENEESTÄ"),		$pdf_luovari_sivu[0], $kasto_helvetica);
			}
			else {
				$pdf_luovari->draw_text(mm_pt(10),  mm_pt(267), t("LUOVUTUSTODISTUS"),						$pdf_luovari_sivu[0], $kasto_helvetica);
			}

			$pdf_luovari->draw_text(mm_pt(10),  mm_pt(259), t("OSTAJA SAA OMISTUSOIKEUDEN"), 				$pdf_luovari_sivu[0]);
			$pdf_luovari->draw_text(mm_pt(10),  mm_pt(252), t("Ostajalle on luovutettu ja hän on vastaanottanut kaupan kohteen ja siinä luovutushetkellä olevat varusteet alla mainittuna päivämääränä."), $pdf_luovari_sivu[0], $kasi_helvetica);

			$pdf_luovari->draw_text(mm_pt(101),  mm_pt(280), t("Pvm").": $luotoaik",							$pdf_luovari_sivu[0], $kasi_helvetica);

			$pdf_luovari->draw_text(mm_pt(160), mm_pt(284), t("Numero"),									$pdf_luovari_sivu[0], $kasi_helvetica);
			$pdf_luovari->draw_rectangle(mm_pt(287), mm_pt(158),  mm_pt(278), mm_pt(197),					$pdf_luovari_sivu[0], $rectparam);
			$pdf_luovari->draw_text(mm_pt(170), mm_pt(280), $laskurow["tunnus"],							$pdf_luovari_sivu[0]);


			$pdf_luovari->draw_text(mm_pt(10),  mm_pt(237), t("KAUPAN KOHDE"),								$pdf_luovari_sivu[0], $kymppi_helvetica);

			if ($tyyppi == "VAIHTOVENE") {
				$pdf_luovari->draw_text(mm_pt(10),  mm_pt(205), t("OSTAJA"),								$pdf_luovari_sivu[0], $kymppi_helvetica);
			}
			else {
				$pdf_luovari->draw_text(mm_pt(10),  mm_pt(205), t("MYYJÄ"),									$pdf_luovari_sivu[0], $kymppi_helvetica);
			}

			$pdf_luovari->draw_text(mm_pt(11),  mm_pt(202), t("Nimi"),										$pdf_luovari_sivu[0], $seiska);
			$pdf_luovari->draw_text(mm_pt(101), mm_pt(202), t("Lähiosoite"),								$pdf_luovari_sivu[0], $seiska);
			$pdf_luovari->draw_text(mm_pt(11),  mm_pt(191.5), t("Postinumero"),								$pdf_luovari_sivu[0], $seiska);
			$pdf_luovari->draw_text(mm_pt(78),  mm_pt(191.5), t("Postitoimipaikka"),						$pdf_luovari_sivu[0], $seiska);
			$pdf_luovari->draw_text(mm_pt(152), mm_pt(191.5), t("Y-tunnus"),								$pdf_luovari_sivu[0], $seiska);
			$pdf_luovari->draw_text(mm_pt(12),  mm_pt(195), $yhtiorow["nimi"], 								$pdf_luovari_sivu[0], $kymppi_helvetica);
			$pdf_luovari->draw_text(mm_pt(102), mm_pt(195), $yhtiorow["osoite"], 							$pdf_luovari_sivu[0], $kymppi_helvetica);
			$pdf_luovari->draw_text(mm_pt(12),  mm_pt(185), $yhtiorow["postino"],							$pdf_luovari_sivu[0], $kymppi_helvetica);
			$pdf_luovari->draw_text(mm_pt(79),  mm_pt(185), $yhtiorow["postitp"],							$pdf_luovari_sivu[0], $kymppi_helvetica);
			$pdf_luovari->draw_text(mm_pt(153), mm_pt(185), $yhtiorow["ytunnus"],							$pdf_luovari_sivu[0], $kymppi_helvetica);

			if ($tyyppi == "VAIHTOVENE") {
				$pdf_luovari->draw_text(mm_pt(10),  mm_pt(174), t("MYYJÄ (Rinnakkaismyyjän tiedot merkitään vain jos yhteisvastuullinen)"),	$pdf_luovari_sivu[0], $kymppi_helvetica);
			}
			else {
				$pdf_luovari->draw_text(mm_pt(10),  mm_pt(174), t("OSTAJA (Rinnakkaisostajan tiedot merkitään vain jos yhteisvastuullinen)"),	$pdf_luovari_sivu[0], $kymppi_helvetica);
			}

			$pdf_luovari->draw_text(mm_pt(11),  mm_pt(169), t("Sukunimi ja puhutteluetunimi"),				$pdf_luovari_sivu[0], $seiska);
			$pdf_luovari->draw_text(mm_pt(78),  mm_pt(169), t("Muut etunimet"),								$pdf_luovari_sivu[0], $seiska);
			$pdf_luovari->draw_text(mm_pt(152), mm_pt(169), t("Henkilö- tai Y-tunnus"),						$pdf_luovari_sivu[0], $seiska);
			$pdf_luovari->draw_text(mm_pt(11),  mm_pt(158), t("Lähiosoite"),								$pdf_luovari_sivu[0], $seiska);
			$pdf_luovari->draw_text(mm_pt(78),  mm_pt(158), t("Postinumero"),								$pdf_luovari_sivu[0], $seiska);
			$pdf_luovari->draw_text(mm_pt(152), mm_pt(158), t("Postitoimipaikka"),							$pdf_luovari_sivu[0], $seiska);
			$pdf_luovari->draw_text(mm_pt(12),  mm_pt(163), $laskurow["nimi"], 								$pdf_luovari_sivu[0], $kymppi_helvetica);
			$pdf_luovari->draw_text(mm_pt(79),  mm_pt(163), $laskurow["nimitark"], 							$pdf_luovari_sivu[0], $kymppi_helvetica);
			$pdf_luovari->draw_text(mm_pt(153), mm_pt(163), $laskurow["ovttunnus"],							$pdf_luovari_sivu[0], $kymppi_helvetica);
			$pdf_luovari->draw_text(mm_pt(12),  mm_pt(152), $laskurow["osoite"], 							$pdf_luovari_sivu[0], $kymppi_helvetica);
			$pdf_luovari->draw_text(mm_pt(79),  mm_pt(152), $laskurow["postino"],							$pdf_luovari_sivu[0], $kymppi_helvetica);
			$pdf_luovari->draw_text(mm_pt(153), mm_pt(152), $laskurow["postitp"],							$pdf_luovari_sivu[0], $kymppi_helvetica);

			if ($tyyppi == "VAIHTOVENE") {
				$pdf_luovari->draw_text(mm_pt(11),  mm_pt(146), t("Rinnakkaismyyjän sukunimi ja puhutteluetunimi"), $pdf_luovari_sivu[0], $seiska);
			}
			else {
				$pdf_luovari->draw_text(mm_pt(11),  mm_pt(146), t("Rinnakkaisostajan sukunimi ja puhutteluetunimi"), $pdf_luovari_sivu[0], $seiska);
			}

			$pdf_luovari->draw_text(mm_pt(78),  mm_pt(146), t("Muut etunimet"),									$pdf_luovari_sivu[0], $seiska);
			$pdf_luovari->draw_text(mm_pt(152), mm_pt(146), t("Henkilö- tai Y-tunnus"),							$pdf_luovari_sivu[0], $seiska);
			$pdf_luovari->draw_text(mm_pt(12),  mm_pt(140), $laskurow["kolm_nimi"], 							$pdf_luovari_sivu[0], $kymppi_helvetica);
			$pdf_luovari->draw_text(mm_pt(79),  mm_pt(140), $laskurow["kolm_nimitark"], 						$pdf_luovari_sivu[0], $kymppi_helvetica);
			$pdf_luovari->draw_text(mm_pt(153), mm_pt(140), $laskurow["kolm_ovttunnus"], 						$pdf_luovari_sivu[0], $kymppi_helvetica);


			$pdf_luovari->draw_text(mm_pt(10),  mm_pt(129), t("OMISTAJA (jos ostaja saa hallintaoikeuden)"),	$pdf_luovari_sivu[0], $kymppi_helvetica);
			$pdf_luovari->draw_text(mm_pt(11),  mm_pt(125.5), t("Omistajan nimi"),								$pdf_luovari_sivu[0], $seiska);
			$pdf_luovari->draw_text(mm_pt(78),  mm_pt(125.5), t("Osoite"),										$pdf_luovari_sivu[0], $seiska);
			$pdf_luovari->draw_text(mm_pt(152), mm_pt(125.5), t("Postinumero ja -toimipaikka"),					$pdf_luovari_sivu[0], $seiska);

			if ($tyyppi == "VAIHTOVENE") {
				$pdf_luovari->draw_text(mm_pt(12),  mm_pt(119), $yhtiorow["nimi"], 								$pdf_luovari_sivu[0], $kymppi_helvetica);
				$pdf_luovari->draw_text(mm_pt(79),  mm_pt(119), $yhtiorow["osoite"], 							$pdf_luovari_sivu[0], $kymppi_helvetica);
				$pdf_luovari->draw_text(mm_pt(153), mm_pt(119), $yhtiorow["postino"]." ".$yhtiorow["postitp"],	$pdf_luovari_sivu[0], $kymppi_helvetica);
			}
			elseif ($laskurow["rekisteilmo_omistaja"] == "NORDEA") {
				$pdf_luovari->draw_text(mm_pt(12),  mm_pt(119), "Nordea Rahoitus Suomi Oy", 					$pdf_luovari_sivu[0], $kymppi_helvetica);
				$pdf_luovari->draw_text(mm_pt(79),  mm_pt(119), "Nihtisillantie 3 G", 							$pdf_luovari_sivu[0], $kymppi_helvetica);
				$pdf_luovari->draw_text(mm_pt(153), mm_pt(119), "ESPOO 00020",									$pdf_luovari_sivu[0], $kymppi_helvetica);
			}
			elseif($laskurow["rekisteilmo_omistaja"] == "GE") {
				$pdf_luovari->draw_text(mm_pt(12),  mm_pt(119), "GE Money Oy", 									$pdf_luovari_sivu[0], $kymppi_helvetica);
				$pdf_luovari->draw_text(mm_pt(79),  mm_pt(119), "Kuortaneenkatu 2", 							$pdf_luovari_sivu[0], $kymppi_helvetica);
				$pdf_luovari->draw_text(mm_pt(153), mm_pt(119), "HELSINKI 00510",								$pdf_luovari_sivu[0], $kymppi_helvetica);
			}
			elseif($laskurow["rekisteilmo_omistaja"] == "OKO") {
				// $pdf_luovari->draw_text(mm_pt(12),  mm_pt(119), "OKO Pankki Oyj", 							$pdf_luovari_sivu[0], $kymppi_helvetica);
				// 				$pdf_luovari->draw_text(mm_pt(79),  mm_pt(119), "Aleksanterinkatu 20", 			$pdf_luovari_sivu[0], $kymppi_helvetica);
				// 				$pdf_luovari->draw_text(mm_pt(153), mm_pt(119), "LAHTI 15140",					$pdf_luovari_sivu[0], $kymppi_helvetica);
				$pdf_luovari->draw_text(mm_pt(12),  mm_pt(119), "Pohjola Pankki Oyj", 							$pdf_luovari_sivu[0], $kymppi_helvetica);
				$pdf_luovari->draw_text(mm_pt(79),  mm_pt(119), "PL 308", 										$pdf_luovari_sivu[0], $kymppi_helvetica);
				$pdf_luovari->draw_text(mm_pt(153), mm_pt(119), "POHJOLA 00013",								$pdf_luovari_sivu[0], $kymppi_helvetica);
			}
			elseif($laskurow["rekisteilmo_omistaja"] == "POHJOLA") {
				$pdf_luovari->draw_text(mm_pt(12),  mm_pt(119), "Pohjola Pankki Oyj", 							$pdf_luovari_sivu[0], $kymppi_helvetica);
				$pdf_luovari->draw_text(mm_pt(79),  mm_pt(119), "PL 308", 										$pdf_luovari_sivu[0], $kymppi_helvetica);
				$pdf_luovari->draw_text(mm_pt(153), mm_pt(119), "POHJOLA 00013",								$pdf_luovari_sivu[0], $kymppi_helvetica);
			}
			elseif($laskurow["rekisteilmo_omistaja"] == "SAMPO") {
				$pdf_luovari->draw_text(mm_pt(12),  mm_pt(119), "Sampo Rahoitus",			 					$pdf_luovari_sivu[0], $kymppi_helvetica);
				$pdf_luovari->draw_text(mm_pt(79),  mm_pt(119), "PL 1274", 										$pdf_luovari_sivu[0], $kymppi_helvetica);
				$pdf_luovari->draw_text(mm_pt(153), mm_pt(119), "SAMPO 00075",									$pdf_luovari_sivu[0], $kymppi_helvetica);
			}
			elseif($laskurow["rekisteilmo_omistaja"] == "SANTAN") {
				$pdf_luovari->draw_text(mm_pt(12),  mm_pt(119), "Santander",			 						$pdf_luovari_sivu[0], $kymppi_helvetica);
				$pdf_luovari->draw_text(mm_pt(79),  mm_pt(119), "Hermannin Rantatie 10", 						$pdf_luovari_sivu[0], $kymppi_helvetica);
				$pdf_luovari->draw_text(mm_pt(153), mm_pt(119), "HELSINKI 00580",								$pdf_luovari_sivu[0], $kymppi_helvetica);
			}
			elseif($laskurow["rekisteilmo_omistaja"] == "HANDEL") {
				$pdf_luovari->draw_text(mm_pt(12),  mm_pt(119), "Handelsbanken Rahoitus Oyj ",					$pdf_luovari_sivu[0], $kymppi_helvetica);
				$pdf_luovari->draw_text(mm_pt(79),  mm_pt(119), "Aleksanterinkatu 11", 						$pdf_luovari_sivu[0], $kymppi_helvetica);
				$pdf_luovari->draw_text(mm_pt(153), mm_pt(119), "HELSINKI 00100",								$pdf_luovari_sivu[0], $kymppi_helvetica);
			}

			$pdf_luovari->draw_text(mm_pt(10),  mm_pt(108), t("ALLEKIRJOITUKSET"),							$pdf_luovari_sivu[0], $kymppi_helvetica);
			$pdf_luovari->draw_text(mm_pt(11),  mm_pt(104), t("Paikka ja aika"),							$pdf_luovari_sivu[0], $seiska);
			$pdf_luovari->draw_text(mm_pt(12),  mm_pt(99), $yhtiorow["postitp"]." ".date("d.m.Y"),			$pdf_luovari_sivu[0], $kymppi_helvetica);

			if ($tyyppi == "VAIHTOVENE") {
				$pdf_luovari->draw_text(mm_pt(141), mm_pt(104), t("Ostajan nimi ja allekirjoitus"),			$pdf_luovari_sivu[0], $seiska);
				$pdf_luovari->draw_text(mm_pt(11),  mm_pt(93), t("Myyjän allekirjoitus"),					$pdf_luovari_sivu[0], $seiska);
				$pdf_luovari->draw_text(mm_pt(73.5),mm_pt(93), t("Rinnakkaismyyjän allekirjoitus"),			$pdf_luovari_sivu[0], $seiska);
			}
			else {
				$pdf_luovari->draw_text(mm_pt(141), mm_pt(104), t("Myyjän nimi ja allekirjoitus"),			$pdf_luovari_sivu[0], $seiska);
				$pdf_luovari->draw_text(mm_pt(11),  mm_pt(93), t("Ostajan allekirjoitus"),					$pdf_luovari_sivu[0], $seiska);
				$pdf_luovari->draw_text(mm_pt(73.5),mm_pt(93), t("Rinnakkaisostajan allekirjoitus"),		$pdf_luovari_sivu[0], $seiska);
			}

			$query  = "	select *
						from kuka
						where tunnus = '$laskurow[myyja]' and yhtio='$kukarow[yhtio]'";
			$myyresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($myyresult) == 1) {
				$myyrow = mysql_fetch_array($myyresult);
			}
			else {
				$myyrow = $kukarow;
			}

			$pdf_luovari->draw_text(mm_pt(143), mm_pt(72), $myyrow["nimi"]."   ".$yhtiorow["nimi"],			$pdf_luovari_sivu[0], $seiska);
			$pdf_luovari->draw_text(mm_pt(13),  mm_pt(72), $laskurow["nimi"],								$pdf_luovari_sivu[0], $seiska);
			$pdf_luovari->draw_text(mm_pt(75),  mm_pt(72), $laskurow["kolm_nimi"],							$pdf_luovari_sivu[0], $seiska);

			if ($tyyppi != "VAIHTOVENE") {
				$pdf_luovari->draw_text(mm_pt(10),  mm_pt(66), t("Uuden tuotteen kaupassa ostaja kuittaa allekirjoituksellaan myös vastaanottaneeksi omistajan käsikirjan."),	$pdf_luovari_sivu[0], $seiska);
			}

			//Palautetan luotu sivu
			return $pdf_luovari_sivu[0];
		}
	}

	if (!function_exists('rivi_luovari')) {
		function rivi_luovari($pdf_luovari, $laskurow, $kala, $kieli, $page_luovari, $sarjanutunnus, $selite, $row) {
			global $yhtiorow, $kukarow, $kasi_helvetica, $kymppi_helvetica, $seiska, $kymppi_courier;

			if($sarjanutunnus != "") {
				$query = "	select *
							from sarjanumeroseuranta
							where yhtio='$kukarow[yhtio]'
							and tuoteno='$row[tuoteno]'
							and $sarjanutunnus='$row[tunnus]'";
				$sarjares = mysql_query($query) or pupe_error($query);
				$sarjarow = mysql_fetch_array($sarjares);

				if (strpos($sarjarow["sarjanumero"], t("PUUTTUU")) === FALSE and $sarjarow["sarjanumero"] != "") {
					$sarjalisa = " (".$sarjarow["sarjanumero"].")";
				}
			}
			else {
				$sarjalisa = "";
			}

			$query = "	SELECT sarjanumeron_lisatiedot.*,
						a1.selitetark_2 Ankkurivinssi,
						a2.selitetark_2 Jaakaappi,
						a3.selitetark_2 Koneistus,
						a4.selitetark_2 Kuvatyyppi,
						a5.selitetark_2 Laatuluokitus,
						a6.selitetark_2 Materiaali,
						a7.selitetark_2 Merkki,
						a8.selitetark_2 Koneen_merkki,
						a9.selitetark_2 Moottorin_ohjausjarjestelma,
						a10.selitetark_2 Pilssipumppu,
						a11.selitetark_2 Sijainti,
						a12.selitetark_2 Tiikkisarja,
						a13.selitetark_2 Toimituskulut,
						a14.selitetark_2 Tyynysarja,
						a15.selitetark_2 Tyyppi,
						a16.selitetark_2 Uuni,
						a17.selitetark_2 Valonheitin,
						a18.selitetark_2 Varirunko,
						a19.selitetark_2 WC_ja_suihku
						FROM sarjanumeron_lisatiedot use index (yhtio_liitostunnus)
						LEFT JOIN avainsana a1 use index (yhtio_laji_selitetark) ON a1.yhtio=sarjanumeron_lisatiedot.yhtio and a1.laji='sarjanumeron_li' and a1.selitetark=sarjanumeron_lisatiedot.Ankkurivinssi and a1.selite='ANKKURIVINSSI'
						LEFT JOIN avainsana a2 use index (yhtio_laji_selitetark) ON a2.yhtio=sarjanumeron_lisatiedot.yhtio and a2.laji='sarjanumeron_li' and a2.selitetark=sarjanumeron_lisatiedot.Jaakaappi and a2.selite='JAAKAAPPI'
						LEFT JOIN avainsana a3 use index (yhtio_laji_selitetark) ON a3.yhtio=sarjanumeron_lisatiedot.yhtio and a3.laji='sarjanumeron_li' and a3.selitetark=sarjanumeron_lisatiedot.Koneistus and a3.selite='KONEISTUS'
						LEFT JOIN avainsana a4 use index (yhtio_laji_selitetark) ON a4.yhtio=sarjanumeron_lisatiedot.yhtio and a4.laji='sarjanumeron_li' and a4.selitetark=sarjanumeron_lisatiedot.Kuvatyyppi and a4.selite='KUVATYYPPI'
						LEFT JOIN avainsana a5 use index (yhtio_laji_selitetark) ON a5.yhtio=sarjanumeron_lisatiedot.yhtio and a5.laji='sarjanumeron_li' and a5.selitetark=sarjanumeron_lisatiedot.Laatuluokitus and a5.selite='LAATU'
						LEFT JOIN avainsana a6 use index (yhtio_laji_selitetark) ON a6.yhtio=sarjanumeron_lisatiedot.yhtio and a6.laji='sarjanumeron_li' and a6.selitetark=sarjanumeron_lisatiedot.Materiaali and a6.selite='MATERIAALI'
						LEFT JOIN avainsana a7 use index (yhtio_laji_selitetark) ON a7.yhtio=sarjanumeron_lisatiedot.yhtio and a7.laji='sarjanumeron_li' and a7.selitetark=sarjanumeron_lisatiedot.Merkki and a7.selite='MERKKI'
						LEFT JOIN avainsana a8 use index (yhtio_laji_selitetark) ON a8.yhtio=sarjanumeron_lisatiedot.yhtio and a8.laji='sarjanumeron_li' and a8.selitetark=sarjanumeron_lisatiedot.Koneen_merkki and a8.selite='MOOTTORINMERKKI'
						LEFT JOIN avainsana a9 use index (yhtio_laji_selitetark) ON a9.yhtio=sarjanumeron_lisatiedot.yhtio and a9.laji='sarjanumeron_li' and a9.selitetark=sarjanumeron_lisatiedot.Moottorin_ohjausjarjestelma and a9.selite='MOOTTORINOHJAUS'
						LEFT JOIN avainsana a10 use index (yhtio_laji_selitetark) ON a10.yhtio=sarjanumeron_lisatiedot.yhtio and a10.laji='sarjanumeron_li' and a10.selitetark=sarjanumeron_lisatiedot.Pilssipumppu and a10.selite='PILSSIPUMPPU'
						LEFT JOIN avainsana a11 use index (yhtio_laji_selitetark) ON a11.yhtio=sarjanumeron_lisatiedot.yhtio and a11.laji='sarjanumeron_li' and a11.selitetark=sarjanumeron_lisatiedot.Sijainti and a11.selite='SIJAINTI'
						LEFT JOIN avainsana a12 use index (yhtio_laji_selitetark) ON a12.yhtio=sarjanumeron_lisatiedot.yhtio and a12.laji='sarjanumeron_li' and a12.selitetark=sarjanumeron_lisatiedot.Tiikkisarja and a12.selite='TIIKKISARJA'
						LEFT JOIN avainsana a13 use index (yhtio_laji_selitetark) ON a13.yhtio=sarjanumeron_lisatiedot.yhtio and a13.laji='sarjanumeron_li' and a13.selitetark=sarjanumeron_lisatiedot.Toimituskulut and a13.selite='TOIMITUSKULUT'
						LEFT JOIN avainsana a14 use index (yhtio_laji_selitetark) ON a14.yhtio=sarjanumeron_lisatiedot.yhtio and a14.laji='sarjanumeron_li' and a14.selitetark=sarjanumeron_lisatiedot.Tyynysarja and a14.selite='TYYNYSARJA'
						LEFT JOIN avainsana a15 use index (yhtio_laji_selitetark) ON a15.yhtio=sarjanumeron_lisatiedot.yhtio and a15.laji='sarjanumeron_li' and a15.selitetark=sarjanumeron_lisatiedot.Tyyppi and a15.selite='TYYPPI'
						LEFT JOIN avainsana a16 use index (yhtio_laji_selitetark) ON a16.yhtio=sarjanumeron_lisatiedot.yhtio and a16.laji='sarjanumeron_li' and a16.selitetark=sarjanumeron_lisatiedot.Uuni and a16.selite='UUNI'
						LEFT JOIN avainsana a17 use index (yhtio_laji_selitetark) ON a17.yhtio=sarjanumeron_lisatiedot.yhtio and a17.laji='sarjanumeron_li' and a17.selitetark=sarjanumeron_lisatiedot.Valonheitin and a17.selite='VALONHEITIN'
						LEFT JOIN avainsana a18 use index (yhtio_laji_selitetark) ON a18.yhtio=sarjanumeron_lisatiedot.yhtio and a18.laji='sarjanumeron_li' and a18.selitetark=sarjanumeron_lisatiedot.Varirunko and a18.selite='VARIRUNKO'
						LEFT JOIN avainsana a19 use index (yhtio_laji_selitetark) ON a19.yhtio=sarjanumeron_lisatiedot.yhtio and a19.laji='sarjanumeron_li' and a19.selitetark=sarjanumeron_lisatiedot.WC_ja_suihku and a19.selite='VESSA'
						WHERE sarjanumeron_lisatiedot.yhtio		 = '$kukarow[yhtio]'
						and sarjanumeron_lisatiedot.liitostunnus = '$sarjarow[tunnus]'";
			$lisatietores = mysql_query($query) or pupe_error($query);
			$lisarow = mysql_fetch_array($lisatietores);

			$pdf_luovari->draw_text(mm_pt(11),  mm_pt($kala+6), $selite, 								$page_luovari, $seiska);
			$pdf_luovari->draw_text(mm_pt(165), mm_pt($kala+6), t("Vuosimalli"), 						$page_luovari, $seiska);
			$pdf_luovari->draw_text(mm_pt(11),  mm_pt($kala), $row["nimitys"]."  ".$sarjalisa, 			$page_luovari, $kymppi_helvetica);

			if ($row["positio"] == "Vene") {
				$pdf_luovari->draw_text(mm_pt(165),  mm_pt($kala), $lisarow["Vuosimalli"], 				$page_luovari, $kymppi_helvetica);
			}
			else {
				$pdf_luovari->draw_text(mm_pt(165),  mm_pt($kala), $lisarow["Koneen_vuosimalli"], 		$page_luovari, $kymppi_helvetica);
			}

			return $page_luovari;
		}
	}

	if (!function_exists('print_pdf_luovari')) {
		function print_pdf_luovari ($pdf_luovari, $laskurow, $komento, $tee = "") {
			global $yhtiorow, $kukarow;

			$oslapp='';
			$returnvalue=0;

			//keksitään uudelle failille joku varmasti uniikki nimi:
			list($usec, $sec) = explode(' ', microtime());
			mt_srand((float) $sec + ((float) $usec * 100000));
			$pdf_luovarifilenimi = "/tmp/Tarjous-".md5(uniqid(mt_rand(), true)).".pdf";

			//kirjoitetaan pdf faili levylle..
			$fh = fopen($pdf_luovarifilenimi, "w");
			if (fwrite($fh, $pdf_luovari->generate()) === FALSE) die("PDF create error $pdf_luovarifilenimi");
			fclose($fh);

			// itse print komento...
			if ($komento == 'RUUDULLE') {
				return $pdf_luovarifilenimi;
			}
			elseif ($komento == 'email') {
				$liite = $pdf_luovarifilenimi;

				$kutsu = t("Luovutustodistus", $kieli);

				if ($yhtiorow["liitetiedostojen_nimeaminen"] == "N") {
					$kutsu .= ", ".trim($laskurow["nimi"]);
				}

				echo t("Luovutustodistus tulostuu")."...<br>";

				if ($kukarow["extranet"] == '') {
					require("../inc/sahkoposti.inc");
				}
				else {
					require("sahkoposti.inc");
				}
			}
			elseif ($tee == 'NAYTATILAUS') {
				//Työnnetään tuo pdf vaan putkeen!
				echo file_get_contents($pdf_luovarifilenimi);
			}
			elseif ($komento != '' and $komento != 'edi') {
				echo t("Luovutustodistus tulostuu")."...<br>";
				$line = exec("$komento $pdf_luovarifilenimi", $output, $returnvalue);
			}

			//poistetaan tmp file samantien kuleksimasta...
			system("rm -f $pdf_luovarifilenimi");
		}
	}

	if (!function_exists('tulosta_luovutustodistus')) {
		function tulosta_luovutustodistus ($otunnus, $komento, $kieli = "", $tee = "") {
			global $yhtiorow, $kukarow;

			// Haetaan laskun tiedot
			$query = "	SELECT *, lasku.laatija laatija, lasku.luontiaika luontiaika, lasku.tunnus tunnus
						FROM lasku
						LEFT JOIN laskun_lisatiedot ON lasku.yhtio=laskun_lisatiedot.yhtio and lasku.tunnus=laskun_lisatiedot.otunnus
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and lasku.tunnus  = '$otunnus'";
			$result = mysql_query($query) or pupe_error($query);
			$laskurow = mysql_fetch_array($result);

			if ($kieli== '') {
				$querykiel = "SELECT kieli FROM asiakas WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$laskurow[liitostunnus]'";
				$kielresult = mysql_query($querykiel) or pupe_error($querykiel);
				$kielnum = mysql_num_rows($kielresult);
				$kielrow = mysql_fetch_array($kielresult);
				$kieli = strtolower($kielrow['kieli']);
			}

			// haetaan maksuehdon tiedot
			$query  = "SELECT * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
			$result = mysql_query($query) or pupe_error($query);
			$masrow = mysql_fetch_array($result);

			//maksuehto tekstinä
			$laskurow["maksuehto"] = t_tunnus_avainsanat($masrow, "teksti", "MAKSUEHTOKV", $kieli);

			$query  = "	SELECT *
						FROM asiakas
						WHERE yhtio='$kukarow[yhtio]' and tunnus='$laskurow[liitostunnus]'";
			$result = mysql_query($query) or pupe_error($query);
			$asiakasrow = mysql_fetch_array($result);

			//korjataan yhtiön tietoja
			$query  = "	SELECT *
						from kuka
						where tunnus = '$laskurow[myyja]'
						and yhtio = '$kukarow[yhtio]'";
			$myyresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($myyresult) == 1) {
				$myyrow = mysql_fetch_array($myyresult);

				$kukarow["toimipaikka"] = $myyrow["toimipaikka"];
			}

			$query = "	SELECT *
						FROM yhtion_toimipaikat
						WHERE yhtio='$kukarow[yhtio]' and tunnus='$kukarow[toimipaikka]'";
			$result = mysql_query($query) or die ("Kysely ei onnistu yhtio $query");

			if (mysql_num_rows($result) == 1) {
				$yhtion_toimipaikkarow = mysql_fetch_array($result);

				$yhtiorow["lasku_logo"] = $yhtion_toimipaikkarow["lasku_logo"];
				$yhtiorow["nimi"] 		= $yhtion_toimipaikkarow["nimi"];
				$yhtiorow["osoite"] 	= $yhtion_toimipaikkarow["osoite"];
				$yhtiorow["postino"] 	= $yhtion_toimipaikkarow["postino"];
				$yhtiorow["postitp"] 	= $yhtion_toimipaikkarow["postitp"];
				$yhtiorow["puhelin"] 	= $yhtion_toimipaikkarow["puhelin"];

			}

			function printtaa_rivit_luovari($pdf_luovari, $laskurow, $kala, $lask, $kieli, $sivu, $page_luovari, $sarjanutunnus, $osasto, $selite, $tyyppi) {
				global $yhtiorow, $kukarow;

				$page_luovari2[$sivu] = $page_luovari;

				if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"])) and $laskurow["vienti_kurssi"] != 0) {
					$hinta_riv = "round(tilausrivi.hinta/$laskurow[vienti_kurssi], 2)";
				}
				else {
					$hinta_riv = "tilausrivi.hinta";
				}

				$query_ale_lisa = generoi_alekentta('M');

				$query = "	SELECT tilausrivi.*,
				 			round($hinta_riv * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * {$query_ale_lisa},2) rivihinta,
							tuote.tuoteno, tilausrivin_lisatiedot.positio
							FROM tilausrivi
							JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
							LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
							WHERE tilausrivi.otunnus = '$laskurow[tunnus]'
							and tilausrivi.yhtio = '$kukarow[yhtio]'
							and tilausrivi.tyyppi in  ('L','T','U')
							$osasto";
				$riresult = mysql_query($query) or pupe_error($query);

				$riveja = mysql_num_rows($riresult);

				$rivilask = 1;

				while ($row = mysql_fetch_array($riresult)) {

					//jos on paljon rivejä tehdään uusi otsikko...
					if ($lask >= 2) {
						$sivu++;
						$lask = 0;

						$page_luovari2[$sivu] = alku_luovari ($laskurow, $sivu, $kieli, $tyyppi, $pdf_luovari);
					}
					elseif ($rivilask > 1) {
						$sivu++;
						$lask = 0;

						$page_luovari2[$sivu] = alku_luovari ($laskurow, $sivu, $kieli, $tyyppi, $pdf_luovari);
					}

					if ($riveja > 1) {
						$rivilask++;
					}

					$page_luovari2[$sivu] = rivi_luovari($pdf_luovari, $laskurow, $kala, $kieli, $page_luovari2[$sivu], $sarjanutunnus, $selite, $row);
					$lask++;

				}
				return array ($page_luovari2[$sivu], $lask);
			}

			//Oletuksia
			$lask = 0;
			$sivu = 1;

			$pdf_luovari = new pdffile;

			$pdf_luovari->set_default('margin-top', 20);
			$pdf_luovari->set_default('margin-bottom', 0);
			$pdf_luovari->set_default('margin-left', 0);
			$pdf_luovari->set_default('margin-right', 0);

			$pdf_luovari->enable('import');

			// Pohja
			$page_luovari[$sivu] = alku_luovari ($laskurow, $sivu, $kieli, "", $pdf_luovari);

			// Uusi vene
			$kala = 227;
			list ($page_luovari[$sivu], $lask) = printtaa_rivit_luovari($pdf_luovari, $laskurow, $kala, $lask, $kieli, $sivu, $page_luovari[$sivu], "myyntirivitunnus", "HAVING tilausrivin_lisatiedot.positio='Vene'", "Veneen merkki, malli ja numero", "");

			if ($lask < 1) {
				$lask = 1;
			}

			// Uusi moottori
			$kala = 217;
			list ($page_luovari[$sivu], $lask) = printtaa_rivit_luovari($pdf_luovari, $laskurow, $kala, $lask, $kieli, $sivu, $page_luovari[$sivu], "myyntirivitunnus", "HAVING tilausrivin_lisatiedot.positio='Moottori'", "Moottorin merkki, malli ja numero", "");

			if ($lask < 2) {
				$lask = 2;
			}

			// Vaihtovene
			$kala = 227;
			list ($page_luovari[$sivu], $lask) = printtaa_rivit_luovari($pdf_luovari, $laskurow, $kala, $lask, $kieli, $sivu, $page_luovari[$sivu], "ostorivitunnus", "HAVING tilausrivin_lisatiedot.positio='Vaihtovene'", "Vaihtoveneen merkki, malli (ja numero, jos tiedossa tarjoushetkellä)", "VAIHTOVENE");


			// Tulostetaan sivu
			print_pdf_luovari ($pdf_luovari, $laskurow, $komento, $tee);

			$tee = '';
		}
	}
?>
