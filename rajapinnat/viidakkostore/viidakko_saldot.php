<?php

class ViidakkoStoreSaldot {
  private $apiurl = "";
  private $token = "";
  private $pupesoft_all_products = array();
  protected $logger = null;

  public function __construct($url, $token, $log_file) {
    $this->apiurl = $url;
    $this->token = $token;

    $this->logger = new Logger($log_file);
  }

  public function update_stock() {
    $this->logger->log('---------Aloitetaan saldojen päivitys---------');

    $pupesoft_products = $this->pupesoft_all_products;

    $current = 0;
    $total = count($pupesoft_products);

    foreach ($pupesoft_products as $product_row) {

      $url = $this->apiurl."/stocks";

      unset($product_row['original_product_code']);

      echo "\nvar_dump stocks:<pre>",var_dump($product_row);

      $data_json = json_encode($product_row);

      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json', 'X-Auth-Token: '.$this->token));
      curl_setopt($ch, CURLOPT_HEADER, TRUE);
      curl_setopt($ch, CURLOPT_POST, TRUE);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

      $response = curl_exec($ch);
      curl_close($ch);

      echo "\n stock\n";
      echo "\nvar_dump stocks:<pre>",var_dump($response);

      $current++;
      $this->logger->log("[{$current}/{$total}] tuote {$product_row["code"]} saldo {$product_row['stock']}");

    }

    $this->logger->log('---------Saldojen päivitys valmis---------');
  }

  public function set_all_products($value) {
    if (is_array($value)) {
      $this->pupesoft_all_products = $value;
    }
  }
}
