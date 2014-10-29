<?php

require_once './presta_client.php';

class PrestaCategories extends PrestaClient {

  public function __construct($url, $api_key) {
    parent::__construct($url, $api_key);
  }
}
