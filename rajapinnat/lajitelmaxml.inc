<?php

	$tiedoston_sijainti = "{$pupe_root_polku}/dataout/dnsassortment_".date("Y_m_d_Hi").".xml";

	$xmlstr  = '<?xml version="1.0" encoding="iso-8859-1"?>';
	$xmlstr .= '<assortments>';
	$xmlstr .= '</assortments>';

	$xml = new SimpleXMLElement($xmlstr);
	
	$customer = $xml->addChild('customer');
	$customer->addChild('case','pupesoft');
	$customer->addChild('name', utf8_encode(xmlentities($yhtiorow["nimi"])));
	$customer->addChild('address', utf8_encode(xmlentities($yhtiorow["osoite"])));
	$customer->addChild('timestamp', date("Y-m-d H:i"));
	
	// HINNASTOJEN PERUSTIEDOT
	$information = $xml->addChild('information');
	
	$laskuri = 0;
	foreach ($dnslajitelma as $key => $lajitelma) {
		// ensimmäinen on "variaatio"
		$product = $information->addChild('product');
		$product->addChild('code',$dnslajitelma[$key][$laskuri]["variaatio"]);

		foreach ($lajitelma as $tiedot) {
			$assortment = $product->addChild('assortment');
				$assortment->addChild('code', utf8_encode(xmlentities($tiedot["tuoteno"])));
				$assortment->addChild('assortmentname', utf8_encode(xmlentities($tiedot["nimitys"])));
				$assortment->addChild('assortmentnameswe', utf8_encode(xmlentities($tiedot["nimi_swe"])));
				$assortment->addChild('assortmentnameeng', utf8_encode(xmlentities($tiedot["nimi_eng"])));
				$assortment->addChild('ean', utf8_encode(xmlentities($tiedot["ean"])));
				$assortment->addChild('assortmentprice',$tiedot["myyntihinta"]);
				$properties = $assortment->addChild('properties');
				$propertytype = $properties->addChild('propertytype');
				
				// tiedot->parametrit->0->parametri_pituus = 45
				foreach ($tiedot["parametrit"] as $value) {
					
					foreach ($value as $par) {
						// avain on arrayn key-nimi
						$avain = key($value);
						
						$propertytype->addChild('propertytypename', utf8_encode(xmlentities($avain)));
							$property = $propertytype->addChild('property');
							$property->addChild('propertyname', utf8_encode(xmlentities($par)));
					}
				}
		}
		$laskuri++;
	}

	$xml->asXML($tiedoston_sijainti);
	
	$ftpfile = realpath($tiedoston_sijainti);
	$todnimi = basename($tiedoston_sijainti);
	require ("{$pupe_root_polku}/inc/ftp-send.inc");
