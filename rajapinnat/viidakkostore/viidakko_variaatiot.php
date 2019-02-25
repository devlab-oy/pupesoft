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

    #echo "\n---------Tarkistetaan onko variaatio jo kaupassa---------\n";

    $pupesoft_variations = $this->pupesoft_all_variations;
    $total = count($pupesoft_variations);
    $current = 0;

    foreach ($pupesoft_variations as $variationcode => $data) {

      if (empty($data[0]['group_id'])) {

        #echo "\n\n if empty group_id\n";
        #echo "<pre>",var_dump($variationcode);
        #echo "<pre>",var_dump($data[0]);

        // POST
        // Add new variation group
        $data[0]['group_id'] = $this->insert_variation_group($variationcode, $data);
      }

      if (empty($data[0]['group_id'])) {
        continue;
      }
      else {

        // luupataan variaatiot l‰pi
        foreach ($data as $variaatio) {

          // siirret‰‰n group_id kaikille variaatoille
          $variaatio['group_id'] = (float) $data[0]['group_id'];

          if (empty($variaatio['variation_id'])) {

            #echo "\n\n if empty variation_id\n";

            // POST
            // Add new variation to specified product
            $this->insert_variation($variationcode, $variaatio);
          }
          else {

            #echo "\n\n if not empty variation_id\n";

            // PUT
            // Edit specified variation
            $this->edit_variation($variationcode, $variaatio);
          }
        }
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

    $data_json = json_encode($data);

    #echo "\n---------UPDATETAAN---------\n";
    #echo "<pre>",var_dump($data);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json', 'X-Auth-Token: '.$this->token));
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    $response_array = json_decode($response);

    #echo "\n update\n";
    #echo "\nvar_dump variations edit:",var_dump($response_array);

    if (isset($response_array->items[0]->id) and $response_array->items[0]->id == $id) {
      $this->logger->log("--> variaatiokoodin {$data["variation_code"]} variaatio {$data["erp_id"]} p‰ivitetty");
      $this->update_id($id, $response_array->items[0]);
      #echo "\n onnistuneesti p‰ivitetty variaatiokoodin {$data["variation_code"]} variaatio {$data["erp_id"]}";
    }
    else {
      $this->logger->log("--> Jokin meni pieleen variaatiokoodin {$data["variation_code"]} variaation {$data["erp_id"]} kanssa");
      #echo "\n Jokin meni pieleen variaatiokoodin {$data["variation_code"]} variaation {$data["erp_id"]} kanssa";
    }
  }

  public function insert_variation($variationcode, $data) {
    $url = $this->apiurl."/products/".$data['product_id']."/variations";

    $id = $data['variation_id'];

    // n‰‰ pois
    unset($data['variation_id']);
    unset($data['variation_group_array']);

    #echo "<pre>",var_dump($data);

    $data_json = json_encode($data);
    #echo "\n---------INSERT÷IDƒƒN---------\n";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json', 'X-Auth-Token: '.$this->token));
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    #echo "\n insert\n";

    $response_array = json_decode($response);

    #echo "\n\nvar_dump insert variations:<pre>",var_dump($response_array);

    if (isset($response_array->items[0]->id) and !empty($response_array->items[0]->id)) {
      $this->logger->log("--> variaatio {$data["erp_id"]} lis‰tty");
      $this->update_id("", $response_array->items[0]);
      #echo "\nIIDEE LISƒTTY!!!\n";
    }
    else {
      $this->logger->log("--> variaatio {$data["erp_id"]} lis‰‰minen ep‰onnistui!");
      $this->logger->log("syy: {$response_array->message}");
      #echo "\nvariaation {$data["erp_id"]} lis‰‰minen ep‰onnistui. syy: {$response_array->message}\n";
    }
  }

  public function insert_variation_group($variationcode, $data) {
    $url = $this->apiurl."/variationgroups";

    #echo "<pre>",var_dump($data[0]['variation_group_array']);

    $data_json = json_encode($data[0]['variation_group_array']);

    #echo "\n---------INSERT÷IDƒƒN---------\n";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json', 'X-Auth-Token: '.$this->token));
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    #echo "\n insert\n";

    $response_array = json_decode($response);

    #echo "\n\nvar_dump insert variationgroups:<pre>",var_dump($response_array);

    if (isset($response_array->items[0]->id) and !empty($response_array->items[0]->id)) {

      $this->logger->log("--> variaatiogroup {$variationcode} lis‰tty");

      foreach ($data as $variaatiodata) {
        $this->update_group_id($variaatiodata['erp_id'], $response_array->items[0]->id);
        #echo "\nIIDEE LISƒTTY!!! {$response_array->items[0]->id} tuottelle {$variaatiodata['erp_id']}\n";
      }

      return $response_array->items[0]->id;
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
                  selite      = '{$viidakko_product->id}',
                  laatija     = 'viidakkostore',
                  luontiaika  = now() ON DUPLICATE KEY UPDATE
                  muuttaja    = 'viidakkostore',
                  muutospvm   = now()";
      $insert_res = pupe_query($query);

      #echo "\n\ninsertˆitiin update_id!\n\n";
    }
    elseif ($id != $viidakko_product->id) {
      $query = "  UPDATE tuotteen_avainsanat SET
                  selite      = '{$viidakko_product->id}',
                  muuttaja    = 'viidakkostore',
                  muutospvm   = now()
                  WHERE
                  yhtio       = '{$yhtiorow['yhtio']}' AND
                  tuoteno     = '{$viidakko_product->erp_id}' AND
                  laji        = 'viidakko_variation_id'";
      $update_res = pupe_query($query);

      #echo "\n\updatettiin update_id!\n\n";
    }
  }

  public function update_group_id($tuoteno, $viidakko_id) { //todo
    global $yhtiorow;

    $query = "  UPDATE tuotteen_avainsanat SET
                selitetark  = '{$viidakko_id}',
                muuttaja    = 'viidakkostore',
                muutospvm   = now()
                WHERE
                yhtio       = '{$yhtiorow['yhtio']}' AND
                tuoteno     = '{$tuoteno}' AND
                laji        = 'parametri_variaatio'";
    $update_res = pupe_query($query);
  }
}
