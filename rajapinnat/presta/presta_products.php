<?php

require_once 'rajapinnat/presta/presta_categories.php';
require_once 'rajapinnat/presta/presta_client.php';
require_once 'rajapinnat/presta/presta_manufacturers.php';
require_once 'rajapinnat/presta/presta_product_feature_values.php';
require_once 'rajapinnat/presta/presta_product_features.php';

class PrestaProducts extends PrestaClient {
  private $_category_sync = true;
  private $_removable_fields = array();
  private $features_table = null;
  private $image_fetch = false;
  private $presta_all_products = null;
  private $presta_categories = null;
  private $presta_home_category_id = null;
  private $presta_manufacturers = null;
  private $presta_product_feature_values = null;
  private $presta_product_features = null;
  private $pupesoft_all_products = null;
  private $tax_rates_table = null;
  private $visibility_type = null;

  public function __construct($url, $api_key, $log_file) {
    $this->presta_categories = new PrestaCategories($url, $api_key, $log_file);
    $this->presta_manufacturers = new PrestaManufacturers($url, $api_key, $log_file);
    $this->presta_product_feature_values = new PrestaProductFeatureValues($url, $api_key, $log_file);
    $this->presta_product_features = new PrestaProductFeatures($url, $api_key, $log_file);

    parent::__construct($url, $api_key, $log_file);
  }

  protected function resource_name() {
    return 'products';
  }

  protected function remove_read_only_fields(SimpleXMLElement $xml) {
    unset($xml->product->manufacturer_name);
    unset($xml->product->quantity);
    unset($xml->product->position_in_category);

    return $xml;
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
    }

    $xml->product->reference = $this->xml_value($product['tuoteno']);
    $xml->product->supplier_reference = $this->xml_value($product['tuoteno']);

    $_ean = '';

    if (is_numeric($product['ean']) and strlen($product['ean']) < 14) {
      $_ean = $product['ean'];
    }
    elseif (!empty($product['ean'])) {
      $this->logger->log("Virheellinen EAN koodi '{$product['ean']}'");
    }

    $xml->product->ean13 = $_ean;

    $xml->product->price = $product['myyntihinta'];
    $xml->product->wholesale_price = $product['myyntihinta'];
    $xml->product->unity = $product['yksikko'];
    $xml->product->on_sale = 0; // 0 = no, 1 = yes

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
      $stock_row = $this->find_product_from_all_products($product['tuoteno']);
      $stock = (int) $stock_row['saldo'];

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
    $_nimi = empty($product['nimi']) ? '-' : $product['nimi'];

    // remove forbidden characters
    $_nimi = preg_replace("/[&]+/", "", $_nimi);

    // we must set these for all languages
    for ($i=0; $i < $languages; $i++) {
      $xml->product->name->language[$i]              = $this->xml_value($_nimi);
      $xml->product->description->language[$i]       = nl2br($this->xml_value($product['kuvaus']));
      $xml->product->description_short->language[$i] = nl2br($this->xml_value($product['lyhytkuvaus']));
      $xml->product->link_rewrite->language[$i]      = $this->saniteze_link_rewrite("{$product['tuoteno']}_{$product['nimi']}");
      $xml->product->available_later->language[$i]   = '';
    }

    // loop all translations and overwrite defaults
    foreach ($product['tuotteen_kaannokset'] as $translation) {
      $tr_id = $this->get_language_id($translation['kieli']);

      // if we don't have the language in presta
      if ($tr_id === null) {
        $this->logger->log("VIRHE! kielt‰ {$translation['kieli']} ei lˆydy Prestasta!");
        continue;
      }

      $value = $this->xml_value($translation['teksti']);

      // set translation to correct field
      $field = strtolower($translation['kentta']);

      switch ($field) {
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
      case 'tilaustuote':
        $xml->product->available_later->language[$tr_id] = $value;
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

      // t‰htituote means "on sale", so we need to enable it
      if (!empty($product['tahtituote'])) {
        $this->logger->log("T‰htituote, asetetaan 'on sale'");

        $xml->product->on_sale = 1; // 0 = no, 1 = yes
      }
    }

    // Assign dynamic product parameters
    $this->assign_dynamic_fields($xml->product, $product);

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
    if (count($product['tuotteen_lapsituotteet']) > 0) {
      $xml->product->associations->addChild('product_bundle');

      // Add child products for product bundle
      foreach ($product['tuotteen_lapsituotteet'] as $child_product) {
        $child_id = $this->add_child_product($xml, $child_product);

        // added the child successfully
        if ($child_id !== false) {
          // set parent product to pack
          $product_type = 'pack';
        }
      }
    }

    if ($product_type == "virtual") {
      $xml->product->is_virtual = 1;
    }

    // set product type
    $xml->product->type = $product_type;

    // First, remove all product features
    $remove_node = $xml->product->associations->product_features;
    $dom_node = dom_import_simplexml($remove_node);
    $dom_node->parentNode->removeChild($dom_node);

    // Then add element back
    $xml->product->associations->addChild('product_features');

    // Add product features
    foreach ($this->features_table as $field_name => $feature_id) {
      $value = $this->xml_value($product[$field_name]);
      $value_id = $this->presta_product_feature_values->add_by_value($feature_id, $value);

      if ($value_id != 0) {
        $feature = $xml->product->associations->product_features->addChild('product_features');
        $feature->id = $feature_id;
        $feature->id_feature_value = $value_id;

        $this->logger->log("Liitettiin ominaisuuteen {$feature_id} arvoksi {$value} ({$value_id})");
      }
    }

    $manufacturer_name = $product['tuotemerkki'];
    $manufacturer_id = $this->presta_manufacturers->add_manufacturer_by_name($manufacturer_name);

    $xml->product->id_manufacturer = $manufacturer_id;

    if ($manufacturer_id != 0) {
      $this->logger->log("Liitettiin valmistaja '{$manufacturer_name}' ({$manufacturer_id})");
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

    $category_id = $response['id'];
    $category = $xml->product->associations->categories->addChild('category');
    $category->addChild('id');
    $category->id = $category_id;

    $this->logger->log("Liitettiin tuote kategoriaan {$category_id}");

    return $category_id;
  }

  private function add_child_product(SimpleXMLElement &$xml, $product) {
    $qty        = $product['kerroin'];
    $sku        = $product['tuoteno'];
    $product_id = $this->product_id_by_sku($sku);

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

    // fetch the first shop group id, we will add all products to this shop group (for now)
    $shop_group_id = $this->shop_group_id();

    foreach ($products as $product) {
      $row_counter++;
      $this->logger->log("[{$row_counter}/{$total_counter}] Tuote {$product['tuoteno']}");

      try {
        // check do we have this product in this store
        $id = $this->product_id_by_sku($product['tuoteno']);

        if ($id !== false) {
          $this->update($id, $product, null, $shop_group_id);
        }
        else {
          $response = $this->create($product, null, $shop_group_id);
          $id = (string) $response['product']['id'];
        }
      }
      catch (Exception $e) {
        //Do nothing here. If create / update throws exception loggin happens inside those functions
        //Exception is not thrown because we still want to continue syncing for other products
      }

      // Set product activity per store
      $this->set_active_by_shop($id, $product['nakyvyys']);

      $this->logger->log("Tuote {$product['tuoteno']} k‰sitelty.\n");
    }

    $this->delete_all_unnecessary_products();

    $this->logger->log('---------Tuotteiden siirto valmis---------');
    return true;
  }

  public function fetch_and_save_images() {
    if ($this->image_fetch !== true) {
      return;
    }

    $this->logger->log('---------Aloitetaan tuotekuvien siirto---------');
    $this->logger->log('Haetaan kaikkien tuotteiden kuvatiedot');

    $all_product_images = $this->all_product_images();

    $this->logger->log('Haetaan kuvatiedostot Prestasta');

    $row_counter = 0;
    $total_counter = count($all_product_images);
    $all_presta_images = array();

    // loop all products
    foreach ($all_product_images as $product) {
      $row_counter++;
      $this->logger->log("[{$row_counter}/{$total_counter}] Tuote {$product['sku']}");

      // loop all product images
      foreach ($product['images'] as $image) {

        // collect all presta image ids to an array
        $all_presta_images[] = $image['id'];

        // do we have this image already
        if (presta_image_exists($product['sku'], $image['id'])) {
          $this->logger->log("Kuva {$image['id']} lˆytyy Pupesoftista");
        }
        else {
          $this->logger->log("Haetaan ja tallennetaan kuva {$image['href']}");

          // this requires changing PrestaShopWebservice executeRequest -method to public
          // otherwise we don't have any way to get a binary response from presta
          // without rewriting the whole curl call
          $url = "{$this->url}api/images/products/{$product['product_id']}/{$image['id']}";
          $response = $this->ws->executeRequest($url);

          // save file to tmp dir
          $temp_file = tempnam('/tmp', 'presta');
          file_put_contents($temp_file, $response['response']);

          // save image to pupesoft liitetiedostot
          $params = array(
            "filename" => $temp_file,
            "id"       => $image['id'],
            "sku"      => $product['sku'],
          );

          presta_tallenna_liite($params);

          // delete temp file
          unlink($temp_file);
        }
      }

      // remove all images, that are not in Presta
      $removed = presta_poista_ylimaaraiset_kuvat($product['sku'], $all_presta_images);

      $this->logger->log("Poistettiin {$removed} tuotekuvaa Pupesoftista.");

      $this->logger->log("Tuote {$product['sku']} k‰sitelty");
    }

    $this->logger->log('---------Tuotekuvien siirto valmis---------');
  }

  public function all_skus() {
    if ($this->presta_all_products !== null) {
      return $this->presta_all_products;
    }

    $this->logger->log('Haetaan kaikki tuotteet Prestashopista');

    $display = array('id', 'reference');
    $filter = array();
    $shop_group_id = $this->shop_group_id();

    $existing_products = $this->all($display, $filter, null, $shop_group_id);
    $existing_products = array_column($existing_products, 'reference', 'id');

    $this->presta_all_products = $existing_products;
    return $existing_products;
  }

  private function set_active_by_shop($product_id, $active_shop_ids_string) {
    // Set store ids.
    $verkkokauppa_nakyvyys = explode(" ", $active_shop_ids_string);
    $active_shop_ids = $this->set_shop_ids($verkkokauppa_nakyvyys);

    // If we get null, nakyvyys was invalid. Don't change visibility
    if (is_null($active_shop_ids)) {
      return;
    }

    // all shop ids
    $all_shop_ids = $this->all_shop_ids();

    foreach ($all_shop_ids as $id) {
      // if id is in active_shop_ids, we want it active
      // activity values: 0 off, 1 on
      $activity = in_array($id, $active_shop_ids) ? 1 : 0;

      $this->logger->log("Active {$activity} kauppaan {$id}");

      try {
        // fetch product, change activity and update
        $xml = $this->get_as_xml($product_id, $id);
        $xml->product->active = $activity;
        $this->update_xml($product_id, $xml, $id);
      }
      catch (Exception $e) {
      }
    }
  }

  private function get_tax_group_id($vat) {
    $vat = round($vat, 2);
    $value = isset($this->tax_rates_table[$vat]) ? $this->tax_rates_table[$vat] : null;

    if (empty($value)) {
      return null;
    }
    else {
      return $value;
    }
  }

  private function delete_all_unnecessary_products() {
    $pupesoft_products = $this->pupesoft_all_products;

    if ($pupesoft_products === null or count($pupesoft_products) == 0) {
      $this->logger->log("pupesoft_all_products not set, can't delete!");
      return;
    }

    $presta_products = $this->all_skus();
    $keep_presta_ids = array();
    $all_presta_ids = array();

    foreach ($pupesoft_products as $product_row) {
      $product = $product_row['tuoteno'];

      // do we have this product in presta
      $presta_id = $this->product_id_by_sku($product);

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

    $total = count($delete_presta_ids);
    $current = 0;

    // delete products from presta
    foreach ($delete_presta_ids as $presta_id) {
      $current++;
      $this->logger->log("[{$current}/{$total}] Poistetaan tuote {$presta_id}");

      try {
        $this->delete($presta_id);
      }
      catch (Exception $e) {
      }
    }
  }

  private function find_product_from_all_products($sku) {
    foreach ($this->pupesoft_all_products as $product_row) {
      if ($sku == $product_row['tuoteno']) {
        return $product_row;
      }
    }

    return false;
  }

  private function all_product_images() {
    $all_product_images = array();
    $all_skus = $this->all_skus();

    $row_counter = 0;
    $total_counter = count($all_skus);

    // loop all products
    foreach ($all_skus as $sku) {
      $row_counter++;
      $this->logger->log("[{$row_counter}/{$total_counter}] Tuote {$sku}");

      // fetch product as xml, because we need to preserve the attributes
      $product_id = $this->product_id_by_sku($sku);
      $product = $this->get_as_xml($product_id);

      // product images are under associations, sometimes singular, sometimes plural
      $xml_images = $product->product->associations->images;
      if (isset($xml_images->image)) {
        $images = $xml_images->image;
      }
      elseif (isset($xml_images->images)) {
        $images = $xml_images->images;
      }
      else {
        $images = array();
      }

      $product_images = array();

      // loop images
      foreach ($images as $image) {
        $image_id = (int) $image->id;
        $attributes = $image->attributes("http://www.w3.org/1999/xlink");
        $image_href = (string) $attributes['href'];

        // collect products images urls
        $product_images[] = array(
          "href" => $image_href,
          "id" => $image_id,
        );
      }

      // add products images to an array
      $all_product_images[] = array(
        "images" => $product_images,
        "product_id" => $product_id,
        "sku" => $sku,
      );
    }

    return $all_product_images;
  }

  public function product_id_by_sku($sku) {
    // lowercase all Presta SKU:s and the search term
    $all_products = array_map('strtolower', $this->all_skus());
    $search = strtolower($sku);

    $product_id = array_search($search, $all_products);

    return $product_id;
  }

  public function set_removable_fields($fields) {
    $this->_removable_fields = $fields;
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

  public function set_visibility_type($value) {
    $this->visibility_type = $value;
  }

  public function set_product_features($value) {
    if (is_array($value)) {
      $this->features_table = $value;
    }
  }

  public function set_home_category_id($value) {
    $this->presta_home_category_id = $value;
  }

  public function set_image_fetch($value) {
    $this->image_fetch = $value;
  }
}
