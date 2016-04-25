<?php

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
  die ("Tätä scriptiä voi ajaa vain komentoriviltä!\n");
}

$yhtio    = trim($argv[1]);
$kuka     = trim($argv[2]);
$function = trim($argv[3]);
$params   = trim($argv[4]);

if ($yhtio == '') {
  die ("Et antanut yhtiötä!\n");
}
elseif ($kuka == '') {
  die ("Et antanut käyttäjää!\n");
}
elseif ($function == '') {
  die ("Et antanut kutsuttavan funktion nimeä!\n");
}

// lisätään includepathiin pupe-root
ini_set("include_path", ini_get("include_path") . PATH_SEPARATOR . dirname(__FILE__));

$pupe_root_polku = dirname(dirname(__FILE__));

// otetaan tietokanta connect ja funktiot
require "{$pupe_root_polku}/inc/connect.inc";
require "{$pupe_root_polku}/inc/functions.inc";

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
  global $pupe_root_polku;

  require "{$pupe_root_polku}/tilauskasittely/luo_myyntitilausotsikko.inc";

  $customer_id = $params->customer_id;

  $sales_order_id = luo_myyntitilausotsikko('RIVISYOTTO', $customer_id);

  return array('sales_order_id' => $sales_order_id);
}

function pupenext_tilaus_valmis($params) {
  global $kukarow, $yhtiorow, $pupe_root_polku;

  $order_id = $params->order_id;

  $kukarow['kesken'] = $order_id;

  $query = "SELECT *
            FROM lasku
            WHERE tunnus = {$order_id}
            LIMIT 1";
  $result = pupe_query($query);

  $laskurow = mysql_fetch_assoc($result);

  require "{$pupe_root_polku}/tilauskasittely/tilaus-valmis.inc";

  $status_raw = ob_get_contents();

  $status_html = new DOMDocument();
  $status_html->loadHTML($status_raw);

  $status = $status_html->getElementsByTagName('font')->item(0)->textContent;

  return array('status' => $status);
}
