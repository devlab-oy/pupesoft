<?php

require_once 'rajapinnat/presta/presta_client.php';

# HUOM, t�m� ei ole normaali Prestan API. T�m� on tehty customina Prestan p��h�n.
# Ei voida k�ytt�� perusasennuksissa.

class PrestaCarrierFiles extends PrestaClient {
  public function __construct($url, $api_key, $log_file) {
    parent::__construct($url, $api_key, $log_file);
  }

  protected function resource_name() {
    return 'carrier_file';
  }

  protected function generate_xml($resource, SimpleXMLElement $existing_resource = null) {
    throw new Exception('You shouldnt be here, CRUD is not implemented!');
  }
}
