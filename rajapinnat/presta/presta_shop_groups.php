<?php

require_once 'rajapinnat/presta/presta_client.php';

class PrestaShopGroups extends PrestaClient {
  private $all_shop_groups = null;

  public function __construct($url, $api_key) {
    parent::__construct($url, $api_key);
  }

  protected function resource_name() {
    return 'shop_groups';
  }

  protected function generate_xml($shop, SimpleXMLElement $existing_shop = null) {
    throw new Exception('You shouldnt be here! Shop does not have CRUD yet');
  }

  public function first_shop_group() {
    $shops = $this->fetch_all();
    $shop = $shops[0];

    return $shop;
  }

  public function fetch_all() {
    if (isset($this->all_shop_groups)) {
      return $this->all_shop_groups;
    }

    $this->logger->log("Haetaan kaikki kaupparyhmät");

    $display = array('id', 'name');
    $this->all_shop_groups = $this->all($display);

    return $this->all_shop_groups;
  }
}
