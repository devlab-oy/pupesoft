<?php

require_once 'rajapinnat/presta/presta_client.php';

class PrestaCustomerGroups extends PrestaClient {

  const RESOURCE = 'groups';

  public function __construct($url, $api_key) {
    parent::__construct($url, $api_key);
  }

  protected function resource_name() {
    return self::RESOURCE;
  }

  /**
   *
   * @param array $group
   * @param SimpleXMLElement $existing_group
   * @return \SimpleXMLElement
   */
  protected function generate_xml($group, SimpleXMLElement $existing_group = null) {
    $xml = new SimpleXMLElement($this->schema->asXML());

    if (!is_null($existing_group)) {
      $xml = $existing_group;
    }

    return $xml;
  }

  public function sync_groups($groups) {
    $this->logger->log('---------Start group sync---------');

    try {
      $this->schema = $this->get_empty_schema();
      $existing_groups = $this->all(array('id', 'note'));
      $existing_groups = array_column($existing_groups, 'note', 'id');

      foreach ($groups as $group) {
        try {
          if (in_array($group['tunnus'], $existing_groups)) {
            $id = array_search($group['tunnus'], $existing_groups);
            $this->update($id, $group);
          }
          else {
            $this->create($group);
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

    $this->logger->log('---------End group sync---------');

    return true;
  }
}
