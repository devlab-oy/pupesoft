<?php

require_once 'rajapinnat/presta/presta_client.php';

class PrestaCustomerGroups extends PrestaClient {

  const RESOURCE = 'groups';

  private $kukarow = array();

  public function __construct($url, $api_key) {
    parent::__construct($url, $api_key);
  }

  protected function resource_name() {
    return self::RESOURCE;
  }

  public function set_kukarow($kukarow) {
    $this->kukarow = $kukarow;
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

    $xml->group->reduction = $group['alennus'];
    //1 = Tax excluded, 0 = tax included in presta
    $xml->group->price_display_method = 0;
    $xml->group->show_prices = 1;
    $xml->group->name->language[0] = $group['selite'];
    $xml->group->name->language[1] = $group['selite'];

    return $xml;
  }

  public function sync_groups($groups) {
    if (empty($this->kukarow)) {
      throw new Exception('Kukarow on tyhjä');
    }

    $this->logger->log('---------Start group sync---------');

    try {
      $this->schema = $this->get_empty_schema();
      $existing_groups = $this->all(array('id'));
      $existing_groups = array_column($existing_groups, 'id');

      foreach ($groups as $group) {
        try {
          if (in_array($group['presta_customergroup_id'], $existing_groups)) {
            $id = $group['presta_customergroup_id'];
            $this->update($id, $group);
          }
          else {
            $presta_group = $this->create($group);
            $id = (int) $presta_group['group']['id'];
            $this->insert_id_to_pupesoft($id, $group);
          }
        }
        catch (Exception $e) {
          //Do nothing here. If create / update throws exception loggin happens inside those functions
          //Exception is not thrown because we still want to continue syncing for other products
        }
      }

      $this->delete_unnecessary_groups($groups, $existing_groups);
    }
    catch (Exception $e) {
      //Exception logging happens in create / update.

      return false;
    }

    $this->logger->log('---------End group sync---------');

    return true;
  }

  private function insert_id_to_pupesoft($id, $group) {
    if (empty($id)) {
      return false;
    }

    $query = "SELECT tunnus
              FROM avainsana
              WHERE yhtio = '{$group['yhtio']}'
              AND laji = 'PRE_RYH_ID'
              AND selite = '{$group['tunnus']}'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) == 0) {
      $query = "INSERT INTO avainsana
                SET yhtio = '{$this->kukarow['yhtio']}',
                kieli = 'fi',
                laji = 'PRE_RYH_ID',
                laatija = '{$this->kukarow['kuka']}',
                muuttaja = '{$this->kukarow['kuka']}',
                luontiaika = NOW(),
                muutospvm = NOW(),
                selitetark = {$id},
                selite = {$group['tunnus']}";
      pupe_query($query);
    }
  }

  private function delete_unnecessary_groups($pupesoft_groups, $presta_groups) {
    $presta_ids = array_column($pupesoft_groups, 'presta_customergroup_id');

    $deleted_groups = array_diff($presta_groups, $presta_ids);

    //Presta default groups can not be deleted
    $presta_default_groups = array(1, 2, 3);
    foreach ($deleted_groups as $deleted_group) {
      if (in_array($deleted_group, $presta_default_groups)) {
        continue;
      }
      $this->delete($deleted_group);
    }
  }
}
