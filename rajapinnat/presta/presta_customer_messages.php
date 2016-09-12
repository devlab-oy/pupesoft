<?php

require_once 'rajapinnat/presta/presta_client.php';
require_once 'rajapinnat/presta/presta_customer_threads.php';

class PrestaCustomerMessages extends PrestaClient {
  private $presta_customer_threads = null;

  public function __construct($url, $api_key, $log_file) {
    parent::__construct($url, $api_key, $log_file);

    $this->presta_customer_threads = new PrestaCustomerThreads($url, $api_key, $log_file);
  }

  public function messages_by_order($order_id) {
    $message_ids = array();
    $messages = array();

    $thread_ids = $this->presta_customer_threads->find_by_order($order_id);

    // no threads for this order
    if (count($thread_ids) == 0) {
      return $messages;
    }

    foreach ($thread_ids as $id) {
      $ids = $this->presta_customer_threads->messages_by_thread($id);

      $message_ids = array_merge($message_ids, $ids);
    }

    // no messages in these thread
    if (count($message_ids) == 0) {
      return $messages;
    }

    foreach ($message_ids as $id) {
      $message = $this->get($id);

      $messages[] = $message['message'];
    }

    return $messages;
  }

  protected function resource_name() {
    return 'customer_messages';
  }

  protected function generate_xml($record, SimpleXMLElement $existing_record = null) {
    throw new Exception('You shouldnt be here, CRUD is not implemented!');
  }
}
