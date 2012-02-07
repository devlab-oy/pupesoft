<?php

	// From: hg clone https://code.google.com/p/wse-php/
	require('/Users/joni/Dropbox/Sites/wse-php/soap-wsse.php');

	date_default_timezone_set('Europe/Helsinki');
	$pupe_root_polku = dirname(dirname(__FILE__));
	error_reporting(E_ALL);
	ini_set("display_errors", 1);

	// From: http://www.nordea.fi/sitemod/upload/root/fi_org/liite/WSNDEA1234.p12
	// Converted: openssl pkcs12 -in WSNDEA1234.p12 -out nordea.pem -nodes
	// Password: WSNDEA1234
	define('CERT', 'nordea.pem');
	define('PRIVATE_KEY', 'nordea.key');
	define('CERT_FILE', 'nordea.crt');

	// From: http://www.fkl.fi/teemasivut/sepa/tekninen_dokumentaatio/Dokumentit/WebServices_Messages_20081022_105.pdf
	/*
		1. Create (ascii) payment file in corporate legacy. This is called the "Payload".
		2. Perform Base64 -coding to the Payload.
		3. Create a XML file called "Application Request", having elements such as Content and Signature ("command=UploadFile").
		4. Put the Base64-coded Payload into Content element of the "Application Request".
		5. Digitally sign (Enveloped -type) the whole "Application Request" with the Private Key of the Signing Certificate.
		6. Perform Base64-coding to the signed Application Request.
		7. Create a SOAP message for "Upload File" Use Case based on the WSDL.
		8. Insert Signed and Base64-coded Application Request into SOAP message Body part.
		9. Digitally sign (detached type XML Digital Signature) the whole SOAP message with the Private Key of Sender Certificate and put the signature into SOAP-header This step is usually performed by the SOAP software based on a security configuration.
		10. Send the SOAP request and wait for a response.
	*/

	// 1. Tarvitaan maksuaineiston nimi muuttujassa $payload_file
	$payload_file = "/Users/joni/Desktop/ssl/SEPA-demo-07.02.12.14.07.49.xml";

	// 2. Tehdään base64_encode
	$payload = base64_encode(file_get_contents($payload_file));

	// 3. Tehdään Application Request ja lisätään infot
	$xml = new DomDocument('1.0');
	$xml->encoding = 'UTF-8';
	$xml->preserveWhiteSpace = true;
	$xml->formatOutput = true;
	$applicationrequest = $xml->createElement("ApplicationRequest");
	$applicationrequest = $xml->appendChild($applicationrequest);
	$applicationrequest->setAttribute("xmlns", "http://bxd.fi/xmldata/");
	$applicationrequest->appendChild($xml->createElement("CustomerId", 	"11111111"));
	$applicationrequest->appendChild($xml->createElement("Command",		"UploadFile"));
	$applicationrequest->appendChild($xml->createElement("Timestamp", 	date("c")));
	$applicationrequest->appendChild($xml->createElement("Environment", "TEST"));
	$applicationrequest->appendChild($xml->createElement("SoftwareId", 	"Pupesoft 1.0"));
	$applicationrequest->appendChild($xml->createElement("Content", 	$payload)); // <--- 4. Laitetaan payload content-elementtiin

	// 5. Signataan Application Request
	$applicationrequest_signature = new XMLSecurityDSig();
	$applicationrequest_signature->setCanonicalMethod(XMLSecurityDSig::C14N_COMMENTS);
	$applicationrequest_signature->addReference($xml, XMLSecurityDSig::SHA1, array('http://www.w3.org/2000/09/xmldsig#enveloped-signature'));

	// Haetaan private key
	$applicationrequest_key = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, array('type' => 'private'));
	$applicationrequest_key->loadKey(PRIVATE_KEY, TRUE);

	// Ja laitetaan signeeraus XML dokumenttiin
	$applicationrequest_signature->sign($applicationrequest_key);
	$applicationrequest_signature->add509Cert(CERT_FILE, TRUE, TRUE);
	$applicationrequest_signature->appendSignature($xml->documentElement);

	# 6. Koko Application Request base64_encodataan
	$application_request = base64_encode($xml->saveXML());

	# 7. Tehdään SOAP
	$soap = new DomDocument('1.0');
	$soap->encoding = 'UTF-8';
	$soap->preserveWhiteSpace = true;
	$soap->formatOutput = true;

	$envelope = $soap->createElement("soapenv:Envelope");
	$envelope = $soap->appendChild($envelope);
	$envelope->setAttribute("xmlns:cor", "http://bxd.fi/CorporateFileService");
	$envelope->setAttribute("xmlns:mod", "http://model.bxd.fi");
	$envelope->setAttribute("xmlns:soapenv", "http://schemas.xmlsoap.org/soap/envelope/");
#	$envelope->setAttribute("xmlns:sign", "http://danskebank.dk/AGENA/SEPA/SigningService");
#	$envelope->setAttribute("xmlns:dpstate", "http://danskebank.dk/AGENA/SEPA/dpstate");
#	$envelope->appendChild($soap->createElement("soapenv:Header"));
	$body = $envelope->appendChild($soap->createElement("soapenv:Body"));
	$uploadfilein = $body->appendChild($soap->createElement("mod:uploadFilein"));
	$requestheader = $uploadfilein->appendChild($soap->createElement("mod:RequestHeader"));
	$requestheader->appendChild($soap->createElement("mod:SenderId", 			"111111111"));
	$requestheader->appendChild($soap->createElement("mod:RequestId",			date("U")));
	$requestheader->appendChild($soap->createElement("mod:Timestamp",			date("C")));
	$requestheader->appendChild($soap->createElement("mod:Language",			"FI"));
	$requestheader->appendChild($soap->createElement("mod:UserAgent",			"Pupesoft 1.0"));
	$requestheader->appendChild($soap->createElement("mod:ReceiverId", 			"11111111A1"));
	$uploadfilein->appendChild($soap->createElement("mod:ApplicationRequest", 	$application_request)); // <--- 8. Laitetaan Application Request ApplicationRequest-elementtiin

	# 9. Signataan SOAP
	$soap_request = new WSSESoap($soap);
	$soap_request->addTimestamp();

	$soap_request_key = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, array('type' => 'private'));
	$soap_request_key->loadKey(PRIVATE_KEY, TRUE);

	$soap_request->signSoapDoc($soap_request_key);
	$token = $soap_request->addBinaryToken(file_get_contents(CERT_FILE));
	$soap_request->attachTokentoSig($token);

	$soap_request_xml = $soap_request->saveXML();

	$soap_xml = new DomDocument('1.0');
	$soap_xml->Load($soap_request_xml);

	# 10. Lähetetään SOAP request (Nordea)
	$client = new SoapClient ("file://{$pupe_root_polku}/sepa/BankCorporateFileService_20080616.wsdl");
	$client->__setLocation = "https://filetransfer.nordea.com/services/CorporateFileService";
	var_dump($client->__getFunctions());

//	$client->uploadFile($soap_xml->saveXML());
