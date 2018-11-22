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

    foreach ($pupesoft_variations as $product) {

      $url = $this->apiurl."/products?codes[]=".$variation["product_code"];
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'X-Auth-Token: '.$this->token));
      curl_setopt($ch, CURLOPT_HEADER, FALSE);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

      $response = curl_exec($ch);
      curl_close($ch);

      $current++;
      $response_array = json_decode($response);

      #echo "\n check\n";
      #echo "\nvar_dump variations:<pre>",var_dump($response_array);

      if (isset($response_array->items[0]->id) and !empty($response_array->items[0]->id)) {
        echo "\n---------200---------\n";
        $this->logger->log("[{$current}/{$total}] tuote {$variation["product_code"]} löytyy kaupasta");
        // lets check store product id
        $this->update_id($variation['id'], $response_array->items[0]);
        // lets update product with correct id
        $variation['id'] = $response_array->items[0]->id;
        #echo "\nvar_dump iidee:<pre>",var_dump($response_array->items[0]);
        #echo "\nvar_dump products:<pre>",var_dump($variation);
        $this->update_variation($variation);
      }
      elseif (isset($response_array->items) and !isset($response_array->items[0])) {
        echo "\n---------404---------\n";
        $this->logger->log("[{$current}/{$total}] tuote {$variation["code"]} ei löydy kaupasta | response_code: $response_array->code");
        // lets insert variation
        $this->insert_variation($variation);
      }
      elseif (isset($response_array->code) and $response_array->code == "400") {
        echo "\n---------400---------\n";
        $this->logger->log("--> tuote {$variation["product_code"]} kysely epäonnistui!");
        $this->logger->log("syy: {$response_array->message}");
      }
      else {
        echo "\n---------unknown-----\n";
        $this->logger->log("--> tuote {$variation["product_code"]} kysely epäonnistui tuntemattomasta syystä!");
        $this->logger->log("syy: {$response_array->message}");
      }
    }
  }

  public function update_variation($variation) {
    $url = $this->apiurl."/products/".$variation["id"];

    unset($variation['id']);

    $data_json = json_encode($variation);
    echo "\n---------UPDATETAAN---------\n";
    echo "<pre>",var_dump($variation);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json', 'X-Auth-Token: '.$this->token));
    curl_setopt($ch, CURLOPT_HEADER, TRUE);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    $response_array = json_decode($response);

    echo "\n update\n";
    echo "\nvar_dump products:<pre>",var_dump($response);

    // response ok/fail vielä tähän
    $this->logger->log("--> tuote {$variation["product_code"]} päivitetty");
  }

  public function insert_variation($variation) {
    $url = $this->apiurl."/products";

    // id:tä ei tarvita insertissä
    unset($variation['id']);

    echo "<pre>",var_dump($variation);

    $data_json = json_encode($variation);
    echo "\n---------INSERTÖIDÄÄN---------\n";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json', 'X-Auth-Token: '.$this->token));
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    echo "\n insert\n";
    echo "\n\nvar_dump products:<pre>",var_dump($response);

    $response_array = json_decode($response);

    if (isset($response_array->items[0]->id) and !empty($response_array->items[0]->id)) {
      $this->logger->log("--> tuote {$variation["product_code"]} lisätty");
      $this->update_id("", $response_array->items[0]);
      echo "\nIIDEE PÄIVITETTY!!!\n";
    }
    else {
      $this->logger->log("--> tuote {$variation["product_code"]} lisääminen epäonnistui!");
      $this->logger->log("syy: {$response_array->message}");
    }
  }

  public function set_all_variations($value) {
    if (is_array($value)) {
      $this->pupesoft_all_variations = $value;
    }
  }

  public function update_id($id, $viidakko_product) {
    global $yhtiorow;

    if ($id== "") {
      $query = "  INSERT INTO tuotteen_avainsanat SET
                  yhtio       = '{$yhtiorow['yhtio']}',
                  tuoteno     = '{$viidakko_product->product_code}',
                  laji        = 'viidakko_tuoteno',
                  selite      = '{$viidakko_product->id}',
                  laatija     = 'viidakkostore',
                  luontiaika  = now() ON DUPLICATE KEY UPDATE
                  muuttaja    = 'viidakkostore',
                  muutospvm   = now()";
      $insert_res = pupe_query($query);
    }
    elseif ($id != $viidakko_product->id) {
      $query = "  UPDATE tuotteen_avainsanat SET
                  yhtio       = '{$yhtiorow['yhtio']}',
                  tuoteno     = '{$viidakko_product->product_code}',
                  laji        = 'viidakko_tuoteno',
                  selite      = '{$viidakko_product->id}',
                  muuttaja    = 'viidakkostore',
                  muutospvm   = now()";
      $update_res = pupe_query($query);
    }
  }
}
