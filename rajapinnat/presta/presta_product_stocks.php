<?php

require_once 'rajapinnat/presta/presta_client.php';

class PrestaProductStocks extends PrestaClient {

  const RESOURCE = 'stock_availables';

  public function __construct($url, $api_key) {
    parent::__construct($url, $api_key);
  }

  protected function resource_name() {
    return self::RESOURCE;
  }

  /**
   *
   * @param array   $stock
   * @param SimpleXMLElement $existing_stock
   * @return \SimpleXMLElement
   */


  protected function generate_xml($stock, SimpleXMLElement $existing_stock = null) {
    $xml = new SimpleXMLElement($this->schema->asXML());

    if (!is_null($existing_stock)) {
      $xml = $existing_stock;
    }

    // we need to floor quantity, as Presta does not allow decimal numbers
    $quantity = is_numeric($stock['saldo']) ? floor($stock['saldo']) : 0;

    $xml->stock_available->quantity = $stock['saldo'];
    $xml->stock_available->id_product = $stock['product_id'];

    return $xml;
  }

  /**
   * Used in PrestaProducts sync_products()
   * Creates or updates the products stock
   *
   * @param int     $product_id
   * @param int     $stock
   * @return boolean
   */
  public function create_or_update($product_id, $qty) {
    $display = array();
    $filters = array(
      'id_product' => $product_id,
    );
    $stock = array(
      'product_id' => $product_id,
      'saldo'      => $qty,
    );

    //Needs to be inside try-catch so that we wont interrupt product create loop.
    //In catch we only log the error. Do not rethrow the exception because that interrupts
    //the product create loop
    try {
      $this->schema = $this->get_empty_schema();
      $existing_stock = $this->all($display, $filters);

      if (empty($existing_stock)) {
        $this->create($stock);
      }
      else {
        //All allways returns many. Thats why we can point with index
        //Allways use index 0 so that product can only have 1 stock
        $this->update($existing_stock[0]['id'], $stock);
      }
    }
    catch (Exception $e) {
      $msg = "Tuotteen: {$product_id} saldon luonti/päivitys epäonnistui. Saldo: {$qty}";
      $this->logger->log($msg, $e);

      return false;
    }

    return true;
  }
}
