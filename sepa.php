<?php

/*
Tehty ja validoitu käyttäen speksejä:

URL: http://www.iso20022.org/catalogue_of_unifi_messages.page
Msg ID: pain.001.001.02
Message Name: CustomerCreditTransferInitiationV02
*/

function sepa_header() {
  global $xml, $pain, $yhtiorow;

  $xmlstr  = '<?xml version="1.0" encoding="UTF-8"?>';
  $xmlstr .= '<Document ';
  $xmlstr .= 'xmlns="urn:iso:std:iso:20022:tech:xsd:pain.001.001.02" ';
  $xmlstr .= 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ';
  $xmlstr .= 'xsi:schemaLocation="urn:iso:std:iso:20022:tech:xsd:pain.001.001.02 pain.001.001.02.xsd">';
  $xmlstr .= '</Document>';

  $xml = new SimpleXMLElement($xmlstr);

  $pain = $xml->addChild('pain.001.001.02');
  $GrpHdr = $pain->addChild('GrpHdr');                                                  // GroupHeader
  $GrpHdr->addChild('MsgId', date('Y-m-d')."T".date('H:i:s'));                                    // MessageIdentification, Text, Pakollinen kenttä
  $GrpHdr->addChild('CreDtTm', date('Y-m-d')."T".date('H:i:s'));                                    // CreationDateTime, DateTime, Pakollinen kenttä
  // $GrpHdr->addChild('Authstn', '');                                                  // Authorisation
  $GrpHdr->addChild('BtchBookg', 'true');                                                // BatchBooking, Indicator
  $GrpHdr->addChild('NbOfTxs', 0);                                                  // NumberOfTransactions, Text, Pakollinen kenttä
  // $GrpHdr->addChild('CtrlSum', '');                                                  // ControlSum, Quantity
  $GrpHdr->addChild('Grpg', 'MIXD');                                                  // Grouping, Pakollinen kenttä
  $InitgPty = $GrpHdr->addChild('InitgPty', '');                                            // InitiatingParty, Pakollinen
  $InitgPty->addChild('Nm', sprintf("%-1.70s", $yhtiorow['nimi']));                                // Name 1-70
  $PstlAdr = $InitgPty->addChild('PstlAdr', '');                                          // PostalAddress
  // $PstlAdr->addChild('AdrTp', '');
  $PstlAdr->addChild('AdrLine', sprintf("%-1.70s", $yhtiorow['osoite']));                            // AddressLine
  $PstlAdr->addChild('AdrLine', sprintf("%-1.70s", $yhtiorow['maa']."-".$yhtiorow['postino']." ".$yhtiorow['postitp']));
  $PstlAdr->addChild('StrtNm', sprintf("%-1.70s", $yhtiorow['osoite']));                            // StreetName
  // $PstlAdr->addChild('BldgNb', '');
  $PstlAdr->addChild('PstCd', sprintf("%-1.16s", $yhtiorow['maa']."-".$yhtiorow['postino']));                  // PostCode
  $PstlAdr->addChild('TwnNm', sprintf("%-1.35s", $yhtiorow['postitp']));                            // TownName
  // $PstlAdr->addChild('CtrySubDvsn', '');
  $PstlAdr->addChild('Ctry', sprintf("%-1.2s", $yhtiorow['maa']));                              // Country
  // $Id = $InitgPty->addChild('Id', '');
  // $OrgId = $Id->addChild('OrgId', '');
  // $OrgId->addChild('BIC', '');
  // $OrgId->addChild('IBEI', '');
  // $OrgId->addChild('BEI', '');
  // $OrgId->addChild('EANGLN', '');
  // $OrgId->addChild('USCHU', '');
  // $OrgId->addChild('DUNS', '');
  // $OrgId->addChild('BkPtyId', '');
  // $OrgId->addChild('TaxIdNb', '');
  // $PrtryId = $OrgId->addChild('PrtryId', '');
  // $PrtryId->addChild('Id', '');
  // $PrtryId->addChild('Issr', '');
}

function sepa_paymentinfo($laskurow) {
  global $xml, $pain, $PmtInf, $yhtiorow;

  $PmtInf = $pain->addChild('PmtInf');                                                // PaymentInformation
  $PmtInfId = $PmtInf->addChild('PmtInfId', $laskurow['tunnus']);                                  // PaymentInformationIdentification, Pakollinen kenttä
  $PmtMtd = $PmtInf->addChild('PmtMtd', 'TRF');                                           // PaymentMethod, Pakollinen kenttä (TRF = transfer)

  if ($laskurow["sepa"] === 'SEPA') {
    $PmtTpInf = $PmtInf->addChild('PmtTpInf');                                            // Jos SEPA maa, laitetaan nämä segmentit mukaan
    // $InstrPrty = $PmtTpInf->addChild('InstrPrty');
    $SvcLvl = $PmtTpInf->addChild('SvcLvl');
    $SvcLvl->addChild('Cd', 'SEPA');
  }
  // $LclInstrm = $PmtTpInf->addChild('LclInstrm');
  // $LclInstrm->addChild('Cd', '');
  // $CtgyPurp = $PmtTpInf->addChild('CtgyPurp');
  $ReqdExctnDt = $PmtInf->addChild('ReqdExctnDt', $laskurow['olmapvm']);                              // RequestedExecutionDate, Pakollinen kenttä
  // $PoolgAdjstmntDt = $PmtInf->addChild('PoolgAdjstmntDt');

  $Dbtr = $PmtInf->addChild('Dbtr');                                                // Debtor
  $Dbtr->addChild('Nm', sprintf("%-1.70s", $yhtiorow['nimi']));                                // Name
  $PstlAdr = $Dbtr->addChild('PstlAdr');                                            // PostalAddress
  // $PstlAdr->addChild('AdrTp', '');
  $PstlAdr->addChild('AdrLine', sprintf("%-1.70s", $yhtiorow['osoite']));                          // AddressLine
  $PstlAdr->addChild('AdrLine', sprintf("%-1.70s", $yhtiorow['maa']."-".$yhtiorow['postino']." ".$yhtiorow['postitp']));
  // $PstlAdr->addChild('StrtNm', '');
  // $PstlAdr->addChild('BldgNb', '');
  // $PstlAdr->addChild('PstCd', $yhtiorow['maa']."-".$yhtiorow['postino']);
  // $PstlAdr->addChild('TwnNm', $yhtiorow['postitp']);
  // $PstlAdr->addChild('CtrySubDvsn', '');
  $PstlAdr->addChild('Ctry', sprintf("%-2.2s", $yhtiorow['maa']));                            // Country
  $Id = $Dbtr->addChild('Id');                                                // Identification
  $OrgId = $Id->addChild('OrgId');                                            // OrganisationIdentification
  // $OrgId->addChild('BIC', '');
  // $OrgId->addChild('IBEI', '');
  // $OrgId->addChild('BEI', '');
  // $OrgId->addChild('EANGLN', '');
  // $OrgId->addChild('USCHU', '');
  // $OrgId->addChild('DUNS', '');
  if ($laskurow["yriti_asiakastunnus"] != "0" and $laskurow["yriti_asiakastunnus"] != "") {
    $OrgId->addChild('BkPtyId', $laskurow["yriti_asiakastunnus"]);                            // BankPartyIdentification, Pakollinen kenttä (service code given by Nordea)
  }
  else {
    $OrgId->addChild('BkPtyId', $yhtiorow['ytunnus']);                                  // BankPartyIdentification, Pakollinen kenttä (service code given by Nordea)
  }
  // $OrgId->addChild('TaxIdNb', '');
  // $PrtryId = $OrgId->addChild('PrtryId', '');
  // $PrtryId->addChild('Id', '');
  // $PrtryId->addChild('Issr', '');
  $DbtrAcct = $PmtInf->addChild('DbtrAcct');                                            // DebtorAccount
  $Id = $DbtrAcct->addChild('Id');                                              // Identification
  $Id->addChild('IBAN', $laskurow['yriti_iban']);                                      // IBAN, Pakollinen kenttä
  // $DbtrAcct->addChild('Ccy');
  // $DbtrAcct->addChild('Nm');
  $DbtrAgt = $PmtInf->addChild('DbtrAgt');                                            // DebtorAgent
  $FinInstnId  = $DbtrAgt->addChild('FinInstnId');                                        // FinancialInstitutionIdentification
  $FinInstnId->addChild('BIC', $laskurow['yriti_bic']);                                  // BIC, Pakollinen kenttä

  // $UltmtDbtr = $PmtInf->addChild('UltmtDbtr');                                          // UltimateDebtor (käytetään vain jos eri kuin Debtor)
  // $UltmtDbtr->addChild('Nm');                                                  // Name
  // $PstlAdr = $UltmtDbtr->addChild('PstlAdr');                                          // PostalAddress
  // $PstlAdr->addChild('AdrTp', '');
  // $PstlAdr->addChild('AdrLine', '');                                              // AddressLine
  // $PstlAdr->addChild('StrtNm', '');
  // $PstlAdr->addChild('BldgNb', '');
  // $PstlAdr->addChild('PstCd', $yhtiorow['maa']."-".$yhtiorow['postino']);
  // $PstlAdr->addChild('TwnNm', $yhtiorow['postitp']);
  // $PstlAdr->addChild('CtrySubDvsn', '');
  // $PstlAdr->addChild('Ctry', $yhtiorow['maa']);                                        // Country
  // $Id = $UltmtDbtr->addChild('Id');                                              // Identification
  // $OrgId = $Id->addChild('OrgId');                                              // OrganisationIdentification
  // $OrgId->addChild('BIC', '');
  // $OrgId->addChild('IBEI', '');
  // $OrgId->addChild('BEI', '');
  // $OrgId->addChild('EANGLN', '');
  // $OrgId->addChild('USCHU', '');
  // $OrgId->addChild('DUNS', '');
  // $OrgId->addChild('BkPtyId', $yhtiorow['ytunnus']);                                    // BankPartyIdentification, Pakollinen kenttä (service code given by Nordea)
  // $OrgId->addChild('TaxIdNb', '');
  // $PrtryId = $OrgId->addChild('PrtryId', '');
  // $PrtryId->addChild('Id', '');
  // $PrtryId->addChild('Issr', '');
  // $UltmtDbtr->addChild('CtryOfRes');

  $ChrgBr = $PmtInf->addChild('ChrgBr', 'SLEV');                                          // ChargeBearer (SLEV = shared charges)
  // HUOM: CdtTrfTxInf -segmentille, on oma funktio -> sepa_credittransfer
}

function sepa_credittransfer($laskurow, $popvm_nyt, $netotetut_rivit = '') {
  global $xml, $pain, $PmtInf, $yhtiorow, $kukarow;

  // HUOM: Tämä kuuluu PmtInf -segmentin sisään!
  $CdtTrfTxInf = $PmtInf->addChild('CdtTrfTxInf', '');                    // CreditTransferTransaction Information
  $PmtId = $CdtTrfTxInf->addChild('PmtId', '');                      // PaymentIdentification
  $InstrId = $PmtId->addChild('InstrId', "{$laskurow['tunnus']}-".preg_replace("/[^0-9]/", "", $popvm_nyt));      // Instruction Id
  $EndToEndId = $PmtId->addChild('EndToEndId', "{$laskurow['tunnus']}-".preg_replace("/[^0-9]/", "", $popvm_nyt));  // EndToEndIdentification, Pakollinen kenttä

  $PmtTpInf = $CdtTrfTxInf->addChild('PmtTpInf', '');                    // PaymentTypeInformation
  // $InstrPrty = $PmtTpInf->addChild('InstrPrty', '');
  // $SvcLvl = $PmtTpInf->addChild('SvcLvl', '');                    // ServiceLevel
  // $Prtry = $SvcLvl->addChild('Prtry', '');                    // Proprietary Nordea (URGP = Urgent payment)
  // $Cd = $SvcLvl->addChild('Cd', 'SEPA');
  // $LclInstrm = $PmtTpInf->addChild('LclInstrm', '');
  // $Cd = $LclInstrm->addChild('Cd', '');
  // $CtgyPurp = $PmtTpInf->addChild('CtgyPurp', '');                  // CategoryPurpose (INTC = Intercompany eli Nordea -> Nordea)

  $Amt = $CdtTrfTxInf->addChild('Amt', '');                        // Amount

  if ($laskurow['alatila'] != 'K') {
    $InstdAmt = $Amt->addChild('InstdAmt', sprintf("%.02f", round($laskurow['summa'], 2)));              // InstructedAmount, Pakollinen kenttä
  }
  else {
    $InstdAmt = $Amt->addChild('InstdAmt', sprintf("%.02f", round($laskurow['summa'] - $laskurow['kasumma'], 2)));  // InstructedAmount, Pakollinen kenttä
  }
  $InstdAmt->addAttribute('Ccy', $laskurow['valkoodi']);                  // Currency, Pakollinen attribute

  // $XchgRateInf = $CdtTrfTxInf->addChild('XchgRateInf', '');                // ExchangeRateInformation
  //   $XchgRate = $XchgRateInf->addChild('XchgRate', '');
  //   $RateTp = $XchgRateInf->addChild('RateTp', '');
  //   $CtrctId = $XchgRateInf->addChild('CtrctId', '');                  // ContractIdentification (FX trade reference provided the FX rate is agreed in advance)
  // $ChrgBr = $CdtTrfTxInf->addChild('ChrgBr', 'SLEV');
  // $ChqInstr = $CdtTrfTxInf->addChild('ChqInstr', '');
  //   $ChqTp = $ChqInstr->addChild('ChqTp', '');
  //   $DlvryMtd = $ChqInstr->addChild('DlvryMtd', '');
  //     $Cd = $DlvryMtd->addChild('Cd', '');
  // $UltmtDbtr = $CdtTrfTxInf->addChild('UltmtDbtr', '');                  // UltimateDebtor (käytetään vain jos eri kuin Debtor)
  //   $Nm = $UltmtDbtr->addChild('Nm', '');                        // Name
  //   $PstlAdr = $UltmtDbtr->addChild('PstlAdr', '');
  //     $AdrTp = $PstlAdr->addChild('AdrTp', '');
  //     $AdrLine = $PstlAdr->addChild('AdrLine', '');
  //     $StrtNm = $PstlAdr->addChild('StrtNm', '');
  //     $BldgNb = $PstlAdr->addChild('BldgNb', '');
  //     $PstCd = $PstlAdr->addChild('PstCd', '');
  //     $TwnNm = $PstlAdr->addChild('TwnNm', '');
  //     $CtrySubDvsn = $PstlAdr->addChild('CtrySubDvsn', '');
  //     $Ctry = $PstlAdr->addChild('Ctry', '');
  //   $Id = $UltmtDbtr->addChild('Id', '');
  //     $OrgId = $Id->addChild('OrgId', '');
  //       $BIC = $OrgId->addChild('BIC', '');
  //       $IBEI = $OrgId->addChild('IBEI', '');
  //       $BEI = $OrgId->addChild('BEI', '');
  //       $EANGLN = $OrgId->addChild('EANGLN', '');
  //       $USCHU = $OrgId->addChild('USCHU', '');
  //       $DUNS = $OrgId->addChild('DUNS', '');
  //       $BkPtyId = $OrgId->addChild('BkPtyId', '');
  //       $TaxIdNb = $OrgId->addChild('TaxIdNb', '');
  //       $PrtryId = $OrgId->addChild('PrtryId', '');
  //         $Id = $PrtryId->addChild('Id', '');
  //         $Issr = $PrtryId->addChild('Issr', '');
  //   $CtryOfRes = $UltmtDbtr->addChild('CtryOfRes', '');
  // $IntrmyAgt1 = $CdtTrfTxInf->addChild('IntrmyAgt1', '');
  //   $FinInstnId = $IntrmyAgt1->addChild('FinInstnId', '');
  //     $BIC = $FinInstnId->addChild('BIC', '');
  // $IntrmyAgt1Acct = $CdtTrfTxInf->addChild('IntrmyAgt1Acct', '');
  //   $Id = $IntrmyAgt1Acct->addChild('Id', '');
  //     $IBAN = $Id->addChild('IBAN', '');
  //   $Ccy = $IntrmyAgt1Acct->addChild('Ccy', '');
  //   $Nm = $IntrmyAgt1Acct->addChild('Nm', '');
  // $IntrmyAgt2 = $CdtTrfTxInf->addChild('IntrmyAgt2', '');
  //   $FinInstnId = $IntrmyAgt2->addChild('FinInstnId', '');
  //     $BIC = $FinInstnId->addChild('BIC', '');
  // $IntrmyAgt2Acct = $CdtTrfTxInf->addChild('IntrmyAgt2Acct', '');
  //   $Id = $IntrmyAgt2Acct->addChild('Id', '');
  //     $IBAN = $Id->addChild('IBAN', '');
  //   $Ccy = $IntrmyAgt2Acct->addChild('Ccy', '');
  //   $Nm = $IntrmyAgt2Acct->addChild('Nm', '');

  $CdtrAgt = $CdtTrfTxInf->addChild('CdtrAgt', '');                        // CreditorAgent
  $FinInstnId = $CdtrAgt->addChild('FinInstnId', '');                      // FinancialInstitutionIdentification
  $BIC = $FinInstnId->addChild('BIC', $laskurow['swift']);                // BIC

  if ($laskurow['clearing'] != '') {
    $CmbndId = $FinInstnId->addChild('CmbndId', '');                    // CombinedIdentification
    $ClrSysMmbId = $CmbndId->addChild('ClrSysMmbId', '');                // ClearingSystemMemberIdentification
    $ClrSysMmbId->addChild('Id', sprintf("%-1.35s", $laskurow['clearing']));       // Identification
    $Nm = $CmbndId->addChild('Nm', sprintf("%-1.70s", $laskurow['pankki1']));         // Name (Bank's)
    $PstlAdr = $CmbndId->addChild('PstlAdr', '');                    // Postal Address
    $PstlAdr->addChild('CtrySubDvsn', sprintf("%-1.35s", $laskurow['pankki2']));  // CountrySubDivision
    $PstlAdr->addChild('Ctry', '');                          // Country
  }

  // $CdtrAgtAcct = $CdtTrfTxInf->addChild('CdtrAgtAcct', '');
  //   $Id = $CdtrAgtAcct->addChild('Id', '');
  //     $IBAN = $Id->addChild('IBAN', '');
  //   $Ccy = $CdtrAgtAcct->addChild('Ccy', '');
  //   $Nm = $CdtrAgtAcct->addChild('Nm', '');

  $Cdtr = $CdtTrfTxInf->addChild('Cdtr', '');                                                  // Creditor

  // jos pankkihaltijan nimi on syötetty, laitetaan se nimen tilalle
  if (trim($laskurow['pankki_haltija']) != '') {
    $Nm = $Cdtr->addChild('Nm', sprintf("%-1.70s", str_replace("&", "&amp;", $laskurow['pankki_haltija'])));                    // Name, Pakollinen kenttä 1-70
  }
  else {
    $Nm = $Cdtr->addChild('Nm', sprintf("%-1.70s", str_replace("&", "&amp;", trim($laskurow['nimi']." ".$laskurow['nimitark']))));                        // Name, Pakollinen kenttä 1-70
  }
  $PstlAdr = $Cdtr->addChild('PstlAdr', '');                                                // PostalAddress
  // $AdrTp = $PstlAdr->addChild('AdrTp', '');

  if ($laskurow['yriti_bic'] == 'HELSFIHH') {
    // Aktian kohdalla ei laiteta mitään jos tietoja puuttuu. Sama juttu erottimessa.
    $_osoite  = trim($laskurow['osoite'])  == '' ? ''  : $laskurow['osoite'];
    $_postino = trim($laskurow['postino']) == '' ? ''  : $laskurow['postino'];
    $_postitp = trim($laskurow['postitp']) == '' ? ''  : $laskurow['postitp'];
    $_erotin = "";
  }
  else {
    // Danske hylkää (joskus) aineistot, jos on vaan space, laitetaan tyhjässä tapauksessa defaultteja
    $_osoite  = trim($laskurow['osoite'])  == '' ? '-'  : $laskurow['osoite'];
    $_postino = trim($laskurow['postino']) == '' ? '-'  : $laskurow['postino'];
    $_postitp = trim($laskurow['postitp']) == '' ? '-'  : $laskurow['postitp'];
    $_erotin = "-";
  }

  $_maa = trim($laskurow['maa']) == '' ? 'FI' : $laskurow['maa'];

  $AdrLine = $PstlAdr->addChild('AdrLine', sprintf("%-1.70s", $_osoite)); // AddressLine 1-70
  $AdrLine = $PstlAdr->addChild('AdrLine', sprintf("%-1.70s", "{$_maa}{$_erotin}{$_postino}{$_erotin}{$_postitp}"));
  $StrtNm = $PstlAdr->addChild('StrtNm', sprintf("%-1.70s", $_osoite)); // StreetName 1-70
  // $BldgNb = $PstlAdr->addChild('BldgNb', ''); // BuildingNumber
  $PstCd = $PstlAdr->addChild('PstCd', sprintf("%-1.16s", "{$_maa}{$_erotin}{$_postino}")); // PostCode 1-16
  $TwnNm = $PstlAdr->addChild('TwnNm', sprintf("%-1.35s", $_postitp)); // TownName 1-35
  // $CtrySubDvsn = $PstlAdr->addChild('CtrySubDvsn', '');
  $Ctry = $PstlAdr->addChild('Ctry', sprintf("%-2.2s", $_maa)); // Country

  // $Id = $Cdtr->addChild('Id', '');
  //   $OrgId = $Id->addChild('OrgId', '');
  //     $BIC = $OrgId->addChild('BIC', '');
  //     $IBEI = $OrgId->addChild('IBEI', '');
  //     $BEI = $OrgId->addChild('BEI', '');
  //     $EANGLN = $OrgId->addChild('EANGLN', '');
  //     $USCHU = $OrgId->addChild('USCHU', '');
  //     $DUNS = $OrgId->addChild('DUNS', '');
  //     $BkPtyId = $OrgId->addChild('BkPtyId', $laskurow['ytunnus']);
  //     $TaxIdNb = $OrgId->addChild('TaxIdNb', '');
  //     $PrtryId = $OrgId->addChild('PrtryId', '');
  //       $Id = $PrtryId->addChild('Id', '');
  //       $Issr = $PrtryId->addChild('Issr', '');

  $CtryOfRes = $Cdtr->addChild('CtryOfRes', sprintf("%-2.2s", $_maa));
  $CdtrAcct = $CdtTrfTxInf->addChild('CdtrAcct', '');                  // CreditorAccount
  $Id = $CdtrAcct->addChild('Id', '');                      // Identification
  if (tarkista_sepa($laskurow["iban_maa"]) !== FALSE) {
    $Id->addChild('IBAN', $laskurow['ultilno']);              // IBAN = kotimaa, BBAN = ulkomaa, Pakollinen tieto
  }
  else {
    $Id->addChild('BBAN', $laskurow['ultilno']);              // IBAN = kotimaa, BBAN = ulkomaa, Pakollinen tieto
  }
  //   $Ccy = $CdtrAcct->addChild('Ccy', '');
  //   $Nm = $CdtrAcct->addChild('Nm', '');
  // $UltmtCdtr = $CdtTrfTxInf->addChild('UltmtCdtr', '');
  //   $Nm = $UltmtCdtr->addChild('Nm', '');
  //   $PstlAdr = $UltmtCdtr->addChild('PstlAdr', '');
  //     $AdrTp = $PstlAdr->addChild('AdrTp', '');
  //     $AdrLine = $PstlAdr->addChild('AdrLine', '');
  //     $StrtNm = $PstlAdr->addChild('StrtNm', '');
  //     $BldgNb = $PstlAdr->addChild('BldgNb', '');
  //     $PstCd = $PstlAdr->addChild('PstCd', '');
  //     $TwnNm = $PstlAdr->addChild('TwnNm', '');
  //     $CtrySubDvsn = $PstlAdr->addChild('CtrySubDvsn', '');
  //     $Ctry = $PstlAdr->addChild('Ctry', '');
  //   $Id = $UltmtCdtr->addChild('Id', '');
  //     $OrgId = $Id->addChild('OrgId', '');
  //       $BIC = $OrgId->addChild('BIC', '');
  //       $IBEI = $OrgId->addChild('IBEI', '');
  //       $BEI = $OrgId->addChild('BEI', '');
  //       $EANGLN = $OrgId->addChild('EANGLN', '');
  //       $USCHU = $OrgId->addChild('USCHU', '');
  //       $DUNS = $OrgId->addChild('DUNS', '');
  //       $BkPtyId = $OrgId->addChild('BkPtyId', '');
  //       $TaxIdNb = $OrgId->addChild('TaxIdNb', '');
  //       $PrtryId = $OrgId->addChild('PrtryId', '');
  //         $Id = $PrtryId->addChild('Id', '');
  //         $Issr = $PrtryId->addChild('Issr', '');
  //   $CtryOfRes = $UltmtCdtr->addChild('CtryOfRes', '');
  // $InstrForDbtrAgt = $CdtTrfTxInf->addChild('InstrForDbtrAgt', '');
  // $Purp = $CdtTrfTxInf->addChild('Purp', '');
  //   $Cd = $Purp->addChild('Cd', '');
  // $RgltryRptg = $CdtTrfTxInf->addChild('RgltryRptg', '');
  //   $DbtCdtRptgInd = $RgltryRptg->addChild('DbtCdtRptgInd', '');
  //   $Authrty = $RgltryRptg->addChild('Authrty', '');
  //     $AuthrtyNm = $Authrty->addChild('AuthrtyNm', '');
  //     $AuthrtyCtry = $Authrty->addChild('AuthrtyCtry', '');
  //   $RgltryDtls = $RgltryRptg->addChild('RgltryDtls', '');
  //     $Cd = $RgltryDtls->addChild('Cd', '');
  //     $Amt = $RgltryDtls->addChild('Amt', '');
  //     $Inf = $RgltryDtls->addChild('Inf', '');
  $RmtInf = $CdtTrfTxInf->addChild('RmtInf', '');                            // RemittanceInformation

  if ($yhtiorow['maa'] == 'EE' and strlen(trim($laskurow["laskunro"])) > 0 and $laskurow['viesti'] != "") {
    $reference_number_and_message = "/RFB/".$laskurow['laskunro']."/TXT/".$laskurow['viesti'];
    $Ustrd = $RmtInf->addChild('Ustrd', sprintf("%-1.140s", $reference_number_and_message));          // Unstructured (max 140 char)
  }
  else {
    if (strlen(trim($laskurow["viite"])) > 0 and $laskurow["tilaustyyppi"] == 'M') {             // Matkalaskuilla viite viestiksi sanomalle
      $Ustrd = $RmtInf->addChild('Ustrd', sprintf("%-1.140s", $laskurow['viite']));          // Unstructured (max 140 char)
    }
    elseif (strlen(trim($laskurow["viite"])) > 0) {
      $Strd = $RmtInf->addChild('Strd', '');                              // Structured (Max 9 occurrences)
      // $RfrdDocInf = $Strd->addChild('RfrdDocInf', '');                      // ReferredDocumentInformation
      //   $RfrdDocTp = $RfrdDocInf->addChild('RfrdDocTp', '');
      //     $Cd = $RfrdDocTp->addChild('Cd', '');
      //   $RfrdDocNb = $RfrdDocInf->addChild('RfrdDocNb', '');
      // $RfrdDocRltdDt = $Strd->addChild('RfrdDocRltdDt', '');
      // $RfrdDocAmt = $Strd->addChild('RfrdDocAmt', '');
      //   $RmtdAmt = $RfrdDocAmt->addChild('RmtdAmt', '');
      $CdtrRefInf = $Strd->addChild('CdtrRefInf', '');                      // CreditorReferenceInformation
      $CdtrRefTp = $CdtrRefInf->addChild('CdtrRefTp', '');                  // CreditorReferenceType
      $Cd = $CdtrRefTp->addChild('Cd', 'SCOR');                      // Code (SCOR = Structured Communication Reference)
      $CdtrRef = $CdtrRefInf->addChild('CdtrRef', sprintf("%-1.35s", $laskurow['viite']));  // CreditorReference
      // $AddtlRmtInf = $Strd->addChild('AddtlRmtInf', '');
    }
    elseif ($laskurow['viesti'] != "") {
      $Ustrd = $RmtInf->addChild('Ustrd', sprintf("%-1.140s", $laskurow['viesti']));          // Unstructured (max 140 char)
    }
  }

  // jos tämä muuttuja on setattu, on tämä ko. lasku/tapahtuma netotettu näistä tunnuksista!
  if ($netotetut_rivit != "") {

    $query = "SELECT *
              FROM lasku
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus  in ($netotetut_rivit)";
    $result = pupe_query($query);

    while ($nettorow = mysql_fetch_assoc($result)) {

      // Jos laskunumero on syötetty, lisätään se viestiin mukaan
      if ($nettorow['laskunro'] != 0 and $nettorow['laskunro'] != $nettorow['viesti']) {
        $nettorow['viesti'] = (trim($nettorow['viesti']) == "") ? $nettorow['laskunro'] : trim($nettorow['viesti']." ".$nettorow['laskunro']);
      }

      if ($nettorow["summa"] < 0) {
        $code = "CREN";  // hyvityslasku
      }
      else {
        $code = "CINV";  // veloituslasku
      }

      $Strd = $RmtInf->addChild('Strd', '');                                 // Structured (Max 9 occurrences)

      $RfrdDocInf = $Strd->addChild('RfrdDocInf', '');                         // ReferredDocumentInformation
      $RfrdDocTp = $RfrdDocInf->addChild('RfrdDocTp', '');                     // ReferredDocumentType
      $Cd = $RfrdDocTp->addChild('Cd', $code);                         // Code, Pakollinen tieto (CINV = Commercial invoice, CREN = Credit note)
      // $RfrdDocNb = $RfrdDocInf->addChild('RfrdDocNb', '');
      // $RfrdDocRltdDt = $Strd->addChild('RfrdDocRltdDt', '');
      $RfrdDocAmt = $Strd->addChild('RfrdDocAmt', '');                         // ReferredDocumentAmount

      if ($nettorow["summa"] < 0) {
        $RmtdAmt = $RfrdDocAmt->addChild('CdtNoteAmt', sprintf("%.02f", abs($nettorow["summa"])));      // CreditNoteAmount
      }
      else {
        if ($nettorow['alatila'] != 'K') {
          $RmtdAmt = $RfrdDocAmt->addChild('RmtdAmt', sprintf("%.02f", round($nettorow["summa"], 2)));             // RemittedAmount
        }
        else {
          $RmtdAmt = $RfrdDocAmt->addChild('RmtdAmt', sprintf("%.02f",round($nettorow["summa"] - $nettorow['kasumma'], 2)));             // RemittedAmount
        }
      }

      $RmtdAmt->addAttribute('Ccy', $nettorow['valkoodi']);                     // Attribute Currency

      if (strlen(trim($nettorow["viite"])) > 0) {
        $CdtrRefInf = $Strd->addChild('CdtrRefInf', '');                         // CreditorReferenceInformation
        $CdtrRefTp = $CdtrRefInf->addChild('CdtrRefTp', '');                     // CreditorReferenceType
        $Cd = $CdtrRefTp->addChild('Cd', 'SCOR');                         // Code (SCOR = Structured Communication Reference)
        $CdtrRef = $CdtrRefInf->addChild('CdtrRef', sprintf("%-1.35s", $nettorow['viite']));  // CreditorReference
      }
      elseif ($nettorow["viesti"] != "") {
        $AddtlRmtInf = $Strd->addChild('AddtlRmtInf', sprintf("%-1.140s", $nettorow['viesti']));  // AdditionalRemittanceInformation
      }
    }
  }
}

if (isset($_POST["tee"])) {
  if ($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto = 1;
  if ($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
}

require "inc/parametrit.inc";
require "inc/pankkiyhteys_functions.inc";

$tee = empty($tee) ? '' : $tee;

// Onko maksuaineistoille annettu salasanat.php:ssä oma polku jonne tallennetaan
if ($tee == "KIRJOITAKOPIO" || isset($vanhatee) && $vanhatee == "KIRJOITAKOPIO") {
  $pankkitiedostot_polku = "/tmp";
}
elseif (!empty($maksuaineiston_siirto[$kukarow["yhtio"]]["local_dir"])) {
  $pankkitiedostot_polku = trim($maksuaineiston_siirto[$kukarow["yhtio"]]["local_dir"]);
}
elseif (!empty($pankkitiedostot_polku) != "") {
  $pankkitiedostot_polku = trim($pankkitiedostot_polku);
}
else {
  $pankkitiedostot_polku = $pupe_root_polku."/dataout";
}

// Varmistetaan, että loppuu kauttaviivaan
$pankkitiedostot_polku = rtrim($pankkitiedostot_polku, '/').'/';

if ($tee == "lataa_tiedosto") {
  if (isset($pankkifilenimi)) {
    readfile($pankkitiedostot_polku.basename($pankkifilenimi));
  }
  elseif (isset($tmpfilenimi)) {
    readfile("/tmp/".basename($tmpfilenimi));
  }

  exit;
}

echo "<font class='head'>".t("SEPA-maksuaineisto")."</font><hr>";

if (!is_writable($pankkitiedostot_polku)) {
  virhe("Pankkitiedostopolku virheellinen!");
  $tee = "";
}

// Pankkiyhteys oikeellisuustarkastukset
if ($tee == "laheta_pankkiin") {
  if (empty($salasana)) {
    virhe("Salasana täytyy antaa!");
    $tee = "virhe";
  }
  elseif (!hae_pankkiyhteys_ja_pura_salaus($pankkiyhteys_tunnus, $salasana)) {
    virhe("Antamasi salasana on väärä!");
    $tee = "virhe";
  }

  if (empty($pankkiyhteys_tunnus)) {
    virhe("Pankkiyhteystunnus katosi!");
    $tee = "virhe";
  }

  $pankkiyhteys_tiedosto_full = "{$pankkitiedostot_polku}{$pankkiyhteys_tiedosto}";

  if (!is_readable($pankkiyhteys_tiedosto_full)) {
    virhe("Maksuaineisto ei ole luettavissa!");
    $tee = "virhe";
  }
}

// Pankkiyhteys tiedoston lähetys
if ($tee == "laheta_pankkiin") {
  $_xml = file_get_contents($pankkiyhteys_tiedosto_full);
  $_data = base64_encode($_xml);

  $params = array(
    "pankkiyhteys_tunnus"   => $pankkiyhteys_tunnus,
    "pankkiyhteys_salasana" => $salasana,
    "file_type"             => "NDCORPAYS",
    "maksuaineisto"         => $_data,
  );

  $vastaus = sepa_upload_file($params);

  if ($vastaus) {
    viesti("Maksuaineisto lähetetty, vastaus pankista:");

    echo "<br/>";
    echo "<table>";
    echo "<tbody>";
    foreach ($vastaus as $key => $value) {
      echo "<tr>";
      echo "<td>{$key}</td>";
      echo "<td>{$value}</td>";
      echo "</tr>";
    }
    echo "</tbody>";
    echo "</table>";
    echo "<br/><br/>";

    // Nollataan tämä, niin ei näytetä lähetyskäyttöliittymää uudestaan!
    $pankkiyhteys_tiedosto = "";
  }

  $tee = "";
}

$pankkitili_tunnus = empty($pankkitili_tunnus) ? 0 : (int) $pankkitili_tunnus;

// Jos halutaan tiedosto per pankki per päivä
if ($yhtiorow["pankkitiedostot"] == "E") {
  echo "<font class='error'>";
  echo t("SEPA-aineston voi luoda ainoastaan per pankki tai kaikki pankit yhteen tiedostoon.");
  echo "<br>";
  echo t("Tarkista pankkitiedostot -yhtiön parametrti.");
  echo "</font>";

  $tee = "virhe";
}

if ($tee == "KIRJOITAKOPIO") {
  $lisa = " and lasku.tunnus in ($poimitut_laskut) ";
}
else {
  $lisa = " and lasku.tila = 'P' and lasku.maksaja = '$kukarow[kuka]' ";
}

// Haetaan kaiki SEPA-maat
$sepa_maat_array = tarkista_sepa('', 'K');
$sepamaat        = "'".implode("','", $sepa_maat_array)."'";

// Haetaan poimitut maksut (HUOM: sama selecti alempana!!!!)
$haku_query = "SELECT lasku.*,
               if(lasku.ultilno_maa != '', lasku.ultilno_maa, lasku.maa) iban_maa,
               if((lasku.ultilno_maa != ''
                 AND lasku.ultilno_maa in ($sepamaat))
                 OR lasku.maa          in ($sepamaat), 'SEPA', '') sepa,
               yriti.iban yriti_iban,
               yriti.bic yriti_bic,
               yriti.asiakastunnus yriti_asiakastunnus,
               yriti.tunnus AS yriti_tunnus,
               date_format(lasku.popvm, '%d.%m.%y.%H.%i.%s') popvm_dmy
               FROM lasku
               INNER JOIN valuu ON (valuu.yhtio = lasku.yhtio
                AND valuu.nimi         = lasku.valkoodi)
               INNER JOIN yriti ON (yriti.yhtio = lasku.yhtio
                AND yriti.tunnus       = lasku.maksu_tili
                AND yriti.kaytossa     = '')
               WHERE lasku.yhtio       = '{$kukarow["yhtio"]}'
               {$lisa}
               ORDER BY maksu_tili, olmapvm, sepa, ultilno";
$result = pupe_query($haku_query);

if ($tee == "") {

  $_num = mysql_num_rows($result);

  echo "<br>";
  echo "<font class='message'>".t("Sinulla on")." {$_num} ".t("laskua poimittuna").".</font>";
  echo "<br><br>";

  // Jos meillä on pankkiyhteys käytössä, niin pitää hakea pankkitilin tunnus
  if (SEPA_PANKKIYHTEYS and $_num > 0) {
    $_temp = mysql_fetch_assoc($result);
    $pankkitili_tunnus = $_temp['yriti_tunnus'];
    mysql_data_seek($result, 0);
  }

  $virheita = 0;

  while ($laskurow = mysql_fetch_assoc($result)) {

    // Tehdään oikeellisuustarkastuksia
    if (tarkista_iban($laskurow["ultilno"]) != $laskurow["ultilno"] and tarkista_sepa($laskurow["iban_maa"]) !== FALSE) {
      echo "<font class='error'>Laskun tilinumero ei ole oikeellinen IBAN tilinumero, laskua ei voida lisätä aineistoon! $laskurow[nimi] ($laskurow[summa] $laskurow[valkoodi]) $laskurow[ultilno]</font><br>";
      $virheita++;
      continue;
    }
    elseif (tarkista_bban($laskurow["ultilno"]) === FALSE) {
      echo "<font class='error'>Laskun tilinumero ei ole oikeellinen BBAN tilinumero, laskua ei voida lisätä aineistoon! $laskurow[nimi] ($laskurow[summa] $laskurow[valkoodi]) $laskurow[ultilno]</font><br>";
      $virheita++;
      continue;
    }

    if ($laskurow["ultilno"] == "") {
      echo "<font class='error'>Laskulta puuttuu tilinumero, laskua ei voida lisätä aineistoon! $laskurow[nimi] ($laskurow[summa] $laskurow[valkoodi]) </font><br>";
      $virheita++;
      continue;
    }

    if (tarkista_iban($laskurow["yriti_iban"]) == "") {
      echo "<font class='error'>Yrityksen pankkitili $laskurow[yriti_iban] ei ole oikeellinen IBAN tilinumero, laskua ei voida lisätä aineistoon! $laskurow[nimi] ($laskurow[summa] $laskurow[valkoodi]) </font><br>";
      $virheita++;
      continue;
    }

    if (tarkista_bic($laskurow["yriti_bic"]) === FALSE) {
      echo "<font class='error'>Yrityksen pankkitilin $laskurow[yriti_iban] BIC on virheellinen, laskua ei voida lisätä aineistoon! $laskurow[nimi] ($laskurow[summa] $laskurow[valkoodi]) </font><br>";
      $virheita++;
      continue;
    }

    if (tarkista_bic($laskurow["swift"]) === FALSE) {
      echo "<font class='error'>Laskun BIC ei ole oikeellinen, laskua ei voida lisätä aineistoon! $laskurow[nimi] ($laskurow[summa] $laskurow[valkoodi]) $laskurow[swift]</font><br>";
      $virheita++;
      continue;
    }

    if ($laskurow["summa"] == 0) {
      echo "<font class='error'>Laskulta puuttuu summa, laskua ei voida lisätä aineistoon! $laskurow[nimi] ($laskurow[summa] $laskurow[valkoodi]) </font><br>";
      $virheita++;
      continue;
    }

  }

  if (!is_dir($pankkitiedostot_polku) or !is_writable($pankkitiedostot_polku)) {
    echo "<font class='error'>".t("Kansioissa ongelmia").": $pankkitiedostot_polku</font><br>";
    $virheita++;
  }

  if (mysql_num_rows($result) > 0 and $virheita == 0) {
    echo "<form name = 'valinta' method='post'>";
    echo "<input type = 'hidden' name = 'tee' value = 'KIRJOITA'>";
    echo "<input type = 'hidden' name = 'pankkitili_tunnus' value = '{$pankkitili_tunnus}'>";
    echo "<input type = 'submit' value = '".t("Tee maksuaineistot")."'>";
    echo "</form>";
  }
}

if ($tee == "KIRJOITA" or $tee == "KIRJOITAKOPIO") {

  if (mysql_num_rows($result) > 0) {

    $popvm_row = mysql_fetch_assoc($result);

    // uniikkia tunnusta varten popvm: aineistokopiolle alkuperäinen ja uudelle aineistolle se joka kirjoitetaan kantaan
    if ($tee == "KIRJOITAKOPIO") {
      $popvm_nyt = $popvm_row["popvm"];
      $popvm_dmy = $popvm_row["popvm_dmy"];
    }
    else {
      $popvm_nyt = date("Y-m-d H:i:s");
      $popvm_dmy = date("d.m.y.H.i.s");
    }

    // Päätetääm maksuaineston tiedostonimi
    if (strtoupper($yhtiorow['maa']) == 'EE' and substr($popvm_row['yriti_iban'], 0, 2) == "EE") {
      $kaunisnimi = "EESEPA-$kukarow[yhtio]-".$popvm_dmy.".xml";
    }
    else {
      $kaunisnimi = "SEPA-$kukarow[yhtio]-".$popvm_dmy.".xml";
    }

    $toot = fopen($pankkitiedostot_polku.$kaunisnimi, "w+");

    if (!$toot) {
      echo t("En saanut tiedostoa auki! Tarkista polku")." $pankkitiedostot_polku$kaunisnimi !";
      exit;
    }

    echo "<br>";
    echo "<table>";
    echo "<tr>";
    echo "<th>".t("Tiedosto")."</th>";
    echo "<td>$kaunisnimi</td>";
    echo "</tr>";
  }
  else {
    echo "<font class='message'>".t("Sopivia laskuja ei löydy")."</font>";
    exit;
  }

  // Alustetaan muuttujat
  $tapahtuma_maara    = 0;
  $edpvm              = "0000-00-00";
  $edsepa             = "";
  $edtili             = "";
  $netotettava_laskut = array();
  $netotettava_summa  = array();

  // Tarkistetaan ensin mahdolliset netotettavat hyvitykset
  $query = "SELECT maksu_tili, ultilno, olmapvm, valkoodi
            FROM lasku
            WHERE yhtio = '$kukarow[yhtio]'
            {$lisa}
            AND summa   < 0
            GROUP BY maksu_tili, ultilno, olmapvm, valkoodi";
  $result = pupe_query($query);

  while ($laskurow = mysql_fetch_assoc($result)) {

    // Etsitään samalle päivälle tarpeeksi veloituksia, haetaan ensin kaikki miinukset, sitten summan mukaan desc
    $query = "SELECT lasku.tunnus laskutunnus,
              if(lasku.alatila = 'K', summa - kasumma, summa) maksettavasumma
              FROM lasku
              WHERE yhtio    = '$kukarow[yhtio]'
              {$lisa}
              AND ultilno    = '$laskurow[ultilno]'
              AND valkoodi   = '$laskurow[valkoodi]'
              AND maksu_tili = '$laskurow[maksu_tili]'
              AND olmapvm    = '$laskurow[olmapvm]'
              ORDER BY if(summa < 0, 1, 2), summa DESC";
    $nettolaskures = pupe_query($query);

    // Tällä lasketaan monta laskua tarvitaan mukaan
    $nettosumma_yhteensa = 0;
    $nettolaskuja_yhteensa = 0;

    // Tänne tallennetaan laskujen tunnukset
    $nettolaskujen_tunnukset = "";

    // Loopataan laskuja läpi, kunnes päästään plussalle
    while ($nettolaskurow = mysql_fetch_assoc($nettolaskures)) {

      $nettosumma_yhteensa += $nettolaskurow["maksettavasumma"];
      $nettolaskujen_tunnukset .= "$nettolaskurow[laskutunnus],";
      $nettolaskuja_yhteensa++;

      if ($nettosumma_yhteensa > 0) {
        break;
      }
    }

    // Vika pilkku pois
    $nettolaskujen_tunnukset = substr($nettolaskujen_tunnukset, 0, -1);

    if ($nettosumma_yhteensa < 0) {

      echo "<tr>";
      echo "<th>".t("Virhe")."</th>";
      echo "<td><font class='error'>Hyvityslaskujen netotus jää miinukselle! Poimi lisää laskuja tilille $laskurow[valkoodi] $laskurow[ultilno], $laskurow[olmapvm]</font></td>";
      echo "</tr>";
      echo "</table>";

      require "inc/footer.inc";
      exit;
    }

    if ($nettolaskuja_yhteensa > 9) {

      echo "<tr>";
      echo "<th>".t("Virhe")."</th>";
      echo "<td><font class='error'>Hyvityslaskujen netotus koostuu yli yhdeksästä tapahtumasta! SEPA aineisto ei tue näin isoja netotuksia! $laskurow[valkoodi] $laskurow[ultilno], $laskurow[olmapvm]</font></td>";
      echo "</tr>";
      echo "</table>";

      require "inc/footer.inc";
      exit;
    }

    $netotettava_laskut[] = $nettolaskujen_tunnukset;
    $netotettava_summa[]  = $nettosumma_yhteensa;
  }

  // SEPA header
  sepa_header();

  // Tehdään netotetut tapahtumat
  foreach ($netotettava_laskut as $i => $tunnukset) {
    $query = "SELECT lasku.*, if(lasku.ultilno_maa != '', lasku.ultilno_maa, lasku.maa) iban_maa,
              if((lasku.ultilno_maa != ''
                AND lasku.ultilno_maa in ($sepamaat))
                OR lasku.maa          in ($sepamaat), 'SEPA', '') sepa,
              yriti.iban yriti_iban, yriti.bic yriti_bic, yriti.asiakastunnus yriti_asiakastunnus
              FROM lasku
              JOIN yriti ON (yriti.yhtio = lasku.yhtio
                AND yriti.tunnus      = lasku.maksu_tili
                AND yriti.kaytossa    = '')
              WHERE lasku.yhtio       = '$kukarow[yhtio]'
              AND lasku.tunnus        in ($tunnukset)
              LIMIT 1";
    $result = pupe_query($query);
    $nettorow = mysql_fetch_assoc($result);

    // Viesti ei saa olla tyhjä
    $nettorow["viesti"] = (trim($nettorow["viesti"]) == "") ? $nettorow["laskunro"] : $nettorow["viesti"];
    $nettorow["viesti"] = (trim($nettorow["viesti"]) == "") ? $nettorow["viite"] : $nettorow["viesti"];

    $nettorow["viite"]    = '';            // Viitenroa ei sallita netotetulla tapahtumalla
    $nettorow["alatila"]  = '';            // Ei käteisalennusta netotetulla tapahtumalla
    $nettorow["summa"]    = $netotettava_summa[$i];  // Netotettu summa

    sepa_paymentinfo($nettorow);
    sepa_credittransfer($nettorow, $popvm_nyt, $tunnukset);
    $tapahtuma_maara++;

    if ($tee == "KIRJOITA") {
      // päivitetään laskut "odottaa suoritusta" tilaan
      $query = "UPDATE lasku
                SET tila = 'Q',
                popvm       = '$popvm_nyt'
                WHERE yhtio = '$kukarow[yhtio]'
                AND tunnus  in ($tunnukset)";
      $uresult = pupe_query($query);
    }
  }

  $netotetut_laskut = implode(",", $netotettava_laskut);

  // Haetaan poimitut maksut POISLUKIEN netotetut
  if ($netotetut_laskut != "") {
    $lisa .= " and lasku.tunnus not in ($netotetut_laskut) ";
  }

  // Haetaan poimitut maksut (HUOM: sama selecti ylempänä!!!!)
  $haku_query = "SELECT lasku.*,
                 if(lasku.ultilno_maa != '', lasku.ultilno_maa, lasku.maa) iban_maa,
                 if((lasku.ultilno_maa != ''
                   AND lasku.ultilno_maa in ($sepamaat))
                   OR lasku.maa          in ($sepamaat), 'SEPA', '') sepa,
                 yriti.iban yriti_iban,
                 yriti.bic yriti_bic,
                 yriti.asiakastunnus yriti_asiakastunnus,
                 date_format(lasku.popvm, '%d.%m.%y.%H.%i.%s') popvm_dmy
                 FROM lasku
                 INNER JOIN valuu ON (valuu.yhtio = lasku.yhtio
                  AND valuu.nimi         = lasku.valkoodi)
                 INNER JOIN yriti ON (yriti.yhtio = lasku.yhtio
                  AND yriti.tunnus       = lasku.maksu_tili
                  AND yriti.kaytossa     = '')
                 WHERE lasku.yhtio       = '{$kukarow["yhtio"]}'
                 {$lisa}
                 ORDER BY maksu_tili, olmapvm, sepa, ultilno";
  $result = pupe_query($haku_query);

  while ($laskurow = mysql_fetch_assoc($result)) {

    // Jos laskunumero on syötetty, lisätään se viestiin mukaan
    if ($laskurow['laskunro'] != 0 and $laskurow['laskunro'] != $laskurow['viesti'] and $yhtiorow['maa'] != 'EE') {
      $laskurow['viesti'] = (trim($laskurow['viesti']) == "") ? $laskurow['laskunro'] : $laskurow['viesti']." ".$laskurow['laskunro'];
    }

    if ($kukarow["yhtio"] == "kiko") {
      // Tehdään erät per päivä ja sepa-maksut yhteen ja muut toiseen
      if ($edpvm != $laskurow['olmapvm'] or $edsepa != $laskurow['sepa']) {
        sepa_paymentinfo($laskurow);
        $edpvm  = $laskurow['olmapvm'];
        $edsepa = $laskurow['sepa'];
      }
    }
    else {
      if ($edpvm != $laskurow['olmapvm'] or $edtili != $laskurow['ultilno']) {
        sepa_paymentinfo($laskurow);
        $edpvm  = $laskurow['olmapvm'];
        $edtili = $laskurow['ultilno'];
      }
    }

    sepa_credittransfer($laskurow, $popvm_nyt);
    $tapahtuma_maara++;

    if ($tee == "KIRJOITA") {
      // päivitetään lasku "odottaa suoritusta" tilaan
      $query = "UPDATE lasku
                SET tila = 'Q',
                popvm       = '$popvm_nyt'
                WHERE yhtio = '$kukarow[yhtio]'
                AND tunnus  = '$laskurow[tunnus]'";
      $uresult = pupe_query($query);
    }
  }

  // Lisätään vielä oikea tapahtumien määrä sanoman headeriin
  $xml->{"pain.001.001.02"}->GrpHdr->NbOfTxs = $tapahtuma_maara;

  /* Tämä blocki piti poistaa, koska rikkoo Samlinkin. Aineistossa ei saa olla mitään jäsentelyä.
  // Kirjoitetaaan XML, tehdään tästä jäsennelty aineisto. Tämä toimii paremmin mm OPn kanssa
  $dom = new DOMDocument('1.0');
  $dom->preserveWhiteSpace = true;
  $dom->formatOutput = true;
  $dom->loadXML(str_replace(array("\n", "\r"), "", utf8_encode($xml->asXML())));
  fwrite($toot, ($dom->saveXML()));
  */

  // Kirjoitetaaan XML ja tehdään UTF8 encode
  fwrite($toot, str_replace(chr(10), "", utf8_encode($xml->asXML())));
  fclose($toot);

  // Tehdään vielä tässä vaiheessa XML validointi, vaikka ainesto onkin jo tehty. :(
  libxml_use_internal_errors(true);

  $xml_virheet = "";
  $xml_domdoc = new DomDocument;
  $xml_file = $pankkitiedostot_polku.$kaunisnimi;
  $xml_schema = "$pupe_root_polku/datain/pain.001.001.02.xsd";

  // Tämä tiedosto lähetetään pankkiin!
  $pankkiyhteys_tiedosto = $kaunisnimi;

  $xml_domdoc->Load($xml_file);

  if (!$xml_domdoc->schemaValidate($xml_schema)) {

    echo "<font class='message'>SEPA-aineistosta löytyi vielä seuraavat virheet, aineisto saattaa hylkääntyä pankissa!</font><br><br>";

    $all_errors = libxml_get_errors();

    foreach ($all_errors as $error) {
      echo "<font class='info'>$error->message</font><br>";
      $xml_virheet .= "$error->message\n";
    }

    echo "<br>";

    // Lähetetään viesti adminille!
    mail($yhtiorow['admin_email'], mb_encode_mimeheader($yhtiorow['nimi']." - SEPA Error", "ISO-8859-1", "Q"), $xml_virheet."\n", "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");
  }

  echo "<tr><th>".t("Tallenna aineisto")."</th>";
  echo "<form method='post' class='multisubmit'>";
  echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
  echo "<input type='hidden' name='kaunisnimi' value='$kaunisnimi'>";

  if ($tee == "KIRJOITAKOPIO") {
    echo "<input type='hidden' name='tmpfilenimi' value='".basename($kaunisnimi)."'>";
  }
  else {
    echo "<input type='hidden' name='pankkifilenimi' value='$kaunisnimi'>";
  }

  echo "<td><input type='submit' value='".t("Tallenna")."'></form></td>";
  echo "</tr>";
  echo "</table>";

  // Jos kaikki siirtoon tarvittavat parametrit on kunnossa, siirretään tiedosto!
  $y = $kukarow["yhtio"];
  if (isset(  $maksuaineiston_siirto[$y]["host"],
      $maksuaineiston_siirto[$y]["user"],
      $maksuaineiston_siirto[$y]["pass"],
      $maksuaineiston_siirto[$y]["path"],
      $maksuaineiston_siirto[$y]["type"],
      $maksuaineiston_siirto[$y]["file"],
      $maksuaineiston_siirto[$y]["local_dir"],
      $maksuaineiston_siirto[$y]["local_dir_ok"],
      $maksuaineiston_siirto[$y]["local_dir_error"])) {
    require "maksuaineisto_send.php";
    echo "<br><font class='message'>".t("Maksuaineisto siirretty pankkiyhteysohjelmaan").".</font>";
  }
}

// Jos meillä on SEPA pankkiyhteys käytössä
if (SEPA_PANKKIYHTEYS and !empty($pankkiyhteys_tiedosto)) {
  // Katsotaan, että pankkiyhteys on perustettu
  $query = "SELECT pankkiyhteys.tunnus AS pankkiyhteys_tunnus
            FROM yriti
            INNER JOIN pankkiyhteys ON (pankkiyhteys.yhtio = yriti.yhtio
              AND pankkiyhteys.pankki = yriti.bic)
            WHERE yriti.yhtio         = '{$kukarow["yhtio"]}'
            AND yriti.tunnus          = {$pankkitili_tunnus}";
  $result = pupe_query($query);

  // Meillä on pankkiyhteys luotu, tehdään formi lähettämistä varten
  if (mysql_num_rows($result) == 1) {
    $row = mysql_fetch_assoc($result);

    echo "<br><br>";
    echo "<font class='message'>";
    echo t("Lähetä maksuaineisto pankkiin");
    echo "</font>";
    echo "<hr>";

    echo "<form method='post' action='sepa.php'>";
    echo "<input type='hidden' name='tee' value='laheta_pankkiin'/>";
    echo "<input type='hidden' name='vanhatee' value='{$tee}'/>";
    echo "<input type='hidden' name='pankkitili_tunnus' value='{$pankkitili_tunnus}'/>";
    echo "<input type='hidden' name='pankkiyhteys_tunnus' value='{$row['pankkiyhteys_tunnus']}'/>";
    echo "<input type='hidden' name='pankkiyhteys_tiedosto' value='$pankkiyhteys_tiedosto'/>";

    echo "<table>";
    echo "<tr>";
    echo "<th>" . t("Tiedosto") . "</th>";
    echo "<td>{$pankkiyhteys_tiedosto}</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<th><label for='salasana'>" . t("Syötä pankkiyhteyden salasana") . "</label></th>";
    echo "<td><input type='password' name='salasana' id='salasana'/></td>";
    echo "</tr>";
    echo "</table>";

    echo "<br>";
    echo "<input type='submit' value='".t("Lähetä aineisto pankkiin")."'>";
    echo "</form>";
  }
}
