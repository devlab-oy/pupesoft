<?php

require_once 'rajapinnat/presta/presta_client.php';

class PrestaCountries extends PrestaClient {
  private $all_countries = null;

  public function __construct($url, $api_key, $log_file) {
    parent::__construct($url, $api_key, $log_file);
  }

  protected function resource_name() {
    return 'countries';
  }

  protected function generate_xml($record, SimpleXMLElement $existing_record = null) {
    throw new Exception('You shouldnt be here, CRUD is not implemented!');
  }

  public function find_country_by_code($iso_code) {
    $iso_code = strtoupper($iso_code);

    foreach ($this->all_countries() as $country) {
      if ($iso_code == $country['iso_code']) {
        return $country['id'];
      }
    }

    return null;
  }

  public function first_country() {
    $all = $this->all_countries();

    return $all[0]['id'];
  }

  private function all_countries() {
    if ($this->all_countries !== null) {
      return $this->all_countries;
    }

    $this->logger->log('Haetaan kaikki maat Prestashopista');

    $display = array('id', 'iso_code');
    $filter = array();
    $shop_group_id = $this->shop_group_id();

    $countries = $this->all($display, $filter, null, $shop_group_id);

    $this->all_countries = $countries;

    return $countries;
  }
}
