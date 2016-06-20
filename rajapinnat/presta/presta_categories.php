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

  public function __construct($url, $api_key, $log_file) {
    parent::__construct($url, $api_key, $log_file);
  }

  public function sync_categories($pupesoft_categories) {
    $this->logger->log("---Start category import---");

    if ($this->category_sync === false) {
      $this->logger->log("Kategorioiden päivitys ei käytössä!");
      return;
    }

    $shop_group_id = $this->shop_group_id();
    $count = count($pupesoft_categories) - 1;
    $current = 0;

    // add all pupesoft categories
    foreach ($pupesoft_categories as $category) {

      $current += 1;
      $tunnus = $category['node_tunnus'];
      $parent_tunnus = $category['parent_tunnus'];
      $nimi = $category['nimi'];

      $this->logger->log("[$current/$count] {$nimi}");

      // this is pupesoft root category
      if ($parent_tunnus === null) {
        $this->pupesoft_root_category_id = $tunnus;
        $this->logger->log("Ohitetaan root kategoria");
        continue;
      }

      $presta_category = $this->find_category_by_tunnus($tunnus);

      try {
        if ($presta_category === false) {
          $this->logger->log("Luodaan kategoria (node {$tunnus}, parent {$parent_tunnus})");
          $this->create($category, null, $shop_group_id);

          // reset all values, so we'll fetch all again next loop
          $this->all_values = null;
        }
        else {
          $id = $presta_category['id'];
          $this->logger->log("Päivitetään kategoria {$id} (node {$tunnus}, parent {$parent_tunnus})");
          $this->update($id, $category, null, $shop_group_id);
        }
      }
      catch (Exception $e) {
      }
    }

    $this->delete_unnecessary_categories($pupesoft_categories);

    $this->logger->log("---Stop category import---");
  }

  protected function remove_read_only_fields(SimpleXMLElement $xml) {
    unset($xml->category->level_depth);
    unset($xml->category->nb_products_recursive);

    return $xml;
  }

  protected function generate_xml($record, SimpleXMLElement $existing_record = null) {
    if (is_null($existing_record)) {
      $xml = $this->empty_xml();
    }
    else {
      $xml = $existing_record;
    }

    $parent_tunnus = $record['parent_tunnus'];

    // if our parent is pupesoft root, parent is presta home
    if ($parent_tunnus == $this->pupesoft_root_category_id) {
      $parent = $this->presta_home_category_id;
    }
    // otherwise we need to fetch the id of the parent id
    else {
      $presta_category = $this->find_category_by_tunnus($parent_tunnus);

      if ($presta_category === false) {
        $this->logger->log("Isäkategoria $parent_tunnus ei löytynyt!");
        throw new Exception("Isäkategoria $parent_tunnus ei löytynyt!");
      }

      $parent = $presta_category['id'];
    }

    $friendly_url = $this->saniteze_link_rewrite($record['nimi']);

    $xml->category->active = 1;
    $xml->category->id_parent = $parent;

    // we must set values for all languages
    $languages = count($xml->category->name->language);

    for ($i=0; $i < $languages; $i++) {
      $xml->category->name->language[$i] = $this->xml_value($record['nimi']);
      $xml->category->link_rewrite->language[$i] = $this->xml_value($friendly_url);
      $xml->category->meta_keywords->language[$i] = $record['node_tunnus'];
    }

    // loop all translations and overwrite defaults
    foreach ($record['kaannokset'] as $translation) {
      $tr_id = $this->get_language_id($translation['kieli']);

      // if we don't have the language in presta
      if ($tr_id === null) {
        $this->logger->log("VIRHE! kieltä {$translation['kieli']} ei löydy Prestasta!");
        continue;
      }

      $value = $this->xml_value($translation['nimi']);
      $xml->category->name->language[$tr_id] = $value;

      $this->logger->log("Käännös {$translation['kieli']} ({$tr_id}): $value");
    }

    return $xml;
  }

  public function find_category_by_tunnus($tunnus) {
    // loop categories
    foreach ($this->fetch_all() as $category) {
      $meta = $category["meta_keywords"]["language"];
      $presta_value = is_array($meta) ? $meta[0] : $meta;

      if ($presta_value == $tunnus) {
        return $category;
      }
    }

    return false;
  }

  private function fetch_all() {
    if (isset($this->all_values)) {
      return $this->all_values;
    }

    $display = array('id', 'meta_keywords', 'name');
    $filters = array();
    $shop_group_id = $this->shop_group_id();

    $this->logger->log("Haetaan kaikki kategoriat");
    $this->all_values = $this->all($display, $filters, null, $shop_group_id);

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
        $keep_presta_ids[] = (int) $presta_category['id'];
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

    $count = count($delete_presta_ids);
    $this->logger->log("Poistettavia kategorioita {$count} kpl");

    $shop_group_id = $this->shop_group_id();

    // we should delete them from presta
    foreach ($delete_presta_ids as $id) {
      try {
        $result = $this->delete($id, null, $shop_group_id);
      }
      catch (Exception $e) {
      }
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
