<?php

require_once 'rajapinnat/presta/presta_client.php';
require_once 'rajapinnat/presta/presta_countries.php';

class PrestaAddresses extends PrestaClient {

  const RESOURCE = 'addresses';

  public function __construct($url, $api_key) {
    parent::__construct($url, $api_key);
  }

  protected function resource_name() {
    return self::RESOURCE;
  }

  /**
   *
   * @param array $address
   * @param SimpleXMLElement $existing_address
   * @return \SimpleXMLElement
   */
  protected function generate_xml($address, SimpleXMLElement $existing_address = null) {
    $xml = new SimpleXMLElement($this->schema->asXML());

    if (!is_null($existing_address)) {
      $xml = $existing_address;
    }

    $presta_country = new PrestaCountries($this->get_url(), $this->get_api_key());
    $finland = $presta_country->find_findland();
    $xml->address->id_country = $finland['id'];
    $xml->address->id_customer = $address['presta_customer_id'];
    //Address alias is mandatory
    $xml->address->alias = 'Home';
    $xml->address->lastname = $address['sukunimi'];
    $xml->address->firstname = $address['etunimi'];
    $xml->address->address1 = $address['osoite'];
    $xml->address->postcode = $address['postino'];
    $xml->address->city = $address['postitp'];
    //Phone or phone_mobile is mandatory
    $xml->address->phone = $address['puh'];
    $xml->address->phone_mobile = $address['gsm'];

    if (empty($address['puh']) and empty($address['gsm'])) {
      $xml->address->phone = '0000000000';
    }

    return $xml;
  }

  public function create(array $address) {
    parent::create($address);
  }

  public function update_with_customer_id(array $address) {
    $presta_address = $this->find_address_by_customer_id($address['presta_customer_id']);
    parent::update($presta_address['id'], $address);
  }

  private function find_address_by_customer_id($customer_id) {
    $display = $filter = array();
    $filter['id_customer'] = $customer_id;

    $addresses = $this->all($display, $filter);

    $address = $addresses[0];

    return $address;
  }
}
