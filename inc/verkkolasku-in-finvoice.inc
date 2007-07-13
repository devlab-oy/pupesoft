<?php

//echo "käsitellään saapuva Finvoice BuyerPartyDetails\n".print_r($xml->xpath('BuyerPartyDetails'));
$xyhtio 				= $xml->xpath('BuyerPartyDetails/BuyerOrganisationUnitNumber');		 			// 	Tätä kovin harvoin täällä aineistossa välitetään yritetään nyt kuitenkin..
if($xyhtio[0] == "") { 																					/* 	tehdään oletus ovttunnus ytunnuksen pohjalta */
	$xyhtio	= $xml->xpath('BuyerPartyDetails/BuyerPartyIdentifier'); 									// 	ytynnus väliviivalla
	if($xyhtio[0] == "") $xml->xpath('BuyerPartyDetails/BuyerOrganisationTaxCode'); 					// tämä voisi löytyö myös täältä
	$ytunnus = str_replace("-","",$xyhtio[0]);
	$xyhtio[0] = "0037".$ytunnus."00001";
	unset($ytunnus);
}
$xverkkotunnus_vas 		= array("");																	//	Tätä ei oikein tartteta finvoicessa, toivottavasti löytyy oikea yhtio ilmankin..
$xlaskun_tyyppi 		= $xml->xpath('InvoiceDetails/InvoiceTypeCode');						
if($xlaskun_tyyppi[0] == "INV02") $xlaskun_tyyppi[0] = "381";											/*	Hyvityslaskut pitää merkata hyvitykseksi jotta ne osataan käsitellä oikein */
$xlaskunro 				= $xml->xpath('InvoiceDetails/InvoiceNumber');	
$xlaskun_ebid[0] 		= "/".basename($file);								//	Nämä tallennetaan levylle joten tämä on tiedoston nimi
$xlaskun_paiva			= $xml->xpath('EpiDetails/EpiIdentificationDetails/EpiDate');
$xlaskun_erapaiva		= $xml->xpath('EpiDetails/EpiPaymentInstructionDetails/EpiDateOptionDate');
if($xlaskun_erapaiva[0] == "")	{																		//	Tämä voi löytyä myös maksatustiedoista
	$xlaskun_erapaiva		= $xml->xpath('PaymentTermsDetails/InvoiceDueDate');
}
$xlaskuttajan_ovt		= $xml->xpath('SellerPartyDetails/SellerOrganisationUnitNumber');
if($xlaskuttajan_ovt[0] == "") { 																		/* 	tehdään oletus ovttunnus ytunnuksen pohjalta */
	$xlaskuttajan_ovt	= $xml->xpath('SellerPartyDetails/SellerPartyIdentifier'); 						// 	ytynnus väliviivalla
	if($xlaskuttajan_ovt[0] == "") $xlaskuttajan_ovt = $xml->xpath('SellerPartyDetails/SellerOrganisationTaxCode'); // tämä voisi löytyö myös täältä
	$ytunnus = $xlaskuttajan_ovt[0];	
	$xlaskuttajan_ovt[0] = "0037".str_replace("-","",$ytunnus)."00001";
	unset($ytunnus);
}
$xlaskuttaja			= $xml->xpath('SellerPartyDetails/SellerOrganisationName');
$xlaskun_pankkiviite	= $xml->xpath('EpiDetails/EpiIdentificationDetails/EpiReference');
if($xlaskun_pankkiviite[0] == "") {																		/*	Vaihtoehtoinen paikka mistä tieto voidaan löytää */
	$xlaskun_pankkiviite	= $xml->xpath('InvoiceDetails/SellerReferenceIdentifier');
}
$xlaskun_summa_eur		= $xml->xpath('EpiDetails/EpiPaymentInstructionDetails/EpiInstructedAmount');
$xlaskun_asiakastunnus	= $xyhtio [0];																	//	tämä taitaa olla sama kuin tuo ytunnus?

//echo "käsitellään saapuva Finvoice BuyerPartyDetails\n".print_r($xml->xpath('InvoiceRow'));
$tuotetiedot = $xml->xpath('InvoiceRow');

//echo "Tuotetiedot löydetty! Niitä on " . sizeof($tuotetiedot) . "\n";
$i = 0;
$xtuoteno	= array();
$tuoteno	= array();
$xrsumma	= array();
$rsumma		= array();
$xvat		= array();
$vat 		= array();
$xrinfo 	= array();
$info 		= array();
$xrkpl 		= array();
$rkpl 		= array();
$xrivino 	= array();
$rrivino	= array();


if (sizeof($tuotetiedot) > 0) {
	foreach ($tuotetiedot as $tuotetieto) {
		$xtuoteno[$i]	= $tuotetieto->xpath('ArticleIdentifier');
		$tuoteno[$i]	= $xtuoteno[$i][0];
		if($tuoteno[$i] != "") {																			/*	Meillä voi olla pelkkä "kommenttirivi", näitä on turha käsitellä */
			// veroton rivihinta
			$xrsumma[$i]	= $tuotetieto->xpath('RowAmount');
			$rsumma[$i]		= $xrsumma[$i][0];
			$xvat[$i]		= $tuotetieto->xpath('RowVatRatePercent');
			if (is_array($xvat[$i])) $vat[$i] = $xvat[$i][0]; // Näin voimme pohtia oliko täällä tuo segmentti
			$xrinfo[$i] 	= $tuotetieto-> xpath('RowFreeText');
			if(is_array($xrinfo[$i])) {							//	Meillä on MONTA selitettä niputetaan yhteen..
				foreach($xrinfo[$i] as $xln) {
					$rinfo[$i] .= " / $xln";
				}
			}
			else {
				$rinfo[$i] = $xrinfo[$i][0];
			}		
			// laskutettu määrä
			$xrkpl[$i] 		= $tuotetieto-> xpath('DeliveredQuantity');
			$rkpl[$i] 		= $xrkpl[$i][0];
			$xrivino[$i] 	= array("");
			$rrivino[$i]	= $xrivino[$i][0];			
		}
		else {
			//	Poistetaan tämä tietue	
			unset($tuoteno[$i]);
			$i--;
		}
		$i++;
	}
}

$yhtio 					= $xyhtio[0];
$verkkotunnus_vas 		= $xverkkotunnus_vas[0];
$laskun_tyyppi 			= $xlaskun_tyyppi[0];
$laskun_numero 			= $xlaskunro[0];
$laskun_ebid 			= $xlaskun_ebid[0];
$laskun_paiva			= $xlaskun_paiva[0];
$laskun_erapaiva		= $xlaskun_erapaiva[0];
$laskuttajan_ovt		= $xlaskuttajan_ovt[0];
$laskuttajan_nimi		= $xlaskuttaja[0];
$laskun_pankkiviite		= $xlaskun_pankkiviite[0];
$laskun_summa_eur		= (float) $xlaskun_summa_eur[0];
$laskun_asiakastunnus	= $xlaskun_asiakastunnus[0];

$lisavat = array();
$xlisavat = array();
$i = 0;

$vattiedot = $xml->xpath('InvoiceDetails/VatSpecificationDetails');
if (sizeof($vattiedot) > 0) {
	foreach ($vattiedot as $vattieto) {
		$xlisavat[$i]	= $vattieto->xpath('VatRatePercent');
		$lisavat[$i]	= $xlisavat[$i][0];
		echo "ALV on $lisavat[$i]!\n";
		$i++;
	}
}

?>