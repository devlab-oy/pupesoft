<?php

class ViidakkoStoreKuvat {
  private $apiurl = "";
  private $token = "";
  private $pupesoft_all_products = array();
  protected $logger = null;

  public function __construct($url, $token, $log_file) {
    $this->apiurl = $url;
    $this->token = $token;

    $this->logger = new Logger($log_file);
  }

  public function check_pics() {
    $this->logger->log('---------Tarkistetaan onko kuva jo kaupassa---------');
    echo "\n---------Tarkistetaan onko kuva jo kaupassa---------\n";

    $pupesoft_products = $this->pupesoft_all_products;
    $total = count($pupesoft_products);
    $current = 0;

    echo "\n\n\n",var_dump($pupesoft_products);

    foreach ($pupesoft_products as $product) {

      // upload image to store
      if (empty($product['image_id'])) {
        $this->insert_product($product);
        continue;
      }
      else { // picture already in store

        $url = $this->apiurl."/products/".$product["id"]."/images/".$product["image_id"];
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'X-Auth-Token: '.$this->token));
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        $current++;
        $response_array = json_decode($response);

        echo "\n kuvat check\n";
        echo "\n\n\n",var_dump($response_array);

        continue;

        if (isset($response_array->items) and count($response_array->items) > 0) {
          foreach ($response_array->items AS $key => $imagedata) {

            echo "\n\n\n",var_dump($imagedata->id);



          }
        }
        die;
      }




      if (isset($response_array->items[0]->id) and !empty($response_array->items[0]->id)) {
        echo "\n---------200---------\n";
        $this->logger->log("[{$current}/{$total}] tuote {$product["code"]} löytyy kaupasta");
        // lets check store product id
        $this->update_id($product['id'], $response_array->items[0]);
        // lets update product with correct id
        $product['id'] = $response_array->items[0]->id;
        $this->update_product($product);
      }
      elseif (isset($response_array->items) and !isset($response_array->items[0])) {
        echo "\n---------404---------\n";
        $this->logger->log("[{$current}/{$total}] tuote {$product["code"]} ei löydy kaupasta | response: $response");
        // lets insert product
        $this->insert_product($product);
      }
      elseif (isset($response_array->code) and $response_array->code == "400") {
        echo "\n---------400---------\n";
        $this->logger->log("--> tuote {$product["code"]} kysely epäonnistui!");
        $this->logger->log("syy: {$response}");
      }
      else {
        echo "\n---------unknown-----\n";
        $this->logger->log("--> tuote {$product["code"]} kysely epäonnistui tuntemattomasta syystä!");
        $this->logger->log("syy: {$response}");
      }
    }
  }

  public function update_product($product) {
    $url = $this->apiurl."/products/".$product["id"]."/images/".$product["image_id"];

    $data_json = json_encode(array(
      "titles"        => $product['title_description'],
      "descriptions"  => $product['title_description'],
      "position"      => $product['position'],
      "is_default"    => $product['is_default'],
    ));

    echo "\n---------UPDATETAAN---------\n";
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
die;
    // response ok/fail vielä tähän
    $this->logger->log("--> tuote {$product["product_code"]} päivitetty");
  }

  public function insert_product($product) {
    $url = $this->apiurl."/products/".$product['id']."/images/upload";

    #echo "<pre>",var_dump($product);

    $data_json = json_encode(array(
      "titles"        => $product['title_description'],
      #"descriptions"  => $product['title_description'],
      "position"      => $product['position'],
      "file_url_path" => $product['image'],
      "is_default"    => $product['is_default'],
      "is_teaser"     => $product['is_teaser'],
    ));

    echo "\n<pre>",var_dump(json_decode($data_json));

    echo "\n---------INSERTÖIDÄÄN---------\n";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json', 'X-Auth-Token: '.$this->token));
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    $response_array = json_decode($response, true);

    echo "\n\nvar_dump kuvat:<pre>",var_dump($response_array);

    if (isset($response_array['items'][0]['id']) and !empty($response_array['items'][0]['id'])) {
      echo "\n---------200---------\n";
      $this->logger->log("--> tuotteen {$product["code"]} kuvan lisääminen onnistui! ID: {$response_array['items'][0]['id']}, Pupe-liitetiedostot-tunnus: {$product['liitetiedostot_tunnus']}");
      $this->update_id($product['image_id'], $response_array['items'][0], $product['liitetiedostot_tunnus']);
    }
    else {
      echo "\n---------400---------\n";
      $this->logger->log("--> tuotteen {$product["code"]} kuvan lisäys epäonnistui! Pupe-liitetiedostot-tunnus: {$product['liitetiedostot_tunnus']}");
      $this->logger->log("syy: {$response}");
    }
  }

  public function set_all_products($value) {
    if (is_array($value)) {
      $this->pupesoft_all_products = $value;
    }
  }

  public function update_id($id, $viidakko_pic, $liitetiedostot_tunnus) {
    global $yhtiorow;

    if (empty($id) or $id != $viidakko_pic['id']) {
      $query = "  UPDATE liitetiedostot SET
                  external_id = '{$viidakko_pic['id']}'
                  WHERE tunnus = $liitetiedostot_tunnus";
      $insert_res = pupe_query($query);
    }
  }
}
