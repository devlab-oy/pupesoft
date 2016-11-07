<?php

// Otetaan sisään:
// $magento_api_met = Toimitustapa
// $magento_api_rak = Rahtikirjanro
// $magento_api_ord = Asiakkaan_tilausnumero
// $magento_api_noutokuittaus = Noutokuittaus, ilmoitetaan asiakkaalle tilaus noudettavissa
// $magento_api_toimituskuittaus_viestit (array) = Viesti joka liitetään noutotilauksiin (optional)
// $magento_api_laskutunnus = Jälkitoimituksien varalta katsotaan että tehdään kuittaus vain kerran

if (!empty($yhtiorow['editilaus_suoratoimitus'])) {

  // Tarkistetaan onko kuittaus jo tehty aikaisemmista tämän Magento-tilauksen Pupe-tilauksista
  // Jos tilauksia löytyy toimitettuina/laskutettuina, niin ei tehdä uutta kuittausta
  // Noutotilaukset on kerätty tilassa, huomioidaan nekin
  $query = "(SELECT tunnus
            FROM lasku
            WHERE yhtio                 = '$kukarow[yhtio]'
            AND tila                    = 'L'
            AND alatila                 in ('D','X')
            AND asiakkaan_tilausnumero  = '$magento_api_ord'
            AND tunnus                  != '$magento_api_laskutunnus'
            AND ohjelma_moduli          = 'MAGENTO')
            UNION
            (SELECT lasku.tunnus as tunnus
            FROM lasku
            JOIN toimitustapa on (toimitustapa.yhtio = lasku.yhtio
              AND toimitustapa.selite = lasku.toimitustapa
              AND toimitustapa.nouto != '')
            WHERE lasku.yhtio                 = '$kukarow[yhtio]'
            AND lasku.tila                    = 'L'
            AND lasku.alatila                 = 'C'
            AND lasku.asiakkaan_tilausnumero  = '$magento_api_ord'
            AND lasku.tunnus                  != '$magento_api_laskutunnus'
            AND lasku.ohjelma_moduli          = 'MAGENTO')";
  $checkre = pupe_query($query);
  
  echo "<br>$query<br>";
  exit;

  if (mysql_num_rows($checkre) == 0) {
    $_kuittaus_jo_tehty = false;
  }
  else {
    $_kuittaus_jo_tehty = true;
  }
}
else {
  $_kuittaus_jo_tehty = false;
}

if (!function_exists("log_message")) {
  function log_message($message) {
    pupesoft_log('magento_orders', $message);
  }
}
exit;
if (empty($magento_api_toimituskuittaus_viestit)) {
  $magento_api_toimituskuittaus_viestit = array();
}

$default_kuittaukset = array(
  "nouto"     => "Tilauksesi on noudettavissa.",
  "toimitus"  => "Your order is shipped!"
);

$kuittaukset = array_merge($default_kuittaukset, $magento_api_toimituskuittaus_viestit);

$magento_api_ord   = (int) $magento_api_ord;
$_magento_kaytossa = (!empty($magento_api_tt_url) and !empty($magento_api_tt_usr) and !empty($magento_api_tt_pas));

if (!$_magento_kaytossa or $magento_api_ord <= 0 or $_kuittaus_jo_tehty) {
  exit;
}

$proxy = new SoapClient($magento_api_tt_url);
$sessionId = $proxy->login($magento_api_tt_usr, $magento_api_tt_pas);

$magento_api_met = utf8_encode($magento_api_met);
$canShip    = true;
$canInvoice = true;
$magLinkurl = "";

$message = "Toimitetaan tilaus {$magento_api_ord} Magentoon";
log_message($message);

// Create new shipment
try {

  $magLinkurl = "Tracking number: ";

  if (stripos($magento_api_rak, "JJFI") !== false) {
    preg_match_all("/JJFI ?[0-9]{6} ?[0-9]{11}/", $magento_api_rak, $match);

    foreach ($match[0] as $nro) {
      $nro = str_replace(" ", "", $nro);
      $magLinkurl .= "<a target=newikkuna href='http://www.posti.fi/henkiloasiakkaat/seuranta/#/lahetys/{$nro}'>{$nro}</a><br>";
    }

    $magLinkurl = substr($magLinkurl, 0, -4); // vika br pois
  }
  elseif (stripos($magento_api_met, "mypack") !== FALSE) {
    $magLinkurl .= "<a target=newikkuna href='http://www.postnord.fi/fi/yritysasiakkaat/asiakaspalvelu/sahkoinen-asiointi/Sivut/Lahetysten-seuranta.aspx?view=item&itemid={$magento_api_rak}'>{$magento_api_rak}</a><br>";
  }
  else {
    $magLinkurl .= "$magento_api_met / $magento_api_rak<br>";
  }

  // Shipment comment joka lisätään Magentosta asiakkaalle lähtevään sähköpostiin
  if (isset($magento_api_noutokuittaus) and $magento_api_noutokuittaus == "JOO") {
    $comment = $kuittaukset['nouto'];
  }
  else {
    $comment = $kuittaukset['toimitus']."<br><br>$magLinkurl";
  }

  $newShipmentId = $proxy->call($sessionId, 'sales_order_shipment.create', array($magento_api_ord, array(), $comment, true, true));
}
catch (Exception $e) {
  $canShip = false;

  $message = "Lähetyksen luonti epäonnistui";
  $message .= " (" . $e->faultstring . ") faultcode: " . $e->faultcode;
  log_message($message);
}

if ($canShip) {
  // Add tracking
  try {
    $newTrackId = $proxy->call($sessionId, 'sales_order_shipment.addTrack', array($newShipmentId, "custom", $magento_api_met, $magento_api_rak));
  }
  catch(Exception $e) {
    $message = "Lähetyksenseurannan luonti epäonnistui";
    $message .= " (" . $e->faultstring . ") faultcode: " . $e->faultcode;
    log_message($message);
  }
}

// Create new invoice
try {
  $newInvoiceId = $proxy->call($sessionId, 'sales_order_invoice.create', array($magento_api_ord, array(), 'Invoice Created', false, false));
}
catch(Exception $e) {
  $canInvoice = false;

  $message = "Laskun luonti epäonnistui";
  $message .= " (" . $e->faultstring . ") faultcode: " . $e->faultcode;
  log_message($message);
}

if ($canInvoice) {
  try {
    $proxy->call($sessionId, 'sales_order_invoice.capture', $newInvoiceId);
  }
  catch (Exception $e) {
    $message = "Laskun capture epäonnistui";
    $message .= " (" . $e->faultstring . ") faultcode: " . $e->faultcode;
    log_message($message);
  }
}
