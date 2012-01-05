<?php

	$xmlstr  = '<?xml version="1.0" encoding="iso-8859-1"?>';
	$xmlstr .= '<customers>';
	$xmlstr .= '</customers>';

	$xml = new SimpleXMLElement($xmlstr);
	
	$customer = $xml->addChild('customer');
	$customer->addChild('case','pupesoft');
	$customer->addChild('name', utf8_encode(xmlentities($yhtiorow["nimi"])));
	$customer->addChild('address', utf8_encode(xmlentities($yhtiorow["osoite"])));
	$customer->addChild('timestamp', date("Y-m-d H:i"));
	
	$information = $xml->addChild('information');

	foreach ($dnsasiakas as $asiakas) {

		$customer = $information->addChild('customer');
			$customer->addChild('firstname',utf8_encode(xmlentities($asiakas["nimi"])));
			$customer->addChild('lastname','');
			$customer->addChild('address',utf8_encode(xmlentities($asiakas["osoite"])));
			$customer->addChild('postnumber',utf8_encode(xmlentities($asiakas["postino"])));
			$customer->addChild('city',utf8_encode(xmlentities($asiakas["postitp"])));
			$customer->addChild('email',utf8_encode(xmlentities($asiakas["email"])));
	}

	$xml->asXML("../dataout/dnscustomer.xml");
