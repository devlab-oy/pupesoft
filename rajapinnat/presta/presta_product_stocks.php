<?php

require_once 'rajapinnat/presta/presta_client.php';

class PrestaProductStocks extends PrestaClient {

  const RESOURCE = 'stock_availables';

  public function __construct($url, $api_key) {
    parent::__construct($url, $api_key);
  }

  protected function resource_name() {
    return self::RESOURCE;
  }

  /**
   *
   * @param array $stock
   * @param SimpleXMLElement $existing_stock
   * @return \SimpleXMLElement
   */
  protected function generate_xml($stock, SimpleXMLElement $existing_stock = null) {
    $xml = new SimpleXMLElement($this->schema->asXML());

    if (!is_null($existing_stock)) {
      $xml = $existing_stock;
    }

    return $xml;
  }
}
