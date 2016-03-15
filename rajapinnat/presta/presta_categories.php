<?php

require_once 'rajapinnat/presta/PSWebServiceLibrary.php';
require_once 'rajapinnat/logger.php';

class PrestaCategories {
  private $_categories = null;
  private $_empty_category = null;
  private $_category_sync = true;

  private $presta_url = null;
  private $presta_client = null;
  private $presta_home_category_id = null;
  private $pupesoft_root_category_id = null;

  public function __construct($url, $api_key, $home_id) {
    $this->presta_url = $url;
    $this->presta_home_category_id = $home_id;
    $this->presta_client = new PrestaShopWebservice($url, $api_key, false);

    $log_path = is_dir('/home/devlab/logs') ? '/home/devlab/logs' : '/tmp';

    $this->logger = new Logger("{$log_path}/presta_export.log");
    $this->logger->set_date_format('Y-m-d H:i:s');
  }

  public function sync_categories($pupesoft_categories) {
    $this->logger->log("---Start category import---");

    if ($this->_category_sync === false) {
      $this->logger->log("Category sync distabled!");
      return;
    }

    $count = count($pupesoft_categories) - 1;
    $current = 1;

    // add all pupesoft categories
    foreach ($pupesoft_categories as $category) {

      $tunnus = $category['node_tunnus'];
      $parent_tunnus = $category['parent_tunnus'];

      // this is pupesoft root category
      if ($parent_tunnus === null) {
        $this->pupesoft_root_category_id = $tunnus;
        continue;
      }

      $this->logger->log("[$current/$count]");

      // if our parent is pupesoft root, parent is presta home
      if ($parent_tunnus == $this->pupesoft_root_category_id) {
        $parent = $this->presta_home_category_id;
      }
      // otherwise we need to fetch the id of the parent id
      else {
        $presta_category = $this->find_category_by_tunnus($parent_tunnus);

        if ($presta_category === false) {
          $this->logger->log("Parent $parent_tunnus not FOUND! Skipping {$category['nimi']}");
          continue;
        }

        $parent = $presta_category->category->id;
      }

      $params = array(
        'nimi'         => $category['nimi'],
        'koodi'        => $category['koodi'],
        'tunnus'       => $tunnus,
        'parent_id'    => $parent,
      );

      $presta_category = $this->find_category_by_tunnus($tunnus);

      if ($presta_category === false) {
        $return = $this->create_category($params);
        $this->logger->log("Create {$category['nimi']}");
      }
      else {
        $id = $presta_category->category->id;
        $return = $this->update_category($id, $params);
        $this->logger->log("Update {$category['nimi']}");
      }

      if ($return === false) {
        $this->logger->log("FAILED!");
      }
      else {
        $this->logger->log("OK!");
      }

      $current += 1;
    }

    // delete unnecessary categories
    $this->delete_unnecessary_categories($pupesoft_categories);

    $this->logger->log("---Stop category import---");
  }

  public function find_category_by_tunnus($tunnus) {
    // loop categories
    foreach ($this->categories() as $category) {
      if ($category->category->meta_keywords->language[0] == $tunnus) {
        return $category;
      }
    }

    return false;
  }

  private function delete_unnecessary_categories($pupesoft_categories) {
    // collect all presta ID:s we should keep (always keep home and root 1)
    $keep_presta_ids = array(
      1,
      (int) $this->presta_home_category_id
    );

    foreach ($pupesoft_categories as $category) {
      // find category by pupesoft tunnus
      $presta_category = $this->find_category_by_tunnus($category['node_tunnus']);

      // add presta id to array
      if ($presta_category !== false) {
        $keep_presta_ids[] = (int) $presta_category->category->id;
      }
    }

    // collect all presta ID:s we have in store
    $all_presta_ids = array();

    foreach ($this->categories() as $category) {
      // add presta id to array
      $all_presta_ids[] = (int) $category->category->id;
    }

    // all ids found in all_presta_ids that are not found in keep_presta_ids
    $delete_presta_ids = array_diff($all_presta_ids, $keep_presta_ids);

    // we should delete them from presta
    foreach ($delete_presta_ids as $id) {
      $this->logger->log("Delete $id");

      $result = $this->delete_category($id);

      if ($result === false) {
        $this->logger->log("FAILED!");
      }
      else {
        $this->logger->log("OK!");
      }
    }

    return true;
  }

  private function categories() {
    if ($this->_categories !== null) {
      return $this->_categories;
    }

    // fetch categories
    $params = array(
      'resource' => 'categories',
    );

    try {
      $xml = $this->presta_client->get($params);
    }
    catch (PrestaShopWebserviceException $ex) {
      $this->logger->log($ex->getMessage());

      return array();
    }

    $categories = array();

    // loop categories
    foreach ($xml->categories->children() as $category) {
      // fetch category
      $params = array(
        'resource' => 'categories',
        'id' => $category['id'],
      );

      try {
        $xml = $this->presta_client->get($params);

        // remove read only fields from response
        $remove_fields = array(
          $xml->category->level_depth,
          $xml->category->nb_products_recursive,
        );

        foreach ($remove_fields as $field) {
          $dom = dom_import_simplexml($field);
          $dom->parentNode->removeChild($dom);
        }

        $categories[] = $xml;
      }
      catch (PrestaShopWebserviceException $ex) {
        $this->logger->log($ex->getMessage());
      }
    }

    $this->_categories = $categories;

    return $this->_categories;
  }

  private function empty_category() {
    if ($this->_empty_category !== null) {
      return $this->_empty_category;
    }

    $params = array(
      'url' => $this->presta_url . 'api/categories?schema=blank'
    );

    try {
      $category = $this->presta_client->get($params);
    }
    catch (PrestaShopWebserviceException $ex) {
      $this->logger->log($ex->getMessage());
      return false;
    }

    $this->_empty_category = $category;

    return $this->_empty_category;
  }

  private function find_category_by_id($id) {
    // loop categories
    foreach ($this->categories() as $category) {
      if ($category->category->id == $id) {
        return $category;
      }
    }

    return false;
  }

  private function update_category_array($id, $category) {
    // loop categories
    foreach ($this->categories() as $key => $value) {
      if ($value->category->id == $id) {
        $this->_categories[$key] = $category;
        return true;
      }
    }

    return false;
  }

  private function update_category($id, $params) {
    $category     = $this->find_category_by_id($id);
    $nimi         = (string) $params['nimi'];
    $koodi        = (int)    $params['koodi'];
    $tunnus       = (int)    $params['tunnus'];
    $parent_id    = (int)    $params['parent_id'];

    if ($category === false
        or empty($nimi)
        or empty($tunnus)
        or empty($parent_id)) {
      return false;
    }

    $friendly_url = $this->parameterize($koodi, $nimi);

    // change category info
    $new_name      = utf8_encode($nimi);
    $new_url       = utf8_encode($friendly_url);
    $new_tunnus    = $tunnus;
    $new_active    = 1;
    $new_parent_id = $parent_id;

    $before_name      = $category->category->name->language[0];
    $before_url       = $category->category->link_rewrite->language[0];
    $before_tunnus    = $category->category->meta_keywords->language[0];
    $before_active    = $category->category->active;
    $before_parent_id = $category->category->id_parent;

    // if nothing changes, don't do anything
    if ($new_name == $before_name and
        $new_url == $before_url and
        $new_tunnus == $before_tunnus and
        $new_active == $before_active and
        $new_parent_id == $before_parent_id) {
      return true;
    }

    $languages = count($category->category->name->language);

    // we must set these for all languages
    for ($i=0; $i < $languages; $i++) {
      $category->category->name->language[$i]          = $new_name;
      $category->category->link_rewrite->language[$i]  = $new_url;
      $category->category->meta_keywords->language[$i] = $new_tunnus;
    }

    $category->category->active                     = $new_active;
    $category->category->id_parent                  = $new_parent_id;

    // update category
    $params = array(
      'resource' => 'categories',
      'id' => $id,
      'putXml' => $category->asXML(),
    );

    try {
      $category = $this->presta_client->edit($params);
    }
    catch (PrestaShopWebserviceException $ex) {
      $this->logger->log($ex->getMessage());
      return false;
    }

    # update instance variable with these values
    $return = $this->update_category_array($id, $category);

    # update failed, set to null
    if ($return === false) {
      $this->_categories = null;
    }

    return true;
  }

  private function create_category($params) {
    $category     = $this->empty_category();
    $nimi         = (string) $params['nimi'];
    $koodi        = (int)    $params['koodi'];
    $tunnus       = (int)    $params['tunnus'];
    $parent_id    = (int)    $params['parent_id'];

    if ($category === false
        or empty($nimi)
        or empty($tunnus)
        or empty($parent_id)) {
      return false;
    }

    $friendly_url = $this->parameterize($koodi, $nimi);

    $languages = count($category->category->name->language);

    // change category info for every language
    for ($i=0; $i < $languages; $i++) {
      $category->category->name->language[$i] = utf8_encode($nimi);
      $category->category->link_rewrite->language[$i] = utf8_encode($friendly_url);
      $category->category->meta_keywords->language[$i] = $tunnus;
    }

    $category->category->active = 1;
    $category->category->id_parent = $parent_id;

    // create category
    $params = array(
      'resource' => 'categories',
      'postXml' => $category->asXML(),
    );

    try {
      $category = $this->presta_client->add($params);
    }
    catch (PrestaShopWebserviceException $ex) {
      $this->logger->log($ex->getMessage());
      return false;
    }

    # add this to the instance variable
    $this->_categories[] = $category;

    return true;
  }

  private function delete_category($id) {
    $params = array(
      'resource' => 'categories',
      'id' => $id,
    );

    try {
      $category = $this->presta_client->delete($params);
    }
    catch (PrestaShopWebserviceException $ex) {
      $this->logger->log($ex->getMessage());
      return false;
    }

    return true;
  }

  private function parameterize($koodi, $nimi) {
    if (empty($koodi)) {
      $string = $nimi;
    }
    else {
      $string = "{$koodi}_{$nimi}";
    }

    return preg_replace("/[^a-zA-Z0-9_]+/", "", $string);
  }

  public function set_category_sync($value) {
    if ($value === false) {
      $this->_category_sync = false;
    }
  }
}
