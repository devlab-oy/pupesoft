<?php
echo "MORJENS";
# Otetaan sisään:
# $anvia_api_met = Toimitustapa
# $anvia_api_rak = Rahtikirjanro
# $anvia_api_ord = Asiakkaan_tilausnumero
# $anvia_api_til = Pupen tilausnumero

$tiedoston_sijainti = "{$pupe_root_polku}/dataout/anvia_toimituskuittaus_".date("Y_m_d_Hi").".xml";

$xmlstr  = '<?xml version="1.0" encoding="iso-8859-1"?>';
$xmlstr .= '<shipment_confirmation>';
$xmlstr .= '</shipment_confirmation>';

$xml = new SimpleXMLElement($xmlstr);

$information = $xml->addChild('information');

$information->addChild('timestamp', date("Y-m-d H:i"));
$information->addChild('shipping_method', utf8_encode($anvia_api_met));
$information->addChild('shipping_number', utf8_encode($anvia_api_rak));
$information->addChild('anvia_order_number', utf8_encode($anvia_api_ord));
$information->addChild('pupesoft_order_number', utf8_encode($anvia_api_til));

$xml->asXML($tiedoston_sijainti);

$ftpfile = realpath($tiedoston_sijainti);
$todnimi = basename($tiedoston_sijainti);
require "{$pupe_root_polku}/inc/ftp-send.inc";
