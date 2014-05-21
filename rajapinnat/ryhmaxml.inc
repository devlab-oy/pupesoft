<?php

$tiedoston_sijainti = "{$pupe_root_polku}/dataout/dnsgroups_".date("Y_m_d_Hi").".xml";

$xmlstr  = '<?xml version="1.0" encoding="iso-8859-1"?>';
$xmlstr .= '<groups>';
$xmlstr .= '</groups>';

$xml = new SimpleXMLElement($xmlstr);

$customer = $xml->addChild('customer');
$customer->addChild('case','pupesoft');
$customer->addChild('name', xmlentities($yhtiorow["nimi"]));
$customer->addChild('address', xmlentities($yhtiorow["osoite"]));
$customer->addChild('timestamp', date("Y-m-d H:i"));

$information = $xml->addChild('information');

foreach ($dnsryhma as $key => $osasto) {

  // Osasto on joka rivillä sama, otetaan ekalta riviltä tiedot
  $group = $information->addChild('group');
  $group->addAttribute('extSystemID',$key);
  // setataan muuttuja joka seuraavassa luupissa Osaston vaihtuessa tehdään yhden kerran groupname-xml kirjoitus
  $vain_ekalla = 1;

  // Loopataan osaston tuoteryhmät
  foreach ($osasto as $try) {

    if ($vain_ekalla == 1) {
      $group->addChild('groupname',xmlentities($try["osasto_fi"]));
      $group->addChild('groupnameswe',xmlentities($try["osasto_se"]));
      $group->addChild('groupnameeng',xmlentities($try["osasto_en"]));
    }

    $vain_ekalla = 2;

    $subgroup = $group->addChild('subgroup');
    $subgroup->addAttribute('extSystemID',$try["try"]);
      $subgroup->addChild('subgroupcode',$try["try"]);
      $subgroup->addChild('subgroupname',    xmlentities($try["try_fi"]));
      $subgroup->addChild('subgroupnameswe',  xmlentities($try["try_se"]));
      $subgroup->addChild('subgroupnameeng',  xmlentities($try["try_en"]));
  }
}

$xml->asXML($tiedoston_sijainti);

$ftpfile = realpath($tiedoston_sijainti);
$todnimi = basename($tiedoston_sijainti);
require ("{$pupe_root_polku}/inc/ftp-send.inc");
