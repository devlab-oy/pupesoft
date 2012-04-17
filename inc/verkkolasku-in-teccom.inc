<?php

	$laskuformaatti 		= "TECCOM-INVOICE";

	// parseroidaan xml
	$yhtio 					= "20428100"; 		//$xml->InvoiceHeader->InvoiceOrg->TaxRegistrationNumber;
	$verkkotunnus_vas		= "003720428100";	//$xml->InvoiceHeader->InvoiceOrg->TaxRegistrationNumber; 	// joissakin materiaaleissa viallista materiaalia.
	$laskun_tyyppi 			= "TECCOM-INVOICE";
	$laskun_numero 			= $xml->InvoiceHeader->InvoiceId; // Tämä on lähettäjän laskunnumero
	$laskun_ebid 			= "TECCOM-INVOICE";
	$laskun_tilausviite		= "";

	if (isset($xml->InvoiceHeader->InvoiceIssueDate->Date)) {
		$laskun_tapvm 	= $xml->InvoiceHeader->InvoiceIssueDate->Date;
		$laskun_lapvm	= $xml->InvoiceHeader->InvoiceIssueDate->Date;
	}
	else {
		$laskun_tapvm 	= date("Ymd");
		$laskun_lapvm 	= date("Ymd");
	}

	if (isset($xml->InvoiceHeader->InvoiceDueDate->Date)) {
		$laskun_erapaiva = $xml->InvoiceHeader->InvoiceDueDate->Date;
	}
	else {
		$laskun_erapaiva = date("Ymd");
	}

	$laskuttajan_ovt				= "";
	$laskuttajan_nimi				= utf8_decode($xml->InvoiceHeader->SellerParty->Address->Name1);
	$laskuttajan_toimittajanumero	= utf8_decode($xml->InvoiceHeader->SellerParty->PartyNumber);

	if (strtoupper($laskuttajan_toimittajanumero) == "ELRING") {
		$laskuttajan_toimittajanumero = "123312";
	}
	elseif (strtoupper($laskuttajan_toimittajanumero) == "BOSCH") {
		$laskuttajan_toimittajanumero = "123067";
	}
	elseif (strtoupper($laskuttajan_toimittajanumero) == "LES-7") {
		$laskuttajan_toimittajanumero = "123080";
	}
	elseif (strtoupper($laskuttajan_toimittajanumero) == "NISSENS") {
		$laskuttajan_toimittajanumero = "123403";
	}
	elseif ($laskuttajan_toimittajanumero == "112") {
		$laskuttajan_toimittajanumero = "123442";
	}

	$laskuttajan_vat				= "";
	$laskun_pankkiviite 			= "";
	$laskun_asiakastunnus			= "";

	if (isset($xml->InvoiceSummary->InvoiceTotals->InvoiceAmountPayable->Amount)  and $xml->InvoiceSummary->InvoiceTotals->InvoiceAmountPayable->Amount != 0) {
		$laskun_summa_eur = (float) str_replace(",", ".", $xml->InvoiceSummary->InvoiceTotals->InvoiceAmountPayable->Amount);
	}
	elseif (isset($xml->InvoiceSummary->InvoiceTotals->InvoiceNetValue->Amount) and $xml->InvoiceSummary->InvoiceTotals->InvoiceNetValue->Amount != 0) {
		$laskun_summa_eur = (float) str_replace(",", ".", $xml->InvoiceSummary->InvoiceTotals->InvoiceNetValue->Amount);
	}
	else {
		$laskun_summa_eur = (float) str_replace(",", ".", $xml->InvoiceSummary->InvoiceTotals->InvoiceGrossValue->Amount);
	}

	if (isset($xml->InvoiceHeader->OrderRef)) {
		foreach ($xml->InvoiceHeader as $otsikolta) {
			foreach ($otsikolta->OrderRef as $viitteet) {
				$laskun_tilausviite .= $viitteet->BuyerOrderNumber.",";
			}
		}
		$laskun_tilausviite = substr($laskun_tilausviite,0,-1);
	}
	else {
		foreach ($xml->InvoiceDetail as $riveilta) {
			$rivi[] = $riveilta->OrderRef->BuyerOrderNumber;
		}
		$rivi = array_unique($rivi); // otetaan uniikit
		$rivi = array_values($rivi); // poistetaan turhat välit
		$laskun_tilausviite = implode(",",$rivi);
	}

	$laskun_asiakkaan_tilausnumero = "";
	$kauttalaskutus = "";

	$toim_asiakkaantiedot = array();
	$toim_asiakkaantiedot["toim_ovttunnus"] = "";
	$toim_asiakkaantiedot["ytunnus"] = "";

	// Ensiksi deliverystä
	if (isset($xml->InvoiceHeader->DeliveryParty->Address->Name1)) {
		$toim_asiakkaantiedot["nimi"] = trim(utf8_decode($xml->InvoiceHeader->DeliveryParty->Address->Name1));
	}
	else { // jos ei löytynyt, niin laitetaan laskutusosoite
		$toim_asiakkaantiedot["nimi"] = trim(utf8_decode($xml->InvoiceHeader->InvoiceOrg->InvoiceParty->Address->Name1));
	}

	if (isset($xml->InvoiceHeader->DeliveryParty->Address->Street1)) {
		$toim_asiakkaantiedot["osoite"] = trim(utf8_decode($xml->InvoiceHeader->DeliveryParty->Address->Street1));
	}
	else {
		$toim_asiakkaantiedot["osoite"] = trim(utf8_decode($xml->InvoiceHeader->InvoiceOrg->InvoiceParty->Address->Street1));
	}

	if (isset($xml->InvoiceHeader->DeliveryParty->Address->PostalCode)) {
		$toim_asiakkaantiedot["postino"] = trim(utf8_decode($xml->InvoiceHeader->DeliveryParty->Address->PostalCode));
	}
	else {
		$toim_asiakkaantiedot["postino"] = trim(utf8_decode($xml->InvoiceHeader->InvoiceOrg->InvoiceParty->Address->PostalCode));
	}

	if (isset($xml->InvoiceHeader->DeliveryParty->Address->City)) {
		$toim_asiakkaantiedot["postitp"] = trim(utf8_decode($xml->InvoiceHeader->DeliveryParty->Address->City));
	}
	else {
		$toim_asiakkaantiedot["postitp"] = trim(utf8_decode($xml->InvoiceHeader->InvoiceOrg->InvoiceParty->Address->City));
	}

	// Sama ostaja_tiedoille...
	$ostaja_asiakkaantiedot = array();
	$ostaja_asiakkaantiedot["toim_ovttunnus"] = "";
	$ostaja_asiakkaantiedot["ytunnus"] = "";

	if (isset($xml->InvoiceHeader->BuyerParty->Address->Name1)) {
		$ostaja_asiakkaantiedot["nimi"] = trim(utf8_decode($xml->InvoiceHeader->BuyerParty->Address->Name1));
	}
	else {
		$ostaja_asiakkaantiedot["nimi"] = trim(utf8_decode($xml->InvoiceHeader->InvoiceOrg->InvoiceParty->Address->Name1));
	}

	if (isset($xml->InvoiceHeader->BuyerParty->Address->Street1)) {
		$ostaja_asiakkaantiedot["osoite"] = trim(utf8_decode($xml->InvoiceHeader->BuyerParty->Address->Street1));
	}
	else {
		$ostaja_asiakkaantiedot["osoite"] = trim(utf8_decode($xml->InvoiceHeader->InvoiceOrg->InvoiceParty->Address->Street1));
	}

	if (isset($xml->InvoiceHeader->BuyerParty->Address->PostalCode)) {
		$ostaja_asiakkaantiedot["postino"] = trim(utf8_decode($xml->InvoiceHeader->BuyerParty->Address->PostalCode));
	}
	else {
		$ostaja_asiakkaantiedot["postino"] = trim(utf8_decode($xml->InvoiceHeader->InvoiceOrg->InvoiceParty->Address->PostalCode));
	}

	if (isset($xml->InvoiceHeader->BuyerParty->Address->City)) {
		$ostaja_asiakkaantiedot["postitp"] = trim(utf8_decode($xml->InvoiceHeader->BuyerParty->Address->City));
	}
	else {
		$ostaja_asiakkaantiedot["postitp"] = trim(utf8_decode($xml->InvoiceHeader->InvoiceOrg->InvoiceParty->Address->City));
	}

	// Onko tunnus IBAN-muodossa, jos ei niin varmistetaan, että se on ovtmuodossa
	if (strlen($yhtio) != 18 or strtoupper(substr($yhtio,0,2)) != "FI") {
		// Varmistetaan, että tunnus on ovtunnus muodossa
		$yhtio = preg_replace("/[^0-9]/", "", $yhtio);

		if (strpos($yhtio, "0037") === FALSE) {
			$yhtio = "0037".$yhtio;
		}
	}

	// Varmistetaan, että tunnus on ytunnus muodossa (Finvoicessa täällä tulee siis vastanottajan ytunnus FI etuliitteellä)
	$verkkotunnus_vas = preg_replace("/[^0-9]/", "", $verkkotunnus_vas);

	// Käsitellään tuoterivit
	$tuotetiedot	= $xml->InvoiceDetail;
	$i				= 0;
	$rtuoteno		= array();
	$ealvi		 	= array("");
	$lisavat		= array("");
	$aputuoteno		= "";
	$pituus			= "";

	if (count($tuotetiedot) > 0) {
		foreach ($tuotetiedot as $tuotetieto) {
			// tuotekoodi
			// Tehdään poikkeustapaksien käsittely
			if ($laskuttajan_toimittajanumero == 123085) {
				$rtuoteno[$i]["tuoteno"] = $rtuoteno[$i]["tuoteno2"] = utf8_decode($tuotetieto->ProductDescription->ProductName1);
				echo "Tuotenumeron ".$rtuoteno[$i]["tuoteno"]." poikkeuskäsittely (laskuttajan toimittajanumero {$laskuttajan_toimittajanumero})<br><br>";
			}
			elseif ($laskuttajan_toimittajanumero == 123067) {
				$rtuoteno[$i]["tuoteno"] = substr(utf8_decode($tuotetieto->ProductId->ProductNumber),0,-3);
				$rtuoteno[$i]["tuoteno2"] = substr(utf8_decode($tuotetieto->ProductId->ProductNumber),0,-3)."090";
				echo "Tuotenumeron ".$rtuoteno[$i]["tuoteno"]." poikkeuskäsittely (laskuttajan toimittajanumero {$laskuttajan_toimittajanumero})<br><br>";
			}
			elseif ($laskuttajan_toimittajanumero == 123007) {
				$aputuoteno = utf8_decode($tuotetieto->ProductId->ProductNumber);
				$pituus 	= strlen(trim($aputuoteno));

				if ($pituus == 8 and strpos(' ',$aputuoteno) == 6) {
					$rtuoteno[$i]["tuoteno"] = $rtuoteno[$i]["tuoteno2"] = substr($aputuoteno,0,5);
				}
				else {
					$rtuoteno[$i]["tuoteno"] = $rtuoteno[$i]["tuoteno2"] = $aputuoteno;
				}
				echo "Tuotenumeron ".$rtuoteno[$i]["tuoteno"]." poikkeuskäsittely (laskuttajan toimittajanumero {$laskuttajan_toimittajanumero})<br><br>";
			}
			elseif ($laskuttajan_toimittajanumero == "123453") {
				$rtuoteno[$i]["tuoteno"] = substr($tuotetieto->ProductId->ProductNumber,0,3)."-".substr($tuotetieto->ProductId->ProductNumber,3);
				$rtuoteno[$i]["tuoteno2"] = $tuotetieto->ProductId->ProductNumber;
			}
			elseif ($laskuttajan_toimittajanumero == "123178") {
				$rtuoteno[$i]["tuoteno"] = substr($tuotetieto->ProductId->ProductNumber,3);
				$rtuoteno[$i]["tuoteno2"] = $tuotetieto->ProductId->ProductNumber;
			}
			elseif ($laskuttajan_toimittajanumero == "123084") {
				$rtuoteno[$i]["tuoteno"] = $tuotetieto->ProductId->ProductNumber;
				$rtuoteno[$i]["tuoteno2"] = ltrim($tuotetieto->ProductId->ProductNumber, '0');
			}
			else {
				$rtuoteno[$i]["tuoteno"] = $rtuoteno[$i]["tuoteno2"] = utf8_decode($tuotetieto->ProductId->ProductNumber);
				echo "Ei poikkeuskäsittelyä tuotteelle ".$rtuoteno[$i]["tuoteno"]."<br><br>";
			}

			if ($rtuoteno[$i]["tuoteno"] != "") {

				echo "tuoteno: ".$rtuoteno[$i]["tuoteno"]."<br>";

				// Tallennetaan riville jos tämä on kauttalaskutusta
				if ($kauttalaskutus == "KAUTTALASKUTUS") {
					$rtuoteno[$i]["kauttalaskutus"] = "KAUTTALASKUTUS";
				}

				$rtuoteno[$i]["ostotilausnro"] = (string) $tuotetieto->OrderRef->BuyerOrderNumber;

				// $rtuoteno[$i]["tilaajanrivinro"] = (string) $tuotetieto->PositionNumber;
				$rtuoteno[$i]["tilaajanrivinro"] = (string) $tuotetieto->OrderItemRef->BuyerOrderItemRef;

				$rtuoteno[$i]["kpl"] = (float) str_replace(",", ".", $tuotetieto->Quantity);

				if (in_array($laskuttajan_toimittajanumero, array("123007","123442","123001"))) {
					$rtuoteno[$i]["rivihinta"] = (float) str_replace(",", ".", $tuotetieto->TotalValue->Amount); // summa
				}
				else {
					$rtuoteno[$i]["rivihinta"] = (float) str_replace(",", ".", $tuotetieto->TotalPrice->Amount); // summa
				}

				// jos rivihinta löytyy, niin käytetään sitä, muussa tapauksessa lasketaan
				if (isset($tuotetieto->UnitPrice->Amount) and $tuotetieto->UnitPrice->Amount != '') {
					$rtuoteno[$i]["hinta"] = (float) str_replace(",", ".", $tuotetieto->UnitPrice->Amount);
				}
				else {
					$rtuoteno[$i]["hinta"] = $rtuoteno[$i]["rivihinta"]  / $rtuoteno[$i]["kpl"] ;
				}

				// otetaan talteen "AllowOrCharge" kentät ja tallennetaan ne johonkin
				if (isset($tuotetieto->AllowOrCharge->AllowOrChargeIdentifier)) {
					$attribute = $tuotetieto->AllowOrCharge->AllowOrChargeIdentifier->attributes()->Value;

					if (strtolower($attribute) == "allow") {
						if (!isset($rtuoteno[$i]['ale1'])) {
							if (isset($tuotetieto->AllowOrCharge->Percent)) {
								$rtuoteno[$i]['ale1'] = $tuotetieto->AllowOrCharge->Percent;
							}
							else {
								$rtuoteno[$i]['ale1'] = round($tuotetieto->AllowOrCharge->Amount / $rtuoteno[$i]['rivihinta'] * 100, 2);
							}
						}
						elseif (!isset($rtuoteno[$i]['ale2'])) {
							if (isset($tuotetieto->AllowOrCharge->Percent)) {
								$rtuoteno[$i]['ale2'] = $tuotetieto->AllowOrCharge->Percent;
							}
							else {
								$rtuoteno[$i]['ale2'] = round($tuotetieto->AllowOrCharge->Amount / $rtuoteno[$i]['rivihinta'] * 100, 2);
							}
						}
						else {

							if (!isset($rtuoteno[$i]['ale3'])) $rtuoteno[$i]['ale3'] = 0;

							if (isset($tuotetieto->AllowOrCharge->Percent)) {
								// 100-(1-50/100)*(1-50/100)*100
								if (isset($rtuoteno[$i]['ale3'])) {
									$rtuoteno[$i]['ale3'] = round(100 - (1 - $rtuoteno[$i]['ale3'] / 100) * (1 - $tuotetieto->AllowOrCharge->Percent / 100) * 100, 2);
									}
								else {
									$rtuoteno[$i]['ale3'] = $tuotetieto->AllowOrCharge->Percent;
								}
							}
							else {
								// 100-(1-50/100)*(1-50/100)*100
								if (isset($rtuoteno[$i]['ale3'])) {
									$rtuoteno[$i]['ale3'] = round(100 - (1 - $rtuoteno[$i]['ale3'] / 100) * ($tuotetieto->AllowOrCharge->Amount / $rtuoteno[$i]['rivihinta'] * 100) * 100, 2);
								}
								else {
									$rtuoteno[$i]['ale3'] = round($tuotetieto->AllowOrCharge->Amount / $rtuoteno[$i]['hinta'] * 100, 2);
								}
							}
						}
					}
					elseif (strtolower($attribute) == "charge") {
						if (strtolower($tuotetieto->AllowOrCharge->AllowOrChargeDescription) == "bx runkoarvo") {
							$rtuoteno[$i]["lisakulu"] = (float) str_replace(",", ".", $tuotetieto->AllowOrCharge->Amount);
						}
						else {
							if (!isset($rtuoteno[$i]["kulu"])) $rtuoteno[$i]["kulu"] = 0;
							$rtuoteno[$i]["kulu"] += (float) str_replace(",", ".", $tuotetieto->AllowOrCharge->Amount);
						}
					}
				}

				if (isset($tuotetieto->DesAdvRef->DocumentNumber)) {
					$rtuoteno[$i]["documentnumber"] = $tuotetieto->DesAdvRef->DocumentNumber;
				}

				$ealvi[$i] = 0; // Oletetaan aina ALV 0%
				$rtuoteno[$i]["alv"] = 0; // Oletetaan aina ALV 0%
				$rtuoteno[$i]["nimitys"] = utf8_decode($tuotetieto->ProductDescription->ProductName1);
			}
			else {
				//	Poistetaan tämä tietue
				unset($rtuoteno[$i]);
				$i--;
			}
			$i++;
		}
	}

	error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
	ini_set("display_errors", 0);

?>