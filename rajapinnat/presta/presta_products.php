<?php

require_once 'rajapinnat/presta/presta_client.php';
require_once 'rajapinnat/presta/presta_manufacturers.php';
require_once 'rajapinnat/presta/presta_product_feature_values.php';
require_once 'rajapinnat/presta/presta_product_features.php';
require_once 'rajapinnat/presta/presta_product_stocks.php';

class PrestaProducts extends PrestaClient {
  private $_category_sync = true;
  private $_dynamic_fields = array();
  private $_removable_fields = array();
  private $features_table = null;
  private $languages_table = null;
  private $presta_all_products = null;
  private $presta_categories = null;
  private $presta_home_category_id = null;
  private $presta_manufacturers = null;
  private $presta_product_feature_values = null;
  private $presta_product_features = null;
  private $presta_stock = null;
  private $pupesoft_all_products = null;
  private $tax_rates_table = null;
  private $visibility_type = null;

  public function __construct($url, $api_key, $presta_home_category_id) {
    $this->presta_categories = new PrestaCategories($url, $api_key, $presta_home_category_id);
    $this->presta_home_category_id = $presta_home_category_id;

    $this->presta_manufacturers = new PrestaManufacturers($url, $api_key);
    $this->presta_product_feature_values = new PrestaProductFeatureValues($url, $api_key);
    $this->presta_product_features = new PrestaProductFeatures($url, $api_key);
    $this->presta_stock = new PrestaProductStocks($url, $api_key);

    parent::__construct($url, $api_key);
  }

  protected function resource_name() {
    return 'products';
  }

  /**
   *
   * @param array   $product
   * @param SimpleXMLElement $existing_product
   * @return \SimpleXMLElement
   */
  protected function generate_xml($product, SimpleXMLElement $existing_product = null) {
    if (is_null($existing_product)) {
      $xml = $this->empty_xml();
    }
    else {
      $xml = $existing_product;

      unset($xml->product->position_in_category);
      unset($xml->product->manufacturer_name);
      unset($xml->product->quantity);
    }

    unset($xml->product->position_in_category);

    $xml->product->reference = utf8_encode($product['tuoteno']);
    $xml->product->supplier_reference = utf8_encode($product['tuoteno']);
    $xml->product->ean13 = is_numeric($product['ean']) ? $product['ean'] : '';

    $xml->product->price = $product['myyntihinta'];
    $xml->product->wholesale_price = $product['myyntihinta'];
    $xml->product->unity = $product['yksikko'];

    // TODO: unit_price_ratio does nothing. Presta just ignores this field and we cannot set unit price.
    // find another way to se unit price? or do wait for presta to fix?
    $xml->product->unit_price_ratio = 1; // unit price is same as price

    // by default product is visible everywhere, and active
    // visibility values: both, catalog, search, none
    // activity values: 0 off, 1 on
    $visibility = 'both';
    $active = 1;

    // if we are moving all products to presta, hide the product if we don't want to show it
    if ($this->visibility_type == 2) {
      // we have stock value in pupesoft_all_products
      $stock = $this->pupesoft_all_products[$product['tuoteno']];

      if (empty($product['nakyvyys'])) {
        $this->logger->log("N‰kyvyys tyhj‰‰, ei n‰ytet‰ verkkokaupassa.");
        $visibility = 'none';
        $active = 0;
      }
      elseif ($product['status'] == 'P' and $stock <= 0) {
        $this->logger->log("Status P ja saldo <= 0, ei n‰ytet‰ verkkokaupassa.");
        $visibility = 'none';
        $active = 0;
      }
    }
    else {
      $this->logger->log("Tuote aktiivinen ja n‰ytet‰‰n verkkokaupassa.");
    }

    $xml->product->active = $active;
    $xml->product->visibility = $visibility;

    $xml->product->id_tax_rules_group = $this->get_tax_group_id($product["alv"]);

    $xml->product->width  = str_replace(",", ".", $product['tuoteleveys']);
    $xml->product->height = str_replace(",", ".", $product['tuotekorkeus']);
    $xml->product->depth  = str_replace(",", ".", $product['tuotesyvyys']);
    $xml->product->weight = str_replace(",", ".", $product['tuotemassa']);

    $xml->product->available_for_order = 1;
    $xml->product->show_price = 1;

    // Set default value from Pupesoft to all languages
    $languages = count($xml->product->name->language);

    // we must set these for all languages
    for ($i=0; $i < $languages; $i++) {
      $xml->product->name->language[$i]              = empty($product['nimi']) ? '-' : utf8_encode($product['nimi']);
      $xml->product->description->language[$i]       = utf8_encode($product['kuvaus']);
      $xml->product->description_short->language[$i] = utf8_encode($product['lyhytkuvaus']);
      $xml->product->link_rewrite->language[$i]      = $this->saniteze_link_rewrite("{$product['tuoteno']}_{$product['nimi']}");
    }

    // loop all translations and overwrite defaults
    foreach ($product['tuotteen_kaannokset'] as $translation) {
      $tr_id = $this->get_language_id($translation['kieli']);

      // if we don't have the language in presta
      if ($tr_id === null) {
        $this->logger->log("VIRHE! kielt‰ {$translation['kieli']} ei lˆydy Prestasta!");
        continue;
      }

      $value = utf8_encode($translation['teksti']);

      // set translation to correct field
      switch ($translation['kentta']) {
        case 'nimitys':
          $xml->product->name->language[$tr_id] = $value;
          $xml->product->link_rewrite->language[$tr_id] = $this->saniteze_link_rewrite("{$product['tuoteno']}_{$value}");
          break;
        case 'kuvaus':
          $xml->product->description->language[$tr_id] = $value;
          break;
        case 'lyhytkuvaus':
          $xml->product->description_short->language[$tr_id] = $value;
          break;
      }

      $this->logger->log("K‰‰nnˆs {$translation['kieli']}, {$translation['kentta']}: $value");
    }

    if ($this->_category_sync) {
      // First, remove all categories from XML
      $remove_node = $xml->product->associations->categories;
      $dom_node = dom_import_simplexml($remove_node);
      $dom_node->parentNode->removeChild($dom_node);

      // Then add them back
      $xml->product->associations->addChild('categories');

      $category_id = '';

      foreach ($product['tuotepuun_tunnukset'] as $pupesoft_category) {
        // Default category id is set inside loop, so the last category is set as default
        $category_id = $this->add_category($xml, $pupesoft_category);
      }

      $xml->product->id_category_default = $category_id;
    }

    // Dynamic product parameters
    $product_parameters = $this->_dynamic_fields;

    if (isset($product_parameters) and count($product_parameters) > 0) {
      foreach ($product_parameters as $parameter) {
        $_key = $parameter['arvo'];
        $_attribute = $parameter['nimi'];
        $_value = utf8_encode($product[$_key]);

        $this->logger->log("Poikkeava arvo product.{$_attribute} -kentt‰‰n. Asetetaan {$_key} kent‰n arvo {$_value}");

        $xml->product->$_attribute = $_value;
      }
    }

    // Removed product parameters
    $removables = $this->_removable_fields;

    if (isset($removables) and count($removables) > 0) {
      foreach ($removables as $element) {
        unset($xml->product->$element);
      }
    }

    // Product type, default to simple
    // Values: simple, pack, virtual
    $product_type = empty($product['ei_saldoa']) ? 'simple' : 'virtual';

    // First, remove all old child products
    $remove_node = $xml->product->associations->product_bundle;
    $dom_node = dom_import_simplexml($remove_node);
    $dom_node->parentNode->removeChild($dom_node);

    // Then add element back
    $xml->product->associations->addChild('product_bundle');

    // Calculated parent price
    $parent_price = 0;

    // Add child products for product bundle
    foreach ($product['tuotteen_lapsituotteet'] as $child_product) {
      $child_id = $this->add_child_product($xml, $child_product);

      // added the child successfully
      if ($child_id !== false) {
        // set parent product to pack
        $product_type = 'pack';

        // we must fetch child from presta
        $child = $this->get($child_id);

        // calculate parent price
        $parent_price += ($child['price'] * $child_product['kerroin'] * $child_product['hintakerroin']);
      }
    }

    // set product type
    $xml->product->type = $product_type;

    // if it's a pack, update parent price
    if ($product_type == 'pack') {
      $this->logger->log("Asetettiin tuottelle hinta hinta {$parent_price}, joka laskettiin lapsituotteiden hinnoista.");
      $xml->product->price = $parent_price;
      $xml->product->wholesale_price = $parent_price;
    }

    // First, remove all product features
    $remove_node = $xml->product->associations->product_features;
    $dom_node = dom_import_simplexml($remove_node);
    $dom_node->parentNode->removeChild($dom_node);

    // Then add element back
    $xml->product->associations->addChild('product_features');

    // Add product features
    foreach ($this->features_table as $field_name => $feature_id) {
      $value = trim($product[$field_name]);

      // if we don't have a value, don't add anything.
      if (empty($value)) {
        continue;
      }

      $value_id = $this->presta_product_feature_values->value_id_by_value($value);

      if (empty($value_id)) {
        $feature_value = array(
          "id_feature" => $feature_id,
          "value" => $value,
        );

        // Create feature value
        $response = $this->presta_product_feature_values->create($feature_value);
        $value_id = $response['product_feature_value']['id'];

        // nollataan all values array, jotta se haetaan uusiksi prestasta, niin ei perusteta samaa arvoa monta kertaa
        $this->presta_product_feature_values->reset_all_values();
        $this->logger->log("Perustettiin ominaisuuden arvo '{$value}' ({$value_id})");
      }

      $feature = $xml->product->associations->product_features->addChild('product_features');
      $feature->id = $feature_id;
      $feature->id_feature_value = $value_id;

      $this->logger->log("Lis‰ttiin ominaisuuteen {$feature_id} arvoksi {$value} ({$value_id})");
    }

    $manufacturer_name = $product['tuotemerkki'];

    $xml->product->id_manufacturer = 0;

    // add manufacturer
    if (!empty($manufacturer_name)) {
      $manufacturer_id = $this->presta_manufacturers->manufacturer_id_by_name($manufacturer_name);

      if (empty($manufacturer_id)) {
        $manufacturer = array(
          "name" => $manufacturer_name,
        );

        // Create manufacturer
        $response = $this->presta_manufacturers->create($manufacturer);
        $manufacturer_id = $response['manufacturer']['id'];

        // nollataan array, haetaan uusiksi prestasta, ett‰ ei perusteta samaa monta kertaa
        $this->presta_manufacturers->reset_all_records();
        $this->logger->log("Perustettiin valmistaja '{$manufacturer_name}' ({$manufacturer_id})");
      }

      $xml->product->id_manufacturer = $manufacturer_id;
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

    $this->logger->log("Liitettiin tuote kategoriaan {$category_id}");

    return $category_id;
  }

  private function add_child_product(SimpleXMLElement &$xml, $product) {
    $discount   = $product['alekerroin'];
    $price      = $product['hintakerroin'];
    $qty        = $product['kerroin'];
    $sku        = $product['tuoteno'];
    $product_id = array_search($sku, $this->all_skus());

    if ($product_id === false) {
      $this->logger->log("VIRHE! Lapsituotetta {$sku} ei lˆytynyt!");
      return false;
    }

    $product = $xml->product->associations->product_bundle->addChild('product');
    $product->addChild('id');
    $product->id = $product_id;
    $product->addChild('quantity');
    $product->quantity = $qty;

    $this->logger->log("Lis‰ttiin lapsituote {$sku} ({$product_id})");

    // return the id of the child product
    return $product_id;
  }

  /**
   *
   * @param array   $products
   * @return boolean
   */
  public function sync_products(array $products) {
    $this->logger->log('---------Aloitetaan tuotteiden siirto---------');

    $row_counter = 0;
    $total_counter = count($products);

    try {
      $existing_products = $this->all_skus();

      foreach ($products as $product) {
        $row_counter++;
        $this->logger->log("[{$row_counter}/{$total_counter}] Tuote {$product['tuoteno']}");

        try {
          if (in_array($product['tuoteno'], $existing_products)) {
            $id = array_search($product['tuoteno'], $existing_products);
            $this->update($id, $product);
          }
          else {
            $this->create($product);
          }
        }
        catch (Exception $e) {
          //Do nothing here. If create / update throws exception loggin happens inside those functions
          //Exception is not thrown because we still want to continue syncing for other products
        }

        $this->logger->log("Tuote {$product['tuoteno']} k‰sitelty.\n");
      }
    }
    catch (Exception $e) {
      //Exception logging happens in create / update.

      return false;
    }

    $this->delete_all_unnecessary_products();
    $this->update_stock();

    $this->logger->log('---------Tuotteiden siirto valmis---------');
    return true;
  }

  public function all_skus() {
    if ($this->presta_all_products !== null) {
      return $this->presta_all_products;
    }

    $this->logger->log('Haetaan kaikki tuotteet Prestashopista');

    $existing_products = $this->all(array('id', 'reference'));
    $existing_products = array_column($existing_products, 'reference', 'id');

    $this->presta_all_products = $existing_products;
    return $existing_products;
  }

  private function get_tax_group_id($vat) {
    $vat = round($vat, 2);
    $value = $this->tax_rates_table[$vat];

    if (empty($value)) {
      return null;
    }
    else {
      return $value;
    }
  }

  private function get_language_id($code) {
    $value = $this->languages_table[$code];

    if (empty($value)) {
      return null;
    }
    else {
      // substract one, since API key starts from zero
      return ($value - 1);
    }
  }

  private function delete_all_unnecessary_products() {
    // pupesoft_all_products has SKU as the array key, we need an array of SKUs.
    $pupesoft_products = array_keys($this->pupesoft_all_products);

    if ($pupesoft_products === null or count($pupesoft_products) == 0) {
      $this->logger->log("pupesoft_all_products not set, can't delete!");
      return;
    }

    $presta_products = $this->all_skus();
    $keep_presta_ids = array();
    $all_presta_ids = array();

    foreach ($pupesoft_products as $product) {
      // do we have this product in presta
      $presta_id = array_search($product, $presta_products);

      // if we found product from presta, add presta id to array
      if ($presta_id !== false) {
        $keep_presta_ids[] = $presta_id;
      }
    }

    foreach ($presta_products as $key => $value) {
      $all_presta_ids[] = $key;
    }

    // compare all_presta_ids against keep_presta_ids
    // return the values that are not present keep_presta_ids
    $delete_presta_ids = array_diff($all_presta_ids, $keep_presta_ids);

    // delete products from presta
    foreach ($delete_presta_ids as $presta_id) {
      try {
        $this->delete($presta_id);
      }
      catch (Exception $e) {
      }
    }
  }

  private function update_stock() {
    $this->logger->log('---------Aloitetaan saldojen p‰ivitys---------');

    // set all products null, so we'll fetch all_skus again from presta
    $this->presta_all_products = null;
    $pupesoft_products = $this->pupesoft_all_products;

    $current = 0;
    $total = count($pupesoft_products);

    foreach ($pupesoft_products as $sku => $stock) {
      $product_id = array_search($sku, $this->all_skus());

      $current++;
      $this->logger->log("[{$current}/{$total}] tuote {$sku} ({$product_id}) saldo {$stock}");

      // could not find product or
      // this is a virtual product, no stock management
      if ($product_id === false or $stock === null) {
        continue;
      }

      $this->presta_stock->create_or_update($product_id, $stock);
    }

    $this->logger->log('---------Saldojen p‰ivitys valmis---------');
  }

  public function set_removable_fields($fields) {
    $this->_removable_fields = $fields;
  }

  public function set_dynamic_fields($fields) {
    $this->_dynamic_fields = $fields;
  }

  public function set_category_sync($value) {
    if ($value === false) {
      $this->_category_sync = false;
    }
  }

  public function set_all_products($value) {
    if (is_array($value)) {
      $this->pupesoft_all_products = $value;
    }
  }

  public function set_tax_rates_table($value) {
    if (is_array($value)) {
      $this->tax_rates_table = $value;
    }
  }

  public function set_languages_table($value) {
    if (is_array($value)) {
      $this->languages_table = $value;
    }
  }

  public function set_visibility_type($value) {
    $this->visibility_type = $value;
  }

  public function set_product_features($value) {
    if (is_array($value)) {
      $this->features_table = $value;
    }
  }
}
