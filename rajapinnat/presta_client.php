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
   * @return \SimpleXMLElement
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
      throw $e;
    }

    return $response_xml;
  }

  /**
   *
   * @param array $products
   * @return \SimpleXMLElement
   */
  public function sync_products(array $products) {
    try {
      $this->schema = $this->get_empty_schema('products');
      //Fetch all products with ID's and SKU's only
      $existing_products = $this->all_products(array('id', 'reference'));
      $existing_products = xml_to_array($existing_products);
      $existing_products = $existing_products['products']['product'];
      $existing_products = array_column($existing_products, 'reference', 'id');

      foreach ($products as $product) {
        if (in_array($product['tuoteno'], $existing_products)) {
          $id = array_search($product['tuoteno'], $existing_products);
          $response_xml = $this->update_product($id, $product);
        }
        else {
          $response_xml = $this->create_product($product);
        }

        if (!empty($product['images'])) {
          $this->create_product_images((string) $response_xml->product->id, $product['images']);
          $this->logger->log('Luotiin ' . count($product['images']) . ' tuotekuvaa');
        }
      }
    }
    catch (Exception $e) {
      //Exception logging happens in create / update. No need to do anything here
    }

    return $response_xml;
  }

  public function create_product($product) {
    try {
      $opt = array('resource' => 'products');
      $opt['postXml'] = $this->generate_product_xml($product)->asXML();
      $response_xml = $this->ws->add($opt);
      $this->logger->log("Luotiin tuote: {$product['tuoteno']}");
    }
    catch (Exception $e) {
      $msg = "Tuotteen {$product['tuoteno']} luonti epäonnistui";
      $this->logger->log($msg, $e);
      throw $e;
    }

    return $response_xml;
  }

  public function update_product($id, $product) {
    try {
      $opt = array('resource' => 'products');
      $opt['id'] = $id;
      $existing_product = $this->get_product($id);
      $opt['putXml'] = $this->generate_product_xml($product, $existing_product)->asXML();
      $response_xml = $this->ws->edit($opt);
      $this->logger->log("Päivitettiin tuote: {$product['tuoteno']}");
    }
    catch (Exception $e) {
      $msg = "Tuotteen {$product['tuoteno']} päivittäminen epäonnistui";
      $this->logger->log($msg, $e);
      throw $e;
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

      $this->logger->log("Luotiin tuotekuva tuotteelle {$product_id}");
    }
    catch (Exception $e) {
      $msg = "Tuotteen: {$product_id} tuotekuvan luonti epäonnistui";
      $this->logger->log($msg, $e);
    }

    return $response;
  }

  /**
   *
   * @param array $display
   * @return \SimpleXMLElement
   * @throws Exception
   */
  public function all_products($display = array()) {
    try {
      if (!empty($display)) {
        $display = '[' . implode(',', $display) . ']';
      }
      else {
        $display = 'full';
      }
      $opt = array(
          'resource' => 'products',
          'display'  => $display,
      );

      $response = $this->ws->get($opt);
    }
    catch (Exception $e) {
      $msg = "Kaikkien tuotteiden haku epäonnistui";
      $this->logger->log($msg, $e);
      throw $e;
    }

    return $response;
  }

  /**
   *
   * @param array $product
   * @return \SimpleXMLElement
   */
  private function generate_product_xml($product, $existing_product = null) {
    $xml = new SimpleXMLElement($this->schema->asXML());

    if (!is_null($existing_product)) {
      $xml = $existing_product;
      unset($xml->product->position_in_category);
      unset($xml->product->manufacturer_name);
      unset($xml->product->quantity);
    }

    $xml->product->reference = $product['tuoteno'];
    $xml->product->supplier_reference = $product['tuoteno'];
    $xml->product->price = $product['myyntihinta'];

    $xml->product->link_rewrite->language[0] = $product['nimitys'].'a';
    $xml->product->link_rewrite->language[1] = $product['nimitys'].'a';
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
