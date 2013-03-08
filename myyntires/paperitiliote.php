<?php

	function alku () {
		global $pdf, $asiakastiedot, $yhtiorow, $kukarow, $kala, $sivu, $rectparam, $norm, $pieni, $kieli, $tito_pvm;

		$firstpage = $pdf->new_page("a4");

		tulosta_logo_pdf($pdf, $firstpage, "");

		//Otsikko
		$pdf->draw_text(280, 815, t("TILIOTE", $kieli), $firstpage);
		$pdf->draw_text(430, 815, t("Sivu", $kieli)." ".$sivu, 	$firstpage, $norm);

		//Vasen sarake
		$pdf->draw_text(50, 729, t("Laskutusosoite", $kieli), 	$firstpage, $pieni);

		if ($asiakastiedot["laskutus_nimi"] != "") {
			$pdf->draw_text(50, 717, $asiakastiedot["laskutus_nimi"], 		$firstpage, $norm);
			$pdf->draw_text(50, 707, $asiakastiedot["laskutus_nimitark"], 	$firstpage, $norm);
			$pdf->draw_text(50, 697, $asiakastiedot["laskutus_osoite"], 	$firstpage, $norm);
			$pdf->draw_text(50, 687, $asiakastiedot["laskutus_postino"]." ".$asiakastiedot["laskutus_postitp"], $firstpage, $norm);
			$pdf->draw_text(50, 677, $asiakastiedot["laskutus_maa"], 		$firstpage, $norm);
		}
		else {
			$pdf->draw_text(50, 717, $asiakastiedot["nimi"], 		$firstpage, $norm);
			$pdf->draw_text(50, 707, $asiakastiedot["nimitark"], 	$firstpage, $norm);
			$pdf->draw_text(50, 697, $asiakastiedot["osoite"], 		$firstpage, $norm);
			$pdf->draw_text(50, 687, $asiakastiedot["postino"]." ".$asiakastiedot["postitp"], $firstpage, $norm);
			$pdf->draw_text(50, 677, $asiakastiedot["maa"], 		$firstpage, $norm);
		}

		//Oikea sarake
		$pdf->draw_rectangle(800, 300, 779, 580, 				$firstpage, $rectparam);
		$pdf->draw_rectangle(800, 420, 779, 580, 				$firstpage, $rectparam);
		$pdf->draw_text(310, 792, t("Tulostettu", $kieli), 		$firstpage, $pieni);
		$pdf->draw_text(310, 782, tv1dateconv(date('Y-m-d')), 	$firstpage, $norm);
		$pdf->draw_text(430, 792, t("Asiaa hoitaa", $kieli), 	$firstpage, $pieni);
		$pdf->draw_text(430, 782, $kukarow["nimi"], 			$firstpage, $norm);

		$pdf->draw_rectangle(779, 300, 758, 580, $firstpage, $rectparam);
		$pdf->draw_rectangle(779, 420, 758, 580, $firstpage, $rectparam);

		$pdf->draw_text(310, 771, t("Päivämäärä", $kieli), 		$firstpage, $pieni);
		$pdf->draw_text(310, 761, tv1dateconv($tito_pvm), 	$firstpage, $norm);

		$pdf->draw_text(430, 771, t("Puhelin", $kieli), $firstpage, $pieni);
		$pdf->draw_text(430, 761, $kukarow["puhno"], 		$firstpage, $norm);

		$pdf->draw_rectangle(758, 300, 737, 580, $firstpage, $rectparam);
		$pdf->draw_rectangle(758, 420, 737, 580, $firstpage, $rectparam);

		$pdf->draw_text(310, 750, t("Ytunnus/Asiakasnumero", $kieli), 	$firstpage, $pieni);
		$pdf->draw_text(310, 740, $asiakastiedot["ytunnus"], 			$firstpage, $norm);

		$pdf->draw_text(430, 750, t("Sähköposti", $kieli), 		$firstpage, $pieni);
		$pdf->draw_text(430, 740,  $kukarow["eposti"],				$firstpage, $norm);

		//Rivit alkaa täsä kohtaa
		$kala = 620;

		//eka rivi
		$pdf->draw_text(30,  $kala, t("Laskunro", $kieli),			$firstpage, $pieni);
		$pdf->draw_text(100, $kala, t("Pvm", $kieli),				$firstpage, $pieni);
		$pdf->draw_text(180, $kala, t("Eräpvm", $kieli),			$firstpage, $pieni);

		$pdf->draw_text(300, $kala, t("Valuutta", $kieli),			$firstpage, $pieni);

		$oikpos = $pdf->strlen(t("Summa", $kieli), $pieni);
		$pdf->draw_text(400-$oikpos, $kala, t("Summa", $kieli),		$firstpage, $pieni);

		$oikpos = $pdf->strlen(t("Avoinsumma", $kieli), $pieni);
		$pdf->draw_text(480-$oikpos, $kala, t("Avoinsumma", $kieli),$firstpage, $pieni);

		if ($tito_pvm != date('Y-m-d')) {
			$pdf->draw_text(510, $kala, t("Maksettu", $kieli),		$firstpage, $pieni);
		}


		$kala -= 15;

		return($firstpage);
	}

	function rivi ($tyyppi, $firstpage, $row) {
		global $pdf, $kala, $sivu, $lask, $rectparam, $norm, $pieni,$kieli, $yhtiorow, $tito_pvm;

		if ($lask == 39) {
			$sivu++;
			loppu($firstpage, array());
			$firstpage = alku();
			$kala = 605;
			$lask = 1;
		}

		if ($tyyppi==1) {

			$pdf->draw_text(30,  $kala, $row["laskunro"],				$firstpage, $norm);
			$pdf->draw_text(100, $kala, tv1dateconv($row["tapvm"]), 	$firstpage, $norm);
			$pdf->draw_text(180, $kala, tv1dateconv($row["erpcm"]), 	$firstpage, $norm);
			$pdf->draw_text(300, $kala, $row["valkoodi"], 				$firstpage, $norm);

			if (strtoupper($row['valkoodi']) == strtoupper($yhtiorow['valkoodi'])) {
				$oikpos = $pdf->strlen($row["tiliointisumma"], $norm);
				$pdf->draw_text(400-$oikpos, $kala, $row["tiliointisumma"],		$firstpage, $norm);

				$oikpos = $pdf->strlen($row["tiliointiavoinsaldo"], $norm);
				$pdf->draw_text(480-$oikpos, $kala, $row["tiliointiavoinsaldo"], $firstpage, $norm);
			}
			else {
				$oikpos = $pdf->strlen($row["tiliointisumma_valuutassa"], $norm);
				$pdf->draw_text(400-$oikpos, $kala, $row["tiliointisumma_valuutassa"],		$firstpage, $norm);

				$oikpos = $pdf->strlen($row["tiliointiavoinsaldo_valuutassa"], $norm);
				$pdf->draw_text(480-$oikpos, $kala, $row["tiliointiavoinsaldo_valuutassa"],	$firstpage, $norm);
			}

			if ($tito_pvm != date('Y-m-d')) {
				$pdf->draw_text(510, $kala, tv1dateconv($row["mapvm"]), 	$firstpage, $norm);
			}
		}
		else {
			$pdf->draw_text(30,  $kala, t("Kohdistamaton suoritus"),	$firstpage, $norm);
			$pdf->draw_text(180, $kala, tv1dateconv($row["tapvm"]), 	$firstpage, $norm);
			$pdf->draw_text(300, $kala, $row["valkoodi"], 				$firstpage, $norm);
			$oikpos = $pdf->strlen($row["laskusumma"], $norm);
			$pdf->draw_text(480-$oikpos, $kala, $row["laskusumma"], 			$firstpage, $norm);
		}
		$kala = $kala - 13;
		$lask++;
		return($firstpage);
	}

	function loppu ($firstpage, $summat) {

		global $pdf, $yhtiorow, $kukarow, $sivu, $rectparam, $norm, $pieni, $kieli, $lask, $kala;

		if (count($summat) > 1 and  $lask > 35) { //Valuuttaerittely ei mahdu! Rekursio! IIK!
			$sivu++;
			loppu($firstpage, array());
			$firstpage = alku();
			$kala = 605;
			$lask = 1;
		}

		//yhteensärivi
		$pdf->draw_rectangle(110, 394, 90, 580,	$firstpage, $rectparam);
		$pdf->draw_rectangle(110, 540, 90, 580,	$firstpage, $rectparam);

		if (count($summat) > 1) {
			$oikpos = $pdf->strlen("----------------", $norm);
			$pdf->draw_text(480-$oikpos, $kala, "----------------",	$firstpage, $norm);
			$kala = $kala - 13;
		}

		foreach ($summat as $valuutta => $summa) {
			if (count($summat) == 1) {
				$pdf->draw_text(404, 92,  t("YHTEENSÄ", $kieli).":",	$firstpage, $norm);
				$pdf->draw_text(464, 92,  sprintf('%.2f', $summa),	$firstpage, $norm);
				$pdf->draw_text(550, 92,  $valuutta,			$firstpage, $norm);
			}
			else {
				$pdf->draw_text(300, $kala, $valuutta,	 		$firstpage, $norm);
				$summa=sprintf('%.2f', $summa);
				$oikpos = $pdf->strlen($summa, $norm);
				$pdf->draw_text(480-$oikpos, $kala, $summa, 		$firstpage, $norm);
				$kala = $kala - 13;
			}
		}

		//Pankkiyhteystiedot
		$pdf->draw_rectangle(90, 20, 20, 580,	$firstpage, $rectparam);

		$pdf->draw_text(30, 82,  t("Pankkiyhteys", $kieli),	$firstpage, $pieni);
		$pdf->draw_text(30, 72,  $yhtiorow["pankkinimi1"]." ".$yhtiorow["pankkitili1"],	$firstpage, $norm);
		$pdf->draw_text(217, 72, $yhtiorow["pankkinimi2"]." ".$yhtiorow["pankkitili2"],	$firstpage, $norm);
		$pdf->draw_text(404, 72, $yhtiorow["pankkinimi3"]." ".$yhtiorow["pankkitili3"],	$firstpage, $norm);


		//Alimmat kolme laatikkoa, yhtiötietoja
		$pdf->draw_rectangle(70, 20, 20, 580,	$firstpage, $rectparam);
		$pdf->draw_rectangle(70, 207, 20, 580,	$firstpage, $rectparam);
		$pdf->draw_rectangle(70, 394, 20, 580,	$firstpage, $rectparam);

		$pdf->draw_text(30, 55, $yhtiorow["nimi"],		$firstpage, $pieni);
		$pdf->draw_text(30, 45, $yhtiorow["osoite"],	$firstpage, $pieni);
		$pdf->draw_text(30, 35, $yhtiorow["postino"]."  ".$yhtiorow["postitp"],	$firstpage, $pieni);
		$pdf->draw_text(30, 25, $yhtiorow["maa"],		$firstpage, $pieni);

		$pdf->draw_text(217, 55, t("Puhelin", $kieli).":",		$firstpage, $pieni);
		$pdf->draw_text(247, 55, $yhtiorow["puhelin"],			$firstpage, $pieni);
		$pdf->draw_text(217, 45, t("Fax", $kieli).":",			$firstpage, $pieni);
		$pdf->draw_text(247, 45, $yhtiorow["fax"],				$firstpage, $pieni);
		$pdf->draw_text(217, 35, t("Email", $kieli).":",		$firstpage, $pieni);
		$pdf->draw_text(247, 35, $yhtiorow["email"],			$firstpage, $pieni);

		$pdf->draw_text(404, 55, t("Y-tunnus", $kieli).":",		$firstpage, $pieni);
		$pdf->draw_text(444, 55, $yhtiorow["ytunnus"],			$firstpage, $pieni);
		$pdf->draw_text(404, 45, t("Kotipaikka", $kieli).":",	$firstpage, $pieni);
		$pdf->draw_text(444, 45, $yhtiorow["kotipaikka"],		$firstpage, $pieni);
		$pdf->draw_text(404, 35, t("Enn.per.rek", $kieli),		$firstpage, $pieni);
		$pdf->draw_text(404, 25, t("Alv.rek", $kieli),			$firstpage, $pieni);

	}

	require('pdflib/phppdflib.class.php');

	//echo "<font class='message'>Tiliote tulostuu...</font>";
	flush();

	//PDF parametrit
	$pdf = new pdffile;
	$pdf->set_default('margin-top', 	0);
	$pdf->set_default('margin-bottom', 	0);
	$pdf->set_default('margin-left', 	0);
	$pdf->set_default('margin-right', 	0);
	$rectparam["width"] = 0.3;

	$norm["height"] = 10;
	$norm["font"] = "Times-Roman";

	$pieni["height"] = 8;
	$pieni["font"] = "Times-Roman";

	// defaultteja
	$lask = 1;
	$sivu = 1;

	// aloitellaan tiliotteen teko
	if ($alatila == "T") {
		$tunnukset 	= $asiakasid;
	}
	else {
		$query = "	SELECT group_concat(tunnus) tunnukset
					FROM asiakas
					WHERE yhtio = '$kukarow[yhtio]'
					and ytunnus = '$ytunnus'";
		$result = pupe_query($query);
		$asiakasrow2 = mysql_fetch_assoc($result);

		$tunnukset 	= $asiakasrow2['tunnukset'];
	}

	if (!checkdate($kk, $pp, $vv)) {
		$tito_pvm = date("Y-m-d");
	}
	else {
		$tito_pvm = date("Y-m-d", mktime(0, 0, 0, $kk, $pp, $vv));
	}

	$query = "	SELECT
				lasku.ytunnus,
				lasku.maa,
				lasku.valkoodi,
				lasku.tunnus,
				lasku.erpcm,
				lasku.liitostunnus,
				lasku.mapvm,
				lasku.nimi,
				lasku.tapvm,
				lasku.laskunro,
				lasku.summa-lasku.saldo_maksettu laskuavoinsaldo,
				lasku.summa_valuutassa-lasku.saldo_maksettu_valuutassa laskuavoinsaldo_valuutassa,
				lasku.summa laskusumma,
				lasku.summa_valuutassa laskusumma_valuutassa,
				sum(tiliointi.summa) tiliointiavoinsaldo,
				sum(tiliointi.summa_valuutassa) tiliointiavoinsaldo_valuutassa,
				sum(if(tiliointi.tapvm = lasku.tapvm, tiliointi.summa, 0))-lasku.pyoristys tiliointisumma,
				sum(if(tiliointi.tapvm = lasku.tapvm, tiliointi.summa_valuutassa, 0))-lasku.pyoristys_valuutassa tiliointisumma_valuutassa
				FROM lasku use index (yhtio_tila_liitostunnus_tapvm)
				JOIN tiliointi use index (tositerivit_index) ON (
					lasku.yhtio 		    = tiliointi.yhtio
					and lasku.tunnus 	    = tiliointi.ltunnus
					and tiliointi.tilino   in ('$yhtiorow[myyntisaamiset]', '$yhtiorow[factoringsaamiset]', '$yhtiorow[konsernimyyntisaamiset]')
					and tiliointi.korjattu  = ''
					and tiliointi.tapvm    <= '$tito_pvm' )
				WHERE lasku.yhtio = '$kukarow[yhtio]'
				and (lasku.mapvm  > '$tito_pvm' or lasku.mapvm = '0000-00-00')
				and lasku.tapvm	  <= '$tito_pvm'
				and lasku.tapvm	  > '0000-00-00'
				and lasku.tila	  = 'U'
				and lasku.alatila = 'X'
				and lasku.liitostunnus in ($tunnukset)
				GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14
				ORDER BY lasku.ytunnus, lasku.laskunro";
	$result = pupe_query($query);
	$laskutiedot = mysql_fetch_assoc($result);

	//otetaan asiakastiedot ekalta laskulta
	$query = "	SELECT *
				FROM asiakas
				WHERE yhtio = '$kukarow[yhtio]'
				AND tunnus = '$laskutiedot[liitostunnus]'";
	$asiakasresult = pupe_query($query);
	$asiakastiedot = mysql_fetch_assoc($asiakasresult);

	//Otetaan tässä asiakkaan kieli talteen
	$kieli = $asiakastiedot["kieli"];

	//ja kelataan akuun
	if (mysql_num_rows($result) > 0) {
		mysql_data_seek($result, 0);
	}

	$query = "	SELECT maksupvm tapvm, summa * -1 summa, valkoodi, summa*-1 laskusumma
				FROM suoritus
				WHERE suoritus.yhtio = '$kukarow[yhtio]'
				and (suoritus.kohdpvm = '0000-00-00' or (suoritus.kohdpvm > '$tito_pvm' and suoritus.maksupvm < '$tito_pvm'))
				and suoritus.ltunnus  > 0
				and suoritus.kirjpvm <= '$tito_pvm'
				and suoritus.asiakas_tunnus in ($tunnukset)";
	$suoritusresult = pupe_query($query);

	$firstpage = alku();

	$totaali = array();

	while ($row = mysql_fetch_assoc($result)) {

		$firstpage = rivi(1, $firstpage, $row);

		if ($row['valkoodi'] == $yhtiorow['valkoodi']) {
			$totaali[$row['valkoodi']] += $row['tiliointiavoinsaldo'];
		}
		else {
			$totaali[$row['valkoodi']] += $row['tiliointiavoinsaldo_valuutassa'];
		}
	}

	while ($row = mysql_fetch_assoc($suoritusresult)) {
		$firstpage = rivi(2, $firstpage, $row);
		$totaali[$row['valkoodi']] += $row['summa'];
	}

	loppu($firstpage,$totaali);

	//keksitään uudelle failille joku varmasti uniikki nimi:
	list($usec, $sec) = explode(' ', microtime());
	mt_srand((float) $sec + ((float) $usec * 100000));
	$pdffilenimi = "/tmp/tiliote-".md5(uniqid(mt_rand(), true)).".pdf";

	//kirjoitetaan pdf faili levylle..
	$fh = fopen($pdffilenimi, "w");
	if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus epäonnistui $pdffilenimi");
	fclose($fh);

	echo file_get_contents($pdffilenimi);

?>