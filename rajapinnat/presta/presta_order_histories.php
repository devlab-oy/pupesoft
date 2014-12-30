<?php

require_once 'rajapinnat/presta/presta_client.php';

class PrestaOrderHistories extends PrestaClient {

  const RESOURCE = 'order_histories';

  public function __construct($url, $api_key) {
    parent::__construct($url, $api_key);
  }

  protected function resource_name() {
    return self::RESOURCE;
  }

  /**
   *
   * @param array $order_history
   * @param SimpleXMLElement $existing_order_history
   * @return \SimpleXMLElement
   */
  protected function generate_xml($order_history, SimpleXMLElement $existing_order_history = null) {
    $xml = new SimpleXMLElement($this->schema->asXML());

    if (!is_null($existing_order_history)) {
      $xml = $existing_order_history;
    }

    $xml->order_history->id_order_state = $order_history['order_state'];
    $xml->order_history->id_order = $order_history['order_id'];
    $xml->order_history->id_employee = 0;

    return $xml;
  }

  public function create(array $resource) {
    parent::create($resource);
  }
}
