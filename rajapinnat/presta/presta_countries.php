<?php

require_once 'rajapinnat/presta/presta_client.php';

class PrestaCountries extends PrestaClient {

  const RESOURCE = 'countries';

  public function __construct($url, $api_key) {
    parent::__construct($url, $api_key);
  }

  protected function resource_name() {
    return self::RESOURCE;
  }

  /**
   *
   * @param array   $country
   * @param SimpleXMLElement $existing_country
   * @return \SimpleXMLElement
   */


  protected function generate_xml($country, SimpleXMLElement $existing_country = null) {
    throw new Exception('You shouldnt be here! Country does not have CRUD yet');
  }

  /**
   *
   * @return array
   */
  public function find_findland() {
    $display = $filter = array();
    $filter['iso_code'] = 'FI';

    $countries = $this->all($display, $filter);

    $country = $countries[0];

    return $country;
  }
}
