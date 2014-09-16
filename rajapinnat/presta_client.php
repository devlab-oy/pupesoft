<?php

require_once 'logger.php';
require_once 'PSWebServiceLibrary.php';

class PrestaClient {

  private $url = null;
  private $api_key = null;

  /**
   *
   * @var PrestaShopWebservice
   */
  private $ws = null;

  /**
   * Schema is used to create / update a resource. It contains given resource blank xml schema
   * @var SimpleXML
   */
  private $schema = null;

  /**
   *
   * @var Logger
   */
  private $logger = null;

  public function __construct($url, $api_key) {
    $this->logger = new Logger('/tmp/presta_log.txt');
    $this->logger->set_date_format('Y-m-d H:i:s');

    $this->url = $url;
    $this->api_key = $api_key;
    $this->ws = new PrestaShopWebservice($this->url, $this->api_key);
  }

  /**
   *
   * @param int $id
   * @return SimpleXML
   */
  public function get_product($id) {
    $opt = array(
        'resource' => 'products',
        'id'       => $id,
    );

    try {
      $response_xml = $this->ws->get($opt);
    }
    catch (Exception $e) {
      $msg = "Tuotteen: {$id} haku epäonnistui";
      $this->logger->log($msg, $e);
    }

    return $response_xml;
  }

  /**
   *
   * @param array $products
   * @return SimpleXML
   */
  public function create_products(array $products) {
    try {
      $this->schema = $this->get_empty_schema('products');
      $xml_messages = $this->generate_products_xml($products);

      $opt = array('resource' => 'products');
      foreach ($xml_messages as $xml_message) {
        $opt['postXml'] = $xml_message['product']->asXML();
        $response_xml = $this->ws->add($opt);

        if (!empty($xml_message['images'])) {
          $this->create_product_images((string) $response_xml->product->id, $xml_message['images']);
        }
      }
    }
    catch (Exception $e) {
      $msg = 'Tuotteiden luonti epäonnistui';
      $this->logger->log($msg, $e);
    }

    return $response_xml;
  }

  public function create_product_images($product_id, $images) {
    foreach ($images as $image) {
      $this->create_product_image($product_id, $image);
    }
  }

  public function create_product_image($product_id, $image) {
    try {
      $opt = array(
          'resource'   => 'products',
          'id'         => $product_id,
          'attachment' => $image,
          'method'     => 'POST'
      );

      $response = $this->ws->executeImageRequest($opt);
    }
    catch (Exception $e) {
      $msg = "Tuotteen: {$product_id} tuotekuvan luonti epäonnistui";
      $this->logger->log($msg, $e);
    }

    return $response;
  }

  /**
   *
   * @param array $products
   */
  private function generate_products_xml($products) {
    $xml_messages = array();
    foreach ($products as $product) {
      $xml_messages[$product['tunnus']]['images'] = $product['images'];
      $xml_messages[$product['tunnus']]['product'] = $this->populate_product_xml($product);
    }

    return $xml_messages;
  }

  private function populate_product_xml($product, $create = true) {
    $xml = new SimpleXMLElement($this->schema->asXML());
//    $request_xml->product->new = $create;
    $xml->product->reference = $product['tuoteno'];
    $xml->product->supplier_reference = $product['tuoteno'];
    $xml->product->price = $product['myyntihinta'];

    $xml->product->link_rewrite->language[0] = $product['nimitys'];
    $xml->product->link_rewrite->language[1] = $product['nimitys'];
    $xml->product->name->language[0] = $product['nimitys'];
    $xml->product->name->language[1] = $product['nimitys'];

    return $xml;
  }

  /**
   * Fetch empty xml schema for given resource
   *
   * @param string $resource
   * @return SimpleXML
   * @throws Exception
   */
  private function get_empty_schema($resource) {
    $opt = array(
        'resource' => "$resource?schema=blank"
    );

    try {
      $schema = $this->ws->get($opt);
    }
    catch (Exception $e) {
      $msg = "Resurssin {$resource} empty schema GET epäonnistui";
      $this->logger->log($msg, $e);
      throw new Exception($msg);
    }

    return $schema;
  }
}
