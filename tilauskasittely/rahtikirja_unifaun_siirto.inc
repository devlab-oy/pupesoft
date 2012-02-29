<?php

# Pupesoft -> Unifaun datasiirto

//jos on laitettu kollikentt‰‰n 0 niin ei yritet‰ luoda siirtotiedostoa
if ($kollityht > 0) {

	if (($unifaun_host != "" and $unifaun_user != "" and $unifaun_pass != "" and $unifaun_path != "") or $unifaun_path != "") {

		if ($phpnimi == "rahtikirja_custom.php") {
			$postirow  = $osoitelappurow;
			$rakir_row = $osoitelappurow;
		}
		else {
			$query = "	SELECT *
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]'
						AND tunnus in ($otunnukset)
						ORDER BY tunnus LIMIT 1";
			$tempr = mysql_query($query) or pupe_error($query);
			$postirow = mysql_fetch_assoc($tempr);
		}

		//	Haetaan asiakastiedot
		$query = "	SELECT *
					FROM asiakas
					WHERE yhtio = '$kukarow[yhtio]'
					and tunnus  = '$postirow[liitostunnus]'";
		$asres = mysql_query($query) or pupe_error($query);
		$asiakasrow = mysql_fetch_assoc($asres);

		// haetaan varaston osoitetiedot, k‰ytet‰‰n niit‰ l‰hetystietoina
		$query = "	SELECT nimi, nimitark, osoite, postino, postitp, maa
					FROM varastopaikat
					WHERE yhtio = '$kukarow[yhtio]'
					AND tunnus  = '$postirow[varasto]'";
		$tempr = mysql_query($query) or pupe_error($query);
		$postirow_varasto = mysql_fetch_assoc($tempr);

		// jos varastolle on annettu joku osoite, k‰ytet‰‰n sit‰
		if ($postirow_varasto["nimi"] != "") {
			$postirow["yhtio_nimi"]     = $postirow_varasto["nimi"];
			$postirow['yhtio_nimitark']	= $postirow_varasto["nimitark"];
			$postirow["yhtio_osoite"]   = $postirow_varasto["osoite"];
			$postirow["yhtio_postino"]  = $postirow_varasto["postino"];
			$postirow["yhtio_postitp"]  = $postirow_varasto["postitp"];
			$postirow["yhtio_maa"]      = $postirow_varasto["maa"];
		}

		$xmlstr  = '<?xml version="1.0" encoding="ISO-8859-1"?><printserver></printserver>';

		// Luodaan UNIFAUN-XML
		$xml = new SimpleXMLElement($xmlstr);

			// Metatiedot
			#<meta>
			#	<val n="printer"></val>							# Sends the print job to defined printer/ID. The value must be enclosed in pipe characters, |.
			#	<val n="favorite"></val>						# Defines the print favourite in the online system which is used to auto-complete the order file if necessary.
			#	<val n="partition"></val>						# Defines the profile group where the shipment should be stored in the online system.
			#</meta>

			// L‰hett‰j‰n tiedot
			$uni_sender = $xml->addChild('sender');	# Attribute sndid corresponds to sender ID/quick ID. Any contents. Mandatory
			$uni_sender->addAttribute('sndid', "0".$yhtiorow["tunnus"]);

				$uni_snd_val = $uni_sender->addChild('val', $postirow["yhtio_nimi"]); # Sender's name
				$uni_snd_val->addAttribute('n', "name");

				$uni_snd_val = $uni_sender->addChild('val', $postirow["yhtio_osoite"]); # Address line 1
				$uni_snd_val->addAttribute('n', "address1");

				$uni_snd_val = $uni_sender->addChild('val', ""); # Address line 2
				$uni_snd_val->addAttribute('n', "address2");

				$uni_snd_val = $uni_sender->addChild('val', $postirow["yhtio_postino"]); # Zip code
				$uni_snd_val->addAttribute('n', "zipcode");

				$uni_snd_val = $uni_sender->addChild('val', $postirow["yhtio_postitp"]); # City
				$uni_snd_val->addAttribute('n', "city");

				$uni_snd_val = $uni_sender->addChild('val', $postirow["yhtio_maa"]); # Country code according to ISO-standard
				$uni_snd_val->addAttribute('n', "country");

				$uni_snd_val = $uni_sender->addChild('val', ""); # Contact person
				$uni_snd_val->addAttribute('n', "contact");

				$uni_snd_val = $uni_sender->addChild('val', $yhtiorow["puhelin"]); # Phone number
				$uni_snd_val->addAttribute('n', "phone");

				$uni_snd_val = $uni_sender->addChild('val', $yhtiorow["fax"]); # Fax number
				$uni_snd_val->addAttribute('n', "fax");

				$uni_snd_val = $uni_sender->addChild('val', ""); # Organisation number (only for Sweden)
				$uni_snd_val->addAttribute('n', "orgno");

				$uni_snd_val = $uni_sender->addChild('val', str_replace("0037", "", $postirow["yhtio_ovttunnus"])); # VAT number
				$uni_snd_val->addAttribute('n', "vatno");

				$uni_snd_val = $uni_sender->addChild('val', $yhtiorow["email"]); # E-mail
				$uni_snd_val->addAttribute('n', "email");

				$uni_snd_val = $uni_sender->addChild('val', ""); # Mobile phone number. In Sweden the number must begin with 07 and contain 10 digits.
				$uni_snd_val->addAttribute('n', "sms");

				// L‰hett‰j‰n rahtisopimustiedot
				$uni_partner = $uni_sender->addChild('partner'); # Attribute parid corresponds to carrier's ID. See SUP-112-Services-en.xls.
				$uni_partner->addAttribute('parid', $toitarow['rahdinkuljettaja']);

					$uni_par_val = $uni_partner->addChild('val', $toitarow['sopimusnro']); 	# Customer number
					$uni_par_val->addAttribute('n', "custno");

					$uni_par_val = $uni_partner->addChild('val', $toitarow['sopimusnro']); 	# Customer number for international services
					$uni_par_val->addAttribute('n', "custno_international");

					$uni_par_val = $uni_partner->addChild('val', ""); 	# EDI-address. UFPS only.
					$uni_par_val->addAttribute('n', "ediaddress");

					$uni_par_val = $uni_partner->addChild('val', ""); 	# Pallet reg. number for EUR-pallets
					$uni_par_val->addAttribute('n', "palletregno");

					$uni_par_val = $uni_partner->addChild('val', ""); 	# Terminal, used with delivery terms for international freights.
					$uni_par_val->addAttribute('n', "terminal");

					$uni_par_val = $uni_partner->addChild('val', ""); 	# Number for PlusGiro. Used mostly by Cash On Delivery add-on.
					$uni_par_val->addAttribute('n', "postgiro");

					$uni_par_val = $uni_partner->addChild('val', ""); 	# Number for BankGiro. Used mostly by Cash On Delivery add-on.
					$uni_par_val->addAttribute('n', "bankgiro");

					$uni_par_val = $uni_partner->addChild('val', ""); 	# Specifies an offshore account.
					$uni_par_val->addAttribute('n', "konto");

					$uni_par_val = $uni_partner->addChild('val', $yhtiorow["pankkitili1"]); 	# IBAN account number
					$uni_par_val->addAttribute('n', "iban");

					$uni_par_val = $uni_partner->addChild('val', $yhtiorow["pankkiswift1"]); 	# BIC number
					$uni_par_val->addAttribute('n', "bic");

					$uni_par_val = $uni_partner->addChild('val', ""); 	# Payment method. Used for Posten's mail services. Valid values: INVO = Credit without delivery note INVODN = Credit with delivery note METERED = Domestic franking STAMP = Stamp/cash
					$uni_par_val->addAttribute('n', "paymentmethod");

			// Vastaanottajan tiedot
			$uni_receiver = $xml->addChild('receiver');	# Any contents. Mandatory.
			$uni_receiver->addAttribute('rcvid', $asiakasrow["tunnus"]);

				$uni_rcv_val = $uni_receiver->addChild('val', trim($rakir_row["toim_nimi"]."".$rakir_row["toim_nimitark"])); # Receiver's name
				$uni_rcv_val->addAttribute('n', "name");

				$uni_rcv_val = $uni_receiver->addChild('val', urlencode($rakir_row["toim_osoite"])); # Address line 1
				$uni_rcv_val->addAttribute('n', "address1");

				$uni_rcv_val = $uni_receiver->addChild('val', ""); # Address line 2
				$uni_rcv_val->addAttribute('n', "address2");

				$uni_rcv_val = $uni_receiver->addChild('val', $rakir_row["toim_postino"]); # Zipcode
				$uni_rcv_val->addAttribute('n', "zipcode");

				$uni_rcv_val = $uni_receiver->addChild('val', urlencode($rakir_row["toim_postitp"])); # City
				$uni_rcv_val->addAttribute('n', "city");

				$uni_rcv_val = $uni_receiver->addChild('val', ""); # State
				$uni_rcv_val->addAttribute('n', "state");

				$uni_rcv_val = $uni_receiver->addChild('val', $rakir_row['toim_maa']); # Country code according to ISO standard
				$uni_rcv_val->addAttribute('n', "country");

				$uni_rcv_val = $uni_receiver->addChild('val', $postirow["tilausyhteyshenkilo"]); # Contact person
				$uni_rcv_val->addAttribute('n', "contact");

				$uni_rcv_val = $uni_receiver->addChild('val', $rakir_row["puhelin"]); # Phone number
				$uni_rcv_val->addAttribute('n', "phone");

				$uni_rcv_val = $uni_receiver->addChild('val', $asiakasrow["fax"]); # Fax number
				$uni_rcv_val->addAttribute('n', "fax");

				$uni_rcv_val = $uni_receiver->addChild('val', ""); # Organisation number (for Sweden only)
				$uni_rcv_val->addAttribute('n', "orgno");

				$uni_rcv_val = $uni_receiver->addChild('val', $postirow["ytunnus"]); # VAT number
				$uni_rcv_val->addAttribute('n', "vatno");

				$uni_rcv_val = $uni_receiver->addChild('val', $asiakasrow["email"]); # E-mail
				$uni_rcv_val->addAttribute('n', "email");

				$uni_rcv_val = $uni_receiver->addChild('val', $rakir_row["puhelin"]); # Mobile phone number. For Sweden, the number must begin with 07 and contain 10 characters.
				$uni_rcv_val->addAttribute('n', "sms");

				$uni_rcv_val = $uni_receiver->addChild('val', ""); # Door code
				$uni_rcv_val->addAttribute('n', "doorcode");

				// Vastaanottajan rahtisopimustiedot
				$uni_partner = $uni_receiver->addChild('partner'); # Attribute parid corresponds to carrier's ID. See SUP-112-Services-en.xls.
				$uni_partner->addAttribute('parid', "");

					$uni_par_val = $uni_partner->addChild('val', ""); 	# Customer number
					$uni_par_val->addAttribute('n', "custno");

					$uni_par_val = $uni_partner->addChild('val', ""); 	# Pallet reg. number for EUR-pallets
					$uni_par_val->addAttribute('n', "palletregno");

					$uni_par_val = $uni_partner->addChild('val', ""); 	# Terminal, used with delivery terms for international freights.
					$uni_par_val->addAttribute('n', "terminal");

					$uni_par_val = $uni_partner->addChild('val', ""); 	# Number for PlusGiro. Used mostly by Cash On Delivery add-on.
					$uni_par_val->addAttribute('n', "postgiro");

					$uni_par_val = $uni_partner->addChild('val', ""); 	# Number for BankGiro. Used mostly by Cash On Delivery add-on.
					$uni_par_val->addAttribute('n', "bankgiro");

					$uni_par_val = $uni_partner->addChild('val', ""); 	# Specifies an offshore account.
					$uni_par_val->addAttribute('n', "konto");

					$uni_par_val = $uni_partner->addChild('val', ""); 	# Agent's identity, mandatory value for DBSchenker PrivPak. Normally set by application.
					$uni_par_val->addAttribute('n', "agentno");

			// L‰hetyksen tiedot
			$uni_shipment = $xml->addChild('shipment');	# Unique order number. Any contents. Mandatory. Order number is searchable in the system but not printed on shipping documents.
			$uni_shipment->addAttribute('orderno', $postirow["tunnus"]);

				$uni_shi_val = $uni_shipment->addChild('val', $yhtiorow["tunnus"]); # Defines the sender. Refers to the sndid value for sender.
				$uni_shi_val->addAttribute('n', "from");

				$uni_shi_val = $uni_shipment->addChild('val', ""); # Defines the legal sender (not printed on shipping documents).
				$uni_shi_val->addAttribute('n', "legalfrom");

				$uni_shi_val = $uni_shipment->addChild('val', $asiakasrow["tunnus"]); # Defines the receiver. Refers to rcvid value for receiver.
				$uni_shi_val->addAttribute('n', "to");

				$uni_shi_val = $uni_shipment->addChild('val', ""); # Defines the legal receiver (not printed on shipping documents).
				$uni_shi_val->addAttribute('n', "legalto");

				$uni_shi_val = $uni_shipment->addChild('val', ""); # Defines the agent's ID for recipient in shipment. Used by DBSchenker PrivPak to store the agent's details, address etc. For Bring it's used to store address details to MyQuickBox machine.
				$uni_shi_val->addAttribute('n', "agentto");

				$uni_shi_val = $uni_shipment->addChild('val', ""); # Exporter
				$uni_shi_val->addAttribute('n', "customsfrom");

				$uni_shi_val = $uni_shipment->addChild('val', ""); # Importer
				$uni_shi_val->addAttribute('n', "customsto");

				$uni_shi_val = $uni_shipment->addChild('val', $postirow["tunnus"]); # Shipment ID. UFPS only.
				$uni_shi_val->addAttribute('n', "shpid");

				$uni_shi_val = $uni_shipment->addChild('val', "freetext1"); # Free text field with any contents. Can be used for delivery instructions, for example. It is printed on shipping documents. 4 lines available, freetext1-4. Max. 30 characters/line.
				$uni_shi_val->addAttribute('n', "freetext1");

				$uni_shi_val = $uni_shipment->addChild('val', "freetext2"); # Free text field with any contents. Can be used for delivery instructions, for example. It is printed on shipping documents. 4 lines available, freetext1-4. Max. 30 characters/line.
				$uni_shi_val->addAttribute('n', "freetext2");

				$uni_shi_val = $uni_shipment->addChild('val', "freetext3"); # Free text field with any contents. Can be used for delivery instructions, for example. It is printed on shipping documents. 4 lines available, freetext1-4. Max. 30 characters/line.
				$uni_shi_val->addAttribute('n', "freetext3");

				$uni_shi_val = $uni_shipment->addChild('val', "freetext4"); # Free text field with any contents. Can be used for delivery instructions, for example. It is printed on shipping documents. 4 lines available, freetext1-4. Max. 30 characters/line.
				$uni_shi_val->addAttribute('n', "freetext4");

				$uni_shi_val = $uni_shipment->addChild('val', ""); # Number of EUR pallets in the shipment. Requires palletregno for sender and receiver.
				$uni_shi_val->addAttribute('n', "eurpallets");

				$uni_shi_val = $uni_shipment->addChild('val', $postirow["tunnus"]); # Shipment reference. Any contents. Max. 17 characters.
				$uni_shi_val->addAttribute('n', "reference");

				$uni_shi_val = $uni_shipment->addChild('val', $postirow["tunnus"]); # Shipment reference as barcode. Max. 17 numeric characters.
				$uni_shi_val->addAttribute('n', "referencebarcode");

				$uni_shi_val = $uni_shipment->addChild('val', ""); # Receiver's reference. Any contents. Max. 17 characters.
				$uni_shi_val->addAttribute('n', "rcvreference");

				$uni_shi_val = $uni_shipment->addChild('val', "sisfreetext1"); # Free text field with any contents. Can be used for delivery instructions, for example. It is printed on shipping documents. 5 lines available, sisfreetext1-5. Max. 30 characters/line.
				$uni_shi_val->addAttribute('n', "sisfreetext1");

				$uni_shi_val = $uni_shipment->addChild('val', "sisfreetext2"); # Free text field with any contents. Can be used for delivery instructions, for example. It is printed on shipping documents. 5 lines available, sisfreetext1-5. Max. 30 characters/line.
				$uni_shi_val->addAttribute('n', "sisfreetext2");

				$uni_shi_val = $uni_shipment->addChild('val', "sisfreetext3"); # Free text field with any contents. Can be used for delivery instructions, for example. It is printed on shipping documents. 5 lines available, sisfreetext1-5. Max. 30 characters/line.
				$uni_shi_val->addAttribute('n', "sisfreetext3");

				$uni_shi_val = $uni_shipment->addChild('val', "sisfreetext4"); # Free text field with any contents. Can be used for delivery instructions, for example. It is printed on shipping documents. 5 lines available, sisfreetext1-5. Max. 30 characters/line.
				$uni_shi_val->addAttribute('n', "sisfreetext4");

				$uni_shi_val = $uni_shipment->addChild('val', "sisfreetext5"); # Free text field with any contents. Can be used for delivery instructions, for example. It is printed on shipping documents. 5 lines available, sisfreetext1-5. Max. 30 characters/line.
				$uni_shi_val->addAttribute('n', "sisfreetext5");

				$uni_shi_val = $uni_shipment->addChild('val', "cmrfreetext1"); # Free text field with any contents. Can be used for delivery instructions, for example. Only printed on CMR waybill. 5 lines available, cmrfreetext1-5. Max. 30 characters/line.
				$uni_shi_val->addAttribute('n', "cmrfreetext1");

				$uni_shi_val = $uni_shipment->addChild('val', "cmrfreetext2"); # Free text field with any contents. Can be used for delivery instructions, for example. Only printed on CMR waybill. 5 lines available, cmrfreetext1-5. Max. 30 characters/line.
				$uni_shi_val->addAttribute('n', "cmrfreetext2");

				$uni_shi_val = $uni_shipment->addChild('val', "cmrfreetext3"); # Free text field with any contents. Can be used for delivery instructions, for example. Only printed on CMR waybill. 5 lines available, cmrfreetext1-5. Max. 30 characters/line.
				$uni_shi_val->addAttribute('n', "cmrfreetext3");

				$uni_shi_val = $uni_shipment->addChild('val', "cmrfreetext4"); # Free text field with any contents. Can be used for delivery instructions, for example. Only printed on CMR waybill. 5 lines available, cmrfreetext1-5. Max. 30 characters/line.
				$uni_shi_val->addAttribute('n', "cmrfreetext4");

				$uni_shi_val = $uni_shipment->addChild('val', "cmrfreetext5"); # Free text field with any contents. Can be used for delivery instructions, for example. Only printed on CMR waybill. 5 lines available, cmrfreetext1-5. Max. 30 characters/line.
				$uni_shi_val->addAttribute('n', "cmrfreetext5");

				$uni_shi_val = $uni_shipment->addChild('val', ""); # Fields for additional documents. 2 lines available, cmrdocuments1-2. Max. 30 characters/line.
				$uni_shi_val->addAttribute('n', "cmrdocuments1");

				$uni_shi_val = $uni_shipment->addChild('val', ""); # Fields for additional documents. 2 lines available, cmrdocuments1-2. Max. 30 characters/line.
				$uni_shi_val->addAttribute('n', "cmrdocuments2");

				$uni_shi_val = $uni_shipment->addChild('val', ""); # Specifies any special agreement. Max. 30 characters.
				$uni_shi_val->addAttribute('n', "cmrspecialagreement");

				$uni_shi_val = $uni_shipment->addChild('val', ""); # Delivery terms. See SUP-112-Services-en.xls for valid delivery terms.
				$uni_shi_val->addAttribute('n', "termcode");

				$uni_shi_val = $uni_shipment->addChild('val', ""); # Defines the location where takeover for the specified delivery term is done.
				$uni_shi_val->addAttribute('n', "termlocation");

				$uni_shi_val = $uni_shipment->addChild('val', ""); # Defines which documents to print. Pipe characters are mandatory. Valid values: |label| = Label only, |sis| = Waybill only, |*| = None of the above
				$uni_shi_val->addAttribute('n', "printset");

				$uni_shi_val = $uni_shipment->addChild('val', date("Y-m-d")); # Defines shipment date. Printed on shipping documents. The default value is the current date. Please note that EDI is sent on this date.
				$uni_shi_val->addAttribute('n', "shipdate");

				$uni_shi_val = $uni_shipment->addChild('val', ""); # Customs currency unit
				$uni_shi_val->addAttribute('n', "customsunit");

				$uni_service = $uni_shipment->addChild('service'); # Corresponds to carrier's service. See SUP-112-Services-en.xls for valid services.
				$uni_service->addAttribute('srvid', $toitarow['virallinen_selite']);

					$uni_ser_val = $uni_service->addChild('val', "no"); # Defines if the shipment is a return shipment or not. Valid values: yes, no
					$uni_ser_val->addAttribute('n', "returnlabel");

					$uni_ser_val = $uni_service->addChild('val', "RETURN"); # Defines action when the package is undeliverable. Only for Posten Postpaket Utrikes. RETURN = Return to sender, ABANDON = Treat as abandoned in receiver's country.
					$uni_ser_val->addAttribute('n', "nondelivery");

					$uni_booking = $uni_service->addChild('service'); # Booking information for pick up with DBSchenker. UFPS only.

						$uni_ser_val = $uni_booking->addChild('val', ""); # OPAL-number. Acquired from DBSchenker.
						$uni_ser_val->addAttribute('n', "bookingid");

						$uni_ser_val = $uni_booking->addChild('val', ""); # Booking office, numeric code. See SUP-112-Services-en.xls for valid codes.
						$uni_ser_val->addAttribute('n', "bookingoffice");


					if ($toitarow['virallinen_selite'] == "P19" and $rakir_row["puhelin"] != "") {
						$uni_addon = $uni_service->addChild('addon'); # Corresponds to add-on service. See SUP-112-Services-en.xls for valid add-on services.
						$uni_addon->addAttribute('adnid', "NOTSMS");
						$uni_add_val = $uni_addon->addChild('val', $rakir_row["puhelin"]); # Defines value for misctype.
						$uni_add_val->addAttribute('n', "misc");
						$uni_add_val = $uni_addon->addChild('val', "PHONE"); # Used to define notification mode for add-on NOT. Valid values: PHONE = Phone, FAX = Fax.
						$uni_add_val->addAttribute('n', "misctype");
					}
					elseif ($toitarow['virallinen_selite'] == "P19") {
						$uni_addon = $uni_service->addChild('addon'); # Corresponds to add-on service. See SUP-112-Services-en.xls for valid add-on services.
						$uni_addon->addAttribute('adnid', "NOTLTR");
					}

					if ($rakir_row["jv"] != '' or $mehto['jv'] != '') {
						$uni_addon = $uni_service->addChild('addon'); # Corresponds to add-on service. See SUP-112-Services-en.xls for valid add-on services.
						$uni_addon->addAttribute('adnid', "COD");

							$uni_add_val = $uni_addon->addChild('val', $yhteensa); # Amount. Used only with add-on COD (Cash On Delivery).
							$uni_add_val->addAttribute('n', "amount");

							$uni_add_val = $uni_addon->addChild('val', $viite); # Payment reference. Used with add-on COD.
							$uni_add_val->addAttribute('n', "reference");
					}

					#$uni_addon = $uni_service->addChild('addon'); # Corresponds to add-on service. See SUP-112-Services-en.xls for valid add-on services.
					#$uni_addon->addAttribute('adnid', "");
                    #
					#	$uni_add_val = $uni_addon->addChild('val', ""); # Amount. Used only with add-on COD (Cash On Delivery).
					#	$uni_add_val->addAttribute('n', "amount");
                    #
					#	$uni_add_val = $uni_addon->addChild('val', ""); # Customer number for carrier. Used primarily with RPAY (receiver pays) and OPAY (other payer).
					#	$uni_add_val->addAttribute('n', "custno");
                    #
					#	$uni_add_val = $uni_addon->addChild('val', ""); # Payment reference. Used with add-on COD.
					#	$uni_add_val->addAttribute('n', "reference");
                    #
					#	$uni_add_val = $uni_addon->addChild('val', ""); # Defines value for misctype.
					#	$uni_add_val->addAttribute('n', "misc");
                    #
					#	$uni_add_val = $uni_addon->addChild('val', ""); # Used to define notification mode for add-on NOT. Valid values: PHONE = Phone, FAX = Fax.
					#	$uni_add_val->addAttribute('n', "misctype");
                    #
					#	$uni_add_val = $uni_addon->addChild('val', ""); # Defines name of collection point for Posten add-on DLVNOT.
					#	$uni_add_val->addAttribute('n', "text1");
                    #
					#	$uni_add_val = $uni_addon->addChild('val', ""); # Defines address for collection point for Posten add-on DLVNOT.
					#	$uni_add_val->addAttribute('n', "text2");
                    #
					#	$uni_add_val = $uni_addon->addChild('val', ""); # Defines phone number for Posten add-ons PODNOT, DLVNOT and PRENOT.
					#	$uni_add_val->addAttribute('n', "text3");
                    #
					#	$uni_add_val = $uni_addon->addChild('val', ""); # Defines e-mail address for Posten add-ons PODNOT, DLVNOT and PRENOT.
					#	$uni_add_val->addAttribute('n', "text4");
                    #
					#	$uni_add_val = $uni_addon->addChild('val', ""); # Max. temperature allowed. Used with DBSchenker ColdSped.
					#	$uni_add_val->addAttribute('n', "tempmax");
                    #
					#	$uni_add_val = $uni_addon->addChild('val', ""); # Min. temperature allowed. Used by DBSchenker ColdSped.
					#	$uni_add_val->addAttribute('n', "tempmin");

				$uni_parcel = $uni_shipment->addChild('container'); # Parcel information can be supplied in various ways. See p. 7.
				$uni_parcel->addAttribute('type', "parcel");

					$uni_par_val = $uni_parcel->addChild('val', $kollityht); # Number of parcels
					$uni_par_val->addAttribute('n', "copies");

					$uni_par_val = $uni_parcel->addChild('val', ""); # Parcel ID. Used only for custom parcel ID. UFPS only. Cntid is to be incremented according to number of parcels.
					$uni_par_val->addAttribute('n', "cntid1");

					$uni_par_val = $uni_parcel->addChild('val', ""); # Goods marking
					$uni_par_val->addAttribute('n', "marking");

					$uni_par_val = $uni_parcel->addChild('val', "PC"); # Package code. See SUP-112-Services-en.xls for valid package codes.
					$uni_par_val->addAttribute('n', "packagecode");

					$uni_par_val = $uni_parcel->addChild('val', $kilotyht); # Weight
					$uni_par_val->addAttribute('n', "weight");

					$uni_par_val = $uni_parcel->addChild('val', ""); # Volume
					$uni_par_val->addAttribute('n', "volume");

					$uni_par_val = $uni_parcel->addChild('val', ""); # Loadmeter. Can only be specified for entire shipment.
					$uni_par_val->addAttribute('n', "area");

					$uni_par_val = $uni_parcel->addChild('val', ""); # Length
					$uni_par_val->addAttribute('n', "length");

					$uni_par_val = $uni_parcel->addChild('val', ""); # Width
					$uni_par_val->addAttribute('n', "width");

					$uni_par_val = $uni_parcel->addChild('val', ""); # Height
					$uni_par_val->addAttribute('n', "height");

					$uni_par_val = $uni_parcel->addChild('val', ""); # Item number
					$uni_par_val->addAttribute('n', "itemno");

					$uni_par_val = $uni_parcel->addChild('val', $pakkauskuvaus[0]); # Contents
					$uni_par_val->addAttribute('n', "contents");

					$uni_par_val = $uni_parcel->addChild('val', ""); # UN-number for ADR. Supplied as a 4 digit code.
					$uni_par_val->addAttribute('n', "dnguncode");

					$uni_par_val = $uni_parcel->addChild('val', ""); # Label number for ADR
					$uni_par_val->addAttribute('n', "dnghzcode");

					$uni_par_val = $uni_parcel->addChild('val', ""); # Packaging group/ADR-class. Supplied as I, II or III.
					$uni_par_val->addAttribute('n', "dngpkcode");

					$uni_par_val = $uni_parcel->addChild('val', ""); # ADR-class
					$uni_par_val->addAttribute('n', "dngadrclass");

					$uni_par_val = $uni_parcel->addChild('val', ""); # Official transport name for item regarding ADR
					$uni_par_val->addAttribute('n', "dngdescr");

					$uni_par_val = $uni_parcel->addChild('val', ""); # Defines if the contents contaminate the marine environment, ADR only. Valid values: 1 = Toxic and 2 = Non-toxic for the marine environment
					$uni_par_val->addAttribute('n', "dngmpcode");

					$uni_par_val = $uni_parcel->addChild('val', ""); # Note for ADR goods
					$uni_par_val->addAttribute('n', "dngnote");

					$uni_par_val = $uni_parcel->addChild('val', ""); # Net weight for ADR goods class I (usually explosive contents). Always mandatory for DBSchenker, regardless of class. Defined in kg.
					$uni_par_val->addAttribute('n', "dngnetweight");


		if ($unifaun_path != '') {
			if (substr($unifaun_path,-1) != '/') {
				$unifaun_path .= '/';
			}
			$filenimi = $unifaun_path."dpdimport-".md5(uniqid(rand(),true)).".dat";
		}
		else {
			$filenimi = "/tmp/dpdimport-".md5(uniqid(rand(),true)).".dat";
		}

		//kirjoitetaan faili levylle..
		if (file_put_contents($filenimi, $xml->asXML()) === FALSE) {
			echo "<br><font class='error'>".t("VIRHE: tiedoston kirjoitus ep‰onnistui")."!</font><br>";
		}

		if ($unifaun_host != "" and $unifaun_user != "" and $unifaun_pass != "" and $unifaun_path != "") {
			// tarvitaan  $ftphost $ftpuser $ftppass $ftppath $ftpfile
			// palautetaan $palautus ja $syy
			$ftphost = $unifaun_host;
			$ftpuser = $unifaun_user;
			$ftppass = $unifaun_pass;
			$ftppath = $unifaun_path;
			$ftpfile = realpath($filenimi);
			require ("inc/ftp-send.inc");
		}
	}
	else {
		echo "<br><font class='error'>".t("UNIFAUN-aineiston luontiin tarvittavia parametreja puuttuu!")."</font><br>";
	}
}

?>