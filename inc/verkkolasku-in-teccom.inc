<?php

	// Bosch Teccom -case
	$xml = isset($xml->ArchivedDocument->Invoice) ? $xml->ArchivedDocument->Invoice : $xml;

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
	$laskun_pankkiviite				= "";
	$laskuttajan_vat				= "";
	$laskun_asiakastunnus			= "";
	$laskun_pakkauskulu				= 0;
	$laskun_rahtikulu				= 0;

	/*
	 * osto_kulu = $laskun_pakkauskulu
	 * osto_rahti = invoiceSummary rahti
	 * osto_rivi_kulu  = ($laskun_rahtikulu, $rtuoteno[$i]['kulu'])
	 */
	$osto_kulu = 0;
	$osto_rahti = 0;
	$osto_rivi_kulu = 0;

	$ei_oteta_kulua_pois = false;

	if (strtoupper($laskuttajan_toimittajanumero) == "ELRING") {
		$laskuttajan_toimittajanumero = "123312";
	}
	elseif (strtoupper($laskuttajan_toimittajanumero) == "BOSCH" OR strtoupper($laskuttajan_toimittajanumero) == "AA_FI") {
		$laskuttajan_toimittajanumero = "123067";

		// Tarkistusnumero
		$viite = $laskun_numero;

		// Menee tasan samalla systeemilla kuin viitenumeron tarkistusnumeron laskeminen..
		include("inc/generoiviite.inc");

		$laskun_pankkiviite = $viite;
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
	elseif ($laskuttajan_toimittajanumero == "123035") {
		$laskuttajan_toimittajanumero = "123036";
	}

	if (isset($xml->InvoiceSummary->InvoiceTotals->InvoiceAmountPayable->Amount)  and $xml->InvoiceSummary->InvoiceTotals->InvoiceAmountPayable->Amount != 0) {
		$laskun_summa_eur = (float) str_replace(",", ".", $xml->InvoiceSummary->InvoiceTotals->InvoiceAmountPayable->Amount);
	}
	elseif (isset($xml->InvoiceSummary->InvoiceTotals->InvoiceNetValue->Amount) and $xml->InvoiceSummary->InvoiceTotals->InvoiceNetValue->Amount != 0) {
		$laskun_summa_eur = (float) str_replace(",", ".", $xml->InvoiceSummary->InvoiceTotals->InvoiceNetValue->Amount);
	}
	else {
		$laskun_summa_eur = (float) str_replace(",", ".", $xml->InvoiceSummary->InvoiceTotals->InvoiceGrossValue->Amount);
	}

	if (isset($xml->InvoiceSummary->InvoiceTotals->InvoiceCharge->Amount) and $xml->InvoiceSummary->InvoiceTotals->InvoiceCharge->Amount != 0) {
		$laskun_pakkauskulu = (float) str_replace(",", ".", $xml->InvoiceSummary->InvoiceTotals->InvoiceCharge->Amount);
		$osto_kulu = $laskun_pakkauskulu;
		$osto_kulu_alv = $laskun_alv;

		if ($laskuttajan_toimittajanumero == "123441") {
			$ei_oteta_kulua_pois = true;
		}
	}

	$laskun_alv = 0;

	if (isset($xml->InvoiceSummary->Tax->Percent) and $xml->InvoiceSummary->Tax->Percent != 0) {
		$laskun_alv = (float) str_replace(",", ".", $xml->InvoiceSummary->Tax->Percent);
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
		if (is_array($rivi)) {
			$rivi = array_unique($rivi); // otetaan uniikit
			$rivi = array_values($rivi); // poistetaan turhat välit
			$laskun_tilausviite = implode(",",$rivi);
		}
	}

	$onko_hyvityslasku_kerroin = 1;

	// Boschin hyvityslasku -keissi
	if ($laskuttajan_toimittajanumero == "123067" and isset($xml->InvoiceHeader->FreeText) and isset($xml->InvoiceHeader->FreeText->attributes()->Caption) and $xml->InvoiceHeader->FreeText->attributes()->Caption == 'C') {
		$onko_hyvityslasku_kerroin = -1;
		$laskun_summa_eur *= $onko_hyvityslasku_kerroin;
	}

	$laskun_asiakkaan_tilausnumero 	= "";
	$kauttalaskutus 				= "";
	$laskun_toimitunnus				= 0;

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

	if (isset($xml->InvoiceHeader->Currency)) {
		$laskuttajan_valkoodi = $xml->InvoiceHeader->Currency;
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
	$x 				= 0;
	$rtuoteno		= array();
	$ealvi		 	= array("");
	$lisavat		= array("");
	$aputuoteno		= "";
	$pituus			= "";

	if (count($tuotetiedot) > 0) {
		foreach ($tuotetiedot as $tuotetieto) {
			// tuotekoodi
			// Tehdään poikkeustapaksien käsittely
			if ($laskuttajan_toimittajanumero == "123085") {
				$rtuoteno[$i]["tuoteno"] = $rtuoteno[$i]["tuoteno2"] = utf8_decode($tuotetieto->ProductDescription->ProductName1);
			}
			elseif ($laskuttajan_toimittajanumero == "123067") {
				$rtuoteno[$i]["tuoteno"] = substr(utf8_decode($tuotetieto->ProductId->ProductNumber),0,-3);
				$rtuoteno[$i]["tuoteno2"] = "{$rtuoteno[$i]["tuoteno"]}090";
			}
			elseif ($laskuttajan_toimittajanumero == "123007") {
				$aputuoteno = utf8_decode($tuotetieto->ProductId->ProductNumber);
				$pituus 	= strlen(trim($aputuoteno));

				if ($pituus == 8 and strpos(' ',$aputuoteno) == 6) {
					$rtuoteno[$i]["tuoteno"] = $rtuoteno[$i]["tuoteno2"] = substr($aputuoteno,0,5);
				}
				else {
					$rtuoteno[$i]["tuoteno"] = $rtuoteno[$i]["tuoteno2"] = $aputuoteno;
				}
			}
			elseif ($laskuttajan_toimittajanumero == "123453") {
				if (strpos($tuotetieto->ProductId->ProductNumber, "-") !== FALSE) {
					$rtuoteno[$i]["tuoteno"] = str_replace("-", "", $tuotetieto->ProductId->ProductNumber);
				}
				else {
					$rtuoteno[$i]["tuoteno"] = substr($tuotetieto->ProductId->ProductNumber,0,3)."-".substr($tuotetieto->ProductId->ProductNumber,3);
				}

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
			}

			if ($rtuoteno[$i]["tuoteno"] != "") {

				$rtuoteno[$i]['status'] = '';

				// Tallennetaan riville jos tämä on kauttalaskutusta
				if ($kauttalaskutus == "KAUTTALASKUTUS") {
					$rtuoteno[$i]["kauttalaskutus"] = "KAUTTALASKUTUS";
				}

				$rtuoteno[$i]["ostotilausnro"] = (string) $tuotetieto->OrderRef->BuyerOrderNumber;

				$rtuoteno[$i]["tilaajanrivinro"] = (string) $tuotetieto->OrderItemRef->BuyerOrderItemRef;

				$rtuoteno[$i]["kpl"] = (int) str_replace(",", ".", $tuotetieto->Quantity);

				if (isset($tuotetieto->TotalValue->Amount)) {
					$rtuoteno[$i]["rivihinta"] = (float) str_replace(",", ".", $tuotetieto->TotalValue->Amount); // summa
				}
				else {
					$rtuoteno[$i]["rivihinta"] = (float) str_replace(",", ".", $tuotetieto->TotalPrice->Amount); // summa
				}

				// ZF ja GKN
				if (isset($tuotetieto->AllowOrCharge->AllowOrChargeIdentifier) and (float) trim($tuotetieto->AllowOrCharge->Amount) != 0
					and ($laskuttajan_toimittajanumero == '123007' or $laskuttajan_toimittajanumero == "123084")) {

					$node_search = $tuotetieto->xpath(".//AllowOrCharge");

					foreach ($node_search as $node_x) {

						$attribute = $node_x->AllowOrChargeIdentifier->attributes()->Value;

						if (strtolower($attribute) == "charge") {

							$charge_description = strtolower(trim($node_x->AllowOrChargeDescription));
							$charge_code = strtolower(trim($node_x->AllowOrChargeCode));

							if ($charge_code == "zcwr" or $charge_code == 'zfwa' or strpos($charge_description, "core value") !== FALSE) {
								$rtuoteno[$i]['rivihinta'] -= (float) str_replace(",", ".", $node_x->Amount);
							}
						}
					}
				}

				// BehrHella
				if ($laskuttajan_toimittajanumero == '123108' and isset($tuotetieto->TotalValue->Amount) and isset($tuotetieto->TotalPrice->Amount)) {

					$chk_sum = (float) str_replace(",", ".", $tuotetieto->TotalValue->Amount) - (float) str_replace(",", ".", $tuotetieto->TotalPrice->Amount);

					if ($chk_sum > 0) {
						$osto_rahti += $chk_sum;
					}
				}

				$rtuoteno[$i]["hinta"] = $rtuoteno[$i]["rivihinta"]  / $rtuoteno[$i]["kpl"];

				$query = "	SELECT tuotteen_toimittajat.tuotekerroin
							FROM toimi
							JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = toimi.yhtio
														AND tuotteen_toimittajat.liitostunnus = toimi.tunnus
														#AND tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno
														AND tuotteen_toimittajat.toim_tuoteno IN ('{$rtuoteno[$i]['tuoteno']}','{$rtuoteno[$i]['tuoteno2']}')
														AND tuotteen_toimittajat.toim_tuoteno != '')
							WHERE toimi.yhtio = '{$kukarow['yhtio']}'
							AND toimi.toimittajanro = '{$laskuttajan_toimittajanumero}'
							AND toimi.tyyppi != 'P'
							AND toimi.asn_sanomat IN ('K', 'L')";
				$tuotekerroin_chk_res = pupe_query($query);

				if (mysql_num_rows($tuotekerroin_chk_res) > 0) {
					$tuotekerroin_chk_row = mysql_fetch_assoc($tuotekerroin_chk_res);

					if ($tuotekerroin_chk_row['tuotekerroin'] != 0) {
						$rtuoteno[$i]['kpl'] /= $tuotekerroin_chk_row['tuotekerroin'];
					}
				}

				// otetaan talteen "AllowOrCharge" kentät.
				// täältä tulevat alennukset ja runko-, rahti- ja muutkulut
				if (isset($tuotetieto->AllowOrCharge->AllowOrChargeIdentifier) and ((float) trim($tuotetieto->AllowOrCharge->Amount) != 0 or (float) trim($tuotetieto->AllowOrCharge->Percent) != 0)) {

					if (isset($tuotetieto->UnitPrice->Amount) and $tuotetieto->UnitPrice->Amount != '') {
						$rtuoteno[$i]["hinta"] = (float) str_replace(",", ".", $tuotetieto->UnitPrice->Amount);
					}

					$node_search = $tuotetieto->xpath(".//AllowOrCharge");

					$ale1 = 0;
					$ale2 = 0;

					foreach ($node_search as $node) {

						$attribute = $node->AllowOrChargeIdentifier->attributes()->Value;

						// ei oteta alennuksia huomioon Klokkerholm-keississä
						// eikä ZF-keississä (123007)
						if (strtolower($attribute) == "allow" and $laskuttajan_toimittajanumero != "123418" and $laskuttajan_toimittajanumero != "123007") {

							if (!isset($rtuoteno[$i]['ale1'])) {

								if (isset($node->Percent)) {
									$rtuoteno[$i]['ale1'] = round($node->Percent, 2);
								}
								else {
									$ale1 = (float) str_replace(",", ".", $node->Amount);
									$rtuoteno[$i]['ale1'] = round(($ale1 / ($rtuoteno[$i]["hinta"] * $rtuoteno[$i]["kpl"])) * 100, 2);
								}
							}
							elseif (!isset($rtuoteno[$i]['ale2'])) {

								if (isset($node->Percent)) {
									$rtuoteno[$i]['ale2'] = round($node->Percent, 2);
								}
								else {
									$ale2 = (float) str_replace(",", ".", $node->Amount);
									$rtuoteno[$i]['ale2'] = round(($ale2 / (($rtuoteno[$i]["hinta"] * $rtuoteno[$i]["kpl"]) - $ale1) * 100), 2);
								}
							}
							else {

								if (isset($node->Percent)) {

									// 100-(1-50/100)*(1-50/100)*100
									if (isset($rtuoteno[$i]['ale3'])) {

										$rtuoteno[$i]['ale3'] = round(100 - (1 - $rtuoteno[$i]['ale3'] / 100) * (1 - (float) str_replace(",", ".", $node->Percent) / 100) * 100, 2);
									}
									else {
										$rtuoteno[$i]['ale3'] = round($node->Percent, 2);
									}
								}
								else {

									// 100-(1-50/100)*(1-50/100)*100
									if (isset($rtuoteno[$i]['ale3'])) {
										$rtuoteno[$i]['ale3'] = round(100 - (1 - $rtuoteno[$i]['ale3'] / 100) * (((float) str_replace(",", ".", $node->Amount) / (($rtuoteno[$i]["hinta"] * $rtuoteno[$i]["kpl"]) - $ale1 - $ale2)) * 100) * 100, 2);
									}
									else {
										$rtuoteno[$i]['ale3'] = round( ((float) str_replace(",", ".", $node->Amount) / (($rtuoteno[$i]["hinta"] * $rtuoteno[$i]["kpl"]) - $ale1 - $ale2)) * 100, 2);
									}
								}
							}
						}
						elseif (strtolower($attribute) == "charge") {

							$charge_description = strtolower(trim($node->AllowOrChargeDescription));
							$charge_code = strtolower(trim($node->AllowOrChargeCode));

							/*
							 * Runkotuotteet
							 * =============
							 *
							 * bx runkoarvo = BOSCH
							 * zcwr = ZF
							 * core value = GKN
							 */

							if (($laskuttajan_toimittajanumero == "123067" and $charge_description == "bx core value") or
								($laskuttajan_toimittajanumero == "123067" and $charge_description == "bx runkoarvo") or
								($laskuttajan_toimittajanumero == "123007" and $charge_code == "zcwr") or
								(in_array($laskuttajan_toimittajanumero, array("123084","123007")) and strpos($charge_description, "core value") !== FALSE)) {

								// Jos rivillä tulee runkoveloitus, niin pitää tehdä rungosta oma lapsirivi
								$query = "	SELECT tuote.tuoteno, tuote.nimitys
											FROM toimi
											JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = toimi.yhtio
												AND tuotteen_toimittajat.liitostunnus = toimi.tunnus
												AND tuotteen_toimittajat.toim_tuoteno IN ('{$rtuoteno[$i]['tuoteno']}','{$rtuoteno[$i]['tuoteno2']}')
												AND tuotteen_toimittajat.toim_tuoteno != '')
											JOIN tuoteperhe ON (tuoteperhe.yhtio = tuotteen_toimittajat.yhtio
												AND tuoteperhe.isatuoteno = tuotteen_toimittajat.tuoteno
												AND tuoteperhe.tyyppi IN ('P','')
												AND tuoteperhe.ohita_kerays != '')
											JOIN tuote ON (tuote.yhtio = tuoteperhe.yhtio AND tuote.tuoteno = tuoteperhe.tuoteno AND tuote.status != 'P')
											WHERE toimi.yhtio = '{$kukarow['yhtio']}'
											AND toimi.toimittajanro = '{$laskuttajan_toimittajanumero}'
											AND toimi.tyyppi != 'P'
											AND toimi.asn_sanomat IN ('K', 'L')";
								$child_chk_res = pupe_query($query);

								if (mysql_num_rows($child_chk_res) > 0) {

									$child_chk_row = mysql_fetch_assoc($child_chk_res);

									$x = $i + 1;

									$rtuoteno[$x]['tuoteno'] 			= $child_chk_row['tuoteno'];
									$rtuoteno[$x]['tuoteno2'] 			= $child_chk_row['tuoteno'];
									$rtuoteno[$x]['ostotilausnro'] 		= $rtuoteno[$i]['ostotilausnro'];
									$rtuoteno[$x]['tilaajanrivinro'] 	= $rtuoteno[$i]['tilaajanrivinro'];
									$rtuoteno[$x]['kpl'] 				= $rtuoteno[$i]['kpl'];

									// GKN-case: ilmoittavat yksikköhinnan Amountissa.
									if ($laskuttajan_toimittajanumero == "123084") {
										$rtuoteno[$x]['rivihinta']			= (float) str_replace(",", ".", $node->Amount) * $rtuoteno[$x]['kpl'];
										$rtuoteno[$x]['hinta']				= (float) str_replace(",", ".", $node->Amount);
										}
									else {
										$rtuoteno[$x]['rivihinta']			= (float) str_replace(",", ".", $node->Amount);
										$rtuoteno[$x]['hinta']				= $rtuoteno[$x]['rivihinta'] / $rtuoteno[$x]['kpl'];

										if ($laskuttajan_toimittajanumero == "123067") $rtuoteno[$i]['rivihinta'] -= $rtuoteno[$x]['rivihinta'];
									}

									$rtuoteno[$x]["nimitys"]			= $child_chk_row['nimitys'];
									$rtuoteno[$x]['status']				= '';
									$rtuoteno[$x]["alv"] 				= 0; // Oletetaan aina ALV 0%
									$ealvi[$x] 							= 0; // Oletetaan aina ALV 0%

									if ($onko_hyvityslasku_kerroin != 1) {
										$rtuoteno[$x]['rivihinta'] *= $onko_hyvityslasku_kerroin;
										$rtuoteno[$x]['hinta'] *= $onko_hyvityslasku_kerroin;
									}
								}
							}
							elseif ($charge_description == 'freight costs' or $charge_description == 'freight charges' or $charge_code == 'zfwa' or $charge_code == 'freightcharges') {

								/*
								 * Rahtikulu
								 * =========
								 *
								 * freight costs = BOSCH
								 * zfwa = ZF
								 */

								$laskun_rahtikulu += (float) str_replace(",", ".", $node->Amount);
								$osto_rivi_kulu += (float) str_replace(",", ".", $node->Amount);
								$osto_rivi_kulu_alv = $laskun_alv;

								// ZF -keississä otetaan rahti pois rivihinnasta.
								// Bosch -keississä ($charge_description == 'freight charges') otetaan myös rahti pois rivihinnasta
								if ($charge_code == 'freightcharges' or $charge_description == 'freight charges') {
									$rtuoteno[$i]['rivihinta'] -= (float) str_replace(",", ".", $node->Amount);
								}

								// Bosch -keissit
								if ($charge_description == 'freight costs' or $charge_code == 'freightcharges' or $charge_description == 'freight charges') {
									$ei_oteta_kulua_pois = true;
								}
							}
							else {

								/*
								 * Muut kulut
								 * ==========
								 *
								 * zpkp = ZF
								 * zpnp = ZF
								 * pn00 = GKN
								 */

								if (($laskuttajan_toimittajanumero == "123084" and $charge_code != 'pn00' and $charge_code != '') or ($laskuttajan_toimittajanumero != "123084" and $charge_code != 'zpkp' and $charge_code != 'zpnp')) {
									if (!isset($rtuoteno[$i]["kulu"])) $rtuoteno[$i]["kulu"] = 0;
									$rtuoteno[$i]["kulu"] += (float) str_replace(",", ".", $node->Amount);
									$osto_rivi_kulu += (float) str_replace(",", ".", $node->Amount);
									$osto_rivi_kulu_alv = $laskun_alv;
								}
							}
						}
					}
				}

				if (isset($tuotetieto->DesAdvRef->DocumentNumber)) {
					$rtuoteno[$i]["documentnumber"] = $tuotetieto->DesAdvRef->DocumentNumber;
				}

				$ealvi[$i] = 0; // Oletetaan aina ALV 0%
				$rtuoteno[$i]["alv"] = 0; // Oletetaan aina ALV 0%
				$rtuoteno[$i]["nimitys"] = utf8_decode($tuotetieto->ProductDescription->ProductName1);

				if ($onko_hyvityslasku_kerroin != 1) {
					$rtuoteno[$i]['rivihinta'] *= $onko_hyvityslasku_kerroin;
					$rtuoteno[$i]['hinta'] *= $onko_hyvityslasku_kerroin;
				}
			}
			else {
				//	Poistetaan tämä tietue
				unset($rtuoteno[$i]);
				$i--;
			}

			$i++;

			if ($x != 0) {
				$x = 0;
				$i++;
			}
		}

		if ($laskun_alv != 0) {
			$osto_kulu = round($osto_kulu * (1 + ($laskun_alv / 100)) * $onko_hyvityslasku_kerroin, 2);
			$osto_rahti = round($osto_rahti * (1 + ($laskun_alv / 100)) * $onko_hyvityslasku_kerroin, 2);
			$osto_rivi_kulu = round($osto_rivi_kulu * (1 + ($laskun_alv / 100)) * $onko_hyvityslasku_kerroin, 2);
		}

		if ($laskun_rahtikulu != 0 and $onko_hyvityslasku_kerroin != 1) $laskun_rahtikulu *= $onko_hyvityslasku_kerroin;

		// lasketaan mätsäävätkö rivihinnat ja laskunloppusumma, jos ei niin lisätään se kuluihin.
		// $chk_sum = 0;

		// for ($ix = 0; $ix < count($rtuoteno); $ix++) {
		// 	$chk_sum += $rtuoteno[$ix]['rivihinta'];
		// }

		// if ($chk_sum > 0 and $chk_sum != $laskun_summa_eur) {
		// 	$osto_kulu += (abs($chk_sum	- $laskun_summa_eur));
		// }

		// Valeo
		if ($laskuttajan_toimittajanumero == "123342") {
			$osto_rivi_kulu = 0;
		}
	}

	error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
	ini_set("display_errors", 0);
