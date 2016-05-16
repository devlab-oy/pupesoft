<?php

require_once 'rajapinnat/presta/presta_client.php';
require_once 'rajapinnat/presta/presta_products.php';

class PrestaProductStocks extends PrestaClient {
  private $all_stocks = null;
  private $presta_products = null;
  private $pupesoft_all_products = array();

  public function __construct($url, $api_key, $log_file) {
    $this->presta_products = new PrestaProducts($url, $api_key, $log_file);

    parent::__construct($url, $api_key, $log_file);
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
    $xml->stock_available->id_shop = $stock['id_shop'];

    // Tilaustuote
    // 0 = deny orders, 1 = allow orders, 2 = default
    $out_of_stock = $stock['status'] == 'T' ? 1 : 2;

    $xml->stock_available->out_of_stock = $out_of_stock;

    return $xml;
  }

  public function update_stock() {
    $this->logger->log('---------Aloitetaan saldojen päivitys---------');

    $pupesoft_products = $this->pupesoft_all_products;

    $current = 0;
    $total = count($pupesoft_products);

    foreach ($pupesoft_products as $product_row) {
      $sku = $product_row['tuoteno'];
      $saldo = is_numeric($product_row['saldo']) ? floor((float) $product_row['saldo']) : 0;
      $status = $product_row['status'];

      $product_id = $this->presta_products->product_id_by_sku($sku);

      $current++;
      $this->logger->log("[{$current}/{$total}] tuote {$sku} ({$product_id}) saldo {$saldo} status {$status}");

      // could not find product or
      // this is a virtual product, no stock management (saldo === null)
      if ($product_id === false or $product_row['saldo'] === null) {
        continue;
      }

      // loop all shops
      foreach ($this->all_shop_ids() as $id_shop) {
        $stock = array(
          'product_id' => $product_id,
          'saldo'      => $saldo,
          'status'     => $status,
          'id_shop'    => $id_shop,
        );

        $this->update_stock_availables($stock);
      }
    }

    $this->logger->log('---------Saldojen päivitys valmis---------');
  }

  private function update_stock_availables($stock) {
    $product_id = $stock['product_id'];
    $qty = $stock['saldo'];
    $id_shop = $stock['id_shop'];

    // Needs to be inside try-catch so that we wont interrupt product create loop.
    // In catch we only log the error. Do not rethrow the exception because that interrupts
    // the product create loop
    try {
      $stock_id = $this->stock_id_by_product_id($product_id, $id_shop);

      if ($stock_id === false) {
        // stocks can only be updated, cannot create via API
        $this->logger->log("Tuotteelle ei löydy stock_availables tietuetta kaupasta {$id_shop}. Ei voida päivittää.");
      }
      else {
        $this->update($stock_id, $stock, $id_shop);
      }
    }
    catch (Exception $e) {
      $msg = "Tuotteen: {$product_id} saldon luonti/päivitys epäonnistui. Saldo: {$qty}";
      $this->logger->log($msg, $e);

      return false;
    }

    return true;
  }

  private function stock_id_by_product_id($product_id, $id_shop) {
    if (is_null($this->all_stocks)) {
      $display = array('id', 'id_product', 'id_shop');
      $filter = array();
      $id_group_shop = $this->shop_group_id();

      // fetch from all shops
      $all_stocks = $this->all($display, $filter, null, $id_group_shop);

      $this->all_stocks = $all_stocks;
    }

    foreach ($this->all_stocks as $stock) {
      if ($product_id == $stock['id_product'] and $id_shop == $stock['id_shop']) {
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
