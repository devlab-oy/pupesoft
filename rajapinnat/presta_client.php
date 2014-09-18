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
   * @throws Exception
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
    $this->logger->log('---------Start product sync---------');

    try {
      $this->schema = $this->get_empty_schema('products');
      //Fetch all products with ID's and SKU's only
      $existing_products = $this->all_products(array('id', 'reference'));
      $existing_products = xml_to_array($existing_products);
      $existing_products = $existing_products['products']['product'];
      $existing_products = array_column($existing_products, 'reference', 'id');

      foreach ($products as $product) {
        try {
          if (in_array($product['tuoteno'], $existing_products)) {
            $id = array_search($product['tuoteno'], $existing_products);
            $response_xml = $this->update_product($id, $product);
            $this->delete_product_images($id);
          }
          else {
            $response_xml = $this->create_product($product);
            $id = (string) $response_xml->product->id;
          }

          $this->create_product_images($id, $product['images']);
        }
        catch (Exception $e) {
          //Do nothing here. If create / update throws exception loggin happens inside those functions
          //Exception is not thrown because we still want to continue syncing for other products
        }
      }
    }
    catch (Exception $e) {
      //Exception logging happens in create / update. No need to do anything here
    }

    $this->logger->log('---------End product sync---------');
    return $response_xml;
  }

  /**
   *
   * @param array $product
   * @return SimpleXMLElement
   * @throws Exception
   */
  public function create_product($product) {
    $opt = array('resource' => 'products');

    try {
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

  /**
   *
   * @param int $id
   * @param array $product
   * @return SimpleXMLElement
   * @throws Exception
   */
  public function update_product($id, $product) {
    $opt = array('resource' => 'products');
    $opt['id'] = $id;

    try {
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

  /**
   *
   * @param int $product_id
   * @param array $images
   * @return int
   */
  public function create_product_images($product_id, $images) {
    if (empty($images)) {
      return;
    }

    $count = 0;
    foreach ($images as $image) {
      try {
        $response = $this->create_product_image($product_id, $image);
        if ($response['status_code'] == 200) {
          $count++;
        }
      }
      catch (Exception $e) {
        //Do not throw exception because one failed image create can not interrupt with other image create
      }
    }

    $this->logger->log("Luotiin {$count} tuotekuvaa");

    return $count;
  }

  /**
   *
   * @param int $product_id
   * @param array $image
   * @return array
   * @throws Exception
   */
  public function create_product_image($product_id, $image) {
    $opt = array(
        'resource'   => 'products',
        'id'         => $product_id,
        'attachment' => $image,
        'method'     => 'POST'
    );
    try {
      $response = $this->ws->executeImageRequest($opt);

      $this->logger->log("Luotiin tuotekuva tuotteelle {$product_id}");
    }
    catch (Exception $e) {
      $msg = "Tuotteen: {$product_id} tuotekuvan luonti epäonnistui";
      $this->logger->log($msg, $e);
      throw $e;
    }

    return $response;
  }

  /**
   *
   * @param array $display
   * @return SimpleXMLElement
   * @throws Exception
   */
  public function all_products($display = array()) {
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

    try {
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
   * @param int $id
   * @return boolean
   * @throws Exception
   */
  public function delete_product($id) {
    $opt = array(
        'resource' => 'products',
        'id'       => $id,
    );

    try {
      $response = $this->ws->delete($opt);
    }
    catch (Exception $e) {
      $msg = "Tuotteen: {$id} poistaminen epäonnistui";
      $this->logger->log($msg, $e);
      throw $e;
    }

    return $response;
  }

  /**
   *
   * @param int $product_id
   * @return array
   * @throws Exception
   */
  public function get_product_images($product_id) {
    $image_ids = array();
    $opt = array(
        'resource' => 'images/products',
        'id'       => $product_id,
    );

    try {
      $response_xml = $this->ws->get($opt);
      foreach ($response_xml->image->declination as $node) {
        foreach ($node->attributes() as $key => $value) {
          if ($key == 'id') {
            $image_ids[] = (string) $value;
          }
        }
      }

      //For some reason API gives duplicate ids sometimes
      $image_ids = array_unique($image_ids);
    }
    catch (Exception $e) {
      $msg = "Tuotteen: {$product_id} tuotekuvien haku epäonnistui";
      $this->logger->log($msg, $e);
      throw $e;
    }

    return $image_ids;
  }

  /**
   *
   * @param int $product_id
   * @param array $image_ids
   * @return int
   */
  public function delete_product_images($product_id, $image_ids = array()) {
    $deleted = 0;

    try {
      if (empty($image_ids)) {
        $image_ids = $this->get_product_images($product_id);
      }

      foreach ($image_ids as $image_id) {
        $ok = $this->delete_product_image($product_id, $image_id);
        if ($ok) {
          $deleted++;
        }
      }
    }
    catch (Exception $e) {
      //If get_product_images throws an exception with status code 500 it means that there is no existing
      //product images.
      //@TODO For now we do not check for the status code but in future statuscodes other than 500
      //should retry get_product_images
      //Do not throw exception here
    }

    return $deleted;
  }

  /**
   *
   * @param int $product_id
   * @param int $image_id
   * @return boolean
   * @throws Exception
   */
  public function delete_product_image($product_id, $image_id) {
    $opt = array(
        'url' => "{$this->url}api/images/products/{$product_id}/{$image_id}",
    );

    try {
      $response = $this->ws->delete($opt);
    }
    catch (Exception $e) {
      $msg = "Tuotteen: {$product_id} tuotekuvan {$image_id} poistaminen epäonnistui";
      $this->logger->log($msg, $e);
      throw $e;
    }

    return $response;
  }

  /**
   *
   * @param array $product
   * @param SimpleXMLElement $existing_product
   * @return \SimpleXMLElement
   */
  private function generate_product_xml($product, SimpleXMLElement $existing_product = null) {
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

    $xml->product->link_rewrite->language[0] = preg_replace('/[^a-zA-Z0-9]/', '', $product['nimitys']);
    $xml->product->link_rewrite->language[1] = preg_replace('/[^a-zA-Z0-9]/', '', $product['nimitys']);
    $xml->product->name->language[0] = $product['nimitys'];
    $xml->product->name->language[1] = $product['nimitys'];

    return $xml;
  }

  /**
   * Fetch empty xml schema for given resource
   *
   * @param string $resource
   * @return SimpleXMLElement
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
      throw $e;
    }

    return $schema;
  }
}
