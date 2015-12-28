<?php

require_once 'rajapinnat/logger.php';
require_once 'PSWebServiceLibrary.php';

abstract class PrestaClient {

  private $url = null;
  private $api_key = null;

  /**
   *
   * @var PrestaShopWebservice REST-client
   */
  protected $ws = null;

  /**
   * Schema is used to create / update a resource.
   * It contains given resource blank xml schema
   * @var SimpleXML
   */
  protected $schema = null;

  /**
   *
   * @var Logger
   */
  protected $logger = null;

  public function __construct($url, $api_key) {
    if (empty($url)) {
      throw new Exception('Presta URL puuttuu');
    }
    if (empty($api_key)) {
      throw new Exception('Presta API key puuttuu');
    }

    $this->logger = new Logger('/tmp/presta_log.txt');
    $this->logger->set_date_format('Y-m-d H:i:s');

    if (substr($url, -1) == '/') {
      $url = substr($url, 0, -1);
    }
    $this->url = $url;
    $this->api_key = $api_key;
    $this->ws = new PrestaShopWebservice($this->url, $this->api_key);
  }

  /**
   *
   * @return SimpleXMLElement
   * @throws Exception
   */
  protected function get_empty_schema() {
    $resource = $this->resource_name();
    $opt = array(
        'resource' => "{$resource}?schema=blank"
    );

    try {
      $schema = $this->ws->get($opt);
      $msg = "Resurssin {$resource} empty schema haettu";
      $this->logger->log($msg);
    }
    catch (Exception $e) {
      $msg = "Resurssin {$resource} empty schema GET ep�onnistui";
      $this->logger->log($msg, $e);
      throw $e;
    }

    return $schema;
  }

  /**
   * Use $this-> instead of parent:: if you are going to override this class get()
   *
   * @param int $id
   * @return array
   * @throws Exception
   */
  protected function get($id) {
    try {
      $response_xml = $this->get_as_xml($id);
    }
    catch (Exception $e) {
      throw $e;
    }

    /**
     * Hackhack...presta web service returns the fetched record wrapped in
     * its resource name:
     * $c = array(
     *  'customer' => array(
     *      'id' =>1,
     *       ...
     * )
     * );
     *
     * Basically this means all the fetched records are one level too deep.
     * Remove the unnecessary level
     */
    $response = xml_to_array($response_xml);
    $keys = array_keys($response);
    if (isset($keys[0])) {
      $response = $response[$keys[0]];
    }

    return $response;
  }

  /**
   *
   * @param int $id
   * @return SimpleXMLElement
   * @throws Exception
   */
  protected function get_as_xml($id) {
    $resource = $this->resource_name();
    $opt = array(
        'resource' => $resource,
        'id'       => $id,
    );

    try {
      $response_xml = $this->ws->get($opt);
      $msg = "Resurssin {$resource} {$id} haettu";
      $this->logger->log($msg);
    }
    catch (Exception $e) {
      $msg = "Resurssin: {$resource} {$id} haku ep�onnistui";
      $this->logger->log($msg, $e);
      throw $e;
    }

    return $response_xml;
  }

  /**
   * Creates the given resource
   *
   * @param array $resource
   * @return array
   * @throws Exception
   */
  protected function create(array $resource) {
    $opt = array(
        'resource' => $this->resource_name()
    );

    try {
      if (empty($this->schema)) {
        $this->schema = $this->get_empty_schema();
      }
      $opt['postXml'] = $this->generate_xml($resource)->asXML();
      $response_xml = $this->ws->add($opt);
      //@TODO Resource IDENTIFIER to log message
      $this->logger->log("Luotiin resurssi:" . $this->resource_name());
    }
    catch (Exception $e) {
      $msg = "Resurssin " . $this->resource_name() . " luonti ep�onnistui";
      $this->logger->log($msg, $e);
      throw $e;
    }

    return xml_to_array($response_xml);
  }

  /**
   * Updates given resource
   *
   * @param int $id
   * @param array $resource
   * @return array
   * @throws Exception
   */
  protected function update($id, array $resource) {
    //@TODO pit��k� t�� blokki olla my�s try catchin sis�ll�??
    $existing_resource = $this->get_as_xml($id);
    if (empty($this->schema)) {
      $this->schema = $this->get_empty_schema();
    }
    $xml = $this->generate_xml($resource, $existing_resource);

    return $this->update_xml($id, $xml);
  }

  /**
   * Updates given resource straight from the given xml
   * Xml needs to be in Presta format.
   * This is used for example in PrestaSalesOrders
   *
   * @param int $id
   * @param SimpleXMLElement $xml
   * @return array
   * @throws Exception
   */
  protected function update_xml($id, SimpleXMLElement $xml) {
    $opt = array(
        'id'       => $id,
        'resource' => $this->resource_name(),
    );

    try {
      $opt['putXml'] = $xml->asXML();
      $response_xml = $this->ws->edit($opt);
      //@TODO Resource IDENTIFIER to log message
      $this->logger->log("P�ivitettiin resurssi: " . $this->resource_name());
    }
    catch (Exception $e) {
      $msg = "Resurssin: "
              . $this->resource_name()
              . " {$id} p�ivitt�minen ep�onnistui";
      $this->logger->log($msg, $e);
      throw $e;
    }

    return xml_to_array($response_xml);
  }

  /**
   * $display = array('id','name');
   * $filter = array('name'=>'John');
   *
   * @param array $display Defines SELECT columns
   * @param array $filters adds WHERE statements. Needs to be key/value pair
   * @return array
   * @throws Exception
   */
  protected function all($display = array(), $filters = array()) {
    $resource = $this->resource_name();
    $opt = array(
        'resource' => $resource,
    );

    if (!empty($display)) {
      $display = '[' . implode(',', $display) . ']';
    }
    else {
      $display = 'full';
    }
    $opt['display'] = $display;

    foreach ($filters as $column_key => $value) {
      $key = "filter[{$column_key}]";
      $opt[$key] = "[{$value}]";
    }

    try {
      $response_xml = $this->ws->get($opt);
      $msg = "Resurssin {$resource} kaikki rivit haettu";
      $this->logger->log($msg);
    }
    catch (Exception $e) {
      $msg = "Kaikkien resurssin "
              . $resource
              . " rivien haku ep�onnistui";
      $this->logger->log($msg, $e);
      throw $e;
    }

    /**
     * Hackhack...presta web service returns the fetched records wrapped in
     * its resource name:
     * $c = array(
     *  'customers' => array(
     *    'customer' => array(
     *      array('id'=>1,'name'=>'A'),
     *      array('id'=>2,'name'=>'B'),
     *    )
     *  )
     * );
     *
     * Basically this means all the fetched records are two level too deep.
     * Remove the unnecessary levels
     */
    $response = xml_to_array($response_xml);
    $keys = array_keys($response);
    if (isset($keys[0])) {
      $response = $response[$keys[0]];
    }
    $keys = array_keys($response);
    if (isset($keys[0])) {
      $response = $response[$keys[0]];
    }

    /**
     * Each Presta record contains id field. Check for it and if its found return
     * the record inside an array. All should allways return many. Not one.
     * This has to be done due to the nature of XML
     */
    if (isset($response['id'])) {
      $response = array($response);
    }

    return $response;
  }

  /**
   *
   * @param int $id
   * @return boolean
   * @throws Exception
   */
  protected function delete($id) {
    $opt = array(
        'resource' => $this->resource_name(),
        'id'       => $id,
    );

    try {
      $response_bool = $this->ws->delete($opt);
      $msg = "Resurssin " . $this->resource_name() . " {$id} poistettu";
      $this->logger->log($msg);
    }
    catch (Exception $e) {
      $msg = "Resurssin: "
              . $this->resource_name()
              . " {$id} poistaminen ep�onnistui";
      $this->logger->log($msg, $e);
      throw $e;
    }

    return $response_bool;
  }

  protected function delete_all() {
    $this->logger->log('---------Start ' . $this->resource_name() . ' delete all---------');
    $existing_resources = $this->all(array('id'));
    $existing_resources = array_column($existing_resources, 'id');

    foreach ($existing_resources as $id) {
      if ($id == 1 and $this->resource_name() == 'categories') {
        //Root category can not be deleted
        continue;
      }
      try {
        $this->delete($id);
      }
      catch (Exception $e) {

      }
    }

    $this->logger->log('Kaikki ' . $this->resource_name() . ' poistettu');
    $this->logger->log('---------End ' . $this->resource_name() . ' delete all---------');
  }

  /**
   *
   * @param int $id
   * @param array $image
   * @return array
   * @throws Exception
   */
  protected function create_resource_image($id, $image) {
    $opt = array(
        'resource'   => $this->resource_name(),
        'id'         => $id,
        'attachment' => $image,
        'method'     => 'POST'
    );
    try {
      $response = $this->ws->executeImageRequest($opt);

      $msg = "Luotiin resurssille: " . $this->resource_name() . " kuva {$id}";
      $this->logger->log($msg);
    }
    catch (Exception $e) {
      $msg = "Resurssin:"
              . $this->resource_name()
              . " {$id} kuvan luonti ep�onnistui";
      $this->logger->log($msg, $e);
      throw $e;
    }

    return $response;
  }

  /**
   *
   * @param int $id
   * @return array
   * @throws Exception
   */
  protected function get_resource_images($id) {
    $image_ids = array();
    $opt = array(
        'resource' => 'images/' . $this->resource_name(),
        'id'       => $id,
    );

    try {
      $response_xml = $this->ws->get($opt);
      foreach ($response_xml->image->declination as $node) {
        foreach ($node->attributes() as $key => $value) {
          if ($key == 'id') {
            $image_ids[] = (string) $value;
          }
        }
      }

      //For some reason API gives duplicate ids sometimes
      $image_ids = array_unique($image_ids);
    }
    catch (Exception $e) {
      $msg = "Resurssin: "
              . $this->resource_name()
              . " {$id} kuvien haku ep�onnistui."
              . " Jos kyseess� HTTP code 500 tarkoittaa se"
              . ", ett� resurssille ei l�ytynyt kuvia.";
      $this->logger->log($msg, $e);
      throw $e;
    }

    return $image_ids;
  }

  /**
   *
   * @param int $resouce_id
   * @param int $image_id
   * @return boolean
   * @throws Exception
   */
  protected function delete_resource_image($resouce_id, $image_id) {
    $opt = array(
        'url' => "{$this->url}/api/images/" . $this->resource_name() . "/{$resouce_id}/{$image_id}",
    );

    try {
      $response = $this->ws->delete($opt);
    }
    catch (Exception $e) {
      $msg = "Resurssin: " . $this->resource_name() . " {$resouce_id}"
              . " kuvan {$image_id} poistaminen ep�onnistui"
              . "url: {$opt['url']}";
      $this->logger->log($msg, $e);
      throw $e;
    }

    return $response;
  }

  /**
   *
   * @return string
   */
  protected function url() {
    return $this->url;
  }

  /**
   *
   * @return string
   */
  protected function api_key() {
    return $this->api_key;
  }

  /**
   * Sanitezes string for presta link_rewrite column
   *
   * @param string $string
   * @return string
   */
  protected function saniteze_link_rewrite($string) {
    return preg_replace('/[^a-zA-Z0-9]/', '', $string);
  }

  //Child has to implement function which returns schema=blank or repopulated xml
  protected abstract function generate_xml($resource, SimpleXMLElement $existing_resource = null);

  //Child needs to implement function which return resource as string: 'products'...
  protected abstract function resource_name();
}
