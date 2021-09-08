<?php

if (!function_exists("xml_float")) {
  function xml_float($value) {
    $value = (string) $value;

    if (!PUPE_UNICODE and mb_detect_encoding($value, 'UTF-8', true) !== FALSE) {
      $value = utf8_decode($value);
    }

    return pupesoft_cleannumber($value);
  }
}

if (!function_exists("xml_string")) {
  function xml_string($value) {
    $value = (string) $value;

    if (!PUPE_UNICODE and mb_detect_encoding($value, 'UTF-8', true) !== FALSE) {
      $value = utf8_decode($value);
    }

    return pupesoft_cleanstring($value);
  }
}

$laskuformaatti = "FINVOICE";
$laskun_ebid = "FINVOICEXML";

if (strpos($file, "maventa") !== false) {
  $laskun_ebid = $ebid = "MAVENTAXML";
}
if ($fitek_xml_cron_tiedot) {
  $fitek_yhtio = $fitek_xml_cron_tiedot['yhtio'];
  $laskun_numero = xml_string($xml->InvoiceDetails->InvoiceNumber);
  $query_tarkista_lasku = "SELECT laskunro 
                            FROM lasku 
                            WHERE yhtio = '{$fitek_yhtio}' 
                            AND tila         IN ('H','M','P','Q','Y') 
                            AND tapvm        >= date_sub(now(), INTERVAL 12 MONTH)
                            AND laskunro = '{$laskun_numero}'";
  $duplikaattilasku_check_fitek = pupe_query($query_tarkista_lasku);
  if (mysql_num_rows($duplikaattilasku_check_fitek) > 0) {
    $fitek_return_false = true;
    return;
  }
}
// parseroidaan xml
$yhtio = xml_string($xml->BuyerOrganisationUnitNumber);
$verkkotunnus_vas = xml_string($xml->BuyerPartyDetails->BuyerOrganisationTaxCode);
$verkkotunnus_vas_inv = xml_string($xml->InvoiceRecipientPartyDetails->InvoiceRecipientOrganisationTaxCode);
$laskun_tyyppi = xml_string($xml->InvoiceDetails->InvoiceTypeCode);
$laskun_tyyppi_teksti = xml_string($xml->InvoiceDetails->InvoiceTypeText);
$laskun_numero = xml_string($xml->InvoiceDetails->InvoiceNumber);
$laskun_tapvm = xml_string($xml->InvoiceDetails->InvoiceDate);
$laskun_lapvm = xml_string($xml->InvoiceDetails->InvoiceDate);
$laskun_erapaiva = xml_string($xml->EpiDetails->EpiPaymentInstructionDetails->EpiDateOptionDate);
$laskun_kapvm = "";
$laskun_kasumma = "";
$laskuttajan_ovt = xml_string($xml->SellerOrganisationUnitNumber);
$laskuttajan_nimi = xml_string($xml->SellerPartyDetails->SellerOrganisationName);
$laskuttajan_vat = xml_string($xml->SellerPartyDetails->SellerOrganisationTaxCode);
$laskuttajan_identifier = xml_string($xml->SellerPartyDetails->SellerPartyIdentifier);
$laskuttajan_valkoodi = xml_string($xml->EpiDetails->EpiPaymentInstructionDetails->EpiInstructedAmount->attributes()->AmountCurrencyIdentifier);

if ($fitek_xml_cron_tiedot) {
  $laskun_pankkiviite = xml_string($xml->InvoiceDetails->BuyerReferenceIdentifier);
}
else {
  $laskun_pankkiviite = xml_string($xml->EpiDetails->EpiPaymentInstructionDetails->EpiRemittanceInfoIdentifier);
}
$laskun_asiakastunnus = xml_string($xml->BuyerPartyDetails->BuyerPartyIdentifier);
$laskun_summa_eur = xml_float($xml->EpiDetails->EpiPaymentInstructionDetails->EpiInstructedAmount);
$laskun_tilausviite = xml_string($xml->InvoiceDetails->AgreementIdentifier);
$laskun_asiakkaan_tilausnumero = xml_string($xml->InvoiceDetails->OrderIdentifier);
$kauttalaskutus = xml_string($xml->DeliveryDetails->TerminalAddressText);
$laskun_toimitunnus = xml_string($xml->SellerPartyDetails->SellerPupesoftId);

$toim_asiakkaantiedot = array();
$toim_asiakkaantiedot["toim_ovttunnus"] = xml_string($xml->DeliveryOrganisationUnitNumber);
$toim_asiakkaantiedot["ytunnus"] = xml_string($xml->DeliveryPartyDetails->DeliveryPartyIdentifier);
$toim_asiakkaantiedot["nimi"] = xml_string($xml->DeliveryPartyDetails->DeliveryOrganisationName);
$toim_asiakkaantiedot["osoite"] = xml_string($xml->DeliveryPartyDetails->DeliveryPostalAddressDetails->DeliveryStreetName);
$toim_asiakkaantiedot["postino"] = xml_string($xml->DeliveryPartyDetails->DeliveryPostalAddressDetails->DeliveryPostCodeIdentifier);
$toim_asiakkaantiedot["postitp"] = xml_string($xml->DeliveryPartyDetails->DeliveryPostalAddressDetails->DeliveryTownName);

$ostaja_asiakkaantiedot = array();
$ostaja_asiakkaantiedot["toim_ovttunnus"] = xml_string($xml->BuyerOrganisationUnitNumber);
$ostaja_asiakkaantiedot["ytunnus"] = xml_string($xml->BuyerPartyDetails->BuyerPartyIdentifier);
$ostaja_asiakkaantiedot["nimi"] = xml_string($xml->BuyerPartyDetails->BuyerOrganisationName);
$ostaja_asiakkaantiedot["osoite"] = xml_string($xml->BuyerPartyDetails->BuyerPostalAddressDetails->BuyerStreetName);
$ostaja_asiakkaantiedot["postino"] = xml_string($xml->BuyerPartyDetails->BuyerPostalAddressDetails->BuyerPostCodeIdentifier);
$ostaja_asiakkaantiedot["postitp"] = xml_string($xml->BuyerPartyDetails->BuyerPostalAddressDetails->BuyerTownName);

if ($laskuttajan_vat == "" and $laskuttajan_identifier != "") {
  $laskuttajan_vat = $laskuttajan_identifier;
}

if ($verkkotunnus_vas == "" and $verkkotunnus_vas_inv != "") {
  $verkkotunnus_vas = $verkkotunnus_vas_inv;
}

$laskuttajan_toimittajanumero = "";

if (strpos($file, "maventa") !== false and $laskuttajan_identifier != "") {
  $laskuttajan_toimittajanumero = trim(str_replace("-", "", $laskuttajan_identifier));
}

if ($yhtio == "") {
  $yhtio = xml_string($xml->BuyerPartyDetails->BuyerPartyIdentifier);
}

if ($yhtio == "") {
  $yhtio = xml_string($xml->BuyerPartyDetails->BuyerOrganisationTaxCode);
}

//FITEK XML CRON poikkeus laskun pvm
if ($fitek_xml_cron_tiedot) {
  $laskun_saapumisnumero = xml_string($xml->InvoiceDetails->OrderIdentifier);
}

// Onko tunnus IBAN-muodossa, jos ei niin varmistetaan, että se on ovtmuodossa
if (strlen($yhtio) != 18 or strtoupper(substr($yhtio, 0, 2)) != "FI") {
  // Varmistetaan, että tunnus on ovtunnus muodossa
  $yhtio = preg_replace("/[^0-9]/", "", $yhtio);

  if (strpos($yhtio, "0037") === false) {
    $yhtio = "0037".$yhtio;
  }
}

if ($laskun_erapaiva == "") {
  $laskun_erapaiva = xml_string($xml->PaymentTermsDetails->InvoiceDueDate);
}

if ($laskun_erapaiva == "") {
  $laskun_erapaiva = xml_string($xml->InvoiceDetails->PaymentTermsDetails->InvoiceDueDate);
}

if ($laskun_pankkiviite == "") {
  $laskun_pankkiviite = xml_string($xml->EpiDetails->EpiIdentificationDetails->EpiReference);
}

if ($laskun_pankkiviite == "") {
  $laskun_pankkiviite = xml_string($xml->InvoiceDetails->SellerReferenceIdentifier);
}

if ($laskun_summa_eur == 0) {
  $laskun_summa_eur = xml_float($xml->InvoiceDetails->InvoiceTotalVatIncludedAmount);
}

// Varmistetaan, että tunnus on ytunnus muodossa
// Finvoicessa täällä tulee vastanottajan ytunnus FI etuliitteellä
$verkkotunnus_vas = preg_replace("/[^0-9]/", "", $verkkotunnus_vas);

if ($laskun_tyyppi == "INV02") {
  // Hyvityslaskut pitää merkata hyvitykseksi jotta ne osataan käsitellä oikein
  $laskun_tyyppi = "381";
}

//Tuotetiedot
$tuotetiedot = $xml->InvoiceRow;
$i = 0;
$rtuoteno = array();
$ealvi = array("");
$lisavat = array("");
$laskun_rahtikulu = 0;
$osto_kulu = 0;
$osto_rahti = 0;
$osto_rivi_kulu = 0;
$osto_kulu_alv = 0;
$osto_rahti_alv = 0;
$osto_rivi_kulu_alv = 0;
$ei_oteta_kulua_pois = true;
$rivien_summa_yhteensa = 0;
$fitek_xml_loydetyt_kustannuspaikat = array();

if (count($tuotetiedot) > 0) {
  foreach ($tuotetiedot as $tuotetieto) {

    // Lista elementeistä prioriteettijärjestyksessä, josta tuotetieto voi riviltä löytyä
    $tuote_elementit = array(
      xml_string($tuotetieto->ArticleIdentifier),
      xml_string($tuotetieto->ArticleName),
    );

    // Otetaan eka arvo, joka ei ole FALSE (null ja empty string on false)
    $rtuoteno[$i]["tuoteno"] = current(array_filter($tuote_elementit));

    if ($rtuoteno[$i]["tuoteno"] != "") {

      $rtuoteno[$i]["nimitys"] = xml_string($tuotetieto->ArticleName);

      // fitek XML muutoksia lis�t��n array:iin, jota voidaan k�ytt�� tulevaisuudessa
      if ($fitek_xml_cron_tiedot) {
        //Tutkitaan ett� ovatko kustannuspaikat erilaiset tuotteille, muuten Array'ssa on 1 key.
        $fitek_xml_loydetyt_kustannuspaikat[xml_string($tuotetieto->Accounting->JournalEntry->CostObjective->Kaubaost)] = true;
        $fitek_xml_cron_tiedot["kustannuspaikka"] = xml_string($tuotetieto->Accounting->JournalEntry->CostObjective->Kaubaost);
        $rtuoteno[$i]["kirjanpito"]["alv"] = xml_string($tuotetieto->Accounting->JournalEntry->VatRate);
        $rtuoteno[$i]["kirjanpito"]["alv_prosentti"] = xml_string($tuotetieto->Accounting->JournalEntry->VatRate);
        $rtuoteno[$i]["kirjanpito"]["tili"] = xml_string($tuotetieto->Accounting->JournalEntry->GeneralLedger);
        if($tuotetieto->Accounting->JournalEntry->CostObjective->Kaubaost) {
          $kustannuspaikat_fitek = true;
          $rtuoteno[$i]["kirjanpito"]["kustannuspaikka"] = xml_string($tuotetieto->Accounting->JournalEntry->CostObjective->Kaubaost);
        }
      }

      // maventa-case
      $rtuoteno[$i]['ostotilausnro'] = $laskun_asiakkaan_tilausnumero;

      // Tallennetaan riville jos tämä on kauttalaskutusta
      if ($kauttalaskutus == "KAUTTALASKUTUS") {
        $rtuoteno[$i]["kauttalaskutus"] = "KAUTTALASKUTUS";
      }
      else {
        $rtuoteno[$i]["kauttalaskutus"] = "";
      }
      
      $rtuoteno[$i]["riviinfo"] = preg_replace("/ {2,}/", " " , xml_string($tuotetieto->ArticleName));

      $rtuoteno[$i]["rivihinta"] = round(xml_float($tuotetieto->RowVatExcludedAmount), 2);
      $rtuoteno[$i]["rivihinta_verolli"] = round(xml_float($tuotetieto->RowAmount), 2);

      $rivien_summa_yhteensa += $rtuoteno[$i]["rivihinta_verolli"];

      // Tämä pitää olla stringi (?!?), koska sitä käsittellään niin verkkolasku-in.incissä
      $xvati = str_replace(",", ".", $tuotetieto->RowVatRatePercent);

      $rtuoteno[$i]["alv"] = $xvati;
      $ealvi[$i] = $xvati;

      $rtuoteno[$i]["ale"] = xml_float($tuotetieto->RowDiscountPercent);
      $rtuoteno[$i]["hinta"] = round(xml_float($tuotetieto->UnitPriceAmount), 2);

      // rivikommentti (kommentti putsataan ja decoodataan kokonaisuudessaan myöhemmin)
      $xrinfo = $tuotetieto->RowFreeText;

      if (is_array($xrinfo)) {
        foreach ($xrinfo as $xln) {
          $rtuoteno[$i]["kommentti"] .= "{$xln}\n";
        }
      }
      else {
        $rtuoteno[$i]["kommentti"] = $xrinfo;
      }

      // tilausnumero putsataan ja decoodataan kokonaisuudessaan myöhemmin
      $_tilausnumero = trim($tuotetieto->RowIdentifierUrlText);

      if ($_tilausnumero != "") {
        // alkuperäinen tilausnumero rivikommenttiin mukaan
        $rtuoteno[$i]["kommentti"] = "Tilausnumero: {$_tilausnumero}\n" . $rtuoteno[$i]["kommentti"];
      }

      $rtuoteno[$i]["kommentti"] = xml_string($rtuoteno[$i]["kommentti"]);
      $rtuoteno[$i]["kpl"] = xml_float($tuotetieto->DeliveredQuantity);
      $rtuoteno[$i]["yksikko"] = xml_string($tuotetieto->DeliveredQuantity->attributes()->QuantityUnitCode);
      $rtuoteno[$i]["tilaajanrivinro"] = xml_string($tuotetieto->RowIdentifier);
      $rtuoteno[$i]["lapsi"] = "";
    }
    else {
      // Poistetaan tämä tietue
      unset($rtuoteno[$i]);
      $i--;
    }

    $i++;
  }
}

// Verotiedot
$vattiedot = $xml->InvoiceDetails->VatSpecificationDetails;
$i = 0;

$alv_talteen = xml_float($xml->InvoiceDetails->VatSpecificationDetails->VatRatePercent);

if (count($vattiedot) > 0) {
  foreach ($vattiedot as $vattieto) {
    $lisavat[$i] = xml_float($vattieto->VatRatePercent);
    $i++;
  }
}
