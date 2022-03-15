<?php

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
  die("Tätä scriptiä voi ajaa vain komentoriviltä!");
}

$pupe_root_polku = dirname(dirname(dirname(__FILE__)));

ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.$pupe_root_polku);
error_reporting(E_ALL ^ E_DEPRECATED);
ini_set("display_errors", 1);
ini_set("max_execution_time", 0); // unlimited execution time
ini_set("memory_limit", "2G");

date_default_timezone_set('Europe/Helsinki');

require "inc/connect.inc";
require "inc/functions.inc";
require_once('./vendor/autoload.php');

if (!isset($argv[1]) || !$argv[1]) {
  echo t("Anna yhtio");
  exit;
}
if (!isset($argv[2]) || !$argv[2]) {
  echo t("Mitä ajetaan?");
  exit;
}

// ytiorow. Jos ei l?ydy, lopeta cronä
$yhtiorow = hae_yhtion_parametrit(pupesoft_cleanstring($argv[1]));
if (!$yhtiorow) {
  echo "Vaara yhtio";
  exit;
}

$resource = pupesoft_cleanstring($argv[2]);

$days = pupesoft_cleanstring($argv[3]);

// Logitetaan ajo
cron_log();

/*
  Main class
*/
class Presta17RestApi
{


  /*
    Default variables and values in the class
  */
  public function __construct($yhtiorow, $rest, $url)
  {
    $php_cli = true;
    $this->kukarow = hae_kukarow('admin', $yhtiorow['yhtio']);
    $this->yhtiorow  = $yhtiorow;
    $this->rest = $rest;
    $this->url = $url;
  }

  public function getPupesoftProducts($days)
  {
    $yhtio = $this->yhtiorow['yhtio'];
    $query = "SELECT * from tuote 
              JOIN puun_alkio on (tuote.yhtio =  puun_alkio.yhtio and tuote.tuoteno = puun_alkio.liitos) 
              JOIN dynaaminen_puu on (puun_alkio.yhtio = dynaaminen_puu.yhtio and puun_alkio.puun_tunnus = dynaaminen_puu.tunnus) 
              where tuote.yhtio='$yhtio' and (
              (tuote.muutospvm between date_sub(now(),INTERVAL '$days' DAY) and now()) 
              or 
              (puun_alkio.muutospvm between date_sub(now(),INTERVAL '$days' DAY) and now())
              )
              ";

    $products = pupe_query($query);
    $results = array();

    while ($product = mysql_fetch_assoc($products)) {
      $osastores = t_avainsana("OSASTO", "", "and avainsana.selite ='$product[osasto]'", "'$yhtio'");
      $osastorow = mysql_fetch_assoc($osastores);
  
      if ($osastorow['selitetark'] != "") {
        $product['osasto_nimike'] = $osastorow['selitetark'];
      }
  
      $tryres = t_avainsana("TRY", "", "and avainsana.selite ='$product[try]'", "'$yhtio'");
      $tryrow = mysql_fetch_assoc($tryres);
  
      if ($tryrow['selitetark'] != "") {
        $product['try_nimike'] = $tryrow['selitetark'];
      }

      $results[$product['tuoteno']][] = $product;
    }

    return
      array(
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
      $presta_products_opt = [
        'resource' => 'products',
        'filter[reference]'  => '['.$pupesoft_products_id.']'
      ];

      $prestashop_products_chunk = $this->rest->get($presta_products_opt);
      if ($prestashop_products_chunk->products->children()) {
        $prestashop_products_chunk = json_encode($prestashop_products_chunk);
        $prestashop_products_chunk = json_decode($prestashop_products_chunk, true);
        foreach ($prestashop_products_chunk['products']['product'] as $found_product) {
          $prestashop_products[] = $found_product['id'];
        }
      } else {
        $missing_products[$pupesoft_products_id] = $pupesoft_products['products'][$pupesoft_products_id];
      }
    }

    return array(
      "found" => $prestashop_products,
      "missing" => $missing_products
    );
  }


  public function setPrestashopManufacturer($manufacturer)
  {
    $blankXml = $this->rest->get(['url' => $this->url.'api/manufacturers?schema=synopsis']);
    $manufacturerFields = $blankXml->manufacturer->children();
    $manufacturerFields->name = (string) $manufacturer;
    $manufacturerFields->active = 1;
    unset($manufacturerFields->link_rewrite);
    $manufacturers = [
      'resource' => 'manufacturers',
      'postXml' => $blankXml->asXML(),
    ];
    $createdXml = $this->rest->add($manufacturers);
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
    $text = strtolower($text);
    if (empty($text)) {
      return 'n-a';
    }
    return $text;
  }


  public function getPrestashopProductFeature($product_feature)
  {
    $product_feature_name = $product_feature;
    $product_features = [
      'resource' => 'product_features',
      'filter[name]'  => '['.$product_feature.']'
    ];
    $product_feature = $this->rest->get($product_features);
    $checker = $product_feature->product_features->children();
    if (empty($checker)) {
      $product_feature_id = $this->setPrestashopProductFeature($product_feature_name);
    } else {
      $product_feature_id = $product_feature->product_features->product_feature;
      $product_feature_id = json_encode($product_feature_id);
      $product_feature_id = json_decode($product_feature_id, true);
      $product_feature_id = $product_feature_id['@attributes']['id'];
    }

    return $product_feature_id;
  }

  public function setPrestashopProductFeature($product_feature)
  {
    $blankXml = $this->rest->get(['url' => $this->url.'api/product_features?schema=synopsis']);
    $product_featureFields = $blankXml->product_feature->children();
    $product_featureFields->name = (string) $product_feature;
    $product_features = [
      'resource' => 'product_features',
      'postXml' => $blankXml->asXML(),
    ];
    $createdXml = $this->rest->add($product_features);
    $newProductFeatureFields = $createdXml->product_feature->children();
    return $newProductFeatureFields->id;
  }

  public function setPrestashopCategory($cat_data, $id=false, $parent=false, $position=false)
  {
    if (!$cat_data->nimi or $cat_data->nimi == '') {
      return;
    }
    $category_name = $cat_data->nimi;

    $category_id = $cat_data->node_tunnus;
    if ($id) {
      $xml = $this->rest->get([
        'resource' => 'categories',
        'id' => $id,
      ]);
      if ($categoryFields = $xml->category->children()) {
        $categoryFields->name->language[0][0] = $category_name;
        $categoryFields->name->link_rewrite[0][0] = $this->slugify($category_name);
        $categoryFields->id_parent = $parent;
        $categoryFields->position = $position;
        unset($categoryFields->level_depth);
        unset($categoryFields->nb_products_recursive);
        $updatedXml = $this->rest->edit([
          'resource' => 'categories',
          'id' => (int) $categoryFields->id,
          'putXml' => $xml->asXML(),
        ]);
        return $categoryFields->id;
      }
    } else {
      $blankXml = $this->rest->get(['url' => $this->url.'api/categories?schema=synopsis']);
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
    unset($categoryFields->level_depth);
    unset($categoryFields->nb_products_recursive);
    unset($categoryFields->id);

    $categoryFields->active = 1;
    
    $categories = [
      'resource' => 'categories',
      'postXml' => $blankXml->asXML(),
    ];
    $createdXml = $this->rest->add($categories);
    $newCategoryFields = $createdXml->category->children();
    return $newCategoryFields->id;
  }


  public function getPrestashopManufacturer($manufacturer)
  {
    $manufacturer_name = $manufacturer;
    $manufacturers = [
      'resource' => 'manufacturers',
      'filter[name]'  => '['.$manufacturer.']'
    ];
    $manufacturer = $this->rest->get($manufacturers);
    $checker = $manufacturer->manufacturers->children();
    if (empty($checker)) {
      $manufacturer_id = $this->setPrestashopManufacturer($manufacturer_name);
    } else {
      $manufacturer_id = $manufacturer->manufacturers->manufacturer;
      $manufacturer_id = json_encode($manufacturer_id);
      $manufacturer_id = json_decode($manufacturer_id, true);
      $manufacturer_id = $manufacturer_id['@attributes']['id'];
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

      $this->categories[$row['tunnus']] = (object) array(
        "parent_nimi"   => $row['parent_nimi'],
        "nimi"          => $row['nimi'],
        "node_tunnus"   => $row['tunnus'],
        "parent_tunnus" => $row['parent_tunnus'],
        "syvyys"        => $row['syvyys'],
        "sijainti"      => $row['lft'],
      );

      $counter++;
    }

    foreach ($this->categories as &$i) {
      $this->nodes[] = array('data' => &$i, 'childs' => array() , 'parent' => null);
    }

    $this->cat_tree = array('data' => null, 'childs' => array() , 'parent' => null);
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


  public function getPrestashopCategory($cat_data, $id=false, $parent=false, $position=false)
  {
    $category_name = $cat_data->nimi;
    $category_tunnus = $cat_data->node_tunnus;
    if ($id and $parent and $position) {
      $categories = [
        'resource' => 'categories',
        'filter[id]'  => '['.$id.']',
        'filter[pupesoft_id]'  => '['.$category_tunnus.']',
        'filter[position]'  => '['.$position.']',
        'filter[id_parent]' => '['.$parent.']'
      ];
    } elseif ($id and !$parent and !$position) {
      $categories = [
        'resource' => 'categories',
        'filter[id]'  => '['.$id.']'
      ];
    } else {
      $categories = [
        'resource' => 'categories',
        'filter[pupesoft_id]'  => '['.$category_tunnus.']'
      ];
    }

    $category = $this->rest->get($categories);
    $checker = $category->categories->children();
    if (empty($checker) and $position and $parent) {
      $category_id = $this->setPrestashopCategory($cat_data, $id, $parent, $position);
    } elseif (empty($checker)) {
      $category_id = $this->setPrestashopCategory($cat_data);
    } else {
      $category_id = $category->categories->category;
      $category_id = json_encode($category_id);
      $category_id = json_decode($category_id, true);
      $category_id = $category_id['@attributes']['id'];
    }

    return $category_id;
  }

  public function setCategoryPupesoftIds()
  {
    $yhtio = $this->yhtiorow['yhtio'];
    $categories = [
      'resource' => 'categories',
      'display'    => 'full'
    ];

    $categories = $this->rest->get($categories);
    foreach ($categories->categories->category as $cat) {
      $cur_level = $cat->level_depth->__toString();
      if ($cur_level < 2) {
        continue;
      }
      
      $cur_id = $cat->pupesoft_id->language->__toString();


      $cur_level = $cur_level-1;
      if (!$cur_id or 1==1) {
        $cur_presta_id = $cat->id->__toString();

        $cur_name = $cat->name->language->__toString();
        $query = "SELECT tunnus
                  FROM dynaaminen_puu
                    WHERE yhtio = '{$yhtio}' 
                    AND syvyys    = '{$cur_level}' 
                    AND nimi = '{$cur_name}'
                  LIMIT 1";
        $result = pupe_query($query);

        $result = mysql_fetch_assoc($result);

        $xml = $this->rest->get([
          'resource' => 'categories',
          'id' => $cur_presta_id
        ]);

        $categoryFields = $xml->category->children();

        if ($result) {
          $categoryFields->pupesoft_id->language[0] = $result['tunnus'];
          $categoryFields->active = 1;
        } else {
          $categoryFields->active = 0;
          $categoryFields->pupesoft_id->language[0] = '';
        }

        unset($categoryFields->level_depth);
        unset($categoryFields->nb_products_recursive);

        $updatedXml = $this->rest->edit([
          'resource' => 'categories',
          'id' => (int) $categoryFields->id,
          'putXml' => $xml->asXML(),
        ]);
      }
    }
  }
  
  public function updatePrestashopCategory($cat)
  {
    $cat_data = $cat['data'];

    $parent_data = (object) array(
      "nimi" => $cat['data']->parent_nimi,
      "node_tunnus" => $cat['data']->parent_tunnus
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
    $product_feature_values = [
      'resource' => 'product_feature_values',
      'filter[value]'  => '['.$product_feature.']',
      'filter[id_feature]' => '['.$product_feature_id.']'
    ];
    $product_feature_value = $this->rest->get($product_feature_values);

    $checker = $product_feature_value->product_feature_values->children();
    if (empty($checker)) {
      $product_feature_value_id = $this->setPrestashopProductFeatureValues($product_feature_name, $product_feature_id);
    } else {
      $product_feature_value_id = $product_feature_value->product_feature_values->product_feature_value;
      $product_feature_value_id = json_encode($product_feature_value_id);
      $product_feature_value_id = json_decode($product_feature_value_id, true);
      $product_feature_value_id = $product_feature_value_id['@attributes']['id'];
    }

    return $product_feature_value_id;
  }

  public function setPrestashopProductFeatureValues($product_feature_value, $product_feature_id)
  {
    $blankXml = $this->rest->get(['url' => $this->url.'api/product_feature_values?schema=synopsis']);
    $product_feature_valueFields = $blankXml->product_feature_value->children();
    $product_feature_valueFields->value = (string) $product_feature_value;
    $product_feature_valueFields->id_feature = $product_feature_id;
    $product_feature_values = [
      'resource' => 'product_feature_values',
      'postXml' => $blankXml->asXML(),
    ];
    $createdXml = $this->rest->add($product_feature_values);
    $newProductFeatureValuesFields = $createdXml->product_feature_value->children();
    return $newProductFeatureValuesFields->id;
  }


  public function updatePrestashopProducts($prestashop_products, $pupesoft_products)
  {
    foreach ($prestashop_products['found'] as $prestashop_product) {
      $xml = $this->rest->get([
        'resource' => 'products',
        'id' => $prestashop_product,
      ]);

      if ($productFields = $xml->product->children()) {
        $pupesoft_products_arr = $pupesoft_products[(string) $productFields->reference];
      } else {
        $pupesoft_products_arr = array();
      }

      foreach ($pupesoft_products_arr as $pupesoft_product) {
        $cat_data[] = (object) array(
          "nimi" => '',
          "node_tunnus" => $pupesoft_product['puun_tunnus']
        );
      }

      $pupesoft_product = $pupesoft_products_arr[0];

      $pupesoft_product['try_nimike'] = $pupesoft_product['try_nimike'];
      $pupesoft_product['tuotemerkki'] = $pupesoft_product['tuotemerkki'];

      if (isset($pupesoft_product['tuotemerkki'])
          and $pupesoft_product['tuotemerkki']
          and $pupesoft_product['tuotemerkki'] != ""
          and $pupesoft_product['tuotemerkki'] != null) {
        $productFields->id_manufacturer = $this->getPrestashopManufacturer($pupesoft_product['tuotemerkki']);
      } else {
        $productFields->id_manufacturer = "";
      }

      unset($productFields->associations->product_features->product_feature);

      //Yksikkö
      $new_feat=$productFields->associations->product_features->addChild('product_feature');
      $new_feat->addChild('id', $this->getPrestashopProductFeature('Yksikkö'));
      $product_val = $this->getPrestashopProductFeatureValues($pupesoft_product['yksikko'], $this->getPrestashopProductFeature('Yksikkö'));
      $new_feat->addChild('id_feature_value', $product_val);

      //Myyntierä 
      $new_feat=$productFields->associations->product_features->addChild('product_feature');
      $new_feat->addChild('id', $this->getPrestashopProductFeature('Myyntierä'));
      $product_val = $this->getPrestashopProductFeatureValues($pupesoft_product['myynti_era'], $this->getPrestashopProductFeature('Myyntierä'));
      $new_feat->addChild('id_feature_value', $product_val);

      unset($productFields->associations->categories->category->id);
      foreach ($cat_data as $cat_data_single) {
        $productFields->associations->categories->category->addChild('id', $this->getPrestashopCategory($cat_data_single));
      }

      $productFields->id_category_default = $this->getPrestashopCategory($cat_data[0]);

      $productFields->price = $pupesoft_product['myyntihinta'];
      $productFields->name->language[0] = $pupesoft_product['nimitys'];
      $productFields->link_rewrite->language[0] = $this->slugify($pupesoft_product['nimitys']);
      $productFields->meta_title->language[0] = $pupesoft_product['nimitys'];

      $productFields->meta_keywords->language[0] = $pupesoft_product['try_nimike'];
      $productFields->description_short->language[0] = mb_strimwidth($pupesoft_product['lyhytkuvaus'], 0, 400, '...', 'utf-8');
      $productFields->meta_description->language[0] = mb_strimwidth(str_replace("=", "-", $pupesoft_product['lyhytkuvaus']), 0, 200, '...');
      $productFields->description->language[0] = $pupesoft_product['kuvaus'];
      
      $productFields->minimal_quantity = (int) $pupesoft_product['myynti_era'];
      $productFields->ean13 = mb_strimwidth(preg_replace('/\s+/', '', $pupesoft_product['eankoodi']), 0, 13, '', 'utf-8');
      $productFields->state = 1;
      
      if ($pupesoft_product['status'] != 'A') {
        $productFields->active = 0;
        $productFields->available_for_order = 0;
      } else {
        $productFields->active = 1;
        $productFields->available_for_order = 1;
      }

      $productFields->height = (float) $pupesoft_product['tuotekorkeus'];
      $productFields->width = (float) $pupesoft_product['tuoteleveys'];
      $productFields->depth = (float) $pupesoft_product['tuotesyvyys'];
      $productFields->weight = (float) $pupesoft_product['tuotemassa'];

      unset($productFields->manufacturer_name);
      unset($productFields->quantity);
      unset($productFields->id_shop_default);
      unset($productFields->id_default_image);

      unset($productFields->id_default_combination);
      unset($productFields->position_in_category);
      unset($productFields->type);
      unset($productFields->pack_stock_type);
      //unset($productFields->date_add);
      unset($productFields->date_upd);
      
      $updatedXml = $this->rest->edit([
          'resource' => 'products',
          'id' => (int) $productFields->id,
          'putXml' => $xml->asXML(),
        ]);
    }

    unset($productFields);
    unset($updatedXml);
    unset($xml);
    unset($pupesoft_product);
    unset($cat_data);
    unset($skip_product);

    foreach ($prestashop_products['missing'] as $pupesoft_products_arr) {
      foreach ($pupesoft_products_arr as $pupesoft_product) {
        $cat_data[] = (object) array(
          "nimi" => '',
          "node_tunnus" => $pupesoft_product['puun_tunnus']
        );
      }
      $pupesoft_product = $pupesoft_products_arr[0];
      $blankXml = $this->rest->get(['url' => $this->url.'api/products?schema=synopsis']);
      $productFields = $blankXml->product->children();

      if (isset($pupesoft_product['tuotemerkki'])
      and $pupesoft_product['tuotemerkki']
      and $pupesoft_product['tuotemerkki'] != ""
      and $pupesoft_product['tuotemerkki'] != null) {
        $productFields->id_manufacturer = $this->getPrestashopManufacturer($pupesoft_product['tuotemerkki']);
      } else {
        $productFields->id_manufacturer = "";
      }

      foreach ($cat_data as $cat_data_single) {
        $productFields->associations->categories->category->addChild('id', $this->getPrestashopCategory($cat_data_single));
      }

      $productFields->id_category_default = $this->getPrestashopCategory($cat_data[0]);

      $productFields->price = $pupesoft_product['myyntihinta'];

      $productFields->name->language[0] = $pupesoft_product['nimitys'];
      $productFields->meta_title->language[0] = $pupesoft_product['nimitys'];
      $productFields->link_rewrite->language[0] = $this->slugify($pupesoft_product['nimitys']);
      
      $productFields->description_short->language[0] = mb_strimwidth($pupesoft_product['lyhytkuvaus'], 0, 400, '...', 'utf-8');
      $productFields->meta_description->language[0] = mb_strimwidth(str_replace("=", "-", $pupesoft_product['lyhytkuvaus']), 0, 200, '...');
      $productFields->description->language[0] = $pupesoft_product['kuvaus'];

      $productFields->minimal_quantity = (int) $pupesoft_product['myynti_era'];
      $productFields->ean13 = mb_strimwidth(preg_replace('/\s+/', '', $pupesoft_product['eankoodi']), 0, 13, '', 'utf-8');
      $productFields->state = 1;
      $productFields->reference = $pupesoft_product['tuoteno'];
      
      if ($pupesoft_product['status'] != 'A') {
        $productFields->active = 0;
        $productFields->available_for_order = 0;
      } else {
        $productFields->active = 1;
        $productFields->available_for_order = 1;
      }

      $productFields->height = (float) $pupesoft_product['tuotekorkeus'];
      $productFields->width = (float) $pupesoft_product['tuoteleveys'];
      $productFields->depth = (float) $pupesoft_product['tuotesyvyys'];
      $productFields->weight = (float) $pupesoft_product['tuotemassa'];

      unset($productFields->id);
      unset($productFields->manufacturer_name);
      unset($productFields->quantity);
      unset($productFields->id_shop_default);
      unset($productFields->id_default_image);

      unset($productFields->id_default_combination);
      unset($productFields->position_in_category);
      unset($productFields->type);
      unset($productFields->pack_stock_type);
      unset($productFields->date_add);
      unset($productFields->date_upd);
      
      $updatedXml = $this->rest->add([
        'resource' => 'products',
        'postXml' => $blankXml->asXML(),
      ]);
    }
  }


  public function begin($resource, $days=7)
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
  }
}


$webService = new PrestaShopWebservice($presta17_api_url, $presta17_api_pass, $presta17_api_debug);
$execute = new Presta17RestApi(
  $yhtiorow,
  $webService,
  $presta17_api_url
);

$execute->begin($resource, $days);
