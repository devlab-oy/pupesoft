<?php

require 'inc/pupenext_functions.inc';

function woo_commerce_toimita_tilaus($params) {
  global $woo_config;

  // jos Woo Commerce API ei k‰ytˆss‰
  if (empty($woo_config)
      or empty($woo_config["access_token"])
      or empty($woo_config["consumer_key"])
      or empty($woo_config["consumer_secret"])
      or empty($woo_config["store_url"])) {
    pupesoft_log("woocommerce_orders", "WooCommerce config ei ole kunnossa.");
    return false;
  }

  // parametrein‰ pupesoft tunnukset array sek‰ seurantakoodit string
  $pupesoft_tunnukset = $params["pupesoft_tunnukset"];
  $tracking_code = $params["tracking_code"];

  // haetaan tilausten woo tilausnumerot
  $woo_tilausnumerot = woo_commerce_hae_woo_tilausnumerot($pupesoft_tunnukset);

  foreach ($woo_tilausnumerot as $order_number) {

    pupesoft_log("woocommerce_orders", "P‰ivitet‰‰n tilaus $order_number toimitetuiksi.");

    // rakennetaan woo request
    $woo_parameters = array(
      "access_token"    => $woo_config["access_token"],
      "store_url"       => $woo_config["store_url"],
      "consumer_key"    => $woo_config["consumer_key"],
      "consumer_secret" => $woo_config["consumer_secret"],
      "order"           => array(
        "order_number"  => $order_number,
        "tracking_code" => $tracking_code,
      ),
    );

    // tehd‰‰n request
    pupenext_rest($woo_parameters, "woo_complete_order");
  }
}

function woo_commerce_hae_woo_tilausnumerot($pupesoft_tunnukset) {
  global $kukarow, $yhtiorow;

  $response = array();

  // jos meill‰ ei ole yht‰‰n tilausnumeroita
  if (empty($pupesoft_tunnukset) or !is_array($pupesoft_tunnukset)) {
    return $response;
  }

  // tehd‰‰n tunnuksista stringi
  $tunnukset = implode(",", $pupesoft_tunnukset);

  // tehd‰‰n request tilaus kerrallaan
  // ainoastaan jos moduli on magento ja asiakkaan tilausnumero lˆytyy
  $query = "SELECT asiakkaan_tilausnumero
            FROM lasku
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus in ({$tunnukset})
            AND ohjelma_moduli = 'MAGENTO'
            AND asiakkaan_tilausnumero  != ''
            AND laatija = 'WooCommerce'";
  $result = pupe_query($query);

  while ($row = mysql_fetch_assoc($result)) {
    $response[] = $row['asiakkaan_tilausnumero'];
  }

  return $response;
}
