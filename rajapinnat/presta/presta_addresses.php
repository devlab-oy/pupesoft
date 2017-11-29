<?php

require_once 'rajapinnat/presta/presta_client.php';
require_once 'rajapinnat/presta/presta_countries.php';

class PrestaAddresses extends PrestaClient {
  private $customer_handling = null;
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
    if (empty($country)) {
      $country = $this->presta_countries->find_country_by_code('FI');
    }

    // default to first country
    if (empty($country)) {
      $country = $this->presta_countries->first_country();
    }

    $xml->address->address1     = $this->clean_field($address['osoite']);
    $xml->address->alias        = 'Home';
    $xml->address->city         = $this->clean_field($address['postitp']);
    $xml->address->company      = $this->clean_alphanumeric($address['asiakas_nimi'], 64);
    $xml->address->dni          = $address['asiakas_id'];
    $xml->address->firstname    = '-';
    $xml->address->id_country   = $country;
    $xml->address->id_customer  = $address['id_customer'];
    $xml->address->lastname     = $this->clean_name($address['nimi']);
    $xml->address->phone        = $this->clean_field($address['puh']);
    $xml->address->phone_mobile = $this->clean_field($address['gsm']);
    $xml->address->postcode     = $this->clean_field($address['postino']);
    $xml->address->vat_number   = $this->clean_field($address['ytunnus']);
    
    if ($yhtio != 'audio') {
      $xml->address->address2 = $this->clean_field($address['asiakas_nimitark']);
    }

    return $xml;
  }

  public function set_customer_handling($value) {
    $this->customer_handling = $value;
  }

  public function add_addresses_for_customer($presta_customer_id, Array $addresses, $id_shop = null) {
    $added_addresses = array();
    $count = count($addresses);
    $current = 0;

    // loop all given addresses
    foreach ($addresses as $address) {
      $current++;
      $this->logger->log("Käsitellään osoite ({$current}/{$count})");

      // we need to add customer id to address -array since we don't know it in pupesoft
      $address['id_customer'] = $presta_customer_id;

      // add or update address
      $added_addresses[] = $this->address_with_customer_id($presta_customer_id, $address, $id_shop);
    }

    $this->remove_unused_addresses($presta_customer_id, $added_addresses, $id_shop);
  }

  private function address_with_customer_id($presta_customer_id, array $address, $id_shop) {
    $presta_address = $this->find_addresses_for_customer_id($presta_customer_id, $address, $id_shop);

    if (is_null($presta_address)) {
      $response = $this->create($address, $id_shop);
      $id = (string) $response['address']['id'];
    }
    else {
      $this->update($presta_address['id'], $address, $id_shop);
      $id = $presta_address['id'];
    }

    return $id;
  }

  private function find_addresses_for_customer_id($customer_id, $address, $id_shop) {
    $display = array();

    if ($this->customer_handling == 'yhteyshenkiloittain') {
      // if customer handling is "multiple Pupesoft customers per Prestashop user"
      // we must find the exact address, otherwise create new
      $filter = array(
        'address1'     => $this->clean_field($address['osoite']),
        'city'         => $this->clean_field($address['postitp']),
        'dni'          => $address['asiakas_id'],
        'id_customer'  => $customer_id,
        'postcode'     => $this->clean_field($address['postino']),
      );

      $this->logger->log("Etsitään osoitteet osoitetietojen mukaan ({$filter['address1']})");
    }
    else {
      // otherwise we search only with customer id
      $filter = array(
        'id_customer' => $customer_id,
      );

      $this->logger->log("Etsitään osoite asiakkaalle customer_id {$customer_id}");
    }

    $addresses = $this->all($display, $filter, $id_shop);

    $address = isset($addresses[0]) ? $addresses[0] : null;

    return $address;
  }

  private function remove_unused_addresses($customer_id, $added_addresses, $id_shop) {
    $display = array('id');
    $filter = array('id_customer' => $customer_id);

    // fetch all customer addresses
    $all_addresses = $this->all($display, $filter, $id_shop);
    $address_ids = array_column($all_addresses, 'id');

    // check which we need to remove
    $remove_ids = array_diff($address_ids, $added_addresses);

    // counters
    $current = 0;
    $total = count($remove_ids);

    // delete addresses from presta
    foreach ($remove_ids as $presta_id) {
      $current++;
      $this->logger->log("Poistetaan osoite {$presta_id} ({$current}/{$total})");

      try {
        $this->delete($presta_id);
      }
      catch (Exception $e) {
      }
    }
  }
}
