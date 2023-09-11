<?php

if (php_sapi_name() != 'cli') {
  die();
}

$pupe_root_polku = dirname(dirname(dirname(__FILE__)));

ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $pupe_root_polku);
error_reporting(E_ALL ^ E_DEPRECATED);
ini_set('display_errors', 1);
ini_set('max_execution_time', 0);
ini_set('memory_limit', '2G');

date_default_timezone_set('Europe/Helsinki');

require 'inc/connect.inc';
require 'inc/functions.inc';
require_once './vendor/autoload.php';
require_once "../edi.php";

if (!isset($argv[1]) || !$argv[1]) {
  exit;
}
if (!isset($argv[2]) || !$argv[2]) {
  exit;
}

$yhtiorow = hae_yhtion_parametrit(pupesoft_cleanstring($argv[1]));
if (!$yhtiorow) {
  exit;
}

if (!isset($presta_varastot)) {
  $presta_varastot = array(0);
}

$resource = pupesoft_cleanstring($argv[2]);
$days = pupesoft_cleanstring($argv[3]);

/*
  Main class
*/
class Presta17RestApi
{
  /*
  Default variables and values in the class
  */
  public function __construct($yhtiorow, $rest, $url, $presta_varastot, $edi, $presta17_api_customer, $presta17_api_edipath, $presta17_api_payment_rule, $presta17_api_ovt)
  {
    $php_cli = true;
    $this->kukarow = hae_kukarow('admin', $yhtiorow['yhtio']);
    $this->yhtiorow = $yhtiorow;
    $this->foundCategories = array();
    $this->rest = $rest;
    $this->url = $url;
    $this->presta17_api_payment_rule = $presta17_api_payment_rule;
    $this->warehouses = $presta_varastot;
    $this->presta17_api_customer = $presta17_api_customer;
    $this->presta17_api_edipath = $presta17_api_edipath;
    $this->edi = $edi;
    $this->presta17_api_ovt = $presta17_api_ovt;
    $this->fi_countries = Array(
      'Suomi' => 'Finland',
      'Ruotsi' => 'Sweden',
      'Viro' => 'Estonia',
      'Venäjä' => 'Russian Federation',
      'Saksa' => 'Germany'
    );
  } 

  public function getPupesoftProducts($days)
  {
    $yhtio = $this->yhtiorow['yhtio'];
    $query = "SELECT * from tuote 
                JOIN puun_alkio on (tuote.yhtio =  puun_alkio.yhtio and tuote.tuoteno = puun_alkio.liitos) 
                JOIN dynaaminen_puu on (puun_alkio.yhtio = dynaaminen_puu.yhtio and puun_alkio.puun_tunnus = dynaaminen_puu.tunnus) 
                where tuote.yhtio='$yhtio' and tuote.nakyvyys = 1
                and (
                (tuote.muutospvm between date_sub(now(),INTERVAL '$days' DAY) and now()) 
                or 
                (puun_alkio.muutospvm between date_sub(now(),INTERVAL '$days' DAY) and now())
                ) 
              ";

    $products = pupe_query($query);
    $results = array();

    while ($product = mysql_fetch_assoc($products)) {
      $osastores = t_avainsana('OSASTO', '', "and avainsana.selite ='$product[osasto]'", "'$yhtio'");
      $osastorow = mysql_fetch_assoc($osastores);

      if ($osastorow['selitetark'] != '') {
        $product['osasto_nimike'] = $osastorow['selitetark'];
      }

      $tryres = t_avainsana('TRY', '', "and avainsana.selite ='$product[try]'", "'$yhtio'");
      $tryrow = mysql_fetch_assoc($tryres);

      if ($tryrow['selitetark'] != '') {
        $product['try_nimike'] = $tryrow['selitetark'];
      }

      $results[$product['tuoteno']][] = $product;
    }

    return
    Array(
      'products' => $results,
      'ids' => array_keys($results)
    );
  }

  public function getPrestashopProducts($pupesoft_products)
  {
    $pupesoft_products_ids = $pupesoft_products['ids'];
    $prestashop_products = array();
    $missing_products = array();

    foreach ($pupesoft_products_ids as $pupesoft_products_id) {

      $presta_products_opt = Array(
        'resource' => 'products',
        'filter[reference]' => '[' . $pupesoft_products_id . ']'
      );

      $prestashop_products_chunk = $this->rest->get($presta_products_opt);
      if ($prestashop_products_chunk->products->children()) {
        foreach ($prestashop_products_chunk->products as $found_product) {
          $prestashop_products[$pupesoft_products_id] = $found_product->product->attributes()->id->__toString();
        }
      } else {
        $missing_products[$pupesoft_products_id] = $pupesoft_products['products'][$pupesoft_products_id];
      }
    }

    return Array(
      'found' => $prestashop_products,
      'missing' => $missing_products
    );
  }

  public function setStocks($id, $qty) {
    if(!$qty) {
      $qty = 0;
    }
    $qty = round($qty, 0, PHP_ROUND_HALF_DOWN);
    $stocks = Array(
      'resource' => 'stock_availables',
      'filter[id_product]' => '[' . $id . ']',
      'filter[quantity]' => '[' . $qty . ']',
      'filter[id_product_attribute]' => '[]',
      'limit' => 1
    );
    $stocks_check = $this->rest->get($stocks);
    $checker = $stocks_check->stock_availables->children();
    if (!empty($checker)) {
      return true;
    }

    $stocks = Array(
      'resource' => 'stock_availables',
      'filter[id_product]' => '[' . $id . ']',
      'filter[id_product_attribute]' => '[]',
      'limit' => 1,
    );
    $stocks_check = $this->rest->get($stocks);
    $checker = $stocks_check->stock_availables->children();
    if (!empty($checker)) {
      $xml = $this->rest->get(Array(
        'resource' => 'stock_availables',
        'id' =>  $stocks_check->stock_availables->stock_available->attributes()->id->__toString(),
      ));
      if ($stock_availableFields = $xml->stock_available->children()) {
        $stock_availableFields->out_of_stock = 0;
        $stock_availableFields->quantity = $qty;
        $updatedXml = $this->rest->edit(Array(
          'resource' => 'stock_availables',
          'id' => (int) $stock_availableFields->id->__toString(),
          'putXml' => $xml->asXML(),
        )); 
        return true;
      }
    }

    return false;
    
  }

  public function setPrestashopManufacturer($manufacturer)
  {
    $blankXml = $this->rest->get(Array('url' => $this->url . 'api/manufacturers?schema=synopsis'));
    $manufacturerFields = $blankXml->manufacturer->children();
    $manufacturerFields->name = (string) $manufacturer;
    $manufacturerFields->active = 1;
    unset($manufacturerFields->link_rewrite);
    $manufacturers = Array(
      'resource' => 'manufacturers',
      'postXml' => $blankXml->asXML(),
    );
    $createdXml = $this->rest->add($manufacturers);
    if ($this->errors_found('manufacturer:' . $manufacturer, $createdXml)) {
      return;
    }
    $newManufacturerFields = $createdXml->manufacturer->children();

    return $newManufacturerFields->id;
  }

  public static function slugify($text, $divider = '-')
  {
    $text = preg_replace('~[^\pL\d]+~u', $divider, $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, $divider);
    $text = preg_replace('~-+~', $divider, $text);
    $text = mb_strtolower($text);
    if (empty($text)) {
      return 'n-a';
    }
    return $text;
  }

  public function getPrestashopProductFeature($product_feature)
  {
    $product_feature_name = $product_feature;
    $product_features = Array(
      'resource' => 'product_features',
      'filter[name]' => '[' . $product_feature . ']'
    );
    $product_feature = $this->rest->get($product_features);
    $checker = $product_feature->product_features->children();
    if (empty($checker)) {
      $product_feature_id = $this->setPrestashopProductFeature($product_feature_name);
    } else {
      $product_feature_id = $product_feature->product_features->product_feature;
      $product_feature_id = $product_feature_id->attributes()->id->__toString();
    }

    return $product_feature_id;
  }
  
  public function setPrestashopProductFeature($product_feature)
  {
    $blankXml = $this->rest->get(Array('url' => $this->url . 'api/product_features?schema=synopsis'));
    $product_featureFields = $blankXml->product_feature->children();
    $product_featureFields->name->language[0] = (string) $product_feature;
    $product_features = Array(
      'resource' => 'product_features',
      'postXml' => $blankXml->asXML(),
    );
    $createdXml = $this->rest->add($product_features);
    if ($this->errors_found('product feature:' . $product_feature, $createdXml)) {
      return;
    }
    $newProductFeatureFields = $createdXml->product_feature->children();

    return $newProductFeatureFields->id;
  }

  public function setPrestashopCategory($cat_data, $id = false, $parent = false, $position = false)
  {
    if (!$cat_data->nimi or $cat_data->nimi == '') {
      return;
    }
    $category_name = $cat_data->nimi;

    $category_id = $cat_data->node_tunnus;
    if ($id) {
      $xml = $this->rest->get(Array(
        'resource' => 'categories',
        'id' => $id,
      ));
      if ($categoryFields = $xml->category->children()) {
        $categoryFields->name->language[0][0] = $category_name;
        $categoryFields->name->link_rewrite[0][0] = $this->slugify($category_name);
        $categoryFields->id_parent = $parent;
        $categoryFields->position = $position;
        unset($categoryFields->level_depth, $categoryFields->nb_products_recursive);
        
        $updatedXml = $this->rest->edit(Array(
          'resource' => 'categories',
          'id' => (int) $categoryFields->id,
          'putXml' => $xml->asXML(),
        ));

        return $categoryFields->id;
      }
    } else {
      $blankXml = $this->rest->get(Array('url' => $this->url . 'api/categories?schema=synopsis'));
    }

    $categoryFields = $blankXml->category->children();

    $categoryFields->name->language[0][0] = $category_name;
    $categoryFields->name->link_rewrite[0][0] = $this->slugify($category_name);

    $categoryFields->pupesoft_id->language[0][0] = $category_id;

    if ($parent and $position) {
      $categoryFields->id_parent = $parent;
      $categoryFields->position = $position;
    } else {
      $categoryFields->id_parent = 2;
    }
    unset($categoryFields->level_depth, $categoryFields->nb_products_recursive, $categoryFields->id);

    $categoryFields->active = 1;

    $categories = Array(
      'resource' => 'categories',
      'postXml' => $blankXml->asXML(),
    );
    $createdXml = $this->rest->add($categories);
    if ($this->errors_found($cat_data, $createdXml)) {
      return;
    }
    $newCategoryFields = $createdXml->category->children();

    return $newCategoryFields->id;
  }

  public function getPrestashopManufacturer($manufacturer)
  {
    $manufacturer_name = $manufacturer;
    $manufacturers = Array(
      'resource' => 'manufacturers',
      'filter[name]' => '[' . $manufacturer . ']'
    );
    $manufacturer = $this->rest->get($manufacturers);
    $checker = $manufacturer->manufacturers->children();
    if (empty($checker)) {
      $manufacturer_id = $this->setPrestashopManufacturer($manufacturer_name);
    } else {
      $manufacturer_id = $manufacturer->manufacturers->manufacturer;
      $manufacturer_id = $manufacturer_id->attributes()->id->__toString();
    }

    return $manufacturer_id;
  }

  public function categoryTreeBuild()
  {
    $yhtio = $this->yhtiorow['yhtio'];

    $query = "SELECT 
                node.nimi as nimi, 
                node.tunnus as tunnus, 
                node.parent_id as parent_tunnus, 
                node.lft as lft, 
                node.rgt as rgt, 
                node.parent_id as parent_id, 
                parent.nimi as parent_nimi,
                node.syvyys as syvyys
              FROM dynaaminen_puu AS node
              JOIN dynaaminen_puu AS parent ON node.yhtio=parent.yhtio and node.laji=parent.laji AND (node.parent_id=parent.tunnus OR parent.tunnus=NULL)
              WHERE node.yhtio = '{$yhtio}' 
              AND node.laji    = 'TUOTE'
              ORDER BY lft ASC";

    $result = pupe_query($query);
    $this->categories = array();
    $counter = 0;
    while ($row = mysql_fetch_assoc($result)) {
      if ($counter == 0) {
        $root_cat = $row['parent_tunnus'];
      }

      $this->categories[$row['tunnus']] = (object) Array(
        'parent_nimi' => $row['parent_nimi'],
        'nimi' => $row['nimi'],
        'node_tunnus' => $row['tunnus'],
        'parent_tunnus' => $row['parent_tunnus'],
        'syvyys' => $row['syvyys'],
        'sijainti' => $row['lft'],
      );

      $counter++;
    }

    foreach ($this->categories as &$i) {
      $this->nodes[] = Array('data' => &$i, 'childs' => Array(), 'parent' => null);
    }

    $this->cat_tree = Array('data' => null, 'childs' => Array(), 'parent' => null);
    foreach ($this->nodes as &$i) {
      if ($i['data']->parent_tunnus == $root_cat) {
        $this->cat_tree['childs'][$i['data']->node_tunnus] = &$i;
        //$i['parent'] = &$this->cat_tree;
        $i['childs'] = $this->categoryTreeBuildRecursive($i);
      }
    }

    $this->cat_tree = $this->cat_tree['childs'];

    return $this->cat_tree;
  }

  public function categoryTreeBuildRecursive(&$parent)
  {
    $childs = array();
    foreach ($this->nodes as &$i) {
      if ($i['data']->parent_tunnus == $parent['data']->node_tunnus) {
        $childs[$i['data']->node_tunnus] = &$i;
        $i['parent'] = &$parent;
        $i['childs'] = $this->categoryTreeBuildRecursive($i);
      }
    }

    return $childs;
  }

  public function getPrestashopCategory($cat_data, $id = false, $parent = false, $position = false)
  {
    $category_name = $cat_data->nimi;
    $category_tunnus = $cat_data->node_tunnus;
    if ($id and $parent and $position) {
      $categories = Array(
        'resource' => 'categories',
        'filter[id]' => '[' . $id . ']',
        'filter[pupesoft_id]' => '[' . $category_tunnus . ']',
        'filter[position]' => '[' . $position . ']',
        'filter[id_parent]' => '[' . $parent . ']'
      );
    } elseif ($id and !$parent and !$position) {
      $categories = Array(
        'resource' => 'categories',
        'filter[id]' => '[' . $id . ']'
      );
    } else {
      $categories = Array(
        'resource' => 'categories',
        'filter[pupesoft_id]' => '[' . $category_tunnus . ']'
      );
    }

    $category = $this->rest->get($categories);
    $checker = $category->categories->children();
    if (empty($checker) and $position and $parent) {
      $category_id = $this->setPrestashopCategory($cat_data, $id, $parent, $position);
    } elseif (empty($checker)) {
      $category_id = $this->setPrestashopCategory($cat_data);
    } else {
      $category_id = $category->categories->category;
      $category_id = $category_id->attributes()->id->__toString();
    }

    $this->foundCategories[$category_tunnus] = $category_id;

    return $category_id;
  }

  public function setCategoryPupesoftIds()
  {
    $yhtio = $this->yhtiorow['yhtio'];
    $categories = Array(
      'resource' => 'categories',
      'display' => 'full',
      'sort' => '[level_depth_ASC]'
    );

    $categories = $this->rest->get($categories);
    foreach ($categories->categories->category as $cat) {
      
      $cur_level = $cat->level_depth->__toString();
      
      if ($cur_level < 2) {
        continue;
      }

      $cur_id = $cat->pupesoft_id->language->__toString();

      $cur_level = $cur_level - 1;

      $cur_presta_id = $cat->id->__toString();
      $par_presta_id = $cat->id_parent->__toString();

      $parent_cat = $this->rest->get(Array(
        'resource' => 'categories',
        'id' => $par_presta_id
      ));
      $parent_cat_pupe_fields = $parent_cat->category->children();
      if($parent_cat_pupe_fields->pupesoft_id->language->__toString()) {
        $parent_lisa = "AND parent_id = '".$parent_cat_pupe_fields->pupesoft_id->language->__toString()."'";
      } else {
        $parent_lisa = '';
      }

      $cur_name = $cat->name->language->__toString();
      $query = "SELECT tunnus
                  FROM dynaaminen_puu
                  WHERE yhtio = '{$yhtio}' 
                  AND syvyys    = '{$cur_level}' 
                  AND nimi = '{$cur_name}' 
                  $parent_lisa 
                ";
      $result_q = pupe_query($query);

      $result = mysql_fetch_assoc($result_q);

      $xml = $this->rest->get(Array(
        'resource' => 'categories',
        'id' => $cur_presta_id
      ));

      $categoryFields = $xml->category->children();

      if ($result) {
        $categoryFields->pupesoft_id->language[0] = $result['tunnus'];
        $categoryFields->active = 1;
      } else {
        $categoryFields->active = 0;
        $categoryFields->pupesoft_id->language[0] = '';
      }

      unset($categoryFields->level_depth, $categoryFields->nb_products_recursive);

      $updatedXml = $this->rest->edit(Array(
        'resource' => 'categories',
        'id' => (int) $categoryFields->id,
        'putXml' => $xml->asXML(),
      ));
    }
  }

  public function get_tax_group_id($vat) {
    $taxes = array(
      "0" => "0",
      "10.00" => "3",
      "14.00" => "2",
      "24.00" => "1"
    );
    return $taxes[$vat];
  }

  public function updatePrestashopCategory($cat)
  {
    $cat_data = $cat['data'];

    $parent_data = (object) Array(
      'nimi' => $cat['data']->parent_nimi,
      'node_tunnus' => $cat['data']->parent_tunnus
    );

    if ($cat_data->syvyys == 1) {
      $parent_root = 2;
    } else {
      $parent_root = false;
    }

    $presta_parent_id = $this->getPrestashopCategory($parent_data, $parent_root, false, false, 1);

    $presta_id = $this->getPrestashopCategory($cat_data, false, false, false);
    $cat_id = $this->getPrestashopCategory($cat_data, $presta_id, $presta_parent_id, $cat_data->sijainti);
  }

  public function updatePrestashopCategories($pupesoft_categories)
  {
    foreach ($pupesoft_categories as $cat1) {
      $this->updatePrestashopCategory($cat1);
      foreach ($cat1['childs'] as $cat2) {
        $this->updatePrestashopCategory($cat2);
        foreach ($cat2['childs'] as $cat3) {
          $this->updatePrestashopCategory($cat3);
          foreach ($cat3['childs'] as $cat4) {
            $this->updatePrestashopCategory($cat4);
          }
        }
      }
    }
  }

  public function getPrestashopProductFeatureValues($product_feature, $product_feature_id)
  {
    $product_feature_name = $product_feature;
    $product_feature_values = Array(
      'resource' => 'product_feature_values',
      'filter[value]' => '[' . $product_feature . ']',
      'filter[id_feature]' => '[' . $product_feature_id . ']'
    );
    $product_feature_value = $this->rest->get($product_feature_values);

    $checker = $product_feature_value->product_feature_values->children();
    if (empty($checker)) {
      $product_feature_value_id = $this->setPrestashopProductFeatureValues($product_feature_name, $product_feature_id);
    } else {
      $product_feature_value_id = $product_feature_value->product_feature_values->product_feature_value;
      $product_feature_value_id = $product_feature_value_id->attributes()->id->__toString();
    }

    return $product_feature_value_id;
  }

  public function setPrestashopProductFeatureValues($product_feature_value, $product_feature_id)
  {
    $blankXml = $this->rest->get(Array('url' => $this->url . 'api/product_feature_values?schema=synopsis'));
    $product_feature_valueFields = $blankXml->product_feature_value->children();
    $product_feature_valueFields->value->language[0] = (string) $product_feature_value;
    $product_feature_valueFields->id_feature = $product_feature_id;
    $product_feature_values = Array(
      'resource' => 'product_feature_values',
      'postXml' => $blankXml->asXML(),
    );
    $createdXml = $this->rest->add($product_feature_values);
    if ($this->errors_found('feature value:' . $product_feature_value . '|' . $product_feature_id, $createdXml)) {
      return;
    }
    $newProductFeatureValuesFields = $createdXml->product_feature_value->children();

    return $newProductFeatureValuesFields->id;
  }

  public function updatePrestashopProducts($prestashop_products, $pupesoft_products)
  {
    foreach ($prestashop_products['found'] as $prestashop_product) {
      
      $xml = $this->rest->get(Array(
        'resource' => 'products',
        'id' => $prestashop_product,
      ));

      if ($productFields = $xml->product->children()) {
        $pupesoft_products_arr = $pupesoft_products[(string) $productFields->reference];
      } else {
        //$prestashop_products['missing'][] = $prestashop_product;
        continue;
      }

      $cat_data = array();

      foreach ($pupesoft_products_arr as $pupesoft_product) {
        $cat_data[] = (object) Array(
          'nimi' => '',
          'node_tunnus' => $pupesoft_product['puun_tunnus']
        );
      }

      $pupesoft_product = $pupesoft_products_arr[0];

      $pupesoft_product['try_nimike'] = $pupesoft_product['try_nimike'];
      $pupesoft_product['tuotemerkki'] = $pupesoft_product['tuotemerkki'];

      if (isset($pupesoft_product['tuotemerkki'])
      and $pupesoft_product['tuotemerkki']
      and $pupesoft_product['tuotemerkki'] != ''
      and $pupesoft_product['tuotemerkki'] != null) {
        $productFields->id_manufacturer = $this->getPrestashopManufacturer($pupesoft_product['tuotemerkki']);
      } else {
        $productFields->id_manufacturer = '';
      }

      unset($productFields->associations->product_features->product_feature);

      if ($pupesoft_product['yksikko'] and $pupesoft_product['yksikko'] != '') {
        $new_feat = $productFields->associations->product_features->addChild('product_feature');
        $new_feat->addChild('id', $this->getPrestashopProductFeature('Yksikkö'));
        $product_val = $this->getPrestashopProductFeatureValues($pupesoft_product['yksikko'], $this->getPrestashopProductFeature('Yksikkö'));
        $new_feat->addChild('id_feature_value', $product_val);
      }

      if ($pupesoft_product['myynti_era'] and $pupesoft_product['myynti_era'] != '' and $pupesoft_product['myynti_era'] > 0) {
          $new_feat = $productFields->associations->product_features->addChild('product_feature');
          $new_feat->addChild('id', $this->getPrestashopProductFeature('Myyntierä'));
          $product_val = $this->getPrestashopProductFeatureValues($pupesoft_product['myynti_era'], $this->getPrestashopProductFeature('Myyntierä'));
          $new_feat->addChild('id_feature_value', $product_val);
      }

      unset($productFields->associations->product_bundle);

      unset($productFields->associations->categories->category);

      foreach ($cat_data as $cat_data_single) {
        if (isset($this->foundCategories[$cat_data_single->node_tunnus])) {
          $newcat = $productFields->associations->categories->addChild('category');
          $newcat->addChild('id', $this->foundCategories[$cat_data_single->node_tunnus]);
        } else {
          $newcat = $productFields->associations->categories->addChild('category');
          $newcat->addChild('id', $this->getPrestashopCategory($cat_data_single));
        }
      }

      $productFields->id_category_default = $this->getPrestashopCategory($cat_data[0]);

      $productFields->id_tax_rules_group = $this->get_tax_group_id($pupesoft_product["alv"]);

      $productFields->price = $pupesoft_product['myyntihinta'];
      $pupesoft_product['nimitys'] = trim(str_replace(array('='), '-', $pupesoft_product['nimitys']));
      $pupesoft_product['nimitys'] = trim(str_replace(array('#'), '?', $pupesoft_product['nimitys']));
      $productFields->name->language[0] = $pupesoft_product['nimitys'];
      $productFields->link_rewrite->language[0] = $this->slugify($pupesoft_product['nimitys']);
      $productFields->meta_title->language[0] = $pupesoft_product['nimitys'];

      $productFields->meta_keywords->language[0] = $pupesoft_product['try_nimike'];

      $productFields->available_later->language[0] = 'TILAUSTUOTE';

      if ($pupesoft_product['lyhytkuvaus'] and $pupesoft_product['lyhytkuvaus'] != '') {
        $productFields->description_short->language[0] = mb_strimwidth($pupesoft_product['lyhytkuvaus'], 0, 400, '...');
        $productFields->meta_description->language[0] = mb_strimwidth(str_replace('=', '-', $pupesoft_product['lyhytkuvaus']), 0, 200, '...');
      }

      if ($pupesoft_product['kuvaus'] and $pupesoft_product['kuvaus'] != '') {
        $productFields->description->language[0] = nl2br($pupesoft_product['kuvaus']);
      }

      $productFields->minimal_quantity = (int) $pupesoft_product['myynti_era'];
      if($pupesoft_product['eankoodi'] and mb_strlen(preg_replace("/[^0-9]/", "", $pupesoft_product['eankoodi']) == 13)) {
        $productFields->ean13 = preg_replace("/[^0-9]/", "", $pupesoft_product['eankoodi']);
      }
      
      $productFields->state = 1;

      if ($pupesoft_product['status'] == 'P') {
        $productFields->active = 0;
        $productFields->available_for_order = 0;
      } else {
        $productFields->active = 1;
        $productFields->available_for_order = 1;
      }

      $productFields->show_price = 1;

      $productFields->height = (float) $pupesoft_product['tuotekorkeus'];
      $productFields->width = (float) $pupesoft_product['tuoteleveys'];
      $productFields->depth = (float) $pupesoft_product['tuotesyvyys'];
      $productFields->weight = (float) $pupesoft_product['tuotemassa'];

      unset($productFields->manufacturer_name, $productFields->quantity, $productFields->id_shop_default, $productFields->id_default_image, $productFields->id_default_combination, $productFields->position_in_category, $productFields->type, $productFields->pack_stock_type);

      $updatedXml = $this->rest->edit(Array(
        'resource' => 'products',
        'id' => (int) $productFields->id,
        'putXml' => $xml->asXML(),
      ));
    }

    unset($productFields, $updatedXml, $xml, $pupesoft_product, $cat_data, $skip_product);

    foreach ($prestashop_products['missing'] as $pupesoft_products_arr) {

      $cat_data = array();
      
      foreach ($pupesoft_products_arr as $pupesoft_product) {
        $cat_data[] = (object) Array(
          'nimi' => '',
          'node_tunnus' => $pupesoft_product['puun_tunnus']
        );
      }
      $pupesoft_product = $pupesoft_products_arr[0];
      $blankXml = $this->rest->get(Array('url' => $this->url . 'api/products?schema=synopsis'));
      $productFields = $blankXml->product->children();

      if (isset($pupesoft_product['tuotemerkki'])
      and $pupesoft_product['tuotemerkki']
      and $pupesoft_product['tuotemerkki'] != ''
      and $pupesoft_product['tuotemerkki'] != null) {
        $productFields->id_manufacturer = $this->getPrestashopManufacturer($pupesoft_product['tuotemerkki']);
      } else {
        $productFields->id_manufacturer = '';
      }

      unset($productFields->associations->product_bundle);

      foreach ($cat_data as $cat_data_single) {
        if (isset($this->foundCategories[$cat_data_single->node_tunnus])) {
          $newcat = $productFields->associations->categories->addChild('category');
          $newcat->addChild('id', $this->foundCategories[$cat_data_single->node_tunnus]);
        } else {
          $newcat = $productFields->associations->categories->addChild('category');
          $newcat->addChild('id', $this->getPrestashopCategory($cat_data_single));
        }
      }

      if ($pupesoft_product['yksikko'] and $pupesoft_product['yksikko'] != '') {
        $new_feat = $productFields->associations->product_features->addChild('product_feature');
        $new_feat->addChild('id', $this->getPrestashopProductFeature('Yksikkö'));
        $product_val = $this->getPrestashopProductFeatureValues($pupesoft_product['yksikko'], $this->getPrestashopProductFeature('Yksikkö'));
        $new_feat->addChild('id_feature_value', $product_val);
      }

      if ($pupesoft_product['myynti_era'] and $pupesoft_product['myynti_era'] != '' and $pupesoft_product['myynti_era'] > 0) {
        $new_feat = $productFields->associations->product_features->addChild('product_feature');
        $new_feat->addChild('id', $this->getPrestashopProductFeature('Myyntierä'));
        $product_val = $this->getPrestashopProductFeatureValues($pupesoft_product['myynti_era'], $this->getPrestashopProductFeature('Myyntierä'));
        $new_feat->addChild('id_feature_value', $product_val);
      }

      $productFields->id_category_default = $this->getPrestashopCategory($cat_data[0]);

      $productFields->id_tax_rules_group = $this->get_tax_group_id($pupesoft_product["alv"]);

      $productFields->price = $pupesoft_product['myyntihinta'];
      $pupesoft_product['nimitys'] = trim(str_replace(array('='), '-', $pupesoft_product['nimitys']));
      $pupesoft_product['nimitys'] = trim(str_replace(array('#'), '?', $pupesoft_product['nimitys']));
      $productFields->name->language[0] = $pupesoft_product['nimitys'];
      $productFields->meta_title->language[0] = $pupesoft_product['nimitys'];
      $productFields->link_rewrite->language[0] = $this->slugify($pupesoft_product['nimitys']);
      $productFields->meta_keywords->language[0] = $pupesoft_product['try_nimike'];

      $productFields->available_later->language[0] = 'TILAUSTUOTE';

      if ($pupesoft_product['lyhytkuvaus'] and $pupesoft_product['lyhytkuvaus'] != '') {
        $productFields->description_short->language[0] = mb_strimwidth($pupesoft_product['lyhytkuvaus'], 0, 400, '...');
        $productFields->meta_description->language[0] = mb_strimwidth(str_replace('=', '-', $pupesoft_product['lyhytkuvaus']), 0, 200, '...');
      }

      if ($pupesoft_product['kuvaus'] and $pupesoft_product['kuvaus'] != '') {
        $productFields->description->language[0] = nl2br($pupesoft_product['kuvaus']);
      }
      
      $productFields->minimal_quantity = (int) $pupesoft_product['myynti_era'];
      if($pupesoft_product['eankoodi'] and mb_strlen(preg_replace("/[^0-9]/", "", $pupesoft_product['eankoodi']) == 13)) {
        $productFields->ean13 = preg_replace("/[^0-9]/", "", $pupesoft_product['eankoodi']);
      }
      
      $productFields->state = 1;
      $productFields->reference = $pupesoft_product['tuoteno'];

      if ($pupesoft_product['status'] == 'P') {
        $productFields->active = 0;
        $productFields->available_for_order = 0;
      } else {
        $productFields->active = 1;
        $productFields->available_for_order = 1;
      }

      $productFields->show_price = 1;
 
      $productFields->height = (float) $pupesoft_product['tuotekorkeus'];
      $productFields->width = (float) $pupesoft_product['tuoteleveys'];
      $productFields->depth = (float) $pupesoft_product['tuotesyvyys'];
      $productFields->weight = (float) $pupesoft_product['tuotemassa'];

      unset($productFields->id, $productFields->manufacturer_name, $productFields->quantity, $productFields->id_shop_default, $productFields->id_default_image, $productFields->id_default_combination, $productFields->position_in_category, $productFields->type, $productFields->pack_stock_type, $productFields->date_add, $productFields->date_upd);
      
      $updatedXml = $this->rest->add(Array(
        'resource' => 'products',
        'postXml' => $blankXml->asXML(),
      ));
    }
  }

  public function getPupesoftCustomerGroups()
  {
    $yhtio = $this->yhtiorow['yhtio'];

    $query = "SELECT avainsana.*,
              avainsana.selitetark_5 AS presta_customergroup_id
              FROM avainsana
              WHERE avainsana.yhtio = '{$yhtio}'
              AND laji              = 'ASIAKASRYHMA' ORDER by jarjestys";
    $result = pupe_query($query);

    $groups_result = array();

    while ($group = mysql_fetch_assoc($result)) {
      if (!$group['presta_customergroup_id'] or $group['presta_customergroup_id'] == '') {
        $group_id = $group['presta_customergroup_id'] = $this->setPupesoftCustomerGroup($group);
        $query = "UPDATE avainsana
                    SET selitetark_5 = '{$group_id}'
                    WHERE yhtio = '{$yhtio}'
                    AND tunnus  = {$group['tunnus']}";
        pupe_query($query);
      } else {
        $group_id = $group['presta_customergroup_id'];
        $group_name = $group['selite'];
        $groups = Array(
          'resource' => 'groups',
          'filter[id]' => '[' . $group_id . ']'
        );
        $group_presta = $this->rest->get($groups);
        $checker = $group_presta->groups->children();
        if (empty($checker)) {
          $group_id = $this->setPupesoftCustomerGroup($group);
          $query = "UPDATE avainsana
                      SET selitetark_5 = '{$group_id}'
                      WHERE yhtio = '{$yhtio}'
                      AND tunnus  = {$group['tunnus']}";
          pupe_query($query);
        } else {
          $groups = Array(
            'resource' => 'groups',
            'filter[name]' => '[' . $group['selite'] . ']',
            'filter[id]' => '[' . $group_id . ']'
          );
          $group_presta = $this->rest->get($groups);
          $checker = $group_presta->groups->children();
          if (empty($checker)) {
            $this->setPupesoftCustomerGroup($group, $group_id);
          }
        }
      }

      $groups_result[$group_id] = $group;
    }

    $groups = Array(
      'resource' => 'groups'
    );
    $group_presta = $this->rest->get($groups);
    $group_presta = $group_presta->groups->group;

    foreach ($group_presta as $group) {
      $id_check = (string) $group->attributes()->id;
      if (!isset($groups_result[$id_check])) {
        $this->rest->delete(Array(
          'resource' => 'groups',
          'id' => $id_check
        ));
      }
    }

    return $groups_result;
  }

  public function setPupesoftCustomerGroup($group, $group_id = false)
  {
    if (!$group_id) {
      $blankXml = $this->rest->get(Array('url' => $this->url . 'api/groups?schema=synopsis'));
      $groupFields = $blankXml->group->children();
      $group_name = (string) $group['selite'];
      $groupFields->name->language[0] = $group_name;
      $groupFields->price_display_method = 1;
      $groupFields->show_prices = 1;
      $groups = Array(
        'resource' => 'groups',
        'postXml' => $blankXml->asXML(),
      );
      $createdXml = $this->rest->add($groups);
      if ($this->errors_found($group, $createdXml)) {
        return;
      }
      $newGroupFields = $createdXml->group->children();

      return $newGroupFields->id;
    } else {
      $xml = $this->rest->get(Array(
        'resource' => 'groups',
        'id' => $group_id,
      ));
      $groupFields = $xml->group->children();

      $groupFields->name->language[0] = (string) $group['selite'];
      $updatedXml = $this->rest->edit(Array(
        'resource' => 'groups',
        'id' => (int) $groupFields->id,
        'putXml' => $xml->asXML(),
      ));
      return $groupFields->id;
    }
  }

  public function errors_found($source, $xml)
  {
    if ($xml->errors->error) {
      $error_message = print_r((array) $xml->errors->error, true);
      $error_container = print_r($source, true);
      $log_file = './logs/errors' . date('d_m_Y') . '.log';
      $error_message = "\n\n\n\n-------------" . date('d.m.Y H:i:s') . ".............\n\n" . $error_container . "\n--------------------\n" . $error_message;
      error_log($error_message, 3, $log_file);
      chmod($log_file, 0600);
      return true;
    }

    return;
  }

  public function setPupesoftCustomer($customer, $customer_id = false, $group_add = false)
  {
    $yhtio = $this->yhtiorow['yhtio'];

    if ($customer_id) {
      $xml = $this->rest->get(Array(
        'resource' => 'customers',
        'id' => $customer_id,
      ));
      if ($xml and $customerFields = $xml->customer->children()) {
        unset($customerFields->date_upd);
      } else {
        $customer_id = false;
      }
    }
    if (!$customer_id) {
      $xml = $this->rest->get(Array('url' => $this->url . 'api/customers?schema=synopsis'));
      $customerFields = $xml->customer->children();
    }

    $customerFields->email = filter_var((string) $customer['email'], FILTER_VALIDATE_EMAIL);
    if (!$customerFields->email) {
      return;
    }

    if ($customer_id and $customerFields->email == 'development@devlab.fi') {
      $customerFields->email = rand(0, 999999)."development@devlab.fi";
    }

    $customerFields->firstname = '-';

    if (!preg_match("/^[a-zA-Z\s\ä\Ä\ö\Ö]+$/", $customer['nimi'])) {
      $address['nimi'] = 'Tuntematon';
    } 
    $customerFields->lastname = str_replace(array(".","@"), " ", $customer['nimi']);

    if (isset($customer['asiakas_nimi'])) {
      $customerFields->company = (string) $customer['asiakas_nimi'];
    }
    $customerFields->show_public_prices = 1;
    $customerFields->newsletter = 1;

    if (!empty($customer['verkkokauppa_salasana'])) {
      $contact_id_upd = $customer['yhteyshenkilo_tunnus'];
      $customerFields->passwd = (string) $customer['verkkokauppa_salasana'];
      $query = "UPDATE yhteyshenkilo 
        SET verkkokauppa_salasana = '' 
        WHERE yhtio = '{$yhtio}' 
        AND tunnus  = {$contact_id_upd}";
      pupe_query($query);
    }

    $customerFields->active = 1;

    $group_id = (int) $customer['presta_customergroup_id'];
    $customerFields->id_default_group = $group_id;

    $remove_node = $customerFields->associations->groups;
    $dom_node = dom_import_simplexml($remove_node);
    $dom_node->parentNode->removeChild($dom_node);

    $groups = $customerFields->associations->addChild('groups');

    if ($group_add) {
      $all_groups = array_merge(Array(1, 2, 3, $group_id),$group_add);
    } else {
      $all_groups = Array(1, 2, 3, $group_id);
    }

    foreach ($all_groups as $group_id) {
      $group = $groups->addChild('groups');
      $group->addChild('id', $group_id);
    }
    
    if ($customer_id) {
      $updatedXml = $this->rest->edit(Array(
        'resource' => 'customers',
        'id' => $customer_id,
        'putXml' => $xml->asXML(),
      ));
      
      return $customer_id;
    } else {
      $customers = Array(
        'resource' => 'customers',
        'postXml' => $xml->asXML(),
      );
      $createdXml = $this->rest->add($customers);

      if ($this->errors_found($customer, $createdXml)) {
        return false;
      }

      if ($createdXml) {
        $newGroupFields = $createdXml->customer->children();
        $yhteyshenkilon_tunnus = $customer['yhteyshenkilo_tunnus'];
        $query = "UPDATE yhteyshenkilo
                  SET ulkoinen_asiakasnumero = {$newGroupFields->id}
                  WHERE yhtio = '{$yhtio}'
                  AND tunnus  = {$yhteyshenkilon_tunnus}";
        pupe_query($query);
        return $newGroupFields->id;
      }
    }
  }

  public function getPrestashopCountry($search, $by_name = false)
  {
    if ($by_name) {
      if (isset($this->fi_countries[$search])) {
        $search = $this->fi_countries[$search];
      }
      $countries = Array(
        'resource' => 'countries',
        'filter[name]' => '[' . $search . ']',
        'display' => 'full'
      );
    } else {
      $countries = Array(
        'resource' => 'countries',
        'filter[iso_code]' => '[' . $search . ']',
        'display' => 'full'
      );
    }

    if ($countries = $this->rest->get($countries)) {
      if ($countries = $countries->countries->country) {
        return $countries->id->__toString();
      }
    }

    return;
  }

  public function getPupesoftCustomers($days, $presta_customergroup_id = "")
  {
    $yhtio = $this->yhtiorow['yhtio'];

    $customers = array();

    if($presta_customergroup_id != "") {
      $presta_customergroup_id = "AND ulkoinen_asiakasnumero = $presta_customergroup_id";
    }
    
    $query = "SELECT
                asiakas.kuljetusohje,
                asiakas.nimi as asiakas_nimi,
                asiakas.nimitark as asiakas_nimitark,
                yhteyshenkilo.tunnus as asiakas_tunnus,
                yhteyshenkilo.liitostunnus as y_tun,
                asiakas.tunnus as a_tun,
                yhteyshenkilo.fakta as fakta,
                asiakas.ytunnus,
                asiakas.ryhma,
                asiakas.yhtio,
                asiakas.gsm as gsm2,
                asiakas.puhelin as puh2,
                asiakas.maa as asiakas_maa, 
                avainsana.selitetark_5,
                yhteyshenkilo.email,
                yhteyshenkilo.gsm,
                yhteyshenkilo.maa as maa,
                yhteyshenkilo.nimi,
                asiakas.laskutus_osoite,
                asiakas.laskutus_postino,
                asiakas.laskutus_postitp,
                COALESCE(NULLIF(yhteyshenkilo.postino,''), asiakas.postino) as postino,
                COALESCE(NULLIF(yhteyshenkilo.postitp,''), asiakas.postitp) as postitp, 
                COALESCE(NULLIF(yhteyshenkilo.osoite,''), asiakas.osoite) as osoite, 
                yhteyshenkilo.puh,
                yhteyshenkilo.tunnus as yhteyshenkilo_tunnus,
                yhteyshenkilo.ulkoinen_asiakasnumero,
                yhteyshenkilo.verkkokauppa_nakyvyys,
                yhteyshenkilo.verkkokauppa_salasana,
                yhteyshenkilo.yhtio,
                avainsana.selitetark_5 as presta_customergroup_id
              FROM yhteyshenkilo
              INNER JOIN asiakas
              ON (asiakas.yhtio = yhteyshenkilo.yhtio
                AND asiakas.tunnus      = yhteyshenkilo.liitostunnus )
              LEFT JOIN avainsana
              ON (avainsana.yhtio = asiakas.yhtio
                AND avainsana.selite    = asiakas.ryhma
                AND avainsana.laji      = 'ASIAKASRYHMA')
              WHERE yhteyshenkilo.yhtio = '{$yhtio}' 
                AND asiakas.laji != 'P' 
                AND yhteyshenkilo.rooli   = 'Presta' 
              $presta_customergroup_id 
                AND (
                (yhteyshenkilo.muutospvm between date_sub(now(),INTERVAL '$days' DAY) AND now()) 
                OR 
                (asiakas.muutospvm between date_sub(now(),INTERVAL '$days' DAY) AND now())
              )
              ";

    $result = pupe_query($query);
    $addresses = array();
    while ($customer = mysql_fetch_assoc($result)) {
      $addresses[0] = Array(
        'asiakas_id' => $customer['asiakas_tunnus'],
        'asiakas_nimi' => $customer['asiakas_nimi'],
        'gsm' => $customer['gsm'],
        'gsm2' => $customer['gsm2'],
        'maa' => $customer['maa'],
        'asiakas_maa' => $customer['asiakas_maa'],
        'nimi' => $customer['nimi'],
        'osoite' => $customer['osoite'],
        'postino' => $customer['postino'],
        'postitp' => $customer['postitp'],
        'y_tun' => $customer['y_tun'],
        'a_tun' => $customer['a_tun'],
        'laskutus_osoite' => $customer['laskutus_osoite'],
        'laskutus_postino' => $customer['laskutus_postino'],
        'laskutus_postitp' => $customer['laskutus_postitp'],
        'puh' => $customer['puh'],
        'puh2' => $customer['puh2'],
        'ytunnus' => $customer['ytunnus'],
        'asiakas_nimitark' => $customer['asiakas_nimitark'],
      );

      $presta_id = false;

      if (!$customer['ulkoinen_asiakasnumero'] or $customer['ulkoinen_asiakasnumero'] == '') {

        $addresses_search = Array(
          'resource' => 'addresses',
          'filter[lastname]' => '[' . $customer['nimi'] . ']',
          'filter[company]' => '[' . $customer['asiakas_nimi'] . ']',
          'filter[dni]' => '![]',
          'display' => 'full'
        );
    
        if ($address_search = $this->rest->get($addresses_search)) {
          if ($address_search = $address_search->addresses->address) {
            $presta_id = $customer['ulkoinen_asiakasnumero'] = $address_search->id_customer->__toString();
            $pupe_id = $address_search->dni->__toString();
            $customer['ulkoinen_asiakasnumero'] = $presta_id;
            $query = "UPDATE yhteyshenkilo
                        SET ulkoinen_asiakasnumero = {$presta_id}
                      WHERE yhtio = '{$yhtio}'
                        AND tunnus  = {$pupe_id}";
            pupe_query($query);
          }
        }
        if(!$presta_id) {
          $presta_id = $customer['ulkoinen_asiakasnumero'] = $this->setPupesoftCustomer($customer);
        }
      } else {
        $presta_id = $customer['ulkoinen_asiakasnumero'] = $this->setPupesoftCustomer($customer, $customer['ulkoinen_asiakasnumero']);
      }

      if(!$presta_id) {
        continue;
      }

      $customers[$presta_id][] = Array(
        'email' => $customer['email'],
        'kuljetusohje' => $customer['kuljetusohje'],
        'nimi' => $customer['nimi'],
        'osoitteet' => $addresses,
        'presta_customergroup_id' => $customer['selitetark_5'],
        'tunnus' => $customer['yhteyshenkilo_tunnus'],
        'ulkoinen_asiakasnumero' => $customer['ulkoinen_asiakasnumero'],
        'verkkokauppa_nakyvyys' => $customer['verkkokauppa_nakyvyys'],
        'verkkokauppa_salasana' => $customer['verkkokauppa_salasana'],
        'yhtio' => $customer['yhtio'],
      );
    }

    return $customers;
  }

  public function setPupesoftOrder($pupesoft_customer_id, $order, $invoice_address, $delivery_address) {

    $order_id = $order->id->__toString();

    $query = "SELECT * from asiakas where tunnus = $pupesoft_customer_id";
    $pupesoft_customer_search = pupe_query($query);

    $options = array(
      'edi_polku'         => $this->presta17_api_edipath,
      'ovt_tunnus'        => $this->presta17_api_ovt,
      'rahtikulu_nimitys' => 'Toimituskulut',
      'rahtikulu_tuoteno' => 'RAHTI',
      'tilaustyyppi'      => '2',
      'maksuehto_ohjaus'  => $this->presta17_api_payment_rule,
      'erikoiskasittely'  => array(),
      'verkkokauppa_verollisen_hinnan_kentta' => '',
    );
    $tilaus['payment']['method'] = $order->payment->__toString();

    $carrier_search = $this->rest->get(Array(
      'resource' => 'carriers',
      'id' => $order->id_carrier->__toString()
    ));
    if($carrier = $carrier_search->carrier) {
      $tilaus['shipping_description'] = $carrier->name->__toString();
      $tilaus['shipping_description_line'] = $carrier->name->__toString();
    }

    $prestashop_customer_search = $this->rest->get(Array(
      'resource' => 'customers',
      'id' => $order->id_customer->__toString()
    ));
    if($customer = $prestashop_customer_search->customer) {
      $tilaus['customer_email'] = $customer->email->__toString();
    }

    while ($pupesoft_customer = mysql_fetch_assoc($pupesoft_customer_search)) {
      $options['asiakasnro'] = $pupesoft_customer['asiakasnro'];

      $tilaus['billing_address']['city'] = $invoice_address->city->__toString();
      $tilaus['billing_address']['company'] = $invoice_address->company->__toString();
      $tilaus['billing_address']['fax'] = "";
      $tilaus['billing_address']['firstname'] = $invoice_address->firstname->__toString();
      $tilaus['billing_address']['lastname'] = $invoice_address->lastname->__toString();
      $tilaus['billing_address']['postcode'] = $invoice_address->postcode->__toString();
      $tilaus['billing_address']['street'] = $invoice_address->address1->__toString();
      $tilaus['billing_address']['telephone'] = $invoice_address->phone_mobile->__toString();
      if(!$tilaus['billing_address']['telephone'] or $tilaus['billing_address']['telephone'] == "") {
        $tilaus['billing_address']['telephone'] = $invoice_address->phone->__toString();
      }

      $tilaus['shipping_address']['city'] = $delivery_address->city->__toString();
      $tilaus['shipping_address']['company'] = $delivery_address->company->__toString();

      $country_search = $this->rest->get(Array(
        'resource' => 'countries',
        'id' => $delivery_address->id_country->__toString()
      ));
      if($country = $country_search->country) {
        $tilaus['shipping_address']['country_id'] = $country->iso_code->__toString();
      }

      $tilaus['shipping_address']['firstname'] = $delivery_address->firstname->__toString();
      $tilaus['shipping_address']['lastname'] = $delivery_address->lastname->__toString();
      $tilaus['shipping_address']['postcode'] = $delivery_address->postcode->__toString();
      $tilaus['shipping_address']['street'] = $delivery_address->address1->__toString();
      $tilaus['shipping_address']['telephone'] = $delivery_address->phone_mobile->__toString();
      if(!$tilaus['shipping_address']['telephone'] or $tilaus['shipping_address']['telephone'] == "") {
        $tilaus['shipping_address']['telephone'] = $delivery_address->phone->__toString();
      }

      $tilaus['order_number'] = $order_id;
      $tilaus['increment_id'] = $order_id;

      $tilaus['store_name'] = "Presta";
      $tilaus['status'] = "processing";
      $tilaus['reference_number'] = "";
      $tilaus['target'] = "";
      $tilaus['webtex_giftcard'] = "";

      $tilaus['customer_id'] = $pupesoft_customer_id;

      $messages_search = $this->rest->get(Array(
        'resource' => 'customer_threads',
        'filter[id_order]' => '['.$order_id.']',
        'display' => 'full'
      ));

      $tilaus['customer_note'] = '';

      foreach ($messages_search->customer_threads->customer_thread as $messages) {
        if($message_id = $messages->associations->customer_messages->customer_message->id->__toString()) {
          $message_search = $this->rest->get(Array(
            'resource' => 'customer_messages',
            'id' => $message_id
          ));
          if($message = $message_search->customer_message) {
            $tilaus['customer_note'] = $message->message->__toString();
            break;
          }
        }
      }

      $tilaus['grand_total'] = (float) $order->total_paid;
      $tilaus['tax_amount'] = (float) $order->total_paid_tax_incl - (float) $order->total_paid_tax_excl;
      $tilaus['shipping_tax_amount'] = (float) $order->total_shipping_tax_incl - (float) $order->total_shipping_tax_excl;
      $tilaus['shipping_amount'] = (float) $order->total_shipping_tax_excl;
      $tilaus['order_currency_code'] = "EUR";

      $tilaus['items'] = array();

      foreach ($order->associations->order_rows->order_row as $product) {

        $item = array();

        $item['sku'] = (string) $product->product_reference;
        $item['name'] = (string) $product->product_name;

        $item['qty_ordered'] = (int) $product->product_quantity;

        $item['base_discount_amount'] = 0;
        $item['discount_percent'] = 0;

        $item['original_price'] = (float) $product->unit_price_tax_incl;

        $item['price'] = (float) $product->unit_price_tax_excl;

        $item['tax_percent'] = (($item['original_price'] / $item['price']) - 1) * 100;
        $item['parent_item_id'] = "";
        $item['product_id'] = "";
        $item['product_type'] = "";

        $tilaus['items'][] = $item;
      }

      if($this->edi->create($tilaus, $options)) {
        $xml = $this->rest->get(Array(
          'resource' => 'orders',
          'id' => $order_id,
        ));
  
        if ($orderFields = $xml->order->children()) {
          $orderFields->note = $orderFields->note->__toString()."\nTilaus pupeessa";
          $updatedXml = $this->rest->edit(Array(
            'resource' => 'orders',
            'id' => (int) $orderFields->id,
            'putXml' => $xml->asXML(),
          ));
        }
      }

    }
  }

  public function getPrestashopOrders($days) {

    $orders = Array(
      'resource' => 'orders',
      'filter[note]' => '%[Tilaus pupeessa]%',
      'display' => 'full'
    );
    $orders_processed = array();
    $pupesoft_orders = $this->rest->get($orders);
    foreach ($pupesoft_orders->orders->order as $order) {
      if($order->id_customer->__toString() != 0) {
        $orders_processed[$order->id->__toString()] = $order;
      }
    }

    $orders = Array(
      'resource' => 'orders',
      'display' => 'full'
    );
    $missing_orders = array();
    $prestashop_orders = $this->rest->get($orders);

    $date_now = new DateTime();
    $date_now->modify("-$days day");
    
    foreach ($prestashop_orders->orders->order as $order) {
      if(!isset($orders_processed[$order->id->__toString()]) and $customer_id = $order->id_customer->__toString()) {

        $date2 = new DateTime($order->date_add->__toString());

        if ($date_now > $date2) {
          continue;
        }

        $customer_dummy = array(
          $customer_id => array(
            0 => array(
              'osoitteet' => array(
                'asiakas_id' => false
              )
            )
          )
        );

        if($customer = $this->getPupesoftCustomers("99999", $customer_id) or $customer = $customer_dummy) {

          foreach($customer[$customer_id] as $address_arr) {

            foreach($address_arr['osoitteet'] as $address) {

              if($address['asiakas_id']) {
                $found_invoice_add = $this->rest->get(Array(
                  'resource' => 'addresses',
                  'filter[id_customer]' => '[' . $customer_id . ']',
                  'filter[dni]' => '[' . $address['asiakas_id'] . ']',
                  'filter[id]' => '[' . $order->id_address_invoice->__toString() . ']',
                  'display' => 'full'
                ));
  
                $found_delivery_add = $this->rest->get(Array(
                  'resource' => 'addresses',
                  'filter[id_customer]' => '[' . $customer_id . ']',
                  'filter[dni]' => '[' . $address['asiakas_id'] . ']',
                  'filter[id]' => '[' . $order->id_address_delivery->__toString() . ']',
                  'display' => 'full'
                ));

                $pupesoft_customer_id = $address['a_tun'];
              } else {
                $found_invoice_add = $this->rest->get(Array(
                  'resource' => 'addresses',
                  'filter[id_customer]' => '[' . $customer_id . ']',
                  'filter[id]' => '[' . $order->id_address_invoice->__toString() . ']',
                  'display' => 'full'
                ));
  
                $found_delivery_add = $this->rest->get(Array(
                  'resource' => 'addresses',
                  'filter[id_customer]' => '[' . $customer_id . ']',
                  'filter[id]' => '[' . $order->id_address_delivery->__toString() . ']',
                  'display' => 'full'
                ));

                $pupesoft_customer_id = $this->presta17_api_customer;
              }

              if(
                $invoice_address = $found_invoice_add->addresses->address and 
                $delivery_address = $found_delivery_add->addresses->address
              ) {

                $this->setPupesoftOrder(
                  $pupesoft_customer_id, 
                  $order, 
                  $invoice_address,
                  $delivery_address
                );
              } 

            }
          }
        }
      }
    }

    return array(
      "processed" => $orders_processed,
      "missing" => $missing_orders
    );
  }

  public function getPupesoftPrices($days)
  {
    $yhtio = $this->yhtiorow['yhtio'];

    $query = "SELECT
                GROUP_CONCAT(yhteyshenkilo.ulkoinen_asiakasnumero) AS asiakas_presta_id,
                GROUP_CONCAT(asiakas.tunnus) AS asiakas_pupe_id,
                concat(asiakashinta.tuoteno, '|||', asiakashinta.hinta) AS group_name, 
                asiakashinta.alkupvm, asiakashinta.loppupvm
              FROM asiakashinta
                JOIN asiakas ON ((asiakas.yhtio = asiakashinta.yhtio AND asiakas.piiri = asiakashinta.piiri)
                  OR (asiakas.yhtio = asiakashinta.yhtio AND asiakas.tunnus = asiakashinta.asiakas))
                JOIN yhteyshenkilo ON (yhteyshenkilo.yhtio = asiakashinta.yhtio AND yhteyshenkilo.liitostunnus = asiakas.tunnus) 
                LEFT JOIN tuote on (tuote.yhtio = asiakashinta.yhtio AND tuote.tuoteno = asiakashinta.tuoteno) 
              WHERE asiakashinta.yhtio = '{$yhtio}'
                AND asiakashinta.piiri != '' AND yhteyshenkilo.ulkoinen_asiakasnumero > '' 
                AND if(asiakashinta.alkupvm  = '0000-00-00', '0001-01-01', asiakashinta.alkupvm)  <= current_date 
                AND if(asiakashinta.loppupvm = '0000-00-00', '9999-12-31', asiakashinta.loppupvm) >= current_date 
                AND asiakashinta.hinta > 0 
                AND tuote.status != 'P' 
                AND tuote.nakyvyys != '' 
                AND asiakashinta.muutospvm between date_sub(now(),INTERVAL '$days' DAY) and now()
              GROUP by group_name";
    $price_targets_q = pupe_query($query);
    $prices_targets = array();
    while ($price_targets = mysql_fetch_assoc($price_targets_q)) {
      $prices_targets[] = $price_targets;
    }

    $query = "SELECT
                asiakashinta.tuoteno,
                asiakashinta.alkupvm,
                asiakashinta.loppupvm,
                asiakashinta.minkpl,
                asiakashinta.hinta,
                asiakashinta.valkoodi,
                avainsana.selitetark_5 AS presta_customergroup_id,
                yhteyshenkilo.ulkoinen_asiakasnumero AS presta_customer_id,
                'asiakashinta' AS tyyppi
              FROM asiakashinta
                LEFT JOIN avainsana ON (avainsana.yhtio = asiakashinta.yhtio
                  AND avainsana.selite           = asiakashinta.asiakas_ryhma
                  AND avainsana.laji             = 'ASIAKASRYHMA')
                LEFT JOIN yhteyshenkilo ON (yhteyshenkilo.yhtio = asiakashinta.yhtio
                  AND yhteyshenkilo.liitostunnus = asiakashinta.asiakas) 
                LEFT JOIN tuote on (tuote.yhtio = asiakashinta.yhtio 
                  AND tuote.tuoteno = asiakashinta.tuoteno) 
              WHERE asiakashinta.yhtio         = '{$yhtio}' 
                AND asiakashinta.asiakas_ryhma != '' AND avainsana.selitetark_5 != '' AND asiakashinta.tuoteno != '' 
                AND if(asiakashinta.alkupvm  = '0000-00-00', '0001-01-01', asiakashinta.alkupvm)  <= current_date
                AND if(asiakashinta.loppupvm = '0000-00-00', '9999-12-31', asiakashinta.loppupvm) >= current_date
                AND asiakashinta.hinta           > 0 
                AND tuote.status != 'P' 
                AND tuote.nakyvyys != '' 
                AND asiakashinta.muutospvm between date_sub(now(),INTERVAL '$days' DAY) and now()
              ";
    $price_groups_q = pupe_query($query);

    $prices_groups = array();
    while ($price_groups = mysql_fetch_assoc($price_groups_q)) {
      $prices_groups[] = $price_groups;
    }

    return Array(
      'piirit' => $prices_targets,
      'ryhmat' => $prices_groups
    );
  }

  public function begin($resource, $days = 7)
  {

    if ($resource == 'prepare' or $resource == 'all') {
      $this->setCategoryPupesoftIds();
    }

    if ($resource == 'categories' or $resource == 'all') {
      $this->pupesoft_categories = $this->categoryTreeBuild();
      $this->updatePrestashopCategories($this->pupesoft_categories);
    }

    if ($resource == 'products' or $resource == 'all') {
      $this->pupesoft_products = $this->getPupesoftProducts($days);
      $this->prestashop_products = $this->getPrestashopProducts($this->pupesoft_products);
      $this->updatePrestashopProducts($this->prestashop_products, $this->pupesoft_products['products']);
    }

    if ($resource == 'customers' or $resource == 'all') {
      $pupesoft_customers = $this->getPupesoftCustomers($days);
      $prestashop_addresses = $this->setPrestashopAddresses($pupesoft_customers);
    }

    if ($resource == 'prices' or $resource == 'all') {
      $this->pupesoft_products = $this->getPupesoftProducts(999999);
      $this->prestashop_products = $this->getPrestashopProducts($this->pupesoft_products);
      $this->setPrestashopPrices($this->getPupesoftPrices($days), $this->getPupesoftCustomers(999999), $this->prestashop_products);
    }

    if ($resource == 'orders') {
      $this->prestashop_orders = $this->getPrestashopOrders($days);
    }

    if ($resource == 'clean' or $resource == 'products' or $resource == 'stocks') {
      global $kukarow, $yhtiorow;
      $kukarow = $this->kukarow;
      $yhtiorow = $this->yhtiorow;

      $all_products = $this->getPupesoftProducts(99999);
      $presta_products_opt = Array(
        'resource' => 'products',
        'display' => 'full'
      );

      $all_products = $all_products['products'];
  
      $products = $this->rest->get($presta_products_opt);
      foreach ($products->products->product as $product) {
        $product_ref = $product->reference->__toString();

        if(!isset($all_products[$product_ref])) {
          if($product->active == 0) {
            continue;
          }
          $xml = $this->rest->get(Array(
            'resource' => 'products',
            'id' => $product->id->__toString(),
          ));
 
          if ($productFields = $xml->product->children()) {
            unset($productFields->associations->product_bundle, $productFields->description_short, $productFields->manufacturer_name, $productFields->quantity, $productFields->id_shop_default, $productFields->id_default_image, $productFields->id_default_combination, $productFields->position_in_category, $productFields->type, $productFields->pack_stock_type);
            $productFields->active = 0;
            $updatedXml = $this->rest->edit(Array(
              'resource' => 'products',
              'id' => (int) $productFields->id,
              'putXml' => $xml->asXML(),
            ));
          }
        } else if($resource == 'stocks') {
          list(, , $stock) = saldo_myytavissa($product_ref, '', $this->warehouses);
          $this->setStocks($product->id->__toString(), $stock);
        }
      }
    }
  }

  public function setPrestashopPrice($group_id, $_group_price, $_presta_product, $alkupvm, $loppupvm)
  {

    $existing_price_ok = Array(
      'resource' => 'specific_prices',
      'filter[id_group]' => '[' . $group_id . ']',
      'filter[id_product]' => '[' . $_presta_product . ']'
    );

    $found = false;

    if ($existing_price_checker = $this->rest->get($existing_price_ok)) {
      $existing_price_check = $existing_price_checker->specific_prices;
      if ($existing_price_check->specific_price) {
        $id = $existing_price_check->specific_price->attributes()->id->__toString();
        $found = true;
        $xml = $this->rest->get(Array(
          'resource' => 'specific_prices',
          'id' => $id,
        ));
        $specific_pricesFields = $xml->specific_price->children();
        unset($specific_pricesFields->date_upd);
      }
    }

    if (!$found) {
      $xml = $this->rest->get(Array('url' => $this->url . 'api/specific_prices?schema=synopsis'));
      $specific_pricesFields = $xml->specific_price->children();
    }

    $specific_pricesFields->id_shop = 0;
    $specific_pricesFields->id_cart = 0;
    $specific_pricesFields->id_product = $_presta_product;
    $specific_pricesFields->id_currency = 1;
    $specific_pricesFields->id_country = 0;
    $specific_pricesFields->id_group = $group_id;
    $specific_pricesFields->price = $_group_price;
    $specific_pricesFields->id_customer = 0;
    $specific_pricesFields->from_quantity  = 1;
    $specific_pricesFields->reduction = "0.000000";
    $specific_pricesFields->reduction_tax = 0;
    $specific_pricesFields->reduction_type = 'amount';
    $specific_pricesFields->from = $alkupvm;
    $specific_pricesFields->to = $loppupvm;
    
    if (!$found) {
      unset($specific_pricesFields->id);
      $specific_prices = Array(
        'resource' => 'specific_prices',
        'postXml' => $xml->asXML(),
      );

      if ($createdXml = $this->rest->add($specific_prices)) {
        if ($this->errors_found($group_id, $createdXml)) {
          return;
        }
      }
    } else {
      $updatedXml = $this->rest->edit(Array(
        'resource' => 'specific_prices',
        'id' => $id,
        'putXml' => $xml->asXML(),
      ));
    } 
  }

  public function setPrestashopPrices($specific_prices, $pupesoft_customers, $prestashop_products)
  {

    if (!empty($specific_prices['piirit'])) {
      $data = $specific_prices['piirit'];
      foreach ($data as $info_k => $info) {
        $_group_data = explode('|||', $info['group_name']);
        $_group_price = round((float) $_group_data[1], 2);
        $data[$info_k]['tuoteno'] = $_group_data[0];
        if(!isset($prestashop_products['found'][$data[$info_k]['tuoteno']])) {
          continue;
        }
        $_group_tuoteno = mb_substr(preg_replace('/[^A-Za-z0-9\-]/', '', $_group_data[0]), 0, 26);
        $data[$info_k]['group_name'] = $_group_tuoteno . '|' . $_group_price;
        $data[$info_k]['price'] = $_group_price;
        $info['group_name'] = htmlentities($info['group_name']);
        if(mb_strlen($info['group_name']) > 32) {
          $info['group_name'] = str_replace('.000000', '', $info['group_name']);
        }
        $groups = Array(
          'resource' => 'groups',
          'filter[name]' => $info['group_name']
        );
        $group_presta = $this->rest->get($groups);
        $checker = $group_presta->groups->children();
        
        if (!empty($checker)) {
          $group_id = $checker->group->attributes()->id->__toString();
        } else {
          $group_id = $this->setPupesoftCustomerGroup(Array('selite' => $info['group_name']));
        }
        $all_groups[$data[$info_k]['group_name']] = $group_id;
      }

      $add_customer_groups = array();

      foreach ($data as $info) {
        $_group_price = $info['price'];
        $_group_tuoteno = $info['tuoteno'];
        if(!isset($prestashop_products['found'][$_group_tuoteno])) {
          continue;
        }
        $group_id = $all_groups[$info['group_name']];
        $_group_customers = explode(',', $info['asiakas_presta_id']);
        $_presta_product = $prestashop_products['found'][$_group_tuoteno];

        foreach ($_group_customers as $_group_customer) {
          if (!isset($pupesoft_customers[$_group_customer])) {
            continue;
          }
          
          foreach ($pupesoft_customers[$_group_customer] as $cus) {
            $add_customer_groups[$_group_customer]['cus'] = $cus;
            $add_customer_groups[$_group_customer]['ids'][] = $group_id;
          }
        }

        $this->setPrestashopPrice($group_id, $_group_price, $_presta_product, $info['alkupvm'], $info['loppupvm']);
      }
      
      foreach($add_customer_groups as $add_customer_groups_id => $groups_add) {
        $this->setPupesoftCustomer($groups_add['cus'], $add_customer_groups_id, $groups_add['ids']);
      }
    }

    if (!empty($specific_prices['ryhmat'])) {
      $data = $specific_prices['ryhmat'];
      foreach ($data as $info_k => $info) {
        if(!isset($prestashop_products['found'][$info['tuoteno']])) {
          continue;
        }
        $_presta_product = $prestashop_products['found'][$info['tuoteno']]; 
        $this->setPrestashopPrice($info['presta_customergroup_id'], $info['hinta'], $_presta_product, $info['alkupvm'], $info['loppupvm']);
      }
    }
    
  }

  public function setPrestashopAddresses($customers)
  {
    $yhtio = $this->yhtiorow['yhtio'];

    foreach ($customers as $addresses) {
      foreach ($addresses as $customer) {
        $customer_id = $customer['ulkoinen_asiakasnumero'];
        $asiakas_idt = array();
        foreach ($customer['osoitteet'] as $address) {
          
          $existing_address_ok = Array(
            'resource' => 'addresses',
            'filter[id_customer]' => '[' . $customer_id . ']',
            'filter[dni]' => '[' . $address['asiakas_id'] . ']'
          );

          $found = false;

          if ($existing_address_checker = $this->rest->get($existing_address_ok)) {

            $existing_address_check = $existing_address_checker->addresses;

            if ($existing_address_check->address) {
              $id = $existing_address_check->address->attributes()->id->__toString();
              $found = true;
              $xml = $this->rest->get(Array(
                'resource' => 'addresses',
                'id' => $id,
              ));
              $addressesFields = $xml->address->children();
              unset($addressesFields->date_upd);
            }
          }

          if (!$found) {
            $xml = $this->rest->get(Array('url' => $this->url . 'api/addresses?schema=synopsis'));
            $addressesFields = $xml->address->children();
          }

          $addressesFields->id_customer = $customer_id;

          if ($address['maa'] or (isset($customer['asiakas_maa']) and $customer['asiakas_maa'])) {
            if ($addressesFields->id_country = $this->getPrestashopCountry($address['maa'], true)) {
            } else {
              $addressesFields->id_country = $this->getPrestashopCountry($customer['asiakas_maa']);
            }
          } else {
            $addressesFields->id_country = $this->getPrestashopCountry('FI');
          }

          $addressesFields->alias = $address['nimi'];

          $addressesFields->firstname = '-';
          if (!preg_match("/^[a-zA-Z\s\ä\Ä\ö\Ö]+$/", $address['nimi'])) {
            $address['nimi'] = 'Tuntematon';
          }
          $addressesFields->lastname = str_replace(array(".","@"), " ", $address['nimi']);
          $addressesFields->address2 = $address['asiakas_nimitark'];
          $addressesFields->vat_number = $address['ytunnus'];
          if (!$address['osoite']) {
            $address['osoite'] = $address['laskutus_osoite'];
          }
          if(!$address['osoite'] and !$address['laskutus_osoite']) {
            continue;
          }
          if (!$address['postino']) {
            $address['postino'] = $address['laskutus_postino'];
          }
          if (!$address['postitp']) {
            $address['postitp'] = $address['laskutus_postitp'];
          }
          $addressesFields->address1 = $address['osoite'];
          $addressesFields->postcode = $address['postino'];
          if(!$address['postitp'] and $address['postino']) {
            $address['postitp'] = $address['postino'];
          } else if(!$address['postitp']) {
            $address['postitp'] = "-";
          }
          if(!$address['gsm'] and $address['gsm2']) {
            $address['gsm'] = $address['gsm2'];
          }
          if(!$address['puh'] and $address['puh2']) {
            $address['puh'] = $address['puh2'];
          }

          $addressesFields->city = $address['postitp'];
          $addressesFields->company = $address['asiakas_nimi'];
          $addressesFields->phone = preg_replace('/[^\dxX+]/', '', $address['puh']);
          $addressesFields->phone_mobile = preg_replace('/[^\dxX+]/', '', $address['gsm']);

          $addressesFields->dni = $address['asiakas_id'];
          if ($address['laskutus_osoite'] or $address['laskutus_postino'] or $address['laskutus_postitp']) {
            $msg = "Laskutusosoite:\n";
            if ($address['laskutus_osoite']) {
              $msg .= $address['laskutus_osoite'] . "\n";
            }
            if ($address['laskutus_postino']) {
              $msg .= $address['laskutus_postino'] . "\n";
            }
            if ($address['laskutus_postitp']) {
              $msg .= $address['laskutus_postitp'] . "\n";
            }
            $addressesFields->other = $msg;
          }

          if (!$found) {
            $addresses = Array(
              'resource' => 'addresses',
              'postXml' => $xml->asXML(),
            );
            $createdXml = $this->rest->add($addresses);
            $id_got = (string) $createdXml->address->children()->id;
          } else {
            $updatedXml = $this->rest->edit(Array(
              'resource' => 'addresses',
              'id' => $id,
              'putXml' => $xml->asXML(),
            ));
            $id_got = $id;
          }

          $asiakas_idt[$id_got] = $id_got;
        }

        $existing_address_ok_clean = Array(
          'resource' => 'addresses',
          'filter[id_customer]' => '[' . $customer_id . ']'
        );

        if ($existing_address_checker_cl = $this->rest->get($existing_address_ok_clean)) {
          $existing_address_check_cl = $existing_address_checker_cl->addresses->address;
      
          foreach ($existing_address_check_cl as $address_cl) {
            $address_cl_id = (string) $address_cl->attributes()->id;

            $xml = $this->rest->get(Array(
              'resource' => 'addresses',
              'id' => $address_cl_id,
            ));
            $addressesFields = $xml->address->children();
            $addressesFieldsDni = (string) $addressesFields->dni;

            if(!isset($asiakas_idt[$address_cl_id]) and $addressesFieldsDni) {
              $this->rest->delete(Array(
                'resource' => 'addresses',
                'id' => $address_cl_id
              ));
            }
          }
        }
      }
    }
  }
}
$edi = new Edi();
$webService = new PrestaShopWebservice($presta17_api_url, $presta17_api_pass, $presta17_api_debug);
$execute = new Presta17RestApi(
  $yhtiorow,
  $webService,
  $presta17_api_url,
  $presta_varastot,
  $edi,
  $presta17_api_customer,
  $presta17_api_edipath,
  $presta17_api_payment_rule,
  $presta17_api_ovt
);

$execute->begin($resource, $days);
