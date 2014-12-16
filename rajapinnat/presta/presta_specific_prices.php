<?php

require_once 'rajapinnat/presta/presta_client.php';
require_once 'rajapinnat/presta/presta_products.php';

class PrestaSpecificPrices extends PrestaClient {

  const RESOURCE = 'specific_prices';

  private $products = array();

  public function __construct($url, $api_key) {
    parent::__construct($url, $api_key);

    $presta_products = new PrestaProducts($url, $api_key);
    $this->products = $presta_products->all_skus();


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

    /**
     * <specific_price>
<id/>
<id_shop_group/>
<id_shop/>
<id_cart/>
<id_product/>
<id_product_attribute/>
<id_currency/>
<id_country/>
<id_group/>
<id_customer/>
<id_specific_price_rule/>
<price/>
<from_quantity/>
<reduction/>
<reduction_type/>
<from/>
<to/>
</specific_price>
     */

    if (!empty($specific_price['tuoteno'])) {
      $xml->specific_price->id_product;
    }

    return $xml;
  }

  public function sync_prices($prices) {
    $this->logger->log('---------Start specific price sync---------');

    try {
      $this->schema = $this->get_empty_schema();

      foreach ($prices as $price) {
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
