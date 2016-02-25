<?php

require_once 'rajapinnat/presta/presta_client.php';

class PrestaProductStocks extends PrestaClient {
  private $all_stocks = null;

  public function __construct($url, $api_key) {
    parent::__construct($url, $api_key);
  }

  protected function resource_name() {
    return 'stock_availables';
  }

  protected function generate_xml($stock, SimpleXMLElement $existing_stock = null) {
    if (is_null($existing_stock)) {
      $xml = $this->empty_xml();
    }
    else {
      $xml = $existing_stock;
    }

    // we need to floor quantity, as Presta does not allow decimal numbers
    $quantity = is_numeric($stock['saldo']) ? floor((float) $stock['saldo']) : 0;

    $xml->stock_available->quantity = $stock['saldo'];
    $xml->stock_available->id_product = $stock['product_id'];

    return $xml;
  }

  public function create_or_update($product_id, $qty) {
    $stock = array(
      'product_id' => $product_id,
      'saldo'      => $qty,
    );

    // Needs to be inside try-catch so that we wont interrupt product create loop.
    // In catch we only log the error. Do not rethrow the exception because that interrupts
    // the product create loop
    try {
      $stock_id = $this->stock_id_by_product_id($product_id);

      if ($stock_id === false) {
        $this->create($stock);
      }
      else {
        $this->update($stock_id, $stock);
      }
    }
    catch (Exception $e) {
      $msg = "Tuotteen: {$product_id} saldon luonti/päivitys epäonnistui. Saldo: {$qty}";
      $this->logger->log($msg, $e);

      return false;
    }

    return true;
  }

  private function stock_id_by_product_id($product_id) {
    if (is_null($this->all_stocks)) {
      // fetch all stocks
      $this->logger->log("Haetaan kaikki saldot Prestasta");
      $display = array('id', 'id_product');
      $this->all_stocks = $this->all($display);
    }

    foreach ($this->all_stocks as $stock) {
      if ($product_id == $stock['id_product']) {
        return $stock['id'];
      }
    }

    return false;
  }
}
