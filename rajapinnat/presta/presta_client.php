<?php

require_once 'rajapinnat/logger.php';
require_once 'rajapinnat/presta/presta_shop_groups.php';
require_once 'rajapinnat/presta/presta_shops.php';
require_once 'rajapinnat/presta/PSWebServiceLibrary.php';

abstract class PrestaClient {
  public $url = null;
  private $api_key = null;
  private $shop_ids = null;
  private $presta_shops = null;
  private $presta_shop_groups = null;

  // ids of installed languages
  protected $languages_table = null;

  // dynamic fields for xml
  protected $dynamic_fields = array();

  // PrestaShopWebservice REST-client
  protected $ws = null;

  /**
   * Schema is used to create / update a resource.
   * It contains given resource blank xml schema
   *
   * @var SimpleXML
   */
  protected $schema = null;

  /**
   *
   * @var Logger
   */
  protected $logger = null;

  public function __construct($url, $api_key, $log_file) {
    if (empty($url)) {
      throw new Exception('Presta URL puuttuu');
    }
    if (empty($api_key)) {
      throw new Exception('Presta API key puuttuu');
    }

    $this->logger = new Logger($log_file);
    $this->url = rtrim($url, '/').'/';
    $this->api_key = $api_key;
    $this->ws = new PrestaShopWebservice($this->url, $this->api_key, false);
  }

  /**
   *
   * @return SimpleXMLElement
   * @throws Exception
   */
  protected function get_empty_schema() {
    if (isset($this->schema)) {
      return $this->schema;
    }

    $resource = $this->resource_name();
    $opt = array(
      'resource' => "{$resource}?schema=blank"
    );

    try {
      $schema = $this->ws->get($opt);
      $msg = "Haetaan {$resource} empty schema";
      $this->logger->log($msg);
    }
    catch (Exception $e) {
      $msg = "Empty schema haku {$resource} epäonnistui";
      $this->logger->log($msg, $e);
      throw $e;
    }

    $this->schema = $schema;

    return $schema;
  }

  protected function empty_xml() {
    $empty = $this->get_empty_schema()->asXML();
    $xml   = new SimpleXMLElement($empty);

    return $xml;
  }

  /**
   * Use $this-> instead of parent:: if you are going to override this class get()
   *
   * @param int     $id
   * @return array
   * @throws Exception
   */
  protected function get($id, $id_shop = null, $id_group_shop = null) {
    try {
      $response_xml = $this->get_as_xml($id, $id_shop, $id_group_shop);
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
   * @param int     $id
   * @return SimpleXMLElement
   * @throws Exception
   */
  protected function get_as_xml($id, $id_shop = null, $id_group_shop = null) {
    $resource = $this->resource_name();
    $opt = array(
      'id'            => $id,
      'id_group_shop' => $id_group_shop,
      'id_shop'       => $id_shop,
      'resource'      => $resource,
    );

    $kauppa  = "";
    $kauppa .= is_null($id_shop) ? '' : "kaupasta {$id_shop} ";
    $kauppa .= is_null($id_group_shop) ? '' : "kaupparyhmästä {$id_group_shop} ";

    try {
      $msg = "Haetaan {$resource} id {$id} {$kauppa}";
      $this->logger->log($msg);
      $response_xml = $this->ws->get($opt);
    }
    catch (Exception $e) {
      $msg = "Haku {$resource} id {$id} {$kauppa}epäonnistui!";
      $this->logger->log($msg, $e);
      throw $e;
    }

    return $response_xml;
  }

  /**
   * Creates the given resource
   *
   * @param array   $resource
   * @return array
   * @throws Exception
   */
  protected function create(array $resource, $id_shop = null, $id_group_shop = null) {
    $opt = array(
      'id_group_shop' => $id_group_shop,
      'id_shop'       => $id_shop,
      'resource'      => $this->resource_name(),
    );

    $kauppa  = "";
    $kauppa .= is_null($id_shop) ? '' : "kauppaan {$id_shop} ";
    $kauppa .= is_null($id_group_shop) ? '' : "kaupparyhmään {$id_group_shop} ";

    try {
      $xml = $this->generate_xml($resource);
      $xml = $this->remove_read_only_fields($xml);
      $opt['postXml'] = $xml->asXML();

      $response_xml = $this->ws->add($opt);

      $this->logger->log("Luotiin {$kauppa}uusi " . $this->resource_name());
    }
    catch (Exception $e) {
      $msg = "Resurssin " . $this->resource_name() . " luonti {$kauppa}epäonnistui";
      $this->logger->log($msg, $e);
      throw $e;
    }

    return xml_to_array($response_xml);
  }

  // removes all readonly files from XML
  // this gets called before update/create. implement this if needed.
  protected function remove_read_only_fields(SimpleXMLElement $xml) {
    return $xml;
  }

  /**
   * Updates given resource
   *
   * @param int     $id
   * @param array   $resource
   * @return array
   * @throws Exception
   */
  protected function update($id, array $resource, $id_shop = null, $id_group_shop = null) {
    //@TODO pitääkö tää blokki olla myös try catchin sisällä??
    $existing_resource = $this->get_as_xml($id, $id_shop);
    $existing_xml = $existing_resource->asXML();

    $xml = $this->generate_xml($resource, $existing_resource);
    $new_xml = $xml->asXML();

    // if nothing has changed, don't update
    if ($existing_xml == $new_xml) {
      $this->logger->log("Ei muutoksia, ei päivitetä");

      // update_xml returns an array aswell
      return xml_to_array($existing_xml);
    }

    return $this->update_xml($id, $xml, $id_shop, $id_group_shop);
  }

  /**
   * Updates given resource straight from the given xml
   * Xml needs to be in Presta format.
   * This is used for example in PrestaSalesOrders
   *
   * @param int     $id
   * @param SimpleXMLElement $xml
   * @return array
   * @throws Exception
   */
  protected function update_xml($id, SimpleXMLElement $xml, $id_shop = null, $id_group_shop = null) {
    $opt = array(
      'id'            => $id,
      'id_group_shop' => $id_group_shop,
      'id_shop'       => $id_shop,
      'resource'      => $this->resource_name(),
    );

    $kauppa  = "";
    $kauppa .= is_null($id_shop) ? '' : "kauppaan {$id_shop} ";
    $kauppa .= is_null($id_group_shop) ? '' : "kaupparyhmään {$id_group_shop} ";

    try {
      $xml = $this->remove_read_only_fields($xml);
      $opt['putXml'] = $xml->asXML();
      $response_xml = $this->ws->edit($opt);

      $this->logger->log("Päivitettiin {$this->resource_name()} id {$id} {$kauppa}");
    }
    catch (Exception $e) {
      $msg = "Päivittäminen epäonnistui " . $this->resource_name() . " id $id {$kauppa}";
      $this->logger->log($msg, $e);
      throw $e;
    }

    return xml_to_array($response_xml);
  }

  /**
   * $display = array('id','name');
   * $filter = array('name'=>'John');
   *
   * @param array   $display Defines SELECT columns
   * @param array   $filters adds WHERE statements. Needs to be key/value pair
   * @return array
   * @throws Exception
   */
  protected function all($display = array(), $filters = array(), $id_shop = null, $id_group_shop = null) {
    $resource = $this->resource_name();

    // esim. 'display' => '[name,value]'
    if (!empty($display)) {
      $display = '[' . implode(',', $display) . ']';
    }
    else {
      $display = 'full';
    }

    $opt = array(
      'display'       => $display,
      'id_group_shop' => $id_group_shop,
      'id_shop'       => $id_shop,
      'resource'      => $resource,
    );

    // esim: 'filter[id]' => '[1|5]'
    foreach ($filters as $key => $value) {
      $key = "filter[{$key}]";
      $opt[$key] = $value;
    }

    $kauppa  = "";
    $kauppa .= is_null($id_shop) ? '' : "kaupasta {$id_shop} ";
    $kauppa .= is_null($id_group_shop) ? '' : "kaupparyhmästä {$id_group_shop} ";

    try {
      $response_xml = $this->ws->get($opt);
      $msg = "Kaikki {$resource} rivit haettu {$kauppa}";
      $this->logger->log($msg);
    }
    catch (Exception $e) {
      $msg = "Kaikkien {$resource} rivien haku {$kauppa}epäonnistui!";
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
   * @param int     $id
   * @return boolean
   * @throws Exception
   */
  protected function delete($id, $id_shop = null, $id_group_shop = null) {
    $opt = array(
      'id'            => $id,
      'id_group_shop' => $id_group_shop,
      'id_shop'       => $id_shop,
      'resource'      => $this->resource_name(),
    );

    $kauppa  = "";
    $kauppa .= is_null($id_shop) ? '' : "kaupasta {$id_shop} ";
    $kauppa .= is_null($id_group_shop) ? '' : "kaupparyhmästä {$id_group_shop} ";

    try {
      $response_bool = $this->ws->delete($opt);
      $msg = "Poistettiin " . $this->resource_name() . " id {$id} {$kauppa}";
      $this->logger->log($msg);
    }
    catch (Exception $e) {
      $msg = "Poistaminen epäonnistui! " . $this->resource_name() . " id {$id} {$kauppa}";
      $this->logger->log($msg, $e);
      throw $e;
    }

    return $response_bool;
  }

  protected function delete_all() {
    $this->logger->log('---------Start ' . $this->resource_name() . ' delete all---------');
    // TODO, this only fetches records from the default shop
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
   *
   * @return array
   */
  protected function shop_ids() {
    return $this->shop_ids;
  }

  /**
   * Sanitezes string for presta link_rewrite column
   *
   * @param string  $string
   * @return string
   */
  protected function saniteze_link_rewrite($string) {
    return preg_replace('/[^a-zA-Z0-9_]/', '', $string);
  }

  protected function set_shop_ids($value) {
    if ((!is_array($value) or count($value) < 1) and !empty($value)) {
      throw new Exception('Shop id pitää olla array tai tyhjä');
    }

    // if we want to reset
    if (empty($value)) {
      $this->shop_ids = null;
      return null;
    }

    if (is_null($this->presta_shops)) {
      $this->presta_shops = new PrestaShops($this->url, $this->api_key, $this->logger->log_file());
    }

    $valid_values = array();

    // if we want to set, check ids are valid
    foreach ($value as $shop_id) {
      $shop = $this->presta_shops->shop_by_id($shop_id);

      if (is_null($shop)) {
        $this->logger->log("Virheellinen shop_id '{$shop_id}', ei voida lisätä.");
      }
      else {
        $valid_values[] = $shop_id;
      }
    }

    // set to null if we don't have any valid values. Presta will add thise to the default store
    if (count($valid_values) == 0) {
      $value = null;
    }
    else {
      $value = $valid_values;
    }

    $this->shop_ids = $value;

    return $value;
  }

  protected function all_shop_ids() {
    if (is_null($this->presta_shops)) {
      $this->presta_shops = new PrestaShops($this->url, $this->api_key, $this->logger->log_file());
    }

    $all = $this->presta_shops->fetch_all();
    $shops = array_column($all, 'id');

    return $shops;
  }

  protected function shop_group_id() {
    if (is_null($this->presta_shop_groups)) {
      $this->presta_shop_groups = new PrestaShopGroups($this->url, $this->api_key, $this->logger->log_file());
    }

    // fetch the first shop group id, we'll use it for now for all products
    $shop_group = $this->presta_shop_groups->first_shop_group();
    $shop_group_id = isset($shop_group['id']) ? (int) $shop_group['id'] : null;

    return $shop_group_id;
  }

  protected function get_language_id($code) {
    if (empty($this->languages_table[$code])) {
      return null;
    }
    else {
      // substract one, since API key starts from zero
      return $this->languages_table[$code] - 1;
    }
  }

  protected function xml_value($value) {
    $value = utf8_encode($value);
    $value = htmlspecialchars($value, ENT_IGNORE);

    return $value;
  }

  protected function assign_dynamic_fields(SimpleXMLElement &$xml_node, $value_array) {
    $parameters = $this->dynamic_fields;

    if (empty($parameters)) {
      return;
    }

    foreach ($parameters as $parameter) {
      $key       = $parameter['arvo'];
      $attribute = $parameter['nimi'];

      if (empty($value_array[$key])) {
        $this->logger->log("VIRHE! Kenttää {$key} ei ole asetettu. Ei voida asettaa arvoa {$attribute} -kenttään.");
        continue;
      }

      $value = $this->xml_value($value_array[$key]);
      $xml_node->$attribute = $value;

      $this->logger->log("Poikkeava arvo {$attribute} -kenttään. Asetetaan {$key} kentän arvo {$value}");
    }
  }

  protected function clean_field($value) {
    $field = empty($value) ? "-" : trim($value);

    return $this->xml_value($field);
  }

  protected function clean_alphanumeric($value, $length = 32) {
    // max $length, special characters not allowed
    $name = preg_replace("/[^0-9a-zA-ZäöåÄÖÅ ]+/", "", substr($value, 0, $length));

    return $this->clean_field($name);
  }

  protected function clean_name($value, $length = 32) {
    // max $length, numbers and special characters not allowed
    $name = preg_replace("/[^a-zA-ZäöåÄÖÅ ]+/", "", substr($value, 0, $length));

    return $this->clean_field($name);
  }

  public function set_dynamic_fields($fields) {
    $this->dynamic_fields = $fields;
  }

  public function set_languages_table($value) {
    if (is_array($value)) {
      $this->languages_table = $value;
    }
  }

  //Child has to implement function which returns schema=blank or repopulated xml
  protected abstract function generate_xml($resource, SimpleXMLElement $existing_resource = null);

  //Child needs to implement function which return resource as string: 'products'...
  protected abstract function resource_name();
}
