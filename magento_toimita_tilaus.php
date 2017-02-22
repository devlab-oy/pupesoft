<?php

// Otetaan sisään:
// $magento_api_met = Toimitustapa
// $magento_api_rak = Rahtikirjanro
// $magento_api_ord = Asiakkaan_tilausnumero
// $magento_api_noutokuittaus = Noutokuittaus, ilmoitetaan asiakkaalle tilaus noudettavissa
// $magento_api_toimituskuittaus_viestit (array) = Viesti joka liitetään noutotilauksiin (optional)
// $magento_api_laskutunnus = Jälkitoimituksien varalta katsotaan että tehdään kuittaus vain kerran

if (!function_exists("log_message")) {
  function log_message($message) {
    pupesoft_log('magento_orders', $message);
  }
}

$magento_api_ord   = (int) $magento_api_ord;
$_magento_kaytossa = (!empty($magento_api_tt_url) and !empty($magento_api_tt_usr) and !empty($magento_api_tt_pas));

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

  if (mysql_num_rows($checkre) == 0) {
    $_kuittaus_tekematta = true;
  }
  else {
    $_kuittaus_tekematta = false;

    $_tilaukset = "";
    while ($checkrow = mysql_fetch_assoc($checkre)) {
      $_tilaukset .= $checkrow['tunnus'] . ", ";
    }

    $_tilaukset = substr($_tilaukset, -2);
    $message = "Magento-tilaus on jo kuitattu Magentoon ($_tilaukset). Ei tehdä sitä enää uudelleen tilaukselle $magento_api_laskutunnus ($magento_api_ord)";
    log_message($message);
  }
}
else {
  $_kuittaus_tekematta = true;
}

if ($_kuittaus_tekematta and $_magento_kaytossa and $magento_api_ord > 0) {

  if (empty($magento_api_toimituskuittaus_viestit)) {
    $magento_api_toimituskuittaus_viestit = array();
  }

  $default_kuittaukset = array(
    "nouto"     => "Tilauksesi on noudettavissa.",
    "toimitus"  => "Your order is shipped!"
  );

  $kuittaukset = array_merge($default_kuittaukset, $magento_api_toimituskuittaus_viestit);

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
    $magento_laskutunnus = array();
    $magento_laskutunnus[] = $magento_api_laskutunnus;

    $linkit = tilauksen_seurantalinkit($magento_laskutunnus);

    foreach ($linkit as $seurantalinkki) {
      $rakirno = $seurantalinkki['id'];
      $link    = $seurantalinkki['link'];

      if (empty($link)) {
        $magLinkurl .= "$magento_api_met / $rakirno<br>";
      }
      else {
        $magLinkurl .= "<a target=newikkuna href='{$link}'>{$rakirno}</a><br>";
      }
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
}
