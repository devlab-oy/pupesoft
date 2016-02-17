<?php

require_once 'rajapinnat/presta/presta_client.php';

class PrestaProductFeatureValues extends PrestaClient {
  # http://prestashop.devlab.fi/api/product_feature_values/34

  public function __construct($url, $api_key) {
    parent::__construct($url, $api_key);
  }

  public function value_id_by_value($value) {
    array_search($value, $this->fetch_all());
  }

  protected function resource_name() {
    return 'product_feature_values';
  }

  protected function generate_xml($record, SimpleXMLElement $existing_record = null) {
    if (is_null($existing_record)) {
      $xml = empty_xml();
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
}
