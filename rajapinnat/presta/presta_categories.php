<?php

require_once 'rajapinnat/presta/presta_client.php';

class PrestaCategories extends PrestaClient {
  private $all_values = null;
  private $category_sync = true;
  private $presta_home_category_id = null;
  private $pupesoft_root_category_id = null;

  protected function resource_name() {
    return 'categories';
  }

  public function __construct($url, $api_key) {
    parent::__construct($url, $api_key);
  }

  public function sync_categories($pupesoft_categories) {
    $this->logger->log("---Start category import---");

    if ($this->category_sync === false) {
      $this->logger->log("Kategorioiden päivitys ei käytössä!");
      return;
    }

    $count = count($pupesoft_categories) - 1;
    $current = 0;

    // add all pupesoft categories
    foreach ($pupesoft_categories as $category) {

      $current += 1;
      $tunnus = $category['node_tunnus'];
      $parent_tunnus = $category['parent_tunnus'];

      $this->logger->log("[$current/$count] {$category['nimi']}");

      // this is pupesoft root category
      if ($parent_tunnus === null) {
        $this->pupesoft_root_category_id = $tunnus;
        $this->logger->log("Ohitetaan root kategoria {$category['nimi']}");
        continue;
      }

      $id = $this->find_category_by_tunnus($tunnus);

      try {
        if ($id === false) {
          $this->logger->log("Luodaan kategoria (node {$tunnus}, parent {$parent_tunnus})");
          $this->create($category);

          // reset all values, so we'll fetch all again next loop
          $this->all_values = null;
        }
        else {
          $this->logger->log("Päivitetään kategoria {$id} (node {$tunnus}, parent {$parent_tunnus})");
          $this->update($id, $category);
        }
      }
      catch (Exception $e) {
      }
    }

    $this->delete_unnecessary_categories();

    $this->logger->log("---Stop category import---");
  }

  protected function generate_xml($record, SimpleXMLElement $existing_record = null) {
    if (is_null($existing_record)) {
      $xml = $this->empty_xml();
    }
    else {
      $xml = $existing_record;
    }

    // remove read only fields
    unset($xml->category->level_depth);
    unset($xml->category->nb_products_recursive);

    $parent_tunnus = $record['parent_tunnus'];

    // if our parent is pupesoft root, parent is presta home
    if ($parent_tunnus == $this->pupesoft_root_category_id) {
      $parent = $this->presta_home_category_id;
    }
    // otherwise we need to fetch the id of the parent id
    else {
      $parent = $this->find_category_by_tunnus($parent_tunnus);

      if ($parent === false) {
        $this->logger->log("Isäkategoria $parent_tunnus ei löytynyt!");
        throw new Exception("Isäkategoria $parent_tunnus ei löytynyt!");
      }
    }

    $friendly_url = $this->saniteze_link_rewrite($record['nimi']);

    $xml->category->active = 1;
    $xml->category->id_parent = $parent;

    // we must set values for all languages
    $languages = count($xml->category->name->language);

    for ($i=0; $i < $languages; $i++) {
      $xml->category->name->language[$i] = utf8_encode($record['nimi']);
      $xml->category->link_rewrite->language[$i] = utf8_encode($friendly_url);
      $xml->category->meta_keywords->language[$i] = $record['node_tunnus'];
    }

    return $xml;
  }

  public function find_category_by_tunnus($tunnus) {
    // loop categories
    foreach ($this->fetch_all() as $category) {
      $meta = $category["meta_keywords"]["language"];
      $presta_value = is_array($meta) ? $meta[0] : $meta;

      if ($presta_value == $tunnus) {
        return $category['id'];
      }
    }

    return false;
  }

  private function fetch_all() {
    if (isset($this->all_values)) {
      return $this->all_values;
    }

    $display = array('id', 'meta_keywords');

    $this->logger->log("Haetaan kaikki kategoriat");
    $this->all_values = $this->all($display);

    return $this->all_values;
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

    foreach ($this->fetch_all() as $category) {
      // add presta id to array
      $all_presta_ids[] = (int) $category['id'];
    }

    // all ids found in all_presta_ids that are not found in keep_presta_ids
    $delete_presta_ids = array_diff($all_presta_ids, $keep_presta_ids);

    // we should delete them from presta
    foreach ($delete_presta_ids as $id) {
      $result = $this->delete($id);
    }

    return true;
  }

  public function set_category_sync($value) {
    if ($value === false) {
      $this->category_sync = false;
    }
  }

  public function set_home_category_id($value) {
    $this->presta_home_category_id = $value;
  }
}
