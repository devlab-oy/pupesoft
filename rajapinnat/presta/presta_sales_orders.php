<?php

require_once 'rajapinnat/presta/presta_addresses.php';
require_once 'rajapinnat/presta/presta_carrier_files.php';
require_once 'rajapinnat/presta/presta_carriers.php';
require_once 'rajapinnat/presta/presta_client.php';
require_once 'rajapinnat/presta/presta_countries.php';
require_once 'rajapinnat/presta/presta_currencies.php';
require_once 'rajapinnat/presta/presta_customer_messages.php';
require_once 'rajapinnat/presta/presta_order_histories.php';

class PrestaSalesOrders extends PrestaClient {
  private $edi_filepath_base = null;
  private $edi_order = '';
  private $fetch_carrier_files = false;
  private $fetch_statuses = array();
  private $fetched_status = null;
  private $presta_addresses = null;
  private $presta_carrier_files = null;
  private $presta_carriers = null;
  private $presta_changeable_invoice_address = true;
  private $presta_countries = null;
  private $presta_currencies = null;
  private $presta_customer_messages = null;
  private $presta_order_histories = null;
  private $verkkokauppa_customer = null;
  private $yhtiorow = array();

  public function __construct($url, $api_key, $log_file) {
    $this->presta_addresses = new PrestaAddresses($url, $api_key, $log_file);
    $this->presta_carrier_files = new PrestaCarrierFiles($url, $api_key, $log_file);
    $this->presta_carriers = new PrestaCarriers($url, $api_key, $log_file);
    $this->presta_countries = new PrestaCountries($url, $api_key, $log_file);
    $this->presta_currencies = new PrestaCurrencies($url, $api_key, $log_file);
    $this->presta_customer_messages = new PrestaCustomerMessages($url, $api_key, $log_file);
    $this->presta_order_histories = new PrestaOrderHistories($url, $api_key, $log_file);

    parent::__construct($url, $api_key, $log_file);
  }

  protected function resource_name() {
    return 'orders';
  }

  protected function generate_xml($record, SimpleXMLElement $existing_record = null) {
    throw new Exception('You shouldnt be here, CRUD is not implemented!');
  }

  public function set_edi_filepath($filepath) {
    $filepath = rtrim($filepath, '/');

    if (!is_writable($filepath)) {
      throw new Exception("{$filepath} ei pysty kirjoittamaan");
    }

    $this->edi_filepath_base = $filepath;
  }

  public function set_yhtiorow($yhtiorow) {
    $this->yhtiorow = $yhtiorow;
  }

  public function set_verkkokauppa_customer($value) {
    $this->verkkokauppa_customer = $value;
  }

  public function set_fetch_statuses($value) {
    $this->fetch_statuses = $value;
  }

  public function set_fetched_status($value) {
    $this->fetched_status = $value;
  }

  public function set_changeable_invoice_address($value) {
    $this->presta_changeable_invoice_address = $value;
  }

  public function set_fetch_carrier_files($value) {
    $this->fetch_carrier_files = $value;
  }

  public function transfer_orders_to_pupesoft() {
    $this->logger->log('---------Start sales orders fetch---------');

    if (empty($this->edi_filepath_base)) {
      throw new Exception('Edi tiedosto polku pitää olla määritelty');
    }

    if (empty($this->yhtiorow)) {
      throw new Exception('Yhtiorow on tyhjä');
    }

    $sales_orders = $this->fetch_sales_orders();
    $id_group_shop = $this->shop_group_id();

    $total = count($sales_orders);
    $current = 0;

    foreach ($sales_orders as $sales_order) {
      $current++;
      $this->logger->log("[{$current}/{$total}] Myyntitilaus {$sales_order['id']}");

      try {
        // fetch associated addresses
        $address_invoice  = $this->presta_addresses->get($sales_order['id_address_invoice'], null, $id_group_shop);
        $invoice_country  = $this->presta_countries->get($address_invoice['id_country'], null, $id_group_shop);

        $address_delivery = $this->presta_addresses->get($sales_order['id_address_delivery'], null, $id_group_shop);
        $delivery_country = $this->presta_countries->get($address_delivery['id_country'], null, $id_group_shop);

        // fetch currency
        $currency = $this->presta_currencies->get($sales_order['id_currency'], null, $id_group_shop);

        // fetch carrier
        if (!empty($sales_order['id_carrier'])) {
          $carrier = $this->presta_carriers->get($sales_order['id_carrier'], null, $id_group_shop);
        }
        else {
          $carrier = array(
            "is_free" => 0,
            "name" => '',
          );
        }

        // fetch carrier files
        $carrier_file = null;

        if ($this->fetch_carrier_files === true) {
          $file_directory = $this->carrier_file_directory();
          $carrier_file   = $this->presta_carrier_files->save_file($sales_order['id'], $file_directory);
        }

        $params = array(
          "carrier"          => $carrier,
          "carrier_file"     => basename($carrier_file),
          "currency"         => $currency,
          "delivery_address" => $address_delivery,
          "delivery_country" => $delivery_country,
          "invoice_address"  => $address_invoice,
          "invoice_country"  => $invoice_country,
          "order"            => $sales_order,
        );

        $this->convert_to_edi($params);
        $this->mark_as_fetched($sales_order);

        // write order to disk
        $this->write_to_file();
      }
      catch (Exception $e) {
        // Do nothing because we still want to try to create the other
        // order eventhough this failed
        $msg = "Myyntitilauksen {$sales_order['id']} luominen pupesoftiin epäonnistui";
        $this->logger->log($msg, $e);

        return false;
      }
    }

    $this->logger->log('---------End sales orders fetch---------');

    return true;
  }

  /**
   * Fetches PrestaShop order which are in $this->order_states state
   *
   * @return array
   */
  private function fetch_sales_orders() {
    $this->logger->log('Fetching sales orders');

    try {
      $states_str = implode('|', $this->fetch_statuses);
      $filters = array(
        'current_state' => "[{$states_str}]",
      );

      $this->logger->log("Haetaan tilaukset rajauksella current_state = {$states_str}");

      $id_group_shop = $this->shop_group_id();
      $sales_orders = $this->all(null, $filters, null, $id_group_shop);
    }
    catch (Exception $e) {
      return array();
    }

    $this->logger->log("Fetched ".count($sales_orders)." orders.");

    return $sales_orders;
  }

  /**
   * Converts presta array to pupesoft
   *
   * @param array   $presta_order
   * @return array
   */
  private function convert_to_edi($params) {
    $carrier          = $params["carrier"];
    $carrier_file     = $params["carrier_file"];
    $currency         = $params["currency"];
    $delivery_address = $params["delivery_address"];
    $delivery_country = $params["delivery_country"];
    $invoice_address  = $params["invoice_address"];
    $invoice_country  = $params["invoice_country"];
    $order            = $params["order"];

    $customer_params = array(
      "id_customer" => $order['id_customer'],
      "id_delivery" => $delivery_address['dni'],
      "id_invoice"  => $invoice_address['dni'],
    );

    // find pupsoft customer id
    $pupesoft_customer = $this->fetch_pupesoft_customer($customer_params);

    // choose pupesoft customer number
    if (!empty($pupesoft_customer['asiakasnro'])) {
      $pupesoft_customer_id = $pupesoft_customer['asiakasnro'];
    }
    elseif (!empty($pupesoft_customer['toim_ovttunnus'])) {
      $pupesoft_customer_id = $pupesoft_customer['toim_ovttunnus'];
    }
    elseif (!empty($pupesoft_customer['ovttunnus'])) {
      $pupesoft_customer_id = $pupesoft_customer['ovttunnus'];
    }
    else {
      $pupesoft_customer_id = '';
    }

    // we don't allow users to change their invoice address, use pupesoft's address instead
    if ($this->presta_changeable_invoice_address === false) {
      if (!empty($pupesoft_customer['laskutus_nimi'])) {
        $invoice_address = array(
          'address1'  => $pupesoft_customer['laskutus_osoite'],
          'city'      => $pupesoft_customer['laskutus_postitp'],
          'firstname' => $pupesoft_customer['laskutus_nimitark'],
          'lastname'  => $pupesoft_customer['laskutus_nimi'],
          'phone'     => '',
          'postcode'  => $pupesoft_customer['laskutus_postino'],
        );
      }
      else {
        $invoice_address = array(
          'address1'  => $pupesoft_customer['osoite'],
          'city'      => $pupesoft_customer['postitp'],
          'firstname' => $pupesoft_customer['nimitark'],
          'lastname'  => $pupesoft_customer['nimi'],
          'phone'     => $pupesoft_customer['puhelin'],
          'postcode'  => $pupesoft_customer['postino'],
        );
      }
    }

    $invoice_name = $this->cleanup_name($invoice_address['firstname'], $invoice_address['lastname']);
    $delivery_name = $this->cleanup_name($delivery_address['firstname'], $delivery_address['lastname']);

    // fetch order messages, implode into one string, and remove newlines.
    $order_messages = $this->presta_customer_messages->messages_by_order($order['id']);
    $order_message  = implode(' ', $order_messages);
    $order_message  = trim(preg_replace('/\s+/', ' ', $order_message));

    // empty edi_order
    $this->edi_order = '';
    $this->add_row("*IS from:721111720-1 to:IKH,ORDERS*id:{$order['id']} version:AFP-1.0 *MS");
    $this->add_row("*MS {$order['id']}");
    $this->add_row("*RS OSTOTIL");
    $this->add_row("NADSE:{$this->yhtiorow['ovttunnus']}");
    $this->add_row("OSTOTIL.OT_NRO:{$order['id']}");
    $this->add_row("OSTOTIL.OT_TILAUSTYYPPI:");
    $this->add_row("OSTOTIL.VERKKOKAUPPA:");
    $this->add_row("OSTOTIL.OT_VERKKOKAUPPA_ASIAKASNRO:");
    $this->add_row("OSTOTIL.OT_VERKKOKAUPPA_TILAUSVIITE:{$order['invoice_number']}");
    $this->add_row("OSTOTIL.OT_VERKKOKAUPPA_TILAUSNUMERO:");
    $this->add_row("OSTOTIL.OT_VERKKOKAUPPA_KOHDE:");
    $this->add_row("OSTOTIL.OT_LIITETIEDOSTO:{$carrier_file}");
    $this->add_row("OSTOTIL.OT_TILAUSAIKA:");
    $this->add_row("OSTOTIL.OT_KASITTELIJA:");
    $this->add_row("OSTOTIL.OT_TOIMITUSAIKA:");
    $this->add_row("OSTOTIL.OT_TOIMITUSTAPA:{$carrier['name']}");
    $this->add_row("OSTOTIL.OT_RAHTIVAPAA:{$carrier['is_free']}");
    $this->add_row("OSTOTIL.OT_TOIMITUSEHTO:");
    $this->add_row("OSTOTIL.OT_MAKSETTU:"); // complete tarkoittaa, että on jo maksettu
    $this->add_row("OSTOTIL.OT_MAKSUEHTO:{$order['payment']}");
    $this->add_row("OSTOTIL.OT_VIITTEEMME:");
    $this->add_row("OSTOTIL.OT_VIITTEENNE:");
    $this->add_row("OSTOTIL.OT_VEROMAARA:");
    $this->add_row("OSTOTIL.OT_SUMMA:{$order['total_paid_tax_incl']}");
    $this->add_row("OSTOTIL.OT_VALUUTTAKOODI:{$currency['iso_code']}");
    $this->add_row("OSTOTIL.OT_KLAUSUULI1:");
    $this->add_row("OSTOTIL.OT_KLAUSUULI2:");
    $this->add_row("OSTOTIL.OT_KULJETUSOHJE:");
    $this->add_row("OSTOTIL.OT_LAHETYSTAPA:");
    $this->add_row("OSTOTIL.OT_VAHVISTUS_FAKSILLA:");
    $this->add_row("OSTOTIL.OT_TILAUSVIESTI:{$order_message}");
    $this->add_row("OSTOTIL.OT_FAKSI:");
    $this->add_row("OSTOTIL.OT_ASIAKASNRO:{$pupesoft_customer_id}");
    $this->add_row("OSTOTIL.OT_YRITYS:");
    $this->add_row("OSTOTIL.OT_YHTEYSHENKILO:{$invoice_name}");
    $this->add_row("OSTOTIL.OT_KATUOSOITE:{$invoice_address['address1']}");
    $this->add_row("OSTOTIL.OT_POSTITOIMIPAIKKA:{$invoice_address['city']}");
    $this->add_row("OSTOTIL.OT_POSTINRO:{$invoice_address['postcode']}");
    $this->add_row("OSTOTIL.OT_YHTEYSHENKILONPUH:{$invoice_address['phone']}");
    $this->add_row("OSTOTIL.OT_YHTEYSHENKILONFAX:");
    $this->add_row("OSTOTIL.OT_MYYNTI_YRITYS:");
    $this->add_row("OSTOTIL.OT_MYYNTI_KATUOSOITE:");
    $this->add_row("OSTOTIL.OT_MYYNTI_POSTITOIMIPAIKKA:");
    $this->add_row("OSTOTIL.OT_MYYNTI_POSTINRO:");
    $this->add_row("OSTOTIL.OT_MYYNTI_MAAKOODI:");
    $this->add_row("OSTOTIL.OT_MYYNTI_YHTEYSHENKILO:");
    $this->add_row("OSTOTIL.OT_MYYNTI_YHTEYSHENKILONPUH:");
    $this->add_row("OSTOTIL.OT_MYYNTI_YHTEYSHENKILONFAX:");
    $this->add_row("OSTOTIL.OT_TOIMITUS_YRITYS:");
    $this->add_row("OSTOTIL.OT_TOIMITUS_NIMI:{$delivery_name}");
    $this->add_row("OSTOTIL.OT_TOIMITUS_KATUOSOITE:{$delivery_address['address1']}");
    $this->add_row("OSTOTIL.OT_TOIMITUS_POSTITOIMIPAIKKA:{$delivery_address['city']}");
    $this->add_row("OSTOTIL.OT_TOIMITUS_POSTINRO:{$delivery_address['postcode']}");
    $this->add_row("OSTOTIL.OT_TOIMITUS_MAAKOODI:{$delivery_country['iso_code']}");
    $this->add_row("OSTOTIL.OT_TOIMITUS_PUH:{$delivery_address['phone']}");
    $this->add_row("OSTOTIL.OT_TOIMITUS_EMAIL:");
    $this->add_row("*RE OSTOTIL");

    $order_rows = $order['associations']['order_rows'];

    if (isset($order_rows['order_rows'])) {
      $rows = $order_rows['order_rows'];
    }
    elseif (isset($order_rows['order_row'])) {
      $rows = $order_rows['order_row'];
    }
    else {
      throw new Exception("Tilaukselta ei löydy tilausrivejä, ei voida jatkaa");
    }

    // One row fix
    if (isset($rows['id'])) {
      $rows = array($rows);
    }

    // Add shipping costs
    $shipping_cost = $order['total_shipping_tax_excl']; // veroton hinta
    $shipping_product = $this->yhtiorow['rahti_tuotenumero'];

    if ($shipping_cost > 0 and isset($shipping_product)) {
      // "emulate" order row
      $rows[] = array(
        'product_name' => 'Rahti',
        'product_quantity' => 1,
        'product_reference' => $shipping_product,
        'unit_price_tax_excl' => $shipping_cost,
      );
    }

    $row_number = 0;

    foreach ($rows as $row) {
      $row_number += 1;

      // pack_rows on custom presta kenttä. Mikäli kyseessä on pack -tuote (tuoteperhe), niin
      // kentässä tulee lapsituotteiden tiedot muodossa "tuotekoodi1:hinta1;tuotekoodi1:hinta2..."
      $pack_rows = empty($row['pack_rows']) ? '' : $row['pack_rows'];

      $this->add_row("*RS OSTOTILRIV {$row_number}");
      $this->add_row("OSTOTILRIV.OTR_NRO:{$order['id']}");
      $this->add_row("OSTOTILRIV.OTR_RIVINRO:{$row_number}");
      $this->add_row("OSTOTILRIV.OTR_TOIMITTAJANRO:");
      $this->add_row("OSTOTILRIV.OTR_TUOTEKOODI:{$row['product_reference']}");
      $this->add_row("OSTOTILRIV.OTR_TUOTERAKENNE:{$pack_rows}");
      $this->add_row("OSTOTILRIV.OTR_NIMI:{$row['product_name']}");
      $this->add_row("OSTOTILRIV.OTR_TILATTUMAARA:{$row['product_quantity']}");
      $this->add_row("OSTOTILRIV.OTR_RIVISUMMA:");
      $this->add_row("OSTOTILRIV.OTR_OSTOHINTA:{$row['unit_price_tax_excl']}"); // veroton hinta
      $this->add_row("OSTOTILRIV.OTR_ALENNUS:0"); // prestan unit_price_tax_excl on nettohinta, joten laitetaan eksplisiittisesti alennus 0%
      $this->add_row("OSTOTILRIV.OTR_VEROKANTA:");
      $this->add_row("OSTOTILRIV.OTR_VIITE:");
      $this->add_row("OSTOTILRIV.OTR_OSATOIMITUSKIELTO:");
      $this->add_row("OSTOTILRIV.OTR_JALKITOIMITUSKIELTO:");
      $this->add_row("OSTOTILRIV.OTR_YKSIKKO:");
      $this->add_row("OSTOTILRIV.OTR_SALLITAANJT:0");
      $this->add_row("*RE  OSTOTILRIV {$row_number}");
    }

    $this->add_row("*ME");
    $this->add_row("*IE");
  }

  private function mark_as_fetched($sales_order) {

    try {
      //Basically what we want to do here is call update but since update()
      //and create() use generate_xml() we can not do it. Generate_xml()
      //is for converting pupesoft object to presta and for now we only have
      //presta objects so no conversion is needed. Thats why we call the
      //update_xml function.
      $id = $sales_order['id'];
      $existing_xml = $this->get_as_xml($id);
      $existing_xml->order->current_state = $this->fetched_status;
      $this->update_xml($id, $existing_xml);

      $order_history = array(
        'order_id'    => $id,
        'order_state' => $this->fetched_status,
      );

      $this->logger->log("Merkattiin tilaus {$id} tilaan {$this->fetched_status}.");

      $this->presta_order_histories->create($order_history);
    }
    catch (Exception $e) {
      $msg = "Tilauksen {$sales_order['id']} haetuksi merkkaaminen epäonnistui";
      $this->logger->log($msg, $e);

      throw $e;
    }
  }

  private function add_row($value) {
    $this->edi_order .= "{$value}\n";
  }

  private function write_to_file() {
    $rnd  = md5(uniqid(rand(), true));
    $date = date("Ymd");
    $filepath = "{$this->edi_filepath_base}/presta-order-{$date}-{$rnd}.txt";

    // write file
    file_put_contents($filepath, $this->edi_order);

    // empty variable
    $this->edi_order = '';

    $this->logger->log("Tallennettiin tiedosto {$filepath}");

    return true;
  }

  private function carrier_file_directory() {
    $file_directory = "{$this->edi_filepath_base}/liitetiedostot";

    if (!is_writable($file_directory)) {
      throw new Exception("{$file_directory} ei pysty kirjoittamaan");
    }

    return "{$this->edi_filepath_base}/liitetiedostot";
  }

  private function cleanup_name($firstname, $lastname) {
    $firstname = trim($firstname);
    $lastname = trim($lastname);

    if (empty($firstname) or $firstname == '-') {
      return $lastname;
    }

    return "${lastname} ${firstname}";
  }

  private function fetch_pupesoft_customer($params) {
    $id_customer = $params['id_customer'];
    $id_delivery = $params['id_delivery'];
    $id_invoice  = $params['id_invoice'];

    // if we have pupesoft customer id in delivery address
    $pupesoft_customer = presta_hae_asiakas_tunnuksella($id_delivery);

    if (!empty($pupesoft_customer)) {
      $this->logger->log("Asiakkaan toimitusosoitteen Pupesoft asiakastunnus {$id_delivery}");

      return $pupesoft_customer;
    }

    // if we have pupesoft customer id in invoice address
    $pupesoft_customer = presta_hae_asiakas_tunnuksella($id_invoice);

    if (!empty($pupesoft_customer)) {
      $this->logger->log("Asiakkaan laskutusosoitteen Pupesoft asiakastunnus {$id_invoice}");

      return $pupesoft_customer;
    }

    // find customer with ulkoinen asiakasnumero
    $pupesoft_customer = presta_hae_yhteyshenkilon_asiakas_ulkoisella_asiakasnumerolla($id_customer);

    if (!empty($pupesoft_customer)) {
      $this->logger->log("PrestaShop asiakkaan {$id_customer} Pupesoft asiakastunnus {$pupesoft_customer['tunnus']}");

      return $pupesoft_customer;
    }

    $msg = "Asiakasta {$id_customer} ei löytynyt Pupesoftista! ";

    $id = $this->verkkokauppa_customer;

    if (empty($id)) {
      $msg .= "Oletus verkkokauppa-asiakasta ei ole asetettu! Tilausta ei voida hakea!";

      throw new Exception($msg);
    }

    $msg .= "Käytetään oletusasiakasta {$id}.";
    $this->logger->log($msg);

    $pupesoft_customer = hae_asiakas($id);

    if (empty($pupesoft_customer)) {
      $msg = "Oletusasiakasta {$id} ei löytynyt Pupesoftista!";

      throw new Exception($msg);
    }

    return $pupesoft_customer;
  }
}
