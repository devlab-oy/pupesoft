<?php

/**
 * Lumo-handler ajaa taustalla maksupäätetapahtumia ja tallentaa liikkuvat tiedot kantaan.
 * Tarvitaan $tilausnumero, $korttimaksu (summa)
 * Maksutapahtuman tila tarkastetaan ensin aina tietokannasta ja ajetaan vasta sitten maksupäätteelle
 * Settaa lopuksi muuttujat käyttöliittymää varten 
 **/

require_once("rajapinnat/lumo_client.php");

$korttimaksu = str_replace(",",".", $korttimaksu);
$korttimaksu_f = $korttimaksu * 100;

// 1 = tee maksutapahtuma, 2 = hae asiakkaan kuitti, 3 = hae kauppiaan kuitti
$suoritukset = array (1,2,3);

// Tarkistetaan onko onnistuneita suorituksia
$query = "SELECT *
          FROM maksupaatetapahtumat
          WHERE yhtio = '{$kukarow['yhtio']}'
          AND tilausnumero = '$tilausnumero'
          AND maksutapa != ''
          AND tila != ''";

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

$maksupaate_maksetut['luottokortti'] = $maksettu_lkortilla;
$maksupaate_maksetut['pankkikortti'] = $maksettu_pkortilla;
// Jos kirjatut suoritukset kattavat jo koko avoinna olevan summan ei tehdä lisää maksupäätetapahtumia
if ($vertailtava_summa_int >= $kaikkiyhteensa_int) {
  return 0;
}

foreach($suoritukset as $suoritus) {

  // Jos salasanat.phpstä puuttuu lumon osoite tai portti
  if(!isset($lumo_service_address) or !isset($lumo_service_port)) {
    $onnistuuko = t("Maksupäätettä ei ole konfiguroitu oikein");
    break;
  }

  // Jos puuttuu jostain syystä tilausnumero
  if(!isset($tilausnumero) or trim($tilausnumero) == '') {
    $onnistuuko = t("Tilausnumero kadonnut, maksutapahtumaa ei voi tehdä");
    break;
  }

  // Jos summa on vähintään 0.01 EUR voidaan tehdä maksutapahtuma
  if(is_numeric($korttimaksu_f) and $korttimaksu_f >= 1) {
    
    $clientti = new LumoClient($lumo_service_address, $lumo_service_port);

    if ($clientti->getErrorCount() > 0) {
      $onnistuuko = t("Ei yhteyttä maksupäätteeseen");
      break;
    }
    
    switch ($suoritus) {
      case '1':
        $onnistuuko = $clientti->startTransaction($korttimaksu_f);
        $onnistuuko = $onnistuuko === TRUE ? t("VELOITETTU") : t("HYLÄTTY");
        break;
    
      case '2':
        if ($onnistuuko != t("VELOITETTU")) break;
        $kuitti1 = $clientti->getCustomerReceipt();
        $kuitti1 = utf8_decode($kuitti1);
        $pankkivailuotto = '';
        if (strpos($kuitti1, "LUOTTOKORTTI") !== FALSE) {
          $pankkivailuotto = 'LUOTTOKORTTI';
          $maksupaate_maksetut['luottokortti'] += $korttimaksu;
        }
        elseif (strpos($kuitti1, "PANKKIKORTTI") !== FALSE) {
          $pankkivailuotto = 'PANKKIKORTTI';
          $maksupaate_maksetut['pankkikortti'] += $korttimaksu;
        }
        else {
          // Failsafe jos kuitilta ei jostain syystä löydy pankki/luottokorttitekstiä Suomeksi
          $pankkivailuotto = 'PANKKIKORTTI';
          $maksupaate_maksetut['pankkikortti'] += $korttimaksu;
        }
        
        break;
    
      case '3':
        if ($onnistuuko != t("VELOITETTU")) break;
        $kuitti2 = $clientti->getMerchantReceipt();
        $kuitti2 = utf8_decode($kuitti2);

        break;
    }

    // Tuhotaan objekti että socketti sulkeutuu
    unset($clientti);
  }
  else {
    $onnistuuko = t("Tarkista summa");
  }
}

$status = $onnistuuko == "VELOITETTU" ? 'K' : '';
// Päivitetään suoritus kantaan jos se onnistui
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
            tila = '{$status}',
            tilausnumero = '{$tilausnumero}',
            summa_valuutassa = $korttimaksu
            ";
  $result = pupe_query($query);
  $korttimaksu = '';
}
else {
  $korttimaksutapahtuman_status = $onnistuuko;
}
// Tyhjennetään handlerissa setattavat muuttujat jotka eivät mene käyttöliittymään
unset($kuittilisa);
unset($kuitti1);
unset($kuitti2);
unset($onnistuuko);
unset($status);
unset($pankkivailuotto);
