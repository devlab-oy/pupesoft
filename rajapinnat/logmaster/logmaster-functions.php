<?php

if (!function_exists('logmaster_send_file')) {
  function logmaster_send_file($filename) {
    global $kukarow, $yhtiorow, $logmaster;

    // L‰hetet‰‰n aina UTF-8 muodossa
    $ftputf8 = true;

    $ftphost = $logmaster['host'];
    $ftpuser = $logmaster['user'];
    $ftppass = $logmaster['pass'];
    $ftppath = $logmaster['path'];

    $ftpfile = realpath($filename);

    require "inc/ftp-send.inc";

    return $palautus;
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
      die(t('Annettu arvo ei ole k‰ytˆss‰'));
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

    global $kukarow, $yhtiorow, $pupe_root_polku, $logmaster;

    $query = "SELECT lasku.*,
              tilausrivi.*,
              lasku.varasto AS otsikon_varasto,
              tilausrivi.kpl + tilausrivi.varattu AS kpl,
              tilausrivi.tunnus AS tilausrivin_tunnus,
              lasku.toimaika AS lasku_toimaika,
              asiakas.email,
              tuote.eankoodi
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
                tuote.tuoteno       = tilausrivi.tuoteno AND
                tuote.ei_saldoa     = ''
              )
              WHERE lasku.yhtio     = '{$kukarow['yhtio']}'
              AND lasku.tunnus      = '{$otunnus}'";
    $loopres = pupe_query($query);

    if (mysql_num_rows($loopres) == 0) {
      pupesoft_log('logmaster_outbound_delivery', "Yht‰‰n rivi‰ ei lˆytynyt tilaukselle {$otunnus}. Sanoman luonti ep‰onnistui.");

      return;
    }

    $looprow = mysql_fetch_assoc($loopres);

    $varastorow = hae_varasto($looprow['otsikon_varasto']);

    switch ($varastorow['ulkoinen_jarjestelma']) {
    case 'L':
      $uj_nimi = "Helsingin Hyllyvarasto";
      break;
    case 'P':
      $uj_nimi = "PostNord";
      break;
    default:
      pupesoft_log('logmaster_outbound_delivery', "Tilauksen {$otunnus} varaston ulkoinen j‰rjestelm‰ oli virheellinen.");

      return;
    }

    # S‰‰detaan muuttujia kuntoon
    $query = "SELECT tunnus
              FROM toimitustapa
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND selite  = '{$looprow['toimitustapa']}'";
    $toimitustapa_chk_res = pupe_query($query);
    $toimitustapa_chk_row = mysql_fetch_assoc($toimitustapa_chk_res);
    $toim_ta = $looprow['ohjelma_moduli'] == 'MAGENTO' ? t_avainsana('TOIM_TAPA_TA', '', '', '', '', 'selitetark') : null;
    $transport_account = $toim_ta ? $toim_ta : substr($toimitustapa_chk_row['tunnus'], 0, 10);

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

    $receiver = $custpickinglist->addChild('Receiver');
    $receiver->addChild('RecCustAccount',  0);
    $receiver->addChild('RecCustName',     xml_cleanstring($rec_cust_name, 50));
    $receiver->addChild('RecCustStreet',   xml_cleanstring($rec_cust_street, 50));
    $receiver->addChild('RecCustStreet2',  xml_cleanstring($rec_cust_street2, 50));
    $receiver->addChild('RecCustPostCode', xml_cleanstring($looprow['toim_postino'], 10));
    $receiver->addChild('RecCustCity',     xml_cleanstring($looprow['toim_postitp'], 30));
    $receiver->addChild('RecCustCountry',  xml_cleanstring($looprow['toim_maa'], 10));
    $receiver->addChild('RecCustPhone',    xml_cleanstring($looprow['toim_puh'], 30));

    $transport = $custpickinglist->addChild('Transport');
    $transport->addChild('TransportAccount',      $transport_account);
    $transport->addChild('TransportInstruction',  substr('', 0, 250));
    $transport->addChild('FreightPayer',          'sender');

    $lines = $custpickinglist->addChild('Lines');

    mysql_data_seek($loopres, 0);

    $_line_i = 1;
    $logmaster_itemnumberfield = logmaster_field('ItemNumber');

    while ($looprow = mysql_fetch_assoc($loopres)) {
      // Laitetaan kappalem‰‰r‰t kuntoon
      $looprow['kpl'] = $looprow['var'] == 'J' ? 0 : $looprow['kpl'];

      $line = $lines->addChild('Line');
      $line->addAttribute('No', $_line_i);
      $line->addChild('TransId',           xml_cleanstring($looprow['tilausrivin_tunnus'], 20));
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
      $line->addChild('LineInfo',          xml_cleanstring($looprow['kommentti'], 92));

      $_line_i++;
    }

    pupesoft_log('logmaster_outbound_delivery', "Tilauksen {$otunnus} sanomalle lis‰tty ".($_line_i - 1)." rivi‰.");

    $_name = substr("out_{$otunnus}_".md5(uniqid()), 0, 25);
    $filename = $pupe_root_polku."/dataout/{$_name}.xml";

    if (file_put_contents($filename, $xml->asXML())) {

      echo "<br /><font class='message'>", t("Tiedoston luonti onnistui"), "</font><br />";

      $palautus = logmaster_send_file($filename);

      if ($palautus == 0) {
        pupesoft_log('logmaster_outbound_delivery', "Siirretiin tilaus {$otunnus} {$uj_nimi} -j‰rjestelm‰‰n.");
      }
      else {
        pupesoft_log('logmaster_outbound_delivery', "Tilauksen {$otunnus} siirto {$uj_nimi} -j‰rjestelm‰‰n ep‰onnistui.");
      }
    }
    else {
      pupesoft_log('logmaster_outbound_delivery', "Tilauksen {$otunnus} sanoman luonti {$uj_nimi} -j‰rjestelm‰‰n ep‰onnistui.");
    }
  }
}
