<?php

require_once 'logger.php';
require_once 'PSWebServiceLibrary.php';

class PrestaClient {

  private $url = null;
  private $api_key = null;
  private $ws = null;
  private $logger = null;

  public function __construct($url = null, $api_key = null) {
    $this->logger = new Logger('/tmp/presta_log.txt');
    $this->logger->set_date_format('Y-m-d H:i:s');

    if (!is_null($url)) {
      $this->url = $url;
    }

    if (!is_null($api_key)) {
      $this->api_key = $api_key;
    }

    if (!is_null($url) and !is_null($api_key)) {
      $this->ws = new PrestaShopWebservice($this->url, $this->api_key);
    }
  }

  public function set_url($url) {
    $this->url = $url;
  }

  public function set_api_ket($api_key) {
    $this->api_key = $api_key;
  }

  public function get_products() {
    $opt = array(
        'resource' => 'products',
        'id'       => 2,
    );
    $response_xml = $this->ws->get($opt);

    return $response_xml;
  }

  public function send_products(array $products) {
    $xml = $this->generate_products_xml($products);

    $opt = array(
        'resource' => 'products',
        'xml'      => $xml->asXML(),
    );
    $response_xml = $this->ws->add($opt);

    return $response_xml;
  }

  private function generate_products_xml($products) {

  }
}
