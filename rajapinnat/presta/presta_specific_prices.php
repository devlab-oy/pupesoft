<?php

require_once 'rajapinnat/presta/presta_client.php';

class PrestaSpecificPrices extends PrestaClient {

  const RESOURCE = 'specific_prices';

  public function __construct($url, $api_key) {
    parent::__construct($url, $api_key);
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

    return $xml;
  }

  public function create(array $resource) {
    parent::create($resource);
  }
}
