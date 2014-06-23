<?php

/**
 * LUMO-API maksupääteclient 
 */

class LumoClient {

  /**
   * Logging päällä/pois
   */
  const LOGGING = true;

  /**
   *  
   */
   
  /**
   * Tämän yhteyden aikana sattuneiden virheiden määrä
   */
  private $_error_count = 0;

  /**
   * Constructor
   *
   * @param string  $address  IP address where Lumo is listening
   * @param string  $service_port     PORT number where Lumo is listening
   */
  function __construct($address, $service_port) {

    try {
      //$this->_proxy = new LumoClient($address, $service_port, $socket);
      $this->log("Maksupääteyhteys avattiin\n");

      $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
      if ($socket === false) {
        $this->log("socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "\n");
      }
      else {
        $this->_error_count++;
      }

      $this->log("Attempting to connect to '$address' on port '$service_port'...");
      $result = socket_connect($socket, $address, $service_port);
      if ($result === false) {
        $this->log("socket_connect() failed.\nReason: ($result) " . socket_strerror(socket_last_error($socket)) . "\n");
      }
      else {
        $this->_error_count++;
      }

      $in = "<EMVLumo xmlns='http://www.luottokunta.fi/EMVLumo'> 
      <MakeTransaction><TransactionType>0</TransactionType></MakeTransaction></EMVLumo>";
      $out = '';

      $this->log("Sending XML request...\n");
      socket_write($socket, $in, strlen($in));

      $this->log("Reading response:\n");
      while ($out = socket_read($socket, 2048)) {
        echo $out;
      }

      $this->log("Closing socket...");
      socket_close($socket);
    }
    catch (Exception $e) {
      $this->_error_count++;
      $this->log("Virhe! Lumo-class init failed", $e);
    }
  }

  /**
   * Destructor
   */
  function __destruct() {
    $this->log("Maksupääteyhteys suljettiin\n");
  }
  
  /**
   * Hakee error_countin:n
   *
   * @return int  virheiden määrä
   */
  public function getErrorCount() {
    return $this->_error_count;
  }
  
  /**
   * Virhelogi
   *
   * @param string  $message   Virheviesti
   * @param exception $exception Exception
   */
  private function log($message, $exception = '') {

    if (self::LOGGING == true) {
      $timestamp = date('d.m.y H:i:s');
      $message = utf8_encode($message);

    if ($exception != '') {
      $message .= " (" . $exception->getMessage() . ") faultcode: " . $exception->faultcode;
    }

    $message .= "\n";
    error_log("{$timestamp}: {$message}", 3, '/tmp/lumo_log.txt');

    }
  }
}