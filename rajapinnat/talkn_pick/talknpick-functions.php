<?php

if (!function_exists('talknpick_createpictask')) {
  function talknpick_createpictask($otunnus) {
    global $kukarow, $yhtiorow, $talknpick;

    $query = "SELECT *,
              varasto AS otsikon_varasto,
              toimaika AS lasku_toimaika,
              if (hyvaksynnanmuutos = '', 'X', hyvaksynnanmuutos) prioriteetti
              FROM lasku
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = '{$otunnus}'";
    $laskures = pupe_query($query);

    if (mysql_num_rows($laskures) == 0) {
      pupesoft_log('talknpick_create_task', "Yhtään riviä ei löytynyt tilaukselle {$otunnus}. Sanoman luonti epäonnistui.");
      return false;
    }

    $laskurow = mysql_fetch_assoc($laskures);
    $varastorow = hae_varasto($laskurow['otsikon_varasto']);

    if ($varastorow['ulkoinen_jarjestelma'] != "D") {
      pupesoft_log('talknpick_create_task', "Tilauksen {$otunnus} varaston ulkoinen järjestelmä oli virheellinen.");
      return false;
    }

    # Säädetaan muuttujia kuntoon
    $rec_cust_name = trim($laskurow['toim_nimi'].' '.$laskurow['toim_nimitark']);

    if (empty($rec_cust_name)) {
      $rec_cust_name = trim($laskurow['nimi'].' '.$laskurow['nimitark']);
    }

    $prioriteettinro = (int) t_avainsana("ASIAKASLUOKKA", "", " and avainsana.selite='{$laskurow['prioriteetti']}'", "", "", "selitetark_3");

    if ($laskurow['toimaika'] == "0000-00-00") {
      if ($laskurow['kerayspvm'] != "0000-00-00 00:00:00") {
        $laskurow['toimaika'] = $laskurow['kerayspvm'];
      }
      else {
        $laskurow['toimaika'] = date("Y-m-d");
      }
    }

    $taksnumpperi = mt_rand(1,10000000);

    # Rakennetaan JSON
    $task = array();
    $task["taskNumber"] = $taksnumpperi;
    $task["taskType"] = "PIC";
    $task["status"] = "new";
    $task["priority"] = $prioriteettinro;
    $task["taskAdditionalInfo"] = xml_cleanstring($laskurow['sisviesti2'], 255);
    $task["customerName"] = xml_cleanstring($rec_cust_name, 255);
    #$task["route"] = "";
    $task["deliveryDate"] = $laskurow['toimaika'];
    $task["warehouseID"] = 2;
    $task["companyID"] = 2;

    // Luodaan Taski
    $parameters = array(
      "method"    => "POST",
      "data"      => $task,
      "url"       => $talknpick["host"]."/tasks",
      "auth_user" => $talknpick["user"],
      "auth_pass" => $talknpick["pass"]
    );

    list($taskcode, $taskresponse) = pupesoft_rest($parameters);

    echo "\nTASK:\n";
    var_dump($taskcode);
    var_dump(json_encode($taskresponse));

    // Jos taskin tekeminen epäonnistuu
    if ($taskcode != 200) {
      return FALSE;
    }

    $taskID = $taskresponse["taskID"];

    $orderType = "Myyntitilaus";

    if ($laskurow['tila'] == "G") {
      $orderType = "Varastosiirto";
    }

    # Rakennetaan JSON
    $order = array();
    $order["orderNumber"] = $otunnus;
    $order["priority"] = $prioriteettinro;
    $order["zone"] = "";
    $order["orderType"] = $orderType;
    $order["companyID"] = 2;
    $order["taskID"] = $taskID;

    // Luodaan Orderi
    $parameters = array(
      "method"    => "POST",
      "data"      => $order,
      "url"       => $talknpick["host"]."/orders",
      "auth_user" => $talknpick["user"],
      "auth_pass" => $talknpick["pass"]
    );

    list($ordercode, $orderresponse) = pupesoft_rest($parameters);

    echo "\nORDER:\n";
    var_dump($ordercode);
    var_dump($orderresponse);

    // Jos orderin tekeminen epäonnistuu
    if ($ordercode != 200) {
      return FALSE;
    }

    $orderID = $orderresponse["orderID"];

    $select_lisa       = "";
    $where_lisa        = "";
    $lisa1             = "";
    $pjat_sortlisa     = "";

    // keräyslistalle ei oletuksena tulosteta saldottomia tuotteita
    if ($yhtiorow["kerataanko_saldottomat"] == '') {
      $lisa1 = " and tuote.ei_saldoa = '' ";
    }

    if ($laskurow["tila"] == "V") {
      $sorttauskentta = generoi_sorttauskentta($yhtiorow["valmistus_kerayslistan_jarjestys"]);
      $order_sorttaus = $yhtiorow["valmistus_kerayslistan_jarjestys_suunta"];

      if ($yhtiorow["valmistus_kerayslistan_palvelutjatuottet"] == "E") $pjat_sortlisa = "tuotetyyppi,";

      // Summataan rivit yhteen (HUOM: unohdetaan kaikki perheet!)
      if ($yhtiorow["valmistus_kerayslistan_jarjestys"] == "S") {
        $select_lisa = "sum(tilausrivi.kpl) kpl, sum(tilausrivi.tilkpl) tilkpl, sum(tilausrivi.varattu) varattu, sum(tilausrivi.jt) jt, '' perheid, '' perheid2, ";
        $where_lisa = "GROUP BY tilausrivi.tuoteno, tilausrivi.hyllyalue, tilausrivi.hyllyvali, tilausrivi.hyllyalue, tilausrivi.hyllynro";
      }
    }
    else {
      $sorttauskentta = generoi_sorttauskentta($yhtiorow["kerayslistan_jarjestys"]);
      $order_sorttaus = $yhtiorow["kerayslistan_jarjestys_suunta"];

      if ($yhtiorow["kerayslistan_palvelutjatuottet"] == "E") $pjat_sortlisa = "tuotetyyppi,";

      // Summataan rivit yhteen (HUOM: unohdetaan kaikki perheet!)
      if ($yhtiorow["kerayslistan_jarjestys"] == "S") {
        $select_lisa = "sum(tilausrivi.kpl) kpl, sum(tilausrivi.tilkpl) tilkpl, sum(tilausrivi.varattu) varattu, sum(tilausrivi.jt) jt, '' perheid, '' perheid2, ";
        $where_lisa = "GROUP BY tilausrivi.tuoteno, tilausrivi.hyllyalue, tilausrivi.hyllyvali, tilausrivi.hyllyalue, tilausrivi.hyllynro";
      }
    }

    // keräyslistan rivit
    $query = "SELECT tilausrivi.*,
              $select_lisa
              $sorttauskentta,
              if (tuote.tuotetyyppi='K','2 Työt','1 Muut') tuotetyyppi,
              if (tuote.myyntihinta_maara=0, 1, tuote.myyntihinta_maara) myyntihinta_maara,
              tuote.sarjanumeroseuranta,
              tuote.eankoodi,
              (tilausrivi.kpl+tilausrivi.varattu) kpl,
              ta.selite productid
              FROM tilausrivi
              JOIN tuote ON (tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno)
              LEFT JOIN tuotteen_avainsanat AS ta ON (ta.yhtio = tuote.yhtio AND ta.tuoteno = tuote.tuoteno AND ta.laji = 'synkronointi')
              WHERE tilausrivi.otunnus = $otunnus
              and tilausrivi.yhtio     = '$kukarow[yhtio]'
              and tilausrivi.tyyppi   != 'D'
              $lisa1
              $where_lisa
              ORDER BY $pjat_sortlisa sorttauskentta $order_sorttaus, tilausrivi.tunnus";
    $riresult = pupe_query($query);

    $lasklines = array();
    $perheid = 0;
    $_line_i = 1;

    while ($rivirow = mysql_fetch_assoc($riresult)) {
      // Varmistetaan, että tuote löytyy, jos ei löydy niin perustetaan se
      if (empty($rivirow['productid'])) {
        $query = "SELECT tuote.*,
                  ta.selite AS synkronointi, ta.tunnus AS ta_tunnus
                  FROM tuote
                  LEFT JOIN tuotteen_avainsanat AS ta ON (ta.yhtio = tuote.yhtio AND ta.tuoteno = tuote.tuoteno AND ta.laji = 'synkronointi')
                  WHERE tuote.yhtio = '{$kukarow['yhtio']}'
                  AND tuote.tuoteno = '{$rivirow['tuoteno']}'";
        $res = pupe_query($query);
        $tuoterow = mysql_fetch_assoc($res);

        if (empty($tuoterow["synkronointi"])) {
          list($tuotecode, $tuoteresponse) = talknpick_create_product($tuoterow);
          echo "\nTUOTE:\n";
          var_dump($tuotecode);
          var_dump($tuoteresponse);

          $rivirow["productid"] = $tuoteresponse["productID"];
        }
        else {
          $rivirow['productid'] = $tuoterow["synkronointi"];
        }
      }

      // Laitetaan kappalemäärät kuntoon
      $rivirow['kpl'] = $rivirow['var'] == 'J' ? 0 : $rivirow['kpl'];

      list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($rivirow["tuoteno"], '', '', '', $rivirow["hyllyalue"], $rivirow["hyllynro"], $rivirow["hyllyvali"], $rivirow["hyllytaso"], '', '', '');

      $rivirow["perhe_kommentti1"] = "";
      $rivirow["perhe_kommentti2"] = "";

      // Info tuoteperheestä
      if ($rivirow["perheid"] > 0 and $perheid != $rivirow["perheid"]) {
        $numrows = 0;

        $query = "SELECT vanhatunnus
                  FROM lasku
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tunnus  = '$rivirow[otunnus]'";
        $vtunres = pupe_query($query);
        $vtunrow = mysql_fetch_assoc($vtunres);

        if ($vtunrow["vanhatunnus"] != 0) {
          $query = "SELECT GROUP_CONCAT(distinct tunnus SEPARATOR ',') tunnukset
                    FROM lasku use index (yhtio_vanhatunnus)
                    WHERE yhtio     = '$kukarow[yhtio]'
                    and vanhatunnus = '$vtunrow[vanhatunnus]'";
          $perheresult = pupe_query($query);
          $numrows = mysql_num_rows($perheresult);
        }

        if ($numrows == 0 or $vtunrow["vanhatunnus"] == 0) {
          $query = "SELECT GROUP_CONCAT(distinct tunnus SEPARATOR ',') tunnukset
                    FROM lasku use index (PRIMARY)
                    WHERE yhtio = '$kukarow[yhtio]'
                    and tunnus  = '$rivirow[otunnus]'";
          $perheresult = pupe_query($query);
        }

        if (mysql_num_rows($perheresult) > 0) {
          $perherow = mysql_fetch_assoc($perheresult);

          $query = "SELECT distinct tilausrivi.tuoteno, tilausrivi.nimitys, varattu
                    FROM tilausrivi
                    JOIN tuote tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
                    WHERE tilausrivi.yhtio  = '$kukarow[yhtio]'
                    and tilausrivi.otunnus  in ($perherow[tunnukset])
                    AND tilausrivi.tyyppi  != 'D'
                    and tilausrivi.var     != 'O'
                    and tilausrivi.perheid  = '$rivirow[perheid]'
                    ORDER BY tilausrivi.tunnus";
          $perheresult = pupe_query($query);

          if (mysql_num_rows($perheresult) > 1) {
            $perherow = mysql_fetch_assoc($perheresult);

            $rivirow["perhe_kommentti1"] = $perherow["tuoteno"]." (".$rivirow["varattu"].") ".$perherow["nimitys"];

            while ($perherow = mysql_fetch_assoc($perheresult)) {
              $rivirow["perhe_kommentti2"] .= strtoupper($perherow["tuoteno"]).", ";
            }

            $rivirow["perhe_kommentti2"] = substr($rivirow["perhe_kommentti2"], 0, -2);
          }
        }
      }

      if (trim($rivirow["perhe_kommentti1"]) != '') {
        $rivirow['kommentti'] .= " Tuoteperhe: ".$rivirow["perhe_kommentti1"]." ";

        if (trim($rivirow["perhe_kommentti2"]) != '') {
          $rivirow['kommentti'] = " Sisältää tuotteet: ".$rivirow["perhe_kommentti2"]." ";
        }
      }

      $taskline = array();
      $taskline["lineNumber"] = $_line_i;
      $taskline["orderLineNumber"] = $rivirow['tunnus'];
      $taskline["lineCode"] = xml_cleanstring($rivirow['tuoteno'], 255);
      $taskline["bin"] = xml_cleanstring($rivirow['hyllyalue']." ".$rivirow['hyllynro']." ".$rivirow['hyllyvali']." ".$rivirow['hyllytaso'], 255);
      #$taskline["binTo"] = "";
      $taskline["status"] = "new";
      #$taskline["lot"] = "";
      $taskline["taskAmount"] = $rivirow['kpl'];
      $taskline["uom"] = xml_cleanstring($rivirow['yksikko'], 255);
      $taskline["lineAdditionalInfo"] = xml_cleanstring($rivirow['kommentti'], 255);
      $taskline["orderedTotal"] = $rivirow['varattu'];
      $taskline["binTotalBeforeTask"] = $hyllyssa;
      #$taskline["checkMethod"] = "";
      #$taskline["binValidationMethod"] = "";
      #$taskline["binValidationDigits"] = "";
      #$taskline["containerNumber"] = "";
      $taskline["companyID"] = 2;
      $taskline["taskID"] = $taskID;
      #$taskline["product"] = "";
      $taskline["orderID"] = $orderID;
      $taskline["productID"] = $rivirow['productid'];

      $_line_i++;
      $perheid  = $rivirow["perheid"];
      $lasklines[] = $taskline;

      $parameters = array(
        "method"    => "POST",
        "data"      => $taskline,
        "url"       => $talknpick["host"]."/taskLines",
        "auth_user" => $talknpick["user"],
        "auth_pass" => $talknpick["pass"]
      );

      list($linecode, $lineresponse) = pupesoft_rest($parameters);

      echo "\nLINE:\n";
      var_dump($linecode);
      var_dump($lineresponse);
    }

    return array($taskcode, $taskresponse);
  }
}

if (!function_exists('talknpick_create_product')) {
  function talknpick_create_product($tuoterow) {
    global $kukarow, $yhtiorow, $talknpick;

    # Rakennetaan JSON
    $product = array();
    $product["productCode"] = xml_cleanstring($tuoterow["tuoteno"], 255);
    $product["name"] = xml_cleanstring($tuoterow["nimitys"], 255);
    $product["productGroup"] = xml_cleanstring($tuoterow["try"], 255);
    $product["weight"] = round($tuoterow["tuotemassa"]*1000);
    $product["uom"] = xml_cleanstring($tuoterow['yksikko'], 255);
    #$product["volume"] = 1;
    #$product["imageUrl"] = "";
    $product["companyID"] = 2;

    $parameters = array(
      "method"    => "POST",
      "data"      => $product,
      "url"       => $talknpick["host"]."/products",
      "auth_user" => $talknpick["user"],
      "auth_pass" => $talknpick["pass"]
    );

    list($code, $response) = pupesoft_rest($parameters);

    $productID = $response["productID"];

    if ($code == 200 and is_null($tuoterow['synkronointi'])) {
      $query = "INSERT INTO tuotteen_avainsanat SET
                yhtio      = '{$kukarow['yhtio']}',
                tuoteno    = '{$tuoterow['tuoteno']}',
                kieli      = '{$yhtiorow['kieli']}',
                laji       = 'synkronointi',
                selite     = '$productID',
                laatija    = '{$kukarow['kuka']}',
                luontiaika = now(),
                muutospvm  = now(),
                muuttaja   = '{$kukarow['kuka']}'";
      pupe_query($query);
    }
    elseif ($code == 200) {
      $query = "UPDATE tuotteen_avainsanat SET
                selite      = '$productID'
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tuoteno = '{$tuoterow['tuoteno']}'
                AND laji    = 'synkronointi'";
      pupe_query($query);
    }

    return array($code, $response);
  }
}

if (!function_exists('talknpick_task_status')) {
  function talknpick_task_status($taskid) {
    global $kukarow, $yhtiorow, $talknpick;

    $parameters = array(
      "method"    => "POST",
      "data"      => array(),
      "url"       => $talknpick["host"],
      "auth_user" => $talknpick["user"],
      "auth_pass" => $talknpick["pass"],
      "headers"   => array()
    );

    return pupesoft_rest($parameters);
  }
}

if (!function_exists('talknpick_list_companies')) {
  function talknpick_list_companies() {
    global $kukarow, $yhtiorow, $talknpick;

    $parameters = array(
      "method"    => "GET",
      "data"      => array(),
      "url"       => $talknpick["host"]."/companies",
      "auth_user" => $talknpick["user"],
      "auth_pass" => $talknpick["pass"]
    );

    return pupesoft_rest($parameters);
  }
}

if (!function_exists('talknpick_list_warehouses')) {
  function talknpick_list_warehouses() {
    global $kukarow, $yhtiorow, $talknpick;

    $parameters = array(
      "method"    => "GET",
      "data"      => array(),
      "url"       => $talknpick["host"]."/warehouses",
      "auth_user" => $talknpick["user"],
      "auth_pass" => $talknpick["pass"]
    );

    return pupesoft_rest($parameters);
  }
}

if (!function_exists('talknpick_delete_all_tasks')) {
  function talknpick_delete_all_tasks() {
    global $kukarow, $yhtiorow, $talknpick;

    $parameters = array(
      "method"    => "GET",
      "data"      => array(),
      "url"       => $talknpick["host"]."/tasks",
      "auth_user" => $talknpick["user"],
      "auth_pass" => $talknpick["pass"]
    );

    list($code, $response) = pupesoft_rest($parameters);

    foreach ($response as $task) {

      $parameters = array(
        "method"    => "DELETE",
        "data"      => array("taskID" => $task["taskID"]),
        "url"       => $talknpick["host"]."/tasks/".$task["taskID"],
        "auth_user" => $talknpick["user"],
        "auth_pass" => $talknpick["pass"]
      );

      list($code, $del_response) = pupesoft_rest($parameters);

      var_dump($del_response)."\n";
    }
  }
}

if (!function_exists('talknpick_delete_all_orders')) {
  function talknpick_delete_all_orders() {
    global $kukarow, $yhtiorow, $talknpick;

    $parameters = array(
      "method"    => "GET",
      "data"      => array(),
      "url"       => $talknpick["host"]."/orders",
      "auth_user" => $talknpick["user"],
      "auth_pass" => $talknpick["pass"]
    );

    list($code, $response) = pupesoft_rest($parameters);

    foreach ($response as $order) {

      $parameters = array(
        "method"    => "DELETE",
        "data"      => array("taskID" => $order["orderID"]),
        "url"       => $talknpick["host"]."/orders/".$order["orderID"],
        "auth_user" => $talknpick["user"],
        "auth_pass" => $talknpick["pass"]
      );

      list($code, $del_response) = pupesoft_rest($parameters);

      var_dump($del_response)."\n";
    }
  }
}

if (!function_exists('talknpick_delete_all_products')) {
  function talknpick_delete_all_products() {
    global $kukarow, $yhtiorow, $talknpick;

    function list_all_products () {
      global $kukarow, $yhtiorow, $talknpick;

      $parameters = array(
        "method"    => "GET",
        "data"      => array(),
        "url"       => $talknpick["host"]."/products",
        "auth_user" => $talknpick["user"],
        "auth_pass" => $talknpick["pass"]
      );

      return pupesoft_rest($parameters);
    }

    $kala = 1;

    while (list($code, $response) = list_all_products()) {

      if (count($response) == 0) {
        return;
      }

      foreach ($response as $product) {
        $parameters = array(
          "method"    => "DELETE",
          "data"      => array("productID" => $product["productID"]),
          "url"       => $talknpick["host"]."/products/".$product["productID"],
          "auth_user" => $talknpick["user"],
          "auth_pass" => $talknpick["pass"]
        );

        list($code, $del_response) = pupesoft_rest($parameters);

        echo "$kala ";
        var_dump($del_response)."\n";
        $kala++;
      }
    }
  }
}






