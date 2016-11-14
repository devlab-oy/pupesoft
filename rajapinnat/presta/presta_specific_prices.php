<?php

require_once 'rajapinnat/presta/presta_client.php';
require_once 'rajapinnat/presta/presta_products.php';

class PrestaSpecificPrices extends PrestaClient {
  private $all_prices = null;
  private $already_removed_product = array();
  private $currency_codes = null;
  private $presta_products = null;
  private $presta_static_customer_group = null;
  private $product_ids = array();
  private $shop = null;

  public function __construct($url, $api_key, $log_file) {
    $this->presta_products = new PrestaProducts($url, $api_key, $log_file);

    parent::__construct($url, $api_key, $log_file);
  }

  protected function resource_name() {
    return 'specific_prices';
  }

  /**
   *
   * @param array   $specific_price
   * @param SimpleXMLElement $existing_specific_price
   * @return \SimpleXMLElement
   */
  protected function generate_xml($specific_price, SimpleXMLElement $existing_specific_price = null) {
    if (is_null($existing_specific_price)) {
      $xml = $this->empty_xml();
    }
    else {
      $xml = $existing_specific_price;
    }

    $xml->specific_price->id_group = 0;

    if (!empty($specific_price['presta_customergroup_id'])) {
      $xml->specific_price->id_group = $specific_price['presta_customergroup_id'];
    }

    $xml->specific_price->id_customer = 0;

    if (!empty($specific_price['presta_customer_id'])) {
      $xml->specific_price->id_customer = $specific_price['presta_customer_id'];
    }

    $xml->specific_price->from = '0000-00-00 00:00:00';
    if ($specific_price['alkupvm'] != '0000-00-00') {
      $xml->specific_price->from = $specific_price['alkupvm'];
    }

    $xml->specific_price->to = '0000-00-00 00:00:00';
    if ($specific_price['loppupvm'] != '0000-00-00') {
      $xml->specific_price->to = $specific_price['loppupvm'];
    }

    $xml->specific_price->from_quantity = 1;
    if (!empty($specific_price['minkpl'])) {
      $xml->specific_price->from_quantity = $specific_price['minkpl'];
    }

    $currency_id = empty($specific_price['valkoodi']) ? 0 : $this->get_currency_id($specific_price['valkoodi']);

    $xml->specific_price->id_product = $specific_price['presta_product_id'];
    $xml->specific_price->reduction_type = 'amount';
    $xml->specific_price->reduction = 0;
    $xml->specific_price->reduction_tax = 0;
    $xml->specific_price->id_shop = 0;       // leave this empty, we'll add shop group to crete request
    $xml->specific_price->id_shop_group = 0; // leave this empty, we'll add shop group to crete request
    $xml->specific_price->id_cart = 0;
    $xml->specific_price->id_currency = $currency_id;
    $xml->specific_price->id_country = 0;

    // price or percentage
    if (isset($specific_price['alennus'])) {
      // Pupe stores percentages, presta needs them divided
      $reduction = $specific_price['alennus'] / 100;

      // Price -1 == "Leave base price" checked
      $xml->specific_price->price = -1;
      $xml->specific_price->reduction_type = "percentage";
      $xml->specific_price->reduction = $reduction;
    }
    elseif (isset($specific_price['hinta'])) {
      $xml->specific_price->price = $specific_price['hinta'];
      $xml->specific_price->reduction_type = "amount";
      $xml->specific_price->reduction = 0;
    }
    else {
      $this->logger->log('Both amount and percentage were empty! Nothing was set!');
    }

    return $xml;
  }

  public function sync_prices($prices) {
    $this->logger->log('---------Start specific price sync---------');

    try {
      $this->product_ids = $this->presta_products->all_skus();
      $shop_group_id = $this->shop_group_id();

      $total = count($prices);
      $current = 0;

      foreach ($prices as $price) {
        $current++;
        $this->logger->log("[{$current}/$total]");

        //In pupesoft tuoteno is not mandatory but in presta it is.
        if (empty($price['tuoteno'])) {
          $this->logger->log('Ohitettu special price koska tuotenumero puuttuu');
          continue;
        }

        try {
          $price['presta_product_id'] = $this->find_presta_product_id($price['tuoteno']);

          $this->delete_special_prices_for_product($price['presta_product_id']);

          if (empty($price['hinta']) and empty($price['alennus'])) {
            $this->logger->log("Ohitettu special price tuotteelle {$price['tuoteno']} koska alennus sekä hinta puuttuu");
            continue;
          }

          if (!empty($price['hinta']) and empty($price['valkoodi'])) {
            $this->logger->log("Ohitettu special price tuotteelle {$price['tuoteno']} koska hinnalla {$price['hinta']} ei ole valuuttaa!");
            continue;
          }

          if ($price['tyyppi'] !== 'hinnastohinta' and $price['tyyppi'] !== 'informatiivinen_hinta' and empty($price['presta_customer_id']) and empty($price['presta_customergroup_id'])) {
            $this->logger->log("Ohitettu {$price['tyyppi']} tuotteelle '{$price['tuoteno']}' koska sillä ei ole asiakastunnusta eikä asiakasryhmää");
            continue;
          }

          // jos meillä on static customer group, niin lisätään kaikki hinnastohinnat siihen
          if ($price['tyyppi'] == 'hinnastohinta' and !empty($this->presta_static_customer_group)) {
            $price['presta_customergroup_id'] = $this->presta_static_customer_group;

            $this->logger->log("Lisätään hinnastohintaan asiakasryhmä {$this->presta_static_customer_group}");
          }

          $this->create($price, null, $shop_group_id);

          $message = "Lisätty tuotteelle '{$price['tuoteno']}' {$price['tyyppi']}:";

          if (isset($price['alennus'])) {
            $message .= " alennus '{$price['alennus']}'";
          }
          if (isset($price['hinta'])) {
            $message .= " hinta '{$price['hinta']}'";
          }
          if (isset($price['valkoodi'])) {
            $message .= " valuutta '{$price['valkoodi']}'";
          }
          if (isset($price['presta_customer_id'])) {
            $message .= " asiakastunnus '{$price['presta_customer_id']}'";
          }
          if (isset($price['presta_customergroup_id'])) {
            $message .= " asiakasryhma '{$price['presta_customergroup_id']}'";
          }
          if (isset($price['alkupvm']) and $price['alkupvm'] != '0000-00-00') {
            $message .= " alkupvm '{$price['alkupvm']}'";
          }
          if (isset($price['loppupvm']) and $price['loppupvm'] != '0000-00-00') {
            $message .= " loppupvm '{$price['loppupvm']}'";
          }

          $this->logger->log($message);
        }
        catch (Exception $e) {
          //Do nothing here. If create / update throws exception loggin happens inside those functions
          //Exception is not thrown because we still want to continue syncing for other products
        }
      }
    }
    catch (Exception $e) {
      //Exception logging happens in create / update.

      return false;
    }

    $this->logger->log('---------End specific price sync---------');

    return true;
  }

  /**
   * Finds presta product id from $this->product_ids
   *
   * @param string  $tuoteno
   * @return int
   * @throws Exception
   */
  private function find_presta_product_id($tuoteno) {
    // lowercase all Presta SKU:s and the search term
    $all_products = array_map('strtolower', $this->product_ids);
    $search = strtolower($tuoteno);

    $presta_product_id = array_search($search, $all_products);

    if ($presta_product_id === false) {
      $msg = "Tuotetta {$tuoteno} ei löytynyt";
      $this->logger->log($msg);
      throw new Exception($msg);
    }

    return (int) $presta_product_id;
  }

  private function delete_special_prices_for_product($id) {
    $id = (int) $id;

    if ($id <= 0) {
      return false;
    }

    // make sure to remove old prices only once..
    if (array_search($id, $this->already_removed_product) !== false) {
      return true;
    }

    $id_group_shop = $this->shop_group_id();
    $id_shop = null;

    if ($this->all_prices === null) {
      $this->logger->log('Haetaan kaikki Prestan alennukset');

      $display = array('id', 'id_product');
      $filters = array();

      $this->all_prices = $this->all($display, $filters, $id_shop, $id_group_shop);
    }

    foreach ($this->all_prices as $price) {
      if ($price['id_product'] == $id) {
        try {
          $price_id = $price['id'];
          $this->delete($price_id, $id_shop, $id_group_shop);
        }
        catch (Exception $e) {
          $this->logger->log("Tuotteen {$id} hinnan {$price_id} poisto epäonnistui!");
        }
      }
    }

    $this->already_removed_product[] = $id;

    return true;
  }

  private function get_currency_id($code) {
    if (empty($code) or !isset($this->currency_codes[$code])) {
      // zero means "all currencies"
      return 0;
    }

    $value = $this->currency_codes[$code];

    if (empty($value)) {
      // zero means "all currencies"
      return 0;
    }
    else {
      return $value;
    }
  }

  public function set_currency_codes($value) {
    if (is_array($value)) {
      $this->currency_codes = $value;
    }
  }

  public function set_presta_static_customer_group($value) {
    $this->presta_static_customer_group = $value;
  }
}
