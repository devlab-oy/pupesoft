<?php

require_once 'rajapinnat/presta/presta_client.php';
require_once 'rajapinnat/presta/presta_shops.php';

class PrestaSpecificPrices extends PrestaClient {

  const RESOURCE = 'specific_prices';

  private $shop;
  private $product_ids;

  public function __construct($url, $api_key) {
    parent::__construct($url, $api_key);

    $this->shop = null;
    $this->product_ids = array();
  }

  protected function resource_name() {
    return self::RESOURCE;
  }

  /**
   *
   * @param array   $specific_price
   * @param SimpleXMLElement $existing_specific_price
   * @return \SimpleXMLElement
   */


  protected function generate_xml($specific_price, SimpleXMLElement $existing_specific_price = null) {
    $xml = new SimpleXMLElement($this->schema->asXML());

    if (!is_null($existing_specific_price)) {
      $xml = $existing_specific_price;
    }

    $xml->specific_price->id_group = 0;

    if (!empty($specific_price['presta_customergroup_id'])) {
      $xml->specific_price->id_group = $specific_price['presta_customergroup_id'];
    }

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

    $xml->specific_price->id_product = $specific_price['presta_product_id'];
    $xml->specific_price->reduction_type = 'amount';
    $xml->specific_price->reduction = 0;
    $xml->specific_price->id_shop = $this->shop['id'];
    $xml->specific_price->id_cart = 0;
    $xml->specific_price->id_currency = 0;
    $xml->specific_price->id_country = 0;

    //Price == -1 if Leave base price is checked. Otherwise its the given price.
    //To use reduction amounts rather than fixed price enter hinta_muutos as reduction and -1 as price
    $xml->specific_price->price = $specific_price['customer_price'];

    return $xml;
  }

  public function sync_prices($prices) {
    $this->delete_all();
    $this->logger->log('---------Start specific price sync---------');

    try {
      $this->schema = $this->get_empty_schema();

      $presta_shop = new PrestaShops($this->url(), $this->api_key());
      $this->shop = $presta_shop->first_shop();

      $presta_product = new PrestaProducts($this->url(), $this->api_key());
      $this->product_ids = $presta_product->all_skus();

      foreach ($prices as $price) {
        //In pupesoft tuoteno is not mandatory but in presta it is.
        if (empty($price['tuoteno'])) {
          $this->logger->log('Ohitettu asiakashinta koska tuotenumero puuttuu');
          continue;
        }

        if (empty($price['presta_customer_id'])) {
          $this->logger->log("Ohitettu asiakashinta tuotteelle {$price['tuoteno']} koska asiakastunnus puuttuu");
          continue;
        }

        try {
          $price['presta_product_id'] = $this->find_presta_product_id($price['tuoteno']);
          $this->create($price);
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
    $presta_product_id = array_search($tuoteno, $this->product_ids);
    if ($presta_product_id === false) {
      $msg = "Tuotetta {$tuoteno} ei l�ytynyt";
      $this->logger->log($msg);
      throw new Exception($msg);
    }

    return (int) $presta_product_id;
  }
}
