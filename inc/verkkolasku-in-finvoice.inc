<?php

$laskuformaatti = "FINVOICE";
$laskun_ebid    = "FINVOICEXML";

if (strpos($file, "maventa") !== false) $laskun_ebid = $ebid = "MAVENTAXML";

// parseroidaan xml
$yhtio           = $xml->BuyerOrganisationUnitNumber;
$verkkotunnus_vas    = $xml->BuyerPartyDetails->BuyerOrganisationTaxCode;
$laskun_tyyppi       = $xml->InvoiceDetails->InvoiceTypeCode;
$laskun_tyyppi_teksti  = $xml->InvoiceDetails->InvoiceTypeText;
$laskun_numero       = $xml->InvoiceDetails->InvoiceNumber;
$laskun_tapvm      = $xml->InvoiceDetails->InvoiceDate;
$laskun_lapvm      = $xml->InvoiceDetails->InvoiceDate;
$laskun_erapaiva    = $xml->EpiDetails->EpiPaymentInstructionDetails->EpiDateOptionDate;
$laskun_kapvm       = "";
$laskun_kasumma     = "";
$laskuttajan_ovt    = $xml->SellerOrganisationUnitNumber;
$laskuttajan_nimi    = $xml->SellerPartyDetails->SellerOrganisationName;
$laskuttajan_vat    = $xml->SellerPartyDetails->SellerOrganisationTaxCode;
$laskuttajan_valkoodi   = $xml->EpiDetails->EpiPaymentInstructionDetails->EpiInstructedAmount->attributes()->AmountCurrencyIdentifier;
$laskun_pankkiviite   = $xml->EpiDetails->EpiPaymentInstructionDetails->EpiRemittanceInfoIdentifier;
$laskun_asiakastunnus  = $xml->BuyerPartyDetails->BuyerPartyIdentifier;
$laskun_summa_eur    = (float) str_replace(",", ".", $xml->EpiDetails->EpiPaymentInstructionDetails->EpiInstructedAmount);
$laskun_tilausviite   = $xml->InvoiceDetails->AgreementIdentifier;
$laskun_asiakkaan_tilausnumero = $xml->InvoiceDetails->OrderIdentifier;
$kauttalaskutus     = $xml->DeliveryDetails->TerminalAddressText;
$laskun_toimitunnus    = $xml->SellerPartyDetails->SellerPupesoftId;

$toim_asiakkaantiedot = array();
$toim_asiakkaantiedot["toim_ovttunnus"] = trim($xml->DeliveryOrganisationUnitNumber);
$toim_asiakkaantiedot["ytunnus"]     = trim($xml->DeliveryPartyDetails->DeliveryPartyIdentifier);
$toim_asiakkaantiedot["nimi"]       = trim($xml->DeliveryPartyDetails->DeliveryOrganisationName);
$toim_asiakkaantiedot["osoite"]     = trim($xml->DeliveryPartyDetails->DeliveryPostalAddressDetails->DeliveryStreetName);
$toim_asiakkaantiedot["postino"]     = trim($xml->DeliveryPartyDetails->DeliveryPostalAddressDetails->DeliveryPostCodeIdentifier);
$toim_asiakkaantiedot["postitp"]     = trim($xml->DeliveryPartyDetails->DeliveryPostalAddressDetails->DeliveryTownName);

$ostaja_asiakkaantiedot = array();
$ostaja_asiakkaantiedot["toim_ovttunnus"]   = trim($xml->BuyerOrganisationUnitNumber);
$ostaja_asiakkaantiedot["ytunnus"]       = trim($xml->BuyerPartyDetails->BuyerPartyIdentifier);
$ostaja_asiakkaantiedot["nimi"]       = trim($xml->BuyerPartyDetails->BuyerOrganisationName);
$ostaja_asiakkaantiedot["osoite"]       = trim($xml->BuyerPartyDetails->BuyerPostalAddressDetails->BuyerStreetName);
$ostaja_asiakkaantiedot["postino"]       = trim($xml->BuyerPartyDetails->BuyerPostalAddressDetails->BuyerPostCodeIdentifier);
$ostaja_asiakkaantiedot["postitp"]       = trim($xml->BuyerPartyDetails->BuyerPostalAddressDetails->BuyerTownName);

if (trim($laskuttajan_vat) == "" and trim($xml->SellerPartyDetails->SellerPartyIdentifier) != "") $laskuttajan_vat = $xml->SellerPartyDetails->SellerPartyIdentifier;

$laskuttajan_toimittajanumero = "";

if (strpos($file, "maventa") !== false and trim($xml->SellerPartyDetails->SellerPartyIdentifier) != "") {
  $laskuttajan_toimittajanumero = trim(str_replace("-", "", trim($xml->SellerPartyDetails->SellerPartyIdentifier)));
}

if ($yhtio == "") {
  $yhtio = $xml->BuyerPartyDetails->BuyerPartyIdentifier;
}

if ($yhtio == "") {
  $yhtio = $xml->BuyerPartyDetails->BuyerOrganisationTaxCode;
}

// Onko tunnus IBAN-muodossa, jos ei niin varmistetaan, että se on ovtmuodossa
if (strlen($yhtio) != 18 or strtoupper(substr($yhtio,0,2)) != "FI") {
  // Varmistetaan, että tunnus on ovtunnus muodossa
  $yhtio = preg_replace("/[^0-9]/", "", $yhtio);

  if (strpos($yhtio, "0037") === FALSE) {
    $yhtio = "0037".$yhtio;
  }
}

if ($laskun_erapaiva == "") {
  $laskun_erapaiva = $xml->PaymentTermsDetails->InvoiceDueDate;
}

if ($laskun_pankkiviite == "") {
  $laskun_pankkiviite = $xml->EpiDetails->EpiIdentificationDetails->EpiReference;
}

if ($laskun_pankkiviite == "") {
  $laskun_pankkiviite = $xml->InvoiceDetails->SellerReferenceIdentifier;
}

if ($laskun_summa_eur == 0) {
  $laskun_summa_eur = (float) str_replace(",", ".", $xml->InvoiceDetails->InvoiceTotalVatIncludedAmount);
}

// Varmistetaan, että tunnus on ytunnus muodossa (Finvoicessa täällä tulee siis vastanottajan ytunnus FI etuliitteellä)
$verkkotunnus_vas = preg_replace("/[^0-9]/", "", $verkkotunnus_vas);

if ($laskun_tyyppi == "INV02") {
  // Hyvityslaskut pitää merkata hyvitykseksi jotta ne osataan käsitellä oikein
  $laskun_tyyppi = "381";
}

//Tuotetiedot
$tuotetiedot      = $xml->InvoiceRow;
$i            = 0;
$rtuoteno        = array();
$ealvi           = array("");
$lisavat         = array("");
$laskun_rahtikulu     = 0;
$osto_kulu         = 0;
$osto_rahti       = 0;
$osto_rivi_kulu     = 0;
$osto_kulu_alv       = 0;
$osto_rahti_alv     = 0;
$osto_rivi_kulu_alv   = 0;
$ei_oteta_kulua_pois  = TRUE;
$rivien_summa_yhteensa  = 0;

if (count($tuotetiedot) > 0) {
  foreach ($tuotetiedot as $tuotetieto) {
    // tuotekoodi
    $rtuoteno[$i]["tuoteno"]  = empty($tuotetieto->ArticleIdentifier) ? pupesoft_cleanstring($tuotetieto->ArticleName) : pupesoft_cleanstring($tuotetieto->ArticleIdentifier);

    if ($rtuoteno[$i]["tuoteno"] != "") {

      // maventa-case
      $rtuoteno[$i]['ostotilausnro'] = $laskun_asiakkaan_tilausnumero;

      // Tallennetaan riville jos tämä on kauttalaskutusta
      if ($kauttalaskutus == "KAUTTALASKUTUS") {
        $rtuoteno[$i]["kauttalaskutus"] = "KAUTTALASKUTUS";
      }
      else {
        $rtuoteno[$i]["kauttalaskutus"] = "";
      }

      $rtuoteno[$i]["nimitys"]       = pupesoft_cleanstring($tuotetieto->ArticleName);
      $rtuoteno[$i]["rivihinta"]       = round((float) str_replace(",", ".", $tuotetieto->RowVatExcludedAmount), 2);
      $rtuoteno[$i]["rivihinta_verolli"]  = round((float) str_replace(",", ".", $tuotetieto->RowAmount), 2);

      $rivien_summa_yhteensa += $rtuoteno[$i]["rivihinta_verolli"];

      $xvati = str_replace(",", ".", $tuotetieto->RowVatRatePercent);

      $rtuoteno[$i]["alv"] = $xvati;
      $ealvi[$i] = $xvati;

      $rtuoteno[$i]["ale"] = (float) str_replace(",", ".", $tuotetieto->RowDiscountPercent);
      $rtuoteno[$i]["hinta"] = round((float) str_replace(",", ".", $tuotetieto->UnitPriceAmount), 2);

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

      if ($tuotetieto->RowIdentifierUrlText != "") {
        $rtuoteno[$i]["kommentti"] = "Tilausnumero: ".$tuotetieto->RowIdentifierUrlText."\n".$rtuoteno[$i]["kommentti"]; // alkuperäinen tilausnumero rivikommenttiin mukaan
      }

      $rtuoteno[$i]["kommentti"] = trim(pupesoft_cleanstring($rtuoteno[$i]["kommentti"]));

      $rtuoteno[$i]["kpl"] = (float) str_replace(",", ".", $tuotetieto->DeliveredQuantity);

      $rtuoteno[$i]["yksikko"] = trim($tuotetieto->DeliveredQuantity->attributes()->QuantityUnitCode);
      $rtuoteno[$i]["tilaajanrivinro"] = trim($tuotetieto->RowIdentifier);
      $rtuoteno[$i]["lapsi"] = "";
    }
    else {
      //  Poistetaan tämä tietue
      unset($rtuoteno[$i]);
      $i--;
    }

    $i++;
  }
}

// Verotiedot
$vattiedot  = $xml->InvoiceDetails->VatSpecificationDetails;
$i       = 0;

$alv_talteen = (float) str_replace(",", ".", $xml->InvoiceDetails->VatSpecificationDetails->VatRatePercent);

if (count($vattiedot) > 0) {
  foreach ($vattiedot as $vattieto) {
    $lisavat[$i] = str_replace(",", ".", $vattieto->VatRatePercent);
    $i++;
  }
}
