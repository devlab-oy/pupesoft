<?php

class CustobarProducts {
  private $apiurl = "";
  private $userpwd = "";
  private $pupesoft_products = array();
  protected $logger = null;

  public function __construct($url, $username, $api_key, $log_file) {
    $this->apiurl = $url;
    $this->userpwd = "{$username}:{$api_key}";

    $this->logger = new Logger($log_file);
  }

  public function update_products() {
    $this->logger->log('---------Aloitetaan tuotteiden p�ivitys---------');

    $pupesoft_products = $this->pupesoft_all_products;

    echo "var_dump pupesoft_products:<pre>",var_dump($pupesoft_products);

    $url = $this->apiurl."/products/upload/";
    $data_json = json_encode(array("products" => $pupesoft_products));

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_HEADER, TRUE);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_USERPWD, $this->userpwd);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLINFO_HEADER_OUT, true);
    $response = curl_exec($ch);

    curl_close($ch);

    echo "\n\nvar_dump products:<pre>",var_dump($response);

    $this->logger->log('---------Tuotteiden p�ivitys valmis---------');
  }

  public function set_all_products($value) {
    if (is_array($value)) {
      $this->pupesoft_all_products = $value;
    }
  }
}
