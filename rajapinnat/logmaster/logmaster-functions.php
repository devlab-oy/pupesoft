<?php

if (!function_exists('logmaster_send_email')) {
  function logmaster_send_email($params) {
    $body        = "";
    $email       = $params['email'];
    $email_array = $params['email_array'];
    $log_name    = $params['log_name'];
    $subject     = "";

    if (empty($email) or !is_array($email_array) or count($email_array) == 0) {
      return;
    }

    switch ($log_name) {
    case 'logmaster_outbound_delivery_confirmation':
      $subject = t("Logmaster: Keräyksen kuittaus");
      break;

    case 'logmaster_inbound_delivery_confirmation':
      $subject = t("Logmaster: Saapumisen kuittaus");
      break;

    case 'logmaster_stock_report':
      $subject = t("Logmaster saldovertailu");
      break;

    default:
      return;
    }

    foreach ($email_array as $msg) {
      $body .= $msg."<br>\n";
    }

    $args = array(
      'body' => $body,
      'cc' => '',
      'ctype' => 'html',
      'subject' => $subject,
      'to' => $email,
    );

    if (pupesoft_sahkoposti($args)) {
      pupesoft_log($log_name, "Sähköposti lähetetty onnistuneesti osoitteeseen {$email}");
    }
    else {
      pupesoft_log($log_name, "Sähköpostin lähetys epäonnistui osoitteeseen {$email}");
    }
  }
}

if (!function_exists('logmaster_send_file')) {
  function logmaster_send_file($filename) {
    global $kukarow, $yhtiorow, $logmaster;

    // Lähetetään aina UTF-8 muodossa
    $ftputf8 = true;

    # Ei haluta että tulostetaan mitään ruudulle
    $tulos_ulos = "foobar";

    $ftphost = $logmaster['host'];
    $ftpuser = $logmaster['user'];
    $ftppass = $logmaster['pass'];
    $ftppath = $logmaster['path'];

    $ftpfile = realpath($filename);

    require "inc/ftp-send.inc";

    return $palautus;
  }
}

if (!function_exists('logmaster_sent_timestamp')) {
  function logmaster_sent_timestamp($tunnus) {
    global $kukarow;

    $query = "UPDATE lasku SET
              lahetetty_ulkoiseen_varastoon = now()
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND (lahetetty_ulkoiseen_varastoon IS NULL or lahetetty_ulkoiseen_varastoon = 0)
              AND tunnus = '{$tunnus}'";
    $res = pupe_query($query);
  }
}

if (!function_exists('logmaster_mark_as_sent')) {
  function logmaster_mark_as_sent($tunnus) {
    global $kukarow, $yhtiorow;

    $tunnus = (int) $tunnus;

    $query = "SELECT *
              FROM lasku
              WHERE yhtio = '{$kukarow['yhtio']}' AND
              ((tila = 'N' AND alatila = 'A') OR
              (
                tila                = 'G' AND
                alatila             = 'J' AND
                tilaustyyppi       != 'M' AND
                toimitustavan_lahto = 0
              ))
              AND tunnus = '{$tunnus}'";
    $res = pupe_query($query);

    if (mysql_num_rows($res) == 0) {
      return false;
    }

    $laskurow = mysql_fetch_assoc($res);

    switch ($laskurow['tila']) {
    case 'N':
      # Tällä yhtiön parametrilla pystytään ohittamaan tulostus
      $yhtiorow["lahetteen_tulostustapa"] = "X";
      $laskuja = 1;
      $tilausnumeroita = $laskurow['tunnus'];
      $toim = "";

      ob_start();

      require "tilauskasittely/tilaus-valmis-tulostus.inc";

      $viestit = ob_get_contents();
      ob_end_clean();

      return $viestit;
    case 'G':
      $toim         = "SIIRTOLISTA";
      $tulostetaan  = "OK";
      # Tällä yhtiön parametrilla pystytään ohittamaan keräyslistan tulostus
      $yhtiorow['tulosta_valmistus_tulosteet'] = 'foobar';

      ob_start();

      require "tilauskasittely/tilaus-valmis-siirtolista.inc";

      $viestit = ob_get_contents();
      ob_end_clean();

      return $viestit;
    default:
      return false;
    }
  }
}

if (!function_exists('logmaster_field')) {
  function logmaster_field($column_name) {

    switch ($column_name) {
    case 'ItemNumber':
      $key = t_avainsana('POSTEN_TKOODI', '', " and avainsana.selite = 'ItemNumber' ", '', '', 'selitetark');

      if (empty($key)) {
        $key = 'tuoteno';
      }

      break;
    case 'ProdGroup1':
      $key = t_avainsana('POSTEN_TKOODI', '', " and avainsana.selite = 'ProdGroup1' ", '', '', 'selitetark');

      if (empty($key)) {
        $key = 'try';
      }

      break;
      case 'ProdGroup2':
        $key = t_avainsana('POSTEN_TKOODI', '', " and avainsana.selite = 'ProdGroup2' ", '', '', 'selitetark');

        if (empty($key)) {
          $key = '';
        }

        break;
    default:
      die(t('Annettu arvo ei ole käytössä'));
    }

    return mysql_real_escape_string($key);
  }
}

if (!function_exists('check_file_extension')) {
  function check_file_extension($file_name, $extension) {

    if (!is_file($file_name) or !is_readable($file_name)) {
      return false;
    }

    $path_parts = pathinfo($file_name);

    if (empty($path_parts['extension'])) {
      return false;
    }

    if (strtoupper($path_parts['extension']) == strtoupper($extension)) {
      return true;
    }

    return false;
  }
}

if (!function_exists('logmaster_message_type')) {
  function logmaster_message_type($file_name) {
    $extension = check_file_extension($file_name, 'XML');

    if ($extension === false) {
      return false;
    }

    $xml = simplexml_load_file($file_name);

    if (!is_object($xml)) {
      return false;
    }

    if (isset($xml->MessageHeader) and isset($xml->MessageHeader->MessageType)) {
      return trim($xml->MessageHeader->MessageType);
    }

    return false;
  }
}

if (!function_exists('logmaster_outbounddelivery')) {
  function logmaster_outbounddelivery($otunnus) {

    global $kukarow, $yhtiorow;

    $pupe_root_polku = dirname(dirname(dirname(__FILE__)));

    $query = "SELECT lasku.*,
              tilausrivi.*,
              lasku.varasto AS otsikon_varasto,
              tilausrivi.kpl + tilausrivi.varattu AS kpl,
              tilausrivi.tunnus AS tilausrivin_tunnus,
              tilausrivi.keratty,
              lasku.toimaika AS lasku_toimaika,
              asiakas.email,
              asiakas.asiakasnro,
              tuote.eankoodi,
              tuote.ei_saldoa,
              tuote.tullinimike1,
              laskun_lisatiedot.noutopisteen_tunnus
              FROM lasku
              LEFT JOIN asiakas ON (asiakas.yhtio = lasku.yhtio AND asiakas.tunnus = lasku.liitostunnus)
              JOIN tilausrivi ON (
                tilausrivi.yhtio    = lasku.yhtio AND
                tilausrivi.otunnus  = lasku.tunnus AND
                (tilausrivi.varattu + tilausrivi.kpl) > 0 AND
                tilausrivi.tyyppi   IN ('L', 'G') AND
                tilausrivi.var     != 'J'
              )
              JOIN tuote ON (
                tuote.yhtio         = tilausrivi.yhtio AND
                tuote.tuoteno       = tilausrivi.tuoteno
              )
              LEFT JOIN laskun_lisatiedot ON (
                laskun_lisatiedot.yhtio         = lasku.yhtio AND
                laskun_lisatiedot.otunnus       = lasku.tunnus
              )
              WHERE lasku.yhtio     = '{$kukarow['yhtio']}'
              AND lasku.tunnus      = '{$otunnus}'";
    $loopres = pupe_query($query);

    if (mysql_num_rows($loopres) == 0) {
      pupesoft_log('logmaster_outbound_delivery', "Yhtään riviä ei löytynyt tilaukselle {$otunnus}. Sanoman luonti epäonnistui.");

      return false;
    }

    $looprow = mysql_fetch_assoc($loopres);

    $varastorow = hae_varasto($looprow['otsikon_varasto']);

    switch ($varastorow['ulkoinen_jarjestelma']) {
    case 'L':
      $uj_nimi = "Velox";
      break;
    case 'P':
      $uj_nimi = "PostNord";
      break;
    default:
      pupesoft_log('logmaster_outbound_delivery', "Tilauksen {$otunnus} varaston ulkoinen järjestelmä oli virheellinen.");

      return false;
    }

    # Säädetaan muuttujia kuntoon
    $query = "SELECT tunnus
              FROM toimitustapa
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND selite  = '{$looprow['toimitustapa']}'";
    $toimitustapa_chk_res = pupe_query($query);
    $toimitustapa_chk_row = mysql_fetch_assoc($toimitustapa_chk_res);
    $toim_ta = $looprow['ohjelma_moduli'] == 'MAGENTO' ? t_avainsana('TOIM_TAPA_TA', '', '', '', '', 'selitetark') : null;
    $transport_account = $toim_ta ? $toim_ta : substr($toimitustapa_chk_row['tunnus'], 0, 10);
    $droppoint   = $looprow['noutopisteen_tunnus'];

    $h1time = explode("-", substr($looprow['h1time'], 0, 10));
    $pickinglistdate = "{$h1time[2]}-{$h1time[1]}-{$h1time[0]}";

    $lasku_toimaika = explode("-", $looprow['lasku_toimaika']);
    $deliverydate = "{$lasku_toimaika[2]}-{$lasku_toimaika[1]}-{$lasku_toimaika[0]}";

    $cust_name         = trim($looprow['nimi'].' '.$looprow['nimitark']);
    $rec_cust_name     = trim($looprow['toim_nimi'].' '.$looprow['toim_nimitark']);
    $rec_cust_street   = $looprow['toim_osoite'];
    $rec_cust_street2  = '';

    if (!empty($looprow['kohde'])) {
      $rec_cust_street2 = $looprow['kohde'];
    }

    # Rakennetaan XML
    $xml = simplexml_load_string("<?xml version='1.0' encoding='UTF-8'?><Message></Message>");

    $messageheader = $xml->addChild('MessageHeader');
    $messageheader->addChild('MessageType', 'OutboundDelivery');
    $messageheader->addChild('Sender',       xml_cleanstring($yhtiorow['nimi']));
    $messageheader->addChild('Receiver',     $uj_nimi);

    $custpickinglist = $xml->addChild('CustPickingList');
    $custpickinglist->addChild('SalesId',             substr($otunnus, 0, 20));
    $custpickinglist->addChild('PickingListId',       substr($otunnus, 0, 20));
    $custpickinglist->addChild('CustOrderNumber',     xml_cleanstring($looprow['asiakkaan_tilausnumero'], 20)); // Magentosta?
    $custpickinglist->addChild('CustReference',       xml_cleanstring($looprow['viesti'], 50));
    $custpickinglist->addChild('OrderCode',          'U');
    $custpickinglist->addChild('OrderType',          'SO');
    $custpickinglist->addChild('PickingListDate',    $pickinglistdate);
    $custpickinglist->addChild('DeliveryDate',       $deliverydate);
    $custpickinglist->addChild('UnloadingDate',      0);
    $custpickinglist->addChild('OrderDate',          0);
    $custpickinglist->addChild('DlvTerm',            xml_cleanstring($looprow['toimitusehto'], 3));
    $custpickinglist->addChild('PickingInstruction', xml_cleanstring($looprow['sisviesti2'], 128));
    $custpickinglist->addChild('SalesOffice',        0);
    $custpickinglist->addChild('SalesContact',       0);
    $custpickinglist->addChild('Warehouse',          0);
    $custpickinglist->addChild('InvoiceAmounth',     0);

    $orderedby = $custpickinglist->addChild('OrderedBy');
    $orderedby->addChild('CustAccount',  0);
    $orderedby->addChild('CustName',     xml_cleanstring($cust_name, 50));
    $orderedby->addChild('CustStreet',   xml_cleanstring($looprow['osoite'], 50));
    $orderedby->addChild('CustStreet2',  xml_cleanstring($looprow['osoite'], 50));
    $orderedby->addChild('CustPostCode', xml_cleanstring($looprow['postino'], 10));
    $orderedby->addChild('CustCity',     xml_cleanstring($looprow['postitp'], 30));
    $orderedby->addChild('CustCountry',  xml_cleanstring($looprow['maa'], 10));
    $orderedby->addChild('Email',        xml_cleanstring($looprow['email']));

    if ($uj_nimi == "Velox") {
      $orderedby->addChild('Custnr',        xml_cleanstring($looprow['asiakasnro']));
      $orderedby->addChild('PaymentTerm',   xml_cleanstring($looprow['maksuehto']));
      $orderedby->addChild('Seller',        xml_cleanstring($looprow['myyja']));
    }

    $receiver = $custpickinglist->addChild('Receiver');
    $receiver->addChild('RecCustAccount',  0);
    $receiver->addChild('RecCustName',     xml_cleanstring($rec_cust_name, 50));
    $receiver->addChild('RecCustStreet',   xml_cleanstring($rec_cust_street, 50));
    $receiver->addChild('RecCustStreet2',  xml_cleanstring($rec_cust_street2, 50));
    $receiver->addChild('RecCustPostCode', xml_cleanstring($looprow['toim_postino'], 10));
    $receiver->addChild('RecCustCity',     xml_cleanstring($looprow['toim_postitp'], 30));
    $receiver->addChild('RecCustCountry',  xml_cleanstring($looprow['toim_maa'], 10));
    $receiver->addChild('RecCustPhone',    xml_cleanstring($looprow['toim_puh'], 20));
    if ($looprow['toim_maa'] == 'FI' or $looprow['toim_maa'] == '') {
      $rec_lang = 'FI';
    }
      else {
      $rec_lang = 'EN';
    }
    $receiver->addChild('RecCustLanguage',    xml_cleanstring($rec_lang, 2));

    $transport = $custpickinglist->addChild('Transport');
    $transport->addChild('TransportAccount',      $transport_account);
    $transport->addChild('TransportInstruction',  substr('', 0, 250));
    $transport->addChild('FreightPayer',          'sender');
    $transport->addChild('DropPoint',      $droppoint);

    $lines = $custpickinglist->addChild('Lines');

    mysql_data_seek($loopres, 0);

    $_line_i = 1;
    $logmaster_itemnumberfield = logmaster_field('ItemNumber');

    while ($looprow = mysql_fetch_assoc($loopres)) {
      // Laitetaan kappalemäärät kuntoon
      $looprow['kpl'] = $looprow['var'] == 'J' ? 0 : $looprow['kpl'];

      if ($uj_nimi == 'PostNord' and $looprow['ei_saldoa'] == 'o') continue;

      $line = $lines->addChild('Line');
      $line->addAttribute('No', $_line_i);
      $line->addChild('TransId',           xml_cleanstring($looprow['tilausrivin_tunnus'], 20));

      if ($uj_nimi == "Velox") {
        $line->addChild('ItemNumber',        xml_cleanstring($looprow[$logmaster_itemnumberfield], 32));
        $line->addChild('CustItemNumber',    0);
        $line->addChild('ItemName',          xml_cleanstring($looprow['nimitys'], 100));
        $line->addChild('ItemText',          0);
        $line->addChild('BatchId',           0);
        $line->addChild('CustItemName',      0);
        $line->addChild('Type',              $looprow['tyyppi']);
        $line->addChild('BBDate',            0);
        $line->addChild('OrderedQuantity',   $looprow['kpl']);
        $line->addChild('DeliveredQuantity', $looprow['kpl']);
        $line->addChild('Unit',              xml_cleanstring($looprow['yksikko']));
        $line->addChild('Price',             $looprow['hinta']);
        $line->addChild('DiscountPercent',   $looprow['ale1']);
        $line->addChild('CurrencyCode',      $looprow['valkoodi']);
        $line->addChild('TaxCode',           $looprow['alv']);
        $line->addChild('Stockable',         $looprow['keratty']);
      }
      else {
        $line->addChild('ItemNumber',        xml_cleanstring($looprow[$logmaster_itemnumberfield], 22));
        $line->addChild('CustItemNumber',    0);
        $line->addChild('ItemName',          0);
        $line->addChild('ItemText',          0);
        $line->addChild('BatchId',           0);
        $line->addChild('CustItemName',      0);
        $line->addChild('Type',              1);
        $line->addChild('BBDate',            0);
        $line->addChild('OrderedQuantity',   $looprow['kpl']);
        $line->addChild('DeliveredQuantity', $looprow['kpl']);
        $line->addChild('Unit',              0);
        $line->addChild('Price',             0);
        $line->addChild('DiscountPercent',   0);
        $line->addChild('CurrencyCode',      0);
        $line->addChild('TaxCode',           0);
      }

      $line->addChild('LineInfo',          xml_cleanstring($looprow['kommentti'], 92));

      $_line_i++;
    }

    pupesoft_log('logmaster_outbound_delivery', "Tilauksen {$otunnus} sanomalle lisätty ".($_line_i - 1)." riviä.");

    $_name = substr("out_{$otunnus}_".md5(uniqid()), 0, 25);
    $filename = $pupe_root_polku."/dataout/{$_name}.xml";

    if (file_put_contents($filename, $xml->asXML())) {
      pupesoft_log('logmaster_outbound_delivery', "Tilauksen {$otunnus} sanoman luonti {$uj_nimi} -järjestelmään onnistui.");
      return $filename;
    }

    pupesoft_log('logmaster_outbound_delivery', "Tilauksen {$otunnus} sanoman luonti {$uj_nimi} -järjestelmään epäonnistui.");
    return false;
  }
}

if (!function_exists('logmaster_check_params')) {
  function logmaster_check_params($toim) {
    global $kukarow, $yhtiorow;

    $onkologmaster  = LOGMASTER_RAJAPINTA;
    $onkologmaster &= (in_array($yhtiorow['ulkoinen_jarjestelma'], array('', 'K')));
    $onkologmaster &= (in_array($toim, array('RIVISYOTTO','PIKATILAUS')));

    if ($onkologmaster === false) {
      return false;
    }

    $varastotunnukset = logmaster_warehouses();

    if ($varastotunnukset === false) {
      return false;
    }

    return true;
  }
}

if (!function_exists('logmaster_warehouses')) {
  function logmaster_warehouses() {
    global $kukarow;

    $query = "SELECT GROUP_CONCAT(tunnus) AS tunnukset
              FROM varastopaikat
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tyyppi != 'P'
              AND ulkoinen_jarjestelma IN ('L','P')";
    $varastores = pupe_query($query);
    $varastorow = mysql_fetch_assoc($varastores);

    return empty($varastorow['tunnukset']) ? false : $varastorow['tunnukset'];
  }
}

if (!function_exists('logmaster_fetch_rows')) {
  function logmaster_fetch_rows($tunnus, $otunnus = 0) {
    global $kukarow;

    $varastotunnukset = logmaster_warehouses();

    if ($varastotunnukset === false) {
      return false;
    }

    $wherelisa = '';
    $tunnus    = (int) $tunnus;
    $otunnus   = (int) $otunnus;

    if (empty($tunnus) and empty($otunnus)) {
      return false;
    }

    if (!empty($tunnus)) {
      $wherelisa = " AND tilausrivi.tunnus  = '{$tunnus}' ";
    }

    if (!empty($otunnus)) {
      $wherelisa = " AND tilausrivi.otunnus  = '{$otunnus}' ";
    }

    $query = "SELECT tilausrivi.*, tuote.sarjanumeroseuranta
              FROM tilausrivi
              JOIN tuote ON (
                tuote.yhtio     = tilausrivi.yhtio AND
                tuote.tuoteno   = tilausrivi.tuoteno AND
                tuote.ei_saldoa = ''
              )
              WHERE tilausrivi.yhtio  = '{$kukarow['yhtio']}'
              {$wherelisa}
              AND tilausrivi.var     != 'J'
              AND tilausrivi.varasto IN ({$varastotunnukset})";
    $logmaster_res = pupe_query($query);

    return $logmaster_res;
  }
}

if (!function_exists('logmaster_verify_row')) {
  function logmaster_verify_row($tunnus, $toim) {
    global $yhtiorow, $kukarow;

    $errors = array();

    if (logmaster_check_params($toim) === false) {
      return array();
    }

    # Sarjanumerollisia tuotteita ei tueta
    # Tilausrivien täytyy sisältää vain kokonaislukuja
    $logmaster_rivi_res = logmaster_fetch_rows($tunnus);

    if ($logmaster_rivi_res === false) {
      return array();
    }

    while ($logmaster_rivi_row = mysql_fetch_assoc($logmaster_rivi_res)) {
      if ($logmaster_rivi_row['sarjanumeroseuranta'] != '') {
        $errors[] = t("VIRHE: Sarjanumeroseurannassa olevia tuotteita ei sallita ulkoisessa varastossa")."!";
      }

      $logmaster_kpl = $logmaster_rivi_row['varattu'] + $logmaster_rivi_row['kpl'];

      if (fmod($logmaster_kpl, 1) != 0) {
        $errors[] = t("VIRHE: Ulkoinen varasto tukee vain kokonaislukuja")."!";
      }
    }

    return $errors;
  }
}

if (!function_exists('logmaster_verify_order')) {
  function logmaster_verify_order($tunnus, $toim) {
    global $yhtiorow, $kukarow;

    $errors = array();
    $tunnus = (int) $tunnus;

    if (logmaster_check_params($toim) === false) {
      return array();
    }

    # Maksuehto ei saa olla jälkivaatimus
    $query = "SELECT lasku.*, maksuehto.jv
              FROM lasku
              LEFT JOIN maksuehto ON (
                maksuehto.yhtio = lasku.yhtio AND
                maksuehto.tunnus = lasku.maksuehto
              )
              WHERE lasku.yhtio = '{$kukarow['yhtio']}'
              AND lasku.tunnus = '{$tunnus}'";
    $laskures = pupe_query($query);
    $laskurow = mysql_fetch_assoc($laskures);

    # Tarkistetaan onko Logmasteriin meneviä tilausrivejä
    $logmaster_rivi_res = logmaster_fetch_rows(0, $tunnus);

    if ($logmaster_rivi_res === false) {
      return array();
    }

    if (mysql_num_rows($logmaster_rivi_res) > 0 and $laskurow['jv'] != '') {
      $errors[] = t("VIRHE: Jälkivaatimuksia ei sallita ulkoisessa varastossa")."!";
    }

    return $errors;
  }
}
