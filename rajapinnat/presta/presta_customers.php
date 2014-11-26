<?php

require_once 'rajapinnat/presta/presta_client.php';

class PrestaCustomers extends PrestaClient {

  const RESOURCE = 'customers';

  public function __construct($url, $api_key) {
    parent::__construct($url, $api_key);
  }

  protected function resource_name() {
    return self::RESOURCE;
  }

  /**
   *
   * @param array $customer
   * @param SimpleXMLElement $existing_customer
   * @return \SimpleXMLElement
   */
  protected function generate_xml($customer, SimpleXMLElement $existing_customer = null) {
    $xml = new SimpleXMLElement($this->schema->asXML());

    if (!is_null($existing_customer)) {
      $xml = $existing_customer;
    }

    return $xml;
  }

  public function sync_customers($customers) {
    $this->logger->log('---------Start customer sync---------');

    try {
      $this->schema = $this->get_empty_schema();
      //Fetch all products with ID's and SKU's only
      $existing_products = $this->all(array('id', 'reference'));
      $existing_products = $existing_products['products']['product'];
      $existing_products = array_column($existing_products, 'reference', 'id');

      foreach ($products as $product) {
        try {
          if (in_array($product['tuoteno'], $existing_products)) {
            $id = array_search($product['tuoteno'], $existing_products);
            $response = $this->update($id, $product);
            $this->delete_product_images($id);
          }
          else {
            $response = $this->create($product);
            $id = (string) $response['product']['id'];
          }

          $this->create_product_images($id, $product['images']);
        }
        catch (Exception $e) {
          //Do nothing here. If create / update throws exception loggin happens inside those functions
          //Exception is not thrown because we still want to continue syncing for other products
        }
      }
    }
    catch (Exception $e) {
      //Exception logging happens in create / update.

      return false;
    }

    $this->logger->log('---------End customer sync---------');

    return true;
  }
}
