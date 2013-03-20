<?php

	// tarvitaaan: yum install xmlsec1
	date_default_timezone_set('Europe/Helsinki');
	$pupe_root_polku = dirname(dirname(__FILE__));
	error_reporting(E_ALL);
	ini_set("display_errors", 1);

	// Tiedot, jolla Signataan ApplicationRequest
	$server_sertificate_p12 = "server.p12";
	$server_sertificate_pass = "salasana";
	$bank_certificate_pem = "nordea.pem";

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
	$xmlstr  = '<?xml version="1.0" encoding="UTF-8"?>';
	$xmlstr .= '<ApplicationRequest xmlns="http://bxd.fi/xmldata/">';
	$xmlstr .= '</ApplicationRequest>';

	$application_request_xml = new SimpleXMLElement($xmlstr);	
	$application_request_xml->addChild("CustomerId",	"joni");
	$application_request_xml->addChild("Command",		"UploadFile");
	$application_request_xml->addChild("Timestamp",		date("c"));
	$application_request_xml->addChild("Environment",	"TEST");
	$application_request_xml->addChild("TargetId",		"11111111A1");
	$application_request_xml->addChild("SoftwareId",	"Pupesoft 1.0");
	$application_request_xml->addChild("FileType",		"NDCORPAYS");	
	$application_request_xml->addChild("Content",		$payload); // <--- 4. Laitetaan payload content-elementtiin

	// Signature osa
	$signature = $application_request_xml->addChild("Signature");
	$signature->addAttribute("xmlns", "http://www.w3.org/2000/09/xmldsig#");
		$signedinfo = $signature->addChild("SignedInfo");
		$canonicalizationmethod = $signedinfo->addChild("CanonicalizationMethod");
		$canonicalizationmethod->addAttribute("Algorithm", "http://www.w3.org/TR/2001/REC-xml-c14n-20010315");
		$signaturemethod = $signedinfo->addChild("SignatureMethod");
		$signaturemethod->addAttribute("Algorithm", "http://www.w3.org/2000/09/xmldsig#rsa-sha1");
		$reference = $signedinfo->addChild("Reference");
		$reference->addAttribute("URI", "");
			$transforms = $reference->addChild("Transforms");
				$transform = $transforms->addChild("Transform");
				$transform->addAttribute("Algorithm", "http://www.w3.org/2000/09/xmldsig#enveloped-signature");
			$digestmethod = $reference->addChild("DigestMethod");
			$digestmethod->addAttribute("Algorithm", "http://www.w3.org/2000/09/xmldsig#sha1");
			$reference->addChild("DigestValue");
		$signature->addChild("SignatureValue");	
		$keyinfo = $signature->addChild("KeyInfo");	
			$x509data = $keyinfo->addChild("X509Data");
				$x509data->addChild("X509SubjectName");
				$x509data->addChild("X509IssuerSerial");
				$x509data->addChild("X509Certificate");			
			$keyinfo->addChild("KeyValue");

	file_put_contents("simple.xml", $application_request_xml->asXML());

	# 5. Signataan Application Request (Pitääiskikö tämä myös encryptata? xmlsec1 --encrypt)
	$application_request_file = tempnam("/tmp", "appreq");
	$application_request_file_signed = tempnam("/tmp", "appreq");
	file_put_contents($application_request_file, $application_request_xml->asXML());
	system("xmlsec1 --sign --output $application_request_file_signed --pkcs12 $server_sertificate_p12 --pwd $server_sertificate_pass --trusted-pem $bank_certificate_pem $application_request_file");

	$xml = new DomDocument();
	$xml->Load($application_request_file_signed);

	// Tehdään validaatio Application Requestille
	libxml_use_internal_errors(true);
	if (!$xml->schemaValidate("{$pupe_root_polku}/sepa/ApplicationRequest_20080918.xsd")) {
		echo "Virheellinen Application Request!\n\n";
		
		var_dump($xml->saveXML());
		
		$all_errors = libxml_get_errors();
		foreach ($all_errors as $error) {
			echo "$error->message\n";
		}
		exit;
	}

	# 6. Koko Application Request base64_encodataan
	$application_request = base64_encode(file_get_contents($application_request_file_signed));
	// echo file_get_contents($application_request_file);
	// echo file_get_contents($application_request_file_signed);
	unlink($application_request_file);
	unlink($application_request_file_signed);

	# 7. Tehdään SOAP
	$soap_data = array(	"RequestHeader" => array(
						"SenderId" => '111111111',
						"RequestId" => date('U'),
						"Timestamp" => date('c'),
						"Language" => 'FI',
						"UserAgent" => 'Pupesoft 1.00',
						"ReceiverId" => '11111111A1'),
						"ApplicationRequest" => $application_request);  // <--- 8. Laitetaan Application Request ApplicationRequest-elementtiin

	# 9. Signataan SOAP
 	// MITEN?
	
	# 10. Lähetetään SOAP request (Nordea)
	try {
		$client = new SoapClient ("file://{$pupe_root_polku}/sepa/BankCorporateFileService_20080616.wsdl");
		$client->__setLocation = "https://filetransfer.nordea.com/services/CorporateFileService";
		//var_dump($client->__getFunctions());

		$soap_result = $client->uploadFile($soap_data);
		var_dump($soap_result);
	}
	catch (SoapFault $ex) {
		var_dump($ex->faultcode, $ex->faultstring);
	}
		
	// CREATE SERVER ROOT CERT
	// openssl genrsa -des3 -out ca.key 1024
	// openssl req -new -x509 -days 365 -key ca.key -out ca.crt
	// 
	// CREATE SERVER SERTIFICATE
	// openssl genrsa -des3 -out server.key 1024
	// openssl req -new -key server.key -out server.csr
	// openssl x509 -req -days 365 -in server.csr -CA ca.crt -CAkey ca.key -set_serial 01 -out server.crt
	// 
	// Generate server pem
	// openssl req -x509 -nodes -days 365 -newkey rsa:1024 -keyout server.pem -out server.pem
	//
	// COMBINE SERVER CERT+KEY TO P12
	// openssl pkcs12 -export -out server.p12 -inkey server.key -in server.crt
	// 
	// CONVERT Nordea P12 to PEM (http://www.nordea.fi/sitemod/upload/root/fi_org/liite/WSNDEA1234.p12)
	// openssl pkcs12 -in WSNDEA1234.p12 -out nordea.pem -nodes
	