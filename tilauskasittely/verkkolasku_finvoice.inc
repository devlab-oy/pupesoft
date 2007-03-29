<?php

if (!function_exists('finvoice_otsik')) {
	function finvoice_otsik($tootfinvoice, $lasrow, $masrow, $silent="", $tulos_ulos="") {
		global $yhtiorow, $err_finvoice, $tulos_ulos;

		//	Koitetaan ollaanko virheettˆmi‰
		//	Jos tapahtuu virhe, eli tietoja puuttuu niin tuota loppua ei saa tehd‰ tai koko aineisto voi menn‰ virheelliseksi!

		$err_finvoice = 0;

		//varmuudeksi alku aina puhtaalta pˆyd‰lt‰
		$senderpartyid 			= '';
		$senderintermediator 	= '';
		$receiverpartyid 		= '';
		$receiverintermediator 	= '';

		//	MessageID
		$mid = date("YmdHis")."-".$lasrow['laskunro'];

		// jos maksuehdon takana on annettu pankkiyhteystiedot, k‰ytet‰‰n niit‰ n‰in
		if ($masrow["pankkinimi1"] != "") {
			$yhtiorow["pankkinimi1"]	= $masrow["pankkinimi1"];
			$yhtiorow["pankkitili1"]	= $masrow["pankkitili1"];
			$yhtiorow["pankkiiban1"]	= $masrow["pankkiiban1"];
			$yhtiorow["pankkiswift1"]	= $masrow["pankkiswift1"];
			$yhtiorow["pankkinimi2"]	= $masrow["pankkinimi2"];
			$yhtiorow["pankkitili2"]	= $masrow["pankkitili2"];
			$yhtiorow["pankkiiban2"]	= $masrow["pankkiiban2"];
			$yhtiorow["pankkiswift2"]	= $masrow["pankkiswift2"];
			$yhtiorow["pankkinimi3"]	= $masrow["pankkinimi3"];
			$yhtiorow["pankkitili3"]	= $masrow["pankkitili3"];
			$yhtiorow["pankkiiban3"]	= $masrow["pankkiiban3"];
			$yhtiorow["pankkiswift3"]	= $masrow["pankkiswift3"];
		}

		if ($yhtiorow["verkkolasku_lah"] == "iban+soap") {
			//tehd‰‰n Finvoicen SOAP-Envelope ibantunnuksella
			$senderpartyid 			= str_replace(" ", "", $yhtiorow['pankkiiban1']);
			$senderintermediator	= $yhtiorow['pankkiswift1'];
		}
		elseif ($yhtiorow["verkkolasku_lah"] == "ovt+soap") {
			//tehd‰‰n Finvoicen SOAP-Envelope ibantunnuksella
			$senderpartyid 			= str_replace(" ", "", $yhtiorow['ovttunnus']);
			$senderintermediator	= $yhtiorow['pankkiswift1'];
		}
		
		//	Osataanko t‰m‰n k‰sitell‰ t‰m‰n pankin finvoiceaineistoa?
		switch ($yhtiorow['pankkiswift1']) {
			case 'NDEAFIHH':
	        	$tulostuspalvelu	= "tulostukseen";
				break;
			case 'PSPBFIHH':
	        	$tulostuspalvelu	= "003718062728810P";			
				break;
			default:
	        	$tulostuspalvelu	= "";			
				break;
		}

		//	Aloitellaan aineiston luontia
		if ($senderpartyid != "" and $senderintermediator != "" and $tulostuspalvelu != "") {

			list($receiverpartyid, $receiverintermediator) = explode("@",$lasrow["verkkotunnus"]);

			// jos t‰nne ei saada mit‰‰n arvoja laitetaan se edelleen pankkiin, mutta annetaan lipuksi tulostukseen
			if ($receiverpartyid == "" or $receiverintermediator == "" or $lasrow["chn"] == 100) {
				$receiverpartyid 		= $tulostuspalvelu;
				$receiverintermediator 	= $yhtiorow['pankkiswift1'];

				if ($lasrow["chn"] != 100 and $silent == "") {
					$tulos_ulos .= "<font class='error'>".t("Asiakkaalta puuttuu verkkotunnus, lasku menee tulostuspalveluun.")." $lasrow[nimi] $lasrow[laskunro]</font><br>\r\n<br>\r\n";
				}
			}

			// 	Tehd‰‰n SOAP
			fputs ($tootfinvoice, "<SOAP-ENV:Envelope xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" xmlns:eb=\"http://www.oasis-open.org/committees/ebxml-msg/schema/msg-header-2_0.xsd\">\r\n");
			fputs ($tootfinvoice, "<SOAP-ENV:Header>\r\n");
			fputs ($tootfinvoice, "<eb:MessageHeader xmlns:eb=\"http://www.oasis-open.org/committees/ebxml-msg/schema/msg-header-2_0.xsd\" SOAP-ENV:mustUnderstand=\"1\" eb:version=\"2.0\">\r\n");

			fputs ($tootfinvoice, "<eb:From>\r\n");
			xml_add("eb:PartyId", $senderpartyid ,								$tootfinvoice);
			xml_add("eb:Role", "Sender", 										$tootfinvoice);
			fputs ($tootfinvoice, "</eb:From>\r\n");

			fputs ($tootfinvoice, "<eb:From>\r\n");
			xml_add("eb:PartyId", $senderintermediator ,						$tootfinvoice);
			xml_add("eb:Role", "Intermediator",									$tootfinvoice);
			fputs ($tootfinvoice, "</eb:From>\r\n");

			fputs ($tootfinvoice, "<eb:To>\r\n");
			xml_add("eb:PartyId", $receiverpartyid ,							$tootfinvoice);
			xml_add("eb:Role", "Receiver",										$tootfinvoice);
			fputs ($tootfinvoice, "</eb:To>\r\n");

			fputs ($tootfinvoice, "<eb:To>\r\n");
			xml_add("eb:PartyId", $receiverintermediator ,						$tootfinvoice);
			xml_add("eb:Role", "Intermediator",									$tootfinvoice);
			fputs ($tootfinvoice, "</eb:To>\r\n");

			xml_add("eb:CPAId", "yoursand-mycpa", 								$tootfinvoice); //	Ei ilmeisesti juuri merkitt‰v‰
			xml_add("eb:ConversationId", "nnnnn", 								$tootfinvoice);	// 	Ei ilmeisesti juuri merkitt‰v‰
			xml_add("eb:Service", "Routing",									$tootfinvoice); //	Ei ilmeisesti juuri merkitt‰v‰
			xml_add("eb:Action", "ProcessInvoice",								$tootfinvoice);
			fputs ($tootfinvoice, "<eb:MessageData>\r\n");
			xml_add("eb:MessageId", $mid									, 	$tootfinvoice);
			xml_add("eb:Timestamp", date("Y-m-d")."T".date("H:i:s")."+02", 		$tootfinvoice);
			fputs ($tootfinvoice, "<eb:RefToMessageId/>\r\n");
			fputs ($tootfinvoice, "</eb:MessageData>\r\n");
			fputs ($tootfinvoice, "</eb:MessageHeader>\r\n");
			fputs ($tootfinvoice, "</SOAP-ENV:Header>\r\n");
			fputs ($tootfinvoice, "<SOAP-ENV:Body>\r\n");
			fputs ($tootfinvoice, "<eb:Manifest eb:id=\"Manifest\" eb:version=\"2.0\">\r\n");
			fputs ($tootfinvoice, "<eb:Reference eb:id=\"Finvoice\" xlink:href=\"$mid\">\r\n");
			fputs ($tootfinvoice, "<eb:schema eb:location=\"http://www.pankkiyhdistys.fi/verkkolasku/finvoice/finvoice.xsd\" eb:version=\"2.0\"/>\r\n");
			fputs ($tootfinvoice, "</eb:Reference>\r\n");
			fputs ($tootfinvoice, "</eb:Manifest>\r\n");
			fputs ($tootfinvoice, "</SOAP-ENV:Body>\r\n");
			fputs ($tootfinvoice, "</SOAP-ENV:Envelope>\r\n");

			//laitetaan setit kuntoon..
			fputs($tootfinvoice, "<?xml version=\"1.0\" encoding=\"ISO-8859-15\"?>\r\n");
			fputs($tootfinvoice, "<!DOCTYPE Finvoice SYSTEM \"Finvoice.dtd\">\r\n");
			fputs($tootfinvoice, "<?xml-stylesheet type=\"text/xsl\" href=\"Finvoice.xsl\"?>\r\n");

			//t‰st‰ l‰htee Finvoice koodi
			fputs($tootfinvoice, "<Finvoice Version=\"1.2\">\r\n");
			fputs($tootfinvoice, "<SellerPartyDetails>\r\n");
			xml_add("SellerPartyIdentifier",											ymuuta($yhtiorow['ytunnus']), 							$tootfinvoice);
			xml_add("SellerPartyIdentifierUrlText",										"http://www.ytj.fi/yrit_sel2.asp?kielikoodi=1",			$tootfinvoice);
			xml_add("SellerOrganisationName",											$yhtiorow['nimi'],										$tootfinvoice);
			xml_add("SellerOrganisationTaxCode", 										ymuuta($yhtiorow['ytunnus']),							$tootfinvoice);
			xml_add("SellerOrganisationTaxCodeUrlText",									"http://europa.eu.int/comm/taxation_customs/vies/fi/vieshome.htm", 	$tootfinvoice);

			fputs($tootfinvoice, "<SellerPostalAddressDetails>\r\n");
			xml_add("SellerStreetName", 												$yhtiorow['osoite'], 									$tootfinvoice);
			xml_add("SellerTownName", 													$yhtiorow['postitp'], 									$tootfinvoice);
			xml_add("SellerPostCodeIdentifier", 										$yhtiorow['postino'], 									$tootfinvoice);
			xml_add("CountryCode",														$yhtiorow['maakoodi'],									$tootfinvoice);
			xml_add("CountryName",														$yhtiorow['maa'], 										$tootfinvoice);
			fputs($tootfinvoice, "</SellerPostalAddressDetails>\r\n");

			fputs($tootfinvoice, "</SellerPartyDetails>\r\n");

			if($yhtiorow["ovttunnus"] != "") {
				xml_add("SellerOrganisationUnitNumber",									$yhtiorow["ovttunnus"], 								$tootfinvoice);
			}

			xml_add("SellerContactPersonName",											$lasrow["laatija"], 									$tootfinvoice);

			fputs($tootfinvoice, "<SellerCommunicationDetails>\r\n");
			if($yhtiorow["email"] != "") {
				xml_add("SellerEmailaddressIdentifier",									$yhtiorow['email'], 									$tootfinvoice);
			}

			fputs($tootfinvoice, "</SellerCommunicationDetails>\r\n");

			fputs($tootfinvoice, "<SellerInformationDetails>\r\n");
			xml_add("SellerHomeTownName",												$yhtiorow['kotipaikka'],								$tootfinvoice);
			xml_add("SellerPhoneNumber", 												$yhtiorow['puhelin'], 									$tootfinvoice);
			if($yhtiorow["fax"] != "") {
				xml_add("SellerFaxNumber", 												$yhtiorow['fax'], 										$tootfinvoice);
			}
			if($yhtiorow["email"] != "") {
				xml_add("SellerCommonEmailaddressIdentifier",							$yhtiorow['email'], 									$tootfinvoice);
			}
			if($yhtiorow["www"] != "") {
				xml_add("SellerWebaddressIdentifier",									$yhtiorow['www'],   									$tootfinvoice);
			}
			if($yhtiorow["laskun_vapaakentta"] != "") {
				xml_add("SellerFreeText",   		                     				$yhtiorow['laskun_vapaakentta'],      					$tootfinvoice);
			}

			fputs($tootfinvoice, "<SellerAccountDetails>\r\n");
			xml_add('SellerAccountID IdentificationSchemeName="IBAN"',					$yhtiorow['pankkiiban1'],								$tootfinvoice);
			xml_add('SellerBic IdentificationSchemeName="BIC"',							$yhtiorow['pankkiswift1'],								$tootfinvoice);
			fputs($tootfinvoice, "</SellerAccountDetails>\r\n");

			if ($yhtiorow['pankkiiban2']!='') {
				fputs($tootfinvoice, "<SellerAccountDetails>\r\n");
				xml_add('SellerAccountID IdentificationSchemeName="IBAN"',				$yhtiorow['pankkiiban2'],       						$tootfinvoice);
				xml_add('SellerBic IdentificationSchemeName="BIC"',						$yhtiorow['pankkiswift2'],      						$tootfinvoice);
				fputs($tootfinvoice, "</SellerAccountDetails>\r\n");
			}
			if ($yhtiorow['pankkiiban3']!='') {
				fputs($tootfinvoice, "<SellerAccountDetails>\r\n");
				xml_add('SellerAccountID IdentificationSchemeName="IBAN"',				$yhtiorow['pankkiiban3'],       						$tootfinvoice);
				xml_add('SellerBic IdentificationSchemeName="BIC"',						$yhtiorow['pankkiswift3'],     							$tootfinvoice);
				fputs($tootfinvoice, "</SellerAccountDetails>\r\n");
			}


			fputs($tootfinvoice, "<InvoiceRecipientDetails>\r\n");
			xml_add("InvoiceRecipientAddress",											$yhtiorow['pankkiiban1'],		  						$tootfinvoice);
			xml_add("InvoiceRecipientIntermediatorAddress",								$yhtiorow['pankkiswift1'],                 				$tootfinvoice);
			fputs($tootfinvoice, "</InvoiceRecipientDetails>\r\n");

			fputs($tootfinvoice, "</SellerInformationDetails>\r\n");

			fputs($tootfinvoice, "<BuyerPartyDetails>\r\n");
			xml_add("BuyerPartyIdentifier",				  								ymuuta($lasrow['ytunnus']), 							$tootfinvoice);
			xml_add("BuyerOrganisationName",                     						$lasrow['nimi'],    									$tootfinvoice);
			xml_add("BuyerOrganisationTaxCode",                     					$lasrow['maa']."".ymuuta($lasrow['ytunnus']),    		$tootfinvoice);

			fputs($tootfinvoice, "<BuyerPostalAddressDetails>\r\n");
			xml_add("BuyerStreetName",													$lasrow['osoite'],     									$tootfinvoice);
			xml_add("BuyerTownName",                                  					$lasrow['postitp'],      								$tootfinvoice);
			xml_add("BuyerPostCodeIdentifier",                         					$lasrow['postino'],     								$tootfinvoice);
			fputs($tootfinvoice, "</BuyerPostalAddressDetails>\r\n");

			fputs($tootfinvoice, "</BuyerPartyDetails>\r\n");

			if($lasrow['ovttunnus'] != "") {
				xml_add("BuyerOrganisationUnitNumber",                    				$lasrow['ovttunnus'],    								$tootfinvoice);
			}

			fputs($tootfinvoice, "<DeliveryPartyDetails>\r\n");

			fputs($tootfinvoice, "<DeliveryPartyIdentifier/>\r\n");
			xml_add("DeliveryOrganisationName",                            				$lasrow['toim_nimi'],     								$tootfinvoice);

			fputs($tootfinvoice, "<DeliveryPostalAddressDetails>\r\n");
			xml_add("DeliveryStreetName",                             					$lasrow['toim_osoite'],     							$tootfinvoice);
			xml_add("DeliveryTownName",                                 				$lasrow['toim_postitp'],     							$tootfinvoice);
			xml_add("DeliveryPostCodeIdentifier",                     					$lasrow['toim_postino'],								$tootfinvoice);
			fputs($tootfinvoice, "</DeliveryPostalAddressDetails>\r\n");

			fputs($tootfinvoice, "</DeliveryPartyDetails>\r\n");

			fputs($tootfinvoice, "<DeliveryDetails>\r\n");
			xml_add('DeliveryDate Format="CCYYMMDD"',                   				vlas_dateconv($lasrow['toimaika']),						$tootfinvoice);
			xml_add("DeliveryMethodText",                                     			$lasrow['toimitustapa'],          						$tootfinvoice);
			fputs($tootfinvoice, "</DeliveryDetails>\r\n");

			fputs($tootfinvoice, "<InvoiceDetails>\r\n");
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
			xml_add('InvoiceDate Format="CCYYMMDD"',									vlas_dateconv($lasrow['tapvm']),						$tootfinvoice);
			xml_add("SellerReferenceIdentifier",										$lasrow['tunnus'],										$tootfinvoice);
			xml_add("OrderIdentifier",													$lasrow['viesti'],										$tootfinvoice);
			xml_add("InvoiceTotalVatExcludedAmount AmountCurrencyIdentifier=\"$lasrow[valkoodi]\"", pp($lasrow['arvo']),									$tootfinvoice);
			xml_add("InvoiceTotalVatAmount AmountCurrencyIdentifier=\"$lasrow[valkoodi]\"",			pp(round($lasrow['summa']-$lasrow['arvo'], 2)),			$tootfinvoice);
			xml_add("InvoiceTotalVatIncludedAmount AmountCurrencyIdentifier=\"$lasrow[valkoodi]\"",	pp($lasrow['summa']),									$tootfinvoice);

		}
		elseif($silent == "") {
			if($tulostuspalvelu == "") {
				$tulos_ulos .= t("Finvoiceaineiston l‰hetyst‰ pankkiin '$yhtiorow[pankkiswift1]' ei viel‰ tueta. Tulosta lasku manuaalisesti!")." ".$lasrow["laskunro"]."<br>\r\n<br>\r\n";				
			}
			else {
				$tulos_ulos .= t("Finvoiceaineistoa ei voitu luoda. Yhtiolta puuttuu ovttunnus, SWIFT tai IBAN tunnus. Tulosta lasku manuaalisesti!")." ".$lasrow["laskunro"]."<br>\r\n<br>\r\n";				
			}
			$err_finvoice++;
		}
		else {
			$err_finvoice++;
		}
	}
}

if (!function_exists('finvoice_alvierittely')) {
	function finvoice_alvierittely($tootfinvoice, $lasrow, $alvrow) {
		global $err_finvoice;
		if($err_finvoice == 0) {
			fputs($tootfinvoice, "<VatSpecificationDetails>\r\n");
			xml_add("VatBaseAmount AmountCurrencyIdentifier=\"$lasrow[valkoodi]\"",	pp($alvrow[1]), 				$tootfinvoice);
			xml_add("VatRatePercent",												pp($alvrow[0]), 				$tootfinvoice);
			xml_add("VatRateAmount AmountCurrencyIdentifier=\"$lasrow[valkoodi]\"",	pp($alvrow[2]), 				$tootfinvoice);
			fputs($tootfinvoice, "</VatSpecificationDetails>\r\n");
		}
	}
}

if (!function_exists('finvoice_otsikko_loput')) {
	function finvoice_otsikko_loput($tootfinvoice, $lasrow, $masrow) {
		global $err_finvoice;
		if($err_finvoice == 0)	{
			fputs($tootfinvoice, "<PaymentTermsDetails>\r\n");
			xml_add("PaymentTermsFreeText",       											pp($masrow['teksti']." ".$masrow['kassa_teksti']),			$tootfinvoice);
			xml_add('InvoiceDueDate Format="CCYYMMDD"',               						pp(vlas_dateconv($lasrow['erpcm'])),   	       				$tootfinvoice);

			fputs($tootfinvoice, "<PaymentOverDueFineDetails>\r\n");
			xml_add("PaymentOverDueFineFreeText",											"viiv‰styskorko ".pp($lasrow['viikorkopros']),				$tootfinvoice);
			xml_add("PaymentOverDueFinePercent",                 						 	pp($lasrow['viikorkopros']),   								$tootfinvoice);
			fputs($tootfinvoice, "</PaymentOverDueFineDetails>\r\n");

			fputs($tootfinvoice, "</PaymentTermsDetails>\r\n");

			fputs($tootfinvoice, "</InvoiceDetails>\r\n");

			fputs($tootfinvoice, "<PaymentStatusDetails>\r\n");
			xml_add("PaymentStatusCode", 													"NOTPAID", 													$tootfinvoice);
			fputs($tootfinvoice, "</PaymentStatusDetails>\r\n");
		}
	}
}

if (!function_exists('finvoice_rivi')) {
	function finvoice_rivi($tootfinvoice, $tilrow, $lasrow, $vatamount, $totalvat) {
		global $err_finvoice;

		if($err_finvoice == 0) {
			fputs($tootfinvoice, "<InvoiceRow>\r\n");
			xml_add("ArticleIdentifier",                                   				$tilrow['tuoteno'],         	$tootfinvoice);
			xml_add("ArticleName",                                    					$tilrow['nimitys'],  			$tootfinvoice);
			xml_add("DeliveredQuantity QuantityUnitCode=\"$tilrow[yksikko]\"",			pp($tilrow['kpl']),      		$tootfinvoice);
			xml_add("UnitPriceAmount AmountCurrencyIdentifier=\"$lasrow[valkoodi]\"",	pp($tilrow['hinta']),        	$tootfinvoice);

			if($tilrow["tilaajanrivinro"] != 0) {
				xml_add("RowIdentifier",                                    			$tilrow['tilaajanrivinro'], 	$tootfinvoice); // t‰nne laitetaan asiakkaan rivinumero, niin saavat parseroida senkin laskuista
			}
			if ($tilrow['kommentti']!='') {
				xml_add("RowFreeText",    												$tilrow['kommentti'],			$tootfinvoice);
			}

			xml_add("RowVatRatePercent",                                    			pp($tilrow['alv']),           	$tootfinvoice);
			xml_add("RowVatAmount AmountCurrencyIdentifier=\"$lasrow[valkoodi]\"",          			pp($vatamount),      			$tootfinvoice);	// veron m‰‰r‰
			xml_add("RowVatExcludedAmount AmountCurrencyIdentifier=\"$lasrow[valkoodi]\"",  			pp($tilrow['rivihinta']),      	$tootfinvoice);	// veroton rivihinta
			xml_add("RowAmount AmountCurrencyIdentifier=\"$lasrow[valkoodi]\"",             			pp($totalvat),     				$tootfinvoice);	// verollinen rivihinta
			fputs($tootfinvoice, "</InvoiceRow>\r\n");
		}
	}
}

if (!function_exists('finvoice_lasku_loppu')) {
	function finvoice_lasku_loppu($tootfinvoice, $lasrow, $masrow) {
		global $yhtiorow, $err_finvoice;

		// jos maksuehdon takana on annettu pankkiyhteystiedot, k‰ytet‰‰n niit‰ n‰in
		if ($masrow["pankkinimi1"] != "") {
			$yhtiorow["pankkinimi1"]	= $masrow["pankkinimi1"];
			$yhtiorow["pankkitili1"]	= $masrow["pankkitili1"];
			$yhtiorow["pankkiiban1"]	= $masrow["pankkiiban1"];
			$yhtiorow["pankkiswift1"]	= $masrow["pankkiswift1"];
			$yhtiorow["pankkinimi2"]	= $masrow["pankkinimi2"];
			$yhtiorow["pankkitili2"]	= $masrow["pankkitili2"];
			$yhtiorow["pankkiiban2"]	= $masrow["pankkiiban2"];
			$yhtiorow["pankkiswift2"]	= $masrow["pankkiswift2"];
			$yhtiorow["pankkinimi3"]	= $masrow["pankkinimi3"];
			$yhtiorow["pankkitili3"]	= $masrow["pankkitili3"];
			$yhtiorow["pankkiiban3"]	= $masrow["pankkiiban3"];
			$yhtiorow["pankkiswift3"]	= $masrow["pankkiswift3"];
		}

		if($err_finvoice == 0) {
			fputs($tootfinvoice, "<EpiDetails>\r\n");

			fputs($tootfinvoice, "<EpiIdentificationDetails>\r\n");
			xml_add('EpiDate Format="CCYYMMDD"',                   							vlas_dateconv($lasrow['tapvm']),	$tootfinvoice);
			xml_add("EpiReference",                               							$lasrow['viite'],					$tootfinvoice);
			fputs($tootfinvoice, "</EpiIdentificationDetails>\r\n");

			fputs($tootfinvoice, "<EpiPartyDetails>\r\n");

			fputs($tootfinvoice, "<EpiBfiPartyDetails>\r\n");
			xml_add('EpiBfiIdentifier IdentificationSchemeName="BIC"',						$yhtiorow['pankkiswift1'],			$tootfinvoice);
			fputs($tootfinvoice, "</EpiBfiPartyDetails>\r\n");

			fputs($tootfinvoice, "<EpiBeneficiaryPartyDetails>\r\n");
			xml_add("EpiNameAddressDetails",    											$yhtiorow['nimi'],					$tootfinvoice);
			xml_add("EpiBei",                         										ymuuta($yhtiorow['ytunnus']),  		$tootfinvoice);
			xml_add("EpiAccountID IdentificationSchemeName=\"BBAN\"",						$yhtiorow['pankkitili1'],			$tootfinvoice);
			fputs($tootfinvoice, "</EpiBeneficiaryPartyDetails>\r\n");

			fputs($tootfinvoice, "</EpiPartyDetails>\r\n");

			fputs($tootfinvoice, "<EpiPaymentInstructionDetails>\r\n");
			xml_add("EpiRemittanceInfoIdentifier IdentificationSchemeName=\"SPY\"",			spyconv($lasrow['viite']),    		$tootfinvoice);
			xml_add("EpiInstructedAmount AmountCurrencyIdentifier=\"$lasrow[valkoodi]\"", pp($lasrow['summa']),         		$tootfinvoice);
			xml_add("EpiCharge ChargeOption=\"SHA\"", 										"SHA",								$tootfinvoice);
			xml_add('EpiDateOptionDate Format="CCYYMMDD"',                					vlas_dateconv($lasrow['erpcm']),	$tootfinvoice);
			fputs($tootfinvoice, "</EpiPaymentInstructionDetails>\r\n");

			fputs($tootfinvoice, "</EpiDetails>\r\n");

			fputs($tootfinvoice, "</Finvoice>\r\n");
		}
	}
}

?>
