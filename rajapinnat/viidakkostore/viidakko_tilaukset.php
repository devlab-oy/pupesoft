<?php

require_once "rajapinnat/edi.php";

class ViidakkoStoreTilaukset {
  private $apiurl = "";
  private $token = "";

  // Minne hakemistoon EDI-tilaus tallennetaan
  private $edi_polku = '/tmp';

  // Ovt tunnus, kenelle EDI-tilaukset tehdään (yhtio.ovttunnus)
  private $ovt_tunnus = null;

  // Mikä on EDI-tilauksella rahtikulutuotteen nimitys
  private $rahtikulu_nimitys = 'Lähetyskulut';

  // Mikä on EDI-tilauksella rahtikulutuotteen tuotenumero (yhtio.rahti_tuotenumero)
  private $rahtikulu_tuoteno = null;

  // Mikä on EDI-tilauksen tilaustyyppi
  private $pupesoft_tilaustyyppi = '';

  // Mikä on EDI-tilauksella asiakasnumero, jolle tilaus tehdään
  private $verkkokauppa_asiakasnro = null;

  // Korvaavia Maksuehtoja ViidakkoStoren maksuehdoille
  private $viidakko_maksuehto_ohjaus = array();

  // Vaihoehtoisia OVT-tunnuksia EDI-tilaukselle
  private $viidakko_erikoiskasittely = array();

  protected $logger = null;

  public function __construct($url, $token, $log_file) {
    $this->apiurl = $url;
    $this->token = $token;

    $this->logger = new Logger($log_file);
  }

  public function set_edi_polku($value) {
    if (!is_writable($value)) {
      throw new Exception("EDI -hakemistoon ei voida kirjoittaa");
    }

    $this->edi_polku = $value;
  }

  public function set_ovt_tunnus($value) {
    $this->ovt_tunnus = $value;
  }

  public function set_rahtikulu_nimitys($value) {
    $this->rahtikulu_nimitys = $value;
  }

  public function set_rahtikulu_tuoteno($value) {
    $this->rahtikulu_tuoteno = $value;
  }

  public function set_pupesoft_tilaustyyppi($value) {
    $this->pupesoft_tilaustyyppi = $value;
  }

  public function set_verkkokauppa_asiakasnro($value) {
    $this->verkkokauppa_asiakasnro = $value;
  }

  public function set_viidakko_maksuehto_ohjaus($value) {
    $this->viidakko_maksuehto_ohjaus = $value;
  }

  public function set_viidakko_erikoiskasittely($value) {
    $this->viidakko_erikoiskasittely = $value;
  }

  public function update_viidakko_order_status($order_id) {
    $url = $this->apiurl."/orders/".$order_id."/statuses";

    $data_json = json_encode(array(
      "status"  => "13",
      "comment" => "Jou",
    ));
    echo "\n---------UPDATETAAN---------\n";
    echo "<pre>",var_dump($product);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json', 'X-Auth-Token: '.$this->token));
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    $response_array = json_decode($response);

    echo "\n edit\n";
    echo "\nvar_dump edit product:<pre>",var_dump($response);

    // response ok/fail vielä tähän
    $this->logger->log("--> tilaus {$product["product_code"]} päivitetty");
  }

  public function fetch_orders($status) {
    $this->logger->log('---------Aloitetaan tilausten haku---------');

    // EDI-tilauksen luontiin tarvittavat parametrit
    $options = array(
      'edi_polku'          => $this->edi_polku,
      'ovt_tunnus'         => $this->ovt_tunnus,
      'rahtikulu_nimitys'  => $this->rahtikulu_nimitys,
      'rahtikulu_tuoteno'  => $this->rahtikulu_tuoteno,
      'tilaustyyppi'       => $this->pupesoft_tilaustyyppi,
      'asiakasnro'         => $this->verkkokauppa_asiakasnro,
      'maksuehto_ohjaus'   => $this->viidakko_maksuehto_ohjaus,
      'erikoiskasittely'   => $this->viidakko_erikoiskasittely,
    );

    $url = $this->apiurl."/orders?status=".$status;
    #$url = $this->apiurl."/orders";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'X-Auth-Token: '.$this->token));
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    $response_array = json_decode($response);

    $tilaus = array();

    echo "<pre>",var_dump($response_array);
    #die;

    if (count($response_array->items) > 0 ) {
      $this->logger->log("Tilausten haku onnistui");
      echo "\n\nTilausten haku onnistui\n";
      foreach ($response_array->items as $order) {

        // Ostajan tiedot
        $tilaus['billing_address']['city'] = $order->payer->city;
        $tilaus['billing_address']['company'] = $order->payer->company;
        $tilaus['billing_address']['firstname'] = $order->payer->name;
        $tilaus['billing_address']['lastname'] = "";
        $tilaus['billing_address']['street'] = $order->payer->street;
        $tilaus['billing_address']['postcode'] = $order->payer->postal_code;
        $tilaus['billing_address']['city'] = $order->payer->city;
        $tilaus['billing_address']['telephone'] = $order->payer->phone_number;
        $tilaus['billing_address']['fax'] = "";

        // Toimitusosoitteen tiedot
        $tilaus['shipping_address']['city'] = $order->recipient->city;
        $tilaus['shipping_address']['company'] = $order->recipient->company;
        $tilaus['shipping_address']['country_id'] = strtoupper($order->recipient->country_code);
        $tilaus['shipping_address']['firstname'] = $order->recipient->name;
        $tilaus['shipping_address']['lastname'] = "";
        $tilaus['shipping_address']['postcode'] = $order->recipient->postal_code;
        $tilaus['shipping_address']['street'] = $order->recipient->street;
        $tilaus['shipping_address']['telephone'] = $order->recipient->phone_number;

        // Sähköposti
        if (!empty($order->recipient->email)) {
          $tilaus['customer_email'] = $order->recipient->email;
        }
        else {
          $tilaus['customer_email'] = $order->payer->email;
        }

        // Tilausnumero
        $tilaus['order_number'] = $order->id;
        $tilaus['increment_id'] = $order->number;

        // Verkkokaupan nimi
        $tilaus['store_name'] = "ViidakkoStore";

        // Maksettu
        $tilaus['status'] = "processing";

        // Ei käytössä
        $tilaus['reference_number'] = "";
        $tilaus['target'] = "";
        $tilaus['webtex_giftcard'] = "";

        // Asiakasnumero
        $tilaus['customer_id'] = $order->company;

        // Asiakkaan viesti meille
        $tilaus['customer_note'] = trim((string) $order->notes);

        // Yhteensäsumma, summataan riveiltä
        $tilaus['grand_total'] = 0;

        // Veron määrä, summataan riveiltä
        $tilaus['tax_amount'] = 0;

        // Valuutta
        $tilaus['order_currency_code'] = "EUR";

        // Rahtimaksu
        if (!empty($order->delivery->cost)) {
          // Rahtikulu, veroton
          $tilaus['shipping_amount'] = (float) $order->delivery->cost;

          // Toimitustavan nimi
          $tilaus['shipping_description'] = $order->delivery->names[0]->name;
          $tilaus['shipping_description_line'] = $order->delivery->names[0]->name;

          // Noutopisteen tiedot: Tallennetaan toimitusosoitteen perään [# ]-tägeihin
          $pickupcode = "";

          if (!empty($pickupcode)) {
            $tilaus['shipping_address']['street'] .= " [#".$pickupcode."]";
          }

          // Veron määrä (ei toistaiseksi)
          $tilaus['shipping_tax_amount'] = 0;
          #continue; #vut mitä täs yritetty?
        }

        // Tuotteet
        $tilaus['items'] = array();

        foreach ($order->rows as $product) {

          // Maksutavan hinta
          #if (!empty($product->ProductID->attributes()->PaymentID)) {
          #  $tilaus['payment']['method'] = "";
          #  continue;
          #}

          $tilaus['payment']['method'] = "131"; #testing

          $item = array();

          // Tuoteno
          $item['sku'] = $product->code;

          // Nimitys
          $item['name'] = $product->names[0]->name;

          // Hinta ja määräkerroin
          $kerroin = 1;

          // Määrä
          $item['qty_ordered'] = $product->amount * $kerroin;

          $item['base_discount_amount'] = 0;
          $item['discount_percent'] = 0;

          // Verollinen yksikköhinta
          $item['original_price'] = (float) $product->price * 1.24 * $kerroin;

          // Veroton yksikköhinta
          $item['price'] = ((float) $product->price) * $kerroin;

          // Verokanta
          $item['tax_percent'] = 24; #fiksaa verot

          // Yheensäsumma
          $tilaus['grand_total'] += $item['price'] * $item['qty_ordered'];
          $tilaus['tax_amount'] += ($item['original_price'] - $item['price']) * $item['qty_ordered'];

          // Ei käytössä
          $item['parent_item_id'] = "";
          $item['product_id'] = "";
          $item['product_type'] = "";

          // Lisätään tuotelistaan
          $tilaus['items'][] = $item;
        }

        #echo "<pre>",var_dump($tilaus);
        #die;

        $filename = Edi::create($tilaus, $options);

        // Tallennetaan tämän tilauksen aikaleima
        $tilausaika = date("Y-m-d H:i:s");

        cron_aikaleima("VIID_ORDR_CRON", $tilausaika);

echo "\n\nTallennettiin tilaus";

        $this->logger->log("Tallennettiin tilaus '{$filename}'");

        #update_viidakko_order_status($order->id);
      }
    }
    else {
      $this->logger->log("Tilausten haku epäonnistui");
    }

    $this->logger->log('---------Tilausten haku valmis---------');
  }
}
