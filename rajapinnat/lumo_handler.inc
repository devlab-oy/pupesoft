<?php

/**
 * Lumo-handler ajaa taustalla maksup‰‰tetapahtumia ja tallentaa liikkuvat tiedot kantaan.
 * Tarvitaan $tilausnumero, $korttimaksu (summa)
 * Maksutapahtuman tila tarkastetaan ensin aina tietokannasta ja ajetaan vasta sitten maksup‰‰tteelle
 * Settaa lopuksi muuttujat k‰yttˆliittym‰‰ varten 
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
// Jos kirjatut suoritukset kattavat jo koko avoinna olevan summan ei tehd‰ lis‰‰ maksup‰‰tetapahtumia
if ($vertailtava_summa_int >= $kaikkiyhteensa_int) {
  return 0;
}
// TODO suoritustyyppi: maksu tai hyvitys/peruutus (cancel/refund, tarvitsee archiveid(aviite?, lˆytyy kuitilta))
foreach($suoritukset as $suoritus) {

  // Jos salasanat.phpst‰ puuttuu lumon osoite tai portti
  if(!isset($lumo_service_address) or !isset($lumo_service_port)) {
    $onnistuuko = t("Maksup‰‰tett‰ ei ole konfiguroitu oikein");
    break;
  }

  // Jos puuttuu jostain syyst‰ tilausnumero
  if(!isset($tilausnumero) or trim($tilausnumero) == '') {
    $onnistuuko = t("Tilausnumero kadonnut, maksutapahtumaa ei voi tehd‰");
    break;
  }

  // Jos summa on v‰hint‰‰n 0.01 EUR voidaan tehd‰ maksutapahtuma
  if(is_numeric($korttimaksu_f) and $korttimaksu_f >= 1) {
    // Jos maksutapahtuma ep‰onnistui niin ei tehd‰ mit‰‰n
    if ($suoritus > 1 and $onnistuuko != t("VELOITETTU")) break;

    $clientti = new LumoClient($lumo_service_address, $lumo_service_port);

    if ($clientti->getErrorCount() > 0) {
      $onnistuuko = t("Ei yhteytt‰ maksup‰‰tteeseen");
      break;
    }

    switch ($suoritus) {
      case '1':
        $onnistuuko = $clientti->startTransaction($korttimaksu_f,1,140711001007);
        $onnistuuko = $onnistuuko === TRUE ? t("VELOITETTU") : t("HYLƒTTY");
        break;

      case '2':
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
          // Failsafe jos kuitilta ei jostain syyst‰ lˆydy pankki/luottokorttiteksti‰ Suomeksi
          $pankkivailuotto = 'PANKKIKORTTI';
          $maksupaate_maksetut['pankkikortti'] += $korttimaksu;
        }
        if (strpos($kuitti1, "PERUUTUS")) {
          //TODO peruutustapahtumat kirjataan skˆtsibˆtsimˆtsilˆlˆlˆˆ
        }
        
        break;

      case '3':
        $kuitti2 = $clientti->getMerchantReceipt();
        $kuitti2 = utf8_decode($kuitti2);
        if (strpos($kuitti2, "ALLEKIRJOITUS")) {
          //TODO tulostetaan kauppiaankuitti samantein allekirjoitusta varten
        }
        break;
    }

    // Tuhotaan objekti ett‰ socketti sulkeutuu
    unset($clientti);
  }
  else {
    $onnistuuko = t("Tarkista summa");
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
// Tyhjennet‰‰n handlerissa setattavat muuttujat jotka eiv‰t mene k‰yttˆliittym‰‰n
unset($kuittilisa);
unset($kuitti1);
unset($kuitti2);
unset($onnistuuko);
unset($status);
unset($pankkivailuotto);
