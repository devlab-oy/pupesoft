<?php

require_once 'logger.php';

class PrestaClient {

  private $logger = null;

  private $url = null;

  public function __construct() {
    $this->logger = new Logger('/tmp/presta_log.txt');
    $this->logger->set_date_format('Y-m-d H:i:s');
  }

  public function __destruct() {

  }
}
