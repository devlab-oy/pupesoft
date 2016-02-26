<?php

require_once 'rajapinnat/presta/presta_client.php';

class PrestaShops extends PrestaClient {

  const RESOURCE = 'shops';

  public function __construct($url, $api_key) {
    parent::__construct($url, $api_key);
  }

  protected function resource_name() {
    return self::RESOURCE;
  }

  /**
   *
   * @param array   $shop
   * @param SimpleXMLElement $existing_shop
   * @return \SimpleXMLElement
   */


  protected function generate_xml($shop, SimpleXMLElement $existing_shop = null) {
    throw new Exception('You shouldnt be here! Shop does not have CRUD yet');
  }

  /**
   * This function updates the first shops category_id
   * This is used in PrestaCategories sync_categories();
   *
   * @param string  $category_id
   */
  public function update_shops_category($category_id) {
    try {
      $shop = $this->first_shop();
      $shop_id = $shop['id'];

      $shop = $this->get_as_xml($shop_id);

      $shop->shop->id_category = $category_id;

      $this->update_xml($shop_id, $shop);
    }
    catch (Exception $e) {
      $msg = "update_shops_category category_id: {$category_id} epäonnistui";
      $this->logger->log($msg, $e);

      throw $e;
    }
  }

  /**
   * Fetches the first shop in presta
   *
   * @return array
   * @throws Exception
   */
  public function first_shop() {
    try {
      $shops = $this->all();
      $shop = $shops[0];
    }
    catch (Exception $e) {
      $msg = "first_shop haku epäonnistui";
      $this->logger->log($msg, $e);

      throw $e;
    }

    return $shop;
  }
}
