<?php

class ViidakkoStoreTuotteet {
  private $apiurl = "";
  private $token = "";
  private $pupesoft_all_products = array();
  protected $logger = null;

  public function __construct($url, $token, $log_file) {
    $this->apiurl = $url;
    $this->token = $token;

    $this->logger = new Logger($log_file);
  }

  public function check_products() {

    $this->logger->log('---------Tarkistetaan onko tuote jo kaupassa---------');
    #echo "\n---------Tarkistetaan onko tuote jo kaupassa---------\n";

    $pupesoft_products = $this->pupesoft_all_products;
    $total = count($pupesoft_products);
    $current = 0;

    foreach ($pupesoft_products as $product) {

      $url = $this->apiurl."/products?codes[]=".$product["product_code"];
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'X-Auth-Token: '.$this->token));
      curl_setopt($ch, CURLOPT_HEADER, FALSE);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

      $response = curl_exec($ch);
      curl_close($ch);

      $current++;
      $response_array = json_decode($response);

      #echo "\n check\n";
      #echo "\nvar_dump products:",var_dump($response_array);

      if (isset($response_array->items[0]->id) and !empty($response_array->items[0]->id)) {

        #echo "\n---------200---------\n";

        $this->logger->log("[{$current}/{$total}] tuote {$product["product_code"]} löytyy kaupasta");

        // lets check store product id
        // if original_product_code then us it
        if (!empty($product['original_product_code'])) {
          $product_code = $product['original_product_code'];
        }
        else {
          $product_code = $product['product_code'];
        }

        $this->update_id($product['id'], $product_code, $response_array->items[0]->id);

        // lets update product with correct id
        $product['id'] = $response_array->items[0]->id;

        #echo "\nvar_dump iidee:<pre>",var_dump($response_array->items[0]);
        #echo "\nvar_dump products:<pre>",var_dump($product);

        $this->update_product($product);
      }
      elseif (isset($response_array->items) and !isset($response_array->items[0])) {
        #echo "\n---------404---------\n";
        $this->logger->log("[{$current}/{$total}] tuote {$product["product_code"]} ei löydy kaupasta");
        // lets insert product
        $this->insert_product($product);
      }
      elseif (isset($response_array->code) and $response_array->code == "400") {
        #echo "\n---------400---------\n";
        $this->logger->log("--> tuote {$product["product_code"]} kysely epäonnistui!");
        $this->logger->log("syy: {$response_array->message}");
      }
      else {
        #echo "\n---------unknown-----\n";
        $this->logger->log("--> tuote {$product["product_code"]} kysely epäonnistui tuntemattomasta syystä!");
        $this->logger->log("syy: {$response_array->message}");
      }
    }
  }

  public function update_product($product) {
    $url = $this->apiurl."/products/".$product["id"];

    // id:t ym ei tarvita updatessa
    unset($product['id']);
    unset($product['original_product_code']);

    $data_json = json_encode($product);

    #echo "\n---------UPDATETAAN---------\n";
    #echo "",var_dump($product);

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
    #echo "\nvar_dump products:",var_dump($response_array);

    // response ok/fail vielä tähän
    $this->logger->log("--> tuote {$product["product_code"]} päivitetty");
  }

  public function insert_product($product) {
    $url = $this->apiurl."/products";

    // if original_product_code then us it
    if (!empty($product['original_product_code'])) {
      $product_code = $product['original_product_code'];
    }
    else {
      $product_code = $product['product_code'];
    }

    // id:tä ym ei tarvita insertissä
    unset($product['id']);
    unset($product['original_product_code']);

    #echo "<pre>",var_dump($product);

    $data_json = json_encode($product);

    #echo "\n---------INSERTÖIDÄÄN---------\n";

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

    #echo "\n\nvar_dump products:",var_dump($response_array);

    if (isset($response_array->items[0]->id) and !empty($response_array->items[0]->id)) {
      $this->logger->log("--> tuote {$product["product_code"]} lisätty");
      $this->update_id("", $product_code, $response_array->items[0]->id);
      #echo "\nIIDEE PÄIVITETTY!!!\n";
    }
    else {
      $this->logger->log("--> tuote {$product["product_code"]} lisääminen epäonnistui!");
      $this->logger->log("syy: {$response_array->message}");
    }
  }

  public function set_all_products($value) {
    if (is_array($value)) {
      $this->pupesoft_all_products = $value;
    }
  }

  public function update_id($id, $viidakko_productcode, $viidakko_id) {
    global $yhtiorow;

    if ($id== "") {
      $query = "  INSERT INTO tuotteen_avainsanat SET
                  yhtio       = '{$yhtiorow['yhtio']}',
                  tuoteno     = '{$viidakko_productcode}',
                  laji        = 'viidakko_tuoteno',
                  selite      = '{$viidakko_id}',
                  laatija     = 'viidakkostore',
                  luontiaika  = now() ON DUPLICATE KEY UPDATE
                  muuttaja    = 'viidakkostore',
                  muutospvm   = now()";
      $insert_res = pupe_query($query);
    }
    elseif ($id != $viidakko_id) {
      $query = "  UPDATE tuotteen_avainsanat SET
                  selite      = '{$viidakko_id}',
                  muuttaja    = 'viidakkostore',
                  muutospvm   = now()
                  WHERE
                  yhtio       = '{$yhtiorow['yhtio']}' AND
                  tuoteno     = '{$viidakko_productcode}' AND
                  laji        = 'viidakko_tuoteno'";
      $update_res = pupe_query($query);
    }
  }
}
