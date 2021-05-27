<?php

require_once "rajapinnat/edi.php";

class MyCashflowTilaukset {
  private $apiurl = "";
  private $whkey = "";

  // Minne hakemistoon EDI-tilaus tallennetaan
  private $edi_polku = '/tmp';

  // Ovt tunnus, kenelle EDI-tilaukset tehd��n (yhtio.ovttunnus)
  private $ovt_tunnus = null;

  // Mik� on EDI-tilauksella rahtikulutuotteen nimitys
  private $rahtikulu_nimitys = 'L�hetyskulut';

  // Mik� on EDI-tilauksella rahtikulutuotteen tuotenumero (yhtio.rahti_tuotenumero)
  private $rahtikulu_tuoteno = null;

  // Mik� on EDI-tilauksen tilaustyyppi
  private $pupesoft_tilaustyyppi = '';

  // Mik� on EDI-tilauksella asiakasnumero, jolle tilaus tehd��n
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

  public function set_mycf_kaupat($value) {
    $this->mycf_kaupat = $value;
  }

  public function get_asiakastiedot($kauppaversio, $etsi_asiakas) {

    // vain version 6 asiakkaan tiedot haetaan
    if(!in_array($kauppaversio, array(6))) {
      return;
    }
    
    // yritet��n hakea oikea asiakas
    $query = "SELECT * 
                FROM asiakas 
                WHERE yhtio = '{$GLOBALS["yhtiorow"]['yhtio']}' 
                AND ytunnus = '{$etsi_asiakas->BusinessID}'
              ";
    $result = pupe_query($query);
    if (!mysql_num_rows($result)) {
      return;
    }

    $loydetty_asiakas = mysql_fetch_assoc($result);

    // saadaan kurssit avainsanoista.
    $kurssi_avainsanat = t_avainsana("VERKKOKAUKURSSI");
    $kurssi_kerroin = 1;
    $asiakaskurssi=false;
    while ($kurssi = mysql_fetch_assoc($kurssi_avainsanat)) {
      if($kurssi['selite'] == $loydetty_asiakas['valkoodi']) {
        // saadaan kerroin
        $kurssi_kerroin = floatval(str_replace(",", ".", $kurssi['selitetark']));
        $kurssi_kerroin = round($kurssi_kerroin, 9);
        $asiakaskurssi = round(1/$kurssi_kerroin, 9);
        break;
      }
    }

    // jos kurssi ei l�ydy - haetaan pupeesta kurssi.
    if(!$asiakaskurssi) {
      $query = "SELECT kurssi
                  FROM valuu
                  WHERE yhtio = '{$GLOBALS["yhtiorow"]['yhtio']}'
                  AND nimi    = '{$loydetty_asiakas['valkoodi']}'";
      $valres = pupe_query($query);

      if ($valrow = mysql_fetch_assoc($valres)) {
        $kurssi_kerroin = floatval(str_replace(",", ".", $valrow['kurssi']));
        $kurssi_kerroin = round($kurssi_kerroin*100, 9);
        $asiakaskurssi = round(1/$kurssi_kerroin, 9);
      } else {
        return;
      }
    }

    // haetaan asiakkaan maksuehto
    $asiakasmaksuehto = false;
    if(!empty($loydetty_asiakas['maksuehto'])) {
      $query = "SELECT teksti 
                  FROM maksuehto 
                  WHERE yhtio = '{$GLOBALS["yhtiorow"]['yhtio']}' 
                  AND tunnus = '{$loydetty_asiakas['maksuehto']}'
                ";
      $result = pupe_query($query);
      if (mysql_num_rows($result)) {
        $loydetty_asiakasmaksuehto = mysql_fetch_assoc($result);
        $asiakasmaksuehto = $loydetty_asiakasmaksuehto['teksti'];
      }
    }

    return array(
      'asiakasnro' => $loydetty_asiakas['asiakasnro'],
      'kurssi_kerroin' => $kurssi_kerroin,
      'asiakasvaluuta' => $loydetty_asiakas['valkoodi'],
      'maksuehto' => $asiakasmaksuehto,
      'asiakaskurssi' => $asiakaskurssi
    );
  }

  public function fetch_orders() {
    $this->logger->log('---------Aloitetaan tilausten haku---------');

    // Haetaan aika jolloin t�m� skripti on viimeksi ajettu
    $datetime_checkpoint = cron_aikaleima("MYCF_ORDR_CRON");

    if (empty($datetime_checkpoint)) {
      $datetime_checkpoint = 1;
    }

    // EDI-tilauksen luontiin tarvittavat parametrit
    $options = array(
      'edi_polku'         => $this->edi_polku,
      'ovt_tunnus'        => $this->ovt_tunnus,
      'rahtikulu_nimitys' => $this->rahtikulu_nimitys,
      'rahtikulu_tuoteno' => $this->rahtikulu_tuoteno,
      'tilaustyyppi'      => $this->pupesoft_tilaustyyppi,
      'asiakasnro'        => $this->verkkokauppa_asiakasnro,
      'maksuehto_ohjaus'  => $this->mycf_maksuehto_ohjaus,
      'erikoiskasittely'  => $this->mycf_erikoiskasittely,
      'verkkokauppa_verollisen_hinnan_kentta' => '',
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
      
      // Ohitetaan duplikaatit
      $query = "SELECT asiakkaan_tilausnumero
                FROM lasku
                WHERE yhtio = '{$GLOBALS["yhtiorow"]['yhtio']}'
                AND asiakkaan_tilausnumero = '{$order->OrderNumber}'
                AND ohjelma_moduli = 'MAGENTO'";
      $result = pupe_query($query);

      if (mysql_num_rows($result)) {
        $this->logger->log("Duplikaattitilaus ohitettiin: '{$order->OrderNumber}'");
        $tilausaika = $order->OrderedAt->attributes()->timestamp;
        cron_aikaleima("MYCF_ORDR_CRON", $tilausaika);
        continue;
      }

      // Kaupan tiedot
      $kauppaversio = (int) $order->OrderVersionID;

      if (!empty($this->mycf_kaupat[$kauppaversio])) {
        $options['asiakasnro'] = $this->mycf_kaupat[$kauppaversio];
      }
      else {
        $options['asiakasnro'] = $this->verkkokauppa_asiakasnro;
      }

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

      // S�hk�posti
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

      // Ei k�yt�ss�
      $tilaus['reference_number'] = "";
      $tilaus['target'] = "";
      $tilaus['webtex_giftcard'] = "";

      // Asiakasnumero
      $tilaus['customer_id'] = "";

      // Asiakkaan viesti meille
      $tilaus['customer_note'] = trim((string) $order->Comments[0]);

      // Yhteens�summa, summataan riveilt�
      $tilaus['grand_total'] = 0;

      // Veron m��r�, summataan riveilt�
      $tilaus['tax_amount'] = 0;

      // Valuutta
      $tilaus['order_currency_code'] = "EUR";

      // Valuutan kurssi. 1 on EUR
      $kurssi_kerroin = 1;

      //Kustomoitu maksutapa oletuksena pois
      $custom_payment_method = false;

      // Tuotteet
      $tilaus['items'] = array();

      // Yritet��n hakea erikoisasiakastiedot ja muut tiedot
      if($asiakastiedot = $this->get_asiakastiedot($kauppaversio, $order->CustomerInformation)) {
        $options['asiakasnro'] = $asiakastiedot['asiakasnro'];
        $kurssi_kerroin = $asiakastiedot['kurssi_kerroin'];
        $custom_payment_method = (array) $order->PaymentMethod;
        if($asiakastiedot['maksuehto']) {
          $tilaus['payment']['method'] = $asiakastiedot['maksuehto'];
        } else {
          $tilaus['payment']['method'] = $custom_payment_method[0];
        }
        $options['oma_kurssi'] = "JOO";
        $options['asiakasvaluuta'] = $asiakastiedot['asiakasvaluuta'];
        $options['asiakaskurssi'] = $asiakastiedot['asiakaskurssi'];
      }

      foreach ($order->Products->Product as $product) {

        // Maksutavan hinta
        if (!$custom_payment_method and !empty($product->ProductID->attributes()->PaymentID)) {
          $tilaus['payment']['method'] = "";
          continue;
        }

        // Rahtimaksu
        if (!empty($product->ProductID->attributes()->ShippingID)) {
          // Rahtikulu, veroton
          $tilaus['shipping_amount'] = ((float) $product->Total - (float) $product->TotalTax) * $kurssi_kerroin;

          // Toimitustavan nimi
          $tilaus['shipping_description'] = $product->ProductName;
          $tilaus['shipping_description_line'] = $product->ProductName;

          // Noutopisteen tiedot: Tallennetaan toimitusosoitteen per��n [# ]-t�geihin
          $pickupcode = trim($order->ShippingMethod->attributes()->PickUpPointCode);

          if (!empty($pickupcode)) {
            $tilaus['shipping_address']['street'] .= " [#".$pickupcode."]";
          }

          // Veron m��r�
          $tilaus['shipping_tax_amount'] = $product->TotalTax * $kurssi_kerroin;
          continue;
        }

        $item = array();

        // T�m� on alennustuote
        if (!empty($product->ProductID->attributes()->CouponCode) and !empty($GLOBALS["yhtiorow"]["alennus_tuotenumero"])) {
          // Tuoteno
          $item['sku'] = $GLOBALS["yhtiorow"]["alennus_tuotenumero"];

          // Nimitys
          $item['name'] = "Alennuskoodi: ".$product->ProductName;

          // Hinta ja m��r�kerroin
          $kerroin = -1;
        }
        else {
          // Tuoteno
          $item['sku'] = $product->Code;

          // Nimitys
          $item['name'] = $product->ProductName;

          // Hinta ja m��r�kerroin
          $kerroin = 1;
        }

        // M��r�
        $item['qty_ordered'] = $product->Quantity * $kerroin;

        $item['base_discount_amount'] = 0;
        $item['discount_percent'] = 0;

        // Verollinen yksikk�hinta
        $item['original_price'] = $product->UnitPrice * $kerroin * $kurssi_kerroin;

        // Veroton yksikk�hinta
        $item['price'] = ((float) $product->UnitPrice - (float) $product->UnitTax) * $kerroin * $kurssi_kerroin;

        // Verokanta
        $item['tax_percent'] = $product->UnitPrice->attributes()->vat;

        // Yheens�summa
        $tilaus['grand_total'] += (float) $product->Total * $kerroin * $kurssi_kerroin;
        $tilaus['tax_amount'] += (float) $product->TotalTax * $kerroin * $kurssi_kerroin;

        // Ei k�yt�ss�
        $item['parent_item_id'] = "";
        $item['product_id'] = "";
        $item['product_type'] = "";

        // Lis�t��n tuotelistaan
        $tilaus['items'][] = $item;
      }

      $filename = Edi::create($tilaus, $options);

      // Tallennetaan t�m�n tilauksen aikaleima
      $tilausaika = $order->OrderedAt->attributes()->timestamp;

      cron_aikaleima("MYCF_ORDR_CRON", $tilausaika);

      $this->logger->log("Tallennettiin tilaus '{$filename}'");
    }

    $this->logger->log('---------Tilausten haku valmis---------');
  }
}
