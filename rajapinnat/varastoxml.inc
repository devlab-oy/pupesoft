<?php

  $tiedoston_sijainti = "{$pupe_root_polku}/dataout/dnstock_".date("Y_m_d_Hi").".xml";

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

  foreach ($dnstock as $tuote) {

    $product = $information->addChild('product');
      $product->addChild('code', utf8_encode(xmlentities($tuote["tuoteno"])));
      $product->addChild('ean', $tuote["ean"]);
      $product->addChild('instock',$tuote["myytavissa"]);
  }

  $xml->asXML($tiedoston_sijainti);
  
  $ftpfile = realpath($tiedoston_sijainti);
  $todnimi = basename($tiedoston_sijainti);
  require ("{$pupe_root_polku}/inc/ftp-send.inc");
