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

    foreach ($pupesoft_products as $product_row) {

      $url = $this->apiurl."/stocks";

      #echo "\nvar_dump stocks:",var_dump($product_row);

      $data_json = json_encode($product_row);

      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json', 'X-Auth-Token: '.$this->token));
      curl_setopt($ch, CURLOPT_HEADER, FALSE);
      curl_setopt($ch, CURLOPT_POST, TRUE);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

      $response = curl_exec($ch);
      curl_close($ch);

      #echo "\n stock\n";
      $response_array = json_decode($response);
      #echo "\nvar_dump stocks:",var_dump($response_array);


      if (isset($response_array->items[0]->id) and !empty($response_array->items[0]->id)) {
        #echo "\n---------200---------\n";
        $this->logger->log("--> tuotteen {$response_array->items[0]->code} saldon päivitys onnistui! ID: {$response_array->items[0]->id}, saldo: {$response_array->items[0]->stock}");
      }
      else {
        #echo "\n---------400---------\n";
        $this->logger->log("--> tuotteen {$product_row[0]["code"]} saldon päivitys epäonnistui!");
        $this->logger->log("syy: {$response}");
      }

    }

    $this->logger->log('---------Saldojen päivitys valmis---------');
  }

  public function set_all_products($value) {
    if (is_array($value)) {
      $this->pupesoft_all_products = $value;
    }
  }
}
