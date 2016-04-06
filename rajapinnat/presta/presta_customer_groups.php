<?php

require_once 'rajapinnat/presta/presta_client.php';

class PrestaCustomerGroups extends PrestaClient {
  private $presta_show_prices = 1;
  private $presta_price_display_method = 0;

  public function __construct($url, $api_key) {
    parent::__construct($url, $api_key);
  }

  protected function resource_name() {
    return 'groups';
  }

  /**
   *
   * @param array   $group
   * @param SimpleXMLElement $existing_group
   * @return \SimpleXMLElement
   */


  protected function generate_xml($group, SimpleXMLElement $existing_group = null) {
    if (is_null($existing_group)) {
      $xml = $this->empty_xml();
    }
    else {
      $xml = $existing_group;
    }

    if (empty($group['selitetark_2'])) {
      $xml->group->reduction = 0;
    }
    else {
      $xml->group->reduction = $group['selitetark_2'];
    }

    // 1 = Tax excluded, 0 = Tax included
    $xml->group->price_display_method = $this->presta_price_display_method;

    // 1 = Show prices, 0 = Don't show prices
    $xml->group->show_prices = $this->presta_show_prices;

    // Set default value from Pupesoft to all languages
    $languages = count($xml->group->name->language);

    // we must set these for all languages
    for ($i=0; $i < $languages; $i++) {
      $xml->group->name->language[$i] = utf8_encode($group['selitetark']);
    }

    return $xml;
  }

  public function sync_groups($groups) {
    $this->logger->log('---------Start group sync---------');

    try {
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
            $this->update_id_to_pupesoft($id, $group);
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

  private function update_id_to_pupesoft($id, $group) {
    if (empty($id)) {
      return false;
    }

    $query = "UPDATE avainsana
              SET selitetark_5 = '{$id}'
              WHERE yhtio = '{$group['yhtio']}'
              AND tunnus  = {$group['tunnus']}";
    pupe_query($query);
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

  public function set_price_display_method($value) {
    $this->presta_price_display_method = $value;
  }

  public function set_show_prices($value) {
    $this->presta_presta_show_prices = $value;
  }
}
