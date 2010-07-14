<?php

	/*

	Tehty ja validoitu käyttäen speksejä:

	URL:			http://www.iso20022.org/catalogue_of_unifi_messages.page
	Msg ID:			pain.001.001.02
	Message Name:	CustomerCreditTransferInitiationV02

	*/

	function sepa_header() {
		global $xml, $pain, $yhtiorow, $yritirow;

		$xmlstr  = '<Document ';
		$xmlstr .= 'xmlns="urn:iso:std:iso:20022:tech:xsd:pain.001.001.02" ';
		$xmlstr .= 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ';
		$xmlstr .= 'xsi:schemaLocation="urn:iso:std:iso:20022:tech:xsd:pain.001.001.02 pain.001.001.02.xsd">';
		$xmlstr .= '</Document>';

		$xml = new SimpleXMLElement($xmlstr);

		$pain = $xml->addChild('pain.001.001.02');
			$GrpHdr = $pain->addChild('GrpHdr');
				$GrpHdr->addChild('MsgId', date('Y-m-d')."T".date('H:i:s'));							// Pakollinen kenttä
				$GrpHdr->addChild('CreDtTm', date('Y-m-d')."T".date('H:i:s'));							// Pakollinen kenttä
//				$GrpHdr->addChild('Authstn', '');
//				$GrpHdr->addChild('BtchBookg', '');
				$GrpHdr->addChild('NbOfTxs', '1');														// Pakollinen kenttä
//				$GrpHdr->addChild('CtrlSum', '');
				$GrpHdr->addChild('Grpg', 'SNGL');														// Pakollinen kenttä
				$InitgPty = $GrpHdr->addChild('InitgPty', '');
					$InitgPty->addChild('Nm', $yhtiorow['nimi']);
					$PstlAdr = $InitgPty->addChild('PstlAdr', '');
//				 		$PstlAdr->addChild('AdrTp', '');
				 		$PstlAdr->addChild('AdrLine', $yhtiorow['osoite']);
						$PstlAdr->addChild('AdrLine', $yhtiorow['maa']."-".$yhtiorow['postino']." ".$yhtiorow['postitp']);
				 		$PstlAdr->addChild('StrtNm', $yhtiorow['osoite']);
//				 		$PstlAdr->addChild('BldgNb', '');
						$PstlAdr->addChild('PstCd', $yhtiorow['maa']."-".$yhtiorow['postino']);
						$PstlAdr->addChild('TwnNm', $yhtiorow['postitp']);
//				 		$PstlAdr->addChild('CtrySubDvsn', '');
						$PstlAdr->addChild('Ctry', $yhtiorow['maa']);
//					$Id = $InitgPty->addChild('Id', '');
//						$OrgId = $Id->addChild('OrgId', '');
//							$OrgId->addChild('BIC', '');
//							$OrgId->addChild('IBEI', '');
//							$OrgId->addChild('BEI', '');
//							$OrgId->addChild('EANGLN', '');
//							$OrgId->addChild('USCHU', '');
//							$OrgId->addChild('DUNS', '');
//							$OrgId->addChild('BkPtyId', '');
//							$OrgId->addChild('TaxIdNb', '');
//							$PrtryId = $OrgId->addChild('PrtryId', '');
//								$PrtryId->addChild('Id', '');
//								$PrtryId->addChild('Issr', '');
	}

	function sepa_paymentinfo($laskurow) {
		global $xml, $pain, $PmtInf, $yhtiorow, $yritirow;

		$PmtInf = $pain->addChild('PmtInf');
			$PmtInfId = $PmtInf->addChild('PmtInfId', $laskurow['tunnus']);							// Pakollinen kenttä
			$PmtMtd = $PmtInf->addChild('PmtMtd', 'TRF'); 											// Pakollinen kenttä (TRF = transfer)
//			$PmtTpInf = $PmtInf->addChild('PmtTpInf');
//			 	$InstrPrty = $PmtTpInf->addChild('InstrPrty');
//			 	$SvcLvl = $PmtTpInf->addChild('SvcLvl');
//			 		$SvcLvl->addChild('Cd', 'SEPA');
//			 	$LclInstrm = $PmtTpInf->addChild('LclInstrm');
//			 		$LclInstrm->addChild('Cd', '');
//			 	$CtgyPurp = $PmtTpInf->addChild('CtgyPurp');
			$ReqdExctnDt = $PmtInf->addChild('ReqdExctnDt', $laskurow['olmapvm']);					// Pakollinen kenttä
//			$PoolgAdjstmntDt = $PmtInf->addChild('PoolgAdjstmntDt');
			$Dbtr = $PmtInf->addChild('Dbtr');
//			 	$Dbtr->addChild('Nm', $yhtiorow['nimi']);
//			 	$PstlAdr = $Dbtr->addChild('PstlAdr');
//			 		$PstlAdr->addChild('AdrTp', '');
//			 		$PstlAdr->addChild('AdrLine', '');
//			 		$PstlAdr->addChild('StrtNm', '');
//			 		$PstlAdr->addChild('BldgNb', '');
//			 		$PstlAdr->addChild('PstCd', $yhtiorow['maa']."-".$yhtiorow['postino']);
//			 		$PstlAdr->addChild('TwnNm', $yhtiorow['postitp']);
//			 		$PstlAdr->addChild('CtrySubDvsn', '');
//			 		$PstlAdr->addChild('Ctry', $yhtiorow['maa']);
				$Id = $Dbtr->addChild('Id');
					$OrgId = $Id->addChild('OrgId');
//						$OrgId->addChild('BIC', '');
//						$OrgId->addChild('IBEI', '');
//						$OrgId->addChild('BEI', '');
//						$OrgId->addChild('EANGLN', '');
//						$OrgId->addChild('USCHU', '');
//						$OrgId->addChild('DUNS', '');
						$OrgId->addChild('BkPtyId', $yhtiorow['ytunnus']);							// Pakollinen kenttä
//						$OrgId->addChild('TaxIdNb', '');
//						$PrtryId = $OrgId->addChild('PrtryId', '');
//							$PrtryId->addChild('Id', '');
//							$PrtryId->addChild('Issr', '');
			$DbtrAcct = $PmtInf->addChild('DbtrAcct');
				$Id = $DbtrAcct->addChild('Id');
					$Id->addChild('IBAN', $yritirow['iban']);										// Pakollinen kenttä
//				$DbtrAcct->addChild('Ccy');
//				$DbtrAcct->addChild('Nm');
			$DbtrAgt = $PmtInf->addChild('DbtrAgt');
				$FinInstnId	= $DbtrAgt->addChild('FinInstnId');
					$FinInstnId->addChild('BIC', $yritirow['bic']);									// Pakollinen kenttä
//			$UltmtDbtr = $PmtInf->addChild('UltmtDbtr');
//				$UltmtDbtr->addChild('Nm');
//				$PstlAdr = $UltmtDbtr->addChild('PstlAdr');
//				$PstlAdr->addChild('AdrTp', '');
//				$PstlAdr->addChild('AdrLine', '');
//				$PstlAdr->addChild('StrtNm', '');
//				$PstlAdr->addChild('BldgNb', '');
//				$PstlAdr->addChild('PstCd', $yhtiorow['maa']."-".$yhtiorow['postino']);
//				$PstlAdr->addChild('TwnNm', $yhtiorow['postitp']);
//				$PstlAdr->addChild('CtrySubDvsn', '');
//				$PstlAdr->addChild('Ctry', $yhtiorow['maa']);
//				$Id = $UltmtDbtr->addChild('Id');
//				$OrgId = $Id->addChild('OrgId');
//					$OrgId->addChild('BIC', '');
//					$OrgId->addChild('IBEI', '');
//					$OrgId->addChild('BEI', '');
//					$OrgId->addChild('EANGLN', '');
//					$OrgId->addChild('USCHU', '');
//					$OrgId->addChild('DUNS', '');
//					$OrgId->addChild('BkPtyId', $yhtiorow['ytunnus']);
//					$OrgId->addChild('TaxIdNb', '');
//					$PrtryId = $OrgId->addChild('PrtryId', '');
//						$PrtryId->addChild('Id', '');
//						$PrtryId->addChild('Issr', '');
//			$UltmtDbtr->addChild('CtryOfRes');
//			$ChrgBr = $PmtInf->addChild('ChrgBr', 'SLEV');

		// HUOM: CdtTrfTxInf -segmentille, on oma funktio -> sepa_credittransfer

	}

	function sepa_credittransfer($laskurow) {
		global $xml, $pain, $PmtInf, $yhtiorow, $yritirow;

		// HUOM: Tämä kuuluu PmtInf -segmentin sisään!

		$CdtTrfTxInf = $PmtInf->addChild('CdtTrfTxInf', '');
			$PmtId = $CdtTrfTxInf->addChild('PmtId', '');
				$InstrId = $PmtId->addChild('InstrId', $laskurow['tunnus']);
				$EndToEndId = $PmtId->addChild('EndToEndId', $laskurow['tunnus']);					// Pakollinen kenttä
//			$PmtTpInf = $CdtTrfTxInf->addChild('PmtTpInf', '');
//				$InstrPrty = $PmtTpInf->addChild('InstrPrty', '');
//				$SvcLvl = $PmtTpInf->addChild('SvcLvl', '');
//					$Cd = $SvcLvl->addChild('Cd', 'SEPA');
//				$LclInstrm = $PmtTpInf->addChild('LclInstrm', '');
//					$Cd = $LclInstrm->addChild('Cd', '');
//				$CtgyPurp = $PmtTpInf->addChild('CtgyPurp', '');
			$Amt = $CdtTrfTxInf->addChild('Amt', '');

			if ($laskurow['alatila'] == 'K') {
				$InstdAmt = $Amt->addChild('InstdAmt', $laskurow['summa']);							// Pakollinen kenttä
			}
			else {
				$InstdAmt = $Amt->addChild('InstdAmt', $laskurow['summa'] - $laskurow['kasumma']);	// Pakollinen kenttä
			}
			$InstdAmt->addAttribute('Ccy', $laskurow['valkoodi']);									// Pakollinen atribute

//			$XchgRateInf = $CdtTrfTxInf->addChild('XchgRateInf', '');
//				$XchgRate = $XchgRateInf->addChild('XchgRate', '');
//				$RateTp = $XchgRateInf->addChild('RateTp', '');
//				$CtrctId = $XchgRateInf->addChild('CtrctId', '');
//			$ChrgBr = $CdtTrfTxInf->addChild('ChrgBr', 'SLEV');
//			$ChqInstr = $CdtTrfTxInf->addChild('ChqInstr', '');
//				$ChqTp = $ChqInstr->addChild('ChqTp', '');
//				$DlvryMtd = $ChqInstr->addChild('DlvryMtd', '');
//					$Cd = $DlvryMtd->addChild('Cd', '');
//			$UltmtDbtr = $CdtTrfTxInf->addChild('UltmtDbtr', '');
//				$Nm = $UltmtDbtr->addChild('Nm', '');
//				$PstlAdr = $UltmtDbtr->addChild('PstlAdr', '');
//					$AdrTp = $PstlAdr->addChild('AdrTp', '');
//					$AdrLine = $PstlAdr->addChild('AdrLine', '');
//					$StrtNm = $PstlAdr->addChild('StrtNm', '');
//					$BldgNb = $PstlAdr->addChild('BldgNb', '');
//					$PstCd = $PstlAdr->addChild('PstCd', '');
//					$TwnNm = $PstlAdr->addChild('TwnNm', '');
//					$CtrySubDvsn = $PstlAdr->addChild('CtrySubDvsn', '');
//					$Ctry = $PstlAdr->addChild('Ctry', '');
//				$Id = $UltmtDbtr->addChild('Id', '');
//					$OrgId = $Id->addChild('OrgId', '');
//						$BIC = $OrgId->addChild('BIC', '');
//						$IBEI = $OrgId->addChild('IBEI', '');
//						$BEI = $OrgId->addChild('BEI', '');
//						$EANGLN = $OrgId->addChild('EANGLN', '');
//						$USCHU = $OrgId->addChild('USCHU', '');
//						$DUNS = $OrgId->addChild('DUNS', '');
//						$BkPtyId = $OrgId->addChild('BkPtyId', '');
//						$TaxIdNb = $OrgId->addChild('TaxIdNb', '');
//						$PrtryId = $OrgId->addChild('PrtryId', '');
//							$Id = $PrtryId->addChild('Id', '');
//							$Issr = $PrtryId->addChild('Issr', '');
//				$CtryOfRes = $UltmtDbtr->addChild('CtryOfRes', '');
//			$IntrmyAgt1 = $CdtTrfTxInf->addChild('IntrmyAgt1', '');
//				$FinInstnId = $IntrmyAgt1->addChild('FinInstnId', '');
//					$BIC = $FinInstnId->addChild('BIC', '');
//			$IntrmyAgt1Acct = $CdtTrfTxInf->addChild('IntrmyAgt1Acct', '');
//				$Id = $IntrmyAgt1Acct->addChild('Id', '');
//					$IBAN = $Id->addChild('IBAN', '');
//				$Ccy = $IntrmyAgt1Acct->addChild('Ccy', '');
//				$Nm = $IntrmyAgt1Acct->addChild('Nm', '');
//			$IntrmyAgt2 = $CdtTrfTxInf->addChild('IntrmyAgt2', '');
//				$FinInstnId = $IntrmyAgt2->addChild('FinInstnId', '');
//					$BIC = $FinInstnId->addChild('BIC', '');
//			$IntrmyAgt2Acct = $CdtTrfTxInf->addChild('IntrmyAgt2Acct', '');
//				$Id = $IntrmyAgt2Acct->addChild('Id', '');
//					$IBAN = $Id->addChild('IBAN', '');
//				$Ccy = $IntrmyAgt2Acct->addChild('Ccy', '');
//				$Nm = $IntrmyAgt2Acct->addChild('Nm', '');
//			$CdtrAgt = $CdtTrfTxInf->addChild('CdtrAgt', '');
//				$FinInstnId = $CdtrAgt->addChild('FinInstnId', '');
//					$BIC = $FinInstnId->addChild('BIC', $laskurow['bic']);
//			$CdtrAgtAcct = $CdtTrfTxInf->addChild('CdtrAgtAcct', '');
//				$Id = $CdtrAgtAcct->addChild('Id', '');
//					$IBAN = $Id->addChild('IBAN', '');
//				$Ccy = $CdtrAgtAcct->addChild('Ccy', '');
//				$Nm = $CdtrAgtAcct->addChild('Nm', '');
			$Cdtr = $CdtTrfTxInf->addChild('Cdtr', '');
				$Nm = $Cdtr->addChild('Nm', $laskurow['nimi']);									// Pakollinen kenttä
			 	$PstlAdr = $Cdtr->addChild('PstlAdr', '');
//					$AdrTp = $PstlAdr->addChild('AdrTp', '');
			 		$AdrLine = $PstlAdr->addChild('AdrLine', $laskurow['osoite']);
			 		$AdrLine = $PstlAdr->addChild('AdrLine', $laskurow['maa']."-".$laskurow['postino']." ".$laskurow['postitp']);
			 		$StrtNm = $PstlAdr->addChild('StrtNm', $laskurow['osoite']);
//					$BldgNb = $PstlAdr->addChild('BldgNb', '');
			 		$PstCd = $PstlAdr->addChild('PstCd', $laskurow['maa']."-".$laskurow['postino']);
			 		$TwnNm = $PstlAdr->addChild('TwnNm', $laskurow['postitp']);
//					$CtrySubDvsn = $PstlAdr->addChild('CtrySubDvsn', '');
			 		$Ctry = $PstlAdr->addChild('Ctry', $laskurow['maa']);
//			$Id = $Cdtr->addChild('Id', '');
//				$OrgId = $Id->addChild('OrgId', '');
//					$BIC = $OrgId->addChild('BIC', '');
//					$IBEI = $OrgId->addChild('IBEI', '');
//					$BEI = $OrgId->addChild('BEI', '');
//					$EANGLN = $OrgId->addChild('EANGLN', '');
//					$USCHU = $OrgId->addChild('USCHU', '');
//					$DUNS = $OrgId->addChild('DUNS', '');
//					$BkPtyId = $OrgId->addChild('BkPtyId', $laskurow['ytunnus']);
//					$TaxIdNb = $OrgId->addChild('TaxIdNb', '');
//					$PrtryId = $OrgId->addChild('PrtryId', '');
//						$Id = $PrtryId->addChild('Id', '');
//						$Issr = $PrtryId->addChild('Issr', '');
//			$CtryOfRes = $Cdtr->addChild('CtryOfRes', '');
//			$CdtrAcct = $CdtTrfTxInf->addChild('CdtrAcct', '');
//				$Id = $CdtrAcct->addChild('Id', '');
//					$IBAN = $Id->addChild('IBAN', $laskurow['iban']);
//				$Ccy = $CdtrAcct->addChild('Ccy', '');
//				$Nm = $CdtrAcct->addChild('Nm', '');
//			$UltmtCdtr = $CdtTrfTxInf->addChild('UltmtCdtr', '');
//				$Nm = $UltmtCdtr->addChild('Nm', '');
//				$PstlAdr = $UltmtCdtr->addChild('PstlAdr', '');
//					$AdrTp = $PstlAdr->addChild('AdrTp', '');
//					$AdrLine = $PstlAdr->addChild('AdrLine', '');
//					$StrtNm = $PstlAdr->addChild('StrtNm', '');
//					$BldgNb = $PstlAdr->addChild('BldgNb', '');
//					$PstCd = $PstlAdr->addChild('PstCd', '');
//					$TwnNm = $PstlAdr->addChild('TwnNm', '');
//					$CtrySubDvsn = $PstlAdr->addChild('CtrySubDvsn', '');
//					$Ctry = $PstlAdr->addChild('Ctry', '');
//				$Id = $UltmtCdtr->addChild('Id', '');
//					$OrgId = $Id->addChild('OrgId', '');
//						$BIC = $OrgId->addChild('BIC', '');
//						$IBEI = $OrgId->addChild('IBEI', '');
//						$BEI = $OrgId->addChild('BEI', '');
//						$EANGLN = $OrgId->addChild('EANGLN', '');
//						$USCHU = $OrgId->addChild('USCHU', '');
//						$DUNS = $OrgId->addChild('DUNS', '');
//						$BkPtyId = $OrgId->addChild('BkPtyId', '');
//						$TaxIdNb = $OrgId->addChild('TaxIdNb', '');
//						$PrtryId = $OrgId->addChild('PrtryId', '');
//							$Id = $PrtryId->addChild('Id', '');
//							$Issr = $PrtryId->addChild('Issr', '');
//				$CtryOfRes = $UltmtCdtr->addChild('CtryOfRes', '');
//			$InstrForDbtrAgt = $CdtTrfTxInf->addChild('InstrForDbtrAgt', '');
//			$Purp = $CdtTrfTxInf->addChild('Purp', '');
//				$Cd = $Purp->addChild('Cd', '');
//			$RgltryRptg = $CdtTrfTxInf->addChild('RgltryRptg', '');
//				$DbtCdtRptgInd = $RgltryRptg->addChild('DbtCdtRptgInd', '');
//				$Authrty = $RgltryRptg->addChild('Authrty', '');
//					$AuthrtyNm = $Authrty->addChild('AuthrtyNm', '');
//					$AuthrtyCtry = $Authrty->addChild('AuthrtyCtry', '');
//				$RgltryDtls = $RgltryRptg->addChild('RgltryDtls', '');
//					$Cd = $RgltryDtls->addChild('Cd', '');
//					$Amt = $RgltryDtls->addChild('Amt', '');
//					$Inf = $RgltryDtls->addChild('Inf', '');
//			$RmtInf = $CdtTrfTxInf->addChild('RmtInf', '');
//				$Ustrd = $RmtInf->addChild('Ustrd', $laskurow['viesti']);
//				$Strd = $RmtInf->addChild('Strd', '');
//					$RfrdDocInf = $Strd->addChild('RfrdDocInf', '');
//						$RfrdDocTp = $RfrdDocInf->addChild('RfrdDocTp', '');
//							$Cd = $RfrdDocTp->addChild('Cd', '');
//						$RfrdDocNb = $RfrdDocInf->addChild('RfrdDocNb', '');
//					$RfrdDocRltdDt = $Strd->addChild('RfrdDocRltdDt', '');
//					$RfrdDocAmt = $Strd->addChild('RfrdDocAmt', '');
//						$RmtdAmt = $RfrdDocAmt->addChild('RmtdAmt', '');
//					$CdtrRefInf = $Strd->addChild('CdtrRefInf', '');
//						$CdtrRefTp = $CdtrRefInf->addChild('CdtrRefTp', '');
//							$Cd = $CdtrRefTp->addChild('Cd', 'SCOR');
//						$CdtrRef = $CdtrRefInf->addChild('CdtrRef', $laskurow['viite']);
//					$AddtlRmtInf = $Strd->addChild('AddtlRmtInf', '');
	}

	if (isset($_POST["tee"])) {
		if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
		if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
	}

	require("inc/parametrit.inc");

	if (isset($tee)) {
		if ($tee == "lataa_tiedosto") {
			readfile("dataout/".$filenimi);
			exit;
		}
	}
	else {

		echo "<font class='head'>SEPA-maksuaineisto</font><hr>";

		//Poimitaan SEPA-maksut
		$query = "	SELECT *
					FROM lasku
					WHERE lasku.yhtio	= '$kukarow[yhtio]'
					and lasku.tila		= 'P'
					and lasku.valkoodi	= 'EUR'
					and lasku.maksaja	= '$kukarow[kuka]'
					and lasku.summa     > 0";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {
			//Päätetääm maksuaineston tiedostonimi

			$kaunisnimi = "sepa-$kukarow[yhtio]-" . date("d.m.y.H.i.s") . "-". $generaatio . ".txt";
			$toot = fopen("dataout/".$kaunisnimi,"w+");

			if (!$toot) {
				echo t("En saanut tiedostoa auki! Tarkista polku")." dataout/$kaunisnimi !";
				exit;
			}

			echo "<table>";
			echo "<tr><th>".t("SEPA-maksujen tiedoston nimi on").":</th><td>$kaunisnimi</td></tr>";
			echo "<tr><td class='back'><br></td></tr>";
		}
		else {
			echo "<font class='message'>".t("Sopivia laskuja ei löydy")."</font>";
			exit;
		}

		$totkpl 	 = 0;
		$totsumma	 = 0;
		$generaatio  = 0;
		$makskpl 	 = 0;
		$makssumma 	 = 0;
		$edmaksutili = 0;
		$edpvm       = '0000-00-00';
		$edtili      = '';

		while ($laskurow = mysql_fetch_array($result)) {

			$iban_tilinumero = tarkista_iban($laskurow["ultilno"]);

			if ($iban_tilinumero == "") {
				echo "Tilinumero $laskurow[ultilno] ei ole oikeellinen IBAN tilinumero, ei voida lisätä laskua aineistoon!<br>";
				continue;
			}

			if ($edmaksutili != $laskurow['maksu_tili']) {

				if ($edmaksutili != '') {
					if (is_resource($toot)) {
						fputs($toot, $xml->asXML());
						fclose($toot);
					}
					echo "<tr><td>".sprintf(t("Tililtä %s siirretään maksuun"), $yritirow["nimi"]).":</td><td>".sprintf('%.2f',$makssumma)."</td>";
					echo "<tr><td>".t("Summa koostuu").":</td><td>$makskpl ".t("laskusta")."</td></tr>";
				}

				$query = "	SELECT *
							FROM yriti
							WHERE yhtio  = '$kukarow[yhtio]'
							and tunnus 	 = '$laskurow[maksu_tili]'";
				$yritiresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($yritiresult) == 0) {
					echo "<font class='error'>".t("Maksutili ei löydy")." $laskurow[maksu_tili]</font>";
					exit;
				}
				$yritirow = mysql_fetch_array($yritiresult);
				echo "<tr><th>".t("Tilinumero").":</th><td>".$yritirow['nimi']."</td></tr>";
				$edmaksutili = $laskurow['maksu_tili'];

				if (!is_resource($toot)) {
					$generaatio++;
					$kaunisnimi = "sepa-$kukarow[yhtio]-" . date("d.m.y.H.i.s") . "-". $generaatio . ".txt";
					$toot = fopen("dataout/".$kaunisnimi,"w+");
					if (!$toot) {
						echo t("En saanut tiedostoa auki! Tarkista polku")." dataout/$kaunisnimi !";
						exit;
					}
				}
				sepa_header();
			}

			if ($edpvm != $laskurow['olmapvm'] or $edtili != $laskurow['ultilno']) {
				sepa_paymentinfo($laskurow);
				$edpvm = $laskurow['olmapvm'];
				$edtili = $laskurow['ultilno'];
			}

			sepa_credittransfer($laskurow);

			$makskpl++;
			if ($laskurow['alatila'] == 'K') {
				$makssumma += $laskurow['summa'];
			}
			else {
				$makssumma += $laskurow['summa']-$laskurow['kasumma'];
			}
		}

		echo "<tr><td>".sprintf(t("Tililtä %s siirretään maksuun"), $yritirow["nimi"]).":</td><td>".sprintf('%.2f',$makssumma)."</td>";
		echo "<tr><td>".t("Summa koostuu").":</td><td>$makskpl ".t("laskusta")."</td></tr>";

		if (is_resource($toot)) {
			fputs($toot,$xml->asXML());
			fclose($toot);

			if ($tiedostonimi == "") {
				$tiedostonimi = $kaunisnimi;
			}

			echo "<tr><th>".t("Tallenna aineisto").":</th>";
			echo "<form method='post' action='$PHP_SELF'>";
			echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
			echo "<input type='hidden' name='kaunisnimi' value='$tiedostonimi'>";
			echo "<input type='hidden' name='filenimi' value='$kaunisnimi'>";
			echo "<td><input type='submit' value='".t("Tallenna")."'></td></tr></form>";

		}

/*
			$query = "	UPDATE lasku
								SET tila = 'Q'
					          	WHERE yhtio 	= '$kukarow[yhtio]'
					          	and tila 		= 'P'
								and maa		 	= '$kotimaa'
					          	and maksaja 	= '$kukarow[kuka]'
					          	and maksu_tili	= '$yritirow[tunnus]'
					          	and olmapvm 	= '$pvmrow[olmapvm]'
					          	ORDER BY yhtio, tila";
			$result = mysql_query($query) or pupe_error($query);
*/
			$makskpl 	= 0;
			$makssumma 	= 0;

	}

?>
