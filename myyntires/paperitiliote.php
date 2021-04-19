<?php

	function alku () {
		global $pdf, $asiakastiedot, $yhtiorow, $kukarow, $kala, $sivu, $rectparam, $norm, $pieni, $kaatosumma, $kieli;

		$firstpage = $pdf->new_page("a4");

		tulosta_logo_pdf($pdf, $firstpage, "");

		//Otsikko
		//$pdf->draw_rectangle(830, 20,  800, 580, $firstpage, $rectparam);
		$pdf->draw_text(280, 815, t("TILIOTE", $kieli), $firstpage);
		$pdf->draw_text(430, 815, t("Sivu", $kieli)." ".$sivu, 	$firstpage, $norm);

		//Vasen sarake
		//$pdf->draw_rectangle(737, 20,  674, 300, $firstpage, $rectparam);
		$pdf->draw_text(50, 729, t("Laskutusosoite", $kieli), 	$firstpage, $pieni);
		$pdf->draw_text(50, 717, $asiakastiedot["nimi"], 		$firstpage, $norm);
		$pdf->draw_text(50, 707, $asiakastiedot["nimitark"], 	$firstpage, $norm);
		$pdf->draw_text(50, 697, $asiakastiedot["osoite"], 		$firstpage, $norm);
		$pdf->draw_text(50, 687, $asiakastiedot["postino"]." ".$asiakastiedot["postitp"], $firstpage, $norm);
		$pdf->draw_text(50, 677, $asiakastiedot["maa"], 		$firstpage, $norm);

		$pdf->draw_rectangle(737, 300, 716, 580, $firstpage, $rectparam);
		$pdf->draw_text(310, 729, t("Ytunnus/Asiakasnumero", $kieli), 	$firstpage, $pieni);
		$pdf->draw_text(310, 717, $asiakastiedot["ytunnus"], 			$firstpage, $norm);


		//Oikea sarake
		$pdf->draw_rectangle(800, 300, 779, 580, 				$firstpage, $rectparam);
		$pdf->draw_rectangle(800, 420, 779, 580, 				$firstpage, $rectparam);
		$pdf->draw_text(310, 792, t("P�iv�m��r�", $kieli), 		$firstpage, $pieni);
		$pdf->draw_text(310, 782, tv1dateconv(date('Y-m-d')), 				$firstpage, $norm);
		$pdf->draw_text(430, 792, t("Asiaa hoitaa", $kieli), 	$firstpage, $pieni);
		$pdf->draw_text(430, 782, $kukarow["nimi"], 				$firstpage, $norm);

		$pdf->draw_rectangle(779, 300, 758, 580, $firstpage, $rectparam);
		$pdf->draw_rectangle(779, 420, 758, 580, $firstpage, $rectparam);
/*		$pdf->draw_text(310, 771, t("Er�p�iv�", $kieli), $firstpage, $pieni);

		$paiva	   = date("j");
		$kuu   	   = date("n");
		$year  	   = date("Y");
		$seurday   = date("j",mktime(0, 0, 0, $kuu, $paiva+7,  $year));
		$seurmonth = date("n",mktime(0, 0, 0, $kuu, $paiva+7,  $year));
		$seuryear  = date("Y",mktime(0, 0, 0, $kuu, $paiva+7,  $year));
		$pdf->draw_text(310, 761, $seuryear."-".$seurmonth."-".$seurday, $firstpage, $norm);
*/
		$pdf->draw_text(430, 771, t("Puhelin", $kieli), $firstpage, $pieni);
		$pdf->draw_text(430, 761, $kukarow["puhno"], 		$firstpage, $norm);

		$pdf->draw_rectangle(758, 300, 737, 580, $firstpage, $rectparam);
		$pdf->draw_rectangle(758, 420, 737, 580, $firstpage, $rectparam);
//		$pdf->draw_text(310, 750, t("Viiv�stykorko", $kieli), 	$firstpage, $pieni);
//		$pdf->draw_text(310, 740, $yhtiorow["viivastyskorko"], 	$firstpage, $norm);
		$pdf->draw_text(430, 750, t("S�hk�posti", $kieli), 		$firstpage, $pieni);
		$pdf->draw_text(430, 740,  $kukarow["eposti"],				$firstpage, $norm);

		//Rivit alkaa t�s� kohtaa
		$kala = 620;

		//Laskurivien otsikkotiedot
		//eka rivi
		$pdf->draw_text(30,  $kala, t("Laskunro", $kieli),			$firstpage, $pieni);
		$pdf->draw_text(100, $kala, t("Pvm", $kieli),				$firstpage, $pieni);
		$pdf->draw_text(180, $kala, t("Er�pvm", $kieli),			$firstpage, $pieni);
		$oikpos = $pdf->strlen(t("Summa", $kieli), $pieni);
		$pdf->draw_text(400-$oikpos, $kala, t("Summa", $kieli),			$firstpage, $pieni);
		$oikpos = $pdf->strlen(t("Avoinsumma", $kieli), $pieni);
		$pdf->draw_text(480-$oikpos, $kala, t("Avoinsumma", $kieli),		$firstpage, $pieni);

		$kala -= 15;

		return($firstpage);
	}

	function rivi ($tyyppi, $firstpage, $summa, $row) {
		global $pdf, $kala, $sivu, $lask, $rectparam, $norm, $pieni, $lask, $kieli, $yhtiorow;

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

			if ($row['valkoodi'] == $yhtiorow['valkoodi']) {
				$oikpos = $pdf->strlen($row["avoinsumma"], $norm);
				$pdf->draw_text(480-$oikpos, $kala, $row["avoinsumma"], $firstpage, $norm);
				$oikpos = $pdf->strlen($row["summa"], $norm);
				$pdf->draw_text(400-$oikpos, $kala, $row["summa"],		$firstpage, $norm);
			}
			else {
				$oikpos = $pdf->strlen($row["avoinsummavaluutassa"], $norm);
				$pdf->draw_text(480-$oikpos, $kala, $row["avoinsummavaluutassa"],	$firstpage, $norm);
				$oikpos = $pdf->strlen($row["summa_valuutassa"], $norm);
				$pdf->draw_text(400-$oikpos, $kala, $row["summa_valuutassa"],		$firstpage, $norm);
			}
		}
		else {
			$pdf->draw_text(30,  $kala, t("Kohdistamaton suoritus"),	$firstpage, $norm);
			$pdf->draw_text(180, $kala, tv1dateconv($row["tapvm"]), 	$firstpage, $norm);
			$pdf->draw_text(300, $kala, $row["valkoodi"], 				$firstpage, $norm);
			$oikpos = $pdf->strlen($row["summa"], $norm);
			$pdf->draw_text(480-$oikpos, $kala, $row["summa"], 			$firstpage, $norm);
		}
		$kala = $kala - 13;
		$lask++;
		return($firstpage);
	}

	function loppu ($firstpage, $summat) {

		global $pdf, $yhtiorow, $kukarow, $sivu, $rectparam, $norm, $pieni, $kieli, $lask, $kala;

		if (sizeof($summat) > 1 and  $lask > 35) { //Valuuttaerittely ei mahdu! Rekursio! IIK!
			$sivu++;
			loppu($firstpage, array());
			$firstpage = alku();
			$kala = 605;
			$lask = 1;
		}

		//yhteens�rivi
		$pdf->draw_rectangle(110, 20, 90, 580,	$firstpage, $rectparam);
		$pdf->draw_rectangle(110, 207, 90, 580,	$firstpage, $rectparam);
		$pdf->draw_rectangle(110, 394, 90, 580,	$firstpage, $rectparam);
		$pdf->draw_rectangle(110, 540, 90, 580,	$firstpage, $rectparam);

		if (sizeof($summat) > 1) {
			$oikpos = $pdf->strlen("----------------", $norm);
			$pdf->draw_text(480-$oikpos, $kala, "----------------",	$firstpage, $norm);
			$kala = $kala - 13;
		}

		foreach ($summat as $valuutta => $summa) {
			if (sizeof($summat) == 1) {
				$pdf->draw_text(404, 92,  t("YHTEENS�", $kieli).":",	$firstpage, $norm);
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


		//Alimmat kolme laatikkoa, yhti�tietoja
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

	require('../pdflib/phppdflib.class.php');

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
		$result = mysql_query($query) or pupe_error($query);
		$asiakasrow2 = mysql_fetch_array($result);

		$tunnukset 	= $asiakasrow2['tunnukset'];
	}

	$query = "	SELECT tapvm,
				liitostunnus,
				summa - saldo_maksettu - pyoristys avoinsumma,
				summa_valuutassa - saldo_maksettu_valuutassa - pyoristys_valuutassa  avoinsummavaluutassa,
				summa,
				summa_valuutassa,
				valkoodi,
				erpcm,
				laskunro
				FROM lasku
				WHERE yhtio ='$kukarow[yhtio]'
				and tila = 'U'
				and mapvm = '0000-00-00'
				and liitostunnus in ($tunnukset)";
	$result = mysql_query($query) or pupe_error($query);
	$laskutiedot = mysql_fetch_array($result);

	//otetaan asiakastiedot ekalta laskulta
	$query = "	SELECT *
				FROM asiakas
				WHERE yhtio='$kukarow[yhtio]' AND tunnus = '$laskutiedot[liitostunnus]'";
	$asiakasresult = mysql_query($query) or pupe_error($query);
	$asiakastiedot = mysql_fetch_array($asiakasresult);

	//Otetaan t�ss� asiakkaan kieli talteen
	$kieli = $asiakastiedot["kieli"];

	//ja kelataan akuun
	if (mysql_num_rows($result) > 0) {
		mysql_data_seek($result, 0);
	}

	$query = "	SELECT maksupvm tapvm, summa * -1 summa, valkoodi
				FROM suoritus
				WHERE yhtio  = '$kukarow[yhtio]'
				and ltunnus <> 0
				and kohdpvm = '0000-00-00'
				and asiakas_tunnus in ($tunnukset)";
	$suoritusresult = mysql_query($query) or pupe_error($query);

	$firstpage = alku();

	$totaali = array();

	while ($row = mysql_fetch_array($result)) {

		$firstpage = rivi(1, $firstpage, $summa, $row);

		if ($row['valkoodi'] == $yhtiorow['valkoodi']) {
			$totaali[$row['valkoodi']] += $row['avoinsumma'];
		}
		else {
			$totaali[$row['valkoodi']] += $row['avoinsummavaluutassa'];
		}
	}

	while ($row = mysql_fetch_array($suoritusresult)) {
		$firstpage = rivi(2, $firstpage, $summa, $row);
		$totaali[$row['valkoodi']] += $row['summa'];
	}

	//$loppusumma = sprintf('%.2f', $summa+$kaatosumma); Multivaluutta versioon 2!!!

	loppu($firstpage,$totaali);

	//keksit��n uudelle failille joku varmasti uniikki nimi:
	list($usec, $sec) = explode(' ', microtime());
	mt_srand((float) $sec + ((float) $usec * 100000));
	$pdffilenimi = "/tmp/tiliote-".md5(uniqid(mt_rand(), true)).".pdf";

	//kirjoitetaan pdf faili levylle..
	$fh = fopen($pdffilenimi, "w");
	if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus ep�onnistui $pdffilenimi");
	fclose($fh);

	echo file_get_contents($pdffilenimi);

?>