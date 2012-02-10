<?php

	// From: hg clone https://code.google.com/p/wse-php/
	require('/Users/joni/Dropbox/Sites/wse-php/soap-wsse.php');

	date_default_timezone_set('Europe/Helsinki');
	$pupe_root_polku = dirname(dirname(__FILE__));
	error_reporting(E_ALL);
	ini_set("display_errors", 1);

	// From: http://www.nordea.fi/sitemod/upload/root/fi_org/liite/WSNDEA1234.p12
	// Converted: openssl pkcs12 -in WSNDEA1234.p12 -out nordea.pem -nodes
	// Public key: openssl rsa -in nordea.pem -pubout
	// Private key: openssl rsa -in nordea.pem

	// Password: WSNDEA1234
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

		The hash algorithm used in XML Digital Signatures is SHA1.
		The cryptographic algorithm used in XML Digital Signatures is RSA.

		Key lengths are determined by the certificates used for signing and are thus bank specific.
		Nordea key length: 1024

		Canonicalization standard used for Nordea WS is http://www.w3.org/2001/10/xml-exc-c14n#
		The hash algorithm used in XML Digital Signatures is SHA1 http://www.w3.org/2000/09/xmldsig#sha1
		The cryptographic algorithm used in XML Digital Signatures is RSA http://www.w3.org/2000/09/xmldsig#rsa-sha1

		Standard used for WS communication at Nordea for SOAP envelope is:
		SignatureMethod Algorithm = http://www.w3.org/2000/09/xmldsig#rsa-sha1
		DigestMethod Algorithm = http://www.w3.org/2000/09/xmldsig#sha1
	*/

	// 1. Tarvitaan maksuaineiston nimi muuttujassa $payload_file
	$payload_file = "SEPA-demo-07.02.12.14.07.49.xml";

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
	$applicationrequest->appendChild($xml->createElement("TargetId", 	"11111111A1"));
	$applicationrequest->appendChild($xml->createElement("SoftwareId", 	"Pupesoft 1.0"));
	$applicationrequest->appendChild($xml->createElement("FileType", 	"NDCORPAYS"));
	$applicationrequest->appendChild($xml->createElement("Content", 	$payload)); // <--- 4. Laitetaan payload content-elementtiin

	// 5. Signataan Application Request

	// Canonicalizoidaan xml (exclusive true, comments false)
	$xml_data = $xml->C14N(TRUE, FALSE);

	// Haetaan private key ja sertifikaatti
	$key = openssl_pkey_get_private(file_get_contents(PRIVATE_KEY));
	$cert = file_get_contents(CERT_FILE);
	$cert = str_replace(array("-----BEGIN CERTIFICATE-----", "-----END CERTIFICATE-----", "\n"), "", $cert);

	// Signataan tiedosto
	openssl_sign($xml_data, $signature, $key, OPENSSL_ALGO_SHA1);
	$signature_value = base64_encode($signature);

	// Lasketaan raw hash/digest tiedostosta
	$digest = base64_encode(sha1($xml_data, TRUE));

	// Lisätään SOAP Signature osio ja laitetaan signature, cert ja digest tiedostoon
	$simple_xml = simplexml_load_string($xml_data);

	$signature = $simple_xml->addChild("Signature");
	$signature->addAttribute("xmlns", "http://www.w3.org/2000/09/xmldsig#");
		$signedinfo = $signature->addChild("SignedInfo");
		$canonicalizationmethod = $signedinfo->addChild("CanonicalizationMethod");
		$canonicalizationmethod->addAttribute("Algorithm", "http://www.w3.org/2001/10/xml-exc-c14n#");
		$signaturemethod = $signedinfo->addChild("SignatureMethod");
		$signaturemethod->addAttribute("Algorithm", "http://www.w3.org/2000/09/xmldsig#rsa-sha1");
		$reference = $signedinfo->addChild("Reference");
			$transforms = $reference->addChild("Transforms");
				$transform = $transforms->addChild("Transform");
				$transform->addAttribute("Algorithm", "http://www.w3.org/2001/10/xml-exc-c14n#");
			$digestmethod = $reference->addChild("DigestMethod");
			$digestmethod->addAttribute("Algorithm", "http://www.w3.org/2000/09/xmldsig#sha1");
			$reference->addChild("DigestValue", $digest);
		$signature->addChild("SignatureValue", $signature_value);
		$keyinfo = $signature->addChild("KeyInfo");
			$x509data = $keyinfo->addChild("X509Data");
				$x509data->addChild("X509Certificate", $cert);

	#file_put_contents("application_request.xml", $simple_xml->asXML());

	# 6. Koko Application Request base64_encodataan
	$application_request = base64_encode($simple_xml->asXML());

	// Tehdään validaatio Application Requestille
	$axml = new DomDocument('1.0');
	$axml->encoding = 'UTF-8';
	$axml->loadXML($simple_xml->asXML());

	// Tehdään validaatio Application Requestille
	libxml_use_internal_errors(true);
	if (!$axml->schemaValidate("{$pupe_root_polku}/sepa/ApplicationRequest_20080918.xsd")) {
		echo "Virheellinen Application Request!\n\n";

		var_dump($axml->saveXML());

		$all_errors = libxml_get_errors();
		foreach ($all_errors as $error) {
			echo "$error->message\n";
		}
		exit;
	}

	# 6. Koko Application Request base64_encodataan
	$application_request = base64_encode($axml->saveXML());

	# 7. Tehdään SOAP
	$soap = new DomDocument('1.0');
	$soap->encoding = 'UTF-8';
	$soap->preserveWhiteSpace = true;
	$soap->formatOutput = true;

	$body_timestamp = date("c");
	$body_requestid = date("U");

	$envelope = $soap->createElement("soapenv:Envelope");
	$envelope = $soap->appendChild($envelope);
	$envelope->setAttribute("xmlns:cor", "http://bxd.fi/CorporateFileService");
	$envelope->setAttribute("xmlns:mod", "http://model.bxd.fi");
	$envelope->setAttribute("xmlns:soapenv", "http://schemas.xmlsoap.org/soap/envelope/");
	$body = $envelope->appendChild($soap->createElement("soapenv:Body"));
	$body->setAttribute("xmlns:wsu", "http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd");
	$uploadfilein = $body->appendChild($soap->createElement("cor:uploadFilein"));
	$requestheader = $uploadfilein->appendChild($soap->createElement("mod:RequestHeader"));
	$requestheader->appendChild($soap->createElement("mod:SenderId", 			"111111111"));
	$requestheader->appendChild($soap->createElement("mod:RequestId",			$body_requestid));
	$requestheader->appendChild($soap->createElement("mod:Timestamp",			$body_timestamp));
	$requestheader->appendChild($soap->createElement("mod:Language",			"FI"));
	$requestheader->appendChild($soap->createElement("mod:UserAgent",			"Pupesoft 1.0"));
	$requestheader->appendChild($soap->createElement("mod:ReceiverId", 			"11111111A1"));
	$uploadfilein->appendChild($soap->createElement("mod:ApplicationRequest", 	$application_request)); // <--- 8. Laitetaan Application Request ApplicationRequest-elementtiin

	# 9. Signataan SOAP

	// Canonicalizoidaan xml (exclusive true, comments false)
	$xml_data = $soap->C14N(TRUE, FALSE);

	// Signataan tiedosto
	openssl_sign($xml_data, $signature, $key, OPENSSL_ALGO_SHA1);
	$signature_value = base64_encode($signature);

	// Lasketaan raw hash/digest tiedostosta
	$digest = base64_encode(sha1($xml_data, TRUE));

	// Tehdään GUID1, lisätään se Body Id:seen sekä headerin SignedInfo->Reference URI:in
	// Tehdään GUID2, lisätään se BinarySecurityToken Id:seen sekä SecurityTokenReference->Reference URI:in
	$guid1 = md5(uniqid(rand(), true));
	$guid2 = md5(uniqid(rand(), true));

	// Aloitetaan uusi SOAP dokumentti
	$soap = new DomDocument('1.0');
	$soap->encoding = 'UTF-8';
	$soap->preserveWhiteSpace = true;
	$soap->formatOutput = true;

	$envelope = $soap->createElement("soapenv:Envelope");
	$envelope = $soap->appendChild($envelope);
	$envelope->setAttribute("xmlns:cor", "http://bxd.fi/CorporateFileService");
	$envelope->setAttribute("xmlns:mod", "http://model.bxd.fi");
	$envelope->setAttribute("xmlns:soapenv", "http://schemas.xmlsoap.org/soap/envelope/");

	// Tehdään SOAP Header elementti
	$header = $envelope->appendChild($soap->createElement("soapenv:Header"));
		$security = $header->appendChild($soap->createElement("wsse:Security"));
		$security->setAttribute("xmlns:wsse", "http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd");
		$security->setAttribute("soapenv:mustUnderstand", "1");
		$token = $security->appendChild($soap->createElement("wsse:BinarySecurityToken", $cert));
		$token->setAttribute("xmlns:wsu", "http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd");
		$token->setAttribute("EncodingType", "http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary");
		$token->setAttribute("wsu:Id", $guid2);
		$token->setAttribute("ValueType", "http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-x509-token-profile-1.0#X509v3");
		$signature = $security->appendChild($soap->createElement("ds:Signature"));
		$signature->setAttribute("xmlns:ds", "http://www.w3.org/2000/09/xmldsig#");
			$signedinfo = $signature->appendChild($soap->createElement("ds:SignedInfo"));
				$canonicalizationmethod = $signedinfo->appendChild($soap->createElement("ds:CanonicalizationMethod"));
				$canonicalizationmethod->setAttribute("Algorithm", "http://www.w3.org/TR/2001/REC-xml-c14n-20010315");
				$signaturemethod = $signedinfo->appendChild($soap->createElement("ds:SignatureMethod"));
				$signaturemethod->setAttribute("Algorithm", "http://www.w3.org/2000/09/xmldsig#rsa-sha1");
				$reference = $signedinfo->appendChild($soap->createElement("ds:Reference"));
				$reference->setAttribute("URI", "#".$guid1);
					$transforms = $reference->appendChild($soap->createElement("ds:Transforms"));
						$transform = $transforms->appendChild($soap->createElement("ds:Transform"));
						$transform->setAttribute("Algorithm", "http://www.w3.org/2001/10/xml-exc-c14n#");
					$digestmethod = $reference->appendChild($soap->createElement("ds:DigestMethod"));
					$digestmethod->setAttribute("Algorithm", "http://www.w3.org/2000/09/xmldsig#sha1");
					$reference->appendChild($soap->createElement("ds:DigestValue", $digest));
			$signature->appendChild($soap->createElement("ds:SignatureValue", $signature_value));
			$keyinfo = $signature->appendChild($soap->createElement("ds:KeyInfo"));
				$sectoken = $keyinfo->appendChild($soap->createElement("wsse:SecurityTokenReference"));
					$reference = $sectoken->appendChild($soap->createElement("wsse:Reference"));
					$reference->setAttribute("URI", "#".$guid2);
		$timestamp = $security->appendChild($soap->createElement("wsu:Timestamp"));
			$timestamp->appendChild($soap->createElement("wsu:Created", gmdate("Y-m-d\TH:i:s", time()).'Z'));
			$timestamp->appendChild($soap->createElement("wsu:Expires", gmdate("Y-m-d\TH:i:s", time() + 3600).'Z'));
			$timestamp->setAttribute("xmlns:wsu", "http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd");
#			$timestamp->setAttribute("wsu:Id");

	// Kirjoitetaan BODY uudestaan (taidot loppu, että oisin osannut appendaa valmiin blockin, toisaalta tämä on identtinen, joten ei pitäisi tulla hash ongelmia)
	$body = $envelope->appendChild($soap->createElement("soapenv:Body"));
	$body->setAttribute("xmlns:wsu", "http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd");
	$body->setAttribute("wsu:Id", $guid1);
	$uploadfilein = $body->appendChild($soap->createElement("cor:uploadFilein"));
	$requestheader = $uploadfilein->appendChild($soap->createElement("mod:RequestHeader"));
	$requestheader->appendChild($soap->createElement("mod:SenderId", 			"111111111"));
	$requestheader->appendChild($soap->createElement("mod:RequestId",			$body_requestid));
	$requestheader->appendChild($soap->createElement("mod:Timestamp",			$body_timestamp));
	$requestheader->appendChild($soap->createElement("mod:Language",			"FI"));
	$requestheader->appendChild($soap->createElement("mod:UserAgent",			"Pupesoft 1.0"));
	$requestheader->appendChild($soap->createElement("mod:ReceiverId", 			"11111111A1"));
	$uploadfilein->appendChild($soap->createElement("mod:ApplicationRequest", 	$application_request)); // <--- 8. Laitetaan Application Request ApplicationRequest-elementtiin

	#file_put_contents("verify_soap1.xml", $soap->saveXML());
	$soap_xml = $soap->saveXML();

	// Tehdään validaatio Application Requestille
	$axml = new DomDocument('1.0');
	$axml->encoding = 'UTF-8';
	$axml->loadXML($soap_xml);

	// Tehdään validaatio Application Requestille
	libxml_use_internal_errors(true);
	if (!$axml->schemaValidate("{$pupe_root_polku}/sepa/soap-envelope-schema.xsd")) {
		echo "Virheellinen SOAP Envelope!\n\n";

		$all_errors = libxml_get_errors();
		foreach ($all_errors as $error) {
			echo "$error->message\n";
		}
		exit;
	}

/*
	$axml = new DomDocument('1.0');
	$axml->encoding = 'UTF-8';
	$axml->loadXML($axml_xml);

	// Tehdään Security header
	$soap_request = new WSSESoap($axml);
	$soap_request->addTimestamp();

	// Ladataan private key
	$soap_request_key = new XMLSecurityKey("http://www.w3.org/2000/09/xmldsig#rsa-sha1", array('type' => 'private'));
	$soap_request_key->loadKey(PRIVATE_KEY, TRUE);

	$soap_request->signSoapDoc($soap_request_key);
	$token = $soap_request->addBinaryToken(file_get_contents(CERT_FILE));
	$soap_request->attachTokentoSig($token);

	file_put_contents("verify_soap2.xml", $soap_request->saveXML());
*/

	# 10. Lähetetään SOAP request (Nordea)
	try {
		$client = new SoapClient ("file://{$pupe_root_polku}/sepa/BankCorporateFileService_20080616.wsdl");
		// Check Available functions:
		// var_dump($client->__getFunctions());
		// var_dump($client->__getTypes());

#		$request	= $soap_request->saveXML();
		$request	= $soap_xml;
#		$request	= file_get_contents("verify_soap.xml");
#		$request	= file_get_contents("SOAPrequest_GetUserInfo.xml");
		$location	= "https://filetransfer.nordea.com/services/CorporateFileService";
		$action		= "uploadFile";
		$version	= "1";

		$soap_result = $client->__doRequest($request, $location, $action, $version);
	}
	catch (SoapFault $ex) {
		var_dump($ex->faultcode, $ex->faultstring);
		exit;
	}

	$soap_response = new DomDocument('1.0');
	$soap_response->encoding = 'UTF-8';
	$soap_response->loadXML($soap_result);
	$soap_response->preserveWhiteSpace = true;
	$soap_response->formatOutput = true;
	echo $soap_response->saveXML();
