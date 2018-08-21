<?php

class CustobarCustomers {
  private $apiurl = "";
  private $userpwd = "";
  private $pupesoft_customers = array();
  protected $logger = null;

  public function __construct($url, $username, $api_key, $log_file) {
    $this->apiurl = $url;
    $this->userpwd = "{$username}:{$api_key}";

    $this->logger = new Logger($log_file);
  }

  public function update_customers() {
    $this->logger->log('---------Aloitetaan asiakkaiden p�ivitys---------');

    $pupesoft_customers = $this->pupesoft_all_customers;

    echo "var_dump pupesoft_customers:<pre>",var_dump($pupesoft_customers);

    $url = $this->apiurl."/customers/upload/";

    $data_json = json_encode(array("customers" => $pupesoft_customers));

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

    echo "\n\nvar_dump customers:<pre>",var_dump($response);

    $this->logger->log('---------Asiakkaiden p�ivitys valmis---------');
  }

  public function set_all_customers($value) {
    if (is_array($value)) {
      $this->pupesoft_all_customers = $value;
    }
  }
}
