<?php

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
  die ("Tätä scriptiä voi ajaa vain komentoriviltä!\n");
}

$yhtio    = isset($argv[1]) ? trim($argv[1]) : null;
$kuka     = isset($argv[2]) ? trim($argv[2]) : null;
$function = isset($argv[3]) ? trim($argv[3]) : null;
$params   = isset($argv[4]) ? trim($argv[4]) : null;

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
  ini_get("include_path")    . PATH_SEPARATOR .
  dirname(dirname(__FILE__)) . PATH_SEPARATOR .
  "/usr/share/pear"          . PATH_SEPARATOR .
  "/usr/share/php/"          . PATH_SEPARATOR
);

// otetaan tietokanta connect ja funktiot
require "inc/connect.inc";
require "inc/functions.inc";

$yhtio    = mysql_real_escape_string($yhtio);
$kuka     = mysql_real_escape_string($kuka);
$function = mysql_real_escape_string($function);
$params   = json_decode($params);

$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow  = hae_kukarow($kuka, $yhtiorow['yhtio']);

if (empty($kukarow)) {
  die ("Käyttäjää ei löytynyt!\n");
}

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

function pupenext_lisaa_rivi($params) {
  global $kukarow;

  $count      = isset($params->count)      ? $params->count      : 1;
  $order_id   = isset($params->order_id)   ? $params->order_id   : die("Et antanut tilausnumeroa\n");
  $product_id = isset($params->product_id) ? $params->product_id : die("Et antanut tuotteen tunnusta\n");

  $query = "SELECT *
            FROM tuote
            WHERE tuote.yhtio = '{$kukarow['yhtio']}'
              AND tuote.tunnus = $product_id
            LIMIT 1";
  $result = pupe_query($query);

  $trow = mysql_fetch_assoc($result);

  $query = "SELECT *
            FROM lasku
            WHERE lasku.yhtio = '{$kukarow['yhtio']}'
            AND lasku.tunnus = $order_id";
  $result = pupe_query($query);

  $laskurow = mysql_fetch_assoc($result);

  $kukarow['kesken'] = $laskurow['tunnus'];

  $parametrit = array(
    'kpl'      => $count,
    'laskurow' => $laskurow,
    'trow'     => $trow,
    'tuoteno'  => $trow['tuoteno'],
  );

  list($added_rows_1, $added_rows_2) = lisaa_rivi($parametrit);

  return array(
    'added_rows_1' => $added_rows_1,
    'added_rows_2' => $added_rows_2,
  );
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
