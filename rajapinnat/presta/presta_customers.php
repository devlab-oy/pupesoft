<?php

require_once 'rajapinnat/presta/presta_client.php';
require_once 'rajapinnat/presta/presta_addresses.php';

class PrestaCustomers extends PrestaClient {

  const RESOURCE = 'customers';

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

    $xml->customer->firstname = $customer['etunimi'];
    $xml->customer->lastname = $customer['sukunimi'];
    //Email is mandatory
    $xml->customer->email = $customer['email'];
    if (empty($customer['email'])) {
      $xml->customer->email = 'test@example.com';
    }

    $xml->customer->active = 1;

    if (!empty($customer['presta_customergroup_id'])) {
      $xml->customer->id_default_group = $customer['presta_customergroup_id'];
      $xml->customer->associations->groups->groups->id = $customer['presta_customergroup_id'];
    }

    return $xml;
  }

  public function sync_customers($customers) {
    $this->logger->log('---------Start customer sync---------');

    try {
      $this->schema = $this->get_empty_schema();
      $existing_customers = $this->all(array('id'));
      $existing_customers = array_column($existing_customers, 'id');

      foreach ($customers as $customer) {
        try {
          $presta_address = new PrestaAddresses($this->get_url(), $this->get_api_key());
          if (in_array($customer['ulkoinen_asiakasnumero'], $existing_customers)) {
            $id = $customer['ulkoinen_asiakasnumero'];
            $this->update($id, $customer);

            $customer['presta_customer_id'] = $id;
            $presta_address->update_with_customer_id($customer);
          }
          else {
            $response = $this->create($customer);
            $id = (string) $response['customer']['id'];

            $customer['presta_customer_id'] = $id;
            $presta_address->create($customer);
          }

          $this->update_to_pupesoft($id, $customer['tunnus'], $customer['yhtio']);
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

  private function update_to_pupesoft($presta_id, $pupesoft_id, $yhtio) {
    if (empty($presta_id) or empty($pupesoft_id) or empty($yhtio)) {
      return false;
    }

    $query = "UPDATE yhteyshenkilo
              SET ulkoinen_asiakasnumero = {$presta_id}
              WHERE yhtio = '{$yhtio}'
              AND tunnus = {$pupesoft_id}";
    pupe_query($query);

    return true;
  }

  /**
   * Overrides parents get
   *
   * @param int $id
   */
  public function get($id) {
    return parent::get($id);
  }
}
