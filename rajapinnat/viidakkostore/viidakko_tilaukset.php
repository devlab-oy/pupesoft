<?php

require_once "rajapinnat/edi.php";

class ViidakkoStoreTilaukset {
  private $apiurl = "";
  private $userpwd = "";

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

  public function __construct($url, $username, $api_key, $log_file) {
    $this->apiurl = $url;
    $this->userpwd = "{$username}:{$api_key}";

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

  public function fetch_orders() {
    $this->logger->log('---------Aloitetaan tilausten haku---------');

    // Haetaan aika jolloin tämä skripti on viimeksi ajettu
    $datetime_checkpoint = cron_aikaleima("VIID_ORDR_CRON");

    if (empty($datetime_checkpoint)) {
      $datetime_checkpoint = 1;
    }

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

    $url = $this->apiurl."/orders?status=".$viidakko_tilausstatus;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, $this->userpwd);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
    curl_setopt($ch, CURLOPT_HEADER, TRUE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    $response_array = json_decode($response);

    $tilaus = array();

    if ($response_array->code == "200") {
      $this->logger->log("Tilausten haku onnistui");

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
          $tilaus['shipping_description'] = $order->delivery->names->fi;
          $tilaus['shipping_description_line'] = $order->delivery->names->fi;

          // Noutopisteen tiedot: Tallennetaan toimitusosoitteen perään [# ]-tägeihin
          $pickupcode = "";

          if (!empty($pickupcode)) {
            $tilaus['shipping_address']['street'] .= " [#".$pickupcode."]";
          }

          // Veron määrä (ei toistaiseksi)
          $tilaus['shipping_tax_amount'] = 0;
          continue;
        }

        // Tuotteet
        $tilaus['items'] = array();

        foreach ($order->rows as $product) {

          // Maksutavan hinta
          if (!empty($product->ProductID->attributes()->PaymentID)) {
            $tilaus['payment']['method'] = "";
            continue;
          }

          $item = array();

          // Tämä on alennustuote
          if (!empty($product->ProductID->attributes()->CouponCode) and !empty($GLOBALS["yhtiorow"]["alennus_tuotenumero"])) {
            // Tuoteno
            $item['sku'] = $GLOBALS["yhtiorow"]["alennus_tuotenumero"];

            // Nimitys
            $item['name'] = "Alennuskoodi: ".$product->ProductName;

            // Hinta ja määräkerroin
            $kerroin = -1;
          }
          else {
            // Tuoteno
            $item['sku'] = $product->Code;

            // Nimitys
            $item['name'] = $product->ProductName;

            // Hinta ja määräkerroin
            $kerroin = 1;
          }

          // Määrä
          $item['qty_ordered'] = $product->Quantity * $kerroin;

          $item['base_discount_amount'] = 0;
          $item['discount_percent'] = 0;

          // Verollinen yksikköhinta
          $item['original_price'] = $product->UnitPrice * $kerroin;

          // Veroton yksikköhinta
          $item['price'] = ((float) $product->UnitPrice - (float) $product->UnitTax) * $kerroin;

          // Verokanta
          $item['tax_percent'] = $product->UnitPrice->attributes()->vat;

          // Yheensäsumma
          $tilaus['grand_total'] += (float) $product->Total * $kerroin;
          $tilaus['tax_amount'] += (float) $product->TotalTax * $kerroin;

          // Ei käytössä
          $item['parent_item_id'] = "";
          $item['product_id'] = "";
          $item['product_type'] = "";

          // Lisätään tuotelistaan
          $tilaus['items'][] = $item;
        }

        $filename = Edi::create($tilaus, $options);

        // Tallennetaan tämän tilauksen aikaleima
        $tilausaika = $order->OrderedAt->attributes()->timestamp;

        cron_aikaleima("VIID_ORDR_CRON", $tilausaika);

        $this->logger->log("Tallennettiin tilaus '{$filename}'");
      }
    }
    else {
      $this->logger->log("Tilausten haku epäonnistui");
    }

    $this->logger->log('---------Tilausten haku valmis---------');
  }
}
