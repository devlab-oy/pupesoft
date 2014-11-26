<?php

require_once 'rajapinnat/presta/presta_client.php';

class PrestaCategories extends PrestaClient {

  const RESOURCE = 'categories';

  /**
   * Presta root element id
   * 
   * @var int 
   */
  private $root = null;

  /**
   * Synced categories
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
   * @param array $ancestors
   * @return string
   */
  public function find_category($ancestors) {
    $category_id = null;
    //Display is allways supposed to be an empty array when its given to all
    $display = $filter = $parents = array();
    //Nodes are in parent -> deepest level children order
    foreach ($ancestors as $node_nimi) {
      $filter['name'] = $node_nimi;
      $categories = $this->all($display, $filter);
      $category = $categories['categories']['category'];

      //@TODO if many categories with the name is found, we have a problem...
      //We simply take the first one
      //Hackhack logic for indentifying this situation
      //(count on categories.category wont work)
      if (!isset($category['id'])) {
        $category = $category[0];
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
   * @param array $category
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

    $link_rewrite = $this->saniteze_link_rewrite($category['node_nimi']);
    $xml->category->link_rewrite->language[0] = $link_rewrite;
    $xml->category->link_rewrite->language[1] = $link_rewrite;
    $xml->category->name->language[0] = $category['node_nimi'];
    $xml->category->name->language[1] = $category['node_nimi'];

    return $xml;
  }

  /**
   * 
   * @param array $categories 2D array (array of hashes) of categories
   * @return boolean
   */
  public function sync_categories($categories) {
    $this->logger->log('---------Start category sync---------');
    $this->set_category_root(1);
    $this->categories = $categories;

    if (empty($this->root)) {
      throw new RuntimeException('Preta root element id:tä ei ole setattu');
    }

    try {
      $this->schema = $this->get_empty_schema($this->resource_name());

      //We start with first level. This means that the root element is skipped.
      $first_level_node_depth = array(
          'node_syvyys' => 1
      );
      $first_level_nodes = array_find($this->categories, $first_level_node_depth);
      foreach ($first_level_nodes as $first_level_node) {
        $first_level_node['parent_id'] = $this->root;
        $this->recursive_save($first_level_node);
      }
    }
    catch (Exception $e) {
      //Exception logging happens in create / update.

      return false;
    }

    $this->logger->log('---------End category sync---------');

    return true;
  }

  /**
   * Contains the logic to save a node and recursively all its children
   * 
   * @param array $node
   */
  private function recursive_save($node) {
    $response = $this->create($node);
    $parent_id = (string) $response['category']['id'];
    $nodes = $this->next_level_nodes($node['syvyys'], $node['rgt'], $node['lft']);

    foreach ($nodes as $node) {
      $node['parent_id'] = $parent_id;
      $this->recursive_save($node);
    }
  }

  /**
   * Returns specific nodes first level chidlren
   * 
   * @param int $parent_depth (syvyys)
   * @param int $parent_right (rgt)
   * @param int $parent_left (lft)
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
   * @param int $presta_category_id
   */
  public function set_category_root($presta_category_id) {
    //Manually set Devlab testi root node
    $this->root = $presta_category_id;
  }

  public function delete_all() {
    $this->logger->log('---------Start category delete all---------');
    $existing_categories = $this->all(array('id'));
    $existing_categories = $existing_categories['categories']['category'];
    $existing_categories = array_column($existing_categories, 'id');

    foreach ($existing_categories as $id) {
      try {
        $this->delete($id);
      }
      catch (Exception $e) {
        
      }
    }
    
    $this->logger->log('---------End category delete all---------');
  }
}
