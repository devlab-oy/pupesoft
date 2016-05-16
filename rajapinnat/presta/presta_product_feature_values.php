<?php

require_once 'rajapinnat/presta/presta_client.php';

class PrestaProductFeatureValues extends PrestaClient {
  private $all_values = null;

  public function __construct($url, $api_key, $log_file) {
    parent::__construct($url, $api_key, $log_file);
  }

  public function value_id_by_value($value) {
    foreach ($this->fetch_all() as $feature_value) {
      // Match only to default language (this is an array if multiple languages)
      $feature = $feature_value["value"]["language"];
      $presta_value = is_array($feature) ? $feature[0] : $feature;

      if ($presta_value == $value) {
        return $feature_value['id'];
      }
    }

    return false;
  }

  public function reset_all_values() {
    $this->all_values = null;
  }

  protected function resource_name() {
    return 'product_feature_values';
  }

  protected function generate_xml($record, SimpleXMLElement $existing_record = null) {
    if (is_null($existing_record)) {
      $xml = $this->empty_xml();
    }
    else {
      $xml = $existing_record;
    }

    $xml->product_feature_value->id_feature = $record['id_feature'];

    // we must set value for all languages
    $languages = count($xml->product_feature_value->value->language);

    for ($i=0; $i < $languages; $i++) {
      $xml->product_feature_value->value->language[$i] = $record['value'];
    }

    return $xml;
  }

  private function fetch_all() {
    if (isset($this->all_values)) {
      return $this->all_values;
    }

    $display = array('id', 'value');

    $this->logger->log("Haetaan ominaisuuksien arvot.");
    $this->all_values = $this->all($display);

    return $this->all_values;
  }
}
