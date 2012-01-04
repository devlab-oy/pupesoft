<?php

// Tämä vaatii wse-php:n. Se löytyy http://code.google.com/p/wse-php/
// cd <pupenasennusdirikka>
// hg clone https://code.google.com/p/wse-php/

require('wse-php/soap-wsse.php');
require('ws-sepa-tee-pyynto.php');

define('PRIVATE_KEY', '/home/jarmo/pupesoft/datain/Nordea_Demo_Certificate_key.pem');
define('CERT_FILE', '/home/jarmo/pupesoft/datain/Nordea_Demo_Certificate.crt');

class mySoap extends SoapClient {

	function __doRequest($request, $location, $saction, $version) {
	$doc = new DOMDocument('1.0');
	$doc->loadXML($request);

	$objWSSE = new WSSESoap($doc);

	/* add Timestamp with no expiration timestamp */
	$objWSSE->addTimestamp();

	/* create new XMLSec Key using RSA SHA-1 and type is private key */
	$objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, array('type'=>'private'));

	/* load the private key from file - last arg is bool if key in file (TRUE) or is string (FALSE) */
	$objKey->loadKey(PRIVATE_KEY, TRUE);

	/* Sign the message - also signs appropraite WS-Security items */
	$objWSSE->signSoapDoc($objKey);

	/* Add certificate (BinarySecurityToken) to the message and attach pointer to Signature */
	$token = $objWSSE->addBinaryToken(file_get_contents(CERT_FILE));
	$objWSSE->attachTokentoSig($token);
	//var_dump($objWSSE->saveXML());
	return parent::__doRequest($objWSSE->saveXML(), $location, $saction, $version);
	}
}


////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

date_default_timezone_set('Europe/Helsinki');

/*	echo("\nDumping client object:\n");
	var_dump($client);

	echo("\nDumping client object functions:\n");
	var_dump($client->__getFunctions());

	$vastaus = array("ResponseHeader" => array(
				"SenderId" => '11111111A1',
				"RequestId" => date('U'),
				"Timestamp" => date('c'),
				"ResponseCode" => '',
				"ResponseText" => '',
				"ReceiverId" => '111111111'),
			"ApplicationResponse" => '');
*/

	$lahetys = array("RequestHeader" => array(
				"SenderId" => '111111111',
				"RequestId" => date('U'),
				"Timestamp" => date('c'),
				"Language" => 'FI',
				"UserAgent" => 'Pupesoft 0.01',
				"ReceiverId" => '11111111A1'),
			"ApplicationRequest" => '');

$pyyntoxml = tee_applicationrequest('', '', '', '');

$doc = new DOMDocument();
$doc->loadXML($pyyntoxml);

$objDSig = new XMLSecurityDSig();
$objDSig->setCanonicalMethod(XMLSecurityDSig::C14N_COMMENTS);
$objDSig->addReference($doc, XMLSecurityDSig::SHA1, array('http://www.w3.org/2000/09/xmldsig#enveloped-signature'));

$objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, array('type'=>'private'));
/* load private key */
$objKey->loadKey(PRIVATE_KEY, TRUE);
/* if key has Passphrase, set it using $objKey->passphrase = <passphrase> " */
$objDSig->sign($objKey);

/* Add associated public key */
$objDSig->add509Cert(CERT_FILE, TRUE, TRUE);
$objDSig->appendSignature($doc->documentElement);

$lahetys['ApplicationRequest'] = $doc->saveXML();

ini_set('default_socket_timeout', 60);

try {
	$client = new mySoap ("file:///home/jarmo/pupesoft/datain/BankCorporateFileService_20080616.wsdl", array('trace' => 1, "connection_timeout" => 60));
	$client->location = 'https://filetransfer.nordea.com/services/CorporateFileService/';
	$client->getUserInfo($lahetys);
} catch (SoapFault $e) {
	var_dump($e);
}
?>
