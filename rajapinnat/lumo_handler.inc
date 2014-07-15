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
          AND tila = 'K'";

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
// TODO suoritustyyppi: maksu tai hyvitys/peruutus (cancel/refund, tarvitsee archiveid(aviite?, löytyy kuitilta))
foreach($suoritukset as $suoritus) {

  if ($suoritus == 1) $onnistuuko = false;

  // Jos salasanat.phpstä puuttuu lumon osoite tai portti
  if(!isset($lumo_service_address) or !isset($lumo_service_port)) {
    $onnistuuko = "Maksupäätettä ei ole konfiguroitu oikein";
    break;
  }

  // Jos puuttuu jostain syystä tilausnumero
  if(!isset($tilausnumero) or trim($tilausnumero) == '') {
    $onnistuuko = "Tilausnumero kadonnut, maksutapahtumaa ei voi tehdä";
    break;
  }

  // Jos summa on vähintään 0.01 EUR voidaan tehdä maksutapahtuma
  if(is_numeric($korttimaksu_f)) {
    // Jos maksutapahtuma epäonnistui niin ei tehdä mitään
    if ($suoritus > 1 and $onnistuuko != "VELOITETTU") break;

    $clientti = new LumoClient($lumo_service_address, $lumo_service_port);

    if ($clientti->getErrorCount() > 0) {
      $onnistuuko = "Ei yhteyttä maksupäätteeseen";
      break;
    }
    
    switch ($suoritus) {
      case '1':
        // Jos summa on vähintään 0.01 EUR voidaan tehdä maksutapahtuma
        if ($korttimaksu_f >= 1) {
         $onnistuuko = $clientti->startTransaction($korttimaksu_f);
        }
        elseif($korttimaksu_f < 0) {
          // Jos summa on negatiivinen tehdään hyvitys/peruutus
          // Dummyarkistointitunnus
          $arkistointitunnus = 999999999999;
          // Haetaan viimeisimmän onnistuneen maksusuorituksen kuitti
          $query = "SELECT asiakkaan_kuitti
                    FROM maksupaatetapahtumat
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND tilausnumero = '{$tilausnumero}'
                    AND tila = 'K'
                    AND asiakkaan_kuitti != ''
                    ORDER BY luontiaika desc
                    LIMIT 1";
          $res = pupe_query($query);
          $kuittirivi = mysql_fetch_assoc($res);

          if(isset($kuittirivi['asiakkaan_kuitti']) and !empty($kuittirivi['asiakkaan_kuitti'])) {
            $kaikki_kuittirivit = explode("\n",$kuittirivi['asiakkaan_kuitti']);

            foreach($kaikki_kuittirivit as $kuitin_rivi) {
              list($rivinotsikko, $rivintieto) = explode(":",$kuitin_rivi);
              // Etsitään kuittiriviltä arkistointitunnusta
              if(trim($rivinotsikko) == "AVIITE") {
                $arkistointitunnus = trim($rivintieto);
                break;
              }
            }
          }
          // Jos käyttöliittymässä summa on negatiivinen, muutetaan maksupäätteelle
          $korttimaksu_f = $korttimaksu_f * -1;
          $onnistuuko = $clientti->startTransaction($korttimaksu_f,1,$arkistointitunnus);
        }
        else {
          $onnistuuko = "HYLÄTTY";
        }
        $onnistuuko = $onnistuuko === true ? "VELOITETTU" : "HYLÄTTY";
        break;

      case '2':
        $kuitti1 = $clientti->getCustomerReceipt();
        $kuitti1 = utf8_decode($kuitti1);
        $pankkivailuotto = '';
        $maksutyyppi = '';

        if(strpos($kuitti1, "LUOTTOKORTTI") !== false) {
          $pankkivailuotto = 'LUOTTOKORTTI';
          $maksupaate_maksetut['luottokortti'] += $korttimaksu;
        }
        elseif(strpos($kuitti1, "PANKKIKORTTI") !== false) {
          $pankkivailuotto = 'PANKKIKORTTI';
          $maksupaate_maksetut['pankkikortti'] += $korttimaksu;
        }
        else {
          // Failsafe jos kuitilta ei jostain syystä löydy pankki/luottokorttitekstiä Suomeksi
          $pankkivailuotto = 'PANKKIKORTTI';
          $maksupaate_maksetut['pankkikortti'] += $korttimaksu;
        }
       
        if(strpos($kuitti1, "PERUUTUS") !== false) {
          $maksutyyppi = "PERUUTUS";
        }
        elseif(strpos($kuitti1, "HYVITYS") !== false) {
          $maksutyyppi = "HYVITYS";
        }
        elseif(strpos($kuitti1, "OSTO") !== false) {
          $maksutyyppi = "OSTO";
        }
        else {
          $maksutyyppi = "FAIL";
        }

        break;

      case '3':
        $kuitti2 = $clientti->getMerchantReceipt();
        $kuitti2 = utf8_decode($kuitti2);
        if (strpos($kuitti2, "ALLEKIRJOITUS")) {
          // TODO
          //tulostakuitti($kuitti2);
        }
        break;
    }

    // Tuhotaan objekti että socketti sulkeutuu
    unset($clientti);
    // Venataan sekka koska socketti on joskus hidas ja halutaan seuraava transaktio
    if ($suoritus < 3) sleep(1);
  }
  else {
    $onnistuuko = "Tarkista summa";
  }
}

// Kirjataan suoritus lopuksi
$status = '';
echo "onnistuuko $onnistuuko / maksutyyppi $maksutyyppi \n";
// Setataan oikea arvo tapahtumalle
if ($onnistuuko == "VELOITETTU" and ($maksutyyppi == 'OSTO')) {
  // K - Korttimaksu
  $status = 'K';
}
elseif ($onnistuuko == "VELOITETTU" and ($maksutyyppi == 'PERUUTUS' or $maksutyyppi == "HYVITYS")) {
  // H - Hyvitys
  $status = 'H';
}

// Päivitetään suoritus kantaan jos se onnistui
if ($status == 'K' or $status == 'H') {

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
  $korttimaksutapahtuman_status = $onnistuuko === false ? "HYLÄTTY" : $onnistuuko;
}
// Tyhjennetään handlerissa setattavat muuttujat jotka eivät mene käyttöliittymään
unset($kuittilisa);
unset($kuitti1);
unset($kuitti2);
unset($onnistuuko);
unset($status);
unset($pankkivailuotto);
unset($maksutyyppi);
