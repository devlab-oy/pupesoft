<?php

class Edi {

  /**
   * Luo edi tilauksen
   *
   * @param array   $order   Tilauksen tiedot ja tilauserivit
   * @param array   $options Tarvittavat parametrit
   * @return string           Luodun tiedoston polku
   */


  static function create($order, $options) {
    $magento_api_ht_edi            = $options['edi_polku'];
    $ovt_tunnus                    = $options['ovt_tunnus'];
    $pupesoft_tilaustyyppi         = $options['tilaustyyppi'];
    $magento_maksuehto_ohjaus      = $options['maksuehto_ohjaus'];
    $verkkokauppa_asiakasnro       = $options['asiakasnro'];
    $rahtikulu_tuoteno             = $options['rahtikulu_tuoteno'];
    $rahtikulu_nimitys             = $options['rahtikulu_nimitys'];
    $verkkokauppa_erikoiskasittely = $options['erikoiskasittely'];

    // Oletuksena "magento"
    $tyyppi = empty($options['tyyppi']) ? "magento" : $options['tyyppi'];

    if ($tyyppi == "magento") {
      if (empty($magento_api_ht_edi) or empty($ovt_tunnus) or empty($pupesoft_tilaustyyppi)) {
        die("Parametrej‰ puuttuu\n");
      }

      if (empty($verkkokauppa_asiakasnro) or empty($rahtikulu_tuoteno) or empty($rahtikulu_nimitys)) {
        die("Parametrej‰ puuttuu\n");
      }

      if (!is_writable($magento_api_ht_edi)) {
        die("EDI -hakemistoon ei voida kirjoittaa\n");
      }

      $viitteenne    = $storenimi;
      $yhteyshenkilo = "{$order["billing_address"]["lastname"]} " .
                       "{$order['billing_address']['firstname']}";
    }
    else {
      $viitteenne    = $order["laskun_numero"];
      $yhteyshenkilo = $order["tilausyhteyshenkilo"];
    }

    // Tilauksella k‰ytetyt lahjakortit ei saa vehent‰‰ myynti pupen puolella
    $giftcards = empty($order['webtex_giftcard']) ? null : json_decode($order['webtex_giftcard']);

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

    $vaihtoehtoinen_ovt = '';
    $vaihtoehtoinen_asiakasnro = '';

    //Tarkistetaan onko t‰m‰n nimiselle verkkokaupalle asetettu erikoisk‰sittelyj‰
    if (isset($verkkokauppa_erikoiskasittely) and count($verkkokauppa_erikoiskasittely) > 0) {
      $edi_store = str_replace("\n", " ", $order['store_name']);

      foreach ($verkkokauppa_erikoiskasittely as $verkkokauppaparametrit) {
        // Avaimet
        // 0 = Verkkokaupan nimi
        // 1 = Editilaus_tilaustyyppi
        // 2 = Tilaustyyppilisa
        // 3 = Myyjanumero
        // 4 = Vaihtoehtoinen ovttunnus OSTOTIL.OT_TOIMITTAJANRO -kentt‰‰n EDI tiedostossa
        // 5 = Rahtivapaus, jos 'E', niin k‰ytet‰‰n asiakkaan 'rahtivapaa' -oletusta
        // 6 = Tyhjennet‰‰nkˆ OSTOTIL.OT_MAKSETTU EDI tiedostossa (tyhj‰ ei, kaikki muut arvot kyll‰)
        // 7 = Vaihtoehtoinen asiakasnro

        if (strpos($edi_store, $verkkokauppaparametrit[0]) !== false) {
          $vaihtoehtoinen_ovt = $verkkokauppaparametrit[4];
        }

        if (strpos($edi_store, $verkkokauppaparametrit[0]) !== false and !empty($verkkokauppaparametrit[7])) {
          $vaihtoehtoinen_asiakasnro = $verkkokauppaparametrit[7];
        }
      }
    }

    $valittu_ovt_tunnus = (!empty($vaihtoehtoinen_ovt)) ? $vaihtoehtoinen_ovt : $ovt_tunnus;
    $verkkokauppa_asiakasnro = (!empty($vaihtoehtoinen_asiakasnro)) ? $vaihtoehtoinen_asiakasnro : $verkkokauppa_asiakasnro;

    $maksuehto = strip_tags($order['payment']['method']);

    // Jos on asetettu maksuehtojen ohjaus, tarkistetaan korvataanko Magentosta tullut maksuehto
    if (isset($magento_maksuehto_ohjaus) and count($magento_maksuehto_ohjaus) > 0) {
      foreach ($magento_maksuehto_ohjaus as $key => $array) {
        if (in_array($maksuehto, $array) and !empty($key)) {
          $maksuehto = $key;
        }
      }
    }

    $store_name = str_replace("\n", " ", $order['store_name']);
    $billingadress = str_replace("\n", ", ", $order['billing_address']['street']);
    $shippingadress = str_replace("\n", ", ", $order['shipping_address']['street']);

    // Yritystilauksissa vaihdetaan yrityksen ja tilaajan nimi toisin p‰in
    if (!empty($order['billing_address']['company'])) {
      $billing_company = $order['billing_address']['lastname']." ".$order['billing_address']['firstname'];
      $billing_contact = $order['billing_address']['company'];

      $shipping_company = $order['shipping_address']['lastname']." ".$order['shipping_address']['firstname'];
      $shipping_contact = $order['shipping_address']['company'];
    }
    else {
      $billing_company = $order['billing_address']['company'];
      $billing_contact = $order['billing_address']['lastname']." ".$order['billing_address']['firstname'];

      $shipping_company = $order['shipping_address']['company'];
      $shipping_contact = $order['shipping_address']['lastname']." ".$order['shipping_address']['firstname'];
    }

    $noutopistetunnus = '';
    $tunnisteosa = 'matkahuoltoNearbyParcel_';

    // Jos shipping_method sis‰lt‰‰ tunnisteosan ja sen per‰ss‰ on numero niin otetaan talteen
    if (!empty($order['shipping_method']) and strpos($order['shipping_method'], $tunnisteosa) !== false) {
      $tunnistekoodi = str_replace($tunnisteosa, '', $order['shipping_method']);
      $noutopistetunnus = is_numeric($tunnistekoodi) ? $tunnistekoodi : '';
    }

    // Noutopiste voi olla myˆs katuosoitteen lopussa esim "Testitie 1 [#12345]"
    preg_match("/\[#([0-9]*)\]/", $shippingadress, $tunnistekoodi);
    if ($noutopistetunnus == '' and !empty($tunnistekoodi[1])) {
      $noutopistetunnus = $tunnistekoodi[1];
      $shippingadress = str_replace($tunnistekoodi[0], "", $shippingadress);
    }

    $tilausviite = '';
    $tilausnumero = '';
    $kohde = '';
    $toimaika = '';

    if (!empty($order['reference_number'])) {
      $tilausviite = str_replace("\n", " ", $order['reference_number']);
    }

    if (!empty($order['order_number'])) {
      $tilausnumero = str_replace("\n", " ", $order['order_number']);
    }

    if (!empty($order['target'])) {
      $kohde = str_replace("\n", " ", $order['target']);
    }

    if (!empty($order['delivery_time'])) {
      $toimaika = str_replace("\n", " ", $order['delivery_time']);
    }
    else {
      $toimaika = date("Y-m-d");
    }

    // tilauksen otsikko
    $edi_order  = "*IS from:721111720-1 to:IKH,ORDERS*id:{$order['increment_id']} version:AFP-1.0 *MS\n";
    $edi_order .= "*MS {$order['increment_id']}\n";
    $edi_order .= "*RS OSTOTIL\n";
    $edi_order .= "OSTOTIL.OT_NRO:{$order['increment_id']}\n";
    $edi_order .= "OSTOTIL.OT_TOIMITTAJANRO:{$valittu_ovt_tunnus}\n";
    $edi_order .= "OSTOTIL.OT_TILAUSTYYPPI:{$pupesoft_tilaustyyppi}\n";
    $edi_order .= "OSTOTIL.VERKKOKAUPPA:{$store_name}\n";
    $edi_order .= "OSTOTIL.OT_VERKKOKAUPPA_ASIAKASNRO:{$order['customer_id']}\n"; //t‰m‰ tulee suoraan Magentosta
    $edi_order .= "OSTOTIL.OT_VERKKOKAUPPA_TILAUSVIITE:{$tilausviite}\n";
    $edi_order .= "OSTOTIL.OT_VERKKOKAUPPA_TILAUSNUMERO:{$tilausnumero}\n";
    $edi_order .= "OSTOTIL.OT_VERKKOKAUPPA_KOHDE:{$kohde}\n";
    $edi_order .= "OSTOTIL.OT_TILAUSAIKA:{$toimaika}\n";
    $edi_order .= "OSTOTIL.OT_KASITTELIJA:\n";
    $edi_order .= "OSTOTIL.OT_TOIMITUSAIKA:{$toimaika}\n";
    $edi_order .= "OSTOTIL.OT_TOIMITUSTAPA:{$order['shipping_description']}\n";
    $edi_order .= "OSTOTIL.OT_TOIMITUSEHTO:\n";
    $edi_order .= "OSTOTIL.OT_MAKSETTU:{$order['status']}\n";
    $edi_order .= "OSTOTIL.OT_MAKSUEHTO:{$maksuehto}\n";
    $edi_order .= "OSTOTIL.OT_VIITTEEMME:\n";
    $edi_order .= "OSTOTIL.OT_VIITTEENNE:{$viitteenne}\n";
    $edi_order .= "OSTOTIL.OT_TILAUSVIESTI:{$order['customer_note']}\n";
    $edi_order .= "OSTOTIL.OT_VEROMAARA:{$order['tax_amount']}\n";
    $edi_order .= "OSTOTIL.OT_SUMMA:{$grand_total}\n";
    $edi_order .= "OSTOTIL.OT_VALUUTTAKOODI:{$order['order_currency_code']}\n";
    $edi_order .= "OSTOTIL.OT_KLAUSUULI1:\n";
    $edi_order .= "OSTOTIL.OT_KLAUSUULI2:\n";
    $edi_order .= "OSTOTIL.OT_KULJETUSOHJE:\n";
    $edi_order .= "OSTOTIL.OT_LAHETYSTAPA:\n";
    $edi_order .= "OSTOTIL.OT_VAHVISTUS_FAKSILLA:\n";
    $edi_order .= "OSTOTIL.OT_FAKSI:\n";
    $edi_order .= "OSTOTIL.OT_ASIAKASNRO:{$verkkokauppa_asiakasnro}\n";
    $edi_order .= "OSTOTIL.OT_YRITYS:{$billing_company}\n";
    $edi_order .= "OSTOTIL.OT_YHTEYSHENKILO:{$billing_contact}\n";
    $edi_order .= "OSTOTIL.OT_KATUOSOITE:".$billingadress."\n";
    $edi_order .= "OSTOTIL.OT_POSTITOIMIPAIKKA:{$order['billing_address']['city']}\n";
    $edi_order .= "OSTOTIL.OT_POSTINRO:{$order['billing_address']['postcode']}\n";
    $edi_order .= "OSTOTIL.OT_YHTEYSHENKILONPUH:{$order['billing_address']['telephone']}\n";
    $edi_order .= "OSTOTIL.OT_YHTEYSHENKILONFAX:{$order['billing_address']['fax']}\n";
    $edi_order .= "OSTOTIL.OT_MYYNTI_YRITYS:\n";
    $edi_order .= "OSTOTIL.OT_MYYNTI_KATUOSOITE:\n";
    $edi_order .= "OSTOTIL.OT_MYYNTI_POSTITOIMIPAIKKA:\n";
    $edi_order .= "OSTOTIL.OT_MYYNTI_POSTINRO:\n";
    $edi_order .= "OSTOTIL.OT_MYYNTI_MAAKOODI:\n";
    $edi_order .= "OSTOTIL.OT_MYYNTI_YHTEYSHENKILO:\n";
    $edi_order .= "OSTOTIL.OT_MYYNTI_YHTEYSHENKILONPUH:\n";
    $edi_order .= "OSTOTIL.OT_MYYNTI_YHTEYSHENKILONFAX:\n";
    $edi_order .= "OSTOTIL.OT_TOIMITUS_YRITYS:{$shipping_company}\n";
    $edi_order .= "OSTOTIL.OT_TOIMITUS_NIMI:{$shipping_contact}\n";
    $edi_order .= "OSTOTIL.OT_TOIMITUS_KATUOSOITE:".$shippingadress."\n";
    $edi_order .= "OSTOTIL.OT_TOIMITUS_POSTITOIMIPAIKKA:{$order['shipping_address']['city']}\n";
    $edi_order .= "OSTOTIL.OT_TOIMITUS_POSTINRO:{$order['shipping_address']['postcode']}\n";
    $edi_order .= "OSTOTIL.OT_TOIMITUS_MAAKOODI:{$order['shipping_address']['country_id']}\n";
    $edi_order .= "OSTOTIL.OT_TOIMITUS_PUH:{$order['shipping_address']['telephone']}\n";
    $edi_order .= "OSTOTIL.OT_TOIMITUS_EMAIL:{$order['customer_email']}\n";
    $edi_order .= "OSTOTIL.OT_TOIMITUS_NOUTOPISTE_TUNNUS:{$noutopistetunnus}\n";
    $edi_order .= "*RE OSTOTIL\n";

    $i = 1;

    foreach ($order['items'] as $item) {
      $product_id = $item['product_id'];

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
        // Lahjakorttia ja eurom‰‰r‰ist‰ alennusta ei voi k‰ytt‰‰ samalla tilauksella, Magentossa estetty
        if ($alennusprosentti == 0 and $alennusmaara > 0 and $giftcard_sum == 0) {
          // Lasketaan alennusm‰‰r‰ alennusprosentiksi
          $alennusprosentti = round(($alennusmaara * 100 / ($verollinen_hinta * $kpl)), 6);
        }

        // Verokanta
        $alvprosentti = $_item['tax_percent'];

        // Verollinen rivihinta
        $rivihinta_verollinen = round(($verollinen_hinta * $kpl) * (1 - $alennusprosentti / 100), 6);

        // Veroton rivihinta
        $rivihinta_veroton = round(($veroton_hinta * $kpl) * (1 - $alennusprosentti / 100), 6);

        // Rivin tiedot
        $edi_order .= "*RS OSTOTILRIV {$i}\n";
        $edi_order .= "OSTOTILRIV.OTR_NRO:{$order['increment_id']}\n";
        $edi_order .= "OSTOTILRIV.OTR_RIVINRO:{$i}\n";
        $edi_order .= "OSTOTILRIV.OTR_TOIMITTAJANRO:\n";
        $edi_order .= "OSTOTILRIV.OTR_TUOTEKOODI:{$tuoteno}\n";
        $edi_order .= "OSTOTILRIV.OTR_NIMI:{$nimitys}\n";
        $edi_order .= "OSTOTILRIV.OTR_TILATTUMAARA:{$kpl}\n";
        $edi_order .= "OSTOTILRIV.OTR_VEROKANTA:{$alvprosentti}\n";
        $edi_order .= "OSTOTILRIV.OTR_RIVISUMMA:{$rivihinta_veroton}\n";
        $edi_order .= "OSTOTILRIV.OTR_OSTOHINTA:{$veroton_hinta}\n";
        $edi_order .= "OSTOTILRIV.OTR_ALENNUS:{$alennusprosentti}\n";
        $edi_order .= "OSTOTILRIV.OTR_VIITE:\n";
        $edi_order .= "OSTOTILRIV.OTR_OSATOIMITUSKIELTO:\n";
        $edi_order .= "OSTOTILRIV.OTR_JALKITOIMITUSKIELTO:\n";
        $edi_order .= "OSTOTILRIV.OTR_YKSIKKO:\n";
        $edi_order .= "OSTOTILRIV.OTR_SALLITAANJT:0\n";
        $edi_order .= "*RE  OSTOTILRIV {$i}\n";

        $i++;
      }
    }

    // Rahtikulu, veroton
    $rahti_veroton = $order['shipping_amount'];

    if ($rahti_veroton != 0) {
      // Rahtikulu, verollinen
      $rahti = $order['shipping_amount'] + $order['shipping_tax_amount'];

      // Rahtin alviprossa
      $rahti_alvpros = round((($rahti / $order['shipping_amount']) - 1) * 100);

      if (!empty($order['shipping_description_line'])) {
        $rahtikulu_nimitys .= " / {$order['shipping_description_line']}";
      }

      $edi_order .= "*RS OSTOTILRIV {$i}\n";
      $edi_order .= "OSTOTILRIV.OTR_NRO:{$order['increment_id']}\n";
      $edi_order .= "OSTOTILRIV.OTR_RIVINRO:{$i}\n";
      $edi_order .= "OSTOTILRIV.OTR_TOIMITTAJANRO:\n";
      $edi_order .= "OSTOTILRIV.OTR_TUOTEKOODI:{$rahtikulu_tuoteno}\n";
      $edi_order .= "OSTOTILRIV.OTR_NIMI:{$rahtikulu_nimitys}\n";
      $edi_order .= "OSTOTILRIV.OTR_TILATTUMAARA:1\n";
      $edi_order .= "OSTOTILRIV.OTR_RIVISUMMA:{$order['shipping_amount']}\n";
      $edi_order .= "OSTOTILRIV.OTR_OSTOHINTA:{$order['shipping_amount']}\n";
      $edi_order .= "OSTOTILRIV.OTR_ALENNUS:0\n";
      $edi_order .= "OSTOTILRIV.OTR_VEROKANTA:{$rahti_alvpros}\n";
      $edi_order .= "OSTOTILRIV.OTR_VIITE:{$rahtikulu_nimitys}\n";
      $edi_order .= "OSTOTILRIV.OTR_OSATOIMITUSKIELTO:\n";
      $edi_order .= "OSTOTILRIV.OTR_JALKITOIMITUSKIELTO:\n";
      $edi_order .= "OSTOTILRIV.OTR_YKSIKKO:\n";
      $edi_order .= "*RE  OSTOTILRIV {$i}\n";
    }

    $edi_order .= "*ME\n";
    $edi_order .= "*IE\n";

    if (!PUPE_UNICODE) {
      $edi_order = iconv("UTF-8", "ISO-8859-1//TRANSLIT", $edi_order);
    }

    if ($tyyppi == "finvoice") {
      $polku    = "{$GLOBALS["pupe_root_polku"]}/datain/finvoice-orders";
      $filenimi = "finvoice-order-{$order['increment_id']}-".date("Ymd")."-".md5(uniqid(rand(), true));
      $filename = "{$polku}/{$filenimi}";
    }
    else {
      $name_prefix = "magento-order-{$order['increment_id']}-".date("Ymd")."-";
      $file_dir    = $magento_api_ht_edi;
      $filename    = tempnam($file_dir, $name_prefix);
      unlink($filename);
    }

    file_put_contents("{$filename}.txt", $edi_order);

    return "{$filename}.txt";
  }
}
