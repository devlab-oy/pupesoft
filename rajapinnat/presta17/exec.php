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

// ytiorow. Jos ei l?ydy, lopeta cron¨
$yhtiorow = hae_yhtion_parametrit(pupesoft_cleanstring($argv[1]));
if (!$yhtiorow) {
  echo "Vaara yhtio";
  exit;
}

$resource = pupesoft_cleanstring($argv[2]);

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

  public function getPupesoftProducts()
  {
    $yhtio = $this->yhtiorow['yhtio'];
    $query = "SELECT * from tuote where yhtio='$yhtio' and muutospvm between date_sub(now(),INTERVAL 2 DAY) and now()";
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

      $results[$product['tuoteno']] = $product;
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
    $pupesoft_products_ids_chunks = array_chunk($pupesoft_products_ids, 500);
    $prestashop_products = array();
    foreach ($pupesoft_products_ids_chunks as $pupesoft_products_ids_chunk) {
      $presta_products_opt = [
        'resource' => 'products',
        'filter[reference]'  => '['.implode("|", $pupesoft_products_ids_chunk).']'
      ];
      $prestashop_products_chunk = $this->rest->get($presta_products_opt);
      $prestashop_products_chunk = json_encode($prestashop_products_chunk);
      $prestashop_products_chunk = json_decode($prestashop_products_chunk, true);

      foreach ($prestashop_products_chunk['products']['product'] as $found_product) {
        $prestashop_products[] = $found_product['@attributes']['id'];
      }
    }
    return $prestashop_products;
  }

  public function setPrestashopManufacturer($manufacturer)
  {
    $blankXml = $this->rest->get(['url' => $this->url.'api/manufacturers?schema=blank']);
    $manufacturerFields = $blankXml->manufacturer->children();
    $manufacturerFields->name = (string) $manufacturer;
    $manufacturerFields->active = 1;
    $manufacturers = [
      'resource' => 'manufacturers',
      'postXml' => $blankXml->asXML(),
    ];
    $createdXml = $this->rest->add($manufacturers);
    $newManufacturerFields = $createdXml->manufacturer->children();
    return $newManufacturerFields->id;
  }

  public function setPrestashopCategory($category, $id=false)
  {
    if ($id) {
      $xml = $this->rest->get([
        'resource' => 'categories',
        'id' => $id,
      ]);
      if ($categoryFields = $xml->category->children()) {
      }
    }

    die();


    $blankXml = $this->rest->get(['url' => $this->url.'api/categories?schema=blank']);
    $categoryFields = $blankXml->category->children();
    $categoryFields->name = (string) $category;
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
    if (empty($manufacturer->manufacturers->children())) {
      $manufacturer_id = $this->setPrestashopManufacturer($manufacturer_name);
    } else {
      $manufacturer_id = $manufacturer->manufacturers->manufacturer;
      $manufacturer_id = json_encode($manufacturer_id);
      $manufacturer_id = json_decode($manufacturer_id, true);
      $manufacturer_id = $manufacturer_id['@attributes']['id'];
    }

    return $manufacturer_id;
  }

  public function getPupesoftCategories()
  {
    $yhtio = $this->yhtiorow['yhtio'];

    $query = "SELECT max(syvyys) as depth 
              FROM dynaaminen_puu 
              WHERE yhtio = '{$yhtio}' 
              AND laji = 'tuote'
              LIMIT 1";
    $result = pupe_query($query);
    $max_depth = mysql_fetch_assoc($result);
    $max_depth = $max_depth['depth'];
    $categories = array();
    for ($i = 0; $i <= $max_depth; $i++) {
      $query = "SELECT node.nimi,
                node.koodi, node.syvyys, node.lft, 
                node.tunnus AS node_tunnus,
                (SELECT parent.tunnus
                FROM dynaaminen_puu AS parent
                  WHERE parent.yhtio = node.yhtio
                  AND parent.laji    = node.laji
                  AND parent.lft     < node.lft
                  AND parent.rgt     > node.rgt
                  ORDER by parent.lft DESC
                  LIMIT 1) as parent_tunnus
                  FROM dynaaminen_puu AS node
                  WHERE node.yhtio    = '{$yhtio}' 
                  AND syvyys = $i 
                  AND node.laji       = 'tuote'
                  ORDER BY node.lft ASC";
      $result = pupe_query($query);

      while ($category = mysql_fetch_assoc($result)) {
        $add_category = array(
          "koodi"         => $category['koodi'],
          "nimi"          => $category['nimi'],
          "node_tunnus"   => $category['node_tunnus'],
          "parent_tunnus" => $category['parent_tunnus'],
          "syvyys"        => $category['syvyys'],
          "sijainti"      => $category['lft'],
        );


        if ($i <= 1) {
          $categories[$category['node_tunnus']] = $add_category;
        }
        if ($i == 2) {
          $categories[$category['parent_tunnus']]['children'][$category['node_tunnus']] = $add_category;
        }
        if ($i >= 3) {
          $cur_id = $category['parent_tunnus'];
          $query_child = "SELECT node.nimi,
                    node.koodi, node.syvyys, node.lft, 
                    node.tunnus AS node_tunnus,
                    (SELECT parent.tunnus
                    FROM dynaaminen_puu AS parent
                      WHERE parent.yhtio = node.yhtio
                      AND parent.laji    = node.laji
                      AND parent.lft     < node.lft
                      AND parent.rgt     > node.rgt
                      ORDER by parent.lft DESC
                      LIMIT 1) as parent_tunnus
                      FROM dynaaminen_puu AS node
                      WHERE node.yhtio    = '{$yhtio}' 
                      AND node.laji       = 'tuote'
                      AND node.tunnus = '{$cur_id}' 
                      ORDER BY node.lft ASC";
          $result_child = pupe_query($query_child);

          while ($category_child = mysql_fetch_assoc($result_child)) {
            if ($i == 3) {
              $categories[$category_child['parent_tunnus']]['children'][$category['parent_tunnus']]['children'][$category['node_tunnus']] = $add_category;
            } else {
              $cur_id_child = $category['node_tunnus'];
              $query_child_2 = "SELECT node.nimi,
                          node.koodi, node.syvyys, node.lft, 
                          node.tunnus AS node_tunnus,
                          node.parent_id AS parent_tunnus 
                            FROM dynaaminen_puu AS node
                            WHERE node.yhtio    = '{$yhtio}' 
                            AND syvyys = $i
                            AND node.laji       = 'tuote'
                            AND node.tunnus = '{$cur_id_child}' 
                            ORDER BY node.lft ASC";
              $result_child_2 = pupe_query($query_child_2);
  
              while ($category_child_2 = mysql_fetch_assoc($result_child_2)) {

                $categories[$category_child['parent_tunnus']]['children'][$category['node_tunnus']]['children'][$category_child['node_tunnus']]['children'][$category_child_2['node_tunnus']] = $add_category;
                echo "<pre>";
                print_r($categories[$category_child['parent_tunnus']]['children'][$category['node_tunnus']]);
                echo "</pre>";
                die();
                $cur_id_child_2 = $category_child_2['parent_tunnus'];
                $query_child_3 = "SELECT node.nimi,
                                node.koodi, node.syvyys, node.lft, 
                                node.tunnus AS node_tunnus,
                                (SELECT parent.tunnus
                                FROM dynaaminen_puu AS parent
                                  WHERE parent.yhtio = node.yhtio
                                  AND parent.laji    = node.laji
                                  AND parent.lft     < node.lft
                                  AND parent.rgt     > node.rgt
                                  ORDER by parent.lft DESC
                                  LIMIT 1) as parent_tunnus
                                  FROM dynaaminen_puu AS node
                                  WHERE node.yhtio    = '{$yhtio}' 
                                  AND syvyys = $i-1 
                                  AND node.laji       = 'tuote'
                                  AND node.tunnus = '{$cur_id_child_2}' 
                                  ORDER BY node.lft ASC";
                $result_child_3 = pupe_query($query_child_3);

                while ($category_child_3 = mysql_fetch_assoc($result_child_3)) {
                  $categories[$category_child['parent_tunnus']][$category['parent_tunnus']]['children'][$category['node_tunnus']]['children'][$category_child_2['node_tunnus']]['children'][$result_child_3['node_tunnus']] = $add_category;
                }
              }
            }
          }
        }

        //getPrestashopCategory($category, 0);
      }

      //$this->setPrestashopCategory($category['nimi'], 2);
    }
    echo "<pre>";
    print_r($categories);
    echo "</pre>";
    die();    
    
    return $categories;
  }

  public function getPrestashopCategoriesByDepth($depth, $parent_id)
  {
    $category_name = $category;
    $categories = [
      'resource' => 'categories',
      'filter[level_depth]'  => '['.$depth.']'
    ];
    $category = $this->rest->get($categories);
    if (empty($category->categories->children())) {
      $category_id = $this->setPrestashopCategory($category_name);
    } else {
      $category_id = $category->categories->category;
      $category_id = json_encode($category_id);
      $category_id = json_decode($category_id, true);
      $category_id = $category_id['@attributes']['id'];
    }

    return $category_id;
  }

  public function getPrestashopCategory($category, $id=false)
  {
    $category_name = $category;
    if ($id) {
      $categories = [
        'resource' => 'categories',
        'filter[id]'  => '['.$id.']'
      ];
    } else {
      $categories = [
        'resource' => 'categories',
        'filter[name]'  => '['.$category.']'
      ];
    }

    $category = $this->rest->get($categories);
    if (empty($category->categories->children())) {
      $category_id = $this->setPrestashopCategory($category_name);
    } else {
      $category_id = $category->categories->category;
      $category_id = json_encode($category_id);
      $category_id = json_decode($category_id, true);
      $category_id = $category_id['@attributes']['id'];
    }

    return $category_id;
  }

  public function compareData($pupe, $presta)
  {
    if (
      utf8_encode($pupe['nimitys']) != $presta->name or
      $pupe['myyntihinta'] != $presta->price or
      $pupe['tuotemerkki'] != $presta->manufacturer_name or
      utf8_encode($pupe['kuvaus']) != $presta->description or
      utf8_encode($pupe['lyhytkuvaus']) != $presta->description_short or
      (int) $pupe['myynti_era'] != $presta->minimal_quantity or
      $pupe['eankoodi'] != $presta->ean13 or
      $pupe['tuotekorkeus'] != $presta->height or
      $pupe['tuoteleveys'] != $presta->width or
      $pupe['tuotesyvyys'] != $presta->depth or
      $pupe['tuotemassa'] != $presta->weight or
      ($pupe['status'] != 'A' and $presta->active == 1) or
      ($pupe['status'] == 'A' and $presta->active != 1)
    ) {
      return true;
    }
    return;
  }

  public function updatePrestashopProducts($prestashop_products, $pupesoft_products)
  {
    foreach ($prestashop_products as $prestashop_product) {
      // call to retrieve customer with ID 2
      $xml = $this->rest->get([
        'resource' => 'products',
        'id' => $prestashop_product, // Here we use hard coded value but of course you could get this ID from a request parameter or anywhere else
      ]);

      $skip_product = false;

      if ($productFields = $xml->product->children()) {
        $pupesoft_product = $pupesoft_products[(string) $productFields->reference];
        if (!$this->compareData($pupesoft_product, $productFields)) {
          $skip_product = true;
        }
      } else {
        $skip_product = true;
      }

      if ($skip_product) {
        continue;
      }

      if (isset($pupesoft_product['tuotemerkki'])
      and $pupesoft_product['tuotemerkki']
      and $pupesoft_product['tuotemerkki'] != ""
      and $pupesoft_product['tuotemerkki'] != null) {
        $productFields->id_manufacturer = $this->getPrestashopManufacturer($pupesoft_product['tuotemerkki']);
      } else {
        $productFields->id_manufacturer = "";
      }

      if (isset($pupesoft_product['try_nimike'])
      and $pupesoft_product['try_nimike']
      and $pupesoft_product['try_nimike'] != ""
      and $pupesoft_product['try_nimike'] != null) {
        $productFields->associations->categories->category->id = $this->getPrestashopCategory($pupesoft_product['try_nimike']);
        $productFields->id_category_default = $this->getPrestashopCategory($pupesoft_product['try_nimike']);
      } else {
        $productFields->associations->categories->category->id = "";
        $productFields->id_category_default = "";
      }

      $productFields->price = $pupesoft_product['myyntihinta'];
      $productFields->name = $productFields->meta_title = utf8_encode($pupesoft_product['nimitys']);
      $productFields->description_short = $productFields->meta_description = utf8_encode($pupesoft_product['lyhytkuvaus']);
      $productFields->description = utf8_encode($pupesoft_product['kuvaus']);
      $productFields->minimal_quantity = (int) $pupesoft_product['myynti_era'];
      $productFields->ean13 = $pupesoft_product['eankoodi'];
      
      if ($pupesoft_product['status'] != 'A') {
        $productFields->active = 0;
      } else {
        $productFields->active = 1;
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
  }

  public function begin($resource)
  {
    echo "<pre>";
    print_r($this->getPupesoftCategories());
    echo "</pre>";
    die();
    $this->pupesoft_products = $this->getPupesoftProducts();
    $this->prestashop_products = $this->getPrestashopProducts($this->pupesoft_products);
    $this->updatePrestashopProducts($this->prestashop_products, $this->pupesoft_products['products']);
  }
}

$webService = new PrestaShopWebservice($presta17_api_url, $presta17_api_pass, $presta17_api_debug);
$execute = new Presta17RestApi(
  $yhtiorow,
  $webService,
  $presta17_api_url
);
$execute->begin($resource);
