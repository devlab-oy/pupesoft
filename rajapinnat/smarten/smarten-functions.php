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
      $subject = t("Smarten: Ker‰yksen kuittaus");
      break;

    case 'smarten_inbound_delivery_confirmation':
      $subject = t("Smarten: Saapumisen kuittaus");
      break;

    case 'smarten_stock_report':
      $subject = t("Smarten saldovertailu");
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
      pupesoft_log($log_name, "S‰hkˆposti l‰hetetty onnistuneesti osoitteeseen {$email}");
    }
    else {
      pupesoft_log($log_name, "S‰hkˆpostin l‰hetys ep‰onnistui osoitteeseen {$email}");
    }
  }
}

if (!function_exists('smarten_send_file')) {
  function smarten_send_file($filename, $path = "") {
    global $kukarow, $yhtiorow, $smarten;

    // L‰hetet‰‰n aina UTF-8 muodossa
    $ftputf8 = true;

    # Ei haluta ett‰ tulostetaan mit‰‰n ruudulle
    $tulos_ulos = "foobar";

    $ftphost = $smarten['host'];
    $ftpuser = $smarten['user'];
    $ftppass = $smarten['pass'];
    $ftppath = $smarten['path_to'];
    $ftpport = $smarten['port'];
    $ftpskey = $smarten['skey'];

    if (!empty($path)) {
      $ftppath = $path;
    }

    $ftpfile = realpath($filename);

    require "inc/sftp-send.inc";

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
      # T‰ll‰ yhti‰n parametrilla pystyt‰‰n ohittamaan tulostus
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
      # T‰ll‰ yhtiˆn parametrilla pystyt‰‰n ohittamaan ker‰yslistan tulostus
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

    $dtype = "";
    $dsubtype = "";

    if (isset($xml->Document) and isset($xml->Document->DocumentType)) {
      $dtype = trim($xml->Document->DocumentType);
    }

    if (isset($xml->Document->DocumentInfo) and isset($xml->Document->DocumentInfo->DocumentSubType)) {
      $dsubtype = trim($xml->Document->DocumentInfo->DocumentSubType);
    }

    return array($dtype, $dsubtype);
  }
}

if (!function_exists('smarten_timestamp')) {
  function smarten_timestamp($time)
  {
    $result = str_replace(" ", "T", $time);
    return $result;
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
                tuote.yhtio     = tilausrivi.yhtio AND
                tuote.tuoteno   = tilausrivi.tuoteno AND
                tuote.ei_saldoa = ''
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
      pupesoft_log('smarten_outbound_delivery', "Yht‰‰n rivi‰ ei l‰ytynyt tilaukselle {$otunnus}. Sanoman luonti ep‰onnistui.");
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

    if ($varastorow['ulkoinen_jarjestelma'] != "S") {
      pupesoft_log('smarten_outbound_delivery', "Tilauksen {$otunnus} varaston ulkoinen j‰rjestelm‰ oli virheellinen.");
      return false;
    }

    # S‰‰det‰‰n muuttujia kuntoon
    $query = "SELECT *
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

    $CustOrderNumber = $looprow['asiakkaan_tilausnumero'];

    // Katsotaan onko CustOrderNumber kentt‰‰n erillist‰ m‰‰rityst‰
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

    # Varastosiirrot
    if ($looprow['tila'] == 'G') {
      $query = "SELECT email, puhelin
                FROM varastopaikat
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus = '{$looprow['clearing']}'";
      $vvarres = pupe_query($query);
      $vvarrow = mysql_fetch_assoc($vvarres);

      $looprow['toim_puh'] = $vvarrow['puhelin'];
      $looprow['toim_email'] = $vvarrow['email'];
    }

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
    $buyerparty->addChild("Name", xml_cleanstring($cust_name));

    $deliveryparty = $documentparties->addChild("DeliveryParty");
    $deliveryparty->addChild("PartyCode", $toimitustapa_chk_row['smarten_partycode']);
    $deliveryparty->addChild("Name", xml_cleanstring($rec_cust_name));

    $contactdata = $deliveryparty->addChild("ContactData");

    $actualaddress = $contactdata->addChild("ActualAddress");
    $actualaddress->addChild("Address1", xml_cleanstring($looprow['toim_osoite']));
    $actualaddress->addChild("City", xml_cleanstring($looprow['toim_postitp']));
    $actualaddress->addChild("PostalCode", xml_cleanstring($looprow['toim_postino']));
    $actualaddress->addChild("CountryCode", xml_cleanstring($looprow['toim_maa']));

    $contactdata->addChild("PhoneNum", "");
    $contactdata->addChild("MobileNum", xml_cleanstring($looprow['toim_puh']));

    if ($looprow['toim_email'] == '') {
      // Unifaun/Smarten only wants the first Email address if there's many...
      $emaili = trim(array_shift(explode(',', $looprow['email'])));
      $contactdata->addChild('EmailAddress', xml_cleanstring($emaili));
    }
    else {
      // Unifaun/Smarten only wants the first Email address if there's many...
      $emaili = trim(array_shift(explode(',', $looprow['toim_email'])));
      $contactdata->addChild('EmailAddress', xml_cleanstring($emaili));
    }

    if ($edi_pack_process) {
      $recipientparty = $documentparties->addChild("RecipientParty");
      $recipientparty->addChild("PartyCode", $looprow['viesti']);
      $recipientparty->addChild("Name", $looprow['viesti']);
    }
    elseif (!empty($looprow['noutopisteen_tunnus'])) {
      $recipientparty = $documentparties->addChild("RecipientParty");
      $recipientparty->addChild("PartyCode", $looprow['noutopisteen_tunnus']);
      $recipientparty->addChild("Name", $looprow['noutopisteen_tunnus']);
    }

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

    $sourcedocument = $refinfo->addChild("SourceDocument");
    $sourcedocument->addAttribute("type", "order");
    $sourcedocument->addChild("SourceDocumentNum", $CustOrderNumber);
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

    if (!empty($looprow['noutopisteen_tunnus'])) {
      $extension = $additionalinfo->addChild("Extension");
      $extension->addAttribute("extensionId", "ParcelMachine");
      $extension->addChild("InfoContent", "1");
    }
    elseif (!empty($toimitustapa_chk_row['smarten_serviceid'])) {
      $extension = $additionalinfo->addChild("Extension");
      $extension->addAttribute("extensionId", $toimitustapa_chk_row['smarten_serviceid']);
      $extension->addChild("InfoContent", "1");
    }

    if ($edi_pack_process) {
      $extension = $additionalinfo->addChild("Extension");
      $extension->addAttribute("extensionId", "SSCC");
      $extension->addChild("InfoContent", "1");
    }

    $query = "SELECT filename, tunnus
              FROM liitetiedostot
              WHERE yhtio         = '{$kukarow["yhtio"]}'
              AND liitos          = 'lasku'
              AND liitostunnus    = {$otunnus}
              AND kayttotarkoitus = 'SMARTEN'";
    $liiteres = pupe_query($query);

    while ($liitetiedosto = mysql_fetch_assoc($liiteres)) {
      $path_parts = pathinfo($liitetiedosto['filename']);
      $ext = strtoupper($path_parts['extension']);
      $filename = "{$otunnus}_{$liitetiedosto['tunnus']}.{$ext}";

      $extension = $additionalinfo->addChild("Extension");
      $extension->addAttribute("extensionId", "file");
      $extension->addChild("InfoContent", $filename);
    }

    pupesoft_log('smarten_outbound_delivery', "Tilauksen {$otunnus} sanomalle lis‰tty.");

    $_date = date("Ymd_His");
    $filename = "/home/smarten/out/{$_date}_desorder_BNNB_{$otunnus}.xml";

    if (file_put_contents($filename, $xml->asXML())) {
      pupesoft_log('smarten_outbound_delivery', "Tilauksen {$otunnus} sanoman luonti Smarten-j‰rjestelm‰‰n onnistui.");
      return $filename;
    }

    pupesoft_log('smarten_outbound_delivery', "Tilauksen {$otunnus} sanoman luonti Smarten-j‰rjestelm‰‰n ep‰onnistui.");
    return false;
  }
}

if (!function_exists("smarten_outbounddelivery_attachments")) {
  function smarten_outbounddelivery_attachments($tilausnumero) {
    global $kukarow, $smarten;

    $query = "SELECT filename, data, tunnus
              FROM liitetiedostot
              WHERE yhtio         = '{$kukarow["yhtio"]}'
              AND liitos          = 'lasku'
              AND liitostunnus    = {$tilausnumero}
              AND kayttotarkoitus = 'SMARTEN'";
    $result = pupe_query($query);

    $liitetiedostot = array();

    while ($liitetiedosto = mysql_fetch_assoc($result)) {
      $path_parts = pathinfo($liitetiedosto['filename']);
      $ext = strtoupper($path_parts['extension']);
      $filename = "/tmp/{$tilausnumero}_{$liitetiedosto['tunnus']}.{$ext}";
      file_put_contents($filename, $liitetiedosto["data"]);
      smarten_send_file($filename, $smarten['path_to_files']);
    }
  }
}

if (!function_exists('smarten_check_params')) {
  function smarten_check_params($toim) {
    global $kukarow, $yhtiorow;

    $onkosmarten  = SMARTEN_RAJAPINTA;
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
              AND ulkoinen_jarjestelma = 'S'";
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
    # Tilausrivien t‰ytyy sis‰lt‰‰ vain kokonaislukuja
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

    # Maksuehto ei saa olla j‰lkivaatimus
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

    # Tarkistetaan onko smarteniin menevi‰ tilausrivej‰
    $smarten_rivi_res = smarten_fetch_rows(0, $tunnus);

    if ($smarten_rivi_res === false) {
      return array();
    }

    if (mysql_num_rows($smarten_rivi_res) > 0 and $laskurow['jv'] != '') {
      $errors[] = t("VIRHE: J‰lkivaatimuksia ei sallita ulkoisessa varastossa")."!";
    }

    return $errors;
  }
}
