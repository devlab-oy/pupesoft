<?php

require_once 'rajapinnat/presta/presta_client.php';
require_once 'rajapinnat/presta/presta_countries.php';

class PrestaAddresses extends PrestaClient {
  private $presta_countries = null;

  public function __construct($url, $api_key, $log_file) {
    $this->presta_countries = new PrestaCountries($url, $api_key, $log_file);

    parent::__construct($url, $api_key, $log_file);
  }

  protected function resource_name() {
    return 'addresses';
  }

  /**
   *
   * @param array   $address
   * @param SimpleXMLElement $existing_address
   * @return \SimpleXMLElement
   */
  protected function generate_xml($address, SimpleXMLElement $existing_address = null) {
    if (is_null($existing_address)) {
      $xml = $this->empty_xml();
    }
    else {
      $xml = $existing_address;
    }

    // find customer country
    if (!empty($address['maa'])) {
      $country = $this->presta_countries->find_country_by_code($address['maa']);
    }

    // default to finland
    if (empty($country) {
      $country = $this->presta_countries->find_country_by_code('FI');
    }

    // Mandatory fields
    $_osoite = empty($address['osoite']) ? "-" : utf8_encode($address['osoite']);
    $_postitp = empty($address['postitp']) ? "-" : utf8_encode($address['postitp']);
    $_puh = empty($address['puh']) ? "-" : $address['puh'];

    // max 32, numbers and special characters not allowed
    $_nimi = preg_replace("/[^a-zA-ZäöåÄÖÅ ]+/", "", substr($address['nimi'], 0, 32));
    $_nimi = empty($_nimi) ? '-' : utf8_encode($_nimi);

    $xml->address->id_country = $finland['id'];
    $xml->address->id_customer = $address['presta_customer_id'];
    $xml->address->alias = 'Home';
    $xml->address->lastname = $_nimi;
    $xml->address->firstname = '-';
    $xml->address->address1 = $_osoite;
    $xml->address->postcode = $address['postino'];
    $xml->address->city = $_postitp;
    $xml->address->phone = $_puh;
    $xml->address->phone_mobile = $address['gsm'];

    return $xml;
  }

  public function update_with_customer_id(array $customer, $id_shop = null) {
    $presta_address = $this->find_address_by_customer_id($customer['presta_customer_id'], $id_shop);

    if (is_null($presta_address)) {
      parent::create($customer, $id_shop);
    }
    else {
      parent::update($presta_address['id'], $customer, $id_shop);
    }
  }

  private function find_address_by_customer_id($customer_id, $id_shop = null) {
    $display = $filter = array();
    $filter['id_customer'] = $customer_id;

    $addresses = $this->all($display, $filter, $id_shop);

    $address = isset($addresses[0]) ? $addresses[0] : null;

    return $address;
  }
}
