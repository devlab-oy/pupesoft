<?php

require_once 'rajapinnat/presta/presta_client.php';

class PrestaOrderHistories extends PrestaClient {
  public function __construct($url, $api_key) {
    parent::__construct($url, $api_key);
  }

  protected function resource_name() {
    return 'order_histories';
  }

  protected function generate_xml($order_history, SimpleXMLElement $existing_order_history = null) {
    if (is_null($existing_order_history)) {
      $xml = $this->empty_xml();
    }
    else {
      $xml = $existing_order_history;
    }

    $xml->order_history->id_order_state = $order_history['order_state'];
    $xml->order_history->id_order = $order_history['order_id'];
    $xml->order_history->id_employee = 0;

    return $xml;
  }
}
