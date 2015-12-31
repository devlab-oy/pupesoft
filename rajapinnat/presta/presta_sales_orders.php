<?php

require_once 'rajapinnat/presta/presta_client.php';
require_once 'rajapinnat/presta/presta_customers.php';
require_once 'rajapinnat/presta/presta_order_histories.php';
require_once 'rajapinnat/edi_presta.php';

class PrestaSalesOrders extends PrestaClient {

  const RESOURCE = 'orders';

  /**
   * State to be uploaded to presta when order is fetched and saved to pupesoft
   */
  const FETCHED = 3;

  /**
   * Presta order states which are fetched in fetch_sales_orders()
   *
   * @var array
   */
  protected $order_states = array();

  /**
   * Order states which indicate that presta order is not yet paid
   *
   * @var array
   */
  private $not_paid_order_states = array();
  private $paid_order_states = array();

  /**
   * Filepath base where edi files are saved
   *
   * @var string
   */
  private $edi_filepath_base = '';
  private $yhtiorow = array();

  public function __construct($url, $api_key) {
    parent::__construct($url, $api_key);

    /**
     *
      1  Awaiting cheque payment
      2  Payment accepted
      3  Preparation in progress
      4  Shipped
      5  Delivered
      6  Canceled
      7  Refund
      8  Payment error
      9  On backorder
      10  Awaiting bank wire payment
      11  Awaiting PayPal payment
      12  Remote payment accepted
     */
    $this->order_states = array(1, 2, 10, 11, 12);

    $this->not_paid_order_states = array(1, 10, 11);
    $this->paid_order_states = array(2, 12);
  }

  protected function resource_name() {
    return self::RESOURCE;
  }

  /**
   *
   * @param array $sales_order
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

  /**
   * Gets all PrestaShop sales orders and saves them in EDI-format for pupesoft.
   * Marks saved sales orders as fetched and updates them to presta.
   *
   * @return boolean
   * @throws Exception
   */
  public function transfer_orders_to_pupesoft() {
    if ($this->edi_filepath_base == '') {
      throw new Exception('Edi tiedosto polku pitää olla määritelty');
    }

    if (empty($this->yhtiorow)) {
      throw new Exception('Yhtiorow on tyhjä');
    }

    $sales_orders = $this->fetch_sales_orders();

    foreach ($sales_orders as $sales_order) {
      try {
        $pupesoft_order = $this->convert_for_pupesoft($sales_order);
        EdiPresta::create($pupesoft_order, $this->edi_filepath_base);

        $this->mark_as_fetched($sales_order);
      }
      catch (Exception $e) {
        //Do nothing because we still want to try to create the other
        //order eventhough this failed
        $msg = "Myyntitilauksen {$sales_order['id']} luominen "
                . "pupesoftiin epäonnistui";
        $this->logger->log($msg, $e);

        return false;
      }
    }

    return true;
  }

  /**
   * Fetches PrestaShop order which are in $this->order_states state
   *
   * @return array
   */
  public function fetch_sales_orders() {
    $this->logger->log('---------Start sales orders fetch---------');

    try {
      //@TODO Consider if order_states should be synced aswell.
      //For now we dont do it.
      $states_str = implode('|', $this->order_states);
      $display = array();
      $filters = array(
          'current_state' => "[{$states_str}]",
      );

      $sales_orders = $this->all($display, $filters);
    }
    catch (Exception $e) {
      return array();
    }

    $this->logger->log('---------End sales orders fetch---------');

    return $sales_orders;
  }

  /**
   * Converts presta array to pupesoft
   *
   * @param array $presta_order
   * @return array
   */
  private function convert_for_pupesoft($presta_order) {
    $pupesoft_customer = hae_yhteyshenkilon_asiakas_ulkoisella_asiakasnumerolla($presta_order['id_customer']);
    if (empty($pupesoft_customer)) {
      //@TODO Tässä pitää hakea $pupesoft_customer = verkkokauppa_asiakas();
      //tai jotain vastaavaa
      $pupesoft_customer = hae_asiakas(70201);
    }

    $pupesoft_order = array();
    $pupesoft_order['yhtio_ovttunnus'] = $this->yhtiorow['ovttunnus'];
    $pupesoft_order['liitostunnus'] = $pupesoft_customer['tunnus'];
    $pupesoft_order['asiakasnro'] = $pupesoft_customer['asiakasnro'];
    $pupesoft_order['viite'] = $presta_order['invoice_number'];
    $pupesoft_order['external_system_id'] = $presta_order['id'];
    $pupesoft_order['toimitustapa'] = '';
    $pupesoft_order['maksettu'] = '';
    $pupesoft_order['maksuehto'] = $presta_order['payment'];
    $pupesoft_order['alv_maara'] = ($presta_order['total_paid_tax_incl'] - $presta_order['total_paid_tax_excl']);
    $pupesoft_order['summa'] = $presta_order['total_paid_tax_incl'];
    $pupesoft_order['valkoodi'] = $presta_order['id_currency'];

    if (empty($pupesoft_customer['laskutus_osoite'])) {
      $pupesoft_order['laskutus_nimi'] = $pupesoft_customer['nimi'];
      $pupesoft_order['laskutus_osoite'] = $pupesoft_customer['osoite'];
      $pupesoft_order['laskutus_postitp'] = $pupesoft_customer['postitp'];
      $pupesoft_order['laskutus_postino'] = $pupesoft_customer['postino'];
      $pupesoft_order['laskutus_maa'] = $pupesoft_customer['maa'];
    }
    else {
      $pupesoft_order['laskutus_nimi'] = $pupesoft_customer['laskutus_nimi'];
      $pupesoft_order['laskutus_osoite'] = $pupesoft_customer['laskutus_osoite'];
      $pupesoft_order['laskutus_postitp'] = $pupesoft_customer['laskutus_postitp'];
      $pupesoft_order['laskutus_postino'] = $pupesoft_customer['laskutus_postino'];
      $pupesoft_order['laskutus_maa'] = $pupesoft_customer['laskutus_maa'];
    }

    $pupesoft_order['puhelin'] = $pupesoft_customer['puhelin'];
    $pupesoft_order['fax'] = $pupesoft_customer['fax'];

    if (empty($pupesoft_customer['toim_osoite'])) {
      $pupesoft_order['toim_nimi'] = $pupesoft_customer['nimi'];
      $pupesoft_order['toim_osoite'] = $pupesoft_customer['osoite'];
      $pupesoft_order['toim_postitp'] = $pupesoft_customer['postitp'];
      $pupesoft_order['toim_postino'] = $pupesoft_customer['postino'];
      $pupesoft_order['toim_maa'] = $pupesoft_customer['maa'];
      $pupesoft_order['toim_puhelin'] = $pupesoft_customer['puhelin'];
    }
    else {
      $pupesoft_order['toim_nimi'] = $pupesoft_customer['toim_nimi'];
      $pupesoft_order['toim_osoite'] = $pupesoft_customer['toim_osoite'];
      $pupesoft_order['toim_postitp'] = $pupesoft_customer['toim_postitp'];
      $pupesoft_order['toim_postino'] = $pupesoft_customer['toim_postino'];
      $pupesoft_order['toim_maa'] = $pupesoft_customer['toim_maa'];
      $pupesoft_order['toim_puhelin'] = $pupesoft_customer['puhelin'];
    }

    $pupesoft_order['email'] = $pupesoft_customer['email'];
    $pupesoft_order['rahti_veroton'] = $presta_order['total_shipping_tax_excl'];
    $shipping_tax = ($presta_order['total_shipping_tax_incl'] - $presta_order['total_shipping_tax_excl']);
    $pupesoft_order['rahti_vero_maara'] = $shipping_tax;

    if (in_array($presta_order['current_state'], $this->not_paid_order_states)) {
      $pupesoft_order['tilaustyyppi'] = 'maksamatta';
    }
    elseif (in_array($presta_order['current_state'], $this->paid_order_states)) {
      $pupesoft_order['tilaustyyppi'] = 'maksettu';
    }
    else {
      $msg = "Haettu tilaus on tilassa {$presta_order['current_state']},"
              . " joka ei ole tuettujen tilojen joukossa " . implode(',', $this->order_states);
      throw new Exception($msg);
    }

    $pupesoft_order['tilausrivit'] = array();

    $rows = $presta_order['associations']['order_rows']['order_rows'];
    //One row fix
    if (isset($rows['id'])) {
      $rows = array($rows);
    }

    foreach ($rows as $row) {
      $pupesoft_row = array();
      $pupesoft_row['tuoteno'] = $row['product_reference'];
      $pupesoft_row['nimitys'] = $row['product_name'];
      $pupesoft_row['tilkpl'] = $row['product_quantity'];
      $pupesoft_row['verollinen_yksikkohinta'] = $row['unit_price_tax_incl'];
      $pupesoft_row['veroton_yksikkohinta'] = $row['product_price'];
      $pupesoft_row['alennusprosentti'] = 0;
      $pupesoft_row['alv_prosentti'] = $presta_order['carrier_tax_rate'];

      $pupesoft_order['tilausrivit'][] = $pupesoft_row;
    }

    return $pupesoft_order;
  }

  /**
   *
   * @param array $sales_order
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
      $existing_xml->order->current_state = self::FETCHED;
      $this->update_xml($id, $existing_xml);

      $order_history = array(
          'order_id'    => $id,
          'order_state' => self::FETCHED,
      );
      $presta_order_history = new PrestaOrderHistories($this->url(), $this->api_key());
      $presta_order_history->create($order_history);
    }
    catch (Exception $e) {
      $msg = "Tilauksen {$sales_order['id']} haetuksi merkkaaminen"
              . "epäonnistui";
      $this->logger->log($msg, $e);
      throw $e;
    }
  }
}
