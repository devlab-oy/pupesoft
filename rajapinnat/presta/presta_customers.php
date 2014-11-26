<?php

require_once 'rajapinnat/presta/presta_client.php';

class PrestaCustomers extends PrestaClient {

  const RESOURCE = 'customers';
  const TUNNUS_SEPARATOR = ';#x#';

  public function __construct($url, $api_key) {
    parent::__construct($url, $api_key);
  }

  protected function resource_name() {
    return self::RESOURCE;
  }

  /**
   *
   * @param array $customer
   * @param SimpleXMLElement $existing_customer
   * @return \SimpleXMLElement
   */
  protected function generate_xml($customer, SimpleXMLElement $existing_customer = null) {
    $xml = new SimpleXMLElement($this->schema->asXML());

    if (!is_null($existing_customer)) {
      $xml = $existing_customer;
    }

    $xml->customer->firstname = '-';
    
    $name = preg_replace('/[^a-zA-Z]/', '', $customer['nimi']);
    $xml->customer->lastname = $name;
    $email = 'test@example.com';
    if (!empty($customer['email'])) {
      $email = $customer['email'];
    }
    $xml->customer->email = $email;
    $pupesoft_tunnus = "Pupesoft tunnus do not remove: "
      . self::TUNNUS_SEPARATOR . $customer['tunnus'] . self::TUNNUS_SEPARATOR;
    $xml->customer->note = $pupesoft_tunnus;

    return $xml;
  }

  public function sync_customers($customers) {
    $this->logger->log('---------Start customer sync---------');

    try {
      $this->schema = $this->get_empty_schema();
      //Fetch all products with ID's and SKU's only
      $existing_customers = $this->all(array('id', 'note'));
      $existing_customers = $existing_customers['customers']['customer'];
      $existing_customers = array_column($existing_customers, 'note', 'id');
      //Pupesoft tunnus is put in note column to make update easier
      //and it is separated with self::TUNNUS_SEPARATOR
      $existing_customers = array_filter($existing_customers, array($this, 'filter_tunnus'));
      array_walk($existing_customers, array($this, 'sanitize_tunnus'));

      foreach ($customers as $customer) {
        try {
          if (in_array($customer['tunnus'], $existing_customers)) {
            $id = array_search($customer['tunnus'], $existing_customers);
            $this->update($id, $customer);
          }
          else {
            $this->create($customer);
          }
        }
        catch (Exception $e) {
          //Do nothing here. If create / update throws exception loggin happens inside those functions
          //Exception is not thrown because we still want to continue syncing for other products
        }
      }
    }
    catch (Exception $e) {
      //Exception logging happens in create / update.

      return false;
    }

    $this->logger->log('---------End customer sync---------');

    return true;
  }

  public function sanitize_tunnus(&$tunnus, $key) {
    $tunnus_array = explode(self::TUNNUS_SEPARATOR, $tunnus);
    $tunnus = $tunnus_array[1];
  }

  public function filter_tunnus($value) {
    if (empty($value)) {
      return false;
    }

    return true;
  }
}
