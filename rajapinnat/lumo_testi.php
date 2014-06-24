<?php
// Kutsutaanko CLI:stä
$php_cli = FALSE;

if (php_sapi_name() == 'cli') {
  $php_cli = TRUE;
}

date_default_timezone_set('Europe/Helsinki');

// Kutsutaanko CLI:stä
if (!$php_cli) {
  die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
}

$pupe_root_polku = dirname(dirname(__FILE__));

require "{$pupe_root_polku}/inc/connect.inc";
require "{$pupe_root_polku}/inc/functions.inc";

error_reporting(E_ALL);

require "{$pupe_root_polku}/rajapinnat/lumo_client.php";

if(isset($lumo_service_address) and isset($lumo_service_port)) {
  $clientti = new LumoClient($lumo_service_address, $lumo_service_port);

  if ($clientti->getErrorCount() > 0) {
    exit;
  }
  
  $clientti->startTransaction(1000);
  
  $clientti->cancelTransaction();

  echo "skripti loppu";
}
