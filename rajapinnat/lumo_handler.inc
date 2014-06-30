<?php

/**
 * Lumo-handler ajaa taustalla maksupäätetapahtumia ja tallentaa liikkuvat tiedot kantaan.
 * Tarvitaan $tilausnumero, $maksut array (pankkikorttisumma, luottokorttisumma)
 * Maksutapahtuman tila tarkastetaan ensin aina tietokannasta ja ajetaan vasta sitten maksupäätteelle
 * Settaa lopuksi muuttujat käyttöliittymää varten
 **/
require("rajapinnat/lumo_client.php");
echo "olen maksupaatesumma kisssa  = $korttimaksu <br>";

$korttimaksu = str_replace(",",".", $korttimaksu);
$korttimaksu_f = $korttimaksu * 100;
echo "olen F-summa koirra $korttimaksu_f";
//return 0;

$suoritukset = array (1,2);

foreach($suoritukset as $suoritus) {
  
  if(isset($lumo_service_address) and isset($lumo_service_port) and is_numeric($korttimaksu_f) and $korttimaksu_f > 0) {

    $clientti = new LumoClient($lumo_service_address, $lumo_service_port);

    if ($clientti->getErrorCount() > 0) {
      $onnistuuko = "HYLÄTTY";
      exit;
    }
    
    switch ($suoritus) {
      case '1':
        $onnistuuko = $clientti->startTransaction($korttimaksu_f);
        $onnistuuko = $onnistuuko === TRUE ? "VELOITETTU" : "HYLÄTTY";
        break;
    
      case '2': 
        $kuitti1 = $clientti->getCustomerReceipt();
        $pankkivailuotto = '';
        if (strpos($kuitti1, "LUOTTOKORTTI") !== FALSE) {
          echo "<br>MAKSU TEHTIIN LUOTTOKORTILLA<br>";
          
        }
        elseif (strpos($kuitti1, "PANKKIKORTTI") !== FALSE) {
          echo "<br>MAKSU TEHTIIN PANKKIKORTILLA<br>";
        }
        break;
    
      case '3':
        $kuitti2 = $clientti->getMerchantReceipt();
        break;
    }

    // Tuhotaan objekti että socketti sulkeutuu
    unset($clientti);
  }
  /*if($suoritus != "getCustomerReceipt()") {  
  sleep(3);
 }*/
}

$maksupaate_maksetut['luottokortti'] = $korttimaksu;
$maksupaate_maksetut['pankkikortti'];

$salamuuttuja1 = "<td>$onnistuuko</td>";
