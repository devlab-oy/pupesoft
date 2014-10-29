<?php

require_once './presta_client.php';

class PrestaProducts extends PrestaClient {

  public function __construct($url, $api_key) {
    parent::__construct($url, $api_key);
  }

  protected function resource_name() {
    return 'products';
  }

  /**
   *
   * @param array $product
   * @param SimpleXMLElement $existing_product
   * @return \SimpleXMLElement
   */
  protected function generate_xml($product, SimpleXMLElement $existing_product = null) {
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

    $xml->product->link_rewrite->language[0] = preg_replace('/[^a-zA-Z0-9]/', '', $product['nimi']);
    $xml->product->link_rewrite->language[1] = preg_replace('/[^a-zA-Z0-9]/', '', $product['nimi']);
    $xml->product->name->language[0] = $product['nimi'];
    $xml->product->name->language[1] = $product['nimi'];

    return $xml;
  }

  /**
   *
   * @param array $products
   * @return \SimpleXMLElement
   */
  public function sync_products(array $products) {
    $this->logger->log('---------Start product sync---------');

    try {
      $this->schema = $this->get_empty_schema($this->resource_name());
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
   * @param int $id
   * @return SimpleXMLElement
   * @throws Exception
   */
  public function get_product($id) {
    try {
      $response = $this->get('products', $id);
    }
    catch (Exception $e) {
      throw $e;
    }

    return $response;
  }

  /**
   *
   * @param array $product
   * @return SimpleXMLElement
   * @throws Exception
   */
  public function create_product($product) {
    try {
      $response_xml = $this->create($product);
    }
    catch (Exception $e) {
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
    try {
      $response_xml = $this->update($id, $product);
    }
    catch (Exception $e) {
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
    try {
      $response = $this->create_resource_image($product_id, $image);
    }
    catch (Exception $e) {
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
    try {
      $response = $this->all($display);
    }
    catch (Exception $e) {
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
    try {
      $response = $this->delete($id);
    }
    catch (Exception $e) {
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
    try {
      $image_ids = $this->get_resource_images($product_id);
    }
    catch (Exception $e) {
      throw $e;
    }

    return $image_ids;
  }

  /**
   *
   * @param int $product_id
   * @param array $image_ids If empty delete all
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
    try {
      $response = $this->delete_resource_image($product_id, $image_id);
    }
    catch (Exception $e) {
      throw $e;
    }

    return $response;
  }
}
