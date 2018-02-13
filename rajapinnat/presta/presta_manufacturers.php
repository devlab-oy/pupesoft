<?php

require_once 'rajapinnat/presta/presta_client.php';

class PrestaManufacturers extends PrestaClient {
  private $all_records = null;

  public function __construct($url, $api_key, $log_file) {
    parent::__construct($url, $api_key, $log_file);
  }

  public function add_manufacturer_by_name($name) {
    $name = trim($name);

    // empty means no manufacturer (= 0)
    if (empty($name)) {
      return 0;
    }

    // check do we have this already
    $manufacturer_id = $this->fetch_id_by_name($name);

    // nope, create
    if (empty($manufacturer_id)) {
      $manufacturer = array(
        "name" => $name,
      );

      // Create manufacturer
      try {
        $response = $this->create($manufacturer);
        $manufacturer_id = $response['manufacturer']['id'];

        $this->logger->log("Perustettiin valmistaja '{$name}' ({$manufacturer_id})");

        // nollataan array, haetaan uusiksi prestasta, että ei perusteta samaa monta kertaa
        $this->reset_all_records();
      }
      catch (Exception $e) {
        $this->logger->log("Valmistajan '{$name}' perustaminen epäonnistui!");

        $manufacturer_id = 0;
      }
    }

    return $manufacturer_id;
  }

  public function reset_all_records() {
    $this->all_records = null;
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

    $xml->manufacturer->name = $this->xml_value($record['name']);
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

  private function fetch_id_by_name($name) {
    $display = array('id', 'name');
    $filters = array("name" => $name);

    foreach ($this->all($display, $filters) as $record) {
      if ($record['name'] == $name) {
        return $record['id'];
      }
    }

    return null;
  }
}
