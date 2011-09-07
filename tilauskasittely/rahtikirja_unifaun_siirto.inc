<?php

# Pupesoft -> Unifaun datasiirto

//jos on laitettu kollikenttään 0 niin ei yritetä luoda siirtotiedostoa
if ($kollityht > 0) {

	if (($unifaun_host != "" and $unifaun_user != "" and $unifaun_pass != "" and $unifaun_path != "") or $unifaun_path != "") {

		if ($phpnimi == "rahtikirja_custom.php") {
			$postirow = $osoitelappurow;
			$rakir_row = $osoitelappurow;
		}
		else {
			$query = "SELECT * from lasku where yhtio = '$kukarow[yhtio]' and tunnus in ($otunnukset) order by tunnus limit 1";
			$tempr = mysql_query($query) or pupe_error($query);
			$postirow = mysql_fetch_array($tempr);
		}

		// haetaan varaston osoitetiedot, käytetään niitä lähetystietoina
		$query = "	SELECT nimi, nimitark, osoite, postino, postitp, maa
					FROM varastopaikat
					WHERE yhtio = '$kukarow[yhtio]'
					AND tunnus = '$postirow[varasto]'";
		$tempr = mysql_query($query) or pupe_error($query);
		$postirow_varasto = mysql_fetch_array($tempr);

		// jos varastolle on annettu joku osoite, käytetään sitä
		if ($postirow_varasto["nimi"] != "") {
			$postirow["yhtio_nimi"]     = $postirow_varasto["nimi"];
			$postirow['yhtio_nimitark']	= $postirow_varasto["nimitark"];
			$postirow["yhtio_osoite"]   = $postirow_varasto["osoite"];
			$postirow["yhtio_postino"]  = $postirow_varasto["postino"];
			$postirow["yhtio_postitp"]  = $postirow_varasto["postitp"];
			$postirow["yhtio_maa"]      = $postirow_varasto["maa"];
		}

		//Näin se oli muuallakin onkai se sitten oikein?
		$rahtikirjanro = $lotsikot[0];

		// Luodaan UNIFAUN-XML
		$xml = new SimpleXMLElement("<?xml version="1.0" encoding=\"ISO-8859-1\"?>");

		$uni_printserver = $xml->addChild('printserver');
			
			// Metatiedot
			#<meta>
			#	<val n="printer"></val>							# Sends the print job to defined printer/ID. The value must be enclosed in pipe characters, |.
			#	<val n="favorite"></val>						# Defines the print favourite in the online system which is used to auto-complete the order file if necessary.
			#	<val n="partition"></val>						# Defines the profile group where the shipment should be stored in the online system.
			#</meta>
				
			// Lähettäjän tiedot
			$uni_sender = $uni_printserver->addChild('sender');	# Attribute sndid corresponds to sender ID/quick ID. Any contents. Mandatory		
				$uni_sender->addAttribute('sndid', "");				
				
				$uni_snd_val = $uni_sender->addChild('val', ""); # Sender's name
				$uni_snd_val->addAttribute('n', "name");
				
				$uni_snd_val = $uni_sender->addChild('val', ""); # Address line 1
				$uni_snd_val->addAttribute('n', "address1");
				
				$uni_snd_val = $uni_sender->addChild('val', ""); # Address line 2
				$uni_snd_val->addAttribute('n', "address2");
				
				$uni_snd_val = $uni_sender->addChild('val', ""); # Zip code
				$uni_snd_val->addAttribute('n', "zipcode");
				
				$uni_snd_val = $uni_sender->addChild('val', ""); # City
				$uni_snd_val->addAttribute('n', "city");
				
				$uni_snd_val = $uni_sender->addChild('val', ""); # Country code according to ISO-standard
				$uni_snd_val->addAttribute('n', "country");
				
				$uni_snd_val = $uni_sender->addChild('val', ""); # Contact person
				$uni_snd_val->addAttribute('n', "contact");
				
				$uni_snd_val = $uni_sender->addChild('val', ""); # Phone number
				$uni_snd_val->addAttribute('n', "phone");
				
				$uni_snd_val = $uni_sender->addChild('val', ""); # Fax number
				$uni_snd_val->addAttribute('n', "fax");
				
				$uni_snd_val = $uni_sender->addChild('val', ""); # Organisation number (only for Sweden)
				$uni_snd_val->addAttribute('n', "orgno");
				
				$uni_snd_val = $uni_sender->addChild('val', ""); # VAT number
				$uni_snd_val->addAttribute('n', "vatno");
				
				$uni_snd_val = $uni_sender->addChild('val', ""); # E-mail
				$uni_snd_val->addAttribute('n', "email");
				
				$uni_snd_val = $uni_sender->addChild('val', ""); # Mobile phone number. In Sweden the number must begin with 07 and contain 10 digits.
				$uni_snd_val->addAttribute('n', "sms");
				
				#<partner parid="...">							# Attribute parid corresponds to carrier's ID. See SUP-112-Services-en.xls.
				#	<val n="custno"></val>						# Customer number
				#	<val n="custno_international"></val>		# Customer number for international services
				#	<val n="ediaddress"></val>					# EDI-address. UFPS only.
				#	<val n="palletregno"></val>					# Pallet reg. number for EUR-pallets
				#	<val n="terminal"></val>					# Terminal, used with delivery terms for international freights.
				#	<val n="postgiro"></val>					# Number for PlusGiro. Used mostly by Cash On Delivery add-on.
				#	<val n="bankgiro"></val>					# Number for BankGiro. Used mostly by Cash On Delivery add-on.
				#	<val n="konto"></val>						# Specifies an offshore account.
				#	<val n="iban"></val>						# IBAN account number
				#	<val n="bic"></val>							# BIC number
				#	<val n="paymentmethod"></val>				# Payment method. Used for Posten's mail services. Valid values: INVO = Credit without delivery note INVODN = Credit with delivery note METERED = Domestic franking STAMP = Stamp/cash
				#</partner>			

		/*
		<?xml version="1.0" encoding="ISO-8859-1"?>
		<printserver>
			
			<sender sndid="...">								# Attribute sndid corresponds to sender ID/quick ID. Any contents. Mandatory.
				<val n="name"></val>							# Sender's name
				<val n="address1"></val>						# Address line 1.
				<val n="address2"></val>						# Address line 2
				<val n="zipcode"></val>							# Zip code
				<val n="city"></val>							# City
				<val n="country"></val>							# Country code according to ISO-standard
				<val n="contact"></val>							# Contact person
				<val n="phone"></val>							# Phone number
				<val n="fax"></val>								# Fax number
				<val n="orgno"></val>							# Organisation number (only for Sweden)
				<val n="vatno"></val>							# VAT number
				<val n="email"></val>							# E-mail
				<val n="sms"></val>								# Mobile phone number. In Sweden the number must begin with 07 and contain 10 digits.
				<partner parid="...">							# Attribute parid corresponds to carrier's ID. See SUP-112-Services-en.xls.
					<val n="custno"></val>						# Customer number
					<val n="custno_international"></val>		# Customer number for international services
					<val n="ediaddress"></val>					# EDI-address. UFPS only.
					<val n="palletregno"></val>					# Pallet reg. number for EUR-pallets
					<val n="terminal"></val>					# Terminal, used with delivery terms for international freights.
					<val n="postgiro"></val>					# Number for PlusGiro. Used mostly by Cash On Delivery add-on.
					<val n="bankgiro"></val>					# Number for BankGiro. Used mostly by Cash On Delivery add-on.
					<val n="konto"></val>						# Specifies an offshore account.
					<val n="iban"></val>						# IBAN account number
					<val n="bic"></val>							# BIC number
					<val n="paymentmethod"></val>				# Payment method. Used for Posten's mail services. Valid values: INVO = Credit without delivery note INVODN = Credit with delivery note METERED = Domestic franking STAMP = Stamp/cash
				</partner>
			</sender>
			<receiver rcvid="...">								# Any contents. Mandatory.
				<val n="name"></val>							# Receiver's name
				<val n="address1"></val>						# Address line 1
				<val n="address2"></val>						# Address line 2
				<val n="zipcode"></val>							# Zipcode
				<val n="city"></val>							# City
				<val n="state"></val>							# State
				<val n="country"></val>							# Country code according to ISO standard
				<val n="contact"></val>							# Contact person
				<val n="phone"></val>							# Phone number
				<val n="fax"></val>								# Fax number
				<val n="orgno"></val>							# Organisation number (for Sweden only)
				<val n="vatno"></val>							# VAT number
				<val n="email"></val>							# E-mail
				<val n="sms"></val>								# Mobile phone number. For Sweden, the number must begin with 07 and contain 10 characters.
				<val n="doorcode"></val>						# Door code
				<partner parid="...">							# Attribute parid corresponds to carrier's ID. See SUP-112-Services-en.xls.
					<val n="custno"></val>						# Customer number
					<val n="palletregno"></val>					# Pallet reg. number for EUR-pallets
					<val n="terminal"></val>					# Terminal, used with delivery terms for international freights.
					<val n="postgiro"></val>					# Number for PlusGiro. Used mostly by Cash On Delivery add-on.
					<val n="bankgiro"></val>					# Number for BankGiro. Used mostly by Cash On Delivery add-on.
					<val n="konto"></val>						# Specifies an offshore account.
					<val n="agentno"></val>						# Agent's identity, mandatory value for DBSchenker PrivPak. Normally set by application.
				</partner>
			</receiver>
			<shipment orderno="..."> 							# Unique order number. Any contents. Mandatory. Order number is searchable in the system but not printed on shipping documents.
				<val n="from"></val> 							# Defines the sender. Refers to the sndid value for sender.
				<val n="legalfrom"></val> 						# Defines the legal sender (not printed on shipping documents).
				<val n="to"></val> 								# Defines the receiver. Refers to rcvid value for receiver.
				<val n="legalto"></val>							# Defines the legal receiver (not printed on shipping documents).
				<val n="agentto"></val>							# Defines the agent's ID for recipient in shipment. Used by DBSchenker PrivPak to store the agent's details, address etc. For Bring it's used to store address details to MyQuickBox machine.
				<val n="customsfrom"></val>						# Exporter
				<val n="customsto"></val>						# Importer
				<val n="shpid"></val>							# Shipment ID. UFPS only.
				<val n="freetext1"></val>						# Free text field with any contents. Can be used for delivery instructions, for example. It is printed on shipping documents. 4 lines available, freetext1-4. Max. 30 characters/line.
				<val n="eurpallets"></val>						# Number of EUR pallets in the shipment. Requires palletregno for sender and receiver.
				<val n="reference"></val>						# Shipment reference. Any contents. Max. 17 characters.
				<val n="referencebarcode"></val>				# Shipment reference as barcode. Max. 17 numeric characters.
				<val n="rcvreference"></val>					# Receiver's reference. Any contents. Max. 17 characters.
				<val n="sisfreetext1"></val>					# Free text field with any contents. Can be used for delivery instructions, for example. It is printed on shipping documents. 5 lines available, sisfreetext1-5. Max. 30 characters/line.
				<val n="cmrfreetext1"></val>					# Free text field with any contents. Can be used for delivery instructions, for example. Only printed on CMR waybill. 5 lines available, cmrfreetext1-5. Max. 30 characters/line.
				<val n="cmrdocuments1"></val>					# Fields for additional documents. 2 lines available, cmrdocuments1-2. Max. 30 characters/line.
				<val n="cmrspecialagreement"></val>				# Specifies any special agreement. Max. 30 characters.
				<val n="termcode"></val>						# Delivery terms. See SUP-112-Services-en.xls for valid delivery terms.
				<val n="termlocation"></val>					# Defines the location where takeover for the specified delivery term is done.
				<val n="printset"></val>						# Defines which documents to print. Pipe characters are mandatory. Valid values: |label| = Label only, |sis| = Waybill only, |*| = None of the above
				<val n="shipdate"></val>						# Defines shipment date. Printed on shipping documents. The default value is the current date. Please note that EDI is sent on this date.
				<val n="customsunit"></val>						# Customs currency unit
				<service srvid="...">							# Corresponds to carrier's service. See SUP-112-Services-en.xls for valid services.
					<val n="returnlabel"></val>					# Defines if the shipment is a return shipment or not. Valid values: yes, no
					<val n="nondelivery"></val>					# Defines action when the package is undeliverable. Only for Posten Postpaket Utrikes. RETURN = Return to sender, ABANDON = Treat as abandoned in receiver's country.
					<booking>									# Booking information for pick up with DBSchenker. UFPS only.
						<val n="bookingid"></val>				# OPAL-number. Acquired from DBSchenker.
						<val n="bookingoffice"></val>			# Booking office, numeric code. See SUP-112-Services-en.xls for valid codes.
					</booking>
					<addon adnid="...">							# Corresponds to add-on service. See SUP-112-Services-en.xls for valid add-on services.
						<val n="amount"></val>					# Amount. Used only with add-on COD (Cash On Delivery).
						<val n="custno"></val>					# Customer number for carrier. Used primarily with RPAY (receiver pays) and OPAY (other payer).
						<val n="reference"></val>				# Payment reference. Used with add-on COD.
						<val n="misc"></val>					# Defines value for misctype.
						<val n="misctype"></val>				# Used to define notification mode for add-on NOT. Valid values: PHONE = Phone, FAX = Fax.
						<val n="text1"></val>					# Defines name of collection point for Posten add-on DLVNOT.
						<val n="text2"></val>					# Defines address for collection point for Posten add-on DLVNOT.
						<val n="text3"></val>					# Defines phone number for Posten add-ons PODNOT, DLVNOT and PRENOT.
						<val n="text4"></val>					# Defines e-mail address for Posten add-ons PODNOT, DLVNOT and PRENOT.
						<val n="tempmax"></val>					# Max. temperature allowed. Used with DBSchenker ColdSped.
						<val n="tempmin"></val>					# Min. temperature allowed. Used by DBSchenker ColdSped.
					</addon>
				</service>
				<container type="parcel">						# Parcel information can be supplied in various ways. See p. 7.
					<val n="copies"></val>						# Number of parcels
					<val n="cntid1"></val>						# Parcel ID. Used only for custom parcel ID. UFPS only. Cntid is to be incremented according to number of parcels.
					<val n="marking"></val>						# Goods marking
					<val n="packagecode"></val>					# Package code. See SUP-112-Services-en.xls for valid package codes.
					<val n="weight"></val>						# Weight
					<val n="volume"></val>						# Volume
					<val n="area"></val>						# Loadmeter. Can only be specified for entire shipment.
					<val n="length"></val>						# Length
					<val n="width"></val>						# Width
					<val n="height"></val>						# Height
					<val n="itemno"></val>						# Item number
					<val n="contents"></val>					# Contents
					<val n="dnguncode"></val>					# UN-number for ADR. Supplied as a 4 digit code.
					<val n="dnghzcode"></val>					# Label number for ADR
					<val n="dngpkcode"></val>					# Packaging group/ADR-class. Supplied as I, II or III.
					<val n="dngadrclass"></val>					# ADR-class
					<val n="dngdescr"></val>					# Official transport name for item regarding ADR
					<val n="dngmpcode"></val>					# Defines if the contents contaminate the marine environment, ADR only. Valid values: 1 = Toxic and 2 = Non-toxic for the marine environment
					<val n="dngnote"></val>						# Note for ADR goods
					<val n="dngnetweight"></val>				# Net weight for ADR goods class I (usually explosive contents). Always mandatory for DBSchenker, regardless of class. Defined in kg.
				</container>
			</shipment>
		</printserver>
		*/

		// Muunnetaan merkistö Windows ANSI
		iconv("ISO-8859-1", "Windows-1252", $aineisto);

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
		if (file_put_contents($filenimi, $aineisto) === FALSE) {
			echo "<br><font class='error'>".t("tiedoston kirjoitus EPÄONNISTUI")."</font><br>";
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
		echo "<br><font class='error'>".t("UNIFAUN aineiston luotiin tarvittavia parametrejä puuttuu!")."</font><br>";
	}
}

?>