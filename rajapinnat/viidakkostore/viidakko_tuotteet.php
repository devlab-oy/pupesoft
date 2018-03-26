<?php

class ViidakkoStoreTuotteet {
  private $apiurl = "";
  private $userpwd = "";
  private $pupesoft_all_products = array();
  protected $logger = null;

  public function __construct($url, $username, $api_key, $log_file) {
    $this->apiurl = $url;
    $this->userpwd = "{$username}:{$api_key}";

    $this->logger = new Logger($log_file);
  }

  public function check_products() {
    $this->logger->log('---------Tarkistetaan onko tuote jo kaupassa---------');

    $pupesoft_products = $this->pupesoft_all_products;

    foreach ($pupesoft_products as $product) {

      $url = $this->apiurl."/products/".$product["product_code"];

      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
      curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
      curl_setopt($ch, CURLOPT_USERPWD, $this->userpwd);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
      curl_setopt($ch, CURLOPT_HEADER, TRUE);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

      $response = curl_exec($ch);
      curl_close($ch);

      $current++;
      $response_array = json_decode($response);

      if ($response_array->code == "200") {
        $this->logger->log("[{$current}/{$total}] tuote {$product["product_code"]} löytyy kaupasta");
        update_product($product);
      }
      else {
        $this->logger->log("[{$current}/{$total}] tuote {$product["product_code"]} ei löydy kaupasta");
        insert_product($product);
      }
    }
  }

  public function update_product($product) {
    $url = $this->apiurl."/products/".$product["product_code"];
    $data_json = json_encode($product);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, $this->userpwd);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
    curl_setopt($ch, CURLOPT_HEADER, TRUE);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    // response ok/fail vielä tähän
    $this->logger->log("--> tuote {$product["product_code"]} päivitetty");
  }

  public function insert_product($product) {
    $url = $this->apiurl."/products";
    $data_json = json_encode($product);

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

    // response ok/fail vielä tähän
    $this->logger->log("--> tuote {$product["product_code"]} lisätty");
  }

  public function set_all_products($value) {
    if (is_array($value)) {
      $this->pupesoft_all_products = $value;
    }
  }
}
