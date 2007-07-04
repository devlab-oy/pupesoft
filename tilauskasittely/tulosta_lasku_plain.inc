<?php

require_once('pdflib/phppdflib.class.php');

$rectparam["width"] = 0.3;

$norm["height"] = 10;
$norm["font"] = "Times-Roman";

$pieni["height"] = 8;
$pieni["font"] = "Times-Roman";

$boldi["height"] = 10;
$boldi["font"] = "Times-Bold";

$italic["height"] = 10;
$italic["font"] = "Times-Italic";

// defaultteja
$kala = 540;
$lask = 1;
$sivu = 1;

// laskun alku ja loppu functiot...
if (!function_exists('alku')) {
	function alku () {
		global $pdf, $laskurow, $yhtiorow, $kukarow, $sivu, $rectparam, $norm, $pieni, $maksuehto, $kateistyyppi, $kieli, $boldi, $sispit, $sispit2, $italic, $lask, $kala;

		//PDF parametrit
		if (!isset($pdf)) {
			$pdf = new pdffile;
			$pdf->set_default('margin-top', 	0);
			$pdf->set_default('margin-bottom', 	0);
			$pdf->set_default('margin-left', 	0);
			$pdf->set_default('margin-right', 	0);
		}

		$firstpage = $pdf->new_page("a4");

//		$pdf->enable('template');
//		$tid = $pdf->template->create();
//		$pdf->template->size($tid, 600, 830);

		//Otsikko
		//$pdf->draw_rectangle(830, 20,  800, 580, $firstpage, $rectparam);
		if (trim($yhtiorow["lasku_logo"]) != '' and file_exists($yhtiorow["lasku_logo"])) {
			$filename = $yhtiorow["lasku_logo"];

			$fh = fopen($filename, "r");
			$data = fread($fh, filesize($filename));
			fclose($fh);

			$image = $pdf->jfif_embed($data);

			if(!$image) {
				echo t("Logokuvavirhe").": ".$image.$data;
			}
			else {
				$kala = array();
				$kala['scale'] = 0.15;
				$placement = $pdf->image_place($image, mm_pt(280), mm_pt(10), $firstpage, $kala);
			}
		}
		else {
			//$pdf->draw_text(150, 815,  $yhtiorow["nimi"], $firstpage);
			$pdf->draw_text(30, 815,  $yhtiorow["nimi"], $firstpage);
		}


		if ($laskurow["summa"] >= 0) {
			$pdf->draw_text(310, 815, t("Lasku", $kieli), $firstpage);
		}
		else {
			$pdf->draw_text(310, 815, t("Hyvityslasku", $kieli), $firstpage);
		}

		$pdf->draw_text(430, 815, t("Sivu", $kieli)." ".$sivu, $firstpage, $norm);

		//Vasen sarake
		//$pdf->draw_rectangle(737, 20,  674, 300, $firstpage, $rectparam);
		$pdf->draw_text(50, 729, t("Laskutusosoite", $kieli), 	$firstpage, $pieni);
		$pdf->draw_text(50, 717, $laskurow["nimi"], 			$firstpage, $norm);
		$pdf->draw_text(50, 707, $laskurow["nimitark"],			$firstpage, $norm);
		$pdf->draw_text(50, 697, $laskurow["osoite"], 			$firstpage, $norm);
		$pdf->draw_text(50, 687, $laskurow["postino"]." ".$laskurow["postitp"], $firstpage, $norm);


		//$pdf->draw_rectangle(674, 20,  611, 300, $firstpage, $rectparam);
		$pdf->draw_text(50, 656, t("Toimitusosoite", $kieli), 	$firstpage, $pieni);
		$pdf->draw_text(50, 644, $laskurow["toim_nimi"], 		$firstpage, $norm);
		$pdf->draw_text(50, 634, $laskurow["toim_nimitark"], 	$firstpage, $norm);
		$pdf->draw_text(50, 624, $laskurow["toim_osoite"],	 	$firstpage, $norm);
		$pdf->draw_text(50, 614, $laskurow["toim_postino"]." ".$laskurow["toim_postitp"], $firstpage, $norm);


		//Oikea sarake
		$pdf->draw_rectangle(800, 300, 779, 580, 	$firstpage, $rectparam);
		$pdf->draw_rectangle(800, 420, 779, 580, 	$firstpage, $rectparam);
		$pdf->draw_text(310, 792, t("Laskun pvm", $kieli), 	$firstpage, $pieni);
		$pdf->draw_text(310, 782, tv1dateconv($laskurow["tapvm"]),		$firstpage, $norm);
		$pdf->draw_text(430, 792, t("Eräpäivä", $kieli), 	$firstpage, $pieni);
		
		if ($kateistyyppi != '') {
			$erapaiva = t('MAKSETTU', $kieli);
		} else {
			$erapaiva = tv1dateconv($laskurow["erpcm"]);
		}
		
		$pdf->draw_text(430, 782, $erapaiva, $firstpage, $norm);

		$pdf->draw_rectangle(779, 300, 758, 580, $firstpage, $rectparam);
		$pdf->draw_rectangle(779, 420, 758, 580, $firstpage, $rectparam);
		$pdf->draw_text(310, 771, t("Viitenumero", $kieli), 	$firstpage, $pieni);
		$pdf->draw_text(310, 761, $laskurow["viite"], 			$firstpage, $norm);
		$pdf->draw_text(430, 771, t("Laskun numero", $kieli), 	$firstpage, $pieni);
		$pdf->draw_text(430, 761, $laskurow["laskunro"], 		$firstpage, $boldi);

		$pdf->draw_rectangle(758, 300, 737, 580, $firstpage, $rectparam);
		$pdf->draw_rectangle(758, 420, 737, 580, $firstpage, $rectparam);
		$pdf->draw_text(310, 750, t("Maksuehto", $kieli), $firstpage, $pieni);
		$pdf->draw_text(310, 740, $maksuehto, $firstpage, $norm);
		$pdf->draw_text(430, 750, t("Kassa-alennus", $kieli)." ".$laskurow["valkoodi"], $firstpage, $pieni);
		if ($laskurow["kasumma"] != 0.00) {
			$pdf->draw_text(430, 740, $laskurow["kasumma"], $firstpage, $norm);
		}

		$pdf->draw_rectangle(737, 300, 716, 580, $firstpage, $rectparam);
		$pdf->draw_rectangle(737, 420, 716, 580, $firstpage, $rectparam);
		$pdf->draw_text(310, 729, t("Viivästyskorko", $kieli), 		$firstpage, $pieni);
		$pdf->draw_text(310, 719, $laskurow["viikorkopros"],	 	$firstpage, $norm);

		//etsitään tilausnumerot
		if ($laskurow["tila"] == 'U') {
			$where = "uusiotunnus='".$laskurow['tunnus']."' ";
		}
		else {
			$where = "otunnus='".$laskurow['tunnus']."' ";
		}

		$query  = "	select group_concat(distinct(otunnus))
					from tilausrivi
					where yhtio='$kukarow[yhtio]' and tyyppi='L' and $where
					order by otunnus";
		$myyresult = mysql_query($query) or pupe_error($query);
		$myyrow = mysql_fetch_array($myyresult);

		$pdf->draw_text(430, 729, t("Tilausnumerot", $kieli), 	$firstpage, $pieni);
		$pdf->draw_text(430, 719, $myyrow[0], $firstpage, $norm);

		$pdf->draw_rectangle(716, 300, 695, 580, $firstpage, $rectparam);
		$pdf->draw_rectangle(716, 420, 695, 580, $firstpage, $rectparam);
		$pdf->draw_text(310, 708, t("Valuutta", $kieli), 		$firstpage, $pieni);
		$pdf->draw_text(310, 698, $laskurow["valkoodi"], 		$firstpage, $norm);
		$pdf->draw_text(430, 708, t("Asiaa hoitaa", $kieli), 	$firstpage, $pieni);

		//etsitään myyjän nimi
		$query  = "	select nimi
					from kuka
					where tunnus='$laskurow[myyja]' and yhtio='$kukarow[yhtio]'";
		$myyresult = mysql_query($query) or pupe_error($query);
		$myyrow = mysql_fetch_array($myyresult);

		$pdf->draw_text(430, 698, $myyrow["nimi"], $firstpage, $norm);

		$pdf->draw_rectangle(695, 300, 674, 580, $firstpage, $rectparam);
		$pdf->draw_rectangle(695, 420, 674, 580, $firstpage, $rectparam);
		$pdf->draw_text(310, 687, t("Asiakkaan Y-tunnus", $kieli), 	$firstpage, $pieni);
		$pdf->draw_text(310, 677, $laskurow["ytunnus"], 			$firstpage, $norm);
		$pdf->draw_text(430, 687, t("Asiakasnumero", $kieli), 		$firstpage, $pieni);
		$pdf->draw_text(430, 677, $laskurow["ytunnus"], 			$firstpage, $norm);

		$pdf->draw_rectangle(674, 300, 653, 580, $firstpage, $rectparam);
		$pdf->draw_rectangle(674, 420, 653, 580, $firstpage, $rectparam);
		$pdf->draw_text(310, 666, t("Toimitustapa", $kieli), 	$firstpage, $pieni);
		$pdf->draw_text(310, 656, $laskurow["toimitustapa"], 	$firstpage, $norm);

		//etsitään toimituspäivät ($where on jo asetettu)

		$query  = "	select group_concat(distinct(left(toimitettuaika,10)))
					from tilausrivi
					where yhtio='$kukarow[yhtio]' and tyyppi='L' and $where
					order by otunnus";
		$myyresult = mysql_query($query) or pupe_error($query);
		$myyrow = mysql_fetch_array($myyresult);


		$pdf->draw_text(430, 666, t("Toimituspvm", $kieli), 	$firstpage, $pieni);
		$pdf->draw_text(430, 656, tv1dateconv($myyrow[0]), 		$firstpage, $norm);

		$pdf->draw_rectangle(653, 300, 632, 580, $firstpage, $rectparam);
		$pdf->draw_text(310, 645, t("Toimitusehto", $kieli), $firstpage, $pieni);
		//$pdf->draw_text(310, 635, $laskurow["toimitusehto"], $firstpage, $norm);

		$pdf->draw_rectangle(632, 300, 611, 580, $firstpage, $rectparam);
		$pdf->draw_text(310, 624, t("Lisätietoja", $kieli), 	$firstpage, $pieni);
		$pdf->draw_text(310, 614, $laskurow["viesti"], 			$firstpage, $norm);

		$pdf->draw_text(310, 595, t("Mahdolliset muistutukset tehtävä 8 päivän kuluessa", $kieli), $firstpage, $pieni);

		//tehdään viestejä ja lasketaan niitten pituuksia
		if ($laskurow['sisviesti1'] != '' and $sivu == 1) {
			$pdf->draw_text(35,  580, t("Huomautukset", $kieli).":", $firstpage, $pieni);
			$pohja = $pdf->draw_paragraph(580, 35, 120, 550, $laskurow["sisviesti1"], $firstpage, $italic);
			$kala = $pohja - 15;
		}
		else {
			$kala = 572;
		}

		if ($lask < 16 or $sivu > 1) {
			//Laskurivien otsikkotiedot
			//eka rivi
			$pdf->draw_text(35,  $kala, t("Tuotenimi", $kieli),		$firstpage, $pieni);
			$pdf->draw_text(210, $kala, t("Tuotenumero", $kieli),		$firstpage, $pieni);
			$pdf->draw_text(350, $kala, t("Määrä", $kieli),			$firstpage, $pieni);
			$pdf->draw_text(410, $kala, t("A-hinta", $kieli),			$firstpage, $pieni);
			$pdf->draw_text(470, $kala, t("Alennus", $kieli)."-%",	$firstpage, $pieni);
			$pdf->draw_text(530, $kala, t("Rivihinta", $kieli),	$firstpage, $pieni);
			//toka rivi
			$kala = $kala - 9;
			$pdf->draw_text(35,  $kala, t("Lisätietoja", $kieli),		$firstpage, $pieni);
			$pdf->draw_text(210, $kala, "",								$firstpage, $pieni);
			$pdf->draw_text(350, $kala, "",								$firstpage, $pieni);
			//$pdf->draw_text(410, $kala, t("Toimituspvm", $kieli),		$firstpage, $pieni);
			$pdf->draw_text(470, $kala, "",								$firstpage, $pieni);

			$kala = $kala - 25;
		}

		return($firstpage);
	}
}

if (!function_exists('rivi')) {
	function rivi ($firstpage) {
		global $firstpage, $pdf, $row, $kala, $sivu, $lask, $rectparam, $norm, $pieni, $lask, $kieli, $yhtiorow, $kukarow;

		if ($lask == 14) {
			$sivu++;
			loppu($firstpage);
			$firstpage = alku();
			$kala = 540;
			$lask = 1;
		}

		if (strtolower($kieli) != strtolower($yhtiorow['kieli'])) {
			$laji = 'nimitys_'.strtolower($kieli);
			$query = "SELECT selite FROM tuotteen_avainsanat WHERE yhtio = '$kukarow[yhtio]' and laji = '$laji' and tuoteno = '$row[tuoteno]' LIMIT 1";
			$nimresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($nimresult)>0) {
				$nimrow=mysql_fetch_array($nimresult);
				$row['nimitys']=$nimrow['selite'];
			}
		}

		$pdf->draw_text(35,  $kala, substr($row["nimitys"],0,27),	$firstpage, $norm);
		$pdf->draw_text(210, $kala, $row["tuoteno"], 	$firstpage, $norm);
		$pdf->draw_text(350, $kala, $row["kpl"], 		$firstpage, $norm);
		$pdf->draw_text(410, $kala, $row["hinta"], 		$firstpage, $norm);
		$pdf->draw_text(470, $kala, $row["ale"],		$firstpage, $norm);
		$pdf->draw_text(530, $kala, $row["rivihinta"], 	$firstpage, $norm);
		$kala = $kala - 10;

		$pdf->draw_text(35,  $kala, $row["kommentti"],	$firstpage, $pieni);

//		$pdf->draw_text(350, $kala, $row["yksikko"],	$firstpage, $pieni);

/*
		$row["toimitettuaika"] = substr($row["toimitettuaika"],0,10);

		if ($row["toimitettuaika"] == "0000-00-00") {
			$row["toimitettuaika"] = "";
		}

		$pdf->draw_text(410, $kala, $row["toimitettuaika"], 	$firstpage, $pieni);
*/
		$verollinenhinta = round($row["rivihinta"]*(1+($row["alv"]/100)),2);
		$verollinenhinta = sprintf('%.2f',$verollinenhinta );


		$kala = $kala - 15;
		$lask++;
	}
}

if (!function_exists('loppu')) {
	function loppu ($firstpage) {

		global $pdf, $laskurow, $yhtiorow, $kukarow, $sivu, $rectparam, $norm, $pieni, $kieli, $masrow, $boldi, $uytunnus, $ytunpit, $kateistyyppi;

		if ($masrow["factoring"] != '') {
			$pdf->draw_text(30, 130, "Vår fordran enligt denna faktura har överlåtits till S E B Finans AB (publ).", $firstpage);
			$pdf->draw_text(30, 115, "Fakturan skall därför betalas direkt till S E B Finans AB(publ), 167 81  BROMMA.", $firstpage);
		}

		//yhteensärivi
		$pdf->draw_rectangle(110, 20, 90, 580,	$firstpage, $rectparam);
		$pdf->draw_rectangle(110, 207, 90, 580,	$firstpage, $rectparam);
		$pdf->draw_rectangle(110, 394, 90, 580,	$firstpage, $rectparam);
		$pdf->draw_rectangle(110, 540, 90, 580,	$firstpage, $rectparam);

		$pdf->draw_text(30,  102, t("Eräpäivä", $kieli),	$firstpage, $pieni);
		
		if ($kateistyyppi != '') {
			$erapaiva = t('MAKSETTU', $kieli);
		} else {
			$erapaiva = tv1dateconv($laskurow["erpcm"]);
		}
		
		$pdf->draw_text(30,  92,  $erapaiva,		$firstpage, $norm);
		$pdf->draw_text(217, 102, t("Viitenumero", $kieli),	$firstpage, $pieni);
		$pdf->draw_text(217, 92,  $laskurow["viite"],		$firstpage, $norm);
		$pdf->draw_text(404, 92,  t("Yhteensä", $kieli).":",$firstpage, $norm);
		$pdf->draw_text(464, 92,  $laskurow["summa"],		$firstpage, $boldi);
		$pdf->draw_text(550, 92,  $laskurow["valkoodi"],	$firstpage, $boldi);

		//Pankkiyhteystiedot
		$pdf->draw_rectangle(90, 20, 20, 580,	$firstpage, $rectparam);
		
		if ($masrow["pankkinimi1"] != "") {
			$yhtiorow["pankkinimi1"]	= $masrow["pankkinimi1"];
			$yhtiorow["pankkitili1"]	= $masrow["pankkitili1"];
			$yhtiorow["pankkiiban1"]	= $masrow["pankkiiban1"];
			$yhtiorow["pankkiswift1"]	= $masrow["pankkiswift1"];
			$yhtiorow["pankkinimi2"]	= $masrow["pankkinimi2"];
			$yhtiorow["pankkitili2"]	= $masrow["pankkitili2"];
			$yhtiorow["pankkiiban2"]	= $masrow["pankkiiban2"];
			$yhtiorow["pankkiswift2"]	= $masrow["pankkiswift2"];
			$yhtiorow["pankkinimi3"]	= $masrow["pankkinimi3"];
			$yhtiorow["pankkitili3"]	= $masrow["pankkitili3"];
			$yhtiorow["pankkiiban3"]	= $masrow["pankkiiban3"];
			$yhtiorow["pankkiswift3"]	= $masrow["pankkiswift3"];
			
		}
		
	    if ($masrow["factoring"] != '') {
			$query = "	SELECT *
						FROM factoring
						WHERE yhtio 		= '{$kukarow['yhtio']}'
						and factoringyhtio 	= '{$masrow['factoring']}'
						and valkoodi 		= '{$laskurow['valkoodi']}'";
			$fres = mysql_query($query) or pupe_error($query);
			$frow = mysql_fetch_array($fres);
			
			$yhtiorow["pankkinimi1"]  =	$frow["pankkinimi1"];
			$yhtiorow["pankkitili1"]  =	$frow["pankkitili1"];
			$yhtiorow["pankkiiban1"]  =	$frow["pankkiiban1"];
			$yhtiorow["pankkiswift1"] =	$frow["pankkiswift1"];
			$yhtiorow["pankkinimi2"]  =	$frow["pankkinimi2"];
			$yhtiorow["pankkitili2"]  =	$frow["pankkitili2"];
			$yhtiorow["pankkiiban2"]  =	$frow["pankkiiban2"];
			$yhtiorow["pankkiswift2"] = $frow["pankkiswift2"];
			$yhtiorow["pankkinimi3"]  =	"";
			$yhtiorow["pankkitili3"]  =	"";
			$yhtiorow["pankkiiban3"]  =	"";
			$yhtiorow["pankkiswift3"] =	"";
			
			$pdf->draw_text(30, 82,  t("Pankkiyhteys", $kieli),	$firstpage, $pieni);
        	$pdf->draw_text(30, 72,  $yhtiorow["pankkinimi1"]." ".$yhtiorow["pankkitili1"],	$firstpage, $norm);
        	$pdf->draw_text(217, 72, $yhtiorow["pankkinimi2"]." ".$yhtiorow["pankkitili2"],	$firstpage, $norm);
        	$pdf->draw_text(404, 72, $yhtiorow["pankkinimi3"]." ".$yhtiorow["pankkitili3"],	$firstpage, $norm);
		}
		else {
		    $pdf->draw_text(30, 82,  t("Pankkiyhteys", $kieli),	$firstpage, $pieni);
        	$pdf->draw_text(30, 72,  $yhtiorow["pankkinimi1"]." ".$yhtiorow["pankkitili1"],	$firstpage, $norm);
        	$pdf->draw_text(217, 72, $yhtiorow["pankkinimi2"]." ".$yhtiorow["pankkitili2"],	$firstpage, $norm);
        	$pdf->draw_text(404, 72, $yhtiorow["pankkinimi3"]." ".$yhtiorow["pankkitili3"],	$firstpage, $norm);
        }
		
		// toimipaikat tiedot
		if ($laskurow["yhtio_ovttunnus"] != "") {

			// haetaan toimipaikan tiedot
			$alhqur = "select * from yhtion_toimipaikat where yhtio='$kukarow[yhtio]' and vat_numero='$laskurow[yhtio_ovttunnus]'";
			$alhire = mysql_query($alhqur) or pupe_error($alhqur);

			if (mysql_num_rows($alhire) == 1) {

				// haetaan toimipaikan tiedot
				$alhiro = mysql_fetch_array($alhire);

				// fax ja puhelin ei ole laskulla
				$yhtiorow['puhelin']	= $alhiro['puhelin'];
				$yhtiorow['fax']		= $alhiro['fax'];

			}
			
			// loput tiedot otetaan laskulta
			$yhtiorow["nimi"] 		= $laskurow["yhtio_nimi"];
			$yhtiorow["osoite"] 	= $laskurow["yhtio_osoite"];
			$yhtiorow["postino"] 	= $laskurow["yhtio_postino"];
			$yhtiorow["postitp"] 	= $laskurow["yhtio_postitp"];
			$yhtiorow["maa"] 		= $laskurow["yhtio_maa"];
			$yhtiorow["kotipaikka"]	= $laskurow["yhtio_kotipaikka"];
			$vatnumero				= $laskurow["yhtio_ovttunnus"];
		}
        
		//Alimmat kolme laatikkoa, yhtiötietoja
		$pdf->draw_rectangle(70, 20, 20, 580,	$firstpage, $rectparam);
		$pdf->draw_rectangle(70, 207, 20, 580,	$firstpage, $rectparam);
		$pdf->draw_rectangle(70, 394, 20, 580,	$firstpage, $rectparam);

		$pdf->draw_text(30, 55, $yhtiorow["nimi"],		$firstpage, $pieni);
		$pdf->draw_text(30, 45, $yhtiorow["osoite"],	$firstpage, $pieni);
		$pdf->draw_text(30, 35, $yhtiorow["postino"]."  ".$yhtiorow["postitp"].", ".$yhtiorow["maa"], $firstpage, $pieni);
//		$pdf->draw_text(30, 25, "",						$firstpage, $pieni);

		$pdf->draw_text(217, 55, t("Puhelin", $kieli).":",			$firstpage, $pieni);
		$pdf->draw_text(257, 55, $yhtiorow["puhelin"],				$firstpage, $pieni);
		$pdf->draw_text(217, 45, t("Sähköposti", $kieli).":",		$firstpage, $pieni);
		$pdf->draw_text(257, 45, $yhtiorow["email"],				$firstpage, $pieni);
		$pdf->draw_text(217, 35, t("Fax", $kieli).":",				$firstpage, $pieni);
		$pdf->draw_text(257, 35, $yhtiorow["fax"],					$firstpage, $pieni);

		//jos on suomalainen yritys tehdään ytunnus nätiks
		if (strtoupper($yhtiorow["maa"])== 'FI') {
			//muutetaan ytunnus takas oikean näköseks
			$ytunpit = 8-strlen($yhtiorow["ytunnus"]);
			if ($ytunpit > 0) {
				$uytunnus = $yhtiorow["ytunnus"];
				while ($ytunpit > 0) {
					$uytunnus = "0".$uytunnus;
					$ytunpit--;
				}
			}
			else {
				$uytunnus = $yhtiorow["ytunnus"];
			}
			$uytunnus = substr($uytunnus,0,7)."-".substr($uytunnus,7,1);
		}
		else {
			$uytunnus = $yhtiorow["ytunnus"];
		}

		$pdf->draw_text(404, 55, t("Y-tunnus", $kieli).":",			$firstpage, $pieni);
		$pdf->draw_text(444, 55, $uytunnus,							$firstpage, $pieni);
		$pdf->draw_text(404, 45, t("Kotipaikka", $kieli).":",		$firstpage, $pieni);
		$pdf->draw_text(444, 45, $yhtiorow["kotipaikka"],			$firstpage, $pieni);
		$pdf->draw_text(404, 35, t("Enn.per.rek", $kieli),			$firstpage, $pieni);
		$pdf->draw_text(404, 25, t("Alv.rek", $kieli),				$firstpage, $pieni);
	}
}

if (!function_exists('alvierittely')) {
	function alvierittely ($firstpage, $kala) {
		global $pdf, $laskurow, $yhtiorow, $kukarow, $sivu, $rectparam, $norm, $pieni, $kala, $lask, $kieli, $boldi;

		if ($lask >= 29) {
			$sivu++;
			loppu($firstpage);
			$firstpage = alku();
			$kala = 540;
			$lask = 1;
			$loppumyos = 1;
		}

		$kala -=10;
		//loppusummat vain vikalle sivulle
		$pdf->draw_text(360, $kala, t("Veroton arvo yhteensä", $kieli).":", 	$firstpage, $norm);
		$pdf->draw_text(470, $kala, $laskurow["arvo"], 			$firstpage, $norm);
		$pdf->draw_text(530, $kala, $laskurow["valkoodi"], 		$firstpage, $norm);

		$alvquery = "	SELECT distinct alv
						FROM tilausrivi
						WHERE uusiotunnus = '$laskurow[tunnus]' and yhtio='$kukarow[yhtio]'
						ORDER BY alv";
		$alvresult = mysql_query($alvquery) or pupe_error($alvquery);
		$kala -= 10;

		while ($alvrow = mysql_fetch_array($alvresult)) {
			$aquery = "	SELECT round(sum(rivihinta*(alv/100)),2) alvrivihinta, sum(rivihinta) rivihinta
						FROM tilausrivi
						WHERE uusiotunnus = '$laskurow[tunnus]' and yhtio='$kukarow[yhtio]' and alv='$alvrow[alv]'
						ORDER BY alv";
			$aresult = mysql_query($aquery) or pupe_error($aquery);
			$arow = mysql_fetch_array($aresult);

			$pdf->draw_text(360, $kala, t("Alv", $kieli).":",	$firstpage, $norm);
			$pdf->draw_text(470, $kala, $arow["alvrivihinta"], $firstpage, $norm);
			$pdf->draw_text(530, $kala, $alvrow["alv"]."%", $firstpage, $norm);
			$kala -= 10;
		}

		$pdf->draw_text(360, $kala, t("Lasku yhteensä", $kieli).":",	$firstpage, $norm);
		$pdf->draw_text(470, $kala, $laskurow["summa"], 				$firstpage, $boldi);
		$pdf->draw_text(530, $kala, $laskurow["valkoodi"], 				$firstpage, $boldi);
		$kala -= 10;

		if ($loppumyos == 1) {
			loppu($firstpage);
		}
	}
}

?>
