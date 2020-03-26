<?php

if (!function_exists('smarten_send_email')) {
  function smarten_send_email($params) {
    $body        = "";
    $email       = $params['email'];
    $email_array = $params['email_array'];
    $log_name    = $params['log_name'];
    $subject     = "";

    if (empty($email) or !is_array($email_array) or count($email_array) == 0) {
      return;
    }

    switch ($log_name) {
    case 'smarten_outbound_delivery_confirmation':
      $subject = t("smarten: Keräyksen kuittaus");
      break;

    case 'smarten_inbound_delivery_confirmation':
      $subject = t("smarten: Saapumisen kuittaus");
      break;

    case 'smarten_stock_report':
      $subject = t("smarten saldovertailu");
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

if (!function_exists('smarten_send_file')) {
  function smarten_send_file($filename, $binary = FALSE) {
    global $kukarow, $yhtiorow, $smarten;

    // Lähetetään aina UTF-8 muodossa
    $ftputf8 = true;

    # Ei haluta että tulostetaan mitään ruudulle
    $tulos_ulos = "foobar";

    $ftphost = $smarten['host'];
    $ftpuser = $smarten['user'];
    $ftppass = $smarten['pass'];
    $ftppath = $smarten['path'];

    $ftpbinary = $binary;

    $ftpfile = realpath($filename);

    require "inc/ftp-send.inc";

    return $palautus;
  }
}

if (!function_exists('smarten_sent_timestamp')) {
  function smarten_sent_timestamp($tunnus) {
    global $kukarow;

    $query = "UPDATE lasku SET
              lahetetty_ulkoiseen_varastoon = now()
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND (lahetetty_ulkoiseen_varastoon IS NULL or lahetetty_ulkoiseen_varastoon = 0)
              AND tunnus = '{$tunnus}'";
    $res = pupe_query($query);
  }
}

if (!function_exists('smarten_mark_as_sent')) {
  function smarten_mark_as_sent($tunnus) {
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

if (!function_exists('smarten_field')) {
  function smarten_field($column_name) {

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

if (!function_exists('smarten_message_type')) {
  function smarten_message_type($file_name) {
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

if (!function_exists('smarten_timestamp')) {
  function smarten_timestamp($time)
  {
    return date("Y-m-d\TH:i:s.v", $time);
  }
}

if (!function_exists('smarten_outbounddelivery')) {
  function smarten_outbounddelivery($otunnus) {

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
              lasku.toim_email,
              asiakas.asiakasnro,
              asiakas.tunnus asiakastunnus,
              tuote.eankoodi,
              tuote.ei_saldoa,
              tuote.tullinimike1,
              laskun_lisatiedot.noutopisteen_tunnus,
              varastopaikat.ulkoisen_jarjestelman_tunnus
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
              LEFT JOIN varastopaikat ON (
                varastopaikat.yhtio  = tilausrivi.yhtio AND
                varastopaikat.tunnus = tilausrivi.varasto                
              )
              WHERE lasku.yhtio     = '{$kukarow['yhtio']}'
              AND lasku.tunnus      = '{$otunnus}'";
    $loopres = pupe_query($query);

    if (mysql_num_rows($loopres) == 0) {
      pupesoft_log('smarten_outbound_delivery', "Yhtään riviä ei löytynyt tilaukselle {$otunnus}. Sanoman luonti epäonnistui.");
      return false;
    }

    $looprow = mysql_fetch_assoc($loopres);

    $varastorow = hae_varasto($looprow['otsikon_varasto']);

    $query = "SELECT *
              FROM asiakkaan_avainsanat
              WHERE yhtio       = '{$kukarow['yhtio']}'
              AND liitostunnus  = '{$looprow['asiakastunnus']}'
              AND laji          = 'KICK_EDI'
              AND avainsana    != ''";
    $edi_chk_res = pupe_query($query);

    // Special EDI case
    $edi_pack_process = FALSE;
    if (mysql_num_rows($edi_chk_res)) {
      $edi_pack_process = TRUE;
    }

    switch ($varastorow['ulkoinen_jarjestelma']) {
    case 'S':
      $uj_nimi = "Smarten";
      break;
    default:
      pupesoft_log('smarten_outbound_delivery', "Tilauksen {$otunnus} varaston ulkoinen järjestelmä oli virheellinen.");

      return false;
    }

    # S��det��n muuttujia kuntoon
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

    if ($looprow['ohjelma_moduli'] == 'MAGENTO' and $looprow['ohjausmerkki'] != 'VARASTOTÄYDENNYS') {
      $tilaustyyppi = 8;
    }
    elseif ($looprow['clearing'] == 'ENNAKKOTILAUS') {
      $tilaustyyppi = 9;
    }
    elseif ($looprow['tilaustyyppi'] == 'U') {
      $tilaustyyppi = 7;
    }
    elseif ($edi_pack_process) {
      $tilaustyyppi = 'K';
    }
    else {
      $tilaustyyppi = '';
    }

    $CustOrderNumber = $looprow['asiakkaan_tilausnumero'];

    // Katsotaan onko CustOrderNumber kenttään erillistä määritystä
    $query = "SELECT *
              FROM toimitustavan_avainsanat
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND liitostunnus = '{$toimitustapa_chk_row['tunnus']}'
              AND laji = 'ulk_var_param'
              AND selite = 'CustOrderNumber'";
    $toimitustavan_avainsanat_res = pupe_query($query);

    if (mysql_num_rows($toimitustavan_avainsanat_res) == 1) {
      $toimitustavan_avainsanat_row = mysql_fetch_assoc($toimitustavan_avainsanat_res);

      // normaali tilausnumero
      if ($toimitustavan_avainsanat_row['selitetark'] == 'k') {
        $CustOrderNumber = $otunnus;
      }
    }

    $smarten_itemnumberfield = smarten_field('ItemNumber');

    # Rakennetaan XML
    $xml = simplexml_load_string("<?xml version='1.0' encoding='UTF-8'?><E-Document></E-Document>");

    $header = $xml->addChild('Header');
    $header->addChild('DateIssued', smarten_timestamp($looprow['luontiaika']));
    $header->addChild('Version',    "1");
    $header->addChild('SenderID',   "BNNB");
    $header->addChild('ReceiverID', "SMTN");

    $document = $xml->addChild('Document');
    $document->addChild('DocumentType', "desorder");

    $documentparties = $document->addChild('DocumentParties');

    $buyerparty = $documentparties->addChild("BuyerParty");
    $buyerparty->addChild("Name", xml_cleanstring($looprow['nimi']));

    $deliveryparty = $documentparties->addChild("BuyerParty");
    $deliveryparty->addChild("PartyCode", "");
    $deliveryparty->addChild("Name", xml_cleanstring($looprow['toim_nimi']));

    $contactdata = $deliveryparty->addChild("ContactData");

    $actualaddress = $contactdata->addChild("ActualAddress");
    $actualaddress->addChild("Address1", xml_cleanstring($looprow['toim_osoite']));
    $actualaddress->addChild("City", xml_cleanstring($looprow['toim_postitp']));
    $actualaddress->addChild("PostalCode", xml_cleanstring($looprow['toim_postino']));
    $actualaddress->addChild("CountryCode", xml_cleanstring($looprow['toim_maa']));

    $contactdata->addChild("PhoneNum", "");
    $contactdata->addChild("MobileNum", xml_cleanstring($looprow['toim_puh']));

    $sellerparty = $documentparties->addChild("SellerParty");
    $sellerparty->addChild("PartyCode", "BNNB");

    $documentinfo = $document->addChild('DocumentInfo');
    $documentinfo->addChild("DocumentName", "SalesOrder");
    $documentinfo->addChild("DocumentNum", $otunnus);
    $documentinfo->addChild("PaymentTerm", xml_cleanstring($looprow['maksuteksti']));

    $dateinfo = $documentinfo->addChild("DateInfo");
    // $dateinfo->addChild("DueDate", "");
    $dateinfo->addChild("DeliveryDateRequested", smarten_timestamp($looprow['lasku_toimaika']));
    //$dateinfo->addChild("IssueDate", "");

    $refinfo = $documentinfo->addChild("RefInfo");

    $sourcedocument = $refinfo->addChild("DueDate");
    $sourcedocument->addAttribute("type", "order");
    $sourcedocument->addChild("SourceDocumentNum", $looprow['asiakkaan_tilausnumero']);
    $sourcedocument->addChild("SourceDocumentDate", "");

    // $refinfo->addChild("TransportReferenceID", "");

    if ($looprow['sisviesti3'] == "TEST") {
      $documentinfo->addChild("CreatedBy", "TEST");
    }

    $documentitem = $document->addChild('DocumentItem');

    $varastotunnus = xml_cleanstring($looprow['ulkoisen_jarjestelman_tunnus']);
    if (empty($varastotunnus)) {
      $varastotunnus = xml_cleanstring($looprow['varasto']);
    }

    mysql_data_seek($loopres, 0);
    while ($looprow = mysql_fetch_assoc($loopres)) {
      $itementry = $documentitem->addChild('ItemEntry');

      $itementry->addChild("LineItemNum", $looprow['tilausrivin_tunnus']);
      $itementry->addChild("SellerItemCode", xml_cleanstring($looprow['tuoteno']));
      $itementry->addChild("ItemDescription", xml_cleanstring($looprow['nimitys']));

      //$itemunitrecord = $itementry->addChild("ItemUnitRecord");
      //$itemunitrecord->addChild("ItemUnit", xml_cleanstring($looprow['yksikko']));

      //$itementry->addChild("BaseUnit", xml_cleanstring($looprow['yksikko']));
      $itementry->addChild("AmountOrdered", xml_cleanstring($looprow['kpl']));
      $itementry->addChild("ItemPrice", xml_cleanstring($looprow['hinta']));
      // $itementry->addChild("ItemBasePrice", xml_cleanstring($looprow['hinta_alkuperainen']));
      // $itementry->addChild("ItemSum", xml_cleanstring($looprow['rivihinta']));

      // $addition = $itementry->addChild("Addition");
      // $addition->addAttribute("addCode", "DSC");
      // $addition->addChild("AddContent", "");
      // $addition->addChild("AddRate", "");
      // $addition->addChild("AddSum", "");

      // $vat = $itementry->addChild("VAT");
      // $vat->addAttribute("vatID", "");
      // $vat->addChild("SumBeforeVAT", xml_cleanstring($looprow['rivihinta']));
      // $vat->addChild("VATRate", xml_cleanstring($looprow['alv']));
      // $vat->addChild("VATSum", xml_cleanstring($looprow['rivihinta']));

      // $itementry->addChild("ItemTotal", "");

      $itemreserve = $itementry->addChild("ItemReserve");

      $itemreserveunit = $itemreserve->addChild("ItemReserveUnit");
      // $itemreserveunit->addChild("ItemUnit", "");
      $itemreserveunit->addChild("AmountActual", xml_cleanstring($looprow['kpl']));

      $location = $itemreserve->addChild("Location");
      $location->addChild("WarehouseCode", $varastotunnus);

      // $additionalinfo = $itementry->addChild("AdditionalInfo");

      // $extension = $additionalinfo->addChild("Extension");
      // $extension->addAttribute("extensionId", "externalremarks");
      // $extension->addChild("InfoContent", xml_cleanstring($looprow['kommentti']));
    }

    mysql_data_seek($loopres, 0);
    $looprow = mysql_fetch_assoc($loopres);

    $additionalinfo = $document->addChild('AdditionalInfo');

    $extension = $additionalinfo->addChild("Extension");
    $extension->addAttribute("extensionId", "internalremarks");
    $extension->addChild("InfoContent", xml_cleanstring($looprow['sisviesti2']));

    $extension = $additionalinfo->addChild("Extension");
    $extension->addAttribute("extensionId", "externalremarks");
    $extension->addChild("InfoContent", xml_cleanstring($looprow['kommentti']));






/*
    $custpickinglist = $xml->addChild('CustPickingList');
    $custpickinglist->addChild('SalesId',             substr($otunnus, 0, 20));
    $custpickinglist->addChild('PickingListId',       substr($otunnus, 0, 20));
    $custpickinglist->addChild('CustOrderNumber',     xml_cleanstring($CustOrderNumber, 20));
    $custpickinglist->addChild('CustReference',       xml_cleanstring($looprow['viesti'], 50));
    $custpickinglist->addChild('OrderCode',          $tilaustyyppi);
    $custpickinglist->addChild('OrderType',          'SO');
    $custpickinglist->addChild('PickingListDate',    $pickinglistdate);
    $custpickinglist->addChild('DeliveryDate',       $deliverydate);
    $custpickinglist->addChild('UnloadingDate',      0);
    $custpickinglist->addChild('OrderDate',          0);
    $custpickinglist->addChild('DlvTerm',            xml_cleanstring($looprow['toimitusehto'], 3));
    $custpickinglist->addChild('PickingInstruction', xml_cleanstring($looprow['sisviesti2'], 128));
    $custpickinglist->addChild('SalesOffice',        0);
    $custpickinglist->addChild('SalesContact',       0);
    $custpickinglist->addChild('Warehouse',          xml_cleanstring($looprow['ulkoisen_jarjestelman_tunnus']));
    $custpickinglist->addChild('InvoiceAmounth',     0);

    $orderedby = $custpickinglist->addChild('OrderedBy');
    $orderedby->addChild('CustAccount',  0);
    $orderedby->addChild('CustName',     xml_cleanstring($cust_name, 50));
    $orderedby->addChild('CustStreet',   xml_cleanstring($looprow['osoite'], 50));
    $orderedby->addChild('CustStreet2',  xml_cleanstring($looprow['osoite'], 50));
    $orderedby->addChild('CustPostCode', xml_cleanstring($looprow['postino'], 10));
    $orderedby->addChild('CustCity',     xml_cleanstring($looprow['postitp'], 30));
    $orderedby->addChild('CustCountry',  xml_cleanstring($looprow['maa'], 10));
    if ($looprow['toim_email'] == '') {
      $orderedby->addChild('Email',        xml_cleanstring($looprow['email']));
    }
      else {
      $orderedby->addChild('Email',        xml_cleanstring($looprow['toim_email']));
    }

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
    $smarten_itemnumberfield = smarten_field('ItemNumber');

    while ($looprow = mysql_fetch_assoc($loopres)) {
      // Laitetaan kappalemäärät kuntoon
      $looprow['kpl'] = $looprow['var'] == 'J' ? 0 : $looprow['kpl'];

      if ($uj_nimi == 'PostNord' and $looprow['ei_saldoa'] == 'o') continue;

      $line = $lines->addChild('Line');
      $line->addAttribute('No', $_line_i);
      $line->addChild('TransId',           xml_cleanstring($looprow['tilausrivin_tunnus'], 20));

      if ($uj_nimi == "Velox") {
        $line->addChild('ItemNumber',        xml_cleanstring($looprow[$smarten_itemnumberfield], 32));
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
        $line->addChild('ItemNumber',        xml_cleanstring($looprow[$smarten_itemnumberfield], 22));
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
*/
    pupesoft_log('smarten_outbound_delivery', "Tilauksen {$otunnus} sanomalle lisätty ".($_line_i - 1)." riviä.");

    $_name = substr("out_{$otunnus}_".md5(uniqid()), 0, 25);
    $filename = $pupe_root_polku."/dataout/{$_name}.xml";

    if (file_put_contents($filename, $xml->asXML())) {
      pupesoft_log('smarten_outbound_delivery', "Tilauksen {$otunnus} sanoman luonti {$uj_nimi} -järjestelmään onnistui.");
      return $filename;
    }

    pupesoft_log('smarten_outbound_delivery', "Tilauksen {$otunnus} sanoman luonti {$uj_nimi} -järjestelmään epäonnistui.");
    return false;
  }
}

if (!function_exists('smarten_check_params')) {
  function smarten_check_params($toim) {
    global $kukarow, $yhtiorow;

    $onkosmarten  = smarten_RAJAPINTA;
    $onkosmarten &= (in_array($yhtiorow['ulkoinen_jarjestelma'], array('', 'K')));
    $onkosmarten &= (in_array($toim, array('RIVISYOTTO','PIKATILAUS')));

    if ($onkosmarten === false) {
      return false;
    }

    $varastotunnukset = smarten_warehouses();

    if ($varastotunnukset === false) {
      return false;
    }

    return true;
  }
}

if (!function_exists('smarten_warehouses')) {
  function smarten_warehouses() {
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

if (!function_exists('smarten_fetch_rows')) {
  function smarten_fetch_rows($tunnus, $otunnus = 0) {
    global $kukarow;

    $varastotunnukset = smarten_warehouses();

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
    $smarten_res = pupe_query($query);

    return $smarten_res;
  }
}

if (!function_exists('smarten_verify_row')) {
  function smarten_verify_row($tunnus, $toim) {
    global $yhtiorow, $kukarow;

    $errors = array();

    if (smarten_check_params($toim) === false) {
      return array();
    }

    # Sarjanumerollisia tuotteita ei tueta
    # Tilausrivien täytyy sisältää vain kokonaislukuja
    $smarten_rivi_res = smarten_fetch_rows($tunnus);

    if ($smarten_rivi_res === false) {
      return array();
    }

    while ($smarten_rivi_row = mysql_fetch_assoc($smarten_rivi_res)) {
      if ($smarten_rivi_row['sarjanumeroseuranta'] != '') {
        $errors[] = t("VIRHE: Sarjanumeroseurannassa olevia tuotteita ei sallita ulkoisessa varastossa")."!";
      }

      $smarten_kpl = $smarten_rivi_row['varattu'] + $smarten_rivi_row['kpl'];

      if (fmod($smarten_kpl, 1) != 0) {
        $errors[] = t("VIRHE: Ulkoinen varasto tukee vain kokonaislukuja")."!";
      }
    }

    return $errors;
  }
}

if (!function_exists('smarten_verify_order')) {
  function smarten_verify_order($tunnus, $toim) {
    global $yhtiorow, $kukarow;

    $errors = array();
    $tunnus = (int) $tunnus;

    if (smarten_check_params($toim) === false) {
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

    # Tarkistetaan onko smarteniin meneviä tilausrivejä
    $smarten_rivi_res = smarten_fetch_rows(0, $tunnus);

    if ($smarten_rivi_res === false) {
      return array();
    }

    if (mysql_num_rows($smarten_rivi_res) > 0 and $laskurow['jv'] != '') {
      $errors[] = t("VIRHE: Jälkivaatimuksia ei sallita ulkoisessa varastossa")."!";
    }

    return $errors;
  }
}
