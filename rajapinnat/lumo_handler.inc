<?php

/**
 * Lumo-handler ajaa taustalla maksupäätetapahtumia ja tallentaa liikkuvat tiedot kantaan.
 * Tarvitaan $tilausnumero, $maksut array (pankkikorttisumma, luottokorttisumma)
 * Maksutapahtuman tila tarkastetaan ensin aina tietokannasta ja ajetaan vasta sitten maksupäätteelle
 * Settaa lopuksi muuttujat käyttöliittymää varten
 **/
require("rajapinnat/lumo_client.php");

foreach($maksut as $massu) {
  
  if(isset($lumo_service_address) and isset($lumo_service_port) and is_numeric($massu)) {

    $clientti = new LumoClient($lumo_service_address, $lumo_service_port);

    if ($clientti->getErrorCount() > 0) {
      exit;
    }
    $massu = $massu*100;
    $onnistuuko = $clientti->startTransaction($massu);
    $onnistuuko = $onnistuuko === TRUE ? "VELOITETTU" : "HYLÄTTY";

    echo "skripti loppu, palautti \n $onnistuuko \n";
    if ($onnistuuko) {

      echo "kysytään kuittia \n";
      echo $clientti->getCustomerReceipt(); 

      //echo "kysytään 2kuittia \n";
      //echo $clientti->getMerchantReceipt();
    }
  }
  sleep(2);
}

$salamuuttuja1 = "<td>$onnistuuko</td>";
$salamuuttuja2 = "<td>KISSA</td>";

 