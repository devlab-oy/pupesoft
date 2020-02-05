<?php

if (!function_exists('CheckHeaders')) {
  function CheckHeaders($params) {

    if (!isset($params['method']) or trim($params['method']) != 'POST') {
      pupesoft_log('sendorder', "CheckHeaders: Method is not set or method is not POST. Method: {$params['method']}");

      return false;
    }

    if (!isset($params['url']) or trim($params['url']) == '') {
      pupesoft_log('sendorder', "CheckHeaders: URL is not set or URL is empty.");

      return false;
    }

    if (!isset($params['headers']) or !is_array($params['headers']) or count($params['headers']) == 0) {
      pupesoft_log('sendorder', "CheckHeaders: Headers is not set, headers is not an array or array is empty.");

      return false;
    }

    if (!isset($params['posttype']) or trim($params['posttype']) != 'xml') {
      pupesoft_log('sendorder', "CheckHeaders: Post-type is not set or post-type is not XML. Post-type: {$params['posttype']}");

      return false;
    }

    pupesoft_log('sendorder', "CheckHeaders: OK");

    return true;
  }


}

if (!function_exists('GetServerInfo')) {
  function GetServerInfo($params) {

    if (!CheckHeaders($params)) return false;

    // GetServerInfo
    $xml = simplexml_load_string("<?xml version='1.0' encoding='ISO-8859-1'?><Message><Action>GetServerInfo</Action></Message>");

    $params['data'] = array($xml->asXML());
    $return = pupesoft_rest($params);

    if ($return[0] != 200) {
      pupesoft_log('sendorder', "GetServerInfo: Fail. HTTP Status Code is not 200. HTTP Status Code: {$return[0]}");

      return false;
    }

    pupesoft_log('sendorder', "GetServerInfo: OK. HTTP Status Code: 200");

    $xml = simplexml_load_string($return[1]);

    pupesoft_log('sendorder', "GetServerInfo: XML Status Code: {$xml->StatusCode}");

    return $xml->StatusCode == 0 ? true : false;
  }


}

if (!function_exists('SendOrder')) {
  function SendOrder($headers, $params) {

    global $futursoft_rest_folder;

    if (!GetServerInfo($headers)) return false;

    if (!isset($params['ordernumber'])) {
      pupesoft_log('sendorder', "SendOrder: OrderNumber is not set.");
      return false;
    }

    if (!isset($params['suppliernumber'])) {
      pupesoft_log('sendorder', "SendOrder: SupplierNumber is not set.");
      return false;
    }

    if (!isset($params['cust_id'])) {
      pupesoft_log('sendorder', "SendOrder: CustomerID is not set.");
      return false;
    }

    if (!isset($params['username'])) {
      pupesoft_log('sendorder', "SendOrder: Username is not set.");
      return false;
    }

    if (!isset($params['password'])) {
      pupesoft_log('sendorder', "SendOrder: Password is not set.");
      return false;
    }

    if (!isset($params['rows'])) {
      pupesoft_log('sendorder', "SendOrder: Rows is not set.");
      return false;
    }

    if (!isset($params['yhtio'])) {
      pupesoft_log('sendorder', "SendOrder: Company is not set.");
      return false;
    }

    if (!isset($params['created'])) $params['created'] = 'cron';

    pupesoft_log('sendorder', "SendOrder: Starting to create XML for order {$params['ordernumber']}.");
    pupesoft_log('sendorder', "SendOrder: Number of rows ".count($params['rows']).".");

    // SendOrder
    $xml = simplexml_load_string("<?xml version='1.0' encoding='ISO-8859-1' ?><Message></Message>");

    $xml->addChild('MessageType', 7);
    $xml->addChild('Action', 'SendOrder');
    $xml->addChild('User', $params['created']);

    $order = $xml->addChild('Order');
    $order->addChild('OrderNumber', $params['ordernumber']);
    $order->addChild('DeliveryCode', $params['deliverycode']);
    $order->addChild('SupplierNumber', $params['suppliernumber']);
    $order->addChild('CustomerID', $params['cust_id']);
    $order->addChild('Username', $params['username']);
    $order->addChild('Password', $params['password']);

    $products = $order->addChild('Products');

    foreach ($params['rows'] as $row) {

      $product = $products->addChild('Product');

      $product->addChild('RowNumber', $row['rownumber']);
      $product->addChild('ProductCode', $row['productcode']);

      $product->addChild('ProductName', preg_replace("/[äöåÄÖÅØ]/", "", $row['productname']));
      $product->addChild('Quantity', (int) $row['quantity']);
    }

    $headers['data'] = array($xml->asXML());
    $headers['timeout'] = 5;

    pupesoft_log('sendorder', "SendOrder: XML created.");
    pupesoft_log('sendorder', "SendOrder: Calling pupesoft_rest-function.");

    $return = pupesoft_rest($headers);

    $xml_response = simplexml_load_string(utf8_encode($return[1]));

    // Jos koodi on 200 eli success
    if ($return[0] == 200 and isset($xml_response->StatusCode) and $xml_response->StatusCode == 0) {
      // Lähetys onnistui
      pupesoft_log('sendorder', "SendOrder: Sending success. HTTP Status Code: 200. XML Status Code: 0. Order Number: {$params['ordernumber']}");
      $msg = "Lähetys onnistui.";
    }
    else {
      pupesoft_log('sendorder', "SendOrder: Sending failed. HTTP Status Code: {$return[0]}. XML Status Code: {$xml_response->StatusCode}. Order Number: {$params['ordernumber']}");
      $msg ="VIRHE: Lähetys epäonnistui. Lähetä ostotilaus uudestaan.";
    }

    echo "<script type='text/javascript'>
          $(function() {
            alert('$msg')
          });
          </script>";
  }


}

if (isset($luo) and $luo == 'sendorder' and isset($laskurow) and is_array($laskurow)) {

  $headers = array(
    "method" => "POST",
    "url" => $futursoft_rest_http[$kukarow['yhtio']],
    "headers" => array('Accept:text/xml', 'Content-Type: text/xml'),
    "posttype" => "xml",
  );

  $avainsana_result = t_avainsana('SAHKTILTUN', '', " AND selite = '{$laskurow['vanhatunnus']}' AND selitetark = '{$laskurow['liitostunnus']}' ");

  if (mysql_num_rows($avainsana_result) > 0) {

    $tilausrivit = array();

    $query = "SELECT tilausrivi.*
              FROM tilausrivi
              LEFT JOIN tuotteen_avainsanat AS ta ON (ta.yhtio = tilausrivi.yhtio AND ta.tuoteno = tilausrivi.tuoteno AND ta.laji = 'ei_edi_ostotilaukseen')
              WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
              AND tilausrivi.tyyppi  = 'O'
              AND tilausrivi.otunnus = '{$laskurow['tunnus']}'
              AND ta.tunnus IS NULL";
    $tilausrivires = pupe_query($query);

    $i = 1;

    while ($tilausrivirow = mysql_fetch_assoc($tilausrivires)) {

      $query = "SELECT toim_tuoteno
                FROM tuotteen_toimittajat
                WHERE yhtio      = '{$kukarow['yhtio']}'
                AND tuoteno      = '{$tilausrivirow['tuoteno']}'
                AND liitostunnus = '{$laskurow['liitostunnus']}'";
      $toim_tuoteno_res = pupe_query($query);
      $toim_tuoteno_row = mysql_fetch_assoc($toim_tuoteno_res);

      $_nimitys = $tilausrivirow['nimitys'];

      // siistitään pari custom merkkiä ja tehdään csv kelpoinen siivous
      $old = array("É", "°");
      $new = array("E", "&deg");
      $_nimitys = str_replace($old, $new, $_nimitys);

      $_nimitys = trim(pupesoft_csvstring($_nimitys));

      $tilausrivit[] = array(
        'rownumber' => $i,
        'productcode' => $toim_tuoteno_row['toim_tuoteno'],
        'quantity' => $tilausrivirow['varattu'],
        'productname' => $_nimitys,
      );

      $query = "UPDATE tilausrivi SET
                tilaajanrivinro = $i
                WHERE yhtio     = '{$kukarow['yhtio']}'
                AND tunnus      = '{$tilausrivirow['tunnus']}'";
      pupe_query($query, $masterlink);

      $i++;
    }

    if (count($tilausrivit) > 0) {

      $avainsana_row = mysql_fetch_assoc($avainsana_result);

      $params = array(
        'created' => $kukarow['kuka'],
        'ordernumber' => $laskurow['tunnus'],
        'deliverycode' => $laskurow['kuljetus'],
        'cust_id' => $avainsana_row['selitetark_2'],
        'username' => $avainsana_row['selitetark_3'],
        'password' => $avainsana_row['selitetark_4'],
        'suppliernumber' => $avainsana_row['selitetark_5'],
        'rows' => $tilausrivit,
        'yhtio' => $kukarow['yhtio'],
      );

      echo "<font class='message'>", t("Odota hetki vaihtamatta ikkunaa, lähetys käynnissä..."), "</font><br /><br />";
      error_log("Aloitetaan sähköisen tilauksen lähetys: " . $laskurow['tunnus']);

      SendOrder($headers, $params);

      $query = "UPDATE lasku
                SET kirjoitin = 'edi'
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = '{$laskurow['tunnus']}'";
      pupe_query($query, $masterlink);
    }
  }
}

if (isset($hae) and $hae == 'option_edi_kuvaus' and isset($ulos)) {

  if (!isset($sel['sendorder_out_futur'])) $sel['sendorder_out_futur'] = '';

  $ulos .= "<option value='sendorder_out_futur' {$sel['sendorder_out_futur']}>Futur-sendorder</option>";

}

if (!function_exists('GetSupplierAvailability')) {
  function GetSupplierAvailability($headers, $params) {

    if (!CheckHeaders($headers)) return false;

    // GetSupplierAvailability
    $xml = simplexml_load_string("<?xml version='1.0' encoding='ISO-8859-1' ?><Message></Message>");

    $xml->addChild('MessageType', 7);
    $xml->addChild('Action', 'GetSupplierAvailability');
    $xml->addChild('User', '1');

    $supplier = $xml->addChild('Supplier');
    $supplier->addChild('SupplierNumber', $params['suppliernumber']);
    $supplier->addChild('CustomerID', $params['cust_id']);
    $supplier->addChild('Username', $params['username']);
    $supplier->addChild('Password', $params['password']);

    $products = $supplier->addChild('Products');

    if (is_array($params['tuoteno'])) {

      foreach ($params['tuoteno'] as $_tun => $_tuoteno) {
        $product = $products->addChild('Product');
        $product->addChild('ProductCode', $_tuoteno);
        $product->addChild('RequestedQuantity', $params['myytavissa'][$_tun]);
      }
    }
    else {
      $product = $products->addChild('Product');
      $product->addChild('ProductCode', $params['tuoteno']);
      $product->addChild('RequestedQuantity', $params['myytavissa']);
    }

    $headers['data'] = array($xml->asXML());
    $return = pupesoft_rest($headers);

    $xml = simplexml_load_string($return[1]);

    // Jos koodi on 200 eli success
    if ($return[0] == 200 and isset($xml->StatusCode) and $xml->StatusCode == 0) {

      if (is_array($params['tuoteno'])) {

        unset($xml->Supplier->SupplierNumber);

        $params['saldo'] = array();

        foreach ($xml->Supplier->Product as $_product) {
          foreach ($params['tuoteno'] as $_tun => $_tuoteno) {

            if ($_product->ProductCode == $_tuoteno and $_product->RequestedQuantity == $params['myytavissa'][$_tun]) {
              $params['saldo'][$_tun] = (int) $_product->Availability;
            }
          }
        }

      }
      else {
        $params['saldo'] = (int) $xml->Supplier->Product->Availability;
      }

      $tehdas_saldo_lisa = $params['saldo'] == 2 ? ", tehdas_saldo = '{$myytavissa}'" : "";

      if (isset($params['tt_tunnus'])) {
        $query = "UPDATE tuotteen_toimittajat SET
                  tehdas_saldo_paivitetty = now()
                  {$tehdas_saldo_lisa}
                  WHERE yhtio             = '{$params['yhtio']}'
                  AND tunnus              = '{$params['tt_tunnus']}'";
        $upd_res = pupe_query($query);
      }

      $params['tehdas_saldo_paivitetty'] = date("d.m.Y H:i");
    }
    else {
      $params['error'] = true;
      $params['error_msg'] = t("Haku ei onnistunut! Ole yhteydessä IT-tukeen");
    }

    return array(
      'id' => $params['tunnus'],
      'error' => $params['error'],
      'error_msg' => $params['error_msg'],
      'tehdas_saldo_paivitetty' => $params['tehdas_saldo_paivitetty'],
      'saldo' => $params['saldo'],
    );
  }


}

if (!function_exists('GetResponses')) {
  function GetResponses($headers, $yhtio) {
    global $yhtiorow;

    if (!CheckHeaders($headers)) return false;

    // GetResponses
    $xml = simplexml_load_string("<?xml version='1.0' encoding='ISO-8859-1'?><Message></Message>");

    $xml->addChild('MessageType', 7);
    $xml->addChild('Action', 'GetResponses');
    $xml->addChild('User', 'cron');

    $avainsana_result = t_avainsana('SAHKTILTUN', '', '', "'{$yhtio}'");

    if (mysql_num_rows($avainsana_result) > 0) {

      $toimittajat = array();

      while ($row = mysql_fetch_assoc($avainsana_result)) {

        if (!isset($toimittajat[$row['selitetark_5']])) {

          $toimittajat[$row['selitetark_5']]['suppliernumber'] = $row['selitetark_5'];
          $toimittajat[$row['selitetark_5']]['customerid'] = $row['selitetark_2'];
          $toimittajat[$row['selitetark_5']]['username'] = $row['selitetark_3'];
          $toimittajat[$row['selitetark_5']]['password'] = $row['selitetark_4'];
          $toimittajat[$row['selitetark_5']]['toimittajan_tunnus'] = $row['selitetark'];
        }
      }

      if (count($toimittajat) == 0) false;

      foreach ($toimittajat as $toimi) {

        $supplier = $xml->addChild('Supplier');
        $supplier->addChild('SupplierNumber', $toimi['suppliernumber']);
        $supplier->addChild('CustomerID', $toimi['customerid']);
        $supplier->addChild('Username', $toimi['username']);
        $supplier->addChild('Password', $toimi['password']);
      }

      $headers['data'] = array($xml->asXML());
      $return = pupesoft_rest($headers);

      $xml = simplexml_load_string($return[1]);

      return ($return[0] == 200 and isset($xml->StatusCode) and $xml->StatusCode == 0) ? $xml : false;
    }
    else {
      return false;
    }
  }


}

if (!function_exists('GetResponse')) {
  function GetResponse($headers, $responseid) {

    if (!CheckHeaders($headers)) return false;

    $xml = simplexml_load_string("<?xml version='1.0' encoding='ISO-8859-1'?><Message></Message>");

    $xml->addChild('MessageType', 7);
    $xml->addChild('Action', 'GetResponse');
    $xml->addChild('User', 'cron');
    $xml->addChild('ResponseID', $responseid);

    $headers['data'] = array($xml->asXML());
    $return = pupesoft_rest($headers);

    $xml = simplexml_load_string($return[1]);

    return ($return[0] == 200 and isset($xml->StatusCode) and $xml->StatusCode == 0) ? $xml : false;
  }


}

if (!function_exists('DeleteResponse')) {
  function DeleteResponse($headers, $responseid) {

    $xml = simplexml_load_string("<?xml version='1.0' encoding='ISO-8859-1'?><Message></Message>");

    $xml->addChild('MessageType', 7);
    $xml->addChild('Action', 'DeleteResponse');
    $xml->addChild('User', 'cron');
    $xml->addChild('ResponseID', $responseid);

    // $headers['data'] = array($xml->asXML());
    // $return = pupesoft_rest($headers);

    // $xml = simplexml_load_string($return[1]);

    // return ($return[0] == 200 and isset($xml->StatusCode) and $xml->StatusCode == 0) ? true : false;
    return false;
  }


}

if (isset($hae) and $hae == 'nappi' and isset($vastaavat_table) and isset($rivitunnus) and isset($_vastaavat_tunnus)) {
  $vastaavat_table .= "<input type='button' class='tarkista_tehtaan_saldot' id='{$_vastaavat_tunnus}' name='tarkista_tehtaan_saldot' value='".t("Tarkista toimittajan saldot")."' />";
  $vastaavat_table .= "<input type='hidden' class='{$_vastaavat_tunnus}_myytavissa' value='{$_tilattu}' />";
  $vastaavat_table .= "<input type='hidden' class='{$_vastaavat_tunnus}_tuoteno' value='{$_vastaavat_tuoteno}'>";
}

if (isset($hae) and $hae == 'nappi_kaikki' and isset($tilausnumero)) {
  echo "<td class='back'><input type='button' class='tarkista_tehtaan_saldot_kaikki' id='{$tilausnumero}' name='tarkista_tehtaan_saldot_kaikki' value='".t("Tarkista toimittajan saldot")."' /></td>";
}

if (isset($hae) and $hae == 'tarkista_tehtaan_saldot_kaikki' and isset($otunnus)) {

  $data = array(
    'id' => $tunnus,
    'error' => false,
    'error_msg' => '',
    'tehdas_saldo_paivitetty' => '',
    'saldo' => false,
  );

  $query = "SELECT liitostunnus, vanhatunnus
            FROM lasku
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus  = '{$otunnus}'";
  $res = pupe_query($query);
  $row = mysql_fetch_assoc($res);

  // avainsana ON (toimi.yhtio = avainsana.yhtio AND avainsana.laji = 'SAHKTILTUN' AND avainsana.selite = '{$laskurow['vanhatunnus']}' AND toimi.tunnus = avainsana.selitetark)
  $query = "SELECT *
            FROM avainsana
            WHERE yhtio    = '{$kukarow['yhtio']}'
            AND laji       = 'SAHKTILTUN'
            AND selite     = '{$row['vanhatunnus']}'
            AND selitetark = '{$row['liitostunnus']}'";
  $avainsana_res = pupe_query($query);

  if (mysql_num_rows($avainsana_res) > 0) {

    $avainsana_row = mysql_fetch_assoc($avainsana_res);

    $suppliernumber = $avainsana_row['selitetark_5'];
    $cust_id = $avainsana_row['selitetark_2'];
    $username = $avainsana_row['selitetark_3'];
    $password = $avainsana_row['selitetark_4'];

    $headers = array(
      "method" => "POST",
      "url" => $futursoft_rest_http[$kukarow['yhtio']],
      "headers" => array('Accept:text/xml', 'Content-Type: text/xml'),
      "posttype" => "xml",
    );

    if (GetServerInfo($headers)) {

      $tuoteno = $myytavissa = $tunnus = array();

      $query = "SELECT tuoteno, tilkpl, tunnus
                FROM tilausrivi
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND otunnus = '{$otunnus}'";
      $tilausrivires = pupe_query($query);

      while ($tilausrivirow = mysql_fetch_assoc($tilausrivires)) {

        $query = "SELECT tt.*
                  FROM tuote
                  JOIN tuotteen_toimittajat AS tt ON (tt.yhtio = tuote.yhtio AND tt.tuoteno = tuote.tuoteno AND tt.liitostunnus = '{$row['liitostunnus']}')
                  WHERE tuote.yhtio = '{$kukarow['yhtio']}'
                  AND tuote.tuoteno = '{$tilausrivirow['tuoteno']}'";
        $chk_res = pupe_query($query);
        $chk_row = mysql_fetch_assoc($chk_res);

        $tuoteno[$tilausrivirow['tunnus']] = $chk_row['toim_tuoteno'];
        $myytavissa[$tilausrivirow['tunnus']] = (int) $tilausrivirow['tilkpl'];
        $tunnus[$tilausrivirow['tunnus']] = $tilausrivirow['tunnus'];
      }

      $params = array(
        'suppliernumber' => $suppliernumber,
        'cust_id' => $cust_id,
        'username' => $username,
        'password' => $password,
        'tuoteno' => $tuoteno,
        'myytavissa' => $myytavissa,
        'yhtio' => $kukarow['yhtio'],
        'tunnus' => $tunnus,
        'saldo' => false,
        'error' => false,
        'error_msg' => '',
      );

      $data = GetSupplierAvailability($headers, $params);
    }
    else {
      $data['error'] = true;
      $data['error_msg'] = t("Haku ei onnistunut! Ole yhteydessä IT-tukeen");
    }
  }
  else {
    $data['error'] = true;
    $data['error_msg'] = t("Haku ei onnistunut! Ole yhteydessä IT-tukeen. Tunnuksia ei löytynyt!");
  }
}

if (isset($hae) and $hae == 'tarkista_tehtaan_saldot' and isset($tunnus) and is_int($tunnus) and isset($cust_id) and isset($username) and isset($password) and isset($tt_tunnus) and is_int($tt_tunnus) and isset($futursoft_rest_http) and is_array($futursoft_rest_http) and isset($futursoft_rest_http[$kukarow['yhtio']])) {

  $headers = array(
    "method" => "POST",
    "url" => $futursoft_rest_http[$kukarow['yhtio']],
    "headers" => array('Accept:text/xml', 'Content-Type: text/xml'),
    "posttype" => "xml",
  );

  $data = array(
    'id' => $tunnus,
    'error' => false,
    'error_msg' => '',
    'tehdas_saldo_paivitetty' => '',
    'saldo' => false,
  );

  if (GetServerInfo($headers)) {

    $params = array(
      'suppliernumber' => $suppliernumber,
      'cust_id' => $cust_id,
      'username' => $username,
      'password' => $password,
      'tuoteno' => $tuoteno,
      'myytavissa' => $myytavissa,
      'yhtio' => $kukarow['yhtio'],
      'tunnus' => $tunnus,
      'tt_tunnus' => $tt_tunnus,
      'saldo' => false,
      'error' => false,
      'error_msg' => '',
    );

    $data = GetSupplierAvailability($headers, $params);
  }
  else {
    $data['error'] = true;
    $data['error_msg'] = t("Haku ei onnistunut! Ole yhteydessä IT-tukeen");
  }
}
