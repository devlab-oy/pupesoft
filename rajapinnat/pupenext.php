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

// otetaan tietokanta connect ja funktiot
require "inc/connect.inc";
require "inc/functions.inc";

$yhtio    = mysql_real_escape_string($yhtio);
$kuka     = mysql_real_escape_string($kuka);
$function = mysql_real_escape_string($function);
$params   = json_decode($params);

$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow  = hae_kukarow($kuka, $yhtiorow['yhtio']);

echo json_encode(call_user_func($function, $params));
echo "\n";

function pupenext_luo_myyntitilausotsikko($params) {
  require 'tilauskasittely/luo_myyntitilausotsikko.inc';

  $customer_id = $params->customer_id;

  $sales_order_id = luo_myyntitilausotsikko('RIVISYOTTO', $customer_id);

  return json_encode(array('sales_order_id' => $sales_order_id));
}
