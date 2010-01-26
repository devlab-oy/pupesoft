<?php

$xyhtio = $xml->BuyerOrganisationUnitNumber;

$xasiakkaantiedot = array();
$xasiakkaantiedot["toim_ovttunnus"] = $xml->DeliveryOrganisationUnitNumber;
$xasiakkaantiedot["ytunnus"] = $xml->DeliveryPartyDetails->DeliveryPartyIdentifier;

if ($xyhtio == "") {
	$xyhtio	= $xml->BuyerPartyDetails->BuyerPartyIdentifier;
}
if ($xyhtio == "") {
	$xyhtio	= $xml->BuyerPartyDetails->BuyerOrganisationTaxCode;
}

$xverkkotunnus_vas	= $xml->BuyerPartyDetails->BuyerOrganisationTaxCode;

$xlaskun_tyyppi 		= $xml->InvoiceDetails->InvoiceTypeCode;
$xlaskunro 				= $xml->InvoiceDetails->InvoiceNumber;
$xlaskun_ebid 			= "";
$xlaskun_paiva			= $xml->EpiDetails->EpiIdentificationDetails->EpiDate;
$xlaskun_erapaiva		= $xml->EpiDetails->EpiPaymentInstructionDetails->EpiDateOptionDate;

if ($xlaskun_erapaiva == "") {
	$xlaskun_erapaiva 	= $xml->PaymentTermsDetails->InvoiceDueDate;
}

$xlaskuttajan_ovt		= $xml->SellerPartyDetails->SellerOrganisationUnitNumber;
$xlaskuttaja			= $xml->SellerPartyDetails->SellerOrganisationName;
$xlaskuttajan_vat		= $xml->SellerPartyDetails->SellerOrganisationTaxCode;
$xlaskun_pankkiviite	= $xml->EpiDetails->EpiIdentificationDetails->EpiReference;

if ($xlaskun_pankkiviite == "") {
	$xlaskun_pankkiviite = $xml->InvoiceDetails->SellerReferenceIdentifier;
}

$xlaskun_asiakastunnus	= $xyhtio; // tämä taitaa olla sama kuin tuo ytunnus?
$xlaskun_summa_eur		= $xml->EpiDetails->EpiPaymentInstructionDetails->EpiInstructedAmount;

if ($xlaskun_summa_eur == "") {
	$xlaskun_summa_eur	= $xml->InvoiceDetails->InvoiceTotalVatIncludedAmount;
}

// Varmistetaan, että tunnus on ovtunnus muodossa
$xyhtio = preg_replace("/[^0-9]/", "", $xyhtio);

// Varmistetaan, että tunnus on ytunnus muodossa (Finvoicessa täällä tulee siis vastanottajan ytunnus FI etuliitteellä)
$xverkkotunnus_vas = preg_replace("/[^0-9]/", "", $xverkkotunnus_vas);

if (strpos($xyhtio, "0037") === FALSE) {
	$xyhtio = "0037".$xyhtio;
}

if ($xlaskun_tyyppi == "INV02") {
	// Hyvityslaskut pitää merkata hyvitykseksi jotta ne osataan käsitellä oikein
	$xlaskun_tyyppi = "381";
}

// Nämä tallennetaan levylle joten tämä on tiedoston nimi
$xlaskun_ebid = "FINVOICEXML";

// Onko tämä kauttalaskutusta?
$xkauttalaskutus = $xml->DeliveryDetails->TerminalAddressText;

// Käsitellään tuoterivit
$tuotetiedot = $xml->InvoiceRow;

//echo "Tuotetiedot löydetty! Niitä on " . sizeof($tuotetiedot) . "\n";
$i 			= 0;
$rtuoteno	= array();

if (count($tuotetiedot) > 0) {
	foreach ($tuotetiedot as $tuotetieto) {
		// tuotekoodi
		$rtuoteno[$i]["tuoteno"] = utf8_decode($tuotetieto->ArticleIdentifier);

		if ($rtuoteno[$i]["tuoteno"] != "") {

			// Tallennetaan riville jos tämä on kauttalaskutusta
			if ($xkauttalaskutus == "KAUTTALASKUTUS") {
				$rtuoteno[$i]["kauttalaskutus"] = "KAUTTALASKUTUS";
			}

			$rtuoteno[$i]["nimitys"] = utf8_decode($tuotetieto->ArticleName);
			$rtuoteno[$i]["rivihinta"] = (float) str_replace(",", ".", $tuotetieto->RowVatExcludedAmount);
			$rtuoteno[$i]["alv"] = (float) str_replace(",", ".", $tuotetieto->RowVatRatePercent);
			$rtuoteno[$i]["ale"] = (float) str_replace(",", ".", $tuotetieto->RowDiscountPercent);
			$rtuoteno[$i]["hinta"] = (float) str_replace(",", ".", $tuotetieto->UnitPriceAmount);

			if ($xkauttalaskutus != "KAUTTALASKUTUS") {
				// rivikommentti
				$xrinfo = $tuotetieto->RowFreeText;

				if (is_array($xrinfo)) {
					foreach ($xrinfo as $xln) {
						$rtuoteno[$i]["kommentti"] .= $xln."\n";
					}
				}
				else {
					$rtuoteno[$i]["kommentti"] = $xrinfo;
				}

				$rtuoteno[$i]["kommentti"] = trim($rtuoteno[$i]["kommentti"]);
			}

			$rtuoteno[$i]["kpl"] = (float) str_replace(",", ".", $tuotetieto->DeliveredQuantity);
			$rtuoteno[$i]["yksikko"] = $tuotetieto->DeliveredQuantity->attributes()->QuantityUnitCode;
			$rtuoteno[$i]["tilaajanrivinro"] = $tuotetieto->RowIdentifier;
		}
		else {
			//	Poistetaan tämä tietue
			unset($rtuoteno[$i]);
			$i--;
		}
		$i++;
	}
}

$yhtio 					= $xyhtio;
$verkkotunnus_vas 		= $xverkkotunnus_vas;
$laskun_tyyppi 			= $xlaskun_tyyppi;
$laskun_numero 			= $xlaskunro;
$laskun_ebid 			= $xlaskun_ebid;
$laskun_paiva			= $xlaskun_paiva;
$laskun_erapaiva		= $xlaskun_erapaiva;
$laskuttajan_ovt		= $xlaskuttajan_ovt;
$laskuttajan_nimi		= $xlaskuttaja;
$laskuttajan_vat		= $xlaskuttajan_vat;
$laskun_pankkiviite		= $xlaskun_pankkiviite;
$laskun_summa_eur		= (float) str_replace(",", ".", $xlaskun_summa_eur);
$laskun_pankkiviite 	= $xlaskun_pankkiviite;
$laskun_asiakastunnus	= $xlaskun_asiakastunnus;

$lisavat = array();
$xlisavat = array();
$i = 0;

$vattiedot = $xml->InvoiceDetails->VatSpecificationDetails;

if (sizeof($vattiedot) > 0) {
	foreach ($vattiedot as $vattieto) {
		$xlisavat[$i]	= $vattieto->VatRatePercent;
		$lisavat[$i]	= $xlisavat[$i];
		$i++;
	}
}

?>