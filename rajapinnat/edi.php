<?php

class Edi {

  /**
   * Luo edi tilauksen
   *
   * @param array  $order Tilauksen tiedot ja tilauserivit
   * @param string $tyyppi
   *
   * @return true / false
   */


  static function create($order, $tyyppi = "magento") {
    global $pupesoft_tilaustyyppi;

    if (empty($pupesoft_tilaustyyppi)) {
      exit("Parametrej‰ puuttuu\n");
    }

    // require 'magento_salasanat.php' muuttujat, jos kyseess‰ on magento
    if ($tyyppi == "magento") {
      global $magento_api_ht_edi, $ovt_tunnus, $pupesoft_tilaustyyppi, $magento_maksuehto_ohjaus,
             $verkkokauppa_asiakasnro, $rahtikulu_tuoteno, $rahtikulu_nimitys,
             $verkkokauppa_erikoiskasittely;

      if (empty($magento_api_ht_edi) or empty($ovt_tunnus) or empty($verkkokauppa_asiakasnro) or
          empty($rahtikulu_tuoteno) or empty($rahtikulu_nimitys)
      ) {
        exit("Parametrej‰ puuttuu\n");
      }

      $viitteenne = "";
      $yhteyshenkilo =
        "{$order["billing_address"]["lastname"]} {$order['billing_address']['firstname']}";
    }
    else {
      $ovt_tunnus = $order["laskuttajan_ovt"];
      $verkkokauppa_asiakasnro = $order["toim_ovttunnus"];
      $viitteenne = $order["laskun_numero"];
      $yhteyshenkilo = $order["tilausyhteyshenkilo"];
    }

    //Tilauksella k‰ytetyt lahjakortit ei saa vehent‰‰ myynti pupen puolella
    $giftcards = json_decode($order['webtex_giftcard']);
    if (!empty($giftcards)) {
      $giftcard_sum = 0;
      foreach ($giftcards as $giftcard_values) {
        foreach ($giftcard_values as $index => $value) {
          if (!stristr($index, 'classname')) {
            $giftcard_sum += $value;
          }
        }
      }

      $grand_total = $order['grand_total'] + $giftcard_sum;
    }
    else {
      $grand_total = $order['grand_total'];
    }

    // Miten storen nimi?
    //$storenimi = (isset($_COOKIE["store_name"])) ? $_COOKIE["store_name"] : "";
    $storenimi = '';

    //tilauksen otsikko
    //return $order->getUpdatedAt();
    $edi_order  = "*IS from:721111720-1 to:IKH,ORDERS*id:".$order['increment_id']." version:AFP-1.0 *MS\n";
    $edi_order .= "*MS ".$order['increment_id']."\n";
    $edi_order .= "*RS OSTOTIL\n";
    $edi_order .= "OSTOTIL.OT_NRO:".$order['increment_id']."\n";

    $vaihtoehtoinen_ovt = '';
    //Tarkistetaan onko t‰m‰n nimiselle verkkokaupalle asetettu erikoisk‰sittelyj‰
    if (isset($verkkokauppa_erikoiskasittely) and count($verkkokauppa_erikoiskasittely) > 0) {
      $edi_store = str_replace("\n", " ", $order['store_name']);
      foreach ($verkkokauppa_erikoiskasittely as $verkkokauppaparametrit) {
        // $verkkokauppaparametrit[0] - Verkkokaupan nimi
        // $verkkokauppaparametrit[1] - Editilaus_tilaustyyppi
        // $verkkokauppaparametrit[2] - Tilaustyyppilisa
        // $verkkokauppaparametrit[3] - Myyjanumero
        // $verkkokauppaparametrit[4] - Vaihtoehtoinen ovttunnus
        if (strpos($edi_store, $verkkokauppaparametrit[0]) !== false) {
          $vaihtoehtoinen_ovt = $verkkokauppaparametrit[4];
        }
      }
    }

    $valittu_ovt_tunnus = (isset($vaihtoehtoinen_ovt) and !empty($vaihtoehtoinen_ovt)) ? $vaihtoehtoinen_ovt : $ovt_tunnus;
    //Yrityksen ovt_tunnus MUISTA MUUTTAA
    $edi_order .= "OSTOTIL.OT_TOIMITTAJANRO:".$valittu_ovt_tunnus."\n";

    if (!empty($pupesoft_tilaustyyppi)) {
      $edi_order .= "OSTOTIL.OT_TILAUSTYYPPI:$pupesoft_tilaustyyppi\n";
    }

    $edi_order .= "OSTOTIL.VERKKOKAUPPA:".str_replace("\n", " ", $order['store_name'])."\n";
    $edi_order .= "OSTOTIL.OT_VERKKOKAUPPA_ASIAKASNRO:".$order['customer_id']."\n";
    $edi_order .= "OSTOTIL.OT_VERKKOKAUPPA_TILAUSVIITE:".str_replace("\n", " ", $order['reference_number'])."\n";
    $edi_order .= "OSTOTIL.OT_VERKKOKAUPPA_TILAUSNUMERO:".str_replace("\n", " ", $order['order_number'])."\n";
    $edi_order .= "OSTOTIL.OT_VERKKOKAUPPA_KOHDE:".str_replace("\n", " ", $order['target'])."\n";
    $edi_order .= "OSTOTIL.OT_TILAUSAIKA:\n";
    $edi_order .= "OSTOTIL.OT_KASITTELIJA:\n";
    $edi_order .= "OSTOTIL.OT_TOIMITUSAIKA:\n";
    $edi_order .= "OSTOTIL.OT_TOIMITUSTAPA:".$order['shipping_description']."\n";
    $edi_order .= "OSTOTIL.OT_TOIMITUSEHTO:\n";
    //Onko tilaus maksettu = processing vai j‰lkvaatimus = pending_cashondelivery_asp
    $edi_order .= "OSTOTIL.OT_MAKSETTU:".$order['status']."\n";

    $maksuehto = strip_tags($order['payment']['method']);
    // Jos on asetettu maksuehtojen ohjaus, tarkistetaan korvataanko Magentosta tullut maksuehto
    if (isset($magento_maksuehto_ohjaus) and count($magento_maksuehto_ohjaus) > 0) {
      foreach ($magento_maksuehto_ohjaus as $key => $array) {
        if (in_array($maksuehto, $array) and !empty($key)) {
          $maksuehto = $key;
        }
      }
    }

    $edi_order .= "OSTOTIL.OT_MAKSUEHTO:$maksuehto\n";
    $edi_order .= "OSTOTIL.OT_VIITTEEMME:\n";
    $edi_order .= "OSTOTIL.OT_VIITTEENNE:{$viitteenne}\n";
    $edi_order .= "OSTOTIL.OT_VEROMAARA:".$order['tax_amount']."\n";
    $edi_order .= "OSTOTIL.OT_SUMMA:".$grand_total."\n";
    $edi_order .= "OSTOTIL.OT_VALUUTTAKOODI:".$order['order_currency_code']."\n";
    $edi_order .= "OSTOTIL.OT_KLAUSUULI1:\n";
    $edi_order .= "OSTOTIL.OT_KLAUSUULI2:\n";
    $edi_order .= "OSTOTIL.OT_KULJETUSOHJE:\n";
    $edi_order .= "OSTOTIL.OT_LAHETYSTAPA:\n";
    $edi_order .= "OSTOTIL.OT_VAHVISTUS_FAKSILLA:\n";
    $edi_order .= "OSTOTIL.OT_FAKSI:\n";

    $billingadress = str_replace("\n", ", ", $order['billing_address']['street']);
    $shippingadress = str_replace("\n", ", ", $order['shipping_address']['street']);

    //Asiakkaan ovt_tunnus MUISTA MUUTTAA
    $edi_order .= "OSTOTIL.OT_ASIAKASNRO:".$verkkokauppa_asiakasnro."\n";

    // Yritystilauksissa vaihdetaan yrityksen ja tilaajan nimi toisin p‰in
    if (!empty($order['billing_address']['company'])) {
      $edi_order .= "OSTOTIL.OT_YRITYS:".$order['billing_address']['lastname']." ".$order['billing_address']['firstname']."\n";
      $edi_order .= "OSTOTIL.OT_YHTEYSHENKILO:".$order['billing_address']['company']."\n";
    }
    else {
      $edi_order .= "OSTOTIL.OT_YRITYS:".$order['billing_address']['company']."\n";
      $edi_order .= "OSTOTIL.OT_YHTEYSHENKILO:{$yhteyshenkilo}\n";
    }

    $edi_order .= "OSTOTIL.OT_KATUOSOITE:".$billingadress."\n";
    $edi_order .= "OSTOTIL.OT_POSTITOIMIPAIKKA:".$order['billing_address']['city']."\n";
    $edi_order .= "OSTOTIL.OT_POSTINRO:".$order['billing_address']['postcode']."\n";
    $edi_order .= "OSTOTIL.OT_YHTEYSHENKILONPUH:".$order['billing_address']['telephone']."\n";
    $edi_order .= "OSTOTIL.OT_YHTEYSHENKILONFAX:".$order['billing_address']['fax']."\n";
    $edi_order .= "OSTOTIL.OT_MYYNTI_YRITYS:\n";
    $edi_order .= "OSTOTIL.OT_MYYNTI_KATUOSOITE:\n";
    $edi_order .= "OSTOTIL.OT_MYYNTI_POSTITOIMIPAIKKA:\n";
    $edi_order .= "OSTOTIL.OT_MYYNTI_POSTINRO:\n";
    $edi_order .= "OSTOTIL.OT_MYYNTI_MAAKOODI:\n";
    $edi_order .= "OSTOTIL.OT_MYYNTI_YHTEYSHENKILO:\n";
    $edi_order .= "OSTOTIL.OT_MYYNTI_YHTEYSHENKILONPUH:\n";
    $edi_order .= "OSTOTIL.OT_MYYNTI_YHTEYSHENKILONFAX:\n";

    // Yritystilauksissa vaihdetaan yrityksen ja tilaajan nimi toisin p‰in
    if (!empty($order['shipping_address']['company'])) {
      $edi_order .= "OSTOTIL.OT_TOIMITUS_YRITYS:".$order['shipping_address']['lastname']." ".$order['shipping_address']['firstname']."\n";
      $edi_order .= "OSTOTIL.OT_TOIMITUS_NIMI:".$order['shipping_address']['company']."\n";
    }
    else {
      $edi_order .= "OSTOTIL.OT_TOIMITUS_YRITYS:".$order['shipping_address']['company']."\n";
      $edi_order .= "OSTOTIL.OT_TOIMITUS_NIMI:".$order['shipping_address']['lastname']." ".$order['shipping_address']['firstname']."\n";
    }

    $edi_order .= "OSTOTIL.OT_TOIMITUS_KATUOSOITE:".$shippingadress."\n";
    $edi_order .= "OSTOTIL.OT_TOIMITUS_POSTITOIMIPAIKKA:".$order['shipping_address']['city']."\n";
    $edi_order .= "OSTOTIL.OT_TOIMITUS_POSTINRO:".$order['shipping_address']['postcode']."\n";
    $edi_order .= "OSTOTIL.OT_TOIMITUS_MAAKOODI:".$order['shipping_address']['country_id']."\n";
    $edi_order .= "OSTOTIL.OT_TOIMITUS_PUH:".$order['shipping_address']['telephone']."\n";
    $edi_order .= "OSTOTIL.OT_TOIMITUS_EMAIL:".$order['customer_email']."\n";

    $noutopistetunnus = '';
    $tunnisteosa = 'matkahuoltoNearbyParcel_';
    // Jos shipping_method sis‰lt‰‰ tunnisteosan ja sen per‰ss‰ on numero niin otetaan talteen
    if (!empty($order['shipping_method']) and strpos($order['shipping_method'], $tunnisteosa) !== false) {
       $tunnistekoodi = str_replace($tunnisteosa, '', $order['shipping_method']);
       $noutopistetunnus = is_numeric($tunnistekoodi) ? $tunnistekoodi : '';
    }
    $edi_order .= "OSTOTIL.OT_TOIMITUS_NOUTOPISTE_TUNNUS:".$noutopistetunnus."\n";
    $edi_order .= "*RE OSTOTIL\n";

    $i = 1;
    foreach ($order['items'] as $item) {
      if ($item['product_type'] != "configurable") {

        // Tuoteno
        $tuoteno = $item['sku'];
        if (substr($tuoteno, 0, 4) == "SKU_") $tuoteno = substr($tuoteno, 4);

        // Nimitys
        $nimitys = $item['name'];

        // M‰‰r‰
        $kpl = $item['qty_ordered'] * 1;

        // Hinta pit‰‰ hakea is‰lt‰
        $result = search_array_key_for_value_recursive($order['items'], "item_id", $item['parent_item_id']);

        // Lˆyty yks tai enemm‰n, otetaan eka?
        if (count($result) != 0) {
          $_item = $result[0];
        }
        else {
          $_item = $item;
        }

        // Verollinen yksikkˆhinta
        $verollinen_hinta = $_item['original_price'];

        // Veroton yksikkˆhinta
        $veroton_hinta = $_item['price'];

        // Rivin alennusprosentti
        $alennusprosentti = $_item['discount_percent'];

        // Rivin alennusm‰‰r‰
        $alennusmaara = $_item['base_discount_amount'];

        // Jos alennusprosentti on 0, tarkistetaan viel‰ onko annettu eurom‰‰r‰ist‰ alennusta
        if ($alennusprosentti == 0 and $alennusmaara > 0) {
          // Lasketaan alennusm‰‰r‰ alennusprosentiksi
          $alennusprosentti = round(($alennusmaara * 100 / ($verollinen_hinta * $kpl)), 6);
        }

        // Verokanta
        $alvprosentti = $_item['tax_percent'];

        // Verollinen rivihinta
        $rivihinta_verollinen = round(($verollinen_hinta * $kpl) * (1 - $alennusprosentti / 100), 6);

        // Veroton rivihinta
        $rivihinta_veroton = round(($veroton_hinta * $kpl) * (1 - $alennusprosentti / 100), 6);

        $edi_order .= "*RS OSTOTILRIV $i\n";
        $edi_order .= "OSTOTILRIV.OTR_NRO:".$order['increment_id']."\n";
        $edi_order .= "OSTOTILRIV.OTR_RIVINRO:$i\n";
        $edi_order .= "OSTOTILRIV.OTR_TOIMITTAJANRO:\n";
        $edi_order .= "OSTOTILRIV.OTR_TUOTEKOODI:$tuoteno\n";
        $edi_order .= "OSTOTILRIV.OTR_NIMI:$nimitys\n";

        $edi_order .= "OSTOTILRIV.OTR_TILATTUMAARA:$kpl\n";

        // Verottomat hinnat
        $edi_order .= "OSTOTILRIV.OTR_VEROKANTA:$alvprosentti\n";
        $edi_order .= "OSTOTILRIV.OTR_RIVISUMMA:$rivihinta_veroton\n";
        $edi_order .= "OSTOTILRIV.OTR_OSTOHINTA:$veroton_hinta\n";
        $edi_order .= "OSTOTILRIV.OTR_ALENNUS:$alennusprosentti\n";

        $edi_order .= "OSTOTILRIV.OTR_VIITE:\n";
        $edi_order .= "OSTOTILRIV.OTR_OSATOIMITUSKIELTO:\n";
        $edi_order .= "OSTOTILRIV.OTR_JALKITOIMITUSKIELTO:\n";
        $edi_order .= "OSTOTILRIV.OTR_YKSIKKO:\n";
        $edi_order .= "OSTOTILRIV.OTR_SALLITAANJT:0\n";
        $edi_order .= "*RE  OSTOTILRIV $i\n";
        $i++;
      }
    }


    // // Rahtikulu, veroton
    if ($tyyppi == "magento") {
      $rahti_veroton = $order['shipping_amount'];

      if ($rahti_veroton != 0) {
        // Rahtikulu, verollinen
        $rahti = $order['shipping_amount'] + $order['shipping_tax_amount'];

        // Rahtin alviprossa
        $rahti_alvpros = round((($rahti / $rahti_veroton) - 1) * 100);

        $edi_order .= "*RS OSTOTILRIV $i\n";
        $edi_order .= "OSTOTILRIV.OTR_NRO:" . $order['increment_id'] . "\n";
        $edi_order .= "OSTOTILRIV.OTR_RIVINRO:$i\n";
        $edi_order .= "OSTOTILRIV.OTR_TOIMITTAJANRO:\n";
        $edi_order .= "OSTOTILRIV.OTR_TUOTEKOODI:$rahtikulu_tuoteno\n";
        $edi_order .= "OSTOTILRIV.OTR_NIMI:$rahtikulu_nimitys\n";
        $edi_order .= "OSTOTILRIV.OTR_TILATTUMAARA:1\n";
        $edi_order .= "OSTOTILRIV.OTR_RIVISUMMA:$rahti_veroton\n";
        $edi_order .= "OSTOTILRIV.OTR_OSTOHINTA:$rahti_veroton\n";
        $edi_order .= "OSTOTILRIV.OTR_ALENNUS:0\n";
        $edi_order .= "OSTOTILRIV.OTR_VEROKANTA:$rahti_alvpros\n";
        $edi_order .= "OSTOTILRIV.OTR_VIITE:\n";
        $edi_order .= "OSTOTILRIV.OTR_OSATOIMITUSKIELTO:\n";
        $edi_order .= "OSTOTILRIV.OTR_JALKITOIMITUSKIELTO:\n";
        $edi_order .= "OSTOTILRIV.OTR_YKSIKKO:\n";
        $edi_order .= "*RE  OSTOTILRIV $i\n";
      }
    }

    $edi_order .= "*ME\n";
    $edi_order .= "*IE";

    if (!PUPE_UNICODE) {
      $edi_order = iconv("UTF-8", "ISO-8859-1//TRANSLIT", $edi_order);
    }

    if ($tyyppi == "finvoice") {
      $filename =
        # @todo Vaihda pathi oikeaksi
        "/tmp/finvoice-orders/finvoice-order-{$order['increment_id']}-" . date("Ymd") . "-" .
        md5(uniqid(rand(), true)) . ".txt";
    }
    else {
      $filename =
        $magento_api_ht_edi . "/magento-order-{$order['increment_id']}-" . date("Ymd") . "-" .
        md5(uniqid(rand(), true)) . ".txt";
    }

    file_put_contents($filename, $edi_order);

    return true;
  }
}
