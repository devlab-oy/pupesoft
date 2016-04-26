<?php

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
  die ("Tätä scriptiä voi ajaa vain komentoriviltä!\n");
}

$yhtio    = trim($argv[1]);
$kuka     = trim($argv[2]);
$function = trim($argv[3]);
$params   = trim($argv[4]);

if (empty($yhtio)) {
  die ("Et antanut yhtiötä!\n");
}
elseif (empty($kuka)) {
  die ("Et antanut käyttäjää!\n");
}
elseif (empty($function)) {
  die ("Et antanut kutsuttavan funktion nimeä!\n");
}

// otetaan includepath aina rootista
ini_set("include_path",
        ini_get("include_path") .
          PATH_SEPARATOR .
          dirname(dirname(__FILE__)) .
          PATH_SEPARATOR .
          "/usr/share/pear" .
          PATH_SEPARATOR .
          "/usr/share/php/");

// otetaan tietokanta connect ja funktiot
require "inc/connect.inc";
require "inc/functions.inc";

$yhtio    = mysql_real_escape_string($yhtio);
$kuka     = mysql_real_escape_string($kuka);
$function = mysql_real_escape_string($function);
$params   = json_decode($params);

$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow  = hae_kukarow($kuka, $yhtiorow['yhtio']);

ob_start();

$response = json_encode(call_user_func("pupenext_$function", $params));

ob_end_clean();

echo $response;

function pupenext_luo_myyntitilausotsikko($params) {
  global $kukarow, $yhtiorow;

  require "tilauskasittely/luo_myyntitilausotsikko.inc";

  $customer_id = (int) $params->customer_id;

  if (empty($customer_id)) {
    return null;
  }

  $query = "SELECT *
            FROM asiakas
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = {$customer_id}
            LIMIT 1";
  $result = pupe_query($query);

  if (mysql_num_rows($result) != 1) {
    return null;
  }

  $sales_order_id = luo_myyntitilausotsikko('RIVISYOTTO', $customer_id);
  $status = capture_status();

  return array(
    'sales_order_id' => $sales_order_id,
    'status'         => $status,
  );
}

function pupenext_tilaus_valmis($params) {
  global $kukarow, $yhtiorow;

  $order_id = (int) $params->order_id;

  if (empty($order_id)) {
    return null;
  }

  $kukarow['kesken'] = $order_id;

  $query = "SELECT *
            FROM lasku
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = {$order_id}
            LIMIT 1";
  $result = pupe_query($query);

  if (mysql_num_rows($result) != 1) {
    return null;
  }

  $laskurow = mysql_fetch_assoc($result);

  require "tilauskasittely/tilaus-valmis.inc";

  $status = capture_status();

  return array('status' => $status);
}

function capture_status() {
  $status_raw = ob_get_contents();

  if (empty($status_raw)) return null;

  $status_html = new DOMDocument();
  $status_html->loadHTML($status_raw);

  $elements = $status_html->getElementsByTagName('font');

  if ($elements->length < 1) return null;

  $status = $elements->item(0)->textContent;

  return $status;
}
