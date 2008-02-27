<?php

//PDF parametrit
$Xpdf = new pdffile;

$Xpdf->set_default('margin-top', 	0);
$Xpdf->set_default('margin-bottom', 0);
$Xpdf->set_default('margin-left', 	0);
$Xpdf->set_default('margin-right', 	0);
$Xrectparam["width"] = 0.3;

$Xnorm["height"] = 10;
$Xnorm["font"] = "Times-Roman";

$Xpieni["height"] = 8;
$Xpieni["font"] = "Times-Roman";

// defaultteja
$Xkala = 540;
$Xlask = 1;
$Xsivu = 1;


//hetaan kaikki otunnukset jotka löytyvät tän uusiotunnuksen alta
$Xquery = "	SELECT distinct otunnus
			FROM tilausrivi
			WHERE tilausrivi.uusiotunnus = '$uusiotunnus' and tilausrivi.yhtio='$kukarow[yhtio]'";
$Xuresult = mysql_query($Xquery) or pupe_error($Xquery);

$Xtunnukset2 = '';
while($Xurow = mysql_fetch_array($Xuresult)) {
	$Xtunnukset2  .= "'".$Xurow['otunnus']."',";
}

$Xtunnukset2 = substr($Xtunnukset2,0,-1);

// varmistetaan vielä, että löyty tilauksia
if ($Xtunnukset2 != "") {
	
	//haetaan kollimäärä ja bruttopaino
	$Xquery = "	SELECT *
				FROM rahtikirjat
				WHERE otsikkonro in ($Xtunnukset2) and yhtio='$kukarow[yhtio]'";
	$Xrahtiresult = mysql_query($Xquery) or pupe_error($Xquery);

	$Xkilot  = 0;
	$Xkollit = 0;

	while($Xrahtirow = mysql_fetch_array($Xrahtiresult)) {
		if ($Xkilot == 0 and $Xkollit == 0) {
			$Xpakkaus = $Xrahtirow["pakkaus"];
			$Xpakkauskuvaus = $Xrahtirow["pakkauskuvaus"];
		}
		$Xkilot  += $Xrahtirow["kilot"];
		$Xkollit += $Xrahtirow["kollit"];
	}

	$Xquery = "	SELECT 
				tuote.tuoteno, 
				tullinimike.su, 
				tuote.tullinimike1, 
				tuote.tullikohtelu, 
				(SELECT alkuperamaa from tuotteen_toimittajat WHERE tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno and tuotteen_toimittajat.alkuperamaa != '$yhtiorow[maa]' LIMIT 1) alkuperamaa,
				sum(tilausrivi.rivihinta) summa, 
				sum(tilausrivi.kpl) kpl,
				round(sum((tilausrivi.rivihinta/$laskurow[summa])*$Xkilot),0) paino
				FROM tilausrivi
				JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno 
				LEFT JOIN tullinimike ON tuote.tullinimike1=tullinimike.cn and tullinimike.kieli = '$yhtiorow[kieli]'
				WHERE tilausrivi.uusiotunnus='$uusiotunnus' 
				and tilausrivi.yhtio = '$kukarow[yhtio]' 
				and tilausrivi.tyyppi = 'L'
				and tilausrivi.kpl<>0
				GROUP BY tuote.tuoteno, 
				tullinimike.su, 
				tuote.tullinimike1, 
				tuote.tullikohtelu, 
				alkuperamaa";	
	$Xriviresult = mysql_query($Xquery) or pupe_error($Xquery);

	if (mysql_num_rows($Xriviresult) < 1) {
		echo t("Yhtään tilausriviä ei löytynyt")."!<br>";
	}


	// listan functiot...
	if (!function_exists('erittely_alku')) {
		function erittely_alku () {
			global $Xpdf, $laskurow, $yhtiorow, $kukarow, $Xsivu, $Xrectparam, $Xnorm, $Xpieni, $kieli;
	
			$Xfirstpage = $Xpdf->new_page("a4");
			$Xpdf->enable('template');
			$Xtid = $Xpdf->template->create();
			$Xpdf->template->size($Xtid, 600, 830);
	
			//Otsikko
			//$Xpdf->draw_rectangle(830, 20,  800, 580, $Xfirstpage, $Xrectparam);
			$Xpdf->draw_text(30,  815, $yhtiorow["nimi"], 			$Xfirstpage);
			$Xpdf->draw_text(310, 815, t("Vientierittely", $kieli), $Xfirstpage);
		
			$Xpdf->draw_text(430, 815, t("Sivu", $kieli)." ".$Xsivu, $Xfirstpage, $Xnorm);
	
			$Xpdf->draw_text(50, 729, t("Laskutusosoite", $kieli), 	$Xfirstpage, $Xpieni);
			$Xpdf->draw_text(50, 717, $laskurow["nimi"], 			$Xfirstpage, $Xnorm);
			$Xpdf->draw_text(50, 707, $laskurow["osoite"], 			$Xfirstpage, $Xnorm);
			$Xpdf->draw_text(50, 697, $laskurow["postino"]." ".$laskurow["postitp"], $Xfirstpage, $Xnorm);
			$Xpdf->draw_text(50, 687, $laskurow["maa"], 			$Xfirstpage, $Xnorm);
		
			$Xpdf->draw_text(50, 666, t("Toimitusosoite", $kieli), 	$Xfirstpage, $Xpieni);
			$Xpdf->draw_text(50, 654, $laskurow["toim_nimi"], 		$Xfirstpage, $Xnorm);
			$Xpdf->draw_text(50, 644, $laskurow["toim_osoite"], 	$Xfirstpage, $Xnorm);
			$Xpdf->draw_text(50, 634, $laskurow["toim_postino"]." ".$laskurow["toim_postitp"], $Xfirstpage, $Xnorm);
			$Xpdf->draw_text(50, 624, $laskurow["toim_maa"], 		$Xfirstpage, $Xnorm);
		
		
			//Oikea sarake
			$Xpdf->draw_rectangle(800, 300, 779, 580, $Xfirstpage, 	$Xrectparam);
			$Xpdf->draw_rectangle(800, 420, 779, 580, $Xfirstpage, 	$Xrectparam);
			$Xpdf->draw_text(310, 792, t("Laskun pvm", $kieli), 	$Xfirstpage, $Xpieni);
			$Xpdf->draw_text(310, 782, $laskurow["tapvm"], 			$Xfirstpage, $Xnorm);
			$Xpdf->draw_text(430, 792, t("Laskun numero", $kieli), 	$Xfirstpage, $Xpieni);
			$Xpdf->draw_text(430, 782, $laskurow["laskunro"], 		$Xfirstpage, $Xnorm);
	
			$Xpdf->draw_rectangle(779, 300, 758, 580, $Xfirstpage, 	$Xrectparam);
			$Xpdf->draw_rectangle(779, 420, 758, 580, $Xfirstpage, 	$Xrectparam);
			$Xpdf->draw_text(310, 771, t("Viitteenne", $kieli), 	$Xfirstpage, $Xpieni);
			$Xpdf->draw_text(310, 761, substr($laskurow["viesti"],0,15), $Xfirstpage, $Xnorm);
			$Xpdf->draw_text(430, 771, t("Y-tunnus", $kieli), 		$Xfirstpage, $Xpieni);
			$Xpdf->draw_text(430, 761, $laskurow["ytunnus"], 		$Xfirstpage, $Xnorm);
	
			$Xpdf->draw_rectangle(758, 300, 737, 580, 				$Xfirstpage, $Xrectparam);
			$Xpdf->draw_rectangle(758, 420, 737, 580, 				$Xfirstpage, $Xrectparam);
			$Xpdf->draw_text(310, 750, t("Toimitusehto", $kieli), 	$Xfirstpage, $Xpieni);
			$Xpdf->draw_text(310, 740, $laskurow["toimitusehto"], 	$Xfirstpage, $Xnorm);
			$Xpdf->draw_text(430, 750, t("Toimituspvm", $kieli), 	$Xfirstpage, $Xpieni);
			$Xpdf->draw_text(430, 740, $laskurow["toimaika"], 		$Xfirstpage, $Xnorm);
	
			$Xpdf->draw_rectangle(737, 300, 716, 580, 				$Xfirstpage, $Xrectparam);
			$Xpdf->draw_text(310, 729, t("Toimitustapa", $kieli),	$Xfirstpage, $Xpieni);
			$Xpdf->draw_text(310, 719, $laskurow["toimitustapa"], 	$Xfirstpage, $Xnorm);
	
			$Xpdf->draw_rectangle(716, 300, 695, 580, 				$Xfirstpage, $Xrectparam);
			$Xpdf->draw_text(310, 708, t("Kommentti", $kieli), 		$Xfirstpage, $Xpieni);
			$Xpdf->draw_text(310, 698, $laskurow["viesti"], 		$Xfirstpage, $Xnorm);
	
			//rivien otsikkotiedot
			$Xpdf->draw_text(30,  572, t("Nimike", $kieli),			$Xfirstpage, $Xpieni);
			$Xpdf->draw_text(150, 572, t("Kohtelu", $kieli),		$Xfirstpage, $Xpieni);
			$Xpdf->draw_text(200, 572, t("Tuote", $kieli),			$Xfirstpage, $Xpieni);
			$Xpdf->draw_text(340, 572, t("Määrä", $kieli),			$Xfirstpage, $Xpieni);
			$Xpdf->draw_text(390, 572, t("Hinta", $kieli),			$Xfirstpage, $Xpieni);
			$Xpdf->draw_text(450, 572, t("Paino", $kieli),			$Xfirstpage, $Xpieni);
			
			if ($yhtiorow["vienti_erittelyn_tulostus"] != "I") {
				$Xpdf->draw_text(500, 572, t("Alkuperämaa", $kieli),	$Xfirstpage, $Xpieni);
			}
			
			return($Xfirstpage);
		}
	}
	if (!function_exists('erittely_rivi')) {
		function erittely_rivi ($Xfirstpage) {
			global $Xfirstpage, $yhtiorow, $Xpdf, $Xrow, $Xkala, $Xsivu, $Xlask, $Xrectparam, $Xnorm, $Xpieni, $kieli;
	
			if ($Xlask == 36) {
				$Xsivu++;
				erittely_loppu($Xfirstpage);
				$Xfirstpage = erittely_alku();
				$Xkala = 540;
				$Xlask = 1;
			}
		
			$Xpdf->draw_text(30,  $Xkala, $Xrow["tullinimike1"],	$Xfirstpage, $Xnorm);
			$Xpdf->draw_text(150, $Xkala, $Xrow["tullikohtelu"], 	$Xfirstpage, $Xnorm);
			$Xpdf->draw_text(200, $Xkala, $Xrow["tuoteno"], 		$Xfirstpage, $Xnorm);
			$Xpdf->draw_text(340, $Xkala, $Xrow["kpl"], 			$Xfirstpage, $Xnorm);
			$Xpdf->draw_text(390, $Xkala, $Xrow["summa"], 			$Xfirstpage, $Xnorm);
			$Xpdf->draw_text(450, $Xkala, $Xrow["paino"],			$Xfirstpage, $Xnorm);
			
			if ($yhtiorow["vienti_erittelyn_tulostus"] != "I") {
				$Xpdf->draw_text(500, $Xkala, $Xrow["alkuperamaa"],		$Xfirstpage, $Xnorm);
			}
			
			$Xkala = $Xkala - 13;
			$Xlask++;
		}
	}

	if (!function_exists('erittely_loppu')) {
		function erittely_loppu ($Xfirstpage) {
			global $Xpdf, $yhtiorow, $Xsivu, $Xrectparam, $Xnorm, $Xpieni, $kieli;
	
			//Alimmat kolme laatikkoa, yhtiötietoja
			$Xpdf->draw_rectangle(55, 20, 20, 580,							$Xfirstpage, $Xrectparam);
			$Xpdf->draw_rectangle(55, 207, 20, 580,							$Xfirstpage, $Xrectparam);
			$Xpdf->draw_rectangle(55, 394, 20, 580,							$Xfirstpage, $Xrectparam);
	
			$Xpdf->draw_text(30, 45, $yhtiorow["nimi"],						$Xfirstpage, $Xpieni);
			$Xpdf->draw_text(30, 35, $yhtiorow["osoite"],					$Xfirstpage, $Xpieni);
			$Xpdf->draw_text(30, 25, $yhtiorow["postino"]."  ".$yhtiorow["postitp"]."  ".$yhtiorow["maa"],	$Xfirstpage, $Xpieni);
	
			$Xpdf->draw_text(217, 45, t("Puhelin", $kieli).":",				$Xfirstpage, $Xpieni);
			$Xpdf->draw_text(247, 45, $yhtiorow["puhelin"],					$Xfirstpage, $Xpieni);
			$Xpdf->draw_text(217, 35, t("Telefax", $kieli).":",				$Xfirstpage, $Xpieni);
			$Xpdf->draw_text(247, 35, $yhtiorow["fax"],						$Xfirstpage, $Xpieni);
			$Xpdf->draw_text(217, 25, t("Sähköposti", $kieli).":",			$Xfirstpage, $Xpieni);
			$Xpdf->draw_text(247, 25, $yhtiorow["email"],					$Xfirstpage, $Xpieni);
	
			$Xpdf->draw_text(404, 45, t("Y-tunnus", $kieli).":",			$Xfirstpage, $Xpieni);
			$Xpdf->draw_text(444, 45, $yhtiorow["maa"]."-".$yhtiorow["ytunnus"],	$Xfirstpage, $Xpieni);
			$Xpdf->draw_text(404, 35, t("Kotipaikka", $kieli).":",			$Xfirstpage, $Xpieni);
			$Xpdf->draw_text(444, 35, $yhtiorow["kotipaikka"],				$Xfirstpage, $Xpieni);
		}
	}

	///* Tehdään lista *///

	$Xfirstpage = erittely_alku();

	while ($Xrow = mysql_fetch_array($Xriviresult)) {
		erittely_rivi ($Xfirstpage);
	}

	erittely_loppu ($Xfirstpage);
}
else {
	echo "<font class='error'>".t("Tilausta ei löytynyt")."!<br>$Xquery<br></font>";
}

?>