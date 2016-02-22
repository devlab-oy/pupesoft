<?php

require_once 'rajapinnat/presta/presta_client.php';
require_once 'rajapinnat/presta/presta_customers.php';
require_once 'rajapinnat/presta/presta_order_histories.php';

class PrestaSalesOrders extends PrestaClient {
  private $edi_filepath_base = '';
  private $edi_order = '';
  private $fetch_statuses = array();
  private $fetched_status = null;
  private $verkkokauppa_customer = null;
  private $yhtiorow = array();

  public function __construct($url, $api_key) {
    parent::__construct($url, $api_key);
  }

  protected function resource_name() {
    return 'orders';
  }

  protected function generate_xml($sales_order, SimpleXMLElement $existing_sales_order = null) {
    die('You should not be here! Order update() or create() are not implemented');
  }

  public function set_edi_filepath($filepath) {
    $this->edi_filepath_base = rtrim($filepath, '/');
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

  public function transfer_orders_to_pupesoft() {
    $this->logger->log('---------Start sales orders fetch---------');

    if ($this->edi_filepath_base == '') {
      throw new Exception('Edi tiedosto polku pitää olla määritelty');
    }

    if (empty($this->yhtiorow)) {
      throw new Exception('Yhtiorow on tyhjä');
    }

    $sales_orders = $this->fetch_sales_orders();

    $total = count($sales_orders);
    $current = 0;

    foreach ($sales_orders as $sales_order) {
      $current++;
      $this->logger->log("[{$current}/{$total}] Myyntitilaus {$sales_order['id']}");

      try {
        $this->convert_to_edi($sales_order);
        $this->mark_as_fetched($sales_order);
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
  public function fetch_sales_orders() {
    $this->logger->log('Fetching sales orders');

    try {
      $states_str = implode('|', $this->fetch_statuses);
      $filters = array(
        'current_state' => "[{$states_str}]",
      );

      $this->logger->log("Haetaan tilaukset rajauksella current_state = {$states_str}");

      $sales_orders = $this->all(null, $filters);
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
  private function convert_to_edi($order) {
    $pupesoft_customer = hae_yhteyshenkilon_asiakas_ulkoisella_asiakasnumerolla($order['id_customer']);

    if (empty($pupesoft_customer)) {
      $msg = "Asiakasta {$order['id_customer']} ei löytynyt Pupesoftista! ";

      $id = $this->verkkokauppa_customer;

      if (empty($id)) {
        $msg .= "Oletus verkkokauppa-asiakasta ei ole asetettu! Tilausta ei voida hakea!";

        throw new Exception($msg);
      }

      $msg .= "Käytetään oletusasiakasta {$id}.";
      $this->logger->log($msg);

      $pupesoft_customer = hae_asiakas($id);
    }

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
    $this->add_row("OSTOTIL.OT_TILAUSAIKA:");
    $this->add_row("OSTOTIL.OT_KASITTELIJA:");
    $this->add_row("OSTOTIL.OT_TOIMITUSAIKA:");
    $this->add_row("OSTOTIL.OT_TOIMITUSTAPA:");
    $this->add_row("OSTOTIL.OT_TOIMITUSEHTO:");
    $this->add_row("OSTOTIL.OT_MAKSETTU:complete"); // tarkoittaa, että on jo maksettu
    $this->add_row("OSTOTIL.OT_MAKSUEHTO:{$order['payment']}");
    $this->add_row("OSTOTIL.OT_VIITTEEMME:");
    $this->add_row("OSTOTIL.OT_VIITTEENNE:");
    $this->add_row("OSTOTIL.OT_VEROMAARA:");
    $this->add_row("OSTOTIL.OT_SUMMA:{$order['total_paid_tax_incl']}");
    $this->add_row("OSTOTIL.OT_VALUUTTAKOODI:{$order['id_currency']}");
    $this->add_row("OSTOTIL.OT_KLAUSUULI1:");
    $this->add_row("OSTOTIL.OT_KLAUSUULI2:");
    $this->add_row("OSTOTIL.OT_KULJETUSOHJE:");
    $this->add_row("OSTOTIL.OT_LAHETYSTAPA:");
    $this->add_row("OSTOTIL.OT_VAHVISTUS_FAKSILLA:");
    $this->add_row("OSTOTIL.OT_FAKSI:");
    $this->add_row("OSTOTIL.OT_ASIAKASNRO:{$pupesoft_customer['asiakasnro']}");
    $this->add_row("OSTOTIL.OT_YRITYS:");
    $this->add_row("OSTOTIL.OT_YHTEYSHENKILO:{$pupesoft_customer['nimi']}");
    $this->add_row("OSTOTIL.OT_KATUOSOITE:{$pupesoft_customer['osoite']}");
    $this->add_row("OSTOTIL.OT_POSTITOIMIPAIKKA:{$pupesoft_customer['postitp']}");
    $this->add_row("OSTOTIL.OT_POSTINRO:{$pupesoft_customer['postino']}");
    $this->add_row("OSTOTIL.OT_YHTEYSHENKILONPUH:{$pupesoft_customer['puhelin']}");
    $this->add_row("OSTOTIL.OT_YHTEYSHENKILONFAX:{$pupesoft_customer['fax']}");
    $this->add_row("OSTOTIL.OT_MYYNTI_YRITYS:");
    $this->add_row("OSTOTIL.OT_MYYNTI_KATUOSOITE:");
    $this->add_row("OSTOTIL.OT_MYYNTI_POSTITOIMIPAIKKA:");
    $this->add_row("OSTOTIL.OT_MYYNTI_POSTINRO:");
    $this->add_row("OSTOTIL.OT_MYYNTI_MAAKOODI:");
    $this->add_row("OSTOTIL.OT_MYYNTI_YHTEYSHENKILO:");
    $this->add_row("OSTOTIL.OT_MYYNTI_YHTEYSHENKILONPUH:");
    $this->add_row("OSTOTIL.OT_MYYNTI_YHTEYSHENKILONFAX:");
    $this->add_row("OSTOTIL.OT_TOIMITUS_YRITYS:");
    $this->add_row("OSTOTIL.OT_TOIMITUS_NIMI:");
    $this->add_row("OSTOTIL.OT_TOIMITUS_KATUOSOITE:");
    $this->add_row("OSTOTIL.OT_TOIMITUS_POSTITOIMIPAIKKA:");
    $this->add_row("OSTOTIL.OT_TOIMITUS_POSTINRO:");
    $this->add_row("OSTOTIL.OT_TOIMITUS_MAAKOODI:");
    $this->add_row("OSTOTIL.OT_TOIMITUS_PUH:");
    $this->add_row("OSTOTIL.OT_TOIMITUS_EMAIL:");
    $this->add_row("*RE OSTOTIL");

    $rows = $order['associations']['order_rows']['order_rows'];

    // One row fix
    if (isset($rows['id'])) {
      $rows = array($rows);
    }

    $row_number = 0;

    foreach ($rows as $row) {
      $row_number += 1;

      $this->add_row("*RS OSTOTILRIV {$row_number}");
      $this->add_row("OSTOTILRIV.OTR_NRO:{$order['id']}");
      $this->add_row("OSTOTILRIV.OTR_RIVINRO:{$row_number}");
      $this->add_row("OSTOTILRIV.OTR_TOIMITTAJANRO:");
      $this->add_row("OSTOTILRIV.OTR_TUOTEKOODI:{$row['product_reference']}");
      $this->add_row("OSTOTILRIV.OTR_NIMI:{$row['product_name']}");
      $this->add_row("OSTOTILRIV.OTR_TILATTUMAARA:{$row['product_quantity']}");
      $this->add_row("OSTOTILRIV.OTR_RIVISUMMA:");
      $this->add_row("OSTOTILRIV.OTR_OSTOHINTA:{$row['unit_price_tax_excl']}"); // veroton hinta
      $this->add_row("OSTOTILRIV.OTR_ALENNUS:");
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

    // write order to disk
    $this->write_to_file();
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

      $presta_order_history = new PrestaOrderHistories($this->url(), $this->api_key());
      $presta_order_history->create($order_history);
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

    if (!is_writable(dirname($filepath))) {
      throw new Exception("{$filepath} ei pysty kirjoittamaan");
    }

    // write file
    file_put_contents($filepath, $this->edi_order);

    // empty variable
    $this->edi_order = '';

    return true;
  }
}
