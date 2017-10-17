<?php

class MyCashflowSaldot {
  private $apiurl = "";
  private $userpwd = "";
  private $pupesoft_all_products = array();
  protected $logger = null;

  public function __construct($url, $username, $api_key, $log_file) {
    $this->apiurl = $url;
    $this->userpwd = "{$username}:{$api_key}";

    $this->logger = new Logger($log_file);
  }

  public function update_stock() {
    $this->logger->log('---------Aloitetaan saldojen päivitys---------');

    $pupesoft_products = $this->pupesoft_all_products;

    $current = 0;
    $total = count($pupesoft_products);

    foreach ($pupesoft_products as $product_row) {

      $url = $this->apiurl."/api/v1/stock/".$product_row["tuoteno"];

      $data_json = json_encode(array("enabled" => true,
                                     "quantity" => $product_row['saldo']));

      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
      curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
      curl_setopt($ch, CURLOPT_USERPWD, $this->userpwd);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
      curl_setopt($ch, CURLOPT_HEADER, TRUE);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

      $response = curl_exec($ch);
      curl_close($ch);

      $current++;
      $this->logger->log("[{$current}/{$total}] tuote {$product_row["tuoteno"]} saldo {$product_row['saldo']}");

    }

    $this->logger->log('---------Saldojen päivitys valmis---------');
  }

  public function set_all_products($value) {
    if (is_array($value)) {
      $this->pupesoft_all_products = $value;
    }
  }
}
