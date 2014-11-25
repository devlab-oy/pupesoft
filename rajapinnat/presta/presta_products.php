<?php

require_once 'rajapinnat/presta/presta_client.php';

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
   * @return boolean
   */
  public function sync_products(array $products) {
    $this->logger->log('---------Start product sync---------');

    try {
      $this->schema = $this->get_empty_schema($this->resource_name());
      //Fetch all products with ID's and SKU's only
      $existing_products = $this->all(array('id', 'reference'));
      $existing_products = $existing_products['products']['product'];
      $existing_products = array_column($existing_products, 'reference', 'id');

      foreach ($products as $product) {
        try {
          if (in_array($product['tuoteno'], $existing_products)) {
            $id = array_search($product['tuoteno'], $existing_products);
            $response = $this->update($id, $product);
            $this->delete_product_images($id);
          }
          else {
            $response = $this->create($product);
            $id = (string) $response['product']['id'];
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
      //Exception logging happens in create / update.
      
      return false;
    }

    $this->logger->log('---------End product sync---------');
    return true;
  }

  /**
   *
   * @param int $product_id
   * @param array $images
   * @return int
   */
  protected function create_product_images($product_id, $images) {
    if (empty($images)) {
      return;
    }

    $count = 0;
    foreach ($images as $image) {
      try {
        $response = $this->create_resource_image($product_id, $image);
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
   * @param array $image_ids If empty delete all
   * @return int
   */
  protected function delete_product_images($product_id, $image_ids = array()) {
    $deleted = 0;

    try {
      if (empty($image_ids)) {
        $image_ids = $this->get_resource_images($product_id);
      }

      foreach ($image_ids as $image_id) {
        $ok = $this->delete_resource_image($product_id, $image_id);
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
}
