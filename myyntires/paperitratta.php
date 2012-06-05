<?php

	if (!function_exists("uusi_karhukierros")) {
		function uusi_karhukierros($yhtio) {
			$query = "	SELECT tunnus
						FROM karhukierros
						where pvm  = current_date
						and yhtio  = '$yhtio'
						and tyyppi = 'T'";
			$result = pupe_query($query);

			if (!mysql_num_rows($result)) {
				$query = "INSERT INTO karhukierros (pvm,yhtio,tyyppi) values (current_date,'$yhtio','T')";
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

	function liita_lasku($ktunnus,$ltunnus) {
		$query = "INSERT INTO karhu_lasku (ktunnus,ltunnus) values ($ktunnus,$ltunnus)";
		$result = pupe_query($query);
	}

	function alku ($trattakierros_tunnus = '') {
		global $pdf, $asiakastiedot, $yhteyshenkilo, $yhtiorow, $kukarow, $kala, $sivu, $rectparam, $norm, $pieni, $kaatosumma, $kieli, $iso;

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

		tulosta_logo_pdf($pdf, $firstpage, "");

		//Otsikko
		$pdf->draw_text(310, 815, t("Tratta", $kieli), $firstpage, $iso);
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
		$pdf->draw_text(310, 718, $asiakastiedot["ytunnus"], 			$firstpage, $norm);


		//Oikea sarake
		$pdf->draw_rectangle(800, 300, 779, 580, 				$firstpage, $rectparam);
		$pdf->draw_rectangle(800, 420, 779, 580, 				$firstpage, $rectparam);
		$pdf->draw_text(310, 792, t("P‰iv‰m‰‰r‰", $kieli), 		$firstpage, $pieni);

		if ($trattakierros_tunnus != "") {
			$query = "	SELECT pvm
						FROM karhukierros
						WHERE tunnus = '$trattakierros_tunnus'
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

		$pdf->draw_text(310, 782, tv1dateconv($pvm_row["pvm"]),	$firstpage, $norm);
		$pdf->draw_text(430, 792, t("Asiaa hoitaa", $kieli), 	$firstpage, $pieni);
		$pdf->draw_text(430, 782, $yrow["nimi"], 				$firstpage, $norm);

		$pdf->draw_rectangle(779, 300, 758, 580, $firstpage, $rectparam);
		$pdf->draw_rectangle(779, 420, 758, 580, $firstpage, $rectparam);
		$pdf->draw_text(310, 771, t("Er‰p‰iv‰", $kieli), $firstpage, $pieni);

		$seurday   = date("d",mktime(0, 0, 0, $kuu, $paiva+7,  $year));
		$seurmonth = date("m",mktime(0, 0, 0, $kuu, $paiva+7,  $year));
		$seuryear  = date("Y",mktime(0, 0, 0, $kuu, $paiva+7,  $year));

		$pdf->draw_text(310, 761, tv1dateconv($seuryear."-".$seurmonth."-".$seurday), $firstpage, $norm);

		$pdf->draw_text(430, 771, t("Puhelin", $kieli), $firstpage, $pieni);
		$pdf->draw_text(430, 761, $yrow["puhno"], 		$firstpage, $norm);

		$pdf->draw_rectangle(758, 300, 737, 580, $firstpage, $rectparam);
		$pdf->draw_rectangle(758, 420, 737, 580, $firstpage, $rectparam);
		$pdf->draw_text(310, 750, t("Viiv‰stykorko", $kieli), 		$firstpage, $pieni);
		$pdf->draw_text(310, 740, $yhtiorow["viivastyskorko"]."%", 	$firstpage, $norm);
		$pdf->draw_text(430, 750, t("S‰hkˆposti", $kieli), 			$firstpage, $pieni);
		$pdf->draw_text(430, 740,  $yrow["eposti"],					$firstpage, $norm);

		//Rivit alkaa t‰s‰ kohtaa
		$kala = 620;

		//Laskurivien otsikkotiedot
		//eka rivi
		$pdf->draw_text(30,  $kala, t("Laskun numero", $kieli)." / ".t("Viite", $kieli),		$firstpage, $pieni);
		$pdf->draw_text(180, $kala, t("Laskun pvm", $kieli),									$firstpage, $pieni);
		$pdf->draw_text(240, $kala, t("Er‰p‰iv‰", $kieli),										$firstpage, $pieni);
		$pdf->draw_text(295, $kala, t("Myˆh‰ss‰ pv", $kieli),									$firstpage, $pieni);
		$pdf->draw_text(360, $kala, t("Viimeisin muistutuspvm", $kieli),						$firstpage, $pieni);
		$pdf->draw_text(455, $kala, t("Laskun summa", $kieli),									$firstpage, $pieni);
		$pdf->draw_text(525, $kala, t("Perint‰kerta", $kieli),									$firstpage, $pieni);

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

	function rivi ($firstpage, $summa) {
		global $firstpage, $pdf, $row, $kala, $sivu, $lask, $rectparam, $norm, $pieni, $lask, $kieli, $karhukertanro, $yhtiorow, $kukarow;

		if (($lask == 29 and $sivu == 1) or ($lask == 37 and $sivu > 1)) {
			$sivu++;
			loppu($firstpage,'');
			$firstpage = alku();
			$kala = 605;
			$lask = 1;
		}

		$pdf->draw_text(30,  $kala, $row["laskunro"]." / ".$row["viite"],	$firstpage, $norm);
		$pdf->draw_text(180, $kala, tv1dateconv($row["tapvm"]), 			$firstpage, $norm);
		$pdf->draw_text(240, $kala, tv1dateconv($row["erpcm"]), 			$firstpage, $norm);

		$oikpos = $pdf->strlen($row["ika"], $norm);
		$pdf->draw_text(338-$oikpos, $kala, $row["ika"], 					$firstpage, $norm);

		$pdf->draw_text(365, $kala, tv1dateconv($row["kpvm"]),				$firstpage, $norm);

		if ($row["valkoodi"] != $yhtiorow["valkoodi"]) {
			$oikpos = $pdf->strlen($row["summa_valuutassa"], $norm);
			$pdf->draw_text(500-$oikpos, $kala, $row["summa_valuutassa"]." ".$row["valkoodi"], 	$firstpage, $norm);
			$summa+=$row["summa_valuutassa"];
		}
		else {
			$oikpos = $pdf->strlen($row["summa"], $norm);
			$pdf->draw_text(500-$oikpos, $kala, $row["summa"]." ".$row["valkoodi"], 				$firstpage, $norm);
			$summa+=$row["summa"];
		}

		if ($karhukertanro == "") {
			$karhukertanro = $row["karhuttu"] + 1;
		}

		$oikpos = $pdf->strlen($karhukertanro, $norm);
		$pdf->draw_text(560-$oikpos, $kala, $karhukertanro, 			$firstpage, $norm);

		$kala = $kala - 13;

		$lask++;
		return($summa);
	}

	function loppu ($firstpage, $summa, $valkoodi) {

		global $pdf, $yhtiorow, $kukarow, $sivu, $rectparam, $norm, $pieni, $kaatosumma, $kieli, $maksuehtotiedot;

		//yhteens‰rivi
		$pdf->draw_rectangle(134,  20, 115, 580,	$firstpage, $rectparam);
		$pdf->draw_rectangle(134, 207, 115, 580,	$firstpage, $rectparam);
		$pdf->draw_rectangle(134, 394, 115, 580,	$firstpage, $rectparam);
		$pdf->draw_rectangle(134, 540, 115, 580,	$firstpage, $rectparam);

		$pdf->draw_text(404, 122,  t("YHTEENSƒ", $kieli).":",	$firstpage, $norm);
		$pdf->draw_text(464, 122,  $summa,						$firstpage, $norm);
		$pdf->draw_text(550, 122,  $valkoodi,		$firstpage, $norm);

		if ($maksuehtotiedot["pankkinimi1"] != "") {
			$yhtiorow["pankkinimi1"]	= $maksuehtotiedot["pankkinimi1"];
			$yhtiorow["pankkitili1"]	= $maksuehtotiedot["pankkitili1"];
			$yhtiorow["pankkiiban1"]	= $maksuehtotiedot["pankkiiban1"];
			$yhtiorow["pankkiswift1"]	= $maksuehtotiedot["pankkiswift1"];
			$yhtiorow["pankkinimi2"]	= $maksuehtotiedot["pankkinimi2"];
			$yhtiorow["pankkitili2"]	= $maksuehtotiedot["pankkitili2"];
			$yhtiorow["pankkiiban2"]	= $maksuehtotiedot["pankkiiban2"];
			$yhtiorow["pankkiswift2"]	= $maksuehtotiedot["pankkiswift2"];
			$yhtiorow["pankkinimi3"]	= $maksuehtotiedot["pankkinimi3"];
			$yhtiorow["pankkitili3"]	= $maksuehtotiedot["pankkitili3"];
			$yhtiorow["pankkiiban3"]	= $maksuehtotiedot["pankkiiban3"];
			$yhtiorow["pankkiswift3"]	= $maksuehtotiedot["pankkiswift3"];
		}

		//Pankkiyhteystiedot
		$pdf->draw_rectangle(115, 20, 20, 580,	$firstpage, $rectparam);

		$pdf->draw_text(30, 106,  t("Pankkiyhteys", $kieli),	$firstpage, $pieni);

		$pdf->draw_text(30,  94, $yhtiorow["pankkinimi1"]." ".$yhtiorow["pankkitili1"],	$firstpage, $norm);
		$pdf->draw_text(217, 94, $yhtiorow["pankkinimi2"]." ".$yhtiorow["pankkitili2"],	$firstpage, $norm);
		$pdf->draw_text(404, 94, $yhtiorow["pankkinimi3"]." ".$yhtiorow["pankkitili3"],	$firstpage, $norm);

		if ($yhtiorow["pankkiiban1"] != "") {
			$pdf->draw_text(30,  83, "IBAN: ".$yhtiorow["pankkiiban1"],	$firstpage, $pieni);
		}
		if ($yhtiorow["pankkiiban2"] != "") {
			$pdf->draw_text(217, 83, "IBAN: ".$yhtiorow["pankkiiban2"],	$firstpage, $pieni);
		}
		if ($yhtiorow["pankkiiban3"] != "") {
			$pdf->draw_text(404, 83, "IBAN: ".$yhtiorow["pankkiiban3"],	$firstpage, $pieni);
		}
		if ($yhtiorow["pankkiswift1"] != "") {
			$pdf->draw_text(30,  72, "SWIFT: ".$yhtiorow["pankkiswift1"],	$firstpage, $pieni);
		}
		if ($yhtiorow["pankkiswift2"] != "") {
			$pdf->draw_text(217, 72, "SWIFT: ".$yhtiorow["pankkiswift2"],	$firstpage, $pieni);
		}
		if ($yhtiorow["pankkiswift3"] != "") {
			$pdf->draw_text(404, 72, "SWIFT: ".$yhtiorow["pankkiswift3"],	$firstpage, $pieni);
		}

		//Alimmat kolme laatikkoa, yhtiˆtietoja
		$pdf->draw_rectangle(65, 20,  20, 580,	$firstpage, $rectparam);
		$pdf->draw_rectangle(65, 207, 20, 580,	$firstpage, $rectparam);
		$pdf->draw_rectangle(65, 394, 20, 580,	$firstpage, $rectparam);

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

	$pieni["height"] 	= 8;
	$pieni["font"] 		= "Times-Roman";

	$iso["height"] 		= 14;
	$iso["font"] 		= "Helvetica-Bold";

	// defaultteja
	$lask = 1;
	$sivu = 1;

	// aloitellaan laskun teko
	$xquery='';
	for ($i=0; $i < count($lasku_tunnus); $i++) {
		if($i != 0) {
			$xquery=$xquery . ",";
		}

		$xquery .= "$lasku_tunnus[$i]";
	}

	if ($nayta_pdf == 1 and $karhutunnus != '') {
		$karhutunnus = mysql_real_escape_string($karhutunnus);
		$kjoinlisa = " and kl.ktunnus = '$karhutunnus' ";

		$query = "	SELECT count(distinct ktunnus) kerta
					FROM karhu_lasku
					JOIN karhukierros ON (karhukierros.tunnus = karhu_lasku.ktunnus AND karhukierros.tyyppi = 'T')
					WHERE ltunnus in ($xquery)
					AND ktunnus <= $karhutunnus";
		$karhukertares = pupe_query($query);
		$karhukertarow = mysql_fetch_assoc($karhukertares);

		$karhukertanro = $karhukertarow['kerta'];
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
				LEFT JOIN karhukierros kk on (kk.tunnus = kl.ktunnus and kk.tyyppi = 'T')
				WHERE l.tunnus in ($xquery)
				and l.yhtio = '$kukarow[yhtio]'
				and l.tila = 'U'
				GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13
				ORDER BY l.erpcm";
	$result = pupe_query($query);

	//otetaan maksuehto- ja asiakastiedot ekalta laskulta
	$laskutiedot = mysql_fetch_assoc($result);

	$query = "	SELECT *
				FROM maksuehto
				WHERE yhtio='$kukarow[yhtio]' AND tunnus = '$laskutiedot[maksuehto]'";
	$maksuehtoresult = pupe_query($query);
	$maksuehtotiedot = mysql_fetch_assoc($maksuehtoresult);

	$query = "	SELECT *
				FROM asiakas
				WHERE yhtio='$kukarow[yhtio]' AND tunnus = '$laskutiedot[liitostunnus]'";
	$asiakasresult = pupe_query($query);
	$asiakastiedot = mysql_fetch_assoc($asiakasresult);

	//Otetaan t‰ss‰ asiakkaan kieli talteen
	$kieli = $asiakastiedot["kieli"];

	//ja kelataan akuun
	mysql_data_seek($result,0);

	$query = "	SELECT GROUP_CONCAT(distinct liitostunnus) liitokset
				FROM lasku
				WHERE lasku.yhtio = '$kukarow[yhtio]'
				and lasku.tunnus in ($xquery)";
	$lires = pupe_query($query);
	$lirow = mysql_fetch_assoc($lires);

	// Karhuvaiheessa t‰m‰ on tyhj‰
	if ($laskutiedot["kpvm"] == "") {
		$laskutiedot["kpvm"] = date("Y-m-d");
	}

	$query = "	SELECT sum(summa) summa
				FROM suoritus
				WHERE yhtio  = '$kukarow[yhtio]'
				and (kohdpvm = '0000-00-00' or kohdpvm > '$laskutiedot[kpvm]')
				and ltunnus  > 0
				and kirjpvm <= '$laskutiedot[kpvm]'
				and asiakas_tunnus in ($lirow[liitokset])";
	$summaresult = pupe_query($query);
	$kaato = mysql_fetch_assoc($summaresult);

	$kaatosumma=$kaato["summa"] * -1;
	if (!$kaatosumma) $kaatosumma='0.00';

	if ($tee_pdf != 'tulosta_tratta') {
		$karhukierros=uusi_karhukierros($kukarow['yhtio']);
	}

	$firstpage = alku($karhutunnus);

	$summa=0.0;
	while ($row = mysql_fetch_assoc($result)) {
		if ($tee_pdf != 'tulosta_tratta') {
			liita_lasku($karhukierros,$row['tunnus']);
		}
		$summa = rivi($firstpage, $summa);
	}

	$loppusumma = sprintf('%.2f', $summa+$kaatosumma);

	loppu($firstpage,$loppusumma, $laskutiedot["valkoodi"]);

	//keksit‰‰n uudelle failille joku varmasti uniikki nimi:
	list($usec, $sec) = explode(' ', microtime());
	mt_srand((float) $sec + ((float) $usec * 100000));
	$pdffilenimi = "/tmp/tratta-".md5(uniqid(mt_rand(), true)).".pdf";

	//kirjoitetaan pdf faili levylle..
	$fh = fopen($pdffilenimi, "w");
	if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus ep‰onnistui $pdffilenimi");
	fclose($fh);

	if ($nayta_pdf == 1) {
		echo file_get_contents($pdffilenimi);
	}

	if ($nayta_pdf != 1 and $tee_pdf != 'tulosta_tratta') {
		// itse print komento...
		$query = "	SELECT komento
					from kirjoittimet
					where yhtio = '{$kukarow['yhtio']}'
					and tunnus = '{$kukarow['kirjoitin']}'";
		$kires = pupe_query($query);

		if (mysql_num_rows($kires) == 1) {
			$kirow = mysql_fetch_assoc($kires);
			if($kirow["komento"] == "email") {
				$liite = $pdffilenimi;
				$kutsu = "Tratta ".$asiakastiedot["ytunnus"];
				echo t("Tratta l‰hetet‰‰n osoitteeseen")."  $kukarow[eposti]...\n<br>";

				require("inc/sahkoposti.inc");
			}
			else {
				$line = exec("{$kirow['komento']} $pdffilenimi");
			}
		}
	}
?>
