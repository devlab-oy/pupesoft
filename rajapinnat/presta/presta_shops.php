<?php

require_once 'rajapinnat/presta/presta_client.php';

class PrestaShops extends PrestaClient {
  private $all_shops = null;

  public function __construct($url, $api_key) {
    parent::__construct($url, $api_key);
  }

  protected function resource_name() {
    return 'shops';
  }

  /**
   *
   * @param array   $shop
   * @param SimpleXMLElement $existing_shop
   * @return \SimpleXMLElement
   */

  protected function generate_xml($shop, SimpleXMLElement $existing_shop = null) {
    throw new Exception('You shouldnt be here! Shop does not have CRUD yet');
  }

  public function shop_by_id($value) {
    foreach ($this->fetch_all() as $record) {
      if ($record['id'] == $value) {
        return $record;
      }
    }

    return null;
  }

  /**
   * Fetches the first shop in presta
   *
   * @return array
   * @throws Exception
   */
  public function first_shop() {
    $shops = $this->fetch_all();
    $shop = $shops[0];

    return $shop;
  }

  private function fetch_all() {
    if (isset($this->all_shops)) {
      return $this->all_shops;
    }

    $this->logger->log("Haetaan kaikki kaupat");

    $display = array('id', 'name');
    $this->all_shops = $this->all($display);

    return $this->all_shops;
  }
}
