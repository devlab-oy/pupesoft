<?php

/**
 * LUMO-API simple TCP/IP maksup‰‰teclient, jolla voi l‰hett‰‰ ja vastaanottaa XML-sanomia
 * maksup‰‰tteelle
 */


class LumoClient {

  /**
   * Logging p‰‰ll‰/pois
   */
  const LOGGING = true;

  /**
   *  Avattu socket
   */
  private $_socket = false;

  /**
   * T‰m‰n yhteyden aikana sattuneiden virheiden m‰‰r‰
   */
  private $_error_count = 0;


  /**
   * Constructor
   *
   * @param string  $address      IP address where Lumo is listening
   * @param string  $service_port PORT number where Lumo is listening
   */
  function __construct($address, $service_port) {
    try {
      $this->log("Avataan maksup‰‰teyhteytt‰");
      $this->_socket = stream_socket_client("{$address}:{$service_port}", $errno, $errstr, 10);

      if (!$this->_socket) {
        $this->log("{$errno} {$errstr}");
      }
      else {
        $this->log("Maksup‰‰teyhteys avattu");
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
      $this->log("Maksup‰‰teyhteys suljettiin");
    }
    else {
      $this->log("Maksup‰‰teyhteytt‰ ei suljettu onnistuneesti");
    }
  }


  /**
   * Start transaction
   *
   * @param int     $amount           Maksettava m‰‰r‰. Aina positiitiven integer
   * @param int     $transaction_type Maksun tyyppi. 0: Maksu, 1: hyvitys / peruutus
   * @param string  $archive_id       Arkistotunnus. T‰ytyy antaa, jos kyseess‰ on hyvitys tai peruutus
   *
   * @return bool
   */
  function startTransaction($amount, $transaction_type = 0, $archive_id = '') {
    $return = false;

    if ($archive_id != '') {
      $tyyppi = 'hyvitys/peruutus';
    }
    else {
      $tyyppi = "maksu";
    }

    $in = "<EMVLumo xmlns='http://www.luottokunta.fi/EMVLumo'>
             <SetAmount>
               <Value>{$amount}</Value>
             </SetAmount>
           </EMVLumo>\0";

    // Jos kutsussa on setattu archive_id lis‰t‰‰n se myˆs sanomaan koska kyseess‰ on
    // Peruutus/hyvitystapahtuma
    if ($archive_id != '') {
      $in .= "<EMVLumo xmlns='http://www.luottokunta.fi/EMVLumo'>
                <SetArchiveID>
                  <Value>{$archive_id}</Value>
                </SetArchiveID>
              </EMVLumo>\0";
    }

    $in .= "<EMVLumo xmlns='http://www.luottokunta.fi/EMVLumo'>
             <MakeTransaction>
               <TransactionType>{$transaction_type}</TransactionType>
             </MakeTransaction>
           </EMVLumo>\0";

    $viesti = "Aloitetaan {$tyyppi}tapahtuma,\t" .
      "Tyyppi: {$transaction_type}\t/" .
      "Summa:{$amount}\t/" .
      "Viite: {$archive_id}";

    $this->log($viesti);

    fwrite($this->_socket, $in);

    $out = '';

    while ($patka = fgets($this->_socket)) {
      $out .= $patka;
    }

    $stringit = explode("\0", $out);

    foreach ($stringit as $stringi) {
      $xml = simplexml_load_string($stringi);

      if (isset($xml) and isset($xml->MakeTransaction->Result)) {
        $return = $xml->MakeTransaction->Result == "True" ? true : false;
        $arvo   = $return === true ? "OK" : "HYLƒTTY";

        $this->log("\t{$tyyppi}tapahtuma {$arvo}");
      }

      if (isset($xml) and isset($xml->StatusUpdate->StatusInfo)) {
        $leelo = $xml->StatusUpdate->StatusInfo;

        if ($leelo == "CMD MANUAL_AUTH") {
          $this->log("K‰sivarmenne havaittu");
        }
      }
    }

    return $return;
  }


  /**
   * Hakee edellisen tapahtuman asiakaskuitin
   */
  function getCustomerReceipt() {
    $return = "";

    $in = "<EMVLumo xmlns='http://www.luottokunta.fi/EMVLumo'>
             <GetReceiptCustomer/>
           </EMVLumo>\0";

    for ($i = 1; $i < 4; $i++) {
      fwrite($this->_socket, $in);

      $this->log("Haetaan asiakkaan kuittia, yritys {$i}/3");

      $out = "";

      while ($patka = fgets($this->_socket)) {
        $out .= $patka;
      }

      $stringit = explode("\0", $out);

      foreach ($stringit as $stringi) {
        $xml = simplexml_load_string($stringi);

        if (isset($xml) and isset($xml->GetReceiptCustomer->Result)) {
          $return = $xml->GetReceiptCustomer->Result;

          if (!empty($return)) break;
        }
      }

      if (empty($return)) {
        sleep(3);
      }
      else {
        break;
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
    $return = "";

    $in = "<EMVLumo xmlns='http://www.luottokunta.fi/EMVLumo'>
             <GetReceiptMerchant/>
           </EMVLumo>\0";

    for ($i = 1; $i < 4; $i++) {
      fwrite($this->_socket, $in);

      $this->log("Haetaan kauppiaan kuittia, yritys {$i}/3");

      $out = "";

      while ($patka = fgets($this->_socket)) {
        $out .= $patka;
      }

      $stringit = explode("\0", $out);

      foreach ($stringit as $stringi) {
        $xml = simplexml_load_string($stringi);

        if (isset($xml) and isset($xml->GetReceiptMerchant->Result)) {
          $return = $xml->GetReceiptMerchant->Result;

          if (!empty($return)) break;
        }
      }

      if (empty($return)) {
        sleep(3);
      }
      else {
        break;
      }
    }

    $msg = $return == '' ? "Kauppiaan kuittia ei haettu" : "Kauppiaan kuitti haettu";

    $this->log($msg);

    return $return;
  }

  function getPreviousReceipts($archive_id) {
    $return = array();
    $haettu = false;
    $in = "<EMVLumo xmlns='http://www.luottokunta.fi/EMVLumo'>
             <GetPreviousReceipts>
               <ArchiveID>{$archive_id}</ArchiveID>
               <GetReceiptInfo>true</GetReceiptInfo>
             </GetPreviousReceipts>
           </EMVLumo>\0";

    for ($i = 1; $i < 4; $i++) {
      fwrite($this->_socket, $in);

      $this->log("Haetaan kuitteja tunnuksella {$archive_id}, yritys {$i}");

      $out = "";

      while ($patka = fgets($this->_socket)) {
        $out .= $patka;
      }

      $stringit = explode("\0", $out);

      foreach ($stringit as $stringi) {
        $xml = simplexml_load_string($stringi);

        if ($xml) {
          $return["kauppiaan_kuitti"] = simplexml_load_string($xml->GetPreviousReceipts->Result);
          $return["kauppiaan_kuitti"] = (string) $return["kauppiaan_kuitti"]->Receipt[0]->Text;

          $return["asiakkaan_kuitti"] = simplexml_load_string($xml->GetPreviousReceipts->Result);
          $return["asiakkaan_kuitti"] = (string) $return["asiakkaan_kuitti"]->Receipt[1]->Text;

          $haettu = !empty($return["kauppiaan_kuitti"]) and !empty($return["asiakkaan_kuitti"]);
        }

        if ($haettu) {
          break;
        }
      }

      if ($haettu) {
        break;
      }
      else {
        sleep(3);
      }
    }

    $msg = $return == "" ? "Kuitteja ei haettu" : "Kuitit haettu onnistuneesti";

    $this->log($msg);

    return $return;
  }

  /**
   * Hakee error_countin:n
   *
   * @return int  virheiden m‰‰r‰
   */
  public function getErrorCount() {
    return $this->_error_count;
  }

  /**
   * Virhelogi
   *
   * @param string  $message   Virheviesti
   * @param exception|string $exception $exception Exception
   */
  private function log($message, $exception = '') {
    if (self::LOGGING !== true) {
      return;
    }

    if (!empty($exception)) {
      $message .= " (" . $exception->getMessage() . ") faultcode: " . $exception->faultcode;
    }

    pupesoft_log('lumo_client', $message);
  }
}
