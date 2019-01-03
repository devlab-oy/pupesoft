<?php

class ViidakkoStoreVariaatiot {
  private $apiurl = "";
  private $token = "";
  private $pupesoft_all_variations = array();
  protected $logger = null;

  public function __construct($url, $token, $log_file) {
    $this->apiurl = $url;
    $this->token = $token;

    $this->logger = new Logger($log_file);
  }

  public function check_variations() {
    $this->logger->log('---------Tarkistetaan onko variaatio jo kaupassa---------');
    echo "\n---------Tarkistetaan onko variaatio jo kaupassa---------\n";

    $pupesoft_variations = $this->pupesoft_all_variations;
    $total = count($pupesoft_variations);
    $current = 0;

    foreach ($pupesoft_variations as $variationcode => $data) {

      if (empty($data['group_id'])) {

        // POST
        // Add new variation group
        $group_id = $this->insert_variation_group($variationcode, $data);
        // todn‰k tarvitaan group_id variaatioiden lis‰ykseen
      }

      if (empty($group_id)) continue;

      if (empty($data['variation_id'])) {

        // POST
        // Add new variation to specified product
        $this->insert_variation($variationcode, $data);
      }
      else {

        // PUT
        // Edit specified variation
        $this->edit_variation($variationcode, $data);
      }
    }
  }

  public function edit_variation($variationcode, $data) {
    $url = $this->apiurl."/variations/".$data["variation_id"];

    $id = $data['variation_id'];

    unset($data['variation_id']);
    unset($data['properties']);
    unset($data['href']);
    unset($data['variation_group_array']);
    unset($data['variation_group_array']);

    $data_json = json_encode($data);
    echo "\n---------UPDATETAAN---------\n";
    echo "<pre>",var_dump($variation);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json', 'X-Auth-Token: '.$this->token));
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    $response_array = json_decode($response);

    echo "\n update\n";
    echo "\nvar_dump variations edit:<pre>",var_dump($response);

    if (isset($response_array->items[0]->id) and $response_array->items[0]->id == $id) {
      // response ok/fail viel‰ t‰h‰n
      $this->logger->log("--> variaatiokoodin {$data["variation_code"]} variaatio {$data["erp_id"]} p‰ivitetty");
      echo "\n onnistuneesti p‰ivitetty variaatiokoodin {$data["variation_code"]} variaatio {$data["erp_id"]}";
    }
    else {
      $this->logger->log("--> Jokin meni pieleen variaatiokoodin {$data["variation_code"]} variaation {$data["erp_id"]} kanssa");
      echo "\n Jokin meni pieleen variaatiokoodin {$data["variation_code"]} variaation {$data["erp_id"]} kanssa";
    }
  }

  public function insert_variation($variationcode, $data) {
    $url = $this->apiurl."/products/".$data['product_id']."/variations";

    $id = $data['variation_id'];

    // n‰‰ pois
    unset($data['variation_id']);
    unset($data['variation_group_array']);

    echo "<pre>",var_dump($variation);

    $data_json = json_encode($variation);
    echo "\n---------INSERT÷IDƒƒN---------\n";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json', 'X-Auth-Token: '.$this->token));
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    echo "\n insert\n";
    echo "\n\nvar_dump insert variations:<pre>",var_dump($response);

    $response_array = json_decode($response);

    if (isset($response_array->items[0]->properties->id) and !empty($response_array->items[0]->properties->id)) {
      $this->logger->log("--> variaatio {$data["erp_id"]} lis‰tty");
      $this->update_id($id, $response_array->items[0]);
      echo "\nIIDEE LISƒTTY!!!\n";
    }
    else {
      $this->logger->log("--> variaatio {$data["erp_id"]} lis‰‰minen ep‰onnistui!");
      $this->logger->log("syy: {$response_array->message}");
    }
  }

  public function insert_variation_group($variationcode, $data) {
    $url = $this->apiurl."/variationgroups";

    echo "<pre>",var_dump($data['variation_group_array']);

    $id = $data['variation_id'];

    $data_json = json_encode($data['variation_group_array']);
    echo "\n---------INSERT÷IDƒƒN---------\n";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json', 'X-Auth-Token: '.$this->token));
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    echo "\n insert\n";
    echo "\n\nvar_dump insert variationgroups:<pre>",var_dump($response);

    $response_array = json_decode($response);

    if (isset($response_array->items[0]->id) and !empty($response_array->items[0]->id)) {
      $this->logger->log("--> variaatiogroup {$variationcode} lis‰tty");
      $id = $this->update_group_id($id, $data['erp_id'], $response_array->items[0]->id);
      echo "\nIIDEE LISƒTTY!!! $id\n";
      return $id;
    }
    else {
      $this->logger->log("--> variaatiogroup {$variationcode} lis‰‰minen ep‰onnistui!");
      $this->logger->log("syy: {$response_array->message}");
      return "";
    }
  }

  public function set_all_variations($value) {
    if (is_array($value)) {
      $this->pupesoft_all_variations = $value;
    }
  }

  public function update_id($id, $viidakko_product) {
    global $yhtiorow;

    if ($id == "") {
      $query = "  INSERT INTO tuotteen_avainsanat SET
                  yhtio       = '{$yhtiorow['yhtio']}',
                  tuoteno     = '{$viidakko_product->erp_id}',
                  laji        = 'viidakko_variation_id',
                  selite      = '{$viidakko_product->properties->id}',
                  laatija     = 'viidakkostore',
                  luontiaika  = now() ON DUPLICATE KEY UPDATE
                  muuttaja    = 'viidakkostore',
                  muutospvm   = now()";
      $insert_res = pupe_query($query);
    }
    elseif ($id != $viidakko_product->properties->id) {
      $query = "  UPDATE tuotteen_avainsanat SET
                  yhtio       = '{$yhtiorow['yhtio']}',
                  tuoteno     = '{$viidakko_product->erp_id}',
                  laji        = 'viidakko_variation_id',
                  selite      = '{$viidakko_product->properties->id}',
                  muuttaja    = 'viidakkostore',
                  muutospvm   = now()";
      $update_res = pupe_query($query);
    }
  }

  public function update_group_id($id, $tuoteno, $viidakko_id) {
    global $yhtiorow;

    if ($id == "") {
      $query = "  INSERT INTO tuotteen_avainsanat SET
                  yhtio       = '{$yhtiorow['yhtio']}',
                  tuoteno     = '{$tuoteno}',
                  laji        = 'parametri_variaatio'
                  selitetark  = '{$viidakko_id}',
                  laatija     = 'viidakkostore',
                  luontiaika  = now() ON DUPLICATE KEY UPDATE
                  muuttaja    = 'viidakkostore',
                  muutospvm   = now()";
      $insert_res = pupe_query($query);
    }
    elseif ($id != $viidakko_id) {
      $query = "  UPDATE tuotteen_avainsanat SET
                  yhtio       = '{$yhtiorow['yhtio']}',
                  tuoteno     = '{$tuoteno}',
                  laji        = 'parametri_variaatio',
                  selitetark  = '{$viidakko_id}',
                  muuttaja    = 'viidakkostore',
                  muutospvm   = now()";
      $update_res = pupe_query($query);
    }

    return $viidakko_id;
  }
}
