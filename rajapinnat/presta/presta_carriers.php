<?php

require_once 'rajapinnat/presta/presta_client.php';

class PrestaCarriers extends PrestaClient {
  public function __construct($url, $api_key, $log_file) {
    parent::__construct($url, $api_key, $log_file);
  }

  protected function resource_name() {
    return 'carriers';
  }

  protected function generate_xml($shop, SimpleXMLElement $existing_shop = null) {
    throw new Exception('You shouldnt be here! Shop does not have CRUD yet');
  }
}
