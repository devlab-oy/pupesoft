<?php

/**
 * Lumo-handler ajaa taustalla maksup‰‰tetapahtumia ja tallentaa liikkuvat tiedot kantaan.
 * Tarvitaan $tilausnumero, $maksut array (pankkikorttisumma, luottokorttisumma)
 * Maksutapahtuman tila tarkastetaan ensin aina tietokannasta ja ajetaan vasta sitten maksup‰‰tteelle
 * Settaa lopuksi muuttujat k‰yttˆliittym‰‰ varten 
 **/

require_once("rajapinnat/lumo_client.php");

$korttimaksu = str_replace(",",".", $korttimaksu);
$korttimaksu_f = $korttimaksu * 100;

$suoritukset = array (1,2);



// Tarkistetaan onko onnistuneita suorituksia
$query = "SELECT *
          FROM maksupaatetapahtumat
          WHERE yhtio = '{$kukarow['yhtio']}'
          AND tilausnumero = '$tilausnumero'
          AND maksutapa != ''
          AND status != ''";

$result = pupe_query($query);

$maksettu_pkortilla = 0;
$maksettu_lkortilla = 0;

while ($ruutu = mysql_fetch_assoc($result)) {
  if($ruutu['maksutapa'] == "LUOTTOKORTTI") {
    $maksettu_lkortilla += $ruutu['summa_valuutassa'];
  }
  else {
    $maksettu_pkortilla += $ruutu['summa_valuutassa']; 
  }
}

$kaikkiyhteensa_int = $kaikkiyhteensa * 100;
$vertailtava_summa_int = ($maksettu_pkortilla + $maksettu_lkortilla) * 100;

echo "suorituksia yhteensa kaikkiaan $kaikkiyhteensa_int <br>";
echo "vertailtavasummaint $vertailtava_summa_int <br>";

$maksupaate_maksetut['luottokortti'] = $maksettu_lkortilla;
$maksupaate_maksetut['pankkikortti'] = $maksettu_pkortilla;
// Jos kirjatut suoritukset kattavat jo koko vertailtavan summan ei tehd‰ lis‰‰ maksup‰‰tetapahtumia
if ($vertailtava_summa_int >= $kaikkiyhteensa_int) {
  return 0;
}

echo "maksut ei riitt‰ny viel‰ <br>";


foreach($suoritukset as $suoritus) {

  if(isset($lumo_service_address) and isset($lumo_service_port) and is_numeric($korttimaksu_f) and $korttimaksu_f > 0) {
    
    $clientti = new LumoClient($lumo_service_address, $lumo_service_port);

    if ($clientti->getErrorCount() > 0) {
      $onnistuuko = t("HYLƒTTY");
      exit;
    }
    
    switch ($suoritus) {
      case '1':
        $onnistuuko = $clientti->startTransaction($korttimaksu_f);
        $onnistuuko = $onnistuuko === TRUE ? t("VELOITETTU") : t("HYLƒTTY");
        break;
    
      case '2':
        if ($onnistuuko != 'VELOITETTU') break;
        $kuitti1 = $clientti->getCustomerReceipt();
        $kuitti1 = utf8_decode($kuitti1);
        $pankkivailuotto = '';
        if (strpos($kuitti1, "LUOTTOKORTTI") !== FALSE) {
          echo "<br>MAKSU TEHTIIN LUOTTOKORTILLA<br>";
          $pankkivailuotto = 'LUOTTOKORTTI';
          $maksupaate_maksetut['luottokortti'] += $korttimaksu;
        }
        elseif (strpos($kuitti1, "PANKKIKORTTI") !== FALSE) {
          echo "<br>MAKSU TEHTIIN PANKKIKORTILLA<br>";
          $pankkivailuotto = 'PANKKIKORTTI';
          $maksupaate_maksetut['pankkikortti'] += $korttimaksu;
        }
        else {
          // Ehk‰ t‰ss‰ keississ‰ pit‰‰ feilata kokonaan kun tarkoittaa periaatteessa sit‰ ett‰ kuittia ei tule
          $pankkivailuotto = 'PANKKIKORTTI';
          $maksupaate_maksetut['pankkikortti'] += $korttimaksu;
        }
        
        break;
    
      case '3':
        $kuitti2 = $clientti->getMerchantReceipt();
        break;
    }

    // Tuhotaan objekti ett‰ socketti sulkeutuu
    unset($clientti);
  }
}




$status = $onnistuuko == "VELOITETTU" ? 'K' : '';
// P‰ivitet‰‰n suoritus kantaan jos se onnistui
if ($status == 'K') {

  $kuittilisa = "";

  if(!empty($kuitti1)) {
    $kuittilisa .= " asiakkaan_kuitti = '{$kuitti1}', ";
  }

  if(!empty($kuitti2)) {
    $kuittilisa .= " kauppiaan_kuitti = '{$kuitti2}', ";
  }

  $query = "INSERT INTO maksupaatetapahtumat SET
            yhtio = '{$kukarow['yhtio']}',
            $kuittilisa
            maksutapa = '{$pankkivailuotto}',
            luontiaika  = now(),
            muutospvm   = now(),
            laatija = 'Lumo',
            valkoodi = '{$laskurow['valkoodi']}',
            status = '{$status}',
            tilausnumero = '{$tilausnumero}',
            summa_valuutassa = $korttimaksu
            ";
  $result = pupe_query($query);
  $korttimaksu = '';
}
else {
  $salamuuttuja1 = "<td>$onnistuuko</td>";
}
//$maksupaate_maksetut['luottokortti'] += $korttimaksu;
//$maksupaate_maksetut['pankkikortti'];
