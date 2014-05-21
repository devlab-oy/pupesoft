<?php

$tiedoston_sijainti = "{$pupe_root_polku}/dataout/dnscustomer_".date("Y_m_d_Hi").".xml";

$xmlstr  = '<?xml version="1.0" encoding="iso-8859-1"?>';
$xmlstr .= '<customers>';
$xmlstr .= '</customers>';

$xml = new SimpleXMLElement($xmlstr);

$customer = $xml->addChild('customer');
$customer->addChild('case','pupesoft');
$customer->addChild('name', xmlentities($yhtiorow["nimi"]));
$customer->addChild('address', xmlentities($yhtiorow["osoite"]));
$customer->addChild('timestamp', date("Y-m-d H:i"));

$information = $xml->addChild('information');

foreach ($dnsasiakas as $asiakas) {

  $customer = $information->addChild('customer');
    $customer->addChild('firstname',xmlentities($asiakas["nimi"]));
    $customer->addChild('lastname','');
    $customer->addChild('address',xmlentities($asiakas["osoite"]));
    $customer->addChild('postnumber',xmlentities($asiakas["postino"]));
    $customer->addChild('city',xmlentities($asiakas["postitp"]));
    $customer->addChild('email',xmlentities($asiakas["email"]));
}

$xml->asXML($tiedoston_sijainti);

$ftpfile = realpath($tiedoston_sijainti);
$todnimi = basename($tiedoston_sijainti);
require ("{$pupe_root_polku}/inc/ftp-send.inc");
