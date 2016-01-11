<?php

require_once 'rajapinnat/presta/presta_client.php';
require_once 'rajapinnat/presta/presta_shops.php';

class PrestaCategories extends PrestaClient {

  const RESOURCE = 'categories';

  /**
   * Presta root element id
   *
   * @var int
   */


  private $root = null;

  /**
   * Categories to be synced
   *
   * @var array
   */
  private $categories = array();

  public function __construct($url, $api_key) {
    parent::__construct($url, $api_key);
  }

  /**
   *
   * @return string
   */
  protected function resource_name() {
    return self::RESOURCE;
  }

  /**
   * This function is used in PrestaProducts. It finds the deepest level node
   * according to ancestors and returns the presta id if its found
   *
   * Ancestor is given in following format:
   * $ancestors = array(
   *    'Taso 1',
   *    'Taso 1 1',
   *    'Taso 1 1 1', (returns this id)
   * );
   *
   *
   * @param array   $ancestors
   * @return string
   */
  public function find_category($ancestors) {
    $category_id = null;
    //Display is allways supposed to be an empty array when its given to all
    $display = $filter = $parents = array();
    //Nodes are in parent -> deepest level children order
    foreach ($ancestors as $node_nimi) {
      $filter['name'] = utf8_encode($node_nimi);
      $categories = $this->all($display, $filter);

      //@TODO if many categories with the name is found, we have a problem...
      //We simply take the first one
      $category = array();
      if (isset($categories[0])) {
        $category = $categories[0];
      }

      if (empty($category)) {
        return null;
      }

      if (empty($parents)) {
        $parents[] = $category['id'];
        continue;
      }

      if (!in_array($category['id_parent'], $parents)) {
        break;
      }

      $parents[] = $category['id'];
    }

    $category_id = end($parents);

    return $category_id;
  }

  /**
   *
   * @param array   $category
   * @param SimpleXMLElement $existing_category
   * @return \SimpleXMLElement
   */
  protected function generate_xml($category, SimpleXMLElement $existing_category = null) {
    $xml = new SimpleXMLElement($this->schema->asXML());

    if (!is_null($existing_category)) {
      $xml = $existing_category;
      unset($xml->category->position_in_category);
      unset($xml->category->manufacturer_name);
      unset($xml->category->quantity);
    }

    $xml->category->id_parent = $category['parent_id'];
    $xml->category->active = 1;

    if ($category['is_root_category']) {
      $xml->category->is_root_category = 1;
    }
    else {
      $xml->category->is_root_category = 0;
    }

    $link_rewrite = utf8_encode($this->saniteze_link_rewrite($category['node_nimi']));
    $xml->category->link_rewrite->language[0] = $link_rewrite;
    $xml->category->link_rewrite->language[1] = $link_rewrite;
    $xml->category->name->language[0] = utf8_encode($category['node_nimi']);
    $xml->category->name->language[1] = utf8_encode($category['node_nimi']);

    return $xml;
  }

  /**
   *
   * @param array   $categories 2D array (array of hashes) of categories
   * @return boolean
   */
  public function sync_categories($categories) {
    $this->delete_all();

    $this->logger->log('---------Start category sync---------');
    $this->set_category_root(1);
    $this->categories = $categories;

    if (empty($this->root)) {
      throw new RuntimeException('Presta root element id:tä ei ole setattu');
    }

    try {
      $this->get($this->root);
    }
    catch (Exception $e) {
      $msg = "Root categoryä {$this->root} ei ole olemassa";
      $this->logger->log($msg);

      return false;
    }

    try {
      $this->schema = $this->get_empty_schema();

      //We start with root level. This means that the pupesoft root is inserted under Presta root.
      $first_level_node_depth = array(
        'node_syvyys' => 0
      );
      $first_level_nodes = array_find($this->categories, $first_level_node_depth);
      $shop_category_updated = false;
      foreach ($first_level_nodes as $first_level_node) {
        $first_level_node['parent_id'] = $this->root;
        $first_level_node['is_root_category'] = true;

        $root_category_id = $this->recursive_save($first_level_node);

        //Shop has id_category_default which needs to be updated. Otherwise presta will fail.
        if (!$shop_category_updated) {
          $presta_shop = new PrestaShops($this->url(), $this->api_key());
          $presta_shop->update_shops_category($root_category_id);
        }
      }
    }
    catch (Exception $e) {
      return false;
    }

    $this->logger->log('---------End category sync---------');

    return true;
  }

  /**
   * Contains the logic to save a node and recursively all its children
   *
   * @param array   $node
   * @return string
   */
  private function recursive_save($node) {
    $response = $this->create($node);
    $parent_id = (string) $response['category']['id'];
    $nodes = $this->next_level_nodes($node['syvyys'], $node['rgt'], $node['lft']);

    foreach ($nodes as $node) {
      $node['parent_id'] = $parent_id;
      $node['is_root_category'] = false;
      $this->recursive_save($node);
    }

    return $parent_id;
  }

  /**
   * Returns specific nodes first level chidlren
   *
   * @param int     $parent_depth (syvyys)
   * @param int     $parent_right (rgt)
   * @param int     $parent_left  (lft)
   * @return array
   */
  private function next_level_nodes($parent_depth, $parent_right, $parent_left) {
    $next_level_nodes = array();
    foreach ($this->categories as $category) {
      $_depth = ($category['syvyys'] == ($parent_depth + 1));
      $_lft = ($category['lft'] > $parent_left);
      $_rgt = ($category['rgt'] < $parent_right);
      if ($_depth and $_lft and $_rgt) {
        $next_level_nodes[] = $category;
      }
    }

    return $next_level_nodes;
  }

  /**
   * Presta has Root node which is common for all. Since Presta is multi company
   * webstore under root are the company specific nodes. These nodes are the
   * root nodes for each companys dynamic tree
   *
   * Root
   *  Store1_root
   *    Computers
   *    Laptops
   *  Store2_root
   *    Cars
   *    Mopeds
   *
   * @param int     $presta_category_id
   */
  public function set_category_root($presta_category_id) {
    $this->root = $presta_category_id;
  }
}
