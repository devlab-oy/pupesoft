<?php

$tiedoston_sijainti = "{$pupe_root_polku}/dataout/dnsprices_".date("Y_m_d_Hi").".xml";

$xmlstr  = '<?xml version="1.0" encoding="iso-8859-1"?>';
$xmlstr .= '<pricelist>';
$xmlstr .= '</pricelist>';

$xml = new SimpleXMLElement($xmlstr);

$customer = $xml->addChild('customer');
$customer->addChild('case', 'pupesoft');
$customer->addChild('name', utf8_encode(xmlentities($yhtiorow["nimi"])));
$customer->addChild('address', utf8_encode(xmlentities($yhtiorow["osoite"])));
$customer->addChild('timestamp', date("Y-m-d H:i"));

// HINNASTOJEN PERUSTIEDOT
$pricelists = $xml->addChild('pricelists');

foreach ($dnshinnasto as $hinnasto) {

  $pricelist = $pricelists->addChild('pricelist');
  $pricelist->addChild('pricelistcode', utf8_encode(xmlentities($hinnasto["tuoteno"])));
  $pricelist->addChild('name', $hinnasto["selite"]);
  $pricelist->addChild('nameswe', $hinnasto["selite"]);
  $pricelist->addChild('namewng', $hinnasto["selite"]);
  $pricelist->addChild('startdate', $hinnasto["alkupvm"]." 00:00");
  $pricelist->addChild('enddate', $hinnasto["loppupvm"]." 23:59");
}

// TARJOUSHINTOJA
$items = $xml->addChild('items');

foreach ($dnshinnasto as $hinnasto) {
  $item = $items->addChild('item');
  $item->addChild('pricelistcode', utf8_encode(xmlentities($hinnasto["tuoteno"])));
  $item->addChild('productcode', utf8_encode(xmlentities($hinnasto["tuoteno"])));
  $item->addChild('price', $hinnasto["hinta"]);
}

$xml->asXML($tiedoston_sijainti);

$ftpfile = realpath($tiedoston_sijainti);
$todnimi = basename($tiedoston_sijainti);
require "{$pupe_root_polku}/inc/ftp-send.inc";
