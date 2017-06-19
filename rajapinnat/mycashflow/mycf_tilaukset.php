<?php

require_once "rajapinnat/edi.php";

class MyCashflowTilaukset {
  private $apiurl = "";
  private $whkey = "";

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

  // Korvaavia Maksuehtoja MyCashflown maksuehdoille
  private $mycf_maksuehto_ohjaus = array();

  // Vaihoehtoisia OVT-tunnuksia EDI-tilaukselle
  private $mycf_erikoiskasittely = array();

  protected $logger = null;

  public function __construct($url, $mycf_webhooks_key, $log_file) {
    $this->apiurl = $url;
    $this->whkey = $mycf_webhooks_key;

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

  public function set_mycf_maksuehto_ohjaus($value) {
    $this->mycf_maksuehto_ohjaus = $value;
  }

  public function set_mycf_erikoiskasittely($value) {
    $this->mycf_erikoiskasittely = $value;
  }

  public function fetch_orders() {
    $this->logger->log('---------Aloitetaan tilausten haku---------');

    // Haetaan aika jolloin tämä skripti on viimeksi ajettu
    $datetime_checkpoint = cron_aikaleima("MYCF_ORDR_CRON");

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
      'maksuehto_ohjaus'   => $this->mycf_maksuehto_ohjaus,
      'erikoiskasittely'   => $this->mycf_erikoiskasittely,
    );

    $url = "{$this->apiurl}/webhooks/changes?ts={$datetime_checkpoint}&key={$this->whkey}";

    // Webhooks-call
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/xml'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $xml = simplexml_load_string($response);

    if ($xml === FALSE) {
      echo "Response is not valid XML!\n";
    }

    $tilaus = array();

    foreach ($xml->Order as $order) {

      // Ostajan tiedot
      $tilaus['billing_address']['city'] = $order->CustomerInformation->City;
      $tilaus['billing_address']['company'] = "";
      $tilaus['billing_address']['fax'] = "";
      $tilaus['billing_address']['firstname'] = $order->CustomerInformation->FirstName;
      $tilaus['billing_address']['lastname'] = $order->CustomerInformation->LastName;
      $tilaus['billing_address']['postcode'] = $order->CustomerInformation->ZipCode;
      $tilaus['billing_address']['street'] = $order->CustomerInformation->StreetAddress;
      $tilaus['billing_address']['telephone'] = $order->CustomerInformation->Phone;

      // Toimitusosoitteen tiedot
      $tilaus['shipping_address']['city'] = $order->ShippingAddress->City;
      $tilaus['shipping_address']['company'] = "";
      $tilaus['shipping_address']['country_id'] = strtoupper($order->ShippingAddress->Country);
      $tilaus['shipping_address']['firstname'] = $order->ShippingAddress->FirstName;
      $tilaus['shipping_address']['lastname'] = $order->ShippingAddress->LastName;
      $tilaus['shipping_address']['postcode'] = $order->ShippingAddress->ZipCode;
      $tilaus['shipping_address']['street'] = $order->ShippingAddress->StreetAddress;
      $tilaus['shipping_address']['telephone'] = $order->ShippingAddress->Phone;

      // Sähköposti
      if (!empty($order->ShippingAddress->Email)) {
        $tilaus['customer_email'] = $order->ShippingAddress->Email;
      }
      else {
        $tilaus['customer_email'] = $order->CustomerInformation->Email;
      }

      // Tilausnumero
      $tilaus['order_number'] = $order->OrderNumber;
      $tilaus['increment_id'] = $order->OrderNumber;

      // Verkkokaupan nimi
      $tilaus['store_name'] = "MyCashflow";

      // Maksettu
      $tilaus['status'] = "processing";

      // Ei käytössä
      $tilaus['reference_number'] = "";
      $tilaus['target'] = "";
      $tilaus['webtex_giftcard'] = "";

      // Asiakasnumero
      $tilaus['customer_id'] = "";

      // Asiakkaan viesti meille
      $tilaus['customer_note'] = trim((string) $order->Comments[0]);

      // Yhteensäsumma, summataan riveiltä
      $tilaus['grand_total'] = 0;

      // Veron määrä, summataan riveiltä
      $tilaus['tax_amount'] = 0;

      // Valuutta
      $tilaus['order_currency_code'] = "EUR";

      // Tuotteet
      $tilaus['items'] = array();

      foreach ($order->Products->Product as $product) {

        // Maksutavan hinta
        if (!empty($product->ProductID->attributes()->PaymentID)) {
          $tilaus['payment']['method'] = "";
          continue;
        }

        // Rahtimaksu
        if (!empty($product->ProductID->attributes()->ShippingID)) {
          // Rahtikulu, veroton
          $tilaus['shipping_amount'] = (float) $product->Total - (float) $product->TotalTax;

          // Toimitustavan nimi
          $tilaus['shipping_description'] = $product->ProductName;
          $tilaus['shipping_description_line'] = $product->ProductName;

          // Noutopisteen tiedot: Tallennetaan toimitusosoitteen perään [# ]-tägeihin
          $pickupcode = trim($order->ShippingMethod->attributes()->PickUpPointCode);

          if (!empty($pickupcode)) {
            $tilaus['shipping_address']['street'] .= " [#".$pickupcode."]";
          }

          // Veron määrä
          $tilaus['shipping_tax_amount'] = $product->TotalTax;
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

      cron_aikaleima("MYCF_ORDR_CRON", $tilausaika);

      $this->logger->log("Tallennettiin tilaus '{$filename}'");
    }

    $this->logger->log('---------Tilausten haku valmis---------');
  }
}
