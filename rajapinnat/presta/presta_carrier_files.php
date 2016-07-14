<?php

require_once 'rajapinnat/presta/presta_client.php';

# HUOM, tämä ei ole normaali Prestan API. Tämä on tehty customina Prestan päähän.
# Ei voida käyttää perusasennuksissa.

class PrestaCarrierFiles extends PrestaClient {
  public function __construct($url, $api_key, $log_file) {
    parent::__construct($url, $api_key, $log_file);
  }

  public function save_file($order_id, $directory) {
    // custom URI
    $url = "{$this->url}api/carrier_file/{$order_id}";

    // this requires changing PrestaShopWebservice executeRequest -method to public
    // otherwise we don't have any way to get a binary response from presta
    // without rewriting the whole curl call
    $response    = $this->ws->executeRequest($url);
    $file_data   = $response['response'];
    $status_code = $response['status_code'];

    // we get 404 if carrier file does not exist. 200 was a successful fetch.
    if ($status_code != 200) {
      $this->logger->log("Ei carrier_file liitettä ({$status_code})");

      return null;
    }

    $this->logger->log("Haettiin carrier_file/{$order_id}");

    // save file to given directory
    if (!empty($file_data)) {
      $full_path = tempnam($directory, 'carrier_file_');

      file_put_contents($full_path, $file_data);

      $this->logger->log("Tallennettiin {$full_path}");
    }

    // return full path to file
    return $full_path;
  }

  protected function resource_name() {
    return 'carrier_file';
  }

  protected function generate_xml($resource, SimpleXMLElement $existing_resource = null) {
    throw new Exception('You shouldnt be here, CRUD is not implemented!');
  }
}
