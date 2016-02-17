<?php

require_once 'rajapinnat/presta/presta_client.php';

class PrestaProductFeatures extends PrestaClient {

  private $all_features = null;

  public function __construct($url, $api_key) {
    parent::__construct($url, $api_key);
  }

  public function feature_id_by_value($value) {
    $feature_id = array_search($value, $this->fetch_all());

    if ($feature_id === false) {
      $this->logger->log("VIRHE! Ominaisuutta {$value} ei ole perustettu Prestaan!");
      return false;
    }

    return $feature_id;
  }


  protected function resource_name() {
    return 'product_features';
  }

  protected function generate_xml($record, SimpleXMLElement $existing_record = null) {
    if (is_null($existing_record)) {
      $xml = empty_xml();
    }
    else {
      $xml = $existing_record;
    }

    $xml->product_feature->id = $record['id'];

    // we must set name for all languages
    $languages = count($xml->product_feature->name->language);

    for ($i=0; $i < $languages; $i++) {
      $xml->product_feature->name->language[$i] = $record['value'];
    }

    return $xml;
  }

  private function fetch_all() {
    if (isset($this->all_features)) {
      return $this->all_features;
    }

    $display = array('id', 'name');

    $this->all_features = $this->all($display);

    return $this->all_features;
  }
}
