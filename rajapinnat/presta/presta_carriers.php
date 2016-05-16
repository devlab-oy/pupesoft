<?php

require_once 'rajapinnat/presta/presta_client.php';

class PrestaCarriers extends PrestaClient {
  public function __construct($url, $api_key, $log_file) {
    parent::__construct($url, $api_key, $log_file);
  }

  protected function resource_name() {
    return 'carriers';
  }

  protected function generate_xml($resource, SimpleXMLElement $existing_resource = null) {
    throw new Exception('You shouldnt be here, CRUD is not implemented!');
  }
}
