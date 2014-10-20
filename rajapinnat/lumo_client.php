<?php

/**
 * LUMO-API simple TCP/IP maksupääteclient, jolla voi lähettää ja vastaanottaa XML-sanomia
 * maksupäätteelle
 */
class LumoClient
{

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
   * @param string $address IP address where Lumo is listening
   * @param string $service_port PORT number where Lumo is listening
   */
  function __construct($address, $service_port) {
    try {
      $this->log("Avataan maksupääteyhteyttä");
      $this->_socket = stream_socket_client("{$address}:{$service_port}", $errno, $errstr, 10);

      if (!$this->_socket) {
        $this->log("{$errno} {$errstr}");
      }
      else {
        $this->log("Maksupääteyhteys avattu");
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
    if (fclose($this->_socket)) {
      $this->log("Maksupääteyhteys suljettiin");
    }
    else {
      $this->log("Maksupääteyhteyttä ei suljettu onnistuneesti");
    }
  }

  /**
   * Start transaction
   */
  function startTransaction($amount, $transaction_type = 0, $archive_id = '') {
    $return = false;

    // Setataan transaction amount
    $in = "<EMVLumo xmlns='http://www.luottokunta.fi/EMVLumo'> 
             <SetAmount>
               <Value>{$amount}</Value>
             </SetAmount>
           </EMVLumo>\0";

    fwrite($this->_socket, $in);

    // Jos kutsussa on setattu archive_id lisätään se myös sanomaan koska kyseessä on
    // Peruutus/hyvitystapahtuma
    $tyyppi = 'maksu';

    if ($archive_id != '') {
      $bonusin = "<EMVLumo xmlns='http://www.luottokunta.fi/EMVLumo'>
                    <SetArchiveID>
                      <Value>{$archive_id}</Value>
                    </SetArchiveID>
                  </EMVLumo>\0";
      $tyyppi  = 'hyvitys/peruutus';
      fwrite($this->_socket, $bonusin);
    }

    $in = "<EMVLumo xmlns='http://www.luottokunta.fi/EMVLumo'>
             <MakeTransaction>
               <TransactionType>{$transaction_type}</TransactionType>
             </MakeTransaction>
           </EMVLumo>\0";

    $out = '';

    $viesti = "Aloitetaan {$tyyppi}tapahtuma,\t" .
      "Tyyppi: {$transaction_type}\t/" .
      "Summa:{$amount}\t/" .
      "Viite: {$archive_id}";

    $this->log($viesti);

    fwrite($this->_socket, $in);

    while ($out = fgets($this->_socket)) {
      $stringit = explode("\0", $out);

      foreach ($stringit as $stringi) {
        $xml = @simplexml_load_string($stringi);

        if (isset($xml) and isset($xml->MakeTransaction->Result)) {
          $return = $xml->MakeTransaction->Result == "True" ? true : false;
          $arvo   = $return === true ? "OK" : "HYLÄTTY";

          $this->log("\t{$tyyppi}tapahtuma {$arvo}");
        }

        if (isset($xml) and isset($xml->StatusUpdate->StatusInfo)) {
          $leelo = $xml->StatusUpdate->StatusInfo;

          if ($leelo == "CMD MANUAL_AUTH") {
            $this->log("Käsivarmenne havaittu");
          }
        }
      }
    }

    return $return;
  }

  /**
   * Hakee edellisen tapahtuman asiakaskuitin
   */
  function getCustomerReceipt() {

    $return = '';

    $in = "<EMVLumo xmlns='http://www.luottokunta.fi/EMVLumo'>
             <GetReceiptCustomer/>
           </EMVLumo>\0";

    fwrite($this->_socket, $in);

    $this->log("Haetaan asiakkaan kuittia");

    while ($patka = fgets($this->_socket)) {
      $out .= $patka;
    }

    $stringit = explode("\0", $out);

    foreach ($stringit as $stringi) {
      $xml = @simplexml_load_string($out);

      if (isset($xml) and isset($xml->GetReceiptCustomer->Result)) {
        $return = $xml->GetReceiptCustomer->Result;
      }
    }

    $msg = $return == '' ? "Asiakkaan kuittia ei haettu" : "Asiakkaan kuitti haettu";

    $this->log($msg);

    return $return;
  }

  /**
   * Hakee edellisen tapahtuman kauppiaskuitin
   */
  function getMerchantReceipt() {

    $return = '';

    $in = "<EMVLumo xmlns='http://www.luottokunta.fi/EMVLumo'>
             <GetReceiptMerchant/>
           </EMVLumo>\0";

    fwrite($this->_socket, $in);

    $this->log("Haetaan kauppiaan kuittia");

    while ($patka = fgets($this->_socket)) {
      $out .= $patka;
    }

    $stringit = explode("\0", $out);

    foreach ($stringit as $stringi) {
      $xml = @simplexml_load_string($out);

      if (isset($xml) and isset($xml->GetReceiptMerchant->Result)) {
        $return = $xml->GetReceiptMerchant->Result;
      }
    }

    $msg = $return == '' ? "Kauppiaan kuittia ei haettu" : "Kauppiaan kuitti haettu";

    $this->log($msg);

    return $return;
  }

  /**
   * Hakee error_countin:n
   * @return int  virheiden määrä
   */
  public function getErrorCount() {
    return $this->_error_count;
  }

  /**
   * Virhelogi
   *
   * @param string    $message Virheviesti
   * @param exception $exception Exception
   */
  private function log($message, $exception = '') {

    if (self::LOGGING == true) {
      $timestamp = date('d.m.y H:i:s');
      $message   = utf8_encode($message);

      if ($exception != '') {
        $message .= " (" . $exception->getMessage() . ") faultcode: " . $exception->faultcode;
      }

      $message .= "\n";

      error_log("{$timestamp}: {$message}", 3, '/tmp/lumo_log.txt');
    }
  }
}
