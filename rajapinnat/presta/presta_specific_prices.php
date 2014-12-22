<?php

require_once 'rajapinnat/presta/presta_client.php';
require_once 'rajapinnat/presta/presta_shops.php';

class PrestaSpecificPrices extends PrestaClient {

  const RESOURCE = 'specific_prices';

  private $shop;

  public function __construct($url, $api_key) {
    parent::__construct($url, $api_key);

    $this->shop = null;
  }

  protected function resource_name() {
    return self::RESOURCE;
  }

  /**
   *
   * @param array $specific_price
   * @param SimpleXMLElement $existing_specific_price
   * @return \SimpleXMLElement
   */
  protected function generate_xml($specific_price, SimpleXMLElement $existing_specific_price = null) {
    $xml = new SimpleXMLElement($this->schema->asXML());

    if (!is_null($existing_specific_price)) {
      $xml = $existing_specific_price;
    }

    if (!empty($specific_price['presta_customergroup_id'])) {
      $xml->specific_price->id_group = $specific_price['presta_customergroup_id'];
    }
    if (!empty($specific_price['presta_customer_id'])) {
      $xml->specific_price->id_customer = $specific_price['presta_customer_id'];
    }
    if ($specific_price['alkupvm'] != '0000-00-00') {
      $xml->specific_price->from = $specific_price['alkupvm'];
    }
    if ($specific_price['loppupvm'] != '0000-00-00') {
      $xml->specific_price->to = $specific_price['loppupvm'];
    }

    $xml->specific_price->from_quantity = 1;
    if (!empty($specific_price['minkpl'])) {
      $xml->specific_price->from_quantity = $specific_price['minkpl'];
    }

    $xml->specific_price->id_product = $specific_price['tuoteno'];
    $xml->specific_price->reduction_type = 'amount';
    $xml->specific_price->reduction = $specific_price['hinta_muutos'];
    $xml->specific_price->id_shop = $this->shop['id'];

    return $xml;
  }

  public function sync_prices($prices) {
    $this->logger->log('---------Start specific price sync---------');

    try {
      $this->schema = $this->get_empty_schema();

      $presta_shop = new PrestaShops($this->get_url(), $this->get_api_key());
      $this->shop = $presta_shop->first_shop();

      foreach ($prices as $price) {
        //In pupesoft tuoteno is not mandatory but in presta it is.
        if (empty($price['tuoteno'])) {
          continue;
        }
        try {
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
}
