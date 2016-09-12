<?php

require_once 'rajapinnat/presta/presta_client.php';

class PrestaCustomerThreads extends PrestaClient {
  public function __construct($url, $api_key, $log_file) {
    parent::__construct($url, $api_key, $log_file);
  }

  public function find_by_order($order_id) {
    $this->logger->log('Fetching message threads for order');

    $display = array('id');
    $filters = array('id_order' => $order_id);
    $id_group_shop = $this->shop_group_id();
    $thread_ids = array();

    try {
      $customer_threads = $this->all($display, $filters, null, $id_group_shop);
    }
    catch (Exception $e) {
      return $thread_ids;
    }

    foreach ($customer_threads as $thread) {
      $thread_ids[] = $thread['id'];
    }

    return $thread_ids;
  }

  protected function resource_name() {
    return 'customer_threads';
  }

  protected function generate_xml($record, SimpleXMLElement $existing_record = null) {
    throw new Exception('You shouldnt be here, CRUD is not implemented!');
  }
}
