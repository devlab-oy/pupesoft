
<?php


function sepa_header() {
	global $xml, $pain, $yhtiorow, $yritirow;

	$xmlstr='<Document
	xmlns="urn:iso:std:iso:20022:tech:xsd:pain.001.001.02"
	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
	xsi:schemaLocation="urn:iso:std:iso:20022:tech:xsd:pain.001.001.02
	pain.001.001.02.xsd">
	</Document>';

	$msgid = date('Y-m-d')."T".date('H:i:s');

	$xml = new SimpleXMLElement($xmlstr);

	$pain = $xml->addChild('pain.001.001.02');
	$GrpHdr = $pain->addChild('GrpHdr');
	        $toot = $GrpHdr->addChild('MsgId', $msgid);
	        $toot = $GrpHdr->addChild('CreDtTm', date('Y-m-d')."T".date('H:i:s'));
	        $toot = $GrpHdr->addChild('NbOfTxs', '1');
	        $toot = $GrpHdr->addChild('Grpg', 'SNGL');
	        $InitgPty = $GrpHdr->addChild('InitgPty');
	                $toot = $InitgPty->addChild('Nm', $yhtiorow['nimi']);
	                $PstlAdr = $GrpHdr->addChild('PstlAdr');
	                	$toot = $PstlAdr->addChild('AdrLine', $yhtiorow['osoite']);
	                	$toot = $PstlAdr->addChild('AdrLine', $yhtiorow['maa']."-".$yhtiorow['postino'].' '.$yhtiorow['postitp']);
	                	$toot = $PstlAdr->addChild('Ctry', $yhtiorow['maa']);
	                $Id = $InitgPty->addChild('Id');
						$OrgId = $Id->addChild('OrgId');
							$toot = $OrgId->addChild('BkPtyId',$yhtiorow['ytunnus']);
						
}						

function sepa_paymentinfo ($laskurow) {
	global $xml, $pain, $PmtInf, $yhtiorow, $yritirow;
							
	$PmtInf = $pain->addChild('PmtInf');						
		$toot = $PmtInf->addChild('PmtInfId', $laskurow['tunnus']);
		$toot = $PmtInf->addChild('PmtMtd', 'TRF');
		$PmtTpInf = $PmtInf->addChild('PmtTpInf');
			$SvcLvl = $PmtTpInf->addChild('SvcLvl');	
				$toot = $SvcLvl->addChild('Cd','SEPA');
		$toot = $PmtInf->addChild('ReqdExctnDt', $laskurow['olmapvm']);
		$Dbtr = $PmtInf->addChild('Dbtr');
			$toot = $Dbtr->addChild('Nm', $yhtiorow['nimi']);
			$PstlAdr = $Dbtr->addChild('PstlAdr');
				$toot = $PstlAdr->addChild('AdrLine', $yhtiorow['osoite']);
				$toot = $PstlAdr->addChild('AdrLine', $yhtiorow['maa']."-".$yhtiorow['postino'].' '.$yhtiorow['postitp']);			
				$toot = $PstlAdr->addChild('Ctry', $yhtiorow['maa']);
			$Id = $Dbtr->addChild('Id');
				$OrgId = $Id->addChild('OrgId');
					$toot = $OrgId->addChild('BkPtyId',$yhtiorow['ytunnus']);				
		$DbtrAcct = $PmtInf->addChild('DbtrAcct');
			$Id = $DbtrAcct->addChild('Id');		
				$toot = $Id->addChild('IBAN', $yritirow['iban']);
		$DbtrAgt = $PmtInf->addChild('DbtrAgt');
			$FinInstnId = $DbtrAgt->addChild('BIC', $yritirow['bic']);
		$toot = $PmtInf->addChild('ChrgBr', 'SLEV');

}

function sepa_credittransfer ($laskurow) {
	global $xml, $pain, $PmtInf, $yhtiorow, $yritirow;

	$CdtTrfTxInf = $PmtInf->addChild('CdtTrfTxInf');
		$PmtId = $CdtTrfTxInf->addChild('PmtId');
			$toot = $PmtId->addChild('InstrId',$laskurow['tunnus']);
			$toot = $PmtId->addChild('EndToEndId',$laskurow['tunnus']);
		$PmtTpInf = $CdtTrfTxInf->addChild('PmtTpInf');
			$SvcLvl = $PmtTpInf->addChild('SvcLvl');	
				$toot = $SvcLvl->addChild('Cd','SEPA');
		$Amt = $CdtTrfTxInf->addChild('Amt');

		if($laskurow['alatila']=='K')
				$InstdAmt = $Amt->addChild('InstdAmt', $laskurow['summa']);
		else
				$InstdAmt = $Amt->addChild('InstdAmt', $laskurow['summa']-$laskurow['kasumma']);

				$InstdAmt->addAttribute('Ccy', $laskurow['valkoodi']);
		$toot = $CdtTrfTxInf->addChild('ChrgBr', 'SLEV');
		$CdtrAgt = $CdtTrfTxInf->addChild('CdtrAgt');
			$FinInstnId = $CdtrAgt->addChild('BIC', $laskurow['bic']);
		$Cdtr = $CdtTrfTxInf->addChild('Cdtr');
			$toot = $Cdtr->addChild('Nm', $laskurow['nimi']);
			$PstlAdr = $Cdtr->addChild('PstlAdr');
				$toot = $PstlAdr->addChild('AdrLine', $laskurow['osoite']);
				$toot = $PstlAdr->addChild('AdrLine', $laskurow['maa']."-".$laskurow['postino'].' '.$laskurow['postitp']);			
				$toot = $PstlAdr->addChild('Ctry', $laskurow['maa']);
			$Id = $Cdtr->addChild('Id');
				$OrgId = $Id->addChild('OrgId');
					$toot = $OrgId->addChild('BkPtyId',$laskurow['ytunnus']);				
		$CdtrAcct = $CdtTrfTxInf->addChild('CdtrAcct');
			$Id = $CdtrAcct->addChild('Id');		
				$toot = $Id->addChild('IBAN', $laskurow['iban']);

		// Maksun lisätiedot
		$RmtInf = $CdtTrfTxInf->addChild('RmtInf');
		if (strlen($laskurow['viite']) > 0) {
			$Strd = $RmtInf->addChild('Strd');
				$CdtrRefInf= $Strd->addChild('CdtrRefInf');
					$CdtrRefTp= $CdtrRefInf->addChild('CdtrRefTp');
						$toot= $CdtrRefTp->addChild('Cd', 'SCOR');
					$toot= $CdtrRefInf->addChild('CdtrRef',sprintf ('%020s',$laskurow['viite']));
		}
		else {
			$toot = $RmtInf->addChild('Ustrd',$laskurow['viesti']);
		}
		
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
				WHERE yhtio  = '$kukarow[yhtio]'
				and tila 	 = 'Y'
				and valkoodi = 'EUR'
				and maksu_tili >= 17 and maksu_tili <= 19
				and ultilno != ''
				GROUP BY maksu_tili, olmapvm, ultilno
				LIMIT 10";
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
		
		if (($edpvm != $laskurow['olmapvm']) or ($edtili != $laskurow['ultilno'])) {
			sepa_paymentinfo($laskurow);
			$edpvm = $laskurow['olmapvm'];
			$edtili = $laskurow['ultilno'];

			
		}
		sepa_credittransfer($laskurow);
		$makskpl++;
		if($laskurow['alatila']=='K')
			$makssumma += $laskurow['summa'];
		else
			$makssumma += $laskurow['summa']-$laskurow['kasumma'];
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
	else {
		echo "OOOOH We have problems! Toot is not a resource<br>";
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
