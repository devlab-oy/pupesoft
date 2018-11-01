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
    $store_image_ids = array();
    $insert_these_products = array();

    echo "\nkuvadata:<pre>",var_dump($pupesoft_products);

    // looppaa kaupan kuvat ja poista kaikki,
    // jos tuotteen kuvia on pupessa muutettu,
    // jonka jälkeen lisää kaikki uusiks,
    // tai lopeta koko homma jos muutoksia ei ole
    foreach ($pupesoft_products as $product) {

      // only first product pic has updated value "X"
      if ($product['updated'] == 'X') {
        // fetch and delete all pictures of given product
        $url = $this->apiurl."/products/".$product["id"]."/images/";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'X-Auth-Token: '.$this->token));
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        $current++;
        $response_array = json_decode($response);

        echo "\n kuvaresponse:";
        echo "\n<pre>",var_dump($response_array);

        if (isset($response_array->items[0]->id) and !empty($response_array->items[0]->id)) {
          foreach ($response_array->items as $key => $value) {

            $_product = array(
              "id" => $product["id"],
              "image_id" => $value->id,
              "code" => $product["code"],
              "liitetiedostot_tunnus" => "",
            );
            $this->delete_image($_product);
          }
        }

        // inserting products first pic after deletions
        $this->insert_image($product);
      }
      elseif ($product['updated'] == 'Y') {
        $this->insert_image($product);
      }
    }
  }

  public function delete_image($product) {
    $url = $this->apiurl."/products/".$product["id"]."/images/".$product["image_id"];

    echo "\n---------DELETOIDAAN---------product-id:{$product["id"]} image_id:{$product["image_id"]}\n";

    $data_json = json_encode(array(
      "id" => $product['image_id'],
    ));

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'X-Auth-Token: '.$this->token));
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    $response_array = json_decode($response);

    echo "\n delete\n";
    echo "\ndelete image product response_array:<pre>",var_dump($response_array);

    // response ok/fail vielä tähän
    $this->logger->log("--> tuotteen {$product['code']} kuva-id {$product['image_id']} poistettu");
    echo "\nsiirrytään deletoimaan kuvan id {$product['image_id']} tuotteelta {$product["code"]}";
    $this->update_id($product, "", "");
  }

  public function insert_image($product) {
    $url = $this->apiurl."/products/".$product['id']."/images/upload";

    echo "just ennen inserttiä product:<pre>",var_dump($product);

    $data_json = json_encode(array(
      "titles"        => $product['title'],
      "descriptions"  => $product['description'],
      "position"      => $product['position'],
      "file_url_path" => $product['image'],
      "is_default"    => $product['is_default'],
      "is_teaser"     => $product['is_teaser'],
    ));

    echo "\n---------INSERTÖIDÄÄN---------{$product['id']}\n";
    echo "\ninsert json data:<pre>",var_dump(json_decode($data_json));

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json', 'X-Auth-Token: '.$this->token));
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    $response_array = json_decode($response, true);

    echo "\ninsert response_array:<pre>",var_dump($response_array);
    echo "\ninsert response:<pre>",var_dump($response);

    if (isset($response_array['items'][0]['id']) and !empty($response_array['items'][0]['id'])) {
    #if (isset($response_array->items[0]->id) and !empty($response_array->items[0]->id)) {
      echo "\n---------200---------\n";
      $this->logger->log("--> tuotteen {$product["code"]} kuvan lisääminen onnistui! ID: {$response_array['items'][0]['id']}, Pupe-liitetiedostot-tunnus: {$product['liitetiedostot_tunnus']}");
      $this->update_id($product['image_id'], $response_array['items'][0]['id'], $product['liitetiedostot_tunnus']);
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

  public function update_id($product, $viidakko_pic_id, $liitetiedostot_tunnus) {
    global $yhtiorow;

    if ((empty($product['image_id']) or $product['image_id'] != $viidakko_pic_id) and $liitetiedostot_tunnus != "") {
      $query = "  UPDATE liitetiedostot SET
                  external_id = '{$viidakko_pic_id}'
                  WHERE tunnus = $liitetiedostot_tunnus";
      $insert_res = pupe_query($query);
      echo "\nIIDEE UPDATETTU!\n\n";
    }
    elseif (!empty($product['image_id']) and $viidakko_pic_id == "" and $liitetiedostot_tunnus == "") {
      $query = "  UPDATE liitetiedostot SET
                  external_id = ''
                  WHERE yhtio = '{$yhtiorow['yhtio']}'
                  AND liitos = 'tuote'
                  AND liitostunnus = {$product['id']}
                  AND kayttotarkoitus = 'TK'
                  AND external_id = '{$product['image_id']}'
                  ";
      $insert_res = pupe_query($query);
      echo "\nIIDEE UPDATETTU!\n\n";
    }
  }
}
