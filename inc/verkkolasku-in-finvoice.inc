<?php

$xyhtio = $xml->xpath('BuyerPartyDetails/BuyerOrganisationUnitNumber');

if (!is_array($xyhtio) or $xyhtio[0] == "") {
	$xyhtio	= $xml->xpath('BuyerPartyDetails/BuyerPartyIdentifier');
}
if (!is_array($xyhtio) or $xyhtio[0] == "") {
	$xyhtio	= $xml->xpath('BuyerPartyDetails/BuyerOrganisationTaxCode');
}

$xverkkotunnus_vas		= array("");
$xlaskun_tyyppi 		= $xml->xpath('InvoiceDetails/InvoiceTypeCode');
$xlaskunro 				= $xml->xpath('InvoiceDetails/InvoiceNumber');
$xlaskun_ebid 			= array("");
$xlaskun_paiva			= $xml->xpath('EpiDetails/EpiIdentificationDetails/EpiDate');
$xlaskun_erapaiva		= $xml->xpath('EpiDetails/EpiPaymentInstructionDetails/EpiDateOptionDate');

if (!is_array($xlaskun_erapaiva) or $xlaskun_erapaiva[0] == "") {
	$xlaskun_erapaiva 	= $xml->xpath('PaymentTermsDetails/InvoiceDueDate');
}

$xlaskuttajan_ovt		= $xml->xpath('SellerPartyDetails/SellerOrganisationUnitNumber');
$xlaskuttaja			= $xml->xpath('SellerPartyDetails/SellerOrganisationName');
$xlaskuttajan_vat		= $xml->xpath('SellerPartyDetails/SellerOrganisationTaxCode');
$xlaskun_pankkiviite	= $xml->xpath('EpiDetails/EpiIdentificationDetails/EpiReference');

if (!is_array($xlaskun_pankkiviite) or $xlaskun_pankkiviite[0] == "") {
	$xlaskun_pankkiviite = $xml->xpath('InvoiceDetails/SellerReferenceIdentifier');
}

$xlaskun_asiakastunnus	= $xyhtio [0]; // tämä taitaa olla sama kuin tuo ytunnus?
$xlaskun_summa_eur		= $xml->xpath('EpiDetails/EpiPaymentInstructionDetails/EpiInstructedAmount');

if (!is_array($xlaskun_summa_eur) or $xlaskun_summa_eur[0] == "") {
	$xlaskun_summa_eur	= $xml->xpath('InvoiceDetails/InvoiceTotalVatIncludedAmount');
}

// Varmistetaan, että tunnus on ovtunnus muodossa
$xyhtio[0] = preg_replace("/[^0-9]/", "", $xyhtio[0]);

if (strpos($xyhtio[0], "0037") === FALSE) {
	$xyhtio[0] = "0037".$xyhtio[0];
}

if ($xlaskun_tyyppi[0] == "INV02") {
	// Hyvityslaskut pitää merkata hyvitykseksi jotta ne osataan käsitellä oikein
	$xlaskun_tyyppi[0] = "381";
}

// Nämä tallennetaan levylle joten tämä on tiedoston nimi
$xlaskun_ebid[0] = "FINVOICEXML";

// Käsitellään tuoterivit
$tuotetiedot = $xml->xpath('InvoiceRow');

//echo "Tuotetiedot löydetty! Niitä on " . sizeof($tuotetiedot) . "\n";
$i 			= 0;
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
$xriviviite = array();
$riviviite	= array();
$xriviaika 	= array();
$riviaika	= array();

if (count($tuotetiedot) > 0) {
	foreach ($tuotetiedot as $tuotetieto) {
		// tuotekoodi
		$xtuoteno[$i]	= $tuotetieto->xpath('ArticleIdentifier');
		$tuoteno[$i]	= $xtuoteno[$i][0];

		if ($tuoteno[$i] != "") {
			// veroton rivihinta
			$xrsumma[$i]	= $tuotetieto->xpath('RowAmount');
			$rsumma[$i]		= (float) str_replace(",", ".", $xrsumma[$i][0]);

			$xvat[$i]		= $tuotetieto->xpath('RowVatRatePercent');
			$vat[$i] 		= (float) str_replace(",", ".", $xvat[$i][0]);

			// rivikommentti
			$xrinfo[$i] 	= $tuotetieto-> xpath('RowFreeText');

			if (is_array($xrinfo[$i])) {
				foreach($xrinfo[$i] as $xln) {
					$rinfo[$i] .= $xln."\n";
				}
			}
			else {
				$rinfo[$i] = $xrinfo[$i][0];
			}

			$rinfo[$i] 		= trim($rinfo[$i]);

			// laskutettu määrä
			$xrkpl[$i] 		= $tuotetieto->xpath('DeliveredQuantity');
			$rkpl[$i] 		= (float) str_replace(",", ".", $xrkpl[$i][0]);

			$xrivino[$i] 	= $tuotetieto->xpath('RowIdentifier');
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
$laskuttajan_vat		= $xlaskuttajan_vat[0];
$laskun_pankkiviite		= $xlaskun_pankkiviite[0];
$laskun_summa_eur		= (float) str_replace(",", ".", $xlaskun_summa_eur[0]);
$laskun_pankkiviite 	= $xlaskun_pankkiviite[0];
$laskun_asiakastunnus	= $xlaskun_asiakastunnus[0];

$lisavat = array();
$xlisavat = array();
$i = 0;

$vattiedot = $xml->xpath('InvoiceDetails/VatSpecificationDetails');

if (sizeof($vattiedot) > 0) {
	foreach ($vattiedot as $vattieto) {
		$xlisavat[$i]	= $vattieto->xpath('VatRatePercent');
		$lisavat[$i]	= $xlisavat[$i][0];
		//echo "ALV on $lisavat[$i]!\n";
		$i++;
	}
}

?>