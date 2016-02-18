<?php

require_once 'rajapinnat/presta/presta_client.php';

class PrestaManufacturers extends PrestaClient {

  public $all_records = null;

  public function __construct($url, $api_key) {
    parent::__construct($url, $api_key);
  }

  public function manufacturer_id_by_name($value) {
    foreach ($this->fetch_all() as $record) {
      if ($record['name'] == $value) {
        return $record['id'];
      }
    }

    return null;
  }

  protected function resource_name() {
    return 'manufacturers';
  }

  protected function generate_xml($record, SimpleXMLElement $existing_record = null) {
    if (is_null($existing_record)) {
      $xml = $this->empty_xml();
    }
    else {
      $xml = $existing_record;
    }

    $xml->manufacturer->name = $record['name'];
    $xml->manufacturer->active = 1;

    return $xml;
  }

  private function fetch_all() {
    if (isset($this->all_records)) {
      return $this->all_records;
    }

    $display = array('id', 'name');

    $this->logger->log("Haetaan kaikki valmistajat.");
    $this->all_records = $this->all($display);

    return $this->all_records;
  }
}
