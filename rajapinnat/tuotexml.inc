<?php

	$tiedoston_sijainti = "{$pupe_root_polku}/dataout/dnsproduct_".date("Y_m_d_Hi").".xml";

	$xmlstr  = '<?xml version="1.0" encoding="iso-8859-1"?>';
	$xmlstr .= '<products>';
	$xmlstr .= '</products>';

	$xml = new SimpleXMLElement($xmlstr);

	$customer = $xml->addChild('customer');
	$customer->addChild('case','pupesoft');
	$customer->addChild('name', utf8_encode(xmlentities($yhtiorow["nimi"])));
	$customer->addChild('address', utf8_encode(xmlentities($yhtiorow["osoite"])));
	$customer->addChild('timestamp', date("Y-m-d H:i"));

	$information = $xml->addChild('information');

	foreach ($dnstuote as $tuote) {

		$product = $information->addChild('product');
			$product->addAttribute('id','');
			$product->addChild('code', utf8_encode(xmlentities($tuote["tuoteno"])));
			$product->addChild('ean', $tuote["ean"]);
			$product->addChild('vat', $tuote["alv"]);
			$product->addChild('name', utf8_encode(xmlentities($tuote["nimi"])));
			$product->addChild('title', utf8_encode(xmlentities($tuote["nimi"])));
			$product->addChild('titleswe', utf8_encode(xmlentities($tuote["nimi_swe"])));
			$product->addChild('titleeng', utf8_encode(xmlentities($tuote["nimi_eng"])));
			$product->addChild('productsubgroupcode', $tuote["try"]);

			$units = $product->addChild('units');
				$unit = $units->addChild('unit');
				$unit->addAttribute('id','');
					$salesunit = $unit->addChild('salesunit');
					$salesunit->addAttribute('id','');
						$salesunitname = $salesunit->addChild('salesunitname',utf8_encode(xmlentities($tuote["yksikko"])));
				$unit->addChild('unitprice',$tuote["myyntihinta"]);
				$unit->unitprice->addAttribute('id','');
	}

	$xml->asXML($tiedoston_sijainti);

	$ftpfile = realpath($tiedoston_sijainti);
	$todnimi = basename($tiedoston_sijainti);

	require ("{$pupe_root_polku}/inc/ftp-send.inc");

	if ($palautus == 0) {
		unlink($tiedoston_sijainti);
	}
	else {
		rename($tiedoston_sijainti,"{$pupe_root_polku}/dataout/ERROR_".$todnimi);
	}