<?php

if (!function_exists('finvoice_otsik')) {
	function finvoice_otsik($tootfinvoice, $lasrow, $kieli, $pankkitiedot, $masrow, $myyrow, $tyyppi, $toimaikarow, $tulos_ulos, $silent) {
		global $yhtiorow, $err_finvoice, $tulos_ulos;

		//	Koitetaan ollaanko virheettˆmi‰
		//	Jos tapahtuu virhe, eli tietoja puuttuu niin tuota loppua ei saa tehd‰ tai koko aineisto voi menn‰ virheelliseksi!
		$err_finvoice = 0;

		//varmuudeksi alku aina puhtaalta pˆyd‰lt‰
		$senderpartyid 			= '';
		$senderintermediator 	= '';
		$receiverpartyid 		= '';
		$receiverintermediator 	= '';

		// MessageID
		$mid = date("YmdHis")."-".$lasrow['laskunro'];

		$senderpartyid 			= str_replace(" ", "", $yhtiorow['finvoice_senderpartyid']);
		$senderintermediator	= str_replace(" ", "", $yhtiorow['finvoice_senderintermediator']);

		//	Osataanko t‰m‰n k‰sitell‰ t‰m‰n pankin finvoiceaineistoa?
		switch ($senderintermediator) {
			case 'NDEAFIHH':
			$tulostuspalvelu	= "tulostukseen";
				break;
			case 'PSPBFIHH':
			$tulostuspalvelu	= "003718062728810P";
				break;
			case 'DABAFIHH':
			$tulostuspalvelu	= "003718062728810P";
				break;
			case 'HELSFIHH':
			$tulostuspalvelu	= "TULOSTUSPALVELU";
				break;
			case 'OKOYFIHH':
			$tulostuspalvelu	= "TULOSTUSPALVELU";
				break;
			case '003710948874':
			$tulostuspalvelu	= "EKIRJE";
				break;
			default:
			$tulostuspalvelu	= "";
				break;
		}

		//	Aloitellaan aineiston luontia
		if (($senderpartyid != "" and $senderintermediator != "" and $tulostuspalvelu != "") or $lasrow["chn"] == "112") {

			if ($yhtiorow["verkkolasku_lah"] == "iPost") {
				$receiverpartyid = $lasrow["verkkotunnus"];
				$receiverintermediator = "003710948874";
			}
			else {
				list($receiverpartyid, $receiverintermediator) = explode("@", $lasrow["verkkotunnus"]);
			}

			// jos t‰nne ei saada mit‰‰n arvoja laitetaan se edelleen pankkiin, mutta annetaan lipuksi tulostukseen
			if ($receiverpartyid == "" or $receiverintermediator == "" or $lasrow["chn"] == "100") {
				$receiverpartyid 		= $tulostuspalvelu;
				$receiverintermediator 	= $senderintermediator;

				if ($lasrow["chn"] != "100" and $lasrow["chn"] != "112" and $silent == "") {
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

			// jos meill‰ on lasku menossa ulkomaille ja se laskutetaan eri ovttunnuksella
			if ($lasrow["yhtio_ovttunnus"] != "" and $lasrow["yhtio_ovttunnus"] != $yhtiorow["ovttunnus"]) {

				// haetaan toimipaikan tiedot
				$alhqur = "SELECT * from yhtion_toimipaikat where yhtio='$lasrow[yhtio]' and vat_numero='$lasrow[yhtio_ovttunnus]'";
				$alhire = mysql_query($alhqur) or pupe_error($alhqur);

				if (mysql_num_rows($alhire) == 1) {

					// haetaan toimipaikan tiedot
					$alhiro = mysql_fetch_array($alhire);

					$y_puhelin		= $alhiro['puhelin'];
					$y_fax			= $alhiro['fax'];
					$y_email		= $alhiro['email'];
					$y_www			= $yhtiorow['www'];
					$y_nimi 		= $lasrow["yhtio_nimi"];
					$y_osoite 		= $lasrow["yhtio_osoite"];
					$y_postino	 	= $lasrow["yhtio_postino"];
					$y_postitp	 	= $lasrow["yhtio_postitp"];
					$y_maa 			= $lasrow["yhtio_maa"];
					$y_kotipaikka	= $lasrow["yhtio_kotipaikka"];
					$y_vatnumero	= $lasrow["yhtio_ovttunnus"];
				}
				else {
					$y_puhelin		= $yhtiorow['puhelin'];
					$y_fax			= $yhtiorow['fax'];
					$y_email		= $yhtiorow['email'];
					$y_www			= $yhtiorow['www'];
					$y_nimi 		= $yhtiorow["nimi"];
					$y_osoite 		= $yhtiorow["osoite"];
					$y_postino	 	= $yhtiorow["postino"];
					$y_postitp	 	= $yhtiorow["postitp"];
					$y_maa 			= $yhtiorow["maa"];
					$y_kotipaikka	= $yhtiorow["kotipaikka"];
					$y_vatnumero	= $yhtiorow["ovttunnus"];
				}
			}
			else {
				$y_puhelin		= $yhtiorow['puhelin'];
				$y_fax			= $yhtiorow['fax'];
				$y_email		= $yhtiorow['email'];
				$y_www			= $yhtiorow['www'];
				$y_nimi 		= $yhtiorow["nimi"];
				$y_osoite 		= $yhtiorow["osoite"];
				$y_postino	 	= $yhtiorow["postino"];
				$y_postitp	 	= $yhtiorow["postitp"];
				$y_maa 			= $yhtiorow["maa"];
				$y_kotipaikka	= $yhtiorow["kotipaikka"];
				$y_vatnumero	= $yhtiorow["ovttunnus"];
			}

			// haetaan asiakkaan tiedot
			$asquery = "SELECT * from asiakas where yhtio='$lasrow[yhtio]' and tunnus='$lasrow[liitostunnus]'";
			$asiakasre = mysql_query($asquery) or pupe_error($asquery);
			$asiakasrow = mysql_fetch_array($asiakasre);

			//laitetaan setit kuntoon..
			fputs($tootfinvoice, "<?xml version=\"1.0\" encoding=\"ISO-8859-15\"?>\r\n");
			fputs($tootfinvoice, "<!DOCTYPE Finvoice SYSTEM \"Finvoice.dtd\">\r\n");
			fputs($tootfinvoice, "<?xml-stylesheet type=\"text/xsl\" href=\"Finvoice.xsl\"?>\r\n");

			//t‰st‰ l‰htee Finvoice koodi
			fputs($tootfinvoice, "<Finvoice Version=\"1.2\">\r\n");

			fputs($tootfinvoice, "<SellerPartyDetails>\r\n");
			xml_add("SellerPartyIdentifier",				tulosta_ytunnus($yhtiorow['ytunnus'], $y_maa),		$tootfinvoice);
			#xml_add("SellerPartyIdentifierUrlText",						"", 								$tootfinvoice);
			xml_add("SellerOrganisationName",							  	$y_nimi,							$tootfinvoice);
			xml_add("SellerOrganisationTaxCode", tulosta_ytunnus(str_replace("0037", "", $y_vatnumero), $y_maa, "X"), $tootfinvoice);
			#xml_add("SellerOrganisationTaxCodeUrlText",					"", 								$tootfinvoice);

			fputs($tootfinvoice, "<SellerPostalAddressDetails>\r\n");
			xml_add("SellerStreetName", 								  	$y_osoite,							$tootfinvoice);
			xml_add("SellerTownName", 									  	$y_postitp,							$tootfinvoice);
			xml_add("SellerPostCodeIdentifier", 						  	$y_postino,							$tootfinvoice);
			xml_add("CountryCode",										  	$y_maa,								$tootfinvoice);
			xml_add("CountryName",										  	$y_maa,								$tootfinvoice);
			#xml_add("SellerPostOfficeBoxIdentifier",					  	"",									$tootfinvoice);
			fputs($tootfinvoice, "</SellerPostalAddressDetails>\r\n");
			fputs($tootfinvoice, "</SellerPartyDetails>\r\n");

			xml_add("SellerOrganisationUnitNumber",						  	$y_vatnumero,						$tootfinvoice);
			xml_add("SellerContactPersonName",							  	$myyrow['nimi'],					$tootfinvoice);

			fputs($tootfinvoice, "<SellerCommunicationDetails>\r\n");
			xml_add("SellerPhoneNumberIdentifier",						  	$myyrow['puhno'], 					$tootfinvoice);
			xml_add("SellerEmailaddressIdentifier",						  	$myyrow['eposti'], 					$tootfinvoice);
			fputs($tootfinvoice, "</SellerCommunicationDetails>\r\n");

			fputs($tootfinvoice, "<SellerInformationDetails>\r\n");
			xml_add("SellerHomeTownName",								  	$y_kotipaikka,						$tootfinvoice);
			xml_add("SellerVatRegistrationText",						  	t("Alv.Rek"),						$tootfinvoice);
			#xml_add("SellerVatRegistrationDate Format=\"CCYYMMDD\"",	  	"",									$tootfinvoice);
			xml_add("SellerPhoneNumber", 								  	$y_puhelin, 						$tootfinvoice);
			xml_add("SellerFaxNumber", 									  	$y_fax, 							$tootfinvoice);
			xml_add("SellerCommonEmailaddressIdentifier",				  	$y_email, 							$tootfinvoice);
			xml_add("SellerWebaddressIdentifier",						  	$y_www, 			  				$tootfinvoice);
			#xml_add("SellerFreeText",   		                     	  	"",									$tootfinvoice);

			fputs($tootfinvoice, "<SellerAccountDetails>\r\n");
			xml_add("SellerAccountID IdentificationSchemeName=\"IBAN\"",	str_replace(" ", "", $pankkitiedot['pankkiiban1']),		$tootfinvoice);
			xml_add("SellerBic IdentificationSchemeName=\"BIC\"",			$pankkitiedot['pankkiswift1'],		$tootfinvoice);
			fputs($tootfinvoice, "</SellerAccountDetails>\r\n");

			if ($pankkitiedot['pankkiiban2']!='') {
				fputs($tootfinvoice, "<SellerAccountDetails>\r\n");
				xml_add("SellerAccountID IdentificationSchemeName=\"IBAN\"",str_replace(" ", "", $pankkitiedot['pankkiiban2']),      	$tootfinvoice);
				xml_add("SellerBic IdentificationSchemeName=\"BIC\"",		$pankkitiedot['pankkiswift2'],     	$tootfinvoice);
				fputs($tootfinvoice, "</SellerAccountDetails>\r\n");
			}
			if ($pankkitiedot['pankkiiban3']!='') {
				fputs($tootfinvoice, "<SellerAccountDetails>\r\n");
				xml_add("SellerAccountID IdentificationSchemeName=\"IBAN\"",str_replace(" ", "", $pankkitiedot['pankkiiban3']),      	$tootfinvoice);
				xml_add("SellerBic IdentificationSchemeName=\"BIC\"",	  	$pankkitiedot['pankkiswift3'],     	$tootfinvoice);
				fputs($tootfinvoice, "</SellerAccountDetails>\r\n");
			}

			fputs($tootfinvoice, "<InvoiceRecipientDetails>\r\n");
			xml_add("InvoiceRecipientAddress",							  	str_replace(" ", "", $yhtiorow['pankkiiban1']),		   	$tootfinvoice);
			xml_add("InvoiceRecipientIntermediatorAddress",				  	$yhtiorow['pankkiswift1'],         	$tootfinvoice);
			fputs($tootfinvoice, "</InvoiceRecipientDetails>\r\n");
			fputs($tootfinvoice, "</SellerInformationDetails>\r\n");

			#fputs($tootfinvoice, "<InvoiceSenderPartyDetails>\r\n");
			#xml_add("InvoiceSenderPartyIdentifier", 						"", 								$tootfinvoice);
			#xml_add("InvoiceSenderOrganisationName", 						"", 								$tootfinvoice);
			#xml_add("InvoiceSenderOrganisationTaxCode", 					"", 								$tootfinvoice);
			#fputs($tootfinvoice, "</InvoiceSenderPartyDetails>\r\n");

			//	Oisko toista laskutusosoitetta?
			$query = "	SELECT laskutus_nimi,
						laskutus_nimitark,
						laskutus_osoite,
						laskutus_postino,
						laskutus_postitp,
						laskutus_maa
						FROM laskun_lisatiedot
						WHERE yhtio = '{$lasrow['yhtio']}'
						AND laskutus_nimi != ''
						AND CONCAT(laskutus_nimi, laskutus_osoite, laskutus_postino, laskutus_postitp, laskutus_maa) != '$lasrow[nimi]$lasrow[osoite]$lasrow[postino]$lasrow[postitp]$lasrow[maa]'
						AND otunnus = '{$lasrow['tunnus']}'
						LIMIT 1";
			$tarkres = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($tarkres) == 1) {
				$tarkrow = mysql_fetch_assoc($tarkres);

				fputs($tootfinvoice, "<InvoiceRecipientPartyDetails>\r\n");
				xml_add("InvoiceRecipientPartyIdentifier", 						"", 							$tootfinvoice);
				xml_add("InvoiceRecipientOrganisationName", trim($tarkrow["laskutus_nimi"]." ".$tarkrow["laskutus_nimitark"]), $tootfinvoice);
				xml_add("InvoiceRecipientOrganisationTaxCode", 					"",								$tootfinvoice);
				fputs($tootfinvoice, "<InvoiceRecipientPostalAddressDetails>\r\n");
				xml_add("InvoiceRecipientStreetName", 							$tarkrow["laskutus_osoite"], 	$tootfinvoice);
				xml_add("InvoiceRecipientTownName", 							$tarkrow["laskutus_postitp"], 	$tootfinvoice);
				xml_add("InvoiceRecipientPostCodeIdentifier", 					$tarkrow["laskutus_postino"],	$tootfinvoice);
				xml_add("CountryCode", 											$tarkrow["laskutus_maa"], 		$tootfinvoice);
				xml_add("CountryName", 											$tarkrow["laskutus_maa"], 		$tootfinvoice);
				xml_add("InvoiceRecipientPostOfficeBoxIdentifier",				"", 							$tootfinvoice);
				fputs($tootfinvoice, "</InvoiceRecipientPostalAddressDetails>\r\n");
				fputs($tootfinvoice, "</InvoiceRecipientPartyDetails>\r\n");

				xml_add("InvoiceRecipientOrganisationUnitNumber", 				"",								$tootfinvoice);
			}

			#xml_add("InvoiceRecipientContactPersonName", 						"", 							$tootfinvoice);
			#fputs($tootfinvoice, "<InvoiceRecipientCommunicationDetails>\r\n");
			#xml_add("InvoiceRecipientPhoneNumberIdentifier", 					"", 							$tootfinvoice);
			#xml_add("InvoiceRecipientEmailaddressIdentifier", 					"", 							$tootfinvoice);
			#fputs($tootfinvoice, "</InvoiceRecipientCommunicationDetails>\r\n");

			fputs($tootfinvoice, "<BuyerPartyDetails>\r\n");
			xml_add("BuyerPartyIdentifier",	tulosta_ytunnus($lasrow['ytunnus'], $lasrow['maa']), 				$tootfinvoice);
			xml_add("BuyerOrganisationName",        trim($lasrow['nimi']." ".$lasrow['nimitark']),    			$tootfinvoice);
			xml_add("BuyerOrganisationTaxCode", tulosta_ytunnus($lasrow['ytunnus'], $lasrow['maa'], "X"),		$tootfinvoice);

			fputs($tootfinvoice, "<BuyerPostalAddressDetails>\r\n");
			xml_add("BuyerStreetName",				$lasrow['osoite'],     										$tootfinvoice);
			xml_add("BuyerTownName",                $lasrow['postitp'],      									$tootfinvoice);
			xml_add("BuyerPostCodeIdentifier",      $lasrow['postino'],     									$tootfinvoice);
			xml_add("CountryCode",      			$lasrow['maa'],     										$tootfinvoice);
			xml_add("CountryName",      			$lasrow['maa'],     										$tootfinvoice);
			xml_add("BuyerPostOfficeBoxIdentifier", "",     													$tootfinvoice);

			fputs($tootfinvoice, "</BuyerPostalAddressDetails>\r\n");
			fputs($tootfinvoice, "</BuyerPartyDetails>\r\n");

			xml_add("BuyerOrganisationUnitNumber",	$lasrow['ovttunnus'],    									$tootfinvoice);

			//	Onko meill‰ kaupallista k‰sittelij‰‰, falseback on tekninen yhteyshenkilˆ
			$query = "	SELECT kk.nimi kaupallinen_kasittelija, kt.nimi tekninen_yhteyshenkilo
						FROM lasku
						JOIN laskun_lisatiedot ON laskun_lisatiedot.yhtio=lasku.yhtio and laskun_lisatiedot.otunnus=lasku.tunnus
						LEFT JOIN yhteyshenkilo kk ON kk.yhtio=laskun_lisatiedot.yhtio and kk.tunnus=laskun_lisatiedot.yhteyshenkilo_kaupallinen and kk.tyyppi = 'A'
						LEFT JOIN yhteyshenkilo kt ON kt.yhtio=laskun_lisatiedot.yhtio and kt.tunnus=laskun_lisatiedot.yhteyshenkilo_tekninen and kt.tyyppi = 'A'
						WHERE lasku.yhtio = '$lasrow[yhtio]'
						and lasku.laskunro = '$lasrow[laskunro]'
						and lasku.tila = 'L'";
			$tarkres = mysql_query($query) or pupe_error($query);
			$asiakkaan_yhteyshenkilo = "";

			while ($tarkrow = mysql_fetch_array($tarkres)) {
				if ($asiakkaan_yhteyshenkilo == "") {
					if ($tarkrow["kaupallinen_kasittelija"] != "") {
						$asiakkaan_yhteyshenkilo = $tarkrow["kaupallinen_kasittelija"];
					}
					elseif ($tarkrow["tekninen_yhteyshenkilo"] != "") {
						$asiakkaan_yhteyshenkilo = $tarkrow["tekninen_yhteyshenkilo"];
					}
				}
			}

			xml_add("BuyerContactPersonName", 								$asiakkaan_yhteyshenkilo,				$tootfinvoice);
			#fputs($tootfinvoice, "<BuyerCommunicationDetails>\r\n");
			#xml_add("BuyerPhoneNumberIdentifier", 							"",     								$tootfinvoice);
			#xml_add("BuyerEmailaddressIdentifier", 						"",     								$tootfinvoice);
			#fputs($tootfinvoice, "</BuyerCommunicationDetails>\r\n");

			fputs($tootfinvoice, "<DeliveryPartyDetails>\r\n");
			xml_add("DeliveryPartyIdentifier",		tulosta_ytunnus($lasrow['ytunnus'], $lasrow['maa']),     		$tootfinvoice);
			xml_add("DeliveryOrganisationName",                            	trim($lasrow['toim_nimi']." ".$lasrow['toim_nimitark']), $tootfinvoice);
			xml_add("DeliveryOrganisationTaxCode",	tulosta_ytunnus($lasrow['ytunnus'], $lasrow['maa'], "X"),		$tootfinvoice);
			fputs($tootfinvoice, "<DeliveryPostalAddressDetails>\r\n");
			xml_add("DeliveryStreetName",                             		$lasrow['toim_osoite'],     			$tootfinvoice);
			xml_add("DeliveryTownName",                                 	$lasrow['toim_postitp'],     			$tootfinvoice);
			xml_add("DeliveryPostCodeIdentifier",                     		$lasrow['toim_postino'],				$tootfinvoice);
			xml_add("CountryCode",                         					$lasrow['toim_maa'],					$tootfinvoice);
			xml_add("CountryName",                         					$lasrow['toim_maa'],					$tootfinvoice);
			#xml_add("DeliveryPostofficeBoxIdentifier",						"",					     				$tootfinvoice);
			fputs($tootfinvoice, "</DeliveryPostalAddressDetails>\r\n");
			fputs($tootfinvoice, "</DeliveryPartyDetails>\r\n");

			xml_add("DeliveryOrganisationUnitNumber",						$lasrow['toim_ovttunnus'],				$tootfinvoice);
			#xml_add("DeliveryContactPersonName",							"",					     				$tootfinvoice);
			#fputs($tootfinvoice, "<DeliveryCommunicationDetails>\r\n");
			#xml_add("DeliveryPhoneNumberIdentifier",						"",					     				$tootfinvoice);
			#xml_add("DeliveryEmailaddressIdentifier",						"",					     				$tootfinvoice);
			#fputs($tootfinvoice, "</DeliveryCommunicationDetails>\r\n");

			fputs($tootfinvoice, "<DeliveryDetails>\r\n");
			#xml_add("DeliveryDate Format=\"CCYYMMDD\"",					"",										$tootfinvoice);
			fputs($tootfinvoice, "<DeliveryPeriodDetails>\r\n");
			xml_add("StartDate Format=\"CCYYMMDD\"",                   		vlas_dateconv($toimaikarow["mint"]),	$tootfinvoice);
			xml_add("EndDate Format=\"CCYYMMDD\"",                   		vlas_dateconv($toimaikarow["maxt"]),	$tootfinvoice);
			fputs($tootfinvoice, "</DeliveryPeriodDetails>\r\n");
			xml_add("DeliveryMethodText",                                   $lasrow['toimitustapa'],          		$tootfinvoice);
			xml_add("DeliveryTermsText",									$lasrow['toimitusehto'],          		$tootfinvoice);

			if ($lasrow["chn"] == "112") {
				xml_add("TerminalAddressText",								"KAUTTALASKUTUS",	     				$tootfinvoice);
			}

			#xml_add("WaybillIdentifier",									"",					     				$tootfinvoice);
			#xml_add("WaybillTypeCode",										"",					     				$tootfinvoice);
			#xml_add("DelivererIdentifier",									"",					     				$tootfinvoice);
			#xml_add("DelivererName",										"",					     				$tootfinvoice);
			#xml_add("DelivererCountryCode",								"",					     				$tootfinvoice);
			#xml_add("DelivererCountryName",								"",					     				$tootfinvoice);
			#xml_add("ManufacturerIdentifier",								"",					     				$tootfinvoice);
			#xml_add("ManufacturerName",									"",					     				$tootfinvoice);
			#xml_add("ManufacturerCountryCode",								"",					     				$tootfinvoice);
			#xml_add("ManufacturerCountryName",								"",					     				$tootfinvoice);
			fputs($tootfinvoice, "</DeliveryDetails>\r\n");

			fputs($tootfinvoice, "<InvoiceDetails>\r\n");

			// Tsekataan laskun tyyppikoodi INV
			if ($lasrow['arvo'] >= 0)	{
				xml_add("InvoiceTypeCode CodeListAgencyIdentifier=\"SPY\"",	"INV01",      		      				$tootfinvoice);
				xml_add("InvoiceTypeText",									"LASKU",      		      				$tootfinvoice);
			}
			else {
				xml_add("InvoiceTypeCode CodeListAgencyIdentifier=\"SPY\"",	"INV02",      		      				$tootfinvoice);
				xml_add("InvoiceTypeText",									"HYVITYSLASKU",      		      		$tootfinvoice);
			}

			xml_add("OriginCode",              	                   			"Original",                  			$tootfinvoice);
			#xml_add("OriginText",              	                   		"",                  					$tootfinvoice);
			xml_add("InvoiceNumber",       	 	                     		$lasrow['laskunro'],  					$tootfinvoice);
			xml_add("InvoiceDate Format=\"CCYYMMDD\"",						vlas_dateconv($lasrow['tapvm']),		$tootfinvoice);
			xml_add("SellerReferenceIdentifier",							$lasrow['tunnus'],						$tootfinvoice);
			#xml_add("SellerReferenceIdentifierUrlText",					"",										$tootfinvoice);

			if (trim($lasrow['asiakkaan_tilausnumero']) != "") {
				xml_add("OrderIdentifier",					substr($lasrow['asiakkaan_tilausnumero'], 0, 35),		$tootfinvoice);
			}
			else {
				xml_add("OrderIdentifier",									substr($lasrow['viesti'], 0, 35),		$tootfinvoice);
			}

			#xml_add("OrderIdentifierUrlText",								"",										$tootfinvoice);
			xml_add("AgreementIdentifier",									substr($lasrow['viesti'], 0, 35),		$tootfinvoice);
			#xml_add("AgreementIdentifierUrlText",							"",										$tootfinvoice);

			xml_add("InvoiceTotalVatExcludedAmount AmountCurrencyIdentifier=\"$lasrow[valkoodi]\"", pp($lasrow['arvo'], 2),						$tootfinvoice);
			xml_add("InvoiceTotalVatAmount AmountCurrencyIdentifier=\"$lasrow[valkoodi]\"",			pp(($lasrow['summa']-$lasrow['arvo']), 2),	$tootfinvoice);
			xml_add("InvoiceTotalVatIncludedAmount AmountCurrencyIdentifier=\"$lasrow[valkoodi]\"",	pp($lasrow['summa'], 2),					$tootfinvoice);

			#xml_add("ShortProposedAccountIdentifier",						"",										$tootfinvoice);
			#xml_add("NormalProposedAccountIdentifier",						"",										$tootfinvoice);
			#xml_add("AccountDimensionText",								"",										$tootfinvoice);
			#xml_add("SellerAccountText",									"",										$tootfinvoice);
		}
		elseif ($silent == "" or strpos($_SERVER['SCRIPT_NAME'], "valitse_laskutettavat_tilaukset.php") !== FALSE) {
			if ($senderpartyid == "" or $senderintermediator == "") {
				$tulos_ulos .= t("Finvoiceaineistoa ei voitu luoda. Yhtiolta puuttuu finvoice_senderpartyid tai finvoice_senderintermediator tunnus. Tulosta lasku manuaalisesti!")." ".$lasrow["laskunro"]."<br>\r\n<br>\r\n";
			}
			elseif ($tulostuspalvelu == "") {
				$tulos_ulos .= t("Finvoiceaineiston l‰hetyst‰ pankkiin '$yhtiorow[finvoice_senderintermediator]' ei viel‰ tueta. Tulosta lasku manuaalisesti!")." ".$lasrow["laskunro"]."<br>\r\n<br>\r\n";
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
	function finvoice_alvierittely ($tootfinvoice, $lasrow, $alvrow) {
		global $err_finvoice;
		if ($err_finvoice == 0) {
			fputs($tootfinvoice, "<VatSpecificationDetails>\r\n");
			xml_add("VatBaseAmount AmountCurrencyIdentifier=\"$lasrow[valkoodi]\"",	pp($alvrow[1]), 	   	$tootfinvoice);
			xml_add("VatRatePercent",												pp($alvrow[0]), 	   	$tootfinvoice);
			xml_add("VatRateAmount AmountCurrencyIdentifier=\"$lasrow[valkoodi]\"",	pp($alvrow[2]), 	   	$tootfinvoice);
			xml_add("VatFreeText",													"",					   	$tootfinvoice);
			fputs($tootfinvoice, "</VatSpecificationDetails>\r\n");

			$InvoiceFreeTexts = explode("|", trim($lasrow["sisviesti1"]));

			foreach ($InvoiceFreeTexts as $InvoiceFreeText) {
				if (trim($InvoiceFreeText) != "") {
					$InvoiceFreeText = wordwrap($InvoiceFreeText, 512, "</InvoiceFreeText>\r\n<InvoiceFreeText>");

					xml_add("InvoiceFreeText", $InvoiceFreeText, $tootfinvoice);
				}
			}
		}
	}
}

if (!function_exists('finvoice_otsikko_loput')) {
	function finvoice_otsikko_loput($tootfinvoice, $lasrow, $masrow) {
		global $err_finvoice, $yhtiorow;

		if ($err_finvoice == 0)	{
			fputs($tootfinvoice, "<PaymentTermsDetails>\r\n");
			xml_add("PaymentTermsFreeText",       											pp($masrow['teksti']),						  	$tootfinvoice);
			xml_add("InvoiceDueDate Format=\"CCYYMMDD\"",               					pp(vlas_dateconv($lasrow['erpcm'])),   	      	$tootfinvoice);

			if ($lasrow['kasumma'] > 0) {
				xml_add("CashDiscountDate Format=\"CCYYMMDD\"",               					pp(vlas_dateconv($lasrow['kapvm'])),   	    $tootfinvoice);
				xml_add("CashDiscountBaseAmount AmountCurrencyIdentifier=\"$lasrow[valkoodi]\"",pp($lasrow['summa'], 2),					$tootfinvoice);
				xml_add("CashDiscountPercent",													$masrow['kassa_alepros'],					$tootfinvoice);
				xml_add("CashDiscountAmount",													pp($lasrow['kasumma'], 2),	  				$tootfinvoice);
			}

			fputs($tootfinvoice, "<PaymentOverDueFineDetails>\r\n");
			xml_add("PaymentOverDueFineFreeText",											"Viiv‰styskorko ".pp($lasrow['viikorkopros'])."%",	$tootfinvoice);
			xml_add("PaymentOverDueFinePercent",                 						 	pp($lasrow['viikorkopros'], 2),   			  	$tootfinvoice);
			fputs($tootfinvoice, "</PaymentOverDueFineDetails>\r\n");
			fputs($tootfinvoice, "</PaymentTermsDetails>\r\n");
			fputs($tootfinvoice, "</InvoiceDetails>\r\n");

			fputs($tootfinvoice, "<PaymentStatusDetails>\r\n");
			xml_add("PaymentStatusCode", 													"NOTPAID", 									  	$tootfinvoice);
			#xml_add("PaymentMethodText", 													"", 										  	$tootfinvoice);
			fputs($tootfinvoice, "</PaymentStatusDetails>\r\n");

			#fputs($tootfinvoice, "<PartialPaymentDetails>\r\n");
			#xml_add("PaidAmount AmountCurrencyIdentifier=\"$lasrow[valkoodi]\"", 			"", 										  	$tootfinvoice);
			#xml_add("UnPaidAmount AmountCurrencyIdentifier=\"$lasrow[valkoodi]\"", 		"", 										  	$tootfinvoice);
			#xml_add("InterestPercent", 													"", 										  	$tootfinvoice);
			#xml_add("ProsessingCostsAmount AmountCurrencyIdentifier=\"$lasrow[valkoodi]\"", "", 										  	$tootfinvoice);
			#xml_add("PartialPaymentVatIncludedAmount AmountCurrencyIdentifier=\"$lasrow[valkoodi]\"", "", 								  	$tootfinvoice);
			#xml_add("PartialPaymentVatExcludedAmount AmountCurrencyIdentifier=\"$lasrow[valkoodi]\"", "", 								  	$tootfinvoice);
			#xml_add("PartialPaymentDueDate Format=\"CCYYMMDD\"", 							"", 										  	$tootfinvoice);
			#xml_add("PartialPaymentReferenceIdentifier", 									"", 										  	$tootfinvoice);
			#fputs($tootfinvoice, "</PartialPaymentDetails>\r\n");
			#xml_add("VirtualBankBarcode", 													"", 										  	$tootfinvoice);
		}
	}
}

if (!function_exists('finvoice_rivi')) {
	function finvoice_rivi($tootfinvoice, $tilrow, $lasrow, $vatamount, $totalvat) {
		global $err_finvoice, $yhtiorow;

		if ($err_finvoice == 0) {
			fputs($tootfinvoice, "<InvoiceRow>\r\n");
			#xml_add("RowSubIdentifier", 														"", 							$tootfinvoice);
			xml_add("ArticleIdentifier",                                   						$tilrow['tuoteno'],         	$tootfinvoice);
			xml_add("ArticleName",                                    							$tilrow['nimitys'],  			$tootfinvoice);
			#xml_add("ArticleInfoUrlText", 														"", 							$tootfinvoice);
			#xml_add("BuyerArticleIdentifier", 													"", 							$tootfinvoice);
			xml_add("DeliveredQuantity QuantityUnitCode=\"$tilrow[yksikko]\"",					pp($tilrow['kpl'],2),      		$tootfinvoice);
			xml_add("OrderedQuantity QuantityUnitCode=\"$tilrow[yksikko]\"",					pp($tilrow['tilkpl'],2),      	$tootfinvoice);
			#xml_add("ConfirmedQuantity QuantityUnitCode=\"$tilrow[yksikko]\"",					"",      						$tootfinvoice);
			#xml_add("StartDate Format=\"CCYYMMDD\"", 											"", 							$tootfinvoice);
			#xml_add("EndDate Format=\"CCYYMMDD\"", 											"", 							$tootfinvoice);
			xml_add("UnitPriceAmount AmountCurrencyIdentifier=\"$lasrow[valkoodi]\"", pp($tilrow['hinta'], $yhtiorow["hintapyoristys"], 5, 2), $tootfinvoice);
			xml_add("RowIdentifier",                                    						$tilrow['tilaajanrivinro'], 	$tootfinvoice);
			xml_add("RowIdentifierUrlText", 													$tilrow['otunnus'],				$tootfinvoice);
			xml_add("RowIdentifierDate Format=\"CCYYMMDD\"", 						vlas_dateconv($tilrow['laadittu']), 		$tootfinvoice);
			#xml_add("OriginalInvoiceNumber", 													"", 							$tootfinvoice);
			#xml_add("RowDeliveryIdentifier", 													"", 							$tootfinvoice);
			#xml_add("RowDeliveryIdentifierUrlText", 											"", 							$tootfinvoice);
			xml_add("RowDeliveryDate Format=\"CCYYMMDD\"", 							vlas_dateconv($tilrow['toimitettuaika']),	$tootfinvoice);
			#xml_add("RowQuotationIdentifier", 													"", 							$tootfinvoice);
			#xml_add("RowQuotationIdentifierUrlText", 											"", 							$tootfinvoice);
			#xml_add("RowAgreementIdentifier", 													"", 							$tootfinvoice);
			#xml_add("RowAgreementIdentifierUrlText", 											"", 							$tootfinvoice);
			#xml_add("RowRequestOfQuotationIdentifier", 										"", 							$tootfinvoice);
			#xml_add("RowRequestOfQuotationIdentifierUrlText", 									"", 							$tootfinvoice);
			#xml_add("RowPriceListIdentifier", 													"", 							$tootfinvoice);
			#xml_add("RowPriceListIdentifierUrlText", 											"", 							$tootfinvoice);
			#fputs($tootfinvoice, "<RowDeliveryDetails>\r\n");
			#xml_add("RowWaybillIdentifier", 													"", 							$tootfinvoice);
			#xml_add("RowWaybillTypeCode", 														"", 							$tootfinvoice);
			#xml_add("RowDelivererIdentifier", 													"", 							$tootfinvoice);
			#xml_add("RowDelivererName", 														"", 							$tootfinvoice);
			#xml_add("RowDelivererCountryCode", 												"", 							$tootfinvoice);
			#xml_add("RowDelivererCountryName", 												"", 							$tootfinvoice);
			#xml_add("RowManufacturerIdentifier", 												"", 							$tootfinvoice);
			#xml_add("RowManufacturerName", 													"", 							$tootfinvoice);
			#xml_add("RowManufacturerCountryCode", 												"", 							$tootfinvoice);
			#xml_add("RowManufacturerCountryName", 												"", 							$tootfinvoice);
			#xml_add("RowTerminalAddressText", 													"", 							$tootfinvoice);
			#fputs($tootfinvoice, "</RowDeliveryDetails>\r\n");
			#xml_add("RowShortProposedAccountIdentifier", 										"", 							$tootfinvoice);
			#xml_add("RowNormalProposedAccountIdentifier", 										"", 							$tootfinvoice);
			#xml_add("RowAccountDimensionText", 												"", 							$tootfinvoice);
			#xml_add("RowSellerAccountText", 													"", 							$tootfinvoice);

			$RowFreeTexts = explode("|", trim($tilrow["kommentti"]));

			foreach ($RowFreeTexts as $RowFreeText) {
				if (trim($RowFreeText) != "") {
					$RowFreeText = wordwrap($RowFreeText, 512, "</RowFreeText>\r\n<RowFreeText>");

					xml_add("RowFreeText", $RowFreeText, $tootfinvoice);
				}
			}

			xml_add("RowDiscountPercent",                                    					pp($tilrow['ale'], 2),           $tootfinvoice);
			xml_add("RowVatRatePercent",                                    					pp($tilrow['alv'], $yhtiorow["hintapyoristys"], 5, 2),           	$tootfinvoice);
			xml_add("RowVatAmount AmountCurrencyIdentifier=\"$lasrow[valkoodi]\"",          	pp($vatamount, $yhtiorow["hintapyoristys"], 5, 2),      			$tootfinvoice);	// veron m‰‰r‰
			xml_add("RowVatExcludedAmount AmountCurrencyIdentifier=\"$lasrow[valkoodi]\"",  	pp($tilrow['rivihinta'], $yhtiorow["hintapyoristys"], 5, 2),      	$tootfinvoice);	// veroton rivihinta
			xml_add("RowAmount AmountCurrencyIdentifier=\"$lasrow[valkoodi]\"",             	pp($totalvat, $yhtiorow["hintapyoristys"], 5, 2),     				$tootfinvoice);	// verollinen rivihinta
			fputs($tootfinvoice, "</InvoiceRow>\r\n");
		}
	}
}

if (!function_exists('finvoice_lasku_loppu')) {
	function finvoice_lasku_loppu($tootfinvoice, $lasrow, $pankkitiedot, $masrow) {
		global $yhtiorow, $err_finvoice;

		if ($err_finvoice == 0) {

			#fputs($tootfinvoice, "<SpecificationDetails>\r\n");
			#xml_add("SpecificationFreeText", 												"", 								$tootfinvoice);
			#fputs($tootfinvoice, "</SpecificationDetails>\r\n");

			fputs($tootfinvoice, "<EpiDetails>\r\n");

			fputs($tootfinvoice, "<EpiIdentificationDetails>\r\n");
			xml_add("EpiDate Format=\"CCYYMMDD\"",                 							vlas_dateconv($lasrow['tapvm']),	$tootfinvoice);
			xml_add("EpiReference",                               							$lasrow['viite'],					$tootfinvoice);
			#xml_add("EpiUrl", 																"", 								$tootfinvoice);
			#xml_add("EpiEmail", 															"", 								$tootfinvoice);
			#xml_add("EpiOrderInfo", 														"", 								$tootfinvoice);
			fputs($tootfinvoice, "</EpiIdentificationDetails>\r\n");

			fputs($tootfinvoice, "<EpiPartyDetails>\r\n");
			fputs($tootfinvoice, "<EpiBfiPartyDetails>\r\n");
			xml_add("EpiBfiIdentifier IdentificationSchemeName=\"BIC\""	,					$pankkitiedot["pankkiswift1"],		$tootfinvoice);
			fputs($tootfinvoice, "</EpiBfiPartyDetails>\r\n");

			fputs($tootfinvoice, "<EpiBeneficiaryPartyDetails>\r\n");

			if ($masrow["factoring"] != "") {
				xml_add("EpiNameAddressDetails",    										$pankkitiedot["pankkinimi1"],		$tootfinvoice);
			}
			else {
				xml_add("EpiNameAddressDetails",    										$yhtiorow['nimi'],					$tootfinvoice);
				xml_add("EpiBei",                         		tulosta_ytunnus($yhtiorow['ytunnus'], $yhtiorow['maa']),		$tootfinvoice);
			}

			xml_add("EpiAccountID IdentificationSchemeName=\"IBAN\"",						str_replace(" ", "", $pankkitiedot["pankkiiban1"]),		$tootfinvoice);
			fputs($tootfinvoice, "</EpiBeneficiaryPartyDetails>\r\n");

			fputs($tootfinvoice, "</EpiPartyDetails>\r\n");

			fputs($tootfinvoice, "<EpiPaymentInstructionDetails>\r\n");
			#xml_add("EpiPaymentInstructionId", 											"", 								$tootfinvoice);
			#xml_add("EpiTransactionTypeCode", 												"", 								$tootfinvoice);
			#xml_add("EpiInstructionCode", 													"", 								$tootfinvoice);
			xml_add("EpiRemittanceInfoIdentifier IdentificationSchemeName=\"SPY\"",			spyconv($lasrow['viite']),    		$tootfinvoice);
			xml_add("EpiInstructedAmount AmountCurrencyIdentifier=\"$lasrow[valkoodi]\"", 	pp($lasrow['summa']),         		$tootfinvoice);
			xml_add("EpiCharge ChargeOption=\"SHA\"", 										"SHA",								$tootfinvoice);
			xml_add("EpiDateOptionDate Format=\"CCYYMMDD\"",                				vlas_dateconv($lasrow['erpcm']),	$tootfinvoice);
			fputs($tootfinvoice, "</EpiPaymentInstructionDetails>\r\n");
			fputs($tootfinvoice, "</EpiDetails>\r\n");

			#xml_add("InvoiceUrlNameText", 													"", 								$tootfinvoice);
			#xml_add("InvoiceUrlText", 														"", 								$tootfinvoice);

			fputs($tootfinvoice, "</Finvoice>\r\n");
		}
	}
}

?>