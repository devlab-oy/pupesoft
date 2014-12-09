<?php

require_once 'rajapinnat/presta/presta_client.php';

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

    $xml->customer->firstname = '-';

    $name = preg_replace('/[^a-zA-Z]/', '', $customer['nimi']);
    $xml->customer->lastname = $name;
    $email = 'test@example.com';
    if (!empty($customer['email'])) {
      $email = $customer['email'];
    }
    $xml->customer->email = $email;

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
          if (in_array($customer['ulkoinen_asiakasnumero'], $existing_customers)) {
            $id = $customer['ulkoinen_asiakasnumero'];
            $this->update($id, $customer);
          }
          else {
            $response = $this->create($customer);
            $id = (string) $response['customer']['id'];
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
    $customer = parent::get($id);

    return $customer;
  }
}
