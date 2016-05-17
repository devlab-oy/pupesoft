<?php

require_once 'rajapinnat/presta/presta_client.php';

class PrestaCountries extends PrestaClient {
  private $finland = null;

  public function __construct($url, $api_key, $log_file) {
    parent::__construct($url, $api_key, $log_file);
  }

  protected function resource_name() {
    return 'countries';
  }

  protected function generate_xml($record, SimpleXMLElement $existing_record = null) {
    throw new Exception('You shouldnt be here, CRUD is not implemented!');
  }

  public function find_finland() {
    if (!is_null($this->finland)) {
      return $this->finland;
    }

    $display = $filter = array();
    $filter['iso_code'] = 'FI';

    $countries = $this->all($display, $filter);

    $country = $countries[0];

    $this->finland = $country;

    return $country;
  }
}
