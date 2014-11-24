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
   * Schema is used to create / update a resource. It contains given resource blank xml schema
   * @var SimpleXML
   */
  protected $schema = null;

  /**
   *
   * @var Logger
   */
  protected $logger = null;

  public function __construct($url, $api_key) {
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
   * Fetch empty xml schema for given resource
   *
   * @param string $resource
   * @return SimpleXMLElement
   * @throws Exception
   */
  protected function get_empty_schema($resource) {
    $opt = array(
        'resource' => "$resource?schema=blank"
    );

    try {
      $schema = $this->ws->get($opt);
    }
    catch (Exception $e) {
      $msg = "Resurssin {$resource} empty schema GET epäonnistui";
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
    $resource = $this->resource_name();
    $opt = array(
        'resource' => $resource,
        'id'       => $id,
    );

    try {
      $response_xml = $this->ws->get($opt);
    }
    catch (Exception $e) {
      $msg = "Resurssin: {$resource} {$id} haku epäonnistui";
      $this->logger->log($msg, $e);
      throw $e;
    }

    return xml_to_array($response_xml);
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
      $opt['postXml'] = $this->generate_xml($resource)->asXML();
      $response_xml = $this->ws->add($opt);
      //@TODO Resource IDENTIFIER to log message
      $this->logger->log("Luotiin resurssi:" . $this->resource_name());
    }
    catch (Exception $e) {
      $msg = "Resurssin " . $this->resource_name() . " luonti epäonnistui";
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
    $opt = array(
        'id'       => $id,
        'resource' => $this->resource_name()
    );

    try {
      $existing_resource = $this->get($id);
      $opt['putXml'] = $this->generate_xml($resource, $existing_resource)->asXML();
      $response_xml = $this->ws->edit($opt);
      //@TODO Resource IDENTIFIER to log message
      $this->logger->log("Päivitettiin resurssi: " . $this->resource_name());
    }
    catch (Exception $e) {
      $msg = "Resurssin: " . $this->resource_name() . " {$id} päivittäminen epäonnistui";
      $this->logger->log($msg, $e);
      throw $e;
    }

    return xml_to_array($response_xml);
  }

  /**
   *
   * @param array $display
   * @return array
   * @throws Exception
   */
  protected function all($display = array()) {
    if (!empty($display)) {
      $display = '[' . implode(',', $display) . ']';
    }
    else {
      $display = 'full';
    }
    $opt = array(
        'resource' => $this->resource_name(),
        'display'  => $display,
    );

    try {
      $response_xml = $this->ws->get($opt);
    }
    catch (Exception $e) {
      $msg = "Kaikkien resurssin " . $this->resource_name() . " rivien haku epäonnistui";
      $this->logger->log($msg, $e);
      throw $e;
    }

    return xml_to_array($response_xml);
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
    }
    catch (Exception $e) {
      $msg = "Resurssin: " . $this->resource_name() . " {$id} poistaminen epäonnistui";
      $this->logger->log($msg, $e);
      throw $e;
    }

    return $response_bool;
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
      $msg = "Resurssin:" . $this->resource_name() . " {$id} kuvan luonti epäonnistui";
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
      $msg = "Resurssin: " . $this->resource_name() . " {$id} kuvien haku epäonnistui";
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
        'url' => "{$this->url}api/images/" . $this->resource_name() . "/{$resouce_id}/{$image_id}",
    );

    try {
      $response = $this->ws->delete($opt);
    }
    catch (Exception $e) {
      $msg = "Resurssin: " . $this->resource_name() . " {$resouce_id}"
        . " kuvan {$image_id} poistaminen epäonnistui";
      $this->logger->log($msg, $e);
      throw $e;
    }

    return $response;
  }

  //Child has to implement function which returns schema=blank or repopulated xml
  protected abstract function generate_xml($resource, SimpleXMLElement $existing_resource = null);

  //Child needs to implement function which return resource as string: 'products'...
  protected abstract function resource_name();
}
