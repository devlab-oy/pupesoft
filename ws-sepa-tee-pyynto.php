<?php

function tee_applicationrequest ($Command, $TargetId, $FileType, $Content) {

	date_default_timezone_set('Europe/Helsinki');

	$Command = 'GetUserInfo';
	$TargetId = '0012345678';
	$FileType = '';
	$Content = '';

	$juuri = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
	$juuri .= '<ApplicationRequest xmlns="http://bxd.fi/xmldata/"></ApplicationRequest>';
	$pyynto = new SimpleXMLElement($juuri);

	$pyynto->addChild('CustomerId','11111111');
	$pyynto->addChild('Command',$Command);
	$pyynto->addChild('Timestamp',date('c'));
	if (isset($StartDate)) {
		$pyynto->addChild('StartDate',$StartDate);
		if (!isset($EndDate)) $pyynto->addChild('EndDate',$EndDate);
	}
	if (isset($Status)) $pyynto->addChild('Status',$status);
	if (isset($ServiceId)) $pyynto->addChild('ServiceId',$ServiceId);
	$pyynto->addChild('Environment','TEST');
	if (isset($FileReference) and is_array($FileReference)) {
		$FileReferences = $pyynto->addChild('FileReference');
		foreach ($FileReference as $tiedosto) {
			$FileReferences->addChild('FileReference', $tiedosto);
		}
	}
	if (isset($UserFilename)) $pyynto->addChild('UserFilename',$UserFilename);
	$pyynto->addChild('TargetId',$TargetId);
	$pyynto->addChild('ExecutionSerial',date('c'));
	$pyynto->addChild('Encryption','false');
	// not in use $pyynto->addChild('EncryptionMethod','');
	$pyynto->addChild('Compression', 'false');
	//$pyynto->addChild('CompressionMethod','GZIP');
	//$pyynto->addChild('AmountTotal','0');
	//$pyynto->addChild('TransactionCount','0');
	$pyynto->addChild('SoftwareId','Pupesoft 0.01');
	//$pyynto->addChild('CustomerExtension','0');
	if ($Command != 'GetUserInfo') $pyynto->addChild('FileType',$FileType);
	$pyynto->addChild('Content', base64_encode($Content));

//Tähän hässäkkä, joka tekee allekirjoituksen $contentin perusteella luultavasti jossain wse-php/soap-wsse.php 
//wdsl tekee base64_encoden puolestamme??

// Tehdään vielä tässä vaiheessa XML validointi, vaikka ainesto onkin jo tehty. :(
	libxml_use_internal_errors(true);

	$xml_virheet = "";
	$xml_domdoc = new DomDocument;
	$xml_schema = "/home/jarmo/pupesoft/datain/ApplicationRequest.xsd";

	$xml_domdoc->LoadXml($pyynto->saveXML());

	if (!$xml_domdoc->schemaValidate($xml_schema)) {

		echo "<font class='error'>Applicationrequest on virheellinen!</font><br><br>";

		$all_errors = libxml_get_errors();

		foreach ($all_errors as $error) {
			echo "<font class='info'>$error->message</font><br>";
			$xml_virheet .= "$error->message\n";
		}

		echo "<br>";
	}
	return $pyynto->saveXML();
}
?>
