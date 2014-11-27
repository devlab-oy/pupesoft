<?php

require_once 'rajapinnat/presta/presta_client.php';
require_once 'rajapinnat/presta/presta_customers.php';
require_once 'rajapinnat/edi.php';

class PrestaSalesOrders extends PrestaClient {

  const RESOURCE = 'orders';

  /**
   * State to be uploaded to presta when order is fetched and saved to pupesoft
   */
  const FETCHED = 1;

  /**
   * Presta order states which are fetched in fetch_sales_orders()
   * 
   * @var array
   */
  protected $order_states = array();

  public function __construct($url, $api_key) {
    parent::__construct($url, $api_key);

    //Tilattu = 3
    $this->order_states = array(3);
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

  /**
   * Gets all PrestaShop sales orders and saves them in EDI-format for pupesoft.
   * Marks saved sales orders as fetched and updates them to presta.
   */
  public function transfer_orders_to_pupesoft() {
    $sales_orders = $this->fetch_sales_orders();

    foreach ($sales_orders as $sales_order) {
      try {
        $pupesoft_order = $this->convert_for_pupesoft($sales_order);
        Edi::create($pupesoft_order);

        $this->mark_as_fetched($sales_order);
      }
      catch (Exception $e) {
        //Do nothing because we still want to try to create the other
        //order eventhough this failed
        $msg = "Myyntitilauksen {$sales_order['id']} luominen"
          . "pupesoftiin epäonnistui";
        $this->logger->log($msg, $e);
      }
    }
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
          'current_state' => "[{$states_str}]"
      );
      $sales_orders = $this->all($display, $filters);
    }
    catch (Exception $e) {
      return array();
    }

    $this->logger->log('---------End sales orders fetch---------');

    return $sales_orders;
  }

  private function convert_for_pupesoft($presta_order) {
    $presta_customer = new PrestaCustomers($this->get_url(), $this->get_api_key());
    $presta_customer = $presta_customer->get($presta_order['id_customer']);
    if (!empty($presta_customer['pupesoft_id'])) {
      $pupesoft_customer = hae_asiakas($presta_customer['pupesoft_id']);
    }
    else {
      //@TODO Tässä pitää hakea $pupesoft_customer = verkkokauppa_asiakas();
      //tai jotain vastaavaa
      $pupesoft_customer = hae_asiakas(70197);
    }

    $pupesoft_order = array();
    $pupesoft_order['customer_id'] = $presta_order['id_customer'];
    $pupesoft_order['reference_number'] = $presta_order['invoice_number'];
    $pupesoft_order['order_number'] = $presta_order['id'];
    $pupesoft_order['shipping_description'] = '';
    $pupesoft_order['status'] = '';
    $pupesoft_order['payment']['method'] = $presta_order['payment'];
    $pupesoft_order['tax_amount'] = ($presta_order['total_paid_tax_incl'] - $presta_order['total_paid_tax_excl']);
    $pupesoft_order['grand_total'] = $presta_order['total_paid_tax_incl'];
    $pupesoft_order['order_currency_code'] = $presta_order['id_currency'];

    $pupesoft_order['billing_address']['street'] = $pupesoft_customer['laskutus_osoite'];
    $pupesoft_order['billing_address']['city'] = $pupesoft_customer['laskutus_postitp'];
    $pupesoft_order['billing_address']['postcode'] = $pupesoft_customer['laskutus_postino'];
    $pupesoft_order['billing_address']['telephone'] = $pupesoft_customer['puhelin'];
    $pupesoft_order['billing_address']['fax'] = $pupesoft_customer['fax'];

    $pupesoft_order['shipping_address']['street'] = $pupesoft_customer['toim_osoite'];
    $pupesoft_order['shipping_address']['lastname'] = $pupesoft_customer['nimi'];
    $pupesoft_order['shipping_address']['city'] = $pupesoft_customer['toim_postitp'];
    $pupesoft_order['shipping_address']['postcode'] = $pupesoft_customer['toim_postino'];
    $pupesoft_order['shipping_address']['country_id'] = $pupesoft_customer['toim_maa'];
    $pupesoft_order['shipping_address']['telephone'] = $pupesoft_customer['puhelin'];

    $pupesoft_order['customer_email'] = '';
    $pupesoft_order['shipping_amount'] = $presta_order['total_shipping_tax_excl'];
    $shipping_tax = ($presta_order['total_shipping_tax_incl'] - $presta_order['total_shipping_tax_excl']);
    $pupesoft_order['shipping_tax_amount'] = $shipping_tax;


    $pupesoft_order['items'] = array();

    $rows = $presta_order['associations']['order_rows']['order_row'];
    //One row fix
    if (isset($rows['id'])) {
      $rows = array($rows);
    }

    foreach ($rows as $row) {
      $pupesoft_row = array();
      $pupesoft_row['product_id'] = $row['product_id'];
      $pupesoft_row['product_type'] = '';
      $pupesoft_row['sku'] = '';
      $pupesoft_row['name'] = $row['product_name'];
      $pupesoft_row['qty_ordered'] = $row['product_quantity'];
      $pupesoft_row['original_price'] = $row['unit_price_tax_incl'];
      $pupesoft_row['price'] = $row['product_price'];
      $pupesoft_row['discount_percent'] = 0;
      $pupesoft_row['tax_percent'] = $presta_order['carrier_tax_rate'];

      $pupesoft_order['items'][] = $pupesoft_row;
    }
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
    }
    catch (Exception $e) {
      $msg = "Tilauksen {$sales_order['id']} haetuksi merkkaaminen"
        . "epäonnistui";
      $this->logger->log($msg, $e);
      throw $e;
    }
  }
}
