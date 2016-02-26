<?php

require_once 'rajapinnat/presta/presta_client.php';
require_once 'rajapinnat/presta/presta_addresses.php';

class PrestaCustomers extends PrestaClient {
  private $default_groups = array();

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
    if (is_null($existing_customer)) {
      $xml = $this->empty_xml();
    }
    else {
      $xml = $existing_customer;
    }

    $_email = empty($customer['email']) ? 'test@example.com' : $customer['email'];

    // max 32, numbers and special characters not allowed
    $_nimi = preg_replace("/[^a-zA-ZäöåÄÖÅ ]+/", "", substr($customer['nimi'], 0, 32));
    $_nimi = empty($_nimi) ? '-' : utf8_encode($_nimi);

    $xml->customer->firstname = "-";
    $xml->customer->lastname = $_nimi;
    $xml->customer->email = $_email;

    if (!empty($customer['verkkokauppa_salasana'])) {
      $xml->customer->passwd = $customer['verkkokauppa_salasana'];
      $this->confirm_password_reset($customer['tunnus'], $customer['yhtio']);
    }

    $xml->customer->active = 1;

    $group_id = $customer['presta_customergroup_id'];
    $xml->customer->id_default_group = $group_id;

    // First, remove all groups from XML
    $remove_node = $xml->customer->associations->groups;
    $dom_node = dom_import_simplexml($remove_node);
    $dom_node->parentNode->removeChild($dom_node);

    // Add it back
    $groups = $xml->customer->associations->addChild('groups');

    // Groups customer belongs to
    $all_groups = $this->default_groups;

    // add group to default groups array
    if (!empty($group_id)) {
      $all_groups[] = $group_id;
    }

    // id's must be in order
    sort($all_groups);

    // add all groups
    foreach ($all_groups as $group_id) {
      $group = $groups->addChild('groups');
      $group->addChild('id', $group_id);

      $this->logger->log("Lisätään asiakas ryhmään {$group_id}");
    }

    return $xml;
  }

  public function sync_customers($customers) {
    $this->logger->log('---------Start customer sync---------');

    try {
      $existing_customers = $this->all(array('id'));
      $existing_customers = array_column($existing_customers, 'id');

      $total = count($customers);
      $current = 0;

      foreach ($customers as $customer) {
        $current++;
        $this->logger->log("[{$current}/{$total}] Asiakas {$customer['nimi']}");

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

    $this->logger->log("Päivitetään {$yhtio} Pupesoft yhteyshenkilölle {$pupesoft_id} Presta id {$presta_id}");

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
              SET verkkokauppa_salasana = ''
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

  public function set_default_groups($value) {
    if (is_array($value)) {
      $this->default_groups = $value;
    }
  }
}
