<?php

class EdiPresta {

  /**
   * Creates file in EDI-format from sales order
   *
   * @param string $sales_order
   * @return string
   */
  public static function create($sales_order, $filepath_base) {
    if (empty($sales_order)) {
      return '';
    }

    $edi_msg = "*IS from:721111720-1 to:IKH,ORDERS*id:{$sales_order['id']} version:AFP-1.0 *MS\n";
    $edi_msg .= "*MS {$sales_order['id']}\n";
    $edi_msg .= "*RS OSTOTIL\n";
    $edi_msg .= "OSTOTIL.OT_NRO:{$sales_order['id']}\n";
    $edi_msg .= "OSTOTIL.OT_TOIMITTAJANRO:0\n";
    $edi_msg .= "OSTOTIL.OT_TILAUSTYYPPI:{$sales_order['tilaustyyppi']}";
    $edi_msg .= "OSTOTIL.VERKKOKAUPPA:\n";
    $edi_msg .= "OSTOTIL.OT_VERKKOKAUPPA_ASIAKASNRO:{$sales_order['liitostunnus']}\n";
    $edi_msg .= "OSTOTIL.OT_VERKKOKAUPPA_TILAUSVIITE:{$sales_order['viite']}\n";
    $edi_msg .= "OSTOTIL.OT_VERKKOKAUPPA_TILAUSNUMERO:\n";
    $edi_msg .= "OSTOTIL.OT_VERKKOKAUPPA_KOHDE:\n";
    $edi_msg .= "OSTOTIL.OT_TILAUSAIKA:\n";
    $edi_msg .= "OSTOTIL.OT_KASITTELIJA:\n";
    $edi_msg .= "OSTOTIL.OT_TOIMITUSAIKA:\n";
    $edi_msg .= "OSTOTIL.OT_TOIMITUSTAPA:{$sales_order['toimitustapa']}\n";
    $edi_msg .= "OSTOTIL.OT_TOIMITUSEHTO:\n";
    $edi_msg .= "OSTOTIL.OT_MAKSETTU:{$sales_order['maksettu']}\n";
    $edi_msg .= "OSTOTIL.OT_MAKSUEHTO:{$sales_order['maksuehto']}\n";
    $edi_msg .= "OSTOTIL.OT_VIITTEEMME:\n";
    $edi_msg .= "OSTOTIL.OT_VIITTEENNE:\n";
    $edi_msg .= "OSTOTIL.OT_VEROMAARA:{$sales_order['alv_maara']}\n";
    $edi_msg .= "OSTOTIL.OT_SUMMA:{$sales_order['summa']}\n";
    $edi_msg .= "OSTOTIL.OT_VALUUTTAKOODI:{$sales_order['valkoodi']}\n";
    $edi_msg .= "OSTOTIL.OT_KLAUSUULI1:\n";
    $edi_msg .= "OSTOTIL.OT_KLAUSUULI2:\n";
    $edi_msg .= "OSTOTIL.OT_KULJETUSOHJE:\n";
    $edi_msg .= "OSTOTIL.OT_LAHETYSTAPA:\n";
    $edi_msg .= "OSTOTIL.OT_VAHVISTUS_FAKSILLA:\n";
    $edi_msg .= "OSTOTIL.OT_FAKSI:\n";
    $edi_msg .= "OSTOTIL.OT_ASIAKASNRO:\n";
    $edi_msg .= "OSTOTIL.OT_YRITYS:\n";
    $edi_msg .= "OSTOTIL.OT_YHTEYSHENKILO:{$sales_order['laskutus_nimi']}\n";
    $edi_msg .= "OSTOTIL.OT_KATUOSOITE:{$sales_order['laskutus_osoite']}\n";
    $edi_msg .= "OSTOTIL.OT_POSTITOIMIPAIKKA:{$sales_order['laskutus_postitp']}\n";
    $edi_msg .= "OSTOTIL.OT_POSTINRO:{$sales_order['laskutus_postino']}\n";
    $edi_msg .= "OSTOTIL.OT_YHTEYSHENKILONPUH:{$sales_order['puhelin']}\n";
    $edi_msg .= "OSTOTIL.OT_YHTEYSHENKILONFAX:{$sales_order['fax']}\n";
    $edi_msg .= "OSTOTIL.OT_MYYNTI_YRITYS:\n";
    $edi_msg .= "OSTOTIL.OT_MYYNTI_KATUOSOITE:\n";
    $edi_msg .= "OSTOTIL.OT_MYYNTI_POSTITOIMIPAIKKA:\n";
    $edi_msg .= "OSTOTIL.OT_MYYNTI_POSTINRO:\n";
    $edi_msg .= "OSTOTIL.OT_MYYNTI_MAAKOODI:\n";
    $edi_msg .= "OSTOTIL.OT_MYYNTI_YHTEYSHENKILO:\n";
    $edi_msg .= "OSTOTIL.OT_MYYNTI_YHTEYSHENKILONPUH:\n";
    $edi_msg .= "OSTOTIL.OT_MYYNTI_YHTEYSHENKILONFAX:\n";
    $edi_msg .= "OSTOTIL.OT_TOIMITUS_YRITYS:\n";
    $edi_msg .= "OSTOTIL.OT_TOIMITUS_NIMI:{$sales_order['toimitus_nimi']}\n";
    $edi_msg .= "OSTOTIL.OT_TOIMITUS_KATUOSOITE:{$sales_order['toimitus_osoite']}\n";
    $edi_msg .= "OSTOTIL.OT_TOIMITUS_POSTITOIMIPAIKKA:{$sales_order['toimitus_postitp']}\n";
    $edi_msg .= "OSTOTIL.OT_TOIMITUS_POSTINRO:{$sales_order['toimitus_postino']}\n";
    $edi_msg .= "OSTOTIL.OT_TOIMITUS_MAAKOODI:{$sales_order['toimitus_maa']}\n";
    $edi_msg .= "OSTOTIL.OT_TOIMITUS_PUH:{$sales_order['puhelin']}\n";
    $edi_msg .= "OSTOTIL.OT_TOIMITUS_EMAIL:{$sales_order['email']}\n";
    $edi_msg .= "*RE OSTOTIL\n";

    $row_number = 1;
    foreach ($sales_order['tilausrivit'] as $tilausrivi) {
      $tilausrivi['row_number'] = $row_number++;
      $tilausrivi['sales_order_id'] = $sales_order['id'];
      self::add_row($tilausrivi, $edi_msg);
    }

    //Jos tilauksella on rahtikulu niin lisätään tilaukselle rahtikulurivi
    $rahtirivi = array(
        'row_number'     => $row_number,
        'sales_order_id' => $sales_order['id'],
    );
    if ($sales_order['rahti_veroton'] != 0) {
      self::add_row($rahtirivi, $edi_msg);
    }

    $edi_msg .= "*ME\n";
    $edi_msg .= "*IE";

    $edi_msg = iconv("UTF-8", "ISO-8859-1//TRANSLIT", $edi_msg);

    $filepath = self::write_to_file($edi_msg, $filepath_base);

    return $filepath;
  }

  /**
   *
   * @param array $tilausrivi
   * @param string $edi_msg
   */
  private static function add_row($tilausrivi, &$edi_msg) {
    $kpl = $tilausrivi['tilkpl'];
    $alennusprosentti = $tilausrivi['alennusprosentti'];
    $veroton_hinta = $tilausrivi['veroton_yksikkohinta'];
    $rivihinta_veroton = round(($veroton_hinta * $kpl) * (1 - $alennusprosentti / 100), 2);

    $edi_msg .= "*RS OSTOTILRIV {$tilausrivi['row_number']}\n";
    $edi_msg .= "OSTOTILRIV.OTR_NRO:{$tilausrivi['sales_order_id']}\n";
    $edi_msg .= "OSTOTILRIV.OTR_RIVINRO:{$tilausrivi['row_number']}\n";
    $edi_msg .= "OSTOTILRIV.OTR_TOIMITTAJANRO:\n";
    $edi_msg .= "OSTOTILRIV.OTR_TUOTEKOODI:{$tilausrivi['tuoteno']}";
    $edi_msg .= "OSTOTILRIV.OTR_NIMI:{$tilausrivi['nimitys']}\n";
    $edi_msg .= "OSTOTILRIV.OTR_TILATTUMAARA:{$kpl}\n";
    $edi_msg .= "OSTOTILRIV.OTR_RIVISUMMA:$rivihinta_veroton\n";
    $edi_msg .= "OSTOTILRIV.OTR_OSTOHINTA:{$tilausrivi['veroton_yksikkohinta']}";
    $edi_msg .= "OSTOTILRIV.OTR_ALENNUS:{$tilausrivi['alennusprosentti']}\n";
    $edi_msg .= "OSTOTILRIV.OTR_VEROKANTA:{$tilausrivi['alv_prosentti']}\n";
    $edi_msg .= "OSTOTILRIV.OTR_VIITE:\n";
    $edi_msg .= "OSTOTILRIV.OTR_OSATOIMITUSKIELTO:\n";
    $edi_msg .= "OSTOTILRIV.OTR_JALKITOIMITUSKIELTO:\n";
    $edi_msg .= "OSTOTILRIV.OTR_YKSIKKO:\n";
    $edi_msg .= "OSTOTILRIV.OTR_SALLITAANJT:0\n";
    $edi_msg .= "*RE  OSTOTILRIV {$tilausrivi['row_number']}\n";
  }

  /**
   *
   * @param string $edi_msg
   * @param string $filepath
   */
  private static function write_to_file($edi_msg, $filepath) {
    $rnd = md5(uniqid(rand(), true));
    $filepath = $filepath . "/presta-order-" . date("Ymd") . "-{$rnd}.txt";
    file_put_contents($filepath, $edi_msg);

    return $filepath;
  }
}
