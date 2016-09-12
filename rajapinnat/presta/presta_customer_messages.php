<?php

require_once 'rajapinnat/presta/presta_client.php';

class PrestaCustomerMessages extends PrestaClient {
  public function __construct($url, $api_key, $log_file) {
    parent::__construct($url, $api_key, $log_file);
  }

  protected function resource_name() {
    return 'customer_messages';
  }

  protected function generate_xml($record, SimpleXMLElement $existing_record = null) {
    throw new Exception('You shouldnt be here, CRUD is not implemented!');
  }
}
