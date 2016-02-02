<?php

require_once 'rajapinnat/presta/presta_client.php';
require_once 'rajapinnat/presta/presta_customers.php';
require_once 'rajapinnat/presta/presta_order_histories.php';
require_once 'rajapinnat/edi_presta.php';

class PrestaSalesOrders extends PrestaClient {
  const RESOURCE = 'orders';

  private $edi_filepath_base = '';
  private $yhtiorow = array();
  private $verkkokauppa_customer = null;
  private $fetch_statuses = array();
  private $fetched_status = null;

  public function __construct($url, $api_key) {
    parent::__construct($url, $api_key);
  }

  protected function resource_name() {
    return self::RESOURCE;
  }

  /**
   *
   * @param array   $sales_order
   * @param SimpleXMLElement $existing_sales_order
   * @return \SimpleXMLElement
   */
  protected function generate_xml($sales_order, SimpleXMLElement $existing_sales_order = null) {
    die('You should not be here! Order update() or create() are not implemented');
    $xml = new SimpleXMLElement($this->schema->asXML());

    if (!is_null($existing_sales_order)) {
      $xml = $existing_sales_order;
    }

    return $xml;
  }

  public function set_edi_filepath($filepath) {
    if (!empty($filepath) and substr($filepath, -1) != '/') {
      $filepath = $filepath . '/';
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

  /**
   * Gets all PrestaShop sales orders and saves them in EDI-format for pupesoft.
   * Marks saved sales orders as fetched and updates them to presta.
   *
   * @return boolean
   * @throws Exception
   */
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
      $this->logger->log("[{$current}/{$total}]");

      try {
        $pupesoft_order = $this->convert_for_pupesoft($sales_order);
        EdiPresta::create($pupesoft_order, $this->edi_filepath_base);

        $this->mark_as_fetched($sales_order);
      }
      catch (Exception $e) {
        //Do nothing because we still want to try to create the other
        //order eventhough this failed
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
  private function convert_for_pupesoft($presta_order) {
    $pupesoft_customer = hae_yhteyshenkilon_asiakas_ulkoisella_asiakasnumerolla($presta_order['id_customer']);

    if (empty($pupesoft_customer)) {
      $msg = "Asiakasta {$presta_order['id_customer']} ei löytynyt Pupesoftista! ";

      $id = $this->verkkokauppa_customer;

      if (empty($id)) {
        $msg .= "Oletus verkkokauppa-asiakasta ei ole asetettu! Tilausta ei voida hakea!";

        throw new Exception($msg);
      }

      $msg .= "Käytetään oletusasiakasta {$id}.";
      $this->logger->log($msg);

      $pupesoft_customer = hae_asiakas($id);
    }

    $shipping_tax = ($presta_order['total_shipping_tax_incl'] - $presta_order['total_shipping_tax_excl']);
    $tax_amount   = ($presta_order['total_paid_tax_incl'] - $presta_order['total_paid_tax_excl']);

    $pupesoft_order = array();
    $pupesoft_order['alv_maara']          = $tax_amount;
    $pupesoft_order['asiakasnro']         = $pupesoft_customer['asiakasnro'];
    $pupesoft_order['email']              = $pupesoft_customer['email'];
    $pupesoft_order['external_system_id'] = $presta_order['id'];
    $pupesoft_order['fax']                = $pupesoft_customer['fax'];
    $pupesoft_order['liitostunnus']       = $pupesoft_customer['tunnus'];
    $pupesoft_order['maksettu']           = 'complete'; // tarkoittaa, että on jo maksettu
    $pupesoft_order['maksuehto']          = $presta_order['payment'];
    $pupesoft_order['puhelin']            = $pupesoft_customer['puhelin'];
    $pupesoft_order['rahti_vero_maara']   = $shipping_tax;
    $pupesoft_order['rahti_veroton']      = $presta_order['total_shipping_tax_excl'];
    $pupesoft_order['summa']              = $presta_order['total_paid_tax_incl'];
    $pupesoft_order['tilausrivit']        = array();
    $pupesoft_order['tilaustyyppi']       = '';
    $pupesoft_order['toimitustapa']       = '';
    $pupesoft_order['valkoodi']           = $presta_order['id_currency'];
    $pupesoft_order['viite']              = $presta_order['invoice_number'];
    $pupesoft_order['yhtio_ovttunnus']    = $this->yhtiorow['ovttunnus'];

    if (empty($pupesoft_customer['laskutus_osoite'])) {
      $pupesoft_order['laskutus_nimi']    = $pupesoft_customer['nimi'];
      $pupesoft_order['laskutus_osoite']  = $pupesoft_customer['osoite'];
      $pupesoft_order['laskutus_postitp'] = $pupesoft_customer['postitp'];
      $pupesoft_order['laskutus_postino'] = $pupesoft_customer['postino'];
      $pupesoft_order['laskutus_maa']     = $pupesoft_customer['maa'];
    }
    else {
      $pupesoft_order['laskutus_nimi']    = $pupesoft_customer['laskutus_nimi'];
      $pupesoft_order['laskutus_osoite']  = $pupesoft_customer['laskutus_osoite'];
      $pupesoft_order['laskutus_postitp'] = $pupesoft_customer['laskutus_postitp'];
      $pupesoft_order['laskutus_postino'] = $pupesoft_customer['laskutus_postino'];
      $pupesoft_order['laskutus_maa']     = $pupesoft_customer['laskutus_maa'];
    }

    if (empty($pupesoft_customer['toim_osoite'])) {
      $pupesoft_order['toim_nimi']    = $pupesoft_customer['nimi'];
      $pupesoft_order['toim_osoite']  = $pupesoft_customer['osoite'];
      $pupesoft_order['toim_postitp'] = $pupesoft_customer['postitp'];
      $pupesoft_order['toim_postino'] = $pupesoft_customer['postino'];
      $pupesoft_order['toim_maa']     = $pupesoft_customer['maa'];
      $pupesoft_order['toim_puhelin'] = $pupesoft_customer['puhelin'];
    }
    else {
      $pupesoft_order['toim_nimi']    = $pupesoft_customer['toim_nimi'];
      $pupesoft_order['toim_osoite']  = $pupesoft_customer['toim_osoite'];
      $pupesoft_order['toim_postitp'] = $pupesoft_customer['toim_postitp'];
      $pupesoft_order['toim_postino'] = $pupesoft_customer['toim_postino'];
      $pupesoft_order['toim_maa']     = $pupesoft_customer['toim_maa'];
      $pupesoft_order['toim_puhelin'] = $pupesoft_customer['puhelin'];
    }

    $rows = $presta_order['associations']['order_rows']['order_rows'];

    // One row fix
    if (isset($rows['id'])) {
      $rows = array($rows);
    }

    foreach ($rows as $row) {
      $pupesoft_row = array();

      $pupesoft_row['tuoteno']                 = $row['product_reference'];
      $pupesoft_row['nimitys']                 = $row['product_name'];
      $pupesoft_row['tilkpl']                  = $row['product_quantity'];
      $pupesoft_row['verollinen_yksikkohinta'] = $row['unit_price_tax_incl'];
      $pupesoft_row['veroton_yksikkohinta']    = $row['product_price'];
      $pupesoft_row['alennusprosentti']        = 0;
      $pupesoft_row['alv_prosentti']           = $presta_order['carrier_tax_rate'];

      $pupesoft_order['tilausrivit'][] = $pupesoft_row;
    }

    $msg = "Tilaus {$presta_order['id']} haettu, ";
    $msg .= count($pupesoft_order['tilausrivit']). " tilausriviä ";
    $this->logger->log($msg);

    return $pupesoft_order;
  }

  /**
   *
   * @param array   $sales_order
   * @throws Exception
   */
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
}
