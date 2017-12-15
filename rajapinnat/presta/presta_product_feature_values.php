<?php

require_once 'rajapinnat/presta/presta_client.php';

class PrestaProductFeatureValues extends PrestaClient {
  private $all_values = null;

  public function __construct($url, $api_key, $log_file) {
    parent::__construct($url, $api_key, $log_file);
  }

  public function add_by_value($feature_id, $value) {
    $value = trim($value);

    if (empty($value)) {
      return 0;
    }

    $value_id = $this->fetch_id_by_name($value);

    if (empty($value_id)) {
      $feature_value = array(
        "id_feature" => $feature_id,
        "value" => $value,
      );

      // Create feature value
      try {
        $response = $this->create($feature_value);
        $value_id = $response['product_feature_value']['id'];

        // nollataan all values array, jotta se haetaan uusiksi prestasta, niin ei perusteta samaa arvoa monta kertaa
        $this->reset_all_values();
        $this->logger->log("Perustettiin ominaisuuden arvo '{$value}' ({$value_id})");
      }
      catch (Exception $e) {
        $this->logger->log("Ominaisuuden arvon '{$value}' perustaminen epäonnistui!");

        $value_id = 0;
      }
    }

    return $value_id;
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

  private function fetch_id_by_name($name) {
    $display = array('id', 'value');
    $filters = array("value" => $name);

    foreach ($this->all($display, $filters) as $feature_value) {
      // Match only to default language (this is an array if multiple languages)
      $feature = $feature_value["value"]["language"];
      $presta_value = is_array($feature) ? $feature[0] : $feature;

      if ($presta_value == $name) {
        return $feature_value['id'];
      }
    }

    return null;
  }
}
