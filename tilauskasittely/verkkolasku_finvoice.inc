<?php

if (!function_exists('finvoice_otsik')) {
	function finvoice_otsik($tootfinvoice, $lasrow, $silent, $tulos_ulos) {
		global $yhtiorow;
	
		//varmuudeksi alku aina puhtaalta pöydältä
		$senderpartyid 			= '';
		$senderintermediator 	= '';
		$receiverpartyid 		= '';
		$receiverintermediator 	= '';

		//Tunetamton muuttuja $mid
		$mid = "";

		if($yhtiorow["verkkolasku_lah"] == "iban+soap") {
			//tehdään Finvoicen SOAP-Envelope ibantunnuksella

			$senderpartyid 			= str_replace(" ", "", $yhtiorow['pankkiiban1']);
			$senderintermediator	= $yhtiorow['pankkiswift1'];
		}
		elseif($yhtiorow["verkkolasku_lah"] == "ovt+soap") {
			//tehdään Finvoicen SOAP-Envelope ibantunnuksella

			$senderpartyid 			= str_replace(" ", "", $yhtiorow['ovttunnus']);
			$senderintermediator	= $yhtiorow['pankkiswift1'];
		}

		//	Aloitellaan aineiston luontia
		if($senderpartyid != "" and $senderintermediator != "") {

			list($receiverpartyid, $receiverintermediator) = explode("@",$lasrow["verkkotunnus"]);

			// jos tänne ei saada mitään arvoja laitetaan se edelleen pankkiin, mutta annetaan lipuksi tulostukseen
			if($receiverpartyid == "" or $receiverintermediator == "" or $lasrow["chn"] == 100) {
				$receiverpartyid 		= "tulostukseen";
				$receiverintermediator 	= $yhtiorow['pankkiswift1'];

				if($lasrow["chn"] != 100 and $silent == "") {
					$tulos_ulos .= "<font class='error'>".t("Asiakkaalta puuttuu verkkotunnus, lasku menee tulostukseen.")." $lasrow[nimi] $lasrow[laskunro]</font><br>\n<br>\n";
				}
			}

			// 	Tehdään SOAP
			fputs ($tootfinvoice, "<SOAP-ENV:Envelope xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" xmlns:eb=\"http://www.oasis-open.org/committees/ebxml-msg/schema/msg-header-2_0.xsd\">\n");
			fputs ($tootfinvoice, "<SOAP-ENV:Header>\n");
			fputs ($tootfinvoice, "<eb:MessageHeader xmlns:eb=\"http://www.oasis-open.org/committees/ebxml-msg/schema/msg-header-2_0.xsd\" SOAP-ENV:mustUnderstand=\"1\" eb:version=\"2.0\">\n");

			fputs ($tootfinvoice, "<eb:From>\n");
			xml_add("eb:PartyId", $senderpartyid ,								$tootfinvoice);
			xml_add("eb:Role", "Sender", 										$tootfinvoice);
			fputs ($tootfinvoice, "</eb:From>\n");

			fputs ($tootfinvoice, "<eb:From>\n");
			xml_add("eb:PartyId", $senderintermediator ,						$tootfinvoice);
			xml_add("eb:Role", "Intermediator",									$tootfinvoice);
			fputs ($tootfinvoice, "</eb:From>\n");

			fputs ($tootfinvoice, "<eb:To>\n");
			xml_add("eb:PartyId", $receiverpartyid ,							$tootfinvoice);
			xml_add("eb:Role", "Receiver",										$tootfinvoice);
			fputs ($tootfinvoice, "</eb:To>\n");

			fputs ($tootfinvoice, "<eb:To>\n");
			xml_add("eb:PartyId", $receiverintermediator ,						$tootfinvoice);
			xml_add("eb:Role", "Intermediator",									$tootfinvoice);
			fputs ($tootfinvoice, "</eb:To>\n");

			xml_add("eb:CPAId", "yoursand-mycpa", 								$tootfinvoice); //	Ei ilmeisesti juuri merkittävä
			xml_add("eb:ConversationId", "nnnnn", 								$tootfinvoice);	// 	Ei ilmeisesti juuri merkittävä
			xml_add("eb:Service", "Routing",									$tootfinvoice); //	Ei ilmeisesti juuri merkittävä
			xml_add("eb:Action", "ProcessInvoice",								$tootfinvoice);
			fputs ($tootfinvoice, "<eb:MessageData>\n");
			xml_add("eb:MessageId", date("YmdHis")."-".$lasrow['laskunro'], 	$tootfinvoice);
			xml_add("eb:Timestamp", date("Y-m-d")."T".date("H:i:s")."+02", 		$tootfinvoice);
			fputs ($tootfinvoice, "<eb:RefToMessageId/>\n");
			fputs ($tootfinvoice, "</eb:MessageData>\n");
			fputs ($tootfinvoice, "</eb:MessageHeader>\n");
			fputs ($tootfinvoice, "</SOAP-ENV:Header>\n");
			fputs ($tootfinvoice, "<SOAP-ENV:Body>\n");
			fputs ($tootfinvoice, "<eb:Manifest eb:id=\"Manifest\" eb:version=\"2.0\">\n");
			fputs ($tootfinvoice, "<eb:Reference eb:id=\"Finvoice\" xlink:href=\"$mid\">\n");
			fputs ($tootfinvoice, "<eb:schema eb:location=\"http://www.pankkiyhdistys.fi/verkkolasku/finvoice/finvoice.xsd\" eb:version=\"2.0\"/>\n");
			fputs ($tootfinvoice, "</eb:Reference>\n");
			fputs ($tootfinvoice, "</eb:Manifest>\n");
			fputs ($tootfinvoice, "</SOAP-ENV:Body>\n");
			fputs ($tootfinvoice, "</SOAP-ENV:Envelope>\n");

			//laitetaan setit kuntoon..
			fputs($tootfinvoice, "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n");
			fputs($tootfinvoice, "<!DOCTYPE Finvoice SYSTEM \"Finvoice.dtd\">\n");
			fputs($tootfinvoice, "<?xml-stylesheet type=\"text/xsl\" href=\"Finvoice.xsl\"?>\n");

			//tästä lähtee Finvoice koodi
			fputs($tootfinvoice, "<Finvoice Version=\"1.2\">\n");
			fputs($tootfinvoice, "<SellerPartyDetails>\n");
			xml_add("SellerPartyIdentifier",											ymuuta($yhtiorow['ytunnus']), 							$tootfinvoice);
			xml_add("SellerPartyIdentifierUrlText",										"http://www.ytj.fi/yrit_sel2.asp?kielikoodi=1",			$tootfinvoice);
			xml_add("SellerOrganisationName",											$yhtiorow['nimi'],										$tootfinvoice);
			xml_add("SellerOrganisationTaxCode", 										ymuuta($yhtiorow['ytunnus']),							$tootfinvoice);
			xml_add("SellerOrganisationTaxCodeUrlText",									"http://europa.eu.int/comm/taxation_customs/vies/fi/vieshome.htm", 	$tootfinvoice);

			fputs($tootfinvoice, "<SellerPostalAddressDetails>\n");
			xml_add("SellerStreetName", 												$yhtiorow['osoite'], 									$tootfinvoice);
			xml_add("SellerTownName", 													$yhtiorow['postitp'], 									$tootfinvoice);
			xml_add("SellerPostCodeIdentifier", 										$yhtiorow['postino'], 									$tootfinvoice);
			xml_add("CountryCode",														$yhtiorow['maakoodi'],									$tootfinvoice);
			xml_add("CountryName",														$yhtiorow['maa'], 										$tootfinvoice);
			fputs($tootfinvoice, "</SellerPostalAddressDetails>\n");

			fputs($tootfinvoice, "</SellerPartyDetails>\n");

			//xml_add("SellerOrganisationUnitNumber",									$yhtiorow["ovttunnus"], 								$tootfinvoice);

			xml_add("SellerContactPersonName",											$lasrow["laatija"], 									$tootfinvoice);

			fputs($tootfinvoice, "<SellerCommunicationDetails>\n");
			xml_add("SellerEmailaddressIdentifier",										$yhtiorow['email'], 									$tootfinvoice);
			fputs($tootfinvoice, "</SellerCommunicationDetails>\n");

			fputs($tootfinvoice, "<SellerInformationDetails>\n");
			xml_add("SellerHomeTownName",												$yhtiorow['kotipaikka'],								$tootfinvoice);
			xml_add("SellerPhoneNumber", 												$yhtiorow['puhelin'], 									$tootfinvoice);
			xml_add("SellerFaxNumber", 													$yhtiorow['fax'], 										$tootfinvoice);
			xml_add("SellerCommonEmailaddressIdentifier",								$yhtiorow['email'], 									$tootfinvoice);
			xml_add("SellerWebaddressIdentifier",										$yhtiorow['www'],   									$tootfinvoice);
			xml_add("SellerFreeText",   		                     					$yhtiorow['laskun_vapaakentta'],      					$tootfinvoice);

			fputs($tootfinvoice, "<SellerAccountDetails>\n");
			xml_add('SellerAccountID IdentificationSchemeName="IBAN"',					$yhtiorow['pankkiiban1'],								$tootfinvoice);
			xml_add('SellerBic IdentificationSchemeName="BIC"',							$yhtiorow['pankkiswift1'],								$tootfinvoice);
			fputs($tootfinvoice, "</SellerAccountDetails>\n");

			if ($yhtiorow['pankkiiban2']!='') {
				fputs($tootfinvoice, "<SellerAccountDetails>\n");
				xml_add('SellerAccountID IdentificationSchemeName="IBAN"',				$yhtiorow['pankkiiban2'],       						$tootfinvoice);
				xml_add('SellerBic IdentificationSchemeName="BIC"',						$yhtiorow['pankkiswift2'],      						$tootfinvoice);
				fputs($tootfinvoice, "</SellerAccountDetails>\n");
			}
			if ($yhtiorow['pankkiiban3']!='') {
				fputs($tootfinvoice, "<SellerAccountDetails>\n");
				xml_add('SellerAccountID IdentificationSchemeName="IBAN"',				$yhtiorow['pankkiiban3'],       						$tootfinvoice);
				xml_add('SellerBic IdentificationSchemeName="BIC"',						$yhtiorow['pankkiswift3'],     							$tootfinvoice);
				fputs($tootfinvoice, "</SellerAccountDetails>\n");
			}


			fputs($tootfinvoice, "<InvoiceRecipientDetails>\n");
			xml_add("InvoiceRecipientAddress",											$yhtiorow['pankkiiban1'],		  						$tootfinvoice);
			xml_add("InvoiceRecipientIntermediatorAddress",								$yhtiorow['pankkiswift1'],                 				$tootfinvoice);
			fputs($tootfinvoice, "</InvoiceRecipientDetails>\n");

			fputs($tootfinvoice, "</SellerInformationDetails>\n");

			fputs($tootfinvoice, "<BuyerPartyDetails>\n");
			xml_add("BuyerPartyIdentifier",				  								ymuuta($lasrow['ytunnus']), 							$tootfinvoice);
			xml_add("BuyerOrganisationName",                     						$lasrow['nimi'],    									$tootfinvoice);
			xml_add("BuyerOrganisationTaxCode",                     					$lasrow['maa']."".ymuuta($lasrow['ytunnus']),    		$tootfinvoice);
			//xml_add("BuyerOrganisationUnitNumber",                    				$lasrow['ovttunnus'],    								$tootfinvoice);

			fputs($tootfinvoice, "<BuyerPostalAddressDetails>\n");
			xml_add("BuyerStreetName",													$lasrow['osoite'],     									$tootfinvoice);
			xml_add("BuyerTownName",                                  					$lasrow['postitp'],      								$tootfinvoice);
			xml_add("BuyerPostCodeIdentifier",                         					$lasrow['postino'],     								$tootfinvoice);
			fputs($tootfinvoice, "</BuyerPostalAddressDetails>\n");

			fputs($tootfinvoice, "</BuyerPartyDetails>\n");

			fputs($tootfinvoice, "<DeliveryPartyDetails>\n");

			fputs($tootfinvoice, "<DeliveryPartyIdentifier/>\n");
			xml_add("DeliveryOrganisationName",                            				$lasrow['toim_nimi'],     								$tootfinvoice);

			fputs($tootfinvoice, "<DeliveryPostalAddressDetails>\n");
			xml_add("DeliveryStreetName",                             					$lasrow['toim_osoite'],     							$tootfinvoice);
			xml_add("DeliveryTownName",                                 				$lasrow['toim_postitp'],     							$tootfinvoice);
			xml_add("DeliveryPostCodeIdentifier",                     					$lasrow['toim_postino'],								$tootfinvoice);
			fputs($tootfinvoice, "</DeliveryPostalAddressDetails>\n");

			fputs($tootfinvoice, "</DeliveryPartyDetails>\n");

			fputs($tootfinvoice, "<DeliveryDetails>\n");
			xml_add('DeliveryDate Format="CCYYMMDD"',                   				vlas_dateconv($lasrow['toimaika']),						$tootfinvoice);
			xml_add("DeliveryMethodText",                                     			$lasrow['toimitustapa'],          						$tootfinvoice);
			fputs($tootfinvoice, "</DeliveryDetails>\n");

			fputs($tootfinvoice, "<InvoiceDetails>\n");
			//tsekataan laskun tyyppikoodi INV
			if ($lasrow['arvo']>=0)	{
				xml_add("InvoiceTypeCode",												"INV01",      		      								$tootfinvoice);
				xml_add("InvoiceTypeText",												"LASKU",      		      								$tootfinvoice);
			}
			else {
				xml_add("InvoiceTypeCode",												"INV02",      		      								$tootfinvoice);
				xml_add("InvoiceTypeText",												"HYVITYSLASKU",      		      						$tootfinvoice);
			}

			xml_add("OriginCode",              	                   						"Original",                  					 		$tootfinvoice);
			xml_add("InvoiceNumber",       	 	                     					$lasrow['laskunro'],  									$tootfinvoice);
			xml_add('InvoiceDate Format="CCYYMMDD"',									vlas_dateconv($lasrow['tapvm']),										$tootfinvoice);
			xml_add("OrderIdentifier",													$lasrow['tunnus'],														$tootfinvoice);
			xml_add("InvoiceTotalVatExcludedAmount AmountCurrencyIdentifier=\"$lasrow[valkoodi]\"", pp($lasrow['arvo']),									$tootfinvoice);
			xml_add("InvoiceTotalVatAmount AmountCurrencyIdentifier=\"$lasrow[valkoodi]\"",			pp(round($lasrow['summa']-$lasrow['arvo'], 2)),			$tootfinvoice);
			xml_add("InvoiceTotalVatIncludedAmount AmountCurrencyIdentifier=\"$lasrow[valkoodi]\"",	pp($lasrow['summa']),									$tootfinvoice);

		}
		elseif($silent == "") {
			$tulos_ulos .= t("Finvoiceaineistoa ei voitu luoda. Yhtiolta puuttuu ovttunnus, SWIFT tai IBAN tunnus. Tulosta lasku manuaalisesti!")." ".$lasrow["laskunro"]."<br>\n<br>\n";
		}
	}
}

if (!function_exists('finvoice_alvierittely')) {
	function finvoice_alvierittely($tootfinvoice, $lasrow, $alvrow) {
	
		fputs($tootfinvoice, "<VatSpecificationDetails>\n");
		xml_add("VatBaseAmount AmountCurrencyIdentifier=\"$lasrow[valkoodi]\"",	pp($alvrow[1]), 				$tootfinvoice);
		xml_add("VatRatePercent",												pp($alvrow[0]), 				$tootfinvoice);
		xml_add("VatRateAmount AmountCurrencyIdentifier=\"$lasrow[valkoodi]\"",	pp($alvrow[2]), 				$tootfinvoice);
		fputs($tootfinvoice, "</VatSpecificationDetails>\n");
	}
}

if (!function_exists('finvoice_otsikko_loput')) {
	function finvoice_otsikko_loput($tootfinvoice, $lasrow, $masrow) {
	
		fputs($tootfinvoice, "<PaymentTermsDetails>\n");
		xml_add("PaymentTermsFreeText",       											pp($masrow['teksti']." ".$masrow['kassa_teksti']),			$tootfinvoice);
		xml_add('InvoiceDueDate Format="CCYYMMDD"',               						pp(vlas_dateconv($lasrow['tapvm'])),   	       				$tootfinvoice);

		fputs($tootfinvoice, "<PaymentOverDueFineDetails>\n");
		xml_add("PaymentOverDueFineFreeText",											"viivästyskorko ".pp($lasrow['viikorkopros']),				$tootfinvoice);
		xml_add("PaymentOverDueFinePercent",                 						 	pp($lasrow['viikorkopros']),   								$tootfinvoice);
		fputs($tootfinvoice, "</PaymentOverDueFineDetails>\n");

		fputs($tootfinvoice, "</PaymentTermsDetails>\n");

		fputs($tootfinvoice, "</InvoiceDetails>\n");

		fputs($tootfinvoice, "<PaymentStatusDetails>\n");
		xml_add("PaymentStatusCode", 													"NOTPAID", 													$tootfinvoice);
		fputs($tootfinvoice, "</PaymentStatusDetails>\n");
	}
}

if (!function_exists('finvoice_rivi')) {
	function finvoice_rivi($tootfinvoice, $tilrow, $lasrow, $vatamount, $totalvat) {
	
		fputs($tootfinvoice, "<InvoiceRow>\n");
		xml_add("ArticleIdentifier",                                   				$tilrow['tuoteno'],         	$tootfinvoice);
		xml_add("ArticleName",                                    					$tilrow['nimitys'],  			$tootfinvoice);
		xml_add("DeliveredQuantity QuantityUnitCode=\"$tilrow[yksikko]\"",			pp($tilrow['kpl']),      		$tootfinvoice);
		xml_add("UnitPriceAmount AmountCurrencyIdentifier=\"$lasrow[valkoodi]\"",	pp($tilrow['hinta']),        	$tootfinvoice);

		if($tilrow["tilaajanrivinro"] != 0) {
			xml_add("RowIdentifier",                                    			$tilrow['tilaajanrivinro'], 	$tootfinvoice); // tänne laitetaan asiakkaan rivinumero, niin saavat parseroida senkin laskuista
		}
		if ($tilrow['kommentti']!='') {
			xml_add("RowFreeText",    												$tilrow['kommentti'],			$tootfinvoice);
		}

		xml_add("RowVatRatePercent",                                    			pp($tilrow['alv']),           	$tootfinvoice);
		xml_add("RowVatAmount AmountCurrencyIdentifier=\"$val\"",          			pp($vatamount),      			$tootfinvoice);	// veron määrä
		xml_add("RowVatExcludedAmount AmountCurrencyIdentifier=\"$val\"",  			pp($tilrow['rivihinta']),      	$tootfinvoice);	// veroton rivihinta
		xml_add("RowAmount AmountCurrencyIdentifier=\"$val\"",             			pp($totalvat),     				$tootfinvoice);	// verollinen rivihinta
		fputs($tootfinvoice, "</InvoiceRow>\n");
	}
}

if (!function_exists('finvoice_lasku_loppu')) {
	function finvoice_lasku_loppu($tootfinvoice, $lasrow) {
		global $yhtiorow;
	
		fputs($tootfinvoice, "<EpiDetails>\n");

		fputs($tootfinvoice, "<EpiIdentificationDetails>\n");
		xml_add('EpiDate Format="CCYYMMDD"',                   							vlas_dateconv($lasrow['tapvm']),	$tootfinvoice);
		xml_add("EpiReference",                               							$lasrow['viite'],					$tootfinvoice);
		fputs($tootfinvoice, "</EpiIdentificationDetails>\n");

		fputs($tootfinvoice, "<EpiPartyDetails>\n");

		fputs($tootfinvoice, "<EpiBfiPartyDetails>\n");
		xml_add('EpiBfiIdentifier IdentificationSchemeName="BIC"',						$yhtiorow['pankkiswift1'],			$tootfinvoice);
		fputs($tootfinvoice, "</EpiBfiPartyDetails>\n");

		fputs($tootfinvoice, "<EpiBeneficiaryPartyDetails>\n");
		xml_add("EpiNameAddressDetails",    											$yhtiorow['nimi'],					$tootfinvoice);
		xml_add("EpiBei",                         										ymuuta($yhtiorow['ytunnus']),  		$tootfinvoice);
		xml_add("EpiAccountID IdentificationSchemeName=\"BBAN\"",						$yhtiorow['pankkitili1'],			$tootfinvoice);
		fputs($tootfinvoice, "</EpiBeneficiaryPartyDetails>\n");

		fputs($tootfinvoice, "</EpiPartyDetails>\n");

		fputs($tootfinvoice, "<EpiPaymentInstructionDetails>\n");
		xml_add("EpiRemittanceInfoIdentifier IdentificationSchemeName=\"SPY\"",			spyconv($lasrow['viite']),    		$tootfinvoice);
		xml_add("EpiInstructedAmount AmountCurrencyIdentifier=\"$lasrow[valkoodi]\"", pp($lasrow['summa']),         		$tootfinvoice);
		xml_add("EpiCharge ChargeOption=\"SHA\"", 										"SHA",								$tootfinvoice);
		xml_add('EpiDateOptionDate Format="CCYYMMDD"',                					vlas_dateconv($lasrow['erpcm']),	$tootfinvoice);
		fputs($tootfinvoice, "</EpiPaymentInstructionDetails>\n");

		fputs($tootfinvoice, "</EpiDetails>\n");

		fputs($tootfinvoice, "</Finvoice>\n");
	}
}

?>