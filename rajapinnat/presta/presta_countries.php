<?php

require_once 'rajapinnat/presta/presta_client.php';

class PrestaCountries extends PrestaClient {
  public function __construct($url, $api_key, $log_file) {
    parent::__construct($url, $api_key, $log_file);
  }

  protected function resource_name() {
    return 'countries';
  }

  protected function generate_xml($record, SimpleXMLElement $existing_record = null) {
    throw new Exception('You shouldnt be here, CRUD is not implemented!');
  }

  public function find_findland() {
    $display = $filter = array();
    $filter['iso_code'] = 'FI';

    $countries = $this->all($display, $filter);

    $country = $countries[0];

    return $country;
  }
}
