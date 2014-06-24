<?php

/**
 * LUMO-API simple TCP/IP maksupääteclient, jolla voi lähettää ja vastaanottaa XML-sanomia
 * maksupäätteelle
 */

class LumoClient {

  /**
   * Logging päällä/pois
   */
  const LOGGING = true;

  /**
   *  Avattu socket
   */
  private $_socket = false;
  
  /**
   *  Yhteyden tila
   */
  private $_connection = false;
   
  /**
   * Tämän yhteyden aikana sattuneiden virheiden määrä
   */
  private $_error_count = 0;

  /**
   * Constructor
   *
   * @param string  $address          IP address where Lumo is listening
   * @param string  $service_port     PORT number where Lumo is listening
   */
  function __construct($address, $service_port) {

    try {

      $this->log("Maksupääteyhteys avattiin\n");
      set_time_limit(0);
      ob_implicit_flush();

      $this->_socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
      if ($this->_socket === false) {
        $this->log("socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "\n");
        $this->_error_count++;
      }

      $this->log("Attempting to connect to '$address' on port '$service_port'...");
      $this->_connection = socket_connect($this->_socket, $address, $service_port);
      if ($this->_connection === false) {
        $this->log("socket_connect() failed.\nReason: ($this->_connection) " . socket_strerror(socket_last_error($this->_socket)) . "\n");
        $this->_error_count++;
      }

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
    $this->log("Closing socket...");
    socket_close($this->_socket);
  }
  
  function setVariables($amount, $transaction_type = 0) {
    //<?xml version='1.0' encoding='utf-8'
    $in = "<EMVLumo xmlns='http://www.luottokunta.fi/EMVLumo'> 
      <SetTransactionType><Value>{$transaction_type}</Value></SetTransactionType></EMVLumo>\0";
    $out = '';
    $this->log("Sending XML request SetTransactionType...\n");
    socket_write($this->_socket, $in, strlen($in));
    while ($out = socket_read($this->_socket, 2048)) {
      echo $out;
      //$xml = @simplexml_load_string($out);
      //var_dump($xml);
      /*foreach ($xml as $lel) {
        var_dump($lel);
      }*/
    }
    $in = "<EMVLumo xmlns='http://www.luottokunta.fi/EMVLumo'> 
      <SetAmount><Value>{$transaction_type}</Value></SetAmount></EMVLumo>\0";
    $out = '';
    $this->log("Sending XML request SetAmount...\n");
    socket_write($this->_socket, $in, strlen($in));
    while ($out = socket_read($this->_socket, 2048)) {
      echo $out;
      //$xml = @simplexml_load_string($out);
      //var_dump($xml);
      /*foreach ($xml as $lel) {
        var_dump($lel);
      }*/
    }
    
  }
  
  /**
   * Start transaction
   */
  function startTransaction($amount, $transaction_type = 0) {

    $in = "<EMVLumo xmlns='http://www.luottokunta.fi/EMVLumo'> 
      <SetAmount><Value>{$amount}</Value></SetAmount></EMVLumo>\0";

    $this->log("Sending XML request SetAmount...\n");
    $this->log($in);
    socket_write($this->_socket, $in, strlen($in));

    $in = "<EMVLumo xmlns='http://www.luottokunta.fi/EMVLumo'><MakeTransaction><TransactionType>0</TransactionType></MakeTransaction></EMVLumo>\0";
    $out = '';

    $this->log("Sending XML request StartTransaction...\n");
    socket_write($this->_socket, $in, strlen($in));
    $this->log($in);
    $this->log("Reading response:\n");
    //echo "start transaction response: \n";
    while ($out = socket_read($this->_socket, 2048)) {
      //echo $out;
      $xml = @simplexml_load_string($out);
      $saddka = $xml->MakeTransaction->Result;
      if(isset($saddka)) echo "MORO".$saddka;
      /*foreach ($xml->MakeTransaction->Result as $asd) {
        echo "MORO".$asd;
      }*/
      var_dump($xml);
      /*foreach ($xml as $lel) {
        var_dump($lel);
      }*/
    }
    
  }
  
  /**
   * Cancel transaction
   */
  function cancelTransaction() {
    $in = "<EMVLumo xmlns='http://www.luottokunta.fi/EMVLumo'> 
    <CancelTransaction /></EMVLumo>\0";
    $this->log("Sending XML request CancelTransaction...\n");
    socket_write($this->_socket, $in, strlen($in));

    $this->log("Reading response:\n");
    echo "cancel transaction response:\n";

    var_dump(socket_read($this->_socket, 2048));
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