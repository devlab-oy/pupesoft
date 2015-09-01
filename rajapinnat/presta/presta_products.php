<?php

require_once 'rajapinnat/presta/presta_client.php';
require_once 'rajapinnat/presta/presta_product_stocks.php';

class PrestaProducts extends PrestaClient {

  const RESOURCE = 'products';

  /**
   * Ohitettavien tuoteparametrien lista
   */
  private $_removable_fields = array();

  public function __construct($url, $api_key) {
    parent::__construct($url, $api_key);
  }

  protected function resource_name() {
    return self::RESOURCE;
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

    unset($xml->product->position_in_category);

    $xml->product->reference = $product['tuoteno'];
    $xml->product->supplier_reference = $product['tuoteno'];

    $xml->product->price = $product['myyntihinta'];
    $xml->product->wholesale_price = $product['myyntihinta'];

    $xml->product->width  = $product['tuoteleveys'];
    $xml->product->height = $product['tuotekorkeus'];
    $xml->product->depth  = $product['tuotesyvyys'];
    $xml->product->weight = $product['tuotemassa'];

    $xml->product->description = $product['kuvaus'];
    $xml->product->description_short = $product['lyhytkuvaus'];

    $xml->product->available_for_order = 1;
    $xml->product->active = 1;

    $link_rewrite = $this->saniteze_link_rewrite($product['nimi']);
    $xml->product->link_rewrite->language[0] = $link_rewrite;
    $xml->product->link_rewrite->language[1] = $link_rewrite;
    $xml->product->name->language[0] = $product['nimi'];
    $xml->product->name->language[1] = $product['nimi'];

    if (!empty($product['tuotepuun_nodet'])) {
      foreach ($product['tuotepuun_nodet'] as $category_ancestors) {
        //Default category id is set inside for. This means that the last category is set default
        $default_category_id = $this->add_category($xml, $category_ancestors);
      }

      $xml->product->id_category_default = $default_category_id;
    }

    $removables = $this->_removable_fields;
    if (isset($removables) and count($removables) > 0) {
      foreach($removables as $element) {
        unset($xml->product->$element);
      }
    }

    return $xml;
  }

  /**
   *
   * @param SimpleXMLElement $xml
   * @param array $ancestors
   * @return int
   */
  private function add_category(SimpleXMLElement &$xml, $ancestors) {
    $presta_categories = new PrestaCategories($this->url(), $this->api_key());
    $category_id = $presta_categories->find_category($ancestors);
    if (!is_null($category_id)) {
      $category = $xml->product->associations->categories->addChild('category');
      $category->addChild('id');
      $category->id = $category_id;
    }

    return $category_id;
  }

  /**
   *
   * @param array $products
   * @return boolean
   */
  public function sync_products(array $products) {
    $this->logger->log('---------Start product sync---------');

    try {
      $this->schema = $this->get_empty_schema();
      $existing_products = $this->all_skus();

      foreach ($products as $product) {
        //@TODO tee while looppi ja catchissa tsekkaa $counter >= 10 niin break;
        try {
          if (in_array($product['tuoteno'], $existing_products)) {
            $id = array_search($product['tuoteno'], $existing_products);
            $response = $this->update($id, $product);
            // TEMP disabloidaan tämä kuvakonversioon asti
            #$this->delete_product_images($id);
          }
          else {
            $response = $this->create($product);
            $id = (string) $response['product']['id'];
          }

          if (!empty($product['saldo'])) {
            $presta_stock = new PrestaProductStocks($this->url(), $this->api_key());
            $presta_stock->create_or_update($id, $product['saldo']);
          }
          // TEMP disabloidaan tämä kuvakonversioon asti
          #$this->create_product_images($id, $product['images']);
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

  public function all_skus() {
    $existing_products = $this->all(array('id', 'reference'));
    $existing_products = array_column($existing_products, 'reference', 'id');

    return $existing_products;
  }

  public function set_removable_fields($fields) {
    $this->_removable_fields = $fields;
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
