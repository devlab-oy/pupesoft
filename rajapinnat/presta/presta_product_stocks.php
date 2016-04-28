<?php

require_once 'rajapinnat/presta/presta_client.php';

class PrestaProductStocks extends PrestaClient {
  private $all_stocks = null;
  private $pupesoft_all_products = array();

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

    $xml->stock_available->quantity = $quantity;
    $xml->stock_available->id_product = $stock['product_id'];

    // Tilaustuote
    // 0 = deny orders, 1 = allow orders, 2 = default
    $out_of_stock = $stock['status'] == 'T' ? 1 : 2;

    $xml->stock_available->out_of_stock = $out_of_stock;

    return $xml;
  }

  public function update_stock() {
    $this->logger->log('---------Aloitetaan saldojen päivitys---------');

    // set all products null, so we'll fetch all_skus again from presta
    $this->presta_all_products = null;
    $pupesoft_products = $this->pupesoft_all_products;

    $current = 0;
    $total = count($pupesoft_products);

    foreach ($pupesoft_products as $product_row) {
      $sku = $product_row['tuoteno'];
      $saldo = is_numeric($product_row['saldo']) ? floor((float) $product_row['saldo']) : 0;
      $status = $product_row['status'];

      $product_id = array_search($sku, $this->all_skus());

      $current++;
      $this->logger->log("[{$current}/{$total}] tuote {$sku} ({$product_id}) saldo {$saldo} status {$status}");

      // could not find product or
      // this is a virtual product, no stock management
      if ($product_id === false or $saldo === null) {
        continue;
      }

      $stock = array(
        'product_id' => $product_id,
        'saldo'      => $saldo,
        'status'     => $status,
      );

      $this->presta_stock->create_or_update($stock);
    }

    $this->logger->log('---------Saldojen päivitys valmis---------');
  }

  private function create_or_update($stock) {
    $product_id = $stock['product_id'];
    $qty = $stock['saldo'];

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

  public function set_all_products($value) {
    if (is_array($value)) {
      $this->pupesoft_all_products = $value;
    }
  }
}
