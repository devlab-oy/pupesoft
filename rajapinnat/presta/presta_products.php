<?php

require_once 'rajapinnat/presta/presta_client.php';
require_once 'rajapinnat/presta/presta_product_stocks.php';

class PrestaProducts extends PrestaClient {

  const RESOURCE = 'products';

  /**
   * Ohitettavien tuoteparametrien lista
   */

  private $presta_categories = null;
  private $presta_home_category_id = null;
  private $_removable_fields = array();

  public function __construct($url, $api_key, $presta_home_category_id) {
    $this->presta_categories = new PrestaCategories($url, $api_key, $presta_home_category_id);
    $this->presta_home_category_id = $presta_home_category_id;

    parent::__construct($url, $api_key);
  }

  protected function resource_name() {
    return self::RESOURCE;
  }

  /**
   *
   * @param array   $product
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
    $xml->product->ean13 = $product['ean'];

    $xml->product->price = $product['myyntihinta'];
    $xml->product->wholesale_price = $product['myyntihinta'];

    $xml->product->width  = str_replace(",", ".", $product['tuoteleveys']);
    $xml->product->height = str_replace(",", ".", $product['tuotekorkeus']);
    $xml->product->depth  = str_replace(",", ".", $product['tuotesyvyys']);
    $xml->product->weight = str_replace(",", ".", $product['tuotemassa']);

    $xml->product->available_for_order = 1;
    $xml->product->active = 1;
    $xml->product->show_price = 1;
    $xml->product->unit_price = 1;

    $link_rewrite = utf8_encode($this->saniteze_link_rewrite($product['nimi']));
    $xml->product->link_rewrite->language[0] = $link_rewrite;
    $xml->product->link_rewrite->language[1] = $link_rewrite;
    $xml->product->name->language[0] = utf8_encode($product['nimi']);
    $xml->product->name->language[1] = utf8_encode($product['nimi']);

    $xml->product->description = utf8_encode($product['kuvaus']);
    $xml->product->description_short = utf8_encode($product['lyhytkuvaus']);

    if (!empty($product['tuotepuun_tunnukset'])) {
      foreach ($product['tuotepuun_tunnukset'] as $pupesoft_category) {
        // Default category id is set inside for. This means that the last category is set default
        $category_id = $this->add_category($xml, $pupesoft_category);
      }

      $xml->product->id_category_default = $category_id;
    }

    $removables = $this->_removable_fields;
    if (isset($removables) and count($removables) > 0) {
      foreach ($removables as $element) {
        unset($xml->product->$element);
      }
    }

    return $xml;
  }

  /**
   *
   * @param SimpleXMLElement $xml
   * @param array   $ancestors
   * @return int
   */
  private function add_category(SimpleXMLElement &$xml, $category) {
    // fetch presta category with pupe tunnus
    $response = $this->presta_categories->find_category_by_tunnus($category);

    if ($response === false) {
      return null;
    }

    $category_id = $response->category->id;
    $category = $xml->product->associations->categories->addChild('category');
    $category->addChild('id');
    $category->id = $category_id;

    return $category_id;
  }

  /**
   *
   * @param array   $products
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
          }
          else {
            $response = $this->create($product);
            $id = (string) $response['product']['id'];
          }

          if (!empty($product['saldo'])) {
            $presta_stock = new PrestaProductStocks($this->url(), $this->api_key());
            $presta_stock->create_or_update($id, $product['saldo']);
          }
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
}
