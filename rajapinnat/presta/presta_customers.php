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
   * @param array   $customer
   * @param SimpleXMLElement $existing_customer
   * @return \SimpleXMLElement
   */


  protected function generate_xml($customer, SimpleXMLElement $existing_customer = null) {
    $xml = new SimpleXMLElement($this->schema->asXML());

    if (!is_null($existing_customer)) {
      $xml = $existing_customer;
    }

    $_email = empty($customer['email']) ? 'test@example.com' : $customer['email'];

    // max 32, numbers and special characters not allowed
    $_nimi = preg_replace("/[^a-zA-Z������ ]+/", "", substr($customer['nimi'], 0, 32));
    $_nimi = empty($_nimi) ? '-' : utf8_encode($_nimi);

    $xml->customer->firstname = "-";
    $xml->customer->lastname = $_nimi;
    $xml->customer->email = $_email;

    if (!empty($customer['salasanan_resetointi'])) {
      $xml->customer->passwd = $customer['salasanan_resetointi'];
      $this->confirm_password_reset($customer['tunnus'], $customer['yhtio']);
    }

    $xml->customer->active = 1;

    $group_id = $customer['presta_customergroup_id'];

    if (!empty($group_id)) {
      $xml->customer->id_default_group = $group_id;
      $xml->customer->associations->groups->groups->id = $group_id;
    }

    return $xml;
  }

  public function sync_customers($customers) {
    $this->logger->log('---------Start customer sync---------');

    try {
      $this->schema = $this->get_empty_schema();
      $existing_customers = $this->all(array('id'));
      $existing_customers = array_column($existing_customers, 'id');

      $total = count($customers);
      $current = 0;

      foreach ($customers as $customer) {
        $current++;
        $this->logger->log("[{$current}/{$total}]");

        try {
          $presta_address = new PrestaAddresses($this->url(), $this->api_key());
          $id = $customer['ulkoinen_asiakasnumero'];

          if (in_array($id, $existing_customers)) {
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

    $this->logger->log("P�ivitet��n {$yhtio} Pupesoft yhteyshenkil�lle {$pupesoft_id} Presta id {$presta_id}");

    $query = "UPDATE yhteyshenkilo
              SET ulkoinen_asiakasnumero = {$presta_id}
              WHERE yhtio = '{$yhtio}'
              AND tunnus  = {$pupesoft_id}";
    pupe_query($query);

    return true;
  }

  private function confirm_password_reset($contact_id, $yhtio) {
    if (empty($contact_id) or empty($yhtio)) {
      return false;
    }

    $query = "UPDATE yhteyshenkilo
              SET salasanan_resetointi = ''
              WHERE yhtio = '{$yhtio}'
              AND tunnus  = {$contact_id}";
    pupe_query($query);

    return true;
  }

  /**
   * Overrides parents get
   *
   * @param int     $id
   */
  public function get($id) {
    return parent::get($id);
  }
}
