<?php

$tiedoston_sijainti = "{$pupe_root_polku}/dataout/dnsproduct_".date("Y_m_d_Hi").".xml";

$xmlstr  = '<?xml version="1.0" encoding="utf-8"?>';
$xmlstr .= '<products>';
$xmlstr .= '</products>';

$xml = new SimpleXMLElement($xmlstr);

$customer = $xml->addChild('customer');
$customer->addChild('case','pupesoft');
$customer->addChild('name', xmlentities($yhtiorow["nimi"]));
$customer->addChild('address', xmlentities($yhtiorow["osoite"]));
$customer->addChild('timestamp', date("Y-m-d H:i"));

$information = $xml->addChild('information');

foreach ($dnstuote as $tuote) {

  $product = $information->addChild('product');
    $product->addAttribute('extSystemID','');
    $product->addChild('code', xmlentities($tuote["tuoteno"]));
    $product->addChild('ean', $tuote["ean"]);
    $product->addChild('vat', $tuote["alv"]);
    $product->addChild('name', xmlentities($tuote["nimi"]));
    $product->addChild('title', xmlentities($tuote["nimi"]));
    $product->addChild('titleswe', xmlentities($tuote["nimi_swe"]));
    $product->addChild('titleeng', xmlentities($tuote["nimi_eng"]));
    $product->addChild('productsubgroupcode', $tuote["try"]);

    $units = $product->addChild('units');
      $unit = $units->addChild('unit');
      $unit->addAttribute('extSystemID','');
        $salesunit = $unit->addChild('salesunit');
        $salesunit->addAttribute('extSystemID','');
          $salesunitname = $salesunit->addChild('salesunitname',xmlentities($tuote["yksikko"]));
      $unit->addChild('unitprice',$tuote["myyntihinta"]);
      $unit->addChild('kuluttajahinta',$tuote["myymalahinta"]);
      $unit->unitprice->addAttribute('extSystemID','');
}

$xml->asXML($tiedoston_sijainti);

$ftpfile = realpath($tiedoston_sijainti);
$todnimi = basename($tiedoston_sijainti);

require ("{$pupe_root_polku}/inc/ftp-send.inc");
