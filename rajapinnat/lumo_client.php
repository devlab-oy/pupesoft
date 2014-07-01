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

      $this->log("Avataan maksupääteyhteyttä\n");
      set_time_limit(0);
      ob_implicit_flush();

      $this->_socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
      if ($this->_socket === false) {
        $this->log("socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "\n");
        $this->_error_count++;
      }

      $this->log("Yhdistetään '$address' porttiin '$service_port'...");
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
    socket_close($this->_socket);
  }

  /**
   * Start transaction
   */
  function startTransaction($amount, $transaction_type = 0) {
    
    $return = false;

    // Setataan transaction amount
    $in = "<EMVLumo xmlns='http://www.luottokunta.fi/EMVLumo'> 
      <SetAmount><Value>{$amount}</Value></SetAmount></EMVLumo>\0";

    socket_write($this->_socket, $in, strlen($in));

    $in = "<EMVLumo xmlns='http://www.luottokunta.fi/EMVLumo'><MakeTransaction><TransactionType>0</TransactionType></MakeTransaction></EMVLumo>\0";
    $out = '';

    $this->log("Aloitetaan maksutapahtuma\n\tTyyppi: $transaction_type\n\tSumma: $amount\n");
    socket_write($this->_socket, $in, strlen($in));

    while ($out = socket_read($this->_socket, 2048)) {

      $xml = @simplexml_load_string($out);
      if (isset($xml) and isset($xml->MakeTransaction->Result)) {
        $return = $xml->MakeTransaction->Result == "True" ? TRUE : FALSE;
        $arvo = $return === TRUE ? "OK" : "HYLÄTTY";
        $this->log("Maksutapahtuma $arvo");
      }
    }
    return $return;
  }
  
  /**
   * Hakee edellisen tapahtuman asiakaskuitin
   */
  function getCustomerReceipt() {
    
    $return = '';

    $in = "<EMVLumo xmlns='http://www.luottokunta.fi/EMVLumo'><GetReceiptCustomer/></EMVLumo>\0";
    socket_write($this->_socket, $in, strlen($in));
    while ($out = socket_read($this->_socket, 2048)) {

      $xml = @simplexml_load_string($out);
      if (isset($xml) and isset($xml->GetReceiptCustomer->Result)) {
        $return = $xml->GetReceiptCustomer->Result;
      }
    }
    return $return;
  }

  /**
   * Hakee edellisen tapahtuman kauppiaskuitin
   */  
  function getMerchantReceipt() {
    
    $return = '';

    $in = "<EMVLumo xmlns='http://www.luottokunta.fi/EMVLumo'><GetReceiptMerchant/></EMVLumo>\0";
    socket_write($this->_socket, $in, strlen($in));
    while ($out = socket_read($this->_socket, 2048)) {

      $xml = @simplexml_load_string($out);
      if (isset($xml) and isset($xml->GetReceiptMerchant->Result)) {
        $return = $xml->GetReceiptMerchant->Result;
      }
    }
    return $return;
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