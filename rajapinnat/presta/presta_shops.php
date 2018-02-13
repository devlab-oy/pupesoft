<?php

require_once 'rajapinnat/presta/presta_client.php';

class PrestaShops extends PrestaClient {
  private $all_shops = null;

  public function __construct($url, $api_key, $log_file) {
    parent::__construct($url, $api_key, $log_file);
  }

  protected function resource_name() {
    return 'shops';
  }

  protected function generate_xml($record, SimpleXMLElement $existing_record = null) {
    throw new Exception('You shouldnt be here, CRUD is not implemented!');
  }

  public function shop_by_id($value) {
    $display = array('id', 'name');
    $filters = array();

    if (!empty($value)) {
      $filters = array("id" => $value);
    }

    foreach ($this->all($display, $filters) as $record) {
      if ($record['id'] == $value) {
        return $record;
      }
    }

    return null;
  }

  public function first_shop() {
    $shops = $this->fetch_all();
    $shop = $shops[0];

    return $shop;
  }

  public function fetch_all() {
    if (isset($this->all_shops)) {
      return $this->all_shops;
    }

    $this->logger->log("Haetaan kaikki kaupat");

    $display = array('id', 'name');
    $this->all_shops = $this->all($display);

    return $this->all_shops;
  }
}
